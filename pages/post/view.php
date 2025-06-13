<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$post_id = $_GET['id'] ?? null;
if (!$post_id) {
    header("Location: /pages/home.php");
    exit;
}

// Incrementar contagem de visualizações apenas se o usuário ainda não visualizou
$viewed_posts = $_SESSION['viewed_posts'] ?? [];
if (!in_array($post_id, $viewed_posts)) {
    $stmt = $pdo->prepare("UPDATE posts SET visualizacoes = visualizacoes + 1 WHERE id = ?");
    $stmt->execute([$post_id]);
    $viewed_posts[] = $post_id;
    $_SESSION['viewed_posts'] = $viewed_posts;
}

// Buscar o post com informações do autor
$stmt = $pdo->prepare("SELECT p.*, u.nome as autor
                      FROM posts p 
                      JOIN usuarios u ON p.usuario_id = u.id 
                      WHERE p.id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    header("Location: /pages/home.php?error=post_nao_encontrado");
    exit;
}

// Buscar comentários
$stmt = $pdo->prepare("SELECT c.*, u.nome as autor
                      FROM comentarios c
                      JOIN usuarios u ON c.usuario_id = u.id
                      WHERE c.post_id = ?
                      ORDER BY c.data_comentario DESC");
$stmt->execute([$post_id]);
$comentarios = $stmt->fetchAll();

// Verificar se o usuário atual é o autor
$is_autor = ($_SESSION['user_id'] ?? null) == $post['usuario_id'];

$pageTitle = $post['titulo'];
include '../../includes/header.php';
?>

<div class="container post-view">
    <article class="post-content">
        <div class="post-header">
            <div class="author-info">
                <span class="author-name"><?= htmlspecialchars($post['autor']) ?></span>
            </div>
            <span class="post-date"><?= formatDate($post['data_publicacao'], 'd/m/Y H:i') ?></span>
            <span class="post-category"><?= htmlspecialchars($post['categoria']) ?></span>
        </div>
        
        <h1><?= htmlspecialchars($post['titulo']) ?></h1>
        
        <div class="post-body">
            <?= $post['conteudo'] ?>
        </div>
        
        <?php if ($is_autor || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
            <div class="post-actions">
            <a href="edit.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
            <form action="delete.php" method="post" class="d-inline">
                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este post?')">Excluir</button>
            </form>
            </div>
        <?php endif; ?>
    </article>
    
    <section class="post-comments">
        <h2>Comentários</h2>
        
        <?php if (isLoggedIn()): ?>
            <form action="/teste2/process/comment_process.php" method="post" class="comment-form">
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <div class="form-group">
                    <textarea name="texto" placeholder="Adicione um comentário..." required class="form-control"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Enviar Comentário</button>
            </form>
        <?php else: ?>
            <div class="alert alert-info">
                <a href="/pages/login.php">Faça login</a> para comentar.
            </div>
        <?php endif; ?>
        
        <div class="comments-list">
            <?php foreach ($comentarios as $comentario): ?>
                <div class="comment">
                    <div class="comment-header">
                        <span class="comment-author"><?= htmlspecialchars($comentario['autor']) ?></span>
                        <span class="comment-date"><?= formatDate($comentario['data_comentario'], 'd/m/Y H:i') ?></span>
                    </div>
                    <div class="comment-body">
                        <?= nl2br(htmlspecialchars($comentario['texto'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php
include '../../includes/footer.php';
?>