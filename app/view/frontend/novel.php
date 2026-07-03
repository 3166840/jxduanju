<?php
$novel = (array) ($novel ?? []);
$chapters = array_values((array) ($chapters ?? ($novel['chapters'] ?? [])));
$chapterAccess = (array) ($chapter_access ?? []);
$novelId = (int) ($novel['id'] ?? 0);
$firstChapterId = (int) ($first_chapter_id ?? ($chapters[0]['id'] ?? 0));
$cover = (string) (($novel['cover'] ?? '') ?: '/assets/cover-1.svg');
$title = ((string) ($novel['title'] ?? '小说详情')) . ' - 精秀短剧';
$freeCount = (int) ($novel['free_chapter_count'] ?? 0);
$paidCount = max(0, count($chapters) - $freeCount);
$activeTab = 'novels';
?>
<main class="client-screen drama-detail-screen">
    <section class="client-hero-card drama-detail-hero">
        <div class="drama-cover">
            <img src="<?= htmlspecialchars($cover) ?>" alt="">
        </div>
        <div>
            <span class="eyebrow"><?= htmlspecialchars((string) ($novel['category'] ?? '小说')) ?> · <?= htmlspecialchars((string) (($novel['author'] ?? '') ?: '作者未填')) ?></span>
            <h1><?= htmlspecialchars((string) ($novel['title'] ?? '未找到小说')) ?></h1>
            <p><?= htmlspecialchars((string) ($novel['description'] ?? '精彩小说正在准备中。')) ?></p>
            <div class="client-action-row">
                <?php if ($firstChapterId > 0): ?>
                    <a class="btn primary" href="/?route=novel-read&novel_id=<?= $novelId ?>&chapter_id=<?= $firstChapterId ?>">开始阅读</a>
                <?php endif; ?>
                <a class="btn ghost" href="/?route=novels">返回书城</a>
            </div>
        </div>
    </section>

    <section class="client-card episode-section">
        <header class="client-section-head">
            <div>
                <span class="eyebrow">章节目录</span>
                <h2><?= number_format(count($chapters)) ?> 章</h2>
            </div>
            <span>免费 <?= number_format($freeCount) ?> 章 · 付费 <?= number_format($paidCount) ?> 章</span>
        </header>
        <?php if (empty($chapters)): ?>
            <p class="muted">暂无章节。</p>
        <?php else: ?>
            <div class="episode-card-list">
                <?php foreach ($chapters as $index => $chapter): ?>
                    <?php
                    $chapterId = (int) ($chapter['id'] ?? 0);
                    $unlocked = !empty($chapterAccess[$chapterId]);
                    ?>
                    <article class="client-episode-card <?= $unlocked ? 'is-unlocked' : 'is-locked' ?>">
                        <a class="episode-play-link" href="/?route=novel-read&novel_id=<?= $novelId ?>&chapter_id=<?= $chapterId ?>">
                            <span class="episode-index"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <span>
                                <strong><?= htmlspecialchars((string) ($chapter['title'] ?? '未命名章节')) ?></strong>
                                <em><?= number_format((int) ($chapter['word_count'] ?? 0)) ?> 字 · <?= $unlocked ? '可阅读' : '待解锁' ?></em>
                            </span>
                        </a>
                        <div class="episode-actions">
                            <a class="btn ghost" href="/?route=novel-read&novel_id=<?= $novelId ?>&chapter_id=<?= $chapterId ?>"><?= $unlocked ? '阅读' : '解锁' ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/duanju_nav.php'; ?>
