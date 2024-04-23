<?php

require_once __DIR__ . '/../vendor/autoload.php';
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

$config = require_once __DIR__ . '/../config.php';

$bucket = $config['aws_s3_credentials']['bucket'];
$region = $config['aws_s3_credentials']['region'];
$accessKey = $config['aws_s3_credentials']['key'];
$secretKey = $config['aws_s3_credentials']['secret'];

$s3 = new S3Client([
    'version' => 'latest',
    'region' => $region,
    'credentials' => [
        'key' => $accessKey,
        'secret' => $secretKey,
    ],
]);

$expiration = $config['expiration']; // Expiration in minutes
$timestamp = strtotime("-$expiration minutes");

try {
    $objects = $s3->listObjects([
        'Bucket' => $bucket,
    ]);

    if (isset($objects['Contents'])){ 
        foreach ($objects['Contents'] as $object) {
            if (strtotime($object['LastModified']) < $timestamp) {
                $s3->deleteObject([
                    'Bucket' => $bucket,
                    'Key' => $object['Key'],
                ]);
                echo "Deleted object: " . $object['Key'] . PHP_EOL;
            }
        }
    }
} catch (AwsException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
