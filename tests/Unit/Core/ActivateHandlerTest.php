<?php
/**
 * Unit Tests for Core\ActivateHandler
 * Tests the REAL production ActivateHandler class
 */

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Core\ActivateHandler;

class ActivateHandlerTest extends TestCase
{
    private ActivateHandler $handler;
    private $db;
    
    protected function setUp(): void
    {
        parent::setUp();
        // Use the REAL ActivateHandler class from production codebase
        $this->handler = new ActivateHandler();
        
        // Connect to test database
        $this->db = new \mysqli(
            getenv('DB_HOST') ?: 'mysql',
            getenv('DB_USERNAME') ?: 'travian_user',
            getenv('DB_PASSWORD') ?: 'travian_password123',
            'travian_global'
        );
        
        if ($this->db->connect_error) {
            $this->markTestSkipped('Database connection failed: ' . $this->db->connect_error);
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
        if ($this->db) {
            $this->db->close();
        }
        parent::tearDown();
    }
    
    /**
     * Helper: Create test user
     */
    private function createTestUser($email, $token = null)
    {
        if ($token === null) {
            $token = bin2hex(random_bytes(16));
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO activation (email, activationCode, activated, time)
            VALUES (?, ?, 0, ?)
        ");
        $time = time();
        $stmt->bind_param('ssi', $email, $token, $time);
        $stmt->execute();
        
        return [
            'email' => $email,
            'token' => $token
        ];
    }
    
    /**
     * Helper: Clean test user
     */
    private function cleanTestUser($email)
    {
        $stmt = $this->db->prepare("DELETE FROM activation WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_activates_account_with_valid_token()
    {
        $testEmail = 'activatetest_' . time() . '@example.com';
        $user = $this->createTestUser($testEmail);
        
        $result = $this->handler->activate($user['email'], $user['token']);
        
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('activated', strtolower($result['message']));
        
        // Clean up
        $this->cleanTestUser($testEmail);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_rejects_invalid_token()
    {
        $testEmail = 'activatetest_' . time() . '@example.com';
        $user = $this->createTestUser($testEmail);
        
        $result = $this->handler->activate($user['email'], 'invalid_token_123');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        
        // Clean up
        $this->cleanTestUser($testEmail);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_rejects_empty_email()
    {
        $result = $this->handler->activate('', 'some_token');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_rejects_empty_token()
    {
        $testEmail = 'activatetest_' . time() . '@example.com';
        
        $result = $this->handler->activate($testEmail, '');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_rejects_non_existent_email()
    {
        $result = $this->handler->activate('nonexistent@example.com', 'some_token');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_updates_database_on_successful_activation()
    {
        $testEmail = 'activatetest_' . time() . '@example.com';
        $user = $this->createTestUser($testEmail);
        
        $result = $this->handler->activate($user['email'], $user['token']);
        
        $this->assertTrue($result['success']);
        
        // Verify database was updated
        $stmt = $this->db->prepare("SELECT activated, activatedTime FROM activation WHERE email = ?");
        $stmt->bind_param('s', $testEmail);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        
        $this->assertEquals(1, $row['activated']);
        $this->assertGreaterThan(0, $row['activatedTime']);
        
        // Clean up
        $this->cleanTestUser($testEmail);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_prevents_double_activation()
    {
        $testEmail = 'activatetest_' . time() . '@example.com';
        $user = $this->createTestUser($testEmail);
        
        // First activation
        $result1 = $this->handler->activate($user['email'], $user['token']);
        $this->assertTrue($result1['success']);
        
        // Second activation attempt
        $result2 = $this->handler->activate($user['email'], $user['token']);
        
        // Should fail or indicate already activated
        $this->assertFalse($result2['success']);
        
        // Clean up
        $this->cleanTestUser($testEmail);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_returns_success_structure()
    {
        $testEmail = 'activatetest_' . time() . '@example.com';
        $user = $this->createTestUser($testEmail);
        
        $result = $this->handler->activate($user['email'], $user['token']);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        if ($result['success']) {
            $this->assertArrayHasKey('message', $result);
        }
        
        // Clean up
        $this->cleanTestUser($testEmail);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_returns_error_structure_on_failure()
    {
        $result = $this->handler->activate('invalid@example.com', 'invalid_token');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertIsString($result['error']);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_handles_sql_special_characters_in_email()
    {
        $testEmail = "test'email" . time() . "@example.com";
        $user = $this->createTestUser($testEmail);
        
        $result = $this->handler->activate($user['email'], $user['token']);
        
        $this->assertTrue($result['success']);
        
        // Clean up
        $this->cleanTestUser($testEmail);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_handles_special_characters_in_token()
    {
        $testEmail = 'activatetest_' . time() . '@example.com';
        $token = 'token_with_special_chars_!@#$%';
        $user = $this->createTestUser($testEmail, $token);
        
        $result = $this->handler->activate($user['email'], $token);
        
        $this->assertTrue($result['success']);
        
        // Clean up
        $this->cleanTestUser($testEmail);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_validates_email_format()
    {
        // Invalid email format
        $result = $this->handler->activate('not-an-email', 'some_token');
        
        $this->assertFalse($result['success']);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_sets_activation_timestamp()
    {
        $testEmail = 'activatetest_' . time() . '@example.com';
        $user = $this->createTestUser($testEmail);
        
        $beforeTime = time();
        $result = $this->handler->activate($user['email'], $user['token']);
        $afterTime = time();
        
        $this->assertTrue($result['success']);
        
        // Check timestamp is set correctly
        $stmt = $this->db->prepare("SELECT activatedTime FROM activation WHERE email = ?");
        $stmt->bind_param('s', $testEmail);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        
        $this->assertGreaterThanOrEqual($beforeTime, $row['activatedTime']);
        $this->assertLessThanOrEqual($afterTime, $row['activatedTime']);
        
        // Clean up
        $this->cleanTestUser($testEmail);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_handles_case_sensitive_tokens()
    {
        $testEmail = 'activatetest_' . time() . '@example.com';
        $token = 'CaseSensitiveToken123';
        $user = $this->createTestUser($testEmail, $token);
        
        // Try with different case
        $result = $this->handler->activate($user['email'], strtolower($token));
        
        // Should fail if case-sensitive
        $this->assertFalse($result['success']);
        
        // Try with correct case
        $result2 = $this->handler->activate($user['email'], $token);
        $this->assertTrue($result2['success']);
        
        // Clean up
        $this->cleanTestUser($testEmail);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_handles_long_tokens()
    {
        $testEmail = 'activatetest_' . time() . '@example.com';
        $token = bin2hex(random_bytes(64)); // Very long token
        $user = $this->createTestUser($testEmail, $token);
        
        $result = $this->handler->activate($user['email'], $token);
        
        $this->assertTrue($result['success']);
        
        // Clean up
        $this->cleanTestUser($testEmail);
    }
    
    /**
     * @test
     * @group activation
     */
    public function it_handles_multiple_users_correctly()
    {
        $testEmail1 = 'user1_' . time() . '@example.com';
        $testEmail2 = 'user2_' . time() . '@example.com';
        
        $user1 = $this->createTestUser($testEmail1);
        $user2 = $this->createTestUser($testEmail2);
        
        // Activate user 1
        $result1 = $this->handler->activate($user1['email'], $user1['token']);
        $this->assertTrue($result1['success']);
        
        // Activate user 2
        $result2 = $this->handler->activate($user2['email'], $user2['token']);
        $this->assertTrue($result2['success']);
        
        // Verify both are activated
        $stmt = $this->db->prepare("SELECT activated FROM activation WHERE email = ?");
        
        $stmt->bind_param('s', $testEmail1);
        $stmt->execute();
        $row1 = $stmt->get_result()->fetch_assoc();
        $this->assertEquals(1, $row1['activated']);
        
        $stmt->bind_param('s', $testEmail2);
        $stmt->execute();
        $row2 = $stmt->get_result()->fetch_assoc();
        $this->assertEquals(1, $row2['activated']);
        
        // Clean up
        $this->cleanTestUser($testEmail1);
        $this->cleanTestUser($testEmail2);
    }
}
