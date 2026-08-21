<?php declare(strict_types=1);

namespace Authentication\Service;

use Authentication\Service\GoogleAuthService;
use Google\Client as GoogleClient;
use RuntimeException;
use Throwable;

final class GoogleOAuthService
{
    private array $googleConfig;
    private array $securityConfig;

    public function __construct(array $config)
    {
        $this->googleConfig =
            $config['google_oauth'] ?? [];

        $this->securityConfig =
            $config['security'] ?? [];

        $this->assertConfiguration();
    }

    /**
     * Exchange the authorization code for Google's tokens.
     */
    public function exchangeAuthorizationCode(
        string $code,
        string $redirectUri,
        string $codeVerifier
    ): array {
        $this->validateAuthorizationCode($code);

        $this->validateRedirectUri($redirectUri);

        $this->validateCodeVerifier($codeVerifier);

        $client = new GoogleClient();

        $client->setClientId(
            $this->googleConfig['client_id']
        );

        $client->setClientSecret(
            $this->googleConfig['client_secret']
        );

        $client->setRedirectUri(
            $redirectUri
        );

        /*
         * google/apiclient will POST the authorization code
         * to Google's token endpoint.
         *
         * We explicitly provide the PKCE verifier.
         */
        try {
            $token = $client->fetchAccessTokenWithAuthCode(
                $code,
                $codeVerifier
            );
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Google authorization-code exchange failed.',
                0,
                $e
            );
        }

        if (!is_array($token)) {
            throw new RuntimeException(
                'Google returned an invalid token response.'
            );
        }

        if (
            isset($token['error'])
        ) {
            throw new RuntimeException(
                'Google rejected the authorization code.'
            );
        }

        if (
            empty($token['access_token'])
        ) {
            throw new RuntimeException(
                'Google did not return an access token.'
            );
        }

        if (
            empty($token['id_token'])
        ) {
            throw new RuntimeException(
                'Google did not return an ID token.'
            );
        }

