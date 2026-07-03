<?php
$title = '追剧 - 精秀短剧';
$followed = array_values((array) ($followed_dramas ?? []));
$history = array_values((array) ($watch_history ?? []));
$dramaMap = [];
foreach ((array) ($dramas ?? []) as $drama) {
    $dramaMap[(int) ($drama['id'] ?? 0)] = $drama;
}
$cover = static fn (?array $drama): string => (string) (($drama['cover'] ?? '') ?: '/assets/cover-1.svg');
$activeTab = 'zhuiju';
?>
<main class="duanju-app">
    <section class="duanju-page-title">
        <span>Following</span>
        <h1>我的追剧</h1>
        <p>收藏的剧、最近看到哪一集，都会在这里继续。</p>
    </section>

    <section class="duanju-section">
        <header>
            <h2>追剧列表</h2>
            <a href="/juchang">去逛逛</a>
        </header>
        <?php if (empty($followed)): ?>
            <div class="duanju-empty">
                <strong>还没有追剧</strong>
                <p>在播放页点击“追剧”，下次就能从这里快速继续。</p>
                <a class="btn primary" href="/juchang">去剧场看看</a>
            </div>
        <?php else: ?>
            <div class="duanju-list">
                <?php foreach ($followed as $item): ?>
                    <?php $drama = $dramaMap[(int) ($item['drama_id'] ?? 0)] ?? null; ?>
                    <?php if (!$drama) continue; ?>
                    <a class="duanju-list-card" href="/yulan/id/<?= (int) $drama['id'] ?>">
                        <img src="<?= htmlspecialchars($cover($drama)) ?>" alt="">
                        <span>
                            <strong><?= htmlspecialchars((string) ($drama['title'] ?? '')) ?></strong>
                            <em><?= htmlspecialchars((string) ($drama['category'] ?? '短剧')) ?> · <?= htmlspecialchars((string) ($item['created_at'] ?? '')) ?></em>
                            <small><?= htmlspecialchars((string) ($drama['description'] ?? '')) ?></small>
                        </span>
                        <b>继续</b>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="duanju-section">
        <header>
            <h2>观看历史</h2>
            <span><?= number_format(count($history)) ?> 条</span>
        </header>
        <?php if (empty($history)): ?>
            <div class="duanju-empty compact">
                <strong>暂无历史</strong>
                <p>开始播放后会自动记录。</p>
            </div>
        <?php else: ?>
            <div class="duanju-history">
                <?php foreach (array_slice($history, 0, 12) as $item): ?>
                    <?php $drama = $dramaMap[(int) ($item['drama_id'] ?? 0)] ?? null; ?>
                    <?php if (!$drama) continue; ?>
                    <a href="/yulan/id/<?= (int) $drama['id'] ?>?episode_id=<?= (int) ($item['episode_id'] ?? 0) ?>">
                        <strong><?= htmlspecialchars((string) ($drama['title'] ?? '')) ?></strong>
                        <span>看到第 <?= (int) ($item['episode_id'] ?? 0) ?> 集 · <?= htmlspecialchars((string) ($item['updated_at'] ?? '')) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php require dirname(__DIR__) . '/frontend/duanju_nav.php'; ?>
