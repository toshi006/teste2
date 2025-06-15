<?php
require_once '../includes/auth.php';

// Se o usuário já estiver logado, redireciona para a home
if (isLoggedIn()) {
    header("Location: home.php");
    exit;
}

$pageTitle = "Registro";
include '../includes/header.php';
?>

<div class="auth-container">
    <div class="register-form">
        <h2>Criar Nova Conta</h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                $error = $_GET['error'];
                if ($error === 'email_existe') echo "Este email já está cadastrado";
                elseif ($error === 'senha_fraca') echo "A senha deve ter pelo menos 6 caracteres";
                elseif ($error === 'campos_vazios') echo "Preencha todos os campos";
                else echo "Erro ao registrar";
                ?>
            </div>
        <?php endif; ?>
        <form action="../process/register_process.php" method="post">
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" required>
                <small>Mínimo de 6 caracteres</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirme a Senha:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="tipo">Sou um:</label>
                <select id="tipo" name="tipo" required>
                    <option value="aluno">Aluno</option>
                    <option value="professor">Professor</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="terms" required> Aceito os termos de uso
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Registrar</button>
        </form>
        
        <div class="auth-links">
            Já tem uma conta? <a href="login.php">Faça login</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>