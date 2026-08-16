<?php

namespace Authentication\Service;

class JWTConfig
{
    private string $issuer;
    private string $secretKeyExpires;
    private string $refreshKeyExpires;
    private string $signKey;

    public function __construct(array $config)
    {
        $this->issuer = $config['issuer'] ?? '';
        $this->secretKeyExpires = $config['secret_key_expires'] ?? '';
        $this->refreshKeyExpires = $config['refresh_key_expires'] ?? '';
        $this->signKey = $config['sign_key'] ?? '';
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function getSecretKeyExpires(): string
    {
        return $this->secretKeyExpires;
    }

    public function getRefreshKeyExpires(): string
    {
        return $this->refreshKeyExpires;
    }

    public function getSignKey(): string
    {
        return $this->signKey;
    }
}
