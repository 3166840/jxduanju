<?php $title = '支付结果 - 精秀短剧'; ?>
<?php
$qrSvg = '';
$qrError = '';
$orderStatus = (string) ($order['status'] ?? 'pending');
$isPaid = in_array($orderStatus, ['paid', 'partial_refunded', 'refunded'], true);
$displayStatus = $isPaid ? 'paid' : $orderStatus;
$orderType = (string) ($order['type'] ?? '');
$isTestOrder = !empty($order['is_test']) || $orderType === 'payment_test';
$orderContentType = (string) ($order['content_type'] ?? 'drama');
$orderTypeLabel = $isTestOrder ? '通道测试订单' : match ($orderType) {
    'membership', 'vip_week', 'vip_month' => '会员畅看',
    'drama_unlock' => '全集解锁',
    'novel_unlock' => '整本小说解锁',
    'novel_chapter_unlock' => '小说章节解锁',
    default => '单集解锁',
};
$adminOrderUrl = !empty($order['order_no'])
    ? '/jxdjadmin?admin_section=orders&order_no=' . rawurlencode((string) $order['order_no']) . '&open_order=' . rawurlencode((string) $order['order_no']) . '#orders'
    : '/jxdjadmin#orders';
$paymentDisplay = !empty($order) ? $service->paymentDisplayForOrder((array) $order) : [
    'method_name' => '支付宝',
    'provider_name' => '精秀聚合支付',
    'channel_name' => '精秀主通道',
];
$paymentMethodName = (string) (($gateway['payment_method_name'] ?? '') ?: ($paymentDisplay['method_name'] ?? '支付宝'));
$paymentProviderName = (string) (($gateway['payment_provider_name'] ?? '') ?: ($paymentDisplay['provider_name'] ?? '精秀聚合支付'));
$paymentChannelName = (string) (($gateway['payment_channel_name'] ?? '') ?: ($paymentDisplay['channel_name'] ?? '精秀主通道'));
$userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
$isMobileClient = (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod|Windows Phone|MicroMessenger|AlipayClient/i', $userAgent);
$statusLabels = [
    'pending' => '待支付',
    'paid' => '已支付',
    'partial_refunded' => '部分退款',
    'refunded' => '已退款',
    'refund_pending' => '退款中',
    'refund_failed' => '退款失败',
];
if (!empty($gateway['payment_url']) && !$isMobileClient) {
    try {
        $qrSvg = \App\Support\QrCode::svg((string) $gateway['payment_url']);
    } catch (\Throwable $exception) {
        $qrError = $exception->getMessage();
    }
}
?>
<style>
@media (max-width: 760px), (pointer: coarse) {
    .payment-screen .qr-card,
    .payment-screen .payment-desktop-only {
        display: none !important;
    }
    .payment-screen .payment-qr-grid {
        grid-template-columns: 1fr !important;
        justify-items: stretch !important;
    }
    .payment-screen .payment-mobile-only {
        display: inline-flex !important;
    }
    .payment-screen h2.payment-mobile-only,
    .payment-screen p.payment-mobile-only {
        display: block !important;
    }
}
</style>
<main class="client-screen payment-screen">
    <section class="client-hero-card payment-status-hero">
        <span class="payment-status-orb <?= $isPaid ? 'is-paid' : '' ?>" data-payment-orb></span>
        <span class="eyebrow"><?= $isTestOrder ? '支付通道测试收银台' : '精秀短剧收银台' ?></span>
        <h1 data-payment-title><?= $isPaid ? ($isTestOrder ? '测试支付成功' : '支付成功') : ($isTestOrder ? '测试订单已创建' : '订单已创建') ?></h1>
        <p data-payment-hero-message><?= htmlspecialchars((string) ($message ?? ($isPaid ? ($isTestOrder ? '测试支付已完成。' : '支付已完成。') : ''))) ?></p>
        <?php if (!empty($order)): ?>
            <div class="payment-hero-meta">
                <span><?= htmlspecialchars($orderTypeLabel) ?></span>
                <strong>￥<?= htmlspecialchars((string) ($order['amount'] ?? 0)) ?></strong>
                <em data-payment-status-text><?= htmlspecialchars($statusLabels[$displayStatus] ?? $displayStatus) ?></em>
            </div>
        <?php endif; ?>
    </section>

    <?php if (!empty($order)): ?>
        <section class="client-card payment-success-card" data-payment-success-card style="<?= $isPaid ? '' : 'display:none' ?>">
            <div class="payment-success-mark">✓</div>
            <span class="eyebrow"><?= $isTestOrder ? '测试完成' : '支付完成' ?></span>
            <h2 data-payment-success-title><?= $isTestOrder ? '测试支付成功' : '支付成功，权益已发放' ?></h2>
            <p data-payment-success-message><?= $isTestOrder ? '测试订单状态已更新，不发放短剧权益，不计入正式收入。' : '订单权益已经写入账号，你可以继续观看短剧或查看个人中心。' ?></p>
            <div class="client-action-row payment-success-actions">
                <?php if ($isTestOrder): ?>
                    <a class="btn primary" href="/jxdjadmin#payment-channel">返回支付通道</a>
                    <a class="btn ghost" href="<?= htmlspecialchars($adminOrderUrl) ?>">查看测试订单</a>
                <?php elseif ($orderContentType === 'novel'): ?>
                    <a class="btn primary" href="/?route=novel-read&novel_id=<?= (int) ($order['novel_id'] ?? 0) ?>&chapter_id=<?= (int) ($order['chapter_id'] ?? 0) ?>">去阅读</a>
                    <a class="btn ghost" href="/?route=center">查看我的权益</a>
                <?php elseif (!empty($order['episode_id'])): ?>
                    <a class="btn primary" href="/?route=watch&drama_id=<?= (int) ($order['drama_id'] ?? 1) ?>&episode_id=<?= (int) ($order['episode_id'] ?? 0) ?>">去观看</a>
                    <a class="btn ghost" href="/?route=center">查看我的权益</a>
                <?php else: ?>
                    <a class="btn primary" href="/?route=center">查看我的权益</a>
                    <a class="btn ghost" href="/?route=home">返回首页</a>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!$isPaid && !empty($gateway) && !empty($gateway['enabled'])): ?>
        <section class="client-card pay-panel payment-cashier-card" data-payment-cashier-card<?= !empty($gateway['payment_url']) ? ' data-mobile-payment-url="' . htmlspecialchars((string) $gateway['payment_url']) . '"' : '' ?>>
            <header class="client-section-head">
                <div>
                    <span class="eyebrow">收银台</span>
                    <h2><?= empty($gateway['error']) ? '使用' . htmlspecialchars($paymentMethodName) . '完成付款' : '支付下单未完成' ?></h2>
                </div>
                <span><?= htmlspecialchars($paymentProviderName) ?> · <?= htmlspecialchars($paymentChannelName) ?></span>
            </header>

            <?php if (!empty($gateway['payment_url'])): ?>
                <div class="payment-qr-grid<?= $isMobileClient ? ' is-mobile-payment' : '' ?>">
                    <?php if (!$isMobileClient): ?>
                        <div class="qr-card">
                            <?php if ($qrSvg !== ''): ?>
                                <?= $qrSvg ?>
                            <?php else: ?>
                                <p class="notice warn"><?= htmlspecialchars($qrError ?: '二维码生成失败，请直接打开支付链接。') ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="payment-guide">
                        <?php if ($isMobileClient): ?>
                            <span class="pill jade payment-mobile-only">手机端直接支付</span>
                            <h2 class="payment-mobile-only">点击按钮继续付款</h2>
                            <p class="payment-mobile-only">当前是手机浏览器，请直接打开支付链接完成付款，付款后返回本页会自动查询结果。</p>
                        <?php else: ?>
                            <span class="pill jade payment-desktop-only">电脑端扫码支付</span>
                            <span class="pill jade payment-mobile-only">手机端直接支付</span>
                            <h2 class="payment-desktop-only">用手机扫码完成付款</h2>
                            <h2 class="payment-mobile-only">点击按钮继续付款</h2>
                            <p class="payment-desktop-only">电脑打开时请使用手机<?= htmlspecialchars($paymentMethodName) ?>对应 App 扫码；手机打开时可以直接点下面按钮继续支付。</p>
                            <p class="payment-mobile-only">当前是手机浏览器，请直接打开支付链接完成付款，付款后返回本页会自动查询结果。</p>
                        <?php endif; ?>
                        <div class="client-action-row">
                            <a class="btn primary" href="<?= htmlspecialchars((string) $gateway['payment_url']) ?>">立即支付</a>
                            <button class="btn ghost" type="button" data-check-payment>我已支付，立即查询</button>
                        </div>
                        <p class="payment-state-text" data-payment-state><?= $isTestOrder ? '系统会每 10 秒主动查询一次支付结果，成功后只标记测试订单，不发放权益。' : '系统会每 10 秒主动查询一次支付结果，成功后自动发放权益。' ?></p>
                    </div>
                </div>
            <?php elseif (!empty($gateway['pay_info'])): ?>
                <div class="payment-guide">
                    <span class="pill jade">前端调起支付</span>
                    <h2><?= htmlspecialchars($paymentMethodName) ?>已返回支付参数</h2>
                    <p>请按对应支付类型在前端调起，支付完成后可立即查询订单状态。</p>
                    <pre><?= htmlspecialchars((string) $gateway['pay_info']) ?></pre>
                    <div class="client-action-row">
                        <button class="btn ghost" type="button" data-check-payment>我已支付，立即查询</button>
                    </div>
                    <p class="payment-state-text" data-payment-state><?= $isTestOrder ? '系统会每 10 秒主动查询一次支付结果，成功后只标记测试订单，不发放权益。' : '系统会每 10 秒主动查询一次支付结果，成功后自动发放权益。' ?></p>
                </div>
            <?php else: ?>
                <p class="notice warn"><?= htmlspecialchars((string) ($gateway['error'] ?? '未返回支付链接。')) ?></p>
            <?php endif; ?>

        </section>
    <?php elseif (!$isPaid && !empty($gateway['message'])): ?>
        <p class="notice warn"><?= htmlspecialchars((string) $gateway['message']) ?></p>
    <?php endif; ?>

    <a class="btn ghost client-back-home" href="<?= $isTestOrder ? '/jxdjadmin#payment-channel' : '/?route=home' ?>"><?= $isTestOrder ? '返回支付通道' : '返回首页' ?></a>
