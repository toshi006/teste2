<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/middleware.php';

adminMiddleware();

$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['error'] = "ID inválido!";
    header("Location: ../../pages/admin/manage_users.php");
    exit();
}

try {
    // Impede que o admin se exclua
    if ($_SESSION['user_id'] == $id) {
        throw new Exception("Você não pode se excluir!");
    }
    
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['success'] = "Usuário excluído com sucesso!";
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header("Location: ../../pages/admin/manage_users.php");
exit();
