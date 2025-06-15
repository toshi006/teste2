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
$stmt = $pdo->prepare("SELECT *, usuario_id as post_autor_id FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

// Verificar permissões (apenas autor do post ou admin)
if (!$quiz || ($_SESSION['user_id'] != $quiz['post_autor_id'] && !hasRole('admin'))) {
    header("Location: /pages/home.php?error=permissao_negada");
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

$error = '';
$success = '';

// Processar o formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // 1. Atualizar informações básicas do quiz
        $stmt = $pdo->prepare("UPDATE quizzes SET titulo = ?, descricao = ? WHERE id = ?");
        $stmt->execute([
            $_POST['titulo'],
            $_POST['descricao'],
            $quiz_id
        ]);
        
        // 2. Processar cada pergunta
        foreach ($_POST['perguntas'] as $pergunta_id => $pergunta_data) {
            // Se for uma nova pergunta (id começa com 'new_'), inserir e obter o novo id
            if (strpos($pergunta_id, 'new_') === 0) {
                $stmt = $pdo->prepare("INSERT INTO perguntas (quiz_id, texto, tipo, pontos) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $quiz_id,
                    $pergunta_data['texto'],
                    $pergunta_data['tipo'],
                    $pergunta_data['pontos'] ?? 1
                ]);
                $real_pergunta_id = $pdo->lastInsertId();
            } else {
                // Atualizar a pergunta existente
                $stmt = $pdo->prepare("UPDATE perguntas SET texto = ?, tipo = ?, pontos = ? WHERE id = ?");
                $stmt->execute([
                    $pergunta_data['texto'],
                    $pergunta_data['tipo'],
                    $pergunta_data['pontos'] ?? 1,
                    $pergunta_id
                ]);
                $real_pergunta_id = $pergunta_id;
            }

            // Processar opções para perguntas de múltipla escolha
            if ($pergunta_data['tipo'] === 'multipla_escolha' && !empty($pergunta_data['opcoes'])) {
                foreach ($pergunta_data['opcoes'] as $opcao_id => $opcao_data) {
                    if (empty($opcao_data['texto'])) continue;

                    if (is_numeric($opcao_id) && $opcao_id > 0 && strpos($pergunta_id, 'new_') !== 0) {
                        // Opção existente - atualizar
                        $stmt = $pdo->prepare("UPDATE opcoes SET texto = ?, correta = ? WHERE id = ?");
                        $stmt->execute([
                            $opcao_data['texto'],
                            $opcao_data['correta'] ? 1 : 0,
                            $opcao_id
                        ]);
                    } else {
                        // Nova opção - inserir
                        $stmt = $pdo->prepare("INSERT INTO opcoes (pergunta_id, texto, correta) VALUES (?, ?, ?)");
                        $stmt->execute([
                            $real_pergunta_id,
                            $opcao_data['texto'],
                            $opcao_data['correta'] ? 1 : 0
                        ]);
                    }
                }
            }
        }
        
        $pdo->commit();
        $success = 'Quiz atualizado com sucesso!';
        header("Location: view.php?id=$quiz_id&success=1");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Erro ao atualizar quiz: " . $e->getMessage();
    }
}

$pageTitle = "Editar Quiz: " . htmlspecialchars($quiz['titulo']);
include '../../includes/header.php';
?>

