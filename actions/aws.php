<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

function uploadToS3($filePath, $fileName, $bucketName, $region, $accessKey, $secretKey) {
    $s3 = new S3Client([
        'version' => 'latest',
        'region' => $region,
        'credentials' => [
            'key' => $accessKey,
            'secret' => $secretKey,
        ],
    ]);

    try {
        $result = $s3->putObject([
            'Bucket' => $bucketName,
            'Key' => $fileName,
            'Body' => fopen($filePath, 'rb'),
            'ACL' => 'private',
        ]);

        return $result['ObjectURL'];
    } catch (AwsException $e) {
        echo $e;
        return false;
    }
}

function generatePresignedUrl($fileName, $bucketName, $region, $accessKey, $secretKey) {
    $s3 = new S3Client([
        'version' => 'latest',
        'region' => $region,
        'credentials' => [
            'key' => $accessKey,
            'secret' => $secretKey,
        ],
    ]);

    try {
        $command = $s3->getCommand('GetObject', [
            'Bucket' => $bucketName,
            'Key' => $fileName,
        ]);

        $request = $s3->createPresignedRequest($command, '+15 minutes');

        return (string) $request->getUri();
    } catch (AwsException $e) {
        return false;
    }
}
