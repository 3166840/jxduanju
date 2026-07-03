<?php
$title = ($episode['title'] ?? '播放页') . ' - ' . ($drama['title'] ?? '短剧');
$dramaId = (int) ($drama['id'] ?? 1);
$episodeId = (int) ($episode['id'] ?? 0);
$episodes = array_values((array) ($drama['episodes'] ?? []));
$currentIndex = 0;
foreach ($episodes as $index => $item) {
    if ((int) ($item['id'] ?? 0) === $episodeId) {
        $currentIndex = $index + 1;
        break;
    }
}
$paymentRoutes = array_values((array) ($payment_routes ?? []));
$defaultPaymentRouteId = '';
foreach ($paymentRoutes as $route) {
    if (!empty($route['is_default'])) {
        $defaultPaymentRouteId = (string) ($route['id'] ?? '');
        break;
    }
}
$defaultPaymentRouteId = $defaultPaymentRouteId ?: (string) ($paymentRoutes[0]['id'] ?? '');
$routeParam = static fn (string $routeId): string => $routeId !== '' ? '&payment_route_id=' . rawurlencode($routeId) : '';
?>
<main class="client-screen watch-screen">
    <section class="watch-player-card">
        <div class="watch-titlebar">
            <a href="/?route=drama&id=<?= $dramaId ?>">‹ 返回详情</a>
            <span><?= $currentIndex > 0 ? '第 ' . number_format($currentIndex) . ' 集' : '正在播放' ?></span>
        </div>
        <div class="watch-video-frame">
            <?php if (!empty($can_watch)): ?>
                <video controls poster="<?= htmlspecialchars((string) (($drama['cover'] ?? '') ?: '/assets/cover-1.svg')) ?>">
                    <source src="<?= htmlspecialchars((string) ($episode['video_url'] ?? '')) ?>" type="video/mp4">
                </video>
            <?php else: ?>
                <div class="watch-lock-state">
                    <span class="pill ember">本集未解锁</span>
                    <h1>购买后立即观看</h1>
                    <p>按集解锁适合追单集，开通会员可畅看当前短剧。</p>
                    <?php if (!empty($paymentRoutes)): ?>
                        <div class="payment-route-options compact" data-payment-route-picker>
                            <?php foreach ($paymentRoutes as $index => $route): ?>
                                <?php
                                $routeId = (string) ($route['id'] ?? '');
                                $checked = $routeId === $defaultPaymentRouteId || ($defaultPaymentRouteId === '' && $index === 0);
                                ?>
                                <label class="payment-route-option <?= $checked ? 'is-active' : '' ?>">
                                    <input type="radio" name="payment_route_id" value="<?= htmlspecialchars($routeId) ?>" <?= $checked ? 'checked' : '' ?>>
                                    <strong><?= htmlspecialchars((string) ($route['payment_method_name'] ?? '支付宝')) ?></strong>
                                    <span><?= htmlspecialchars((string) ($route['channel_name'] ?? $route['provider_name'] ?? '默认通道')) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="client-action-row">
                        <a class="btn primary js-buy-link" href="/?route=buy-episode&drama_id=<?= $dramaId ?>&episode_id=<?= $episodeId ?><?= htmlspecialchars($routeParam($defaultPaymentRouteId)) ?>">购买本集</a>
                        <a class="btn ghost js-buy-link" href="/?route=buy-membership&drama_id=<?= $dramaId ?><?= htmlspecialchars($routeParam($defaultPaymentRouteId)) ?>">开通会员</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="client-card watch-meta-card">
        <span class="eyebrow">正在播放</span>
        <h1><?= htmlspecialchars((string) ($drama['title'] ?? '')) ?></h1>
        <p><?= htmlspecialchars((string) ($episode['title'] ?? '')) ?> · <?= htmlspecialchars((string) ($episode['duration'] ?? '')) ?></p>
        <div class="mini-benefit-strip compact">
            <div>
                <strong>试看</strong>
                <span>免费集可直接播放</span>
            </div>
            <div>
                <strong>会员</strong>
                <span>支付成功自动发放权益</span>
            </div>
        </div>
    </section>

    <?php if (!empty($episodes)): ?>
        <section class="client-card">
            <header class="client-section-head">
                <div>
                    <span class="eyebrow">选集</span>
                    <h2>继续追剧</h2>
                </div>
                <a href="/?route=drama&id=<?= $dramaId ?>">全部</a>
            </header>
            <div class="watch-episode-strip">
                <?php foreach (array_slice($episodes, 0, 12) as $index => $item): ?>
                    <?php $itemId = (int) ($item['id'] ?? 0); ?>
                    <a class="<?= $itemId === $episodeId ? 'is-active' : '' ?>" href="/?route=watch&drama_id=<?= $dramaId ?>&episode_id=<?= $itemId ?>">
                        <?= $index + 1 ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>
<script>
(() => {
    const picker = document.querySelector('[data-payment-route-picker]');
    if (!picker) return;
    const links = Array.from(document.querySelectorAll('.js-buy-link'));
    const applyRoute = (routeId) => {
        links.forEach((link) => {
            const url = new URL(link.href, window.location.origin);
            if (routeId) {
                url.searchParams.set('payment_route_id', routeId);
            } else {
                url.searchParams.delete('payment_route_id');
            }
            link.href = `${url.pathname}${url.search}`;
        });
    };
    picker.querySelectorAll('input[name="payment_route_id"]').forEach((input) => {
        input.addEventListener('change', () => {
            picker.querySelectorAll('.payment-route-option').forEach((item) => item.classList.toggle('is-active', item.contains(input)));
            applyRoute(input.value);
        });
    });
})();
</script>
