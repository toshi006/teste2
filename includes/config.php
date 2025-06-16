<?php
// Configurações de ambiente
define('ENVIRONMENT', 'development'); // production | development
define('ROOT_PATH', str_replace('\\', '/', realpath(__DIR__ . '/..')) . '/');
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST']; // Ex: localhost ou 192.168.1.106

// Obtém o caminho do script PHP atual na URL.
// Ex: /teste2/pages/home.php ou /pages/home.php
$scriptUrlPath = $_SERVER['PHP_SELF'];

// Pega o diretório do script atual na URL.
// Ex: /teste2/pages/ ou /pages/
$scriptUrlDir = dirname($scriptUrlPath);

// Pega o caminho do diretório do ROOT_PATH em relação ao DOCUMENT_ROOT
// Ex: str_replace('/var/www/html/', '', '/var/www/html/teste2/') => 'teste2/'
$projectFolderOnServer = str_replace(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '', ROOT_PATH);

// Remove qualquer barra inicial extra que possa ter sido adicionada pelo str_replace
$projectFolderOnServer = ltrim($projectFolderOnServer, '/');

// Se o projeto está na raiz do domínio, projectFolderOnServer será vazio.
// Se está em uma subpasta (ex: 'teste2/'), projectFolderOnServer será 'teste2/'
if (empty($projectFolderOnServer) || $projectFolderOnServer === '/') {
    define('BASE_URL', $protocol . $host . '/');
} else {
    define('BASE_URL', $protocol . $host . '/' . $projectFolderOnServer);
}


// Configurações de upload
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);

// Níveis de acesso
define('ROLE_ADMIN', 'admin');
define('ROLE_TEACHER', 'professor');
define('ROLE_STUDENT', 'aluno');

// Configurações de caminho (opcional - pode ser definido manualmente)
define('BASE_PATH', __DIR__ . '/..');  // Ajuste conforme sua estrutura
define('ASSETS_PATH', '/assets');      // Caminho relativo para assets

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
// Função para checar se o usuário possui algum dos papéis permitidos
function userHasRole($allowedRoles) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    if (is_array($allowedRoles)) {
        return in_array($_SESSION['user_role'], $allowedRoles);
    }
    return $_SESSION['user_role'] === $allowedRoles;
}