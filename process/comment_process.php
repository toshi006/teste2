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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['post_id'])) {
    header("Location: ../pages/home.php");
    exit;
}

// Obter e sanitizar dados
$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$texto = filter_input(INPUT_POST, 'texto', FILTER_SANITIZE_STRING);
$parent_id = isset($_POST['parent_id']) ? filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT) : null;

// Validações
if (!$post_id || empty($texto)) {
    header("Location: ../pages/post/view.php?id=$post_id&error=comentario_invalido");
    exit;
}

// Verificar se o post existe
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
$stmt->execute([$post_id]);

if (!$stmt->fetch()) {
    header("Location: ../pages/home.php?error=post_nao_encontrado");
    exit;
}

// Inserir comentário no banco de dados
try {
    $stmt = $pdo->prepare("INSERT INTO comentarios 
                          (post_id, usuario_id, texto, parent_id, data_comentario) 
                          VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$post_id, $_SESSION['user_id'], $texto, $parent_id]);
    
    // Redirecionar de volta ao post com mensagem de sucesso
    header("Location: ../pages/post/view.php?id=$post_id&success=comentario_adicionado#comentarios");
    exit;
} catch (PDOException $e) {
    error_log("Erro ao adicionar comentário: " . $e->getMessage());
    header("Location: ../pages/post/view.php?id=$post_id&error=erro_comentario");
    exit;
}
?>