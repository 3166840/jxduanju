<?php

namespace App\Controller;

use App\Service\PlatformService;

class AccountController
{
    public function center(): array
    {
        $service = new PlatformService();
        $user = $service->currentUser();

        return [
            'view' => 'account/center',
            'data' => [
                'user' => $user,
                'orders' => array_values(array_filter($service->dashboard()['orders'], static fn ($order) => (int) $order['user_id'] === (int) $user['id'])),
                'entitlements' => $service->userEntitlements((int) $user['id']),
            ],
        ];
    }

    public function wode(): array
    {
        $service = new PlatformService();
        $user = $service->currentUser();
        $userId = (int) $user['id'];
        $appKey = $service->currentAppKey($_GET);

        return [
            'view' => 'account/wode',
            'data' => [
                'user' => $user,
                'orders' => array_values(array_filter($service->orders(), static fn ($order) => (int) ($order['user_id'] ?? 0) === $userId)),
                'entitlements' => $service->userEntitlements($userId),
                'watch_history' => $service->watchHistory($userId),
                'followed_dramas' => $service->followedDramas($userId),
                'coin_transactions' => $service->coinTransactions($userId),
                'dramas' => $service->dramas(),
                'app_key' => $appKey,
                'vip_plans' => $service->vipPlans($appKey),
                'coin_packages' => $service->coinPackages($appKey),
            ],
        ];
    }

    public function zhuiju(): array
    {
        $service = new PlatformService();
        $userId = $service->currentUserId();

        return [
            'view' => 'account/zhuiju',
            'data' => [
                'user' => $service->currentUser(),
                'followed_dramas' => $service->followedDramas($userId),
                'watch_history' => $service->watchHistory($userId),
                'dramas' => $service->dramas(),
            ],
        ];
    }

    public function denglu(): array
    {
        return [
            'view' => 'account/denglu',
            'data' => [
                'user' => (new PlatformService())->currentUser(),
            ],
        ];
    }

    public function huiyuan(): array
    {
        $service = new PlatformService();
        $appKey = $service->currentAppKey($_GET);

        return [
            'view' => 'account/huiyuan',
            'data' => [
                'user' => $service->currentUser(),
                'app_key' => $appKey,
                'vip_plans' => $service->vipPlans($appKey),
                'payment_routes' => $service->enabledPaymentRoutes(),
            ],
        ];
    }

    public function bind(): array
    {
        return [
            'view' => 'account/bind',
            'data' => ['user' => (new PlatformService())->currentUser()],
        ];
    }

    public function saveBind(): array
    {
        $nickname = trim((string) ($_POST['nickname'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));

        if ($nickname === '') {
            $nickname = '新用户';
        }

        $service = new PlatformService();
        $user = $service->bindCurrentUser($nickname, $phone);

        return [
            'view' => 'account/bind',
            'data' => [
                'user' => $user,
                'message' => '账号绑定成功。',
            ],
        ];
    }
}
