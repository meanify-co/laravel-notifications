<?php

namespace Meanify\LaravelNotifications\Support;

use Meanify\LaravelNotifications\Services\NotificationDispatcher;

class NotificationBuilder
{
    protected string $locale;

    protected object $to;
    protected ?string $templateKey = null;
    protected ?int $accountId = null;
    protected ?int $applicationId = null;
    protected ?int $sessionId = null;
    protected array $replacements = [];
    protected array $overrideEmails = [];
    protected array $smtpConfigs = [];

    public static function make(object $to_user, ?string $locale): static
    {
        return new static($to_user, $locale);
    }

    public function __construct(object $to, ?string $locale = null)
    {
        $this->setTo($to);
        $this->setLocale($locale ?? config('app.locale'));

        return $this;
    }

    protected function setLocale(string $locale)
    {
        $this->locale = str_replace('-','_',$locale);
    }

    protected function setTo(object $to)
    {
        $this->to = $to;
    }

    public function onAccount(?int $accountId): static
    {
        $this->accountId = $accountId;
        return $this;
    }

    public function onApplication(?int $applicationId): static
    {
        $this->applicationId = $applicationId;
        return $this;
    }

    public function onSession(?int $sessionId): static
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    public function email(array $smtpConfigs, array $recipients, ?string $emailTemplateKey): static
    {
        $this->setSmtpConfigs($smtpConfigs);
        $this->setEmails($recipients);
        $this->setEmailTemplateKey($emailTemplateKey);
        return $this;
    }

    protected function setSmtpConfigs(array $configs)
    {
        $this->smtpConfigs = $configs;
    }

    protected function setEmailTemplateKey(?string $templateKey)
    {
        $this->templateKey = $templateKey;
    }

    protected function setEmails(array $emails)
    {
        $this->overrideEmails = $emails;
    }

    public function with(array $replacements): static
    {
        $this->replacements = $replacements;
        return $this;
    }

    public function send(): void
    {
        app(NotificationDispatcher::class)->dispatch(
            $this->locale,
            $this->to,
            $this->templateKey,
            $this->replacements,
            $this->accountId,
            $this->applicationId,
            $this->sessionId,
            $this->overrideEmails,
            $this->smtpConfigs
        );
    }
}
