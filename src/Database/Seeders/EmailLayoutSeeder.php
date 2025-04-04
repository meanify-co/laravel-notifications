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
                'name'     => 'Main dynamic layout for emails',
                'metadata' => json_encode([
                    'logo_url'     => env('APP_EMAIL_SRC'),
                    'logo_width'   => env('APP_EMAIL_WIDTH'),
                    'logo_height'  => env('APP_EMAIL_HEIGHT'),
                    'short_cta'    => env('APP_EMAIL_SHORT_CTA','Click here'),
                    'cta_help'     => env('APP_EMAIL_CTA_HELP','to be redirected if the button has not appeared.'),
                    'help_text'    => env('APP_EMAIL_HELP_TEXT','This is an automatic email. Please do not reply to this message.'),
                    'social_links' => [
                        'facebook'  => env('APP_EMAIL_SOCIAL_LINK_FACEBOOK','https://facebook.com/meanify.tech'),
                        'linkedin'  => env('APP_EMAIL_SOCIAL_LINK_LINKEDIN','https://linkedin.com/company/meanify-tech'),
                        'instagram' => env('APP_EMAIL_SOCIAL_LINK_INSTAGRAM','https://instagram.com/meanify.tech'),
                        'youtube'   => env('APP_EMAIL_SOCIAL_LINK_YOUTUBE','https://youtube.com/@Meanify-tech'),
                    ],
                    'privacy_url'      => env('APP_EMAIL_PRIVACY_URL',''), //E.g.: 'https://meanify.co/terms/privacy',
                    'privacy_text'     => env('APP_EMAIL_PRIVACY_TEXT',''), //E.g.: 'Privacy Policy',
                    'unsubscribe_url'  => env('APP_EMAIL_UNSUBSCRIBE_URL',''), //E.g.: 'https://meanify.co/email/unsubscribe?origin={email}',
                    'unsubscribe_text' => env('APP_EMAIL_UNSUBSCRIBE_TEXT',''), //E.g.: 'Unsubscribe',
                ]),
                'blade_template' => file_get_contents(base_path('vendor/meanify-co/laravel-notifications/src/Resources/views/emails-layouts/default.blade.php')),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]
        );
    }
}
