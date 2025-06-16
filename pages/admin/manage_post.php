<?php
require_once '../../includes/header.php';
require_once '../../includes/middleware.php';

middleware('admin');

// Simulação de dados de posts
require_once '../../includes/db.php'; // Certifique-se de ter um arquivo de conexão PDO

$posts = [];
try {
    $stmt = $pdo->query("
        SELECT 
            p.id, 
            p.titulo, 
            u.nome AS autor, 
            p.data_publicacao, 
            p.visualizacoes
        FROM posts p
        LEFT JOIN usuarios u ON p.usuario_id = u.id
    ");
  
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Trate o erro conforme necessário
    $posts = [];
}
?>

<style>
.admin-posts-container {
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
.admin-posts-container {
    position: relative;
}

.admin-posts-container .btn-return {
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
.admin-posts-container .btn-return:hover {
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
.admin-posts-table th,
.admin-posts-table td {
    vertical-align: middle;
    font-size: 1rem;
    padding: 0.75rem 1.25rem;
    text-align: center;
}
.admin-posts-table th {
    font-weight: 600;
    color: #495057;
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
}
.admin-posts-table td {
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
    background: #f8f9fa;
}
.admin-posts-table tr {
    border-bottom: 1px solid #e9ecef;
    transition: background 0.15s;

}
.admin-posts-table .btn-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.admin-posts-table .btn-group .btn {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    padding: 0.375rem 0.75rem;
    margin-right: 0;
}
.admin-posts-table .btn-group .btn:last-child {
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

<div class="admin-posts-container">
    <div class="d-flex">
                <div class="mb-3">
        <a href="../../pages/admin/dashboard.php" class="btn btn-return">
            <i class="bi bi-arrow-left"></i> Voltar para o Dashboard
        </a>
    </div>

        <h1 class="mb-3"> <i class="fas fa-book"></i> Gerenciar Posts</h1>
    </div>
    <div class="table-responsive">
        <table class="table admin-posts-table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Autor</th>
                    <th>Data</th>
                    <th>Visitas</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                <tr>
                    <td><?= $post['id'] ?></td>
                    <td><?= $post['titulo'] ?></td>
                    <td><?= $post['autor'] ?></td>
                    <td><?= date('d/m/Y', strtotime($post['data_publicacao'])) ?></td>
                    <td><?= $post['visualizacoes'] ?></td>
                    <td class="action">
                        <div class="btn-group">
                            <a href="../../pages/post/view.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-info" title="Visualizar">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="../../pages/post/edit.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form class="btn btn-sm btn-warning" action="../post/delete.php" method="post" onsubmit="return confirm('Tem certeza que deseja excluir este post?');">
                                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                <input type="hidden" name="return_to" value="manage_post.php">
                                <button type="submit" ><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php require_once '../../includes/footer.php'; ?>