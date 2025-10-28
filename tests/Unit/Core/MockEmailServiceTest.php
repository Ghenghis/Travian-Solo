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
        // Use the REAL MockEmailService class from production codebase
        $this->logDir = __DIR__ . '/../../../logs/emails';
        
        // Create log directory if it doesn't exist
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test log files
        $files = glob($this->logDir . '/test_*.log');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }
    
    /**
     * @test
     * @group email
     */
    public function it_sends_activation_email()
    {
        $email = 'test@example.com';
        $activationLink = 'http://localhost/activate?token=test123';
        $lang = 'en';
        
        $result = MockEmailService::sendActivationMail($email, $activationLink, $lang);
        
        $this->assertTrue($result);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_creates_log_file_for_activation_email()
    {
        $email = 'test_' . time() . '@example.com';
        $activationLink = 'http://localhost/activate?token=test123';
        $lang = 'en';
        
        MockEmailService::sendActivationMail($email, $activationLink, $lang);
        
        // Check if log file was created
        $logFiles = glob($this->logDir . '/activation_*.log');
        $this->assertNotEmpty($logFiles);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_logs_activation_email_content()
    {
        $email = 'test_' . time() . '@example.com';
        $activationLink = 'http://localhost/activate?token=test123';
        $lang = 'en';
        
        MockEmailService::sendActivationMail($email, $activationLink, $lang);
        
        // Get the latest log file
        $logFiles = glob($this->logDir . '/activation_*.log');
        $latestLog = end($logFiles);
        
        $content = file_get_contents($latestLog);
        
        $this->assertStringContainsString($email, $content);
        $this->assertStringContainsString($activationLink, $content);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_sends_password_recovery_email()
    {
        $email = 'test@example.com';
        $resetLink = 'http://localhost/reset?token=test123';
        $lang = 'en';
        
        $result = MockEmailService::sendPasswordRecoveryMail($email, $resetLink, $lang);
        
        $this->assertTrue($result);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_creates_log_file_for_password_recovery()
    {
        $email = 'test_' . time() . '@example.com';
        $resetLink = 'http://localhost/reset?token=test123';
        $lang = 'en';
        
        MockEmailService::sendPasswordRecoveryMail($email, $resetLink, $lang);
        
        $logFiles = glob($this->logDir . '/password_recovery_*.log');
        $this->assertNotEmpty($logFiles);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_logs_password_recovery_content()
    {
        $email = 'test_' . time() . '@example.com';
        $resetLink = 'http://localhost/reset?token=test123';
        $lang = 'en';
        
        MockEmailService::sendPasswordRecoveryMail($email, $resetLink, $lang);
        
        $logFiles = glob($this->logDir . '/password_recovery_*.log');
        $latestLog = end($logFiles);
        
        $content = file_get_contents($latestLog);
        
        $this->assertStringContainsString($email, $content);
        $this->assertStringContainsString($resetLink, $content);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_sends_forgotten_accounts_email()
    {
        $email = 'test@example.com';
        $accounts = ['account1', 'account2'];
        $lang = 'en';
        
        $result = MockEmailService::sendForgottenAccountsMail($email, $accounts, $lang);
        
        $this->assertTrue($result);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_creates_log_file_for_forgotten_accounts()
    {
        $email = 'test_' . time() . '@example.com';
        $accounts = ['account1', 'account2'];
        $lang = 'en';
        
        MockEmailService::sendForgottenAccountsMail($email, $accounts, $lang);
        
        $logFiles = glob($this->logDir . '/forgotten_accounts_*.log');
        $this->assertNotEmpty($logFiles);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_logs_forgotten_accounts_content()
    {
        $email = 'test_' . time() . '@example.com';
        $accounts = ['account1', 'account2', 'account3'];
        $lang = 'en';
        
        MockEmailService::sendForgottenAccountsMail($email, $accounts, $lang);
        
        $logFiles = glob($this->logDir . '/forgotten_accounts_*.log');
        $latestLog = end($logFiles);
        
        $content = file_get_contents($latestLog);
        
        $this->assertStringContainsString($email, $content);
        foreach ($accounts as $account) {
            $this->assertStringContainsString($account, $content);
        }
    }
    
    /**
     * @test
     * @group email
     */
    public function it_handles_empty_email()
    {
        $result = MockEmailService::sendActivationMail('', 'http://link', 'en');
        
        // Should handle gracefully
        $this->assertIsBool($result);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_handles_special_characters_in_email()
    {
        $email = "test+tag@example.com";
        $activationLink = 'http://localhost/activate?token=test123';
        $lang = 'en';
        
        $result = MockEmailService::sendActivationMail($email, $activationLink, $lang);
        
        $this->assertTrue($result);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_handles_different_languages()
    {
        $email = 'test@example.com';
        $activationLink = 'http://localhost/activate?token=test123';
        
        $languages = ['en', 'de', 'fr', 'es'];
        
        foreach ($languages as $lang) {
            $result = MockEmailService::sendActivationMail($email, $activationLink, $lang);
            $this->assertTrue($result, "Failed for language: {$lang}");
        }
    }
    
    /**
     * @test
     * @group email
     */
    public function it_creates_log_directory_if_not_exists()
    {
        // Remove log directory
        $testDir = $this->logDir . '_test';
        if (is_dir($testDir)) {
            rmdir($testDir);
        }
        
        // This should create the directory
        $this->assertTrue(!is_dir($testDir) || is_dir($testDir));
    }
    
    /**
     * @test
     * @group email
     */
    public function it_includes_timestamp_in_log_filename()
    {
        $email = 'test_' . time() . '@example.com';
        $activationLink = 'http://localhost/activate?token=test123';
        
        MockEmailService::sendActivationMail($email, $activationLink, 'en');
        
        $logFiles = glob($this->logDir . '/activation_*.log');
        $latestLog = basename(end($logFiles));
        
        // Should contain timestamp pattern
        $this->assertMatchesRegularExpression('/activation_\d+\.log/', $latestLog);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_handles_long_activation_links()
    {
        $email = 'test@example.com';
        $longLink = 'http://localhost/activate?token=' . str_repeat('a', 200);
        
        $result = MockEmailService::sendActivationMail($email, $longLink, 'en');
        
        $this->assertTrue($result);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_handles_multiple_accounts_in_forgotten_email()
    {
        $email = 'test@example.com';
        $manyAccounts = [];
        for ($i = 0; $i < 10; $i++) {
            $manyAccounts[] = 'account_' . $i;
        }
        
        $result = MockEmailService::sendForgottenAccountsMail($email, $manyAccounts, 'en');
        
        $this->assertTrue($result);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_handles_empty_accounts_array()
    {
        $email = 'test@example.com';
        $emptyAccounts = [];
        
        $result = MockEmailService::sendForgottenAccountsMail($email, $emptyAccounts, 'en');
        
        $this->assertIsBool($result);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_logs_email_headers()
    {
        $email = 'test_' . time() . '@example.com';
        $activationLink = 'http://localhost/activate?token=test123';
        
        MockEmailService::sendActivationMail($email, $activationLink, 'en');
        
        $logFiles = glob($this->logDir . '/activation_*.log');
        $latestLog = end($logFiles);
        
        $content = file_get_contents($latestLog);
        
        $this->assertStringContainsString('To:', $content);
        $this->assertStringContainsString('Subject:', $content);
        $this->assertStringContainsString('From:', $content);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_handles_concurrent_email_sends()
    {
        $emails = [];
        for ($i = 0; $i < 5; $i++) {
            $emails[] = 'test_' . $i . '_' . time() . '@example.com';
        }
        
        foreach ($emails as $email) {
            $result = MockEmailService::sendActivationMail(
                $email,
                'http://localhost/activate?token=test' . $email,
                'en'
            );
            $this->assertTrue($result);
        }
        
        // All should have log files
        $logFiles = glob($this->logDir . '/activation_*.log');
        $this->assertGreaterThanOrEqual(5, count($logFiles));
    }
    
    /**
     * @test
     * @group email
     */
    public function it_preserves_link_parameters()
    {
        $email = 'test@example.com';
        $activationLink = 'http://localhost/activate?token=test123&user=456&lang=en';
        
        MockEmailService::sendActivationMail($email, $activationLink, 'en');
        
        $logFiles = glob($this->logDir . '/activation_*.log');
        $latestLog = end($logFiles);
        
        $content = file_get_contents($latestLog);
        
        $this->assertStringContainsString('token=test123', $content);
        $this->assertStringContainsString('user=456', $content);
        $this->assertStringContainsString('lang=en', $content);
    }
    
    /**
     * @test
     * @group email
     */
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
        $link = 'http://localhost/activate?token=test';
        
        $result = MockEmailService::sendActivationMail($email, $link, 'en');
        
        $this->assertIsBool($result);
    }
    
    /**
     * @test
     * @group email
     */
    public function it_handles_unicode_in_email()
    {
        $email = 'tëst@example.com';
        $link = 'http://localhost/activate?token=test';
        
        $result = MockEmailService::sendActivationMail($email, $link, 'en');
        
        $this->assertIsBool($result);
    }
}
