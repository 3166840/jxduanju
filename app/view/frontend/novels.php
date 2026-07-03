<?php
$title = '小说书城 - 精秀短剧';
$novels = array_values((array) ($novels ?? []));
$user = (array) ($user ?? []);
$novelTemplate = (string) ($novel_homepage_template ?? ($site_config['novel_homepage_template'] ?? 'library'));
if (!in_array($novelTemplate, ['library', 'ranking'], true)) {
    $novelTemplate = 'library';
}
$cover = static fn (array $novel): string => (string) (($novel['cover'] ?? '') ?: '/assets/cover-1.svg');
$chapterCount = static fn (array $novel): int => count((array) ($novel['chapters'] ?? []));
$topNovels = array_values($novels);
if (!empty($topNovels)) {
    $topSource = $topNovels;
    for ($i = count($topNovels); $i < 5; $i++) {
        $topNovels[] = $topSource[$i % count($topSource)];
    }
}
$activeTab = 'novels';
?>
<main class="client-screen novel-home novel-home-<?= htmlspecialchars($novelTemplate) ?>">
    <?php if ($novelTemplate === 'ranking'): ?>
        <?php $heroNovel = $topNovels[0] ?? null; ?>
        <section class="client-hero-card novel-rank-hero">
            <div>
                <span class="eyebrow">Hot Reading</span>
                <h1>热读榜单</h1>
                <p>按热度推荐可投流小说，首屏突出榜单、章节和解锁价格。</p>
            </div>
            <?php if ($heroNovel): ?>
                <a class="novel-hero-book" href="/?route=novel&id=<?= (int) ($heroNovel['id'] ?? 0) ?>">
                    <img src="<?= htmlspecialchars($cover($heroNovel)) ?>" alt="">
                    <span>
                        <small>TOP 1</small>
                        <strong><?= htmlspecialchars((string) ($heroNovel['title'] ?? '未命名小说')) ?></strong>
                        <em><?= htmlspecialchars((string) ($heroNovel['category'] ?? '小说')) ?> · <?= number_format($chapterCount($heroNovel)) ?> 章</em>
                    </span>
                </a>
            <?php endif; ?>
        </section>

        <section class="client-card">
            <header class="client-section-head">
                <div>
                    <span class="eyebrow">榜单导读</span>
                    <h2>优先推荐</h2>
                </div>
                <span><?= number_format(count($novels)) ?> 本</span>
            </header>
            <?php if (empty($novels)): ?>
                <p class="muted">暂无上架小说，请先到后台内容管理添加。</p>
            <?php else: ?>
                <div class="novel-rank-list">
                    <?php foreach (array_slice($topNovels, 0, 8) as $index => $novel): ?>
                        <a class="novel-rank-row" href="/?route=novel&id=<?= (int) ($novel['id'] ?? 0) ?>">
                            <b><?= $index + 1 ?></b>
                            <img src="<?= htmlspecialchars($cover($novel)) ?>" alt="">
                            <span>
                                <strong><?= htmlspecialchars((string) ($novel['title'] ?? '未命名小说')) ?></strong>
                                <em><?= htmlspecialchars((string) ($novel['category'] ?? '小说')) ?> · <?= number_format($chapterCount($novel)) ?> 章 · <?= number_format((int) ($novel['chapter_coin_price'] ?? 0)) ?>K币/章</em>
                            </span>
                            <i>阅读</i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="client-hero-card">
            <span class="eyebrow">Novel Library</span>
            <h1>小说书城</h1>
            <p>小说和短剧共用投放、充值、会员和回收统计，适合先用小说承接再转短剧变现。</p>
        </section>

        <section class="client-card">
            <header class="client-section-head">
                <div>
                    <span class="eyebrow">全部小说</span>
                    <h2>可投流内容</h2>
                </div>
                <span><?= number_format(count($novels)) ?> 本</span>
            </header>
            <?php if (empty($novels)): ?>
                <p class="muted">暂无上架小说，请先到后台内容管理添加。</p>
            <?php else: ?>
                <div class="mini-drama-grid">
                    <?php foreach ($novels as $novel): ?>
                        <a class="mini-drama-card" href="/?route=novel&id=<?= (int) ($novel['id'] ?? 0) ?>">
                            <img src="<?= htmlspecialchars($cover($novel)) ?>" alt="">
                            <strong><?= htmlspecialchars((string) ($novel['title'] ?? '未命名小说')) ?></strong>
                            <span><?= htmlspecialchars((string) ($novel['category'] ?? '小说')) ?> · <?= number_format($chapterCount($novel)) ?> 章 · <?= number_format((int) ($novel['chapter_coin_price'] ?? 0)) ?>K币/章</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/duanju_nav.php'; ?>
