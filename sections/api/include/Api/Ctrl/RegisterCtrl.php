<?php

namespace Api\Ctrl;

use Api\ApiAbstractCtrl;
use Core\ActivateHandler;
use Core\EmailService;
use Core\Newsletter;
use Core\Server;
use Core\WebService;
use Database\DB;
use Database\ServerDB;
use Exceptions\MissingParameterException;
use PDO;

class RegisterCtrl extends ApiAbstractCtrl
{
    public function resendActivationMail()
    {
        $needs = ['gameWorld', 'email'];
        foreach ($needs as $k) {
            if (!isset($this->payload[$k])) {
                throw new MissingParameterException($k);
            }
        }
        $this->response['success'] = false;
        $server = Server::getServerById((int)$this->payload['gameWorld']);
        if (!$server) {
            $this->response['fields']['email'] = 'unknownGameWorld';
            return;
        }
        $email = filter_var($this->payload['email'], FILTER_SANITIZE_EMAIL);
        $activation = $this->getActivationByEmail($server['id'], $email);
        if ($activation) {
            $this->response['success'] = true;
            EmailService::sendActivationMail($email, $activation['worldId'], $server['worldId'], $activation['name'], $activation['activationCode']);
            return;
        }
        $this->response['fields']['email'] = 'emailUnknown';
    }

