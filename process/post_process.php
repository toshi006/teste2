<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verificar autenticação e permissões
if (!isLoggedIn()) {
    header("Location: ../pages/login.php");
    exit;
}

// Verificar se é professor ou admin para criar/editar posts
if (!in_array(getUserRole(), ['professor', 'admin'])) {
    header("Location: ../pages/home.php?error=permissao");
    exit;
}

// Determinar ação (criar ou atualizar)
$action = $_POST['action'] ?? 'create';
$post_id = $_POST['post_id'] ?? null;

// Obter e sanitizar dados
$titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
$conteudo = $_POST['conteudo'] ?? '';
$categoria = filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_STRING);

// Validações
if (empty($titulo) || empty($conteudo) || empty($categoria)) {
    header("Location: ../pages/post/create.php?error=campos_vazios");
    exit;
}

// Processar HTML seguro (opcional: usar HTML Purifier)
$conteudo = purifyHTML($conteudo);

try {
    if ($action === 'create') {
        // Criar novo post
        $stmt = $pdo->prepare("INSERT INTO posts (titulo, conteudo, categoria, usuario_id, data_publicacao) 
                             VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$titulo, $conteudo, $categoria, $_SESSION['user_id']]);
        $post_id = $pdo->lastInsertId();
        
        // Redirecionar para o post criado
        header("Location: ../pages/post/view.php?id=$post_id&success=criado");
        exit;
    } elseif ($action === 'update' && $post_id) {
        // Verificar se o usuário é o autor do post
        $stmt = $pdo->prepare("SELECT usuario_id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();
        
        if (!$post || $post['usuario_id'] != $_SESSION['user_id']) {
            header("Location: ../pages/home.php?error=permissao");
            exit;
        }
        
        // Atualizar post existente
        $stmt = $pdo->prepare("UPDATE posts 
                              SET titulo = ?, conteudo = ?, categoria = ?, data_atualizacao = NOW() 
                              WHERE id = ?");
        $stmt->execute([$titulo, $conteudo, $categoria, $post_id]);
        
        // Redirecionar para o post atualizado
        header("Location: ../pages/post/view.php?id=$post_id&success=atualizado");
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao processar post: " . $e->getMessage());
    $error_redirect = $action === 'create' ? 'create' : "edit&id=$post_id";
    header("Location: ../pages/post/$error_redirect.php?error=banco_dados");
    exit;
}

// Redirecionamento padrão em caso de falha
header("Location: ../pages/home.php");
exit;

// Função auxiliar para sanitizar HTML (simplificada)
function purifyHTML($html) {
    // Implementação básica - considerar usar uma biblioteca como HTML Purifier
    $allowed_tags = '<p><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><br><img>';
    return strip_tags($html, $allowed_tags);
}
?>