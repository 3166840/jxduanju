<?php

namespace App\Controller;

use App\Service\PlatformService;

class ApiController
{
    public function home(): array
    {
        $service = new PlatformService();

        return [
            'json' => [
                'current_user' => $service->currentUser(),
                'app_config' => $service->clientAppConfig(array_merge($_GET, $_POST)),
                'banners' => $service->banners(),
                'dramas' => $service->frontDramas(),
                'stats' => $service->dashboard()['stats'],
            ],
        ];
    }

    public function drama(int $id): array
    {
        $service = new PlatformService();
        $drama = $service->findDrama($id);

        return [
            'json' => [
                'drama' => $drama,
                'current_user' => $service->currentUser(),
            ],
        ];
    }

    public function me(): array
    {
        $service = new PlatformService();
        $user = $service->currentUser();

        return [
            'json' => [
                'user' => $user,
                'app_config' => $service->clientAppConfig(array_merge($_GET, $_POST)),
                'orders' => array_values(array_filter($service->orders(), static fn ($order) => (int) $order['user_id'] === (int) $user['id'])),
                'entitlements' => $service->userEntitlements((int) $user['id']),
            ],
        ];
    }

    public function appConfig(): array
    {
        return ['json' => [
            'ok' => true,
            'app_config' => $this->service()->clientAppConfig(array_merge($_GET, $_POST)),
        ]];
    }

    public function operationConfig(): array
    {
        return ['json' => [
            'ok' => true,
            'operation_config' => $this->service()->clientOperationConfig(array_merge($_GET, $_POST)),
        ]];
    }

    public function activityClaim(): array
    {
        $service = $this->service();

        return ['json' => $service->claimActivityReward(array_merge($_GET, $_POST), $service->currentUserId())];
    }

    public function activityEvent(): array
    {
        $service = $this->service();

        return ['json' => $service->recordActivityEvent(array_merge($_GET, $_POST), $service->currentUserId())];
    }

    public function createEpisodeOrder(int $dramaId, int $episodeId): array
    {
        $service = new PlatformService();
        $drama = $service->findDrama($dramaId);

        return [
            'json' => $service->createOrder([
                'user_id' => $service->currentUserId(),
                'drama_id' => $dramaId,
                'episode_id' => $episodeId,
                'type' => 'episode',
                'amount' => $drama['price_per_episode'] ?? 0,
                'payment_route_id' => (string) ($_GET['payment_route_id'] ?? ''),
            ]),
        ];
    }

    public function sendSmsCode(): array
    {
        $phone = trim((string) ($_POST['phone'] ?? $_GET['phone'] ?? ''));
        $service = $this->service();
        $limit = $service->throttle('sms_send:' . preg_replace('/\D+/', '', $phone), 3, 300);
        if (!$limit['allowed']) {
            return [
                'json' => [
                    'ok' => false,
                    'message' => '验证码获取过于频繁，请 ' . (int) ceil($limit['retry_after'] / 60) . ' 分钟后再试。',
                ],
            ];
        }

        return [
            'json' => $service->sendMockSmsCode($phone),
        ];
    }

    public function loginBySms(): array
    {
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $code = trim((string) ($_POST['code'] ?? ''));
        $service = $this->service();
        $limit = $service->throttle('sms_login:' . preg_replace('/\D+/', '', $phone), 8, 300);
        if (!$limit['allowed']) {
            return [
                'json' => [
                    'ok' => false,
                    'message' => '验证码登录尝试过于频繁，请稍后再试。',
                ],
            ];
        }

        return [
            'json' => $service->loginBySmsCode($phone, $code),
        ];
    }

    public function smsReceipt(): array
    {
        $rawBody = file_get_contents('php://input') ?: '';
        $jsonPayload = json_decode($rawBody, true);
        $payload = array_merge($_GET, $_POST, is_array($jsonPayload) ? $jsonPayload : []);
        $headers = function_exists('getallheaders') ? (array) getallheaders() : [];

        return ['json' => $this->service()->handleSmsDeliveryReceipt($payload, $headers, $rawBody)];
    }

    public function inAppMessages(): array
    {
        return ['json' => $this->service()->userInAppMessages(array_merge($_GET, $_POST), $this->service()->currentUserId())];
    }

