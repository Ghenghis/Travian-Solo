<?php
/**
 * Unit Tests for Core\ActivateHandler
 * Tests the REAL production ActivateHandler::addActivation method
 */

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Core\ActivateHandler;

class ActivateHandlerTest extends TestCase
{
    private \PDO $pdo;
    private string $email;
    
    protected function setUp(): void
    {
        parent::setUp();
        // Connect to world DB (testworld)
        try {
            $dsn = 'mysql:host=' . (getenv('DB_HOST') ?: 'mysql') . ';dbname=' . (getenv('WORLD_DB_NAME') ?: 'travian_testworld') . ';charset=utf8mb4';
            $user = getenv('DB_USERNAME') ?: 'travian_user';
            $pass = getenv('DB_PASSWORD') ?: 'travian_password123';
            $this->pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        } catch (\Throwable $e) {
            $this->markTestSkipped('World DB connection failed: ' . $e->getMessage());
        }

        // Unique test email
        $this->email = 'activatehandler_' . time() . '_' . bin2hex(random_bytes(3)) . '@example.com';

        // Ensure activation table exists, else skip
        try {
            $this->pdo->query('SELECT 1 FROM activation LIMIT 1');
        } catch (\Throwable $e) {
            $this->markTestSkipped('activation table missing in world DB: ' . $e->getMessage());
        }
    }
    
    protected function tearDown(): void
    {
        if (isset($this->pdo)) {
            try {
                $stmt = $this->pdo->prepare('DELETE FROM activation WHERE email = :email');
                $stmt->execute([':email' => $this->email]);
            } catch (\Throwable $e) {
                // ignore
            }
        }
        parent::tearDown();
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_adds_activation_and_returns_token()
    {
        $name = 'TestUser_' . substr($this->email, 0, 6);
        $password = 'Passw0rd!';
        $refUid = 0;

        $token = ActivateHandler::addActivation($name, $password, $this->email, $refUid, $this->pdo);

        $this->assertNotEmpty($token);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);

        // Verify DB row
        $stmt = $this->pdo->prepare('SELECT name, password, email, token, refUid, time FROM activation WHERE email = :email');
        $stmt->execute([':email' => $this->email]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row, 'Activation row should exist');
        $this->assertSame($name, $row['name']);
        $this->assertSame(sha1($password), $row['password']);
        $this->assertSame($this->email, $row['email']);
        $this->assertSame($token, $row['token']);
        $this->assertSame($refUid, (int)$row['refUid']);

        $now = time();
        $this->assertIsNumeric($row['time']);
        $this->assertGreaterThanOrEqual($now - 5, (int)$row['time']);
        $this->assertLessThanOrEqual($now + 5, (int)$row['time']);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_handles_special_characters_in_email_and_name()
    {
        // Determine max length of the `name` column to avoid truncation
        $maxLen = 32;
        try {
            $col = $this->pdo->query("SHOW COLUMNS FROM activation LIKE 'name'")->fetch();
            if ($col && isset($col['Type']) && preg_match('/varchar\((\d+)\)/i', $col['Type'], $m)) {
                $maxLen = (int)$m[1];
            }
        } catch (\Throwable $e) {
            // Keep default
        }

        $rawName = "O'Reilly <script>";
        $name = mb_substr($rawName, 0, max(1, $maxLen));
        $password = 'P@ss123!';
        $refUid = 42;

        $token = ActivateHandler::addActivation($name, $password, $this->email, $refUid, $this->pdo);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);

        $stmt = $this->pdo->prepare('SELECT name, email FROM activation WHERE email = :email');
        $stmt->execute([':email' => $this->email]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertSame($name, $row['name']);
        $this->assertSame($this->email, $row['email']);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_generates_unique_tokens_for_multiple_activations()
    {
        $name1 = 'User1';
        $name2 = 'User2';
        $email2 = 'activatehandler2_' . time() . '_' . bin2hex(random_bytes(3)) . '@example.com';
        $password = 'Pass123!';

        $token1 = ActivateHandler::addActivation($name1, $password, $this->email, 0, $this->pdo);
        $token2 = ActivateHandler::addActivation($name2, $password, $email2, 0, $this->pdo);

        $this->assertNotEquals($token1, $token2, 'Tokens should be unique');
        
        // Cleanup second email
        try {
            $stmt = $this->pdo->prepare('DELETE FROM activation WHERE email = :email');
            $stmt->execute([':email' => $email2]);
        } catch (\Throwable $e) {
            // ignore
        }
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_hashes_password_with_sha1()
    {
        $name = 'HashTest';
        $password = 'MyPassword123';

        $token = ActivateHandler::addActivation($name, $password, $this->email, 0, $this->pdo);

        $stmt = $this->pdo->prepare('SELECT password FROM activation WHERE email = :email');
        $stmt->execute([':email' => $this->email]);
        $row = $stmt->fetch();

        $this->assertSame(sha1($password), $row['password'], 'Password should be hashed with SHA1');
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_stores_refuid_correctly()
    {
        $name = 'RefTest';
        $password = 'Pass123!';
        $refUid = 999;

        $token = ActivateHandler::addActivation($name, $password, $this->email, $refUid, $this->pdo);

        $stmt = $this->pdo->prepare('SELECT refUid FROM activation WHERE email = :email');
        $stmt->execute([':email' => $this->email]);
        $row = $stmt->fetch();

        $this->assertSame($refUid, (int)$row['refUid'], 'refUid should match');
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_sets_timestamp_within_reasonable_range()
    {
        $name = 'TimeTest';
        $password = 'Pass123!';
        $beforeTime = time();

        $token = ActivateHandler::addActivation($name, $password, $this->email, 0, $this->pdo);
        
        $afterTime = time();

        $stmt = $this->pdo->prepare('SELECT time FROM activation WHERE email = :email');
        $stmt->execute([':email' => $this->email]);
        $row = $stmt->fetch();

        $storedTime = (int)$row['time'];
        $this->assertGreaterThanOrEqual($beforeTime, $storedTime);
        $this->assertLessThanOrEqual($afterTime, $storedTime);
    }
}
