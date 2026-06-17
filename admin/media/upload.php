<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

if (empty($_FILES['image']['name'])) {
    echo json_encode(['error' => 'No file received.']);
    exit;
}

$file = $_FILES['image'];

// Error check
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errMap = [
        UPLOAD_ERR_INI_SIZE  => 'File exceeds server size limit.',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit.',
        UPLOAD_ERR_PARTIAL   => 'File only partially uploaded.',
        UPLOAD_ERR_NO_TMP_DIR=> 'No temporary directory.',
        UPLOAD_ERR_CANT_WRITE=> 'Cannot write to disk.',
    ];
    echo json_encode(['error' => $errMap[$file['error']] ?? 'Upload error.']);
    exit;
}

// Size
if ($file['size'] > MAX_UPLOAD_SIZE) {
    echo json_encode(['error' => 'File too large. Max ' . round(MAX_UPLOAD_SIZE / 1048576) . ' MB.']);
    exit;
}

// Extension
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_EXT, true)) {
    echo json_encode(['error' => 'Invalid type. Allowed: JPG, PNG, GIF.']);
    exit;
}

// MIME via finfo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
if (!in_array($mime, ALLOWED_MIME, true)) {
    echo json_encode(['error' => 'File content does not match an allowed image type.']);
    exit;
}

// Ensure directory exists
if (!is_dir(UPLOAD_DIR_PAGES) && !mkdir(UPLOAD_DIR_PAGES, 0755, true)) {
    echo json_encode(['error' => 'Upload directory could not be created.']);
    exit;
}

// Safe filename
$newName = 'page_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest    = UPLOAD_DIR_PAGES . $newName;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['error' => 'Failed to save file. Check directory permissions.']);
    exit;
}

echo json_encode([
    'url'      => UPLOAD_URL_PAGES . $newName,
    'filename' => $newName,
    'size'     => round($file['size'] / 1024) . ' KB',
]);
