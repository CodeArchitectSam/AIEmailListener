<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mailbox driver
    |--------------------------------------------------------------------------
    |
    | fake: reads sample emails seeded into the fake_mailbox_emails table.
    | imap: reads a real mailbox via the PHP imap extension.
    |
    */

    'driver' => env('MAILBOX_DRIVER', 'fake'),

    'imap' => [
        'host' => env('IMAP_HOST', '127.0.0.1'),
        'port' => (int) env('IMAP_PORT', 993),
        'username' => env('IMAP_USERNAME'),
        'password' => env('IMAP_PASSWORD'),
        'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
        'validate_cert' => (bool) env('IMAP_VALIDATE_CERT', true),
        'folder' => env('IMAP_FOLDER', 'INBOX'),
    ],

];