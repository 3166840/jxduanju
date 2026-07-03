<?php

namespace App\Support;

class DataStore
{
    private static ?array $sharedData = null;

    private DatabaseStorage $database;
    private ?array $cachedData = null;

    public function __construct(?string $path = null, ?array $databaseConfig = null)
    {
        $this->database = new DatabaseStorage($databaseConfig ?? $this->databaseConfig());
        if (!$this->database->enabled()) {
            throw new \RuntimeException('MySQL 是当前唯一数据源，请配置 JX_DB_DRIVER=mysql。');
        }
    }

    public function load(bool $backupNormalized = false): array
    {
        if ($this->cachedData !== null) {
            return $this->cachedData;
        }
        if (self::$sharedData !== null) {
            $this->cachedData = self::$sharedData;

            return $this->cachedData;
        }

        $data = $this->database->load();
        if (!is_array($data)) {
            throw new \RuntimeException('MySQL 中缺少 jx_meta.app_data，请先导入数据库快照。');
        }

        $this->cachedData = $this->normalize($data);
        self::$sharedData = $this->cachedData;

        return $this->cachedData;
    }

    public function save(array $data, bool $backup = true): void
    {
        if ($backup) {
            $this->backupCurrentData('auto');
        }
        $this->database->save($data);
        $this->cachedData = $this->normalize($data);
        self::$sharedData = $this->cachedData;
        if ($backup) {
            $this->pruneBackups();
        }
    }

    public function replace(array $data): void
    {
        $this->save($data);
    }

    public function path(): string
    {
        return $this->database->pathLabel();
    }

    public function storageInfo(): array
    {
        return $this->database->info();
    }

    public function exportSql(): string
    {
        return $this->database->exportSql();
    }

    public function backups(): array
    {
        $files = glob($this->backupDir() . '/mysql_*.sql') ?: [];
        rsort($files, SORT_STRING);

        return array_map(static function (string $file): array {
            return [
                'file' => basename($file),
                'path' => $file,
                'size' => is_file($file) ? filesize($file) : 0,
                'created_at' => is_file($file) ? date('Y-m-d H:i:s', filemtime($file)) : '',
            ];
        }, $files);
    }

    public function createBackup(string $reason = 'manual'): ?array
    {
        $file = $this->backupCurrentData($reason, true);
        $this->pruneBackups();
        if ($file === null) {
            return null;
        }

        return [
            'file' => basename($file),
            'path' => $file,
            'size' => is_file($file) ? filesize($file) : 0,
            'created_at' => is_file($file) ? date('Y-m-d H:i:s', filemtime($file)) : '',
        ];
    }

    public function restoreBackup(string $backupFile): bool
    {
        $backupFile = basename($backupFile);
        if (!preg_match('/^mysql_[A-Za-z0-9_.-]+\.sql$/', $backupFile)) {
            return false;
        }

        $source = $this->backupDir() . '/' . $backupFile;
        if (!is_file($source)) {
            return false;
        }

        $sql = file_get_contents($source);
        if (!is_string($sql) || trim($sql) === '') {
            return false;
        }

        $this->createBackup('before_restore');
        $this->database->importSql($sql);
        $this->cachedData = null;
        self::$sharedData = null;

        return true;
    }

    private function seed(): array
    {
        return $this->normalize([
            'users' => [
                ['id' => 1, 'nickname' => '游客A', 'phone' => '', 'role' => 'guest', 'membership' => false, 'membership_expires_at' => null],
                ['id' => 2, 'nickname' => '会员B', 'phone' => '13800000000', 'role' => 'user', 'membership' => true, 'membership_expires_at' => '2027-12-31 23:59:59'],
            ],
            'dramas' => [
                [
                    'id' => 1,
                    'title' => '逆袭总裁的秘密',
                    'cover' => '/assets/cover-1.svg',
                    'description' => '都市逆袭、甜宠反转短剧。',
                    'price_per_episode' => 2.99,
                    'membership_price' => 19.9,
                    'status' => 'online',
                    'episodes' => [
                        ['id' => 101, 'title' => '第1集', 'duration' => '03:21', 'is_free' => true, 'video_url' => 'https://example.com/demo-1.mp4'],
                        ['id' => 102, 'title' => '第2集', 'duration' => '02:58', 'is_free' => false, 'video_url' => 'https://example.com/demo-2.mp4'],
                        ['id' => 103, 'title' => '第3集', 'duration' => '03:04', 'is_free' => false, 'video_url' => 'https://example.com/demo-3.mp4'],
                    ],
                ],
                [
                    'id' => 2,
                    'title' => '重生后我成了顶流',
                    'cover' => '/assets/cover-2.svg',
                    'description' => '重生、娱乐圈、打脸爽剧。',
                    'price_per_episode' => 1.99,
                    'membership_price' => 16.8,
                    'status' => 'online',
                    'episodes' => [
                        ['id' => 201, 'title' => '第1集', 'duration' => '03:11', 'is_free' => true, 'video_url' => 'https://example.com/demo-4.mp4'],
                        ['id' => 202, 'title' => '第2集', 'duration' => '03:00', 'is_free' => false, 'video_url' => 'https://example.com/demo-5.mp4'],
                    ],
                ],
            ],
            'orders' => [],
            'order_action_logs' => [],
            'entitlements' => [],
            'banners' => [
                ['title' => '爆款短剧推荐', 'subtitle' => '今天就开看', 'link' => '/?route=drama&id=1']
            ],
            'stats' => [
                'views' => 12880,
                'orders' => 286,
                'revenue' => 4821.5
            ]
        ]);
    }

    private function backupDir(): string
    {
        $dir = dirname(__DIR__, 2) . '/runtime/data/backups';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return $dir;
    }

    private function backupCurrentData(string $reason = 'auto', bool $force = false): ?string
    {
        return $this->writeBackupSql($this->database->exportSql(), $reason, $force);
    }

    private function writeBackupSql(string $current, string $reason = 'auto', bool $force = false): ?string
    {
        $reason = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $reason) ?: 'auto';
        $latestMetaPath = $this->backupDir() . '/.latest_hash';
        $hash = sha1($current);
        if (!$force && is_file($latestMetaPath) && trim((string) file_get_contents($latestMetaPath)) === $hash) {
            return null;
        }

        $stamp = date('Ymd_His') . '_' . substr((string) str_replace('.', '', microtime(true)), -5);
        $backupPath = $this->backupDir() . '/mysql_' . $stamp . '_' . $reason . '.sql';
        if (file_put_contents($backupPath, $current, LOCK_EX) === false) {
            return null;
        }

        file_put_contents($latestMetaPath, $hash, LOCK_EX);

