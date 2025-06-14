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

// Construir a query base
$query = "SELECT p.*, u.nome as autor FROM posts p JOIN usuarios u ON p.usuario_id = u.id";
$where = [];
$params = [];

// Aplicar filtros
if ($categoria) {
    $where[] = "p.categoria = ?";
    $params[] = $categoria;
}

if ($autor && is_numeric($autor)) {
    $where[] = "p.usuario_id = ?";
    $params[] = $autor;
}

if ($search) {
    $where[] = "(p.titulo LIKE ? OR p.conteudo LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

// Ordenação
$query .= " ORDER BY p.data_publicacao DESC";

// Query para os posts (com paginação)
$stmt = $pdo->prepare($query . " LIMIT ? OFFSET ?");
foreach ($params as $k => $v) {
    $stmt->bindValue($k + 1, $v);
}   
$stmt->bindValue(count($params) + 1, (int)$per_page, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

// Contar total de posts para paginação
$stmt = $pdo->prepare("SELECT COUNT(*) FROM ($query) as total");
$stmt->execute($params);
$total_posts = $stmt->fetchColumn();
$total_pages = ceil($total_posts / $per_page);

// Buscar categorias disponíveis
$stmt = $pdo->query("SELECT DISTINCT categoria FROM posts");
$categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = "Todos os Posts";
include '../../includes/header.php';
?>

<div class="container">
    <h1>Posts Educacionais</h1>
    
    <!-- Filtros -->
    <div class="posts-filters card mb-4">
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
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search ?? '') ?>" class="form-control" placeholder="Título ou conteúdo">
                </div>
                
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="list.php" class="btn btn-secondary ms-2">Limpar</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Lista de Posts -->
    <?php if (count($posts) > 0): ?>
        <div class="posts-list">
            <?php foreach ($posts as $post): ?>
                <div class="post-card card mb-3">
                    <div class="card-body">
                        <div class="post-meta mb-2">
                            <span class="badge bg-secondary"><?= htmlspecialchars($post['categoria']) ?></span>
                            <span class="text-muted ms-2">Por <?= htmlspecialchars($post['autor']) ?> em <?= formatDate($post['data_publicacao'], 'd/m/Y') ?></span>
                        </div>
                        <h2 class="card-title">
                            <a href="view.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['titulo']) ?></a>
                        </h2>
                        <div class="card-text post-excerpt">
                            <?= substr(strip_tags($post['conteudo']), 0, 200) ?>...
                        </div>
                        <a href="view.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-primary mt-2">Ler mais</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Nenhum post encontrado com os filtros selecionados.</div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>