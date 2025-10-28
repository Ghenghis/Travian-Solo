<?php
namespace Middleware;

use Redis;

/**
 * RateLimiter - API Rate Limiting with Redis
 */
class RateLimiter
{
    private $redis;
    private $enabled;
    
    public function __construct()
    {
        $this->enabled = extension_loaded('redis');
        
        if ($this->enabled) {
            try {
                $this->redis = new Redis();
                $this->redis->connect(
                    getenv('REDIS_HOST') ?: 'redis',
                    (int)(getenv('REDIS_PORT') ?: 6379)
                );
                
                $password = getenv('REDIS_PASSWORD');
                if (!empty($password)) {
                    $this->redis->auth($password);
                }
            } catch (\Exception $e) {
                error_log("RateLimiter: Redis connection failed - " . $e->getMessage());
                $this->enabled = false;
            }
        }
    }
    
    /**
     * Check if rate limit exceeded
     * 
     * @param string $identifier Unique identifier (IP, user ID, etc.)
     * @param int $maxRequests Maximum requests allowed
     * @param int $timeWindow Time window in seconds
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => int]
     */
    public function check($identifier, $maxRequests = 60, $timeWindow = 60)
    {
        if (!$this->enabled) {
            // Fallback to session-based rate limiting
            return $this->checkWithSession($identifier, $maxRequests, $timeWindow);
        }
        
        $key = "rate_limit:{$identifier}";
        $now = time();
        
        try {
            // Get current count
            $current = $this->redis->get($key);
            
            if ($current === false) {
                // First request in this window
                $this->redis->setex($key, $timeWindow, 1);
                
                return [
                    'allowed' => true,
                    'remaining' => $maxRequests - 1,
                    'reset_at' => $now + $timeWindow
                ];
            }
            
            $count = (int) $current;
            
            if ($count >= $maxRequests) {
                // Rate limit exceeded
                $ttl = $this->redis->ttl($key);
                
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'reset_at' => $now + $ttl
                ];
            }
            
            // Increment counter
            $this->redis->incr($key);
            $ttl = $this->redis->ttl($key);
            
            return [
                'allowed' => true,
                'remaining' => $maxRequests - ($count + 1),
                'reset_at' => $now + $ttl
            ];
            
        } catch (\Exception $e) {
            error_log("RateLimiter: Redis error - " . $e->getMessage());
            // Allow request on error
            return [
                'allowed' => true,
                'remaining' => $maxRequests,
                'reset_at' => $now + $timeWindow
            ];
        }
    }
    
    /**
     * Fallback to session-based rate limiting
     */
    private function checkWithSession($identifier, $maxRequests, $timeWindow)
    {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $key = "rate_limit_{$identifier}";
        $now = time();
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 1,
                'start_time' => $now
            ];
            
            return [
                'allowed' => true,
                'remaining' => $maxRequests - 1,
                'reset_at' => $now + $timeWindow
            ];
        }
        
        $data = $_SESSION[$key];
        $elapsed = $now - $data['start_time'];
        
        // Reset if time window passed
        if ($elapsed > $timeWindow) {
            $_SESSION[$key] = [
                'count' => 1,
                'start_time' => $now
            ];
            
            return [
                'allowed' => true,
                'remaining' => $maxRequests - 1,
                'reset_at' => $now + $timeWindow
            ];
        }
        
        $count = $data['count'];
        
        if ($count >= $maxRequests) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_at' => $data['start_time'] + $timeWindow
            ];
        }
        
        // Increment counter
        $_SESSION[$key]['count']++;
        
        return [
            'allowed' => true,
            'remaining' => $maxRequests - ($count + 1),
            'reset_at' => $data['start_time'] + $timeWindow
        ];
    }
    
    /**
     * Reset rate limit for identifier
     */
    public function reset($identifier)
    {
        if ($this->enabled) {
            $key = "rate_limit:{$identifier}";
            $this->redis->del($key);
        } else {
            $key = "rate_limit_{$identifier}";
            if (isset($_SESSION[$key])) {
                unset($_SESSION[$key]);
            }
        }
    }
    
    /**
     * Get rate limit info without incrementing
     */
    public function getInfo($identifier, $maxRequests = 60, $timeWindow = 60)
    {
        if (!$this->enabled) {
            if (!isset($_SESSION)) {
                session_start();
            }
            
            $key = "rate_limit_{$identifier}";
            if (!isset($_SESSION[$key])) {
                return [
                    'requests' => 0,
                    'remaining' => $maxRequests,
                    'reset_at' => time() + $timeWindow
                ];
            }
            
            $data = $_SESSION[$key];
            $now = time();
            $elapsed = $now - $data['start_time'];
            
            if ($elapsed > $timeWindow) {
                return [
                    'requests' => 0,
                    'remaining' => $maxRequests,
                    'reset_at' => $now + $timeWindow
                ];
            }
            
            return [
                'requests' => $data['count'],
                'remaining' => max(0, $maxRequests - $data['count']),
                'reset_at' => $data['start_time'] + $timeWindow
            ];
        }
        
        $key = "rate_limit:{$identifier}";
        $current = $this->redis->get($key);
        $now = time();
        
        if ($current === false) {
            return [
                'requests' => 0,
                'remaining' => $maxRequests,
                'reset_at' => $now + $timeWindow
            ];
        }
        
        $count = (int) $current;
        $ttl = $this->redis->ttl($key);
        
        return [
            'requests' => $count,
            'remaining' => max(0, $maxRequests - $count),
            'reset_at' => $now + $ttl
        ];
    }
    
    /**
     * Set rate limit headers
     */
    public function setHeaders($result)
    {
        header('X-RateLimit-Limit: ' . ($result['remaining'] + ($result['allowed'] ? 0 : 1)));
        header('X-RateLimit-Remaining: ' . $result['remaining']);
        header('X-RateLimit-Reset: ' . $result['reset_at']);
        
        if (!$result['allowed']) {
            header('Retry-After: ' . ($result['reset_at'] - time()));
        }
    }
}
