<?php

namespace Meanify\LaravelNotifications\Support;

use Carbon\Carbon;
use Meanify\LaravelNotifications\Services\MailDriverService;
use Meanify\LaravelNotifications\Services\NotificationDispatcher;

class NotificationBuilder
{
    protected ?string $notificationTemplateKey = null;
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
    protected array $attachments = [];
    protected ?string $renderedHtml = null;
    protected ?string $renderedSubject = null;
    protected array $renderedPayload = [];
    protected array $broadcastChannels = [];

    /**
     * Creates a builder from a notification template key.
     * Chain ->withRenderedHtml() instead to use a pre-rendered HTML body.
     *
     * Available preview flows:
     *   make('key', $user, 'pt_BR')->with([...])->preview()                    // template + interpolated
     *   make('key', $user, 'pt_BR')->with([...])->preview(interpolate: false)  // template + raw placeholders
     *   make(null, $user, 'pt_BR')->withRenderedHtml($html)->preview()         // HTML + layout wrapping
     *   make(null, $user, 'pt_BR')->withRenderedHtml($html)->preview(interpolate: false) // HTML + no layout render
     *
     * @param string|null $notificationTemplateKey
     * @param object|null $user
     * @param string|null $locale
     * @return static
     */
    public static function make(?string $notificationTemplateKey, ?object $user = null, ?string $locale = null): static
    {
        return new static($notificationTemplateKey, $user, $locale);
    }

    /**
     * @deprecated Use make(null, $user, $locale)->withRenderedHtml(...) instead
     */
    public static function makeWithRenderedHtml(string $renderedHtml, ?object $user = null, ?string $locale = null, ?string $subject = null, array $payload = []): static
    {
        $instance = new static(null, $user, $locale);
        return $instance->withRenderedHtml($renderedHtml, $subject, $payload);
    }

    /**
     * @param string $notificationTemplateKey
     * @param object|null $user
     * @param string|null $locale
     */
    public function __construct(?string $notificationTemplateKey, ?object $user = null, ?string $locale = null)
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
    protected function setNotificationTemplateKey(?string $notificationTemplateKey)
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
     * Define um HTML já renderizado (dinâmico) e opcionalmente um assunto específico.
     *
     * @param string $renderedHtml
     * @param string|null $subject
     * @param array $payload
     * @return $this
     */
    public function setSubject(string $subject): static
    {
        $this->renderedSubject = $subject;
        return $this;
    }

    public function withRenderedHtml(string $renderedHtml, ?string $subject = null, array $payload = []): static
    {
        $this->renderedHtml     = $renderedHtml;
        $this->renderedSubject  = $subject;
        $this->renderedPayload  = $payload;
        $this->notificationTemplateKey = null;

        return $this;
    }

    /**
     * @param array $attachments Array of attachment configurations
     * Each attachment should have:
     * - 'path' (string): File path or content
     * - 'name' (string, optional): Display name for the attachment
     * - 'mime' (string, optional): MIME type of the attachment
     * - 'content' (string, optional): Base64 encoded content (alternative to path)
     * @return $this
     */
    public function withAttachments(array $attachments): static
    {
        $this->attachments = $attachments;
        return $this;
    }

    /**
     * Define canais de broadcast customizados para notificações in-app.
     * 
     * @param array $channels Array de canais de broadcast
     * Exemplos:
     * - ['user.123', 'admin.456'] - Canais simples
     * - [['channel' => 'user.123', 'event' => 'custom.event']] - Canais com eventos customizados
     * - [['model' => User::class, 'id' => 123]] - Usando ChannelBuilder automático
     * @return $this
     */
    public function toBroadcastChannels(array $channels): static
    {
        $this->broadcastChannels = $channels;
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
     * Returns the rendered email (and metadata) without dispatching anything.
     *
     * @param bool $interpolate When false, dynamic variables are not substituted
     * @return array{subject: string, title: string, body: string, html: string}
     */
    public function preview(bool $interpolate = true): array
    {
        if (empty($this->notificationTemplateKey) && $this->renderedHtml === null) {
            throw new \InvalidArgumentException('Defina uma notification_template_key ou um HTML já renderizado antes de gerar o preview.');
        }

        return app(NotificationDispatcher::class)->preview(
            $this->notificationTemplateKey,
            $this->locale,
            $this->dynamicData,
            $interpolate,
            $this->renderedHtml,
            $this->renderedSubject,
            $this->renderedPayload
        );
    }

    /**
     * @return bool
     */
    public function send(): bool
    {
        if (empty($this->notificationTemplateKey) && $this->renderedHtml === null) {
            throw new \InvalidArgumentException('Defina uma notification_template_key ou um HTML já renderizado antes de enviar.');
        }

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
            $this->sendEmailImmediately,
            $this->attachments,
            $this->renderedHtml,
            $this->renderedSubject,
            $this->renderedPayload,
            $this->broadcastChannels
        );
    }
}
