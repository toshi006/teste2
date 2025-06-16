<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Permissão apenas para professores ou admins
if (!hasAnyRole(['professor', 'admin'])) {
    header("Location: ../../pages/home.php");
    exit;
}

$error = '';
$quiz_id = null;
$user_id = $_SESSION['user_id'] ?? null;

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // 1. Criar quiz
        $stmt = $pdo->prepare("INSERT INTO quizzes (titulo, descricao, data_criacao, usuario_id) VALUES (?, ?, NOW(), ?)");
        $stmt->execute([$_POST['titulo'], $_POST['descricao'], $user_id]);
        $quiz_id = $pdo->lastInsertId();

        // 2. Processar perguntas
        foreach ($_POST['perguntas'] as $pergunta) {
            if (empty($pergunta['texto'])) continue;

            $stmt = $pdo->prepare("INSERT INTO perguntas (quiz_id, texto, tipo, pontos) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $quiz_id,
                $pergunta['texto'],
                $pergunta['tipo'],
                $pergunta['pontos'] ?? 1
            ]);
            $pergunta_id = $pdo->lastInsertId();

            // 3. Opções múltipla escolha
            if ($pergunta['tipo'] === 'multipla_escolha' && !empty($pergunta['opcoes'])) {
                foreach ($pergunta['opcoes'] as $opcao) {
                    if (empty($opcao['texto'])) continue;
                    $stmt = $pdo->prepare("INSERT INTO opcoes (pergunta_id, texto, correta) VALUES (?, ?, ?)");
                    $stmt->execute([
                        $pergunta_id,
                        $opcao['texto'],
                        $opcao['correta'] ? 1 : 0
                    ]);
                }
            }

            // 4. Verdadeiro/Falso
            if ($pergunta['tipo'] === 'verdadeiro_falso') {
                $correta = isset($pergunta['correta']) ? $pergunta['correta'] : 'verdadeiro';
                $opcoes_vf = [
                    ['texto' => 'Verdadeiro', 'correta' => ($correta === 'verdadeiro' ? 1 : 0)],
                    ['texto' => 'Falso', 'correta' => ($correta === 'falso' ? 1 : 0)]
                ];
                foreach ($opcoes_vf as $opcao) {
                    $stmt = $pdo->prepare("INSERT INTO opcoes (pergunta_id, texto, correta) VALUES (?, ?, ?)");
                    $stmt->execute([$pergunta_id, $opcao['texto'], $opcao['correta']]);
                }
            }

            // 5. Resposta curta
            if ($pergunta['tipo'] === 'resposta_curta' && !empty($pergunta['resposta_correta'])) {
                $stmt = $pdo->prepare("INSERT INTO opcoes (pergunta_id, texto, correta) VALUES (?, ?, 1)");
                $stmt->execute([$pergunta_id, $pergunta['resposta_correta']]);
            }
        }

        $pdo->commit();
        header("Location: ../../pages/user/my_quizzes.php?success=1");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Erro ao criar quiz: " . $e->getMessage();
    }
}

$pageTitle = "Criar Novo Quiz";
include '../../includes/header.php';
?>

<div class="quizz-container">
    <h1>Criar Novo Quiz</h1>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form id="quiz-form" method="post">
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h4">Informações do Quiz</h2>
                <div class="mb-3">
                    <label for="titulo" class="form-label">Título do Quiz</label>
                    <input type="text" id="titulo" name="titulo" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea id="descricao" name="descricao" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </div>
        <div id="perguntas-container"></div>
        <div class="d-flex justify-content-between mt-4">
            <button type="button" id="add-pergunta" class="btn btn-secondary">
                <i class="fas fa-plus"></i> Adicionar Pergunta
            </button>
            <button type="submit" class="btn btn-primary">Salvar Quiz</button>
        </div>
    </form>
</div>

