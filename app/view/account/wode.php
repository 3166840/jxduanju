<?php
$title = '我的 - 精秀短剧';
$orders = array_values((array) ($orders ?? []));
$entitlements = array_values((array) ($entitlements ?? []));
$coinTransactions = array_values((array) ($coin_transactions ?? []));
$history = array_values((array) ($watch_history ?? []));
$coinPackages = array_values((array) ($coin_packages ?? []));
$appKey = (string) ($app_key ?? 'default');
$dramaMap = [];
foreach ((array) ($dramas ?? []) as $drama) {
    $dramaMap[(int) ($drama['id'] ?? 0)] = $drama;
}
$coinBalance = (int) ($user['coin_balance'] ?? 0) + (int) ($user['bonus_coin_balance'] ?? 0);
$activeTab = 'wode';
?>
<main class="duanju-app">
    <section class="mine-hero">
        <div class="mine-avatar"><?= htmlspecialchars(function_exists('mb_substr') ? mb_substr((string) (($user['nickname'] ?? '') ?: '精秀'), 0, 1) : substr((string) (($user['nickname'] ?? '') ?: '精秀'), 0, 1)) ?></div>
        <div>
            <span><?= empty($user['phone']) ? '游客账号' : '手机号 ' . htmlspecialchars((string) $user['phone']) ?></span>
            <h1><?= htmlspecialchars((string) (($user['nickname'] ?? '') ?: '游客用户')) ?></h1>
            <p><?= !empty($user['membership']) ? 'VIP 有效期至 ' . htmlspecialchars((string) ($user['membership_expires_at'] ?? '')) : '登录后可保留追剧、订单和权益' ?></p>
        </div>
        <a href="/denglu"><?= empty($user['phone']) ? '登录' : '切换' ?></a>
    </section>

    <section class="mine-vip-card">
        <div>
            <span>会员中心</span>
            <strong><?= !empty($user['membership']) ? 'VIP 已开通' : '开通 VIP 畅看' ?></strong>
        </div>
        <a href="/huiyuan">去开通</a>
    </section>

    <section class="mine-stats">
        <div><strong><?= number_format($coinBalance) ?></strong><span>K币余额</span></div>
        <div><strong><?= number_format(count($entitlements)) ?></strong><span>我的权益</span></div>
        <div><strong><?= number_format(count($orders)) ?></strong><span>订单数量</span></div>
    </section>

    <section class="mine-toggle-card">
        <div>
            <strong>自动解锁下一集</strong>
            <span>余额足够时，继续播放可自动扣 K币解锁</span>
        </div>
        <label class="mine-switch">
            <input type="checkbox" data-auto-unlock <?= !empty($user['auto_unlock_next']) ? 'checked' : '' ?>>
            <i></i>
        </label>
    </section>

    <section class="duanju-section">
        <header>
            <h2>K币充值</h2>
            <span>1元=100K币</span>
        </header>
        <div class="coin-package-grid">
            <?php foreach ($coinPackages as $index => $package): ?>
                <button class="<?= $index === 0 ? 'is-active' : '' ?>" type="button" data-coin-package="<?= htmlspecialchars((string) ($package['code'] ?? '')) ?>">
                    <strong><?= number_format((int) ($package['coins'] ?? 0) + (int) ($package['bonus_coins'] ?? 0)) ?>K</strong>
                    <span>￥<?= number_format((float) ($package['price'] ?? 0), 2) ?><?= (int) ($package['bonus_coins'] ?? 0) > 0 ? ' · 赠' . number_format((int) $package['bonus_coins']) : '' ?></span>
                </button>
            <?php endforeach; ?>
        </div>
        <p class="buy-message" data-coin-message>选择套餐后可创建支付订单，支付成功自动到账。</p>
    </section>

    <section class="duanju-section">
        <header>
            <h2>观看历史</h2>
            <a href="/zhuiju">更多</a>
        </header>
        <?php if (empty($history)): ?>
            <div class="duanju-empty compact"><strong>暂无观看记录</strong><p>看过的短剧会显示在这里。</p></div>
        <?php else: ?>
            <div class="duanju-history">
                <?php foreach (array_slice($history, 0, 4) as $item): ?>
                    <?php $drama = $dramaMap[(int) ($item['drama_id'] ?? 0)] ?? null; ?>
                    <?php if (!$drama) continue; ?>
                    <a href="/yulan/id/<?= (int) $drama['id'] ?>?episode_id=<?= (int) ($item['episode_id'] ?? 0) ?>">
                        <strong><?= htmlspecialchars((string) ($drama['title'] ?? '')) ?></strong>
                        <span><?= htmlspecialchars((string) ($item['updated_at'] ?? '')) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="duanju-section">
        <header>
            <h2>我的订单</h2>
            <span><?= number_format(count($orders)) ?> 笔</span>
        </header>
        <div class="mine-order-list">
            <?php foreach (array_slice($orders, 0, 8) as $order): ?>
                <div>
                    <span><?= htmlspecialchars((string) ($order['order_no'] ?? '')) ?></span>
                    <strong>￥<?= number_format((float) ($order['amount'] ?? 0), 2) ?></strong>
                    <em><?= htmlspecialchars((string) ($order['type'] ?? '订单')) ?> · <?= htmlspecialchars((string) ($order['status'] ?? 'pending')) ?></em>
                </div>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
                <div><span>暂无订单</span><strong>去追剧</strong><em>购买后会显示在这里</em></div>
            <?php endif; ?>
        </div>
    </section>

    <section class="duanju-section">
        <header>
            <h2>K币流水</h2>
            <span><?= number_format(count($coinTransactions)) ?> 条</span>
        </header>
        <div class="mine-order-list">
            <?php foreach (array_slice($coinTransactions, 0, 8) as $item): ?>
                <div>
                    <span><?= htmlspecialchars((string) ($item['remark'] ?? 'K币变动')) ?></span>
                    <strong><?= (int) ($item['coins'] ?? 0) > 0 ? '+' : '' ?><?= number_format((int) ($item['coins'] ?? 0)) ?></strong>
                    <em><?= htmlspecialchars((string) ($item['created_at'] ?? '')) ?></em>
                </div>
            <?php endforeach; ?>
            <?php if (empty($coinTransactions)): ?>
                <div><span>暂无流水</span><strong><?= number_format($coinBalance) ?>K</strong><em>充值或解锁后会记录</em></div>
            <?php endif; ?>
        </div>
    </section>

    <section class="mine-about">
        <a href="/duanju">关于精秀短剧</a>
        <span>版本 v1.0.0</span>
    </section>
