<style>
.quiz-card {
    border: none;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    background-color: #fff;
    
}

.quiz-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.10);
}

.quiz-card .card-body {
    padding: 1.25rem;
    min-width: 360px;
    max-width: 360px;
    min-height: 200px;
    max-height: 200px;
    box-sizing: border-box;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
}
.quiz-card .card-body .d-flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.quiz-card .badge {
    font-size: 0.8rem;
    padding: 0.4em 0.7em;
    border-radius: 8px;
    background-color: #198754;

}

.quiz-card .card-title {
    font-size: 1.2rem;
    color: #222;
    margin-bottom: 0.5rem;
    padding-top: 0.5rem;
}

.quiz-card .card-text {
    font-size: 0.95rem;
    color: #444;
    line-height: 1.4;
    margin-bottom: 1rem;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

.quiz-card .btn-outline-success {
    font-size: 0.85rem;
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
}

.quiz-card .card-footer {
    border-top: 1px solid #eee;
    padding: 0.75rem 1.25rem;
    background-color: #f8f9fa;
    font-size: 0.88rem;
    color: #666;
    border: 1px solid #e0e0e0;
    border-radius: 0 0 12px 12px;
}

.quiz-card small {
    font-size: 0.88rem;
    color: #666;
}

.quiz-card .d-flex.align-items-center small {
    margin-right: 0.75rem;
    display: flex;
    align-items: center;
}

</style>
<div class="card mb-4 shadow-sm quiz-card"> 
    
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2"style="display: flex;gap: 5px;">
            <span class="badge bg-success"><?= htmlspecialchars($quiz['categoria']) ?></span>
            <small class="text-muted"><?= date('d/m/Y', strtotime($quiz['data_criacao'])) ?></small>
        </div>
        
        <h5 class="card-title"><?= htmlspecialchars($quiz['titulo']) ?></h5>
        <p class="card-text text-truncate">
            <?= htmlspecialchars(mb_substr(strip_tags($quiz['descricao']), 0, 120, 'UTF-8')) ?>...
        </p>
        
        <div class="d-flex justify-content-between align-items-center">
            <a href="/teste2/pages/quiz/view.php?id=<?= $quiz['id'] ?>" 
               class="btn btn-sm btn-outline-success">
                Jogar Quiz
            </a>
        </div>
    </div>
    
    <div class="card-footer bg-transparent">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <small><?= htmlspecialchars($quiz['autor']) ?></small>
            </div>
        </div>
    </div>
</div>
