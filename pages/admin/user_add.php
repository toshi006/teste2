<?php
require_once '../../includes/header.php';
?>
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addUserForm" action="../../process/admin/add_user_process.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Adicionar Novo Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                        <div class="mb-3">
                            <label for="userName" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="userName" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="userEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="userEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="userType" class="form-label">Tipo de Usuário</label>
                            <select class="form-select" id="userType" name="tipo" required>
                                <option value="aluno">Aluno</option>
                                <option value="professor">Professor</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="userPassword" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="userPassword" name="senha" required>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>