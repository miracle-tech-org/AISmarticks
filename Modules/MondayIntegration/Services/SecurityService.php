<?php

namespace Modules\MondayIntegration\Services;

class SecurityService
{
    protected $encryptionKey;

    public function __construct()
    {
        $this->encryptionKey = config('app.key');
    }

    public function encrypt($value)
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            $value,
            'AES-256-CBC',
            $this->encryptionKey,
            0,
            $iv
        );
        
        return base64_encode($iv . $encrypted);
    }

    public function decrypt($value)
    {
        $decoded = base64_decode($value);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        
        return openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            $this->encryptionKey,
            0,
            $iv
        );
    }

    public function validateToken($token)
    {
        return preg_match('/^[a-zA-Z0-9]{32,}$/', $token);
    }
}
