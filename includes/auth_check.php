<?php
// includes/auth_check.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require user to be logged in.
 */
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /capstone-repo/public/login.php');
        exit;
    }
}

/**
 * Require a single role.
 */
function require_role(string $role): void {
    require_login();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        http_response_code(403);
        echo 'Forbidden: insufficient privileges';
        exit;
    }
}

/**
 * Require one of multiple roles.
 */
function require_roles(array $roles): void {
    require_login();
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        echo 'Forbidden: insufficient privileges';
        exit;
    }
}

/**
 * Helper to get current user id
 */
function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}
?>