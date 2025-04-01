<?php

namespace Meanify\LaravelNotifications\Support;

use Meanify\LaravelNotifications\Services\NotificationDispatcher;

class NotificationBuilder
{
    protected string $templateKey;
    protected string $locale;
    protected object $to;
    protected ?int $accountId = null;
    protected ?int $applicationId = null;
    protected ?int $sessionId = null;
    protected array $replacements = [];
    protected array $overrideEmails = [];
    protected array $smtpConfigs = [];

    public static function make(): static
    {
        return new static();
    }

    public function __construct()
    {
        //
    }

    public function locale(string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }

    public function to(object $to): static
    {
        $this->to = $to;
        return $this;
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
            $this->templateKey,
            $this->locale,
            $this->to,
            $this->replacements,
            $this->accountId,
            $this->applicationId,
            $this->sessionId,
            $this->overrideEmails,
            $this->smtpConfigs
        );
    }
}
