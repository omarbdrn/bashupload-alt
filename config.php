<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment variables from .env file
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');
$uploadDirectory = __DIR__ . '/uploads';

return [
    'base_url' => $_ENV['BASE_URL'],
    'upload_directory' => $uploadDirectory,
    'expiration' => 15, // Minutes
    'max_downloads' => 1,
    'aws_s3_credentials' => [
        'key' => $_ENV['AWS_ACCESS_KEY_ID'],
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
        'bucket' => $_ENV['AWS_S3_BUCKET'],
        'region' => $_ENV['AWS_S3_REGION'],
    ],
];
