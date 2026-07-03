<?php
$landing = (array) ($landing ?? []);
$page = (array) ($landing['page'] ?? []);
$content = (array) ($landing['content'] ?? []);
$points = array_values((array) ($landing['selling_points'] ?? []));
$title = (string) ($landing['title'] ?? ($page['title'] ?? '精选内容'));
$subtitle = (string) ($landing['subtitle'] ?? ($page['subtitle'] ?? ''));
$cover = (string) (($landing['cover'] ?? '') ?: '/assets/cover-1.svg');
$clickUrl = (string) (($landing['click_url'] ?? '') ?: '/duanju');
$ctaText = (string) (($page['cta_text'] ?? '') ?: '立即观看');
$badge = (string) (($page['badge'] ?? '') ?: ((string) ($landing['content_type'] ?? 'drama') === 'novel' ? '小说推荐' : '短剧热播'));
?>
<section class="mini-hero landing-page-hero">
    <div>
        <span class="eyebrow"><?= htmlspecialchars($badge) ?></span>
        <h1><?= htmlspecialchars($title) ?></h1>
        <p><?= htmlspecialchars($subtitle !== '' ? $subtitle : '精选爆款内容，点击后自动进入播放/阅读页。') ?></p>
        <div class="hero-actions">
            <a class="btn primary" href="<?= htmlspecialchars($clickUrl) ?>"><?= htmlspecialchars($ctaText) ?></a>
            <a class="btn ghost" href="/duanju">返回首页</a>
        </div>
    </div>
    <img src="<?= htmlspecialchars($cover) ?>" alt="<?= htmlspecialchars($title) ?>">
</section>

<section class="panel">
    <div class="section-title" style="margin-top:0">
        <h2>内容亮点</h2>
        <span class="muted"><?= htmlspecialchars((string) ($content['category'] ?? '精选')) ?></span>
    </div>
    <div class="feature-grid">
        <?php foreach ($points as $point): ?>
            <article>
                <strong><?= htmlspecialchars((string) $point) ?></strong>
                <p>进入后可继续观看或阅读，支付权益会自动发放到当前账号。</p>
            </article>
        <?php endforeach; ?>
        <?php if (empty($points)): ?>
            <article>
                <strong>极速进入</strong>
                <p>点击后直达绑定内容，减少投放链路跳失。</p>
            </article>
        <?php endif; ?>
    </div>
</section>
