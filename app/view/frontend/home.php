<?php
$title = '首页 - 精秀短剧';
$homepageTemplate = (string) ($homepage_template ?? ($site_config['homepage_template'] ?? 'mini'));
if ($homepageTemplate === 'diy') {
    require __DIR__ . '/home_diy.php';
    return;
}
if ($homepageTemplate === 'marketing') {
    require __DIR__ . '/home_marketing.php';
    return;
}

$homeDramas = array_values((array) ($home_dramas ?? []));
$featuredDrama = $homeDramas[0] ?? ($dramas[0] ?? null);
$banner = $banners[0] ?? [
    'title' => $featuredDrama['title'] ?? '精秀短剧',
    'subtitle' => $featuredDrama['description'] ?? '热播短剧免费看',
    'link' => $featuredDrama ? '/?route=drama&id=' . (int) $featuredDrama['id'] : '/?route=home',
];
$rankDramas = $homeDramas;
if (!empty($rankDramas)) {
    $rankSource = $rankDramas;
    for ($i = count($rankDramas); $i < 6; $i++) {
        $rankDramas[] = $rankSource[$i % count($rankSource)];
    }
}
$displayDramas = $homeDramas;
$episodeCount = static fn (array $drama): int => count($drama['episodes'] ?? []);
$cover = static fn (?array $drama): string => (string) (($drama['cover'] ?? '') ?: '/assets/cover-1.svg');
?>
<main class="mini-home">
    <section class="mini-top">
        <div>
            <h1>精秀短剧</h1>
        </div>
        <div class="mini-capsule" aria-label="小程序操作">
            <span></span><span></span><span></span>
            <i></i>
        </div>
    </section>

    <form class="mini-search" action="/" method="get">
        <input type="hidden" name="route" value="home">
        <span class="mini-flame" aria-hidden="true"></span>
        <input name="keyword" placeholder="请搜索您感兴趣的剧...">
        <button type="submit">搜索</button>
    </form>

    <a class="mini-hero-banner" href="<?= htmlspecialchars((string) ($banner['link'] ?? '/')) ?>">
        <img src="<?= htmlspecialchars($cover($featuredDrama)) ?>" alt="">
        <span class="mini-hero-shade"></span>
        <span class="mini-hero-copy">
            <small>今日热播</small>
            <strong><?= htmlspecialchars((string) ($banner['title'] ?? '精秀短剧')) ?></strong>
            <em><?= htmlspecialchars((string) ($banner['subtitle'] ?? '短剧追不停')) ?></em>
        </span>
        <span class="mini-dots"><i class="active"></i><i></i><i></i></span>
    </a>

    <section class="mini-add-card">
        <strong>添加到「我的小程序」追剧更方便</strong>
        <a href="/?route=center">去添加</a>
        <button type="button" aria-label="关闭添加提示">×</button>
    </section>

    <section class="mini-rank-card">
        <header class="mini-rank-tabs">
            <strong><span class="mini-crown"></span>必看榜</strong>
            <span>热播榜</span>
            <span>新剧榜</span>
            <a href="/duanju">全部</a>
        </header>
        <?php if (empty($rankDramas)): ?>
            <p class="muted">暂无短剧，去后台添加第一部短剧吧。</p>
        <?php else: ?>
            <div class="mini-rank-list">
                <?php foreach (array_slice($rankDramas, 0, 6) as $index => $drama): ?>
                    <a class="mini-rank-item" href="/?route=drama&id=<?= (int) $drama['id'] ?>">
                        <span class="mini-rank-cover">
                            <img src="<?= htmlspecialchars($cover($drama)) ?>" alt="">
                            <b class="<?= $index < 3 ? 'top' : '' ?>"><?= $index < 3 ? 'TOP ' : '' ?><?= $index + 1 ?></b>
                        </span>
                        <span>
                            <strong><?= htmlspecialchars($drama['title']) ?></strong>
                            <em>更新中 · 共<?= number_format($episodeCount($drama)) ?>集</em>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="mini-section" id="all-dramas">
        <header>
            <h2><span class="mini-tv"></span>推荐剧集</h2>
            <a href="/?route=center">我的权益</a>
        </header>
        <div class="mini-drama-grid">
            <?php foreach ($displayDramas as $drama): ?>
                <a class="mini-drama-card" href="/?route=drama&id=<?= (int) $drama['id'] ?>">
                    <img src="<?= htmlspecialchars($cover($drama)) ?>" alt="">
                    <strong><?= htmlspecialchars($drama['title']) ?></strong>
                    <span>共<?= number_format($episodeCount($drama)) ?>集 · 单集 ￥<?= htmlspecialchars((string) $drama['price_per_episode']) ?></span>
                </a>
            <?php endforeach; ?>
            <?php if (empty($displayDramas)): ?>
                <p class="muted">暂无推荐短剧，去后台添加第一部短剧吧。</p>
            <?php endif; ?>
        </div>
    </section>

    <a class="mini-float-reward" href="/?route=center">
        <strong>领币看剧</strong>
        <span>看剧红包</span>
    </a>
</main>

<nav class="mini-bottom-nav" aria-label="首页底部导航">
    <a class="is-active" href="/?route=home"><?= jx_icon('home') ?><span>推荐</span></a>
    <a href="/duanju"><?= jx_icon('drama') ?><span>小剧场</span></a>
    <a href="/?route=bind"><?= jx_icon('revenue') ?><span>福利</span><i></i></a>
    <a href="/?route=center"><?= jx_icon('user') ?><span>我的</span></a>
</nav>
