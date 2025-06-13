<div class="comment mb-3 border-bottom pb-3" id="comment-<?= $comment['id'] ?>">
        
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($comment['autor_nome']) ?></h6>
                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($comment['data'])) ?></small>
            </div>
            
            <div class="comment-content mb-2">
                <?= nl2br(htmlspecialchars($comment['conteudo'])) ?>
            </div>
            
            <div class="comment-actions">
                <button class="btn btn-sm btn-outline-secondary reply-btn" 
                        data-comment-id="<?= $comment['id'] ?>"
                        data-username="<?= htmlspecialchars($comment['autor_nome']) ?>">
                    <i class="bi bi-reply"></i> Responder
                </button>
                
                <?php if ($isAdmin || $userId == $comment['autor_id']): ?>
                    <button class="btn btn-sm btn-outline-danger delete-comment-btn ms-2" 
                            data-comment-id="<?= $comment['id'] ?>">
                        <i class="bi bi-trash"></i> Excluir
                    </button>
                <?php endif; ?>
            </div>
            
            <!-- Respostas aos comentários -->
            <?php if (!empty($comment['respostas'])): ?>
                <div class="mt-3 ps-3 border-start">
                    <?php foreach ($comment['respostas'] as $resposta): ?>
                        <?php 
                            // Reutiliza o mesmo template para respostas
                            $resposta['autor_avatar'] = $resposta['autor_avatar'] ?? 'default.png';
                            include 'comment.php'; 
                        ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>