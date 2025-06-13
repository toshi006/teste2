<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Verificar se o ID do usuário foi fornecido
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: ../home.php?error=usuario_nao_encontrado");
    exit;
}

// Buscar informações do usuário
$stmt = $pdo->prepare("SELECT id, nome, email, tipo, data_cadastro FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: ../home.php?error=usuario_nao_encontrado");
    exit;
}

// Buscar posts do usuário
$stmt = $pdo->prepare("SELECT id, titulo, data_publicacao, categoria FROM posts WHERE usuario_id = ? ORDER BY data_publicacao DESC LIMIT 5");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll();

// Buscar quizzes do usuário
$stmt = $pdo->prepare("SELECT id, titulo, data_criacao FROM quizzes WHERE usuario_id = ? ORDER BY data_criacao DESC LIMIT 5");
$stmt->execute([$user_id]);
$quizzes = $stmt->fetchAll();

$pageTitle = "Perfil de " . htmlspecialchars($user['nome']);
include '../../includes/header.php';
?>

<div class="container">
    <div class="profile-header">
        <div class="profile-info">
            <h1><?= htmlspecialchars($user['nome']) ?></h1>
            <p class="profile-role"><?= htmlspecialchars(ucfirst($user['tipo'])) ?></p>
            <p class="profile-joined">Membro desde <?= formatDate($user['data_cadastro'], 'd/m/Y') ?></p>
            
            <?php if (!empty($user['bio'])): ?>
                <div class="profile-bio">
                    <?= nl2br(htmlspecialchars($user['bio'])) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-sections">
        <section class="profile-posts">
            <h2>Últimos Posts</h2>
            <?php if (count($posts) > 0): ?>
                <div class="posts-list">
                    <?php foreach ($posts as $post): ?>
                        <div class="post-item">
                            <h3><a href="../post/view.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['titulo']) ?></a></h3>
                            <div class="post-meta">
                                <span><?= formatDate($post['data_publicacao'], 'd/m/Y') ?></span> &bull;
                                <span><?= htmlspecialchars($post['categoria']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="my_posts.php?id=<?= $user_id ?>" class="btn">Ver Todos</a>
            <?php else: ?>
                <p>Este usuário ainda não publicou nenhum post.</p>
            <?php endif; ?>
        </section>

        <section class="profile-quizzes">
            <h2>Últimos Quizzes</h2>
            <?php if (count($quizzes) > 0): ?>
                <div class="quizzes-list">
                    <?php foreach ($quizzes as $quiz): ?>
                        <div class="quiz-item">
                            <h3><a href="../quiz/view.php?id=<?= $quiz['id'] ?>"><?= htmlspecialchars($quiz['titulo']) ?></a></h3>
                            <div class="quiz-meta">
                                <span>Post: <?= htmlspecialchars($quiz['post_titulo']) ?></span> &bull;
                                <span><?= formatDate($quiz['data_criacao'], 'd/m/Y') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a href="my_quizzes.php?id=<?= $user_id ?>" class="btn">Ver Todos</a>
            <?php else: ?>
                <p>Este usuário ainda não criou nenhum quiz.</p>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php

include '../../includes/footer.php';
?>