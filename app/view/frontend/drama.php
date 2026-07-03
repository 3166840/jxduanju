<?php
$title = ($drama['title'] ?? '剧集详情') . ' - 精秀短剧';
$episodes = array_values((array) ($drama['episodes'] ?? []));
$episodeCount = count($episodes);
$freeCount = count(array_filter($episodes, static fn (array $episode): bool => !empty($episode['is_free'])));
$unlockedCount = 0;
foreach ($episodes as $episode) {
    if (!empty($episode_access[(int) ($episode['id'] ?? 0)]) || !empty($has_membership)) {
        $unlockedCount++;
    }
}
$firstEpisode = $episodes[0] ?? null;
$firstEpisodeId = (int) ($firstEpisode['id'] ?? 0);
$dramaId = (int) ($drama['id'] ?? 1);
$cover = (string) (($drama['cover'] ?? '') ?: '/assets/cover-1.svg');
$statusLabel = (($drama['status'] ?? 'online') === 'online') ? '热播中' : '已下架';
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
<main class="client-screen drama-detail-screen">
    <section class="client-hero-card drama-detail-hero">
        <div class="detail-poster-wrap">
            <img class="detail-poster" src="<?= htmlspecialchars($cover) ?>" alt="">
            <span class="detail-badge"><?= htmlspecialchars($statusLabel) ?></span>
        </div>
        <div class="detail-copy">
            <span class="eyebrow">短剧详情</span>
            <h1><?= htmlspecialchars($drama['title'] ?? '未找到剧集') ?></h1>
            <p><?= htmlspecialchars($drama['description'] ?? '精彩短剧正在准备中。') ?></p>
            <div class="client-stat-grid">
                <span><strong><?= number_format($episodeCount) ?></strong><em>总集数</em></span>
                <span><strong><?= number_format($freeCount) ?></strong><em>可试看</em></span>
                <span><strong><?= number_format($unlockedCount) ?></strong><em>已解锁</em></span>
            </div>
            <div class="client-action-row">
                <?php if ($firstEpisodeId > 0): ?>
                    <a class="btn primary" href="/?route=watch&drama_id=<?= $dramaId ?>&episode_id=<?= $firstEpisodeId ?>">立即播放</a>
                <?php endif; ?>
                <a class="btn ghost js-buy-link" href="/?route=buy-membership&drama_id=<?= $dramaId ?><?= htmlspecialchars($routeParam($defaultPaymentRouteId)) ?>">开通会员</a>
            </div>
        </div>
    </section>

    <?php if (!empty($paymentRoutes)): ?>
        <section class="client-card payment-route-picker" data-payment-route-picker>
            <header class="client-section-head">
                <div>
                    <span class="eyebrow">支付方式</span>
                    <h2><?= count($paymentRoutes) > 1 ? '选择付款方式' : '默认付款方式' ?></h2>
                </div>
                <span><?= htmlspecialchars((string) ($paymentRoutes[0]['provider_name'] ?? '支付通道')) ?></span>
            </header>
            <div class="payment-route-options">
                <?php foreach ($paymentRoutes as $index => $route): ?>
                    <?php
                    $routeId = (string) ($route['id'] ?? '');
                    $checked = $routeId === $defaultPaymentRouteId || ($defaultPaymentRouteId === '' && $index === 0);
                    ?>
                    <label class="payment-route-option <?= $checked ? 'is-active' : '' ?>">
                        <input type="radio" name="payment_route_id" value="<?= htmlspecialchars($routeId) ?>" <?= $checked ? 'checked' : '' ?>>
                        <div class="payment-route-head">
                            <?= jx_payment_icon($route) ?>
                            <strong><?= htmlspecialchars((string) ($route['payment_method_name'] ?? '支付宝')) ?></strong>
                        </div>
                        <span><?= htmlspecialchars((string) ($route['channel_name'] ?? $route['provider_name'] ?? '默认通道')) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="mini-benefit-strip">
        <div>
            <strong>单集 ￥<?= htmlspecialchars((string) ($drama['price_per_episode'] ?? 0)) ?></strong>
            <span>喜欢哪集买哪集</span>
        </div>
        <div>
            <strong>会员 ￥<?= htmlspecialchars((string) ($drama['membership_price'] ?? 0)) ?></strong>
            <span><?= !empty($has_membership) ? '当前会员有效' : '整部短剧畅看' ?></span>
        </div>
    </section>

    <section class="client-card episode-section">
        <header class="client-section-head">
            <div>
                <span class="eyebrow">试看与解锁</span>
                <h2>剧集列表</h2>
            </div>
            <a href="/?route=center">我的权益</a>
        </header>

        <?php if (empty($episodes)): ?>
            <p class="muted">暂无分集，去后台添加第一集吧。</p>
        <?php else: ?>
            <div class="episode-card-list">
                <?php foreach ($episodes as $index => $episode): ?>
                    <?php
                    $episodeId = (int) ($episode['id'] ?? 0);
                    $isFree = !empty($episode['is_free']);
                    $unlocked = !empty($episode_access[$episodeId]) || !empty($has_membership);
                    $episodeClass = $unlocked ? 'is-unlocked' : 'is-locked';
                    ?>
                    <article class="client-episode-card <?= $episodeClass ?>">
                        <a class="episode-play-link" href="/?route=watch&drama_id=<?= $dramaId ?>&episode_id=<?= $episodeId ?>">
                            <span class="episode-index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <span>
                                <strong><?= htmlspecialchars((string) ($episode['title'] ?? '未命名分集')) ?></strong>
                                <em><?= htmlspecialchars((string) ($episode['duration'] ?? '')) ?> · <?= $isFree ? '免费试看' : ($unlocked ? '已解锁' : '待购买') ?></em>
                            </span>
                        </a>
                        <div class="episode-actions">
                            <?php if (!$unlocked): ?>
                                <a class="btn js-buy-link" href="/?route=buy-episode&drama_id=<?= $dramaId ?>&episode_id=<?= $episodeId ?><?= htmlspecialchars($routeParam($defaultPaymentRouteId)) ?>">购买</a>
                            <?php endif; ?>
                            <a class="btn ghost" href="/?route=watch&drama_id=<?= $dramaId ?>&episode_id=<?= $episodeId ?>">播放</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
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
