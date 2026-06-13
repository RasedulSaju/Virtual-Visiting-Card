<?php
declare(strict_types=1);
/**
 * admin/auth_check.php
 * Include at the top of every admin file AFTER helpers.php is loaded.
 * Redirects to login if the current session is not an active admin.
 */
if (!isLoggedIn() || !isAdmin()) {
    flash('error', 'Admin access required.');
    redirect('login');
}
