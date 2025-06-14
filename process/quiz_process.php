<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verificar autenticação
if (!isLoggedIn()) {
    header("Location: ../pages/login.php");
    exit;
}

// Verificar se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/home.php");
    exit;
}

// Determinar ação (criar quiz ou responder quiz)
$action = $_POST['action'] ?? 'responder';

if ($action === 'criar_quiz') {
    // Processar criação de quiz
    require 'process_quiz_creation.php';
} else {
    // Processar respostas do quiz
    require '../process/process_quiz_responses.php';
}

?>