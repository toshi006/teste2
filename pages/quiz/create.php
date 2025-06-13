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
$quiz_id = null;

// Obtém o ID do usuário logado
$user_id = $_SESSION['user_id'] ?? null;

// Processar o formulário de criação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // 1. Criar o quiz (agora com usuario_id)
        $stmt = $pdo->prepare("INSERT INTO quizzes (titulo, descricao, data_criacao, usuario_id) 
                     VALUES (?, ?, NOW(), ?)");
        $stmt->execute([
            $_POST['titulo'],
            $_POST['descricao'],
            $user_id
        ]);
        $quiz_id = $pdo->lastInsertId();
        // 2. Processar cada pergunta
        foreach ($_POST['perguntas'] as $pergunta) {
            if (empty($pergunta['texto'])) continue;
            
            $stmt = $pdo->prepare("INSERT INTO perguntas (quiz_id, texto, tipo, pontos) 
                                 VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $quiz_id,
                $pergunta['texto'],
                $pergunta['tipo'],
                $pergunta['pontos'] ?? 1
            ]);
            $pergunta_id = $pdo->lastInsertId();
            
            // 3. Processar opções para perguntas de múltipla escolha
            if ($pergunta['tipo'] === 'multipla_escolha' && !empty($pergunta['opcoes'])) {
                foreach ($pergunta['opcoes'] as $opcao) {
                    if (empty($opcao['texto'])) continue;
                    
                    $stmt = $pdo->prepare("INSERT INTO opcoes (pergunta_id, texto, correta) 
                                         VALUES (?, ?, ?)");
                    $stmt->execute([
                        $pergunta_id,
                        $opcao['texto'],
                        $opcao['correta'] ? 1 : 0
                    ]);
                }
            }
        }
        
        $pdo->commit();
        header("Location: view.php?id=$quiz_id&success=1");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Erro ao criar quiz: " . $e->getMessage();
    }
}

$pageTitle = "Criar Novo Quiz";
include '../../includes/header.php';
?>
<style>
/* Container principal */
.quizz-container {
    max-width: 900px;
    margin: 6rem auto;
    padding: 1rem;
    position: relative;
    padding-top: 3.5rem;
    background: #fff;
}

/* Título da página */
.quizz-container h1 {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 0;
    text-align: left;
    position: absolute;
    top: 1rem;
    left: 1.5rem;
    margin-top: 0;
    margin-left: 0;
    z-index: 2;
    align-items: flex-start;
    justify-content: flex-start;
}

/* Formulário logo abaixo do h1 */
#quiz-form {
    margin-top: 2.8rem;
    margin-left: 0;
}

/* Cartões principais */
.card {
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    margin-bottom: 1.5rem;
}

/* Cabeçalho das perguntas */
.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    padding: 1rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Corpo das perguntas */
.card-body {
    padding: 1.25rem;
}

/* Botões principais */
#add-pergunta, 
#quiz-form .btn-primary {
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.95rem;
}

#add-pergunta i,
.add-opcao i {
    margin-right: 0.3rem;
}

/* Botões de ação */
.remove-pergunta,
.remove-opcao {
    margin-left: 0.5rem;
    padding: 0.4rem 0.6rem;
}

/* Campos de formulário */
.form-label {
    font-weight: 500;
    margin-bottom: 0.3rem;
}

.form-control,
.form-select {
    border-radius: 8px;
}

/* Opções de múltipla escolha */
.opcoes-container {
    margin-top: 1rem;
    padding: 1rem;
    border: 1px dashed #ccc;
    border-radius: 8px;
    background-color: #fdfdfd;
}

.opcoes-container h4 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

/* Input com radio */
.opcao .input-group {
    align-items: center;
    display: flex;
    gap: 0.5rem;
}

.opcao input[type="text"] {
    flex: 1;
}

/* Estilo do botão de adicionar opção */
.add-opcao {
    margin-top: 0.5rem;
}

/* Responsividade */
@media (max-width: 576px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }

    .card-header h3 {
        font-size: 1rem;
    }
    .quizz-container {
        padding: 0.5rem;
        padding-top: 3.5rem;
    }
    .quizz-container h1 {
        font-size: 1.2rem;
        left: 0.5rem;
    }
}
</style>

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
        
        <div id="perguntas-container">
            <!-- As perguntas serão adicionadas aqui via JavaScript -->
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <button type="button" id="add-pergunta" class="btn btn-secondary">
                <i class="fas fa-plus"></i> Adicionar Pergunta
            </button>
            <button type="submit" class="btn btn-primary">Salvar Quiz</button>
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

// Contador de perguntas
let perguntaCount = 0;

// Adicionar uma nova pergunta
document.getElementById('add-pergunta').addEventListener('click', () => {
    const container = document.getElementById('perguntas-container');
    container.insertAdjacentHTML('beforeend', perguntaTemplate(perguntaCount));
    
    // Configurar eventos para a nova pergunta
    const novaPergunta = container.lastElementChild;
    configurarPergunta(novaPergunta);
    
    perguntaCount++;
});

// Configurar eventos para uma pergunta
function configurarPergunta(perguntaElement) {
    const index = perguntaElement.dataset.index;
    
    // Alternar visibilidade das opções baseado no tipo
    const tipoSelect = perguntaElement.querySelector('.tipo-pergunta');
    const opcoesContainer = perguntaElement.querySelector('.opcoes-container');
    
    tipoSelect.addEventListener('change', function() {
        opcoesContainer.style.display = this.value === 'multipla_escolha' ? 'block' : 'none';
    });
    
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
        
        // Configurar evento de remoção
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
}

// Adicionar a primeira pergunta automaticamente
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('add-pergunta').click();
});
</script>

<?php include '../../includes/footer.php'; ?>