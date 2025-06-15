<?php
require_once '../../includes/header.php';
require_once '../../includes/middleware.php';

middleware('admin');

// Simulação de dados de quizzes
$quizzes = [
    ['id' => 1, 'titulo' => 'Matemática Básica', 'criador' => 'Professor Silva', 'questoes' => 10, 'tentativas' => 85],
    ['id' => 2, 'titulo' => 'Gramática Portuguesa', 'criador' => 'Professora Ana', 'questoes' => 15, 'tentativas' => 62],
    ['id' => 3, 'titulo' => 'História do Brasil', 'criador' => 'Professor Costa', 'questoes' => 20, 'tentativas' => 47],
];
?>

<div class="container mt-5">
    <h1 class="mb-4">Gerenciar Quizzes</h1>
    
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
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
                    <td><?= $quiz['tentativas'] ?></td>
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
                        <a href="#" class="btn btn-sm btn-success" title="Estatísticas">
                            <i class="bi bi-graph-up"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>