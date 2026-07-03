<?php

namespace App\Service;

class PaymentGatewayService
{
    private const CREATE_METHOD = 'pay.order/create';
    private const QUERY_METHOD = 'pay.order/query';
    private const REFUND_METHOD = 'pay.order/refund';
    private const REFUND_QUERY_METHOD = 'pay.order/refundQuery';
    private const SUPERPAY_CREATE_PATH = 'openapi/pay/create';
    private const SUPERPAY_QUERY_PATH = 'openapi/pay/query';
    private const SUPERPAY_REFUND_PATH = 'openapi/pay/refund';

    public function __construct(private PlatformService $platform = new PlatformService())
    {
    }

    public function buildPayment(array $order): array
    {
        $config = $this->platform->paymentRouteForOrder($order);
        $display = $this->platform->paymentDisplayForOrder($order);
        if (!$this->providerSupported($config)) {
            return [
                'enabled' => false,
                'message' => ($display['provider_name'] ?? '当前支付服务商') . '暂未接入真实接口，请先配置对应适配器。',
                'params' => [],
                'payment_method_name' => $display['method_name'],
                'payment_provider_name' => $display['provider_name'],
                'payment_channel_name' => $display['channel_name'],
            ];
        }
        if (!$this->configured($config, 'request')) {
            return [
                'enabled' => false,
                'message' => ($display['channel_name'] ?? '当前支付通道') . '未配置完整，当前使用模拟支付。',
                'params' => [],
                'payment_method_name' => $display['method_name'],
                'payment_provider_name' => $display['provider_name'],
                'payment_channel_name' => $display['channel_name'],
            ];
        }

        if (!empty($order['gateway_payment_url'])) {
            return [
                'enabled' => true,
                'payment_url' => $order['gateway_payment_url'],
                'pay_info' => $order['gateway_pay_info'] ?? '',
                'api_url' => $this->endpointFor($config, 'create'),
                'method' => 'POST',
                'params' => [],
                'message' => '已复用已创建的' . $display['method_name'] . '订单。',
                'payment_method_name' => $display['method_name'],
                'payment_provider_name' => $display['provider_name'],
                'payment_channel_name' => $display['channel_name'],
            ];
        }

        try {
            $params = $this->createOrderParams($order, $config);
        } catch (\Throwable $exception) {
            return [
                'enabled' => true,
                'api_url' => $this->endpointFor($config, 'create'),
                'method' => 'POST',
                'params' => [],
                'payment_url' => '',
                'pay_info' => '',
                'remote_response' => '',
                'error' => $exception->getMessage(),
                'message' => $display['channel_name'] . '签名失败，请检查后台密钥配置。',
                'payment_method_name' => $display['method_name'],
                'payment_provider_name' => $display['provider_name'],
                'payment_channel_name' => $display['channel_name'],
            ];
        }

        $apiUrl = $this->endpointFor($config, 'create');
        $response = $this->post($apiUrl, $params, (int) ($config['request_timeout'] ?? 12));
        $json = $response['json'];
        $success = (string) ($json['code'] ?? '') === '1';
        $data = is_array($json['data'] ?? null) ? $json['data'] : [];
        $paymentUrl = (string) (($data['payurl'] ?? '') ?: ($data['pay_url'] ?? ''));
        $payInfo = (string) ($data['payInfo'] ?? '');
        $remoteResponse = $json ?: $response['body'];

        if ($success) {
            $this->platform->updateOrderGatewayData($order['order_no'], [
                'gateway_trade_no' => (string) ($data['trade_no'] ?? ''),
                'gateway_payment_url' => $paymentUrl,
                'gateway_pay_info' => $payInfo,
                'gateway_created_at' => date('Y-m-d H:i:s'),
                'payment_route_id' => $display['route_id'],
                'payment_provider' => $display['provider'],
                'payment_provider_name' => $display['provider_name'],
                'payment_channel_name' => $display['channel_name'],
                'payment_method' => $display['method'],
                'payment_method_name' => $display['method_name'],
                'gateway_trade_type' => $display['trade_type'],
            ]);
        }
        $this->platform->recordOrderGatewayLog($order['order_no'], 'create', $apiUrl, $params, $remoteResponse, $response['error'] ?: null, $success);

        return [
            'enabled' => true,
            'api_url' => $apiUrl,
            'method' => 'POST',
            'params' => $params,
            'payment_url' => $paymentUrl,
            'pay_info' => $payInfo,
            'remote_response' => $remoteResponse,
            'error' => $success ? null : ($json['msg'] ?? $response['error'] ?? '支付下单失败。'),
            'message' => $success ? $display['method_name'] . '订单已创建。' : $display['channel_name'] . '下单失败，请检查后台配置。',
            'payment_method_name' => $display['method_name'],
            'payment_provider_name' => $display['provider_name'],
            'payment_channel_name' => $display['channel_name'],
        ];
    }

