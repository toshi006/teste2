<?php
require_once '../includes/auth.php';

// Se o usuário já estiver logado, redireciona para a home
if (isLoggedIn()) {
    header("Location: home.php");
    exit;
}

$pageTitle = "Login";
include '../includes/header.php';
?>

<div class="auth-container">
    <div class="login-form">
        <h2>Login</h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                $error = $_GET['error'];
                if ($error === 'credenciais') echo "Email ou senha incorretos";
                elseif ($error === 'inativo') echo "Sua conta está inativa";
                else echo "Erro ao fazer login";
                ?>
            </div>
        <?php endif; ?>
        
        <form action="../process/login_process.php" method="post">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="remember"> Lembrar de mim
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
        
        <div class="auth-links">
            <a href="register.php">Criar nova conta</a>
            <a href="forgot_password.php">Esqueci minha senha</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>