<div class="container">
    <h1>Editar Quiz</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <form id="quiz-form" method="post" onsubmit="return validarQuizForm();">
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h4">Informações do Quiz</h2>
                <div class="mb-3">
                    <label for="titulo" class="form-label">Título do Quiz</label>
                    <input type="text" id="titulo" name="titulo" class="form-control" 
                           value="<?= htmlspecialchars($quiz['titulo']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea id="descricao" name="descricao" class="form-control" rows="3"><?= htmlspecialchars($quiz['descricao']) ?></textarea>
                </div>
            </div>
        </div>
        
        <div id="perguntas-container">
            <?php foreach ($perguntas as $index => $pergunta): ?>
                <div class="card mb-4 pergunta" data-index="<?= $index ?>">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="h5 mb-0">Pergunta #<?= $index + 1 ?></h3>
                        <button type="button" class="btn btn-sm btn-danger remove-pergunta">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="perguntas[<?= $pergunta['id'] ?>][id]" value="<?= $pergunta['id'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Texto da Pergunta</label>
                            <input type="text" name="perguntas[<?= $pergunta['id'] ?>][texto]" 
                                   class="form-control" value="<?= htmlspecialchars($pergunta['texto']) ?>" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Pergunta</label>
                                <select name="perguntas[<?= $pergunta['id'] ?>][tipo]" class="form-select tipo-pergunta" required>
                                    <option value="multipla_escolha" <?= $pergunta['tipo'] === 'multipla_escolha' ? 'selected' : '' ?>>Múltipla Escolha</option>
                                    <option value="verdadeiro_falso" <?= $pergunta['tipo'] === 'verdadeiro_falso' ? 'selected' : '' ?>>Verdadeiro/Falso</option>
                                    <option value="resposta_curta" <?= $pergunta['tipo'] === 'resposta_curta' ? 'selected' : '' ?>>Resposta Curta</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pontos</label>
                                <input type="number" name="perguntas[<?= $pergunta['id'] ?>][pontos]" 
                                       class="form-control" min="1" value="<?= $pergunta['pontos'] ?>">
                            </div>
                        </div>
                        
                        <div class="opcoes-container" style="<?= $pergunta['tipo'] === 'multipla_escolha' ? 'display: block;' : 'display: none;' ?>">
                            <h4 class="h6">Opções de Resposta</h4>
                            <div class="opcoes-list mb-2">
                                <?php if ($pergunta['tipo'] === 'multipla_escolha' && !empty($pergunta['opcoes'])): ?>
                                    <?php foreach ($pergunta['opcoes'] as $opcao): ?>
                                        <div class="opcao mb-2">
                                            <div class="input-group">
                                                <div class="input-group-text">
                                                    <input type="radio" name="perguntas[<?= $pergunta['id'] ?>][correta]" 
                                                           value="<?= $opcao['id'] ?>" class="form-check-input"
                                                           <?= $opcao['correta'] ? 'checked' : '' ?>>
                                                </div>
                                                <input type="text" name="perguntas[<?= $pergunta['id'] ?>][opcoes][<?= $opcao['id'] ?>][texto]" 
                                                       class="form-control" value="<?= htmlspecialchars($opcao['texto']) ?>" required>
                                                <input type="hidden" name="perguntas[<?= $pergunta['id'] ?>][opcoes][<?= $opcao['id'] ?>][correta]" 
                                                       value="<?= $opcao['correta'] ? '1' : '0' ?>">
                                                <button type="button" class="btn btn-outline-danger remove-opcao">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary add-opcao">
                                <i class="fas fa-plus"></i> Adicionar Opção
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <button type="button" id="add-pergunta" class="btn btn-secondary">
                <i class="fas fa-plus"></i> Adicionar Pergunta
            </button>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </div>
    </form>
</div>

<script>
// Template para uma nova pergunta
const perguntaTemplate = (index) => `
<div class="card mb-4 pergunta" data-index="${index}">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="h5 mb-0">Pergunta #${index + 1}</h3>
        <button type="button" class="btn btn-sm btn-danger remove-pergunta">
            <i class="fas fa-trash"></i>
        </button>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Texto da Pergunta</label>
            <input type="text" name="perguntas[new_${index}][texto]" class="form-control" required>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Tipo de Pergunta</label>
                <select name="perguntas[new_${index}][tipo]" class="form-select tipo-pergunta" required>
                    <option value="multipla_escolha">Múltipla Escolha</option>
                    <option value="verdadeiro_falso">Verdadeiro/Falso</option>
                    <option value="resposta_curta">Resposta Curta</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Pontos</label>
                <input type="number" name="perguntas[new_${index}][pontos]" class="form-control" min="1" value="1">
            </div>
        </div>
        
        <div class="opcoes-container" style="display: block;">
            <h4 class="h6">Opções de Resposta</h4>
            <div class="opcoes-list mb-2">
                <div class="opcao mb-2">
                    <div class="input-group">
                        <div class="input-group-text">
                            <input type="radio" name="perguntas[new_${index}][correta]" value="0" class="form-check-input" checked>
                        </div>
                        <input type="text" name="perguntas[new_${index}][opcoes][0][texto]" class="form-control" placeholder="Texto da opção">
                        <input type="hidden" name="perguntas[new_${index}][opcoes][0][correta]" value="1">
                    </div>
                </div>
                <div class="opcao mb-2">
                    <div class="input-group">
                        <div class="input-group-text">
                            <input type="radio" name="perguntas[new_${index}][correta]" value="1" class="form-check-input">
                        </div>
                        <input type="text" name="perguntas[new_${index}][opcoes][1][texto]" class="form-control" placeholder="Texto da opção">
                        <input type="hidden" name="perguntas[new_${index}][opcoes][1][correta]" value="0">
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary add-opcao">
                <i class="fas fa-plus"></i> Adicionar Opção
            </button>
        </div>
    </div>
</div>`;

