<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: ../../pages/login.php");
    exit;
}

$quiz_id = $_GET['id'] ?? null;
if (!$quiz_id) {
    header("Location: ../../pages/home.php");
    exit;
}

// Buscar informações do quiz
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: ../../pages/home.php?error=quiz_nao_encontrado");
    exit;
}

// Verificar se o usuário já respondeu este quiz
$stmt = $pdo->prepare("SELECT id FROM respostas_usuarios 
                      WHERE usuario_id = ? AND quiz_id = ? 
                      LIMIT 1");
$stmt->execute([$_SESSION['user_id'], $quiz_id]);
if ($stmt->fetch()) {
    header("Location: ../../pages/quiz/results.php?id=$quiz_id");
    exit;
}

// Buscar perguntas do quiz
$stmt = $pdo->prepare("SELECT * FROM perguntas WHERE quiz_id = ? ORDER BY id");
$stmt->execute([$quiz_id]);
$perguntas = $stmt->fetchAll();

foreach ($perguntas as &$pergunta) {
    if ($pergunta['tipo'] === 'multipla_escolha') {
        $stmt = $pdo->prepare("SELECT * FROM opcoes WHERE pergunta_id = ?");
        $stmt->execute([$pergunta['id']]);
        $pergunta['opcoes'] = $stmt->fetchAll();
    }
}
unset($pergunta);
$action = $_POST['action'] ?? 'responder';


$pageTitle = "Responder Quiz: " . htmlspecialchars($quiz['titulo']);
include '../../includes/header.php';
?>

<div class="container quiz-container">
    <h1><?= htmlspecialchars($quiz['titulo']) ?></h1>
    <p class="lead">Post relacionado: <?= htmlspecialchars($quiz['titulo']) ?></p>
    
    <?php if (!empty($quiz['descricao'])): ?>
        <div class="quiz-description mb-4">
            <?= nl2br(htmlspecialchars($quiz['descricao'])) ?>
        </div>
    <?php endif; ?>
    
    <form id="quiz-form" action="../../process/quiz_process.php" method="post">
        <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
        
        <?php foreach ($perguntas as $index => $pergunta): ?>
            <div class="card mb-4 pergunta">
                <div class="card-body">
                    <h3 class="h5">
                        <span class="question-number"><?= $index + 1 ?>.</span>
                        <?= htmlspecialchars($pergunta['texto']) ?>
                        <small class="text-muted">(<?= $pergunta['pontos'] ?> ponto<?= $pergunta['pontos'] > 1 ? 's' : '' ?>)</small>
                    </h3>
                    
                    <?php if ($pergunta['tipo'] === 'multipla_escolha'): ?>
                        <div class="opcoes mt-3">
                            <?php foreach ($pergunta['opcoes'] as $opcao): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" 
                                           name="resposta[<?= $pergunta['id'] ?>]" 
                                           id="opcao_<?= $opcao['id'] ?>" 
                                           value="<?= $opcao['id'] ?>" required>
                                    <label class="form-check-label" for="opcao_<?= $opcao['id'] ?>">
                                        <?= htmlspecialchars($opcao['texto']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($pergunta['tipo'] === 'verdadeiro_falso'): ?>
                        <div class="opcoes mt-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" 
                                       name="resposta[<?= $pergunta['id'] ?>]" 
                                       id="vf_<?= $pergunta['id'] ?>_true" 
                                       value="Verdadeiro" required>
                                <label class="form-check-label" for="vf_<?= $pergunta['id'] ?>_true">
                                    Verdadeiro
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" 
                                       name="resposta[<?= $pergunta['id'] ?>]" 
                                       id="vf_<?= $pergunta['id'] ?>_false" 
                                       value="Falso">
                                <label class="form-check-label" for="vf_<?= $pergunta['id'] ?>_false">
                                    Falso
                                </label>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="form-group mt-3">
                            <input type="text" class="form-control" 
                                   name="resposta[<?= $pergunta['id'] ?>]" 
                                   placeholder="Digite sua resposta..." required>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg">Enviar Respostas</button>
        </div>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>