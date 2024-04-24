<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/actions/aws.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$config = require_once __DIR__ . '/config.php';
$awsBucket = $config['aws_s3_credentials']['bucket'];
$awsRegion = $config['aws_s3_credentials']['region'];
$awsAccessKey = $config['aws_s3_credentials']['key'];
$awsSecretKey = $config['aws_s3_credentials']['secret'];

$request = Request::createFromGlobals();
$response = new Response();

function deleteTempFile($tempFilePath) {
    if (file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }
}

if ($request->isMethod('PUT') || ($request->isMethod('POST') && $request->headers->get('Content-Type') === 'application/octet-stream')) {
    $uniqueID = uniqid();
    $fileExtension = pathinfo($request->getRequestUri(), PATHINFO_EXTENSION);
    $fileName = $uniqueID . '.' . $fileExtension;
    $uploadsDir = $config['upload_directory'];
    $tempFilePath = $uploadsDir . '/' . $fileName;
    register_shutdown_function('deleteTempFile', $tempFilePath);

    $fileHandle = fopen($tempFilePath, 'w');

    if ($fileHandle === false) {
        $response->setContent('Failed to open file for writing.');
        $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
    } else {
        $inputStream = fopen('php://input', 'r');
        while (!feof($inputStream)) {
            fwrite($fileHandle, fread($inputStream, 8192)); // 8192 bytes = 8 KB
        }
        fclose($fileHandle);

        $fileSize = filesize($tempFilePath);
        $uploadResult = uploadToS3($tempFilePath, $fileName, $awsBucket, $awsRegion, $awsAccessKey, $awsSecretKey);

        if ($uploadResult !== false) {
            $responseBody = "======================\n";
            $responseBody .= "\n";
            $responseBody .= "Uploaded 1 file: " . $fileName . " " . $fileSize . " Bytes\n";
            $responseBody .= "\n";
            $responseBody .= "wget ".$config['base_url']."/".$fileName." \n";
            $responseBody .= "\n";
            $responseBody .= "======================\n";
            $response->setContent($responseBody);
            $response->setStatusCode(Response::HTTP_OK);
        } else {
            $response->setContent('Failed to upload file to AWS S3.');
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        unlink($tempFilePath); // Remove the temporary file
    }
} elseif ($request->isMethod('GET') && $request->getPathInfo() !== '/') {
    $fileName = basename($request->getPathInfo());
    $downloadUrl = generatePresignedUrl($fileName, $awsBucket, $awsRegion, $awsAccessKey, $awsSecretKey);

    if ($downloadUrl !== false) {
        header('Location: ' . $downloadUrl);
        exit;
    } else {
        $response->setContent('Error generating pre-signed URL for file download.');
        $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
} else {
    $response->setContent('Invalid request method or content type.');
    $response->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
}

echo $response->getContent();
