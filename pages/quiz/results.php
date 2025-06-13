<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: /pages/login.php");
    exit;
}

$quiz_id = $_GET['id'] ?? null;
if (!$quiz_id) {
    header("Location: /pages/home.php");
    exit;
}

// Buscar informações do quiz
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: /pages/home.php?error=quiz_nao_encontrado");
    exit;
}

// Buscar respostas do usuário
$stmt = $pdo->prepare("SELECT r.*, p.texto as pergunta_texto, p.tipo as pergunta_tipo, p.pontos
                      FROM respostas_usuarios r
                      JOIN perguntas p ON r.pergunta_id = p.id
                      WHERE r.usuario_id = ? AND r.quiz_id = ?");
$stmt->execute([$_SESSION['user_id'], $quiz_id]);
$respostas = $stmt->fetchAll();

// Calcular pontuação total
$pontuacao_total = array_sum(array_column($respostas, 'pontuacao'));

// Buscar perguntas para mostrar as respostas corretas
$stmt = $pdo->prepare("SELECT p.* 
                      FROM perguntas p
                      WHERE p.quiz_id = ?");
$stmt->execute([$quiz_id]);
$perguntas = $stmt->fetchAll();

foreach ($perguntas as &$pergunta) {
    if ($pergunta['tipo'] === 'multipla_escolha') {
        $stmt = $pdo->prepare("SELECT * FROM opcoes WHERE pergunta_id = ?");
        $stmt->execute([$pergunta['id']]);
        $pergunta['opcoes'] = $stmt->fetchAll();
        
        // Encontrar a opção correta
        foreach ($pergunta['opcoes'] as $opcao) {
            if ($opcao['correta']) {
                $pergunta['resposta_correta'] = $opcao['texto'];
                break;
            }
        }
    }
}
unset($pergunta);

$pageTitle = "Resultados: " . htmlspecialchars($quiz['titulo']);
include '../../includes/header.php';
?>

<div class="container quiz-results">
    <h1>Resultados do Quiz</h1>
    <h2 class="h4"><?= htmlspecialchars($quiz['titulo']) ?></h2>
    
    <div class="result-summary card mb-4">
        <div class="card-body text-center">
            <h3 class="h5">Sua Pontuação</h3>
            <div class="display-4 text-primary"><?= $pontuacao_total ?> pontos</div>
            <p class="text-muted"><?= count($respostas) ?> perguntas respondidas</p>
        </div>
    </div>
    
    <div class="results-details">
        <?php foreach ($respostas as $index => $resposta): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h4 class="h5">
                        <span class="question-number"><?= $index + 1 ?>.</span>
                        <?= htmlspecialchars($resposta['pergunta_texto']) ?>
                        <small class="text-muted">(<?= $resposta['pontos'] ?> ponto<?= $resposta['pontos'] > 1 ? 's' : '' ?>)</small>
                    </h4>
                    
                    <div class="user-answer mt-3">
                        <p class="mb-1"><strong>Sua resposta:</strong></p>
                        <div class="ps-3">
                            <?php if ($resposta['pergunta_tipo'] === 'multipla_escolha'): ?>
                                <?php 
                                $stmt = $pdo->prepare("SELECT texto FROM opcoes WHERE id = ?");
                                $stmt->execute([$resposta['resposta']]);
                                $opcao = $stmt->fetch();
                                ?>
                                <p><?= htmlspecialchars($opcao['texto'] ?? 'Resposta inválida') ?></p>
                            <?php else: ?>
                                <p><?= htmlspecialchars($resposta['resposta']) ?></p>
                            <?php endif; ?>
                            
                            <p class="mb-0">
                                <span class="badge bg-<?= $resposta['pontuacao'] > 0 ? 'success' : 'danger' ?>">
                                    <?= $resposta['pontuacao'] > 0 ? 'Correta' : 'Incorreta' ?>
                                </span>
                                <?php if ($resposta['pontuacao'] > 0): ?>
                                    +<?= $resposta['pontuacao'] ?> ponto<?= $resposta['pontuacao'] > 1 ? 's' : '' ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($resposta['pontuacao'] == 0): ?>
                        <div class="correct-answer mt-3">
                            <p class="mb-1"><strong>Resposta correta:</strong></p>
                            <div class="ps-3">
                                <?php 
                                $pergunta_correspondente = array_filter($perguntas, function($p) use ($resposta) {
                                    return $p['id'] == $resposta['pergunta_id'];
                                });
                                $pergunta_correspondente = reset($pergunta_correspondente);
                                
                                if ($resposta['pergunta_tipo'] === 'multipla_escolha' && isset($pergunta_correspondente['resposta_correta'])) {
                                    echo htmlspecialchars($pergunta_correspondente['resposta_correta']);
                                } elseif ($resposta['pergunta_tipo'] === 'verdadeiro_falso') {
                                    echo "Verdadeiro"; // Isso deveria vir do banco de dados
                                } else {
                                    echo "Resposta correta não disponível";
                                }
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-4">
        <a href="/pages/quiz/list.php" class="btn btn-primary">Voltar para Lista de Quizzes</a>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>