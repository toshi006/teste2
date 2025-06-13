<?php
/**
 * Funções auxiliares globais
 */

/**
 * Redireciona para uma URL com mensagens flash opcionais
 */
function redirect($url, $messages = []) {
    if (!empty($messages)) {
        $_SESSION['flash_messages'] = $messages;
    }
    header("Location: " . BASE_URL . "/" . ltrim($url, '/'));
    exit;
}

/**
 * Obtém mensagens flash e as remove da sessão
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Escapa output HTML para prevenir XSS
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Gera um token CSRF e o armazena na sessão
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida um token CSRF
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formata uma data para exibição
 */
function formatDate($dateString, $format = 'd/m/Y H:i') {
    $date = new DateTime($dateString);
    return $date->format($format);
}

/**
 * Sanitiza dados de entrada
 */
function sanitizeInput($data, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var(trim($data), FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'url':
            return filter_var(trim($data), FILTER_SANITIZE_URL);
        case 'string':
        default:
            return filter_var(trim($data), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    }
}

/**
 * Valida um endereço de email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Carrega uma view com os dados fornecidos
 */
function loadView($viewPath, $data = []) {
    extract($data);
    require __DIR__ . "/../views/{$viewPath}.php";
}

/**
 * Carrega um componente
 */
function loadComponent($componentName, $data = []) {
    extract($data);
    require __DIR__ . "/../templates/{$componentName}.php";
}

/**
 * Debug helper
 */
function dd($data) {
    if (ENVIRONMENT === 'development') {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        die();
    }
}
?>