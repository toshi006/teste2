<?php
session_start();
require_once 'db.php';

function login($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['senha'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_role'] = $user['tipo']; 
        return true;
    }
    
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function logout() {
    session_destroy();
    header("Location: login.php");
    exit;
}
function getUserRole() {
    // Verifica se a sessão tem o campo 'user_role' (que é seu campo de role no BD)
    return $_SESSION['user_role'] ?? null; // Retorna null se não existir
}
function hasAnyRole(array $allowedRoles) {
    if (!isset($_SESSION['user_role'])) {  // Agora usando 'user_role' em vez de 'tipo'
        return false;
    }
    return in_array($_SESSION['user_role'], $allowedRoles);
}
?>