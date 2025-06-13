<div class="card quiz-card mb-4" data-quiz-id="<?= $quiz['id'] ?>">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><?= htmlspecialchars($quiz['titulo']) ?></h5>
    </div>
    
    <div class="card-body">
        <p class="card-text"><?= htmlspecialchars($quiz['descricao']) ?></p>
        
        <div class="quiz-meta mb-3">
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-info">
                    <i class="bi bi-question-circle"></i> 
                    <?= $quiz['questoes_count'] ?> questões
                </span>
                <span class="badge bg-success">
                    <i class="bi bi-clock"></i> 
                    <?= $quiz['duracao'] ?> min
                </span>
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-bar-chart"></i> 
                    Dificuldade: <?= $quiz['dificuldade'] ?>
                </span>
            </div>
        </div>
        
        <div class="d-flex justify-content-between align-items-center">
            <div class="quiz-author">
                <img src="/sistema-educacional/uploads/avatars/<?= htmlspecialchars($quiz['autor_avatar'] ?: 'default.png') ?>" 
                     class="rounded-circle me-2" 
                     width="30" 
                     height="30" 
                     alt="<?= htmlspecialchars($quiz['autor_nome']) ?>">
                <small><?= htmlspecialchars($quiz['autor_nome']) ?></small>
            </div>
            
            <?php if ($quiz['realizado']): ?>
                <span class="badge bg-success">
                    <i class="bi bi-check-circle"></i> Completo
                </span>
            <?php else: ?>
                <a href="/sistema-educacional/pages/quiz/take.php?id=<?= $quiz['id'] ?>" 
                   class="btn btn-sm btn-primary">
                    Iniciar Quiz
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card-footer bg-transparent">
        <small class="text-muted">
            Última atualização: <?= date('d/m/Y', strtotime($quiz['data_atualizacao'])) ?>
        </small>
        
        <?php if ($quiz['realizado']): ?>
            <div class="mt-2">
                <div class="progress">
                    <div class="progress-bar bg-success" 
                         role="progressbar" 
                         style="width: <?= $quiz['pontuacao'] ?>%" 
                         aria-valuenow="<?= $quiz['pontuacao'] ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        <?= $quiz['pontuacao'] ?>%
                    </div>
                </div>
                <small class="d-block text-end">Sua pontuação</small>
            </div>
        <?php endif; ?>
    </div>
</div>