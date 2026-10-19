<?php
// includes/auth.php - Simple Admin Authentication Middleware

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db.php';

function isLoggedIn() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function requireRole($roles = []) {
    requireLogin();
}

function authenticateUser($username, $password) {
    $pdo = getDBConnection();
    
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    return [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'full_name' => $user['full_name'],
                        'role' => 'admin',
                    ];
                }
            }
        } catch (Exception $e) {
            // Fallback
        }
    }

    // Default admin credential
    if ($username === 'admin' && $password === 'admin123') {
        return [
            'id' => 1,
            'username' => 'admin',
            'full_name' => 'Administrator',
            'role' => 'admin',
        ];
    }

    return false;
}
