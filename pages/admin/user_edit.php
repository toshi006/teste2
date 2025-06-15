<?php
require_once '../../includes/header.php';
require_once '../../includes/db.php';

// Verifica se o ID do usuário foi passado
if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>ID de usuário não especificado.</div>";
    require_once '../../includes/footer.php';
    exit;
}

$id = intval($_GET['id']);

// Busca os dados do usuário
$stmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $nome = $user['nome'];
    $email = $user['email'];
} else {
    echo "<div class='alert alert-danger'>Usuário não encontrado.</div>";
    require_once '../../includes/footer.php';
    exit;
}

// Atualiza os dados se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novo_nome = $_POST['nome'] ?? '';
    $novo_email = $_POST['email'] ?? '';

    $update = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id");
    $update->bindParam(':nome', $novo_nome, PDO::PARAM_STR);
    $update->bindParam(':email', $novo_email, PDO::PARAM_STR);
    $update->bindParam(':id', $id, PDO::PARAM_INT);

    if ($update->execute()) {
        echo "<div class='alert alert-success'>Usuário atualizado com sucesso!</div>";
        $nome = $novo_nome;
        $email = $novo_email;
    } else {
        echo "<div class='alert alert-danger'>Erro ao atualizar usuário.</div>";
    }
}
?>

<style>
.user-edit-container {
    max-width: 400px;
    margin: 5rem auto 0 auto;
}

.user-edit-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.10);
    background: #fff;
}

.user-edit-card-header {
    background: transparent;
    border-bottom: none;
    border-radius: 12px 12px 0 0;
    padding: 2rem 2rem 0 2rem;
    text-align: center;
}

.user-edit-card-header h2 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: #1e1e2f;
}

.user-edit-card-body {
    padding: 2rem;
}

.form-group label {
    font-weight: 500;
    color: #1e1e2f;
}

.form-control {
    border-radius: 0.5rem;
    border: 1px solid #d1d3e2;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    margin-bottom: 1rem;
}

.btn-lg-custom {
    padding: 0.75rem 2rem;
    font-size: 1.1rem;
    border-radius: 0.5rem;
}

.btn-primary {
    background: #00bcd4;
    border: none;
}

.btn-primary:hover, .btn-primary:focus {
    background: #009cb2;
}

.btn-secondary {
    background: #858796;
    border: none;
}

.btn-secondary:hover, .btn-secondary:focus {
    background: #6c757d;
}
</style>

<div class="container user-edit-container">
    <div class="card user-edit-card">
        <div class="card-header user-edit-card-header">
            <h2>Editar Usuário</h2>
        </div>
        <div class="card-body user-edit-card-body">
            <form method="post">
                <div class="form-group mb-3">
                    <label for="nome">Nome:</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" required>
                </div>
                <div class="form-group mb-3">
                    <label for="email">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg-custom">Salvar</button>
                <a href="../../pages/admin/manage_users.php" class="btn btn-secondary btn-lg-custom ms-2">Voltar</a>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>