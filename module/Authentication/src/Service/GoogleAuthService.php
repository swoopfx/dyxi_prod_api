<?php

namespace Authentication\Service;

use RuntimeException;

class GoogleAuthService
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config =
            $config['google_oauth'];
    }

    public function exchangeAuthorizationCode(
        string $code,
        string $redirectUri,
        string $codeVerifier
    ): array {
        if ($code === '') {
            throw new RuntimeException(
                'Google authorization code is required.'
            );
        }

        if ($codeVerifier === '') {
            throw new RuntimeException(
                'PKCE code verifier is required.'
            );
        }

        /*
         * The redirect URI supplied by the client must match
         * the redirect URI registered for this OAuth client.
         */

        if (
            !hash_equals(
                $this->config['redirect_uri'],
                $redirectUri
            )
        ) {
            throw new RuntimeException(
                'Invalid redirect URI.'
            );
        }

        $postData = http_build_query([
            'code' =>
                $code,
            'client_id' =>
                $this->config['client_id'],
            'client_secret' =>
                $this->config['client_secret'],
            'redirect_uri' =>
                $redirectUri,
            'grant_type' =>
                'authorization_code',
            'code_verifier' =>
                $codeVerifier,
        ]);

        $ch = curl_init(
            $this->config['token_url']
        );

        curl_setopt_array($ch, [
            CURLOPT_POST =>
                true,
            CURLOPT_POSTFIELDS =>
                $postData,
            CURLOPT_RETURNTRANSFER =>
                true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT =>
                15,
            CURLOPT_SSL_VERIFYPEER =>
                true,
            CURLOPT_SSL_VERIFYHOST =>
                2,
        ]);

        $response =
            curl_exec($ch);

        $httpCode =
            curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );

        $curlError =
            curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException(
                'Google token request failed: '
                . $curlError
            );
        }

        $data =
            json_decode(
                $response,
                true
            );

        if (!is_array($data)) {
            throw new RuntimeException(
                'Invalid response from Google.'
            );
        }

        if (
            $httpCode < 200 ||
            $httpCode >= 300
        ) {
            throw new RuntimeException(
                $data['error_description']
                    ?? 'Google authorization failed.'
            );
        }

        if (
            empty($data['id_token'])
        ) {
            throw new RuntimeException(
                'Google did not return an ID token.'
            );
        }

        return $data;
    }

    public function getGoogleUser(
        string $accessToken
    ): array {
        $ch = curl_init(
            $this->config['userinfo_url']
        );

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER =>
                true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '
                    . $accessToken,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT =>
                15,
            CURLOPT_SSL_VERIFYPEER =>
                true,
            CURLOPT_SSL_VERIFYHOST =>
                2,
        ]);

        $response =
            curl_exec($ch);

        $httpCode =
            curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );

        $curlError =
            curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException(
                'Google userinfo request failed: '
                . $curlError
            );
        }

        $data =
            json_decode(
                $response,
                true
            );

        if (
            $httpCode < 200 ||
            $httpCode >= 300
        ) {
            throw new RuntimeException(
                'Unable to retrieve Google profile.'
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException(
                'Invalid Google profile response.'
            );
        }

        return $data;
    }
}