        return $token;
    }

    /**
     * Cryptographically verifies the Google ID token.
     *
     * Validation performed:
     *
     * - Google signature
     * - supported signing algorithm
     * - issuer
     * - audience
     * - expiration
     * - issued-at
     * - subject
     * - nonce
     * - azp where applicable
     * - hosted domain where configured
     * - verified email
     */
    public function validateIdToken(
        string $idToken,
        string $expectedNonce
    ): array {
        if ($idToken === '') {
            throw new RuntimeException(
                'Google ID token is missing.'
            );
        }

        $this->validateNonce(
            $expectedNonce
        );

        /*
         * Google Client verifies:
         *
         * - JWT signature
         * - Google signing certificates
         * - expiration
         * - audience
         *
         * Google's client refreshes the certificate material
         * when required.
         */
        $client = new GoogleClient();

        $client->setClientId(
            $this->googleConfig['client_id']
        );

        try {
            $payload =
                $client->verifyIdToken(
                    $idToken
                );
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Google ID token signature or claim validation failed.',
                0,
                $e
            );
        }

        if (
            !is_array($payload)
        ) {
            throw new RuntimeException(
                'Google ID token payload is invalid.'
            );
        }

        /*
         * ---------------------------------------------------------
         * ISSUER
         * ---------------------------------------------------------
         */
        $issuer =
            isset($payload['iss'])
                ? (string) $payload['iss']
                : '';

        if (
            !in_array(
                $issuer,
                $this->googleConfig['allowed_issuers'],
                true
            )
        ) {
            throw new RuntimeException(
                'Invalid Google token issuer.'
            );
        }

        /*
         * ---------------------------------------------------------
         * AUDIENCE
         * ---------------------------------------------------------
         */
        $audience =
            $payload['aud'] ?? null;

        $clientId =
            (string) $this->googleConfig['client_id'];

        if (
            is_array($audience)
        ) {
            if (
                !in_array(
                    $clientId,
                    $audience,
                    true
                )
            ) {
                throw new RuntimeException(
                    'Google token audience does not match this application.'
                );
            }
        } else {
            if (
                !is_string($audience) ||
                !hash_equals(
                    $clientId,
                    $audience
                )
            ) {
                throw new RuntimeException(
                    'Google token audience does not match this application.'
                );
            }
        }

        /*
         * ---------------------------------------------------------
         * AZP
         *
         * If an authorized party is present, ensure it is our
         * OAuth client.
         * ---------------------------------------------------------
         */
        if (
            isset($payload['azp']) &&
            !hash_equals(
                $clientId,
                (string) $payload['azp']
            )
        ) {
            throw new RuntimeException(
                'Invalid Google authorized party.'
            );
        }

        /*
         * ---------------------------------------------------------
         * EXP
         * ---------------------------------------------------------
         */
        $now =
            time();

        $clockSkew =
            (int) (
                $this->securityConfig['clock_skew']
                    ?? 60
            );

        $exp =
            filter_var(
                $payload['exp'] ?? null,
                FILTER_VALIDATE_INT
            );

        if (
            $exp === false ||
            $exp === null
        ) {
            throw new RuntimeException(
                'Google ID token does not contain a valid expiration.'
            );
        }

        if (
            $now >
            ($exp + $clockSkew)
        ) {
            throw new RuntimeException(
                'Google ID token has expired.'
            );
        }

        /*
         * ---------------------------------------------------------
         * IAT
         * ---------------------------------------------------------
         */
        $iat =
            filter_var(
                $payload['iat'] ?? null,
                FILTER_VALIDATE_INT
            );

        if (
            $iat === false ||
            $iat === null
        ) {
            throw new RuntimeException(
                'Google ID token does not contain a valid issued-at time.'
            );
        }

        /*
         * Prevent a token claiming to have been issued
         * significantly in the future.
         */
        if (
            $iat >
            ($now + $clockSkew)
        ) {
            throw new RuntimeException(
                'Google ID token was issued in the future.'
            );
        }

        /*
         * ---------------------------------------------------------
         * SUB
         * ---------------------------------------------------------
         */
        $sub =
            isset($payload['sub'])
                ? trim((string) $payload['sub'])
                : '';

        if ($sub === '') {
            throw new RuntimeException(
                'Google ID token does not contain a subject.'
            );
        }

        /*
         * Google defines sub as the stable identifier for
         * the Google account. Do NOT use email as the identity key.
         */
        if (strlen($sub) > 255) {
            throw new RuntimeException(
                'Google subject identifier is invalid.'
            );
        }

        /*
         * ---------------------------------------------------------
         * NONCE
         * ---------------------------------------------------------
         */
        $tokenNonce =
            isset($payload['nonce'])
                ? (string) $payload['nonce']
                : '';

        if (
            $tokenNonce === '' ||
            !hash_equals(
                $expectedNonce,
                $tokenNonce
            )
        ) {
            throw new RuntimeException(
                'Google ID token nonce validation failed.'
            );
        }

        /*
         * ---------------------------------------------------------
         * EMAIL
         * ---------------------------------------------------------
         */
        $email =
            isset($payload['email'])
                ? trim(strtolower(
                    (string) $payload['email']
                ))
                : '';

        if (
            $email === '' ||
            filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            throw new RuntimeException(
                'Google account does not contain a valid email address.'
            );
        }

        /*
         * ---------------------------------------------------------
         * EMAIL VERIFIED
         * ---------------------------------------------------------
         */
        if (
            (
                $this->securityConfig[
                    'require_verified_email'
                ] ?? true
            ) && (
                !isset($payload['email_verified']) ||
                $payload['email_verified'] !== true
            )
        ) {
            throw new RuntimeException(
                'Google email address has not been verified.'
            );
        }

        /*
         * ---------------------------------------------------------
         * HOSTED DOMAIN
         * ---------------------------------------------------------
         *
         * This is only enforced when configured.
         */
        $allowedHostedDomain =
            $this->googleConfig['hosted_domain']
                ?? null;

        if (
            $allowedHostedDomain !== null &&
            $allowedHostedDomain !== ''
        ) {
            $hd =
                isset($payload['hd'])
                    ? strtolower(
                        trim(
                            (string) $payload['hd']
                        )
                    )
                    : '';

            if (
                $hd === '' ||
                !hash_equals(
                    strtolower(
                        $allowedHostedDomain
                    ),
                    $hd
                )
            ) {
                throw new RuntimeException(
                    'Google account is not from an allowed organization.'
                );
            }
        }

        return [
            'sub' =>
                $sub,
            'email' =>
                $email,
            'email_verified' =>
                (bool) ($payload['email_verified'] ?? false),
            'name' =>
                trim(
                    (string) ($payload['name'] ?? '')
                ),
            'given_name' =>
                trim(
                    (string) ($payload['given_name'] ?? '')
                ),
            'family_name' =>
                trim(
                    (string) ($payload['family_name'] ?? '')
                ),
            'picture' =>
                trim(
                    (string) ($payload['picture'] ?? '')
                ),
            'locale' =>
                trim(
                    (string) ($payload['locale'] ?? '')
                ),
            'hd' =>
                isset($payload['hd'])
                    ? (string) $payload['hd']
                    : null,
        ];
    }

    private function assertConfiguration(): void
    {
        $required = [
            'client_id',
            'client_secret',
            'redirect_uri',
            'allowed_issuers',
        ];

        foreach ($required as $key) {
            if (
                empty(
                    $this->googleConfig[$key]
                )
            ) {
                throw new RuntimeException(
                    "Missing Google configuration: {$key}"
                );
            }
        }

        if (
            !is_array(
                $this->googleConfig['allowed_issuers']
            )
        ) {
            throw new RuntimeException(
                'Google allowed_issuers must be an array.'
            );
        }
    }

    private function validateAuthorizationCode(
        string $code
    ): void {
        $max =
            (int) ($this->securityConfig[
                'max_code_length'
            ] ?? 2048);

        if (
            $code === '' ||
            strlen($code) > $max
        ) {
            throw new RuntimeException(
                'Invalid Google authorization code.'
            );
        }
    }

    private function validateRedirectUri(
        string $redirectUri
    ): void {
        $max =
            (int) ($this->securityConfig[
                'max_redirect_uri_length'
            ] ?? 2048);

        if (
            $redirectUri === '' ||
            strlen($redirectUri) > $max
        ) {
            throw new RuntimeException(
                'Invalid redirect URI.'
            );
        }

        /*
         * Exact match. Never accept a client-selected arbitrary URI.
         */
        if (
            !hash_equals(
                (string) $this->googleConfig['redirect_uri'],
                $redirectUri
            )
        ) {
            throw new RuntimeException(
                'Redirect URI is not registered for this application.'
            );
        }
    }

    private function validateCodeVerifier(
        string $verifier
    ): void {
        $length =
            strlen($verifier);

        $max =
            (int) ($this->securityConfig[
                'max_verifier_length'
            ] ?? 128);

        /*
         * RFC 7636 verifier length is 43-128 characters.
         */
        if (
            $length < 43 ||
            $length > $max
        ) {
            throw new RuntimeException(
                'Invalid PKCE code verifier length.'
            );
        }

        /*
         * RFC 7636 unreserved characters:
         *
         * ALPHA / DIGIT / "-" / "." / "_" / "~"
         */
        if (
            !preg_match(
                '/^[A-Za-z0-9\-\._~]+$/',
                $verifier
            )
        ) {
            throw new RuntimeException(
                'Invalid PKCE code verifier.'
            );
        }
    }

    private function validateNonce(
        string $nonce
    ): void {
        $max =
            (int) ($this->securityConfig[
                'max_nonce_length'
            ] ?? 255);

        if (
            $nonce === '' ||
            strlen($nonce) > $max
        ) {
            throw new RuntimeException(
                'Invalid OAuth nonce.'
            );
        }
    }
}
