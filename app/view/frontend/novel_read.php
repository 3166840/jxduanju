<?php
$novel = (array) ($novel ?? []);
$chapter = (array) ($chapter ?? []);
$chapters = array_values((array) ($chapters ?? ($novel['chapters'] ?? [])));
$chapterAccess = (array) ($chapter_access ?? []);
$user = (array) ($user ?? []);
$paymentRoutes = array_values((array) ($payment_routes ?? []));
$novelId = (int) ($novel['id'] ?? 0);
$chapterId = (int) ($chapter['id'] ?? 0);
$title = ((string) ($chapter['title'] ?? '小说阅读')) . ' - ' . ((string) ($novel['title'] ?? '精秀短剧'));
$currentIndex = 0;
foreach ($chapters as $index => $item) {
    if ((int) ($item['id'] ?? 0) === $chapterId) {
        $currentIndex = $index;
        break;
    }
}
$prevChapter = $chapters[max(0, $currentIndex - 1)] ?? null;
$nextChapter = $chapters[$currentIndex + 1] ?? null;
$defaultRouteId = '';
foreach ($paymentRoutes as $route) {
    if (!empty($route['is_default'])) {
        $defaultRouteId = (string) ($route['id'] ?? '');
        break;
    }
}
$defaultRouteId = $defaultRouteId ?: (string) ($paymentRoutes[0]['id'] ?? '');
$coinBalance = (int) ($user['coin_balance'] ?? 0) + (int) ($user['bonus_coin_balance'] ?? 0);
$paidCount = max(0, count($chapters) - (int) ($novel['free_chapter_count'] ?? 0));
$canRead = !empty($can_read);
$activeTab = 'novels';
?>
<main class="duanju-player" data-novel-reader data-order-no="">
    <section class="client-card">
        <div class="player-topbar">
            <a href="/?route=novel&id=<?= $novelId ?>">‹</a>
            <span><?= htmlspecialchars((string) ($novel['title'] ?? '小说')) ?></span>
            <a href="/?route=novels">书城</a>
        </div>
        <div class="player-info-card">
            <div>
                <span><?= htmlspecialchars((string) ($novel['category'] ?? '小说')) ?> · 第 <?= number_format($currentIndex + 1) ?>/<?= number_format(count($chapters)) ?> 章</span>
                <h1><?= htmlspecialchars((string) ($chapter['title'] ?? '章节不存在')) ?></h1>
                <p><?= htmlspecialchars((string) ($novel['description'] ?? '')) ?></p>
            </div>
            <div class="player-balance">
                <strong><?= number_format($coinBalance) ?> K币</strong>
                <span>章节 <?= number_format((int) ($novel['chapter_coin_price'] ?? 0)) ?>K币</span>
            </div>
        </div>
    </section>

    <section class="client-card">
        <?php if ($canRead): ?>
            <article class="novel-reader-content">
                <?php foreach (preg_split('/\R{2,}|\n/u', (string) ($chapter['content'] ?? '')) ?: [] as $paragraph): ?>
                    <?php $paragraph = trim((string) $paragraph); ?>
                    <?php if ($paragraph !== ''): ?>
                        <p><?= htmlspecialchars($paragraph) ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if (trim((string) ($chapter['content'] ?? '')) === ''): ?>
                    <p>章节正文待补充。</p>
                <?php endif; ?>
            </article>
        <?php else: ?>
            <div class="player-lock">
                <span>本章已锁定</span>
                <h1>解锁后继续阅读</h1>
                <p>可用 K币直接解锁，也可以支付解锁当前章节或整本小说。</p>
                <button class="btn primary" type="button" data-open-buy>立即解锁</button>
            </div>
        <?php endif; ?>
        <div class="player-controls">
            <a class="<?= $prevChapter ? '' : 'is-disabled' ?>" href="<?= $prevChapter ? '/?route=novel-read&novel_id=' . $novelId . '&chapter_id=' . (int) ($prevChapter['id'] ?? 0) : '#' ?>">上一章</a>
            <button type="button" data-open-chapters>目录 <?= number_format($currentIndex + 1) ?>/<?= number_format(count($chapters)) ?></button>
            <a class="<?= $nextChapter ? '' : 'is-disabled' ?>" href="<?= $nextChapter ? '/?route=novel-read&novel_id=' . $novelId . '&chapter_id=' . (int) ($nextChapter['id'] ?? 0) : '#' ?>">下一章</a>
        </div>
    </section>
</main>

