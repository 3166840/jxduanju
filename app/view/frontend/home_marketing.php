<?php $title = '首页 - 精秀短剧'; ?>
<div class="hero">
    <div class="hero-content">
        <div class="tag">精秀短剧 H5 · 游客试看 · 支付即解锁</div>
        <h1>把爆款短剧装进精秀短剧</h1>
        <p>支持试看、按集解锁、会员畅看和后台运营配置。先用 H5 跑通闭环，后续可继续扩展微信登录、分销和推荐算法。</p>
        <div class="hero-actions">
            <a class="btn primary" href="/?route=drama&id=1">开始追剧</a>
            <a class="btn ghost" href="/?route=center">查看我的权益</a>
        </div>
        <div class="metric-row">
            <div class="metric"><strong><?= number_format((int) ($stats['views'] ?? 0)) ?></strong><span>累计播放</span></div>
            <div class="metric"><strong><?= number_format((int) ($stats['orders'] ?? 0)) ?></strong><span>成交订单</span></div>
            <div class="metric"><strong>￥<?= htmlspecialchars((string) ($stats['revenue'] ?? 0)) ?></strong><span>模拟收入</span></div>
        </div>
    </div>
</div>

<div class="section-title">
    <h2>运营推荐</h2>
    <span class="muted">后台可配置 Banner</span>
</div>
<div class="panel soft stack">
    <?php foreach ($banners as $banner): ?>
        <a class="banner-strip" href="<?= htmlspecialchars($banner['link']) ?>">
            <span>
                <span class="eyebrow">今日主推</span>
                <h3><?= htmlspecialchars($banner['title']) ?></h3>
                <span class="muted"><?= htmlspecialchars($banner['subtitle']) ?></span>
            </span>
            <span class="btn ghost">去看看</span>
        </a>
    <?php endforeach; ?>
</div>

<div class="section-title">
    <h2>正在热播</h2>
    <span class="muted">按集付费 / 会员畅看</span>
</div>
<div class="grid">
    <?php foreach ($dramas as $drama): ?>
        <div class="card drama-card">
            <img src="<?= htmlspecialchars($drama['cover']) ?>" alt="">
            <span class="pill jade"><?= htmlspecialchars($drama['status']) ?></span>
            <h3><?= htmlspecialchars($drama['title']) ?></h3>
            <p class="muted"><?= htmlspecialchars($drama['description']) ?></p>
            <div class="price-line">
                <span class="pill">单集 ￥<?= htmlspecialchars((string) $drama['price_per_episode']) ?></span>
                <span class="pill ember">会员 ￥<?= htmlspecialchars((string) $drama['membership_price']) ?></span>
            </div>
            <div class="card-actions">
                <a class="btn primary" href="/?route=drama&id=<?= (int) $drama['id'] ?>">立即观看</a>
                <a class="btn ghost" href="/?route=buy-membership&drama_id=<?= (int) $drama['id'] ?>">开会员</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>
