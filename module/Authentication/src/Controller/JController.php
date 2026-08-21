<?php declare(strict_types=1);

namespace Auth\Controller;

use Auth\Service\GoogleOAuthService;
use Auth\Service\JwtService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use RuntimeException;
use Throwable;

final class AuthController extends AbstractActionController
{
    private GoogleOAuthService $google;
    private JwtService $jwt;
    private array $config;

    public function __construct(
        GoogleOAuthService $google,
        JwtService $jwt,
        array $config
    ) {
        $this->google = $google;
        $this->jwt = $jwt;
        $this->config = $config;
    }

    /**
     * POST /auth/google
     *
     * Request:
     *
     * {
     *   "code": "...",
     *   "redirect_uri": "...",
     *   "code_verifier": "...",
     *   "nonce": "..."
     * }
     */
    public function googleAction()
    {
        try {
            $this->assertPostRequest();

            $body =
                $this->readJsonBody();

            $code =
                $this->requiredString(
                    $body,
                    'code',
                    2048
                );

            $redirectUri =
                $this->requiredString(
                    $body,
                    'redirect_uri',
                    2048
                );

            $codeVerifier =
                $this->requiredString(
                    $body,
                    'code_verifier',
                    128
                );

            $nonce =
                $this->requiredString(
                    $body,
                    'nonce',
                    255
                );

            /*
             * -----------------------------------------------------
             * 1. Exchange Google authorization code.
             * -----------------------------------------------------
             */
            $googleTokens =
                $this
                    ->google
                    ->exchangeAuthorizationCode(
                        $code,
                        $redirectUri,
                        $codeVerifier
                    );

            /*
             * -----------------------------------------------------
             * 2. Verify Google's signed ID token.
             * -----------------------------------------------------
             */
            $googleUser =
                $this
                    ->google
                    ->validateIdToken(
                        (string) $googleTokens['id_token'],
                        $nonce
                    );

            /*
             * -----------------------------------------------------
             * 3. Find/create local account.
             * -----------------------------------------------------
             *
             * IMPORTANT:
             * google_sub is the identity key.
             *
             * Do not use email as the Google identity key.
             */
            $user =
                $this->findOrCreateGoogleUser(
                    $googleUser
                );

            /*
             * -----------------------------------------------------
             * 4. Generate application's own JWT.
             * -----------------------------------------------------
             */
            $access =
                $this
                    ->jwt
                    ->createAccessToken(
                        $user
                    );

            /*
             * -----------------------------------------------------
             * 5. Generate refresh token.
             * -----------------------------------------------------
             */
            $refresh =
                $this
                    ->jwt
                    ->createRefreshToken(
                        $user
                    );

            /*
             * In a production system:
             *
             * store the refresh-token jti in a database.
             */
            $this->storeRefreshToken(
                $user['id'],
                $refresh['jti']
            );

            /*
             * -----------------------------------------------------
             * 6. Return exact structure expected by Qt.
             * -----------------------------------------------------
             */
            return $this->json(
                [
                    'success' => true,
                    'access_token' =>
                        $access['token'],
                    'refresh_token' =>
                        $refresh['token'],
                    'expires_in' =>
                        $access['expires_in'],
                    'user' => [
                        'id' =>
                            (string) $user['id'],
                        'username' =>
                            $user['username'],
                        'email' =>
                            $user['email'],
                        'display_name' =>
                            $user['display_name'],
                    ],
                ]
            );
        } catch (Throwable $e) {
            /*
             * Do NOT return internal exception details
             * to the mobile application.
             */
            return $this->error(
                'Google authentication failed.',
                401
            );
        }
    }

