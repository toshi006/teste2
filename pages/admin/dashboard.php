<?php
require_once '../../includes/header.php';
require_once '../../includes/auth.php';
// Conexão com o banco de dados (ajuste conforme necessário)
require_once '../../includes/db.php';

// Consulta para contar usuários
$stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
$users_count = $stmt ? $stmt->fetchColumn() : 0;

// Consulta para contar posts
$stmt = $pdo->query("SELECT COUNT(*) FROM posts");
$posts_count = $stmt ? $stmt->fetchColumn() : 0;

// Consulta para contar quizzes
$stmt = $pdo->query("SELECT COUNT(*) FROM quizzes");
$quizzes_count = $stmt ? $stmt->fetchColumn() : 0;

// Consultar últimas atividades (exemplo: últimos 5 registros)
$activities = [];
$stmt = $pdo->query("
    SELECT * FROM (
        SELECT CONCAT('Novo usuário registrado - ', nome) AS activity, data_cadastro AS data_atividade
        FROM usuarios
        ORDER BY data_cadastro DESC
        LIMIT 2
    ) AS u
    UNION ALL
    SELECT * FROM (
        SELECT CONCAT('Post criado - \"', titulo, '\"') AS activity, data_publicacao AS data_atividade
        FROM posts
        ORDER BY data_publicacao DESC
        LIMIT 2
    ) AS p
    UNION ALL
    SELECT * FROM (
        SELECT CONCAT('Quiz criado - \"', titulo, '\"') AS activity, data_criacao AS data_atividade
        FROM quizzes
        ORDER BY data_criacao DESC
        LIMIT 1
    ) AS q
    ORDER BY data_atividade DESC
    LIMIT 5
");
if ($stmt) {
    $activities = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}
include_once '../../includes/header.php';
?>
<style>.dashboard h1 {
    font-size: 2rem;
    font-weight: bold;
    text-align: center;
    margin-bottom: 2rem;
}

.stat-card {
    border-radius: 0.5rem;
    transition: transform 0.2s ease-in-out;
}

.stat-card:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.stat-card .card-title {
    font-size: 1.2rem;
    font-weight: 600;
}

.stat-card .card-text {
    font-weight: bold;
    font-size: 2.5rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.list-group-item {
    font-size: 0.95rem;
    padding: 0.75rem 1.25rem;
}

@media (max-width: 768px) {
    .stat-card .card-text {
        font-size: 2rem;
    }
}
</style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<div class="container mt-5" style="padding-top: 6rem;">
    <header class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Painel de Controle</h1>
        <div class="text-muted">Atualizado em <?= date('d/m/Y H:i') ?></div>
    </header>
    
    <!-- Cards de Métricas -->
    <div class="row g-4 mb-4">
        <!-- Card de Usuários -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 hover-effect">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0 text-primary">
                            <i class="bi bi-people-fill me-2"></i>Usuários
                        </h5>
                        <span class="badge bg-primary-light text-primary">+5%</span>
                    </div>
                    <p class="display-4 fw-bold mb-3"><?= $users_count ?></p>
                    <a href="manage_users.php" class="text-decoration-none d-flex align-items-center">
                        Gerenciar <i class="bi bi-arrow-right-short ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Card de Posts -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 hover-effect">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0 text-success">
                            <i class="bi bi-file-earmark-text-fill me-2"></i>Posts
                        </h5>
                        <span class="badge bg-success-light text-success">+12%</span>
                    </div>
                    <p class="display-4 fw-bold mb-3"><?= $posts_count ?></p>
                    <a href="manage_post.php" class="text-decoration-none d-flex align-items-center">
                        Gerenciar <i class="bi bi-arrow-right-short ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Card de Quizzes -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 hover-effect">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0 text-danger">
                            <i class="bi bi-question-circle-fill me-2"></i>Quizzes
                        </h5>
                        <span class="badge bg-danger-light text-danger">-2%</span>
                    </div>
                    <p class="display-4 fw-bold mb-3"><?= $quizzes_count ?></p>
                    <a href="manage_quizzes.php" class="text-decoration-none d-flex align-items-center">
                        Gerenciar <i class="bi bi-arrow-right-short ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Últimas atividades -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 d-flex align-items-center">
                <i class="bi bi-activity me-2 text-primary"></i>Últimas Atividades
            </h5>
        </div>
        <div class="card-body p-0">
            <ul class="list-group list-group-flush">
                <?php if (!empty($activities)): ?>
                    <?php foreach ($activities as $activity): ?>
                        <li class="list-group-item border-0 py-3 d-flex align-items-center">
                            <span class="badge bg-light text-muted me-3">
                                <i class="bi bi-circle-fill text-primary"></i>
                            </span>
                            <?= htmlspecialchars($activity) ?>
                            <small class="text-muted ms-auto"><?= date('H:i') ?></small>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="list-group-item border-0 py-4 text-center text-muted">
                        <i class="bi bi-info-circle me-2"></i>Nenhuma atividade recente
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<style>
    .hover-effect:hover {
        transform: translateY(-5px);
        transition: transform 0.3s ease;
    }
    
    .bg-primary-light {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-success-light {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-danger-light {
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .card {
        border-radius: 12px;
        overflow: hidden;
    }
    
    .card-header {
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
</style>

<?php require_once '../../includes/footer.php'; ?>