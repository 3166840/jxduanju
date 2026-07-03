<?php
$title = '会员中心 - 精秀短剧';
$plans = array_values((array) ($vip_plans ?? []));
$paymentRoutes = array_values((array) ($payment_routes ?? []));
$appKey = (string) ($app_key ?? 'default');
$defaultRouteId = '';
foreach ($paymentRoutes as $route) {
    if (!empty($route['is_default'])) {
        $defaultRouteId = (string) ($route['id'] ?? '');
        break;
    }
}
$defaultRouteId = $defaultRouteId ?: (string) ($paymentRoutes[0]['id'] ?? '');
?>
<main class="duanju-app vip-app">
    <section class="vip-hero">
        <span>JX VIP</span>
        <h1>会员畅看短剧</h1>
        <p><?= !empty($user['membership']) ? '你的会员有效期至 ' . htmlspecialchars((string) ($user['membership_expires_at'] ?? '')) : '开通后可畅看会员权益短剧，支付成功自动发放。' ?></p>
    </section>

    <section class="vip-plan-grid">
        <?php foreach ($plans as $index => $plan): ?>
            <label class="<?= $index === 0 ? 'is-active' : '' ?>">
                <input type="radio" name="vip_plan" value="<?= htmlspecialchars((string) ($plan['code'] ?? 'vip_month')) ?>" <?= $index === 0 ? 'checked' : '' ?>>
                <span><?= htmlspecialchars((string) ($plan['badge'] ?? '畅看')) ?></span>
                <strong><?= htmlspecialchars((string) ($plan['name'] ?? 'VIP 套餐')) ?></strong>
                <em><?= number_format((int) ($plan['days'] ?? 30)) ?> 天权益</em>
                <b>￥<?= number_format((float) ($plan['price'] ?? 0), 2) ?></b>
            </label>
        <?php endforeach; ?>
    </section>

    <section class="vip-benefits">
        <div><strong>付费剧集畅看</strong><span>会员有效期内自动识别权益</span></div>
        <div><strong>支付自动发放</strong><span>回调或主动查询成功即到账</span></div>
        <div><strong>追剧资产保留</strong><span>登录后保留订单、K币和历史</span></div>
    </section>

    <?php if (!empty($paymentRoutes)): ?>
        <section class="buy-route-list vip-route-list">
            <?php foreach ($paymentRoutes as $index => $route): ?>
                <?php $routeId = (string) ($route['id'] ?? ''); ?>
                <label class="<?= $routeId === $defaultRouteId || ($defaultRouteId === '' && $index === 0) ? 'is-active' : '' ?>">
                    <input type="radio" name="payment_route_id" value="<?= htmlspecialchars($routeId) ?>" <?= $routeId === $defaultRouteId || ($defaultRouteId === '' && $index === 0) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars((string) ($route['payment_method_name'] ?? '支付宝')) ?></span>
                </label>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <p class="buy-message" data-vip-message>余额足够时优先扣 K币，不足则创建支付订单。</p>
    <button class="btn primary vip-pay-button" type="button" data-vip-submit>立即开通</button>
</main>
<script>
(() => {
    document.querySelectorAll('input[name="vip_plan"]').forEach((input) => {
        input.addEventListener('change', () => {
            document.querySelectorAll('.vip-plan-grid label').forEach((item) => item.classList.toggle('is-active', item.contains(input)));
        });
    });
    document.querySelectorAll('input[name="payment_route_id"]').forEach((input) => {
        input.addEventListener('change', () => {
            document.querySelectorAll('.vip-route-list label').forEach((item) => item.classList.toggle('is-active', item.contains(input)));
        });
    });
    document.querySelector('[data-vip-submit]')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        const message = document.querySelector('[data-vip-message]');
        button.disabled = true;
        if (message) message.textContent = '正在创建会员订单...';
        try {
            const body = new URLSearchParams({
                drama_id: '0',
                episode_id: '0',
                plan: document.querySelector('input[name="vip_plan"]:checked')?.value || 'vip_month',
                payment_route_id: document.querySelector('input[name="payment_route_id"]:checked')?.value || '',
                app_key: <?= json_encode($appKey, JSON_UNESCAPED_UNICODE) ?>
            });
            const response = await fetch('/?route=api-player-order', { method: 'POST', body, headers: { 'Accept': 'application/json' } });
            const result = await response.json();
            if (result.unlocked) {
                if (message) message.textContent = result.message || 'VIP 已开通。';
                window.setTimeout(() => window.location.href = '/wode', 700);
                return;
            }
            if (result.cashier_url) {
                window.location.href = result.cashier_url;
                return;
            }
            if (message) message.textContent = result.message || '订单创建失败。';
        } catch (error) {
            if (message) message.textContent = '网络异常，请稍后重试。';
        } finally {
            button.disabled = false;
        }
    });
})();
</script>
