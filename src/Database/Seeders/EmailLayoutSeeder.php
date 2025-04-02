<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailLayoutSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('emails_layouts')->updateOrInsert(
            ['key' => 'default'],
            [
                'name' => 'Main dynamic layout for emails',
                'metadata' => json_encode([
                    'logo_url' => null,
                    'logo_width' => null,
                    'logo_height' => null,
                    'short_cta' => 'Click here',
                    'cta_help' => 'to be redirected if the button has not appeared.',
                    'help_text' => 'This is an automatic email. Please do not reply to this message.',
                    'social_links' => [
                        'facebook' => 'https://facebook.com/meanify.tech',
                        'linkedin' => 'https://linkedin.com/company/meanify-tech',
                        'instagram' => 'https://instagram.com/meanify.tech',
                        'youtube' => 'https://youtube.com/@Meanify-tech',
                    ],
                    'privacy_url'  => 'https://meanify.co/terms/privacy',
                    'privacy_text' => 'Privacy Policy',
                    'unsubscribe_url' => 'https://meanify.co/email/unsubscribe?origin={{recipient}}',
                    'unsubscribe_text' => 'Unsubscribe',
                ]),
                'blade_template' => file_get_contents(base_path('vendor/meanify-co/laravel-notifications/src/Resources/views/emails-layouts/default.blade.php')),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