    public function markInAppMessagesRead(): array
    {
        return ['json' => $this->service()->markUserInAppMessagesRead(array_merge($_GET, $_POST), $this->service()->currentUserId())];
    }

    public function archiveInAppMessages(): array
    {
        return ['json' => $this->service()->archiveUserInAppMessages(array_merge($_GET, $_POST), $this->service()->currentUserId())];
    }

    public function deleteInAppMessages(): array
    {
        return ['json' => $this->service()->deleteUserInAppMessages(array_merge($_GET, $_POST), $this->service()->currentUserId())];
    }

    public function playerOrder(): array
    {
        $service = $this->service();
        $dramaId = (int) ($_POST['drama_id'] ?? 0);
        $episodeId = (int) ($_POST['episode_id'] ?? 0);
        $plan = trim((string) ($_POST['plan'] ?? 'episode_unlock'));
        $paymentRouteId = trim((string) ($_POST['payment_route_id'] ?? ''));
        $appKey = $service->currentAppKey($_POST);
        $isVipPlan = $service->vipPlan($plan, $appKey) !== null;
        $drama = $service->findDrama($dramaId);
        if (!$drama && $plan !== 'coin_recharge' && !$isVipPlan) {
            return ['json' => ['ok' => false, 'message' => '短剧不存在。']];
        }
        $drama ??= ['id' => 0, 'title' => '精秀短剧'];

        if ($plan === 'drama_unlock') {
            $response = $this->buildPlayerOrderResponse($service, $drama, $dramaId, null, $paymentRouteId, 'drama_unlock', (float) ($drama['full_unlock_price'] ?? 0), (int) round((float) ($drama['full_unlock_price'] ?? 0) * 100), '全集解锁');
        } elseif ($isVipPlan) {
            $response = $this->buildVipOrderResponse($service, $drama, $plan, $paymentRouteId, $appKey);
        } elseif ($plan === 'coin_recharge') {
            $response = $this->buildCoinRechargeResponse($service, $paymentRouteId, $appKey);
        } else {
            $response = $this->buildPlayerOrderResponse($service, $drama, $dramaId, $episodeId, $paymentRouteId, 'episode_unlock', (float) ($drama['price_per_episode'] ?? 0), max(1, (int) ($drama['episode_coin_price'] ?? 0)), '单集解锁');
        }

        return ['json' => $response];
    }

    public function novelOrder(): array
    {
        $service = $this->service();
        $novelId = (int) ($_POST['novel_id'] ?? 0);
        $chapterId = (int) ($_POST['chapter_id'] ?? 0);
        $plan = trim((string) ($_POST['plan'] ?? 'novel_chapter_unlock'));
        $paymentRouteId = trim((string) ($_POST['payment_route_id'] ?? ''));
        $novel = $service->findNovel($novelId);
        if (!$novel) {
            return ['json' => ['ok' => false, 'message' => '小说不存在。']];
        }

        $chapter = $this->chapterFromNovel($novel, $chapterId);
        if ($plan !== 'novel_unlock' && empty($chapter)) {
            return ['json' => ['ok' => false, 'message' => '章节不存在。']];
        }

        $response = $plan === 'novel_unlock'
            ? $this->buildNovelOrderResponse($service, $novel, $novelId, null, $paymentRouteId, 'novel_unlock', (float) ($novel['full_unlock_price'] ?? 0), (int) round((float) ($novel['full_unlock_price'] ?? 0) * 100), '整本小说解锁')
            : $this->buildNovelOrderResponse($service, $novel, $novelId, $chapterId, $paymentRouteId, 'novel_chapter_unlock', max(0.01, ((int) ($novel['chapter_coin_price'] ?? 99)) / 100), max(1, (int) ($novel['chapter_coin_price'] ?? 99)), '小说章节解锁');

        return ['json' => $response];
    }

    public function followDrama(): array
    {
        return ['json' => $this->service()->toggleFollowDrama((int) ($_POST['drama_id'] ?? 0))];
    }

    public function watchHistory(): array
    {
        return ['json' => $this->service()->recordWatchHistory((int) ($_POST['drama_id'] ?? 0), (int) ($_POST['episode_id'] ?? 0), (int) ($_POST['progress'] ?? 0))];
    }

    public function autoUnlock(): array
    {
        return ['json' => $this->service()->setAutoUnlockNext(!empty($_POST['enabled']))];
    }

