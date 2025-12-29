<?php

namespace Redium\Auth;

use DateTimeImmutable;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Validation\Validator;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;

class AuthService
{
    private static Validator $validator;
    private static Parser $parser;
    private string $issuer;
    private string $audience;
    private InMemory $signingKey;

    public function __construct()
    {
        self::$validator = new Validator();
        self::$parser = new Parser(new JoseEncoder());
        
        $this->issuer = $_ENV['JWT_ISSUER'] ?? $_ENV['API_SERVICE'] ?? 'Redium';
        $this->audience = $_ENV['JWT_AUDIENCE'] ?? $_ENV['SERVICE_HOST'] ?? 'localhost';
        
        // Use environment secret or generate random key
        $secret = $_ENV['JWT_SECRET'] ?? random_bytes(32);
        $this->signingKey = InMemory::plainText($secret);
    }

    /**
     * Generate JWT authentication token
     * 
     * @param array $data User data to embed in token (e.g., identifier, role, permissions)
     * @param int $expirationHours Token expiration in hours (default: 8)
     * @return string JWT token
     */
    public function generateAuthToken(array $data, int $expirationHours = 8): string
    {
        $tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));
        $algorithm = new Sha256();

        $now = new DateTimeImmutable();
        $expiresAt = $now->modify("+{$expirationHours} hour");

        $token = $tokenBuilder
            ->issuedBy($this->issuer)
            ->permittedFor($this->audience)
            ->issuedAt($now)
            ->expiresAt($expiresAt)
            ->withClaim('user', $data)
            ->getToken($algorithm, $this->signingKey);

        return $token->toString();
    }

    /**
     * Extract user information from token
     * 
     * @param string $token JWT token string
     * @return array|null User data or null if invalid
     */
    public function getTokenInformation(string $token): ?array
    {
        try {
            $parsedToken = self::$parser->parse($token);

            if ($parsedToken instanceof Plain) {
                return $parsedToken->claims()->get('user');
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Validate JWT token and return user data
     * 
     * @param string $token JWT token string
     * @return array|false User data if valid, false otherwise
     */
    public function validateToken(string $token): array|false
    {
        try {
            $parsedToken = self::$parser->parse($token);

            // Check if token is issued by our service
            $valid = self::$validator->validate($parsedToken, new IssuedBy($this->issuer));

            if ($valid) {
                // Check if token is expired
                if ($parsedToken instanceof Plain) {
                    $expiresAt = $parsedToken->claims()->get('exp');
                    if ($expiresAt && $expiresAt->getTimestamp() < time()) {
                        return false;
                    }
                }

                return $this->getTokenInformation($token);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if user has specific permission
     * 
     * @param array $userPermissions Array of user's permission names
     * @param string $requiredPermission Required permission name
     * @return bool True if user has permission
     */
    public function hasPermission(array $userPermissions, string $requiredPermission): bool
    {
        // "all" permission grants access to everything
        if (in_array("all", $userPermissions)) {
            return true;
        }

        return in_array($requiredPermission, $userPermissions);
    }
}
