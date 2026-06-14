<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/fields/');
}

verifyCsrf();

$fieldId = (int)($_POST['id'] ?? 0);

if (!$fieldId) {
    flash('error', 'Invalid field ID.');
    redirect('admin/fields/');
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT field_label FROM profile_fields WHERE id = ? LIMIT 1');
    $stmt->execute([$fieldId]);
    $field = $stmt->fetch();

    if (!$field) {
        flash('error', 'Field not found.');
        redirect('admin/fields/');
    }

    // FK ON DELETE CASCADE handles user_field_values cleanup automatically
    $pdo->prepare('DELETE FROM profile_fields WHERE id = ?')->execute([$fieldId]);

    flash('success', 'Field "' . $field['field_label'] . '" and all associated user data deleted.');
} catch (PDOException $e) {
    error_log('[AdminDeleteField] ' . $e->getMessage());
    flash('error', 'Failed to delete field.');
}

redirect('admin/fields/');
