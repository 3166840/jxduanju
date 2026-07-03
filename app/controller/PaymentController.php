<?php

namespace App\Controller;

use App\Service\PaymentGatewayService;
use App\Service\PlatformService;

class PaymentController
{
    public function buyEpisode(int $dramaId, int $episodeId): array
    {
        $service = new PlatformService();
        $drama = $service->findDrama($dramaId);
        $episode = null;
        foreach ($service->episodes($dramaId) as $item) {
            if ((int) $item['id'] === $episodeId) {
                $episode = $item;
            }
        }

        $order = $service->createOrder([
            'user_id' => $service->currentUserId(),
            'drama_id' => $dramaId,
            'episode_id' => $episodeId,
            'type' => 'episode',
            'amount' => $drama['price_per_episode'] ?? 0,
            'payment_route_id' => (string) ($_GET['payment_route_id'] ?? ''),
        ]);
        $gateway = (new PaymentGatewayService($service))->buildPayment($order);

        return [
            'view' => 'payment/result',
            'data' => [
                'order' => $order,
                'gateway' => $gateway,
                'message' => $gateway['message'] ?? '订单已创建。',
                'episode' => $episode,
            ],
        ];
    }

    public function buyMembership(int $dramaId): array
    {
        $service = new PlatformService();
        $drama = $service->findDrama($dramaId);

        $order = $service->createOrder([
            'user_id' => $service->currentUserId(),
            'drama_id' => $dramaId,
            'type' => 'membership',
            'amount' => $drama['membership_price'] ?? 0,
            'payment_route_id' => (string) ($_GET['payment_route_id'] ?? ''),
        ]);
        $gateway = (new PaymentGatewayService($service))->buildPayment($order);

        return [
            'view' => 'payment/result',
            'data' => [
                'order' => $order,
                'gateway' => $gateway,
                'message' => $gateway['message'] ?? '会员订单已创建。',
            ],
        ];
    }

    public function paymentTestResult(): array
    {
        $service = new PlatformService();
        if (!$service->adminLoggedIn()) {
            header('Location: /jxdjadmin');
            exit;
        }

        $orderNo = $this->requestOrderNo();
        $order = $service->findOrder($orderNo);
        if (!$order || empty($order['is_test'])) {
            return [
                'view' => 'payment/result',
                'data' => [
                    'order' => $order,
                    'gateway' => ['enabled' => false],
                    'message' => '测试订单不存在或不是支付通道测试订单。',
                ],
            ];
        }

        $gateway = [];
        if (($order['status'] ?? '') === 'paid') {
            $gateway = [
                'enabled' => true,
                'payment_url' => (string) ($order['gateway_payment_url'] ?? ''),
                'pay_info' => (string) ($order['gateway_pay_info'] ?? ''),
                'message' => '测试支付成功，测试订单状态已更新。',
                'payment_method_name' => (string) ($order['payment_method_name'] ?? ''),
                'payment_provider_name' => (string) ($order['payment_provider_name'] ?? ''),
                'payment_channel_name' => (string) ($order['payment_channel_name'] ?? ''),
            ];
        } else {
            $gateway = (new PaymentGatewayService($service))->buildPayment($order);
        }

        $order = $service->findOrder($orderNo) ?: $order;

        return [
            'view' => 'payment/result',
            'data' => [
                'order' => $order,
                'gateway' => $gateway,
                'message' => $gateway['message'] ?? '测试订单已创建。',
            ],
        ];
    }

    public function orderResult(): array
    {
        $service = new PlatformService();
        $orderNo = $this->requestOrderNo();
        $order = $service->findOrder($orderNo);
        if (!$order) {
            $order = $this->fallbackReturnOrder($service, $orderNo);
            $orderNo = (string) ($order['order_no'] ?? $orderNo);
        }
        if (!$order) {
            return [
                'view' => 'payment/result',
                'data' => [
                    'order' => null,
                    'gateway' => ['enabled' => false],
                    'message' => '订单不存在。',
                ],
            ];
        }

        $gateway = ($order['status'] ?? '') === 'paid'
            ? ['enabled' => true, 'message' => '支付已完成。']
            : (new PaymentGatewayService($service))->buildPayment($order);
        $order = $service->findOrder($orderNo) ?: $order;

        return [
            'view' => 'payment/result',
            'data' => [
                'order' => $order,
                'gateway' => $gateway,
                'message' => $gateway['message'] ?? '订单已创建。',
            ],
        ];
    }


