<?php

$localEnvPath = __DIR__ . '/mysql.local.env';
if (is_file($localEnvPath)) {
    if (!is_readable($localEnvPath)) {
        throw new \RuntimeException('数据库配置文件无法读取，请检查 config/mysql.local.env 的文件权限。');
    }

    $localEnvLines = file($localEnvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($localEnvLines === false) {
        throw new \RuntimeException('数据库配置文件读取失败，请检查 config/mysql.local.env。');
    }

    foreach ($localEnvLines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key !== '' && getenv($key) === false && !array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
            $_SERVER[$key] = $value;
            $_ENV[$key] = $value;
        }
    }
}

$env = static function (string $key, mixed $default = null): mixed {
    $value = getenv($key);
    if ($value === false && array_key_exists($key, $_SERVER)) {
        $value = $_SERVER[$key];
    }
    if ($value === false && array_key_exists($key, $_ENV)) {
        $value = $_ENV[$key];
    }

    return $value === false || $value === null || $value === '' ? $default : $value;
};

return [
    'driver' => 'mysql',
    'mysql' => [
        'host' => (string) $env('JX_DB_HOST', ''),
        'port' => (int) $env('JX_DB_PORT', 3306),
        'database' => (string) $env('JX_DB_DATABASE', ''),
        'username' => (string) $env('JX_DB_USERNAME', ''),
        'password' => (string) $env('JX_DB_PASSWORD', ''),
        'charset' => (string) $env('JX_DB_CHARSET', 'utf8mb4'),
        'prefix' => preg_replace('/[^a-zA-Z0-9_]+/', '', (string) $env('JX_DB_PREFIX', 'jx_')) ?: 'jx_',
    ],
];
