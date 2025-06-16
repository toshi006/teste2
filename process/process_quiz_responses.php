<?php
require_once __DIR__ .'../../includes/config.php';
// Obter dados do formulário
$quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);

// Validações básicas
if (!$quiz_id) {
    header("Location: ../pages/home.php?error=quiz_invalido");
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
            $stmt = $pdo->prepare("SELECT texto FROM opcoes WHERE pergunta_id = ? AND correta = 1");
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
    header("Location: ../pages/quiz/results.php?id=$quiz_id&pontuacao=$pontuacao_total");
    exit;
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao processar respostas do quiz: " . $e->getMessage());
    header("Location: ../pages/quiz/take.php?quiz_id=$quiz_id&error=erro_processamento");
    exit;
}