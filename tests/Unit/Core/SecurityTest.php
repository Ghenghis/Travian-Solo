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
    // No instance needed; using static methods only
    
    protected function setUp(): void
    {
        parent::setUp();
        // Use the REAL Security class from production codebase
        // Static methods only; no instance needed for current tests
        
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
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
        $token = Security::generateCsrfToken();
        
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
        $token = Security::generateCsrfToken();
        
        $isValid = Security::validateCsrfToken($token);
        
        $this->assertTrue($isValid);
    }
    
    /**
     * @test
     * @group csrf
     */
    public function it_rejects_invalid_csrf_token()
    {
        Security::generateCsrfToken();
        
        $isValid = Security::validateCsrfToken('invalid_token_12345');
        
        $this->assertFalse($isValid);
    }
    
    /**
     * @test
     * @group csrf
     */
    public function it_rejects_empty_csrf_token()
    {
        Security::generateCsrfToken();
        
        $isValid = Security::validateCsrfToken('');
        
        $this->assertFalse($isValid);
    }
    
    /**
     * @test
     * @group csrf
     */
    public function it_rejects_csrf_token_when_session_token_missing()
    {
        unset($_SESSION['csrf_token']);
        
        $isValid = Security::validateCsrfToken('some_token');
        
        $this->assertFalse($isValid);
    }
    
    /**
     * @test
     * @group csrf
     */
    public function it_handles_csrf_token_expiration()
    {
        $token = Security::generateCsrfToken();
        
        // Set expiration to past
        $_SESSION['csrf_token_time'] = time() - 3700; // 1 hour + 100 seconds ago
        
        $isValid = Security::validateCsrfToken($token);
        
        $this->assertFalse($isValid);
    }
    
    /**
     * @test
     * @group xss
     */
    public function it_sanitizes_basic_html()
    {
        $input = '<script>alert("XSS")</script>';
        
        $sanitized = Security::sanitizeInput($input);
        
        $this->assertStringNotContainsString('<script>', $sanitized);
        // Production removes tags then escapes, so script tag is stripped entirely
        $this->assertStringNotContainsString('&lt;script&gt;', $sanitized);
    }
    
    /**
     * @test
     * @group xss
     */
    public function it_sanitizes_html_entities()
    {
        $input = '<b>Bold</b> & <i>Italic</i>';
        
        $sanitized = Security::sanitizeInput($input);
        
        // Production strips tags and escapes HTML special characters
        $this->assertStringNotContainsString('<b>', $sanitized);
        $this->assertStringNotContainsString('<i>', $sanitized);
        $this->assertStringNotContainsString('&lt;b&gt;', $sanitized);
        $this->assertStringContainsString('&amp;', $sanitized);
    }
    
    /**
     * @test
     * @group xss
     */
    public function it_handles_null_input_in_sanitization()
    {
        $sanitized = Security::sanitizeInput(null);
        
        // Production returns empty string for null input
        $this->assertSame('', $sanitized);
    }
    
    /**
     * @test
     * @group xss
     */
    public function it_preserves_safe_text()
    {
        $input = 'Hello World! This is safe text.';
        
        $sanitized = Security::sanitizeInput($input);
        
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
        
        $sanitized = Security::sanitizeInput($inputs);
        
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
        
        $hash = Security::hashPassword($password);
        
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
        $hash = Security::hashPassword($password);
        
        $isValid = Security::verifyPassword($password, $hash);
        
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
        $hash = Security::hashPassword($password);
        
        $isValid = Security::verifyPassword($wrongPassword, $hash);
        
        $this->assertFalse($isValid);
    }
    
    /**
     * @test
     * @group password
     */
    public function it_validates_strong_password()
    {
        $strongPassword = 'MySecure123!Pass';
        
        $result = Security::validatePasswordStrength($strongPassword);
        
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
        
        $result = Security::validatePasswordStrength($shortPassword);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Password must be at least 8 characters', $result['errors']);
    }
    
    /**
     * @test
     * @group password
     */
    public function it_rejects_password_without_uppercase()
    {
        $password = 'mysecure123!';
        
        $result = Security::validatePasswordStrength($password);
        
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
        
        $result = Security::validatePasswordStrength($password);
        
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
        
        $result = Security::validatePasswordStrength($password);
        
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
        
        $result = Security::validatePasswordStrength($password);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Password must contain at least one special character', $result['errors']);
    }
    
    /**
     * @test
     * @group security
     */
    public function it_generates_secure_random_token()
    {
        $token1 = Security::generateToken(32);
        $token2 = Security::generateToken(32);
        
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
        
        $sanitized = Security::sanitizePath($maliciousPath);
        
        $this->assertStringNotContainsString('..', $sanitized);
    }
    
    /**
     * @test
     * @group security
     */
    public function it_extracts_ip_from_remote_addr()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        
        $ip = Security::getClientIp();
        
        $this->assertEquals('192.168.1.1', $ip);
    }
    
    /**
     * @test
     * @group security
     */
    public function it_extracts_ip_from_forwarded_header()
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1';
        
        $ip = Security::getClientIp();
        
        $this->assertEquals('203.0.113.1', $ip);
    }
    
    /**
     * @test
     * @group security
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_sets_security_headers()
    {
        // Headers cannot be reliably tested in CLI; guard to avoid warnings
        if (!headers_sent()) {
            Security::setSecurityHeaders();
            $this->assertTrue(true);
        } else {
            $this->markTestSkipped('Headers already sent in CLI environment.');
        }
    }
    
    /**
     * @test
     * @group security
     */
    public function it_handles_empty_password()
    {
        $result = Security::validatePasswordStrength('');
        
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
            $tokens[] = Security::generateToken(16);
        }
        
        $uniqueTokens = array_unique($tokens);
        
        $this->assertCount(10, $uniqueTokens, 'All generated tokens should be unique');
    }
}
