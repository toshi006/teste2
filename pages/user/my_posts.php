<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Apenas usuários logados podem ver seus posts
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$current_page = $_GET['page'] ?? 1;
$per_page = 10;
$offset = ($current_page - 1) * $per_page;

// Buscar posts do usuário
$stmt = $pdo->prepare("SELECT id, titulo, data_publicacao, categoria
                      FROM posts 
                      WHERE usuario_id = ? 
                      ORDER BY data_publicacao DESC 
                      LIMIT ? OFFSET ?");
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

// Contar total de posts para paginação
$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE usuario_id = ?");
$stmt->execute([$user_id]);
$total_posts = $stmt->fetchColumn();
$total_pages = ceil($total_posts / $per_page);

$pageTitle = "Meus Posts";
include '../../includes/header.php';
?>

<div class="container">
    <h1>Meus Posts</h1>
    
    <a href="../post/create.php" class="btn btn-primary">Criar Novo Post</a>
    
    <?php if (count($posts) > 0): ?>
        <table class="posts-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Categoria</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><a href="../post/view.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['titulo']) ?></a></td>
                        <td><?= htmlspecialchars($post['categoria']) ?></td>
                        <td><?= formatDate($post['data_publicacao'], 'd/m/Y') ?></td>
                        <td class="actions">
                            <a href="../post/edit.php?id=<?= $post['id'] ?>" class="btn btn-small">Editar</a>
                            <form action="../post/delete.php" method="post" onsubmit="return confirm('Tem certeza que deseja excluir este post?');">
                                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                <input type="hidden" name="return_to" value="my_posts.php">
                                <button type="submit" class="btn btn-small btn-danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Paginação -->
        <div class="pagination">
            <?php if ($total_pages > 1): ?>
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?= $current_page - 1 ?>" class="btn">&laquo; Anterior</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="btn <?= $i == $current_page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?= $current_page + 1 ?>" class="btn">Próxima &raquo;</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p>Você ainda não criou nenhum post.</p>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>