<?php

namespace Meanify\LaravelNotifications\Support;

use Meanify\LaravelNotifications\Services\NotificationDispatcher;

class NotificationBuilder
{
    protected string $notificationTemplateKey;
    protected object $user;
    protected string $locale;
    protected ?int $accountId = null;
    protected ?int $applicationId = null;
    protected ?int $sessionId = null;
    protected bool $sendEmailImmediately = false;
    protected array $smtpConfigs = [];
    protected array $recipients = [];
    protected array $dynamicData = [];

    /**
     * @param string $notificationTemplateKey
     * @param object $user
     * @param string|null $locale
     * @return static
     */
    public static function make(string $notificationTemplateKey, object $user, ?string $locale): static
    {
        return new static($notificationTemplateKey, $user, $locale);
    }

    /**
     * @param object $user
     * @param string|null $locale
     */
    public function __construct(string $notificationTemplateKey, object $user, ?string $locale = null)
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
     * @param object $user
     * @return void
     */
    protected function setUser(object $user)
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
     * @param array $smtpConfigs
     * @param array $recipients
     * @param bool $sendImmediately
     * @return $this
     */
    public function forEmail(array $smtpConfigs, array $recipients, bool $sendImmediately = false): static
    {
        $this->setSmtpConfigs($smtpConfigs);
        $this->setRecipients($recipients);
        $this->sendEmailImmediately = $sendImmediately;
        return $this;
    }

    /**
     * @param array $configs
     * @return void
     */
    protected function setSmtpConfigs(array $configs)
    {
        $this->smtpConfigs = $configs;
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
            $this->smtpConfigs,
            $this->recipients,
            $this->dynamicData,
            $this->sendEmailImmediately
        );
    }
}
