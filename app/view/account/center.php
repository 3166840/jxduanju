<?php
$title = '个人中心 - 精秀短剧';
$orders = array_values((array) ($orders ?? []));
$entitlements = array_values((array) ($entitlements ?? []));
$paidCount = count(array_filter($orders, static fn (array $order): bool => in_array((string) ($order['status'] ?? ''), ['paid', 'partial_refunded', 'refunded'], true)));
$pendingCount = count(array_filter($orders, static fn (array $order): bool => (string) ($order['status'] ?? '') === 'pending'));
$coinBalance = (int) ($user['coin_balance'] ?? 0) + (int) ($user['bonus_coin_balance'] ?? 0);
$isMember = !empty($user['membership']);
$membershipText = $isMember ? 'VIP 已开通' : '未开通';
$membershipHint = $isMember && !empty($user['membership_expires_at'])
    ? '有效期至 ' . (string) $user['membership_expires_at']
    : '开通后可享会员权益';
$statusLabels = [
    'pending' => '待支付',
    'paid' => '已支付',
    'partial_refunded' => '部分退款',
    'refunded' => '已退款',
    'refund_pending' => '退款中',
    'refund_failed' => '退款失败',
    'failed' => '支付失败',
    'closed' => '已关闭',
    'expired' => '已过期',
];
$statusTone = static function (string $status): string {
    return match ($status) {
        'paid' => 'paid',
        'partial_refunded', 'refunded' => 'refund',
        'failed', 'closed', 'expired', 'refund_failed' => 'failed',
        default => 'pending',
    };
};
$orderTypeLabels = [
    'membership' => '会员',
    'episode' => '分集',
    'episode_unlock' => '分集',
    'drama_unlock' => '整剧',
    'coin_recharge' => 'K币充值',
    'novel_unlock' => '整本小说',
    'novel_chapter_unlock' => '小说章节',
];
$entitlementTypeLabels = [
    'membership' => '会员权益',
    'vip_week' => '周卡会员',
    'vip_month' => '月卡会员',
    'episode' => '分集权益',
    'episode_unlock' => '分集权益',
    'drama_unlock' => '整剧权益',
    'novel_unlock' => '整本小说',
    'novel_chapter_unlock' => '章节权益',
];
$avatarSource = (string) (($user['nickname'] ?? '') ?: '精');
$avatarText = function_exists('mb_substr') ? mb_substr($avatarSource, 0, 1, 'UTF-8') : substr($avatarSource, 0, 1);
$phoneText = trim((string) ($user['phone'] ?? '')) ?: '未绑定手机号';
$quickActions = [
    ['label' => '继续追剧', 'hint' => '查看追剧记录', 'href' => '/zhuiju', 'icon' => 'orders'],
    ['label' => '会员中心', 'hint' => '开通/续费', 'href' => '/huiyuan', 'icon' => 'revenue'],
    ['label' => '绑定账号', 'hint' => '保存手机号', 'href' => '/?route=bind', 'icon' => 'account'],
    ['label' => '返回首页', 'hint' => '继续浏览', 'href' => '/?route=home', 'icon' => 'home'],
];
?>
<main class="client-screen account-screen account-center-page">
    <section class="account-center-hero">
        <div class="account-center-hero-main">
            <span class="account-avatar"><?= htmlspecialchars($avatarText) ?></span>
            <div>
                <span class="eyebrow">个人中心</span>
                <h1><?= htmlspecialchars((string) (($user['nickname'] ?? '') ?: '游客用户')) ?></h1>
                <p><?= htmlspecialchars($phoneText) ?></p>
            </div>
        </div>
        <div class="account-center-hero-actions">
            <a class="btn primary" href="/?route=bind"><?= empty($user['phone']) ? '立即绑定' : '编辑资料' ?></a>
            <a class="btn ghost" href="/?route=home">去追剧</a>
        </div>
    </section>

    <section class="account-balance-card">
        <div>
            <span>K币余额</span>
            <strong><?= number_format($coinBalance) ?></strong>
            <em>可用于解锁短剧/小说内容</em>
        </div>
        <div>
            <span>会员状态</span>
            <strong><?= htmlspecialchars($membershipText) ?></strong>
            <em><?= htmlspecialchars($membershipHint) ?></em>
        </div>
    </section>

    <section class="account-stat-grid">
        <span><strong><?= number_format(count($entitlements)) ?></strong><em>权益数量</em></span>
        <span><strong><?= number_format(count($orders)) ?></strong><em>订单总数</em></span>
        <span><strong><?= number_format($paidCount) ?></strong><em>已支付</em></span>
        <span><strong><?= number_format($pendingCount) ?></strong><em>待支付</em></span>
    </section>

    <section class="account-shortcut-grid">
        <?php foreach ($quickActions as $action): ?>
            <a href="<?= htmlspecialchars($action['href']) ?>">
                <?= jx_icon($action['icon']) ?>
                <span>
                    <strong><?= htmlspecialchars($action['label']) ?></strong>
                    <em><?= htmlspecialchars($action['hint']) ?></em>
                </span>
            </a>
        <?php endforeach; ?>
    </section>

    <section class="client-card account-section-card">
        <header class="client-section-head">
            <div>
                <span class="eyebrow">权益</span>
                <h2>我的权益</h2>
            </div>
            <span><?= number_format(count($entitlements)) ?> 项</span>
        </header>
        <?php if (empty($entitlements)): ?>
            <div class="client-empty-state account-empty-state">
                <strong>暂无权益</strong>
                <p>购买分集、整剧或开通会员后，权益会自动出现在这里。</p>
                <a class="btn primary" href="/?route=home">去追剧</a>
            </div>
        <?php else: ?>
            <div class="account-list">
                <?php foreach (array_slice($entitlements, 0, 8) as $item): ?>
                    <?php
                        $type = (string) ($item['type'] ?? '权益');
                        $contentType = (string) ($item['content_type'] ?? 'drama') === 'novel' ? '小说' : '短剧';
                        $contentId = (int) (($item['content_type'] ?? 'drama') === 'novel' ? ($item['novel_id'] ?? 0) : ($item['drama_id'] ?? 0));
                        $unitId = (int) (($item['content_type'] ?? 'drama') === 'novel' ? ($item['chapter_id'] ?? 0) : ($item['episode_id'] ?? 0));
                        $target = $contentType . ($contentId > 0 ? ' ' . $contentId : '');
                        if ($unitId > 0) {
                            $target .= ' · ' . ($contentType === '小说' ? '章节 ' : '剧集 ') . $unitId;
                        }
                    ?>
                    <div class="account-list-row">
                        <div>
                            <span><?= htmlspecialchars($target) ?></span>
                            <strong><?= htmlspecialchars($entitlementTypeLabels[$type] ?? $type) ?></strong>
                            <em><?= htmlspecialchars((string) ($item['granted_at'] ?? '')) ?></em>
                        </div>
                        <b>可用</b>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="client-card account-section-card">
        <header class="client-section-head">
            <div>
                <span class="eyebrow">订单</span>
                <h2>我的订单</h2>
            </div>
            <span><?= number_format($pendingCount) ?> 笔待支付</span>
        </header>
        <?php if (empty($orders)): ?>
            <div class="client-empty-state account-empty-state">
                <strong>暂无订单</strong>
                <p>你下单后的支付记录会展示在这里。</p>
            </div>
        <?php else: ?>
            <div class="account-list">
                <?php foreach (array_slice($orders, 0, 10) as $order): ?>
                    <?php
                        $status = (string) ($order['status'] ?? 'pending');
                        $type = (string) ($order['type'] ?? '');
                        $orderNo = (string) ($order['order_no'] ?? '');
                    ?>
                    <div class="account-list-row order-row">
                        <div>
                            <span><?= htmlspecialchars($orderNo !== '' ? $orderNo : '订单记录') ?></span>
                            <strong>￥<?= number_format((float) ($order['amount'] ?? 0), 2) ?></strong>
                            <em><?= htmlspecialchars($orderTypeLabels[$type] ?? ($type ?: '订单')) ?><?= !empty($order['created_at']) ? ' · ' . htmlspecialchars((string) $order['created_at']) : '' ?></em>
                        </div>
                        <b class="<?= htmlspecialchars($statusTone($status)) ?>"><?= htmlspecialchars($statusLabels[$status] ?? $status) ?></b>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
