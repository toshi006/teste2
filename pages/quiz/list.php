<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Configuração de paginação
$current_page = $_GET['page'] ?? 1;
$per_page = 10;
$offset = ($current_page - 1) * $per_page;

// Filtros
$categoria = $_GET['categoria'] ?? null;
$autor = $_GET['autor'] ?? null;
$search = $_GET['search'] ?? null;

/*
 * Nova query sem dependência de posts.
 * Agora quizzes têm suas próprias colunas de categoria, autor, etc.
 */

// Construir a query base

$query = "SELECT q.*, u.nome as autor 
          FROM quizzes q
          JOIN usuarios u ON q.usuario_id = u.id";
$where = [];
$params = [];

// Aplicar filtros
if ($categoria) {
    $where[] = "q.categoria = ?";
    $params[] = $categoria;
}

if ($autor && is_numeric($autor)) {
    $where[] = "q.usuario_id = ?";
    $params[] = $autor;
}

if ($search) {
    $where[] = "(q.titulo LIKE ? OR q.descricao LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Ordenação
$query .= " ORDER BY q.data_criacao DESC";

// Query para os quizzes (com paginação)
$stmt = $pdo->prepare($query . " LIMIT ? OFFSET ?");
foreach ($params as $k => $v) {
    $stmt->bindValue($k + 1, $v);
}

$stmt->bindValue(count($params) + 1, (int)$per_page, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();
include '../templates/quizz_card.php';
// Contar total de quizzes para paginação
$stmt = $pdo->prepare("SELECT COUNT(*) FROM ($query) as total");
$stmt->execute($params);
$total_quizzes = $stmt->fetchColumn();
$total_pages = ceil($total_quizzes / $per_page);

// Buscar categorias disponíveis
$stmt = $pdo->query("SELECT DISTINCT categoria FROM quizzes");
$categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = "Quizzes Disponíveis";
include '../../includes/header.php';
?>

<div class="container">
    <h1>Quizzes Educacionais</h1>
    
    <!-- Filtros -->
    <div class="quizzes-filters card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="categoria" class="form-label">Categoria</label>
                    <select id="categoria" name="categoria" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $categoria === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="search" class="form-label">Buscar</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search ?? '') ?>" 
                           class="form-control" placeholder="Título ou descrição">
                </div>
                
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="list.php" class="btn btn-secondary ms-2">Limpar</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Lista de Quizzes -->
    <?php if (count($posts) > 0): ?>
        <div class="quizzes-list">
            <?php foreach ($posts as $quiz): ?>
                <div class="quiz-card card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="h5 card-title">
                                    <a href="view.php?id=<?= $quiz['id'] ?>"><?= htmlspecialchars($quiz['titulo']) ?></a>
                                </h2>
                                <p class="card-text">
                                    <small class="text-muted">
                                        Autor: <?= htmlspecialchars($quiz['autor']) ?> | 
                                        Categoria: <?= htmlspecialchars($quiz['categoria']) ?>
                                    </small>
                                </p>
                            </div>
                            <span class="badge bg-light text-dark">
                                <?= formatDate($quiz['data_criacao'], 'd/m/Y') ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($quiz['descricao'])): ?>
                            <p class="card-text mt-2"><?= htmlspecialchars($quiz['descricao']) ?></p>
                        <?php endif; ?>
                        
                        <div class="buttons">
                            <a href="take.php?id=<?= $quiz['id'] ?>" class="btn btn-sm btn-primary">Responder Quiz</a>

                            <?php if (hasAnyRole(['professor', 'admin']) && ($_SESSION['user_id'] == $quiz['usuario_id'] || hasAnyRole(['admin']))): ?>
                                <div class="btn-group">
                                    <a href="edit.php?id=<?= $quiz['id'] ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                                    <form action="delete.php" method="post" class="d-inline">
                                        <input type="hidden" name="id" value="<?= $quiz['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                onclick="return confirm('Tem certeza que deseja excluir este quiz?')">
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Nenhum quiz encontrado com os filtros selecionados.</div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>