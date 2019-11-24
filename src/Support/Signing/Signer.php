<?php

namespace Cronboard\Support\Signing;

class Signer
{
    const SIGNATURE_HEADER = 'X-Cronboard-Signature';
    
    public function calculateSignature(string $content, string $key): string
    {
        return hash_hmac('sha256', $content, $key);
    }
}