    public function verifyNotify(array $payload): ?array
    {
        if (empty($payload['sign'])) {
            return null;
        }

        $orderNo = (string) ($payload['out_trade_no'] ?? '');
        $order = $orderNo !== '' ? $this->platform->findOrder($orderNo) : null;
        $config = $order ? $this->platform->paymentRouteForOrder($order) : $this->platform->defaultPaymentRoute();

        if (!$this->providerSupported($config) || !$this->configured($config, 'notify') || !$this->verifySignature($payload, $config)) {
            if ($orderNo !== '' && $order) {
                $this->platform->recordOrderGatewayLog($orderNo, 'notify', $this->endpointFor($config, 'notify'), $payload, '验签失败或通道未配置完整。', null, false);
            }
            return null;
        }

        if ($orderNo === '') {
            return null;
        }

        $status = $this->notifyStatus($payload, $config);
        $paid = $this->paymentStatusSucceeded($status, $payload, $config);
        if (!$paid) {
            $this->platform->recordOrderGatewayLog($orderNo, 'notify', $this->endpointFor($config, 'notify'), $payload, ['status' => $status], null, false);
            return null;
        }

        $this->platform->updateOrderGatewayData($orderNo, [
            'gateway_trade_no' => (string) (($payload['trade_no'] ?? '') ?: ($payload['transaction_id'] ?? (is_array($order) ? ($order['gateway_trade_no'] ?? '') : ''))),
            'gateway_notify_at' => date('Y-m-d H:i:s'),
            'gateway_last_notify_params' => $payload,
        ]);
        $this->platform->recordOrderGatewayLog($orderNo, 'notify', $this->endpointFor($config, 'notify'), $payload, ['status' => $status, 'accepted' => true], null, true);

        return $this->platform->confirmOrderPaid($orderNo, 'gateway_notify', '支付通道回调确认支付成功。');
    }

    public function queryOrder(array $order): array
    {
        $config = $this->platform->paymentRouteForOrder($order);
        $display = $this->platform->paymentDisplayForOrder($order);
        if (!$this->providerSupported($config) || !$this->configured($config, 'request')) {
            return [
                'ok' => false,
                'paid' => false,
                'status' => (string) ($order['status'] ?? 'pending'),
                'message' => ($display['channel_name'] ?? '当前支付通道') . '未配置完整。',
                'remote_response' => null,
            ];
        }

        try {
            $params = $this->queryOrderParams($order, $config);
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'paid' => false,
                'status' => (string) ($order['status'] ?? 'pending'),
                'message' => $exception->getMessage(),
                'remote_response' => null,
            ];
        }

