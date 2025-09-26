<?php

namespace Meanify\LaravelNotifications\Jobs;

use GuzzleHttp\Client;
use http\Params;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Notification;
use Mailgun\Mailgun;
use Meanify\LaravelNotifications\Support\NotificationRenderer;
use Sendpulse\RestApi\ApiClient;
use Sendpulse\RestApi\Storage\FileStorage;

class SendNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Notification $notification;
    public int $tries;
    public int $backoff;

    /**
     * @param Notification $notification
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;

        $this->tries   = config('meanify-laravel-notifications.email.tries', 3);
        $this->backoff = config('meanify-laravel-notifications.email.backoff', 30);

        $this->onQueue(config('meanify-laravel-notifications.email.queue', 'meanify.queue.notifications.emails'));
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        if (!config('meanify-laravel-notifications.email.enabled', true)) {
            return;
        }

        self::execute($this->notification);
    }

    /**
     * @param $notification
     * @return bool
     */
    public static function execute($notification): bool
    {
        try
        {
            $notification->update(['status' => Notification::NOTIFICATION_STATUS_PROCESSING]);


            $mail = $notification->payload['__mail'] ?? null;

            $recipients  = $notification->payload['__recipients'] ?? [];
            $attachments = $notification->payload['__attachments'] ?? [];

            $html = app(NotificationRenderer::class)->renderEmail($notification);

            $subject = $notification->payload['subject'] ?? 'App notification';

            if($mail['driver'] === 'smtp')
            {
                self::sendEmailWithSmtp($mail['configs'], $subject, $html, $recipients, $attachments);
            }
            else if($mail['driver'] === 'mailgun')
            {
                self::sendEmailWithMailgun($mail['configs'], $subject, $html, $recipients, $attachments);
            }
            else if($mail['driver'] === 'sendgrid')
            {
                self::sendEmailWithSendGrid($mail['configs'], $subject, $html, $recipients, $attachments);
            }
            else if($mail['driver'] === 'sendpulse')
            {
                self::sendEmailWithSendPulse($mail['configs'], $subject, $html, $recipients, $attachments);
            }
            else
            {
                throw new \Exception('Invalid driver to send email: ' . $mail['driver']);
            }

            $notification->update(['status' => Notification::NOTIFICATION_STATUS_SENT, 'sent_at' => now()]);

            return true;
        }
        catch (\Throwable $e)
        {
            $notification->update([
                'status' => Notification::NOTIFICATION_STATUS_FAILED,
                'failed_at' => now(),
                'failed_log' => [
                    'exception' => [
                        'code' => $e->getCode(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'message' => $e->getMessage(),
                    ],
                ]
            ]);

            Log::emergency('Email notification failed', [
                'notification_id' => $notification->id,
                'exception' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'message' => $e->getMessage(),
                ],
            ]);

            return false;
        }
    }

    /**
     * @param array $mailConfigs
     * @param string $subject
     * @param string $body
     * @param array $recipients
     * @param array $attachments
     * @return void
     */
    protected static function sendEmailWithSmtp(array $mailConfigs, string $subject, string $body, array $recipients, array $attachments = [])
    {
        try
        {
            $password = Crypt::decrypt($mailConfigs['password']);
        }
        catch (\Exception $e)
        {
            $password = $mailConfigs['password'];
        }

        config([
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.host'       => $mailConfigs['host'] ?? '',
            'mail.mailers.smtp.port'       => $mailConfigs['port'] ?? 587,
            'mail.mailers.smtp.encryption' => $mailConfigs['encryption'] ?? 'tls',
            'mail.mailers.smtp.username'   => $mailConfigs['username'] ?? null,
            'mail.mailers.smtp.password'   => $password,
            'mail.from.address'            => $mailConfigs['from_address'] ?? config('mail.from.address'),
            'mail.from.name'               => $mailConfigs['from_name'] ?? config('mail.from.name'),
        ]);

        foreach ($recipients as $recipient)
        {
            $html = str_replace('{{recipient}}', Crypt::encrypt($recipient), $body);

            Mail::html($html, function ($message) use ($recipient, $subject, $attachments) {
                $message->to($recipient);
                $message->subject($subject);
                
                // Adicionar anexos
                foreach ($attachments as $attachment) {
                    if (isset($attachment['content'])) {
                        // Anexo com conteúdo base64
                        $message->attachData(
                            base64_decode($attachment['content']),
                            $attachment['name'] ?? 'attachment',
                            ['mime' => $attachment['mime'] ?? 'application/octet-stream']
                        );
                    } elseif (isset($attachment['path']) && file_exists($attachment['path'])) {
                        // Anexo com caminho de arquivo
                        $message->attach(
                            $attachment['path'],
                            ['as' => $attachment['name'] ?? basename($attachment['path'])]
                        );
                    }
                }
            });
        }
    }

    /**
     * @param array $mailConfigs
     * @param string $subject
     * @param string $body
     * @param array $recipients
     * @param array $attachments
     * @return void
     */
    protected static function sendEmailWithMailgun(array $mailConfigs, string $subject, string $body, array $recipients, array $attachments = [])
    {
        $mailFromName    = $mailConfigs['from_name'] ?? config('mail.from.name');
        $mailFromAddress = $mailConfigs['from_address'] ?? config('mail.from.address');

        foreach ($recipients as $recipient)
        {
            $html = str_replace('{{recipient}}', Crypt::encrypt($recipient), $body);

            $mailgun = \Mailgun\Mailgun::create($mailConfigs['api_key'], $mailConfigs['endpoint']);

            $messageData = [
                'from'    => $mailFromName . ' <'.$mailFromAddress.'>',
                'to'      => $recipient,
                'subject' => $subject,
                'html'    => $html
            ];

            $attachmentFiles = [];
            // Adicionar anexos
            foreach ($attachments as $attachment) {
                if (isset($attachment['content'])) {
                    // Anexo com conteúdo base64
                    $tempFile = tempnam(sys_get_temp_dir(), 'mailgun_attachment_');
                    file_put_contents($tempFile, base64_decode($attachment['content']));
                    $attachmentFiles[] = [
                        'filePath' => $tempFile,
                        'filename' => $attachment['name'] ?? 'attachment'
                    ];
                } elseif (isset($attachment['path']) && file_exists($attachment['path'])) {
                    // Anexo com caminho de arquivo
                    $attachmentFiles[] = [
                        'filePath' => $attachment['path'],
                        'filename' => $attachment['name'] ?? basename($attachment['path'])
                    ];
                }
            }

            $mailgun->messages()->send($mailConfigs['domain'], $messageData, $attachmentFiles);

            // Limpar arquivos temporários
            foreach ($attachmentFiles as $file) {
                if (strpos($file['filePath'], sys_get_temp_dir()) === 0) {
                    @unlink($file['filePath']);
                }
            }
        }
    }

    /**
     * @param array $mailConfigs
     * @param string $subject
     * @param string $body
     * @param array $recipients
     * @param array $attachments
     * @return void
     */
    protected static function sendEmailWithSendGrid(array $mailConfigs, string $subject, string $body, array $recipients, array $attachments = [])
    {
        $mailFromName    = $mailConfigs['from_name'] ?? config('mail.from.name');
        $mailFromAddress = $mailConfigs['from_address'] ?? config('mail.from.address');

        foreach ($recipients as $recipient)
        {
            $html = str_replace('{{recipient}}', Crypt::encrypt($recipient), $body);

            $email = new \SendGrid\Mail\Mail();
            $email->setFrom($mailFromAddress, $mailFromName);
            $email->setSubject($subject);
            $email->addTo($recipient);
            $email->addContent(
                "text/html", $html
            );

            // Adicionar anexos
            foreach ($attachments as $attachment) {
                if (isset($attachment['content'])) {
                    // Anexo com conteúdo base64
                    $email->addAttachment(
                        $attachment['content'],
                        $attachment['mime'] ?? 'application/octet-stream',
                        $attachment['name'] ?? 'attachment',
                        'attachment'
                    );
                } elseif (isset($attachment['path']) && file_exists($attachment['path'])) {
                    // Anexo com caminho de arquivo
                    $fileContent = base64_encode(file_get_contents($attachment['path']));
                    $mimeType = $attachment['mime'] ?? mime_content_type($attachment['path']) ?? 'application/octet-stream';
                    $fileName = $attachment['name'] ?? basename($attachment['path']);
                    
                    $email->addAttachment(
                        $fileContent,
                        $mimeType,
                        $fileName,
                        'attachment'
                    );
                }
            }

            $sendgrid = new \SendGrid($mailConfigs['api_key'], ['verify_ssl' => config('meanify-laravel-notifications.email.verify_ssl', true)]);
            $sendgrid->send($email);
        }
    }

    /**
     * @param array $mailConfigs
     * @param string $subject
     * @param string $body
     * @param array $recipients
     * @param array $attachments
     * @return void
     */
    protected static function sendEmailWithSendPulse(array $mailConfigs, string $subject, string $body, array $recipients, array $attachments = [])
    {
        $mailFromName    = $mailConfigs['from_name'] ?? config('mail.from.name');
        $mailFromAddress = $mailConfigs['from_address'] ?? config('mail.from.address');

        foreach ($recipients as $recipient)
        {
            $html = str_replace('{{recipient}}', Crypt::encrypt($recipient), $body);

            $apiClient = new ApiClient($mailConfigs['client_id'], $mailConfigs['client_secret'], new FileStorage(storage_path('temp/')));

            $emailData = [
                'html'        => $html,
                'subject'     => $subject,
                'from'        => [
                    'name'  => $mailFromName,
                    'email' => $mailFromAddress,
                ],
                'to'          => [
                    [
                        'email' => $recipient,
                    ]
                ],
            ];

            // Adicionar anexos
            if (!empty($attachments)) {
                $emailData['attachments'] = [];
                foreach ($attachments as $attachment) {
                    if (isset($attachment['content'])) {
                        // Anexo com conteúdo base64
                        $emailData['attachments'][] = [
                            'content' => $attachment['content'],
                            'name' => $attachment['name'] ?? 'attachment',
                            'type' => $attachment['mime'] ?? 'application/octet-stream'
                        ];
                    } elseif (isset($attachment['path']) && file_exists($attachment['path'])) {
                        // Anexo com caminho de arquivo
                        $fileContent = base64_encode(file_get_contents($attachment['path']));
                        $mimeType = $attachment['mime'] ?? mime_content_type($attachment['path']) ?? 'application/octet-stream';
                        $fileName = $attachment['name'] ?? basename($attachment['path']);
                        
                        $emailData['attachments'][] = [
                            'content' => $fileContent,
                            'name' => $fileName,
                            'type' => $mimeType
                        ];
                    }
                }
            }
            
            $result = $apiClient->smtpSendMail($emailData);

            if (isset($result['result']) && $result['result'] === true) {
                //Sent with successfully
            } else {
                throw new \Exception(is_array($result) ? json_encode($result) : (string)$result);
            }
        }
    }


    /**
     * @param \Throwable $e
     * @return void
     */
    public function failed(\Throwable $e): void
    {
        $this->notification->update(['status' => 'failed']);

        Log::emergency('Email notification failed', [
            'notification_id' => $this->notification->id,
            'exception' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ],
        ]);
    }
}