<script>
// --- TEMPLATE DE PERGUNTA ---
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
            <input type="text" name="perguntas[${index}][texto]" class="form-control" required>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Tipo de Pergunta</label>
                <select name="perguntas[${index}][tipo]" class="form-select tipo-pergunta" required>
                    <option value="multipla_escolha">Múltipla Escolha</option>
                    <option value="verdadeiro_falso">Verdadeiro/Falso</option>
                    <option value="resposta_curta">Resposta Curta</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Pontos</label>
                <input type="number" name="perguntas[${index}][pontos]" class="form-control" min="1" value="1">
            </div>
        </div>
        <div class="opcoes-container" style="display: block;">
            <h4 class="h6">Opções de Resposta</h4>
            <div class="opcoes-list mb-2">
                <div class="opcao mb-2">
                    <div class="input-group">
                        <div class="input-group-text">
                            <input type="radio" name="perguntas[${index}][correta]" value="0" class="form-check-input" checked>
                        </div>
                        <input type="text" name="perguntas[${index}][opcoes][0][texto]" class="form-control" placeholder="Texto da opção" required>
                        <input type="hidden" name="perguntas[${index}][opcoes][0][correta]" value="1">
                    </div>
                </div>
                <div class="opcao mb-2">
                    <div class="input-group">
                        <div class="input-group-text">
                            <input type="radio" name="perguntas[${index}][correta]" value="1" class="form-check-input">
                        </div>
                        <input type="text" name="perguntas[${index}][opcoes][1][texto]" class="form-control" placeholder="Texto da opção" required>
                        <input type="hidden" name="perguntas[${index}][opcoes][1][correta]" value="0">
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary add-opcao">
                <i class="fas fa-plus"></i> Adicionar Opção
            </button>
        </div>
    </div>
