<?php

$requestStartedAt = microtime(true);
$routeForSlowLog = '';
register_shutdown_function(static function () use ($requestStartedAt, &$routeForSlowLog): void {
    $elapsedMs = (int) round((microtime(true) - $requestStartedAt) * 1000);
    $thresholdMs = max(500, (int) (getenv('JX_SLOW_REQUEST_MS') ?: 1500));
    if ($elapsedMs < $thresholdMs) {
        return;
    }

    $logDir = __DIR__ . '/../log';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    $uri = substr((string) ($_SERVER['REQUEST_URI'] ?? ''), 0, 500);
    $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $status = http_response_code();
    $status = $status > 0 ? $status : 200;
    $route = $routeForSlowLog !== '' ? $routeForSlowLog : (string) ($_GET['route'] ?? '');
    $line = sprintf(
        "[%s] %dms status=%d method=%s route=%s uri=%s memory=%s\n",
        date('Y-m-d H:i:s'),
        $elapsedMs,
        $status,
        $method,
        $route !== '' ? $route : '-',
        $uri !== '' ? $uri : '/',
        number_format(memory_get_peak_usage(true))
    );
    @file_put_contents($logDir . '/slow-request.log', $line, FILE_APPEND);
});

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

session_start();

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (str_starts_with($class, $prefix)) {
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $segments = explode('/', $relative);
        if (!empty($segments[0])) {
            $segments[0] = strtolower($segments[0]);
        }
        $path = __DIR__ . '/../app/' . implode('/', $segments) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new \ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (\Throwable $exception): void {
    $message = $exception->getMessage();
    @file_put_contents(
        __DIR__ . '/../log/app-error.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $exception::class . ': ' . $message . "\n" . $exception->getTraceAsString() . "\n\n",
        FILE_APPEND
    );

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $isPermissionError = str_contains($message, 'Permission denied')
        || str_contains($message, '无法读取')
        || str_contains($message, 'mysql.local.env');
    $isDatabaseError = $isPermissionError
        || str_contains($message, 'MySQL')
        || str_contains($message, 'SQLSTATE')
        || str_contains($message, 'jx_meta.app_data');
    $title = $isDatabaseError ? '数据库连接失败' : '系统启动失败';
    $body = $isPermissionError
        ? '服务器无法读取数据库配置文件，请检查 config/mysql.local.env 的归属用户和读取权限。'
        : ($isDatabaseError ? '当前系统只使用 MySQL，请检查数据库配置、网络和 jx_meta.app_data 数据。' : $message);
    $detail = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
        . '</title><style>:root{color-scheme:light;--ink:#172033;--muted:#64748b;--line:#e6edf7;--blue:#426cff;--jade:#20b486;--rose:#ff5f7b}*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:28px;background:radial-gradient(circle at 18% 12%,rgba(66,108,255,.16),transparent 28%),radial-gradient(circle at 84% 18%,rgba(32,180,134,.14),transparent 24%),linear-gradient(180deg,#f7faff 0%,#eef4ff 100%);color:var(--ink);font-family:-apple-system,BlinkMacSystemFont,"PingFang SC","Microsoft YaHei",sans-serif}.box{position:relative;width:min(680px,calc(100vw - 32px));overflow:hidden;padding:38px;border:1px solid rgba(255,255,255,.72);border-radius:28px;background:rgba(255,255,255,.88);box-shadow:0 26px 80px rgba(37,57,111,.16);backdrop-filter:blur(18px)}.box:before{content:"";position:absolute;inset:0 0 auto;height:6px;background:linear-gradient(90deg,var(--blue),var(--jade),var(--rose))}.mark{width:58px;height:58px;display:grid;place-items:center;border-radius:20px;color:#fff;background:linear-gradient(135deg,var(--blue),var(--jade));font-size:28px;font-weight:950;box-shadow:0 16px 32px rgba(66,108,255,.24)}h1{margin:22px 0 12px;font-size:32px;line-height:1.2;letter-spacing:0}p{margin:0;color:var(--muted);font-size:16px;line-height:1.85}.hint{display:grid;gap:8px;margin-top:22px;padding:16px;border:1px solid var(--line);border-radius:18px;background:#f8fbff}.hint strong{font-size:14px}.hint code{overflow:auto;display:block;padding:12px;border-radius:12px;background:#162033;color:#edf6ff;font-size:13px;white-space:pre-wrap;word-break:break-word}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:24px}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:0 18px;border-radius:999px;background:var(--blue);color:#fff;text-decoration:none;font-weight:850}.btn.ghost{background:#edf3ff;color:#3156cf}@media(max-width:560px){.box{padding:28px}h1{font-size:26px}}</style></head><body><main class="box"><div class="mark">精</div><h1>'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
        . '</h1><p>'
        . htmlspecialchars($body, ENT_QUOTES, 'UTF-8')
        . '</p><section class="hint"><strong>技术信息</strong><code>'
        . $detail
        . '</code></section><div class="actions"><a class="btn" href="javascript:location.reload()">重新加载</a><a class="btn ghost" href="/jxdjadmin">进入后台</a></div></main></body></html>';
});

