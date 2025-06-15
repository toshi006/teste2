<?php
require_once '../../includes/db.php'; // Inclui a conexão com o banco de dados
require_once '../../includes/config.php';
require_once '../../includes/middleware.php';

// Verifica se é admin
adminMiddleware();

// Sanitiza inputs
$name = htmlspecialchars($_POST['nome'], ENT_QUOTES, 'UTF-8');
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$password = password_hash($_POST['senha'], PASSWORD_DEFAULT);
$role = htmlspecialchars($_POST['tipo'], ENT_QUOTES, 'UTF-8');

// // Dump das variáveis para debug
// var_dump([
//     'name' => $name,
//     'email' => $email,
//     'password' => $password,
//     'role' => $role,
//     '_POST' => $_POST
// ]);
// exit;

// Validação básica
if (empty($name) || empty($email) || empty($_POST['senha']) || empty($role)) {
    $_SESSION['error'] = "Preencha todos os campos obrigatórios!";
    header("Location: ../../pages/admin/user_add.php");
    exit();
}

/** Garante que a variável $db está definida corretamente a partir do arquivo de conexão **/
if (!isset($db)) {
    if (isset($pdo)) {
        $db = $pdo;
    } else {
        // Tenta incluir manualmente a conexão se não estiver definida
        require_once '../../includes/db.php';
        if (!isset($db) && isset($pdo)) {
            $db = $pdo;
        }
    }
}

// Verifica se email já existe
$stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $_SESSION['error'] = "Este email já está cadastrado!";
    header("Location: ../../pages/admin/user_add.php");
    exit();
}

// Insere no banco
try {
    $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $password, $role]);
    
    $_SESSION['success'] = "Usuário cadastrado com sucesso!";
    header("Location: ../../pages/admin/manage_users.php");
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erro no banco de dados: " . $e->getMessage();
    header("Location: ../../pages/admin/user_add.php");
}