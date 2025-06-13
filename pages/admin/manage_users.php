<?php
require_once '../../includes/header.php';
require_once '../../includes/middleware.php';

middleware('admin');

// Simulação de dados de usuários
$users = [
    ['id' => 1, 'nome' => 'Admin', 'email' => 'admin@escola.com', 'tipo' => 'admin', 'data_registro' => '2023-01-01'],
    ['id' => 2, 'nome' => 'Professor Silva', 'email' => 'prof.silva@escola.com', 'tipo' => 'teacher', 'data_registro' => '2023-01-15'],
    ['id' => 3, 'nome' => 'Aluno João', 'email' => 'aluno.joao@escola.com', 'tipo' => 'student', 'data_registro' => '2023-02-10'],
];
?>

<div class="container mt-5">
    <h1 class="mb-4">Gerenciar Usuários</h1>
    
    <div class="mb-3">
        <a href="#" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-plus-circle"></i> Adicionar Usuário
        </a>
    </div>
    
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Tipo</th>
                <th>Data Registro</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= $user['nome'] ?></td>
                <td><?= $user['email'] ?></td>
                <td>
                    <span class="badge bg-<?= $user['tipo'] === 'admin' ? 'danger' : ($user['tipo'] === 'teacher' ? 'primary' : 'success') ?>">
                        <?= ucfirst($user['tipo']) ?>
                    </span>
                </td>
                <td><?= date('d/m/Y', strtotime($user['data_registro'])) ?></td>
                <td>
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

<!-- Modal para adicionar usuário -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Adicionar Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="mb-3">
                        <label for="userName" class="form-label">Nome Completo</label>
                        <input type="text" class="form-control" id="userName" required>
                    </div>
                    <div class="mb-3">
                        <label for="userEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="userEmail" required>
                    </div>
                    <div class="mb-3">
                        <label for="userType" class="form-label">Tipo de Usuário</label>
                        <select class="form-select" id="userType" required>
                            <option value="student">Aluno</option>
                            <option value="teacher">Professor</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary">Salvar</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>