</div>`;

// --- FUNÇÕES AUXILIARES ---

// Adiciona campo de seleção para verdadeiro/falso
function configurarPerguntaVF(perguntaElement, index) {
    const opcoesContainer = perguntaElement.querySelector('.opcoes-container');
    opcoesContainer.innerHTML = `
        <h4 class="h6">Selecione a resposta correta</h4>
        <div class="mb-2">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="perguntas[${index}][correta]" id="vf-verdadeiro-${index}" value="verdadeiro" checked>
                <label class="form-check-label" for="vf-verdadeiro-${index}">Verdadeiro</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="perguntas[${index}][correta]" id="vf-falso-${index}" value="falso">
                <label class="form-check-label" for="vf-falso-${index}">Falso</label>
            </div>
        </div>
    `;
}

// Função para configurar o botão de adicionar opção e eventos de rádio
function configurarOpcoesMultiplaEscolha(perguntaElement, index) {
    // Adicionar opção
    perguntaElement.querySelector('.add-opcao').addEventListener('click', function() {
        const opcoesList = perguntaElement.querySelector('.opcoes-list');
        const opcaoCount = opcoesList.children.length;
        const novaOpcao = document.createElement('div');
        novaOpcao.className = 'opcao mb-2';
        novaOpcao.innerHTML = `
            <div class="input-group">
                <div class="input-group-text">
                    <input type="radio" name="perguntas[${index}][correta]" value="${opcaoCount}" class="form-check-input">
                </div>
                <input type="text" name="perguntas[${index}][opcoes][${opcaoCount}][texto]" class="form-control" placeholder="Texto da opção" required>
                <input type="hidden" name="perguntas[${index}][opcoes][${opcaoCount}][correta]" value="0">
                <button type="button" class="btn btn-outline-danger remove-opcao">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        opcoesList.appendChild(novaOpcao);
        novaOpcao.querySelector('.remove-opcao').addEventListener('click', function() {
            novaOpcao.remove();
        });

        // Atualizar valores corretos para os novos radios
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
    });

    // Atualizar valores corretos para os radios existentes
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
}
function atualizarIndicesPerguntas() {
    const perguntas = document.querySelectorAll('.pergunta'); // Pega todas as perguntas visíveis
    perguntas.forEach((perguntaElement, newIndex) => {
        const oldIndex = perguntaElement.dataset.index; // Pega o número antigo da pergunta

        // 1. Atualiza o número no título da pergunta (Ex: "Pergunta #1")
        perguntaElement.querySelector('.card-header h3').textContent = `Pergunta #${newIndex + 1}`;

        // 2. Atualiza o atributo 'data-index' (importante para o JS saber o número certo)
        perguntaElement.dataset.index = newIndex;

        // 3. Muda os nomes dos campos para o PHP receber certo (Ex: "perguntas[0][texto]" vira "perguntas[1][texto]")
        perguntaElement.querySelectorAll('[name*="perguntas["]').forEach(input => {
            const currentName = input.getAttribute('name');
            const newName = currentName.replace(`perguntas[${oldIndex}]`, `perguntas[${newIndex}]`);
            input.setAttribute('name', newName);
        });

        // 4. Se for Verdadeiro/Falso, ajusta os IDs internos para não dar conflito
        const tipoSelect = perguntaElement.querySelector('.tipo-pergunta');
        if (tipoSelect.value === 'verdadeiro_falso') {
            configurarPerguntaVF(perguntaElement, newIndex); // Chama sua função existente
        }
    });
}
// Configura eventos para cada pergunta
function configurarPergunta(perguntaElement) {
    const index = perguntaElement.dataset.index;
    const tipoSelect = perguntaElement.querySelector('.tipo-pergunta');
    const opcoesContainer = perguntaElement.querySelector('.opcoes-container');
    const opcoesOriginal = opcoesContainer.innerHTML;

    tipoSelect.addEventListener('change', function() {
        if (this.value === 'multipla_escolha') {
            opcoesContainer.innerHTML = opcoesOriginal;
            opcoesContainer.style.display = 'block';
            opcoesContainer.querySelectorAll('input[type="text"]').forEach(input => input.required = true);
            configurarOpcoesMultiplaEscolha(perguntaElement, index);
        } else if (this.value === 'verdadeiro_falso') {
            configurarPerguntaVF(perguntaElement, index);
            opcoesContainer.style.display = 'block';
        } else {
            opcoesContainer.innerHTML = '';
            opcoesContainer.style.display = 'none';
        }
    });

    // Configuração inicial
    if (tipoSelect.value === 'multipla_escolha') {
        configurarOpcoesMultiplaEscolha(perguntaElement, index);
    } else if (tipoSelect.value === 'verdadeiro_falso') {
        configurarPerguntaVF(perguntaElement, index);
        opcoesContainer.style.display = 'block';
    }

    // Remover pergunta
    perguntaElement.querySelector('.remove-pergunta').addEventListener('click', function() {
        if (document.querySelectorAll('.pergunta').length > 1) {
            perguntaElement.remove();
        } else {
            alert('O quiz deve ter pelo menos uma pergunta.');
        }
    });
}

// Adiciona nova pergunta ao DOM
document.getElementById('add-pergunta').addEventListener('click', () => {
    const container = document.getElementById('perguntas-container');
    // Não precisamos mais de 'perguntaCount' aqui. Pegamos o próximo índice pela quantidade de perguntas.
    const newQuestionIndex = document.querySelectorAll('.pergunta').length; 

    container.insertAdjacentHTML('beforeend', perguntaTemplate(newQuestionIndex));
    const novaPergunta = container.lastElementChild;
    configurarPergunta(novaPergunta);

    // CHAME A FUNÇÃO AQUI: Organiza os números de todas as perguntas
    atualizarIndicesPerguntas(); 
});
// Validação de campos visíveis antes do submit
document.getElementById('quiz-form').addEventListener('submit', function(e) {
    document.querySelectorAll('.pergunta').forEach(pergunta => {
        const tipo = pergunta.querySelector('.tipo-pergunta').value;
        if (tipo !== 'multipla_escolha') {
            pergunta.querySelectorAll('.opcoes-container input[type="text"]').forEach(input => input.required = false);
        } else {
            pergunta.querySelectorAll('.opcoes-container input[type="text"]').forEach(input => input.required = true);
        }
    });
});

// Adiciona a primeira pergunta automaticamente
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('add-pergunta').click();
});
</script>

<?php include '../../includes/footer.php'; ?>
