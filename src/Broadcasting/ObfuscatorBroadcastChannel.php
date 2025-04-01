<?php

namespace Meanify\LaravelNotifications\Broadcasting;

use Illuminate\Support\Facades\Request;
use Meanify\LaravelObfuscator\Support\IdObfuscator;

class ObfuscatorBroadcastChannel
{
    public static function validate($user, string $encoded): bool
    {
        try {
            $encoded = str_replace('mfy_channel_', '', $encoded);
            $decoded = base64_decode($encoded);
            [$model, $obfuscatedId] = explode('::', $decoded);

            $realId = IdObfuscator::decode($obfuscatedId, $model);

            if (! $realId) {
                return false;
            }

            $userId = request()->header('x-mfy-user-id');
            $accountId = request()->header('x-mfy-account-id');
            $applicationId = request()->header('x-mfy-application-id');
            $sessionId = request()->header('x-mfy-session-id');

            return match ($model) {
                \App\Models\User::class =>  $userId == $realId,
                \App\Models\Account::class => $accountId == $realId,
                \App\Models\Application::class => $applicationId == $realId,
                \App\Models\Session::class => $sessionId == $realId,
                default => false,
            };
        } catch (\Throwable) {
            return false;
        }
    }
}
