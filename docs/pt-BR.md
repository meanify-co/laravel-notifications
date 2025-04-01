<p align="center">
  <a href="https://www.meanify.co?from=github&lib=laravel-payment-hub">
    <img src="https://meanify.co/assets/core/img/logo/png/meanify_color_dark_horizontal_02.png" width="200" alt="Meanify Logo" />
  </a>
</p>


# Laravel Notifications


Um sistema de notificações poderoso e extensível para Laravel que unifica **e-mail**, **in-app** e **notificações em tempo real** (broadcast).

> ✅ Suporte completo a templates dinâmicos, layouts personalizados, traduções, SMTP dinâmico e canais seguros com IDs mascarados.

---

## 📦 Instalação

```bash
composer require meanify-co/laravel-notifications
```

---

## 🚀 Publicação dos Recursos

```bash
php artisan vendor:publish --provider="Meanify\LaravelNotifications\Providers\MeanifyLaravelNotificationServiceProvider"
```

Isso irá publicar:

- `config/meanify-laravel-notifications.php`
- Migrations
- Seeders (opcional)
- Views (layouts Blade para e-mail)

---

## ⚙️ Configuração

Edite `config/meanify-laravel-notifications.php` para personalizar:

- Nomes das filas
- Layout padrão para e-mail
- Tentativas e delay de e-mail
- Prefixo dos canais de broadcast

---

## 🧱 Estrutura do Banco de Dados

Este pacote usa as seguintes tabelas:

- `emails_layouts`
- `notifications_templates`
- `notifications_templates_translations`
- `notifications_templates_variables`
- `notifications`

Execute as migrations:

```bash
php artisan migrate
```

---

## 🌱 Seeders

Opcionalmente, popule o banco com templates e layouts padrão:

```bash
php artisan db:seed --class="\Meanify\LaravelNotifications\Database\Seeders\EmailLayoutSeeder"
php artisan db:seed --class="\Meanify\LaravelNotifications\Database\Seeders\NotificationTemplateSeeder"
```

---

## 💡 Uso com Helper

```php
meanify_notifications()
    ->to($user)
    ->locale('pt_BR')
    ->onAccount($accountId)
    ->onApplication($appId)
    ->onSession($sessionId)
    ->email($smtpConfigs, $recipients, 'sign_in_code')
    ->with(['code' => '123456'])
    ->send();
```

---

## 🧪 Comando Artisan de Teste

```bash
php artisan meanify:notifications:test \
    --template=sign_in_code \
    --user=1 \
    --emails=usuario@email.com \
    --vars='{"code": "123456"}'
```

Opções:

- `--template=` Chave do template
- `--locale=` Idioma (pt-BR ou en-US)
- `--user=` ID do usuário
- `--emails=` Lista de e-mails separados por vírgula
- `--smtp=` ID das configurações SMTP (do seu banco)
- `--account=`, `--application=`, `--session=` Contexto opcional
- `--vars=` JSON com variáveis dinâmicas

---

## ✨ Templates Dinâmicos

- Templates salvos no banco de dados com traduções por idioma.
- Use `{{ nome_variavel }}` no assunto, corpo ou mensagem curta.
- In-app é usado para mensagens breves (ex: toast ou dropdown).

---

## 🎨 Layouts

Cada template pode usar um layout HTML salvo no banco com o marcador `{{ $content }}`.

Se não houver, o fallback será:

```html
<html>
  <body>
    {{ $content }}
  </body>
</html>
```

---

## 📡 Notificações em Tempo Real

Habilite o Laravel Reverb e use:

```php
broadcast(new InAppNotificationCreated($notification))
```

Cada canal tem o formato:

```
mfy_channel_{base64(ModelClass::obfuscated_id)}
```

Use o helper:

```php
ChannelBuilder::makeChannel(User::class, $user)
```

> Usa `meanify/laravel-obfuscator` para mascaramento seguro.

---

## 🛠️ Configuração do Supervisor

Crie um worker para cada fila:

### notifications.conf

```ini
[program:meanify-notifications-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --queue=meanify_queue_notification --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/supervisor-notifications.log
stopwaitsecs=3600
```

Repita para:

- `meanify_queue_notification_emails`
- `meanify_queue_notification_in_app`

---

## ✅ Canais Suportados

- [x] E-mail
- [x] In-App
- [x] Laravel Reverb (Broadcast)

---

## 🧰 Customização

- SMTP dinâmico por notificação (criptografado)
- Broadcast seguro com Obfuscator + base64
- Crie novos templates via painel ou seeders

---

## 📄 Licença

MIT © [Meanify](https://meanify.co)

