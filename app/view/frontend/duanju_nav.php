<?php
$activeTab = (string) ($activeTab ?? 'duanju');
$tabs = [
    'duanju' => ['label' => '推荐', 'href' => '/duanju', 'icon' => 'home'],
    'juchang' => ['label' => '剧场', 'href' => '/juchang', 'icon' => 'drama'],
    'novels' => ['label' => '小说', 'href' => '/?route=novels', 'icon' => 'account'],
    'zhuiju' => ['label' => '追剧', 'href' => '/zhuiju', 'icon' => 'orders'],
    'wode' => ['label' => '我的', 'href' => '/wode', 'icon' => 'user'],
];
?>
<nav class="duanju-tabbar" aria-label="短剧底部导航">
    <?php foreach ($tabs as $key => $tab): ?>
        <a class="<?= $activeTab === $key ? 'is-active' : '' ?>" href="<?= htmlspecialchars($tab['href']) ?>">
            <?= jx_icon($tab['icon']) ?>
            <span><?= htmlspecialchars($tab['label']) ?></span>
        </a>
    <?php endforeach; ?>
</nav>
