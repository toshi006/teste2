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
// Não precisamos mais de 'resposta_vf' ou 'resposta_curta' aqui
$stmt = $pdo->prepare("SELECT id, quiz_id, texto, tipo, pontos FROM perguntas WHERE quiz_id = ? ORDER BY id");
$stmt->execute([$quiz_id]);
$perguntas = $stmt->fetchAll();

foreach ($perguntas as &$pergunta) {
    // Busca opções para MÚLTIPLA ESCOLHA E VERDADEIRO/FALSO
    if ($pergunta['tipo'] === 'multipla_escolha' || $pergunta['tipo'] === 'verdadeiro_falso') {
        $stmt = $pdo->prepare("SELECT id, pergunta_id, texto, correta FROM opcoes WHERE pergunta_id = ?");
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
        // Manter controle das perguntas que devem permanecer no quiz
        $perguntas_a_manter = [];

        foreach ($_POST['perguntas'] as $pergunta_temp_id => $pergunta_data) {
            $real_pergunta_id = null; // ID real da pergunta no BD

            // ----------------------------------------------------
            // Lógica de ATUALIZAÇÃO/INSERÇÃO da PERGUNTA
            // ----------------------------------------------------
            if (strpos($pergunta_temp_id, 'new_') === 0) {
                // Nova pergunta - INSERIR
                $stmt = $pdo->prepare("INSERT INTO perguntas (quiz_id, texto, tipo, pontos) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $quiz_id,
                    $pergunta_data['texto'],
                    $pergunta_data['tipo'],
                    $pergunta_data['pontos'] ?? 1
                ]);
                $real_pergunta_id = $pdo->lastInsertId();
            } else {
                // Pergunta existente - ATUALIZAR
                $real_pergunta_id = $pergunta_temp_id;
                $stmt = $pdo->prepare("UPDATE perguntas SET texto = ?, tipo = ?, pontos = ? WHERE id = ?");
                $stmt->execute([
                    $pergunta_data['texto'],
                    $pergunta_data['tipo'],
                    $pergunta_data['pontos'] ?? 1,
                    $real_pergunta_id
                ]);
            }
            $perguntas_a_manter[] = $real_pergunta_id; // Adicionar ao array de perguntas a manter

            // ----------------------------------------------------
            // Lógica de GERENCIAMENTO das OPÇÕES (para Múltipla Escolha e V/F)
            // ----------------------------------------------------

            // Primeiro, deletar todas as opções antigas para esta pergunta
            // Vamos gerenciar isso de forma mais robusta para evitar deletar e reinserir desnecessariamente
            // e para garantir que as opções V/F corretas sejam mantidas.
            $opcoes_existentes = [];
            $stmt = $pdo->prepare("SELECT id, texto, correta FROM opcoes WHERE pergunta_id = ?");
            $stmt->execute([$real_pergunta_id]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $opt) {
                $opcoes_existentes[$opt['id']] = $opt;
            }
            
            $opcoes_a_manter = []; // Para trackear opções que foram atualizadas e não devem ser deletadas

            if ($pergunta_data['tipo'] === 'multipla_escolha' && !empty($pergunta_data['opcoes'])) {
                // Processar opções para Múltipla Escolha
                $correta_multipla_id = $pergunta_data['correta_multipla'] ?? null;

                foreach ($pergunta_data['opcoes'] as $opcao_temp_id => $opcao_info) {
                    if (empty($opcao_info['texto'])) continue; // Ignorar opções sem texto

                    $correta = (string)$opcao_temp_id === (string)$correta_multipla_id ? 1 : 0;
                    $real_opcao_id = null;

                    if (strpos($opcao_temp_id, 'new_opcao_') === 0 || strpos($opcao_temp_id, 'new_mc_option') === 0) {
                        // Nova opção - INSERIR
                        $stmt = $pdo->prepare("INSERT INTO opcoes (pergunta_id, texto, correta) VALUES (?, ?, ?)");
                        $stmt->execute([
                            $real_pergunta_id,
                            $opcao_info['texto'],
                            $correta
                        ]);
                        $real_opcao_id = $pdo->lastInsertId();
                    } else {
                        // Opção existente - ATUALIZAR
                        $real_opcao_id = $opcao_temp_id;
                        $stmt = $pdo->prepare("UPDATE opcoes SET texto = ?, correta = ? WHERE id = ? AND pergunta_id = ?");
                        $stmt->execute([
                            $opcao_info['texto'],
                            $correta,
                            $real_opcao_id,
                            $real_pergunta_id
                        ]);
                    }
                    if ($real_opcao_id) {
                        $opcoes_a_manter[] = $real_opcao_id;
                    }
                }
            } elseif ($pergunta_data['tipo'] === 'verdadeiro_falso') {
                // Processar opções para Verdadeiro/Falso
                // O campo correta_multipla indica qual opção foi marcada como correta
                $correta_vf_id = $pergunta_data['correta_multipla'] ?? null;

                // Mapeia IDs existentes para textos V/F
                $vf_opcoes_map = []; // [texto => id_opcao]
                foreach ($opcoes_existentes as $opt_id => $opt_data) {
                    $vf_opcoes_map[strtolower($opt_data['texto'])] = $opt_id;
                }

                // Determina os IDs das opções "Verdadeiro" e "Falso" (podem ser 'new_true_option', 'new_false_option' ou IDs reais)
                $true_id = null;
                $false_id = null;
                foreach ($pergunta_data['opcoes'] as $opcao_id => $opcao_info) {
                    if (strtolower($opcao_info['texto']) === 'verdadeiro') {
                        $true_id = $opcao_id;
                    } elseif (strtolower($opcao_info['texto']) === 'falso') {
                        $false_id = $opcao_id;
                    }
                }

                // Para cada opção V/F, insere ou atualiza, marcando correta conforme o radio selecionado
                foreach (['verdadeiro' => $true_id, 'falso' => $false_id] as $texto => $opcao_id) {
                    $correta = ((string)$correta_vf_id === (string)$opcao_id) ? 1 : 0;
                    $real_opcao_id = null;

                    if (isset($vf_opcoes_map[$texto])) {
                        // Opção V/F existente - ATUALIZAR
                        $real_opcao_id = $vf_opcoes_map[$texto];
                        $stmt = $pdo->prepare("UPDATE opcoes SET texto = ?, correta = ? WHERE id = ? AND pergunta_id = ?");
                        $stmt->execute([ucfirst($texto), $correta, $real_opcao_id, $real_pergunta_id]);
                    } else {
                        // Opção V/F não existe ou foi deletada - INSERIR
                        $stmt = $pdo->prepare("INSERT INTO opcoes (pergunta_id, texto, correta) VALUES (?, ?, ?)");
                        $stmt->execute([$real_pergunta_id, ucfirst($texto), $correta]);
                        $real_opcao_id = $pdo->lastInsertId();
                    }
                    if ($real_opcao_id) {
                        $opcoes_a_manter[] = $real_opcao_id;
                    }
                }
            }

            // Deletar opções que não foram processadas e não devem mais existir
            if (!empty($opcoes_existentes)) {
                $opcoes_para_deletar = array_diff(array_keys($opcoes_existentes), $opcoes_a_manter);
                if (!empty($opcoes_para_deletar)) {
                    $placeholders = implode(',', array_fill(0, count($opcoes_para_deletar), '?'));
                    $stmt = $pdo->prepare("DELETE FROM opcoes WHERE id IN ($placeholders) AND pergunta_id = ?");
                    $stmt->execute(array_merge($opcoes_para_deletar, [$real_pergunta_id]));
                }
            }
            
            // Para 'resposta_curta', garantir que não há opções remanescentes
            if ($pergunta_data['tipo'] === 'resposta_curta') {
                $stmt = $pdo->prepare("DELETE FROM opcoes WHERE pergunta_id = ?");
                $stmt->execute([$real_pergunta_id]);
                // Adicionalmente, se você quiser armazenar a resposta curta, você pode adicionar uma coluna para isso na tabela 'perguntas'
                // ou em uma nova tabela de 'respostas_curtas'. Por enquanto, vou assumir que não há um campo para salvar a 'resposta_curta'
                // no banco de dados, a menos que você adicione uma coluna na tabela 'perguntas' ou crie uma nova.
                // O código do formulário HTML/JS pode ter um campo para isso, mas o PHP não salvará sem a coluna.
            }
        } // Fim do foreach perguntas

        // Deletar perguntas que foram removidas no formulário (não estão em $perguntas_a_manter)
        $ids_perguntas_atuais = array_column($perguntas, 'id'); // IDs das perguntas carregadas do BD
        $perguntas_para_deletar = array_diff($ids_perguntas_atuais, $perguntas_a_manter);

        if (!empty($perguntas_para_deletar)) {
            $placeholders = implode(',', array_fill(0, count($perguntas_para_deletar), '?'));
            $stmt = $pdo->prepare("DELETE FROM perguntas WHERE id IN ($placeholders) AND quiz_id = ?");
            $stmt->execute(array_merge($perguntas_para_deletar, [$quiz_id]));
        }

        $pdo->commit();
        $success = 'Quiz atualizado com sucesso!';
        header("Location: view.php?id=$quiz_id&success=1");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Erro ao atualizar quiz: " . $e->getMessage();
        error_log("PDO Error: " . $e->getMessage()); // Para depuração
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
                <div class="card mb-4 pergunta" data-id="<?= $pergunta['id'] ?>" data-index="<?= $index ?>">
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
                        
                        <div class="opcoes-container" style="<?= ($pergunta['tipo'] === 'multipla_escolha' || $pergunta['tipo'] === 'verdadeiro_falso') ? 'display: block;' : 'display: none;' ?>">
                            <h4 class="h6">Opções de Resposta</h4>
                            <div class="opcoes-list mb-2">
                                <?php if (($pergunta['tipo'] === 'multipla_escolha' || $pergunta['tipo'] === 'verdadeiro_falso') && !empty($pergunta['opcoes'])): ?>
                                    <?php 
                                    // Para V/F, vamos garantir que as opções "Verdadeiro" e "Falso" estejam presentes e marcadas corretamente
                                    if ($pergunta['tipo'] === 'verdadeiro_falso') {
                                        $vf_opcoes = [];
                                        foreach ($pergunta['opcoes'] as $opt) {
                                            $vf_opcoes[strtolower($opt['texto'])] = $opt;
                                        }
                                        $v_checked = isset($vf_opcoes['verdadeiro']) && $vf_opcoes['verdadeiro']['correta'] ? 'checked' : '';
                                        $f_checked = isset($vf_opcoes['falso']) && $vf_opcoes['falso']['correta'] ? 'checked' : '';
                                        
                                        // Exibe as opções fixas para Verdadeiro/Falso
                                        ?>
                                        <div class="opcao-vf mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="perguntas[<?= $pergunta['id'] ?>][correta_multipla]" id="vf_true_<?= $pergunta['id'] ?>" value="<?= htmlspecialchars($vf_opcoes['verdadeiro']['id'] ?? 'new_true_option') ?>" <?= $v_checked ?>>
                                                <label class="form-check-label" for="vf_true_<?= $pergunta['id'] ?>">Verdadeiro</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="perguntas[<?= $pergunta['id'] ?>][correta_multipla]" id="vf_false_<?= $pergunta['id'] ?>" value="<?= htmlspecialchars($vf_opcoes['falso']['id'] ?? 'new_false_option') ?>" <?= $f_checked ?>>
                                                <label class="form-check-label" for="vf_false_<?= $pergunta['id'] ?>">Falso</label>
                                            </div>
                                            <input type="hidden" name="perguntas[<?= $pergunta['id'] ?>][opcoes][<?= htmlspecialchars($vf_opcoes['verdadeiro']['id'] ?? 'new_true_option') ?>][texto]" value="Verdadeiro">
                                            <input type="hidden" name="perguntas[<?= $pergunta['id'] ?>][opcoes][<?= htmlspecialchars($vf_opcoes['falso']['id'] ?? 'new_false_option') ?>][texto]" value="Falso">
                                            <input type="hidden" name="perguntas[<?= $pergunta['id'] ?>][opcoes][<?= htmlspecialchars($vf_opcoes['verdadeiro']['id'] ?? 'new_true_option') ?>][correta]" value="<?= $v_checked ? '1' : '0' ?>">
                                            <input type="hidden" name="perguntas[<?= $pergunta['id'] ?>][opcoes][<?= htmlspecialchars($vf_opcoes['falso']['id'] ?? 'new_false_option') ?>][correta]" value="<?= $f_checked ? '1' : '0' ?>">
                                        </div>
                                        <?php
                                    } else {
                                        // Exibe as opções normais de Múltipla Escolha
                                        foreach ($pergunta['opcoes'] as $opcao): ?>
                                            <div class="opcao mb-2" data-opcao-id="<?= $opcao['id'] ?>">
                                                <div class="input-group">
                                                    <div class="input-group-text">
                                                        <input type="radio" name="perguntas[<?= $pergunta['id'] ?>][correta_multipla]" 
                                                               value="<?= $opcao['id'] ?>" class="form-check-input"
                                                               <?= $opcao['correta'] ? 'checked' : '' ?>>
                                                    </div>
                                                    <input type="text" name="perguntas[<?= $pergunta['id'] ?>][opcoes][<?= $opcao['id'] ?>][texto]" 
                                                           class="form-control" value="<?= htmlspecialchars($opcao['texto']) ?>" required>
                                                    <input type="hidden" name="perguntas[<?= $pergunta['id'] ?>][opcoes][<?= $opcao['id'] ?>][correta]" value="<?= $opcao['correta'] ? '1' : '0' ?>">
                                                    <button type="button" class="btn btn-outline-danger remove-opcao">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; 
                                    } ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary add-opcao" style="<?= $pergunta['tipo'] === 'verdadeiro_falso' ? 'display: none;' : 'display: block;' ?>">
                                <i class="fas fa-plus"></i> Adicionar Opção
                            </button>
                        </div>

                        <div class="resposta-curta-container" style="<?= $pergunta['tipo'] === 'resposta_curta' ? 'display: block;' : 'display: none;' ?>">
                            <h4 class="h6">Resposta Esperada</h4>
                            <input type="text" name="perguntas[<?= $pergunta['id'] ?>][resposta_curta]" class="form-control" 
                                   value="<?= ($pergunta['tipo'] === 'resposta_curta' && isset($pergunta['resposta_curta'])) ? htmlspecialchars($pergunta['resposta_curta']) : '' ?>" 
                                   placeholder="Digite a resposta esperada">
                            <small class="form-text text-muted">Esta resposta será salva apenas se você adicionar a coluna 'resposta_curta' na tabela 'perguntas'.</small>
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
let lastNewQuestionIndex = <?= count($perguntas) > 0 ? max(array_keys($perguntas)) + 1 : 0 ?>; // Acompanha o índice para novas perguntas

// Template para uma nova pergunta (incluindo V/F e Resposta Curta)
const perguntaTemplate = (index) => `
<div class="card mb-4 pergunta" data-id="new_${index}" data-index="new_${index}">
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
                    <option value="verdadeiro_falso" selected>Verdadeiro/Falso</option> <option value="resposta_curta">Resposta Curta</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Pontos</label>
                <input type="number" name="perguntas[new_${index}][pontos]" class="form-control" min="1" value="1">
            </div>
        </div>
        
        <div class="opcoes-container" style="display: block;"> <h4 class="h6">Opções de Resposta</h4>
            <div class="opcoes-list mb-2">
                <div class="opcao-vf mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="perguntas[new_${index}][correta_multipla]" id="vf_true_new_${index}" value="new_true_option" checked>
                        <label class="form-check-label" for="vf_true_new_${index}">Verdadeiro</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="perguntas[new_${index}][correta_multipla]" id="vf_false_new_${index}" value="new_false_option">
                        <label class="form-check-label" for="vf_false_new_${index}">Falso</label>
                    </div>
                    <input type="hidden" name="perguntas[new_${index}][opcoes][new_true_option][texto]" value="Verdadeiro">
                    <input type="hidden" name="perguntas[new_${index}][opcoes][new_false_option][texto]" value="Falso">
                    <input type="hidden" name="perguntas[new_${index}][opcoes][new_true_option][correta]" value="1">
                    <input type="hidden" name="perguntas[new_${index}][opcoes][new_false_option][correta]" value="0">
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary add-opcao" style="display: none;">
                <i class="fas fa-plus"></i> Adicionar Opção
            </button>
        </div>

        <div class="resposta-curta-container" style="display: none;">
            <h4 class="h6">Resposta Esperada</h4>
            <input type="text" name="perguntas[new_${index}][resposta_curta]" class="form-control" placeholder="Digite a resposta esperada">
        </div>

    </div>
</div>`;

// Configurar eventos para perguntas existentes ao carregar a página
document.querySelectorAll('.pergunta').forEach(pergunta => {
    configurarPergunta(pergunta);
});

// Adicionar nova pergunta
document.getElementById('add-pergunta').addEventListener('click', () => {
    const container = document.getElementById('perguntas-container');
    const newQuestionIndex = ++lastNewQuestionIndex; // Incrementa o índice global
    container.insertAdjacentHTML('beforeend', perguntaTemplate(newQuestionIndex));
    configurarPergunta(container.lastElementChild);
    // Atualizar índices visuais das perguntas
    atualizarIndicesPerguntas();
});

// Função para configurar eventos de uma pergunta (existente ou nova)
function configurarPergunta(perguntaElement) {
    const tipoSelect = perguntaElement.querySelector('.tipo-pergunta');
    const opcoesContainer = perguntaElement.querySelector('.opcoes-container');
    const opcoesList = perguntaElement.querySelector('.opcoes-list');
    const addOpcaoBtn = perguntaElement.querySelector('.add-opcao');
    const respostaCurtaContainer = perguntaElement.querySelector('.resposta-curta-container');
    
    // Função para alternar a visibilidade dos containers de opção
    const toggleContainers = () => {
        const tipo = tipoSelect.value;
        opcoesContainer.style.display = (tipo === 'multipla_escolha' || tipo === 'verdadeiro_falso') ? 'block' : 'none';
        respostaCurtaContainer.style.display = tipo === 'resposta_curta' ? 'block' : 'none';

        // Lógica específica para Verdadeiro/Falso dentro do opcoes-container
        const vfOptionsDiv = opcoesList.querySelector('.opcao-vf');
        if (vfOptionsDiv) {
            vfOptionsDiv.style.display = tipo === 'verdadeiro_falso' ? 'block' : 'none';
        }

        // Mostrar/esconder botão "Adicionar Opção"
        if (addOpcaoBtn) {
            addOpcaoBtn.style.display = tipo === 'multipla_escolha' ? 'block' : 'none';
        }

        // Gerenciar opções existentes e V/F ao mudar o tipo
        if (tipo === 'verdadeiro_falso') {
            // Remove opções de múltipla escolha se existirem
            opcoesList.querySelectorAll('.opcao:not(.opcao-vf)').forEach(opt => opt.remove());
            // Garante que as opções V/F existem, se não, as adiciona (para perguntas existentes que mudaram de tipo)
            if (!opcoesList.querySelector('.opcao-vf')) {
                opcoesList.insertAdjacentHTML('afterbegin', `
                    <div class="opcao-vf mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="perguntas[${perguntaElement.dataset.id}][correta_multipla]" id="vf_true_${perguntaElement.dataset.id}" value="new_true_option" checked>
                            <label class="form-check-label" for="vf_true_${perguntaElement.dataset.id}">Verdadeiro</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="perguntas[${perguntaElement.dataset.id}][correta_multipla]" id="vf_false_${perguntaElement.dataset.id}" value="new_false_option">
                            <label class="form-check-label" for="vf_false_${perguntaElement.dataset.id}">Falso</label>
                        </div>
                        <input type="hidden" name="perguntas[${perguntaElement.dataset.id}][opcoes][new_true_option][texto]" value="Verdadeiro">
                        <input type="hidden" name="perguntas[${perguntaElement.dataset.id}][opcoes][new_false_option][texto]" value="Falso">
                        <input type="hidden" name="perguntas[${perguntaElement.dataset.id}][opcoes][new_true_option][correta]" value="1">
                        <input type="hidden" name="perguntas[${perguntaElement.dataset.id}][opcoes][new_false_option][correta]" value="0">
                    </div>
                `);
                 // Reconfigurar os radios da nova opção V/F
                 atualizarRadiosMultiplaEscolha(perguntaElement);
            }
        } else if (tipo === 'multipla_escolha') {
            // Remove opções V/F se existirem
            const currentVfDiv = opcoesList.querySelector('.opcao-vf');
            if (currentVfDiv) currentVfDiv.remove();
            // Garante que pelo menos duas opções de múltipla escolha existam
            if (opcoesList.children.length < 2) {
                adicionarOpcaoMultiplaEscolha(perguntaElement);
                adicionarOpcaoMultiplaEscolha(perguntaElement);
            }
        } else { // Resposta Curta
            // Remove todas as opções se existirem
            opcoesList.innerHTML = '';
        }
    };

    // Alternar visibilidade ao mudar o tipo
    tipoSelect.addEventListener('change', toggleContainers);
    // Chamar uma vez para configurar o estado inicial (importante para perguntas existentes)
    toggleContainers(); 
    
    // Adicionar opção (Múltipla Escolha)
    if (addOpcaoBtn) {
        addOpcaoBtn.addEventListener('click', function() {
            adicionarOpcaoMultiplaEscolha(perguntaElement);
        });
    }
    
    // Função auxiliar para adicionar uma nova opção de múltipla escolha
    function adicionarOpcaoMultiplaEscolha(perguntaEl) {
        const opcoesList = perguntaEl.querySelector('.opcoes-list');
        const opcaoCount = opcoesList.querySelectorAll('.opcao:not(.opcao-vf)').length; // Conta apenas opções MC
        const perguntaId = perguntaEl.dataset.id; // Pode ser '123' ou 'new_0'
        
        const novaOpcao = document.createElement('div');
        novaOpcao.className = 'opcao mb-2';
        novaOpcao.innerHTML = `
            <div class="input-group">
                <div class="input-group-text">
                    <input type="radio" name="perguntas[${perguntaId}][correta_multipla]" 
                            value="new_mc_option_${opcaoCount}" class="form-check-input">
                </div>
                <input type="text" name="perguntas[${perguntaId}][opcoes][new_mc_option_${opcaoCount}][texto]" 
                        class="form-control" placeholder="Texto da opção" required>
                <input type="hidden" name="perguntas[${perguntaId}][opcoes][new_mc_option_${opcaoCount}][correta]" value="0">
                <button type="button" class="btn btn-outline-danger remove-opcao">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        opcoesList.appendChild(novaOpcao);
        
        // Configurar remoção de opção para a nova opção
        novaOpcao.querySelector('.remove-opcao').addEventListener('click', function() {
            novaOpcao.remove();
            // Reconfigurar os radios após remover uma opção
            atualizarRadiosMultiplaEscolha(perguntaEl);
        });
        // Configurar o evento de mudança para o novo rádio
        novaOpcao.querySelector('input[type="radio"][name*="[correta_multipla]"]').addEventListener('change', function() {
            atualizarRadiosMultiplaEscolha(perguntaEl);
        });
    }

    // Remover pergunta
    const removePerguntaBtn = perguntaElement.querySelector('.remove-pergunta');
    if (removePerguntaBtn) {
        removePerguntaBtn.addEventListener('click', function() {
            if (document.querySelectorAll('.pergunta').length > 1) {
                perguntaElement.remove();
                atualizarIndicesPerguntas(); // Atualiza índices visuais após remover
            } else {
                alert('O quiz deve ter pelo menos uma pergunta.');
            }
        });
    }
    
    // Atualizar valores corretos quando o radio de MÚLTIPLA ESCOLHA OU V/F é alterado
    // Este evento é para o grupo de radios (correta_multipla)
    perguntaElement.querySelectorAll('input[type="radio"][name*="[correta_multipla]"]').forEach(radio => {
        radio.addEventListener('change', function() {
            atualizarRadiosMultiplaEscolha(perguntaElement);
        });
    });

    // Configurar remoção de opções existentes (Múltipla Escolha)
    perguntaElement.querySelectorAll('.remove-opcao').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.opcao').remove();
            // Reconfigurar os radios após remover uma opção
            atualizarRadiosMultiplaEscolha(perguntaElement);
        });
    });
}

// Função auxiliar para reconfigurar os valores hidden 'correta' das opções de múltipla escolha
// ou V/F (que usam o mesmo radio group 'correta_multipla')
function atualizarRadiosMultiplaEscolha(perguntaElement) {
    const opcoes = perguntaElement.querySelectorAll('.opcoes-list .opcao:not(.opcao-vf), .opcoes-list .opcao-vf input[type="hidden"][name*="[opcoes]"]');
    const selectedRadio = perguntaElement.querySelector('input[type="radio"][name*="[correta_multipla]"]:checked');
    let correctValue = null;

    if (selectedRadio) {
        correctValue = selectedRadio.value; 
    }
    
    // Para opções de Múltipla Escolha padrão
    perguntaElement.querySelectorAll('.opcoes-list .opcao:not(.opcao-vf)').forEach((opcaoDiv) => {
        const radioInput = opcaoDiv.querySelector('input[type="radio"]');
        const hiddenInput = opcaoDiv.querySelector('input[type="hidden"][name*="[correta]"]');
        
        if (hiddenInput) {
            hiddenInput.value = (correctValue !== null && String(radioInput.value) === String(correctValue)) ? '1' : '0';
        }
    });

    // Para as opções Verdadeiro/Falso fixas
    const vfOptionsDiv = perguntaElement.querySelector('.opcao-vf');
    if (vfOptionsDiv) {
        const trueHiddenInput = vfOptionsDiv.querySelector('input[name*="[opcoes][new_true_option][correta]"], input[name*="[opcoes]"][value$="true_option"][type="hidden"]');
        const falseHiddenInput = vfOptionsDiv.querySelector('input[name*="[opcoes][new_false_option][correta]"], input[name*="[opcoes]"][value$="false_option"][type="hidden"]');
        
        const trueRadio = vfOptionsDiv.querySelector('input[value="new_true_option"]');
        const falseRadio = vfOptionsDiv.querySelector('input[value="new_false_option"]');

        // Para opções V/F existentes, pode ser que elas tenham IDs numéricos reais
        // Tentamos pegar o ID do radio que está 'checked'
        if (selectedRadio && selectedRadio.closest('.opcao-vf')) {
            const selectedText = selectedRadio.labels[0].textContent.toLowerCase();
            if (trueHiddenInput) trueHiddenInput.value = (selectedText === 'verdadeiro') ? '1' : '0';
            if (falseHiddenInput) falseHiddenInput.value = (selectedText === 'falso') ? '1' : '0';
        } else { // Se não há radio V/F selecionado, ou se a pergunta mudou de tipo e foi carregada sem seleção
            if (trueHiddenInput) trueHiddenInput.value = '0';
            if (falseHiddenInput) falseHiddenInput.value = '0';
        }
    }
}


// Validação customizada do formulário
function validarQuizForm() {
    let valido = true;
    let mensagens = [];

    const tituloQuiz = document.getElementById('titulo');
    if (!tituloQuiz.value.trim()) {
        valido = false;
        mensagens.push('O título do quiz é obrigatório.');
    }

    const perguntas = document.querySelectorAll('.pergunta');
    if (perguntas.length === 0) {
        valido = false;
        mensagens.push('O quiz deve ter pelo menos uma pergunta.');
    }

    perguntas.forEach((pergunta) => {
        const textoPergunta = pergunta.querySelector('input[type="text"][name*="[texto]"]');
        if (textoPergunta && !textoPergunta.value.trim()) {
            valido = false;
            mensagens.push('Preencha o texto de todas as perguntas.');
        }

        const tipoSelect = pergunta.querySelector('.tipo-pergunta');
        const tipo = tipoSelect ? tipoSelect.value : '';

        if (tipo === 'multipla_escolha') {
            const opcoes = pergunta.querySelectorAll('.opcoes-list .opcao:not(.opcao-vf)'); // Apenas opções MC
            let temOpcaoValida = false;
            let temOpcaoCorreta = false;

            if (opcoes.length < 2) {
                valido = false;
                mensagens.push('Cada pergunta de múltipla escolha deve ter pelo menos duas opções.');
            }

            opcoes.forEach(opcaoDiv => {
                const textoOpcao = opcaoDiv.querySelector('input[type="text"][name*="[texto]"]');
                const hiddenCorreta = opcaoDiv.querySelector('input[type="hidden"][name*="[correta]"]');

                if (textoOpcao && textoOpcao.value.trim()) {
                    temOpcaoValida = true;
                } else {
                    valido = false;
                    mensagens.push('Todas as opções de múltipla escolha devem ter texto preenchido.');
                }
                
                if (hiddenCorreta && hiddenCorreta.value === '1') {
                    temOpcaoCorreta = true;
                }
            });

            if (!temOpcaoValida) {
                valido = false; 
                mensagens.push('Cada pergunta de múltipla escolha deve ter pelo menos uma opção preenchida.');
            }
            if (!temOpcaoCorreta) {
                valido = false;
                mensagens.push('Cada pergunta de múltipla escolha deve ter uma opção correta selecionada.');
            }
        } else if (tipo === 'verdadeiro_falso') {
            // Verifica se algum radio V/F está selecionado
            const radioVf = pergunta.querySelector('.opcao-vf input[type="radio"]:checked');
            if (!radioVf) {
                valido = false;
                mensagens.push('Selecione "Verdadeiro" ou "Falso" para todas as perguntas desse tipo.');
            }
        } else if (tipo === 'resposta_curta') {
            const respostaCurtaInput = pergunta.querySelector('input[type="text"][name*="[resposta_curta]"]');
            if (respostaCurtaInput && !respostaCurtaInput.value.trim()) {
                valido = false;
                mensagens.push('Preencha a resposta esperada para todas as perguntas de resposta curta.');
            }
        }
    });

    if (!valido) {
        // Remover mensagens duplicadas
        mensagens = [...new Set(mensagens)]; 
        alert(mensagens.join('\n'));
    }
    return valido;
}

// Função para atualizar os números das perguntas exibidos no cabeçalho do card
function atualizarIndicesPerguntas() {
    document.querySelectorAll('.pergunta').forEach((perguntaElement, index) => {
        const h5 = perguntaElement.querySelector('.card-header h3.h5');
        if (h5) {
            h5.textContent = `Pergunta #${index + 1}`;
        }
    });
}

</script>

<?php include '../../includes/footer.php'; ?>