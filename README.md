# 付费短剧 H5 平台

## 运行

1. 安装 PHP 8+
2. 配置 MySQL。项目会读取 `config/mysql.local.env`，需要包含：

```text
JX_DB_DRIVER=mysql
JX_DB_HOST=数据库地址
JX_DB_PORT=3306
JX_DB_DATABASE=数据库名
JX_DB_USERNAME=数据库用户名
JX_DB_PASSWORD=数据库密码
JX_DB_CHARSET=utf8mb4
```

3. 在项目根目录执行：

```bash
./tools/start-mysql-server.sh
```

4. 访问 `http://127.0.0.1:8001`

## MySQL 数据库模式

系统只支持 MySQL 数据源。数据库需要已有 `jx_meta`、`jx_orders`、`jx_users`、`jx_dramas`、`jx_entitlements`、`jx_payment_routes` 等表；后台“系统设置 / MySQL 维护”可创建、下载和恢复 SQL 备份。

## 功能

- 游客试看
- 按集购买
- 会员权益
- 精秀聚合支付 RSA2 下单、电脑端二维码支付页、支付 URL 跳转、主动查询、异步回调验签、防重复发货
- 后台订单列表支持主动查询支付状态、已支付精秀订单退款
- 后台管理登录、内容管理、订单补单、用户管理、基础统计

## 页面路由

- `/` 首页
- `/?route=drama&id=1` 剧集详情
- `/?route=watch&drama_id=1&episode_id=101` 播放页
- `/?route=center` 个人中心
- `/?route=bind` 绑定账号
- `/jxdjadmin` 运营后台
- `/payment/jingxiu/notify` 精秀支付异步回调
- `/payment/jingxiu/status?order_no=订单号` 主动查询精秀订单状态

## API 路由

- `/?route=api-home`
- `/?route=api-drama&id=1`
- `/?route=api-me`
- `/?route=api-order-episode&drama_id=1&episode_id=102`
- `/?route=api-pay-callback&order_no=订单号`

## 说明

- 当前项目按 ThinkPHP 风格目录组织，并提供可直接运行的纯 PHP 入口。
- 默认后台账号：`admin`，默认密码：`admin123`。上线前请在后台“系统设置”里修改。
- 精秀交易 API 使用公共参数 `mchid`、`method`、`charset`、`sign_type`、`timestamp`、`version`、`biz_content`、`sign`。
- 下单接口 `method=pay.order/create`，请求地址按后台配置的网关地址自动拼接，例如 `https://gateway.jxpays.com/pay.order/create`。
- 下单业务参数放在 `biz_content` JSON 中，核心字段为 `trade_type`、`out_trade_no`、`total_amount`、`subject`、`notify_url`、`return_url`、`client_ip`。
- 签名方式为 `RSA2`，后台需要配置商户私钥和精秀平台公钥；支付结果通知成功后返回 `success`。
- 支付页会把精秀返回的 `payurl` 本地生成 SVG 二维码；前端每 10 秒查询一次订单状态，查询到 `order_status=SUCCESS` 后自动调用发货逻辑。
- 后台订单退款先调用 `pay.order/refund` 生成“处理中”的退款申请，后续点击查询退款状态，确认通道退款成功后才会标记为 `refunded` / `partial_refunded`，全额退款时撤销对应权益。
- 后续如果你要切成正式 ThinkPHP 项目，可以保留现有控制器、服务层和视图结构，再接入框架路由、中间件、ORM 和支付宝 SDK。
