<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$post_id = $_GET['id'] ?? null;
if (!$post_id) {
    header("Location: /pages/home.php");
    exit;
}

// Buscar o post
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

// Verificar permissões (apenas autor ou admin)
if (!$post || ($_SESSION['user_id'] != $post['usuario_id'] && !hasRole('admin'))) {
    header("Location: /pages/home.php?error=acesso_negado");
    exit;
}

$error = '';
$success = '';

// Processar o formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $conteudo = trim($_POST['conteudo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');

    if (empty($titulo) || empty($conteudo) || empty($categoria)) {
        $error = 'Todos os campos são obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE posts SET titulo = ?, conteudo = ?, categoria = ?, data_atualizacao = NOW() WHERE id = ?");
            $stmt->execute([$titulo, $conteudo, $categoria, $post_id]);
            
            $success = 'Post atualizado com sucesso!';
            $post['titulo'] = $titulo;
            $post['conteudo'] = $conteudo;
            $post['categoria'] = $categoria;
        } catch (PDOException $e) {
            $error = 'Erro ao atualizar o post: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Editar Post: " . htmlspecialchars($post['titulo']);
include '../../includes/header.php';
?>

<div class="container">
    <h1>Editar Post</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <form method="post" class="post-form">
        <div class="form-group">
            <label for="titulo">Título</label>
            <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($post['titulo']) ?>" required class="form-control">
        </div>
        
        <div class="form-group">
            <label for="categoria">Categoria</label>
            <select id="categoria" name="categoria" required class="form-control">
                <option value="">Selecione...</option>
                <option value="Matemática" <?= $post['categoria'] === 'Matemática' ? 'selected' : '' ?>>Matemática</option>
                <option value="Ciências" <?= $post['categoria'] === 'Ciências' ? 'selected' : '' ?>>Ciências</option>
                <option value="História" <?= $post['categoria'] === 'História' ? 'selected' : '' ?>>História</option>
                <option value="Português" <?= $post['categoria'] === 'Português' ? 'selected' : '' ?>>Português</option>
                <option value="Inglês" <?= $post['categoria'] === 'Inglês' ? 'selected' : '' ?>>Inglês</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="conteudo">Conteúdo</label>
            <textarea id="conteudo" name="conteudo" class="form-control rich-text-editor" required><?= htmlspecialchars($post['conteudo']) ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="view.php?id=<?= $post['id'] ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>