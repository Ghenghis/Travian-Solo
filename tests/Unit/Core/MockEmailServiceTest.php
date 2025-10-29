<?php
/**
 * Unit Tests for Core\MockEmailService
 * Tests the REAL production MockEmailService class
 */

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Core\MockEmailService;

class MockEmailServiceTest extends TestCase
{
    private $logDir;
    
    protected function setUp(): void
    {
        parent::setUp();
        // Clear shared log file and in-memory log to avoid cross-test interference
        MockEmailService::clearLog();
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
    }
    
    /**
     * @test
     * @group email
     */
    public function it_sends_activation_email_basic()
    {
        $email = 'test@example.com';
        $code = 'TEST123';
        $worldId = 1;
        
        $result = MockEmailService::sendActivationMail($email, $code, $worldId);
        
        $this->assertTrue($result);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_logs_activation_email_to_shared_file()
    {
        $email = 'test_' . time() . '@example.com';
        $code = 'CODE123';
        $worldId = 2;
        
        MockEmailService::sendActivationMail($email, $code, $worldId);
        
        $content = MockEmailService::getLoggedEmails();
        $this->assertStringContainsString('ACTIVATION EMAIL', $content);
        $this->assertStringContainsString($email, $content);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_logs_activation_email_content_basic()
    {
        $email = 'test_' . time() . '@example.com';
        $code = 'LOGME123';
        $worldId = 3;
        
        MockEmailService::sendActivationMail($email, $code, $worldId);
        
        $content = MockEmailService::getLoggedEmails();
        
        $this->assertStringContainsString($email, $content);
        $this->assertStringContainsString($code, $content);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_sends_password_recovery_email()
    {
        $email = 'test@example.com';
        $serverId = 1;
        $worldId = 1;
        $uid = 123;
        $recoveryCode = 'REC123';
        
        $result = MockEmailService::sendPasswordForgotten($email, $serverId, $worldId, $uid, $recoveryCode);
        
        $this->assertTrue($result);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_logs_password_recovery_to_shared_file()
    {
        $email = 'test_' . time() . '@example.com';
        $serverId = 1;
        $worldId = 1;
        $uid = 5;
        $recoveryCode = 'RCV456';
        
        MockEmailService::sendPasswordForgotten($email, $serverId, $worldId, $uid, $recoveryCode);
        
        $content = MockEmailService::getLoggedEmails();
        $this->assertStringContainsString('PASSWORD RECOVERY', $content);
        $this->assertStringContainsString($email, $content);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_sends_activation_email()
    {
        $email = 'test@example.com';
        $code = 'ABC123';
        $worldId = 1;
        
        $result = MockEmailService::sendActivationMail($email, $code, $worldId);
        
        $this->assertTrue($result);
        
        // Check in-memory log
        $log = MockEmailService::getEmailLog();
        $this->assertCount(1, $log);
        $this->assertEquals('activation', $log[0]['type']);
        $this->assertEquals($email, $log[0]['to']);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_logs_forgotten_accounts_content()
    {
        $email = 'test_' . time() . '@example.com';
        $accounts = ['account1', 'account2', 'account3'];
        
        MockEmailService::sendForgottenAccounts($email, $accounts);
        
        $content = MockEmailService::getLoggedEmails();
        
        $this->assertStringContainsString($email, $content);
        foreach ($accounts as $account) {
            $this->assertStringContainsString($account, $content);
        }
    }
    
    /**
     * @test
     * @group email
     */
    public function it_includes_timestamp_in_log_file()
    {
        $email = 'time@example.com';
        $code = 'TIMESTAMP123';
        $worldId = 1;
        
        MockEmailService::sendActivationMail($email, $code, $worldId);
        
        // Check shared log file contains timestamp
        $content = MockEmailService::getLoggedEmails();
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $content);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_logs_activation_email_content()
    {
        $email = 'test@example.com';
        $code = 'XYZ789';
        $worldId = 2;
        
        MockEmailService::sendActivationMail($email, $code, $worldId);
        
        // Check file log content
        $content = MockEmailService::getLoggedEmails();
        $this->assertStringContainsString('ACTIVATION EMAIL', $content);
        $this->assertStringContainsString($email, $content);
        $this->assertStringContainsString($code, $content);
        $this->assertStringContainsString((string)$worldId, $content);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_handles_concurrent_email_sends()
    {
        // Clear existing logs
        MockEmailService::clearLog();
        
        $emails = [
            ['email' => 'concurrent1@example.com', 'code' => 'C1', 'worldId' => 1],
            ['email' => 'concurrent2@example.com', 'code' => 'C2', 'worldId' => 1],
            ['email' => 'concurrent3@example.com', 'code' => 'C3', 'worldId' => 1],
            ['email' => 'concurrent4@example.com', 'code' => 'C4', 'worldId' => 1],
            ['email' => 'concurrent5@example.com', 'code' => 'C5', 'worldId' => 1],
        ];
        
        foreach ($emails as $e) {
            MockEmailService::sendActivationMail($e['email'], $e['code'], $e['worldId']);
        }
        
        // Verify in-memory log has all emails
        $log = MockEmailService::getEmailLog();
        $this->assertCount(5, $log);
        
        // Verify file log contains all emails
        $content = MockEmailService::getLoggedEmails();
        foreach ($emails as $e) {
            $this->assertStringContainsString($e['email'], $content);
            $this->assertStringContainsString($e['code'], $content);
        }
    }
    
    /**
     * @test
     * @group email
     */
    public function it_preserves_link_parameters()
    {
        $email = 'links@example.com';
        $code = 'LINK123';
        $worldId = 1;
        
        MockEmailService::sendActivationMail($email, $code, $worldId);
        
        // Verify parameters appear in shared log file
        $content = MockEmailService::getLoggedEmails();
        $this->assertStringContainsString($email, $content);
        $this->assertStringContainsString($code, $content);
        $this->assertStringContainsString((string)$worldId, $content);
        $this->assertStringContainsString('ACTIVATION EMAIL', $content);
    }
    public function it_handles_special_characters_in_links()
    {
        $email = 'test@example.com';
        $linkWithSpecialChars = 'http://localhost/activate?token=test&param=value%20with%20spaces';
        
        $result = MockEmailService::sendActivationMail($email, $linkWithSpecialChars, 'en');
        
        $this->assertTrue($result);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_returns_boolean_result()
    {
        $email = 'test@example.com';
        $code = 'BOOL123';
        $worldId = 1;
        
        $result = MockEmailService::sendActivationMail($email, $code, $worldId);
        
        $this->assertIsBool($result);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_handles_unicode_in_email()
    {
        $email = 'tëst@example.com';
        $code = 'UNICODE';
        $worldId = 1;
        
        $result = MockEmailService::sendActivationMail($email, $code, $worldId);
        
        $this->assertIsBool($result);
    }
}
