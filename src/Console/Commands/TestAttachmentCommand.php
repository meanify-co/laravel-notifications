<?php

namespace Meanify\LaravelNotifications\Console\Commands;

use Illuminate\Console\Command;
use Meanify\LaravelNotifications\Support\NotificationBuilder;

class TestAttachmentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meanify:test-attachment
                            {email : Email address to send test}
                            {--driver=smtp : Mail driver to use (smtp, mailgun, sendgrid, sendpulse)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email notifications with attachments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $driver = $this->option('driver');

        $this->info("Testing email notification with attachments...");
        $this->info("Email: {$email}");
        $this->info("Driver: {$driver}");

        // Criar um arquivo de teste temporário
        $testContent = "Este é um arquivo de teste para demonstrar anexos em emails.\n\nData: " . now()->format('Y-m-d H:i:s');
        $tempFile = tempnam(sys_get_temp_dir(), 'meanify_test_');
        file_put_contents($tempFile, $testContent);

        // Configurações básicas para cada driver (você deve ajustar conforme necessário)
        $configs = $this->getDriverConfigs($driver);

        if (empty($configs)) {
            $this->error("Configurações para o driver '{$driver}' não foram encontradas.");
            $this->info("Configure as variáveis de ambiente apropriadas para o driver escolhido.");
            return 1;
        }

        try {
            // Preparar anexos de teste
            $attachments = [
                [
                    'path' => $tempFile,
                    'name' => 'arquivo-teste.txt',
                    'mime' => 'text/plain'
                ],
                [
                    'content' => base64_encode('Conteúdo de anexo base64'),
                    'name' => 'anexo-base64.txt',
                    'mime' => 'text/plain'
                ]
            ];

            // Enviar notificação com anexos
            $result = NotificationBuilder::make('test-template', null, 'pt_BR')
                ->forEmail($driver, $configs, [$email], true) // true = enviar imediatamente
                ->with([
                    'user_name' => 'Usuário Teste',
                    'test_date' => now()->format('d/m/Y H:i:s')
                ])
                ->withAttachments($attachments)
                ->send();

            if ($result) {
                $this->info("✅ Email com anexos enviado com sucesso!");
            } else {
                $this->error("❌ Falha ao enviar email.");
            }

        } catch (\Exception $e) {
            $this->error("❌ Erro ao enviar email: " . $e->getMessage());
            return 1;
        } finally {
            // Limpar arquivo temporário
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }

        return 0;
    }

    /**
     * Get driver configurations from environment
     */
    private function getDriverConfigs(string $driver): array
    {
        return match ($driver) {
            'smtp' => [
                'smtp_host' => env('MAIL_HOST'),
                'smtp_port' => env('MAIL_PORT', 587),
                'smtp_username' => env('MAIL_USERNAME'),
                'smtp_password' => env('MAIL_PASSWORD'),
                'smtp_encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'from_address' => env('MAIL_FROM_ADDRESS'),
                'from_name' => env('MAIL_FROM_NAME'),
            ],
            'mailgun' => [
                'mailgun_domain' => env('MAILGUN_DOMAIN'),
                'mailgun_api_key' => env('MAILGUN_SECRET'),
                'mailgun_endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
                'from_address' => env('MAIL_FROM_ADDRESS'),
                'from_name' => env('MAIL_FROM_NAME'),
            ],
            'sendgrid' => [
                'sendgrid_api_key' => env('SENDGRID_API_KEY'),
                'sendgrid_endpoint' => env('SENDGRID_ENDPOINT'),
                'from_address' => env('MAIL_FROM_ADDRESS'),
                'from_name' => env('MAIL_FROM_NAME'),
            ],
            'sendpulse' => [
                'sendpulse_client_id' => env('SENDPULSE_CLIENT_ID'),
                'sendpulse_client_secret' => env('SENDPULSE_CLIENT_SECRET'),
                'sendpulse_endpoint' => env('SENDPULSE_ENDPOINT'),
                'from_address' => env('MAIL_FROM_ADDRESS'),
                'from_name' => env('MAIL_FROM_NAME'),
            ],
            default => []
        };
    }
}
