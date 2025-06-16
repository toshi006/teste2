<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$current_page = $_GET['page'] ?? 1;
$per_page = 10;
$offset = ($current_page - 1) * $per_page;

// Removido JOIN com posts e coluna post_titulo
$stmt = $pdo->prepare("SELECT id, titulo, data_criacao 
                      FROM quizzes 
                      WHERE usuario_id = ? 
                      ORDER BY data_criacao DESC 
                      LIMIT ? OFFSET ?");
$stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$stmt->bindValue(2, $per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$quizzes = $stmt->fetchAll();

// Contar total de quizzes para paginação
$stmt = $pdo->prepare("SELECT COUNT(*) FROM quizzes WHERE usuario_id = ?");
$stmt->execute([$user_id]);
$total_quizzes = $stmt->fetchColumn();
$total_pages = ceil($total_quizzes / $per_page);

$pageTitle = "Meus Quizzes";
include '../../includes/header.php';
?>

<div class="container">
    <h1>Meus Quizzes</h1>
    <a href="<?= BASE_URL ?>/pages/quiz/create.php" class="btn btn-primary">Criar Novo Quiz</a>
    <?php if (count($quizzes) > 0): ?>
        <table class="quizzes-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Data de Criação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quizzes as $quiz): ?>
                    <tr>
                        <td><a href="../quiz/view.php?id=<?= $quiz['id'] ?>"><?= htmlspecialchars($quiz['titulo']) ?></a></td>
                        <td><?= formatDate($quiz['data_criacao'], 'd/m/Y') ?></td>
                        <td class="actions">
                            <a href="../quiz/edit.php?id=<?= $quiz['id'] ?>" class="btn btn-small">Editar</a>
                            <form action="../quiz/delete.php" method="post" onsubmit="return confirm('Tem certeza que deseja excluir este quiz?');">
                                <input type="hidden" name="id" value="<?= $quiz['id'] ?>">
                                <input type="hidden" name="return_to" value="my_quizzes.php">
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
        <p>Você ainda não criou nenhum quiz.</p>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
