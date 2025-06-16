<?php
require_once '../../includes/header.php';
require_once '../../includes/db.php'; // Inclua o arquivo que cria a conexão PDO em $conn
require_once '../../includes/middleware.php';

middleware('admin');

// Simulação de dados de usuários
$users = [];
try {
    $stmt = $pdo->prepare("SELECT id, nome, email, tipo, data_cadastro FROM usuarios");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}
?>

<div class="container mt-5">
    
    <div class="admin-users-container">
        <div class="mb-3">
        <a href="../../pages/admin/dashboard.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Voltar para o Dashboard
        </a>
    </div>
        <!-- Cabeçalho com título e botão -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title">
                <i class="bi bi-people-fill me-2"></i>Gerenciar Usuários
            </h1>
            <div class="mb-3">
                <a href="../../pages/admin/user_add.php" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-plus-circle"></i> Adicionar Usuário
                </a>
            </div>
        </div>

        <!-- Tabela de usuários -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 admin-users-table">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Data Registro</th>
                                <th class="text-end pe-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr class="user-row">
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($user['id']) ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <span class="d-block fw-semibold"><?= htmlspecialchars($user['nome']) ?></span>
                                               
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?= $user['tipo'] === 'admin' ? 'danger' : ($user['tipo'] === 'teacher' ? 'primary' : 'success') ?>">
                                            <i class="bi bi-<?= $user['tipo'] === 'admin' ? 'shield-shaded' : ($user['tipo'] === 'teacher' ? 'person-video2' : 'person') ?> me-1"></i>
                                            <?= ucfirst(htmlspecialchars($user['tipo'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span><?= date('d/m/Y', strtotime($user['data_cadastro'])) ?></span>
                                            <small class="text-muted"><?= date('H:i', strtotime($user['data_cadastro'])) ?></small>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4 action">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="../../pages/admin/user_edit.php?id=<?= urlencode($user['id']) ?>" class="btn btn-outline-primary" title="Editar">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <a href="../../process/admin/user_delete_process.php?id=<?= urlencode($user['id']) ?>" class="btn btn-outline-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este usuário?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</script>
<?php require_once '../../includes/footer.php'; ?>