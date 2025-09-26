# Exemplo de Uso - Anexos em Emails

Este documento demonstra como usar a nova funcionalidade de anexos em emails na biblioteca Meanify Laravel Notifications.

## Como usar

### Exemplo básico com arquivo local

```php
use Meanify\LaravelNotifications\Support\NotificationBuilder;

// Configurar a notificação com anexos
NotificationBuilder::make('welcome-email', $user, 'pt_BR')
    ->forEmail('smtp', $smtpConfigs, ['user@example.com'])
    ->with(['name' => 'João', 'company' => 'Minha Empresa'])
    ->withAttachments([
        [
            'path' => '/path/to/document.pdf',
            'name' => 'Documento.pdf',
            'mime' => 'application/pdf'
        ],
        [
            'path' => '/path/to/image.jpg',
            'name' => 'Imagem.jpg'
        ]
    ])
    ->send();
```

### Exemplo com conteúdo base64

```php
// Anexo usando conteúdo base64 (útil para arquivos gerados dinamicamente)
$pdfContent = base64_encode($generatedPdfContent);

NotificationBuilder::make('invoice-email', $user, 'pt_BR')
    ->forEmail('sendgrid', $sendgridConfigs, ['client@example.com'])
    ->with(['invoice_number' => '12345'])
    ->withAttachments([
        [
            'content' => $pdfContent,
            'name' => 'Fatura-12345.pdf',
            'mime' => 'application/pdf'
        ]
    ])
    ->send();
```

### Exemplo com múltiplos anexos e diferentes drivers

```php
// Funciona com todos os drivers: smtp, mailgun, sendgrid, sendpulse
NotificationBuilder::make('report-email', $user, 'en_US')
    ->forEmail('mailgun', $mailgunConfigs, ['manager@example.com'])
    ->with(['report_date' => '2024-03-25'])
    ->withAttachments([
        [
            'path' => storage_path('reports/monthly-report.xlsx'),
            'name' => 'Monthly Report March 2024.xlsx',
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ],
        [
            'path' => storage_path('reports/charts.png'),
            'name' => 'Charts.png',
            'mime' => 'image/png'
        ],
        [
            'content' => base64_encode($summaryPdfContent),
            'name' => 'Summary.pdf',
            'mime' => 'application/pdf'
        ]
    ])
    ->send();
```

## Estrutura do array de anexos

Cada anexo deve ser um array com as seguintes chaves:

### Para arquivos locais:

- `path` (string, obrigatório): Caminho para o arquivo
- `name` (string, opcional): Nome do arquivo no email (padrão: basename do path)
- `mime` (string, opcional): Tipo MIME (será detectado automaticamente se não fornecido)

### Para conteúdo base64:

- `content` (string, obrigatório): Conteúdo do arquivo codificado em base64
- `name` (string, opcional): Nome do arquivo no email (padrão: 'attachment')
- `mime` (string, opcional): Tipo MIME (padrão: 'application/octet-stream')

## Limitações e considerações

1. **Tamanho dos arquivos**: Verifique os limites de tamanho do seu provedor de email:

   - SMTP: Depende da configuração do servidor
   - Mailgun: Até 25MB por email
   - SendGrid: Até 30MB por email
   - SendPulse: Verifique a documentação da API

2. **Tipos de arquivo**: Todos os tipos de arquivo são suportados, mas alguns provedores podem bloquear certos tipos por segurança.

3. **Performance**: Anexos grandes podem impactar a performance. Para arquivos muito grandes, considere usar links para download em vez de anexos.

4. **Armazenamento temporário**: O sistema pode criar arquivos temporários durante o processamento (especialmente para Mailgun), que são automaticamente removidos após o envio.

## Drivers suportados

Todos os drivers de email da biblioteca suportam anexos:

- ✅ SMTP
- ✅ Mailgun
- ✅ SendGrid
- ✅ SendPulse

## Tratamento de erros

Se um arquivo não existir ou não puder ser lido, o anexo será ignorado silenciosamente para não interromper o envio do email. Logs de erro serão gerados conforme necessário.