    public function redeemCode(): array
    {
        $service = $this->service();
        $limit = $service->throttle('redeem_code:' . $service->currentUserId(), 10, 300);
        if (!$limit['allowed']) {
            return [
                'json' => [
                    'ok' => false,
                    'message' => '兑换操作过于频繁，请稍后再试。',
                ],
            ];
        }

        return ['json' => $service->redeemCode((string) ($_POST['code'] ?? $_GET['code'] ?? ''), $service->currentUserId(), array_merge($_GET, $_POST))];
    }

    public function redeemCodeBatch(): array
    {
        $service = $this->service();
        $limit = $service->throttle('redeem_code_batch:' . $service->currentUserId(), 10, 300);
        if (!$limit['allowed']) {
            return [
                'json' => [
                    'ok' => false,
                    'message' => '兑换操作过于频繁，请稍后再试。',
                ],
            ];
        }

        return ['json' => $service->redeemCodeBatch((string) ($_POST['batch_no'] ?? $_GET['batch_no'] ?? ''), $service->currentUserId(), array_merge($_GET, $_POST))];
    }

    public function promotionEvent(): array
    {
        $event = trim((string) ($_POST['event'] ?? $_GET['event'] ?? ''));
        if ($event === 'add-desk' || $event === 'add_desktop') {
            $event = 'add_desktop';
        }
        if ($event === 'activation') {
            $event = 'activate';
        }

        return ['json' => $this->service()->recordPromotionEvent($event, [
            'code' => (string) ($_POST['code'] ?? $_GET['code'] ?? ''),
            'promotion_link_id' => (int) ($_POST['promotion_link_id'] ?? $_GET['promotion_link_id'] ?? 0),
            'path' => (string) ($_POST['path'] ?? $_GET['path'] ?? ''),
            'traffic_platform' => (string) ($_POST['traffic_platform'] ?? $_GET['traffic_platform'] ?? $_POST['platform'] ?? $_GET['platform'] ?? ''),
            'channel_id' => (string) ($_POST['channel_id'] ?? $_GET['channel_id'] ?? ''),
            'media_app_id' => (string) ($_POST['media_app_id'] ?? $_GET['media_app_id'] ?? $_POST['app_id'] ?? $_GET['app_id'] ?? ''),
            'ad_id' => (string) ($_POST['ad_id'] ?? $_GET['ad_id'] ?? ''),
            'creative_id' => (string) ($_POST['creative_id'] ?? $_GET['creative_id'] ?? ''),
            'material_id' => (string) ($_POST['material_id'] ?? $_GET['material_id'] ?? ''),
        ])];
    }

    public function feedbackSubmit(): array
    {
        $service = $this->service();
        $limit = $service->throttle('feedback_submit:' . $service->currentUserId(), 5, 300);
        if (!$limit['allowed']) {
            return [
                'json' => [
                    'ok' => false,
                    'message' => '反馈提交过于频繁，请稍后再试。',
                ],
            ];
        }

        return ['json' => $service->submitFeedback(array_merge($_GET, $_POST))];
    }

    public function contentComments(): array
    {
        return ['json' => [
            'ok' => true,
            'comments' => $this->service()->publicContentComments(array_merge($_GET, $_POST)),
        ]];
    }

    public function commentSubmit(): array
    {
        $service = $this->service();
        $limit = $service->throttle('comment_submit:' . $service->currentUserId(), 10, 300);
        if (!$limit['allowed']) {
            return [
                'json' => [
                    'ok' => false,
                    'message' => '评论提交过于频繁，请稍后再试。',
                ],
            ];
        }

        return ['json' => $service->submitContentComment(array_merge($_GET, $_POST))];
    }

    public function adSlots(): array
    {
        return ['json' => [
            'ok' => true,
            'ad_slots' => $this->service()->activeAdSlots(array_merge($_GET, $_POST)),
        ]];
    }

    public function adPlatformConfigs(): array
    {
        return ['json' => [
            'ok' => true,
            'ad_platform_configs' => $this->service()->activeAdPlatformConfigs(array_merge($_GET, $_POST)),
        ]];
    }

    public function adDeliveryRules(): array
    {
        return ['json' => [
            'ok' => true,
            'ad_delivery_rules' => $this->service()->activeAdDeliveryRules(array_merge($_GET, $_POST)),
        ]];
    }

