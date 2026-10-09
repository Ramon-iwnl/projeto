<?php
// Configuração do banco de dados da BoardSurf.
// Para XAMPP padrão, estes valores normalmente funcionam sem alteração.

function get_db() {
    static $db = null;
    if ($db instanceof PDO) return $db;

    $host = getenv('BOARDSURF_DB_HOST') ?: '127.0.0.1';
    $dbname = getenv('BOARDSURF_DB_NAME') ?: 'boardsurf';
    $user = getenv('BOARDSURF_DB_USER') ?: 'root';
    $pass = getenv('BOARDSURF_DB_PASSWORD') ?: '';
    $port = getenv('BOARDSURF_DB_PORT') ?: '3306';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $db;
}

function start_app_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function require_method(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        json_response(['ok' => false, 'message' => 'Método não permitido.'], 405);
    }
}

function input_json(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : [];
}

function current_user(): ?array {
    start_app_session();
    return $_SESSION['user'] ?? null;
}

function require_login(): array {
    $user = current_user();
    if (!$user) json_response(['ok' => false, 'message' => 'É necessário estar autenticado.'], 401);
    return $user;
}

function require_master(): array {
    $user = require_login();
    if (($user['perfil'] ?? '') !== 'MASTER') {
        json_response(['ok' => false, 'message' => 'Acesso permitido somente ao Usuário Master.'], 403);
    }
    return $user;
}

function audit(PDO $db, ?int $usuarioId, ?string $login, ?string $nome, string $evento, string $resultado, string $fator = 'N/A'): void {
    $cpf = null;
    if ($usuarioId) {
        $q = $db->prepare('SELECT cpf FROM usuarios WHERE id = ? LIMIT 1');
        $q->execute([$usuarioId]);
        $cpf = $q->fetchColumn() ?: null;
    }
    $stmt = $db->prepare('INSERT INTO logs_autenticacao (usuario_id, login, nome, cpf, evento, resultado, fator_autenticacao, ip, data_hora) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([
        $usuarioId, $login, $nome, $cpf, $evento, $resultado, $fator, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
    ]);
}

function clean_user(array $row): array {
    return [
        'id' => (int)$row['id'],
        'nome' => $row['nome'],
        'login' => $row['login'],
        'email' => $row['email'],
        'cpf' => $row['cpf'],
        'celular' => $row['celular'],
        'cidade' => $row['cidade'],
        'estado' => $row['estado'],
        'perfil' => $row['perfil'],
    ];
}

function letras(string $valor, int $quantidade): bool {
    return mb_strlen($valor, 'UTF-8') === $quantidade && preg_match('/^\p{L}+$/u', $valor) === 1;
}
function nome_valido(string $nome): bool {
    return mb_strlen($nome, 'UTF-8') >= 15 && mb_strlen($nome, 'UTF-8') <= 80 && preg_match('/^\p{L}+(?: +\p{L}+)*$/u', $nome) === 1;
}
function cpf_valido(string $cpf): bool {
    if (!preg_match('/^\d{11}$/', $cpf) || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
    for ($t = 9; $t <= 10; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) $soma += (int)$cpf[$i] * ($t + 1 - $i);
        $digito = ($soma * 10) % 11;
        if ($digito === 10) $digito = 0;
        if ($digito !== (int)$cpf[$t]) return false;
    }
    return true;
}
function telefone_nacional(string $telefone): string {
    $digitos = preg_replace('/\D/', '', $telefone);
    if (str_starts_with($digitos, '55') && (str_contains($telefone, '+') || strlen($digitos) > 11)) $digitos = substr($digitos, 2);
    return $digitos;
}
function normalizar_resposta(string $resposta, string $fator): string {
    $resposta = trim($resposta);
    if ($fator === 'CEP') return preg_replace('/\D/', '', $resposta);
    if ($fator === 'DATA_NASCIMENTO') {
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $resposta, $m)) return "$m[3]-$m[2]-$m[1]";
        return $resposta;
    }
    return mb_strtolower(preg_replace('/\s+/u', ' ', $resposta), 'UTF-8');
}
function pergunta_2fa(string $fator): string {
    return ['NOME_MATERNO'=>'Qual o nome da sua mãe?', 'DATA_NASCIMENTO'=>'Qual a data do seu nascimento?', 'CEP'=>'Qual o CEP do seu endereço?'][$fator];
}
