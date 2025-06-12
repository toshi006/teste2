<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$query = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'all'; // all, posts, quizzes, users

$pageTitle = "Busca: " . htmlspecialchars($query);
include '../includes/header.php';
?>

<div class="container">
    <h1>Resultados da Busca</h1>
    
    <div class="search-box">
        <form method="get" action="search.php">
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Buscar...">
            <select name="type">
                <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>Tudo</option>
                <option value="posts" <?= $type === 'posts' ? 'selected' : '' ?>>Posts</option>
                <option value="quizzes" <?= $type === 'quizzes' ? 'selected' : '' ?>>Quizzes</option>
                <option value="users" <?= $type === 'users' ? 'selected' : '' ?>>Usuários</option>
            </select>
            <button type="submit" class="btn">Buscar</button>
        </form>
    </div>
    
    <?php if (!empty($query)): ?>
        <div class="search-results">
            <?php if ($type === 'all' || $type === 'posts'): ?>
                <section class="search-posts">
                    <h2>Posts</h2>
                    <?php
                    $stmt = $pdo->prepare("SELECT p.*, u.nome as autor 
                                           FROM posts p 
                                           JOIN usuarios u ON p.usuario_id = u.id 
                                           WHERE p.titulo LIKE :query OR p.conteudo LIKE :query 
                                           ORDER BY p.data_publicacao DESC");
                    $stmt->execute(['query' => '%' . $query . '%']);
                    $posts = $stmt->fetchAll();
                    
                    if ($posts):
                        foreach ($posts as $post):
                            include '../templates/post_card.php';
                        endforeach;
                    else:
                        echo "<p>Nenhum post encontrado.</p>";
                    endif;
                    ?>
                </section>
            <?php endif; ?>
            <?php if ($type === 'all' || $type === 'quizzes'): ?>
                <section class="search-quizzes">
                    <h2>Quizzes</h2>
                    <?php
                    $stmt = $pdo->prepare("SELECT q.*, COUNT(r.id) as tentativas 
                                           FROM quizzes q 
                                           LEFT JOIN respostas_usuarios r ON q.id = r.quiz_id 
                                           WHERE q.titulo LIKE :query OR q.descricao LIKE :query 
                                           GROUP BY q.id 
                                           ORDER BY tentativas DESC");
                    $stmt->execute(['query' => '%' . $query . '%']);
                    $quizzes = $stmt->fetchAll();
                    
                    if ($quizzes):
                        foreach ($quizzes as $quiz):
                            include '../templates/quiz_card.php';
                        endforeach;
                    else:
                        echo "<p>Nenhum quiz encontrado.</p>";
                    endif;
                    ?>
                </section>
            <?php endif; ?>
            <?php if ($type === 'all' || $type === 'users'): ?>
                <section class="search-users">
                    <h2>Usuários</h2>
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nome LIKE :query OR email LIKE :query");
                    $stmt->execute(['query' => '%' . $query . '%']);
                    $users = $stmt->fetchAll();
                    
                    if ($users):
                        foreach ($users as $user):
                            include '../templates/user_card.php';
                        endforeach;
                    else:
                        echo "<p>Nenhum usuário encontrado.</p>";
                    endif;
                    ?>
                </section>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p>Por favor, insira um termo de busca.</p>
    <?php endif; ?>
</div>
<?php
include '../includes/footer.php';
// Footer template
?>
<footer>
    <p>&copy; <?= date('Y') ?> Sistema Educacional. Todos os direitos reservados.</p>