use App\Controller\AdminController;
use App\Controller\AccountController;
use App\Controller\ApiController;
use App\Controller\HomeController;
use App\Controller\PaymentController;
use App\Service\PlatformService;

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$prettyYulanId = null;
$prettyLandingSlug = null;
if (preg_match('#^/yulan/id/(\d+)$#', $path, $matches) === 1) {
    $prettyYulanId = (int) $matches[1];
}
if (preg_match('#^/lp/([A-Za-z0-9_-]+)$#', $path, $matches) === 1) {
    $prettyLandingSlug = (string) $matches[1];
}
$route = match ($path) {
    '/jxdjadmin', '/jxdjadmin/' => 'admin',
    '/jxdjadmin/logout' => 'admin-logout',
    '/duanju', '/duanju/' => 'duanju',
    '/juchang', '/juchang/' => 'juchang',
    '/zhuiju', '/zhuiju/' => 'zhuiju',
    '/wode', '/wode/' => 'wode',
    '/huiyuan', '/huiyuan/' => 'huiyuan',
    '/denglu', '/denglu/' => 'denglu',
    '/payment/jingxiu/notify' => 'jingxiu-notify',
    '/payment/superpay/notify', '/payment/payjf/notify' => 'superpay-notify',
    '/payment/status' => 'payment-status',
    '/payment/jingxiu/status' => 'payment-status',
    '/api/notification/sms/receipt' => 'api-sms-receipt',
    '/api/messages' => 'api-in-app-messages',
    '/api/messages/read' => 'api-in-app-message-read',
    '/api/messages/archive' => 'api-in-app-message-archive',
    '/api/messages/delete' => 'api-in-app-message-delete',
    default => $prettyLandingSlug !== null ? 'landing-page' : ($prettyYulanId !== null ? 'yulan' : ($_GET['route'] ?? 'home')),
};
$routeForSlowLog = (string) $route;
$routes = array_merge(
    require __DIR__ . '/../route/web.php',
    require __DIR__ . '/../route/api.php'
);

function render(string $view, array $data = []): void
{
    extract($data);
    $title = $data['title'] ?? match ($view) {
        'frontend/home' => '首页 - 精秀短剧',
        'frontend/drama' => (($drama['title'] ?? '剧集详情') . ' - 精秀短剧'),
        'frontend/watch' => (($episode['title'] ?? '播放页') . ' - ' . ($drama['title'] ?? '短剧')),
        'frontend/duanju' => '短剧首页 - 精秀短剧',
        'frontend/juchang' => '剧场 - 精秀短剧',
        'frontend/yulan' => (($drama['title'] ?? '播放预览') . ' - 精秀短剧'),
        'payment/result' => '支付结果 - 精秀短剧',
        'account/center' => '个人中心 - 精秀短剧',
        'account/bind' => '绑定账号 - 精秀短剧',
        'account/zhuiju' => '追剧 - 精秀短剧',
        'account/wode' => '我的 - 精秀短剧',
        'account/denglu' => '登录 - 精秀短剧',
        'account/huiyuan' => '会员中心 - 精秀短剧',
        'admin/login' => '后台登录 - 精秀短剧',
        'admin/dashboard' => '后台管理 - 精秀短剧',
        default => '精秀短剧',
    };
    $service = new PlatformService();
    $currentUser = $service->currentUser();
    $site_config ??= $service->siteConfig();
    require __DIR__ . '/../app/view/layout/header.php';
    require __DIR__ . '/../app/view/' . $view . '.php';
    require __DIR__ . '/../app/view/layout/footer.php';
}

$response = null;
if (isset($routes[$route])) {
    [$class, $method] = $routes[$route];
    $controller = new $class();
    $response = match ($route) {
        'drama', 'api-drama' => $controller->$method((int) ($_GET['id'] ?? 1)),
        'novel' => $controller->$method((int) ($_GET['id'] ?? 1)),
        'novel-read' => $controller->$method((int) ($_GET['novel_id'] ?? $_GET['id'] ?? 1), (int) ($_GET['chapter_id'] ?? 0)),
        'promo' => $controller->$method((string) ($_GET['code'] ?? '')),
        'landing-page', 'landing-click' => $controller->$method((string) ($prettyLandingSlug ?? ($_GET['slug'] ?? ''))),
        'watch' => $controller->$method((int) ($_GET['drama_id'] ?? 1), (int) ($_GET['episode_id'] ?? 101)),
        'yulan' => $controller->$method((int) ($prettyYulanId ?? ($_GET['drama_id'] ?? $_GET['id'] ?? 1)), (int) ($_GET['episode_id'] ?? 0)),
        'buy-episode', 'api-order-episode' => $controller->$method((int) ($_GET['drama_id'] ?? 1), (int) ($_GET['episode_id'] ?? 101)),
        'buy-membership' => $controller->$method((int) ($_GET['drama_id'] ?? 1)),
        'pay-callback', 'api-pay-callback' => $controller->$method((string) ($_GET['order_no'] ?? '')),
        'jingxiu-notify', 'superpay-notify' => $controller->$method(),
        default => $controller->$method(),
    };
}

if ($response === null) {
    $response = (new HomeController())->index();
}

if (isset($response['json'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response['json'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

if (isset($response['plain'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo $response['plain'];
    exit;
}

render($response['view'], $response['data']);
