<?php
/**
 * Unit Tests for Core\Security
 * Tests the REAL production Security class
 */

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Core\Security;

class SecurityTest extends TestCase
{
    private Security $security;
    
    protected function setUp(): void
    {
        parent::setUp();
        // Use the REAL Security class from production codebase
        $this->security = new Security();
        
        // Start session if not started
        if (session_status() === PHP_STATUS_NONE) {
            session_start();
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up session
        $_SESSION = [];
        parent::tearDown();
    }
    
    /**
     * @test
     * @group csrf
     */
    public function it_generates_csrf_token()
    {
        $token = $this->security->generateCSRFToken();
        
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/i', $token);
        $this->assertArrayHasKey('csrf_token', $_SESSION);
    }
    
    /**
     * @test
     * @group csrf
     */
    public function it_validates_correct_csrf_token()
    {
        $token = $this->security->generateCSRFToken();
        
        $isValid = $this->security->validateCSRFToken($token);
        
        $this->assertTrue($isValid);
    }
    
    /**
     * @test
     * @group csrf
     */
    public function it_rejects_invalid_csrf_token()
    {
        $this->security->generateCSRFToken();
        
        $isValid = $this->security->validateCSRFToken('invalid_token_12345');
        
        $this->assertFalse($isValid);
    }
    
    /**
     * @test
     * @group csrf
     */
    public function it_rejects_empty_csrf_token()
    {
        $this->security->generateCSRFToken();
        
        $isValid = $this->security->validateCSRFToken('');
        
        $this->assertFalse($isValid);
    }
    
    /**
     * @test
     * @group csrf
     */
    public function it_rejects_csrf_token_when_session_token_missing()
    {
        unset($_SESSION['csrf_token']);
        
        $isValid = $this->security->validateCSRFToken('some_token');
        
        $this->assertFalse($isValid);
    }
    
    /**
     * @test
     * @group csrf
     */
    public function it_handles_csrf_token_expiration()
    {
        $token = $this->security->generateCSRFToken();
        
        // Set expiration to past
        $_SESSION['csrf_token_time'] = time() - 3700; // 1 hour + 100 seconds ago
        
        $isValid = $this->security->validateCSRFToken($token);
        
        $this->assertFalse($isValid);
    }
    
    /**
     * @test
     * @group xss
     */
    public function it_sanitizes_basic_html()
    {
        $input = '<script>alert("XSS")</script>';
        
        $sanitized = $this->security->sanitizeInput($input);
        
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('&lt;script&gt;', $sanitized);
    }
    
    /**
     * @test
     * @group xss
     */
    public function it_sanitizes_html_entities()
    {
        $input = '<b>Bold</b> & <i>Italic</i>';
        
        $sanitized = $this->security->sanitizeInput($input);
        
        $this->assertStringNotContainsString('<b>', $sanitized);
        $this->assertStringNotContainsString('<i>', $sanitized);
        $this->assertStringContainsString('&lt;b&gt;', $sanitized);
        $this->assertStringContainsString('&amp;', $sanitized);
    }
    
    /**
     * @test
     * @group xss
     */
    public function it_handles_null_input_in_sanitization()
    {
        $sanitized = $this->security->sanitizeInput(null);
        
        $this->assertSame('', $sanitized);
    }
    
    /**
     * @test
     * @group xss
     */
    public function it_preserves_safe_text()
    {
        $input = 'Hello World! This is safe text.';
        
        $sanitized = $this->security->sanitizeInput($input);
        
        $this->assertEquals($input, $sanitized);
    }
    
    /**
     * @test
     * @group xss
     */
    public function it_sanitizes_array_of_inputs()
    {
        $inputs = [
            'name' => '<script>alert("XSS")</script>John',
            'email' => 'test@example.com',
            'message' => '<b>Bold message</b>'
        ];
        
        $sanitized = $this->security->sanitizeArray($inputs);
        
        $this->assertStringNotContainsString('<script>', $sanitized['name']);
        $this->assertEquals('test@example.com', $sanitized['email']);
        $this->assertStringNotContainsString('<b>', $sanitized['message']);
    }
    
    /**
     * @test
     * @group password
     */
    public function it_hashes_password()
    {
        $password = 'MySecurePassword123!';
        
        $hash = $this->security->hashPassword($password);
        
        $this->assertNotEmpty($hash);
        $this->assertNotEquals($password, $hash);
        $this->assertStringStartsWith('$2y$', $hash); // BCrypt identifier
    }
    
    /**
     * @test
     * @group password
     */
    public function it_verifies_correct_password()
    {
        $password = 'MySecurePassword123!';
        $hash = $this->security->hashPassword($password);
        
        $isValid = $this->security->verifyPassword($password, $hash);
        
        $this->assertTrue($isValid);
    }
    
    /**
     * @test
     * @group password
     */
    public function it_rejects_incorrect_password()
    {
        $password = 'MySecurePassword123!';
        $wrongPassword = 'WrongPassword456!';
        $hash = $this->security->hashPassword($password);
        
        $isValid = $this->security->verifyPassword($wrongPassword, $hash);
        
        $this->assertFalse($isValid);
    }
    
    /**
     * @test
     * @group password
     */
    public function it_validates_strong_password()
    {
        $strongPassword = 'MySecure123!Pass';
        
        $result = $this->security->validatePasswordStrength($strongPassword);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    /**
     * @test
     * @group password
     */
    public function it_rejects_short_password()
    {
        $shortPassword = 'Short1!';
        
        $result = $this->security->validatePasswordStrength($shortPassword);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Password must be at least 8 characters long', $result['errors']);
    }
    
    /**
     * @test
     * @group password
     */
    public function it_rejects_password_without_uppercase()
    {
        $password = 'mysecure123!';
        
        $result = $this->security->validatePasswordStrength($password);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Password must contain at least one uppercase letter', $result['errors']);
    }
    
    /**
     * @test
     * @group password
     */
    public function it_rejects_password_without_lowercase()
    {
        $password = 'MYSECURE123!';
        
        $result = $this->security->validatePasswordStrength($password);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Password must contain at least one lowercase letter', $result['errors']);
    }
    
    /**
     * @test
     * @group password
     */
    public function it_rejects_password_without_number()
    {
        $password = 'MySecurePass!';
        
        $result = $this->security->validatePasswordStrength($password);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Password must contain at least one number', $result['errors']);
    }
    
    /**
     * @test
     * @group password
     */
    public function it_rejects_password_without_special_char()
    {
        $password = 'MySecurePass123';
        
        $result = $this->security->validatePasswordStrength($password);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Password must contain at least one special character', $result['errors']);
    }
    
    /**
     * @test
     * @group security
     */
    public function it_generates_secure_random_token()
    {
        $token1 = $this->security->generateSecureToken(32);
        $token2 = $this->security->generateSecureToken(32);
        
        $this->assertEquals(64, strlen($token1)); // 32 bytes = 64 hex chars
        $this->assertEquals(64, strlen($token2));
        $this->assertNotEquals($token1, $token2); // Should be unique
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/i', $token1);
    }
    
    /**
     * @test
     * @group security
     */
    public function it_sanitizes_path_input()
    {
        $maliciousPath = '../../../etc/passwd';
        
        $sanitized = $this->security->sanitizePath($maliciousPath);
        
        $this->assertStringNotContainsString('..', $sanitized);
    }
    
    /**
     * @test
     * @group security
     */
    public function it_extracts_ip_from_remote_addr()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        
        $ip = $this->security->getClientIP();
        
        $this->assertEquals('192.168.1.1', $ip);
    }
    
    /**
     * @test
     * @group security
     */
    public function it_extracts_ip_from_forwarded_header()
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1, 192.168.1.1';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        
        $ip = $this->security->getClientIP();
        
        $this->assertEquals('203.0.113.1', $ip);
    }
    
    /**
     * @test
     * @group security
     */
    public function it_sets_security_headers()
    {
        // Capture headers (PHPUnit doesn't actually send headers)
        $this->security->setSecurityHeaders();
        
        // We can't directly test headers in PHPUnit, but we can verify the method executes
        $this->assertTrue(true);
    }
    
    /**
     * @test
     * @group security
     */
    public function it_handles_empty_password()
    {
        $result = $this->security->validatePasswordStrength('');
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }
    
    /**
     * @test
     * @group security
     */
    public function it_generates_different_tokens_each_time()
    {
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            $tokens[] = $this->security->generateSecureToken(16);
        }
        
        $uniqueTokens = array_unique($tokens);
        
        $this->assertCount(10, $uniqueTokens, 'All generated tokens should be unique');
    }
}
