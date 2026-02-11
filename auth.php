<?php
session_start();
require_once __DIR__ . '/config.php';

/**
 * Attempt to log in a user
 */
function login($username, $password)
{
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
    } catch (PDOException $e) {
        // Log error
    }
    return false;
}

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is superadmin
 */
function isSuperAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
}

/**
 * Logout user
 */
function logout()
{
    session_destroy();
    header("Location: login.php");
    exit;
}

/**
 * Redirect if not logged in
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}
?>