</main>

<?php if (!empty($order) && !$isPaid && !empty($gateway['enabled']) && (!empty($gateway['payment_url']) || !empty($gateway['pay_info']))): ?>
<script>
(() => {
    const orderNo = <?= json_encode((string) ($order['order_no'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
    const isTestOrder = <?= $isTestOrder ? 'true' : 'false' ?>;
    if (!orderNo) {
        return;
    }

    const title = document.querySelector('[data-payment-title]');
    const heroMessage = document.querySelector('[data-payment-hero-message]');
    const orb = document.querySelector('[data-payment-orb]');
    const state = document.querySelector('[data-payment-state]');
    const button = document.querySelector('[data-check-payment]');
    const paidActions = document.querySelector('[data-paid-actions]');
    const cashierCard = document.querySelector('[data-payment-cashier-card]');
    const mobilePaymentUrl = cashierCard?.dataset.mobilePaymentUrl || '';
    const isMobileBrowser = /Mobile|Android|iPhone|iPad|iPod|Windows Phone|MicroMessenger|AlipayClient/i.test(navigator.userAgent)
        || window.matchMedia?.('(max-width: 760px), (pointer: coarse)')?.matches;
    const successCard = document.querySelector('[data-payment-success-card]');
    const successTitle = document.querySelector('[data-payment-success-title]');
    const successMessage = document.querySelector('[data-payment-success-message]');
    const safeActions = document.querySelector('[data-payment-safe-actions]');
    const statusNodes = document.querySelectorAll('[data-payment-status-text], [data-order-summary-status], [data-order-payment-status]');
    let cooldown = 0;
    let cooldownTimer = null;
    let pollingTimer = null;

    const setState = (text) => {
        if (state) {
            state.textContent = text;
        }
    };

    const setPaid = (message) => {
        if (title) {
            title.textContent = isTestOrder ? '测试支付成功' : '支付成功';
        }
        orb?.classList.add('is-paid');
        statusNodes.forEach((node) => {
            node.textContent = '已支付';
        });
        if (heroMessage) {
            heroMessage.textContent = message || (isTestOrder ? '测试支付成功，测试订单状态已更新。' : '支付成功，权益已自动发放。');
        }
        setState(message || (isTestOrder ? '测试支付成功，测试订单状态已更新。' : '支付成功，权益已自动发放。'));
        if (cashierCard) {
            cashierCard.style.display = 'none';
        }
        if (successCard) {
            successCard.style.display = '';
        }
        if (successTitle) {
            successTitle.textContent = isTestOrder ? '测试支付成功' : '支付成功，权益已发放';
        }
        if (successMessage) {
            successMessage.textContent = isTestOrder ? '测试订单状态已更新，不发放短剧权益，不计入正式收入。' : '订单权益已经写入账号，你可以继续观看短剧或查看个人中心。';
        }
        if (safeActions) {
            safeActions.style.display = 'none';
        }
        if (paidActions) {
            paidActions.style.display = '';
        }
        if (button) {
            button.disabled = true;
            button.textContent = isTestOrder ? '测试成功' : '支付成功';
        }
        if (pollingTimer) {
            clearInterval(pollingTimer);
        }
        if (cooldownTimer) {
            clearInterval(cooldownTimer);
        }
    };

    const tickCooldown = () => {
        if (!button) {
            return;
        }
        if (cooldown <= 0) {
            button.disabled = false;
            button.textContent = '我已支付，立即查询';
            clearInterval(cooldownTimer);
            cooldownTimer = null;
            return;
        }
        button.disabled = true;
        button.textContent = `请稍等 ${cooldown}s`;
        cooldown--;
    };

    const startCooldown = () => {
        cooldown = 10;
        tickCooldown();
        if (cooldownTimer) {
            clearInterval(cooldownTimer);
        }
        cooldownTimer = setInterval(tickCooldown, 1000);
    };

    const checkPayment = async (manual = false) => {
        if (manual && cooldown > 0) {
            return;
        }
        let paid = false;
        try {
            setState('正在查询支付结果...');
            const response = await fetch(`/payment/status?order_no=${encodeURIComponent(orderNo)}`, {
                cache: 'no-store',
                headers: { 'Accept': 'application/json' }
            });
            const result = await response.json();
            if (result.paid) {
                paid = true;
                setPaid(result.message);
                return;
            }
            setState(result.message || (isTestOrder ? '还没有查到测试支付成功，系统会继续自动查询。' : '还没有查到支付成功，系统会继续自动查询。'));
        } catch (error) {
            setState('查询暂时失败，系统会继续重试。');
        } finally {
            if (!paid) {
                startCooldown();
            }
        }
    };

    if (button) {
        button.addEventListener('click', () => checkPayment(true));
    }
    if (isMobileBrowser && cashierCard) {
        cashierCard.querySelectorAll('.qr-card, .payment-desktop-only').forEach((node) => node.remove());
        cashierCard.querySelectorAll('.payment-mobile-only').forEach((node) => {
            node.style.display = node.tagName === 'H2' || node.tagName === 'P' ? 'block' : 'inline-flex';
        });
        cashierCard.querySelector('.payment-qr-grid')?.classList.add('is-mobile-payment');
    }
    if (mobilePaymentUrl && isMobileBrowser) {
        const autoOpenKey = `payment:auto-open:${orderNo}`;
        let shouldAutoOpen = true;
        try {
            shouldAutoOpen = sessionStorage.getItem(autoOpenKey) !== '1';
            sessionStorage.setItem(autoOpenKey, '1');
        } catch (error) {
            shouldAutoOpen = true;
        }
        if (shouldAutoOpen) {
            setTimeout(() => {
                window.location.href = mobilePaymentUrl;
            }, 450);
        }
    }
    pollingTimer = setInterval(() => checkPayment(false), 10000);
    setTimeout(() => checkPayment(false), 1500);
})();
</script>
<?php endif; ?>
