<?php
$title = '短剧首页 - 精秀短剧';
$dramas = array_values((array) ($dramas ?? []));
$hotDramas = array_values((array) ($hot_dramas ?? $dramas));
$newDramas = array_values((array) ($new_dramas ?? $dramas));
$featured = $hotDramas[0] ?? ($dramas[0] ?? null);
$cover = static fn (?array $drama): string => (string) (($drama['cover'] ?? '') ?: '/assets/cover-1.svg');
$episodeCount = static fn (array $drama): int => count((array) ($drama['episodes'] ?? []));
$firstEpisodeId = static fn (array $drama): int => (int) (($drama['episodes'][0]['id'] ?? 0) ?: ((int) ($drama['id'] ?? 1) * 100 + 1));
$activeTab = 'duanju';
?>
<main class="duanju-app">
    <section class="duanju-head">
        <div>
            <span>精选短剧</span>
            <h1>精秀短剧</h1>
        </div>
        <a href="/denglu"><?= empty($user['phone']) ? '登录' : '已登录' ?></a>
    </section>

    <form class="duanju-search" action="/juchang" method="get">
        <span><?= jx_icon('stats') ?></span>
        <input name="keyword" placeholder="搜索短剧名、分类、热播关键词">
        <button type="submit">搜索</button>
    </form>

    <?php if ($featured): ?>
        <a class="duanju-hero" href="/yulan/id/<?= (int) $featured['id'] ?>">
            <img src="<?= htmlspecialchars($cover($featured)) ?>" alt="">
            <span class="duanju-hero-shade"></span>
            <span class="duanju-hero-copy">
                <em><?= htmlspecialchars((string) ($featured['category'] ?? '热播')) ?> · 热播中</em>
                <strong><?= htmlspecialchars((string) ($featured['title'] ?? '')) ?></strong>
                <small><?= htmlspecialchars((string) ($featured['description'] ?? '')) ?></small>
                <b>立即观看</b>
            </span>
        </a>
    <?php endif; ?>

    <section class="duanju-wallet">
        <a href="/huiyuan">
            <strong><?= !empty($user['membership']) ? 'VIP 已开通' : '开通 VIP' ?></strong>
            <span><?= !empty($user['membership_expires_at']) ? htmlspecialchars((string) $user['membership_expires_at']) . ' 到期' : '畅看付费短剧' ?></span>
        </a>
        <a href="/wode">
            <strong><?= number_format((int) ($user['coin_balance'] ?? 0) + (int) ($user['bonus_coin_balance'] ?? 0)) ?> K币</strong>
            <span>余额与流水</span>
        </a>
    </section>

    <section class="duanju-section">
        <header>
            <h2>热门推荐</h2>
            <a href="/juchang">全部</a>
        </header>
        <div class="duanju-scroll-row">
            <?php foreach (array_slice($hotDramas, 0, 8) as $index => $drama): ?>
                <a class="duanju-poster-card" href="/yulan/id/<?= (int) $drama['id'] ?>">
                    <span class="duanju-poster">
                        <img src="<?= htmlspecialchars($cover($drama)) ?>" alt="">
                        <b><?= $index < 3 ? 'TOP ' . ($index + 1) : htmlspecialchars((string) ($drama['category'] ?? '短剧')) ?></b>
                    </span>
                    <strong><?= htmlspecialchars((string) ($drama['title'] ?? '')) ?></strong>
                    <em><?= number_format($episodeCount($drama)) ?>集 · <?= number_format((int) ($drama['views'] ?? 0)) ?>热度</em>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="duanju-section">
        <header>
            <h2>新剧上线</h2>
            <a href="/juchang?category=全部">换一批</a>
        </header>
        <div class="duanju-list">
            <?php foreach (array_slice($newDramas, 0, 10) as $drama): ?>
                <a class="duanju-list-card" href="/yulan/id/<?= (int) $drama['id'] ?>">
                    <img src="<?= htmlspecialchars($cover($drama)) ?>" alt="">
                    <span>
                        <strong><?= htmlspecialchars((string) ($drama['title'] ?? '')) ?></strong>
                        <em><?= htmlspecialchars((string) ($drama['category'] ?? '短剧')) ?> · 共<?= number_format($episodeCount($drama)) ?>集 · 前<?= number_format((int) ($drama['free_episode_count'] ?? 1)) ?>集免费</em>
                        <small><?= htmlspecialchars((string) ($drama['description'] ?? '')) ?></small>
                    </span>
                    <b>播放</b>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/duanju_nav.php'; ?>
