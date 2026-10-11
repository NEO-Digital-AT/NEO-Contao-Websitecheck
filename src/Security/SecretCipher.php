<?php

declare(strict_types=1);

namespace Neo\WebsitecheckBundle\Security;

/**
 * Symmetric encryption for the API keys stored at rest (PageSpeed, AI provider). Uses libsodium's
 * authenticated secretbox with a 32-byte key derived from the application secret
 * (kernel.secret / APP_SECRET). Values are tagged with an "enc:" prefix so a
 * plaintext value (e.g. one injected from an environment variable) is passed
 * through unchanged on decrypt.
 *
 * Note: rotating APP_SECRET invalidates previously stored ciphertexts — the key
 * then has to be re-entered in the back end (documented behaviour).
 */
final class SecretCipher
{
    private const PREFIX = 'enc:';

    public function __construct(private readonly string $secret)
    {
    }

    public function encrypt(string $plain): string
    {
        if ('' === $plain) {
            return '';
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plain, $nonce, $this->deriveKey());

        return self::PREFIX.base64_encode($nonce.$cipher);
    }

    public function decrypt(string $stored): string
    {
        if ('' === $stored) {
            return '';
        }

        // Not encrypted (e.g. a plaintext value provided via an env variable).
        if (!str_starts_with($stored, self::PREFIX)) {
            return $stored;
        }

        $raw = base64_decode(substr($stored, \strlen(self::PREFIX)), true);
        $min = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES;

        if (false === $raw || \strlen($raw) < $min) {
            return '';
        }

        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open($cipher, $nonce, $this->deriveKey());

        return false === $plain ? '' : $plain;
    }

    public function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    private function deriveKey(): string
    {
        return hash('sha256', 'neo-websitecheck:'.$this->secret, true);
    }
}
