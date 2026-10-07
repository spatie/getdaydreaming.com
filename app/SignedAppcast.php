<?php

namespace App;

class SignedAppcast
{
    public function isValid(string $body): bool
    {
        $signatureOffset = strrpos($body, "<!-- sparkle-signatures:\n");

        if ($signatureOffset === false) {
            return false;
        }

        $unsignedBody = substr($body, 0, $signatureOffset);
        $signatureBlock = substr($body, $signatureOffset);

        if (preg_match('/\A<!-- sparkle-signatures:\nedSignature: ([A-Za-z0-9+\/]{86}==)\nlength: ([1-9]\d*)\n-->\n?\z/', $signatureBlock, $matches) !== 1) {
            return false;
        }

        if ((int) $matches[2] !== strlen($unsignedBody)) {
            return false;
        }

        $signature = base64_decode($matches[1], true);
        $publicKey = base64_decode(config('services.appcast.public_key'), true);

        if (! is_string($signature) || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || ! is_string($publicKey) || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($signature, $unsignedBody, $publicKey);
    }
}
