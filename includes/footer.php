        </main> <!-- Fecha a tag main aberta no header -->
        
        <footer class="main-footer">
            <div class="container">
                <div class="footer-grid">
                    <div class="footer-section">
                        <h3>Sobre o Sistema</h3>
                        <p>Plataforma educacional para compartilhamento de conhecimento e interação entre estudantes e professores.</p>
                    </div>
                    
                    <div class="footer-section">
                        <h3>Links Rápidos</h3>
                        <ul>
                            <li><a href="../pages/home.php">Início</a></li>
                            <li><a href="../pages/post/list.php">Posts</a></li>
                            <li><a href="../pages/quiz/list.php">Quizzes</a></li>
                            <li><a href="../pages/search.php">Busca</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h3>Contato</h3>
                        <ul>
                            <li><i class="fas fa-envelope"></i> contato@sistemaeducacional.com</li>
                            <li><i class="fas fa-phone"></i> (11) 1234-5678</li>
                        </ul>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p>&copy; <?= date('Y') ?> Sistema Educacional. Todos os direitos reservados.</p>
                    
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </footer>
        
        <!-- Scripts JavaScript -->
        <script src="/assets/js/main.js"></script>
        
        <!-- CKEditor (para áreas de texto ricas) -->
        <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
        <script>
            // Inicializa todos os editores na página
            document.addEventListener('DOMContentLoaded', function() {
                const editors = document.querySelectorAll('.rich-text-editor');
                editors.forEach(editor => {
                    CKEDITOR.replace(editor, {
                        toolbar: [
                            { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike'] },
                            { name: 'paragraph', items: ['NumberedList', 'BulletedList', 'Blockquote'] },
                            { name: 'links', items: ['Link', 'Unlink'] },
                            { name: 'insert', items: ['Image', 'Table'] },
                            { name: 'tools', items: ['Maximize'] },
                            { name: 'document', items: ['Source'] }
                        ],
                        height: 200,
                        removePlugins: 'elementspath',
                        resize_enabled: false
                    });
                });
            });
            
        </script>
    </body>
</html>