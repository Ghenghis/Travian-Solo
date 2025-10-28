<?php
namespace Core;

/**
 * Mock Email Service for Testing
 * Logs emails instead of sending them via SMTP
 */
class MockEmailService
{
    private static $emailLog = [];
    
    /**
     * Send activation email (mocked)
     */
    public static function sendActivationMail($email, $activationCode, $worldId)
    {
        $emailData = [
            'type' => 'activation',
            'to' => $email,
            'subject' => 'Activate Your Travian Account',
            'activation_code' => $activationCode,
            'world_id' => $worldId,
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'LOGGED (not sent - mock mode)'
        ];
        
        self::$emailLog[] = $emailData;
        
        // Log to file for debugging
        $logFile = __DIR__ . '/../../../../storage/email-log.txt';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = sprintf(
            "[%s] ACTIVATION EMAIL\nTo: %s\nActivation Code: %s\nWorld ID: %s\n---\n",
            $emailData['timestamp'],
            $email,
            $activationCode,
            $worldId
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        return true;
    }
    
    /**
     * Send password recovery email (mocked)
     */
    public static function sendPasswordForgotten($email, $serverId, $worldId, $uid, $recoveryCode)
    {
        $emailData = [
            'type' => 'password_recovery',
            'to' => $email,
            'subject' => 'Password Recovery',
            'recovery_code' => $recoveryCode,
            'uid' => $uid,
            'world_id' => $worldId,
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'LOGGED (not sent - mock mode)'
        ];
        
        self::$emailLog[] = $emailData;
        
        // Log to file
        $logFile = __DIR__ . '/../../../../storage/email-log.txt';
        $logEntry = sprintf(
            "[%s] PASSWORD RECOVERY\nTo: %s\nRecovery Code: %s\nUID: %s\nWorld ID: %s\n---\n",
            $emailData['timestamp'],
            $email,
            $recoveryCode,
            $uid,
            $worldId
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        return true;
    }
    
    /**
     * Send forgotten accounts email (mocked)
     */
    public static function sendForgottenAccounts($email, $gameWorlds)
    {
        $emailData = [
            'type' => 'forgotten_accounts',
            'to' => $email,
            'subject' => 'Your Travian Accounts',
            'game_worlds' => $gameWorlds,
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'LOGGED (not sent - mock mode)'
        ];
        
        self::$emailLog[] = $emailData;
        
        // Log to file
        $logFile = __DIR__ . '/../../../../storage/email-log.txt';
        $logEntry = sprintf(
            "[%s] FORGOTTEN ACCOUNTS\nTo: %s\nWorlds: %s\n---\n",
            $emailData['timestamp'],
            $email,
            json_encode($gameWorlds)
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        return true;
    }
    
    /**
     * Get all logged emails
     */
    public static function getEmailLog()
    {
        return self::$emailLog;
    }
    
    /**
     * Clear email log
     */
    public static function clearLog()
    {
        self::$emailLog = [];
        $logFile = __DIR__ . '/../../../../storage/email-log.txt';
        if (file_exists($logFile)) {
            unlink($logFile);
        }
    }
    
    /**
     * Get logged emails from file
     */
    public static function getLoggedEmails()
    {
        $logFile = __DIR__ . '/../../../../storage/email-log.txt';
        if (file_exists($logFile)) {
            return file_get_contents($logFile);
        }
        return "No emails logged yet.";
    }
}
