<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/users/');
}

verifyCsrf();

$userId = (int)($_POST['id'] ?? 0);

if (!$userId) {
    flash('error', 'Invalid user ID.');
    redirect('admin/users/');
}

// Prevent self-deletion
if ($userId === (int)$_SESSION['user_id']) {
    flash('error', 'You cannot delete your own account.');
    redirect('admin/users/');
}

try {
    $pdo = getDB();

    // Fetch to get image for cleanup
    $stmt = $pdo->prepare('SELECT profile_image, username FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        flash('error', 'User not found.');
        redirect('admin/users/');
    }

    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);

    // Clean up uploaded image
    deleteProfileImage($user['profile_image']);

    flash('success', 'User "' . $user['username'] . '" deleted.');
} catch (PDOException $e) {
    error_log('[AdminDeleteUser] ' . $e->getMessage());
    flash('error', 'Failed to delete user.');
}

redirect('admin/users/');