        $apiUrl = $this->endpointFor($config, 'query');
        $response = $this->post($apiUrl, $params, (int) ($config['request_timeout'] ?? 12));
        $json = $response['json'];
        $data = is_array($json['data'] ?? null) ? $json['data'] : [];
        $status = $this->queryStatus($data, $config);
        $queryPayload = array_merge($json, $data);
        $queryPayload['expected_out_trade_no'] = (string) ($order['order_no'] ?? '');
        $paid = (string) ($json['code'] ?? '') === '1' && $this->paymentStatusSucceeded($status, $queryPayload, $config);
        $remoteResponse = $json ?: $response['body'];
        $ok = (string) ($json['code'] ?? '') === '1';
        if ($paid) {
            $this->platform->updateOrderGatewayData((string) $order['order_no'], [
                'gateway_trade_no' => (string) (($data['trade_no'] ?? '') ?: ($data['transaction_id'] ?? ($data['channel_order_no'] ?? ($order['gateway_trade_no'] ?? '')))),
                'gateway_last_query_paid_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->platform->recordOrderGatewayLog($order['order_no'], 'query', $apiUrl, $params, $remoteResponse, $response['error'] ?: null, $ok);

        return [
            'ok' => $ok,
            'paid' => $paid,
            'status' => $status ?: (string) ($order['status'] ?? 'pending'),
            'message' => $this->paymentStatusMessage($status, $paid, $json, $response['error'] ?? null, $config),
            'remote_response' => $remoteResponse,
        ];
    }

    public function refundOrder(array $order, ?float $amount = null, string $reason = '后台订单退款'): array
    {
        $config = $this->platform->paymentRouteForOrder($order);
        $display = $this->platform->paymentDisplayForOrder($order);
        if (!$this->providerSupported($config) || !$this->configured($config, 'request')) {
            return [
                'ok' => false,
                'message' => ($display['channel_name'] ?? '当前支付通道') . '未配置完整，无法发起退款。',
                'remote_response' => null,
            ];
        }

        $refundAmount = $amount ?? (float) ($order['amount'] ?? 0);
        if ($refundAmount <= 0) {
            return [
                'ok' => false,
                'message' => '退款金额必须大于 0。',
                'remote_response' => null,
            ];
        }

        $outRefundNo = 'RF' . date('YmdHis') . substr(md5($order['order_no'] . $refundAmount . microtime(true)), 0, 8);
        try {
            $params = $this->refundOrderParams($order, $config, $refundAmount, $reason, $outRefundNo);
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'message' => $exception->getMessage(),
                'remote_response' => null,
            ];
        }

        $apiUrl = $this->endpointFor($config, 'refund');
        $response = $this->post($apiUrl, $params, (int) ($config['request_timeout'] ?? 12));
        $json = $response['json'];
        $data = is_array($json['data'] ?? null) ? $json['data'] : [];
        $remoteStatus = strtoupper((string) ($data['refund_status'] ?? $data['status'] ?? ''));
        $ok = (string) ($json['code'] ?? '') === '1' && !$this->refundStatusFailed($remoteStatus);
        $remoteResponse = $json ?: $response['body'];
        $this->platform->recordOrderGatewayLog($order['order_no'], 'refund', $apiUrl, $params, $remoteResponse, $response['error'] ?: null, $ok);

        return [
            'ok' => $ok,
            'refund_no' => $outRefundNo,
            'gateway_refund_no' => (string) (($data['refund_no'] ?? '') ?: ($data['ins_refund_sn'] ?? '')),
            'refund_amount' => $refundAmount,
            'message' => (string) ($json['msg'] ?? $response['error'] ?? ($ok ? '退款申请成功。' : '退款申请失败。')),
            'remote_response' => $remoteResponse,
        ];
    }

    public function queryRefund(array $order, array $refundRequest): array
    {
        $config = $this->platform->paymentRouteForOrder($order);
        $display = $this->platform->paymentDisplayForOrder($order);
        $outRefundNo = (string) ($refundRequest['refund_no'] ?? $order['refund_no'] ?? '');
        if (!$this->providerSupported($config) || !$this->configured($config, 'request')) {
            return [
                'ok' => false,
                'refunded' => false,
                'failed' => false,
                'status' => 'CONFIG_INCOMPLETE',
                'message' => ($display['channel_name'] ?? '当前支付通道') . '未配置完整，无法查询退款状态。',
                'remote_response' => null,
            ];
        }
        if ($outRefundNo === '') {
            return [
                'ok' => false,
                'refunded' => false,
                'failed' => false,
                'status' => 'MISSING_REFUND_NO',
                'message' => '缺少退款单号，无法查询退款状态。',
                'remote_response' => null,
            ];
        }

        try {
            $params = $this->refundQueryParams($order, $refundRequest, $config, $outRefundNo);
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'refunded' => false,
                'failed' => false,
                'status' => 'SIGN_ERROR',
                'message' => $exception->getMessage(),
                'remote_response' => null,
            ];
        }

        $apiUrl = $this->endpointFor($config, 'refund_query');
        $response = $this->post($apiUrl, $params, (int) ($config['request_timeout'] ?? 12));
        $json = $response['json'];
        $data = is_array($json['data'] ?? null) ? $json['data'] : [];
        $status = $this->refundQueryStatus($data, $config);
        $ok = (string) ($json['code'] ?? '') === '1';
        $refunded = $ok && match ($this->provider($config)) {
            'superpay' => $status === 'TRADE_REFUND' || $this->refundStatusSucceeded($status, $data),
            default => $this->refundStatusSucceeded($status, $data),
        };
        $failed = $ok && !$refunded && $this->refundStatusFailed($status);
        $remoteResponse = $json ?: $response['body'];
        $this->platform->recordOrderGatewayLog($order['order_no'], 'refund_query', $apiUrl, $params, $remoteResponse, $response['error'] ?: null, $ok);

        return [
            'ok' => $ok,
            'refunded' => $refunded,
            'failed' => $failed,
            'status' => $status ?: ($ok ? 'PROCESSING' : 'QUERY_FAILED'),
            'refund_amount' => $this->refundAmountFromData($data, (float) ($refundRequest['amount'] ?? 0)),
            'message' => (string) ($json['msg'] ?? $response['error'] ?? ($refunded ? '退款成功。' : '退款处理中。')),
            'remote_response' => $remoteResponse,
        ];
    }

    public function supportsOrderAction(array $order, string $action): bool
    {
        if (!in_array($action, ['create', 'query', 'refund', 'refund_query', 'notify'], true)) {
            return false;
        }

        return $this->providerSupported($this->platform->paymentRouteForOrder($order));
    }

