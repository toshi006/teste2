<?php
require_once '../../includes/header.php';
require_once '../../includes/middleware.php';

middleware('admin');

// Simulação de dados de quizzes
$quizzes = [];
try {
    $stmt = $pdo->query("
        SELECT 
            q.id, 
            q.titulo, 
            u.nome AS criador, 
            COUNT(DISTINCT p.id) AS questoes
        FROM quizzes q
        LEFT JOIN usuarios u ON q.usuario_id = u.id
        LEFT JOIN perguntas p ON q.id = p.quiz_id
        GROUP BY q.id, u.nome
    ");
  
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Trate o erro conforme necessário
    $quizzes = [];
}
?>
<style>
.admin-quizzes-container {
    background: #fff;
    border-radius: 12px;
    padding: 32px 24px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.05);
    margin-top: 6rem;
    max-width: 900px;
    width: 100%;
    margin-left: auto;
    margin-right: auto;
}
.admin-quizzes-container {
    position: relative;
}

.admin-quizzes-container .btn-return {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    position: absolute;
    top: 0;
    right: 0;
    margin: 16px 24px 0 0;
    z-index: 2;
    background: #f8f9fa;
    color: #6c757d;
    border: 1px solid #ced4da;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: background 0.2s, color 0.2s;
    margin-left: auto;
}
.admin-quizzes-container .btn-return:hover {
    background: #e2e6ea;
    color: #343a40;
    border-color: #adb5bd;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-decoration: none;
}
.table-responsive {
    display: flex;
    flex-direction: column;
    width: 100%;

}
.d-flex {
    display: flex;
    flex-direction: wrap;
    align-items: flex-start;
    gap: 1rem;
    width: 100%;
    margin-bottom: 52px;
}
.d-flex .mb-3{
    margin-bottom: 1rem;
}
.admin-quizzes-table th,
.admin-quizzes-table td {
    vertical-align: middle;
    font-size: 1rem;
    padding: 0.75rem 1.25rem;
    text-align: center;
}
.admin-quizzes-table th {
    font-weight: 600;
    color: #495057;
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
}
.admin-quizzes-table td {
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
    background: #f8f9fa;
}
.admin-quizzes-table tr {
    border-bottom: 1px solid #e9ecef;
    transition: background 0.15s;

}
.admin-quizzes-table .btn-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.admin-quizzes-table .btn-group .btn {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    padding: 0.375rem 0.75rem;
    margin-right: 0;
}
.admin-quizzes-table .btn-group .btn:last-child {
    margin-right: 0;
}
.action > .btn-group {
    display: flex;
    justify-content: space-around;
    align-items: center;
}
form {
   height: 32px;
   width: 40px;
}
button{
    background: transparent;
    border: none;
    color: #ffffff;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    cursor: pointer;
    transition: color 0.2s;
}
button:hover {
  color: #008ca1;
}
@media (max-width: 768px) {
    .admin-posts-container {
        padding: 16px 6px;
    }
    .admin-posts-table th, .admin-posts-table td {
        font-size: 0.95rem;
    }
}
</style>

<div class="admin-quizzes-container">
    <div class="d-flex">
        <div class="mb-3">
            <a href="../../pages/admin/dashboard.php" class="btn btn-return">
                <i class="bi bi-arrow-left"></i> Voltar para o Dashboard
            </a>
        </div>
        <h1 class="mb-3 ms-3"><i class="fas fa-question-circle"></i> Gerenciar Quizzes</h1>
    </div>
    <div class="table-responsive">
        <table class="table admin-quizzes-table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Criador</th>
                    <th>Questões</th>
                    <th>Tentativas</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quizzes as $quiz): ?>
                <tr>
                    <td><?= $quiz['id'] ?></td>
                    <td><?= $quiz['titulo'] ?></td>
                    <td><?= $quiz['criador'] ?></td>
                    <td><?= $quiz['questoes'] ?></td>
                    <td>—</td>
                    <td class="action">
                        <div class="btn-group">
                            <a href="#" class="btn btn-sm btn-info" title="Visualizar">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-warning" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                             <form class="btn btn-sm btn-warning" action="../quiz/delete.php" method="post" onsubmit="return confirm('Tem certeza que deseja excluir este quizz?');">
                                <input type="hidden" name="id" value="<?= $quiz['id'] ?>">
                                <input type="hidden" name="return_to" value="manage_quiz.php">
                                <button type="submit" ><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>