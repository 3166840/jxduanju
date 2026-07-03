<?php

namespace App\Support;

use PDO;

class DatabaseStorage
{
    private ?PDO $pdo = null;
    private bool $schemaReady = false;
    private array $mysql;
    private string $prefix;

    public function __construct(private array $config)
    {
        $this->mysql = (array) ($config['mysql'] ?? []);
        $this->prefix = preg_replace('/[^a-zA-Z0-9_]+/', '', (string) ($this->mysql['prefix'] ?? 'jx_')) ?: 'jx_';
    }

    public function enabled(): bool
    {
        return strtolower((string) ($this->config['driver'] ?? 'mysql')) === 'mysql';
    }

    public function load(): ?array
    {
        if (!$this->enabled()) {
            return null;
        }

        $statement = $this->pdo()->prepare('SELECT meta_value FROM ' . $this->table('meta') . ' WHERE meta_key = :key LIMIT 1');
        $statement->execute(['key' => 'app_data']);
        $json = $statement->fetchColumn();
        if (!is_string($json) || trim($json) === '') {
            return null;
        }

        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }

    public function save(array $data): void
    {
        if (!$this->enabled()) {
            return;
        }

        $this->ensureSchema();
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new \RuntimeException('数据库数据编码失败，保存已取消。');
        }

        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $statement = $pdo->prepare(
                'INSERT INTO ' . $this->table('meta') . ' (meta_key, meta_value, updated_at)
                 VALUES (:meta_key, :meta_value, :updated_at)
                 ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = VALUES(updated_at)'
            );
            $statement->execute([
                'meta_key' => 'app_data',
                'meta_value' => $json,
                'updated_at' => $now,
            ]);

            $this->rebuildIndexes($data);
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    public function info(): array
    {
        return [
            'driver' => 'mysql',
            'label' => 'MySQL 数据库',
            'database' => (string) ($this->mysql['database'] ?? ''),
            'host' => (string) ($this->mysql['host'] ?? ''),
            'prefix' => $this->prefix,
            'configured' => $this->enabled(),
        ];
    }

    public function pathLabel(): string
    {
        return sprintf(
            'mysql://%s/%s',
            (string) ($this->mysql['host'] ?? '127.0.0.1'),
            (string) ($this->mysql['database'] ?? '')
        );
    }

    public function ensureSchema(): void
    {
        if ($this->schemaReady || !$this->enabled()) {
            return;
        }

        $pdo = $this->pdo();
        foreach ($this->schemaSql() as $sql) {
            $pdo->exec($sql);
        }
        $this->ensureIndexSchema();
        $this->ensureTableComments();

        $this->schemaReady = true;
    }

    public function exportSql(): string
    {
        $this->ensureSchema();
        $pdo = $this->pdo();
        $tables = $this->tableNames();
        $lines = [
            '-- Jingxiu MySQL backup',
            '-- Created at: ' . date('Y-m-d H:i:s'),
            'SET FOREIGN_KEY_CHECKS=0;',
        ];

        foreach ($tables as $table) {
            $quotedTable = $this->quoteIdentifier($table);
            $create = $pdo->query('SHOW CREATE TABLE ' . $quotedTable)->fetch();
            $createSql = (string) ($create['Create Table'] ?? $create['Create View'] ?? '');
            if ($createSql === '') {
                continue;
            }

            $lines[] = '';
            $lines[] = 'DROP TABLE IF EXISTS ' . $quotedTable . ';';
            $lines[] = $createSql . ';';

            $rows = $pdo->query('SELECT * FROM ' . $quotedTable)->fetchAll();
            if (empty($rows)) {
                continue;
            }

            $columns = array_keys($rows[0]);
            $columnSql = implode(', ', array_map($this->quoteIdentifier(...), $columns));
            foreach (array_chunk($rows, 50) as $chunk) {
                $values = [];
                foreach ($chunk as $row) {
                    $values[] = '(' . implode(', ', array_map(
                        static fn (mixed $value): string => $value === null ? 'NULL' : $pdo->quote((string) $value),
                        array_map(static fn (string $column): mixed => $row[$column] ?? null, $columns)
                    )) . ')';
                }
                $lines[] = 'INSERT INTO ' . $quotedTable . ' (' . $columnSql . ') VALUES ' . implode(",\n", $values) . ';';
            }
        }

        $lines[] = '';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';

        return implode("\n", $lines) . "\n";
    }