    public function notifySuccessText(array $payload = []): string
    {
        $orderNo = (string) ($payload['out_trade_no'] ?? '');
        $order = $orderNo !== '' ? $this->platform->findOrder($orderNo) : null;
        $config = $order ? $this->platform->paymentRouteForOrder($order) : $this->platform->defaultPaymentRoute();
        $text = trim((string) ($config['notify_success_text'] ?? 'success'));

        return $text === '' ? 'success' : $text;
    }

    private function provider(array $config): string
    {
        $provider = strtolower(trim((string) ($config['provider'] ?? 'jingxiu')));

        return match ($provider) {
            'payjf' => 'superpay',
            '' => 'jingxiu',
            default => $provider,
        };
    }

    private function providerSupported(array $config): bool
    {
        return in_array($this->provider($config), ['jingxiu', 'superpay'], true);
    }

    private function configured(array $config, string $purpose = 'request'): bool
    {
        if (empty($config['enabled']) || empty($config['api_url']) || empty($config['merchant_id'])) {
            return false;
        }

        if ($this->provider($config) === 'superpay') {
            $signType = $this->superpaySignType($config);
            if ($signType === 'MD5') {
                return !empty($config['secret_key']);
            }

            return $purpose === 'notify'
                ? !empty($config['platform_public_key'])
                : !empty($config['merchant_private_key']);
        }

        return $this->provider($config) === 'jingxiu'
            && !empty($config['merchant_private_key'])
            && !empty($config['platform_public_key']);
    }

    private function createOrderParams(array $order, array $config): array
    {
        if ($this->provider($config) === 'superpay') {
            return $this->superpayCreateOrderParams($order, $config);
        }

        return $this->jingxiuCreateOrderParams($order, $config);
    }

    private function jingxiuCreateOrderParams(array $order, array $config): array
    {
        $notifyUrl = $config['notify_url'] ?: $this->baseUrl() . '/payment/jingxiu/notify';
        $returnUrl = $config['return_url'] ?: $this->defaultReturnUrl($order);
        $bizContent = [
            'trade_type' => ($config['trade_type'] ?? $config['pay_type'] ?? '') ?: 'alipayWap',
            'out_trade_no' => $order['order_no'],
            'total_amount' => number_format((float) $order['amount'], 2, '.', ''),
            'subject' => $this->cleanSubject($this->orderTitle($order)),
            'attach' => $order['type'],
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
            'client_ip' => $this->clientIp(),
        ];

        if (!empty($config['pay_channel_id'])) {
            $bizContent['pay_channel_id'] = $config['pay_channel_id'];
        }

        if (!empty($config['channel_code'])) {
            $bizContent['channel_code'] = $config['channel_code'];
        }

        return $this->signedParams(
            self::CREATE_METHOD,
            $bizContent,
            $config,
            'REQ' . date('YmdHis') . substr(md5($order['order_no']), 0, 8)
        );
    }

    private function superpayCreateOrderParams(array $order, array $config): array
    {
        $params = [
            'pid' => $config['merchant_id'],
            'out_trade_no' => $order['order_no'],
            'total_amount' => number_format((float) $order['amount'], 2, '.', ''),
            'subject' => $this->cleanSubject($this->orderTitle($order)),
            'paytype_code' => $this->superpayPayTypeCode($config),
            'notify_url' => $config['notify_url'] ?: $this->baseUrl() . '/payment/superpay/notify',
            'return_url' => $config['return_url'] ?: $this->defaultReturnUrl($order),
            'attach' => (string) ($order['type'] ?? ''),
            'client_ip' => $this->clientIp(),
            'timestamp' => (string) time(),
            'sign_type' => $this->superpaySignType($config),
        ];

        if (!empty($config['pay_channel_id'])) {
            $params['channel_id'] = (string) $config['pay_channel_id'];
        }

        return $this->superpaySignedParams($params, $config);
    }

    private function queryOrderParams(array $order, array $config): array
    {
        if ($this->provider($config) === 'superpay') {
            return $this->superpaySignedParams($this->superpayTradeLookupParams($order, $config), $config);
        }

        return $this->signedParams(
            self::QUERY_METHOD,
            $this->queryBizContent($order),
            $config,
            'QRY' . date('YmdHis') . substr(md5($order['order_no']), 0, 8)
        );
    }

