<?php
// Configurações de ambiente
define('ENVIRONMENT', 'development'); // production | development

// Configurações de URL
define('BASE_URL', 'http://192.168.1.106/teste2');

// Configurações de banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_educacional');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurações de upload
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);

// Níveis de acesso
define('ROLE_ADMIN', 'admin');
define('ROLE_TEACHER', 'professor');
define('ROLE_STUDENT', 'aluno');

// Configuração de erro reporting
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