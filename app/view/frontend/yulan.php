<?php
$title = ($drama['title'] ?? '播放预览') . ' - 精秀短剧';
$drama = (array) ($drama ?? []);
$episode = (array) ($episode ?? []);
$episodes = array_values((array) ($episodes ?? ($drama['episodes'] ?? [])));
$dramaId = (int) ($drama['id'] ?? 0);
$episodeId = (int) ($episode['id'] ?? 0);
$episodeAccess = (array) ($episode_access ?? []);
$cover = (string) (($drama['cover'] ?? '') ?: '/assets/cover-1.svg');
$currentIndex = 0;
foreach ($episodes as $index => $item) {
    if ((int) ($item['id'] ?? 0) === $episodeId) {
        $currentIndex = $index;
        break;
    }
}
$prevEpisode = $episodes[max(0, $currentIndex - 1)] ?? null;
$nextEpisode = $episodes[$currentIndex + 1] ?? null;
$paymentRoutes = array_values((array) ($payment_routes ?? []));
$defaultRouteId = '';
foreach ($paymentRoutes as $route) {
    if (!empty($route['is_default'])) {
        $defaultRouteId = (string) ($route['id'] ?? '');
        break;
    }
}
$defaultRouteId = $defaultRouteId ?: (string) ($paymentRoutes[0]['id'] ?? '');
$vipPlans = array_values((array) ($vip_plans ?? []));
$weekPlan = $vipPlans[0] ?? ['code' => 'vip_week', 'name' => 'VIP 周卡', 'price' => 9.9, 'days' => 7];
$monthPlan = $vipPlans[1] ?? ($vipPlans[0] ?? ['code' => 'vip_month', 'name' => 'VIP 月卡', 'price' => 29.9, 'days' => 30]);
$appKey = (string) ($app_key ?? 'default');
$coinBalance = (int) ($user['coin_balance'] ?? 0) + (int) ($user['bonus_coin_balance'] ?? 0);
$freeCount = (int) ($drama['free_episode_count'] ?? 1);
$paidCount = max(0, count($episodes) - $freeCount);
$activeTab = 'duanju';
?>
<main class="duanju-player" data-player-page data-order-no="">
    <section class="player-stage">
        <div class="player-topbar">
            <a href="/duanju">‹</a>
            <span><?= htmlspecialchars((string) ($drama['title'] ?? '短剧')) ?></span>
            <button type="button" data-follow-drama data-followed="<?= !empty($is_followed) ? '1' : '0' ?>"><?= !empty($is_followed) ? '已追' : '追剧' ?></button>
        </div>

        <div class="player-video">
            <?php if (!empty($can_watch)): ?>
                <video controls playsinline poster="<?= htmlspecialchars($cover) ?>">
                    <source src="<?= htmlspecialchars((string) ($episode['video_url'] ?? '')) ?>" type="video/mp4">
                </video>
            <?php else: ?>
                <img src="<?= htmlspecialchars($cover) ?>" alt="">
                <div class="player-lock">
                    <span>第 <?= number_format($currentIndex + 1) ?> 集已锁定</span>
                    <h1>解锁后继续观看</h1>
                    <p>可用 K币直接解锁，也可以选择支付全集/VIP/单集方案。</p>
                    <button class="btn primary" type="button" data-open-buy>立即解锁</button>
                </div>
            <?php endif; ?>
        </div>

        <div class="player-controls">
            <a class="<?= $prevEpisode ? '' : 'is-disabled' ?>" href="<?= $prevEpisode ? '/yulan/id/' . $dramaId . '?episode_id=' . (int) ($prevEpisode['id'] ?? 0) : '#' ?>">上一集</a>
            <button type="button" data-open-episodes>选集 <?= number_format($currentIndex + 1) ?>/<?= number_format(count($episodes)) ?></button>
            <a class="<?= $nextEpisode ? '' : 'is-disabled' ?>" href="<?= $nextEpisode ? '/yulan/id/' . $dramaId . '?episode_id=' . (int) ($nextEpisode['id'] ?? 0) : '#' ?>">下一集</a>
        </div>
    </section>

    <section class="player-info-card">
        <div>
            <span><?= htmlspecialchars((string) ($drama['category'] ?? '短剧')) ?> · <?= number_format(count($episodes)) ?>集</span>
            <h1><?= htmlspecialchars((string) ($drama['title'] ?? '')) ?></h1>
            <p><?= htmlspecialchars((string) ($drama['description'] ?? '')) ?></p>
        </div>
        <div class="player-balance">
            <strong><?= number_format($coinBalance) ?> K币</strong>
            <span>1元=100K币</span>
        </div>
    </section>

    <section class="player-mini-episodes">
        <header>
            <h2>选集</h2>
            <button type="button" data-open-episodes>展开全部</button>
        </header>
        <div>
            <?php foreach (array_slice($episodes, 0, 12) as $index => $item): ?>
                <?php
                $itemId = (int) ($item['id'] ?? 0);
                $unlocked = !empty($episodeAccess[$itemId]);
                ?>
                <a class="<?= $itemId === $episodeId ? 'is-active' : '' ?> <?= $unlocked ? 'is-free' : 'is-lock' ?>" href="/yulan/id/<?= $dramaId ?>?episode_id=<?= $itemId ?>">
                    <?= $index + 1 ?><?= $unlocked ? '' : '锁' ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<div class="player-sheet" data-episode-sheet hidden>
    <button class="player-sheet-backdrop" type="button" data-close-sheet aria-label="关闭"></button>
    <section class="player-sheet-card">
        <header>
            <div>
                <span>Episodes</span>
                <h2>全部选集</h2>
            </div>
            <button type="button" data-close-sheet>×</button>
        </header>
        <div class="player-episode-grid">
            <?php foreach ($episodes as $index => $item): ?>
                <?php
                $itemId = (int) ($item['id'] ?? 0);
                $unlocked = !empty($episodeAccess[$itemId]);
                ?>
                <a class="<?= $itemId === $episodeId ? 'is-active' : '' ?> <?= $unlocked ? 'is-free' : 'is-lock' ?>" href="/yulan/id/<?= $dramaId ?>?episode_id=<?= $itemId ?>">
                    <strong><?= $index + 1 ?></strong>
                    <span><?= $unlocked ? '可看' : '锁定' ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<div class="player-sheet buy-sheet" data-buy-sheet hidden>
    <button class="player-sheet-backdrop" type="button" data-close-buy aria-label="关闭"></button>
    <section class="player-sheet-card">
        <header>
            <div>
                <span>Unlock</span>
                <h2>选择解锁方式</h2>
            </div>
            <button type="button" data-close-buy>×</button>
        </header>

        <div class="buy-tabs">
            <button class="is-active" type="button" data-buy-tab="drama_unlock">全集</button>
            <button type="button" data-buy-tab="<?= htmlspecialchars((string) ($monthPlan['code'] ?? 'vip_month')) ?>">VIP</button>
            <button type="button" data-buy-tab="episode_unlock">单集</button>
        </div>

        <div class="buy-plan-list">
            <label class="buy-plan is-active" data-plan-card="drama_unlock">
                <input type="radio" name="buy_plan" value="drama_unlock" checked>
                <span>
                    <strong>全集解锁</strong>
                    <em>付费 <?= number_format($paidCount) ?> 集，一次解锁当前短剧</em>
                </span>
                <b>￥<?= number_format((float) ($drama['full_unlock_price'] ?? 0), 2) ?></b>
            </label>
            <label class="buy-plan" data-plan-card="<?= htmlspecialchars((string) ($weekPlan['code'] ?? 'vip_week')) ?>">
                <input type="radio" name="buy_plan" value="<?= htmlspecialchars((string) ($weekPlan['code'] ?? 'vip_week')) ?>">
                <span>
                    <strong><?= htmlspecialchars((string) ($weekPlan['name'] ?? 'VIP 周卡')) ?></strong>
                    <em><?= number_format((int) ($weekPlan['days'] ?? 7)) ?> 天畅看会员权益</em>
                </span>
                <b>￥<?= number_format((float) ($weekPlan['price'] ?? 0), 2) ?></b>
            </label>
            <label class="buy-plan" data-plan-card="<?= htmlspecialchars((string) ($monthPlan['code'] ?? 'vip_month')) ?>">
                <input type="radio" name="buy_plan" value="<?= htmlspecialchars((string) ($monthPlan['code'] ?? 'vip_month')) ?>">
                <span>
                    <strong><?= htmlspecialchars((string) ($monthPlan['name'] ?? 'VIP 月卡')) ?></strong>
                    <em><?= number_format((int) ($monthPlan['days'] ?? 30)) ?> 天畅看会员权益</em>
                </span>
                <b>￥<?= number_format((float) ($monthPlan['price'] ?? 0), 2) ?></b>
            </label>
            <label class="buy-plan" data-plan-card="episode_unlock">
                <input type="radio" name="buy_plan" value="episode_unlock">
                <span>
                    <strong>单集购买</strong>
                    <em>当前第 <?= number_format($currentIndex + 1) ?> 集，优先使用 K币</em>
                </span>
                <b><?= number_format((int) ($drama['episode_coin_price'] ?? 0)) ?>K币</b>
            </label>
        </div>

        <?php if (!empty($paymentRoutes)): ?>
            <div class="buy-route-list">
                <?php foreach ($paymentRoutes as $index => $route): ?>
                    <?php $routeId = (string) ($route['id'] ?? ''); ?>
                    <label class="<?= $routeId === $defaultRouteId || ($defaultRouteId === '' && $index === 0) ? 'is-active' : '' ?>">
                        <input type="radio" name="payment_route_id" value="<?= htmlspecialchars($routeId) ?>" <?= $routeId === $defaultRouteId || ($defaultRouteId === '' && $index === 0) ? 'checked' : '' ?>>
                        <span class="payment-route-method"><?= jx_payment_icon($route) ?><?= htmlspecialchars((string) ($route['payment_method_name'] ?? '支付宝')) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="buy-message" data-buy-message>余额足够时将直接扣 K币解锁，余额不足会创建支付订单。</p>
        <button class="btn primary buy-submit" type="button" data-submit-buy>立即开通/解锁</button>
    </section>
