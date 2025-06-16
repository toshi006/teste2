<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
// $current_page = basename($_SERVER['PHP_SELF']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../pages/home.php");
    exit;
}
$post_id = $_POST['id'] ?? null;
if (!$post_id) {
    header("Location: ../../pages/home.php");
    var_dump($post_id);
    exit;
}
$return_to = $_POST['return_to'] ?? 'home.php'; // Define 'home.php' como padrão se não for enviado
// Buscar o post
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

// Verificar permissões (apenas autor ou admin)
if (!$post || ($_SESSION['user_id'] != $post['usuario_id'] && !hasRole('admin'))) {
    header("Location: ../../pages/home.php?error=acesso_negado");
    exit;
}

try {
    $pdo->beginTransaction();

    // Excluir comentários primeiro
    $stmt = $pdo->prepare("DELETE FROM comentarios WHERE post_id = ?");
    $stmt->execute([$post_id]);

    // Excluir o post
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);

    $pdo->commit();
    if ($return_to === 'my_posts.php') {
        $return_to = '../../pages/user/my_posts.php';
    } elseif ($return_to === 'list.php') {
        $return_to = '../../pages/post/list.php';
    } elseif ($return_to === 'manage_post.php') {
        $return_to = '../../pages/post/manage_post.php';
    } else {
        $return_to = '../../pages/user/home.php';
    }
    header("Location: $return_to?success=post_excluido");
    exit;
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: view.php?id=$post_id&error=erro_exclusao");
    exit;
}
?>