<div class="player-sheet" data-chapter-sheet hidden>
    <button class="player-sheet-backdrop" type="button" data-close-sheet aria-label="关闭"></button>
    <section class="player-sheet-card">
        <header>
            <div>
                <span>Chapters</span>
                <h2>全部章节</h2>
            </div>
            <button type="button" data-close-sheet>×</button>
        </header>
        <div class="player-episode-grid">
            <?php foreach ($chapters as $index => $item): ?>
                <?php
                $itemId = (int) ($item['id'] ?? 0);
                $unlocked = !empty($chapterAccess[$itemId]);
                ?>
                <a class="<?= $itemId === $chapterId ? 'is-active' : '' ?> <?= $unlocked ? 'is-free' : 'is-lock' ?>" href="/?route=novel-read&novel_id=<?= $novelId ?>&chapter_id=<?= $itemId ?>">
                    <strong><?= $index + 1 ?></strong>
                    <span><?= $unlocked ? '可读' : '锁定' ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<div class="player-sheet buy-sheet" data-buy-sheet hidden>
    <button class="player-sheet-backdrop" type="button" data-close-buy aria-label="关闭"></button>
    <section class="player-sheet-card">
        <header>
            <div>
                <span>Unlock</span>
                <h2>选择解锁方式</h2>
            </div>
            <button type="button" data-close-buy>×</button>
        </header>

        <div class="buy-tabs">
            <button class="is-active" type="button" data-buy-tab="novel_unlock">整本</button>
            <button type="button" data-buy-tab="novel_chapter_unlock">本章</button>
        </div>

        <div class="buy-plan-list">
            <label class="buy-plan is-active" data-plan-card="novel_unlock">
                <input type="radio" name="buy_plan" value="novel_unlock" checked>
                <span>
                    <strong>整本解锁</strong>
                    <em>付费 <?= number_format($paidCount) ?> 章，一次解锁当前小说</em>
                </span>
                <b>￥<?= number_format((float) ($novel['full_unlock_price'] ?? 0), 2) ?></b>
            </label>
            <label class="buy-plan" data-plan-card="novel_chapter_unlock">
                <input type="radio" name="buy_plan" value="novel_chapter_unlock">
                <span>
                    <strong>本章解锁</strong>
                    <em>优先使用 K币，余额不足转支付</em>
                </span>
                <b><?= number_format((int) ($novel['chapter_coin_price'] ?? 0)) ?>K币</b>
            </label>
        </div>

        <?php if (!empty($paymentRoutes)): ?>
            <div class="buy-route-list">
                <?php foreach ($paymentRoutes as $index => $route): ?>
                    <?php $routeId = (string) ($route['id'] ?? ''); ?>
                    <label class="<?= $routeId === $defaultRouteId || ($defaultRouteId === '' && $index === 0) ? 'is-active' : '' ?>">
                        <input type="radio" name="payment_route_id" value="<?= htmlspecialchars($routeId) ?>" <?= $routeId === $defaultRouteId || ($defaultRouteId === '' && $index === 0) ? 'checked' : '' ?>>
                        <span class="payment-route-method"><?= jx_payment_icon($route) ?><?= htmlspecialchars((string) ($route['payment_method_name'] ?? '支付宝')) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="buy-message" data-buy-message>余额足够时将直接扣 K币解锁，余额不足会创建支付订单。</p>
        <button class="btn primary buy-submit" type="button" data-submit-buy>立即解锁</button>
    </section>
</div>

<?php require __DIR__ . '/duanju_nav.php'; ?>

<script>
(() => {
    const novelId = <?= (int) $novelId ?>;
    const chapterId = <?= (int) $chapterId ?>;
    const chapterSheet = document.querySelector('[data-chapter-sheet]');
    const buySheet = document.querySelector('[data-buy-sheet]');
    const message = document.querySelector('[data-buy-message]');
    const submit = document.querySelector('[data-submit-buy]');
    const reader = document.querySelector('[data-novel-reader]');
    const open = (node) => {
        if (node) node.hidden = false;
        document.body.classList.add('has-player-sheet');
    };
    const close = (node) => {
        if (node) node.hidden = true;
        if ((!chapterSheet || chapterSheet.hidden) && (!buySheet || buySheet.hidden)) {
            document.body.classList.remove('has-player-sheet');
        }
    };
    document.querySelectorAll('[data-open-chapters]').forEach((button) => button.addEventListener('click', () => open(chapterSheet)));
    document.querySelectorAll('[data-close-sheet]').forEach((button) => button.addEventListener('click', () => close(chapterSheet)));
    document.querySelectorAll('[data-open-buy]').forEach((button) => button.addEventListener('click', () => open(buySheet)));
    document.querySelectorAll('[data-close-buy]').forEach((button) => button.addEventListener('click', () => close(buySheet)));
    document.querySelectorAll('[data-buy-tab]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-buy-tab]').forEach((item) => item.classList.toggle('is-active', item === button));
            document.querySelector(`[data-plan-card="${button.dataset.buyTab}"]`)?.click();
        });
    });
    document.querySelectorAll('.buy-plan input').forEach((input) => {
        input.addEventListener('change', () => {
            document.querySelectorAll('.buy-plan').forEach((item) => item.classList.toggle('is-active', item.contains(input)));
        });
    });
    document.querySelectorAll('.buy-route-list input').forEach((input) => {
        input.addEventListener('change', () => {
            document.querySelectorAll('.buy-route-list label').forEach((item) => item.classList.toggle('is-active', item.contains(input)));
        });
    });
    submit?.addEventListener('click', async () => {
        const plan = document.querySelector('input[name="buy_plan"]:checked')?.value || 'novel_chapter_unlock';
        const routeId = document.querySelector('input[name="payment_route_id"]:checked')?.value || '';
        submit.disabled = true;
        message.textContent = '正在创建解锁订单...';
        try {
            const body = new URLSearchParams({
                novel_id: String(novelId),
                chapter_id: String(chapterId),
                plan,
                payment_route_id: routeId
            });
            const response = await fetch('/?route=api-novel-order', { method: 'POST', body, headers: { 'Accept': 'application/json' } });
            const result = await response.json();
            if (result.unlocked) {
                message.textContent = result.message || '解锁成功，正在刷新...';
                window.setTimeout(() => window.location.reload(), 700);
                return;
            }
            if (result.payment_required && result.cashier_url) {
                reader.dataset.orderNo = result.order?.order_no || '';
                message.textContent = '订单已创建，正在打开收银台...';
                window.location.href = result.cashier_url;
                return;
            }
            message.textContent = result.message || result.gateway?.message || '创建订单失败，请稍后重试。';
        } catch (error) {
            message.textContent = '网络异常，请稍后再试。';
        } finally {
            submit.disabled = false;
        }
    });
})();
</script>
