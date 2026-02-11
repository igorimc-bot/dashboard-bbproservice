<?php
session_start();
require_once __DIR__ . '/config.php';

/**
 * Generate a CSRF token
 */
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token
 */
function validateCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if IP is blocked due to too many failed attempts
 */
function isIPBlocked()
{
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'];
    $minutes = 15;
    $limit = 5;

    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    $stmt->execute([$ip, $minutes]);
    return $stmt->fetchColumn() >= $limit;
}

/**
 * Log a failed login attempt
 */
function logFailedAttempt()
{
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $db->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
    $stmt->execute([$ip]);
}

/**
 * Clear failed attempts for an IP (on successful login)
 */
function clearFailedAttempts()
{
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$ip]);
}

/**
 * Attempt to log in a user
 */
function login($username, $password, $csrfToken)
{
    if (!validateCSRFToken($csrfToken)) {
        return ['status' => false, 'message' => 'Errore di sicurezza (CSRF). Per favorere riprova.'];
    }

    if (isIPBlocked()) {
        return ['status' => false, 'message' => 'Troppi tentativi falliti. Riprova tra 15 minuti.'];
    }

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
            clearFailedAttempts();
            return ['status' => true];
        } else {
            logFailedAttempt();
        }
    } catch (PDOException $e) {
        // Log error
    }
    return ['status' => false, 'message' => 'Credenziali non valide.'];
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