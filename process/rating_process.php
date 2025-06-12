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

// Obter e validar dados
$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$nota = filter_input(INPUT_POST, 'nota', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 5]
]);

// Validações
if (!$post_id || !$nota) {
    header("Location: ../pages/post/view.php?id=$post_id&error=avaliacao_invalida");
    exit;
}

// Verificar se o post existe
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
$stmt->execute([$post_id]);

if (!$stmt->fetch()) {
    header("Location: ../pages/home.php?error=post_nao_encontrado");
    exit;
}

// Verificar se o usuário já avaliou este post
$stmt = $pdo->prepare("SELECT id FROM avaliacoes WHERE post_id = ? AND usuario_id = ?");
$stmt->execute([$post_id, $_SESSION['user_id']]);

if ($stmt->fetch()) {
    // Atualizar avaliação existente
    try {
        $stmt = $pdo->prepare("UPDATE avaliacoes 
                              SET nota = ?, data_avaliacao = NOW() 
                              WHERE post_id = ? AND usuario_id = ?");
        $stmt->execute([$nota, $post_id, $_SESSION['user_id']]);
        
        header("Location: ../pages/post/view.php?id=$post_id&success=avaliacao_atualizada");
        exit;
    } catch (PDOException $e) {
        error_log("Erro ao atualizar avaliação: " . $e->getMessage());
        header("Location: ../pages/post/view.php?id=$post_id&error=erro_avaliacao");
        exit;
    }
} else {
    // Criar nova avaliação
    try {
        $stmt = $pdo->prepare("INSERT INTO avaliacoes 
                              (post_id, usuario_id, nota, data_avaliacao) 
                              VALUES (?, ?, ?, NOW())");
        $stmt->execute([$post_id, $_SESSION['user_id'], $nota]);
        
        header("Location: ../pages/post/view.php?id=$post_id&success=avaliacao_adicionada");
        exit;
    } catch (PDOException $e) {
        error_log("Erro ao adicionar avaliação: " . $e->getMessage());
        header("Location: ../pages/post/view.php?id=$post_id&error=erro_avaliacao");
        exit;
    }
}
?>