<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers.php';
require_once __DIR__ . '/../auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/pages/');
}

verifyCsrf();

$pageId = (int)($_POST['id'] ?? 0);
if (!$pageId) {
    flash('error', 'Invalid page ID.');
    redirect('admin/pages/');
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT title FROM pages WHERE id = ? LIMIT 1');
    $stmt->execute([$pageId]);
    $pg   = $stmt->fetch();

    if (!$pg) {
        flash('error', 'Page not found.');
        redirect('admin/pages/');
    }

    $pdo->prepare('DELETE FROM pages WHERE id = ?')->execute([$pageId]);
    flash('success', 'Page "' . $pg['title'] . '" deleted.');
} catch (PDOException $e) {
    error_log('[AdminDeletePage] ' . $e->getMessage());
    flash('error', 'Failed to delete page.');
}

redirect('admin/pages/');
