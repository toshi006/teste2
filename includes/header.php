<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Determina a página atual
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Sistema Educacional', ENT_QUOTES, 'UTF-8') ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/teste2/assets/css/reset.css">
    <link rel="stylesheet" href="/teste2/assets/css/style.css">
    <link rel="stylesheet" href="/teste2/assets/css/header.css">
    <link rel="stylesheet" href="/teste2/assets/css/footer.css">
    
    <!-- CSS específico por página -->
    <?php
        $cssFile = strtolower(basename($_SERVER['PHP_SELF'], '.php'));
        $cssPath = $_SERVER['DOCUMENT_ROOT'] . "/teste2/assets/css/{$cssFile}.css";
        if (file_exists($cssPath)):
    ?>
        <link rel="stylesheet" href="/teste2/assets/css/<?= $cssFile ?>.css">
    <?php endif; ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">


</head>
<body>
    <header class="main_header">
        <div class="container-header">
            <div class="logo">
                <a href="/teste2/pages/home.php">
                    <img src="/teste2/assets/images/logo.png" alt="Logo Sistema Educacional">
                </a>
            </div>
            
            <nav class="main_nav">
                <ul>
                    <li class="<?= ($current_page == 'home.php') ? 'active' : '' ?>">
                        <a href="/teste2/pages/home.php"><i class="fas fa-home"></i> Início</a>
                    </li>
                    
                    <li class="<?= (strpos($current_page, 'post/') !== false) ? 'active' : '' ?>">
                        <a href="/teste2/pages/post/list.php"><i class="fas fa-book"></i> Posts</a>
                    </li>
                    
                    <li class="<?= (strpos($current_page, 'quiz/') !== false) ? 'active' : '' ?>">
                        <a href="/teste2/pages/quiz/list.php"><i class="fas fa-question-circle"></i> Quizzes</a>
                    </li>

                    <?php if (isLoggedIn()): ?>
                        <li class="dropdown <?= (strpos($current_page, 'user/') !== false || strpos($current_page, 'admin/') !== false) ? 'active' : '' ?>">
                            <a href="#"><i class="fas fa-user"></i> <?= e($_SESSION['user_name']) ?></a>
                            <ul class="dropdown-menu">
                                <li><a href="/teste2/pages/user/profile.php"><i class="fas fa-user-circle"></i> Perfil</a></li>
                                <li><a href="/teste2/pages/user/my_posts.php"><i class="fas fa-file-alt"></i> Meus Posts</a></li>
                                <li><a href="/teste2/pages/user/my_quizzes.php"><i class="fas fa-list"></i> Meus Quizzes</a></li>
                                <li class="divider"></li>
                                <?php if (hasAnyRole([ROLE_ADMIN, ROLE_TEACHER])): ?>
                                    <li><a href="/teste2/pages/admin/dashboard.php"><i class="fas fa-cog"></i> Painel Admin</a></li>
                                <?php endif; ?>
                                <li><a href="/teste2/process/logout_process.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="<?= ($current_page == 'login.php') ? 'active' : '' ?>">
                            <a href="/teste2/pages/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                        </li>
                        <li class="<?= ($current_page == 'register.php') ? 'active' : '' ?>">
                            <a href="/teste2/pages/register.php"><i class="fas fa-user-plus"></i> Registrar</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">