<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verificar autenticação
if (!isLoggedIn()) {
    header("Location: ../pages/login.php");
    exit;
}

// Verificar se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/home.php");
    exit;
}

// Determinar ação (criar quiz ou responder quiz)
$action = $_POST['action'] ?? 'responder';

if ($action === 'criar_quiz') {
    // Processar criação de quiz
    require 'process_quiz_creation.php';
} else {
    // Processar respostas do quiz
    require 'process_quiz_responses.php';
}

// --- Arquivo process_quiz_creation.php ---
// Verificar permissões (aprofessores/admins podem criar quizzes)
if (!in_array(getUserRole(), ['professor', 'admin'])) {
    header("Location: ../pages/home.php?error=permissao");
    exit;
}

// Obter dados do formulário
$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
$descricao = $_POST['descricao'] ?? '';

// Validações básicas
if (!$post_id || empty($titulo)) {
    header("Location: ../pages/quiz/create.php?post_id=$post_id&error=campos_vazios");
    exit;
}

// Verificar se o post pertence ao usuário (opcional)
$stmt = $pdo->prepare("SELECT usuario_id FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post || ($post['usuario_id'] != $_SESSION['user_id'] && getUserRole() !== 'admin')) {
    header("Location: ../pages/home.php?error=permissao");
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Inserir quiz
    $stmt = $pdo->prepare("INSERT INTO quizzes (post_id, titulo, descricao, data_criacao) 
                          VALUES (?, ?, ?, NOW())");
    $stmt->execute([$post_id, $titulo, $descricao]);
    $quiz_id = $pdo->lastInsertId();
    
    // Processar perguntas
    foreach ($_POST['pergunta_texto'] as $index => $texto) {
        if (empty($texto)) continue;
        
        $tipo = $_POST['pergunta_tipo'][$index];
        $pontos = $_POST['pergunta_pontos'][$index] ?? 1;
        
        // Inserir pergunta
        $stmt = $pdo->prepare("INSERT INTO perguntas 
                              (quiz_id, texto, tipo, pontos) 
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([$quiz_id, $texto, $tipo, $pontos]);
        $pergunta_id = $pdo->lastInsertId();
        
        // Processar opções para múltipla escolha
        if ($tipo === 'multipla_escolha' && isset($_POST['opcao_texto'][$index])) {
            foreach ($_POST['opcao_texto'][$index] as $opcao_index => $opcao_texto) {
                if (empty($opcao_texto)) continue;
                
                $correta = isset($_POST['opcao_correta'][$index][$opcao_index]);
                
                $stmt = $pdo->prepare("INSERT INTO opcoes 
                                      (pergunta_id, texto, correta) 
                                      VALUES (?, ?, ?)");
                $stmt->execute([$pergunta_id, $opcao_texto, $correta ? 1 : 0]);
            }
        }
    }
    
    $pdo->commit();
    header("Location: ../pages/quiz/view.php?id=$quiz_id&success=quiz_criado");
    exit;
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao criar quiz: " . $e->getMessage());
    header("Location: ../pages/quiz/create.php?post_id=$post_id&error=erro_banco_dados");
    exit;
}
?>

// --- Arquivo process_quiz_responses.php ---
<?php
// Obter dados do formulário
$quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);

// Validações básicas
if (!$quiz_id) {
    header("Location: ../pages/home.php");
    exit;
}

// Verificar se o quiz existe
$stmt = $pdo->prepare("SELECT id FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);

if (!$stmt->fetch()) {
    header("Location: ../pages/home.php?error=quiz_nao_encontrado");
    exit;
}

try {
    $pdo->beginTransaction();
    $pontuacao_total = 0;
    
    // Buscar perguntas do quiz
    $stmt = $pdo->prepare("SELECT id, tipo, pontos FROM perguntas WHERE quiz_id = ?");
    $stmt->execute([$quiz_id]);
    $perguntas = $stmt->fetchAll();
    
    foreach ($perguntas as $pergunta) {
        $resposta = $_POST['resposta'][$pergunta['id']] ?? '';
        $pontuacao = 0;
        
        // Verificar resposta correta
        if ($pergunta['tipo'] === 'multipla_escolha') {
            // Buscar opção correta
            $stmt = $pdo->prepare("SELECT id FROM opcoes WHERE pergunta_id = ? AND correta = 1");
            $stmt->execute([$pergunta['id']]);
            $opcao_correta = $stmt->fetchColumn();
            
            if ($resposta == $opcao_correta) {
                $pontuacao = $pergunta['pontos'];
            }
        } elseif ($pergunta['tipo'] === 'verdadeiro_falso') {
            // Buscar resposta correta (armazenada no texto da pergunta)
            $stmt = $pdo->prepare("SELECT texto FROM perguntas WHERE id = ?");
            $stmt->execute([$pergunta['id']]);
            $pergunta_data = $stmt->fetch();
            
            // Assumindo que a resposta correta está no formato "Verdadeiro" ou "Falso"
            $resposta_correta = stripos($pergunta_data['texto'], 'verdadeiro') !== false ? 'verdadeiro' : 'falso';
            
            if (strtolower($resposta) === $resposta_correta) {
                $pontuacao = $pergunta['pontos'];
            }
        } elseif ($pergunta['tipo'] === 'resposta_curta') {
            // Buscar respostas corretas possíveis
            $stmt = $pdo->prepare("SELECT texto FROM opcoes WHERE pergunta_id = ?");
            $stmt->execute([$pergunta['id']]);
            $respostas_corretas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Verificar se a resposta do usuário corresponde a alguma opção correta
            $resposta_normalizada = strtolower(trim($resposta));
            foreach ($respostas_corretas as $rc) {
                if ($resposta_normalizada === strtolower(trim($rc))) {
                    $pontuacao = $pergunta['pontos'];
                    break;
                }
            }
        }
        
        $pontuacao_total += $pontuacao;
        
        // Registrar resposta do usuário
        $stmt = $pdo->prepare("INSERT INTO respostas_usuarios 
                              (usuario_id, quiz_id, pergunta_id, resposta, pontuacao, data_resposta) 
                              VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $_SESSION['user_id'],
            $quiz_id,
            $pergunta['id'],
            $resposta,
            $pontuacao
        ]);
    }
    
    $pdo->commit();
    
    // Redirecionar para página de resultados
    header("Location: ../pages/quiz/results.php?quiz_id=$quiz_id&pontuacao=$pontuacao_total");
    exit;
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao processar respostas do quiz: " . $e->getMessage());
    header("Location: ../pages/quiz/take.php?quiz_id=$quiz_id&error=erro_processamento");
    exit;
}
?>