<?php

namespace Subscription\Service;

class TokenDecryptionService
{
    /**
     * @var string Hex key (64 hex characters = 32 bytes for AES-256-CBC)
     */
    private $centralHexKey;

    /**
     * TokenDecryptionService constructor.
     *
     * @param string|null $centralHexKey Central hex key or fallback
     */
    public function __construct(?string $centralHexKey = null)
    {
        // Default 64-character (32-byte binary) hex key if not set in environment/config
        $this->centralHexKey = $centralHexKey ?: (
            getenv('CENTRAL_HEX_KEY')
            ?: getenv('CENTRAL_KEY_HEX')
            ?: '4f8b2c6e9a1d3f5b7c8a9e0d1f2c3b4a5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0b'
        );
    }

    /**
     * Decrypt an incoming base64 token using symmetric AES-256-CBC with central hex key.
     * Steps:
     * 1. Convert the central hex back to binary bytes (hex2bin)
     * 2. Decode the base64 token sent
     * 3. Separate the 16 byte IV and ciphertext
     * 4. Assign the central key and extract IV
     * 5. Decrypt and return the original string / decoded payload
     *
     * @param string $token Encrypted base64 token
     * @return array Decoded payload array (containing userId/user_id, wardId/ward_id)
     * @throws \Exception
     */
    public function decryptToken(string $token): array
    {
        if (empty(trim($token))) {
            throw new \Exception("Token is required.");
        }

        // 1. Convert central hex back to binary bytes
        $binaryKey = @hex2bin($this->centralHexKey);
        if ($binaryKey === false) {
            throw new \Exception("Invalid central hex key configuration.");
        }

        // Handle URL-safe Base64 variants and query string space replacements
        $rawToken = str_replace(' ', '+', trim($token));
        $base64Token = str_replace(['-', '_'], ['+', '/'], $rawToken);
        // Add padding if missing
        $mod4 = strlen($base64Token) % 4;
        if ($mod4) {
            $base64Token .= substr('====', $mod4);
        }

        // 2. Decode the base64 token sent (try strict first, fallback to non-strict)
        $decodedBinary = base64_decode($base64Token, true);
        if ($decodedBinary === false) {
            $decodedBinary = base64_decode($base64Token, false);
        }

        if ($decodedBinary === false || strlen($decodedBinary) < 17) {
            throw new \Exception("Invalid base64 token structure or length.");
        }

        // 3. Separate the 16 byte IV and ciphertext
        $iv = substr($decodedBinary, 0, 16);
        $ciphertext = substr($decodedBinary, 16);

        if (strlen($iv) !== 16) {
            throw new \Exception("Invalid IV extraction: IV must be exactly 16 bytes.");
        }

        // 4 & 5. Assign central key, extract IV, decrypt and return original string
        $decryptedString = openssl_decrypt(
            $ciphertext,
            'AES-256-CBC',
            $binaryKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decryptedString === false) {
            throw new \Exception("Decryption failed. Token may be corrupted or key mismatched.");
        }

        // Parse payload (JSON)
        $payload = json_decode($decryptedString, true);
        if (! is_array($payload)) {
            // Fallback if plain string format like "userId:1,wardId:2"
            $payload = [];
            parse_str(str_replace(',', '&', $decryptedString), $payload);
            if (empty($payload)) {
                $payload = ['raw' => $decryptedString];
            }
        }

        // Normalize keys (userId / user_id, wardId / ward_id)
        if (! isset($payload['userId']) && isset($payload['user_id'])) {
            $payload['userId'] = $payload['user_id'];
        }
        if (! isset($payload['wardId']) && isset($payload['ward_id'])) {
            $payload['wardId'] = $payload['ward_id'];
        }

        return [
            'original_string' => $decryptedString,
            'payload'         => $payload,
            'user_id'         => $payload['userId'] ?? null,
            'ward_id'         => $payload['wardId'] ?? null,
        ];
    }

    /**
     * Helper to encrypt payload into symmetric token (for testing or token generation).
     *
     * @param array|string $data Data to encrypt (array or string)
     * @return string Encrypted base64 token
     * @throws \Exception
     */
    public function encryptToken($data): string
    {
        $binaryKey = @hex2bin($this->centralHexKey);
        if ($binaryKey === false) {
            throw new \Exception("Invalid central hex key configuration.");
        }

        $plainText = is_array($data) ? json_encode($data) : (string) $data;
        $iv = openssl_random_pseudo_bytes(16);

        $ciphertext = openssl_encrypt(
            $plainText,
            'AES-256-CBC',
            $binaryKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($ciphertext === false) {
            throw new \Exception("Encryption failed.");
        }

        $combinedBinary = $iv . $ciphertext;
        $base64 = base64_encode($combinedBinary);

        // Convert to URL safe base64 format for route parameters
        return str_replace(['+', '/', '='], ['-', '_', ''], $base64);
    }

    public function getCentralHexKey(): string
    {
        return $this->centralHexKey;
    }
}
