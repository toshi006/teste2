<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/home.php");
    exit;
}

$post_id = $_POST['id'] ?? null;
if (!$post_id) {
    header("Location: /pages/home.php");
    exit;
}

// Buscar o post
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

// Verificar permissões (apenas autor ou admin)
if (!$post || ($_SESSION['user_id'] != $post['usuario_id'] && !hasRole('admin'))) {
    header("Location: /pages/home.php?error=acesso_negado");
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Verificar se há quizzes associados
    $stmt = $pdo->prepare("SELECT id FROM quizzes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    
    if ($stmt->fetch()) {
        header("Location: view.php?id=$post_id&error=post_com_quiz");
        exit;
    }
    
    // Excluir comentários primeiro
    $stmt = $pdo->prepare("DELETE FROM comentarios WHERE post_id = ?");
    $stmt->execute([$post_id]);
    
    // Excluir o post
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    
    $pdo->commit();
    
    header("Location: /pages/home.php?success=post_excluido");
    exit;
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: view.php?id=$post_id&error=erro_exclusao");
    exit;
}
?>