    private function refundOrderParams(array $order, array $config, float $refundAmount, string $reason, string $outRefundNo): array
    {
        if ($this->provider($config) === 'superpay') {
            $params = $this->superpayTradeLookupParams($order, $config);
            $params['refund_amount'] = number_format($refundAmount, 2, '.', '');
            $params['refund_reason'] = $this->cleanSubject($reason);

            return $this->superpaySignedParams($params, $config);
        }

        $bizContent = [
            'refund_amount' => number_format($refundAmount, 2, '.', ''),
            'refund_reason' => $this->cleanSubject($reason),
            'out_refund_no' => $outRefundNo,
            'out_trade_no' => $order['order_no'],
        ];

        if (!empty($order['gateway_trade_no'])) {
            $bizContent['trade_no'] = $order['gateway_trade_no'];
        }

        return $this->signedParams(self::REFUND_METHOD, $bizContent, $config, 'RFD' . date('YmdHis') . substr(md5($outRefundNo), 0, 8));
    }

    private function refundQueryParams(array $order, array $refundRequest, array $config, string $outRefundNo): array
    {
        if ($this->provider($config) === 'superpay') {
            return $this->superpaySignedParams($this->superpayTradeLookupParams($order, $config), $config);
        }

        return $this->signedParams(
            self::REFUND_QUERY_METHOD,
            $this->refundQueryBizContent($order, $refundRequest),
            $config,
            'RFQ' . date('YmdHis') . substr(md5($outRefundNo), 0, 8)
        );
    }

    private function superpayTradeLookupParams(array $order, array $config): array
    {
        $params = [
            'pid' => $config['merchant_id'],
            'out_trade_no' => $order['order_no'],
            'timestamp' => (string) time(),
            'sign_type' => $this->superpaySignType($config),
        ];

        if (!empty($order['gateway_trade_no'])) {
            $params['trade_no'] = (string) $order['gateway_trade_no'];
        }

        return $params;
    }

    private function queryBizContent(array $order): array
    {
        $bizContent = [
            'out_trade_no' => $order['order_no'],
        ];

        if (!empty($order['gateway_trade_no'])) {
            $bizContent['trade_no'] = $order['gateway_trade_no'];
        }

        return $bizContent;
    }

    private function refundQueryBizContent(array $order, array $refundRequest): array
    {
        $bizContent = [
            'out_trade_no' => $order['order_no'],
            'out_refund_no' => (string) ($refundRequest['refund_no'] ?? $order['refund_no'] ?? ''),
        ];

        $gatewayRefundNo = (string) (($refundRequest['gateway_refund_no'] ?? '') ?: ($refundRequest['channel_refund_no'] ?? ''));
        if ($gatewayRefundNo !== '') {
            $bizContent['refund_no'] = $gatewayRefundNo;
        }

        if (!empty($order['gateway_trade_no'])) {
            $bizContent['trade_no'] = $order['gateway_trade_no'];
        }

        return $bizContent;
    }

    private function notifyStatus(array $payload, array $config): string
    {
        return $this->provider($config) === 'superpay'
            ? $this->firstStatusText($payload, ['trade_status', 'status', 'pay_status'])
            : $this->firstStatusText($payload, ['order_status', 'trade_status', 'pay_status', 'payment_status', 'status', 'state']);
    }

    private function queryStatus(array $data, array $config): string
    {
        return $this->provider($config) === 'superpay'
            ? $this->firstStatusText($data, ['trade_status', 'status', 'pay_status'])
            : $this->firstStatusText($data, ['order_status', 'trade_status', 'pay_status', 'payment_status', 'status', 'state']);
    }

    private function refundQueryStatus(array $data, array $config): string
    {
        if ($this->provider($config) === 'superpay') {
            return strtoupper((string) ($data['trade_status'] ?? $data['refund_status'] ?? $data['status'] ?? ''));
        }

        return strtoupper((string) ($data['refund_status'] ?? $data['refund_state'] ?? $data['status'] ?? $data['state'] ?? $data['order_status'] ?? ''));
    }

    private function superpayPayTypeCode(array $config): string
    {
        $code = strtolower(trim((string) (($config['payment_method'] ?? '') ?: ($config['trade_type'] ?? 'alipay'))));

        return match ($code) {
            'wechat', 'weixin', 'wx' => 'wxpay',
            'alipaywap', 'alipaypage', 'alipay' => 'alipay',
            'union', 'yunshanfu' => 'unionpay',
            default => $code !== '' ? $code : 'alipay',
        };
    }

    private function superpaySignType(array $config): string
    {
        $signType = strtoupper(trim((string) ($config['sign_type'] ?? 'MD5')));

        return in_array($signType, ['RSA', 'RSA2'], true) ? 'RSA' : 'MD5';
    }