    public function importSql(string $sql): void
    {
        if (!str_contains($sql, '-- Jingxiu MySQL backup') || !str_contains($sql, 'SET FOREIGN_KEY_CHECKS')) {
            throw new \RuntimeException('SQL 备份格式无效，恢复已取消。');
        }

        $pdo = $this->pdo();
        foreach ($this->splitSqlStatements($sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            $pdo->exec($statement);
        }

        $this->schemaReady = false;
        $this->ensureSchema();
    }

    private function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $host = (string) ($this->mysql['host'] ?? '');
        $port = (int) ($this->mysql['port'] ?? 3306);
        $database = (string) ($this->mysql['database'] ?? '');
        $charset = (string) ($this->mysql['charset'] ?? 'utf8mb4');
        $username = (string) ($this->mysql['username'] ?? '');
        if ($host === '' || $database === '' || $username === '') {
            throw new \RuntimeException('MySQL 未配置完整，请检查 JX_DB_HOST、JX_DB_DATABASE 和 JX_DB_USERNAME。');
        }

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);
        $this->pdo = new PDO($dsn, $username, (string) ($this->mysql['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ]);

        return $this->pdo;
    }

    private function table(string $name): string
    {
        return '`' . $this->rawTableName($name) . '`';
    }

    private function rawTableName(string $name): string
    {
        return $this->prefix . preg_replace('/[^a-zA-Z0-9_]+/', '', $name);
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function tableNames(): array
    {
        $statement = $this->pdo()->prepare('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
        $statement->execute();
        $tables = [];
        foreach ($statement->fetchAll(PDO::FETCH_NUM) as $row) {
            $table = (string) ($row[0] ?? '');
            if ($table !== '' && str_starts_with($table, $this->prefix)) {
                $tables[] = $table;
            }
        }
        sort($tables, SORT_STRING);

        return $tables;
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $quote = null;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($quote === null && $char === '-' && $next === '-') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }

            if ($quote === null && $char === '/' && $next === '*') {
                $i += 2;
                while ($i < $length - 1 && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                    $i++;
                }
                $i++;
                continue;
            }

            $buffer .= $char;

            if ($quote !== null) {
                if ($char === '\\') {
                    if ($i + 1 < $length) {
                        $buffer .= $sql[++$i];
                    }
                    continue;
                }
                if ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($char === '\'' || $char === '"' || $char === '`') {
                $quote = $char;
                continue;
            }

            if ($char === ';') {
                $statements[] = substr($buffer, 0, -1);
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }

    private function ensureIndexSchema(): void
    {
        $this->ensureColumns('orders', [
            'content_type' => 'VARCHAR(20) NOT NULL DEFAULT "drama"',
            'drama_id' => 'INT NOT NULL DEFAULT 0',
            'episode_id' => 'INT NOT NULL DEFAULT 0',
            'novel_id' => 'INT NOT NULL DEFAULT 0',
            'chapter_id' => 'INT NOT NULL DEFAULT 0',
            'promotion_link_id' => 'INT NOT NULL DEFAULT 0',
            'traffic_platform' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'channel_id' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'media_app_id' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'ad_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'creative_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'material_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'app_key' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'product_code' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'product_template_id' => 'INT NOT NULL DEFAULT 0',
            'replacement_rule_count' => 'INT NOT NULL DEFAULT 0',
        ]);
        $this->ensureIndexes('orders', [
            'idx_content' => '(content_type, drama_id, novel_id)',
            'idx_promotion_link' => '(promotion_link_id)',
            'idx_traffic' => '(traffic_platform, ad_id, material_id)',
            'idx_app_product' => '(app_key, product_code)',
        ]);

        $this->ensureColumns('entitlements', [
            'content_type' => 'VARCHAR(20) NOT NULL DEFAULT "drama"',
            'novel_id' => 'INT NOT NULL DEFAULT 0',
            'chapter_id' => 'INT NOT NULL DEFAULT 0',
        ]);
        $this->ensureIndexes('entitlements', [
            'idx_user_novel' => '(user_id, novel_id)',
        ]);

        $this->ensureColumns('rights_repair_logs', [
            'user_id' => 'INT NOT NULL DEFAULT 0',
            'action' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'status' => 'VARCHAR(20) NOT NULL DEFAULT ""',
            'created_at' => 'DATETIME NULL',
        ]);
        $this->ensureIndexes('rights_repair_logs', [
            'idx_user_created' => '(user_id, created_at)',
            'idx_action_status' => '(action, status)',
        ]);
        $this->ensureColumns('redeem_codes', [
            'promotion_code' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'agent_id' => 'INT NOT NULL DEFAULT 0',
            'channel_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
        ]);
        $this->ensureIndexes('redeem_codes', [
            'idx_scope' => '(promotion_code, agent_id, channel_id)',
        ]);
        $this->ensureColumns('redeem_code_logs', [
            'promotion_code' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'agent_id' => 'INT NOT NULL DEFAULT 0',
            'channel_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
        ]);
        $this->ensureIndexes('redeem_code_logs', [
            'idx_scope_created' => '(promotion_code, agent_id, channel_id, created_at)',
        ]);
        $this->ensureColumns('sms_codes', [
            'provider' => 'VARCHAR(40) NOT NULL DEFAULT "mock"',
            'send_status' => 'VARCHAR(32) NOT NULL DEFAULT "mocked"',
        ]);
        $this->ensureIndexes('sms_codes', [
            'idx_provider_status' => '(provider, send_status)',
        ]);
        $this->ensureColumns('email_delivery_logs', [
            'to_email' => 'VARCHAR(120) NOT NULL DEFAULT ""',
            'provider' => 'VARCHAR(40) NOT NULL DEFAULT "mock"',
            'status' => 'VARCHAR(32) NOT NULL DEFAULT ""',
            'created_at' => 'DATETIME NULL',
        ]);
        $this->ensureIndexes('email_delivery_logs', [
            'idx_email_created' => '(to_email, created_at)',
            'idx_status_created' => '(status, created_at)',
        ]);
        $this->ensureColumns('config_change_requests', [
            'config_type' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'target_key' => 'VARCHAR(120) NOT NULL DEFAULT ""',
            'status' => 'VARCHAR(32) NOT NULL DEFAULT "pending"',
            'created_by_admin_id' => 'INT NOT NULL DEFAULT 0',
            'created_at' => 'DATETIME NULL',
            'reviewed_at' => 'DATETIME NULL',
            'applied_at' => 'DATETIME NULL',
        ]);
        $this->ensureIndexes('config_change_requests', [
            'idx_status_created' => '(status, created_at)',
            'idx_type_target' => '(config_type, target_key)',
        ]);
        $this->ensureColumns('config_change_notification_logs', [
            'request_id' => 'INT NOT NULL DEFAULT 0',
            'event' => 'VARCHAR(32) NOT NULL DEFAULT "submitted"',
            'channel' => 'VARCHAR(32) NOT NULL DEFAULT "system"',
            'receiver_admin_id' => 'INT NOT NULL DEFAULT 0',
            'status' => 'VARCHAR(32) NOT NULL DEFAULT "success"',
            'created_at' => 'DATETIME NULL',
        ]);
        $this->ensureIndexes('config_change_notification_logs', [
            'idx_request_event' => '(request_id, event)',
            'idx_receiver_created' => '(receiver_admin_id, created_at)',
            'idx_status_created' => '(status, created_at)',
        ]);
        $this->ensureColumns('app_config_delivery_logs', [
            'fingerprint' => 'VARCHAR(160) NOT NULL DEFAULT ""',
            'app_key' => 'VARCHAR(60) NOT NULL DEFAULT "default"',
            'user_id' => 'INT NOT NULL DEFAULT 0',
            'review_mode' => 'VARCHAR(32) NOT NULL DEFAULT "normal"',
            'gray_hit' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'last_seen_at' => 'DATETIME NULL',
        ]);
        $this->ensureIndexes('app_config_delivery_logs', [
            'idx_fingerprint' => '(fingerprint)',
            'idx_app_seen' => '(app_key, last_seen_at)',
            'idx_user_seen' => '(user_id, last_seen_at)',
            'idx_review_gray' => '(review_mode, gray_hit)',
        ]);
        $this->ensureColumns('activity_participation_logs', [
            'activity_id' => 'INT NOT NULL DEFAULT 0',
            'activity_code' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'event_type' => 'VARCHAR(32) NOT NULL DEFAULT "claim"',
            'experiment_key' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'variant_key' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'user_id' => 'INT NOT NULL DEFAULT 0',
            'app_key' => 'VARCHAR(60) NOT NULL DEFAULT "default"',
            'status' => 'VARCHAR(20) NOT NULL DEFAULT ""',
            'participation_date' => 'DATE NULL',
            'created_at' => 'DATETIME NULL',
        ]);
        $this->ensureIndexes('activity_participation_logs', [
            'idx_activity_user_date' => '(activity_id, user_id, participation_date)',
            'idx_code_user_date' => '(activity_code, user_id, participation_date)',
            'idx_status_created' => '(status, created_at)',
            'idx_event_created' => '(event_type, created_at)',
            'idx_experiment_variant' => '(experiment_key, variant_key)',
        ]);
        $this->ensureColumns('activity_configs', [
            'target_tiers' => 'VARCHAR(120) NOT NULL DEFAULT "all"',
            'experiment_key' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'variant_key' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'traffic_percent' => 'INT NOT NULL DEFAULT 100',
        ]);

        foreach (['dramas', 'novels'] as $table) {
            $this->ensureColumns($table, [
                'group_id' => 'INT NOT NULL DEFAULT 0',
                'audit_status' => 'VARCHAR(32) NOT NULL DEFAULT "draft"',
                'tag_names' => 'VARCHAR(255) NOT NULL DEFAULT ""',
            ]);
            $this->ensureIndexes($table, [
                'idx_group_audit' => '(group_id, audit_status)',
            ]);
        }

        $this->ensureColumns('apps', [
            'app_name' => 'VARCHAR(120) NOT NULL DEFAULT ""',
            'app_type' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'status' => 'VARCHAR(32) NOT NULL DEFAULT ""',
            'product_template_key' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'payment_route_id' => 'VARCHAR(64) NOT NULL DEFAULT ""',
            'recommend_slot_count' => 'INT NOT NULL DEFAULT 0',
            'enabled_task_count' => 'INT NOT NULL DEFAULT 0',
            'user_tier_count' => 'INT NOT NULL DEFAULT 0',
            'client_review_mode' => 'VARCHAR(32) NOT NULL DEFAULT "normal"',
            'client_force_update' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'client_gray_release_percent' => 'INT NOT NULL DEFAULT 100',
            'add_desktop_enabled' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'callback_enabled' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'callback_endpoint_configured' => 'TINYINT(1) NOT NULL DEFAULT 0',
        ]);
        $this->ensureIndexes('apps', [
            'idx_status_type' => '(status, app_type)',
            'idx_template' => '(product_template_key)',
            'idx_payment_route' => '(payment_route_id)',
            'idx_client_review' => '(client_review_mode, client_force_update)',
            'idx_callback' => '(callback_enabled, callback_endpoint_configured)',
        ]);

        $this->ensureColumns('promotion_links', [
            'content_type' => 'VARCHAR(20) NOT NULL DEFAULT "drama"',
            'novel_id' => 'INT NOT NULL DEFAULT 0',
            'chapter_id' => 'INT NOT NULL DEFAULT 0',
            'traffic_platform' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'channel_id' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'media_app_id' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'ad_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'creative_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'material_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
        ]);
        $this->ensureIndexes('promotion_links', [
            'idx_novel' => '(novel_id)',
            'idx_traffic' => '(traffic_platform, ad_id, material_id)',
        ]);

        foreach (['promotion_events', 'promotion_costs', 'callback_logs', 'content_events'] as $table) {
            $this->ensureColumns($table, [
                'traffic_platform' => 'VARCHAR(40) NOT NULL DEFAULT ""',
                'channel_id' => 'VARCHAR(60) NOT NULL DEFAULT ""',
                'media_app_id' => 'VARCHAR(60) NOT NULL DEFAULT ""',
                'ad_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
                'creative_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
                'material_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            ]);
            $this->ensureIndexes($table, [
                'idx_traffic' => '(traffic_platform, ad_id, material_id)',
            ]);
        }
        $this->ensureColumns('callback_logs', [
            'app_key' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'callback_policy_source' => 'VARCHAR(20) NOT NULL DEFAULT "global"',
            'callback_retry_failed' => 'TINYINT(1) NOT NULL DEFAULT 1',
        ]);
        $this->ensureIndexes('callback_logs', [
            'idx_app_status' => '(app_key, status)',
            'idx_callback_source' => '(callback_policy_source, status)',
        ]);
        $this->ensureColumns('feedback_items', [
            'priority' => 'VARCHAR(20) NOT NULL DEFAULT "normal"',
            'app_key' => 'VARCHAR(60) NOT NULL DEFAULT "default"',
            'sla_status' => 'VARCHAR(32) NOT NULL DEFAULT "normal"',
            'due_at' => 'DATETIME NULL',
            'suggested_action' => 'VARCHAR(40) NOT NULL DEFAULT "none"',
            'agent_id' => 'INT NOT NULL DEFAULT 0',
            'traffic_platform' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'channel_id' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'media_app_id' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'ad_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'creative_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'material_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
        ]);
        $this->ensureIndexes('feedback_items', [
            'idx_status_created' => '(status, created_at)',
            'idx_app_status' => '(app_key, status)',
            'idx_sla_status' => '(sla_status, due_at)',
            'idx_suggested_action' => '(suggested_action, status)',
            'idx_user' => '(user_id)',
            'idx_order_no' => '(order_no)',
            'idx_promotion' => '(promotion_link_id)',
            'idx_agent_status' => '(agent_id, status)',
            'idx_traffic' => '(traffic_platform, ad_id, material_id)',
        ]);
        $this->ensureColumns('operation_alert_notifications', [
            'fingerprint' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'alert_type' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'status' => 'VARCHAR(32) NOT NULL DEFAULT "pending"',
            'priority' => 'VARCHAR(20) NOT NULL DEFAULT "normal"',
            'promotion_link_id' => 'INT NOT NULL DEFAULT 0',
            'promotion_code' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'order_no' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'agent_id' => 'INT NOT NULL DEFAULT 0',
            'traffic_platform' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'channel_id' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'media_app_id' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'ad_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'creative_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'material_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'last_seen_at' => 'DATETIME NULL',
            'occurrence_count' => 'INT NOT NULL DEFAULT 1',
        ]);
        $this->ensureIndexes('operation_alert_notifications', [
            'idx_fingerprint' => '(fingerprint)',
            'idx_status_priority' => '(status, priority)',
            'idx_type_status' => '(alert_type, status)',
            'idx_promotion' => '(promotion_link_id)',
            'idx_agent_status' => '(agent_id, status)',
            'idx_traffic' => '(traffic_platform, ad_id, material_id)',
        ]);
        $this->ensureColumns('operation_alert_notification_receivers', [
            'receiver_name' => 'VARCHAR(120) NOT NULL DEFAULT ""',
            'status' => 'VARCHAR(32) NOT NULL DEFAULT "active"',
            'scope_type' => 'VARCHAR(32) NOT NULL DEFAULT "global"',
            'scope_role' => 'VARCHAR(32) NOT NULL DEFAULT ""',
            'agent_id' => 'INT NOT NULL DEFAULT 0',
            'webhook_url' => 'VARCHAR(240) NOT NULL DEFAULT ""',
            'min_priority' => 'VARCHAR(20) NOT NULL DEFAULT "normal"',
        ]);
        $this->ensureIndexes('operation_alert_notification_receivers', [
            'idx_status_scope' => '(status, scope_type, scope_role)',
            'idx_agent_status' => '(agent_id, status)',
        ]);
        $this->ensureColumns('operation_alert_notification_logs', [
            'alert_id' => 'INT NOT NULL DEFAULT 0',
            'receiver_id' => 'INT NOT NULL DEFAULT 0',
            'receiver_name' => 'VARCHAR(120) NOT NULL DEFAULT ""',
            'fingerprint' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'alert_type' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'status' => 'VARCHAR(32) NOT NULL DEFAULT "pending"',
            'channel' => 'VARCHAR(32) NOT NULL DEFAULT "webhook"',
            'endpoint' => 'VARCHAR(240) NOT NULL DEFAULT ""',
            'priority' => 'VARCHAR(20) NOT NULL DEFAULT "normal"',
            'promotion_link_id' => 'INT NOT NULL DEFAULT 0',
            'promotion_code' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'order_no' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'agent_id' => 'INT NOT NULL DEFAULT 0',
            'traffic_platform' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'channel_id' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'media_app_id' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'ad_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'creative_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'material_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'attempt_count' => 'INT NOT NULL DEFAULT 0',
            'last_attempt_at' => 'DATETIME NULL',
            'created_at' => 'DATETIME NULL',
        ]);
        $this->ensureIndexes('operation_alert_notification_logs', [
            'idx_alert_status' => '(alert_id, status)',
            'idx_receiver_status' => '(receiver_id, status)',
            'idx_status_created' => '(status, created_at)',
            'idx_type_status' => '(alert_type, status)',
            'idx_promotion' => '(promotion_link_id)',
            'idx_agent_status' => '(agent_id, status)',
            'idx_traffic' => '(traffic_platform, ad_id, material_id)',
        ]);
        foreach (['landing_pages', 'landing_page_events'] as $table) {
            $this->ensureColumns($table, [
                'traffic_platform' => 'VARCHAR(40) NOT NULL DEFAULT ""',
                'channel_id' => 'VARCHAR(60) NOT NULL DEFAULT ""',
                'media_app_id' => 'VARCHAR(60) NOT NULL DEFAULT ""',
                'ad_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
                'creative_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
                'material_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            ]);
            $this->ensureIndexes($table, [
                'idx_traffic' => '(traffic_platform, ad_id, material_id)',
            ]);
        }
        $this->ensureColumns('ad_platform_configs', [
            'app_key' => 'VARCHAR(60) NOT NULL DEFAULT "default"',
            'provider' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'provider_name' => 'VARCHAR(120) NOT NULL DEFAULT ""',
            'status' => 'VARCHAR(32) NOT NULL DEFAULT "active"',
            'platform_app_id' => 'VARCHAR(120) NOT NULL DEFAULT ""',
            'account_id' => 'VARCHAR(120) NOT NULL DEFAULT ""',
            'media_id' => 'VARCHAR(120) NOT NULL DEFAULT ""',
            'default_ecpm' => 'DECIMAL(12,4) NOT NULL DEFAULT 0',
            'revenue_share_rate' => 'DECIMAL(6,2) NOT NULL DEFAULT 100',
        ]);
        $this->ensureIndexes('ad_platform_configs', [
            'idx_app_provider' => '(app_key, provider)',
            'idx_status_provider' => '(status, provider)',
        ]);
        $this->ensureColumns('ad_delivery_rules', [
            'code' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'rule_name' => 'VARCHAR(120) NOT NULL DEFAULT ""',
            'status' => 'VARCHAR(32) NOT NULL DEFAULT "active"',
            'app_keys' => 'VARCHAR(255) NOT NULL DEFAULT ""',
            'slot_codes' => 'VARCHAR(255) NOT NULL DEFAULT ""',
            'positions' => 'VARCHAR(255) NOT NULL DEFAULT ""',
            'ad_types' => 'VARCHAR(160) NOT NULL DEFAULT ""',
            'providers' => 'VARCHAR(160) NOT NULL DEFAULT ""',
            'membership' => 'VARCHAR(32) NOT NULL DEFAULT "all"',
            'pay_stage' => 'VARCHAR(32) NOT NULL DEFAULT "all"',
            'priority' => 'INT NOT NULL DEFAULT 100',
        ]);
        $this->ensureIndexes('ad_delivery_rules', [
            'idx_status_priority' => '(status, priority)',
            'idx_membership_stage' => '(membership, pay_stage)',
        ]);
        $this->ensureColumns('ad_slots', [
            'code' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'slot_name' => 'VARCHAR(120) NOT NULL DEFAULT ""',
            'ad_type' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'position' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'app_key' => 'VARCHAR(60) NOT NULL DEFAULT "default"',
            'status' => 'VARCHAR(32) NOT NULL DEFAULT "active"',
            'provider' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'unit_id' => 'VARCHAR(120) NOT NULL DEFAULT ""',
            'estimate_ecpm' => 'DECIMAL(12,4) NOT NULL DEFAULT 0',
            'revenue_share_rate' => 'DECIMAL(6,2) NOT NULL DEFAULT 100',
            'reward_coins' => 'INT NOT NULL DEFAULT 0',
            'daily_limit' => 'INT NOT NULL DEFAULT 0',
            'frequency_seconds' => 'INT NOT NULL DEFAULT 0',
        ]);
        $this->ensureIndexes('ad_slots', [
            'idx_app_position' => '(app_key, position)',
            'idx_status_type' => '(status, ad_type)',
            'idx_provider' => '(provider, unit_id)',
        ]);
        $this->ensureColumns('ad_events', [
            'ad_slot_id' => 'INT NOT NULL DEFAULT 0',
            'code' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'event_type' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'user_id' => 'INT NOT NULL DEFAULT 0',
            'app_key' => 'VARCHAR(60) NOT NULL DEFAULT "default"',
            'position' => 'VARCHAR(60) NOT NULL DEFAULT ""',
            'ad_type' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'provider' => 'VARCHAR(40) NOT NULL DEFAULT ""',
            'unit_id' => 'VARCHAR(120) NOT NULL DEFAULT ""',
            'reward_coins' => 'INT NOT NULL DEFAULT 0',
            'revenue' => 'DECIMAL(12,4) NOT NULL DEFAULT 0',
            'ecpm' => 'DECIMAL(12,4) NOT NULL DEFAULT 0',
            'currency' => 'VARCHAR(12) NOT NULL DEFAULT "CNY"',
            'error_code' => 'VARCHAR(80) NOT NULL DEFAULT ""',
        ]);
        $this->ensureIndexes('ad_events', [
            'idx_slot_event_created' => '(ad_slot_id, event_type, created_at)',
            'idx_app_position_created' => '(app_key, position, created_at)',
            'idx_user_created' => '(user_id, created_at)',
        ]);
        $this->ensureColumns('mini_program_configs', [
            'app_key' => 'VARCHAR(60) NOT NULL DEFAULT "default"',
            'config_name' => 'VARCHAR(120) NOT NULL DEFAULT ""',
            'status' => 'VARCHAR(32) NOT NULL DEFAULT "draft"',
            'mp_app_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'original_id' => 'VARCHAR(80) NOT NULL DEFAULT ""',
            'upload_mode' => 'VARCHAR(32) NOT NULL DEFAULT "manual"',
            'content_scope' => 'VARCHAR(32) NOT NULL DEFAULT "all"',
            'last_sync_at' => 'DATETIME NULL',
        ]);
        $this->ensureIndexes('mini_program_configs', [
            'idx_app_key' => '(app_key)',
            'idx_status' => '(status)',
            'idx_mp_app' => '(mp_app_id)',
        ]);
        $this->ensureColumns('mini_program_sync_tasks', [
            'config_id' => 'INT NOT NULL DEFAULT 0',
            'app_key' => 'VARCHAR(60) NOT NULL DEFAULT "default"',
            'content_type' => 'VARCHAR(32) NOT NULL DEFAULT "mixed"',
            'status' => 'VARCHAR(32) NOT NULL DEFAULT "generated"',
            'item_count' => 'INT NOT NULL DEFAULT 0',
            'created_at' => 'DATETIME NULL',
        ]);
        $this->ensureIndexes('mini_program_sync_tasks', [
            'idx_config_created' => '(config_id, created_at)',
            'idx_app_status' => '(app_key, status)',
            'idx_status_created' => '(status, created_at)',
        ]);
    }

    private function ensureColumns(string $table, array $columns): void
    {
        $existing = $this->tableColumns($table);
        foreach ($columns as $name => $definition) {
            if (isset($existing[strtolower((string) $name)])) {
                continue;
            }
            $safeName = preg_replace('/[^a-zA-Z0-9_]+/', '', (string) $name);
            $this->pdo()->exec('ALTER TABLE ' . $this->table($table) . ' ADD COLUMN `' . $safeName . '` ' . $definition);
        }
    }

    private function ensureIndexes(string $table, array $indexes): void
    {
        $existing = $this->tableIndexes($table);
        foreach ($indexes as $name => $definition) {
            if (isset($existing[strtolower((string) $name)])) {
                continue;
            }
            $safeName = preg_replace('/[^a-zA-Z0-9_]+/', '', (string) $name);
            $this->pdo()->exec('ALTER TABLE ' . $this->table($table) . ' ADD KEY `' . $safeName . '` ' . $definition);
        }
    }

    private function ensureTableComments(): void
    {
        foreach ($this->tableComments() as $table => $comment) {
            $this->pdo()->exec(
                'ALTER TABLE ' . $this->table($table)
                . ' COMMENT = ' . $this->pdo()->quote($comment)
            );
        }
    }

    private function tableComments(): array
    {
        return [
            'activity_configs' => '运营活动配置',
            'activity_participation_logs' => '活动参与与领奖日志',
            'ad_delivery_rules' => '广告分层投放规则',
            'ad_events' => '广告请求曝光点击与激励事件',
            'ad_platform_configs' => '广告平台 SDK 参数配置',
            'ad_slots' => '广告位配置',
            'admins' => '后台管理员账号',
            'agents' => '商务代理组织账号',
            'app_config_delivery_logs' => '应用配置下发日志',
            'apps' => '应用配置',
            'callback_logs' => '投放平台回传日志',
            'coin_transactions' => 'K币流水',
            'config_change_notification_logs' => '配置审批通知日志',
            'config_change_requests' => '配置变更审批单',
            'content_events' => '内容播放阅读转化事件',
            'content_groups' => '内容分组',
            'content_tags' => '内容标签',
            'dramas' => '短剧作品',
            'email_delivery_logs' => '邮件发送日志',
            'entitlements' => '用户内容与会员权益',
            'feedback_items' => '用户投诉反馈工单',
            'followed_dramas' => '用户追剧收藏',
            'home_recommendations' => '首页推荐位配置',
            'landing_page_events' => '推广落地页访问点击事件',
            'landing_pages' => '推广落地页',
            'meta' => '系统完整数据快照',
            'mini_program_configs' => '小程序账号与上传配置',
            'mini_program_sync_tasks' => '小程序内容同步任务',
            'novel_chapters' => '小说章节',
            'novels' => '小说作品',
            'operation_alert_notification_logs' => '运营预警外发日志',
            'operation_alert_notification_receivers' => '运营预警接收人',
            'operation_alert_notifications' => '运营预警待办',
            'order_action_logs' => '订单操作日志',
            'orders' => '用户订单',
            'payment_routes' => '支付通道配置',
            'popup_notices' => '弹窗公告配置',
            'promotion_costs' => '推广投放消耗',
            'promotion_events' => '推广访问加桌支付事件',
            'promotion_links' => '推广链接',
            'recharge_products' => '充值商品',
            'redeem_code_logs' => '兑换码使用日志',
            'redeem_codes' => '优惠券兑换码',
            'rights_repair_logs' => '用户权益补发撤销日志',
            'sms_codes' => '短信验证码日志',
            'users' => '用户账号',
            'watch_history' => '用户观看历史',
        ];
    }

    private function tableColumns(string $table): array
    {
        $statement = $this->pdo()->query('SHOW COLUMNS FROM ' . $this->table($table));
        $columns = [];
        foreach ($statement->fetchAll() as $row) {
            $columns[strtolower((string) ($row['Field'] ?? ''))] = true;
        }

        return $columns;
    }

    private function tableIndexes(string $table): array
    {
        $statement = $this->pdo()->query('SHOW INDEX FROM ' . $this->table($table));
        $indexes = [];
        foreach ($statement->fetchAll() as $row) {
            $indexes[strtolower((string) ($row['Key_name'] ?? ''))] = true;
        }

        return $indexes;
    }

    private function schemaSql(): array
    {
        return [
            'CREATE TABLE IF NOT EXISTS ' . $this->table('meta') . ' (
                meta_key VARCHAR(100) NOT NULL PRIMARY KEY,
                meta_value LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('users') . ' (
                id INT NOT NULL PRIMARY KEY,
                phone VARCHAR(32) NOT NULL DEFAULT "",
                nickname VARCHAR(100) NOT NULL DEFAULT "",
                role VARCHAR(32) NOT NULL DEFAULT "",
                membership TINYINT(1) NOT NULL DEFAULT 0,
                coin_balance INT NOT NULL DEFAULT 0,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_phone (phone),
                KEY idx_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('admins') . ' (
                id INT NOT NULL PRIMARY KEY,
                username VARCHAR(80) NOT NULL DEFAULT "",
                nickname VARCHAR(100) NOT NULL DEFAULT "",
                role VARCHAR(32) NOT NULL DEFAULT "super_admin",
                agent_id INT NOT NULL DEFAULT 0,
                status VARCHAR(32) NOT NULL DEFAULT "active",
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_username (username),
                KEY idx_role_agent (role, agent_id),
                KEY idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('dramas') . ' (
                id INT NOT NULL PRIMARY KEY,
                title VARCHAR(160) NOT NULL DEFAULT "",
                category VARCHAR(60) NOT NULL DEFAULT "",
                group_id INT NOT NULL DEFAULT 0,
                audit_status VARCHAR(32) NOT NULL DEFAULT "draft",
                tag_names VARCHAR(255) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "",
                is_hot TINYINT(1) NOT NULL DEFAULT 0,
                is_new TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_category_status (category, status),
                KEY idx_group_audit (group_id, audit_status),
                KEY idx_sort (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('novels') . ' (
                id INT NOT NULL PRIMARY KEY,
                title VARCHAR(160) NOT NULL DEFAULT "",
                author VARCHAR(100) NOT NULL DEFAULT "",
                category VARCHAR(60) NOT NULL DEFAULT "",
                group_id INT NOT NULL DEFAULT 0,
                audit_status VARCHAR(32) NOT NULL DEFAULT "draft",
                tag_names VARCHAR(255) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "",
                is_hot TINYINT(1) NOT NULL DEFAULT 0,
                is_new TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                chapter_count INT NOT NULL DEFAULT 0,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_category_status (category, status),
                KEY idx_group_audit (group_id, audit_status),
                KEY idx_sort (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('content_tags') . ' (
                id INT NOT NULL PRIMARY KEY,
                name VARCHAR(80) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "active",
                sort_order INT NOT NULL DEFAULT 0,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_status_sort (status, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('content_groups') . ' (
                id INT NOT NULL PRIMARY KEY,
                name VARCHAR(120) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "active",
                sort_order INT NOT NULL DEFAULT 0,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_status_sort (status, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('novel_chapters') . ' (
                row_key VARCHAR(120) NOT NULL PRIMARY KEY,
                novel_id INT NOT NULL DEFAULT 0,
                chapter_id INT NOT NULL DEFAULT 0,
                chapter_order INT NOT NULL DEFAULT 0,
                status VARCHAR(32) NOT NULL DEFAULT "",
                is_free TINYINT(1) NOT NULL DEFAULT 0,
                word_count INT NOT NULL DEFAULT 0,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_novel_order (novel_id, chapter_order),
                KEY idx_novel_status (novel_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('orders') . ' (
                order_no VARCHAR(80) NOT NULL PRIMARY KEY,
                user_id INT NOT NULL DEFAULT 0,
                status VARCHAR(40) NOT NULL DEFAULT "",
                order_type VARCHAR(80) NOT NULL DEFAULT "",
                content_type VARCHAR(20) NOT NULL DEFAULT "drama",
                drama_id INT NOT NULL DEFAULT 0,
                episode_id INT NOT NULL DEFAULT 0,
                novel_id INT NOT NULL DEFAULT 0,
                chapter_id INT NOT NULL DEFAULT 0,
                promotion_link_id INT NOT NULL DEFAULT 0,
                traffic_platform VARCHAR(40) NOT NULL DEFAULT "",
                channel_id VARCHAR(60) NOT NULL DEFAULT "",
                media_app_id VARCHAR(60) NOT NULL DEFAULT "",
                app_key VARCHAR(60) NOT NULL DEFAULT "",
                callback_policy_source VARCHAR(20) NOT NULL DEFAULT "global",
                callback_retry_failed TINYINT(1) NOT NULL DEFAULT 1,
                ad_id VARCHAR(80) NOT NULL DEFAULT "",
                creative_id VARCHAR(80) NOT NULL DEFAULT "",
                material_id VARCHAR(80) NOT NULL DEFAULT "",
                product_code VARCHAR(80) NOT NULL DEFAULT "",
                product_template_id INT NOT NULL DEFAULT 0,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                payment_route_id VARCHAR(64) NOT NULL DEFAULT "",
                payment_provider VARCHAR(64) NOT NULL DEFAULT "",
                payment_method VARCHAR(64) NOT NULL DEFAULT "",
                is_test TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                paid_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_user_id (user_id),
                KEY idx_status_created (status, created_at),
                KEY idx_payment_route (payment_route_id),
                KEY idx_content (content_type, drama_id, novel_id),
                KEY idx_promotion_link (promotion_link_id),
                KEY idx_traffic (traffic_platform, ad_id, material_id),
                KEY idx_app_product (app_key, product_code),
                KEY idx_is_test (is_test)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('entitlements') . ' (
                row_key VARCHAR(120) NOT NULL PRIMARY KEY,
                user_id INT NOT NULL DEFAULT 0,
                content_type VARCHAR(20) NOT NULL DEFAULT "drama",
                drama_id INT NOT NULL DEFAULT 0,
                episode_id INT NOT NULL DEFAULT 0,
                novel_id INT NOT NULL DEFAULT 0,
                chapter_id INT NOT NULL DEFAULT 0,
                entitlement_type VARCHAR(60) NOT NULL DEFAULT "",
                order_no VARCHAR(80) NOT NULL DEFAULT "",
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_user_drama (user_id, drama_id),
                KEY idx_user_novel (user_id, novel_id),
                KEY idx_order_no (order_no)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('payment_routes') . ' (
                id VARCHAR(64) NOT NULL PRIMARY KEY,
                provider VARCHAR(64) NOT NULL DEFAULT "",
                payment_method VARCHAR(64) NOT NULL DEFAULT "",
                enabled TINYINT(1) NOT NULL DEFAULT 0,
                is_default TINYINT(1) NOT NULL DEFAULT 0,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_provider_method (provider, payment_method),
                KEY idx_enabled (enabled)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('apps') . ' (
                app_key VARCHAR(80) NOT NULL PRIMARY KEY,
                app_name VARCHAR(120) NOT NULL DEFAULT "",
                app_type VARCHAR(40) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "",
                product_template_key VARCHAR(80) NOT NULL DEFAULT "",
                payment_route_id VARCHAR(64) NOT NULL DEFAULT "",
                recommend_slot_count INT NOT NULL DEFAULT 0,
                enabled_task_count INT NOT NULL DEFAULT 0,
                user_tier_count INT NOT NULL DEFAULT 0,
                client_review_mode VARCHAR(32) NOT NULL DEFAULT "normal",
                client_force_update TINYINT(1) NOT NULL DEFAULT 0,
                client_gray_release_percent INT NOT NULL DEFAULT 100,
                add_desktop_enabled TINYINT(1) NOT NULL DEFAULT 0,
                callback_enabled TINYINT(1) NOT NULL DEFAULT 0,
                callback_endpoint_configured TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_status_type (status, app_type),
                KEY idx_template (product_template_key),
                KEY idx_payment_route (payment_route_id),
                KEY idx_client_review (client_review_mode, client_force_update),
                KEY idx_callback (callback_enabled, callback_endpoint_configured)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('order_action_logs') . ' (
                row_key VARCHAR(120) NOT NULL PRIMARY KEY,
                order_no VARCHAR(80) NOT NULL DEFAULT "",
                action VARCHAR(80) NOT NULL DEFAULT "",
                success TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_order_no (order_no),
                KEY idx_action_created (action, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('coin_transactions') . ' (
                row_key VARCHAR(120) NOT NULL PRIMARY KEY,
                user_id INT NOT NULL DEFAULT 0,
                transaction_type VARCHAR(80) NOT NULL DEFAULT "",
                amount INT NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_user_created (user_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('rights_repair_logs') . ' (
                id INT NOT NULL PRIMARY KEY,
                user_id INT NOT NULL DEFAULT 0,
                action VARCHAR(40) NOT NULL DEFAULT "",
                content_type VARCHAR(20) NOT NULL DEFAULT "",
                status VARCHAR(20) NOT NULL DEFAULT "",
                admin_id INT NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_user_created (user_id, created_at),
                KEY idx_action_status (action, status),
                KEY idx_admin_created (admin_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('redeem_codes') . ' (
                id INT NOT NULL PRIMARY KEY,
                code VARCHAR(40) NOT NULL DEFAULT "",
                code_name VARCHAR(120) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "",
                reward_type VARCHAR(32) NOT NULL DEFAULT "",
                app_key VARCHAR(60) NOT NULL DEFAULT "",
                promotion_code VARCHAR(80) NOT NULL DEFAULT "",
                agent_id INT NOT NULL DEFAULT 0,
                channel_id VARCHAR(80) NOT NULL DEFAULT "",
                total_limit INT NOT NULL DEFAULT 0,
                per_user_limit INT NOT NULL DEFAULT 1,
                started_at DATETIME NULL,
                ended_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY idx_code (code),
                KEY idx_status_app (status, app_key),
                KEY idx_scope (promotion_code, agent_id, channel_id),
                KEY idx_time (started_at, ended_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('redeem_code_logs') . ' (
                row_key VARCHAR(120) NOT NULL PRIMARY KEY,
                code_id INT NOT NULL DEFAULT 0,
                code VARCHAR(40) NOT NULL DEFAULT "",
                user_id INT NOT NULL DEFAULT 0,
                promotion_code VARCHAR(80) NOT NULL DEFAULT "",
                agent_id INT NOT NULL DEFAULT 0,
                channel_id VARCHAR(80) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "",
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_code_user (code, user_id),
                KEY idx_code_created (code_id, created_at),
                KEY idx_user_created (user_id, created_at),
                KEY idx_scope_created (promotion_code, agent_id, channel_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('home_recommendations') . ' (
                id INT NOT NULL PRIMARY KEY,
                app_key VARCHAR(60) NOT NULL DEFAULT "",
                slot VARCHAR(40) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "",
                content_type VARCHAR(32) NOT NULL DEFAULT "",
                content_id INT NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                started_at DATETIME NULL,
                ended_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_app_slot_status (app_key, slot, status),
                KEY idx_time_sort (started_at, ended_at, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('popup_notices') . ' (
                id INT NOT NULL PRIMARY KEY,
                app_key VARCHAR(60) NOT NULL DEFAULT "",
                trigger_name VARCHAR(40) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "",
                priority INT NOT NULL DEFAULT 100,
                started_at DATETIME NULL,
                ended_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_app_trigger_status (app_key, trigger_name, status),
                KEY idx_time_priority (started_at, ended_at, priority)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('activity_configs') . ' (
                id INT NOT NULL PRIMARY KEY,
                code VARCHAR(60) NOT NULL DEFAULT "",
                app_key VARCHAR(60) NOT NULL DEFAULT "",
                activity_type VARCHAR(40) NOT NULL DEFAULT "",
                target_tiers VARCHAR(120) NOT NULL DEFAULT "all",
                experiment_key VARCHAR(60) NOT NULL DEFAULT "",
                variant_key VARCHAR(40) NOT NULL DEFAULT "",
                traffic_percent INT NOT NULL DEFAULT 100,
                status VARCHAR(32) NOT NULL DEFAULT "",
                sort_order INT NOT NULL DEFAULT 0,
                started_at DATETIME NULL,
                ended_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY idx_code (code),
                KEY idx_app_type_status (app_key, activity_type, status),
                KEY idx_time_sort (started_at, ended_at, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('activity_participation_logs') . ' (
                id INT NOT NULL PRIMARY KEY,
                activity_id INT NOT NULL DEFAULT 0,
                activity_code VARCHAR(60) NOT NULL DEFAULT "",
                event_type VARCHAR(32) NOT NULL DEFAULT "claim",
                experiment_key VARCHAR(60) NOT NULL DEFAULT "",
                variant_key VARCHAR(40) NOT NULL DEFAULT "",
                user_id INT NOT NULL DEFAULT 0,
                app_key VARCHAR(60) NOT NULL DEFAULT "default",
                status VARCHAR(20) NOT NULL DEFAULT "",
                reward_type VARCHAR(32) NOT NULL DEFAULT "",
                coin_amount INT NOT NULL DEFAULT 0,
                vip_days INT NOT NULL DEFAULT 0,
                participation_date DATE NULL,
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_activity_user_date (activity_id, user_id, participation_date),
                KEY idx_code_user_date (activity_code, user_id, participation_date),
                KEY idx_status_created (status, created_at),
                KEY idx_event_created (event_type, created_at),
                KEY idx_experiment_variant (experiment_key, variant_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('watch_history') . ' (
                row_key VARCHAR(120) NOT NULL PRIMARY KEY,
                user_id INT NOT NULL DEFAULT 0,
                drama_id INT NOT NULL DEFAULT 0,
                episode_id INT NOT NULL DEFAULT 0,
                updated_time DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_user_updated (user_id, updated_time),
                KEY idx_drama (drama_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('followed_dramas') . ' (
                row_key VARCHAR(120) NOT NULL PRIMARY KEY,
                user_id INT NOT NULL DEFAULT 0,
                drama_id INT NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_user_drama (user_id, drama_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('sms_codes') . ' (
                row_key VARCHAR(120) NOT NULL PRIMARY KEY,
                phone VARCHAR(32) NOT NULL DEFAULT "",
                code VARCHAR(12) NOT NULL DEFAULT "",
                provider VARCHAR(40) NOT NULL DEFAULT "mock",
                send_status VARCHAR(32) NOT NULL DEFAULT "mocked",
                created_at DATETIME NULL,
                expires_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_phone_created (phone, created_at),
                KEY idx_provider_status (provider, send_status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('email_delivery_logs') . ' (
                id INT NOT NULL PRIMARY KEY,
                to_email VARCHAR(120) NOT NULL DEFAULT "",
                provider VARCHAR(40) NOT NULL DEFAULT "mock",
                status VARCHAR(32) NOT NULL DEFAULT "",
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_email_created (to_email, created_at),
                KEY idx_status_created (status, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('config_change_requests') . ' (
                id INT NOT NULL PRIMARY KEY,
                config_type VARCHAR(40) NOT NULL DEFAULT "",
                target_key VARCHAR(120) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "pending",
                created_by_admin_id INT NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                reviewed_at DATETIME NULL,
                applied_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_status_created (status, created_at),
                KEY idx_type_target (config_type, target_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('config_change_notification_logs') . ' (
                id INT NOT NULL PRIMARY KEY,
                request_id INT NOT NULL DEFAULT 0,
                event VARCHAR(32) NOT NULL DEFAULT "submitted",
                channel VARCHAR(32) NOT NULL DEFAULT "system",
                receiver_admin_id INT NOT NULL DEFAULT 0,
                status VARCHAR(32) NOT NULL DEFAULT "success",
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_request_event (request_id, event),
                KEY idx_receiver_created (receiver_admin_id, created_at),
                KEY idx_status_created (status, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('app_config_delivery_logs') . ' (
                id INT NOT NULL PRIMARY KEY,
                fingerprint VARCHAR(160) NOT NULL DEFAULT "",
                app_key VARCHAR(60) NOT NULL DEFAULT "default",
                user_id INT NOT NULL DEFAULT 0,
                review_mode VARCHAR(32) NOT NULL DEFAULT "normal",
                gray_hit TINYINT(1) NOT NULL DEFAULT 0,
                last_seen_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_fingerprint (fingerprint),
                KEY idx_app_seen (app_key, last_seen_at),
                KEY idx_user_seen (user_id, last_seen_at),
                KEY idx_review_gray (review_mode, gray_hit)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('promotion_links') . ' (
                id INT NOT NULL PRIMARY KEY,
                code VARCHAR(80) NOT NULL DEFAULT "",
                content_type VARCHAR(20) NOT NULL DEFAULT "drama",
                drama_id INT NOT NULL DEFAULT 0,
                episode_id INT NOT NULL DEFAULT 0,
                novel_id INT NOT NULL DEFAULT 0,
                chapter_id INT NOT NULL DEFAULT 0,
                agent_id INT NOT NULL DEFAULT 0,
                status VARCHAR(32) NOT NULL DEFAULT "",
                traffic_platform VARCHAR(40) NOT NULL DEFAULT "",
                channel_id VARCHAR(60) NOT NULL DEFAULT "",
                media_app_id VARCHAR(60) NOT NULL DEFAULT "",
                app_key VARCHAR(60) NOT NULL DEFAULT "",
                callback_policy_source VARCHAR(20) NOT NULL DEFAULT "global",
                callback_retry_failed TINYINT(1) NOT NULL DEFAULT 1,
                ad_id VARCHAR(80) NOT NULL DEFAULT "",
                creative_id VARCHAR(80) NOT NULL DEFAULT "",
                material_id VARCHAR(80) NOT NULL DEFAULT "",
                replacement_rule_count INT NOT NULL DEFAULT 0,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY idx_code (code),
                KEY idx_drama (drama_id),
                KEY idx_novel (novel_id),
                KEY idx_agent (agent_id),
                KEY idx_traffic (traffic_platform, ad_id, material_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('promotion_events') . ' (
                row_key VARCHAR(120) NOT NULL PRIMARY KEY,
                promotion_link_id INT NOT NULL DEFAULT 0,
                code VARCHAR(80) NOT NULL DEFAULT "",
                event_type VARCHAR(40) NOT NULL DEFAULT "",
                user_id INT NOT NULL DEFAULT 0,
                order_no VARCHAR(80) NOT NULL DEFAULT "",
                amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                traffic_platform VARCHAR(40) NOT NULL DEFAULT "",
                channel_id VARCHAR(60) NOT NULL DEFAULT "",
                media_app_id VARCHAR(60) NOT NULL DEFAULT "",
                app_key VARCHAR(60) NOT NULL DEFAULT "",
                callback_policy_source VARCHAR(20) NOT NULL DEFAULT "global",
                callback_retry_failed TINYINT(1) NOT NULL DEFAULT 1,
                ad_id VARCHAR(80) NOT NULL DEFAULT "",
                creative_id VARCHAR(80) NOT NULL DEFAULT "",
                material_id VARCHAR(80) NOT NULL DEFAULT "",
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_link_event_created (promotion_link_id, event_type, created_at),
                KEY idx_order_no (order_no),
                KEY idx_traffic (traffic_platform, ad_id, material_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('promotion_costs') . ' (
                id INT NOT NULL PRIMARY KEY,
                cost_date DATE NULL,
                promotion_link_id INT NOT NULL DEFAULT 0,
                agent_id INT NOT NULL DEFAULT 0,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                traffic_platform VARCHAR(40) NOT NULL DEFAULT "",
                channel_id VARCHAR(60) NOT NULL DEFAULT "",
                media_app_id VARCHAR(60) NOT NULL DEFAULT "",
                ad_id VARCHAR(80) NOT NULL DEFAULT "",
                creative_id VARCHAR(80) NOT NULL DEFAULT "",
                material_id VARCHAR(80) NOT NULL DEFAULT "",
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_date_link (cost_date, promotion_link_id),
                KEY idx_agent_date (agent_id, cost_date),
                KEY idx_traffic (traffic_platform, ad_id, material_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('callback_logs') . ' (
                id INT NOT NULL PRIMARY KEY,
                event_type VARCHAR(40) NOT NULL DEFAULT "",
                platform_event VARCHAR(80) NOT NULL DEFAULT "",
                promotion_link_id INT NOT NULL DEFAULT 0,
                user_id INT NOT NULL DEFAULT 0,
                order_no VARCHAR(80) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "",
                traffic_platform VARCHAR(40) NOT NULL DEFAULT "",
                channel_id VARCHAR(60) NOT NULL DEFAULT "",
                media_app_id VARCHAR(60) NOT NULL DEFAULT "",
                app_key VARCHAR(60) NOT NULL DEFAULT "",
                callback_policy_source VARCHAR(20) NOT NULL DEFAULT "global",
                callback_retry_failed TINYINT(1) NOT NULL DEFAULT 1,
                ad_id VARCHAR(80) NOT NULL DEFAULT "",
                creative_id VARCHAR(80) NOT NULL DEFAULT "",
                material_id VARCHAR(80) NOT NULL DEFAULT "",
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_status_created (status, created_at),
                KEY idx_event_created (event_type, created_at),
                KEY idx_order_no (order_no),
                KEY idx_app_status (app_key, status),
                KEY idx_callback_source (callback_policy_source, status),
                KEY idx_traffic (traffic_platform, ad_id, material_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('feedback_items') . ' (
                id INT NOT NULL PRIMARY KEY,
                feedback_type VARCHAR(40) NOT NULL DEFAULT "feedback",
                status VARCHAR(32) NOT NULL DEFAULT "pending",
                priority VARCHAR(20) NOT NULL DEFAULT "normal",
                app_key VARCHAR(60) NOT NULL DEFAULT "default",
                user_id INT NOT NULL DEFAULT 0,
                order_no VARCHAR(80) NOT NULL DEFAULT "",
                content_type VARCHAR(20) NOT NULL DEFAULT "",
                drama_id INT NOT NULL DEFAULT 0,
                episode_id INT NOT NULL DEFAULT 0,
                novel_id INT NOT NULL DEFAULT 0,
                chapter_id INT NOT NULL DEFAULT 0,
                promotion_link_id INT NOT NULL DEFAULT 0,
                promotion_code VARCHAR(80) NOT NULL DEFAULT "",
                agent_id INT NOT NULL DEFAULT 0,
                traffic_platform VARCHAR(40) NOT NULL DEFAULT "",
                channel_id VARCHAR(60) NOT NULL DEFAULT "",
                media_app_id VARCHAR(60) NOT NULL DEFAULT "",
                ad_id VARCHAR(80) NOT NULL DEFAULT "",
                creative_id VARCHAR(80) NOT NULL DEFAULT "",
                material_id VARCHAR(80) NOT NULL DEFAULT "",
                sla_status VARCHAR(32) NOT NULL DEFAULT "normal",
                due_at DATETIME NULL,
                suggested_action VARCHAR(40) NOT NULL DEFAULT "none",
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_status_created (status, created_at),
                KEY idx_app_status (app_key, status),
                KEY idx_sla_status (sla_status, due_at),
                KEY idx_suggested_action (suggested_action, status),
                KEY idx_user (user_id),
                KEY idx_order_no (order_no),
                KEY idx_promotion (promotion_link_id),
                KEY idx_agent_status (agent_id, status),
                KEY idx_traffic (traffic_platform, ad_id, material_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('operation_alert_notifications') . ' (
                id INT NOT NULL PRIMARY KEY,
                fingerprint VARCHAR(80) NOT NULL DEFAULT "",
                alert_type VARCHAR(40) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "pending",
                priority VARCHAR(20) NOT NULL DEFAULT "normal",
                promotion_link_id INT NOT NULL DEFAULT 0,
                promotion_code VARCHAR(80) NOT NULL DEFAULT "",
                order_no VARCHAR(80) NOT NULL DEFAULT "",
                agent_id INT NOT NULL DEFAULT 0,
                traffic_platform VARCHAR(40) NOT NULL DEFAULT "",
                channel_id VARCHAR(60) NOT NULL DEFAULT "",
                media_app_id VARCHAR(60) NOT NULL DEFAULT "",
                ad_id VARCHAR(80) NOT NULL DEFAULT "",
                creative_id VARCHAR(80) NOT NULL DEFAULT "",
                material_id VARCHAR(80) NOT NULL DEFAULT "",
                last_seen_at DATETIME NULL,
                occurrence_count INT NOT NULL DEFAULT 1,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY idx_fingerprint (fingerprint),
                KEY idx_status_priority (status, priority),
                KEY idx_type_status (alert_type, status),
                KEY idx_promotion (promotion_link_id),
                KEY idx_agent_status (agent_id, status),
                KEY idx_traffic (traffic_platform, ad_id, material_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('operation_alert_notification_receivers') . ' (
                id INT NOT NULL PRIMARY KEY,
                receiver_name VARCHAR(120) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "active",
                scope_type VARCHAR(32) NOT NULL DEFAULT "global",
                scope_role VARCHAR(32) NOT NULL DEFAULT "",
                agent_id INT NOT NULL DEFAULT 0,
                webhook_url VARCHAR(240) NOT NULL DEFAULT "",
                min_priority VARCHAR(20) NOT NULL DEFAULT "normal",
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_status_scope (status, scope_type, scope_role),
                KEY idx_agent_status (agent_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('operation_alert_notification_logs') . ' (
                id INT NOT NULL PRIMARY KEY,
                alert_id INT NOT NULL DEFAULT 0,
                receiver_id INT NOT NULL DEFAULT 0,
                receiver_name VARCHAR(120) NOT NULL DEFAULT "",
                fingerprint VARCHAR(80) NOT NULL DEFAULT "",
                alert_type VARCHAR(40) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "pending",
                channel VARCHAR(32) NOT NULL DEFAULT "webhook",
                endpoint VARCHAR(240) NOT NULL DEFAULT "",
                priority VARCHAR(20) NOT NULL DEFAULT "normal",
                promotion_link_id INT NOT NULL DEFAULT 0,
                promotion_code VARCHAR(80) NOT NULL DEFAULT "",
                order_no VARCHAR(80) NOT NULL DEFAULT "",
                agent_id INT NOT NULL DEFAULT 0,
                traffic_platform VARCHAR(40) NOT NULL DEFAULT "",
                channel_id VARCHAR(60) NOT NULL DEFAULT "",
                media_app_id VARCHAR(60) NOT NULL DEFAULT "",
                ad_id VARCHAR(80) NOT NULL DEFAULT "",
                creative_id VARCHAR(80) NOT NULL DEFAULT "",
                material_id VARCHAR(80) NOT NULL DEFAULT "",
                attempt_count INT NOT NULL DEFAULT 0,
                last_attempt_at DATETIME NULL,
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_alert_status (alert_id, status),
                KEY idx_receiver_status (receiver_id, status),
                KEY idx_status_created (status, created_at),
                KEY idx_type_status (alert_type, status),
                KEY idx_promotion (promotion_link_id),
                KEY idx_agent_status (agent_id, status),
                KEY idx_traffic (traffic_platform, ad_id, material_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('landing_pages') . ' (
                id INT NOT NULL PRIMARY KEY,
                slug VARCHAR(100) NOT NULL DEFAULT "",
                page_name VARCHAR(120) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "active",
                template VARCHAR(32) NOT NULL DEFAULT "drama",
                content_type VARCHAR(20) NOT NULL DEFAULT "drama",
                drama_id INT NOT NULL DEFAULT 0,
                episode_id INT NOT NULL DEFAULT 0,
                novel_id INT NOT NULL DEFAULT 0,
                chapter_id INT NOT NULL DEFAULT 0,
                promotion_link_id INT NOT NULL DEFAULT 0,
                promotion_code VARCHAR(80) NOT NULL DEFAULT "",
                agent_id INT NOT NULL DEFAULT 0,
                app_key VARCHAR(60) NOT NULL DEFAULT "",
                traffic_platform VARCHAR(40) NOT NULL DEFAULT "",
                channel_id VARCHAR(60) NOT NULL DEFAULT "",
                media_app_id VARCHAR(60) NOT NULL DEFAULT "",
                ad_id VARCHAR(80) NOT NULL DEFAULT "",
                creative_id VARCHAR(80) NOT NULL DEFAULT "",
                material_id VARCHAR(80) NOT NULL DEFAULT "",
                views INT NOT NULL DEFAULT 0,
                clicks INT NOT NULL DEFAULT 0,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY idx_slug (slug),
                KEY idx_status (status),
                KEY idx_content (content_type, drama_id, novel_id),
                KEY idx_promotion (promotion_link_id),
                KEY idx_agent_status (agent_id, status),
                KEY idx_traffic (traffic_platform, ad_id, material_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('landing_page_events') . ' (
                row_key VARCHAR(120) NOT NULL PRIMARY KEY,
                landing_page_id INT NOT NULL DEFAULT 0,
                slug VARCHAR(100) NOT NULL DEFAULT "",
                event_type VARCHAR(32) NOT NULL DEFAULT "view",
                user_id INT NOT NULL DEFAULT 0,
                promotion_link_id INT NOT NULL DEFAULT 0,
                promotion_code VARCHAR(80) NOT NULL DEFAULT "",
                agent_id INT NOT NULL DEFAULT 0,
                traffic_platform VARCHAR(40) NOT NULL DEFAULT "",
                channel_id VARCHAR(60) NOT NULL DEFAULT "",
                media_app_id VARCHAR(60) NOT NULL DEFAULT "",
                ad_id VARCHAR(80) NOT NULL DEFAULT "",
                creative_id VARCHAR(80) NOT NULL DEFAULT "",
                material_id VARCHAR(80) NOT NULL DEFAULT "",
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_page_event_created (landing_page_id, event_type, created_at),
                KEY idx_promotion (promotion_link_id),
                KEY idx_agent_created (agent_id, created_at),
                KEY idx_traffic (traffic_platform, ad_id, material_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('ad_platform_configs') . ' (
                id INT NOT NULL PRIMARY KEY,
                app_key VARCHAR(60) NOT NULL DEFAULT "default",
                provider VARCHAR(40) NOT NULL DEFAULT "",
                provider_name VARCHAR(120) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "active",
                platform_app_id VARCHAR(120) NOT NULL DEFAULT "",
                account_id VARCHAR(120) NOT NULL DEFAULT "",
                media_id VARCHAR(120) NOT NULL DEFAULT "",
                default_ecpm DECIMAL(12,4) NOT NULL DEFAULT 0,
                revenue_share_rate DECIMAL(6,2) NOT NULL DEFAULT 100,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY idx_app_provider (app_key, provider),
                KEY idx_status_provider (status, provider)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('ad_delivery_rules') . ' (
                id INT NOT NULL PRIMARY KEY,
                code VARCHAR(80) NOT NULL DEFAULT "",
                rule_name VARCHAR(120) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "active",
                app_keys VARCHAR(255) NOT NULL DEFAULT "",
                slot_codes VARCHAR(255) NOT NULL DEFAULT "",
                positions VARCHAR(255) NOT NULL DEFAULT "",
                ad_types VARCHAR(160) NOT NULL DEFAULT "",
                providers VARCHAR(160) NOT NULL DEFAULT "",
                membership VARCHAR(32) NOT NULL DEFAULT "all",
                pay_stage VARCHAR(32) NOT NULL DEFAULT "all",
                priority INT NOT NULL DEFAULT 100,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY idx_code (code),
                KEY idx_status_priority (status, priority),
                KEY idx_membership_stage (membership, pay_stage)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('ad_slots') . ' (
                id INT NOT NULL PRIMARY KEY,
                code VARCHAR(80) NOT NULL DEFAULT "",
                slot_name VARCHAR(120) NOT NULL DEFAULT "",
                ad_type VARCHAR(40) NOT NULL DEFAULT "",
                position VARCHAR(60) NOT NULL DEFAULT "",
                app_key VARCHAR(60) NOT NULL DEFAULT "default",
                status VARCHAR(32) NOT NULL DEFAULT "active",
                provider VARCHAR(40) NOT NULL DEFAULT "",
                unit_id VARCHAR(120) NOT NULL DEFAULT "",
                estimate_ecpm DECIMAL(12,4) NOT NULL DEFAULT 0,
                revenue_share_rate DECIMAL(6,2) NOT NULL DEFAULT 100,
                reward_coins INT NOT NULL DEFAULT 0,
                daily_limit INT NOT NULL DEFAULT 0,
                frequency_seconds INT NOT NULL DEFAULT 0,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY idx_code (code),
                KEY idx_app_position (app_key, position),
                KEY idx_status_type (status, ad_type),
                KEY idx_provider (provider, unit_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('ad_events') . ' (
                row_key VARCHAR(120) NOT NULL PRIMARY KEY,
                ad_slot_id INT NOT NULL DEFAULT 0,
                code VARCHAR(80) NOT NULL DEFAULT "",
                event_type VARCHAR(40) NOT NULL DEFAULT "",
                user_id INT NOT NULL DEFAULT 0,
                app_key VARCHAR(60) NOT NULL DEFAULT "default",
                position VARCHAR(60) NOT NULL DEFAULT "",
                ad_type VARCHAR(40) NOT NULL DEFAULT "",
                provider VARCHAR(40) NOT NULL DEFAULT "",
                unit_id VARCHAR(120) NOT NULL DEFAULT "",
                reward_coins INT NOT NULL DEFAULT 0,
                revenue DECIMAL(12,4) NOT NULL DEFAULT 0,
                ecpm DECIMAL(12,4) NOT NULL DEFAULT 0,
                currency VARCHAR(12) NOT NULL DEFAULT "CNY",
                error_code VARCHAR(80) NOT NULL DEFAULT "",
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_slot_event_created (ad_slot_id, event_type, created_at),
                KEY idx_app_position_created (app_key, position, created_at),
                KEY idx_user_created (user_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('mini_program_configs') . ' (
                id INT NOT NULL PRIMARY KEY,
                app_key VARCHAR(60) NOT NULL DEFAULT "default",
                config_name VARCHAR(120) NOT NULL DEFAULT "",
                status VARCHAR(32) NOT NULL DEFAULT "draft",
                mp_app_id VARCHAR(80) NOT NULL DEFAULT "",
                original_id VARCHAR(80) NOT NULL DEFAULT "",
                upload_mode VARCHAR(32) NOT NULL DEFAULT "manual",
                content_scope VARCHAR(32) NOT NULL DEFAULT "all",
                last_sync_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY idx_app_key (app_key),
                KEY idx_status (status),
                KEY idx_mp_app (mp_app_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('mini_program_sync_tasks') . ' (
                id INT NOT NULL PRIMARY KEY,
                config_id INT NOT NULL DEFAULT 0,
                app_key VARCHAR(60) NOT NULL DEFAULT "default",
                content_type VARCHAR(32) NOT NULL DEFAULT "mixed",
                status VARCHAR(32) NOT NULL DEFAULT "generated",
                item_count INT NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_config_created (config_id, created_at),
                KEY idx_app_status (app_key, status),
                KEY idx_status_created (status, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('content_events') . ' (
                row_key VARCHAR(120) NOT NULL PRIMARY KEY,
                content_type VARCHAR(20) NOT NULL DEFAULT "drama",
                event_type VARCHAR(40) NOT NULL DEFAULT "",
                drama_id INT NOT NULL DEFAULT 0,
                episode_id INT NOT NULL DEFAULT 0,
                novel_id INT NOT NULL DEFAULT 0,
                chapter_id INT NOT NULL DEFAULT 0,
                user_id INT NOT NULL DEFAULT 0,
                order_no VARCHAR(80) NOT NULL DEFAULT "",
                promotion_link_id INT NOT NULL DEFAULT 0,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                traffic_platform VARCHAR(40) NOT NULL DEFAULT "",
                channel_id VARCHAR(60) NOT NULL DEFAULT "",
                media_app_id VARCHAR(60) NOT NULL DEFAULT "",
                ad_id VARCHAR(80) NOT NULL DEFAULT "",
                creative_id VARCHAR(80) NOT NULL DEFAULT "",
                material_id VARCHAR(80) NOT NULL DEFAULT "",
                created_at DATETIME NULL,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_content_event_created (content_type, event_type, created_at),
                KEY idx_drama_created (drama_id, event_type, created_at),
                KEY idx_novel_created (novel_id, event_type, created_at),
                KEY idx_promotion_created (promotion_link_id, created_at),
                KEY idx_order_no (order_no),
                KEY idx_traffic (traffic_platform, ad_id, material_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('agents') . ' (
                id INT NOT NULL PRIMARY KEY,
                name VARCHAR(100) NOT NULL DEFAULT "",
                role VARCHAR(40) NOT NULL DEFAULT "",
                parent_id INT NOT NULL DEFAULT 0,
                status VARCHAR(32) NOT NULL DEFAULT "",
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_role_parent (role, parent_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $this->table('recharge_products') . ' (
                code VARCHAR(80) NOT NULL PRIMARY KEY,
                product_type VARCHAR(40) NOT NULL DEFAULT "",
                price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                enabled TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                payload LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL,
                KEY idx_type_enabled (product_type, enabled),
                KEY idx_sort (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        ];
    }

    private function rebuildIndexes(array $data): void
    {
        $pdo = $this->pdo();
        $now = date('Y-m-d H:i:s');
        foreach ([
            'admins',
            'users',
            'content_tags',
            'content_groups',
            'dramas',
            'novels',
            'novel_chapters',
            'orders',
            'entitlements',
            'payment_routes',
            'apps',
            'order_action_logs',
            'coin_transactions',
            'rights_repair_logs',
            'redeem_codes',
            'redeem_code_logs',
            'home_recommendations',
            'popup_notices',
            'activity_configs',
            'activity_participation_logs',
            'watch_history',
            'followed_dramas',
            'sms_codes',
            'email_delivery_logs',
            'config_change_requests',
            'config_change_notification_logs',
            'app_config_delivery_logs',
            'promotion_links',
            'promotion_events',
            'promotion_costs',
            'callback_logs',
            'feedback_items',
            'operation_alert_notifications',
            'operation_alert_notification_receivers',
            'operation_alert_notification_logs',
            'landing_pages',
            'landing_page_events',
            'ad_platform_configs',
            'ad_delivery_rules',
            'ad_slots',
            'ad_events',
            'mini_program_configs',
            'mini_program_sync_tasks',
            'content_events',
            'agents',
            'recharge_products',
        ] as $table) {
            $pdo->exec('DELETE FROM ' . $this->table($table));
        }

        $this->insertAdmins((array) ($data['admins'] ?? []), $now);
        $this->insertUsers((array) ($data['users'] ?? []), $now);
        $this->insertContentTags((array) ($data['content_tags'] ?? []), $now);
        $this->insertContentGroups((array) ($data['content_groups'] ?? []), $now);
        $this->insertDramas((array) ($data['dramas'] ?? []), $now);
        $this->insertNovels((array) ($data['novels'] ?? []), $now);
        $this->insertOrders((array) ($data['orders'] ?? []), $now);
        $this->insertEntitlements((array) ($data['entitlements'] ?? []), $now);
        $this->insertPaymentRoutes((array) ($data['payment_config']['routes'] ?? []), $now);
        $this->insertApps((array) ($data['apps'] ?? []), $now);
        $this->insertOrderActionLogs((array) ($data['order_action_logs'] ?? []), $now);
        $this->insertCoinTransactions((array) ($data['coin_transactions'] ?? []), $now);
        $this->insertRightsRepairLogs((array) ($data['rights_repair_logs'] ?? []), $now);
        $this->insertRedeemCodes((array) ($data['redeem_codes'] ?? []), $now);
        $this->insertRedeemCodeLogs((array) ($data['redeem_code_logs'] ?? []), $now);
        $this->insertHomeRecommendations((array) ($data['home_recommendations'] ?? []), $now);
        $this->insertPopupNotices((array) ($data['popup_notices'] ?? []), $now);
        $this->insertActivityConfigs((array) ($data['activity_configs'] ?? []), $now);
        $this->insertActivityParticipationLogs((array) ($data['activity_participation_logs'] ?? []), $now);
        $this->insertWatchHistory((array) ($data['watch_history'] ?? []), $now);
        $this->insertFollowedDramas((array) ($data['followed_dramas'] ?? []), $now);
        $this->insertSmsCodes((array) ($data['sms_codes'] ?? []), $now);
        $this->insertEmailDeliveryLogs((array) ($data['email_delivery_logs'] ?? []), $now);
        $this->insertConfigChangeRequests((array) ($data['config_change_requests'] ?? []), $now);
        $this->insertConfigChangeNotificationLogs((array) ($data['config_change_notification_logs'] ?? []), $now);
        $this->insertAppConfigDeliveryLogs((array) ($data['app_config_delivery_logs'] ?? []), $now);
        $this->insertPromotionLinks((array) ($data['promotion_links'] ?? []), $now);
        $this->insertPromotionEvents((array) ($data['promotion_events'] ?? []), $now);
        $this->insertPromotionCosts((array) ($data['promotion_costs'] ?? []), $now);
        $this->insertCallbackLogs((array) ($data['callback_logs'] ?? []), $now);
        $this->insertFeedbackItems((array) ($data['feedback_items'] ?? []), $now);
        $this->insertOperationAlertNotifications((array) ($data['operation_alert_notifications'] ?? []), $now);
        $this->insertOperationAlertNotificationReceivers((array) ($data['operation_alert_notification_receivers'] ?? []), $now);
        $this->insertOperationAlertNotificationLogs((array) ($data['operation_alert_notification_logs'] ?? []), $now);
        $this->insertLandingPages((array) ($data['landing_pages'] ?? []), $now);
        $this->insertLandingPageEvents((array) ($data['landing_page_events'] ?? []), $now);
        $this->insertAdPlatformConfigs((array) ($data['ad_platform_configs'] ?? []), $now);
        $this->insertAdDeliveryRules((array) ($data['ad_delivery_rules'] ?? []), $now);
        $this->insertAdSlots((array) ($data['ad_slots'] ?? []), $now);
        $this->insertAdEvents((array) ($data['ad_events'] ?? []), $now);
        $this->insertMiniProgramConfigs((array) ($data['mini_program_configs'] ?? []), $now);
        $this->insertMiniProgramSyncTasks((array) ($data['mini_program_sync_tasks'] ?? []), $now);
        $this->insertContentEvents((array) ($data['content_events'] ?? []), $now);
        $this->insertAgents((array) ($data['agents'] ?? []), $now);
        $this->insertRechargeProducts((array) ($data['recharge_products'] ?? []), $now);
    }

    private function insertAdmins(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('admins') . ' (id, username, nickname, role, agent_id, status, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['username'] ?? ''),
                (string) ($item['nickname'] ?? ''),
                (string) ($item['role'] ?? 'super_admin'),
                (int) ($item['agent_id'] ?? 0),
                (string) ($item['status'] ?? 'active'),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertUsers(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('users') . ' (id, phone, nickname, role, membership, coin_balance, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? 0),
                (string) ($item['phone'] ?? ''),
                (string) ($item['nickname'] ?? ''),
                (string) ($item['role'] ?? ''),
                !empty($item['membership']) ? 1 : 0,
                (int) ($item['coin_balance'] ?? 0),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertContentTags(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('content_tags') . ' (id, name, status, sort_order, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['name'] ?? ''),
                (string) ($item['status'] ?? 'active'),
                (int) ($item['sort'] ?? 0),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertContentGroups(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('content_groups') . ' (id, name, status, sort_order, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['name'] ?? ''),
                (string) ($item['status'] ?? 'active'),
                (int) ($item['sort'] ?? 0),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertDramas(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('dramas') . ' (id, title, category, group_id, audit_status, tag_names, status, is_hot, is_new, sort_order, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? 0),
                (string) ($item['title'] ?? ''),
                (string) ($item['category'] ?? ''),
                (int) ($item['group_id'] ?? 0),
                (string) ($item['audit_status'] ?? 'draft'),
                implode(',', array_map('strval', (array) ($item['tags'] ?? []))),
                (string) ($item['status'] ?? ''),
                !empty($item['is_hot']) ? 1 : 0,
                !empty($item['is_new']) ? 1 : 0,
                (int) ($item['sort'] ?? 0),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertNovels(array $items, string $now): void
    {
        $novelStatement = $this->pdo()->prepare('INSERT INTO ' . $this->table('novels') . ' (id, title, author, category, group_id, audit_status, tag_names, status, is_hot, is_new, sort_order, chapter_count, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $chapterStatement = $this->pdo()->prepare('INSERT INTO ' . $this->table('novel_chapters') . ' (row_key, novel_id, chapter_id, chapter_order, status, is_free, word_count, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $novelId = (int) ($item['id'] ?? ($index + 1));
            $chapters = array_values((array) ($item['chapters'] ?? []));
            $novelStatement->execute([
                $novelId,
                (string) ($item['title'] ?? ''),
                (string) ($item['author'] ?? ''),
                (string) ($item['category'] ?? ''),
                (int) ($item['group_id'] ?? 0),
                (string) ($item['audit_status'] ?? 'draft'),
                implode(',', array_map('strval', (array) ($item['tags'] ?? []))),
                (string) ($item['status'] ?? ''),
                !empty($item['is_hot']) ? 1 : 0,
                !empty($item['is_new']) ? 1 : 0,
                (int) ($item['sort'] ?? 0),
                count($chapters),
                $this->payload($item),
                $now,
            ]);
            foreach ($chapters as $chapterIndex => $chapter) {
                if (!is_array($chapter)) {
                    continue;
                }
                $chapterId = (int) ($chapter['id'] ?? ($novelId * 1000 + $chapterIndex + 1));
                $chapterStatement->execute([
                    $novelId . ':' . $chapterId,
                    $novelId,
                    $chapterId,
                    (int) ($chapter['sort'] ?? ($chapterIndex + 1)),
                    (string) ($chapter['status'] ?? ''),
                    !empty($chapter['is_free']) ? 1 : 0,
                    (int) ($chapter['word_count'] ?? 0),
                    $this->payload($chapter + ['novel_id' => $novelId]),
                    $now,
                ]);
            }
        }
    }

    private function insertOrders(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('orders') . ' (order_no, user_id, status, order_type, content_type, drama_id, episode_id, novel_id, chapter_id, promotion_link_id, traffic_platform, channel_id, media_app_id, ad_id, creative_id, material_id, app_key, product_code, product_template_id, amount, payment_route_id, payment_provider, payment_method, is_test, created_at, paid_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $orderNo = (string) ($item['order_no'] ?? '');
            if ($orderNo === '') {
                continue;
            }
            $statement->execute([
                $orderNo,
                (int) ($item['user_id'] ?? 0),
                (string) ($item['status'] ?? ''),
                (string) (($item['type'] ?? '') ?: ($item['order_type'] ?? '')),
                (string) ($item['content_type'] ?? 'drama'),
                (int) ($item['drama_id'] ?? 0),
                (int) ($item['episode_id'] ?? 0),
                (int) ($item['novel_id'] ?? 0),
                (int) ($item['chapter_id'] ?? 0),
                (int) ($item['promotion_link_id'] ?? 0),
                (string) ($item['traffic_platform'] ?? ''),
                (string) ($item['channel_id'] ?? ''),
                (string) ($item['media_app_id'] ?? ''),
                (string) ($item['ad_id'] ?? ''),
                (string) ($item['creative_id'] ?? ''),
                (string) ($item['material_id'] ?? ''),
                (string) ($item['app_key'] ?? ''),
                (string) ($item['product_code'] ?? $item['package_code'] ?? $item['plan_code'] ?? ''),
                (int) ($item['product_template_id'] ?? 0),
                round((float) ($item['amount'] ?? $item['price'] ?? 0), 2),
                (string) ($item['payment_route_id'] ?? ''),
                (string) ($item['payment_provider'] ?? ''),
                (string) ($item['payment_method'] ?? ''),
                !empty($item['is_test']) ? 1 : 0,
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->datetimeOrNull((string) ($item['paid_at'] ?? $item['paid_time'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertEntitlements(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('entitlements') . ' (row_key, user_id, content_type, drama_id, episode_id, novel_id, chapter_id, entitlement_type, order_no, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                $this->rowKey($item, $index, ['id', 'entitlement_id']),
                (int) ($item['user_id'] ?? 0),
                (string) ($item['content_type'] ?? 'drama'),
                (int) ($item['drama_id'] ?? 0),
                (int) ($item['episode_id'] ?? 0),
                (int) ($item['novel_id'] ?? 0),
                (int) ($item['chapter_id'] ?? 0),
                (string) (($item['type'] ?? '') ?: ($item['entitlement_type'] ?? '')),
                (string) ($item['order_no'] ?? ''),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertPaymentRoutes(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('payment_routes') . ' (id, provider, payment_method, enabled, is_default, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (string) (($item['id'] ?? '') ?: ($index + 1));
            $statement->execute([
                $id,
                (string) ($item['provider'] ?? ''),
                (string) ($item['payment_method'] ?? ''),
                !empty($item['enabled']) ? 1 : 0,
                !empty($item['is_default']) ? 1 : 0,
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertApps(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('apps') . ' (app_key, app_name, app_type, status, product_template_key, payment_route_id, recommend_slot_count, enabled_task_count, user_tier_count, client_review_mode, client_force_update, client_gray_release_percent, add_desktop_enabled, callback_enabled, callback_endpoint_configured, sort_order, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $appKey = (string) (($item['app_key'] ?? '') ?: ('app_' . ($index + 1)));
            $recommendSlots = array_values((array) ($item['recommend_slots'] ?? []));
            $taskConfig = (array) ($item['task_config'] ?? []);
            $userTiers = (array) ($item['user_tiers'] ?? []);
            $clientConfig = (array) ($item['client_config'] ?? []);
            $callbackPolicy = (array) ($item['callback_policy'] ?? []);
            $statement->execute([
                $appKey,
                (string) ($item['name'] ?? ''),
                (string) ($item['type'] ?? ''),
                (string) ($item['status'] ?? ''),
                (string) ($item['product_template_key'] ?? ''),
                (string) ($item['payment_route_id'] ?? ''),
                count(array_filter($recommendSlots, static fn (array $slot): bool => !empty($slot['enabled']))),
                count(array_filter($taskConfig, static fn (array $task): bool => !empty($task['enabled']))),
                count(array_filter($userTiers, static fn (array $tier): bool => !empty($tier['enabled']))),
                (string) ($clientConfig['review_mode'] ?? 'normal'),
                !empty($clientConfig['force_update']) ? 1 : 0,
                max(0, min(100, (int) ($clientConfig['gray_release_percent'] ?? 100))),
                !empty($taskConfig['add_desktop']['enabled']) ? 1 : 0,
                !empty($callbackPolicy['enabled']) ? 1 : 0,
                trim((string) ($callbackPolicy['endpoint'] ?? ($item['callback_url'] ?? ''))) !== '' ? 1 : 0,
                (int) ($item['sort'] ?? 0),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertOrderActionLogs(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('order_action_logs') . ' (row_key, order_no, action, success, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                $this->rowKey($item, $index, ['id', 'log_id']),
                (string) ($item['order_no'] ?? ''),
                (string) ($item['action'] ?? ''),
                !empty($item['success']) ? 1 : 0,
                $this->datetimeOrNull((string) ($item['created_at'] ?? $item['time'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertCoinTransactions(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('coin_transactions') . ' (row_key, user_id, transaction_type, amount, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                $this->rowKey($item, $index, ['id', 'transaction_id']),
                (int) ($item['user_id'] ?? 0),
                (string) (($item['type'] ?? '') ?: ($item['transaction_type'] ?? '')),
                (int) ($item['amount'] ?? $item['coins'] ?? 0),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertRightsRepairLogs(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('rights_repair_logs') . ' (id, user_id, action, content_type, status, admin_id, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (int) ($item['user_id'] ?? 0),
                (string) ($item['action'] ?? ''),
                (string) ($item['content_type'] ?? ''),
                (string) ($item['status'] ?? ''),
                (int) ($item['admin_id'] ?? 0),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertRedeemCodes(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('redeem_codes') . ' (id, code, code_name, status, reward_type, app_key, promotion_code, agent_id, channel_id, total_limit, per_user_limit, started_at, ended_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['code'] ?? ''),
                (string) ($item['name'] ?? ''),
                (string) ($item['status'] ?? ''),
                (string) ($item['reward_type'] ?? ''),
                (string) ($item['app_key'] ?? ''),
                (string) ($item['promotion_code'] ?? ''),
                (int) ($item['agent_id'] ?? 0),
                (string) ($item['channel_id'] ?? ''),
                (int) ($item['total_limit'] ?? 0),
                (int) ($item['per_user_limit'] ?? 1),
                $this->datetimeOrNull((string) ($item['started_at'] ?? '')),
                $this->datetimeOrNull((string) ($item['ended_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertRedeemCodeLogs(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('redeem_code_logs') . ' (row_key, code_id, code, user_id, promotion_code, agent_id, channel_id, status, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                $this->rowKey($item, $index, ['id']),
                (int) ($item['code_id'] ?? 0),
                (string) ($item['code'] ?? ''),
                (int) ($item['user_id'] ?? 0),
                (string) ($item['promotion_code'] ?? ''),
                (int) ($item['agent_id'] ?? 0),
                (string) ($item['channel_id'] ?? ''),
                (string) ($item['status'] ?? ''),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertHomeRecommendations(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('home_recommendations') . ' (id, app_key, slot, status, content_type, content_id, sort_order, started_at, ended_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['app_key'] ?? ''),
                (string) ($item['slot'] ?? ''),
                (string) ($item['status'] ?? ''),
                (string) ($item['content_type'] ?? ''),
                (int) ($item['content_id'] ?? 0),
                (int) ($item['sort'] ?? 0),
                $this->datetimeOrNull((string) ($item['started_at'] ?? '')),
                $this->datetimeOrNull((string) ($item['ended_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertPopupNotices(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('popup_notices') . ' (id, app_key, trigger_name, status, priority, started_at, ended_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['app_key'] ?? ''),
                (string) ($item['trigger'] ?? ''),
                (string) ($item['status'] ?? ''),
                (int) ($item['priority'] ?? 100),
                $this->datetimeOrNull((string) ($item['started_at'] ?? '')),
                $this->datetimeOrNull((string) ($item['ended_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertActivityConfigs(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('activity_configs') . ' (id, code, app_key, activity_type, target_tiers, experiment_key, variant_key, traffic_percent, status, sort_order, started_at, ended_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['code'] ?? ''),
                (string) ($item['app_key'] ?? ''),
                (string) ($item['activity_type'] ?? ''),
                implode(',', array_map('strval', (array) ($item['target_tiers'] ?? ['all']))),
                (string) ($item['experiment_key'] ?? ''),
                (string) ($item['variant_key'] ?? ''),
                (int) ($item['traffic_percent'] ?? 100),
                (string) ($item['status'] ?? ''),
                (int) ($item['sort'] ?? 0),
                $this->datetimeOrNull((string) ($item['started_at'] ?? '')),
                $this->datetimeOrNull((string) ($item['ended_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertActivityParticipationLogs(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('activity_participation_logs') . ' (id, activity_id, activity_code, event_type, experiment_key, variant_key, user_id, app_key, status, reward_type, coin_amount, vip_days, participation_date, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (int) ($item['activity_id'] ?? 0),
                (string) ($item['activity_code'] ?? $item['code'] ?? ''),
                (string) ($item['event_type'] ?? 'claim'),
                (string) ($item['experiment_key'] ?? ''),
                (string) ($item['variant_key'] ?? ''),
                (int) ($item['user_id'] ?? 0),
                (string) ($item['app_key'] ?? 'default'),
                (string) ($item['status'] ?? ''),
                (string) ($item['reward_type'] ?? ''),
                (int) ($item['coin_amount'] ?? $item['coins'] ?? 0),
                (int) ($item['vip_days'] ?? 0),
                $this->datetimeOrNull((string) ($item['participation_date'] ?? '')),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertWatchHistory(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('watch_history') . ' (row_key, user_id, drama_id, episode_id, updated_time, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                $this->rowKey($item, $index, ['id']),
                (int) ($item['user_id'] ?? 0),
                (int) ($item['drama_id'] ?? 0),
                (int) ($item['episode_id'] ?? 0),
                $this->datetimeOrNull((string) ($item['updated_at'] ?? $item['watched_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertFollowedDramas(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('followed_dramas') . ' (row_key, user_id, drama_id, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                $this->rowKey($item, $index, ['id']),
                (int) ($item['user_id'] ?? 0),
                (int) ($item['drama_id'] ?? 0),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertSmsCodes(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('sms_codes') . ' (row_key, phone, code, provider, send_status, created_at, expires_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                $this->rowKey($item, $index, ['id']),
                (string) ($item['phone'] ?? ''),
                (string) ($item['code'] ?? ''),
                (string) ($item['provider'] ?? 'mock'),
                (string) ($item['send_status'] ?? 'mocked'),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->datetimeOrNull((string) ($item['expires_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertEmailDeliveryLogs(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('email_delivery_logs') . ' (id, to_email, provider, status, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['to_email'] ?? ''),
                (string) ($item['provider'] ?? 'mock'),
                (string) ($item['status'] ?? ''),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertConfigChangeRequests(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('config_change_requests') . ' (id, config_type, target_key, status, created_by_admin_id, created_at, reviewed_at, applied_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['config_type'] ?? ''),
                (string) ($item['target_key'] ?? ''),
                (string) ($item['status'] ?? 'pending'),
                (int) ($item['created_by_admin_id'] ?? 0),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->datetimeOrNull((string) ($item['reviewed_at'] ?? '')),
                $this->datetimeOrNull((string) ($item['applied_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertConfigChangeNotificationLogs(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('config_change_notification_logs') . ' (id, request_id, event, channel, receiver_admin_id, status, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (int) ($item['request_id'] ?? 0),
                (string) ($item['event'] ?? 'submitted'),
                (string) ($item['channel'] ?? 'system'),
                (int) ($item['receiver_admin_id'] ?? 0),
                (string) ($item['status'] ?? 'success'),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertAppConfigDeliveryLogs(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('app_config_delivery_logs') . ' (id, fingerprint, app_key, user_id, review_mode, gray_hit, last_seen_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['fingerprint'] ?? ''),
                (string) ($item['app_key'] ?? 'default'),
                (int) ($item['user_id'] ?? 0),
                (string) ($item['review_mode'] ?? 'normal'),
                !empty($item['gray_hit']) ? 1 : 0,
                $this->datetimeOrNull((string) ($item['last_seen_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertPromotionLinks(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('promotion_links') . ' (id, code, content_type, drama_id, episode_id, novel_id, chapter_id, agent_id, status, traffic_platform, channel_id, media_app_id, ad_id, creative_id, material_id, replacement_rule_count, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['code'] ?? ''),
                (string) ($item['content_type'] ?? 'drama'),
                (int) ($item['drama_id'] ?? 0),
                (int) ($item['episode_id'] ?? 0),
                (int) ($item['novel_id'] ?? 0),
                (int) ($item['chapter_id'] ?? 0),
                (int) ($item['agent_id'] ?? 0),
                (string) ($item['status'] ?? ''),
                (string) ($item['traffic_platform'] ?? ''),
                (string) ($item['channel_id'] ?? ''),
                (string) ($item['media_app_id'] ?? ''),
                (string) ($item['ad_id'] ?? ''),
                (string) ($item['creative_id'] ?? ''),
                (string) ($item['material_id'] ?? ''),
                count((array) ($item['replacement_rules'] ?? [])),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertPromotionEvents(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('promotion_events') . ' (row_key, promotion_link_id, code, event_type, user_id, order_no, amount, traffic_platform, channel_id, media_app_id, ad_id, creative_id, material_id, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                $this->rowKey($item, $index, ['id']),
                (int) ($item['promotion_link_id'] ?? 0),
                (string) ($item['code'] ?? ''),
                (string) ($item['event'] ?? ''),
                (int) ($item['user_id'] ?? 0),
                (string) ($item['order_no'] ?? ''),
                round((float) ($item['amount'] ?? 0), 2),
                (string) ($item['traffic_platform'] ?? ''),
                (string) ($item['channel_id'] ?? ''),
                (string) ($item['media_app_id'] ?? ''),
                (string) ($item['ad_id'] ?? ''),
                (string) ($item['creative_id'] ?? ''),
                (string) ($item['material_id'] ?? ''),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertPromotionCosts(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('promotion_costs') . ' (id, cost_date, promotion_link_id, agent_id, amount, traffic_platform, channel_id, media_app_id, ad_id, creative_id, material_id, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                $this->dateOrNull((string) ($item['date'] ?? '')),
                (int) ($item['promotion_link_id'] ?? 0),
                (int) ($item['agent_id'] ?? 0),
                round((float) ($item['amount'] ?? 0), 2),
                (string) ($item['traffic_platform'] ?? ''),
                (string) ($item['channel_id'] ?? ''),
                (string) ($item['media_app_id'] ?? ''),
                (string) ($item['ad_id'] ?? ''),
                (string) ($item['creative_id'] ?? ''),
                (string) ($item['material_id'] ?? ''),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertCallbackLogs(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('callback_logs') . ' (id, event_type, platform_event, promotion_link_id, user_id, order_no, status, traffic_platform, channel_id, media_app_id, app_key, callback_policy_source, callback_retry_failed, ad_id, creative_id, material_id, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['event'] ?? ''),
                (string) ($item['platform_event'] ?? ''),
                (int) ($item['promotion_link_id'] ?? 0),
                (int) ($item['user_id'] ?? 0),
                (string) ($item['order_no'] ?? ''),
                (string) ($item['status'] ?? ''),
                (string) ($item['traffic_platform'] ?? ''),
                (string) ($item['channel_id'] ?? ''),
                (string) ($item['media_app_id'] ?? ''),
                (string) ($item['app_key'] ?? ''),
                (string) ($item['callback_policy_source'] ?? 'global'),
                array_key_exists('callback_retry_failed', $item) && empty($item['callback_retry_failed']) ? 0 : 1,
                (string) ($item['ad_id'] ?? ''),
                (string) ($item['creative_id'] ?? ''),
                (string) ($item['material_id'] ?? ''),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertFeedbackItems(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('feedback_items') . ' (id, feedback_type, status, priority, app_key, user_id, order_no, content_type, drama_id, episode_id, novel_id, chapter_id, promotion_link_id, promotion_code, agent_id, traffic_platform, channel_id, media_app_id, ad_id, creative_id, material_id, sla_status, due_at, suggested_action, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['type'] ?? 'feedback'),
                (string) ($item['status'] ?? 'pending'),
                (string) ($item['priority'] ?? 'normal'),
                (string) ($item['app_key'] ?? 'default'),
                (int) ($item['user_id'] ?? 0),
                (string) ($item['order_no'] ?? ''),
                (string) ($item['content_type'] ?? ''),
                (int) ($item['drama_id'] ?? 0),
                (int) ($item['episode_id'] ?? 0),
                (int) ($item['novel_id'] ?? 0),
                (int) ($item['chapter_id'] ?? 0),
                (int) ($item['promotion_link_id'] ?? 0),
                (string) ($item['promotion_code'] ?? ''),
                (int) ($item['agent_id'] ?? 0),
                (string) ($item['traffic_platform'] ?? ''),
                (string) ($item['channel_id'] ?? ''),
                (string) ($item['media_app_id'] ?? ''),
                (string) ($item['ad_id'] ?? ''),
                (string) ($item['creative_id'] ?? ''),
                (string) ($item['material_id'] ?? ''),
                (string) ($item['sla_status'] ?? 'normal'),
                $this->datetimeOrNull((string) ($item['due_at'] ?? '')),
                (string) ($item['suggested_action'] ?? 'none'),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertOperationAlertNotifications(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('operation_alert_notifications') . ' (id, fingerprint, alert_type, status, priority, promotion_link_id, promotion_code, order_no, agent_id, traffic_platform, channel_id, media_app_id, ad_id, creative_id, material_id, last_seen_at, occurrence_count, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['fingerprint'] ?? ''),
                (string) ($item['type'] ?? 'operation_alert'),
                (string) ($item['status'] ?? 'pending'),
                (string) ($item['priority'] ?? 'normal'),
                (int) ($item['promotion_link_id'] ?? 0),
                (string) ($item['promotion_code'] ?? ''),
                (string) ($item['order_no'] ?? ''),
                (int) ($item['agent_id'] ?? 0),
                (string) ($item['traffic_platform'] ?? ''),
                (string) ($item['channel_id'] ?? ''),
                (string) ($item['media_app_id'] ?? ''),
                (string) ($item['ad_id'] ?? ''),
                (string) ($item['creative_id'] ?? ''),
                (string) ($item['material_id'] ?? ''),
                $this->datetimeOrNull((string) ($item['last_seen_at'] ?? '')),
                max(1, (int) ($item['occurrence_count'] ?? 1)),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertOperationAlertNotificationReceivers(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('operation_alert_notification_receivers') . ' (id, receiver_name, status, scope_type, scope_role, agent_id, webhook_url, min_priority, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['name'] ?? ''),
                (string) ($item['status'] ?? 'active'),
                (string) ($item['scope_type'] ?? 'global'),
                (string) ($item['scope_role'] ?? ''),
                (int) ($item['agent_id'] ?? 0),
                (string) ($item['webhook_url'] ?? ''),
                (string) ($item['min_priority'] ?? 'normal'),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertOperationAlertNotificationLogs(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('operation_alert_notification_logs') . ' (id, alert_id, receiver_id, receiver_name, fingerprint, alert_type, status, channel, endpoint, priority, promotion_link_id, promotion_code, order_no, agent_id, traffic_platform, channel_id, media_app_id, ad_id, creative_id, material_id, attempt_count, last_attempt_at, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (int) ($item['alert_id'] ?? 0),
                (int) ($item['receiver_id'] ?? 0),
                (string) ($item['receiver_name'] ?? ''),
                (string) ($item['fingerprint'] ?? ''),
                (string) ($item['alert_type'] ?? $item['type'] ?? 'operation_alert'),
                (string) ($item['status'] ?? 'pending'),
                (string) ($item['channel'] ?? 'webhook'),
                (string) ($item['endpoint'] ?? ''),
                (string) ($item['priority'] ?? 'normal'),
                (int) ($item['promotion_link_id'] ?? 0),
                (string) ($item['promotion_code'] ?? ''),
                (string) ($item['order_no'] ?? ''),
                (int) ($item['agent_id'] ?? 0),
                (string) ($item['traffic_platform'] ?? ''),
                (string) ($item['channel_id'] ?? ''),
                (string) ($item['media_app_id'] ?? ''),
                (string) ($item['ad_id'] ?? ''),
                (string) ($item['creative_id'] ?? ''),
                (string) ($item['material_id'] ?? ''),
                max(0, (int) ($item['attempt_count'] ?? 0)),
                $this->datetimeOrNull((string) ($item['last_attempt_at'] ?? '')),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertLandingPages(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('landing_pages') . ' (id, slug, page_name, status, template, content_type, drama_id, episode_id, novel_id, chapter_id, promotion_link_id, promotion_code, agent_id, app_key, traffic_platform, channel_id, media_app_id, ad_id, creative_id, material_id, views, clicks, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['slug'] ?? ''),
                (string) ($item['name'] ?? ''),
                (string) ($item['status'] ?? 'active'),
                (string) ($item['template'] ?? 'drama'),
                (string) ($item['content_type'] ?? 'drama'),
                (int) ($item['drama_id'] ?? 0),
                (int) ($item['episode_id'] ?? 0),
                (int) ($item['novel_id'] ?? 0),
                (int) ($item['chapter_id'] ?? 0),
                (int) ($item['promotion_link_id'] ?? 0),
                (string) ($item['promotion_code'] ?? ''),
                (int) ($item['agent_id'] ?? 0),
                (string) ($item['app_key'] ?? ''),
                (string) ($item['traffic_platform'] ?? ''),
                (string) ($item['channel_id'] ?? ''),
                (string) ($item['media_app_id'] ?? ''),
                (string) ($item['ad_id'] ?? ''),
                (string) ($item['creative_id'] ?? ''),
                (string) ($item['material_id'] ?? ''),
                (int) ($item['views'] ?? 0),
                (int) ($item['clicks'] ?? 0),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertLandingPageEvents(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('landing_page_events') . ' (row_key, landing_page_id, slug, event_type, user_id, promotion_link_id, promotion_code, agent_id, traffic_platform, channel_id, media_app_id, ad_id, creative_id, material_id, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                $this->rowKey($item, $index, ['id']),
                (int) ($item['landing_page_id'] ?? 0),
                (string) ($item['slug'] ?? ''),
                (string) ($item['event'] ?? 'view'),
                (int) ($item['user_id'] ?? 0),
                (int) ($item['promotion_link_id'] ?? 0),
                (string) ($item['promotion_code'] ?? ''),
                (int) ($item['agent_id'] ?? 0),
                (string) ($item['traffic_platform'] ?? ''),
                (string) ($item['channel_id'] ?? ''),
                (string) ($item['media_app_id'] ?? ''),
                (string) ($item['ad_id'] ?? ''),
                (string) ($item['creative_id'] ?? ''),
                (string) ($item['material_id'] ?? ''),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertAdPlatformConfigs(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('ad_platform_configs') . ' (id, app_key, provider, provider_name, status, platform_app_id, account_id, media_id, default_ecpm, revenue_share_rate, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['app_key'] ?? 'default'),
                (string) ($item['provider'] ?? ''),
                (string) ($item['provider_name'] ?? ''),
                (string) ($item['status'] ?? 'active'),
                (string) ($item['platform_app_id'] ?? ''),
                (string) ($item['account_id'] ?? ''),
                (string) ($item['media_id'] ?? ''),
                round(max(0, (float) ($item['default_ecpm'] ?? 0)), 4),
                round(min(100, max(0, (float) ($item['revenue_share_rate'] ?? 100))), 2),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertAdDeliveryRules(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('ad_delivery_rules') . ' (id, code, rule_name, status, app_keys, slot_codes, positions, ad_types, providers, membership, pay_stage, priority, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['code'] ?? ''),
                (string) ($item['name'] ?? ''),
                (string) ($item['status'] ?? 'active'),
                implode(',', array_map('strval', (array) ($item['app_keys'] ?? []))),
                implode(',', array_map('strval', (array) ($item['slot_codes'] ?? []))),
                implode(',', array_map('strval', (array) ($item['positions'] ?? []))),
                implode(',', array_map('strval', (array) ($item['ad_types'] ?? []))),
                implode(',', array_map('strval', (array) ($item['providers'] ?? []))),
                (string) ($item['membership'] ?? 'all'),
                (string) ($item['pay_stage'] ?? 'all'),
                (int) ($item['priority'] ?? 100),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertAdSlots(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('ad_slots') . ' (id, code, slot_name, ad_type, position, app_key, status, provider, unit_id, estimate_ecpm, revenue_share_rate, reward_coins, daily_limit, frequency_seconds, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['code'] ?? ''),
                (string) ($item['name'] ?? ''),
                (string) ($item['ad_type'] ?? ''),
                (string) ($item['position'] ?? ''),
                (string) ($item['app_key'] ?? 'default'),
                (string) ($item['status'] ?? 'active'),
                (string) ($item['provider'] ?? ''),
                (string) ($item['unit_id'] ?? ''),
                round(max(0, (float) ($item['estimate_ecpm'] ?? 0)), 4),
                round(min(100, max(0, (float) ($item['revenue_share_rate'] ?? 100))), 2),
                (int) ($item['reward_coins'] ?? 0),
                (int) ($item['daily_limit'] ?? 0),
                (int) ($item['frequency_seconds'] ?? 0),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertAdEvents(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('ad_events') . ' (row_key, ad_slot_id, code, event_type, user_id, app_key, position, ad_type, provider, unit_id, reward_coins, revenue, ecpm, currency, error_code, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                $this->rowKey($item, $index, ['id']),
                (int) ($item['ad_slot_id'] ?? 0),
                (string) ($item['code'] ?? ''),
                (string) ($item['event'] ?? 'impression'),
                (int) ($item['user_id'] ?? 0),
                (string) ($item['app_key'] ?? 'default'),
                (string) ($item['position'] ?? ''),
                (string) ($item['ad_type'] ?? ''),
                (string) ($item['provider'] ?? ''),
                (string) ($item['unit_id'] ?? ''),
                (int) ($item['reward_coins'] ?? 0),
                round(max(0, (float) ($item['revenue'] ?? 0)), 4),
                round(max(0, (float) ($item['ecpm'] ?? 0)), 4),
                (string) ($item['currency'] ?? 'CNY'),
                (string) ($item['error_code'] ?? ''),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertMiniProgramConfigs(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('mini_program_configs') . ' (id, app_key, config_name, status, mp_app_id, original_id, upload_mode, content_scope, last_sync_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['app_key'] ?? 'default'),
                (string) ($item['name'] ?? ''),
                (string) ($item['status'] ?? 'draft'),
                (string) ($item['mp_app_id'] ?? ''),
                (string) ($item['original_id'] ?? ''),
                (string) ($item['upload_mode'] ?? 'manual'),
                (string) ($item['content_scope'] ?? 'all'),
                $this->datetimeOrNull((string) ($item['last_sync_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertMiniProgramSyncTasks(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('mini_program_sync_tasks') . ' (id, config_id, app_key, content_type, status, item_count, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (int) ($item['config_id'] ?? 0),
                (string) ($item['app_key'] ?? 'default'),
                (string) ($item['content_type'] ?? 'mixed'),
                (string) ($item['status'] ?? 'generated'),
                (int) ($item['item_count'] ?? 0),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertContentEvents(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('content_events') . ' (row_key, content_type, event_type, drama_id, episode_id, novel_id, chapter_id, user_id, order_no, promotion_link_id, amount, traffic_platform, channel_id, media_app_id, ad_id, creative_id, material_id, created_at, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                $this->rowKey($item, $index, ['id']),
                (string) ($item['content_type'] ?? 'drama'),
                (string) ($item['event'] ?? ''),
                (int) ($item['drama_id'] ?? 0),
                (int) ($item['episode_id'] ?? 0),
                (int) ($item['novel_id'] ?? 0),
                (int) ($item['chapter_id'] ?? 0),
                (int) ($item['user_id'] ?? 0),
                (string) ($item['order_no'] ?? ''),
                (int) ($item['promotion_link_id'] ?? 0),
                round((float) ($item['amount'] ?? 0), 2),
                (string) ($item['traffic_platform'] ?? ''),
                (string) ($item['channel_id'] ?? ''),
                (string) ($item['media_app_id'] ?? ''),
                (string) ($item['ad_id'] ?? ''),
                (string) ($item['creative_id'] ?? ''),
                (string) ($item['material_id'] ?? ''),
                $this->datetimeOrNull((string) ($item['created_at'] ?? '')),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertAgents(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('agents') . ' (id, name, role, parent_id, status, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $statement->execute([
                (int) ($item['id'] ?? ($index + 1)),
                (string) ($item['name'] ?? ''),
                (string) ($item['role'] ?? ''),
                (int) ($item['parent_id'] ?? 0),
                (string) ($item['status'] ?? ''),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function insertRechargeProducts(array $items, string $now): void
    {
        $statement = $this->pdo()->prepare('INSERT INTO ' . $this->table('recharge_products') . ' (code, product_type, price, enabled, sort_order, payload, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $code = (string) (($item['code'] ?? '') ?: ('goods_' . ($index + 1)));
            $statement->execute([
                $code,
                (string) ($item['type'] ?? ''),
                round((float) ($item['price'] ?? 0), 2),
                !empty($item['enabled']) ? 1 : 0,
                (int) ($item['sort'] ?? 0),
                $this->payload($item),
                $now,
            ]);
        }
    }

    private function payload(array $item): string
    {
        $json = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? '{}' : $json;
    }

    private function rowKey(array $item, int $index, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($item[$key]) && (string) $item[$key] !== '') {
                return substr((string) $item[$key], 0, 110);
            }
        }

        return substr(sha1(json_encode($item, JSON_UNESCAPED_UNICODE) . '#' . $index), 0, 40);
    }

    private function datetimeOrNull(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    private function dateOrNull(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }
}
