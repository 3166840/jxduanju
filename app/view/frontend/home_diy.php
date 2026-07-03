<?php
$title = '首页 - 精秀短剧';
$designHome = $design_config['home'] ?? [];
$featuredDrama = $dramas[0] ?? null;
$banner = $banners[0] ?? [
    'title' => $featuredDrama['title'] ?? '精秀短剧',
    'subtitle' => $featuredDrama['description'] ?? '热播短剧免费看',
    'link' => $featuredDrama ? '/?route=drama&id=' . (int) $featuredDrama['id'] : '/?route=home',
];
$rankDramas = array_values($dramas);
if (!empty($rankDramas)) {
    $rankSource = $rankDramas;
    for ($i = count($rankDramas); $i < 6; $i++) {
        $rankDramas[] = $rankSource[$i % count($rankSource)];
    }
}
$modules = array_flip(array_map('strval', (array) ($designHome['modules'] ?? [])));
$isModuleEnabled = static fn (string $module): bool => isset($modules[$module]);
$color = static function (string $value, string $fallback): string {
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? $value : $fallback;
};
$primaryColor = $color((string) ($designHome['primary_color'] ?? '#ef5b5f'), '#ef5b5f');
$accentColor = $color((string) ($designHome['accent_color'] ?? '#ff955d'), '#ff955d');
$brandTitle = (string) ($designHome['brand_title'] ?? '精秀短剧');
$heroTitle = (string) ($designHome['hero_title'] ?? ($banner['title'] ?? '精秀短剧'));
$heroSubtitle = (string) ($designHome['hero_subtitle'] ?? ($banner['subtitle'] ?? '短剧追不停'));
$quickNavs = array_values((array) ($designHome['quick_navs'] ?? []));
$episodeCount = static fn (array $drama): int => count($drama['episodes'] ?? []);
$cover = static fn (?array $drama): string => (string) (($drama['cover'] ?? '') ?: '/assets/cover-1.svg');
?>
<main class="mini-home diy-home" style="--diy-primary: <?= htmlspecialchars($primaryColor) ?>; --diy-accent: <?= htmlspecialchars($accentColor) ?>;">
    <section class="mini-top">
        <div>
            <h1><?= htmlspecialchars($brandTitle) ?></h1>
        </div>
        <div class="mini-capsule" aria-label="小程序操作">
            <span></span><span></span><span></span>
            <i></i>
        </div>
    </section>

    <?php if ($isModuleEnabled('search')): ?>
        <form class="mini-search diy-search" action="/" method="get">
            <input type="hidden" name="route" value="home">
            <span class="mini-flame" aria-hidden="true"></span>
            <input name="keyword" placeholder="<?= htmlspecialchars((string) ($designHome['search_placeholder'] ?? '请搜索您感兴趣的剧...')) ?>">
            <button type="submit">搜索</button>
        </form>
    <?php endif; ?>

    <?php if ($isModuleEnabled('banner')): ?>
        <a class="mini-hero-banner diy-hero-banner" href="<?= htmlspecialchars((string) ($banner['link'] ?? '/')) ?>">
            <img src="<?= htmlspecialchars($cover($featuredDrama)) ?>" alt="">
            <span class="mini-hero-shade"></span>
            <span class="mini-hero-copy">
                <small>DIY 推荐</small>
                <strong><?= htmlspecialchars($heroTitle) ?></strong>
                <em><?= htmlspecialchars($heroSubtitle) ?></em>
            </span>
            <span class="mini-dots"><i class="active"></i><i></i><i></i></span>
        </a>
    <?php endif; ?>

    <?php if ($isModuleEnabled('quick_nav') && !empty($quickNavs)): ?>
        <section class="diy-quick-grid">
            <?php foreach (array_slice($quickNavs, 0, 4) as $item): ?>
                <a href="<?= htmlspecialchars((string) ($item['link'] ?? '#')) ?>">
                    <span><?= jx_icon('drama') ?></span>
                    <strong><?= htmlspecialchars((string) ($item['label'] ?? '导航')) ?></strong>
                </a>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($isModuleEnabled('notice')): ?>
        <section class="mini-add-card diy-notice">
            <strong><?= htmlspecialchars((string) ($designHome['notice_text'] ?? '添加到「我的小程序」追剧更方便')) ?></strong>
            <a href="/?route=center">去添加</a>
            <button type="button" aria-label="关闭添加提示">×</button>
        </section>
    <?php endif; ?>

    <?php if ($isModuleEnabled('rank')): ?>
        <section class="mini-rank-card" id="rank">
            <header class="mini-rank-tabs">
                <strong><span class="mini-crown"></span>DIY 热榜</strong>
                <span>热播榜</span>
                <span>新剧榜</span>
                <a href="#all-dramas">完整榜单</a>
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
    <?php endif; ?>

    <?php if ($isModuleEnabled('drama_grid')): ?>
        <section class="mini-section" id="all-dramas">
            <header>
                <h2><span class="mini-tv"></span><?= htmlspecialchars((string) ($designHome['section_title'] ?? '全部剧集')) ?></h2>
                <a href="/?route=center">我的权益</a>
            </header>
            <div class="mini-drama-grid">
                <?php foreach (array_values($dramas) as $drama): ?>
                    <a class="mini-drama-card" href="/?route=drama&id=<?= (int) $drama['id'] ?>">
                        <img src="<?= htmlspecialchars($cover($drama)) ?>" alt="">
                        <strong><?= htmlspecialchars($drama['title']) ?></strong>
                        <span>共<?= number_format($episodeCount($drama)) ?>集 · 单集 ￥<?= htmlspecialchars((string) $drama['price_per_episode']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($isModuleEnabled('reward')): ?>
        <a class="mini-float-reward diy-float-reward" href="/?route=center">
            <strong>领币看剧</strong>
            <span>DIY 福利</span>
        </a>
    <?php endif; ?>
</main>

<?php if ($isModuleEnabled('bottom_nav')): ?>
    <nav class="mini-bottom-nav diy-bottom-nav" aria-label="首页底部导航">
        <a class="is-active" href="/?route=home"><?= jx_icon('home') ?><span>推荐</span></a>
        <a href="#all-dramas"><?= jx_icon('drama') ?><span>小剧场</span></a>
        <a href="/?route=bind"><?= jx_icon('revenue') ?><span>福利</span><i></i></a>
        <a href="/?route=center"><?= jx_icon('user') ?><span>我的</span></a>
    </nav>
<?php endif; ?>
