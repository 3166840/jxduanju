<?php
$title = '后台管理 - 精秀短剧';
$message = $message ?? null;
$csrfToken = (string) ($csrf_token ?? '');
$csrfField = static fn (): string => '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES) . '">';
$truncate = static function (string $text, int $limit = 80): string {
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($text, 'UTF-8') > $limit ? mb_substr($text, 0, $limit, 'UTF-8') . '...' : $text;
    }

    return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
};
$payment = $payment_config ?? [];
$paymentRoutes = array_values((array) ($payment['routes'] ?? []));
$paymentRouteNameById = [];
foreach ($paymentRoutes as $route) {
    $paymentRouteNameById[(string) ($route['id'] ?? '')] = (string) (($route['channel_name'] ?? '') ?: ($route['provider_name'] ?? '支付通道'));
}
$defaultPaymentRoute = $paymentRoutes[0] ?? $payment;
foreach ($paymentRoutes as $route) {
    if (!empty($route['is_default'])) {
        $defaultPaymentRoute = $route;
        break;
    }
}
$enabledPaymentRouteCount = count(array_filter($paymentRoutes, static fn (array $route): bool => !empty($route['enabled'])));
$siteConfig = $site_config ?? [];
$designConfig = $design_config ?? [];
$designHome = $designConfig['home'] ?? [];
$designPages = array_values((array) ($designConfig['pages'] ?? []));
$homepageTemplate = (string) ($homepage_template ?? ($siteConfig['homepage_template'] ?? 'mini'));
if (!in_array($homepageTemplate, ['mini', 'marketing'], true)) {
    $homepageTemplate = 'mini';
}
$homepageTemplateOptions = [
    'mini' => [
        'code' => 'D-001',
        'label' => '小程序风格',
        'summary' => '更接近移动端短剧小程序，包含搜索、焦点 Banner、榜单和底部导航。',
        'tag' => '推荐',
        'features' => ['移动端沉浸式', '榜单推荐', '底部导航'],
    ],
    'marketing' => [
        'code' => 'D-002',
        'label' => '经典 H5 营销页',
        'summary' => '保留原本首页模版，突出平台介绍、运营推荐、热播短剧和成交数据。',
        'tag' => '原版',
        'features' => ['首屏转化', '运营 Banner', '热播卡片'],
    ],
];
$novelHomepageTemplate = (string) ($novel_homepage_template ?? ($siteConfig['novel_homepage_template'] ?? 'library'));
if (!in_array($novelHomepageTemplate, ['library', 'ranking'], true)) {
    $novelHomepageTemplate = 'library';
}
$novelHomepageTemplateOptions = [
    'library' => [
        'code' => 'N-001',
        'label' => '书城宫格',
        'summary' => '以书城首页为主，突出封面、分类、章节数量和单章价格，适合快速浏览内容池。',
        'tag' => '推荐',
        'features' => ['书城入口', '封面宫格', '分类信息'],
    ],
    'ranking' => [
        'code' => 'N-002',
        'label' => '榜单导读',
        'summary' => '首屏突出热读榜和章节信息，更适合用小说承接投放流量。',
        'tag' => '投流',
        'features' => ['热读榜单', '章节导读', '转化按钮'],
    ],
];
$designModuleOptions = [
    'search' => '搜索栏',
    'banner' => '焦点 Banner',
    'quick_nav' => '快捷导航',
    'notice' => '公告提示',
    'rank' => '榜单模块',
    'drama_grid' => '剧集宫格',
    'reward' => '悬浮福利',
    'bottom_nav' => '底部菜单',
];
$designModules = array_map('strval', (array) ($designHome['modules'] ?? array_keys($designModuleOptions)));
$designQuickNavs = array_values((array) ($designHome['quick_navs'] ?? []));
for ($i = count($designQuickNavs); $i < 4; $i++) {
    $designQuickNavs[] = ['label' => '', 'link' => '#'];
}
$designColor = static function (string $value, string $fallback): string {
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? $value : $fallback;
};
$designPrimaryColor = $designColor((string) ($designHome['primary_color'] ?? '#ef5b5f'), '#ef5b5f');
$designAccentColor = $designColor((string) ($designHome['accent_color'] ?? '#ff955d'), '#ff955d');
$dramaCategoryOptions = ['都市', '甜宠', '虐恋', '穿越', '古装'];
$dramaTitleById = [];
foreach ((array) ($dramas ?? []) as $dramaForCategory) {
    $dramaTitleById[(int) ($dramaForCategory['id'] ?? 0)] = (string) ($dramaForCategory['title'] ?? '未命名短剧');
    $category = trim((string) ($dramaForCategory['category'] ?? ''));
    if ($category !== '' && !in_array($category, $dramaCategoryOptions, true)) {
        $dramaCategoryOptions[] = $category;
    }
}
$novels = array_values((array) ($novels ?? []));
$novelTitleById = [];
foreach ($novels as $novel) {
    $novelTitleById[(int) ($novel['id'] ?? 0)] = (string) ($novel['title'] ?? '未命名小说');
}
$novelCategoryOptions = ['都市', '甜宠', '逆袭', '玄幻', '古言'];
foreach ($novels as $novelForCategory) {
    $category = trim((string) ($novelForCategory['category'] ?? ''));
    if ($category !== '' && !in_array($category, $novelCategoryOptions, true)) {
        $novelCategoryOptions[] = $category;
    }
}
$mediaContents = array_values((array) ($media_contents ?? []));
$mediaCategoryOptions = ['都市', '甜宠', '玄幻', '壁纸', 'H5'];
foreach ($mediaContents as $mediaForCategory) {
    $category = trim((string) ($mediaForCategory['category'] ?? ''));
    if ($category !== '' && !in_array($category, $mediaCategoryOptions, true)) {
        $mediaCategoryOptions[] = $category;
    }
}
$workStatusLabels = ['draft' => '草稿', 'online' => '已上架', 'offline' => '已下架'];
$workQualityLabels = ['normal' => '普通', 'featured' => '精品', 'premium' => '优质'];
$workTypeLabels = ['drama' => '剧集', 'novel' => '书籍', 'image' => '壁纸', 'h5' => 'H5'];
$coinTransactions = array_values((array) ($coin_transactions ?? []));
$rightsRepairLogs = array_values((array) ($rights_repair_logs ?? []));
$redeemCodes = array_values((array) ($redeem_codes ?? []));
$redeemCodeLogs = array_values((array) ($redeem_code_logs ?? []));
$homeRecommendations = array_values((array) ($home_recommendations ?? []));
$hotRankConfigs = array_values((array) ($hot_rank_configs ?? []));
$hotRankDashboard = (array) ($hot_rank_dashboard ?? ['summary' => [], 'configs' => [], 'rows_by_config' => []]);
$hotRankSummary = (array) ($hotRankDashboard['summary'] ?? []);
$hotRankConfigRows = array_values((array) ($hotRankDashboard['configs'] ?? []));
$hotRankPreviewByConfig = (array) ($hotRankDashboard['rows_by_config'] ?? []);
$popupNotices = array_values((array) ($popup_notices ?? []));
$activityConfigs = array_values((array) ($activity_configs ?? []));
$activityParticipationLogs = array_values((array) ($activity_participation_logs ?? []));
$activityFunnelDashboard = (array) ($activity_funnel_dashboard ?? ['summary' => [], 'rows' => []]);
$activityFunnelSummary = (array) ($activityFunnelDashboard['summary'] ?? []);
$activityFunnelRows = array_values((array) ($activityFunnelDashboard['rows'] ?? []));
$activityTierRows = array_values((array) ($activityFunnelDashboard['tier_rows'] ?? []));
$activityBudgetRows = array_values((array) ($activityFunnelDashboard['budget_rows'] ?? []));
$activityReviewSuggestions = array_values((array) ($activityFunnelDashboard['recommendations'] ?? []));
$smsCodes = array_values((array) ($sms_codes ?? []));
$messageTemplates = array_values((array) ($message_templates ?? []));
$inAppMessages = array_values((array) ($in_app_messages ?? []));
$notificationRecordDashboard = (array) ($notification_record_dashboard ?? ['summary' => [], 'rows' => [], 'channel_rows' => [], 'source_rows' => []]);
$notificationRecordSummary = (array) ($notificationRecordDashboard['summary'] ?? []);
$notificationRecordRows = array_values((array) ($notificationRecordDashboard['rows'] ?? []));
$notificationRecordChannelRows = array_values((array) ($notificationRecordDashboard['channel_rows'] ?? []));
$notificationRecordSourceRows = array_values((array) ($notificationRecordDashboard['source_rows'] ?? []));
$promotionLinks = array_values((array) ($promotion_links ?? []));
$promotionCosts = array_values((array) ($promotion_costs ?? []));
$landingPages = array_values((array) ($landing_pages ?? []));
$landingPageEvents = array_values((array) ($landing_page_events ?? []));
$adPlatformConfigs = array_values((array) ($ad_platform_configs ?? []));
$adWaterfallConfig = (array) ($ad_waterfall_config ?? []);
$adDeliveryRules = array_values((array) ($ad_delivery_rules ?? []));
$adSlots = array_values((array) ($ad_slots ?? []));
$adEvents = array_values((array) ($ad_events ?? []));
$adMonetizationDashboard = (array) ($ad_monetization_dashboard ?? ['summary' => [], 'rows' => [], 'app_rows' => [], 'position_rows' => [], 'provider_rows' => []]);
$adMonetizationSummary = (array) ($adMonetizationDashboard['summary'] ?? []);
$adMonetizationRows = array_values((array) ($adMonetizationDashboard['rows'] ?? []));
$adWaterfallRows = array_values((array) ($adMonetizationDashboard['waterfall_rows'] ?? []));
$adMonetizationRowsBySlotId = [];
foreach ($adMonetizationRows as $adMonetizationRow) {
    $adMonetizationRowsBySlotId[(int) ($adMonetizationRow['slot_id'] ?? 0)] = $adMonetizationRow;
}
$promotionDashboard = (array) ($promotion_dashboard ?? ['rows' => [], 'summary' => []]);
$promotionRows = array_values((array) ($promotionDashboard['rows'] ?? []));
$promotionSummary = (array) ($promotionDashboard['summary'] ?? []);
$callbackConfig = (array) ($callback_config ?? []);
$callbackTemplates = array_values((array) ($callback_templates ?? []));
$callbackTemplateOptions = (array) ($callback_templates ?? []);
$callbackFieldMappingText = json_encode((array) ($callbackConfig['field_mapping'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}';
$callbackAuthConfig = (array) ($callbackConfig['auth_config'] ?? []);
$callbackAuthModeLabels = [
    'none' => '不签名',
    'hmac_header' => 'HMAC Header',
    'bearer' => 'Bearer Token',
    'query_sign' => 'Query 签名',
    'body_sign' => 'Body 签名',
];
$callbackAuthMode = (string) ($callbackAuthConfig['mode'] ?? 'none');
if (!isset($callbackAuthModeLabels[$callbackAuthMode])) {
    $callbackAuthMode = 'none';
}
$callbackRetryPolicy = (array) ($callbackConfig['retry_policy'] ?? []);
$callbackLogs = array_values((array) ($callback_logs ?? []));
$adminApiLogs = array_values((array) ($api_logs ?? []));
$contentComments = array_values((array) ($content_comments ?? []));
$commentDashboard = (array) ($comment_dashboard ?? ['summary' => [], 'content_rows' => [], 'pending_rows' => [], 'risk_rows' => []]);
$commentSummary = (array) ($commentDashboard['summary'] ?? []);
$commentContentRows = array_values((array) ($commentDashboard['content_rows'] ?? []));
$feedbackItems = array_values((array) ($feedback_items ?? []));
$operationAlertNotifications = array_values((array) ($operation_alert_notifications ?? []));
$operationAlertNotificationConfig = (array) ($operation_alert_notification_config ?? []);
$operationAlertNotificationReceivers = array_values((array) ($operation_alert_notification_receivers ?? []));
$operationAlertNotificationLogs = array_values((array) ($operation_alert_notification_logs ?? []));
$promotionStopTasks = array_values((array) ($promotion_stop_tasks ?? []));
$promotionStopTaskDashboard = (array) ($promotion_stop_task_dashboard ?? ['summary' => [], 'rows' => [], 'provider_rows' => []]);
$promotionStopTaskSummary = (array) ($promotionStopTaskDashboard['summary'] ?? []);
$promotionStopTaskRows = array_values((array) ($promotionStopTaskDashboard['rows'] ?? []));
$promotionStopTaskProviderRows = array_values((array) ($promotionStopTaskDashboard['provider_rows'] ?? []));
$promotionStopAdapterConfigs = array_values((array) ($promotion_stop_adapter_configs ?? []));
$promotionStopAdapterPresets = (array) ($promotion_stop_adapter_presets ?? []);
$adminOperationLogs = array_values((array) ($admin_operation_logs ?? []));
$entitlements = array_values((array) ($entitlements ?? []));
$agentDashboard = (array) ($agent_dashboard ?? ['rows' => [], 'summary' => []]);
$agentRows = array_values((array) ($agentDashboard['rows'] ?? []));
$agentSummary = (array) ($agentDashboard['summary'] ?? []);
$agentSettlements = array_values((array) ($agent_settlements ?? []));
$agentPayoutBatches = array_values((array) ($agent_payout_batches ?? []));
$agentSettlementDashboard = (array) ($agent_settlement_dashboard ?? ['summary' => [], 'rows' => [], 'preview_rows' => []]);
$agentSettlementSummary = (array) ($agentSettlementDashboard['summary'] ?? []);
$agentSettlementRows = array_values((array) ($agentSettlementDashboard['rows'] ?? []));
$agentSettlementPreviewRows = array_values((array) ($agentSettlementDashboard['preview_rows'] ?? []));
$adminScope = (array) ($admin_scope ?? ['role' => 'super_admin', 'role_label' => '管理员', 'restricted' => false, 'agent_ids' => []]);
$adminAccounts = array_values((array) ($admin_accounts ?? []));
$adminRoleLabels = ['super_admin' => '管理员', 'business' => '商务', 'leader' => '代理组长', 'agent' => '代理', 'editor' => '编辑'];
$currentAdminId = (int) ($current_admin['id'] ?? 0);
$filterPresets = array_values((array) ($filter_presets ?? []));
$orderFilterPresets = array_values(array_filter($filterPresets, static fn (array $preset): bool => (string) ($preset['scope'] ?? '') === 'orders'));
$callbackFilterPresets = array_values(array_filter($filterPresets, static fn (array $preset): bool => (string) ($preset['scope'] ?? '') === 'callback_logs'));
$analyticsFilterPresets = array_values(array_filter($filterPresets, static fn (array $preset): bool => (string) ($preset['scope'] ?? '') === 'analytics'));
$filterPresetSummary = static function (array $preset, array $labelMap): string {
    $parts = [];
    foreach ((array) ($preset['filters'] ?? []) as $key => $value) {
        $value = trim((string) $value);
        if ($value === '') {
            continue;
        }
        $parts[] = (string) ($labelMap[(string) $key] ?? $key) . '=' . $value;
    }
    $visible = array_slice($parts, 0, 5);
    return empty($visible) ? '无筛选条件' : implode(' / ', $visible) . (count($parts) > 5 ? ' ...' : '');
};
$orderFilterPresetUrl = static function (array $preset): string {
    $params = ['admin_section' => 'orders', 'page' => 1];
    foreach (['order_no', 'user_keyword', 'payment_route_id', 'promotion_code', 'traffic_platform', 'channel_id', 'media_app_id', 'ad_id', 'material_id', 'status', 'per_page'] as $key) {
        $value = trim((string) (($preset['filters'] ?? [])[$key] ?? ''));
        if ($value !== '' && $value !== 'all') {
            $params[$key] = $value;
        }
    }

    return '/jxdjadmin?' . http_build_query($params) . '#orders';
};
$callbackFilterPresetUrl = static function (array $preset): string {
    $params = ['admin_section' => 'callback-config'];
    $mapping = [
        'status' => 'callback_status',
        'event' => 'callback_event',
        'order_no' => 'callback_order_no',
        'code' => 'callback_code',
        'platform' => 'callback_platform',
        'app_key' => 'callback_app_key',
        'ad_id' => 'callback_ad_id',
        'material_id' => 'callback_material_id',
    ];
    foreach ($mapping as $source => $target) {
        $value = trim((string) (($preset['filters'] ?? [])[$source] ?? ''));
        if ($value !== '' && $value !== 'all') {
            $params[$target] = $value;
        }
    }

    return '/jxdjadmin?' . http_build_query($params) . '#callback-config';
};
$analyticsFilterPresetUrl = static function (array $preset, string $sectionId): string {
    $params = ['admin_section' => $sectionId];
    $mapping = [
        'date_preset' => 'analytics_date_preset',
        'date_start' => 'analytics_date_start',
        'date_end' => 'analytics_date_end',
        'app_key' => 'analytics_app_key',
        'business_id' => 'analytics_business_id',
        'leader_id' => 'analytics_leader_id',
        'agent_id' => 'analytics_agent_id',
        'promotion_link_id' => 'analytics_promotion_link_id',
        'promotion_code' => 'analytics_promotion_code',
        'traffic_platform' => 'analytics_traffic_platform',
        'channel_id' => 'analytics_channel_id',
        'ad_id' => 'analytics_ad_id',
        'material_id' => 'analytics_material_id',
    ];
    foreach ($mapping as $source => $target) {
        $value = trim((string) (($preset['filters'] ?? [])[$source] ?? ''));
        if ($value !== '' && $value !== '0' && $value !== 'all') {
            $params[$target] = $value;
        }
    }

    return '/jxdjadmin?' . http_build_query($params) . '#' . $sectionId;
};
$emailDeliveryLogs = array_values((array) ($email_delivery_logs ?? []));
$smsConfig = (array) ($siteConfig['sms_config'] ?? []);
$emailConfig = (array) ($siteConfig['email_config'] ?? []);
$configApprovalPolicy = (array) ($siteConfig['config_approval_policy'] ?? []);
$agents = array_values((array) ($agents ?? []));
$agentOptions = array_values(array_filter($agents, static fn (array $agent): bool => (string) ($agent['role'] ?? '') === 'agent'));
if (empty($agentOptions)) {
    $agentOptions = $agents;
}
$businessOptions = array_values(array_filter($agents, static fn (array $agent): bool => (string) ($agent['role'] ?? '') === 'business'));
$leaderOptions = array_values(array_filter($agents, static fn (array $agent): bool => (string) ($agent['role'] ?? '') === 'leader'));
$agentPathById = [];
foreach ($agentRows as $agentRow) {
    $agentPathById[(int) ($agentRow['id'] ?? 0)] = (string) (($agentRow['path'] ?? '') ?: ($agentRow['name'] ?? '投放账号'));
}
$agentRoleLabels = ['business' => '商务', 'leader' => '组长', 'agent' => '代理'];
$callbackStatusLabels = ['pending' => '待回传', 'success' => '成功', 'failed' => '失败', 'skipped' => '已跳过'];
$callbackEventLabels = ['add_desktop' => '加桌', 'paid' => '支付', 'activate' => '激活', 'register' => '注册'];
$orderFilterPresetLabels = ['order_no' => '订单号', 'user_keyword' => '用户', 'payment_route_id' => '通道', 'promotion_code' => '推广码', 'traffic_platform' => '平台', 'channel_id' => '渠道', 'media_app_id' => '应用', 'ad_id' => '广告', 'material_id' => '素材', 'status' => '状态', 'per_page' => '每页'];
$callbackFilterPresetLabels = ['status' => '状态', 'event' => '事件', 'order_no' => '订单号', 'code' => '推广码', 'platform' => '平台', 'app_key' => '应用', 'ad_id' => '广告', 'material_id' => '素材'];
$analyticsFilterPresetLabels = ['date_preset' => '时间', 'date_start' => '开始', 'date_end' => '结束', 'app_key' => '应用', 'business_id' => '商务', 'leader_id' => '组长', 'agent_id' => '代理', 'promotion_link_id' => '入口', 'promotion_code' => '推广码', 'traffic_platform' => '平台', 'channel_id' => '渠道', 'ad_id' => '广告', 'material_id' => '素材'];
$feedbackTypeLabels = ['feedback' => '意见反馈', 'complaint' => '投诉', 'payment' => '支付问题', 'content' => '内容问题', 'account' => '账号问题', 'promotion' => '投放问题'];
$feedbackStatusLabels = ['pending' => '待处理', 'processing' => '处理中', 'resolved' => '已解决', 'rejected' => '已驳回'];
$feedbackPriorityLabels = ['low' => '低', 'normal' => '普通', 'high' => '高', 'urgent' => '紧急'];
$feedbackSlaLabels = ['normal' => 'SLA正常', 'due_soon' => '即将超时', 'overdue' => '已超时', 'handled_on_time' => '按时处理', 'handled_overdue' => '超时处理'];
$feedbackActionLabels = ['none' => '无', 'contact_user' => '联系用户', 'check_order' => '核对订单', 'query_payment' => '查支付', 'refund' => '建议退款', 'rights_repair' => '建议补发', 'content_review' => '内容复核', 'promotion_review' => '投放排查'];
$commentStatusLabels = ['pending' => '待审核', 'approved' => '已通过', 'rejected' => '已驳回', 'hidden' => '已隐藏'];
$commentRiskLabels = ['normal' => '正常', 'sensitive' => '敏感', 'spam' => '垃圾'];
$commentSentimentLabels = ['positive' => '正向', 'neutral' => '中性', 'negative' => '负向'];
$feedbackDashboard = (array) ($feedback_dashboard ?? ['summary' => [], 'app_rows' => [], 'action_rows' => [], 'overdue_rows' => [], 'suggestion_rows' => []]);
$feedbackSummary = (array) ($feedbackDashboard['summary'] ?? []);
$feedbackAppRows = array_values((array) ($feedbackDashboard['app_rows'] ?? []));
$feedbackActionRows = array_values((array) ($feedbackDashboard['action_rows'] ?? []));
$operationAlertTypeLabels = ['callback_failed' => '回传异常', 'low_conversion_material' => '低转化素材', 'high_refund_material' => '高退款素材', 'low_recovery_link' => '低回收推广', 'auto_paused_link' => '自动停投', 'promotion_stop_failed' => '停投失败', 'promotion_stop_auth_failed' => '停投授权失败', 'promotion_stop_rate_limited' => '停投限流'];
$operationAlertStatusLabels = ['pending' => '待处理', 'processing' => '处理中', 'resolved' => '已解决', 'ignored' => '已忽略'];
$operationAlertPriorityLabels = ['low' => '低', 'normal' => '普通', 'high' => '高', 'urgent' => '紧急'];
$operationAlertNotificationStatusLabels = ['pending' => '待发送', 'success' => '成功', 'failed' => '失败', 'skipped' => '已跳过'];
$operationAlertNotificationChannelLabels = ['webhook' => 'Webhook', 'wechat_work' => '企业微信', 'email' => '邮件', 'sms' => '短信', 'in_app' => '站内'];
$operationAlertReceiverScopeLabels = ['global' => '全局', 'role' => '按角色', 'agent' => '指定组织'];
$promotionStopTaskStatusLabels = ['pending' => '待执行', 'processing' => '执行中', 'success' => '已停投', 'failed' => '执行失败', 'skipped' => '已跳过', 'manual_done' => '人工完成', 'cancelled' => '已取消'];
$promotionStopTaskActionLabels = ['pause_ad' => '暂停广告', 'pause_material' => '暂停素材', 'pause_campaign' => '暂停计划', 'manual' => '人工处理'];
$promotionStopTaskErrorLabels = ['auth_failed' => '授权失败', 'rate_limited' => '平台限流', 'platform_failed' => '平台失败', 'retryable_failed' => '可重试失败', 'config_missing' => '配置缺失', 'adapter_missing' => '适配器缺失', 'unknown' => '未知失败'];
$pendingFeedbackCount = count(array_filter($feedbackItems, static fn (array $item): bool => in_array((string) ($item['status'] ?? 'pending'), ['pending', 'processing'], true)));
$pendingOperationAlertCount = count(array_filter($operationAlertNotifications, static fn (array $item): bool => in_array((string) ($item['status'] ?? 'pending'), ['pending', 'processing'], true)));
$pendingExternalOperationAlertCount = count(array_filter($operationAlertNotifications, static function (array $item) use ($operationAlertNotificationConfig): bool {
    $statuses = array_values((array) ($operationAlertNotificationConfig['send_statuses'] ?? ['pending', 'processing']));
    $rank = ['low' => 0, 'normal' => 1, 'high' => 2, 'urgent' => 3];
    $priority = (string) ($item['priority'] ?? 'normal');
    $minPriority = (string) ($operationAlertNotificationConfig['min_priority'] ?? 'normal');
    if (empty($operationAlertNotificationConfig['retry_failed']) && (string) ($item['external_notify_status'] ?? '') === 'failed') {
        return false;
    }

    return in_array((string) ($item['status'] ?? 'pending'), $statuses, true) && (($rank[$priority] ?? 1) >= ($rank[$minPriority] ?? 1));
}));
$pendingCallbackCount = count(array_filter($callbackLogs, static function (array $log): bool {
    $status = (string) ($log['status'] ?? '');
    if ($status === 'pending') {
        return true;
    }

    return $status === 'failed' && (!array_key_exists('callback_retry_failed', $log) || !empty($log['callback_retry_failed']));
}));
$callbackFilters = [
    'status' => (string) ($_GET['callback_status'] ?? 'all'),
    'event' => (string) ($_GET['callback_event'] ?? 'all'),
    'order_no' => trim((string) ($_GET['callback_order_no'] ?? '')),
    'code' => trim((string) ($_GET['callback_code'] ?? '')),
    'platform' => trim((string) ($_GET['callback_platform'] ?? '')),
    'app_key' => trim((string) ($_GET['callback_app_key'] ?? '')),
    'ad_id' => trim((string) ($_GET['callback_ad_id'] ?? '')),
    'material_id' => trim((string) ($_GET['callback_material_id'] ?? '')),
];
if (!isset($callbackStatusLabels[$callbackFilters['status']]) && $callbackFilters['status'] !== 'all') {
    $callbackFilters['status'] = 'all';
}
if (!isset($callbackEventLabels[$callbackFilters['event']]) && $callbackFilters['event'] !== 'all') {
    $callbackFilters['event'] = 'all';
}
$callbackMatchesFilters = static function (array $log) use ($callbackFilters): bool {
    $payload = is_array($log['request_payload'] ?? null) ? (array) $log['request_payload'] : [];
    if ($callbackFilters['status'] !== 'all' && (string) ($log['status'] ?? 'pending') !== $callbackFilters['status']) {
        return false;
    }
    if ($callbackFilters['event'] !== 'all' && (string) ($log['event'] ?? '') !== $callbackFilters['event']) {
        return false;
    }
    if ($callbackFilters['order_no'] !== '' && !str_contains((string) ($log['order_no'] ?? ''), $callbackFilters['order_no'])) {
        return false;
    }
    if ($callbackFilters['code'] !== '' && !str_contains((string) ($log['code'] ?? ''), $callbackFilters['code'])) {
        return false;
    }
    $platform = (string) (($payload['platform'] ?? '') ?: ($log['platform'] ?? ''));
    if ($callbackFilters['platform'] !== '' && !str_contains($platform, $callbackFilters['platform'])) {
        return false;
    }
    $appKey = (string) (($log['app_key'] ?? '') ?: ($payload['app_key'] ?? ''));
    if ($callbackFilters['app_key'] !== '' && !str_contains($appKey, $callbackFilters['app_key'])) {
        return false;
    }
    foreach (['ad_id', 'material_id'] as $key) {
        $value = (string) (($log[$key] ?? '') ?: ($payload[$key] ?? ''));
        if ($callbackFilters[$key] !== '' && !str_contains($value, $callbackFilters[$key])) {
            return false;
        }
    }

    return true;
};
$filteredCallbackLogs = array_values(array_filter($callbackLogs, $callbackMatchesFilters));
$callbackStatusCounts = ['pending' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0];
$callbackFailureReasons = [];
foreach ($filteredCallbackLogs as $callbackLog) {
    $status = (string) ($callbackLog['status'] ?? 'pending');
    $callbackStatusCounts[$status] = (int) ($callbackStatusCounts[$status] ?? 0) + 1;
    if ($status === 'failed') {
        $reason = trim((string) ($callbackLog['message'] ?? '未知失败原因')) ?: '未知失败原因';
        $callbackFailureReasons[$reason] = (int) ($callbackFailureReasons[$reason] ?? 0) + 1;
    }
}
arsort($callbackFailureReasons);
$callbackMappingSummary = static function (array $mapping): string {
    $pairs = [];
    foreach ($mapping as $target => $source) {
        $target = trim((string) $target);
        $source = trim((string) $source);
        if ($target === '' || $source === '') {
            continue;
        }
        $pairs[] = $target . '<-' . $source;
        if (count($pairs) >= 8) {
            break;
        }
    }

    return empty($pairs) ? '未配置字段映射' : implode(' · ', $pairs);
};
$rechargeProducts = array_values((array) ($recharge_products ?? []));
$rechargeConfig = (array) ($recharge_config ?? []);
$globalRechargeCodes = array_map('strval', (array) ($rechargeConfig['global_product_codes'] ?? []));
$retentionProductCode = (string) ($rechargeConfig['retention_product_code'] ?? '');
$rechargeTemplates = array_values((array) ($rechargeConfig['app_product_templates'] ?? []));
$rechargeTemplateNameByKey = [];
foreach ($rechargeTemplates as $template) {
    $rechargeTemplateNameByKey[(string) ($template['app_key'] ?? '')] = (string) ($template['name'] ?? '应用商品模板');
}
$rechargeTypeLabels = ['coin' => '云币/K币', 'vip' => '会员', 'full_unlock' => '全集解锁'];
$paymentRoutePolicy = (array) ($payment['route_policy'] ?? []);
$paymentRoutePolicyMode = (string) ($paymentRoutePolicy['mode'] ?? 'default');
$paymentRoutePolicyLabels = ['default' => '默认优先', 'success_rate' => '成功率优先', 'round_robin' => '轮询'];
$paymentRoutePolicyMode = array_key_exists($paymentRoutePolicyMode, $paymentRoutePolicyLabels) ? $paymentRoutePolicyMode : 'default';
$apps = array_values((array) ($apps ?? []));
$appNameByKey = [];
foreach ($apps as $app) {
    $appNameByKey[(string) ($app['app_key'] ?? '')] = (string) ($app['name'] ?? '应用');
}
$appTypeLabels = ['h5' => 'H5', 'wechat_mp' => '微信小程序', 'wechat_official' => '公众号', 'quick_app' => '快应用', 'douyin' => '抖音', 'kuaishou' => '快手', 'native' => 'App'];
$appStatusLabels = ['active' => '启用', 'review' => '审核', 'paused' => '停用'];
$appRecommendSlotLabels = ['home' => '首页推荐', 'comment' => '评论入口', 'favorite' => '收藏入口', 'reading' => '阅读入口', 'category' => '内容分类'];
$appTaskLabels = ['add_desktop' => '加桌任务', 'register' => '注册任务', 'watch' => '观看任务', 'favorite' => '收藏任务', 'share' => '分享任务'];
$appUserTierLabels = ['new' => '新客', 'unpaid' => '未付费', 'paid' => '已付费', 'member' => '会员'];
$clientReviewModeLabels = ['normal' => '正常模式', 'review' => '审核模式', 'safe' => '安全模式'];
$clientThemeLabels = ['default' => '默认主题', 'dark' => '深色', 'light' => '浅色', 'brand' => '品牌色'];
$systemConfigFragments = array_values((array) ($system_config_fragments ?? []));
$configChangeRequests = array_values((array) ($config_change_requests ?? []));
$configChangePending = array_values(array_filter($configChangeRequests, static fn (array $item): bool => (string) ($item['status'] ?? '') === 'pending'));
$configChangeNotificationLogs = array_values((array) ($config_change_notification_logs ?? []));
$configChangeSlaDashboard = (array) ($config_change_sla_dashboard ?? ['summary' => [], 'type_rows' => [], 'target_rows' => [], 'pending_rows' => []]);
$configChangeSlaSummary = (array) ($configChangeSlaDashboard['summary'] ?? []);
$configChangeSlaTypeRows = array_values((array) ($configChangeSlaDashboard['type_rows'] ?? []));
$configChangeSlaTargetRows = array_values((array) ($configChangeSlaDashboard['target_rows'] ?? []));
$configChangeSlaPendingRows = array_values((array) ($configChangeSlaDashboard['pending_rows'] ?? []));
$configChangeSlaAdminRows = array_values((array) ($configChangeSlaDashboard['admin_rows'] ?? []));
$formatMinutes = static function (int $minutes): string {
    $minutes = max(0, $minutes);
    if ($minutes >= 60) {
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;
        return $rest > 0 ? ($hours . '小时' . $rest . '分') : ($hours . '小时');
    }

    return $minutes . '分钟';
};
$appConfigDeliveryLogs = array_values((array) ($app_config_delivery_logs ?? []));
$appConfigDeliverySummary = ['hits' => 0, 'gray_hits' => 0, 'review_hits' => 0, 'safe_hits' => 0];
foreach ($appConfigDeliveryLogs as $deliveryLog) {
    $hitCount = max(1, (int) ($deliveryLog['hit_count'] ?? 1));
    $appConfigDeliverySummary['hits'] += $hitCount;
    if (!empty($deliveryLog['gray_hit'])) {
        $appConfigDeliverySummary['gray_hits'] += $hitCount;
    }
    if ((string) ($deliveryLog['review_mode'] ?? '') === 'review') {
        $appConfigDeliverySummary['review_hits'] += $hitCount;
    }
    if ((string) ($deliveryLog['review_mode'] ?? '') === 'safe') {
        $appConfigDeliverySummary['safe_hits'] += $hitCount;
    }
}
$configFragmentTypeLabels = ['text' => '文本', 'json' => 'JSON', 'url' => '链接', 'secret' => '密钥'];
$configFragmentGroupLabels = ['system' => '系统', 'payment' => '支付', 'callback' => '回传', 'mini_program' => '小程序', 'ad' => '广告', 'custom' => '自定义'];
$maskConfigValue = static function (array $fragment): string {
    $value = (string) ($fragment['value'] ?? '');
    if ($value === '') {
        return '';
    }
    if (empty($fragment['sensitive'])) {
        return function_exists('mb_substr') ? mb_substr($value, 0, 120) : substr($value, 0, 120);
    }
    $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    if ($length <= 8) {
        return str_repeat('*', max(4, $length));
    }
    $prefix = function_exists('mb_substr') ? mb_substr($value, 0, 3) : substr($value, 0, 3);
    $suffix = function_exists('mb_substr') ? mb_substr($value, -3) : substr($value, -3);

    return $prefix . str_repeat('*', 6) . $suffix;
};
$recoveryDashboard = (array) ($recovery_dashboard ?? ['rows' => [], 'summary' => [], 'days' => []]);
$recoveryRows = array_values((array) ($recoveryDashboard['rows'] ?? []));
$recoverySummary = (array) ($recoveryDashboard['summary'] ?? []);
$recoveryDays = array_values((array) ($recoveryDashboard['days'] ?? [0, 1, 3, 7, 15, 30, 60, 90]));
$analyticsFilters = (array) ($analytics_filters ?? []);
$analyticsCompare = (array) ($analytics_compare ?? []);
$analyticsFilterActive = !empty($analyticsFilters['active']);
$analyticsFilterCount = 0;
if ((string) ($analyticsFilters['date_preset'] ?? 'all') !== 'all') {
    $analyticsFilterCount++;
}
foreach ($analyticsFilters as $analyticsFilterKey => $analyticsFilterValue) {
    if (in_array($analyticsFilterKey, ['active', 'date_label', 'date_preset', 'date_start', 'date_end'], true)) {
        continue;
    }
    if ((string) $analyticsFilterValue !== '' && (string) $analyticsFilterValue !== '0') {
        $analyticsFilterCount++;
    }
}
$analyticsFilterLabels = [
    'date_label' => '时间',
    'app_key' => '应用',
    'business_id' => '商务',
    'leader_id' => '组长',
    'agent_id' => '代理',
    'promotion_link_id' => '推广入口',
    'promotion_code' => '推广码',
    'traffic_platform' => '平台',
    'channel_id' => '渠道',
    'ad_id' => '广告',
    'material_id' => '素材',
];
$analyticsFilterSummaryParts = [];
foreach ($analyticsFilterLabels as $analyticsFilterKey => $analyticsFilterLabel) {
    $analyticsFilterValue = (string) ($analyticsFilters[$analyticsFilterKey] ?? '');
    if ($analyticsFilterValue !== '' && $analyticsFilterValue !== '0') {
        if ($analyticsFilterKey === 'date_label' && (string) ($analyticsFilters['date_preset'] ?? 'all') === 'all') {
            continue;
        }
        $analyticsFilterSummaryParts[] = $analyticsFilterLabel . '=' . $analyticsFilterValue;
    }
}
$playStatsDashboard = (array) ($play_stats_dashboard ?? ['summary' => [], 'rows' => [], 'unit_rows' => [], 'daily_rows' => []]);
$playStatsSummary = (array) ($playStatsDashboard['summary'] ?? []);
$playStatsRows = array_values((array) ($playStatsDashboard['rows'] ?? []));
$playStatsUnitRows = array_values((array) ($playStatsDashboard['unit_rows'] ?? []));
$playStatsDailyRows = array_values((array) ($playStatsDashboard['daily_rows'] ?? []));
$userGrowthDashboard = (array) ($user_growth_dashboard ?? ['rows' => [], 'source_rows' => [], 'summary' => []]);
$userGrowthRows = array_values((array) ($userGrowthDashboard['rows'] ?? []));
$userGrowthSourceRows = array_values((array) ($userGrowthDashboard['source_rows'] ?? []));
$userGrowthSummary = (array) ($userGrowthDashboard['summary'] ?? []);
$contentConversionDashboard = (array) ($content_conversion_dashboard ?? ['rows' => [], 'summary' => []]);
$contentConversionRows = array_values((array) ($contentConversionDashboard['rows'] ?? []));
$contentConversionSummary = (array) ($contentConversionDashboard['summary'] ?? []);
$rechargeHourlyDashboard = (array) ($recharge_hourly_dashboard ?? ['rows' => [], 'method_rows' => [], 'summary' => []]);
$rechargeHourlyRows = array_values((array) ($rechargeHourlyDashboard['rows'] ?? []));
$rechargeHourlyMethodRows = array_values((array) ($rechargeHourlyDashboard['method_rows'] ?? []));
$rechargeHourlySummary = (array) ($rechargeHourlyDashboard['summary'] ?? []);
$paymentSuccessDashboard = (array) ($payment_success_dashboard ?? ['summary' => [], 'status_rows' => [], 'route_rows' => [], 'method_rows' => []]);
$paymentSuccessSummary = (array) ($paymentSuccessDashboard['summary'] ?? []);
$paymentSuccessStatusRows = array_values((array) ($paymentSuccessDashboard['status_rows'] ?? []));
$paymentSuccessRouteRows = array_values((array) ($paymentSuccessDashboard['route_rows'] ?? []));
$paymentSuccessMethodRows = array_values((array) ($paymentSuccessDashboard['method_rows'] ?? []));
$analyticsInsightDashboard = (array) ($analytics_insight_dashboard ?? ['summary' => [], 'hour_rows' => [], 'recommendations' => []]);
$analyticsInsightSummary = (array) ($analyticsInsightDashboard['summary'] ?? []);
$analyticsInsightHourRows = array_values((array) ($analyticsInsightDashboard['hour_rows'] ?? []));
$analyticsInsightRecommendations = array_values((array) ($analyticsInsightDashboard['recommendations'] ?? []));
$analyticsReviewTaskDashboard = (array) ($analytics_review_task_dashboard ?? ['summary' => [], 'rows' => []]);
$analyticsReviewTaskSummary = (array) ($analyticsReviewTaskDashboard['summary'] ?? []);
$analyticsReviewTaskRows = array_values((array) ($analyticsReviewTaskDashboard['rows'] ?? []));
$analyticsReviewTaskStatusLabels = ['pending' => '待处理', 'processing' => '处理中', 'done' => '已完成', 'ignored' => '已忽略'];
$analyticsReviewTaskActionLabels = ['observe' => '观察', 'review_content' => '内容复核', 'replace_material' => '替换素材', 'adjust_budget' => '调整预算', 'pause_promotion' => '暂停推广', 'amplify' => '放大投放', 'custom' => '自定义'];
$analyticsReviewMaterialStatusLabels = ['none' => '未提交', 'pending' => '待审批', 'approved' => '已通过', 'rejected' => '已驳回', 'applied' => '已应用'];
$operationAlertDashboard = (array) ($operation_alert_dashboard ?? ['summary' => [], 'callback_alerts' => [], 'low_conversion_alerts' => [], 'high_refund_alerts' => [], 'low_recovery_alerts' => [], 'auto_paused_alerts' => [], 'thresholds' => []]);
$operationAlertSummary = (array) ($operationAlertDashboard['summary'] ?? []);
$callbackAlertRows = array_values((array) ($operationAlertDashboard['callback_alerts'] ?? []));
$callbackGroupAlertRows = array_values((array) ($operationAlertDashboard['callback_group_alerts'] ?? []));
$lowConversionAlertRows = array_values((array) ($operationAlertDashboard['low_conversion_alerts'] ?? []));
$highRefundAlertRows = array_values((array) ($operationAlertDashboard['high_refund_alerts'] ?? []));
$lowRecoveryAlertRows = array_values((array) ($operationAlertDashboard['low_recovery_alerts'] ?? []));
$autoPausedAlertRows = array_values((array) ($operationAlertDashboard['auto_paused_alerts'] ?? []));
$operationAlertThresholds = (array) ($operationAlertDashboard['thresholds'] ?? []);
$contentTags = array_values((array) ($content_tags ?? []));
$contentGroups = array_values((array) ($content_groups ?? []));
$contentImportLogs = array_values((array) ($content_import_logs ?? []));
$contentLibraryRows = array_values((array) ($content_library ?? []));
$contentGroupNameById = [];
foreach ($contentGroups as $contentGroup) {
    $contentGroupNameById[(int) ($contentGroup['id'] ?? 0)] = (string) ($contentGroup['name'] ?? '内容分组');
}
$contentAuditLabels = ['draft' => '草稿', 'pending' => '待审核', 'approved' => '已通过', 'rejected' => '已驳回', 'online' => '已上架', 'offline' => '已下架'];
$worksFilters = [
    'type' => (string) ($_GET['works_type'] ?? 'all'),
    'category' => trim((string) ($_GET['works_category'] ?? '')),
    'status' => (string) ($_GET['works_status'] ?? 'all'),
    'keyword' => trim((string) ($_GET['works_keyword'] ?? '')),
];
if (!isset($workTypeLabels[$worksFilters['type']]) && $worksFilters['type'] !== 'all') {
    $worksFilters['type'] = 'all';
}
if (!isset($workStatusLabels[$worksFilters['status']]) && $worksFilters['status'] !== 'all') {
    $worksFilters['status'] = 'all';
}
$worksRows = [];
foreach ((array) ($dramas ?? []) as $drama) {
    $worksRows[] = [
        'key' => 'drama:' . (int) ($drama['id'] ?? 0),
        'type' => 'drama',
        'id' => (int) ($drama['id'] ?? 0),
        'title' => (string) ($drama['title'] ?? '未命名短剧'),
        'author' => (string) ($drama['author'] ?? ''),
        'category' => (string) ($drama['category'] ?? '都市'),
        'tags' => array_values((array) ($drama['tags'] ?? [])),
        'cover' => (string) ($drama['cover'] ?? ''),
        'status' => (string) ($drama['status'] ?? 'draft'),
        'audit_status' => (string) ($drama['audit_status'] ?? $drama['status'] ?? 'draft'),
        'quality' => (string) ($drama['quality'] ?? 'normal'),
        'is_finished' => !empty($drama['is_finished']),
        'is_vip' => !empty($drama['is_vip']),
        'unit_count' => count((array) ($drama['episodes'] ?? [])),
        'unit_label' => '集',
        'price_label' => number_format((int) ($drama['episode_coin_price'] ?? 199)) . ' K币/集',
        'buy_start' => max(0, (int) ($drama['buy_start'] ?? $drama['free_episode_count'] ?? 1)),
        'read_count' => max(0, (int) ($drama['views'] ?? 0)),
        'updated_at' => (string) (($drama['updated_at'] ?? '') ?: ($drama['created_at'] ?? '')),
        'edit_section' => 'dramas',
        'unit_section' => 'episodes',
    ];
}
foreach ($novels as $novel) {
    $worksRows[] = [
        'key' => 'novel:' . (int) ($novel['id'] ?? 0),
        'type' => 'novel',
        'id' => (int) ($novel['id'] ?? 0),
        'title' => (string) ($novel['title'] ?? '未命名小说'),
        'author' => (string) ($novel['author'] ?? ''),
        'category' => (string) ($novel['category'] ?? '都市'),
        'tags' => array_values((array) ($novel['tags'] ?? [])),
        'cover' => (string) ($novel['cover'] ?? ''),
        'status' => (string) ($novel['status'] ?? 'draft'),
        'audit_status' => (string) ($novel['audit_status'] ?? $novel['status'] ?? 'draft'),
        'quality' => (string) ($novel['quality'] ?? 'normal'),
        'is_finished' => !empty($novel['is_finished']),
        'is_vip' => !empty($novel['is_vip']),
        'unit_count' => count((array) ($novel['chapters'] ?? [])),
        'unit_label' => '章',
        'price_label' => number_format((int) ($novel['chapter_coin_price'] ?? 99)) . ' K币/章',
        'buy_start' => max(0, (int) ($novel['buy_start'] ?? $novel['free_chapter_count'] ?? 3)),
        'read_count' => max(0, (int) ($novel['read_count'] ?? $novel['views'] ?? 0)),
        'updated_at' => (string) (($novel['updated_at'] ?? '') ?: ($novel['created_at'] ?? '')),
        'edit_section' => 'novels',
        'unit_section' => 'episodes',
    ];
}
foreach ($mediaContents as $media) {
    $type = (string) ($media['type'] ?? 'image') === 'h5' ? 'h5' : 'image';
    $worksRows[] = [
        'key' => $type . ':' . (int) ($media['id'] ?? 0),
        'type' => $type,
        'id' => (int) ($media['id'] ?? 0),
        'title' => (string) ($media['title'] ?? ($type === 'h5' ? '未命名H5' : '未命名壁纸')),
        'author' => (string) ($media['author'] ?? ''),
        'category' => (string) ($media['category'] ?? ''),
        'tags' => array_values((array) ($media['tags'] ?? [])),
        'cover' => (string) ($media['cover'] ?? ''),
        'status' => (string) ($media['status'] ?? 'draft'),
        'audit_status' => (string) ($media['status'] ?? 'draft'),
        'quality' => (string) ($media['quality'] ?? 'normal'),
        'is_finished' => !empty($media['is_finished']),
        'is_vip' => !empty($media['is_vip']),
        'unit_count' => 1,
        'unit_label' => $type === 'h5' ? '页' : '张',
        'price_label' => number_format((int) ($media['price_coins'] ?? 0)) . ' K币/' . ($type === 'h5' ? '页' : '张'),
        'buy_start' => max(0, (int) ($media['buy_start'] ?? 1)),
        'read_count' => max(0, (int) ($media['read_count'] ?? 0)),
        'updated_at' => (string) (($media['updated_at'] ?? '') ?: ($media['created_at'] ?? '')),
        'edit_section' => $type === 'h5' ? 'media-h5' : 'media-wallpapers',
        'unit_section' => '',
    ];
}
$filteredWorksRows = array_values(array_filter($worksRows, static function (array $row) use ($worksFilters): bool {
    if ($worksFilters['type'] !== 'all' && (string) ($row['type'] ?? '') !== $worksFilters['type']) {
        return false;
    }
    if ($worksFilters['category'] !== '' && (string) ($row['category'] ?? '') !== $worksFilters['category']) {
        return false;
    }
    if ($worksFilters['status'] !== 'all' && (string) ($row['status'] ?? '') !== $worksFilters['status']) {
        return false;
    }
    if ($worksFilters['keyword'] !== '') {
        $haystack = (string) ($row['title'] ?? '') . ' ' . (string) ($row['author'] ?? '') . ' ' . (string) ($row['id'] ?? '');
        if (!str_contains($haystack, $worksFilters['keyword'])) {
            return false;
        }
    }

    return true;
}));
$worksCategoryOptions = array_values(array_unique(array_filter(array_merge($dramaCategoryOptions, $novelCategoryOptions, $mediaCategoryOptions))));
$unitFilters = [
    'type' => (string) ($_GET['unit_type'] ?? 'all'),
    'parent_id' => max(0, (int) ($_GET['unit_parent_id'] ?? 0)),
    'status' => (string) ($_GET['unit_status'] ?? 'all'),
    'keyword' => trim((string) ($_GET['unit_keyword'] ?? '')),
];
if (!in_array($unitFilters['type'], ['all', 'drama', 'novel'], true)) {
    $unitFilters['type'] = 'all';
}
if (!isset($workStatusLabels[$unitFilters['status']]) && $unitFilters['status'] !== 'all') {
    $unitFilters['status'] = 'all';
}
$contentUnitRows = [];
foreach ((array) ($dramas ?? []) as $drama) {
    foreach (array_values((array) ($drama['episodes'] ?? [])) as $episodeIndex => $episode) {
        $contentUnitRows[] = [
            'key' => 'drama:' . (int) ($drama['id'] ?? 0) . ':' . (int) ($episode['id'] ?? 0),
            'type' => 'drama',
            'type_label' => '短剧分集',
            'content_id' => (int) ($drama['id'] ?? 0),
            'content_title' => (string) ($drama['title'] ?? '短剧'),
            'unit_id' => (int) ($episode['id'] ?? 0),
            'title' => (string) ($episode['title'] ?? ('第' . ($episodeIndex + 1) . '集')),
            'sort' => (int) ($episode['sort'] ?? ($episodeIndex + 1)),
            'status' => (string) ($episode['status'] ?? $drama['status'] ?? 'draft'),
            'is_free' => !empty($episode['is_free']),
            'coin_price' => max(0, (int) ($episode['coin_price'] ?? $drama['episode_coin_price'] ?? 199)),
            'duration' => (string) ($episode['duration'] ?? ''),
            'url' => (string) ($episode['video_url'] ?? ''),
            'content' => '',
            'word_count' => 0,
        ];
    }
}
foreach ($novels as $novel) {
    foreach (array_values((array) ($novel['chapters'] ?? [])) as $chapterIndex => $chapter) {
        $contentUnitRows[] = [
            'key' => 'novel:' . (int) ($novel['id'] ?? 0) . ':' . (int) ($chapter['id'] ?? 0),
            'type' => 'novel',
            'type_label' => '小说章节',
            'content_id' => (int) ($novel['id'] ?? 0),
            'content_title' => (string) ($novel['title'] ?? '小说'),
            'unit_id' => (int) ($chapter['id'] ?? 0),
            'title' => (string) ($chapter['title'] ?? ('第' . ($chapterIndex + 1) . '章')),
            'sort' => (int) ($chapter['sort'] ?? ($chapterIndex + 1)),
            'status' => (string) ($chapter['status'] ?? 'draft'),
            'is_free' => !empty($chapter['is_free']),
            'coin_price' => max(0, (int) ($chapter['coin_price'] ?? $novel['chapter_coin_price'] ?? 99)),
            'duration' => '',
            'url' => '',
            'content' => (string) ($chapter['content'] ?? ''),
            'word_count' => max(0, (int) ($chapter['word_count'] ?? 0)),
        ];
    }
}
$filteredContentUnitRows = array_values(array_filter($contentUnitRows, static function (array $row) use ($unitFilters): bool {
    if ($unitFilters['type'] !== 'all' && (string) ($row['type'] ?? '') !== $unitFilters['type']) {
        return false;
    }
    if ($unitFilters['parent_id'] > 0 && (int) ($row['content_id'] ?? 0) !== $unitFilters['parent_id']) {
        return false;
    }
    if ($unitFilters['status'] !== 'all' && (string) ($row['status'] ?? '') !== $unitFilters['status']) {
        return false;
    }
    if ($unitFilters['keyword'] !== '') {
        $haystack = (string) ($row['content_title'] ?? '') . ' ' . (string) ($row['title'] ?? '') . ' ' . (string) ($row['unit_id'] ?? '');
        if (!str_contains($haystack, $unitFilters['keyword'])) {
            return false;
        }
    }

    return true;
}));
$workEditorType = (string) ($_GET['work_type'] ?? $_POST['work_type'] ?? 'drama');
if (!in_array($workEditorType, ['drama', 'novel'], true)) {
    $workEditorType = 'drama';
}
$workEditorId = max(0, (int) ($_GET['work_id'] ?? $_POST['work_id'] ?? 0));
$workEditorRangeStart = max(1, (int) ($_GET['unit_start'] ?? $_POST['unit_start'] ?? 1));
$workEditorRangeSize = 30;
$workEditorContent = null;
foreach ($workEditorType === 'novel' ? $novels : (array) ($dramas ?? []) as $candidateWork) {
    if ((int) ($candidateWork['id'] ?? 0) === $workEditorId) {
        $workEditorContent = $candidateWork;
        break;
    }
}
$workEditorUnits = $workEditorContent === null
    ? []
    : array_values((array) ($workEditorType === 'novel' ? ($workEditorContent['chapters'] ?? []) : ($workEditorContent['episodes'] ?? [])));
usort($workEditorUnits, static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (int) ($a['id'] ?? 0)] <=> [(int) ($b['sort'] ?? 0), (int) ($b['id'] ?? 0)]);
$workEditorUnitCount = count($workEditorUnits);
$workEditorMaxRangeStart = max(1, (int) (floor(max(0, $workEditorUnitCount - 1) / $workEditorRangeSize) * $workEditorRangeSize + 1));
if ($workEditorRangeStart > $workEditorMaxRangeStart) {
    $workEditorRangeStart = $workEditorMaxRangeStart;
}
$workEditorRangeEnd = min($workEditorUnitCount, $workEditorRangeStart + $workEditorRangeSize - 1);
$workEditorVisibleUnits = $workEditorUnitCount > 0 ? array_slice($workEditorUnits, $workEditorRangeStart - 1, $workEditorRangeSize) : [];
$workEditorUnitLabel = $workEditorType === 'novel' ? '章' : '集';
$workEditorTitle = (string) ($workEditorContent['title'] ?? ($workEditorType === 'novel' ? '未选择小说' : '未选择短剧'));
$workEditorCover = trim((string) ($workEditorContent['cover'] ?? ''));
$workEditorAction = $workEditorType === 'novel' ? 'update_novel' : 'update_drama';
$workEditorCreateUnitAction = $workEditorType === 'novel' ? 'create_novel_chapter' : 'create_episode';
$workEditorUpdateUnitAction = $workEditorType === 'novel' ? 'update_novel_chapter' : 'update_episode';
$workEditorUrl = static function (int $start = 1) use ($workEditorType, $workEditorId): string {
    return '/jxdjadmin?admin_section=work-editor&work_type=' . rawurlencode($workEditorType) . '&work_id=' . $workEditorId . '&unit_start=' . max(1, $start) . '#work-editor';
};
$workEditorFormAction = $workEditorUrl($workEditorRangeStart);
$workEditorUnitCover = static function (array $unit, string $fallback): string {
    $meta = (array) ($unit['source_video_meta'] ?? []);
    $cover = trim((string) ($meta['cover_url'] ?? ''));
    return $cover !== '' ? $cover : $fallback;
};
$landingStatusLabels = ['active' => '启用', 'review' => '审核', 'paused' => '暂停'];
$landingTemplateLabels = ['drama' => '短剧落地页', 'novel' => '小说落地页', 'mixed' => '混合落地页'];
$adTypeLabels = ['reward_video' => '激励视频', 'interstitial' => '插屏广告', 'banner' => '横幅广告', 'floating' => '悬浮广告', 'native' => '信息流广告'];
$adPositionLabels = ['home_banner' => '首页横幅', 'player_pause' => '播放暂停', 'player_pre_unlock' => '解锁前', 'reader_bottom' => '阅读页底部', 'center_top' => '中心页顶部', 'landing_page' => '落地页'];
$adProviderLabels = ['csj' => '穿山甲', 'ylh' => '优量汇', 'kuaishou' => '快手联盟', 'baidu' => '百度联盟', 'custom' => '自定义'];
$adStatusLabels = ['active' => '启用', 'review' => '审核', 'paused' => '停用'];
$adRuleStatusLabels = ['active' => '启用', 'paused' => '停用'];
$adMembershipLabels = ['all' => '全部用户', 'guest' => '非会员', 'member' => '会员'];
$adPayStageLabels = ['all' => '全部阶段', 'new' => '新访客', 'unpaid' => '未付费', 'paid' => '已付费'];
$adEventLabels = ['impression' => '曝光', 'click' => '点击', 'reward' => '激励'];
$miniProgramConfigs = array_values((array) ($mini_program_configs ?? []));
$miniProgramSyncTasks = array_values((array) ($mini_program_sync_tasks ?? []));
$miniProgramStatusLabels = ['draft' => '草稿', 'active' => '启用', 'paused' => '停用'];
$miniProgramUploadModeLabels = ['manual' => '手动上传', 'api' => '接口上传', 'ci' => 'CI 上传'];
$miniProgramScopeLabels = ['all' => '短剧+小说', 'drama' => '仅短剧', 'novel' => '仅小说'];
$miniProgramTokenStatusLabels = ['success' => '已刷新', 'failed' => '刷新失败', 'skipped' => '已跳过', '' => '未刷新'];
$miniProgramTaskScopeLabels = ['mixed' => '短剧+小说', 'all' => '短剧+小说', 'drama' => '仅短剧', 'novel' => '仅小说'];
$miniProgramTaskStatusLabels = ['pending' => '待处理', 'generated' => '已生成', 'uploaded' => '已上传', 'review_submitted' => '审核中', 'review_passed' => '审核通过', 'review_rejected' => '审核驳回', 'released' => '已发布', 'failed' => '失败'];
$miniProgramTaskActionLabels = ['generate' => '生成清单', 'upload' => '上传代码', 'submit_review' => '提交审核', 'query_review' => '查询审核', 'release' => '发布上线', 'retry' => '失败重试'];
$miniProgramTaskErrorLabels = ['auth_failed' => '授权失败', 'rate_limited' => '微信频控', 'platform_failed' => '平台失败', 'retryable_failed' => '可重试失败', 'config_missing' => '配置缺失', 'invalid_status' => '状态不符', 'review_rejected' => '审核驳回', 'unknown' => '未知失败'];
$miniProgramTaskStatusCounts = array_fill_keys(array_keys($miniProgramTaskStatusLabels), 0);
foreach ($miniProgramSyncTasks as $miniProgramTaskForCount) {
    $miniProgramTaskStatus = (string) ($miniProgramTaskForCount['status'] ?? 'generated');
    $miniProgramTaskStatusCounts[$miniProgramTaskStatus] = (int) ($miniProgramTaskStatusCounts[$miniProgramTaskStatus] ?? 0) + 1;
}
$adEventCounts = [];
foreach ($adEvents as $event) {
    $slotId = (int) ($event['ad_slot_id'] ?? 0);
    $eventType = (string) ($event['event'] ?? 'impression');
    $adEventCounts[$slotId] ??= ['impression' => 0, 'click' => 0, 'reward' => 0, 'reward_coins' => 0];
    if (isset($adEventCounts[$slotId][$eventType])) {
        $adEventCounts[$slotId][$eventType]++;
    }
    if ($eventType === 'reward') {
        $adEventCounts[$slotId]['reward_coins'] += max(0, (int) ($event['reward_coins'] ?? 0));
    }
}
$landingEventCounts = [];
foreach ($landingPageEvents as $event) {
    $landingId = (int) ($event['landing_page_id'] ?? 0);
    $eventType = (string) ($event['event'] ?? 'view');
    $landingEventCounts[$landingId] ??= ['view' => 0, 'click' => 0];
    if (isset($landingEventCounts[$landingId][$eventType])) {
        $landingEventCounts[$landingId][$eventType]++;
    }
}
$userProfile = is_array($user_profile ?? null) ? (array) $user_profile : null;
$profileUser = (array) ($userProfile['user'] ?? []);
$profileSummary = (array) ($userProfile['summary'] ?? []);
$profileAttribution = (array) ($userProfile['attribution'] ?? []);
$userNameById = [];
foreach ((array) ($users ?? []) as $userForName) {
    $uidForName = (int) ($userForName['id'] ?? 0);
    if ($uidForName > 0) {
        $userNameById[$uidForName] = (string) (($userForName['nickname'] ?? '') ?: (($userForName['phone'] ?? '') ?: ('用户 ' . $uidForName)));
    }
}
$episodeTitleByKey = [];
$episodeRows = [];
foreach ($dramas as $dramaForEpisodeRows) {
    $dramaIdForEpisodeRows = (int) ($dramaForEpisodeRows['id'] ?? 0);
    foreach (array_values((array) ($dramaForEpisodeRows['episodes'] ?? [])) as $episodeIndex => $episodeForRows) {
        $episodeIdForRows = (int) ($episodeForRows['id'] ?? 0);
        if ($dramaIdForEpisodeRows <= 0 || $episodeIdForRows <= 0) {
            continue;
        }
        $episodeTitleByKey[$dramaIdForEpisodeRows . ':' . $episodeIdForRows] = (string) (($episodeForRows['title'] ?? '') ?: ('第' . ($episodeIndex + 1) . '集'));
        $episodeRows[] = [
            'drama' => $dramaForEpisodeRows,
            'episode' => $episodeForRows,
            'index' => $episodeIndex + 1,
        ];
    }
}
$selectedEpisodeDramaId = max(0, (int) ($_GET['episode_drama_id'] ?? 0));
$visibleEpisodeRows = $selectedEpisodeDramaId > 0
    ? array_values(array_filter($episodeRows, static fn (array $row): bool => (int) ($row['drama']['id'] ?? 0) === $selectedEpisodeDramaId))
    : $episodeRows;
$userTypeFilter = (string) ($_GET['user_type'] ?? 'all');
if (!in_array($userTypeFilter, ['all', 'guest', 'member'], true)) {
    $userTypeFilter = 'all';
}
$visibleUsers = array_values(array_filter($users, static function (array $user) use ($userTypeFilter): bool {
    if ($userTypeFilter === 'member') {
        return !empty($user['membership']);
    }
    if ($userTypeFilter === 'guest') {
        return empty($user['membership']);
    }

    return true;
}));
$episodeEntitlementRows = array_values(array_filter($entitlements, static fn (array $item): bool => (string) ($item['content_type'] ?? 'drama') === 'drama' && (string) ($item['type'] ?? '') === 'episode_unlock' && (int) ($item['episode_id'] ?? 0) > 0));
usort($episodeEntitlementRows, static fn (array $a, array $b): int => strcmp((string) (($b['granted_at'] ?? '') ?: ($b['created_at'] ?? '')), (string) (($a['granted_at'] ?? '') ?: ($a['created_at'] ?? ''))));
$quickEntryRows = [
    ['label' => '新增短剧', 'hint' => '内容管理', 'href' => '/jxdjadmin?admin_section=dramas#dramas', 'icon' => 'drama'],
    ['label' => '订单查询', 'hint' => '按订单号或用户查找', 'href' => '/jxdjadmin?admin_section=orders#orders', 'icon' => 'order'],
    ['label' => '权益补发', 'hint' => '客服处理入口', 'href' => '/jxdjadmin?admin_section=rights-repair#rights-repair', 'icon' => 'setting'],
    ['label' => '支付通道', 'hint' => '通道与密钥配置', 'href' => '/jxdjadmin?admin_section=payment-channel#payment-channel', 'icon' => 'payment'],
    ['label' => '投放链接', 'hint' => '生成承接入口', 'href' => '/jxdjadmin?admin_section=promotion-links#promotion-links', 'icon' => 'profit'],
    ['label' => '首页模版', 'hint' => '切换前台展示', 'href' => '/jxdjadmin?admin_section=homepage-template#homepage-template', 'icon' => 'home'],
    ['label' => '操作日志', 'hint' => '后台动作审计', 'href' => '/jxdjadmin?admin_section=operation-log#operation-log', 'icon' => 'order'],
];
$orderStatusForView = static function (array $order): string {
    $status = (string) ($order['status'] ?? 'pending');
    $amount = (float) ($order['amount'] ?? 0);
    $refunded = (float) ($order['refund_total'] ?? $order['refund_amount'] ?? 0);
    if ($status === 'refund_pending' || !empty($order['refund_pending'])) {
        return 'refund_pending';
    }
    if ($status === 'refunded' && max(0, $amount - $refunded) > 0.004) {
        return 'partial_refunded';
    }

    return in_array($status, ['paid', 'partial_refunded', 'refunded', 'failed', 'closed', 'expired'], true) ? $status : 'pending';
};
$isIntegratedPaymentOrderView = static function (array $order): bool {
    $provider = strtolower((string) ($order['payment_provider'] ?? ''));
    if ($provider === 'payjf') {
        $provider = 'superpay';
    }
    if ($provider !== '') {
        return in_array($provider, ['jingxiu', 'superpay'], true);
    }

    return in_array((string) ($order['payment_method'] ?? 'jingxiu'), ['jingxiu', 'alipay', 'wechat', 'wxpay'], true);
};
$businessOrders = array_values(array_filter($orders, static fn (array $order): bool => empty($order['is_test'])));
$settledOrderStatuses = ['paid', 'partial_refunded', 'refunded'];
$pendingOrders = count(array_filter($businessOrders, static fn (array $order): bool => $orderStatusForView($order) === 'pending'));
$paidOrders = count(array_filter($businessOrders, static fn (array $order): bool => in_array($orderStatusForView($order), ['paid', 'partial_refunded'], true)));
$refundedOrders = count(array_filter($businessOrders, static fn (array $order): bool => in_array($orderStatusForView($order), ['refunded', 'partial_refunded'], true)));
$pendingRefundCount = count(array_filter($businessOrders, static fn (array $order): bool => !empty($order['refund_pending']) || (string) ($order['status'] ?? '') === 'refund_pending'));
$pendingCommentCount = count(array_filter($contentComments, static fn (array $comment): bool => (string) ($comment['status'] ?? 'pending') === 'pending'));
$pendingFeedbackCount = count(array_filter($feedbackItems, static fn (array $feedback): bool => in_array((string) ($feedback['status'] ?? 'open'), ['open', 'pending', 'processing'], true)));
$pendingApprovalCount = count(array_filter($configChangeRequests, static fn (array $item): bool => in_array((string) ($item['status'] ?? 'pending'), ['pending', 'reviewing'], true)));
$highAlertCount = count(array_filter($operationAlertNotifications, static fn (array $item): bool => in_array((string) ($item['status'] ?? 'pending'), ['pending', 'open'], true)));
$miniProgramTodoCount = count(array_filter($miniProgramSyncTasks, static fn (array $task): bool => in_array((string) ($task['status'] ?? 'generated'), ['generated', 'uploaded', 'review_submitted', 'review_rejected', 'failed'], true)));
$todoRows = [
    ['label' => '待支付订单', 'count' => $pendingOrders, 'hint' => '需要主动查询支付状态', 'href' => '/jxdjadmin?admin_section=pending-orders#pending-orders', 'tone' => 'blue'],
    ['label' => '退款处理中', 'count' => $pendingRefundCount, 'hint' => '需要查询退款结果', 'href' => '/jxdjadmin?admin_section=refund-orders#refund-orders', 'tone' => 'orange'],
    ['label' => '待审核评论', 'count' => $pendingCommentCount, 'hint' => '评论通过后才会展示', 'href' => '/jxdjadmin?admin_section=content-comments#content-comments', 'tone' => 'green'],
    ['label' => '投诉反馈', 'count' => $pendingFeedbackCount, 'hint' => '客服需跟进处理', 'href' => '/jxdjadmin?admin_section=feedback#feedback', 'tone' => 'ember'],
    ['label' => '配置审批', 'count' => $pendingApprovalCount, 'hint' => '涉及系统配置变更', 'href' => '/jxdjadmin?admin_section=config-approval#config-approval', 'tone' => 'blue'],
    ['label' => '投放异常', 'count' => $highAlertCount, 'hint' => '关注停投与外发通知', 'href' => '/jxdjadmin?admin_section=operation-alerts#operation-alerts', 'tone' => 'orange'],
    ['label' => '小程序任务', 'count' => $miniProgramTodoCount, 'hint' => '上传、审核、发布流程', 'href' => '/jxdjadmin?admin_section=mini-program#mini-program', 'tone' => 'green'],
];
$activeAdminSection = (string) ($active_admin_section ?? 'overview');
$legacyAdminSectionMap = [
    'today-trade' => 'overview',
    'system-notice' => 'popup-notice',
    'preview-config' => 'dramas',
    'price-config' => 'dramas',
    'guest-users' => 'users',
    'member-users' => 'users',
    'bind-records' => 'users',
    'security-config' => 'settings',
    'page-decoration' => 'homepage-template',
];
if (isset($legacyAdminSectionMap[$activeAdminSection])) {
    $activeAdminSection = $legacyAdminSectionMap[$activeAdminSection];
}
$adminPrimaryMenus = [
    'dashboard' => [
        'label' => '首页',
        'icon' => 'dashboard',
        'default' => 'overview',
        'children' => [
            ['id' => 'overview', 'label' => '仪表盘', 'icon' => 'dashboard', 'ready' => true],
            ['id' => 'data-screen', 'label' => '数据大屏', 'icon' => 'stats', 'ready' => true],
            ['id' => 'todo', 'label' => '待办事项', 'icon' => 'order', 'ready' => true],
            ['id' => 'quick-entry', 'label' => '快捷入口', 'icon' => 'setting', 'ready' => true],
        ],
    ],
    'works' => [
        'label' => '作品管理',
        'icon' => 'drama',
        'default' => 'works-list',
        'children' => [
            ['id' => 'works-list', 'label' => '作品列表', 'icon' => 'stats', 'ready' => true],
            ['id' => 'novels', 'label' => '编辑书籍', 'icon' => 'account', 'ready' => true],
            ['id' => 'dramas', 'label' => '编辑剧集', 'icon' => 'drama', 'ready' => true],
            ['id' => 'media-wallpapers', 'label' => '编辑壁纸', 'icon' => 'banner', 'ready' => true],
            ['id' => 'media-h5', 'label' => '编辑H5', 'icon' => 'home', 'ready' => true],
            ['id' => 'content-tags', 'label' => '分类管理', 'icon' => 'banner', 'ready' => true],
        ],
    ],
    'content' => [
        'label' => '内容管理',
        'icon' => 'drama',
        'default' => 'shelf-review',
        'children' => [
            ['id' => 'shelf-review', 'label' => '内容库', 'icon' => 'setting', 'ready' => true],
            ['id' => 'content-comments', 'label' => '评论审核', 'icon' => 'message', 'ready' => true],
        ],
    ],
    'orders' => [
        'label' => '订单中心',
        'icon' => 'order',
        'default' => 'orders',
        'children' => [
            ['id' => 'orders', 'label' => '全部订单', 'icon' => 'order', 'ready' => true],
            ['id' => 'pending-orders', 'label' => '待支付订单', 'icon' => 'payment', 'ready' => true],
            ['id' => 'paid-orders', 'label' => '已支付订单', 'icon' => 'orders', 'ready' => true],
            ['id' => 'refund-orders', 'label' => '退款订单', 'icon' => 'withdraw', 'ready' => true],
            ['id' => 'repair-orders', 'label' => '补单记录', 'icon' => 'setting', 'ready' => true],
            ['id' => 'payment-query', 'label' => '支付状态查询', 'icon' => 'stats', 'ready' => true],
        ],
    ],
    'finance' => [
        'label' => '支付财务',
        'icon' => 'payment',
        'default' => 'payment',
        'children' => [
            ['id' => 'payment', 'label' => '支付配置', 'icon' => 'payment', 'ready' => true],
            ['id' => 'payment-channel', 'label' => '支付通道', 'icon' => 'setting', 'ready' => true],
            ['id' => 'payment-method', 'label' => '支付方式', 'icon' => 'payment', 'ready' => true],
            ['id' => 'recharge-products', 'label' => '充值商品', 'icon' => 'revenue', 'ready' => true],
            ['id' => 'agent-settlement', 'label' => '代理结算', 'icon' => 'withdraw', 'ready' => true],
            ['id' => 'apps', 'label' => '应用管理', 'icon' => 'home', 'ready' => true],
            ['id' => 'channel-polling', 'label' => '通道轮询', 'icon' => 'stats', 'ready' => true],
            ['id' => 'mini-program', 'label' => '公众号小程序', 'icon' => 'home', 'ready' => true],
        ],
    ],
    'users' => [
        'label' => '用户权益',
        'icon' => 'user',
        'default' => 'users',
        'children' => [
            ['id' => 'users', 'label' => '用户列表', 'icon' => 'user', 'ready' => true],
            ['id' => 'episode-rights', 'label' => '单集权益', 'icon' => 'drama', 'ready' => true],
            ['id' => 'rights-repair', 'label' => '权益补发/撤销', 'icon' => 'setting', 'ready' => true],
        ],
    ],
    'operation' => [
        'label' => '运营推荐',
        'icon' => 'banner',
        'default' => 'banner',
        'children' => [
            ['id' => 'banner', 'label' => 'Banner 管理', 'icon' => 'banner', 'ready' => true],
            ['id' => 'promotion-links', 'label' => '投放链接', 'icon' => 'profit', 'ready' => true],
            ['id' => 'landing-pages', 'label' => '推广落地页', 'icon' => 'home', 'ready' => true],
            ['id' => 'ad-slots', 'label' => '广告位设置', 'icon' => 'banner', 'ready' => true],
            ['id' => 'agent-accounts', 'label' => '投放账号', 'icon' => 'account', 'ready' => true],
            ['id' => 'callback-config', 'label' => '回传配置', 'icon' => 'setting', 'ready' => true],
            ['id' => 'home-recommend', 'label' => '首页推荐', 'icon' => 'drama', 'ready' => true],
            ['id' => 'hot-rank', 'label' => '热播榜单', 'icon' => 'stats', 'ready' => true],
            ['id' => 'popup-notice', 'label' => '弹窗公告', 'icon' => 'banner', 'ready' => true],
            ['id' => 'activity-config', 'label' => '活动配置', 'icon' => 'setting', 'ready' => true],
            ['id' => 'coupon-code', 'label' => '优惠券/兑换码', 'icon' => 'revenue', 'ready' => true],
        ],
    ],
    'messages' => [
        'label' => '消息管理',
        'icon' => 'order',
        'default' => 'feedback',
        'children' => [
            ['id' => 'feedback', 'label' => '投诉反馈', 'icon' => 'order', 'ready' => true],
            ['id' => 'message-template', 'label' => '消息模板', 'icon' => 'banner', 'ready' => true],
            ['id' => 'notice-records', 'label' => '通知记录', 'icon' => 'stats', 'ready' => true],
        ],
    ],
    'design' => [
        'label' => '设计',
        'icon' => 'design',
        'default' => 'homepage-template',
        'children' => [
            ['id' => 'homepage-template', 'label' => '首页模版', 'icon' => 'home', 'ready' => true],
        ],
    ],
    'stats' => [
        'label' => '数据统计',
        'icon' => 'stats',
        'default' => 'play-stats',
        'children' => [
            ['id' => 'play-stats', 'label' => '播放统计', 'icon' => 'stats', 'ready' => true],
            ['id' => 'recharge-hourly', 'label' => '充值时段', 'icon' => 'order', 'ready' => true],
            ['id' => 'revenue-trend', 'label' => '回收趋势', 'icon' => 'profit', 'ready' => true],
            ['id' => 'content-conversion', 'label' => '内容转化', 'icon' => 'drama', 'ready' => true],
            ['id' => 'operation-alerts', 'label' => '投放异常', 'icon' => 'withdraw', 'ready' => true],
            ['id' => 'user-growth', 'label' => '用户增长', 'icon' => 'user', 'ready' => true],
            ['id' => 'payment-success', 'label' => '支付成功率', 'icon' => 'payment', 'ready' => true],
        ],
    ],
    'settings' => [
        'label' => '系统设置',
        'icon' => 'setting',
        'default' => 'settings',
        'children' => [
            ['id' => 'settings', 'label' => '后台账号', 'icon' => 'account', 'ready' => true],
            ['id' => 'base-config', 'label' => '基础配置', 'icon' => 'setting', 'ready' => true],
            ['id' => 'notification-config', 'label' => '短信邮件', 'icon' => 'message', 'ready' => true],
            ['id' => 'config-approval', 'label' => '配置审批', 'icon' => 'order', 'ready' => true],
            ['id' => 'config-fragments', 'label' => '配置片段', 'icon' => 'setting', 'ready' => true],
            ['id' => 'operation-log', 'label' => '操作日志', 'icon' => 'order', 'ready' => true],
            ['id' => 'api-log', 'label' => '接口日志', 'icon' => 'stats', 'ready' => true],
            ['id' => 'maintenance', 'label' => '缓存/维护', 'icon' => 'withdraw', 'ready' => true],
        ],
    ],
];
$implementedAdminSections = ['overview', 'data-screen', 'todo', 'quick-entry', 'works-list', 'work-editor', 'dramas', 'episodes', 'novels', 'media-wallpapers', 'media-h5', 'content-tags', 'shelf-review', 'payment', 'payment-channel', 'payment-method', 'recharge-products', 'agent-settlement', 'apps', 'channel-polling', 'mini-program', 'orders', 'pending-orders', 'paid-orders', 'refund-orders', 'repair-orders', 'payment-query', 'users', 'episode-rights', 'rights-repair', 'banner', 'promotion-links', 'landing-pages', 'ad-slots', 'home-recommend', 'hot-rank', 'popup-notice', 'activity-config', 'coupon-code', 'agent-accounts', 'callback-config', 'feedback', 'message-template', 'notice-records', 'play-stats', 'recharge-hourly', 'payment-success', 'revenue-trend', 'content-conversion', 'operation-alerts', 'user-growth', 'settings', 'base-config', 'notification-config', 'config-approval', 'config-fragments', 'operation-log', 'api-log', 'homepage-template', 'maintenance', 'content-comments'];
$adminSectionMeta = [];
$activeAdminPrimary = 'dashboard';
foreach ($adminPrimaryMenus as $primaryId => $primary) {
    foreach ($primary['children'] as $child) {
        $adminSectionMeta[$child['id']] = [
            'primary' => $primaryId,
            'primary_label' => $primary['label'],
            'label' => $child['label'],
            'icon' => $child['icon'],
            'ready' => !empty($child['ready']),
        ];
        if ($child['id'] === $activeAdminSection) {
            $activeAdminPrimary = $primaryId;
        }
    }
}
if (!isset($adminSectionMeta['episodes'])) {
    $adminSectionMeta['episodes'] = [
        'primary' => 'works',
        'primary_label' => '作品管理',
        'label' => '分集/章节维护',
        'icon' => 'order',
        'ready' => true,
    ];
    if ($activeAdminSection === 'episodes') {
        $activeAdminPrimary = 'works';
    }
}
if (!isset($adminSectionMeta['work-editor'])) {
    $adminSectionMeta['work-editor'] = [
        'primary' => 'works',
        'primary_label' => '作品管理',
        'label' => '高级编辑',
        'icon' => 'drama',
        'ready' => true,
    ];
    if ($activeAdminSection === 'work-editor') {
        $activeAdminPrimary = 'works';
    }
}
$money = static fn (float $value): string => '￥' . number_format($value, 2);
$trafficMetaLabels = [
    'traffic_platform' => '平台',
    'channel_id' => '渠道',
    'media_app_id' => '应用',
    'ad_id' => '广告',
    'creative_id' => '创意',
    'material_id' => '素材',
];
$trafficMetaLines = static function (array $item) use ($trafficMetaLabels): array {
    $lines = [];
    foreach ($trafficMetaLabels as $key => $label) {
        $value = trim((string) ($item[$key] ?? ''));
        if ($value !== '') {
            $lines[] = $label . ' ' . $value;
        }
    }

    return $lines;
};
$paymentDisplayForOrderView = static fn (array $order): array => $service->paymentDisplayForOrder($order);
$gatewaySceneLabels = [
    'create' => '下单接口',
    'query' => '查询接口',
    'refund' => '退款接口',
    'refund_query' => '退款查询',
    'notify' => '回调通知',
];
$refundStatusLabels = [
    'pending' => '待处理',
    'processing' => '处理中',
    'success' => '退款成功',
    'failed' => '退款失败',
];
$orderStatusMeta = static function (string $status): array {
    return match ($status) {
        'paid' => ['label' => '已支付', 'class' => 'jade'],
        'refund_pending' => ['label' => '退款处理中', 'class' => 'ember'],
        'partial_refunded' => ['label' => '部分退款', 'class' => 'ember'],
        'refunded' => ['label' => '已退款', 'class' => 'ember'],
        'failed' => ['label' => '支付失败', 'class' => 'ember'],
        'closed' => ['label' => '已关闭', 'class' => ''],
        'expired' => ['label' => '已过期', 'class' => ''],
        default => ['label' => '待支付', 'class' => ''],
    };
};
$maskGatewayPayload = static function (mixed $value) use (&$maskGatewayPayload): mixed {
    if (!is_array($value)) {
        return $value;
    }

    $masked = [];
    foreach ($value as $key => $item) {
        $keyText = strtolower((string) $key);
        if (str_contains($keyText, 'private_key') || str_contains($keyText, 'secret') || str_contains($keyText, 'password')) {
            $masked[$key] = '******';
            continue;
        }

        $masked[$key] = $maskGatewayPayload($item);
    }

    return $masked;
};
$formatGatewayPayload = static function (mixed $value) use ($maskGatewayPayload): string {
    if ($value === null || $value === '' || $value === []) {
        return '暂无记录';
    }

    if (is_string($value)) {
        $trimmed = trim($value);
        $decoded = json_decode($trimmed, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return $trimmed !== '' ? $trimmed : '暂无记录';
        }
        $value = $decoded;
    }

    $encoded = json_encode($maskGatewayPayload($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    return $encoded !== false ? $encoded : '参数无法格式化';
};
$dashboardTimezone = new DateTimeZone('Asia/Shanghai');
$today = new DateTimeImmutable('today', $dashboardTimezone);
$todayKey = $today->format('Y-m-d');
$yesterdayKey = $today->modify('-1 day')->format('Y-m-d');
$parseOrderTime = static function (string $raw) use ($dashboardTimezone): ?DateTimeImmutable {
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }

    try {
        if (preg_match('/(?:Z|[+-]\d{2}:?\d{2})$/i', $raw) === 1) {
            return (new DateTimeImmutable($raw))->setTimezone($dashboardTimezone);
        }

        return new DateTimeImmutable($raw, $dashboardTimezone);
    } catch (Exception) {
        return null;
    }
};
$orderFilters = [
    'order_no' => trim((string) ($_GET['order_no'] ?? '')),
    'user_keyword' => trim((string) ($_GET['user_keyword'] ?? '')),
    'payment_route_id' => trim((string) ($_GET['payment_route_id'] ?? '')),
    'promotion_code' => trim((string) ($_GET['promotion_code'] ?? '')),
    'traffic_platform' => trim((string) ($_GET['traffic_platform'] ?? '')),
    'channel_id' => trim((string) ($_GET['channel_id'] ?? '')),
    'media_app_id' => trim((string) ($_GET['media_app_id'] ?? '')),
    'ad_id' => trim((string) ($_GET['ad_id'] ?? '')),
    'material_id' => trim((string) ($_GET['material_id'] ?? '')),
    'status' => (string) ($_GET['status'] ?? 'all'),
    'per_page' => (int) ($_GET['per_page'] ?? 10),
    'page' => (int) ($_GET['page'] ?? 1),
];
$hasExplicitOrderStatusFilter = isset($_GET['status']) && (string) $_GET['status'] !== '';
$orderStatusOptions = [
    'all' => '全部状态',
    'pending' => '待支付',
    'paid' => '已支付',
    'refund_pending' => '退款中',
    'partial_refunded' => '部分退款',
    'refunded' => '已退款',
    'failed' => '支付失败',
    'closed' => '已关闭',
    'expired' => '已过期',
];
$orderSectionConfigs = [
    'orders' => ['title' => '订单管理', 'summary' => '全量订单总览，支持按订单号、用户和支付状态筛选。', 'statuses' => ['all']],
    'pending-orders' => ['title' => '待支付订单', 'summary' => '聚焦待支付订单，适合批量查询支付状态和人工补单。', 'statuses' => ['pending']],
    'paid-orders' => ['title' => '已支付订单', 'summary' => '展示已支付和部分退款订单，可继续查看详情或发起退款。', 'statuses' => ['paid', 'partial_refunded']],
    'refund-orders' => ['title' => '退款订单', 'summary' => '集中查看退款处理中、部分退款、已退款和退款失败记录。', 'statuses' => ['refund_pending', 'partial_refunded', 'refunded'], 'include_refund_requests' => true],
    'payment-query' => ['title' => '支付状态查询', 'summary' => '专门用于按订单号/用户查询支付状态，并批量查询当前页待支付订单。', 'statuses' => ['all']],
];
$activeOrderSectionIds = array_keys($orderSectionConfigs);
if (!isset($orderStatusOptions[$orderFilters['status']])) {
    $orderFilters['status'] = 'all';
}
$orderFilters['per_page'] = in_array($orderFilters['per_page'], [10, 100], true) ? $orderFilters['per_page'] : 10;
$userIndexForOrders = [];
foreach ($users as $user) {
    $userIndexForOrders[(int) ($user['id'] ?? 0)] = $user;
}
$userPhoneMatches = [];
if ($orderFilters['user_keyword'] !== '') {
    foreach ($users as $user) {
        $userIdText = (string) ($user['id'] ?? '');
        $phoneText = (string) ($user['phone'] ?? '');
        if ($userIdText === $orderFilters['user_keyword'] || ($phoneText !== '' && str_contains($phoneText, $orderFilters['user_keyword']))) {
            $userPhoneMatches[(int) ($user['id'] ?? 0)] = true;
        }
    }
}
$orderHasRefundRequest = static fn (array $order): bool => !empty($order['refund_requests']) || !empty($order['refund_history']) || !empty($order['refund_pending']) || !empty($order['refund_no']) || ((float) ($order['refund_total'] ?? $order['refund_amount'] ?? 0) > 0);
$orderMatchesSearch = static function (array $order, array $filters, array $matchedUsers) use ($orderStatusForView, $paymentDisplayForOrderView): bool {
    if ($filters['order_no'] !== '' && !str_contains((string) ($order['order_no'] ?? ''), $filters['order_no'])) {
        return false;
    }

    if (($filters['payment_route_id'] ?? '') !== '') {
        $display = $paymentDisplayForOrderView($order);
        if ((string) ($display['route_id'] ?? '') !== (string) $filters['payment_route_id']) {
            return false;
        }
    }

    if ($filters['user_keyword'] !== '') {
        $orderUserId = (int) ($order['user_id'] ?? 0);
        if ((string) $orderUserId !== $filters['user_keyword'] && empty($matchedUsers[$orderUserId])) {
            return false;
        }
    }

    foreach (['promotion_code', 'traffic_platform', 'channel_id', 'media_app_id', 'ad_id', 'material_id'] as $key) {
        if (($filters[$key] ?? '') !== '' && !str_contains((string) ($order[$key] ?? ''), (string) $filters[$key])) {
            return false;
        }
    }

    return $filters['status'] === 'all' || $orderStatusForView($order) === $filters['status'];
};
$sortOrdersDesc = static function (array &$items) use ($parseOrderTime): void {
    usort($items, static function (array $a, array $b) use ($parseOrderTime): int {
        $timeA = $parseOrderTime((string) ($a['created_at'] ?? ''))?->getTimestamp() ?? 0;
        $timeB = $parseOrderTime((string) ($b['created_at'] ?? ''))?->getTimestamp() ?? 0;

        return [$timeB, (int) ($b['id'] ?? 0)] <=> [$timeA, (int) ($a['id'] ?? 0)];
    });
};
$filterOrdersForSection = static function (array $items, array $filters, array $config) use ($orderMatchesSearch, $orderStatusForView, $orderHasRefundRequest, $userPhoneMatches): array {
    $allowedStatuses = array_values((array) ($config['statuses'] ?? ['all']));

    return array_values(array_filter($items, static function (array $order) use ($filters, $config, $allowedStatuses, $orderMatchesSearch, $orderStatusForView, $orderHasRefundRequest, $userPhoneMatches): bool {
        if (!$orderMatchesSearch($order, $filters, $userPhoneMatches)) {
            return false;
        }

        if (!in_array('all', $allowedStatuses, true) && !in_array($orderStatusForView($order), $allowedStatuses, true)) {
            if (empty($config['include_refund_requests']) || !$orderHasRefundRequest($order)) {
                return false;
            }
        }

        return true;
    }));
};
$orderSectionData = [];
foreach ($orderSectionConfigs as $sectionId => $config) {
    $sectionFilters = $orderFilters;
    $sectionStatusLocked = $sectionId !== 'orders' && $sectionId !== 'payment-query';
    if ($sectionStatusLocked) {
        $sectionFilters['status'] = 'all';
    } elseif ($sectionId === 'payment-query' && !$hasExplicitOrderStatusFilter) {
        $sectionFilters['status'] = 'pending';
    }

    $sectionOrders = $filterOrdersForSection($orders, $sectionFilters, $config);
    $sortOrdersDesc($sectionOrders);
    $total = count($sectionOrders);
    $totalPages = max(1, (int) ceil($total / $sectionFilters['per_page']));
    $page = max(1, min($totalPages, $sectionFilters['page']));
    $sectionFilters['page'] = $page;
    $offset = ($page - 1) * $sectionFilters['per_page'];
    $paginated = array_slice($sectionOrders, $offset, $sectionFilters['per_page']);
    $currentNos = array_values(array_map(static fn (array $order): string => (string) ($order['order_no'] ?? ''), $paginated));
    $pendingIntegrated = array_values(array_filter($paginated, static fn (array $order): bool => $isIntegratedPaymentOrderView($order) && $orderStatusForView($order) === 'pending'));
    $callbackOrderNos = array_values(array_map(
        static fn (array $order): string => (string) ($order['order_no'] ?? ''),
        array_filter($paginated, static function (array $order) use ($orderStatusForView): bool {
            $status = $orderStatusForView($order);
            $isTest = !empty($order['is_test']) || ((string) ($order['type'] ?? '') === 'payment_test');

            return !$isTest && in_array($status, ['paid', 'partial_refunded', 'refunded'], true);
        })
    ));
    $queryParams = static function (array $overrides = []) use ($sectionFilters, $sectionId): string {
        $params = [
            'admin_section' => $sectionId,
            'order_no' => $sectionFilters['order_no'],
            'user_keyword' => $sectionFilters['user_keyword'],
            'payment_route_id' => $sectionFilters['payment_route_id'],
            'promotion_code' => $sectionFilters['promotion_code'],
            'traffic_platform' => $sectionFilters['traffic_platform'],
            'channel_id' => $sectionFilters['channel_id'],
            'media_app_id' => $sectionFilters['media_app_id'],
            'ad_id' => $sectionFilters['ad_id'],
            'material_id' => $sectionFilters['material_id'],
            'status' => $sectionFilters['status'],
            'per_page' => $sectionFilters['per_page'],
            'page' => $sectionFilters['page'],
        ];
        foreach ($overrides as $key => $value) {
            $params[$key] = $value;
        }
        $params = array_filter($params, static fn ($value): bool => $value !== '' && $value !== null && $value !== 'all');

        return http_build_query($params);
    };
    $orderSectionData[$sectionId] = [
        'config' => $config,
        'filters' => array_merge($sectionFilters, ['page' => $page]),
        'orders' => $sectionOrders,
        'paginated' => $paginated,
        'total' => $total,
        'total_pages' => $totalPages,
        'current_page_order_nos' => $currentNos,
        'pending_integrated' => $pendingIntegrated,
        'callback_order_nos' => $callbackOrderNos,
        'query_params' => $queryParams,
        'status_locked' => $sectionStatusLocked,
    ];
}
$filteredOrders = $orderSectionData['orders']['orders'];
$paginatedOrders = $orderSectionData['orders']['paginated'];
$orderTotalFiltered = $orderSectionData['orders']['total'];
$orderTotalPages = $orderSectionData['orders']['total_pages'];
$orderFilters = $orderSectionData['orders']['filters'];
$currentPageOrderNos = $orderSectionData['orders']['current_page_order_nos'];
$currentPagePendingIntegratedOrders = $orderSectionData['orders']['pending_integrated'];
$currentPageCallbackOrderNos = $orderSectionData['orders']['callback_order_nos'];
$orderQueryParams = $orderSectionData['orders']['query_params'];
$orderPageUrl = static fn (int $page): string => '/jxdjadmin?' . $orderQueryParams(['page' => $page]) . '#orders';
$orderActionLogs = array_values((array) ($order_action_logs ?? []));
usort($orderActionLogs, static function (array $a, array $b) use ($parseOrderTime): int {
    $timeA = $parseOrderTime((string) ($a['created_at'] ?? ''))?->getTimestamp() ?? 0;
    $timeB = $parseOrderTime((string) ($b['created_at'] ?? ''))?->getTimestamp() ?? 0;

    return [$timeB, (int) ($b['id'] ?? 0)] <=> [$timeA, (int) ($a['id'] ?? 0)];
});
$recentPaymentQueryLogs = array_values(array_filter($orderActionLogs, static fn (array $log): bool => in_array((string) ($log['action'] ?? ''), ['query_payment', 'bulk_query_payment'], true)));
$recentPaymentQueryLogs = array_slice($recentPaymentQueryLogs, 0, 8);
$repairActionLogs = array_values(array_filter($orderActionLogs, static fn (array $log): bool => in_array((string) ($log['action'] ?? ''), ['query_payment', 'bulk_query_payment', 'query_refund', 'refund_apply', 'repair_callback'], true)));
$repairActionLogs = array_slice($repairActionLogs, 0, 12);
$repairExceptionOrders = array_values(array_filter($orders, static function (array $order) use ($orderStatusForView, $parseOrderTime): bool {
    if ($orderStatusForView($order) !== 'pending') {
        return false;
    }

    if (!empty($order['gateway_last_error']) || (!empty($order['gateway_last_scene']) && empty($order['gateway_last_success']))) {
        return true;
    }

    if (!empty($order['gateway_payment_url']) || !empty($order['gateway_trade_no'])) {
        $createdAt = $parseOrderTime((string) ($order['created_at'] ?? ''));
        return $createdAt !== null && (time() - $createdAt->getTimestamp()) > 900;
    }

    return false;
}));
$sortOrdersDesc($repairExceptionOrders);
$trendDays = [];
for ($i = 6; $i >= 0; $i--) {
    $day = $today->modify("-{$i} days");
    $trendDays[$day->format('Y-m-d')] = [
        'label' => $day->format('m/d'),
        'amount' => 0.0,
        'income' => 0.0,
        'refund' => 0.0,
        'orders' => 0,
    ];
}

foreach ($businessOrders as $order) {
    $status = (string) ($order['status'] ?? 'pending');
    if (!in_array($status, $settledOrderStatuses, true)) {
        continue;
    }

    $paidTime = $parseOrderTime((string) ($order['paid_at'] ?? $order['created_at'] ?? ''));
    if ($paidTime === null) {
        continue;
    }

    $key = $paidTime->format('Y-m-d');
    if (!isset($trendDays[$key])) {
        continue;
    }

    $amount = (float) ($order['amount'] ?? 0);
    $refunded = min($amount, max(0.0, (float) ($order['refund_total'] ?? $order['refund_amount'] ?? 0)));
    $trendDays[$key]['amount'] += $amount;
    $trendDays[$key]['income'] += max(0, $amount - $refunded);
    $trendDays[$key]['refund'] += $refunded;
    $trendDays[$key]['orders']++;
}

$trendRows = array_values($trendDays);
$amountValues = array_column($trendRows, 'amount');
$incomeValues = array_column($trendRows, 'income');
$refundValues = array_column($trendRows, 'refund');
$orderValues = array_column($trendRows, 'orders');
$todayTrend = $trendDays[$todayKey] ?? ['amount' => 0.0, 'income' => 0.0, 'refund' => 0.0, 'orders' => 0];
$todayRevenue = (float) $todayTrend['amount'];
$todayNetRevenue = (float) $todayTrend['income'];
$todayRefundAmount = (float) $todayTrend['refund'];
$todayOrderCount = (int) $todayTrend['orders'];
$periodRevenue = array_sum($amountValues);
$periodNetRevenue = array_sum($incomeValues);
$periodRefundAmount = array_sum($refundValues);
$periodOrderCount = array_sum($orderValues);
$maxTrend = max(1.0, max($amountValues ?: [0]), max($incomeValues ?: [0]));
$niceAxisMax = static function (float $value): float {
    if ($value <= 1.0) {
        return 1.0;
    }

    $magnitude = 10 ** floor(log10($value));
    $normalized = $value / $magnitude;
    $nice = match (true) {
        $normalized <= 1 => 1,
        $normalized <= 2 => 2,
        $normalized <= 3 => 3,
        $normalized <= 5 => 5,
        default => 10,
    };

    return $nice * $magnitude;
};
$axisMax = $niceAxisMax($maxTrend);
$axisMoney = static function (float $value): string {
    if (abs($value) < 0.005) {
        return '￥0';
    }

    return '￥' . rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
};
$tooltipMoney = static function (float $value): string {
    if (abs($value) < 0.005) {
        return '0';
    }

    return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
};
$hasTrendData = max(array_map(static fn (array $row): float => $row['amount'] + $row['income'], $trendRows)) > 0;
$chartWidth = 620;
$chartHeight = 310;
$chartLeft = 54;
$chartRight = 56;
$chartTop = 70;
$chartBottom = 250;
$plotWidth = $chartWidth - $chartLeft - $chartRight;
$plotHeight = $chartBottom - $chartTop;
$buildChartPoints = static function (array $values) use ($axisMax, $chartLeft, $chartBottom, $plotWidth, $plotHeight): array {
    $count = max(1, count($values));
    return array_map(static function (float $value, int $index) use ($axisMax, $chartLeft, $chartBottom, $plotWidth, $plotHeight, $count): array {
        $x = $chartLeft + ($count === 1 ? 0 : ($plotWidth / ($count - 1)) * $index);
        $y = $chartBottom - (($value / $axisMax) * $plotHeight);

        return ['x' => round($x, 2), 'y' => round($y, 2), 'value' => $value];
    }, array_map('floatval', $values), array_keys($values));
};
$pointString = static fn (array $points): string => implode(' ', array_map(static fn (array $point): string => $point['x'] . ',' . $point['y'], $points));
$areaPath = static function (array $points) use ($chartBottom): string {
    if (empty($points)) {
        return '';
    }
    $first = $points[0];
    $last = $points[array_key_last($points)];

    return 'M ' . $first['x'] . ' ' . $chartBottom . ' L ' . implode(' L ', array_map(static fn (array $point): string => $point['x'] . ' ' . $point['y'], $points)) . ' L ' . $last['x'] . ' ' . $chartBottom . ' Z';
};
$amountPoints = $buildChartPoints($amountValues);
$incomePoints = $buildChartPoints($incomeValues);
$gridTicks = [];
for ($i = 0; $i <= 5; $i++) {
    $gridTicks[] = [
        'y' => round($chartTop + ($plotHeight / 5) * $i, 2),
        'value' => $axisMax * (1 - $i / 5),
    ];
}
$trendHoverItems = [];
$activeTrendIndex = 0;
foreach ($trendRows as $index => $row) {
    if (($row['amount'] + $row['income']) > 0) {
        $activeTrendIndex = $index;
    }
}
if (!$hasTrendData) {
    $activeTrendIndex = (int) floor(max(0, count($trendRows) - 1) / 2);
}
$hitboxWidth = $plotWidth / max(1, count($trendRows));
$tooltipWidth = 132;
$tooltipHeight = 88;
foreach ($trendRows as $index => $row) {
    $amountPoint = $amountPoints[$index];
    $incomePoint = $incomePoints[$index];
    $guideY = $amountPoint['y'];
    $tooltipX = min(max($chartLeft + 8, $amountPoint['x'] - ($tooltipWidth / 2)), $chartWidth - $chartRight - $tooltipWidth - 8);
    $tooltipY = max($chartTop + 10, min($chartBottom - $tooltipHeight - 12, min($amountPoint['y'], $incomePoint['y']) + 22));
    $xPillWidth = 54;
    $trendHoverItems[] = [
        'index' => $index,
        'row' => $row,
        'amount' => $amountPoint,
        'income' => $incomePoint,
        'guide_y' => $guideY,
        'hitbox_x' => max($chartLeft, $amountPoint['x'] - ($hitboxWidth / 2)),
        'hitbox_width' => min($hitboxWidth, ($chartWidth - $chartRight) - max($chartLeft, $amountPoint['x'] - ($hitboxWidth / 2))),
        'tooltip_x' => $tooltipX,
        'tooltip_y' => $tooltipY,
        'x_pill_x' => min(max($chartLeft, $amountPoint['x'] - ($xPillWidth / 2)), $chartWidth - $chartRight - $xPillWidth),
        'x_pill_width' => $xPillWidth,
    ];
}
$kpis = [
    ['theme' => 'blue', 'icon' => 'revenue', 'label' => '今日交易额', 'value' => $money($todayRevenue), 'meta' => '近 7 天累计 ' . $money($periodRevenue)],
    ['theme' => 'green', 'icon' => 'orders', 'label' => '今日订单数', 'value' => number_format($todayOrderCount) . ' 笔', 'meta' => '近 7 天成交 ' . number_format((int) $periodOrderCount) . ' 笔'],
    ['theme' => 'orange', 'icon' => 'profit', 'label' => '今日实收净额', 'value' => $money($todayNetRevenue), 'meta' => '今日退款 ' . $money($todayRefundAmount)],
    ['theme' => 'cyan', 'icon' => 'withdraw', 'label' => '近 7 天可提现', 'value' => $money($periodNetRevenue), 'meta' => '近 7 天退款 ' . $money($periodRefundAmount)],
];
$settledOrders = array_values(array_filter($businessOrders, static fn (array $order): bool => in_array((string) ($order['status'] ?? 'pending'), $settledOrderStatuses, true)));
$screenGrossRevenue = array_sum(array_map(static fn (array $order): float => (float) ($order['amount'] ?? 0), $settledOrders));
$screenRefundTotal = array_sum(array_map(static fn (array $order): float => min((float) ($order['amount'] ?? 0), max(0.0, (float) ($order['refund_total'] ?? $order['refund_amount'] ?? 0))), $settledOrders));
$screenNetRevenue = max(0, $screenGrossRevenue - $screenRefundTotal);
$screenOrderTotal = max(1, count($businessOrders));
$screenPaidRate = round(($paidOrders / $screenOrderTotal) * 100, 1);
$screenRefundRate = round(($refundedOrders / $screenOrderTotal) * 100, 1);
$screenOnlineDramaCount = count(array_filter($dramas, static fn (array $drama): bool => ($drama['status'] ?? '') === 'online'));
$screenOnlineNovelCount = count(array_filter($novels, static fn (array $novel): bool => ($novel['status'] ?? '') === 'online'));
$screenOnlineContentCount = $screenOnlineDramaCount + $screenOnlineNovelCount;
$screenMemberCount = count(array_filter($users, static fn (array $user): bool => !empty($user['membership'])));
$screenKpis = [
    ['label' => '累计交易额', 'value' => $money($screenGrossRevenue), 'unit' => 'GMV', 'tone' => 'cyan'],
    ['label' => '平台实收', 'value' => $money($screenNetRevenue), 'unit' => 'NET', 'tone' => 'green'],
    ['label' => '支付成功率', 'value' => number_format($screenPaidRate, 1) . '%', 'unit' => 'PAY', 'tone' => 'violet'],
    ['label' => '在线内容', 'value' => number_format($screenOnlineContentCount) . ' 部', 'unit' => 'CONTENT', 'tone' => 'amber'],
];
$screenTrendMax = max(1.0, max($amountValues ?: [0]), max($incomeValues ?: [0]));
$screenTrendBars = array_map(static function (array $row) use ($screenTrendMax): array {
    $amountHeight = max(8, min(100, (int) round(((float) $row['amount'] / $screenTrendMax) * 100)));
    $incomeHeight = max(6, min(100, (int) round(((float) $row['income'] / $screenTrendMax) * 100)));

    return [
        'label' => $row['label'],
        'amount' => (float) $row['amount'],
        'income' => (float) $row['income'],
        'orders' => (int) $row['orders'],
        'amount_height' => $amountHeight,
        'income_height' => $incomeHeight,
    ];
}, $trendRows);
$contentLeaderboard = array_map(static function (array $drama): array {
    return [
        'content_type' => '短剧',
        'id' => (int) ($drama['id'] ?? 0),
        'title' => (string) ($drama['title'] ?? '未命名短剧'),
        'status' => (string) ($drama['status'] ?? 'draft'),
        'units' => count((array) ($drama['episodes'] ?? [])),
        'unit_label' => '集',
        'orders' => 0,
        'revenue' => 0.0,
    ];
}, $dramas);
$contentLeaderboard = array_merge($contentLeaderboard, array_map(static function (array $novel): array {
    return [
        'content_type' => '小说',
        'id' => (int) ($novel['id'] ?? 0),
        'title' => (string) ($novel['title'] ?? '未命名小说'),
        'status' => (string) ($novel['status'] ?? 'draft'),
        'units' => count((array) ($novel['chapters'] ?? [])),
        'unit_label' => '章',
        'orders' => 0,
        'revenue' => 0.0,
    ];
}, $novels));
$dramaIndex = [];
$novelIndexForLeaderboard = [];
foreach ($contentLeaderboard as $index => $row) {
    if ((string) ($row['content_type'] ?? '') === '小说') {
        $novelIndexForLeaderboard[$row['id']] = $index;
    } else {
        $dramaIndex[$row['id']] = $index;
    }
}
foreach ($settledOrders as $order) {
    $orderContentType = (string) ($order['content_type'] ?? 'drama');
    $index = null;
    if ($orderContentType === 'novel') {
        $novelId = (int) ($order['novel_id'] ?? 0);
        $index = $novelIndexForLeaderboard[$novelId] ?? null;
    } else {
        $dramaId = (int) ($order['drama_id'] ?? 0);
        $index = $dramaIndex[$dramaId] ?? null;
    }
    if ($index === null) {
        continue;
    }
    $amount = (float) ($order['amount'] ?? 0);
    $refunded = min($amount, max(0.0, (float) ($order['refund_total'] ?? $order['refund_amount'] ?? 0)));
    $contentLeaderboard[$index]['orders']++;
    $contentLeaderboard[$index]['revenue'] += max(0, $amount - $refunded);
}
usort($contentLeaderboard, static fn (array $a, array $b): int => [$b['revenue'], $b['orders'], $b['units']] <=> [$a['revenue'], $a['orders'], $a['units']]);
$contentLeaderboard = array_slice($contentLeaderboard, 0, 6);
$maxContentRevenue = max(1.0, max(array_column($contentLeaderboard, 'revenue') ?: [0]));
$channelRows = [];
foreach ($businessOrders as $order) {
    $display = $paymentDisplayForOrderView($order);
    $label = (string) ($display['method_name'] ?? '未知方式');
    $channelRows[$label] ??= ['label' => $label, 'orders' => 0, 'amount' => 0.0];
    $channelRows[$label]['orders']++;
    $channelRows[$label]['amount'] += (float) ($order['amount'] ?? 0);
}
if (empty($channelRows)) {
    $channelRows['暂无订单'] = ['label' => '暂无订单', 'orders' => 0, 'amount' => 0.0];
}
$channelRows = array_values($channelRows);
usort($channelRows, static fn (array $a, array $b): int => [$b['amount'], $b['orders']] <=> [$a['amount'], $a['orders']]);
$maxChannelAmount = max(1.0, max(array_column($channelRows, 'amount') ?: [0]));
$channelTotalAmount = array_sum(array_map(static fn (array $row): float => (float) ($row['amount'] ?? 0), $channelRows));
$hasChannelData = $channelTotalAmount > 0.004;
$primaryChannelPercent = $hasChannelData ? max(3, min(100, (int) round(((float) ($channelRows[0]['amount'] ?? 0) / $channelTotalAmount) * 100))) : 0;
$paymentChannelRows = [];
$paymentChannelStatusCounts = ['enabled' => 0, 'pending' => 0, 'disabled' => 0];
foreach ($paymentRoutes as $index => $route) {
    $routeId = (string) ($route['id'] ?? '');
    $routeOrders = array_values(array_filter($businessOrders, static function (array $order) use ($route, $routeId, $paymentDisplayForOrderView): bool {
        $display = $paymentDisplayForOrderView($order);
        if ($routeId !== '' && (string) ($display['route_id'] ?? '') === $routeId) {
            return true;
        }

        return (string) ($display['provider'] ?? '') === (string) ($route['provider'] ?? '')
            && (string) ($display['method'] ?? '') === (string) ($route['payment_method'] ?? '');
    }));
    $routeOrderCount = count($routeOrders);
    $routePaidCount = 0;
    $routeTotalAmount = 0.0;
    $routePaidAmount = 0.0;
    $routeTodayAmount = 0.0;
    $routeYesterdayAmount = 0.0;
    foreach ($routeOrders as $order) {
        $amount = (float) ($order['amount'] ?? 0);
        $routeTotalAmount += $amount;
        if (in_array($orderStatusForView($order), ['paid', 'partial_refunded', 'refunded'], true)) {
            $routePaidCount++;
            $netAmount = max(0.0, $amount - (float) ($order['refund_total'] ?? $order['refund_amount'] ?? 0));
            $routePaidAmount += $netAmount;
            $paidTime = $parseOrderTime((string) ($order['paid_at'] ?? $order['created_at'] ?? ''));
            if ($paidTime !== null && $paidTime->format('Y-m-d') === $todayKey) {
                $routeTodayAmount += $netAmount;
            }
            if ($paidTime !== null && $paidTime->format('Y-m-d') === $yesterdayKey) {
                $routeYesterdayAmount += $netAmount;
            }
        }
    }
    $routeStatus = !empty($route['enabled'])
        ? 'enabled'
        : ((!empty($route['api_url']) || !empty($route['merchant_id'])) ? 'pending' : 'disabled');
    $paymentChannelStatusCounts[$routeStatus]++;
    $safeRoute = $route;
    unset($safeRoute['merchant_private_key'], $safeRoute['platform_public_key'], $safeRoute['secret_key']);
    $safeRoute['has_merchant_private_key'] = !empty($route['merchant_private_key']);
    $safeRoute['has_platform_public_key'] = !empty($route['platform_public_key']);
    $safeRoute['has_secret_key'] = !empty($route['secret_key']);
    $safeRoute['is_default'] = !empty($route['is_default']);
    $safeRoute['enabled'] = !empty($route['enabled']);
    $paymentChannelRows[] = [
        'route' => $route,
        'safe_json' => json_encode($safeRoute, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT),
        'status' => $routeStatus,
        'status_label' => ['enabled' => '启用中', 'pending' => '待开启', 'disabled' => '已停用'][$routeStatus],
        'status_class' => ['enabled' => 'is-enabled', 'pending' => 'is-pending', 'disabled' => 'is-disabled'][$routeStatus],
        'total_amount' => $routeTotalAmount,
        'paid_amount' => $routePaidAmount,
        'today_amount' => $routeTodayAmount,
        'yesterday_amount' => $routeYesterdayAmount,
        'success_rate' => $routeOrderCount > 0 ? round($routePaidCount / $routeOrderCount * 100, 1) : 0,
        'order_count' => $routeOrderCount,
        'created_at' => (string) (($route['created_at'] ?? '') ?: ($route['updated_at'] ?? '历史配置')),
        'sort_index' => $index + 1,
    ];
}
$paymentMethodOptions = [
    'alipay' => ['name' => '支付宝', 'devices' => 'H5 / 扫码 / 移动端'],
    'wechat' => ['name' => '微信支付', 'devices' => 'H5 / 公众号 / 小程序'],
    'wxpay' => ['name' => '微信支付', 'devices' => 'H5 / 公众号 / 小程序'],
    'qqpay' => ['name' => 'QQ 钱包', 'devices' => 'H5 / 扫码'],
    'bank' => ['name' => '网银支付', 'devices' => 'PC / H5'],
    'jdpay' => ['name' => '京东钱包', 'devices' => 'H5 / 扫码'],
    'unionpay' => ['name' => '云闪付', 'devices' => 'H5 / 扫码'],
    'paypal' => ['name' => 'PayPal', 'devices' => 'H5 / 海外'],
    'douyinpay' => ['name' => '抖音支付', 'devices' => 'H5 / 抖音端'],
];
$paymentProviderOptions = [
    'jingxiu' => [
        'name' => '精秀聚合支付',
        'summary' => '适合支付宝/微信等聚合收款，使用 mchid + RSA2 密钥。',
        'api_url' => '',
        'sign_type' => 'RSA2',
        'default_trade_type' => 'alipayWap',
        'merchant_label' => '商户号 mchid',
        'channel_id_label' => '上游通道 ID（数字）',
        'channel_code_label' => '通道编码',
        'secret_mode' => 'rsa',
    ],
    'superpay' => [
        'name' => '超级支付',
        'summary' => 'payjf.cn 通道，merchant_id 对应 pid，支持 MD5 / RSA。',
        'api_url' => 'http://payjf.cn',
        'sign_type' => 'MD5',
        'default_trade_type' => 'alipay',
        'merchant_label' => '商户号 pid',
        'channel_id_label' => '上游通道 ID（数字）',
        'channel_code_label' => '通道编码',
        'secret_mode' => 'md5',
    ],
];
$paymentMethodRows = [];
foreach ($paymentMethodOptions as $methodCode => $methodMeta) {
    $methodRoutes = array_values(array_filter($paymentRoutes, static fn (array $route): bool => (string) ($route['payment_method'] ?? '') === $methodCode));
    $methodOrders = array_values(array_filter($businessOrders, static function (array $order) use ($methodCode, $paymentDisplayForOrderView): bool {
        $display = $paymentDisplayForOrderView($order);

        return (string) ($display['method'] ?? '') === $methodCode;
    }));
    $todayAmount = 0.0;
    foreach ($methodOrders as $order) {
        if (!in_array($orderStatusForView($order), ['paid', 'partial_refunded', 'refunded'], true)) {
            continue;
        }
        $paidTime = $parseOrderTime((string) ($order['paid_at'] ?? $order['created_at'] ?? ''));
        if ($paidTime !== null && $paidTime->format('Y-m-d') === $todayKey) {
            $todayAmount += max(0.0, (float) ($order['amount'] ?? 0) - (float) ($order['refund_total'] ?? $order['refund_amount'] ?? 0));
        }
    }
    $paymentMethodRows[] = [
        'code' => $methodCode,
        'name' => $methodRoutes[0]['payment_method_name'] ?? $methodMeta['name'],
        'devices' => $methodMeta['devices'],
        'today_amount' => $todayAmount,
        'route_count' => count($methodRoutes),
        'enabled' => count(array_filter($methodRoutes, static fn (array $route): bool => !empty($route['enabled']))) > 0,
    ];
}
$paymentRouteDefaultsJson = json_encode([
    'provider' => 'superpay',
    'provider_name' => '超级支付',
    'channel_name' => '超级支付主通道',
    'payment_method' => 'alipay',
    'payment_method_name' => '支付宝',
    'trade_type' => 'alipay',
    'daily_amount_limit' => '0',
    'daily_order_limit' => '0',
    'frequency_window' => '0',
    'frequency_count' => '0',
    'min_amount' => '0',
    'max_amount' => '0',
    'open_start_hour' => '0',
    'open_end_hour' => '23',
    'api_url' => 'http://payjf.cn',
    'merchant_id' => '',
    'pay_channel_id' => '',
    'channel_code' => '',
    'sign_type' => 'MD5',
    'request_timeout' => '12',
    'notes' => '',
    'enabled' => false,
    'is_default' => false,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
$renderAnalyticsFilterBar = static function (string $sectionId, string $summaryText = '') use ($analyticsFilters, $analyticsCompare, $analyticsFilterActive, $analyticsFilterCount, $analyticsFilterSummaryParts, $apps, $agentRows, $analyticsFilterPresets, $analyticsFilterPresetUrl, $analyticsFilterPresetLabels, $filterPresetSummary, $currentAdminId, $adminScope, $csrfField, $money): void {
    $appOptions = [];
    foreach ((array) $apps as $app) {
        $appKey = (string) ($app['app_key'] ?? '');
        if ($appKey !== '') {
            $appOptions[$appKey] = (string) (($app['name'] ?? '') ?: $appKey);
        }
    }
    $agentOptionsByRole = ['business' => [], 'leader' => [], 'agent' => []];
    foreach ((array) $agentRows as $row) {
        $role = (string) ($row['role'] ?? 'agent');
        if (!isset($agentOptionsByRole[$role])) {
            continue;
        }
        $agentOptionsByRole[$role][(int) ($row['id'] ?? 0)] = (string) (($row['path'] ?? '') ?: ($row['name'] ?? '投放账号'));
    }
    $datePresetLabels = [
        'all' => '全部时间',
        'today' => '今日',
        'yesterday' => '昨日',
        'last_7_days' => '近7天',
        'last_30_days' => '近30天',
        'last_90_days' => '近90天',
        'this_month' => '本月',
        'last_month' => '上月',
        'custom' => '自定义',
    ];
    $currentDatePreset = (string) ($analyticsFilters['date_preset'] ?? 'all');
    if (!isset($datePresetLabels[$currentDatePreset])) {
        $currentDatePreset = 'all';
    }
    $formatMetricChange = static function (array $change, string $type = 'number') use ($money): string {
        $diff = $change['diff'] ?? null;
        if ($diff === null) {
            return '-';
        }
        $prefix = (float) $diff > 0 ? '+' : '';
        if ($type === 'money') {
            return $prefix . $money((float) $diff);
        }
        if ($type === 'rate') {
            return $prefix . number_format((float) $diff, 2) . ' pct';
        }

        return $prefix . number_format((float) $diff);
    };
    ?>
    <form class="order-filter-bar analytics-filter-bar" method="get" action="/jxdjadmin#<?= htmlspecialchars($sectionId) ?>">
        <input type="hidden" name="admin_section" value="<?= htmlspecialchars($sectionId) ?>">
        <label>时间范围
            <select name="analytics_date_preset">
                <?php foreach ($datePresetLabels as $presetValue => $presetLabel): ?>
                    <option value="<?= htmlspecialchars($presetValue) ?>" <?= $currentDatePreset === $presetValue ? 'selected' : '' ?>><?= htmlspecialchars($presetLabel) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>开始日期<input name="analytics_date_start" type="date" value="<?= htmlspecialchars((string) ($analyticsFilters['date_start'] ?? '')) ?>"></label>
        <label>结束日期<input name="analytics_date_end" type="date" value="<?= htmlspecialchars((string) ($analyticsFilters['date_end'] ?? '')) ?>"></label>
        <label>应用
            <select name="analytics_app_key">
                <option value="">全部应用</option>
                <?php foreach ($appOptions as $appKey => $appName): ?>
                    <option value="<?= htmlspecialchars((string) $appKey) ?>" <?= (string) ($analyticsFilters['app_key'] ?? '') === (string) $appKey ? 'selected' : '' ?>><?= htmlspecialchars($appName) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>商务
            <select name="analytics_business_id">
                <option value="0">全部商务</option>
                <?php foreach ($agentOptionsByRole['business'] as $agentId => $agentName): ?>
                    <option value="<?= (int) $agentId ?>" <?= (int) ($analyticsFilters['business_id'] ?? 0) === (int) $agentId ? 'selected' : '' ?>><?= htmlspecialchars($agentName) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>组长
            <select name="analytics_leader_id">
                <option value="0">全部组长</option>
                <?php foreach ($agentOptionsByRole['leader'] as $agentId => $agentName): ?>
                    <option value="<?= (int) $agentId ?>" <?= (int) ($analyticsFilters['leader_id'] ?? 0) === (int) $agentId ? 'selected' : '' ?>><?= htmlspecialchars($agentName) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>代理
            <select name="analytics_agent_id">
                <option value="0">全部代理</option>
                <?php foreach ($agentOptionsByRole['agent'] as $agentId => $agentName): ?>
                    <option value="<?= (int) $agentId ?>" <?= (int) ($analyticsFilters['agent_id'] ?? 0) === (int) $agentId ? 'selected' : '' ?>><?= htmlspecialchars($agentName) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>推广入口<input name="analytics_promotion_link_id" type="number" min="0" value="<?= (int) ($analyticsFilters['promotion_link_id'] ?? 0) ?: '' ?>" placeholder="链接ID"></label>
        <label>推广码<input name="analytics_promotion_code" value="<?= htmlspecialchars((string) ($analyticsFilters['promotion_code'] ?? '')) ?>" placeholder="code"></label>
        <label>投放平台<input name="analytics_traffic_platform" value="<?= htmlspecialchars((string) ($analyticsFilters['traffic_platform'] ?? '')) ?>" placeholder="巨量 / 快手"></label>
        <label>渠道ID<input name="analytics_channel_id" value="<?= htmlspecialchars((string) ($analyticsFilters['channel_id'] ?? '')) ?>" placeholder="channel_id"></label>
        <label>广告ID<input name="analytics_ad_id" value="<?= htmlspecialchars((string) ($analyticsFilters['ad_id'] ?? '')) ?>" placeholder="ad_id"></label>
        <label>素材ID<input name="analytics_material_id" value="<?= htmlspecialchars((string) ($analyticsFilters['material_id'] ?? '')) ?>" placeholder="material_id"></label>
        <div class="order-filter-actions">
            <button class="btn primary" type="submit">筛选统计</button>
            <a class="btn ghost" href="/jxdjadmin?admin_section=<?= htmlspecialchars($sectionId) ?>#<?= htmlspecialchars($sectionId) ?>">重置</a>
        </div>
    </form>
    <div class="row-card analytics-scope-card">
        <span>
            <strong><?= $analyticsFilterActive ? '当前统计筛选 ' . number_format($analyticsFilterCount) . ' 项' : '当前统计口径：全部可见数据' ?></strong>
            <em><?= htmlspecialchars($analyticsFilterActive ? implode(' / ', $analyticsFilterSummaryParts) : ($summaryText !== '' ? $summaryText : '可按应用、组织、推广入口、平台、渠道、广告和素材收窄数据。')) ?></em>
        </span>
    </div>
    <div class="filter-preset-panel analytics-preset-panel">
        <form class="filter-preset-save" method="post" action="/jxdjadmin?admin_section=<?= htmlspecialchars($sectionId) ?>#<?= htmlspecialchars($sectionId) ?>">
            <input type="hidden" name="admin_action" value="save_filter_preset">
            <input type="hidden" name="admin_section" value="<?= htmlspecialchars($sectionId) ?>">
            <input type="hidden" name="preset_scope" value="analytics">
            <input type="hidden" name="preset_return_section" value="<?= htmlspecialchars($sectionId) ?>">
            <?= $csrfField() ?>
            <?php
            $analyticsPresetFieldMap = [
                'date_preset' => 'analytics_date_preset',
                'date_start' => 'analytics_date_start',
                'date_end' => 'analytics_date_end',
                'app_key' => 'analytics_app_key',
                'business_id' => 'analytics_business_id',
                'leader_id' => 'analytics_leader_id',
                'agent_id' => 'analytics_agent_id',
                'promotion_link_id' => 'analytics_promotion_link_id',
                'promotion_code' => 'analytics_promotion_code',
                'traffic_platform' => 'analytics_traffic_platform',
                'channel_id' => 'analytics_channel_id',
                'ad_id' => 'analytics_ad_id',
                'material_id' => 'analytics_material_id',
            ];
            ?>
            <?php foreach ($analyticsPresetFieldMap as $filterKey => $fieldName): ?>
                <input type="hidden" name="<?= htmlspecialchars($fieldName) ?>" value="<?= htmlspecialchars((string) ($analyticsFilters[$filterKey] ?? '')) ?>">
            <?php endforeach; ?>
            <input name="preset_name" placeholder="保存当前统计筛选">
            <label><span><input type="checkbox" name="preset_shared" value="1"> 共享</span></label>
            <button class="btn ghost" type="submit">保存方案</button>
        </form>
        <?php if (!empty($analyticsFilterPresets)): ?>
            <div class="filter-preset-list">
                <?php foreach ($analyticsFilterPresets as $preset): ?>
                    <?php $canDeletePreset = (int) ($preset['admin_id'] ?? 0) === $currentAdminId || (string) ($adminScope['role'] ?? '') === 'super_admin'; ?>
                    <div class="filter-preset-item">
                        <a href="<?= htmlspecialchars($analyticsFilterPresetUrl($preset, $sectionId)) ?>">
                            <strong><?= htmlspecialchars((string) ($preset['name'] ?? '统计筛选')) ?><?= !empty($preset['is_shared']) ? ' · 共享' : '' ?></strong>
                            <em class="muted"><?= htmlspecialchars($filterPresetSummary($preset, $analyticsFilterPresetLabels)) ?></em>
                        </a>
                        <?php if ($canDeletePreset): ?>
                            <form class="inline-form" method="post" action="/jxdjadmin?admin_section=<?= htmlspecialchars($sectionId) ?>#<?= htmlspecialchars($sectionId) ?>" onsubmit="return confirm('确认删除这个筛选方案吗？');">
                                <input type="hidden" name="admin_action" value="delete_filter_preset">
                                <input type="hidden" name="admin_section" value="<?= htmlspecialchars($sectionId) ?>">
                                <input type="hidden" name="preset_scope" value="analytics">
                                <input type="hidden" name="preset_return_section" value="<?= htmlspecialchars($sectionId) ?>">
                                <input type="hidden" name="preset_id" value="<?= (int) ($preset['id'] ?? 0) ?>">
                                <?= $csrfField() ?>
                                <button class="btn ghost" type="submit">删除</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php if (!empty($analyticsCompare['enabled'])): ?>
        <?php $compareChanges = (array) ($analyticsCompare['changes'] ?? []); ?>
        <div class="repair-grid">
            <div class="row-card">
                <span>
                    <strong>统计周期对比</strong>
                    <em><?= htmlspecialchars((string) ($analyticsCompare['current_label'] ?? '当前周期')) ?> vs <?= htmlspecialchars((string) ($analyticsCompare['previous_label'] ?? '上一周期')) ?></em>
                </span>
            </div>
            <div class="row-card">
                <span>
                    <strong><?= htmlspecialchars($formatMetricChange((array) ($compareChanges['revenue'] ?? []), 'money')) ?></strong>
                    <em>净收入环比</em>
                </span>
            </div>
            <div class="row-card">
                <span>
                    <strong><?= htmlspecialchars($formatMetricChange((array) ($compareChanges['cost'] ?? []), 'money')) ?></strong>
                    <em>成本环比</em>
                </span>
            </div>
            <div class="row-card">
                <span>
                    <strong><?= htmlspecialchars($formatMetricChange((array) ($compareChanges['paid_orders'] ?? []))) ?></strong>
                    <em>付费订单环比</em>
                </span>
            </div>
            <div class="row-card">
                <span>
                    <strong><?= htmlspecialchars($formatMetricChange((array) ($compareChanges['recovery_rate'] ?? []), 'rate')) ?></strong>
                    <em>回本率变化</em>
                </span>
            </div>
        </div>
    <?php endif; ?>
    <?php
};
$statusRows = [
    ['label' => '待支付', 'value' => $pendingOrders, 'tone' => 'pending'],
    ['label' => '已支付', 'value' => $paidOrders, 'tone' => 'paid'],
    ['label' => '退款单', 'value' => $refundedOrders, 'tone' => 'refund'],
];
$latestScreenOrders = array_slice(array_reverse($businessOrders), 0, 7);
$screenNow = new DateTimeImmutable('now', $dashboardTimezone);
?>
<section class="admin-shell" data-active-admin-section="<?= htmlspecialchars($activeAdminSection) ?>" data-active-admin-primary="<?= htmlspecialchars($activeAdminPrimary) ?>">
    <div class="admin-mobile-drawer-backdrop" data-admin-menu-close hidden></div>
    <aside class="panel admin-menu">
        <button class="admin-mobile-menu-toggle" type="button" data-admin-menu-toggle aria-expanded="false">
            <span>
                <b data-admin-mobile-current><?= htmlspecialchars((string) ($adminSectionMeta[$activeAdminSection]['label'] ?? '数据概览')) ?></b>
                <em data-admin-mobile-primary><?= htmlspecialchars((string) ($adminPrimaryMenus[$activeAdminPrimary]['label'] ?? '工作台')) ?></em>
            </span>
            <i aria-hidden="true"></i>
        </button>
        <div class="admin-mobile-menu-head">
            <span>菜单</span>
            <strong>精秀短剧</strong>
        </div>
        <div class="admin-mobile-primary-grid">
            <?php foreach ($adminPrimaryMenus as $primaryId => $primary): ?>
                <a class="<?= $primaryId === $activeAdminPrimary ? 'is-active' : '' ?>" href="#<?= htmlspecialchars((string) $primary['default']) ?>" data-admin-primary="<?= htmlspecialchars($primaryId) ?>" data-admin-target="<?= htmlspecialchars((string) $primary['default']) ?>">
                    <?= jx_icon($primary['icon']) ?><span><?= htmlspecialchars($primary['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <strong class="admin-menu-section-title"><?= jx_icon($adminPrimaryMenus[$activeAdminPrimary]['icon']) ?><span data-secondary-title><?= htmlspecialchars($adminPrimaryMenus[$activeAdminPrimary]['label']) ?></span></strong>
        <?php foreach ($adminPrimaryMenus as $primaryId => $primary): ?>
            <div class="secondary-menu-group <?= $primaryId === $activeAdminPrimary ? 'is-active' : '' ?>" data-secondary-group="<?= htmlspecialchars($primaryId) ?>">
                <?php foreach ($primary['children'] as $child): ?>
                    <a class="<?= $child['id'] === $activeAdminSection ? 'is-active' : '' ?>" href="#<?= htmlspecialchars($child['id']) ?>" data-admin-primary="<?= htmlspecialchars($primaryId) ?>" data-admin-target="<?= htmlspecialchars($child['id']) ?>">
                        <?= jx_icon($child['icon']) ?>
                        <span class="admin-menu-label">
                            <span data-admin-menu-label><?= htmlspecialchars($child['label']) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <a class="btn ghost admin-menu-logout" href="/jxdjadmin/logout"><?= jx_icon('logout') ?><span>退出登录</span></a>
    </aside>

    <div class="admin-panel">
        <section class="admin-workbench admin-section <?= $activeAdminSection === 'overview' ? 'is-active' : '' ?>" id="admin-section-overview" data-admin-section="overview" data-admin-primary="dashboard">
            <div class="dashboard-kpi-grid">
                <?php foreach ($kpis as $kpi): ?>
                    <div class="kpi dashboard-kpi <?= htmlspecialchars($kpi['theme']) ?>">
                        <span class="kpi-icon"><?= jx_icon($kpi['icon']) ?></span>
                        <small><?= htmlspecialchars($kpi['label']) ?></small>
                        <strong><?= htmlspecialchars($kpi['value']) ?></strong>
                        <em><?= htmlspecialchars($kpi['meta']) ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="insight-grid">
                <div class="panel insight-card trend-insight">
                    <div class="trend-card-head">
                        <h2>近 7 天交易趋势</h2>
                        <div class="chart-legend"><span class="amount">交易金额</span><span class="income">平台收入</span></div>
                    </div>
                    <div class="trend-chart" aria-label="近 7 天交易趋势图">
                        <?php if ($hasTrendData): ?>
                            <svg class="trend-svg" viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" role="img" aria-label="近 7 天交易金额和平台收入折线图">
                                <defs>
                                    <filter id="trendTooltipShadow" x="-25%" y="-25%" width="150%" height="150%">
                                        <feDropShadow dx="0" dy="10" stdDeviation="10" flood-color="#6d7685" flood-opacity=".18"/>
                                    </filter>
                                </defs>
                                <?php foreach ($gridTicks as $tick): ?>
                                    <line class="trend-grid-line" x1="<?= $chartLeft ?>" y1="<?= $tick['y'] ?>" x2="<?= $chartWidth - $chartRight ?>" y2="<?= $tick['y'] ?>"/>
                                    <text class="trend-axis-label trend-axis-label-left" x="<?= $chartLeft - 10 ?>" y="<?= $tick['y'] + 5 ?>"><?= htmlspecialchars($axisMoney((float) $tick['value'])) ?></text>
                                <?php endforeach; ?>
                                <polyline class="trend-line amount" points="<?= htmlspecialchars($pointString($amountPoints)) ?>"/>
                                <polyline class="trend-line income" points="<?= htmlspecialchars($pointString($incomePoints)) ?>"/>
                                <?php foreach ($amountPoints as $index => $point): ?>
                                    <?php $row = $trendRows[$index]; ?>
                                    <g class="trend-point-group">
                                        <circle class="trend-point amount" cx="<?= $point['x'] ?>" cy="<?= $point['y'] ?>" r="5.8"/>
                                        <circle class="trend-point income" cx="<?= $incomePoints[$index]['x'] ?>" cy="<?= $incomePoints[$index]['y'] ?>" r="5.3"/>
                                        <text class="trend-day" x="<?= $point['x'] ?>" y="<?= $chartHeight - 20 ?>"><?= htmlspecialchars($row['label']) ?></text>
                                    </g>
                                <?php endforeach; ?>
                                <?php foreach ($trendHoverItems as $item): ?>
                                    <?php
                                        $row = $item['row'];
                                        $isActiveTrend = $item['index'] === $activeTrendIndex;
                                        $axisLabel = $axisMoney((float) $row['amount']);
                                        $ariaLabel = $row['label'] . '，交易金额 ' . $tooltipMoney((float) $row['amount']) . '，平台收入 ' . $tooltipMoney((float) $row['income']);
                                    ?>
                                    <g class="trend-hover-zone <?= $isActiveTrend ? 'is-active' : '' ?>" data-trend-point tabindex="0" aria-label="<?= htmlspecialchars($ariaLabel) ?>">
                                        <rect class="trend-hitbox" x="<?= $item['hitbox_x'] ?>" y="<?= $chartTop ?>" width="<?= $item['hitbox_width'] ?>" height="<?= $chartBottom - $chartTop + 42 ?>"/>
                                        <g class="trend-hover-layer">
                                            <line class="trend-guide trend-guide-x" x1="<?= $item['amount']['x'] ?>" y1="<?= $chartTop ?>" x2="<?= $item['amount']['x'] ?>" y2="<?= $chartBottom ?>"/>
                                            <line class="trend-guide trend-guide-y" x1="<?= $chartLeft ?>" y1="<?= $item['guide_y'] ?>" x2="<?= $chartWidth - $chartRight ?>" y2="<?= $item['guide_y'] ?>"/>
                                            <rect class="trend-axis-pill" x="6" y="<?= $item['guide_y'] - 13 ?>" width="50" height="26" rx="5"/>
                                            <text class="trend-axis-pill-text" x="31" y="<?= $item['guide_y'] + 5 ?>"><?= htmlspecialchars($axisLabel) ?></text>
                                            <rect class="trend-x-pill" x="<?= $item['x_pill_x'] ?>" y="<?= $chartBottom + 18 ?>" width="<?= $item['x_pill_width'] ?>" height="30" rx="5"/>
                                            <text class="trend-x-pill-text" x="<?= $item['x_pill_x'] + ($item['x_pill_width'] / 2) ?>" y="<?= $chartBottom + 38 ?>"><?= htmlspecialchars($row['label']) ?></text>
                                            <g class="trend-tooltip-card" transform="translate(<?= $item['tooltip_x'] ?> <?= $item['tooltip_y'] ?>)">
                                                <rect class="trend-tooltip-box" width="132" height="88" rx="8"/>
                                                <text class="trend-tooltip-date" x="14" y="26"><?= htmlspecialchars($row['label']) ?></text>
                                                <circle class="trend-tooltip-dot amount" cx="16" cy="50" r="5.5"/>
                                                <text class="trend-tooltip-label" x="30" y="56">交易金额</text>
                                                <text class="trend-tooltip-value" x="118" y="56"><?= htmlspecialchars($tooltipMoney((float) $row['amount'])) ?></text>
                                                <circle class="trend-tooltip-dot income" cx="16" cy="74" r="5.5"/>
                                                <text class="trend-tooltip-label" x="30" y="80">平台收入</text>
                                                <text class="trend-tooltip-value" x="118" y="80"><?= htmlspecialchars($tooltipMoney((float) $row['income'])) ?></text>
                                            </g>
                                        </g>
                                    </g>
                                <?php endforeach; ?>
                            </svg>
                        <?php else: ?>
                            <div class="trend-empty-state">
                                <strong>暂无近 7 天交易数据</strong>
                                <span>有支付成功订单后，这里会自动展示交易额和平台收入趋势。</span>
                                <div class="trend-empty-bars" aria-hidden="true">
                                    <?php foreach ($trendRows as $row): ?><i><b></b><span><?= htmlspecialchars($row['label']) ?></span></i><?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="panel insight-card dashboard-side-card">
                    <div class="section-title admin-section-title">
                        <h2>今日渠道分布</h2>
                        <span class="muted"><?= htmlspecialchars((string) ($defaultPaymentRoute['provider_name'] ?? '精秀聚合支付')) ?> · 按支付方式统计</span>
                    </div>
                    <div class="donut-wrap">
                        <div class="donut <?= $hasChannelData ? '' : 'is-empty' ?>" style="--channel-primary: <?= $primaryChannelPercent ?>%" aria-label="渠道分布图"><strong><?= $hasChannelData ? $primaryChannelPercent . '%' : '0' ?></strong><span><?= $hasChannelData ? '主渠道' : '订单' ?></span></div>
                        <div class="donut-legend">
                            <?php foreach (array_slice($channelRows, 0, 3) as $channelRow): ?>
                                <?php $channelPercent = $hasChannelData ? (int) round(((float) ($channelRow['amount'] ?? 0) / $channelTotalAmount) * 100) : 0; ?>
                                <span><b><?= htmlspecialchars((string) ($channelRow['label'] ?? '渠道')) ?></b><em><?= number_format((int) ($channelRow['orders'] ?? 0)) ?> 笔 · <?= $channelPercent ?>%</em></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="admin-section data-screen-section <?= $activeAdminSection === 'data-screen' ? 'is-active' : '' ?>" id="admin-section-data-screen" data-admin-section="data-screen" data-admin-primary="dashboard">
            <div class="data-screen-shell">
                <div class="data-screen-bg-grid"></div>
                <header class="data-screen-header">
                    <div class="screen-corner left"></div>
                    <div>
                        <span>JINGXIU SHORT DRAMA COMMAND CENTER</span>
                        <h1>精秀短剧实时数据大屏</h1>
                    </div>
                    <div class="data-screen-clock">
                        <strong><?= htmlspecialchars($screenNow->format('H:i:s')) ?></strong>
                        <span><?= htmlspecialchars($screenNow->format('Y-m-d')) ?></span>
                    </div>
                    <div class="screen-corner right"></div>
                </header>

                <div class="data-screen-kpis">
                    <?php foreach ($screenKpis as $item): ?>
                        <div class="screen-kpi <?= htmlspecialchars($item['tone']) ?>">
                            <small><?= htmlspecialchars($item['unit']) ?></small>
                            <span><?= htmlspecialchars($item['label']) ?></span>
                            <strong><?= htmlspecialchars($item['value']) ?></strong>
                            <i></i>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="data-screen-grid">
                    <aside class="screen-column">
                        <div class="screen-card">
                            <div class="screen-card-head">
                                <h2>交易态势</h2>
                                <span>近 7 天</span>
                            </div>
                            <div class="screen-bar-chart">
                                <?php foreach ($screenTrendBars as $bar): ?>
                                    <div class="screen-bar-item" title="<?= htmlspecialchars($bar['label'] . ' 交易 ' . $money($bar['amount']) . ' 实收 ' . $money($bar['income'])) ?>">
                                        <div class="screen-bar-track">
                                            <i class="amount" style="height: <?= (int) $bar['amount_height'] ?>%"></i>
                                            <i class="income" style="height: <?= (int) $bar['income_height'] ?>%"></i>
                                        </div>
                                        <span><?= htmlspecialchars($bar['label']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="screen-mini-metrics">
                                <span><b><?= htmlspecialchars($money($periodRevenue)) ?></b>近 7 天 GMV</span>
                                <span><b><?= number_format((int) $periodOrderCount) ?></b>成交订单</span>
                            </div>
                        </div>

                        <div class="screen-card">
                            <div class="screen-card-head">
                                <h2>支付渠道</h2>
                                <span>聚合支付</span>
                            </div>
                            <div class="screen-channel-list">
                                <?php foreach ($channelRows as $row): ?>
                                    <?php $width = max(8, min(100, (int) round(((float) $row['amount'] / $maxChannelAmount) * 100))); ?>
                                    <div class="screen-channel-row">
                                        <div>
                                            <strong><?= htmlspecialchars($row['label']) ?></strong>
                                            <span><?= number_format((int) $row['orders']) ?> 笔 · <?= htmlspecialchars($money((float) $row['amount'])) ?></span>
                                        </div>
                                        <i><b style="width: <?= $width ?>%"></b></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </aside>

                    <main class="screen-center">
                        <div class="screen-map-card">
                            <div class="screen-orbit">
                                <span class="orbit one"></span>
                                <span class="orbit two"></span>
                                <span class="orbit three"></span>
                                <strong>精秀短剧</strong>
                                <em>H5 运营核心</em>
                            </div>
                            <div class="screen-radar">
                                <i></i><i></i><i></i>
                            </div>
                            <div class="screen-map-stats">
                                <span><b><?= number_format(count($users)) ?></b>用户账户</span>
                                <span><b><?= number_format($screenMemberCount) ?></b>会员用户</span>
                                <span><b><?= number_format(count($dramas) + count($novels)) ?></b>内容库</span>
                            </div>
                        </div>

                        <div class="screen-card screen-status-card">
                            <div class="screen-card-head">
                                <h2>订单状态</h2>
                                <span>全量订单</span>
                            </div>
                            <div class="screen-status-grid">
                                <?php foreach ($statusRows as $row): ?>
                                    <?php $percent = round(((int) $row['value'] / $screenOrderTotal) * 100, 1); ?>
                                    <div class="screen-status-item <?= htmlspecialchars($row['tone']) ?>">
                                        <strong><?= number_format((int) $row['value']) ?></strong>
                                        <span><?= htmlspecialchars($row['label']) ?></span>
                                        <i><b style="width: <?= max(4, min(100, (int) round($percent))) ?>%"></b></i>
                                        <em><?= number_format($percent, 1) ?>%</em>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </main>

                    <aside class="screen-column">
                        <div class="screen-card">
                            <div class="screen-card-head">
                                <h2>内容回收排行</h2>
                                <span>TOP <?= count($contentLeaderboard) ?></span>
                            </div>
                            <div class="screen-rank-list">
                                <?php foreach ($contentLeaderboard as $index => $row): ?>
                                    <?php $width = max(8, min(100, (int) round(((float) $row['revenue'] / $maxContentRevenue) * 100))); ?>
                                    <div class="screen-rank-row">
                                        <b><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></b>
                                        <div>
                                            <strong><?= htmlspecialchars($row['title']) ?></strong>
                                            <span><?= htmlspecialchars((string) ($row['content_type'] ?? '内容')) ?> · <?= number_format((int) $row['orders']) ?> 单 · <?= number_format((int) $row['units']) ?><?= htmlspecialchars((string) ($row['unit_label'] ?? '集')) ?> · <?= htmlspecialchars($money((float) $row['revenue'])) ?></span>
                                            <i><em style="width: <?= $width ?>%"></em></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="screen-card">
                            <div class="screen-card-head">
                                <h2>实时订单流</h2>
                                <span>最新 <?= count($latestScreenOrders) ?> 条</span>
                            </div>
                            <div class="screen-order-stream">
                                <?php if (empty($latestScreenOrders)): ?>
                                    <p class="screen-empty">暂无订单，完成一笔购买后这里会滚动展示。</p>
                                <?php else: ?>
                                    <?php foreach ($latestScreenOrders as $order): ?>
                                        <?php
                                            $status = (string) ($order['status'] ?? 'pending');
                                            $statusText = match ($status) {
                                                'paid' => '支付成功',
                                                'partial_refunded' => '部分退款',
                                                'refunded' => '已退款',
                                                default => '待支付',
                                            };
                                        ?>
                                        <div class="screen-order-line">
                                            <span><?= htmlspecialchars($statusText) ?></span>
                                            <strong><?= htmlspecialchars((string) ($order['order_no'] ?? '-')) ?></strong>
                                            <em><?= htmlspecialchars($money((float) ($order['amount'] ?? 0))) ?></em>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </aside>
                </div>

                <footer class="data-screen-footer">
                    <span>支付成功率 <?= number_format($screenPaidRate, 1) ?>%</span>
                    <span>退款占比 <?= number_format($screenRefundRate, 1) ?>%</span>
                    <span>数据来源：本地运营订单 / 用户 / 内容</span>
                </footer>
            </div>
        </section>

        <section class="panel admin-section <?= $activeAdminSection === 'todo' ? 'is-active' : '' ?>" id="admin-section-todo" data-admin-section="todo" data-admin-primary="dashboard">
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">工作台</span>
                    <h2>待办事项</h2>
                </div>
                <span class="muted">需要人工关注的运营队列</span>
            </div>
            <div class="placeholder-grid">
                <?php foreach ($todoRows as $todo): ?>
                    <a class="system-item" href="<?= htmlspecialchars((string) $todo['href']) ?>" data-admin-target="<?= htmlspecialchars(trim(parse_url((string) $todo['href'], PHP_URL_FRAGMENT) ?: '', '#')) ?>">
                        <strong><?= number_format((int) $todo['count']) ?> 条</strong>
                        <span><?= htmlspecialchars((string) $todo['label']) ?> · <?= htmlspecialchars((string) $todo['hint']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel admin-section <?= $activeAdminSection === 'quick-entry' ? 'is-active' : '' ?>" id="admin-section-quick-entry" data-admin-section="quick-entry" data-admin-primary="dashboard">
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">工作台</span>
                    <h2>快捷入口</h2>
                </div>
                <span class="muted">常用后台动作集中跳转</span>
            </div>
            <div class="placeholder-grid">
                <?php foreach ($quickEntryRows as $entry): ?>
                    <a class="system-item" href="<?= htmlspecialchars((string) $entry['href']) ?>" data-admin-target="<?= htmlspecialchars(trim(parse_url((string) $entry['href'], PHP_URL_FRAGMENT) ?: '', '#')) ?>">
                        <strong><?= jx_icon((string) $entry['icon']) ?> <?= htmlspecialchars((string) $entry['label']) ?></strong>
                        <span><?= htmlspecialchars((string) $entry['hint']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel admin-section <?= $activeAdminSection === 'payment' ? 'is-active' : '' ?>" id="admin-section-payment" data-admin-section="payment" data-admin-primary="finance">
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">支付财务</span>
                    <h2>默认收款配置</h2>
                </div>
                <span class="muted"><?= htmlspecialchars((string) ($defaultPaymentRoute['payment_method_name'] ?? '支付宝')) ?> · <?= htmlspecialchars((string) ($defaultPaymentRoute['channel_name'] ?? '默认通道')) ?></span>
            </div>
            <p class="muted">这里配置前台默认使用的付款方式。精秀聚合支付、超级支付都是接口服务商，前台会显示“支付宝 / 微信支付 / 云闪付”等真实支付方式名称。</p>
            <div class="payment-route-summary">
                <div class="system-item"><strong><?= !empty($defaultPaymentRoute['enabled']) ? '真实支付' : '模拟模式' ?></strong><span>当前模式</span></div>
                <div class="system-item"><strong><?= htmlspecialchars((string) ($defaultPaymentRoute['payment_method_name'] ?? '支付宝')) ?></strong><span>前台显示</span></div>
                <div class="system-item"><strong><?= htmlspecialchars((string) ($defaultPaymentRoute['provider_name'] ?? '精秀聚合支付')) ?></strong><span>服务商</span></div>
                <div class="system-item"><strong><?= number_format($enabledPaymentRouteCount) ?> 条</strong><span>已启用路线</span></div>
            </div>
            <form method="post" action="/jxdjadmin" class="stack">
                <input type="hidden" name="admin_action" value="update_payment">
                <?= $csrfField() ?>
                <input type="hidden" name="admin_section" value="payment">
                <input type="hidden" name="payment_route_id" value="<?= htmlspecialchars((string) ($defaultPaymentRoute['id'] ?? 'jingxiu_alipay')) ?>">
                <label><span><input type="checkbox" name="enabled" value="1" <?= !empty($payment['enabled']) ? 'checked' : '' ?>> 启用真实支付</span></label>
                <div class="form-grid">
                    <label>接口服务商代码<input name="provider" value="<?= htmlspecialchars((string) ($defaultPaymentRoute['provider'] ?? 'jingxiu')) ?>" placeholder="jingxiu / superpay"></label>
                    <label>接口服务商名称<input name="provider_name" value="<?= htmlspecialchars((string) ($defaultPaymentRoute['provider_name'] ?? '精秀聚合支付')) ?>" placeholder="精秀聚合支付"></label>
                    <label>通道名称<input name="channel_name" value="<?= htmlspecialchars((string) ($defaultPaymentRoute['channel_name'] ?? '精秀主通道')) ?>" placeholder="精秀主通道"></label>
                    <label>前台支付方式
                        <select name="payment_method">
                            <?php foreach (['alipay' => '支付宝', 'wechat' => '微信支付', 'unionpay' => '云闪付'] as $methodCode => $methodText): ?>
                                <option value="<?= htmlspecialchars($methodCode) ?>" <?= (string) ($defaultPaymentRoute['payment_method'] ?? 'alipay') === $methodCode ? 'selected' : '' ?>><?= htmlspecialchars($methodText) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>支付方式显示名<input name="payment_method_name" value="<?= htmlspecialchars((string) ($defaultPaymentRoute['payment_method_name'] ?? '支付宝')) ?>" placeholder="支付宝"></label>
                    <label>接口交易类型<input name="trade_type" value="<?= htmlspecialchars((string) ($defaultPaymentRoute['trade_type'] ?? $payment['pay_type'] ?? 'alipayWap')) ?>" placeholder="alipayWap"></label>
                    <label>请求地址<input name="api_url" value="<?= htmlspecialchars((string) ($payment['api_url'] ?? '')) ?>" placeholder="https://gateway.jxpays.com"></label>
                    <label>商户号 mchid<input name="merchant_id" value="<?= htmlspecialchars((string) ($payment['merchant_id'] ?? '')) ?>"></label>
                    <label>通道 ID<input name="pay_channel_id" value="<?= htmlspecialchars((string) ($payment['pay_channel_id'] ?? '')) ?>" placeholder="可选"></label>
                    <label>通道编码<input name="channel_code" value="<?= htmlspecialchars((string) ($payment['channel_code'] ?? '')) ?>" placeholder="可选"></label>
                </div>
                <label>商户私钥 merchant_private_key<textarea name="merchant_private_key" placeholder="<?= !empty($payment['merchant_private_key']) ? '已保存，留空不修改' : '请粘贴商户 RSA 私钥 PEM' ?>"></textarea></label>
                <label>平台公钥 platform_public_key<textarea name="platform_public_key" placeholder="<?= !empty($payment['platform_public_key']) ? '已保存，留空不修改' : '请粘贴精秀平台 RSA 公钥 PEM，用于回调验签' ?>"></textarea></label>
                <label>备注<textarea name="notes" placeholder="接口说明或运营备注"><?= htmlspecialchars((string) ($defaultPaymentRoute['notes'] ?? $payment['notes'] ?? '')) ?></textarea></label>
                <p><button class="primary" type="submit">保存默认收款配置</button></p>
            </form>
        </section>

        <section class="panel admin-section <?= $activeAdminSection === 'payment-channel' ? 'is-active' : '' ?>" id="admin-section-payment-channel" data-admin-section="payment-channel" data-admin-primary="finance">
            <div class="section-title payment-channel-title admin-section-title">
                <div>
                    <span class="eyebrow">支付接口</span>
                    <h2>支付通道</h2>
                </div>
                <span class="muted">通道列表 · 编辑业务规则 · 抽屉配置密钥</span>
            </div>

            <div class="payment-channel-notice">
                <span>!</span>
                <p>参考支付接口后台规则：列表只看通道概要，编辑只改运营/风控规则，商户号、密钥和网关地址放到“配置密钥”抽屉里，避免敏感信息铺在列表页。</p>
                <button type="button" data-payment-channel-dismiss aria-label="关闭提示">×</button>
            </div>

            <div class="payment-channel-filter is-collapsed" data-payment-channel-filter>
                <label>搜索
                    <input data-channel-filter-name placeholder="通道ID/名称">
                </label>
                <label>插件
                    <input data-channel-filter-provider placeholder="支付插件">
                </label>
                <label>支付方式
                    <select data-channel-filter-method>
                        <option value="all">所有支付方式</option>
                        <?php foreach ($paymentMethodOptions as $methodCode => $methodMeta): ?>
                            <option value="<?= htmlspecialchars($methodCode) ?>"><?= htmlspecialchars($methodMeta['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="payment-channel-filter-actions">
                    <button class="btn ghost" type="button" data-channel-filter-reset>重置</button>
                    <button class="btn primary" type="button" data-channel-filter-apply>查询</button>
                    <button class="btn ghost" type="button" data-channel-filter-toggle><span data-channel-filter-toggle-text>展开</span></button>
                </div>
                <div class="payment-channel-filter-extra" data-channel-filter-extra hidden>
                    <label>状态
                        <select data-channel-filter-status>
                            <option value="all">全部状态</option>
                            <option value="enabled">启用中</option>
                            <option value="pending">待开启</option>
                            <option value="disabled">已停用</option>
                        </select>
                    </label>
                    <label>商户号
                        <input data-channel-filter-merchant placeholder="商户号 / pid">
                    </label>
                    <label>交易情况
                        <select data-channel-filter-trade>
                            <option value="all">全部交易情况</option>
                            <option value="has_trade">有交易</option>
                            <option value="no_trade">无交易</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="payment-channel-toolbar">
                <div class="payment-channel-toolbar-left">
                    <button class="btn primary" type="button" data-payment-route-create>新增</button>
                    <button class="channel-status-btn is-active" type="button" data-channel-status-filter="all">全部 <?= number_format(count($paymentChannelRows)) ?></button>
                    <button class="channel-status-btn is-enabled" type="button" data-channel-status-filter="enabled">启用中 <?= number_format($paymentChannelStatusCounts['enabled']) ?></button>
                    <button class="channel-status-btn is-pending" type="button" data-channel-status-filter="pending">待开启 <?= number_format($paymentChannelStatusCounts['pending']) ?></button>
                    <button class="channel-status-btn is-disabled" type="button" data-channel-status-filter="disabled">已停用 <?= number_format($paymentChannelStatusCounts['disabled']) ?></button>
                </div>
                <div class="payment-channel-toolbar-right">
                    <span>当前筛选通道总金额：<strong data-channel-total-amount><?= htmlspecialchars(number_format(array_sum(array_column($paymentChannelRows, 'total_amount')), 2)) ?></strong></span>
                    <button class="btn ghost" type="button" data-channel-filter-reset>刷新数据</button>
                    <button class="btn ghost" type="button" data-channel-placeholder-title="表格设置" data-channel-placeholder="表格列设置已预留，后续可接入斑马纹、边框和字段显隐。">表格设置</button>
                </div>
            </div>

            <div class="payment-channel-table-wrap">
                <table class="payment-channel-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>显示名称</th>
                        <th>支付方式</th>
                        <th>支付服务商</th>
                        <th>商户号</th>
                        <th>状态</th>
                        <th>今日收款</th>
                        <th>昨日收款</th>
                        <th>成功率</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($paymentChannelRows)): ?>
                        <tr>
                            <td colspan="10" class="payment-channel-empty">暂无支付通道，请点击“新增”添加第一条通道。</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paymentChannelRows as $row): ?>
                            <?php
                                $route = $row['route'];
                                $routeId = (string) ($route['id'] ?? '');
                                $providerName = (string) ($route['provider_name'] ?? '精秀聚合支付');
                                $providerCode = (string) ($route['provider'] ?? 'jingxiu');
                                $channelName = (string) ($route['channel_name'] ?? '默认通道');
                                $methodCode = (string) ($route['payment_method'] ?? 'alipay');
                                $methodName = (string) ($route['payment_method_name'] ?? '支付宝');
                                $merchantId = (string) ($route['merchant_id'] ?? '');
                                $channelCode = (string) (($route['channel_code'] ?? '') ?: ($route['pay_channel_id'] ?? ''));
                                $notes = trim((string) ($route['notes'] ?? ''));
                                $hasTrade = $row['total_amount'] > 0 ? 'has_trade' : 'no_trade';
                            ?>
                            <tr
                                data-payment-channel-row
                                data-channel-status="<?= htmlspecialchars($row['status']) ?>"
                                data-channel-trade="<?= htmlspecialchars($hasTrade) ?>"
                                data-channel-method="<?= htmlspecialchars($methodCode) ?>"
                                data-channel-name="<?= htmlspecialchars($routeId . ' ' . $providerName . ' ' . $channelName . ' ' . $methodName) ?>"
                                data-channel-merchant="<?= htmlspecialchars($merchantId) ?>"
                                data-channel-provider="<?= htmlspecialchars($providerCode . ' ' . $channelCode) ?>"
                                data-channel-amount="<?= htmlspecialchars(number_format((float) $row['total_amount'], 2, '.', '')) ?>"
                            >
                                <td><code><?= htmlspecialchars($routeId ?: ('route-' . $row['sort_index'])) ?></code></td>
                                <td>
                                    <strong><?= htmlspecialchars($channelName) ?></strong>
                                    <em><?= htmlspecialchars($notes !== '' ? $notes : $providerName) ?></em>
                                </td>
                                <td><span class="method-pill"><?= htmlspecialchars($methodName) ?><em><?= htmlspecialchars($methodCode) ?></em></span></td>
                                <td><strong><?= htmlspecialchars($providerCode) ?></strong><em><?= htmlspecialchars($providerName) ?></em></td>
                                <td><code><?= htmlspecialchars($merchantId !== '' ? $merchantId : '未配置') ?></code></td>
                                <td>
                                    <div class="channel-state-controls">
                                        <form method="post" action="/jxdjadmin#payment-channel" class="channel-switch-form">
                                            <input type="hidden" name="admin_action" value="save_payment_route">
                <?= $csrfField() ?>
                                            <input type="hidden" name="admin_section" value="payment-channel">
                                            <input type="hidden" name="payment_route_id" value="<?= htmlspecialchars($routeId) ?>">
                                            <input type="hidden" name="enabled" value="<?= !empty($route['enabled']) ? '0' : '1' ?>">
                                            <button class="channel-switch <?= htmlspecialchars($row['status_class']) ?>" type="submit" title="<?= !empty($route['enabled']) ? '点击停用通道' : '点击启用通道' ?>">
                                                <i></i><?= htmlspecialchars($row['status_label']) ?>
                                            </button>
                                        </form>
                                        <form method="post" action="/jxdjadmin#payment-channel" class="channel-default-form">
                                            <input type="hidden" name="admin_action" value="save_payment_route">
                <?= $csrfField() ?>
                                            <input type="hidden" name="admin_section" value="payment-channel">
                                            <input type="hidden" name="payment_route_id" value="<?= htmlspecialchars($routeId) ?>">
                                            <input type="hidden" name="is_default" value="1">
                                            <button class="channel-default-btn <?= !empty($route['is_default']) ? 'is-active' : '' ?>" type="submit" <?= !empty($route['is_default']) ? 'disabled' : '' ?>>
                                                <?= !empty($route['is_default']) ? '默认路线' : '设为默认' ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <td><span class="money-tag green"><?= htmlspecialchars($money((float) $row['today_amount'])) ?></span></td>
                                <td><span class="money-tag pink"><?= htmlspecialchars($money((float) $row['yesterday_amount'])) ?></span></td>
                                <td><?= htmlspecialchars(number_format((float) $row['success_rate'], 1)) ?>%</td>
                                <td>
                                    <div class="payment-channel-actions">
                                        <button class="btn mini" type="button" data-payment-route-key data-route='<?= htmlspecialchars((string) $row['safe_json'], ENT_QUOTES) ?>'>配置密钥</button>
                                        <button class="btn mini ghost" type="button" data-payment-route-edit data-route='<?= htmlspecialchars((string) $row['safe_json'], ENT_QUOTES) ?>'>编辑</button>
                                        <a class="btn mini ghost" href="/jxdjadmin?admin_section=orders&payment_route_id=<?= rawurlencode($routeId) ?>#orders">订单</a>
                                        <button class="btn mini ghost" type="button" data-payment-test-open data-route='<?= htmlspecialchars((string) $row['safe_json'], ENT_QUOTES) ?>'>测试</button>
                                        <button class="btn mini ghost" type="button" data-channel-placeholder-title="风控设置" data-channel-placeholder="风控设置入口已预留，后续可接入限额、黑名单、支付频率和异常订单拦截规则。">风控设置</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="payment-channel-footer">
                <span>共 <?= number_format(count($paymentChannelRows)) ?> 条</span>
                <span data-payment-channel-visible-count>当前显示 <?= number_format(count($paymentChannelRows)) ?> 条</span>
                <span>20 条/页</span>
            </div>

            <div class="payment-route-dialog" data-payment-route-dialog hidden>
                <div class="payment-route-dialog-backdrop" data-payment-route-close></div>
                <article class="payment-route-dialog-card" role="dialog" aria-modal="true" aria-labelledby="payment-route-dialog-title">
                    <header class="payment-route-dialog-head">
                        <div>
                            <span class="eyebrow">支付通道</span>
                            <h3 id="payment-route-dialog-title" data-route-dialog-title>编辑通道</h3>
                        </div>
                        <button type="button" data-payment-route-close aria-label="关闭编辑弹窗">×</button>
                    </header>
                    <form method="post" action="/jxdjadmin#payment-channel" class="payment-route-dialog-form">
                        <input type="hidden" name="admin_action" value="save_payment_route">
                <?= $csrfField() ?>
                        <input type="hidden" name="admin_section" value="payment-channel">
                        <input type="hidden" name="payment_route_id" data-route-field="payment_route_id">
                        <input type="hidden" name="create_new_route" data-route-field="create_new_route">
                        <input type="hidden" name="provider_name" data-route-field="provider_name">
                        <input type="hidden" name="payment_method_name" data-route-field="payment_method_name">

                        <section>
                            <h4>基础信息</h4>
                            <div class="form-grid">
                                <label>通道 ID<input data-route-field="route_id_display" value="保存后自动生成" readonly></label>
                                <label>显示名称<input name="channel_name" data-route-field="channel_name" maxlength="20" placeholder="最多 20 个字，例如 支付宝主通道"></label>
                                <label>通道备注<input name="notes" data-route-field="notes" maxlength="20" placeholder="最多 20 个字，例如 备用通道"></label>
                            </div>
                        </section>

                        <section>
                            <h4>第一步：选择前台支付方式</h4>
                            <div class="route-choice-grid payment-method-choice-grid">
                                <?php foreach ($paymentMethodOptions as $methodCode => $methodMeta): ?>
                                    <label class="route-choice-card" data-method-card>
                                        <input type="radio" name="payment_method" value="<?= htmlspecialchars($methodCode) ?>" data-route-field="payment_method" data-method-name="<?= htmlspecialchars($methodMeta['name']) ?>">
                                        <span>
                                            <strong><?= htmlspecialchars($methodMeta['name']) ?></strong>
                                            <em><?= htmlspecialchars($methodCode) ?> · <?= htmlspecialchars($methodMeta['devices']) ?></em>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section>
                            <h4>第二步：选择接口服务商</h4>
                            <div class="route-choice-grid payment-provider-choice-grid">
                                <?php foreach ($paymentProviderOptions as $providerCode => $providerMeta): ?>
                                    <label class="route-choice-card" data-provider-card>
                                        <input type="radio" name="provider" value="<?= htmlspecialchars($providerCode) ?>" data-route-field="provider" data-provider-name="<?= htmlspecialchars($providerMeta['name']) ?>" data-provider-api-url="<?= htmlspecialchars($providerMeta['api_url']) ?>" data-provider-sign-type="<?= htmlspecialchars($providerMeta['sign_type']) ?>" data-provider-trade-type="<?= htmlspecialchars($providerMeta['default_trade_type']) ?>">
                                        <span>
                                            <strong><?= htmlspecialchars($providerMeta['name']) ?></strong>
                                            <em><?= htmlspecialchars($providerMeta['summary']) ?></em>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section data-route-provider-panel>
                            <h4>第三步：填写通道参数</h4>
                            <div class="route-provider-hint" data-route-provider-hint>选择支付方式和服务商后，这里只展示当前通道需要的参数。</div>
                            <div class="form-grid">
                                <label>接口交易类型<input name="trade_type" data-route-field="trade_type" placeholder="alipay / alipayWap"></label>
                                <label data-provider-field="api_url">网关地址<input name="api_url" data-route-field="api_url" placeholder="例如 http://payjf.cn"></label>
                                <label data-provider-field="merchant_id"><span data-provider-label="merchant_id">商户号</span><input name="merchant_id" data-route-field="merchant_id" placeholder="请输入商户号"></label>
                                <label data-provider-field="pay_channel_id"><span data-provider-label="pay_channel_id">上游通道 ID（数字）</span><input name="pay_channel_id" data-route-field="pay_channel_id" inputmode="numeric" pattern="[0-9]*" placeholder="只允许数字，可留空"></label>
                                <label data-provider-field="channel_code"><span data-provider-label="channel_code">通道编码</span><input name="channel_code" data-route-field="channel_code" placeholder="可选"></label>
                                <label data-provider-field="sign_type">签名方式
                                    <select name="sign_type" data-route-field="sign_type">
                                        <option value="MD5">MD5</option>
                                        <option value="RSA">RSA</option>
                                        <option value="RSA2">RSA2</option>
                                    </select>
                                </label>
                                <label>请求超时秒数<input name="request_timeout" data-route-field="request_timeout" type="number" min="1" max="60" step="1" placeholder="12"></label>
                            </div>
                            <h5>轮询与风控</h5>
                            <div class="form-grid">
                                <label>最小金额<input name="min_amount" data-route-field="min_amount" type="number" min="0" step="0.01" placeholder="0"></label>
                                <label>最大金额<input name="max_amount" data-route-field="max_amount" type="number" min="0" step="0.01" placeholder="0"></label>
                                <label>日收款上限<input name="daily_amount_limit" data-route-field="daily_amount_limit" type="number" min="0" step="0.01" placeholder="0"></label>
                                <label>日订单上限<input name="daily_order_limit" data-route-field="daily_order_limit" type="number" min="0" step="1" placeholder="0"></label>
                                <label>频控分钟<input name="frequency_window" data-route-field="frequency_window" type="number" min="0" step="1" placeholder="0"></label>
                                <label>频控订单数<input name="frequency_count" data-route-field="frequency_count" type="number" min="0" step="1" placeholder="0"></label>
                                <label>开放开始小时<input name="open_start_hour" data-route-field="open_start_hour" type="number" min="0" max="23" step="1" placeholder="0"></label>
                                <label>开放结束小时<input name="open_end_hour" data-route-field="open_end_hour" type="number" min="0" max="23" step="1" placeholder="23"></label>
                            </div>
                            <div class="route-secret-panel" data-secret-group="md5">
                                <h5>MD5 接口密钥</h5>
                                <label>接口密钥 secret_key<input name="secret_key" data-route-secret="secret_key" placeholder="留空不修改已保存接口密钥"></label>
                            </div>
                            <div class="route-secret-panel" data-secret-group="rsa">
                                <h5>RSA / RSA2 密钥</h5>
                                <label>商户私钥 merchant_private_key<textarea name="merchant_private_key" data-route-secret="merchant_private_key" placeholder="留空不修改已保存商户私钥"></textarea></label>
                                <label>平台公钥 platform_public_key<textarea name="platform_public_key" data-route-secret="platform_public_key" placeholder="留空不修改已保存平台公钥"></textarea></label>
                            </div>
                        </section>

                        <footer>
                            <span class="muted" data-payment-route-hint>保存通道配置不会主动调用支付接口；测试请使用列表里的“测试”。</span>
                            <button class="btn ghost" type="button" data-payment-route-close>取消</button>
                            <button class="btn primary" type="submit">保存通道</button>
                        </footer>
                    </form>
                </article>
            </div>

            <div class="payment-route-drawer" data-payment-route-key-drawer hidden>
                <div class="payment-route-drawer-backdrop" data-payment-route-key-close></div>
                <aside class="payment-route-drawer-card" role="dialog" aria-modal="true" aria-labelledby="payment-route-key-title">
                    <header class="payment-route-drawer-head">
                        <div>
                            <span class="eyebrow">配置对接密钥</span>
                            <h3 id="payment-route-key-title" data-route-key-title>配置密钥</h3>
                            <p data-route-key-subtitle>密钥留空表示不修改已保存内容。</p>
                        </div>
                        <button type="button" data-payment-route-key-close aria-label="关闭密钥抽屉">×</button>
                    </header>
                    <form method="post" action="/jxdjadmin#payment-channel" class="payment-route-key-form">
                        <input type="hidden" name="admin_action" value="save_payment_route">
                <?= $csrfField() ?>
                        <input type="hidden" name="admin_section" value="payment-channel">
                        <input type="hidden" name="payment_route_id" data-key-field="payment_route_id">

                        <section>
                            <h4>接口参数</h4>
                            <label>网关地址<input name="api_url" data-key-field="api_url" placeholder="http://payjf.cn"></label>
                            <label><span data-key-label="merchant_id">商户号</span><input name="merchant_id" data-key-field="merchant_id"></label>
                            <label><span data-key-label="pay_channel_id">上游通道 ID（数字）</span><input name="pay_channel_id" data-key-field="pay_channel_id" inputmode="numeric" pattern="[0-9]*" placeholder="只允许数字，可选"></label>
                            <label>通道编码<input name="channel_code" data-key-field="channel_code" placeholder="可选"></label>
                            <label>签名方式
                                <select name="sign_type" data-key-field="sign_type">
                                    <option value="MD5">MD5</option>
                                    <option value="RSA">RSA</option>
                                    <option value="RSA2">RSA2（精秀）</option>
                                </select>
                            </label>
                            <label>请求超时秒数<input name="request_timeout" data-key-field="request_timeout" type="number" min="1" placeholder="12"></label>
                        </section>

                        <section>
                            <h4>密钥信息</h4>
                            <p class="muted">列表不会展示密钥明文；这里填写新值才会覆盖旧值。</p>
                            <label>接口密钥 secret_key<input name="secret_key" data-key-secret="secret_key" placeholder="留空不修改已保存接口密钥"></label>
                            <label>商户私钥 merchant_private_key<textarea name="merchant_private_key" data-key-secret="merchant_private_key" placeholder="留空不修改已保存商户私钥"></textarea></label>
                            <label>平台公钥 platform_public_key<textarea name="platform_public_key" data-key-secret="platform_public_key" placeholder="留空不修改已保存平台公钥"></textarea></label>
                        </section>

                        <div class="payment-route-key-note" data-route-key-note>保存后只更新本地通道配置，不会主动调用真实支付接口。</div>
                        <footer>
                            <button class="btn ghost" type="button" data-payment-route-key-close>关闭</button>
                            <button class="btn primary" type="submit">保存密钥</button>
                        </footer>
                    </form>
                </aside>
            </div>

            <div class="channel-action-dialog" data-channel-action-dialog hidden>
                <div class="channel-action-dialog-backdrop" data-channel-action-close></div>
                <article class="channel-action-dialog-card" role="dialog" aria-modal="true" aria-labelledby="channel-action-title">
                    <header>
                        <div>
                            <span class="eyebrow">支付通道</span>
                            <h3 id="channel-action-title" data-channel-action-title>操作提示</h3>
                        </div>
                        <button type="button" data-channel-action-close aria-label="关闭操作提示">×</button>
                    </header>
                    <div class="channel-action-dialog-body">
                        <strong data-channel-action-route>当前通道</strong>
                        <p data-channel-action-message>该能力已预留，暂未接入真实接口。</p>
                        <div class="channel-action-safe-note">安全提示：这里不会触发真实支付接口，不会创建订单，也不会扣款。</div>
                    </div>
                    <footer>
                        <button class="btn primary" type="button" data-channel-action-close>我知道了</button>
                    </footer>
                </article>
            </div>

            <div class="payment-test-dialog" data-payment-test-dialog hidden>
                <div class="payment-test-dialog-backdrop" data-payment-test-close></div>
                <article class="payment-test-dialog-card" role="dialog" aria-modal="true" aria-labelledby="payment-test-title">
                    <header>
                        <div>
                            <span class="eyebrow">真实测试下单</span>
                            <h3 id="payment-test-title">发起支付通道测试</h3>
                        </div>
                        <button type="button" data-payment-test-close aria-label="关闭测试弹窗">×</button>
                    </header>
                    <form method="post" action="/jxdjadmin#payment-channel" class="payment-test-form">
                        <input type="hidden" name="admin_action" value="create_test_payment_order">
                <?= $csrfField() ?>
                        <input type="hidden" name="admin_section" value="payment-channel">
                        <input type="hidden" name="payment_route_id" data-payment-test-field="payment_route_id">

                        <div class="payment-test-route-card">
                            <strong data-payment-test-field="channel_name">当前通道</strong>
                            <span data-payment-test-field="route_meta">支付方式 / 服务商</span>
                        </div>
                        <label>测试金额
                            <input name="test_amount" type="number" min="0.01" max="9999.99" step="0.01" value="0.01" data-payment-test-amount>
                        </label>
                        <label>测试标题
                            <input name="test_subject" value="支付通道测试" maxlength="40" placeholder="支付通道测试">
                        </label>
                        <p class="payment-test-warning">会真实请求当前支付通道并可能产生真实收款；测试支付成功后只标记测试订单，不发放短剧权益，不计入正式收入。</p>
                        <footer>
                            <button class="btn ghost" type="button" data-payment-test-close>取消</button>
                            <button class="btn primary" type="submit">发起测试支付</button>
                        </footer>
                    </form>
                </article>
            </div>

            <template data-payment-route-defaults>
                <?= htmlspecialchars((string) $paymentRouteDefaultsJson, ENT_NOQUOTES) ?>
            </template>
        </section>

        <section class="panel admin-section payment-subsection <?= $activeAdminSection === 'payment-method' ? 'is-active' : '' ?>" id="admin-section-payment-method" data-admin-section="payment-method" data-admin-primary="finance">
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">支付接口</span>
                    <h2>支付方式</h2>
                </div>
                <span class="muted">前台展示给用户看的真实支付方式</span>
            </div>
            <p class="muted">这里把支付宝、微信支付、云闪付等支付方式和后台通道区分开。服务商仍由支付通道负责，例如精秀聚合支付、超级支付。</p>
            <div class="payment-channel-table-wrap payment-method-table-wrap">
                <table class="payment-channel-table payment-method-table">
                    <thead>
                    <tr>
                        <th>调用值</th>
                        <th>名称</th>
                        <th>支持设备</th>
                        <th>今日收款</th>
                        <th>通道数</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($paymentMethodRows as $row): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($row['code']) ?></code></td>
                            <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                            <td><?= htmlspecialchars($row['devices']) ?></td>
                            <td><span class="money-tag green"><?= htmlspecialchars($money((float) $row['today_amount'])) ?></span></td>
                            <td><?= number_format((int) $row['route_count']) ?> 条</td>
                            <td><span class="channel-switch <?= $row['enabled'] ? 'is-enabled' : 'is-pending' ?>"><i></i><?= $row['enabled'] ? '已开启' : '待配置' ?></span></td>
                            <td>
                                <div class="payment-channel-actions">
                                    <button class="btn mini" type="button" data-channel-placeholder-title="支付方式编辑" data-channel-placeholder="支付方式编辑入口已预留，当前请到支付通道里配置具体服务商路线。">编辑</button>
                                    <a class="btn mini ghost" href="/jxdjadmin#orders">订单</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel admin-section payment-subsection <?= $activeAdminSection === 'recharge-products' ? 'is-active' : '' ?>" id="admin-section-recharge-products" data-admin-section="recharge-products" data-admin-primary="finance">
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">变现配置</span>
                    <h2>充值商品</h2>
                </div>
                <span class="muted">商品模板、默认充值列表和关闭弹窗挽留商品。</span>
            </div>

            <form method="post" action="/jxdjadmin" class="row-card stack">
                <input type="hidden" name="admin_action" value="save_recharge_config">
                <input type="hidden" name="admin_section" value="recharge-products">
                <?= $csrfField() ?>
                <p><strong>默认充值与挽留商品</strong></p>
                <div class="form-grid">
                    <label>关闭弹窗挽留商品
                        <select name="retention_product_code">
                            <?php foreach ($rechargeProducts as $product): ?>
                                <?php if (empty($product['enabled'])) { continue; } ?>
                                <option value="<?= htmlspecialchars((string) ($product['code'] ?? '')) ?>" <?= (string) ($product['code'] ?? '') === $retentionProductCode ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($product['name'] ?? '充值商品')) ?> · <?= htmlspecialchars($money((float) ($product['price'] ?? 0))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label><span><input type="checkbox" name="retention_once_per_user" value="1" <?= !empty($rechargeConfig['retention_once_per_user']) ? 'checked' : '' ?>> 每个用户只展示/购买一次挽留商品</span></label>
                </div>
                <p class="muted">默认充值商品会在前台 K币/会员入口优先展示；挽留商品用于用户关闭充值弹窗后的低价召回。</p>
                <div class="form-grid">
                    <?php foreach ($rechargeProducts as $product): ?>
                        <?php if (empty($product['enabled'])) { continue; } ?>
                        <?php $productCode = (string) ($product['code'] ?? ''); ?>
                        <label><span><input type="checkbox" name="global_product_codes[]" value="<?= htmlspecialchars($productCode) ?>" <?= in_array($productCode, $globalRechargeCodes, true) ? 'checked' : '' ?>> <?= htmlspecialchars((string) ($product['name'] ?? '充值商品')) ?>（<?= htmlspecialchars($rechargeTypeLabels[(string) ($product['type'] ?? '')] ?? '商品') ?>）</span></label>
                    <?php endforeach; ?>
                </div>
                <p><button class="btn" type="submit">保存默认配置</button></p>
            </form>

            <form method="post" action="/jxdjadmin" class="row-card stack">
                <input type="hidden" name="admin_action" value="save_recharge_template">
                <input type="hidden" name="admin_section" value="recharge-products">
                <?= $csrfField() ?>
                <p><strong>新增应用商品模板</strong></p>
                <div class="form-grid">
                    <label>模板名称<input name="name" value="默认应用模板" placeholder="例如：快手投流模板"></label>
                    <label>应用标识<input name="app_key" value="default" placeholder="default / app_id"></label>
                    <label>应用名称<input name="app_name" value="默认应用" placeholder="运营后台展示名"></label>
                    <label>状态
                        <select name="status">
                            <option value="active">启用</option>
                            <option value="paused">停用</option>
                        </select>
                    </label>
                    <label>排序<input name="sort" type="number" value="100"></label>
                    <label>备注<input name="remark" placeholder="例如：巨量首充低价模板"></label>
                    <label>挽留商品
                        <select name="retention_product_code">
                            <?php foreach ($rechargeProducts as $product): ?>
                                <?php if (empty($product['enabled'])) { continue; } ?>
                                <option value="<?= htmlspecialchars((string) ($product['code'] ?? '')) ?>">
                                    <?= htmlspecialchars((string) ($product['name'] ?? '充值商品')) ?> · <?= htmlspecialchars($money((float) ($product['price'] ?? 0))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <p class="muted">应用标识会匹配链接参数 app_key / app_id / media_app_id；没有命中时使用 default 模板。</p>
                <div class="form-grid">
                    <?php foreach ($rechargeProducts as $product): ?>
                        <?php if (empty($product['enabled'])) { continue; } ?>
                        <?php $productCode = (string) ($product['code'] ?? ''); ?>
                        <label><span><input type="checkbox" name="product_codes[]" value="<?= htmlspecialchars($productCode) ?>" <?= in_array($productCode, $globalRechargeCodes, true) ? 'checked' : '' ?>> <?= htmlspecialchars((string) ($product['name'] ?? '充值商品')) ?>（<?= htmlspecialchars($rechargeTypeLabels[(string) ($product['type'] ?? '')] ?? '商品') ?>）</span></label>
                    <?php endforeach; ?>
                </div>
                <p><button class="btn" type="submit">新增应用模板</button></p>
            </form>

            <?php foreach ($rechargeTemplates as $template): ?>
                <?php
                $templateCodes = array_map('strval', (array) ($template['product_codes'] ?? []));
                $templateRetentionCode = (string) ($template['retention_product_code'] ?? '');
                ?>
                <form method="post" action="/jxdjadmin" class="row-card stack">
                    <input type="hidden" name="admin_action" value="save_recharge_template">
                    <input type="hidden" name="admin_section" value="recharge-products">
                    <input type="hidden" name="template_id" value="<?= htmlspecialchars((string) ($template['id'] ?? 0)) ?>">
                    <?= $csrfField() ?>
                    <p><strong><?= htmlspecialchars((string) ($template['name'] ?? '应用商品模板')) ?></strong> <span class="muted"><?= htmlspecialchars((string) ($template['app_key'] ?? 'default')) ?> · <?= htmlspecialchars((string) ($template['app_name'] ?? '默认应用')) ?></span></p>
                    <div class="form-grid">
                        <label>模板名称<input name="name" value="<?= htmlspecialchars((string) ($template['name'] ?? '')) ?>"></label>
                        <label>应用标识<input name="app_key" value="<?= htmlspecialchars((string) ($template['app_key'] ?? 'default')) ?>"></label>
                        <label>应用名称<input name="app_name" value="<?= htmlspecialchars((string) ($template['app_name'] ?? '')) ?>"></label>
                        <label>状态
                            <select name="status">
                                <option value="active" <?= (string) ($template['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>启用</option>
                                <option value="paused" <?= (string) ($template['status'] ?? 'active') === 'paused' ? 'selected' : '' ?>>停用</option>
                            </select>
                        </label>
                        <label>排序<input name="sort" type="number" value="<?= htmlspecialchars((string) ($template['sort'] ?? 100)) ?>"></label>
                        <label>备注<input name="remark" value="<?= htmlspecialchars((string) ($template['remark'] ?? '')) ?>"></label>
                        <label>挽留商品
                            <select name="retention_product_code">
                                <?php foreach ($rechargeProducts as $product): ?>
                                    <?php if (empty($product['enabled'])) { continue; } ?>
                                    <?php $productCode = (string) ($product['code'] ?? ''); ?>
                                    <option value="<?= htmlspecialchars($productCode) ?>" <?= $productCode === $templateRetentionCode ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($product['name'] ?? '充值商品')) ?> · <?= htmlspecialchars($money((float) ($product['price'] ?? 0))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <div class="form-grid">
                        <?php foreach ($rechargeProducts as $product): ?>
                            <?php if (empty($product['enabled'])) { continue; } ?>
                            <?php $productCode = (string) ($product['code'] ?? ''); ?>
                            <label><span><input type="checkbox" name="product_codes[]" value="<?= htmlspecialchars($productCode) ?>" <?= in_array($productCode, $templateCodes, true) ? 'checked' : '' ?>> <?= htmlspecialchars((string) ($product['name'] ?? '充值商品')) ?>（<?= htmlspecialchars($rechargeTypeLabels[(string) ($product['type'] ?? '')] ?? '商品') ?>）</span></label>
                        <?php endforeach; ?>
                    </div>
                    <p><button class="btn" type="submit">保存应用模板</button></p>
                </form>
            <?php endforeach; ?>

            <form method="post" action="/jxdjadmin" class="row-card stack">
                <input type="hidden" name="admin_action" value="save_recharge_product">
                <input type="hidden" name="admin_section" value="recharge-products">
                <input type="hidden" name="create_new_product" value="1">
                <?= $csrfField() ?>
                <p><strong>新增商品模板</strong></p>
                <div class="form-grid">
                    <label>商品名称<input name="name" placeholder="例如：9元送9元"></label>
                    <label>类型
                        <select name="type">
                            <option value="coin">云币/K币</option>
                            <option value="vip">会员</option>
                            <option value="full_unlock">全集解锁</option>
                        </select>
                    </label>
                    <label>支付金额<input name="price" type="number" step="0.01" min="0.01" value="9"></label>
                    <label>K币数量<input name="coins" type="number" min="0" value="900"></label>
                    <label>赠送K币<input name="bonus_coins" type="number" min="0" value="0"></label>
                    <label>会员天数<input name="vip_days" type="number" min="0" value="0"></label>
                    <label>全集数量<input name="unlock_count" type="number" min="0" value="0"></label>
                    <label>角标<input name="badge" placeholder="热卖/挽留推荐"></label>
                    <label>描述<input name="description" placeholder="给运营看的商品说明"></label>
                    <label>排序<input name="sort" type="number" value="100"></label>
                </div>
                <div class="form-grid">
                    <label><span><input type="checkbox" name="is_recommended" value="1"> 推荐商品</span></label>
                    <label><span><input type="checkbox" name="enabled" value="1" checked> 启用</span></label>
                </div>
                <p><button class="btn" type="submit">新增商品</button></p>
            </form>

            <?php foreach ($rechargeProducts as $product): ?>
                <form method="post" action="/jxdjadmin" class="row-card stack">
                    <input type="hidden" name="admin_action" value="save_recharge_product">
                    <input type="hidden" name="admin_section" value="recharge-products">
                    <input type="hidden" name="product_code" value="<?= htmlspecialchars((string) ($product['code'] ?? '')) ?>">
                    <?= $csrfField() ?>
                    <p><strong><?= htmlspecialchars((string) ($product['name'] ?? '充值商品')) ?></strong> <span class="muted"><?= htmlspecialchars((string) ($product['code'] ?? '')) ?> · <?= htmlspecialchars($rechargeTypeLabels[(string) ($product['type'] ?? '')] ?? '商品') ?></span></p>
                    <div class="form-grid">
                        <label>商品名称<input name="name" value="<?= htmlspecialchars((string) ($product['name'] ?? '')) ?>"></label>
                        <label>类型
                            <select name="type">
                                <?php foreach ($rechargeTypeLabels as $typeCode => $typeLabel): ?>
                                    <option value="<?= htmlspecialchars($typeCode) ?>" <?= (string) ($product['type'] ?? '') === $typeCode ? 'selected' : '' ?>><?= htmlspecialchars($typeLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>支付金额<input name="price" type="number" step="0.01" min="0.01" value="<?= htmlspecialchars((string) ($product['price'] ?? 0)) ?>"></label>
                        <label>K币数量<input name="coins" type="number" min="0" value="<?= htmlspecialchars((string) ($product['coins'] ?? 0)) ?>"></label>
                        <label>赠送K币<input name="bonus_coins" type="number" min="0" value="<?= htmlspecialchars((string) ($product['bonus_coins'] ?? 0)) ?>"></label>
                        <label>会员天数<input name="vip_days" type="number" min="0" value="<?= htmlspecialchars((string) ($product['vip_days'] ?? 0)) ?>"></label>
                        <label>全集数量<input name="unlock_count" type="number" min="0" value="<?= htmlspecialchars((string) ($product['unlock_count'] ?? 0)) ?>"></label>
                        <label>角标<input name="badge" value="<?= htmlspecialchars((string) ($product['badge'] ?? '')) ?>"></label>
                        <label>描述<input name="description" value="<?= htmlspecialchars((string) ($product['description'] ?? '')) ?>"></label>
                        <label>排序<input name="sort" type="number" value="<?= htmlspecialchars((string) ($product['sort'] ?? 100)) ?>"></label>
                    </div>
                    <div class="form-grid">
                        <label><span><input type="checkbox" name="is_recommended" value="1" <?= !empty($product['is_recommended']) ? 'checked' : '' ?>> 推荐商品</span></label>
                        <label><span><input type="checkbox" name="enabled" value="1" <?= !empty($product['enabled']) ? 'checked' : '' ?>> 启用</span></label>
                    </div>
                    <p><button class="btn" type="submit">保存商品</button></p>
                </form>
            <?php endforeach; ?>
        </section>

        <section class="panel admin-section payment-subsection <?= $activeAdminSection === 'agent-settlement' ? 'is-active' : '' ?>" id="admin-section-agent-settlement" data-admin-section="agent-settlement" data-admin-primary="finance">
            <?php
                $settlementModeLabels = ['revenue_share' => '收入分成', 'profit_share' => '利润分成'];
                $settlementStatusLabels = ['pending' => '待确认', 'confirmed' => '已确认', 'paid' => '已打款', 'rejected' => '已驳回'];
                $settlementConfirmLabels = ['none' => '未确认', 'confirmed' => '已确认到账', 'disputed' => '有异议'];
                $settlementDisputeLabels = ['none' => '无异议', 'open' => '待处理', 'processing' => '处理中', 'resolved' => '已解决', 'rejected' => '已驳回'];
                $settlementResolutionLabels = ['keep_original' => '维持原结算', 'adjust_amount' => '调整佣金', 'supplement_payout' => '补打款', 'reject' => '驳回异议'];
                $payoutBatchStatusLabels = ['generated' => '已生成', 'paid' => '已打款', 'failed' => '失败', 'cancelled' => '已取消'];
            ?>
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">财务结算</span>
                    <h2>代理结算</h2>
                </div>
                <span class="muted">待确认 <?= htmlspecialchars($money((float) ($agentSettlementSummary['pending_amount'] ?? 0))) ?> · 已打款 <?= htmlspecialchars($money((float) ($agentSettlementSummary['paid_amount'] ?? 0))) ?> · 待代理确认 <?= number_format((int) ($agentSettlementSummary['agent_confirm_pending'] ?? 0)) ?> · 待处理异议 <?= number_format((int) ($agentSettlementSummary['agent_dispute_open'] ?? 0)) ?></span>
            </div>
            <div class="kpi-grid">
                <div class="kpi blue">
                    <span class="kpi-icon"><?= jx_icon('withdraw') ?></span>
                    <small>结算单</small>
                    <strong><?= number_format((int) ($agentSettlementSummary['total'] ?? count($agentSettlementRows))) ?></strong>
                    <em>历史结算记录</em>
                </div>
                <div class="kpi orange">
                    <span class="kpi-icon"><?= jx_icon('order') ?></span>
                    <small>待确认</small>
                    <strong><?= htmlspecialchars($money((float) ($agentSettlementSummary['pending_amount'] ?? 0))) ?></strong>
                    <em><?= number_format((int) ($agentSettlementSummary['pending'] ?? 0)) ?> 单</em>
                </div>
                <div class="kpi green">
                    <span class="kpi-icon"><?= jx_icon('payment') ?></span>
                    <small>已确认</small>
                    <strong><?= htmlspecialchars($money((float) ($agentSettlementSummary['confirmed_amount'] ?? 0))) ?></strong>
                    <em><?= number_format((int) ($agentSettlementSummary['confirmed'] ?? 0)) ?> 单待打款</em>
                </div>
                <div class="kpi cyan">
                    <span class="kpi-icon"><?= jx_icon('revenue') ?></span>
                    <small>总佣金</small>
                    <strong><?= htmlspecialchars($money((float) ($agentSettlementSummary['commission_amount'] ?? 0))) ?></strong>
                    <em>收入 <?= htmlspecialchars($money((float) ($agentSettlementSummary['net_revenue'] ?? 0))) ?></em>
                </div>
            </div>
            <form method="post" action="/jxdjadmin#agent-settlement" class="row-card stack">
                <input type="hidden" name="admin_action" value="generate_agent_settlement">
                <input type="hidden" name="admin_section" value="agent-settlement">
                <?= $csrfField() ?>
                <p><strong>生成周期结算</strong></p>
                <div class="form-grid">
                    <label>开始日期<input name="period_start" type="date" value="<?= htmlspecialchars(date('Y-m-01')) ?>"></label>
                    <label>结束日期<input name="period_end" type="date" value="<?= htmlspecialchars(date('Y-m-d')) ?>"></label>
                    <label>代理范围
                        <select name="agent_id">
                            <option value="0">全部代理</option>
                            <?php foreach ($agentRows as $row): ?>
                                <?php $agentId = (int) ($row['id'] ?? 0); ?>
                                <option value="<?= $agentId ?>"><?= htmlspecialchars((string) (($row['path'] ?? '') ?: ($row['name'] ?? '投放账号'))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>结算口径
                        <select name="settlement_mode">
                            <?php foreach ($settlementModeLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>分成比例 %<input name="commission_rate" type="number" min="0" max="100" step="0.01" value="10"></label>
                    <label>备注<input name="remark" placeholder="例如 7月上半月结算"></label>
                </div>
                <p><button class="btn" type="submit">生成结算单</button></p>
            </form>
            <form method="post" action="/jxdjadmin#agent-settlement" class="row-card stack" enctype="multipart/form-data">
                <input type="hidden" name="admin_action" value="bulk_update_agent_settlements">
                <input type="hidden" name="admin_section" value="agent-settlement">
                <?= $csrfField() ?>
                <p><strong>批量处理结算</strong></p>
                <div class="form-grid">
                    <label>原状态
                        <select name="status_filter">
                            <option value="confirmed">已确认</option>
                            <option value="pending">待确认</option>
                            <option value="paid">已打款</option>
                            <option value="rejected">已驳回</option>
                            <option value="all">全部</option>
                        </select>
                    </label>
                    <label>更新为
                        <select name="target_status">
                            <?php foreach ($settlementStatusLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= $value === 'paid' ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>开始日期<input name="period_start" type="date"></label>
                    <label>结束日期<input name="period_end" type="date"></label>
                    <label>代理范围
                        <select name="agent_id">
                            <option value="0">全部代理</option>
                            <?php foreach ($agentRows as $row): ?>
                                <?php $agentId = (int) ($row['id'] ?? 0); ?>
                                <option value="<?= $agentId ?>"><?= htmlspecialchars((string) (($row['path'] ?? '') ?: ($row['name'] ?? '投放账号'))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>结算单ID<input name="settlement_ids_text" placeholder="多个ID用逗号隔开"></label>
                    <label>打款方式<input name="payout_method" placeholder="支付宝/银行卡/微信"></label>
                    <label>打款时间<input name="paid_at" type="datetime-local"></label>
                    <label>打款流水号<input name="payout_reference_no" placeholder="平台流水号/银行流水"></label>
                    <label>凭证链接<input name="payout_proof_url" placeholder="打款截图或凭证地址"></label>
                    <label>上传凭证<input name="payout_proof_file" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.csv"></label>
                    <label>发票/收据号<input name="invoice_no" placeholder="发票号或收据号"></label>
                    <label>备注<input name="remark" placeholder="批量处理备注"></label>
                </div>
                <p><button class="btn" type="submit">批量保存</button></p>
            </form>
            <form method="post" action="/jxdjadmin#agent-settlement" class="row-card stack">
                <input type="hidden" name="admin_action" value="export_agent_settlements_csv">
                <input type="hidden" name="admin_section" value="agent-settlement">
                <?= $csrfField() ?>
                <p><strong>导出结算对账</strong></p>
                <div class="form-grid">
                    <label>状态
                        <select name="status_filter">
                            <option value="all">全部</option>
                            <?php foreach ($settlementStatusLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>结算口径
                        <select name="settlement_mode">
                            <option value="all">全部</option>
                            <?php foreach ($settlementModeLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>开始日期<input name="period_start" type="date"></label>
                    <label>结束日期<input name="period_end" type="date"></label>
                    <label>代理范围
                        <select name="agent_id">
                            <option value="0">全部代理</option>
                            <?php foreach ($agentRows as $row): ?>
                                <?php $agentId = (int) ($row['id'] ?? 0); ?>
                                <option value="<?= $agentId ?>"><?= htmlspecialchars((string) (($row['path'] ?? '') ?: ($row['name'] ?? '投放账号'))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>结算单ID<input name="settlement_ids_text" placeholder="多个ID用逗号隔开"></label>
                </div>
                <p><button class="btn ghost" type="submit">导出CSV</button></p>
            </form>
            <form method="post" action="/jxdjadmin#agent-settlement" class="row-card stack">
                <input type="hidden" name="admin_action" value="export_agent_payout_batch_csv">
                <input type="hidden" name="admin_section" value="agent-settlement">
                <?= $csrfField() ?>
                <p><strong>导出批量打款文件</strong></p>
                <div class="form-grid">
                    <label>打款批次号<input name="payout_batch_no" placeholder="留空自动生成"></label>
                    <label>打款渠道<input name="payout_channel" placeholder="支付宝/银行卡/微信/通用打款"></label>
                    <label>状态
                        <select name="status_filter">
                            <option value="confirmed">已确认</option>
                            <option value="paid">已打款</option>
                            <option value="all">全部</option>
                        </select>
                    </label>
                    <label>开始日期<input name="period_start" type="date"></label>
                    <label>结束日期<input name="period_end" type="date"></label>
                    <label>代理范围
                        <select name="agent_id">
                            <option value="0">全部代理</option>
                            <?php foreach ($agentRows as $row): ?>
                                <?php $agentId = (int) ($row['id'] ?? 0); ?>
                                <option value="<?= $agentId ?>"><?= htmlspecialchars((string) (($row['path'] ?? '') ?: ($row['name'] ?? '投放账号'))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>结算单ID<input name="settlement_ids_text" placeholder="多个ID用逗号隔开"></label>
                </div>
                <p><button class="btn ghost" type="submit">导出打款文件</button></p>
            </form>
            <div class="order-list" style="margin-bottom:16px">
                <?php if (empty($agentPayoutBatches)): ?>
                    <div class="empty">暂无代理打款批次。</div>
                <?php endif; ?>
                <?php foreach (array_slice($agentPayoutBatches, 0, 8) as $batch): ?>
                    <?php
                        $batchStatus = (string) ($batch['status'] ?? 'generated');
                        $batchStatusClass = match ($batchStatus) {
                            'paid' => 'green',
                            'failed', 'cancelled' => 'orange',
                            default => 'blue',
                        };
                    ?>
                    <form method="post" action="/jxdjadmin#agent-settlement" class="order-item" enctype="multipart/form-data">
                        <input type="hidden" name="admin_action" value="update_agent_payout_batch">
                        <input type="hidden" name="admin_section" value="agent-settlement">
                        <input type="hidden" name="batch_id" value="<?= (int) ($batch['id'] ?? 0) ?>">
                        <?= $csrfField() ?>
                        <span>
                            <strong><?= htmlspecialchars((string) ($batch['batch_no'] ?? '打款批次')) ?></strong>
                            <span class="pill <?= htmlspecialchars($batchStatusClass) ?>"><?= htmlspecialchars($payoutBatchStatusLabels[$batchStatus] ?? $batchStatus) ?></span>
                            <em><?= htmlspecialchars((string) ($batch['channel'] ?? '通用打款')) ?> · <?= htmlspecialchars((string) ($batch['file_name'] ?? '')) ?></em>
                            <em><?= htmlspecialchars((string) ($batch['generated_by_admin_name'] ?? '系统')) ?> · <?= htmlspecialchars((string) ($batch['created_at'] ?? '')) ?></em>
                        </span>
                        <span>
                            批次金额 <?= htmlspecialchars($money((float) ($batch['total_amount'] ?? 0))) ?>
                            <em>结算 <?= number_format((int) ($batch['item_count'] ?? 0)) ?> 单 · 代理 <?= number_format(count((array) ($batch['agent_ids'] ?? []))) ?> 个</em>
                            <?php if (!empty($batch['handled_by_admin_name'])): ?><em><?= htmlspecialchars((string) ($batch['handled_by_admin_name'] ?? '')) ?> · <?= htmlspecialchars((string) ($batch['handled_at'] ?? '')) ?></em><?php endif; ?>
                        </span>
                        <span>
                            <select name="status" aria-label="批次状态">
                                <?php foreach ($payoutBatchStatusLabels as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= $batchStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <em>凭证 <input name="proof_url" value="<?= htmlspecialchars((string) ($batch['proof_url'] ?? '')) ?>" placeholder="打款凭证链接"></em>
                            <em>上传 <input name="proof_file" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.csv"></em>
                            <?php if (!empty($batch['proof_file_name'])): ?><em>文件 <?= htmlspecialchars((string) ($batch['proof_file_name'] ?? '')) ?></em><?php endif; ?>
                        </span>
                        <span>
                            <em>打款时间 <input name="paid_at" type="datetime-local" value="<?= htmlspecialchars(str_replace(' ', 'T', (string) ($batch['paid_at'] ?? ''))) ?>"></em>
                            <input name="remark" value="<?= htmlspecialchars((string) ($batch['remark'] ?? '')) ?>" placeholder="批次备注">
                            <button class="btn ghost" type="submit">保存批次</button>
                        </span>
                    </form>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($agentSettlementPreviewRows)): ?>
                <div class="order-list" style="margin-bottom:16px">
                    <?php foreach (array_slice($agentSettlementPreviewRows, 0, 5) as $preview): ?>
                        <div class="order-item">
                            <span>
                                <strong><?= htmlspecialchars((string) ($preview['agent_name'] ?? '代理')) ?></strong>
                                <em><?= htmlspecialchars((string) ($preview['business_name'] ?? '')) ?> / <?= htmlspecialchars((string) ($preview['leader_name'] ?? '')) ?></em>
                            </span>
                            <span>
                                预估佣金 <?= htmlspecialchars($money((float) ($preview['commission_amount'] ?? 0))) ?>
                                <em>收入 <?= htmlspecialchars($money((float) ($preview['net_revenue'] ?? 0))) ?> · 成本 <?= htmlspecialchars($money((float) ($preview['cost_amount'] ?? 0))) ?> · 订单 <?= number_format((int) ($preview['order_count'] ?? 0)) ?></em>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="order-list">
                <?php if (empty($agentSettlementRows)): ?>
                    <div class="empty">暂无代理结算单。可以先按周期生成结算，系统会根据推广归因订单和投放成本计算佣金。</div>
                <?php endif; ?>
                <?php foreach ($agentSettlementRows as $settlement): ?>
                    <?php
                        $settlementStatus = (string) ($settlement['status'] ?? 'pending');
                        $settlementStatusClass = match ($settlementStatus) {
                            'paid' => 'green',
                            'rejected' => 'orange',
                            'confirmed' => 'blue',
                            default => 'orange',
                        };
                        $settlementConfirmStatus = (string) ($settlement['agent_confirm_status'] ?? 'none');
                        $settlementConfirmClass = match ($settlementConfirmStatus) {
                            'confirmed' => 'green',
                            'disputed' => 'orange',
                            default => 'blue',
                        };
                        $settlementDisputeStatus = (string) ($settlement['dispute_status'] ?? 'none');
                        $settlementDisputeClass = match ($settlementDisputeStatus) {
                            'resolved' => 'green',
                            'rejected', 'open' => 'orange',
                            'processing' => 'blue',
                            default => 'blue',
                        };
                    ?>
                    <form method="post" action="/jxdjadmin#agent-settlement" class="order-item" enctype="multipart/form-data">
                        <input type="hidden" name="admin_action" value="update_agent_settlement">
                        <input type="hidden" name="admin_section" value="agent-settlement">
                        <input type="hidden" name="settlement_id" value="<?= (int) ($settlement['id'] ?? 0) ?>">
                        <?= $csrfField() ?>
                        <span>
                            <strong><?= htmlspecialchars((string) ($settlement['agent_name'] ?? '代理')) ?></strong>
                            <span class="pill <?= htmlspecialchars($settlementStatusClass) ?>"><?= htmlspecialchars($settlementStatusLabels[$settlementStatus] ?? $settlementStatus) ?></span>
                            <span class="pill <?= htmlspecialchars($settlementConfirmClass) ?>"><?= htmlspecialchars($settlementConfirmLabels[$settlementConfirmStatus] ?? $settlementConfirmStatus) ?></span>
                            <?php if ($settlementConfirmStatus === 'disputed'): ?><span class="pill <?= htmlspecialchars($settlementDisputeClass) ?>"><?= htmlspecialchars($settlementDisputeLabels[$settlementDisputeStatus] ?? $settlementDisputeStatus) ?></span><?php endif; ?>
                            <em><?= htmlspecialchars((string) ($settlement['period_start'] ?? '')) ?> 至 <?= htmlspecialchars((string) ($settlement['period_end'] ?? '')) ?> · <?= htmlspecialchars($settlementModeLabels[(string) ($settlement['settlement_mode'] ?? 'revenue_share')] ?? '收入分成') ?></em>
                            <em><?= htmlspecialchars((string) ($settlement['business_name'] ?? '')) ?> / <?= htmlspecialchars((string) ($settlement['leader_name'] ?? '')) ?></em>
                        </span>
                        <span>
                            佣金 <?= htmlspecialchars($money((float) ($settlement['commission_amount'] ?? 0))) ?>
                            <em>基数 <?= htmlspecialchars($money((float) ($settlement['commission_base'] ?? 0))) ?> · 比例 <?= number_format((float) ($settlement['commission_rate'] ?? 0), 2) ?>%</em>
                            <em>收入 <?= htmlspecialchars($money((float) ($settlement['net_revenue'] ?? 0))) ?> · 成本 <?= htmlspecialchars($money((float) ($settlement['cost_amount'] ?? 0))) ?> · 订单 <?= number_format((int) ($settlement['order_count'] ?? 0)) ?></em>
                        </span>
                        <span>
                            <select name="status" aria-label="状态">
                                <?php foreach ($settlementStatusLabels as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= $settlementStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <em>方式 <input name="payout_method" value="<?= htmlspecialchars((string) ($settlement['payout_method'] ?? '')) ?>" placeholder="支付宝/银行卡/微信"></em>
                            <em>账号 <input name="payout_account" value="<?= htmlspecialchars((string) ($settlement['payout_account'] ?? '')) ?>" placeholder="收款账号"></em>
                            <em>姓名 <input name="payout_name" value="<?= htmlspecialchars((string) ($settlement['payout_name'] ?? '')) ?>" placeholder="收款人"></em>
                        </span>
                        <span>
                            <em>流水 <input name="payout_reference_no" value="<?= htmlspecialchars((string) ($settlement['payout_reference_no'] ?? '')) ?>" placeholder="平台/银行流水"></em>
                            <em>凭证 <input name="payout_proof_url" value="<?= htmlspecialchars((string) ($settlement['payout_proof_url'] ?? '')) ?>" placeholder="凭证链接"></em>
                            <em>上传 <input name="payout_proof_file" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.csv"></em>
                            <?php if (!empty($settlement['payout_proof_file_name'])): ?><em>文件 <?= htmlspecialchars((string) ($settlement['payout_proof_file_name'] ?? '')) ?></em><?php endif; ?>
                            <em>票据 <input name="invoice_no" value="<?= htmlspecialchars((string) ($settlement['invoice_no'] ?? '')) ?>" placeholder="发票/收据号"></em>
                        </span>
                        <span>
                            <input name="paid_at" type="datetime-local" value="<?= htmlspecialchars(str_replace(' ', 'T', (string) ($settlement['paid_at'] ?? ''))) ?>" aria-label="打款时间">
                            <input name="remark" value="<?= htmlspecialchars((string) ($settlement['remark'] ?? '')) ?>" placeholder="结算备注">
                            <?php if (!empty($settlement['handled_by_admin_name'])): ?><em><?= htmlspecialchars((string) ($settlement['handled_by_admin_name'] ?? '')) ?> · <?= htmlspecialchars((string) ($settlement['handled_at'] ?? '')) ?></em><?php endif; ?>
                            <button class="btn ghost" type="submit">保存状态</button>
                        </span>
                    </form>
                    <?php if ($settlementStatus === 'paid'): ?>
                        <form method="post" action="/jxdjadmin#agent-settlement" class="order-item">
                            <input type="hidden" name="admin_action" value="ack_agent_settlement">
                            <input type="hidden" name="admin_section" value="agent-settlement">
                            <input type="hidden" name="settlement_id" value="<?= (int) ($settlement['id'] ?? 0) ?>">
                            <?= $csrfField() ?>
                            <span>
                                <strong>代理对账确认</strong>
                                <em><?= htmlspecialchars((string) ($settlement['agent_confirmed_by_admin_name'] ?? '')) ?> <?= htmlspecialchars((string) ($settlement['agent_confirmed_at'] ?? '')) ?></em>
                            </span>
                            <span>
                                <select name="agent_confirm_status" aria-label="代理对账状态">
                                    <option value="confirmed" <?= $settlementConfirmStatus === 'confirmed' ? 'selected' : '' ?>>确认到账</option>
                                    <option value="disputed" <?= $settlementConfirmStatus === 'disputed' ? 'selected' : '' ?>>提出异议</option>
                                </select>
                                <em>说明 <input name="agent_confirm_remark" value="<?= htmlspecialchars((string) ($settlement['agent_confirm_remark'] ?? '')) ?>" placeholder="到账说明或异议原因"></em>
                            </span>
                            <span>
                                <em>流水 <?= htmlspecialchars((string) ($settlement['payout_reference_no'] ?? '')) ?></em>
                                <em>打款时间 <?= htmlspecialchars((string) ($settlement['paid_at'] ?? '')) ?></em>
                                <button class="btn ghost" type="submit">提交对账</button>
                            </span>
                        </form>
                    <?php endif; ?>
                    <?php if ($settlementConfirmStatus === 'disputed'): ?>
                        <form method="post" action="/jxdjadmin#agent-settlement" class="order-item">
                            <input type="hidden" name="admin_action" value="resolve_agent_settlement_dispute">
                            <input type="hidden" name="admin_section" value="agent-settlement">
                            <input type="hidden" name="settlement_id" value="<?= (int) ($settlement['id'] ?? 0) ?>">
                            <?= $csrfField() ?>
                            <span>
                                <strong>异议处理</strong>
                                <em>代理说明：<?= htmlspecialchars((string) ($settlement['agent_confirm_remark'] ?? '')) ?></em>
                                <?php if (!empty($settlement['dispute_handled_by_admin_name'])): ?><em><?= htmlspecialchars((string) ($settlement['dispute_handled_by_admin_name'] ?? '')) ?> · <?= htmlspecialchars((string) ($settlement['dispute_handled_at'] ?? '')) ?></em><?php endif; ?>
                            </span>
                            <span>
                                <select name="dispute_status" aria-label="异议状态">
                                    <?php foreach (['open', 'processing', 'resolved', 'rejected'] as $value): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= $settlementDisputeStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($settlementDisputeLabels[$value] ?? $value) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="dispute_resolution_type" aria-label="处理方式">
                                    <option value="">处理方式</option>
                                    <?php foreach ($settlementResolutionLabels as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($settlement['dispute_resolution_type'] ?? '') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <em>调整 <input name="dispute_adjustment_amount" type="number" step="0.01" value="<?= htmlspecialchars((string) ($settlement['dispute_adjustment_amount'] ?? '0')) ?>" placeholder="0.00"></em>
                            </span>
                            <span>
                                <em>异议后佣金 <?= htmlspecialchars($money((float) ($settlement['dispute_final_commission_amount'] ?? $settlement['commission_amount'] ?? 0))) ?></em>
                                <input name="dispute_resolution_remark" value="<?= htmlspecialchars((string) ($settlement['dispute_resolution_remark'] ?? '')) ?>" placeholder="处理结论">
                                <button class="btn ghost" type="submit">保存处理</button>
                            </span>
                        </form>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel admin-section payment-subsection <?= $activeAdminSection === 'apps' ? 'is-active' : '' ?>" id="admin-section-apps" data-admin-section="apps" data-admin-primary="finance">
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">多应用运营</span>
                    <h2>应用管理</h2>
                </div>
                <span class="muted">把应用、支付路线、商品模板和协议链接集中管理。</span>
            </div>
            <div class="payment-rule-grid">
                <div class="system-item"><strong><?= number_format(count($apps)) ?> 个</strong><span>应用总数</span></div>
                <div class="system-item"><strong><?= number_format(count(array_filter($apps, static fn (array $app): bool => (string) ($app['status'] ?? 'active') === 'active'))) ?> 个</strong><span>启用应用</span></div>
                <div class="system-item"><strong><?= number_format(count(array_filter($apps, static fn (array $app): bool => (string) ($app['status'] ?? '') === 'review'))) ?> 个</strong><span>审核应用</span></div>
                <div class="system-item"><strong><?= number_format((int) ($appConfigDeliverySummary['hits'] ?? 0)) ?> 次</strong><span>配置下发</span></div>
                <div class="system-item"><strong><?= number_format((int) ($appConfigDeliverySummary['gray_hits'] ?? 0)) ?> 次</strong><span>灰度命中</span></div>
                <div class="system-item"><strong><?= number_format((int) (($appConfigDeliverySummary['review_hits'] ?? 0) + ($appConfigDeliverySummary['safe_hits'] ?? 0))) ?> 次</strong><span>审核/安全模式</span></div>
            </div>
            <p class="muted">应用侧可调用 <code>api-app-config</code> 获取客户端配置、当前用户阶梯、推荐位和任务规则。</p>

            <form method="post" action="/jxdjadmin" class="row-card stack">
                <input type="hidden" name="admin_action" value="save_app">
                <input type="hidden" name="admin_section" value="apps">
                <?= $csrfField() ?>
                <p><strong>新增应用</strong></p>
                <div class="form-grid">
                    <label>应用名称<input name="name" placeholder="例如：巨量 H5 应用"></label>
                    <label>应用标识<input name="app_key" placeholder="app_key / app_id"></label>
                    <label>应用类型
                        <select name="type">
                            <?php foreach ($appTypeLabels as $typeCode => $typeLabel): ?>
                                <option value="<?= htmlspecialchars($typeCode) ?>"><?= htmlspecialchars($typeLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>状态
                        <select name="status">
                            <?php foreach ($appStatusLabels as $statusCode => $statusLabel): ?>
                                <option value="<?= htmlspecialchars($statusCode) ?>"><?= htmlspecialchars($statusLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>AppID<input name="app_id" placeholder="小程序/快应用/客户端 AppID"></label>
                    <label>AppSecret<input name="app_secret" placeholder="留空则不配置"></label>
                    <label>原始ID<input name="original_id" placeholder="公众号原始ID等"></label>
                    <label>商品模板
                        <select name="product_template_key">
                            <?php foreach ($rechargeTemplates as $template): ?>
                                <option value="<?= htmlspecialchars((string) ($template['app_key'] ?? 'default')) ?>"><?= htmlspecialchars((string) ($template['name'] ?? '应用商品模板')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>支付路线
                        <select name="payment_route_id">
                            <?php foreach ($paymentRoutes as $route): ?>
                                <option value="<?= htmlspecialchars((string) ($route['id'] ?? '')) ?>"><?= htmlspecialchars((string) (($route['channel_name'] ?? '') ?: ($route['provider_name'] ?? '支付通道'))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>首页模板
                        <select name="homepage_template">
                            <option value="mini">小程序风格</option>
                            <option value="marketing">经典 H5</option>
                            <option value="diy">DIY 首页</option>
                        </select>
                    </label>
                    <label>隐私协议<input name="privacy_url" placeholder="/privacy.html"></label>
                    <label>用户协议<input name="agreement_url" placeholder="/agreement.html"></label>
                    <label>回调地址<input name="callback_url" placeholder="应用侧通知地址"></label>
                    <label>排序<input name="sort" type="number" value="100"></label>
                </div>
                <label>备注<input name="remark" placeholder="用于说明应用主体、投放场景或审核策略"></label>
                <p><strong>推荐位配置</strong></p>
                <div class="form-grid">
                    <?php foreach ($appRecommendSlotLabels as $slotKey => $slotLabel): ?>
                        <label><span><input type="checkbox" name="recommend_<?= htmlspecialchars($slotKey) ?>_enabled" value="1" checked> <?= htmlspecialchars($slotLabel) ?></span></label>
                        <label><?= htmlspecialchars($slotLabel) ?>名称<input name="recommend_<?= htmlspecialchars($slotKey) ?>_name" value="<?= htmlspecialchars($slotLabel) ?>"></label>
                        <label>内容类型
                            <select name="recommend_<?= htmlspecialchars($slotKey) ?>_content_type">
                                <option value="drama">短剧</option>
                                <option value="novel" <?= $slotKey === 'reading' ? 'selected' : '' ?>>小说</option>
                                <option value="mixed" <?= $slotKey === 'category' ? 'selected' : '' ?>>混合</option>
                                <option value="url">外链</option>
                            </select>
                        </label>
                        <label>内容ID<input name="recommend_<?= htmlspecialchars($slotKey) ?>_content_id" type="number" value="0"></label>
                        <label>跳转链接<input name="recommend_<?= htmlspecialchars($slotKey) ?>_link" value="<?= $slotKey === 'reading' ? '/?route=novels' : ($slotKey === 'favorite' ? '/zhuiju' : '/duanju') ?>"></label>
                        <label>排序<input name="recommend_<?= htmlspecialchars($slotKey) ?>_sort" type="number" value="<?= (array_search($slotKey, array_keys($appRecommendSlotLabels), true) + 1) * 10 ?>"></label>
                    <?php endforeach; ?>
                </div>
                <p><strong>任务配置</strong></p>
                <div class="form-grid">
                    <?php foreach ($appTaskLabels as $taskKey => $taskLabel): ?>
                        <label><span><input type="checkbox" name="task_<?= htmlspecialchars($taskKey) ?>_enabled" value="1" <?= $taskKey === 'share' ? '' : 'checked' ?>> <?= htmlspecialchars($taskLabel) ?></span></label>
                        <label><?= htmlspecialchars($taskLabel) ?>名称<input name="task_<?= htmlspecialchars($taskKey) ?>_name" value="<?= htmlspecialchars($taskLabel) ?>"></label>
                        <label>奖励K币<input name="task_<?= htmlspecialchars($taskKey) ?>_reward_coins" type="number" value="<?= $taskKey === 'add_desktop' ? 30 : ($taskKey === 'register' ? 20 : 10) ?>"></label>
                        <label>每日上限<input name="task_<?= htmlspecialchars($taskKey) ?>_daily_limit" type="number" value="<?= $taskKey === 'watch' ? 3 : 1 ?>"></label>
                    <?php endforeach; ?>
                </div>
                <p><strong>用户阶梯</strong></p>
                <div class="form-grid">
                    <?php foreach ($appUserTierLabels as $tierKey => $tierLabel): ?>
                        <label><span><input type="checkbox" name="tier_<?= htmlspecialchars($tierKey) ?>_enabled" value="1" checked> <?= htmlspecialchars($tierLabel) ?></span></label>
                        <label><?= htmlspecialchars($tierLabel) ?>名称<input name="tier_<?= htmlspecialchars($tierKey) ?>_name" value="<?= htmlspecialchars($tierLabel) ?>"></label>
                        <label>最小付费订单<input name="tier_<?= htmlspecialchars($tierKey) ?>_min_paid_orders" type="number" min="0" value="<?= $tierKey === 'paid' ? 1 : 0 ?>"></label>
                        <label>最大付费订单<input name="tier_<?= htmlspecialchars($tierKey) ?>_max_paid_orders" type="number" min="0" value="0"></label>
                        <label>注册天数内<input name="tier_<?= htmlspecialchars($tierKey) ?>_registered_within_days" type="number" min="0" value="<?= $tierKey === 'new' ? 3 : 0 ?>"></label>
                        <label><span><input type="checkbox" name="tier_<?= htmlspecialchars($tierKey) ?>_membership_required" value="1" <?= $tierKey === 'member' ? 'checked' : '' ?>> 需要会员</span></label>
                        <label>标签<input name="tier_<?= htmlspecialchars($tierKey) ?>_tag" value="<?= htmlspecialchars($tierLabel) ?>"></label>
                        <label>权益说明<input name="tier_<?= htmlspecialchars($tierKey) ?>_benefit_text" value="<?= $tierKey === 'member' ? '会员权益和留存' : ($tierKey === 'paid' ? '复购和全集解锁' : '首单转化重点人群') ?>"></label>
                        <label>排序<input name="tier_<?= htmlspecialchars($tierKey) ?>_sort" type="number" value="<?= (array_search($tierKey, array_keys($appUserTierLabels), true) + 1) * 10 ?>"></label>
                    <?php endforeach; ?>
                </div>
                <p><strong>客户端配置</strong></p>
                <div class="form-grid">
                    <label>当前版本<input name="client_version" value="1.0.0"></label>
                    <label>最低版本<input name="client_min_version" value="1.0.0"></label>
                    <label><span><input type="checkbox" name="client_force_update" value="1"> 强制更新</span></label>
                    <label>更新地址<input name="client_update_url" placeholder="https://..."></label>
                    <label>客户端模式
                        <select name="client_review_mode">
                            <?php foreach ($clientReviewModeLabels as $modeKey => $modeLabel): ?>
                                <option value="<?= htmlspecialchars($modeKey) ?>"><?= htmlspecialchars($modeLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>主题
                        <select name="client_theme">
                            <?php foreach ($clientThemeLabels as $themeKey => $themeLabel): ?>
                                <option value="<?= htmlspecialchars($themeKey) ?>"><?= htmlspecialchars($themeLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>客服链接<input name="client_customer_service_url" placeholder="/customer-service"></label>
                    <label>分享标题<input name="client_share_title" placeholder="分享给好友一起追剧"></label>
                    <label>分享图片<input name="client_share_image" placeholder="/assets/share.png"></label>
                    <label>启动公告<input name="client_launch_notice" placeholder="可选"></label>
                    <label>灰度比例%<input name="client_gray_release_percent" type="number" min="0" max="100" value="100"></label>
                    <label><span><input type="checkbox" name="client_show_ads" value="1" checked> 展示广告</span></label>
                    <label><span><input type="checkbox" name="client_show_rewards" value="1" checked> 展示任务奖励</span></label>
                    <label><span><input type="checkbox" name="client_show_vip" value="1" checked> 展示会员入口</span></label>
                </div>
                <p><strong>应用回传策略</strong></p>
                <div class="form-grid">
                    <label><span><input type="checkbox" name="callback_policy_enabled" value="1"> 启用应用独立回传</span></label>
                    <label><span><input type="checkbox" name="callback_policy_use_global_fallback" value="1" checked> 未启用时使用全局回传</span></label>
                    <label>平台名称<input name="callback_policy_platform" placeholder="巨量引擎 / 快手 / 广点通"></label>
                    <label>回传地址<input name="callback_policy_endpoint" placeholder="mock://success 或 https://..."></label>
                    <label>密钥<input name="callback_policy_secret" placeholder="应用回传签名密钥"></label>
                    <label>加桌事件名<input name="callback_policy_add_desktop_events" value="active" placeholder="active,add_to_desktop"></label>
                    <label>支付事件名<input name="callback_policy_paid_events" value="pay" placeholder="pay,purchase"></label>
                    <label><span><input type="checkbox" name="callback_policy_retry_failed" value="1" checked> 允许失败重试</span></label>
                    <label><span><input type="checkbox" name="callback_policy_fallback_time_match" value="1" checked> 启用时间匹配兜底</span></label>
                </div>
                <p><button class="btn" type="submit">新增应用</button></p>
            </form>

            <?php foreach ($apps as $app): ?>
                <?php
                $appKeyForView = (string) ($app['app_key'] ?? 'default');
                $appTypeForView = (string) ($app['type'] ?? 'h5');
                $appStatusForView = (string) ($app['status'] ?? 'active');
                $appRecommendSlots = (array) ($app['recommend_slots'] ?? []);
                $appTaskConfig = (array) ($app['task_config'] ?? []);
                $appUserTiers = (array) ($app['user_tiers'] ?? []);
                $appClientConfig = (array) ($app['client_config'] ?? []);
                $appCallbackPolicy = (array) ($app['callback_policy'] ?? []);
                ?>
                <form method="post" action="/jxdjadmin" class="row-card stack">
                    <input type="hidden" name="admin_action" value="save_app">
                    <input type="hidden" name="admin_section" value="apps">
                    <input type="hidden" name="id" value="<?= (int) ($app['id'] ?? 0) ?>">
                    <?= $csrfField() ?>
                    <p><strong><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?></strong> <span class="muted"><?= htmlspecialchars($appKeyForView) ?> · <?= htmlspecialchars($appTypeLabels[$appTypeForView] ?? $appTypeForView) ?> · <?= htmlspecialchars($appStatusLabels[$appStatusForView] ?? $appStatusForView) ?></span></p>
                    <div class="form-grid">
                        <label>应用名称<input name="name" value="<?= htmlspecialchars((string) ($app['name'] ?? '')) ?>"></label>
                        <label>应用标识<input name="app_key" value="<?= htmlspecialchars($appKeyForView) ?>"></label>
                        <label>应用类型
                            <select name="type">
                                <?php foreach ($appTypeLabels as $typeCode => $typeLabel): ?>
                                    <option value="<?= htmlspecialchars($typeCode) ?>" <?= $appTypeForView === $typeCode ? 'selected' : '' ?>><?= htmlspecialchars($typeLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>状态
                            <select name="status">
                                <?php foreach ($appStatusLabels as $statusCode => $statusLabel): ?>
                                    <option value="<?= htmlspecialchars($statusCode) ?>" <?= $appStatusForView === $statusCode ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>AppID<input name="app_id" value="<?= htmlspecialchars((string) ($app['app_id'] ?? '')) ?>"></label>
                        <label>AppSecret<input name="app_secret" value="<?= htmlspecialchars((string) ($app['app_secret'] ?? '')) ?>"></label>
                        <label>原始ID<input name="original_id" value="<?= htmlspecialchars((string) ($app['original_id'] ?? '')) ?>"></label>
                        <label>商品模板
                            <select name="product_template_key">
                                <?php foreach ($rechargeTemplates as $template): ?>
                                    <?php $templateKey = (string) ($template['app_key'] ?? 'default'); ?>
                                    <option value="<?= htmlspecialchars($templateKey) ?>" <?= (string) ($app['product_template_key'] ?? 'default') === $templateKey ? 'selected' : '' ?>><?= htmlspecialchars((string) ($template['name'] ?? '应用商品模板')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>支付路线
                            <select name="payment_route_id">
                                <?php foreach ($paymentRoutes as $route): ?>
                                    <?php $routeId = (string) ($route['id'] ?? ''); ?>
                                    <option value="<?= htmlspecialchars($routeId) ?>" <?= (string) ($app['payment_route_id'] ?? '') === $routeId ? 'selected' : '' ?>><?= htmlspecialchars((string) (($route['channel_name'] ?? '') ?: ($route['provider_name'] ?? '支付通道'))) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>首页模板
                            <select name="homepage_template">
                                <option value="mini" <?= (string) ($app['homepage_template'] ?? 'mini') === 'mini' ? 'selected' : '' ?>>小程序风格</option>
                                <option value="marketing" <?= (string) ($app['homepage_template'] ?? 'mini') === 'marketing' ? 'selected' : '' ?>>经典 H5</option>
                                <option value="diy" <?= (string) ($app['homepage_template'] ?? 'mini') === 'diy' ? 'selected' : '' ?>>DIY 首页</option>
                            </select>
                        </label>
                        <label>隐私协议<input name="privacy_url" value="<?= htmlspecialchars((string) ($app['privacy_url'] ?? '')) ?>"></label>
                        <label>用户协议<input name="agreement_url" value="<?= htmlspecialchars((string) ($app['agreement_url'] ?? '')) ?>"></label>
                        <label>回调地址<input name="callback_url" value="<?= htmlspecialchars((string) ($app['callback_url'] ?? '')) ?>"></label>
                        <label>排序<input name="sort" type="number" value="<?= htmlspecialchars((string) ($app['sort'] ?? 100)) ?>"></label>
                    </div>
                    <label>备注<input name="remark" value="<?= htmlspecialchars((string) ($app['remark'] ?? '')) ?>"></label>
                    <p><strong>推荐位配置</strong></p>
                    <div class="form-grid">
                        <?php foreach ($appRecommendSlotLabels as $slotKey => $slotLabel): ?>
                            <?php $slot = (array) ($appRecommendSlots[$slotKey] ?? []); ?>
                            <label><span><input type="checkbox" name="recommend_<?= htmlspecialchars($slotKey) ?>_enabled" value="1" <?= !empty($slot['enabled']) ? 'checked' : '' ?>> <?= htmlspecialchars($slotLabel) ?></span></label>
                            <label><?= htmlspecialchars($slotLabel) ?>名称<input name="recommend_<?= htmlspecialchars($slotKey) ?>_name" value="<?= htmlspecialchars((string) (($slot['name'] ?? '') ?: $slotLabel)) ?>"></label>
                            <label>内容类型
                                <select name="recommend_<?= htmlspecialchars($slotKey) ?>_content_type">
                                    <?php foreach (['drama' => '短剧', 'novel' => '小说', 'mixed' => '混合', 'url' => '外链'] as $contentType => $contentLabel): ?>
                                        <option value="<?= htmlspecialchars($contentType) ?>" <?= (string) ($slot['content_type'] ?? 'drama') === $contentType ? 'selected' : '' ?>><?= htmlspecialchars($contentLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>内容ID<input name="recommend_<?= htmlspecialchars($slotKey) ?>_content_id" type="number" value="<?= htmlspecialchars((string) ($slot['content_id'] ?? 0)) ?>"></label>
                            <label>跳转链接<input name="recommend_<?= htmlspecialchars($slotKey) ?>_link" value="<?= htmlspecialchars((string) ($slot['link'] ?? '')) ?>"></label>
                            <label>排序<input name="recommend_<?= htmlspecialchars($slotKey) ?>_sort" type="number" value="<?= htmlspecialchars((string) ($slot['sort'] ?? 100)) ?>"></label>
                        <?php endforeach; ?>
                    </div>
                    <p><strong>任务配置</strong></p>
                    <div class="form-grid">
                        <?php foreach ($appTaskLabels as $taskKey => $taskLabel): ?>
                            <?php $task = (array) ($appTaskConfig[$taskKey] ?? []); ?>
                            <label><span><input type="checkbox" name="task_<?= htmlspecialchars($taskKey) ?>_enabled" value="1" <?= !empty($task['enabled']) ? 'checked' : '' ?>> <?= htmlspecialchars($taskLabel) ?></span></label>
                            <label><?= htmlspecialchars($taskLabel) ?>名称<input name="task_<?= htmlspecialchars($taskKey) ?>_name" value="<?= htmlspecialchars((string) (($task['name'] ?? '') ?: $taskLabel)) ?>"></label>
                            <label>奖励K币<input name="task_<?= htmlspecialchars($taskKey) ?>_reward_coins" type="number" value="<?= htmlspecialchars((string) ($task['reward_coins'] ?? 0)) ?>"></label>
                            <label>每日上限<input name="task_<?= htmlspecialchars($taskKey) ?>_daily_limit" type="number" value="<?= htmlspecialchars((string) ($task['daily_limit'] ?? 0)) ?>"></label>
                        <?php endforeach; ?>
                    </div>
                    <p><strong>用户阶梯</strong></p>
                    <div class="form-grid">
                        <?php foreach ($appUserTierLabels as $tierKey => $tierLabel): ?>
                            <?php $tier = (array) ($appUserTiers[$tierKey] ?? []); ?>
                            <label><span><input type="checkbox" name="tier_<?= htmlspecialchars($tierKey) ?>_enabled" value="1" <?= !empty($tier['enabled']) ? 'checked' : '' ?>> <?= htmlspecialchars($tierLabel) ?></span></label>
                            <label><?= htmlspecialchars($tierLabel) ?>名称<input name="tier_<?= htmlspecialchars($tierKey) ?>_name" value="<?= htmlspecialchars((string) (($tier['name'] ?? '') ?: $tierLabel)) ?>"></label>
                            <label>最小付费订单<input name="tier_<?= htmlspecialchars($tierKey) ?>_min_paid_orders" type="number" min="0" value="<?= (int) ($tier['min_paid_orders'] ?? 0) ?>"></label>
                            <label>最大付费订单<input name="tier_<?= htmlspecialchars($tierKey) ?>_max_paid_orders" type="number" min="0" value="<?= (int) ($tier['max_paid_orders'] ?? 0) ?>"></label>
                            <label>注册天数内<input name="tier_<?= htmlspecialchars($tierKey) ?>_registered_within_days" type="number" min="0" value="<?= (int) ($tier['registered_within_days'] ?? ($tierKey === 'new' ? 3 : 0)) ?>"></label>
                            <label><span><input type="checkbox" name="tier_<?= htmlspecialchars($tierKey) ?>_membership_required" value="1" <?= !empty($tier['membership_required']) ? 'checked' : '' ?>> 需要会员</span></label>
                            <label>标签<input name="tier_<?= htmlspecialchars($tierKey) ?>_tag" value="<?= htmlspecialchars((string) (($tier['tag'] ?? '') ?: $tierLabel)) ?>"></label>
                            <label>权益说明<input name="tier_<?= htmlspecialchars($tierKey) ?>_benefit_text" value="<?= htmlspecialchars((string) ($tier['benefit_text'] ?? '')) ?>"></label>
                            <label>排序<input name="tier_<?= htmlspecialchars($tierKey) ?>_sort" type="number" value="<?= (int) ($tier['sort'] ?? 100) ?>"></label>
                        <?php endforeach; ?>
                    </div>
                    <p><strong>客户端配置</strong></p>
                    <div class="form-grid">
                        <label>当前版本<input name="client_version" value="<?= htmlspecialchars((string) ($appClientConfig['version'] ?? '1.0.0')) ?>"></label>
                        <label>最低版本<input name="client_min_version" value="<?= htmlspecialchars((string) ($appClientConfig['min_version'] ?? '1.0.0')) ?>"></label>
                        <label><span><input type="checkbox" name="client_force_update" value="1" <?= !empty($appClientConfig['force_update']) ? 'checked' : '' ?>> 强制更新</span></label>
                        <label>更新地址<input name="client_update_url" value="<?= htmlspecialchars((string) ($appClientConfig['update_url'] ?? '')) ?>"></label>
                        <label>客户端模式
                            <select name="client_review_mode">
                                <?php foreach ($clientReviewModeLabels as $modeKey => $modeLabel): ?>
                                    <option value="<?= htmlspecialchars($modeKey) ?>" <?= (string) ($appClientConfig['review_mode'] ?? 'normal') === $modeKey ? 'selected' : '' ?>><?= htmlspecialchars($modeLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>主题
                            <select name="client_theme">
                                <?php foreach ($clientThemeLabels as $themeKey => $themeLabel): ?>
                                    <option value="<?= htmlspecialchars($themeKey) ?>" <?= (string) ($appClientConfig['theme'] ?? 'default') === $themeKey ? 'selected' : '' ?>><?= htmlspecialchars($themeLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>客服链接<input name="client_customer_service_url" value="<?= htmlspecialchars((string) ($appClientConfig['customer_service_url'] ?? '')) ?>"></label>
                        <label>分享标题<input name="client_share_title" value="<?= htmlspecialchars((string) ($appClientConfig['share_title'] ?? '')) ?>"></label>
                        <label>分享图片<input name="client_share_image" value="<?= htmlspecialchars((string) ($appClientConfig['share_image'] ?? '')) ?>"></label>
                        <label>启动公告<input name="client_launch_notice" value="<?= htmlspecialchars((string) ($appClientConfig['launch_notice'] ?? '')) ?>"></label>
                        <label>灰度比例%<input name="client_gray_release_percent" type="number" min="0" max="100" value="<?= (int) ($appClientConfig['gray_release_percent'] ?? 100) ?>"></label>
                        <label><span><input type="checkbox" name="client_show_ads" value="1" <?= !array_key_exists('show_ads', $appClientConfig) || !empty($appClientConfig['show_ads']) ? 'checked' : '' ?>> 展示广告</span></label>
                        <label><span><input type="checkbox" name="client_show_rewards" value="1" <?= !array_key_exists('show_rewards', $appClientConfig) || !empty($appClientConfig['show_rewards']) ? 'checked' : '' ?>> 展示任务奖励</span></label>
                        <label><span><input type="checkbox" name="client_show_vip" value="1" <?= !array_key_exists('show_vip', $appClientConfig) || !empty($appClientConfig['show_vip']) ? 'checked' : '' ?>> 展示会员入口</span></label>
                    </div>
                    <p><strong>应用回传策略</strong></p>
                    <div class="form-grid">
                        <label><span><input type="checkbox" name="callback_policy_enabled" value="1" <?= !empty($appCallbackPolicy['enabled']) ? 'checked' : '' ?>> 启用应用独立回传</span></label>
                        <label><span><input type="checkbox" name="callback_policy_use_global_fallback" value="1" <?= !empty($appCallbackPolicy['use_global_fallback']) ? 'checked' : '' ?>> 未启用时使用全局回传</span></label>
                        <label>平台名称<input name="callback_policy_platform" value="<?= htmlspecialchars((string) ($appCallbackPolicy['platform'] ?? '')) ?>" placeholder="巨量引擎 / 快手 / 广点通"></label>
                        <label>回传地址<input name="callback_policy_endpoint" value="<?= htmlspecialchars((string) ($appCallbackPolicy['endpoint'] ?? '')) ?>" placeholder="mock://success 或 https://..."></label>
                        <label>密钥<input name="callback_policy_secret" value="<?= htmlspecialchars((string) ($appCallbackPolicy['secret'] ?? '')) ?>" placeholder="应用回传签名密钥"></label>
                        <label>加桌事件名<input name="callback_policy_add_desktop_events" value="<?= htmlspecialchars(implode(',', (array) ($appCallbackPolicy['add_desktop_events'] ?? ['active']))) ?>"></label>
                        <label>支付事件名<input name="callback_policy_paid_events" value="<?= htmlspecialchars(implode(',', (array) ($appCallbackPolicy['paid_events'] ?? ['pay']))) ?>"></label>
                        <label><span><input type="checkbox" name="callback_policy_retry_failed" value="1" <?= !empty($appCallbackPolicy['retry_failed']) ? 'checked' : '' ?>> 允许失败重试</span></label>
                        <label><span><input type="checkbox" name="callback_policy_fallback_time_match" value="1" <?= !empty($appCallbackPolicy['fallback_time_match']) ? 'checked' : '' ?>> 启用时间匹配兜底</span></label>
                    </div>
                    <p><button class="btn" type="submit">保存应用</button></p>
                </form>
            <?php endforeach; ?>
            <div class="row-card stack">
                <div class="section-title admin-section-title">
                    <div>
                        <span class="eyebrow">客户端配置</span>
                        <h2>配置下发命中日志</h2>
                    </div>
                    <span class="muted">最近 <?= number_format(count($appConfigDeliveryLogs)) ?> 条聚合记录</span>
                </div>
                <div class="order-table">
                    <div class="row-card order-row-head">
                        <span>应用/用户</span>
                        <span>模式/版本</span>
                        <span>灰度</span>
                        <span>命中</span>
                    </div>
                    <?php if (empty($appConfigDeliveryLogs)): ?>
                        <p class="muted">暂无客户端配置下发日志。</p>
                    <?php endif; ?>
                    <?php foreach (array_slice($appConfigDeliveryLogs, 0, 12) as $log): ?>
                        <?php
                            $deliveryMode = (string) ($log['review_mode'] ?? 'normal');
                            $deliveryModeLabel = $clientReviewModeLabels[$deliveryMode] ?? $deliveryMode;
                        ?>
                        <div class="row-card order-row">
                            <span>
                                <strong><?= htmlspecialchars((string) ($log['app_name'] ?? '默认应用')) ?></strong>
                                <em><?= htmlspecialchars((string) ($log['app_key'] ?? 'default')) ?> · 用户 <?= (int) ($log['user_id'] ?? 0) ?> · <?= htmlspecialchars((string) (($log['user_tier'] ?? '') ?: '-')) ?></em>
                            </span>
                            <span><?= htmlspecialchars($deliveryModeLabel) ?><em>版本 <?= htmlspecialchars((string) (($log['version'] ?? '') ?: '-')) ?> · 最低 <?= htmlspecialchars((string) (($log['min_version'] ?? '') ?: '-')) ?></em></span>
                            <span>
                                <span class="pill <?= !empty($log['gray_hit']) ? 'green' : 'orange' ?>"><?= !empty($log['gray_hit']) ? '命中' : '未命中' ?></span>
                                <em><?= (int) ($log['gray_percent'] ?? 100) ?>% · 桶 <?= (int) ($log['gray_bucket'] ?? 0) ?></em>
                            </span>
                            <span><?= number_format((int) ($log['hit_count'] ?? 1)) ?> 次<em><?= htmlspecialchars((string) ($log['last_seen_at'] ?? '')) ?></em></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="panel admin-section payment-subsection <?= $activeAdminSection === 'channel-polling' ? 'is-active' : '' ?>" id="admin-section-channel-polling" data-admin-section="channel-polling" data-admin-primary="finance">
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">支付接口</span>
                    <h2>支付通道轮询</h2>
                </div>
                <span class="muted"><?= htmlspecialchars($paymentRoutePolicyLabels[$paymentRoutePolicyMode] ?? '默认优先') ?> · <?= number_format((int) ($paymentRoutePolicy['success_window_days'] ?? 7)) ?> 天窗口</span>
            </div>
            <div class="payment-rule-grid">
                <div class="system-item"><strong><?= number_format($enabledPaymentRouteCount) ?> 条</strong><span>启用通道</span></div>
                <div class="system-item"><strong>默认路线</strong><span><?= htmlspecialchars((string) ($defaultPaymentRoute['channel_name'] ?? '默认通道')) ?></span></div>
                <div class="system-item"><strong><?= htmlspecialchars($paymentRoutePolicyLabels[$paymentRoutePolicyMode] ?? '默认优先') ?></strong><span>当前路由策略</span></div>
            </div>
            <form method="post" action="/jxdjadmin#channel-polling" class="row-card stack">
                <input type="hidden" name="admin_action" value="save_payment_route_policy">
                <input type="hidden" name="admin_section" value="channel-polling">
                <?= $csrfField() ?>
                <p><strong>路由策略</strong></p>
                <div class="form-grid">
                    <label>策略模式
                        <select name="route_policy_mode">
                            <?php foreach ($paymentRoutePolicyLabels as $modeKey => $modeLabel): ?>
                                <option value="<?= htmlspecialchars($modeKey) ?>" <?= $paymentRoutePolicyMode === $modeKey ? 'selected' : '' ?>><?= htmlspecialchars($modeLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>成功率统计天数<input name="route_policy_success_window_days" type="number" min="1" max="90" value="<?= (int) ($paymentRoutePolicy['success_window_days'] ?? 7) ?>"></label>
                    <label>最小样本订单<input name="route_policy_min_sample_orders" type="number" min="0" max="1000" value="<?= (int) ($paymentRoutePolicy['min_sample_orders'] ?? 3) ?>"></label>
                </div>
                <p class="muted">下单会先按通道启用状态、金额上下限、开放时段、日收款/订单上限和用户频控筛选，再按策略选择通道；用户或应用明确指定的通道在未超限时优先生效。</p>
                <p><button class="primary" type="submit">保存轮询策略</button></p>
            </form>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>通道</th>
                        <th>状态</th>
                        <th>成功率</th>
                        <th>订单数</th>
                        <th>今日收款</th>
                        <th>限制</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($paymentChannelRows as $row): ?>
                        <?php $route = (array) ($row['route'] ?? []); ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string) ($route['channel_name'] ?? '支付通道')) ?></strong><br><span class="muted"><?= htmlspecialchars((string) ($route['payment_method_name'] ?? $route['payment_method'] ?? '支付方式')) ?></span></td>
                            <td><?= htmlspecialchars((string) ($row['status_label'] ?? '未知')) ?></td>
                            <td><?= htmlspecialchars(number_format((float) ($row['success_rate'] ?? 0), 1)) ?>%</td>
                            <td><?= number_format((int) ($row['order_count'] ?? 0)) ?></td>
                            <td><?= htmlspecialchars($money((float) ($row['today_amount'] ?? 0))) ?></td>
                            <td class="muted">金额 <?= htmlspecialchars((string) ($route['min_amount'] ?? '0')) ?>-<?= htmlspecialchars((string) ($route['max_amount'] ?? '0')) ?> · 日额 <?= htmlspecialchars((string) ($route['daily_amount_limit'] ?? '0')) ?> · 日单 <?= htmlspecialchars((string) ($route['daily_order_limit'] ?? '0')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel admin-section payment-subsection <?= $activeAdminSection === 'mini-program' ? 'is-active' : '' ?>" id="admin-section-mini-program" data-admin-section="mini-program" data-admin-primary="finance">
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">微信小程序</span>
                    <h2>公众号小程序</h2>
                </div>
                <span class="muted"><?= number_format(count($miniProgramConfigs)) ?> 个配置 · <?= number_format(count($miniProgramSyncTasks)) ?> 个同步任务</span>
            </div>
            <div class="payment-rule-grid">
                <div class="system-item"><strong><?= number_format(count(array_filter($miniProgramConfigs, static fn (array $config): bool => (string) ($config['status'] ?? '') === 'active'))) ?> 个</strong><span>启用配置</span></div>
                <div class="system-item"><strong><?= number_format(array_sum(array_map(static fn (array $task): int => (int) ($task['item_count'] ?? 0), $miniProgramSyncTasks))) ?> 条</strong><span>生成内容清单</span></div>
                <div class="system-item"><strong><?= number_format((int) ($miniProgramTaskStatusCounts['uploaded'] ?? 0)) ?> 个</strong><span>已上传任务</span></div>
                <div class="system-item"><strong><?= number_format((int) ($miniProgramTaskStatusCounts['review_submitted'] ?? 0)) ?> 个</strong><span>审核中任务</span></div>
                <div class="system-item"><strong><?= number_format((int) ($miniProgramTaskStatusCounts['released'] ?? 0)) ?> 个</strong><span>已发布任务</span></div>
                <div class="system-item"><strong><?= number_format((int) ($miniProgramTaskStatusCounts['failed'] ?? 0)) ?> 个</strong><span>失败待重试</span></div>
            </div>

            <form method="post" action="/jxdjadmin#mini-program" class="row-card stack">
                <input type="hidden" name="admin_action" value="save_mini_program_config">
                <input type="hidden" name="admin_section" value="mini-program">
                <?= $csrfField() ?>
                <p><strong>新增小程序配置</strong></p>
                <div class="form-grid">
                    <label>配置名称<input name="name" placeholder="例如：精秀短剧小程序"></label>
                    <label>绑定应用
                        <select name="app_key">
                            <?php foreach ($apps as $app): ?>
                                <option value="<?= htmlspecialchars((string) ($app['app_key'] ?? 'default')) ?>"><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars((string) ($app['app_key'] ?? 'default')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>状态
                        <select name="status">
                            <?php foreach ($miniProgramStatusLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>小程序 AppID<input name="mp_app_id" placeholder="wx..."></label>
                    <label>小程序 AppSecret<input name="mp_app_secret" placeholder="用于后续接口上传/获取 token"></label>
                    <label>原始ID<input name="original_id" placeholder="gh_..."></label>
                    <label>商户号<input name="mch_id" placeholder="微信支付商户号，可选"></label>
                    <label>服务器域名<input name="server_domain" placeholder="https://example.com"></label>
                    <label>上传令牌<input name="upload_token" placeholder="CI/API 上传令牌，可选"></label>
                    <label>接口基地址<input name="api_base_url" value="https://api.weixin.qq.com" placeholder="https://api.weixin.qq.com"></label>
                    <label>上传方式
                        <select name="upload_mode">
                            <?php foreach ($miniProgramUploadModeLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>内容范围
                        <select name="content_scope">
                            <?php foreach ($miniProgramScopeLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>短剧默认分类<input name="default_drama_category" value="短剧"></label>
                    <label>小说默认分类<input name="default_novel_category" value="小说"></label>
                    <label>隐私协议<input name="privacy_url" placeholder="/privacy.html"></label>
                    <label>用户协议<input name="agreement_url" placeholder="/agreement.html"></label>
                    <label>项目名称<input name="project_name" value="精秀短剧小程序"></label>
                    <label>编译类型<input name="compile_type" value="miniprogram"></label>
                    <label>小程序根目录<input name="miniprogram_root" value="miniprogram/"></label>
                    <label><span><input type="checkbox" name="setting_url_check" value="1"> 关闭 URL 检查</span></label>
                    <label><span><input type="checkbox" name="setting_es6" value="1" checked> ES6 转换</span></label>
                    <label><span><input type="checkbox" name="setting_postcss" value="1" checked> PostCSS</span></label>
                    <label><span><input type="checkbox" name="setting_minified" value="1" checked> 压缩代码</span></label>
                </div>
                <label>页面路径
                    <textarea name="pages_text" rows="3">pages/index/index
pages/drama/detail
pages/novel/detail</textarea>
                </label>
                <label>配置文件 JSON
                    <textarea name="config_json_text" rows="4" placeholder="可选：直接粘贴 project.config.json 的关键字段"></textarea>
                </label>
                <label>备注<input name="remark" placeholder="主体、审核账号、类目或上传说明"></label>
                <p><button class="btn" type="submit">保存小程序配置</button></p>
            </form>

            <?php foreach ($miniProgramConfigs as $config): ?>
                <?php
                    $configId = (int) ($config['id'] ?? 0);
                    $configJsonText = json_encode((array) ($config['config_json'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}';
                    $tokenStatus = (string) ($config['access_token_status'] ?? '');
                    $tokenExpiresAt = (string) ($config['access_token_expires_at'] ?? '');
                ?>
                <form method="post" action="/jxdjadmin#mini-program" class="row-card stack">
                    <input type="hidden" name="admin_action" value="save_mini_program_config">
                    <input type="hidden" name="admin_section" value="mini-program">
                    <input type="hidden" name="mini_program_config_id" value="<?= $configId ?>">
                    <?= $csrfField() ?>
                    <p><strong><?= htmlspecialchars((string) ($config['name'] ?? '小程序配置')) ?></strong> <span class="muted"><?= htmlspecialchars((string) ($config['app_key'] ?? 'default')) ?> · <?= htmlspecialchars($miniProgramStatusLabels[(string) ($config['status'] ?? 'draft')] ?? '草稿') ?> · <?= htmlspecialchars($miniProgramUploadModeLabels[(string) ($config['upload_mode'] ?? 'manual')] ?? '手动上传') ?></span></p>
                    <p class="muted">access_token <?= htmlspecialchars($miniProgramTokenStatusLabels[$tokenStatus] ?? $tokenStatus) ?><?= $tokenExpiresAt !== '' ? ' · 到期 ' . htmlspecialchars($tokenExpiresAt) : '' ?><?= !empty($config['access_token_message']) ? ' · ' . htmlspecialchars((string) $config['access_token_message']) : '' ?></p>
                    <div class="form-grid">
                        <label>配置名称<input name="name" value="<?= htmlspecialchars((string) ($config['name'] ?? '')) ?>"></label>
                        <label>绑定应用<input name="app_key" value="<?= htmlspecialchars((string) ($config['app_key'] ?? 'default')) ?>"></label>
                        <label>状态
                            <select name="status">
                                <?php foreach ($miniProgramStatusLabels as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($config['status'] ?? 'draft') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>小程序 AppID<input name="mp_app_id" value="<?= htmlspecialchars((string) ($config['mp_app_id'] ?? '')) ?>"></label>
                        <label>小程序 AppSecret<input name="mp_app_secret" value="<?= htmlspecialchars((string) ($config['mp_app_secret'] ?? '')) ?>"></label>
                        <label>原始ID<input name="original_id" value="<?= htmlspecialchars((string) ($config['original_id'] ?? '')) ?>"></label>
                        <label>商户号<input name="mch_id" value="<?= htmlspecialchars((string) ($config['mch_id'] ?? '')) ?>"></label>
                        <label>服务器域名<input name="server_domain" value="<?= htmlspecialchars((string) ($config['server_domain'] ?? '')) ?>"></label>
                        <label>上传令牌<input name="upload_token" value="<?= htmlspecialchars((string) ($config['upload_token'] ?? '')) ?>"></label>
                        <label>接口基地址<input name="api_base_url" value="<?= htmlspecialchars((string) (($config['api_base_url'] ?? '') ?: 'https://api.weixin.qq.com')) ?>"></label>
                        <label>上传方式
                            <select name="upload_mode">
                                <?php foreach ($miniProgramUploadModeLabels as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($config['upload_mode'] ?? 'manual') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>内容范围
                            <select name="content_scope">
                                <?php foreach ($miniProgramScopeLabels as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($config['content_scope'] ?? 'all') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>短剧默认分类<input name="default_drama_category" value="<?= htmlspecialchars((string) ($config['default_drama_category'] ?? '短剧')) ?>"></label>
                        <label>小说默认分类<input name="default_novel_category" value="<?= htmlspecialchars((string) ($config['default_novel_category'] ?? '小说')) ?>"></label>
                        <label>隐私协议<input name="privacy_url" value="<?= htmlspecialchars((string) ($config['privacy_url'] ?? '')) ?>"></label>
                        <label>用户协议<input name="agreement_url" value="<?= htmlspecialchars((string) ($config['agreement_url'] ?? '')) ?>"></label>
                    </div>
                    <label>配置文件 JSON<textarea name="config_json_text" rows="4"><?= htmlspecialchars($configJsonText) ?></textarea></label>
                    <label>备注<input name="remark" value="<?= htmlspecialchars((string) ($config['remark'] ?? '')) ?>"></label>
                    <p><button class="btn ghost" type="submit">保存配置</button></p>
                </form>

                <form method="post" action="/jxdjadmin#mini-program" class="row-card stack">
                    <input type="hidden" name="admin_action" value="refresh_mini_program_access_token">
                    <input type="hidden" name="admin_section" value="mini-program">
                    <input type="hidden" name="mini_program_config_id" value="<?= $configId ?>">
                    <?= $csrfField() ?>
                    <p><strong>刷新 access_token：<?= htmlspecialchars((string) ($config['name'] ?? '小程序配置')) ?></strong></p>
                    <p class="muted">接口上传模式会自动刷新；这里可用于上线前检查 AppID/AppSecret 是否可用。</p>
                    <button class="btn ghost" type="submit">刷新 access_token</button>
                </form>

                <form method="post" action="/jxdjadmin#mini-program" class="row-card stack">
                    <input type="hidden" name="admin_action" value="create_mini_program_sync_task">
                    <input type="hidden" name="admin_section" value="mini-program">
                    <input type="hidden" name="mini_program_config_id" value="<?= $configId ?>">
                    <?= $csrfField() ?>
                    <p><strong>生成上传清单：<?= htmlspecialchars((string) ($config['name'] ?? '小程序配置')) ?></strong></p>
                    <div class="form-grid">
                        <label>同步内容
                            <select name="content_type">
                                <?php foreach ($miniProgramScopeLabels as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value === 'all' ? 'mixed' : $value) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>指定内容ID<input name="content_ids" placeholder="可空；多个 ID 用逗号分隔"></label>
                    </div>
                    <p><button class="btn" type="submit">生成短剧/小说上传清单</button></p>
                </form>
            <?php endforeach; ?>

            <div class="order-table">
                <div class="row-card order-row-head">
                    <span>任务/版本</span>
                    <span>发布状态</span>
                    <span>清单/快照</span>
                    <span>操作</span>
                </div>
                <?php if (empty($miniProgramSyncTasks)): ?>
                    <p class="muted">暂无同步任务。保存小程序配置后，可生成短剧/小说上传清单。</p>
                <?php endif; ?>
                <?php foreach (array_slice($miniProgramSyncTasks, 0, 30) as $task): ?>
                    <?php
                        $manifest = (array) ($task['manifest'] ?? []);
                        $manifestItems = array_slice((array) ($manifest['items'] ?? []), 0, 4);
                        $taskStatus = (string) ($task['status'] ?? 'generated');
                        $taskStatusClass = match ($taskStatus) {
                            'released', 'review_passed' => 'green',
                            'uploaded', 'review_submitted' => 'blue',
                            'failed', 'review_rejected' => 'orange',
                            default => '',
                        };
                        $taskId = (int) ($task['id'] ?? 0);
                        $taskVersion = (string) (($task['version'] ?? '') ?: date('Y.m.d.Hi'));
                        $taskUploadMode = (string) (($task['upload_mode'] ?? '') ?: ($manifest['config']['upload_mode'] ?? 'manual'));
                        $requestSnapshot = (array) ($task['request_snapshot'] ?? []);
                        $responseSnapshot = (array) ($task['response_snapshot'] ?? []);
                        $requestSnapshotText = !empty($requestSnapshot) ? (json_encode($requestSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}') : '';
                        $responseSnapshotText = !empty($responseSnapshot) ? (json_encode($responseSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}') : '';
                        $taskLogs = array_slice(array_reverse(array_values((array) ($task['action_logs'] ?? []))), 0, 4);
                        $canUpload = in_array($taskStatus, ['generated', 'failed'], true);
                        $canSubmitReview = in_array($taskStatus, ['uploaded', 'failed'], true);
                        $canQueryReview = in_array($taskStatus, ['review_submitted', 'failed'], true);
                        $canRelease = in_array($taskStatus, ['review_passed', 'failed'], true);
                    ?>
                    <div class="row-card order-row">
                        <span>
                            <strong>#<?= number_format($taskId) ?> · v<?= htmlspecialchars($taskVersion) ?></strong>
                            <em><?= htmlspecialchars($miniProgramTaskStatusLabels[$taskStatus] ?? $taskStatus) ?> · <?= htmlspecialchars($miniProgramUploadModeLabels[$taskUploadMode] ?? $taskUploadMode) ?></em>
                            <em>应用 <?= htmlspecialchars((string) ($task['app_key'] ?? 'default')) ?> · 配置 #<?= number_format((int) ($task['config_id'] ?? 0)) ?></em>
                            <em>创建 <?= htmlspecialchars((string) ($task['created_at'] ?? '')) ?> · <?= htmlspecialchars((string) ($task['created_by_admin_name'] ?? '')) ?></em>
                        </span>
                        <span>
                            <span class="pill <?= htmlspecialchars($taskStatusClass) ?>"><?= htmlspecialchars($miniProgramTaskStatusLabels[$taskStatus] ?? $taskStatus) ?></span>
                            <em><?= htmlspecialchars((string) ($task['message'] ?? '')) ?></em>
                            <em>小程序 <?= htmlspecialchars((string) ($manifest['mp_app_id'] ?? '-')) ?> · 范围 <?= htmlspecialchars($miniProgramTaskScopeLabels[(string) ($task['content_type'] ?? 'mixed')] ?? (string) ($task['content_type'] ?? 'mixed')) ?></em>
                            <?php if ((string) ($task['upload_job_id'] ?? '') !== ''): ?><em>上传 Job：<?= htmlspecialchars((string) ($task['upload_job_id'] ?? '')) ?> · <?= htmlspecialchars((string) ($task['uploaded_at'] ?? '')) ?></em><?php endif; ?>
                            <?php if ((string) ($task['review_id'] ?? '') !== ''): ?><em>审核单：<?= htmlspecialchars((string) ($task['review_id'] ?? '')) ?> · <?= htmlspecialchars((string) (($task['review_checked_at'] ?? '') ?: ($task['review_submitted_at'] ?? ''))) ?></em><?php endif; ?>
                            <?php if ((string) ($task['release_version'] ?? '') !== ''): ?><em>发布版本：<?= htmlspecialchars((string) ($task['release_version'] ?? '')) ?> · <?= htmlspecialchars((string) ($task['released_at'] ?? '')) ?></em><?php endif; ?>
                            <?php if (!empty($task['platform_code']) || !empty($task['platform_message'])): ?><em>平台返回 <?= htmlspecialchars((string) (($task['platform_code'] ?? '') ?: '-')) ?> · <?= htmlspecialchars((string) (($task['platform_message'] ?? '') ?: '-')) ?></em><?php endif; ?>
                            <?php if (!empty($task['error_category'])): ?><em>错误分类 <?= htmlspecialchars($miniProgramTaskErrorLabels[(string) ($task['error_category'] ?? '')] ?? (string) ($task['error_category'] ?? '')) ?></em><?php endif; ?>
                            <?php if (!empty($task['next_retry_at']) || !empty($task['retry_blocked_reason'])): ?><em>下次重试 <?= htmlspecialchars((string) (($task['next_retry_at'] ?? '') ?: '-')) ?><?= !empty($task['retry_blocked_reason']) ? ' · ' . htmlspecialchars((string) $task['retry_blocked_reason']) : '' ?></em><?php endif; ?>
                            <?php if ((string) ($task['last_error'] ?? '') !== ''): ?><em>错误：<?= htmlspecialchars((string) ($task['last_error'] ?? '')) ?></em><?php endif; ?>
                            <em>重试 <?= number_format((int) ($task['retry_count'] ?? 0)) ?> 次 · 最近动作 <?= htmlspecialchars($miniProgramTaskActionLabels[(string) ($task['last_action'] ?? '')] ?? (string) ($task['last_action'] ?? '-')) ?> <?= htmlspecialchars((string) ($task['last_action_at'] ?? '')) ?></em>
                            <?php foreach ($taskLogs as $log): ?>
                                <em><?= htmlspecialchars((string) ($log['created_at'] ?? '')) ?> · <?= htmlspecialchars($miniProgramTaskActionLabels[(string) ($log['action'] ?? '')] ?? (string) ($log['action'] ?? '动作')) ?> · <?= htmlspecialchars((string) ($log['message'] ?? '')) ?></em>
                            <?php endforeach; ?>
                        </span>
                        <span>
                            <?= number_format((int) ($task['item_count'] ?? 0)) ?> 条内容
                            <?php foreach ($manifestItems as $item): ?>
                                <em><?= htmlspecialchars((string) (($item['type'] ?? '') === 'novel' ? '小说' : '短剧')) ?> #<?= number_format((int) ($item['id'] ?? 0)) ?> · <?= htmlspecialchars((string) ($item['title'] ?? '')) ?></em>
                            <?php endforeach; ?>
                            <em>生成时间 <?= htmlspecialchars((string) ($manifest['generated_at'] ?? '')) ?></em>
                            <?php if ($requestSnapshotText !== ''): ?><em>请求：<?= htmlspecialchars($requestSnapshotText) ?></em><?php endif; ?>
                            <?php if ($responseSnapshotText !== ''): ?><em>响应：<?= htmlspecialchars($responseSnapshotText) ?></em><?php endif; ?>
                        </span>
                        <span>
                            <?php if ($canUpload): ?>
                                <form method="post" action="/jxdjadmin#mini-program" class="inline-form">
                                    <input type="hidden" name="admin_action" value="run_mini_program_task_action">
                                    <input type="hidden" name="admin_section" value="mini-program">
                                    <input type="hidden" name="mini_program_task_id" value="<?= $taskId ?>">
                                    <input type="hidden" name="mini_program_action" value="upload">
                                    <?= $csrfField() ?>
                                    <input name="version" value="<?= htmlspecialchars($taskVersion) ?>" placeholder="版本号">
                                    <input name="description" value="短剧/小说内容更新" placeholder="上传说明">
                                    <button class="btn ghost" type="submit">上传代码</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($canSubmitReview): ?>
                                <form method="post" action="/jxdjadmin#mini-program" class="inline-form">
                                    <input type="hidden" name="admin_action" value="run_mini_program_task_action">
                                    <input type="hidden" name="admin_section" value="mini-program">
                                    <input type="hidden" name="mini_program_task_id" value="<?= $taskId ?>">
                                    <input type="hidden" name="mini_program_action" value="submit_review">
                                    <?= $csrfField() ?>
                                    <input name="description" value="短剧/小说内容更新" placeholder="提审说明">
                                    <button class="btn ghost" type="submit">提交审核</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($canQueryReview): ?>
                                <form method="post" action="/jxdjadmin#mini-program" class="inline-form">
                                    <input type="hidden" name="admin_action" value="run_mini_program_task_action">
                                    <input type="hidden" name="admin_section" value="mini-program">
                                    <input type="hidden" name="mini_program_task_id" value="<?= $taskId ?>">
                                    <input type="hidden" name="mini_program_action" value="query_review">
                                    <?= $csrfField() ?>
                                    <select name="review_status">
                                        <option value="pass">通过</option>
                                        <option value="pending">审核中</option>
                                        <option value="reject">驳回</option>
                                    </select>
                                    <input name="description" placeholder="驳回原因/备注">
                                    <button class="btn ghost" type="submit">查询审核</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($canRelease): ?>
                                <form method="post" action="/jxdjadmin#mini-program" class="inline-form">
                                    <input type="hidden" name="admin_action" value="run_mini_program_task_action">
                                    <input type="hidden" name="admin_section" value="mini-program">
                                    <input type="hidden" name="mini_program_task_id" value="<?= $taskId ?>">
                                    <input type="hidden" name="mini_program_action" value="release">
                                    <?= $csrfField() ?>
                                    <input name="version" value="<?= htmlspecialchars($taskVersion) ?>" placeholder="发布版本">
                                    <button class="btn ghost" type="submit">发布上线</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($taskStatus === 'failed'): ?>
                                <form method="post" action="/jxdjadmin#mini-program" class="inline-form">
                                    <input type="hidden" name="admin_action" value="run_mini_program_task_action">
                                    <input type="hidden" name="admin_section" value="mini-program">
                                    <input type="hidden" name="mini_program_task_id" value="<?= $taskId ?>">
                                    <input type="hidden" name="mini_program_action" value="retry">
                                    <?= $csrfField() ?>
                                    <button class="btn ghost" type="submit">失败重试</button>
                                </form>
                            <?php endif; ?>
                            <?php if (!$canUpload && !$canSubmitReview && !$canQueryReview && !$canRelease && $taskStatus !== 'failed'): ?>
                                <em>当前状态暂无待执行动作。</em>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel admin-section <?= $activeAdminSection === 'works-list' ? 'is-active' : '' ?>" id="admin-section-works-list" data-admin-section="works-list" data-admin-primary="works">
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">作品管理</span>
                    <h2>作品列表</h2>
                </div>
                <span class="muted"><?= number_format(count($filteredWorksRows)) ?> / <?= number_format(count($worksRows)) ?> 个作品 · 短剧/小说/壁纸/H5统一管理</span>
            </div>
            <form class="order-filter-bar" method="get" action="/jxdjadmin">
                <input type="hidden" name="admin_section" value="works-list">
                <label>类型
                    <select name="works_type">
                        <option value="all">全部</option>
                        <?php foreach ($workTypeLabels as $typeValue => $typeLabel): ?>
                            <option value="<?= htmlspecialchars($typeValue) ?>" <?= $worksFilters['type'] === $typeValue ? 'selected' : '' ?>><?= htmlspecialchars($typeLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>分类
                    <select name="works_category">
                        <option value="">全部</option>
                        <?php foreach ($worksCategoryOptions as $categoryOption): ?>
                            <option value="<?= htmlspecialchars($categoryOption) ?>" <?= $worksFilters['category'] === $categoryOption ? 'selected' : '' ?>><?= htmlspecialchars($categoryOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>状态
                    <select name="works_status">
                        <option value="all">全部</option>
                        <?php foreach ($workStatusLabels as $statusValue => $statusLabel): ?>
                            <option value="<?= htmlspecialchars($statusValue) ?>" <?= $worksFilters['status'] === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>搜索
                    <input name="works_keyword" value="<?= htmlspecialchars($worksFilters['keyword']) ?>" placeholder="作品名 / ID / 作者">
                </label>
                <div class="order-filter-actions">
                    <button class="btn primary" type="submit">查询</button>
                    <a class="btn ghost" href="/jxdjadmin?admin_section=works-list#works-list">重置</a>
                </div>
            </form>
            <form id="works-bulk-status-form" method="post" action="/jxdjadmin#works-list" data-works-bulk-form>
                <input type="hidden" name="admin_action" value="bulk_update_work_status" data-works-bulk-admin-action>
                <input type="hidden" name="admin_section" value="works-list">
                <input type="hidden" name="bulk_status" value="" data-works-bulk-status>
                <?= $csrfField() ?>
            </form>
            <div class="works-bulk-toolbar">
                <div>
                    <strong>勾选作品后批量操作</strong>
                    <span class="muted" data-works-selected-count>已选择 0 个作品</span>
                </div>
                <div class="works-bulk-actions">
                    <button class="btn ghost" type="button" data-works-bulk-submit data-works-bulk-action="online" disabled>批量上架</button>
                    <button class="btn danger" type="button" data-works-bulk-submit data-works-bulk-action="offline" disabled>批量下架</button>
                    <button class="btn danger solid" type="button" data-works-bulk-submit data-works-bulk-action="delete" disabled>批量删除</button>
                </div>
            </div>
            <div class="kpi-grid">
                <div class="kpi blue"><span class="kpi-icon"><?= jx_icon('drama') ?></span><small>剧集</small><strong><?= number_format(count(array_filter($worksRows, static fn (array $row): bool => (string) ($row['type'] ?? '') === 'drama'))) ?></strong><em>短剧作品</em></div>
                <div class="kpi cyan"><span class="kpi-icon"><?= jx_icon('account') ?></span><small>书籍</small><strong><?= number_format(count(array_filter($worksRows, static fn (array $row): bool => (string) ($row['type'] ?? '') === 'novel'))) ?></strong><em>小说作品</em></div>
                <div class="kpi orange"><span class="kpi-icon"><?= jx_icon('banner') ?></span><small>扩展</small><strong><?= number_format(count(array_filter($worksRows, static fn (array $row): bool => in_array((string) ($row['type'] ?? ''), ['image', 'h5'], true)))) ?></strong><em>壁纸/H5</em></div>
                <div class="kpi green"><span class="kpi-icon"><?= jx_icon('stats') ?></span><small>上架</small><strong><?= number_format(count(array_filter($worksRows, static fn (array $row): bool => (string) ($row['status'] ?? '') === 'online'))) ?></strong><em>可投放内容</em></div>
            </div>
            <div class="order-table works-table">
                <div class="row-card order-row-head">
                    <span><label class="works-select-all"><input type="checkbox" data-works-select-all> 全选</label></span>
                    <span>作品</span>
                    <span>分类与标签</span>
                    <span>数据</span>
                    <span>状态</span>
                    <span>操作</span>
                </div>
                <?php if (empty($filteredWorksRows)): ?>
                    <div class="empty">暂无符合条件的作品。</div>
                <?php endif; ?>
                <?php foreach (array_slice($filteredWorksRows, 0, 120) as $workRow): ?>
                    <?php
                        $workType = (string) ($workRow['type'] ?? 'drama');
                        $workStatus = (string) ($workRow['status'] ?? 'draft');
                        $workQuality = (string) ($workRow['quality'] ?? 'normal');
                        $workAction = match ($workType) {
                            'novel' => 'update_novel',
                            'image', 'h5' => 'save_media_content',
                            default => 'update_drama',
                        };
                        $workStatusKey = (string) preg_replace('/[^a-z0-9_-]+/i', '-', $workStatus);
                        $workTags = array_values(array_filter(array_map(static fn ($tag): string => trim((string) $tag), (array) ($workRow['tags'] ?? []))));
                        $workCover = trim((string) ($workRow['cover'] ?? ''));
                        $workUpdatedAt = (string) ($workRow['updated_at'] ?? '');
                        $workUpdatedTs = $workUpdatedAt !== '' ? strtotime($workUpdatedAt) : false;
                        $workUpdatedLabel = $workUpdatedTs ? date('m-d H:i', $workUpdatedTs) : ($workUpdatedAt !== '' ? $workUpdatedAt : '未记录');
                        $workEditSection = (string) ($workRow['edit_section'] ?? 'works-list');
                        $workUnitSection = (string) ($workRow['unit_section'] ?? '');
                        $workIcon = $workType === 'novel' ? 'account' : ($workType === 'drama' ? 'drama' : 'banner');
                        $workAdvancedHref = in_array($workType, ['drama', 'novel'], true)
                            ? '/jxdjadmin?admin_section=work-editor&work_type=' . rawurlencode($workType) . '&work_id=' . (int) ($workRow['id'] ?? 0) . '#work-editor'
                            : '/jxdjadmin?admin_section=' . rawurlencode($workEditSection) . '#' . rawurlencode($workEditSection);
                    ?>
                    <div class="row-card order-row works-row">
                        <div class="works-select-cell">
                            <label aria-label="选择 <?= htmlspecialchars((string) ($workRow['title'] ?? '作品')) ?>">
                                <input type="checkbox" name="work_keys[]" value="<?= htmlspecialchars((string) ($workRow['key'] ?? '')) ?>" form="works-bulk-status-form" data-works-checkbox>
                            </label>
                        </div>
                        <div class="works-title-cell">
                            <span class="works-cover">
                                <?php if ($workCover !== ''): ?>
                                    <img src="<?= htmlspecialchars($workCover) ?>" alt="">
                                <?php else: ?>
                                    <?= jx_icon($workIcon) ?>
                                <?php endif; ?>
                            </span>
                            <span class="works-title-copy">
                                <strong><?= htmlspecialchars((string) ($workRow['title'] ?? '未命名作品')) ?></strong>
                                <em>ID <?= number_format((int) ($workRow['id'] ?? 0)) ?> · <?= htmlspecialchars($workTypeLabels[$workType] ?? $workType) ?> · <?= htmlspecialchars((string) (($workRow['author'] ?? '') ?: '未填作者')) ?></em>
                            </span>
                        </div>
                        <div class="works-meta-cell">
                            <strong><?= htmlspecialchars((string) (($workRow['category'] ?? '') ?: '未分类')) ?></strong>
                            <em><?= htmlspecialchars($workQualityLabels[$workQuality] ?? $workQuality) ?></em>
                            <span class="works-tags">
                                <?php if (empty($workTags)): ?>
                                    <i>未配置标签</i>
                                <?php else: ?>
                                    <?php foreach (array_slice($workTags, 0, 3) as $workTag): ?>
                                        <b><?= htmlspecialchars($workTag) ?></b>
                                    <?php endforeach; ?>
                                    <?php if (count($workTags) > 3): ?><b>+<?= number_format(count($workTags) - 3) ?></b><?php endif; ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="works-data-cell">
                            <strong><?= number_format((int) ($workRow['unit_count'] ?? 0)) ?> <?= htmlspecialchars((string) ($workRow['unit_label'] ?? '单元')) ?></strong>
                            <em><?= htmlspecialchars((string) ($workRow['price_label'] ?? '')) ?></em>
                            <em>第 <?= number_format((int) ($workRow['buy_start'] ?? 0)) ?> <?= htmlspecialchars((string) ($workRow['unit_label'] ?? '单元')) ?>起收费</em>
                            <em><?= number_format((int) ($workRow['read_count'] ?? 0)) ?> 阅读/播放</em>
                            <em>更新 <?= htmlspecialchars($workUpdatedLabel) ?></em>
                        </div>
                        <div class="works-status-stack">
                            <span class="works-badge is-<?= htmlspecialchars($workStatusKey) ?>"><?= htmlspecialchars($workStatusLabels[$workStatus] ?? $workStatus) ?></span>
                            <span class="works-badge <?= !empty($workRow['is_finished']) ? 'is-done' : 'is-muted' ?>"><?= !empty($workRow['is_finished']) ? '完结' : '连载中' ?></span>
                            <span class="works-badge <?= !empty($workRow['is_vip']) ? 'is-paid' : 'is-free' ?>"><?= !empty($workRow['is_vip']) ? '收费' : '免费' ?></span>
                        </div>
                        <div class="works-actions">
                            <a class="btn ghost" href="<?= htmlspecialchars($workAdvancedHref) ?>">高级编辑</a>
                        </div>
                        <details class="work-quick-edit">
                            <summary>快速编辑</summary>
                            <form method="post" action="/jxdjadmin#works-list" class="work-quick-edit-form">
                                <input type="hidden" name="admin_action" value="<?= htmlspecialchars($workAction) ?>">
                                <input type="hidden" name="admin_section" value="works-list">
                                <?php if ($workType === 'novel'): ?>
                                    <input type="hidden" name="novel_id" value="<?= (int) ($workRow['id'] ?? 0) ?>">
                                <?php elseif (in_array($workType, ['image', 'h5'], true)): ?>
                                    <input type="hidden" name="media_id" value="<?= (int) ($workRow['id'] ?? 0) ?>">
                                    <input type="hidden" name="media_type" value="<?= htmlspecialchars($workType) ?>">
                                <?php else: ?>
                                    <input type="hidden" name="drama_id" value="<?= (int) ($workRow['id'] ?? 0) ?>">
                                <?php endif; ?>
                                <?= $csrfField() ?>
                                <div class="work-quick-edit-grid">
                                    <label>作品名<input name="title" value="<?= htmlspecialchars((string) ($workRow['title'] ?? '')) ?>"></label>
                                    <label>作者/版权方<input name="author" value="<?= htmlspecialchars((string) ($workRow['author'] ?? '')) ?>"></label>
                                    <label>封面地址<input name="cover" value="<?= htmlspecialchars((string) ($workRow['cover'] ?? '')) ?>"></label>
                                    <label>标签<input name="tags" value="<?= htmlspecialchars(implode(',', (array) ($workRow['tags'] ?? []))) ?>"></label>
                                    <label>分类
                                        <select name="category">
                                            <?php foreach ($worksCategoryOptions as $categoryOption): ?>
                                                <option value="<?= htmlspecialchars($categoryOption) ?>" <?= (string) ($workRow['category'] ?? '') === $categoryOption ? 'selected' : '' ?>><?= htmlspecialchars($categoryOption) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label>质量
                                        <select name="quality">
                                            <?php foreach ($workQualityLabels as $qualityValue => $qualityLabel): ?>
                                                <option value="<?= htmlspecialchars($qualityValue) ?>" <?= $workQuality === $qualityValue ? 'selected' : '' ?>><?= htmlspecialchars($qualityLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label>状态
                                        <select name="status">
                                            <?php foreach ($workStatusLabels as $statusValue => $statusLabel): ?>
                                                <option value="<?= htmlspecialchars($statusValue) ?>" <?= $workStatus === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label><?= $workType === 'drama' ? '单集K币价' : ($workType === 'novel' ? '章节K币价' : '单价K币') ?>
                                        <?php if ($workType === 'drama'): ?>
                                            <input name="episode_coin_price" value="<?= htmlspecialchars((string) preg_replace('/\D+/', '', (string) ($workRow['price_label'] ?? '199'))) ?>">
                                        <?php elseif ($workType === 'novel'): ?>
                                            <input name="chapter_coin_price" value="<?= htmlspecialchars((string) preg_replace('/\D+/', '', (string) ($workRow['price_label'] ?? '99'))) ?>">
                                        <?php else: ?>
                                            <input name="price_coins" value="<?= htmlspecialchars((string) preg_replace('/\D+/', '', (string) ($workRow['price_label'] ?? '99'))) ?>">
                                        <?php endif; ?>
                                    </label>
                                    <label>收费起始<input name="buy_start" value="<?= htmlspecialchars((string) ($workRow['buy_start'] ?? 0)) ?>"></label>
                                    <label>阅读/播放量<input name="read_count" value="<?= htmlspecialchars((string) ($workRow['read_count'] ?? 0)) ?>"></label>
                                    <label class="works-check-row"><span><input type="checkbox" name="is_finished" value="1" <?= !empty($workRow['is_finished']) ? 'checked' : '' ?>> 完结</span></label>
                                    <label class="works-check-row"><span><input type="checkbox" name="is_vip" value="1" <?= !empty($workRow['is_vip']) ? 'checked' : '' ?>> 收费</span></label>
                                </div>
                                <div class="work-quick-edit-actions">
                                    <button class="btn primary" type="submit">保存修改</button>
                                </div>
                            </form>
                        </details>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel admin-section work-editor-section <?= $activeAdminSection === 'work-editor' ? 'is-active' : '' ?>" id="admin-section-work-editor" data-admin-section="work-editor" data-admin-primary="works">
            <div class="admin-page-head work-editor-head">
                <div>
                    <span class="eyebrow">作品管理</span>
                    <h2><?= htmlspecialchars($workEditorType === 'novel' ? '编辑书籍' : '编辑剧集') ?></h2>
                    <p class="muted"><?= htmlspecialchars($workEditorTitle) ?> · <?= number_format($workEditorUnitCount) ?> <?= htmlspecialchars($workEditorUnitLabel) ?></p>
                </div>
                <div class="admin-page-actions">
                    <a class="btn ghost" href="/jxdjadmin?admin_section=works-list#works-list">返回作品列表</a>
                </div>
            </div>
            <?php if ($workEditorContent === null): ?>
                <div class="empty">没有找到要编辑的作品，请从作品列表重新进入。</div>
            <?php else: ?>
                <?php
                    $workEditorStatus = (string) ($workEditorContent['status'] ?? 'draft');
                    $workEditorQuality = (string) ($workEditorContent['quality'] ?? 'normal');
                    $workEditorTagsText = implode(',', array_values((array) ($workEditorContent['tags'] ?? [])));
                    $workEditorBuyStart = max(0, (int) ($workEditorContent['buy_start'] ?? ($workEditorType === 'novel' ? ($workEditorContent['free_chapter_count'] ?? 3) : ($workEditorContent['free_episode_count'] ?? 1))));
                    $workEditorReadCount = max(0, (int) ($workEditorType === 'novel' ? ($workEditorContent['read_count'] ?? 0) : ($workEditorContent['views'] ?? 0)));
                    $workEditorPriceCoins = max(1, (int) ($workEditorType === 'novel' ? ($workEditorContent['chapter_coin_price'] ?? 99) : ($workEditorContent['episode_coin_price'] ?? 199)));
                    $workEditorCategoryOptions = $workEditorType === 'novel' ? $novelCategoryOptions : $dramaCategoryOptions;
                    $workEditorContentIdName = $workEditorType === 'novel' ? 'novel_id' : 'drama_id';
                ?>
                <div class="work-editor-layout">
                    <aside class="work-editor-side">
                        <form method="post" action="<?= htmlspecialchars($workEditorFormAction) ?>" class="work-editor-form">
                            <input type="hidden" name="admin_action" value="<?= htmlspecialchars($workEditorAction) ?>">
                            <input type="hidden" name="admin_section" value="work-editor">
                            <input type="hidden" name="work_type" value="<?= htmlspecialchars($workEditorType) ?>">
                            <input type="hidden" name="work_id" value="<?= $workEditorId ?>">
                            <input type="hidden" name="<?= htmlspecialchars($workEditorContentIdName) ?>" value="<?= $workEditorId ?>">
                            <?php if ($workEditorType === 'novel'): ?>
                                <input type="hidden" name="free_chapter_count" value="<?= max(0, $workEditorBuyStart) ?>">
                            <?php else: ?>
                                <input type="hidden" name="free_episode_count" value="<?= max(0, $workEditorBuyStart - 1) ?>">
                            <?php endif; ?>
                            <?= $csrfField() ?>
                            <div class="work-editor-cover" style="--work-cover: url('<?= htmlspecialchars($workEditorCover !== '' ? $workEditorCover : '/assets/cover-1.svg') ?>')">
                                <?php if ($workEditorCover !== ''): ?>
                                    <img src="<?= htmlspecialchars($workEditorCover) ?>" alt="">
                                <?php else: ?>
                                    <?= jx_icon($workEditorType === 'novel' ? 'account' : 'drama') ?>
                                <?php endif; ?>
                            </div>
                            <div class="work-editor-fields">
                                <label>名称<input name="title" value="<?= htmlspecialchars((string) ($workEditorContent['title'] ?? '')) ?>"></label>
                                <label>分类
                                    <select name="category">
                                        <?php foreach ($workEditorCategoryOptions as $categoryOption): ?>
                                            <option value="<?= htmlspecialchars($categoryOption) ?>" <?= (string) ($workEditorContent['category'] ?? '') === $categoryOption ? 'selected' : '' ?>><?= htmlspecialchars($categoryOption) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>标签<input name="tags" value="<?= htmlspecialchars($workEditorTagsText) ?>"></label>
                                <label>作者<input name="author" value="<?= htmlspecialchars((string) ($workEditorContent['author'] ?? '')) ?>"></label>
                                <label><?= $workEditorType === 'novel' ? '章节价' : '单集价' ?><input name="<?= $workEditorType === 'novel' ? 'chapter_coin_price' : 'episode_coin_price' ?>" value="<?= $workEditorPriceCoins ?>"></label>
                                <label>是否完结
                                    <select name="is_finished">
                                        <option value="1" <?= !empty($workEditorContent['is_finished']) ? 'selected' : '' ?>>完结</option>
                                        <option value="0" <?= empty($workEditorContent['is_finished']) ? 'selected' : '' ?>>未完结</option>
                                    </select>
                                </label>
                                <label>是否上架
                                    <select name="status">
                                        <?php foreach ($workStatusLabels as $statusValue => $statusLabel): ?>
                                            <option value="<?= htmlspecialchars($statusValue) ?>" <?= $workEditorStatus === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>是否收费
                                    <select name="is_vip">
                                        <option value="1" <?= !empty($workEditorContent['is_vip']) ? 'selected' : '' ?>>是</option>
                                        <option value="0" <?= empty($workEditorContent['is_vip']) ? 'selected' : '' ?>>否</option>
                                    </select>
                                </label>
                                <label>收费起始<input name="buy_start" value="<?= $workEditorBuyStart ?>"></label>
                                <label>阅读总量<input name="<?= $workEditorType === 'novel' ? 'read_count' : 'views' ?>" value="<?= $workEditorReadCount ?>"></label>
                                <label>质量
                                    <select name="quality">
                                        <?php foreach ($workQualityLabels as $qualityValue => $qualityLabel): ?>
                                            <option value="<?= htmlspecialchars($qualityValue) ?>" <?= $workEditorQuality === $qualityValue ? 'selected' : '' ?>><?= htmlspecialchars($qualityLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="work-editor-field-full">封面地址<input name="cover" value="<?= htmlspecialchars($workEditorCover) ?>"></label>
                                <label class="work-editor-field-full">简介<textarea name="description" rows="9"><?= htmlspecialchars((string) ($workEditorContent['description'] ?? '')) ?></textarea></label>
                            </div>
                            <footer class="work-editor-savebar">
                                <span class="muted">最后修改：<?= htmlspecialchars((string) ($workEditorContent['updated_at'] ?? '')) ?></span>
                                <span>
                                    <a class="btn ghost" href="/jxdjadmin?admin_section=works-list#works-list">取消</a>
                                    <button class="btn primary" type="submit">保存</button>
                                </span>
                            </footer>
                        </form>
                    </aside>
                    <div class="work-editor-main">
                        <div class="work-editor-toolbar">
                            <div class="work-editor-tabs" aria-label="编辑模式">
                                <span class="is-active"><?= $workEditorType === 'novel' ? '章节列表' : '剧集列表' ?></span>
                                <span>编辑/新增</span>
                                <span>批量上传</span>
                            </div>
                            <details class="work-editor-create">
                                <summary><?= $workEditorType === 'novel' ? '新增章节' : '新增剧集' ?></summary>
                                <form method="post" action="<?= htmlspecialchars($workEditorFormAction) ?>" class="work-editor-unit-form">
                                    <input type="hidden" name="admin_action" value="<?= htmlspecialchars($workEditorCreateUnitAction) ?>">
                                    <input type="hidden" name="admin_section" value="work-editor">
                                    <input type="hidden" name="work_type" value="<?= htmlspecialchars($workEditorType) ?>">
                                    <input type="hidden" name="work_id" value="<?= $workEditorId ?>">
                                    <input type="hidden" name="<?= htmlspecialchars($workEditorContentIdName) ?>" value="<?= $workEditorId ?>">
                                    <?= $csrfField() ?>
                                    <div class="work-editor-unit-form-grid">
                                        <label>标题<input name="title" placeholder="<?= $workEditorType === 'novel' ? '第N章' : '第N集' ?>"></label>
                                        <label>排序<input name="sort" value="<?= $workEditorUnitCount + 1 ?>"></label>
                                        <label>K币价<input name="coin_price" value="<?= $workEditorPriceCoins ?>"></label>
                                        <label>状态
                                            <select name="status">
                                                <?php foreach ($workStatusLabels as $statusValue => $statusLabel): ?>
                                                    <option value="<?= htmlspecialchars($statusValue) ?>" <?= $workEditorStatus === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label>试看
                                            <select name="is_free">
                                                <option value="0">收费</option>
                                                <option value="1">免费</option>
                                            </select>
                                        </label>
                                        <?php if ($workEditorType === 'novel'): ?>
                                            <label>字数<input name="word_count"></label>
                                            <label class="work-editor-field-full">正文<textarea name="content" rows="4"></textarea></label>
                                        <?php else: ?>
                                            <label>时长<input name="duration" value="03:00"></label>
                                            <label class="work-editor-field-full">视频地址<input name="video_url" placeholder="https://..."></label>
                                        <?php endif; ?>
                                    </div>
                                    <button class="btn primary" type="submit"><?= $workEditorType === 'novel' ? '新增章节' : '新增剧集' ?></button>
                                </form>
                            </details>
                        </div>
                        <div class="work-editor-ranges">
                            <strong><?= $workEditorType === 'novel' ? '章节分段浏览' : '剧集分段浏览' ?></strong>
                            <span>切换<?= htmlspecialchars($workEditorUnitLabel) ?>数范围查看不同分段</span>
                            <div>
                                <?php for ($rangeStart = 1; $rangeStart <= max(1, $workEditorUnitCount); $rangeStart += $workEditorRangeSize): ?>
                                    <?php $rangeEnd = min($workEditorUnitCount, $rangeStart + $workEditorRangeSize - 1); ?>
                                    <a class="<?= $rangeStart === $workEditorRangeStart ? 'is-active' : '' ?>" href="<?= htmlspecialchars($workEditorUrl($rangeStart)) ?>"><?= number_format($rangeStart) ?>-<?= number_format(max($rangeStart, $rangeEnd)) ?><?= htmlspecialchars($workEditorUnitLabel) ?></a>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="<?= $workEditorType === 'novel' ? 'work-editor-chapter-list' : 'work-editor-unit-grid' ?>">
                            <?php if (empty($workEditorVisibleUnits)): ?>
                                <div class="empty">暂无<?= htmlspecialchars($workEditorUnitLabel) ?>。</div>
                            <?php endif; ?>
                            <?php foreach ($workEditorVisibleUnits as $unitIndex => $unit): ?>
                                <?php
                                    $unitSort = (int) ($unit['sort'] ?? ($workEditorRangeStart + $unitIndex));
                                    $unitTitle = (string) ($unit['title'] ?? ($workEditorType === 'novel' ? ('第' . $unitSort . '章') : ('第' . $unitSort . '集')));
                                    $unitStatus = (string) ($unit['status'] ?? $workEditorStatus);
                                    $unitCoinPrice = max(0, (int) ($unit['coin_price'] ?? $workEditorPriceCoins));
                                    $unitIsFree = !empty($unit['is_free']);
                                    $unitCover = $workEditorUnitCover($unit, $workEditorCover);
                                ?>
                                <article class="work-editor-unit-card">
                                    <?php if ($workEditorType === 'novel'): ?>
                                        <div class="work-editor-chapter-summary">
                                            <strong><?= htmlspecialchars($unitTitle) ?></strong>
                                            <span><?= number_format(max(0, (int) ($unit['word_count'] ?? 0))) ?> 字 · <?= $unitIsFree ? '免费' : '收费' ?> · <?= htmlspecialchars($workStatusLabels[$unitStatus] ?? $unitStatus) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="work-editor-unit-poster">
                                            <?php if ($unitCover !== ''): ?>
                                                <img src="<?= htmlspecialchars($unitCover) ?>" alt="">
                                            <?php else: ?>
                                                <?= jx_icon('drama') ?>
                                            <?php endif; ?>
                                        </div>
                                        <strong>第<?= number_format($unitSort) ?>集</strong>
                                    <?php endif; ?>
                                    <details class="work-editor-unit-edit">
                                        <summary>编辑</summary>
                                        <form method="post" action="<?= htmlspecialchars($workEditorFormAction) ?>" class="work-editor-unit-form">
                                            <input type="hidden" name="admin_action" value="<?= htmlspecialchars($workEditorUpdateUnitAction) ?>">
                                            <input type="hidden" name="admin_section" value="work-editor">
                                            <input type="hidden" name="work_type" value="<?= htmlspecialchars($workEditorType) ?>">
                                            <input type="hidden" name="work_id" value="<?= $workEditorId ?>">
                                            <input type="hidden" name="<?= htmlspecialchars($workEditorContentIdName) ?>" value="<?= $workEditorId ?>">
                                            <?php if ($workEditorType === 'novel'): ?>
                                                <input type="hidden" name="chapter_id" value="<?= (int) ($unit['id'] ?? 0) ?>">
                                            <?php else: ?>
                                                <input type="hidden" name="episode_id" value="<?= (int) ($unit['id'] ?? 0) ?>">
                                            <?php endif; ?>
                                            <?= $csrfField() ?>
                                            <div class="work-editor-unit-form-grid">
                                                <label>标题<input name="title" value="<?= htmlspecialchars($unitTitle) ?>"></label>
                                                <label>排序<input name="sort" value="<?= $unitSort ?>"></label>
                                                <label>K币价<input name="coin_price" value="<?= $unitCoinPrice ?>"></label>
                                                <label>状态
                                                    <select name="status">
                                                        <?php foreach ($workStatusLabels as $statusValue => $statusLabel): ?>
                                                            <option value="<?= htmlspecialchars($statusValue) ?>" <?= $unitStatus === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </label>
                                                <label>试看
                                                    <select name="is_free">
                                                        <option value="0" <?= !$unitIsFree ? 'selected' : '' ?>>收费</option>
                                                        <option value="1" <?= $unitIsFree ? 'selected' : '' ?>>免费</option>
                                                    </select>
                                                </label>
                                                <?php if ($workEditorType === 'novel'): ?>
                                                    <label>字数<input name="word_count" value="<?= max(0, (int) ($unit['word_count'] ?? 0)) ?>"></label>
                                                    <label class="work-editor-field-full">正文<textarea name="content" rows="5"><?= htmlspecialchars((string) ($unit['content'] ?? '')) ?></textarea></label>
                                                <?php else: ?>
                                                    <label>时长<input name="duration" value="<?= htmlspecialchars((string) ($unit['duration'] ?? '03:00')) ?>"></label>
                                                    <label class="work-editor-field-full">视频地址<input name="video_url" value="<?= htmlspecialchars((string) ($unit['video_url'] ?? '')) ?>"></label>
                                                <?php endif; ?>
                                            </div>
                                            <button class="btn ghost" type="submit">保存<?= htmlspecialchars($workEditorUnitLabel) ?></button>
                                        </form>
                                    </details>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel admin-section admin-tool-section <?= $activeAdminSection === 'dramas' ? 'is-active' : '' ?>" id="admin-section-dramas" data-admin-section="dramas" data-admin-primary="works">
            <div class="admin-page-head">
                <div>
                    <span class="eyebrow">作品管理</span>
                    <h2>编辑剧集</h2>
                    <p class="muted"><?= number_format(count($dramas)) ?> 部短剧 · 列表优先，新增和编辑放在右侧抽屉中处理。</p>
                </div>
                <div class="admin-page-actions">
                    <button class="btn primary" type="button" data-admin-drawer-open="drawer-create-drama"><?= jx_icon('drama') ?><span>新增短剧</span></button>
                    <a class="btn ghost" href="/jxdjadmin?admin_section=episodes#episodes" data-admin-target="episodes"><?= jx_icon('order') ?><span>分集管理</span></a>
                </div>
            </div>
            <?php if (!empty($message)): ?><p class="notice"><?= htmlspecialchars($message) ?></p><?php endif; ?>
            <div class="admin-summary-strip admin-summary-strip-tight">
                <div><strong><?= number_format(count($dramas)) ?></strong><span>剧集总数</span></div>
                <div><strong><?= number_format(count(array_filter($dramas, static fn (array $item): bool => (string) ($item['status'] ?? 'draft') === 'online'))) ?></strong><span>已上架</span></div>
                <div><strong><?= number_format(array_sum(array_map(static fn (array $item): int => count((array) ($item['episodes'] ?? [])), $dramas))) ?></strong><span>分集总数</span></div>
                <div><strong><?= number_format(array_sum(array_map(static fn (array $item): int => (int) ($item['views'] ?? 0), $dramas))) ?></strong><span>播放/阅读</span></div>
            </div>
            <div class="admin-data-panel drama-list-panel">
                <div class="admin-data-head drama-data-head">
                    <span>剧集</span>
                    <span>价格权益</span>
                    <span>展示状态</span>
                    <span>分集</span>
                    <span>操作</span>
                </div>
                <?php if (empty($dramas)): ?>
                    <div class="empty">暂无短剧，点击右上角新增。</div>
                <?php endif; ?>
                <?php foreach ($dramas as $drama): ?>
                    <?php
                        $dramaId = (int) ($drama['id'] ?? 0);
                        $dramaStatus = (string) ($drama['status'] ?? 'draft');
                        $dramaStatusKey = (string) preg_replace('/[^a-z0-9_-]+/i', '-', $dramaStatus);
                        $dramaEpisodeCount = count((array) ($drama['episodes'] ?? []));
                        $dramaTags = array_values(array_filter(array_map(static fn ($tag): string => trim((string) $tag), (array) ($drama['tags'] ?? []))));
                        $dramaDrawerId = 'drawer-drama-' . $dramaId;
                        $episodeDrawerId = 'drawer-drama-' . $dramaId . '-new-episode';
                    ?>
                    <div class="admin-data-row drama-data-row">
                        <div class="works-title-cell">
                            <span class="works-cover">
                                <?php if (trim((string) ($drama['cover'] ?? '')) !== ''): ?>
                                    <img src="<?= htmlspecialchars((string) ($drama['cover'] ?? '')) ?>" alt="">
                                <?php else: ?>
                                    <?= jx_icon('drama') ?>
                                <?php endif; ?>
                            </span>
                            <span class="works-title-copy">
                                <strong><?= htmlspecialchars((string) ($drama['title'] ?? '未命名短剧')) ?></strong>
                                <em>ID <?= number_format($dramaId) ?> · <?= htmlspecialchars((string) (($drama['author'] ?? '') ?: '未填作者')) ?></em>
                                <span class="works-tags">
                                    <?php if (empty($dramaTags)): ?>
                                        <i>未配置标签</i>
                                    <?php else: ?>
                                        <?php foreach (array_slice($dramaTags, 0, 3) as $dramaTag): ?><b><?= htmlspecialchars($dramaTag) ?></b><?php endforeach; ?>
                                    <?php endif; ?>
                                </span>
                            </span>
                        </div>
                        <div class="works-data-cell">
                            <strong><?= number_format((int) ($drama['episode_coin_price'] ?? 199)) ?> K币/集</strong>
                            <em>免费 <?= number_format((int) ($drama['free_episode_count'] ?? 1)) ?> 集 · 第 <?= number_format((int) ($drama['buy_start'] ?? $drama['free_episode_count'] ?? 1)) ?> 集起收费</em>
                            <em>全集 ￥<?= htmlspecialchars((string) ($drama['full_unlock_price'] ?? $drama['membership_price'] ?? 19.9)) ?> · 会员 ￥<?= htmlspecialchars((string) ($drama['membership_price'] ?? 0)) ?></em>
                        </div>
                        <div class="works-status-stack">
                            <span class="works-badge is-<?= htmlspecialchars($dramaStatusKey) ?>"><?= htmlspecialchars($workStatusLabels[$dramaStatus] ?? $dramaStatus) ?></span>
                            <span class="works-badge <?= !empty($drama['is_hot']) ? 'is-paid' : 'is-muted' ?>">首页热播</span>
                            <span class="works-badge <?= !empty($drama['is_new']) ? 'is-free' : 'is-muted' ?>">新剧上线</span>
                            <span class="works-badge <?= !empty($drama['is_finished']) ? 'is-done' : 'is-muted' ?>"><?= !empty($drama['is_finished']) ? '已完结' : '连载中' ?></span>
                        </div>
                        <div class="works-data-cell">
                            <strong><?= number_format($dramaEpisodeCount) ?> 集</strong>
                            <em><?= number_format((int) ($drama['views'] ?? 0)) ?> 播放/阅读</em>
                            <em><?= htmlspecialchars($workQualityLabels[(string) ($drama['quality'] ?? 'normal')] ?? (string) ($drama['quality'] ?? 'normal')) ?></em>
                        </div>
                        <div class="admin-row-actions">
                            <button class="btn ghost" type="button" data-admin-drawer-open="<?= htmlspecialchars($dramaDrawerId) ?>">编辑</button>
                            <button class="btn ghost" type="button" data-admin-drawer-open="<?= htmlspecialchars($episodeDrawerId) ?>">新增分集</button>
                            <a class="btn ghost" href="/jxdjadmin?admin_section=episodes&unit_type=drama&unit_parent_id=<?= $dramaId ?>#episodes" data-admin-target="episodes">分集</a>
                        </div>
                    </div>

                    <div class="admin-drawer" id="<?= htmlspecialchars($dramaDrawerId) ?>" data-admin-drawer hidden>
                        <div class="admin-drawer-backdrop" data-admin-drawer-close></div>
                        <aside class="admin-drawer-card" role="dialog" aria-modal="true" aria-labelledby="<?= htmlspecialchars($dramaDrawerId) ?>-title">
                            <header class="admin-drawer-head">
                                <div>
                                    <span class="eyebrow">编辑剧集</span>
                                    <h3 id="<?= htmlspecialchars($dramaDrawerId) ?>-title"><?= htmlspecialchars((string) ($drama['title'] ?? '未命名短剧')) ?></h3>
                                    <p>基础信息、价格权益和展示状态集中维护。</p>
                                </div>
                                <button type="button" data-admin-drawer-close aria-label="关闭">×</button>
                            </header>
                            <form method="post" action="/jxdjadmin#dramas" class="admin-drawer-form">
                                <input type="hidden" name="admin_action" value="update_drama">
                                <input type="hidden" name="admin_section" value="dramas">
                                <?= $csrfField() ?>
                                <input type="hidden" name="drama_id" value="<?= $dramaId ?>">
                                <section>
                                    <h4>基础信息</h4>
                                    <div class="form-grid">
                                        <label>名称<input name="title" value="<?= htmlspecialchars((string) ($drama['title'] ?? '')) ?>"></label>
                                        <label>封面地址<input name="cover" value="<?= htmlspecialchars((string) ($drama['cover'] ?? '')) ?>"></label>
                                        <label>作者/版权方<input name="author" value="<?= htmlspecialchars((string) ($drama['author'] ?? '')) ?>"></label>
                                        <label>分类
                                            <select name="category">
                                                <?php foreach ($dramaCategoryOptions as $categoryOption): ?>
                                                    <option value="<?= htmlspecialchars($categoryOption) ?>" <?= (string) ($drama['category'] ?? '') === $categoryOption ? 'selected' : '' ?>><?= htmlspecialchars($categoryOption) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="admin-form-span-2">简介<input name="description" value="<?= htmlspecialchars((string) ($drama['description'] ?? '')) ?>"></label>
                                        <label class="admin-form-span-2">标签<input name="tags" value="<?= htmlspecialchars(implode(',', (array) ($drama['tags'] ?? []))) ?>"></label>
                                    </div>
                                </section>
                                <section>
                                    <h4>价格权益</h4>
                                    <div class="form-grid">
                                        <label>单集价格<input name="price_per_episode" value="<?= htmlspecialchars((string) ($drama['price_per_episode'] ?? 0)) ?>"></label>
                                        <label>单集K币价<input name="episode_coin_price" value="<?= htmlspecialchars((string) ($drama['episode_coin_price'] ?? 199)) ?>"></label>
                                        <label>免费集数<input name="free_episode_count" value="<?= htmlspecialchars((string) ($drama['free_episode_count'] ?? 1)) ?>"></label>
                                        <label>收费起始<input name="buy_start" value="<?= htmlspecialchars((string) ($drama['buy_start'] ?? $drama['free_episode_count'] ?? 1)) ?>"></label>
                                        <label>全集价格<input name="full_unlock_price" value="<?= htmlspecialchars((string) ($drama['full_unlock_price'] ?? $drama['membership_price'] ?? 19.9)) ?>"></label>
                                        <label>会员价<input name="membership_price" value="<?= htmlspecialchars((string) ($drama['membership_price'] ?? 0)) ?>"></label>
                                    </div>
                                </section>
                                <section>
                                    <h4>展示状态</h4>
                                    <div class="form-grid">
                                        <label>阅读总量<input name="views" value="<?= htmlspecialchars((string) ($drama['views'] ?? 0)) ?>"></label>
                                        <label>质量
                                            <select name="quality">
                                                <?php foreach ($workQualityLabels as $qualityValue => $qualityLabel): ?>
                                                    <option value="<?= htmlspecialchars($qualityValue) ?>" <?= (string) ($drama['quality'] ?? 'normal') === $qualityValue ? 'selected' : '' ?>><?= htmlspecialchars($qualityLabel) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label>排序<input name="sort" value="<?= htmlspecialchars((string) ($drama['sort'] ?? 0)) ?>"></label>
                                        <label>状态
                                            <select name="status">
                                                <?php foreach ($workStatusLabels as $statusValue => $statusLabel): ?>
                                                    <option value="<?= htmlspecialchars($statusValue) ?>" <?= $dramaStatus === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                    </div>
                                    <div class="admin-check-grid">
                                        <label><span><input type="checkbox" name="is_hot" value="1" <?= !empty($drama['is_hot']) ? 'checked' : '' ?>> 首页热播</span></label>
                                        <label><span><input type="checkbox" name="is_new" value="1" <?= !empty($drama['is_new']) ? 'checked' : '' ?>> 新剧上线</span></label>
                                        <label><span><input type="checkbox" name="is_finished" value="1" <?= !empty($drama['is_finished']) ? 'checked' : '' ?>> 已完结</span></label>
                                        <label><span><input type="checkbox" name="is_vip" value="1" <?= !empty($drama['is_vip']) ? 'checked' : '' ?>> 收费</span></label>
                                    </div>
                                </section>
                                <footer>
                                    <button class="btn ghost" type="button" data-admin-drawer-close>取消</button>
                                    <button class="btn primary" type="submit">保存剧集</button>
                                </footer>
                            </form>
                        </aside>
                    </div>

                    <div class="admin-drawer" id="<?= htmlspecialchars($episodeDrawerId) ?>" data-admin-drawer hidden>
                        <div class="admin-drawer-backdrop" data-admin-drawer-close></div>
                        <aside class="admin-drawer-card" role="dialog" aria-modal="true" aria-labelledby="<?= htmlspecialchars($episodeDrawerId) ?>-title">
                            <header class="admin-drawer-head">
                                <div>
                                    <span class="eyebrow">新增分集</span>
                                    <h3 id="<?= htmlspecialchars($episodeDrawerId) ?>-title"><?= htmlspecialchars((string) ($drama['title'] ?? '短剧')) ?></h3>
                                    <p>新增后可在“分集/章节管理”继续批量维护。</p>
                                </div>
                                <button type="button" data-admin-drawer-close aria-label="关闭">×</button>
                            </header>
                            <form method="post" action="/jxdjadmin#dramas" class="admin-drawer-form">
                                <input type="hidden" name="admin_action" value="create_episode">
                                <input type="hidden" name="admin_section" value="dramas">
                                <?= $csrfField() ?>
                                <input type="hidden" name="drama_id" value="<?= $dramaId ?>">
                                <section>
                                    <h4>分集信息</h4>
                                    <div class="form-grid">
                                        <label>分集标题<input name="title"></label>
                                        <label>排序<input name="sort" value="<?= number_format($dramaEpisodeCount + 1) ?>"></label>
                                        <label>时长<input name="duration" value="03:00"></label>
                                        <label>K币价<input name="coin_price" value="<?= htmlspecialchars((string) ($drama['episode_coin_price'] ?? 199)) ?>"></label>
                                        <label>状态
                                            <select name="status">
                                                <?php foreach ($workStatusLabels as $statusValue => $statusLabel): ?>
                                                    <option value="<?= htmlspecialchars($statusValue) ?>" <?= $dramaStatus === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label class="admin-form-span-2">视频地址<input name="video_url" placeholder="https://...mp4"></label>
                                    </div>
                                    <div class="admin-check-grid">
                                        <label><span><input type="checkbox" name="is_free" value="1"> 免费试看</span></label>
                                    </div>
                                </section>
                                <footer>
                                    <button class="btn ghost" type="button" data-admin-drawer-close>取消</button>
                                    <button class="btn primary" type="submit">新增分集</button>
                                </footer>
                            </form>
                        </aside>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="admin-drawer" id="drawer-create-drama" data-admin-drawer hidden>
                <div class="admin-drawer-backdrop" data-admin-drawer-close></div>
                <aside class="admin-drawer-card" role="dialog" aria-modal="true" aria-labelledby="drawer-create-drama-title">
                    <header class="admin-drawer-head">
                        <div>
                            <span class="eyebrow">新增短剧</span>
                            <h3 id="drawer-create-drama-title">创建新剧集</h3>
                            <p>先填写基础和价格信息，分集可创建后继续补充。</p>
                        </div>
                        <button type="button" data-admin-drawer-close aria-label="关闭">×</button>
                    </header>
                    <form method="post" action="/jxdjadmin#dramas" class="admin-drawer-form">
                        <input type="hidden" name="admin_action" value="create_drama">
                        <input type="hidden" name="admin_section" value="dramas">
                        <?= $csrfField() ?>
                        <section>
                            <h4>基础信息</h4>
                            <div class="form-grid">
                                <label>新短剧名称<input name="title"></label>
                                <label>封面地址<input name="cover" value="/assets/cover-1.svg"></label>
                                <label>作者/版权方<input name="author"></label>
                                <label>分类
                                    <select name="category">
                                        <?php foreach ($dramaCategoryOptions as $categoryOption): ?>
                                            <option value="<?= htmlspecialchars($categoryOption) ?>"><?= htmlspecialchars($categoryOption) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="admin-form-span-2">简介<input name="description"></label>
                                <label class="admin-form-span-2">标签<input name="tags" placeholder="逆袭,甜宠,投流"></label>
                            </div>
                        </section>
                        <section>
                            <h4>价格权益</h4>
                            <div class="form-grid">
                                <label>单集价格<input name="price_per_episode"></label>
                                <label>单集K币价<input name="episode_coin_price" value="199"></label>
                                <label>免费集数<input name="free_episode_count" value="1"></label>
                                <label>收费起始<input name="buy_start" value="1"></label>
                                <label>全集价格<input name="full_unlock_price" value="19.9"></label>
                                <label>会员价<input name="membership_price"></label>
                            </div>
                        </section>
                        <section>
                            <h4>展示状态</h4>
                            <div class="form-grid">
                                <label>阅读总量<input name="views" value="0"></label>
                                <label>质量
                                    <select name="quality">
                                        <?php foreach ($workQualityLabels as $qualityValue => $qualityLabel): ?>
                                            <option value="<?= htmlspecialchars($qualityValue) ?>"><?= htmlspecialchars($qualityLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>排序<input name="sort" value="0"></label>
                                <label>状态
                                    <select name="status">
                                        <?php foreach ($workStatusLabels as $statusValue => $statusLabel): ?>
                                            <option value="<?= htmlspecialchars($statusValue) ?>"><?= htmlspecialchars($statusLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <div class="admin-check-grid">
                                <label><span><input type="checkbox" name="is_hot" value="1" checked> 首页热播</span></label>
                                <label><span><input type="checkbox" name="is_new" value="1" checked> 新剧上线</span></label>
                                <label><span><input type="checkbox" name="is_finished" value="1" checked> 已完结</span></label>
                                <label><span><input type="checkbox" name="is_vip" value="1" checked> 收费</span></label>
                            </div>
                        </section>
                        <footer>
                            <button class="btn ghost" type="button" data-admin-drawer-close>取消</button>
                            <button class="btn primary" type="submit">新增短剧</button>
                        </footer>
                    </form>
                </aside>
            </div>
        </section>

        <section class="panel admin-section admin-tool-section <?= $activeAdminSection === 'episodes' ? 'is-active' : '' ?>" id="admin-section-episodes" data-admin-section="episodes" data-admin-primary="works">
            <div class="admin-page-head">
                <div>
                    <span class="eyebrow">作品管理</span>
                    <h2>分集/章节管理</h2>
                    <p class="muted"><?= number_format(count($filteredContentUnitRows)) ?> / <?= number_format(count($contentUnitRows)) ?> 个单元 · 查询和批量维护优先，新增收进抽屉。</p>
                </div>
                <div class="admin-page-actions">
                    <button class="btn primary" type="button" data-admin-drawer-open="drawer-create-episode" <?= empty($dramas) ? 'disabled' : '' ?>><?= jx_icon('drama') ?><span>新增分集</span></button>
                    <button class="btn ghost" type="button" data-admin-drawer-open="drawer-create-chapter" <?= empty($novels) ? 'disabled' : '' ?>><?= jx_icon('account') ?><span>新增章节</span></button>
                </div>
            </div>
            <form class="order-filter-bar" method="get" action="/jxdjadmin">
                <input type="hidden" name="admin_section" value="episodes">
                <label>类型
                    <select name="unit_type">
                        <option value="all">全部</option>
                        <option value="drama" <?= $unitFilters['type'] === 'drama' ? 'selected' : '' ?>>短剧分集</option>
                        <option value="novel" <?= $unitFilters['type'] === 'novel' ? 'selected' : '' ?>>小说章节</option>
                    </select>
                </label>
                <label>作品ID<input name="unit_parent_id" value="<?= $unitFilters['parent_id'] > 0 ? (int) $unitFilters['parent_id'] : '' ?>" placeholder="按作品ID筛选"></label>
                <label>状态
                    <select name="unit_status">
                        <option value="all">全部</option>
                        <?php foreach ($workStatusLabels as $statusValue => $statusLabel): ?>
                            <option value="<?= htmlspecialchars($statusValue) ?>" <?= $unitFilters['status'] === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>搜索<input name="unit_keyword" value="<?= htmlspecialchars($unitFilters['keyword']) ?>" placeholder="作品名 / 单元名 / ID"></label>
                <div class="order-filter-actions">
                    <button class="btn primary" type="submit">查询</button>
                    <a class="btn ghost" href="/jxdjadmin?admin_section=episodes#episodes">重置</a>
                </div>
            </form>
            <div class="admin-drawer" id="drawer-create-episode" data-admin-drawer hidden>
                <div class="admin-drawer-backdrop" data-admin-drawer-close></div>
                <aside class="admin-drawer-card" role="dialog" aria-modal="true" aria-labelledby="drawer-create-episode-title">
                    <header class="admin-drawer-head">
                        <div>
                            <span class="eyebrow">新增分集</span>
                            <h3 id="drawer-create-episode-title">新增短剧分集</h3>
                            <p>选择短剧后填写标题、价格和视频地址。</p>
                        </div>
                        <button type="button" data-admin-drawer-close aria-label="关闭">×</button>
                    </header>
                    <form method="post" action="/jxdjadmin#episodes" class="admin-drawer-form">
                        <input type="hidden" name="admin_action" value="create_episode">
                        <input type="hidden" name="admin_section" value="episodes">
                        <?= $csrfField() ?>
                        <section>
                            <h4>分集信息</h4>
                            <div class="form-grid">
                                <label>短剧
                                    <select name="drama_id">
                                        <?php foreach ((array) ($dramas ?? []) as $dramaOption): ?>
                                            <option value="<?= (int) ($dramaOption['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($dramaOption['title'] ?? '短剧')) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>标题<input name="title" placeholder="第N集"></label>
                                <label>排序<input name="sort" value="1"></label>
                                <label>时长<input name="duration" value="03:00"></label>
                                <label>K币价<input name="coin_price" value="199"></label>
                                <label>状态
                                    <select name="status">
                                        <?php foreach ($workStatusLabels as $statusValue => $statusLabel): ?>
                                            <option value="<?= htmlspecialchars($statusValue) ?>"><?= htmlspecialchars($statusLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="admin-form-span-2">视频地址<input name="video_url" placeholder="https://...mp4"></label>
                            </div>
                            <div class="admin-check-grid">
                                <label><span><input type="checkbox" name="is_free" value="1"> 免费试看</span></label>
                            </div>
                        </section>
                        <footer>
                            <button class="btn ghost" type="button" data-admin-drawer-close>取消</button>
                            <button class="btn primary" type="submit" <?= empty($dramas) ? 'disabled' : '' ?>>新增分集</button>
                        </footer>
                    </form>
                </aside>
            </div>
            <div class="admin-drawer" id="drawer-create-chapter" data-admin-drawer hidden>
                <div class="admin-drawer-backdrop" data-admin-drawer-close></div>
                <aside class="admin-drawer-card" role="dialog" aria-modal="true" aria-labelledby="drawer-create-chapter-title">
                    <header class="admin-drawer-head">
                        <div>
                            <span class="eyebrow">新增章节</span>
                            <h3 id="drawer-create-chapter-title">新增小说章节</h3>
                            <p>小说章节仍复用原有提交动作。</p>
                        </div>
                        <button type="button" data-admin-drawer-close aria-label="关闭">×</button>
                    </header>
                    <form method="post" action="/jxdjadmin#episodes" class="admin-drawer-form">
                        <input type="hidden" name="admin_action" value="create_novel_chapter">
                        <input type="hidden" name="admin_section" value="episodes">
                        <?= $csrfField() ?>
                        <section>
                            <h4>章节信息</h4>
                            <div class="form-grid">
                                <label>小说
                                    <select name="novel_id">
                                        <?php foreach ($novels as $novelOption): ?>
                                            <option value="<?= (int) ($novelOption['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($novelOption['title'] ?? '小说')) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>标题<input name="title" placeholder="第N章"></label>
                                <label>排序<input name="sort" value="1"></label>
                                <label>字数<input name="word_count" placeholder="不填自动估算"></label>
                                <label>K币价<input name="coin_price" value="99"></label>
                                <label>状态
                                    <select name="status">
                                        <?php foreach ($workStatusLabels as $statusValue => $statusLabel): ?>
                                            <option value="<?= htmlspecialchars($statusValue) ?>"><?= htmlspecialchars($statusLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="admin-form-span-2">正文<textarea name="content" rows="5" placeholder="粘贴章节正文"></textarea></label>
                            </div>
                            <div class="admin-check-grid">
                                <label><span><input type="checkbox" name="is_free" value="1"> 免费试看</span></label>
                            </div>
                        </section>
                        <footer>
                            <button class="btn ghost" type="button" data-admin-drawer-close>取消</button>
                            <button class="btn primary" type="submit" <?= empty($novels) ? 'disabled' : '' ?>>新增章节</button>
                        </footer>
                    </form>
                </aside>
            </div>
            <form method="post" action="/jxdjadmin#episodes" class="row-card stack">
                <input type="hidden" name="admin_action" value="bulk_update_content_units">
                <input type="hidden" name="admin_section" value="episodes">
                <?= $csrfField() ?>
                <div class="form-grid">
                    <label>批量状态
                        <select name="bulk_status">
                            <option value="keep">不修改状态</option>
                            <?php foreach ($workStatusLabels as $statusValue => $statusLabel): ?>
                                <option value="<?= htmlspecialchars($statusValue) ?>"><?= htmlspecialchars($statusLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>批量试看
                        <select name="bulk_free_mode">
                            <option value="keep">不修改免费</option>
                            <option value="free">设为免费</option>
                            <option value="paid">设为收费</option>
                        </select>
                    </label>
                    <label>批量K币价<input name="bulk_coin_price" placeholder="留空不修改"></label>
                </div>
                <div class="order-table">
                    <div class="row-card order-row-head">
                        <span>选择/单元</span>
                        <span>作品</span>
                        <span>状态/收费</span>
                        <span>地址/正文</span>
                    </div>
                    <?php if (empty($filteredContentUnitRows)): ?>
                        <div class="empty">暂无符合条件的分集或章节。</div>
                    <?php endif; ?>
                    <?php foreach (array_slice($filteredContentUnitRows, 0, 160) as $unitRow): ?>
                        <?php $unitStatus = (string) ($unitRow['status'] ?? 'draft'); ?>
                        <div class="row-card order-row">
                            <span>
                                <label><span><input type="checkbox" name="unit_keys[]" value="<?= htmlspecialchars((string) ($unitRow['key'] ?? '')) ?>"> <?= htmlspecialchars((string) ($unitRow['title'] ?? '未命名单元')) ?></span></label>
                                <em><?= htmlspecialchars((string) ($unitRow['type_label'] ?? '内容单元')) ?> #<?= number_format((int) ($unitRow['unit_id'] ?? 0)) ?> · 排序 <?= number_format((int) ($unitRow['sort'] ?? 0)) ?></em>
                            </span>
                            <span>
                                <strong><?= htmlspecialchars((string) ($unitRow['content_title'] ?? '作品')) ?></strong>
                                <em>作品 ID <?= number_format((int) ($unitRow['content_id'] ?? 0)) ?></em>
                            </span>
                            <span>
                                <span class="pill <?= $unitStatus === 'online' ? 'green' : ($unitStatus === 'offline' ? 'orange' : 'blue') ?>"><?= htmlspecialchars($workStatusLabels[$unitStatus] ?? $unitStatus) ?></span>
                                <em><?= !empty($unitRow['is_free']) ? '免费试看' : '收费' ?> · <?= number_format((int) ($unitRow['coin_price'] ?? 0)) ?> K币</em>
                                <?php if ((string) ($unitRow['duration'] ?? '') !== ''): ?><em>时长 <?= htmlspecialchars((string) ($unitRow['duration'] ?? '')) ?></em><?php endif; ?>
                                <?php if ((int) ($unitRow['word_count'] ?? 0) > 0): ?><em>字数 <?= number_format((int) ($unitRow['word_count'] ?? 0)) ?></em><?php endif; ?>
                            </span>
                            <span>
                                <?php if ((string) ($unitRow['url'] ?? '') !== ''): ?><em><?= htmlspecialchars((string) ($unitRow['url'] ?? '')) ?></em><?php endif; ?>
                                <?php if ((string) ($unitRow['content'] ?? '') !== ''): ?><em><?= htmlspecialchars($truncate((string) ($unitRow['content'] ?? ''), 80)) ?></em><?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p><button class="btn" type="submit">批量保存选中单元</button></p>
            </form>
        </section>

        <section class="panel admin-section <?= $activeAdminSection === 'novels' ? 'is-active' : '' ?>" id="admin-section-novels" data-admin-section="novels" data-admin-primary="works">
        <div class="section-title admin-section-title">
            <div>
                <span class="eyebrow">小说内容</span>
                <h2>小说管理</h2>
            </div>
            <span class="muted"><?= number_format(count($novels)) ?> 本小说 · 可接投放入口</span>
        </div>
        <form method="post" action="/jxdjadmin" class="stack">
            <input type="hidden" name="admin_action" value="create_novel">
                <?= $csrfField() ?>
            <input type="hidden" name="admin_section" value="novels">
            <div class="form-grid">
                <label>新小说名称<input name="title" placeholder="例如：离婚后我成了首富"></label>
                <label>封面地址<input name="cover" value="/assets/cover-1.svg"></label>
                <label>作者<input name="author" placeholder="作者/版权方"></label>
                <label>简介<input name="description" placeholder="投流承接简介"></label>
                <label>分类
                    <select name="category">
                        <?php foreach ($novelCategoryOptions as $categoryOption): ?>
                            <option value="<?= htmlspecialchars($categoryOption) ?>"><?= htmlspecialchars($categoryOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>标签<input name="tags" placeholder="逆袭,甜宠,爽文"></label>
                <label>章节K币价<input name="chapter_coin_price" value="99"></label>
                <label>免费章数<input name="free_chapter_count" value="3"></label>
                <label>收费起始<input name="buy_start" value="3"></label>
                <label>整本价格<input name="full_unlock_price" value="19.9"></label>
                <label>阅读总量<input name="read_count" value="0"></label>
                <label>质量
                    <select name="quality">
                        <?php foreach ($workQualityLabels as $qualityValue => $qualityLabel): ?>
                            <option value="<?= htmlspecialchars($qualityValue) ?>"><?= htmlspecialchars($qualityLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>排序<input name="sort" value="0"></label>
                <label>状态
                    <select name="status">
                        <option value="draft">草稿</option>
                        <option value="online">上架</option>
                        <option value="offline">下架</option>
                    </select>
                </label>
            </div>
            <div class="form-grid">
                <label><span><input type="checkbox" name="is_hot" value="1" checked> 热门小说</span></label>
                <label><span><input type="checkbox" name="is_new" value="1" checked> 新书推荐</span></label>
                <label><span><input type="checkbox" name="is_finished" value="1" checked> 已完结</span></label>
                <label><span><input type="checkbox" name="is_vip" value="1" checked> 收费</span></label>
            </div>
            <p><button class="btn" type="submit">新增小说</button></p>
        </form>

        <?php foreach ($novels as $novel): ?>
            <?php $chapters = array_values((array) ($novel['chapters'] ?? [])); ?>
            <form method="post" action="/jxdjadmin" class="row-card stack">
                <input type="hidden" name="admin_action" value="update_novel">
                <?= $csrfField() ?>
                <input type="hidden" name="admin_section" value="novels">
                <input type="hidden" name="novel_id" value="<?= (int) ($novel['id'] ?? 0) ?>">
                <p><strong><?= htmlspecialchars((string) ($novel['title'] ?? '未命名小说')) ?></strong> <span class="muted">章节 <?= number_format(count($chapters)) ?> · <?= htmlspecialchars((string) ($novel['status'] ?? 'draft')) ?></span></p>
                <div class="form-grid">
                    <label>名称<input name="title" value="<?= htmlspecialchars((string) ($novel['title'] ?? '')) ?>"></label>
                    <label>封面地址<input name="cover" value="<?= htmlspecialchars((string) ($novel['cover'] ?? '')) ?>"></label>
                    <label>作者<input name="author" value="<?= htmlspecialchars((string) ($novel['author'] ?? '')) ?>"></label>
                    <label>简介<input name="description" value="<?= htmlspecialchars((string) ($novel['description'] ?? '')) ?>"></label>
                    <label>分类
                        <select name="category">
                            <?php foreach ($novelCategoryOptions as $categoryOption): ?>
                                <option value="<?= htmlspecialchars($categoryOption) ?>" <?= (string) ($novel['category'] ?? '') === $categoryOption ? 'selected' : '' ?>><?= htmlspecialchars($categoryOption) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>标签<input name="tags" value="<?= htmlspecialchars(implode(',', (array) ($novel['tags'] ?? []))) ?>"></label>
                    <label>章节K币价<input name="chapter_coin_price" value="<?= htmlspecialchars((string) ($novel['chapter_coin_price'] ?? 99)) ?>"></label>
                    <label>免费章数<input name="free_chapter_count" value="<?= htmlspecialchars((string) ($novel['free_chapter_count'] ?? 3)) ?>"></label>
                    <label>收费起始<input name="buy_start" value="<?= htmlspecialchars((string) ($novel['buy_start'] ?? $novel['free_chapter_count'] ?? 3)) ?>"></label>
                    <label>整本价格<input name="full_unlock_price" value="<?= htmlspecialchars((string) ($novel['full_unlock_price'] ?? 19.9)) ?>"></label>
                    <label>阅读总量<input name="read_count" value="<?= htmlspecialchars((string) ($novel['read_count'] ?? 0)) ?>"></label>
                    <label>质量
                        <select name="quality">
                            <?php foreach ($workQualityLabels as $qualityValue => $qualityLabel): ?>
                                <option value="<?= htmlspecialchars($qualityValue) ?>" <?= (string) ($novel['quality'] ?? 'normal') === $qualityValue ? 'selected' : '' ?>><?= htmlspecialchars($qualityLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>排序<input name="sort" value="<?= htmlspecialchars((string) ($novel['sort'] ?? 0)) ?>"></label>
                    <label>状态
                        <select name="status">
                            <?php foreach (['draft' => '草稿', 'online' => '上架', 'offline' => '下架'] as $statusValue => $statusLabel): ?>
                                <option value="<?= htmlspecialchars($statusValue) ?>" <?= (string) ($novel['status'] ?? 'draft') === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div class="form-grid">
                    <label><span><input type="checkbox" name="is_hot" value="1" <?= !empty($novel['is_hot']) ? 'checked' : '' ?>> 热门小说</span></label>
                    <label><span><input type="checkbox" name="is_new" value="1" <?= !empty($novel['is_new']) ? 'checked' : '' ?>> 新书推荐</span></label>
                    <label><span><input type="checkbox" name="is_finished" value="1" <?= !empty($novel['is_finished']) ? 'checked' : '' ?>> 已完结</span></label>
                    <label><span><input type="checkbox" name="is_vip" value="1" <?= !empty($novel['is_vip']) ? 'checked' : '' ?>> 收费</span></label>
                </div>
                <p><button class="btn" type="submit">保存小说</button></p>
            </form>

            <form method="post" action="/jxdjadmin" class="row-card stack">
                <input type="hidden" name="admin_action" value="create_novel_chapter">
                <?= $csrfField() ?>
                <input type="hidden" name="admin_section" value="novels">
                <input type="hidden" name="novel_id" value="<?= (int) ($novel['id'] ?? 0) ?>">
                <p><strong><?= htmlspecialchars((string) ($novel['title'] ?? '小说')) ?> / 新增章节</strong></p>
                <div class="form-grid">
                    <label>章节标题<input name="title" placeholder="第<?= number_format(count($chapters) + 1) ?>章"></label>
                    <label>排序<input name="sort" value="<?= number_format(count($chapters) + 1) ?>"></label>
                    <label>字数<input name="word_count" placeholder="不填自动按正文估算"></label>
                    <label>K币价<input name="coin_price" value="<?= htmlspecialchars((string) ($novel['chapter_coin_price'] ?? 99)) ?>"></label>
                    <label>状态
                        <select name="status">
                            <option value="draft">草稿</option>
                            <option value="online">上架</option>
                            <option value="offline">下架</option>
                        </select>
                    </label>
                </div>
                <label>正文<textarea name="content" placeholder="粘贴章节正文"></textarea></label>
                <label><span><input type="checkbox" name="is_free" value="1"> 免费试看</span></label>
                <p><button class="btn" type="submit">新增章节</button></p>
            </form>

            <?php foreach ($chapters as $chapter): ?>
                <form method="post" action="/jxdjadmin" class="row-card stack">
                    <input type="hidden" name="admin_action" value="update_novel_chapter">
                <?= $csrfField() ?>
                    <input type="hidden" name="admin_section" value="novels">
                    <input type="hidden" name="novel_id" value="<?= (int) ($novel['id'] ?? 0) ?>">
                    <input type="hidden" name="chapter_id" value="<?= (int) ($chapter['id'] ?? 0) ?>">
                    <div class="form-grid">
                        <label>标题<input name="title" value="<?= htmlspecialchars((string) ($chapter['title'] ?? '')) ?>"></label>
                        <label>排序<input name="sort" value="<?= htmlspecialchars((string) ($chapter['sort'] ?? 0)) ?>"></label>
                        <label>字数<input name="word_count" value="<?= htmlspecialchars((string) ($chapter['word_count'] ?? 0)) ?>"></label>
                        <label>K币价<input name="coin_price" value="<?= htmlspecialchars((string) ($chapter['coin_price'] ?? $novel['chapter_coin_price'] ?? 99)) ?>"></label>
                        <label>状态
                            <select name="status">
                                <?php foreach (['draft' => '草稿', 'online' => '上架', 'offline' => '下架'] as $statusValue => $statusLabel): ?>
                                    <option value="<?= htmlspecialchars($statusValue) ?>" <?= (string) ($chapter['status'] ?? 'draft') === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                    <label>正文<textarea name="content"><?= htmlspecialchars((string) ($chapter['content'] ?? '')) ?></textarea></label>
                    <label><span><input type="checkbox" name="is_free" value="1" <?= !empty($chapter['is_free']) ? 'checked' : '' ?>> 免费试看</span></label>
                    <p><button class="btn" type="submit">保存章节</button></p>
                </form>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </section>

        <?php foreach ([
            ['id' => 'media-wallpapers', 'type' => 'image', 'title' => '编辑壁纸', 'unit' => '单张价', 'placeholder' => '图片或图集资源地址'],
            ['id' => 'media-h5', 'type' => 'h5', 'title' => '编辑H5', 'unit' => '千字价/页价', 'placeholder' => 'H5页面地址'],
        ] as $mediaSection): ?>
            <?php
                $mediaSectionRows = array_values(array_filter($mediaContents, static fn (array $item): bool => (string) ($item['type'] ?? 'image') === (string) $mediaSection['type']));
            ?>
            <section class="panel admin-section <?= $activeAdminSection === $mediaSection['id'] ? 'is-active' : '' ?>" id="admin-section-<?= htmlspecialchars((string) $mediaSection['id']) ?>" data-admin-section="<?= htmlspecialchars((string) $mediaSection['id']) ?>" data-admin-primary="works">
                <div class="section-title admin-section-title">
                    <div>
                        <span class="eyebrow">作品管理</span>
                        <h2><?= htmlspecialchars((string) $mediaSection['title']) ?></h2>
                    </div>
                    <span class="muted"><?= number_format(count($mediaSectionRows)) ?> 个作品 · 资源地址/封面/收费信息</span>
                </div>
                <form method="post" action="/jxdjadmin#<?= htmlspecialchars((string) $mediaSection['id']) ?>" class="row-card stack">
                    <input type="hidden" name="admin_action" value="save_media_content">
                    <input type="hidden" name="admin_section" value="<?= htmlspecialchars((string) $mediaSection['id']) ?>">
                    <input type="hidden" name="media_type" value="<?= htmlspecialchars((string) $mediaSection['type']) ?>">
                    <?= $csrfField() ?>
                    <p><strong>新增<?= htmlspecialchars((string) $mediaSection['title']) ?></strong></p>
                    <div class="form-grid">
                        <label>名称<input name="title" placeholder="<?= (string) $mediaSection['type'] === 'h5' ? '互动H5标题' : '壁纸标题' ?>"></label>
                        <label>分类
                            <select name="category">
                                <?php foreach ($mediaCategoryOptions as $categoryOption): ?>
                                    <option value="<?= htmlspecialchars($categoryOption) ?>"><?= htmlspecialchars($categoryOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>标签<input name="tags" placeholder="标签，逗号分隔"></label>
                        <label>作者/版权方<input name="author"></label>
                        <label><?= htmlspecialchars((string) $mediaSection['unit']) ?><input name="price_coins" value="99"></label>
                        <label>收费起始<input name="buy_start" value="1"></label>
                        <label>阅读总量<input name="read_count" value="0"></label>
                        <label>质量
                            <select name="quality">
                                <?php foreach ($workQualityLabels as $qualityValue => $qualityLabel): ?>
                                    <option value="<?= htmlspecialchars($qualityValue) ?>"><?= htmlspecialchars($qualityLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>状态
                            <select name="status">
                                <?php foreach ($workStatusLabels as $statusValue => $statusLabel): ?>
                                    <option value="<?= htmlspecialchars($statusValue) ?>"><?= htmlspecialchars($statusLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>排序<input name="sort" value="100"></label>
                    </div>
                    <label>封面地址<input name="cover" placeholder="/assets/cover-1.svg"></label>
                    <label>资源地址<input name="resource_url" placeholder="<?= htmlspecialchars((string) $mediaSection['placeholder']) ?>"></label>
                    <label>简介<textarea name="description" rows="4" placeholder="内容介绍、授权说明或投放备注"></textarea></label>
                    <div class="form-grid">
                        <label><span><input type="checkbox" name="is_finished" value="1" checked> 完结</span></label>
                        <label><span><input type="checkbox" name="is_vip" value="1" checked> 收费</span></label>
                    </div>
                    <p><button class="btn" type="submit">保存作品</button></p>
                </form>
                <div class="order-table">
                    <div class="row-card order-row-head">
                        <span>作品</span>
                        <span>分类/资源</span>
                        <span>收费/状态</span>
                        <span>操作</span>
                    </div>
                    <?php if (empty($mediaSectionRows)): ?>
                        <div class="empty">暂无<?= htmlspecialchars((string) $mediaSection['title']) ?>作品。</div>
                    <?php endif; ?>
                    <?php foreach ($mediaSectionRows as $mediaItem): ?>
                        <?php $mediaStatus = (string) ($mediaItem['status'] ?? 'draft'); $mediaQuality = (string) ($mediaItem['quality'] ?? 'normal'); ?>
                        <form method="post" action="/jxdjadmin#<?= htmlspecialchars((string) $mediaSection['id']) ?>" class="row-card order-row">
                            <input type="hidden" name="admin_action" value="save_media_content">
                            <input type="hidden" name="admin_section" value="<?= htmlspecialchars((string) $mediaSection['id']) ?>">
                            <input type="hidden" name="media_type" value="<?= htmlspecialchars((string) $mediaSection['type']) ?>">
                            <input type="hidden" name="media_id" value="<?= (int) ($mediaItem['id'] ?? 0) ?>">
                            <?= $csrfField() ?>
                            <span>
                                <strong><input name="title" value="<?= htmlspecialchars((string) ($mediaItem['title'] ?? '')) ?>"></strong>
                                <em>ID <?= number_format((int) ($mediaItem['id'] ?? 0)) ?> · <?= htmlspecialchars((string) (($mediaItem['author'] ?? '') ?: '未填版权方')) ?></em>
                                <em><input name="author" value="<?= htmlspecialchars((string) ($mediaItem['author'] ?? '')) ?>" placeholder="作者/版权方"></em>
                            </span>
                            <span>
                                <select name="category">
                                    <?php foreach ($mediaCategoryOptions as $categoryOption): ?>
                                        <option value="<?= htmlspecialchars($categoryOption) ?>" <?= (string) ($mediaItem['category'] ?? '') === $categoryOption ? 'selected' : '' ?>><?= htmlspecialchars($categoryOption) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <em><input name="tags" value="<?= htmlspecialchars(implode(',', (array) ($mediaItem['tags'] ?? []))) ?>" placeholder="标签"></em>
                                <em><input name="cover" value="<?= htmlspecialchars((string) ($mediaItem['cover'] ?? '')) ?>" placeholder="封面地址"></em>
                                <em><input name="resource_url" value="<?= htmlspecialchars((string) ($mediaItem['resource_url'] ?? '')) ?>" placeholder="<?= htmlspecialchars((string) $mediaSection['placeholder']) ?>"></em>
                            </span>
                            <span>
                                <input name="price_coins" value="<?= htmlspecialchars((string) ($mediaItem['price_coins'] ?? 99)) ?>" placeholder="K币价">
                                <em><input name="buy_start" value="<?= htmlspecialchars((string) ($mediaItem['buy_start'] ?? 1)) ?>" placeholder="收费起始"></em>
                                <em><input name="read_count" value="<?= htmlspecialchars((string) ($mediaItem['read_count'] ?? 0)) ?>" placeholder="阅读总量"></em>
                                <em>
                                    <select name="quality">
                                        <?php foreach ($workQualityLabels as $qualityValue => $qualityLabel): ?>
                                            <option value="<?= htmlspecialchars($qualityValue) ?>" <?= $mediaQuality === $qualityValue ? 'selected' : '' ?>><?= htmlspecialchars($qualityLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </em>
                                <em>
                                    <select name="status">
                                        <?php foreach ($workStatusLabels as $statusValue => $statusLabel): ?>
                                            <option value="<?= htmlspecialchars($statusValue) ?>" <?= $mediaStatus === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </em>
                            </span>
                            <span>
                                <textarea name="description" rows="3" placeholder="简介"><?= htmlspecialchars((string) ($mediaItem['description'] ?? '')) ?></textarea>
                                <em><label><span><input type="checkbox" name="is_finished" value="1" <?= !empty($mediaItem['is_finished']) ? 'checked' : '' ?>> 完结</span></label> <label><span><input type="checkbox" name="is_vip" value="1" <?= !empty($mediaItem['is_vip']) ? 'checked' : '' ?>> 收费</span></label></em>
                                <em><input name="sort" value="<?= htmlspecialchars((string) ($mediaItem['sort'] ?? 100)) ?>" placeholder="排序"></em>
                                <button class="btn ghost" type="submit">保存</button>
                            </span>
                        </form>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <section class="panel admin-section <?= $activeAdminSection === 'content-tags' ? 'is-active' : '' ?>" id="admin-section-content-tags" data-admin-section="content-tags" data-admin-primary="works">
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">内容库</span>
                    <h2>分类/标签</h2>
                </div>
                <span class="muted"><?= number_format(count($contentTags)) ?> 个标签 · <?= number_format(count($contentGroups)) ?> 个分组 · <?= number_format(count($contentImportLogs)) ?> 次导入</span>
            </div>
            <form method="post" action="/jxdjadmin#content-tags" class="row-card stack">
                <input type="hidden" name="admin_action" value="import_content_batch">
                <input type="hidden" name="admin_section" value="content-tags">
                <?= $csrfField() ?>
                <p><strong>批量导入短剧/小说</strong></p>
                <div class="form-grid">
                    <label>默认分类<input name="default_category" value="都市"></label>
                    <label>默认标签<input name="default_tags" placeholder="逆袭,甜宠,投流"></label>
                    <label>默认分组
                        <select name="default_group_id">
                            <option value="0">不指定分组</option>
                            <?php foreach ($contentGroups as $group): ?>
                                <option value="<?= (int) ($group['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($group['name'] ?? '内容分组')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>导入来源<input name="source" value="manual"></label>
                    <label>默认状态
                        <select name="default_status">
                            <option value="draft">草稿</option>
                            <option value="online">上架</option>
                            <option value="offline">下架</option>
                        </select>
                    </label>
                    <label>审核状态
                        <select name="default_audit_status">
                            <?php foreach ($contentAuditLabels as $statusValue => $statusLabel): ?>
                                <option value="<?= htmlspecialchars($statusValue) ?>"><?= htmlspecialchars($statusLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <label>导入 JSON
                    <textarea name="content_import_text" rows="8" placeholder='[{"type":"drama","title":"逆袭新剧","category":"都市","episodes":[{"title":"第1集","video_url":"https://example.com/1.mp4","is_free":true}]},{"type":"novel","title":"离婚后我成了首富2","author":"精秀作者","chapters":[{"title":"第1章","content":"章节正文","is_free":true}]}]'></textarea>
                </label>
                <p><button class="btn" type="submit">导入内容</button></p>
            </form>
            <?php if (!empty($contentImportLogs)): ?>
                <div class="order-table">
                    <div class="row-card order-row-head">
                        <span>批次</span>
                        <span>结果</span>
                        <span>数量</span>
                        <span>错误</span>
                    </div>
                    <?php foreach (array_slice($contentImportLogs, 0, 8) as $log): ?>
                        <?php
                            $importStatus = (string) ($log['status'] ?? 'success');
                            $importStatusLabel = ['success' => '成功', 'partial' => '部分成功', 'failed' => '失败'][$importStatus] ?? $importStatus;
                            $importErrors = array_slice((array) ($log['errors'] ?? []), 0, 3);
                        ?>
                        <div class="row-card order-row">
                            <span>
                                <strong><?= htmlspecialchars((string) ($log['batch_no'] ?? '导入批次')) ?></strong>
                                <em><?= htmlspecialchars((string) ($log['created_at'] ?? '')) ?> · <?= htmlspecialchars((string) ($log['admin_name'] ?? '管理员')) ?></em>
                            </span>
                            <span>
                                <span class="pill <?= $importStatus === 'failed' ? 'orange' : ($importStatus === 'partial' ? 'blue' : 'green') ?>"><?= htmlspecialchars($importStatusLabel) ?></span>
                                <em><?= htmlspecialchars((string) ($log['message'] ?? '')) ?></em>
                            </span>
                            <span>
                                成功 <?= number_format((int) ($log['success_count'] ?? 0)) ?> / <?= number_format((int) ($log['total_count'] ?? 0)) ?>
                                <em>短剧 <?= number_format((int) ($log['drama_count'] ?? 0)) ?> · 分集 <?= number_format((int) ($log['episode_count'] ?? 0)) ?></em>
                                <em>小说 <?= number_format((int) ($log['novel_count'] ?? 0)) ?> · 章节 <?= number_format((int) ($log['chapter_count'] ?? 0)) ?></em>
                            </span>
                            <span>
                                <?php if (empty($importErrors)): ?>
                                    <em>无错误</em>
                                <?php else: ?>
                                    <?php foreach ($importErrors as $error): ?>
                                        <em>第 <?= number_format((int) ($error['row'] ?? 0)) ?> 行：<?= htmlspecialchars((string) ($error['message'] ?? '导入失败')) ?></em>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="post" action="/jxdjadmin" class="row-card stack">
                <input type="hidden" name="admin_action" value="save_content_tag">
                <input type="hidden" name="admin_section" value="content-tags">
                <?= $csrfField() ?>
                <p><strong>新增内容标签</strong></p>
                <div class="form-grid">
                    <label>标签名称<input name="name" placeholder="例如：逆袭 / 甜宠 / 爽文"></label>
                    <label>颜色<input name="color" value="#64748b"></label>
                    <label>排序<input name="sort" value="100"></label>
                    <label>状态
                        <select name="status">
                            <option value="active">启用</option>
                            <option value="paused">停用</option>
                        </select>
                    </label>
                </div>
                <p><button class="btn" type="submit">保存标签</button></p>
            </form>
            <form method="post" action="/jxdjadmin" class="row-card stack">
                <input type="hidden" name="admin_action" value="save_content_group">
                <input type="hidden" name="admin_section" value="content-tags">
                <?= $csrfField() ?>
                <p><strong>新增内容分组</strong></p>
                <div class="form-grid">
                    <label>分组名称<input name="name" placeholder="例如：女频投流池 / 审核安全池"></label>
                    <label>说明<input name="description" placeholder="用途、投放团队或素材方向"></label>
                    <label>排序<input name="sort" value="100"></label>
                    <label>状态
                        <select name="status">
                            <option value="active">启用</option>
                            <option value="paused">停用</option>
                        </select>
                    </label>
                </div>
                <p><button class="btn" type="submit">保存分组</button></p>
            </form>
            <div class="order-table">
                <div class="row-card order-row-head">
                    <span>标签</span>
                    <span>状态</span>
                    <span>分组</span>
                    <span>说明</span>
                </div>
                <?php foreach ($contentTags as $tag): ?>
                    <form method="post" action="/jxdjadmin" class="row-card order-row">
                        <input type="hidden" name="admin_action" value="save_content_tag">
                        <input type="hidden" name="admin_section" value="content-tags">
                        <input type="hidden" name="tag_id" value="<?= (int) ($tag['id'] ?? 0) ?>">
                        <?= $csrfField() ?>
                        <span><strong><input name="name" value="<?= htmlspecialchars((string) ($tag['name'] ?? '标签')) ?>"></strong><em><input name="color" value="<?= htmlspecialchars((string) ($tag['color'] ?? '#64748b')) ?>"></em></span>
                        <span><select name="status"><option value="active" <?= (string) ($tag['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>启用</option><option value="paused" <?= (string) ($tag['status'] ?? 'active') === 'paused' ? 'selected' : '' ?>>停用</option></select></span>
                        <span>标签排序 <input name="sort" value="<?= htmlspecialchars((string) ($tag['sort'] ?? 100)) ?>"></span>
                        <span><button class="btn ghost" type="submit">保存</button></span>
                    </form>
                <?php endforeach; ?>
                <?php foreach ($contentGroups as $group): ?>
                    <form method="post" action="/jxdjadmin" class="row-card order-row">
                        <input type="hidden" name="admin_action" value="save_content_group">
                        <input type="hidden" name="admin_section" value="content-tags">
                        <input type="hidden" name="group_id" value="<?= (int) ($group['id'] ?? 0) ?>">
                        <?= $csrfField() ?>
                        <span><strong><input name="name" value="<?= htmlspecialchars((string) ($group['name'] ?? '内容分组')) ?>"></strong><em>内容分组</em></span>
                        <span><select name="status"><option value="active" <?= (string) ($group['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>启用</option><option value="paused" <?= (string) ($group['status'] ?? 'active') === 'paused' ? 'selected' : '' ?>>停用</option></select></span>
                        <span>排序 <input name="sort" value="<?= htmlspecialchars((string) ($group['sort'] ?? 100)) ?>"></span>
                        <span><input name="description" value="<?= htmlspecialchars((string) ($group['description'] ?? '')) ?>" placeholder="说明"><button class="btn ghost" type="submit">保存</button></span>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel admin-section <?= $activeAdminSection === 'shelf-review' ? 'is-active' : '' ?>" id="admin-section-shelf-review" data-admin-section="shelf-review" data-admin-primary="content">
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">内容审核</span>
                    <h2>上下架审核</h2>
                </div>
                <span class="muted"><?= number_format(count($contentLibraryRows)) ?> 个内容 · 短剧/小说统一管理</span>
            </div>
            <div class="order-table">
                <div class="row-card order-row-head">
                    <span>内容</span>
                    <span>标签/分组</span>
                    <span>审核/状态</span>
                    <span>数据</span>
                </div>
                <?php foreach ($contentLibraryRows as $row): ?>
                    <form method="post" action="/jxdjadmin" class="row-card order-row">
                        <input type="hidden" name="admin_action" value="update_content_ops">
                        <input type="hidden" name="admin_section" value="shelf-review">
                        <input type="hidden" name="content_type" value="<?= htmlspecialchars((string) ($row['content_type'] ?? 'drama')) ?>">
                        <input type="hidden" name="content_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                        <?= $csrfField() ?>
                        <span>
                            <strong><?= htmlspecialchars((string) ($row['title'] ?? '未命名内容')) ?></strong>
                            <em><?= htmlspecialchars((string) ($row['content_label'] ?? '内容')) ?> · <?= htmlspecialchars((string) ($row['category'] ?? '未分类')) ?> · <?= number_format((int) ($row['unit_count'] ?? 0)) ?> 单元</em>
                            <em>基础状态：<?= htmlspecialchars((string) ($row['status'] ?? 'draft')) ?></em>
                        </span>
                        <span>
                            <input name="tags" value="<?= htmlspecialchars(implode(',', (array) ($row['tags'] ?? []))) ?>" placeholder="标签用逗号分隔">
                            <em>
                                <select name="group_id">
                                    <option value="0">未分组</option>
                                    <?php foreach ($contentGroups as $group): ?>
                                        <option value="<?= (int) ($group['id'] ?? 0) ?>" <?= (int) ($row['group_id'] ?? 0) === (int) ($group['id'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($group['name'] ?? '内容分组')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </em>
                        </span>
                        <span>
                            <select name="audit_status">
                                <?php foreach ($contentAuditLabels as $statusValue => $statusLabel): ?>
                                    <option value="<?= htmlspecialchars($statusValue) ?>" <?= (string) ($row['audit_status'] ?? 'draft') === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <em><input name="audit_note" value="<?= htmlspecialchars((string) ($row['audit_note'] ?? '')) ?>" placeholder="审核备注"></em>
                            <em><?= htmlspecialchars((string) (($row['reviewed_by'] ?? '') ?: '未审核')) ?> <?= htmlspecialchars((string) (($row['reviewed_at'] ?? '') ?: '')) ?></em>
                        </span>
                        <span>
                            收入 <?= htmlspecialchars($money((float) ($row['revenue'] ?? 0))) ?>
                            <em>订单 <?= number_format((int) ($row['orders'] ?? 0)) ?> · 付费 <?= number_format((int) ($row['paid_orders'] ?? 0)) ?></em>
                            <button class="btn ghost" type="submit">保存审核</button>
                        </span>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel admin-section <?= $activeAdminSection === 'content-comments' ? 'is-active' : '' ?>" id="admin-section-content-comments" data-admin-section="content-comments" data-admin-primary="content">
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">内容互动</span>
                    <h2>评论审核</h2>
                </div>
                <span class="muted">待审 <?= number_format((int) ($commentSummary['pending'] ?? 0)) ?> 条 · 总计 <?= number_format((int) ($commentSummary['total'] ?? count($contentComments))) ?> 条</span>
            </div>
            <div class="kpi-grid">
                <div class="kpi blue">
                    <span class="kpi-icon"><?= jx_icon('message') ?></span>
                    <small>评论总数</small>
                    <strong><?= number_format((int) ($commentSummary['total'] ?? count($contentComments))) ?></strong>
                    <em>短剧/小说评论</em>
                </div>
                <div class="kpi orange">
                    <span class="kpi-icon"><?= jx_icon('order') ?></span>
                    <small>待审核</small>
                    <strong><?= number_format((int) ($commentSummary['pending'] ?? 0)) ?></strong>
                    <em>需要人工处理</em>
                </div>
                <div class="kpi green">
                    <span class="kpi-icon"><?= jx_icon('stats') ?></span>
                    <small>已通过</small>
                    <strong><?= number_format((int) ($commentSummary['approved'] ?? 0)) ?></strong>
                    <em>前台可展示</em>
                </div>
                <div class="kpi cyan">
                    <span class="kpi-icon"><?= jx_icon('revenue') ?></span>
                    <small>平均评分</small>
                    <strong><?= number_format((float) ($commentSummary['avg_rating'] ?? 0), 2) ?></strong>
                    <em>5 分制</em>
                </div>
            </div>
            <?php if (!empty($commentContentRows)): ?>
                <div class="order-list" style="margin-bottom:16px">
                    <?php foreach (array_slice($commentContentRows, 0, 6) as $row): ?>
                        <div class="order-item">
                            <span>
                                <strong><?= htmlspecialchars((string) ($row['title'] ?? '内容')) ?></strong>
                                <em><?= htmlspecialchars((string) (($row['content_type'] ?? 'drama') === 'novel' ? '小说' : '短剧')) ?> #<?= (int) ($row['content_id'] ?? 0) ?></em>
                            </span>
                            <span>
                                总评 <?= number_format((int) ($row['total'] ?? 0)) ?>
                                <em>待审 <?= number_format((int) ($row['pending'] ?? 0)) ?> · 通过 <?= number_format((int) ($row['approved'] ?? 0)) ?> · 评分 <?= number_format((float) ($row['avg_rating'] ?? 0), 2) ?></em>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="order-list">
                <?php if (empty($contentComments)): ?>
                    <div class="empty">暂无评论。前台或小程序可通过 api-comment-submit 提交，审核通过后由 api-content-comments 返回。</div>
                <?php endif; ?>
                <?php foreach (array_slice($contentComments, 0, 120) as $comment): ?>
                    <?php
                        $commentStatus = (string) ($comment['status'] ?? 'pending');
                        $commentRisk = (string) ($comment['risk_level'] ?? 'normal');
                        $commentSentiment = (string) ($comment['sentiment'] ?? 'neutral');
                        $commentTrafficLines = $trafficMetaLines($comment);
                        $commentStatusClass = match ($commentStatus) {
                            'approved' => 'green',
                            'rejected', 'hidden' => 'orange',
                            default => 'blue',
                        };
                        $commentRiskClass = match ($commentRisk) {
                            'sensitive', 'spam' => 'orange',
                            default => 'green',
                        };
                    ?>
                    <form method="post" action="/jxdjadmin#content-comments" class="order-item">
                        <input type="hidden" name="admin_action" value="update_content_comment">
                        <input type="hidden" name="admin_section" value="content-comments">
                        <input type="hidden" name="comment_id" value="<?= (int) ($comment['id'] ?? 0) ?>">
                        <?= $csrfField() ?>
                        <span>
                            <strong><?= htmlspecialchars((string) ($comment['content_title'] ?? '内容')) ?></strong>
                            <em><?= htmlspecialchars((string) (($comment['content_type'] ?? 'drama') === 'novel' ? '小说' : '短剧')) ?> #<?= (int) ($comment['content_id'] ?? 0) ?><?= (int) ($comment['unit_id'] ?? 0) > 0 ? ' · 单元 #' . (int) ($comment['unit_id'] ?? 0) : '' ?></em>
                            <em><?= htmlspecialchars((string) ($comment['created_at'] ?? '')) ?> · 用户 <?= number_format((int) ($comment['user_id'] ?? 0)) ?> · <?= htmlspecialchars((string) (($comment['nickname'] ?? '') ?: '匿名用户')) ?></em>
                            <?php if (!empty($commentTrafficLines)): ?><em><?= htmlspecialchars(implode(' · ', array_slice($commentTrafficLines, 0, 4))) ?></em><?php endif; ?>
                        </span>
                        <span>
                            <span class="pill <?= htmlspecialchars($commentStatusClass) ?>"><?= htmlspecialchars($commentStatusLabels[$commentStatus] ?? $commentStatus) ?></span>
                            <span class="pill <?= htmlspecialchars($commentRiskClass) ?>"><?= htmlspecialchars($commentRiskLabels[$commentRisk] ?? $commentRisk) ?></span>
                            <em>评分 <?= number_format((int) ($comment['rating'] ?? 5)) ?> · <?= htmlspecialchars($commentSentimentLabels[$commentSentiment] ?? $commentSentiment) ?> · 赞 <?= number_format((int) ($comment['likes'] ?? 0)) ?> · 举报 <?= number_format((int) ($comment['reports'] ?? 0)) ?></em>
                            <em><?= htmlspecialchars((string) ($comment['content'] ?? '')) ?></em>
                            <?php if (!empty($comment['reply'])): ?><em>回复：<?= htmlspecialchars((string) ($comment['reply'] ?? '')) ?></em><?php endif; ?>
                        </span>
                        <span>
                            <select name="status" aria-label="审核状态">
                                <?php foreach ($commentStatusLabels as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= $commentStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <em>
                                <select name="risk_level" aria-label="风险">
                                    <?php foreach ($commentRiskLabels as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= $commentRisk === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </em>
                            <em>
                                <select name="sentiment" aria-label="情绪">
                                    <?php foreach ($commentSentimentLabels as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= $commentSentiment === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </em>
                            <em>评分 <input name="rating" type="number" min="1" max="5" value="<?= (int) ($comment['rating'] ?? 5) ?>" aria-label="评分"></em>
                            <label><input type="checkbox" name="is_pinned" value="1" <?= !empty($comment['is_pinned']) ? 'checked' : '' ?>> 置顶</label>
                            <label><input type="checkbox" name="is_featured" value="1" <?= !empty($comment['is_featured']) ? 'checked' : '' ?>> 精选</label>
                        </span>
                        <span>
                            <textarea name="content" rows="2" aria-label="评论内容"><?= htmlspecialchars((string) ($comment['content'] ?? '')) ?></textarea>
                            <textarea name="reply" rows="2" placeholder="后台回复"><?= htmlspecialchars((string) ($comment['reply'] ?? '')) ?></textarea>
                            <?php if (!empty($comment['reviewed_by_admin_name'])): ?><em><?= htmlspecialchars((string) ($comment['reviewed_by_admin_name'] ?? '')) ?> · <?= htmlspecialchars((string) ($comment['reviewed_at'] ?? '')) ?></em><?php endif; ?>
                            <button class="btn ghost" type="submit">保存审核</button>
                        </span>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel admin-section admin-compact-section <?= $activeAdminSection === 'users' ? 'is-active' : '' ?>" id="admin-section-users" data-admin-section="users" data-admin-primary="users">
        <?php
            $memberUserCount = count(array_filter($users, static fn (array $item): bool => !empty($item['membership'])));
            $guestUserCount = max(0, count($users) - $memberUserCount);
            $totalCoinBalance = array_sum(array_map(static fn (array $item): int => (int) ($item['coin_balance'] ?? 0), $users));
            $totalBonusCoinBalance = array_sum(array_map(static fn (array $item): int => (int) ($item['bonus_coin_balance'] ?? 0), $users));
        ?>
        <div class="section-title admin-page-title">
            <div>
                <span class="eyebrow">用户记录</span>
                <h2>用户权益</h2>
            </div>
            <span class="muted"><?= number_format(count($users)) ?> 个用户 · <?= number_format($memberUserCount) ?> 个会员 · 当前显示 <?= number_format(count($visibleUsers)) ?> 个</span>
        </div>
        <div class="admin-summary-strip">
            <div><strong><?= number_format(count($users)) ?></strong><span>用户总数</span></div>
            <div><strong><?= number_format($memberUserCount) ?></strong><span>会员用户</span></div>
            <div><strong><?= number_format($guestUserCount) ?></strong><span>普通/游客</span></div>
            <div><strong><?= number_format($totalCoinBalance + $totalBonusCoinBalance) ?></strong><span>K币总余额</span></div>
        </div>
        <form class="order-filter-bar admin-filter-bar user-lookup-bar" method="get" action="/jxdjadmin">
            <input type="hidden" name="admin_section" value="users">
            <label>用户类型
                <select name="user_type">
                    <option value="all" <?= $userTypeFilter === 'all' ? 'selected' : '' ?>>全部用户</option>
                    <option value="guest" <?= $userTypeFilter === 'guest' ? 'selected' : '' ?>>普通/游客</option>
                    <option value="member" <?= $userTypeFilter === 'member' ? 'selected' : '' ?>>会员用户</option>
                </select>
            </label>
            <label>用户ID
                <input name="user_id" value="<?= htmlspecialchars((string) ($profileUser['id'] ?? ($_GET['user_id'] ?? ''))) ?>" placeholder="输入用户ID查看画像">
            </label>
            <div class="order-filter-actions">
                <button class="btn primary" type="submit">查看画像</button>
                <a class="btn ghost" href="/jxdjadmin?admin_section=users#users">重置</a>
            </div>
        </form>
        <?php if ($userProfile !== null): ?>
            <?php
                $profileTrafficLines = $trafficMetaLines($profileAttribution);
                $profileOrders = array_slice(array_values((array) ($userProfile['orders'] ?? [])), 0, 8);
                $profileEntitlements = array_slice(array_values((array) ($userProfile['entitlements'] ?? [])), 0, 8);
                $profileCoinTransactions = array_slice(array_values((array) ($userProfile['coin_transactions'] ?? [])), 0, 8);
                $profilePromotionEvents = array_slice(array_values((array) ($userProfile['promotion_events'] ?? [])), 0, 8);
                $profileContentEvents = array_slice(array_values((array) ($userProfile['content_events'] ?? [])), 0, 8);
            ?>
            <div class="row-card stack user-profile-card">
                <div class="section-title admin-page-title">
                    <div>
                        <span class="eyebrow">用户画像</span>
                        <h2><?= htmlspecialchars((string) (($profileUser['nickname'] ?? '') ?: ('用户 ' . ($profileUser['id'] ?? 0)))) ?></h2>
                    </div>
                    <span class="muted">ID <?= (int) ($profileUser['id'] ?? 0) ?> · <?= htmlspecialchars((string) (($profileUser['phone'] ?? '') ?: '未绑定手机')) ?> · <a href="/jxdjadmin?admin_section=rights-repair&user_id=<?= (int) ($profileUser['id'] ?? 0) ?>#rights-repair">权益处理</a></span>
                </div>
                <div class="kpi-grid">
                    <div class="kpi blue">
                        <span class="kpi-icon"><?= jx_icon('orders') ?></span>
                        <small>订单 / 付费</small>
                        <strong><?= number_format((int) ($profileSummary['order_count'] ?? 0)) ?> / <?= number_format((int) ($profileSummary['paid_order_count'] ?? 0)) ?></strong>
                        <em>最近下单 <?= htmlspecialchars((string) (($profileSummary['latest_order_at'] ?? '') ?: '-')) ?></em>
                    </div>
                    <div class="kpi green">
                        <span class="kpi-icon"><?= jx_icon('revenue') ?></span>
                        <small>实收金额</small>
                        <strong><?= htmlspecialchars($money((float) ($profileSummary['net_amount'] ?? 0))) ?></strong>
                        <em>退款 <?= htmlspecialchars($money((float) ($profileSummary['refund_amount'] ?? 0))) ?></em>
                    </div>
                    <div class="kpi orange">
                        <span class="kpi-icon"><?= jx_icon('payment') ?></span>
                        <small>K币余额</small>
                        <strong><?= number_format((int) ($profileSummary['coin_balance'] ?? 0) + (int) ($profileSummary['bonus_coin_balance'] ?? 0)) ?></strong>
                        <em>赠送 <?= number_format((int) ($profileSummary['bonus_coin_balance'] ?? 0)) ?></em>
                    </div>
                    <div class="kpi cyan">
                        <span class="kpi-icon"><?= jx_icon('drama') ?></span>
                        <small>权益数</small>
                        <strong><?= number_format((int) ($profileSummary['entitlement_count'] ?? 0)) ?></strong>
                        <em>首付 <?= htmlspecialchars((string) (($profileSummary['first_paid_at'] ?? '') ?: '-')) ?></em>
                    </div>
                </div>
                <div class="order-info-grid">
                    <div><span>推广码</span><strong><?= htmlspecialchars((string) (($profileAttribution['promotion_code'] ?? '') ?: '未归因')) ?></strong></div>
                    <div><span>推广链接ID</span><strong><?= (int) ($profileAttribution['promotion_link_id'] ?? 0) ?></strong></div>
                    <div><span>来源/计划</span><strong><?= htmlspecialchars(trim((string) (($profileAttribution['source'] ?? '') . ' ' . ($profileAttribution['campaign'] ?? ''))) ?: '未记录') ?></strong></div>
                    <div><span>投流字段</span><strong><?= htmlspecialchars(!empty($profileTrafficLines) ? implode(' · ', array_slice($profileTrafficLines, 0, 4)) : '未记录') ?></strong></div>
                </div>
                <div class="order-table">
                    <div class="row-card order-row-head">
                        <span>最近订单</span>
                        <span>权益/K币</span>
                        <span>推广事件</span>
                        <span>内容事件</span>
                    </div>
                    <div class="row-card order-row">
                        <span>
                            <?php if (empty($profileOrders)): ?>
                                暂无订单
                            <?php endif; ?>
                            <?php foreach ($profileOrders as $order): ?>
                                <em><?= htmlspecialchars((string) ($order['order_no'] ?? '-')) ?> · <?= htmlspecialchars($money((float) ($order['amount'] ?? 0))) ?> · <?= htmlspecialchars((string) ($order['status'] ?? 'pending')) ?></em>
                            <?php endforeach; ?>
                        </span>
                        <span>
                            <?php if (empty($profileEntitlements) && empty($profileCoinTransactions)): ?>
                                暂无权益流水
                            <?php endif; ?>
                            <?php foreach ($profileEntitlements as $entitlement): ?>
                                <em><?= htmlspecialchars((string) ($entitlement['type'] ?? '权益')) ?> · <?= htmlspecialchars((string) (($entitlement['content_type'] ?? 'drama') === 'novel' ? '小说' : '短剧')) ?> · <?= htmlspecialchars((string) ($entitlement['granted_at'] ?? '')) ?></em>
                            <?php endforeach; ?>
                            <?php foreach ($profileCoinTransactions as $transaction): ?>
                                <em>K币 <?= number_format((int) ($transaction['coins'] ?? 0)) ?> · <?= htmlspecialchars((string) ($transaction['scene'] ?? $transaction['type'] ?? '流水')) ?> · <?= htmlspecialchars((string) ($transaction['created_at'] ?? '')) ?></em>
                            <?php endforeach; ?>
                        </span>
                        <span>
                            <?php if (empty($profilePromotionEvents)): ?>
                                暂无推广事件
                            <?php endif; ?>
                            <?php foreach ($profilePromotionEvents as $event): ?>
                                <em><?= htmlspecialchars((string) ($event['event'] ?? '-')) ?> · <?= htmlspecialchars((string) (($event['code'] ?? '') ?: '-')) ?> · <?= htmlspecialchars((string) ($event['created_at'] ?? '')) ?></em>
                            <?php endforeach; ?>
                        </span>
                        <span>
                            <?php if (empty($profileContentEvents)): ?>
                                暂无内容事件
                            <?php endif; ?>
                            <?php foreach ($profileContentEvents as $event): ?>
                                <em><?= htmlspecialchars((string) ($event['event'] ?? '-')) ?> · <?= htmlspecialchars((string) (($event['content_type'] ?? 'drama') === 'novel' ? '小说' : '短剧')) ?> · <?= htmlspecialchars((string) ($event['created_at'] ?? '')) ?></em>
                            <?php endforeach; ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php elseif ((int) ($_GET['user_id'] ?? 0) > 0): ?>
            <p class="notice warn">未找到该用户。</p>
        <?php endif; ?>
        <div class="admin-data-panel user-list-panel">
            <div class="admin-data-head user-list-head">
                <span>用户</span>
                <span>联系方式 / 会员</span>
                <span>余额</span>
                <span>开关</span>
                <span>操作</span>
            </div>
            <?php if (empty($visibleUsers)): ?>
                <div class="admin-empty-state">
                    <strong>暂无用户</strong>
                    <span>当前筛选下没有用户，换一个类型再看。</span>
                </div>
            <?php endif; ?>
            <?php foreach ($visibleUsers as $user): ?>
                <form method="post" action="/jxdjadmin" class="admin-data-row user-edit-row">
                    <input type="hidden" name="admin_action" value="update_user">
                    <?= $csrfField() ?>
                    <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                    <div class="user-main-cell">
                        <strong><?= htmlspecialchars((string) (($user['nickname'] ?? '') ?: ('用户 ' . (int) ($user['id'] ?? 0)))) ?></strong>
                        <em>ID <?= (int) ($user['id'] ?? 0) ?> · <?= !empty($user['membership']) ? '会员' : '普通用户' ?></em>
                        <label>昵称<input name="nickname" value="<?= htmlspecialchars($user['nickname']) ?>"></label>
                        <label>角色<input name="role" value="<?= htmlspecialchars($user['role']) ?>"></label>
                    </div>
                    <div class="user-field-stack">
                        <label>手机<input name="phone" value="<?= htmlspecialchars($user['phone']) ?>"></label>
                        <label>会员到期<input name="membership_expires_at" value="<?= htmlspecialchars((string) ($user['membership_expires_at'] ?? '')) ?>"></label>
                    </div>
                    <div class="user-field-stack">
                        <label>K币余额<input name="coin_balance" value="<?= htmlspecialchars((string) ($user['coin_balance'] ?? 0)) ?>"></label>
                        <label>赠送K币<input name="bonus_coin_balance" value="<?= htmlspecialchars((string) ($user['bonus_coin_balance'] ?? 0)) ?>"></label>
                    </div>
                    <div class="user-toggle-stack">
                        <label><input type="checkbox" name="auto_unlock_next" value="1" <?= !empty($user['auto_unlock_next']) ? 'checked' : '' ?>> 自动解锁下一集</label>
                        <label><input type="checkbox" name="membership" value="1" <?= !empty($user['membership']) ? 'checked' : '' ?>> 会员</label>
                    </div>
                    <div class="admin-row-actions">
                        <button class="btn" type="submit">保存</button>
                        <a class="btn ghost" href="/jxdjadmin?admin_section=users&user_id=<?= (int) ($user['id'] ?? 0) ?>#users">画像</a>
                        <a class="btn ghost" href="/jxdjadmin?admin_section=rights-repair&user_id=<?= (int) ($user['id'] ?? 0) ?>#rights-repair">权益</a>
                        <a class="btn ghost" href="/jxdjadmin?admin_section=orders&user_keyword=<?= (int) ($user['id'] ?? 0) ?>#orders">订单</a>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
        </section>

        <section class="panel admin-section admin-compact-section <?= $activeAdminSection === 'episode-rights' ? 'is-active' : '' ?>" id="admin-section-episode-rights" data-admin-section="episode-rights" data-admin-primary="users">
            <div class="section-title admin-page-title">
                <div>
                    <span class="eyebrow">用户权益</span>
                    <h2>单集权益</h2>
                </div>
                <span class="muted"><?= number_format(count($episodeEntitlementRows)) ?> 条单集解锁记录</span>
            </div>
            <div class="order-table admin-data-panel rights-log-panel">
                <div class="row-card order-row-head rights-log-head">
                    <span>用户</span>
                    <span>短剧/分集</span>
                    <span>来源</span>
                    <span>处理</span>
                </div>
                <?php if (empty($episodeEntitlementRows)): ?>
                    <div class="admin-empty-state">
                        <strong>暂无单集权益</strong>
                        <span>用户购买或后台补发单集后，会在这里形成权益台账。</span>
                    </div>
                <?php endif; ?>
                <?php foreach (array_slice($episodeEntitlementRows, 0, 200) as $entitlement): ?>
                    <?php
                        $entUserId = (int) ($entitlement['user_id'] ?? 0);
                        $entDramaId = (int) ($entitlement['drama_id'] ?? 0);
                        $entEpisodeId = (int) ($entitlement['episode_id'] ?? 0);
                        $entOrderNo = (string) ($entitlement['order_no'] ?? ($entitlement['source_order_no'] ?? ''));
                    ?>
                    <div class="row-card order-row rights-log-row">
                        <span>
                            <strong><?= htmlspecialchars((string) ($userNameById[$entUserId] ?? ('用户 ' . $entUserId))) ?></strong>
                            <em>ID <?= $entUserId ?></em>
                        </span>
                        <span>
                            <?= htmlspecialchars((string) ($dramaTitleById[$entDramaId] ?? ('短剧 ' . $entDramaId))) ?>
                            <em><?= htmlspecialchars((string) ($episodeTitleByKey[$entDramaId . ':' . $entEpisodeId] ?? ('分集 ' . $entEpisodeId))) ?></em>
                        </span>
                        <span>
                            <?= htmlspecialchars($entOrderNo !== '' ? $entOrderNo : '后台/活动发放') ?>
                            <em><?= htmlspecialchars((string) (($entitlement['granted_at'] ?? '') ?: ($entitlement['created_at'] ?? ''))) ?></em>
                        </span>
                        <span>
                            <a class="btn ghost" href="/jxdjadmin?admin_section=rights-repair&user_id=<?= $entUserId ?>#rights-repair">处理权益</a>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

<section class="panel admin-section admin-compact-section <?= $activeAdminSection === 'rights-repair' ? 'is-active' : '' ?>" id="admin-section-rights-repair" data-admin-section="rights-repair" data-admin-primary="users">
    <?php
        $rightsActionLabels = [
            'grant_coin' => '补发K币',
            'deduct_coin' => '扣减K币',
            'grant_vip' => '补发VIP',
            'revoke_vip' => '撤销VIP',
            'grant_content' => '补发内容权益',
            'revoke_content' => '撤销内容权益',
        ];
        $rightsEntitlementLabels = [
            'drama_unlock' => '短剧全集',
            'episode_unlock' => '短剧单集',
            'novel_unlock' => '小说整本',
            'novel_chapter_unlock' => '小说章节',
            'membership' => '会员权益',
        ];
        $rightsStatusLabels = ['success' => '成功', 'failed' => '失败'];
        $selectedRepairUserId = max(0, (int) ($_GET['user_id'] ?? 0));
        $latestRightsRepairLogs = array_slice($rightsRepairLogs, 0, 80);
    ?>
    <div class="section-title admin-page-title">
        <div>
            <span class="eyebrow">客服运营</span>
            <h2>权益补发/撤销</h2>
        </div>
        <span class="muted"><?= number_format(count($rightsRepairLogs)) ?> 条处理记录</span>
    </div>
    <form method="post" action="/jxdjadmin" class="stack admin-form-panel rights-repair-form">
        <input type="hidden" name="admin_action" value="repair_user_rights">
        <input type="hidden" name="admin_section" value="rights-repair">
        <?= $csrfField() ?>
        <div class="form-grid">
            <label>用户ID<input name="user_id" value="<?= htmlspecialchars((string) $selectedRepairUserId) ?>" placeholder="例如 1001"></label>
            <label>操作类型
                <select name="rights_action">
                    <?php foreach ($rightsActionLabels as $actionValue => $actionLabel): ?>
                        <option value="<?= htmlspecialchars($actionValue) ?>"><?= htmlspecialchars($actionLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>K币<input name="coins" type="number" min="0" value="0"></label>
            <label>赠送K币<input name="bonus_coins" type="number" min="0" value="0"></label>
            <label>VIP天数<input name="vip_days" type="number" min="0" value="0"></label>
            <label>内容类型
                <select name="content_type">
                    <option value="drama">短剧</option>
                    <option value="novel">小说</option>
                </select>
            </label>
            <label>权益类型
                <select name="entitlement_type">
                    <option value="">自动判断</option>
                    <?php foreach ($rightsEntitlementLabels as $typeValue => $typeLabel): ?>
                        <option value="<?= htmlspecialchars($typeValue) ?>"><?= htmlspecialchars($typeLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>短剧ID<input name="drama_id" type="number" min="0" value="0"></label>
            <label>分集ID<input name="episode_id" type="number" min="0" value="0"></label>
            <label>小说ID<input name="novel_id" type="number" min="0" value="0"></label>
            <label>章节ID<input name="chapter_id" type="number" min="0" value="0"></label>
            <label>处理备注<input name="remark" placeholder="例如 支付成功未到账、客服补偿、误发撤销"></label>
        </div>
        <p class="admin-form-actions"><button class="btn primary" type="submit">提交处理</button></p>
    </form>
    <div class="order-table admin-data-panel rights-log-panel">
        <div class="row-card order-row-head rights-log-head">
            <span>用户</span>
            <span>动作/权益</span>
            <span>数量/内容</span>
            <span>结果</span>
        </div>
        <?php if (empty($latestRightsRepairLogs)): ?>
            <div class="admin-empty-state">
                <strong>暂无权益处理记录</strong>
                <span>提交补发或撤销后会在这里生成操作日志。</span>
            </div>
        <?php endif; ?>
        <?php foreach ($latestRightsRepairLogs as $log): ?>
            <?php
                $logUserId = (int) ($log['user_id'] ?? 0);
                $logAction = (string) ($log['action'] ?? '');
                $logContentType = (string) ($log['content_type'] ?? 'drama');
                $logEntitlementType = (string) ($log['entitlement_type'] ?? '');
                $logStatus = (string) ($log['status'] ?? 'success');
                $contentParts = [];
                if ($logContentType === 'novel') {
                    $contentParts[] = '小说 ' . (int) ($log['novel_id'] ?? 0);
                    if (!empty($log['chapter_id'])) {
                        $contentParts[] = '章节 ' . (int) ($log['chapter_id'] ?? 0);
                    }
                } elseif (!empty($log['drama_id'])) {
                    $contentParts[] = '短剧 ' . (int) ($log['drama_id'] ?? 0);
                    if (!empty($log['episode_id'])) {
                        $contentParts[] = '分集 ' . (int) ($log['episode_id'] ?? 0);
                    }
                }
                $amountParts = [];
                if ((int) ($log['coins'] ?? 0) !== 0) {
                    $amountParts[] = 'K币 ' . number_format((int) ($log['coins'] ?? 0));
                }
                if ((int) ($log['bonus_coins'] ?? 0) !== 0) {
                    $amountParts[] = '赠币 ' . number_format((int) ($log['bonus_coins'] ?? 0));
                }
                if ((int) ($log['vip_days'] ?? 0) > 0) {
                    $amountParts[] = 'VIP ' . number_format((int) ($log['vip_days'] ?? 0)) . '天';
                }
                $detailText = trim(implode(' · ', array_merge($amountParts, $contentParts))) ?: '仅调整权益状态';
            ?>
            <div class="row-card order-row rights-log-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($userNameById[$logUserId] ?? ('用户 ' . $logUserId))) ?></strong>
                    <em>ID <?= $logUserId ?> · <?= htmlspecialchars((string) ($log['created_at'] ?? '')) ?></em>
                </span>
                <span>
                    <?= htmlspecialchars((string) ($rightsActionLabels[$logAction] ?? $logAction)) ?>
                    <em><?= htmlspecialchars((string) (($rightsEntitlementLabels[$logEntitlementType] ?? $logEntitlementType) ?: '-')) ?></em>
                </span>
                <span>
                    <?= htmlspecialchars($detailText) ?>
                    <em><?= htmlspecialchars((string) (($log['remark'] ?? '') ?: '-')) ?></em>
                </span>
                <span>
                    <strong><?= htmlspecialchars((string) ($rightsStatusLabels[$logStatus] ?? $logStatus)) ?></strong>
                    <em><?= htmlspecialchars((string) (($log['message'] ?? '') ?: '-')) ?></em>
                    <em><?= htmlspecialchars((string) (($log['admin_name'] ?? '') ?: '管理员')) ?></em>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'banner' ? 'is-active' : '' ?>" id="admin-section-banner" data-admin-section="banner" data-admin-primary="operation">
    <h2>运营推荐</h2>
    <?php $banner = $banners[0] ?? ['title' => '', 'subtitle' => '', 'link' => '/']; ?>
    <form method="post" action="/jxdjadmin" class="stack">
        <input type="hidden" name="admin_action" value="update_banner">
                <?= $csrfField() ?>
        <div class="form-grid">
            <label>标题<input name="banner_title" value="<?= htmlspecialchars($banner['title']) ?>"></label>
            <label>副标题<input name="banner_subtitle" value="<?= htmlspecialchars($banner['subtitle']) ?>"></label>
            <label>跳转链接<input name="banner_link" value="<?= htmlspecialchars($banner['link']) ?>"></label>
        </div>
        <p><button class="btn" type="submit">保存 Banner</button></p>
    </form>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'home-recommend' ? 'is-active' : '' ?>" id="admin-section-home-recommend" data-admin-section="home-recommend" data-admin-primary="operation">
    <?php
        $homeRecommendActiveCount = count(array_filter($homeRecommendations, static fn (array $item): bool => (string) ($item['status'] ?? 'active') === 'active'));
        $homeRecommendSlotLabels = ['home' => '首页', 'rank' => '榜单', 'hot' => '热播', 'new' => '新剧', 'category' => '分类', 'center' => '个人中心'];
        $operationContentTypeLabels = ['drama' => '短剧', 'novel' => '小说', 'activity' => '活动', 'url' => '链接'];
    ?>
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">运营推荐</span>
            <h2>首页推荐</h2>
        </div>
        <span class="muted">启用 <?= number_format($homeRecommendActiveCount) ?> 条 · 总计 <?= number_format(count($homeRecommendations)) ?> 条</span>
    </div>
    <form method="post" action="/jxdjadmin#home-recommend" class="row-card stack">
        <input type="hidden" name="admin_action" value="save_home_recommendation">
        <input type="hidden" name="admin_section" value="home-recommend">
        <?= $csrfField() ?>
        <p><strong>新建首页推荐</strong></p>
        <div class="form-grid">
            <label>推荐位
                <select name="slot">
                    <?php foreach ($homeRecommendSlotLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>标题<input name="title" placeholder="例如 今日爆款"></label>
            <label>副标题<input name="subtitle" placeholder="推荐理由/活动文案"></label>
            <label>内容类型
                <select name="content_type">
                    <?php foreach ($operationContentTypeLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>内容ID<input name="content_id" type="number" min="0" value="0"></label>
            <label>跳转链接<input name="link" placeholder="/duanju 或 /?route=novels"></label>
            <label>图片<input name="image" placeholder="可选图片地址"></label>
            <label>标签<input name="tag" placeholder="热播 / 福利 / 新剧"></label>
            <label>应用
                <select name="app_key">
                    <option value="all">全部应用</option>
                    <?php foreach ($apps as $app): ?>
                        <option value="<?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?>"><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>状态
                <select name="status">
                    <option value="active">启用</option>
                    <option value="paused">停用</option>
                </select>
            </label>
            <label>排序<input name="sort" type="number" value="100"></label>
            <label>开始时间<input name="started_at" type="datetime-local"></label>
            <label>结束时间<input name="ended_at" type="datetime-local"></label>
            <label>备注<input name="remark" placeholder="运营批次/素材说明"></label>
        </div>
        <p><button class="btn" type="submit">保存推荐</button></p>
    </form>
    <div class="order-list">
        <?php if (empty($homeRecommendations)): ?><div class="empty">暂无首页推荐。</div><?php endif; ?>
        <?php foreach ($homeRecommendations as $item): ?>
            <?php $status = (string) ($item['status'] ?? 'active'); ?>
            <form method="post" action="/jxdjadmin#home-recommend" class="order-item">
                <input type="hidden" name="admin_action" value="save_home_recommendation">
                <input type="hidden" name="admin_section" value="home-recommend">
                <input type="hidden" name="home_recommendation_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                <?= $csrfField() ?>
                <span>
                    <strong><?= htmlspecialchars((string) ($item['title'] ?? '首页推荐')) ?></strong>
                    <span class="pill <?= $status === 'active' ? 'green' : 'orange' ?>"><?= $status === 'active' ? '启用' : '停用' ?></span>
                    <em><?= htmlspecialchars($homeRecommendSlotLabels[(string) ($item['slot'] ?? 'home')] ?? '首页') ?> · <?= htmlspecialchars($operationContentTypeLabels[(string) ($item['content_type'] ?? 'drama')] ?? '短剧') ?> #<?= (int) ($item['content_id'] ?? 0) ?></em>
                </span>
                <span>
                    <input name="title" value="<?= htmlspecialchars((string) ($item['title'] ?? '')) ?>" aria-label="标题">
                    <em><input name="subtitle" value="<?= htmlspecialchars((string) ($item['subtitle'] ?? '')) ?>" placeholder="副标题" aria-label="副标题"></em>
                    <em>
                        <select name="slot" aria-label="推荐位">
                            <?php foreach ($homeRecommendSlotLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($item['slot'] ?? '') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </em>
                    <em>
                        <select name="content_type" aria-label="内容类型">
                            <?php foreach ($operationContentTypeLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($item['content_type'] ?? '') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </em>
                    <em>内容ID <input name="content_id" type="number" min="0" value="<?= (int) ($item['content_id'] ?? 0) ?>" aria-label="内容ID"></em>
                </span>
                <span>
                    <em><input name="link" value="<?= htmlspecialchars((string) ($item['link'] ?? '')) ?>" placeholder="跳转链接" aria-label="跳转链接"></em>
                    <em><input name="image" value="<?= htmlspecialchars((string) ($item['image'] ?? '')) ?>" placeholder="图片" aria-label="图片"></em>
                    <em><input name="tag" value="<?= htmlspecialchars((string) ($item['tag'] ?? '')) ?>" placeholder="标签" aria-label="标签"></em>
                    <em>应用
                        <select name="app_key" aria-label="应用">
                            <option value="all" <?= (string) ($item['app_key'] ?? 'all') === 'all' ? 'selected' : '' ?>>全部应用</option>
                            <?php foreach ($apps as $app): ?>
                                <?php $appKey = (string) ($app['app_key'] ?? ''); ?>
                                <option value="<?= htmlspecialchars($appKey) ?>" <?= (string) ($item['app_key'] ?? '') === $appKey ? 'selected' : '' ?>><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars($appKey) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </em>
                    <em>
                        <select name="status" aria-label="状态">
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>启用</option>
                            <option value="paused" <?= $status === 'paused' ? 'selected' : '' ?>>停用</option>
                        </select>
                    </em>
                    <em>排序 <input name="sort" type="number" value="<?= (int) ($item['sort'] ?? 100) ?>" aria-label="排序"></em>
                    <em>开始 <input name="started_at" type="datetime-local" value="<?= htmlspecialchars(str_replace(' ', 'T', (string) ($item['started_at'] ?? ''))) ?>" aria-label="开始时间"></em>
                    <em>结束 <input name="ended_at" type="datetime-local" value="<?= htmlspecialchars(str_replace(' ', 'T', (string) ($item['ended_at'] ?? ''))) ?>" aria-label="结束时间"></em>
                    <em><input name="remark" value="<?= htmlspecialchars((string) ($item['remark'] ?? '')) ?>" placeholder="备注" aria-label="备注"></em>
                    <button class="btn ghost" type="submit">保存</button>
                </span>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'hot-rank' ? 'is-active' : '' ?>" id="admin-section-hot-rank" data-admin-section="hot-rank" data-admin-primary="operation">
    <?php
        $hotRankContentTypeLabels = ['mixed' => '短剧+小说', 'drama' => '短剧', 'novel' => '小说'];
        $hotRankAlgorithmLabels = ['hot_score' => '综合热度', 'views' => '播放/浏览', 'unlock' => '解锁', 'revenue' => '收入', 'manual' => '人工排序'];
        $hotRankTimeWindowLabels = ['all' => '全部时间', 'today' => '今天', 'yesterday' => '昨天', 'last_7_days' => '近7天', 'last_30_days' => '近30天', 'this_month' => '本月'];
        $hotRankStatusLabels = ['active' => '启用', 'paused' => '停用'];
        $hotRankPinnedText = static function (array $items): string {
            $lines = [];
            foreach ($items as $item) {
                $type = (string) ($item['content_type'] ?? 'drama') === 'novel' ? 'novel' : 'drama';
                $id = max(0, (int) ($item['content_id'] ?? 0));
                if ($id > 0) {
                    $lines[] = $type . ':' . $id;
                }
            }

            return implode("\n", $lines);
        };
    ?>
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">运营推荐</span>
            <h2>热播榜单</h2>
        </div>
        <span class="muted">启用 <?= number_format((int) ($hotRankSummary['active_count'] ?? 0)) ?> 个 · 总计 <?= number_format((int) ($hotRankSummary['config_count'] ?? count($hotRankConfigs))) ?> 个</span>
    </div>
    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('stats') ?></span>
            <small>榜单数</small>
            <strong><?= number_format((int) ($hotRankSummary['config_count'] ?? count($hotRankConfigs))) ?></strong>
            <em>已配置榜单</em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('profit') ?></span>
            <small>启用</small>
            <strong><?= number_format((int) ($hotRankSummary['active_count'] ?? 0)) ?></strong>
            <em>前台可投放</em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('drama') ?></span>
            <small>短剧作品</small>
            <strong><?= number_format((int) ($hotRankSummary['drama_count'] ?? count($dramas ?? []))) ?></strong>
            <em>可参与排行</em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('revenue') ?></span>
            <small>小说内容</small>
            <strong><?= number_format((int) ($hotRankSummary['novel_count'] ?? count($novels))) ?></strong>
            <em>可参与排行</em>
        </div>
    </div>
    <form method="post" action="/jxdjadmin#hot-rank" class="row-card stack">
        <input type="hidden" name="admin_action" value="save_hot_rank_config">
        <input type="hidden" name="admin_section" value="hot-rank">
        <?= $csrfField() ?>
        <p><strong>新建热播榜单</strong></p>
        <div class="form-grid">
            <label>榜单编码<input name="rank_key" placeholder="home_hot"></label>
            <label>榜单名称<input name="name" placeholder="首页综合热播榜"></label>
            <label>应用
                <select name="app_key">
                    <option value="all">全部应用</option>
                    <?php foreach ($apps as $app): ?>
                        <option value="<?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?>"><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>内容范围
                <select name="content_type">
                    <?php foreach ($hotRankContentTypeLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>排序算法
                <select name="algorithm">
                    <?php foreach ($hotRankAlgorithmLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>统计窗口
                <select name="time_window">
                    <?php foreach ($hotRankTimeWindowLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $value === 'last_7_days' ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>状态
                <select name="status">
                    <option value="active">启用</option>
                    <option value="paused">停用</option>
                </select>
            </label>
            <label>展示条数<input name="limit" type="number" min="1" max="50" value="10"></label>
            <label>最低分<input name="min_score" type="number" min="0" value="0"></label>
            <label>排序<input name="sort" type="number" value="100"></label>
            <label>开始时间<input name="started_at" type="datetime-local"></label>
            <label>结束时间<input name="ended_at" type="datetime-local"></label>
            <label>人工置顶<textarea name="pinned_items_text" rows="3" placeholder="drama:1&#10;novel:1"></textarea></label>
            <label>备注<textarea name="remark" rows="3" placeholder="榜单用途、运营批次或投放位置"></textarea></label>
        </div>
        <p><button class="btn" type="submit">保存榜单</button></p>
    </form>
    <div class="order-list">
        <?php if (empty($hotRankConfigRows)): ?><div class="empty">暂无热播榜单。</div><?php endif; ?>
        <?php foreach ($hotRankConfigRows as $configRow): ?>
            <?php
                $rankConfig = (array) ($configRow['config'] ?? []);
                $rankRows = array_values((array) ($configRow['rows'] ?? []));
                $rankStatus = (string) ($rankConfig['status'] ?? 'active');
                $rankConfigId = (int) ($rankConfig['id'] ?? 0);
                $rankKey = (string) ($rankConfig['rank_key'] ?? '');
            ?>
            <form method="post" action="/jxdjadmin#hot-rank" class="row-card stack">
                <input type="hidden" name="admin_action" value="save_hot_rank_config">
                <input type="hidden" name="admin_section" value="hot-rank">
                <input type="hidden" name="hot_rank_id" value="<?= $rankConfigId ?>">
                <?= $csrfField() ?>
                <div class="section-title admin-section-title">
                    <div>
                        <strong><?= htmlspecialchars((string) ($rankConfig['name'] ?? '热播榜单')) ?></strong>
                        <em><?= htmlspecialchars($rankKey) ?> · <?= htmlspecialchars($hotRankContentTypeLabels[(string) ($rankConfig['content_type'] ?? 'mixed')] ?? '短剧+小说') ?> · <?= htmlspecialchars($hotRankAlgorithmLabels[(string) ($rankConfig['algorithm'] ?? 'hot_score')] ?? '综合热度') ?></em>
                    </div>
                    <span class="pill <?= $rankStatus === 'active' ? 'green' : 'orange' ?>"><?= htmlspecialchars($hotRankStatusLabels[$rankStatus] ?? $rankStatus) ?></span>
                </div>
                <div class="form-grid">
                    <label>榜单编码<input name="rank_key" value="<?= htmlspecialchars($rankKey) ?>"></label>
                    <label>榜单名称<input name="name" value="<?= htmlspecialchars((string) ($rankConfig['name'] ?? '')) ?>"></label>
                    <label>应用
                        <select name="app_key">
                            <option value="all" <?= (string) ($rankConfig['app_key'] ?? 'all') === 'all' ? 'selected' : '' ?>>全部应用</option>
                            <?php foreach ($apps as $app): ?>
                                <?php $appKey = (string) ($app['app_key'] ?? ''); ?>
                                <option value="<?= htmlspecialchars($appKey) ?>" <?= (string) ($rankConfig['app_key'] ?? '') === $appKey ? 'selected' : '' ?>><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars($appKey) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>内容范围
                        <select name="content_type">
                            <?php foreach ($hotRankContentTypeLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($rankConfig['content_type'] ?? 'mixed') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>排序算法
                        <select name="algorithm">
                            <?php foreach ($hotRankAlgorithmLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($rankConfig['algorithm'] ?? 'hot_score') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>统计窗口
                        <select name="time_window">
                            <?php foreach ($hotRankTimeWindowLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($rankConfig['time_window'] ?? 'last_7_days') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>状态
                        <select name="status">
                            <?php foreach ($hotRankStatusLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= $rankStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>展示条数<input name="limit" type="number" min="1" max="50" value="<?= (int) ($rankConfig['limit'] ?? 10) ?>"></label>
                    <label>最低分<input name="min_score" type="number" min="0" value="<?= (int) ($rankConfig['min_score'] ?? 0) ?>"></label>
                    <label>排序<input name="sort" type="number" value="<?= (int) ($rankConfig['sort'] ?? 100) ?>"></label>
                    <label>开始时间<input name="started_at" type="datetime-local" value="<?= htmlspecialchars(str_replace(' ', 'T', (string) ($rankConfig['started_at'] ?? ''))) ?>"></label>
                    <label>结束时间<input name="ended_at" type="datetime-local" value="<?= htmlspecialchars(str_replace(' ', 'T', (string) ($rankConfig['ended_at'] ?? ''))) ?>"></label>
                    <label>人工置顶<textarea name="pinned_items_text" rows="3"><?= htmlspecialchars($hotRankPinnedText((array) ($rankConfig['pinned_items'] ?? []))) ?></textarea></label>
                    <label>备注<textarea name="remark" rows="3"><?= htmlspecialchars((string) ($rankConfig['remark'] ?? '')) ?></textarea></label>
                </div>
                <p><button class="btn ghost" type="submit">保存榜单</button></p>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>排名</th>
                                <th>内容</th>
                                <th>热度分</th>
                                <th>播放/浏览</th>
                                <th>解锁</th>
                                <th>收入</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rankRows)): ?>
                                <tr><td colspan="6">暂无可预览内容。</td></tr>
                            <?php endif; ?>
                            <?php foreach ($rankRows as $rankRow): ?>
                                <?php
                                    $rowViews = (int) ($rankRow['views'] ?? 0) + (int) ($rankRow['legacy_views'] ?? 0) + (int) ($rankRow['watch_records'] ?? 0);
                                    $rowUnlocks = max((int) ($rankRow['unlock_success'] ?? 0), (int) ($rankRow['unlocks'] ?? 0));
                                ?>
                                <tr>
                                    <td>#<?= (int) ($rankRow['rank'] ?? 0) ?><?= !empty($rankRow['pinned']) ? ' · 置顶' : '' ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars((string) ($rankRow['title'] ?? '内容')) ?></strong>
                                        <em><?= htmlspecialchars((string) ($rankRow['content_label'] ?? '短剧')) ?> #<?= (int) ($rankRow['content_id'] ?? 0) ?></em>
                                    </td>
                                    <td><?= number_format((int) ($rankRow['score'] ?? 0)) ?></td>
                                    <td><?= number_format($rowViews) ?></td>
                                    <td><?= number_format($rowUnlocks) ?></td>
                                    <td><?= $money((float) ($rankRow['revenue'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'popup-notice' ? 'is-active' : '' ?>" id="admin-section-popup-notice" data-admin-section="popup-notice" data-admin-primary="operation">
    <?php
        $popupActiveCount = count(array_filter($popupNotices, static fn (array $item): bool => (string) ($item['status'] ?? 'active') === 'active'));
        $popupTriggerLabels = ['launch' => '启动', 'home' => '首页', 'player' => '播放页', 'reader' => '阅读页', 'center' => '个人中心', 'payment_success' => '支付成功'];
    ?>
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">运营触达</span>
            <h2>弹窗公告</h2>
        </div>
        <span class="muted">启用 <?= number_format($popupActiveCount) ?> 条 · 总计 <?= number_format(count($popupNotices)) ?> 条</span>
    </div>
    <form method="post" action="/jxdjadmin#popup-notice" class="row-card stack">
        <input type="hidden" name="admin_action" value="save_popup_notice">
        <input type="hidden" name="admin_section" value="popup-notice">
        <?= $csrfField() ?>
        <p><strong>新建弹窗公告</strong></p>
        <div class="form-grid">
            <label>触发场景
                <select name="trigger">
                    <?php foreach ($popupTriggerLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>标题<input name="title" placeholder="例如 限时福利"></label>
            <label>内容<input name="content" placeholder="弹窗正文"></label>
            <label>按钮<input name="button_text" value="立即查看"></label>
            <label>跳转链接<input name="link" placeholder="/?route=center"></label>
            <label>图片<input name="image" placeholder="可选图片地址"></label>
            <label>应用
                <select name="app_key">
                    <option value="all">全部应用</option>
                    <?php foreach ($apps as $app): ?>
                        <option value="<?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?>"><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>状态
                <select name="status">
                    <option value="active">启用</option>
                    <option value="paused">停用</option>
                </select>
            </label>
            <label>优先级<input name="priority" type="number" value="100"></label>
            <label>每日展示上限<input name="daily_limit" type="number" min="0" value="1"></label>
            <label><span><input type="checkbox" name="once_per_user" value="1" checked> 单用户只弹一次</span></label>
            <label>开始时间<input name="started_at" type="datetime-local"></label>
            <label>结束时间<input name="ended_at" type="datetime-local"></label>
            <label>备注<input name="remark" placeholder="公告批次/人群说明"></label>
        </div>
        <p><button class="btn" type="submit">保存弹窗</button></p>
    </form>
    <div class="order-list">
        <?php if (empty($popupNotices)): ?><div class="empty">暂无弹窗公告。</div><?php endif; ?>
        <?php foreach ($popupNotices as $item): ?>
            <?php $status = (string) ($item['status'] ?? 'active'); ?>
            <form method="post" action="/jxdjadmin#popup-notice" class="order-item">
                <input type="hidden" name="admin_action" value="save_popup_notice">
                <input type="hidden" name="admin_section" value="popup-notice">
                <input type="hidden" name="popup_notice_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                <?= $csrfField() ?>
                <span>
                    <strong><?= htmlspecialchars((string) ($item['title'] ?? '弹窗公告')) ?></strong>
                    <span class="pill <?= $status === 'active' ? 'green' : 'orange' ?>"><?= $status === 'active' ? '启用' : '停用' ?></span>
                    <em><?= htmlspecialchars($popupTriggerLabels[(string) ($item['trigger'] ?? 'launch')] ?? '启动') ?> · 优先级 <?= (int) ($item['priority'] ?? 100) ?></em>
                </span>
                <span>
                    <input name="title" value="<?= htmlspecialchars((string) ($item['title'] ?? '')) ?>" aria-label="标题">
                    <em><input name="content" value="<?= htmlspecialchars((string) ($item['content'] ?? '')) ?>" placeholder="内容" aria-label="内容"></em>
                    <em>
                        <select name="trigger" aria-label="触发场景">
                            <?php foreach ($popupTriggerLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($item['trigger'] ?? '') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </em>
                    <em><input name="button_text" value="<?= htmlspecialchars((string) ($item['button_text'] ?? '')) ?>" placeholder="按钮" aria-label="按钮"></em>
                    <em><input name="link" value="<?= htmlspecialchars((string) ($item['link'] ?? '')) ?>" placeholder="跳转链接" aria-label="跳转链接"></em>
                    <em><input name="image" value="<?= htmlspecialchars((string) ($item['image'] ?? '')) ?>" placeholder="图片" aria-label="图片"></em>
                </span>
                <span>
                    <em>应用
                        <select name="app_key" aria-label="应用">
                            <option value="all" <?= (string) ($item['app_key'] ?? 'all') === 'all' ? 'selected' : '' ?>>全部应用</option>
                            <?php foreach ($apps as $app): ?>
                                <?php $appKey = (string) ($app['app_key'] ?? ''); ?>
                                <option value="<?= htmlspecialchars($appKey) ?>" <?= (string) ($item['app_key'] ?? '') === $appKey ? 'selected' : '' ?>><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars($appKey) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </em>
                    <em>
                        <select name="status" aria-label="状态">
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>启用</option>
                            <option value="paused" <?= $status === 'paused' ? 'selected' : '' ?>>停用</option>
                        </select>
                    </em>
                    <em>优先级 <input name="priority" type="number" value="<?= (int) ($item['priority'] ?? 100) ?>" aria-label="优先级"></em>
                    <em>日上限 <input name="daily_limit" type="number" min="0" value="<?= (int) ($item['daily_limit'] ?? 1) ?>" aria-label="每日展示上限"></em>
                    <em><label><span><input type="checkbox" name="once_per_user" value="1" <?= !empty($item['once_per_user']) ? 'checked' : '' ?>> 单用户一次</span></label></em>
                    <em>开始 <input name="started_at" type="datetime-local" value="<?= htmlspecialchars(str_replace(' ', 'T', (string) ($item['started_at'] ?? ''))) ?>" aria-label="开始时间"></em>
                    <em>结束 <input name="ended_at" type="datetime-local" value="<?= htmlspecialchars(str_replace(' ', 'T', (string) ($item['ended_at'] ?? ''))) ?>" aria-label="结束时间"></em>
                    <em><input name="remark" value="<?= htmlspecialchars((string) ($item['remark'] ?? '')) ?>" placeholder="备注" aria-label="备注"></em>
                    <button class="btn ghost" type="submit">保存</button>
                </span>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'activity-config' ? 'is-active' : '' ?>" id="admin-section-activity-config" data-admin-section="activity-config" data-admin-primary="operation">
    <?php
        $activityActiveCount = count(array_filter($activityConfigs, static fn (array $item): bool => (string) ($item['status'] ?? 'active') === 'active'));
        $activityTypeLabels = ['general' => '通用', 'sign_in' => '签到', 'invite' => '邀请', 'recharge' => '充值', 'watch' => '观看', 'share' => '分享'];
        $activityRewardLabels = ['none' => '无奖励', 'coin' => 'K币', 'vip' => 'VIP', 'redeem_code' => '兑换码'];
        $activityTierLabels = ['all' => '全部用户', 'new' => '新客', 'unpaid' => '未付费', 'paid' => '已付费', 'member' => '会员'];
        $activityBudgetByKey = [];
        foreach ($activityBudgetRows as $budgetRow) {
            if ((int) ($budgetRow['activity_id'] ?? 0) > 0) {
                $activityBudgetByKey['id:' . (int) $budgetRow['activity_id']] = $budgetRow;
            }
            if ((string) ($budgetRow['activity_code'] ?? '') !== '') {
                $activityBudgetByKey['code:' . (string) $budgetRow['activity_code']] = $budgetRow;
            }
        }
    ?>
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">活动运营</span>
            <h2>活动配置</h2>
        </div>
        <span class="muted">启用 <?= number_format($activityActiveCount) ?> 个 · 总计 <?= number_format(count($activityConfigs)) ?> 个</span>
    </div>
    <form method="post" action="/jxdjadmin#activity-config" class="row-card stack">
        <input type="hidden" name="admin_action" value="save_activity_config">
        <input type="hidden" name="admin_section" value="activity-config">
        <?= $csrfField() ?>
        <p><strong>新建活动</strong></p>
        <div class="form-grid">
            <label>活动编码<input name="code" placeholder="例如 july_checkin"></label>
            <label>活动名称<input name="name" placeholder="例如 七月签到"></label>
            <label>活动类型
                <select name="activity_type">
                    <?php foreach ($activityTypeLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>目标人群
                <select name="target_tiers[]" multiple size="5">
                    <?php foreach ($activityTierLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $value === 'all' ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>入口标题<input name="title" placeholder="活动页标题"></label>
            <label>入口副标题<input name="subtitle" placeholder="活动说明"></label>
            <label>入口文案<input name="entry_text" placeholder="去领取"></label>
            <label>入口链接<input name="entry_link" placeholder="/?route=center"></label>
            <label>图片<input name="image" placeholder="可选图片地址"></label>
            <label>奖励类型
                <select name="reward_type">
                    <?php foreach ($activityRewardLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>K币奖励<input name="coin_amount" type="number" min="0" value="0"></label>
            <label>VIP 天数<input name="vip_days" type="number" min="0" value="0"></label>
            <label>兑换码ID<input name="redeem_code_id" type="number" min="0" value="0"></label>
            <label>每日上限<input name="daily_limit" type="number" min="0" value="0"></label>
            <label>总上限<input name="total_limit" type="number" min="0" value="0"></label>
            <label>权益预算<input name="budget_coin_limit" type="number" min="0" value="0" placeholder="K币等值，0为不限"></label>
            <label>VIP天折算<input name="vip_day_budget_coins" type="number" min="0" value="100" placeholder="1天VIP折算K币"></label>
            <label><span><input type="checkbox" name="auto_pause_on_budget" value="1"> 预算用完自动停用</span></label>
            <label>实验键<input name="experiment_key" placeholder="同一实验填写相同键"></label>
            <label>版本键<input name="variant_key" placeholder="例如 A 或 B"></label>
            <label>流量占比<input name="traffic_percent" type="number" min="0" max="100" value="100"></label>
            <label>应用
                <select name="app_key">
                    <option value="all">全部应用</option>
                    <?php foreach ($apps as $app): ?>
                        <option value="<?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?>"><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>状态
                <select name="status">
                    <option value="active">启用</option>
                    <option value="paused">停用</option>
                </select>
            </label>
            <label>排序<input name="sort" type="number" value="100"></label>
            <label>开始时间<input name="started_at" type="datetime-local"></label>
            <label>结束时间<input name="ended_at" type="datetime-local"></label>
            <label>备注<input name="remark" placeholder="活动批次/规则说明"></label>
        </div>
        <p><button class="btn" type="submit">保存活动</button></p>
    </form>
    <div class="order-list">
        <?php if (empty($activityConfigs)): ?><div class="empty">暂无活动配置。</div><?php endif; ?>
        <?php foreach ($activityConfigs as $item): ?>
            <?php
                $status = (string) ($item['status'] ?? 'active');
                $targetTiers = array_values((array) ($item['target_tiers'] ?? ['all']));
                if (empty($targetTiers)) {
                    $targetTiers = ['all'];
                }
                $activityBudgetRow = $activityBudgetByKey['id:' . (int) ($item['id'] ?? 0)] ?? $activityBudgetByKey['code:' . (string) ($item['code'] ?? '')] ?? [];
                $activityBudgetLimit = (int) ($activityBudgetRow['budget_limit'] ?? ($item['budget_coin_limit'] ?? 0));
                $activityBudgetUsed = (int) ($activityBudgetRow['budget_used'] ?? 0);
                $activityBudgetRate = (float) ($activityBudgetRow['budget_usage_rate'] ?? 0);
            ?>
            <form method="post" action="/jxdjadmin#activity-config" class="order-item">
                <input type="hidden" name="admin_action" value="save_activity_config">
                <input type="hidden" name="admin_section" value="activity-config">
                <input type="hidden" name="activity_config_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                <?= $csrfField() ?>
                <span>
                    <strong><?= htmlspecialchars((string) ($item['name'] ?? '活动')) ?></strong>
                    <span class="pill <?= $status === 'active' ? 'green' : 'orange' ?>"><?= $status === 'active' ? '启用' : '停用' ?></span>
                    <em><?= htmlspecialchars((string) ($item['code'] ?? '')) ?> · <?= htmlspecialchars($activityTypeLabels[(string) ($item['activity_type'] ?? 'general')] ?? '通用') ?> · <?= htmlspecialchars(implode('/', array_map(static fn (string $tier): string => $activityTierLabels[$tier] ?? $tier, $targetTiers))) ?></em>
                    <?php if ($activityBudgetLimit > 0): ?>
                        <em>预算 <?= number_format($activityBudgetUsed) ?> / <?= number_format($activityBudgetLimit) ?> K币等值 · <?= number_format($activityBudgetRate, 2) ?>%<?= !empty($item['auto_pause_on_budget']) ? ' · 自动停用' : '' ?></em>
                    <?php endif; ?>
                    <?php if (!empty($item['budget_auto_paused_at'])): ?><em>预算停用 <?= htmlspecialchars((string) ($item['budget_auto_paused_at'] ?? '')) ?> · <?= htmlspecialchars((string) ($item['budget_auto_paused_reason'] ?? '')) ?></em><?php endif; ?>
                </span>
                <span>
                    <input name="code" value="<?= htmlspecialchars((string) ($item['code'] ?? '')) ?>" aria-label="活动编码">
                    <em><input name="name" value="<?= htmlspecialchars((string) ($item['name'] ?? '')) ?>" aria-label="活动名称"></em>
                    <em>
                        <select name="activity_type" aria-label="活动类型">
                            <?php foreach ($activityTypeLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($item['activity_type'] ?? '') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </em>
                    <em>
                        <select name="target_tiers[]" multiple size="5" aria-label="目标人群">
                            <?php foreach ($activityTierLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= in_array($value, $targetTiers, true) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </em>
                    <em><input name="title" value="<?= htmlspecialchars((string) ($item['title'] ?? '')) ?>" placeholder="标题" aria-label="标题"></em>
                    <em><input name="subtitle" value="<?= htmlspecialchars((string) ($item['subtitle'] ?? '')) ?>" placeholder="副标题" aria-label="副标题"></em>
                    <em><input name="entry_text" value="<?= htmlspecialchars((string) ($item['entry_text'] ?? '')) ?>" placeholder="入口文案" aria-label="入口文案"></em>
                    <em><input name="entry_link" value="<?= htmlspecialchars((string) ($item['entry_link'] ?? '')) ?>" placeholder="入口链接" aria-label="入口链接"></em>
                </span>
                <span>
                    <em>
                        <select name="reward_type" aria-label="奖励类型">
                            <?php foreach ($activityRewardLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($item['reward_type'] ?? '') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </em>
                    <em>K币 <input name="coin_amount" type="number" min="0" value="<?= (int) ($item['coin_amount'] ?? 0) ?>" aria-label="K币奖励"></em>
                    <em>VIP <input name="vip_days" type="number" min="0" value="<?= (int) ($item['vip_days'] ?? 0) ?>" aria-label="VIP天数"></em>
                    <em>兑换码 <input name="redeem_code_id" type="number" min="0" value="<?= (int) ($item['redeem_code_id'] ?? 0) ?>" aria-label="兑换码ID"></em>
                    <em>日上限 <input name="daily_limit" type="number" min="0" value="<?= (int) ($item['daily_limit'] ?? 0) ?>" aria-label="每日上限"></em>
                    <em>总上限 <input name="total_limit" type="number" min="0" value="<?= (int) ($item['total_limit'] ?? 0) ?>" aria-label="总上限"></em>
                    <em>预算 <input name="budget_coin_limit" type="number" min="0" value="<?= (int) ($item['budget_coin_limit'] ?? 0) ?>" aria-label="权益预算"></em>
                    <em>VIP折算 <input name="vip_day_budget_coins" type="number" min="0" value="<?= (int) ($item['vip_day_budget_coins'] ?? 100) ?>" aria-label="VIP天折算K币"></em>
                    <em><label><input name="auto_pause_on_budget" type="checkbox" value="1" <?= !empty($item['auto_pause_on_budget']) ? 'checked' : '' ?>> 预算停用</label></em>
                    <em>实验 <input name="experiment_key" value="<?= htmlspecialchars((string) ($item['experiment_key'] ?? '')) ?>" placeholder="实验键" aria-label="实验键"></em>
                    <em>版本 <input name="variant_key" value="<?= htmlspecialchars((string) ($item['variant_key'] ?? '')) ?>" placeholder="版本键" aria-label="版本键"></em>
                    <em>流量 <input name="traffic_percent" type="number" min="0" max="100" value="<?= (int) ($item['traffic_percent'] ?? 100) ?>" aria-label="流量占比"></em>
                    <em>应用
                        <select name="app_key" aria-label="应用">
                            <option value="all" <?= (string) ($item['app_key'] ?? 'all') === 'all' ? 'selected' : '' ?>>全部应用</option>
                            <?php foreach ($apps as $app): ?>
                                <?php $appKey = (string) ($app['app_key'] ?? ''); ?>
                                <option value="<?= htmlspecialchars($appKey) ?>" <?= (string) ($item['app_key'] ?? '') === $appKey ? 'selected' : '' ?>><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars($appKey) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </em>
                    <em>
                        <select name="status" aria-label="状态">
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>启用</option>
                            <option value="paused" <?= $status === 'paused' ? 'selected' : '' ?>>停用</option>
                        </select>
                    </em>
                    <em>排序 <input name="sort" type="number" value="<?= (int) ($item['sort'] ?? 100) ?>" aria-label="排序"></em>
                    <em>开始 <input name="started_at" type="datetime-local" value="<?= htmlspecialchars(str_replace(' ', 'T', (string) ($item['started_at'] ?? ''))) ?>" aria-label="开始时间"></em>
                    <em>结束 <input name="ended_at" type="datetime-local" value="<?= htmlspecialchars(str_replace(' ', 'T', (string) ($item['ended_at'] ?? ''))) ?>" aria-label="结束时间"></em>
                    <em><input name="image" value="<?= htmlspecialchars((string) ($item['image'] ?? '')) ?>" placeholder="图片" aria-label="图片"></em>
                    <em><input name="remark" value="<?= htmlspecialchars((string) ($item['remark'] ?? '')) ?>" placeholder="备注" aria-label="备注"></em>
                    <button class="btn ghost" type="submit">保存</button>
                </span>
            </form>
        <?php endforeach; ?>
    </div>
    <div class="row-card stack">
        <p><strong>接口口径</strong></p>
        <p class="muted">客户端可通过 /?route=api-operation-config&amp;app_key=default 拉取首页推荐、弹窗公告和活动配置，也可从 /?route=api-app-config 的 operation_config 字段读取。配置会按应用、状态、生效时间、目标人群和 A/B 分组自动过滤，可带 trigger 或 slot 只取指定场景。活动曝光/点击调用 /?route=api-activity-event，传 activity_id 或 activity_code 与 event=exposure|click；用户领奖调用 /?route=api-activity-claim，传 activity_id 或 activity_code。</p>
    </div>
    <form method="post" action="/jxdjadmin" class="row-card">
        <input type="hidden" name="admin_action" value="export_activity_funnel_csv">
        <input type="hidden" name="admin_section" value="activity-config">
        <?= $csrfField() ?>
        <span>
            <strong>导出活动复盘</strong>
            <em>包含活动/版本效果、预算使用、人群效果和自动复盘建议。</em>
        </span>
        <button class="btn ghost" type="submit" <?= empty($activityFunnelRows) && empty($activityTierRows) ? 'disabled' : '' ?>>导出CSV</button>
    </form>
    <?php
        $latestActivityLogs = array_slice($activityParticipationLogs, 0, 80);
        $activitySuccessCount = count(array_filter($activityParticipationLogs, static fn (array $log): bool => (string) ($log['status'] ?? '') === 'success'));
        $activityFailedCount = count(array_filter($activityParticipationLogs, static fn (array $log): bool => (string) ($log['status'] ?? '') === 'failed'));
    ?>
    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('stats') ?></span>
            <small>活动曝光</small>
            <strong><?= number_format((int) ($activityFunnelSummary['exposure'] ?? 0)) ?></strong>
            <em>埋点 exposure</em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('profit') ?></span>
            <small>活动点击</small>
            <strong><?= number_format((int) ($activityFunnelSummary['click'] ?? 0)) ?></strong>
            <em>点击率 <?= number_format((float) ($activityFunnelSummary['click_rate'] ?? 0), 2) ?>%</em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('payment') ?></span>
            <small>领奖成功</small>
            <strong><?= number_format((int) ($activityFunnelSummary['claim_success'] ?? 0)) ?></strong>
            <em>领奖转化 <?= number_format((float) ($activityFunnelSummary['claim_rate'] ?? 0), 2) ?>%</em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('user') ?></span>
            <small>A/B 实验</small>
            <strong><?= number_format((int) ($activityFunnelSummary['experiment_count'] ?? 0)) ?> 个</strong>
            <em>版本 <?= number_format((int) ($activityFunnelSummary['variant_count'] ?? 0)) ?> 个 · 人群 <?= number_format((int) ($activityFunnelSummary['tier_count'] ?? 0)) ?> 类</em>
        </div>
    </div>
    <div class="repair-grid">
        <div class="row-card stack">
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">分层复盘</span>
                    <h2>活动人群效果</h2>
                </div>
                <span class="muted">按新客、未付费、已付费、会员聚合</span>
            </div>
            <div class="order-table">
                <div class="row-card order-row-head">
                    <span>人群</span>
                    <span>曝光/点击</span>
                    <span>领奖</span>
                    <span>转化</span>
                </div>
                <?php if (empty($activityTierRows)): ?>
                    <p class="muted">暂无可分层的活动数据。</p>
                <?php endif; ?>
                <?php foreach ($activityTierRows as $tierRow): ?>
                    <?php $tierKey = (string) ($tierRow['user_tier'] ?? 'unknown'); ?>
                    <div class="row-card order-row">
                        <span>
                            <strong><?= htmlspecialchars((string) ($tierRow['tier_name'] ?? ($activityTierLabels[$tierKey] ?? '未识别人群'))) ?></strong>
                            <em>用户 <?= number_format((int) ($tierRow['unique_users'] ?? 0)) ?> · 最近 <?= htmlspecialchars((string) (($tierRow['latest_at'] ?? '') ?: '-')) ?></em>
                        </span>
                        <span>曝光 <?= number_format((int) ($tierRow['exposure'] ?? 0)) ?><em>点击 <?= number_format((int) ($tierRow['click'] ?? 0)) ?></em></span>
                        <span>成功 <?= number_format((int) ($tierRow['claim_success'] ?? 0)) ?><em>失败 <?= number_format((int) ($tierRow['claim_failed'] ?? 0)) ?></em></span>
                        <span>点击率 <?= number_format((float) ($tierRow['click_rate'] ?? 0), 2) ?>%<em>领奖率 <?= number_format((float) ($tierRow['claim_rate'] ?? 0), 2) ?>% · 失败率 <?= number_format((float) ($tierRow['fail_rate'] ?? 0), 2) ?>%</em></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="row-card stack">
            <p><strong>自动复盘建议</strong></p>
            <?php if (empty($activityReviewSuggestions)): ?>
                <p class="muted">暂无复盘建议。</p>
            <?php endif; ?>
            <?php foreach ($activityReviewSuggestions as $suggestion): ?>
                <p class="muted"><?= htmlspecialchars((string) $suggestion) ?></p>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="row-card stack">
        <div class="section-title admin-section-title">
            <div>
                <span class="eyebrow">转化漏斗</span>
                <h2>活动 A/B 效果</h2>
            </div>
            <span class="muted">按活动和版本汇总曝光、点击、领奖</span>
        </div>
        <div class="order-table">
            <div class="row-card order-row-head">
                <span>活动/版本</span>
                <span>曝光/点击</span>
                <span>领奖</span>
                <span>转化</span>
            </div>
            <?php if (empty($activityFunnelRows)): ?>
                <p class="muted">暂无活动曝光、点击或领奖数据。</p>
            <?php endif; ?>
            <?php foreach ($activityFunnelRows as $row): ?>
                <div class="row-card order-row">
                    <span>
                        <strong><?= htmlspecialchars((string) (($row['activity_name'] ?? '') ?: ($row['activity_code'] ?? '活动'))) ?></strong>
                        <em><?= htmlspecialchars((string) ($row['activity_code'] ?? '')) ?><?= (string) ($row['experiment_key'] ?? '') !== '' ? ' · 实验 ' . htmlspecialchars((string) ($row['experiment_key'] ?? '')) . '/' . htmlspecialchars((string) ($row['variant_key'] ?? '')) : '' ?></em>
                    </span>
                    <span>曝光 <?= number_format((int) ($row['exposure'] ?? 0)) ?><em>点击 <?= number_format((int) ($row['click'] ?? 0)) ?></em></span>
                    <span>成功 <?= number_format((int) ($row['claim_success'] ?? 0)) ?><em>失败 <?= number_format((int) ($row['claim_failed'] ?? 0)) ?></em></span>
                    <span>点击率 <?= number_format((float) ($row['click_rate'] ?? 0), 2) ?>%<em>领奖率 <?= number_format((float) ($row['claim_rate'] ?? 0), 2) ?>% · 流量 <?= number_format((int) ($row['traffic_percent'] ?? 100)) ?>%</em></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="row-card stack">
        <div class="section-title admin-section-title">
            <div>
                <span class="eyebrow">参与记录</span>
                <h2>活动领奖日志</h2>
            </div>
            <span class="muted">成功 <?= number_format($activitySuccessCount) ?> · 失败 <?= number_format($activityFailedCount) ?></span>
        </div>
        <div class="order-table">
            <div class="row-card order-row-head">
                <span>用户/时间</span>
                <span>活动</span>
                <span>奖励</span>
                <span>结果</span>
            </div>
            <?php if (empty($latestActivityLogs)): ?>
                <p class="muted">暂无活动参与记录。</p>
            <?php endif; ?>
            <?php foreach ($latestActivityLogs as $log): ?>
                <?php
                    $logUserId = (int) ($log['user_id'] ?? 0);
                    $rewardParts = [];
                    if ((int) ($log['coin_amount'] ?? 0) > 0) {
                        $rewardParts[] = 'K币 ' . number_format((int) ($log['coin_amount'] ?? 0));
                    }
                    if ((int) ($log['bonus_coin_amount'] ?? 0) > 0) {
                        $rewardParts[] = '赠币 ' . number_format((int) ($log['bonus_coin_amount'] ?? 0));
                    }
                    if ((int) ($log['vip_days'] ?? 0) > 0) {
                        $rewardParts[] = 'VIP ' . number_format((int) ($log['vip_days'] ?? 0)) . '天';
                    }
                    if ((int) ($log['redeem_code_id'] ?? 0) > 0) {
                        $rewardParts[] = '兑换码 ' . (int) ($log['redeem_code_id'] ?? 0);
                    }
                    $rewardText = empty($rewardParts) ? ($activityRewardLabels[(string) ($log['reward_type'] ?? 'none')] ?? '无奖励') : implode(' · ', $rewardParts);
                    $logStatus = (string) ($log['status'] ?? 'success');
                    $logEventType = (string) ($log['event_type'] ?? 'claim');
                    $logEventLabel = ['exposure' => '曝光', 'click' => '点击', 'claim' => '领奖'][$logEventType] ?? '领奖';
                    $logContext = is_array($log['context'] ?? null) ? (array) $log['context'] : [];
                    $logTier = (string) ($logContext['user_tier'] ?? '');
                    $logExperimentText = (string) ($log['experiment_key'] ?? '') !== ''
                        ? ' · 实验 ' . (string) ($log['experiment_key'] ?? '') . '/' . (string) ($log['variant_key'] ?? '')
                        : '';
                ?>
                <div class="row-card order-row">
                    <span>
                        <strong><?= htmlspecialchars((string) ($userNameById[$logUserId] ?? ('用户 ' . $logUserId))) ?></strong>
                        <em>ID <?= $logUserId ?> · <?= htmlspecialchars((string) ($log['created_at'] ?? '')) ?></em>
                    </span>
                    <span>
                        <?= htmlspecialchars((string) (($log['activity_name'] ?? '') ?: ($log['activity_code'] ?? '活动'))) ?>
                        <em><?= htmlspecialchars((string) ($log['activity_code'] ?? '')) ?> · <?= htmlspecialchars($activityTypeLabels[(string) ($log['activity_type'] ?? 'general')] ?? '通用') ?><?= htmlspecialchars($logExperimentText) ?></em>
                    </span>
                    <span>
                        <?= htmlspecialchars($rewardText) ?>
                        <em><?= htmlspecialchars((string) ($log['app_key'] ?? 'default')) ?><?= $logTier !== '' ? ' · ' . htmlspecialchars($activityTierLabels[$logTier] ?? $logTier) : '' ?></em>
                    </span>
                    <span>
                        <strong><?= htmlspecialchars($logEventLabel) ?> · <?= $logStatus === 'success' ? '成功' : ($logStatus === 'tracked' ? '已记录' : '失败') ?></strong>
                        <em><?= htmlspecialchars((string) (($log['message'] ?? '') ?: '-')) ?></em>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'promotion-links' ? 'is-active' : '' ?>" id="admin-section-promotion-links" data-admin-section="promotion-links" data-admin-primary="operation">
    <div class="section-title admin-section-title">
        <h2>投放链接</h2>
        <span class="muted">先把“推广入口 -> 用户访问 -> 下单充值”串起来，后续再接 T+N 回收和平台回传。</span>
    </div>
    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('profit') ?></span>
            <small>推广链接</small>
            <strong><?= number_format((int) ($promotionSummary['link_count'] ?? 0)) ?> 条</strong>
            <em>启用 <?= number_format((int) ($promotionSummary['active_link_count'] ?? 0)) ?> 条</em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('stats') ?></span>
            <small>访问点击</small>
            <strong><?= number_format((int) ($promotionSummary['visits'] ?? 0)) ?></strong>
            <em>来自推广入口访问</em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('orders') ?></span>
            <small>付费订单</small>
            <strong><?= number_format((int) ($promotionSummary['paid_orders'] ?? 0)) ?> 笔</strong>
            <em>转化率 <?= number_format((float) ($promotionSummary['conversion_rate'] ?? 0), 2) ?>%</em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('revenue') ?></span>
            <small>归因收入</small>
            <strong><?= htmlspecialchars($money((float) ($promotionSummary['revenue'] ?? 0))) ?></strong>
            <em>回本 <?= ($promotionSummary['recovery_rate'] ?? null) === null ? '-' : number_format((float) $promotionSummary['recovery_rate'], 2) . '%' ?></em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('profit') ?></span>
            <small>投放消耗</small>
            <strong><?= htmlspecialchars($money((float) ($promotionSummary['cost'] ?? 0))) ?></strong>
            <em>加桌成本 <?= ($promotionSummary['add_desktop_cost'] ?? null) === null ? '-' : htmlspecialchars($money((float) $promotionSummary['add_desktop_cost'])) ?></em>
        </div>
    </div>

    <form method="post" action="/jxdjadmin" class="row-card stack">
        <input type="hidden" name="admin_action" value="create_promotion_link">
        <input type="hidden" name="admin_section" value="promotion-links">
        <?= $csrfField() ?>
        <p><strong>新建推广入口</strong></p>
        <div class="form-grid">
            <label>推广名称<input name="name" placeholder="例如：七月投流-第3集"></label>
            <label>内容类型
                <select name="content_type">
                    <option value="drama">短剧</option>
                    <option value="novel">小说</option>
                </select>
            </label>
            <label>短剧
                <select name="drama_id">
                    <option value="0">不绑定短剧</option>
                    <?php foreach ($dramas as $drama): ?>
                        <option value="<?= (int) ($drama['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($drama['title'] ?? '未命名短剧')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>起始分集ID<input name="episode_id" placeholder="不填则进短剧预览页"></label>
            <label>小说
                <select name="novel_id">
                    <option value="0">不绑定小说</option>
                    <?php foreach ($novels as $novel): ?>
                        <option value="<?= (int) ($novel['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($novel['title'] ?? '未命名小说')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>起始章节ID<input name="chapter_id" placeholder="不填则进小说详情页"></label>
            <label>代理
                <select name="agent_id">
                    <option value="0">未分配代理</option>
                    <?php foreach ($agentOptions as $agent): ?>
                        <?php $agentId = (int) ($agent['id'] ?? 0); ?>
                        <option value="<?= $agentId ?>"><?= htmlspecialchars($agentPathById[$agentId] ?? (string) ($agent['name'] ?? '代理')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>来源<input name="source" placeholder="douyin / kuaishou / wx"></label>
            <label>计划名<input name="campaign" placeholder="计划/素材/批次"></label>
            <label>投放平台<input name="traffic_platform" placeholder="巨量 / 快手 / 百度 / TikTok"></label>
            <label>渠道ID<input name="channel_id" placeholder="channel / cid"></label>
            <label>应用
                <select name="app_key">
                    <option value="">不绑定应用</option>
                    <?php foreach ($apps as $app): ?>
                        <option value="<?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?>"><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>广告ID<input name="ad_id" placeholder="ad_id"></label>
            <label>创意ID<input name="creative_id" placeholder="creative_id"></label>
            <label>素材ID<input name="material_id" placeholder="material_id"></label>
            <label>投放预算<input name="cost_budget_limit" type="number" step="0.01" min="0" placeholder="0 不限制"></label>
            <label>最低回本率(%)<input name="min_recovery_rate" type="number" step="0.01" min="0" placeholder="低于则停投"></label>
            <label>最低消耗门槛<input name="auto_pause_min_cost" type="number" step="0.01" min="0" placeholder="例如 100"></label>
            <label><span><input type="checkbox" name="auto_pause_on_cost" value="1"> 自动停投</span></label>
            <label>状态
                <select name="status">
                    <option value="active">推广中</option>
                    <option value="review">审核中</option>
                    <option value="paused">已暂停</option>
                </select>
            </label>
            <label>跳转方式
                <select name="jump_mode">
                    <option value="auto">自动跳转</option>
                    <option value="app">始终跳应用</option>
                    <option value="review">始终跳审核</option>
                </select>
            </label>
            <label>真实跳转<input name="target_url" placeholder="默认进所选短剧"></label>
            <label>审核跳转<input name="review_url" placeholder="审核时跳转的安全链接"></label>
        </div>
        <p><button class="btn" type="submit">创建推广链接</button></p>
    </form>

    <form method="post" action="/jxdjadmin" class="row-card stack">
        <input type="hidden" name="admin_action" value="save_promotion_cost">
        <input type="hidden" name="admin_section" value="promotion-links">
        <?= $csrfField() ?>
        <p><strong>录入每日投放消耗</strong></p>
        <div class="form-grid">
            <label>投放链接
                <select name="promotion_link_id">
                    <option value="0">未绑定链接</option>
                    <?php foreach ($promotionLinks as $link): ?>
                        <option value="<?= (int) ($link['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($link['name'] ?? $link['code'] ?? '推广链接')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>日期<input name="cost_date" type="date" value="<?= htmlspecialchars(date('Y-m-d')) ?>"></label>
            <label>消耗金额<input name="amount" type="number" step="0.01" min="0.01" placeholder="例如 200.00"></label>
            <label>曝光<input name="impressions" type="number" min="0" placeholder="可选"></label>
            <label>点击<input name="clicks" type="number" min="0" placeholder="可选"></label>
            <label>投放平台<input name="traffic_platform" placeholder="默认取推广链接"></label>
            <label>广告ID<input name="ad_id" placeholder="默认取推广链接"></label>
            <label>素材ID<input name="material_id" placeholder="默认取推广链接"></label>
            <label>备注<input name="remark" placeholder="平台/素材/计划备注"></label>
        </div>
        <p><button class="btn" type="submit">保存消耗</button></p>
    </form>

    <form method="post" action="/jxdjadmin" class="row-card stack">
        <input type="hidden" name="admin_action" value="save_promotion_replacement_rule">
        <input type="hidden" name="admin_section" value="promotion-links">
        <?= $csrfField() ?>
        <p><strong>分时段替换推广目标</strong> <span class="muted">同一推广码按时间切到不同短剧/小说章节，适合审核期、冷启动和素材疲劳切换。</span></p>
        <div class="form-grid">
            <label>投放链接
                <select name="promotion_link_id">
                    <option value="0">请选择链接</option>
                    <?php foreach ($promotionLinks as $link): ?>
                        <option value="<?= (int) ($link['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($link['name'] ?? $link['code'] ?? '推广链接')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>规则名称<input name="replacement_name" placeholder="例如：审核后切第8章"></label>
            <label>内容类型
                <select name="replacement_content_type">
                    <option value="drama">短剧</option>
                    <option value="novel">小说</option>
                </select>
            </label>
            <label>短剧
                <select name="replacement_drama_id">
                    <option value="0">不绑定短剧</option>
                    <?php foreach ($dramas as $drama): ?>
                        <option value="<?= (int) ($drama['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($drama['title'] ?? '未命名短剧')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>分集ID<input name="replacement_episode_id" placeholder="可选"></label>
            <label>小说
                <select name="replacement_novel_id">
                    <option value="0">不绑定小说</option>
                    <?php foreach ($novels as $novel): ?>
                        <option value="<?= (int) ($novel['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($novel['title'] ?? '未命名小说')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>章节ID<input name="replacement_chapter_id" placeholder="可选"></label>
            <label>开始时间<input name="started_at" type="datetime-local"></label>
            <label>结束时间<input name="ended_at" type="datetime-local"></label>
            <label>优先级<input name="priority" type="number" min="0" value="0"></label>
            <label>状态
                <select name="replacement_status">
                    <option value="active">启用</option>
                    <option value="paused">暂停</option>
                </select>
            </label>
            <label>真实跳转<input name="replacement_target_url" placeholder="不填则按内容自动生成"></label>
            <label>审核跳转<input name="replacement_review_url" placeholder="审核模式下可单独配置"></label>
            <label>备注<input name="remark" placeholder="规则用途"></label>
        </div>
        <p><button class="btn" type="submit">保存替换规则</button></p>
    </form>

    <div class="order-table">
        <div class="row-card order-row-head">
            <span>入口</span>
            <span>内容/代理</span>
            <span>新增/加桌</span>
            <span>充值/回收</span>
        </div>
        <?php if (empty($promotionRows)): ?>
            <p class="muted">暂无推广链接。先创建一条入口，复制链接去投放，后续订单会自动带来源。</p>
        <?php endif; ?>
        <?php foreach ($promotionRows as $row): ?>
            <?php
                $link = (array) ($row['link'] ?? []);
                $contentType = (string) ($link['content_type'] ?? 'drama');
                $contentTitle = $contentType === 'novel'
                    ? ($novelTitleById[(int) ($link['novel_id'] ?? 0)] ?? '未绑定小说')
                    : (string) ($row['drama_title'] ?? '未绑定短剧');
                $contentSub = $contentType === 'novel'
                    ? ('小说' . ((int) ($link['chapter_id'] ?? 0) > 0 ? ' · 章节 ' . (int) $link['chapter_id'] : ''))
                    : ('短剧' . ((int) ($link['episode_id'] ?? 0) > 0 ? ' · 分集 ' . (int) $link['episode_id'] : ''));
                $trafficLines = $trafficMetaLines($link);
                $replacementRules = array_values((array) ($link['replacement_rules'] ?? []));
                $costBudgetLimit = (float) ($row['cost_budget_limit'] ?? $link['cost_budget_limit'] ?? 0);
                $budgetUsedRate = $row['budget_used_rate'] ?? null;
                $minRecoveryRate = (float) ($row['min_recovery_rate'] ?? $link['min_recovery_rate'] ?? 0);
                $autoPauseMinCost = (float) ($row['auto_pause_min_cost'] ?? $link['auto_pause_min_cost'] ?? 0);
                $autoPausedReason = trim((string) ($link['auto_paused_reason'] ?? ''));
            ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($link['name'] ?? '推广链接')) ?></strong>
                    <em><?= htmlspecialchars((string) ($row['entry_url'] ?? '')) ?></em>
                    <em>状态：<?= htmlspecialchars(match ((string) ($link['status'] ?? 'active')) { 'review' => '审核中', 'paused' => '已暂停', default => '推广中' }) ?></em>
                    <?php if (!empty($row['auto_pause_on_cost'])): ?>
                        <em>自动停投：<?= $costBudgetLimit > 0 ? '预算 ' . htmlspecialchars($money($costBudgetLimit)) : '无预算上限' ?><?= $minRecoveryRate > 0 ? ' · 回本低于 ' . number_format($minRecoveryRate, 2) . '%' : '' ?></em>
                    <?php endif; ?>
                    <?php if ($autoPausedReason !== ''): ?>
                        <em>停投原因：<?= htmlspecialchars($autoPausedReason) ?></em>
                    <?php endif; ?>
                    <em>替换规则 <?= number_format(count($replacementRules)) ?> 条</em>
                </span>
                <span>
                    <?= htmlspecialchars($contentTitle) ?>
                    <em><?= htmlspecialchars($contentSub) ?></em>
                    <em><?= htmlspecialchars((string) ($row['agent_name'] ?? '未分配代理')) ?></em>
                    <em><?= htmlspecialchars((string) (($link['source'] ?? '') ?: '未填来源')) ?> <?= htmlspecialchars((string) (($link['campaign'] ?? '') ?: '')) ?></em>
                    <?php foreach (array_slice($trafficLines, 0, 3) as $line): ?>
                        <em><?= htmlspecialchars($line) ?></em>
                    <?php endforeach; ?>
                </span>
                <span>
                    访问 <?= number_format((int) ($row['visits'] ?? 0)) ?>
                    <em>新增 <?= number_format((int) ($row['registers'] ?? 0)) ?> · 加桌 <?= number_format((int) ($row['add_desktop'] ?? 0)) ?></em>
                </span>
                <span>
                    <?= htmlspecialchars($money((float) ($row['revenue'] ?? 0))) ?>
                    <em>消耗 <?= htmlspecialchars($money((float) ($row['cost'] ?? 0))) ?> · 回本 <?= ($row['recovery_rate'] ?? null) === null ? '-' : number_format((float) $row['recovery_rate'], 2) . '%' ?></em>
                    <?php if ($costBudgetLimit > 0): ?>
                        <em>预算 <?= htmlspecialchars($money((float) ($row['cost'] ?? 0))) ?> / <?= htmlspecialchars($money($costBudgetLimit)) ?><?= $budgetUsedRate === null ? '' : ' · ' . number_format((float) $budgetUsedRate, 2) . '%' ?></em>
                    <?php endif; ?>
                    <?php if ($minRecoveryRate > 0): ?>
                        <em>保护线 <?= number_format($minRecoveryRate, 2) ?>%<?= $autoPauseMinCost > 0 ? ' · 门槛 ' . htmlspecialchars($money($autoPauseMinCost)) : '' ?></em>
                    <?php endif; ?>
                    <em>付费 <?= number_format((int) ($row['paid_orders'] ?? 0)) ?> · 下单成本 <?= ($row['order_cost'] ?? null) === null ? '-' : htmlspecialchars($money((float) $row['order_cost'])) ?></em>
                </span>
            </div>
            <?php if (!empty($replacementRules)): ?>
                <div class="row-card stack">
                    <p><strong><?= htmlspecialchars((string) ($link['name'] ?? '推广链接')) ?> / 分时段替换</strong></p>
                    <div class="order-info-grid">
                        <?php foreach (array_slice($replacementRules, 0, 6) as $rule): ?>
                            <?php
                                $ruleContent = (string) (($rule['content_type'] ?? 'drama') === 'novel'
                                    ? ('小说 ' . (int) ($rule['novel_id'] ?? 0) . ((int) ($rule['chapter_id'] ?? 0) > 0 ? ' · 章 ' . (int) $rule['chapter_id'] : ''))
                                    : ('短剧 ' . (int) ($rule['drama_id'] ?? 0) . ((int) ($rule['episode_id'] ?? 0) > 0 ? ' · 集 ' . (int) $rule['episode_id'] : '')));
                            ?>
                            <div>
                                <span><?= htmlspecialchars((string) ($rule['name'] ?? '替换规则')) ?> · <?= htmlspecialchars((string) (($rule['status'] ?? 'active') === 'active' ? '启用' : '暂停')) ?></span>
                                <strong><?= htmlspecialchars($ruleContent) ?></strong>
                                <em><?= htmlspecialchars((string) (($rule['started_at'] ?? '') ?: '立即')) ?> 至 <?= htmlspecialchars((string) (($rule['ended_at'] ?? '') ?: '长期')) ?></em>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="row-card stack">
        <p><strong>事件上报口径</strong></p>
        <p class="muted">推广入口自动记录访问；登录后记录注册；支付成功记录激活和付费。小程序加桌后可调用 /?route=api-promotion-event&event=add_desktop&code=推广码，上报后会进入加桌人数、加桌成本和回收趋势。</p>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'landing-pages' ? 'is-active' : '' ?>" id="admin-section-landing-pages" data-admin-section="landing-pages" data-admin-primary="operation">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">推广工具</span>
            <h2>推广落地页</h2>
        </div>
        <span class="muted"><?= number_format(count($landingPages)) ?> 个页面 · 访问 <?= number_format(count(array_filter($landingPageEvents, static fn (array $event): bool => (string) ($event['event'] ?? '') === 'view'))) ?> · 点击 <?= number_format(count(array_filter($landingPageEvents, static fn (array $event): bool => (string) ($event['event'] ?? '') === 'click'))) ?></span>
    </div>

    <form method="post" action="/jxdjadmin#landing-pages" class="row-card stack">
        <input type="hidden" name="admin_action" value="save_landing_page">
        <input type="hidden" name="admin_section" value="landing-pages">
        <?= $csrfField() ?>
        <p><strong>新建推广落地页</strong></p>
        <div class="form-grid">
            <label>页面名称<input name="name" placeholder="例如：7月巨量短剧承接页"></label>
            <label>访问标识<input name="slug" placeholder="不填自动生成，可用字母数字"></label>
            <label>标题<input name="title" placeholder="默认取绑定作品标题"></label>
            <label>副标题<input name="subtitle" placeholder="一句话卖点"></label>
            <label>模板
                <select name="template">
                    <option value="drama">短剧落地页</option>
                    <option value="novel">小说落地页</option>
                    <option value="mixed">混合落地页</option>
                </select>
            </label>
            <label>状态
                <select name="status">
                    <option value="active">启用</option>
                    <option value="review">审核</option>
                    <option value="paused">暂停</option>
                </select>
            </label>
            <label>绑定推广链接
                <select name="promotion_link_id">
                    <option value="0">不绑定推广链接</option>
                    <?php foreach ($promotionLinks as $link): ?>
                        <option value="<?= (int) ($link['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($link['name'] ?? $link['code'] ?? '推广链接')) ?> · <?= htmlspecialchars((string) ($link['code'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>代理
                <select name="agent_id">
                    <option value="0">跟随推广链接或不绑定</option>
                    <?php foreach ($agentOptions as $agent): ?>
                        <?php $agentId = (int) ($agent['id'] ?? 0); ?>
                        <option value="<?= $agentId ?>"><?= htmlspecialchars($agentPathById[$agentId] ?? (string) ($agent['name'] ?? '代理')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>内容类型
                <select name="content_type">
                    <option value="drama">短剧</option>
                    <option value="novel">小说</option>
                </select>
            </label>
            <label>短剧
                <select name="drama_id">
                    <option value="0">不绑定短剧</option>
                    <?php foreach ($dramas as $drama): ?>
                        <option value="<?= (int) ($drama['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($drama['title'] ?? '未命名短剧')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>起始分集ID<input name="episode_id" placeholder="可选"></label>
            <label>小说
                <select name="novel_id">
                    <option value="0">不绑定小说</option>
                    <?php foreach ($novels as $novel): ?>
                        <option value="<?= (int) ($novel['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($novel['title'] ?? '未命名小说')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>起始章节ID<input name="chapter_id" placeholder="可选"></label>
            <label>应用
                <select name="app_key">
                    <option value="">不绑定应用</option>
                    <?php foreach ($apps as $app): ?>
                        <option value="<?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?>"><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>按钮文案<input name="cta_text" placeholder="立即观看 / 立即阅读"></label>
            <label>按钮目标
                <select name="cta_mode">
                    <option value="content">直达绑定内容</option>
                    <option value="promotion">使用推广链接跳转策略</option>
                    <option value="custom">自定义链接</option>
                </select>
            </label>
            <label>自定义链接<input name="cta_url" placeholder="按钮目标选自定义时填写"></label>
            <label>封面图<input name="cover" placeholder="不填取作品封面"></label>
            <label>徽标<input name="badge" placeholder="例如：爆款热播"></label>
            <label>投放平台<input name="traffic_platform" placeholder="默认取推广链接"></label>
            <label>渠道ID<input name="channel_id" placeholder="默认取推广链接"></label>
            <label>广告ID<input name="ad_id" placeholder="默认取推广链接"></label>
            <label>创意ID<input name="creative_id" placeholder="默认取推广链接"></label>
            <label>素材ID<input name="material_id" placeholder="默认取推广链接"></label>
            <label>排序<input name="sort" type="number" value="100"></label>
        </div>
        <label>卖点文案
            <textarea name="selling_points_text" rows="3" placeholder="每行一个卖点，例如：&#10;前三集免费看&#10;充值后自动解锁&#10;适合投流承接"></textarea>
        </label>
        <p><button class="btn" type="submit">保存落地页</button></p>
    </form>

    <div class="order-table">
        <div class="row-card order-row-head">
            <span>落地页</span>
            <span>绑定内容</span>
            <span>归因</span>
            <span>数据</span>
        </div>
        <?php if (empty($landingPages)): ?>
            <p class="muted">暂无推广落地页。创建后可复制 /lp/标识 用于投放承接。</p>
        <?php endif; ?>
        <?php foreach ($landingPages as $page): ?>
            <?php
                $landingId = (int) ($page['id'] ?? 0);
                $landingContentType = (string) ($page['content_type'] ?? 'drama');
                $landingContentTitle = $landingContentType === 'novel'
                    ? ($novelTitleById[(int) ($page['novel_id'] ?? 0)] ?? '未绑定小说')
                    : (string) (($dramaTitleById[(int) ($page['drama_id'] ?? 0)] ?? '') ?: '未绑定短剧');
                $landingTrafficLines = $trafficMetaLines($page);
                $landingViews = (int) ($landingEventCounts[$landingId]['view'] ?? ($page['views'] ?? 0));
                $landingClicks = (int) ($landingEventCounts[$landingId]['click'] ?? ($page['clicks'] ?? 0));
                $landingRate = $landingViews > 0 ? ($landingClicks / max(1, $landingViews) * 100) : 0;
            ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($page['name'] ?? '推广落地页')) ?></strong>
                    <em>/lp/<?= htmlspecialchars((string) ($page['slug'] ?? '')) ?></em>
                    <em><?= htmlspecialchars($landingTemplateLabels[(string) ($page['template'] ?? 'drama')] ?? '落地页') ?> · <?= htmlspecialchars($landingStatusLabels[(string) ($page['status'] ?? 'active')] ?? '启用') ?></em>
                    <a class="btn mini ghost" href="/lp/<?= rawurlencode((string) ($page['slug'] ?? '')) ?>" target="_blank">预览</a>
                </span>
                <span>
                    <?= htmlspecialchars($landingContentTitle) ?>
                    <em><?= $landingContentType === 'novel' ? '小说' : '短剧' ?> · <?= $landingContentType === 'novel' ? ('章节 ' . (int) ($page['chapter_id'] ?? 0)) : ('分集 ' . (int) ($page['episode_id'] ?? 0)) ?></em>
                    <em>按钮：<?= htmlspecialchars((string) ($page['cta_text'] ?? '立即观看')) ?> · <?= htmlspecialchars(match ((string) ($page['cta_mode'] ?? 'content')) { 'promotion' => '推广策略', 'custom' => '自定义', default => '绑定内容' }) ?></em>
                </span>
                <span>
                    推广码 <?= htmlspecialchars((string) (($page['promotion_code'] ?? '') ?: '-')) ?>
                    <em>链接 #<?= number_format((int) ($page['promotion_link_id'] ?? 0)) ?> · 代理 #<?= number_format((int) ($page['agent_id'] ?? 0)) ?></em>
                    <?php foreach (array_slice($landingTrafficLines, 0, 3) as $line): ?>
                        <em><?= htmlspecialchars($line) ?></em>
                    <?php endforeach; ?>
                </span>
                <span>
                    访问 <?= number_format($landingViews) ?> · 点击 <?= number_format($landingClicks) ?>
                    <em>点击率 <?= number_format($landingRate, 2) ?>%</em>
                    <em><?= htmlspecialchars((string) ($page['updated_at'] ?? '')) ?></em>
                </span>
            </div>
            <form method="post" action="/jxdjadmin#landing-pages" class="row-card stack">
                <input type="hidden" name="admin_action" value="save_landing_page">
                <input type="hidden" name="admin_section" value="landing-pages">
                <input type="hidden" name="landing_page_id" value="<?= $landingId ?>">
                <?= $csrfField() ?>
                <p><strong>编辑：<?= htmlspecialchars((string) ($page['name'] ?? '推广落地页')) ?></strong></p>
                <div class="form-grid">
                    <label>页面名称<input name="name" value="<?= htmlspecialchars((string) ($page['name'] ?? '')) ?>"></label>
                    <label>访问标识<input name="slug" value="<?= htmlspecialchars((string) ($page['slug'] ?? '')) ?>"></label>
                    <label>标题<input name="title" value="<?= htmlspecialchars((string) ($page['title'] ?? '')) ?>"></label>
                    <label>副标题<input name="subtitle" value="<?= htmlspecialchars((string) ($page['subtitle'] ?? '')) ?>"></label>
                    <label>状态
                        <select name="status">
                            <?php foreach ($landingStatusLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($page['status'] ?? 'active') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>绑定推广链接<input name="promotion_link_id" value="<?= (int) ($page['promotion_link_id'] ?? 0) ?>"></label>
                    <label>内容类型<input name="content_type" value="<?= htmlspecialchars((string) ($page['content_type'] ?? 'drama')) ?>"></label>
                    <label>短剧ID<input name="drama_id" value="<?= (int) ($page['drama_id'] ?? 0) ?>"></label>
                    <label>分集ID<input name="episode_id" value="<?= (int) ($page['episode_id'] ?? 0) ?>"></label>
                    <label>小说ID<input name="novel_id" value="<?= (int) ($page['novel_id'] ?? 0) ?>"></label>
                    <label>章节ID<input name="chapter_id" value="<?= (int) ($page['chapter_id'] ?? 0) ?>"></label>
                    <label>应用<input name="app_key" value="<?= htmlspecialchars((string) ($page['app_key'] ?? '')) ?>"></label>
                    <label>按钮文案<input name="cta_text" value="<?= htmlspecialchars((string) ($page['cta_text'] ?? '')) ?>"></label>
                    <label>按钮目标<input name="cta_mode" value="<?= htmlspecialchars((string) ($page['cta_mode'] ?? 'content')) ?>"></label>
                    <label>自定义链接<input name="cta_url" value="<?= htmlspecialchars((string) ($page['cta_url'] ?? '')) ?>"></label>
                    <label>广告ID<input name="ad_id" value="<?= htmlspecialchars((string) ($page['ad_id'] ?? '')) ?>"></label>
                    <label>素材ID<input name="material_id" value="<?= htmlspecialchars((string) ($page['material_id'] ?? '')) ?>"></label>
                </div>
                <label>卖点文案<textarea name="selling_points_text" rows="2"><?= htmlspecialchars(implode("\n", (array) ($page['selling_points'] ?? []))) ?></textarea></label>
                <p><button class="btn ghost" type="submit">保存编辑</button></p>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'ad-slots' ? 'is-active' : '' ?>" id="admin-section-ad-slots" data-admin-section="ad-slots" data-admin-primary="operation">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">广告管理</span>
            <h2>广告位设置</h2>
        </div>
        <span class="muted"><?= number_format(count($adSlots)) ?> 个广告位 · 曝光 <?= number_format((int) ($adMonetizationSummary['impressions'] ?? 0)) ?> · 收益 <?= htmlspecialchars($money((float) ($adMonetizationSummary['revenue'] ?? 0))) ?></span>
    </div>

    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('banner') ?></span>
            <small>广告位</small>
            <strong><?= number_format(count($adSlots)) ?></strong>
            <em>按应用和位置配置</em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('stats') ?></span>
            <small>展示 / eCPM</small>
            <strong><?= number_format((int) ($adMonetizationSummary['impressions'] ?? 0)) ?></strong>
            <em>eCPM <?= number_format((float) ($adMonetizationSummary['ecpm'] ?? 0), 2) ?></em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('revenue') ?></span>
            <small>广告收益</small>
            <strong><?= htmlspecialchars($money((float) ($adMonetizationSummary['revenue'] ?? 0))) ?></strong>
            <em>填充 <?= ($adMonetizationSummary['fill_rate'] ?? null) === null ? '-' : number_format((float) $adMonetizationSummary['fill_rate'], 2) . '%' ?> · CTR <?= ($adMonetizationSummary['ctr'] ?? null) === null ? '-' : number_format((float) $adMonetizationSummary['ctr'], 2) . '%' ?></em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('coin') ?></span>
            <small>激励完成</small>
            <strong><?= number_format((int) ($adMonetizationSummary['rewards'] ?? 0)) ?></strong>
            <em>发放 <?= number_format((int) ($adMonetizationSummary['reward_coins'] ?? 0)) ?> K币</em>
        </div>
    </div>

    <form method="post" action="/jxdjadmin#ad-slots" class="row-card stack">
        <input type="hidden" name="admin_action" value="save_ad_waterfall_config">
        <input type="hidden" name="admin_section" value="ad-slots">
        <?= $csrfField() ?>
        <p><strong>广告瀑布流择优</strong></p>
        <div class="form-grid">
            <label>自动择优
                <select name="enabled">
                    <option value="1" <?= !array_key_exists('enabled', $adWaterfallConfig) || !empty($adWaterfallConfig['enabled']) ? 'selected' : '' ?>>开启</option>
                    <option value="0" <?= array_key_exists('enabled', $adWaterfallConfig) && empty($adWaterfallConfig['enabled']) ? 'selected' : '' ?>>关闭</option>
                </select>
            </label>
            <label>排序模式
                <select name="mode">
                    <option value="auto" <?= (string) ($adWaterfallConfig['mode'] ?? 'auto') === 'auto' ? 'selected' : '' ?>>自动打分</option>
                    <option value="manual" <?= (string) ($adWaterfallConfig['mode'] ?? 'auto') === 'manual' ? 'selected' : '' ?>>仅人工排序</option>
                </select>
            </label>
            <label>统计窗口天数<input name="score_window_days" type="number" min="1" max="90" value="<?= (int) ($adWaterfallConfig['score_window_days'] ?? 7) ?>"></label>
            <label>最小样本量<input name="min_requests" type="number" min="0" value="<?= (int) ($adWaterfallConfig['min_requests'] ?? 20) ?>"></label>
            <label>最多返回广告位<input name="max_slots" type="number" min="0" value="<?= (int) ($adWaterfallConfig['max_slots'] ?? 0) ?>"></label>
            <label>eCPM权重<input name="ecpm_weight" type="number" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string) ($adWaterfallConfig['ecpm_weight'] ?? 45)) ?>"></label>
            <label>填充率权重<input name="fill_rate_weight" type="number" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string) ($adWaterfallConfig['fill_rate_weight'] ?? 25)) ?>"></label>
            <label>CTR权重<input name="ctr_weight" type="number" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string) ($adWaterfallConfig['ctr_weight'] ?? 10)) ?>"></label>
            <label>激励完成权重<input name="reward_rate_weight" type="number" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string) ($adWaterfallConfig['reward_rate_weight'] ?? 10)) ?>"></label>
            <label>失败扣分权重<input name="failure_penalty_weight" type="number" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string) ($adWaterfallConfig['failure_penalty_weight'] ?? 25)) ?>"></label>
            <label>人工排序权重<input name="manual_sort_weight" type="number" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string) ($adWaterfallConfig['manual_sort_weight'] ?? 10)) ?>"></label>
        </div>
        <label>备注<input name="remark" value="<?= htmlspecialchars((string) ($adWaterfallConfig['remark'] ?? '')) ?>" placeholder="例如：冷启动期降低最小样本量，成熟期提高 eCPM 权重"></label>
        <p><button class="btn" type="submit">保存瀑布流配置</button></p>
    </form>

    <form method="post" action="/jxdjadmin#ad-slots" class="row-card stack">
        <input type="hidden" name="admin_action" value="save_ad_slot">
        <input type="hidden" name="admin_section" value="ad-slots">
        <?= $csrfField() ?>
        <p><strong>新增广告位</strong></p>
        <div class="form-grid">
            <label>广告位名称<input name="name" placeholder="例如：首页横幅广告"></label>
            <label>广告位编码<input name="code" placeholder="home_banner_top，不填自动生成"></label>
            <label>广告类型
                <select name="ad_type">
                    <?php foreach ($adTypeLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>展示位置
                <select name="position">
                    <?php foreach ($adPositionLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>应用
                <select name="app_key">
                    <option value="default">默认应用</option>
                    <?php foreach ($apps as $app): ?>
                        <option value="<?= htmlspecialchars((string) ($app['app_key'] ?? 'default')) ?>"><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars((string) ($app['app_key'] ?? 'default')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>状态
                <select name="status">
                    <?php foreach ($adStatusLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>广告平台<input name="provider" placeholder="穿山甲 / 优量汇 / 快手联盟"></label>
            <label>广告单元ID<input name="unit_id" placeholder="ad unit id"></label>
            <label>预估eCPM<input name="estimate_ecpm" type="number" min="0" step="0.01" value="0"></label>
            <label>收益分成%<input name="revenue_share_rate" type="number" min="0" max="100" step="0.01" value="100"></label>
            <label>标题<input name="title" placeholder="自运营广告标题，可选"></label>
            <label>图片<input name="image" placeholder="自运营广告图片，可选"></label>
            <label>跳转链接<input name="link" placeholder="自运营广告跳转链接，可选"></label>
            <label>激励K币<input name="reward_coins" type="number" min="0" value="0"></label>
            <label>每日上限<input name="daily_limit" type="number" min="0" value="0"></label>
            <label>频控秒数<input name="frequency_seconds" type="number" min="0" value="0"></label>
            <label>排序<input name="sort" type="number" value="100"></label>
        </div>
        <label>备注<input name="remark" placeholder="投放平台、审核要求或前端接入说明"></label>
        <p><button class="btn" type="submit">保存广告位</button></p>
    </form>

    <div class="repair-grid">
        <form method="post" action="/jxdjadmin#ad-slots" class="row-card stack">
            <input type="hidden" name="admin_action" value="save_ad_platform_config">
            <input type="hidden" name="admin_section" value="ad-slots">
            <?= $csrfField() ?>
            <p><strong>新增广告平台配置</strong></p>
            <div class="form-grid">
                <label>平台
                    <select name="provider">
                        <?php foreach ($adProviderLabels as $providerValue => $providerText): ?>
                            <option value="<?= htmlspecialchars($providerValue) ?>"><?= htmlspecialchars($providerText) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>显示名称<input name="provider_name" placeholder="例如：穿山甲"></label>
                <label>应用
                    <select name="app_key">
                        <option value="default">默认应用</option>
                        <?php foreach ($apps as $app): ?>
                            <option value="<?= htmlspecialchars((string) ($app['app_key'] ?? 'default')) ?>"><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars((string) ($app['app_key'] ?? 'default')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>状态
                    <select name="status">
                        <option value="active">启用</option>
                        <option value="test">测试</option>
                        <option value="paused">停用</option>
                    </select>
                </label>
                <label>平台AppID<input name="platform_app_id" placeholder="SDK AppID"></label>
                <label>SDK Key<input name="sdk_key" placeholder="可选"></label>
                <label>账号ID<input name="account_id" placeholder="广告账号/主体ID"></label>
                <label>媒体ID<input name="media_id" placeholder="媒体/应用ID"></label>
                <label>默认eCPM<input name="default_ecpm" type="number" min="0" step="0.01" value="0"></label>
                <label>收益分成%<input name="revenue_share_rate" type="number" min="0" max="100" step="0.01" value="100"></label>
                <label>币种<input name="currency" value="CNY"></label>
                <label>测试模式
                    <select name="test_mode">
                        <option value="0">关闭</option>
                        <option value="1">开启</option>
                    </select>
                </label>
            </div>
            <label>初始化参数 JSON<textarea name="init_params_text" rows="3" placeholder='{"debug":false}'></textarea></label>
            <label>隐私说明<input name="privacy_note" placeholder="SDK 合规、个性化广告说明"></label>
            <label>备注<input name="remark" placeholder="接入说明"></label>
            <p><button class="btn" type="submit">保存平台配置</button></p>
        </form>
        <div class="row-card stack">
            <p><strong>广告平台配置</strong></p>
            <?php if (empty($adPlatformConfigs)): ?>
                <p class="muted">暂无平台配置。保存后前端可调用 /?route=api-ad-platform-configs&amp;app_key=default 拉取 SDK 初始化参数。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach ($adPlatformConfigs as $platformConfig): ?>
                        <form method="post" action="/jxdjadmin#ad-slots" class="stack">
                            <input type="hidden" name="admin_action" value="save_ad_platform_config">
                            <input type="hidden" name="admin_section" value="ad-slots">
                            <input type="hidden" name="ad_platform_config_id" value="<?= (int) ($platformConfig['id'] ?? 0) ?>">
                            <?= $csrfField() ?>
                            <strong><?= htmlspecialchars((string) (($platformConfig['provider_name'] ?? '') ?: ($adProviderLabels[(string) ($platformConfig['provider'] ?? '')] ?? '广告平台'))) ?></strong>
                            <div class="form-grid">
                                <label>平台编码<input name="provider" value="<?= htmlspecialchars((string) ($platformConfig['provider'] ?? '')) ?>"></label>
                                <label>显示名称<input name="provider_name" value="<?= htmlspecialchars((string) ($platformConfig['provider_name'] ?? '')) ?>"></label>
                                <label>应用
                                    <select name="app_key">
                                        <option value="default" <?= (string) ($platformConfig['app_key'] ?? 'default') === 'default' ? 'selected' : '' ?>>默认应用</option>
                                        <?php foreach ($apps as $app): ?>
                                            <?php $appKey = (string) ($app['app_key'] ?? 'default'); ?>
                                            <option value="<?= htmlspecialchars($appKey) ?>" <?= (string) ($platformConfig['app_key'] ?? 'default') === $appKey ? 'selected' : '' ?>><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars($appKey) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>状态
                                    <select name="status">
                                        <option value="active" <?= (string) ($platformConfig['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>启用</option>
                                        <option value="test" <?= (string) ($platformConfig['status'] ?? 'active') === 'test' ? 'selected' : '' ?>>测试</option>
                                        <option value="paused" <?= (string) ($platformConfig['status'] ?? 'active') === 'paused' ? 'selected' : '' ?>>停用</option>
                                    </select>
                                </label>
                                <label>平台AppID<input name="platform_app_id" value="<?= htmlspecialchars((string) ($platformConfig['platform_app_id'] ?? '')) ?>"></label>
                                <label>SDK Key<input name="sdk_key" value="<?= htmlspecialchars((string) ($platformConfig['sdk_key'] ?? '')) ?>"></label>
                                <label>账号ID<input name="account_id" value="<?= htmlspecialchars((string) ($platformConfig['account_id'] ?? '')) ?>"></label>
                                <label>媒体ID<input name="media_id" value="<?= htmlspecialchars((string) ($platformConfig['media_id'] ?? '')) ?>"></label>
                                <label>默认eCPM<input name="default_ecpm" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($platformConfig['default_ecpm'] ?? 0)) ?>"></label>
                                <label>收益分成%<input name="revenue_share_rate" type="number" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string) ($platformConfig['revenue_share_rate'] ?? 100)) ?>"></label>
                                <label>币种<input name="currency" value="<?= htmlspecialchars((string) ($platformConfig['currency'] ?? 'CNY')) ?>"></label>
                                <label>测试模式
                                    <select name="test_mode">
                                        <option value="0" <?= empty($platformConfig['test_mode']) ? 'selected' : '' ?>>关闭</option>
                                        <option value="1" <?= !empty($platformConfig['test_mode']) ? 'selected' : '' ?>>开启</option>
                                    </select>
                                </label>
                            </div>
                            <label>初始化参数 JSON<textarea name="init_params_text" rows="2"><?= htmlspecialchars(json_encode((array) ($platformConfig['init_params'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}') ?></textarea></label>
                            <label>隐私说明<input name="privacy_note" value="<?= htmlspecialchars((string) ($platformConfig['privacy_note'] ?? '')) ?>"></label>
                            <label>备注<input name="remark" value="<?= htmlspecialchars((string) ($platformConfig['remark'] ?? '')) ?>"></label>
                            <button class="btn ghost" type="submit">保存平台</button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="repair-grid">
        <form method="post" action="/jxdjadmin#ad-slots" class="row-card stack">
            <input type="hidden" name="admin_action" value="save_ad_delivery_rule">
            <input type="hidden" name="admin_section" value="ad-slots">
            <?= $csrfField() ?>
            <p><strong>新增广告分层策略</strong></p>
            <div class="form-grid">
                <label>策略名称<input name="name" placeholder="例如：未付费用户优先激励视频"></label>
                <label>策略编码<input name="code" placeholder="unpaid_reward_first，不填自动生成"></label>
                <label>应用
                    <select name="app_keys[]" multiple>
                        <option value="default" selected>默认应用</option>
                        <?php foreach ($apps as $app): ?>
                            <option value="<?= htmlspecialchars((string) ($app['app_key'] ?? 'default')) ?>"><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars((string) ($app['app_key'] ?? 'default')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>状态
                    <select name="status">
                        <?php foreach ($adRuleStatusLabels as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>广告位编码<input name="slot_codes" placeholder="多个用逗号隔开，留空不限"></label>
                <label>展示位置
                    <select name="positions[]" multiple>
                        <?php foreach ($adPositionLabels as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>广告类型
                    <select name="ad_types[]" multiple>
                        <?php foreach ($adTypeLabels as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>广告平台<input name="providers" placeholder="csj, ylh, kuaishou"></label>
                <label>用户标签<input name="user_tags" placeholder="新客, 高价值, 审核流量"></label>
                <label>会员状态
                    <select name="membership">
                        <?php foreach ($adMembershipLabels as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>付费阶段
                    <select name="pay_stage">
                        <?php foreach ($adPayStageLabels as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>优先级<input name="priority" type="number" value="100"></label>
                <label>奖励覆盖K币<input name="reward_coins_override" type="number" min="0" value="0"></label>
                <label>日上限覆盖<input name="daily_limit_override" type="number" min="0" value="0"></label>
                <label>频控覆盖秒<input name="frequency_seconds_override" type="number" min="0" value="0"></label>
                <label>每日展示上限<input name="max_impressions_per_day" type="number" min="0" value="0"></label>
            </div>
            <label>备注<input name="remark" placeholder="适用人群、投放目的或审核说明"></label>
            <p><button class="btn" type="submit">保存分层策略</button></p>
        </form>
        <div class="row-card stack">
            <p><strong>广告分层策略</strong></p>
            <?php if (empty($adDeliveryRules)): ?>
                <p class="muted">暂无分层策略。创建后 /?route=api-ad-slots 会按用户标签、会员状态和付费阶段返回匹配广告位。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach ($adDeliveryRules as $rule): ?>
                        <?php
                            $ruleAppKeys = array_values(array_map('strval', (array) ($rule['app_keys'] ?? [])));
                            $rulePositions = array_values(array_map('strval', (array) ($rule['positions'] ?? [])));
                            $ruleAdTypes = array_values(array_map('strval', (array) ($rule['ad_types'] ?? [])));
                        ?>
                        <form method="post" action="/jxdjadmin#ad-slots" class="stack">
                            <input type="hidden" name="admin_action" value="save_ad_delivery_rule">
                            <input type="hidden" name="admin_section" value="ad-slots">
                            <input type="hidden" name="ad_delivery_rule_id" value="<?= (int) ($rule['id'] ?? 0) ?>">
                            <?= $csrfField() ?>
                            <strong><?= htmlspecialchars((string) ($rule['name'] ?? '广告分层策略')) ?></strong>
                            <div class="form-grid">
                                <label>策略名称<input name="name" value="<?= htmlspecialchars((string) ($rule['name'] ?? '')) ?>"></label>
                                <label>策略编码<input name="code" value="<?= htmlspecialchars((string) ($rule['code'] ?? '')) ?>"></label>
                                <label>应用
                                    <select name="app_keys[]" multiple>
                                        <option value="default" <?= in_array('default', $ruleAppKeys, true) ? 'selected' : '' ?>>默认应用</option>
                                        <?php foreach ($apps as $app): ?>
                                            <?php $appKey = (string) ($app['app_key'] ?? 'default'); ?>
                                            <option value="<?= htmlspecialchars($appKey) ?>" <?= in_array($appKey, $ruleAppKeys, true) ? 'selected' : '' ?>><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars($appKey) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>状态
                                    <select name="status">
                                        <?php foreach ($adRuleStatusLabels as $value => $label): ?>
                                            <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($rule['status'] ?? 'active') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>广告位编码<input name="slot_codes" value="<?= htmlspecialchars(implode(',', (array) ($rule['slot_codes'] ?? []))) ?>"></label>
                                <label>展示位置
                                    <select name="positions[]" multiple>
                                        <?php foreach ($adPositionLabels as $value => $label): ?>
                                            <option value="<?= htmlspecialchars($value) ?>" <?= in_array($value, $rulePositions, true) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>广告类型
                                    <select name="ad_types[]" multiple>
                                        <?php foreach ($adTypeLabels as $value => $label): ?>
                                            <option value="<?= htmlspecialchars($value) ?>" <?= in_array($value, $ruleAdTypes, true) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>广告平台<input name="providers" value="<?= htmlspecialchars(implode(',', (array) ($rule['providers'] ?? []))) ?>"></label>
                                <label>用户标签<input name="user_tags" value="<?= htmlspecialchars(implode(',', (array) ($rule['user_tags'] ?? []))) ?>"></label>
                                <label>会员状态
                                    <select name="membership">
                                        <?php foreach ($adMembershipLabels as $value => $label): ?>
                                            <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($rule['membership'] ?? 'all') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>付费阶段
                                    <select name="pay_stage">
                                        <?php foreach ($adPayStageLabels as $value => $label): ?>
                                            <option value="<?= htmlspecialchars($value) ?>" <?= (string) ($rule['pay_stage'] ?? 'all') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>优先级<input name="priority" type="number" value="<?= (int) ($rule['priority'] ?? 100) ?>"></label>
                                <label>奖励覆盖K币<input name="reward_coins_override" type="number" min="0" value="<?= (int) ($rule['reward_coins_override'] ?? 0) ?>"></label>
                                <label>日上限覆盖<input name="daily_limit_override" type="number" min="0" value="<?= (int) ($rule['daily_limit_override'] ?? 0) ?>"></label>
                                <label>频控覆盖秒<input name="frequency_seconds_override" type="number" min="0" value="<?= (int) ($rule['frequency_seconds_override'] ?? 0) ?>"></label>
                                <label>每日展示上限<input name="max_impressions_per_day" type="number" min="0" value="<?= (int) ($rule['max_impressions_per_day'] ?? 0) ?>"></label>
                            </div>
                            <label>备注<input name="remark" value="<?= htmlspecialchars((string) ($rule['remark'] ?? '')) ?>"></label>
                            <button class="btn ghost" type="submit">保存策略</button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="order-table">
        <div class="row-card order-row-head">
            <span>广告位</span>
            <span>应用/位置</span>
            <span>平台/单元</span>
            <span>数据/奖励</span>
        </div>
        <?php if (empty($adSlots)): ?>
            <p class="muted">暂无广告位配置。创建后前端可调用 /?route=api-ad-slots&position=位置 获取启用广告位。</p>
        <?php endif; ?>
        <?php foreach ($adSlots as $slot): ?>
            <?php
                $slotId = (int) ($slot['id'] ?? 0);
                $slotEventCounts = (array) ($adEventCounts[$slotId] ?? ['impression' => 0, 'click' => 0, 'reward' => 0, 'reward_coins' => 0]);
                $slotMonetization = (array) ($adMonetizationRowsBySlotId[$slotId] ?? []);
                $slotStatus = (string) ($slot['status'] ?? 'active');
                $slotType = (string) ($slot['ad_type'] ?? 'banner');
                $slotPosition = (string) ($slot['position'] ?? 'home_banner');
                $slotAppKey = (string) ($slot['app_key'] ?? 'default');
            ?>
            <form method="post" action="/jxdjadmin#ad-slots" class="row-card order-row">
                <input type="hidden" name="admin_action" value="save_ad_slot">
                <input type="hidden" name="admin_section" value="ad-slots">
                <input type="hidden" name="ad_slot_id" value="<?= $slotId ?>">
                <?= $csrfField() ?>
                <span>
                    <strong><input name="name" value="<?= htmlspecialchars((string) ($slot['name'] ?? '广告位')) ?>" aria-label="广告位名称"></strong>
                    <em><input name="code" value="<?= htmlspecialchars((string) ($slot['code'] ?? '')) ?>" aria-label="广告位编码"></em>
                    <em><?= htmlspecialchars($adStatusLabels[$slotStatus] ?? $slotStatus) ?> · <?= htmlspecialchars((string) ($slot['updated_at'] ?? '')) ?></em>
                </span>
                <span>
                    <select name="app_key" aria-label="应用">
                        <option value="default" <?= $slotAppKey === 'default' ? 'selected' : '' ?>>默认应用</option>
                        <?php foreach ($apps as $app): ?>
                            <?php $appKey = (string) ($app['app_key'] ?? 'default'); ?>
                            <option value="<?= htmlspecialchars($appKey) ?>" <?= $slotAppKey === $appKey ? 'selected' : '' ?>><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars($appKey) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <em>
                        <select name="position" aria-label="展示位置">
                            <?php foreach ($adPositionLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= $slotPosition === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </em>
                    <em>
                        <select name="ad_type" aria-label="广告类型">
                            <?php foreach ($adTypeLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= $slotType === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </em>
                    <em>
                        <select name="status" aria-label="广告位状态">
                            <?php foreach ($adStatusLabels as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= $slotStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </em>
                </span>
                <span>
                    <input name="provider" value="<?= htmlspecialchars((string) ($slot['provider'] ?? '')) ?>" placeholder="广告平台" aria-label="广告平台">
                    <em><input name="unit_id" value="<?= htmlspecialchars((string) ($slot['unit_id'] ?? '')) ?>" placeholder="广告单元ID" aria-label="广告单元ID"></em>
                    <em>预估 eCPM <input name="estimate_ecpm" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($slot['estimate_ecpm'] ?? 0)) ?>" aria-label="预估eCPM"></em>
                    <em>分成 <input name="revenue_share_rate" type="number" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string) ($slot['revenue_share_rate'] ?? 100)) ?>" aria-label="收益分成">%</em>
                    <em><input name="title" value="<?= htmlspecialchars((string) ($slot['title'] ?? '')) ?>" placeholder="标题" aria-label="标题"></em>
                    <em><input name="image" value="<?= htmlspecialchars((string) ($slot['image'] ?? '')) ?>" placeholder="图片" aria-label="图片"></em>
                    <em><input name="link" value="<?= htmlspecialchars((string) ($slot['link'] ?? '')) ?>" placeholder="跳转链接" aria-label="跳转链接"></em>
                </span>
                <span>
                    请求 <?= number_format((int) ($slotMonetization['requests'] ?? 0)) ?> · 填充 <?= number_format((int) ($slotMonetization['fills'] ?? 0)) ?> · 曝光 <?= number_format((int) ($slotMonetization['impressions'] ?? ($slotEventCounts['impression'] ?? 0))) ?>
                    <em>点击 <?= number_format((int) ($slotMonetization['clicks'] ?? ($slotEventCounts['click'] ?? 0))) ?> · CTR <?= ($slotMonetization['ctr'] ?? null) === null ? '-' : number_format((float) $slotMonetization['ctr'], 2) . '%' ?> · 填充 <?= ($slotMonetization['fill_rate'] ?? null) === null ? '-' : number_format((float) $slotMonetization['fill_rate'], 2) . '%' ?></em>
                    <em>收益 <?= htmlspecialchars($money((float) ($slotMonetization['revenue'] ?? 0))) ?> · eCPM <?= number_format((float) ($slotMonetization['ecpm'] ?? 0), 2) ?> · 失败 <?= number_format((int) ($slotMonetization['fails'] ?? 0)) ?></em>
                    <em>激励 <?= number_format((int) ($slotMonetization['rewards'] ?? ($slotEventCounts['reward'] ?? 0))) ?> 次 · 发放 <?= number_format((int) ($slotMonetization['reward_coins'] ?? ($slotEventCounts['reward_coins'] ?? 0))) ?> K币</em>
                    <em>奖励 <input name="reward_coins" type="number" min="0" value="<?= (int) ($slot['reward_coins'] ?? 0) ?>" aria-label="激励K币"> · 日上限 <input name="daily_limit" type="number" min="0" value="<?= (int) ($slot['daily_limit'] ?? 0) ?>" aria-label="每日上限"></em>
                    <em>频控 <input name="frequency_seconds" type="number" min="0" value="<?= (int) ($slot['frequency_seconds'] ?? 0) ?>" aria-label="频控秒数"> 秒 · 排序 <input name="sort" type="number" value="<?= (int) ($slot['sort'] ?? 100) ?>" aria-label="排序"></em>
                    <em><input name="remark" value="<?= htmlspecialchars((string) ($slot['remark'] ?? '')) ?>" placeholder="备注" aria-label="备注"></em>
                    <button class="btn ghost" type="submit">保存</button>
                </span>
            </form>
        <?php endforeach; ?>
    </div>

    <div class="repair-grid">
        <div class="order-info-card">
            <h4>按应用收益</h4>
            <?php if (empty($adMonetizationDashboard['app_rows'])): ?>
                <p class="muted">暂无广告收益数据。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach (array_slice((array) $adMonetizationDashboard['app_rows'], 0, 8) as $row): ?>
                        <div>
                            <strong><?= htmlspecialchars((string) ($row['label'] ?? 'default')) ?></strong>
                            <span class="pill blue"><?= htmlspecialchars($money((float) ($row['revenue'] ?? 0))) ?></span>
                            <em>曝光 <?= number_format((int) ($row['impressions'] ?? 0)) ?> · eCPM <?= number_format((float) ($row['ecpm'] ?? 0), 2) ?> · CTR <?= ($row['ctr'] ?? null) === null ? '-' : number_format((float) $row['ctr'], 2) . '%' ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="order-info-card">
            <h4>按平台收益</h4>
            <?php if (empty($adMonetizationDashboard['provider_rows'])): ?>
                <p class="muted">暂无广告平台数据。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach (array_slice((array) $adMonetizationDashboard['provider_rows'], 0, 8) as $row): ?>
                        <div>
                            <strong><?= htmlspecialchars((string) ($row['label'] ?? '未标记平台')) ?></strong>
                            <span class="pill green"><?= htmlspecialchars($money((float) ($row['revenue'] ?? 0))) ?></span>
                            <em>请求 <?= number_format((int) ($row['requests'] ?? 0)) ?> · 填充 <?= ($row['fill_rate'] ?? null) === null ? '-' : number_format((float) $row['fill_rate'], 2) . '%' ?> · 失败 <?= number_format((int) ($row['fails'] ?? 0)) ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="order-info-card">
            <h4>瀑布流推荐顺序</h4>
            <?php if (empty($adWaterfallRows)): ?>
                <p class="muted">暂无可推荐广告位。配置广告位并上报广告事件后会自动生成排序。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach (array_slice($adWaterfallRows, 0, 8) as $index => $slot): ?>
                        <?php
                            $waterfall = (array) ($slot['waterfall'] ?? []);
                            $metrics = (array) ($waterfall['metrics'] ?? []);
                        ?>
                        <div>
                            <strong>#<?= $index + 1 ?> <?= htmlspecialchars((string) ($slot['name'] ?? '广告位')) ?></strong>
                            <span class="pill blue">评分 <?= number_format((float) ($waterfall['score'] ?? 0), 2) ?></span>
                            <em><?= htmlspecialchars((string) ($slot['provider'] ?? '')) ?> · <?= htmlspecialchars((string) ($slot['position'] ?? '')) ?> · eCPM <?= number_format((float) ($metrics['ecpm'] ?? $slot['estimate_ecpm'] ?? 0), 2) ?></em>
                            <em>请求 <?= number_format((int) ($metrics['requests'] ?? 0)) ?> · 填充 <?= number_format((float) ($metrics['fill_rate'] ?? 0), 2) ?>% · 失败 <?= number_format((float) ($metrics['failure_rate'] ?? 0), 2) ?>%</em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row-card stack">
        <p><strong>接口口径</strong></p>
        <p class="muted">前端按 /?route=api-ad-platform-configs&amp;app_key=default 拉取 SDK 平台参数，按 /?route=api-ad-delivery-rules&amp;app_key=default 查看启用策略，按 /?route=api-ad-slots&amp;app_key=default&amp;position=home_banner&amp;user_tags=新客 拉取已按分层策略过滤、瀑布流评分排序的广告位；返回的 delivery_rule 表示命中人群策略，waterfall 表示自动择优评分和样本指标。请求、填充、曝光、点击、激励完成和失败后调用 /?route=api-ad-event&amp;code=广告位编码&amp;event=request|fill|impression|click|reward|fail，可带 revenue/ecpm/error_code/user_tags。激励奖励受广告位和命中策略的每日上限、展示上限与频控限制。</p>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'coupon-code' ? 'is-active' : '' ?>" id="admin-section-coupon-code" data-admin-section="coupon-code" data-admin-primary="operation">
    <?php
        $redeemSuccessLogs = array_values(array_filter($redeemCodeLogs, static fn (array $log): bool => (string) ($log['status'] ?? '') === 'success'));
        $redeemFailedLogs = array_values(array_filter($redeemCodeLogs, static fn (array $log): bool => (string) ($log['status'] ?? '') === 'failed'));
        $redeemUsageByCodeId = [];
        foreach ($redeemSuccessLogs as $log) {
            $key = (int) ($log['code_id'] ?? 0);
            $redeemUsageByCodeId[$key] = ($redeemUsageByCodeId[$key] ?? 0) + 1;
        }
        $redeemActiveCount = count(array_filter($redeemCodes, static fn (array $item): bool => (string) ($item['status'] ?? 'active') === 'active'));
        $redeemCoinTotal = array_sum(array_map(static fn (array $log): int => (int) ($log['coin_amount'] ?? 0) + (int) ($log['bonus_coin_amount'] ?? 0), $redeemSuccessLogs));
        $redeemVipTotal = array_sum(array_map(static fn (array $log): int => (int) ($log['vip_days'] ?? 0), $redeemSuccessLogs));
        $redeemBatchStats = [];
        foreach ($redeemCodes as $codeItem) {
            $batchNo = (string) ($codeItem['batch_no'] ?? '');
            if ($batchNo === '') {
                continue;
            }
            $redeemBatchStats[$batchNo] ??= ['count' => 0, 'active' => 0, 'used' => 0, 'name' => (string) ($codeItem['name'] ?? ''), 'created_at' => (string) ($codeItem['created_at'] ?? ''), 'source' => (string) ($codeItem['source'] ?? 'manual'), 'import_file_name' => (string) ($codeItem['import_file_name'] ?? '')];
            $redeemBatchStats[$batchNo]['count']++;
            if ((string) ($codeItem['status'] ?? 'active') === 'active') {
                $redeemBatchStats[$batchNo]['active']++;
            }
            $redeemBatchStats[$batchNo]['used'] += (int) ($redeemUsageByCodeId[(int) ($codeItem['id'] ?? 0)] ?? 0);
        }
    ?>
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">运营权益</span>
            <h2>优惠券/兑换码</h2>
        </div>
        <span class="muted">启用 <?= number_format($redeemActiveCount) ?> 个 · 成功兑换 <?= number_format(count($redeemSuccessLogs)) ?> 次</span>
    </div>
    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('revenue') ?></span>
            <small>兑换码</small>
            <strong><?= number_format(count($redeemCodes)) ?> 个</strong>
            <em>启用 <?= number_format($redeemActiveCount) ?> 个</em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('stats') ?></span>
            <small>成功兑换</small>
            <strong><?= number_format(count($redeemSuccessLogs)) ?> 次</strong>
            <em>失败 <?= number_format(count($redeemFailedLogs)) ?> 次</em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('payment') ?></span>
            <small>K币发放</small>
            <strong><?= number_format($redeemCoinTotal) ?></strong>
            <em>含赠币奖励</em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('user') ?></span>
            <small>VIP 发放</small>
            <strong><?= number_format($redeemVipTotal) ?> 天</strong>
            <em>从用户当前到期时间顺延</em>
        </div>
    </div>

    <form method="post" action="/jxdjadmin#coupon-code" class="row-card stack" enctype="multipart/form-data">
        <input type="hidden" name="admin_action" value="save_redeem_code">
        <input type="hidden" name="admin_section" value="coupon-code">
        <?= $csrfField() ?>
        <p><strong>新建兑换码</strong></p>
        <div class="form-grid">
            <label>兑换码<input name="code" placeholder="例如 JULYVIP88，不填自动生成"></label>
            <label>名称<input name="name" placeholder="例如 七月拉新福利"></label>
            <label>奖励类型
                <select name="reward_type">
                    <option value="coin">K币</option>
                    <option value="vip">VIP</option>
                    <option value="mixed">K币 + VIP</option>
                </select>
            </label>
            <label>K币<input name="coin_amount" type="number" min="0" value="0"></label>
            <label>赠币<input name="bonus_coin_amount" type="number" min="0" value="300"></label>
            <label>VIP 天数<input name="vip_days" type="number" min="0" value="0"></label>
            <label>总次数<input name="total_limit" type="number" min="0" value="0"></label>
            <label>单用户次数<input name="per_user_limit" type="number" min="1" value="1"></label>
            <label>推广码<input name="promotion_code" placeholder="仅限指定推广入口"></label>
            <label>代理ID<input name="agent_id" type="number" min="0" value="0"></label>
            <label>渠道ID<input name="channel_id" placeholder="仅限指定渠道"></label>
            <label>指定用户ID<input name="allowed_user_ids" placeholder="多个用逗号分隔"></label>
            <label>应用
                <select name="app_key">
                    <option value="all">全部应用</option>
                    <?php foreach ($apps as $app): ?>
                        <option value="<?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?>"><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>状态
                <select name="status">
                    <option value="active">启用</option>
                    <option value="paused">停用</option>
                </select>
            </label>
            <label>开始时间<input name="started_at" type="datetime-local"></label>
            <label>结束时间<input name="ended_at" type="datetime-local"></label>
            <label>备注<input name="remark" placeholder="投放渠道、活动批次或客服备注"></label>
        </div>
        <p><button class="btn" type="submit">保存兑换码</button></p>
    </form>

    <form method="post" action="/jxdjadmin#coupon-code" class="row-card stack">
        <input type="hidden" name="admin_action" value="generate_redeem_code_batch">
        <input type="hidden" name="admin_section" value="coupon-code">
        <?= $csrfField() ?>
        <p><strong>批量生成一次性码包</strong></p>
        <div class="form-grid">
            <label>码包名称<input name="batch_name" placeholder="例如 七月代理福利包"></label>
            <label>生成数量<input name="batch_count" type="number" min="1" max="1000" value="50"></label>
            <label>码前缀<input name="batch_prefix" value="RC" maxlength="10"></label>
            <label>奖励类型
                <select name="reward_type">
                    <option value="coin">K币</option>
                    <option value="vip">VIP</option>
                    <option value="mixed">K币 + VIP</option>
                </select>
            </label>
            <label>K币<input name="coin_amount" type="number" min="0" value="0"></label>
            <label>赠币<input name="bonus_coin_amount" type="number" min="0" value="300"></label>
            <label>VIP 天数<input name="vip_days" type="number" min="0" value="0"></label>
            <label>推广码<input name="batch_promotion_code" placeholder="整批仅限指定推广入口"></label>
            <label>代理ID<input name="batch_agent_id" type="number" min="0" value="0"></label>
            <label>渠道ID<input name="batch_channel_id" placeholder="整批仅限指定渠道"></label>
            <label>指定用户ID<input name="batch_allowed_user_ids" placeholder="多个用逗号分隔"></label>
            <label>应用
                <select name="app_key">
                    <option value="all">全部应用</option>
                    <?php foreach ($apps as $app): ?>
                        <option value="<?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?>"><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>开始时间<input name="started_at" type="datetime-local"></label>
            <label>结束时间<input name="ended_at" type="datetime-local"></label>
            <label>备注<input name="remark" placeholder="渠道、代理、客服批次"></label>
        </div>
        <p class="muted">每个码自动设置为一次性：总次数 1、单用户 1。生成后可在下方按批次导出 CSV。</p>
        <p><button class="btn primary" type="submit">生成码包</button></p>
    </form>

    <form method="post" action="/jxdjadmin#coupon-code" class="row-card stack" enctype="multipart/form-data">
        <input type="hidden" name="admin_action" value="import_redeem_code_pool">
        <input type="hidden" name="admin_section" value="coupon-code">
        <?= $csrfField() ?>
        <p><strong>导入外部码池</strong></p>
        <div class="form-grid">
            <label>码包名称<input name="import_batch_name" placeholder="例如 渠道外部码池"></label>
            <label>自定义批次号<input name="import_batch_no" placeholder="留空自动生成"></label>
            <label>奖励类型
                <select name="reward_type">
                    <option value="coin">K币</option>
                    <option value="vip">VIP</option>
                    <option value="mixed">K币 + VIP</option>
                </select>
            </label>
            <label>K币<input name="coin_amount" type="number" min="0" value="0"></label>
            <label>赠币<input name="bonus_coin_amount" type="number" min="0" value="300"></label>
            <label>VIP 天数<input name="vip_days" type="number" min="0" value="0"></label>
            <label>推广码<input name="batch_promotion_code" placeholder="整批仅限指定推广入口"></label>
            <label>代理ID<input name="batch_agent_id" type="number" min="0" value="0"></label>
            <label>渠道ID<input name="batch_channel_id" placeholder="整批仅限指定渠道"></label>
            <label>指定用户ID<input name="batch_allowed_user_ids" placeholder="多个用逗号分隔"></label>
            <label>应用
                <select name="app_key">
                    <option value="all">全部应用</option>
                    <?php foreach ($apps as $app): ?>
                        <option value="<?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?>"><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars((string) ($app['app_key'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>开始时间<input name="started_at" type="datetime-local"></label>
            <label>结束时间<input name="ended_at" type="datetime-local"></label>
            <label>备注<input name="remark" placeholder="外部码来源、渠道或活动说明"></label>
        </div>
        <label>上传码池<input name="import_codes_file" type="file" accept=".txt,.csv"></label>
        <label>外部兑换码<textarea name="import_codes" rows="5" placeholder="一行一个码，也支持逗号、空格分隔；若填写 CODE,名称，会把第二列作为单码名称"></textarea></label>
        <p class="muted">可上传 TXT/CSV，也可直接粘贴；重复码会自动跳过；导入成功后可在码包批次中导出 CSV，文件会包含领取链接。</p>
        <p><button class="btn primary" type="submit">导入码池</button></p>
    </form>

    <?php if (!empty($redeemBatchStats)): ?>
        <div class="row-card stack">
            <p><strong>码包批次</strong></p>
            <div class="order-table">
                <div class="row-card order-row-head">
                    <span>批次</span>
                    <span>数量</span>
                    <span>使用</span>
                    <span>操作</span>
                </div>
                <?php foreach ($redeemBatchStats as $batchNo => $batch): ?>
                    <?php $batchClaimLink = '/?route=api-redeem-code-batch&batch_no=' . rawurlencode((string) $batchNo); ?>
                    <div class="row-card order-row">
                        <span>
                            <strong><?= htmlspecialchars($batchNo) ?></strong>
                            <em><?= htmlspecialchars((string) ($batch['name'] ?? '')) ?> · <?= ((string) ($batch['source'] ?? '') === 'external_import') ? '外部导入' : (((string) ($batch['source'] ?? '') === 'batch_generated') ? '系统生成' : '手动创建') ?> · <?= htmlspecialchars((string) ($batch['created_at'] ?? '')) ?></em>
                            <?php if (!empty($batch['import_file_name'])): ?><em>文件 <?= htmlspecialchars((string) ($batch['import_file_name'] ?? '')) ?></em><?php endif; ?>
                        </span>
                        <span>总数 <?= number_format((int) ($batch['count'] ?? 0)) ?><em>启用 <?= number_format((int) ($batch['active'] ?? 0)) ?></em></span>
                        <span>已兑 <?= number_format((int) ($batch['used'] ?? 0)) ?><em>未兑 <?= number_format(max(0, (int) ($batch['count'] ?? 0) - (int) ($batch['used'] ?? 0))) ?></em></span>
                        <span>批次领取<em><input value="<?= htmlspecialchars($batchClaimLink) ?>" readonly aria-label="批次领取链接"></em></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <form method="post" action="/jxdjadmin" class="order-filter-bar">
                <input type="hidden" name="admin_action" value="export_redeem_code_batch_csv">
                <input type="hidden" name="admin_section" value="coupon-code">
                <?= $csrfField() ?>
                <label>批次号
                    <select name="batch_no">
                        <?php foreach (array_keys($redeemBatchStats) as $batchNo): ?>
                            <option value="<?= htmlspecialchars($batchNo) ?>"><?= htmlspecialchars($batchNo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="order-filter-actions"><button class="btn ghost" type="submit">导出CSV</button></div>
            </form>
        </div>
    <?php endif; ?>

    <div class="order-list">
        <?php if (empty($redeemCodes)): ?>
            <div class="empty">暂无兑换码。</div>
        <?php endif; ?>
        <?php foreach ($redeemCodes as $item): ?>
            <?php
                $codeId = (int) ($item['id'] ?? 0);
                $redeemCodeText = (string) ($item['code'] ?? '');
                $usedCount = (int) ($redeemUsageByCodeId[$codeId] ?? 0);
                $totalLimit = (int) ($item['total_limit'] ?? 0);
                $status = (string) ($item['status'] ?? 'active');
            ?>
            <form method="post" action="/jxdjadmin#coupon-code" class="order-item">
                <input type="hidden" name="admin_action" value="save_redeem_code">
                <input type="hidden" name="admin_section" value="coupon-code">
                <input type="hidden" name="redeem_code_id" value="<?= $codeId ?>">
                <?= $csrfField() ?>
                <span>
                    <strong><?= htmlspecialchars((string) ($item['name'] ?? '兑换码')) ?></strong>
                    <span class="pill <?= $status === 'active' ? 'green' : 'orange' ?>"><?= $status === 'active' ? '启用' : '停用' ?></span>
                    <?php if ((string) ($item['source'] ?? '') === 'external_import'): ?><span class="pill blue">外部导入</span><?php endif; ?>
                    <em>编码 <input name="code" value="<?= htmlspecialchars($redeemCodeText) ?>" aria-label="兑换码编码"></em>
                    <em>名称 <input name="name" value="<?= htmlspecialchars((string) ($item['name'] ?? '')) ?>" aria-label="兑换码名称"></em>
                    <em>领取 <input value="<?= htmlspecialchars('/?route=api-redeem-code&code=' . rawurlencode($redeemCodeText)) ?>" readonly aria-label="领取链接"></em>
                </span>
                <span>
                    <em>
                        <select name="reward_type" aria-label="奖励类型">
                            <option value="coin" <?= (string) ($item['reward_type'] ?? '') === 'coin' ? 'selected' : '' ?>>K币</option>
                            <option value="vip" <?= (string) ($item['reward_type'] ?? '') === 'vip' ? 'selected' : '' ?>>VIP</option>
                            <option value="mixed" <?= (string) ($item['reward_type'] ?? '') === 'mixed' ? 'selected' : '' ?>>K币 + VIP</option>
                        </select>
                    </em>
                    <em>K币 <input name="coin_amount" type="number" min="0" value="<?= (int) ($item['coin_amount'] ?? 0) ?>" aria-label="K币"></em>
                    <em>赠币 <input name="bonus_coin_amount" type="number" min="0" value="<?= (int) ($item['bonus_coin_amount'] ?? 0) ?>" aria-label="赠币"></em>
                    <em>VIP <input name="vip_days" type="number" min="0" value="<?= (int) ($item['vip_days'] ?? 0) ?>" aria-label="VIP天数"> 天</em>
                    <em>总次数 <input name="total_limit" type="number" min="0" value="<?= $totalLimit ?>" aria-label="总次数"></em>
                    <em>单用户 <input name="per_user_limit" type="number" min="1" value="<?= (int) ($item['per_user_limit'] ?? 1) ?>" aria-label="单用户次数"></em>
                    <em>推广 <input name="promotion_code" value="<?= htmlspecialchars((string) ($item['promotion_code'] ?? '')) ?>" placeholder="推广码" aria-label="推广码"></em>
                    <em>代理 <input name="agent_id" type="number" min="0" value="<?= (int) ($item['agent_id'] ?? 0) ?>" aria-label="代理ID"></em>
                    <em>渠道 <input name="channel_id" value="<?= htmlspecialchars((string) ($item['channel_id'] ?? '')) ?>" placeholder="渠道ID" aria-label="渠道ID"></em>
                    <em>用户 <input name="allowed_user_ids" value="<?= htmlspecialchars(implode(',', array_map('strval', (array) ($item['allowed_user_ids'] ?? [])))) ?>" placeholder="指定用户ID" aria-label="指定用户ID"></em>
                </span>
                <span>
                    已兑 <?= number_format($usedCount) ?><?= $totalLimit > 0 ? ' / ' . number_format($totalLimit) : '' ?> 次
                    <em>应用
                        <select name="app_key" aria-label="适用应用">
                            <option value="all" <?= (string) ($item['app_key'] ?? 'all') === 'all' ? 'selected' : '' ?>>全部应用</option>
                            <?php foreach ($apps as $app): ?>
                                <?php $appKey = (string) ($app['app_key'] ?? ''); ?>
                                <option value="<?= htmlspecialchars($appKey) ?>" <?= (string) ($item['app_key'] ?? '') === $appKey ? 'selected' : '' ?>><?= htmlspecialchars((string) ($app['name'] ?? '应用')) ?> · <?= htmlspecialchars($appKey) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </em>
                    <em>
                        <select name="status" aria-label="状态">
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>启用</option>
                            <option value="paused" <?= $status === 'paused' ? 'selected' : '' ?>>停用</option>
                        </select>
                    </em>
                    <em>开始 <input name="started_at" type="datetime-local" value="<?= htmlspecialchars(str_replace(' ', 'T', (string) ($item['started_at'] ?? ''))) ?>" aria-label="开始时间"></em>
                    <em>结束 <input name="ended_at" type="datetime-local" value="<?= htmlspecialchars(str_replace(' ', 'T', (string) ($item['ended_at'] ?? ''))) ?>" aria-label="结束时间"></em>
                    <em><input name="remark" value="<?= htmlspecialchars((string) ($item['remark'] ?? '')) ?>" placeholder="备注" aria-label="备注"></em>
                    <button class="btn ghost" type="submit">保存</button>
                </span>
            </form>
        <?php endforeach; ?>
    </div>

    <div class="repair-grid">
        <div class="order-info-card">
            <h4>最近兑换成功</h4>
            <?php if (empty($redeemSuccessLogs)): ?>
                <p class="muted">暂无成功兑换记录。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach (array_slice($redeemSuccessLogs, 0, 8) as $log): ?>
                        <div>
                            <strong><?= htmlspecialchars((string) ($log['code'] ?? '')) ?></strong>
                            <span class="pill green">用户 #<?= (int) ($log['user_id'] ?? 0) ?></span>
                            <em><?= htmlspecialchars((string) ($log['message'] ?? '兑换成功')) ?> · K币 <?= number_format((int) ($log['coin_amount'] ?? 0) + (int) ($log['bonus_coin_amount'] ?? 0)) ?> · VIP <?= number_format((int) ($log['vip_days'] ?? 0)) ?> 天</em>
                            <em><?= htmlspecialchars((string) ($log['created_at'] ?? '')) ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="order-info-card">
            <h4>最近兑换失败</h4>
            <?php if (empty($redeemFailedLogs)): ?>
                <p class="muted">暂无失败兑换记录。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach (array_slice($redeemFailedLogs, 0, 8) as $log): ?>
                        <div>
                            <strong><?= htmlspecialchars((string) ($log['code'] ?? '')) ?></strong>
                            <span class="pill orange">用户 #<?= (int) ($log['user_id'] ?? 0) ?></span>
                            <em><?= htmlspecialchars((string) ($log['message'] ?? '兑换失败')) ?></em>
                            <em><?= htmlspecialchars((string) ($log['created_at'] ?? '')) ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="order-info-card">
            <h4>接口口径</h4>
            <p class="muted">客户端调用 /?route=api-redeem-code，POST code=兑换码；也可调用 /?route=api-redeem-code-batch，POST batch_no=批次号，由系统自动分配该批次当前用户可领取的兑换码。请求可带 app_key、promotion_code、agent_id、channel_id。系统会校验启停、有效期、应用、推广入口、代理、渠道、指定用户、总次数和单用户次数；成功后发放 K币/赠币/VIP，写入 K币流水和兑换日志，返回 reward、实际 code 与最新 user。</p>
        </div>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'agent-accounts' ? 'is-active' : '' ?>" id="admin-section-agent-accounts" data-admin-section="agent-accounts" data-admin-primary="operation">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">投放组织</span>
            <h2>投放账号</h2>
        </div>
        <span class="muted">商务 <?= number_format((int) ($agentSummary['business_count'] ?? 0)) ?> · 组长 <?= number_format((int) ($agentSummary['leader_count'] ?? 0)) ?> · 代理 <?= number_format((int) ($agentSummary['agent_count'] ?? 0)) ?></span>
    </div>
    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('account') ?></span>
            <small>商务</small>
            <strong><?= number_format((int) ($agentSummary['business_count'] ?? 0)) ?></strong>
            <em>一级投放归属</em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('user') ?></span>
            <small>组长</small>
            <strong><?= number_format((int) ($agentSummary['leader_count'] ?? 0)) ?></strong>
            <em>承接代理团队</em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('profit') ?></span>
            <small>代理</small>
            <strong><?= number_format((int) ($agentSummary['agent_count'] ?? 0)) ?></strong>
            <em>推广链接归因主体</em>
        </div>
    </div>

    <form method="post" action="/jxdjadmin" class="row-card stack">
        <input type="hidden" name="admin_action" value="save_agent">
        <input type="hidden" name="admin_section" value="agent-accounts">
        <?= $csrfField() ?>
        <p><strong>新增投放账号</strong></p>
        <div class="form-grid">
            <label>账号角色
                <select name="role">
                    <option value="business">商务</option>
                    <option value="leader">组长</option>
                    <option value="agent">代理</option>
                </select>
            </label>
            <label>上级账号
                <select name="parent_id">
                    <option value="0">商务无上级</option>
                    <?php foreach ($businessOptions as $agent): ?>
                        <option value="<?= (int) ($agent['id'] ?? 0) ?>">商务：<?= htmlspecialchars((string) ($agent['name'] ?? '商务')) ?></option>
                    <?php endforeach; ?>
                    <?php foreach ($leaderOptions as $agent): ?>
                        <?php $leaderId = (int) ($agent['id'] ?? 0); ?>
                        <option value="<?= $leaderId ?>">组长：<?= htmlspecialchars($agentPathById[$leaderId] ?? (string) ($agent['name'] ?? '组长')) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>账号名称<input name="name" placeholder="例如：华东商务 / A组组长 / 张三代理"></label>
            <label>状态
                <select name="status">
                    <option value="active">启用</option>
                    <option value="paused">暂停</option>
                </select>
            </label>
            <label>备注<input name="remark" placeholder="负责平台、区域或结算说明"></label>
        </div>
        <p><button class="btn" type="submit">保存投放账号</button></p>
    </form>

    <div class="order-table">
        <div class="row-card order-row-head">
            <span>账号层级</span>
            <span>角色/状态</span>
            <span>链接/加桌</span>
            <span>消耗/回收</span>
        </div>
        <?php foreach ($agentRows as $row): ?>
            <?php $agent = (array) ($row['agent'] ?? []); ?>
            <form method="post" action="/jxdjadmin" class="row-card order-row">
                <input type="hidden" name="admin_action" value="save_agent">
                <input type="hidden" name="admin_section" value="agent-accounts">
                <input type="hidden" name="agent_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                <input type="hidden" name="role" value="<?= htmlspecialchars((string) ($row['role'] ?? 'agent')) ?>">
                <input type="hidden" name="parent_id" value="<?= (int) ($row['parent_id'] ?? 0) ?>">
                <?= $csrfField() ?>
                <span>
                    <strong><input name="name" value="<?= htmlspecialchars((string) ($row['name'] ?? '投放账号')) ?>" aria-label="账号名称"></strong>
                    <em><?= htmlspecialchars((string) ($row['path'] ?? $row['name'] ?? '投放账号')) ?></em>
                    <em><?= htmlspecialchars((string) ($agent['remark'] ?? '')) ?></em>
                </span>
                <span>
                    <?= htmlspecialchars($agentRoleLabels[(string) ($row['role'] ?? 'agent')] ?? '代理') ?>
                    <em>
                        <select name="status" aria-label="账号状态">
                            <option value="active" <?= (string) ($row['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>启用</option>
                            <option value="paused" <?= (string) ($row['status'] ?? 'active') === 'paused' ? 'selected' : '' ?>>暂停</option>
                        </select>
                    </em>
                </span>
                <span>
                    链接 <?= number_format((int) ($row['link_count'] ?? 0)) ?> · 加桌 <?= number_format((int) ($row['add_desktop'] ?? 0)) ?>
                    <em>访问 <?= number_format((int) ($row['visits'] ?? 0)) ?> · 付费 <?= number_format((int) ($row['paid_orders'] ?? 0)) ?></em>
                </span>
                <span>
                    消耗 <?= htmlspecialchars($money((float) ($row['cost'] ?? 0))) ?> · 回收 <?= htmlspecialchars($money((float) ($row['revenue'] ?? 0))) ?>
                    <em>回本 <?= ($row['recovery_rate'] ?? null) === null ? '-' : number_format((float) $row['recovery_rate'], 2) . '%' ?> · 下单成本 <?= ($row['order_cost'] ?? null) === null ? '-' : htmlspecialchars($money((float) $row['order_cost'])) ?></em>
                    <em><input name="remark" value="<?= htmlspecialchars((string) ($agent['remark'] ?? '')) ?>" placeholder="备注" aria-label="备注"></em>
                    <button class="btn ghost" type="submit">保存</button>
                </span>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'callback-config' ? 'is-active' : '' ?>" id="admin-section-callback-config" data-admin-section="callback-config" data-admin-primary="operation">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">平台回传</span>
            <h2>回传配置</h2>
        </div>
        <span class="muted">待处理 <?= number_format($pendingCallbackCount) ?> 条 · 已记录 <?= number_format(count($callbackLogs)) ?> 条</span>
    </div>
    <form method="post" action="/jxdjadmin" class="row-card stack">
        <input type="hidden" name="admin_action" value="save_callback_config">
        <input type="hidden" name="admin_section" value="callback-config">
        <?= $csrfField() ?>
        <input type="hidden" name="template_key" value="<?= htmlspecialchars((string) ($callbackConfig['template_key'] ?? 'custom')) ?>">
        <input type="hidden" name="template_name" value="<?= htmlspecialchars((string) ($callbackConfig['template_name'] ?? '自定义模板')) ?>">
        <div class="form-grid">
            <label><span><input type="checkbox" name="enabled" value="1" <?= !empty($callbackConfig['enabled']) ? 'checked' : '' ?>> 启用回传队列</span></label>
            <label>平台名称<input name="platform" value="<?= htmlspecialchars((string) ($callbackConfig['platform'] ?? '巨量引擎')) ?>" placeholder="巨量引擎 / 快手 / 广点通"></label>
            <label>回传地址<input name="endpoint" value="<?= htmlspecialchars((string) ($callbackConfig['endpoint'] ?? '')) ?>" placeholder="https://ad-platform.example/callback"></label>
            <label>密钥<input name="secret" value="<?= htmlspecialchars((string) ($callbackConfig['secret'] ?? '')) ?>" placeholder="签名密钥，后续真实发送时使用"></label>
            <label>当前模板<input value="<?= htmlspecialchars((string) (($callbackConfig['template_name'] ?? '') ?: '自定义模板')) ?>" disabled></label>
            <label>加桌事件名<input name="add_desktop_events" value="<?= htmlspecialchars(implode(',', (array) ($callbackConfig['add_desktop_events'] ?? ['active']))) ?>" placeholder="active,add_to_desktop"></label>
            <label>支付事件名<input name="paid_events" value="<?= htmlspecialchars(implode(',', (array) ($callbackConfig['paid_events'] ?? ['pay']))) ?>" placeholder="pay,purchase"></label>
            <label><span><input type="checkbox" name="fallback_time_match" value="1" <?= !empty($callbackConfig['fallback_time_match']) ? 'checked' : '' ?>> 启用时间匹配兜底</span></label>
        </div>
        <p><strong>真实平台鉴权</strong></p>
        <div class="form-grid">
            <label>鉴权方式
                <select name="auth_mode">
                    <?php foreach ($callbackAuthModeLabels as $modeKey => $modeLabel): ?>
                        <option value="<?= htmlspecialchars($modeKey) ?>" <?= $callbackAuthMode === $modeKey ? 'selected' : '' ?>><?= htmlspecialchars($modeLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>签名算法
                <select name="auth_algorithm">
                    <?php foreach (['sha256' => 'HMAC-SHA256', 'sha1' => 'HMAC-SHA1', 'md5' => 'HMAC-MD5'] as $algorithmKey => $algorithmLabel): ?>
                        <option value="<?= htmlspecialchars($algorithmKey) ?>" <?= (string) ($callbackAuthConfig['algorithm'] ?? 'sha256') === $algorithmKey ? 'selected' : '' ?>><?= htmlspecialchars($algorithmLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>签名密钥<input name="auth_secret" value="<?= htmlspecialchars((string) ($callbackAuthConfig['secret'] ?? ($callbackConfig['secret'] ?? ''))) ?>" placeholder="平台分配的签名密钥"></label>
            <label>Token<input name="auth_token" value="<?= htmlspecialchars((string) ($callbackAuthConfig['token'] ?? '')) ?>" placeholder="Bearer Token 可填"></label>
            <label>签名 Header<input name="auth_header_name" value="<?= htmlspecialchars((string) ($callbackAuthConfig['header_name'] ?? 'X-JX-Signature')) ?>" placeholder="X-JX-Signature"></label>
            <label>Token Header<input name="auth_token_header_name" value="<?= htmlspecialchars((string) ($callbackAuthConfig['token_header_name'] ?? 'Authorization')) ?>" placeholder="Authorization"></label>
            <label>Query 签名字段<input name="auth_query_key" value="<?= htmlspecialchars((string) ($callbackAuthConfig['query_key'] ?? 'sign')) ?>" placeholder="sign"></label>
            <label>Body 签名字段<input name="auth_body_key" value="<?= htmlspecialchars((string) ($callbackAuthConfig['body_key'] ?? 'sign')) ?>" placeholder="sign"></label>
            <label>时间戳字段<input name="auth_timestamp_key" value="<?= htmlspecialchars((string) ($callbackAuthConfig['timestamp_key'] ?? 'timestamp')) ?>" placeholder="timestamp"></label>
            <label>签名内容
                <select name="auth_sign_source">
                    <?php foreach (['body' => '请求 Body', 'body_with_timestamp' => 'Body + 时间戳', 'query' => 'Query 参数'] as $sourceKey => $sourceLabel): ?>
                        <option value="<?= htmlspecialchars($sourceKey) ?>" <?= (string) ($callbackAuthConfig['sign_source'] ?? 'body') === $sourceKey ? 'selected' : '' ?>><?= htmlspecialchars($sourceLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span><input type="checkbox" name="auth_include_timestamp" value="1" <?= !empty($callbackAuthConfig['include_timestamp']) ? 'checked' : '' ?>> 请求带时间戳</span></label>
        </div>
        <p><strong>失败重试策略</strong></p>
        <div class="form-grid">
            <label>最大重试次数<input name="retry_max_attempts" type="number" min="1" max="20" value="<?= htmlspecialchars((string) ($callbackRetryPolicy['max_attempts'] ?? 5)) ?>"></label>
            <label>基础间隔分钟<input name="retry_base_interval_minutes" type="number" min="1" max="1440" value="<?= htmlspecialchars((string) ($callbackRetryPolicy['base_interval_minutes'] ?? 5)) ?>"></label>
            <label>最大间隔分钟<input name="retry_max_interval_minutes" type="number" min="1" max="1440" value="<?= htmlspecialchars((string) ($callbackRetryPolicy['max_interval_minutes'] ?? 120)) ?>"></label>
            <label><span><input type="checkbox" name="retry_backoff" value="1" <?= !array_key_exists('backoff', $callbackRetryPolicy) || !empty($callbackRetryPolicy['backoff']) ? 'checked' : '' ?>> 启用指数退避</span></label>
        </div>
        <label>字段映射 JSON<textarea name="field_mapping_text" rows="5" placeholder='{"outer_code":"promotion_code","order_id":"order_no","price":"amount"}'><?= htmlspecialchars($callbackFieldMappingText) ?></textarea></label>
        <p class="muted">字段映射格式为“平台字段: 系统字段”，系统会额外保留 `_raw` 原始回传内容用于排查。</p>
        <p><button class="btn" type="submit">保存回传配置</button></p>
    </form>

    <div class="row-card stack">
        <div class="section-title admin-section-title">
            <div>
                <span class="eyebrow">模板预设</span>
                <h2>平台回传模板</h2>
            </div>
            <span class="muted">已内置 <?= number_format(count($callbackTemplateOptions)) ?> 个平台预设</span>
        </div>
        <div class="payment-rule-grid">
            <?php foreach ($callbackTemplateOptions as $templateKey => $template): ?>
                <?php
                    $template = (array) $template;
                    $templateMapping = (array) ($template['field_mapping'] ?? []);
                    $isCurrentCallbackTemplate = (string) ($callbackConfig['template_key'] ?? '') === (string) $templateKey;
                ?>
                <form method="post" action="/jxdjadmin#callback-config" class="system-item">
                    <input type="hidden" name="admin_action" value="apply_callback_template">
                    <input type="hidden" name="admin_section" value="callback-config">
                    <input type="hidden" name="callback_template_key" value="<?= htmlspecialchars((string) $templateKey) ?>">
                    <?= $csrfField() ?>
                    <strong><?= htmlspecialchars((string) ($template['name'] ?? $templateKey)) ?></strong>
                    <span><?= htmlspecialchars((string) ($template['platform'] ?? '投放平台')) ?><?= $isCurrentCallbackTemplate ? ' · 当前' : '' ?></span>
                    <span><?= htmlspecialchars((string) ($template['summary'] ?? '')) ?></span>
                    <span>加桌 <?= htmlspecialchars(implode(',', (array) ($template['add_desktop_events'] ?? ['active']))) ?> · 支付 <?= htmlspecialchars(implode(',', (array) ($template['paid_events'] ?? ['pay']))) ?></span>
                    <span><?= htmlspecialchars($callbackMappingSummary($templateMapping)) ?></span>
                    <input name="endpoint" value="<?= htmlspecialchars((string) (($callbackConfig['endpoint'] ?? '') ?: ($template['endpoint_placeholder'] ?? 'mock://success'))) ?>" placeholder="回传地址">
                    <label><span><input type="checkbox" name="enable_after_apply" value="1" <?= empty($callbackConfig['enabled']) ? 'checked' : '' ?>> 应用后启用队列</span></label>
                    <button class="btn ghost" type="submit"><?= $isCurrentCallbackTemplate ? '重新应用' : '应用模板' ?></button>
                </form>
            <?php endforeach; ?>
        </div>
    </div>

    <form method="post" action="/jxdjadmin" class="row-card">
        <input type="hidden" name="admin_action" value="bulk_send_callback_logs">
        <input type="hidden" name="admin_section" value="callback-config">
        <input type="hidden" name="limit" value="50">
        <?= $csrfField() ?>
        <span>
            <strong>批量发送待处理回传</strong>
            <em>只处理待回传和失败记录，单次最多 50 条。</em>
        </span>
        <button class="btn ghost" type="submit" <?= $pendingCallbackCount <= 0 ? 'disabled' : '' ?>>批量发送/重试</button>
    </form>

    <form class="order-filter-bar" method="get" action="/jxdjadmin">
        <input type="hidden" name="admin_section" value="callback-config">
        <label>状态
            <select name="callback_status">
                <option value="all" <?= $callbackFilters['status'] === 'all' ? 'selected' : '' ?>>全部状态</option>
                <?php foreach ($callbackStatusLabels as $statusValue => $statusText): ?>
                    <option value="<?= htmlspecialchars($statusValue) ?>" <?= $callbackFilters['status'] === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusText) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>事件
            <select name="callback_event">
                <option value="all" <?= $callbackFilters['event'] === 'all' ? 'selected' : '' ?>>全部事件</option>
                <?php foreach (['add_desktop', 'paid'] as $eventValue): ?>
                    <option value="<?= htmlspecialchars($eventValue) ?>" <?= $callbackFilters['event'] === $eventValue ? 'selected' : '' ?>><?= htmlspecialchars($callbackEventLabels[$eventValue] ?? $eventValue) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>订单号<input name="callback_order_no" value="<?= htmlspecialchars($callbackFilters['order_no']) ?>" placeholder="订单号"></label>
        <label>推广码<input name="callback_code" value="<?= htmlspecialchars($callbackFilters['code']) ?>" placeholder="推广码"></label>
        <label>平台<input name="callback_platform" value="<?= htmlspecialchars($callbackFilters['platform']) ?>" placeholder="巨量 / 快手"></label>
        <label>应用Key<input name="callback_app_key" value="<?= htmlspecialchars($callbackFilters['app_key']) ?>" placeholder="app_key"></label>
        <label>广告ID<input name="callback_ad_id" value="<?= htmlspecialchars($callbackFilters['ad_id']) ?>" placeholder="ad_id"></label>
        <label>素材ID<input name="callback_material_id" value="<?= htmlspecialchars($callbackFilters['material_id']) ?>" placeholder="material_id"></label>
        <div class="order-filter-actions">
            <button class="btn primary" type="submit">筛选回传</button>
            <a class="btn ghost" href="/jxdjadmin?admin_section=callback-config#callback-config">重置</a>
        </div>
    </form>

    <div class="filter-preset-panel">
        <form class="filter-preset-save" method="post" action="/jxdjadmin?admin_section=callback-config#callback-config">
            <input type="hidden" name="admin_action" value="save_filter_preset">
            <input type="hidden" name="admin_section" value="callback-config">
            <input type="hidden" name="preset_scope" value="callback_logs">
            <?= $csrfField() ?>
            <?php foreach (['status', 'event', 'order_no', 'code', 'platform', 'app_key', 'ad_id', 'material_id'] as $callbackFilterKey): ?>
                <input type="hidden" name="callback_<?= htmlspecialchars($callbackFilterKey) ?>" value="<?= htmlspecialchars((string) ($callbackFilters[$callbackFilterKey] ?? '')) ?>">
            <?php endforeach; ?>
            <input name="preset_name" placeholder="保存当前回传筛选">
            <label><span><input type="checkbox" name="preset_shared" value="1"> 共享</span></label>
            <button class="btn ghost" type="submit">保存方案</button>
        </form>
        <?php if (!empty($callbackFilterPresets)): ?>
            <div class="filter-preset-list">
                <?php foreach ($callbackFilterPresets as $preset): ?>
                    <?php $canDeletePreset = (int) ($preset['admin_id'] ?? 0) === $currentAdminId || (string) ($adminScope['role'] ?? '') === 'super_admin'; ?>
                    <div class="filter-preset-item">
                        <a href="<?= htmlspecialchars($callbackFilterPresetUrl($preset)) ?>">
                            <strong><?= htmlspecialchars((string) ($preset['name'] ?? '回传筛选')) ?><?= !empty($preset['is_shared']) ? ' · 共享' : '' ?></strong>
                            <em class="muted"><?= htmlspecialchars($filterPresetSummary($preset, $callbackFilterPresetLabels)) ?></em>
                        </a>
                        <?php if ($canDeletePreset): ?>
                            <form class="inline-form" method="post" action="/jxdjadmin?admin_section=callback-config#callback-config" onsubmit="return confirm('确认删除这个筛选方案吗？');">
                                <input type="hidden" name="admin_action" value="delete_filter_preset">
                                <input type="hidden" name="admin_section" value="callback-config">
                                <input type="hidden" name="preset_scope" value="callback_logs">
                                <input type="hidden" name="preset_id" value="<?= (int) ($preset['id'] ?? 0) ?>">
                                <?= $csrfField() ?>
                                <button class="btn ghost" type="submit">删除</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="dashboard-grid three">
        <article class="stat-card">
            <span class="kpi-icon"><?= jx_icon('stats') ?></span>
            <strong><?= number_format(count($filteredCallbackLogs)) ?> 条</strong>
            <em>当前筛选</em>
        </article>
        <article class="stat-card">
            <span class="kpi-icon"><?= jx_icon('payment') ?></span>
            <strong><?= number_format((int) ($callbackStatusCounts['success'] ?? 0)) ?> 条</strong>
            <em>成功回传</em>
        </article>
        <article class="stat-card">
            <span class="kpi-icon"><?= jx_icon('withdraw') ?></span>
            <strong><?= number_format((int) ($callbackStatusCounts['failed'] ?? 0)) ?> 条</strong>
            <em>失败记录</em>
        </article>
    </div>

    <div class="order-toolbar">
        <div>
            <strong>筛选结果 <?= number_format(count($filteredCallbackLogs)) ?> 条</strong>
            <span class="muted">待回传 <?= number_format((int) ($callbackStatusCounts['pending'] ?? 0)) ?> · 已跳过 <?= number_format((int) ($callbackStatusCounts['skipped'] ?? 0)) ?></span>
        </div>
        <form class="inline-form" method="post" action="/jxdjadmin">
            <input type="hidden" name="admin_action" value="export_callback_logs_csv">
            <input type="hidden" name="admin_section" value="callback-config">
            <?= $csrfField() ?>
            <?php foreach (['status', 'event', 'order_no', 'code', 'platform', 'app_key', 'ad_id', 'material_id'] as $callbackFilterKey): ?>
                <input type="hidden" name="callback_<?= htmlspecialchars($callbackFilterKey) ?>" value="<?= htmlspecialchars((string) ($callbackFilters[$callbackFilterKey] ?? '')) ?>">
            <?php endforeach; ?>
            <button class="btn ghost" type="submit" <?= empty($filteredCallbackLogs) ? 'disabled' : '' ?>>导出当前筛选回传</button>
        </form>
    </div>

    <?php if (!empty($callbackFailureReasons)): ?>
        <div class="row-card stack">
            <strong>失败原因汇总</strong>
            <?php foreach (array_slice($callbackFailureReasons, 0, 5, true) as $reason => $count): ?>
                <span><span class="pill ember"><?= number_format((int) $count) ?> 条</span> <?= htmlspecialchars((string) $reason) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="order-table">
        <div class="row-card order-row-head">
            <span>事件</span>
            <span>归因</span>
            <span>状态</span>
            <span>请求内容</span>
        </div>
        <?php if (empty($callbackLogs)): ?>
            <p class="muted">暂无回传记录。产生加桌或支付事件后，会在这里留下回传队列日志。</p>
        <?php elseif (empty($filteredCallbackLogs)): ?>
            <p class="muted">没有符合筛选条件的回传记录。</p>
        <?php endif; ?>
        <?php foreach (array_slice($filteredCallbackLogs, 0, 100) as $log): ?>
            <?php
                $status = (string) ($log['status'] ?? 'pending');
                $statusClass = match ($status) {
                    'success' => 'jade',
                    'failed' => 'ember',
                    'skipped' => '',
                    default => 'blue',
                };
                $payload = (array) ($log['request_payload'] ?? []);
                $response = (array) ($log['response_payload'] ?? []);
                $callbackTrafficLines = $trafficMetaLines($log);
                $callbackSourceLabel = (string) ($log['callback_policy_source'] ?? 'global') === 'app' ? '应用策略' : '全局策略';
                $callbackAppLabel = trim((string) (($log['app_name'] ?? '') ?: ($log['app_key'] ?? '')));
                $callbackLogTemplateName = (string) (($log['callback_template_name'] ?? '') ?: '自定义模板');
                $callbackLogMappingSummary = $callbackMappingSummary((array) ($log['callback_field_mapping'] ?? []));
                $callbackLogAuthConfig = (array) ($log['callback_auth_config'] ?? []);
                $callbackLogAuthMode = (string) ($callbackLogAuthConfig['mode'] ?? $callbackAuthMode);
                $callbackLogAuthLabel = $callbackAuthModeLabels[$callbackLogAuthMode] ?? $callbackLogAuthMode;
            ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars($callbackEventLabels[(string) ($log['event'] ?? '')] ?? (string) ($log['event'] ?? '事件')) ?></strong>
                    <em>平台事件：<?= htmlspecialchars((string) ($log['platform_event'] ?? '-')) ?></em>
                    <em><?= htmlspecialchars((string) ($log['created_at'] ?? '')) ?></em>
                </span>
                <span>
                    推广码 <?= htmlspecialchars((string) (($log['code'] ?? '') ?: '-')) ?>
                    <?php if ($callbackAppLabel !== ''): ?><em>应用 <?= htmlspecialchars($callbackAppLabel) ?> · <?= htmlspecialchars($callbackSourceLabel) ?></em><?php else: ?><em><?= htmlspecialchars($callbackSourceLabel) ?></em><?php endif; ?>
                    <em>用户 <?= number_format((int) ($log['user_id'] ?? 0)) ?> · 订单 <?= htmlspecialchars((string) (($log['order_no'] ?? '') ?: '-')) ?></em>
                    <em>金额 <?= htmlspecialchars($money((float) ($log['amount'] ?? 0))) ?></em>
                    <?php if (!empty($callbackTrafficLines)): ?>
                        <em><?= htmlspecialchars(implode(' · ', array_slice($callbackTrafficLines, 0, 3))) ?></em>
                    <?php endif; ?>
                </span>
                <span>
                    <span class="pill <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($callbackStatusLabels[$status] ?? $status) ?></span>
                    <em><?= htmlspecialchars((string) ($log['message'] ?? '')) ?></em>
                    <em>尝试 <?= number_format((int) ($log['attempt_count'] ?? 0)) ?> 次 · 最后 <?= htmlspecialchars((string) (($log['last_attempt_at'] ?? '') ?: '-')) ?></em>
                    <?php if (!empty($log['next_retry_at']) || !empty($log['retry_blocked_reason'])): ?>
                        <em>下次重试 <?= htmlspecialchars((string) (($log['next_retry_at'] ?? '') ?: '-')) ?><?= !empty($log['retry_blocked_reason']) ? ' · ' . htmlspecialchars((string) $log['retry_blocked_reason']) : '' ?></em>
                    <?php endif; ?>
                    <em>模板 <?= htmlspecialchars($callbackLogTemplateName) ?> · <?= htmlspecialchars($callbackLogMappingSummary) ?></em>
                    <em>鉴权 <?= htmlspecialchars((string) $callbackLogAuthLabel) ?><?= !empty($callbackLogAuthConfig['include_timestamp']) ? ' · 时间戳' : '' ?></em>
                    <em>失败重试 <?= array_key_exists('callback_retry_failed', $log) && empty($log['callback_retry_failed']) ? '关闭' : '开启' ?> · 时间兜底 <?= !empty($log['callback_fallback_time_match']) ? '开启' : '关闭' ?></em>
                    <em><?= htmlspecialchars((string) (($log['endpoint'] ?? '') ?: '未配置地址')) ?></em>
                </span>
                <span>
                    <?= htmlspecialchars((string) ($payload['platform'] ?? ($callbackConfig['platform'] ?? '平台'))) ?> · <?= htmlspecialchars((string) ($payload['event'] ?? '-')) ?>
                    <em><?= htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}') ?></em>
                    <?php if (!empty($response)): ?>
                        <em>响应：<?= htmlspecialchars(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}') ?></em>
                    <?php endif; ?>
                    <?php if (in_array($status, ['pending', 'failed', 'skipped'], true)): ?>
                        <form method="post" action="/jxdjadmin" class="inline-form">
                            <input type="hidden" name="admin_action" value="send_callback_log">
                            <input type="hidden" name="admin_section" value="callback-config">
                            <input type="hidden" name="callback_log_id" value="<?= (int) ($log['id'] ?? 0) ?>">
                            <?= $csrfField() ?>
                            <button class="btn ghost" type="submit"><?= $status === 'failed' ? '重试' : '发送' ?></button>
                        </form>
                    <?php endif; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'feedback' ? 'is-active' : '' ?>" id="admin-section-feedback" data-admin-section="feedback" data-admin-primary="messages">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">消息管理</span>
            <h2>投诉反馈</h2>
        </div>
        <span class="muted">待处理 <?= number_format($pendingFeedbackCount) ?> 条 · 已记录 <?= number_format(count($feedbackItems)) ?> 条</span>
    </div>

    <div class="payment-rule-grid">
        <div class="system-item"><strong><?= number_format((int) ($feedbackSummary['open'] ?? $pendingFeedbackCount)) ?> 条</strong><span>待处理/处理中</span></div>
        <div class="system-item"><strong><?= number_format((int) ($feedbackSummary['overdue'] ?? 0)) ?> 条</strong><span>SLA 超时</span></div>
        <div class="system-item"><strong><?= number_format((int) ($feedbackSummary['payment'] ?? 0)) ?> 条</strong><span>支付相关</span></div>
        <div class="system-item"><strong><?= number_format((int) ($feedbackSummary['suggest_refund'] ?? 0)) ?> 条</strong><span>建议退款</span></div>
        <div class="system-item"><strong><?= number_format((int) ($feedbackSummary['suggest_rights_repair'] ?? 0)) ?> 条</strong><span>建议补发</span></div>
        <div class="system-item"><strong><?= number_format((float) ($feedbackSummary['sla_hit_rate'] ?? 100), 1) ?>%</strong><span>SLA 达标率</span></div>
    </div>

    <?php if (!empty($feedbackAppRows) || !empty($feedbackActionRows)): ?>
        <div class="row-card stack">
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">售后看板</span>
                    <h2>应用与处理建议</h2>
                </div>
                <span class="muted">已关联订单 <?= number_format((int) ($feedbackSummary['with_order'] ?? 0)) ?> · 带推广归因 <?= number_format((int) ($feedbackSummary['with_promotion'] ?? 0)) ?> · 平均处理 <?= htmlspecialchars($formatMinutes((int) ($feedbackSummary['avg_handle_minutes'] ?? 0))) ?></span>
            </div>
            <div class="order-table">
                <div class="row-card order-row-head">
                    <span>应用</span>
                    <span>待办</span>
                    <span>风险</span>
                    <span>退款建议</span>
                </div>
                <?php if (empty($feedbackAppRows)): ?>
                    <p class="muted">暂无应用维度反馈。</p>
                <?php endif; ?>
                <?php foreach (array_slice($feedbackAppRows, 0, 6) as $row): ?>
                    <div class="row-card order-row">
                        <span><strong><?= htmlspecialchars((string) (($row['app_name'] ?? '') ?: ($row['app_key'] ?? 'default'))) ?></strong><em><?= htmlspecialchars((string) ($row['app_key'] ?? 'default')) ?></em></span>
                        <span><?= number_format((int) ($row['open'] ?? 0)) ?> 条<em>总计 <?= number_format((int) ($row['total'] ?? 0)) ?></em></span>
                        <span><span class="pill <?= (int) ($row['overdue'] ?? 0) > 0 ? 'orange' : 'blue' ?>">超时 <?= number_format((int) ($row['overdue'] ?? 0)) ?></span><em>支付 <?= number_format((int) ($row['payment'] ?? 0)) ?></em></span>
                        <span><?= number_format((int) ($row['suggest_refund'] ?? 0)) ?> 条<em>需客服核实后执行</em></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($feedbackActionRows)): ?>
                <div class="order-table">
                    <div class="row-card order-row-head">
                        <span>建议动作</span>
                        <span>待办</span>
                        <span>超时</span>
                        <span>总量</span>
                    </div>
                    <?php foreach (array_slice($feedbackActionRows, 0, 6) as $row): ?>
                        <?php $actionKey = (string) ($row['action'] ?? 'none'); ?>
                        <div class="row-card order-row">
                            <span><strong><?= htmlspecialchars($feedbackActionLabels[$actionKey] ?? $actionKey) ?></strong><em><?= htmlspecialchars($actionKey) ?></em></span>
                            <span><?= number_format((int) ($row['open'] ?? 0)) ?> 条</span>
                            <span><span class="pill <?= (int) ($row['overdue'] ?? 0) > 0 ? 'orange' : 'blue' ?>"><?= number_format((int) ($row['overdue'] ?? 0)) ?> 条</span></span>
                            <span><?= number_format((int) ($row['total'] ?? 0)) ?> 条</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="order-table">
        <div class="row-card order-row-head">
            <span>反馈内容</span>
            <span>关联对象</span>
            <span>来源归因</span>
            <span>处理</span>
        </div>
        <?php if (empty($feedbackItems)): ?>
            <p class="muted">暂无投诉反馈。用户可通过 api-feedback-submit 提交，后台会按推广/订单范围展示。</p>
        <?php endif; ?>
        <?php foreach (array_slice($feedbackItems, 0, 80) as $feedback): ?>
            <?php
                $feedbackStatus = (string) ($feedback['status'] ?? 'pending');
                $feedbackStatusClass = match ($feedbackStatus) {
                    'resolved' => 'jade',
                    'rejected' => 'ember',
                    'processing' => 'blue',
                    default => '',
                };
                $feedbackPriority = (string) ($feedback['priority'] ?? 'normal');
                $feedbackSlaStatus = (string) ($feedback['sla_status'] ?? 'normal');
                $feedbackSlaClass = match ($feedbackSlaStatus) {
                    'overdue', 'handled_overdue' => 'orange',
                    'due_soon' => 'ember',
                    'handled_on_time' => 'jade',
                    default => 'blue',
                };
                $feedbackAction = (string) ($feedback['suggested_action'] ?? 'none');
                $feedbackTrafficLines = $trafficMetaLines($feedback);
                $feedbackContentType = (string) ($feedback['content_type'] ?? '');
                $feedbackContentLabel = $feedbackContentType === 'novel'
                    ? '小说 #' . (int) ($feedback['novel_id'] ?? 0) . ((int) ($feedback['chapter_id'] ?? 0) > 0 ? ' / 章节 #' . (int) ($feedback['chapter_id'] ?? 0) : '')
                    : ($feedbackContentType === 'drama' ? '短剧 #' . (int) ($feedback['drama_id'] ?? 0) . ((int) ($feedback['episode_id'] ?? 0) > 0 ? ' / 分集 #' . (int) ($feedback['episode_id'] ?? 0) : '') : '-');
                $feedbackOrderSnapshot = (array) ($feedback['order_snapshot'] ?? []);
            ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) (($feedback['title'] ?? '') ?: ($feedbackTypeLabels[(string) ($feedback['type'] ?? 'feedback')] ?? '反馈'))) ?></strong>
                    <em><?= htmlspecialchars((string) ($feedback['content'] ?? '')) ?></em>
                    <em><?= htmlspecialchars((string) ($feedback['created_at'] ?? '')) ?> · 用户 <?= number_format((int) ($feedback['user_id'] ?? 0)) ?></em>
                    <em><?= htmlspecialchars((string) (($feedback['nickname'] ?? '') ?: '-')) ?> · <?= htmlspecialchars((string) (($feedback['phone'] ?? '') ?: ($feedback['contact'] ?? '-'))) ?></em>
                    <em>应用 <?= htmlspecialchars((string) (($feedback['app_name'] ?? '') ?: ($feedback['app_key'] ?? 'default'))) ?></em>
                </span>
                <span>
                    <span class="pill"><?= htmlspecialchars($feedbackTypeLabels[(string) ($feedback['type'] ?? 'feedback')] ?? '反馈') ?></span>
                    <span class="pill <?= htmlspecialchars($feedbackSlaClass) ?>"><?= htmlspecialchars($feedbackSlaLabels[$feedbackSlaStatus] ?? $feedbackSlaStatus) ?></span>
                    <em>订单 <?= htmlspecialchars((string) (($feedback['order_no'] ?? '') ?: '-')) ?></em>
                    <?php if (!empty($feedbackOrderSnapshot)): ?>
                        <em><?= htmlspecialchars((string) ($feedbackOrderSnapshot['status'] ?? '-')) ?> · <?= htmlspecialchars($money((float) ($feedbackOrderSnapshot['amount'] ?? 0))) ?> · 已退 <?= htmlspecialchars($money((float) ($feedbackOrderSnapshot['refund_total'] ?? 0))) ?></em>
                    <?php endif; ?>
                    <em><?= htmlspecialchars($feedbackContentLabel) ?></em>
                    <em>优先级 <?= htmlspecialchars($feedbackPriorityLabels[$feedbackPriority] ?? $feedbackPriority) ?> · 截止 <?= htmlspecialchars((string) (($feedback['due_at'] ?? '') ?: '-')) ?></em>
                </span>
                <span>
                    推广码 <?= htmlspecialchars((string) (($feedback['promotion_code'] ?? '') ?: '-')) ?>
                    <em>链接 #<?= number_format((int) ($feedback['promotion_link_id'] ?? 0)) ?> · 代理 #<?= number_format((int) ($feedback['agent_id'] ?? 0)) ?></em>
                    <?php if (!empty($feedbackTrafficLines)): ?>
                        <em><?= htmlspecialchars(implode(' · ', array_slice($feedbackTrafficLines, 0, 4))) ?></em>
                    <?php else: ?>
                        <em>暂无投流参数</em>
                    <?php endif; ?>
                    <span class="pill <?= in_array($feedbackAction, ['refund', 'rights_repair', 'query_payment'], true) ? 'orange' : 'blue' ?>"><?= htmlspecialchars($feedbackActionLabels[$feedbackAction] ?? $feedbackAction) ?></span>
                    <em><?= htmlspecialchars((string) (($feedback['suggested_reason'] ?? '') ?: '按客服流程处理')) ?></em>
                </span>
                <span>
                    <form method="post" action="/jxdjadmin#feedback" class="stack">
                        <input type="hidden" name="admin_action" value="update_feedback">
                        <input type="hidden" name="admin_section" value="feedback">
                        <input type="hidden" name="feedback_id" value="<?= (int) ($feedback['id'] ?? 0) ?>">
                        <?= $csrfField() ?>
                        <span class="pill <?= htmlspecialchars($feedbackStatusClass) ?>"><?= htmlspecialchars($feedbackStatusLabels[$feedbackStatus] ?? $feedbackStatus) ?></span>
                        <div class="form-grid">
                            <label>状态
                                <select name="status">
                                    <?php foreach ($feedbackStatusLabels as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= $feedbackStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>优先级
                                <select name="priority">
                                    <?php foreach ($feedbackPriorityLabels as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= $feedbackPriority === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                        <label>处理回复
                            <textarea name="reply" rows="2" placeholder="记录处理结果"><?= htmlspecialchars((string) ($feedback['reply'] ?? '')) ?></textarea>
                        </label>
                        <?php if (!empty($feedback['handled_by_admin_name'])): ?>
                            <em><?= htmlspecialchars((string) ($feedback['handled_by_admin_name'] ?? '')) ?> · <?= htmlspecialchars((string) ($feedback['handled_at'] ?? '')) ?> · 耗时 <?= htmlspecialchars($formatMinutes((int) ($feedback['handled_minutes'] ?? 0))) ?></em>
                        <?php endif; ?>
                        <button class="btn ghost" type="submit">保存处理</button>
                    </form>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'message-template' ? 'is-active' : '' ?>" id="admin-section-message-template" data-admin-section="message-template" data-admin-primary="messages">
    <?php
        $messageChannelLabels = ['system' => '站内', 'sms' => '短信', 'email' => '邮件', 'webhook' => 'Webhook', 'in_app' => '站内消息'];
        $messageScenarioLabels = ['login_code' => '登录验证码', 'config_approval' => '配置审批', 'operation_alert' => '投放预警', 'feedback_reply' => '反馈回复', 'rights_repair' => '权益补发', 'payment_success' => '支付成功', 'activity' => '运营活动', 'system_notice' => '系统通知'];
        $activeTemplateCount = count(array_filter($messageTemplates, static fn (array $item): bool => (string) ($item['status'] ?? 'active') === 'active'));
        $templateChannelCounts = [];
        foreach ($messageTemplates as $templateForCount) {
            $templateChannel = (string) ($templateForCount['channel'] ?? 'system');
            $templateChannelCounts[$templateChannel] = (int) ($templateChannelCounts[$templateChannel] ?? 0) + 1;
        }
        $inAppUnreadCount = count(array_filter($inAppMessages, static fn (array $item): bool => (string) ($item['status'] ?? 'unread') === 'unread'));
        $inAppAdminCount = count(array_filter($inAppMessages, static fn (array $item): bool => (string) ($item['recipient_type'] ?? '') === 'admin'));
        $recentInAppMessages = $inAppMessages;
        usort($recentInAppMessages, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
        $inAppTemplateOptions = array_values(array_filter($messageTemplates, static fn (array $item): bool => (string) ($item['status'] ?? 'active') === 'active' && in_array((string) ($item['channel'] ?? 'system'), ['system', 'in_app'], true)));
    ?>
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">消息管理</span>
            <h2>消息模板</h2>
        </div>
        <span class="muted">模板 <?= number_format(count($messageTemplates)) ?> 个 · 启用 <?= number_format($activeTemplateCount) ?> 个</span>
    </div>
    <?php if (!empty($message)): ?><p class="notice"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('banner') ?></span>
            <small>模板总数</small>
            <strong><?= number_format(count($messageTemplates)) ?></strong>
            <em>短信 / 邮件 / 站内 / Webhook</em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('stats') ?></span>
            <small>启用模板</small>
            <strong><?= number_format($activeTemplateCount) ?></strong>
            <em>停用 <?= number_format(max(0, count($messageTemplates) - $activeTemplateCount)) ?></em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('message') ?></span>
            <small>短信/邮件</small>
            <strong><?= number_format((int) ($templateChannelCounts['sms'] ?? 0)) ?> / <?= number_format((int) ($templateChannelCounts['email'] ?? 0)) ?></strong>
            <em>登录验证与测试邮件</em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('setting') ?></span>
            <small>站内/Webhook</small>
            <strong><?= number_format((int) (($templateChannelCounts['system'] ?? 0) + ($templateChannelCounts['in_app'] ?? 0))) ?> / <?= number_format((int) ($templateChannelCounts['webhook'] ?? 0)) ?></strong>
            <em>审批、预警、客服通知</em>
        </div>
    </div>
    <?php if ((string) ($adminScope['role'] ?? '') === 'super_admin'): ?>
        <form method="post" action="/jxdjadmin#message-template" class="row-card stack">
            <input type="hidden" name="admin_action" value="send_in_app_message">
            <input type="hidden" name="admin_section" value="message-template">
            <?= $csrfField() ?>
            <p><strong>手动投递站内消息</strong></p>
            <div class="form-grid">
                <label>消息模板
                    <select name="template_id">
                        <option value="0">不使用模板</option>
                        <?php foreach ($inAppTemplateOptions as $templateOption): ?>
                            <option value="<?= (int) ($templateOption['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($templateOption['name'] ?? '消息模板')) ?> · <?= htmlspecialchars((string) ($templateOption['template_key'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>接收类型
                    <select name="receiver_type">
                        <option value="user">指定用户</option>
                        <option value="admin">指定后台账号</option>
                        <option value="all_admins">全部启用后台账号</option>
                        <option value="role">按后台角色</option>
                    </select>
                </label>
                <label>接收人 ID<input name="receiver_id" type="number" min="0" placeholder="用户或后台账号 ID"></label>
                <label>后台角色
                    <select name="receiver_role">
                        <?php foreach ($adminRoleLabels as $roleKey => $roleLabel): ?>
                            <option value="<?= htmlspecialchars($roleKey) ?>"><?= htmlspecialchars($roleLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>优先级
                    <select name="priority">
                        <option value="normal">普通</option>
                        <option value="high">重要</option>
                        <option value="urgent">紧急</option>
                        <option value="low">低</option>
                    </select>
                </label>
                <label>标题<input name="title" placeholder="不填时使用模板标题"></label>
                <label>关联类型<input name="reference_type" placeholder="order / feedback / alert"></label>
                <label>关联 ID<input name="reference_id" type="number" min="0" value="0"></label>
            </div>
            <label>消息内容<textarea name="body" rows="3" placeholder="不填时使用模板内容；未选模板时必填"></textarea></label>
            <label>模板变量<textarea name="variables_text" rows="3" placeholder="site=精秀短剧&#10;message=您的反馈已处理"></textarea></label>
            <p><button class="btn primary" type="submit">投递站内消息</button></p>
        </form>
        <form method="post" action="/jxdjadmin#message-template" class="row-card stack">
            <input type="hidden" name="admin_action" value="resend_business_in_app_message">
            <input type="hidden" name="admin_section" value="message-template">
            <?= $csrfField() ?>
            <p><strong>按业务单据重发站内消息</strong></p>
            <div class="form-grid">
                <label>业务类型
                    <select name="reference_type">
                        <option value="order">支付成功订单</option>
                        <option value="feedback">投诉反馈</option>
                        <option value="rights_repair">权益补发/撤销记录</option>
                        <option value="activity_log">活动领奖记录</option>
                    </select>
                </label>
                <label>业务 ID<input name="reference_id" type="number" min="0" placeholder="反馈ID / 权益日志ID / 活动日志ID"></label>
                <label>订单号<input name="order_no" placeholder="支付订单可填订单号"></label>
                <label>说明<input value="按业务模板自动生成内容" readonly></label>
            </div>
            <p><button class="btn ghost" type="submit">重发业务消息</button></p>
        </form>
        <form method="post" action="/jxdjadmin#message-template" class="row-card stack">
            <input type="hidden" name="admin_action" value="save_message_template">
            <input type="hidden" name="admin_section" value="message-template">
            <?= $csrfField() ?>
            <p><strong>新增消息模板</strong></p>
            <div class="form-grid">
                <label>模板编码<input name="template_key" placeholder="例如 feedback_reply_notice"></label>
                <label>模板名称<input name="name" placeholder="例如 投诉反馈处理通知"></label>
                <label>业务场景
                    <select name="scenario">
                        <?php foreach ($messageScenarioLabels as $scenarioKey => $scenarioLabel): ?>
                            <option value="<?= htmlspecialchars($scenarioKey) ?>"><?= htmlspecialchars($scenarioLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>发送渠道
                    <select name="channel">
                        <?php foreach ($messageChannelLabels as $channelKey => $channelLabel): ?>
                            <option value="<?= htmlspecialchars($channelKey) ?>"><?= htmlspecialchars($channelLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>状态
                    <select name="status">
                        <option value="active">启用</option>
                        <option value="paused">停用</option>
                    </select>
                </label>
                <label>排序<input name="sort" type="number" value="100"></label>
                <label>标题模板<input name="title_template" placeholder="例如 您的反馈已处理"></label>
                <label>变量<input name="placeholders" placeholder="code,minutes,site"></label>
            </div>
            <label>内容模板<textarea name="body_template" rows="3" placeholder="支持 {code} 或 {{message}} 这类变量"></textarea></label>
            <label>备注<input name="remark" placeholder="使用位置或注意事项"></label>
            <p><button class="btn primary" type="submit">保存消息模板</button></p>
        </form>
    <?php endif; ?>
    <div class="order-table">
        <div class="row-card order-row-head">
            <span>站内消息</span>
            <span>接收人</span>
            <span>状态</span>
            <span>内容</span>
        </div>
        <?php if (empty($recentInAppMessages)): ?>
            <p class="muted">暂无站内消息。</p>
        <?php endif; ?>
        <?php foreach (array_slice($recentInAppMessages, 0, 30) as $inAppMessage): ?>
            <?php
                $inAppStatus = (string) ($inAppMessage['status'] ?? 'unread');
                $inAppPriority = (string) ($inAppMessage['priority'] ?? 'normal');
                $inAppStatusClass = $inAppStatus === 'read' ? 'green' : ($inAppPriority === 'urgent' ? 'red' : 'blue');
            ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($inAppMessage['title'] ?? '站内消息')) ?></strong>
                    <em><?= htmlspecialchars((string) ($inAppMessage['source'] ?? 'manual')) ?> · <?= htmlspecialchars((string) ($inAppMessage['scenario'] ?? 'system_notice')) ?></em>
                    <em><?= htmlspecialchars((string) ($inAppMessage['created_at'] ?? '')) ?></em>
                </span>
                <span>
                    <strong><?= htmlspecialchars((string) (($inAppMessage['recipient_name'] ?? '') ?: '-')) ?></strong>
                    <em><?= htmlspecialchars((string) ($inAppMessage['recipient_type'] ?? 'user')) ?> #<?= (int) (($inAppMessage['recipient_type'] ?? '') === 'admin' ? ($inAppMessage['admin_id'] ?? 0) : ($inAppMessage['user_id'] ?? 0)) ?></em>
                    <em><?= htmlspecialchars((string) ($inAppMessage['recipient_contact'] ?? '')) ?></em>
                </span>
                <span>
                    <span class="pill <?= htmlspecialchars($inAppStatusClass) ?>"><?= $inAppStatus === 'read' ? '已读' : '未读' ?></span>
                    <em>优先级 <?= htmlspecialchars($inAppPriority) ?> · 未读 <?= number_format($inAppUnreadCount) ?></em>
                    <em>后台消息 <?= number_format($inAppAdminCount) ?> 条</em>
                </span>
                <span>
                    <?= htmlspecialchars((string) ($inAppMessage['body'] ?? '')) ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="order-table">
        <div class="row-card order-row-head">
            <span>模板</span>
            <span>场景/渠道</span>
            <span>内容</span>
            <span>维护</span>
        </div>
        <?php if (empty($messageTemplates)): ?>
            <p class="muted">暂无消息模板。</p>
        <?php endif; ?>
        <?php foreach ($messageTemplates as $template): ?>
            <?php
                $templateStatus = (string) ($template['status'] ?? 'active');
                $templateChannel = (string) ($template['channel'] ?? 'system');
                $templateScenario = (string) ($template['scenario'] ?? 'system_notice');
                $placeholderText = implode(',', array_map('strval', (array) ($template['placeholders'] ?? [])));
            ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($template['name'] ?? '消息模板')) ?></strong>
                    <em><?= htmlspecialchars((string) ($template['template_key'] ?? '')) ?></em>
                    <em>排序 <?= number_format((int) ($template['sort'] ?? 0)) ?> · <?= htmlspecialchars((string) ($template['updated_at'] ?? '')) ?></em>
                </span>
                <span>
                    <span class="pill <?= $templateStatus === 'active' ? 'green' : 'orange' ?>"><?= $templateStatus === 'active' ? '启用' : '停用' ?></span>
                    <em><?= htmlspecialchars($messageScenarioLabels[$templateScenario] ?? $templateScenario) ?> · <?= htmlspecialchars($messageChannelLabels[$templateChannel] ?? $templateChannel) ?></em>
                    <em>变量 <?= htmlspecialchars($placeholderText !== '' ? $placeholderText : '-') ?></em>
                </span>
                <span>
                    <strong><?= htmlspecialchars((string) (($template['title_template'] ?? '') ?: '-')) ?></strong>
                    <em><?= htmlspecialchars((string) ($template['body_template'] ?? '')) ?></em>
                    <em><?= htmlspecialchars((string) ($template['remark'] ?? '')) ?></em>
                </span>
                <span>
                    <?php if ((string) ($adminScope['role'] ?? '') === 'super_admin'): ?>
                        <details>
                            <summary class="btn mini ghost">编辑</summary>
                            <form method="post" action="/jxdjadmin#message-template" class="stack" style="margin-top:10px; min-width:320px">
                                <input type="hidden" name="admin_action" value="save_message_template">
                                <input type="hidden" name="admin_section" value="message-template">
                                <input type="hidden" name="template_id" value="<?= (int) ($template['id'] ?? 0) ?>">
                                <?= $csrfField() ?>
                                <div class="form-grid">
                                    <label>模板编码<input name="template_key" value="<?= htmlspecialchars((string) ($template['template_key'] ?? '')) ?>"></label>
                                    <label>模板名称<input name="name" value="<?= htmlspecialchars((string) ($template['name'] ?? '')) ?>"></label>
                                    <label>场景
                                        <select name="scenario">
                                            <?php foreach ($messageScenarioLabels as $scenarioKey => $scenarioLabel): ?>
                                                <option value="<?= htmlspecialchars($scenarioKey) ?>" <?= $templateScenario === $scenarioKey ? 'selected' : '' ?>><?= htmlspecialchars($scenarioLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label>渠道
                                        <select name="channel">
                                            <?php foreach ($messageChannelLabels as $channelKey => $channelLabel): ?>
                                                <option value="<?= htmlspecialchars($channelKey) ?>" <?= $templateChannel === $channelKey ? 'selected' : '' ?>><?= htmlspecialchars($channelLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label>状态
                                        <select name="status">
                                            <option value="active" <?= $templateStatus === 'active' ? 'selected' : '' ?>>启用</option>
                                            <option value="paused" <?= $templateStatus === 'paused' ? 'selected' : '' ?>>停用</option>
                                        </select>
                                    </label>
                                    <label>排序<input name="sort" type="number" value="<?= (int) ($template['sort'] ?? 100) ?>"></label>
                                    <label>标题模板<input name="title_template" value="<?= htmlspecialchars((string) ($template['title_template'] ?? '')) ?>"></label>
                                    <label>变量<input name="placeholders" value="<?= htmlspecialchars($placeholderText) ?>"></label>
                                </div>
                                <label>内容模板<textarea name="body_template" rows="3"><?= htmlspecialchars((string) ($template['body_template'] ?? '')) ?></textarea></label>
                                <label>备注<input name="remark" value="<?= htmlspecialchars((string) ($template['remark'] ?? '')) ?>"></label>
                                <p><button class="btn primary" type="submit">保存模板</button></p>
                            </form>
                        </details>
                    <?php else: ?>
                        <em>仅管理员可编辑</em>
                    <?php endif; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'notice-records' ? 'is-active' : '' ?>" id="admin-section-notice-records" data-admin-section="notice-records" data-admin-primary="messages">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">消息管理</span>
            <h2>通知记录</h2>
        </div>
        <span class="muted">最近 <?= number_format((int) ($notificationRecordSummary['total'] ?? 0)) ?> 条通知</span>
    </div>
    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('stats') ?></span>
            <small>通知总数</small>
            <strong><?= number_format((int) ($notificationRecordSummary['total'] ?? 0)) ?></strong>
            <em>来源 <?= number_format((int) ($notificationRecordSummary['sources'] ?? 0)) ?> · 渠道 <?= number_format((int) ($notificationRecordSummary['channels'] ?? 0)) ?></em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('message') ?></span>
            <small>成功</small>
            <strong><?= number_format((int) ($notificationRecordSummary['success'] ?? 0)) ?></strong>
            <em>已发送 / 模拟成功</em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('withdraw') ?></span>
            <small>失败</small>
            <strong><?= number_format((int) ($notificationRecordSummary['failed'] ?? 0)) ?></strong>
            <em>需要排查通道</em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('order') ?></span>
            <small>跳过/待处理</small>
            <strong><?= number_format((int) (($notificationRecordSummary['skipped'] ?? 0) + ($notificationRecordSummary['pending'] ?? 0))) ?></strong>
            <em>停用、无接收人或待发送</em>
        </div>
    </div>
    <div class="repair-grid">
        <div class="order-info-card">
            <h4>渠道分布</h4>
            <div class="repair-log-list">
                <?php if (empty($notificationRecordChannelRows)): ?>
                    <p class="muted">暂无通知渠道数据。</p>
                <?php endif; ?>
                <?php foreach ($notificationRecordChannelRows as $row): ?>
                    <div>
                        <strong><?= htmlspecialchars((string) ($row['label'] ?? '渠道')) ?></strong>
                        <span class="pill blue"><?= number_format((int) ($row['total'] ?? 0)) ?> 条</span>
                        <em>成功 <?= number_format((int) ($row['success'] ?? 0)) ?> · 失败 <?= number_format((int) ($row['failed'] ?? 0)) ?> · 跳过 <?= number_format((int) ($row['skipped'] ?? 0)) ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="order-info-card">
            <h4>来源分布</h4>
            <div class="repair-log-list">
                <?php if (empty($notificationRecordSourceRows)): ?>
                    <p class="muted">暂无通知来源数据。</p>
                <?php endif; ?>
                <?php foreach ($notificationRecordSourceRows as $row): ?>
                    <div>
                        <strong><?= htmlspecialchars((string) ($row['label'] ?? '来源')) ?></strong>
                        <span class="pill green"><?= number_format((int) ($row['total'] ?? 0)) ?> 条</span>
                        <em>成功 <?= number_format((int) ($row['success'] ?? 0)) ?> · 失败 <?= number_format((int) ($row['failed'] ?? 0)) ?> · 待处理 <?= number_format((int) ($row['pending'] ?? 0)) ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="order-table">
        <div class="row-card order-row-head">
            <span>通知</span>
            <span>接收人</span>
            <span>渠道/状态</span>
            <span>内容</span>
        </div>
        <?php if (empty($notificationRecordRows)): ?>
            <p class="muted">暂无通知记录。短信验证码、测试邮件、配置审批和投放预警外发都会汇总到这里。</p>
        <?php endif; ?>
        <?php foreach (array_slice($notificationRecordRows, 0, 120) as $record): ?>
            <?php
                $recordStatusGroup = (string) ($record['status_group'] ?? 'pending');
                $recordStatusClass = match ($recordStatusGroup) {
                    'success' => 'green',
                    'failed' => 'red',
                    'skipped' => 'orange',
                    default => 'blue',
                };
            ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($record['title'] ?? '通知记录')) ?></strong>
                    <em><?= htmlspecialchars((string) ($record['source_label'] ?? '')) ?> · <?= htmlspecialchars((string) ($record['reference'] ?? '')) ?></em>
                    <em><?= htmlspecialchars((string) ($record['created_at'] ?? '')) ?></em>
                </span>
                <span>
                    <?= htmlspecialchars((string) (($record['receiver'] ?? '') ?: '-')) ?>
                </span>
                <span>
                    <span class="pill <?= htmlspecialchars($recordStatusClass) ?>"><?= htmlspecialchars((string) ($record['status_label'] ?? '待处理')) ?></span>
                    <em><?= htmlspecialchars((string) ($record['channel_label'] ?? '')) ?> · <?= htmlspecialchars((string) ($record['status'] ?? '')) ?></em>
                </span>
                <span>
                    <?= htmlspecialchars((string) (($record['content'] ?? '') ?: '-')) ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'orders' ? 'is-active' : '' ?>" id="admin-section-orders" data-admin-section="orders" data-admin-primary="orders">
    <div class="section-title admin-section-title">
        <h2>订单管理</h2>
        <span class="muted">已支付 <?= $paidOrders ?> 笔 · 待支付 <?= $pendingOrders ?> 笔 · 已退款 <?= $refundedOrders ?> 笔</span>
    </div>
    <?php if (!empty($message)): ?><p class="notice order-message"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <form class="order-filter-bar" method="get" action="/jxdjadmin">
        <input type="hidden" name="admin_section" value="orders">
        <?php if ($orderFilters['payment_route_id'] !== ''): ?>
            <input type="hidden" name="payment_route_id" value="<?= htmlspecialchars($orderFilters['payment_route_id']) ?>">
        <?php endif; ?>
        <label>订单号
            <input name="order_no" value="<?= htmlspecialchars($orderFilters['order_no']) ?>" placeholder="输入订单号">
        </label>
        <label>用户查询
            <input name="user_keyword" value="<?= htmlspecialchars($orderFilters['user_keyword']) ?>" placeholder="用户ID / 手机号">
        </label>
        <label>推广码
            <input name="promotion_code" value="<?= htmlspecialchars($orderFilters['promotion_code']) ?>" placeholder="推广码">
        </label>
        <label>投放平台
            <input name="traffic_platform" value="<?= htmlspecialchars($orderFilters['traffic_platform']) ?>" placeholder="巨量 / 快手">
        </label>
        <label>渠道ID
            <input name="channel_id" value="<?= htmlspecialchars($orderFilters['channel_id']) ?>" placeholder="channel_id">
        </label>
        <label>应用ID
            <input name="media_app_id" value="<?= htmlspecialchars($orderFilters['media_app_id']) ?>" placeholder="app_id">
        </label>
        <label>广告ID
            <input name="ad_id" value="<?= htmlspecialchars($orderFilters['ad_id']) ?>" placeholder="ad_id">
        </label>
        <label>素材ID
            <input name="material_id" value="<?= htmlspecialchars($orderFilters['material_id']) ?>" placeholder="material_id">
        </label>
        <label>支付状态
            <select name="status">
                <?php foreach ($orderStatusOptions as $statusValue => $statusText): ?>
                    <option value="<?= htmlspecialchars($statusValue) ?>" <?= $orderFilters['status'] === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusText) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>每页显示
            <select name="per_page">
                <option value="10" <?= $orderFilters['per_page'] === 10 ? 'selected' : '' ?>>10 条</option>
                <option value="100" <?= $orderFilters['per_page'] === 100 ? 'selected' : '' ?>>100 条</option>
            </select>
        </label>
        <input type="hidden" name="page" value="1">
        <div class="order-filter-actions">
            <button class="btn primary" type="submit">查询</button>
            <a class="btn ghost" href="/jxdjadmin#orders">重置</a>
        </div>
    </form>
    <div class="filter-preset-panel">
        <form class="filter-preset-save" method="post" action="/jxdjadmin?admin_section=orders#orders">
            <input type="hidden" name="admin_action" value="save_filter_preset">
            <input type="hidden" name="admin_section" value="orders">
            <input type="hidden" name="preset_scope" value="orders">
            <?= $csrfField() ?>
            <?php foreach (['order_no', 'user_keyword', 'payment_route_id', 'promotion_code', 'traffic_platform', 'channel_id', 'media_app_id', 'ad_id', 'material_id', 'status', 'per_page'] as $orderFilterKey): ?>
                <input type="hidden" name="<?= htmlspecialchars($orderFilterKey) ?>" value="<?= htmlspecialchars((string) ($orderFilters[$orderFilterKey] ?? '')) ?>">
            <?php endforeach; ?>
            <input name="preset_name" placeholder="保存当前订单筛选">
            <label><span><input type="checkbox" name="preset_shared" value="1"> 共享</span></label>
            <button class="btn ghost" type="submit">保存方案</button>
        </form>
        <?php if (!empty($orderFilterPresets)): ?>
            <div class="filter-preset-list">
                <?php foreach ($orderFilterPresets as $preset): ?>
                    <?php $canDeletePreset = (int) ($preset['admin_id'] ?? 0) === $currentAdminId || (string) ($adminScope['role'] ?? '') === 'super_admin'; ?>
                    <div class="filter-preset-item">
                        <a href="<?= htmlspecialchars($orderFilterPresetUrl($preset)) ?>">
                            <strong><?= htmlspecialchars((string) ($preset['name'] ?? '订单筛选')) ?><?= !empty($preset['is_shared']) ? ' · 共享' : '' ?></strong>
                            <em class="muted"><?= htmlspecialchars($filterPresetSummary($preset, $orderFilterPresetLabels)) ?></em>
                        </a>
                        <?php if ($canDeletePreset): ?>
                            <form class="inline-form" method="post" action="/jxdjadmin?admin_section=orders#orders" onsubmit="return confirm('确认删除这个筛选方案吗？');">
                                <input type="hidden" name="admin_action" value="delete_filter_preset">
                                <input type="hidden" name="admin_section" value="orders">
                                <input type="hidden" name="preset_scope" value="orders">
                                <input type="hidden" name="preset_id" value="<?= (int) ($preset['id'] ?? 0) ?>">
                                <?= $csrfField() ?>
                                <button class="btn ghost" type="submit">删除</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="order-toolbar">
        <div>
            <strong>共 <?= number_format($orderTotalFiltered) ?> 条</strong>
            <span class="muted">当前第 <?= number_format($orderFilters['page']) ?> / <?= number_format($orderTotalPages) ?> 页，本页 <?= number_format(count($paginatedOrders)) ?> 条</span>
        </div>
        <form class="inline-form" method="post" action="/jxdjadmin">
            <input type="hidden" name="admin_action" value="export_orders_csv">
            <input type="hidden" name="admin_section" value="orders">
            <?= $csrfField() ?>
            <?php foreach (['order_no', 'user_keyword', 'payment_route_id', 'promotion_code', 'traffic_platform', 'channel_id', 'media_app_id', 'ad_id', 'material_id', 'status'] as $exportFilterKey): ?>
                <input type="hidden" name="<?= htmlspecialchars($exportFilterKey) ?>" value="<?= htmlspecialchars((string) ($orderFilters[$exportFilterKey] ?? '')) ?>">
            <?php endforeach; ?>
            <button class="btn ghost" type="submit" <?= $orderTotalFiltered <= 0 ? 'disabled' : '' ?>>导出当前筛选订单</button>
        </form>
        <form class="inline-form" method="post" action="/jxdjadmin?<?= htmlspecialchars($orderQueryParams()) ?>#orders" onsubmit="return confirm('确认批量查询当前页待支付订单吗？系统只会查询当前页待支付且已接入接口的订单。');">
            <input type="hidden" name="admin_action" value="bulk_query_order_payment">
                <?= $csrfField() ?>
            <input type="hidden" name="admin_section" value="orders">
            <?php foreach ($currentPageOrderNos as $orderNo): ?>
                <input type="hidden" name="order_nos[]" value="<?= htmlspecialchars($orderNo) ?>">
            <?php endforeach; ?>
            <button class="btn ghost" type="submit" <?= empty($currentPagePendingIntegratedOrders) ? 'disabled' : '' ?>>批量查询当前页待支付订单</button>
        </form>
        <form class="inline-form" method="post" action="/jxdjadmin?<?= htmlspecialchars($orderQueryParams()) ?>#orders" onsubmit="return confirm('确认批量手动回传当前页已支付订单吗？系统会跳过测试订单和未支付订单。');">
            <input type="hidden" name="admin_action" value="bulk_manual_callback_orders">
            <?= $csrfField() ?>
            <input type="hidden" name="admin_section" value="orders">
            <input type="hidden" name="send_now" value="1">
            <?php foreach ($currentPageCallbackOrderNos as $orderNo): ?>
                <input type="hidden" name="order_nos[]" value="<?= htmlspecialchars($orderNo) ?>">
            <?php endforeach; ?>
            <button class="btn ghost" type="submit" <?= empty($currentPageCallbackOrderNos) ? 'disabled' : '' ?>>批量回传当前页已支付订单</button>
        </form>
    </div>
    <?php if (empty($orders)): ?>
        <p class="muted">暂无订单</p>
    <?php elseif (empty($paginatedOrders)): ?>
        <p class="muted">没有符合筛选条件的订单。</p>
    <?php else: ?>
        <div class="order-table">
            <div class="order-row order-row-head">
                <span>订单信息</span>
                <span>金额</span>
                <span>状态</span>
                <span>操作</span>
            </div>
            <?php foreach ($paginatedOrders as $order): ?>
                <?php
                    $status = $orderStatusForView($order);
                    $paymentDisplay = $paymentDisplayForOrderView($order);
                    $paymentMethod = (string) ($paymentDisplay['method_name'] ?? '支付宝');
                    $paymentProviderName = (string) ($paymentDisplay['provider_name'] ?? '精秀聚合支付');
                    $paymentChannelName = (string) ($paymentDisplay['channel_name'] ?? '精秀主通道');
                    $paymentTradeType = (string) ($paymentDisplay['trade_type'] ?? 'alipayWap');
                    $isIntegratedPayment = $isIntegratedPaymentOrderView($order);
                    $isTestOrderView = !empty($order['is_test']) || ((string) ($order['type'] ?? '') === 'payment_test');
                    $orderAmount = (float) ($order['amount'] ?? 0);
                    $refundedTotal = (float) ($order['refund_total'] ?? $order['refund_amount'] ?? 0);
                    $remainingRefund = max(0, $orderAmount - $refundedTotal);
                    $refundRequests = array_values((array) ($order['refund_requests'] ?? []));
                    $pendingRefundRequest = null;
                    foreach (array_reverse($refundRequests) as $refundRequest) {
                        if (in_array((string) ($refundRequest['status'] ?? 'processing'), ['pending', 'processing'], true)) {
                            $pendingRefundRequest = $refundRequest;
                            break;
                        }
                    }
                    $hasPendingRefund = $pendingRefundRequest !== null;
                    $statusMeta = $orderStatusMeta($status);
                    $statusLabel = $hasPendingRefund ? '退款处理中' : $statusMeta['label'];
                    $statusClass = $hasPendingRefund ? 'ember' : $statusMeta['class'];
                    $canQuery = $isIntegratedPayment && in_array($status, ['pending', 'failed', 'closed', 'expired'], true);
                    $canRefund = $isIntegratedPayment && in_array($status, ['paid', 'partial_refunded'], true) && $remainingRefund > 0 && !$hasPendingRefund;
                    $canQueryRefund = $isIntegratedPayment && $hasPendingRefund;
                    $detailId = 'order-modal-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $order['order_no']);
                    $refundDialogId = 'refund-dialog-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $order['order_no']);
                    $orderUser = $userIndexForOrders[(int) ($order['user_id'] ?? 0)] ?? [];
                    $userPhone = trim((string) ($orderUser['phone'] ?? '')) ?: '未绑定';
                    $orderContentType = (string) ($order['content_type'] ?? 'drama');
                    $typeLabel = $isTestOrderView ? '通道测试' : match ((string) ($order['type'] ?? 'episode')) {
                        'membership', 'vip_week', 'vip_month' => '会员权益',
                        'drama_unlock' => '短剧全集',
                        'novel_unlock' => '小说整本',
                        'novel_chapter_unlock' => '小说章节',
                        default => '单集解锁',
                    };
                    $episodeLabel = $isTestOrderView ? '测试订单' : (!empty($order['episode_id']) ? '第 ' . (int) $order['episode_id'] . ' 集' : '整剧/会员');
                    $contentLabel = $orderContentType === 'novel' ? '小说' : '短剧';
                    $contentDetail = $orderContentType === 'novel'
                        ? ('小说 ' . (int) ($order['novel_id'] ?? 0) . (!empty($order['chapter_id']) ? ' · 章节 ' . (int) $order['chapter_id'] : ' · 整本/会员'))
                        : ('剧 ' . (int) ($order['drama_id'] ?? 0) . (!empty($order['episode_id']) ? ' · 第 ' . (int) $order['episode_id'] . ' 集' : ' · 整剧/会员'));
                    $gatewayTradeNo = (string) (($order['gateway_trade_no'] ?? '') ?: '暂无');
                    $gatewayPayUrl = (string) (($order['gateway_payment_url'] ?? '') ?: '暂无');
                    $paidAt = (string) (($order['paid_at'] ?? '') ?: '未支付');
                    $createdAt = (string) (($order['created_at'] ?? '') ?: '-');
                    $orderTrafficLines = $trafficMetaLines($order);
                    $latestGatewayAt = (string) (($order['gateway_last_at'] ?? $order['gateway_created_at'] ?? '') ?: '暂无');
                    $refundHistory = array_values((array) ($order['refund_history'] ?? []));
                    $gatewayLogs = array_values((array) ($order['gateway_logs'] ?? []));
                    $hasCreateLog = false;
                    $hasRefundLog = false;
                    foreach ($gatewayLogs as $gatewayLog) {
                        $hasCreateLog = $hasCreateLog || (($gatewayLog['scene'] ?? '') === 'create');
                        $hasRefundLog = $hasRefundLog || (($gatewayLog['scene'] ?? '') === 'refund');
                    }
                    if (!$hasCreateLog && (!empty($order['gateway_payment_url']) || !empty($order['gateway_trade_no']))) {
                        $gatewayLogs[] = [
                            'scene' => 'create',
                            'api_url' => '历史订单未记录请求地址',
                            'method' => 'POST',
                            'request_params' => $order['gateway_last_request_params'] ?? [],
                            'response_params' => [
                                'trade_no' => $order['gateway_trade_no'] ?? '',
                                'payurl' => $order['gateway_payment_url'] ?? '',
                                'payInfo' => $order['gateway_pay_info'] ?? '',
                            ],
                            'success' => true,
                            'error' => null,
                            'created_at' => $order['gateway_created_at'] ?? $order['created_at'] ?? '',
                        ];
                    }
                    if (!$hasRefundLog && !empty($order['refund_response'])) {
                        $gatewayLogs[] = [
                            'scene' => 'refund',
                            'api_url' => '历史订单未记录请求地址',
                            'method' => 'POST',
                            'request_params' => [],
                            'response_params' => $order['refund_response'],
                            'success' => true,
                            'error' => null,
                            'created_at' => $order['refunded_at'] ?? '',
                        ];
                    }
                ?>
                <div class="row-card order-row">
                    <div>
                        <strong><?= htmlspecialchars($order['order_no']) ?><?php if ($isTestOrderView): ?> <span class="pill ember">测试订单</span><?php endif; ?></strong>
                        <p class="muted">
                            <?php if ($isTestOrderView): ?>
                                管理员测试 · <?= htmlspecialchars((string) (($order['created_by_admin_name'] ?? '') ?: '管理员')) ?> · <?= htmlspecialchars($paymentMethod) ?> · <?= htmlspecialchars($paymentChannelName) ?>
                            <?php else: ?>
                                用户 <?= (int) $order['user_id'] ?> · <?= htmlspecialchars($contentDetail) ?> · <?= htmlspecialchars($paymentMethod) ?> · <?= htmlspecialchars($paymentChannelName) ?>
                            <?php endif; ?>
                        </p>
                        <?php if ($refundedTotal > 0): ?>
                            <p class="muted">已退 ￥<?= htmlspecialchars(number_format($refundedTotal, 2)) ?> · 剩余可退 ￥<?= htmlspecialchars(number_format($remainingRefund, 2)) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($orderTrafficLines)): ?>
                            <p class="muted"><?= htmlspecialchars(implode(' · ', array_slice($orderTrafficLines, 0, 3))) ?></p>
                        <?php endif; ?>
                    </div>
                    <div><strong>￥<?= htmlspecialchars(number_format($orderAmount, 2)) ?></strong></div>
                    <div><span class="pill <?= $statusClass ?>"><?= $statusLabel ?></span></div>
                    <div class="order-actions">
                        <button class="btn ghost order-view-btn" type="button" data-order-modal-open="<?= htmlspecialchars($detailId) ?>" data-order-no="<?= htmlspecialchars((string) $order['order_no']) ?>">查看</button>
                        <?php if (!$isTestOrderView && in_array($status, ['paid', 'partial_refunded', 'refunded'], true)): ?>
                            <form method="post" action="/jxdjadmin?<?= htmlspecialchars($orderQueryParams()) ?>#orders" class="inline-form" onsubmit="return confirm('确认对该订单执行手动回传吗？');">
                                <input type="hidden" name="admin_action" value="manual_callback_order">
                                <input type="hidden" name="admin_section" value="orders">
                                <input type="hidden" name="order_no" value="<?= htmlspecialchars((string) $order['order_no']) ?>">
                                <input type="hidden" name="send_now" value="1">
                                <?= $csrfField() ?>
                                <button class="btn ghost" type="submit">手动回传</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="order-modal" id="<?= htmlspecialchars($detailId) ?>" data-order-modal hidden>
                        <div class="order-modal-backdrop" data-order-modal-close></div>
                        <article class="order-modal-card" role="dialog" aria-modal="true" aria-labelledby="<?= htmlspecialchars($detailId) ?>-title">
                            <header class="order-modal-titlebar">
                                <h3 id="<?= htmlspecialchars($detailId) ?>-title"><?= $isTestOrderView ? '测试订单详情' : '订单详情' ?></h3>
                                <button type="button" class="order-modal-close" data-order-modal-close aria-label="关闭订单详情">×</button>
                            </header>
                            <div class="order-modal-hero">
                                <div>
                                    <strong>￥<?= htmlspecialchars(number_format($orderAmount, 2)) ?></strong>
                                    <span class="pill <?= $statusClass ?>"><?= $statusLabel ?></span>
                                    <?php if ($isTestOrderView): ?><span class="pill ember">测试订单</span><?php endif; ?>
                                </div>
                                <div>
                                    <span>平台订单号</span>
                                    <b><?= htmlspecialchars((string) $order['order_no']) ?></b>
                                </div>
                                <div>
                                    <span>渠道订单号</span>
                                    <b><?= htmlspecialchars($gatewayTradeNo) ?></b>
                                </div>
                            </div>
                            <nav class="order-modal-tabs" aria-label="订单详情页签">
                                <button class="is-active" type="button" data-order-tab-target="base">基本信息</button>
                                <button type="button" data-order-tab-target="logs">接口日志</button>
                                <button type="button" data-order-tab-target="refund">退款信息</button>
                                <button type="button" data-order-tab-target="extra">扩展信息</button>
                            </nav>
                            <div class="order-modal-body">
                                <section class="order-tab-panel is-active" data-order-tab-panel="base">
                                    <div class="order-info-card">
                                        <h4>订单信息</h4>
                                        <div class="order-info-grid">
                                            <div><span>订单号</span><strong><?= htmlspecialchars((string) $order['order_no']) ?></strong></div>
                                            <div><span>用户 ID</span><strong><?= (int) ($order['user_id'] ?? 0) ?></strong></div>
                                            <div><span>用户手机号</span><strong><?= htmlspecialchars($userPhone) ?></strong></div>
                                            <div><span>商品类型</span><strong><?= htmlspecialchars($typeLabel) ?></strong></div>
                                            <div><span><?= htmlspecialchars($contentLabel) ?>内容</span><strong><?= htmlspecialchars($contentDetail) ?></strong></div>
                                            <?php if ($isTestOrderView): ?>
                                                <div><span>测试标题</span><strong><?= htmlspecialchars((string) (($order['test_subject'] ?? '') ?: '支付通道测试')) ?></strong></div>
                                                <div><span>创建管理员</span><strong><?= htmlspecialchars((string) (($order['created_by_admin_name'] ?? '') ?: '管理员')) ?></strong></div>
                                            <?php endif; ?>
                                            <div><span>支付方式</span><strong><?= htmlspecialchars($paymentMethod) ?></strong></div>
                                            <div><span>支付服务商</span><strong><?= htmlspecialchars($paymentProviderName) ?></strong></div>
                                            <div><span>支付通道</span><strong><?= htmlspecialchars($paymentChannelName) ?></strong></div>
                                            <div><span>接口交易类型</span><strong><?= htmlspecialchars($paymentTradeType) ?></strong></div>
                                            <div><span>支付渠道订单号</span><strong><?= htmlspecialchars($gatewayTradeNo) ?></strong></div>
                                            <div><span>支付链接 payurl</span><strong><?= htmlspecialchars($gatewayPayUrl) ?></strong></div>
                                            <div><span>下单时间</span><strong><?= htmlspecialchars($createdAt) ?></strong></div>
                                            <div><span>支付时间</span><strong><?= htmlspecialchars($paidAt) ?></strong></div>
                                            <div><span>投放平台</span><strong><?= htmlspecialchars((string) (($order['traffic_platform'] ?? '') ?: '未归因')) ?></strong></div>
                                            <div><span>渠道ID</span><strong><?= htmlspecialchars((string) (($order['channel_id'] ?? '') ?: '未记录')) ?></strong></div>
                                            <div><span>应用ID</span><strong><?= htmlspecialchars((string) (($order['media_app_id'] ?? '') ?: '未记录')) ?></strong></div>
                                            <div><span>广告ID</span><strong><?= htmlspecialchars((string) (($order['ad_id'] ?? '') ?: '未记录')) ?></strong></div>
                                            <div><span>创意ID</span><strong><?= htmlspecialchars((string) (($order['creative_id'] ?? '') ?: '未记录')) ?></strong></div>
                                            <div><span>素材ID</span><strong><?= htmlspecialchars((string) (($order['material_id'] ?? '') ?: '未记录')) ?></strong></div>
                                        </div>
                                    </div>
                                </section>
                                <section class="order-tab-panel" data-order-tab-panel="logs">
                                    <?php if (empty($gatewayLogs)): ?>
                                        <p class="muted">暂无支付接口请求/返回记录。新订单下单、查询支付状态或退款后会自动记录。</p>
                                    <?php else: ?>
                                        <div class="gateway-log-list">
                                            <?php foreach (array_reverse($gatewayLogs) as $gatewayLog): ?>
                                                <?php
                                                    $scene = (string) ($gatewayLog['scene'] ?? 'create');
                                                    $sceneLabel = $gatewaySceneLabels[$scene] ?? '接口调用';
                                                    $success = $gatewayLog['success'] ?? null;
                                                    $successText = $success === null ? '未标记' : ($success ? '成功' : '失败');
                                                ?>
                                                <article class="gateway-log-card">
                                                    <div class="gateway-log-head">
                                                        <div>
                                                            <span class="eyebrow"><?= htmlspecialchars($sceneLabel) ?></span>
                                                            <strong><?= htmlspecialchars((string) ($gatewayLog['api_url'] ?? '')) ?></strong>
                                                        </div>
                                                        <div class="gateway-log-meta">
                                                            <span><?= htmlspecialchars((string) ($gatewayLog['method'] ?? 'POST')) ?></span>
                                                            <span><?= htmlspecialchars($successText) ?></span>
                                                            <span><?= htmlspecialchars((string) ($gatewayLog['created_at'] ?? '')) ?></span>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($gatewayLog['error'])): ?>
                                                        <p class="notice warn">接口错误：<?= htmlspecialchars((string) $gatewayLog['error']) ?></p>
                                                    <?php endif; ?>
                                                    <div class="gateway-log-grid">
                                                        <div class="gateway-log-payload">
                                                            <span>请求参数</span>
                                                            <pre><?= htmlspecialchars($formatGatewayPayload($gatewayLog['request_params'] ?? [])) ?></pre>
                                                        </div>
                                                        <div class="gateway-log-payload">
                                                            <span>返回参数</span>
                                                            <pre><?= htmlspecialchars($formatGatewayPayload($gatewayLog['response_params'] ?? null)) ?></pre>
                                                        </div>
                                                    </div>
                                                </article>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </section>
                                <section class="order-tab-panel" data-order-tab-panel="refund">
                                    <div class="order-info-card">
                                        <h4>退款信息</h4>
                                        <div class="order-info-grid">
                                            <div><span>退款状态</span><strong><?= $hasPendingRefund ? '退款处理中' : (in_array($status, ['refunded', 'partial_refunded'], true) ? htmlspecialchars($statusLabel) : '未退款') ?></strong></div>
                                            <div><span>已退金额</span><strong>￥<?= htmlspecialchars(number_format($refundedTotal, 2)) ?></strong></div>
                                            <div><span>剩余可退</span><strong>￥<?= htmlspecialchars(number_format($remainingRefund, 2)) ?></strong></div>
                                            <div><span>最近退款单号</span><strong><?= htmlspecialchars((string) (($pendingRefundRequest['refund_no'] ?? $order['refund_no'] ?? '') ?: '暂无')) ?></strong></div>
                                        </div>
                                        <?php if (empty($refundRequests) && empty($refundHistory)): ?>
                                            <p class="muted">暂无退款历史。发起退款后会先显示为处理中，查询退款状态成功后才会更新为退款成功。</p>
                                        <?php else: ?>
                                            <div class="refund-history-list">
                                                <?php foreach (array_reverse($refundRequests) as $refundItem): ?>
                                                    <?php
                                                        $refundStatus = (string) ($refundItem['status'] ?? 'processing');
                                                        $refundStatusLabel = $refundStatusLabels[$refundStatus] ?? $refundStatus;
                                                        $refundStatusClass = $refundStatus === 'success' ? 'jade' : ($refundStatus === 'failed' ? 'ember' : '');
                                                        $refundMessage = trim((string) ($refundItem['message'] ?? ''));
                                                        $gatewayRefundNoText = trim((string) ($refundItem['gateway_refund_no'] ?? ''));
                                                    ?>
                                                    <div>
                                                        <strong><?= htmlspecialchars((string) ($refundItem['refund_no'] ?? '-')) ?></strong>
                                                        <span class="pill <?= htmlspecialchars($refundStatusClass) ?>"><?= htmlspecialchars($refundStatusLabel) ?></span>
                                                        <span>￥<?= htmlspecialchars(number_format((float) ($refundItem['amount'] ?? 0), 2)) ?> · 申请 <?= htmlspecialchars((string) ($refundItem['created_at'] ?? '-')) ?></span>
                                                        <?php if ($gatewayRefundNoText !== ''): ?>
                                                            <span>通道退款单号 <?= htmlspecialchars($gatewayRefundNoText) ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($refundMessage !== ''): ?>
                                                            <span>原因 <?= htmlspecialchars($refundMessage) ?></span>
                                                        <?php elseif (in_array($refundStatus, ['pending', 'processing'], true)): ?>
                                                            <span>下一步：点击底部“查询退款状态”确认是否到账</span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($refundItem['updated_at'])): ?>
                                                            <span>最近更新 <?= htmlspecialchars((string) $refundItem['updated_at']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php foreach (array_reverse($refundHistory) as $refundItem): ?>
                                                    <?php
                                                        $historyRefundNo = (string) ($refundItem['refund_no'] ?? '-');
                                                        $alreadyListed = false;
                                                        foreach ($refundRequests as $requestItem) {
                                                            if ((string) ($requestItem['refund_no'] ?? '') === $historyRefundNo) {
                                                                $alreadyListed = true;
                                                                break;
                                                            }
                                                        }
                                                        if ($alreadyListed) {
                                                            continue;
                                                        }
                                                    ?>
                                                    <div>
                                                        <strong><?= htmlspecialchars($historyRefundNo) ?></strong>
                                                        <span class="pill jade">退款成功</span>
                                                        <span>￥<?= htmlspecialchars(number_format((float) ($refundItem['amount'] ?? 0), 2)) ?> · <?= htmlspecialchars((string) ($refundItem['created_at'] ?? '-')) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </section>
                                <section class="order-tab-panel" data-order-tab-panel="extra">
                                    <div class="order-info-card">
                                        <h4>扩展信息</h4>
                                        <div class="order-info-grid">
                                            <div><span>订单状态</span><strong><?= htmlspecialchars($statusLabel) ?></strong></div>
                                            <div><span>权益类型</span><strong><?= htmlspecialchars($typeLabel) ?></strong></div>
                                            <div><span>订单属性</span><strong><?= $isTestOrderView ? '测试订单，不计正式收入/权益' : '正式业务订单' ?></strong></div>
                                            <div><span>付款方式</span><strong><?= htmlspecialchars($paymentMethod) ?></strong></div>
                                            <div><span>服务商/通道</span><strong><?= htmlspecialchars($paymentProviderName) ?> · <?= htmlspecialchars($paymentChannelName) ?></strong></div>
                                            <div><span>最近接口时间</span><strong><?= htmlspecialchars($latestGatewayAt) ?></strong></div>
                                            <div><span>订单 ID</span><strong><?= (int) ($order['id'] ?? 0) ?></strong></div>
                                            <div><span>接口最近结果</span><strong><?= array_key_exists('gateway_last_success', $order) ? (!empty($order['gateway_last_success']) ? '成功' : '失败') : '暂无' ?></strong></div>
                                            <div><span>推广链接ID</span><strong><?= (int) ($order['promotion_link_id'] ?? 0) ?></strong></div>
                                            <div><span>推广码</span><strong><?= htmlspecialchars((string) (($order['promotion_code'] ?? '') ?: '未归因')) ?></strong></div>
                                            <div><span>来源/计划</span><strong><?= htmlspecialchars(trim((string) (($order['traffic_source'] ?? '') . ' ' . ($order['campaign'] ?? ''))) ?: '未记录') ?></strong></div>
                                        </div>
                                    </div>
                                </section>
                            </div>
                            <footer class="order-modal-actions">
                                <?php if ($canQuery): ?>
                                    <form class="inline-form" method="post" action="/jxdjadmin?<?= htmlspecialchars($orderQueryParams()) ?>#orders">
                                        <input type="hidden" name="admin_action" value="query_order_payment">
                <?= $csrfField() ?>
                                        <input type="hidden" name="order_no" value="<?= htmlspecialchars($order['order_no']) ?>">
                                        <button class="btn ghost" type="submit"><?= $isTestOrderView ? '查询测试支付状态' : '查询支付状态' ?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if (!in_array($status, ['paid', 'partial_refunded', 'refunded'], true)): ?>
                                    <form class="inline-form" method="post" action="/?route=pay-callback">
                                        <input type="hidden" name="admin_action" value="repair_callback">
                <?= $csrfField() ?>
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) ($csrf_token ?? '')) ?>">
                                        <input type="hidden" name="order_no" value="<?= htmlspecialchars($order['order_no']) ?>">
                                        <button class="btn" type="submit"><?= $isTestOrderView ? '模拟测试成功' : '补单' ?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($canQueryRefund): ?>
                                    <form class="inline-form" method="post" action="/jxdjadmin?<?= htmlspecialchars($orderQueryParams()) ?>#orders">
                                        <input type="hidden" name="admin_action" value="query_refund_status">
                <?= $csrfField() ?>
                                        <input type="hidden" name="order_no" value="<?= htmlspecialchars($order['order_no']) ?>">
                                        <input type="hidden" name="refund_no" value="<?= htmlspecialchars((string) ($pendingRefundRequest['refund_no'] ?? '')) ?>">
                                        <button class="btn ghost" type="submit">查询退款状态</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($canRefund): ?>
                                    <button class="btn danger" type="button" data-refund-dialog-open="<?= htmlspecialchars($refundDialogId) ?>">退款</button>
                                    <span class="refund-note">可退 ￥<?= htmlspecialchars(number_format($remainingRefund, 2)) ?></span>
                                <?php elseif ($hasPendingRefund): ?>
                                    <span class="muted">退款处理中，查询成功后才会更新订单状态。</span>
                                <?php endif; ?>
                                <?php if (!$isIntegratedPayment): ?>
                                    <span class="muted">该支付通道暂未接入查询/退款操作。</span>
                                <?php elseif (!$canQuery && !$canRefund && !$canQueryRefund && in_array($status, ['paid', 'partial_refunded', 'refunded'], true)): ?>
                                    <span class="muted">当前状态暂无可执行操作。</span>
                                <?php endif; ?>
                            </footer>
                        </article>
                        <?php if ($canRefund): ?>
                            <div class="refund-dialog" id="<?= htmlspecialchars($refundDialogId) ?>" data-refund-dialog hidden>
                                <div class="refund-dialog-backdrop" data-refund-dialog-close></div>
                                <article class="refund-dialog-card" role="dialog" aria-modal="true" aria-labelledby="<?= htmlspecialchars($refundDialogId) ?>-title">
                                    <button type="button" class="refund-dialog-close" data-refund-dialog-close aria-label="关闭退款弹窗">×</button>
                                    <span class="eyebrow">退款申请</span>
                                    <h3 id="<?= htmlspecialchars($refundDialogId) ?>-title">输入退款金额</h3>
                                    <p class="muted">提交后只会向<?= htmlspecialchars($paymentChannelName) ?>发起退款申请，订单会保持原状态；需要后续点击“查询退款状态”确认成功。</p>
                                    <div class="refund-dialog-summary">
                                        <div>
                                            <span>订单号</span>
                                            <strong><?= htmlspecialchars((string) $order['order_no']) ?></strong>
                                        </div>
                                        <div>
                                            <span>剩余可退</span>
                                            <strong>￥<?= htmlspecialchars(number_format($remainingRefund, 2)) ?></strong>
                                        </div>
                                    </div>
                                    <form class="refund-dialog-form" method="post" action="/jxdjadmin?<?= htmlspecialchars($orderQueryParams()) ?>#orders" data-refund-submit-form>
                                        <input type="hidden" name="admin_action" value="refund_order">
                <?= $csrfField() ?>
                                        <input type="hidden" name="order_no" value="<?= htmlspecialchars($order['order_no']) ?>">
                                        <label>退款金额
                                            <input name="refund_amount" type="number" min="0.01" max="<?= htmlspecialchars(number_format($remainingRefund, 2, '.', '')) ?>" step="0.01" value="<?= htmlspecialchars(number_format($remainingRefund, 2, '.', '')) ?>" aria-label="退款金额">
                                        </label>
                                        <p class="refund-dialog-status" data-refund-dialog-status hidden></p>
                                        <div class="refund-dialog-actions">
                                            <button class="btn ghost" type="button" data-refund-dialog-close>取消</button>
                                            <button class="btn danger" type="submit">提交退款申请</button>
                                        </div>
                                    </form>
                                </article>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <nav class="order-pagination" aria-label="订单分页">
            <a class="btn ghost <?= $orderFilters['page'] <= 1 ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($orderPageUrl(1)) ?>">首页</a>
            <a class="btn ghost <?= $orderFilters['page'] <= 1 ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($orderPageUrl(max(1, $orderFilters['page'] - 1))) ?>">上一页</a>
            <span>第 <?= number_format($orderFilters['page']) ?> / <?= number_format($orderTotalPages) ?> 页</span>
            <a class="btn ghost <?= $orderFilters['page'] >= $orderTotalPages ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($orderPageUrl(min($orderTotalPages, $orderFilters['page'] + 1))) ?>">下一页</a>
            <a class="btn ghost <?= $orderFilters['page'] >= $orderTotalPages ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($orderPageUrl($orderTotalPages)) ?>">末页</a>
        </nav>
    <?php endif; ?>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'play-stats' ? 'is-active' : '' ?>" id="admin-section-play-stats" data-admin-section="play-stats" data-admin-primary="stats">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">播放阅读</span>
            <h2>播放统计</h2>
        </div>
        <span class="muted">短剧播放和小说阅读统一统计浏览、观看进度、锁定曝光与解锁。</span>
    </div>
    <?php $renderAnalyticsFilterBar('play-stats', '短剧播放和小说阅读统一统计浏览、观看进度、锁定曝光与解锁。'); ?>
    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('stats') ?></span>
            <small>浏览/播放</small>
            <strong><?= number_format((int) ($playStatsSummary['views'] ?? 0) + (int) ($playStatsSummary['watch_records'] ?? 0)) ?></strong>
            <em>事件浏览 <?= number_format((int) ($playStatsSummary['views'] ?? 0)) ?> · 观看记录 <?= number_format((int) ($playStatsSummary['watch_records'] ?? 0)) ?></em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('user') ?></span>
            <small>观看用户</small>
            <strong><?= number_format((int) ($playStatsSummary['watch_users'] ?? 0)) ?></strong>
            <em>平均进度 <?= number_format((float) ($playStatsSummary['avg_progress'] ?? 0), 2) ?>% · 完播 <?= number_format((int) ($playStatsSummary['completion_records'] ?? 0)) ?></em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('payment') ?></span>
            <small>锁定/解锁</small>
            <strong><?= number_format((int) ($playStatsSummary['lock_exposures'] ?? 0)) ?> / <?= number_format((int) ($playStatsSummary['unlock_success'] ?? 0)) ?></strong>
            <em>解锁率 <?= number_format((float) ($playStatsSummary['unlock_rate'] ?? 0), 2) ?>% · 下单 <?= number_format((int) ($playStatsSummary['order_created'] ?? 0)) ?></em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('drama') ?></span>
            <small>内容覆盖</small>
            <strong><?= number_format((int) ($playStatsSummary['content_count'] ?? 0)) ?></strong>
            <em>历史播放 <?= number_format((int) ($playStatsSummary['legacy_views'] ?? 0)) ?> · 活跃天 <?= number_format((int) ($playStatsSummary['active_days'] ?? 0)) ?></em>
        </div>
    </div>

    <div class="repair-grid">
        <div class="order-info-card">
            <h4>每日播放阅读</h4>
            <?php if (empty($playStatsDailyRows)): ?>
                <p class="muted">当前筛选范围内暂无播放阅读趋势。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach (array_slice($playStatsDailyRows, 0, 12) as $row): ?>
                        <div>
                            <strong><?= htmlspecialchars((string) ($row['date'] ?? '')) ?></strong>
                            <span class="pill blue"><?= number_format((int) ($row['views'] ?? 0) + (int) ($row['watch_records'] ?? 0)) ?> 次</span>
                            <em>浏览 <?= number_format((int) ($row['views'] ?? 0)) ?> · 观看 <?= number_format((int) ($row['watch_records'] ?? 0)) ?> · 用户 <?= number_format((int) ($row['watch_user_count'] ?? 0)) ?></em>
                            <em>锁定 <?= number_format((int) ($row['lock_exposures'] ?? 0)) ?> · 下单 <?= number_format((int) ($row['order_created'] ?? 0)) ?> · 解锁 <?= number_format((int) ($row['unlock_success'] ?? 0)) ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="order-info-card">
            <h4>播放阅读口径</h4>
            <div class="repair-log-list">
                <div>
                    <strong>短剧播放</strong>
                    <span class="pill green"><?= number_format((int) ($playStatsSummary['drama_views'] ?? 0)) ?></span>
                    <em>由内容浏览事件和短剧观看历史共同统计。</em>
                </div>
                <div>
                    <strong>小说阅读</strong>
                    <span class="pill blue"><?= number_format((int) ($playStatsSummary['novel_views'] ?? 0)) ?></span>
                    <em>由小说阅读页上报的内容事件统计。</em>
                </div>
                <div>
                    <strong>历史兜底</strong>
                    <span class="pill orange"><?= number_format((int) ($playStatsSummary['legacy_views'] ?? 0)) ?></span>
                    <em>来自短剧基础浏览量，用于旧数据没有事件日志时展示热度。</em>
                </div>
            </div>
        </div>
    </div>

    <div class="order-table">
        <div class="row-card order-row-head">
            <span>内容</span>
            <span>播放/浏览</span>
            <span>进度/完播</span>
            <span>锁定/解锁</span>
        </div>
        <?php if (empty($playStatsRows)): ?>
            <p class="muted">暂无内容播放阅读数据。</p>
        <?php endif; ?>
        <?php foreach ($playStatsRows as $row): ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($row['title'] ?? '未命名内容')) ?></strong>
                    <em><?= htmlspecialchars((string) ($row['content_label'] ?? '内容')) ?> · <?= number_format((int) ($row['unit_count'] ?? 0)) ?><?= htmlspecialchars((string) ($row['unit_label'] ?? '集')) ?></em>
                </span>
                <span>
                    浏览 <?= number_format((int) ($row['views'] ?? 0)) ?> · 观看 <?= number_format((int) ($row['watch_records'] ?? 0)) ?>
                    <em>历史热度 <?= number_format((int) ($row['legacy_views'] ?? 0)) ?> · 热度分 <?= number_format((int) ($row['hot_score'] ?? 0)) ?></em>
                </span>
                <span>
                    用户 <?= number_format((int) ($row['watch_user_count'] ?? 0)) ?> · 均进度 <?= number_format((float) ($row['avg_progress'] ?? 0), 2) ?>%
                    <em>完播 <?= number_format((int) ($row['completion_records'] ?? 0)) ?></em>
                </span>
                <span>
                    锁定 <?= number_format((int) ($row['lock_exposures'] ?? 0)) ?> · 解锁 <?= number_format((int) ($row['unlock_success'] ?? 0)) ?>
                    <em>下单 <?= number_format((int) ($row['order_created'] ?? 0)) ?> · 解锁率 <?= number_format((float) ($row['unlock_rate'] ?? 0), 2) ?>%</em>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="section-title">
        <div>
            <span class="eyebrow">分集章节</span>
            <h2>播放明细</h2>
        </div>
        <span class="muted">按短剧分集和小说章节观察卡点、锁点与解锁。</span>
    </div>
    <div class="order-table">
        <div class="row-card order-row-head">
            <span>分集/章节</span>
            <span>浏览/观看</span>
            <span>进度/完播</span>
            <span>锁定/转化</span>
        </div>
        <?php if (empty($playStatsUnitRows)): ?>
            <p class="muted">暂无分集或章节明细。</p>
        <?php endif; ?>
        <?php foreach (array_slice($playStatsUnitRows, 0, 30) as $row): ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($row['title'] ?? '未命名单元')) ?></strong>
                    <em><?= htmlspecialchars((string) ($row['content_label'] ?? '内容')) ?> #<?= number_format((int) ($row['content_id'] ?? 0)) ?> · <?= htmlspecialchars((string) ($row['unit_label'] ?? '集')) ?> ID <?= number_format((int) ($row['unit_id'] ?? 0)) ?></em>
                </span>
                <span>
                    浏览 <?= number_format((int) ($row['views'] ?? 0)) ?> · 观看 <?= number_format((int) ($row['watch_records'] ?? 0)) ?>
                    <em>用户 <?= number_format((int) ($row['watch_user_count'] ?? 0)) ?></em>
                </span>
                <span>
                    均进度 <?= number_format((float) ($row['avg_progress'] ?? 0), 2) ?>%
                    <em>完播 <?= number_format((int) ($row['completion_records'] ?? 0)) ?></em>
                </span>
                <span>
                    锁定 <?= number_format((int) ($row['lock_exposures'] ?? 0)) ?> · 解锁 <?= number_format((int) ($row['unlock_success'] ?? 0)) ?>
                    <em>下单 <?= number_format((int) ($row['order_created'] ?? 0)) ?> · 解锁率 <?= number_format((float) ($row['unlock_rate'] ?? 0), 2) ?>%</em>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row-card stack">
        <p><strong>口径说明</strong></p>
        <p class="muted">播放统计优先使用内容事件中的浏览、锁定曝光、下单和解锁；短剧观看进度来自观看历史，进度不低于 90% 计为完播；旧短剧基础浏览量只作为历史热度兜底。该页沿用上方统计筛选和后台角色数据范围。</p>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'recharge-hourly' ? 'is-active' : '' ?>" id="admin-section-recharge-hourly" data-admin-section="recharge-hourly" data-admin-primary="stats">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">充值时空</span>
            <h2>充值时段</h2>
        </div>
        <span class="muted">按支付小时聚合充值订单、净收入、退款和支付方式。</span>
    </div>
    <?php $renderAnalyticsFilterBar('recharge-hourly', '按支付小时聚合充值订单、净收入、退款和支付方式。'); ?>
    <?php
        $peakRevenueHour = (array) ($rechargeHourlySummary['peak_revenue_hour'] ?? []);
        $peakOrderHour = (array) ($rechargeHourlySummary['peak_order_hour'] ?? []);
    ?>
    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('revenue') ?></span>
            <small>净收入</small>
            <strong><?= htmlspecialchars($money((float) ($rechargeHourlySummary['net_amount'] ?? 0))) ?></strong>
            <em>成交 <?= htmlspecialchars($money((float) ($rechargeHourlySummary['gross_amount'] ?? 0))) ?> · 退款 <?= htmlspecialchars($money((float) ($rechargeHourlySummary['refund_amount'] ?? 0))) ?></em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('orders') ?></span>
            <small>充值订单</small>
            <strong><?= number_format((int) ($rechargeHourlySummary['orders'] ?? 0)) ?></strong>
            <em>均单 <?= htmlspecialchars($money((float) ($rechargeHourlySummary['avg_order_amount'] ?? 0))) ?></em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('user') ?></span>
            <small>付费用户</small>
            <strong><?= number_format((int) ($rechargeHourlySummary['paid_users'] ?? 0)) ?></strong>
            <em>退款率 <?= number_format((float) ($rechargeHourlySummary['refund_rate'] ?? 0), 2) ?>%</em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('stats') ?></span>
            <small>峰值小时</small>
            <strong><?= htmlspecialchars((string) (($peakRevenueHour['label'] ?? '') ?: '-')) ?></strong>
            <em>订单峰值 <?= htmlspecialchars((string) (($peakOrderHour['label'] ?? '') ?: '-')) ?></em>
        </div>
    </div>

    <div class="repair-grid">
        <div class="order-info-card">
            <h4>24 小时充值分布</h4>
            <div class="order-table">
                <div class="row-card order-row-head">
                    <span>小时</span>
                    <span>净收入</span>
                    <span>订单/用户</span>
                    <span>退款/均单</span>
                </div>
                <?php foreach ($rechargeHourlyRows as $row): ?>
                    <?php if ((int) ($row['orders'] ?? 0) <= 0 && (float) ($row['net_amount'] ?? 0) <= 0): ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <div class="row-card order-row">
                        <span>
                            <strong><?= htmlspecialchars((string) ($row['label'] ?? '')) ?></strong>
                            <em>第 <?= number_format((int) ($row['hour'] ?? 0)) ?> 小时</em>
                        </span>
                        <span>
                            <?= htmlspecialchars($money((float) ($row['net_amount'] ?? 0))) ?>
                            <em>成交 <?= htmlspecialchars($money((float) ($row['gross_amount'] ?? 0))) ?></em>
                        </span>
                        <span>
                            订单 <?= number_format((int) ($row['orders'] ?? 0)) ?>
                            <em>付费用户 <?= number_format((int) ($row['paid_user_count'] ?? 0)) ?></em>
                        </span>
                        <span>
                            退款 <?= htmlspecialchars($money((float) ($row['refund_amount'] ?? 0))) ?>
                            <em>退款率 <?= number_format((float) ($row['refund_rate'] ?? 0), 2) ?>% · 均单 <?= htmlspecialchars($money((float) ($row['avg_order_amount'] ?? 0))) ?></em>
                        </span>
                    </div>
                <?php endforeach; ?>
                <?php if ((int) ($rechargeHourlySummary['orders'] ?? 0) <= 0): ?>
                    <p class="muted">当前筛选范围内暂无已支付充值订单。</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="order-info-card">
            <h4>支付方式分布</h4>
            <?php if (empty($rechargeHourlyMethodRows)): ?>
                <p class="muted">暂无支付方式数据。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach ($rechargeHourlyMethodRows as $row): ?>
                        <div>
                            <strong><?= htmlspecialchars((string) ($row['method_name'] ?? $row['method'] ?? '支付方式')) ?></strong>
                            <span class="pill blue"><?= number_format((int) ($row['orders'] ?? 0)) ?> 单</span>
                            <em>净收入 <?= htmlspecialchars($money((float) ($row['net_amount'] ?? 0))) ?> · 付费用户 <?= number_format((int) ($row['paid_user_count'] ?? 0)) ?></em>
                            <em>成交 <?= htmlspecialchars($money((float) ($row['gross_amount'] ?? 0))) ?> · 退款 <?= htmlspecialchars($money((float) ($row['refund_amount'] ?? 0))) ?> · 均单 <?= htmlspecialchars($money((float) ($row['avg_order_amount'] ?? 0))) ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row-card stack">
        <p><strong>口径说明</strong></p>
        <p class="muted">按订单支付时间所在小时统计已支付、部分退款和已退款订单；净收入为订单金额扣除退款。该页沿用上方统计筛选和后台角色数据范围，只影响统计看板。</p>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'payment-success' ? 'is-active' : '' ?>" id="admin-section-payment-success" data-admin-section="payment-success" data-admin-primary="stats">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">支付质量</span>
            <h2>支付成功率</h2>
        </div>
        <span class="muted">按订单状态、支付通道和支付方式拆分成功率、失败率和退款率。</span>
    </div>
    <?php $renderAnalyticsFilterBar('payment-success', '按订单状态、支付通道和支付方式拆分成功率、失败率和退款率。'); ?>
    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('orders') ?></span>
            <small>创建订单</small>
            <strong><?= number_format((int) ($paymentSuccessSummary['orders'] ?? 0)) ?></strong>
            <em>待支付 <?= number_format((int) ($paymentSuccessSummary['pending_orders'] ?? 0)) ?> · 失败/关闭 <?= number_format((int) ($paymentSuccessSummary['failed_orders'] ?? 0)) ?></em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('payment') ?></span>
            <small>支付成功率</small>
            <strong><?= number_format((float) ($paymentSuccessSummary['success_rate'] ?? 0), 2) ?>%</strong>
            <em>成功 <?= number_format((int) ($paymentSuccessSummary['success_orders'] ?? 0)) ?> 单</em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('withdraw') ?></span>
            <small>退款率</small>
            <strong><?= number_format((float) ($paymentSuccessSummary['refund_rate'] ?? 0), 2) ?>%</strong>
            <em>退款相关 <?= number_format((int) ($paymentSuccessSummary['refund_orders'] ?? 0)) ?> 单</em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('revenue') ?></span>
            <small>净收入</small>
            <strong><?= htmlspecialchars($money((float) ($paymentSuccessSummary['net_amount'] ?? 0))) ?></strong>
            <em>成交 <?= htmlspecialchars($money((float) ($paymentSuccessSummary['gross_amount'] ?? 0))) ?> · 退款 <?= htmlspecialchars($money((float) ($paymentSuccessSummary['refund_amount'] ?? 0))) ?></em>
        </div>
    </div>

    <div class="repair-grid">
        <div class="order-info-card">
            <h4>订单状态分布</h4>
            <?php if (empty($paymentSuccessStatusRows)): ?>
                <p class="muted">当前筛选范围内暂无订单。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach ($paymentSuccessStatusRows as $row): ?>
                        <div>
                            <strong><?= htmlspecialchars((string) ($row['name'] ?? $row['key'] ?? '订单状态')) ?></strong>
                            <span class="pill <?= (float) ($row['success_rate'] ?? 0) >= 70 ? 'green' : ((int) ($row['failed_orders'] ?? 0) > 0 ? 'ember' : 'blue') ?>"><?= number_format((int) ($row['orders'] ?? 0)) ?> 单</span>
                            <em>成功 <?= number_format((int) ($row['success_orders'] ?? 0)) ?> · 待确认 <?= number_format((int) ($row['pending_orders'] ?? 0)) ?> · 失败/关闭 <?= number_format((int) ($row['failed_orders'] ?? 0)) ?></em>
                            <em>净收入 <?= htmlspecialchars($money((float) ($row['net_amount'] ?? 0))) ?> · 退款 <?= htmlspecialchars($money((float) ($row['refund_amount'] ?? 0))) ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="order-info-card">
            <h4>支付方式成功率</h4>
            <?php if (empty($paymentSuccessMethodRows)): ?>
                <p class="muted">暂无支付方式数据。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach ($paymentSuccessMethodRows as $row): ?>
                        <div>
                            <strong><?= htmlspecialchars((string) ($row['name'] ?? $row['key'] ?? '支付方式')) ?></strong>
                            <span class="pill <?= (float) ($row['success_rate'] ?? 0) >= 70 ? 'green' : ((float) ($row['failure_rate'] ?? 0) >= 20 ? 'ember' : 'blue') ?>"><?= number_format((float) ($row['success_rate'] ?? 0), 2) ?>%</span>
                            <em>总单 <?= number_format((int) ($row['orders'] ?? 0)) ?> · 成功 <?= number_format((int) ($row['success_orders'] ?? 0)) ?> · 失败/关闭 <?= number_format((int) ($row['failed_orders'] ?? 0)) ?></em>
                            <em>净收入 <?= htmlspecialchars($money((float) ($row['net_amount'] ?? 0))) ?> · 退款率 <?= number_format((float) ($row['refund_rate'] ?? 0), 2) ?>%</em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="order-table">
        <div class="row-card order-row-head">
            <span>支付通道</span>
            <span>成功率</span>
            <span>订单状态</span>
            <span>成交/退款</span>
        </div>
        <?php if (empty($paymentSuccessRouteRows)): ?>
            <p class="muted">当前筛选范围内暂无支付通道数据。</p>
        <?php endif; ?>
        <?php foreach ($paymentSuccessRouteRows as $row): ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($row['name'] ?? '支付通道')) ?></strong>
                    <em><?= htmlspecialchars((string) ($row['key'] ?? '')) ?></em>
                </span>
                <span>
                    <?= number_format((float) ($row['success_rate'] ?? 0), 2) ?>%
                    <em>失败率 <?= number_format((float) ($row['failure_rate'] ?? 0), 2) ?>%</em>
                </span>
                <span>
                    总单 <?= number_format((int) ($row['orders'] ?? 0)) ?> · 成功 <?= number_format((int) ($row['success_orders'] ?? 0)) ?>
                    <em>待确认 <?= number_format((int) ($row['pending_orders'] ?? 0)) ?> · 失败/关闭 <?= number_format((int) ($row['failed_orders'] ?? 0)) ?></em>
                </span>
                <span>
                    <?= htmlspecialchars($money((float) ($row['gross_amount'] ?? 0))) ?>
                    <em>退款 <?= htmlspecialchars($money((float) ($row['refund_amount'] ?? 0))) ?> · 净收 <?= htmlspecialchars($money((float) ($row['net_amount'] ?? 0))) ?></em>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row-card stack">
        <p><strong>口径说明</strong></p>
        <p class="muted">支付成功率 = 已支付、部分退款、已退款订单 / 当前筛选范围内的非测试订单；失败率统计失败、关闭和过期订单；退款率按成功订单中退款相关订单占比计算。</p>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'content-conversion' ? 'is-active' : '' ?>" id="admin-section-content-conversion" data-admin-section="content-conversion" data-admin-primary="stats">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">内容漏斗</span>
            <h2>内容转化</h2>
        </div>
        <span class="muted">短剧和小说统一统计浏览、锁定曝光、下单、解锁和收入。</span>
    </div>
    <?php $renderAnalyticsFilterBar('content-conversion', '短剧和小说统一统计浏览、锁定曝光、下单、解锁和收入。'); ?>
    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('drama') ?></span>
            <small>内容数</small>
            <strong><?= number_format((int) ($contentConversionSummary['content_count'] ?? 0)) ?></strong>
            <em>短剧 + 小说</em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('stats') ?></span>
            <small>阅读/播放</small>
            <strong><?= number_format((int) ($contentConversionSummary['views'] ?? 0)) ?></strong>
            <em>锁定曝光 <?= number_format((int) ($contentConversionSummary['lock_exposures'] ?? 0)) ?></em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('orders') ?></span>
            <small>下单/解锁</small>
            <strong><?= number_format((int) ($contentConversionSummary['orders'] ?? 0)) ?> / <?= number_format((int) ($contentConversionSummary['unlocks'] ?? 0)) ?></strong>
            <em>订单创建 / 成功解锁</em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('revenue') ?></span>
            <small>内容收入</small>
            <strong><?= htmlspecialchars($money((float) ($contentConversionSummary['revenue'] ?? 0))) ?></strong>
            <em>投放归因 <?= htmlspecialchars($money((float) ($contentConversionSummary['promotion_revenue'] ?? 0))) ?></em>
        </div>
    </div>

    <div class="order-table">
        <div class="row-card order-row-head">
            <span>内容</span>
            <span>浏览/锁定</span>
            <span>下单/解锁</span>
            <span>收入/转化</span>
        </div>
        <?php if (empty($contentConversionRows)): ?>
            <p class="muted">暂无内容转化数据。用户进入短剧预览或小说阅读页后会自动记录浏览和锁定曝光。</p>
        <?php endif; ?>
        <?php foreach ($contentConversionRows as $row): ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($row['title'] ?? '未命名内容')) ?></strong>
                    <em><?= htmlspecialchars((string) ($row['content_label'] ?? '内容')) ?> · <?= number_format((int) ($row['unit_count'] ?? 0)) ?><?= htmlspecialchars((string) ($row['unit_label'] ?? '集')) ?> · <?= htmlspecialchars((string) ($row['status'] ?? 'draft')) ?></em>
                </span>
                <span>
                    浏览 <?= number_format((int) ($row['views'] ?? 0)) ?>
                    <em>锁定曝光 <?= number_format((int) ($row['lock_exposures'] ?? 0)) ?> · 锁定率 <?= number_format((float) ($row['lock_rate'] ?? 0), 2) ?>%</em>
                </span>
                <span>
                    下单 <?= number_format((int) ($row['orders'] ?? 0)) ?> · 解锁 <?= number_format((int) ($row['unlocks'] ?? 0)) ?>
                    <em>下单率 <?= number_format((float) ($row['order_rate'] ?? 0), 2) ?>% · 解锁率 <?= number_format((float) ($row['unlock_rate'] ?? 0), 2) ?>%</em>
                </span>
                <span>
                    <?= htmlspecialchars($money((float) ($row['revenue'] ?? 0))) ?>
                    <em>付费用户 <?= number_format((int) ($row['paid_user_count'] ?? 0)) ?> · ARPPU <?= htmlspecialchars($money((float) ($row['arppu'] ?? 0))) ?></em>
                    <em>投放归因 <?= htmlspecialchars($money((float) ($row['promotion_revenue'] ?? 0))) ?></em>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'user-growth' ? 'is-active' : '' ?>" id="admin-section-user-growth" data-admin-section="user-growth" data-admin-primary="stats">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">用户归因</span>
            <h2>用户增长</h2>
        </div>
        <span class="muted">按日期和来源统计新增、注册、加桌、激活、付费与成本。</span>
    </div>
    <?php $renderAnalyticsFilterBar('user-growth', '按日期和来源统计新增、注册、加桌、激活、付费与成本。'); ?>
    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('user') ?></span>
            <small>新增用户</small>
            <strong><?= number_format((int) ($userGrowthSummary['new_users'] ?? 0)) ?></strong>
            <em>访问 <?= number_format((int) ($userGrowthSummary['visits'] ?? 0)) ?> · 注册事件 <?= number_format((int) ($userGrowthSummary['registers'] ?? 0)) ?></em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('stats') ?></span>
            <small>加桌/激活</small>
            <strong><?= number_format((int) ($userGrowthSummary['add_desktop'] ?? 0)) ?> / <?= number_format((int) ($userGrowthSummary['activations'] ?? 0)) ?></strong>
            <em>加桌率 <?= number_format((float) ($userGrowthSummary['add_desktop_rate'] ?? 0), 2) ?>% · 激活率 <?= number_format((float) ($userGrowthSummary['activation_rate'] ?? 0), 2) ?>%</em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('orders') ?></span>
            <small>付费转化</small>
            <strong><?= number_format((int) ($userGrowthSummary['paid_users'] ?? 0)) ?> 人</strong>
            <em>订单 <?= number_format((int) ($userGrowthSummary['paid_orders'] ?? 0)) ?> · 付费率 <?= number_format((float) ($userGrowthSummary['pay_rate'] ?? 0), 2) ?>%</em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('profit') ?></span>
            <small>收入/成本</small>
            <strong><?= htmlspecialchars($money((float) ($userGrowthSummary['revenue'] ?? 0))) ?></strong>
            <em>成本 <?= htmlspecialchars($money((float) ($userGrowthSummary['cost'] ?? 0))) ?> · 回本 <?= ($userGrowthSummary['recovery_rate'] ?? null) === null ? '-' : number_format((float) $userGrowthSummary['recovery_rate'], 2) . '%' ?></em>
        </div>
    </div>

    <div class="order-table">
        <div class="row-card order-row-head">
            <span>日期</span>
            <span>新增/访问</span>
            <span>注册/加桌/激活</span>
            <span>付费/收入/成本</span>
        </div>
        <?php if (empty($userGrowthRows)): ?>
            <p class="muted">当前筛选范围内暂无用户增长数据。</p>
        <?php endif; ?>
        <?php foreach ($userGrowthRows as $row): ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($row['date'] ?? '')) ?></strong>
                    <em>自然日口径</em>
                </span>
                <span>
                    新增 <?= number_format((int) ($row['new_users'] ?? 0)) ?> · 访问 <?= number_format((int) ($row['visits'] ?? 0)) ?>
                    <em>新增成本 <?= ($row['new_user_cost'] ?? null) === null ? '-' : htmlspecialchars($money((float) $row['new_user_cost'])) ?></em>
                </span>
                <span>
                    注册 <?= number_format((int) ($row['registers'] ?? 0)) ?> · 加桌 <?= number_format((int) ($row['add_desktop'] ?? 0)) ?> · 激活 <?= number_format((int) ($row['activations'] ?? 0)) ?>
                    <em>注册率 <?= number_format((float) ($row['register_rate'] ?? 0), 2) ?>% · 加桌率 <?= number_format((float) ($row['add_desktop_rate'] ?? 0), 2) ?>%</em>
                </span>
                <span>
                    付费 <?= number_format((int) ($row['paid_user_count'] ?? 0)) ?> 人 / <?= number_format((int) ($row['paid_orders'] ?? 0)) ?> 单
                    <em>收入 <?= htmlspecialchars($money((float) ($row['revenue'] ?? 0))) ?> · 成本 <?= htmlspecialchars($money((float) ($row['cost'] ?? 0))) ?></em>
                    <em>付费率 <?= number_format((float) ($row['pay_rate'] ?? 0), 2) ?>% · 回本 <?= ($row['recovery_rate'] ?? null) === null ? '-' : number_format((float) $row['recovery_rate'], 2) . '%' ?></em>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="section-title">
        <div>
            <span class="eyebrow">来源拆分</span>
            <h2>增长来源</h2>
        </div>
        <span class="muted">按应用、平台、渠道、广告、素材和推广入口聚合。</span>
    </div>
    <div class="order-table">
        <div class="row-card order-row-head">
            <span>来源</span>
            <span>新增/注册</span>
            <span>加桌/激活</span>
            <span>付费/成本</span>
        </div>
        <?php if (empty($userGrowthSourceRows)): ?>
            <p class="muted">暂无可拆分的来源数据。</p>
        <?php endif; ?>
        <?php foreach ($userGrowthSourceRows as $row): ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($row['name'] ?? '自然/未归因')) ?></strong>
                    <em>应用 <?= htmlspecialchars((string) (($row['app_key'] ?? '') ?: '-')) ?> · 平台 <?= htmlspecialchars((string) (($row['traffic_platform'] ?? '') ?: '-')) ?> · 渠道 <?= htmlspecialchars((string) (($row['channel_id'] ?? '') ?: '-')) ?></em>
                    <em>广告 <?= htmlspecialchars((string) (($row['ad_id'] ?? '') ?: '-')) ?> · 素材 <?= htmlspecialchars((string) (($row['material_id'] ?? '') ?: '-')) ?> · 推广 <?= htmlspecialchars((string) (($row['code'] ?? '') ?: ($row['promotion_link_id'] ?? '-'))) ?></em>
                </span>
                <span>
                    新增 <?= number_format((int) ($row['new_users'] ?? 0)) ?> · 注册 <?= number_format((int) ($row['registers'] ?? 0)) ?>
                    <em>访问 <?= number_format((int) ($row['visits'] ?? 0)) ?> · 注册率 <?= number_format((float) ($row['register_rate'] ?? 0), 2) ?>%</em>
                </span>
                <span>
                    加桌 <?= number_format((int) ($row['add_desktop'] ?? 0)) ?> · 激活 <?= number_format((int) ($row['activations'] ?? 0)) ?>
                    <em>加桌率 <?= number_format((float) ($row['add_desktop_rate'] ?? 0), 2) ?>% · 激活率 <?= number_format((float) ($row['activation_rate'] ?? 0), 2) ?>%</em>
                </span>
                <span>
                    付费 <?= number_format((int) ($row['paid_user_count'] ?? 0)) ?> 人 / <?= number_format((int) ($row['paid_orders'] ?? 0)) ?> 单
                    <em>收入 <?= htmlspecialchars($money((float) ($row['revenue'] ?? 0))) ?> · 成本 <?= htmlspecialchars($money((float) ($row['cost'] ?? 0))) ?> · 回本 <?= ($row['recovery_rate'] ?? null) === null ? '-' : number_format((float) $row['recovery_rate'], 2) . '%' ?></em>
                    <em>新增成本 <?= ($row['new_user_cost'] ?? null) === null ? '-' : htmlspecialchars($money((float) $row['new_user_cost'])) ?> · 加桌成本 <?= ($row['add_desktop_cost'] ?? null) === null ? '-' : htmlspecialchars($money((float) $row['add_desktop_cost'])) ?></em>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row-card stack">
        <p><strong>口径说明</strong></p>
        <p class="muted">新增用户按用户创建时间统计；访问、注册、加桌、激活来自推广事件；付费和收入按已支付、部分退款、已退款订单净额统计；来源拆分优先使用用户首个推广事件归因，成本来自投放消耗。该页沿用统计筛选与后台角色数据范围。</p>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'operation-alerts' ? 'is-active' : '' ?>" id="admin-section-operation-alerts" data-admin-section="operation-alerts" data-admin-primary="stats">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">投放诊断</span>
            <h2>投放异常</h2>
        </div>
        <span class="muted">自动聚合失败回传、低转化素材、高退款素材、低回收推广和自动停投入口。</span>
    </div>
    <?php $renderAnalyticsFilterBar('operation-alerts', '自动聚合失败回传、低转化素材、高退款素材、低回收推广和自动停投入口。'); ?>
    <div class="kpi-grid">
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('withdraw') ?></span>
            <small>异常总数</small>
            <strong><?= number_format((int) ($operationAlertSummary['total'] ?? 0)) ?></strong>
            <em>当前角色可见范围</em>
        </div>
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('setting') ?></span>
            <small>回传异常</small>
            <strong><?= number_format((int) ($operationAlertSummary['callback_failed'] ?? 0)) ?></strong>
            <em>聚合 <?= number_format((int) ($operationAlertSummary['callback_group_failed'] ?? 0)) ?> 组</em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('stats') ?></span>
            <small>低转化素材</small>
            <strong><?= number_format((int) ($operationAlertSummary['low_conversion'] ?? 0)) ?></strong>
            <em>访问≥<?= number_format((int) ($operationAlertThresholds['low_conversion_min_visits'] ?? 10)) ?> 且转化&lt;<?= number_format((float) ($operationAlertThresholds['low_conversion_rate'] ?? 1), 2) ?>%</em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('profit') ?></span>
            <small>止损风险</small>
            <strong><?= number_format((int) ($operationAlertSummary['high_refund'] ?? 0) + (int) ($operationAlertSummary['low_recovery'] ?? 0) + (int) ($operationAlertSummary['auto_paused'] ?? 0)) ?></strong>
            <em>退款高、回本低或已停投</em>
        </div>
    </div>

    <div class="repair-grid">
        <div class="order-info-card">
            <h4>自动停投入口</h4>
            <?php if (empty($autoPausedAlertRows)): ?>
                <p class="muted">暂无自动停投入口。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach (array_slice($autoPausedAlertRows, 0, 8) as $alert): ?>
                        <div>
                            <strong><?= htmlspecialchars((string) ($alert['title'] ?? '自动停投推广')) ?></strong>
                            <span class="pill ember">已停投</span>
                            <em><?= htmlspecialchars((string) ($alert['message'] ?? '')) ?></em>
                            <em>消耗 <?= htmlspecialchars($money((float) ($alert['cost'] ?? 0))) ?> · 净回收 <?= htmlspecialchars($money((float) ($alert['net_amount'] ?? 0))) ?> · 回本 <?= ($alert['recovery_rate'] ?? null) === null ? '-' : number_format((float) $alert['recovery_rate'], 2) . '%' ?></em>
                            <em>预算 <?= (float) ($alert['cost_budget_limit'] ?? 0) > 0 ? htmlspecialchars($money((float) $alert['cost_budget_limit'])) : '不限' ?> · 保护线 <?= (float) ($alert['min_recovery_rate'] ?? 0) > 0 ? number_format((float) $alert['min_recovery_rate'], 2) . '%' : '-' ?> · <?= htmlspecialchars((string) ($alert['auto_paused_at'] ?? '')) ?></em>
                            <em>广告 <?= htmlspecialchars((string) (($alert['ad_id'] ?? '') ?: '-')) ?> · 素材 <?= htmlspecialchars((string) (($alert['material_id'] ?? '') ?: '-')) ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="order-info-card">
            <h4>回传失败聚合</h4>
            <?php if (empty($callbackGroupAlertRows)): ?>
                <p class="muted">暂无达到阈值的聚合回传失败。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach (array_slice($callbackGroupAlertRows, 0, 8) as $alert): ?>
                        <?php $badCallbackCount = (int) ($alert['failed_logs'] ?? 0) + (int) ($alert['retry_disabled_logs'] ?? 0); ?>
                        <div>
                            <strong><?= htmlspecialchars((string) ($alert['title'] ?? '回传失败聚合')) ?></strong>
                            <span class="pill <?= (string) ($alert['level'] ?? '') === 'high' ? 'ember' : 'blue' ?>"><?= number_format((float) ($alert['failure_rate'] ?? 0), 2) ?>%</span>
                            <em><?= htmlspecialchars((string) ($alert['message'] ?? '')) ?></em>
                            <em>平台 <?= htmlspecialchars((string) (($alert['traffic_platform'] ?? '') ?: '-')) ?> · 应用 <?= htmlspecialchars((string) (($alert['media_app_id'] ?? '') ?: '-')) ?> · 渠道 <?= htmlspecialchars((string) (($alert['channel_id'] ?? '') ?: '-')) ?></em>
                            <em>广告 <?= htmlspecialchars((string) (($alert['ad_id'] ?? '') ?: '-')) ?> · 素材 <?= htmlspecialchars((string) (($alert['material_id'] ?? '') ?: '-')) ?> · 推广码 <?= htmlspecialchars((string) (($alert['code'] ?? '') ?: '-')) ?></em>
                            <em>异常 <?= number_format($badCallbackCount) ?> / 总计 <?= number_format((int) ($alert['total_logs'] ?? 0)) ?> · 影响 <?= htmlspecialchars($money((float) ($alert['failed_amount'] ?? 0))) ?> · 最近 <?= htmlspecialchars((string) (($alert['last_failed_at'] ?? '') ?: ($alert['last_attempt_at'] ?? ''))) ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="order-info-card">
            <h4>回传异常</h4>
            <?php if (empty($callbackAlertRows)): ?>
                <p class="muted">暂无失败回传或不可重试记录。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach (array_slice($callbackAlertRows, 0, 8) as $alert): ?>
                        <div>
                            <strong><?= htmlspecialchars((string) ($alert['title'] ?? '回传异常')) ?></strong>
                            <span class="pill <?= (string) ($alert['level'] ?? '') === 'high' ? 'ember' : 'blue' ?>"><?= htmlspecialchars((string) (($alert['level'] ?? '') === 'high' ? '高优先级' : '需关注')) ?></span>
                            <em><?= htmlspecialchars((string) ($alert['message'] ?? '')) ?></em>
                            <em>推广码 <?= htmlspecialchars((string) (($alert['code'] ?? '') ?: '-')) ?> · 广告 <?= htmlspecialchars((string) (($alert['ad_id'] ?? '') ?: '-')) ?> · 素材 <?= htmlspecialchars((string) (($alert['material_id'] ?? '') ?: '-')) ?></em>
                            <em>尝试 <?= number_format((int) ($alert['attempt_count'] ?? 0)) ?> 次 · <?= htmlspecialchars((string) (($alert['last_attempt_at'] ?? '') ?: ($alert['created_at'] ?? ''))) ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="order-info-card">
            <h4>低转化素材</h4>
            <?php if (empty($lowConversionAlertRows)): ?>
                <p class="muted">暂无达到阈值的低转化素材。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach (array_slice($lowConversionAlertRows, 0, 8) as $alert): ?>
                        <div>
                            <strong><?= htmlspecialchars((string) ($alert['title'] ?? '低转化素材')) ?></strong>
                            <span class="pill <?= (string) ($alert['level'] ?? '') === 'high' ? 'ember' : 'blue' ?>"><?= number_format((float) ($alert['conversion_rate'] ?? 0), 2) ?>%</span>
                            <em><?= htmlspecialchars((string) ($alert['message'] ?? '')) ?></em>
                            <em>平台 <?= htmlspecialchars((string) (($alert['traffic_platform'] ?? '') ?: '-')) ?> · 广告 <?= htmlspecialchars((string) (($alert['ad_id'] ?? '') ?: '-')) ?> · 素材 <?= htmlspecialchars((string) (($alert['material_id'] ?? '') ?: '-')) ?></em>
                            <em>消耗 <?= htmlspecialchars($money((float) ($alert['cost'] ?? 0))) ?> · 净回收 <?= htmlspecialchars($money((float) ($alert['net_amount'] ?? 0))) ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="repair-grid">
        <div class="order-info-card">
            <h4>高退款素材</h4>
            <?php if (empty($highRefundAlertRows)): ?>
                <p class="muted">暂无高退款素材。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach (array_slice($highRefundAlertRows, 0, 8) as $alert): ?>
                        <div>
                            <strong><?= htmlspecialchars((string) ($alert['title'] ?? '高退款素材')) ?></strong>
                            <span class="pill ember"><?= number_format((float) ($alert['refund_rate'] ?? 0), 2) ?>%</span>
                            <em><?= htmlspecialchars((string) ($alert['message'] ?? '')) ?></em>
                            <em>退款 <?= htmlspecialchars($money((float) ($alert['refund_amount'] ?? 0))) ?> / 成交 <?= htmlspecialchars($money((float) ($alert['amount'] ?? 0))) ?></em>
                            <em>广告 <?= htmlspecialchars((string) (($alert['ad_id'] ?? '') ?: '-')) ?> · 素材 <?= htmlspecialchars((string) (($alert['material_id'] ?? '') ?: '-')) ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="order-info-card">
            <h4>低回收推广</h4>
            <?php if (empty($lowRecoveryAlertRows)): ?>
                <p class="muted">暂无低回收推广链接。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach (array_slice($lowRecoveryAlertRows, 0, 8) as $alert): ?>
                        <div>
                            <strong><?= htmlspecialchars((string) ($alert['title'] ?? '低回收推广')) ?></strong>
                            <span class="pill <?= (string) ($alert['level'] ?? '') === 'high' ? 'ember' : 'blue' ?>"><?= number_format((float) ($alert['recovery_rate'] ?? 0), 2) ?>%</span>
                            <em><?= htmlspecialchars((string) ($alert['message'] ?? '')) ?></em>
                            <em>付费 <?= number_format((int) ($alert['paid_orders'] ?? 0)) ?> · 代理 <?= htmlspecialchars((string) (($alert['agent_name'] ?? '') ?: '-')) ?> · 商务 <?= htmlspecialchars((string) (($alert['business_name'] ?? '') ?: '-')) ?></em>
                            <em>广告 <?= htmlspecialchars((string) (($alert['ad_id'] ?? '') ?: '-')) ?> · 素材 <?= htmlspecialchars((string) (($alert['material_id'] ?? '') ?: '-')) ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row-card stack">
        <p><strong>监控口径</strong></p>
        <p class="muted">低转化按素材/广告/推广组合统计访问与已支付订单；高退款按已支付订单里的退款订单占比统计；低回收按推广链接消耗和净收入统计；自动停投来自推广入口的预算/回本保护触发记录。所有数据沿用当前后台账号的数据范围裁剪。</p>
    </div>

    <div class="section-title">
        <div>
            <span class="eyebrow">接口模板</span>
            <h2>平台停投适配器</h2>
        </div>
        <span class="muted">已配置 <?= number_format(count($promotionStopAdapterConfigs)) ?> 个 · 启用 <?= number_format(count(array_filter($promotionStopAdapterConfigs, static fn (array $item): bool => (string) ($item['status'] ?? 'paused') === 'active'))) ?> 个</span>
    </div>
    <?php if ((string) ($adminScope['role'] ?? '') === 'super_admin'): ?>
        <div class="payment-rule-grid">
            <?php foreach ($promotionStopAdapterPresets as $presetKey => $preset): ?>
                <?php $preset = (array) $preset; ?>
                <form method="post" action="/jxdjadmin#operation-alerts" class="system-item">
                    <input type="hidden" name="admin_action" value="save_promotion_stop_adapter_config">
                    <input type="hidden" name="admin_section" value="operation-alerts">
                    <input type="hidden" name="preset_key" value="<?= htmlspecialchars((string) $presetKey) ?>">
                    <input type="hidden" name="status" value="active">
                    <?= $csrfField() ?>
                    <strong><?= htmlspecialchars((string) ($preset['name'] ?? $presetKey)) ?></strong>
                    <span><?= htmlspecialchars((string) ($preset['summary'] ?? '')) ?></span>
                    <span>别名 <?= htmlspecialchars(implode(' / ', array_map('strval', (array) ($preset['provider_aliases'] ?? [])))) ?></span>
                    <span><?= htmlspecialchars($callbackMappingSummary((array) ($preset['field_mapping'] ?? []))) ?></span>
                    <input name="endpoint" value="<?= htmlspecialchars((string) ($preset['endpoint_placeholder'] ?? 'mock://success')) ?>" placeholder="停投接口地址">
                    <button class="btn ghost" type="submit">启用模板</button>
                </form>
            <?php endforeach; ?>
        </div>
        <form method="post" action="/jxdjadmin#operation-alerts" class="row-card stack">
            <input type="hidden" name="admin_action" value="save_promotion_stop_adapter_config">
            <input type="hidden" name="admin_section" value="operation-alerts">
            <?= $csrfField() ?>
            <p><strong>新增停投适配器</strong></p>
            <div class="form-grid">
                <label>适配器编码<input name="adapter_key" placeholder="例如 stop_juliang_custom"></label>
                <label>名称<input name="name" placeholder="例如 巨量停投正式接口"></label>
                <label>状态
                    <select name="status">
                        <option value="active">启用</option>
                        <option value="paused">停用</option>
                    </select>
                </label>
                <label>默认动作
                    <select name="default_stop_action">
                        <?php foreach ($promotionStopTaskActionLabels as $actionKey => $actionText): ?>
                            <option value="<?= htmlspecialchars($actionKey) ?>"><?= htmlspecialchars($actionText) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>接口地址<input name="endpoint" placeholder="mock://success 或 https://..."></label>
                <label>查询接口<input name="query_endpoint" placeholder="mock://query-success 或 https://..."></label>
                <label>鉴权方式
                    <select name="auth_mode">
                        <?php foreach ($callbackAuthModeLabels as $authMode => $authLabel): ?>
                            <option value="<?= htmlspecialchars($authMode) ?>"><?= htmlspecialchars($authLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>密钥<input name="secret" placeholder="可选"></label>
                <label>Token<input name="auth_token" placeholder="Bearer Token 可选"></label>
                <label>签名 Header<input name="auth_header_name" value="X-JX-Signature"></label>
                <label>Token Header<input name="auth_token_header_name" value="Authorization"></label>
            </div>
            <label>平台别名<textarea name="provider_aliases_text" rows="2" placeholder="每行或逗号分隔，例如：巨量, 巨量引擎, 抖音"></textarea></label>
            <label>字段映射 JSON<textarea name="field_mapping_text" rows="4" placeholder='{"ad_id":"ad_id","reason":"reason","task_no":"task_no"}'></textarea></label>
            <label>查询字段映射 JSON<textarea name="query_field_mapping_text" rows="3" placeholder='{"request_id":"platform_request_id","outer_code":"promotion_code"}'></textarea></label>
            <label>授权账号 JSON<textarea name="account_profiles_text" rows="5" placeholder='[{"account_key":"app_a","name":"A应用账号","match_media_app_ids":["APP_A"],"endpoint":"mock://success","query_endpoint":"mock://query-success","auth_config":{"mode":"bearer","token":"TOKEN"},"token_expires_at":"2026-12-31 23:59:59","refresh_endpoint":"mock://refresh-success","refresh_token":"REFRESH_TOKEN","refresh_before_minutes":30}]'></textarea></label>
            <div class="form-grid">
                <label>成功字段<input name="response_success_path" placeholder="例如 code 或 success"></label>
                <label>成功值<input name="response_success_values_text" value="0, true, success, ok" placeholder="多个值用逗号分隔"></label>
                <label>处理中值<input name="response_processing_values_text" value="processing, pending, running, accepted, queued"></label>
                <label>失败值<input name="response_failed_values_text" value="failed, fail, error, rejected, cancelled"></label>
                <label>平台单号字段<input name="response_request_id_path" value="request_id"></label>
                <label>平台状态字段<input name="response_status_path" value="status"></label>
                <label>平台编码字段<input name="response_code_path" value="code"></label>
                <label>平台消息字段<input name="response_message_path" value="message"></label>
            </div>
            <label>备注<input name="remark" placeholder="接口用途或平台账号范围"></label>
            <p><button class="btn primary" type="submit">保存适配器</button></p>
        </form>
    <?php endif; ?>
    <div class="order-table">
        <div class="row-card order-row-head">
            <span>适配器</span>
            <span>匹配平台</span>
            <span>接口/映射</span>
            <span>维护</span>
        </div>
        <?php if (empty($promotionStopAdapterConfigs)): ?>
            <p class="muted">暂无停投适配器。可先启用一个模板，用 mock 地址联调。</p>
        <?php endif; ?>
        <?php foreach ($promotionStopAdapterConfigs as $adapter): ?>
            <?php
                $adapterStatus = (string) ($adapter['status'] ?? 'paused');
                $adapterAuth = (array) ($adapter['auth_config'] ?? []);
                $adapterResponse = (array) ($adapter['response_config'] ?? []);
                $adapterMappingText = json_encode((array) ($adapter['field_mapping'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}';
                $adapterQueryMappingText = json_encode((array) ($adapter['query_field_mapping'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}';
                $adapterAccountProfiles = array_values((array) ($adapter['account_profiles'] ?? []));
                $adapterAccountProfilesText = json_encode($adapterAccountProfiles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '[]';
                $adapterExpiredAccounts = count(array_filter($adapterAccountProfiles, static fn (array $profile): bool => trim((string) ($profile['token_expires_at'] ?? '')) !== '' && strtotime((string) $profile['token_expires_at']) !== false && (int) strtotime((string) $profile['token_expires_at']) <= time()));
                $adapterExpiringAccounts = count(array_filter($adapterAccountProfiles, static function (array $profile): bool {
                    $expiresAt = trim((string) ($profile['token_expires_at'] ?? ''));
                    if ($expiresAt === '' || strtotime($expiresAt) === false) {
                        return false;
                    }
                    $refreshBefore = max(0, min(1440, (int) ($profile['refresh_before_minutes'] ?? 30))) * 60;
                    $expiresTs = (int) strtotime($expiresAt);
                    return $expiresTs > time() && $expiresTs <= time() + $refreshBefore;
                }));
                $adapterTestStatus = (string) ($adapter['last_test_status'] ?? '');
                $adapterTestLabel = ['success' => '试连成功', 'failed' => '试连失败', 'skipped' => '未试连'][(string) $adapterTestStatus] ?? '未试连';
                $adapterTestClass = ['success' => 'green', 'failed' => 'ember', 'skipped' => 'orange'][(string) $adapterTestStatus] ?? 'blue';
            ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($adapter['name'] ?? '停投适配器')) ?></strong>
                    <em><?= htmlspecialchars((string) ($adapter['adapter_key'] ?? '')) ?></em>
                    <span class="pill <?= $adapterStatus === 'active' ? 'green' : 'orange' ?>"><?= $adapterStatus === 'active' ? '启用' : '停用' ?></span>
                </span>
                <span>
                    <?= htmlspecialchars(implode(' / ', array_map('strval', (array) ($adapter['provider_aliases'] ?? []))) ?: '-') ?>
                    <em>默认动作 <?= htmlspecialchars($promotionStopTaskActionLabels[(string) ($adapter['default_stop_action'] ?? '')] ?? (string) ($adapter['default_stop_action'] ?? '-')) ?></em>
                </span>
                <span>
                    <?= htmlspecialchars((string) (($adapter['endpoint'] ?? '') ?: '-')) ?>
                    <em><?= htmlspecialchars($callbackAuthModeLabels[(string) ($adapterAuth['mode'] ?? 'none')] ?? (string) ($adapterAuth['mode'] ?? 'none')) ?> · <?= htmlspecialchars($callbackMappingSummary((array) ($adapter['field_mapping'] ?? []))) ?></em>
                    <em>查询 <?= htmlspecialchars((string) (($adapter['query_endpoint'] ?? '') ?: '-')) ?> · <?= htmlspecialchars($callbackMappingSummary((array) ($adapter['query_field_mapping'] ?? []))) ?></em>
                    <em>授权账号 <?= number_format(count($adapterAccountProfiles)) ?> 个<?= !empty($adapterAccountProfiles) ? ' · ' . htmlspecialchars(implode(' / ', array_map(static fn (array $profile): string => (string) (($profile['name'] ?? '') ?: ($profile['account_key'] ?? '授权账号')), array_slice($adapterAccountProfiles, 0, 3)))) : '' ?><?= $adapterExpiredAccounts > 0 ? ' · 过期 ' . number_format($adapterExpiredAccounts) : '' ?><?= $adapterExpiringAccounts > 0 ? ' · 将过期 ' . number_format($adapterExpiringAccounts) : '' ?></em>
                    <em>响应 <?= htmlspecialchars((string) (($adapterResponse['success_path'] ?? '') ?: 'HTTP')) ?><?= !empty($adapterResponse['success_path']) ? '=' . htmlspecialchars(implode('/', array_map('strval', (array) ($adapterResponse['success_values'] ?? [])))) : '' ?> · 单号 <?= htmlspecialchars((string) (($adapterResponse['request_id_path'] ?? '') ?: '-')) ?></em>
                    <em><?= htmlspecialchars((string) ($adapter['remark'] ?? '')) ?></em>
                    <em>
                        <span class="pill <?= htmlspecialchars($adapterTestClass) ?>"><?= htmlspecialchars($adapterTestLabel) ?></span>
                        <?= htmlspecialchars((string) (($adapter['last_test_at'] ?? '') ?: '')) ?>
                        <?= htmlspecialchars((string) (($adapter['last_test_message'] ?? '') ?: '')) ?>
                    </em>
                </span>
                <span>
                    <?php if ((string) ($adminScope['role'] ?? '') === 'super_admin'): ?>
                        <?php foreach (array_slice($adapterAccountProfiles, 0, 5) as $accountProfile): ?>
                            <?php
                                $accountExpiresAt = trim((string) ($accountProfile['token_expires_at'] ?? ''));
                                $accountStatusText = '未设到期';
                                $accountStatusClass = 'blue';
                                if ($accountExpiresAt !== '' && strtotime($accountExpiresAt) !== false) {
                                    $accountRefreshBefore = max(0, min(1440, (int) ($accountProfile['refresh_before_minutes'] ?? 30))) * 60;
                                    $accountExpiresTs = (int) strtotime($accountExpiresAt);
                                    if ($accountExpiresTs <= time()) {
                                        $accountStatusText = '已过期';
                                        $accountStatusClass = 'ember';
                                    } elseif ($accountExpiresTs <= time() + $accountRefreshBefore) {
                                        $accountStatusText = '将过期';
                                        $accountStatusClass = 'orange';
                                    } else {
                                        $accountStatusText = '有效';
                                        $accountStatusClass = 'green';
                                    }
                                }
                            ?>
                            <form method="post" action="/jxdjadmin#operation-alerts" class="inline-form">
                                <input type="hidden" name="admin_action" value="refresh_promotion_stop_adapter_account_token">
                                <input type="hidden" name="admin_section" value="operation-alerts">
                                <input type="hidden" name="adapter_id" value="<?= (int) ($adapter['id'] ?? 0) ?>">
                                <input type="hidden" name="account_key" value="<?= htmlspecialchars((string) ($accountProfile['account_key'] ?? '')) ?>">
                                <?= $csrfField() ?>
                                <button class="btn mini ghost" type="submit">刷新 <?= htmlspecialchars((string) (($accountProfile['name'] ?? '') ?: ($accountProfile['account_key'] ?? '账号'))) ?></button>
                                <span class="pill <?= htmlspecialchars($accountStatusClass) ?>"><?= htmlspecialchars($accountStatusText) ?></span>
                            </form>
                        <?php endforeach; ?>
                        <form method="post" action="/jxdjadmin#operation-alerts" class="inline-form">
                            <input type="hidden" name="admin_action" value="test_promotion_stop_adapter_config">
                            <input type="hidden" name="admin_section" value="operation-alerts">
                            <input type="hidden" name="adapter_id" value="<?= (int) ($adapter['id'] ?? 0) ?>">
                            <?= $csrfField() ?>
                            <button class="btn mini ghost" type="submit">测试连接</button>
                        </form>
                        <details>
                            <summary class="btn mini ghost">编辑</summary>
                            <form method="post" action="/jxdjadmin#operation-alerts" class="stack" style="margin-top:10px; min-width:320px">
                                <input type="hidden" name="admin_action" value="save_promotion_stop_adapter_config">
                                <input type="hidden" name="admin_section" value="operation-alerts">
                                <input type="hidden" name="adapter_id" value="<?= (int) ($adapter['id'] ?? 0) ?>">
                                <?= $csrfField() ?>
                                <div class="form-grid">
                                    <label>编码<input name="adapter_key" value="<?= htmlspecialchars((string) ($adapter['adapter_key'] ?? '')) ?>"></label>
                                    <label>名称<input name="name" value="<?= htmlspecialchars((string) ($adapter['name'] ?? '')) ?>"></label>
                                    <label>状态
                                        <select name="status">
                                            <option value="active" <?= $adapterStatus === 'active' ? 'selected' : '' ?>>启用</option>
                                            <option value="paused" <?= $adapterStatus === 'paused' ? 'selected' : '' ?>>停用</option>
                                        </select>
                                    </label>
                                    <label>默认动作
                                        <select name="default_stop_action">
                                            <?php foreach ($promotionStopTaskActionLabels as $actionKey => $actionText): ?>
                                                <option value="<?= htmlspecialchars($actionKey) ?>" <?= (string) ($adapter['default_stop_action'] ?? '') === $actionKey ? 'selected' : '' ?>><?= htmlspecialchars($actionText) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label>接口地址<input name="endpoint" value="<?= htmlspecialchars((string) ($adapter['endpoint'] ?? '')) ?>"></label>
                                    <label>查询接口<input name="query_endpoint" value="<?= htmlspecialchars((string) ($adapter['query_endpoint'] ?? '')) ?>"></label>
                                    <label>鉴权方式
                                        <select name="auth_mode">
                                            <?php foreach ($callbackAuthModeLabels as $authMode => $authLabel): ?>
                                                <option value="<?= htmlspecialchars($authMode) ?>" <?= (string) ($adapterAuth['mode'] ?? 'none') === $authMode ? 'selected' : '' ?>><?= htmlspecialchars($authLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label>密钥<input name="secret" value="<?= htmlspecialchars((string) ($adapter['secret'] ?? '')) ?>"></label>
                                    <label>Token<input name="auth_token" value="<?= htmlspecialchars((string) ($adapterAuth['token'] ?? '')) ?>"></label>
                                    <label>签名 Header<input name="auth_header_name" value="<?= htmlspecialchars((string) ($adapterAuth['header_name'] ?? 'X-JX-Signature')) ?>"></label>
                                    <label>Token Header<input name="auth_token_header_name" value="<?= htmlspecialchars((string) ($adapterAuth['token_header_name'] ?? 'Authorization')) ?>"></label>
                                </div>
                                <label>平台别名<textarea name="provider_aliases_text" rows="2"><?= htmlspecialchars(implode("\n", array_map('strval', (array) ($adapter['provider_aliases'] ?? [])))) ?></textarea></label>
                                <label>字段映射 JSON<textarea name="field_mapping_text" rows="4"><?= htmlspecialchars($adapterMappingText) ?></textarea></label>
                                <label>查询字段映射 JSON<textarea name="query_field_mapping_text" rows="3"><?= htmlspecialchars($adapterQueryMappingText) ?></textarea></label>
                                <label>授权账号 JSON<textarea name="account_profiles_text" rows="5"><?= htmlspecialchars($adapterAccountProfilesText) ?></textarea></label>
                                <div class="form-grid">
                                    <label>成功字段<input name="response_success_path" value="<?= htmlspecialchars((string) ($adapterResponse['success_path'] ?? '')) ?>" placeholder="例如 code 或 success"></label>
                                    <label>成功值<input name="response_success_values_text" value="<?= htmlspecialchars(implode(', ', array_map('strval', (array) ($adapterResponse['success_values'] ?? [])))) ?>"></label>
                                    <label>处理中值<input name="response_processing_values_text" value="<?= htmlspecialchars(implode(', ', array_map('strval', (array) ($adapterResponse['processing_values'] ?? [])))) ?>"></label>
                                    <label>失败值<input name="response_failed_values_text" value="<?= htmlspecialchars(implode(', ', array_map('strval', (array) ($adapterResponse['failed_values'] ?? [])))) ?>"></label>
                                    <label>平台单号字段<input name="response_request_id_path" value="<?= htmlspecialchars((string) (($adapterResponse['request_id_path'] ?? '') ?: 'request_id')) ?>"></label>
                                    <label>平台状态字段<input name="response_status_path" value="<?= htmlspecialchars((string) (($adapterResponse['status_path'] ?? '') ?: 'status')) ?>"></label>
                                    <label>平台编码字段<input name="response_code_path" value="<?= htmlspecialchars((string) (($adapterResponse['code_path'] ?? '') ?: 'code')) ?>"></label>
                                    <label>平台消息字段<input name="response_message_path" value="<?= htmlspecialchars((string) (($adapterResponse['message_path'] ?? '') ?: 'message')) ?>"></label>
                                </div>
                                <label>备注<input name="remark" value="<?= htmlspecialchars((string) ($adapter['remark'] ?? '')) ?>"></label>
                                <p><button class="btn primary" type="submit">保存适配器</button></p>
                            </form>
                        </details>
                    <?php else: ?>
                        <em>仅管理员可维护</em>
                    <?php endif; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="section-title">
        <div>
            <span class="eyebrow">止损执行</span>
            <h2>平台停投任务</h2>
        </div>
        <span class="muted">待执行 <?= number_format((int) ($promotionStopTaskSummary['pending'] ?? 0)) ?> 条 · 处理中 <?= number_format((int) ($promotionStopTaskSummary['processing'] ?? 0)) ?> 条 · 失败 <?= number_format((int) ($promotionStopTaskSummary['failed'] ?? 0)) ?> 条 · 已完成 <?= number_format((int) (($promotionStopTaskSummary['success'] ?? 0) + ($promotionStopTaskSummary['manual_done'] ?? 0))) ?> 条</span>
    </div>
    <div class="repair-grid">
        <form method="post" action="/jxdjadmin#operation-alerts" class="row-card stack">
            <input type="hidden" name="admin_action" value="bulk_execute_promotion_stop_tasks">
            <input type="hidden" name="admin_section" value="operation-alerts">
            <?= $csrfField() ?>
            <p><strong>批量执行待停投任务</strong></p>
            <div class="form-grid">
                <label>执行方式
                    <select name="execution_mode">
                        <option value="adapter">平台适配器</option>
                        <option value="mock_success">模拟成功</option>
                        <option value="mock_fail">模拟失败</option>
                        <option value="http">HTTP 接口</option>
                    </select>
                </label>
                <label>接口地址<input name="endpoint" placeholder="HTTP 模式填写，或任务内已保存地址"></label>
                <label>签名密钥<input name="secret" placeholder="可选"></label>
                <label>本次上限<input type="number" name="limit" min="1" max="100" value="20"></label>
            </div>
            <button class="btn ghost" type="submit" <?= ((int) ($promotionStopTaskSummary['pending'] ?? 0) + (int) ($promotionStopTaskSummary['failed'] ?? 0)) <= 0 ? 'disabled' : '' ?>>批量执行停投</button>
            <p class="muted">授权失败、配置缺失或未到下次重试时间的失败任务会自动跳过。</p>
        </form>
        <form method="post" action="/jxdjadmin#operation-alerts" class="row-card stack">
            <input type="hidden" name="admin_action" value="bulk_query_promotion_stop_tasks">
            <input type="hidden" name="admin_section" value="operation-alerts">
            <?= $csrfField() ?>
            <p><strong>批量回查处理中任务</strong></p>
            <div class="form-grid">
                <label>本次上限<input type="number" name="limit" min="1" max="100" value="20"></label>
            </div>
            <button class="btn ghost" type="submit" <?= (int) ($promotionStopTaskSummary['processing'] ?? 0) <= 0 ? 'disabled' : '' ?>>批量回查结果</button>
        </form>
        <div class="row-card stack">
            <p><strong>平台分布</strong></p>
            <?php if (empty($promotionStopTaskProviderRows)): ?>
                <p class="muted">暂无平台停投任务。</p>
            <?php else: ?>
                <div class="repair-log-list">
                    <?php foreach (array_slice($promotionStopTaskProviderRows, 0, 8) as $providerRow): ?>
                        <div>
                            <strong><?= htmlspecialchars((string) ($providerRow['provider'] ?? '未标记平台')) ?></strong>
                            <span class="pill blue"><?= number_format((int) ($providerRow['total'] ?? 0)) ?> 条</span>
                            <em>待执行 <?= number_format((int) ($providerRow['pending'] ?? 0)) ?> · 处理中 <?= number_format((int) ($providerRow['processing'] ?? 0)) ?> · 成功 <?= number_format((int) ($providerRow['success'] ?? 0)) ?> · 失败 <?= number_format((int) ($providerRow['failed'] ?? 0)) ?> · 人工 <?= number_format((int) ($providerRow['manual_done'] ?? 0)) ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="order-table">
        <div class="row-card order-row-head">
            <span>任务</span>
            <span>平台对象</span>
            <span>状态</span>
            <span>执行</span>
        </div>
        <?php if (empty($promotionStopTaskRows)): ?>
            <p class="muted">暂无平台停投任务。推广入口触发自动停投后会自动生成任务。</p>
        <?php endif; ?>
        <?php foreach (array_slice($promotionStopTaskRows, 0, 50) as $stopTask): ?>
            <?php
                $stopStatus = (string) ($stopTask['status'] ?? 'pending');
                $stopStatusClass = match ($stopStatus) {
                    'success', 'manual_done' => 'jade',
                    'failed' => 'ember',
                    'processing' => 'blue',
                    'cancelled', 'skipped' => 'orange',
                    default => 'ember',
                };
            ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) (($stopTask['task_no'] ?? '') ?: ('停投任务 #' . (int) ($stopTask['id'] ?? 0)))) ?></strong>
                    <em><?= htmlspecialchars($promotionStopTaskActionLabels[(string) ($stopTask['stop_action'] ?? '')] ?? (string) ($stopTask['stop_action'] ?? '停投')) ?> · 来源 <?= htmlspecialchars((string) ($stopTask['source_type'] ?? 'auto_pause')) ?></em>
                    <em><?= htmlspecialchars((string) ($stopTask['reason'] ?? '')) ?></em>
                </span>
                <span>
                    推广码 <?= htmlspecialchars((string) (($stopTask['promotion_code'] ?? '') ?: '-')) ?>
                    <em>平台 <?= htmlspecialchars((string) (($stopTask['provider'] ?? '') ?: (($stopTask['traffic_platform'] ?? '') ?: '-'))) ?> · 链接 #<?= number_format((int) ($stopTask['promotion_link_id'] ?? 0)) ?></em>
                    <?php if (!empty($stopTask['adapter_account_key']) || !empty($stopTask['adapter_account_name'])): ?>
                        <em>授权 <?= htmlspecialchars((string) (($stopTask['adapter_account_name'] ?? '') ?: ($stopTask['adapter_account_key'] ?? '-'))) ?><?= !empty($stopTask['adapter_account_key']) ? ' · ' . htmlspecialchars((string) $stopTask['adapter_account_key']) : '' ?></em>
                    <?php endif; ?>
                    <em>广告 <?= htmlspecialchars((string) (($stopTask['ad_id'] ?? '') ?: '-')) ?> · 素材 <?= htmlspecialchars((string) (($stopTask['material_id'] ?? '') ?: '-')) ?></em>
                </span>
                <span>
                    <span class="pill <?= htmlspecialchars($stopStatusClass) ?>"><?= htmlspecialchars($promotionStopTaskStatusLabels[$stopStatus] ?? $stopStatus) ?></span>
                    <em>尝试 <?= number_format((int) ($stopTask['attempt_count'] ?? 0)) ?> 次 · <?= htmlspecialchars((string) (($stopTask['last_attempt_at'] ?? '') ?: ($stopTask['created_at'] ?? ''))) ?></em>
                    <?php if ((int) ($stopTask['query_count'] ?? 0) > 0 || !empty($stopTask['last_query_at'])): ?>
                        <em>回查 <?= number_format((int) ($stopTask['query_count'] ?? 0)) ?> 次 · <?= htmlspecialchars((string) (($stopTask['last_query_at'] ?? '') ?: '-')) ?></em>
                    <?php endif; ?>
                    <?php if (!empty($stopTask['platform_request_id']) || !empty($stopTask['platform_status']) || !empty($stopTask['platform_code'])): ?>
                        <em>平台单号 <?= htmlspecialchars((string) (($stopTask['platform_request_id'] ?? '') ?: '-')) ?> · 状态 <?= htmlspecialchars((string) (($stopTask['platform_status'] ?? '') ?: '-')) ?> · 编码 <?= htmlspecialchars((string) (($stopTask['platform_code'] ?? '') ?: '-')) ?></em>
                    <?php endif; ?>
                    <?php if (!empty($stopTask['platform_message'])): ?><em>平台消息 <?= htmlspecialchars((string) $stopTask['platform_message']) ?></em><?php endif; ?>
                    <?php if (!empty($stopTask['error_category'])): ?>
                        <em>错误分类 <?= htmlspecialchars($promotionStopTaskErrorLabels[(string) ($stopTask['error_category'] ?? '')] ?? (string) ($stopTask['error_category'] ?? '')) ?></em>
                    <?php endif; ?>
                    <?php if (!empty($stopTask['next_retry_at']) || !empty($stopTask['rate_limited_until'])): ?>
                        <em>下次重试 <?= htmlspecialchars((string) (($stopTask['next_retry_at'] ?? '') ?: '-')) ?><?= !empty($stopTask['rate_limited_until']) ? ' · 限流至 ' . htmlspecialchars((string) $stopTask['rate_limited_until']) : '' ?></em>
                    <?php endif; ?>
                    <?php if (!empty($stopTask['retry_blocked_reason'])): ?><em>阻断原因 <?= htmlspecialchars((string) $stopTask['retry_blocked_reason']) ?></em><?php endif; ?>
                    <?php if (!empty($stopTask['message'])): ?><em><?= htmlspecialchars((string) $stopTask['message']) ?></em><?php endif; ?>
                    <?php if (!empty($stopTask['handled_by_admin_name'])): ?><em><?= htmlspecialchars((string) $stopTask['handled_by_admin_name']) ?> · <?= htmlspecialchars((string) ($stopTask['completed_at'] ?? $stopTask['updated_at'] ?? '')) ?></em><?php endif; ?>
                </span>
                <span>
                    <?php if ($stopStatus === 'processing'): ?>
                        <form method="post" action="/jxdjadmin#operation-alerts" class="inline-form">
                            <input type="hidden" name="admin_action" value="query_promotion_stop_task_status">
                            <input type="hidden" name="admin_section" value="operation-alerts">
                            <input type="hidden" name="task_id" value="<?= (int) ($stopTask['id'] ?? 0) ?>">
                            <?= $csrfField() ?>
                            <button class="btn mini ghost" type="submit">回查结果</button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="/jxdjadmin#operation-alerts" class="stack">
                        <input type="hidden" name="admin_action" value="execute_promotion_stop_task">
                        <input type="hidden" name="admin_section" value="operation-alerts">
                        <input type="hidden" name="task_id" value="<?= (int) ($stopTask['id'] ?? 0) ?>">
                        <?= $csrfField() ?>
                        <label>执行方式
                            <select name="execution_mode">
                                <option value="adapter">平台适配器</option>
                                <option value="mock_success">模拟成功</option>
                                <option value="mock_fail">模拟失败</option>
                                <option value="http">HTTP 接口</option>
                                <option value="manual_done">人工完成</option>
                                <option value="cancel">取消任务</option>
                            </select>
                        </label>
                        <label>接口地址<input name="endpoint" value="<?= htmlspecialchars((string) ($stopTask['endpoint'] ?? '')) ?>" placeholder="HTTP 模式填写"></label>
                        <label>备注<input name="remark" value="<?= htmlspecialchars((string) ($stopTask['remark'] ?? '')) ?>" placeholder="执行结果或人工处理说明"></label>
                        <button class="btn ghost" type="submit">处理任务</button>
                    </form>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="section-title">
        <div>
            <span class="eyebrow">外部通知</span>
            <h2>预警通道</h2>
        </div>
        <span class="muted">待外发 <?= number_format($pendingExternalOperationAlertCount) ?> 条 · 最近日志 <?= number_format(count($operationAlertNotificationLogs)) ?> 条</span>
    </div>
    <div class="repair-grid">
        <form method="post" action="/jxdjadmin#operation-alerts" class="row-card stack">
            <input type="hidden" name="admin_action" value="save_operation_alert_notification_config">
            <input type="hidden" name="admin_section" value="operation-alerts">
            <?= $csrfField() ?>
            <label>启用状态
                <select name="enabled">
                    <option value="1" <?= !empty($operationAlertNotificationConfig['enabled']) ? 'selected' : '' ?>>启用</option>
                    <option value="0" <?= empty($operationAlertNotificationConfig['enabled']) ? 'selected' : '' ?>>停用</option>
                </select>
            </label>
            <?php $globalAlertChannels = array_values((array) ($operationAlertNotificationConfig['channels'] ?? [$operationAlertNotificationConfig['channel'] ?? 'webhook'])); ?>
            <div class="filter-checks">
                <?php foreach ($operationAlertNotificationChannelLabels as $channelValue => $channelText): ?>
                    <label><input type="checkbox" name="channels[]" value="<?= htmlspecialchars($channelValue) ?>" <?= in_array($channelValue, $globalAlertChannels, true) ? 'checked' : '' ?>> <?= htmlspecialchars($channelText) ?></label>
                <?php endforeach; ?>
            </div>
            <label>Webhook 地址
                <input name="webhook_url" value="<?= htmlspecialchars((string) ($operationAlertNotificationConfig['webhook_url'] ?? '')) ?>" placeholder="mock://success 或 https://...">
            </label>
            <label>签名密钥
                <input name="secret" value="<?= htmlspecialchars((string) ($operationAlertNotificationConfig['secret'] ?? '')) ?>" placeholder="可选，生成 X-JX-Signature">
            </label>
            <label>企业微信机器人
                <input name="wechat_work_url" value="<?= htmlspecialchars((string) ($operationAlertNotificationConfig['wechat_work_url'] ?? '')) ?>" placeholder="mock://success 或 https://qyapi.weixin.qq.com/...">
            </label>
            <label>企微签名密钥
                <input name="wechat_work_secret" value="<?= htmlspecialchars((string) ($operationAlertNotificationConfig['wechat_work_secret'] ?? '')) ?>">
            </label>
            <div class="filter-checks">
                <label><input type="checkbox" name="wechat_work_signing_enabled" value="1" <?= !empty($operationAlertNotificationConfig['wechat_work_signing_enabled']) ? 'checked' : '' ?>> 企业微信机器人加签</label>
            </div>
            <label>全局邮箱
                <input name="email" value="<?= htmlspecialchars((string) ($operationAlertNotificationConfig['email'] ?? '')) ?>" placeholder="ops@example.com">
            </label>
            <label>全局手机号
                <input name="phone" value="<?= htmlspecialchars((string) ($operationAlertNotificationConfig['phone'] ?? '')) ?>" placeholder="13800000000">
            </label>
            <label>标题模板
                <input name="title_template" value="<?= htmlspecialchars((string) ($operationAlertNotificationConfig['title_template'] ?? '短剧投放预警：{{title}}')) ?>">
            </label>
            <label>正文模板
                <textarea name="body_template" rows="3"><?= htmlspecialchars((string) ($operationAlertNotificationConfig['body_template'] ?? '{{message}}')) ?></textarea>
            </label>
            <label>邮件标题模板
                <input name="email_subject_template" value="<?= htmlspecialchars((string) ($operationAlertNotificationConfig['email_subject_template'] ?? $operationAlertNotificationConfig['title_template'] ?? '短剧投放预警：{{title}}')) ?>">
            </label>
            <label>短信模板
                <input name="sms_template" value="<?= htmlspecialchars((string) ($operationAlertNotificationConfig['sms_template'] ?? '【{{priority}}】{{title}} {{message}}')) ?>">
            </label>
            <label>最低优先级
                <select name="min_priority">
                    <?php foreach ($operationAlertPriorityLabels as $priorityValue => $priorityText): ?>
                        <option value="<?= htmlspecialchars($priorityValue) ?>" <?= (string) ($operationAlertNotificationConfig['min_priority'] ?? 'normal') === $priorityValue ? 'selected' : '' ?>><?= htmlspecialchars($priorityText) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="filter-checks">
                <?php foreach ($operationAlertStatusLabels as $statusValue => $statusText): ?>
                    <label><input type="checkbox" name="send_statuses[]" value="<?= htmlspecialchars($statusValue) ?>" <?= in_array($statusValue, (array) ($operationAlertNotificationConfig['send_statuses'] ?? ['pending', 'processing']), true) ? 'checked' : '' ?>> <?= htmlspecialchars($statusText) ?></label>
                <?php endforeach; ?>
                <label><input type="checkbox" name="retry_failed" value="1" <?= !array_key_exists('retry_failed', $operationAlertNotificationConfig) || !empty($operationAlertNotificationConfig['retry_failed']) ? 'checked' : '' ?>> 允许失败后重发</label>
                <label><input type="checkbox" name="escalation_enabled" value="1" <?= !empty($operationAlertNotificationConfig['escalation_enabled']) ? 'checked' : '' ?>> 紧急预警升级</label>
                <label><input type="checkbox" name="failure_escalation_enabled" value="1" <?= !empty($operationAlertNotificationConfig['failure_escalation_enabled']) ? 'checked' : '' ?>> 失败后升级</label>
            </div>
            <?php $urgentAlertChannels = array_values((array) ($operationAlertNotificationConfig['urgent_channels'] ?? [])); ?>
            <div class="filter-checks">
                <?php foreach ($operationAlertNotificationChannelLabels as $channelValue => $channelText): ?>
                    <label><input type="checkbox" name="urgent_channels[]" value="<?= htmlspecialchars($channelValue) ?>" <?= in_array($channelValue, $urgentAlertChannels, true) ? 'checked' : '' ?>> 紧急加发<?= htmlspecialchars($channelText) ?></label>
                <?php endforeach; ?>
            </div>
            <label>失败升级阈值
                <input type="number" name="failure_escalation_after_attempts" min="1" max="10" value="<?= (int) ($operationAlertNotificationConfig['failure_escalation_after_attempts'] ?? 1) ?>">
            </label>
            <?php $failureEscalationChannels = array_values((array) ($operationAlertNotificationConfig['failure_escalation_channels'] ?? [])); ?>
            <div class="filter-checks">
                <?php foreach ($operationAlertNotificationChannelLabels as $channelValue => $channelText): ?>
                    <label><input type="checkbox" name="failure_escalation_channels[]" value="<?= htmlspecialchars($channelValue) ?>" <?= in_array($channelValue, $failureEscalationChannels, true) ? 'checked' : '' ?>> 失败加发<?= htmlspecialchars($channelText) ?></label>
                <?php endforeach; ?>
            </div>
            <p><strong>接收人配置</strong></p>
            <p class="muted">未配置接收人时使用上面的全局渠道；配置后会按接收人范围和渠道分别发送。</p>
            <?php $receiverRows = array_merge($operationAlertNotificationReceivers, [['id' => 0, 'name' => '', 'status' => 'active', 'scope_type' => 'global', 'scope_role' => '', 'agent_id' => 0, 'channels' => ['webhook'], 'webhook_url' => '', 'secret' => '', 'wechat_work_url' => '', 'wechat_work_secret' => '', 'wechat_work_signing_enabled' => false, 'email' => '', 'phone' => '', 'send_statuses' => ['pending', 'processing'], 'alert_types' => ['all'], 'min_priority' => 'normal', 'retry_failed' => true, 'remark' => '']]); ?>
            <?php foreach ($receiverRows as $receiverIndex => $receiver): ?>
                <?php
                    $receiverId = (int) ($receiver['id'] ?? 0);
                    $receiverPrefix = 'receivers[' . $receiverIndex . ']';
                    $receiverAlertTypes = (array) ($receiver['alert_types'] ?? ['all']);
                    $receiverChannels = array_values((array) ($receiver['channels'] ?? [$receiver['channel'] ?? 'webhook']));
                ?>
                <div class="row-card stack">
                    <input type="hidden" name="<?= htmlspecialchars($receiverPrefix) ?>[id]" value="<?= $receiverId ?>">
                    <label>接收人名称
                        <input name="<?= htmlspecialchars($receiverPrefix) ?>[name]" value="<?= htmlspecialchars((string) ($receiver['name'] ?? '')) ?>" placeholder="<?= $receiverId > 0 ? '' : '新增接收人' ?>">
                    </label>
                    <label>状态
                        <select name="<?= htmlspecialchars($receiverPrefix) ?>[status]">
                            <option value="active" <?= (string) ($receiver['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>启用</option>
                            <option value="paused" <?= (string) ($receiver['status'] ?? 'active') === 'paused' ? 'selected' : '' ?>>停用</option>
                        </select>
                    </label>
                    <label>通知范围
                        <select name="<?= htmlspecialchars($receiverPrefix) ?>[scope_type]">
                            <?php foreach ($operationAlertReceiverScopeLabels as $scopeValue => $scopeText): ?>
                                <option value="<?= htmlspecialchars($scopeValue) ?>" <?= (string) ($receiver['scope_type'] ?? 'global') === $scopeValue ? 'selected' : '' ?>><?= htmlspecialchars($scopeText) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>角色范围
                        <select name="<?= htmlspecialchars($receiverPrefix) ?>[scope_role]">
                            <option value="">不限</option>
                            <option value="business" <?= (string) ($receiver['scope_role'] ?? '') === 'business' ? 'selected' : '' ?>>商务</option>
                            <option value="leader" <?= (string) ($receiver['scope_role'] ?? '') === 'leader' ? 'selected' : '' ?>>组长</option>
                            <option value="agent" <?= (string) ($receiver['scope_role'] ?? '') === 'agent' ? 'selected' : '' ?>>代理</option>
                        </select>
                    </label>
                    <label>指定组织
                        <select name="<?= htmlspecialchars($receiverPrefix) ?>[agent_id]">
                            <option value="0">不限</option>
                            <?php foreach ($agentRows as $row): ?>
                                <?php $receiverAgentId = (int) ($row['id'] ?? 0); ?>
                                <option value="<?= $receiverAgentId ?>" <?= (int) ($receiver['agent_id'] ?? 0) === $receiverAgentId ? 'selected' : '' ?>><?= htmlspecialchars($agentPathById[$receiverAgentId] ?? (string) ($row['name'] ?? '投放账号')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="filter-checks">
                        <?php foreach ($operationAlertNotificationChannelLabels as $channelValue => $channelText): ?>
                            <label><input type="checkbox" name="<?= htmlspecialchars($receiverPrefix) ?>[channels][]" value="<?= htmlspecialchars($channelValue) ?>" <?= in_array($channelValue, $receiverChannels, true) ? 'checked' : '' ?>> <?= htmlspecialchars($channelText) ?></label>
                        <?php endforeach; ?>
                    </div>
                    <label>Webhook 地址
                        <input name="<?= htmlspecialchars($receiverPrefix) ?>[webhook_url]" value="<?= htmlspecialchars((string) ($receiver['webhook_url'] ?? '')) ?>" placeholder="mock://success 或 https://...">
                    </label>
                    <label>签名密钥
                        <input name="<?= htmlspecialchars($receiverPrefix) ?>[secret]" value="<?= htmlspecialchars((string) ($receiver['secret'] ?? '')) ?>">
                    </label>
                    <label>企业微信机器人
                        <input name="<?= htmlspecialchars($receiverPrefix) ?>[wechat_work_url]" value="<?= htmlspecialchars((string) ($receiver['wechat_work_url'] ?? '')) ?>" placeholder="mock://success 或 https://qyapi.weixin.qq.com/...">
                    </label>
                    <label>企微签名密钥
                        <input name="<?= htmlspecialchars($receiverPrefix) ?>[wechat_work_secret]" value="<?= htmlspecialchars((string) ($receiver['wechat_work_secret'] ?? '')) ?>">
                    </label>
                    <div class="filter-checks">
                        <label><input type="checkbox" name="<?= htmlspecialchars($receiverPrefix) ?>[wechat_work_signing_enabled]" value="1" <?= !empty($receiver['wechat_work_signing_enabled']) ? 'checked' : '' ?>> 企业微信机器人加签</label>
                    </div>
                    <label>邮箱
                        <input name="<?= htmlspecialchars($receiverPrefix) ?>[email]" value="<?= htmlspecialchars((string) ($receiver['email'] ?? '')) ?>" placeholder="ops@example.com">
                    </label>
                    <label>手机号
                        <input name="<?= htmlspecialchars($receiverPrefix) ?>[phone]" value="<?= htmlspecialchars((string) ($receiver['phone'] ?? '')) ?>" placeholder="13800000000">
                    </label>
                    <label>最低优先级
                        <select name="<?= htmlspecialchars($receiverPrefix) ?>[min_priority]">
                            <?php foreach ($operationAlertPriorityLabels as $priorityValue => $priorityText): ?>
                                <option value="<?= htmlspecialchars($priorityValue) ?>" <?= (string) ($receiver['min_priority'] ?? 'normal') === $priorityValue ? 'selected' : '' ?>><?= htmlspecialchars($priorityText) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="filter-checks">
                        <?php foreach (['all' => '全部类型'] + $operationAlertTypeLabels as $typeValue => $typeText): ?>
                            <label><input type="checkbox" name="<?= htmlspecialchars($receiverPrefix) ?>[alert_types][]" value="<?= htmlspecialchars($typeValue) ?>" <?= in_array($typeValue, $receiverAlertTypes, true) ? 'checked' : '' ?>> <?= htmlspecialchars($typeText) ?></label>
                        <?php endforeach; ?>
                    </div>
                    <div class="filter-checks">
                        <?php foreach ($operationAlertStatusLabels as $statusValue => $statusText): ?>
                            <label><input type="checkbox" name="<?= htmlspecialchars($receiverPrefix) ?>[send_statuses][]" value="<?= htmlspecialchars($statusValue) ?>" <?= in_array($statusValue, (array) ($receiver['send_statuses'] ?? ['pending', 'processing']), true) ? 'checked' : '' ?>> <?= htmlspecialchars($statusText) ?></label>
                        <?php endforeach; ?>
                        <label><input type="checkbox" name="<?= htmlspecialchars($receiverPrefix) ?>[retry_failed]" value="1" <?= !array_key_exists('retry_failed', $receiver) || !empty($receiver['retry_failed']) ? 'checked' : '' ?>> 允许失败后重发</label>
                        <?php if ($receiverId > 0): ?><label><input type="checkbox" name="<?= htmlspecialchars($receiverPrefix) ?>[delete]" value="1"> 删除</label><?php endif; ?>
                    </div>
                    <label>备注
                        <input name="<?= htmlspecialchars($receiverPrefix) ?>[remark]" value="<?= htmlspecialchars((string) ($receiver['remark'] ?? '')) ?>">
                    </label>
                </div>
            <?php endforeach; ?>
            <button class="btn primary" type="submit">保存通知配置</button>
        </form>
        <div class="row-card stack">
            <p><strong>批量发送预警通知</strong></p>
            <p class="muted">按配置中的状态、优先级和接收范围筛选，逐条发送到 Webhook、企微、邮件、短信或站内渠道；支持 mock://success 和 mock://fail 做联调。</p>
            <form method="post" action="/jxdjadmin#operation-alerts" class="stack">
                <input type="hidden" name="admin_action" value="bulk_send_operation_alert_notifications">
                <input type="hidden" name="admin_section" value="operation-alerts">
                <?= $csrfField() ?>
                <label>本次上限<input type="number" name="limit" min="1" max="100" value="20"></label>
                <button class="btn ghost" type="submit" <?= $pendingExternalOperationAlertCount <= 0 ? 'disabled' : '' ?>>批量发送通知</button>
            </form>
            <?php if (!empty($operationAlertNotificationLogs)): ?>
                <div class="repair-log-list">
                    <?php foreach (array_slice($operationAlertNotificationLogs, 0, 8) as $notifyLog): ?>
                        <?php
                            $notifyLogStatus = (string) ($notifyLog['status'] ?? 'pending');
                            $notifyLogClass = $notifyLogStatus === 'success' ? 'jade' : ($notifyLogStatus === 'failed' ? 'ember' : 'blue');
                            $notifyLogChannel = (string) ($notifyLog['channel'] ?? 'webhook');
                        ?>
                        <div>
                            <strong><?= htmlspecialchars($operationAlertTypeLabels[(string) ($notifyLog['alert_type'] ?? '')] ?? (string) ($notifyLog['alert_type'] ?? '预警通知')) ?></strong>
                            <span class="pill <?= htmlspecialchars($notifyLogClass) ?>"><?= htmlspecialchars($operationAlertNotificationStatusLabels[$notifyLogStatus] ?? $notifyLogStatus) ?></span>
                            <span class="pill blue"><?= htmlspecialchars($operationAlertNotificationChannelLabels[$notifyLogChannel] ?? $notifyLogChannel) ?></span>
                            <?php if (!empty($notifyLog['is_escalation'])): ?><span class="pill orange">升级</span><?php endif; ?>
                            <em><?= htmlspecialchars((string) ($notifyLog['message'] ?? '')) ?></em>
                            <?php if (!empty($notifyLog['escalation_reason'])): ?><em><?= htmlspecialchars((string) $notifyLog['escalation_reason']) ?></em><?php endif; ?>
                            <em>接收人 <?= htmlspecialchars((string) (($notifyLog['receiver_name'] ?? '') ?: '全局预警')) ?> · <?= htmlspecialchars((string) (($notifyLog['receiver_contact'] ?? '') ?: ($notifyLog['endpoint'] ?? '-'))) ?> · 预警 #<?= number_format((int) ($notifyLog['alert_id'] ?? 0)) ?> · 尝试 <?= number_format((int) ($notifyLog['attempt_count'] ?? 0)) ?> 次 · <?= htmlspecialchars((string) (($notifyLog['last_attempt_at'] ?? '') ?: ($notifyLog['created_at'] ?? ''))) ?></em>
                            <em>推广码 <?= htmlspecialchars((string) (($notifyLog['promotion_code'] ?? '') ?: '-')) ?> · 广告 <?= htmlspecialchars((string) (($notifyLog['ad_id'] ?? '') ?: '-')) ?> · 素材 <?= htmlspecialchars((string) (($notifyLog['material_id'] ?? '') ?: '-')) ?></em>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted">暂无外部通知发送日志。</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="section-title">
        <div>
            <span class="eyebrow">站内待办</span>
            <h2>预警通知</h2>
        </div>
        <span class="muted">待处理 <?= number_format($pendingOperationAlertCount) ?> 条 · 已记录 <?= number_format(count($operationAlertNotifications)) ?> 条</span>
    </div>
    <form method="post" action="/jxdjadmin#operation-alerts" class="row-card">
        <input type="hidden" name="admin_action" value="sync_operation_alert_notifications">
        <input type="hidden" name="admin_section" value="operation-alerts">
        <?= $csrfField() ?>
        <span>
            <strong>生成/同步当前异常预警</strong>
            <em>按稳定指纹去重，同一异常再次出现会更新次数和最后发现时间。</em>
        </span>
        <button class="btn ghost" type="submit" <?= (int) ($operationAlertSummary['total'] ?? 0) <= 0 ? 'disabled' : '' ?>>同步预警待办</button>
    </form>
    <div class="order-table">
        <div class="row-card order-row-head">
            <span>预警</span>
            <span>归因对象</span>
            <span>状态</span>
            <span>处理</span>
        </div>
        <?php if (empty($operationAlertNotifications)): ?>
            <p class="muted">暂无预警通知。点击“同步预警待办”后，会把当前异常生成可处理记录。</p>
        <?php endif; ?>
        <?php foreach (array_slice($operationAlertNotifications, 0, 50) as $alertNotice): ?>
            <?php
                $noticeStatus = (string) ($alertNotice['status'] ?? 'pending');
                $noticePriority = (string) ($alertNotice['priority'] ?? 'normal');
                $noticeStatusClass = match ($noticeStatus) {
                    'resolved' => 'jade',
                    'ignored' => '',
                    'processing' => 'blue',
                    default => 'ember',
                };
                $noticePriorityClass = in_array($noticePriority, ['urgent', 'high'], true) ? 'ember' : ($noticePriority === 'normal' ? 'blue' : '');
                $externalNotifyStatus = (string) ($alertNotice['external_notify_status'] ?? '');
                $externalNotifyClass = $externalNotifyStatus === 'success' ? 'jade' : ($externalNotifyStatus === 'failed' ? 'ember' : 'blue');
                $noticeMetrics = (array) ($alertNotice['metric_snapshot'] ?? []);
            ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($alertNotice['title'] ?? '投放异常预警')) ?></strong>
                    <em><?= htmlspecialchars($operationAlertTypeLabels[(string) ($alertNotice['type'] ?? '')] ?? (string) ($alertNotice['type'] ?? '异常')) ?> · 首次 <?= htmlspecialchars((string) ($alertNotice['first_seen_at'] ?? '')) ?></em>
                    <em><?= htmlspecialchars((string) ($alertNotice['message'] ?? '')) ?></em>
                    <?php if (!empty($alertNotice['suggestion'])): ?><em>建议：<?= htmlspecialchars((string) $alertNotice['suggestion']) ?></em><?php endif; ?>
                </span>
                <span>
                    推广码 <?= htmlspecialchars((string) (($alertNotice['promotion_code'] ?? '') ?: '-')) ?>
                    <em>订单 <?= htmlspecialchars((string) (($alertNotice['order_no'] ?? '') ?: '-')) ?> · 链接 <?= number_format((int) ($alertNotice['promotion_link_id'] ?? 0)) ?></em>
                    <em>平台 <?= htmlspecialchars((string) (($alertNotice['traffic_platform'] ?? '') ?: '-')) ?> · 广告 <?= htmlspecialchars((string) (($alertNotice['ad_id'] ?? '') ?: '-')) ?> · 素材 <?= htmlspecialchars((string) (($alertNotice['material_id'] ?? '') ?: '-')) ?></em>
                    <?php if (!empty($noticeMetrics)): ?><em><?= htmlspecialchars(json_encode($noticeMetrics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}') ?></em><?php endif; ?>
                </span>
                <span>
                    <span class="pill <?= htmlspecialchars($noticeStatusClass) ?>"><?= htmlspecialchars($operationAlertStatusLabels[$noticeStatus] ?? $noticeStatus) ?></span>
                    <span class="pill <?= htmlspecialchars($noticePriorityClass) ?>"><?= htmlspecialchars($operationAlertPriorityLabels[$noticePriority] ?? $noticePriority) ?></span>
                    <em>出现 <?= number_format((int) ($alertNotice['occurrence_count'] ?? 1)) ?> 次 · 最近 <?= htmlspecialchars((string) ($alertNotice['last_seen_at'] ?? '')) ?></em>
                    <?php if (!empty($alertNotice['handled_by_admin_name'])): ?>
                        <em><?= htmlspecialchars((string) $alertNotice['handled_by_admin_name']) ?> · <?= htmlspecialchars((string) ($alertNotice['handled_at'] ?? '')) ?></em>
                    <?php endif; ?>
                    <?php if (!empty($alertNotice['reply'])): ?><em>处理备注：<?= htmlspecialchars((string) $alertNotice['reply']) ?></em><?php endif; ?>
                    <?php if ($externalNotifyStatus !== ''): ?>
                        <em><span class="pill <?= htmlspecialchars($externalNotifyClass) ?>"><?= htmlspecialchars($operationAlertNotificationStatusLabels[$externalNotifyStatus] ?? $externalNotifyStatus) ?></span> 外发 <?= htmlspecialchars((string) ($alertNotice['external_notify_last_attempt_at'] ?? '')) ?></em>
                        <?php if (!empty($alertNotice['external_notify_message'])): ?><em><?= htmlspecialchars((string) $alertNotice['external_notify_message']) ?></em><?php endif; ?>
                    <?php endif; ?>
                </span>
                <span>
                    <form method="post" action="/jxdjadmin#operation-alerts" class="stack">
                        <input type="hidden" name="admin_action" value="send_operation_alert_notification">
                        <input type="hidden" name="admin_section" value="operation-alerts">
                        <input type="hidden" name="alert_id" value="<?= (int) ($alertNotice['id'] ?? 0) ?>">
                        <?= $csrfField() ?>
                        <button class="btn ghost" type="submit">发送通知</button>
                    </form>
                    <form method="post" action="/jxdjadmin#operation-alerts" class="stack">
                        <input type="hidden" name="admin_action" value="update_operation_alert_notification">
                        <input type="hidden" name="admin_section" value="operation-alerts">
                        <input type="hidden" name="alert_id" value="<?= (int) ($alertNotice['id'] ?? 0) ?>">
                        <?= $csrfField() ?>
                        <label>处理状态
                            <select name="status">
                                <?php foreach ($operationAlertStatusLabels as $statusValue => $statusText): ?>
                                    <option value="<?= htmlspecialchars($statusValue) ?>" <?= $noticeStatus === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusText) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>处理备注
                            <textarea name="reply" rows="2" placeholder="记录排查结果"><?= htmlspecialchars((string) ($alertNotice['reply'] ?? '')) ?></textarea>
                        </label>
                        <button class="btn ghost" type="submit">保存处理</button>
                    </form>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'revenue-trend' ? 'is-active' : '' ?>" id="admin-section-revenue-trend" data-admin-section="revenue-trend" data-admin-primary="stats">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">投放回收</span>
            <h2>回收趋势</h2>
        </div>
        <span class="muted">按用户注册日期分组，统计 T+0 到 T+90 累计回收。</span>
    </div>
    <?php $renderAnalyticsFilterBar('revenue-trend', '按用户注册日期分组，统计 T+0 到 T+90 累计回收。'); ?>
    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('user') ?></span>
            <small>新增用户</small>
            <strong><?= number_format((int) ($recoverySummary['new_users'] ?? 0)) ?></strong>
            <em>加桌 <?= number_format((int) ($recoverySummary['add_desktop'] ?? 0)) ?></em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('orders') ?></span>
            <small>付费用户</small>
            <strong><?= number_format((int) ($recoverySummary['paid_users'] ?? 0)) ?></strong>
            <em>付费率 <?= number_format((float) ($recoverySummary['pay_rate'] ?? 0), 2) ?>%</em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('revenue') ?></span>
            <small>累计回收</small>
            <strong><?= htmlspecialchars($money((float) ($recoverySummary['revenue'] ?? 0))) ?></strong>
            <em>已扣除退款净额</em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('profit') ?></span>
            <small>总成本</small>
            <strong><?= htmlspecialchars($money((float) ($recoverySummary['cost'] ?? 0))) ?></strong>
            <em>回本 <?= ($recoverySummary['recovery_rate'] ?? null) === null ? '-' : number_format((float) $recoverySummary['recovery_rate'], 2) . '%' ?></em>
        </div>
    </div>

    <div class="section-title">
        <div>
            <span class="eyebrow">小时洞察</span>
            <h3>小时级趋势</h3>
        </div>
        <span class="muted">访问、内容浏览、支付、成本和回本率按小时聚合。</span>
    </div>
    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('stats') ?></span>
            <small>访问/浏览</small>
            <strong><?= number_format((int) ($analyticsInsightSummary['visits'] ?? 0)) ?> / <?= number_format((int) ($analyticsInsightSummary['content_views'] ?? 0)) ?></strong>
            <em>锁定曝光 <?= number_format((int) ($analyticsInsightSummary['lock_exposures'] ?? 0)) ?></em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('orders') ?></span>
            <small>支付订单</small>
            <strong><?= number_format((int) ($analyticsInsightSummary['paid_orders'] ?? 0)) ?></strong>
            <em>转化 <?= number_format((float) ($analyticsInsightSummary['conversion_rate'] ?? 0), 2) ?>%</em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('revenue') ?></span>
            <small>小时净收</small>
            <strong><?= htmlspecialchars($money((float) ($analyticsInsightSummary['net_amount'] ?? 0))) ?></strong>
            <em>成本 <?= htmlspecialchars($money((float) ($analyticsInsightSummary['cost'] ?? 0))) ?></em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('profit') ?></span>
            <small>峰值小时</small>
            <strong><?= htmlspecialchars((string) (($analyticsInsightSummary['peak_revenue_hour']['label'] ?? '') ?: '-')) ?></strong>
            <em>回本 <?= ($analyticsInsightSummary['recovery_rate'] ?? null) === null ? '-' : number_format((float) $analyticsInsightSummary['recovery_rate'], 2) . '%' ?></em>
        </div>
    </div>
    <div class="repair-grid">
        <div class="order-info-card">
            <h4>自动复盘建议</h4>
            <div class="repair-log-list">
                <?php if (empty($analyticsInsightRecommendations)): ?>
                    <p class="muted">暂无复盘建议。</p>
                <?php endif; ?>
                <?php foreach ($analyticsInsightRecommendations as $recommendation): ?>
                    <?php
                        $recommendationLevel = (string) ($recommendation['level'] ?? 'low');
                        $recommendationClass = match ($recommendationLevel) {
                            'high' => 'red',
                            'medium' => 'orange',
                            'good' => 'green',
                            default => 'blue',
                        };
                        $recommendationLabel = ['high' => '优先处理', 'medium' => '建议关注', 'good' => '可放大', 'low' => '观察'][(string) $recommendationLevel] ?? '建议';
                        $recommendationContext = (array) ($recommendation['context'] ?? []);
                        $recommendationMetrics = (array) ($recommendation['metrics'] ?? []);
                        $recommendationContextJson = json_encode($recommendationContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $recommendationMetricsJson = json_encode($recommendationMetrics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    ?>
                    <div>
                        <strong><?= htmlspecialchars((string) ($recommendation['title'] ?? '复盘建议')) ?></strong>
                        <span class="pill <?= htmlspecialchars($recommendationClass) ?>"><?= htmlspecialchars($recommendationLabel) ?></span>
                        <span class="pill blue"><?= htmlspecialchars($analyticsReviewTaskActionLabels[(string) ($recommendation['action_type'] ?? 'observe')] ?? '观察') ?></span>
                        <em><?= htmlspecialchars((string) ($recommendation['message'] ?? '')) ?></em>
                        <?php if ((string) ($recommendation['action'] ?? '') !== ''): ?>
                            <em>动作：<?= htmlspecialchars((string) ($recommendation['action'] ?? '')) ?></em>
                        <?php endif; ?>
                        <form method="post" action="/jxdjadmin#revenue-trend" class="inline-form">
                            <input type="hidden" name="admin_action" value="create_analytics_review_task">
                            <input type="hidden" name="admin_section" value="revenue-trend">
                            <?= $csrfField() ?>
                            <input type="hidden" name="fingerprint" value="<?= htmlspecialchars((string) ($recommendation['fingerprint'] ?? ''), ENT_QUOTES) ?>">
                            <input type="hidden" name="level" value="<?= htmlspecialchars((string) ($recommendation['level'] ?? 'medium'), ENT_QUOTES) ?>">
                            <input type="hidden" name="action_type" value="<?= htmlspecialchars((string) ($recommendation['action_type'] ?? 'observe'), ENT_QUOTES) ?>">
                            <input type="hidden" name="title" value="<?= htmlspecialchars((string) ($recommendation['title'] ?? '复盘建议'), ENT_QUOTES) ?>">
                            <input type="hidden" name="message" value="<?= htmlspecialchars((string) ($recommendation['message'] ?? ''), ENT_QUOTES) ?>">
                            <input type="hidden" name="suggested_action" value="<?= htmlspecialchars((string) ($recommendation['action'] ?? ''), ENT_QUOTES) ?>">
                            <input type="hidden" name="context_json" value="<?= htmlspecialchars((string) $recommendationContextJson, ENT_QUOTES) ?>">
                            <input type="hidden" name="metrics_json" value="<?= htmlspecialchars((string) $recommendationMetricsJson, ENT_QUOTES) ?>">
                            <input type="hidden" name="promotion_link_id" value="<?= (int) ($recommendationContext['promotion_link_id'] ?? 0) ?>">
                            <input type="hidden" name="promotion_code" value="<?= htmlspecialchars((string) ($recommendationContext['promotion_code'] ?? ''), ENT_QUOTES) ?>">
                            <input type="hidden" name="traffic_platform" value="<?= htmlspecialchars((string) ($recommendationContext['traffic_platform'] ?? ''), ENT_QUOTES) ?>">
                            <input type="hidden" name="channel_id" value="<?= htmlspecialchars((string) ($recommendationContext['channel_id'] ?? ''), ENT_QUOTES) ?>">
                            <input type="hidden" name="ad_id" value="<?= htmlspecialchars((string) ($recommendationContext['ad_id'] ?? ''), ENT_QUOTES) ?>">
                            <input type="hidden" name="material_id" value="<?= htmlspecialchars((string) ($recommendationContext['material_id'] ?? ''), ENT_QUOTES) ?>">
                            <input type="hidden" name="content_type" value="<?= htmlspecialchars((string) ($recommendationContext['content_type'] ?? ''), ENT_QUOTES) ?>">
                            <input type="hidden" name="content_id" value="<?= (int) ($recommendationContext['content_id'] ?? 0) ?>">
                            <input type="hidden" name="content_title" value="<?= htmlspecialchars((string) ($recommendationContext['content_title'] ?? ''), ENT_QUOTES) ?>">
                            <button class="btn ghost" type="submit"><?= jx_icon('order') ?><span>转复盘任务</span></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="order-info-card">
            <h4>高峰小时</h4>
            <div class="repair-log-list">
                <?php foreach (array_filter([(array) ($analyticsInsightSummary['peak_revenue_hour'] ?? []), (array) ($analyticsInsightSummary['peak_order_hour'] ?? []), (array) ($analyticsInsightSummary['best_recovery_hour'] ?? [])]) as $hourRow): ?>
                    <?php if (empty($hourRow['label'])) { continue; } ?>
                    <div>
                        <strong><?= htmlspecialchars((string) ($hourRow['label'] ?? '')) ?></strong>
                        <span class="pill green"><?= htmlspecialchars($money((float) ($hourRow['net_amount'] ?? 0))) ?></span>
                        <em>支付 <?= number_format((int) ($hourRow['paid_orders'] ?? 0)) ?> 单 · 成本 <?= htmlspecialchars($money((float) ($hourRow['cost'] ?? 0))) ?> · 回本 <?= ($hourRow['recovery_rate'] ?? null) === null ? '-' : number_format((float) $hourRow['recovery_rate'], 2) . '%' ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="section-title">
        <div>
            <span class="eyebrow">执行闭环</span>
            <h3>复盘任务</h3>
        </div>
        <span class="muted">待处理 <?= number_format((int) ($analyticsReviewTaskSummary['pending'] ?? 0)) ?> · 逾期 <?= number_format((int) ($analyticsReviewTaskSummary['overdue'] ?? 0)) ?> · 素材待审 <?= number_format((int) ($analyticsReviewTaskSummary['material_pending'] ?? 0)) ?> · 已应用 <?= number_format((int) ($analyticsReviewTaskSummary['material_applied'] ?? 0)) ?> · 效果提升 <?= number_format((int) ($analyticsReviewTaskSummary['effect_positive'] ?? 0)) ?></span>
    </div>
    <div class="repair-grid">
        <?php if (empty($analyticsReviewTaskRows)): ?>
            <div class="order-info-card">
                <p class="muted">暂无复盘任务。可从上方自动复盘建议一键生成。</p>
            </div>
        <?php endif; ?>
        <?php foreach (array_slice($analyticsReviewTaskRows, 0, 6) as $reviewTask): ?>
            <?php
                $reviewTaskStatus = (string) ($reviewTask['status'] ?? 'pending');
                $reviewTaskLevel = (string) ($reviewTask['level'] ?? 'medium');
                $reviewTaskClass = match ($reviewTaskLevel) {
                    'high' => 'red',
                    'good' => 'green',
                    'low' => 'blue',
                    default => 'orange',
                };
                $reviewTaskMetrics = (array) ($reviewTask['metrics'] ?? []);
                $reviewTaskBudget = (float) ($reviewTaskMetrics['cost_budget_limit'] ?? 0);
                $reviewTaskMinRecovery = (float) ($reviewTaskMetrics['min_recovery_rate'] ?? 0);
                $reviewTaskMinCost = (float) ($reviewTaskMetrics['auto_pause_min_cost'] ?? 0);
                $reviewTaskDueAt = (string) ($reviewTask['due_at'] ?? '');
                $reviewTaskDueValue = $reviewTaskDueAt !== '' ? str_replace(' ', 'T', substr($reviewTaskDueAt, 0, 16)) : '';
                $reviewTaskMaterialStatus = (string) ($reviewTask['material_review_status'] ?? 'none');
                $reviewTaskEffectBaseline = (array) ($reviewTask['effect_baseline_metrics'] ?? []);
                $reviewTaskEffectLatest = (array) ($reviewTask['effect_latest_metrics'] ?? []);
                $reviewTaskEffectDelta = (array) ($reviewTask['effect_delta_metrics'] ?? []);
            ?>
            <div class="order-info-card">
                <h4><?= htmlspecialchars((string) ($reviewTask['title'] ?? '复盘任务')) ?></h4>
                <div class="repair-log-list">
                    <div>
                        <strong><?= htmlspecialchars((string) ($reviewTask['task_no'] ?? '')) ?></strong>
                        <span class="pill <?= htmlspecialchars($reviewTaskClass) ?>"><?= htmlspecialchars($analyticsReviewTaskStatusLabels[$reviewTaskStatus] ?? '待处理') ?></span>
                        <span class="pill blue"><?= htmlspecialchars($analyticsReviewTaskActionLabels[(string) ($reviewTask['action_type'] ?? 'observe')] ?? '观察') ?></span>
                        <span class="pill <?= $reviewTaskMaterialStatus === 'pending' ? 'orange' : ($reviewTaskMaterialStatus === 'applied' ? 'green' : 'blue') ?>"><?= htmlspecialchars($analyticsReviewMaterialStatusLabels[$reviewTaskMaterialStatus] ?? '未提交') ?></span>
                        <?php if (!empty($reviewTask['is_overdue'])): ?>
                            <span class="pill red">已逾期</span>
                        <?php elseif (!empty($reviewTask['is_due_today'])): ?>
                            <span class="pill orange">今日到期</span>
                        <?php endif; ?>
                        <em><?= htmlspecialchars((string) ($reviewTask['message'] ?? '')) ?></em>
                        <?php if ((string) ($reviewTask['suggested_action'] ?? '') !== ''): ?>
                            <em>建议：<?= htmlspecialchars((string) ($reviewTask['suggested_action'] ?? '')) ?></em>
                        <?php endif; ?>
                        <em>负责人 <?= (int) ($reviewTask['assigned_to_admin_id'] ?? 0) > 0 ? htmlspecialchars((string) ($reviewTask['assigned_to_admin_name'] ?? ('管理员 #' . (int) ($reviewTask['assigned_to_admin_id'] ?? 0)))) : '未分派' ?> · 截止 <?= $reviewTaskDueAt !== '' ? htmlspecialchars($reviewTaskDueAt) : '-' ?> · 提醒 <?= number_format((int) ($reviewTask['reminder_count'] ?? 0)) ?> 次</em>
                        <em>入口 <?= (int) ($reviewTask['promotion_link_id'] ?? 0) > 0 ? '#' . (int) ($reviewTask['promotion_link_id'] ?? 0) : '-' ?><?= (string) ($reviewTask['promotion_code'] ?? '') !== '' ? ' · ' . htmlspecialchars((string) $reviewTask['promotion_code']) : '' ?><?= (string) ($reviewTask['content_title'] ?? '') !== '' ? ' · ' . htmlspecialchars((string) $reviewTask['content_title']) : '' ?></em>
                    </div>
                </div>
                <form method="post" action="/jxdjadmin#revenue-trend" class="stack">
                    <input type="hidden" name="admin_action" value="update_analytics_review_task">
                    <input type="hidden" name="admin_section" value="revenue-trend">
                    <input type="hidden" name="task_id" value="<?= (int) ($reviewTask['id'] ?? 0) ?>">
                    <?= $csrfField() ?>
                    <div class="form-grid">
                        <label>处理状态
                            <select name="task_status">
                                <?php foreach ($analyticsReviewTaskStatusLabels as $statusValue => $statusLabel): ?>
                                    <option value="<?= htmlspecialchars((string) $statusValue) ?>" <?= $reviewTaskStatus === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>推广动作
                            <select name="promotion_action">
                                <option value="none">只更新任务</option>
                                <?php if ((int) ($reviewTask['promotion_link_id'] ?? 0) > 0): ?>
                                    <option value="update_budget">更新预算保护线</option>
                                    <option value="pause_link">暂停推广入口</option>
                                <?php endif; ?>
                            </select>
                        </label>
                        <label>负责人
                            <select name="assigned_to_admin_id">
                                <option value="0">未分派</option>
                                <?php foreach ($adminAccounts as $account): ?>
                                    <?php
                                        $accountId = (int) ($account['id'] ?? 0);
                                        $accountName = (string) (($account['nickname'] ?? '') ?: ($account['username'] ?? ('管理员' . $accountId)));
                                    ?>
                                    <?php if ($accountId > 0 && (string) ($account['status'] ?? 'active') === 'active'): ?>
                                        <option value="<?= $accountId ?>" <?= (int) ($reviewTask['assigned_to_admin_id'] ?? 0) === $accountId ? 'selected' : '' ?>><?= htmlspecialchars($accountName) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>截止时间<input type="datetime-local" name="due_at" value="<?= htmlspecialchars($reviewTaskDueValue) ?>"></label>
                        <label>预算上限<input type="number" step="0.01" min="0" name="cost_budget_limit" value="<?= htmlspecialchars((string) $reviewTaskBudget) ?>"></label>
                        <label>最低回本率<input type="number" step="0.01" min="0" name="min_recovery_rate" value="<?= htmlspecialchars((string) $reviewTaskMinRecovery) ?>"></label>
                        <label>最低消耗<input type="number" step="0.01" min="0" name="auto_pause_min_cost" value="<?= htmlspecialchars((string) $reviewTaskMinCost) ?>"></label>
                        <label>自动保护
                            <select name="auto_pause_on_cost">
                                <option value="1">开启</option>
                                <option value="0">关闭</option>
                            </select>
                        </label>
                    </div>
                    <label>处理备注<input name="task_remark" value="<?= htmlspecialchars((string) ($reviewTask['remark'] ?? ''), ENT_QUOTES) ?>" placeholder="记录处理结论、素材方向或预算动作"></label>
                    <button class="btn primary" type="submit"><?= jx_icon('setting') ?><span>保存处理</span></button>
                </form>
                <?php if (!in_array($reviewTaskStatus, ['done', 'ignored'], true)): ?>
                    <form method="post" action="/jxdjadmin#revenue-trend" class="inline-form">
                        <input type="hidden" name="admin_action" value="send_analytics_review_task_reminder">
                        <input type="hidden" name="admin_section" value="revenue-trend">
                        <input type="hidden" name="task_id" value="<?= (int) ($reviewTask['id'] ?? 0) ?>">
                        <?= $csrfField() ?>
                        <button class="btn ghost" type="submit"><?= jx_icon('message') ?><span>发送提醒</span></button>
                    </form>
                <?php endif; ?>
                <div class="repair-log-list">
                    <div>
                        <strong>素材替换审批</strong>
                        <span class="pill <?= $reviewTaskMaterialStatus === 'pending' ? 'orange' : ($reviewTaskMaterialStatus === 'applied' ? 'green' : 'blue') ?>"><?= htmlspecialchars($analyticsReviewMaterialStatusLabels[$reviewTaskMaterialStatus] ?? '未提交') ?></span>
                        <em>原素材 <?= htmlspecialchars((string) (($reviewTask['original_material_id'] ?? '') ?: ($reviewTask['material_id'] ?? '-'))) ?> · 新素材 <?= htmlspecialchars((string) (($reviewTask['proposed_material_id'] ?? '') ?: '-')) ?></em>
                        <?php if ((string) ($reviewTask['material_review_note'] ?? '') !== ''): ?>
                            <em>审批备注：<?= htmlspecialchars((string) ($reviewTask['material_review_note'] ?? '')) ?></em>
                        <?php endif; ?>
                    </div>
                </div>
                <form method="post" action="/jxdjadmin#revenue-trend" class="stack">
                    <input type="hidden" name="admin_action" value="submit_analytics_review_material_proposal">
                    <input type="hidden" name="admin_section" value="revenue-trend">
                    <input type="hidden" name="task_id" value="<?= (int) ($reviewTask['id'] ?? 0) ?>">
                    <?= $csrfField() ?>
                    <div class="form-grid">
                        <label>新广告 ID<input name="proposed_ad_id" value="<?= htmlspecialchars((string) ($reviewTask['proposed_ad_id'] ?? ''), ENT_QUOTES) ?>"></label>
                        <label>新创意 ID<input name="proposed_creative_id" value="<?= htmlspecialchars((string) ($reviewTask['proposed_creative_id'] ?? ''), ENT_QUOTES) ?>"></label>
                        <label>新素材 ID<input name="proposed_material_id" value="<?= htmlspecialchars((string) ($reviewTask['proposed_material_id'] ?? ''), ENT_QUOTES) ?>"></label>
                    </div>
                    <label>替换说明<input name="proposed_note" value="<?= htmlspecialchars((string) ($reviewTask['proposed_note'] ?? ''), ENT_QUOTES) ?>" placeholder="素材问题、替换原因或预期方向"></label>
                    <button class="btn ghost" type="submit"><?= jx_icon('banner') ?><span>提交素材替换</span></button>
                </form>
                <?php if ($reviewTaskMaterialStatus !== 'none'): ?>
                    <form method="post" action="/jxdjadmin#revenue-trend" class="stack">
                        <input type="hidden" name="admin_action" value="review_analytics_review_material_proposal">
                        <input type="hidden" name="admin_section" value="revenue-trend">
                        <input type="hidden" name="task_id" value="<?= (int) ($reviewTask['id'] ?? 0) ?>">
                        <?= $csrfField() ?>
                        <div class="form-grid">
                            <label>审批动作
                                <select name="material_decision">
                                    <option value="approve">通过</option>
                                    <option value="reject">驳回</option>
                                    <?php if ((int) ($reviewTask['promotion_link_id'] ?? 0) > 0): ?>
                                        <option value="apply">通过并应用</option>
                                    <?php endif; ?>
                                </select>
                            </label>
                            <label>审批备注<input name="material_review_note" value="<?= htmlspecialchars((string) ($reviewTask['material_review_note'] ?? ''), ENT_QUOTES) ?>"></label>
                        </div>
                        <button class="btn ghost" type="submit"><?= jx_icon('setting') ?><span>处理素材审批</span></button>
                    </form>
                <?php endif; ?>
                <div class="repair-log-list">
                    <div>
                        <strong>效果追踪</strong>
                        <em>基线收入 <?= htmlspecialchars($money((float) ($reviewTaskEffectBaseline['revenue'] ?? 0))) ?> · 最新收入 <?= htmlspecialchars($money((float) ($reviewTaskEffectLatest['revenue'] ?? 0))) ?> · 变化 <?= htmlspecialchars($money((float) ($reviewTaskEffectDelta['revenue'] ?? 0))) ?></em>
                        <em>基线回本 <?= ($reviewTaskEffectBaseline['recovery_rate'] ?? null) === null ? '-' : number_format((float) $reviewTaskEffectBaseline['recovery_rate'], 2) . '%' ?> · 最新回本 <?= ($reviewTaskEffectLatest['recovery_rate'] ?? null) === null ? '-' : number_format((float) $reviewTaskEffectLatest['recovery_rate'], 2) . '%' ?> · 变化 <?= ($reviewTaskEffectDelta['recovery_rate'] ?? null) === null ? '-' : number_format((float) $reviewTaskEffectDelta['recovery_rate'], 2) . '%' ?></em>
                    </div>
                </div>
                <form method="post" action="/jxdjadmin#revenue-trend" class="inline-form">
                    <input type="hidden" name="admin_action" value="refresh_analytics_review_task_effect">
                    <input type="hidden" name="admin_section" value="revenue-trend">
                    <input type="hidden" name="task_id" value="<?= (int) ($reviewTask['id'] ?? 0) ?>">
                    <?= $csrfField() ?>
                    <button class="btn ghost" type="submit"><?= jx_icon('stats') ?><span>刷新效果</span></button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="order-table">
        <div class="row-card order-row-head">
            <span>小时</span>
            <span>流量</span>
            <span>订单/收入</span>
            <span>成本/回本</span>
        </div>
        <?php foreach ($analyticsInsightHourRows as $hourRow): ?>
            <?php
                $hasHourData = ((int) ($hourRow['visits'] ?? 0) + (int) ($hourRow['content_views'] ?? 0) + (int) ($hourRow['paid_orders'] ?? 0) + (float) ($hourRow['cost'] ?? 0)) > 0;
            ?>
            <?php if (!$hasHourData) { continue; } ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($hourRow['label'] ?? '')) ?></strong>
                    <em>创建订单 <?= number_format((int) ($hourRow['orders'] ?? 0)) ?></em>
                </span>
                <span>
                    访问 <?= number_format((int) ($hourRow['visits'] ?? 0)) ?> · 浏览 <?= number_format((int) ($hourRow['content_views'] ?? 0)) ?>
                    <em>锁定曝光 <?= number_format((int) ($hourRow['lock_exposures'] ?? 0)) ?></em>
                </span>
                <span>
                    支付 <?= number_format((int) ($hourRow['paid_orders'] ?? 0)) ?> · <?= htmlspecialchars($money((float) ($hourRow['net_amount'] ?? 0))) ?>
                    <em>转化 <?= number_format((float) ($hourRow['conversion_rate'] ?? 0), 2) ?>% · 均单 <?= htmlspecialchars($money((float) ($hourRow['avg_order_amount'] ?? 0))) ?></em>
                </span>
                <span>
                    <?= htmlspecialchars($money((float) ($hourRow['cost'] ?? 0))) ?>
                    <em>回本 <?= ($hourRow['recovery_rate'] ?? null) === null ? '-' : number_format((float) $hourRow['recovery_rate'], 2) . '%' ?></em>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="order-table">
        <div class="row-card order-row-head">
            <span>注册日期</span>
            <span>新增/加桌</span>
            <span>充值/成本</span>
            <?php foreach ($recoveryDays as $day): ?>
                <span>T+<?= (int) $day ?></span>
            <?php endforeach; ?>
        </div>
        <?php if (empty($recoveryRows)): ?>
            <p class="muted">暂无可统计的用户或订单。产生用户和已支付订单后，这里会自动形成回收趋势。</p>
        <?php endif; ?>
        <?php foreach ($recoveryRows as $row): ?>
            <div class="row-card order-row">
                <span>
                    <strong><?= htmlspecialchars((string) ($row['date'] ?? '')) ?></strong>
                    <em>距今 <?= number_format((int) ($row['age_days'] ?? 0)) ?> 天</em>
                </span>
                <span>
                    新增 <?= number_format((int) ($row['new_users'] ?? 0)) ?> · 加桌 <?= number_format((int) ($row['add_desktop'] ?? 0)) ?>
                    <em>付费 <?= number_format((int) ($row['paid_user_count'] ?? 0)) ?> · 付费率 <?= number_format((float) ($row['pay_rate'] ?? 0), 2) ?>%</em>
                </span>
                <span>
                    <?= htmlspecialchars($money((float) ($row['revenue'] ?? 0))) ?>
                    <em>成本 <?= htmlspecialchars($money((float) ($row['cost'] ?? 0))) ?> · 回本 <?= ($row['recovery_rate'] ?? null) === null ? '-' : number_format((float) $row['recovery_rate'], 2) . '%' ?></em>
                    <em>加桌成本 <?= ($row['add_desktop_cost'] ?? null) === null ? '-' : htmlspecialchars($money((float) $row['add_desktop_cost'])) ?> · 下单成本 <?= ($row['order_cost'] ?? null) === null ? '-' : htmlspecialchars($money((float) $row['order_cost'])) ?></em>
                </span>
                <?php foreach ($recoveryDays as $day): ?>
                    <?php $dayAmounts = (array) ($row['day_amounts'] ?? []); ?>
                    <?php $dayRates = (array) ($row['recovery_rates'] ?? []); ?>
                    <span>
                        <?= htmlspecialchars($money((float) ($dayAmounts[$day] ?? 0))) ?>
                        <em><?= ($dayRates[$day] ?? null) === null ? '-' : number_format((float) $dayRates[$day], 2) . '%' ?></em>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="row-card stack">
        <p><strong>口径说明</strong></p>
        <p class="muted">按“注册日期 cohort + 已支付订单净额 + 每日投放消耗”计算累计回收；旧用户没有注册时间时，会用首单/首次推广事件时间兜底。加桌事件来自推广事件上报，用于计算加桌成本。</p>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'settings' ? 'is-active' : '' ?>" id="admin-section-settings" data-admin-section="settings" data-admin-primary="settings">
    <h2>后台账号设置</h2>
    <p class="muted">当前角色：<?= htmlspecialchars((string) ($adminScope['role_label'] ?? '管理员')) ?><?= !empty($adminScope['restricted']) ? ' · 仅查看绑定投放组织范围内的数据' : ' · 可查看全站数据' ?>。上线前建议改掉默认密码。</p>
    <form method="post" action="/jxdjadmin#settings" class="row-card stack">
        <input type="hidden" name="admin_action" value="update_admin_account">
        <input type="hidden" name="admin_section" value="settings">
        <?= $csrfField() ?>
        <p><strong>当前账号资料</strong></p>
        <div class="form-grid">
            <label>登录账号<input name="username" value="<?= htmlspecialchars((string) ($current_admin['username'] ?? 'admin')) ?>"></label>
            <label>显示名称<input name="nickname" value="<?= htmlspecialchars((string) ($current_admin['nickname'] ?? '管理员')) ?>"></label>
            <label>通知邮箱<input name="email" value="<?= htmlspecialchars((string) ($current_admin['email'] ?? '')) ?>"></label>
            <label>通知手机号<input name="phone" value="<?= htmlspecialchars((string) ($current_admin['phone'] ?? '')) ?>"></label>
            <label>后台角色
                <select name="role">
                    <?php foreach ($adminRoleLabels as $roleKey => $roleLabel): ?>
                        <option value="<?= htmlspecialchars($roleKey) ?>" <?= (string) ($current_admin['role'] ?? 'super_admin') === $roleKey ? 'selected' : '' ?>><?= htmlspecialchars($roleLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>绑定投放账号
                <select name="agent_id">
                    <option value="0">不绑定投放账号</option>
                    <?php foreach ($agentRows as $row): ?>
                        <?php $agentId = (int) ($row['id'] ?? 0); ?>
                        <option value="<?= $agentId ?>" <?= (int) ($current_admin['agent_id'] ?? 0) === $agentId ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) ($row['path'] ?? $row['name'] ?? '投放账号')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>新密码<input name="password" type="password" placeholder="留空表示不修改"></label>
        </div>
        <p><button class="btn primary" type="submit">保存后台账号</button></p>
    </form>
    <?php if ((string) ($adminScope['role'] ?? '') === 'super_admin'): ?>
        <form method="post" action="/jxdjadmin#settings" class="row-card stack">
            <input type="hidden" name="admin_action" value="save_admin_account">
            <input type="hidden" name="admin_section" value="settings">
            <?= $csrfField() ?>
            <p><strong>新建后台账号</strong></p>
            <div class="form-grid">
                <label>登录账号<input name="account_username" placeholder="例如 editor01"></label>
                <label>显示名称<input name="account_nickname" placeholder="例如 内容编辑"></label>
                <label>通知邮箱<input name="account_email" placeholder="用于审批通知"></label>
                <label>通知手机号<input name="account_phone" placeholder="用于审批短信"></label>
                <label>初始密码<input name="account_password" type="password" placeholder="必填"></label>
                <label>后台角色
                    <select name="account_role">
                        <?php foreach ($adminRoleLabels as $roleKey => $roleLabel): ?>
                            <option value="<?= htmlspecialchars($roleKey) ?>" <?= $roleKey === 'editor' ? 'selected' : '' ?>><?= htmlspecialchars($roleLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>绑定投放账号
                    <select name="account_agent_id">
                        <option value="0">不绑定投放账号</option>
                        <?php foreach ($agentRows as $row): ?>
                            <?php $agentId = (int) ($row['id'] ?? 0); ?>
                            <option value="<?= $agentId ?>"><?= htmlspecialchars((string) ($row['path'] ?? $row['name'] ?? '投放账号')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>状态
                    <select name="account_status">
                        <option value="active">启用</option>
                        <option value="paused">停用</option>
                    </select>
                </label>
            </div>
            <p><button class="btn" type="submit">创建后台账号</button></p>
        </form>
        <div class="row-card stack">
            <div class="section-title admin-section-title">
                <div>
                    <span class="eyebrow">账号管理</span>
                    <h2>后台账号列表</h2>
                </div>
                <span class="muted">共 <?= number_format(count($adminAccounts)) ?> 个账号</span>
            </div>
            <div class="order-list">
                <?php foreach ($adminAccounts as $account): ?>
                    <?php
                        $accountId = (int) ($account['id'] ?? 0);
                        $accountRole = (string) ($account['role'] ?? 'editor');
                        $accountStatus = (string) ($account['status'] ?? 'active');
                    ?>
                    <form method="post" action="/jxdjadmin#settings" class="order-item">
                        <input type="hidden" name="admin_action" value="save_admin_account">
                        <input type="hidden" name="admin_section" value="settings">
                        <input type="hidden" name="admin_account_id" value="<?= $accountId ?>">
                        <?= $csrfField() ?>
                        <span>
                            <strong><?= htmlspecialchars((string) ($account['nickname'] ?? $account['username'] ?? '后台账号')) ?></strong>
                            <span class="pill <?= $accountStatus === 'active' ? 'green' : 'orange' ?>"><?= $accountStatus === 'active' ? '启用' : '停用' ?></span>
                            <em>ID <?= $accountId ?> · <?= htmlspecialchars($adminRoleLabels[$accountRole] ?? $accountRole) ?></em>
                        </span>
                        <span>
                            <em>账号 <input name="account_username" value="<?= htmlspecialchars((string) ($account['username'] ?? '')) ?>" aria-label="登录账号"></em>
                            <em>名称 <input name="account_nickname" value="<?= htmlspecialchars((string) ($account['nickname'] ?? '')) ?>" aria-label="显示名称"></em>
                            <em>邮箱 <input name="account_email" value="<?= htmlspecialchars((string) ($account['email'] ?? '')) ?>" aria-label="通知邮箱"></em>
                            <em>手机 <input name="account_phone" value="<?= htmlspecialchars((string) ($account['phone'] ?? '')) ?>" aria-label="通知手机号"></em>
                            <em>密码 <input name="account_password" type="password" placeholder="留空不改" aria-label="重置密码"></em>
                        </span>
                        <span>
                            <em>
                                <select name="account_role" aria-label="后台角色">
                                    <?php foreach ($adminRoleLabels as $roleKey => $roleLabel): ?>
                                        <option value="<?= htmlspecialchars($roleKey) ?>" <?= $accountRole === $roleKey ? 'selected' : '' ?>><?= htmlspecialchars($roleLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </em>
                            <em>投放
                                <select name="account_agent_id" aria-label="绑定投放账号">
                                    <option value="0">不绑定</option>
                                    <?php foreach ($agentRows as $row): ?>
                                        <?php $agentId = (int) ($row['id'] ?? 0); ?>
                                        <option value="<?= $agentId ?>" <?= (int) ($account['agent_id'] ?? 0) === $agentId ? 'selected' : '' ?>><?= htmlspecialchars((string) ($row['path'] ?? $row['name'] ?? '投放账号')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </em>
                            <em>
                                <select name="account_status" aria-label="状态">
                                    <option value="active" <?= $accountStatus === 'active' ? 'selected' : '' ?>>启用</option>
                                    <option value="paused" <?= $accountStatus === 'paused' ? 'selected' : '' ?>>停用</option>
                                </select>
                            </em>
                            <em><?= htmlspecialchars((string) ($account['updated_at'] ?? '')) ?></em>
                            <button class="btn ghost" type="submit">保存</button>
                        </span>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'base-config' ? 'is-active' : '' ?>" id="admin-section-base-config" data-admin-section="base-config" data-admin-primary="settings">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">系统设置</span>
            <h2>基础配置</h2>
        </div>
        <span class="muted">前台展示 / 备案信息</span>
    </div>
    <?php if (!empty($message)): ?><p class="notice"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <p class="muted">站点名称会用于后台顶部品牌展示；备案号会在前台底部展示，留空则不显示。</p>
    <form method="post" action="/jxdjadmin" class="stack">
        <input type="hidden" name="admin_action" value="update_base_config">
                <?= $csrfField() ?>
        <div class="form-grid">
            <label>站点名称<input name="site_name" value="<?= htmlspecialchars((string) ($siteConfig['site_name'] ?? '精秀短剧')) ?>" placeholder="例如：精秀短剧"></label>
            <label>备案号<input name="icp_number" value="<?= htmlspecialchars((string) ($siteConfig['icp_number'] ?? '')) ?>" placeholder="例如：粤ICP备xxxxxxxx号"></label>
        </div>
        <div class="placeholder-grid">
            <div class="system-item"><strong><?= htmlspecialchars((string) ($siteConfig['site_name'] ?? '精秀短剧')) ?></strong><span>当前站点名称</span></div>
            <div class="system-item"><strong><?= htmlspecialchars((string) (($siteConfig['icp_number'] ?? '') ?: '未填写')) ?></strong><span>当前备案号</span></div>
            <div class="system-item"><strong><?= htmlspecialchars($homepageTemplateOptions[$homepageTemplate]['label'] ?? '小程序风格') ?></strong><span>当前首页模版</span></div>
        </div>
        <p><button class="btn primary" type="submit">保存基础配置</button></p>
    </form>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'notification-config' ? 'is-active' : '' ?>" id="admin-section-notification-config" data-admin-section="notification-config" data-admin-primary="settings">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">系统设置</span>
            <h2>短信邮件配置</h2>
        </div>
        <span class="muted">短信 <?= htmlspecialchars((string) ($smsConfig['provider'] ?? 'mock')) ?> · 邮件 <?= htmlspecialchars((string) ($emailConfig['provider'] ?? 'mock')) ?></span>
    </div>
    <div class="kpi-grid">
        <div class="kpi blue">
            <span class="kpi-icon"><?= jx_icon('message') ?></span>
            <small>短信通道</small>
            <strong><?= (string) ($smsConfig['status'] ?? 'active') === 'active' ? '启用' : '停用' ?></strong>
            <em><?= htmlspecialchars((string) ($smsConfig['sign_name'] ?? '精秀短剧')) ?></em>
        </div>
        <div class="kpi green">
            <span class="kpi-icon"><?= jx_icon('stats') ?></span>
            <small>短信记录</small>
            <strong><?= number_format(count($smsCodes)) ?></strong>
            <em>最近验证码发送</em>
        </div>
        <div class="kpi orange">
            <span class="kpi-icon"><?= jx_icon('message') ?></span>
            <small>邮件通道</small>
            <strong><?= (string) ($emailConfig['status'] ?? 'paused') === 'active' ? '启用' : '停用' ?></strong>
            <em><?= htmlspecialchars((string) ($emailConfig['from_email'] ?? 'noreply@example.com')) ?></em>
        </div>
        <div class="kpi cyan">
            <span class="kpi-icon"><?= jx_icon('order') ?></span>
            <small>邮件记录</small>
            <strong><?= number_format(count($emailDeliveryLogs)) ?></strong>
            <em>测试与发送日志</em>
        </div>
    </div>
    <form method="post" action="/jxdjadmin#notification-config" class="row-card stack">
        <input type="hidden" name="admin_action" value="save_notification_config">
        <input type="hidden" name="admin_section" value="notification-config">
        <?= $csrfField() ?>
        <p><strong>短信通道</strong></p>
        <div class="form-grid">
            <label>状态
                <select name="sms_status">
                    <option value="active" <?= (string) ($smsConfig['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>启用</option>
                    <option value="paused" <?= (string) ($smsConfig['status'] ?? 'active') === 'paused' ? 'selected' : '' ?>>停用</option>
                </select>
            </label>
            <label>通道
                <select name="sms_provider">
                    <?php foreach (['mock' => '模拟通道', 'aliyun' => '阿里云短信', 'tencent' => '腾讯云短信', 'qiniu' => '七牛短信', 'custom' => '自定义'] as $providerKey => $providerLabel): ?>
                        <option value="<?= htmlspecialchars($providerKey) ?>" <?= (string) ($smsConfig['provider'] ?? 'mock') === $providerKey ? 'selected' : '' ?>><?= htmlspecialchars($providerLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>短信签名<input name="sms_sign_name" value="<?= htmlspecialchars((string) ($smsConfig['sign_name'] ?? '精秀短剧')) ?>"></label>
            <label>模板ID<input name="sms_template_id" value="<?= htmlspecialchars((string) ($smsConfig['template_id'] ?? 'SMS_LOGIN')) ?>"></label>
            <label>有效分钟<input name="sms_expire_minutes" type="number" min="1" max="30" value="<?= (int) ($smsConfig['expire_minutes'] ?? 10) ?>"></label>
            <label>单号日限<input name="sms_daily_limit_per_phone" type="number" min="1" max="50" value="<?= (int) ($smsConfig['daily_limit_per_phone'] ?? 10) ?>"></label>
            <label>AccessKey<input name="sms_access_key" value="<?= htmlspecialchars((string) ($smsConfig['access_key'] ?? '')) ?>"></label>
            <label>AccessSecret<input name="sms_access_secret" type="password" placeholder="<?= trim((string) ($smsConfig['access_secret'] ?? '')) !== '' ? '已保存，留空不改' : '未配置' ?>"></label>
            <label>接口地址<input name="sms_endpoint" value="<?= htmlspecialchars((string) ($smsConfig['endpoint'] ?? '')) ?>" placeholder="mock://success 或 https://..."></label>
            <label>备注<input name="sms_remark" value="<?= htmlspecialchars((string) ($smsConfig['remark'] ?? '')) ?>"></label>
        </div>
        <label>短信模板<textarea name="sms_template_content" rows="2"><?= htmlspecialchars((string) ($smsConfig['template_content'] ?? '您的验证码为 {code}，{minutes} 分钟内有效。')) ?></textarea></label>
        <p><strong>邮件通道</strong></p>
        <div class="form-grid">
            <label>状态
                <select name="email_status">
                    <option value="active" <?= (string) ($emailConfig['status'] ?? 'paused') === 'active' ? 'selected' : '' ?>>启用</option>
                    <option value="paused" <?= (string) ($emailConfig['status'] ?? 'paused') === 'paused' ? 'selected' : '' ?>>停用</option>
                </select>
            </label>
            <label>通道
                <select name="email_provider">
                    <?php foreach (['mock' => '模拟通道', 'smtp' => 'SMTP', 'aliyun' => '阿里云邮件', 'sendcloud' => 'SendCloud', 'custom' => '自定义'] as $providerKey => $providerLabel): ?>
                        <option value="<?= htmlspecialchars($providerKey) ?>" <?= (string) ($emailConfig['provider'] ?? 'mock') === $providerKey ? 'selected' : '' ?>><?= htmlspecialchars($providerLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>发件邮箱<input name="email_from_email" value="<?= htmlspecialchars((string) ($emailConfig['from_email'] ?? 'noreply@example.com')) ?>"></label>
            <label>发件名称<input name="email_from_name" value="<?= htmlspecialchars((string) ($emailConfig['from_name'] ?? '精秀短剧')) ?>"></label>
            <label>SMTP Host<input name="email_host" value="<?= htmlspecialchars((string) ($emailConfig['host'] ?? '')) ?>" placeholder="mock://smtp-success 或 smtp.example.com"></label>
            <label>端口<input name="email_port" type="number" min="0" max="65535" value="<?= (int) ($emailConfig['port'] ?? 465) ?>"></label>
            <label>加密
                <select name="email_secure">
                    <?php foreach (['tls' => 'TLS', 'ssl' => 'SSL', 'none' => '无'] as $secureKey => $secureLabel): ?>
                        <option value="<?= htmlspecialchars($secureKey) ?>" <?= (string) ($emailConfig['secure'] ?? 'tls') === $secureKey ? 'selected' : '' ?>><?= htmlspecialchars($secureLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>用户名<input name="email_username" value="<?= htmlspecialchars((string) ($emailConfig['username'] ?? '')) ?>"></label>
            <label>密码<input name="email_password" type="password" placeholder="<?= trim((string) ($emailConfig['password'] ?? '')) !== '' ? '已保存，留空不改' : '未配置' ?>"></label>
            <label>测试标题<input name="email_test_subject" value="<?= htmlspecialchars((string) ($emailConfig['test_subject'] ?? '精秀短剧邮件测试')) ?>"></label>
        </div>
        <label>测试模板<textarea name="email_test_template" rows="2"><?= htmlspecialchars((string) ($emailConfig['test_template'] ?? '这是一封系统测试邮件，发送时间：{time}。')) ?></textarea></label>
        <p><button class="btn primary" type="submit">保存短信邮件配置</button></p>
    </form>
    <div class="repair-grid">
        <form method="post" action="/jxdjadmin#notification-config" class="order-info-card stack">
            <input type="hidden" name="admin_action" value="send_test_notification">
            <input type="hidden" name="admin_section" value="notification-config">
            <input type="hidden" name="test_type" value="sms">
            <?= $csrfField() ?>
            <h4>短信测试</h4>
            <label>手机号<input name="test_phone" placeholder="请输入测试手机号"></label>
            <p><button class="btn ghost" type="submit">发送测试短信</button></p>
        </form>
        <form method="post" action="/jxdjadmin#notification-config" class="order-info-card stack">
            <input type="hidden" name="admin_action" value="send_test_notification">
            <input type="hidden" name="admin_section" value="notification-config">
            <input type="hidden" name="test_type" value="email">
            <?= $csrfField() ?>
            <h4>邮件测试</h4>
            <label>邮箱<input name="test_email" placeholder="请输入测试邮箱"></label>
            <p><button class="btn ghost" type="submit">发送测试邮件</button></p>
        </form>
        <div class="order-info-card">
            <h4>最近通知日志</h4>
            <div class="repair-log-list">
                <?php foreach (array_slice($smsCodes, 0, 4) as $log): ?>
                    <div>
                        <strong>短信 <?= htmlspecialchars((string) ($log['phone'] ?? '')) ?></strong>
                        <span class="pill blue"><?= htmlspecialchars((string) ($log['provider'] ?? 'mock')) ?></span>
                        <em><?= htmlspecialchars((string) ($log['created_at'] ?? '')) ?> · <?= htmlspecialchars((string) ($log['send_status'] ?? 'mocked')) ?></em>
                    </div>
                <?php endforeach; ?>
                <?php foreach (array_slice($emailDeliveryLogs, 0, 4) as $log): ?>
                    <div>
                        <strong>邮件 <?= htmlspecialchars((string) ($log['to_email'] ?? '')) ?></strong>
                        <span class="pill <?= (string) ($log['status'] ?? '') === 'success' ? 'green' : 'orange' ?>"><?= htmlspecialchars((string) ($log['status'] ?? '')) ?></span>
                        <em><?= htmlspecialchars((string) ($log['created_at'] ?? '')) ?> · <?= htmlspecialchars((string) ($log['message'] ?? '')) ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'config-approval' ? 'is-active' : '' ?>" id="admin-section-config-approval" data-admin-section="config-approval" data-admin-primary="settings">
    <?php $configTypeLabels = ['base_config' => '基础配置', 'notification_config' => '短信邮件', 'config_fragment' => '配置片段', 'payment_config' => '支付配置', 'app_config' => '应用配置']; ?>
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">系统设置</span>
            <h2>配置审批</h2>
        </div>
        <span class="muted">待审批 <?= number_format(count($configChangePending)) ?> · 总计 <?= number_format(count($configChangeRequests)) ?></span>
    </div>
    <div class="payment-rule-grid">
        <div class="system-item"><strong><?= number_format((int) ($configChangeSlaSummary['pending'] ?? 0)) ?> 条</strong><span>待审批</span></div>
        <div class="system-item"><strong><?= number_format((int) ($configChangeSlaSummary['overdue_pending'] ?? 0)) ?> 条</strong><span>SLA 超时</span></div>
        <div class="system-item"><strong><?= number_format((float) ($configChangeSlaSummary['sla_rate'] ?? 0), 1) ?>%</strong><span>审批达标率</span></div>
        <div class="system-item"><strong><?= htmlspecialchars($formatMinutes((int) ($configChangeSlaSummary['avg_review_minutes'] ?? 0))) ?></strong><span>平均审批</span></div>
        <div class="system-item"><strong><?= htmlspecialchars($formatMinutes((int) ($configChangeSlaSummary['avg_apply_minutes'] ?? 0))) ?></strong><span>平均发布</span></div>
        <div class="system-item"><strong><?= number_format((int) ($configChangeSlaSummary['rolled_back'] ?? 0)) ?> 条</strong><span>已回滚</span></div>
    </div>
    <div class="row-card stack">
        <div class="section-title admin-section-title">
            <div>
                <span class="eyebrow">审批 SLA</span>
                <h2>配置效率与超时</h2>
            </div>
            <span class="muted">支付/应用 2小时 · 短信邮件 4小时 · 其他 8小时 · <?= !empty($configApprovalPolicy['quiet_hours_enabled']) ? ('免打扰 ' . htmlspecialchars((string) ($configApprovalPolicy['quiet_start'] ?? '22:00')) . '-' . htmlspecialchars((string) ($configApprovalPolicy['quiet_end'] ?? '08:00'))) : '免打扰关闭' ?> · <?= (int) ($configApprovalPolicy['escalate_after_multiplier'] ?? 2) ?>倍超时升级</span>
        </div>
        <?php if ((int) ($configChangeSlaSummary['overdue_pending'] ?? 0) > 0 && (string) ($adminScope['role'] ?? '') === 'super_admin'): ?>
            <form method="post" action="/jxdjadmin#config-approval" class="inline-form">
                <input type="hidden" name="admin_action" value="send_config_change_sla_reminder">
                <input type="hidden" name="admin_section" value="config-approval">
                <input type="hidden" name="bulk_overdue" value="1">
                <?= $csrfField() ?>
                <button class="btn ghost" type="submit">催办全部超时审批</button>
            </form>
        <?php endif; ?>
        <div class="order-table">
            <div class="row-card order-row-head">
                <span>配置类型</span>
                <span>处理效率</span>
                <span>发布/驳回</span>
                <span>SLA</span>
            </div>
            <?php if (empty($configChangeSlaTypeRows)): ?>
                <p class="muted">暂无审批 SLA 数据。</p>
            <?php endif; ?>
            <?php foreach ($configChangeSlaTypeRows as $row): ?>
                <?php $rowType = (string) ($row['config_type'] ?? 'base_config'); ?>
                <div class="row-card order-row">
                    <span>
                        <strong><?= htmlspecialchars($configTypeLabels[$rowType] ?? $rowType) ?></strong>
                        <em>总计 <?= number_format((int) ($row['total'] ?? 0)) ?> · 阈值 <?= htmlspecialchars($formatMinutes((int) ($row['deadline_minutes'] ?? 0))) ?></em>
                    </span>
                    <span>待审 <?= number_format((int) ($row['pending'] ?? 0)) ?> · 超时 <?= number_format((int) ($row['overdue_pending'] ?? 0)) ?><em>平均审批 <?= htmlspecialchars($formatMinutes((int) ($row['avg_review_minutes'] ?? 0))) ?></em></span>
                    <span>发布 <?= number_format((int) ($row['applied'] ?? 0)) ?> · 驳回 <?= number_format((int) ($row['rejected'] ?? 0)) ?><em>回滚 <?= number_format((int) ($row['rolled_back'] ?? 0)) ?> · 平均发布 <?= htmlspecialchars($formatMinutes((int) ($row['avg_apply_minutes'] ?? 0))) ?></em></span>
                    <span><span class="pill <?= (float) ($row['sla_rate'] ?? 0) >= 90 ? 'green' : ((int) ($row['overdue_pending'] ?? 0) > 0 ? 'orange' : 'blue') ?>"><?= number_format((float) ($row['sla_rate'] ?? 0), 1) ?>%</span><em><?= number_format((int) ($row['sla_met'] ?? 0)) ?>/<?= number_format((int) ($row['sla_total'] ?? 0)) ?> 达标</em></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($configChangeSlaTargetRows)): ?>
            <div class="order-table">
                <div class="row-card order-row-head">
                    <span>应用/目标</span>
                    <span>待审风险</span>
                    <span>处理耗时</span>
                    <span>SLA</span>
                </div>
                <?php foreach (array_slice($configChangeSlaTargetRows, 0, 8) as $row): ?>
                    <?php $rowType = (string) ($row['config_type'] ?? 'base_config'); ?>
                    <div class="row-card order-row">
                        <span>
                            <strong><?= htmlspecialchars((string) (($row['label'] ?? '') ?: ($row['target_key'] ?? '配置目标'))) ?></strong>
                            <em><?= htmlspecialchars($configTypeLabels[$rowType] ?? $rowType) ?> · <?= htmlspecialchars((string) ($row['target_key'] ?? '')) ?></em>
                        </span>
                        <span>待审 <?= number_format((int) ($row['pending'] ?? 0)) ?><em>超时 <?= number_format((int) ($row['overdue_pending'] ?? 0)) ?> · 总计 <?= number_format((int) ($row['total'] ?? 0)) ?></em></span>
                        <span>审批 <?= htmlspecialchars($formatMinutes((int) ($row['avg_review_minutes'] ?? 0))) ?><em>发布 <?= htmlspecialchars($formatMinutes((int) ($row['avg_apply_minutes'] ?? 0))) ?></em></span>
                        <span><span class="pill <?= (float) ($row['sla_rate'] ?? 0) >= 90 ? 'green' : ((int) ($row['overdue_pending'] ?? 0) > 0 ? 'orange' : 'blue') ?>"><?= number_format((float) ($row['sla_rate'] ?? 0), 1) ?>%</span><em>阈值 <?= htmlspecialchars($formatMinutes((int) ($row['deadline_minutes'] ?? 0))) ?></em></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($configChangeSlaAdminRows)): ?>
            <div class="order-table">
                <div class="row-card order-row-head">
                    <span>负责人</span>
                    <span>申请积压</span>
                    <span>审批处理</span>
                    <span>效率/风险</span>
                </div>
                <?php foreach (array_slice($configChangeSlaAdminRows, 0, 8) as $row): ?>
                    <div class="row-card order-row">
                        <span>
                            <strong><?= htmlspecialchars((string) ($row['admin_name'] ?? '未知管理员')) ?></strong>
                            <em><?= htmlspecialchars((string) ($row['role_label'] ?? '负责人')) ?> · ID <?= (int) ($row['admin_id'] ?? 0) ?></em>
                        </span>
                        <span>提交 <?= number_format((int) ($row['submitted'] ?? 0)) ?> · 待审 <?= number_format((int) ($row['pending_created'] ?? 0)) ?><em>超时积压 <?= number_format((int) ($row['overdue_created'] ?? 0)) ?></em></span>
                        <span>审批 <?= number_format((int) ($row['reviewed'] ?? 0)) ?> · 发布 <?= number_format((int) ($row['applied'] ?? 0)) ?> · 驳回 <?= number_format((int) ($row['rejected'] ?? 0)) ?><em>平均 <?= htmlspecialchars($formatMinutes((int) ($row['avg_review_minutes'] ?? 0))) ?></em></span>
                        <span><span class="pill <?= (float) ($row['sla_rate'] ?? 0) >= 90 ? 'green' : ((int) ($row['overdue_created'] ?? 0) > 0 ? 'orange' : 'blue') ?>"><?= number_format((float) ($row['sla_rate'] ?? 0), 1) ?>%</span><em>催办 <?= number_format((int) ($row['reminders_sent'] ?? 0)) ?> · 回滚 <?= number_format((int) ($row['rolled_back'] ?? 0)) ?></em></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($configChangeSlaPendingRows)): ?>
            <div class="order-table">
                <div class="row-card order-row-head">
                    <span>待审事项</span>
                    <span>目标</span>
                    <span>耗时</span>
                    <span>状态</span>
                </div>
                <?php foreach (array_slice($configChangeSlaPendingRows, 0, 8) as $row): ?>
                    <?php $rowType = (string) ($row['config_type'] ?? 'base_config'); ?>
                    <div class="row-card order-row">
                        <span>
                            <strong><?= htmlspecialchars((string) ($row['title'] ?? '配置变更')) ?></strong>
                            <em><?= htmlspecialchars((string) ($row['created_by_admin_name'] ?? '')) ?> · <?= htmlspecialchars((string) ($row['created_at'] ?? '')) ?></em>
                        </span>
                        <span><?= htmlspecialchars($configTypeLabels[$rowType] ?? $rowType) ?><em><?= htmlspecialchars((string) (($row['target_label'] ?? '') ?: ($row['target_key'] ?? ''))) ?></em></span>
                        <span><?= htmlspecialchars($formatMinutes((int) ($row['elapsed_minutes'] ?? 0))) ?><em>阈值 <?= htmlspecialchars($formatMinutes((int) ($row['deadline_minutes'] ?? 0))) ?></em></span>
                        <span>
                            <span class="pill <?= !empty($row['overdue']) ? 'orange' : 'blue' ?>"><?= !empty($row['overdue']) ? '已超时' : '进行中' ?></span>
                            <em><?= !empty($row['overdue']) ? '请优先处理' : ('剩余 ' . htmlspecialchars($formatMinutes((int) ($row['remaining_minutes'] ?? 0)))) ?></em>
                            <?php if (!empty($row['overdue']) && (string) ($adminScope['role'] ?? '') === 'super_admin'): ?>
                                <form method="post" action="/jxdjadmin#config-approval" class="inline-form">
                                    <input type="hidden" name="admin_action" value="send_config_change_sla_reminder">
                                    <input type="hidden" name="admin_section" value="config-approval">
                                    <input type="hidden" name="config_change_id" value="<?= (int) ($row['id'] ?? 0) ?>">
                                    <?= $csrfField() ?>
                                    <button class="btn mini ghost" type="submit">催办</button>
                                </form>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <form method="post" action="/jxdjadmin#config-approval" class="row-card stack">
        <input type="hidden" name="admin_action" value="create_config_change_request">
        <input type="hidden" name="admin_section" value="config-approval">
        <?= $csrfField() ?>
        <p><strong>提交配置变更</strong></p>
        <div class="form-grid">
            <label>变更标题<input name="change_title" placeholder="例如 修改短信签名"></label>
            <label>变更类型
                <select name="change_config_type">
                    <option value="base_config">基础配置</option>
                    <option value="notification_config">短信邮件</option>
                    <option value="config_fragment">配置片段</option>
                </select>
            </label>
            <label>原因<input name="change_reason" placeholder="说明变更原因、工单或负责人"></label>
            <label>站点名称<input name="change_site_name" value="<?= htmlspecialchars((string) ($siteConfig['site_name'] ?? '精秀短剧')) ?>"></label>
            <label>备案号<input name="change_icp_number" value="<?= htmlspecialchars((string) ($siteConfig['icp_number'] ?? '')) ?>"></label>
            <label><span><input type="checkbox" name="change_approval_payment_config" value="1" <?= !empty($configApprovalPolicy['payment_config']) ? 'checked' : '' ?>> 支付配置强制审批</span></label>
            <label><span><input type="checkbox" name="change_approval_app_config" value="1" <?= !empty($configApprovalPolicy['app_config']) ? 'checked' : '' ?>> 应用配置强制审批</span></label>
            <label><span><input type="checkbox" name="change_approval_quiet_hours_enabled" value="1" <?= !empty($configApprovalPolicy['quiet_hours_enabled']) ? 'checked' : '' ?>> 审批通知免打扰</span></label>
            <label>免打扰开始<input name="change_approval_quiet_start" type="time" value="<?= htmlspecialchars((string) ($configApprovalPolicy['quiet_start'] ?? '22:00')) ?>"></label>
            <label>免打扰结束<input name="change_approval_quiet_end" type="time" value="<?= htmlspecialchars((string) ($configApprovalPolicy['quiet_end'] ?? '08:00')) ?>"></label>
            <label>升级倍数<input name="change_approval_escalate_multiplier" type="number" min="1" max="12" value="<?= (int) ($configApprovalPolicy['escalate_after_multiplier'] ?? 2) ?>"></label>
            <label>短信状态
                <select name="change_sms_status">
                    <option value="active" <?= (string) ($smsConfig['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>启用</option>
                    <option value="paused" <?= (string) ($smsConfig['status'] ?? 'active') === 'paused' ? 'selected' : '' ?>>停用</option>
                </select>
            </label>
            <label>短信通道<input name="change_sms_provider" value="<?= htmlspecialchars((string) ($smsConfig['provider'] ?? 'mock')) ?>"></label>
            <label>短信签名<input name="change_sms_sign_name" value="<?= htmlspecialchars((string) ($smsConfig['sign_name'] ?? '精秀短剧')) ?>"></label>
            <label>短信模板ID<input name="change_sms_template_id" value="<?= htmlspecialchars((string) ($smsConfig['template_id'] ?? 'SMS_LOGIN')) ?>"></label>
            <label>邮件状态
                <select name="change_email_status">
                    <option value="active" <?= (string) ($emailConfig['status'] ?? 'paused') === 'active' ? 'selected' : '' ?>>启用</option>
                    <option value="paused" <?= (string) ($emailConfig['status'] ?? 'paused') === 'paused' ? 'selected' : '' ?>>停用</option>
                </select>
            </label>
            <label>邮件通道<input name="change_email_provider" value="<?= htmlspecialchars((string) ($emailConfig['provider'] ?? 'mock')) ?>"></label>
            <label>发件邮箱<input name="change_email_from_email" value="<?= htmlspecialchars((string) ($emailConfig['from_email'] ?? 'noreply@example.com')) ?>"></label>
            <label>配置片段键<input name="change_fragment_key" placeholder="例如 payment.callback.secret"></label>
            <label>片段名称<input name="change_fragment_name" placeholder="配置片段名称"></label>
            <label>片段分组<input name="change_fragment_group" value="system"></label>
            <label>片段类型
                <select name="change_fragment_type">
                    <?php foreach ($configFragmentTypeLabels as $typeKey => $typeLabel): ?>
                        <option value="<?= htmlspecialchars($typeKey) ?>"><?= htmlspecialchars($typeLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>短信模板<textarea name="change_sms_template_content" rows="2"><?= htmlspecialchars((string) ($smsConfig['template_content'] ?? '您的验证码为 {code}，{minutes} 分钟内有效。')) ?></textarea></label>
        <label>片段值<textarea name="change_fragment_value" rows="3" placeholder="配置片段新值"></textarea></label>
        <label><span><input type="checkbox" name="change_fragment_sensitive" value="1"> 敏感配置</span></label>
        <p><button class="btn primary" type="submit">提交审批</button></p>
    </form>
    <div class="row-card stack">
        <div class="section-title admin-section-title">
            <div>
                <span class="eyebrow">审批队列</span>
                <h2>配置变更记录</h2>
            </div>
            <span class="muted">审批通过后自动发布</span>
        </div>
        <div class="order-table">
            <div class="row-card order-row-head">
                <span>变更</span>
                <span>申请人</span>
                <span>状态</span>
                <span>操作</span>
            </div>
            <?php if (empty($configChangeRequests)): ?>
                <p class="muted">暂无配置变更申请。</p>
            <?php endif; ?>
            <?php foreach ($configChangeRequests as $request): ?>
                <?php
                    $requestStatus = (string) ($request['status'] ?? 'pending');
                    $statusLabel = ['pending' => '待审批', 'approved' => '已审批', 'rejected' => '已驳回', 'applied' => '已发布', 'rolled_back' => '已回滚'][$requestStatus] ?? $requestStatus;
                ?>
                <div class="row-card order-row">
                    <span>
                        <strong><?= htmlspecialchars((string) ($request['title'] ?? '配置变更')) ?></strong>
                        <em><?= htmlspecialchars($configTypeLabels[(string) ($request['config_type'] ?? 'base_config')] ?? (string) ($request['config_type'] ?? '')) ?> · <?= htmlspecialchars((string) ($request['target_key'] ?? '')) ?></em>
                        <em><?= htmlspecialchars((string) ($request['reason'] ?? '')) ?></em>
                    </span>
                    <span><?= htmlspecialchars((string) ($request['created_by_admin_name'] ?? '')) ?><em><?= htmlspecialchars((string) ($request['created_at'] ?? '')) ?></em></span>
                    <span>
                        <span class="pill <?= $requestStatus === 'applied' ? 'green' : (in_array($requestStatus, ['rejected', 'rolled_back'], true) ? 'orange' : 'blue') ?>"><?= htmlspecialchars($statusLabel) ?></span>
                        <em><?= htmlspecialchars((string) (($request['rollback_note'] ?? '') ?: ($request['review_note'] ?? ''))) ?></em>
                    </span>
                    <span>
                        <?php if ($requestStatus === 'pending' && (string) ($adminScope['role'] ?? '') === 'super_admin'): ?>
                            <form method="post" action="/jxdjadmin#config-approval" class="inline-form">
                                <input type="hidden" name="admin_action" value="review_config_change_request">
                                <input type="hidden" name="admin_section" value="config-approval">
                                <input type="hidden" name="config_change_id" value="<?= (int) ($request['id'] ?? 0) ?>">
                                <input type="hidden" name="decision" value="approve">
                                <?= $csrfField() ?>
                                <input name="review_note" placeholder="审批备注">
                                <button class="btn ghost" type="submit">通过并发布</button>
                            </form>
                            <form method="post" action="/jxdjadmin#config-approval" class="inline-form">
                                <input type="hidden" name="admin_action" value="review_config_change_request">
                                <input type="hidden" name="admin_section" value="config-approval">
                                <input type="hidden" name="config_change_id" value="<?= (int) ($request['id'] ?? 0) ?>">
                                <input type="hidden" name="decision" value="reject">
                                <?= $csrfField() ?>
                                <input name="review_note" placeholder="驳回原因">
                                <button class="btn ghost" type="submit">驳回</button>
                            </form>
                        <?php elseif ($requestStatus === 'applied' && (string) ($adminScope['role'] ?? '') === 'super_admin'): ?>
                            <form method="post" action="/jxdjadmin#config-approval" class="inline-form">
                                <input type="hidden" name="admin_action" value="rollback_config_change_request">
                                <input type="hidden" name="admin_section" value="config-approval">
                                <input type="hidden" name="config_change_id" value="<?= (int) ($request['id'] ?? 0) ?>">
                                <?= $csrfField() ?>
                                <input name="rollback_note" placeholder="回滚原因">
                                <button class="btn ghost" type="submit">回滚到发布前</button>
                            </form>
                            <em><?= htmlspecialchars((string) (($request['reviewed_by_admin_name'] ?? '') ?: '-')) ?> <?= htmlspecialchars((string) ($request['applied_at'] ?? $request['reviewed_at'] ?? '')) ?></em>
                        <?php else: ?>
                            <em><?= htmlspecialchars((string) (($request['reviewed_by_admin_name'] ?? '') ?: '-')) ?> <?= htmlspecialchars((string) ($request['reviewed_at'] ?? '')) ?></em>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="row-card stack">
        <div class="section-title admin-section-title">
            <div>
                <span class="eyebrow">通知记录</span>
                <h2>审批通知日志</h2>
            </div>
            <span class="muted">最近 <?= number_format(count($configChangeNotificationLogs)) ?> 条</span>
        </div>
        <div class="order-table">
            <div class="row-card order-row-head">
                <span>通知</span>
                <span>接收人</span>
                <span>渠道</span>
                <span>状态</span>
            </div>
            <?php if (empty($configChangeNotificationLogs)): ?>
                <p class="muted">暂无审批通知记录。</p>
            <?php endif; ?>
            <?php foreach (array_slice($configChangeNotificationLogs, 0, 12) as $log): ?>
                <?php
                    $notifyStatus = (string) ($log['status'] ?? 'success');
                    $notifyEventLabel = ['submitted' => '提交待审', 'approved' => '审批发布', 'rejected' => '审批驳回', 'rolled_back' => '发布回滚', 'reminded' => '审批催办', 'escalated' => '超时升级'][(string) ($log['event'] ?? 'submitted')] ?? (string) ($log['event'] ?? '');
                    $notifyChannelLabel = ['system' => '站内', 'email' => '邮件', 'sms' => '短信'][(string) ($log['channel'] ?? 'system')] ?? (string) ($log['channel'] ?? '');
                ?>
                <div class="row-card order-row">
                    <span><strong><?= htmlspecialchars((string) ($log['title'] ?? '配置审批通知')) ?></strong><em><?= htmlspecialchars($notifyEventLabel) ?> · 申请 #<?= (int) ($log['request_id'] ?? 0) ?></em></span>
                    <span><?= htmlspecialchars((string) ($log['receiver_name'] ?? '')) ?><em><?= htmlspecialchars((string) ($log['receiver_contact'] ?? '站内')) ?></em></span>
                    <span><?= htmlspecialchars($notifyChannelLabel) ?><em><?= htmlspecialchars((string) ($log['created_at'] ?? '')) ?></em></span>
                    <span><span class="pill <?= $notifyStatus === 'success' ? 'green' : ($notifyStatus === 'skipped' ? 'orange' : 'red') ?>"><?= htmlspecialchars($notifyStatus === 'success' ? '成功' : ($notifyStatus === 'skipped' ? '跳过' : '失败')) ?></span><em><?= htmlspecialchars((string) ($log['message'] ?? '')) ?></em></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'config-fragments' ? 'is-active' : '' ?>" id="admin-section-config-fragments" data-admin-section="config-fragments" data-admin-primary="settings">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">系统设置</span>
            <h2>配置片段</h2>
        </div>
        <span class="muted"><?= number_format(count($systemConfigFragments)) ?> 个片段 · 敏感值脱敏展示</span>
    </div>
    <?php if (!empty($message)): ?><p class="notice"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <p class="muted">用于集中保存第三方配置、审核开关、回传模板、支付备注等键值片段。标记为敏感后，列表只显示脱敏值；编辑时留空不会覆盖旧密钥。</p>
    <form method="post" action="/jxdjadmin#config-fragments" class="row-card stack">
        <input type="hidden" name="admin_action" value="save_system_config_fragment">
        <input type="hidden" name="admin_section" value="config-fragments">
                <?= $csrfField() ?>
        <p><strong>新增配置片段</strong></p>
        <div class="form-grid">
            <label>配置键<input name="key" placeholder="payment.callback.secret"></label>
            <label>名称<input name="name" placeholder="支付回调密钥"></label>
            <label>分组
                <select name="group">
                    <?php foreach ($configFragmentGroupLabels as $groupKey => $groupLabel): ?>
                        <option value="<?= htmlspecialchars($groupKey) ?>"><?= htmlspecialchars($groupLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>类型
                <select name="type">
                    <?php foreach ($configFragmentTypeLabels as $typeKey => $typeLabel): ?>
                        <option value="<?= htmlspecialchars($typeKey) ?>"><?= htmlspecialchars($typeLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>状态
                <select name="status">
                    <option value="active">启用</option>
                    <option value="paused">停用</option>
                </select>
            </label>
            <label><span><input type="checkbox" name="sensitive" value="1"> 敏感值</span></label>
        </div>
        <label>配置值<textarea name="value" rows="4" placeholder="文本、JSON、URL 或密钥"></textarea></label>
        <label>备注<input name="remark" placeholder="使用位置、来源或注意事项"></label>
        <p><button class="btn primary" type="submit">保存配置片段</button></p>
    </form>
    <div class="payment-channel-table-wrap">
        <table class="payment-channel-table">
            <thead>
            <tr>
                <th>配置键</th>
                <th>分组/类型</th>
                <th>值</th>
                <th>状态</th>
                <th>更新时间</th>
                <th>编辑</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($systemConfigFragments)): ?>
                <tr><td colspan="6" class="payment-channel-empty">暂无配置片段。</td></tr>
            <?php else: ?>
                <?php foreach ($systemConfigFragments as $fragment): ?>
                    <?php
                        $fragmentType = (string) ($fragment['type'] ?? 'text');
                        $fragmentGroup = (string) ($fragment['group'] ?? 'system');
                        $fragmentStatus = (string) ($fragment['status'] ?? 'active');
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string) ($fragment['name'] ?? '配置片段')) ?></strong><br>
                            <code><?= htmlspecialchars((string) ($fragment['key'] ?? '')) ?></code>
                        </td>
                        <td><?= htmlspecialchars($configFragmentGroupLabels[$fragmentGroup] ?? $fragmentGroup) ?><br><span class="muted"><?= htmlspecialchars($configFragmentTypeLabels[$fragmentType] ?? $fragmentType) ?><?= !empty($fragment['sensitive']) ? ' · 敏感' : '' ?></span></td>
                        <td><code><?= htmlspecialchars($maskConfigValue($fragment)) ?></code><br><span class="muted"><?= htmlspecialchars((string) ($fragment['remark'] ?? '')) ?></span></td>
                        <td><span class="pill <?= $fragmentStatus === 'active' ? 'jade' : 'ember' ?>"><?= $fragmentStatus === 'active' ? '启用' : '停用' ?></span></td>
                        <td><?= htmlspecialchars((string) ($fragment['updated_at'] ?? '-')) ?><br><span class="muted"><?= htmlspecialchars((string) ($fragment['updated_by'] ?? '')) ?></span></td>
                        <td>
                            <details>
                                <summary class="btn mini ghost">编辑</summary>
                                <form method="post" action="/jxdjadmin#config-fragments" class="stack" style="margin-top:10px; min-width:320px">
                                    <input type="hidden" name="admin_action" value="save_system_config_fragment">
                <?= $csrfField() ?>
                                    <input type="hidden" name="admin_section" value="config-fragments">
                                    <input type="hidden" name="fragment_id" value="<?= (int) ($fragment['id'] ?? 0) ?>">
                                    <div class="form-grid">
                                        <label>配置键<input name="key" value="<?= htmlspecialchars((string) ($fragment['key'] ?? '')) ?>"></label>
                                        <label>名称<input name="name" value="<?= htmlspecialchars((string) ($fragment['name'] ?? '')) ?>"></label>
                                        <label>分组
                                            <select name="group">
                                                <?php foreach ($configFragmentGroupLabels as $groupKey => $groupLabel): ?>
                                                    <option value="<?= htmlspecialchars($groupKey) ?>" <?= $fragmentGroup === $groupKey ? 'selected' : '' ?>><?= htmlspecialchars($groupLabel) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label>类型
                                            <select name="type">
                                                <?php foreach ($configFragmentTypeLabels as $typeKey => $typeLabel): ?>
                                                    <option value="<?= htmlspecialchars($typeKey) ?>" <?= $fragmentType === $typeKey ? 'selected' : '' ?>><?= htmlspecialchars($typeLabel) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label>状态
                                            <select name="status">
                                                <option value="active" <?= $fragmentStatus === 'active' ? 'selected' : '' ?>>启用</option>
                                                <option value="paused" <?= $fragmentStatus === 'paused' ? 'selected' : '' ?>>停用</option>
                                            </select>
                                        </label>
                                        <label><span><input type="checkbox" name="sensitive" value="1" <?= !empty($fragment['sensitive']) ? 'checked' : '' ?>> 敏感值</span></label>
                                    </div>
                                    <label>配置值<textarea name="value" rows="4" placeholder="<?= !empty($fragment['sensitive']) ? '已保存，留空不覆盖' : '' ?>"><?= empty($fragment['sensitive']) ? htmlspecialchars((string) ($fragment['value'] ?? '')) : '' ?></textarea></label>
                                    <label>备注<input name="remark" value="<?= htmlspecialchars((string) ($fragment['remark'] ?? '')) ?>"></label>
                                    <p><button class="btn primary" type="submit">保存片段</button></p>
                                </form>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'operation-log' ? 'is-active' : '' ?>" id="admin-section-operation-log" data-admin-section="operation-log" data-admin-primary="settings">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">系统设置</span>
            <h2>操作日志</h2>
        </div>
        <span class="muted">最近 <?= number_format(count($adminOperationLogs)) ?> 条后台操作</span>
    </div>
    <p class="muted">记录后台关键 POST 操作、权限拒绝和导出下载行为；密码、密钥、私钥、token 等字段会自动隐藏。</p>
    <div class="payment-channel-table-wrap">
        <table class="payment-channel-table">
            <thead>
            <tr>
                <th>时间</th>
                <th>管理员</th>
                <th>模块/动作</th>
                <th>结果</th>
                <th>摘要</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($adminOperationLogs)): ?>
                <tr><td colspan="5" class="payment-channel-empty">暂无后台操作日志。</td></tr>
            <?php else: ?>
                <?php foreach (array_slice($adminOperationLogs, 0, 200) as $log): ?>
                    <?php
                        $logStatus = (string) ($log['status'] ?? 'success');
                        $summaryParts = [];
                        foreach ((array) ($log['summary'] ?? []) as $summaryKey => $summaryValue) {
                            $summaryParts[] = (string) $summaryKey . ': ' . (string) $summaryValue;
                            if (count($summaryParts) >= 6) {
                                break;
                            }
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string) ($log['created_at'] ?? '-')) ?><br><span class="muted"><?= htmlspecialchars((string) ($log['ip'] ?? '')) ?></span></td>
                        <td><strong><?= htmlspecialchars((string) ($log['admin_name'] ?? '管理员')) ?></strong><br><span class="muted"><?= htmlspecialchars((string) ($adminRoleLabels[(string) ($log['admin_role'] ?? '')] ?? ($log['admin_role'] ?? ''))) ?></span></td>
                        <td><code><?= htmlspecialchars((string) ($log['action'] ?? '')) ?></code><br><span class="muted"><?= htmlspecialchars((string) ($log['section'] ?? '')) ?></span></td>
                        <td><span class="pill <?= $logStatus === 'success' ? 'jade' : ($logStatus === 'denied' ? 'ember' : '') ?>"><?= htmlspecialchars(['success' => '成功', 'failed' => '失败', 'denied' => '拒绝'][$logStatus] ?? $logStatus) ?></span></td>
                        <td><strong><?= htmlspecialchars((string) ($log['message'] ?? '')) ?></strong><br><span class="muted"><?= htmlspecialchars(implode(' · ', $summaryParts)) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'api-log' ? 'is-active' : '' ?>" id="admin-section-api-log" data-admin-section="api-log" data-admin-primary="settings">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">系统设置</span>
            <h2>接口日志</h2>
        </div>
        <span class="muted">最近 <?= number_format(count($adminApiLogs)) ?> 条接口记录</span>
    </div>
    <p class="muted">聚合支付网关、投放回传和小程序接口动作；敏感 token 与密钥只展示脱敏后的地址或摘要。</p>
    <div class="payment-channel-table-wrap">
        <table class="payment-channel-table">
            <thead>
            <tr>
                <th>时间</th>
                <th>来源/场景</th>
                <th>关联订单</th>
                <th>接口</th>
                <th>结果</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($adminApiLogs)): ?>
                <tr><td colspan="5" class="payment-channel-empty">暂无接口日志。</td></tr>
            <?php else: ?>
                <?php foreach (array_slice($adminApiLogs, 0, 200) as $log): ?>
                    <?php
                        $apiStatus = (string) ($log['status'] ?? 'pending');
                        $apiStatusClass = in_array($apiStatus, ['success', 'sent', 'released'], true) ? 'jade' : (in_array($apiStatus, ['failed', 'error'], true) ? 'ember' : 'blue');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string) (($log['created_at'] ?? '') ?: '-')) ?></td>
                        <td><strong><?= htmlspecialchars((string) ($log['source'] ?? '-')) ?></strong><br><span class="muted"><?= htmlspecialchars((string) ($log['scene'] ?? '-')) ?></span></td>
                        <td><?= htmlspecialchars((string) (($log['order_no'] ?? '') ?: '-')) ?></td>
                        <td><code><?= htmlspecialchars((string) (($log['method'] ?? 'POST') . ' ' . (($log['api_url'] ?? '') ?: '-'))) ?></code></td>
                        <td><span class="pill <?= htmlspecialchars($apiStatusClass) ?>"><?= htmlspecialchars($apiStatus) ?></span><br><span class="muted"><?= htmlspecialchars((string) ($log['message'] ?? '')) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'maintenance' ? 'is-active' : '' ?>" id="admin-section-maintenance" data-admin-section="maintenance" data-admin-primary="settings">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">系统设置</span>
            <h2>MySQL 维护</h2>
        </div>
        <span class="muted">SQL 备份 / 导出 / 恢复</span>
    </div>
    <?php if (!empty($message)): ?><p class="notice"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <p class="muted">系统每次关键保存前会自动生成 MySQL SQL 滚动备份。这里可以手动创建备份、下载当前 SQL 备份，或在误操作后恢复某个 SQL 备份。</p>
    <div class="placeholder-grid">
        <div class="system-item"><strong><?= htmlspecialchars((string) (($storage_info['label'] ?? '') ?: 'MySQL 数据库')) ?></strong><span>当前存储模式</span></div>
        <div class="system-item"><strong><?= number_format(count((array) ($data_backups ?? []))) ?> 份</strong><span>当前备份数量</span></div>
        <div class="system-item"><strong>自动保留</strong><span>最近 30 份备份</span></div>
        <div class="system-item"><strong>恢复前备份</strong><span>恢复时会先保护当前数据</span></div>
        <div class="system-item"><strong><?= htmlspecialchars((string) ($storage_info['database'] ?? '-')) ?></strong><span>MySQL 数据库</span></div>
    </div>
    <div class="card-actions" style="margin:14px 0 18px">
        <form method="post" action="/jxdjadmin#maintenance" class="inline-form">
            <input type="hidden" name="admin_action" value="create_data_backup">
                <?= $csrfField() ?>
            <input type="hidden" name="admin_section" value="maintenance">
            <button class="btn primary" type="submit">立即创建备份</button>
        </form>
        <form method="post" action="/jxdjadmin#maintenance" class="inline-form">
            <input type="hidden" name="admin_action" value="download_data_file">
                <?= $csrfField() ?>
            <input type="hidden" name="admin_section" value="maintenance">
            <button class="btn ghost" type="submit">下载 SQL 备份</button>
        </form>
    </div>
    <div class="payment-channel-table-wrap">
        <table class="payment-channel-table">
            <thead>
            <tr>
                <th>备份文件</th>
                <th>大小</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($data_backups)): ?>
                <tr><td colspan="4" class="payment-channel-empty">暂无 SQL 备份，点击“立即创建备份”生成第一份。</td></tr>
            <?php else: ?>
                <?php foreach (array_slice((array) $data_backups, 0, 12) as $backup): ?>
                    <tr>
                        <td><code><?= htmlspecialchars((string) ($backup['file'] ?? '')) ?></code></td>
                        <td><?= htmlspecialchars(number_format(((float) ($backup['size'] ?? 0)) / 1024, 1)) ?> KB</td>
                        <td><?= htmlspecialchars((string) ($backup['created_at'] ?? '-')) ?></td>
                        <td>
                            <form method="post" action="/jxdjadmin#maintenance" class="inline-form" onsubmit="return confirm('确认恢复这个 SQL 备份吗？恢复前系统会自动备份当前 MySQL 数据。');">
                                <input type="hidden" name="admin_action" value="restore_data_backup">
                <?= $csrfField() ?>
                                <input type="hidden" name="admin_section" value="maintenance">
                                <input type="hidden" name="backup_file" value="<?= htmlspecialchars((string) ($backup['file'] ?? '')) ?>">
                                <button class="btn mini ghost" type="submit">恢复</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel admin-section <?= $activeAdminSection === 'homepage-template' ? 'is-active' : '' ?>" id="admin-section-homepage-template" data-admin-section="homepage-template" data-admin-primary="design">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">设计中心</span>
            <h2>首页模版</h2>
        </div>
        <span class="muted">短剧：<?= htmlspecialchars($homepageTemplateOptions[$homepageTemplate]['label']) ?> · 小说：<?= htmlspecialchars($novelHomepageTemplateOptions[$novelHomepageTemplate]['label']) ?></span>
    </div>
    <?php if (!empty($message)): ?><p class="notice"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <p class="muted">这里只保留首页系统模版选择。短剧首页和小说首页分开配置，预览按钮会打开临时效果，不会保存当前设置。</p>
    <form method="post" action="/jxdjadmin" class="stack">
        <input type="hidden" name="admin_action" value="update_homepage_template">
        <?= $csrfField() ?>
        <input type="hidden" name="admin_section" value="homepage-template">
        <div class="template-system-block">
            <div class="template-system-head">
                <div>
                    <span class="eyebrow">短剧系统模版</span>
                    <h3>短剧首页</h3>
                </div>
                <a class="btn ghost" href="/?preview_homepage_template=<?= rawurlencode($homepageTemplate) ?>" target="_blank" rel="noopener">预览当前短剧模板</a>
            </div>
            <div class="template-choice-grid">
                <?php foreach ($homepageTemplateOptions as $templateId => $option): ?>
                    <label class="template-choice <?= $homepageTemplate === $templateId ? 'is-selected' : '' ?>">
                        <input type="radio" name="homepage_template" value="<?= htmlspecialchars($templateId) ?>" <?= $homepageTemplate === $templateId ? 'checked' : '' ?>>
                        <span class="template-preview <?= htmlspecialchars($templateId) ?>">
                            <i></i><i></i><i></i>
                        </span>
                        <span class="template-copy">
                            <span class="template-meta-row">
                                <code><?= htmlspecialchars((string) ($option['code'] ?? strtoupper((string) $templateId))) ?></code>
                                <small><?= htmlspecialchars($option['tag']) ?></small>
                            </span>
                            <strong><?= htmlspecialchars($option['label']) ?></strong>
                            <em><?= htmlspecialchars($option['summary']) ?></em>
                        </span>
                        <span class="template-feature-row">
                            <?php foreach ($option['features'] as $feature): ?>
                                <b><?= htmlspecialchars($feature) ?></b>
                            <?php endforeach; ?>
                        </span>
                        <span class="template-action-row">
                            <a class="btn mini ghost" href="/?preview_homepage_template=<?= rawurlencode((string) $templateId) ?>" target="_blank" rel="noopener">预览</a>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="template-system-block">
            <div class="template-system-head">
                <div>
                    <span class="eyebrow">小说系统模版</span>
                    <h3>小说首页</h3>
                </div>
                <a class="btn ghost" href="/?route=novels&preview_novel_template=<?= rawurlencode($novelHomepageTemplate) ?>" target="_blank" rel="noopener">预览当前小说模板</a>
            </div>
            <div class="template-choice-grid">
                <?php foreach ($novelHomepageTemplateOptions as $templateId => $option): ?>
                    <label class="template-choice <?= $novelHomepageTemplate === $templateId ? 'is-selected' : '' ?>">
                        <input type="radio" name="novel_homepage_template" value="<?= htmlspecialchars($templateId) ?>" <?= $novelHomepageTemplate === $templateId ? 'checked' : '' ?>>
                        <span class="template-preview novel-<?= htmlspecialchars($templateId) ?>">
                            <i></i><i></i><i></i>
                        </span>
                        <span class="template-copy">
                            <span class="template-meta-row">
                                <code><?= htmlspecialchars((string) ($option['code'] ?? strtoupper((string) $templateId))) ?></code>
                                <small><?= htmlspecialchars($option['tag']) ?></small>
                            </span>
                            <strong><?= htmlspecialchars($option['label']) ?></strong>
                            <em><?= htmlspecialchars($option['summary']) ?></em>
                        </span>
                        <span class="template-feature-row">
                            <?php foreach ($option['features'] as $feature): ?>
                                <b><?= htmlspecialchars($feature) ?></b>
                            <?php endforeach; ?>
                        </span>
                        <span class="template-action-row">
                            <a class="btn mini ghost" href="/?route=novels&preview_novel_template=<?= rawurlencode((string) $templateId) ?>" target="_blank" rel="noopener">预览</a>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="design-save-row">
            <button class="btn primary" type="submit">保存首页模版</button>
        </div>
    </form>
</section>
<?php
$renderOrderMiniList = static function (array $items, string $emptyText, string $sectionId) use ($orderStatusForView, $userIndexForOrders, $money, $refundStatusLabels, $paymentDisplayForOrderView, $orderStatusMeta): void {
?>
    <?php if (empty($items)): ?>
        <p class="muted"><?= htmlspecialchars($emptyText) ?></p>
    <?php else: ?>
        <div class="order-table order-mini-table">
            <div class="order-row order-row-head">
                <span>订单信息</span>
                <span>金额</span>
                <span>状态</span>
                <span>操作</span>
            </div>
            <?php foreach ($items as $order): ?>
                <?php
                    $status = $orderStatusForView($order);
                    $refundRequests = array_values((array) ($order['refund_requests'] ?? []));
                    $hasPendingRefund = false;
                    $latestRefundRequest = null;
                    foreach ($refundRequests as $refundRequest) {
                        if (in_array((string) ($refundRequest['status'] ?? 'processing'), ['pending', 'processing'], true)) {
                            $hasPendingRefund = true;
                        }
                    }
                    foreach (array_reverse($refundRequests) as $refundRequest) {
                        $latestRefundRequest = $refundRequest;
                        break;
                    }
                    $statusMeta = $orderStatusMeta($status);
                    $statusLabel = $hasPendingRefund ? '退款处理中' : $statusMeta['label'];
                    $statusClass = $hasPendingRefund ? 'ember' : $statusMeta['class'];
                    if ($sectionId === 'refund-orders') {
                        $refundStatus = (string) (($latestRefundRequest['status'] ?? '') ?: ($order['refund_status'] ?? ''));
                        if ($refundStatus !== '') {
                            $statusLabel = $refundStatusLabels[$refundStatus] ?? $refundStatus;
                            $statusClass = match ($refundStatus) {
                                'success' => 'jade',
                                'failed' => 'ember',
                                default => '',
                            };
                        }
                    }
                    $user = $userIndexForOrders[(int) ($order['user_id'] ?? 0)] ?? [];
                    $phone = trim((string) ($user['phone'] ?? '')) ?: '未绑定';
                    $paymentDisplay = $paymentDisplayForOrderView($order);
                    $orderNo = (string) ($order['order_no'] ?? '');
                    $detailUrl = '/jxdjadmin?admin_section=orders&order_no=' . rawurlencode($orderNo) . '&open_order=' . rawurlencode($orderNo) . '&per_page=10&page=1#orders';
                ?>
                <div class="row-card order-row">
                    <div>
                        <strong><?= htmlspecialchars($orderNo) ?></strong>
                        <p class="muted">用户 <?= (int) ($order['user_id'] ?? 0) ?> · <?= htmlspecialchars($phone) ?> · <?= htmlspecialchars((string) ($paymentDisplay['method_name'] ?? '支付宝')) ?> · <?= htmlspecialchars((string) ($paymentDisplay['channel_name'] ?? '默认通道')) ?></p>
                    </div>
                    <div><strong><?= htmlspecialchars($money((float) ($order['amount'] ?? 0))) ?></strong></div>
                    <div><span class="pill <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($statusLabel) ?></span></div>
                    <div class="order-actions">
                        <a class="btn ghost" href="<?= htmlspecialchars($detailUrl) ?>">查看/处理</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php
};
$renderActionLogList = static function (array $logs, string $emptyText): void {
?>
    <?php if (empty($logs)): ?>
        <p class="muted"><?= htmlspecialchars($emptyText) ?></p>
    <?php else: ?>
        <div class="action-log-list">
            <?php foreach ($logs as $log): ?>
                <?php
                    $context = (array) ($log['context'] ?? []);
                    $contextParts = [];
                    foreach ($context as $contextKey => $contextValue) {
                        if ($contextValue === '' || $contextValue === null || is_array($contextValue)) {
                            continue;
                        }
                        $contextParts[] = $contextKey . ': ' . (string) $contextValue;
                    }
                ?>
                <article class="action-log-card">
                    <div class="action-log-head">
                        <strong><?= htmlspecialchars((string) ($log['order_no'] ?? '-')) ?></strong>
                        <span class="pill <?= !empty($log['success']) ? 'jade' : 'ember' ?>"><?= !empty($log['success']) ? '成功' : '未完成' ?></span>
                    </div>
                    <p><?= htmlspecialchars((string) ($log['message'] ?? '')) ?></p>
                    <?php if (!empty($contextParts)): ?>
                        <small><?= htmlspecialchars(implode(' · ', $contextParts)) ?></small>
                    <?php endif; ?>
                    <em><?= htmlspecialchars((string) ($log['created_at'] ?? '')) ?><?= !empty($log['admin_name']) ? ' · ' . htmlspecialchars((string) $log['admin_name']) : '' ?></em>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php
};
$renderOrderSubSection = static function (string $sectionId, array $data, bool $active) use ($paidOrders, $pendingOrders, $refundedOrders, $message, $orderStatusOptions, $renderOrderMiniList, $renderActionLogList, $recentPaymentQueryLogs, $csrfField): void {
    $config = $data['config'];
    $filters = $data['filters'];
    $queryParams = $data['query_params'];
    $pageUrl = static fn (int $page): string => '/jxdjadmin?' . $queryParams(['page' => $page]) . '#' . $sectionId;
    $resetUrl = '/jxdjadmin?admin_section=' . rawurlencode($sectionId) . '#' . $sectionId;
?>
    <section class="panel admin-section <?= $active ? 'is-active' : '' ?>" id="admin-section-<?= htmlspecialchars($sectionId) ?>" data-admin-section="<?= htmlspecialchars($sectionId) ?>" data-admin-primary="orders">
        <div class="section-title admin-section-title">
            <div>
                <span class="eyebrow">订单中心</span>
                <h2><?= htmlspecialchars((string) $config['title']) ?></h2>
            </div>
            <span class="muted">已支付 <?= $paidOrders ?> 笔 · 待支付 <?= $pendingOrders ?> 笔 · 已退款 <?= $refundedOrders ?> 笔</span>
        </div>
        <p class="muted"><?= htmlspecialchars((string) $config['summary']) ?></p>
        <?php if (!empty($message)): ?><p class="notice order-message"><?= htmlspecialchars($message) ?></p><?php endif; ?>
        <form class="order-filter-bar" method="get" action="/jxdjadmin">
            <input type="hidden" name="admin_section" value="<?= htmlspecialchars($sectionId) ?>">
            <?php if (($filters['payment_route_id'] ?? '') !== ''): ?>
                <input type="hidden" name="payment_route_id" value="<?= htmlspecialchars((string) $filters['payment_route_id']) ?>">
            <?php endif; ?>
            <label>订单号
                <input name="order_no" value="<?= htmlspecialchars($filters['order_no']) ?>" placeholder="输入订单号">
            </label>
            <label>用户查询
                <input name="user_keyword" value="<?= htmlspecialchars($filters['user_keyword']) ?>" placeholder="用户ID / 手机号">
            </label>
            <?php if (empty($data['status_locked'])): ?>
                <label>支付状态
                    <select name="status">
                        <?php foreach ($orderStatusOptions as $statusValue => $statusText): ?>
                            <option value="<?= htmlspecialchars($statusValue) ?>" <?= $filters['status'] === $statusValue ? 'selected' : '' ?>><?= htmlspecialchars($statusText) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php else: ?>
                <input type="hidden" name="status" value="all">
                <label>模块口径
                    <input value="<?= htmlspecialchars((string) $config['title']) ?>" disabled>
                </label>
            <?php endif; ?>
            <label>每页显示
                <select name="per_page">
                    <option value="10" <?= $filters['per_page'] === 10 ? 'selected' : '' ?>>10 条</option>
                    <option value="100" <?= $filters['per_page'] === 100 ? 'selected' : '' ?>>100 条</option>
                </select>
            </label>
            <input type="hidden" name="page" value="1">
            <div class="order-filter-actions">
                <button class="btn primary" type="submit">查询</button>
                <a class="btn ghost" href="<?= htmlspecialchars($resetUrl) ?>">重置</a>
            </div>
        </form>
        <?php if ($sectionId === 'payment-query'): ?>
            <div class="order-sub-stats">
                <div><span>当前筛选</span><strong><?= number_format((int) $data['total']) ?></strong><em>订单</em></div>
                <div><span>本页待查</span><strong><?= number_format(count($data['pending_integrated'])) ?></strong><em>已接入通道</em></div>
                <div><span>最近查询</span><strong><?= number_format(count($recentPaymentQueryLogs)) ?></strong><em>动作日志</em></div>
            </div>
        <?php endif; ?>
        <div class="order-toolbar">
            <div>
                <strong>共 <?= number_format((int) $data['total']) ?> 条</strong>
                <span class="muted">当前第 <?= number_format((int) $filters['page']) ?> / <?= number_format((int) $data['total_pages']) ?> 页，本页 <?= number_format(count($data['paginated'])) ?> 条</span>
            </div>
            <form class="inline-form" method="post" action="/jxdjadmin?<?= htmlspecialchars($queryParams()) ?>#<?= htmlspecialchars($sectionId) ?>" onsubmit="return confirm('确认批量查询当前页待支付订单吗？系统只会查询当前页待支付且已接入接口的订单。');">
                <input type="hidden" name="admin_action" value="bulk_query_order_payment">
                <?= $csrfField() ?>
                <input type="hidden" name="admin_section" value="<?= htmlspecialchars($sectionId) ?>">
                <?php foreach ($data['current_page_order_nos'] as $orderNo): ?>
                    <input type="hidden" name="order_nos[]" value="<?= htmlspecialchars($orderNo) ?>">
                <?php endforeach; ?>
                <button class="btn ghost" type="submit" <?= empty($data['pending_integrated']) ? 'disabled' : '' ?>>批量查询当前页待支付订单</button>
            </form>
            <form class="inline-form" method="post" action="/jxdjadmin?<?= htmlspecialchars($queryParams()) ?>#<?= htmlspecialchars($sectionId) ?>" onsubmit="return confirm('确认批量手动回传当前页已支付订单吗？系统会跳过测试订单和未支付订单。');">
                <input type="hidden" name="admin_action" value="bulk_manual_callback_orders">
                <?= $csrfField() ?>
                <input type="hidden" name="admin_section" value="<?= htmlspecialchars($sectionId) ?>">
                <input type="hidden" name="send_now" value="1">
                <?php foreach ($data['callback_order_nos'] as $orderNo): ?>
                    <input type="hidden" name="order_nos[]" value="<?= htmlspecialchars($orderNo) ?>">
                <?php endforeach; ?>
                <button class="btn ghost" type="submit" <?= empty($data['callback_order_nos']) ? 'disabled' : '' ?>>批量回传当前页已支付订单</button>
            </form>
        </div>
        <?php $renderOrderMiniList($data['paginated'], '暂无符合条件的订单。', $sectionId); ?>
        <?php if ($sectionId === 'payment-query'): ?>
            <div class="order-info-card order-section-log-card">
                <h4>最近支付查询日志</h4>
                <?php $renderActionLogList($recentPaymentQueryLogs, '暂无支付查询日志。'); ?>
            </div>
        <?php endif; ?>
        <nav class="order-pagination" aria-label="<?= htmlspecialchars((string) $config['title']) ?>分页">
            <a class="btn ghost <?= $filters['page'] <= 1 ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($pageUrl(1)) ?>">首页</a>
            <a class="btn ghost <?= $filters['page'] <= 1 ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($pageUrl(max(1, $filters['page'] - 1))) ?>">上一页</a>
            <span>第 <?= number_format((int) $filters['page']) ?> / <?= number_format((int) $data['total_pages']) ?> 页</span>
            <a class="btn ghost <?= $filters['page'] >= $data['total_pages'] ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($pageUrl(min((int) $data['total_pages'], $filters['page'] + 1))) ?>">下一页</a>
            <a class="btn ghost <?= $filters['page'] >= $data['total_pages'] ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($pageUrl((int) $data['total_pages'])) ?>">末页</a>
        </nav>
    </section>
<?php
};
foreach (['pending-orders', 'paid-orders', 'refund-orders', 'payment-query'] as $orderSubSectionId) {
    $renderOrderSubSection($orderSubSectionId, $orderSectionData[$orderSubSectionId], $activeAdminSection === $orderSubSectionId);
}
?>
<section class="panel admin-section <?= $activeAdminSection === 'repair-orders' ? 'is-active' : '' ?>" id="admin-section-repair-orders" data-admin-section="repair-orders" data-admin-primary="orders">
    <div class="section-title admin-section-title">
        <div>
            <span class="eyebrow">订单中心</span>
            <h2>补单记录</h2>
        </div>
        <span class="muted">操作日志 <?= number_format(count($repairActionLogs)) ?> 条 · 异常订单 <?= number_format(count($repairExceptionOrders)) ?> 条</span>
    </div>
    <p class="muted">这里同时展示后台查询/退款动作日志，以及需要人工关注的异常待支付订单。</p>
    <div class="repair-grid">
        <div class="order-info-card">
            <h4>异常待处理订单</h4>
            <?php $renderOrderMiniList(array_slice($repairExceptionOrders, 0, 10), '暂无异常待处理订单。', 'repair-orders'); ?>
        </div>
        <div class="order-info-card">
            <h4>最近操作日志</h4>
            <?php $renderActionLogList($repairActionLogs, '暂无补单或查询操作记录。'); ?>
        </div>
    </div>
</section>
<?php foreach ($adminSectionMeta as $sectionId => $meta): ?>
    <?php if (in_array($sectionId, $implementedAdminSections, true)) { continue; } ?>
    <section class="panel admin-section admin-placeholder <?= $activeAdminSection === $sectionId ? 'is-active' : '' ?>" id="admin-section-<?= htmlspecialchars($sectionId) ?>" data-admin-section="<?= htmlspecialchars($sectionId) ?>" data-admin-primary="<?= htmlspecialchars($meta['primary']) ?>">
        <span class="eyebrow"><?= htmlspecialchars($meta['primary_label']) ?></span>
        <h2><?= htmlspecialchars($meta['label']) ?></h2>
        <p class="muted">这个二级模块已放入后台导航结构，后续可以在这里接入独立列表、筛选、表单和统计图。当前先保留为清晰占位，避免功能都挤在一个页面里。</p>
        <div class="placeholder-grid">
            <div class="system-item"><strong>独立页面</strong><span>已预留菜单入口和页面容器</span></div>
            <div class="system-item"><strong>可接数据</strong><span>后续可按模块接入真实业务表格</span></div>
            <div class="system-item"><strong>不影响现有功能</strong><span>订单、支付、内容管理仍保持可用</span></div>
        </div>
    </section>
<?php endforeach; ?>
    </div>
</section>
<script>
(() => {
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }

    const shell = document.querySelector('[data-active-admin-section]');
    if (!shell) {
        return;
    }

    const sections = Array.from(document.querySelectorAll('[data-admin-section]'));
    const links = Array.from(document.querySelectorAll('[data-admin-target]'));
    const secondaryGroups = Array.from(document.querySelectorAll('[data-secondary-group]'));
    const secondaryTitle = document.querySelector('[data-secondary-title]');
    const adminMenu = document.querySelector('.admin-menu');
    const adminMenuToggles = Array.from(document.querySelectorAll('[data-admin-menu-toggle]'));
    const adminMenuToggle = adminMenuToggles[0] ?? null;
    const adminMenuCloseTargets = Array.from(document.querySelectorAll('[data-admin-menu-close]'));
    const adminMobileCurrent = document.querySelector('[data-admin-mobile-current]');
    const adminMobilePrimary = document.querySelector('[data-admin-mobile-primary]');
    const fallback = shell.dataset.activeAdminSection || 'overview';
    const legacySectionMap = {
        'today-trade': 'overview',
        'system-notice': 'popup-notice',
        'preview-config': 'dramas',
        'price-config': 'dramas',
        'guest-users': 'users',
        'member-users': 'users',
        'bind-records': 'users',
        'security-config': 'settings',
        'page-decoration': 'homepage-template',
    };
    const sectionScrollPositions = new Map();
    let currentSection = fallback;
    const setAdminMenuOpen = (open) => {
        if (!adminMenu || !adminMenuToggle) {
            return;
        }
        adminMenu.classList.toggle('is-open', open);
        document.body.classList.toggle('has-admin-mobile-drawer', open);
        adminMenuToggles.forEach((toggle) => {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        adminMenuCloseTargets.forEach((target) => {
            target.hidden = !open;
        });
    };
    const keepScrollPosition = (scrollTop) => {
        const applyScroll = () => {
            const maxTop = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
            const nextTop = Math.min(scrollTop, maxTop);
            if (Math.abs(window.scrollY - nextTop) > 1) {
                window.scrollTo(window.scrollX, nextTop);
            }
        };

        requestAnimationFrame(() => {
            applyScroll();
            requestAnimationFrame(applyScroll);
        });
        window.setTimeout(applyScroll, 0);
        window.setTimeout(applyScroll, 80);
        window.setTimeout(applyScroll, 180);
    };

    const showSection = (sectionId, updateHash = true) => {
        sectionId = legacySectionMap[sectionId] || sectionId;
        const previous = currentSection || shell.dataset.activeAdminSection || fallback;
        const currentScrollTop = window.scrollY;
        const targetSection = sections.find((section) => section.dataset.adminSection === sectionId) || sections.find((section) => section.dataset.adminSection === 'overview');
        const next = targetSection?.dataset.adminSection || 'overview';
        const primary = targetSection?.dataset.adminPrimary || 'dashboard';
        const primaryLink = document.querySelector(`.admin-topbar [data-admin-primary="${primary}"]`);
        const sameSection = previous === next;

        if (!sameSection) {
            sectionScrollPositions.set(previous, currentScrollTop);
        }

        sections.forEach((section) => section.classList.toggle('is-active', section.dataset.adminSection === next));
        secondaryGroups.forEach((group) => group.classList.toggle('is-active', group.dataset.secondaryGroup === primary));
        links.forEach((link) => {
            const isTopLink = !!link.closest('.admin-topbar');
            const isPrimaryGridLink = !!link.closest('.admin-mobile-primary-grid');
            const active = (isTopLink || isPrimaryGridLink) ? link.dataset.adminPrimary === primary : link.dataset.adminTarget === next;
            link.classList.toggle('is-active', active);
        });

        currentSection = next;
        shell.dataset.activeAdminSection = next;
        shell.dataset.activeAdminPrimary = primary;
        if (secondaryTitle && primaryLink) {
            secondaryTitle.textContent = primaryLink.textContent.trim();
        }
        if (adminMobileCurrent) {
            adminMobileCurrent.textContent = targetSection?.querySelector('.section-title h2, h2')?.textContent?.trim()
                || document.querySelector(`[data-admin-target="${next}"] [data-admin-menu-label]`)?.textContent?.trim()
                || next;
        }
        if (adminMobilePrimary && primaryLink) {
            adminMobilePrimary.textContent = primaryLink.textContent.trim();
        }
        if (adminMenu && adminMenuToggle && window.matchMedia('(max-width: 820px)').matches) {
            setAdminMenuOpen(false);
        }
        if (updateHash && window.location.hash !== `#${next}`) {
            history.replaceState(null, '', `#${next}`);
        }

        const nextScrollTop = sameSection
            ? currentScrollTop
            : (sectionScrollPositions.has(next) ? sectionScrollPositions.get(next) : (updateHash ? 0 : currentScrollTop));
        keepScrollPosition(nextScrollTop);
    };

    document.addEventListener('click', (event) => {
        const source = event.target instanceof Element ? event.target.closest('[data-admin-target]') : null;
        if (!source || (!shell.contains(source) && !source.closest('.admin-topbar'))) {
            return;
        }

        const target = source.dataset.adminTarget;
        if (!target) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        showSection(target);
    }, true);

    adminMenuToggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const open = !adminMenu?.classList.contains('is-open');
            setAdminMenuOpen(open);
        });
    });
    adminMenuCloseTargets.forEach((target) => {
        target.addEventListener('click', () => setAdminMenuOpen(false));
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && adminMenu?.classList.contains('is-open')) {
            setAdminMenuOpen(false);
        }
    });

    document.querySelectorAll('.admin-panel form').forEach((form) => {
        form.addEventListener('submit', () => {
            if ((form.getAttribute('method') || 'get').toLowerCase() === 'get') {
                return;
            }
            if (form.querySelector('input[name="admin_section"]')) {
                return;
            }
            const section = form.closest('[data-admin-section]');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'admin_section';
            input.value = section?.dataset.adminSection || shell.dataset.activeAdminSection || 'overview';
            form.appendChild(input);
        });
    });

    const worksBulkForm = document.querySelector('[data-works-bulk-form]');
    const worksCheckboxes = Array.from(document.querySelectorAll('[data-works-checkbox]'));
    const worksSelectAll = document.querySelector('[data-works-select-all]');
    const worksSelectedCount = document.querySelector('[data-works-selected-count]');
    const worksBulkButtons = Array.from(document.querySelectorAll('[data-works-bulk-submit]'));
    const worksBulkAdminAction = worksBulkForm?.querySelector('[data-works-bulk-admin-action]');
    const worksBulkStatus = worksBulkForm?.querySelector('[data-works-bulk-status]');
    const updateWorksBulkState = () => {
        const selected = worksCheckboxes.filter((checkbox) => checkbox.checked);
        if (worksSelectedCount) {
            worksSelectedCount.textContent = `已选择 ${selected.length} 个作品`;
        }
        worksBulkButtons.forEach((button) => {
            button.disabled = selected.length === 0;
        });
        if (worksSelectAll) {
            worksSelectAll.checked = selected.length > 0 && selected.length === worksCheckboxes.length;
            worksSelectAll.indeterminate = selected.length > 0 && selected.length < worksCheckboxes.length;
            worksSelectAll.disabled = worksCheckboxes.length === 0;
        }
    };
    worksSelectAll?.addEventListener('change', () => {
        worksCheckboxes.forEach((checkbox) => {
            checkbox.checked = worksSelectAll.checked;
        });
        updateWorksBulkState();
    });
    worksCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', updateWorksBulkState);
    });
    worksBulkButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (!worksBulkForm || button.disabled) {
                return;
            }
            const bulkAction = button.dataset.worksBulkAction || '';
            if (worksBulkAdminAction) {
                worksBulkAdminAction.value = bulkAction === 'delete' ? 'bulk_delete_works' : 'bulk_update_work_status';
            }
            if (worksBulkStatus) {
                worksBulkStatus.value = bulkAction === 'delete' ? '' : bulkAction;
            }
            if (typeof worksBulkForm.requestSubmit === 'function') {
                worksBulkForm.requestSubmit();
                return;
            }
            const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
            if (worksBulkForm.dispatchEvent(submitEvent)) {
                worksBulkForm.submit();
            }
        });
    });
    worksBulkForm?.addEventListener('submit', (event) => {
        const adminActionValue = worksBulkAdminAction?.value || '';
        const actionValue = adminActionValue === 'bulk_delete_works'
            ? 'delete'
            : (worksBulkStatus?.value || event.submitter?.dataset?.worksBulkAction || event.submitter?.value || '');
        const selectedCount = worksCheckboxes.filter((checkbox) => checkbox.checked).length;
        if (selectedCount <= 0) {
            event.preventDefault();
            updateWorksBulkState();
            return;
        }
        if (!['online', 'offline', 'delete'].includes(actionValue)) {
            event.preventDefault();
            alert('请选择批量上架、批量下架或批量删除。');
            return;
        }
        const actionText = actionValue === 'delete' ? '删除' : (actionValue === 'offline' ? '下架' : '上架');
        const confirmText = actionValue === 'delete'
            ? `确认删除 ${selectedCount} 个作品吗？作品会从内容库移除，历史订单和权益记录会保留。`
            : `确认批量${actionText} ${selectedCount} 个作品吗？`;
        if (!confirm(confirmText)) {
            event.preventDefault();
        }
    });
    updateWorksBulkState();

    const moveFloatingLayerToBody = (layer) => {
        if (layer && layer.parentElement !== document.body) {
            document.body.appendChild(layer);
        }
    };
    const adminDrawers = Array.from(document.querySelectorAll('[data-admin-drawer]'));
    let activeAdminDrawer = null;
    const focusAdminDrawer = (drawer) => {
        const target = drawer.querySelector('[data-admin-drawer-close], input:not([type="hidden"]), select, textarea, button:not([disabled]), a[href]');
        window.setTimeout(() => target?.focus(), 30);
    };
    const closeAdminDrawer = () => {
        if (!activeAdminDrawer) {
            return;
        }
        activeAdminDrawer.hidden = true;
        activeAdminDrawer.classList.remove('is-open');
        document.body.classList.remove('has-admin-drawer');
        activeAdminDrawer = null;
    };
    const openAdminDrawer = (drawerId) => {
        const drawer = document.getElementById(drawerId || '');
        if (!drawer || !drawer.matches('[data-admin-drawer]')) {
            return;
        }
        closeAdminDrawer();
        moveFloatingLayerToBody(drawer);
        drawer.hidden = false;
        drawer.classList.add('is-open');
        document.body.classList.add('has-admin-drawer');
        activeAdminDrawer = drawer;
        focusAdminDrawer(drawer);
    };
    adminDrawers.forEach(moveFloatingLayerToBody);
    document.addEventListener('click', (event) => {
        const openButton = event.target instanceof Element ? event.target.closest('[data-admin-drawer-open]') : null;
        if (openButton) {
            event.preventDefault();
            openAdminDrawer(openButton.dataset.adminDrawerOpen || '');
            return;
        }
        const closeButton = event.target instanceof Element ? event.target.closest('[data-admin-drawer-close]') : null;
        if (closeButton && closeButton.closest('[data-admin-drawer]')) {
            event.preventDefault();
            closeAdminDrawer();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeAdminDrawer) {
            event.preventDefault();
            event.stopImmediatePropagation();
            closeAdminDrawer();
        }
    });
    const orderModalLayers = Array.from(document.querySelectorAll('[data-order-modal]'));
    const refundDialogLayers = Array.from(document.querySelectorAll('[data-refund-dialog]'));
    orderModalLayers.forEach(moveFloatingLayerToBody);
    refundDialogLayers.forEach(moveFloatingLayerToBody);

    const channelSection = document.querySelector('#admin-section-payment-channel');
    const routeDialog = document.querySelector('[data-payment-route-dialog]');
    const routeDialogForm = routeDialog?.querySelector('.payment-route-dialog-form');
    const routeDialogTitle = routeDialog?.querySelector('[data-route-dialog-title]');
    const routeKeyDrawer = document.querySelector('[data-payment-route-key-drawer]');
    const routeKeyForm = routeKeyDrawer?.querySelector('.payment-route-key-form');
    const routeKeyTitle = routeKeyDrawer?.querySelector('[data-route-key-title]');
    const routeKeySubtitle = routeKeyDrawer?.querySelector('[data-route-key-subtitle]');
    const routeKeyNote = routeKeyDrawer?.querySelector('[data-route-key-note]');
    const routeDefaultsTemplate = document.querySelector('[data-payment-route-defaults]');
    const channelRows = Array.from(document.querySelectorAll('[data-payment-channel-row]'));
    const channelVisibleCount = document.querySelector('[data-payment-channel-visible-count]');
    const channelTotalAmount = document.querySelector('[data-channel-total-amount]');
    const channelFilterBox = document.querySelector('[data-payment-channel-filter]');
    const channelFilterExtra = document.querySelector('[data-channel-filter-extra]');
    const channelFilterToggleText = document.querySelector('[data-channel-filter-toggle-text]');
    const channelActionDialog = document.querySelector('[data-channel-action-dialog]');
    const channelActionTitle = channelActionDialog?.querySelector('[data-channel-action-title]');
    const channelActionRoute = channelActionDialog?.querySelector('[data-channel-action-route]');
    const channelActionMessage = channelActionDialog?.querySelector('[data-channel-action-message]');
    const paymentTestDialog = document.querySelector('[data-payment-test-dialog]');
    const paymentTestForm = paymentTestDialog?.querySelector('.payment-test-form');
    const paymentTestAmount = paymentTestDialog?.querySelector('[data-payment-test-amount]');
    let activeChannelStatus = 'all';
    let routeDefaults = {};
    try {
        const routeDefaultsRaw = routeDefaultsTemplate?.content?.textContent || routeDefaultsTemplate?.innerHTML || '{}';
        routeDefaults = JSON.parse(routeDefaultsRaw.trim() || '{}');
    } catch (error) {
        routeDefaults = {};
    }
    moveFloatingLayerToBody(routeDialog);
    moveFloatingLayerToBody(routeKeyDrawer);
    moveFloatingLayerToBody(channelActionDialog);
    moveFloatingLayerToBody(paymentTestDialog);
    const routeProviderMeta = {
        jingxiu: {
            name: '精秀聚合支付',
            api_url: '',
            sign_type: 'RSA2',
            trade_type: 'alipayWap',
            merchant_label: '商户号 mchid',
            channel_id_label: '上游通道 ID（数字）',
            channel_code_label: '通道编码',
            hint: '精秀聚合支付需要 mchid、RSA2 商户私钥和平台公钥；SUCCESS / CALL_FAIL 都会按支付成功处理。',
            secret_mode: 'rsa',
        },
        superpay: {
            name: '超级支付',
            api_url: 'http://payjf.cn',
            sign_type: 'MD5',
            trade_type: 'alipay',
            merchant_label: '商户号 pid',
            channel_id_label: '上游通道 ID（数字）',
            channel_code_label: '通道编码',
            hint: '超级支付 merchant_id 对应 pid，payment_method 对应 paytype_code，MD5 使用接口密钥，RSA 使用商户私钥。',
            secret_mode: 'md5',
        },
    };
    const routeMethodMeta = {
        <?php foreach ($paymentMethodOptions as $methodCode => $methodMeta): ?>
        <?= json_encode((string) $methodCode, JSON_UNESCAPED_UNICODE) ?>: {
            name: <?= json_encode((string) $methodMeta['name'], JSON_UNESCAPED_UNICODE) ?>,
            trade_type: <?= json_encode((string) $methodCode, JSON_UNESCAPED_UNICODE) ?>,
        },
        <?php endforeach; ?>
    };

    const channelFilters = {
        name: document.querySelector('[data-channel-filter-name]'),
        merchant: document.querySelector('[data-channel-filter-merchant]'),
        provider: document.querySelector('[data-channel-filter-provider]'),
        method: document.querySelector('[data-channel-filter-method]'),
        status: document.querySelector('[data-channel-filter-status]'),
        trade: document.querySelector('[data-channel-filter-trade]'),
    };
    const normalizeText = (value) => String(value || '').trim().toLowerCase();
    const applyChannelFilters = () => {
        const name = normalizeText(channelFilters.name?.value);
        const merchant = normalizeText(channelFilters.merchant?.value);
        const provider = normalizeText(channelFilters.provider?.value);
        const method = channelFilters.method?.value || 'all';
        const selectStatus = channelFilters.status?.value || 'all';
        const status = activeChannelStatus !== 'all' ? activeChannelStatus : selectStatus;
        const trade = channelFilters.trade?.value || 'all';
        let visible = 0;
        let amount = 0;

        channelRows.forEach((row) => {
            const rowName = normalizeText(row.dataset.channelName);
            const rowMerchant = normalizeText(row.dataset.channelMerchant);
            const rowProvider = normalizeText(row.dataset.channelProvider);
            const statusOk = status === 'all' || row.dataset.channelStatus === status;
            const tradeOk = trade === 'all' || row.dataset.channelTrade === trade;
            const methodOk = method === 'all' || row.dataset.channelMethod === method;
            const matched = (!name || rowName.includes(name))
                && (!merchant || rowMerchant.includes(merchant))
                && (!provider || rowProvider.includes(provider))
                && methodOk
                && statusOk
                && tradeOk;
            row.hidden = !matched;
            if (matched) {
                visible++;
                amount += Number(row.dataset.channelAmount || 0);
            }
        });

        if (channelVisibleCount) {
            channelVisibleCount.textContent = `当前显示 ${visible} 条`;
        }
        if (channelTotalAmount) {
            channelTotalAmount.textContent = amount.toFixed(2);
        }
    };

    document.querySelectorAll('[data-channel-filter-apply]').forEach((button) => {
        button.addEventListener('click', applyChannelFilters);
    });
    document.querySelectorAll('[data-channel-filter-reset]').forEach((button) => {
        button.addEventListener('click', () => {
            Object.values(channelFilters).forEach((field) => {
                if (!field) return;
                field.value = field.tagName === 'SELECT' ? 'all' : '';
            });
            activeChannelStatus = 'all';
            document.querySelectorAll('[data-channel-status-filter]').forEach((item) => {
                item.classList.toggle('is-active', item.dataset.channelStatusFilter === 'all');
            });
            applyChannelFilters();
        });
    });
    document.querySelector('[data-channel-filter-toggle]')?.addEventListener('click', () => {
        const expanded = channelFilterExtra?.hidden !== false;
        if (channelFilterExtra) {
            channelFilterExtra.hidden = !expanded;
        }
        channelFilterBox?.classList.toggle('is-collapsed', !expanded);
        if (channelFilterToggleText) {
            channelFilterToggleText.textContent = expanded ? '收起' : '展开';
        }
    });
    Object.values(channelFilters).forEach((field) => {
        field?.addEventListener('change', applyChannelFilters);
        field?.addEventListener('input', () => {
            window.clearTimeout(field._channelFilterTimer);
            field._channelFilterTimer = window.setTimeout(applyChannelFilters, 160);
        });
    });
    document.querySelectorAll('[data-channel-status-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            activeChannelStatus = button.dataset.channelStatusFilter || 'all';
            if (channelFilters.status) {
                channelFilters.status.value = activeChannelStatus;
            }
            document.querySelectorAll('[data-channel-status-filter]').forEach((item) => {
                item.classList.toggle('is-active', item === button);
            });
            applyChannelFilters();
        });
    });
    document.querySelector('[data-payment-channel-dismiss]')?.addEventListener('click', (event) => {
        event.currentTarget.closest('.payment-channel-notice')?.remove();
    });

    let activeRouteDialog = null;
    let activeRouteKeyDrawer = null;
    let activeChannelActionDialog = null;
    let activePaymentTestDialog = null;
    const closeChannelActionDialog = () => {
        if (!activeChannelActionDialog) {
            return;
        }
        activeChannelActionDialog.hidden = true;
        activeChannelActionDialog.classList.remove('is-open');
        document.body.classList.remove('has-channel-action-dialog');
        activeChannelActionDialog = null;
    };
    const closePaymentTestDialog = () => {
        if (!activePaymentTestDialog) {
            return;
        }
        activePaymentTestDialog.hidden = true;
        activePaymentTestDialog.classList.remove('is-open');
        document.body.classList.remove('has-payment-test-dialog');
        activePaymentTestDialog = null;
    };
    const openChannelActionDialog = (button) => {
        if (!channelActionDialog) {
            return;
        }
        const row = button.closest('[data-payment-channel-row]');
        const title = button.dataset.channelPlaceholderTitle || button.textContent.trim() || '操作提示';
        const message = button.dataset.channelPlaceholder || '该能力已预留，暂未接入真实接口。';
        const routeName = row?.querySelector('td:nth-child(2) strong')?.textContent?.trim()
            || row?.dataset.channelName
            || '当前页面';
        if (channelActionTitle) {
            channelActionTitle.textContent = title;
        }
        if (channelActionRoute) {
            channelActionRoute.textContent = routeName;
        }
        if (channelActionMessage) {
            channelActionMessage.textContent = message;
        }
        moveFloatingLayerToBody(channelActionDialog);
        channelActionDialog.hidden = false;
        channelActionDialog.classList.add('is-open');
        document.body.classList.add('has-channel-action-dialog');
        activeChannelActionDialog = channelActionDialog;
        channelActionDialog.querySelector('[data-channel-action-close]')?.focus();
    };
    const setPaymentTestField = (name, value) => {
        if (!paymentTestDialog) {
            return;
        }
        const field = paymentTestDialog.querySelector(`[data-payment-test-field="${name}"]`);
        if (!field) {
            return;
        }
        if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
            field.value = value ?? '';
            return;
        }
        field.textContent = value ?? '';
    };
    const openPaymentTestDialog = (route) => {
        if (!paymentTestDialog || !paymentTestForm) {
            return;
        }
        const data = { ...routeDefaults, ...route };
        paymentTestForm.reset();
        setPaymentTestField('payment_route_id', data.id || data.payment_route_id || '');
        setPaymentTestField('channel_name', data.channel_name || '当前支付通道');
        setPaymentTestField('route_meta', `${data.payment_method_name || '支付方式'} · ${data.provider_name || data.provider || '支付服务商'} · ${data.enabled ? '已启用' : '未启用'}`);
        if (paymentTestAmount) {
            paymentTestAmount.value = '0.01';
        }
        moveFloatingLayerToBody(paymentTestDialog);
        paymentTestDialog.hidden = false;
        paymentTestDialog.classList.add('is-open');
        document.body.classList.add('has-payment-test-dialog');
        activePaymentTestDialog = paymentTestDialog;
        paymentTestDialog.querySelector('input[name="test_amount"]')?.focus();
        paymentTestDialog.querySelector('input[name="test_amount"]')?.select();
    };
    const closeRouteDialog = () => {
        if (!activeRouteDialog) {
            return;
        }
        activeRouteDialog.hidden = true;
        activeRouteDialog.classList.remove('is-open');
        document.body.classList.remove('has-payment-route-dialog');
        activeRouteDialog = null;
    };
    const closeRouteKeyDrawer = () => {
        if (!activeRouteKeyDrawer) {
            return;
        }
        activeRouteKeyDrawer.hidden = true;
        activeRouteKeyDrawer.classList.remove('is-open');
        document.body.classList.remove('has-payment-route-key-drawer');
        activeRouteKeyDrawer = null;
    };
    const setRouteField = (name, value) => {
        if (!routeDialogForm) {
            return;
        }
        const fields = Array.from(routeDialogForm.querySelectorAll(`[data-route-field="${name}"]`));
        const field = fields[0];
        if (!field) {
            return;
        }
        fields.forEach((item) => {
            if (item.type === 'checkbox') {
                item.checked = Boolean(value);
                return;
            }
            if (item.type === 'radio') {
                item.checked = String(item.value) === String(value);
                return;
            }
            item.value = value ?? '';
        });
    };
    const setKeyField = (name, value) => {
        if (!routeKeyForm) {
            return;
        }
        const field = routeKeyForm.querySelector(`[data-key-field="${name}"]`);
        if (!field) {
            return;
        }
        field.value = value ?? '';
    };
    const getRouteFieldValue = (name) => {
        if (!routeDialogForm) {
            return '';
        }
        const checked = routeDialogForm.querySelector(`[data-route-field="${name}"]:checked`);
        if (checked) {
            return checked.value || '';
        }
        const field = routeDialogForm.querySelector(`[data-route-field="${name}"]`);
        return field?.value || '';
    };
    const setRouteText = (selector, value) => {
        routeDialogForm?.querySelectorAll(selector).forEach((node) => {
            node.textContent = value;
        });
    };
    const updateRouteChoiceState = () => {
        if (!routeDialogForm) {
            return;
        }
        const method = getRouteFieldValue('payment_method') || 'alipay';
        const provider = getRouteFieldValue('provider') || 'superpay';
        routeDialogForm.querySelectorAll('[data-method-card]').forEach((card) => {
            const input = card.querySelector('input[type="radio"]');
            card.classList.toggle('is-active', input?.checked === true);
        });
        routeDialogForm.querySelectorAll('[data-provider-card]').forEach((card) => {
            const input = card.querySelector('input[type="radio"]');
            card.classList.toggle('is-active', input?.checked === true);
        });
        const methodMeta = routeMethodMeta[method] || routeMethodMeta.alipay || { name: '支付宝', trade_type: 'alipay' };
        const providerMeta = routeProviderMeta[provider] || routeProviderMeta.superpay;
        setRouteField('payment_method_name', methodMeta.name || method);
        setRouteField('provider_name', providerMeta.name || provider);
        setRouteText('[data-provider-label="merchant_id"]', providerMeta.merchant_label || '商户号');
        setRouteText('[data-provider-label="pay_channel_id"]', providerMeta.channel_id_label || '上游通道 ID（数字）');
        setRouteText('[data-provider-label="channel_code"]', providerMeta.channel_code_label || '通道编码');
        const hint = routeDialogForm.querySelector('[data-route-provider-hint]');
        if (hint) {
            hint.textContent = providerMeta.hint || '请填写当前服务商要求的接口参数。';
        }
        const signType = String(getRouteFieldValue('sign_type') || providerMeta.sign_type || '').toUpperCase();
        const showMd5 = signType === 'MD5';
        const showRsa = !showMd5;
        routeDialogForm.querySelectorAll('[data-secret-group="md5"]').forEach((node) => {
            node.hidden = !showMd5;
        });
        routeDialogForm.querySelectorAll('[data-secret-group="rsa"]').forEach((node) => {
            node.hidden = !showRsa;
        });
    };
    const applyRouteProviderDefaults = (force = false) => {
        if (!routeDialogForm) {
            return;
        }
        const provider = getRouteFieldValue('provider') || 'superpay';
        const method = getRouteFieldValue('payment_method') || 'alipay';
        const providerMeta = routeProviderMeta[provider] || routeProviderMeta.superpay;
        if (force || !getRouteFieldValue('api_url')) {
            setRouteField('api_url', providerMeta.api_url || '');
        }
        if (force || !getRouteFieldValue('sign_type')) {
            setRouteField('sign_type', providerMeta.sign_type || 'MD5');
        }
        if (force || !getRouteFieldValue('trade_type')) {
            setRouteField('trade_type', provider === 'jingxiu' ? (providerMeta.trade_type || 'alipayWap') : method);
        }
        updateRouteChoiceState();
    };
    routeDialogForm?.querySelectorAll('[data-route-field="payment_method"]').forEach((field) => {
        field.addEventListener('change', () => {
            const provider = getRouteFieldValue('provider') || 'superpay';
            const method = getRouteFieldValue('payment_method') || 'alipay';
            setRouteField('trade_type', provider === 'jingxiu' ? (routeProviderMeta.jingxiu.trade_type || 'alipayWap') : method);
            updateRouteChoiceState();
        });
    });
    routeDialogForm?.querySelectorAll('[data-route-field="provider"]').forEach((field) => {
        field.addEventListener('change', () => applyRouteProviderDefaults(true));
    });
    routeDialogForm?.querySelector('[data-route-field="sign_type"]')?.addEventListener('change', updateRouteChoiceState);
    routeDialogForm?.querySelector('[data-route-field="pay_channel_id"]')?.addEventListener('input', (event) => {
        event.currentTarget.value = String(event.currentTarget.value || '').replace(/\D+/g, '');
    });
    routeKeyForm?.querySelector('[data-key-field="pay_channel_id"]')?.addEventListener('input', (event) => {
        event.currentTarget.value = String(event.currentTarget.value || '').replace(/\D+/g, '');
    });
    const openRouteDialog = (route, mode = 'edit') => {
        if (!routeDialog || !routeDialogForm) {
            return;
        }
        const data = { ...routeDefaults, ...route };
        routeDialogForm.reset();
        setRouteField('payment_route_id', mode === 'create' ? '' : (data.id || data.payment_route_id || ''));
        setRouteField('route_id_display', mode === 'create' ? '保存后自动生成' : (data.id || data.payment_route_id || ''));
        setRouteField('create_new_route', mode === 'create' ? '1' : '');
        ['provider', 'provider_name', 'channel_name', 'notes', 'payment_method', 'payment_method_name', 'trade_type', 'api_url', 'merchant_id', 'pay_channel_id', 'channel_code', 'sign_type', 'request_timeout', 'daily_amount_limit', 'daily_order_limit', 'frequency_window', 'frequency_count', 'min_amount', 'max_amount', 'open_start_hour', 'open_end_hour'].forEach((key) => {
            setRouteField(key, data[key] ?? '');
        });
        routeDialogForm.querySelectorAll('[data-route-secret]').forEach((field) => {
            const key = field.dataset.routeSecret;
            const saved = Boolean(data[`has_${key}`]);
            field.value = '';
            field.placeholder = saved ? '已保存，留空不修改' : field.placeholder.replace('已保存，', '');
        });
        if (routeDialogTitle) {
            routeDialogTitle.textContent = mode === 'create' ? '新增支付通道' : '编辑支付通道';
        }
        applyRouteProviderDefaults(mode === 'create');
        moveFloatingLayerToBody(routeDialog);
        routeDialog.hidden = false;
        routeDialog.classList.add('is-open');
        document.body.classList.add('has-payment-route-dialog');
        activeRouteDialog = routeDialog;
        routeDialog.querySelector('input[name="channel_name"]')?.focus();
    };
    const openRouteKeyDrawer = (route) => {
        if (!routeKeyDrawer || !routeKeyForm) {
            return;
        }
        const data = { ...routeDefaults, ...route };
        routeKeyForm.reset();
        setKeyField('payment_route_id', data.id || data.payment_route_id || '');
        ['api_url', 'merchant_id', 'pay_channel_id', 'channel_code', 'sign_type', 'request_timeout'].forEach((key) => {
            setKeyField(key, data[key] ?? '');
        });
        routeKeyForm.querySelectorAll('[data-key-secret]').forEach((field) => {
            const key = field.dataset.keySecret;
            const saved = Boolean(data[`has_${key}`]);
            field.value = '';
            field.placeholder = saved ? '已保存，留空不修改' : field.placeholder.replace('已保存，', '');
        });
        const channelName = data.channel_name || '支付通道';
        const providerName = data.provider_name || data.provider || '支付服务商';
        if (routeKeyTitle) {
            routeKeyTitle.textContent = `配置对接密钥 - ${channelName}`;
        }
        if (routeKeySubtitle) {
            routeKeySubtitle.textContent = `${providerName} · ${data.payment_method_name || '支付方式'} · 留空不会覆盖旧密钥`;
        }
        if (routeKeyNote) {
            routeKeyNote.textContent = data.provider === 'superpay'
                ? '超级支付：merchant_id 对应 pid，payment_method 对应 paytype_code，channel_id 可填通道 ID。'
                : '精秀聚合支付：merchant_id 对应 mchid，签名通常使用 RSA2，平台公钥用于回调验签。';
        }
        const keyMeta = routeProviderMeta[data.provider] || routeProviderMeta.superpay;
        routeKeyForm.querySelectorAll('[data-key-label="merchant_id"]').forEach((node) => {
            node.textContent = keyMeta.merchant_label || '商户号';
        });
        routeKeyForm.querySelectorAll('[data-key-label="pay_channel_id"]').forEach((node) => {
            node.textContent = keyMeta.channel_id_label || '上游通道 ID（数字）';
        });
        moveFloatingLayerToBody(routeKeyDrawer);
        routeKeyDrawer.hidden = false;
        routeKeyDrawer.classList.add('is-open');
        document.body.classList.add('has-payment-route-key-drawer');
        activeRouteKeyDrawer = routeKeyDrawer;
        routeKeyDrawer.querySelector('input[name="api_url"]')?.focus();
    };
    document.querySelectorAll('[data-payment-route-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            try {
                openRouteDialog(JSON.parse(button.dataset.route || '{}'), 'edit');
            } catch (error) {
                openRouteDialog({}, 'edit');
            }
        });
    });
    document.querySelector('[data-payment-route-create]')?.addEventListener('click', () => {
        openRouteDialog({}, 'create');
    });
    document.querySelectorAll('[data-payment-route-key]').forEach((button) => {
        button.addEventListener('click', () => {
            try {
                openRouteKeyDrawer(JSON.parse(button.dataset.route || '{}'));
            } catch (error) {
                openRouteKeyDrawer({});
            }
        });
    });
    document.querySelectorAll('[data-payment-route-close]').forEach((button) => {
        button.addEventListener('click', closeRouteDialog);
    });
    document.querySelectorAll('[data-payment-route-key-close]').forEach((button) => {
        button.addEventListener('click', closeRouteKeyDrawer);
    });
    routeDialog?.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.matches('[data-payment-route-close]')) {
            closeRouteDialog();
        }
    });
    routeKeyDrawer?.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.matches('[data-payment-route-key-close]')) {
            closeRouteKeyDrawer();
        }
    });
    document.addEventListener('click', (event) => {
        const source = event.target instanceof Element ? event.target.closest('[data-channel-placeholder]') : null;
        if (!source) {
            return;
        }
        event.preventDefault();
        openChannelActionDialog(source);
    });
    document.addEventListener('click', (event) => {
        const source = event.target instanceof Element ? event.target.closest('[data-payment-test-open]') : null;
        if (!source) {
            return;
        }
        event.preventDefault();
        try {
            openPaymentTestDialog(JSON.parse(source.dataset.route || '{}'));
        } catch (error) {
            openPaymentTestDialog({});
        }
    });
    document.querySelectorAll('[data-channel-action-close]').forEach((button) => {
        button.addEventListener('click', closeChannelActionDialog);
    });
    document.querySelectorAll('[data-payment-test-close]').forEach((button) => {
        button.addEventListener('click', closePaymentTestDialog);
    });
    paymentTestForm?.addEventListener('submit', (event) => {
        const amount = paymentTestForm.querySelector('input[name="test_amount"]')?.value || '0.01';
        const routeName = paymentTestDialog?.querySelector('[data-payment-test-field="channel_name"]')?.textContent?.trim() || '当前支付通道';
        const confirmed = window.confirm(`确定向「${routeName}」发起 ￥${amount} 测试支付吗？这会真实请求支付通道。`);
        if (!confirmed) {
            event.preventDefault();
        }
    });
    channelActionDialog?.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.matches('[data-channel-action-close]')) {
            closeChannelActionDialog();
        }
    });
    paymentTestDialog?.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.matches('[data-payment-test-close]')) {
            closePaymentTestDialog();
        }
    });

    let activeOrderModal = null;
    let activeRefundDialog = null;
    const closeRefundDialog = () => {
        if (!activeRefundDialog) {
            return;
        }
        activeRefundDialog.hidden = true;
        activeRefundDialog.classList.remove('is-open');
        document.body.classList.remove('has-refund-dialog');
        activeRefundDialog = null;
    };
    const closeOrderModal = () => {
        if (!activeOrderModal) {
            return;
        }
        closeRefundDialog();
        activeOrderModal.hidden = true;
        activeOrderModal.classList.remove('is-open');
        document.body.classList.remove('has-order-modal');
        activeOrderModal = null;
    };
    document.querySelectorAll('[data-order-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = document.getElementById(button.dataset.orderModalOpen || '');
            if (!modal) {
                return;
            }
            closeOrderModal();
            moveFloatingLayerToBody(modal);
            modal.hidden = false;
            modal.classList.add('is-open');
            document.body.classList.add('has-order-modal');
            activeOrderModal = modal;
            modal.querySelector('[data-order-modal-close]')?.focus();
        });
    });
    const autoOpenOrderNo = new URLSearchParams(window.location.search).get('open_order');
    if (autoOpenOrderNo) {
        const autoOpenButton = Array.from(document.querySelectorAll('[data-order-modal-open]')).find((button) => button.dataset.orderNo === autoOpenOrderNo);
        window.setTimeout(() => autoOpenButton?.click(), 120);
    }
    document.querySelectorAll('[data-order-modal-close]').forEach((button) => {
        button.addEventListener('click', closeOrderModal);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeChannelActionDialog) {
            closeChannelActionDialog();
            return;
        }
        if (event.key === 'Escape' && activePaymentTestDialog) {
            closePaymentTestDialog();
            return;
        }
        if (event.key === 'Escape' && activeRouteKeyDrawer) {
            closeRouteKeyDrawer();
            return;
        }
        if (event.key === 'Escape' && activeRouteDialog) {
            closeRouteDialog();
            return;
        }
        if (event.key === 'Escape' && !document.querySelector('.refund-dialog.is-open')) {
            closeOrderModal();
        }
    });
    document.querySelectorAll('[data-order-modal]').forEach((modal) => {
        const tabButtons = Array.from(modal.querySelectorAll('[data-order-tab-target]'));
        const tabPanels = Array.from(modal.querySelectorAll('[data-order-tab-panel]'));
        tabButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.dataset.orderTabTarget;
                tabButtons.forEach((item) => item.classList.toggle('is-active', item === button));
                tabPanels.forEach((panel) => panel.classList.toggle('is-active', panel.dataset.orderTabPanel === target));
            });
        });
    });

    document.querySelectorAll('[data-refund-dialog-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const dialog = document.getElementById(button.dataset.refundDialogOpen || '');
            if (!dialog) {
                return;
            }
            closeRefundDialog();
            moveFloatingLayerToBody(dialog);
            dialog.hidden = false;
            dialog.classList.add('is-open');
            document.body.classList.add('has-refund-dialog');
            activeRefundDialog = dialog;
            const amountInput = dialog.querySelector('input[name="refund_amount"]');
            amountInput?.focus();
            amountInput?.select();
        });
    });
    document.querySelectorAll('[data-refund-dialog-close]').forEach((button) => {
        button.addEventListener('click', closeRefundDialog);
    });
    document.querySelectorAll('[data-refund-submit-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const input = form.querySelector('input[name="refund_amount"]');
            const status = form.querySelector('[data-refund-dialog-status]');
            const submitButton = form.querySelector('button[type="submit"]');
            const amount = Number(input?.value || 0);
            const max = Number(input?.getAttribute('max') || 0);
            const showStatus = (message) => {
                if (!status) {
                    return;
                }
                status.hidden = false;
                status.textContent = message;
            };
            if (!Number.isFinite(amount) || amount <= 0) {
                event.preventDefault();
                showStatus('退款金额必须大于 0。');
                input?.focus();
                return;
            }
            if (max > 0 && amount - max > 0.0001) {
                event.preventDefault();
                showStatus(`退款金额不能超过剩余可退金额 ￥${max.toFixed(2)}。`);
                input?.focus();
                return;
            }
            if (!confirm('确认提交退款申请吗？提交后需要再查询退款状态，成功后才会更新订单。')) {
                event.preventDefault();
                showStatus('已取消提交退款申请。');
                return;
            }
            showStatus('正在提交退款申请，请稍候...');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = '正在提交...';
            }
        });
    });
    const orderMessage = document.querySelector('#admin-section-orders .order-message');
    if (orderMessage && window.location.hash === '#orders') {
        orderMessage.scrollIntoView({ block: 'center' });
    }
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeRefundDialog) {
            closeRefundDialog();
        }
    });

    const trendPoints = Array.from(document.querySelectorAll('[data-trend-point]'));
    const activateTrendPoint = (activePoint) => {
        trendPoints.forEach((point) => point.classList.toggle('is-active', point === activePoint));
    };
    trendPoints.forEach((point) => {
        point.addEventListener('mouseenter', () => activateTrendPoint(point));
        point.addEventListener('focus', () => activateTrendPoint(point));
        point.addEventListener('click', () => activateTrendPoint(point));
    });

    const designForm = document.querySelector('[data-design-form]');
    const designPreviewRoot = document.querySelector('[data-design-preview-root]');
    if (designForm && designPreviewRoot) {
        const bindText = (field, selector) => {
            const input = designForm.querySelector(`[data-design-bind="${field}"]`);
            const target = document.querySelector(selector);
            if (!input || !target) {
                return;
            }
            const apply = () => {
                target.textContent = input.value || input.placeholder || '';
            };
            input.addEventListener('input', apply);
            apply();
        };

        bindText('brand', '[data-design-preview="brand"]');
        bindText('hero_title', '[data-design-preview="hero_title"]');
        bindText('hero_subtitle', '[data-design-preview="hero_subtitle"]');
        bindText('notice_text', '[data-design-preview="notice_text"]');
        bindText('section_title', '[data-design-preview="section_title"]');
        bindText('search_placeholder', '.design-phone-search');

        const primaryInput = designForm.querySelector('[data-design-color="primary"]');
        const accentInput = designForm.querySelector('[data-design-color="accent"]');
        const applyColors = () => {
            if (primaryInput) {
                designPreviewRoot.style.setProperty('--design-primary', primaryInput.value);
            }
            if (accentInput) {
                designPreviewRoot.style.setProperty('--design-accent', accentInput.value);
            }
        };
        primaryInput?.addEventListener('input', applyColors);
        accentInput?.addEventListener('input', applyColors);
        applyColors();

        const moduleInputs = Array.from(designForm.querySelectorAll('[data-design-module-toggle]'));
        const applyModules = () => {
            moduleInputs.forEach((input) => {
                const moduleName = input.dataset.designModuleToggle;
                document.querySelectorAll(`[data-design-preview-module="${moduleName}"]`).forEach((item) => {
                    item.style.display = input.checked ? '' : 'none';
                });
            });
        };
        moduleInputs.forEach((input) => input.addEventListener('change', applyModules));
        applyModules();

        const quickInputs = Array.from(designForm.querySelectorAll('input[name^="quick_nav_label_"]'));
        const quickItems = Array.from(document.querySelectorAll('.design-phone-quick span'));
        const applyQuickNavs = () => {
            quickInputs.forEach((input, index) => {
                const item = quickItems[index];
                if (!item) {
                    return;
                }
                const icon = item.querySelector('i');
                item.textContent = input.value || `导航${index + 1}`;
                if (icon) {
                    item.prepend(icon);
                }
            });
        };
        quickInputs.forEach((input) => input.addEventListener('input', applyQuickNavs));
        applyQuickNavs();
    }

    window.addEventListener('hashchange', () => showSection(window.location.hash.slice(1) || fallback, false));
    showSection(window.location.hash.slice(1) || fallback, false);
})();
</script>
