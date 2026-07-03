<?php
$title = '后台登录 - 精秀短剧';
$loginChallenge = is_array($login_challenge ?? null) ? $login_challenge : ['id' => '', 'target' => 72];
$loginTarget = max(8, min(92, (int) ($loginChallenge['target'] ?? 72)));
?>
<section class="admin-login">
    <div class="login-aura login-aura-one"></div>
    <div class="login-aura login-aura-two"></div>

    <div class="login-hero">
        <a class="login-brand" href="/">
            <span>JX</span>
            <strong>精秀短剧</strong>
        </a>
        <p class="eyebrow">Jingxiu Drama Console</p>
        <h1>短剧生意，从这里开场。</h1>
        <p class="login-subtitle">管理内容、订单、用户权益和多支付通道配置，让爆款短剧的每一笔收入都清清楚楚。</p>

        <div class="login-metric-grid">
            <div><strong>24h</strong><span>实时运营</span></div>
            <div><strong>Pay</strong><span>聚合支付</span></div>
            <div><strong>VIP</strong><span>会员权益</span></div>
        </div>

        <div class="login-preview-card">
            <header>
                <span>今日运营概览</span>
                <b>Live</b>
            </header>
            <div class="login-preview-bars" aria-hidden="true">
                <i style="height: 48%"></i>
                <i style="height: 72%"></i>
                <i style="height: 58%"></i>
                <i style="height: 86%"></i>
                <i style="height: 64%"></i>
                <i style="height: 92%"></i>
            </div>
            <div class="login-preview-row">
                <span>订单同步</span>
                <strong>稳定运行中</strong>
            </div>
        </div>
    </div>

    <form class="login-panel" method="post" action="/jxdjadmin">
        <input type="hidden" name="admin_action" value="login">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) ($csrf_token ?? '')) ?>">
        <div class="login-panel-head">
            <span class="login-lock-mark"></span>
            <div>
                <p>管理员入口</p>
                <h2>欢迎回来</h2>
            </div>
        </div>
        <?php if (!empty($message)): ?><p class="notice"><?= htmlspecialchars($message) ?></p><?php endif; ?>
        <label class="login-field"><span>账号</span><input name="username" value="admin" autocomplete="username" placeholder="请输入管理员账号"></label>
        <label class="login-field"><span>密码</span><input name="password" type="password" autocomplete="current-password" placeholder="请输入登录密码"></label>
        <div class="login-verify" data-login-verify data-target="<?= $loginTarget ?>">
            <input type="hidden" name="login_challenge_id" value="<?= htmlspecialchars((string) ($loginChallenge['id'] ?? '')) ?>">
            <input type="hidden" name="login_slider_percent" value="" data-login-slider-percent>
            <input type="hidden" name="login_slider_elapsed" value="" data-login-slider-elapsed>
            <input type="hidden" name="login_slider_passed" value="0" data-login-slider-passed>
            <div class="login-verify-head">
                <span>安全验证</span>
                <strong data-login-verify-state>拖动滑块完成验证</strong>
            </div>
            <div class="login-slider-track" data-login-slider-track>
                <span class="login-slider-target" style="left: calc(<?= $loginTarget ?>% - 18px);"></span>
                <span class="login-slider-fill" data-login-slider-fill></span>
                <button class="login-slider-thumb" type="button" data-login-slider-thumb aria-label="拖动滑块完成验证">
                    <span></span>
                </button>
                <em data-login-slider-text>按住滑块，拖到亮色缺口处</em>
            </div>
        </div>
        <button class="btn primary" type="submit">登录后台</button>
        <div class="login-helper-row">
            <span>/jxdjadmin</span>
            <span>首次登录后请修改默认密码</span>
        </div>
    </form>