    private function getActivationByEmail($worldId, $email)
    {
        $db = DB::getInstance();
        $stmt = $db->prepare("SELECT * FROM activation WHERE worldId=:wid AND email=:email AND used=0");
        $stmt->bindValue('wid', $worldId, PDO::PARAM_INT);
        $stmt->bindValue('email', $email, PDO::PARAM_STR);
        $stmt->execute();
        if (!$stmt->rowCount()) {
            return false;
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function activate()
    {
        global $globalConfig;
        $needs = ['gameWorld', 'activationCode', 'password', 'captcha'];
        foreach ($needs as $k) {
            if (!isset($this->payload[$k])) {
                throw new MissingParameterException($k);
            }
        }
        $this->response['success'] = false;
        // TEMPORARILY DISABLED FOR TESTING - TODO: Re-enable captcha in production
        /* 
        $recaptcha = new \ReCaptcha\ReCaptcha($globalConfig['staticParameters']['recaptcha_private_key']);
        $resp = $recaptcha->verify($this->payload['captcha'], WebService::ipAddress());
        if (!$resp->isSuccess()) {
            $this->response['fields']['captcha'] = 'invalidCaptcha';
            return;
        }
        */
        $activation = $this->getActivationByActivationCode((int)$this->payload['gameWorld'], $this->payload['activationCode']);
        if ($activation) {
            $password = $this->payload['password'];
            if (strlen($password) < 4) {
                return;
            }
            if (empty($password)) {
                return;
            }
            if (!empty($password) && $password == $activation['name']) {
                $this->response['fields']['password'] = 'passwordLikeName';
                return;
            }
            //passwordInsecure
            $db = DB::getInstance();
            $db->query("UPDATE activation SET used=1 WHERE id=" . $activation['id']);
            // TEMPORARILY DISABLED FOR TESTING - TODO: Create newsletter table or re-enable
            /*
            if ($activation['newsletter'] || TRUE) {
                Newsletter::addEmail($activation['email']);
            }
            */
            $server = Server::getServerById($activation['worldId']);
            $serverDB = ServerDB::getInstance($server['configFileLocation']);
            $token = ActivateHandler::addActivation($activation['name'], $password, $activation['email'], $activation['refUid'], $serverDB);
            $this->response['success'] = true;
            $this->response['redirect'] = $server['gameWorldUrl'] . 'activate.php?token=' . $token;
        } else {
            $this->response['fields']['activationCode'] = 'activationNotFound';
        }
    }

    private function getActivationByActivationCode($worldId, $activationCode)
    {
        $db = DB::getInstance();
        $stmt = $db->prepare("SELECT * FROM activation WHERE worldId=:wid AND activationCode=:activationCode AND used=0");
        $stmt->bindValue('wid', $worldId, PDO::PARAM_INT);
        $stmt->bindValue('activationCode', $activationCode, PDO::PARAM_STR);
        $stmt->execute();
        if (!$stmt->rowCount()) {
            return false;
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function register()
    {
        // Don't break the reference! Add keys instead of reassigning
        $this->response['_METHOD_CALLED'] = 'register';
        $this->response['debug'] = ['step' => 'start'];
        $needs = ['gameWorld', 'username', 'email', 'termsAndConditions'];
        foreach ($needs as $k) {
            if (!isset($this->payload[$k])) {
                $this->response['debug']['missing'] = $k;
                throw new MissingParameterException($k);
            }
        }
        $this->response['success'] = false;
        $this->response['debug']['step'] = 'initialized';
        $server = Server::getServerById((int)$this->payload['gameWorld']);
        if (!$server) {
            $this->response['fields']['username'] = 'unknownGameWorld';
            return;
        }
        if ($server['registerClosed'] == 1 || $server['finished'] == 1) {
            $this->response['fields']['username'] = 'registrationClosed';
            return;
        }
        $inviter = isset($this->payload['inviter']) ? $this->payload['inviter'] : [];
        $username = trim($this->payload['username']);
        $password = isset($this->payload['password']) ? $this->payload['password'] : null;
        $email = trim($this->payload['email']);
        $registrationKey = isset($this->payload['registrationKey']) ? trim($this->payload['registrationKey']) : null;
        $subscribeNewsletter = isset($this->payload['subscribeNewsletter']) && $this->payload['subscribeNewsletter'];
        $termsAndConditions = isset($this->payload['termsAndConditions']) && $this->payload['termsAndConditions'];
        $errors = 0;
        $this->response['debug']['email_checks'] = [];
        {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->response['debug']['email_checks'][] = 'FAILED: Invalid format';
                ++$errors;
            } else {
                $this->response['debug']['email_checks'][] = 'PASSED: Valid format';
            }
            if (empty($email)) {
                $this->response['debug']['email_checks'][] = 'FAILED: Empty';
                ++$errors;
            } else {
                $this->response['debug']['email_checks'][] = 'PASSED: Not empty';
            }
            if (strlen($email) < 5) {
                $this->response['debug']['email_checks'][] = 'FAILED: Too short (< 5)';
                ++$errors;
            } else {
                $this->response['debug']['email_checks'][] = 'PASSED: Length OK (>= 5)';
            }
            if (strlen($email) > 90) {
                $this->response['debug']['email_checks'][] = 'FAILED: Too long (> 90)';
                ++$errors;
            } else {
                $this->response['debug']['email_checks'][] = 'PASSED: Length OK (<= 90)';
            }
        }
        {
            if (!empty($username) && $this->isNameBlackListed($username)) {
                if (!isset($this->response['username']['password'])) {
                    $this->response['fields']['username'] = 'usernameBlacklisted';
                }
                ++$errors;
            }
        }
        {
            if (empty($username)
                || !filter_var($username, FILTER_SANITIZE_STRING)
                || strpos($username, '@') !== FALSE
            ) {
                if (!isset($this->response['fields']['username'])) {
                    $this->response['fields']['username'] = 'invalidChars';
                }
                ++$errors;
            }
            if (strlen($username) < 3) {
                ++$errors;
            }
            if (strlen($username) > 15) {
                ++$errors;
            }
        }
        if ($server['activation'] == 0) {
            if (empty($password)) {
                $errors++;
            }
            if (!empty($password) && !empty($username) && $password == $username) {
                if (!isset($this->response['fields']['password'])) {
                    $this->response['fields']['password'] = 'passwordLikeName';
                }
                ++$errors;
            }
        }
        if (!$termsAndConditions) {
            ++$errors;
        }
        $preRegister = FALSE;
        if ($server['preregistration_key_only']) {
            if (empty($registrationKey)) {
                ++$errors;
            } else if (!$this->checkPreRegistrationKey($server['id'], $registrationKey, null)) {
                $this->response['fields']['registrationKey'] = 'registrationCodeInvalid';
                ++$errors;
            } else {
                $preRegister = TRUE;
            }
        }
        $serverDB = ServerDB::getInstance($server['configFileLocation']);
        if (!isset($this->response['fields']['username'])) {
            if ($this->doesNameExists($server['id'], $serverDB, $username)) {
                $this->response['fields']['username'] = 'nameAlreadyExists';
                $errors++;
            }
        }
        if (!isset($this->response['fields']['email'])) {
            if ($this->doesEmailExists($server['id'], $serverDB, $email)) {
                $this->response['fields']['email'] = 'emailAlreadyRegistered';
                $errors++;
            } else if ($this->isEmailBlackListed($email)) {
                $this->response['fields']['email'] = 'emailInvalid';
                $errors++;
            }
        }
        $this->response['debug']['errors'] = $errors;
        $this->response['debug']['fields'] = $this->response['fields'] ?? [];
        if ($errors) {
            $this->response['debug']['step'] = 'validation_failed';
            return;
        }
        $this->response['debug']['step'] = 'validation_passed';
        if ($preRegister) {
            $this->useRegistrationKey($server['id'], $registrationKey);
        }
        $activationCode = substr(sha1(microtime() . $username . $email), 0, mt_rand(10, 13));
        $refUid = 0;
        if (isset($inviter['gameWorldName']) && isset($inviter['uid'])) {
            $refUid = $inviter['uid'];
        }
        $this->response['success'] = true;
        error_log("RegisterCtrl: activation={$server['activation']}, taking " . ($server['activation'] == 0 ? 'ACTIVATION=0 path' : 'ACTIVATION=1 path'));
        
        if ($server['activation'] == 0) {
            error_log("RegisterCtrl: Using ActivateHandler (no email activation)");
            $token = ActivateHandler::addActivation($username, $password, $email, $refUid, $serverDB);
            EmailService::sendYouRegisteredOn($email, $server['worldId'], $username, $password, $server['gameWorldUrl']);
            $this->response['redirect'] = $server['gameWorldUrl'] . 'activate.php?token=' . $token;
        } else {
            error_log("RegisterCtrl: Email activation required, inserting to global DB");
            $db = DB::getInstance();
            // Generate a token for the activation record
            $token = md5($username . $email . microtime());
            $stmt = $db->prepare("INSERT INTO activation (`worldId`, `name`, `password`, `email`, `activationCode`, `newsletter`, `token`, `refUid`, `time`) VALUES (:wid, :username, :password, :email, :activationCode, :newsletter, :token, :refUid, :time)");
            $stmt->bindValue('wid', $server['id'], PDO::PARAM_INT);
            $stmt->bindValue('username', $username, PDO::PARAM_STR);
            $stmt->bindValue('password', !empty($password) ? $password : sha1(microtime() . time()), PDO::PARAM_STR);
            $stmt->bindValue('email', $email, PDO::PARAM_STR);
            $stmt->bindValue('activationCode', $activationCode, PDO::PARAM_STR);
            $stmt->bindValue('newsletter', $subscribeNewsletter ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue('token', $token, PDO::PARAM_STR);
            $stmt->bindValue('refUid', $refUid, PDO::PARAM_INT);
            $stmt->bindValue('time', time(), PDO::PARAM_INT);
            
            error_log("RegisterCtrl: About to execute INSERT for user: $username");
            $stmt->execute();
            error_log("RegisterCtrl: INSERT executed successfully, rowCount=" . $stmt->rowCount());
            
            error_log("RegisterCtrl: Sending activation email");
            EmailService::sendActivationMail($email, $server['id'], $server['worldId'], $username, $activationCode);
            error_log("RegisterCtrl: Registration complete for user: $username");
        }
    }

    private function isNameBlackListed($name)
    {
        $invalidNames = explode(",", file_get_contents(FILTERING_PATH . "blackListedNames.txt"));
        //names like natars | multihuneter | support
        foreach ($invalidNames as $blackListed) {
            $percent = 0;
            similar_text($name, $blackListed, $percent);
            if ($percent > 78.5) {
                return true; //:|
            }
        }
        return false;
    }

    private function checkPreRegistrationKey($worldId, $key, $name)
    {
        $db = DB::getInstance();
        $stmt = $db->prepare("SELECT COUNT(id) FROM preregistration_keys WHERE worldId=:worldId AND pre_key=:preRegKey AND used=0");
        $stmt->bindValue('worldId', $worldId, PDO::PARAM_STR);
        $stmt->bindValue('preRegKey', $key, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    private function doesNameExists($activeServerId, PDO $serverDB, $playerName)
    {
        {
            $stmt = DB::getInstance()->prepare("SELECT COUNT(id) FROM activation WHERE name=:name AND worldId=:worldId AND used=0");
            $stmt->bindValue("name", $playerName, PDO::PARAM_STR);
            $stmt->bindValue("worldId", $activeServerId, PDO::PARAM_INT);
            $stmt->execute();
            if ((int)$stmt->fetchColumn() > 0) return true;
        }
        {
            $stmt = $serverDB->prepare("SELECT COUNT(id) FROM users WHERE name=:name");
            $stmt->bindValue("name", $playerName);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) return true;
        }
        {
            $stmt = $serverDB->prepare("SELECT COUNT(id) FROM activation WHERE name=:name");
            $stmt->bindValue("name", $playerName);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) return true;
        }
        return false;
    }

    private function doesEmailExists($activeServerId, PDO $serverDB, $email)
    {
        $stmt = DB::getInstance()->prepare("SELECT COUNT(id) FROM activation WHERE email=:email AND worldId=:worldId AND used=0");
        $stmt->bindValue("email", $email, PDO::PARAM_STR);
        $stmt->bindValue("worldId", $activeServerId, PDO::PARAM_INT);
        $stmt->execute();
        if ((int)$stmt->fetchColumn() > 0) return true;
        {
            $stmt = $serverDB->prepare("SELECT COUNT(id) FROM activation WHERE email=:email");
            $stmt->bindValue("email", $email, PDO::PARAM_STR);
            $stmt->execute();
            if ((int)$stmt->fetchColumn() > 0) return true;
        }
        $stmt = $serverDB->prepare("SELECT COUNT(uid) FROM changeEmail WHERE email=:email");
        $stmt->bindValue("email", $email, PDO::PARAM_STR);
        $stmt->execute();
        if ((int)$stmt->fetchColumn() > 0) return true;

        $stmt = $serverDB->prepare("SELECT COUNT(id) FROM users WHERE email=:email AND email_verified=1");
        $stmt->bindValue("email", $email, PDO::PARAM_STR);
        $stmt->execute();
        if ((int)$stmt->fetchColumn() > 0) return true;

        return false;
    }

    private function isEmailBlackListed($email)
    {
        $email = strtolower($email);
        $db = DB::getInstance();
        $stmt = $db->prepare("SELECT COUNT(id) FROM email_blacklist WHERE email=:email");
        $stmt->bindValue('email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    private function useRegistrationKey($worldId, $key)
    {
        $db = DB::getInstance();
        $stmt = $db->prepare("UPDATE preregistration_keys SET used=1 WHERE worldId=:worldId AND pre_key=:preRegKey");
        $stmt->bindValue('worldId', $worldId, PDO::PARAM_STR);
        $stmt->bindValue('preRegKey', $key, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}