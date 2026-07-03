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
$paymentMethodCode = strtolower((string) (($gateway['payment_method'] ?? '') ?: ($order['payment_method'] ?? '') ?: ($order['gateway_trade_type'] ?? '')));
$isAlipayPayment = str_contains($paymentMethodCode, 'alipay') || str_contains($paymentMethodName, '支付宝');
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
.view-payment-result.is-client {
    color: #171a21 !important;
    background: #f6f7fb !important;
}
.view-payment-result.is-client::before {
    display: none !important;
}
.view-payment-result .client-topbar {
    display: none !important;
}
.view-payment-result .client-footer {
    display: none !important;
}
.view-payment-result.is-client:not(.view-frontend-home) .wrap {
    padding: max(18px, env(safe-area-inset-top)) 14px max(24px, env(safe-area-inset-bottom)) !important;
}
.payment-screen.has-mobile-checkout {
    display: grid;
    min-height: calc(100vh - 42px);
    min-height: calc(100dvh - 42px);
    place-items: center;
}
.payment-screen.has-mobile-checkout > .payment-status-hero,
.payment-screen.has-mobile-checkout > .payment-cashier-card,
.payment-screen.has-mobile-checkout > .client-back-home {
    display: none !important;
}
.payment-mobile-checkout {
    position: relative;
    overflow: hidden;
    display: grid;
    gap: 16px;
    width: min(100%, 430px);
    padding: 20px;
    border: 1px solid #eaedf3;
    border-radius: 28px;
    color: #171a21;
    background: #fff;
    box-shadow: 0 20px 50px rgba(18, 24, 38, .10);
}
.payment-mobile-checkout::before {
    content: "";
    position: absolute;
    inset: 0 0 auto;
    height: 86px;
    background: linear-gradient(135deg, rgba(255, 126, 70, .12), rgba(75, 194, 129, .10));
    pointer-events: none;
}
.payment-mobile-checkout > * {
    position: relative;
}
.payment-mobile-checkout-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.payment-mobile-checkout-brand {
    display: grid;
    gap: 4px;
}
.payment-mobile-checkout-brand span,
.payment-mobile-checkout-secure {
    color: #8a93a3;
    font-size: 12px;
    font-weight: 900;
}
.payment-mobile-checkout-brand strong {
    color: #171a21;
    font-size: 22px;
    line-height: 1.1;
    letter-spacing: 0;
}
.payment-mobile-checkout-secure {
    padding: 7px 10px;
    border-radius: 999px;
    color: #2d9f66;
    background: #edf8f2;
    white-space: nowrap;
}
.payment-mobile-amount {
    display: grid;
    gap: 10px;
    padding: 24px 0 8px;
    text-align: center;
}
.payment-mobile-amount span {
    color: #7d8593;
    font-size: 13px;
    font-weight: 900;
}
.payment-mobile-amount strong {
    color: #111318;
    font-size: 48px;
    line-height: 1;
    letter-spacing: 0;
}
.payment-mobile-amount small {
    justify-self: center;
    padding: 6px 10px;
    border-radius: 999px;
    color: #bf4756;
    background: #fff0f2;
    font-size: 12px;
    font-weight: 900;
}
.payment-mobile-meta {
    display: grid;
    gap: 8px;
    padding: 12px;
    border-radius: 18px;
    background: #f7f8fb;
}
.payment-mobile-meta div {
    display: grid;
    grid-template-columns: 74px minmax(0, 1fr);
    gap: 10px;
    align-items: center;
    min-height: 28px;
}
.payment-mobile-meta span {
    color: #8e96a5;
    font-size: 12px;
    font-weight: 900;
}
.payment-mobile-meta strong {
    min-width: 0;
    overflow: hidden;
    color: #222630;
    font-size: 14px;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.payment-mobile-actions {
    display: grid;
    gap: 10px;
}
.payment-mobile-actions .btn,
.payment-mobile-actions button {
    width: 100%;
    min-height: 54px;
    justify-content: center;
    border-radius: 18px;
    white-space: nowrap;
    font-size: 16px;
}
.payment-mobile-actions .btn.primary {
    color: #fff;
    background: linear-gradient(135deg, #ff7d45, #ef5d63);
    box-shadow: 0 14px 26px rgba(239, 93, 99, .22);
}
.payment-mobile-actions .btn.ghost {
    color: #5266ad;
    background: #f3f6ff;
    border-color: #e6ebff;
    box-shadow: none;
}
.payment-mobile-note {
    margin: 0;
    padding: 11px 12px;
    border-radius: 16px;
    color: #735821;
    background: #fff7e8;
    font-size: 13px;
    font-weight: 800;
    line-height: 1.55;
}
.payment-mobile-back {
    justify-self: center;
    color: #858e9d;
    font-size: 13px;
    font-weight: 900;
}
.payment-cashier-card .client-section-head h2 {
    word-break: keep-all;
}
@media (max-width: 760px), (pointer: coarse) {
    .view-payment-result.is-client:not(.view-frontend-home) .wrap {
        padding: 0 12px 48px !important;
    }
    .view-payment-result .client-topbar {
        display: none !important;
    }
    .payment-screen.has-mobile-checkout .payment-status-hero,
    .payment-screen.has-mobile-checkout .payment-cashier-card,
    .payment-screen.has-mobile-checkout .client-back-home {
        display: none !important;
    }
    .payment-screen {
        gap: 0 !important;
        min-height: calc(100vh - 48px);
        min-height: calc(100dvh - 48px);
        justify-content: center;
    }
    .payment-screen .client-card,
    .payment-screen .client-hero-card {
        border-radius: 22px !important;
        box-shadow: 0 10px 28px rgba(20, 24, 32, .08) !important;
    }
    .payment-status-hero {
        min-height: 0 !important;
        padding: 18px !important;
        gap: 12px !important;
        align-content: start !important;
        background: linear-gradient(135deg, #fff, #fff6ef) !important;
    }
    .payment-status-orb {
        right: 18px !important;
        top: 18px !important;
        width: 42px !important;
        height: 42px !important;
        border-radius: 16px !important;
        box-shadow: 0 12px 22px rgba(239, 93, 99, .18) !important;
    }
    .payment-status-hero .eyebrow,
    .payment-cashier-card .eyebrow {
        width: fit-content !important;
        padding: 7px 12px !important;
        font-size: 12px !important;
    }
    .payment-status-hero h1 {
        max-width: calc(100% - 56px) !important;
        font-size: 34px !important;
        line-height: 1.08 !important;
        letter-spacing: 0 !important;
    }
    .payment-status-hero p {
        margin: 0 !important;
        font-size: 15px !important;
        line-height: 1.55 !important;
    }
    .payment-hero-meta {
        gap: 8px !important;
    }
    .payment-hero-meta strong {
        order: -1;
        width: 100%;
        font-size: 34px !important;
        line-height: 1 !important;
        letter-spacing: 0 !important;
    }
    .payment-hero-meta span,
    .payment-hero-meta em {
        padding: 7px 10px !important;
        background: #fff !important;
    }
    .payment-cashier-card.pay-panel {
        padding: 18px !important;
        gap: 16px !important;
        border-color: rgba(239, 93, 99, .10) !important;
        background: #fff !important;
    }
    .payment-cashier-card .client-section-head {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 8px !important;
        align-items: start !important;
    }
    .payment-cashier-card .client-section-head > span {
        width: fit-content !important;
        max-width: 100% !important;
        padding: 0 !important;
        border: 0 !important;
        color: #9aa0aa !important;
        background: transparent !important;
        font-size: 13px !important;
    }
    .payment-cashier-card .client-section-head h2 {
        font-size: 26px !important;
        line-height: 1.15 !important;
        letter-spacing: 0 !important;
    }
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
    .payment-guide {
        gap: 14px !important;
    }
    .payment-guide h2 {
        font-size: 28px !important;
        line-height: 1.16 !important;
        letter-spacing: 0 !important;
    }
    .payment-guide p {
        font-size: 16px !important;
        line-height: 1.7 !important;
    }
    .payment-guide .client-action-row {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 10px !important;
    }
    .payment-guide .client-action-row .btn,
    .payment-guide .client-action-row button {
        width: 100% !important;
        min-height: 52px !important;
        justify-content: center !important;
        white-space: nowrap !important;
        font-size: 16px !important;
    }
    .payment-state-text {
        padding: 11px 12px !important;
        border-radius: 14px !important;
        font-size: 14px !important;
        line-height: 1.55 !important;
    }
    .client-back-home {
        margin-top: 0 !important;
        min-height: 46px !important;
        justify-content: center !important;
    }
    .payment-mobile-checkout {
        display: grid;
        gap: 14px;
        width: 100%;
        max-width: 420px;
        margin: 10px auto 0;
        padding: 18px;
        border: 1px solid rgba(229, 232, 238, .92);
        border-radius: 26px;
        color: #16181d;
        background: #fff;
        box-shadow: 0 18px 42px rgba(20, 24, 32, .10);
    }
    .payment-mobile-checkout-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .payment-mobile-checkout-brand {
        display: grid;
        gap: 4px;
    }
    .payment-mobile-checkout-brand span,
    .payment-mobile-checkout-secure {
        color: #8b93a1;
        font-size: 12px;
        font-weight: 900;
    }
    .payment-mobile-checkout-brand strong {
        font-size: 22px;
        line-height: 1.1;
        letter-spacing: 0;
    }
    .payment-mobile-checkout-secure {
        padding: 7px 10px;
        border-radius: 999px;
        color: #39a36f;
        background: #edf9f2;
        white-space: nowrap;
    }
    .payment-mobile-amount {
        display: grid;
        gap: 9px;
        padding: 22px 0 10px;
        text-align: center;
    }
    .payment-mobile-amount span {
        color: #8b93a1;
        font-size: 13px;
        font-weight: 900;
    }
    .payment-mobile-amount strong {
        color: #111318;
        font-size: 46px;
        line-height: 1;
        letter-spacing: 0;
    }
    .payment-mobile-amount small {
        color: #c9505d;
        font-size: 13px;
        font-weight: 900;
    }
    .payment-mobile-meta {
        display: grid;
        gap: 8px;
        padding: 12px;
        border-radius: 18px;
        background: #f7f8fb;
    }
    .payment-mobile-meta div {
        display: grid;
        grid-template-columns: 74px minmax(0, 1fr);
        gap: 10px;
        align-items: center;
        min-height: 28px;
    }
    .payment-mobile-meta span {
        color: #9299a6;
        font-size: 12px;
        font-weight: 900;
    }
    .payment-mobile-meta strong {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: #242832;
        font-size: 14px;
    }
    .payment-mobile-actions {
        display: grid;
        gap: 10px;
    }
    .payment-mobile-actions .btn,
    .payment-mobile-actions button {
        width: 100%;
        min-height: 54px;
        justify-content: center;
        border-radius: 18px;
        white-space: nowrap;
        font-size: 16px;
    }
    .payment-mobile-actions .btn.primary {
        color: #fff;
        background: linear-gradient(135deg, #ff7f45, #f05d5f);
        box-shadow: 0 14px 26px rgba(240, 93, 95, .22);
    }
    .payment-mobile-actions .btn.ghost {
        color: #5266ad;
        background: #f3f6ff;
        border-color: #e6ebff;
        box-shadow: none;
    }
    .payment-mobile-note {
        margin: 0;
        padding: 11px 12px;
        border-radius: 16px;
        color: #7a5a21;
        background: #fff7e8;
        font-size: 13px;
        font-weight: 800;
        line-height: 1.55;
    }
    .payment-mobile-back {
        justify-self: center;
        color: #8b93a1;
        font-size: 13px;
        font-weight: 900;
    }
}

/* Final mobile checkout skin: full-screen, no floating card. */
.view-payment-result.is-client,
.view-payment-result.is-client:not(.view-frontend-home) {
    background: #ffffff !important;
}
.view-payment-result.is-client:not(.view-frontend-home) .wrap {
    width: 100% !important;
    max-width: none !important;
    min-height: 100vh !important;
    min-height: 100dvh !important;
    padding: 0 !important;
}
.payment-screen.has-mobile-checkout {
    min-height: 100vh !important;
    min-height: 100dvh !important;
    place-items: stretch !important;
    align-content: stretch !important;
}
.payment-mobile-checkout {
    width: 100% !important;
    max-width: none !important;
    min-height: 100vh !important;
    min-height: 100dvh !important;
    margin: 0 !important;
    padding: max(22px, env(safe-area-inset-top)) 20px max(22px, env(safe-area-inset-bottom)) !important;
    border: 0 !important;
    border-radius: 0 !important;
    background: #ffffff !important;
    box-shadow: none !important;
    grid-template-rows: auto auto auto 1fr auto auto;
    align-content: start;
}
.payment-mobile-checkout::before {
    content: none !important;
}
.payment-mobile-checkout-head {
    padding: 2px 0 14px !important;
    border-bottom: 1px solid #f0f1f5;
}
.payment-mobile-checkout-brand {
    gap: 6px !important;
}
.payment-mobile-checkout-brand span {
    color: #8f96a3 !important;
    font-size: 13px !important;
}
.payment-mobile-checkout-brand strong {
    font-size: 24px !important;
    font-weight: 950 !important;
}
.payment-mobile-checkout-secure {
    padding: 6px 9px !important;
    border-radius: 10px !important;
    color: #1f9d63 !important;
    background: #eefaf3 !important;
    font-size: 12px !important;
}
.payment-mobile-amount {
    gap: 8px !important;
    padding: 42px 0 32px !important;
}
.payment-mobile-amount span {
    color: #7b828f !important;
    font-size: 14px !important;
}
.payment-mobile-amount strong {
    font-size: 50px !important;
    font-weight: 950 !important;
}
.payment-mobile-amount small {
    padding: 0 !important;
    color: #b24a59 !important;
    background: transparent !important;
    font-size: 14px !important;
}
.payment-mobile-meta {
    gap: 0 !important;
    padding: 0 !important;
    border-top: 1px solid #f0f1f5;
    border-bottom: 1px solid #f0f1f5;
    border-radius: 0 !important;
    background: transparent !important;
}
.payment-mobile-meta div {
    display: flex !important;
    justify-content: space-between;
    min-height: 50px !important;
    padding: 0 !important;
    border-bottom: 1px solid #f6f7fa;
}
.payment-mobile-meta div:last-child {
    border-bottom: 0;
}
.payment-mobile-meta span {
    color: #8b93a1 !important;
    font-size: 14px !important;
}
.payment-mobile-meta strong {
    color: #20242c !important;
    font-size: 15px !important;
}
.payment-mobile-actions {
    align-self: end;
    gap: 12px !important;
    padding-top: 24px;
}
.payment-mobile-actions .btn,
.payment-mobile-actions button {
    min-height: 52px !important;
    border-radius: 12px !important;
    font-size: 16px !important;
}
.payment-mobile-actions .btn.primary {
    background: #111318 !important;
    box-shadow: none !important;
}
.payment-mobile-actions .btn.ghost {
    color: #515a6a !important;
    background: #f5f6f8 !important;
    border-color: #f5f6f8 !important;
}
.payment-mobile-note {
    margin-top: 8px !important;
    padding: 0 !important;
    color: #8b7352 !important;
    background: transparent !important;
    font-size: 13px !important;
    text-align: center;
}
.payment-mobile-back {
    margin-top: 4px;
    color: #949ba8 !important;
}
</style>
<main class="client-screen payment-screen<?= (!$isPaid && !empty($gateway['payment_url'])) ? ' has-mobile-checkout' : '' ?>">
    <?php if (!$isPaid && !empty($gateway) && !empty($gateway['enabled']) && !empty($gateway['payment_url'])): ?>
        <section class="payment-mobile-checkout" data-payment-mobile-checkout>
            <div class="payment-mobile-checkout-head">
                <div class="payment-mobile-checkout-brand">
                    <span><?= $isTestOrder ? '支付通道测试' : '精秀短剧' ?></span>
                    <strong><?= htmlspecialchars($paymentMethodName) ?>收银台</strong>
                </div>
                <span class="payment-mobile-checkout-secure">安全支付</span>
            </div>
            <div class="payment-mobile-amount">
                <span><?= htmlspecialchars($orderTypeLabel) ?></span>
                <strong>￥<?= htmlspecialchars((string) ($order['amount'] ?? 0)) ?></strong>
                <small data-payment-status-text><?= htmlspecialchars($statusLabels[$displayStatus] ?? $displayStatus) ?></small>
            </div>
            <div class="payment-mobile-meta">
                <div><span>支付方式</span><strong><?= htmlspecialchars($paymentMethodName) ?></strong></div>
                <div><span>支付通道</span><strong><?= htmlspecialchars($paymentChannelName) ?></strong></div>
            </div>
            <div class="payment-mobile-actions">
                <a class="btn primary" href="<?= htmlspecialchars((string) $gateway['payment_url']) ?>" data-payment-open data-payment-url="<?= htmlspecialchars((string) $gateway['payment_url']) ?>" data-payment-method="<?= $isAlipayPayment ? 'alipay' : 'default' ?>">立即支付</a>
                <button class="btn ghost" type="button" data-check-payment>我已支付，查询结果</button>
            </div>
            <p class="payment-mobile-note" data-payment-state><?= $isTestOrder ? '付款后会自动查询测试订单状态。' : '付款后返回本页，会自动查询并发放权益。' ?></p>
            <a class="payment-mobile-back" href="<?= $isTestOrder ? '/jxdjadmin#payment-channel' : '/?route=home' ?>"><?= $isTestOrder ? '返回支付通道' : '返回首页' ?></a>
        </section>
    <?php endif; ?>

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
                    <h2><?= empty($gateway['error']) ? htmlspecialchars($paymentMethodName) . '收银台' : '支付下单未完成' ?></h2>
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
                            <a class="btn primary" href="<?= htmlspecialchars((string) $gateway['payment_url']) ?>" data-payment-open data-payment-url="<?= htmlspecialchars((string) $gateway['payment_url']) ?>" data-payment-method="<?= $isAlipayPayment ? 'alipay' : 'default' ?>">立即支付</a>
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
    const payOpenLinks = Array.from(document.querySelectorAll('[data-payment-open]'));
    const paidActions = document.querySelector('[data-paid-actions]');
    const cashierCard = document.querySelector('[data-payment-cashier-card]');
    const modernCheckout = document.querySelector('[data-payment-mobile-checkout]');
    const mobilePaymentUrl = cashierCard?.dataset.mobilePaymentUrl || payOpenLinks[0]?.dataset.paymentUrl || '';
    const isAlipayPayment = <?= $isAlipayPayment ? 'true' : 'false' ?>;
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
        if (modernCheckout) {
            modernCheckout.style.display = 'none';
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

    const openPaymentUrl = (paymentUrl) => {
        if (!paymentUrl) {
            return;
        }
        if (!isAlipayPayment || !isMobileBrowser) {
            window.location.href = paymentUrl;
            return;
        }

        setState('正在打开支付宝...');
        const encodedPayUrl = encodeURIComponent(paymentUrl);
        const schemes = [
            `alipays://platformapi/startapp?appId=20000067&url=${encodedPayUrl}`,
            `alipays://platformapi/startapp?saId=10000007&clientVersion=3.7.0.0718&qrcode=${encodedPayUrl}`
        ];
        let opened = false;
        const markOpened = () => {
            opened = true;
        };
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                markOpened();
            }
        }, { once: true });
        window.addEventListener('pagehide', markOpened, { once: true });

        window.location.href = schemes[0];
        setTimeout(() => {
            if (!opened && document.visibilityState === 'visible') {
                window.location.href = schemes[1];
            }
        }, 900);
        setTimeout(() => {
            if (!opened && document.visibilityState === 'visible') {
                window.location.href = paymentUrl;
            }
        }, 1800);
    };

    payOpenLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const paymentUrl = link.dataset.paymentUrl || link.href || '';
            if (!paymentUrl || (!isAlipayPayment && !isMobileBrowser)) {
                return;
            }
            event.preventDefault();
            openPaymentUrl(paymentUrl);
        });
    });

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
                openPaymentUrl(mobilePaymentUrl);
            }, 450);
        }
    }
    pollingTimer = setInterval(() => checkPayment(false), 10000);
    setTimeout(() => checkPayment(false), 1500);
})();
</script>
<?php endif; ?>
