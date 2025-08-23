<?php

// config for LovaszCC/LaravelInnvoice
return [
    'username' => env('INNVOICE_USERNAME'),
    'password' => env('INNVOICE_PASSWORD'),
    'company_name' => env('INNVOICE_COMPANY_NAME'),
    'checkbook_id' => env('INNVOICE_CHECKBOOK_ID'),
    'storage_path' => env('INNVOICE_STORAGE_PATH', 'app/public/innvoice'),
];
