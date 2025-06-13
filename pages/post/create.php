<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Verifica se o usuário tem permissão (professores ou admins)
if (!hasAnyRole(['professor', 'admin'])) {
    header("Location: /teste2/pages/home.php");
    exit;
}

$error = '';
$success = '';

// Processar o formulário de criação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $conteudo = trim($_POST['conteudo'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');

    // Validações
    if (empty($titulo) || empty($conteudo) || empty($categoria)) {
        $error = 'Todos os campos são obrigatórios.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Inserir o post no banco de dados
            $stmt = $pdo->prepare("INSERT INTO posts (titulo, conteudo, categoria, usuario_id, data_publicacao) 
                                 VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$titulo, $conteudo, $categoria, $_SESSION['user_id']]);
            $post_id = $pdo->lastInsertId();
            
            $pdo->commit();
            
            // Redirecionar para o post criado
            header("Location: view.php?id=$post_id&success=1");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Erro ao criar o post: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Criar Novo Post";
include '../../includes/header.php';
?>

<div class="container">
    <h1>Criar Novo Post Educacional</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="post" class="post-form">
        <div class="form-group">
            <label for="titulo">Título</label>
            <input type="text" id="titulo" name="titulo" required class="form-control">
        </div>
        
        <div class="form-group">
            <label for="categoria">Categoria</label>
            <select id="categoria" name="categoria" required class="form-control">
                <option value="">Selecione...</option>
                <option value="Matemática">Matemática</option>
                <option value="Ciências">Ciências</option>
                <option value="História">História</option>
                <option value="Português">Português</option>
                <option value="Inglês">Inglês</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="conteudo">Conteúdo</label>
            <textarea id="conteudo" name="conteudo" class="form-control rich-text-editor" required></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Publicar Post</button>
            <a href="list.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>