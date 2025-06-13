<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verificar se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "pages/register.php");
    exit;
}
 // Obter e sanitizar dados do formulário
$nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$tipo = in_array($_POST['tipo'], ['aluno', 'professor']) ? $_POST['tipo'] : 'aluno';
$terms = isset($_POST['terms']);

// Validações
$errors = [];

if (empty($nome) || empty($email) || empty($password) || empty($confirm_password)) {
    $errors[] = 'campos_vazios';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'email_invalido';
}

if (strlen($password) < 6) {
    $errors[] = 'senha_fraca';
}

if ($password !== $confirm_password) {
    $errors[] = 'senhas_nao_conferem';
}

if (!$terms) {
    $errors[] = 'termos_nao_aceitos';
}

// Verificar se email já existe
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    $errors[] = 'email_existe';
}

// Se houver erros, redirecionar com feedback
if (!empty($errors)) {
    header("Location: ../pages/register.php?error=" . $errors[0]);
    exit;
}

// Criar hash da senha
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Inserir novo usuário no banco de dados
try {
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo, data_cadastro) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$nome, $email, $password_hash, $tipo]);
    
    // Redirecionar para login com mensagem de sucesso
    header("Location: ../pages/login.php?success=registro");
    exit;
} catch (PDOException $e) {
    error_log("Erro ao registrar usuário: " . $e->getMessage());
    header("Location: ../pages/register.php?error=erro_banco_dados");
    exit;
}
?>