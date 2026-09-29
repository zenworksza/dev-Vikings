<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application document encryption
    |--------------------------------------------------------------------------
    |
    | Franchisee-application documents are encrypted in the app (AES-256-GCM)
    | BEFORE they are uploaded, so the S3 bucket only ever holds ciphertext and
    | the storage provider never sees the plaintext or this key. The key is
    | separate from APP_KEY so rotating one does not affect the other.
    |
    | Generate: php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
    |
    | KEEP A BACKUP OF THIS KEY OFF THE SERVER (password manager). If it is
    | lost, every stored document is unrecoverable.
    |
    | Required in production. Outside production it falls back to APP_KEY so
    | local development needs no extra setup.
    |
    */

    'encryption_key' => env('DOCUMENTS_ENCRYPTION_KEY'),

    'cipher' => 'AES-256-GCM',

];