    public function adEvent(): array
    {
        $service = $this->service();
        $limit = $service->throttle('ad_event:' . $service->currentUserId(), 60, 60);
        if (!$limit['allowed']) {
            return [
                'json' => [
                    'ok' => false,
                    'message' => '广告事件上报过于频繁，请稍后再试。',
                ],
            ];
        }

        return ['json' => $service->recordAdEvent(array_merge($_GET, $_POST))];
    }

    public function callback(string $orderNo): array
    {
        $service = new PlatformService();
        if (!$service->adminLoggedIn() || !$service->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            return [
                'json' => [
                    'ok' => false,
                    'message' => '补单接口需要后台登录和安全令牌。',
                ],
            ];
        }

        return [
            'json' => [
                'order' => $service->confirmOrderPaid($orderNo, 'admin_api_repair', '后台 API 补单确认支付成功。'),
            ],
        ];
    }

    private function service(): PlatformService
    {
        return new PlatformService();
    }

    private function buildPlayerOrderResponse(PlatformService $service, array $drama, int $dramaId, ?int $episodeId, string $paymentRouteId, string $type, float $yuanAmount, int $coinCost, string $subject): array
    {
        $userId = $service->currentUserId();
        $canUnlock = $type === 'drama_unlock'
            ? $service->hasAccess($userId, $dramaId)
            : ($episodeId !== null && ($service->isEpisodeFree($drama, $this->episodeFromDrama($drama, $episodeId)) || $service->hasAccess($userId, $dramaId, $episodeId)));
        if ($canUnlock) {
            return ['ok' => true, 'unlocked' => true, 'message' => '当前内容已解锁。'];
        }

        if ($coinCost > 0) {
            $coinUnlock = $service->unlockWithCoins($dramaId, $episodeId, $type);
            if (!empty($coinUnlock['ok']) && !empty($coinUnlock['unlocked'])) {
                return array_merge($coinUnlock, ['ok' => true, 'payment_required' => false]);
            }
            if (!empty($coinUnlock['need_payment'])) {
                $yuanAmount = max(0.01, $yuanAmount);
            }
        }

        $order = $service->createOrder([
            'user_id' => $userId,
            'drama_id' => $dramaId,
            'episode_id' => $episodeId,
            'type' => $type,
            'amount' => round(max(0.01, $yuanAmount), 2),
            'payment_route_id' => $paymentRouteId,
            'subject' => $subject,
            'plan_code' => $type,
        ]);
        $gateway = (new \App\Service\PaymentGatewayService($service))->buildPayment($order);

        return [
            'ok' => true,
            'payment_required' => true,
            'order' => $order,
            'gateway' => $gateway,
            'cashier_url' => '/?route=payment-result&order_no=' . rawurlencode((string) $order['order_no']),
        ];
    }

    private function buildVipOrderResponse(PlatformService $service, array $drama, string $planCode, string $paymentRouteId, ?string $appKey = null): array
    {
        $plan = $service->vipPlan($planCode, $appKey);
        if (!$plan) {
            return ['ok' => false, 'message' => 'VIP 套餐不存在。'];
        }
        $coinUnlock = $service->buyVipWithCoins($planCode, $appKey);
        if (!empty($coinUnlock['ok']) && !empty($coinUnlock['unlocked'])) {
            return array_merge($coinUnlock, ['ok' => true, 'payment_required' => false]);
        }

        $product = $service->rechargeProduct($planCode);
        $order = $service->createOrder([
            'user_id' => $service->currentUserId(),
            'drama_id' => (int) ($drama['id'] ?? 0),
            'episode_id' => null,
            'type' => $planCode,
            'amount' => round((float) ($plan['price'] ?? 0), 2),
            'payment_route_id' => $paymentRouteId,
            'subject' => $plan['name'] ?? 'VIP 套餐',
            'vip_days' => (int) ($plan['days'] ?? 30),
            'plan_code' => $planCode,
            'product_code' => $planCode,
            'product_name' => (string) ($plan['name'] ?? 'VIP 套餐'),
            'product_type' => (string) ($product['type'] ?? 'vip'),
            'app_key' => $appKey,
        ]);
        $gateway = (new \App\Service\PaymentGatewayService($service))->buildPayment($order);

        return [
            'ok' => true,
            'payment_required' => true,
            'order' => $order,
            'gateway' => $gateway,
            'cashier_url' => '/?route=payment-result&order_no=' . rawurlencode((string) $order['order_no']),
        ];
    }

