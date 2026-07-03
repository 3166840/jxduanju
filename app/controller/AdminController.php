<?php

namespace App\Controller;

use App\Service\PaymentGatewayService;
use App\Service\PlatformService;

class AdminController
{
    public function index(): array
    {
        $service = new PlatformService();
        $message = null;
        $activeSection = 'overview';
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $requestedSection = (string) ($_GET['admin_section'] ?? 'orders');
            if (preg_match('/^[a-z0-9-]+$/', $requestedSection) === 1) {
                $activeSection = $requestedSection;
            }
            if ($this->hasOrderFilters($_GET)) {
                $activeSection = in_array($requestedSection, ['orders', 'pending-orders', 'paid-orders', 'refund-orders', 'repair-orders', 'payment-query'], true)
                    ? $requestedSection
                    : 'orders';
            }
            if ($this->hasCallbackFilters($_GET)) {
                $activeSection = 'callback-config';
            }
            if ($this->hasAnalyticsFilters($_GET)) {
                $activeSection = in_array($requestedSection, ['revenue-trend', 'content-conversion', 'operation-alerts'], true)
                    ? $requestedSection
                    : 'revenue-trend';
            }
        }

        if (!$service->adminLoggedIn()) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['admin_action'] ?? '') === 'login') {
                $limit = $service->throttle('admin_login:' . strtolower(trim((string) ($_POST['username'] ?? ''))), 5, 300);
                if (!$service->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
                    $message = '安全令牌已失效，请刷新页面后重试。';
                } elseif (!$limit['allowed']) {
                    $message = '登录尝试过于频繁，请 ' . (int) ceil($limit['retry_after'] / 60) . ' 分钟后再试。';
                } elseif (!$service->verifyAdminLoginChallenge($_POST)) {
                    $message = '请先拖动滑块完成安全验证。';
                } elseif ($service->adminLogin(trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''))) {
                    header('Location: /jxdjadmin');
                    exit;
                } else {
                    $message = '账号或密码错误。';
                }
            }

            return [
                'view' => 'admin/login',
                'data' => [
                    'message' => $message,
                    'login_challenge' => $service->adminLoginChallenge($_SERVER['REQUEST_METHOD'] === 'POST'),
                    'csrf_token' => $service->csrfToken(),
                ],
            ];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = (string) ($_POST['admin_action'] ?? '');
            $activeSection = (string) ($_POST['admin_section'] ?? match ($action) {
                'update_payment' => 'payment',
                'save_payment_route' => 'payment-channel',
                'save_payment_route_policy' => 'channel-polling',
                'create_test_payment_order' => 'payment-channel',
                'save_recharge_product', 'save_recharge_config', 'save_recharge_template' => 'recharge-products',
                'generate_agent_settlement', 'update_agent_settlement', 'bulk_update_agent_settlements', 'ack_agent_settlement', 'resolve_agent_settlement_dispute', 'update_agent_payout_batch', 'export_agent_settlements_csv', 'export_agent_payout_batch_csv' => 'agent-settlement',
                'save_app' => 'apps',
                'save_mini_program_config', 'create_mini_program_sync_task', 'run_mini_program_task_action', 'refresh_mini_program_access_token' => 'mini-program',
                'create_drama', 'update_drama', 'create_episode', 'update_episode' => 'dramas',
                'bulk_update_work_status', 'bulk_delete_works' => 'works-list',
                'create_novel', 'update_novel', 'create_novel_chapter', 'update_novel_chapter' => 'novels',
                'bulk_update_content_units' => 'episodes',
                'save_media_content' => (string) ($_POST['media_type'] ?? '') === 'h5' ? 'media-h5' : 'media-wallpapers',
                'save_content_tag', 'save_content_group', 'import_content_batch' => 'content-tags',
                'update_content_ops' => 'shelf-review',
                'update_content_comment' => 'content-comments',
                'update_user' => 'users',
                'repair_user_rights' => 'rights-repair',
                'update_feedback' => 'feedback',
                'update_banner' => 'banner',
                'create_promotion_link', 'save_promotion_cost', 'save_promotion_replacement_rule' => 'promotion-links',
                'save_redeem_code', 'generate_redeem_code_batch', 'import_redeem_code_pool', 'export_redeem_code_batch_csv' => 'coupon-code',
                'save_home_recommendation' => 'home-recommend',
                'save_hot_rank_config' => 'hot-rank',
                'save_popup_notice' => 'popup-notice',
                'save_activity_config', 'export_activity_funnel_csv' => 'activity-config',
                'save_landing_page' => 'landing-pages',
                'save_ad_slot', 'save_ad_platform_config', 'save_ad_delivery_rule', 'save_ad_waterfall_config' => 'ad-slots',
                'save_agent' => 'agent-accounts',
                'save_callback_config', 'apply_callback_template', 'send_callback_log', 'bulk_send_callback_logs', 'export_callback_logs_csv' => 'callback-config',
                'save_filter_preset', 'delete_filter_preset' => match ((string) ($_POST['preset_scope'] ?? '')) {
                    'callback_logs' => 'callback-config',
                    'analytics' => in_array((string) ($_POST['preset_return_section'] ?? ''), ['play-stats', 'revenue-trend', 'content-conversion', 'operation-alerts', 'user-growth', 'recharge-hourly', 'payment-success'], true) ? (string) $_POST['preset_return_section'] : 'revenue-trend',
                    default => 'orders',
                },
                'sync_operation_alert_notifications', 'update_operation_alert_notification', 'save_operation_alert_notification_config', 'send_operation_alert_notification', 'bulk_send_operation_alert_notifications', 'execute_promotion_stop_task', 'bulk_execute_promotion_stop_tasks', 'query_promotion_stop_task_status', 'bulk_query_promotion_stop_tasks', 'save_promotion_stop_adapter_config', 'test_promotion_stop_adapter_config', 'refresh_promotion_stop_adapter_account_token' => 'operation-alerts',
                'create_analytics_review_task', 'update_analytics_review_task', 'send_analytics_review_task_reminder', 'submit_analytics_review_material_proposal', 'review_analytics_review_material_proposal', 'refresh_analytics_review_task_effect' => 'revenue-trend',
                'query_order_payment', 'bulk_query_order_payment', 'refund_order', 'query_refund_status', 'manual_callback_order', 'bulk_manual_callback_orders', 'export_orders_csv' => 'orders',
                'save_message_template', 'send_in_app_message', 'resend_business_in_app_message' => 'message-template',
                'update_admin_account', 'save_admin_account' => 'settings',
                'save_notification_config', 'send_test_notification' => 'notification-config',
                'update_base_config' => 'base-config',
                'create_config_change_request', 'review_config_change_request', 'rollback_config_change_request', 'send_config_change_sla_reminder' => 'config-approval',
                'save_system_config_fragment' => 'config-fragments',
                'update_homepage_template' => 'homepage-template',
                'update_design_home' => 'page-decoration',
                'create_data_backup', 'restore_data_backup', 'download_data_file' => 'maintenance',
                default => 'overview',
            });
            if (!$service->verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
                $message = '安全令牌已失效，请刷新页面后重试。';
                $service->recordAdminOperation($action, $activeSection, 'failed', $message, $_POST);
                return [
                    'view' => 'admin/dashboard',
                    'data' => array_merge($service->dashboard(), ['message' => $message, 'active_admin_section' => $activeSection]),
                ];
            }
            if (!$this->canRunAdminAction($service, $action)) {
                $message = '当前后台角色没有权限执行该操作。';
                $service->recordAdminOperation($action, $activeSection, 'denied', $message, $_POST);
                return [
                    'view' => 'admin/dashboard',
                    'data' => array_merge($service->dashboard(), ['message' => $message, 'active_admin_section' => $activeSection]),
                ];
            }

            switch ($action) {
                case 'update_drama':
                    $service->updateDrama($_POST);
                    $message = '剧集信息已保存。';
                    break;
                case 'create_drama':
                    $service->createDrama($_POST);
                    $message = '新短剧已创建。';
                    break;
                case 'update_episode':
                    $service->updateEpisode($_POST);
                    $message = '剧集分集已更新。';
                    break;
                case 'create_episode':
                    $service->createEpisode($_POST);
                    $message = '新分集已创建。';
                    break;
                case 'update_novel':
                    $service->updateNovel($_POST);
                    $message = '小说信息已保存。';
                    break;
                case 'create_novel':
                    $service->createNovel($_POST);
                    $message = '新小说已创建。';
                    break;
                case 'update_novel_chapter':
                    $service->updateNovelChapter($_POST);
                    $message = '小说章节已更新。';
                    break;
                case 'create_novel_chapter':
                    $service->createNovelChapter($_POST);
                    $message = '新小说章节已创建。';
                    break;
                case 'bulk_update_content_units':
                    $result = $service->bulkUpdateContentUnits($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '分集/章节已批量更新。' : '分集/章节批量更新失败。'));
                    break;
                case 'bulk_update_work_status':
                    $result = $service->bulkUpdateWorkStatus($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '作品状态已批量更新。' : '作品状态批量更新失败。'));
                    break;
                case 'bulk_delete_works':
                    $result = $service->bulkDeleteWorks($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '作品已批量删除。' : '作品批量删除失败。'));
                    break;
                case 'save_media_content':
                    $result = $service->saveMediaContent($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '扩展作品已保存。' : '扩展作品保存失败。'));
                    break;
                case 'save_content_tag':
                    $result = $service->saveContentTag($_POST);
                    $message = !empty($result['ok']) ? '内容标签已保存。' : (string) ($result['message'] ?? '内容标签保存失败。');
                    break;
                case 'save_content_group':
                    $result = $service->saveContentGroup($_POST);
                    $message = !empty($result['ok']) ? '内容分组已保存。' : (string) ($result['message'] ?? '内容分组保存失败。');
                    break;
                case 'import_content_batch':
                    $result = $service->importContentBatch($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '内容批量导入完成。' : '内容批量导入失败。'));
                    break;
                case 'update_content_ops':
                    $result = $service->updateContentOps($_POST);
                    $message = !empty($result['ok']) ? '内容运营信息已保存。' : (string) ($result['message'] ?? '内容运营信息保存失败。');
                    break;
                case 'update_banner':
                    $service->updateBanner($_POST);
                    $message = 'Banner 已更新。';
                    break;
                case 'create_promotion_link':
                    try {
                        $link = $service->createPromotionLink($_POST);
                        $message = '推广链接已创建：/?route=promo&code=' . $link['code'];
                    } catch (\Throwable $exception) {
                        $message = $exception->getMessage() !== '' ? $exception->getMessage() : '推广链接创建失败。';
                    }
                    break;
                case 'save_promotion_cost':
                    $result = $service->savePromotionCost($_POST);
                    $message = !empty($result['ok']) ? '投放消耗已记录。' : (string) ($result['message'] ?? '投放消耗记录失败。');
                    if (!empty($result['auto_pause']['paused'])) {
                        $message .= ' 已自动暂停推广入口：' . (string) ($result['auto_pause']['reason'] ?? '');
                        if (!empty($result['auto_pause']['notification']['created'])) {
                            $message .= ' 已生成投放预警待办。';
                        } elseif (!empty($result['auto_pause']['notification']['updated'])) {
                            $message .= ' 已更新投放预警待办。';
                        }
                        if (!empty($result['auto_pause']['external_notify'])) {
                            $message .= ' 外部通知：' . (string) ($result['auto_pause']['external_notify']['message'] ?? '');
                        }
                        if (!empty($result['auto_pause']['stop_task']['created'])) {
                            $message .= ' 已生成平台停投任务。';
                        }
                    }
                    break;
                case 'save_promotion_replacement_rule':
                    $result = $service->savePromotionReplacementRule($_POST);
                    $message = !empty($result['ok']) ? '推广入口替换规则已保存。' : (string) ($result['message'] ?? '替换规则保存失败。');
                    break;
                case 'save_redeem_code':
                    $result = $service->saveRedeemCode($_POST);
                    $message = !empty($result['ok']) ? '兑换码已保存。' : (string) ($result['message'] ?? '兑换码保存失败。');
                    break;
                case 'generate_redeem_code_batch':
                    $result = $service->generateRedeemCodeBatch($_POST);
                    $message = !empty($result['ok']) ? ('批量兑换码已生成：' . (int) ($result['count'] ?? 0) . ' 个，批次 ' . (string) ($result['batch_no'] ?? '')) : (string) ($result['message'] ?? '批量兑换码生成失败。');
                    break;
                case 'import_redeem_code_pool':
                    $payload = $_POST;
                    if (isset($_FILES['import_codes_file'])) {
                        $payload['import_codes_file'] = $_FILES['import_codes_file'];
                    }
                    $result = $service->importRedeemCodePool($payload);
                    $message = !empty($result['ok'])
                        ? ('外部兑换码已导入：' . (int) ($result['count'] ?? 0) . ' 个，跳过重复 ' . (int) ($result['skipped_duplicate'] ?? 0) . ' 个，批次 ' . (string) ($result['batch_no'] ?? ''))
                        : (string) ($result['message'] ?? '外部兑换码导入失败。');
                    break;
                case 'save_home_recommendation':
                    $result = $service->saveHomeRecommendation($_POST);
                    $message = !empty($result['ok']) ? '首页推荐已保存。' : (string) ($result['message'] ?? '首页推荐保存失败。');
                    break;
                case 'save_hot_rank_config':
                    $result = $service->saveHotRankConfig($_POST);
                    $message = !empty($result['ok']) ? '热播榜单已保存。' : (string) ($result['message'] ?? '热播榜单保存失败。');
                    break;
                case 'save_popup_notice':
                    $result = $service->savePopupNotice($_POST);
                    $message = !empty($result['ok']) ? '弹窗公告已保存。' : (string) ($result['message'] ?? '弹窗公告保存失败。');
                    break;
                case 'save_activity_config':
                    $result = $service->saveActivityConfig($_POST);
                    $message = !empty($result['ok']) ? '活动配置已保存。' : (string) ($result['message'] ?? '活动配置保存失败。');
                    break;
                case 'save_landing_page':
                    $result = $service->saveLandingPage($_POST);
                    $message = !empty($result['ok']) ? '推广落地页已保存：/lp/' . (string) ($result['landing_page']['slug'] ?? '') : (string) ($result['message'] ?? '推广落地页保存失败。');
                    break;
                case 'save_message_template':
                    $result = $service->saveMessageTemplate($_POST);
                    $message = !empty($result['ok']) ? '消息模板已保存。' : (string) ($result['message'] ?? '消息模板保存失败。');
                    break;
                case 'send_in_app_message':
                    $result = $service->sendInAppMessage($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '站内消息已投递。' : '站内消息投递失败。'));
                    break;
                case 'resend_business_in_app_message':
                    $result = $service->resendBusinessInAppMessage($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '业务站内消息已重发。' : '业务站内消息重发失败。'));
                    break;
                case 'save_ad_slot':
                    $result = $service->saveAdSlot($_POST);
                    $message = !empty($result['ok']) ? '广告位已保存。' : (string) ($result['message'] ?? '广告位保存失败。');
                    break;
                case 'save_ad_platform_config':
                    $result = $service->saveAdPlatformConfig($_POST);
                    $message = !empty($result['ok']) ? '广告平台配置已保存。' : (string) ($result['message'] ?? '广告平台配置保存失败。');
                    break;
                case 'save_ad_delivery_rule':
                    $result = $service->saveAdDeliveryRule($_POST);
                    $message = !empty($result['ok']) ? '广告分层策略已保存。' : (string) ($result['message'] ?? '广告分层策略保存失败。');
                    break;
                case 'save_ad_waterfall_config':
                    $result = $service->updateAdWaterfallConfig($_POST);
                    $message = !empty($result['ok']) ? '广告瀑布流配置已保存。' : (string) ($result['message'] ?? '广告瀑布流配置保存失败。');
                    break;
                case 'save_agent':
                    $result = $service->saveAgent($_POST);
                    $message = !empty($result['ok']) ? '投放账号已保存。' : (string) ($result['message'] ?? '投放账号保存失败。');
                    break;
                case 'save_callback_config':
                    $service->updateCallbackConfig($_POST);
                    $message = '回传配置已保存。';
                    break;
                case 'apply_callback_template':
                    $result = $service->applyCallbackTemplate($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '回传模板已应用。' : '回传模板应用失败。'));
                    break;
                case 'send_callback_log':
                    $result = $service->sendCallbackLog((int) ($_POST['callback_log_id'] ?? 0));
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '回传发送成功。' : '回传发送失败。'));
                    break;
                case 'bulk_send_callback_logs':
                    $result = $service->sendPendingCallbackLogs((int) ($_POST['limit'] ?? 50));
                    $message = (string) ($result['message'] ?? '批量回传已处理。');
                    break;
                case 'sync_operation_alert_notifications':
                    $result = $service->syncOperationAlertNotifications();
                    $message = (string) ($result['message'] ?? '投放预警已同步。');
                    break;
                case 'update_operation_alert_notification':
                    $result = $service->updateOperationAlertNotification($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '预警处理状态已保存。' : '预警处理失败。'));
                    break;
                case 'save_operation_alert_notification_config':
                    $result = $service->updateOperationAlertNotificationConfig($_POST);
                    $message = (string) ($result['message'] ?? '预警通知配置已保存。');
                    break;
                case 'save_promotion_stop_adapter_config':
                    $result = $service->savePromotionStopAdapterConfig($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '平台停投适配器已保存。' : '平台停投适配器保存失败。'));
                    break;
                case 'test_promotion_stop_adapter_config':
                    $result = $service->testPromotionStopAdapterConfig($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '平台停投适配器试连成功。' : '平台停投适配器试连失败。'));
                    break;
                case 'refresh_promotion_stop_adapter_account_token':
                    $result = $service->refreshPromotionStopAdapterAccountToken($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '平台授权账号 token 已刷新。' : '平台授权账号 token 刷新失败。'));
                    break;
                case 'send_operation_alert_notification':
                    $result = $service->sendOperationAlertNotification((int) ($_POST['alert_id'] ?? 0));
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '预警通知发送成功。' : '预警通知发送失败。'));
                    break;
                case 'bulk_send_operation_alert_notifications':
                    $result = $service->sendPendingOperationAlertNotifications((int) ($_POST['limit'] ?? 20));
                    $message = (string) ($result['message'] ?? '预警通知批量发送已处理。');
                    break;
                case 'execute_promotion_stop_task':
                    $result = $service->executePromotionStopTask($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '平台停投任务已执行。' : '平台停投任务执行失败。'));
                    break;
                case 'bulk_execute_promotion_stop_tasks':
                    $result = $service->executePendingPromotionStopTasks($_POST);
                    $message = (string) ($result['message'] ?? '平台停投任务批量执行已处理。');
                    break;
                case 'query_promotion_stop_task_status':
                    $result = $service->queryPromotionStopTaskStatus($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '平台停投结果已回查。' : '平台停投结果回查失败。'));
                    break;
                case 'bulk_query_promotion_stop_tasks':
                    $result = $service->queryProcessingPromotionStopTasks($_POST);
                    $message = (string) ($result['message'] ?? '平台停投结果批量回查已处理。');
                    break;
                case 'create_analytics_review_task':
                    $result = $service->createAnalyticsReviewTask($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '复盘任务已创建。' : '复盘任务创建失败。'));
                    break;
                case 'update_analytics_review_task':
                    $result = $service->updateAnalyticsReviewTask($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '复盘任务已保存。' : '复盘任务保存失败。'));
                    break;
                case 'send_analytics_review_task_reminder':
                    $result = $service->sendAnalyticsReviewTaskReminder($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '复盘任务提醒已发送。' : '复盘任务提醒失败。'));
                    break;
                case 'submit_analytics_review_material_proposal':
                    $result = $service->submitAnalyticsReviewMaterialProposal($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '素材替换审批已提交。' : '素材替换审批提交失败。'));
                    break;
                case 'review_analytics_review_material_proposal':
                    $result = $service->reviewAnalyticsReviewMaterialProposal($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '素材替换审批已处理。' : '素材替换审批处理失败。'));
                    break;
                case 'refresh_analytics_review_task_effect':
                    $result = $service->refreshAnalyticsReviewTaskEffect($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '复盘效果已刷新。' : '复盘效果刷新失败。'));
                    break;
                case 'manual_callback_order':
                    $result = $service->manualCallbackOrder((string) ($_POST['order_no'] ?? ''), !empty($_POST['send_now']));
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '订单回传已处理。' : '订单回传处理失败。'));
                    break;
                case 'bulk_manual_callback_orders':
                    $limit = $service->throttle('admin_bulk_manual_callback', 3, 120);
                    if (!$limit['allowed']) {
                        $message = '批量手动回传过于频繁，请 ' . (int) $limit['retry_after'] . ' 秒后再试。';
                        break;
                    }
                    $result = $service->bulkManualCallbackOrders((array) ($_POST['order_nos'] ?? []), !empty($_POST['send_now']), 100);
                    $message = (string) ($result['message'] ?? '批量手动回传已处理。');
                    break;
                case 'update_user':
                    $service->updateUser($_POST);
                    $message = '用户信息已更新。';
                    break;
                case 'repair_user_rights':
                    $result = $service->repairUserRights($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '权益处理成功。' : '权益处理失败。'));
                    break;
                case 'update_feedback':
                    $result = $service->updateFeedbackStatus($_POST);
                    $message = !empty($result['ok']) ? '反馈处理状态已保存。' : (string) ($result['message'] ?? '反馈处理失败。');
                    break;
                case 'update_content_comment':
                    $result = $service->updateContentComment($_POST);
                    $message = !empty($result['ok']) ? '评论审核状态已保存。' : (string) ($result['message'] ?? '评论审核失败。');
                    break;
                case 'update_payment':
                    $result = $service->updatePaymentConfig($_POST);
                    $message = !empty($result['ok']) ? (string) ($result['message'] ?? '默认支付配置已保存。') : (string) ($result['message'] ?? '默认支付配置保存失败。');
                    break;
                case 'save_payment_route':
                    $service->savePaymentRoute($_POST);
                    $message = '支付通道路线已保存。';
                    break;
                case 'save_payment_route_policy':
                    $service->updatePaymentRoutePolicy($_POST);
                    $message = '支付通道轮询策略已保存。';
                    break;
                case 'create_test_payment_order':
                    $testAmount = round((float) ($_POST['test_amount'] ?? 0), 2);
                    if ($testAmount < 0.01 || $testAmount > 9999.99) {
                        $message = '测试金额必须在 0.01 - 9999.99 之间。';
                        break;
                    }

                    $routeId = trim((string) ($_POST['payment_route_id'] ?? ''));
                    if ($routeId === '' || !$service->paymentRoute($routeId)) {
                        $message = '请选择一个有效的支付通道后再发起测试。';
                        break;
                    }

                    $order = $service->createTestPaymentOrder([
                        'payment_route_id' => $routeId,
                        'amount' => $testAmount,
                        'test_subject' => (string) ($_POST['test_subject'] ?? '支付通道测试'),
                    ]);
                    $service->recordAdminOperation($action, $activeSection, 'success', '创建支付通道测试订单：' . (string) ($order['order_no'] ?? ''), $_POST);
                    header('Location: /?route=payment-test-result&order_no=' . rawurlencode((string) $order['order_no']));
                    exit;
                case 'save_recharge_product':
                    $service->updateRechargeProduct($_POST);
                    $message = '充值商品已保存。';
                    break;
                case 'save_recharge_config':
                    $service->updateRechargeConfig($_POST);
                    $message = '默认充值和挽留商品配置已保存。';
                    break;
                case 'save_recharge_template':
                    $result = $service->updateRechargeTemplate($_POST);
                    $message = !empty($result['ok']) ? '应用商品模板已保存。' : (string) ($result['message'] ?? '应用商品模板保存失败。');
                    break;
                case 'generate_agent_settlement':
                    $result = $service->generateAgentSettlements($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '代理结算已生成。' : '代理结算生成失败。'));
                    break;
                case 'update_agent_settlement':
                    $payload = $_POST;
                    if (isset($_FILES['payout_proof_file'])) {
                        $payload['payout_proof_file'] = $_FILES['payout_proof_file'];
                    }
                    $result = $service->updateAgentSettlement($payload);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '代理结算状态已保存。' : '代理结算处理失败。'));
                    break;
                case 'bulk_update_agent_settlements':
                    $payload = $_POST;
                    if (isset($_FILES['payout_proof_file'])) {
                        $payload['payout_proof_file'] = $_FILES['payout_proof_file'];
                    }
                    $result = $service->bulkUpdateAgentSettlements($payload);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '代理结算批量处理完成。' : '代理结算批量处理失败。'));
                    break;
                case 'ack_agent_settlement':
                    $result = $service->acknowledgeAgentSettlement($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '结算对账已确认。' : '结算对账确认失败。'));
                    break;
                case 'resolve_agent_settlement_dispute':
                    $result = $service->resolveAgentSettlementDispute($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '结算异议已处理。' : '结算异议处理失败。'));
                    break;
                case 'update_agent_payout_batch':
                    $payload = $_POST;
                    if (isset($_FILES['proof_file'])) {
                        $payload['proof_file'] = $_FILES['proof_file'];
                    }
                    $result = $service->updateAgentPayoutBatch($payload);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '代理打款批次已更新。' : '代理打款批次更新失败。'));
                    break;
                case 'save_app':
                    $result = $service->saveApp($_POST);
                    $message = !empty($result['ok']) ? (string) ($result['message'] ?? '应用配置已保存。') : (string) ($result['message'] ?? '应用配置保存失败。');
                    break;
                case 'save_mini_program_config':
                    $result = $service->saveMiniProgramConfig($_POST);
                    $message = !empty($result['ok']) ? '小程序配置已保存。' : (string) ($result['message'] ?? '小程序配置保存失败。');
                    break;
                case 'create_mini_program_sync_task':
                    $result = $service->createMiniProgramSyncTask($_POST);
                    $message = !empty($result['ok']) ? '小程序同步任务已生成。' : (string) ($result['message'] ?? '小程序同步任务生成失败。');
                    break;
                case 'run_mini_program_task_action':
                    $result = $service->runMiniProgramTaskAction($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '小程序发布任务已处理。' : '小程序发布任务处理失败。'));
                    break;
                case 'refresh_mini_program_access_token':
                    $result = $service->refreshMiniProgramAccessToken($_POST);
                    $message = (string) ($result['message'] ?? (!empty($result['ok']) ? '小程序 access_token 已刷新。' : '小程序 access_token 刷新失败。'));
                    break;
                case 'update_admin_account':
                    $service->updateAdminAccount($_POST);
                    $message = '后台账号已更新。';
                    break;
                case 'save_admin_account':
                    $result = $service->saveAdminAccount($_POST);
                    $message = !empty($result['ok']) ? '后台账号已保存。' : (string) ($result['message'] ?? '后台账号保存失败。');
                    break;
                case 'save_notification_config':
                    $result = $service->updateNotificationConfig($_POST);
                    $message = !empty($result['ok']) ? '短信和邮件配置已保存。' : (string) ($result['message'] ?? '短信和邮件配置保存失败。');
                    break;
                case 'send_test_notification':
                    $result = $service->sendTestNotification($_POST);
                    $message = !empty($result['ok']) ? (string) ($result['message'] ?? '测试发送已记录。') : (string) ($result['message'] ?? '测试发送失败。');
                    break;
                case 'update_base_config':
                    $service->updateBaseConfig($_POST);
                    $message = '基础配置已保存。';
                    break;
                case 'create_config_change_request':
                    $result = $service->createConfigChangeRequest($_POST);
                    $message = !empty($result['ok']) ? '配置变更申请已提交。' : (string) ($result['message'] ?? '配置变更申请提交失败。');
                    break;
                case 'review_config_change_request':
                    $result = $service->reviewConfigChangeRequest($_POST);
                    $message = !empty($result['ok']) ? (string) ($result['message'] ?? '配置变更已处理。') : (string) ($result['message'] ?? '配置变更处理失败。');
                    break;
                case 'rollback_config_change_request':
                    $result = $service->rollbackConfigChangeRequest($_POST);
                    $message = !empty($result['ok']) ? (string) ($result['message'] ?? '配置已回滚。') : (string) ($result['message'] ?? '配置回滚失败。');
                    break;
                case 'send_config_change_sla_reminder':
                    $result = $service->sendConfigChangeSlaReminder($_POST);
                    $message = !empty($result['ok']) ? (string) ($result['message'] ?? '审批催办已发送。') : (string) ($result['message'] ?? '审批催办发送失败。');
                    break;
                case 'save_system_config_fragment':
                    $result = $service->saveSystemConfigFragment($_POST);
                    $message = !empty($result['ok']) ? '配置片段已保存。' : (string) ($result['message'] ?? '配置片段保存失败。');
                    break;
                case 'update_homepage_template':
                    $service->updateHomepageTemplate($_POST);
                    $message = '首页模版已保存。';
                    break;
                case 'update_design_home':
                    $service->updateDesignHome($_POST);
                    $message = !empty($_POST['apply_home_diy']) ? 'DIY 首页已保存并启用。' : 'DIY 首页装修已保存。';
                    break;
                case 'query_order_payment':
                    $limit = $service->throttle('admin_query_payment', 20, 60);
                    if (!$limit['allowed']) {
                        $message = '支付状态查询过于频繁，请 ' . (int) $limit['retry_after'] . ' 秒后再试。';
                        break;
                    }
                    $orderNo = (string) ($_POST['order_no'] ?? '');
                    $scopedData = $service->dashboard();
                    $order = $service->findOrder($orderNo, $scopedData);
                    if (!$order) {
                        $message = '订单不存在。';
                        break;
                    }
                    $gateway = new PaymentGatewayService($service);
                    if (!$gateway->supportsOrderAction($order, 'query')) {
                        $message = '该支付通道暂未接入状态查询。';
                        break;
                    }
                    if (($order['status'] ?? '') === 'paid') {
                        $message = '订单已支付，无需重复查询。';
                        break;
                    }
                    if (in_array(($order['status'] ?? ''), ['refunded', 'partial_refunded'], true)) {
                        $message = '订单已退款，无法查询支付成功。';
                        break;
                    }
                    $query = $gateway->queryOrder($order);
                    if (!empty($query['paid'])) {
                        $service->confirmOrderPaid($orderNo, 'admin_query', (string) ($query['message'] ?? '单笔查询确认支付成功。'));
                        $isTestOrder = !empty($order['is_test']);
                        $service->recordOrderAction($orderNo, 'query_payment', $isTestOrder ? '单笔查询到测试支付成功，已更新测试订单状态。' : '单笔查询到支付成功，已自动发放权益。', ['status' => $query['status'] ?? 'paid'], true);
                        $message = $isTestOrder ? '查询到测试支付成功，测试订单状态已更新。' : '查询到支付成功，权益已自动发放。';
                    } else {
                        $service->updateOrderPaymentState($orderNo, (string) ($query['status'] ?? 'pending'), (string) ($query['message'] ?? '支付未完成。'), 'admin_query');
                        $service->recordOrderAction($orderNo, 'query_payment', '单笔查询支付状态未完成。', ['status' => $query['status'] ?? 'pending', 'message' => $query['message'] ?? ''], false);
                        $message = '暂未查询到支付成功：' . ($query['message'] ?? '支付未完成。');
                    }
                    break;
                case 'bulk_query_order_payment':
                    $limit = $service->throttle('admin_bulk_query_payment', 5, 120);
                    if (!$limit['allowed']) {
                        $message = '批量查询过于频繁，请 ' . (int) $limit['retry_after'] . ' 秒后再试。';
                        break;
                    }
                    $orderNos = array_values(array_unique(array_filter(array_map('strval', (array) ($_POST['order_nos'] ?? [])))));
                    $orderNos = array_slice($orderNos, 0, 100);
                    $scopedData = $service->dashboard();
                    $queried = 0;
                    $paid = 0;
                    $unpaid = 0;
                    $skipped = 0;
                    $gateway = new PaymentGatewayService($service);
                    foreach ($orderNos as $orderNo) {
                        $order = $service->findOrder($orderNo, $scopedData);
                        if (!$order || !$gateway->supportsOrderAction($order, 'query') || ($this->orderStatusForAdmin($order) !== 'pending')) {
                            $skipped++;
                            continue;
                        }

                        $queried++;
                        $query = $gateway->queryOrder($order);
                        if (!empty($query['paid'])) {
                            $service->confirmOrderPaid($orderNo, 'admin_bulk_query', (string) ($query['message'] ?? '批量查询确认支付成功。'));
                            $service->recordOrderAction($orderNo, 'bulk_query_payment', !empty($order['is_test']) ? '批量查询到测试支付成功，已更新测试订单状态。' : '批量查询到支付成功，已自动发放权益。', ['status' => $query['status'] ?? 'paid'], true);
                            $paid++;
                        } else {
                            $service->updateOrderPaymentState($orderNo, (string) ($query['status'] ?? 'pending'), (string) ($query['message'] ?? '支付未完成。'), 'admin_bulk_query');
                            $service->recordOrderAction($orderNo, 'bulk_query_payment', '批量查询支付状态未完成。', ['status' => $query['status'] ?? 'pending', 'message' => $query['message'] ?? ''], false);
                            $unpaid++;
                        }
                    }
                    $message = '批量查询完成：本次查询 ' . $queried . ' 笔，支付成功 ' . $paid . ' 笔，未支付 ' . $unpaid . ' 笔，跳过 ' . $skipped . ' 笔。';
                    break;
                case 'refund_order':
                    $orderNo = (string) ($_POST['order_no'] ?? '');
                    $scopedData = $service->dashboard();
                    $order = $service->findOrder($orderNo, $scopedData);
                    if (!$order) {
                        $message = '订单不存在。';
                        break;
                    }
                    $gateway = new PaymentGatewayService($service);
                    if (!$gateway->supportsOrderAction($order, 'refund')) {
                        $message = '该支付通道暂未接入退款接口。';
                        break;
                    }
                    $paymentDisplay = $service->paymentDisplayForOrder($order);
                    $refundAmount = round((float) ($_POST['refund_amount'] ?? 0), 2);
                    $paidAmount = (float) ($order['amount'] ?? 0);
                    $refundedTotal = (float) ($order['refund_total'] ?? $order['refund_amount'] ?? 0);
                    $remainingAmount = round(max(0, $paidAmount - $refundedTotal), 2);
                    if (($order['status'] ?? '') === 'refunded' && $remainingAmount <= 0) {
                        $message = '订单已退款，请勿重复操作。';
                        break;
                    }
                    if (!in_array(($order['status'] ?? ''), ['paid', 'partial_refunded', 'refunded'], true)) {
                        $message = '只有已支付订单才能退款。';
                        break;
                    }
                    if ($service->latestPendingRefundRequest($order)) {
                        $message = '该订单已有退款正在处理中，请先查询退款状态后再继续操作。';
                        break;
                    }
                    if ($refundAmount <= 0) {
                        $message = '退款金额必须大于 0。';
                        break;
                    }
                    if ($refundAmount > $remainingAmount) {
                        $message = '退款金额不能超过剩余可退金额 ￥' . number_format($remainingAmount, 2) . '。';
                        break;
                    }
                    $refund = $gateway->refundOrder($order, $refundAmount);
                    if (!empty($refund['ok'])) {
                        $service->createRefundRequest(
                            $orderNo,
                            (float) $refund['refund_amount'],
                            (string) $refund['refund_no'],
                            (string) ($refund['gateway_refund_no'] ?? ''),
                            $refund['remote_response'] ?? null
                        );
                        $service->recordOrderAction($orderNo, 'refund_apply', '退款申请已提交，等待查询退款状态。', ['refund_no' => $refund['refund_no'] ?? '', 'amount' => $refund['refund_amount'] ?? $refundAmount], true);
                        $message = '提交成功：退款申请已提交到' . ($paymentDisplay['channel_name'] ?? '当前支付通道') . '，当前为处理中。请稍后在订单详情里点击“查询退款状态”，确认成功后系统才会更新订单退款状态。';
                    } else {
                        $failedRefundNo = (string) (($refund['refund_no'] ?? '') ?: ('RF' . date('YmdHis') . substr(md5($orderNo . $refundAmount), 0, 8)));
                        $service->createFailedRefundRequest($orderNo, $refundAmount, $failedRefundNo, $refund['remote_response'] ?? null, (string) ($refund['message'] ?? '请检查支付配置。'));
                        $service->recordOrderAction($orderNo, 'refund_apply', '退款申请提交失败。', ['refund_no' => $failedRefundNo, 'amount' => $refundAmount, 'message' => $refund['message'] ?? ''], false);
                        $message = '提交失败：' . ($refund['message'] ?? '请检查支付配置。') . '，失败记录已保存到退款信息。';
                    }
                    break;
                case 'query_refund_status':
                    $limit = $service->throttle('admin_query_refund', 15, 60);
                    if (!$limit['allowed']) {
                        $message = '退款状态查询过于频繁，请 ' . (int) $limit['retry_after'] . ' 秒后再试。';
                        break;
                    }
                    $orderNo = (string) ($_POST['order_no'] ?? '');
                    $refundNo = (string) ($_POST['refund_no'] ?? '');
                    $scopedData = $service->dashboard();
                    $order = $service->findOrder($orderNo, $scopedData);
                    if (!$order) {
                        $message = '订单不存在。';
                        break;
                    }
                    $gateway = new PaymentGatewayService($service);
                    if (!$gateway->supportsOrderAction($order, 'refund_query')) {
                        $message = '该支付通道暂未接入退款查询。';
                        break;
                    }
                    $refundRequest = $this->refundRequestForQuery($service, $order, $refundNo);
                    if (!$refundRequest) {
                        $message = '没有找到待查询的退款申请。';
                        break;
                    }
                    $query = $gateway->queryRefund($order, $refundRequest);
                    if (!empty($query['refunded'])) {
                        $service->refundOrder(
                            $orderNo,
                            (float) ($query['refund_amount'] ?? $refundRequest['amount'] ?? 0),
                            (string) ($refundRequest['refund_no'] ?? $refundNo),
                            $query['remote_response'] ?? null
                        );
                        $service->recordOrderAction($orderNo, 'query_refund', '查询到退款成功，订单退款状态已更新。', ['refund_no' => $refundRequest['refund_no'] ?? $refundNo, 'status' => $query['status'] ?? 'success'], true);
                        $message = '查询到退款成功，订单退款状态已更新。';
                    } elseif (!empty($query['failed'])) {
                        $service->markRefundRequestFailed($orderNo, (string) ($refundRequest['refund_no'] ?? $refundNo), $query['remote_response'] ?? null);
                        $service->recordOrderAction($orderNo, 'query_refund', '查询到退款失败。', ['refund_no' => $refundRequest['refund_no'] ?? $refundNo, 'status' => $query['status'] ?? 'failed', 'message' => $query['message'] ?? ''], false);
                        $message = '查询到退款失败：' . ($query['message'] ?? '通道返回退款失败。');
                    } else {
                        $service->updateRefundRequestQuery($orderNo, (string) ($refundRequest['refund_no'] ?? $refundNo), $query['remote_response'] ?? null);
                        $service->recordOrderAction($orderNo, 'query_refund', '退款暂未完成，仍在处理中。', ['refund_no' => $refundRequest['refund_no'] ?? $refundNo, 'status' => $query['status'] ?? 'processing', 'message' => $query['message'] ?? ''], false);
                        $message = '退款暂未完成：' . ($query['message'] ?? '通道返回处理中，请稍后再查。');
                    }
                    break;
                case 'save_filter_preset':
                    $message = $service->saveFilterPreset($_POST);
                    break;
                case 'delete_filter_preset':
                    $message = $service->deleteFilterPreset($_POST);
                    break;
                case 'export_orders_csv':
                    $service->recordAdminOperation($action, $activeSection, 'success', '导出订单 CSV。', $_POST);
                    $this->exportOrdersCsv($service, $_POST);
                    exit;
                case 'export_callback_logs_csv':
                    $service->recordAdminOperation($action, $activeSection, 'success', '导出回传日志 CSV。', $_POST);
                    $this->exportCallbackLogsCsv($service, $_POST);
                    exit;
                case 'export_agent_settlements_csv':
                    $service->recordAdminOperation($action, $activeSection, 'success', '导出代理结算 CSV。', $_POST);
                    $this->exportAgentSettlementsCsv($service, $_POST);
                    exit;
                case 'export_agent_payout_batch_csv':
                    $service->recordAdminOperation($action, $activeSection, 'success', '导出代理批量打款 CSV。', $_POST);
                    $this->exportAgentPayoutBatchCsv($service, $_POST);
                    exit;
                case 'export_redeem_code_batch_csv':
                    $service->recordAdminOperation($action, $activeSection, 'success', '导出兑换码批次 CSV。', $_POST);
                    $this->exportRedeemCodeBatchCsv($service, $_POST);
                    exit;
                case 'export_activity_funnel_csv':
                    $service->recordAdminOperation($action, $activeSection, 'success', '导出活动复盘 CSV。', $_POST);
                    $this->exportActivityFunnelCsv($service);
                    exit;
                case 'create_data_backup':
                    $backup = $service->createDataBackup('manual');
                    $message = $backup ? 'MySQL 备份已创建：' . $backup['file'] : 'MySQL 备份创建失败。';
                    break;
                case 'restore_data_backup':
                    $backupFile = (string) ($_POST['backup_file'] ?? '');
                    $message = $service->restoreDataBackup($backupFile) ? 'MySQL 数据已从 SQL 备份恢复，恢复前已自动备份当前数据。' : '恢复失败：SQL 备份文件不存在或内容无效。';
                    break;
                case 'download_data_file':
                    try {
                        $service->recordAdminOperation($action, $activeSection, 'success', '下载当前 MySQL SQL 备份。', $_POST);
                        header('Content-Type: application/sql; charset=utf-8');
                        header('Content-Disposition: attachment; filename="jingxiu-mysql-' . date('Ymd-His') . '.sql"');
                        echo $service->exportDataSql();
                        exit;
                    } catch (\Throwable $exception) {
                        $message = 'SQL 备份导出失败：' . $exception->getMessage();
                    }
                    break;
            }
            if ($action !== '') {
                $service->recordAdminOperation($action, $activeSection, $this->operationStatusFromMessage($message), (string) ($message ?? $this->adminActionLabel($action)), $_POST);
            }
        }

        return [
            'view' => 'admin/dashboard',
            'data' => array_merge($service->dashboard(), ['message' => $message, 'active_admin_section' => $activeSection]),
        ];
    }

    public function logout(): array
    {
        (new PlatformService())->adminLogout();
        header('Location: /jxdjadmin');
        exit;
    }

    private function orderStatusForAdmin(array $order): string
    {
        $status = (string) ($order['status'] ?? 'pending');
        $amount = (float) ($order['amount'] ?? 0);
        $refunded = (float) ($order['refund_total'] ?? $order['refund_amount'] ?? 0);
        if ($status === 'refunded' && max(0, $amount - $refunded) > 0.004) {
            return 'partial_refunded';
        }

        return in_array($status, ['paid', 'refund_pending', 'partial_refunded', 'refunded', 'failed', 'closed', 'expired'], true) ? $status : 'pending';
    }

    private function hasOrderFilters(array $query): bool
    {
        foreach (['order_no', 'user_keyword', 'status', 'per_page', 'page', 'payment_route_id', 'promotion_code', 'traffic_platform', 'channel_id', 'media_app_id', 'ad_id', 'material_id'] as $key) {
            if (isset($query[$key]) && (string) $query[$key] !== '') {
                return true;
            }
        }

        return false;
    }

    private function hasCallbackFilters(array $query): bool
    {
        foreach (['callback_status', 'callback_event', 'callback_order_no', 'callback_code', 'callback_platform', 'callback_app_key', 'callback_ad_id', 'callback_material_id'] as $key) {
            if (isset($query[$key]) && (string) $query[$key] !== '') {
                return true;
            }
        }

        return false;
    }

    private function dateFromPayload(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $timestamp = strtotime(str_replace('T', ' ', $value));
        if ($timestamp === false) {
            return '';
        }

        return date('Y-m-d', $timestamp);
    }

    private function hasAnalyticsFilters(array $query): bool
    {
        foreach (['analytics_app_key', 'analytics_business_id', 'analytics_leader_id', 'analytics_agent_id', 'analytics_promotion_link_id', 'analytics_promotion_code', 'analytics_traffic_platform', 'analytics_channel_id', 'analytics_ad_id', 'analytics_material_id'] as $key) {
            if (isset($query[$key]) && (string) $query[$key] !== '' && (string) $query[$key] !== '0') {
                return true;
            }
        }

        return false;
    }

    private function operationStatusFromMessage(?string $message): string
    {
        $message = (string) ($message ?? '');
        foreach (['失败', '不存在', '不能', '无效', '过于频繁', '没有权限', '暂未', '请选择', '不能为空', '错误'] as $keyword) {
            if (str_contains($message, $keyword)) {
                return 'failed';
            }
        }

        return 'success';
    }

    private function adminActionLabel(string $action): string
    {
        return [
            'update_payment' => '保存支付配置',
            'save_payment_route' => '保存支付通道',
            'save_payment_route_policy' => '保存通道轮询策略',
            'save_system_config_fragment' => '保存配置片段',
            'update_base_config' => '保存基础配置',
            'update_admin_account' => '更新后台账号',
            'create_promotion_link' => '创建推广链接',
            'save_promotion_cost' => '记录投放消耗',
            'save_app' => '保存应用配置',
            'save_mini_program_config' => '保存小程序配置',
            'create_mini_program_sync_task' => '生成小程序上传清单',
            'run_mini_program_task_action' => '执行小程序发布任务',
            'refresh_mini_program_access_token' => '刷新小程序 access_token',
            'import_content_batch' => '批量导入内容',
            'bulk_update_work_status' => '批量上下架作品',
            'bulk_delete_works' => '批量删除作品',
            'create_config_change_request' => '提交配置变更',
            'review_config_change_request' => '审批配置变更',
            'rollback_config_change_request' => '回滚配置变更',
            'send_config_change_sla_reminder' => '发送审批催办',
            'repair_user_rights' => '权益补发/撤销',
            'update_content_comment' => '审核内容评论',
            'generate_agent_settlement' => '生成代理结算',
            'update_agent_settlement' => '处理代理结算',
            'bulk_update_agent_settlements' => '批量处理代理结算',
            'ack_agent_settlement' => '确认代理结算到账',
            'resolve_agent_settlement_dispute' => '处理代理结算异议',
            'update_agent_payout_batch' => '更新代理打款批次',
            'export_agent_settlements_csv' => '导出代理结算',
            'export_agent_payout_batch_csv' => '导出代理打款文件',
            'save_ad_slot' => '保存广告位',
            'save_ad_platform_config' => '保存广告平台配置',
            'save_ad_delivery_rule' => '保存广告分层策略',
            'save_ad_waterfall_config' => '保存广告瀑布流策略',
            'apply_callback_template' => '应用回传模板',
            'save_filter_preset' => '保存筛选方案',
            'delete_filter_preset' => '删除筛选方案',
            'save_redeem_code' => '保存兑换码',
            'generate_redeem_code_batch' => '批量生成兑换码',
            'import_redeem_code_pool' => '导入外部兑换码',
            'export_redeem_code_batch_csv' => '导出兑换码批次',
            'export_activity_funnel_csv' => '导出活动复盘',
            'save_home_recommendation' => '保存首页推荐',
            'save_hot_rank_config' => '保存热播榜单',
            'save_popup_notice' => '保存弹窗公告',
            'save_activity_config' => '保存活动配置',
            'save_message_template' => '保存消息模板',
            'send_in_app_message' => '投递站内消息',
            'resend_business_in_app_message' => '重发业务站内消息',
            'manual_callback_order' => '手动回传订单',
            'bulk_manual_callback_orders' => '批量回传订单',
            'query_order_payment' => '查询支付状态',
            'refund_order' => '申请退款',
            'query_refund_status' => '查询退款状态',
        ][$action] ?? $action;
    }

    private function canRunAdminAction(PlatformService $service, string $action): bool
    {
        if ($action === '') {
            return true;
        }

        $scope = $service->currentAdminScope();
        $role = (string) ($scope['role'] ?? 'super_admin');
        $superOnly = [
            'update_payment', 'save_payment_route', 'save_payment_route_policy', 'create_test_payment_order',
            'save_recharge_product', 'save_recharge_config', 'save_recharge_template', 'generate_agent_settlement', 'update_agent_settlement', 'bulk_update_agent_settlements', 'resolve_agent_settlement_dispute', 'update_agent_payout_batch', 'export_agent_settlements_csv', 'export_agent_payout_batch_csv',
            'save_app', 'save_mini_program_config', 'create_mini_program_sync_task', 'run_mini_program_task_action', 'refresh_mini_program_access_token', 'save_callback_config', 'apply_callback_template', 'save_operation_alert_notification_config', 'save_promotion_stop_adapter_config', 'test_promotion_stop_adapter_config', 'refresh_promotion_stop_adapter_account_token', 'update_base_config', 'save_system_config_fragment', 'create_config_change_request', 'review_config_change_request', 'rollback_config_change_request', 'send_config_change_sla_reminder', 'update_homepage_template',
            'update_design_home', 'save_message_template', 'send_in_app_message', 'resend_business_in_app_message', 'save_hot_rank_config', 'save_ad_slot', 'save_ad_platform_config', 'save_ad_delivery_rule', 'save_ad_waterfall_config', 'repair_user_rights', 'create_data_backup', 'restore_data_backup', 'download_data_file',
        ];
        $contentActions = ['update_drama', 'create_drama', 'update_episode', 'create_episode', 'update_novel', 'create_novel', 'update_novel_chapter', 'create_novel_chapter', 'bulk_update_content_units', 'bulk_update_work_status', 'bulk_delete_works', 'save_media_content', 'save_content_tag', 'save_content_group', 'import_content_batch', 'update_content_ops', 'update_content_comment', 'update_banner'];
        $promotionActions = ['create_promotion_link', 'save_promotion_cost', 'save_promotion_replacement_rule', 'save_home_recommendation', 'save_popup_notice', 'save_activity_config', 'export_activity_funnel_csv', 'save_landing_page', 'send_callback_log', 'bulk_send_callback_logs', 'export_callback_logs_csv', 'sync_operation_alert_notifications', 'update_operation_alert_notification', 'send_operation_alert_notification', 'bulk_send_operation_alert_notifications', 'execute_promotion_stop_task', 'bulk_execute_promotion_stop_tasks', 'create_analytics_review_task', 'update_analytics_review_task', 'send_analytics_review_task_reminder', 'submit_analytics_review_material_proposal', 'review_analytics_review_material_proposal', 'refresh_analytics_review_task_effect'];
        $orderActions = ['query_order_payment', 'bulk_query_order_payment', 'refund_order', 'query_refund_status', 'manual_callback_order', 'bulk_manual_callback_orders', 'export_orders_csv', 'update_user', 'update_feedback'];

        if ($action === 'update_admin_account') {
            return true;
        }
        if (in_array($action, ['save_filter_preset', 'delete_filter_preset'], true)) {
            return in_array($role, ['super_admin', 'business', 'leader', 'agent', 'editor'], true);
        }
        if ($action === 'ack_agent_settlement') {
            return in_array($role, ['super_admin', 'business', 'leader', 'agent'], true);
        }
        if ($action === 'save_agent') {
            return $role === 'super_admin';
        }
        if (in_array($action, ['save_redeem_code', 'generate_redeem_code_batch', 'import_redeem_code_pool', 'export_redeem_code_batch_csv'], true)) {
            return $role === 'super_admin';
        }
        if (in_array($action, $superOnly, true)) {
            return $role === 'super_admin';
        }
        if (in_array($action, $contentActions, true)) {
            return in_array($role, ['super_admin', 'editor'], true);
        }
        if (in_array($action, $promotionActions, true)) {
            return in_array($role, ['super_admin', 'business', 'leader', 'agent'], true);
        }
        if (in_array($action, $orderActions, true)) {
            return in_array($role, ['super_admin', 'business', 'leader', 'agent'], true);
        }

        return $role === 'super_admin';
    }

    private function exportOrdersCsv(PlatformService $service, array $payload): void
    {
        $filters = [
            'order_no' => trim((string) ($payload['order_no'] ?? '')),
            'user_keyword' => trim((string) ($payload['user_keyword'] ?? '')),
            'status' => trim((string) ($payload['status'] ?? 'all')),
            'payment_route_id' => trim((string) ($payload['payment_route_id'] ?? '')),
            'promotion_code' => trim((string) ($payload['promotion_code'] ?? '')),
            'traffic_platform' => trim((string) ($payload['traffic_platform'] ?? '')),
            'channel_id' => trim((string) ($payload['channel_id'] ?? '')),
            'media_app_id' => trim((string) ($payload['media_app_id'] ?? '')),
            'ad_id' => trim((string) ($payload['ad_id'] ?? '')),
            'material_id' => trim((string) ($payload['material_id'] ?? '')),
        ];

        $dashboard = $service->dashboard();
        $users = [];
        foreach ((array) ($dashboard['users'] ?? []) as $user) {
            $users[(int) ($user['id'] ?? 0)] = $user;
        }

        $matchedUsers = [];
        if ($filters['user_keyword'] !== '') {
            foreach ($users as $id => $user) {
                $phone = (string) ($user['phone'] ?? '');
                if ((string) $id === $filters['user_keyword'] || ($phone !== '' && str_contains($phone, $filters['user_keyword']))) {
                    $matchedUsers[$id] = true;
                }
            }
        }

        $orders = array_values(array_filter((array) ($dashboard['orders'] ?? []), function (array $order) use ($service, $filters, $matchedUsers): bool {
            if (!empty($order['is_test'])) {
                return false;
            }
            if ($filters['order_no'] !== '' && !str_contains((string) ($order['order_no'] ?? ''), $filters['order_no'])) {
                return false;
            }
            if ($filters['status'] !== '' && $filters['status'] !== 'all' && $this->orderStatusForAdmin($order) !== $filters['status']) {
                return false;
            }
            if ($filters['payment_route_id'] !== '') {
                $display = $service->paymentDisplayForOrder($order);
                if ((string) ($display['route_id'] ?? '') !== $filters['payment_route_id']) {
                    return false;
                }
            }
            if ($filters['user_keyword'] !== '') {
                $userId = (int) ($order['user_id'] ?? 0);
                if ((string) $userId !== $filters['user_keyword'] && empty($matchedUsers[$userId])) {
                    return false;
                }
            }
            foreach (['promotion_code', 'traffic_platform', 'channel_id', 'media_app_id', 'ad_id', 'material_id'] as $key) {
                if ($filters[$key] !== '' && !str_contains((string) ($order[$key] ?? ''), $filters[$key])) {
                    return false;
                }
            }

            return true;
        }));

        usort($orders, static function (array $a, array $b): int {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="jingxiu-orders-' . date('Ymd-His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }

        fputcsv($out, [
            '订单号', '用户ID', '手机号', '内容类型', '内容ID', '分集/章节ID', '订单类型', '金额', '订单状态',
            '支付方式', '支付服务商', '支付通道', '渠道订单号', '推广码', '推广链接ID', '来源', '计划',
            '投放平台', '渠道ID', '应用ID', '广告ID', '创意ID', '素材ID', '下单时间', '支付时间', '退款金额',
        ], ',', '"', '');

        foreach ($orders as $order) {
            $display = $service->paymentDisplayForOrder($order);
            $user = $users[(int) ($order['user_id'] ?? 0)] ?? [];
            $contentType = (string) ($order['content_type'] ?? 'drama');
            fputcsv($out, [
                (string) ($order['order_no'] ?? ''),
                (int) ($order['user_id'] ?? 0),
                (string) ($user['phone'] ?? ''),
                $contentType === 'novel' ? '小说' : '短剧',
                $contentType === 'novel' ? (int) ($order['novel_id'] ?? 0) : (int) ($order['drama_id'] ?? 0),
                $contentType === 'novel' ? (int) ($order['chapter_id'] ?? 0) : (int) ($order['episode_id'] ?? 0),
                (string) ($order['type'] ?? ''),
                number_format((float) ($order['amount'] ?? 0), 2, '.', ''),
                $this->orderStatusForAdmin($order),
                (string) ($display['method_name'] ?? ''),
                (string) ($display['provider_name'] ?? ''),
                (string) ($display['channel_name'] ?? ''),
                (string) ($order['gateway_trade_no'] ?? ''),
                (string) ($order['promotion_code'] ?? ''),
                (int) ($order['promotion_link_id'] ?? 0),
                (string) ($order['traffic_source'] ?? ''),
                (string) ($order['campaign'] ?? ''),
                (string) ($order['traffic_platform'] ?? ''),
                (string) ($order['channel_id'] ?? ''),
                (string) ($order['media_app_id'] ?? ''),
                (string) ($order['ad_id'] ?? ''),
                (string) ($order['creative_id'] ?? ''),
                (string) ($order['material_id'] ?? ''),
                (string) ($order['created_at'] ?? ''),
                (string) ($order['paid_at'] ?? ''),
                number_format((float) ($order['refund_total'] ?? $order['refund_amount'] ?? 0), 2, '.', ''),
            ], ',', '"', '');
        }

        fclose($out);
    }

    private function exportRedeemCodeBatchCsv(PlatformService $service, array $payload): void
    {
        $batchNo = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($payload['batch_no'] ?? '')));
        $dashboard = $service->dashboard();
        $codes = array_values(array_filter((array) ($dashboard['redeem_codes'] ?? []), static fn (array $item): bool => $batchNo !== '' && (string) ($item['batch_no'] ?? '') === $batchNo));
        $usageByCodeId = [];
        foreach ((array) ($dashboard['redeem_code_logs'] ?? []) as $log) {
            if ((string) ($log['status'] ?? '') !== 'success') {
                continue;
            }
            $codeId = (int) ($log['code_id'] ?? 0);
            $usageByCodeId[$codeId] = (int) ($usageByCodeId[$codeId] ?? 0) + 1;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="jingxiu-redeem-codes-' . ($batchNo !== '' ? $batchNo : date('Ymd-His')) . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fputcsv($out, ['批次号', '兑换码', '领取链接', '批次领取链接', '名称', '奖励类型', 'K币', '赠币', 'VIP天数', '应用', '推广码', '代理ID', '渠道ID', '指定用户ID', '状态', '已兑次数', '开始时间', '结束时间', '备注'], ',', '"', '');
        foreach ($codes as $code) {
            $codeId = (int) ($code['id'] ?? 0);
            $codeText = (string) ($code['code'] ?? '');
            $rowBatchNo = (string) ($code['batch_no'] ?? $batchNo);
            fputcsv($out, [
                $rowBatchNo,
                $codeText,
                '/?route=api-redeem-code&code=' . rawurlencode($codeText),
                '/?route=api-redeem-code-batch&batch_no=' . rawurlencode($rowBatchNo),
                (string) ($code['name'] ?? ''),
                (string) ($code['reward_type'] ?? ''),
                (int) ($code['coin_amount'] ?? 0),
                (int) ($code['bonus_coin_amount'] ?? 0),
                (int) ($code['vip_days'] ?? 0),
                (string) ($code['app_key'] ?? 'all'),
                (string) ($code['promotion_code'] ?? ''),
                (int) ($code['agent_id'] ?? 0),
                (string) ($code['channel_id'] ?? ''),
                implode(',', array_map('strval', (array) ($code['allowed_user_ids'] ?? []))),
                (string) ($code['status'] ?? 'active'),
                (int) ($usageByCodeId[$codeId] ?? 0),
                (string) ($code['started_at'] ?? ''),
                (string) ($code['ended_at'] ?? ''),
                (string) ($code['remark'] ?? ''),
            ], ',', '"', '');
        }
        fclose($out);
    }

    private function exportActivityFunnelCsv(PlatformService $service): void
    {
        $dashboard = $service->dashboard();
        $funnel = (array) ($dashboard['activity_funnel_dashboard'] ?? []);
        $summary = (array) ($funnel['summary'] ?? []);
        $rows = array_values((array) ($funnel['rows'] ?? []));
        $tierRows = array_values((array) ($funnel['tier_rows'] ?? []));
        $budgetRows = array_values((array) ($funnel['budget_rows'] ?? []));
        $recommendations = array_values((array) ($funnel['recommendations'] ?? []));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="jingxiu-activity-funnel-' . date('Ymd-His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }

        fputcsv($out, ['活动复盘汇总'], ',', '"', '');
        fputcsv($out, ['指标', '数值'], ',', '"', '');
        foreach ([
            '曝光' => (int) ($summary['exposure'] ?? 0),
            '点击' => (int) ($summary['click'] ?? 0),
            '领奖成功' => (int) ($summary['claim_success'] ?? 0),
            '领奖失败' => (int) ($summary['claim_failed'] ?? 0),
            '点击率' => number_format((float) ($summary['click_rate'] ?? 0), 2, '.', '') . '%',
            '领奖转化率' => number_format((float) ($summary['claim_rate'] ?? 0), 2, '.', '') . '%',
            '实验数' => (int) ($summary['experiment_count'] ?? 0),
            '版本数' => (int) ($summary['variant_count'] ?? 0),
            '人群数' => (int) ($summary['tier_count'] ?? 0),
            '配置预算活动数' => (int) ($summary['budget_limited_count'] ?? 0),
            '预算用尽活动数' => (int) ($summary['budget_exhausted_count'] ?? 0),
            '预算自动停用活动数' => (int) ($summary['budget_auto_paused_count'] ?? 0),
        ] as $label => $value) {
            fputcsv($out, [$label, $value], ',', '"', '');
        }

        fputcsv($out, [], ',', '"', '');
        fputcsv($out, ['活动/版本效果'], ',', '"', '');
        fputcsv($out, ['活动ID', '活动编码', '活动名称', '活动类型', '应用', '实验键', '版本键', '流量占比', '曝光', '点击', '领奖成功', '领奖失败', '点击率', '领奖率', '最近记录'], ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($out, [
                (int) ($row['activity_id'] ?? 0),
                (string) ($row['activity_code'] ?? ''),
                (string) ($row['activity_name'] ?? ''),
                (string) ($row['activity_type'] ?? ''),
                (string) ($row['app_key'] ?? ''),
                (string) ($row['experiment_key'] ?? ''),
                (string) ($row['variant_key'] ?? ''),
                (int) ($row['traffic_percent'] ?? 100),
                (int) ($row['exposure'] ?? 0),
                (int) ($row['click'] ?? 0),
                (int) ($row['claim_success'] ?? 0),
                (int) ($row['claim_failed'] ?? 0),
                number_format((float) ($row['click_rate'] ?? 0), 2, '.', '') . '%',
                number_format((float) ($row['claim_rate'] ?? 0), 2, '.', '') . '%',
                (string) ($row['latest_at'] ?? ''),
            ], ',', '"', '');
        }

        fputcsv($out, [], ',', '"', '');
        fputcsv($out, ['预算使用'], ',', '"', '');
        fputcsv($out, ['活动ID', '活动编码', '活动名称', '状态', '预算上限', '已用预算', '剩余预算', '使用率', '预算用完自动停用', '自动停用时间', '自动停用原因'], ',', '"', '');
        foreach ($budgetRows as $row) {
            fputcsv($out, [
                (int) ($row['activity_id'] ?? 0),
                (string) ($row['activity_code'] ?? ''),
                (string) ($row['activity_name'] ?? ''),
                (string) ($row['status'] ?? ''),
                (int) ($row['budget_limit'] ?? 0),
                (int) ($row['budget_used'] ?? 0),
                (int) ($row['budget_remaining'] ?? 0),
                number_format((float) ($row['budget_usage_rate'] ?? 0), 2, '.', '') . '%',
                !empty($row['auto_pause_on_budget']) ? '是' : '否',
                (string) ($row['budget_auto_paused_at'] ?? ''),
                (string) ($row['budget_auto_paused_reason'] ?? ''),
            ], ',', '"', '');
        }

        fputcsv($out, [], ',', '"', '');
        fputcsv($out, ['人群效果'], ',', '"', '');
        fputcsv($out, ['人群编码', '人群名称', '独立用户', '曝光', '点击', '领奖成功', '领奖失败', '点击率', '领奖率', '失败率', '最近记录'], ',', '"', '');
        foreach ($tierRows as $row) {
            fputcsv($out, [
                (string) ($row['user_tier'] ?? ''),
                (string) ($row['tier_name'] ?? ''),
                (int) ($row['unique_users'] ?? 0),
                (int) ($row['exposure'] ?? 0),
                (int) ($row['click'] ?? 0),
                (int) ($row['claim_success'] ?? 0),
                (int) ($row['claim_failed'] ?? 0),
                number_format((float) ($row['click_rate'] ?? 0), 2, '.', '') . '%',
                number_format((float) ($row['claim_rate'] ?? 0), 2, '.', '') . '%',
                number_format((float) ($row['fail_rate'] ?? 0), 2, '.', '') . '%',
                (string) ($row['latest_at'] ?? ''),
            ], ',', '"', '');
        }

        fputcsv($out, [], ',', '"', '');
        fputcsv($out, ['自动复盘建议'], ',', '"', '');
        if (empty($recommendations)) {
            fputcsv($out, ['暂无复盘建议。'], ',', '"', '');
        }
        foreach ($recommendations as $recommendation) {
            fputcsv($out, [(string) $recommendation], ',', '"', '');
        }

        fclose($out);
    }

    private function exportAgentSettlementsCsv(PlatformService $service, array $payload): void
    {
        $dashboard = $service->dashboard();
        $rows = array_values((array) ($dashboard['agent_settlements'] ?? []));
        $statusFilter = (string) ($payload['status_filter'] ?? 'all');
        $statusFilter = in_array($statusFilter, ['all', 'pending', 'confirmed', 'paid', 'rejected'], true) ? $statusFilter : 'all';
        $modeFilter = (string) ($payload['settlement_mode'] ?? 'all');
        $modeFilter = in_array($modeFilter, ['all', 'revenue_share', 'profit_share'], true) ? $modeFilter : 'all';
        $agentId = max(0, (int) ($payload['agent_id'] ?? 0));
        $periodStart = $this->dateFromPayload((string) ($payload['period_start'] ?? ''));
        $periodEnd = $this->dateFromPayload((string) ($payload['period_end'] ?? ''));
        if ($periodStart !== '' && $periodEnd !== '' && $periodEnd < $periodStart) {
            [$periodStart, $periodEnd] = [$periodEnd, $periodStart];
        }

        $idText = '';
        foreach (['settlement_ids', 'settlement_ids_text', 'ids'] as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $idText .= ',' . (is_array($payload[$key]) ? implode(',', array_map('strval', $payload[$key])) : (string) $payload[$key]);
        }
        $targetIds = [];
        foreach (preg_split('/[^0-9]+/', $idText) ?: [] as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $targetIds[$id] = true;
            }
        }

        $rows = array_values(array_filter($rows, function (array $row) use ($statusFilter, $modeFilter, $agentId, $periodStart, $periodEnd, $targetIds): bool {
            if (!empty($targetIds) && !isset($targetIds[(int) ($row['id'] ?? 0)])) {
                return false;
            }
            if ($statusFilter !== 'all' && (string) ($row['status'] ?? 'pending') !== $statusFilter) {
                return false;
            }
            if ($modeFilter !== 'all' && (string) ($row['settlement_mode'] ?? 'revenue_share') !== $modeFilter) {
                return false;
            }
            if ($agentId > 0 && !in_array($agentId, [
                (int) ($row['agent_id'] ?? 0),
                (int) ($row['leader_id'] ?? 0),
                (int) ($row['business_id'] ?? 0),
            ], true)) {
                return false;
            }
            $rowStart = $this->dateFromPayload((string) ($row['period_start'] ?? ''));
            $rowEnd = $this->dateFromPayload((string) ($row['period_end'] ?? ''));
            if ($periodStart !== '' && $rowEnd !== '' && $rowEnd < $periodStart) {
                return false;
            }
            if ($periodEnd !== '' && $rowStart !== '' && $rowStart > $periodEnd) {
                return false;
            }

            return true;
        }));

        usort($rows, static fn (array $a, array $b): int => strcmp((string) ($b['period_end'] ?? ''), (string) ($a['period_end'] ?? '')) ?: ((int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0)));
        $statusLabels = ['pending' => '待确认', 'confirmed' => '已确认', 'paid' => '已打款', 'rejected' => '已驳回'];
        $modeLabels = ['revenue_share' => '收入分成', 'profit_share' => '利润分成'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="jingxiu-agent-settlements-' . date('Ymd-His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }

        fputcsv($out, [
            '结算ID', '状态', '代理ID', '代理名称', '组长ID', '组长名称', '商务ID', '商务名称',
            '周期开始', '周期结束', '结算口径', '订单数', '付费用户', '总收入', '退款金额', '净收入', '投放成本',
            '佣金基数', '分成比例', '佣金金额', '打款方式', '收款账号', '收款人', '打款流水号', '凭证链接',
            '发票/收据号', '打款时间', '代理确认状态', '代理确认备注', '代理确认人', '代理确认时间',
            '异议状态', '异议处理方式', '异议调整金额', '异议后佣金', '异议处理结论', '异议处理人', '异议处理时间',
            '生成人', '处理人', '处理时间', '备注', '创建时间', '更新时间',
        ], ',', '"', '');

        $confirmLabels = ['none' => '未确认', 'confirmed' => '已确认到账', 'disputed' => '有异议'];
        $disputeLabels = ['none' => '无异议', 'open' => '待处理', 'processing' => '处理中', 'resolved' => '已解决', 'rejected' => '已驳回'];
        $resolutionLabels = ['keep_original' => '维持原结算', 'adjust_amount' => '调整佣金', 'supplement_payout' => '补打款', 'reject' => '驳回异议'];
        foreach ($rows as $row) {
            fputcsv($out, [
                (int) ($row['id'] ?? 0),
                $statusLabels[(string) ($row['status'] ?? 'pending')] ?? (string) ($row['status'] ?? ''),
                (int) ($row['agent_id'] ?? 0),
                (string) ($row['agent_name'] ?? ''),
                (int) ($row['leader_id'] ?? 0),
                (string) ($row['leader_name'] ?? ''),
                (int) ($row['business_id'] ?? 0),
                (string) ($row['business_name'] ?? ''),
                (string) ($row['period_start'] ?? ''),
                (string) ($row['period_end'] ?? ''),
                $modeLabels[(string) ($row['settlement_mode'] ?? 'revenue_share')] ?? (string) ($row['settlement_mode'] ?? ''),
                (int) ($row['order_count'] ?? 0),
                (int) ($row['paid_user_count'] ?? 0),
                number_format((float) ($row['gross_revenue'] ?? 0), 2, '.', ''),
                number_format((float) ($row['refund_amount'] ?? 0), 2, '.', ''),
                number_format((float) ($row['net_revenue'] ?? 0), 2, '.', ''),
                number_format((float) ($row['cost_amount'] ?? 0), 2, '.', ''),
                number_format((float) ($row['commission_base'] ?? 0), 2, '.', ''),
                number_format((float) ($row['commission_rate'] ?? 0), 2, '.', ''),
                number_format((float) ($row['commission_amount'] ?? 0), 2, '.', ''),
                (string) ($row['payout_method'] ?? ''),
                (string) ($row['payout_account'] ?? ''),
                (string) ($row['payout_name'] ?? ''),
                (string) ($row['payout_reference_no'] ?? ''),
                (string) ($row['payout_proof_url'] ?? ''),
                (string) ($row['invoice_no'] ?? ''),
                (string) ($row['paid_at'] ?? ''),
                $confirmLabels[(string) ($row['agent_confirm_status'] ?? 'none')] ?? (string) ($row['agent_confirm_status'] ?? ''),
                (string) ($row['agent_confirm_remark'] ?? ''),
                (string) ($row['agent_confirmed_by_admin_name'] ?? ''),
                (string) ($row['agent_confirmed_at'] ?? ''),
                $disputeLabels[(string) ($row['dispute_status'] ?? 'none')] ?? (string) ($row['dispute_status'] ?? ''),
                $resolutionLabels[(string) ($row['dispute_resolution_type'] ?? '')] ?? (string) ($row['dispute_resolution_type'] ?? ''),
                number_format((float) ($row['dispute_adjustment_amount'] ?? 0), 2, '.', ''),
                number_format((float) ($row['dispute_final_commission_amount'] ?? $row['commission_amount'] ?? 0), 2, '.', ''),
                (string) ($row['dispute_resolution_remark'] ?? ''),
                (string) ($row['dispute_handled_by_admin_name'] ?? ''),
                (string) ($row['dispute_handled_at'] ?? ''),
                (string) ($row['generated_by_admin_name'] ?? ''),
                (string) ($row['handled_by_admin_name'] ?? ''),
                (string) ($row['handled_at'] ?? ''),
                (string) ($row['remark'] ?? ''),
                (string) ($row['created_at'] ?? ''),
                (string) ($row['updated_at'] ?? ''),
            ], ',', '"', '');
        }
        fclose($out);
    }

    private function exportAgentPayoutBatchCsv(PlatformService $service, array $payload): void
    {
        $dashboard = $service->dashboard();
        $rows = array_values((array) ($dashboard['agent_settlements'] ?? []));
        $statusFilter = (string) ($payload['status_filter'] ?? 'confirmed');
        $statusFilter = in_array($statusFilter, ['all', 'pending', 'confirmed', 'paid', 'rejected'], true) ? $statusFilter : 'confirmed';
        $agentId = max(0, (int) ($payload['agent_id'] ?? 0));
        $periodStart = $this->dateFromPayload((string) ($payload['period_start'] ?? ''));
        $periodEnd = $this->dateFromPayload((string) ($payload['period_end'] ?? ''));
        if ($periodStart !== '' && $periodEnd !== '' && $periodEnd < $periodStart) {
            [$periodStart, $periodEnd] = [$periodEnd, $periodStart];
        }

        $idText = '';
        foreach (['settlement_ids', 'settlement_ids_text', 'ids'] as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $idText .= ',' . (is_array($payload[$key]) ? implode(',', array_map('strval', $payload[$key])) : (string) $payload[$key]);
        }
        $targetIds = [];
        foreach (preg_split('/[^0-9]+/', $idText) ?: [] as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $targetIds[$id] = true;
            }
        }

        $rows = array_values(array_filter($rows, function (array $row) use ($statusFilter, $agentId, $periodStart, $periodEnd, $targetIds): bool {
            if (!empty($targetIds) && !isset($targetIds[(int) ($row['id'] ?? 0)])) {
                return false;
            }
            if ($statusFilter !== 'all' && (string) ($row['status'] ?? 'pending') !== $statusFilter) {
                return false;
            }
            if ($agentId > 0 && !in_array($agentId, [
                (int) ($row['agent_id'] ?? 0),
                (int) ($row['leader_id'] ?? 0),
                (int) ($row['business_id'] ?? 0),
            ], true)) {
                return false;
            }
            $rowStart = $this->dateFromPayload((string) ($row['period_start'] ?? ''));
            $rowEnd = $this->dateFromPayload((string) ($row['period_end'] ?? ''));
            if ($periodStart !== '' && $rowEnd !== '' && $rowEnd < $periodStart) {
                return false;
            }
            if ($periodEnd !== '' && $rowStart !== '' && $rowStart > $periodEnd) {
                return false;
            }

            return true;
        }));

        usort($rows, static fn (array $a, array $b): int => strcmp((string) ($a['agent_name'] ?? ''), (string) ($b['agent_name'] ?? '')) ?: ((int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0)));
        $batchNo = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($payload['payout_batch_no'] ?? '')));
        if ($batchNo === '') {
            $batchNo = 'agent-pay-' . date('Ymd-His');
        }
        $channel = trim((string) ($payload['payout_channel'] ?? ''));
        $channel = $channel !== '' ? $channel : '通用打款';
        $payoutRows = [];
        $settlementIds = [];
        $agentIds = [];
        $totalAmount = 0.0;
        foreach ($rows as $row) {
            $commission = round(max(0.0, (float) ($row['commission_amount'] ?? 0)), 2);
            $finalCommission = round(max(0.0, (float) ($row['dispute_final_commission_amount'] ?? $commission)), 2);
            $disputeStatus = (string) ($row['dispute_status'] ?? 'none');
            $amount = in_array($disputeStatus, ['resolved', 'rejected'], true) ? $finalCommission : $commission;
            $payeeName = trim((string) ($row['payout_name'] ?? ''));
            $payeeAccount = trim((string) ($row['payout_account'] ?? ''));
            $method = trim((string) ($row['payout_method'] ?? ''));
            $check = [];
            if ($payeeName === '') {
                $check[] = '缺收款人';
            }
            if ($payeeAccount === '') {
                $check[] = '缺收款账号';
            }
            if ($method === '') {
                $check[] = '缺打款方式';
            }
            if ($amount <= 0) {
                $check[] = '金额为0';
            }

            $settlementId = (int) ($row['id'] ?? 0);
            $rowAgentId = (int) ($row['agent_id'] ?? 0);
            if ($settlementId > 0) {
                $settlementIds[] = $settlementId;
            }
            if ($rowAgentId > 0) {
                $agentIds[] = $rowAgentId;
            }
            $totalAmount += $amount;
            $payoutRows[] = [
                'settlement_id' => $settlementId,
                'agent_id' => $rowAgentId,
                'agent_name' => (string) ($row['agent_name'] ?? ''),
                'payee_name' => $payeeName,
                'payee_account' => $payeeAccount,
                'method' => $method,
                'amount' => $amount,
                'commission' => $commission,
                'dispute_adjustment_amount' => (float) ($row['dispute_adjustment_amount'] ?? 0),
                'period_start' => (string) ($row['period_start'] ?? ''),
                'period_end' => (string) ($row['period_end'] ?? ''),
                'order_count' => (int) ($row['order_count'] ?? 0),
                'paid_user_count' => (int) ($row['paid_user_count'] ?? 0),
                'remark' => (string) ($row['remark'] ?? ''),
                'check' => empty($check) ? '可打款' : implode(';', $check),
            ];
        }

        $record = $service->recordAgentPayoutBatch([
            'batch_no' => $batchNo,
            'channel' => $channel,
            'item_count' => count($payoutRows),
            'total_amount' => $totalAmount,
            'settlement_ids' => $settlementIds,
            'agent_ids' => $agentIds,
            'filters' => [
                'status_filter' => $statusFilter,
                'agent_id' => $agentId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'settlement_ids' => array_map('intval', array_keys($targetIds)),
            ],
            'remark' => '导出批量打款文件',
        ]);
        if (!empty($record['ok']) && is_array($record['batch'] ?? null)) {
            $batchNo = (string) ($record['batch']['batch_no'] ?? $batchNo);
        }
        $fileName = (!empty($record['ok']) && is_array($record['batch'] ?? null))
            ? (string) ($record['batch']['file_name'] ?? '')
            : '';
        if ($fileName === '') {
            $fileName = 'jingxiu-agent-payout-' . $batchNo . '.csv';
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }

        fputcsv($out, [
            '打款批次号', '打款渠道', '结算ID', '代理ID', '代理名称', '收款人', '收款账号', '打款方式',
            '打款金额', '原佣金', '异议调整', '周期开始', '周期结束', '订单数', '付费用户', '备注', '校验状态',
        ], ',', '"', '');

        foreach ($payoutRows as $row) {
            fputcsv($out, [
                $batchNo,
                $channel,
                (int) ($row['settlement_id'] ?? 0),
                (int) ($row['agent_id'] ?? 0),
                (string) ($row['agent_name'] ?? ''),
                (string) ($row['payee_name'] ?? ''),
                (string) ($row['payee_account'] ?? ''),
                (string) ($row['method'] ?? ''),
                number_format((float) ($row['amount'] ?? 0), 2, '.', ''),
                number_format((float) ($row['commission'] ?? 0), 2, '.', ''),
                number_format((float) ($row['dispute_adjustment_amount'] ?? 0), 2, '.', ''),
                (string) ($row['period_start'] ?? ''),
                (string) ($row['period_end'] ?? ''),
                (int) ($row['order_count'] ?? 0),
                (int) ($row['paid_user_count'] ?? 0),
                (string) ($row['remark'] ?? ''),
                (string) ($row['check'] ?? ''),
            ], ',', '"', '');
        }
        fclose($out);
    }

    private function exportCallbackLogsCsv(PlatformService $service, array $payload): void
    {
        $filters = [
            'status' => trim((string) ($payload['callback_status'] ?? 'all')),
            'event' => trim((string) ($payload['callback_event'] ?? 'all')),
            'order_no' => trim((string) ($payload['callback_order_no'] ?? '')),
            'code' => trim((string) ($payload['callback_code'] ?? '')),
            'platform' => trim((string) ($payload['callback_platform'] ?? '')),
            'app_key' => trim((string) ($payload['callback_app_key'] ?? '')),
            'ad_id' => trim((string) ($payload['callback_ad_id'] ?? '')),
            'material_id' => trim((string) ($payload['callback_material_id'] ?? '')),
        ];
        $validStatuses = ['all', 'pending', 'success', 'failed', 'skipped'];
        $validEvents = ['all', 'add_desktop', 'paid'];
        if (!in_array($filters['status'], $validStatuses, true)) {
            $filters['status'] = 'all';
        }
        if (!in_array($filters['event'], $validEvents, true)) {
            $filters['event'] = 'all';
        }

        $dashboard = $service->dashboard();
        $logs = array_values(array_filter((array) ($dashboard['callback_logs'] ?? []), static function (array $log) use ($filters): bool {
            $payload = is_array($log['request_payload'] ?? null) ? (array) $log['request_payload'] : [];
            if ($filters['status'] !== 'all' && (string) ($log['status'] ?? 'pending') !== $filters['status']) {
                return false;
            }
            if ($filters['event'] !== 'all' && (string) ($log['event'] ?? '') !== $filters['event']) {
                return false;
            }
            if ($filters['order_no'] !== '' && !str_contains((string) ($log['order_no'] ?? ''), $filters['order_no'])) {
                return false;
            }
            if ($filters['code'] !== '' && !str_contains((string) ($log['code'] ?? ''), $filters['code'])) {
                return false;
            }
            $platform = (string) (($payload['platform'] ?? '') ?: ($log['platform'] ?? ''));
            if ($filters['platform'] !== '' && !str_contains($platform, $filters['platform'])) {
                return false;
            }
            $appKey = (string) (($log['app_key'] ?? '') ?: ($payload['app_key'] ?? ''));
            if ($filters['app_key'] !== '' && !str_contains($appKey, $filters['app_key'])) {
                return false;
            }
            foreach (['ad_id', 'material_id'] as $key) {
                $value = (string) (($log[$key] ?? '') ?: ($payload[$key] ?? ''));
                if ($filters[$key] !== '' && !str_contains($value, $filters[$key])) {
                    return false;
                }
            }

            return true;
        }));

        usort($logs, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="jingxiu-callback-logs-' . date('Ymd-His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }

        fputcsv($out, [
            '回传ID', '事件类型', '平台事件', '状态', '失败/处理信息', '推广码', '推广链接ID', '用户ID', '订单号', '金额',
            '平台', '应用Key', '应用名称', '策略来源', '投放平台', '渠道ID', '媒体应用ID', '广告ID', '创意ID', '素材ID',
            '回传地址', '尝试次数', '最后尝试时间', '创建时间', '更新时间', '请求内容', '响应内容',
        ], ',', '"', '');

        foreach ($logs as $log) {
            $payload = is_array($log['request_payload'] ?? null) ? (array) $log['request_payload'] : [];
            $response = is_array($log['response_payload'] ?? null) ? (array) $log['response_payload'] : [];
            fputcsv($out, [
                (int) ($log['id'] ?? 0),
                (string) ($log['event'] ?? ''),
                (string) ($log['platform_event'] ?? ''),
                (string) ($log['status'] ?? 'pending'),
                (string) ($log['message'] ?? ''),
                (string) ($log['code'] ?? ''),
                (int) ($log['promotion_link_id'] ?? 0),
                (int) ($log['user_id'] ?? 0),
                (string) ($log['order_no'] ?? ''),
                number_format((float) ($log['amount'] ?? 0), 2, '.', ''),
                (string) (($payload['platform'] ?? '') ?: ($log['platform'] ?? '')),
                (string) (($log['app_key'] ?? '') ?: ($payload['app_key'] ?? '')),
                (string) ($log['app_name'] ?? ''),
                (string) ($log['callback_policy_source'] ?? 'global'),
                (string) (($log['traffic_platform'] ?? '') ?: ($payload['traffic_platform'] ?? '')),
                (string) (($log['channel_id'] ?? '') ?: ($payload['channel_id'] ?? '')),
                (string) (($log['media_app_id'] ?? '') ?: ($payload['media_app_id'] ?? '')),
                (string) (($log['ad_id'] ?? '') ?: ($payload['ad_id'] ?? '')),
                (string) (($log['creative_id'] ?? '') ?: ($payload['creative_id'] ?? '')),
                (string) (($log['material_id'] ?? '') ?: ($payload['material_id'] ?? '')),
                (string) ($log['endpoint'] ?? ''),
                (int) ($log['attempt_count'] ?? 0),
                (string) ($log['last_attempt_at'] ?? ''),
                (string) ($log['created_at'] ?? ''),
                (string) ($log['updated_at'] ?? ''),
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
                json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            ], ',', '"', '');
        }

        fclose($out);
    }

    private function refundRequestForQuery(PlatformService $service, array $order, string $refundNo): ?array
    {
        $requests = array_values((array) ($order['refund_requests'] ?? []));
        if ($refundNo !== '') {
            foreach (array_reverse($requests) as $request) {
                if ((string) ($request['refund_no'] ?? '') === $refundNo) {
                    return $request;
                }
            }
        }

        return $service->latestPendingRefundRequest($order);
    }
}
