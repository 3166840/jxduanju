<?php $title = '登录 - 精秀短剧'; ?>
<main class="duanju-app login-app">
    <section class="login-hero-card">
        <span>手机号登录</span>
        <h1>一键保留你的追剧资产</h1>
        <p>验证码为首版模拟短信，会直接在页面提示，后续可替换真实短信服务商。</p>
    </section>

    <section class="login-form-card">
        <label>
            <span>手机号</span>
            <input name="phone" inputmode="numeric" maxlength="11" placeholder="请输入 11 位手机号" data-login-phone>
        </label>
        <label>
            <span>验证码</span>
            <div class="login-code-row">
                <input name="code" inputmode="numeric" maxlength="6" placeholder="6 位验证码" data-login-code>
                <button type="button" data-send-code>获取验证码</button>
            </div>
        </label>
        <p class="login-message" data-login-message>未登录也可以试看，购买或追剧建议先登录。</p>
        <button class="btn primary" type="button" data-login-submit>登录/注册</button>
        <a class="btn ghost" href="/duanju">先去逛逛</a>
    </section>
</main>
<script>
(() => {
    const phone = document.querySelector('[data-login-phone]');
    const code = document.querySelector('[data-login-code]');
    const message = document.querySelector('[data-login-message]');
    const send = document.querySelector('[data-send-code]');
    const submit = document.querySelector('[data-login-submit]');
    const setMessage = (text) => { if (message) message.textContent = text; };
    send?.addEventListener('click', async () => {
        send.disabled = true;
        setMessage('正在生成验证码...');
        try {
            const body = new URLSearchParams({ phone: phone?.value || '' });
            const response = await fetch('/?route=api-send-sms', { method: 'POST', body, headers: { 'Accept': 'application/json' } });
            const result = await response.json();
            setMessage(result.ok ? `模拟验证码：${result.code}，10 分钟内有效。` : (result.message || '验证码发送失败。'));
            if (result.ok && code) code.value = result.code;
        } catch (error) {
            setMessage('网络异常，请稍后重试。');
        } finally {
            window.setTimeout(() => { send.disabled = false; }, 1200);
        }
    });
    submit?.addEventListener('click', async () => {
        submit.disabled = true;
        setMessage('正在登录...');
        try {
            const body = new URLSearchParams({ phone: phone?.value || '', code: code?.value || '' });
            const response = await fetch('/?route=api-login-sms', { method: 'POST', body, headers: { 'Accept': 'application/json' } });
            const result = await response.json();
            if (result.ok) {
                setMessage('登录成功，正在进入我的页面...');
                window.setTimeout(() => { window.location.href = '/wode'; }, 500);
                return;
            }
            setMessage(result.message || '登录失败。');
        } catch (error) {
            setMessage('网络异常，请稍后重试。');
        } finally {
            submit.disabled = false;
        }
    });
})();
</script>