    private function buildCoinRechargeResponse(PlatformService $service, string $paymentRouteId, ?string $appKey = null): array
    {
        $package = $service->coinPackage((string) ($_POST['package_code'] ?? ''), $appKey);
        if (!$package) {
            $package = $service->coinPackages($appKey)[0] ?? null;
        }
        if (!$package) {
            return ['ok' => false, 'message' => '没有可用的 K币套餐。'];
        }

        $productCode = (string) ($package['code'] ?? '');
        $product = $service->rechargeProduct($productCode);
        $order = $service->createOrder([
            'user_id' => $service->currentUserId(),
            'drama_id' => 0,
            'episode_id' => null,
            'type' => 'coin_recharge',
            'amount' => round((float) ($package['price'] ?? 0), 2),
            'payment_route_id' => $paymentRouteId,
            'subject' => $package['name'] ?? 'K币充值',
            'coin_amount' => (int) ($package['coins'] ?? 0),
            'bonus_coin_amount' => (int) ($package['bonus_coins'] ?? 0),
            'package_code' => $productCode,
            'product_code' => $productCode,
            'product_name' => (string) ($package['name'] ?? 'K币充值'),
            'product_type' => (string) ($product['type'] ?? 'coin'),
            'app_key' => $appKey,
        ]);
        $gateway = (new \App\Service\PaymentGatewayService($service))->buildPayment($order);

        return [
            'ok' => true,
            'payment_required' => true,
            'order' => $order,
            'gateway' => $gateway,
            'cashier_url' => '/?route=payment-result&order_no=' . rawurlencode((string) $order['order_no']),
        ];
    }

    private function buildNovelOrderResponse(PlatformService $service, array $novel, int $novelId, ?int $chapterId, string $paymentRouteId, string $type, float $yuanAmount, int $coinCost, string $subject): array
    {
        $userId = $service->currentUserId();
        $chapter = $chapterId !== null ? $this->chapterFromNovel($novel, $chapterId) : [];
        $canUnlock = $type === 'novel_unlock'
            ? $service->hasNovelAccess($userId, $novelId)
            : ($chapterId !== null && ($service->isNovelChapterFree($novel, $chapter) || $service->hasNovelAccess($userId, $novelId, $chapterId)));
        if ($canUnlock) {
            return ['ok' => true, 'unlocked' => true, 'message' => '当前小说内容已解锁。'];
        }

        if ($coinCost > 0) {
            $coinUnlock = $service->unlockNovelWithCoins($novelId, $chapterId, $type);
            if (!empty($coinUnlock['ok']) && !empty($coinUnlock['unlocked'])) {
                return array_merge($coinUnlock, ['ok' => true, 'payment_required' => false]);
            }
            if (!empty($coinUnlock['need_payment'])) {
                $yuanAmount = max(0.01, $yuanAmount);
            }
        }

        $order = $service->createOrder([
            'user_id' => $userId,
            'content_type' => 'novel',
            'drama_id' => 0,
            'episode_id' => null,
            'novel_id' => $novelId,
            'chapter_id' => $chapterId,
            'type' => $type,
            'amount' => round(max(0.01, $yuanAmount), 2),
            'payment_route_id' => $paymentRouteId,
            'subject' => $subject,
            'plan_code' => $type,
        ]);
        $gateway = (new \App\Service\PaymentGatewayService($service))->buildPayment($order);

        return [
            'ok' => true,
            'payment_required' => true,
            'order' => $order,
            'gateway' => $gateway,
            'cashier_url' => '/?route=payment-result&order_no=' . rawurlencode((string) $order['order_no']),
        ];
    }

    private function episodeFromDrama(array $drama, int $episodeId): array
    {
        foreach ((array) ($drama['episodes'] ?? []) as $episode) {
            if ((int) ($episode['id'] ?? 0) === $episodeId) {
                return $episode;
            }
        }

        return ['id' => $episodeId, 'is_free' => false];
    }

    private function chapterFromNovel(array $novel, int $chapterId): array
    {
        foreach ((array) ($novel['chapters'] ?? []) as $chapter) {
            if ((int) ($chapter['id'] ?? 0) === $chapterId) {
                return $chapter;
            }
        }

        return [];
    }
}
