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
        $stmt_opcoes = $pdo->prepare("SELECT * FROM opcoes WHERE pergunta_id = ?");
        $stmt_opcoes->execute([$pergunta['id']]);
        $pergunta['opcoes'] = $stmt_opcoes->fetchAll(); // Anexa todas as opções à pergunta
        
        // Encontrar a opção correta para múltipla escolha
        foreach ($pergunta['opcoes'] as $opcao) {
            if ($opcao['correta']) {
                $pergunta['resposta_correta'] = $opcao['texto']; // Anexa o texto da resposta correta
                break; // Sai do loop interno, pois encontrou a correta
            }
        }
    } elseif ($pergunta['tipo'] === 'verdadeiro_falso') {
        // --- LÓGICA PARA VERDADEIRO/FALSO ---
        $stmt_vf_correta = $pdo->prepare("SELECT texto FROM opcoes WHERE pergunta_id = ? AND correta = 1");
        $stmt_vf_correta->execute([$pergunta['id']]);
        $resposta_correta_vf = $stmt_vf_correta->fetchColumn(); // Obtém o texto ('Verdadeiro' ou 'Falso')
        
        if ($resposta_correta_vf) {
            $pergunta['resposta_correta'] = $resposta_correta_vf; // Anexa o texto da resposta correta
        }
        // Você também pode querer buscar todas as opções ('Verdadeiro', 'Falso') para exibir
        $stmt_vf_opcoes = $pdo->prepare("SELECT * FROM opcoes WHERE pergunta_id = ?");
        $stmt_vf_opcoes->execute([$pergunta['id']]);
        $pergunta['opcoes'] = $stmt_vf_opcoes->fetchAll(); // Anexa as opções 'Verdadeiro'/'Falso'
        // --- FIM LÓGICA PARA VERDADEIRO/FALSO ---
    } elseif ($pergunta['tipo'] === 'resposta_curta') {
        // --- LÓGICA PARA RESPOSTA CURTA (Assumindo uma única resposta correta com 'correta=1') ---
        $stmt_rc_correta = $pdo->prepare("SELECT texto FROM opcoes WHERE pergunta_id = ? AND correta = 1");
        $stmt_rc_correta->execute([$pergunta['id']]);
        $resposta_correta_rc = $stmt_rc_correta->fetchColumn();
        
        if ($resposta_correta_rc) {
            $pergunta['resposta_correta'] = $resposta_correta_rc; // Anexa o texto da resposta correta
        }
        // Se a resposta curta pode ter MÚLTIPLAS respostas corretas, você faria:
        // $stmt_rc_todas = $pdo->prepare("SELECT texto FROM opcoes WHERE pergunta_id = ?");
        // $stmt_rc_todas->execute([$pergunta['id']]);
        // $pergunta['respostas_corretas_possiveis'] = $stmt_rc_todas->fetchAll(PDO::FETCH_COLUMN);
        // Ou adaptar de acordo com como você marca múltiplas respostas corretas na tabela 'opcoes'
        // --- FIM LÓGICA PARA RESPOSTA CURTA ---
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

            $pergunta_correspondente = null;
            foreach ($perguntas as $p) {
                if ($p['id'] == $resposta['pergunta_id']) {
                    $pergunta_correspondente = $p;
                    break;
                }
            } if ($pergunta_correspondente && isset($pergunta_correspondente['resposta_correta'])) {
                echo htmlspecialchars($pergunta_correspondente['resposta_correta']);
            } elseif ($pergunta_correspondente && $pergunta_correspondente['tipo'] === 'resposta_curta' && isset($pergunta_correspondente['respostas_corretas_possiveis'])) {
                echo htmlspecialchars(implode(', ', $pergunta_correspondente['respostas_corretas_possiveis']));
            } else {
                echo "Resposta correta não disponível para este tipo de pergunta ou não encontrada.";
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
        <a href="../../pages/quiz/list.php" class="btn btn-primary">Voltar para Lista de Quizzes</a>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>