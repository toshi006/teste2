<?php
require_once '../../includes/config.php';
require_once '../../includes/middleware.php';

adminMiddleware();

// Sanitização e validação
$id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
$name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$role = htmlspecialchars($_POST['role'], ENT_QUOTES, 'UTF-8');

// Validações essenciais
if (!$id) {
    $_SESSION['error'] = "ID inválido!";
    header("Location: ../../pages/admin/manage_users.php");
    exit();
}

// Atualização condicional da senha
$password_update = "";
$params = [$name, $email, $role, $id];

if (!empty($_POST['password'])) {
    $password_update = ", password = ?";
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    array_splice($params, 2, 0, $password);
}

try {
    $sql = "UPDATE users SET name = ?, email = ?, role = ? $password_update WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $_SESSION['success'] = "Usuário atualizado!";
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erro ao atualizar: " . $e->getMessage();
}

header("Location: ../../pages/admin/manage_users.php");