<?php
declare(strict_types=1);

/**
 * Middleware to require specific roles.
 * Redirects if not logged in or role not allowed.
 *
 * @param array $allowedRoles e.g., ['user'], ['admin'], or ['user', 'admin']
 * @param string $redirectUrl Where to redirect on failure
 */
function requireRole(array $allowedRoles, string $redirectUrl = '../authenticator/login.php'): void {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: $redirectUrl");
        exit;
    }
    $userRole = (string)($_SESSION['role'] ?? 'user');
    if (!in_array($userRole, $allowedRoles)) {
        header("Location: ../../index.php?error=access_denied");
        exit;
    }
}

/**
 * Check if user has admin role.
 * @return bool
 */
function isAdmin(): bool {
    return (string)($_SESSION['role'] ?? 'user') === 'admin';
}

?>