    /**
     * GET /auth/me
     */
    public function meAction()
    {
        try {
            $token =
                $this->getBearerToken();

            $payload =
                $this
                    ->jwt
                    ->validateAccessToken(
                        $token
                    );

            /*
             * Load the current account from the database.
             *
             * Do not return account details directly from the JWT
             * when those values can change.
             */
            $user =
                $this->findUserById(
                    (string) $payload->sub
                );

            if ($user === null) {
                return $this->error(
                    'User account no longer exists.',
                    401
                );
            }

            return $this->json([
                'success' =>
                    true,
                'user' => [
                    'id' =>
                        (string) $user['id'],
                    'username' =>
                        $user['username'],
                    'email' =>
                        $user['email'],
                    'display_name' =>
                        $user['display_name'],
                ],
            ]);
        } catch (Throwable $e) {
            return $this->error(
                'Invalid or expired access token.',
                401
            );
        }
    }

    /**
     * POST /auth/refresh
     */
    public function refreshAction()
    {
        try {
            $this->assertPostRequest();

            $body =
                $this->readJsonBody();

            $refreshToken =
                $this->requiredString(
                    $body,
                    'refresh_token',
                    16384
                );

            /*
             * Cryptographically validate JWT.
             */
            $payload =
                $this
                    ->jwt
                    ->validateRefreshToken(
                        $refreshToken
                    );

            /*
             * A refresh token MUST additionally be checked
             * against server-side state if revocation/rotation
             * is required.
             */
            if (
                !$this->isRefreshTokenActive(
                    (string) $payload->jti
                )
            ) {
                return $this->error(
                    'Refresh token has been revoked.',
                    401
                );
            }

            $user =
                $this->findUserById(
                    (string) $payload->sub
                );

            if ($user === null) {
                return $this->error(
                    'User account no longer exists.',
                    401
                );
            }

            /*
             * Rotate refresh token.
             */
            $this->revokeRefreshToken(
                (string) $payload->jti
            );

            $access =
                $this
                    ->jwt
                    ->createAccessToken(
                        $user
                    );

            $refresh =
                $this
                    ->jwt
                    ->createRefreshToken(
                        $user
                    );

            $this->storeRefreshToken(
                $user['id'],
                $refresh['jti']
            );

            return $this->json([
                'success' =>
                    true,
                'access_token' =>
                    $access['token'],
                'refresh_token' =>
                    $refresh['token'],
                'expires_in' =>
                    $access['expires_in'],
                'user' => [
                    'id' =>
                        (string) $user['id'],
                    'username' =>
                        $user['username'],
                    'email' =>
                        $user['email'],
                    'display_name' =>
                        $user['display_name'],
                ],
            ]);
        } catch (Throwable $e) {
            return $this->error(
                'Invalid or expired refresh token.',
                401
            );
        }
    }

    /**
     * POST /auth/logout
     */
    public function logoutAction()
    {
        try {
            $this->assertPostRequest();

            $body =
                $this->readJsonBody();

            $refreshToken =
                $this->requiredString(
                    $body,
                    'refresh_token',
                    16384
                );

            $payload =
                $this
                    ->jwt
                    ->validateRefreshToken(
                        $refreshToken
                    );

            $this->revokeRefreshToken(
                (string) $payload->jti
            );

            return $this->json([
                'success' =>
                    true,
            ]);
        } catch (Throwable $e) {
            /*
             * Logout is intentionally idempotent.
             */
            return $this->json([
                'success' =>
                    true,
            ]);
        }
    }

    private function findOrCreateGoogleUser(
        array $googleUser
    ): array {
        /*
         * =========================================================
         * REPLACE THIS WITH YOUR USER REPOSITORY.
         * =========================================================
         *
         * Search by:
         *
         *     google_sub = $googleUser['sub']
         *
         * Never make email the primary Google identity.
         *
         * Google documents `sub` as the stable unique Google
         * account identifier.
         */

        $googleSub =
            $googleUser['sub'];

        /*
         * Example only.
         */
        $existingUser =
            $this->findUserByGoogleSub(
                $googleSub
            );

        if ($existingUser !== null) {
            return $existingUser;
        }

        /*
         * Create a new local account.
         */
        return $this->createGoogleUser(
            $googleUser
        );
    }

