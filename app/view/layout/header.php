<?php
$title = $title ?? '精秀短剧';
$isAdminView = isset($view) && str_starts_with((string) $view, 'admin/');
$viewClass = 'view-' . preg_replace('/[^a-z0-9_-]+/i', '-', str_replace('/', '-', (string) ($view ?? 'frontend/home')));
$siteName = trim((string) ($site_config['site_name'] ?? '精秀短剧')) ?: '精秀短剧';
$homeTemplate = (string) ($homepage_template ?? ($site_config['homepage_template'] ?? 'mini'));
if (!in_array($homeTemplate, ['mini', 'marketing', 'diy'], true)) {
    $homeTemplate = 'mini';
}
$novelTemplate = (string) ($novel_homepage_template ?? ($site_config['novel_homepage_template'] ?? 'library'));
if (!in_array($novelTemplate, ['library', 'ranking'], true)) {
    $novelTemplate = 'library';
}
$bodyClasses = [$isAdminView ? 'is-admin' : 'is-client', $viewClass];
if (!$isAdminView && (string) ($view ?? '') === 'frontend/home') {
    $bodyClasses[] = 'template-' . $homeTemplate;
}
if (!$isAdminView && (string) ($view ?? '') === 'frontend/novels') {
    $bodyClasses[] = 'novel-template-' . $novelTemplate;
}
$adminName = (string) (($current_admin['nickname'] ?? '') ?: ($current_admin['username'] ?? '个人中心'));
$csrfToken = (string) ($csrf_token ?? '');
if (!function_exists('jx_icon')) {
    function jx_icon(string $name): string
    {
        $icons = [
            'home' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V20h13v-9.5"/><path d="M9.5 20v-6h5v6"/>',
            'dashboard' => '<path d="M4 13h6V4H4z"/><path d="M14 20h6V4h-6z"/><path d="M4 20h6v-3H4z"/>',
            'drama' => '<path d="M5 5h14v14H5z"/><path d="M9 5v14"/><path d="M15 5v14"/><path d="M5 9h4"/><path d="M15 9h4"/><path d="M5 15h4"/><path d="M15 15h4"/>',
            'payment' => '<path d="M4 7h16v10H4z"/><path d="M4 10h16"/><path d="M7 15h4"/>',
            'order' => '<path d="M7 4h10l2 3v13H5V7z"/><path d="M8 10h8"/><path d="M8 14h8"/><path d="M8 18h5"/>',
            'revenue' => '<path d="M4 7h16v11H4z"/><path d="M7 7V5h10v2"/><path d="M15.5 12.5h2"/><path d="M10.5 10.2c-.6-.5-2-.5-2.6.1-.7.7-.2 1.7 1.2 2 1.7.4 2.2 1.4 1.4 2.1-.7.7-2.2.6-2.9-.1"/><path d="M9 9v6"/>',
            'orders' => '<path d="M7 4h10l2 3v13H5V7z"/><path d="m8 13 2 2 4-5"/><path d="M8 18h7"/>',
            'profit' => '<path d="M4 19h16"/><path d="M6 16l4-4 3 3 5-7"/><path d="M15 8h3v3"/>',
            'withdraw' => '<path d="M4 8h16v10H4z"/><path d="M7 8V6h10v2"/><path d="M8 14h4"/><path d="M15 13l2 2 2-2"/><path d="M17 11v4"/>',
            'stats' => '<path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16v-5"/><path d="M12 16V8"/><path d="M16 16v-7"/><path d="M20 16v-3"/>',
            'user' => '<path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/><path d="M4 21a8 8 0 0 1 16 0"/>',
            'banner' => '<path d="M4 6h16v12H4z"/><path d="m7 15 3-3 2 2 3-4 2 3"/>',
            'design' => '<path d="M4 20h16"/><path d="M7 16 17.5 5.5a2.1 2.1 0 0 1 3 3L10 19l-5 1z"/><path d="m14.5 8.5 3 3"/>',
            'setting' => '<path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z"/><path d="M4 12h2"/><path d="M18 12h2"/><path d="m6.3 6.3 1.4 1.4"/><path d="m16.3 16.3 1.4 1.4"/><path d="m17.7 6.3-1.4 1.4"/><path d="m7.7 16.3-1.4 1.4"/>',
            'account' => '<path d="M8 7h8"/><path d="M8 12h8"/><path d="M8 17h5"/><path d="M5 3h14v18H5z"/>',
            'logout' => '<path d="M10 5H5v14h5"/><path d="M14 8l4 4-4 4"/><path d="M9 12h9"/>',
        ];
        $paths = $icons[$name] ?? $icons['dashboard'];

        return '<span class="ui-icon" aria-hidden="true"><svg viewBox="0 0 24 24" role="img">' . $paths . '</svg></span>';
    }
}
if (!function_exists('jx_payment_method_key')) {
    function jx_payment_method_key(array $route): string
    {
        $text = strtolower((string) (($route['payment_method'] ?? '') . ' ' . ($route['payment_method_name'] ?? '') . ' ' . ($route['trade_type'] ?? '') . ' ' . ($route['pay_type'] ?? '')));
        if (str_contains($text, 'alipay') || str_contains($text, '支付宝')) {
            return 'alipay';
        }
        if (str_contains($text, 'wechat') || str_contains($text, 'weixin') || str_contains($text, 'wxpay') || str_contains($text, '微信')) {
            return 'wechat';
        }
        if (str_contains($text, 'union') || str_contains($text, '云闪付') || str_contains($text, '银联')) {
            return 'unionpay';
        }

        return 'default';
    }
}
if (!function_exists('jx_payment_icon')) {
    function jx_payment_icon(array $route): string
    {
        $key = jx_payment_method_key($route);
        $label = match ($key) {
            'alipay' => '支',
            'wechat' => '微',
            'unionpay' => '银',
            default => '付',
        };
        $title = match ($key) {
            'alipay' => '支付宝',
            'wechat' => '微信支付',
            'unionpay' => '云闪付',
            default => '支付方式',
        };

        return '<span class="payment-method-icon is-' . htmlspecialchars($key) . '" aria-label="' . htmlspecialchars($title) . '">' . htmlspecialchars($label) . '</span>';
    }
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        :root {
            --bg: #101410;
            --bg-soft: #171c17;
            --ink: #fff7e8;
            --muted: #aeb7ab;
            --dim: #738073;
            --line: rgba(255, 247, 232, .13);
            --panel: rgba(24, 29, 24, .78);
            --panel-strong: rgba(34, 40, 33, .94);
            --field: rgba(255, 247, 232, .06);
            --gold: #f0c76a;
            --jade: #67d2b0;
            --ember: #ff7044;
            --rose: #e85d75;
            --shadow: 0 24px 70px rgba(0, 0, 0, .34);
            --radius-lg: 28px;
            --radius-md: 18px;
        }
        * { box-sizing: border-box; }
        html {
            max-width: 100%;
            overflow-x: hidden;
        }
        body {
            margin: 0;
            min-height: 100vh;
            max-width: 100%;
            overflow-x: hidden;
            color: var(--ink);
            background:
                radial-gradient(circle at 8% -8%, rgba(240, 199, 106, .24), transparent 34%),
                radial-gradient(circle at 86% 0%, rgba(103, 210, 176, .20), transparent 30%),
                radial-gradient(circle at 50% 120%, rgba(255, 112, 68, .18), transparent 32%),
                linear-gradient(180deg, #141914 0%, #090b09 100%);
            font-family: "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
        }
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: .24;
            background-image:
                linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: linear-gradient(to bottom, #000, transparent 70%);
        }
        a { color: inherit; text-decoration: none; }
        img { display: block; max-width: 100%; }
        input, textarea, select {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 12px 13px;
            color: var(--ink);
            background: var(--field);
            outline: none;
            font: inherit;
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        }
        input:focus, textarea:focus, select:focus {
            border-color: rgba(103, 210, 176, .72);
            box-shadow: 0 0 0 4px rgba(103, 210, 176, .12);
            background: rgba(255, 247, 232, .09);
        }
        input[type="checkbox"] { width: auto; accent-color: var(--gold); }
        textarea { min-height: 102px; resize: vertical; }
        label { display: grid; gap: 7px; color: var(--muted); font-size: 13px; }
        label span { color: var(--ink); }
        h1, h2, h3 { letter-spacing: -.04em; }
        .wrap { position: relative; z-index: 1; max-width: 1240px; margin: 0 auto; padding: 18px; }
        .topbar {
            position: sticky;
            top: 10px;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: rgba(16, 20, 16, .74);
            backdrop-filter: blur(18px);
            box-shadow: 0 12px 34px rgba(0, 0, 0, .18);
        }
        .brand { display: flex; align-items: center; gap: 0; font-weight: 900; letter-spacing: -.02em; white-space: nowrap; }
        .ui-icon {
            display: inline-grid;
            flex: 0 0 auto;
            width: 18px;
            height: 18px;
            place-items: center;
            color: currentColor;
        }
        .ui-icon svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
        }
        .brand-mark {
            width: 38px;
            height: 38px;
            border-radius: 13px;
            display: grid;
            place-items: center;
            color: #15140f;
            background: linear-gradient(135deg, var(--gold), var(--jade));
            box-shadow: 0 10px 24px rgba(103, 210, 176, .22);
            font-size: 16px;
            line-height: 1;
        }
        .nav { display: flex; flex-wrap: wrap; gap: 6px; color: var(--muted); }
        .nav a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 12px;
            border-radius: 999px;
            transition: background .2s ease, color .2s ease;
        }
        .nav a:hover { color: var(--ink); background: rgba(255, 247, 232, .09); }
        body.is-page-loading {
            cursor: wait;
        }
        .page-loader {
            position: fixed;
            inset: 0;
            z-index: 30000;
            display: grid;
            place-items: center;
            padding: 24px;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            background:
                radial-gradient(circle at 50% 42%, rgba(240, 199, 106, .18), transparent 26%),
                rgba(8, 11, 10, .58);
            backdrop-filter: blur(18px);
            transition: opacity .24s ease, visibility .24s ease;
        }
        .page-loader.is-visible {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
        .page-loader-card {
            position: relative;
            display: grid;
            justify-items: center;
            gap: 14px;
            width: min(260px, calc(100vw - 48px));
            padding: 30px 24px 26px;
            overflow: hidden;
            border: 1px solid rgba(255, 247, 232, .22);
            border-radius: 28px;
            color: #fff7e8;
            background:
                linear-gradient(145deg, rgba(22, 26, 22, .82), rgba(33, 39, 34, .72)),
                radial-gradient(circle at 50% 0%, rgba(103, 210, 176, .20), transparent 48%);
            box-shadow: 0 28px 90px rgba(0, 0, 0, .38);
            text-align: center;
        }
        .page-loader-card::before {
            content: "";
            position: absolute;
            inset: -40%;
            opacity: .42;
            background: conic-gradient(from 90deg, transparent, rgba(240, 199, 106, .28), transparent, rgba(103, 210, 176, .22), transparent);
            animation: pageLoaderSweep 2.2s linear infinite;
        }
        .page-loader-orbit {
            position: relative;
            z-index: 1;
            display: grid;
            place-items: center;
            width: 74px;
            height: 74px;
            border-radius: 50%;
            background:
                radial-gradient(circle, rgba(255, 247, 232, .12) 0 42%, transparent 43%),
                conic-gradient(from 0deg, var(--gold), var(--jade), rgba(255, 112, 68, .88), var(--gold));
            box-shadow: 0 0 34px rgba(240, 199, 106, .18);
            animation: pageLoaderSpin .95s linear infinite;
        }
        .page-loader-orbit::before {
            content: "";
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: rgba(19, 24, 20, .92);
            box-shadow: inset 0 0 18px rgba(255, 247, 232, .08);
        }
        .page-loader-orbit::after {
            content: "";
            position: absolute;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: #fff7e8;
            box-shadow: 0 0 18px rgba(240, 199, 106, .86);
            transform: translateY(-31px);
            animation: pageLoaderPulse .95s ease-in-out infinite;
        }
        .page-loader-brand {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 5px;
        }
        .page-loader-brand strong {
            font-size: 22px;
            font-weight: 950;
            letter-spacing: -.05em;
        }
        .page-loader-brand span {
            color: rgba(255, 247, 232, .72);
            font-size: 13px;
            font-weight: 850;
        }
        @keyframes pageLoaderSpin {
            to { transform: rotate(360deg); }
        }
        @keyframes pageLoaderPulse {
            0%, 100% { opacity: .55; transform: translateY(-31px) scale(.78); }
            50% { opacity: 1; transform: translateY(-31px) scale(1.08); }
        }
        @keyframes pageLoaderSweep {
            to { transform: rotate(360deg); }
        }
        @media (prefers-reduced-motion: reduce) {
            .page-loader,
            .page-loader-card::before,
            .page-loader-orbit,
            .page-loader-orbit::after {
                animation-duration: 3s;
                transition-duration: .01ms;
            }
        }
        .hero {
            position: relative;
            overflow: hidden;
            display: grid;
            min-height: 440px;
            padding: clamp(28px, 5vw, 62px);
            border: 1px solid rgba(255, 247, 232, .16);
            border-radius: var(--radius-lg);
            background:
                linear-gradient(112deg, rgba(10, 12, 9, .94) 0%, rgba(19, 58, 48, .78) 48%, rgba(255, 112, 68, .22) 100%),
                url('/assets/cover-1.svg') right center/contain no-repeat,
                #151a14;
            box-shadow: var(--shadow);
            align-content: end;
        }
        .hero::after {
            content: "";
            position: absolute;
            inset: auto -8% -28% 42%;
            height: 280px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(240, 199, 106, .28), transparent 65%);
            filter: blur(16px);
        }
        .hero-content { position: relative; z-index: 1; display: grid; gap: 18px; max-width: 720px; }
        .hero h1 {
            margin: 0;
            font-family: "Songti SC", "STSong", "PingFang SC", serif;
            font-size: clamp(42px, 8vw, 92px);
            line-height: .92;
            letter-spacing: -.08em;
        }
        .hero p { max-width: 560px; margin: 0; color: rgba(255,247,232,.78); font-size: 18px; line-height: 1.75; }
        .hero-actions, .card-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .metric-row { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
        .metric {
            min-width: 116px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 18px;
            background: rgba(255, 247, 232, .08);
        }
        .metric strong { display: block; font-size: 20px; }
        .metric span { color: var(--muted); font-size: 12px; }
        .section-title {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            margin: 34px 0 14px;
        }
        .section-title h2 { margin: 0; font-size: clamp(24px, 4vw, 36px); }
        .card, .panel {
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            padding: 20px;
            background: var(--panel);
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
        }
        .panel.soft { background: rgba(255, 247, 232, .07); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(245px, 1fr)); gap: 18px; }
        .drama-card {
            position: relative;
            overflow: hidden;
            display: grid;
            gap: 13px;
            padding: 14px;
            transition: transform .2s ease, border-color .2s ease, background .2s ease;
        }
        .drama-card:hover {
            transform: translateY(-4px);
            border-color: rgba(240, 199, 106, .42);
            background: rgba(34, 40, 33, .92);
        }
        .drama-card img, .drama-cover {
            width: 100%;
            aspect-ratio: 3 / 4;
            object-fit: cover;
            border-radius: 20px;
            background: #263026;
        }
        .drama-card h3 { margin: 0; font-size: 22px; }
        .price-line { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; color: var(--muted); }
        .muted { color: var(--muted); }
        .dim { color: var(--dim); }
        .eyebrow, .tag, .pill {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            border-radius: 999px;
            padding: 6px 10px;
            background: rgba(240, 199, 106, .16);
            color: #ffe2a2;
            border: 1px solid rgba(240, 199, 106, .2);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .02em;
        }
        .pill.jade { color: #b9f7e2; background: rgba(103, 210, 176, .14); border-color: rgba(103, 210, 176, .22); }
        .pill.ember { color: #ffc2ae; background: rgba(255, 112, 68, .16); border-color: rgba(255, 112, 68, .24); }
        .btn, button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 42px;
            border: 1px solid transparent;
            border-radius: 999px;
            padding: 10px 16px;
            color: #11140f;
            background: linear-gradient(135deg, var(--gold), #ffd98a);
            cursor: pointer;
            font: inherit;
            font-weight: 900;
            box-shadow: 0 12px 28px rgba(240, 199, 106, .17);
        }
        .btn.primary, button.primary { background: linear-gradient(135deg, var(--ember), #ff9d68); color: #1a100b; }
        .btn.ghost {
            color: var(--ink);
            background: rgba(255, 247, 232, .06);
            border-color: var(--line);
            box-shadow: none;
        }
        .btn.danger { background: rgba(232, 93, 117, .18); color: #ffd4dc; border-color: rgba(232, 93, 117, .28); box-shadow: none; }
        .stack { display: grid; gap: 14px; }
        .split { display: grid; grid-template-columns: minmax(0, .85fr) minmax(280px, 1.15fr); gap: 22px; align-items: start; }
        .detail-hero { align-items: center; }
        .episode-list { display: grid; gap: 10px; }
        .episode-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 14px;
            background: rgba(255, 247, 232, .045);
        }
        .episode-meta { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .pay-panel {
            display: grid;
            gap: 12px;
            padding: 16px;
            border: 1px solid rgba(103, 210, 176, .24);
            border-radius: 20px;
            background: rgba(103, 210, 176, .08);
        }
        .payment-qr-grid {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 20px;
            align-items: center;
        }
        .qr-card {
            display: grid;
            place-items: center;
            width: fit-content;
            padding: 14px;
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 18px 36px rgba(0, 0, 0, .22);
        }
        .qr-svg {
            display: block;
            width: min(232px, 68vw);
            height: auto;
            border-radius: 12px;
        }
        .admin-shell { display: grid; grid-template-columns: 238px 1fr; gap: 18px; align-items: start; }
        .admin-menu {
            position: sticky;
            top: 88px;
            display: grid;
            gap: 8px;
            background: rgba(17, 21, 17, .86);
        }
        .admin-menu strong { font-size: 20px; margin-bottom: 8px; }
        .admin-menu a:not(.btn) {
            padding: 11px 12px;
            border-radius: 14px;
            color: var(--muted);
        }
        .admin-menu a:not(.btn):hover { color: var(--ink); background: rgba(255, 247, 232, .07); }
        .admin-panel { display: grid; gap: 16px; }
        .kpi-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 16px; }
        .kpi {
            padding: 16px;
            border-radius: 18px;
            background: rgba(255, 247, 232, .06);
            border: 1px solid var(--line);
        }
        .kpi strong { display: block; font-size: 28px; margin-top: 4px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; }
        .table-list { display: grid; gap: 10px; }
        .row-card {
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 14px;
            background: rgba(255, 247, 232, .045);
        }
        .row-card h3, .row-card p { margin-top: 0; }
        .admin-login {
            min-height: 74vh;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 390px;
            gap: 32px;
            align-items: center;
        }
        .admin-login h1 {
            margin: 0;
            font-family: "Songti SC", "STSong", "PingFang SC", serif;
            font-size: clamp(48px, 8vw, 96px);
            line-height: .92;
            letter-spacing: -.08em;
        }
        .login-panel {
            display: grid;
            gap: 14px;
            padding: 24px;
            border: 1px solid var(--line);
            border-radius: 26px;
            background: var(--panel-strong);
            box-shadow: var(--shadow);
        }
        .notice {
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(103, 210, 176, .12);
            color: #c4f6e5;
            border: 1px solid rgba(103, 210, 176, .2);
        }
        .notice.warn { background: rgba(255, 112, 68, .12); color: #ffd0c0; border-color: rgba(255, 112, 68, .24); }
        .banner-strip {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: center;
            padding: 18px;
            border-radius: 22px;
            background:
                linear-gradient(135deg, rgba(240, 199, 106, .14), rgba(103, 210, 176, .08)),
                rgba(255, 247, 232, .045);
            border: 1px solid var(--line);
        }
        .video-shell { overflow: hidden; padding: 0; }
        .video-shell header { padding: 20px 20px 0; }
        video { width: 100%; max-height: 560px; background: #000; border-radius: 0 0 var(--radius-md) var(--radius-md); }
        .lock-box {
            min-height: 320px;
            display: grid;
            place-items: center;
            text-align: center;
            padding: 32px;
            background:
                radial-gradient(circle at 50% 0%, rgba(255, 112, 68, .18), transparent 46%),
                rgba(0, 0, 0, .26);
        }
        pre {
            overflow: auto;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
            border-radius: 18px;
            padding: 14px;
            background: rgba(0, 0, 0, .36);
            color: #eaf2ed;
            border: 1px solid var(--line);
        }
        :root {
            --bg: #f3f7ff;
            --bg-soft: #eef5ff;
            --ink: #202938;
            --muted: #7b8798;
            --dim: #a5afbf;
            --line: #e7edf7;
            --panel: rgba(255, 255, 255, .92);
            --panel-strong: #ffffff;
            --field: #f8fbff;
            --gold: #4d74ff;
            --jade: #58c45f;
            --ember: #ff9f45;
            --rose: #ef4b5f;
            --shadow: 0 16px 38px rgba(65, 92, 143, .10);
            --radius-lg: 24px;
            --radius-md: 18px;
        }
        body {
            color: var(--ink);
            background:
                radial-gradient(circle at 12% 0%, rgba(90, 124, 255, .14), transparent 30%),
                radial-gradient(circle at 90% 12%, rgba(91, 196, 255, .16), transparent 32%),
                linear-gradient(135deg, #f7f9ff 0%, #edf5ff 100%);
        }
        body::before {
            opacity: .48;
            background-image: linear-gradient(120deg, rgba(77, 116, 255, .06), rgba(94, 206, 255, .04));
            background-size: auto;
            mask-image: none;
        }
        input, textarea, select {
            color: var(--ink);
            background: var(--field);
            border-color: #dfe8f5;
        }
        input:focus, textarea:focus, select:focus {
            border-color: rgba(77, 116, 255, .68);
            box-shadow: 0 0 0 4px rgba(77, 116, 255, .12);
            background: #fff;
        }
        label, .muted { color: var(--muted); }
        label span { color: var(--ink); }
        .wrap { max-width: 1420px; }
        .topbar {
            border-radius: 22px;
            border-color: rgba(218, 226, 240, .9);
            background: rgba(255, 255, 255, .82);
            box-shadow: 0 10px 30px rgba(65, 92, 143, .10);
        }
        .brand { color: #d83a31; font-size: 22px; }
        .brand-mark {
            color: #fff;
            background: linear-gradient(135deg, #426cff, #6aa3ff);
            box-shadow: 0 10px 22px rgba(66, 108, 255, .22);
        }
        .nav { color: #657386; font-weight: 700; }
        .nav a:hover { color: #3568ff; background: #eef4ff; }
        .hero {
            color: var(--ink);
            background:
                linear-gradient(135deg, rgba(255,255,255,.96), rgba(236,244,255,.90)),
                url('/assets/cover-1.svg') right center/contain no-repeat;
            border-color: var(--line);
        }
        .hero::after { background: radial-gradient(circle, rgba(77,116,255,.18), transparent 65%); }
        .hero h1 { font-family: "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif; letter-spacing: -.06em; }
        .hero p { color: #657386; }
        .card, .panel {
            background: rgba(255, 255, 255, .92);
            border-color: rgba(226, 234, 247, .92);
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
        }
        .panel.soft, .row-card, .episode-row {
            background: #f9fbff;
            border-color: #e6edf8;
        }
        .metric, .kpi {
            background: #f8fbff;
            border-color: #e6edf8;
        }
        .eyebrow, .tag, .pill {
            color: #3568ff;
            background: #edf4ff;
            border-color: #dbe7ff;
        }
        .pill.jade { color: #29a044; background: #ecfaef; border-color: #d5f1db; }
        .pill.ember { color: #e07018; background: #fff3e7; border-color: #ffe2c4; }
        .btn, button {
            color: #fff;
            background: linear-gradient(135deg, #426cff, #5d82ff);
            box-shadow: 0 12px 24px rgba(66, 108, 255, .18);
        }
        .btn.primary, button.primary { color: #fff; background: linear-gradient(135deg, #ff7b4b, #ffab62); }
        .btn.ghost {
            color: #3b67db;
            background: #eef4ff;
            border-color: #dce7ff;
        }
        .btn.danger {
            color: #d63045;
            background: #fff0f2;
            border-color: #ffd9df;
        }
        .pay-panel {
            background: #f5fbff;
            border-color: #dbeeff;
        }
        .admin-shell {
            grid-template-columns: 210px minmax(0, 1fr);
            gap: 18px;
        }
        .admin-menu {
            top: 88px;
            min-height: calc(100vh - 120px);
            border-radius: 18px;
            background: rgba(255,255,255,.88);
        }
        .admin-menu strong {
            color: #d83a31;
            font-size: 24px;
        }
        .admin-menu a:not(.btn) {
            color: #657386;
            font-weight: 700;
        }
        .admin-menu a:not(.btn):hover {
            color: #3568ff;
            background: #eef4ff;
        }
        .kpi-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .admin-panel h1 { margin-bottom: 6px; }
        .notice {
            background: #ecfaef;
            color: #23823a;
            border-color: #d7f1dc;
        }
        .notice.warn {
            background: #fff3e7;
            color: #c95f11;
            border-color: #ffe0bd;
        }
        .login-panel {
            background: rgba(255,255,255,.92);
            border-color: var(--line);
        }
        .admin-login h1 {
            font-family: "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
            color: #1f2937;
        }
        .banner-strip {
            background: linear-gradient(135deg, #f8fbff, #f1f7ff);
            border-color: #e6edf8;
        }
        .video-shell, .lock-box { background: #fff; }
        pre {
            background: #f5f8ff;
            color: #334155;
            border-color: #e2eaf6;
        }
        .order-table { display: grid; gap: 10px; }
        .order-row {
            display: grid;
            grid-template-columns: minmax(220px, 1.2fr) minmax(120px, .6fr) minmax(120px, .6fr) minmax(260px, 1fr);
            gap: 12px;
            align-items: center;
        }
        .order-row-head {
            padding: 0 14px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
        }
        .order-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }
        .inline-form { display: inline-flex; }
        .inline-form button { min-height: 36px; padding: 7px 12px; font-size: 13px; }
        .filter-preset-panel {
            display: grid;
            gap: 12px;
            margin: -4px 0 16px;
            padding: 14px;
            border: 1px solid #e7eef8;
            border-radius: 18px;
            background: #fff;
        }
        .filter-preset-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: stretch;
        }
        .filter-preset-item {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) auto;
            gap: 8px;
            align-items: center;
            min-width: min(100%, 320px);
            padding: 10px 12px;
            border: 1px solid #e8eef8;
            border-radius: 14px;
            background: #f9fbff;
        }
        .filter-preset-item a {
            display: grid;
            gap: 3px;
            min-width: 0;
            color: inherit;
            text-decoration: none;
        }
        .filter-preset-item strong,
        .filter-preset-item em {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .filter-preset-save {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) auto auto;
            gap: 10px;
            align-items: center;
        }
        .is-admin {
            background:
                radial-gradient(circle at 12% -10%, rgba(72, 108, 255, .16), transparent 28%),
                radial-gradient(circle at 92% 4%, rgba(96, 196, 255, .18), transparent 28%),
                linear-gradient(135deg, #f8fbff 0%, #eef5ff 48%, #f7fbff 100%);
        }
        .is-admin .wrap {
            max-width: 1740px;
            padding: 16px 22px 28px;
        }
        .admin-topbar {
            top: 12px;
            min-height: 70px;
            padding: 12px 18px;
            border-radius: 26px;
            background: rgba(255, 255, 255, .76);
            box-shadow: 0 18px 46px rgba(52, 82, 145, .12);
        }
        .admin-topbar .brand {
            min-width: 112px;
            color: #cf302b;
            font-size: 25px;
            letter-spacing: -.05em;
        }
        .admin-topbar .brand-mark {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            font-size: 15px;
            background: linear-gradient(135deg, #3f6cff, #74a7ff);
        }
        .admin-topbar .nav {
            flex: 1;
            justify-content: center;
            flex-wrap: nowrap;
            min-width: 0;
            overflow-x: auto;
            gap: 10px;
            white-space: nowrap;
            scrollbar-width: none;
        }
        .admin-topbar .nav::-webkit-scrollbar {
            display: none;
        }
        .admin-topbar .nav a {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 13px;
            color: #687586;
            font-size: 15px;
        }
        .admin-topbar .nav a.is-active {
            color: #3568ff;
            background: #edf4ff;
        }
        .admin-topbar .nav a:hover {
            transform: translateY(-1px);
        }
        .admin-user-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 104px;
            justify-content: flex-end;
            color: #5c6878;
            font-size: 14px;
            font-weight: 800;
        }
        .admin-user-chip::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #58c45f;
            box-shadow: 0 0 0 4px rgba(88, 196, 95, .14);
        }
        .admin-mobile-top-menu {
            display: none;
        }
        .admin-shell {
            grid-template-columns: 238px minmax(0, 1fr);
            gap: 18px;
        }
        .admin-shell > *,
        .admin-panel,
        .admin-panel > *,
        .admin-workbench,
        .insight-grid > * {
            min-width: 0;
        }
        .admin-menu {
            top: 98px;
            overflow: hidden;
            min-height: calc(100vh - 132px);
            padding: 20px;
            border-radius: 24px;
            background: rgba(255, 255, 255, .86);
            box-shadow: 0 18px 45px rgba(56, 83, 137, .10);
        }
        .admin-menu::after {
            content: "精秀短剧 Admin";
            margin-top: auto;
            padding-top: 18px;
            color: #a4afbf;
            border-top: 1px solid #edf2f8;
            font-size: 13px;
            text-align: center;
        }
        .admin-menu strong {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: #1f2a3a;
            font-size: 18px;
            letter-spacing: -.02em;
        }
        .admin-mobile-menu-toggle {
            display: none;
        }
        .admin-mobile-menu-head,
        .admin-mobile-primary-grid,
        .admin-mobile-drawer-backdrop {
            display: none;
        }
        .secondary-menu-group {
            display: none;
            gap: 8px;
        }
        .secondary-menu-group.is-active {
            display: grid;
        }
        .admin-menu a:not(.btn) {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 46px;
            padding: 11px 13px;
            border-radius: 14px;
            color: #687586;
            font-weight: 800;
        }
        .admin-menu a:not(.btn) svg {
            flex: 0 0 auto;
        }
        .admin-menu-label {
            display: flex;
            flex: 1 1 auto;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            min-width: 0;
        }
        .admin-menu-label [data-admin-menu-label] {
            overflow: hidden;
            min-width: 0;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .admin-menu a:not(.btn)::before {
            content: none;
        }
        .admin-menu a.is-active,
        .admin-menu a:not(.btn):hover {
            color: #3568ff;
            background: #edf4ff;
        }
        .admin-menu .btn {
            margin-top: 8px;
            width: 100%;
        }
        .admin-panel {
            gap: 18px;
        }
        .admin-section {
            display: none;
        }
        .admin-section.is-active {
            display: block;
        }
        .admin-workbench.admin-section.is-active {
            display: grid;
        }
        .admin-workbench {
            display: grid;
            gap: 18px;
            padding: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
            backdrop-filter: none;
        }
        .admin-panel .admin-section {
            display: none;
        }
        .admin-panel .admin-section.is-active {
            display: block;
        }
        .admin-panel .admin-workbench.admin-section.is-active {
            display: grid;
        }
        .kpi-grid {
            margin-top: 0;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        .dashboard-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }
        .kpi {
            position: relative;
            overflow: hidden;
            min-height: 148px;
            padding: 24px 24px 20px;
            border: 1px solid rgba(226, 234, 247, .88);
            border-radius: 26px;
            background:
                radial-gradient(circle at 96% 0%, var(--kpi-glow, rgba(66, 108, 255, .14)), transparent 42%),
                linear-gradient(135deg, rgba(255, 255, 255, .98), rgba(247, 250, 255, .92));
            box-shadow: 0 18px 42px rgba(65, 92, 143, .10);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .kpi:hover {
            transform: translateY(-3px);
            border-color: rgba(66, 108, 255, .22);
            box-shadow: 0 24px 54px rgba(65, 92, 143, .14);
        }
        .kpi::after { content: none; }
        .kpi-icon {
            position: absolute;
            right: 22px;
            top: 22px;
            display: grid;
            place-items: center;
            width: 58px;
            height: 58px;
            border-radius: 18px;
            color: #fff;
            background: var(--kpi-color, linear-gradient(135deg, #426cff, #759dff));
            box-shadow: 0 16px 30px var(--kpi-shadow, rgba(66, 108, 255, .22));
        }
        .kpi-icon::after {
            content: "";
            position: absolute;
            inset: -7px;
            border-radius: 24px;
            background: var(--kpi-color, linear-gradient(135deg, #426cff, #759dff));
            opacity: .12;
            z-index: -1;
        }
        .kpi-icon .ui-icon,
        .kpi-icon .ui-icon svg {
            width: 28px;
            height: 28px;
        }
        .kpi small {
            display: block;
            max-width: calc(100% - 78px);
            color: #738199;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .02em;
        }
        .kpi strong {
            position: relative;
            z-index: 1;
            display: block;
            margin-top: 18px;
            color: #172033;
            font-size: clamp(25px, 2.3vw, 34px);
            letter-spacing: -.04em;
        }
        .kpi em {
            display: block;
            margin-top: 8px;
            color: #8a96a8;
            font-style: normal;
            font-size: 13px;
        }
        .kpi em b {
            color: #45a84c;
            font-weight: 900;
        }
        .kpi.blue {
            --kpi-color: linear-gradient(135deg, #426cff, #78a2ff);
            --kpi-shadow: rgba(66, 108, 255, .24);
            --kpi-glow: rgba(66, 108, 255, .16);
        }
        .kpi.green {
            --kpi-color: linear-gradient(135deg, #38b965, #74d98a);
            --kpi-shadow: rgba(56, 185, 101, .22);
            --kpi-glow: rgba(56, 185, 101, .15);
        }
        .kpi.orange {
            --kpi-color: linear-gradient(135deg, #ff8a34, #ffc36b);
            --kpi-shadow: rgba(255, 138, 52, .24);
            --kpi-glow: rgba(255, 138, 52, .16);
        }
        .kpi.cyan {
            --kpi-color: linear-gradient(135deg, #19a9c7, #60d7ef);
            --kpi-shadow: rgba(25, 169, 199, .22);
            --kpi-glow: rgba(25, 169, 199, .15);
        }
        .dashboard-kpi {
            min-height: 112px;
            padding: 18px 18px 16px;
            border-color: #dfe8f5;
            border-radius: 16px;
            background: linear-gradient(180deg, #fff, #fbfdff);
            box-shadow: 0 10px 26px rgba(27, 44, 76, .06);
        }
        .dashboard-kpi:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 34px rgba(27, 44, 76, .09);
        }
        .dashboard-kpi::before {
            content: "";
            position: absolute;
            right: -36px;
            top: -52px;
            width: 118px;
            height: 118px;
            border-radius: 999px;
            background: var(--kpi-color, linear-gradient(135deg, #426cff, #759dff));
            opacity: .12;
        }
        .dashboard-kpi .kpi-icon {
            top: 16px;
            right: 16px;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            box-shadow: none;
        }
        .dashboard-kpi .kpi-icon::after {
            content: none;
        }
        .dashboard-kpi .kpi-icon .ui-icon,
        .dashboard-kpi .kpi-icon .ui-icon svg {
            width: 20px;
            height: 20px;
        }
        .dashboard-kpi small {
            max-width: calc(100% - 52px);
            color: #66748a;
            font-size: 12px;
        }
        .dashboard-kpi strong {
            margin-top: 14px;
            font-size: clamp(24px, 2vw, 30px);
            letter-spacing: -.02em;
        }
        .dashboard-kpi em {
            margin-top: 6px;
            color: #7e8ca1;
            font-size: 12px;
        }
        .insight-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(320px, .78fr);
            gap: 14px;
        }
        .insight-card {
            min-height: 280px;
            padding: 22px;
            border-radius: 18px;
        }
        .insight-card h2,
        .panel h2 {
            margin-top: 0;
            color: #1f2a3a;
            letter-spacing: -.04em;
        }
        .trend-insight {
            min-height: 330px;
        }
        .trend-card-head {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 12px 18px;
            align-items: center;
            margin-bottom: 4px;
        }
        .trend-card-head h2 {
            margin: 0;
            font-size: clamp(22px, 2.4vw, 28px);
        }
        .trend-chart {
            position: relative;
            overflow: hidden;
            margin-top: 12px;
            border: 1px solid #e8eef7;
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(255, 255, 255, .96), rgba(247, 250, 254, .96));
        }
        .trend-chart::before,
        .trend-chart::after {
            content: none;
        }
        .trend-svg {
            display: block;
            width: 100%;
            min-width: 0;
            height: auto;
        }
        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 22px;
            color: #303642;
            font-size: 13px;
            font-weight: 800;
        }
        .chart-legend span {
            display: inline-flex;
            align-items: center;
            gap: 9px;
        }
        .chart-legend span::before {
            content: "";
            width: 13px;
            height: 13px;
            border-radius: 999px;
            background: #fff;
            border: 3px solid #2868ff;
            box-shadow: 0 0 0 2px #f8fafc, -13px 0 0 -7px #2868ff, 13px 0 0 -7px #2868ff;
        }
        .chart-legend span:last-child::before {
            border-color: #09b534;
            box-shadow: 0 0 0 2px #f8fafc, -13px 0 0 -7px #09b534, 13px 0 0 -7px #09b534;
        }
        .trend-grid-line {
            stroke: #dfe7f1;
            stroke-width: 1;
        }
        .trend-axis-label,
        .trend-day,
        .trend-axis-title {
            fill: #747d8c;
            font-size: 12px;
            font-weight: 700;
        }
        .trend-axis-title {
            font-size: 15px;
            font-weight: 800;
        }
        .trend-axis-title-left {
            text-anchor: start;
        }
        .trend-axis-title-right {
            text-anchor: end;
        }
        .trend-axis-label-left {
            text-anchor: end;
        }
        .trend-axis-label-right {
            text-anchor: start;
        }
        .trend-day {
            text-anchor: middle;
        }
        .trend-line {
            fill: none;
            stroke-width: 4.5;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 1400;
            stroke-dashoffset: 1400;
            animation: trendDraw .95s ease forwards;
        }
        .trend-line.amount {
            stroke: #2868ff;
        }
        .trend-line.income {
            stroke: #09b534;
            stroke-width: 4.2;
            animation-delay: .12s;
        }
        .trend-point {
            fill: #fff;
            stroke-width: 3;
        }
        .trend-point.amount {
            stroke: #2868ff;
        }
        .trend-point.income {
            stroke: #09b534;
        }
        .trend-hitbox {
            fill: transparent;
            cursor: crosshair;
            pointer-events: all;
        }
        .trend-hover-layer {
            opacity: 0;
            pointer-events: none;
            transition: opacity .16s ease;
        }
        .trend-hover-zone:hover .trend-hover-layer,
        .trend-hover-zone:focus .trend-hover-layer,
        .trend-hover-zone.is-active .trend-hover-layer {
            opacity: 1;
        }
        .trend-hover-zone:focus {
            outline: none;
        }
        .trend-guide {
            fill: none;
            stroke: #8d96a5;
            stroke-dasharray: 5 4;
            stroke-width: 1.2;
        }
        .trend-guide-x {
            stroke: #a7b2c2;
        }
        .trend-axis-pill,
        .trend-x-pill {
            fill: #6f737d;
        }
        .trend-axis-pill-text,
        .trend-x-pill-text {
            text-anchor: middle;
            fill: #fff;
            font-size: 13px;
            font-weight: 800;
        }
        .trend-tooltip-card {
            filter: url(#trendTooltipShadow);
        }
        .trend-tooltip-box {
            fill: #fff;
            stroke: #eef2f7;
        }
        .trend-tooltip-date {
            fill: #696f79;
            font-size: 17px;
            font-weight: 800;
        }
        .trend-tooltip-dot.amount {
            fill: #2868ff;
        }
        .trend-tooltip-dot.income {
            fill: #09b534;
        }
        .trend-tooltip-label {
            fill: #707784;
            font-size: 15px;
            font-weight: 800;
        }
        .trend-tooltip-value {
            text-anchor: end;
            fill: #6a6f78;
            font-size: 15px;
            font-weight: 900;
        }
        .trend-empty {
            text-anchor: middle;
            fill: #8b97a9;
            font-size: 18px;
            font-weight: 900;
        }
        .trend-empty-state {
            display: grid;
            align-content: center;
            min-height: 300px;
            padding: 34px;
            color: #7b8799;
            text-align: center;
        }
        .trend-empty-state strong {
            color: #263246;
            font-size: 22px;
            letter-spacing: -.02em;
        }
        .trend-empty-state > span {
            margin-top: 8px;
            font-size: 13px;
            font-weight: 700;
        }
        .trend-empty-bars {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 10px;
            align-items: end;
            margin-top: 28px;
        }
        .trend-empty-bars i {
            display: grid;
            gap: 8px;
            justify-items: center;
            color: #9aa6b8;
            font-style: normal;
            font-size: 11px;
            font-weight: 800;
        }
        .trend-empty-bars b {
            width: 100%;
            height: 48px;
            border-radius: 10px 10px 4px 4px;
            background: linear-gradient(180deg, #eef4ff, #dfe8f7);
        }
        .trend-empty-bars i:nth-child(2n) b {
            height: 68px;
        }
        .trend-empty-bars i:nth-child(3n) b {
            height: 38px;
        }
        @keyframes trendDraw {
            to { stroke-dashoffset: 0; }
        }
        .data-screen-section {
            padding: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
        }
        .data-screen-shell {
            position: relative;
            overflow: hidden;
            min-height: 860px;
            padding: 22px;
            border: 1px solid rgba(76, 196, 255, .26);
            border-radius: 28px;
            color: #dff7ff;
            background:
                radial-gradient(circle at 50% 0%, rgba(0, 176, 255, .24), transparent 32%),
                radial-gradient(circle at 12% 20%, rgba(45, 116, 255, .20), transparent 26%),
                radial-gradient(circle at 86% 18%, rgba(53, 255, 214, .14), transparent 24%),
                linear-gradient(180deg, #061a3a 0%, #06102a 48%, #030816 100%);
            box-shadow: 0 24px 70px rgba(0, 25, 80, .25), inset 0 0 70px rgba(36, 152, 255, .08);
            isolation: isolate;
        }
        .data-screen-shell::before,
        .data-screen-shell::after {
            content: "";
            position: absolute;
            pointer-events: none;
            z-index: -1;
        }
        .data-screen-shell::before {
            inset: 0;
            opacity: .42;
            background:
                linear-gradient(rgba(59, 185, 255, .10) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59, 185, 255, .08) 1px, transparent 1px);
            background-size: 38px 38px;
            mask-image: radial-gradient(circle at 50% 36%, #000 0%, transparent 72%);
        }
        .data-screen-shell::after {
            inset: 12px;
            border: 1px solid rgba(76, 196, 255, .18);
            border-radius: 22px;
            box-shadow: inset 0 0 36px rgba(0, 200, 255, .08);
        }
        .data-screen-bg-grid {
            position: absolute;
            inset: 0;
            z-index: -1;
            background:
                radial-gradient(circle at 50% 52%, rgba(45, 255, 217, .13), transparent 22%),
                linear-gradient(115deg, transparent 0 42%, rgba(66, 170, 255, .08) 42% 43%, transparent 43% 100%);
            opacity: .9;
        }
        .data-screen-header {
            position: relative;
            display: grid;
            grid-template-columns: 1fr minmax(0, 1.25fr) auto 1fr;
            gap: 16px;
            align-items: center;
            margin-bottom: 18px;
            text-align: center;
        }
        .data-screen-header h1 {
            margin: 4px 0 0;
            color: #ecfbff;
            font-size: clamp(28px, 3.2vw, 46px);
            letter-spacing: .08em;
            text-shadow: 0 0 18px rgba(60, 202, 255, .52);
        }
        .data-screen-header span {
            color: #65e8ff;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .28em;
        }
        .data-screen-clock {
            display: grid;
            gap: 4px;
            justify-items: end;
            padding: 12px 16px;
            border: 1px solid rgba(101, 232, 255, .22);
            border-radius: 16px;
            background: rgba(3, 16, 42, .62);
        }
        .data-screen-clock strong {
            color: #fff;
            font-size: 22px;
            letter-spacing: .08em;
        }
        .data-screen-clock span {
            color: #7fb8d8;
            font-size: 12px;
            letter-spacing: .06em;
        }
        .screen-corner {
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(69, 213, 255, .9));
            box-shadow: 0 0 18px rgba(69, 213, 255, .56);
        }
        .screen-corner.right {
            background: linear-gradient(90deg, rgba(69, 213, 255, .9), transparent);
        }
        .data-screen-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }
        .screen-kpi {
            position: relative;
            overflow: hidden;
            min-height: 126px;
            padding: 18px;
            border: 1px solid rgba(80, 190, 255, .24);
            border-radius: 20px;
            background:
                radial-gradient(circle at 88% 0%, var(--screen-glow, rgba(39, 205, 255, .24)), transparent 42%),
                linear-gradient(135deg, rgba(11, 37, 83, .88), rgba(4, 18, 48, .78));
            box-shadow: inset 0 0 28px rgba(67, 188, 255, .08), 0 16px 34px rgba(0, 0, 0, .16);
        }
        .screen-kpi::before {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            height: 3px;
            background: var(--screen-line, linear-gradient(90deg, #39dcff, #4488ff));
            box-shadow: 0 0 18px var(--screen-shadow, rgba(57, 220, 255, .45));
        }
        .screen-kpi small {
            position: absolute;
            right: 16px;
            top: 16px;
            color: rgba(224, 248, 255, .16);
            font-size: 28px;
            font-weight: 950;
        }
        .screen-kpi span {
            display: block;
            color: #8fd9ff;
            font-size: 13px;
            font-weight: 900;
        }
        .screen-kpi strong {
            display: block;
            margin-top: 14px;
            color: #fff;
            font-size: clamp(26px, 2.2vw, 38px);
            letter-spacing: -.03em;
            text-shadow: 0 0 16px var(--screen-shadow, rgba(57, 220, 255, .36));
        }
        .screen-kpi i {
            position: absolute;
            left: 18px;
            bottom: 16px;
            width: 58%;
            height: 5px;
            border-radius: 999px;
            background: var(--screen-line, linear-gradient(90deg, #39dcff, #4488ff));
            opacity: .72;
        }
        .screen-kpi.green {
            --screen-line: linear-gradient(90deg, #38f6aa, #64ffdf);
            --screen-glow: rgba(56, 246, 170, .20);
            --screen-shadow: rgba(56, 246, 170, .42);
        }
        .screen-kpi.violet {
            --screen-line: linear-gradient(90deg, #9b7cff, #4cc8ff);
            --screen-glow: rgba(155, 124, 255, .20);
            --screen-shadow: rgba(155, 124, 255, .42);
        }
        .screen-kpi.amber {
            --screen-line: linear-gradient(90deg, #ffd36a, #ff8a34);
            --screen-glow: rgba(255, 171, 74, .19);
            --screen-shadow: rgba(255, 171, 74, .38);
        }
        .data-screen-grid {
            display: grid;
            grid-template-columns: minmax(250px, .88fr) minmax(360px, 1.35fr) minmax(250px, .88fr);
            gap: 16px;
            align-items: stretch;
        }
        .screen-column,
        .screen-center {
            display: grid;
            gap: 16px;
            min-width: 0;
        }
        .screen-card,
        .screen-map-card {
            position: relative;
            overflow: hidden;
            min-width: 0;
            border: 1px solid rgba(75, 184, 255, .24);
            border-radius: 20px;
            background: linear-gradient(180deg, rgba(8, 31, 72, .78), rgba(4, 18, 46, .72));
            box-shadow: inset 0 0 34px rgba(54, 189, 255, .07), 0 16px 36px rgba(0, 0, 0, .18);
        }
        .screen-card {
            padding: 18px;
        }
        .screen-card::before,
        .screen-map-card::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(90deg, rgba(60, 212, 255, .45), transparent 22%, transparent 78%, rgba(60, 212, 255, .35)) top / 100% 1px no-repeat,
                linear-gradient(180deg, rgba(60, 212, 255, .36), transparent 24%, transparent 76%, rgba(60, 212, 255, .25)) left / 1px 100% no-repeat;
            opacity: .72;
        }
        .screen-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }
        .screen-card-head h2 {
            margin: 0;
            color: #dff9ff;
            font-size: 19px;
            letter-spacing: .04em;
        }
        .screen-card-head span {
            color: #61dfff;
            font-size: 12px;
            font-weight: 900;
        }
        .screen-bar-chart {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 10px;
            align-items: end;
            min-height: 220px;
            padding: 12px 4px 0;
        }
        .screen-bar-item {
            display: grid;
            gap: 8px;
            min-width: 0;
            justify-items: center;
        }
        .screen-bar-track {
            display: flex;
            align-items: end;
            justify-content: center;
            gap: 4px;
            width: 100%;
            height: 168px;
            border-radius: 999px 999px 10px 10px;
            background: linear-gradient(180deg, rgba(63, 161, 255, .10), rgba(63, 161, 255, .02));
        }
        .screen-bar-track i {
            display: block;
            width: 10px;
            min-height: 8px;
            border-radius: 999px 999px 3px 3px;
            box-shadow: 0 0 14px currentColor;
        }
        .screen-bar-track .amount {
            color: rgba(58, 218, 255, .8);
            background: linear-gradient(180deg, #52f4ff, #2e7bff);
        }
        .screen-bar-track .income {
            color: rgba(62, 255, 170, .7);
            background: linear-gradient(180deg, #72ffcf, #16b67d);
        }
        .screen-bar-item span {
            overflow: hidden;
            max-width: 100%;
            color: #7fa8c9;
            font-size: 12px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .screen-mini-metrics {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 14px;
        }
        .screen-mini-metrics span {
            display: grid;
            gap: 4px;
            padding: 10px 12px;
            border-radius: 14px;
            color: #8aaed0;
            background: rgba(41, 117, 255, .09);
            font-size: 12px;
        }
        .screen-mini-metrics b {
            color: #fff;
            font-size: 18px;
        }
        .screen-channel-list,
        .screen-rank-list,
        .screen-order-stream {
            display: grid;
            gap: 12px;
        }
        .screen-channel-row {
            display: grid;
            gap: 8px;
        }
        .screen-channel-row div {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        .screen-channel-row strong {
            color: #e8fbff;
        }
        .screen-channel-row span {
            color: #83aacc;
            font-size: 12px;
        }
        .screen-channel-row i,
        .screen-rank-row i,
        .screen-status-item i {
            display: block;
            overflow: hidden;
            height: 8px;
            border-radius: 999px;
            background: rgba(97, 223, 255, .10);
        }
        .screen-channel-row b,
        .screen-rank-row em,
        .screen-status-item b {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #2f8bff, #48efff);
            box-shadow: 0 0 12px rgba(72, 239, 255, .46);
        }
        .screen-map-card {
            min-height: 430px;
            display: grid;
            place-items: center;
            padding: 28px;
            background:
                radial-gradient(circle at 50% 48%, rgba(65, 214, 255, .19), transparent 32%),
                linear-gradient(180deg, rgba(8, 31, 72, .78), rgba(4, 18, 46, .72));
        }
        .screen-orbit {
            position: relative;
            z-index: 1;
            display: grid;
            place-items: center;
            width: min(360px, 70vw);
            aspect-ratio: 1;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(52, 230, 255, .18), rgba(52, 230, 255, .04) 38%, transparent 62%);
        }
        .screen-orbit::before,
        .screen-orbit::after,
        .screen-orbit .orbit {
            content: "";
            position: absolute;
            border: 1px solid rgba(95, 226, 255, .28);
            border-radius: 999px;
            animation: screenPulse 3.2s ease-in-out infinite;
        }
        .screen-orbit::before { inset: 12%; }
        .screen-orbit::after { inset: 25%; animation-delay: .45s; }
        .screen-orbit .orbit.one { inset: 2%; }
        .screen-orbit .orbit.two { inset: 36%; animation-delay: .8s; }
        .screen-orbit .orbit.three {
            inset: 46%;
            background: radial-gradient(circle, #4df4ff, #2d79ff);
            box-shadow: 0 0 30px rgba(77, 244, 255, .7);
            border: 0;
        }
        .screen-orbit strong {
            position: relative;
            z-index: 2;
            color: #fff;
            font-size: clamp(28px, 3vw, 46px);
            letter-spacing: .06em;
            text-shadow: 0 0 24px rgba(77, 244, 255, .62);
        }
        .screen-orbit em {
            position: relative;
            z-index: 2;
            margin-top: 8px;
            color: #7ee8ff;
            font-style: normal;
            font-weight: 900;
        }
        .screen-radar {
            position: absolute;
            inset: 28px;
            pointer-events: none;
        }
        .screen-radar i {
            position: absolute;
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: #64ffdf;
            box-shadow: 0 0 18px #64ffdf;
        }
        .screen-radar i:nth-child(1) { left: 17%; top: 28%; }
        .screen-radar i:nth-child(2) { right: 18%; top: 38%; background: #ffe072; box-shadow: 0 0 18px #ffe072; }
        .screen-radar i:nth-child(3) { left: 45%; bottom: 18%; background: #9b7cff; box-shadow: 0 0 18px #9b7cff; }
        .screen-map-stats {
            position: absolute;
            left: 22px;
            right: 22px;
            bottom: 20px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }
        .screen-map-stats span {
            display: grid;
            gap: 4px;
            padding: 12px;
            border: 1px solid rgba(94, 222, 255, .18);
            border-radius: 14px;
            color: #8fb9d5;
            background: rgba(3, 16, 42, .55);
            font-size: 12px;
            text-align: center;
        }
        .screen-map-stats b {
            color: #fff;
            font-size: 22px;
        }
        .screen-status-card {
            min-height: 210px;
        }
        .screen-status-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }
        .screen-status-item {
            display: grid;
            gap: 8px;
            padding: 14px;
            border: 1px solid rgba(97, 223, 255, .14);
            border-radius: 16px;
            background: rgba(97, 223, 255, .06);
        }
        .screen-status-item strong {
            color: #fff;
            font-size: 28px;
        }
        .screen-status-item span {
            color: #8fb9d5;
            font-size: 13px;
            font-weight: 900;
        }
        .screen-status-item em {
            color: #61dfff;
            font-size: 12px;
            font-style: normal;
        }
        .screen-status-item.paid b { background: linear-gradient(90deg, #38f6aa, #64ffdf); }
        .screen-status-item.refund b { background: linear-gradient(90deg, #ffd36a, #ff8a34); }
        .screen-rank-row {
            display: grid;
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
        }
        .screen-rank-row > b {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: 11px;
            color: #03102a;
            background: linear-gradient(135deg, #54f3ff, #5d85ff);
            box-shadow: 0 0 18px rgba(84, 243, 255, .34);
        }
        .screen-rank-row strong {
            display: block;
            overflow: hidden;
            color: #e8fbff;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .screen-rank-row span {
            display: block;
            margin: 3px 0 8px;
            overflow: hidden;
            color: #87aeca;
            font-size: 12px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .screen-order-line {
            display: grid;
            grid-template-columns: 70px minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(97, 223, 255, .10);
        }
        .screen-order-line:last-child {
            border-bottom: 0;
        }
        .screen-order-line span {
            color: #64ffdf;
            font-size: 12px;
            font-weight: 900;
        }
        .screen-order-line strong {
            overflow: hidden;
            color: #dff7ff;
            font-size: 12px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .screen-order-line em {
            color: #ffe072;
            font-style: normal;
            font-weight: 900;
        }
        .screen-empty {
            margin: 0;
            color: #7fa8c9;
        }
        .data-screen-footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px 28px;
            margin-top: 16px;
            color: #76abc8;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .04em;
        }
        @keyframes screenPulse {
            0%, 100% { opacity: .5; transform: scale(.98); }
            50% { opacity: 1; transform: scale(1.02); }
        }
        .donut-wrap {
            display: grid;
            grid-template-columns: 138px minmax(0, 1fr);
            gap: 18px;
            align-items: center;
            min-height: 190px;
        }
        .donut {
            width: min(138px, 40vw);
            aspect-ratio: 1;
            border-radius: 999px;
            background: conic-gradient(#426cff 0 var(--channel-primary, 0%), #15b85f var(--channel-primary, 0%) 100%);
            display: grid;
            place-items: center;
            color: #22304a;
            box-shadow: inset 0 0 0 1px rgba(226, 234, 247, .8);
        }
        .donut::before {
            content: "";
            width: 62%;
            aspect-ratio: 1;
            border-radius: 999px;
            background: #fff;
            grid-area: 1 / 1;
            box-shadow: inset 0 0 0 1px #eef3fa;
        }
        .donut strong,
        .donut span {
            position: relative;
            z-index: 1;
            grid-area: 1 / 1;
        }
        .donut strong {
            align-self: center;
            margin-top: -12px;
            font-size: 24px;
            font-weight: 900;
        }
        .donut span {
            align-self: center;
            margin-top: 28px;
            color: #8390a4;
            font-size: 11px;
            font-weight: 800;
        }
        .donut.is-empty {
            background: conic-gradient(#d8dee8 0 100%);
        }
        .donut-legend {
            display: grid;
            gap: 10px;
            color: #5f6e81;
            font-size: 12px;
            font-weight: 800;
        }
        .donut-legend span {
            display: grid;
            grid-template-columns: auto 1fr;
            align-items: center;
            gap: 8px 10px;
            padding: 10px 12px;
            border: 1px solid #e8eef8;
            border-radius: 12px;
            background: #fbfdff;
        }
        .donut-legend span::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #426cff;
        }
        .donut-legend span:nth-child(2)::before {
            background: #15b85f;
        }
        .donut-legend span:nth-child(3)::before {
            background: #ff9b3d;
        }
        .donut-legend b,
        .donut-legend em {
            min-width: 0;
        }
        .donut-legend b {
            color: #263246;
        }
        .donut-legend em {
            grid-column: 2;
            color: #8b97a9;
            font-style: normal;
            font-weight: 700;
        }
        .system-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }
        .placeholder-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 12px;
            margin-top: 18px;
        }
        .admin-placeholder {
            min-height: 320px;
        }
        .system-item {
            min-height: 64px;
            padding: 12px 14px;
            border-radius: 14px;
            background: #fbfdff;
            border: 1px solid #e8eef8;
        }
        .system-item strong {
            display: block;
            color: #273244;
            font-size: 15px;
        }
        .system-item span {
            color: #8a96a8;
            font-size: 12px;
        }
        .payment-route-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin: 16px 0;
        }
        #admin-section-payment-channel {
            background: #f5f7fb;
            border-color: #edf1f7;
        }
        .payment-channel-notice {
            display: flex;
            gap: 10px;
            align-items: center;
            margin: 10px 0 14px;
            padding: 10px 14px;
            border: 1px solid #ffe3dc;
            border-radius: 4px;
            background: #fff0ed;
            color: #525f72;
            font-size: 13px;
            font-weight: 800;
        }
        .payment-channel-notice span {
            display: grid;
            place-items: center;
            flex: 0 0 18px;
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background: #2f63ff;
            color: #fff;
            font-size: 12px;
        }
        .payment-channel-notice p {
            flex: 1;
            margin: 0;
            line-height: 1.5;
        }
        .payment-channel-notice button {
            min-height: 24px;
            padding: 0 6px;
            border: 0;
            background: transparent;
            color: #778396;
            box-shadow: none;
            font-size: 18px;
        }
        .payment-channel-filter {
            display: grid;
            grid-template-columns: minmax(190px, 1fr) minmax(170px, .8fr) minmax(170px, .8fr) auto;
            gap: 14px 18px;
            align-items: end;
            padding: 18px;
            border: 1px solid #edf1f7;
            border-radius: 6px;
            background: #fff;
        }
        .payment-channel-filter label {
            display: grid;
            grid-template-columns: 70px minmax(0, 1fr);
            gap: 8px;
            align-items: center;
            color: #526176;
            font-size: 13px;
            font-weight: 900;
        }
        .payment-channel-filter-extra {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: minmax(170px, .7fr) minmax(190px, .9fr) minmax(190px, .9fr);
            gap: 14px 18px;
            padding-top: 2px;
        }
        .payment-channel-filter-extra[hidden] {
            display: none;
        }
        .payment-channel-filter input,
        .payment-channel-filter select {
            min-height: 36px;
            border-radius: 3px;
            background: #f2f4f7;
            border-color: #eef2f7;
            color: #4e5b6d;
            font-size: 13px;
        }
        .payment-channel-filter-actions,
        .payment-channel-toolbar,
        .payment-channel-toolbar-left,
        .payment-channel-toolbar-right,
        .payment-channel-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .payment-channel-toolbar {
            justify-content: space-between;
            margin: 12px 0 0;
            padding: 10px 12px;
            border: 1px solid #edf1f7;
            border-bottom: 0;
            border-radius: 6px 6px 0 0;
            background: #fff;
        }
        .payment-channel-toolbar .btn,
        .payment-channel-actions .btn,
        .channel-status-btn {
            min-height: 30px;
            padding: 6px 12px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 900;
            box-shadow: none;
        }
        .payment-channel-toolbar .btn.primary,
        .payment-channel-actions .btn {
            color: #fff;
            background: #2f63ff;
            border-color: #2f63ff;
        }
        .payment-channel-toolbar .btn.ghost {
            color: #2f63ff;
            background: #eef4ff;
            border-color: #dce7ff;
        }
        .channel-status-btn {
            border: 1px solid #e6ebf2;
            color: #58667a;
            background: #f7f9fc;
        }
        .channel-status-btn.is-active,
        .channel-status-btn:hover {
            color: #fff;
            background: #2f63ff;
            border-color: #2f63ff;
        }
        .channel-status-btn.is-enabled:not(.is-active) {
            color: #1f9a48;
            background: #eaf8ef;
            border-color: #d6f0dd;
        }
        .channel-status-btn.is-pending:not(.is-active) {
            color: #7a8596;
            background: #f2f4f7;
        }
        .channel-status-btn.is-disabled:not(.is-active) {
            color: #c84e57;
            background: #fff0f2;
            border-color: #ffd9df;
        }
        .payment-channel-toolbar-right {
            justify-content: flex-end;
            color: #6a7688;
            font-size: 12px;
            font-weight: 900;
        }
        .payment-channel-toolbar-right strong {
            color: #36b24a;
            font-size: 14px;
        }
        .payment-channel-table-wrap {
            overflow-x: auto;
            border: 1px solid #edf1f7;
            border-radius: 0 0 6px 6px;
            background: #fff;
        }
        .payment-channel-table {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
            color: #465267;
            font-size: 13px;
        }
        .payment-channel-table th,
        .payment-channel-table td {
            padding: 10px 12px;
            border-right: 1px solid #edf1f7;
            border-bottom: 1px solid #edf1f7;
            text-align: left;
            vertical-align: middle;
            white-space: nowrap;
        }
        .payment-channel-table th {
            background: #f7f9fc;
            color: #58667a;
            font-weight: 950;
        }
        .payment-channel-table tbody tr:hover {
            background: #fbfdff;
        }
        .payment-channel-table td strong {
            display: block;
            color: #253145;
            font-size: 13px;
        }
        .payment-channel-table td em {
            display: block;
            margin-top: 4px;
            color: #8a96a8;
            font-size: 12px;
            font-style: normal;
        }
        .payment-method-logo {
            display: grid;
            place-items: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            color: #fff;
            background: linear-gradient(135deg, #2f63ff, #5dd1ff);
            font-size: 13px;
            font-weight: 950;
        }
        .payment-channel-note {
            display: inline-block;
            max-width: 190px;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
        }
        .payment-channel-table code {
            padding: 4px 8px;
            border-radius: 3px;
            color: #98a3b2;
            background: #f0f2f5;
            font-family: inherit;
            font-weight: 900;
        }
        .method-pill {
            display: inline-flex;
            flex-direction: column;
            gap: 2px;
            min-width: 78px;
            padding: 5px 9px;
            border-radius: 8px;
            color: #1f55dc;
            background: #eef4ff;
            font-weight: 950;
        }
        .method-pill em {
            margin: 0;
            color: #7b8aa2;
            font-size: 11px;
            font-style: normal;
        }
        .channel-switch {
            display: inline-flex;
            gap: 6px;
            align-items: center;
            min-height: 28px;
            padding: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
            color: #8793a5;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
        }
        .channel-switch i {
            position: relative;
            width: 28px;
            height: 16px;
            border-radius: 999px;
            background: #c6ccd6;
        }
        .channel-switch i::after {
            content: "";
            position: absolute;
            top: 3px;
            left: 3px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #fff;
            transition: .2s ease;
        }
        .channel-switch.is-enabled i {
            background: #2f63ff;
        }
        .channel-switch.is-enabled i::after {
            transform: translateX(12px);
        }
        .channel-switch.is-pending i {
            background: #aab2bf;
        }
        .channel-state-controls {
            display: grid;
            gap: 6px;
            justify-items: start;
        }
        .channel-switch-form,
        .channel-default-form {
            margin: 0;
        }
        .channel-default-btn {
            min-height: 24px;
            padding: 3px 8px;
            border: 1px solid #dce7ff;
            border-radius: 999px;
            color: #2f63ff;
            background: #eef4ff;
            box-shadow: none;
            font-size: 12px;
            font-weight: 900;
        }
        .channel-default-btn.is-active,
        .channel-default-btn:disabled {
            color: #1f9a48;
            border-color: #d6f0dd;
            background: #eaf8ef;
            cursor: default;
            opacity: 1;
        }
        .money-tag {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: 950;
        }
        .money-tag.pink {
            color: #ec4fe4;
            background: #ffe9fd;
        }
        .money-tag.green {
            color: #2cbf4f;
            background: #eafbeb;
        }
        .payment-channel-actions {
            flex-wrap: nowrap;
        }
        .payment-channel-actions .btn.mini {
            min-height: 28px;
            padding: 5px 10px;
            color: #fff;
            background: #2f63ff;
        }
        .payment-channel-actions .btn.mini.ghost {
            color: #2f63ff;
            background: #eef4ff;
            border-color: #dce7ff;
        }
        .payment-channel-empty {
            padding: 28px;
            color: #8a96a8;
            text-align: center;
        }
        .payment-channel-footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 18px;
            padding: 12px 14px;
            color: #667386;
            background: #fff;
            border: 1px solid #edf1f7;
            border-top: 0;
            border-radius: 0 0 6px 6px;
            font-size: 13px;
            font-weight: 900;
        }
        body.has-payment-route-dialog {
            overflow: hidden;
        }
        body.has-payment-route-key-drawer {
            overflow: hidden;
        }
        body.has-channel-action-dialog {
            overflow: hidden;
        }
        body.has-payment-test-dialog {
            overflow: hidden;
        }
        .payment-route-dialog[hidden] {
            display: none;
        }
        .payment-route-drawer[hidden] {
            display: none;
        }
        .channel-action-dialog[hidden] {
            display: none;
        }
        .payment-test-dialog[hidden] {
            display: none;
        }
        .payment-route-dialog {
            position: fixed;
            inset: 0;
            z-index: 9050;
            display: grid;
            place-items: center;
            width: 100vw;
            height: 100vh;
            height: 100dvh;
            overflow: auto;
            padding: 22px;
        }
        .payment-route-dialog-backdrop {
            position: absolute;
            inset: 0;
            z-index: 0;
            background: rgba(11, 17, 31, .56);
            backdrop-filter: blur(3px);
        }
        .payment-route-dialog-card {
            position: relative;
            z-index: 1;
            overflow: hidden;
            display: grid;
            grid-template-rows: auto minmax(0, 1fr);
            width: min(980px, calc(100vw - 44px));
            max-height: min(90vh, 900px);
            max-height: min(90dvh, 900px);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 28px 80px rgba(17, 24, 39, .28);
        }
        .payment-route-dialog-card * {
            min-width: 0;
        }
        .payment-route-dialog-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 22px;
            border-bottom: 1px solid #edf1f7;
            background: linear-gradient(180deg, #fff, #f9fbff);
        }
        .payment-route-dialog-head h3 {
            margin: 5px 0 0;
            color: #172033;
            font-size: 21px;
        }
        .payment-route-dialog-head button {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            color: #68778d;
            background: #eef2f7;
            box-shadow: none;
            font-size: 22px;
        }
        .payment-route-dialog-form {
            overflow: auto;
            display: grid;
            gap: 14px;
            padding: 18px 22px 22px;
            background: #f6f8fb;
        }
        .payment-route-dialog-form section {
            display: grid;
            gap: 12px;
            padding: 16px;
            border: 1px solid #edf1f7;
            border-radius: 12px;
            background: #fff;
        }
        .payment-route-dialog-form h4 {
            margin: 0;
            color: #243047;
            font-size: 15px;
        }
        .payment-route-dialog-form label {
            display: grid;
            gap: 7px;
            color: #5a687d;
            font-size: 13px;
            font-weight: 900;
        }
        .payment-route-dialog-form input,
        .payment-route-dialog-form textarea {
            min-height: 40px;
            border-radius: 8px;
            background: #fbfcff;
            border-color: #e4ebf5;
        }
        .payment-route-dialog-form textarea {
            min-height: 96px;
            resize: vertical;
        }
        .payment-route-dialog-form select,
        .payment-route-key-form select {
            min-height: 40px;
            border-radius: 8px;
            background: #fbfcff;
            border-color: #e4ebf5;
        }
        .payment-route-checks {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .payment-route-checks label {
            padding: 9px 12px;
            border: 1px solid #e5ecf7;
            border-radius: 999px;
            background: #f8fbff;
        }
        .route-choice-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .payment-method-choice-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .route-choice-card {
            position: relative;
            display: grid;
            min-height: 82px;
            padding: 12px 14px 12px 42px;
            border: 1px solid #e4ebf5;
            border-radius: 14px;
            background:
                radial-gradient(circle at 88% 0%, rgba(47, 99, 255, .08), transparent 38%),
                #fbfcff;
            cursor: pointer;
            transition: border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .18s ease;
        }
        .route-choice-card:hover,
        .route-choice-card.is-active {
            border-color: #2f63ff;
            background:
                radial-gradient(circle at 88% 0%, rgba(47, 99, 255, .16), transparent 42%),
                #f5f8ff;
            box-shadow: 0 12px 28px rgba(47, 99, 255, .12);
            transform: translateY(-1px);
        }
        .route-choice-card input {
            position: absolute;
            top: 15px;
            left: 14px;
            width: 16px;
            height: 16px;
            min-height: 0;
            margin: 0;
            accent-color: #2f63ff;
        }
        .route-choice-card span {
            display: grid;
            gap: 5px;
        }
        .route-choice-card strong {
            color: #1f2b43;
            font-size: 14px;
            font-weight: 950;
        }
        .route-choice-card em {
            color: #7b8798;
            font-size: 12px;
            font-style: normal;
            font-weight: 800;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }
        .route-provider-hint {
            padding: 12px 14px;
            border: 1px solid #dce7ff;
            border-radius: 12px;
            color: #2459d6;
            background: #eef4ff;
            font-size: 13px;
            font-weight: 900;
            line-height: 1.6;
        }
        .route-secret-panel {
            display: grid;
            gap: 10px;
            padding: 14px;
            border: 1px dashed #dbe5f4;
            border-radius: 12px;
            background: #fbfdff;
        }
        .route-secret-panel[hidden] {
            display: none;
        }
        .route-secret-panel h5 {
            margin: 0;
            color: #243047;
            font-size: 14px;
            font-weight: 950;
        }
        .payment-route-dialog-form input[readonly] {
            color: #7a8798;
            background: #eef2f7;
            cursor: not-allowed;
        }
        .payment-route-dialog-form footer {
            position: sticky;
            bottom: -22px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: flex-end;
            padding: 14px 0 0;
            background: linear-gradient(180deg, rgba(246,248,251,0), #f6f8fb 34%);
        }
        .payment-route-dialog-form footer .muted {
            margin-right: auto;
        }
        .payment-route-drawer {
            position: fixed;
            inset: 0;
            z-index: 9100;
            display: grid;
            justify-items: end;
            width: 100vw;
            height: 100vh;
            height: 100dvh;
        }
        .payment-route-drawer-backdrop {
            position: absolute;
            inset: 0;
            z-index: 0;
            background: rgba(15, 23, 42, .42);
            backdrop-filter: blur(2px);
        }
        .payment-route-drawer-card {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-rows: auto minmax(0, 1fr);
            width: min(560px, calc(100vw - 20px));
            height: 100%;
            background: #fff;
            box-shadow: -24px 0 70px rgba(17, 24, 39, .24);
        }
        .payment-route-drawer-card * {
            min-width: 0;
        }
        .payment-route-drawer-head {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 22px 24px;
            border-bottom: 1px solid #edf1f7;
            background:
                radial-gradient(circle at 84% 12%, rgba(47, 99, 255, .14), transparent 34%),
                #fff;
        }
        .payment-route-drawer-head h3 {
            margin: 6px 0 4px;
            color: #172033;
            font-size: 21px;
        }
        .payment-route-drawer-head p {
            margin: 0;
            color: #758399;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.5;
        }
        .payment-route-drawer-head button {
            display: grid;
            place-items: center;
            flex: 0 0 34px;
            width: 34px;
            height: 34px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            color: #68778d;
            background: #eef2f7;
            box-shadow: none;
            font-size: 22px;
        }
        .payment-route-key-form {
            overflow: auto;
            display: grid;
            align-content: start;
            gap: 14px;
            padding: 18px 24px 22px;
            background: #f6f8fb;
        }
        .payment-route-key-form section,
        .payment-route-key-note {
            display: grid;
            gap: 12px;
            padding: 16px;
            border: 1px solid #edf1f7;
            border-radius: 12px;
            background: #fff;
        }
        .payment-route-key-form h4 {
            margin: 0;
            color: #243047;
            font-size: 15px;
        }
        .payment-route-key-form label {
            display: grid;
            gap: 7px;
            color: #5a687d;
            font-size: 13px;
            font-weight: 900;
        }
        .payment-route-key-form input,
        .payment-route-key-form textarea {
            min-height: 40px;
            border-radius: 8px;
            background: #fbfcff;
            border-color: #e4ebf5;
        }
        .payment-route-key-form textarea {
            min-height: 110px;
            resize: vertical;
        }
        .payment-route-key-note {
            color: #526176;
            font-size: 13px;
            font-weight: 850;
            line-height: 1.55;
        }
        .payment-route-key-form footer {
            position: sticky;
            bottom: -22px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 14px 0 0;
            background: linear-gradient(180deg, rgba(246,248,251,0), #f6f8fb 34%);
        }
        .payment-subsection {
            background: #f5f7fb;
            border-color: #edf1f7;
        }
        .payment-method-table {
            min-width: 860px;
        }
        .payment-rule-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin: 16px 0;
        }
        .channel-action-dialog {
            position: fixed;
            inset: 0;
            z-index: 9120;
            display: grid;
            place-items: center;
            width: 100vw;
            height: 100vh;
            height: 100dvh;
            padding: 18px;
        }
        .channel-action-dialog-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, .48);
            backdrop-filter: blur(3px);
        }
        .channel-action-dialog-card {
            position: relative;
            z-index: 1;
            overflow: hidden;
            width: min(460px, calc(100vw - 36px));
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 28px 80px rgba(17, 24, 39, .28);
        }
        .channel-action-dialog-card header {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 20px;
            border-bottom: 1px solid #edf1f7;
            background:
                radial-gradient(circle at 88% 8%, rgba(47, 99, 255, .16), transparent 34%),
                linear-gradient(180deg, #fff, #f8fbff);
        }
        .channel-action-dialog-card h3 {
            margin: 5px 0 0;
            color: #172033;
            font-size: 20px;
        }
        .channel-action-dialog-card header button {
            display: grid;
            place-items: center;
            flex: 0 0 34px;
            width: 34px;
            height: 34px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            color: #68778d;
            background: #eef2f7;
            box-shadow: none;
            font-size: 22px;
        }
        .channel-action-dialog-body {
            display: grid;
            gap: 12px;
            padding: 20px;
        }
        .channel-action-dialog-body strong {
            color: #1f2b43;
            overflow-wrap: anywhere;
        }
        .channel-action-dialog-body p {
            margin: 0;
            color: #526176;
            font-size: 14px;
            font-weight: 850;
            line-height: 1.7;
        }
        .channel-action-safe-note {
            padding: 12px 14px;
            border: 1px solid #dce7ff;
            border-radius: 12px;
            color: #2459d6;
            background: #eef4ff;
            font-size: 13px;
            font-weight: 900;
            line-height: 1.55;
        }
        .channel-action-dialog-card footer {
            display: flex;
            justify-content: flex-end;
            padding: 0 20px 20px;
        }
        .payment-test-dialog {
            position: fixed;
            inset: 0;
            z-index: 9130;
            display: grid;
            place-items: center;
            width: 100vw;
            height: 100vh;
            height: 100dvh;
            padding: 18px;
        }
        .payment-test-dialog-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, .52);
            backdrop-filter: blur(3px);
        }
        .payment-test-dialog-card {
            position: relative;
            z-index: 1;
            overflow: hidden;
            width: min(520px, calc(100vw - 36px));
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 28px 80px rgba(17, 24, 39, .3);
        }
        .payment-test-dialog-card * {
            min-width: 0;
        }
        .payment-test-dialog-card header {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 18px 20px;
            border-bottom: 1px solid #edf1f7;
            background:
                radial-gradient(circle at 88% 8%, rgba(47, 99, 255, .18), transparent 34%),
                linear-gradient(180deg, #fff, #f8fbff);
        }
        .payment-test-dialog-card h3 {
            margin: 5px 0 0;
            color: #172033;
            font-size: 20px;
        }
        .payment-test-dialog-card header button {
            display: grid;
            place-items: center;
            flex: 0 0 34px;
            width: 34px;
            height: 34px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            color: #68778d;
            background: #eef2f7;
            box-shadow: none;
            font-size: 22px;
        }
        .payment-test-form {
            display: grid;
            gap: 14px;
            padding: 20px;
            background: #f7f9fd;
        }
        .payment-test-route-card {
            display: grid;
            gap: 6px;
            padding: 14px;
            border: 1px solid #dbe7ff;
            border-radius: 14px;
            background: linear-gradient(135deg, #eef4ff, #fff);
        }
        .payment-test-route-card strong {
            color: #172033;
            overflow-wrap: anywhere;
        }
        .payment-test-route-card span {
            color: #526176;
            font-size: 13px;
            font-weight: 850;
            overflow-wrap: anywhere;
        }
        .payment-test-form label {
            display: grid;
            gap: 7px;
            color: #5a687d;
            font-size: 13px;
            font-weight: 900;
        }
        .payment-test-form input {
            min-height: 42px;
            border-radius: 10px;
            background: #fff;
            border-color: #dfe8f6;
        }
        .payment-test-warning {
            margin: 0;
            padding: 12px 14px;
            border: 1px solid #fed7aa;
            border-radius: 14px;
            color: #9a3412;
            background: #fff7ed;
            font-size: 13px;
            font-weight: 900;
            line-height: 1.6;
        }
        .payment-test-form footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }
        .order-table {
            overflow: hidden;
            border: 1px solid #e6edf8;
            border-radius: 22px;
            background: #fff;
        }
        .order-filter-bar {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) minmax(180px, 1fr) minmax(150px, .72fr) minmax(130px, .58fr) auto;
            gap: 12px;
            align-items: end;
            margin-bottom: 14px;
            padding: 14px;
            border: 1px solid #e6edf8;
            border-radius: 20px;
            background: linear-gradient(135deg, #fbfdff, #f5f9ff);
        }
        .order-filter-bar label {
            display: grid;
            gap: 6px;
            color: #5f6e81;
            font-size: 13px;
            font-weight: 800;
        }
        .order-filter-actions,
        .order-toolbar,
        .order-pagination {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        .order-toolbar {
            justify-content: space-between;
            margin-bottom: 14px;
            padding: 12px 14px;
            border: 1px solid #e7eef8;
            border-radius: 18px;
            background: #fff;
        }
        .order-toolbar > div {
            display: grid;
            gap: 4px;
        }
        .order-pagination {
            justify-content: flex-end;
            margin-top: 14px;
        }
        .order-pagination span {
            color: #6c7b90;
            font-size: 13px;
            font-weight: 800;
        }
        .order-sub-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin: 0 0 14px;
        }
        .order-sub-stats div {
            min-width: 0;
            padding: 16px 18px;
            border: 1px solid #e6edf8;
            border-radius: 20px;
            background:
                radial-gradient(circle at 92% 10%, rgba(47, 99, 255, .12), transparent 36%),
                #fff;
            box-shadow: 0 14px 32px rgba(68, 91, 130, .07);
        }
        .order-sub-stats span,
        .order-sub-stats em {
            display: block;
            color: #8896aa;
            font-size: 12px;
            font-style: normal;
            font-weight: 900;
        }
        .order-sub-stats strong {
            display: block;
            margin: 6px 0 2px;
            color: #172033;
            font-size: 28px;
            letter-spacing: -.04em;
        }
        .order-mini-table {
            margin-top: 12px;
        }
        .order-mini-table .order-row {
            grid-template-columns: minmax(240px, 1.3fr) minmax(98px, .45fr) minmax(115px, .45fr) minmax(130px, .4fr);
        }
        .works-table .order-row,
        .works-table .order-row-head {
            grid-template-columns: 44px minmax(250px, 1.45fr) minmax(170px, .86fr) minmax(170px, .82fr) minmax(126px, .58fr) minmax(170px, .72fr);
        }
        .works-table .order-row-head {
            display: grid;
            gap: 14px;
            align-items: center;
        }
        .works-row {
            align-items: start;
            gap: 14px;
            padding: 14px 16px;
        }
        .works-bulk-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 0 0 12px;
            padding: 10px 12px;
            border: 1px solid #e0e8f4;
            border-radius: 10px;
            background: #f8fafc;
        }
        .works-bulk-toolbar strong {
            display: block;
            color: #172033;
            font-size: 14px;
            line-height: 1.35;
        }
        .works-bulk-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }
        .admin-panel .btn.danger,
        .admin-panel button.danger {
            color: #b4233a;
            border-color: #ffd4dd;
            background: #fff1f4;
        }
        .admin-panel .btn.danger.solid,
        .admin-panel button.danger.solid {
            color: #fff;
            border-color: #d92d4b;
            background: #d92d4b;
        }
        .admin-panel .btn:disabled,
        .admin-panel button:disabled {
            cursor: not-allowed;
            opacity: .52;
        }
        .works-select-cell,
        .works-select-all {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
        }
        .works-select-all {
            justify-content: flex-start;
            gap: 7px;
            color: #68778d;
            font-size: 12px;
            font-weight: 900;
        }
        .works-select-cell label {
            display: grid;
            place-items: center;
            width: 30px;
            height: 30px;
            border: 1px solid #dfe8f5;
            border-radius: 8px;
            background: #fff;
        }
        .works-select-cell input,
        .works-select-all input {
            width: 16px;
            height: 16px;
            accent-color: #4d74ff;
        }
        .works-title-cell {
            display: grid;
            grid-template-columns: 54px minmax(0, 1fr);
            gap: 12px;
            align-items: center;
            min-width: 0;
        }
        .works-cover {
            display: grid;
            place-items: center;
            width: 54px;
            height: 72px;
            overflow: hidden;
            border: 1px solid #dfe8f5;
            border-radius: 8px;
            color: #496bba;
            background: linear-gradient(135deg, #eef4ff, #f7fbff);
        }
        .works-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .works-cover .ui-icon,
        .works-cover .ui-icon svg {
            width: 24px;
            height: 24px;
        }
        .works-title-copy,
        .works-meta-cell,
        .works-data-cell {
            display: grid;
            gap: 5px;
            min-width: 0;
        }
        .works-title-copy strong,
        .works-meta-cell strong,
        .works-data-cell strong {
            min-width: 0;
            overflow-wrap: anywhere;
            color: #1f2a3d;
            font-size: 14px;
            line-height: 1.35;
        }
        .works-title-copy strong {
            font-size: 15px;
        }
        .works-title-copy em,
        .works-meta-cell em,
        .works-data-cell em {
            min-width: 0;
            overflow-wrap: anywhere;
            color: #77869a;
            font-size: 12px;
            font-style: normal;
            font-weight: 800;
            line-height: 1.45;
        }
        .works-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            min-width: 0;
        }
        .works-tags b,
        .works-tags i {
            display: inline-flex;
            max-width: 100%;
            min-height: 24px;
            align-items: center;
            padding: 4px 8px;
            border: 1px solid #e0e8f6;
            border-radius: 999px;
            color: #536176;
            background: #f6f9ff;
            font-size: 12px;
            font-style: normal;
            font-weight: 900;
            overflow-wrap: anywhere;
        }
        .works-tags i {
            color: #9aa6b7;
            background: transparent;
        }
        .works-status-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-content: flex-start;
        }
        .works-badge {
            display: inline-flex;
            min-height: 26px;
            align-items: center;
            padding: 5px 9px;
            border: 1px solid #e0e8f6;
            border-radius: 999px;
            color: #5f6e81;
            background: #f7f9fd;
            font-size: 12px;
            font-weight: 900;
            line-height: 1;
        }
        .works-badge.is-online,
        .works-badge.is-approved {
            color: #16813d;
            border-color: #cdeed8;
            background: #edfbf1;
        }
        .works-badge.is-draft,
        .works-badge.is-pending {
            color: #9a5a0b;
            border-color: #ffe2b9;
            background: #fff7e8;
        }
        .works-badge.is-offline,
        .works-badge.is-rejected {
            color: #b4233a;
            border-color: #ffd4dd;
            background: #fff1f4;
        }
        .works-badge.is-paid {
            color: #2357c6;
            border-color: #d8e4ff;
            background: #eef4ff;
        }
        .works-badge.is-free,
        .works-badge.is-done {
            color: #16813d;
            border-color: #cdeed8;
            background: #edfbf1;
        }
        .works-badge.is-muted {
            color: #6c7b90;
            background: #f3f6fb;
        }
        .works-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }
        .works-actions .btn {
            min-height: 34px;
            padding: 7px 11px;
            font-size: 12px;
        }
        .work-quick-edit {
            grid-column: 1 / -1;
            width: 100%;
            min-width: 0;
            margin-top: 2px;
            padding-top: 10px;
            border-top: 1px solid #edf2f8;
        }
        .work-quick-edit summary {
            display: inline-flex;
            width: fit-content;
            min-height: 32px;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border: 1px solid #dce7ff;
            border-radius: 999px;
            color: #3b67db;
            background: #eef4ff;
            cursor: pointer;
            font-size: 12px;
            font-weight: 900;
            list-style: none;
        }
        .work-quick-edit summary::-webkit-details-marker {
            display: none;
        }
        .work-quick-edit summary::before {
            content: "+";
            display: inline-grid;
            width: 16px;
            height: 16px;
            place-items: center;
            border-radius: 50%;
            color: #fff;
            background: #4d74ff;
            font-size: 12px;
            line-height: 1;
        }
        .work-quick-edit[open] summary::before {
            content: "-";
        }
        .work-quick-edit-form {
            display: grid;
            gap: 12px;
            margin-top: 10px;
            padding: 12px;
            border: 1px solid #e6edf8;
            border-radius: 12px;
            background: #f9fbff;
        }
        .work-quick-edit-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(145px, 1fr));
            gap: 10px;
            align-items: end;
        }
        .work-quick-edit-grid label {
            min-width: 0;
            font-size: 12px;
            font-weight: 900;
        }
        .work-quick-edit-grid input,
        .work-quick-edit-grid select {
            min-height: 36px;
            padding: 8px 10px;
            font-size: 13px;
        }
        .works-check-row span {
            display: flex;
            min-height: 36px;
            align-items: center;
            gap: 7px;
            padding: 8px 10px;
            border: 1px solid #dfe8f5;
            border-radius: 10px;
            background: #fff;
            font-size: 13px;
            font-weight: 900;
        }
        .work-quick-edit-actions {
            display: flex;
            justify-content: flex-end;
        }
        .work-quick-edit-actions .btn {
            min-height: 36px;
            padding: 8px 14px;
            font-size: 13px;
        }
        .order-section-log-card {
            margin-top: 16px;
        }
        .repair-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(320px, .85fr);
            gap: 16px;
            align-items: start;
        }
        .action-log-list {
            display: grid;
            gap: 10px;
        }
        .action-log-card {
            display: grid;
            gap: 8px;
            min-width: 0;
            padding: 13px 14px;
            border: 1px solid #e6edf8;
            border-radius: 16px;
            background: #fff;
        }
        .action-log-head {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
        }
        .action-log-card strong {
            min-width: 0;
            overflow-wrap: anywhere;
            color: #1f2a3d;
            font-size: 14px;
        }
        .action-log-card p {
            margin: 0;
            color: #536176;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.55;
        }
        .action-log-card small {
            color: #7c8a9d;
            font-size: 12px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }
        .action-log-card em {
            color: #9aa6b7;
            font-size: 12px;
            font-style: normal;
            font-weight: 800;
        }
        .btn[disabled],
        .btn.is-disabled {
            pointer-events: none;
            opacity: .48;
            box-shadow: none;
        }
        .order-row {
            grid-template-columns: minmax(260px, 1.35fr) minmax(110px, .5fr) minmax(115px, .48fr) minmax(360px, 1.18fr);
        }
        .order-row-head {
            padding: 14px 18px;
            border-bottom: 1px solid #edf2f8;
            background: #f8fbff;
        }
        .order-table .row-card {
            margin: 0;
            border: 0;
            border-radius: 0;
            border-bottom: 1px solid #edf2f8;
            background: #fff;
        }
        .order-table .row-card:last-child {
            border-bottom: 0;
        }
        .order-table .row-card:hover {
            background: #fbfdff;
        }
        body.has-order-modal,
        body.has-refund-dialog {
            overflow: hidden;
        }
        .order-modal[hidden] {
            display: none;
        }
        .order-modal {
            position: fixed;
            inset: 0;
            z-index: 9000;
            display: grid;
            place-items: center;
            width: 100vw;
            height: 100vh;
            height: 100dvh;
            overflow: auto;
            overscroll-behavior: contain;
            padding: 22px;
        }
        .order-modal-backdrop {
            position: absolute;
            inset: 0;
            z-index: 0;
            background: rgba(14, 18, 32, .58);
            backdrop-filter: blur(3px);
        }
        .order-modal-card {
            position: relative;
            z-index: 1;
            overflow: hidden;
            display: grid;
            grid-template-rows: auto auto auto minmax(0, 1fr) auto;
            width: min(1120px, calc(100vw - 44px));
            max-height: min(88vh, 920px);
            max-height: min(88dvh, 920px);
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 34px 90px rgba(21, 30, 55, .28);
        }
        .order-modal-card *,
        .refund-dialog-card * {
            min-width: 0;
        }
        .order-modal-titlebar {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            min-height: 74px;
            padding: 0 24px;
            background: #fff;
            border-bottom: 1px solid #eef2f7;
        }
        .order-modal-titlebar h3 {
            margin: 0;
            color: #172033;
            font-size: 19px;
            text-align: center;
        }
        .order-modal-close {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: #4c586b;
            font-size: 28px;
            line-height: 1;
            cursor: pointer;
        }
        .order-modal-close:hover {
            background: #f3f6fb;
        }
        .order-modal-hero {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 22px;
            align-items: center;
            padding: 30px 34px;
            color: #fff;
            background:
                radial-gradient(circle at 18% 10%, rgba(86, 126, 255, .22), transparent 30%),
                linear-gradient(135deg, #111229 0%, #191937 48%, #25235a 100%);
        }
        .order-modal-hero strong {
            font-size: clamp(34px, 4vw, 46px);
            letter-spacing: -.04em;
        }
        .order-modal-hero > div {
            display: flex;
            gap: 12px;
            align-items: center;
            min-width: 0;
        }
        .order-modal-hero > div:not(:first-child) {
            display: grid;
            gap: 7px;
        }
        .order-modal-hero span:not(.pill) {
            color: rgba(255,255,255,.62);
            font-size: 13px;
            font-weight: 800;
        }
        .order-modal-hero b {
            max-width: min(300px, 28vw);
            overflow: hidden;
            padding: 10px 14px;
            border-radius: 8px;
            background: rgba(255,255,255,.10);
            color: #fff;
            font-size: 16px;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .order-modal-tabs {
            display: flex;
            gap: 26px;
            padding: 24px 32px 0;
            border-bottom: 1px solid #eef2f7;
            background: #fff;
        }
        .order-modal-tabs button {
            position: relative;
            min-height: 52px;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: #607086;
            box-shadow: none;
            font-size: 15px;
            font-weight: 900;
        }
        .order-modal-tabs button.is-active {
            color: #2f63ff;
        }
        .order-modal-tabs button.is-active::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -1px;
            height: 3px;
            border-radius: 999px;
            background: #2f63ff;
        }
        .order-modal-body {
            min-height: 0;
            overflow: auto;
            padding: 26px 32px;
            background: #fff;
        }
        .order-tab-panel {
            display: none;
        }
        .order-tab-panel.is-active {
            display: block;
        }
        .order-info-card {
            padding: 22px;
            border-radius: 18px;
            background: #f7f9fc;
        }
        .order-info-card + .order-info-card {
            margin-top: 16px;
        }
        .order-info-card h4 {
            margin: 0 0 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid #e2e8f1;
            color: #172033;
            font-size: 16px;
        }
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 38px;
        }
        .order-info-grid div {
            min-width: 0;
        }
        .order-info-grid span,
        .gateway-log-payload span {
            display: block;
            margin-bottom: 5px;
            color: #8b98aa;
            font-size: 12px;
            font-weight: 800;
        }
        .order-info-grid strong {
            display: block;
            color: #273244;
            font-size: 15px;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .refund-history-list {
            display: grid;
            gap: 10px;
            margin-top: 18px;
        }
        .refund-history-list div {
            display: grid;
            grid-template-columns: minmax(150px, 1fr) auto;
            gap: 10px;
            align-items: center;
            padding: 12px 14px;
            border-radius: 14px;
            background: #fff;
            color: #5f6e81;
        }
        .refund-history-list strong {
            overflow-wrap: anywhere;
        }
        .refund-history-list span:not(.pill) {
            grid-column: 1 / -1;
            overflow-wrap: anywhere;
        }
        .order-modal-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: flex-end;
            padding: 18px 32px 24px;
            border-top: 1px solid #eef2f7;
            background: #fff;
        }
        .gateway-log-list {
            display: grid;
            gap: 12px;
        }
        .gateway-log-card {
            padding: 14px;
            border: 1px solid #e4ecf8;
            border-radius: 16px;
            background: rgba(255, 255, 255, .88);
        }
        .gateway-log-head {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .gateway-log-head strong {
            display: block;
            max-width: 760px;
            overflow-wrap: anywhere;
            word-break: break-word;
            color: #243047;
            font-size: 14px;
        }
        .gateway-log-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: flex-end;
        }
        .gateway-log-meta span {
            padding: 5px 8px;
            border-radius: 999px;
            background: #eef4ff;
            color: #4d6282;
            font-size: 12px;
            font-weight: 800;
        }
        .gateway-log-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .gateway-log-payload pre {
            min-height: 156px;
            max-height: 320px;
            margin: 0;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .order-actions .btn {
            min-width: 86px;
        }
        .order-message {
            margin: 0 0 16px;
            border-color: rgba(47, 99, 255, .18);
            background: linear-gradient(135deg, rgba(47, 99, 255, .10), rgba(18, 184, 134, .10));
            color: #25405f;
            font-weight: 900;
        }
        .refund-note {
            color: #9aa6b7;
            font-size: 12px;
            font-weight: 800;
        }
        .refund-dialog[hidden] {
            display: none;
        }
        .refund-dialog {
            position: fixed;
            inset: 0;
            z-index: 9100;
            display: grid;
            place-items: center;
            width: 100vw;
            height: 100vh;
            height: 100dvh;
            overflow: auto;
            overscroll-behavior: contain;
            padding: 18px;
        }
        .refund-dialog-backdrop {
            position: absolute;
            inset: 0;
            z-index: 0;
            background: rgba(12, 17, 30, .42);
            backdrop-filter: blur(2px);
        }
        .refund-dialog-card {
            position: relative;
            z-index: 1;
            width: min(480px, calc(100vw - 36px));
            max-height: calc(100vh - 36px);
            max-height: calc(100dvh - 36px);
            overflow: auto;
            padding: 26px;
            border: 1px solid rgba(223, 231, 244, .92);
            border-radius: 26px;
            background:
                radial-gradient(circle at 100% 0%, rgba(255, 92, 92, .10), transparent 36%),
                #fff;
            box-shadow: 0 28px 72px rgba(17, 25, 43, .26);
        }
        .refund-dialog-card h3 {
            margin: 6px 0 8px;
            color: #182236;
            font-size: 24px;
            letter-spacing: -.03em;
        }
        .refund-dialog-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 36px;
            height: 36px;
            border: 0;
            border-radius: 999px;
            background: #f3f6fb;
            color: #536176;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
        }
        .refund-dialog-close:hover {
            background: #eaf0f8;
        }
        .refund-dialog-summary {
            display: grid;
            grid-template-columns: 1.35fr .8fr;
            gap: 12px;
            margin: 18px 0;
        }
        .refund-dialog-summary div {
            min-width: 0;
            padding: 14px;
            border-radius: 18px;
            background: #f7f9fd;
        }
        .refund-dialog-summary span,
        .refund-dialog-form label {
            display: block;
            color: #7c8a9e;
            font-size: 12px;
            font-weight: 900;
        }
        .refund-dialog-summary strong {
            display: block;
            margin-top: 6px;
            color: #1d2940;
            overflow-wrap: anywhere;
        }
        .refund-dialog-form {
            display: grid;
            gap: 16px;
        }
        .refund-dialog-form input {
            width: 100%;
            min-height: 52px;
            margin-top: 8px;
            border-radius: 16px;
            font-size: 22px;
            font-weight: 950;
            text-align: center;
        }
        .refund-dialog-status {
            margin: 0;
            padding: 11px 13px;
            border: 1px solid rgba(47, 99, 255, .16);
            border-radius: 14px;
            background: #f3f7ff;
            color: #31507a;
            font-size: 13px;
            font-weight: 900;
        }
        .refund-dialog-status[hidden] {
            display: none;
        }
        .refund-dialog-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }
        .template-choice-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .template-system-block {
            display: grid;
            gap: 12px;
            padding: 16px;
            border: 1px solid #e6edf7;
            border-radius: 12px;
            background: #fbfdff;
        }
        .template-system-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-width: 0;
        }
        .template-system-head > div {
            display: grid;
            gap: 6px;
            min-width: 0;
        }
        .template-system-head h3 {
            margin: 0;
            color: #182235;
            font-size: 18px;
        }
        .template-choice {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: 142px minmax(0, 1fr);
            gap: 16px;
            align-items: center;
            min-height: 170px;
            padding: 14px;
            border: 1px solid #e4ecf7;
            border-radius: 10px;
            background: #fff;
            box-shadow: none;
            cursor: pointer;
            transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
        }
        .template-choice:hover,
        .template-choice.is-selected {
            transform: translateY(-1px);
            border-color: rgba(66, 108, 255, .36);
            box-shadow: 0 12px 24px rgba(65, 92, 143, .08);
        }
        .template-choice input {
            position: absolute;
            right: 14px;
            top: 14px;
            width: 20px;
            height: 20px;
            accent-color: #426cff;
        }
        .template-preview {
            position: relative;
            overflow: hidden;
            display: grid;
            gap: 8px;
            min-height: 148px;
            padding: 12px;
            border-radius: 10px;
            background: linear-gradient(180deg, #fff1ea, #fff8f5 52%, #f3f5f8);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.72), 0 14px 28px rgba(224, 118, 88, .12);
        }
        .template-preview::before {
            content: "";
            height: 16px;
            border-radius: 999px;
            background: #fff;
            box-shadow: inset 0 0 0 2px #f0919a;
        }
        .template-preview i {
            display: block;
            border-radius: 14px;
            background: #fff;
        }
        .template-preview i:nth-child(1) {
            min-height: 52px;
            background: linear-gradient(135deg, #f05d66, #ffc079);
        }
        .template-preview i:nth-child(2),
        .template-preview i:nth-child(3) {
            min-height: 24px;
            background: rgba(255,255,255,.92);
        }
        .template-preview.marketing {
            background: linear-gradient(135deg, #f7fbff, #edf4ff);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.82), 0 14px 28px rgba(66, 108, 255, .10);
        }
        .template-preview.marketing::before {
            height: 60px;
            border-radius: 18px;
            background: linear-gradient(135deg, #426cff, #62d2ff);
            box-shadow: none;
        }
        .template-preview.marketing i:nth-child(1) {
            min-height: 16px;
            width: 68%;
            background: #fff;
        }
        .template-preview.marketing i:nth-child(2),
        .template-preview.marketing i:nth-child(3) {
            min-height: 18px;
            background: #fff;
        }
        .template-preview.diy {
            background: linear-gradient(180deg, #fff6ed, #f7fbff);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.82), 0 14px 28px rgba(239, 91, 95, .11);
        }
        .template-preview.diy::before {
            background: linear-gradient(135deg, #ef5b5f, #ff955d);
            box-shadow: none;
        }
        .template-preview.diy i:nth-child(1) {
            background: linear-gradient(135deg, #315add, #6aa3ff);
        }
        .template-preview.novel-library {
            background: linear-gradient(180deg, #f7fbff, #fff 58%, #f3f6fb);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.78), 0 12px 24px rgba(42, 95, 160, .10);
        }
        .template-preview.novel-library::before {
            background: #fff;
            box-shadow: inset 0 0 0 2px #76a7ff;
        }
        .template-preview.novel-library i:nth-child(1) {
            background: linear-gradient(135deg, #5f8dff, #8bd8ff);
        }
        .template-preview.novel-ranking {
            background: linear-gradient(180deg, #fff8eb, #fff 58%, #f8f3ea);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.78), 0 12px 24px rgba(190, 127, 38, .10);
        }
        .template-preview.novel-ranking::before {
            height: 44px;
            border-radius: 10px;
            background: linear-gradient(135deg, #2f3a56, #edb85f);
            box-shadow: none;
        }
        .template-preview.novel-ranking i:nth-child(1),
        .template-preview.novel-ranking i:nth-child(2),
        .template-preview.novel-ranking i:nth-child(3) {
            min-height: 20px;
            background: #fff;
        }
        .template-copy {
            display: grid;
            gap: 8px;
            padding-right: 24px;
        }
        .template-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            align-items: center;
        }
        .template-meta-row code {
            width: fit-content;
            padding: 5px 9px;
            border: 1px solid #d8e4fb;
            border-radius: 999px;
            color: #24344f;
            background: #fff;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 12px;
            font-weight: 950;
            letter-spacing: 0;
        }
        .template-copy small {
            width: fit-content;
            padding: 5px 9px;
            border-radius: 999px;
            color: #3568ff;
            background: #edf4ff;
            font-weight: 900;
        }
        .template-copy strong {
            color: #182235;
            font-size: 20px;
            letter-spacing: 0;
        }
        .template-copy em {
            color: #738199;
            font-style: normal;
            line-height: 1.7;
        }
        .template-feature-row {
            grid-column: 1 / -1;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .template-feature-row b {
            padding: 7px 10px;
            border-radius: 999px;
            color: #5f6e82;
            background: #f3f7ff;
            font-size: 12px;
        }
        .template-action-row {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .design-studio {
            display: grid;
            grid-template-columns: 330px minmax(0, 1fr);
            gap: 22px;
            align-items: start;
        }
        .design-phone-panel {
            display: grid;
            gap: 18px;
        }
        .design-phone-frame {
            overflow: hidden;
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 22px 55px rgba(65, 92, 143, .16);
            border: 1px solid #e6edf8;
        }
        .design-phone-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            color: #fff;
            background: linear-gradient(135deg, #315add, #386cff);
            font-weight: 900;
        }
        .design-phone-head span {
            padding: 5px 9px;
            border-radius: 999px;
            background: #7bd64c;
            font-size: 12px;
        }
        .design-phone-screen {
            --design-primary: #ef5b5f;
            --design-accent: #ff955d;
            display: grid;
            gap: 10px;
            min-height: 590px;
            padding: 12px 12px 18px;
            background: linear-gradient(180deg, #fff4ec, #f7f8fb);
        }
        .design-phone-banner {
            min-height: 122px;
            padding: 18px;
            border-radius: 16px;
            color: #fff;
            background:
                radial-gradient(circle at 88% 22%, rgba(255,255,255,.32), transparent 26%),
                linear-gradient(135deg, var(--design-primary), var(--design-accent));
        }
        .design-phone-banner strong {
            display: block;
            margin-bottom: 8px;
            font-size: 22px;
            letter-spacing: -.04em;
        }
        .design-phone-search {
            display: flex;
            align-items: center;
            gap: 8px;
            height: 38px;
            padding: 0 12px;
            border: 2px solid color-mix(in srgb, var(--design-primary) 68%, #fff);
            border-radius: 999px;
            color: #a3a9b5;
            background: #fff;
            font-size: 13px;
        }
        .design-phone-quick {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            padding: 12px;
            border-radius: 16px;
            background: #fff;
        }
        .design-phone-quick span {
            display: grid;
            gap: 7px;
            justify-items: center;
            color: #6c7586;
            font-size: 12px;
            font-weight: 800;
        }
        .design-phone-quick i {
            width: 34px;
            height: 34px;
            border-radius: 13px;
            background: linear-gradient(135deg, rgba(49, 90, 221, .15), rgba(62, 125, 255, .28));
        }
        .design-phone-notice {
            padding: 11px 12px;
            border-radius: 14px;
            color: color-mix(in srgb, var(--design-primary) 70%, #553039);
            background: color-mix(in srgb, var(--design-primary) 13%, #fff);
            font-size: 13px;
            font-weight: 900;
        }
        .design-phone-section {
            padding: 14px;
            border-radius: 17px;
            background: #fff;
        }
        .design-phone-section strong {
            display: block;
            margin-bottom: 10px;
            color: #1d2636;
            font-size: 16px;
        }
        .design-phone-rank {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 9px;
        }
        .design-phone-rank i {
            display: block;
            height: 62px;
            border-radius: 12px;
            background: linear-gradient(135deg, #eef4ff, #ffe8e3);
        }
        .design-phone-bottom {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-top: auto;
            padding: 10px;
            border-radius: 18px;
            color: #8b94a4;
            background: rgba(255,255,255,.86);
            font-size: 12px;
            text-align: center;
        }
        .design-phone-bottom b {
            color: var(--design-primary);
        }
        .design-workspace {
            display: grid;
            gap: 18px;
        }
        .design-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .design-toolbar h2 {
            margin: 0;
            color: #1f2a3a;
            font-size: 28px;
        }
        .design-toolbar-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .design-toolbar-actions input {
            width: 210px;
            min-height: 42px;
            background: #fff;
        }
        .design-page-table {
            overflow: hidden;
            border: 1px solid #e4ebf6;
            border-radius: 22px;
            background: #fff;
        }
        .design-page-row {
            display: grid;
            grid-template-columns: minmax(160px, 1.15fr) 116px 140px 116px 116px minmax(230px, .9fr);
            gap: 12px;
            align-items: center;
            padding: 14px 18px;
            border-bottom: 1px solid #edf2f8;
        }
        .design-page-row:last-child {
            border-bottom: 0;
        }
        .design-page-row.head {
            color: #687586;
            background: #f8fbff;
            font-weight: 900;
        }
        .design-page-name strong {
            display: block;
            color: #1f2a3a;
        }
        .design-page-name small {
            display: block;
            margin-top: 3px;
            color: #9aa6b7;
        }
        .design-switch {
            position: relative;
            display: inline-block;
            width: 54px;
            height: 28px;
            border-radius: 999px;
            background: #e9edf4;
        }
        .design-switch::after {
            content: "";
            position: absolute;
            left: 4px;
            top: 4px;
            width: 20px;
            height: 20px;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(89, 99, 120, .18);
            transition: transform .2s ease;
        }
        .design-switch.is-on {
            background: linear-gradient(135deg, #6a4ee8, #386cff);
        }
        .design-switch.is-on::after {
            transform: translateX(26px);
        }
        .design-badge {
            display: inline-flex;
            width: fit-content;
            padding: 7px 10px;
            border-radius: 8px;
            color: #e65f37;
            background: #fff0e9;
            font-size: 13px;
            font-weight: 900;
        }
        .design-badge.blue {
            color: #5968e8;
            background: #eeedff;
        }
        .design-badge.green {
            color: #2d9d45;
            background: #eaf8ee;
        }
        .design-page-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            color: #657386;
            font-weight: 900;
        }
        .design-page-actions a {
            color: inherit;
        }
        .design-page-actions a.primary-link {
            color: #3568ff;
        }
        .diy-editor-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .design-color-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .design-color-grid input[type="color"] {
            min-height: 48px;
            padding: 5px;
        }
        .module-check-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }
        .module-check-grid label {
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid #e4ebf6;
            border-radius: 14px;
            color: #536174;
            background: #fff;
            font-weight: 900;
        }
        .design-save-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        .design-muted-card {
            padding: 18px;
            border: 1px dashed #dce6f5;
            border-radius: 20px;
            color: #7b8798;
            background: #fbfdff;
        }
        .client-topbar {
            background: rgba(255, 255, 255, .76);
        }
        .client-footer {
            position: relative;
            z-index: 1;
            margin: 34px auto 6px;
            padding: 14px;
            color: #8a96a8;
            font-size: 13px;
            text-align: center;
        }
        .view-frontend-home.template-mini .client-footer,
        .view-frontend-home.template-diy .client-footer {
            margin: 14px auto 0;
            padding-bottom: 92px;
            color: #a4a4a4;
        }
        .is-client .hero {
            min-height: 420px;
            background:
                radial-gradient(circle at 88% 24%, rgba(66, 108, 255, .18), transparent 24%),
                linear-gradient(135deg, rgba(255,255,255,.98), rgba(235,244,255,.92)),
                url('/assets/cover-1.svg') right center/contain no-repeat;
        }
        .is-client .hero h1 {
            max-width: 660px;
            color: #172033;
        }
        .is-client .drama-card {
            border-radius: 24px;
        }
        .is-client .drama-card:hover {
            border-color: #d6e4ff;
            background: #fff;
            box-shadow: 0 20px 48px rgba(66, 108, 255, .14);
        }
        .is-client .drama-card img,
        .is-client .drama-cover {
            background: linear-gradient(135deg, #eaf2ff, #f8fbff);
            box-shadow: inset 0 0 0 1px #e8eef8;
        }
        .is-client .section-title h2 {
            color: #172033;
        }
        .view-frontend-home.template-mini.is-client {
            min-height: 100vh;
            background:
                radial-gradient(circle at 50% -10%, rgba(255, 201, 177, .45), transparent 30%),
                linear-gradient(180deg, #fff4ec 0%, #fff8f5 38%, #f7f7f7 100%);
        }
        .view-frontend-home.template-mini.is-client::before {
            content: none;
        }
        .view-frontend-home.template-mini .wrap {
            max-width: 430px;
            min-height: 100vh;
            padding: 0 12px 104px;
        }
        .view-frontend-home.template-mini .client-topbar {
            display: none;
        }
        .view-frontend-home.template-diy.is-client {
            min-height: 100vh;
            background:
                radial-gradient(circle at 50% -10%, rgba(255, 201, 177, .45), transparent 30%),
                linear-gradient(180deg, #fff4ec 0%, #fff8f5 38%, #f7f7f7 100%);
        }
        .view-frontend-home.template-diy.is-client::before {
            content: none;
        }
        .view-frontend-home.template-diy .wrap {
            max-width: 430px;
            min-height: 100vh;
            padding: 0 12px 104px;
        }
        .view-frontend-home.template-diy .client-topbar {
            display: none;
        }
        .diy-home .mini-search {
            border-color: var(--diy-primary, #ef5b5f);
        }
        .diy-home .mini-search button,
        .diy-home .mini-add-card a {
            background: linear-gradient(135deg, var(--diy-primary, #ef5b5f), var(--diy-accent, #ff955d));
        }
        .diy-home .mini-flame,
        .diy-home .mini-flame::after,
        .diy-home .mini-crown,
        .diy-home .mini-rank-cover b.top {
            background: linear-gradient(135deg, var(--diy-primary, #ef5b5f), var(--diy-accent, #ff955d));
        }
        .diy-home .mini-dots .active,
        .diy-bottom-nav a.is-active {
            color: var(--diy-primary, #ef5b5f);
            background: var(--diy-primary, #ef5b5f);
        }
        .diy-bottom-nav a.is-active {
            background: transparent;
        }
        .diy-quick-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            padding: 12px;
            border-radius: 18px;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 10px 26px rgba(31, 35, 41, .06);
        }
        .diy-quick-grid a {
            display: grid;
            gap: 7px;
            justify-items: center;
            min-width: 0;
            color: #575f6b;
            font-size: 12px;
            font-weight: 900;
        }
        .diy-quick-grid span {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 14px;
            color: #fff;
            background: linear-gradient(135deg, var(--diy-primary, #ef5b5f), var(--diy-accent, #ff955d));
            box-shadow: 0 10px 20px color-mix(in srgb, var(--diy-primary, #ef5b5f) 24%, transparent);
        }
        .diy-quick-grid .ui-icon,
        .diy-quick-grid .ui-icon svg {
            width: 20px;
            height: 20px;
        }
        .diy-quick-grid strong {
            overflow: hidden;
            max-width: 100%;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .diy-notice {
            color: color-mix(in srgb, var(--diy-primary, #ef5b5f) 70%, #552b32);
            background: color-mix(in srgb, var(--diy-primary, #ef5b5f) 13%, #fff);
        }
        .diy-float-reward {
            background: linear-gradient(135deg, var(--diy-accent, #ff955d), var(--diy-primary, #ef5b5f));
        }
        .diy-float-reward::after {
            background: var(--diy-primary, #ef5b5f);
        }
        .mini-home {
            display: grid;
            gap: 12px;
            color: #202124;
        }
        .mini-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 22px 0 10px;
        }
        .mini-top h1 {
            margin: 0;
            color: #111;
            font-size: 28px;
            line-height: 1;
            letter-spacing: -.05em;
        }
        .mini-capsule {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 112px;
            height: 42px;
            padding: 0 13px;
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 999px;
            background: rgba(255, 255, 255, .72);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .7);
        }
        .mini-capsule span {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: #171717;
        }
        .mini-capsule i {
            width: 28px;
            height: 28px;
            margin-left: auto;
            border: 5px solid #171717;
            border-radius: 999px;
            box-shadow: inset 0 0 0 5px #fff;
        }
        .mini-search {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            align-items: center;
            gap: 10px;
            height: 44px;
            padding: 4px 5px 4px 18px;
            border: 1.5px solid #ef7c8a;
            border-radius: 999px;
            background: rgba(255, 255, 255, .78);
            box-shadow: 0 8px 20px rgba(238, 92, 108, .08);
        }
        .mini-search input {
            border: 0;
            padding: 0;
            min-width: 0;
            color: #323232;
            background: transparent;
            box-shadow: none;
            font-size: 16px;
        }
        .mini-search input::placeholder {
            color: #c7c0c0;
        }
        .mini-search button {
            min-height: 36px;
            padding: 0 22px;
            border-radius: 999px;
            background: linear-gradient(135deg, #ef6770, #ef5964);
            box-shadow: none;
            font-size: 16px;
        }
        .mini-flame {
            position: relative;
            width: 18px;
            height: 23px;
            border-radius: 14px 14px 14px 4px;
            background: linear-gradient(180deg, #ff6f89, #ed315e);
            transform: rotate(14deg);
        }
        .mini-flame::after {
            content: "";
            position: absolute;
            right: -3px;
            bottom: 2px;
            width: 11px;
            height: 15px;
            border-radius: 999px 999px 999px 2px;
            background: #ff8a9b;
            transform: rotate(-35deg);
        }
        .mini-hero-banner {
            position: relative;
            overflow: hidden;
            min-height: 205px;
            border-radius: 18px;
            background: #edd7c5;
            box-shadow: 0 12px 28px rgba(90, 45, 35, .12);
        }
        .mini-hero-banner img {
            width: 100%;
            height: 205px;
            object-fit: cover;
        }
        .mini-hero-shade {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(0, 0, 0, .50), transparent 58%),
                linear-gradient(180deg, transparent 55%, rgba(0, 0, 0, .34));
        }
        .mini-hero-copy {
            position: absolute;
            left: 20px;
            bottom: 24px;
            display: grid;
            gap: 4px;
            max-width: 62%;
            color: #fff;
        }
        .mini-hero-copy small {
            width: fit-content;
            padding: 4px 8px;
            border-radius: 999px;
            color: #fff2e8;
            background: rgba(255, 255, 255, .16);
            font-weight: 900;
        }
        .mini-hero-copy strong {
            font-size: 27px;
            line-height: 1.05;
            letter-spacing: -.05em;
        }
        .mini-hero-copy em {
            color: rgba(255, 255, 255, .78);
            font-style: normal;
            font-size: 13px;
        }
        .mini-dots {
            position: absolute;
            right: 24px;
            bottom: 22px;
            display: flex;
            gap: 9px;
        }
        .mini-dots i {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: #fff;
            opacity: .88;
        }
        .mini-dots .active {
            background: #ff955d;
        }
        .mini-add-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 18px;
            color: #9c4a58;
            background: #ffe8ea;
        }
        .mini-add-card strong {
            min-width: 0;
            font-size: 16px;
            letter-spacing: -.03em;
        }
        .mini-add-card a {
            padding: 9px 18px;
            border-radius: 999px;
            color: #fff;
            background: #ef5b5f;
            font-weight: 900;
            white-space: nowrap;
        }
        .mini-add-card button {
            min-height: 0;
            width: 26px;
            height: 26px;
            padding: 0;
            color: #a44755;
            background: transparent;
            border: 0;
            box-shadow: none;
            font-size: 24px;
            line-height: 1;
        }
        .mini-rank-card,
        .mini-section {
            padding: 18px 14px;
            border-radius: 20px;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 10px 26px rgba(31, 35, 41, .06);
        }
        .mini-rank-tabs,
        .mini-section header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .mini-rank-tabs strong,
        .mini-section h2 {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin: 0;
            color: #111;
            font-size: 20px;
            font-weight: 950;
            letter-spacing: -.04em;
        }
        .mini-rank-tabs span,
        .mini-rank-tabs a,
        .mini-section header a {
            color: #9a9a9a;
            font-size: 15px;
            font-weight: 800;
            white-space: nowrap;
        }
        .mini-rank-tabs a::after,
        .mini-section header a::after {
            content: "›";
            margin-left: 3px;
        }
        .mini-crown,
        .mini-tv {
            position: relative;
            width: 22px;
            height: 18px;
            border-radius: 6px 6px 4px 4px;
            background: linear-gradient(135deg, #ff7a7f, #ffd15c);
        }
        .mini-crown::before {
            content: "";
            position: absolute;
            inset: -7px 2px auto;
            height: 12px;
            clip-path: polygon(0 100%, 18% 10%, 45% 75%, 70% 0, 100% 100%);
            background: #ff6a73;
        }
        .mini-tv {
            width: 25px;
            height: 20px;
            border: 2px solid #f08b9a;
            background: transparent;
        }
        .mini-tv::before {
            content: "";
            position: absolute;
            left: 6px;
            top: -8px;
            width: 12px;
            height: 8px;
            border-top: 2px solid #f08b9a;
            transform: rotate(-18deg);
        }
        .mini-rank-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 12px;
        }
        .mini-rank-item {
            display: grid;
            grid-template-columns: 70px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
            min-width: 0;
        }
        .mini-rank-cover {
            position: relative;
            overflow: hidden;
            width: 70px;
            height: 96px;
            border-radius: 10px;
            background: #f1f1f1;
        }
        .mini-rank-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .mini-rank-cover b {
            position: absolute;
            left: 0;
            top: 0;
            min-width: 30px;
            padding: 4px 7px 5px;
            border-radius: 0 0 9px 0;
            color: #7a7280;
            background: #f5e6ee;
            font-size: 16px;
            line-height: 1;
            text-align: center;
        }
        .mini-rank-cover b.top {
            color: #fff;
            background: linear-gradient(180deg, #ff836a, #ff5c66);
            font-size: 12px;
            font-weight: 950;
        }
        .mini-rank-item strong {
            display: -webkit-box;
            overflow: hidden;
            min-height: 44px;
            color: #333;
            font-size: 17px;
            line-height: 1.3;
            letter-spacing: -.04em;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .mini-rank-item em {
            display: block;
            margin-top: 8px;
            overflow: hidden;
            color: #aaa;
            font-style: normal;
            font-size: 14px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .mini-section {
            margin-top: 4px;
        }
        .mini-drama-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .mini-drama-card {
            display: grid;
            gap: 8px;
            min-width: 0;
        }
        .mini-drama-card img {
            width: 100%;
            aspect-ratio: 3 / 4;
            object-fit: cover;
            border-radius: 14px;
            background: #f1f1f1;
        }
        .mini-drama-card strong {
            overflow: hidden;
            color: #222;
            font-size: 16px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .mini-drama-card span {
            overflow: hidden;
            color: #9d9d9d;
            font-size: 13px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .mini-float-reward {
            position: fixed;
            z-index: 20;
            right: max(12px, calc((100vw - 430px) / 2 + 12px));
            bottom: 118px;
            display: grid;
            gap: 3px;
            min-width: 104px;
            padding: 10px 13px;
            border-radius: 8px 0 0 8px;
            color: #fff;
            background: linear-gradient(135deg, #ffb052, #f39832);
            box-shadow: 0 10px 26px rgba(230, 121, 32, .22);
        }
        .mini-float-reward::after {
            content: "";
            position: absolute;
            right: -10px;
            top: 0;
            width: 10px;
            height: 100%;
            background: #e9862b;
            clip-path: polygon(0 0, 100% 50%, 0 100%);
        }
        .mini-float-reward strong {
            font-size: 16px;
        }
        .mini-float-reward span {
            font-size: 11px;
            opacity: .86;
        }
        .mini-bottom-nav {
            position: fixed;
            left: 50%;
            bottom: 0;
            z-index: 30;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            width: min(100%, 430px);
            padding: 9px 10px calc(9px + env(safe-area-inset-bottom));
            border-top: 1px solid #f0f0f0;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 -8px 26px rgba(31, 35, 41, .08);
            transform: translateX(-50%);
        }
        .mini-bottom-nav a {
            position: relative;
            display: grid;
            justify-items: center;
            gap: 4px;
            color: #555;
            font-size: 13px;
            font-weight: 800;
        }
        .mini-bottom-nav .ui-icon,
        .mini-bottom-nav .ui-icon svg {
            width: 25px;
            height: 25px;
        }
        .mini-bottom-nav a.is-active {
            color: #ec5d61;
        }
        .mini-bottom-nav a i {
            position: absolute;
            top: 0;
            right: 30%;
            width: 10px;
            height: 10px;
            border: 2px solid #fff;
            border-radius: 999px;
            background: #ef5d63;
        }
        .is-client:not(.view-frontend-home) {
            background:
                radial-gradient(circle at 50% -10%, rgba(255, 205, 184, .42), transparent 30%),
                radial-gradient(circle at 100% 12%, rgba(255, 142, 132, .18), transparent 28%),
                linear-gradient(180deg, #fff5ee 0%, #fff9f5 42%, #f7f8fb 100%);
        }
        .is-client:not(.view-frontend-home)::before {
            content: none;
        }
        .is-client:not(.view-frontend-home) .wrap {
            max-width: 460px;
            min-height: 100vh;
            padding: 0 12px 72px;
        }
        .is-client:not(.view-frontend-home) .client-topbar {
            top: 0;
            z-index: 40;
            margin: 0 -12px 12px;
            padding: 11px 12px;
            border-width: 0 0 1px;
            border-radius: 0 0 24px 24px;
            background: rgba(255, 255, 255, .88);
        }
        .is-client:not(.view-frontend-home) .brand {
            color: #111;
            font-size: 20px;
        }
        .is-client:not(.view-frontend-home) .nav {
            flex-wrap: nowrap;
            overflow-x: auto;
            max-width: 100%;
            padding-bottom: 2px;
            scrollbar-width: none;
        }
        .is-client:not(.view-frontend-home) .nav::-webkit-scrollbar {
            display: none;
        }
        .is-client:not(.view-frontend-home) .nav a {
            flex: 0 0 auto;
            padding: 8px 10px;
            color: #737987;
            background: #f5f7fb;
            font-size: 13px;
            white-space: nowrap;
        }
        .is-client:not(.view-frontend-home) .nav a:hover {
            color: #ef5d63;
            background: #fff0f1;
        }
        .client-screen {
            display: grid;
            gap: 14px;
            min-width: 0;
            color: #202124;
        }
        .client-card,
        .client-hero-card,
        .watch-player-card,
        .account-profile-card {
            position: relative;
            overflow: hidden;
            min-width: 0;
            border: 1px solid rgba(236, 225, 220, .78);
            border-radius: 24px;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 14px 34px rgba(126, 75, 62, .08);
        }
        .client-card {
            display: grid;
            gap: 14px;
            padding: 16px;
        }
        .client-hero-card {
            display: grid;
            gap: 16px;
            padding: 16px;
        }
        .client-screen h1,
        .client-screen h2,
        .client-screen h3 {
            margin: 0;
            color: #14161b;
            letter-spacing: -.05em;
        }
        .client-screen p {
            margin: 0;
            color: #747986;
            line-height: 1.7;
        }
        .client-screen .eyebrow {
            color: #ef5d63;
            background: #fff0f1;
            border-color: rgba(239, 93, 99, .18);
        }
        .client-action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            min-width: 0;
        }
        .client-action-row .btn,
        .client-action-row button {
            flex: 1 1 128px;
            min-width: 0;
        }
        .client-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            min-width: 0;
        }
        .client-section-head > div {
            display: grid;
            gap: 7px;
            min-width: 0;
        }
        .novel-rank-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 148px;
            align-items: stretch;
            gap: 14px;
            background: linear-gradient(135deg, #fff, #fff8ed);
        }
        .novel-hero-book {
            display: grid;
            gap: 8px;
            min-width: 0;
            color: inherit;
        }
        .novel-hero-book img {
            width: 100%;
            aspect-ratio: 3 / 4;
            object-fit: cover;
            border-radius: 12px;
            background: #f2f3f5;
        }
        .novel-hero-book span {
            display: grid;
            gap: 4px;
            min-width: 0;
        }
        .novel-hero-book small {
            width: fit-content;
            padding: 4px 7px;
            border-radius: 999px;
            color: #7a4b00;
            background: #ffe9b6;
            font-size: 11px;
            font-weight: 950;
        }
        .novel-hero-book strong,
        .novel-rank-row strong {
            overflow: hidden;
            color: #171b24;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .novel-hero-book em,
        .novel-rank-row em {
            overflow: hidden;
            color: #77808f;
            font-style: normal;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .novel-rank-list {
            display: grid;
            gap: 10px;
        }
        .novel-rank-row {
            display: grid;
            grid-template-columns: 30px 58px minmax(0, 1fr) auto;
            align-items: center;
            gap: 10px;
            min-width: 0;
            padding: 10px;
            border: 1px solid #edf0f5;
            border-radius: 12px;
            color: inherit;
            background: #fff;
        }
        .novel-rank-row b {
            display: grid;
            place-items: center;
            width: 30px;
            height: 30px;
            border-radius: 999px;
            color: #7a4b00;
            background: #fff1c8;
            font-size: 13px;
            font-weight: 950;
        }
        .novel-rank-row img {
            width: 58px;
            aspect-ratio: 3 / 4;
            object-fit: cover;
            border-radius: 9px;
            background: #f2f3f5;
        }
        .novel-rank-row span {
            display: grid;
            gap: 6px;
            min-width: 0;
        }
        .novel-rank-row i {
            padding: 7px 10px;
            border-radius: 999px;
            color: #fff;
            background: #222b3d;
            font-size: 12px;
            font-style: normal;
            font-weight: 900;
            white-space: nowrap;
        }
        .client-section-head h2 {
            font-size: 22px;
        }
        .client-section-head > a,
        .client-section-head > span {
            flex: 0 0 auto;
            color: #8e94a1;
            font-size: 13px;
            font-weight: 900;
            white-space: nowrap;
        }
        .client-stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            min-width: 0;
        }
        .client-stat-grid span {
            display: grid;
            gap: 3px;
            min-width: 0;
            padding: 10px;
            border-radius: 17px;
            color: #6f7480;
            background: rgba(255, 245, 239, .82);
            text-align: center;
        }
        .client-stat-grid strong {
            overflow: hidden;
            color: #17191d;
            font-size: 18px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .client-stat-grid em {
            overflow: hidden;
            font-style: normal;
            font-size: 12px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .drama-detail-hero {
            grid-template-columns: 150px minmax(0, 1fr);
            align-items: stretch;
            padding: 14px;
            background:
                radial-gradient(circle at 16% 0%, rgba(255, 122, 127, .20), transparent 42%),
                linear-gradient(135deg, rgba(255,255,255,.94), rgba(255,246,241,.92));
        }
        .detail-poster-wrap {
            position: relative;
            overflow: hidden;
            min-height: 214px;
            border-radius: 20px;
            background: #f1f1f1;
            box-shadow: 0 18px 36px rgba(82, 41, 33, .13);
        }
        .detail-poster {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .detail-badge {
            position: absolute;
            left: 10px;
            top: 10px;
            padding: 5px 9px;
            border-radius: 999px;
            color: #fff;
            background: linear-gradient(135deg, #ff765f, #ef5d63);
            font-size: 12px;
            font-weight: 950;
            box-shadow: 0 8px 18px rgba(239, 93, 99, .24);
        }
        .detail-copy {
            display: grid;
            align-content: center;
            gap: 12px;
            min-width: 0;
        }
        .detail-copy h1 {
            display: -webkit-box;
            overflow: hidden;
            font-size: 27px;
            line-height: 1.08;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .detail-copy p {
            display: -webkit-box;
            overflow: hidden;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        .mini-benefit-strip {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .mini-benefit-strip > div {
            display: grid;
            gap: 5px;
            min-width: 0;
            padding: 14px;
            border-radius: 20px;
            background:
                linear-gradient(135deg, rgba(255, 245, 238, .96), rgba(255, 255, 255, .94));
            border: 1px solid rgba(238, 224, 218, .88);
            box-shadow: 0 10px 24px rgba(126, 75, 62, .06);
        }
        .mini-benefit-strip strong {
            overflow: hidden;
            color: #15171b;
            font-size: 17px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .mini-benefit-strip span {
            overflow: hidden;
            color: #8a909b;
            font-size: 12px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .mini-benefit-strip.compact > div {
            padding: 12px;
            background: #fff7f3;
        }
        .payment-route-picker {
            gap: 14px;
            border-color: rgba(239, 93, 99, .12);
            background:
                radial-gradient(circle at 90% 0%, rgba(255, 149, 93, .16), transparent 32%),
                rgba(255, 255, 255, .94);
        }
        .payment-route-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(132px, 1fr));
            gap: 10px;
            min-width: 0;
        }
        .payment-route-options.compact {
            width: min(100%, 420px);
            grid-template-columns: repeat(auto-fit, minmax(126px, 1fr));
        }
        .payment-route-option {
            position: relative;
            display: grid;
            gap: 5px;
            min-width: 0;
            padding: 12px 38px 12px 13px;
            border: 1px solid #edf0f5;
            border-radius: 18px;
            background: rgba(255,255,255,.82);
            text-align: left;
            cursor: pointer;
            transition: .18s ease;
        }
        .payment-route-option input {
            position: absolute;
            right: 13px;
            top: 16px;
            accent-color: #ef5d63;
        }
        .payment-route-head,
        .payment-route-method {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }
        .payment-method-icon {
            display: inline-grid;
            flex: 0 0 auto;
            place-items: center;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            color: #fff;
            background: #8b95a7;
            font-size: 16px;
            font-weight: 950;
            line-height: 1;
            box-shadow: 0 8px 16px rgba(20, 24, 32, .10);
        }
        .payment-method-icon.is-alipay {
            background: #1677ff;
        }
        .payment-method-icon.is-wechat {
            background: #16b66a;
        }
        .payment-method-icon.is-unionpay {
            background: linear-gradient(135deg, #e73843, #2b66d9);
        }
        .payment-route-option strong,
        .payment-route-option span {
            min-width: 0;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .payment-route-option strong {
            color: #1b1d22;
            font-size: 15px;
            font-weight: 950;
        }
        .payment-route-option span {
            color: #8a909b;
            font-size: 12px;
            font-weight: 800;
        }
        .payment-route-option.is-active {
            border-color: rgba(239, 93, 99, .42);
            background: #fff3ef;
            box-shadow: 0 12px 26px rgba(239, 93, 99, .12);
        }
        .episode-card-list {
            display: grid;
            gap: 10px;
        }
        .client-episode-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            min-width: 0;
            padding: 12px;
            border: 1px solid #edf0f5;
            border-radius: 19px;
            background: #fbfcff;
        }
        .client-episode-card.is-locked {
            background: #fff8f4;
            border-color: #f4e6df;
        }
        .episode-play-link {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
            min-width: 0;
        }
        .episode-index {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 15px;
            color: #ef5d63;
            background: #fff0f1;
            font-weight: 950;
        }
        .episode-play-link strong,
        .episode-play-link em {
            display: block;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .episode-play-link strong {
            color: #202124;
        }
        .episode-play-link em {
            margin-top: 4px;
            color: #9399a6;
            font-style: normal;
            font-size: 12px;
        }
        .episode-actions {
            display: flex;
            gap: 7px;
            align-items: center;
        }
        .episode-actions .btn {
            min-height: 36px;
            padding: 8px 12px;
            font-size: 13px;
            box-shadow: none;
        }
        .watch-player-card {
            display: grid;
            gap: 0;
            color: #fff;
            background:
                radial-gradient(circle at 50% 0%, rgba(255, 114, 92, .22), transparent 42%),
                linear-gradient(180deg, #18130f, #050505);
            border-color: rgba(255, 255, 255, .08);
            box-shadow: 0 20px 54px rgba(0, 0, 0, .24);
        }
        .watch-titlebar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 15px;
            color: rgba(255, 255, 255, .82);
            font-size: 13px;
            font-weight: 900;
        }
        .watch-titlebar a {
            min-width: 0;
            color: #fff;
        }
        .watch-video-frame {
            overflow: hidden;
            display: grid;
            min-height: 520px;
            border-radius: 22px 22px 0 0;
            background: #000;
        }
        .watch-video-frame video {
            width: 100%;
            height: 100%;
            min-height: 520px;
            max-height: none;
            object-fit: contain;
            border-radius: 0;
            background: #000;
        }
        .watch-lock-state {
            display: grid;
            place-items: center;
            align-content: center;
            gap: 14px;
            padding: 28px;
            text-align: center;
            background:
                linear-gradient(180deg, rgba(0,0,0,.18), rgba(0,0,0,.72)),
                url('/assets/cover-1.svg') center/cover no-repeat;
        }
        .watch-lock-state h1 {
            color: #fff;
            font-size: 32px;
        }
        .watch-lock-state p {
            max-width: 280px;
            color: rgba(255, 255, 255, .72);
        }
        .watch-meta-card h1 {
            font-size: 26px;
        }
        .watch-episode-strip {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 8px;
        }
        .watch-episode-strip a {
            display: grid;
            place-items: center;
            min-height: 42px;
            border-radius: 14px;
            color: #5f6570;
            background: #f5f7fb;
            font-weight: 950;
        }
        .watch-episode-strip a.is-active {
            color: #fff;
            background: linear-gradient(135deg, #ef5d63, #ff955d);
            box-shadow: 0 10px 20px rgba(239, 93, 99, .20);
        }
        .payment-status-hero {
            min-height: 190px;
            align-content: end;
            padding: 20px;
            background:
                radial-gradient(circle at 86% 12%, rgba(255, 149, 93, .30), transparent 28%),
                linear-gradient(135deg, #fff, #fff3ec);
        }
        .payment-status-orb {
            position: absolute;
            right: 20px;
            top: 18px;
            width: 64px;
            height: 64px;
            border-radius: 24px;
            background:
                radial-gradient(circle at 36% 30%, rgba(255,255,255,.92), transparent 24%),
                linear-gradient(135deg, #ff955d, #ef5d63);
            box-shadow: 0 18px 34px rgba(239, 93, 99, .24);
        }
        .payment-status-orb.is-paid {
            background:
                radial-gradient(circle at 36% 30%, rgba(255,255,255,.92), transparent 24%),
                linear-gradient(135deg, #6edc9c, #47bf7a);
            box-shadow: 0 18px 34px rgba(71, 191, 122, .22);
        }
        .payment-status-hero h1 {
            max-width: 72%;
            font-size: 36px;
        }
        .payment-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .payment-hero-meta span,
        .payment-hero-meta em {
            padding: 7px 10px;
            border-radius: 999px;
            color: #7c8491;
            background: rgba(255, 255, 255, .72);
            font-style: normal;
            font-size: 12px;
            font-weight: 900;
        }
        .payment-hero-meta strong {
            color: #ef5d63;
            font-size: 28px;
            letter-spacing: -.05em;
        }
        .payment-cashier-card.pay-panel {
            padding: 16px;
            border-color: rgba(239, 93, 99, .14);
            background: rgba(255, 255, 255, .94);
        }
        .payment-guide {
            display: grid;
            gap: 12px;
            min-width: 0;
        }
        .payment-guide h2 {
            font-size: 24px;
        }
        .payment-guide p {
            color: #737987;
        }
        .payment-mobile-only {
            display: none !important;
        }
        .payment-qr-grid.is-mobile-payment {
            grid-template-columns: 1fr;
        }
        .payment-qr-grid.is-mobile-payment .qr-card,
        .payment-qr-grid.is-mobile-payment .payment-desktop-only {
            display: none !important;
        }
        .payment-qr-grid.is-mobile-payment .payment-mobile-only {
            display: inline-flex !important;
        }
        .payment-qr-grid.is-mobile-payment h2.payment-mobile-only,
        .payment-qr-grid.is-mobile-payment p.payment-mobile-only {
            display: block !important;
        }
        .payment-state-text {
            padding: 10px 12px;
            border-radius: 16px;
            color: #7a4c28;
            background: #fff5e6;
            font-size: 13px;
        }
        .payment-success-card {
            position: relative;
            overflow: hidden;
            display: grid;
            gap: 12px;
            padding: 22px;
            border-color: rgba(71, 191, 122, .20);
            background:
                radial-gradient(circle at 86% 0%, rgba(71, 191, 122, .20), transparent 30%),
                linear-gradient(135deg, #fff, #f1fff7);
        }
        .payment-success-card[style*="display:none"] {
            display: none !important;
        }
        .payment-success-mark {
            display: grid;
            place-items: center;
            width: 58px;
            height: 58px;
            border-radius: 22px;
            color: #fff;
            background: linear-gradient(135deg, #49c378, #80e09e);
            box-shadow: 0 18px 32px rgba(71, 191, 122, .24);
            font-size: 30px;
            font-weight: 950;
        }
        .payment-success-card h2 {
            margin: 0;
            color: #111827;
            font-size: clamp(24px, 4vw, 34px);
            letter-spacing: -.05em;
        }
        .payment-success-card p {
            max-width: 520px;
            margin: 0;
            color: #6b7280;
            font-weight: 800;
            line-height: 1.7;
        }
        .payment-success-actions {
            margin-top: 6px;
        }
        .payment-api-line,
        .client-back-home {
            width: 100%;
            min-width: 0;
            overflow-wrap: anywhere;
        }
        .payment-order-card {
            gap: 16px;
        }
        .client-info-list {
            display: grid;
            gap: 10px;
        }
        .client-info-list div {
            display: grid;
            grid-template-columns: 86px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
            padding: 12px;
            border-radius: 16px;
            background: #f8fafc;
        }
        .client-info-list span {
            color: #9399a6;
            font-size: 12px;
            font-weight: 900;
        }
        .client-info-list strong {
            min-width: 0;
            overflow-wrap: anywhere;
            color: #202124;
            font-size: 14px;
        }
        .client-debug-card {
            overflow: hidden;
            border: 1px solid #edf0f5;
            border-radius: 18px;
            background: #fbfcff;
        }
        .client-debug-card summary {
            cursor: pointer;
            padding: 13px 14px;
            color: #555d6a;
            font-weight: 950;
        }
        .client-debug-card h3 {
            padding: 0 14px;
            font-size: 15px;
        }
        .client-debug-card pre {
            max-height: 260px;
            margin: 0 12px 12px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
        .account-profile-card {
            display: grid;
            grid-template-columns: 58px minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 16px;
            background:
                radial-gradient(circle at 100% 0%, rgba(255, 149, 93, .26), transparent 34%),
                linear-gradient(135deg, #fff, #fff4ee);
        }
        .account-avatar {
            display: grid;
            place-items: center;
            width: 58px;
            height: 58px;
            border-radius: 22px;
            color: #fff;
            background: linear-gradient(135deg, #ef5d63, #ff955d);
            box-shadow: 0 14px 28px rgba(239, 93, 99, .22);
            font-size: 24px;
            font-weight: 950;
        }
        .account-profile-card h1 {
            overflow: hidden;
            max-width: 100%;
            font-size: 25px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .account-profile-card p {
            overflow: hidden;
            margin-top: 4px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .account-profile-card .btn {
            min-height: 38px;
            padding: 8px 13px;
        }
        .view-account-center.is-client:not(.view-frontend-home) {
            background:
                radial-gradient(circle at 50% -12%, rgba(255, 180, 140, .44), transparent 30%),
                radial-gradient(circle at 100% 18%, rgba(255, 116, 94, .14), transparent 26%),
                linear-gradient(180deg, #fff7f1 0%, #fffaf6 46%, #f6f8fb 100%);
        }
        .view-account-center.is-client:not(.view-frontend-home) .wrap {
            max-width: 540px;
            padding-bottom: 34px;
        }
        .account-center-page {
            gap: 12px;
        }
        .account-center-hero {
            display: grid;
            gap: 16px;
            min-width: 0;
            padding: 18px;
            border: 1px solid rgba(255, 210, 196, .78);
            border-radius: 18px;
            background:
                radial-gradient(circle at 88% 8%, rgba(255, 116, 94, .22), transparent 32%),
                linear-gradient(135deg, rgba(255, 255, 255, .96), rgba(255, 245, 239, .92));
            box-shadow: 0 14px 30px rgba(126, 75, 62, .08);
        }
        .account-center-hero-main {
            display: grid;
            grid-template-columns: 62px minmax(0, 1fr);
            gap: 13px;
            align-items: center;
            min-width: 0;
        }
        .account-center-hero h1 {
            overflow: hidden;
            margin-top: 8px;
            color: #151922;
            font-size: 29px;
            line-height: 1.1;
            letter-spacing: 0;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .account-center-hero p {
            overflow: hidden;
            margin-top: 5px;
            color: #7a828f;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .account-center-hero-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .account-center-hero-actions .btn {
            justify-content: center;
            min-height: 40px;
            border-radius: 12px;
        }
        .account-balance-card {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .account-balance-card > div,
        .account-stat-grid span,
        .account-shortcut-grid a {
            min-width: 0;
            border: 1px solid rgba(236, 225, 220, .82);
            background: rgba(255, 255, 255, .88);
            box-shadow: 0 10px 24px rgba(126, 75, 62, .06);
        }
        .account-balance-card > div {
            display: grid;
            gap: 6px;
            padding: 14px;
            border-radius: 16px;
        }
        .account-balance-card span,
        .account-balance-card em,
        .account-stat-grid em,
        .account-shortcut-grid em {
            color: #848c99;
            font-style: normal;
            font-size: 12px;
            line-height: 1.45;
        }
        .account-balance-card strong {
            overflow: hidden;
            color: #151922;
            font-size: 24px;
            line-height: 1.08;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .account-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }
        .account-stat-grid span {
            display: grid;
            gap: 4px;
            justify-items: start;
            padding: 12px 10px;
            border-radius: 14px;
        }
        .account-stat-grid strong {
            color: #151922;
            font-size: 20px;
            line-height: 1.1;
        }
        .account-shortcut-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .account-shortcut-grid a {
            display: grid;
            grid-template-columns: 34px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
            padding: 12px;
            border-radius: 15px;
            color: inherit;
        }
        .account-shortcut-grid .ui-icon {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: 12px;
            color: #ff684d;
            background: #fff1ec;
        }
        .account-shortcut-grid .ui-icon svg {
            width: 19px;
            height: 19px;
        }
        .account-shortcut-grid span {
            display: grid;
            gap: 3px;
            min-width: 0;
        }
        .account-shortcut-grid strong {
            overflow: hidden;
            color: #151922;
            font-size: 14px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .account-section-card {
            border-radius: 18px;
            background: rgba(255, 255, 255, .94);
        }
        .account-list {
            display: grid;
            gap: 9px;
        }
        .account-list-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            min-width: 0;
            padding: 12px;
            border: 1px solid #edf0f5;
            border-radius: 14px;
            background: #fbfcff;
        }
        .account-list-row > div {
            display: grid;
            gap: 5px;
            min-width: 0;
        }
        .account-list-row span,
        .account-list-row strong,
        .account-list-row em {
            min-width: 0;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .account-list-row span {
            color: #8a93a3;
            font-size: 12px;
            font-weight: 900;
        }
        .account-list-row strong {
            color: #151922;
            font-size: 17px;
        }
        .account-list-row em {
            color: #858d9b;
            font-style: normal;
            font-size: 12px;
        }
        .account-list-row b {
            padding: 6px 9px;
            border-radius: 999px;
            color: #1e8a5b;
            background: #eafaf2;
            font-size: 12px;
            font-weight: 950;
            white-space: nowrap;
        }
        .account-list-row b.pending { color: #9a6500; background: #fff5dc; }
        .account-list-row b.paid { color: #1e8a5b; background: #eafaf2; }
        .account-list-row b.refund { color: #315bb8; background: #edf4ff; }
        .account-list-row b.failed { color: #b83b2f; background: #fff0ee; }
        .account-empty-state {
            border-radius: 14px;
            background: #f8fafc;
        }
        .account-stat-grid {
            padding: 0;
        }
        .client-list {
            display: grid;
            gap: 10px;
        }
        .client-list-item {
            display: grid;
            gap: 5px;
            min-width: 0;
            padding: 13px;
            border: 1px solid #edf0f5;
            border-radius: 18px;
            background: #fbfcff;
        }
        .client-list-item span,
        .client-list-item strong,
        .client-list-item em {
            min-width: 0;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .client-list-item span {
            color: #9097a4;
            font-size: 12px;
            font-weight: 900;
        }
        .client-list-item strong {
            color: #202124;
            font-size: 17px;
        }
        .client-list-item em {
            color: #8a909b;
            font-style: normal;
            font-size: 12px;
        }
        .client-empty-state {
            display: grid;
            justify-items: start;
            gap: 9px;
            padding: 18px;
            border-radius: 20px;
            background: #f8fafc;
        }
        .client-empty-state strong {
            color: #202124;
            font-size: 18px;
        }
        .bind-hero-card {
            min-height: 210px;
            align-content: end;
            padding: 22px;
            background:
                radial-gradient(circle at 84% 12%, rgba(255, 149, 93, .34), transparent 26%),
                linear-gradient(135deg, #fff, #fff3ec);
        }
        .bind-hero-card h1 {
            max-width: 320px;
            font-size: 34px;
            line-height: 1.08;
        }
        .bind-form-card button {
            width: 100%;
        }
        .view-admin-login.is-admin {
            min-height: 100vh;
            color: #f8fbff;
            background:
                radial-gradient(circle at 14% 18%, rgba(255, 130, 85, .30), transparent 28%),
                radial-gradient(circle at 82% 12%, rgba(77, 116, 255, .24), transparent 30%),
                radial-gradient(circle at 50% 100%, rgba(72, 210, 166, .16), transparent 36%),
                linear-gradient(135deg, #111827 0%, #0b1020 52%, #070910 100%);
        }
        .view-admin-login.is-admin::before {
            opacity: .32;
            background-image:
                linear-gradient(rgba(255,255,255,.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.07) 1px, transparent 1px);
            background-size: 52px 52px;
            mask-image: radial-gradient(circle at 50% 24%, #000, transparent 72%);
        }
        .view-admin-login .wrap {
            max-width: none;
            min-height: 100vh;
            padding: 0;
        }
        .view-admin-login .topbar {
            display: none;
        }
        .view-admin-login .admin-login {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(360px, 430px);
            gap: clamp(28px, 6vw, 76px);
            align-items: center;
            width: min(1180px, calc(100% - 40px));
            min-height: 100vh;
            margin: 0 auto;
            padding: clamp(28px, 5vw, 70px) 0;
        }
        .view-admin-login .admin-login::before {
            content: "";
            position: absolute;
            z-index: -1;
            inset: 8% 31% 8% auto;
            width: 360px;
            border-radius: 999px;
            background: linear-gradient(180deg, rgba(255,255,255,.15), rgba(255,255,255,0));
            filter: blur(34px);
            transform: rotate(18deg);
        }
        .login-aura {
            position: absolute;
            z-index: -2;
            border-radius: 999px;
            pointer-events: none;
            filter: blur(6px);
        }
        .login-aura-one {
            left: -80px;
            top: 12%;
            width: 240px;
            height: 240px;
            background: radial-gradient(circle, rgba(255, 126, 82, .34), transparent 68%);
        }
        .login-aura-two {
            right: -90px;
            bottom: 10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(82, 122, 255, .28), transparent 68%);
        }
        .login-hero {
            display: grid;
            gap: 22px;
            min-width: 0;
        }
        .login-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            width: fit-content;
            color: #fff;
            font-weight: 950;
        }
        .login-brand span {
            display: grid;
            place-items: center;
            width: 46px;
            height: 46px;
            border-radius: 17px;
            color: #172033;
            background: linear-gradient(135deg, #ffe2a2, #ff955d);
            box-shadow: 0 18px 36px rgba(255, 149, 93, .24);
            font-size: 15px;
            letter-spacing: -.06em;
        }
        .login-brand strong {
            font-size: 22px;
            letter-spacing: -.04em;
        }
        .view-admin-login .eyebrow {
            color: #ffe2a2;
            background: rgba(255, 226, 162, .10);
            border-color: rgba(255, 226, 162, .16);
        }
        .view-admin-login .admin-login h1 {
            max-width: 720px;
            color: #fff;
            font-family: "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
            font-size: clamp(48px, 7vw, 88px);
            line-height: .92;
            letter-spacing: -.08em;
            text-wrap: balance;
        }
        .login-subtitle {
            max-width: 620px;
            margin: 0;
            color: rgba(248, 251, 255, .72);
            font-size: 17px;
            line-height: 1.9;
        }
        .login-metric-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            max-width: 560px;
        }
        .login-metric-grid div {
            position: relative;
            overflow: hidden;
            display: grid;
            gap: 6px;
            min-width: 0;
            padding: 16px;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 22px;
            background: rgba(255, 255, 255, .08);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.12);
            backdrop-filter: blur(18px);
        }
        .login-metric-grid div::after {
            content: "";
            position: absolute;
            inset: auto -24px -34px auto;
            width: 76px;
            height: 76px;
            border-radius: 999px;
            background: rgba(255, 149, 93, .20);
        }
        .login-metric-grid strong {
            color: #fff;
            font-size: 28px;
            line-height: 1;
        }
        .login-metric-grid span {
            overflow: hidden;
            color: rgba(248, 251, 255, .62);
            font-size: 13px;
            font-weight: 800;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .login-preview-card {
            display: grid;
            gap: 18px;
            max-width: 520px;
            padding: 18px;
            border: 1px solid rgba(255, 255, 255, .13);
            border-radius: 28px;
            background:
                linear-gradient(135deg, rgba(255,255,255,.13), rgba(255,255,255,.06)),
                rgba(255, 255, 255, .06);
            box-shadow: 0 24px 70px rgba(0, 0, 0, .24);
            backdrop-filter: blur(24px);
        }
        .login-preview-card header,
        .login-preview-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .login-preview-card header span,
        .login-preview-row span {
            color: rgba(248, 251, 255, .68);
            font-weight: 900;
        }
        .login-preview-card header b {
            padding: 6px 10px;
            border-radius: 999px;
            color: #aef6df;
            background: rgba(103, 210, 176, .14);
            font-size: 12px;
        }
        .login-preview-bars {
            display: flex;
            align-items: end;
            gap: 10px;
            height: 128px;
            padding: 12px;
            border-radius: 20px;
            background:
                linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px),
                rgba(0, 0, 0, .18);
            background-size: 100% 32px;
        }
        .login-preview-bars i {
            flex: 1;
            min-width: 0;
            border-radius: 999px 999px 8px 8px;
            background: linear-gradient(180deg, #ffe2a2, #ff7b4b);
            box-shadow: 0 14px 30px rgba(255, 123, 75, .22);
        }
        .login-preview-row {
            padding: 13px 14px;
            border-radius: 18px;
            background: rgba(0, 0, 0, .20);
        }
        .login-preview-row strong {
            color: #fff;
            font-size: 14px;
        }
        .view-admin-login .login-panel {
            position: relative;
            overflow: hidden;
            gap: 18px;
            padding: 28px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 32px;
            color: #172033;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 30px 90px rgba(0, 0, 0, .34);
            backdrop-filter: blur(26px);
        }
        .view-admin-login .login-panel::before {
            content: "";
            position: absolute;
            inset: 0 0 auto;
            height: 8px;
            background: linear-gradient(90deg, #ff7b4b, #ffe2a2, #67d2b0);
        }
        .login-panel-head {
            display: flex;
            gap: 14px;
            align-items: center;
            min-width: 0;
        }
        .login-lock-mark {
            position: relative;
            display: grid;
            flex: 0 0 auto;
            width: 54px;
            height: 54px;
            place-items: center;
            border-radius: 20px;
            background: linear-gradient(135deg, #172033, #2b3850);
            box-shadow: 0 16px 34px rgba(23, 32, 51, .24);
        }
        .login-lock-mark::before,
        .login-lock-mark::after {
            content: "";
            position: absolute;
        }
        .login-lock-mark::before {
            top: 14px;
            width: 20px;
            height: 16px;
            border: 3px solid #ffe2a2;
            border-bottom: 0;
            border-radius: 999px 999px 0 0;
        }
        .login-lock-mark::after {
            bottom: 13px;
            width: 26px;
            height: 20px;
            border-radius: 8px;
            background: #ffe2a2;
        }
        .login-panel-head p {
            margin: 0 0 4px;
            color: #8a94a8;
            font-size: 13px;
            font-weight: 900;
        }
        .login-panel-head h2 {
            margin: 0;
            color: #121826;
            font-size: 30px;
            letter-spacing: -.05em;
        }
        .login-field {
            gap: 8px;
            color: #657386;
            font-weight: 900;
        }
        .login-field span {
            color: #314057;
        }
        .view-admin-login .login-field input {
            min-height: 50px;
            border-radius: 18px;
            color: #172033;
            background: #f7f9fc;
            border-color: #e3eaf5;
        }
        .view-admin-login .login-field input:focus {
            border-color: rgba(255, 123, 75, .78);
            box-shadow: 0 0 0 4px rgba(255, 123, 75, .13);
            background: #fff;
        }
        .login-verify {
            display: grid;
            gap: 10px;
            min-width: 0;
            padding: 12px;
            border: 1px solid #e3eaf5;
            border-radius: 20px;
            background: #f7f9fc;
        }
        .login-verify-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            color: #8a94a8;
            font-size: 12px;
            font-weight: 900;
        }
        .login-verify-head strong {
            color: #314057;
            font-size: 12px;
        }
        .login-slider-track {
            position: relative;
            overflow: hidden;
            height: 54px;
            border: 1px solid #dfe7f3;
            border-radius: 18px;
            background:
                linear-gradient(135deg, rgba(255,255,255,.76), rgba(238,244,255,.78)),
                repeating-linear-gradient(135deg, transparent 0 12px, rgba(23,32,51,.04) 12px 24px);
            touch-action: none;
            user-select: none;
        }
        .login-slider-target {
            position: absolute;
            top: 9px;
            z-index: 2;
            width: 36px;
            height: 36px;
            border: 2px dashed rgba(255, 123, 75, .72);
            border-radius: 14px;
            background: rgba(255, 123, 75, .11);
            box-shadow: 0 0 0 6px rgba(255, 123, 75, .08);
        }
        .login-slider-fill {
            position: absolute;
            inset: 0 auto 0 0;
            z-index: 1;
            width: 0;
            border-radius: inherit;
            background: linear-gradient(90deg, rgba(255, 123, 75, .18), rgba(255, 226, 162, .24));
        }
        .login-slider-thumb {
            position: absolute;
            top: 5px;
            left: -24px;
            z-index: 4;
            display: grid;
            width: 48px;
            height: 42px;
            min-height: 0;
            place-items: center;
            padding: 0;
            border: 0;
            border-radius: 16px;
            color: #172033;
            background: linear-gradient(135deg, #ff7b4b, #ffe2a2);
            box-shadow: 0 12px 24px rgba(255, 123, 75, .26);
            cursor: grab;
            transition: box-shadow .18s ease, transform .18s ease;
        }
        .login-slider-thumb:active {
            cursor: grabbing;
            transform: scale(.98);
        }
        .login-slider-thumb span,
        .login-slider-thumb span::before,
        .login-slider-thumb span::after {
            display: block;
            width: 4px;
            height: 16px;
            border-radius: 999px;
            background: rgba(23, 32, 51, .72);
        }
        .login-slider-thumb span {
            position: relative;
        }
        .login-slider-thumb span::before,
        .login-slider-thumb span::after {
            content: "";
            position: absolute;
            top: 0;
        }
        .login-slider-thumb span::before {
            left: -8px;
        }
        .login-slider-thumb span::after {
            right: -8px;
        }
        .login-slider-track em {
            position: absolute;
            inset: 0 14px 0 62px;
            z-index: 3;
            display: grid;
            align-items: center;
            color: #8a94a8;
            font-style: normal;
            font-size: 13px;
            font-weight: 900;
            pointer-events: none;
            transition: color .2s ease;
        }
        .login-verify.is-passed {
            border-color: rgba(103, 210, 176, .44);
            background: #f0fffa;
        }
        .login-verify.is-passed .login-slider-target {
            border-color: rgba(103, 210, 176, .78);
            background: rgba(103, 210, 176, .16);
            box-shadow: 0 0 0 6px rgba(103, 210, 176, .08);
        }
        .login-verify.is-passed .login-slider-fill {
            background: linear-gradient(90deg, rgba(103, 210, 176, .30), rgba(103, 210, 176, .12));
        }
        .login-verify.is-passed .login-slider-thumb {
            background: linear-gradient(135deg, #67d2b0, #b8f4de);
            box-shadow: 0 12px 24px rgba(103, 210, 176, .22);
        }
        .login-verify.is-passed .login-slider-track em,
        .login-verify.is-passed .login-verify-head strong {
            color: #17865f;
        }
        .login-verify.is-error {
            border-color: rgba(232, 93, 117, .38);
            background: #fff5f7;
            animation: loginVerifyShake .32s ease;
        }
        .login-verify.is-error .login-slider-track em,
        .login-verify.is-error .login-verify-head strong {
            color: #d63045;
        }
        @keyframes loginVerifyShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .view-admin-login .login-panel .btn {
            min-height: 52px;
            border-radius: 18px;
            box-shadow: 0 18px 38px rgba(255, 123, 75, .26);
        }
        .login-helper-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: space-between;
            color: #8a94a8;
            font-size: 12px;
            font-weight: 800;
        }
        .login-helper-row span {
            min-width: 0;
            overflow-wrap: anywhere;
        }
        .is-admin {
            background: #f5f7fb;
        }
        .is-admin .wrap {
            max-width: 1680px;
            padding: 12px 18px 24px;
        }
        .admin-topbar {
            top: 8px;
            min-height: 58px;
            padding: 8px 14px;
            border-radius: 14px;
            border-color: #e3e8f1;
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 8px 22px rgba(36, 48, 73, .08);
        }
        .admin-topbar .brand {
            min-width: 104px;
            font-size: 22px;
            letter-spacing: 0;
        }
        .admin-topbar .nav {
            gap: 4px;
        }
        .admin-topbar .nav a {
            min-height: 38px;
            padding: 8px 10px;
            border-radius: 10px;
            color: #5d6878;
            font-size: 14px;
            font-weight: 800;
        }
        .admin-topbar .nav a.is-active,
        .admin-topbar .nav a:hover {
            color: #2459e8;
            background: #eef4ff;
            transform: none;
        }
        .admin-user-chip {
            min-width: 88px;
            color: #4f5b6d;
            font-size: 13px;
        }
        .admin-shell {
            grid-template-columns: 226px minmax(0, 1fr);
            gap: 14px;
        }
        .admin-menu {
            top: 78px;
            min-height: calc(100vh - 104px);
            padding: 14px;
            border-radius: 14px;
            border: 1px solid #e5eaf2;
            background: #fff;
            box-shadow: 0 8px 24px rgba(36, 48, 73, .06);
        }
        .admin-menu::after {
            padding-top: 12px;
            font-size: 12px;
        }
        .admin-menu strong {
            margin-bottom: 8px;
            color: #172033;
            font-size: 16px;
        }
        .secondary-menu-group {
            gap: 4px;
        }
        .admin-menu a:not(.btn) {
            min-height: 38px;
            padding: 8px 10px;
            border-radius: 8px;
            color: #5f6b7c;
            font-size: 14px;
            font-weight: 800;
        }
        .admin-menu a.is-active,
        .admin-menu a:not(.btn):hover {
            color: #2459e8;
            background: #eef4ff;
        }
        .admin-panel {
            gap: 14px;
        }
        .admin-panel .panel,
        .admin-panel .row-card,
        .admin-panel .card {
            border-color: #e3e9f2;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(36, 48, 73, .06);
            backdrop-filter: none;
        }
        .admin-panel .admin-section.panel {
            padding: 18px;
        }
        .admin-page-title,
        .admin-compact-section .section-title {
            margin: 0 0 14px !important;
            gap: 12px;
            align-items: flex-start;
        }
        .admin-section-title {
            margin-top: 0;
        }
        .admin-page-title h2,
        .admin-compact-section .section-title h2 {
            margin: 4px 0 0;
            color: #172033;
            font-size: 26px;
            line-height: 1.15;
            letter-spacing: 0;
        }
        .admin-panel .eyebrow,
        .admin-panel .tag,
        .admin-panel .pill {
            min-height: 24px;
            padding: 4px 8px;
            border-radius: 7px;
            font-size: 12px;
            letter-spacing: 0;
        }
        .admin-panel .btn,
        .admin-panel button {
            min-height: 34px;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 14px;
            box-shadow: none;
        }
        .admin-panel .btn.primary,
        .admin-panel button.primary {
            background: #ff7b42;
        }
        .admin-panel .btn.ghost {
            color: #2459e8;
            background: #f3f6ff;
            border-color: #dce5ff;
        }
        .admin-panel input,
        .admin-panel textarea,
        .admin-panel select {
            min-height: 34px;
            padding: 7px 10px;
            border-radius: 8px;
            background: #fff;
            border-color: #d8e0ec;
            box-shadow: none;
        }
        .admin-panel label {
            gap: 5px;
            color: #627083;
            font-size: 12px;
            font-weight: 800;
        }
        .admin-panel .form-grid {
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px;
        }
        .admin-summary-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }
        .admin-summary-strip div {
            min-width: 0;
            padding: 12px 14px;
            border: 1px solid #e3e9f2;
            border-radius: 10px;
            background: #f8fafc;
        }
        .admin-summary-strip strong,
        .admin-summary-strip span {
            display: block;
        }
        .admin-summary-strip strong {
            color: #172033;
            font-size: 22px;
            line-height: 1.1;
        }
        .admin-summary-strip span {
            margin-top: 4px;
            color: #738094;
            font-size: 12px;
            font-weight: 800;
        }
        .admin-filter-bar,
        .order-filter-bar.admin-filter-bar {
            grid-template-columns: minmax(220px, 340px) auto;
            justify-content: start;
            gap: 10px;
            align-items: end;
            margin-bottom: 12px;
            padding: 12px;
            border-radius: 10px;
            background: #f8fafc;
        }
        .admin-data-panel {
            overflow: hidden;
            border: 1px solid #e3e9f2;
            border-radius: 12px;
            background: #fff;
        }
        .admin-data-head,
        .admin-data-row {
            display: grid;
            gap: 12px;
            align-items: stretch;
        }
        .admin-data-head {
            padding: 10px 14px;
            border-bottom: 1px solid #e8edf5;
            color: #6d7a8d;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 900;
        }
        .admin-data-row {
            padding: 12px 14px;
            border: 0;
            border-bottom: 1px solid #edf1f6;
            border-radius: 0;
            background: #fff;
            box-shadow: none;
        }
        .admin-data-row:last-child {
            border-bottom: 0;
        }
        body.has-admin-drawer {
            overflow: hidden;
        }
        .admin-page-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin: 0 0 14px;
            padding-bottom: 14px;
            border-bottom: 1px solid #e7ecf3;
        }
        .admin-page-head h2,
        .admin-page-head h3 {
            margin: 4px 0 0;
            color: #172033;
            font-size: 24px;
            line-height: 1.16;
            letter-spacing: 0;
        }
        .admin-page-head p {
            margin: 6px 0 0;
            max-width: 720px;
        }
        .admin-page-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }
        .admin-summary-strip-tight {
            margin-bottom: 12px;
        }
        .admin-tool-section {
            display: none;
        }
        .admin-tool-section.is-active {
            display: block;
        }
        .drama-list-panel {
            margin-top: 12px;
        }
        .drama-data-head,
        .drama-data-row {
            grid-template-columns: minmax(260px, 1.18fr) minmax(190px, .9fr) minmax(150px, .7fr) minmax(130px, .55fr) minmax(210px, .72fr);
        }
        .drama-data-row {
            align-items: center;
        }
        .work-editor-section {
            padding: 16px;
            background: #f6f8fb;
        }
        .work-editor-head {
            margin-bottom: 12px;
            border-bottom: 0;
        }
        .work-editor-layout {
            display: grid;
            grid-template-columns: minmax(360px, 540px) minmax(0, 1fr);
            gap: 14px;
            align-items: start;
        }
        .work-editor-side,
        .work-editor-main {
            min-width: 0;
        }
        .work-editor-form,
        .work-editor-main {
            border: 1px solid #e3e9f2;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 16px 40px rgba(15, 23, 42, .06);
        }
        .work-editor-form {
            overflow: hidden;
        }
        .work-editor-cover {
            position: relative;
            display: grid;
            place-items: center;
            min-height: 238px;
            margin: 14px;
            overflow: hidden;
            border-radius: 10px;
            background: #172033;
        }
        .work-editor-cover::before {
            content: "";
            position: absolute;
            inset: -18px;
            background-image: var(--work-cover);
            background-size: cover;
            background-position: center;
            filter: blur(14px);
            opacity: .62;
            transform: scale(1.08);
        }
        .work-editor-cover img {
            position: relative;
            z-index: 1;
            width: min(160px, 42%);
            max-height: 210px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 20px 42px rgba(15, 23, 42, .36);
        }
        .work-editor-cover svg {
            position: relative;
            z-index: 1;
            width: 64px;
            height: 64px;
            color: #fff;
        }
        .work-editor-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            padding: 0 14px 14px;
        }
        .work-editor-field-full {
            grid-column: 1 / -1;
        }
        .work-editor-fields textarea {
            min-height: 150px;
            resize: vertical;
        }
        .work-editor-savebar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 14px 14px;
            border-top: 1px solid #e7ecf3;
        }
        .work-editor-savebar > span:last-child {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .work-editor-main {
            padding: 14px;
        }
        .work-editor-toolbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }
        .work-editor-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .work-editor-tabs span,
        .work-editor-create summary,
        .work-editor-ranges a {
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            min-height: 34px;
            padding: 7px 12px;
            border: 1px solid #e1e7f0;
            border-radius: 8px;
            color: #314055;
            background: #f7f9fc;
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
        }
        .work-editor-tabs .is-active,
        .work-editor-ranges a.is-active {
            color: #fff;
            border-color: #2f7df6;
            background: #2f7df6;
        }
        .work-editor-create {
            min-width: min(360px, 100%);
        }
        .work-editor-create summary {
            cursor: pointer;
            list-style: none;
        }
        .work-editor-create summary::-webkit-details-marker {
            display: none;
        }
        .work-editor-create[open] {
            padding: 10px;
            border: 1px solid #e3e9f2;
            border-radius: 10px;
            background: #f8fafc;
        }
        .work-editor-create[open] summary {
            margin-bottom: 10px;
            background: #fff;
        }
        .work-editor-ranges {
            margin-bottom: 16px;
            padding: 12px;
            border: 1px solid #e8edf5;
            border-radius: 10px;
            background: #fff;
        }
        .work-editor-ranges strong,
        .work-editor-ranges span {
            display: block;
        }
        .work-editor-ranges strong {
            color: #172033;
            font-size: 14px;
        }
        .work-editor-ranges > span {
            margin-top: 3px;
            color: #738094;
            font-size: 12px;
            font-weight: 800;
        }
        .work-editor-ranges div {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .work-editor-unit-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 14px;
        }
        .work-editor-unit-card {
            min-width: 0;
            border: 1px solid #e4eaf3;
            border-radius: 10px;
            background: #fff;
            overflow: hidden;
        }
        .work-editor-unit-poster {
            display: grid;
            place-items: center;
            aspect-ratio: 9 / 12;
            overflow: hidden;
            background: #eef2f7;
        }
        .work-editor-unit-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .work-editor-unit-card > strong {
            display: block;
            padding: 9px 8px 8px;
            color: #172033;
            text-align: center;
            font-size: 13px;
            line-height: 1.2;
        }
        .work-editor-unit-edit {
            border-top: 1px solid #edf1f6;
        }
        .work-editor-unit-edit summary {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            cursor: pointer;
            color: #2f61d6;
            background: #f7f9fc;
            font-size: 12px;
            font-weight: 900;
            list-style: none;
        }
        .work-editor-unit-edit summary::-webkit-details-marker {
            display: none;
        }
        .work-editor-unit-form {
            display: grid;
            gap: 10px;
            padding: 10px;
        }
        .work-editor-unit-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .work-editor-unit-form .btn {
            justify-content: center;
        }
        .work-editor-chapter-list {
            display: grid;
            gap: 10px;
        }
        .work-editor-chapter-list .work-editor-unit-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(220px, .72fr);
            align-items: stretch;
        }
        .work-editor-chapter-summary {
            display: grid;
            gap: 5px;
            align-content: center;
            min-width: 0;
            padding: 12px;
        }
        .work-editor-chapter-summary strong {
            color: #172033;
            font-size: 14px;
            overflow-wrap: anywhere;
        }
        .work-editor-chapter-summary span {
            color: #738094;
            font-size: 12px;
            font-weight: 800;
        }
        .work-editor-chapter-list .work-editor-unit-edit {
            border-top: 0;
            border-left: 1px solid #edf1f6;
        }
        .works-data-cell,
        .works-status-stack {
            display: grid;
            gap: 6px;
            min-width: 0;
            align-content: center;
        }
        .works-data-cell strong {
            color: #172033;
            font-size: 14px;
            line-height: 1.35;
        }
        .works-data-cell em {
            color: #6b7789;
            font-size: 12px;
            font-style: normal;
            font-weight: 750;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }
        .admin-data-panel > .empty {
            display: grid;
            place-items: center;
            min-height: 112px;
            color: #7b8798;
            font-size: 14px;
            font-weight: 800;
            background: #fff;
        }
        .admin-drawer[hidden] {
            display: none !important;
        }
        .admin-drawer {
            position: fixed;
            inset: 0;
            z-index: 9140;
            display: grid;
            justify-items: end;
            background: transparent;
        }
        .admin-drawer-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, .46);
        }
        .admin-drawer-card {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-rows: auto minmax(0, 1fr);
            width: min(620px, calc(100vw - 20px));
            height: 100vh;
            height: 100dvh;
            overflow: hidden;
            border-left: 1px solid #dfe6f0;
            background: #fff;
            box-shadow: -24px 0 58px rgba(15, 23, 42, .2);
        }
        .admin-drawer-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 22px 16px;
            border-bottom: 1px solid #e7ecf3;
            background: #fff;
        }
        .admin-drawer-head h3 {
            margin: 4px 0 0;
            color: #172033;
            font-size: 22px;
            line-height: 1.2;
            letter-spacing: 0;
        }
        .admin-drawer-head p {
            margin: 6px 0 0;
            color: #6b7789;
            font-size: 13px;
            font-weight: 750;
            line-height: 1.45;
        }
        .admin-drawer-head > button {
            display: grid;
            flex: 0 0 34px;
            place-items: center;
            width: 34px;
            height: 34px;
            min-height: 34px;
            padding: 0;
            border: 1px solid #dfe6f0;
            border-radius: 8px;
            color: #5f6b7c;
            background: #f8fafc;
            font-size: 22px;
            line-height: 1;
            box-shadow: none;
        }
        .admin-drawer-form {
            overflow: auto;
            display: grid;
            grid-template-rows: 1fr auto;
            gap: 0;
            min-height: 0;
            background: #f7f9fc;
        }
        .admin-drawer-form > section {
            margin: 0;
            padding: 18px 22px;
            border-bottom: 1px solid #e7ecf3;
            background: #fff;
        }
        .admin-drawer-form > section + section {
            margin-top: 8px;
            border-top: 1px solid #e7ecf3;
        }
        .admin-drawer-form h4 {
            margin: 0 0 12px;
            color: #172033;
            font-size: 14px;
            line-height: 1.2;
        }
        .admin-drawer-form .form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .admin-form-span-2 {
            grid-column: 1 / -1;
        }
        .admin-check-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }
        .admin-check-grid label {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 7px 10px;
            border: 1px solid #dfe6f0;
            border-radius: 8px;
            color: #344054;
            background: #f8fafc;
            font-size: 13px;
            font-weight: 850;
        }
        .admin-check-grid span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .admin-drawer-form footer {
            position: sticky;
            bottom: 0;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 14px 22px max(14px, env(safe-area-inset-bottom));
            border-top: 1px solid #dfe6f0;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 -10px 28px rgba(15, 23, 42, .06);
            backdrop-filter: blur(16px);
        }
        .user-list-head,
        .user-edit-row {
            grid-template-columns: minmax(220px, 1.15fr) minmax(230px, 1.08fr) minmax(210px, .92fr) minmax(150px, .7fr) minmax(170px, .76fr);
        }
        .user-main-cell,
        .user-field-stack,
        .user-toggle-stack,
        .admin-row-actions {
            min-width: 0;
        }
        .user-main-cell,
        .user-field-stack {
            display: grid;
            gap: 8px;
        }
        .user-main-cell strong {
            overflow: hidden;
            color: #172033;
            font-size: 15px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .user-main-cell em {
            color: #7b8798;
            font-size: 12px;
            font-style: normal;
            font-weight: 800;
        }
        .user-toggle-stack {
            display: grid;
            align-content: center;
            gap: 8px;
            color: #344054;
            font-size: 13px;
            font-weight: 800;
        }
        .user-toggle-stack label {
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .admin-row-actions {
            display: flex;
            flex-wrap: wrap;
            align-content: center;
            justify-content: flex-end;
            gap: 7px;
        }
        .admin-row-actions .btn {
            min-width: 0;
            padding-inline: 10px;
        }
        .user-profile-card {
            margin-bottom: 12px;
            padding: 14px;
            border-radius: 12px;
            background: #fff;
        }
        .user-profile-card .kpi {
            min-height: 104px;
            padding: 14px;
            border-radius: 10px;
            box-shadow: none;
        }
        .user-profile-card .kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
        }
        .user-profile-card .kpi strong {
            margin-top: 8px;
            font-size: 22px;
        }
        .admin-form-panel {
            margin-bottom: 14px;
            padding: 14px;
            border: 1px solid #e3e9f2;
            border-radius: 12px;
            background: #f8fafc;
        }
        .admin-form-actions {
            display: flex;
            justify-content: flex-end;
            margin: 0;
        }
        .rights-repair-form .form-grid {
            grid-template-columns: repeat(4, minmax(150px, 1fr));
        }
        .rights-log-head,
        .rights-log-row {
            grid-template-columns: minmax(190px, .9fr) minmax(160px, .72fr) minmax(250px, 1.1fr) minmax(220px, .96fr);
        }
        .rights-log-panel {
            display: grid;
            gap: 0;
        }
        .rights-log-panel .order-row-head {
            margin: 0;
            border: 0;
            border-bottom: 1px solid #e8edf5;
            border-radius: 0;
            background: #f8fafc;
        }
        .rights-log-panel .rights-log-row {
            border: 0;
            border-bottom: 1px solid #edf1f6;
            border-radius: 0;
            background: #fff;
            box-shadow: none;
        }
        .rights-log-panel .rights-log-row:last-child {
            border-bottom: 0;
        }
        .rights-log-row span,
        .rights-log-row em {
            min-width: 0;
            overflow-wrap: anywhere;
        }
        .rights-log-row em {
            display: block;
            margin-top: 4px;
            color: #7b8798;
            font-style: normal;
            font-size: 12px;
        }
        .admin-empty-state {
            display: grid;
            place-items: center;
            gap: 5px;
            min-height: 118px;
            padding: 22px;
            color: #738094;
            text-align: center;
            background: #fff;
        }
        .admin-empty-state strong {
            color: #172033;
            font-size: 15px;
        }
        .admin-placeholder {
            text-align: left;
        }
        .admin-placeholder .placeholder-grid {
            margin-top: 14px;
        }
        .admin-placeholder .system-item {
            border-radius: 10px;
            background: #f8fafc;
            box-shadow: none;
        }
        .is-admin {
            background: #f3f5f8;
        }
        .admin-shell {
            grid-template-columns: 210px minmax(0, 1fr);
            gap: 12px;
        }
        .admin-menu {
            padding: 12px;
            border-radius: 10px;
            box-shadow: none;
        }
        .admin-menu a:not(.btn) {
            min-height: 36px;
            border-radius: 7px;
        }
        .admin-menu > .btn.ghost {
            width: 100%;
            min-height: 38px;
            margin-top: 12px;
            padding: 8px 10px;
            justify-content: flex-start;
            border-radius: 7px;
            color: #3454a5;
            background: #fff;
            border-color: #e1e7f0;
            box-shadow: none;
        }
        .admin-menu > .btn.ghost:hover {
            background: #f6f8fb;
        }
        .admin-menu::after {
            margin-top: 14px;
            padding-top: 12px;
        }
        @media (min-width: 821px) {
            .admin-shell {
                grid-template-columns: 164px minmax(0, 1fr);
                gap: 14px;
                align-items: start;
            }
            .admin-menu {
                position: sticky;
                top: 78px;
                align-self: start;
                overflow: auto;
                display: grid;
                gap: 6px;
                width: 164px;
                min-height: 0;
                height: auto;
                max-height: calc(100vh - 96px);
                padding: 10px;
                border: 1px solid #e4eaf3;
                border-radius: 10px;
                background: #fff;
                box-shadow: none;
            }
            .admin-menu::after {
                content: none;
                display: none;
            }
            .admin-menu-section-title {
                display: flex;
                align-items: center;
                gap: 7px;
                margin: 0 0 4px;
                padding: 4px 6px 8px;
                border-bottom: 1px solid #eef2f7;
                color: #6d7889;
                font-size: 12px;
                font-weight: 900;
                line-height: 1.2;
                letter-spacing: 0;
            }
            .admin-menu-section-title .ui-icon,
            .admin-menu-section-title .ui-icon svg {
                width: 15px;
                height: 15px;
            }
            .secondary-menu-group {
                gap: 2px;
            }
            .admin-menu a:not(.btn) {
                min-height: 32px;
                padding: 6px 8px;
                gap: 8px;
                border-radius: 6px;
                color: #5f6b7c;
                background: transparent;
                font-size: 13px;
                font-weight: 800;
                line-height: 1.2;
            }
            .admin-menu a:not(.btn) .ui-icon,
            .admin-menu a:not(.btn) .ui-icon svg {
                width: 16px;
                height: 16px;
            }
            .admin-menu a.is-active {
                color: #2459e8;
                background: #eef4ff;
                box-shadow: inset 2px 0 0 #3165f4;
            }
            .admin-menu a:not(.btn):hover {
                color: #2459e8;
                background: #f5f8ff;
                box-shadow: inset 2px 0 0 #b9cdfd;
            }
            .admin-menu-logout.btn.ghost {
                width: auto;
                min-height: 30px;
                margin-top: 6px;
                padding: 6px 8px;
                justify-content: flex-start;
                border: 0;
                border-top: 1px solid #eef2f7;
                border-radius: 0;
                color: #6f7b8c;
                background: transparent;
                font-size: 12px;
                font-weight: 850;
            }
            .admin-menu-logout.btn.ghost:hover {
                color: #d14b3f;
                background: #fff6f4;
                border-radius: 6px;
            }
            .admin-menu-logout .ui-icon,
            .admin-menu-logout .ui-icon svg {
                width: 15px;
                height: 15px;
            }
        }
        .admin-panel .admin-section.panel {
            padding: 16px;
            border-radius: 10px;
            box-shadow: none;
        }
        .admin-panel .section-title {
            margin: 0 0 12px !important;
        }
        .admin-panel .section-title h2 {
            font-size: 24px;
            letter-spacing: 0;
        }
        .admin-panel .muted {
            color: #66758a;
        }
        .admin-panel .row-card,
        .admin-panel .order-info-card,
        .admin-panel .filter-preset-panel {
            box-shadow: none;
        }
        .admin-panel .kpi {
            min-height: 106px;
            padding: 15px 16px;
            border-radius: 10px;
            background: #fff;
            box-shadow: none;
            transition: none;
        }
        .admin-panel .kpi:hover {
            transform: none;
            box-shadow: none;
        }
        .admin-panel .kpi-icon {
            right: 14px;
            top: 14px;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            color: #315ee8;
            background: #eef3ff;
            box-shadow: none;
        }
        .admin-panel .kpi-icon::after {
            content: none;
        }
        .admin-panel .kpi-icon .ui-icon,
        .admin-panel .kpi-icon .ui-icon svg {
            width: 20px;
            height: 20px;
        }
        .admin-panel .kpi small {
            max-width: calc(100% - 52px);
            color: #6a7789;
            font-size: 12px;
            letter-spacing: 0;
        }
        .admin-panel .kpi strong {
            margin-top: 14px;
            font-size: 26px;
            letter-spacing: 0;
        }
        .admin-panel .kpi em {
            margin-top: 6px;
            color: #7b8798;
            font-size: 12px;
            line-height: 1.55;
        }
        .admin-section[data-admin-primary="stats"] {
            color: #172033;
        }
        .admin-section[data-admin-primary="stats"] .section-title {
            padding-bottom: 10px;
            border-bottom: 1px solid #e7ecf3;
        }
        .analytics-filter-bar {
            grid-template-columns: repeat(6, minmax(118px, 1fr)) auto;
            gap: 8px;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 8px;
            background: #f7f9fc;
            border-color: #e2e8f0;
        }
        .analytics-filter-bar label {
            font-size: 12px;
            color: #596779;
        }
        .analytics-filter-bar input,
        .analytics-filter-bar select {
            min-height: 32px;
            padding: 6px 9px;
            border-radius: 6px;
        }
        .analytics-filter-bar .order-filter-actions {
            align-self: end;
            gap: 8px;
        }
        .analytics-scope-card {
            margin-bottom: 8px;
            padding: 9px 12px;
            border-radius: 7px;
            background: #fff;
            border-color: #e5ebf3;
            box-shadow: none;
        }
        .analytics-scope-card strong {
            color: #172033;
            font-size: 13px;
        }
        .analytics-scope-card em {
            color: #596779;
            font-size: 13px;
            font-style: normal;
        }
        .analytics-preset-panel {
            margin: 0 0 12px;
            padding: 10px;
            border-radius: 8px;
            background: #fff;
        }
        .analytics-preset-panel .filter-preset-save {
            grid-template-columns: minmax(240px, 1fr) auto auto;
        }
        .admin-section[data-admin-primary="stats"] .kpi-grid {
            gap: 10px;
            margin: 0 0 12px;
        }
        .admin-section[data-admin-primary="stats"] .kpi {
            min-height: 92px;
            border-left: 3px solid #d8e1ee;
        }
        .admin-section[data-admin-primary="stats"] .kpi.blue { border-left-color: #4d74ff; }
        .admin-section[data-admin-primary="stats"] .kpi.green { border-left-color: #38b965; }
        .admin-section[data-admin-primary="stats"] .kpi.orange { border-left-color: #ff8a34; }
        .admin-section[data-admin-primary="stats"] .kpi.cyan { border-left-color: #19a9c7; }
        .admin-section[data-admin-primary="stats"] .repair-grid {
            gap: 10px;
            margin-bottom: 12px;
        }
        .admin-section[data-admin-primary="stats"] .order-info-card {
            padding: 14px 16px;
            border: 1px solid #e5ebf3;
            border-radius: 8px;
            background: #fff;
        }
        .admin-section[data-admin-primary="stats"] .order-info-card h4 {
            margin: 0 0 10px;
            padding-bottom: 9px;
            font-size: 14px;
            border-bottom-color: #e8edf4;
        }
        .admin-section[data-admin-primary="stats"] .repair-log-list {
            display: grid;
            gap: 0;
        }
        .admin-section[data-admin-primary="stats"] .repair-log-list > div {
            display: grid;
            grid-template-columns: minmax(110px, .35fr) auto minmax(0, 1fr);
            gap: 8px;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eef2f7;
        }
        .admin-section[data-admin-primary="stats"] .repair-log-list > div:last-child {
            border-bottom: 0;
        }
        .admin-section[data-admin-primary="stats"] .repair-log-list strong {
            color: #172033;
            font-size: 13px;
        }
        .admin-section[data-admin-primary="stats"] .repair-log-list em {
            color: #596779;
            font-style: normal;
            font-size: 13px;
        }
        .admin-section[data-admin-primary="stats"] .order-table {
            border-radius: 8px;
            border-color: #e2e8f0;
            box-shadow: none;
        }
        .admin-section[data-admin-primary="stats"] .order-table .row-card {
            border: 0;
            border-bottom: 1px solid #edf1f6;
            border-radius: 0;
            background: #fff;
            box-shadow: none;
        }
        .admin-section[data-admin-primary="stats"] .order-table .row-card:last-child {
            border-bottom: 0;
        }
        .admin-section[data-admin-primary="stats"] .order-row-head {
            padding: 10px 14px;
            color: #6b7789;
            background: #f7f9fc;
            font-size: 12px;
        }
        .admin-section[data-admin-primary="stats"] .order-row {
            padding: 12px 14px;
            align-items: center;
        }
        .admin-section[data-admin-primary="stats"] .order-row strong {
            color: #172033;
            font-size: 14px;
        }
        .admin-section[data-admin-primary="stats"] .order-row span,
        .admin-section[data-admin-primary="stats"] .order-row em {
            color: #273244;
            font-size: 13px;
            line-height: 1.45;
        }
        .admin-section[data-admin-primary="stats"] .order-row em {
            color: #5d6a7c;
            font-style: normal;
        }
        @media (max-width: 820px) {
            .view-admin-login .admin-login {
                grid-template-columns: 1fr;
                width: min(100% - 24px, 520px);
                gap: 22px;
                padding: 28px 0;
            }
            .view-admin-login .admin-login h1 {
                font-size: clamp(42px, 13vw, 64px);
            }
            .login-subtitle {
                font-size: 15px;
            }
            .login-preview-card {
                display: none;
            }
            .view-admin-login .login-panel {
                padding: 24px;
                border-radius: 26px;
            }
        }
        @media (max-width: 520px) {
            .view-admin-login .login-aura {
                display: none;
            }
            .login-brand span {
                width: 42px;
                height: 42px;
                border-radius: 15px;
            }
            .login-metric-grid {
                grid-template-columns: 1fr;
            }
            .view-admin-login .login-panel {
                padding: 22px 16px 18px;
            }
            .login-panel-head h2 {
                font-size: 26px;
            }
            .login-helper-row {
                display: grid;
            }
        }
        @media (max-width: 820px) {
            body.has-admin-mobile-drawer {
                overflow: hidden;
            }
            .wrap { padding: 0 0 18px; }
            .is-admin .wrap {
                max-width: none;
                padding: 0 0 18px;
            }
            .topbar { position: static; border-radius: 0; align-items: center; flex-direction: row; }
            .admin-topbar {
                position: sticky;
                top: 0;
                z-index: 120;
                min-height: 68px;
                margin: 0;
                padding: max(12px, env(safe-area-inset-top)) 16px 12px;
                border: 0;
                background: rgba(255, 255, 255, .96);
                box-shadow: none;
                backdrop-filter: blur(18px);
            }
            .admin-topbar .brand {
                flex: 1;
                min-width: 0;
                justify-content: center;
                color: #b52320;
                font-size: clamp(23px, 6.8vw, 29px);
                letter-spacing: -.06em;
            }
            .admin-topbar .brand-mark {
                display: none;
            }
            .admin-mobile-top-menu {
                display: grid;
                place-items: center;
                flex: 0 0 38px;
                width: 38px;
                height: 38px;
                padding: 0;
                border: 0;
                border-radius: 10px;
                color: #4b5563;
                background: transparent;
                box-shadow: none;
                font-size: 28px;
                line-height: 1;
            }
            .admin-topbar .nav {
                display: none;
            }
            .admin-topbar .nav a {
                display: none;
            }
            .admin-user-chip {
                flex: 0 0 34px;
                min-width: 34px;
                width: 34px;
                height: 34px;
                justify-content: center;
                overflow: hidden;
                color: transparent;
                font-size: 0;
            }
            .admin-user-chip::before {
                width: 24px;
                height: 24px;
                border: 2px solid #6b7280;
                border-radius: 999px;
                background: transparent;
                box-shadow: none;
            }
            .is-admin {
                background: #f4f6ff;
            }
            .hero { min-height: 520px; background-position: center top; }
            .split, .admin-shell, .admin-login { grid-template-columns: 1fr; }
            .admin-shell {
                padding: 12px;
            }
            .admin-panel .admin-section.panel {
                padding: 14px;
            }
            .admin-page-title {
                display: grid;
            }
            .admin-page-title h2,
            .admin-compact-section .section-title h2 {
                font-size: 22px;
            }
            .admin-summary-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .admin-page-head {
                display: grid;
                gap: 12px;
            }
            .admin-page-head h2,
            .admin-page-head h3 {
                font-size: 22px;
            }
            .admin-page-actions {
                justify-content: flex-start;
            }
            .admin-page-actions .btn {
                flex: 1 1 132px;
                justify-content: center;
            }
            .drama-data-head {
                display: none;
            }
            .drama-data-row {
                grid-template-columns: 1fr;
                gap: 12px;
                padding: 14px;
            }
            .admin-drawer {
                align-items: end;
                justify-items: stretch;
            }
            .admin-drawer-card {
                width: 100%;
                height: min(92vh, 760px);
                height: min(92dvh, 760px);
                border-left: 0;
                border-radius: 16px 16px 0 0;
                box-shadow: 0 -24px 58px rgba(15, 23, 42, .24);
            }
            .admin-drawer-head {
                padding: 16px 16px 13px;
            }
            .admin-drawer-head h3 {
                font-size: 20px;
            }
            .admin-drawer-form > section {
                padding: 15px 16px;
            }
            .admin-drawer-form .form-grid {
                grid-template-columns: 1fr;
            }
            .admin-form-span-2 {
                grid-column: auto;
            }
            .admin-drawer-form footer {
                padding: 12px 16px max(12px, env(safe-area-inset-bottom));
            }
            .admin-drawer-form footer .btn {
                flex: 1 1 120px;
                justify-content: center;
            }
            .admin-filter-bar,
            .order-filter-bar.admin-filter-bar {
                grid-template-columns: 1fr;
            }
            .analytics-filter-bar {
                grid-template-columns: 1fr;
            }
            .analytics-preset-panel .filter-preset-save {
                grid-template-columns: 1fr;
            }
            .works-table .order-row,
            .works-table .order-row-head {
                grid-template-columns: 1fr;
            }
            .works-table .order-row-head {
                display: none;
            }
            .works-row {
                gap: 12px;
                padding: 14px;
            }
            .works-bulk-toolbar {
                display: grid;
            }
            .works-bulk-actions {
                justify-content: stretch;
            }
            .works-bulk-actions .btn {
                flex: 1 1 116px;
                justify-content: center;
            }
            .works-select-cell {
                justify-content: flex-start;
            }
            .works-actions,
            .work-quick-edit-actions {
                justify-content: flex-start;
            }
            .work-quick-edit-grid {
                grid-template-columns: 1fr;
            }
            .admin-section[data-admin-primary="stats"] .repair-log-list > div {
                grid-template-columns: 1fr;
                gap: 4px;
            }
            .user-list-head,
            .rights-log-head {
                display: none;
            }
            .user-edit-row,
            .rights-log-row {
                grid-template-columns: 1fr;
                gap: 12px;
                padding: 14px;
            }
            .user-main-cell,
            .user-field-stack {
                gap: 9px;
            }
            .user-toggle-stack {
                align-content: start;
                grid-template-columns: 1fr;
            }
            .admin-row-actions {
                justify-content: flex-start;
            }
            .admin-row-actions .btn,
            .admin-form-actions .btn {
                flex: 1 1 96px;
                justify-content: center;
            }
            .rights-repair-form .form-grid {
                grid-template-columns: 1fr;
            }
            .admin-form-actions {
                justify-content: stretch;
            }
            .admin-mobile-drawer-backdrop {
                position: fixed;
                inset: 0;
                z-index: 8990;
                display: block;
                background: rgba(17, 24, 39, .62);
            }
            .admin-mobile-drawer-backdrop[hidden] {
                display: none;
            }
            .admin-menu {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                z-index: 9000;
                overflow: auto;
                display: block;
                width: min(72vw, 328px);
                min-height: 100vh;
                min-height: 100dvh;
                padding: max(20px, env(safe-area-inset-top)) 16px max(78px, env(safe-area-inset-bottom));
                border-radius: 0;
                background: #fff;
                box-shadow: 26px 0 60px rgba(17, 24, 39, .22);
                transform: translateX(-104%);
                transition: transform .22s ease;
                backdrop-filter: none;
            }
            .admin-menu::before {
                content: "精秀短剧 v1.0.0";
                position: absolute;
                left: 0;
                right: 0;
                bottom: max(22px, env(safe-area-inset-bottom));
                padding: 18px 12px 0;
                border-top: 1px solid #edf1f7;
                color: #a2acba;
                font-size: 14px;
                font-weight: 800;
                text-align: center;
            }
            .admin-menu.is-open {
                transform: translateX(0);
            }
            body.has-admin-mobile-drawer .admin-menu {
                transform: translateX(0);
            }
            .admin-menu::after,
            .admin-menu > .admin-mobile-menu-toggle {
                display: none;
            }
            .admin-mobile-menu-head {
                display: grid;
                gap: 14px;
                padding: 8px 0 14px;
            }
            .admin-mobile-menu-head span {
                color: #8b95a5;
                font-size: 14px;
                font-weight: 800;
            }
            .admin-mobile-menu-head strong {
                display: block;
                margin: 0;
                color: #b52320;
                font-size: 26px;
                letter-spacing: -.04em;
            }
            .admin-mobile-primary-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                padding-bottom: 18px;
                border-bottom: 1px solid #edf1f7;
            }
            .admin-mobile-primary-grid a {
                display: flex;
                align-items: center;
                gap: 8px;
                min-height: 46px;
                padding: 9px 11px;
                border-radius: 10px;
                color: #4b5563;
                background: #f7f8fc;
                font-size: 16px;
                font-weight: 850;
            }
            .admin-mobile-primary-grid a.is-active {
                color: #fff;
                background: #3165f4;
                box-shadow: 0 10px 22px rgba(49, 101, 244, .22);
            }
            .admin-menu > strong {
                display: flex;
                margin: 20px 6px 8px;
                color: #3165f4;
                font-size: 17px;
            }
            .admin-menu .secondary-menu-group {
                display: none;
                gap: 8px;
            }
            .admin-menu .secondary-menu-group.is-active {
                display: grid;
            }
            .admin-menu a:not(.btn) {
                min-height: 50px;
                padding: 10px 12px;
                border-radius: 10px;
                background: transparent;
                font-size: 16px;
            }
            .admin-menu a.is-active,
            .admin-menu a:not(.btn):hover {
                color: #3165f4;
                background: transparent;
            }
            .admin-menu > .btn {
                display: inline-flex;
                margin-top: 14px;
            }
            .admin-menu {
                width: min(68vw, 292px);
                padding: max(16px, env(safe-area-inset-top)) 12px max(62px, env(safe-area-inset-bottom));
                box-shadow: 18px 0 44px rgba(17, 24, 39, .18);
            }
            .admin-menu::before {
                bottom: max(14px, env(safe-area-inset-bottom));
                padding-top: 12px;
                font-size: 12px;
            }
            .admin-mobile-menu-head {
                gap: 6px;
                padding: 4px 2px 10px;
            }
            .admin-mobile-menu-head span {
                font-size: 12px;
            }
            .admin-mobile-menu-head strong {
                font-size: 21px;
                letter-spacing: 0;
            }
            .admin-mobile-primary-grid {
                gap: 6px;
                padding-bottom: 12px;
            }
            .admin-mobile-primary-grid a {
                min-height: 38px;
                padding: 7px 9px;
                border-radius: 8px;
                font-size: 13px;
            }
            .admin-menu > .admin-menu-section-title {
                margin: 12px 4px 6px;
                color: #3165f4;
                font-size: 14px;
            }
            .admin-menu .secondary-menu-group {
                gap: 4px;
            }
            .admin-menu a:not(.btn) {
                min-height: 40px;
                padding: 8px 9px;
                border-radius: 8px;
                font-size: 14px;
            }
            .admin-menu a.is-active,
            .admin-menu a:not(.btn):hover {
                background: #eef4ff;
            }
            .admin-menu-logout.btn.ghost {
                width: 100%;
                min-height: 38px;
                margin-top: 10px;
                padding: 8px 9px;
                justify-content: flex-start;
                border-radius: 8px;
                color: #4b5563;
                background: #f7f8fc;
                border-color: #edf1f7;
                box-shadow: none;
            }
            .kpi-grid,
            .dashboard-kpi-grid { grid-template-columns: 1fr; }
            .order-row, .order-row-head { grid-template-columns: 1fr; }
            .order-actions { justify-content: flex-start; }
            .episode-row, .banner-strip, .payment-qr-grid { grid-template-columns: 1fr; }
            .nav { width: 100%; }
            .nav a { flex: 1; text-align: center; }
            .admin-topbar .nav,
            .admin-topbar .nav a { display: none !important; }
            .admin-mobile-top-menu { display: grid !important; }
            .admin-user-chip { justify-content: center; }
            .insight-grid,
            .donut-wrap { grid-template-columns: 1fr; }
            .donut-wrap { justify-items: start; }
            .system-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .template-choice-grid { grid-template-columns: 1fr; }
            .template-system-head { align-items: flex-start; flex-direction: column; }
            .template-choice { grid-template-columns: 118px minmax(0, 1fr); }
            .template-preview { min-height: 132px; }
            .design-studio { grid-template-columns: 1fr; }
            .design-page-table { overflow-x: auto; }
            .design-page-row { min-width: 860px; }
            .diy-editor-grid, .design-color-grid { grid-template-columns: 1fr; }
            .module-check-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .design-toolbar-actions input { width: 100%; }
            .data-screen-shell { min-height: 0; padding: 14px; border-radius: 22px; }
            .data-screen-header { grid-template-columns: 1fr; text-align: left; }
            .screen-corner { display: none; }
            .data-screen-clock { justify-items: start; width: fit-content; }
            .data-screen-kpis { grid-template-columns: 1fr; }
            .data-screen-grid { grid-template-columns: 1fr; }
            .screen-map-card { min-height: 360px; }
            .screen-map-stats, .screen-status-grid { grid-template-columns: 1fr; }
            .screen-mini-metrics { grid-template-columns: 1fr; }
            .screen-bar-chart { gap: 6px; }
            .screen-bar-track i { width: 8px; }
            .trend-card-head { flex-direction: column; align-items: flex-start; }
            .trend-card-head .chart-legend { justify-content: flex-start; }
            .kpi-grid,
            .dashboard-kpi-grid { grid-template-columns: 1fr; }
            .payment-route-summary { grid-template-columns: 1fr 1fr; }
            .payment-channel-filter { grid-template-columns: 1fr; }
            .payment-channel-filter-extra { grid-template-columns: 1fr; }
            .payment-channel-filter label { grid-template-columns: 1fr; }
            .payment-channel-toolbar { align-items: flex-start; }
            .payment-channel-toolbar-left,
            .payment-channel-toolbar-right { width: 100%; justify-content: flex-start; }
            .payment-channel-table-wrap { border-radius: 6px; }
            .route-choice-grid,
            .payment-method-choice-grid { grid-template-columns: 1fr 1fr; }
            .payment-route-dialog { padding: max(10px, env(safe-area-inset-top)) 10px max(10px, env(safe-area-inset-bottom)); align-items: start; }
            .payment-route-dialog-card { width: min(100%, calc(100vw - 20px)); max-height: calc(100vh - 20px); max-height: calc(100dvh - 20px); border-radius: 16px; }
            .payment-route-dialog-head { padding: 14px 16px; }
            .payment-route-dialog-form { padding: 14px; }
            .payment-route-dialog-form section { padding: 14px; }
            .route-choice-card { min-height: 76px; padding: 11px 12px 11px 38px; }
            .payment-route-dialog-form footer .muted { width: 100%; margin-right: 0; }
            .payment-route-drawer { justify-items: stretch; padding: 0; }
            .payment-route-drawer-card { width: 100%; max-width: 100%; }
            .payment-route-drawer-head { padding: 18px 16px; }
            .payment-route-key-form { padding: 14px 14px max(18px, env(safe-area-inset-bottom)); }
            .payment-rule-grid { grid-template-columns: 1fr; }
            .channel-action-dialog { padding: max(10px, env(safe-area-inset-top)) 10px max(10px, env(safe-area-inset-bottom)); align-items: start; }
            .channel-action-dialog-card { width: min(100%, calc(100vw - 20px)); border-radius: 16px; }
            .channel-action-dialog-card header,
            .channel-action-dialog-body { padding: 16px; }
            .channel-action-dialog-card footer { padding: 0 16px 16px; }
            .channel-action-dialog-card footer .btn { width: 100%; justify-content: center; }
            .payment-test-dialog { padding: max(10px, env(safe-area-inset-top)) 10px max(10px, env(safe-area-inset-bottom)); align-items: start; overflow: auto; }
            .payment-test-dialog-card { width: min(100%, calc(100vw - 20px)); max-height: calc(100vh - 20px); max-height: calc(100dvh - 20px); border-radius: 16px; }
            .payment-test-dialog-card header,
            .payment-test-form { padding: 16px; }
            .payment-test-form { overflow: auto; }
            .payment-test-form footer .btn { flex: 1; justify-content: center; }
            .order-table { border-radius: 18px; }
            .order-filter-bar { grid-template-columns: 1fr; padding: 12px; }
            .order-sub-stats { grid-template-columns: 1fr; }
            .order-sub-stats div { padding: 14px; border-radius: 18px; }
            .order-mini-table .order-row { grid-template-columns: 1fr; }
            .repair-grid { grid-template-columns: 1fr; }
            .action-log-head { align-items: flex-start; }
            .order-filter-actions, .order-toolbar, .order-pagination { justify-content: flex-start; }
            .filter-preset-save { grid-template-columns: 1fr; }
            .filter-preset-item { grid-template-columns: 1fr; }
            .order-filter-actions .btn, .order-pagination .btn { flex: 1; justify-content: center; }
            .order-row, .order-row-head { grid-template-columns: 1fr; }
            .order-actions { justify-content: flex-start; }
            .order-modal { padding: max(10px, env(safe-area-inset-top)) 10px max(10px, env(safe-area-inset-bottom)); align-items: start; }
            .order-modal-card { width: min(100%, calc(100vw - 20px)); max-height: calc(100vh - 20px); max-height: calc(100dvh - 20px); border-radius: 22px; }
            .order-modal-titlebar { min-height: 58px; padding: 0 14px; }
            .order-modal-titlebar h3 { text-align: left; font-size: 17px; }
            .order-modal-hero { grid-template-columns: 1fr; gap: 14px; padding: 20px; }
            .order-modal-hero > div { align-items: flex-start; }
            .order-modal-hero strong { font-size: 32px; }
            .order-modal-hero b { width: 100%; max-width: 100%; }
            .order-modal-tabs { gap: 16px; overflow-x: auto; padding: 16px 20px 0; }
            .order-modal-tabs button { flex: 0 0 auto; min-height: 46px; }
            .order-modal-body { overflow-x: hidden; padding: 18px; }
            .order-info-card { padding: 16px; }
            .order-info-grid, .gateway-log-grid { grid-template-columns: 1fr; gap: 14px; }
            .order-modal-actions { justify-content: flex-start; padding: 14px 18px 18px; }
            .order-modal-actions .inline-form,
            .order-modal-actions .btn { width: 100%; justify-content: center; }
            .gateway-log-head { flex-direction: column; }
            .gateway-log-meta { justify-content: flex-start; }
            .refund-dialog { padding: max(10px, env(safe-area-inset-top)) 10px max(10px, env(safe-area-inset-bottom)); align-items: start; }
            .refund-dialog-card { width: min(100%, calc(100vw - 20px)); max-height: calc(100vh - 20px); max-height: calc(100dvh - 20px); padding: 22px 16px 16px; border-radius: 22px; }
            .refund-dialog-summary { grid-template-columns: 1fr; }
            .refund-dialog-actions .btn { flex: 1; justify-content: center; }
            .is-client:not(.view-frontend-home) .wrap { padding: 0 10px 72px; }
            .is-client:not(.view-frontend-home) .client-topbar { margin: 0 -10px 12px; align-items: flex-start; }
            .is-client:not(.view-frontend-home) .nav { flex-wrap: nowrap; overflow-x: auto; width: 100%; }
            .is-client:not(.view-frontend-home) .nav a { flex: 0 0 auto; }
            .drama-detail-hero { grid-template-columns: 138px minmax(0, 1fr); }
            .detail-poster-wrap { min-height: 196px; }
            .client-episode-card { grid-template-columns: 1fr; }
            .episode-actions .btn { flex: 1; justify-content: center; }
            .payment-route-options { grid-template-columns: 1fr; }
            .watch-video-frame,
            .watch-video-frame video { min-height: 470px; }
            .payment-qr-grid { grid-template-columns: 1fr; justify-items: stretch; }
            .payment-qr-grid .qr-card,
            .payment-qr-grid .payment-desktop-only { display: none !important; }
            .payment-qr-grid .payment-mobile-only { display: inline-flex !important; }
            .payment-qr-grid h2.payment-mobile-only,
            .payment-qr-grid p.payment-mobile-only { display: block !important; }
            .payment-guide { justify-items: stretch; width: 100%; }
        }
        @media (max-width: 430px) {
            .drama-detail-hero { grid-template-columns: 128px minmax(0, 1fr); gap: 12px; }
            .detail-poster-wrap { min-height: 184px; border-radius: 18px; }
            .detail-copy { gap: 9px; }
            .detail-copy h1 { font-size: 24px; }
            .detail-copy p { -webkit-line-clamp: 2; }
            .client-stat-grid { gap: 6px; }
            .client-stat-grid span { padding: 9px 7px; }
            .mini-benefit-strip { gap: 8px; }
            .watch-video-frame,
            .watch-video-frame video { min-height: 430px; }
            .watch-lock-state { padding: 22px 18px; }
            .watch-lock-state h1 { font-size: 28px; }
            .watch-episode-strip { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .payment-status-hero h1 { max-width: 68%; font-size: 32px; }
            .client-info-list div { grid-template-columns: 74px minmax(0, 1fr); }
            .account-profile-card { grid-template-columns: 52px minmax(0, 1fr) auto; padding: 14px; }
            .account-avatar { width: 52px; height: 52px; border-radius: 20px; }
            .account-profile-card h1 { font-size: 22px; }
            .account-center-hero { padding: 16px; border-radius: 16px; }
            .account-center-hero-main { grid-template-columns: 54px minmax(0, 1fr); }
            .account-center-hero h1 { font-size: 24px; }
            .account-balance-card { grid-template-columns: 1fr; }
            .account-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .account-shortcut-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 390px) {
            .is-client:not(.view-frontend-home) .brand { font-size: 18px; }
            .is-client:not(.view-frontend-home) .nav a { padding: 8px 9px; font-size: 12px; }
            .drama-detail-hero { grid-template-columns: 118px minmax(0, 1fr); }
            .detail-poster-wrap { min-height: 172px; }
            .detail-copy h1 { font-size: 22px; }
            .client-action-row .btn,
            .client-action-row button { flex-basis: 100%; }
            .mini-benefit-strip { grid-template-columns: 1fr; }
            .payment-route-summary { grid-template-columns: 1fr; }
            .route-choice-grid,
            .payment-method-choice-grid { grid-template-columns: 1fr; }
            .payment-channel-toolbar .btn,
            .channel-status-btn { flex: 1 1 auto; justify-content: center; }
            .payment-route-dialog-form footer .btn { flex: 1; justify-content: center; }
            .watch-video-frame,
            .watch-video-frame video { min-height: 400px; }
            .payment-status-orb { width: 56px; height: 56px; border-radius: 20px; }
            .payment-status-hero h1 { font-size: 30px; }
            .account-profile-card { grid-template-columns: 48px minmax(0, 1fr); }
            .account-profile-card .btn { grid-column: 1 / -1; width: 100%; }
            .account-center-hero-actions { grid-template-columns: 1fr; }
            .account-list-row { grid-template-columns: 1fr; align-items: start; }
            .account-list-row b { width: fit-content; }
        }
        .view-frontend-duanju.is-client,
        .view-frontend-juchang.is-client,
        .view-frontend-yulan.is-client,
        .view-account-zhuiju.is-client,
        .view-account-wode.is-client,
        .view-account-denglu.is-client,
        .view-account-huiyuan.is-client {
            min-height: 100vh;
            color: #251716;
            background:
                radial-gradient(circle at 50% -8%, rgba(255, 116, 93, .30), transparent 34%),
                radial-gradient(circle at 95% 10%, rgba(255, 196, 88, .22), transparent 25%),
                linear-gradient(180deg, #fff4ec 0%, #fffaf7 42%, #f6f7fb 100%);
        }
        .view-frontend-yulan.is-client {
            background: #090a0d;
        }
        .view-frontend-duanju.is-client::before,
        .view-frontend-juchang.is-client::before,
        .view-frontend-yulan.is-client::before,
        .view-account-zhuiju.is-client::before,
        .view-account-wode.is-client::before,
        .view-account-denglu.is-client::before,
        .view-account-huiyuan.is-client::before {
            content: none;
        }
        .view-frontend-duanju .wrap,
        .view-frontend-juchang .wrap,
        .view-frontend-yulan .wrap,
        .view-account-zhuiju .wrap,
        .view-account-wode .wrap,
        .view-account-denglu .wrap,
        .view-account-huiyuan .wrap {
            max-width: 430px;
            min-height: 100vh;
            padding: 0 12px 98px;
        }
        .view-frontend-yulan .wrap {
            max-width: 460px;
            padding: 0 0 96px;
        }
        .view-frontend-duanju .client-topbar,
        .view-frontend-juchang .client-topbar,
        .view-frontend-yulan .client-topbar,
        .view-account-zhuiju .client-topbar,
        .view-account-wode .client-topbar,
        .view-account-denglu .client-topbar,
        .view-account-huiyuan .client-topbar {
            display: none;
        }
        .duanju-app {
            display: grid;
            gap: 14px;
            padding: 18px 0 0;
        }
        .duanju-head,
        .duanju-page-title {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 2px 0;
        }
        .duanju-head span,
        .duanju-page-title span,
        .login-hero-card span,
        .vip-hero span {
            display: block;
            color: #c56852;
            font-size: 12px;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: .12em;
        }
        .duanju-head h1,
        .duanju-page-title h1 {
            margin: 4px 0 0;
            color: #17110f;
            font-size: 31px;
            line-height: 1;
            letter-spacing: -.07em;
        }
        .duanju-head a,
        .duanju-page-title a,
        .duanju-section header a,
        .duanju-section header span {
            color: #b65d4e;
            font-size: 13px;
            font-weight: 950;
        }
        .duanju-page-title {
            display: grid;
            align-items: start;
        }
        .duanju-page-title p {
            margin: 2px 0 0;
            color: #7b6259;
            line-height: 1.6;
        }
        .duanju-search {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            align-items: center;
            gap: 8px;
            min-height: 46px;
            padding: 5px 6px 5px 15px;
            border: 1px solid rgba(230, 105, 81, .24);
            border-radius: 999px;
            background: rgba(255, 255, 255, .74);
            box-shadow: 0 12px 26px rgba(185, 99, 72, .10);
        }
        .duanju-search input {
            min-width: 0;
            padding: 0;
            border: 0;
            color: #2b1a18;
            background: transparent;
            box-shadow: none;
        }
        .duanju-search button {
            min-height: 36px;
            padding: 0 18px;
            color: #fff;
            background: linear-gradient(135deg, #ff6c55, #ff9c5f);
            box-shadow: none;
        }
        .duanju-hero {
            position: relative;
            overflow: hidden;
            min-height: 232px;
            border-radius: 28px;
            background: #311d19;
            box-shadow: 0 24px 46px rgba(120, 50, 34, .20);
        }
        .duanju-hero img {
            width: 100%;
            height: 232px;
            object-fit: cover;
        }
        .duanju-hero-shade {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(31, 11, 7, .84), rgba(31, 11, 7, .08) 68%),
                linear-gradient(180deg, transparent 42%, rgba(31, 11, 7, .62));
        }
        .duanju-hero-copy {
            position: absolute;
            inset: auto 18px 20px;
            display: grid;
            gap: 8px;
            max-width: 76%;
            color: #fff;
        }
        .duanju-hero-copy em,
        .duanju-hero-copy small,
        .duanju-poster-card em,
        .duanju-grid-card em,
        .duanju-list-card em,
        .duanju-list-card small {
            overflow: hidden;
            color: rgba(255, 255, 255, .76);
            font-style: normal;
            text-overflow: ellipsis;
        }
        .duanju-hero-copy strong {
            font-size: 28px;
            line-height: 1.04;
            letter-spacing: -.06em;
        }
        .duanju-hero-copy b,
        .duanju-list-card b {
            width: fit-content;
            padding: 8px 14px;
            border-radius: 999px;
            color: #331510;
            background: linear-gradient(135deg, #ffd66b, #ff8b55);
            font-size: 13px;
            font-weight: 950;
        }
        .duanju-wallet,
        .mine-stats,
        .vip-benefits {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .duanju-wallet a,
        .mine-stats div,
        .vip-benefits div,
        .mine-toggle-card,
        .mine-about,
        .login-form-card,
        .duanju-empty {
            min-width: 0;
            padding: 16px;
            border: 1px solid rgba(236, 222, 214, .88);
            border-radius: 22px;
            background: rgba(255, 255, 255, .84);
            box-shadow: 0 12px 28px rgba(114, 71, 52, .08);
        }
        .duanju-wallet strong,
        .mine-stats strong,
        .vip-benefits strong {
            display: block;
            color: #241613;
            font-size: 18px;
            letter-spacing: -.04em;
        }
        .duanju-wallet span,
        .mine-stats span,
        .vip-benefits span,
        .duanju-empty p {
            display: block;
            margin-top: 5px;
            color: #8a7168;
            font-size: 12px;
            line-height: 1.5;
        }
        .duanju-section {
            display: grid;
            gap: 12px;
        }
        .duanju-section header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .duanju-section h2 {
            margin: 0;
            color: #1f1412;
            font-size: 22px;
            letter-spacing: -.05em;
        }
        .duanju-scroll-row {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding: 0 2px 8px;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
        }
        .duanju-scroll-row::-webkit-scrollbar {
            display: none;
        }
        .duanju-poster-card {
            flex: 0 0 132px;
            display: grid;
            gap: 8px;
            min-width: 0;
            scroll-snap-align: start;
        }
        .duanju-poster,
        .duanju-grid-card span {
            position: relative;
            overflow: hidden;
            display: block;
            border-radius: 18px;
            background: #f2e5dc;
        }
        .duanju-poster img,
        .duanju-grid-card img {
            width: 100%;
            aspect-ratio: 3 / 4;
            object-fit: cover;
        }
        .duanju-poster b,
        .duanju-grid-card b {
            position: absolute;
            left: 8px;
            top: 8px;
            padding: 4px 8px;
            border-radius: 999px;
            color: #fff;
            background: rgba(20, 10, 8, .62);
            font-size: 11px;
            font-weight: 950;
        }
        .duanju-poster-card strong,
        .duanju-grid-card strong {
            overflow: hidden;
            color: #261915;
            font-size: 15px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .duanju-poster-card em,
        .duanju-grid-card em {
            color: #9b8278;
            font-size: 12px;
            white-space: nowrap;
        }
        .duanju-list,
        .duanju-history,
        .mine-order-list {
            display: grid;
            gap: 10px;
        }
        .duanju-list-card {
            display: grid;
            grid-template-columns: 78px minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            min-width: 0;
            padding: 10px;
            border-radius: 22px;
            background: rgba(255, 255, 255, .86);
            box-shadow: 0 12px 26px rgba(114, 71, 52, .07);
        }
        .duanju-list-card img {
            width: 78px;
            aspect-ratio: 3 / 4;
            border-radius: 16px;
            object-fit: cover;
            background: #f0e5dd;
        }
        .duanju-list-card span {
            display: grid;
            gap: 5px;
            min-width: 0;
        }
        .duanju-list-card strong {
            overflow: hidden;
            color: #231512;
            font-size: 17px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .duanju-list-card em,
        .duanju-list-card small {
            color: #91786f;
            font-size: 12px;
            white-space: nowrap;
        }
        .duanju-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 13px;
        }
        .duanju-grid-card {
            display: grid;
            gap: 7px;
            min-width: 0;
        }
        .duanju-category-strip {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 2px 0 8px;
            scrollbar-width: none;
        }
        .duanju-category-strip::-webkit-scrollbar {
            display: none;
        }
        .duanju-category-strip a {
            flex: 0 0 auto;
            padding: 9px 15px;
            border-radius: 999px;
            color: #805f55;
            background: rgba(255, 255, 255, .72);
            font-size: 13px;
            font-weight: 950;
        }
        .duanju-category-strip a.is-active {
            color: #fff;
            background: linear-gradient(135deg, #ff6c55, #ff9f62);
            box-shadow: 0 10px 20px rgba(255, 108, 85, .20);
        }
        .duanju-empty {
            display: grid;
            justify-items: start;
            gap: 8px;
        }
        .duanju-empty.compact {
            padding: 14px;
            box-shadow: none;
        }
        .duanju-empty strong {
            color: #281612;
            font-size: 18px;
        }
        .duanju-history a,
        .mine-order-list div {
            display: grid;
            gap: 5px;
            padding: 13px 14px;
            border-radius: 18px;
            background: rgba(255, 255, 255, .78);
        }
        .duanju-history strong,
        .mine-order-list strong {
            color: #241613;
        }
        .duanju-history span,
        .mine-order-list span,
        .mine-order-list em {
            overflow: hidden;
            color: #8b746d;
            font-style: normal;
            font-size: 12px;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .duanju-tabbar {
            position: fixed;
            left: 50%;
            bottom: 0;
            z-index: 70;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            width: min(100%, 430px);
            padding: 9px 10px calc(9px + env(safe-area-inset-bottom));
            border-top: 1px solid rgba(230, 218, 211, .86);
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 -12px 30px rgba(60, 32, 24, .10);
            transform: translateX(-50%);
        }
        .duanju-tabbar a {
            display: grid;
            justify-items: center;
            gap: 4px;
            color: #806961;
            font-size: 12px;
            font-weight: 900;
        }
        .duanju-tabbar .ui-icon,
        .duanju-tabbar .ui-icon svg {
            width: 23px;
            height: 23px;
        }
        .duanju-tabbar a.is-active {
            color: #ff664f;
        }
        .duanju-player {
            display: grid;
            gap: 12px;
            color: #fff;
            background: #090a0d;
        }
        .player-stage {
            position: relative;
            overflow: hidden;
            min-height: 640px;
            background: #050507;
        }
        .player-topbar {
            position: absolute;
            inset: 0 0 auto;
            z-index: 3;
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr) auto;
            align-items: center;
            gap: 10px;
            padding: max(14px, env(safe-area-inset-top)) 14px 12px;
            background: linear-gradient(180deg, rgba(0, 0, 0, .58), transparent);
        }
        .player-topbar span {
            overflow: hidden;
            font-weight: 950;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .player-topbar a,
        .player-topbar button,
        .player-controls a,
        .player-controls button {
            min-height: 38px;
            border: 0;
            border-radius: 999px;
            color: #fff;
            background: rgba(255, 255, 255, .14);
            box-shadow: none;
        }
        .player-video,
        .player-video video,
        .player-video img {
            width: 100%;
            height: 640px;
            object-fit: cover;
            background: #000;
        }
        .player-video {
            position: relative;
        }
        .player-lock {
            position: absolute;
            inset: 0;
            display: grid;
            place-content: center;
            justify-items: center;
            gap: 10px;
            padding: 28px;
            text-align: center;
            background:
                radial-gradient(circle at 50% 45%, rgba(255, 108, 85, .28), transparent 28%),
                rgba(0, 0, 0, .56);
            backdrop-filter: blur(10px);
        }
        .player-lock span {
            padding: 6px 12px;
            border-radius: 999px;
            color: #ffd7bd;
            background: rgba(255, 255, 255, .12);
            font-weight: 950;
        }
        .player-lock h1 {
            margin: 0;
            color: #fff;
            font-size: 34px;
            letter-spacing: -.07em;
        }
        .player-lock p {
            max-width: 280px;
            margin: 0 0 8px;
            color: rgba(255, 255, 255, .72);
            line-height: 1.7;
        }
        .player-controls {
            position: absolute;
            left: 12px;
            right: 12px;
            bottom: 16px;
            z-index: 3;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 10px;
            align-items: center;
        }
        .player-controls a,
        .player-controls button {
            padding: 0 12px;
            font-weight: 950;
            text-align: center;
        }
        .player-controls .is-disabled {
            opacity: .38;
            pointer-events: none;
        }
        .player-info-card,
        .player-mini-episodes {
            margin: 0 12px;
            padding: 16px;
            border: 1px solid rgba(255, 255, 255, .10);
            border-radius: 24px;
            background: #15171d;
        }
        .player-info-card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: start;
        }
        .player-info-card h1 {
            margin: 5px 0;
            color: #fff;
            font-size: 24px;
            letter-spacing: -.06em;
        }
        .player-info-card p,
        .player-info-card span,
        .player-balance span {
            margin: 0;
            color: rgba(255, 255, 255, .62);
            line-height: 1.65;
        }
        .player-balance {
            display: grid;
            justify-items: end;
            gap: 3px;
        }
        .player-balance strong {
            color: #ffd36a;
            white-space: nowrap;
        }
        .player-mini-episodes header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .player-mini-episodes h2 {
            margin: 0;
            color: #fff;
        }
        .player-mini-episodes header button {
            min-height: 32px;
            padding: 0 10px;
            color: #ffb49e;
            background: rgba(255, 108, 85, .12);
            box-shadow: none;
        }
        .player-mini-episodes > div,
        .player-episode-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 8px;
        }
        .player-mini-episodes a,
        .player-episode-grid a {
            display: grid;
            place-items: center;
            min-height: 40px;
            border-radius: 13px;
            color: #d9dce6;
            background: rgba(255, 255, 255, .08);
            font-size: 13px;
            font-weight: 950;
        }
        .player-mini-episodes a.is-active,
        .player-episode-grid a.is-active {
            color: #23120e;
            background: #ffd36a;
        }
        .player-mini-episodes a.is-lock,
        .player-episode-grid a.is-lock {
            color: #ffb49e;
            background: rgba(255, 108, 85, .15);
        }
        .player-sheet {
            position: fixed;
            inset: 0;
            z-index: 120;
            display: grid;
            align-items: end;
            justify-items: center;
        }
        .player-sheet[hidden] {
            display: none;
        }
        .player-sheet-backdrop {
            position: absolute;
            inset: 0;
            width: 100%;
            min-height: 0;
            border: 0;
            border-radius: 0;
            background: rgba(0, 0, 0, .55);
            box-shadow: none;
        }
        .player-sheet-card {
            position: relative;
            z-index: 1;
            width: min(100%, 430px);
            max-height: min(82vh, 720px);
            overflow: auto;
            padding: 18px 16px calc(18px + env(safe-area-inset-bottom));
            border-radius: 28px 28px 0 0;
            color: #211511;
            background: #fff8f2;
            box-shadow: 0 -24px 60px rgba(0, 0, 0, .28);
        }
        .player-sheet-card header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .player-sheet-card header h2 {
            margin: 3px 0 0;
            color: #211511;
            font-size: 22px;
        }
        .player-sheet-card header span {
            color: #bc6751;
            font-size: 12px;
            font-weight: 950;
            letter-spacing: .1em;
        }
        .player-sheet-card header button {
            width: 38px;
            height: 38px;
            min-height: 0;
            padding: 0;
            color: #7b6259;
            background: #fff;
            box-shadow: none;
        }
        .player-episode-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }
        .player-episode-grid a {
            gap: 2px;
            min-height: 58px;
            color: #5c4741;
            background: #fff;
        }
        .player-episode-grid span {
            font-size: 11px;
            color: #9c8178;
        }
        .buy-tabs,
        .buy-route-list {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }
        .buy-tabs button,
        .buy-route-list label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 40px;
            min-width: 0;
            border-radius: 999px;
            color: #6e574f;
            background: #fff;
            box-shadow: none;
            font-size: 13px;
        }
        .buy-route-list .payment-route-method {
            justify-content: center;
            gap: 6px;
        }
        .buy-route-list .payment-method-icon {
            width: 22px;
            height: 22px;
            border-radius: 7px;
            font-size: 13px;
            box-shadow: none;
        }
        .buy-tabs button.is-active,
        .buy-route-list label.is-active {
            color: #fff;
            background: linear-gradient(135deg, #ff6c55, #ff9f62);
        }
        .buy-route-list input,
        .buy-plan input {
            display: none;
        }
        .buy-plan-list {
            display: grid;
            gap: 10px;
        }
        .buy-plan {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 14px;
            border: 1px solid #f2e1d7;
            border-radius: 20px;
            background: #fff;
        }
        .buy-plan.is-active {
            border-color: rgba(255, 108, 85, .56);
            box-shadow: 0 12px 26px rgba(255, 108, 85, .12);
        }
        .buy-plan span {
            display: grid;
            gap: 5px;
        }
        .buy-plan strong {
            color: #221511;
            font-size: 16px;
        }
        .buy-plan em,
        .buy-message {
            color: #8a7168;
            font-style: normal;
            font-size: 12px;
            line-height: 1.55;
        }
        .buy-plan b {
            color: #ff624b;
            font-size: 18px;
        }
        .buy-submit,
        .vip-pay-button {
            width: 100%;
            min-height: 48px;
        }
        .mine-hero,
        .vip-hero,
        .login-hero-card {
            display: grid;
            gap: 8px;
            padding: 22px 18px;
            border-radius: 28px;
            color: #fff;
            background:
                radial-gradient(circle at 85% 12%, rgba(255, 214, 106, .34), transparent 32%),
                linear-gradient(135deg, #2a1712, #a64737 54%, #ff8e56);
            box-shadow: 0 22px 46px rgba(128, 54, 38, .18);
        }
        .mine-hero {
            grid-template-columns: 62px minmax(0, 1fr) auto;
            align-items: center;
        }
        .mine-avatar {
            display: grid;
            place-items: center;
            width: 62px;
            height: 62px;
            border-radius: 24px;
            color: #3a1810;
            background: #ffdf8a;
            font-size: 28px;
            font-weight: 950;
        }
        .mine-hero h1,
        .vip-hero h1,
        .login-hero-card h1 {
            margin: 0;
            color: #fff;
            font-size: 29px;
            letter-spacing: -.07em;
        }
        .mine-hero p,
        .mine-hero span,
        .vip-hero p,
        .login-hero-card p {
            margin: 0;
            color: rgba(255, 255, 255, .78);
            line-height: 1.55;
        }
        .mine-hero a,
        .mine-vip-card a {
            padding: 9px 13px;
            border-radius: 999px;
            color: #341811;
            background: #ffdf8a;
            font-size: 13px;
            font-weight: 950;
            white-space: nowrap;
        }
        .mine-vip-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px;
            border-radius: 22px;
            background: #17120f;
            color: #ffdf8a;
        }
        .mine-vip-card span {
            color: rgba(255, 223, 138, .66);
            font-size: 12px;
            font-weight: 900;
        }
        .mine-vip-card strong {
            display: block;
            margin-top: 4px;
            color: #fff4d3;
            font-size: 19px;
        }
        .mine-toggle-card,
        .mine-about {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .mine-toggle-card strong {
            color: #241613;
        }
        .mine-toggle-card span,
        .mine-about span {
            display: block;
            margin-top: 4px;
            color: #8b746d;
            font-size: 12px;
        }
        .mine-switch {
            position: relative;
            display: block;
            width: 54px;
            height: 32px;
        }
        .mine-switch input {
            display: none;
        }
        .mine-switch i {
            position: absolute;
            inset: 0;
            border-radius: 999px;
            background: #e7d9d0;
            transition: background .2s ease;
        }
        .mine-switch i::after {
            content: "";
            position: absolute;
            left: 4px;
            top: 4px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #fff;
            transition: transform .2s ease;
        }
        .mine-switch input:checked + i {
            background: #ff7459;
        }
        .mine-switch input:checked + i::after {
            transform: translateX(22px);
        }
        .login-app,
        .vip-app {
            padding-top: 24px;
        }
        .login-form-card {
            display: grid;
            gap: 14px;
        }
        .login-code-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 112px;
            gap: 8px;
        }
        .login-code-row button {
            min-height: 44px;
            padding: 0 10px;
            color: #fff;
            background: #251716;
            box-shadow: none;
        }
        .login-message {
            margin: 0;
            color: #8a7168;
            line-height: 1.6;
        }
        .vip-plan-grid {
            display: grid;
            gap: 12px;
        }
        .vip-plan-grid label {
            display: grid;
            gap: 6px;
            padding: 18px;
            border: 1px solid #f0dfd5;
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 14px 28px rgba(120, 66, 45, .08);
        }
        .vip-plan-grid label.is-active {
            border-color: rgba(255, 108, 85, .58);
            background: linear-gradient(135deg, #fff, #fff3ea);
        }
        .vip-plan-grid input {
            display: none;
        }
        .vip-plan-grid span {
            width: fit-content;
            padding: 5px 9px;
            border-radius: 999px;
            color: #ff674f;
            background: #fff0e9;
            font-size: 12px;
            font-weight: 950;
        }
        .vip-plan-grid strong {
            color: #221511;
            font-size: 22px;
        }
        .vip-plan-grid em {
            color: #8a7168;
            font-style: normal;
        }
        .vip-plan-grid b {
            color: #ff624b;
            font-size: 26px;
        }
        .vip-route-list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-bottom: 0;
        }
        .coin-package-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 9px;
        }
        .coin-package-grid button {
            display: grid;
            gap: 5px;
            min-height: 82px;
            padding: 12px 8px;
            border: 1px solid #f0dfd5;
            border-radius: 18px;
            color: #251716;
            background: #fff;
            box-shadow: 0 10px 20px rgba(120, 66, 45, .06);
        }
        .coin-package-grid button.is-active {
            border-color: rgba(255, 108, 85, .58);
            background: #fff1ea;
        }
        .coin-package-grid strong {
            font-size: 17px;
        }
        .coin-package-grid span {
            color: #8a7168;
            font-size: 11px;
            line-height: 1.45;
        }
        @media (max-width: 430px) {
            .duanju-list-card {
                grid-template-columns: 72px minmax(0, 1fr) auto;
            }
            .duanju-list-card img {
                width: 72px;
            }
            .player-stage,
            .player-video,
            .player-video video,
            .player-video img {
                height: 610px;
                min-height: 610px;
            }
            .player-episode-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        @media (max-width: 390px) {
            .view-frontend-duanju .wrap,
            .view-frontend-juchang .wrap,
            .view-account-zhuiju .wrap,
            .view-account-wode .wrap,
            .view-account-denglu .wrap,
            .view-account-huiyuan .wrap {
                padding-left: 10px;
                padding-right: 10px;
            }
            .duanju-wallet,
            .mine-stats,
            .vip-benefits {
                gap: 8px;
            }
            .duanju-list-card {
                grid-template-columns: 66px minmax(0, 1fr);
            }
            .duanju-list-card img {
                width: 66px;
            }
            .duanju-list-card b {
                grid-column: 1 / -1;
            }
            .duanju-grid {
                gap: 10px;
            }
            .player-stage,
            .player-video,
            .player-video video,
            .player-video img {
                height: 560px;
                min-height: 560px;
            }
            .player-info-card {
                grid-template-columns: 1fr;
            }
            .player-balance {
                justify-items: start;
            }
            .player-mini-episodes > div {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
            .buy-tabs {
                grid-template-columns: 1fr;
            }
            .mine-hero {
                grid-template-columns: 54px minmax(0, 1fr);
            }
            .mine-hero a {
                grid-column: 1 / -1;
                width: fit-content;
            }
            .mine-avatar {
                width: 54px;
                height: 54px;
                border-radius: 20px;
            }
            .login-code-row {
                grid-template-columns: 1fr;
            }
            .coin-package-grid {
                grid-template-columns: 1fr;
            }
            .novel-rank-hero {
                grid-template-columns: 1fr;
            }
            .novel-hero-book {
                grid-template-columns: 82px minmax(0, 1fr);
                align-items: center;
            }
            .novel-rank-row {
                grid-template-columns: 28px 52px minmax(0, 1fr);
            }
            .novel-rank-row i {
                grid-column: 2 / -1;
                width: fit-content;
            }
        }
    </style>
</head>
<body class="<?= htmlspecialchars(implode(' ', $bodyClasses)) ?>" data-csrf-token="<?= htmlspecialchars($csrfToken) ?>">
<div class="page-loader is-visible" data-page-loader aria-live="polite" aria-busy="true" role="status">
    <div class="page-loader-card">
        <div class="page-loader-orbit" aria-hidden="true"></div>
        <div class="page-loader-brand">
            <strong><?= htmlspecialchars($siteName) ?></strong>
            <span data-page-loader-text>页面加载中...</span>
        </div>
    </div>
</div>
<div class="wrap">
    <header class="topbar <?= $isAdminView ? 'admin-topbar' : 'client-topbar' ?>">
        <?php if ($isAdminView): ?>
            <button class="admin-mobile-top-menu" type="button" data-admin-menu-toggle aria-label="打开后台菜单" aria-expanded="false">☰</button>
        <?php endif; ?>
        <a class="brand" href="<?= $isAdminView ? '/jxdjadmin' : '/' ?>"><span><?= htmlspecialchars($siteName) ?></span></a>
        <nav class="nav">
            <?php if ($isAdminView): ?>
                <a class="is-active" href="/jxdjadmin#overview" data-admin-primary="dashboard" data-admin-target="overview"><?= jx_icon('dashboard') ?><span>工作台</span></a>
                <a href="/jxdjadmin#works-list" data-admin-primary="works" data-admin-target="works-list"><?= jx_icon('drama') ?><span>作品管理</span></a>
                <a href="/jxdjadmin#orders" data-admin-primary="orders" data-admin-target="orders"><?= jx_icon('order') ?><span>订单中心</span></a>
                <a href="/jxdjadmin#payment" data-admin-primary="finance" data-admin-target="payment"><?= jx_icon('payment') ?><span>支付财务</span></a>
                <a href="/jxdjadmin#users" data-admin-primary="users" data-admin-target="users"><?= jx_icon('user') ?><span>用户权益</span></a>
                <a href="/jxdjadmin#banner" data-admin-primary="operation" data-admin-target="banner"><?= jx_icon('banner') ?><span>运营推荐</span></a>
                <a href="/jxdjadmin#homepage-template" data-admin-primary="design" data-admin-target="homepage-template"><?= jx_icon('design') ?><span>设计</span></a>
                <a href="/jxdjadmin#play-stats" data-admin-primary="stats" data-admin-target="play-stats"><?= jx_icon('stats') ?><span>数据统计</span></a>
                <a href="/jxdjadmin#settings" data-admin-primary="settings" data-admin-target="settings"><?= jx_icon('setting') ?><span>系统设置</span></a>
            <?php else: ?>
                <a href="/?route=home"><?= jx_icon('home') ?><span>首页</span></a>
                <a href="/?route=center"><?= jx_icon('user') ?><span>个人中心</span></a>
                <a href="/?route=bind"><?= jx_icon('account') ?><span>绑定账号</span></a>
            <?php endif; ?>
        </nav>
        <?php if ($isAdminView): ?>
            <div class="admin-user-chip"><?= htmlspecialchars($adminName) ?></div>
        <?php endif; ?>
    </header>
