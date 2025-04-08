<?php

namespace Meanify\LaravelNotifications\Services;

class MailDriverService
{
    protected $driver;
    protected $config;
    
    public static function getParams(string $driver, array $configs)
    {
        return match ($driver) {
            'smtp' => [
                'host'         => $configs['smtp_host'],
                'port'         => $configs['smtp_port'],
                'username'     => $configs['smtp_username'],
                'password'     => $configs['smtp_password'],
                'encryption'   => $configs['smtp_encryption'],
                'from_address' => $configs['from_address'],
                'from_name'    => $configs['from_name'],
            ],
            'sendgrid'  => [
                'api_key'  => $configs['sendgrid_api_key'],
                'endpoint' => $configs['sendgrid_endpoint'],
                'from_address' => $configs['from_address'],
                'from_name'    => $configs['from_name'],
            ],
            'sendpulse' => [
                'client_id'     => $configs['sendpulse_client_id'],
                'client_secret' => $configs['sendpulse_client_secret'],
                'endpoint'      => $configs['sendpulse_endpoint'],
                'from_address' => $configs['from_address'],
                'from_name'    => $configs['from_name'],
            ],
            'mailgun'   => [
                'domain'   => $configs['mailgun_domain'],
                'api_key'  => $configs['mailgun_api_key'],
                'endpoint' => $configs['mailgun_endpoint'],
                'from_address' => $configs['from_address'],
                'from_name'    => $configs['from_name'],
            ],
            default => throw new InvalidArgumentException("Invalid mail driver: {$driver}"),
        };
    }
}