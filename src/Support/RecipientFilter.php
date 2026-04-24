<?php

namespace Meanify\LaravelNotifications\Support;

class RecipientFilter
{
    /**
     * @param array $recipients
     * @return array{allowed: array, blocked: array}
     */
    public static function filter(array $recipients): array
    {
        $config = config('meanify-laravel-notifications.email.recipient_filter', []);

        if (empty($config['enabled'])) {
            return ['allowed' => $recipients, 'blocked' => []];
        }

        $allowed = [];
        $blocked = [];

        foreach ($recipients as $recipient) {
            $reason = self::getBlockReason($recipient, $config);

            if ($reason !== null) {
                $blocked[] = ['email' => $recipient, 'reason' => $reason];
            } else {
                $allowed[] = $recipient;
            }
        }

        return ['allowed' => $allowed, 'blocked' => $blocked];
    }

    /**
     * @param string $email
     * @param array $config
     * @return string|null
     */
    protected static function getBlockReason(string $email, array $config): ?string
    {
        if (!empty($config['block_encrypted']) && self::looksEncrypted($email)) {
            return 'encrypted';
        }

        if (!empty($config['block_base64']) && self::looksBase64($email)) {
            return 'base64_encoded';
        }

        if (!empty($config['blocked_domains']) && self::matchesBlockedDomain($email, $config['blocked_domains'])) {
            return 'blocked_domain';
        }

        return null;
    }

    /**
     * @param string $email
     * @return bool
     */
    protected static function looksEncrypted(string $email): bool
    {
        if (!str_contains($email, '@')) {
            return true;
        }

        $localPart = explode('@', $email)[0];

        // eyJ prefix is common in encrypted/JWT payloads
        if (str_starts_with($localPart, 'eyJ')) {
            return true;
        }

        // High ratio of non-alphanumeric chars suggests encryption
        $nonAlnum = preg_replace('/[a-zA-Z0-9._\-+]/', '', $localPart);
        if (strlen($localPart) > 0 && (strlen($nonAlnum) / strlen($localPart)) > 0.3) {
            return true;
        }

        return false;
    }

    /**
     * @param string $email
     * @return bool
     */
    protected static function looksBase64(string $email): bool
    {
        if (!str_contains($email, '@')) {
            // Entire string might be base64
            return self::isBase64String($email);
        }

        $localPart = explode('@', $email)[0];

        return self::isBase64String($localPart);
    }

    /**
     * @param string $value
     * @return bool
     */
    protected static function isBase64String(string $value): bool
    {
        if (strlen($value) < 8) {
            return false;
        }

        // Must match base64 character set
        if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $value)) {
            return false;
        }

        // Try decoding — if it decodes and re-encodes to the same value, it's base64
        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return false;
        }

        return base64_encode($decoded) === $value;
    }

    /**
     * @param string $email
     * @param array $patterns
     * @return bool
     */
    protected static function matchesBlockedDomain(string $email, array $patterns): bool
    {
        if (!str_contains($email, '@')) {
            return false;
        }

        $domain = strtolower(explode('@', $email)[1]);

        foreach ($patterns as $pattern) {
            $pattern = strtolower($pattern);

            if ($pattern === $domain) {
                return true;
            }

            // Wildcard support: *.example.com matches sub.example.com
            if (str_starts_with($pattern, '*.')) {
                $suffix = substr($pattern, 1); // .example.com
                if (str_ends_with($domain, $suffix)) {
                    return true;
                }
            }

            // Wildcard support: testenv.* matches testenv.pwc.com, testenv.anything.io
            if (str_ends_with($pattern, '.*')) {
                $prefix = substr($pattern, 0, -1); // testenv.
                if (str_starts_with($domain, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }
}