        return $backupPath;
    }

    private function databaseConfig(): array
    {
        $path = dirname(__DIR__, 2) . '/config/database.php';
        if (!is_file($path)) {
            throw new \RuntimeException('数据库配置文件不存在。');
        }

        $config = require $path;

        if (!is_array($config)) {
            throw new \RuntimeException('数据库配置无效。');
        }

        return $config;
    }

    private function pruneBackups(int $keep = 30): void
    {
        $files = glob($this->backupDir() . '/mysql_*.sql') ?: [];
        rsort($files, SORT_STRING);
        foreach (array_slice($files, $keep) as $file) {
            @unlink($file);
        }
    }

    private function normalize(array $data): array
    {
        $data['users'] ??= [];
        $data['dramas'] ??= [];
        $data['orders'] ??= [];
        $data['order_action_logs'] ??= [];
        $data['admin_operation_logs'] ??= [];
        $data['entitlements'] ??= [];
        $data['coin_transactions'] ??= [];
        $data['rights_repair_logs'] ??= [];
        $data['redeem_codes'] ??= [];
        $data['redeem_code_logs'] ??= [];
        $data['home_recommendations'] ??= [];
        $data['popup_notices'] ??= [];
        $data['activity_configs'] ??= [];
        $data['activity_participation_logs'] ??= [];
        $data['hot_rank_configs'] ??= [];
        $data['watch_history'] ??= [];
        $data['followed_dramas'] ??= [];
        $data['sms_codes'] ??= [];
        $data['email_delivery_logs'] ??= [];
        $data['message_templates'] ??= [];
        $data['in_app_messages'] ??= [];
        $data['promotion_links'] ??= [];
        $data['promotion_events'] ??= [];
        $data['promotion_costs'] ??= [];
        $data['agent_settlements'] ??= [];
        $data['agent_settlement_notification_logs'] ??= [];
        $data['agent_payout_batches'] ??= [];
        $data['callback_logs'] ??= [];
        $data['content_events'] ??= [];
        $data['content_comments'] ??= [];
        $data['agents'] ??= [];
        $data['novels'] ??= [];
        $data['apps'] ??= [];
        $data['content_tags'] ??= [];
        $data['content_groups'] ??= [];
        $data['content_import_logs'] ??= [];
        $data['media_contents'] ??= [];
        $data['feedback_items'] ??= [];
        $data['operation_alert_notifications'] ??= [];
        $data['operation_alert_notification_config'] ??= [];
        $data['operation_alert_notification_receivers'] ??= [];
        $data['operation_alert_notification_logs'] ??= [];
        $data['promotion_stop_tasks'] ??= [];
        $data['analytics_review_tasks'] ??= [];
        $data['promotion_stop_adapter_configs'] ??= [];
        $data['landing_pages'] ??= [];
        $data['landing_page_events'] ??= [];
        $data['ad_platform_configs'] ??= [];
        $data['ad_waterfall_config'] ??= [];
        $data['ad_delivery_rules'] ??= [];
        $data['ad_slots'] ??= [];
        $data['ad_events'] ??= [];
        $data['mini_program_configs'] ??= [];
        $data['mini_program_sync_tasks'] ??= [];
        $data['system_config_fragments'] ??= [];
        $data['config_change_requests'] ??= [];
        $data['config_change_notification_logs'] ??= [];
        $data['app_config_delivery_logs'] ??= [];
        $data['filter_presets'] ??= [];

        if (empty($data['novels'])) {
            $data['novels'] = [
                [
                    'id' => 1,
                    'title' => '离婚后我成了首富',
                    'cover' => '/assets/cover-2.svg',
                    'description' => '女频逆袭爽文，适合短剧投流承接。',
                    'author' => '精秀作者',
                    'category' => '都市',
                    'free_chapter_count' => 3,
                    'chapter_coin_price' => 99,
                    'full_unlock_price' => 19.9,
                    'is_hot' => true,
                    'is_new' => true,
                    'sort' => 1,
                    'status' => 'online',
                    'chapters' => [
                        ['id' => 1001, 'title' => '第1章 她签下离婚协议', 'content' => '她把离婚协议放在桌上，转身走进雨夜。', 'word_count' => 21, 'is_free' => true, 'sort' => 1, 'status' => 'online'],
                        ['id' => 1002, 'title' => '第2章 新身份曝光', 'content' => '三年后，她以集团新任董事的身份回到这座城市。', 'word_count' => 25, 'is_free' => true, 'sort' => 2, 'status' => 'online'],
                    ],
                ],
            ];
        }

        if (empty($data['agents'])) {
            $data['agents'] = [
                ['id' => 1, 'name' => '默认商务', 'role' => 'business', 'parent_id' => 0, 'status' => 'active', 'remark' => '投放归因默认商务'],
                ['id' => 2, 'name' => '默认组长', 'role' => 'leader', 'parent_id' => 1, 'status' => 'active', 'remark' => '投放归因默认组长'],
                ['id' => 3, 'name' => '默认代理', 'role' => 'agent', 'parent_id' => 2, 'status' => 'active', 'remark' => '投放归因默认代理'],
            ];
        }

        foreach ($data['agents'] as $index => &$agent) {
            $agent['id'] = max(1, (int) ($agent['id'] ?? ($index + 1)));
            $agent['name'] = trim((string) ($agent['name'] ?? '投放账号')) ?: '投放账号';
            $agent['role'] = in_array((string) ($agent['role'] ?? ''), ['business', 'leader', 'agent'], true) ? (string) $agent['role'] : 'agent';
            $agent['parent_id'] = max(0, (int) ($agent['parent_id'] ?? 0));
            $agent['status'] = in_array((string) ($agent['status'] ?? ''), ['active', 'paused'], true) ? (string) $agent['status'] : 'active';
            $agent['remark'] = trim((string) ($agent['remark'] ?? ''));
        }
        unset($agent);

        $defaultCategories = ['都市', '甜宠', '虐恋', '穿越', '古装'];
        $data['content_tags'] = $this->normalizeContentTags((array) ($data['content_tags'] ?? []), $defaultCategories);
        $data['content_groups'] = $this->normalizeContentGroups((array) ($data['content_groups'] ?? []));
        $data['content_import_logs'] = $this->normalizeContentImportLogs((array) ($data['content_import_logs'] ?? []));
        $data['media_contents'] = $this->normalizeMediaContents((array) ($data['media_contents'] ?? []), $defaultCategories);
        $contentGroupIds = array_map(static fn (array $group): int => (int) ($group['id'] ?? 0), $data['content_groups']);

        foreach ($data['novels'] as $index => &$novel) {
            $novel['id'] = max(1, (int) ($novel['id'] ?? ($index + 1)));
            $novel['title'] = trim((string) ($novel['title'] ?? '未命名小说')) ?: '未命名小说';
            $novel['cover'] = trim((string) ($novel['cover'] ?? '/assets/cover-1.svg')) ?: '/assets/cover-1.svg';
            $novel['description'] = trim((string) ($novel['description'] ?? ''));
            $novel['author'] = trim((string) ($novel['author'] ?? ''));
            $novel['category'] = trim((string) ($novel['category'] ?? '都市')) ?: '都市';
            $novel['tags'] = $this->normalizeContentTagNames((array) ($novel['tags'] ?? [$novel['category']]));
            $novel['group_id'] = in_array((int) ($novel['group_id'] ?? 0), $contentGroupIds, true) ? (int) ($novel['group_id'] ?? 0) : 0;
            $novel['audit_status'] = $this->normalizeContentAuditStatus((string) ($novel['audit_status'] ?? $novel['status'] ?? 'draft'));
            $novel['audit_note'] = $this->limitText(trim((string) ($novel['audit_note'] ?? '')), 160);
            $novel['reviewed_by'] = $this->limitText(trim((string) ($novel['reviewed_by'] ?? '')), 60);
            $novel['reviewed_at'] = trim((string) ($novel['reviewed_at'] ?? ''));
            $novel['quality'] = in_array((string) ($novel['quality'] ?? ''), ['normal', 'featured', 'premium'], true) ? (string) $novel['quality'] : 'normal';
            $novel['is_finished'] = array_key_exists('is_finished', $novel) ? !empty($novel['is_finished']) : true;
            $novel['is_vip'] = array_key_exists('is_vip', $novel) ? !empty($novel['is_vip']) : true;
            $novel['buy_start'] = max(0, (int) ($novel['buy_start'] ?? $novel['free_chapter_count'] ?? 3));
            $novel['read_count'] = max(0, (int) ($novel['read_count'] ?? $novel['views'] ?? 0));
            $novel['free_chapter_count'] = max(0, (int) ($novel['free_chapter_count'] ?? 3));
            $novel['chapter_coin_price'] = max(1, (int) ($novel['chapter_coin_price'] ?? 99));
            $novel['full_unlock_price'] = round(max(0, (float) ($novel['full_unlock_price'] ?? 19.9)), 2);
            $novel['is_hot'] = !empty($novel['is_hot']);
            $novel['is_new'] = !empty($novel['is_new']);
            $novel['sort'] = (int) ($novel['sort'] ?? $novel['id']);
            $novel['status'] = in_array((string) ($novel['status'] ?? ''), ['online', 'draft', 'offline'], true) ? (string) $novel['status'] : 'draft';
            $novel['created_at'] = trim((string) ($novel['created_at'] ?? '')) ?: date('Y-m-d H:i:s');
            $novel['updated_at'] = trim((string) ($novel['updated_at'] ?? '')) ?: $novel['created_at'];
            $novel['chapters'] = array_values((array) ($novel['chapters'] ?? []));
            foreach ($novel['chapters'] as $chapterIndex => &$chapter) {
                $chapter['id'] = max(1, (int) ($chapter['id'] ?? (((int) $novel['id']) * 1000 + $chapterIndex + 1)));
                $chapter['title'] = trim((string) ($chapter['title'] ?? '未命名章节')) ?: '未命名章节';
                $chapter['content'] = trim((string) ($chapter['content'] ?? ''));
                $chapter['word_count'] = max(0, (int) ($chapter['word_count'] ?? 0));
                if ($chapter['word_count'] <= 0 && $chapter['content'] !== '') {
                    $chapter['word_count'] = function_exists('mb_strlen') ? mb_strlen($chapter['content'], 'UTF-8') : strlen($chapter['content']);
                }
                $chapter['is_free'] = !empty($chapter['is_free']);
                $chapter['coin_price'] = max(0, (int) ($chapter['coin_price'] ?? $novel['chapter_coin_price'] ?? 99));
                $chapter['sort'] = (int) ($chapter['sort'] ?? ($chapterIndex + 1));
                $chapter['status'] = in_array((string) ($chapter['status'] ?? ''), ['online', 'draft', 'offline'], true) ? (string) $chapter['status'] : 'draft';
                $chapter['created_at'] = trim((string) ($chapter['created_at'] ?? '')) ?: date('Y-m-d H:i:s');
                $chapter['updated_at'] = trim((string) ($chapter['updated_at'] ?? '')) ?: $chapter['created_at'];
            }
            unset($chapter);
            usort($novel['chapters'], static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (int) ($a['id'] ?? 0)] <=> [(int) ($b['sort'] ?? 0), (int) ($b['id'] ?? 0)]);
        }
        unset($novel);

        foreach ($data['promotion_links'] as $index => &$link) {
            $link['id'] = max(1, (int) ($link['id'] ?? ($index + 1)));
            $code = preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($link['code'] ?? ''));
            $link['code'] = $code !== '' ? $code : ('p' . base_convert((string) ($link['id'] + 1000), 10, 36));
            $link['name'] = trim((string) ($link['name'] ?? '推广链接')) ?: '推广链接';
            $link['content_type'] = in_array((string) ($link['content_type'] ?? ''), ['drama', 'novel'], true) ? (string) $link['content_type'] : 'drama';
            $link['drama_id'] = max(0, (int) ($link['drama_id'] ?? 0));
            $link['episode_id'] = max(0, (int) ($link['episode_id'] ?? 0));
            $link['novel_id'] = max(0, (int) ($link['novel_id'] ?? 0));
            $link['chapter_id'] = max(0, (int) ($link['chapter_id'] ?? 0));
            if ($link['content_type'] === 'novel') {
                $link['drama_id'] = 0;
                $link['episode_id'] = 0;
            } else {
                $link['novel_id'] = 0;
                $link['chapter_id'] = 0;
            }
            $link['agent_id'] = max(0, (int) ($link['agent_id'] ?? 0));
            $link['source'] = trim((string) ($link['source'] ?? ''));
            $link['campaign'] = trim((string) ($link['campaign'] ?? ''));
            $link['traffic_platform'] = $this->limitText(trim((string) ($link['traffic_platform'] ?? $link['platform'] ?? '')), 40);
            $link['channel_id'] = $this->limitText(trim((string) ($link['channel_id'] ?? $link['channel'] ?? '')), 60);
            $link['media_app_id'] = $this->limitText(trim((string) ($link['media_app_id'] ?? $link['app_id'] ?? '')), 60);
            $link['app_name'] = $this->limitText(trim((string) ($link['app_name'] ?? '')), 60);
            $link['ad_id'] = $this->limitText(trim((string) ($link['ad_id'] ?? $link['advert_id'] ?? '')), 80);
            $link['creative_id'] = $this->limitText(trim((string) ($link['creative_id'] ?? $link['creativity_id'] ?? '')), 80);
            $link['material_id'] = $this->limitText(trim((string) ($link['material_id'] ?? $link['material'] ?? '')), 80);
            $link['cost_budget_limit'] = round(max(0, (float) ($link['cost_budget_limit'] ?? $link['budget_limit'] ?? 0)), 2);
            $link['min_recovery_rate'] = round(max(0, (float) ($link['min_recovery_rate'] ?? 0)), 2);
            $link['auto_pause_on_cost'] = !empty($link['auto_pause_on_cost']);
            $link['auto_pause_min_cost'] = round(max(0, (float) ($link['auto_pause_min_cost'] ?? 0)), 2);
            $link['auto_paused_at'] = trim((string) ($link['auto_paused_at'] ?? ''));
            $link['auto_paused_reason'] = $this->limitText(trim((string) ($link['auto_paused_reason'] ?? '')), 160);
            $link['status'] = in_array((string) ($link['status'] ?? ''), ['active', 'review', 'paused'], true) ? (string) $link['status'] : 'active';
            $link['jump_mode'] = in_array((string) ($link['jump_mode'] ?? ''), ['auto', 'app', 'review'], true) ? (string) $link['jump_mode'] : 'auto';
            $link['target_url'] = trim((string) ($link['target_url'] ?? ''));
            $link['review_url'] = trim((string) ($link['review_url'] ?? ''));
            $link['replacement_rules'] = $this->normalizePromotionReplacementRules((array) ($link['replacement_rules'] ?? []));
            $link['created_at'] = trim((string) ($link['created_at'] ?? '')) ?: date('Y-m-d H:i:s');
            $link['updated_at'] = trim((string) ($link['updated_at'] ?? '')) ?: $link['created_at'];
        }
        unset($link);

        foreach ($data['promotion_events'] as $index => &$event) {
            $event['id'] = max(1, (int) ($event['id'] ?? ($index + 1)));
            $event['promotion_link_id'] = max(0, (int) ($event['promotion_link_id'] ?? 0));
            $event['code'] = preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($event['code'] ?? ''));
            $event['event'] = in_array((string) ($event['event'] ?? ''), ['visit', 'paid', 'register', 'add_desktop', 'activate'], true) ? (string) $event['event'] : 'visit';
            $event['user_id'] = max(0, (int) ($event['user_id'] ?? 0));
            $event['order_no'] = trim((string) ($event['order_no'] ?? ''));
            $event['path'] = trim((string) ($event['path'] ?? ''));
            $event['amount'] = round(max(0, (float) ($event['amount'] ?? 0)), 2);
            $event['traffic_platform'] = $this->limitText(trim((string) ($event['traffic_platform'] ?? $event['platform'] ?? '')), 40);
            $event['channel_id'] = $this->limitText(trim((string) ($event['channel_id'] ?? $event['channel'] ?? '')), 60);
            $event['media_app_id'] = $this->limitText(trim((string) ($event['media_app_id'] ?? $event['app_id'] ?? '')), 60);
            $event['ad_id'] = $this->limitText(trim((string) ($event['ad_id'] ?? $event['advert_id'] ?? '')), 80);
            $event['creative_id'] = $this->limitText(trim((string) ($event['creative_id'] ?? $event['creativity_id'] ?? '')), 80);
            $event['material_id'] = $this->limitText(trim((string) ($event['material_id'] ?? $event['material'] ?? '')), 80);
            $event['created_at'] = trim((string) ($event['created_at'] ?? '')) ?: date('Y-m-d H:i:s');
        }
        unset($event);

        foreach ($data['promotion_costs'] as $index => &$cost) {
            $cost['id'] = max(1, (int) ($cost['id'] ?? ($index + 1)));
            $date = trim((string) ($cost['date'] ?? ''));
            $timestamp = strtotime($date);
            $cost['date'] = $timestamp === false ? date('Y-m-d') : date('Y-m-d', $timestamp);
            $cost['promotion_link_id'] = max(0, (int) ($cost['promotion_link_id'] ?? 0));
            $cost['code'] = preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($cost['code'] ?? ''));
            $cost['agent_id'] = max(0, (int) ($cost['agent_id'] ?? 0));
            $cost['amount'] = round(max(0, (float) ($cost['amount'] ?? 0)), 2);
            $cost['clicks'] = max(0, (int) ($cost['clicks'] ?? 0));
            $cost['impressions'] = max(0, (int) ($cost['impressions'] ?? 0));
            $cost['source'] = trim((string) ($cost['source'] ?? ''));
            $cost['campaign'] = trim((string) ($cost['campaign'] ?? ''));
            $cost['traffic_platform'] = $this->limitText(trim((string) ($cost['traffic_platform'] ?? $cost['platform'] ?? '')), 40);
            $cost['channel_id'] = $this->limitText(trim((string) ($cost['channel_id'] ?? $cost['channel'] ?? '')), 60);
            $cost['media_app_id'] = $this->limitText(trim((string) ($cost['media_app_id'] ?? $cost['app_id'] ?? '')), 60);
            $cost['ad_id'] = $this->limitText(trim((string) ($cost['ad_id'] ?? $cost['advert_id'] ?? '')), 80);
            $cost['creative_id'] = $this->limitText(trim((string) ($cost['creative_id'] ?? $cost['creativity_id'] ?? '')), 80);
            $cost['material_id'] = $this->limitText(trim((string) ($cost['material_id'] ?? $cost['material'] ?? '')), 80);
            $cost['remark'] = trim((string) ($cost['remark'] ?? ''));
            $cost['created_at'] = trim((string) ($cost['created_at'] ?? '')) ?: date('Y-m-d H:i:s');
            $cost['updated_at'] = trim((string) ($cost['updated_at'] ?? '')) ?: $cost['created_at'];
        }
        unset($cost);

        foreach ($data['content_events'] as $index => &$event) {
            $event['id'] = max(1, (int) ($event['id'] ?? ($index + 1)));
            $event['content_type'] = in_array((string) ($event['content_type'] ?? ''), ['drama', 'novel'], true) ? (string) $event['content_type'] : 'drama';
            $event['event'] = in_array((string) ($event['event'] ?? ''), ['view', 'lock_exposure', 'unlock_success', 'order_created'], true) ? (string) $event['event'] : 'view';
            $event['drama_id'] = max(0, (int) ($event['drama_id'] ?? 0));
            $event['episode_id'] = max(0, (int) ($event['episode_id'] ?? 0));
            $event['novel_id'] = max(0, (int) ($event['novel_id'] ?? 0));
            $event['chapter_id'] = max(0, (int) ($event['chapter_id'] ?? 0));
            $event['user_id'] = max(0, (int) ($event['user_id'] ?? 0));
            $event['order_no'] = trim((string) ($event['order_no'] ?? ''));
            $event['amount'] = round(max(0, (float) ($event['amount'] ?? 0)), 2);
            $event['promotion_link_id'] = max(0, (int) ($event['promotion_link_id'] ?? 0));
            $event['code'] = preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($event['code'] ?? ''));
            $event['path'] = trim((string) ($event['path'] ?? ''));
            $event['traffic_platform'] = $this->limitText(trim((string) ($event['traffic_platform'] ?? $event['platform'] ?? '')), 40);
            $event['channel_id'] = $this->limitText(trim((string) ($event['channel_id'] ?? $event['channel'] ?? '')), 60);
            $event['media_app_id'] = $this->limitText(trim((string) ($event['media_app_id'] ?? $event['app_id'] ?? '')), 60);
            $event['ad_id'] = $this->limitText(trim((string) ($event['ad_id'] ?? $event['advert_id'] ?? '')), 80);
            $event['creative_id'] = $this->limitText(trim((string) ($event['creative_id'] ?? $event['creativity_id'] ?? '')), 80);
            $event['material_id'] = $this->limitText(trim((string) ($event['material_id'] ?? $event['material'] ?? '')), 80);
            $event['created_at'] = trim((string) ($event['created_at'] ?? '')) ?: date('Y-m-d H:i:s');
        }
        unset($event);

        $data['callback_config'] = array_merge([
            'enabled' => false,
            'endpoint' => '',
            'secret' => '',
            'platform' => '巨量引擎',
            'template_key' => 'custom',
            'template_name' => '自定义模板',
            'field_mapping' => [],
            'auth_config' => ['mode' => 'none'],
            'retry_policy' => [
                'max_attempts' => 5,
                'base_interval_minutes' => 5,
                'max_interval_minutes' => 120,
                'backoff' => true,
            ],
            'fallback_time_match' => true,
            'add_desktop_events' => ['active'],
            'paid_events' => ['pay'],
            'updated_at' => date('Y-m-d H:i:s'),
        ], (array) ($data['callback_config'] ?? []));
        $data['callback_config']['enabled'] = !empty($data['callback_config']['enabled']);
        $data['callback_config']['endpoint'] = trim((string) ($data['callback_config']['endpoint'] ?? ''));
        $data['callback_config']['secret'] = trim((string) ($data['callback_config']['secret'] ?? ''));
        $data['callback_config']['platform'] = trim((string) ($data['callback_config']['platform'] ?? '巨量引擎')) ?: '巨量引擎';
        $data['callback_config']['template_key'] = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($data['callback_config']['template_key'] ?? 'custom'))) ?: 'custom';
        $data['callback_config']['template_name'] = $this->limitText(trim((string) ($data['callback_config']['template_name'] ?? '自定义模板')), 60);
        $data['callback_config']['field_mapping'] = is_array($data['callback_config']['field_mapping'] ?? null) ? $data['callback_config']['field_mapping'] : [];
        $data['callback_config']['auth_config'] = $this->normalizeCallbackAuthConfig((array) ($data['callback_config']['auth_config'] ?? []), (string) ($data['callback_config']['secret'] ?? ''));
        $data['callback_config']['retry_policy'] = $this->normalizeCallbackRetryPolicy((array) ($data['callback_config']['retry_policy'] ?? []));
        $data['callback_config']['fallback_time_match'] = !empty($data['callback_config']['fallback_time_match']);
        $data['callback_config']['add_desktop_events'] = array_values(array_filter(array_map('strval', (array) ($data['callback_config']['add_desktop_events'] ?? ['active'])), static fn (string $item): bool => trim($item) !== ''));
        $data['callback_config']['paid_events'] = array_values(array_filter(array_map('strval', (array) ($data['callback_config']['paid_events'] ?? ['pay'])), static fn (string $item): bool => trim($item) !== ''));
        if (empty($data['callback_config']['add_desktop_events'])) {
            $data['callback_config']['add_desktop_events'] = ['active'];
        }
        if (empty($data['callback_config']['paid_events'])) {
            $data['callback_config']['paid_events'] = ['pay'];
        }

        foreach ($data['callback_logs'] as $index => &$log) {
            $log['id'] = max(1, (int) ($log['id'] ?? ($index + 1)));
            $log['event'] = in_array((string) ($log['event'] ?? ''), ['add_desktop', 'paid', 'activate', 'register'], true) ? (string) $log['event'] : 'paid';
            $log['platform_event'] = trim((string) ($log['platform_event'] ?? ''));
            $log['promotion_link_id'] = max(0, (int) ($log['promotion_link_id'] ?? 0));
            $log['code'] = preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($log['code'] ?? ''));
            $log['user_id'] = max(0, (int) ($log['user_id'] ?? 0));
            $log['order_no'] = trim((string) ($log['order_no'] ?? ''));
            $log['amount'] = round(max(0, (float) ($log['amount'] ?? 0)), 2);
            $log['traffic_platform'] = $this->limitText(trim((string) ($log['traffic_platform'] ?? $log['platform'] ?? '')), 40);
            $log['channel_id'] = $this->limitText(trim((string) ($log['channel_id'] ?? $log['channel'] ?? '')), 60);
            $log['media_app_id'] = $this->limitText(trim((string) ($log['media_app_id'] ?? $log['app_id'] ?? '')), 60);
            $log['app_key'] = $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($log['app_key'] ?? $log['media_app_id'] ?? ''))) ?: '', 60);
            $log['app_name'] = $this->limitText(trim((string) ($log['app_name'] ?? '')), 60);
            $log['ad_id'] = $this->limitText(trim((string) ($log['ad_id'] ?? $log['advert_id'] ?? '')), 80);
            $log['creative_id'] = $this->limitText(trim((string) ($log['creative_id'] ?? $log['creativity_id'] ?? '')), 80);
            $log['material_id'] = $this->limitText(trim((string) ($log['material_id'] ?? $log['material'] ?? '')), 80);
            $log['callback_policy_source'] = in_array((string) ($log['callback_policy_source'] ?? ''), ['global', 'app'], true) ? (string) $log['callback_policy_source'] : 'global';
            $log['callback_enabled'] = array_key_exists('callback_enabled', $log)
                ? !empty($log['callback_enabled'])
                : ((string) $log['callback_policy_source'] === 'app' && trim((string) ($log['endpoint'] ?? '')) !== '' ? true : !empty($data['callback_config']['enabled']));
            $log['callback_retry_failed'] = array_key_exists('callback_retry_failed', $log) ? !empty($log['callback_retry_failed']) : true;
            $log['callback_fallback_time_match'] = array_key_exists('callback_fallback_time_match', $log) ? !empty($log['callback_fallback_time_match']) : !empty($data['callback_config']['fallback_time_match']);
            $log['callback_secret'] = trim((string) ($log['callback_secret'] ?? ''));
            $log['callback_template_key'] = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($log['callback_template_key'] ?? $data['callback_config']['template_key'] ?? 'custom'))) ?: 'custom';
            $log['callback_template_name'] = $this->limitText(trim((string) ($log['callback_template_name'] ?? $data['callback_config']['template_name'] ?? '自定义模板')), 60);
            $log['callback_field_mapping'] = is_array($log['callback_field_mapping'] ?? null) ? $log['callback_field_mapping'] : (array) ($data['callback_config']['field_mapping'] ?? []);
            $logAuthConfig = (array) ($log['callback_auth_config'] ?? $data['callback_config']['auth_config'] ?? []);
            $logAuthConfig['secret'] = '';
            $logAuthConfig['token'] = '';
            $log['callback_auth_config'] = $this->normalizeCallbackAuthConfig($logAuthConfig, '');
            $log['status'] = in_array((string) ($log['status'] ?? ''), ['pending', 'success', 'failed', 'skipped'], true) ? (string) $log['status'] : 'pending';
            $log['message'] = trim((string) ($log['message'] ?? ''));
            $log['endpoint'] = trim((string) ($log['endpoint'] ?? ''));
            $log['request_payload'] = is_array($log['request_payload'] ?? null) ? $log['request_payload'] : [];
            $log['response_payload'] = is_array($log['response_payload'] ?? null) ? $log['response_payload'] : [];
            $log['attempt_count'] = max(0, (int) ($log['attempt_count'] ?? 0));
            $log['last_attempt_at'] = trim((string) ($log['last_attempt_at'] ?? ''));
            $log['next_retry_at'] = trim((string) ($log['next_retry_at'] ?? ''));
            $log['retry_blocked_reason'] = trim((string) ($log['retry_blocked_reason'] ?? ''));
            $log['created_at'] = trim((string) ($log['created_at'] ?? '')) ?: date('Y-m-d H:i:s');
            $log['updated_at'] = trim((string) ($log['updated_at'] ?? '')) ?: $log['created_at'];
        }
        unset($log);

        $data['feedback_items'] = $this->normalizeFeedbackItems((array) ($data['feedback_items'] ?? []));
        $data['content_comments'] = $this->normalizeContentComments((array) ($data['content_comments'] ?? []));
        $data['agent_settlements'] = $this->normalizeAgentSettlements((array) ($data['agent_settlements'] ?? []));
        $data['agent_settlement_notification_logs'] = $this->normalizeAgentSettlementNotificationLogs((array) ($data['agent_settlement_notification_logs'] ?? []));
        $data['agent_payout_batches'] = $this->normalizeAgentPayoutBatches((array) ($data['agent_payout_batches'] ?? []));
        $data['operation_alert_notifications'] = $this->normalizeOperationAlertNotifications((array) ($data['operation_alert_notifications'] ?? []));
        $data['operation_alert_notification_config'] = $this->normalizeOperationAlertNotificationConfig((array) ($data['operation_alert_notification_config'] ?? []));
        $data['operation_alert_notification_receivers'] = $this->normalizeOperationAlertNotificationReceivers((array) ($data['operation_alert_notification_receivers'] ?? []));
        $data['operation_alert_notification_logs'] = $this->normalizeOperationAlertNotificationLogs((array) ($data['operation_alert_notification_logs'] ?? []));
        $data['promotion_stop_tasks'] = $this->normalizePromotionStopTasks((array) ($data['promotion_stop_tasks'] ?? []));
        $data['analytics_review_tasks'] = $this->normalizeAnalyticsReviewTasks((array) ($data['analytics_review_tasks'] ?? []));
        $data['promotion_stop_adapter_configs'] = $this->normalizePromotionStopAdapterConfigs((array) ($data['promotion_stop_adapter_configs'] ?? []));
        $data['landing_pages'] = $this->normalizeLandingPages((array) ($data['landing_pages'] ?? []));
        $data['landing_page_events'] = $this->normalizeLandingPageEvents((array) ($data['landing_page_events'] ?? []));
        $data['ad_platform_configs'] = $this->normalizeAdPlatformConfigs((array) ($data['ad_platform_configs'] ?? []));
        $data['ad_waterfall_config'] = $this->normalizeAdWaterfallConfig((array) ($data['ad_waterfall_config'] ?? []));
        $data['ad_delivery_rules'] = $this->normalizeAdDeliveryRules((array) ($data['ad_delivery_rules'] ?? []));
        $data['ad_slots'] = $this->normalizeAdSlots((array) ($data['ad_slots'] ?? []));
        $data['ad_events'] = $this->normalizeAdEvents((array) ($data['ad_events'] ?? []));
        $data['mini_program_configs'] = $this->normalizeMiniProgramConfigs((array) ($data['mini_program_configs'] ?? []));
        $data['mini_program_sync_tasks'] = $this->normalizeMiniProgramSyncTasks((array) ($data['mini_program_sync_tasks'] ?? []));
        $data['system_config_fragments'] = $this->normalizeSystemConfigFragments((array) ($data['system_config_fragments'] ?? []));
        $data['config_change_requests'] = $this->normalizeConfigChangeRequests((array) ($data['config_change_requests'] ?? []));
        $data['config_change_notification_logs'] = $this->normalizeConfigChangeNotificationLogs((array) ($data['config_change_notification_logs'] ?? []));
        $data['app_config_delivery_logs'] = $this->normalizeAppConfigDeliveryLogs((array) ($data['app_config_delivery_logs'] ?? []));
        $data['filter_presets'] = $this->normalizeFilterPresets((array) ($data['filter_presets'] ?? []));
        $data['admin_operation_logs'] = $this->normalizeAdminOperationLogs((array) ($data['admin_operation_logs'] ?? []));
        $data['email_delivery_logs'] = $this->normalizeEmailDeliveryLogs((array) ($data['email_delivery_logs'] ?? []));
        $data['message_templates'] = $this->normalizeMessageTemplates((array) ($data['message_templates'] ?? []));
        $data['in_app_messages'] = $this->normalizeInAppMessages((array) ($data['in_app_messages'] ?? []));
        $data['rights_repair_logs'] = $this->normalizeRightsRepairLogs((array) ($data['rights_repair_logs'] ?? []));
        $data['redeem_codes'] = $this->normalizeRedeemCodes((array) ($data['redeem_codes'] ?? []));
        $data['redeem_code_logs'] = $this->normalizeRedeemCodeLogs((array) ($data['redeem_code_logs'] ?? []));
        $data['home_recommendations'] = $this->normalizeHomeRecommendations((array) ($data['home_recommendations'] ?? []));
        $data['popup_notices'] = $this->normalizePopupNotices((array) ($data['popup_notices'] ?? []));
        $data['activity_configs'] = $this->normalizeActivityConfigs((array) ($data['activity_configs'] ?? []));
        $data['activity_participation_logs'] = $this->normalizeActivityParticipationLogs((array) ($data['activity_participation_logs'] ?? []));
        $data['hot_rank_configs'] = $this->normalizeHotRankConfigs((array) ($data['hot_rank_configs'] ?? []));

        $data['vip_plans'] = array_values((array) ($data['vip_plans'] ?? []));
        if (empty($data['vip_plans'])) {
            $data['vip_plans'] = [
                ['code' => 'vip_week', 'name' => 'VIP 周卡', 'days' => 7, 'price' => 9.9, 'badge' => '轻量追剧'],
                ['code' => 'vip_month', 'name' => 'VIP 月卡', 'days' => 30, 'price' => 29.9, 'badge' => '热卖畅看'],
            ];
        }

        $data['coin_packages'] = array_values((array) ($data['coin_packages'] ?? []));
        if (empty($data['coin_packages'])) {
            $data['coin_packages'] = [
                ['code' => 'coin_6', 'name' => '600K币', 'coins' => 600, 'bonus_coins' => 0, 'price' => 6],
                ['code' => 'coin_18', 'name' => '1800K币', 'coins' => 1800, 'bonus_coins' => 120, 'price' => 18],
                ['code' => 'coin_68', 'name' => '6800K币', 'coins' => 6800, 'bonus_coins' => 680, 'price' => 68],
            ];
        }

        foreach ($data['vip_plans'] as &$plan) {
            $code = trim((string) ($plan['code'] ?? ''));
            $plan['code'] = $code !== '' ? $code : 'vip_month';
            $plan['name'] = trim((string) ($plan['name'] ?? 'VIP 套餐')) ?: 'VIP 套餐';
            $plan['days'] = max(1, (int) ($plan['days'] ?? 30));
            $plan['price'] = round(max(0, (float) ($plan['price'] ?? 0)), 2);
            $plan['badge'] = trim((string) ($plan['badge'] ?? '畅看权益'));
            $plan['enabled'] = array_key_exists('enabled', $plan) ? !empty($plan['enabled']) : true;
        }
        unset($plan);

        foreach ($data['coin_packages'] as &$package) {
            $code = trim((string) ($package['code'] ?? ''));
            $package['code'] = $code !== '' ? $code : 'coin_' . max(1, (int) ($package['price'] ?? 1));
            $package['name'] = trim((string) ($package['name'] ?? 'K币套餐')) ?: 'K币套餐';
            $package['coins'] = max(0, (int) ($package['coins'] ?? 0));
            $package['bonus_coins'] = max(0, (int) ($package['bonus_coins'] ?? 0));
            $package['price'] = round(max(0, (float) ($package['price'] ?? 0)), 2);
            $package['enabled'] = array_key_exists('enabled', $package) ? !empty($package['enabled']) : true;
        }
        unset($package);

        $data['recharge_products'] = array_values((array) ($data['recharge_products'] ?? []));
        if (empty($data['recharge_products'])) {
            foreach ($data['coin_packages'] as $package) {
                $data['recharge_products'][] = [
                    'code' => (string) ($package['code'] ?? ''),
                    'name' => (string) ($package['name'] ?? 'K币套餐'),
                    'type' => 'coin',
                    'price' => (float) ($package['price'] ?? 0),
                    'coins' => (int) ($package['coins'] ?? 0),
                    'bonus_coins' => (int) ($package['bonus_coins'] ?? 0),
                    'vip_days' => 0,
                    'unlock_count' => 0,
                    'badge' => (string) ($package['badge'] ?? ''),
                    'description' => (string) ($package['name'] ?? 'K币充值'),
                    'is_recommended' => !empty($package['is_recommended']),
                    'enabled' => !isset($package['enabled']) || !empty($package['enabled']),
                    'sort' => count($data['recharge_products']) + 1,
                ];
            }
            foreach ($data['vip_plans'] as $plan) {
                $data['recharge_products'][] = [
                    'code' => (string) ($plan['code'] ?? ''),
                    'name' => (string) ($plan['name'] ?? 'VIP 套餐'),
                    'type' => 'vip',
                    'price' => (float) ($plan['price'] ?? 0),
                    'coins' => 0,
                    'bonus_coins' => 0,
                    'vip_days' => (int) ($plan['days'] ?? 30),
                    'unlock_count' => 0,
                    'badge' => (string) ($plan['badge'] ?? '畅看权益'),
                    'description' => (string) ($plan['name'] ?? 'VIP 套餐'),
                    'is_recommended' => !empty($plan['is_recommended']),
                    'enabled' => !isset($plan['enabled']) || !empty($plan['enabled']),
                    'sort' => count($data['recharge_products']) + 1,
                ];
            }
            $data['recharge_products'][] = [
                'code' => 'unlock_full_49',
                'name' => '49元解锁全集',
                'type' => 'full_unlock',
                'price' => 49,
                'coins' => 0,
                'bonus_coins' => 0,
                'vip_days' => 0,
                'unlock_count' => 1,
                'badge' => '挽留推荐',
                'description' => '关闭弹窗时用于挽留的全集解锁商品',
                'is_recommended' => true,
                'enabled' => true,
                'sort' => count($data['recharge_products']) + 1,
            ];
        }

        $seenProductCodes = [];
        foreach ($data['recharge_products'] as $index => &$product) {
            $code = preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($product['code'] ?? ''));
            $code = $code !== '' ? $code : ('goods_' . ($index + 1));
            $baseCode = $code;
            $suffix = 2;
            while (isset($seenProductCodes[$code])) {
                $code = $baseCode . '_' . $suffix;
                $suffix++;
            }
            $seenProductCodes[$code] = true;
            $product['code'] = $code;
            $product['name'] = trim((string) ($product['name'] ?? '充值商品')) ?: '充值商品';
            $product['type'] = in_array((string) ($product['type'] ?? ''), ['coin', 'vip', 'full_unlock'], true) ? (string) $product['type'] : 'coin';
            $product['price'] = round(max(0, (float) ($product['price'] ?? 0)), 2);
            $product['coins'] = max(0, (int) ($product['coins'] ?? 0));
            $product['bonus_coins'] = max(0, (int) ($product['bonus_coins'] ?? 0));
            $product['vip_days'] = max(0, (int) ($product['vip_days'] ?? 0));
            $product['unlock_count'] = max(0, (int) ($product['unlock_count'] ?? 0));
            $product['badge'] = trim((string) ($product['badge'] ?? ''));
            $product['description'] = trim((string) ($product['description'] ?? ''));
            $product['is_recommended'] = !empty($product['is_recommended']);
            $product['enabled'] = array_key_exists('enabled', $product) ? !empty($product['enabled']) : true;
            $product['sort'] = (int) ($product['sort'] ?? ($index + 1));
        }
        unset($product);
        usort($data['recharge_products'], static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (float) ($a['price'] ?? 0)] <=> [(int) ($b['sort'] ?? 0), (float) ($b['price'] ?? 0)]);

        $data['recharge_config'] = array_merge([
            'global_product_codes' => [],
            'retention_product_code' => '',
            'retention_once_per_user' => true,
            'app_product_templates' => [],
            'updated_at' => date('Y-m-d H:i:s'),
        ], (array) ($data['recharge_config'] ?? []));
        $enabledProductCodes = array_map(static fn (array $product): string => (string) ($product['code'] ?? ''), array_filter($data['recharge_products'], static fn (array $product): bool => !empty($product['enabled'])));
        $data['recharge_config']['global_product_codes'] = array_values(array_intersect(array_map('strval', (array) ($data['recharge_config']['global_product_codes'] ?? [])), $enabledProductCodes));
        if (empty($data['recharge_config']['global_product_codes'])) {
            $data['recharge_config']['global_product_codes'] = array_slice($enabledProductCodes, 0, 6);
        }
        if (!in_array((string) ($data['recharge_config']['retention_product_code'] ?? ''), $enabledProductCodes, true)) {
            $recommended = array_values(array_filter($data['recharge_products'], static fn (array $product): bool => !empty($product['enabled']) && !empty($product['is_recommended'])));
            $data['recharge_config']['retention_product_code'] = (string) (($recommended[0]['code'] ?? '') ?: ($enabledProductCodes[0] ?? ''));
        }
        $data['recharge_config']['retention_once_per_user'] = array_key_exists('retention_once_per_user', $data['recharge_config']) ? !empty($data['recharge_config']['retention_once_per_user']) : true;
        $data['recharge_config']['app_product_templates'] = $this->normalizeRechargeProductTemplates(
            (array) ($data['recharge_config']['app_product_templates'] ?? []),
            $enabledProductCodes,
            (array) ($data['recharge_config']['global_product_codes'] ?? []),
            (string) ($data['recharge_config']['retention_product_code'] ?? '')
        );

        foreach ($data['users'] as &$user) {
            $user['coin_balance'] = max(0, (int) ($user['coin_balance'] ?? 300));
            $user['bonus_coin_balance'] = max(0, (int) ($user['bonus_coin_balance'] ?? 0));
            $user['bonus_expires_at'] = $user['bonus_expires_at'] ?? null;
            $user['auto_unlock_next'] = array_key_exists('auto_unlock_next', $user) ? !empty($user['auto_unlock_next']) : false;
            $user['tags'] = $this->normalizeListText($user['tags'] ?? [], 12, 24);
            $user['created_at'] = trim((string) ($user['created_at'] ?? '')) ?: date('Y-m-d H:i:s');
        }
        unset($user);

        foreach ($data['orders'] as &$order) {
            $order['content_type'] = in_array((string) ($order['content_type'] ?? ''), ['drama', 'novel'], true) ? (string) $order['content_type'] : 'drama';
            $order['drama_id'] = max(0, (int) ($order['drama_id'] ?? 0));
            $order['episode_id'] = empty($order['episode_id']) ? null : max(0, (int) $order['episode_id']);
            $order['novel_id'] = max(0, (int) ($order['novel_id'] ?? 0));
            $order['chapter_id'] = empty($order['chapter_id']) ? null : max(0, (int) $order['chapter_id']);
            if ($order['content_type'] === 'novel') {
                $order['drama_id'] = 0;
                $order['episode_id'] = null;
            } else {
                $order['novel_id'] = 0;
                $order['chapter_id'] = null;
            }
            $order['traffic_platform'] = $this->limitText(trim((string) ($order['traffic_platform'] ?? $order['platform'] ?? '')), 40);
            $order['channel_id'] = $this->limitText(trim((string) ($order['channel_id'] ?? $order['channel'] ?? '')), 60);
            $order['media_app_id'] = $this->limitText(trim((string) ($order['media_app_id'] ?? $order['app_id'] ?? '')), 60);
            $order['ad_id'] = $this->limitText(trim((string) ($order['ad_id'] ?? $order['advert_id'] ?? '')), 80);
            $order['creative_id'] = $this->limitText(trim((string) ($order['creative_id'] ?? $order['creativity_id'] ?? '')), 80);
            $order['material_id'] = $this->limitText(trim((string) ($order['material_id'] ?? $order['material'] ?? '')), 80);
            $order['app_key'] = $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($order['app_key'] ?? $order['media_app_id'] ?? ''))) ?: 'default', 60);
            $order['app_name'] = $this->limitText(trim((string) ($order['app_name'] ?? '')), 60);
            $order['product_template_id'] = max(0, (int) ($order['product_template_id'] ?? 0));
            $order['product_template_name'] = $this->limitText(trim((string) ($order['product_template_name'] ?? '')), 60);
            $order['product_code'] = $this->limitText(trim((string) ($order['product_code'] ?? $order['package_code'] ?? $order['plan_code'] ?? '')), 80);
            $order['product_name'] = $this->limitText(trim((string) ($order['product_name'] ?? $order['subject'] ?? '')), 80);
            $order['product_type'] = $this->limitText(trim((string) ($order['product_type'] ?? '')), 40);
        }
        unset($order);

        foreach ($data['entitlements'] as &$entitlement) {
            $entitlement['content_type'] = in_array((string) ($entitlement['content_type'] ?? ''), ['drama', 'novel'], true) ? (string) $entitlement['content_type'] : 'drama';
            $entitlement['drama_id'] = max(0, (int) ($entitlement['drama_id'] ?? 0));
            $entitlement['episode_id'] = empty($entitlement['episode_id']) ? null : max(0, (int) $entitlement['episode_id']);
            $entitlement['novel_id'] = max(0, (int) ($entitlement['novel_id'] ?? 0));
            $entitlement['chapter_id'] = empty($entitlement['chapter_id']) ? null : max(0, (int) $entitlement['chapter_id']);
            if ($entitlement['content_type'] === 'novel') {
                $entitlement['drama_id'] = 0;
                $entitlement['episode_id'] = null;
            } else {
                $entitlement['novel_id'] = 0;
                $entitlement['chapter_id'] = null;
            }
        }
        unset($entitlement);

        foreach ($data['dramas'] as $index => &$drama) {
            $episodeCount = count((array) ($drama['episodes'] ?? []));
            $freeEpisodeCount = count(array_filter((array) ($drama['episodes'] ?? []), static fn (array $episode): bool => !empty($episode['is_free'])));
            $pricePerEpisode = round(max(0, (float) ($drama['price_per_episode'] ?? 1.99)), 2);
            $drama['id'] = max(1, (int) ($drama['id'] ?? ($index + 1)));
            $drama['title'] = trim((string) ($drama['title'] ?? '未命名短剧')) ?: '未命名短剧';
            $drama['cover'] = trim((string) ($drama['cover'] ?? '/assets/cover-1.svg')) ?: '/assets/cover-1.svg';
            $drama['description'] = trim((string) ($drama['description'] ?? ''));
            $drama['author'] = $this->limitText(trim((string) ($drama['author'] ?? '')), 40);
            $drama['price_per_episode'] = $pricePerEpisode;
            $drama['membership_price'] = round(max(0, (float) ($drama['membership_price'] ?? 19.9)), 2);
            $drama['status'] = in_array((string) ($drama['status'] ?? ''), ['online', 'draft', 'offline'], true) ? (string) $drama['status'] : 'draft';
            $drama['category'] = trim((string) ($drama['category'] ?? '')) ?: $defaultCategories[$index % count($defaultCategories)];
            $drama['tags'] = $this->normalizeContentTagNames((array) ($drama['tags'] ?? [$drama['category']]));
            $drama['group_id'] = in_array((int) ($drama['group_id'] ?? 0), $contentGroupIds, true) ? (int) ($drama['group_id'] ?? 0) : 0;
            $drama['audit_status'] = $this->normalizeContentAuditStatus((string) ($drama['audit_status'] ?? $drama['status'] ?? 'draft'));
            $drama['audit_note'] = $this->limitText(trim((string) ($drama['audit_note'] ?? '')), 160);
            $drama['reviewed_by'] = $this->limitText(trim((string) ($drama['reviewed_by'] ?? '')), 60);
            $drama['reviewed_at'] = trim((string) ($drama['reviewed_at'] ?? ''));
            $drama['quality'] = in_array((string) ($drama['quality'] ?? ''), ['normal', 'featured', 'premium'], true) ? (string) $drama['quality'] : 'normal';
            $drama['is_finished'] = array_key_exists('is_finished', $drama) ? !empty($drama['is_finished']) : true;
            $drama['is_vip'] = array_key_exists('is_vip', $drama) ? !empty($drama['is_vip']) : true;
            $drama['buy_start'] = max(0, (int) ($drama['buy_start'] ?? $drama['free_episode_count'] ?? 1));
            $drama['free_episode_count'] = max(0, (int) ($drama['free_episode_count'] ?? max(1, $freeEpisodeCount)));
            $drama['episode_coin_price'] = max(1, (int) ($drama['episode_coin_price'] ?? max(1, (int) round($pricePerEpisode * 100))));
            $paidEpisodeCount = max(1, $episodeCount - (int) $drama['free_episode_count']);
            $defaultFullPrice = round(max($pricePerEpisode, $pricePerEpisode * $paidEpisodeCount * 0.72), 2);
            $drama['full_unlock_price'] = round(max(0, (float) ($drama['full_unlock_price'] ?? $defaultFullPrice)), 2);
            $drama['is_hot'] = array_key_exists('is_hot', $drama) ? !empty($drama['is_hot']) : $index < 3;
            $drama['is_new'] = array_key_exists('is_new', $drama) ? !empty($drama['is_new']) : true;
            $drama['sort'] = (int) ($drama['sort'] ?? (1000 - $index));
            $drama['views'] = max(0, (int) ($drama['views'] ?? (12800 - $index * 1900)));
            $drama['episodes'] = array_values((array) ($drama['episodes'] ?? []));
            foreach ($drama['episodes'] as $episodeIndex => &$episode) {
                $episode['id'] = max(1, (int) ($episode['id'] ?? (((int) $drama['id']) * 100 + $episodeIndex + 1)));
                $episode['title'] = trim((string) ($episode['title'] ?? '未命名分集')) ?: '未命名分集';
                $episode['duration'] = trim((string) ($episode['duration'] ?? '03:00')) ?: '03:00';
                $episode['video_url'] = trim((string) ($episode['video_url'] ?? ''));
                $episode['is_free'] = !empty($episode['is_free']);
                $episode['coin_price'] = max(0, (int) ($episode['coin_price'] ?? $drama['episode_coin_price'] ?? 199));
                $episode['sort'] = (int) ($episode['sort'] ?? ($episodeIndex + 1));
                $episode['status'] = in_array((string) ($episode['status'] ?? ''), ['online', 'draft', 'offline'], true) ? (string) $episode['status'] : (string) ($drama['status'] ?? 'draft');
                $episode['created_at'] = trim((string) ($episode['created_at'] ?? '')) ?: date('Y-m-d H:i:s');
                $episode['updated_at'] = trim((string) ($episode['updated_at'] ?? '')) ?: $episode['created_at'];
            }
            unset($episode);
            usort($drama['episodes'], static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (int) ($a['id'] ?? 0)] <=> [(int) ($b['sort'] ?? 0), (int) ($b['id'] ?? 0)]);
        }
        unset($drama);

        $data['admins'] ??= [
            [
                'id' => 1,
                'username' => 'admin',
                'password_hash' => '$2y$12$InkvljjNbpe3tMg0Yk3ojOyEWLrc2dQTQhu5XkyrgyC7RokNJc.Wi',
                'nickname' => '管理员',
                'role' => 'super_admin',
                'agent_id' => 0,
                'status' => 'active',
            ],
        ];
        foreach ($data['admins'] as $index => &$admin) {
            $admin['id'] = max(1, (int) ($admin['id'] ?? ($index + 1)));
            $admin['username'] = preg_replace('/[^a-zA-Z0-9_.@-]+/', '', trim((string) ($admin['username'] ?? ''))) ?: ('admin' . $admin['id']);
            $admin['password_hash'] = trim((string) ($admin['password_hash'] ?? ''));
            if ($admin['password_hash'] === '') {
                $admin['password_hash'] = '$2y$12$InkvljjNbpe3tMg0Yk3ojOyEWLrc2dQTQhu5XkyrgyC7RokNJc.Wi';
            }
            $admin['nickname'] = $this->limitText(trim((string) ($admin['nickname'] ?? $admin['username'])), 60);
            $admin['email'] = $this->limitText(trim((string) ($admin['email'] ?? '')), 120);
            $admin['phone'] = $this->limitText(preg_replace('/\D+/', '', (string) ($admin['phone'] ?? '')) ?: '', 20);
            $admin['role'] = in_array((string) ($admin['role'] ?? ''), ['super_admin', 'business', 'leader', 'agent', 'editor'], true) ? (string) $admin['role'] : 'super_admin';
            $admin['agent_id'] = max(0, (int) ($admin['agent_id'] ?? 0));
            if (in_array($admin['role'], ['super_admin', 'editor'], true)) {
                $admin['agent_id'] = 0;
            }
            $adminStatus = (string) ($admin['status'] ?? 'active');
            $admin['status'] = in_array($adminStatus, ['active', 'paused'], true) ? $adminStatus : 'active';
            $admin['allowed_sections'] = array_values(array_filter(array_map('strval', (array) ($admin['allowed_sections'] ?? [])), static fn (string $item): bool => trim($item) !== ''));
            $admin['created_at'] = trim((string) ($admin['created_at'] ?? '')) ?: date('Y-m-d H:i:s');
            $admin['updated_at'] = trim((string) ($admin['updated_at'] ?? '')) ?: $admin['created_at'];
        }
        unset($admin);

        foreach ($data['sms_codes'] as $index => &$smsCode) {
            if (!is_array($smsCode)) {
                $smsCode = [];
            }
            $sendStatus = (string) ($smsCode['send_status'] ?? 'mocked');
            $smsCode = [
                'id' => max(1, (int) ($smsCode['id'] ?? ($index + 1))),
                'phone' => $this->limitText(preg_replace('/\D+/', '', (string) ($smsCode['phone'] ?? '')) ?: '', 20),
                'code' => $this->limitText(preg_replace('/[^0-9]+/', '', (string) ($smsCode['code'] ?? '')) ?: '', 12),
                'scene' => $this->limitText(trim((string) ($smsCode['scene'] ?? 'login')), 40),
                'provider' => $this->limitText(trim((string) ($smsCode['provider'] ?? 'mock')), 40),
                'provider_request_id' => $this->limitText(trim((string) ($smsCode['provider_request_id'] ?? $smsCode['request_id'] ?? '')), 120),
                'sign_name' => $this->limitText(trim((string) ($smsCode['sign_name'] ?? '')), 40),
                'template_id' => $this->limitText(trim((string) ($smsCode['template_id'] ?? '')), 80),
                'message' => $this->limitText(trim((string) ($smsCode['message'] ?? '')), 240),
                'send_status' => in_array($sendStatus, ['mocked', 'queued', 'success', 'failed'], true) ? $sendStatus : 'mocked',
                'receipt_status' => $this->limitText(trim((string) ($smsCode['receipt_status'] ?? '')), 40),
                'receipt_message' => $this->limitText(trim((string) ($smsCode['receipt_message'] ?? '')), 200),
                'receipt_received_at' => trim((string) ($smsCode['receipt_received_at'] ?? '')),
                'response_payload' => is_array($smsCode['response_payload'] ?? null) ? $smsCode['response_payload'] : [],
                'used' => !empty($smsCode['used']),
                'expires_at' => trim((string) ($smsCode['expires_at'] ?? '')),
                'used_at' => trim((string) ($smsCode['used_at'] ?? '')),
                'created_at' => trim((string) ($smsCode['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }
        unset($smsCode);
        $data['sms_codes'] = array_slice(array_values((array) $data['sms_codes']), -120);

        $paymentDefaults = [
            'provider' => 'jingxiu',
            'provider_name' => '精秀聚合支付',
            'channel_name' => '精秀主通道',
            'payment_method' => 'alipay',
            'payment_method_name' => '支付宝',
            'default_route_id' => 'jingxiu_alipay',
            'enabled' => false,
            'api_url' => '',
            'merchant_id' => '',
            'app_id' => '',
            'secret_key' => '',
            'merchant_private_key' => '',
            'platform_public_key' => '',
            'pay_type' => 'alipayWap',
            'pay_channel_id' => '',
            'channel_code' => '',
            'request_method' => 'POST',
            'amount_unit' => 'yuan',
            'sign_type' => 'RSA2',
            'charset' => 'utf-8',
            'version' => '1.0',
            'request_timeout' => 12,
            'notify_url' => '',
            'return_url' => '',
            'notify_success_text' => 'success',
            'notes' => '精秀交易 API：请求地址后拼接 method，例如 https://gateway.jxpays.com/pay.order/create；公共参数使用 mchid/method/charset/sign_type/timestamp/version/biz_content/sign；签名方式 RSA2；下单金额字段 total_amount，单位元。',
            'route_policy' => [
                'mode' => 'default',
                'success_window_days' => 7,
                'min_sample_orders' => 3,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            'routes' => [],
        ];
        $data['payment_config'] = array_merge($paymentDefaults, $data['payment_config'] ?? []);
        $routePolicy = (array) ($data['payment_config']['route_policy'] ?? []);
        $routePolicyMode = (string) ($routePolicy['mode'] ?? 'default');
        $data['payment_config']['route_policy'] = [
            'mode' => in_array($routePolicyMode, ['default', 'success_rate', 'round_robin'], true) ? $routePolicyMode : 'default',
            'success_window_days' => max(1, min(90, (int) ($routePolicy['success_window_days'] ?? 7))),
            'min_sample_orders' => max(0, min(1000, (int) ($routePolicy['min_sample_orders'] ?? 3))),
            'updated_at' => trim((string) ($routePolicy['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
        ];
        if (empty($data['payment_config']['merchant_private_key']) && empty($data['payment_config']['platform_public_key']) && ($data['payment_config']['sign_type'] ?? '') === 'MD5') {
            $data['payment_config']['pay_type'] = 'alipayWap';
            $data['payment_config']['amount_unit'] = 'yuan';
            $data['payment_config']['sign_type'] = 'RSA2';
            $data['payment_config']['notes'] = $paymentDefaults['notes'];
        }
        $payment = $data['payment_config'];
        $paymentProvider = strtolower(trim((string) ($payment['provider'] ?? 'jingxiu'))) ?: 'jingxiu';
        if ($paymentProvider === 'payjf') {
            $paymentProvider = 'superpay';
        }
        $paymentProviderName = trim((string) ($payment['provider_name'] ?? '')) ?: match ($paymentProvider) {
            'jingxiu' => '精秀聚合支付',
            'superpay' => '超级支付',
            default => strtoupper($paymentProvider),
        };
        $paymentMethod = trim((string) ($payment['payment_method'] ?? 'alipay')) ?: 'alipay';
        $paymentSignType = strtoupper(trim((string) ($payment['sign_type'] ?? '')));
        if ($paymentProvider === 'superpay') {
            $paymentSignType = $paymentSignType === 'RSA2' ? 'RSA' : ($paymentSignType ?: 'MD5');
        } else {
            $paymentSignType = $paymentSignType ?: 'RSA2';
        }
        $routeDefaults = [
            'id' => $paymentProvider . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $paymentMethod),
            'provider' => $paymentProvider,
            'provider_name' => $paymentProviderName,
            'channel_name' => $this->limitText((string) (($payment['channel_name'] ?? '') ?: ($paymentProvider === 'jingxiu' ? '精秀主通道' : $paymentProviderName)), 20),
            'payment_method' => $paymentMethod,
            'payment_method_name' => (string) ($payment['payment_method_name'] ?? '支付宝'),
            'trade_type' => (string) (($payment['trade_type'] ?? '') ?: ($payment['pay_type'] ?? 'alipayWap')),
            'channel_mode' => (string) ($payment['channel_mode'] ?? 'platform_collect'),
            'share_rate' => (string) ($payment['share_rate'] ?? '100'),
            'cost_rate' => (string) ($payment['cost_rate'] ?? '0'),
            'daily_amount_limit' => (string) ($payment['daily_amount_limit'] ?? '0'),
            'daily_order_limit' => (string) ($payment['daily_order_limit'] ?? '0'),
            'frequency_window' => (string) ($payment['frequency_window'] ?? '0'),
            'frequency_count' => (string) ($payment['frequency_count'] ?? '0'),
            'min_amount' => (string) ($payment['min_amount'] ?? '0'),
            'max_amount' => (string) ($payment['max_amount'] ?? '0'),
            'open_start_hour' => (string) ($payment['open_start_hour'] ?? '0'),
            'open_end_hour' => (string) ($payment['open_end_hour'] ?? '23'),
            'enabled' => !empty($payment['enabled']),
            'is_default' => true,
            'api_url' => (string) (($payment['api_url'] ?? '') ?: ($paymentProvider === 'superpay' ? 'http://payjf.cn' : '')),
            'merchant_id' => (string) ($payment['merchant_id'] ?? ''),
            'app_id' => (string) ($payment['app_id'] ?? ''),
            'secret_key' => (string) ($payment['secret_key'] ?? ''),
            'merchant_private_key' => (string) ($payment['merchant_private_key'] ?? ''),
            'platform_public_key' => (string) ($payment['platform_public_key'] ?? ''),
            'pay_channel_id' => (string) ($payment['pay_channel_id'] ?? ''),
            'channel_code' => (string) ($payment['channel_code'] ?? ''),
            'request_method' => (string) ($payment['request_method'] ?? 'POST'),
            'amount_unit' => (string) ($payment['amount_unit'] ?? 'yuan'),
            'sign_type' => $paymentSignType,
            'charset' => (string) ($payment['charset'] ?? 'utf-8'),
            'version' => (string) ($payment['version'] ?? '1.0'),
            'request_timeout' => (int) ($payment['request_timeout'] ?? 12),
            'notify_url' => (string) ($payment['notify_url'] ?? ''),
            'return_url' => (string) ($payment['return_url'] ?? ''),
            'notify_success_text' => (string) ($payment['notify_success_text'] ?? 'success'),
            'notes' => (string) ($payment['notes'] ?? $paymentDefaults['notes']),
        ];
        $routes = [];
        $routeIdMap = [];
        $rawRoutes = (array) ($payment['routes'] ?? []);
        $reservedNumericRouteIds = [];
        $assignedRouteIds = [];
        $nextRouteId = 1;
        foreach ($rawRoutes as $route) {
            if (!is_array($route)) {
                continue;
            }
            $oldRouteId = trim((string) ($route['id'] ?? ''));
            if (ctype_digit($oldRouteId) && (int) $oldRouteId > 0) {
                $reservedNumericRouteIds[$oldRouteId] = true;
                $nextRouteId = max($nextRouteId, (int) $oldRouteId + 1);
            }
        }
        foreach ($rawRoutes as $route) {
            if (!is_array($route)) {
                continue;
            }
            $oldRouteId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($route['id'] ?? '')) ?: '';
            if (ctype_digit($oldRouteId) && (int) $oldRouteId > 0 && !isset($assignedRouteIds[$oldRouteId])) {
                $newRouteId = $oldRouteId;
            } else {
                while (isset($reservedNumericRouteIds[(string) $nextRouteId]) || isset($assignedRouteIds[(string) $nextRouteId])) {
                    $nextRouteId++;
                }
                $newRouteId = (string) $nextRouteId;
                $nextRouteId++;
            }
            $assignedRouteIds[$newRouteId] = true;
            if ($oldRouteId !== '') {
                $routeIdMap[$oldRouteId] = $newRouteId;
            }

            $merged = array_merge($routeDefaults, $route);
            $merged['id'] = $newRouteId;
            $merged['provider'] = strtolower(trim((string) ($merged['provider'] ?? 'jingxiu'))) ?: 'jingxiu';
            if ($merged['provider'] === 'payjf') {
                $merged['provider'] = 'superpay';
            }
            $merged['provider_name'] = trim((string) ($merged['provider_name'] ?? '')) ?: match ($merged['provider']) {
                'jingxiu' => '精秀聚合支付',
                'superpay' => '超级支付',
                default => strtoupper($merged['provider']),
            };
            $merged['channel_name'] = $this->limitText(trim((string) ($merged['channel_name'] ?? '')) ?: $merged['provider_name'], 20);
            $merged['payment_method'] = trim((string) ($merged['payment_method'] ?? 'alipay')) ?: 'alipay';
            $merged['payment_method_name'] = trim((string) ($merged['payment_method_name'] ?? '')) ?: $merged['payment_method'];
            $legacyAutoRouteId = $merged['provider'] . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $merged['payment_method']);
            if ($legacyAutoRouteId !== '_' && !isset($routeIdMap[$legacyAutoRouteId])) {
                $routeIdMap[$legacyAutoRouteId] = $newRouteId;
            }
            $defaultTradeType = $merged['provider'] === 'superpay' ? $merged['payment_method'] : 'alipayWap';
            $merged['trade_type'] = trim((string) (($merged['trade_type'] ?? '') ?: ($merged['pay_type'] ?? $defaultTradeType))) ?: $defaultTradeType;
            $merged['pay_type'] = $merged['trade_type'];
            $merged['channel_mode'] = trim((string) ($merged['channel_mode'] ?? 'platform_collect')) ?: 'platform_collect';
            $merged['share_rate'] = (string) max(0, (float) ($merged['share_rate'] ?? 100));
            $merged['cost_rate'] = (string) max(0, (float) ($merged['cost_rate'] ?? 0));
            $merged['daily_amount_limit'] = (string) max(0, (float) ($merged['daily_amount_limit'] ?? 0));
            $merged['daily_order_limit'] = (string) max(0, (int) ($merged['daily_order_limit'] ?? 0));
            $merged['frequency_window'] = (string) max(0, (int) ($merged['frequency_window'] ?? 0));
            $merged['frequency_count'] = (string) max(0, (int) ($merged['frequency_count'] ?? 0));
            $merged['min_amount'] = (string) max(0, (float) ($merged['min_amount'] ?? 0));
            $merged['max_amount'] = (string) max(0, (float) ($merged['max_amount'] ?? 0));
            $merged['open_start_hour'] = (string) min(23, max(0, (int) ($merged['open_start_hour'] ?? 0)));
            $merged['open_end_hour'] = (string) min(23, max(0, (int) ($merged['open_end_hour'] ?? 23)));
            if ($merged['provider'] === 'superpay' && trim((string) ($merged['api_url'] ?? '')) === '') {
                $merged['api_url'] = 'http://payjf.cn';
            }
            $merged['sign_type'] = strtoupper(trim((string) ($merged['sign_type'] ?? '')));
            if ($merged['provider'] === 'superpay' && empty($route['sign_type'])) {
                $merged['sign_type'] = 'MD5';
            } elseif ($merged['provider'] === 'superpay' && $merged['sign_type'] === 'RSA2') {
                $merged['sign_type'] = 'RSA';
            } elseif ($merged['sign_type'] === '') {
                $merged['sign_type'] = $merged['provider'] === 'superpay' ? 'MD5' : 'RSA2';
            }
            $merged['enabled'] = !empty($merged['enabled']);
            $merged['is_default'] = !empty($merged['is_default']);
            $merged['request_timeout'] = max(1, (int) ($merged['request_timeout'] ?? 12));
            $merged['notes'] = $this->limitText(trim((string) ($merged['notes'] ?? '')), 20);
            $routes[] = $merged;
        }
        if (empty($routes)) {
            $oldDefaultRouteId = (string) ($routeDefaults['id'] ?? '');
            $routeDefaults['id'] = '1';
            $routeDefaults['pay_type'] = $routeDefaults['trade_type'];
            $routeDefaults['notes'] = $this->limitText(trim((string) ($routeDefaults['notes'] ?? '')), 20);
            if ($oldDefaultRouteId !== '') {
                $routeIdMap[$oldDefaultRouteId] = '1';
            }
            $routeIdMap[$paymentProvider . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $paymentMethod)] = '1';
            $routes[] = $routeDefaults;
        }
        $defaultRouteId = (string) ($payment['default_route_id'] ?? '');
        if ($defaultRouteId !== '' && isset($routeIdMap[$defaultRouteId])) {
            $defaultRouteId = $routeIdMap[$defaultRouteId];
        }
        $defaultIndex = 0;
        foreach ($routes as $index => $route) {
            if ((!empty($defaultRouteId) && (string) ($route['id'] ?? '') === $defaultRouteId) || !empty($route['is_default'])) {
                $defaultIndex = $index;
                break;
            }
        }
        foreach ($routes as $index => &$route) {
            $route['is_default'] = $index === $defaultIndex;
        }
        unset($route);
        $defaultRoute = $routes[$defaultIndex];
        foreach ($data['orders'] as &$order) {
            $orderRouteId = trim((string) ($order['payment_route_id'] ?? ''));
            if ($orderRouteId !== '' && isset($routeIdMap[$orderRouteId])) {
                $order['payment_route_id'] = $routeIdMap[$orderRouteId];
                continue;
            }
            if ($orderRouteId !== '' && ctype_digit($orderRouteId)) {
                continue;
            }
            $orderProvider = strtolower(trim((string) ($order['payment_provider'] ?? '')));
            $orderMethod = trim((string) ($order['payment_method'] ?? ''));
            foreach ($routes as $route) {
                if (
                    $orderProvider !== ''
                    && $orderMethod !== ''
                    && (string) ($route['provider'] ?? '') === $orderProvider
                    && (string) ($route['payment_method'] ?? '') === $orderMethod
                ) {
                    $order['payment_route_id'] = (string) ($route['id'] ?? '');
                    break;
                }
            }
        }
        unset($order);
        $data['payment_config'] = array_merge($paymentDefaults, $payment, [
            'provider' => $defaultRoute['provider'],
            'provider_name' => $defaultRoute['provider_name'],
            'channel_name' => $defaultRoute['channel_name'],
            'payment_method' => $defaultRoute['payment_method'],
            'payment_method_name' => $defaultRoute['payment_method_name'],
            'pay_type' => $defaultRoute['trade_type'],
            'channel_mode' => $defaultRoute['channel_mode'] ?? 'platform_collect',
            'share_rate' => $defaultRoute['share_rate'] ?? '100',
            'cost_rate' => $defaultRoute['cost_rate'] ?? '0',
            'daily_amount_limit' => $defaultRoute['daily_amount_limit'] ?? '0',
            'daily_order_limit' => $defaultRoute['daily_order_limit'] ?? '0',
            'frequency_window' => $defaultRoute['frequency_window'] ?? '0',
            'frequency_count' => $defaultRoute['frequency_count'] ?? '0',
            'min_amount' => $defaultRoute['min_amount'] ?? '0',
            'max_amount' => $defaultRoute['max_amount'] ?? '0',
            'open_start_hour' => $defaultRoute['open_start_hour'] ?? '0',
            'open_end_hour' => $defaultRoute['open_end_hour'] ?? '23',
            'default_route_id' => $defaultRoute['id'],
            'enabled' => !empty($defaultRoute['enabled']),
            'api_url' => $defaultRoute['api_url'],
            'merchant_id' => $defaultRoute['merchant_id'],
            'app_id' => $defaultRoute['app_id'],
            'secret_key' => $defaultRoute['secret_key'],
            'merchant_private_key' => $defaultRoute['merchant_private_key'],
            'platform_public_key' => $defaultRoute['platform_public_key'],
            'pay_channel_id' => $defaultRoute['pay_channel_id'],
            'channel_code' => $defaultRoute['channel_code'],
            'request_method' => $defaultRoute['request_method'],
            'amount_unit' => $defaultRoute['amount_unit'],
            'sign_type' => $defaultRoute['sign_type'],
            'charset' => $defaultRoute['charset'],
            'version' => $defaultRoute['version'],
            'request_timeout' => $defaultRoute['request_timeout'],
            'notify_url' => $defaultRoute['notify_url'],
            'return_url' => $defaultRoute['return_url'],
            'notify_success_text' => $defaultRoute['notify_success_text'],
            'notes' => $defaultRoute['notes'],
            'routes' => $routes,
        ]);

        $siteDefaults = [
            'site_name' => '精秀短剧',
            'icp_number' => '',
            'homepage_template' => 'mini',
            'novel_homepage_template' => 'library',
            'config_approval_policy' => [
                'payment_config' => false,
                'app_config' => false,
                'quiet_hours_enabled' => false,
                'quiet_start' => '22:00',
                'quiet_end' => '08:00',
                'escalate_after_multiplier' => 2,
            ],
        ];
        $data['site_config'] = array_merge($siteDefaults, $data['site_config'] ?? []);
        if (!in_array((string) ($data['site_config']['homepage_template'] ?? ''), ['mini', 'marketing', 'diy'], true)) {
            $data['site_config']['homepage_template'] = 'mini';
        }
        if (!in_array((string) ($data['site_config']['novel_homepage_template'] ?? ''), ['library', 'ranking'], true)) {
            $data['site_config']['novel_homepage_template'] = 'library';
        }
        $approvalPolicy = (array) ($data['site_config']['config_approval_policy'] ?? []);
        $data['site_config']['config_approval_policy'] = [
            'payment_config' => !empty($approvalPolicy['payment_config']),
            'app_config' => !empty($approvalPolicy['app_config']),
            'quiet_hours_enabled' => !empty($approvalPolicy['quiet_hours_enabled']),
            'quiet_start' => $this->normalizeTimeOfDay((string) ($approvalPolicy['quiet_start'] ?? '22:00'), '22:00'),
            'quiet_end' => $this->normalizeTimeOfDay((string) ($approvalPolicy['quiet_end'] ?? '08:00'), '08:00'),
            'escalate_after_multiplier' => max(1, min(12, (int) ($approvalPolicy['escalate_after_multiplier'] ?? 2))),
        ];
        $data['site_config']['sms_config'] = $this->normalizeSmsConfig((array) ($data['site_config']['sms_config'] ?? []));
        $data['site_config']['email_config'] = $this->normalizeEmailConfig((array) ($data['site_config']['email_config'] ?? []));
        $templateKeys = array_map(static fn (array $template): string => (string) ($template['app_key'] ?? ''), (array) ($data['recharge_config']['app_product_templates'] ?? []));
        $paymentRouteIds = array_map(static fn (array $route): string => (string) ($route['id'] ?? ''), (array) ($data['payment_config']['routes'] ?? []));
        $data['apps'] = $this->normalizeApps(
            (array) ($data['apps'] ?? []),
            $templateKeys,
            $paymentRouteIds,
            (string) ($data['payment_config']['default_route_id'] ?? ($paymentRouteIds[0] ?? '')),
            (string) ($data['site_config']['site_name'] ?? '精秀短剧')
        );

        $designDefaults = [
            'updated_at' => date('Y-m-d H:i:s'),
            'pages' => [
                ['id' => 'home-diy', 'name' => 'DIY 短剧首页', 'template' => 'diy', 'header_style' => '自定义头部', 'password_access' => false, 'member_access' => false],
                ['id' => 'home-mini', 'name' => '小程序风格首页', 'template' => 'mini', 'header_style' => '沉浸式头部', 'password_access' => false, 'member_access' => false],
                ['id' => 'home-marketing', 'name' => '经典 H5 营销页', 'template' => 'marketing', 'header_style' => '常规头部', 'password_access' => false, 'member_access' => false],
                ['id' => 'user-center', 'name' => '个人中心', 'template' => 'center', 'header_style' => '自定义头部', 'password_access' => false, 'member_access' => true],
                ['id' => 'pay-success', 'name' => '购买成功页', 'template' => 'payment', 'header_style' => '沉浸式头部', 'password_access' => false, 'member_access' => false],
            ],
            'home' => [
                'brand_title' => '精秀短剧',
                'search_placeholder' => '请搜索您感兴趣的剧...',
                'hero_title' => '逆袭总裁的秘密',
                'hero_subtitle' => '今日热播 · 支付即解锁',
                'notice_text' => '添加到「我的小程序」追剧更方便',
                'section_title' => '全部剧集',
                'primary_color' => '#ef5b5f',
                'accent_color' => '#ff955d',
                'modules' => ['search', 'banner', 'quick_nav', 'notice', 'rank', 'drama_grid', 'reward', 'bottom_nav'],
                'quick_navs' => [
                    ['label' => '热门榜', 'link' => '#rank'],
                    ['label' => '新剧', 'link' => '#all-dramas'],
                    ['label' => '会员', 'link' => '/?route=center'],
                    ['label' => '福利', 'link' => '/?route=bind'],
                ],
            ],
        ];
        $data['design_config'] = array_replace_recursive($designDefaults, $data['design_config'] ?? []);
        $data['design_config']['home']['modules'] = array_values(array_intersect(
            ['search', 'banner', 'quick_nav', 'notice', 'rank', 'drama_grid', 'reward', 'bottom_nav'],
            array_map('strval', (array) ($data['design_config']['home']['modules'] ?? []))
        ));
        if (empty($data['design_config']['home']['modules'])) {
            $data['design_config']['home']['modules'] = $designDefaults['home']['modules'];
        }

        return $data;
    }

    private function limitText(string $value, int $maxLength): string
    {
        if ($maxLength < 1) {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength, 'UTF-8');
        }

        preg_match_all('/./us', $value, $matches);
        if (!empty($matches[0])) {
            return implode('', array_slice($matches[0], 0, $maxLength));
        }

        return substr($value, 0, $maxLength);
    }

    private function normalizeListText(mixed $value, int $limit = 20, int $maxLength = 40, bool $identifierOnly = false): array
    {
        $items = is_array($value) ? $value : preg_split('/[\s,，|]+/u', (string) $value);
        $normalized = [];
        foreach ((array) $items as $item) {
            $text = trim((string) $item);
            if ($identifierOnly) {
                $text = preg_replace('/[^a-zA-Z0-9_-]+/', '', $text) ?: '';
            }
            $text = $this->limitText($text, $maxLength);
            if ($text === '' || in_array($text, $normalized, true)) {
                continue;
            }
            $normalized[] = $text;
        }

        return array_slice($normalized, 0, max(1, $limit));
    }

    private function normalizeIntegerList(mixed $value, int $limit = 100): array
    {
        $items = is_array($value) ? $value : preg_split('/[\s,，|]+/u', (string) $value);
        $normalized = [];
        foreach ((array) $items as $item) {
            $number = max(0, (int) $item);
            if ($number <= 0 || in_array($number, $normalized, true)) {
                continue;
            }
            $normalized[] = $number;
        }

        return array_slice($normalized, 0, max(1, $limit));
    }

    private function normalizeContentTagNames(array $tags): array
    {
        $normalized = [];
        foreach ($tags as $tag) {
            $name = $this->limitText(trim((string) $tag), 24);
            if ($name === '' || in_array($name, $normalized, true)) {
                continue;
            }
            $normalized[] = $name;
        }

        return array_slice($normalized, 0, 12);
    }

    private function normalizeContentAuditStatus(string $status): string
    {
        return in_array($status, ['draft', 'pending', 'approved', 'rejected', 'online', 'offline'], true) ? $status : 'draft';
    }

    private function normalizeMediaContents(array $items, array $defaultCategories): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = in_array((string) ($item['type'] ?? ''), ['image', 'h5'], true) ? (string) $item['type'] : 'image';
            $categoryFallback = $defaultCategories[$index % max(1, count($defaultCategories))] ?? '都市';
            $now = date('Y-m-d H:i:s');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'type' => $type,
                'title' => $this->limitText(trim((string) ($item['title'] ?? ($type === 'h5' ? '未命名H5' : '未命名壁纸'))), 100),
                'cover' => trim((string) ($item['cover'] ?? '/assets/cover-1.svg')) ?: '/assets/cover-1.svg',
                'resource_url' => trim((string) ($item['resource_url'] ?? $item['url'] ?? '')),
                'author' => $this->limitText(trim((string) ($item['author'] ?? '')), 40),
                'category' => trim((string) ($item['category'] ?? $categoryFallback)) ?: $categoryFallback,
                'tags' => $this->normalizeContentTagNames((array) ($item['tags'] ?? [$item['category'] ?? $categoryFallback])),
                'price_coins' => max(0, (int) ($item['price_coins'] ?? $item['coin_price'] ?? 99)),
                'is_finished' => array_key_exists('is_finished', $item) ? !empty($item['is_finished']) : true,
                'is_vip' => array_key_exists('is_vip', $item) ? !empty($item['is_vip']) : true,
                'buy_start' => max(0, (int) ($item['buy_start'] ?? 1)),
                'read_count' => max(0, (int) ($item['read_count'] ?? $item['views'] ?? 0)),
                'quality' => in_array((string) ($item['quality'] ?? ''), ['normal', 'featured', 'premium'], true) ? (string) $item['quality'] : 'normal',
                'status' => in_array((string) ($item['status'] ?? ''), ['online', 'draft', 'offline'], true) ? (string) $item['status'] : 'draft',
                'description' => $this->limitText(trim((string) ($item['description'] ?? '')), 500),
                'sort' => (int) ($item['sort'] ?? ($index + 1)),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: $now,
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: $now,
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (int) ($a['id'] ?? 0)] <=> [(int) ($b['sort'] ?? 0), (int) ($b['id'] ?? 0)]);

        return $normalized;
    }

    private function normalizeFeedbackItems(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $type = (string) ($item['type'] ?? 'feedback');
            $status = (string) ($item['status'] ?? 'pending');
            $priority = (string) ($item['priority'] ?? 'normal');
            $contentType = (string) ($item['content_type'] ?? '');
            $slaStatus = (string) ($item['sla_status'] ?? 'normal');
            $suggestedAction = (string) ($item['suggested_action'] ?? 'none');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'type' => in_array($type, ['feedback', 'complaint', 'payment', 'content', 'account', 'promotion'], true) ? $type : 'feedback',
                'status' => in_array($status, ['pending', 'processing', 'resolved', 'rejected'], true) ? $status : 'pending',
                'priority' => in_array($priority, ['low', 'normal', 'high', 'urgent'], true) ? $priority : 'normal',
                'user_id' => max(0, (int) ($item['user_id'] ?? 0)),
                'nickname' => $this->limitText(trim((string) ($item['nickname'] ?? '')), 60),
                'phone' => $this->limitText(trim((string) ($item['phone'] ?? '')), 32),
                'contact' => $this->limitText(trim((string) ($item['contact'] ?? '')), 80),
                'app_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? $item['media_app_id'] ?? ''))) ?: 'default', 60),
                'app_name' => $this->limitText(trim((string) ($item['app_name'] ?? '')), 60),
                'title' => $this->limitText(trim((string) ($item['title'] ?? '')), 80),
                'content' => $this->limitText(trim((string) ($item['content'] ?? '')), 1000),
                'reply' => $this->limitText(trim((string) ($item['reply'] ?? '')), 500),
                'order_no' => $this->limitText(trim((string) ($item['order_no'] ?? '')), 80),
                'content_type' => in_array($contentType, ['drama', 'novel'], true) ? $contentType : '',
                'drama_id' => max(0, (int) ($item['drama_id'] ?? 0)),
                'episode_id' => max(0, (int) ($item['episode_id'] ?? 0)),
                'novel_id' => max(0, (int) ($item['novel_id'] ?? 0)),
                'chapter_id' => max(0, (int) ($item['chapter_id'] ?? 0)),
                'promotion_link_id' => max(0, (int) ($item['promotion_link_id'] ?? 0)),
                'promotion_code' => preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($item['promotion_code'] ?? $item['code'] ?? '')),
                'agent_id' => max(0, (int) ($item['agent_id'] ?? 0)),
                'traffic_platform' => $this->limitText(trim((string) ($item['traffic_platform'] ?? $item['platform'] ?? '')), 40),
                'channel_id' => $this->limitText(trim((string) ($item['channel_id'] ?? $item['channel'] ?? '')), 60),
                'media_app_id' => $this->limitText(trim((string) ($item['media_app_id'] ?? $item['app_id'] ?? '')), 60),
                'ad_id' => $this->limitText(trim((string) ($item['ad_id'] ?? $item['advert_id'] ?? '')), 80),
                'creative_id' => $this->limitText(trim((string) ($item['creative_id'] ?? $item['creativity_id'] ?? '')), 80),
                'material_id' => $this->limitText(trim((string) ($item['material_id'] ?? $item['material'] ?? '')), 80),
                'handled_by_admin_id' => max(0, (int) ($item['handled_by_admin_id'] ?? 0)),
                'handled_by_admin_name' => $this->limitText(trim((string) ($item['handled_by_admin_name'] ?? '')), 60),
                'handled_at' => trim((string) ($item['handled_at'] ?? '')),
                'sla_hours' => max(1, (int) ($item['sla_hours'] ?? 24)),
                'due_at' => trim((string) ($item['due_at'] ?? '')),
                'sla_status' => in_array($slaStatus, ['normal', 'due_soon', 'overdue', 'handled_on_time', 'handled_overdue'], true) ? $slaStatus : 'normal',
                'handled_minutes' => max(0, (int) ($item['handled_minutes'] ?? 0)),
                'suggested_action' => in_array($suggestedAction, ['none', 'contact_user', 'check_order', 'query_payment', 'refund', 'rights_repair', 'content_review', 'promotion_review'], true) ? $suggestedAction : 'none',
                'suggested_reason' => $this->limitText(trim((string) ($item['suggested_reason'] ?? '')), 160),
                'order_snapshot' => is_array($item['order_snapshot'] ?? null) ? $item['order_snapshot'] : [],
                'user_snapshot' => is_array($item['user_snapshot'] ?? null) ? $item['user_snapshot'] : [],
                'ip' => $this->limitText(trim((string) ($item['ip'] ?? '')), 64),
                'user_agent' => $this->limitText(trim((string) ($item['user_agent'] ?? '')), 220),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        return array_slice($normalized, 0, 5000);
    }

    private function normalizeContentComments(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $contentType = (string) ($item['content_type'] ?? 'drama') === 'novel' ? 'novel' : 'drama';
            $contentId = max(0, (int) ($item['content_id'] ?? ($contentType === 'novel' ? ($item['novel_id'] ?? 0) : ($item['drama_id'] ?? 0))));
            if ($contentId <= 0) {
                continue;
            }
            $unitId = max(0, (int) ($item['unit_id'] ?? ($contentType === 'novel' ? ($item['chapter_id'] ?? 0) : ($item['episode_id'] ?? 0))));
            $status = (string) ($item['status'] ?? 'pending');
            $source = (string) ($item['source'] ?? 'api');
            $riskLevel = (string) ($item['risk_level'] ?? 'normal');
            $sentiment = (string) ($item['sentiment'] ?? 'neutral');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'content_type' => $contentType,
                'content_id' => $contentId,
                'drama_id' => $contentType === 'drama' ? $contentId : 0,
                'episode_id' => $contentType === 'drama' ? $unitId : 0,
                'novel_id' => $contentType === 'novel' ? $contentId : 0,
                'chapter_id' => $contentType === 'novel' ? $unitId : 0,
                'unit_id' => $unitId,
                'user_id' => max(0, (int) ($item['user_id'] ?? 0)),
                'nickname' => $this->limitText(trim((string) ($item['nickname'] ?? '')), 60),
                'avatar' => trim((string) ($item['avatar'] ?? '')),
                'phone' => $this->limitText(trim((string) ($item['phone'] ?? '')), 32),
                'app_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? $item['media_app_id'] ?? ''))) ?: 'default', 60),
                'rating' => max(1, min(5, (int) ($item['rating'] ?? 5))),
                'content' => $this->limitText(trim((string) ($item['content'] ?? $item['comment'] ?? '')), 600),
                'reply' => $this->limitText(trim((string) ($item['reply'] ?? '')), 500),
                'status' => in_array($status, ['pending', 'approved', 'rejected', 'hidden'], true) ? $status : 'pending',
                'risk_level' => in_array($riskLevel, ['normal', 'sensitive', 'spam'], true) ? $riskLevel : 'normal',
                'sentiment' => in_array($sentiment, ['positive', 'neutral', 'negative'], true) ? $sentiment : 'neutral',
                'source' => in_array($source, ['api', 'admin', 'import'], true) ? $source : 'api',
                'likes' => max(0, (int) ($item['likes'] ?? $item['like_count'] ?? 0)),
                'reports' => max(0, (int) ($item['reports'] ?? $item['report_count'] ?? 0)),
                'is_pinned' => !empty($item['is_pinned']),
                'is_featured' => !empty($item['is_featured']),
                'promotion_link_id' => max(0, (int) ($item['promotion_link_id'] ?? 0)),
                'promotion_code' => preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($item['promotion_code'] ?? $item['code'] ?? '')),
                'agent_id' => max(0, (int) ($item['agent_id'] ?? 0)),
                'traffic_platform' => $this->limitText(trim((string) ($item['traffic_platform'] ?? $item['platform'] ?? '')), 40),
                'channel_id' => $this->limitText(trim((string) ($item['channel_id'] ?? $item['channel'] ?? '')), 60),
                'media_app_id' => $this->limitText(trim((string) ($item['media_app_id'] ?? $item['app_id'] ?? '')), 60),
                'ad_id' => $this->limitText(trim((string) ($item['ad_id'] ?? $item['advert_id'] ?? '')), 80),
                'creative_id' => $this->limitText(trim((string) ($item['creative_id'] ?? $item['creativity_id'] ?? '')), 80),
                'material_id' => $this->limitText(trim((string) ($item['material_id'] ?? $item['material'] ?? '')), 80),
                'reviewed_by_admin_id' => max(0, (int) ($item['reviewed_by_admin_id'] ?? 0)),
                'reviewed_by_admin_name' => $this->limitText(trim((string) ($item['reviewed_by_admin_name'] ?? '')), 60),
                'reviewed_at' => trim((string) ($item['reviewed_at'] ?? '')),
                'ip' => $this->limitText(trim((string) ($item['ip'] ?? '')), 64),
                'user_agent' => $this->limitText(trim((string) ($item['user_agent'] ?? '')), 220),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [
            !empty($b['is_pinned']) ? 1 : 0,
            (string) ($b['created_at'] ?? ''),
            (int) ($b['id'] ?? 0),
        ] <=> [
            !empty($a['is_pinned']) ? 1 : 0,
            (string) ($a['created_at'] ?? ''),
            (int) ($a['id'] ?? 0),
        ]);

        return array_slice($normalized, 0, 10000);
    }

    private function normalizeAgentSettlements(array $items): array
    {
        $normalized = [];
        $seenKeys = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $agentId = max(0, (int) ($item['agent_id'] ?? 0));
            if ($agentId <= 0) {
                continue;
            }
            $periodStart = $this->normalizeDate((string) ($item['period_start'] ?? $item['started_at'] ?? ''), date('Y-m-01'));
            $periodEnd = $this->normalizeDate((string) ($item['period_end'] ?? $item['ended_at'] ?? ''), date('Y-m-d'));
            if ($periodEnd < $periodStart) {
                [$periodStart, $periodEnd] = [$periodEnd, $periodStart];
            }
            $settlementMode = (string) ($item['settlement_mode'] ?? 'revenue_share');
            $status = (string) ($item['status'] ?? 'pending');
            $fingerprint = $this->limitText(preg_replace('/[^a-zA-Z0-9:_-]+/', '', (string) ($item['fingerprint'] ?? '')), 120);
            if ($fingerprint === '') {
                $fingerprint = 'agent:' . $agentId . ':' . $periodStart . ':' . $periodEnd . ':' . $settlementMode;
            }
            if (isset($seenKeys[$fingerprint])) {
                continue;
            }
            $seenKeys[$fingerprint] = true;
            $grossRevenue = round(max(0.0, (float) ($item['gross_revenue'] ?? 0)), 2);
            $refundAmount = round(max(0.0, (float) ($item['refund_amount'] ?? 0)), 2);
            $netRevenue = round(max(0.0, (float) ($item['net_revenue'] ?? max(0.0, $grossRevenue - $refundAmount))), 2);
            $costAmount = round(max(0.0, (float) ($item['cost_amount'] ?? 0)), 2);
            $commissionBase = round(max(0.0, (float) ($item['commission_base'] ?? ($settlementMode === 'profit_share' ? max(0.0, $netRevenue - $costAmount) : $netRevenue))), 2);
            $commissionRate = round(max(0.0, min(100.0, (float) ($item['commission_rate'] ?? 10))), 2);
            $commissionAmount = round(max(0.0, (float) ($item['commission_amount'] ?? ($commissionBase * $commissionRate / 100))), 2);

            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'fingerprint' => $fingerprint,
                'agent_id' => $agentId,
                'agent_name' => $this->limitText(trim((string) ($item['agent_name'] ?? '投放账号')), 80),
                'agent_role' => in_array((string) ($item['agent_role'] ?? ''), ['business', 'leader', 'agent'], true) ? (string) $item['agent_role'] : 'agent',
                'leader_id' => max(0, (int) ($item['leader_id'] ?? 0)),
                'leader_name' => $this->limitText(trim((string) ($item['leader_name'] ?? '')), 80),
                'business_id' => max(0, (int) ($item['business_id'] ?? 0)),
                'business_name' => $this->limitText(trim((string) ($item['business_name'] ?? '')), 80),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'settlement_mode' => in_array($settlementMode, ['revenue_share', 'profit_share'], true) ? $settlementMode : 'revenue_share',
                'order_count' => max(0, (int) ($item['order_count'] ?? 0)),
                'paid_user_count' => max(0, (int) ($item['paid_user_count'] ?? 0)),
                'gross_revenue' => $grossRevenue,
                'refund_amount' => $refundAmount,
                'net_revenue' => $netRevenue,
                'cost_amount' => $costAmount,
                'commission_base' => $commissionBase,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'status' => in_array($status, ['pending', 'confirmed', 'paid', 'rejected'], true) ? $status : 'pending',
                'payout_method' => $this->limitText(trim((string) ($item['payout_method'] ?? '')), 60),
                'payout_account' => $this->limitText(trim((string) ($item['payout_account'] ?? '')), 120),
                'payout_name' => $this->limitText(trim((string) ($item['payout_name'] ?? '')), 80),
                'payout_reference_no' => $this->limitText(trim((string) ($item['payout_reference_no'] ?? $item['payment_reference_no'] ?? '')), 120),
                'payout_proof_url' => $this->limitText(trim((string) ($item['payout_proof_url'] ?? $item['payment_voucher_url'] ?? '')), 240),
                'payout_proof_file_name' => $this->limitText(trim((string) ($item['payout_proof_file_name'] ?? '')), 160),
                'payout_proof_file_size' => max(0, (int) ($item['payout_proof_file_size'] ?? 0)),
                'payout_proof_mime' => $this->limitText(trim((string) ($item['payout_proof_mime'] ?? '')), 80),
                'payout_proof_uploaded_at' => $this->normalizeDateTime((string) ($item['payout_proof_uploaded_at'] ?? ''), ''),
                'invoice_no' => $this->limitText(trim((string) ($item['invoice_no'] ?? $item['receipt_no'] ?? '')), 120),
                'paid_at' => $this->normalizeDateTime((string) ($item['paid_at'] ?? ''), ''),
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 240),
                'agent_confirm_status' => in_array((string) ($item['agent_confirm_status'] ?? 'none'), ['none', 'confirmed', 'disputed'], true) ? (string) ($item['agent_confirm_status'] ?? 'none') : 'none',
                'agent_confirm_remark' => $this->limitText(trim((string) ($item['agent_confirm_remark'] ?? $item['agent_dispute_reason'] ?? '')), 240),
                'agent_confirmed_by_admin_id' => max(0, (int) ($item['agent_confirmed_by_admin_id'] ?? 0)),
                'agent_confirmed_by_admin_name' => $this->limitText(trim((string) ($item['agent_confirmed_by_admin_name'] ?? '')), 60),
                'agent_confirmed_by_admin_role' => $this->limitText(trim((string) ($item['agent_confirmed_by_admin_role'] ?? '')), 40),
                'agent_confirmed_at' => $this->normalizeDateTime((string) ($item['agent_confirmed_at'] ?? ''), ''),
                'dispute_status' => in_array((string) ($item['dispute_status'] ?? 'none'), ['none', 'open', 'processing', 'resolved', 'rejected'], true) ? (string) ($item['dispute_status'] ?? 'none') : 'none',
                'dispute_resolution_type' => in_array((string) ($item['dispute_resolution_type'] ?? ''), ['keep_original', 'adjust_amount', 'supplement_payout', 'reject'], true) ? (string) ($item['dispute_resolution_type'] ?? '') : '',
                'dispute_adjustment_amount' => round((float) ($item['dispute_adjustment_amount'] ?? 0), 2),
                'dispute_final_commission_amount' => round(max(0.0, (float) ($item['dispute_final_commission_amount'] ?? $commissionAmount)), 2),
                'dispute_resolution_remark' => $this->limitText(trim((string) ($item['dispute_resolution_remark'] ?? '')), 300),
                'dispute_handled_by_admin_id' => max(0, (int) ($item['dispute_handled_by_admin_id'] ?? 0)),
                'dispute_handled_by_admin_name' => $this->limitText(trim((string) ($item['dispute_handled_by_admin_name'] ?? '')), 60),
                'dispute_handled_at' => $this->normalizeDateTime((string) ($item['dispute_handled_at'] ?? ''), ''),
                'generated_by_admin_id' => max(0, (int) ($item['generated_by_admin_id'] ?? 0)),
                'generated_by_admin_name' => $this->limitText(trim((string) ($item['generated_by_admin_name'] ?? '系统')), 60),
                'handled_by_admin_id' => max(0, (int) ($item['handled_by_admin_id'] ?? 0)),
                'handled_by_admin_name' => $this->limitText(trim((string) ($item['handled_by_admin_name'] ?? '')), 60),
                'handled_at' => $this->normalizeDateTime((string) ($item['handled_at'] ?? ''), ''),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [
            (string) ($b['period_end'] ?? ''),
            ['pending' => 0, 'confirmed' => 1, 'paid' => 2, 'rejected' => 3][(string) ($a['status'] ?? 'pending')] ?? 9,
            (int) ($b['id'] ?? 0),
        ] <=> [
            (string) ($a['period_end'] ?? ''),
            ['pending' => 0, 'confirmed' => 1, 'paid' => 2, 'rejected' => 3][(string) ($b['status'] ?? 'pending')] ?? 9,
            (int) ($a['id'] ?? 0),
        ]);

        return array_slice($normalized, 0, 5000);
    }

    private function normalizeAgentSettlementNotificationLogs(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $event = preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($item['event'] ?? 'agent_settlement'));
            $channel = (string) ($item['channel'] ?? 'in_app');
            $status = (string) ($item['status'] ?? 'success');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'settlement_id' => max(0, (int) ($item['settlement_id'] ?? 0)),
                'event' => $event !== '' ? $event : 'agent_settlement',
                'channel' => in_array($channel, ['system', 'in_app', 'sms', 'email', 'webhook'], true) ? $channel : 'in_app',
                'receiver_admin_id' => max(0, (int) ($item['receiver_admin_id'] ?? 0)),
                'receiver_name' => $this->limitText(trim((string) ($item['receiver_name'] ?? '')), 80),
                'receiver_role' => $this->limitText(trim((string) ($item['receiver_role'] ?? '')), 40),
                'receiver_contact' => $this->limitText(trim((string) ($item['receiver_contact'] ?? '')), 120),
                'agent_id' => max(0, (int) ($item['agent_id'] ?? 0)),
                'leader_id' => max(0, (int) ($item['leader_id'] ?? 0)),
                'business_id' => max(0, (int) ($item['business_id'] ?? 0)),
                'title' => $this->limitText(trim((string) ($item['title'] ?? '代理结算通知')), 160),
                'message' => $this->limitText(trim((string) ($item['message'] ?? '')), 600),
                'status' => in_array($status, ['success', 'pending', 'failed', 'skipped'], true) ? $status : 'success',
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')) ?: ((int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0)));

        return array_slice($normalized, 0, 5000);
    }

    private function normalizeAgentPayoutBatches(array $items): array
    {
        $normalized = [];
        $seenBatchNos = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $batchNo = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['batch_no'] ?? '')));
            if ($batchNo === '') {
                $batchNo = 'agent-pay-' . date('YmdHis') . '-' . ($index + 1);
            }
            if (isset($seenBatchNos[$batchNo])) {
                $batchNo .= '-' . ($index + 1);
            }
            $seenBatchNos[$batchNo] = true;
            $status = (string) ($item['status'] ?? 'generated');
            $settlementIds = array_values(array_unique(array_filter(array_map('intval', (array) ($item['settlement_ids'] ?? [])), static fn (int $id): bool => $id > 0)));
            $agentIds = array_values(array_unique(array_filter(array_map('intval', (array) ($item['agent_ids'] ?? [])), static fn (int $id): bool => $id > 0)));

            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'batch_no' => $batchNo,
                'channel' => $this->limitText(trim((string) ($item['channel'] ?? '通用打款')), 60),
                'status' => in_array($status, ['generated', 'paid', 'failed', 'cancelled'], true) ? $status : 'generated',
                'item_count' => max(0, (int) ($item['item_count'] ?? count($settlementIds))),
                'total_amount' => round(max(0.0, (float) ($item['total_amount'] ?? 0)), 2),
                'settlement_ids' => array_slice($settlementIds, 0, 1000),
                'agent_ids' => array_slice($agentIds, 0, 1000),
                'filters' => is_array($item['filters'] ?? null) ? (array) $item['filters'] : [],
                'file_name' => $this->limitText(trim((string) ($item['file_name'] ?? '')), 160),
                'proof_url' => $this->limitText(trim((string) ($item['proof_url'] ?? '')), 240),
                'proof_file_name' => $this->limitText(trim((string) ($item['proof_file_name'] ?? '')), 160),
                'proof_file_size' => max(0, (int) ($item['proof_file_size'] ?? 0)),
                'proof_mime' => $this->limitText(trim((string) ($item['proof_mime'] ?? '')), 80),
                'proof_uploaded_at' => $this->normalizeDateTime((string) ($item['proof_uploaded_at'] ?? ''), ''),
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 240),
                'generated_by_admin_id' => max(0, (int) ($item['generated_by_admin_id'] ?? 0)),
                'generated_by_admin_name' => $this->limitText(trim((string) ($item['generated_by_admin_name'] ?? '系统')), 60),
                'handled_by_admin_id' => max(0, (int) ($item['handled_by_admin_id'] ?? 0)),
                'handled_by_admin_name' => $this->limitText(trim((string) ($item['handled_by_admin_name'] ?? '')), 60),
                'handled_at' => $this->normalizeDateTime((string) ($item['handled_at'] ?? ''), ''),
                'paid_at' => $this->normalizeDateTime((string) ($item['paid_at'] ?? ''), ''),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')) ?: ((int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0)));

        return array_slice($normalized, 0, 1000);
    }

    private function normalizeOperationAlertNotifications(array $items): array
    {
        $normalized = [];
        $seenFingerprints = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $type = (string) ($item['type'] ?? 'operation_alert');
            $alertTypes = ['callback_failed', 'low_conversion_material', 'high_refund_material', 'low_recovery_link', 'auto_paused_link', 'promotion_stop_failed', 'promotion_stop_auth_failed', 'promotion_stop_rate_limited', 'operation_alert'];
            $status = (string) ($item['status'] ?? 'pending');
            $priority = (string) ($item['priority'] ?? 'normal');
            $fingerprint = preg_replace('/[^a-zA-Z0-9:_-]+/', '', (string) ($item['fingerprint'] ?? ''));
            if ($fingerprint === '') {
                $fingerprint = 'alert:' . md5(json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: (string) $index);
            }
            if (isset($seenFingerprints[$fingerprint])) {
                continue;
            }
            $seenFingerprints[$fingerprint] = true;

            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'fingerprint' => $fingerprint,
                'type' => in_array($type, $alertTypes, true) ? $type : 'operation_alert',
                'status' => in_array($status, ['pending', 'processing', 'resolved', 'ignored'], true) ? $status : 'pending',
                'priority' => in_array($priority, ['low', 'normal', 'high', 'urgent'], true) ? $priority : 'normal',
                'title' => $this->limitText(trim((string) ($item['title'] ?? '投放异常预警')), 120),
                'message' => $this->limitText(trim((string) ($item['message'] ?? '')), 600),
                'suggestion' => $this->limitText(trim((string) ($item['suggestion'] ?? '')), 300),
                'promotion_link_id' => max(0, (int) ($item['promotion_link_id'] ?? 0)),
                'promotion_code' => preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($item['promotion_code'] ?? $item['code'] ?? '')),
                'order_no' => $this->limitText(trim((string) ($item['order_no'] ?? '')), 80),
                'agent_id' => max(0, (int) ($item['agent_id'] ?? 0)),
                'traffic_platform' => $this->limitText(trim((string) ($item['traffic_platform'] ?? $item['platform'] ?? '')), 40),
                'channel_id' => $this->limitText(trim((string) ($item['channel_id'] ?? $item['channel'] ?? '')), 60),
                'media_app_id' => $this->limitText(trim((string) ($item['media_app_id'] ?? $item['app_id'] ?? '')), 60),
                'ad_id' => $this->limitText(trim((string) ($item['ad_id'] ?? $item['advert_id'] ?? '')), 80),
                'creative_id' => $this->limitText(trim((string) ($item['creative_id'] ?? $item['creativity_id'] ?? '')), 80),
                'material_id' => $this->limitText(trim((string) ($item['material_id'] ?? $item['material'] ?? '')), 80),
                'metric_snapshot' => is_array($item['metric_snapshot'] ?? null) ? $item['metric_snapshot'] : [],
                'source_payload' => is_array($item['source_payload'] ?? null) ? $item['source_payload'] : [],
                'first_seen_at' => trim((string) ($item['first_seen_at'] ?? $item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'last_seen_at' => trim((string) ($item['last_seen_at'] ?? $item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'occurrence_count' => max(1, (int) ($item['occurrence_count'] ?? 1)),
                'handled_by_admin_id' => max(0, (int) ($item['handled_by_admin_id'] ?? 0)),
                'handled_by_admin_name' => $this->limitText(trim((string) ($item['handled_by_admin_name'] ?? '')), 60),
                'handled_at' => trim((string) ($item['handled_at'] ?? '')),
                'reply' => $this->limitText(trim((string) ($item['reply'] ?? '')), 500),
                'external_notify_status' => in_array((string) ($item['external_notify_status'] ?? ''), ['success', 'failed', 'skipped'], true) ? (string) $item['external_notify_status'] : '',
                'external_notify_message' => $this->limitText(trim((string) ($item['external_notify_message'] ?? '')), 300),
                'external_notify_endpoint' => $this->limitText(trim((string) ($item['external_notify_endpoint'] ?? '')), 240),
                'external_notify_attempt_count' => max(0, (int) ($item['external_notify_attempt_count'] ?? 0)),
                'external_notify_last_attempt_at' => trim((string) ($item['external_notify_last_attempt_at'] ?? '')),
                'external_notify_last_success_at' => trim((string) ($item['external_notify_last_success_at'] ?? '')),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [
            ['pending' => 0, 'processing' => 1, 'resolved' => 2, 'ignored' => 3][(string) ($a['status'] ?? 'pending')] ?? 9,
            (string) ($b['last_seen_at'] ?? ''),
            (int) ($b['id'] ?? 0),
        ] <=> [
            ['pending' => 0, 'processing' => 1, 'resolved' => 2, 'ignored' => 3][(string) ($b['status'] ?? 'pending')] ?? 9,
            (string) ($a['last_seen_at'] ?? ''),
            (int) ($a['id'] ?? 0),
        ]);

        return array_slice($normalized, 0, 5000);
    }

    private function normalizeOperationAlertNotificationConfig(array $config): array
    {
        $allowedChannels = ['webhook', 'wechat_work', 'email', 'sms', 'in_app'];
        $statuses = array_values(array_filter(array_map('strval', (array) ($config['send_statuses'] ?? ['pending', 'processing'])), static fn (string $status): bool => in_array($status, ['pending', 'processing', 'resolved', 'ignored'], true)));
        if (empty($statuses)) {
            $statuses = ['pending', 'processing'];
        }
        $priority = (string) ($config['min_priority'] ?? 'normal');
        $priority = in_array($priority, ['low', 'normal', 'high', 'urgent'], true) ? $priority : 'normal';
        $channel = (string) ($config['channel'] ?? 'webhook');
        $channels = array_values(array_unique(array_filter(array_map('strval', (array) ($config['channels'] ?? [])), static fn (string $item): bool => in_array($item, $allowedChannels, true))));
        if (empty($channels) && in_array($channel, $allowedChannels, true)) {
            $channels = [$channel];
        }
        if (empty($channels)) {
            $channels = ['webhook'];
        }
        $urgentChannels = array_values(array_unique(array_filter(array_map('strval', (array) ($config['urgent_channels'] ?? [])), static fn (string $item): bool => in_array($item, $allowedChannels, true))));
        $failureChannels = array_values(array_unique(array_filter(array_map('strval', (array) ($config['failure_escalation_channels'] ?? [])), static fn (string $item): bool => in_array($item, $allowedChannels, true))));

        return [
            'enabled' => !empty($config['enabled']),
            'channel' => $channels[0] ?? 'webhook',
            'channels' => $channels,
            'webhook_url' => trim((string) ($config['webhook_url'] ?? $config['endpoint'] ?? '')),
            'secret' => trim((string) ($config['secret'] ?? '')),
            'wechat_work_url' => trim((string) ($config['wechat_work_url'] ?? '')),
            'wechat_work_secret' => trim((string) ($config['wechat_work_secret'] ?? '')),
            'wechat_work_signing_enabled' => array_key_exists('wechat_work_signing_enabled', $config)
                ? !empty($config['wechat_work_signing_enabled'])
                : trim((string) ($config['wechat_work_secret'] ?? '')) !== '',
            'email' => $this->limitText(trim((string) ($config['email'] ?? '')), 120),
            'phone' => $this->limitText(trim((string) ($config['phone'] ?? '')), 30),
            'title_template' => $this->limitText(trim((string) ($config['title_template'] ?? '短剧投放预警：{{title}}')), 120),
            'body_template' => $this->limitText(trim((string) ($config['body_template'] ?? '{{message}}')), 500),
            'email_subject_template' => $this->limitText(trim((string) ($config['email_subject_template'] ?? $config['title_template'] ?? '短剧投放预警：{{title}}')), 120),
            'sms_template' => $this->limitText(trim((string) ($config['sms_template'] ?? '【{{priority}}】{{title}} {{message}}')), 200),
            'send_statuses' => $statuses,
            'min_priority' => $priority,
            'retry_failed' => array_key_exists('retry_failed', $config) ? !empty($config['retry_failed']) : true,
            'escalation_enabled' => !empty($config['escalation_enabled']),
            'urgent_channels' => $urgentChannels,
            'failure_escalation_enabled' => !empty($config['failure_escalation_enabled']),
            'failure_escalation_after_attempts' => max(1, min(10, (int) ($config['failure_escalation_after_attempts'] ?? 1))),
            'failure_escalation_channels' => $failureChannels,
            'updated_at' => trim((string) ($config['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
        ];
    }

    private function normalizeOperationAlertNotificationReceivers(array $items): array
    {
        $normalized = [];
        $seenIds = [];
        $allowedChannels = ['webhook', 'wechat_work', 'email', 'sms', 'in_app'];
        $alertTypes = ['callback_failed', 'low_conversion_material', 'high_refund_material', 'low_recovery_link', 'auto_paused_link', 'promotion_stop_failed', 'promotion_stop_auth_failed', 'promotion_stop_rate_limited'];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = max(1, (int) ($item['id'] ?? ($index + 1)));
            while (isset($seenIds[$id])) {
                $id++;
            }
            $seenIds[$id] = true;

            $scopeType = (string) ($item['scope_type'] ?? 'global');
            $statuses = array_values(array_filter(array_map('strval', (array) ($item['send_statuses'] ?? ['pending', 'processing'])), static fn (string $status): bool => in_array($status, ['pending', 'processing', 'resolved', 'ignored'], true)));
            if (empty($statuses)) {
                $statuses = ['pending', 'processing'];
            }
            $types = array_values(array_filter(array_map('strval', (array) ($item['alert_types'] ?? ['all'])), static fn (string $type): bool => $type === 'all' || in_array($type, $alertTypes, true)));
            if (empty($types) || in_array('all', $types, true)) {
                $types = ['all'];
            } else {
                $types = array_values(array_intersect($types, $alertTypes));
            }
            $priority = (string) ($item['min_priority'] ?? 'normal');
            $priority = in_array($priority, ['low', 'normal', 'high', 'urgent'], true) ? $priority : 'normal';
            $channels = array_values(array_unique(array_filter(array_map('strval', (array) ($item['channels'] ?? [])), static fn (string $item): bool => in_array($item, $allowedChannels, true))));
            $legacyChannel = (string) ($item['channel'] ?? '');
            if (empty($channels) && in_array($legacyChannel, $allowedChannels, true)) {
                $channels = [$legacyChannel];
            }
            if (empty($channels) && trim((string) ($item['webhook_url'] ?? $item['endpoint'] ?? '')) !== '') {
                $channels = ['webhook'];
            }
            if (empty($channels) && trim((string) ($item['wechat_work_url'] ?? '')) !== '') {
                $channels = ['wechat_work'];
            }
            if (empty($channels) && trim((string) ($item['email'] ?? '')) !== '') {
                $channels = ['email'];
            }
            if (empty($channels) && trim((string) ($item['phone'] ?? '')) !== '') {
                $channels = ['sms'];
            }
            if (empty($channels)) {
                $channels = ['webhook'];
            }

            $normalized[] = [
                'id' => $id,
                'name' => $this->limitText(trim((string) ($item['name'] ?? '预警接收人' . $id)), 80),
                'status' => (string) ($item['status'] ?? 'active') === 'paused' ? 'paused' : 'active',
                'scope_type' => in_array($scopeType, ['global', 'role', 'agent'], true) ? $scopeType : 'global',
                'scope_role' => in_array((string) ($item['scope_role'] ?? ''), ['business', 'leader', 'agent'], true) ? (string) $item['scope_role'] : '',
                'agent_id' => max(0, (int) ($item['agent_id'] ?? 0)),
                'channel' => $channels[0] ?? 'webhook',
                'channels' => $channels,
                'webhook_url' => trim((string) ($item['webhook_url'] ?? $item['endpoint'] ?? '')),
                'secret' => trim((string) ($item['secret'] ?? '')),
                'wechat_work_url' => trim((string) ($item['wechat_work_url'] ?? '')),
                'wechat_work_secret' => trim((string) ($item['wechat_work_secret'] ?? '')),
                'wechat_work_signing_enabled' => array_key_exists('wechat_work_signing_enabled', $item)
                    ? !empty($item['wechat_work_signing_enabled'])
                    : trim((string) ($item['wechat_work_secret'] ?? '')) !== '',
                'email' => $this->limitText(trim((string) ($item['email'] ?? '')), 120),
                'phone' => $this->limitText(trim((string) ($item['phone'] ?? '')), 30),
                'send_statuses' => $statuses,
                'alert_types' => $types,
                'min_priority' => $priority,
                'retry_failed' => array_key_exists('retry_failed', $item) ? !empty($item['retry_failed']) : true,
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 160),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(string) ($a['status'] ?? 'active'), (int) ($a['id'] ?? 0)] <=> [(string) ($b['status'] ?? 'active'), (int) ($b['id'] ?? 0)]);

        return array_slice($normalized, 0, 500);
    }

    private function normalizeCallbackAuthConfig(array $config, string $secret = ''): array
    {
        $mode = (string) ($config['mode'] ?? 'none');
        $mode = in_array($mode, ['none', 'hmac_header', 'bearer', 'query_sign', 'body_sign'], true) ? $mode : 'none';
        $algorithm = strtolower((string) ($config['algorithm'] ?? 'sha256'));
        $algorithm = in_array($algorithm, hash_hmac_algos(), true) ? $algorithm : 'sha256';
        $headerName = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($config['header_name'] ?? 'X-JX-Signature'))) ?: 'X-JX-Signature';
        $tokenHeaderName = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($config['token_header_name'] ?? 'Authorization'))) ?: 'Authorization';
        $queryKey = preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) ($config['query_key'] ?? 'sign'))) ?: 'sign';
        $bodyKey = preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) ($config['body_key'] ?? 'sign'))) ?: 'sign';
        $timestampKey = preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) ($config['timestamp_key'] ?? 'timestamp'))) ?: 'timestamp';
        $signSource = (string) ($config['sign_source'] ?? 'body');
        $signSource = in_array($signSource, ['body', 'query', 'body_with_timestamp'], true) ? $signSource : 'body';
        $authSecret = trim((string) ($config['secret'] ?? ''));
        if ($authSecret === '') {
            $authSecret = trim($secret);
        }

        return [
            'mode' => $mode,
            'secret' => $authSecret,
            'token' => trim((string) ($config['token'] ?? '')),
            'algorithm' => $algorithm,
            'header_name' => $headerName,
            'token_header_name' => $tokenHeaderName,
            'query_key' => $queryKey,
            'body_key' => $bodyKey,
            'timestamp_key' => $timestampKey,
            'include_timestamp' => !empty($config['include_timestamp']),
            'sign_source' => $signSource,
        ];
    }

    private function normalizeCallbackRetryPolicy(array $policy): array
    {
        $maxAttempts = max(1, min(20, (int) ($policy['max_attempts'] ?? 5)));
        $baseInterval = max(1, min(1440, (int) ($policy['base_interval_minutes'] ?? 5)));
        $maxInterval = max($baseInterval, min(1440, (int) ($policy['max_interval_minutes'] ?? 120)));

        return [
            'max_attempts' => $maxAttempts,
            'base_interval_minutes' => $baseInterval,
            'max_interval_minutes' => $maxInterval,
            'backoff' => array_key_exists('backoff', $policy) ? !empty($policy['backoff']) : true,
        ];
    }

    private function normalizeOperationAlertNotificationLogs(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $status = (string) ($item['status'] ?? 'pending');
            $channel = (string) ($item['channel'] ?? 'webhook');
            $type = (string) ($item['alert_type'] ?? $item['type'] ?? 'operation_alert');
            $alertTypes = ['callback_failed', 'low_conversion_material', 'high_refund_material', 'low_recovery_link', 'auto_paused_link', 'promotion_stop_failed', 'promotion_stop_auth_failed', 'promotion_stop_rate_limited', 'operation_alert'];
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'alert_id' => max(0, (int) ($item['alert_id'] ?? 0)),
                'receiver_id' => max(0, (int) ($item['receiver_id'] ?? 0)),
                'receiver_name' => $this->limitText(trim((string) ($item['receiver_name'] ?? '')), 80),
                'fingerprint' => preg_replace('/[^a-zA-Z0-9:_-]+/', '', (string) ($item['fingerprint'] ?? '')),
                'alert_type' => in_array($type, $alertTypes, true) ? $type : 'operation_alert',
                'status' => in_array($status, ['pending', 'success', 'failed', 'skipped'], true) ? $status : 'pending',
                'channel' => in_array($channel, ['webhook', 'wechat_work', 'email', 'sms', 'in_app'], true) ? $channel : 'webhook',
                'endpoint' => $this->limitText(trim((string) ($item['endpoint'] ?? '')), 240),
                'receiver_contact' => $this->limitText(trim((string) ($item['receiver_contact'] ?? '')), 160),
                'provider_request_id' => $this->limitText(trim((string) ($item['provider_request_id'] ?? $item['request_id'] ?? '')), 120),
                'receipt_status' => $this->limitText(trim((string) ($item['receipt_status'] ?? '')), 40),
                'receipt_message' => $this->limitText(trim((string) ($item['receipt_message'] ?? '')), 200),
                'receipt_received_at' => trim((string) ($item['receipt_received_at'] ?? '')),
                'is_escalation' => !empty($item['is_escalation']),
                'escalation_reason' => $this->limitText(trim((string) ($item['escalation_reason'] ?? '')), 200),
                'priority' => in_array((string) ($item['priority'] ?? ''), ['low', 'normal', 'high', 'urgent'], true) ? (string) $item['priority'] : 'normal',
                'promotion_link_id' => max(0, (int) ($item['promotion_link_id'] ?? 0)),
                'promotion_code' => preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($item['promotion_code'] ?? $item['code'] ?? '')),
                'order_no' => $this->limitText(trim((string) ($item['order_no'] ?? '')), 80),
                'agent_id' => max(0, (int) ($item['agent_id'] ?? 0)),
                'traffic_platform' => $this->limitText(trim((string) ($item['traffic_platform'] ?? $item['platform'] ?? '')), 40),
                'channel_id' => $this->limitText(trim((string) ($item['channel_id'] ?? $item['traffic_channel_id'] ?? '')), 60),
                'media_app_id' => $this->limitText(trim((string) ($item['media_app_id'] ?? $item['app_id'] ?? '')), 60),
                'ad_id' => $this->limitText(trim((string) ($item['ad_id'] ?? $item['advert_id'] ?? '')), 80),
                'creative_id' => $this->limitText(trim((string) ($item['creative_id'] ?? $item['creativity_id'] ?? '')), 80),
                'material_id' => $this->limitText(trim((string) ($item['material_id'] ?? $item['material'] ?? '')), 80),
                'attempt_count' => max(0, (int) ($item['attempt_count'] ?? 0)),
                'message' => $this->limitText(trim((string) ($item['message'] ?? '')), 300),
                'request_payload' => is_array($item['request_payload'] ?? null) ? $item['request_payload'] : [],
                'response_payload' => is_array($item['response_payload'] ?? null) ? $item['response_payload'] : [],
                'last_attempt_at' => trim((string) ($item['last_attempt_at'] ?? '')),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        return array_slice($normalized, 0, 5000);
    }

    private function normalizePromotionStopTasks(array $items): array
    {
        $normalized = [];
        $seenFingerprints = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $status = (string) ($item['status'] ?? 'pending');
            $fingerprint = preg_replace('/[^a-zA-Z0-9:_-]+/', '', (string) ($item['fingerprint'] ?? ''));
            if ($fingerprint === '') {
                $fingerprint = 'stop:' . md5(json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: (string) $index);
            }
            if (isset($seenFingerprints[$fingerprint])) {
                continue;
            }
            $seenFingerprints[$fingerprint] = true;

            $errorCategory = (string) ($item['error_category'] ?? '');
            $errorCategory = in_array($errorCategory, ['auth_failed', 'rate_limited', 'platform_failed', 'retryable_failed', 'config_missing', 'adapter_missing', 'unknown'], true) ? $errorCategory : '';
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'task_no' => $this->limitText(trim((string) ($item['task_no'] ?? '')), 40),
                'fingerprint' => $fingerprint,
                'source_type' => in_array((string) ($item['source_type'] ?? ''), ['auto_pause', 'manual', 'operation_alert'], true) ? (string) $item['source_type'] : 'auto_pause',
                'alert_id' => max(0, (int) ($item['alert_id'] ?? 0)),
                'promotion_link_id' => max(0, (int) ($item['promotion_link_id'] ?? 0)),
                'promotion_code' => preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($item['promotion_code'] ?? $item['code'] ?? '')),
                'agent_id' => max(0, (int) ($item['agent_id'] ?? 0)),
                'traffic_platform' => $this->limitText(trim((string) ($item['traffic_platform'] ?? $item['platform'] ?? '')), 40),
                'channel_id' => $this->limitText(trim((string) ($item['channel_id'] ?? $item['channel'] ?? '')), 60),
                'media_app_id' => $this->limitText(trim((string) ($item['media_app_id'] ?? $item['app_id'] ?? '')), 60),
                'ad_id' => $this->limitText(trim((string) ($item['ad_id'] ?? $item['advert_id'] ?? '')), 80),
                'creative_id' => $this->limitText(trim((string) ($item['creative_id'] ?? $item['creativity_id'] ?? '')), 80),
                'material_id' => $this->limitText(trim((string) ($item['material_id'] ?? $item['material'] ?? '')), 80),
                'provider' => $this->limitText(trim((string) ($item['provider'] ?? $item['traffic_platform'] ?? '')), 40),
                'adapter_id' => max(0, (int) ($item['adapter_id'] ?? 0)),
                'adapter_key' => preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($item['adapter_key'] ?? '')),
                'adapter_name' => $this->limitText(trim((string) ($item['adapter_name'] ?? '')), 80),
                'adapter_account_key' => preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($item['adapter_account_key'] ?? '')),
                'adapter_account_name' => $this->limitText(trim((string) ($item['adapter_account_name'] ?? '')), 80),
                'platform_request_id' => $this->limitText(trim((string) ($item['platform_request_id'] ?? '')), 120),
                'platform_status' => $this->limitText(trim((string) ($item['platform_status'] ?? '')), 80),
                'platform_code' => $this->limitText(trim((string) ($item['platform_code'] ?? '')), 80),
                'platform_message' => $this->limitText(trim((string) ($item['platform_message'] ?? '')), 200),
                'stop_action' => in_array((string) ($item['stop_action'] ?? ''), ['pause_ad', 'pause_material', 'pause_campaign', 'manual'], true) ? (string) $item['stop_action'] : 'pause_ad',
                'reason' => $this->limitText(trim((string) ($item['reason'] ?? '')), 300),
                'status' => in_array($status, ['pending', 'processing', 'success', 'failed', 'skipped', 'manual_done', 'cancelled'], true) ? $status : 'pending',
                'endpoint' => $this->limitText(trim((string) ($item['endpoint'] ?? '')), 240),
                'query_endpoint' => $this->limitText(trim((string) ($item['query_endpoint'] ?? '')), 240),
                'request_payload' => is_array($item['request_payload'] ?? null) ? $item['request_payload'] : [],
                'response_payload' => is_array($item['response_payload'] ?? null) ? $item['response_payload'] : [],
                'query_request_payload' => is_array($item['query_request_payload'] ?? null) ? $item['query_request_payload'] : [],
                'query_response_payload' => is_array($item['query_response_payload'] ?? null) ? $item['query_response_payload'] : [],
                'attempt_count' => max(0, (int) ($item['attempt_count'] ?? 0)),
                'query_count' => max(0, (int) ($item['query_count'] ?? 0)),
                'last_attempt_at' => trim((string) ($item['last_attempt_at'] ?? '')),
                'last_query_at' => trim((string) ($item['last_query_at'] ?? '')),
                'error_category' => $errorCategory,
                'next_retry_at' => trim((string) ($item['next_retry_at'] ?? '')),
                'rate_limited_until' => trim((string) ($item['rate_limited_until'] ?? '')),
                'retry_blocked_reason' => $this->limitText(trim((string) ($item['retry_blocked_reason'] ?? '')), 300),
                'last_error_at' => trim((string) ($item['last_error_at'] ?? '')),
                'completed_at' => trim((string) ($item['completed_at'] ?? '')),
                'handled_by_admin_id' => max(0, (int) ($item['handled_by_admin_id'] ?? 0)),
                'handled_by_admin_name' => $this->limitText(trim((string) ($item['handled_by_admin_name'] ?? '')), 60),
                'message' => $this->limitText(trim((string) ($item['message'] ?? '')), 300),
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 300),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [
            ['pending' => 0, 'failed' => 1, 'processing' => 2, 'success' => 3, 'manual_done' => 4, 'skipped' => 5, 'cancelled' => 6][(string) ($a['status'] ?? 'pending')] ?? 9,
            (string) ($b['created_at'] ?? ''),
            (int) ($b['id'] ?? 0),
        ] <=> [
            ['pending' => 0, 'failed' => 1, 'processing' => 2, 'success' => 3, 'manual_done' => 4, 'skipped' => 5, 'cancelled' => 6][(string) ($b['status'] ?? 'pending')] ?? 9,
            (string) ($a['created_at'] ?? ''),
            (int) ($a['id'] ?? 0),
        ]);

        return array_slice($normalized, 0, 5000);
    }

    private function normalizeAnalyticsReviewTasks(array $items): array
    {
        $normalized = [];
        $seenFingerprints = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $fingerprint = preg_replace('/[^a-zA-Z0-9:_-]+/', '', (string) ($item['fingerprint'] ?? ''));
            if ($fingerprint === '') {
                $fingerprint = 'insight:' . md5(json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: (string) $index);
            }
            if (isset($seenFingerprints[$fingerprint])) {
                continue;
            }
            $seenFingerprints[$fingerprint] = true;

            $status = (string) ($item['status'] ?? 'pending');
            $level = (string) ($item['level'] ?? $item['priority'] ?? 'medium');
            $actionType = (string) ($item['action_type'] ?? 'observe');
            $metrics = is_array($item['metrics'] ?? null) ? (array) $item['metrics'] : [];
            $logs = [];
            foreach (array_values((array) ($item['action_logs'] ?? [])) as $logIndex => $log) {
                if (!is_array($log)) {
                    continue;
                }
                $logs[] = [
                    'id' => max(1, (int) ($log['id'] ?? ($logIndex + 1))),
                    'action' => $this->limitText(trim((string) ($log['action'] ?? 'update')), 40),
                    'message' => $this->limitText(trim((string) ($log['message'] ?? '')), 220),
                    'admin_id' => max(0, (int) ($log['admin_id'] ?? 0)),
                    'admin_name' => $this->limitText(trim((string) ($log['admin_name'] ?? '')), 60),
                    'created_at' => trim((string) ($log['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                ];
            }

            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'task_no' => $this->limitText(trim((string) ($item['task_no'] ?? '')), 40),
                'fingerprint' => $fingerprint,
                'source_type' => in_array((string) ($item['source_type'] ?? ''), ['analytics_insight', 'operation_alert', 'manual'], true) ? (string) $item['source_type'] : 'analytics_insight',
                'level' => in_array($level, ['high', 'medium', 'low', 'good'], true) ? $level : 'medium',
                'action_type' => in_array($actionType, ['observe', 'review_content', 'replace_material', 'adjust_budget', 'pause_promotion', 'amplify', 'custom'], true) ? $actionType : 'observe',
                'title' => $this->limitText(trim((string) ($item['title'] ?? '复盘任务')), 120),
                'message' => $this->limitText(trim((string) ($item['message'] ?? '')), 300),
                'suggested_action' => $this->limitText(trim((string) ($item['suggested_action'] ?? $item['action'] ?? '')), 240),
                'status' => in_array($status, ['pending', 'processing', 'done', 'ignored'], true) ? $status : 'pending',
                'promotion_link_id' => max(0, (int) ($item['promotion_link_id'] ?? 0)),
                'promotion_code' => preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($item['promotion_code'] ?? $item['code'] ?? '')),
                'agent_id' => max(0, (int) ($item['agent_id'] ?? 0)),
                'traffic_platform' => $this->limitText(trim((string) ($item['traffic_platform'] ?? $item['platform'] ?? '')), 40),
                'channel_id' => $this->limitText(trim((string) ($item['channel_id'] ?? $item['channel'] ?? '')), 60),
                'media_app_id' => $this->limitText(trim((string) ($item['media_app_id'] ?? $item['app_id'] ?? '')), 60),
                'ad_id' => $this->limitText(trim((string) ($item['ad_id'] ?? $item['advert_id'] ?? '')), 80),
                'creative_id' => $this->limitText(trim((string) ($item['creative_id'] ?? $item['creativity_id'] ?? '')), 80),
                'material_id' => $this->limitText(trim((string) ($item['material_id'] ?? $item['material'] ?? '')), 80),
                'content_type' => in_array((string) ($item['content_type'] ?? ''), ['drama', 'novel'], true) ? (string) $item['content_type'] : '',
                'content_id' => max(0, (int) ($item['content_id'] ?? 0)),
                'content_title' => $this->limitText(trim((string) ($item['content_title'] ?? '')), 120),
                'metrics' => $metrics,
                'created_by_admin_id' => max(0, (int) ($item['created_by_admin_id'] ?? 0)),
                'created_by_admin_name' => $this->limitText(trim((string) ($item['created_by_admin_name'] ?? '')), 60),
                'assigned_to_admin_id' => max(0, (int) ($item['assigned_to_admin_id'] ?? 0)),
                'assigned_to_admin_name' => $this->limitText(trim((string) ($item['assigned_to_admin_name'] ?? '')), 60),
                'assigned_at' => trim((string) ($item['assigned_at'] ?? '')),
                'handled_by_admin_id' => max(0, (int) ($item['handled_by_admin_id'] ?? 0)),
                'handled_by_admin_name' => $this->limitText(trim((string) ($item['handled_by_admin_name'] ?? '')), 60),
                'due_at' => trim((string) ($item['due_at'] ?? '')),
                'last_reminded_at' => trim((string) ($item['last_reminded_at'] ?? '')),
                'last_reminded_by_admin_id' => max(0, (int) ($item['last_reminded_by_admin_id'] ?? 0)),
                'last_reminded_by_admin_name' => $this->limitText(trim((string) ($item['last_reminded_by_admin_name'] ?? '')), 60),
                'reminder_count' => max(0, (int) ($item['reminder_count'] ?? 0)),
                'material_review_status' => in_array((string) ($item['material_review_status'] ?? 'none'), ['none', 'pending', 'approved', 'rejected', 'applied'], true) ? (string) ($item['material_review_status'] ?? 'none') : 'none',
                'original_ad_id' => $this->limitText(trim((string) ($item['original_ad_id'] ?? '')), 80),
                'original_creative_id' => $this->limitText(trim((string) ($item['original_creative_id'] ?? '')), 80),
                'original_material_id' => $this->limitText(trim((string) ($item['original_material_id'] ?? '')), 80),
                'proposed_ad_id' => $this->limitText(trim((string) ($item['proposed_ad_id'] ?? '')), 80),
                'proposed_creative_id' => $this->limitText(trim((string) ($item['proposed_creative_id'] ?? '')), 80),
                'proposed_material_id' => $this->limitText(trim((string) ($item['proposed_material_id'] ?? '')), 80),
                'proposed_note' => $this->limitText(trim((string) ($item['proposed_note'] ?? '')), 300),
                'material_submitted_by_admin_id' => max(0, (int) ($item['material_submitted_by_admin_id'] ?? 0)),
                'material_submitted_by_admin_name' => $this->limitText(trim((string) ($item['material_submitted_by_admin_name'] ?? '')), 60),
                'material_submitted_at' => trim((string) ($item['material_submitted_at'] ?? '')),
                'material_reviewed_by_admin_id' => max(0, (int) ($item['material_reviewed_by_admin_id'] ?? 0)),
                'material_reviewed_by_admin_name' => $this->limitText(trim((string) ($item['material_reviewed_by_admin_name'] ?? '')), 60),
                'material_reviewed_at' => trim((string) ($item['material_reviewed_at'] ?? '')),
                'material_review_note' => $this->limitText(trim((string) ($item['material_review_note'] ?? '')), 300),
                'material_applied_at' => trim((string) ($item['material_applied_at'] ?? '')),
                'effect_baseline_metrics' => is_array($item['effect_baseline_metrics'] ?? null) ? (array) $item['effect_baseline_metrics'] : [],
                'effect_latest_metrics' => is_array($item['effect_latest_metrics'] ?? null) ? (array) $item['effect_latest_metrics'] : [],
                'effect_delta_metrics' => is_array($item['effect_delta_metrics'] ?? null) ? (array) $item['effect_delta_metrics'] : [],
                'effect_updated_at' => trim((string) ($item['effect_updated_at'] ?? '')),
                'completed_at' => trim((string) ($item['completed_at'] ?? '')),
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 300),
                'action_logs' => array_slice($logs, -20),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [
            ['pending' => 0, 'processing' => 1, 'done' => 2, 'ignored' => 3][(string) ($a['status'] ?? 'pending')] ?? 9,
            (string) ($b['updated_at'] ?? $b['created_at'] ?? ''),
            (int) ($b['id'] ?? 0),
        ] <=> [
            ['pending' => 0, 'processing' => 1, 'done' => 2, 'ignored' => 3][(string) ($b['status'] ?? 'pending')] ?? 9,
            (string) ($a['updated_at'] ?? $a['created_at'] ?? ''),
            (int) ($a['id'] ?? 0),
        ]);

        return array_slice($normalized, 0, 5000);
    }

    private function normalizePromotionStopAdapterConfigs(array $items): array
    {
        $normalized = [];
        $seenIds = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = max(1, (int) ($item['id'] ?? ($index + 1)));
            while (isset($seenIds[$id])) {
                $id++;
            }
            $seenIds[$id] = true;
            $adapterKey = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['adapter_key'] ?? $item['key'] ?? '')));
            if ($adapterKey === '') {
                $adapterKey = 'stop_adapter_' . $id;
            }
            $aliases = $item['provider_aliases'] ?? $item['aliases'] ?? [];
            if (!is_array($aliases)) {
                $aliases = preg_split('/[\\r\\n,，|]+/u', (string) $aliases) ?: [];
            }
            $aliases = array_values(array_unique(array_filter(array_map(static fn (mixed $alias): string => trim((string) $alias), $aliases), static fn (string $alias): bool => $alias !== '')));
            $fieldMapping = is_array($item['field_mapping'] ?? null) ? (array) $item['field_mapping'] : [];
            $normalizedMapping = [];
            foreach ($fieldMapping as $target => $source) {
                $targetKey = preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) $target));
                $sourceKey = preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) $source));
                if ($targetKey !== '' && $sourceKey !== '') {
                    $normalizedMapping[$targetKey] = $sourceKey;
                }
            }
            $queryFieldMapping = is_array($item['query_field_mapping'] ?? null) ? (array) $item['query_field_mapping'] : [];
            $normalizedQueryMapping = [];
            foreach ($queryFieldMapping as $target => $source) {
                $targetKey = preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) $target));
                $sourceKey = preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) $source));
                if ($targetKey !== '' && $sourceKey !== '') {
                    $normalizedQueryMapping[$targetKey] = $sourceKey;
                }
            }

            $normalized[] = [
                'id' => $id,
                'adapter_key' => $adapterKey,
                'name' => $this->limitText(trim((string) ($item['name'] ?? '停投适配器' . $id)), 80),
                'status' => (string) ($item['status'] ?? 'paused') === 'active' ? 'active' : 'paused',
                'provider_aliases' => array_slice($aliases, 0, 20),
                'endpoint' => trim((string) ($item['endpoint'] ?? '')),
                'query_endpoint' => trim((string) ($item['query_endpoint'] ?? '')),
                'secret' => trim((string) ($item['secret'] ?? '')),
                'auth_config' => $this->normalizeCallbackAuthConfig((array) ($item['auth_config'] ?? []), (string) ($item['secret'] ?? '')),
                'field_mapping' => array_slice($normalizedMapping, 0, 60, true),
                'query_field_mapping' => array_slice($normalizedQueryMapping, 0, 60, true),
                'account_profiles' => $this->normalizePromotionStopAdapterAccountProfiles((array) ($item['account_profiles'] ?? [])),
                'response_config' => $this->normalizePromotionStopResponseConfig((array) ($item['response_config'] ?? [])),
                'default_stop_action' => in_array((string) ($item['default_stop_action'] ?? ''), ['pause_ad', 'pause_material', 'pause_campaign', 'manual'], true) ? (string) $item['default_stop_action'] : 'pause_ad',
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 200),
                'last_test_status' => in_array((string) ($item['last_test_status'] ?? ''), ['success', 'failed', 'skipped'], true) ? (string) $item['last_test_status'] : '',
                'last_test_message' => $this->limitText(trim((string) ($item['last_test_message'] ?? '')), 200),
                'last_test_at' => trim((string) ($item['last_test_at'] ?? '')),
                'last_test_request' => is_array($item['last_test_request'] ?? null) ? (array) $item['last_test_request'] : [],
                'last_test_response' => is_array($item['last_test_response'] ?? null) ? (array) $item['last_test_response'] : [],
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(string) ($a['status'] ?? 'paused'), (int) ($a['id'] ?? 0)] <=> [(string) ($b['status'] ?? 'paused'), (int) ($b['id'] ?? 0)]);

        return array_slice($normalized, 0, 200);
    }

    private function normalizePromotionStopAdapterAccountProfiles(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $accountKey = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['account_key'] ?? $item['key'] ?? '')));
            if ($accountKey === '') {
                $accountKey = 'account_' . ($index + 1);
            }
            $secret = trim((string) ($item['secret'] ?? ''));
            $normalized[] = [
                'account_key' => $accountKey,
                'name' => $this->limitText(trim((string) ($item['name'] ?? $item['account_name'] ?? ('授权账号' . ($index + 1)))), 80),
                'status' => (string) ($item['status'] ?? 'active') === 'paused' ? 'paused' : 'active',
                'priority' => max(0, min(9999, (int) ($item['priority'] ?? 100))),
                'match_agent_ids' => array_slice(array_values(array_unique(array_filter(array_map('intval', (array) ($item['match_agent_ids'] ?? $item['agent_ids'] ?? [])), static fn (int $id): bool => $id > 0))), 0, 100),
                'match_media_app_ids' => $this->normalizePromotionStopStringList($item['match_media_app_ids'] ?? $item['media_app_ids'] ?? []),
                'match_channel_ids' => $this->normalizePromotionStopStringList($item['match_channel_ids'] ?? $item['channel_ids'] ?? []),
                'match_ad_ids' => $this->normalizePromotionStopStringList($item['match_ad_ids'] ?? $item['ad_ids'] ?? []),
                'match_provider_aliases' => $this->normalizePromotionStopStringList($item['match_provider_aliases'] ?? $item['provider_aliases'] ?? []),
                'endpoint' => trim((string) ($item['endpoint'] ?? '')),
                'query_endpoint' => trim((string) ($item['query_endpoint'] ?? '')),
                'secret' => $secret,
                'auth_config' => $this->normalizeCallbackAuthConfig((array) ($item['auth_config'] ?? []), $secret),
                'token_expires_at' => trim((string) ($item['token_expires_at'] ?? '')),
                'refresh_endpoint' => trim((string) ($item['refresh_endpoint'] ?? '')),
                'refresh_token' => trim((string) ($item['refresh_token'] ?? '')),
                'refresh_secret' => trim((string) ($item['refresh_secret'] ?? '')),
                'refresh_auth_config' => $this->normalizeCallbackAuthConfig((array) ($item['refresh_auth_config'] ?? []), (string) ($item['refresh_secret'] ?? '')),
                'refresh_field_mapping' => $this->normalizePromotionStopFieldMapping((array) ($item['refresh_field_mapping'] ?? [])),
                'refresh_before_minutes' => max(0, min(1440, (int) ($item['refresh_before_minutes'] ?? 30))),
                'auto_refresh' => array_key_exists('auto_refresh', $item) ? !empty($item['auto_refresh']) : true,
                'refresh_access_token_path' => $this->limitText(preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) ($item['refresh_access_token_path'] ?? 'access_token'))) ?: 'access_token', 80),
                'refresh_token_path' => $this->limitText(preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) ($item['refresh_token_path'] ?? 'refresh_token'))) ?: 'refresh_token', 80),
                'refresh_expires_in_path' => $this->limitText(preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) ($item['refresh_expires_in_path'] ?? 'expires_in'))) ?: 'expires_in', 80),
                'refresh_expires_at_path' => $this->limitText(preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) ($item['refresh_expires_at_path'] ?? 'expires_at'))) ?: 'expires_at', 80),
                'last_refresh_status' => in_array((string) ($item['last_refresh_status'] ?? ''), ['success', 'failed', 'skipped'], true) ? (string) $item['last_refresh_status'] : '',
                'last_refresh_message' => $this->limitText(trim((string) ($item['last_refresh_message'] ?? '')), 200),
                'last_refresh_at' => trim((string) ($item['last_refresh_at'] ?? '')),
                'last_refresh_response' => is_array($item['last_refresh_response'] ?? null) ? (array) $item['last_refresh_response'] : [],
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 160),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(int) ($b['priority'] ?? 0), (string) ($a['account_key'] ?? '')] <=> [(int) ($a['priority'] ?? 0), (string) ($b['account_key'] ?? '')]);

        return array_slice($normalized, 0, 50);
    }

    private function normalizePromotionStopFieldMapping(array $mapping): array
    {
        $normalized = [];
        foreach ($mapping as $target => $source) {
            $targetKey = preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) $target));
            $sourceKey = preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) $source));
            if ($targetKey !== '' && $sourceKey !== '') {
                $normalized[$targetKey] = $sourceKey;
            }
        }

        return array_slice($normalized, 0, 60, true);
    }

    private function normalizePromotionStopStringList(mixed $items): array
    {
        if (!is_array($items)) {
            $items = preg_split('/[\\r\\n,，|]+/u', (string) $items) ?: [];
        }

        return array_slice(array_values(array_unique(array_filter(array_map(static fn (mixed $item): string => trim((string) $item), $items), static fn (string $item): bool => $item !== ''))), 0, 100);
    }

    private function normalizePromotionStopResponseConfig(array $config): array
    {
        $successValues = $config['success_values'] ?? ['0', 'true', '1', 'success', 'ok'];
        if (!is_array($successValues)) {
            $successValues = preg_split('/[\\r\\n,，|]+/u', (string) $successValues) ?: [];
        }
        $successValues = array_values(array_unique(array_filter(array_map(static fn (mixed $item): string => trim((string) $item), $successValues), static fn (string $item): bool => $item !== '')));
        $processingValues = $config['processing_values'] ?? ['processing', 'pending', 'running', 'accepted', 'queued', '处理中', '待处理'];
        if (!is_array($processingValues)) {
            $processingValues = preg_split('/[\\r\\n,，|]+/u', (string) $processingValues) ?: [];
        }
        $processingValues = array_values(array_unique(array_filter(array_map(static fn (mixed $item): string => trim((string) $item), $processingValues), static fn (string $item): bool => $item !== '')));
        $failedValues = $config['failed_values'] ?? ['failed', 'fail', 'error', 'rejected', 'cancelled', '失败', '拒绝'];
        if (!is_array($failedValues)) {
            $failedValues = preg_split('/[\\r\\n,，|]+/u', (string) $failedValues) ?: [];
        }
        $failedValues = array_values(array_unique(array_filter(array_map(static fn (mixed $item): string => trim((string) $item), $failedValues), static fn (string $item): bool => $item !== '')));

        return [
            'success_path' => $this->limitText(preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) ($config['success_path'] ?? ''))) ?: '', 80),
            'success_values' => array_slice($successValues, 0, 20),
            'processing_values' => array_slice($processingValues, 0, 20),
            'failed_values' => array_slice($failedValues, 0, 20),
            'request_id_path' => $this->limitText(preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) ($config['request_id_path'] ?? 'request_id'))) ?: 'request_id', 80),
            'status_path' => $this->limitText(preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) ($config['status_path'] ?? 'status'))) ?: 'status', 80),
            'code_path' => $this->limitText(preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) ($config['code_path'] ?? 'code'))) ?: 'code', 80),
            'message_path' => $this->limitText(preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) ($config['message_path'] ?? 'message'))) ?: 'message', 80),
        ];
    }

    private function normalizeLandingPages(array $items): array
    {
        $normalized = [];
        $seenSlugs = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['slug'] ?? '')));
            $slug = $slug !== '' ? $this->limitText($slug, 80) : ('lp' . base_convert((string) ((int) ($item['id'] ?? ($index + 1)) + 1000), 10, 36));
            $baseSlug = $slug;
            $suffix = 2;
            while (isset($seenSlugs[$slug])) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }
            $seenSlugs[$slug] = true;

            $contentType = (string) ($item['content_type'] ?? 'drama');
            $status = (string) ($item['status'] ?? 'active');
            $template = (string) ($item['template'] ?? 'drama');
            $ctaMode = (string) ($item['cta_mode'] ?? 'content');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'slug' => $slug,
                'name' => $this->limitText(trim((string) ($item['name'] ?? '')) ?: '推广落地页' . ($index + 1), 60),
                'title' => $this->limitText(trim((string) ($item['title'] ?? '')), 80),
                'subtitle' => $this->limitText(trim((string) ($item['subtitle'] ?? '')), 140),
                'template' => in_array($template, ['drama', 'novel', 'mixed'], true) ? $template : 'drama',
                'status' => in_array($status, ['active', 'review', 'paused'], true) ? $status : 'active',
                'cta_text' => $this->limitText(trim((string) ($item['cta_text'] ?? '')) ?: '立即观看', 24),
                'cta_mode' => in_array($ctaMode, ['content', 'promotion', 'custom'], true) ? $ctaMode : 'content',
                'cta_url' => trim((string) ($item['cta_url'] ?? '')),
                'promotion_link_id' => max(0, (int) ($item['promotion_link_id'] ?? 0)),
                'promotion_code' => preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($item['promotion_code'] ?? $item['code'] ?? '')),
                'agent_id' => max(0, (int) ($item['agent_id'] ?? 0)),
                'content_type' => in_array($contentType, ['drama', 'novel'], true) ? $contentType : 'drama',
                'drama_id' => max(0, (int) ($item['drama_id'] ?? 0)),
                'episode_id' => max(0, (int) ($item['episode_id'] ?? 0)),
                'novel_id' => max(0, (int) ($item['novel_id'] ?? 0)),
                'chapter_id' => max(0, (int) ($item['chapter_id'] ?? 0)),
                'app_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? $item['media_app_id'] ?? ''))) ?: '', 60),
                'cover' => trim((string) ($item['cover'] ?? '')),
                'badge' => $this->limitText(trim((string) ($item['badge'] ?? '')), 30),
                'selling_points' => $this->normalizeLandingPagePoints((array) ($item['selling_points'] ?? [])),
                'traffic_platform' => $this->limitText(trim((string) ($item['traffic_platform'] ?? $item['platform'] ?? '')), 40),
                'channel_id' => $this->limitText(trim((string) ($item['channel_id'] ?? $item['channel'] ?? '')), 60),
                'media_app_id' => $this->limitText(trim((string) ($item['media_app_id'] ?? $item['app_id'] ?? '')), 60),
                'ad_id' => $this->limitText(trim((string) ($item['ad_id'] ?? $item['advert_id'] ?? '')), 80),
                'creative_id' => $this->limitText(trim((string) ($item['creative_id'] ?? $item['creativity_id'] ?? '')), 80),
                'material_id' => $this->limitText(trim((string) ($item['material_id'] ?? $item['material'] ?? '')), 80),
                'views' => max(0, (int) ($item['views'] ?? 0)),
                'clicks' => max(0, (int) ($item['clicks'] ?? 0)),
                'sort' => (int) ($item['sort'] ?? ($index + 1)),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (int) ($a['id'] ?? 0)] <=> [(int) ($b['sort'] ?? 0), (int) ($b['id'] ?? 0)]);

        return array_slice($normalized, 0, 1000);
    }

    private function normalizeLandingPageEvents(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $event = (string) ($item['event'] ?? 'view');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'landing_page_id' => max(0, (int) ($item['landing_page_id'] ?? 0)),
                'slug' => preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($item['slug'] ?? '')),
                'event' => in_array($event, ['view', 'click'], true) ? $event : 'view',
                'user_id' => max(0, (int) ($item['user_id'] ?? 0)),
                'promotion_link_id' => max(0, (int) ($item['promotion_link_id'] ?? 0)),
                'promotion_code' => preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($item['promotion_code'] ?? $item['code'] ?? '')),
                'agent_id' => max(0, (int) ($item['agent_id'] ?? 0)),
                'path' => trim((string) ($item['path'] ?? '')),
                'target_url' => trim((string) ($item['target_url'] ?? '')),
                'traffic_platform' => $this->limitText(trim((string) ($item['traffic_platform'] ?? $item['platform'] ?? '')), 40),
                'channel_id' => $this->limitText(trim((string) ($item['channel_id'] ?? $item['channel'] ?? '')), 60),
                'media_app_id' => $this->limitText(trim((string) ($item['media_app_id'] ?? $item['app_id'] ?? '')), 60),
                'ad_id' => $this->limitText(trim((string) ($item['ad_id'] ?? $item['advert_id'] ?? '')), 80),
                'creative_id' => $this->limitText(trim((string) ($item['creative_id'] ?? $item['creativity_id'] ?? '')), 80),
                'material_id' => $this->limitText(trim((string) ($item['material_id'] ?? $item['material'] ?? '')), 80),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        return array_slice($normalized, 0, 10000);
    }

    private function normalizeLandingPagePoints(array $points): array
    {
        $normalized = [];
        foreach ($points as $point) {
            $text = $this->limitText(trim((string) $point), 40);
            if ($text === '' || in_array($text, $normalized, true)) {
                continue;
            }
            $normalized[] = $text;
        }

        return array_slice($normalized, 0, 6);
    }

    private function normalizeAdPlatformConfigs(array $items): array
    {
        $normalized = [];
        $seen = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $provider = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['provider'] ?? ''))) ?: 'custom';
            $provider = $this->limitText($provider, 40);
            $appKey = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? ''))) ?: 'default';
            $appKey = $this->limitText($appKey, 60);
            $key = $appKey . ':' . $provider;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $status = (string) ($item['status'] ?? 'active');
            $initParams = is_array($item['init_params'] ?? null) ? $item['init_params'] : [];
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'app_key' => $appKey,
                'provider' => $provider,
                'provider_name' => $this->limitText(trim((string) ($item['provider_name'] ?? $this->adProviderDefaultName($provider))), 60),
                'status' => in_array($status, ['active', 'paused', 'test'], true) ? $status : 'active',
                'platform_app_id' => $this->limitText(trim((string) ($item['platform_app_id'] ?? $item['app_id'] ?? '')), 100),
                'sdk_key' => $this->limitText(trim((string) ($item['sdk_key'] ?? $item['app_key_secret'] ?? '')), 160),
                'account_id' => $this->limitText(trim((string) ($item['account_id'] ?? '')), 100),
                'media_id' => $this->limitText(trim((string) ($item['media_id'] ?? '')), 100),
                'currency' => $this->limitText(trim((string) ($item['currency'] ?? 'CNY')) ?: 'CNY', 12),
                'default_ecpm' => round(max(0, (float) ($item['default_ecpm'] ?? $item['estimate_ecpm'] ?? 0)), 2),
                'revenue_share_rate' => min(100, max(0, round((float) ($item['revenue_share_rate'] ?? 100), 2))),
                'test_mode' => !empty($item['test_mode']),
                'init_params' => $initParams,
                'privacy_note' => $this->limitText(trim((string) ($item['privacy_note'] ?? '')), 160),
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 160),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(string) ($a['app_key'] ?? ''), (string) ($a['provider'] ?? '')] <=> [(string) ($b['app_key'] ?? ''), (string) ($b['provider'] ?? '')]);

        return array_slice($normalized, 0, 500);
    }

    private function normalizeAdWaterfallConfig(array $config): array
    {
        $mode = (string) ($config['mode'] ?? 'auto');
        $scoreWindowDays = max(1, min(90, (int) ($config['score_window_days'] ?? 7)));
        $minRequests = max(0, min(10000, (int) ($config['min_requests'] ?? 20)));
        $maxSlots = max(0, min(100, (int) ($config['max_slots'] ?? 0)));

        return [
            'enabled' => array_key_exists('enabled', $config) ? !empty($config['enabled']) : true,
            'mode' => in_array($mode, ['manual', 'auto'], true) ? $mode : 'auto',
            'score_window_days' => $scoreWindowDays,
            'min_requests' => $minRequests,
            'max_slots' => $maxSlots,
            'ecpm_weight' => max(0, min(100, (float) ($config['ecpm_weight'] ?? 45))),
            'fill_rate_weight' => max(0, min(100, (float) ($config['fill_rate_weight'] ?? 25))),
            'ctr_weight' => max(0, min(100, (float) ($config['ctr_weight'] ?? 10))),
            'reward_rate_weight' => max(0, min(100, (float) ($config['reward_rate_weight'] ?? 10))),
            'failure_penalty_weight' => max(0, min(100, (float) ($config['failure_penalty_weight'] ?? 25))),
            'manual_sort_weight' => max(0, min(100, (float) ($config['manual_sort_weight'] ?? 10))),
            'remark' => $this->limitText(trim((string) ($config['remark'] ?? '')), 160),
            'updated_at' => trim((string) ($config['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
        ];
    }

    private function normalizeAdDeliveryRules(array $items): array
    {
        $normalized = [];
        $seen = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = $this->limitText(trim((string) ($item['name'] ?? '')), 60);
            if ($name === '') {
                $name = '广告分层策略' . ($index + 1);
            }
            $code = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['code'] ?? '')));
            $code = $code !== '' ? $this->limitText($code, 80) : ('adr_' . ($index + 1));
            $baseCode = $code;
            $suffix = 2;
            while (isset($seen[$code])) {
                $code = $baseCode . '_' . $suffix;
                $suffix++;
            }
            $seen[$code] = true;
            $status = (string) ($item['status'] ?? 'active');
            $membership = (string) ($item['membership'] ?? 'all');
            $payStage = (string) ($item['pay_stage'] ?? 'all');
            $appKeys = $this->normalizeListText($item['app_keys'] ?? $item['app_key'] ?? ['default'], 20, 60, true);
            if (empty($appKeys)) {
                $appKeys = ['default'];
            }

            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'code' => $code,
                'name' => $name,
                'status' => in_array($status, ['active', 'paused'], true) ? $status : 'active',
                'app_keys' => $appKeys,
                'slot_codes' => $this->normalizeListText($item['slot_codes'] ?? $item['slot_code'] ?? [], 50, 80, true),
                'positions' => $this->normalizeListText($item['positions'] ?? $item['position'] ?? [], 20, 60, true),
                'ad_types' => $this->normalizeListText($item['ad_types'] ?? $item['ad_type'] ?? [], 10, 40, true),
                'providers' => $this->normalizeListText($item['providers'] ?? $item['provider'] ?? [], 20, 40, true),
                'user_tags' => $this->normalizeListText($item['user_tags'] ?? $item['user_tag'] ?? [], 20, 24),
                'membership' => in_array($membership, ['all', 'guest', 'member'], true) ? $membership : 'all',
                'pay_stage' => in_array($payStage, ['all', 'new', 'unpaid', 'paid'], true) ? $payStage : 'all',
                'priority' => (int) ($item['priority'] ?? 100),
                'daily_limit_override' => max(0, (int) ($item['daily_limit_override'] ?? 0)),
                'frequency_seconds_override' => max(0, (int) ($item['frequency_seconds_override'] ?? 0)),
                'reward_coins_override' => max(0, (int) ($item['reward_coins_override'] ?? 0)),
                'max_impressions_per_day' => max(0, (int) ($item['max_impressions_per_day'] ?? 0)),
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 160),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(string) (($a['app_keys'][0] ?? 'default')), (int) ($a['priority'] ?? 100), (int) ($a['id'] ?? 0)] <=> [(string) (($b['app_keys'][0] ?? 'default')), (int) ($b['priority'] ?? 100), (int) ($b['id'] ?? 0)]);

        return array_slice($normalized, 0, 1000);
    }

    private function adProviderDefaultName(string $provider): string
    {
        return match ($provider) {
            'csj', 'pangle', 'bytedance' => '穿山甲',
            'ylh', 'gdt', 'tencent' => '优量汇',
            'kuaishou', 'ks' => '快手联盟',
            'baidu' => '百度联盟',
            default => '自定义广告平台',
        };
    }

    private function normalizeAdSlots(array $items): array
    {
        $normalized = [];
        $seenCodes = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $code = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['code'] ?? '')));
            $code = $code !== '' ? $this->limitText($code, 80) : ('ad_' . ($index + 1));
            $baseCode = $code;
            $suffix = 2;
            while (isset($seenCodes[$code])) {
                $code = $baseCode . '_' . $suffix;
                $suffix++;
            }
            $seenCodes[$code] = true;
            $adType = (string) ($item['ad_type'] ?? $item['type'] ?? 'banner');
            $position = (string) ($item['position'] ?? 'home_banner');
            $status = (string) ($item['status'] ?? 'active');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'code' => $code,
                'name' => $this->limitText(trim((string) ($item['name'] ?? '')) ?: '广告位' . ($index + 1), 60),
                'ad_type' => in_array($adType, ['reward_video', 'interstitial', 'banner', 'floating', 'native'], true) ? $adType : 'banner',
                'position' => in_array($position, ['home_banner', 'player_pause', 'player_pre_unlock', 'reader_bottom', 'center_top', 'landing_page'], true) ? $position : 'home_banner',
                'app_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? ''))) ?: 'default', 60),
                'status' => in_array($status, ['active', 'review', 'paused'], true) ? $status : 'active',
                'provider' => $this->limitText(trim((string) ($item['provider'] ?? '')), 40),
                'unit_id' => $this->limitText(trim((string) ($item['unit_id'] ?? $item['ad_unit_id'] ?? '')), 100),
                'estimate_ecpm' => round(max(0, (float) ($item['estimate_ecpm'] ?? $item['ecpm'] ?? 0)), 2),
                'revenue_share_rate' => min(100, max(0, round((float) ($item['revenue_share_rate'] ?? 100), 2))),
                'title' => $this->limitText(trim((string) ($item['title'] ?? '')), 80),
                'image' => trim((string) ($item['image'] ?? '')),
                'link' => trim((string) ($item['link'] ?? '')),
                'reward_coins' => max(0, (int) ($item['reward_coins'] ?? 0)),
                'daily_limit' => max(0, (int) ($item['daily_limit'] ?? 0)),
                'frequency_seconds' => max(0, (int) ($item['frequency_seconds'] ?? 0)),
                'sort' => (int) ($item['sort'] ?? (($index + 1) * 10)),
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 120),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(string) ($a['app_key'] ?? ''), (int) ($a['sort'] ?? 0), (int) ($a['id'] ?? 0)] <=> [(string) ($b['app_key'] ?? ''), (int) ($b['sort'] ?? 0), (int) ($b['id'] ?? 0)]);

        return array_slice($normalized, 0, 1000);
    }

    private function normalizeAdEvents(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $event = (string) ($item['event'] ?? 'impression');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'ad_slot_id' => max(0, (int) ($item['ad_slot_id'] ?? 0)),
                'code' => preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($item['code'] ?? '')),
                'event' => in_array($event, ['request', 'fill', 'impression', 'click', 'reward', 'fail'], true) ? $event : 'impression',
                'user_id' => max(0, (int) ($item['user_id'] ?? 0)),
                'app_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? ''))) ?: 'default', 60),
                'position' => $this->limitText(trim((string) ($item['position'] ?? '')), 40),
                'ad_type' => $this->limitText(trim((string) ($item['ad_type'] ?? '')), 40),
                'provider' => $this->limitText(trim((string) ($item['provider'] ?? '')), 40),
                'unit_id' => $this->limitText(trim((string) ($item['unit_id'] ?? '')), 100),
                'reward_coins' => max(0, (int) ($item['reward_coins'] ?? 0)),
                'revenue' => round(max(0, (float) ($item['revenue'] ?? 0)), 4),
                'ecpm' => round(max(0, (float) ($item['ecpm'] ?? 0)), 2),
                'currency' => $this->limitText(trim((string) ($item['currency'] ?? 'CNY')) ?: 'CNY', 12),
                'error_code' => $this->limitText(trim((string) ($item['error_code'] ?? '')), 60),
                'error_message' => $this->limitText(trim((string) ($item['error_message'] ?? '')), 160),
                'delivery_rule_id' => max(0, (int) ($item['delivery_rule_id'] ?? 0)),
                'delivery_rule_code' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['delivery_rule_code'] ?? ''))) ?: '', 80),
                'delivery_rule_name' => $this->limitText(trim((string) ($item['delivery_rule_name'] ?? '')), 60),
                'path' => trim((string) ($item['path'] ?? '')),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        return array_slice($normalized, 0, 10000);
    }

    private function normalizeMiniProgramConfigs(array $items): array
    {
        $normalized = [];
        $seenKeys = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $appKey = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? $item['media_app_id'] ?? '')));
            $appKey = $this->limitText($appKey !== '' ? $appKey : 'default', 60);
            if (isset($seenKeys[$appKey])) {
                $appKey .= '_' . ($index + 1);
            }
            $seenKeys[$appKey] = true;
            $status = (string) ($item['status'] ?? 'draft');
            $uploadMode = (string) ($item['upload_mode'] ?? 'manual');
            $contentScope = (string) ($item['content_scope'] ?? 'all');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'app_key' => $appKey,
                'name' => $this->limitText(trim((string) ($item['name'] ?? '')) ?: '小程序配置' . ($index + 1), 80),
                'status' => in_array($status, ['draft', 'active', 'paused'], true) ? $status : 'draft',
                'mp_app_id' => $this->limitText(trim((string) ($item['mp_app_id'] ?? $item['appid'] ?? '')), 80),
                'mp_app_secret' => $this->limitText(trim((string) ($item['mp_app_secret'] ?? $item['app_secret'] ?? '')), 120),
                'original_id' => $this->limitText(trim((string) ($item['original_id'] ?? '')), 80),
                'mch_id' => $this->limitText(trim((string) ($item['mch_id'] ?? '')), 80),
                'server_domain' => trim((string) ($item['server_domain'] ?? '')),
                'upload_token' => $this->limitText(trim((string) ($item['upload_token'] ?? '')), 120),
                'upload_mode' => in_array($uploadMode, ['manual', 'api', 'ci'], true) ? $uploadMode : 'manual',
                'api_base_url' => trim((string) ($item['api_base_url'] ?? '')) ?: 'https://api.weixin.qq.com',
                'access_token' => $this->limitText(trim((string) ($item['access_token'] ?? '')), 240),
                'access_token_expires_at' => trim((string) ($item['access_token_expires_at'] ?? '')),
                'access_token_last_refresh_at' => trim((string) ($item['access_token_last_refresh_at'] ?? '')),
                'access_token_status' => in_array((string) ($item['access_token_status'] ?? ''), ['success', 'failed', 'skipped'], true) ? (string) $item['access_token_status'] : '',
                'access_token_message' => $this->limitText(trim((string) ($item['access_token_message'] ?? '')), 240),
                'access_token_response' => is_array($item['access_token_response'] ?? null) ? $item['access_token_response'] : [],
                'content_scope' => in_array($contentScope, ['all', 'drama', 'novel'], true) ? $contentScope : 'all',
                'default_drama_category' => $this->limitText(trim((string) ($item['default_drama_category'] ?? '短剧')), 40),
                'default_novel_category' => $this->limitText(trim((string) ($item['default_novel_category'] ?? '小说')), 40),
                'privacy_url' => trim((string) ($item['privacy_url'] ?? '')),
                'agreement_url' => trim((string) ($item['agreement_url'] ?? '')),
                'config_json' => is_array($item['config_json'] ?? null) ? $item['config_json'] : [],
                'last_sync_at' => trim((string) ($item['last_sync_at'] ?? '')),
                'last_sync_status' => $this->limitText(trim((string) ($item['last_sync_status'] ?? '')), 40),
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 160),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(string) ($a['app_key'] ?? ''), (int) ($a['id'] ?? 0)] <=> [(string) ($b['app_key'] ?? ''), (int) ($b['id'] ?? 0)]);

        return array_slice($normalized, 0, 200);
    }

    private function normalizeMiniProgramSyncTasks(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $contentType = (string) ($item['content_type'] ?? 'drama');
            $status = (string) ($item['status'] ?? 'pending');
            $allowedStatuses = ['pending', 'generated', 'uploaded', 'review_submitted', 'review_passed', 'review_rejected', 'released', 'failed'];
            $errorCategory = (string) ($item['error_category'] ?? '');
            $errorCategory = in_array($errorCategory, ['auth_failed', 'rate_limited', 'platform_failed', 'retryable_failed', 'config_missing', 'invalid_status', 'review_rejected', 'unknown'], true) ? $errorCategory : '';
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'config_id' => max(0, (int) ($item['config_id'] ?? 0)),
                'app_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? ''))) ?: 'default', 60),
                'content_type' => in_array($contentType, ['drama', 'novel', 'mixed'], true) ? $contentType : 'drama',
                'content_ids' => array_values(array_unique(array_filter(array_map('intval', (array) ($item['content_ids'] ?? [])), static fn (int $id): bool => $id > 0))),
                'status' => in_array($status, $allowedStatuses, true) ? $status : 'pending',
                'item_count' => max(0, (int) ($item['item_count'] ?? 0)),
                'version' => $this->limitText(trim((string) ($item['version'] ?? '')), 40),
                'upload_mode' => in_array((string) ($item['upload_mode'] ?? ''), ['manual', 'api', 'ci'], true) ? (string) $item['upload_mode'] : 'manual',
                'upload_job_id' => $this->limitText(trim((string) ($item['upload_job_id'] ?? '')), 80),
                'review_id' => $this->limitText(trim((string) ($item['review_id'] ?? '')), 80),
                'release_version' => $this->limitText(trim((string) ($item['release_version'] ?? '')), 40),
                'retry_count' => max(0, (int) ($item['retry_count'] ?? 0)),
                'error_category' => $errorCategory,
                'next_retry_at' => trim((string) ($item['next_retry_at'] ?? '')),
                'retry_blocked_reason' => $this->limitText(trim((string) ($item['retry_blocked_reason'] ?? '')), 240),
                'platform_code' => $this->limitText(trim((string) ($item['platform_code'] ?? '')), 80),
                'platform_message' => $this->limitText(trim((string) ($item['platform_message'] ?? '')), 240),
                'last_error_at' => trim((string) ($item['last_error_at'] ?? '')),
                'last_error' => $this->limitText(trim((string) ($item['last_error'] ?? '')), 240),
                'last_action' => $this->limitText(trim((string) ($item['last_action'] ?? '')), 40),
                'last_failed_action' => $this->limitText(trim((string) ($item['last_failed_action'] ?? '')), 40),
                'last_action_at' => trim((string) ($item['last_action_at'] ?? '')),
                'uploaded_at' => trim((string) ($item['uploaded_at'] ?? '')),
                'review_submitted_at' => trim((string) ($item['review_submitted_at'] ?? '')),
                'review_checked_at' => trim((string) ($item['review_checked_at'] ?? '')),
                'released_at' => trim((string) ($item['released_at'] ?? '')),
                'manifest' => is_array($item['manifest'] ?? null) ? $item['manifest'] : [],
                'request_snapshot' => is_array($item['request_snapshot'] ?? null) ? $item['request_snapshot'] : [],
                'response_snapshot' => is_array($item['response_snapshot'] ?? null) ? $item['response_snapshot'] : [],
                'action_logs' => array_slice(array_values(array_filter((array) ($item['action_logs'] ?? []), static fn ($log): bool => is_array($log))), -30),
                'message' => $this->limitText(trim((string) ($item['message'] ?? '')), 240),
                'created_by_admin_id' => max(0, (int) ($item['created_by_admin_id'] ?? 0)),
                'created_by_admin_name' => $this->limitText(trim((string) ($item['created_by_admin_name'] ?? '')), 60),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        return array_slice($normalized, 0, 1000);
    }

    private function normalizeSystemConfigFragments(array $items): array
    {
        $normalized = [];
        $seenKeys = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $key = preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) ($item['key'] ?? $item['config_key'] ?? '')));
            if ($key === '') {
                $key = 'fragment_' . ($index + 1);
            }
            if (isset($seenKeys[$key])) {
                $key .= '_' . ($index + 1);
            }
            $seenKeys[$key] = true;
            $type = (string) ($item['type'] ?? 'text');
            $status = (string) ($item['status'] ?? 'active');
            $value = $item['value'] ?? '';
            if (is_array($value)) {
                $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $value = $encoded === false ? '' : $value;
            }
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'key' => $this->limitText($key, 80),
                'name' => $this->limitText(trim((string) ($item['name'] ?? '')) ?: $key, 80),
                'group' => $this->limitText(trim((string) ($item['group'] ?? 'system')) ?: 'system', 40),
                'type' => in_array($type, ['text', 'json', 'url', 'secret'], true) ? $type : 'text',
                'value' => $this->limitText(trim((string) $value), 4000),
                'sensitive' => array_key_exists('sensitive', $item) ? !empty($item['sensitive']) : $type === 'secret',
                'status' => in_array($status, ['active', 'paused'], true) ? $status : 'active',
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 160),
                'updated_by' => $this->limitText(trim((string) ($item['updated_by'] ?? '')), 60),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(string) ($a['group'] ?? ''), (string) ($a['key'] ?? '')] <=> [(string) ($b['group'] ?? ''), (string) ($b['key'] ?? '')]);

        return array_slice($normalized, 0, 500);
    }

    private function normalizeSmsConfig(array $config): array
    {
        $provider = (string) ($config['provider'] ?? 'mock');
        $status = (string) ($config['status'] ?? 'active');

        return [
            'status' => in_array($status, ['active', 'paused'], true) ? $status : 'active',
            'provider' => in_array($provider, ['mock', 'aliyun', 'tencent', 'qiniu', 'custom'], true) ? $provider : 'mock',
            'sign_name' => $this->limitText(trim((string) ($config['sign_name'] ?? '精秀短剧')), 40),
            'template_id' => $this->limitText(trim((string) ($config['template_id'] ?? 'SMS_LOGIN')), 80),
            'template_content' => $this->limitText(trim((string) ($config['template_content'] ?? '您的验证码为 {code}，{minutes} 分钟内有效。')), 200),
            'access_key' => $this->limitText(trim((string) ($config['access_key'] ?? '')), 160),
            'access_secret' => $this->limitText(trim((string) ($config['access_secret'] ?? '')), 240),
            'endpoint' => trim((string) ($config['endpoint'] ?? '')),
            'expire_minutes' => max(1, min(30, (int) ($config['expire_minutes'] ?? 10))),
            'daily_limit_per_phone' => max(1, min(50, (int) ($config['daily_limit_per_phone'] ?? 10))),
            'remark' => $this->limitText(trim((string) ($config['remark'] ?? '')), 160),
            'updated_at' => trim((string) ($config['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
        ];
    }

    private function normalizeEmailConfig(array $config): array
    {
        $provider = (string) ($config['provider'] ?? 'mock');
        $status = (string) ($config['status'] ?? 'paused');
        $secure = (string) ($config['secure'] ?? 'tls');

        return [
            'status' => in_array($status, ['active', 'paused'], true) ? $status : 'paused',
            'provider' => in_array($provider, ['mock', 'smtp', 'aliyun', 'sendcloud', 'custom'], true) ? $provider : 'mock',
            'from_email' => $this->limitText(trim((string) ($config['from_email'] ?? 'noreply@example.com')), 120),
            'from_name' => $this->limitText(trim((string) ($config['from_name'] ?? '精秀短剧')), 60),
            'host' => $this->limitText(trim((string) ($config['host'] ?? '')), 160),
            'port' => max(0, min(65535, (int) ($config['port'] ?? 465))),
            'secure' => in_array($secure, ['none', 'ssl', 'tls'], true) ? $secure : 'tls',
            'username' => $this->limitText(trim((string) ($config['username'] ?? '')), 160),
            'password' => $this->limitText(trim((string) ($config['password'] ?? '')), 240),
            'test_subject' => $this->limitText(trim((string) ($config['test_subject'] ?? '精秀短剧邮件测试')), 120),
            'test_template' => $this->limitText(trim((string) ($config['test_template'] ?? '这是一封系统测试邮件，发送时间：{time}。')), 500),
            'remark' => $this->limitText(trim((string) ($config['remark'] ?? '')), 160),
            'updated_at' => trim((string) ($config['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
        ];
    }

    private function normalizeEmailDeliveryLogs(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $status = (string) ($item['status'] ?? 'success');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'to_email' => $this->limitText(trim((string) ($item['to_email'] ?? '')), 120),
                'subject' => $this->limitText(trim((string) ($item['subject'] ?? '')), 120),
                'body' => $this->limitText(trim((string) ($item['body'] ?? '')), 1000),
                'provider' => $this->limitText(trim((string) ($item['provider'] ?? 'mock')), 40),
                'status' => in_array($status, ['success', 'failed', 'skipped'], true) ? $status : 'success',
                'message' => $this->limitText(trim((string) ($item['message'] ?? '')), 200),
                'response_payload' => is_array($item['response_payload'] ?? null) ? $item['response_payload'] : [],
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        return array_slice($normalized, 0, 500);
    }

    private function normalizeInAppMessages(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $recipientType = (string) ($item['recipient_type'] ?? 'user');
            $status = (string) ($item['status'] ?? 'unread');
            $priority = (string) ($item['priority'] ?? 'normal');
            $channel = (string) ($item['channel'] ?? 'in_app');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'recipient_type' => in_array($recipientType, ['user', 'admin'], true) ? $recipientType : 'user',
                'user_id' => max(0, (int) ($item['user_id'] ?? 0)),
                'admin_id' => max(0, (int) ($item['admin_id'] ?? 0)),
                'agent_id' => max(0, (int) ($item['agent_id'] ?? 0)),
                'recipient_name' => $this->limitText(trim((string) ($item['recipient_name'] ?? '')), 80),
                'recipient_contact' => $this->limitText(trim((string) ($item['recipient_contact'] ?? '')), 120),
                'scenario' => $this->limitText(trim((string) ($item['scenario'] ?? 'system_notice')), 60),
                'template_key' => preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['template_key'] ?? ''))),
                'channel' => in_array($channel, ['in_app', 'system'], true) ? $channel : 'in_app',
                'source' => $this->limitText(trim((string) ($item['source'] ?? 'manual')), 60),
                'reference_type' => $this->limitText(trim((string) ($item['reference_type'] ?? '')), 60),
                'reference_id' => max(0, (int) ($item['reference_id'] ?? 0)),
                'title' => $this->limitText(trim((string) ($item['title'] ?? '站内消息')), 160),
                'body' => $this->limitText(trim((string) ($item['body'] ?? '')), 1200),
                'priority' => in_array($priority, ['low', 'normal', 'high', 'urgent'], true) ? $priority : 'normal',
                'status' => in_array($status, ['unread', 'read', 'archived'], true) ? $status : 'unread',
                'read_at' => trim((string) ($item['read_at'] ?? '')),
                'sender_admin_id' => max(0, (int) ($item['sender_admin_id'] ?? 0)),
                'sender_admin_name' => $this->limitText(trim((string) ($item['sender_admin_name'] ?? '')), 80),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        return array_slice($normalized, 0, 5000);
    }

    private function normalizeMessageTemplates(array $items): array
    {
        $defaultByKey = [];
        foreach ($this->defaultMessageTemplates() as $default) {
            $defaultByKey[(string) ($default['template_key'] ?? '')] = $default;
        }
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $key = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['template_key'] ?? $item['key'] ?? '')));
            if ($key !== '') {
                $defaultByKey[$key] = $item;
            }
        }

        $normalized = [];
        $usedIds = [];
        $usedKeys = [];
        $nextId = 1;
        foreach (array_values($defaultByKey) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $key = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['template_key'] ?? $item['key'] ?? '')));
            if ($key === '' || isset($usedKeys[$key])) {
                $key = 'template_' . ($index + 1);
            }
            while (isset($usedKeys[$key])) {
                $key .= '_' . ($index + 1);
            }
            $usedKeys[$key] = true;

            $id = max(1, (int) ($item['id'] ?? ($index + 1)));
            while (isset($usedIds[$id])) {
                $id = ++$nextId;
            }
            $usedIds[$id] = true;
            $nextId = max($nextId, $id + 1);

            $channel = (string) ($item['channel'] ?? 'system');
            $scenario = (string) ($item['scenario'] ?? 'system_notice');
            $status = (string) ($item['status'] ?? 'active');
            $normalized[] = [
                'id' => $id,
                'template_key' => $key,
                'name' => $this->limitText(trim((string) ($item['name'] ?? '消息模板')), 80),
                'scenario' => in_array($scenario, ['login_code', 'config_approval', 'operation_alert', 'agent_settlement', 'feedback_reply', 'rights_repair', 'payment_success', 'activity', 'system_notice'], true) ? $scenario : 'system_notice',
                'channel' => in_array($channel, ['system', 'sms', 'email', 'webhook', 'in_app'], true) ? $channel : 'system',
                'status' => in_array($status, ['active', 'paused'], true) ? $status : 'active',
                'title_template' => $this->limitText(trim((string) ($item['title_template'] ?? $item['title'] ?? '')), 160),
                'body_template' => $this->limitText(trim((string) ($item['body_template'] ?? $item['body'] ?? $item['content'] ?? '')), 1200),
                'placeholders' => $this->normalizeListText($item['placeholders'] ?? [], 24, 40, true),
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 240),
                'sort' => (int) ($item['sort'] ?? ($index + 1)),
                'created_by_admin_id' => max(0, (int) ($item['created_by_admin_id'] ?? 0)),
                'created_by_admin_name' => $this->limitText(trim((string) ($item['created_by_admin_name'] ?? '系统')), 60),
                'updated_by_admin_id' => max(0, (int) ($item['updated_by_admin_id'] ?? 0)),
                'updated_by_admin_name' => $this->limitText(trim((string) ($item['updated_by_admin_name'] ?? '系统')), 60),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (int) ($a['id'] ?? 0)] <=> [(int) ($b['sort'] ?? 0), (int) ($b['id'] ?? 0)]);

        return array_slice($normalized, 0, 200);
    }

    private function defaultMessageTemplates(): array
    {
        $now = date('Y-m-d H:i:s');

        return [
            [
                'id' => 1,
                'template_key' => 'sms_login_code',
                'name' => '登录验证码短信',
                'scenario' => 'login_code',
                'channel' => 'sms',
                'status' => 'active',
                'title_template' => '验证码',
                'body_template' => '您的验证码为 {code}，{minutes} 分钟内有效。',
                'placeholders' => ['code', 'minutes', 'site'],
                'remark' => '用于用户手机号登录和绑定验证。',
                'sort' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'template_key' => 'email_test_notice',
                'name' => '系统测试邮件',
                'scenario' => 'system_notice',
                'channel' => 'email',
                'status' => 'active',
                'title_template' => '精秀短剧邮件测试',
                'body_template' => '这是一封系统测试邮件，发送时间：{time}。',
                'placeholders' => ['time', 'site'],
                'remark' => '用于短信邮件配置页验证邮件通道。',
                'sort' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'template_key' => 'config_approval_notice',
                'name' => '配置审批通知',
                'scenario' => 'config_approval',
                'channel' => 'system',
                'status' => 'active',
                'title_template' => '配置变更待处理：{{title}}',
                'body_template' => '{{message}}',
                'placeholders' => ['title', 'message', 'config_type', 'target_key', 'status'],
                'remark' => '用于配置审批提交、通过、驳回、回滚和催办。',
                'sort' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'template_key' => 'operation_alert_webhook',
                'name' => '投放预警 Webhook',
                'scenario' => 'operation_alert',
                'channel' => 'webhook',
                'status' => 'active',
                'title_template' => '短剧投放预警：{{title}}',
                'body_template' => '{{message}}',
                'placeholders' => ['title', 'message', 'priority', 'promotion_code', 'ad_id', 'material_id', 'suggestion'],
                'remark' => '用于投放异常外部通知接收人。',
                'sort' => 40,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 5,
                'template_key' => 'feedback_reply_notice',
                'name' => '投诉反馈处理通知',
                'scenario' => 'feedback_reply',
                'channel' => 'in_app',
                'status' => 'active',
                'title_template' => '您的反馈已处理',
                'body_template' => '问题类型：{type}，处理结果：{reply}',
                'placeholders' => ['type', 'reply', 'order_no', 'user_id'],
                'remark' => '用于客服处理投诉反馈后的站内消息扩展。',
                'sort' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 6,
                'template_key' => 'agent_settlement_notice',
                'name' => '代理结算通知',
                'scenario' => 'agent_settlement',
                'channel' => 'in_app',
                'status' => 'active',
                'title_template' => '代理结算：{event}',
                'body_template' => '{agent_name} {period_start} 至 {period_end} 结算状态更新：{message}',
                'placeholders' => ['event', 'agent_name', 'period_start', 'period_end', 'amount', 'message'],
                'remark' => '用于代理结算生成、打款、到账确认和异议处理提醒。',
                'sort' => 60,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 7,
                'template_key' => 'rights_repair_notice',
                'name' => '权益变更通知',
                'scenario' => 'rights_repair',
                'channel' => 'in_app',
                'status' => 'active',
                'title_template' => '权益变更：{action}',
                'body_template' => '{message} {content} {remark}',
                'placeholders' => ['action', 'message', 'content', 'coins', 'vip_days', 'remark'],
                'remark' => '用于后台补发或撤销用户权益后的站内消息。',
                'sort' => 70,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 8,
                'template_key' => 'payment_success_notice',
                'name' => '支付成功通知',
                'scenario' => 'payment_success',
                'channel' => 'in_app',
                'status' => 'active',
                'title_template' => '支付成功：{content}',
                'body_template' => '订单 {order_no} 已支付成功，金额 ￥{amount}，{rights} 已发放。',
                'placeholders' => ['order_no', 'amount', 'content', 'rights'],
                'remark' => '用于订单支付成功并发放权益后的站内消息。',
                'sort' => 80,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 9,
                'template_key' => 'activity_reward_notice',
                'name' => '活动奖励通知',
                'scenario' => 'activity',
                'channel' => 'in_app',
                'status' => 'active',
                'title_template' => '活动奖励：{activity_name}',
                'body_template' => '{message} 奖励：{reward_text}',
                'placeholders' => ['activity_name', 'message', 'reward_type', 'reward_text'],
                'remark' => '用于活动领奖成功后的用户站内消息。',
                'sort' => 90,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
    }

    private function normalizeConfigChangeRequests(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $configType = (string) ($item['config_type'] ?? 'base_config');
            $status = (string) ($item['status'] ?? 'pending');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'title' => $this->limitText(trim((string) ($item['title'] ?? '配置变更')), 120),
                'config_type' => in_array($configType, ['base_config', 'notification_config', 'config_fragment', 'payment_config', 'app_config'], true) ? $configType : 'base_config',
                'target_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_.-]+/', '', trim((string) ($item['target_key'] ?? ''))), 120),
                'status' => in_array($status, ['pending', 'approved', 'rejected', 'applied', 'rolled_back'], true) ? $status : 'pending',
                'reason' => $this->limitText(trim((string) ($item['reason'] ?? '')), 240),
                'before_snapshot' => is_array($item['before_snapshot'] ?? null) ? $item['before_snapshot'] : [],
                'after_snapshot' => is_array($item['after_snapshot'] ?? null) ? $item['after_snapshot'] : [],
                'created_by_admin_id' => max(0, (int) ($item['created_by_admin_id'] ?? 0)),
                'created_by_admin_name' => $this->limitText(trim((string) ($item['created_by_admin_name'] ?? '')), 60),
                'reviewed_by_admin_id' => max(0, (int) ($item['reviewed_by_admin_id'] ?? 0)),
                'reviewed_by_admin_name' => $this->limitText(trim((string) ($item['reviewed_by_admin_name'] ?? '')), 60),
                'review_note' => $this->limitText(trim((string) ($item['review_note'] ?? '')), 240),
                'last_reminded_by_admin_id' => max(0, (int) ($item['last_reminded_by_admin_id'] ?? 0)),
                'last_reminded_by_admin_name' => $this->limitText(trim((string) ($item['last_reminded_by_admin_name'] ?? '')), 60),
                'reminder_count' => max(0, (int) ($item['reminder_count'] ?? 0)),
                'sla_snapshot' => is_array($item['sla_snapshot'] ?? null) ? $item['sla_snapshot'] : [],
                'rolled_back_by_admin_id' => max(0, (int) ($item['rolled_back_by_admin_id'] ?? 0)),
                'rolled_back_by_admin_name' => $this->limitText(trim((string) ($item['rolled_back_by_admin_name'] ?? '')), 60),
                'rollback_note' => $this->limitText(trim((string) ($item['rollback_note'] ?? '')), 240),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'reviewed_at' => trim((string) ($item['reviewed_at'] ?? '')),
                'applied_at' => trim((string) ($item['applied_at'] ?? '')),
                'last_reminded_at' => trim((string) ($item['last_reminded_at'] ?? '')),
                'rolled_back_at' => trim((string) ($item['rolled_back_at'] ?? '')),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        return array_slice($normalized, 0, 1000);
    }

    private function normalizeConfigChangeNotificationLogs(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $event = (string) ($item['event'] ?? 'submitted');
            $channel = (string) ($item['channel'] ?? 'system');
            $status = (string) ($item['status'] ?? 'success');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'request_id' => max(0, (int) ($item['request_id'] ?? 0)),
                'event' => in_array($event, ['submitted', 'approved', 'rejected', 'rolled_back', 'reminded', 'escalated'], true) ? $event : 'submitted',
                'channel' => in_array($channel, ['system', 'email', 'sms'], true) ? $channel : 'system',
                'receiver_admin_id' => max(0, (int) ($item['receiver_admin_id'] ?? 0)),
                'receiver_name' => $this->limitText(trim((string) ($item['receiver_name'] ?? '')), 60),
                'receiver_contact' => $this->limitText(trim((string) ($item['receiver_contact'] ?? '')), 120),
                'title' => $this->limitText(trim((string) ($item['title'] ?? '配置审批通知')), 120),
                'message' => $this->limitText(trim((string) ($item['message'] ?? '')), 240),
                'status' => in_array($status, ['success', 'skipped', 'failed'], true) ? $status : 'success',
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        return array_slice($normalized, 0, 1000);
    }

    private function normalizeAppConfigDeliveryLogs(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $reviewMode = (string) ($item['review_mode'] ?? 'normal');
            $status = (string) ($item['app_status'] ?? 'active');
            $fingerprint = preg_replace('/[^a-zA-Z0-9_.:-]+/', '', trim((string) ($item['fingerprint'] ?? '')));
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'fingerprint' => $this->limitText($fingerprint !== '' ? $fingerprint : ('app_config:' . ($index + 1)), 160),
                'app_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? 'default'))) ?: 'default', 60),
                'app_name' => $this->limitText(trim((string) ($item['app_name'] ?? '默认应用')), 80),
                'user_id' => max(0, (int) ($item['user_id'] ?? 0)),
                'client_id' => $this->limitText(trim((string) ($item['client_id'] ?? '')), 80),
                'user_tier' => $this->limitText(trim((string) ($item['user_tier'] ?? '')), 40),
                'app_status' => in_array($status, ['active', 'review', 'paused'], true) ? $status : 'active',
                'review_mode' => in_array($reviewMode, ['normal', 'review', 'safe'], true) ? $reviewMode : 'normal',
                'version' => $this->limitText(trim((string) ($item['version'] ?? '')), 40),
                'min_version' => $this->limitText(trim((string) ($item['min_version'] ?? '')), 40),
                'gray_percent' => max(0, min(100, (int) ($item['gray_percent'] ?? 100))),
                'gray_bucket' => max(0, min(99, (int) ($item['gray_bucket'] ?? 0))),
                'gray_hit' => !empty($item['gray_hit']),
                'force_update' => !empty($item['force_update']),
                'show_ads' => !empty($item['show_ads']),
                'show_rewards' => !empty($item['show_rewards']),
                'show_vip' => !empty($item['show_vip']),
                'hit_count' => max(1, (int) ($item['hit_count'] ?? 1)),
                'first_seen_at' => trim((string) ($item['first_seen_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'last_seen_at' => trim((string) ($item['last_seen_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['last_seen_at'] ?? ''), (string) ($a['last_seen_at'] ?? '')));

        return array_slice($normalized, 0, 2000);
    }

    private function normalizeAdminOperationLogs(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $status = (string) ($item['status'] ?? 'success');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'action' => $this->limitText(trim((string) ($item['action'] ?? '')), 80),
                'section' => $this->limitText(trim((string) ($item['section'] ?? '')), 60),
                'status' => in_array($status, ['success', 'failed', 'denied'], true) ? $status : 'success',
                'message' => $this->limitText(trim((string) ($item['message'] ?? '')), 200),
                'admin_id' => max(0, (int) ($item['admin_id'] ?? 0)),
                'admin_name' => $this->limitText(trim((string) ($item['admin_name'] ?? '')), 60),
                'admin_role' => $this->limitText(trim((string) ($item['admin_role'] ?? '')), 40),
                'ip' => $this->limitText(trim((string) ($item['ip'] ?? '')), 80),
                'user_agent' => $this->limitText(trim((string) ($item['user_agent'] ?? '')), 180),
                'summary' => is_array($item['summary'] ?? null) ? $item['summary'] : [],
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        return array_slice($normalized, 0, 2000);
    }

    private function normalizeFilterPresets(array $items): array
    {
        $normalized = [];
        $usedIds = [];
        $nextId = 1;
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $scope = (string) ($item['scope'] ?? 'orders');
            if (!in_array($scope, ['orders', 'callback_logs', 'analytics'], true)) {
                continue;
            }
            $name = $this->limitText(trim((string) ($item['name'] ?? '')), 60);
            if ($name === '') {
                continue;
            }
            $id = max(1, (int) ($item['id'] ?? ($index + 1)));
            while (isset($usedIds[$id])) {
                $id = ++$nextId;
            }
            $usedIds[$id] = true;
            $nextId = max($nextId, $id + 1);

            $normalized[] = [
                'id' => $id,
                'scope' => $scope,
                'name' => $name,
                'filters' => $this->normalizeFilterPresetFilters($scope, is_array($item['filters'] ?? null) ? (array) $item['filters'] : []),
                'admin_id' => max(0, (int) ($item['admin_id'] ?? 0)),
                'admin_name' => $this->limitText(trim((string) ($item['admin_name'] ?? '')), 60),
                'role' => $this->limitText(trim((string) ($item['role'] ?? '')), 40),
                'is_shared' => !empty($item['is_shared']),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? '')));

        return array_slice($normalized, 0, 500);
    }

    private function normalizeFilterPresetFilters(string $scope, array $filters): array
    {
        $allowed = match ($scope) {
            'callback_logs' => ['status', 'event', 'order_no', 'code', 'platform', 'app_key', 'ad_id', 'material_id'],
            'analytics' => ['date_preset', 'date_start', 'date_end', 'app_key', 'business_id', 'leader_id', 'agent_id', 'promotion_link_id', 'promotion_code', 'traffic_platform', 'channel_id', 'ad_id', 'material_id'],
            default => ['order_no', 'user_keyword', 'payment_route_id', 'promotion_code', 'traffic_platform', 'channel_id', 'media_app_id', 'ad_id', 'material_id', 'status', 'per_page'],
        };
        $normalized = [];
        foreach ($allowed as $key) {
            $value = trim((string) ($filters[$key] ?? ''));
            if (in_array($key, ['business_id', 'leader_id', 'agent_id', 'promotion_link_id'], true)) {
                $value = (string) max(0, (int) $value);
            } elseif ($key === 'status' && $scope === 'orders') {
                $value = in_array($value, ['all', 'pending', 'paid', 'refund_pending', 'partial_refunded', 'refunded', 'failed', 'closed', 'expired'], true) ? $value : 'all';
            } elseif ($key === 'status') {
                $value = in_array($value, ['all', 'pending', 'success', 'failed', 'skipped'], true) ? $value : 'all';
            } elseif ($key === 'event') {
                $value = in_array($value, ['all', 'add_desktop', 'paid'], true) ? $value : 'all';
            } elseif ($key === 'per_page') {
                $perPage = (int) $value;
                $value = (string) (in_array($perPage, [10, 100], true) ? $perPage : 10);
            } elseif ($key === 'date_preset') {
                $value = in_array($value, ['today', 'yesterday', 'last_7_days', 'last_30_days', 'last_90_days', 'this_month', 'last_month', 'custom'], true) ? $value : 'all';
            } elseif (in_array($key, ['date_start', 'date_end'], true)) {
                $value = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : '';
            } else {
                $value = $this->limitText($value, 100);
            }

            if ($value === '' || $value === '0' || $value === 'all' || ($key === 'per_page' && $value === '10')) {
                continue;
            }
            $normalized[$key] = $value;
        }

        return $normalized;
    }

    private function normalizeRedeemCodes(array $items): array
    {
        $normalized = [];
        $seenCodes = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $code = strtoupper((string) preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['code'] ?? ''))));
            if ($code === '') {
                $code = 'CODE' . ($index + 1);
            }
            $baseCode = $code;
            $suffix = 2;
            while (isset($seenCodes[$code])) {
                $code = $baseCode . '_' . $suffix;
                $suffix++;
            }
            $seenCodes[$code] = true;
            $status = (string) ($item['status'] ?? 'active');
            $rewardType = (string) ($item['reward_type'] ?? 'coin');
            $coinAmount = max(0, (int) ($item['coin_amount'] ?? $item['coins'] ?? 0));
            $bonusCoinAmount = max(0, (int) ($item['bonus_coin_amount'] ?? $item['bonus_coins'] ?? 0));
            $vipDays = max(0, (int) ($item['vip_days'] ?? 0));
            if (!in_array($rewardType, ['coin', 'vip', 'mixed'], true)) {
                $rewardType = $vipDays > 0 && ($coinAmount + $bonusCoinAmount) > 0 ? 'mixed' : ($vipDays > 0 ? 'vip' : 'coin');
            }

            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'code' => $this->limitText($code, 40),
                'name' => $this->limitText(trim((string) ($item['name'] ?? '')) ?: ('兑换码' . ($index + 1)), 80),
                'status' => in_array($status, ['active', 'paused'], true) ? $status : 'active',
                'batch_no' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['batch_no'] ?? ''))), 60),
                'batch_size' => max(0, (int) ($item['batch_size'] ?? 0)),
                'is_single_use' => array_key_exists('is_single_use', $item) ? !empty($item['is_single_use']) : (max(0, (int) ($item['total_limit'] ?? 0)) === 1 && max(1, (int) ($item['per_user_limit'] ?? 1)) === 1),
                'source' => in_array((string) ($item['source'] ?? 'manual'), ['manual', 'batch_generated', 'external_import'], true) ? (string) ($item['source'] ?? 'manual') : 'manual',
                'imported_at' => $this->normalizeDateTime((string) ($item['imported_at'] ?? ''), ''),
                'import_file_name' => $this->limitText(trim((string) ($item['import_file_name'] ?? '')), 160),
                'import_file_size' => max(0, (int) ($item['import_file_size'] ?? 0)),
                'reward_type' => $rewardType,
                'coin_amount' => $coinAmount,
                'bonus_coin_amount' => $bonusCoinAmount,
                'vip_days' => $vipDays,
                'total_limit' => max(0, (int) ($item['total_limit'] ?? 0)),
                'per_user_limit' => max(1, (int) ($item['per_user_limit'] ?? 1)),
                'app_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? ''))) ?: 'all', 60),
                'promotion_code' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['promotion_code'] ?? ''))), 80),
                'agent_id' => max(0, (int) ($item['agent_id'] ?? 0)),
                'channel_id' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['channel_id'] ?? ''))), 80),
                'allowed_user_ids' => $this->normalizeIntegerList($item['allowed_user_ids'] ?? [], 200),
                'started_at' => $this->normalizeDateTime((string) ($item['started_at'] ?? ''), ''),
                'ended_at' => $this->normalizeDateTime((string) ($item['ended_at'] ?? ''), ''),
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 160),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(string) ($a['status'] ?? ''), (int) ($b['id'] ?? 0)] <=> [(string) ($b['status'] ?? ''), (int) ($a['id'] ?? 0)]);

        return array_slice($normalized, 0, 1000);
    }

    private function normalizeRightsRepairLogs(array $items): array
    {
        $normalized = [];
        $allowedActions = ['grant_coin', 'deduct_coin', 'grant_vip', 'revoke_vip', 'grant_content', 'revoke_content'];
        $allowedContentTypes = ['drama', 'novel'];
        $allowedEntitlementTypes = ['drama_unlock', 'episode_unlock', 'novel_unlock', 'novel_chapter_unlock', 'membership'];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $action = (string) ($item['action'] ?? 'grant_coin');
            $contentType = (string) ($item['content_type'] ?? 'drama');
            $status = (string) ($item['status'] ?? 'success');
            $entitlementType = (string) ($item['entitlement_type'] ?? '');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'user_id' => max(0, (int) ($item['user_id'] ?? 0)),
                'action' => in_array($action, $allowedActions, true) ? $action : 'grant_coin',
                'content_type' => in_array($contentType, $allowedContentTypes, true) ? $contentType : 'drama',
                'drama_id' => max(0, (int) ($item['drama_id'] ?? 0)),
                'episode_id' => empty($item['episode_id']) ? null : max(0, (int) $item['episode_id']),
                'novel_id' => max(0, (int) ($item['novel_id'] ?? 0)),
                'chapter_id' => empty($item['chapter_id']) ? null : max(0, (int) $item['chapter_id']),
                'coins' => (int) ($item['coins'] ?? 0),
                'bonus_coins' => (int) ($item['bonus_coins'] ?? 0),
                'vip_days' => max(0, (int) ($item['vip_days'] ?? 0)),
                'entitlement_type' => in_array($entitlementType, $allowedEntitlementTypes, true) ? $entitlementType : '',
                'status' => in_array($status, ['success', 'failed'], true) ? $status : 'success',
                'message' => $this->limitText(trim((string) ($item['message'] ?? '')), 200),
                'admin_id' => max(0, (int) ($item['admin_id'] ?? 0)),
                'admin_name' => $this->limitText(trim((string) ($item['admin_name'] ?? '')), 60),
                'before_snapshot' => is_array($item['before_snapshot'] ?? null) ? $item['before_snapshot'] : [],
                'after_snapshot' => is_array($item['after_snapshot'] ?? null) ? $item['after_snapshot'] : [],
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 240),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        return array_slice($normalized, 0, 10000);
    }

    private function normalizeRedeemCodeLogs(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $status = (string) ($item['status'] ?? 'success');
            $rewardType = (string) ($item['reward_type'] ?? 'coin');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'code_id' => max(0, (int) ($item['code_id'] ?? 0)),
                'code' => $this->limitText(strtoupper((string) preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['code'] ?? '')))), 40),
                'user_id' => max(0, (int) ($item['user_id'] ?? 0)),
                'app_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? ''))) ?: 'default', 60),
                'promotion_code' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['promotion_code'] ?? ''))), 80),
                'agent_id' => max(0, (int) ($item['agent_id'] ?? 0)),
                'channel_id' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['channel_id'] ?? ''))), 80),
                'reward_type' => in_array($rewardType, ['coin', 'vip', 'mixed'], true) ? $rewardType : 'coin',
                'coin_amount' => max(0, (int) ($item['coin_amount'] ?? $item['coins'] ?? 0)),
                'bonus_coin_amount' => max(0, (int) ($item['bonus_coin_amount'] ?? $item['bonus_coins'] ?? 0)),
                'vip_days' => max(0, (int) ($item['vip_days'] ?? 0)),
                'status' => in_array($status, ['success', 'failed'], true) ? $status : 'success',
                'message' => $this->limitText(trim((string) ($item['message'] ?? '')), 160),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        return array_slice($normalized, 0, 10000);
    }

    private function normalizeHotRankConfigs(array $items): array
    {
        if (empty($items)) {
            $items = [[
                'id' => 1,
                'rank_key' => 'home_hot',
                'name' => '首页综合热播榜',
                'app_key' => 'all',
                'content_type' => 'mixed',
                'algorithm' => 'hot_score',
                'time_window' => 'last_7_days',
                'status' => 'active',
                'limit' => 10,
                'sort' => 10,
                'remark' => '默认按播放、浏览、解锁和历史热度综合排序。',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]];
        }

        $normalized = [];
        $usedIds = [];
        $usedKeys = [];
        $nextId = 1;
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = max(1, (int) ($item['id'] ?? ($index + 1)));
            while (isset($usedIds[$id])) {
                $id = ++$nextId;
            }
            $usedIds[$id] = true;
            $nextId = max($nextId, $id + 1);

            $rankKey = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['rank_key'] ?? $item['key'] ?? '')));
            if ($rankKey === '') {
                $rankKey = 'hot_rank_' . $id;
            }
            $baseKey = $rankKey;
            $suffix = 2;
            while (isset($usedKeys[$rankKey])) {
                $rankKey = $baseKey . '_' . $suffix;
                $suffix++;
            }
            $usedKeys[$rankKey] = true;

            $contentType = (string) ($item['content_type'] ?? 'mixed');
            $algorithm = (string) ($item['algorithm'] ?? 'hot_score');
            $timeWindow = (string) ($item['time_window'] ?? 'last_7_days');
            $status = (string) ($item['status'] ?? 'active');

            $normalized[] = [
                'id' => $id,
                'rank_key' => $this->limitText($rankKey, 60),
                'name' => $this->limitText(trim((string) ($item['name'] ?? '热播榜单')) ?: '热播榜单', 80),
                'app_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? 'all'))) ?: 'all', 60),
                'content_type' => in_array($contentType, ['mixed', 'drama', 'novel'], true) ? $contentType : 'mixed',
                'algorithm' => in_array($algorithm, ['hot_score', 'views', 'unlock', 'revenue', 'manual'], true) ? $algorithm : 'hot_score',
                'time_window' => in_array($timeWindow, ['all', 'today', 'yesterday', 'last_7_days', 'last_30_days', 'this_month'], true) ? $timeWindow : 'last_7_days',
                'status' => in_array($status, ['active', 'paused'], true) ? $status : 'active',
                'limit' => max(1, min(50, (int) ($item['limit'] ?? 10))),
                'min_score' => max(0, (int) ($item['min_score'] ?? 0)),
                'pinned_items' => $this->normalizeHotRankItems((array) ($item['pinned_items'] ?? [])),
                'started_at' => $this->normalizeDateTime((string) ($item['started_at'] ?? ''), ''),
                'ended_at' => $this->normalizeDateTime((string) ($item['ended_at'] ?? ''), ''),
                'sort' => (int) ($item['sort'] ?? (($index + 1) * 10)),
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 240),
                'created_by_admin_id' => max(0, (int) ($item['created_by_admin_id'] ?? 0)),
                'created_by_admin_name' => $this->limitText(trim((string) ($item['created_by_admin_name'] ?? '系统')), 60),
                'updated_by_admin_id' => max(0, (int) ($item['updated_by_admin_id'] ?? 0)),
                'updated_by_admin_name' => $this->limitText(trim((string) ($item['updated_by_admin_name'] ?? '系统')), 60),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (int) ($a['id'] ?? 0)] <=> [(int) ($b['sort'] ?? 0), (int) ($b['id'] ?? 0)]);

        return array_slice($normalized, 0, 200);
    }

    private function normalizeHotRankItems(array $items): array
    {
        $normalized = [];
        $seen = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $contentType = (string) ($item['content_type'] ?? 'drama') === 'novel' ? 'novel' : 'drama';
            $contentId = max(0, (int) ($item['content_id'] ?? $item['id'] ?? 0));
            if ($contentId <= 0) {
                continue;
            }
            $key = $contentType . ':' . $contentId;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $normalized[] = [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'sort' => (int) ($item['sort'] ?? (($index + 1) * 10)),
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 120),
            ];
        }
        usort($normalized, static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (int) ($a['content_id'] ?? 0)] <=> [(int) ($b['sort'] ?? 0), (int) ($b['content_id'] ?? 0)]);

        return array_slice($normalized, 0, 50);
    }

    private function normalizeHomeRecommendations(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $status = (string) ($item['status'] ?? 'active');
            $slot = (string) ($item['slot'] ?? 'home');
            $contentType = (string) ($item['content_type'] ?? 'drama');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'slot' => in_array($slot, ['home', 'rank', 'hot', 'new', 'category', 'center'], true) ? $slot : 'home',
                'title' => $this->limitText(trim((string) ($item['title'] ?? '')) ?: '首页推荐', 80),
                'subtitle' => $this->limitText(trim((string) ($item['subtitle'] ?? '')), 120),
                'content_type' => in_array($contentType, ['drama', 'novel', 'activity', 'url'], true) ? $contentType : 'drama',
                'content_id' => max(0, (int) ($item['content_id'] ?? 0)),
                'image' => trim((string) ($item['image'] ?? '')),
                'link' => trim((string) ($item['link'] ?? '')),
                'app_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? ''))) ?: 'all', 60),
                'tag' => $this->limitText(trim((string) ($item['tag'] ?? '')), 24),
                'status' => in_array($status, ['active', 'paused'], true) ? $status : 'active',
                'sort' => (int) ($item['sort'] ?? (($index + 1) * 10)),
                'started_at' => $this->normalizeDateTime((string) ($item['started_at'] ?? ''), ''),
                'ended_at' => $this->normalizeDateTime((string) ($item['ended_at'] ?? ''), ''),
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 160),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (int) ($a['id'] ?? 0)] <=> [(int) ($b['sort'] ?? 0), (int) ($b['id'] ?? 0)]);

        return array_slice($normalized, 0, 1000);
    }

    private function normalizePopupNotices(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $status = (string) ($item['status'] ?? 'active');
            $trigger = (string) ($item['trigger'] ?? 'launch');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'title' => $this->limitText(trim((string) ($item['title'] ?? '')) ?: '弹窗公告', 80),
                'content' => $this->limitText(trim((string) ($item['content'] ?? '')), 500),
                'trigger' => in_array($trigger, ['launch', 'home', 'player', 'reader', 'center', 'payment_success'], true) ? $trigger : 'launch',
                'app_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? ''))) ?: 'all', 60),
                'image' => trim((string) ($item['image'] ?? '')),
                'button_text' => $this->limitText(trim((string) ($item['button_text'] ?? '')) ?: '我知道了', 24),
                'link' => trim((string) ($item['link'] ?? '')),
                'daily_limit' => max(0, min(50, (int) ($item['daily_limit'] ?? 1))),
                'once_per_user' => array_key_exists('once_per_user', $item) ? !empty($item['once_per_user']) : true,
                'status' => in_array($status, ['active', 'paused'], true) ? $status : 'active',
                'priority' => (int) ($item['priority'] ?? 100),
                'started_at' => $this->normalizeDateTime((string) ($item['started_at'] ?? ''), ''),
                'ended_at' => $this->normalizeDateTime((string) ($item['ended_at'] ?? ''), ''),
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 160),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(int) ($a['priority'] ?? 100), (int) ($a['id'] ?? 0)] <=> [(int) ($b['priority'] ?? 100), (int) ($b['id'] ?? 0)]);

        return array_slice($normalized, 0, 500);
    }

    private function normalizeActivityConfigs(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $status = (string) ($item['status'] ?? 'active');
            $activityType = (string) ($item['activity_type'] ?? 'general');
            $rewardType = (string) ($item['reward_type'] ?? 'none');
            $experimentKey = $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['experiment_key'] ?? ''))), 60);
            $variantKey = $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['variant_key'] ?? ''))), 40);
            $targetTiers = $this->normalizeListText($item['target_tiers'] ?? ['all'], 5, 20, true);
            $targetTiers = array_values(array_filter($targetTiers, static fn (string $tier): bool => in_array($tier, ['all', 'new', 'unpaid', 'paid', 'member'], true)));
            if (empty($targetTiers) || in_array('all', $targetTiers, true)) {
                $targetTiers = ['all'];
            }
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'code' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['code'] ?? ''))) ?: ('act_' . ($index + 1)), 60),
                'name' => $this->limitText(trim((string) ($item['name'] ?? '')) ?: '活动配置', 80),
                'activity_type' => in_array($activityType, ['general', 'sign_in', 'invite', 'recharge', 'watch', 'share'], true) ? $activityType : 'general',
                'app_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? ''))) ?: 'all', 60),
                'target_tiers' => $targetTiers,
                'experiment_key' => $experimentKey,
                'variant_key' => $experimentKey !== '' ? ($variantKey !== '' ? $variantKey : 'A') : '',
                'traffic_percent' => $experimentKey !== '' ? max(0, min(100, (int) ($item['traffic_percent'] ?? 100))) : 100,
                'status' => in_array($status, ['active', 'paused'], true) ? $status : 'active',
                'title' => $this->limitText(trim((string) ($item['title'] ?? '')), 80),
                'subtitle' => $this->limitText(trim((string) ($item['subtitle'] ?? '')), 140),
                'entry_text' => $this->limitText(trim((string) ($item['entry_text'] ?? '')), 32),
                'entry_link' => trim((string) ($item['entry_link'] ?? '')),
                'image' => trim((string) ($item['image'] ?? '')),
                'reward_type' => in_array($rewardType, ['none', 'coin', 'vip', 'redeem_code'], true) ? $rewardType : 'none',
                'coin_amount' => max(0, (int) ($item['coin_amount'] ?? 0)),
                'vip_days' => max(0, (int) ($item['vip_days'] ?? 0)),
                'redeem_code_id' => max(0, (int) ($item['redeem_code_id'] ?? 0)),
                'daily_limit' => max(0, min(1000, (int) ($item['daily_limit'] ?? 0))),
                'total_limit' => max(0, (int) ($item['total_limit'] ?? 0)),
                'budget_coin_limit' => max(0, (int) ($item['budget_coin_limit'] ?? 0)),
                'vip_day_budget_coins' => max(0, (int) ($item['vip_day_budget_coins'] ?? 100)),
                'auto_pause_on_budget' => !empty($item['auto_pause_on_budget']),
                'budget_auto_paused_at' => $this->normalizeDateTime((string) ($item['budget_auto_paused_at'] ?? ''), ''),
                'budget_auto_paused_reason' => $this->limitText(trim((string) ($item['budget_auto_paused_reason'] ?? '')), 160),
                'sort' => (int) ($item['sort'] ?? (($index + 1) * 10)),
                'started_at' => $this->normalizeDateTime((string) ($item['started_at'] ?? ''), ''),
                'ended_at' => $this->normalizeDateTime((string) ($item['ended_at'] ?? ''), ''),
                'remark' => $this->limitText(trim((string) ($item['remark'] ?? '')), 160),
                'created_at' => trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($item['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (int) ($a['id'] ?? 0)] <=> [(int) ($b['sort'] ?? 0), (int) ($b['id'] ?? 0)]);

        return array_slice($normalized, 0, 500);
    }

    private function normalizeActivityParticipationLogs(array $items): array
    {
        $normalized = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $activityType = (string) ($item['activity_type'] ?? 'general');
            $rewardType = (string) ($item['reward_type'] ?? 'none');
            $status = (string) ($item['status'] ?? 'success');
            $eventType = (string) ($item['event_type'] ?? 'claim');
            $createdAt = trim((string) ($item['created_at'] ?? '')) ?: date('Y-m-d H:i:s');
            $normalized[] = [
                'id' => max(1, (int) ($item['id'] ?? ($index + 1))),
                'activity_id' => max(0, (int) ($item['activity_id'] ?? 0)),
                'activity_code' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['activity_code'] ?? $item['code'] ?? ''))), 60),
                'activity_name' => $this->limitText(trim((string) ($item['activity_name'] ?? '')), 80),
                'activity_type' => in_array($activityType, ['general', 'sign_in', 'invite', 'recharge', 'watch', 'share'], true) ? $activityType : 'general',
                'event_type' => in_array($eventType, ['exposure', 'click', 'claim'], true) ? $eventType : 'claim',
                'experiment_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['experiment_key'] ?? ''))), 60),
                'variant_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['variant_key'] ?? ''))), 40),
                'user_id' => max(0, (int) ($item['user_id'] ?? 0)),
                'app_key' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($item['app_key'] ?? ''))) ?: 'default', 60),
                'reward_type' => in_array($rewardType, ['none', 'coin', 'vip', 'redeem_code', 'mixed'], true) ? $rewardType : 'none',
                'coin_amount' => max(0, (int) ($item['coin_amount'] ?? $item['coins'] ?? 0)),
                'bonus_coin_amount' => max(0, (int) ($item['bonus_coin_amount'] ?? $item['bonus_coins'] ?? 0)),
                'vip_days' => max(0, (int) ($item['vip_days'] ?? 0)),
                'redeem_code_id' => max(0, (int) ($item['redeem_code_id'] ?? 0)),
                'status' => in_array($status, ['success', 'failed', 'tracked'], true) ? $status : 'success',
                'message' => $this->limitText(trim((string) ($item['message'] ?? '')), 160),
                'context' => is_array($item['context'] ?? null) ? $item['context'] : [],
                'participation_date' => trim((string) ($item['participation_date'] ?? '')) ?: substr($createdAt, 0, 10),
                'created_at' => $createdAt,
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        return array_slice($normalized, 0, 10000);
    }

    private function normalizeContentTags(array $tags, array $defaultNames): array
    {
        if (empty($tags)) {
            $tags = array_map(static fn (string $name): array => ['name' => $name], $defaultNames);
        }

        $normalized = [];
        $seen = [];
        foreach (array_values($tags) as $index => $tag) {
            $tag = is_array($tag) ? $tag : ['name' => $tag];
            $name = $this->limitText(trim((string) ($tag['name'] ?? '')), 24);
            if ($name === '' || isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;
            $status = (string) ($tag['status'] ?? 'active');
            $normalized[] = [
                'id' => max(1, (int) ($tag['id'] ?? ($index + 1))),
                'name' => $name,
                'color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($tag['color'] ?? '')) === 1 ? (string) $tag['color'] : '#64748b',
                'status' => in_array($status, ['active', 'paused'], true) ? $status : 'active',
                'sort' => (int) ($tag['sort'] ?? ($index + 1)),
                'created_at' => trim((string) ($tag['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($tag['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (int) ($a['id'] ?? 0)] <=> [(int) ($b['sort'] ?? 0), (int) ($b['id'] ?? 0)]);

        return $normalized;
    }

    private function normalizeContentGroups(array $groups): array
    {
        if (empty($groups)) {
            $groups = [
                ['id' => 1, 'name' => '默认内容池', 'description' => '短剧和小说统一内容分组', 'sort' => 1],
            ];
        }

        $normalized = [];
        foreach (array_values($groups) as $index => $group) {
            $group = is_array($group) ? $group : ['name' => $group];
            $name = $this->limitText(trim((string) ($group['name'] ?? '')), 40);
            if ($name === '') {
                continue;
            }
            $status = (string) ($group['status'] ?? 'active');
            $normalized[] = [
                'id' => max(1, (int) ($group['id'] ?? ($index + 1))),
                'name' => $name,
                'description' => $this->limitText(trim((string) ($group['description'] ?? '')), 120),
                'status' => in_array($status, ['active', 'paused'], true) ? $status : 'active',
                'sort' => (int) ($group['sort'] ?? ($index + 1)),
                'created_at' => trim((string) ($group['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($group['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (int) ($a['id'] ?? 0)] <=> [(int) ($b['sort'] ?? 0), (int) ($b['id'] ?? 0)]);

        return $normalized;
    }

    private function normalizeContentImportLogs(array $logs): array
    {
        $normalized = [];
        foreach (array_values($logs) as $index => $log) {
            if (!is_array($log)) {
                continue;
            }
            $status = (string) ($log['status'] ?? 'success');
            $normalized[] = [
                'id' => max(1, (int) ($log['id'] ?? ($index + 1))),
                'batch_no' => $this->limitText(preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($log['batch_no'] ?? ''))) ?: ('CIB' . date('YmdHis')), 40),
                'source' => $this->limitText(trim((string) ($log['source'] ?? 'manual')), 40),
                'format' => $this->limitText(trim((string) ($log['format'] ?? 'json')), 20),
                'status' => in_array($status, ['success', 'partial', 'failed'], true) ? $status : 'success',
                'total_count' => max(0, (int) ($log['total_count'] ?? 0)),
                'success_count' => max(0, (int) ($log['success_count'] ?? 0)),
                'failed_count' => max(0, (int) ($log['failed_count'] ?? 0)),
                'drama_count' => max(0, (int) ($log['drama_count'] ?? 0)),
                'novel_count' => max(0, (int) ($log['novel_count'] ?? 0)),
                'episode_count' => max(0, (int) ($log['episode_count'] ?? 0)),
                'chapter_count' => max(0, (int) ($log['chapter_count'] ?? 0)),
                'created_ids' => is_array($log['created_ids'] ?? null) ? [
                    'dramas' => array_values(array_filter(array_map('intval', (array) ($log['created_ids']['dramas'] ?? [])), static fn (int $id): bool => $id > 0)),
                    'novels' => array_values(array_filter(array_map('intval', (array) ($log['created_ids']['novels'] ?? [])), static fn (int $id): bool => $id > 0)),
                ] : ['dramas' => [], 'novels' => []],
                'errors' => array_slice(array_values(array_filter((array) ($log['errors'] ?? []), static fn ($item): bool => is_array($item))), 0, 50),
                'admin_id' => max(0, (int) ($log['admin_id'] ?? 0)),
                'admin_name' => $this->limitText(trim((string) ($log['admin_name'] ?? '')), 60),
                'message' => $this->limitText(trim((string) ($log['message'] ?? '')), 240),
                'created_at' => trim((string) ($log['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

        return array_slice($normalized, 0, 200);
    }

    private function normalizePromotionReplacementRules(array $rules): array
    {
        $normalized = [];
        foreach (array_values($rules) as $index => $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $contentType = in_array((string) ($rule['content_type'] ?? ''), ['drama', 'novel'], true) ? (string) $rule['content_type'] : 'drama';
            $dramaId = $contentType === 'drama' ? max(0, (int) ($rule['drama_id'] ?? 0)) : 0;
            $episodeId = $contentType === 'drama' ? max(0, (int) ($rule['episode_id'] ?? 0)) : 0;
            $novelId = $contentType === 'novel' ? max(0, (int) ($rule['novel_id'] ?? 0)) : 0;
            $chapterId = $contentType === 'novel' ? max(0, (int) ($rule['chapter_id'] ?? 0)) : 0;
            $targetUrl = trim((string) ($rule['target_url'] ?? ''));
            if ($targetUrl === '') {
                $targetUrl = $this->promotionContentTargetUrl($contentType, $dramaId, $episodeId, $novelId, $chapterId);
            }
            $normalized[] = [
                'id' => max(1, (int) ($rule['id'] ?? ($index + 1))),
                'name' => $this->limitText(trim((string) ($rule['name'] ?? '')) ?: '替换规则' . ($index + 1), 60),
                'content_type' => $contentType,
                'drama_id' => $dramaId,
                'episode_id' => $episodeId,
                'novel_id' => $novelId,
                'chapter_id' => $chapterId,
                'target_url' => $targetUrl,
                'review_url' => trim((string) ($rule['review_url'] ?? '')),
                'started_at' => $this->normalizeDateTime((string) ($rule['started_at'] ?? ''), ''),
                'ended_at' => $this->normalizeDateTime((string) ($rule['ended_at'] ?? ''), ''),
                'status' => in_array((string) ($rule['status'] ?? ''), ['active', 'paused'], true) ? (string) $rule['status'] : 'active',
                'priority' => max(0, (int) ($rule['priority'] ?? 0)),
                'remark' => $this->limitText(trim((string) ($rule['remark'] ?? '')), 120),
                'created_at' => trim((string) ($rule['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($rule['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        usort($normalized, static fn (array $a, array $b): int => [
            (int) ($b['priority'] ?? 0),
            strtotime((string) ($b['started_at'] ?? '')) ?: 0,
            (int) ($b['id'] ?? 0),
        ] <=> [
            (int) ($a['priority'] ?? 0),
            strtotime((string) ($a['started_at'] ?? '')) ?: 0,
            (int) ($a['id'] ?? 0),
        ]);

        return $normalized;
    }

    private function normalizeRechargeProductTemplates(array $templates, array $enabledProductCodes, array $globalProductCodes, string $globalRetentionCode): array
    {
        $enabledProductCodes = array_values(array_filter(array_map('strval', $enabledProductCodes), static fn (string $code): bool => $code !== ''));
        $globalProductCodes = array_values(array_intersect(array_map('strval', $globalProductCodes), $enabledProductCodes));
        if (empty($globalProductCodes)) {
            $globalProductCodes = array_slice($enabledProductCodes, 0, 6);
        }
        if (!in_array($globalRetentionCode, $enabledProductCodes, true)) {
            $globalRetentionCode = $globalProductCodes[0] ?? ($enabledProductCodes[0] ?? '');
        }

        $normalized = [];
        $seenKeys = [];
        foreach (array_values($templates) as $index => $template) {
            if (!is_array($template)) {
                continue;
            }

            $appKey = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($template['app_key'] ?? '')));
            $appKey = $appKey !== '' ? $this->limitText($appKey, 60) : 'default';
            if (isset($seenKeys[$appKey])) {
                $appKey .= '_' . ($index + 1);
            }
            $seenKeys[$appKey] = true;

            $productCodes = array_values(array_intersect(array_map('strval', (array) ($template['product_codes'] ?? [])), $enabledProductCodes));
            if (empty($productCodes)) {
                $productCodes = $globalProductCodes;
            }
            $retentionCode = (string) ($template['retention_product_code'] ?? '');
            if (!in_array($retentionCode, $enabledProductCodes, true)) {
                $retentionCode = $productCodes[0] ?? $globalRetentionCode;
            }

            $normalized[] = [
                'id' => max(1, (int) ($template['id'] ?? ($index + 1))),
                'name' => $this->limitText(trim((string) ($template['name'] ?? '')) ?: '应用商品模板', 60),
                'app_key' => $appKey,
                'app_name' => $this->limitText(trim((string) ($template['app_name'] ?? '')) ?: ($appKey === 'default' ? '默认应用' : $appKey), 60),
                'product_codes' => $productCodes,
                'retention_product_code' => $retentionCode,
                'status' => in_array((string) ($template['status'] ?? ''), ['active', 'paused'], true) ? (string) $template['status'] : 'active',
                'sort' => (int) ($template['sort'] ?? (($index + 1) * 10)),
                'remark' => $this->limitText(trim((string) ($template['remark'] ?? '')), 120),
                'created_at' => trim((string) ($template['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($template['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        $hasDefaultTemplate = false;
        foreach ($normalized as $template) {
            if ((string) ($template['app_key'] ?? '') === 'default') {
                $hasDefaultTemplate = true;
                break;
            }
        }
        if (!$hasDefaultTemplate) {
            array_unshift($normalized, [
                'id' => empty($normalized) ? 1 : ((int) max(array_column($normalized, 'id')) + 1),
                'name' => '默认应用模板',
                'app_key' => 'default',
                'app_name' => '默认应用',
                'product_codes' => $globalProductCodes,
                'retention_product_code' => $globalRetentionCode,
                'status' => 'active',
                'sort' => 0,
                'remark' => '未命中应用时使用的默认充值商品模板',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        usort($normalized, static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (int) ($a['id'] ?? 0)] <=> [(int) ($b['sort'] ?? 0), (int) ($b['id'] ?? 0)]);

        return $normalized;
    }

    private function normalizeApps(array $apps, array $templateKeys, array $paymentRouteIds, string $defaultPaymentRouteId, string $siteName): array
    {
        $templateKeys = array_values(array_filter(array_map('strval', $templateKeys), static fn (string $key): bool => $key !== ''));
        $paymentRouteIds = array_values(array_filter(array_map('strval', $paymentRouteIds), static fn (string $id): bool => $id !== ''));
        $defaultPaymentRouteId = in_array($defaultPaymentRouteId, $paymentRouteIds, true) ? $defaultPaymentRouteId : ($paymentRouteIds[0] ?? '');
        $normalized = [];
        $seenKeys = [];
        foreach (array_values($apps) as $index => $app) {
            if (!is_array($app)) {
                continue;
            }

            $appKey = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($app['app_key'] ?? $app['media_app_id'] ?? $app['app_id'] ?? '')));
            $appKey = $appKey !== '' ? $this->limitText($appKey, 60) : 'default';
            if (isset($seenKeys[$appKey])) {
                $appKey .= '_' . ($index + 1);
            }
            $seenKeys[$appKey] = true;

            $templateKey = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($app['product_template_key'] ?? $app['recharge_template_key'] ?? $appKey)));
            if (!in_array($templateKey, $templateKeys, true)) {
                $templateKey = in_array($appKey, $templateKeys, true) ? $appKey : 'default';
            }
            if (!in_array($templateKey, $templateKeys, true)) {
                $templateKey = $templateKeys[0] ?? 'default';
            }

            $paymentRouteId = preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($app['payment_route_id'] ?? '')));
            if (!in_array($paymentRouteId, $paymentRouteIds, true)) {
                $paymentRouteId = $defaultPaymentRouteId;
            }

            $normalized[] = [
                'id' => max(1, (int) ($app['id'] ?? ($index + 1))),
                'app_key' => $appKey,
                'name' => $this->limitText(trim((string) ($app['name'] ?? $app['app_name'] ?? '')) ?: ($appKey === 'default' ? $siteName : $appKey), 60),
                'type' => in_array((string) ($app['type'] ?? ''), ['h5', 'wechat_mp', 'wechat_official', 'quick_app', 'douyin', 'kuaishou', 'native'], true) ? (string) $app['type'] : 'h5',
                'status' => in_array((string) ($app['status'] ?? ''), ['active', 'review', 'paused'], true) ? (string) $app['status'] : 'active',
                'app_id' => $this->limitText(trim((string) ($app['app_id'] ?? '')), 80),
                'app_secret' => $this->limitText(trim((string) ($app['app_secret'] ?? $app['secret'] ?? '')), 120),
                'original_id' => $this->limitText(trim((string) ($app['original_id'] ?? '')), 80),
                'product_template_key' => $templateKey,
                'payment_route_id' => $paymentRouteId,
                'homepage_template' => in_array((string) ($app['homepage_template'] ?? ''), ['mini', 'marketing', 'diy'], true) ? (string) $app['homepage_template'] : 'mini',
                'privacy_url' => trim((string) ($app['privacy_url'] ?? '')),
                'agreement_url' => trim((string) ($app['agreement_url'] ?? '')),
                'callback_url' => trim((string) ($app['callback_url'] ?? '')),
                'recommend_slots' => $this->normalizeAppRecommendSlots((array) ($app['recommend_slots'] ?? [])),
                'task_config' => $this->normalizeAppTaskConfig((array) ($app['task_config'] ?? [])),
                'user_tiers' => $this->normalizeAppUserTiers((array) ($app['user_tiers'] ?? [])),
                'client_config' => $this->normalizeAppClientConfig((array) ($app['client_config'] ?? [])),
                'callback_policy' => $this->normalizeAppCallbackPolicy((array) ($app['callback_policy'] ?? []), trim((string) ($app['callback_url'] ?? ''))),
                'remark' => $this->limitText(trim((string) ($app['remark'] ?? '')), 120),
                'sort' => (int) ($app['sort'] ?? (($index + 1) * 10)),
                'created_at' => trim((string) ($app['created_at'] ?? '')) ?: date('Y-m-d H:i:s'),
                'updated_at' => trim((string) ($app['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            ];
        }

        $hasDefaultApp = false;
        foreach ($normalized as $app) {
            if ((string) ($app['app_key'] ?? '') === 'default') {
                $hasDefaultApp = true;
                break;
            }
        }
        if (!$hasDefaultApp) {
            array_unshift($normalized, [
                'id' => empty($normalized) ? 1 : ((int) max(array_column($normalized, 'id')) + 1),
                'app_key' => 'default',
                'name' => $siteName ?: '精秀短剧',
                'type' => 'h5',
                'status' => 'active',
                'app_id' => '',
                'app_secret' => '',
                'original_id' => '',
                'product_template_key' => in_array('default', $templateKeys, true) ? 'default' : ($templateKeys[0] ?? 'default'),
                'payment_route_id' => $defaultPaymentRouteId,
                'homepage_template' => 'mini',
                'privacy_url' => '',
                'agreement_url' => '',
                'callback_url' => '',
                'recommend_slots' => $this->normalizeAppRecommendSlots([]),
                'task_config' => $this->normalizeAppTaskConfig([]),
                'user_tiers' => $this->normalizeAppUserTiers([]),
                'client_config' => $this->normalizeAppClientConfig([]),
                'callback_policy' => $this->normalizeAppCallbackPolicy([], ''),
                'remark' => '默认 H5 应用，未命中 app_key 时使用',
                'sort' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        usort($normalized, static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (int) ($a['id'] ?? 0)] <=> [(int) ($b['sort'] ?? 0), (int) ($b['id'] ?? 0)]);

        return $normalized;
    }

    private function normalizeAppRecommendSlots(array $slots): array
    {
        $defaults = [
            'home' => ['name' => '首页推荐', 'enabled' => true, 'content_type' => 'drama', 'content_id' => 0, 'link' => '/duanju', 'sort' => 10],
            'comment' => ['name' => '评论入口', 'enabled' => true, 'content_type' => 'drama', 'content_id' => 0, 'link' => '/duanju', 'sort' => 20],
            'favorite' => ['name' => '收藏入口', 'enabled' => true, 'content_type' => 'drama', 'content_id' => 0, 'link' => '/zhuiju', 'sort' => 30],
            'reading' => ['name' => '阅读入口', 'enabled' => true, 'content_type' => 'novel', 'content_id' => 0, 'link' => '/?route=novels', 'sort' => 40],
            'category' => ['name' => '内容分类', 'enabled' => true, 'content_type' => 'mixed', 'content_id' => 0, 'link' => '/duanju', 'sort' => 50],
        ];
        $normalized = [];
        foreach ($defaults as $slotKey => $default) {
            $slot = (array) ($slots[$slotKey] ?? []);
            $contentType = in_array((string) ($slot['content_type'] ?? $default['content_type']), ['drama', 'novel', 'mixed', 'url'], true)
                ? (string) ($slot['content_type'] ?? $default['content_type'])
                : (string) $default['content_type'];
            $normalized[$slotKey] = [
                'name' => $this->limitText(trim((string) ($slot['name'] ?? $default['name'])), 40),
                'enabled' => array_key_exists('enabled', $slot) ? !empty($slot['enabled']) : (bool) $default['enabled'],
                'content_type' => $contentType,
                'content_id' => max(0, (int) ($slot['content_id'] ?? $default['content_id'])),
                'link' => trim((string) ($slot['link'] ?? $default['link'])),
                'sort' => (int) ($slot['sort'] ?? $default['sort']),
            ];
        }

        return $normalized;
    }

    private function normalizeAppTaskConfig(array $tasks): array
    {
        $defaults = [
            'add_desktop' => ['name' => '加桌任务', 'enabled' => true, 'reward_coins' => 30, 'daily_limit' => 1],
            'register' => ['name' => '注册任务', 'enabled' => true, 'reward_coins' => 20, 'daily_limit' => 1],
            'watch' => ['name' => '观看任务', 'enabled' => true, 'reward_coins' => 10, 'daily_limit' => 3],
            'favorite' => ['name' => '收藏任务', 'enabled' => true, 'reward_coins' => 5, 'daily_limit' => 5],
            'share' => ['name' => '分享任务', 'enabled' => false, 'reward_coins' => 10, 'daily_limit' => 3],
        ];
        $normalized = [];
        foreach ($defaults as $taskKey => $default) {
            $task = (array) ($tasks[$taskKey] ?? []);
            $normalized[$taskKey] = [
                'name' => $this->limitText(trim((string) ($task['name'] ?? $default['name'])), 40),
                'enabled' => array_key_exists('enabled', $task) ? !empty($task['enabled']) : (bool) $default['enabled'],
                'reward_coins' => max(0, (int) ($task['reward_coins'] ?? $default['reward_coins'])),
                'daily_limit' => max(0, (int) ($task['daily_limit'] ?? $default['daily_limit'])),
            ];
        }

        return $normalized;
    }

    private function normalizeAppUserTiers(array $tiers): array
    {
        $defaults = [
            'new' => ['name' => '新客', 'enabled' => true, 'min_paid_orders' => 0, 'max_paid_orders' => 0, 'registered_within_days' => 3, 'membership_required' => false, 'tag' => '新客', 'benefit_text' => '首单转化重点人群', 'sort' => 10],
            'unpaid' => ['name' => '未付费', 'enabled' => true, 'min_paid_orders' => 0, 'max_paid_orders' => 0, 'registered_within_days' => 0, 'membership_required' => false, 'tag' => '未付费', 'benefit_text' => '引导充值或激励广告', 'sort' => 20],
            'paid' => ['name' => '已付费', 'enabled' => true, 'min_paid_orders' => 1, 'max_paid_orders' => 0, 'registered_within_days' => 0, 'membership_required' => false, 'tag' => '已付费', 'benefit_text' => '复购和全集解锁', 'sort' => 30],
            'member' => ['name' => '会员', 'enabled' => true, 'min_paid_orders' => 0, 'max_paid_orders' => 0, 'registered_within_days' => 0, 'membership_required' => true, 'tag' => '会员', 'benefit_text' => '会员权益和留存', 'sort' => 40],
        ];
        $normalized = [];
        foreach ($defaults as $tierKey => $default) {
            $tier = (array) ($tiers[$tierKey] ?? []);
            $normalized[$tierKey] = [
                'key' => $tierKey,
                'name' => $this->limitText(trim((string) ($tier['name'] ?? $default['name'])), 40),
                'enabled' => array_key_exists('enabled', $tier) ? !empty($tier['enabled']) : (bool) $default['enabled'],
                'min_paid_orders' => max(0, (int) ($tier['min_paid_orders'] ?? $default['min_paid_orders'])),
                'max_paid_orders' => max(0, (int) ($tier['max_paid_orders'] ?? $default['max_paid_orders'])),
                'registered_within_days' => max(0, (int) ($tier['registered_within_days'] ?? $default['registered_within_days'])),
                'membership_required' => array_key_exists('membership_required', $tier) ? !empty($tier['membership_required']) : (bool) $default['membership_required'],
                'tag' => $this->limitText(trim((string) ($tier['tag'] ?? $default['tag'])), 24),
                'benefit_text' => $this->limitText(trim((string) ($tier['benefit_text'] ?? $default['benefit_text'])), 80),
                'sort' => (int) ($tier['sort'] ?? $default['sort']),
            ];
        }

        uasort($normalized, static fn (array $a, array $b): int => [(int) ($a['sort'] ?? 0), (string) ($a['key'] ?? '')] <=> [(int) ($b['sort'] ?? 0), (string) ($b['key'] ?? '')]);

        return $normalized;
    }

    private function normalizeAppClientConfig(array $config): array
    {
        $theme = (string) ($config['theme'] ?? 'default');
        $reviewMode = (string) ($config['review_mode'] ?? 'normal');

        return [
            'version' => $this->limitText(trim((string) ($config['version'] ?? '1.0.0')), 30),
            'min_version' => $this->limitText(trim((string) ($config['min_version'] ?? '1.0.0')), 30),
            'force_update' => !empty($config['force_update']),
            'update_url' => trim((string) ($config['update_url'] ?? '')),
            'review_mode' => in_array($reviewMode, ['normal', 'review', 'safe'], true) ? $reviewMode : 'normal',
            'theme' => in_array($theme, ['default', 'dark', 'light', 'brand'], true) ? $theme : 'default',
            'customer_service_url' => trim((string) ($config['customer_service_url'] ?? '')),
            'share_title' => $this->limitText(trim((string) ($config['share_title'] ?? '')), 80),
            'share_image' => trim((string) ($config['share_image'] ?? '')),
            'launch_notice' => $this->limitText(trim((string) ($config['launch_notice'] ?? '')), 120),
            'gray_release_percent' => max(0, min(100, (int) ($config['gray_release_percent'] ?? 100))),
            'show_ads' => array_key_exists('show_ads', $config) ? !empty($config['show_ads']) : true,
            'show_rewards' => array_key_exists('show_rewards', $config) ? !empty($config['show_rewards']) : true,
            'show_vip' => array_key_exists('show_vip', $config) ? !empty($config['show_vip']) : true,
            'updated_at' => trim((string) ($config['updated_at'] ?? '')) ?: date('Y-m-d H:i:s'),
        ];
    }

    private function normalizeAppCallbackPolicy(array $policy, string $callbackUrl = ''): array
    {
        $splitEvents = static function (mixed $value, array $fallback): array {
            $items = is_array($value) ? $value : preg_split('/[\s,，|]+/u', (string) $value);
            $events = array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), (array) $items), static fn (string $item): bool => $item !== ''));

            return empty($events) ? $fallback : array_slice(array_values(array_unique($events)), 0, 10);
        };
        $endpoint = trim((string) ($policy['endpoint'] ?? ''));
        if ($endpoint === '' && $callbackUrl !== '') {
            $endpoint = $callbackUrl;
        }

        return [
            'enabled' => !empty($policy['enabled']),
            'use_global_fallback' => array_key_exists('use_global_fallback', $policy) ? !empty($policy['use_global_fallback']) : true,
            'platform' => $this->limitText(trim((string) ($policy['platform'] ?? '')), 40),
            'endpoint' => $endpoint,
            'secret' => trim((string) ($policy['secret'] ?? '')),
            'template_key' => preg_replace('/[^a-zA-Z0-9_-]+/', '', trim((string) ($policy['template_key'] ?? 'custom'))) ?: 'custom',
            'template_name' => $this->limitText(trim((string) ($policy['template_name'] ?? '自定义模板')), 60),
            'field_mapping' => is_array($policy['field_mapping'] ?? null) ? (array) $policy['field_mapping'] : [],
            'auth_config' => $this->normalizeCallbackAuthConfig((array) ($policy['auth_config'] ?? []), (string) ($policy['secret'] ?? '')),
            'add_desktop_events' => $splitEvents($policy['add_desktop_events'] ?? [], ['active']),
            'paid_events' => $splitEvents($policy['paid_events'] ?? [], ['pay']),
            'retry_failed' => array_key_exists('retry_failed', $policy) ? !empty($policy['retry_failed']) : true,
            'fallback_time_match' => array_key_exists('fallback_time_match', $policy) ? !empty($policy['fallback_time_match']) : true,
        ];
    }

    private function promotionContentTargetUrl(string $contentType, int $dramaId, int $episodeId, int $novelId, int $chapterId): string
    {
        if ($contentType === 'novel') {
            if ($novelId > 0 && $chapterId > 0) {
                return '/?route=novel-read&novel_id=' . $novelId . '&chapter_id=' . $chapterId;
            }
            if ($novelId > 0) {
                return '/?route=novel&id=' . $novelId;
            }

            return '/?route=novels';
        }

        if ($dramaId > 0) {
            return '/?route=yulan&id=' . $dramaId . ($episodeId > 0 ? '&episode_id=' . $episodeId : '');
        }

        return '/duanju';
    }

    private function normalizeDate(string $value, string $fallback = ''): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }
        $timestamp = strtotime(str_replace('T', ' ', $value));
        if ($timestamp === false) {
            return $fallback;
        }

        return date('Y-m-d', $timestamp);
    }

    private function normalizeDateTime(string $value, string $fallback = ''): string
    {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }
        $timestamp = strtotime(str_replace('T', ' ', $value));
        if ($timestamp === false) {
            return $fallback;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function normalizeTimeOfDay(string $value, string $fallback = '00:00'): string
    {
        $value = trim($value);
        if (preg_match('/^(\d{1,2}):(\d{1,2})$/', $value, $matches) !== 1) {
            return $fallback;
        }
        $hour = max(0, min(23, (int) $matches[1]));
        $minute = max(0, min(59, (int) $matches[2]));

        return sprintf('%02d:%02d', $hour, $minute);
    }
}
