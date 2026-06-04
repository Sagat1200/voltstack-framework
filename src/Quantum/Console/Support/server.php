<?php

declare(strict_types=1);

$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? getcwd();
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = urldecode((string) parse_url($requestUri, PHP_URL_PATH));
$requestPath = $requestPath === '' ? '/' : $requestPath;
$target = $documentRoot . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $requestPath), DIRECTORY_SEPARATOR);

if ($requestPath !== '/' && is_file($target)) {
    return false;
}

require $documentRoot . DIRECTORY_SEPARATOR . 'index.php';
