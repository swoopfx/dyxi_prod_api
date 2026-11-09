<?php

declare(strict_types=1);

$defaultKeyFile = dirname(__DIR__, 2) . '/data/dyxi-502320-ec4ecc7ca00d.json';
if (!file_exists($defaultKeyFile)) {
    $altKeyFile = dirname(__DIR__, 2) . '/data/keys/dyxi-502320-ec4ecc7ca00d.json';
    if (file_exists($altKeyFile)) {
        $defaultKeyFile = $altKeyFile;
    }
}

return [
    'bigquery' => [
        'project_id'   => getenv('BIGQUERY_PROJECT_ID') ?: 'dyxi-502320',
        'dataset_id'   => getenv('BIGQUERY_DATASET_ID') ?: 'dyxi_api_pre_prod',
        'key_file'     => getenv('BIGQUERY_KEY_FILE') ?: $defaultKeyFile,
        'client_email' => getenv('BIGQUERY_CLIENT_EMAIL') ?: 'dyxi-big-query-pre-prod@dyxi-502320.iam.gserviceaccount.com',
    ],
];

