<?php
$errorCode = $_GET['code'] ?? '404';
$errorMessages = [
    '400' => 'Requisição Inválida',
    '401' => 'Não Autorizado',
    '403' => 'Acesso Proibido',
    '404' => 'Página Não Encontrada',
    '500' => 'Erro Interno do Servidor'
];

$errorTitle = $errorMessages[$errorCode] ?? 'Erro Desconhecido';
$pageTitle = "Erro $errorCode";
include '../includes/header.php';
?>

<div class="error-container">
    <div class="error-content">
        <h1>Erro <?= $errorCode ?></h1>
        <h2><?= $errorTitle ?></h2>
        
        <p>
            <?php 
            switch ($errorCode) {
                case '400':
                    echo "Sua requisição contém dados inválidos.";
                    break;
                case '401':
                    echo "Você precisa estar autenticado para acessar esta página.";
                    break;
                case '403':
                    echo "Você não tem permissão para acessar este recurso.";
                    break;
                case '404':
                    echo "A página que você está tentando acessar não existe ou foi movida.";
                    break;
                case '500':
                    echo "Ocorreu um erro inesperado no servidor.";
                    break;
                default:
                    echo "Ocorreu um erro inesperado.";
            }
            ?>
        </p>
        
        <div class="error-actions">
            <a href="home.php" class="btn btn-primary">Página Inicial</a>
            <a href="javascript:history.back()" class="btn">Voltar</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>