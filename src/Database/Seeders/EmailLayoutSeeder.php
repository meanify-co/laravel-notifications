<?php

namespace Meanify\LaravelNotifications\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailLayoutSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('emails_layouts')->updateOrInsert(
            ['key' => 'default'],
            [
                'name' => 'Default layout',
                'metadata' => json_encode([
                    'primary_color' => '#0047ab',
                    'font_family' => 'Arial, sans-serif',
                    'header_title' => 'App Notification',
                    'footer_text' => '© 2025 Meanify. All rights reserved.'
                ]),
                'blade_template' => <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>{{ \$subject ?? 'Email' }}</title>
    <style>
        body {
            font-family: {{ \$font_family ?? 'sans-serif' }};
            background-color: #f4f4f4;
            padding: 32px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 24px;
            border-radius: 8px;
        }
        .header h1 {
            margin: 0 0 16px;
            color: {{ \$primary_color ?? '#333' }};
        }
        .footer {
            margin-top: 32px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ \$header_title ?? 'Notificação' }}</h1>
        </div>

        <div class="content">
            {!! \$content !!}
        </div>

        <div class="footer">
            <p>{{ \$footer_text ?? 'Este é um e-mail automático. Não responda.' }}</p>
        </div>
    </div>
</body>
</html>
HTML
            ]
        );
    }
}
