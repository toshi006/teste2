<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verificar se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/login.php");
    exit;
}

// Obter e sanitizar dados do formulário
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

// Validação básica
if (empty($email) || empty($password)) {
    header("Location: ../pages/login.php?error=campos_vazios");
    exit;
}

// Buscar usuário no banco de dados
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Verificar credenciais
if (!$user || !password_verify($password, $user['senha'])) {
    header("Location: ../pages/login.php?error=credenciais");
    exit;
}

// Verificar se a conta está ativa (se aplicável)
if (isset($user['ativo']) && !$user['ativo']) {
    header("Location: ../pages/login.php?error=inativo");
    exit;
}

// Iniciar sessão
session_start();
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['nome'];
$_SESSION['user_role'] = $user['tipo'];

// Cookie de "Lembrar de mim" (opcional)
if ($remember) {
    $token = bin2hex(random_bytes(32));
    $expiry = time() + 60 * 60 * 24 * 30; // 30 dias
    
    // Salvar token no banco de dados
    $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = ?, token_expiry = ? WHERE id = ?");
    $stmt->execute([$token, date('Y-m-d H:i:s', $expiry), $user['id']]);
    
    // Definir cookie
    setcookie('remember_token', $token, $expiry, '/');
}

// Redirecionar para a página inicial
header("Location: ../pages/home.php");
exit;
?>