</section>
<script>
(() => {
    const root = document.querySelector('[data-login-verify]');
    const form = document.querySelector('.login-panel');
    if (!root || !form) {
        return;
    }

    const target = Number(root.dataset.target || 0);
    const track = root.querySelector('[data-login-slider-track]');
    const thumb = root.querySelector('[data-login-slider-thumb]');
    const fill = root.querySelector('[data-login-slider-fill]');
    const text = root.querySelector('[data-login-slider-text]');
    const state = root.querySelector('[data-login-verify-state]');
    const percentInput = root.querySelector('[data-login-slider-percent]');
    const elapsedInput = root.querySelector('[data-login-slider-elapsed]');
    const passedInput = root.querySelector('[data-login-slider-passed]');
    let dragging = false;
    let startTime = 0;
    let passed = false;

    const setProgress = (percent) => {
        const safePercent = Math.max(0, Math.min(100, percent));
        thumb.style.left = `calc(${safePercent}% - 24px)`;
        fill.style.width = `${safePercent}%`;
        percentInput.value = safePercent.toFixed(2);
    };

    const resetSlider = () => {
        passed = false;
        passedInput.value = '0';
        elapsedInput.value = '';
        setProgress(0);
        root.classList.remove('is-passed', 'is-error');
        if (text) {
            text.textContent = '按住滑块，拖到亮色缺口处';
        }
        if (state) {
            state.textContent = '拖动滑块完成验证';
        }
    };

    const passSlider = (percent) => {
        passed = true;
        passedInput.value = '1';
        elapsedInput.value = String(Math.max(0, Date.now() - startTime));
        setProgress(percent);
        root.classList.remove('is-error');
        root.classList.add('is-passed');
        if (text) {
            text.textContent = '验证通过，可以登录';
        }
        if (state) {
            state.textContent = '验证通过';
        }
    };

    const percentFromPointer = (clientX) => {
        const rect = track.getBoundingClientRect();
        if (rect.width <= 0) {
            return 0;
        }

        return ((clientX - rect.left) / rect.width) * 100;
    };

    const beginDrag = (clientX, pointerId = null) => {
        if (passed || dragging) {
            return;
        }
        dragging = true;
        startTime = Date.now();
        root.classList.remove('is-error');
        if (pointerId !== null) {
            thumb.setPointerCapture?.(pointerId);
        }
        setProgress(percentFromPointer(clientX));
    };

    const handleMove = (event) => {
        if (!dragging || passed) {
            return;
        }
        event.preventDefault();
        setProgress(percentFromPointer(event.clientX));
    };

    const handleUp = (event) => {
        if (!dragging || passed) {
            return;
        }
        dragging = false;
        thumb.releasePointerCapture?.(event.pointerId);
        const percent = Number(percentInput.value || 0);
        elapsedInput.value = String(Math.max(0, Date.now() - startTime));
        if (Math.abs(percent - target) <= 4) {
            passSlider(percent);
            return;
        }

        root.classList.add('is-error');
        if (text) {
            text.textContent = '位置不对，再试一次';
        }
        if (state) {
            state.textContent = '验证未通过';
        }
        window.setTimeout(resetSlider, 520);
    };

    thumb.addEventListener('pointerdown', (event) => {
        beginDrag(event.clientX, event.pointerId);
    });
    window.addEventListener('pointermove', handleMove);
    window.addEventListener('pointerup', handleUp);
    window.addEventListener('pointercancel', handleUp);
    thumb.addEventListener('mousedown', (event) => beginDrag(event.clientX));
    window.addEventListener('mousemove', handleMove);
    window.addEventListener('mouseup', handleUp);
    thumb.addEventListener('touchstart', (event) => {
        const touch = event.touches[0];
        if (touch) {
            beginDrag(touch.clientX);
        }
    }, { passive: true });
    window.addEventListener('touchmove', (event) => {
        const touch = event.touches[0];
        if (touch) {
            handleMove({ clientX: touch.clientX, preventDefault: () => event.preventDefault() });
        }
    }, { passive: false });
    window.addEventListener('touchend', () => {
        if (!dragging || passed) {
            return;
        }
        dragging = false;
        const percent = Number(percentInput.value || 0);
        elapsedInput.value = String(Math.max(0, Date.now() - startTime));
        if (Math.abs(percent - target) <= 4) {
            passSlider(percent);
            return;
        }
        root.classList.add('is-error');
        if (text) {
            text.textContent = '位置不对，再试一次';
        }
        if (state) {
            state.textContent = '验证未通过';
        }
        window.setTimeout(resetSlider, 520);
    });

    form.addEventListener('submit', (event) => {
        if (passed) {
            return;
        }
        event.preventDefault();
        root.classList.add('is-error');
        if (state) {
            state.textContent = '请先完成验证';
        }
        if (text) {
            text.textContent = '拖动滑块到缺口处后再登录';
        }
    });

    resetSlider();
})();
</script>
