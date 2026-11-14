<?php
/**
 * One-time script: generate PUBLIC_SERVICE_TOKEN
 *
 * Run once from the project root:
 *   php gen_public_token.php
 *
 * Then copy the printed token to .env as PUBLIC_SERVICE_TOKEN=<token>
 * Delete this file afterwards.
 */

require_once __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Predis\Client as RedisClient;

// ── Load .env values ──────────────────────────────────────────────────────────
$env = [];
foreach (file(__DIR__ . '/.env') as $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v);
}

$jwtKey       = $env['JWT_KEY']        ?? '';
$redisHost    = $env['REDIS_HOST']     ?? '';
$redisPort    = (int)($env['REDIS_PORT']     ?? 6379);
$redisUser    = $env['REDIS_USERNAME'] ?? 'default';
$redisPass    = $env['REDIS_PASSWORD'] ?? '';
$redisDB      = (int)($env['REDIS_DATABASE']  ?? 0);
$redisProto   = $env['REDIS_PROTOCOL'] ?? 'tcp';

if (!$jwtKey || !$redisHost) {
    echo "ERROR: JWT_KEY or REDIS_HOST missing in .env\n";
    exit(1);
}

// ── Redis key for the public service session ──────────────────────────────────
$redisKey = 'r2k-public-service-0';

// ── Minimal JWT payload (same shape NestJS CiJwtGuard expects) ────────────────
$payload = [
    'User' => [
        'UserUID'      => 0,
        'UserName'     => 'Public Service',
        'UserEmail'    => '',
        'RoleUID'      => 0,
        'RoleName'     => 'Public',
        'BranchUID'    => 0,
        'HasLoginAccess' => 0,
    ],
    'Org' => [
        'OrgUID'       => 0,
        'OrgName'      => 'Public Service',
        'OrgToken'     => '',
        'StateCode'    => '',
        'StateName'    => '',
        'SectorCode'   => '',
    ],
    'Permissions'   => (object)[],
    'GenSettings'   => [
        'CurrenySymbol'  => '₹',
        'DecimalPoints'  => 2,
        'ListDateFormat' => 'd-m-Y',
    ],
    'ProdSettings'  => (object)[],
    'TransSettings' => (object)[],
    'AttachCfg'     => (object)[],
    'Subscription'  => (object)[],
];

// ── Store in Redis (no expiry — SET without EXPIRE) ───────────────────────────
echo "Connecting to Redis at {$redisHost}:{$redisPort}...\n";
try {
    $redis = new RedisClient([
        'scheme'   => $redisProto,
        'host'     => $redisHost,
        'port'     => $redisPort,
        'username' => $redisUser,
        'password' => $redisPass,
        'database' => $redisDB,
    ]);

    $redis->set($redisKey, json_encode($payload));
    // No expire() call — key lives forever
    echo "Redis key stored: {$redisKey}\n";
} catch (Exception $e) {
    echo "ERROR storing in Redis: " . $e->getMessage() . "\n";
    exit(1);
}

// ── Generate JWT (no exp — never expires) ─────────────────────────────────────
$jwtPayload = [
    'key' => $redisKey,
    'iss' => 'r2k-public',
    'iat' => time(),
    'nbf' => time(),
    // no 'exp' field → token never expires
];

$token = JWT::encode($jwtPayload, $jwtKey, 'HS256');

// ── Output ────────────────────────────────────────────────────────────────────
echo "\n";
echo "=============================================================\n";
echo "PUBLIC_SERVICE_TOKEN generated successfully!\n";
echo "=============================================================\n";
echo "\n";
echo "Add this line to your .env file:\n";
echo "\n";
echo "PUBLIC_SERVICE_TOKEN={$token}\n";
echo "\n";
echo "Redis key : {$redisKey}\n";
echo "JWT key   : (no expiry)\n";
echo "\n";
echo "Delete this file (gen_public_token.php) after copying the token.\n";
