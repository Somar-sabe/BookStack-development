<?php

/**
 * Mail configuration options.
 *
 * Changes to these config files are not supported by BookStack and may break upon updates.
 * Configuration should be altered via the `.env` file or environment variables.
 * Do not edit this file unless you're happy to maintain any changes yourself.
 */

return [

    // Mail driver to use.
    // From Laravel 7+ this is MAIL_MAILER in laravel.
    // Kept as MAIL_DRIVER in BookStack to prevent breaking change.
    // Options: smtp, sendmail, log, array
    'default' => env('MAIL_DRIVER', 'smtp'),

    // Global "From" address & name
    'from' => [
        'address' => env('MAIL_FROM', 'mail@bookstackapp.com'),
        'name'    => env('MAIL_FROM_NAME', 'BookStack'),
    ],

    // Mailer Configurations
    // Available mailing methods and their settings.
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_COMMAND', '/usr/sbin/sendmail -bs'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],
    ],

    // Email markdown configuration
    'markdown' => [
        'theme' => 'default',
        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],
];