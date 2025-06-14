<style>.post-card {
    border: none;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    background-color: #ffffff;
}

.post-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}

.post-card .card-body {
    padding: 1.25rem;
    min-width: 360px;
    min-height: 200px;
}

.post-card .badge {
    font-size: 0.75rem;
    padding: 0.4em 0.6em;
    border-radius: 8px;
    background-color: #00bcd4;
}

.post-card .card-title {
    font-size: 1.25rem;
    color: #333;
    margin-bottom: 0.5rem;
    padding-top: 0.5rem;
}

.post-card .card-text {
    font-size: 0.95rem;
    color: #555;
    line-height: 1.4;
    margin-bottom: 1rem;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 3; /* mostra no máx. 3 linhas */
    -webkit-box-orient: vertical;
}

.post-card .btn-outline-primary {
    font-size: 0.85rem;
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
}

.post-card .card-footer {
    border-top: 1px solid #eee;
    padding: 0.75rem 1.25rem;
    background-color: #f9f9f9;
    font-size: 0.85rem;
    color: #666;
}

.post-card .text-muted i {
    margin-right: 4px;
    padding: 0.2rem;
}

.post-card small {
    font-size: 0.85rem;
    color: #666;
}

.text-mu
/* Ícones alinhados corretamente */
.post-card .d-flex.align-items-center small {
    margin-right: 0.75rem;
    display: flex;
    align-items: center;
}

/* Responsivo */
@media (max-width: 576px) {
    .post-card .d-flex.justify-content-between {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>
<div class="card mb-4 shadow-sm post-card"> 
    
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge bg-primary"><?= htmlspecialchars($post['categoria']) ?></span>
            <small class="text-muted"><?= date('d/m/Y', strtotime($post['data_publicacao'])) ?></small>
        </div>
        
        <h5 class="card-title"><?= htmlspecialchars($post['titulo']) ?></h5>
        <p class="card-text text-truncate">
            <?= htmlspecialchars(mb_substr(strip_tags($post['conteudo']), 0, 120)) ?>...
        </p>
        
        <div class="d-flex justify-content-between align-items-center">
            <a href="/teste2/pages/post/view.php?id=<?= $post['id'] ?>" 
               class="btn btn-sm btn-outline-primary">
                Ler Mais
            </a>
            
            <div class="d-flex align-items-center">
                <small class="text-muted me-2">
                    <i class="bi bi-eye"></i> <?= $post['visualizacoes'] ?>
                </small>
                <small class="text-muted">
                    <i class="bi bi-chat-left-text"></i> <?= $post['comentarios_count'] ?>
                </small>
            </div>
        </div>
    </div>
    
    <div class="card-footer bg-transparent">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <small><?= htmlspecialchars($post['autor']) ?></small>
            </div>
            
        </div>
    </div>
</div>