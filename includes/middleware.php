<?php
/**
 * Middleware - Camada de validação e controle de acesso
 */

// Inicia a sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclui funções helpers se necessário
require_once __DIR__ . '/functions.php';

/**
 * Middleware principal
 */
function middleware($role = null) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: ../pages/login.php');
        exit();
    }
    
    if ($role && $_SESSION['user_role'] !== $role) {
        header('Location: ../pages/error.php?code=403');
        exit();
    }
}

/**
 * Versões específicas dos middlewares
 */
function authMiddleware() {
    middleware();
}

function adminMiddleware() {
    middleware('admin');
}

function roleMiddleware($roles) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../pages/login.php');
        exit();
    }
    
    $userRole = $_SESSION['user_role'];
    if (is_array($roles) ? !in_array($userRole, $roles) : $userRole !== $roles) {
        header('Location: ../pages/error.php?code=403');
        exit();
    }
}

/**
 * CSRF Protection (se não estiver em functions.php)
 */
if (!class_exists('CSRF')) {
    class CSRF {
        public static function generateToken() {
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            return $_SESSION['csrf_token'];
        }
        
        public static function validateToken($token) {
            if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
                header('Location: ../pages/error.php?code=419');
                exit();
            }
        }
    }
}