</div>

<?php require __DIR__ . '/duanju_nav.php'; ?>

<script>
(() => {
    const dramaId = <?= (int) $dramaId ?>;
    const episodeId = <?= (int) $episodeId ?>;
    const episodeSheet = document.querySelector('[data-episode-sheet]');
    const buySheet = document.querySelector('[data-buy-sheet]');
    const message = document.querySelector('[data-buy-message]');
    const submit = document.querySelector('[data-submit-buy]');
    const player = document.querySelector('[data-player-page]');
    const open = (node) => {
        if (node) node.hidden = false;
        document.body.classList.add('has-player-sheet');
    };
    const close = (node) => {
        if (node) node.hidden = true;
        if (!episodeSheet || episodeSheet.hidden) {
            if (!buySheet || buySheet.hidden) document.body.classList.remove('has-player-sheet');
        }
    };
    document.querySelectorAll('[data-open-episodes]').forEach((button) => button.addEventListener('click', () => open(episodeSheet)));
    document.querySelectorAll('[data-close-sheet]').forEach((button) => button.addEventListener('click', () => close(episodeSheet)));
    document.querySelectorAll('[data-open-buy]').forEach((button) => button.addEventListener('click', () => open(buySheet)));
    document.querySelectorAll('[data-close-buy]').forEach((button) => button.addEventListener('click', () => close(buySheet)));
    document.querySelectorAll('[data-buy-tab]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-buy-tab]').forEach((item) => item.classList.toggle('is-active', item === button));
            const card = document.querySelector(`[data-plan-card="${button.dataset.buyTab}"]`);
            card?.click();
        });
    });
    document.querySelectorAll('.buy-plan input').forEach((input) => {
        input.addEventListener('change', () => {
            document.querySelectorAll('.buy-plan').forEach((item) => item.classList.toggle('is-active', item.contains(input)));
        });
    });
    document.querySelectorAll('.buy-route-list input').forEach((input) => {
        input.addEventListener('change', () => {
            document.querySelectorAll('.buy-route-list label').forEach((item) => item.classList.toggle('is-active', item.contains(input)));
        });
    });
    document.querySelector('[data-follow-drama]')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        const body = new URLSearchParams({ drama_id: String(dramaId) });
        const response = await fetch('/?route=api-follow-drama', { method: 'POST', body, headers: { 'Accept': 'application/json' } });
        const result = await response.json();
        button.textContent = result.followed ? '已追' : '追剧';
        button.dataset.followed = result.followed ? '1' : '0';
    });
    submit?.addEventListener('click', async () => {
        const plan = document.querySelector('input[name="buy_plan"]:checked')?.value || 'episode_unlock';
        const routeId = document.querySelector('input[name="payment_route_id"]:checked')?.value || '';
        submit.disabled = true;
        message.textContent = '正在创建解锁订单...';
        try {
            const body = new URLSearchParams({
                drama_id: String(dramaId),
                episode_id: String(episodeId),
                plan,
                payment_route_id: routeId,
                app_key: <?= json_encode($appKey, JSON_UNESCAPED_UNICODE) ?>
            });
            const response = await fetch('/?route=api-player-order', { method: 'POST', body, headers: { 'Accept': 'application/json' } });
            const result = await response.json();
            if (result.unlocked) {
                message.textContent = result.message || '解锁成功，正在刷新播放器...';
                window.setTimeout(() => window.location.reload(), 700);
                return;
            }
            if (result.payment_required && result.cashier_url) {
                player.dataset.orderNo = result.order?.order_no || '';
                message.textContent = '订单已创建，正在打开收银台...';
                window.location.href = result.cashier_url;
                return;
            }
            message.textContent = result.message || result.gateway?.message || '创建订单失败，请稍后重试。';
        } catch (error) {
            message.textContent = '网络异常，请稍后再试。';
        } finally {
            submit.disabled = false;
        }
    });
})();
</script>
