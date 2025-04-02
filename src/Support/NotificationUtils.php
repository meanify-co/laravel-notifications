<?php

namespace Meanify\LaravelNotifications\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class NotificationUtils
{
    /**
     * @param object $user
     * @param string|null $locale
     */
    public function __construct()
    {
        return $this;
    }

    /**
     * @param string $recipient
     * @return string|null
     */
    public function decryptRecipientToUnsubscribe(string $recipient): string|null
    {
        try
        {
            return Crypt::decrypt($recipient);
        }
        catch (\Exception $e)
        {
            Log::error($e->getMessage());
            
            return null;
        }
    }
}
