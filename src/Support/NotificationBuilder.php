<?php

namespace Meanify\LaravelNotifications\Support;

use Meanify\LaravelNotifications\Services\NotificationDispatcher;

class NotificationBuilder
{
    protected string $templateKey;
    protected string $locale;
    protected mixed $to;
    protected ?int $accountId = null;
    protected ?int $applicationId = null;
    protected ?int $sessionId = null;
    protected array $replacements = [];
    protected array $overrideEmails = [];
    protected array $smtpConfigs = [];

    public static function make(string $templateKey): static
    {
        return new static($templateKey);
    }

    public function __construct(string $templateKey)
    {
        $this->templateKey = $templateKey;
    }

    public function locale(string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }

    public function to(mixed $to): static
    {
        $this->to = $to;
        return $this;
    }

    public function emails(array $emails): self
    {
        $this->overrideEmails = $emails;
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

    public function smtpConfigs(array $configs): self
    {
        $this->smtpConfigs = $configs;
        return $this;
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