    private function getBearerToken(): string
    {
        $authorization =
            $this
                ->getRequest()
                ->getHeader(
                    'Authorization'
                );

        if ($authorization === false) {
            throw new RuntimeException(
                'Authorization header missing.'
            );
        }

        $value =
            trim(
                $authorization->getFieldValue()
            );

        if (
            !preg_match(
                '/^Bearer\s+([A-Za-z0-9\-._~+\/]+=*)$/',
                $value,
                $matches
            )
        ) {
            throw new RuntimeException(
                'Invalid authorization header.'
            );
        }

        return $matches[1];
    }

    private function readJsonBody(): array
    {
        $maxBody =
            (int) (
                $this->config['auth']['security'][
                    'max_body_bytes'
                ]
                    ?? 8192
            );

        $raw =
            $this
                ->getRequest()
                ->getContent();

        if (
            !is_string($raw) ||
            $raw === ''
        ) {
            throw new RuntimeException(
                'Request body is empty.'
            );
        }

        if (
            strlen($raw) >
            $maxBody
        ) {
            throw new RuntimeException(
                'Request body is too large.'
            );
        }

        try {
            $body =
                json_decode(
                    $raw,
                    true,
                    16,
                    JSON_THROW_ON_ERROR
                );
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Invalid JSON body.',
                0,
                $e
            );
        }

        if (
            !is_array($body)
        ) {
            throw new RuntimeException(
                'JSON body must be an object.'
            );
        }

        return $body;
    }

    private function requiredString(
        array $body,
        string $field,
        int $maxLength
    ): string {
        if (
            !array_key_exists(
                $field,
                $body
            ) ||
            !is_string(
                $body[$field]
            )
        ) {
            throw new RuntimeException(
                "Missing {$field}."
            );
        }

        $value =
            trim(
                $body[$field]
            );

        if (
            $value === '' ||
            strlen($value) > $maxLength
        ) {
            throw new RuntimeException(
                "Invalid {$field}."
            );
        }

        return $value;
    }

    private function assertPostRequest(): void
    {
        if (
            !$this
                ->getRequest()
                ->isPost()
        ) {
            throw new RuntimeException(
                'POST required.'
            );
        }
    }

    private function json(
        array $data
    ): JsonModel {
        return new JsonModel(
            $data
        );
    }

    private function error(
        string $message,
        int $status
    ): JsonModel {
        $this
            ->getResponse()
            ->setStatusCode(
                $status
            );

        return new JsonModel([
            'success' =>
                false,
            'error' =>
                $message,
        ]);
    }

    /*
     * =============================================================
     * DATABASE METHODS
     * =============================================================
     *
     * These methods should call your actual repositories.
     */

    private function findUserByGoogleSub(
        string $googleSub
    ): ?array {
        return null;
    }

    private function findUserById(
        string $id
    ): ?array {
        return null;
    }

    private function createGoogleUser(
        array $googleUser
    ): array {
        throw new RuntimeException(
            'Implement createGoogleUser() against your user repository.'
        );
    }

    private function storeRefreshToken(
        int|string $userId,
        string $jti
    ): void {
        /*
         * Recommended table:
         *
         * refresh_tokens
         * -------------------------
         * id
         * user_id
         * jti_hash
         * expires_at
         * revoked_at
         * created_at
         */
    }

    private function isRefreshTokenActive(
        string $jti
    ): bool {
        /*
         * Query:
         *
         * SHA-256(jti)
         *
         * and ensure:
         *
         * revoked_at IS NULL
         * expires_at > NOW()
         */
        return true;
    }

    private function revokeRefreshToken(
        string $jti
    ): void {
        /*
         * UPDATE refresh_tokens
         * SET revoked_at = NOW()
         * WHERE jti_hash = SHA256($jti)
         */
    }
}
