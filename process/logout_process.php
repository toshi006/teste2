<?php
require_once '../includes/auth.php';

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpar dados da sessão
$_SESSION = [];

// Destruir sessão
session_destroy();

// Limpar cookie de "Lembrar de mim" se existir
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirecionar para a página de login
header("Location: ../pages/login.php?logout=1");
exit;
?>