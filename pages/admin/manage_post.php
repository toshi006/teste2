<?php
require_once '../../includes/header.php';
require_once '../../includes/middleware.php';

middleware('admin');

// Simulação de dados de posts
$posts = [
    ['id' => 1, 'titulo' => 'Introdução ao PHP', 'autor' => 'Professor Silva', 'data' => '2023-03-01', 'visitas' => 150],
    ['id' => 2, 'titulo' => 'Fundamentos de Matemática', 'autor' => 'Professor Costa', 'data' => '2023-03-05', 'visitas' => 89],
    ['id' => 3, 'titulo' => 'História da Arte Moderna', 'autor' => 'Professora Ana', 'data' => '2023-03-10', 'visitas' => 45],
];
?>

<div class="container mt-5">
    <h1 class="mb-4">Gerenciar Posts</h1>
    
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
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
                    <td><?= date('d/m/Y', strtotime($post['data'])) ?></td>
                    <td><?= $post['visitas'] ?></td>
                    <td>
                        <a href="#" class="btn btn-sm btn-info" title="Visualizar">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-warning" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-danger" title="Excluir">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>