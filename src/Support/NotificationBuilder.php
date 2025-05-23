<?php

namespace Meanify\LaravelNotifications\Support;

use Carbon\Carbon;
use Meanify\LaravelNotifications\Services\MailDriverService;
use Meanify\LaravelNotifications\Services\NotificationDispatcher;

class NotificationBuilder
{
    protected string $notificationTemplateKey;
    protected ?object $user = null;
    protected string $locale;
    protected ?int $accountId = null;
    protected ?int $applicationId = null;
    protected ?int $sessionId = null;
    protected ?Carbon $scheduledTo = null;
    protected bool $sendEmailImmediately = false;
    protected string $mailDriverType = 'smtp';
    protected array $mailDriverConfigs = [];
    protected array $recipients = [];
    protected array $dynamicData = [];

    /**
     * @param string $notificationTemplateKey
     * @param object|null $user
     * @param string|null $locale
     * @return static
     */
    public static function make(string $notificationTemplateKey, ?object $user, ?string $locale): static
    {
        return new static($notificationTemplateKey, $user, $locale);
    }

    /**
     * @param string $notificationTemplateKey
     * @param object|null $user
     * @param string|null $locale
     */
    public function __construct(string $notificationTemplateKey, ?object $user = null, ?string $locale = null)
    {
        $this->setNotificationTemplateKey($notificationTemplateKey);
        $this->setUser($user);
        $this->setLocale($locale ?? config('app.locale'));

        return $this;
    }

    /**
     * @param string $notificationTemplateKey
     * @return void
     */
    protected function setNotificationTemplateKey(string $notificationTemplateKey)
    {
        $this->notificationTemplateKey = $notificationTemplateKey;
    }

    /**
     * @param object|null $user
     * @return void
     */
    protected function setUser(?object $user)
    {
        $this->user = $user;
    }

    /**
     * @param string $locale
     * @return void
     */
    protected function setLocale(string $locale)
    {
        $this->locale = str_replace('-','_',$locale);
    }

    /**
     * @param int|null $accountId
     * @return $this
     */
    public function onAccount(?int $accountId): static
    {
        $this->accountId = $accountId;
        return $this;
    }

    /**
     * @param int|null $applicationId
     * @return $this
     */
    public function onApplication(?int $applicationId): static
    {
        $this->applicationId = $applicationId;
        return $this;
    }

    /**
     * @param int|null $sessionId
     * @return $this
     */
    public function onSession(?int $sessionId): static
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * @param string $mailDriverType
     * @param array $mailDriverConfigs
     * @param array $recipients
     * @param bool $sendImmediately
     * @return $this
     */
    public function forEmail(string $mailDriverType, array $mailDriverConfigs, array $recipients, bool $sendImmediately = false): static
    {
        $this->setMailDriver($mailDriverType, $mailDriverConfigs);
        $this->setRecipients($recipients);
        $this->sendEmailImmediately = $sendImmediately;
        return $this;
    }

    /**
     * @param string $mailDriverType
     * @param array $mailDriverConfigs
     * @return void
     */
    protected function setMailDriver(string $mailDriverType, array $mailDriverConfigs): void
    {
        $this->mailDriverType    = $mailDriverType;
        $this->mailDriverConfigs = MailDriverService::getParams($mailDriverType, $mailDriverConfigs);
    }

    /**
     * @param array $recipients
     * @return void
     */
    protected function setRecipients(array $recipients)
    {
        $this->recipients = $recipients;
    }

    /**
     * @param array $dynamicData
     * @return $this
     */
    public function with(array $dynamicData): static
    {
        $this->dynamicData = $dynamicData;
        return $this;
    }

    /**
     * @param Carbon $scheduledTo
     * @return $this
     */
    public function scheduledTo(Carbon $scheduledTo): static
    {
        $this->scheduledTo = $scheduledTo;
        return $this;
    }

    /**
     * @return bool
     */
    public function send(): bool
    {
        return app(NotificationDispatcher::class)->dispatch(
            $this->notificationTemplateKey,
            $this->user,
            $this->locale,
            $this->accountId,
            $this->applicationId,
            $this->sessionId,
            $this->mailDriverType,
            $this->mailDriverConfigs,
            $this->recipients,
            $this->dynamicData,
            $this->scheduledTo,
            $this->sendEmailImmediately
        );
    }
}
