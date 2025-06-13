<?php
require_once '../../includes/header.php';
require_once '../../includes/middleware.php';

middleware('admin');
?>

<div class="container mt-5">
    <h1 class="mb-4">Configurações do Sistema</h1>
    
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#general">Geral</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#email">Email</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#security">Segurança</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- Aba Geral -->
                <div class="tab-pane fade show active" id="general">
                    <form>
                        <div class="mb-3">
                            <label for="siteName" class="form-label">Nome do Site</label>
                            <input type="text" class="form-control" id="siteName" value="Sistema Educacional">
                        </div>
                        <div class="mb-3">
                            <label for="siteDescription" class="form-label">Descrição</label>
                            <textarea class="form-control" id="siteDescription" rows="3">Plataforma de aprendizagem online</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="maintenanceMode" class="form-label">Modo Manutenção</label>
                            <select class="form-select" id="maintenanceMode">
                                <option value="0">Desativado</option>
                                <option value="1">Ativado</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                    </form>
                </div>
                
                <!-- Aba Email -->
                <div class="tab-pane fade" id="email">
                    <form>
                        <div class="mb-3">
                            <label for="smtpHost" class="form-label">SMTP Host</label>
                            <input type="text" class="form-control" id="smtpHost" value="smtp.exemplo.com">
                        </div>
                        <div class="mb-3">
                            <label for="smtpPort" class="form-label">Porta SMTP</label>
                            <input type="number" class="form-control" id="smtpPort" value="587">
                        </div>
                        <div class="mb-3">
                            <label for="smtpUser" class="form-label">Usuário SMTP</label>
                            <input type="text" class="form-control" id="smtpUser" value="contato@exemplo.com">
                        </div>
                        <div class="mb-3">
                            <label for="smtpPass" class="form-label">Senha SMTP</label>
                            <input type="password" class="form-control" id="smtpPass">
                        </div>
                        <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                    </form>
                </div>
                
                <!-- Aba Segurança -->
                <div class="tab-pane fade" id="security">
                    <form>
                        <div class="mb-3">
                            <label for="loginAttempts" class="form-label">Tentativas de Login Permitidas</label>
                            <input type="number" class="form-control" id="loginAttempts" value="5">
                        </div>
                        <div class="mb-3">
                            <label for="passwordExpiry" class="form-label">Expiração de Senha (dias)</label>
                            <input type="number" class="form-control" id="passwordExpiry" value="90">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="forceSSL">
                            <label class="form-check-label" for="forceSSL">Forçar Conexão SSL</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>