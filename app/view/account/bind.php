<?php $title = '绑定账号 - 精秀短剧'; ?>
<main class="client-screen bind-screen">
    <section class="client-hero-card bind-hero-card">
        <span class="eyebrow">账号绑定</span>
        <h1>把追剧权益留在你的账号里</h1>
        <p>游客购买后的权益会绑定到当前账号，填写昵称和手机号后即可保留历史权益。</p>
    </section>

    <section class="client-card bind-form-card">
        <?php if (!empty($message)): ?><p class="notice"><?= htmlspecialchars($message) ?></p><?php endif; ?>
        <form class="stack" method="post" action="/?route=bind-save">
            <label><span>昵称</span><input name="nickname" value="<?= htmlspecialchars((string) ($user['nickname'] ?? '')) ?>" placeholder="请输入昵称"></label>
            <label><span>手机号</span><input name="phone" value="<?= htmlspecialchars((string) ($user['phone'] ?? '')) ?>" placeholder="请输入手机号"></label>
            <button class="btn primary" type="submit">保存绑定</button>
        </form>
    </section>
</main>
