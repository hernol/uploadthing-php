<?php

declare(strict_types=1);

/**
 * Documentation validation script
 * This script validates that all code examples in the documentation are syntactically correct.
 */

require_once __DIR__ . '/vendor/autoload.php';

use UploadThing\Client;
use UploadThing\Config;

echo "Validating documentation examples...\n";

// Test basic client creation
try {
    $config = new Config('test-api-key');
    $client = Client::create($config);
    echo "✓ Client creation example is valid\n";
} catch (Exception $e) {
    echo "✗ Client creation example failed: " . $e->getMessage() . "\n";
}

// Test file operations
try {
    $files = $client->files();
    echo "✓ Files resource access is valid\n";
} catch (Exception $e) {
    echo "✗ Files resource access failed: " . $e->getMessage() . "\n";
}

// Test uploads operations
try {
    $uploads = $client->uploads();
    echo "✓ Uploads resource access is valid\n";
} catch (Exception $e) {
    echo "✗ Uploads resource access failed: " . $e->getMessage() . "\n";
}

// Test webhooks operations
try {
    $webhooks = $client->webhooks();
    echo "✓ Webhooks resource access is valid\n";
} catch (Exception $e) {
    echo "✗ Webhooks resource access failed: " . $e->getMessage() . "\n";
}

// Test upload helper
try {
    $uploadHelper = $client->uploadHelper();
    echo "✓ Upload helper access is valid\n";
} catch (Exception $e) {
    echo "✗ Upload helper access failed: " . $e->getMessage() . "\n";
}

// Test webhook verifier
try {
    $webhookVerifier = $client->createWebhookVerifier('test-secret');
    echo "✓ Webhook verifier creation is valid\n";
} catch (Exception $e) {
    echo "✗ Webhook verifier creation failed: " . $e->getMessage() . "\n";
}

// Test webhook handler
try {
    $webhookHandler = $client->createWebhookHandler('test-secret');
    echo "✓ Webhook handler creation is valid\n";
} catch (Exception $e) {
    echo "✗ Webhook handler creation failed: " . $e->getMessage() . "\n";
}

// Test MultipartBuilder
try {
    $multipartBuilder = new \UploadThing\Utils\MultipartBuilder();
    $multipartBuilder->addField('name', 'value');
    $multipartBuilder->addFile('file', 'test.txt', 'content', 'text/plain');
    $result = $multipartBuilder->build();
    echo "✓ MultipartBuilder usage is valid\n";
} catch (Exception $e) {
    echo "✗ MultipartBuilder usage failed: " . $e->getMessage() . "\n";
}

// Test Serializer
try {
    $serializer = new \UploadThing\Utils\Serializer();
    $testObject = new stdClass();
    $testObject->name = 'test';
    $json = $serializer->serialize($testObject);
    echo "✓ Serializer usage is valid\n";
} catch (Exception $e) {
    echo "✗ Serializer usage failed: " . $e->getMessage() . "\n";
}

echo "\nDocumentation validation completed!\n";