// Configurar eventos para perguntas existentes
document.querySelectorAll('.pergunta').forEach(pergunta => {
    configurarPergunta(pergunta);
});

// Adicionar nova pergunta
document.getElementById('add-pergunta').addEventListener('click', () => {
    const container = document.getElementById('perguntas-container');
    const index = container.querySelectorAll('.pergunta').length;
    container.insertAdjacentHTML('beforeend', perguntaTemplate(index));
    configurarPergunta(container.lastElementChild);
});

// Função para configurar eventos de uma pergunta
function configurarPergunta(perguntaElement) {
    const tipoSelect = perguntaElement.querySelector('.tipo-pergunta');
    const opcoesContainer = perguntaElement.querySelector('.opcoes-container');
    
    // Alternar visibilidade das opções
    tipoSelect.addEventListener('change', function() {
        opcoesContainer.style.display = this.value === 'multipla_escolha' ? 'block' : 'none';
    });
    
    // Adicionar opção
    perguntaElement.querySelector('.add-opcao').addEventListener('click', function() {
        const opcoesList = perguntaElement.querySelector('.opcoes-list');
        const opcaoCount = opcoesList.children.length;
        const isNew = perguntaElement.dataset.index.includes('new_');
        const prefix = isNew ? 'new_' + perguntaElement.dataset.index.replace('new_', '') : '';
        
        const novaOpcao = document.createElement('div');
        novaOpcao.className = 'opcao mb-2';
        novaOpcao.innerHTML = `
            <div class="input-group">
                <div class="input-group-text">
                    <input type="radio" name="perguntas[${prefix}${perguntaElement.dataset.index}][correta]" 
                           value="${opcaoCount}" class="form-check-input">
                </div>
                <input type="text" name="perguntas[${prefix}${perguntaElement.dataset.index}][opcoes][${opcaoCount}][texto]" 
                       class="form-control" placeholder="Texto da opção">
                <input type="hidden" name="perguntas[${prefix}${perguntaElement.dataset.index}][opcoes][${opcaoCount}][correta]" value="0">
                <button type="button" class="btn btn-outline-danger remove-opcao">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        opcoesList.appendChild(novaOpcao);
        
        // Configurar remoção de opção
        novaOpcao.querySelector('.remove-opcao').addEventListener('click', function() {
            novaOpcao.remove();
        });
    });
    
    // Remover pergunta
    perguntaElement.querySelector('.remove-pergunta').addEventListener('click', function() {
        if (document.querySelectorAll('.pergunta').length > 1) {
            perguntaElement.remove();
        } else {
            alert('O quiz deve ter pelo menos uma pergunta.');
        }
    });
    
    // Atualizar valores corretos quando o radio é alterado
    perguntaElement.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const opcaoIndex = this.value;
            const opcoes = perguntaElement.querySelectorAll('.opcao');
            
            opcoes.forEach((opcao, i) => {
                const hiddenInput = opcao.querySelector('input[type="hidden"]');
                hiddenInput.value = i == opcaoIndex ? '1' : '0';
            });
        });
    });
    
    // Configurar remoção de opções existentes
    perguntaElement.querySelectorAll('.remove-opcao').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.opcao').remove();
        });
    });
}
// Validação customizada do formulário para garantir que todos os campos obrigatórios estejam preenchidos
function validarQuizForm() {
    let valido = true;
    let mensagens = [];

    // Validar perguntas
    document.querySelectorAll('.pergunta').forEach(pergunta => {
        const textoPergunta = pergunta.querySelector('input[type="text"][name*="[texto]"]');
        if (textoPergunta && !textoPergunta.value.trim()) {
            valido = false;
            mensagens.push('Preencha o texto de todas as perguntas.');
        }

        // Se for multipla escolha, validar opções
        const tipo = pergunta.querySelector('.tipo-pergunta');
        if (tipo && tipo.value === 'multipla_escolha') {
            const opcoes = pergunta.querySelectorAll('.opcoes-list .opcao input[type="text"]');
            let temOpcaoValida = false;
            opcoes.forEach(opcao => {
                if (opcao.value.trim()) temOpcaoValida = true;
            });
            if (!temOpcaoValida) {
                valido = false;
                mensagens.push('Cada pergunta de múltipla escolha deve ter pelo menos uma opção preenchida.');
            }
        }
    });

    if (!valido) {
        alert(mensagens.join('\n'));
    }
    return valido;
}
</script>

<?php include '../../includes/footer.php'; ?>