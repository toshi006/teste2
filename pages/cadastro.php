<?php 
require_once 'includes/auth.php';
if (!isLoggedIn()) header("Location: login.php");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cadastro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h2>Cadastrar Item</h2>
        
        <form action="process_cadastro.php" method="post">
            <div class="form-group">
                <label>Nome:</label>
                <input type="text" name="nome" required>
            </div>
            <div class="form-group">
                <label>Descrição:</label>
                <textarea name="descricao"></textarea>
            </div>
            <div class="form-group">
                <label>Quantidade:</label>
                <input type="number" name="quantidade" required>
            </div>
            <button type="submit">Cadastrar</button>
        </form>
    </div>
</body>
</html>