<?php
declare(strict_types=1);

const APP_NAME = "Rice Mo'to";
const SHOP_LAT = 14.5995;
const SHOP_LNG = 120.9842;
const DELIVERY_RATE_PER_KM = 15.0;

$dbHost = getenv('RICE_MOTO_DB_HOST') ?: '127.0.0.1';
$dbName = getenv('RICE_MOTO_DB_NAME') ?: 'rice_moto_db';
$dbUser = getenv('RICE_MOTO_DB_USER') ?: 'root';
$dbPass = getenv('RICE_MOTO_DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    exit('Database connection failed. Check config.php and import schema.sql.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_user(array $roles = []): array
{
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Please sign in to continue.'], 401);
    }
    if ($roles && !in_array($user['role'], $roles, true)) {
        json_response(['error' => 'You do not have permission to perform this action.'], 403);
    }
    return $user;
}

function require_csrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        json_response(['error' => 'Your session token expired. Refresh the page and try again.'], 419);
    }
}

function log_activity(PDO $pdo, ?int $userId, string $action, string $details = ''): void
{
    $stmt = $pdo->prepare('INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
}

function clean_text(mixed $value, int $max = 500): string
{
    $text = trim((string)$value);
    if (mb_strlen($text) > $max) {
        throw new InvalidArgumentException("Text must be {$max} characters or fewer.");
    }
    return $text;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