    private function paymentStatusSucceeded(string $status, array $payload, array $config): bool
    {
        $normalized = $this->normalizeStatusText($status);
        if ($this->provider($config) === 'jingxiu') {
            if (in_array($normalized, ['SUCCESS', 'CALL_FAIL'], true)) {
                return true;
            }

            if ($normalized === 'FALSE' && $this->jingxiuFalseQueryMeansPaid($payload)) {
                return true;
            }

            if (in_array($normalized, ['PROCESSING', 'TIME_OUT', 'TIMEOUT', 'FAIL', 'FAILED', 'CLOSE', 'CLOSED'], true)) {
                return false;
            }
        }

        if (in_array($normalized, ['SUCCESS', 'TRADE_SUCCESS', 'PAID', 'PAY_SUCCESS', 'PAYED', 'ORDER_PAID', 'FINISHED', 'COMPLETE', 'COMPLETED'], true)) {
            return true;
        }

        foreach (['success_time', 'pay_success_time', 'paid_at', 'pay_time', 'payment_time'] as $key) {
            if (!empty($payload[$key])) {
                return true;
            }
        }

        $text = $status;
        foreach (['order_status', 'trade_status', 'pay_status', 'payment_status', 'status', 'state', 'msg', 'message'] as $key) {
            if (isset($payload[$key]) && !is_array($payload[$key])) {
                $text .= ' ' . (string) $payload[$key];
            }
        }

        foreach (['支付成功', '已支付', '交易成功', '付款成功', '支付完成', '交易完成'] as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function paymentStatusMessage(string $status, bool $paid, array $json, ?string $error, array $config): string
    {
        $normalized = $this->normalizeStatusText($status);
        if ($this->provider($config) === 'jingxiu') {
            return match ($normalized) {
                'SUCCESS' => '订单支付成功。',
                'CALL_FAIL' => '支付成功，通道回调未成功返回，系统已按已支付处理。',
                'FALSE' => $paid ? '支付成功，通道返回 false 状态，系统已按已支付处理。' : (string) ($json['msg'] ?? $error ?? '支付未完成。'),
                'PROCESSING' => '订单待支付。',
                'TIME_OUT', 'TIMEOUT' => '订单已过期。',
                'FAIL', 'FAILED' => '订单支付失败。',
                'CLOSE', 'CLOSED' => '订单已关闭。',
                default => $paid ? '订单支付成功。' : (string) ($json['msg'] ?? $error ?? '支付未完成。'),
            };
        }

        return $paid
            ? '订单支付成功。'
            : (string) ($json['msg'] ?? $error ?? '支付未完成。');
    }

    private function normalizeStatusText(string $status): string
    {
        return strtoupper(trim(str_replace(['-', ' '], '_', $status)));
    }

    private function firstStatusText(array $payload, array $keys): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $value = $payload[$key];
            if (is_bool($value)) {
                return $value ? 'TRUE' : 'FALSE';
            }
            if (is_scalar($value)) {
                return $this->normalizeStatusText((string) $value);
            }
        }

        return '';
    }

    private function jingxiuFalseQueryMeansPaid(array $payload): bool
    {
        $outTradeNo = trim((string) ($payload['out_trade_no'] ?? ''));
        $expectedOutTradeNo = trim((string) ($payload['expected_out_trade_no'] ?? ''));
        if ($outTradeNo === '' || ($expectedOutTradeNo !== '' && $outTradeNo !== $expectedOutTradeNo)) {
            return false;
        }

        $hasGatewayTrace = trim((string) (($payload['trade_no'] ?? '') ?: ($payload['ins_order_sn'] ?? '') ?: ($payload['channel_order_sn'] ?? ''))) !== '';
        if (!$hasGatewayTrace) {
            return false;
        }

        $text = '';
        foreach (['order_status', 'trade_status', 'pay_status', 'payment_status', 'status', 'state', 'msg', 'message'] as $key) {
            if (isset($payload[$key]) && is_scalar($payload[$key])) {
                $text .= ' ' . $this->normalizeStatusText((string) $payload[$key]);
            }
        }
        foreach (['WAIT_BUYER_PAY', 'PROCESSING', 'TIME_OUT', 'TIMEOUT', 'FAIL', 'FAILED', 'CLOSE', 'CLOSED', 'REFUND'] as $blocked) {
            if (str_contains($text, $blocked)) {
                return false;
            }
        }

        return true;
    }

    private function refundStatusSucceeded(string $status, array $data): bool
    {
        if (in_array($status, ['SUCCESS', 'REFUND_SUCCESS', 'REFUNDED', 'FINISHED', 'COMPLETE', 'COMPLETED', 'TRADE_REFUND'], true)) {
            return true;
        }

        return $status === '' && (!empty($data['refund_success_time']) || !empty($data['success_time']));
    }

