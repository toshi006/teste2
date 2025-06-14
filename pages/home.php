<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

require_once __DIR__ . '/../includes/config.php';
$pageTitle = "Página Inicial";
// Redirecionar usuários não logados para a página de login
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Buscar posts mais recentes
$stmt = $pdo->prepare("SELECT p.*, u.nome as autor 
                      FROM posts p 
                      JOIN usuarios u ON p.usuario_id = u.id 
                      ORDER BY p.data_publicacao DESC 
                      LIMIT 5");
$stmt->execute();
$recentPosts = $stmt->fetchAll();

// Buscar quizzes populares
$stmt = $pdo->prepare("SELECT q.*, u.nome as autor 
          FROM quizzes q
          JOIN usuarios u ON q.usuario_id = u.id");
$stmt->execute();
$popularQuizzes = $stmt->fetchAll();

$pageTitle = "Página Inicial";
include '../includes/header.php';

?>

<div class="container">
    <h1>Bem-vindo ao Sistema Educacional</h1>
    <p>Uma plataforma para o aprendizado e compartilhamento de conhecimento.<p>
    <div class="posts">
    <section class="recent-posts">
        <h2>Posts Recentes</h2>
        <div class="posts-grid">
            <?php foreach ($recentPosts as $post): ?>
                <?php include '../templates/post_card.php'; ?>
            <?php endforeach; ?>
        </div>
        <a href="post/list.php" class="btn">Ver Todos os Posts</a>
    </section>
    
    <section class="popular-quizzes">
        <h2>Quizzes Populares</h2>
        <div class="quizzes-grid">
            <?php foreach ($popularQuizzes as $quiz): ?>
                <?php include '../templates/quizz_card.php'; ?>
            <?php endforeach; ?>
        </div>
        <a href="quiz/list.php" class="btn">Ver Todos os Quizzes</a>
    </section>
    </div>
</div>

<?php include '../includes/footer.php'; ?>