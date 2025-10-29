<?php
/**
 * Unit Tests for Core\ActivateHandler::addActivation
 * Uses REAL production ActivateHandler with world DB (travian_testworld)
 */

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Core\ActivateHandler;
use PDO;

class ActivateHandlerAddActivationTest extends TestCase
{
    private PDO $pdo;
    private string $email;

    protected function setUp(): void
    {
        parent::setUp();
        // Connect to world DB (testworld)
        try {
            $dsn = 'mysql:host=' . (getenv('DB_HOST') ?: 'mysql') . ';dbname=' . (getenv('WORLD_DB_NAME') ?: 'travian_testworld') . ';charset=utf8mb4';
            $user = getenv('DB_USERNAME') ?: 'travian_user';
            $pass = getenv('DB_PASSWORD') ?: 'travian_password123';
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (\Throwable $e) {
            $this->markTestSkipped('World DB connection failed: ' . $e->getMessage());
        }

        // Unique test email
        $this->email = 'addact_' . time() . '_' . bin2hex(random_bytes(3)) . '@example.com';

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
        // Name is bound as string; DB should store it as-is (no injection)
        $this->assertSame($name, $row['name']);
        $this->assertSame($this->email, $row['email']);
    }
}
