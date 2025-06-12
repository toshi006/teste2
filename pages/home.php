<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

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
$stmt = $pdo->prepare("SELECT q.*, COUNT(r.id) as tentativas 
                      FROM quizzes q
                      LEFT JOIN respostas_usuarios r ON q.id = r.quiz_id
                      GROUP BY q.id
                      ORDER BY tentativas DESC
                      LIMIT 3");
$stmt->execute();
$popularQuizzes = $stmt->fetchAll();

$pageTitle = "Página Inicial";
include '../includes/header.php';
?>

<div class="container">
    <h1>Bem-vindo ao Sistema Educacional</h1>
    
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
                <?php include '../templates/quiz_card.php'; ?>
            <?php endforeach; ?>
        </div>
        <a href="quiz/list.php" class="btn">Ver Todos os Quizzes</a>
    </section>
    
    <?php if (getUserRole() === 'professor' || getUserRole() === 'admin'): ?>
        <div class="quick-actions">
            <a href="post/create.php" class="btn btn-primary">Criar Novo Post</a>
            <a href="quiz/create.php" class="btn btn-primary">Criar Novo Quiz</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>