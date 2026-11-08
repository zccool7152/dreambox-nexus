<?php
declare(strict_types=1);

namespace Nexus\Security;

use InvalidArgumentException;

/**
 * Timing-Safe Inbound Webhook Signature Verifier
 */
class WebhookVerifier
{
    private string $signingSecret;
    private int $toleranceSeconds;

    public function __construct(string $signingSecret, int $toleranceSeconds = 300)
    {
        $this->signingSecret = $signingSecret;
        $this->toleranceSeconds = $toleranceSeconds;
    }

    /**
     * Verify Stripe-style signed webhooks (t=timestamp,v1=signature)
     */
    public function verifyStripeSignature(string $payload, string $sigHeader): bool
    {
        $parts = explode(',', $sigHeader);
        $timestamp = null;
        $signature = null;

        foreach ($parts as $part) {
            $item = explode('=', trim($part), 2);
            if (count($item) === 2) {
                if ($item[0] === 't') {
                    $timestamp = (int)$item[1];
                } elseif ($item[0] === 'v1') {
                    $signature = $item[1];
                }
            }
        }

        if ($timestamp === null || $signature === null) {
            return false;
        }

        // Prevent Replay Attacks
        if (abs(time() - $timestamp) > $this->toleranceSeconds) {
            return false;
        }

        $signedPayload = "{$timestamp}.{$payload}";
        $expected = hash_hmac('sha256', $signedPayload, $this->signingSecret);

        return hash_equals($expected, $signature);
    }

    /**
     * Verify GitHub-style sha256=hash signature
     */
    public function verifySha256(string $payload, string $signatureHeader): bool
    {
        if (str_starts_with($signatureHeader, 'sha256=')) {
            $signature = substr($signatureHeader, 7);
        } else {
            $signature = $signatureHeader;
        }

        $expected = hash_hmac('sha256', $payload, $this->signingSecret);
        return hash_equals($expected, $signature);
    }
}

