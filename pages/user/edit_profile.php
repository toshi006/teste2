<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Apenas usuários logados podem editar o perfil
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Buscar informações atuais do usuário
$stmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: ../home.php?error=usuario_nao_encontrado");
    exit;
}

$error = '';
$success = '';

// Processar o formulário de atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validações
    if (empty($nome) || empty($email)) {
        $error = 'Nome e email são obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido.';
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = 'As novas senhas não coincidem.';
    } else {
        // Verificar se o email já existe (outro usuário)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error = 'Este email já está sendo usado por outro usuário.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Atualizar informações básicas
                $updateData = [
                    'nome' => $nome,
                    'email' => $email,
                    'id' => $user_id
                ];
                
                // Se uma nova senha foi fornecida, verificar a senha atual
                if (!empty($new_password)) {
                    $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $currentUser = $stmt->fetch();
                    
                    if (!$currentUser || !password_verify($current_password, $currentUser['senha'])) {
                        $error = 'Senha atual incorreta.';
                    } else {
                        $updateData['senha'] = password_hash($new_password, PASSWORD_DEFAULT);
                    }
                }
                
                if (empty($error)) {
                    // Processar upload de avatar
                    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['avatar'];
                        
                        // Verificar se é uma imagem
                        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_file($fileInfo, $file['tmp_name']);
                        if (strpos($mime, 'image/') === 0) {
                            // Diretório de upload
                            $uploadDir = '../../uploads/avatars/';
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0777, true);
                            }
                            
                            // Gerar nome único para o arquivo
                            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                            $filename = uniqid('avatar_') . '.' . $extension;
                            $destination = $uploadDir . $filename;
                            
                            if (move_uploaded_file($file['tmp_name'], $destination)) {
                                // Remover avatar antigo se existir
                                if ($user['foto_perfil']) {
                                    $oldFile = $uploadDir . $user['foto_perfil'];
                                    if (file_exists($oldFile)) {
                                        unlink($oldFile);
                                    }
                                }
                                $updateData['foto_perfil'] = $filename;
                            }
                        }
                    }
                    
                    // Construir a query de atualização
                    $setParts = [];
                    $params = [];
                    foreach ($updateData as $key => $value) {
                        if ($key !== 'id') {
                            $setParts[] = "$key = ?";
                            $params[] = $value;
                        }
                    }
                    $params[] = $user_id;
                    
                    $sql = "UPDATE usuarios SET " . implode(', ', $setParts) . " WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $pdo->commit();
                    
                    // Atualizar dados na sessão
                    $_SESSION['user_name'] = $nome;
                    $_SESSION['user_email'] = $email;
                    
                    $success = 'Perfil atualizado com sucesso!';
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Erro ao atualizar o perfil: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = "Editar Perfil";
include '../../includes/header.php';
?>

<div class="container">
    <h1>Editar Perfil</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="avatar">Foto de Perfil</label>
            <input type="file" id="avatar" name="avatar" accept="image/*">
            <?php if ($user['foto_perfil']): ?>
                <div class="current-avatar">
                    <img src="<?= getAvatarUrl($user['foto_perfil']) ?>" alt="Avatar atual" style="max-width: 150px; margin-top: 10px;">
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="nome">Nome Completo</label>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($user['nome']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="bio">Biografia</label>
            <textarea id="bio" name="bio" rows="4"><?= htmlspecialchars($user['bio']) ?></textarea>
        </div>
        
        <h2>Alterar Senha</h2>
        <div class="form-group">
            <label for="current_password">Senha Atual</label>
            <input type="password" id="current_password" name="current_password">
        </div>
        
        <div class="form-group">
            <label for="new_password">Nova Senha</label>
            <input type="password" id="new_password" name="new_password">
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirme a Nova Senha</label>
            <input type="password" id="confirm_password" name="confirm_password">
        </div>
        
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
    </form>
</div>

<?php
// Função para obter a URL do avatar (mesma do profile.php)
function getAvatarUrl($filename) {
    if ($filename) {
        return BASE_URL . '/uploads/avatars/' . $filename;
    }
    return BASE_URL . '/assets/images/default-avatar.jpg';
}

include '../../includes/footer.php';
?>