    private function refundStatusFailed(string $status): bool
    {
        if ($status === '') {
            return false;
        }

        foreach (['FAIL', 'FAILED', 'REFUND_FAIL', 'REFUND_FAILED', 'CLOSED', 'CLOSE', 'ERROR'] as $needle) {
            if (str_contains($status, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function refundAmountFromData(array $data, float $fallback): float
    {
        foreach (['refund_amount', 'amount', 'total_amount'] as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return round((float) $data[$key], 2);
            }
        }

        return round($fallback, 2);
    }

    private function signedParams(string $method, array $bizContent, array $config, string $requestId): array
    {
        $params = [
            'req_id' => $requestId,
            'mchid' => $config['merchant_id'],
            'method' => $method,
            'charset' => $config['charset'] ?: 'utf-8',
            'sign_type' => $config['sign_type'] ?: 'RSA2',
            'timestamp' => (string) time(),
            'version' => $config['version'] ?: '1.0',
            'biz_content' => json_encode($bizContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        if (!empty($config['app_id'])) {
            $params['appid'] = $config['app_id'];
        }

        $params['sign'] = $this->rsa2Sign($params, (string) $config['merchant_private_key']);

        return $params;
    }

    private function superpaySignedParams(array $params, array $config): array
    {
        $params['sign_type'] = $this->superpaySignType($config);
        $params['sign'] = $this->superpaySign($params, $config);

        return $params;
    }

    private function verifySignature(array $payload, array $config): bool
    {
        if ($this->provider($config) === 'superpay') {
            return $this->verifySuperpaySignature($payload, $config);
        }

        return $this->verifyJingxiuSignature($payload, $config);
    }

    private function verifyJingxiuSignature(array $payload, array $config): bool
    {
        $sign = (string) ($payload['sign'] ?? '');
        $publicKey = (string) ($config['platform_public_key'] ?? '');
        if ($sign === '' || $publicKey === '') {
            return false;
        }

        $signature = base64_decode($sign, true);
        if ($signature === false) {
            return false;
        }

        $key = $this->publicKey($publicKey);
        if ($key === false) {
            return false;
        }

        return openssl_verify($this->signingString($payload), $signature, $key, OPENSSL_ALGO_SHA256) === 1;
    }

    private function verifySuperpaySignature(array $payload, array $config): bool
    {
        $sign = (string) ($payload['sign'] ?? '');
        if ($sign === '') {
            return false;
        }

        $signType = strtoupper((string) (($payload['sign_type'] ?? '') ?: $this->superpaySignType($config)));
        if ($signType === 'MD5') {
            return hash_equals(strtoupper($sign), $this->superpayMd5Sign($payload, (string) ($config['secret_key'] ?? '')));
        }

        $signature = base64_decode($sign, true);
        if ($signature === false) {
            return false;
        }

        $key = $this->publicKey((string) ($config['platform_public_key'] ?? ''));
        if ($key === false) {
            return false;
        }

        return openssl_verify($this->superpaySigningString($payload), $signature, $key, OPENSSL_ALGO_SHA256) === 1;
    }

    private function rsa2Sign(array $params, string $privateKey): string
    {
        $key = $this->privateKey($privateKey);
        if ($key === false) {
            throw new \RuntimeException('支付商户私钥格式不正确。');
        }

        $signature = '';
        if (!openssl_sign($this->signingString($params), $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('支付 RSA2 签名失败。');
        }

        return base64_encode($signature);
    }

    private function superpaySign(array $params, array $config): string
    {
        if ($this->superpaySignType($config) === 'MD5') {
            return $this->superpayMd5Sign($params, (string) ($config['secret_key'] ?? ''));
        }

        return $this->superpayRsaSign($params, (string) ($config['merchant_private_key'] ?? ''));
    }

    private function superpayMd5Sign(array $params, string $secretKey): string
    {
        if ($secretKey === '') {
            throw new \RuntimeException('超级支付接口密钥未配置。');
        }

        return strtoupper(md5($this->superpaySigningString($params) . '&key=' . $secretKey));
    }

    private function superpayRsaSign(array $params, string $privateKey): string
    {
        $key = $this->privateKey($privateKey);
        if ($key === false) {
            throw new \RuntimeException('超级支付商户私钥格式不正确。');
        }

        $signature = '';
        if (!openssl_sign($this->superpaySigningString($params), $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('超级支付 RSA 签名失败。');
        }

        return base64_encode($signature);
    }

    private function signingString(array $params): string
    {
        unset($params['sign']);
        ksort($params);

        $pieces = [];
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null || is_array($value)) {
                continue;
            }
            $pieces[] = $key . '=' . $value;
        }

        return implode('&', $pieces);
    }

    private function superpaySigningString(array $params): string
    {
        unset($params['sign'], $params['sign_type']);
        ksort($params, SORT_STRING);

        $pieces = [];
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null || is_array($value)) {
                continue;
            }
            $pieces[] = $key . '=' . $value;
        }

        return implode('&', $pieces);
    }

    private function post(string $url, array $params, int $timeout): array
    {
        $body = http_build_query($params);
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);
            $raw = curl_exec($ch);
            $error = curl_error($ch);

            return $this->response($raw === false ? '' : (string) $raw, $error ?: null);
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'timeout' => $timeout,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);

        return $this->response($raw === false ? '' : (string) $raw, $raw === false ? '网络请求失败。' : null);
    }

    private function response(string $body, ?string $error): array
    {
        $json = json_decode($body, true);

        return [
            'body' => $body,
            'json' => is_array($json) ? $json : [],
            'error' => $error,
        ];
    }

    private function endpointFor(array $config, string $scene): string
    {
        if ($this->provider($config) === 'superpay') {
            $path = match ($scene) {
                'query', 'refund_query' => self::SUPERPAY_QUERY_PATH,
                'refund' => self::SUPERPAY_REFUND_PATH,
                'notify' => 'payment/superpay/notify',
                default => self::SUPERPAY_CREATE_PATH,
            };

            return $scene === 'notify'
                ? $this->baseUrl() . '/' . $path
                : $this->superpayEndpoint($config, $path);
        }

        $method = match ($scene) {
            'query' => self::QUERY_METHOD,
            'refund' => self::REFUND_METHOD,
            'refund_query' => self::REFUND_QUERY_METHOD,
            'notify' => 'payment/jingxiu/notify',
            default => self::CREATE_METHOD,
        };

        return $scene === 'notify'
            ? $this->baseUrl() . '/' . $method
            : $this->endpoint($config, $method);
    }

    private function endpoint(array $config, string $method): string
    {
        $url = trim((string) ($config['api_url'] ?? ''));
        if (str_ends_with($url, $method)) {
            return $url;
        }

        return rtrim($url, '/') . '/' . $method;
    }

    private function superpayEndpoint(array $config, string $path): string
    {
        $url = trim((string) ($config['api_url'] ?? 'http://payjf.cn'));
        if (str_ends_with($url, $path)) {
            return $url;
        }
        if (preg_match('#/openapi/pay/(create|query|refund)$#', $url) === 1) {
            return preg_replace('#/openapi/pay/(create|query|refund)$#', '/' . $path, $url) ?: $url;
        }

        return rtrim($url, '/') . '/' . $path;
    }

    private function normalizePem(string $key, string $type): string
    {
        $key = trim(str_replace('\\n', "\n", $key));
        if (str_contains($key, '-----BEGIN')) {
            return $key;
        }

        return "-----BEGIN {$type}-----\n" . chunk_split(preg_replace('/\s+/', '', $key), 64, "\n") . "-----END {$type}-----\n";
    }

    private function privateKey(string $key): \OpenSSLAsymmetricKey|false
    {
        foreach (['PRIVATE KEY', 'RSA PRIVATE KEY'] as $type) {
            $resource = openssl_pkey_get_private($this->normalizePem($key, $type));
            if ($resource !== false) {
                return $resource;
            }
        }

        return false;
    }

    private function publicKey(string $key): \OpenSSLAsymmetricKey|false
    {
        foreach (['PUBLIC KEY', 'RSA PUBLIC KEY'] as $type) {
            $resource = openssl_pkey_get_public($this->normalizePem($key, $type));
            if ($resource !== false) {
                return $resource;
            }
        }

        return false;
    }

    private function cleanSubject(string $subject): string
    {
        return trim(str_replace(['/', '=', '&'], ' ', $subject));
    }

    private function orderTitle(array $order): string
    {
        if (!empty($order['is_test']) || ($order['type'] ?? '') === 'payment_test') {
            $subject = trim((string) ($order['test_subject'] ?? ''));

            return $subject !== '' ? $subject : '支付通道测试';
        }
        $subject = trim((string) ($order['subject'] ?? ''));
        if ($subject !== '') {
            return $subject;
        }

        return match ((string) ($order['type'] ?? 'episode')) {
            'membership', 'vip_week', 'vip_month' => '短剧VIP会员',
            'drama_unlock' => '短剧全集解锁',
            'coin_recharge' => '短剧K币充值',
            'episode', 'episode_unlock' => '短剧单集解锁',
            default => '短剧权益订单',
        };
    }

    private function defaultReturnUrl(array $order): string
    {
        if (!empty($order['is_test']) || ($order['type'] ?? '') === 'payment_test') {
            return $this->baseUrl() . '/?route=payment-test-result&order_no=' . rawurlencode((string) ($order['order_no'] ?? ''));
        }

        return $this->baseUrl() . '/?route=payment-result&order_no=' . rawurlencode((string) ($order['order_no'] ?? ''));
    }

    private function clientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        return str_contains($ip, ':') ? '127.0.0.1' : $ip;
    }

    private function baseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8001';

        return $scheme . '://' . $host;
    }
}