    public function callback(string $orderNo): array
    {
        $service = new PlatformService();
        if (!$service->adminLoggedIn() || !$service->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            return [
                'view' => 'payment/result',
                'data' => [
                    'order' => null,
                    'message' => '补单失败：请先登录后台并刷新页面后重试。',
                ],
            ];
        }
        if ($orderNo === '') {
            $orderNo = (string) ($_POST['order_no'] ?? '');
        }
        $sourceOrder = $orderNo !== '' ? $service->findOrder($orderNo) : null;
        $order = $service->confirmOrderPaid($orderNo, 'admin_repair', '后台人工补单确认支付成功。');
        if ($orderNo !== '') {
            $isTestOrder = !empty($sourceOrder['is_test']) || !empty($order['is_test']);
            $service->recordOrderAction(
                $orderNo,
                'repair_callback',
                $order ? ($isTestOrder ? '人工补单回调处理成功，测试订单状态已更新。' : '人工补单回调处理成功，权益已发放。') : '人工补单回调失败，订单不存在。',
                ['source' => 'pay-callback'],
                (bool) $order
            );
        }

        return [
            'view' => 'payment/result',
            'data' => [
                'order' => $order,
                'message' => $order ? (!empty($order['is_test']) ? '测试支付回调处理成功。' : '支付回调处理成功。') : '订单不存在。',
            ],
        ];
    }

    public function jingxiuNotify(): array
    {
        $payload = $_POST ?: $_GET;
        $gateway = new PaymentGatewayService();
        $order = $gateway->verifyNotify($payload);

        return [
            'plain' => $order ? $gateway->notifySuccessText($payload) : 'fail',
        ];
    }

    public function superpayNotify(): array
    {
        $payload = $_POST ?: $_GET;
        $gateway = new PaymentGatewayService();
        $order = $gateway->verifyNotify($payload);

        return [
            'plain' => $order ? $gateway->notifySuccessText($payload) : 'fail',
        ];
    }

    public function status(): array
    {
        $orderNo = $this->requestOrderNo();
        $service = new PlatformService();
        $limitKey = 'payment_status:' . ($orderNo !== '' ? $orderNo : ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $limit = $service->throttle($limitKey, 18, 60);
        if (!$limit['allowed']) {
            return [
                'json' => [
                    'ok' => false,
                    'paid' => false,
                    'status' => 'rate_limited',
                    'message' => '查询过于频繁，请 ' . (int) $limit['retry_after'] . ' 秒后再试。',
                ],
            ];
        }

        $order = $service->findOrder($orderNo);
        if (!$order) {
            return [
                'json' => [
                    'ok' => false,
                    'paid' => false,
                    'message' => '订单不存在。',
                ],
            ];
        }

        if (($order['status'] ?? '') === 'paid') {
            return [
                'json' => [
                    'ok' => true,
                    'paid' => true,
                    'status' => 'paid',
                    'message' => !empty($order['is_test']) ? '测试支付已完成。' : '支付已完成。',
                    'order' => $order,
                ],
            ];
        }

        $query = (new PaymentGatewayService($service))->queryOrder($order);
        if (!empty($query['paid'])) {
            $order = $service->confirmOrderPaid($orderNo, 'payment_status', (string) ($query['message'] ?? '主动查询确认支付成功。')) ?: $order;
        } else {
            $order = $service->updateOrderPaymentState($orderNo, (string) ($query['status'] ?? 'pending'), (string) ($query['message'] ?? '支付未完成。'), 'payment_status') ?: $order;
        }

        return [
            'json' => [
                'ok' => (bool) ($query['ok'] ?? false),
                'paid' => (bool) ($query['paid'] ?? false),
                'status' => $query['paid'] ? 'paid' : ($query['status'] ?? 'pending'),
                'message' => $query['paid'] ? (!empty($order['is_test']) ? '测试支付成功，测试订单状态已更新。' : '支付成功，权益已发放。') : ($query['message'] ?? '支付未完成。'),
                'order' => $order,
            ],
        ];
    }

    private function requestOrderNo(): string
    {
        foreach (['order_no', 'out_trade_no', 'outTradeNo', 'out_order_no', 'outOrderNo', 'merchant_order_no', 'merchantOrderNo'] as $key) {
            $value = trim((string) ($_GET[$key] ?? $_POST[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function fallbackReturnOrder(PlatformService $service, string $orderNo): ?array
    {
        $tradeNo = '';
        foreach (['trade_no', 'tradeNo', 'transaction_id', 'transactionId', 'channel_order_no', 'channelOrderNo'] as $key) {
            $tradeNo = trim((string) ($_GET[$key] ?? $_POST[$key] ?? ''));
            if ($tradeNo !== '') {
                break;
            }
        }

        $currentUserId = $service->currentUserId();
        $orders = array_reverse($service->orders());
        foreach ($orders as $order) {
            if ($orderNo !== '' && (string) ($order['order_no'] ?? '') === $orderNo) {
                return $order;
            }
            if ($tradeNo !== '' && (string) ($order['gateway_trade_no'] ?? '') === $tradeNo) {
                return $order;
            }
        }

        $now = time();
        foreach ($orders as $order) {
            if ((int) ($order['user_id'] ?? 0) !== $currentUserId) {
                continue;
            }
            if (!in_array((string) ($order['status'] ?? 'pending'), ['pending', 'paid'], true)) {
                continue;
            }
            if (empty($order['gateway_payment_url']) && empty($order['gateway_trade_no'])) {
                continue;
            }
            $createdAt = strtotime((string) ($order['created_at'] ?? ''));
            if ($createdAt !== false && ($now - $createdAt) <= 3600) {
                return $order;
            }
        }

        return null;
    }
}
