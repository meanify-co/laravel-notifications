<?php

namespace Meanify\LaravelNotifications\Support;

use Meanify\LaravelObfuscator\Support\IdObfuscator;

class ChannelBuilder
{
    /**
     * @param string $modelClass
     * @param mixed $modelInstanceOrId
     * @return string
     */
    public static function makeChannel(string $modelClass, mixed $modelInstanceOrId): string
    {
        $obfuscatedId = IdObfuscator::encode($modelInstanceOrId, $modelClass);
        $encoded = base64_encode("{$modelClass}::{$obfuscatedId}");

        return "mfy_channel_{$encoded}";
    }
}
