<?php
$title = '剧场 - 精秀短剧';
$dramas = array_values((array) ($dramas ?? []));
$categories = array_values((array) ($categories ?? ['全部']));
$category = (string) ($category ?? '全部');
$cover = static fn (array $drama): string => (string) (($drama['cover'] ?? '') ?: '/assets/cover-1.svg');
$episodeCount = static fn (array $drama): int => count((array) ($drama['episodes'] ?? []));
$activeTab = 'juchang';
?>
<main class="duanju-app">
    <section class="duanju-page-title">
        <span>JX Theater</span>
        <h1>短剧剧场</h1>
        <p>按题材快速筛选，找到下一部想追的短剧。</p>
    </section>

    <div class="duanju-category-strip">
        <?php foreach ($categories as $item): ?>
            <?php $item = (string) $item; ?>
            <a class="<?= $item === $category ? 'is-active' : '' ?>" href="/juchang?category=<?= rawurlencode($item) ?>"><?= htmlspecialchars($item) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($dramas)): ?>
        <section class="duanju-empty">
            <strong>当前分类暂无短剧</strong>
            <p>可以切换其他分类，或去后台添加剧集并设置分类。</p>
            <a class="btn primary" href="/duanju">返回推荐</a>
        </section>
    <?php else: ?>
        <section class="duanju-grid">
            <?php foreach ($dramas as $drama): ?>
                <a class="duanju-grid-card" href="/yulan/id/<?= (int) $drama['id'] ?>">
                    <span>
                        <img src="<?= htmlspecialchars($cover($drama)) ?>" alt="">
                        <b><?= htmlspecialchars((string) ($drama['category'] ?? '短剧')) ?></b>
                    </span>
                    <strong><?= htmlspecialchars((string) ($drama['title'] ?? '')) ?></strong>
                    <em><?= number_format($episodeCount($drama)) ?>集 · 单集<?= number_format((int) ($drama['episode_coin_price'] ?? 0)) ?>K币</em>
                </a>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/duanju_nav.php'; ?>