</main>
<?php require dirname(__DIR__) . '/frontend/duanju_nav.php'; ?>
<script>
(() => {
    document.querySelector('[data-auto-unlock]')?.addEventListener('change', async (event) => {
        const body = new URLSearchParams({ enabled: event.currentTarget.checked ? '1' : '0' });
        await fetch('/?route=api-auto-unlock', { method: 'POST', body, headers: { 'Accept': 'application/json' } });
    });
    document.querySelectorAll('[data-coin-package]').forEach((button) => {
        button.addEventListener('click', async () => {
            document.querySelectorAll('[data-coin-package]').forEach((item) => item.classList.toggle('is-active', item === button));
            const message = document.querySelector('[data-coin-message]');
            if (message) message.textContent = '正在创建充值订单...';
            button.disabled = true;
            try {
                const body = new URLSearchParams({ plan: 'coin_recharge', package_code: button.dataset.coinPackage || '', app_key: <?= json_encode($appKey, JSON_UNESCAPED_UNICODE) ?> });
                const response = await fetch('/?route=api-player-order', { method: 'POST', body, headers: { 'Accept': 'application/json' } });
                const result = await response.json();
                if (result.cashier_url) {
                    window.location.href = result.cashier_url;
                    return;
                }
                if (message) message.textContent = result.message || '订单创建失败。';
            } catch (error) {
                if (message) message.textContent = '网络异常，请稍后再试。';
            } finally {
                button.disabled = false;
            }
        });
    });
})();
</script>
