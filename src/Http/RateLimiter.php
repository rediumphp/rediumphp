<?php

namespace Redium\Http;

class RateLimiter
{
    private string $cacheDir;
    private int $maxAttempts;
    private int $decayMinutes;

    public function __construct(int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
        $this->cacheDir = sys_get_temp_dir() . '/redium_ratelimit';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Check if request is allowed
     */
    public function attempt(string $key): bool
    {
        $attempts = $this->getAttempts($key);
        
        if ($attempts >= $this->maxAttempts) {
            return false;
        }

        $this->incrementAttempts($key);
        return true;
    }

    /**
     * Get number of attempts
     */
    public function getAttempts(string $key): int
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return 0;
        }

        $data = unserialize(file_get_contents($file));

        // Check if expired
        if ($data['expires_at'] < time()) {
            $this->clear($key);
            return 0;
        }

        return $data['attempts'];
    }

    /**
     * Increment attempts
     */
    private function incrementAttempts(string $key): void
    {
        $attempts = $this->getAttempts($key) + 1;
        $expiresAt = time() + ($this->decayMinutes * 60);

        $data = [
            'attempts' => $attempts,
            'expires_at' => $expiresAt
        ];

        file_put_contents($this->getFilePath($key), serialize($data));
    }

    /**
     * Clear attempts for a key
     */
    public function clear(string $key): void
    {
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Get remaining attempts
     */
    public function remaining(string $key): int
    {
        return max(0, $this->maxAttempts - $this->getAttempts($key));
    }

    /**
     * Get time until reset in seconds
     */
    public function availableIn(string $key): int
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return 0;
        }

        $data = unserialize(file_get_contents($file));
        return max(0, $data['expires_at'] - time());
    }

    /**
     * Check if too many attempts
     */
    public function tooManyAttempts(string $key): bool
    {
        return $this->getAttempts($key) >= $this->maxAttempts;
    }

    /**
     * Get file path for key
     */
    private function getFilePath(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.limit';
    }

    /**
     * Create rate limiter for IP address
     */
    public static function forIp(string $route, int $maxAttempts = 60, int $decayMinutes = 1): self
    {
        $ip = getIpAddress();
        $key = "rate_limit:{$route}:{$ip}";
        
        $limiter = new self($maxAttempts, $decayMinutes);
        
        if ($limiter->tooManyAttempts($key)) {
            throwError(429, "Too Many Requests", "Rate limit exceeded. Try again in " . $limiter->availableIn($key) . " seconds");
        }

        $limiter->attempt($key);
        return $limiter;
    }
}
