<?php
$path = __DIR__ . '/keep.import';
if (file_exists($path)) {
    header('Content-Type: image/jpeg');
    readfile($path);
    exit;
} else {
    http_response_code(404);
    echo 'Image not found.';
} 