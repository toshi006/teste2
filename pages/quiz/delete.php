<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/home.php");
    exit;
}

$quiz_id = $_POST['id'] ?? null;
if (!$quiz_id) {
    header("Location: /pages/home.php");
    exit;
}

// Buscar informações do quiz
$stmt = $pdo->prepare("SELECT q.*, p.usuario_id as post_autor_id 
                      FROM quizzes q 
                      JOIN posts p ON q.post_id = p.id 
                      WHERE q.id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

// Verificar permissões (apenas autor do post ou admin)
if (!$quiz || ($_SESSION['user_id'] != $quiz['post_autor_id'] && !hasRole('admin'))) {
    header("Location: /pages/home.php?error=permissao_negada");
    exit;
}

try {
    $pdo->beginTransaction();
    
    // 1. Excluir respostas dos usuários
    $stmt = $pdo->prepare("DELETE FROM respostas_usuarios WHERE quiz_id = ?");
    $stmt->execute([$quiz_id]);
    
    // 2. Excluir opções das perguntas
    $stmt = $pdo->prepare("DELETE op FROM opcoes op
                          JOIN perguntas p ON op.pergunta_id = p.id
                          WHERE p.quiz_id = ?");
    $stmt->execute([$quiz_id]);
    
    // 3. Excluir perguntas
    $stmt = $pdo->prepare("DELETE FROM perguntas WHERE quiz_id = ?");
    $stmt->execute([$quiz_id]);
    
    // 4. Excluir o quiz
    $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    
    $pdo->commit();
    
    header("Location: /pages/home.php?success=quiz_excluido");
    exit;
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: view.php?id=$quiz_id&error=erro_exclusao");
    exit;
}
?>