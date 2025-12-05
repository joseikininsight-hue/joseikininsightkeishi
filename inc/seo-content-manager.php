<?php
/**
 * Plugin Name: GI SEO Content Manager
 * Description: AIリノベーション・カルテ機能付きSEOコンテンツ管理 + 補助金データベース
 * Version: 30.0.0
 * Author: GI Web Team
 */

if (!defined('ABSPATH')) exit;

class GI_SEO_Content_Manager {
    private $version = '31.4.0';
    private $table_queue;
    private $table_failed;
    private $table_merge_history;
    private $table_404_log;
    private $table_subsidy;
    private $table_process_log;
    private $table_renovation_stats;

    public function __construct() {
        global $wpdb;
        $this->table_queue = $wpdb->prefix . 'gi_seo_queue';
        $this->table_failed = $wpdb->prefix . 'gi_seo_failed';
        $this->table_merge_history = $wpdb->prefix . 'gi_seo_merge_history';
        $this->table_404_log = $wpdb->prefix . 'gi_seo_404_log';
        $this->table_subsidy = $wpdb->prefix . 'gi_subsidy_db';
        $this->table_process_log = $wpdb->prefix . 'gi_seo_process_log';
        $this->table_renovation_stats = $wpdb->prefix . 'gi_renovation_stats';

        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Ajax - SEO
        add_action('wp_ajax_gi_seo_get_posts', array($this, 'ajax_get_posts'));
        add_action('wp_ajax_gi_seo_renovate', array($this, 'ajax_renovate'));
        add_action('wp_ajax_gi_seo_add_to_queue', array($this, 'ajax_add_to_queue'));
        add_action('wp_ajax_gi_seo_add_all_to_queue', array($this, 'ajax_add_all_to_queue'));
        add_action('wp_ajax_gi_seo_add_urls_to_queue', array($this, 'ajax_add_urls_to_queue'));
        add_action('wp_ajax_gi_seo_start_queue', array($this, 'ajax_start_queue'));
        add_action('wp_ajax_gi_seo_stop_queue', array($this, 'ajax_stop_queue'));
        add_action('wp_ajax_gi_seo_get_queue_status', array($this, 'ajax_get_queue_status'));
        add_action('wp_ajax_gi_seo_get_queue_details', array($this, 'ajax_get_queue_details'));
        add_action('wp_ajax_gi_seo_clear_queue', array($this, 'ajax_clear_queue'));
        add_action('wp_ajax_gi_seo_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_gi_seo_get_failed', array($this, 'ajax_get_failed'));
        add_action('wp_ajax_gi_seo_retry_failed', array($this, 'ajax_retry_failed'));
        add_action('wp_ajax_gi_seo_clear_failed', array($this, 'ajax_clear_failed'));
        add_action('wp_ajax_gi_seo_restore_backup', array($this, 'ajax_restore_backup'));
        add_action('wp_ajax_gi_seo_restore_original', array($this, 'ajax_restore_original'));
        
        // Ajax - M&A（記事統合）
        add_action('wp_ajax_gi_seo_get_merge_candidates', array($this, 'ajax_get_merge_candidates'));
        add_action('wp_ajax_gi_seo_execute_merge', array($this, 'ajax_execute_merge'));
        add_action('wp_ajax_gi_seo_bulk_merge', array($this, 'ajax_bulk_merge'));
        add_action('wp_ajax_gi_seo_get_merge_history', array($this, 'ajax_get_merge_history'));
        add_action('wp_ajax_gi_seo_undo_merge', array($this, 'ajax_undo_merge'));
        add_action('wp_ajax_gi_seo_analyze_keywords', array($this, 'ajax_analyze_keywords'));
        add_action('wp_ajax_gi_seo_get_keyword_stats', array($this, 'ajax_get_keyword_stats'));
        
        // Ajax - 分析・レポート
        add_action('wp_ajax_gi_seo_get_analytics', array($this, 'ajax_get_analytics'));
        add_action('wp_ajax_gi_seo_get_report', array($this, 'ajax_get_report'));
        add_action('wp_ajax_gi_seo_get_report_list', array($this, 'ajax_get_report_list'));
        add_action('wp_ajax_gi_seo_export_reports', array($this, 'ajax_export_reports'));
        
        // Ajax - リノベーション統計
        add_action('wp_ajax_gi_seo_get_renovation_stats', array($this, 'ajax_get_renovation_stats'));
        add_action('wp_ajax_gi_seo_get_pv_history', array($this, 'ajax_get_pv_history'));
        
        // Ajax - 404管理（強化版）
        add_action('wp_ajax_gi_seo_get_404_logs', array($this, 'ajax_get_404_logs'));
        add_action('wp_ajax_gi_seo_set_redirect', array($this, 'ajax_set_redirect'));
        add_action('wp_ajax_gi_seo_clear_redirect', array($this, 'ajax_clear_redirect'));
        add_action('wp_ajax_gi_seo_bulk_clear_redirect', array($this, 'ajax_bulk_clear_redirect'));
        add_action('wp_ajax_gi_seo_find_similar', array($this, 'ajax_find_similar'));
        add_action('wp_ajax_gi_seo_clear_404_logs', array($this, 'ajax_clear_404_logs'));
        add_action('wp_ajax_gi_seo_process_single', array($this, 'ajax_process_single'));
        add_action('wp_ajax_gi_seo_debug_404', array($this, 'ajax_debug_404'));
        add_action('wp_ajax_gi_seo_auto_redirect_all', array($this, 'ajax_auto_redirect_all'));
        add_action('wp_ajax_gi_seo_auto_redirect_single', array($this, 'ajax_auto_redirect_single'));
        add_action('wp_ajax_gi_seo_auto_redirect_selected', array($this, 'ajax_auto_redirect_selected'));
        add_action('wp_ajax_gi_seo_delete_404_logs', array($this, 'ajax_delete_404_logs'));
        
        // Ajax - 補助金データベース（強化版）
        add_action('wp_ajax_gi_subsidy_get_list', array($this, 'ajax_subsidy_get_list'));
        add_action('wp_ajax_gi_subsidy_add', array($this, 'ajax_subsidy_add'));
        add_action('wp_ajax_gi_subsidy_update', array($this, 'ajax_subsidy_update'));
        add_action('wp_ajax_gi_subsidy_delete', array($this, 'ajax_subsidy_delete'));
        add_action('wp_ajax_gi_subsidy_bulk_delete', array($this, 'ajax_subsidy_bulk_delete'));
        add_action('wp_ajax_gi_subsidy_import', array($this, 'ajax_subsidy_import'));
        add_action('wp_ajax_gi_subsidy_export', array($this, 'ajax_subsidy_export'));
        add_action('wp_ajax_gi_subsidy_get_stats', array($this, 'ajax_subsidy_get_stats'));
        add_action('wp_ajax_gi_subsidy_check_exists', array($this, 'ajax_subsidy_check_exists'));
        add_action('wp_ajax_gi_subsidy_sync_posts', array($this, 'ajax_subsidy_sync_posts'));
        add_action('wp_ajax_gi_subsidy_debug_match', array($this, 'ajax_subsidy_debug_match'));
        add_action('wp_ajax_gi_subsidy_reset_matches', array($this, 'ajax_subsidy_reset_matches'));

        // PV計測（post_metaを使用）
        add_action('wp_ajax_gi_track_pv', array($this, 'ajax_track_pv'));
        add_action('wp_ajax_nopriv_gi_track_pv', array($this, 'ajax_track_pv'));
        add_action('wp_footer', array($this, 'output_tracking_script'));

        // Cron - 複数のスケジュールで堅牢性を確保
        add_action('gi_seo_process_queue', array($this, 'process_queue'));
        add_action('gi_seo_background_process', array($this, 'background_process'));
        add_action('gi_seo_health_check', array($this, 'health_check'));

        // 404検出・リダイレクト
        add_action('wp', array($this, 'detect_404'), 1);
        add_action('template_redirect', array($this, 'handle_redirects'), 1);

        // 外部からのCron実行用エンドポイント
        add_action('wp_ajax_nopriv_gi_seo_external_cron', array($this, 'external_cron_handler'));
        add_action('wp_ajax_gi_seo_external_cron', array($this, 'external_cron_handler'));

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        $this->maybe_create_tables();
        $this->ensure_cron_running();
    }

    public function activate() {
        $this->maybe_create_tables();
        
        // 複数のCronスケジュールを設定
        if (!wp_next_scheduled('gi_seo_process_queue')) {
            wp_schedule_event(time(), 'every_minute', 'gi_seo_process_queue');
        }
        if (!wp_next_scheduled('gi_seo_background_process')) {
            wp_schedule_event(time(), 'every_five_minutes', 'gi_seo_background_process');
        }
        if (!wp_next_scheduled('gi_seo_health_check')) {
            wp_schedule_event(time(), 'every_ten_minutes', 'gi_seo_health_check');
        }
        
        // 外部Cronキーを生成
        if (!get_option('gi_seo_cron_key')) {
            update_option('gi_seo_cron_key', wp_generate_password(32, false));
        }
    }

    public function deactivate() {
        wp_clear_scheduled_hook('gi_seo_process_queue');
        wp_clear_scheduled_hook('gi_seo_background_process');
        wp_clear_scheduled_hook('gi_seo_health_check');
    }

    private function ensure_cron_running() {
        add_filter('cron_schedules', function($schedules) {
            $schedules['every_minute'] = array('interval' => 60, 'display' => 'Every Minute');
            $schedules['every_five_minutes'] = array('interval' => 300, 'display' => 'Every 5 Minutes');
            $schedules['every_ten_minutes'] = array('interval' => 600, 'display' => 'Every 10 Minutes');
            return $schedules;
        });
        
        // Cronが正しく動作しているか確認
        if (get_option('gi_seo_queue_running', false)) {
            $last_run = get_option('gi_seo_last_cron_run', 0);
            $current_time = time();
            
            // 5分以上Cronが動いていない場合は再スケジュール
            if ($current_time - $last_run > 300) {
                wp_clear_scheduled_hook('gi_seo_process_queue');
                wp_schedule_event(time(), 'every_minute', 'gi_seo_process_queue');
            }
        }
    }

    // 外部Cronハンドラー（サーバーのcrontabから呼び出し可能）
    public function external_cron_handler() {
        $key = isset($_REQUEST['key']) ? sanitize_text_field($_REQUEST['key']) : '';
        $stored_key = get_option('gi_seo_cron_key', '');
        
        if (empty($stored_key) || $key !== $stored_key) {
            wp_send_json_error('Invalid key');
            return;
        }
        
        $this->process_queue();
        wp_send_json_success(array(
            'message' => 'Cron executed',
            'time' => current_time('mysql')
        ));
    }

    // ヘルスチェック - キューが止まっていないか確認
    public function health_check() {
        global $wpdb;
        
        $running = get_option('gi_seo_queue_running', false);
        if (!$running) return;
        
        $pending = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_queue} WHERE status = 'pending'");
        if ($pending === 0) return;
        
        $last_run = get_option('gi_seo_last_cron_run', 0);
        $current_time = time();
        
        // 10分以上処理がない場合はロックを解除
        if ($current_time - $last_run > 600) {
            delete_transient('gi_seo_queue_lock');
            $wpdb->update($this->table_queue, array('status' => 'pending'), array('status' => 'processing'));
            
            // ログに記録
            $this->log_process('health_check', 'Queue lock released due to inactivity');
        }
    }

    private function log_process($action, $message) {
        global $wpdb;
        $wpdb->insert($this->table_process_log, array(
            'action' => $action,
            'message' => $message,
            'created_at' => current_time('mysql')
        ));
    }

    private function maybe_create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // キューテーブル（タイトル列追加）
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_queue} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            post_title varchar(500) DEFAULT '',
            status varchar(20) DEFAULT 'pending',
            priority int(11) DEFAULT 0,
            attempts int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            error_message text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql);

        // 失敗テーブル
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_failed} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            post_title varchar(500) DEFAULT '',
            error_message text,
            failed_at datetime DEFAULT CURRENT_TIMESTAMP,
            attempts int(11) DEFAULT 1,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        dbDelta($sql);

        // 統合履歴テーブル
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_merge_history} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            parent_id bigint(20) NOT NULL,
            child_id bigint(20) NOT NULL,
            child_title varchar(255),
            child_content longtext,
            child_meta longtext,
            merged_at datetime DEFAULT CURRENT_TIMESTAMP,
            merged_by bigint(20),
            PRIMARY KEY (id),
            KEY parent_id (parent_id),
            KEY child_id (child_id)
        ) $charset_collate;";
        dbDelta($sql);

        // 404ログテーブル
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_404_log} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            url varchar(500) NOT NULL,
            title varchar(500) DEFAULT '',
            referrer varchar(500) DEFAULT '',
            user_agent text,
            ip_address varchar(45) DEFAULT '',
            count int(11) DEFAULT 1,
            redirect_to bigint(20) DEFAULT NULL,
            redirect_url varchar(500) DEFAULT NULL,
            match_score int(11) DEFAULT 0,
            first_seen datetime DEFAULT CURRENT_TIMESTAMP,
            last_seen datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY url_index (url(191)),
            KEY redirect_to (redirect_to),
            KEY last_seen (last_seen)
        ) $charset_collate;";
        dbDelta($sql);

        // 補助金データベーステーブル
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_subsidy} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(500) NOT NULL,
            url varchar(500) DEFAULT '',
            deadline date DEFAULT NULL,
            prefecture varchar(100) DEFAULT '',
            city varchar(100) DEFAULT '',
            subsidy_amount varchar(200) DEFAULT '',
            status varchar(50) DEFAULT 'active',
            data_source varchar(200) DEFAULT '',
            notes text,
            matched_post_id bigint(20) DEFAULT NULL,
            match_type varchar(50) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY prefecture (prefecture),
            KEY status (status),
            KEY deadline (deadline),
            KEY matched_post_id (matched_post_id)
        ) $charset_collate;";
        dbDelta($sql);

        // プロセスログテーブル（デバッグ・監視用）
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_process_log} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            action varchar(100) NOT NULL,
            message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql);

        // リノベーション統計テーブル
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_renovation_stats} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            post_title varchar(500) DEFAULT '',
            original_char_count int(11) DEFAULT 0,
            new_char_count int(11) DEFAULT 0,
            renovated_at datetime DEFAULT CURRENT_TIMESTAMP,
            model varchar(100) DEFAULT '',
            processing_time float DEFAULT 0,
            seed_keyword varchar(200) DEFAULT '',
            keyphrase varchar(200) DEFAULT '',
            pv_before int(11) DEFAULT 0,
            pv_after_7days int(11) DEFAULT 0,
            pv_after_30days int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY renovated_at (renovated_at)
        ) $charset_collate;";
        dbDelta($sql);
        
        $this->maybe_add_columns();
    }
    
    private function maybe_add_columns() {
        global $wpdb;
        
        // 404テーブルにtitle列があるか確認
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_404_log}");
        $column_names = array_map(function($c) { return $c->Field; }, $columns);
        
        if (!in_array('title', $column_names)) {
            $wpdb->query("ALTER TABLE {$this->table_404_log} ADD COLUMN title varchar(500) DEFAULT '' AFTER url");
        }
        if (!in_array('match_score', $column_names)) {
            $wpdb->query("ALTER TABLE {$this->table_404_log} ADD COLUMN match_score int(11) DEFAULT 0 AFTER redirect_url");
        }
        
        // 補助金テーブルにmatched_post_id列があるか確認
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_subsidy}");
        $column_names = array_map(function($c) { return $c->Field; }, $columns);
        
        if (!in_array('matched_post_id', $column_names)) {
            $wpdb->query("ALTER TABLE {$this->table_subsidy} ADD COLUMN matched_post_id bigint(20) DEFAULT NULL AFTER notes");
        }
        if (!in_array('match_type', $column_names)) {
            $wpdb->query("ALTER TABLE {$this->table_subsidy} ADD COLUMN match_type varchar(50) DEFAULT NULL AFTER matched_post_id");
        }

        // キューテーブルにpost_title列があるか確認
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_queue}");
        $column_names = array_map(function($c) { return $c->Field; }, $columns);
        
        if (!in_array('post_title', $column_names)) {
            $wpdb->query("ALTER TABLE {$this->table_queue} ADD COLUMN post_title varchar(500) DEFAULT '' AFTER post_id");
        }

        // 失敗テーブルにpost_title列があるか確認
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_failed}");
        $column_names = array_map(function($c) { return $c->Field; }, $columns);
        
        if (!in_array('post_title', $column_names)) {
            $wpdb->query("ALTER TABLE {$this->table_failed} ADD COLUMN post_title varchar(500) DEFAULT '' AFTER post_id");
        }
    }

    public function add_admin_menu() {
        add_menu_page('SEO Manager', 'SEO Manager', 'manage_options', 'gi-seo-manager', array($this, 'render_page'), 'dashicons-chart-line', 30);
        add_submenu_page('gi-seo-manager', '記事統合（M&A）', '記事統合', 'manage_options', 'gi-seo-merge', array($this, 'render_merge_page'));
        add_submenu_page('gi-seo-manager', '404管理', '404管理', 'manage_options', 'gi-seo-404', array($this, 'render_404_page'));
        add_submenu_page('gi-seo-manager', 'カルテ一覧', 'カルテ一覧', 'manage_options', 'gi-seo-reports', array($this, 'render_reports_page'));
        add_submenu_page('gi-seo-manager', 'PV推移', 'PV推移', 'manage_options', 'gi-seo-pv-stats', array($this, 'render_pv_stats_page'));
        add_submenu_page('gi-seo-manager', '失敗リスト', '失敗リスト', 'manage_options', 'gi-seo-failed', array($this, 'render_failed_page'));
        add_submenu_page('gi-seo-manager', '補助金DB', '補助金DB', 'manage_options', 'gi-subsidy-db', array($this, 'render_subsidy_page'));
        add_submenu_page('gi-seo-manager', '設定', '設定', 'manage_options', 'gi-seo-settings', array($this, 'render_settings_page'));
    }

    private function get_setting($key, $default = '') {
        $settings = get_option('gi_seo_settings', array());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    private function get_current_model() {
        return $this->get_setting('model', 'gemini-3-pro-preview');
    }

    private function get_target_post_types() {
        $types = $this->get_setting('post_types', array('post'));
        return empty($types) ? array('post') : (array)$types;
    }

    // ================================================================
    // PV計測（post_metaを直接使用）
    // ================================================================
    public function output_tracking_script() {
        if (!is_singular() || is_admin()) return;
        $post_id = get_the_ID();
        if (!$post_id) return;
        $ajax_url = admin_url('admin-ajax.php');
        ?>
        <script>
        (function() {
            if (typeof navigator !== 'undefined' && /bot|crawl|spider|slurp|baidu/i.test(navigator.userAgent)) return;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo esc_url($ajax_url); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('action=gi_track_pv&post_id=<?php echo (int)$post_id; ?>');
        })();
        </script>
        <?php
    }

    public function ajax_track_pv() {
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) wp_die();
        
        // get_post_meta / update_post_meta を直接使用
        $total = (int)get_post_meta($post_id, '_gi_pv_total', true);
        update_post_meta($post_id, '_gi_pv_total', $total + 1);
        
        // 日別PVも記録
        $today = date('Y-m-d');
        $daily_key = '_gi_pv_' . $today;
        $daily = (int)get_post_meta($post_id, $daily_key, true);
        update_post_meta($post_id, $daily_key, $daily + 1);
        
        // 最終アクセス日時を記録
        update_post_meta($post_id, '_gi_last_access', current_time('mysql'));
        
        wp_die();
    }

    // PV取得ヘルパー
    private function get_post_pv($post_id) {
        return (int)get_post_meta($post_id, '_gi_pv_total', true);
    }

    // 日別PV取得
    private function get_post_daily_pv($post_id, $date) {
        return (int)get_post_meta($post_id, '_gi_pv_' . $date, true);
    }

    // PV履歴取得（過去N日分）
    private function get_post_pv_history($post_id, $days = 30) {
        $history = array();
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $pv = $this->get_post_daily_pv($post_id, $date);
            $history[] = array(
                'date' => $date,
                'pv' => $pv
            );
        }
        return $history;
    }

    // ================================================================
    // 地域名抽出ヘルパー（強化版）
    // ================================================================
    private function extract_region_from_text($text) {
        $prefectures = array(
            '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
            '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
            '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
            '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
            '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
            '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
            '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県',
            '北海道', '青森', '岩手', '宮城', '秋田', '山形', '福島',
            '茨城', '栃木', '群馬', '埼玉', '千葉', '東京', '神奈川',
            '新潟', '富山', '石川', '福井', '山梨', '長野', '岐阜',
            '静岡', '愛知', '三重', '滋賀', '京都', '大阪', '兵庫',
            '奈良', '和歌山', '鳥取', '島根', '岡山', '広島', '山口',
            '徳島', '香川', '愛媛', '高知', '福岡', '佐賀', '長崎',
            '熊本', '大分', '宮崎', '鹿児島', '沖縄'
        );
        
        $cities = array(
            '札幌', '仙台', 'さいたま', '千葉', '横浜', '川崎', '相模原',
            '新潟', '静岡', '浜松', '名古屋', '京都', '大阪', '堺', '神戸',
            '岡山', '広島', '北九州', '福岡', '熊本'
        );
        
        $found_regions = array();
        
        foreach ($prefectures as $pref) {
            if (mb_strpos($text, $pref) !== false) {
                $normalized = preg_replace('/(都|道|府|県)$/', '', $pref);
                $found_regions[] = $normalized;
            }
        }
        
        foreach ($cities as $city) {
            if (mb_strpos($text, $city) !== false) {
                $found_regions[] = $city;
            }
        }
        
        return array_unique($found_regions);
    }

    private function normalize_region($region) {
        return preg_replace('/(都|道|府|県|市|区|町|村)$/', '', $region);
    }

    // ================================================================
    // 関連記事取得（地域・キーワード考慮版）
    // ================================================================
    private function get_related_posts_list($post_id) {
        $post = get_post($post_id);
        if (!$post) return '';
        
        // 現在の記事から地域名を抽出
        $current_regions = $this->extract_region_from_text($post->post_title . ' ' . $post->post_content);
        $current_seed = $this->extract_seed_keyword_fallback($post->post_title);
        
        // タグを取得
        $current_tags = wp_get_post_tags($post_id, array('fields' => 'names'));
        
        // カテゴリーを取得
        $categories = get_the_category($post_id);
        $category_ids = !empty($categories) ? wp_list_pluck($categories, 'term_id') : array();
        
        // 候補記事を収集
        $candidates = array();
        
        // 同じカテゴリーの記事を取得
        $args = array(
            'post_type' => $post->post_type,
            'post__not_in' => array($post_id),
            'posts_per_page' => 50,
            'post_status' => 'publish'
        );
        
        if (!empty($category_ids)) {
            $args['category__in'] = $category_ids;
        }
        
        $query = new WP_Query($args);
        
        foreach ($query->posts as $p) {
            $score = 0;
            $p_regions = $this->extract_region_from_text($p->post_title);
            
            // 地域の一致をチェック（最重要）
            if (!empty($current_regions) && !empty($p_regions)) {
                $common_regions = array_intersect(
                    array_map(array($this, 'normalize_region'), $current_regions),
                    array_map(array($this, 'normalize_region'), $p_regions)
                );
                
                if (!empty($common_regions)) {
                    $score += 50; // 地域一致で大幅加点
                } else {
                    // 地域が異なる場合は候補から除外
                    continue;
                }
            }
            
            // タグの一致をチェック
            $p_tags = wp_get_post_tags($p->ID, array('fields' => 'names'));
            if (!empty($current_tags) && !empty($p_tags)) {
                $common_tags = array_intersect($current_tags, $p_tags);
                $score += count($common_tags) * 10;
            }
            
            // タイトルの類似度をチェック
            $title_similarity = $this->calculate_title_similarity($post->post_title, $p->post_title);
            $score += $title_similarity * 20;
            
            // PVを加味
            $pv = $this->get_post_pv($p->ID);
            $score += min($pv / 100, 10);
            
            if ($score > 0) {
                $candidates[$p->ID] = array(
                    'post' => $p,
                    'score' => $score,
                    'regions' => $p_regions
                );
            }
        }
        
        wp_reset_postdata();
        
        // スコア順にソート
        uasort($candidates, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        // 上位5件を取得
        $top_candidates = array_slice($candidates, 0, 5, true);
        
        $links = array();
        foreach ($top_candidates as $id => $data) {
            $p = $data['post'];
            $links[] = '- 「' . $p->post_title . '」 → ' . get_permalink($p->ID);
        }
        
        return implode("\n", $links);
    }

    // ================================================================
    // 類似度スコア計算（地域考慮版）
    // ================================================================
    private function calculate_similarity_score($title1, $title2, $content1 = '', $content2 = '') {
        $score = 0;
        
        $regions1 = $this->extract_region_from_text($title1 . ' ' . $content1);
        $regions2 = $this->extract_region_from_text($title2 . ' ' . $content2);
        
        if (!empty($regions1) && !empty($regions2)) {
            $common_regions = array_intersect(
                array_map(array($this, 'normalize_region'), $regions1),
                array_map(array($this, 'normalize_region'), $regions2)
            );
            
            if (empty($common_regions)) {
                return 5;
            }
        }
        
        $title1_clean = $this->clean_title_for_comparison($title1);
        $title2_clean = $this->clean_title_for_comparison($title2);
        
        $max_len = max(mb_strlen($title1_clean), mb_strlen($title2_clean));
        if ($max_len > 0) {
            $distance = levenshtein(
                mb_convert_encoding($title1_clean, 'ASCII', 'UTF-8'),
                mb_convert_encoding($title2_clean, 'ASCII', 'UTF-8')
            );
            $title_similarity = 1 - ($distance / $max_len);
            $score += $title_similarity * 50;
        }
        
        $words1 = $this->extract_meaningful_words($title1 . ' ' . $content1);
        $words2 = $this->extract_meaningful_words($title2 . ' ' . $content2);
        $common_words = array_intersect($words1, $words2);
        $score += min(count($common_words) * 5, 30);
        
        $subsidy_name1 = $this->extract_subsidy_name($title1);
        $subsidy_name2 = $this->extract_subsidy_name($title2);
        if (!empty($subsidy_name1) && !empty($subsidy_name2) && $subsidy_name1 === $subsidy_name2) {
            $score += 20;
        }
        
        return min($score, 100);
    }

    private function clean_title_for_comparison($title) {
        $title = preg_replace('/【.+?】/', '', $title);
        $title = preg_replace('/「.+?」/', '', $title);
        $title = preg_replace('/\(.+?\)/', '', $title);
        $title = preg_replace('/（.+?）/', '', $title);
        $title = preg_replace('/\d{4}年(度)?/', '', $title);
        $title = preg_replace('/(とは|について|まとめ|解説|完全ガイド|徹底解説)/', '', $title);
        return trim($title);
    }

    private function extract_meaningful_words($text) {
        $text = strip_tags($text);
        $text = preg_replace('/[、。！？\s\n\r]+/u', ' ', $text);
        $words = preg_split('/\s+/', $text);
        $words = array_filter($words, function($w) {
            return mb_strlen($w) >= 2;
        });
        return array_unique($words);
    }

    private function extract_subsidy_name($title) {
        $patterns = array(
            '/(.+?(?:助成金|補助金|給付金|支援金|奨励金))/',
            '/「(.+?)」/',
            '/【(.+?)】/'
        );
        foreach ($patterns as $p) {
            if (preg_match($p, $title, $m)) {
                return trim($m[1]);
            }
        }
        return '';
    }

    // ================================================================
    // URLからタイトルを推測
    // ================================================================
    private function extract_title_from_url($url) {
        $path = parse_url($url, PHP_URL_PATH);
        if (empty($path)) return '';
        
        $slug = basename($path);
        $slug = urldecode($slug);
        
        if (preg_match('/^\d+$/', $slug)) return '';
        
        $title = str_replace(array('-', '_'), ' ', $slug);
        $title = preg_replace('/\.(html?|php|asp)$/i', '', $title);
        
        return trim($title);
    }

    // ================================================================
    // タイトル類似度計算
    // ================================================================
    private function calculate_title_similarity($str1, $str2) {
        $str1 = mb_strtolower($this->clean_title_for_comparison($str1));
        $str2 = mb_strtolower($this->clean_title_for_comparison($str2));
        
        if ($str1 === $str2) return 1.0;
        
        $words1 = $this->extract_meaningful_words($str1);
        $words2 = $this->extract_meaningful_words($str2);
        
        if (empty($words1) || empty($words2)) return 0;
        
        $common = count(array_intersect($words1, $words2));
        $total = count(array_unique(array_merge($words1, $words2)));
        
        return $total > 0 ? $common / $total : 0;
    }

    // ================================================================
    // 404自動リダイレクト用マッチング
    // ================================================================
    private function find_best_redirect_match($url, $extracted_title = '') {
        global $wpdb;
        
        $candidates = array();
        $search_terms = array();
        
        $path = parse_url($url, PHP_URL_PATH);
        $slug = basename($path);
        $slug = urldecode($slug);
        
        $has_japanese = preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FFF}]/u', $slug);
        
        if ($has_japanese) {
            $search_terms[] = preg_replace('/[^a-zA-Z0-9\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FFF}]+/u', ' ', $slug);
        }
        
        if (!empty($extracted_title)) {
            $search_terms[] = $extracted_title;
        }
        
        $parts = preg_split('/[-_]/', $slug);
        foreach ($parts as $part) {
            $part = trim($part);
            if (mb_strlen($part) >= 2) {
                $search_terms[] = $part;
            }
        }
        
        $search_terms = array_unique(array_filter($search_terms));
        
        if (empty($search_terms)) {
            return null;
        }
        
        foreach ($search_terms as $term) {
            $args = array(
                'post_type' => $this->get_target_post_types(),
                'post_status' => 'publish',
                's' => $term,
                'posts_per_page' => 20
            );
            
            $query = new WP_Query($args);
            
            foreach ($query->posts as $post) {
                $pv = $this->get_post_pv($post->ID);
                $title_similarity = $this->calculate_title_similarity($slug, $post->post_title);
                
                $score = $title_similarity * 100 + min($pv / 10, 50);
                
                if (mb_stripos($post->post_title, $term) !== false) {
                    $score += 30;
                }
                
                $candidates[$post->ID] = array(
                    'post' => $post,
                    'score' => $score,
                    'pv' => $pv,
                    'similarity' => $title_similarity
                );
            }
            
            wp_reset_postdata();
        }
        
        if (empty($candidates)) {
            return null;
        }
        
        uasort($candidates, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        $best = reset($candidates);
        
        if ($best['score'] < 30) {
            return null;
        }
        
        return array(
            'post_id' => $best['post']->ID,
            'title' => $best['post']->post_title,
            'score' => round($best['score']),
            'pv' => $best['pv'],
            'url' => get_permalink($best['post']->ID)
        );
    }

    // ================================================================
    // 404検出
    // ================================================================
    public function detect_404() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) return;
        if (!is_404()) return;

        global $wpdb;
        $url = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';
        if (empty($url)) return;

        $exclude_patterns = array(
            '/wp-admin', '/wp-content', '/wp-includes', '/favicon.ico', '/robots.txt',
            '/sitemap', '.xml', '.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.ico',
            '.woff', '.woff2', '/feed'
        );
        foreach ($exclude_patterns as $pattern) {
            if (stripos($url, $pattern) !== false) return;
        }

        $referrer = isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field($_SERVER['HTTP_REFERER']) : '';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(substr($_SERVER['HTTP_USER_AGENT'], 0, 500)) : '';
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        
        $extracted_title = $this->extract_title_from_url($url);

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_404_log} WHERE url = %s", $url
        ));

        if ($existing) {
            $update_data = array(
                'count' => $existing->count + 1,
                'last_seen' => current_time('mysql'),
                'referrer' => $referrer ?: $existing->referrer,
                'user_agent' => $user_agent ?: $existing->user_agent,
                'ip_address' => $ip_address
            );
            
            if (empty($existing->title) && !empty($extracted_title)) {
                $update_data['title'] = $extracted_title;
            }
            
            $wpdb->update($this->table_404_log, $update_data, array('id' => $existing->id));
        } else {
            $wpdb->insert($this->table_404_log, array(
                'url' => $url,
                'title' => $extracted_title,
                'referrer' => $referrer,
                'user_agent' => $user_agent,
                'ip_address' => $ip_address,
                'count' => 1,
                'first_seen' => current_time('mysql'),
                'last_seen' => current_time('mysql')
            ));
        }
    }

    public function handle_redirects() {
        if (is_admin() || wp_doing_ajax()) return;

        global $wpdb;
        $url = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';
        if (empty($url)) return;

        if (is_singular()) {
            $post_id = get_queried_object_id();
            if ($post_id) {
                $redirect_to = get_post_meta($post_id, '_gi_redirect_to', true);
                if ($redirect_to) {
                    $target_url = get_permalink($redirect_to);
                    if ($target_url) {
                        wp_redirect($target_url, 301);
                        exit;
                    }
                }
            }
        }

        $redirect = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_404_log} WHERE url = %s AND (redirect_to IS NOT NULL OR redirect_url IS NOT NULL)",
            $url
        ));

        if ($redirect) {
            $target = '';
            if ($redirect->redirect_to) {
                $target = get_permalink($redirect->redirect_to);
            } elseif ($redirect->redirect_url) {
                $target = $redirect->redirect_url;
            }
            if ($target && !empty($target)) {
                wp_redirect($target, 301);
                exit;
            }
        }
    }

    // ================================================================
    // 404自動リダイレクト
    // ================================================================
    public function ajax_auto_redirect_single() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;
        
        $log_id = intval($_POST['log_id'] ?? 0);
        if (!$log_id) {
            wp_send_json_error('ログIDが指定されていません');
            return;
        }
        
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_404_log} WHERE id = %d", $log_id
        ));
        
        if (!$log) {
            wp_send_json_error('ログが見つかりません');
            return;
        }
        
        $match = $this->find_best_redirect_match($log->url, $log->title);
        
        if ($match) {
            $wpdb->update($this->table_404_log, array(
                'redirect_to' => $match['post_id'],
                'match_score' => $match['score']
            ), array('id' => $log_id));
            
            wp_send_json_success(array(
                'message' => 'リダイレクト設定完了',
                'match' => $match
            ));
        } else {
            wp_send_json_error('適切なリダイレクト先が見つかりませんでした');
        }
    }

    // 選択した404ログに対して一括自動リダイレクト
    public function ajax_auto_redirect_selected() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;
        
        $ids = isset($_POST['ids']) ? array_map('intval', (array)$_POST['ids']) : array();
        $min_score = intval($_POST['min_score'] ?? 50);
        
        if (empty($ids)) {
            wp_send_json_error('対象が選択されていません');
            return;
        }
        
        $processed = 0;
        $success = 0;
        $results = array();
        
        foreach ($ids as $log_id) {
            $log = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->table_404_log} WHERE id = %d", $log_id
            ));
            
            if (!$log) continue;
            
            $processed++;
            $match = $this->find_best_redirect_match($log->url, $log->title);
            
            if ($match && $match['score'] >= $min_score) {
                $wpdb->update($this->table_404_log, array(
                    'redirect_to' => $match['post_id'],
                    'match_score' => $match['score']
                ), array('id' => $log_id));
                
                $success++;
                $results[] = array(
                    'url' => $log->url,
                    'redirect_to' => $match['title'],
                    'score' => $match['score']
                );
            }
        }
        
        wp_send_json_success(array(
            'message' => "{$processed}件処理、{$success}件リダイレクト設定",
            'processed' => $processed,
            'success' => $success,
            'results' => $results
        ));
    }

    public function ajax_auto_redirect_all() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;
        
        $min_score = intval($_POST['min_score'] ?? 50);
        $limit = intval($_POST['limit'] ?? 100);
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_404_log} 
             WHERE redirect_to IS NULL AND redirect_url IS NULL 
             ORDER BY count DESC 
             LIMIT %d",
            $limit
        ));
        
        $processed = 0;
        $success = 0;
        $results = array();
        
        foreach ($logs as $log) {
            $processed++;
            $match = $this->find_best_redirect_match($log->url, $log->title);
            
            if ($match && $match['score'] >= $min_score) {
                $wpdb->update($this->table_404_log, array(
                    'redirect_to' => $match['post_id'],
                    'match_score' => $match['score']
                ), array('id' => $log->id));
                
                $success++;
                $results[] = array(
                    'url' => $log->url,
                    'redirect_to' => $match['title'],
                    'score' => $match['score']
                );
            }
        }
        
        wp_send_json_success(array(
            'message' => "{$processed}件処理、{$success}件リダイレクト設定",
            'processed' => $processed,
            'success' => $success,
            'results' => array_slice($results, 0, 20)
        ));
    }

    // リダイレクト設定解除
    public function ajax_clear_redirect() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;
        
        $log_id = intval($_POST['log_id'] ?? 0);
        if (!$log_id) {
            wp_send_json_error('ログIDが指定されていません');
            return;
        }
        
        $result = $wpdb->update($this->table_404_log, array(
            'redirect_to' => null,
            'redirect_url' => null,
            'match_score' => 0
        ), array('id' => $log_id));
        
        if ($result === false) {
            wp_send_json_error('解除に失敗しました');
            return;
        }
        
        wp_send_json_success('リダイレクト設定を解除しました');
    }

    // 一括リダイレクト解除
    public function ajax_bulk_clear_redirect() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;
        
        $ids = isset($_POST['ids']) ? array_map('intval', (array)$_POST['ids']) : array();
        
        if (empty($ids)) {
            wp_send_json_error('対象が選択されていません');
            return;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $cleared = $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_404_log} SET redirect_to = NULL, redirect_url = NULL, match_score = 0 WHERE id IN ($placeholders)",
            ...$ids
        ));
        
        wp_send_json_success("{$cleared}件のリダイレクト設定を解除しました");
    }

    // 404ログ削除
    public function ajax_delete_404_logs() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;
        
        $ids = isset($_POST['ids']) ? array_map('intval', (array)$_POST['ids']) : array();
        
        if (empty($ids)) {
            wp_send_json_error('対象が選択されていません');
            return;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_404_log} WHERE id IN ($placeholders)",
            ...$ids
        ));
        
        wp_send_json_success("{$deleted}件削除しました");
    }

    // ================================================================
    // Googleサジェスト取得
    // ================================================================
    public function get_google_suggest($keyword) {
        if (empty($keyword)) return array();

        $url = 'https://suggestqueries.google.com/complete/search?client=firefox&hl=ja&q=' . urlencode($keyword);
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array('User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
        ));

        if (is_wp_error($response)) return array();

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $suggests = array();
        if (is_array($data) && isset($data[1]) && is_array($data[1])) {
            $suggests = array_filter($data[1], function($s) use ($keyword) {
                return $s !== $keyword && mb_strlen($s) > 2;
            });
        }

        return array_slice(array_values($suggests), 0, 15);
    }

    // ================================================================
    // キーワード抽出
    // ================================================================
    public function extract_seed_keyword($title) {
        $api_key = $this->get_setting('api_key');
        if (empty($api_key)) return $this->extract_seed_keyword_fallback($title);

        $prompt = "以下のタイトルからメインの検索キーワードを1つだけ抽出。キーワードのみ出力。\n\nタイトル: {$title}";
        $result = $this->call_gemini($prompt, 0.1);

        if ($result && !isset($result['error']) && !empty($result['content'])) {
            return trim(preg_replace('/[「」『』【】]/u', '', $result['content']));
        }
        return $this->extract_seed_keyword_fallback($title);
    }

    private function extract_seed_keyword_fallback($title) {
        $patterns = array('/(.+?(?:助成金|補助金|給付金|支援金))/', '/「(.+?)」/', '/【(.+?)】/');
        foreach ($patterns as $p) {
            if (preg_match($p, $title, $m)) return $m[1];
        }
        $clean = preg_replace('/(とは|について|の解説|まとめ|完全ガイド|徹底解説|\d{4}年版?)/u', '', $title);
        return trim(mb_substr($clean, 0, 20));
    }

    public function extract_keyphrase($title, $content) {
        $api_key = $this->get_setting('api_key');
        if (empty($api_key)) return $this->extract_seed_keyword_fallback($title);

        $excerpt = mb_substr(strip_tags($content), 0, 1000);
        $prompt = "以下の記事から狙うべき検索キーフレーズを1つ抽出。2〜4語の複合キーワード。キーフレーズのみ出力。\n\nタイトル: {$title}\n\n本文: {$excerpt}";
        $result = $this->call_gemini($prompt, 0.2);

        return ($result && !isset($result['error']) && !empty($result['content'])) 
            ? trim($result['content']) 
            : $this->extract_seed_keyword_fallback($title);
    }

    public function extract_important_keywords($title, $content) {
        $api_key = $this->get_setting('api_key');
        if (empty($api_key)) return array();

        $excerpt = mb_substr(strip_tags($content), 0, 2000);
        $prompt = "以下の記事からSEO上重要なキーワードを5〜10個抽出。JSON配列形式で出力。\n\nタイトル: {$title}\n\n本文: {$excerpt}";
        $result = $this->call_gemini($prompt, 0.1);

        if ($result && !isset($result['error']) && preg_match('/\[.+\]/s', $result['content'], $m)) {
            $kw = json_decode($m[0], true);
            if (is_array($kw)) return $kw;
        }
        return array();
    }

    // ================================================================
    // Gemini API（gemini-3-pro-preview対応）
    // ================================================================
    public function call_gemini($prompt, $temperature = null) {
        $api_key = $this->get_setting('api_key');
        if (empty($api_key)) return array('error' => 'APIキー未設定');

        $model = $this->get_current_model();
        $temperature = $temperature ?? (float)$this->get_setting('temperature', 0.7);

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

        $response = wp_remote_post($url, array(
            'timeout' => 180,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'contents' => array(array('parts' => array(array('text' => $prompt)))),
                'generationConfig' => array(
                    'temperature' => $temperature,
                    'maxOutputTokens' => 65536
                )
            ))
        ));

        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200) {
            return array('error' => $body['error']['message'] ?? "HTTP {$code}");
        }

        if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return array(
                'content' => $body['candidates'][0]['content']['parts'][0]['text'],
                'model' => $model
            );
        }

        return array('error' => 'レスポンス解析失敗');
    }

    // ================================================================
    // リノベーション（余計な文字除去強化版）
    // ================================================================
    public function ajax_renovate() {
        check_ajax_referer('gi_seo_nonce', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error('記事ID未指定');
            return;
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('記事が見つかりません');
            return;
        }

        $start = microtime(true);

        $seed = $this->extract_seed_keyword($post->post_title);
        $keyphrase = $this->extract_keyphrase($post->post_title, $post->post_content);
        $keywords = $this->extract_important_keywords($post->post_title, $post->post_content);
        $suggests = $this->get_google_suggest($seed);
        $related = $this->get_related_posts_list($post_id);

        $prompt = $this->build_renovation_prompt($post, $seed, $keyphrase, $keywords, $suggests, $related);
        $result = $this->call_gemini($prompt);

        if (isset($result['error'])) {
            $this->log_failed($post_id, $post->post_title, $result['error']);
            wp_send_json_error($result['error']);
            return;
        }

        $new_content = $this->extract_content($result['content']);
        if (empty($new_content)) {
            $this->log_failed($post_id, $post->post_title, 'コンテンツ抽出失敗');
            wp_send_json_error('コンテンツ抽出失敗');
            return;
        }

        // バックアップ
        update_post_meta($post_id, '_gi_content_backup', $post->post_content);
        update_post_meta($post_id, '_gi_backup_date', current_time('mysql'));
        if (!get_post_meta($post_id, '_gi_original_content', true)) {
            update_post_meta($post_id, '_gi_original_content', $post->post_content);
            update_post_meta($post_id, '_gi_original_title', $post->post_title);
        }

        $new_title = $this->extract_title($result['content']);
        $meta_desc = $this->extract_meta_desc($result['content']);

        $update = array('ID' => $post_id, 'post_content' => $new_content);
        if ($new_title) $update['post_title'] = $new_title;
        wp_update_post($update);

        if ($meta_desc) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_desc);
            update_post_meta($post_id, '_gi_meta_description', $meta_desc);
        }

        $time = round(microtime(true) - $start, 2);

        $report = array(
            'date' => current_time('mysql'),
            'seed_keyword' => $seed,
            'keyphrase' => $keyphrase,
            'keywords' => $keywords,
            'suggests' => $suggests,
            'original_title' => $post->post_title,
            'new_title' => $new_title ?: $post->post_title,
            'meta_description' => $meta_desc,
            'original_char_count' => mb_strlen(strip_tags($post->post_content)),
            'new_char_count' => mb_strlen(strip_tags($new_content)),
            'model' => $result['model'],
            'processing_time' => $time,
            'version' => $this->version
        );

        update_post_meta($post_id, '_gi_renovation_report', $report);
        update_post_meta($post_id, '_gi_renovated_at', current_time('mysql'));
        update_post_meta($post_id, '_gi_renovation_count', (int)get_post_meta($post_id, '_gi_renovation_count', true) + 1);

        // リノベーション統計を記録
        $this->record_renovation_stats($post_id, $post->post_title, $report);

        wp_send_json_success(array(
            'post_id' => $post_id,
            'seed_keyword' => $seed,
            'keyphrase' => $keyphrase,
            'suggests_count' => count($suggests),
            'original_char_count' => $report['original_char_count'],
            'new_char_count' => $report['new_char_count'],
            'meta_description' => $meta_desc,
            'processing_time' => $time
        ));
    }

    // リノベーション統計を記録
    private function record_renovation_stats($post_id, $post_title, $report) {
        global $wpdb;
        
        $pv_before = $this->get_post_pv($post_id);
        
        $wpdb->insert($this->table_renovation_stats, array(
            'post_id' => $post_id,
            'post_title' => $post_title,
            'original_char_count' => $report['original_char_count'],
            'new_char_count' => $report['new_char_count'],
            'renovated_at' => current_time('mysql'),
            'model' => $report['model'],
            'processing_time' => $report['processing_time'],
            'seed_keyword' => $report['seed_keyword'],
            'keyphrase' => $report['keyphrase'],
            'pv_before' => $pv_before
        ));
    }

    private function build_renovation_prompt($post, $seed, $keyphrase, $keywords, $suggests, $related) {
        $custom = $this->get_setting('custom_prompt');
        if (!empty($custom)) {
            return str_replace(
                array('{title}', '{content}', '{seed_keyword}', '{keyphrase}', '{keywords}', '{suggests}', '{related_posts}'),
                array($post->post_title, $post->post_content, $seed, $keyphrase, implode(', ', $keywords), implode(', ', $suggests), $related),
                $custom
            );
        }

        $char_count = mb_strlen(strip_tags($post->post_content));
        $expansion = '';
        if ($char_count < 2000) {
            $expansion = "\n\n【重要】元記事が短いため、4000文字以上になるよう大幅に加筆してください。";
        } elseif ($char_count < 3500) {
            $expansion = "\n\n【重要】5000文字以上を目標に内容を充実させてください。";
        }

        $suggests_text = !empty($suggests) ? implode("\n- ", $suggests) : '（取得なし）';
        $keywords_text = !empty($keywords) ? implode(', ', $keywords) : '（なし）';

        $internal_links = '';
        if (!empty($related)) {
            $internal_links = "\n\n## 内部リンク設置（地域・キーワードが一致する記事のみ）\n関連性の高い記事から2〜3記事を本文中に自然にリンク：\n{$related}\n\n※上記リストが空の場合、または関連性が低いと判断される場合は内部リンクを設置しないでください。";
        }

        return "以下の記事をリノベーションしてください。

【絶対厳守ルール】
- 前置き・挨拶・説明文は一切不要
- 「承知しました」「SEOの専門家として」などの文言は絶対に含めない
- HTMLコードのみを出力する
- 出力の最初の文字は必ず「<」で始める
- 内部リンクは地域やテーマが一致する場合のみ設置する

## 対象記事
タイトル: {$post->post_title}

本文:
{$post->post_content}

## キーワード
- シード: {$seed}
- キーフレーズ: {$keyphrase}
- 重要語: {$keywords_text}

## Googleサジェスト（必ず網羅）
- {$suggests_text}
{$expansion}
{$internal_links}

## デザインルール
シンプルな白黒ベースのHTMLで出力。以下のスタイルを使用：

**ポイントボックス**
<div style=\"background:#f5f5f5; border:1px solid #333; padding:20px; margin:20px 0;\">
<h4 style=\"margin:0 0 10px 0; color:#333;\">■ ポイント</h4>
<p style=\"margin:0;\">内容</p>
</div>

**注意ボックス**
<div style=\"background:#fff; border-left:4px solid #333; padding:15px; margin:20px 0;\">
<strong>注意：</strong>内容
</div>

**テーブル**
<table style=\"width:100%; border-collapse:collapse; margin:20px 0;\">
<tr style=\"background:#333; color:#fff;\"><th style=\"padding:10px; border:1px solid #333;\">項目</th><th style=\"padding:10px; border:1px solid #333;\">内容</th></tr>
<tr><td style=\"padding:10px; border:1px solid #ddd;\">項目名</td><td style=\"padding:10px; border:1px solid #ddd;\">内容</td></tr>
</table>

**Q&A**
<div style=\"margin:20px 0;\">
<div style=\"background:#333; color:#fff; padding:10px 15px;\">Q. 質問</div>
<div style=\"background:#f5f5f5; padding:15px; border:1px solid #ddd; border-top:none;\">A. 回答</div>
</div>

## 出力形式（厳守）
<!-- META_DESCRIPTION: 120文字以内の要約 -->
<!-- TITLE: 改善後タイトル（必要な場合） -->
<h2>最初の見出し</h2>
（以降HTMLコード本文）";
    }

    /**
     * AIレスポンスからコンテンツを抽出（余計な文字除去強化版）
     */
    private function extract_content($response) {
        // メタ情報を除去
        $content = preg_replace('/<!--\s*META_DESCRIPTION:.*?-->/s', '', $response);
        $content = preg_replace('/<!--\s*TITLE:.*?-->/s', '', $content);
        
        // コードブロックマーカーを除去
        $content = preg_replace('/```html?\s*/i', '', $content);
        $content = preg_replace('/```\s*$/', '', $content);
        
        // AIの前置き文を除去（複数パターン対応）
        $unwanted_patterns = array(
            // 日本語の前置き
            '/^.*?承知[いし]たしました[。．\s]*/u',
            '/^.*?かしこまりました[。．\s]*/u',
            '/^.*?了解[いし]たしました[。．\s]*/u',
            '/^.*?SEOの専門家として.*?[。．\n]/u',
            '/^.*?以下[がは].*?です[。．\n]/u',
            '/^.*?リノベーション[をし].*?[。．\n]/u',
            '/^.*?全面的に.*?[。．\n]/u',
            '/^.*?読者の検索意図.*?[。．\n]/u',
            '/^.*?情報の網羅性.*?[。．\n]/u',
            '/^.*?文字数を.*?[。．\n]/u',
            '/^.*?具体的な.*?盛り込[みん].*?[。．\n]/u',
            
            // 英語の前置き
            '/^.*?Here is.*?\n/i',
            '/^.*?I\'ll.*?\n/i',
            '/^.*?Let me.*?\n/i',
            '/^.*?Sure.*?\n/i',
            '/^.*?Certainly.*?\n/i',
            '/^.*?Of course.*?\n/i',
            
            // 区切り線
            '/^[\s\-—─=]+\n*/u',
        );
        
        foreach ($unwanted_patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        
        // HTMLタグが始まる位置を探す
        $html_start = strpos($content, '<');
        if ($html_start !== false && $html_start > 0) {
            // HTMLタグより前のテキストをチェック
            $before_html = substr($content, 0, $html_start);
            // 日本語や英語の文章が含まれている場合は除去
            if (preg_match('/[ぁ-んァ-ンa-zA-Z]{3,}/u', $before_html)) {
                $content = substr($content, $html_start);
            }
        }
        
        // 末尾の余計な文章を除去
        $content = preg_replace('/\n+[\s\-—─=]*$/u', '', $content);
        
        // 末尾に「以上」「---」などがある場合は除去
        $content = preg_replace('/\n*[\-—─=]+\s*$/u', '', $content);
        $content = preg_replace('/\n*以上[です。]*\s*$/u', '', $content);
        
        return trim($content);
    }

    private function extract_title($response) {
        if (preg_match('/<!--\s*TITLE:\s*(.+?)\s*-->/s', $response, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function extract_meta_desc($response) {
        if (preg_match('/<!--\s*META_DESCRIPTION:\s*(.+?)\s*-->/s', $response, $m)) {
            $desc = trim($m[1]);
            return mb_strlen($desc) > 120 ? mb_substr($desc, 0, 117) . '...' : $desc;
        }
        return null;
    }

    private function log_failed($post_id, $post_title, $error) {
        global $wpdb;
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_failed} WHERE post_id = %d", $post_id
        ));

        if ($existing) {
            $wpdb->update($this->table_failed, array(
                'post_title' => $post_title,
                'error_message' => $error,
                'failed_at' => current_time('mysql'),
                'attempts' => $existing->attempts + 1
            ), array('post_id' => $post_id));
        } else {
            $wpdb->insert($this->table_failed, array(
                'post_id' => $post_id,
                'post_title' => $post_title,
                'error_message' => $error,
                'failed_at' => current_time('mysql')
            ));
        }
    }

    // ================================================================
    // 記事一覧
    // ================================================================
    public function ajax_get_posts() {
        check_ajax_referer('gi_seo_nonce', 'nonce');

        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 100);
        $search = sanitize_text_field($_POST['search'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? 'all');
        $sort = sanitize_text_field($_POST['sort'] ?? 'date_desc');

        $args = array(
            'post_type' => $this->get_target_post_types(),
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'offset' => ($page - 1) * $per_page
        );

        if ($search) $args['s'] = $search;

        if ($status === 'renovated') {
            $args['meta_query'] = array(array('key' => '_gi_renovated_at', 'compare' => 'EXISTS'));
        } elseif ($status === 'not_renovated') {
            $args['meta_query'] = array(array('key' => '_gi_renovated_at', 'compare' => 'NOT EXISTS'));
        }

        switch ($sort) {
            case 'date_asc':
                $args['orderby'] = 'date';
                $args['order'] = 'ASC';
                break;
            case 'pv_desc':
                $args['meta_key'] = '_gi_pv_total';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'renovated_desc':
                $args['meta_key'] = '_gi_renovated_at';
                $args['orderby'] = 'meta_value';
                $args['order'] = 'DESC';
                if ($status !== 'renovated') {
                    $args['meta_query'] = array(array('key' => '_gi_renovated_at', 'compare' => 'EXISTS'));
                }
                break;
            case 'renovated_asc':
                $args['meta_key'] = '_gi_renovated_at';
                $args['orderby'] = 'meta_value';
                $args['order'] = 'ASC';
                if ($status !== 'renovated') {
                    $args['meta_query'] = array(array('key' => '_gi_renovated_at', 'compare' => 'EXISTS'));
                }
                break;
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
        }

        $query = new WP_Query($args);
        $posts = array();

        foreach ($query->posts as $p) {
            $renovated_at = get_post_meta($p->ID, '_gi_renovated_at', true);
            $renovation_count = get_post_meta($p->ID, '_gi_renovation_count', true);
            $posts[] = array(
                'id' => $p->ID,
                'title' => $p->post_title,
                'date' => $p->post_date,
                'char_count' => mb_strlen(strip_tags($p->post_content)),
                'pv' => $this->get_post_pv($p->ID),
                'renovated' => !empty($renovated_at),
                'renovated_at' => $renovated_at ? substr($renovated_at, 0, 10) : null,
                'renovation_count' => (int)$renovation_count,
                'has_report' => !empty(get_post_meta($p->ID, '_gi_renovation_report', true)),
                'url' => get_permalink($p->ID)
            );
        }

        wp_send_json_success(array(
            'posts' => $posts,
            'total' => $query->found_posts,
            'pages' => ceil($query->found_posts / $per_page)
        ));
    }

    // ================================================================
    // キュー管理（強化版）
    // ================================================================
    public function ajax_add_to_queue() {
        check_ajax_referer('gi_seo_nonce', 'nonce');

        $post_ids = isset($_POST['post_ids']) ? array_map('intval', (array)$_POST['post_ids']) : array();
        $force = isset($_POST['force']) && $_POST['force'] === 'true';
        
        if (empty($post_ids)) {
            wp_send_json_error('記事未選択');
            return;
        }

        global $wpdb;
        $added = 0;
        $already_renovated = array();

        foreach ($post_ids as $pid) {
            $post = get_post($pid);
            if (!$post) continue;
            
            // リノベーション済みチェック
            $is_renovated = !empty(get_post_meta($pid, '_gi_renovated_at', true));
            
            if ($is_renovated && !$force) {
                $already_renovated[] = array(
                    'id' => $pid,
                    'title' => $post->post_title
                );
                continue;
            }
            
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_queue} WHERE post_id = %d AND status IN ('pending', 'processing')",
                $pid
            ));
            if (!$exists) {
                $wpdb->insert($this->table_queue, array(
                    'post_id' => $pid,
                    'post_title' => $post->post_title,
                    'status' => 'pending',
                    'created_at' => current_time('mysql')
                ));
                $added++;
            }
        }

        if (!empty($already_renovated) && !$force) {
            wp_send_json_success(array(
                'message' => "{$added}件追加",
                'added' => $added,
                'already_renovated' => $already_renovated,
                'need_confirm' => true
            ));
        } else {
            wp_send_json_success(array('message' => "{$added}件追加", 'added' => $added));
        }
    }

    // URL一括追加
    public function ajax_add_urls_to_queue() {
        check_ajax_referer('gi_seo_nonce', 'nonce');

        $urls = isset($_POST['urls']) ? sanitize_textarea_field($_POST['urls']) : '';
        $force = isset($_POST['force']) && $_POST['force'] === 'true';
        
        if (empty($urls)) {
            wp_send_json_error('URLが入力されていません');
            return;
        }

        $url_list = preg_split('/[\r\n]+/', $urls);
        $url_list = array_filter(array_map('trim', $url_list));
        
        global $wpdb;
        $added = 0;
        $not_found = array();
        $already_renovated = array();
        
        foreach ($url_list as $url) {
            $post_id = url_to_postid($url);
            
            if (!$post_id) {
                // URLからスラッグを抽出して検索
                $path = parse_url($url, PHP_URL_PATH);
                $slug = basename(rtrim($path, '/'));
                
                $post = get_page_by_path($slug, OBJECT, $this->get_target_post_types());
                if ($post) {
                    $post_id = $post->ID;
                }
            }
            
            if (!$post_id) {
                $not_found[] = $url;
                continue;
            }
            
            $post = get_post($post_id);
            if (!$post) {
                $not_found[] = $url;
                continue;
            }
            
            // リノベーション済みチェック
            $is_renovated = !empty(get_post_meta($post_id, '_gi_renovated_at', true));
            
            if ($is_renovated && !$force) {
                $already_renovated[] = array(
                    'id' => $post_id,
                    'title' => $post->post_title,
                    'url' => $url
                );
                continue;
            }
            
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_queue} WHERE post_id = %d AND status IN ('pending', 'processing')",
                $post_id
            ));
            
            if (!$exists) {
                $wpdb->insert($this->table_queue, array(
                    'post_id' => $post_id,
                    'post_title' => $post->post_title,
                    'status' => 'pending',
                    'created_at' => current_time('mysql')
                ));
                $added++;
            }
        }

        $result = array(
            'message' => "{$added}件追加",
            'added' => $added,
            'not_found' => $not_found
        );
        
        if (!empty($already_renovated) && !$force) {
            $result['already_renovated'] = $already_renovated;
            $result['need_confirm'] = true;
        }
        
        wp_send_json_success($result);
    }

    public function ajax_add_all_to_queue() {
        check_ajax_referer('gi_seo_nonce', 'nonce');

        $status = sanitize_text_field($_POST['status'] ?? 'all');
        $limit = intval($_POST['limit'] ?? 200);

        $args = array(
            'post_type' => $this->get_target_post_types(),
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'fields' => 'ids'
        );

        if ($status === 'not_renovated') {
            $args['meta_query'] = array(array('key' => '_gi_renovated_at', 'compare' => 'NOT EXISTS'));
        }

        $post_ids = get_posts($args);
        global $wpdb;
        $added = 0;

        foreach ($post_ids as $pid) {
            $post = get_post($pid);
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_queue} WHERE post_id = %d AND status IN ('pending', 'processing')",
                $pid
            ));
            if (!$exists) {
                $wpdb->insert($this->table_queue, array(
                    'post_id' => $pid,
                    'post_title' => $post ? $post->post_title : '',
                    'status' => 'pending',
                    'created_at' => current_time('mysql')
                ));
                $added++;
            }
        }

        wp_send_json_success(array('message' => "{$added}件追加（上限{$limit}件）", 'added' => $added));
    }

    public function ajax_start_queue() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        update_option('gi_seo_queue_running', true);
        update_option('gi_seo_queue_started_at', current_time('mysql'));
        
        // Cronが確実に動くように再スケジュール
        wp_clear_scheduled_hook('gi_seo_process_queue');
        wp_schedule_event(time(), 'every_minute', 'gi_seo_process_queue');
        
        $this->log_process('queue_start', 'Queue started by user');
        
        wp_send_json_success('開始');
    }

    public function ajax_stop_queue() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        update_option('gi_seo_queue_running', false);
        delete_transient('gi_seo_queue_lock');
        
        $this->log_process('queue_stop', 'Queue stopped by user');
        
        wp_send_json_success('停止');
    }

    public function ajax_clear_queue() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;
        $wpdb->query("DELETE FROM {$this->table_queue} WHERE status = 'pending'");
        wp_send_json_success('クリア完了');
    }

    public function ajax_get_queue_status() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $pending = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_queue} WHERE status = 'pending'");
        $processing = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_queue} WHERE status = 'processing'");
        $completed = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_queue} WHERE status = 'completed'");
        $failed = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_failed}");

        // 現在処理中の記事
        $current = $wpdb->get_row("SELECT * FROM {$this->table_queue} WHERE status = 'processing' ORDER BY started_at DESC LIMIT 1");

        $cron_key = get_option('gi_seo_cron_key', '');
        $external_cron_url = admin_url('admin-ajax.php') . '?action=gi_seo_external_cron&key=' . $cron_key;

        wp_send_json_success(array(
            'pending' => $pending,
            'processing' => $processing,
            'completed' => $completed,
            'failed' => $failed,
            'running' => get_option('gi_seo_queue_running', false),
            'last' => get_option('gi_seo_last_processed', ''),
            'total_in_queue' => $pending + $processing,
            'last_cron_run' => get_option('gi_seo_last_cron_run', 0),
            'external_cron_url' => $external_cron_url,
            'current_processing' => $current ? array(
                'post_id' => $current->post_id,
                'post_title' => $current->post_title,
                'started_at' => $current->started_at
            ) : null
        ));
    }

    // キュー詳細取得
    public function ajax_get_queue_details() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        // 待機中
        $pending = $wpdb->get_results(
            "SELECT * FROM {$this->table_queue} WHERE status = 'pending' ORDER BY priority DESC, id ASC LIMIT 50"
        );

        // 完了済み（最新20件）
        $completed = $wpdb->get_results(
            "SELECT * FROM {$this->table_queue} WHERE status = 'completed' ORDER BY completed_at DESC LIMIT 20"
        );

        wp_send_json_success(array(
            'pending' => $pending,
            'completed' => $completed
        ));
    }

    /**
     * キュー処理（堅牢性強化版）
     */
    public function process_queue() {
        // 実行時刻を記録
        update_option('gi_seo_last_cron_run', time());
        
        if (!get_option('gi_seo_queue_running', false)) return;
        
        // ロックチェック（タイムアウト付き）
        $lock = get_transient('gi_seo_queue_lock');
        if ($lock) {
            // ロックが5分以上古い場合は解除
            $lock_time = get_option('gi_seo_lock_time', 0);
            if (time() - $lock_time > 300) {
                delete_transient('gi_seo_queue_lock');
                $this->log_process('lock_timeout', 'Lock released due to timeout');
            } else {
                return;
            }
        }

        set_transient('gi_seo_queue_lock', true, 300);
        update_option('gi_seo_lock_time', time());

        global $wpdb;
        
        // 処理中のまま止まっているアイテムをリセット
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_queue} SET status = 'pending' WHERE status = 'processing' AND started_at < %s",
            date('Y-m-d H:i:s', strtotime('-5 minutes'))
        ));

        $item = $wpdb->get_row(
            "SELECT * FROM {$this->table_queue} WHERE status = 'pending' ORDER BY priority DESC, id ASC LIMIT 1"
        );

        if (!$item) {
            delete_transient('gi_seo_queue_lock');
            return;
        }

        $post = get_post($item->post_id);
        $post_title = $post ? $post->post_title : $item->post_title;

        $wpdb->update($this->table_queue, array(
            'status' => 'processing',
            'post_title' => $post_title,
            'started_at' => current_time('mysql'),
            'attempts' => $item->attempts + 1
        ), array('id' => $item->id));

        $this->log_process('process_start', 'Processing: ' . $post_title . ' (ID: ' . $item->post_id . ')');

        // リノベーション実行
        $result = $this->do_renovate($item->post_id);

        if ($result['success']) {
            $wpdb->update($this->table_queue, array(
                'status' => 'completed',
                'completed_at' => current_time('mysql')
            ), array('id' => $item->id));
            update_option('gi_seo_last_processed', current_time('mysql') . ' - ' . $post_title . ' (成功)');
            $this->log_process('process_success', 'Completed: ' . $post_title);
        } else {
            $error = $result['error'] ?? 'Unknown error';
            if ($item->attempts >= 3) {
                $wpdb->delete($this->table_queue, array('id' => $item->id));
                $this->log_failed($item->post_id, $post_title, $error);
                $this->log_process('process_failed_final', 'Failed: ' . $post_title . ' - ' . $error);
            } else {
                $wpdb->update($this->table_queue, array(
                    'status' => 'pending',
                    'error_message' => $error
                ), array('id' => $item->id));
                $this->log_process('process_retry', 'Retry: ' . $post_title . ' - Attempt ' . $item->attempts);
            }
            update_option('gi_seo_last_processed', current_time('mysql') . ' - ' . $post_title . ' (失敗)');
        }

        delete_transient('gi_seo_queue_lock');
    }

    /**
     * 内部リノベーション実行
     */
    private function do_renovate($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return array('success' => false, 'error' => '記事が見つかりません');
        }

        $seed = $this->extract_seed_keyword($post->post_title);
        $keyphrase = $this->extract_keyphrase($post->post_title, $post->post_content);
        $keywords = $this->extract_important_keywords($post->post_title, $post->post_content);
        $suggests = $this->get_google_suggest($seed);
        $related = $this->get_related_posts_list($post_id);

        $prompt = $this->build_renovation_prompt($post, $seed, $keyphrase, $keywords, $suggests, $related);
        $result = $this->call_gemini($prompt);

        if (isset($result['error'])) {
            return array('success' => false, 'error' => $result['error']);
        }

        $new_content = $this->extract_content($result['content']);
        if (empty($new_content)) {
            return array('success' => false, 'error' => 'コンテンツ抽出失敗');
        }

        // バックアップ
        update_post_meta($post_id, '_gi_content_backup', $post->post_content);
        update_post_meta($post_id, '_gi_backup_date', current_time('mysql'));
        if (!get_post_meta($post_id, '_gi_original_content', true)) {
            update_post_meta($post_id, '_gi_original_content', $post->post_content);
            update_post_meta($post_id, '_gi_original_title', $post->post_title);
        }

        $new_title = $this->extract_title($result['content']);
        $meta_desc = $this->extract_meta_desc($result['content']);

        $update = array('ID' => $post_id, 'post_content' => $new_content);
        if ($new_title) $update['post_title'] = $new_title;
        wp_update_post($update);

        if ($meta_desc) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_desc);
            update_post_meta($post_id, '_gi_meta_description', $meta_desc);
        }

        $report = array(
            'date' => current_time('mysql'),
            'seed_keyword' => $seed,
            'keyphrase' => $keyphrase,
            'keywords' => $keywords,
            'suggests' => $suggests,
            'original_title' => $post->post_title,
            'new_title' => $new_title ?: $post->post_title,
            'meta_description' => $meta_desc,
            'original_char_count' => mb_strlen(strip_tags($post->post_content)),
            'new_char_count' => mb_strlen(strip_tags($new_content)),
            'model' => $result['model'],
            'version' => $this->version
        );

        update_post_meta($post_id, '_gi_renovation_report', $report);
        update_post_meta($post_id, '_gi_renovated_at', current_time('mysql'));
        update_post_meta($post_id, '_gi_renovation_count', (int)get_post_meta($post_id, '_gi_renovation_count', true) + 1);

        // リノベーション統計を記録
        $report['processing_time'] = 0;
        $this->record_renovation_stats($post_id, $post->post_title, $report);

        return array('success' => true);
    }

    public function background_process() {
        if (get_option('gi_seo_queue_running', false)) {
            $this->process_queue();
        }
    }

    public function ajax_process_single() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        $this->process_queue();
        wp_send_json_success('処理実行');
    }

    // ================================================================
    // 復元
    // ================================================================
    public function ajax_restore_backup() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        $post_id = intval($_POST['post_id'] ?? 0);
        $backup = get_post_meta($post_id, '_gi_content_backup', true);
        
        if (!$backup) {
            wp_send_json_error('バックアップなし');
            return;
        }
        
        wp_update_post(array('ID' => $post_id, 'post_content' => $backup));
        wp_send_json_success('復元完了');
    }

    public function ajax_restore_original() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        $post_id = intval($_POST['post_id'] ?? 0);
        $original = get_post_meta($post_id, '_gi_original_content', true);
        
        if (!$original) {
            wp_send_json_error('オリジナルなし');
            return;
        }
        
        wp_update_post(array('ID' => $post_id, 'post_content' => $original));
        delete_post_meta($post_id, '_gi_renovated_at');
        delete_post_meta($post_id, '_gi_renovation_report');
        wp_send_json_success('復元完了');
    }

    // ================================================================
    // 失敗リスト
    // ================================================================
    public function ajax_get_failed() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $items = $wpdb->get_results(
            "SELECT f.*, p.post_title as current_title
             FROM {$this->table_failed} f 
             LEFT JOIN {$wpdb->posts} p ON f.post_id = p.ID 
             ORDER BY f.failed_at DESC"
        );

        // post_titleがある場合はそれを使用、なければcurrent_titleを使用
        foreach ($items as &$item) {
            if (empty($item->post_title) && !empty($item->current_title)) {
                $item->post_title = $item->current_title;
            }
        }

        wp_send_json_success($items);
    }

    public function ajax_retry_failed() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        $id = intval($_POST['failed_id'] ?? 0);
        global $wpdb;

        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_failed} WHERE id = %d", $id
        ));

        if (!$item) {
            wp_send_json_error('見つかりません');
            return;
        }

        $post = get_post($item->post_id);
        $wpdb->insert($this->table_queue, array(
            'post_id' => $item->post_id,
            'post_title' => $post ? $post->post_title : $item->post_title,
            'status' => 'pending',
            'priority' => 10
        ));

        $wpdb->delete($this->table_failed, array('id' => $id));
        wp_send_json_success('リトライ追加');
    }

    public function ajax_clear_failed() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$this->table_failed}");
        wp_send_json_success('クリア完了');
    }

    // ================================================================
    // M&A（記事統合）
    // ================================================================
    public function ajax_get_keyword_stats() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $types = $this->get_target_post_types();
        $placeholders = implode(',', array_fill(0, count($types), '%s'));

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ($placeholders) AND post_status = 'publish'",
            ...$types
        ));

        $extracted = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type IN ($placeholders) AND p.post_status = 'publish' 
             AND pm.meta_key = '_gi_seed_keyword'",
            ...$types
        ));

        wp_send_json_success(array(
            'total' => (int)$total,
            'extracted' => (int)$extracted,
            'pending' => (int)$total - (int)$extracted
        ));
    }

    public function ajax_analyze_keywords() {
        check_ajax_referer('gi_seo_nonce', 'nonce');

        $batch = intval($_POST['batch_size'] ?? 100);
        $offset = intval($_POST['offset'] ?? 0);

        $args = array(
            'post_type' => $this->get_target_post_types(),
            'post_status' => 'publish',
            'posts_per_page' => $batch,
            'offset' => $offset,
            'meta_query' => array(array('key' => '_gi_seed_keyword', 'compare' => 'NOT EXISTS'))
        );

        $query = new WP_Query($args);
        $processed = array();

        foreach ($query->posts as $p) {
            $kw = $this->extract_seed_keyword($p->post_title);
            $regions = $this->extract_region_from_text($p->post_title);
            
            update_post_meta($p->ID, '_gi_seed_keyword', $kw);
            update_post_meta($p->ID, '_gi_regions', $regions);
            
            $processed[] = array(
                'id' => $p->ID,
                'title' => $p->post_title,
                'keyword' => $kw,
                'regions' => $regions
            );
            usleep(100000);
        }

        wp_send_json_success(array(
            'processed' => $processed,
            'count' => count($processed),
            'has_more' => $query->found_posts > ($offset + $batch)
        ));
    }

    public function ajax_get_merge_candidates() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 50);
        $min_similarity = intval($_POST['min_similarity'] ?? 60);

        $keywords = $wpdb->get_results(
            "SELECT meta_value as keyword, COUNT(*) as count, GROUP_CONCAT(post_id) as post_ids
             FROM {$wpdb->postmeta}
             WHERE meta_key = '_gi_seed_keyword' AND meta_value != ''
             GROUP BY meta_value
             HAVING count > 1
             ORDER BY count DESC"
        );

        $groups = array();

        foreach ($keywords as $kw) {
            $pids = explode(',', $kw->post_ids);
            $posts = array();
            $total_pv = 0;

            foreach ($pids as $pid) {
                $p = get_post($pid);
                if ($p && $p->post_status === 'publish') {
                    $pv = $this->get_post_pv($pid);
                    $regions = get_post_meta($pid, '_gi_regions', true);
                    $total_pv += $pv;
                    $posts[] = array(
                        'ID' => $p->ID,
                        'title' => $p->post_title,
                        'pv' => $pv,
                        'char_count' => mb_strlen(strip_tags($p->post_content)),
                        'url' => get_permalink($p->ID),
                        'regions' => $regions ?: array(),
                        'content_excerpt' => mb_substr(strip_tags($p->post_content), 0, 200)
                    );
                }
            }

            usort($posts, function($a, $b) {
                return $b['pv'] - $a['pv'];
            });

            if (count($posts) >= 2) {
                $parent = $posts[0];
                $children = array();
                
                for ($i = 1; $i < count($posts); $i++) {
                    $child = $posts[$i];
                    $similarity = $this->calculate_similarity_score(
                        $parent['title'],
                        $child['title'],
                        $parent['content_excerpt'],
                        $child['content_excerpt']
                    );
                    
                    if ($similarity >= $min_similarity) {
                        $child['similarity'] = $similarity;
                        $children[] = $child;
                    }
                }
                
                if (!empty($children)) {
                    $groups[] = array(
                        'keyword' => $kw->keyword,
                        'count' => count($children) + 1,
                        'total_pv' => $total_pv,
                        'parent' => $parent,
                        'children' => $children
                    );
                }
            }
        }

        $total = count($groups);
        $groups = array_slice($groups, ($page - 1) * $per_page, $per_page);

        wp_send_json_success(array(
            'groups' => $groups,
            'total' => $total,
            'pages' => ceil($total / $per_page)
        ));
    }

    public function ajax_execute_merge() {
        check_ajax_referer('gi_seo_nonce', 'nonce');

        $parent_id = intval($_POST['parent_id'] ?? 0);
        $child_ids = isset($_POST['child_ids']) ? array_map('intval', (array)$_POST['child_ids']) : array();

        if (!$parent_id || empty($child_ids)) {
            wp_send_json_error('データ不足');
            return;
        }

        global $wpdb;
        $merged = 0;

        foreach ($child_ids as $cid) {
            $child = get_post($cid);
            if (!$child) continue;

            $wpdb->insert($this->table_merge_history, array(
                'parent_id' => $parent_id,
                'child_id' => $cid,
                'child_title' => $child->post_title,
                'child_content' => $child->post_content,
                'child_meta' => json_encode(get_post_meta($cid)),
                'merged_by' => get_current_user_id()
            ));

            update_post_meta($cid, '_gi_redirect_to', $parent_id);
            wp_update_post(array('ID' => $cid, 'post_status' => 'draft'));

            $merged++;
        }

        wp_send_json_success(array(
            'message' => "{$merged}件統合",
            'merged_count' => $merged
        ));
    }

    public function ajax_bulk_merge() {
        check_ajax_referer('gi_seo_nonce', 'nonce');

        $groups = isset($_POST['groups']) ? (array)$_POST['groups'] : array();
        $total = 0;

        foreach ($groups as $g) {
            $parent = intval($g['parent_id'] ?? 0);
            $children = isset($g['child_ids']) ? array_map('intval', (array)$g['child_ids']) : array();

            if ($parent && !empty($children)) {
                $_POST['parent_id'] = $parent;
                $_POST['child_ids'] = $children;

                ob_start();
                $this->ajax_execute_merge();
                $res = json_decode(ob_get_clean(), true);

                if (isset($res['success']) && $res['success']) {
                    $total += $res['data']['merged_count'] ?? 0;
                }
            }
        }

        wp_send_json_success(array(
            'message' => "合計{$total}件統合",
            'total' => $total
        ));
    }

    public function ajax_get_merge_history() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 100);

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_merge_history}");

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT h.*, p.post_title as parent_title 
             FROM {$this->table_merge_history} h 
             LEFT JOIN {$wpdb->posts} p ON h.parent_id = p.ID 
             ORDER BY h.merged_at DESC 
             LIMIT %d OFFSET %d",
            $per_page, ($page - 1) * $per_page
        ));

        wp_send_json_success(array(
            'items' => $items,
            'total' => (int)$total,
            'pages' => ceil($total / $per_page)
        ));
    }

    public function ajax_undo_merge() {
        check_ajax_referer('gi_seo_nonce', 'nonce');

        $id = intval($_POST['history_id'] ?? 0);
        global $wpdb;

        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_merge_history} WHERE id = %d", $id
        ));

        if (!$item) {
            wp_send_json_error('履歴なし');
            return;
        }

        wp_update_post(array(
            'ID' => $item->child_id,
            'post_status' => 'publish',
            'post_content' => $item->child_content
        ));

        delete_post_meta($item->child_id, '_gi_redirect_to');
        $wpdb->delete($this->table_merge_history, array('id' => $id));

        wp_send_json_success('取消完了');
    }

    // ================================================================
    // 分析・カルテ
    // ================================================================
    public function ajax_get_analytics() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $types = $this->get_target_post_types();
        $placeholders = implode(',', array_fill(0, count($types), '%s'));

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ($placeholders) AND post_status = 'publish'",
            ...$types
        ));

        $renovated = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type IN ($placeholders) AND p.post_status = 'publish' 
             AND pm.meta_key = '_gi_renovated_at'",
            ...$types
        ));

        $reports = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type IN ($placeholders) AND p.post_status = 'publish' 
             AND pm.meta_key = '_gi_renovation_report'",
            ...$types
        ));

        $today = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} 
             WHERE meta_key = '_gi_renovated_at' AND meta_value LIKE %s",
            date('Y-m-d') . '%'
        ));

        wp_send_json_success(array(
            'total_posts' => (int)$total,
            'renovated_posts' => (int)$renovated,
            'renovation_rate' => $total > 0 ? round(($renovated / $total) * 100, 1) : 0,
            'report_count' => (int)$reports,
            'today_renovated' => (int)$today
        ));
    }

    public function ajax_get_report() {
        check_ajax_referer('gi_seo_nonce', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        $post = get_post($post_id);

        if (!$post) {
            wp_send_json_error('記事なし');
            return;
        }

        $report = get_post_meta($post_id, '_gi_renovation_report', true);

        if (!$report) {
            wp_send_json_error('カルテなし');
            return;
        }

        $report['post_id'] = $post_id;
        $report['current_title'] = $post->post_title;
        $report['url'] = get_permalink($post_id);
        $report['edit_url'] = admin_url('post.php?post=' . $post_id . '&action=edit');

        wp_send_json_success($report);
    }

    public function ajax_get_report_list() {
        check_ajax_referer('gi_seo_nonce', 'nonce');

        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 100);

        $args = array(
            'post_type' => $this->get_target_post_types(),
            'post_status' => 'publish',
            'meta_query' => array(array('key' => '_gi_renovation_report', 'compare' => 'EXISTS')),
            'posts_per_page' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'orderby' => 'meta_value',
            'meta_key' => '_gi_renovated_at',
            'order' => 'DESC'
        );

        $query = new WP_Query($args);
        $reports = array();

        foreach ($query->posts as $p) {
            $r = get_post_meta($p->ID, '_gi_renovation_report', true);
            if ($r) {
                $reports[] = array(
                    'post_id' => $p->ID,
                    'title' => $p->post_title,
                    'date' => $r['date'] ?? '',
                    'seed_keyword' => $r['seed_keyword'] ?? '',
                    'keyphrase' => $r['keyphrase'] ?? '',
                    'original_char_count' => $r['original_char_count'] ?? 0,
                    'new_char_count' => $r['new_char_count'] ?? 0,
                    'char_diff' => ($r['new_char_count'] ?? 0) - ($r['original_char_count'] ?? 0),
                    'suggests_count' => count($r['suggests'] ?? array()),
                    'url' => get_permalink($p->ID)
                );
            }
        }

        wp_send_json_success(array(
            'reports' => $reports,
            'total' => $query->found_posts,
            'pages' => ceil($query->found_posts / $per_page)
        ));
    }

    public function ajax_export_reports() {
        check_ajax_referer('gi_seo_nonce', 'nonce');

        $args = array(
            'post_type' => $this->get_target_post_types(),
            'post_status' => 'publish',
            'meta_query' => array(array('key' => '_gi_renovation_report', 'compare' => 'EXISTS')),
            'posts_per_page' => -1
        );

        $query = new WP_Query($args);
        $data = array();
        foreach ($query->posts as $p) {
            $r = get_post_meta($p->ID, '_gi_renovation_report', true);
            if ($r) {
                $data[] = array(
                    'post_id' => $p->ID,
                    'title' => $p->post_title,
                    'url' => get_permalink($p->ID),
                    'date' => $r['date'] ?? '',
                    'seed_keyword' => $r['seed_keyword'] ?? '',
                    'keyphrase' => $r['keyphrase'] ?? '',
                    'original_char_count' => $r['original_char_count'] ?? 0,
                    'new_char_count' => $r['new_char_count'] ?? 0
                );
            }
        }

        wp_send_json_success(array('data' => $data, 'count' => count($data)));
    }

    // ================================================================
    // リノベーション統計・PV推移
    // ================================================================
    public function ajax_get_renovation_stats() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 50);
        $sort = sanitize_text_field($_POST['sort'] ?? 'renovated_desc');

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_renovation_stats}");

        // ソート順序の決定
        $order_sql = 's.renovated_at DESC';
        switch ($sort) {
            case 'renovated_asc':
                $order_sql = 's.renovated_at ASC';
                break;
            case 'pv_current_desc':
                $order_sql = 'current_pv DESC';
                break;
            case 'pv_current_asc':
                $order_sql = 'current_pv ASC';
                break;
            case 'pv_change_desc':
                $order_sql = 'pv_change DESC';
                break;
            case 'pv_change_asc':
                $order_sql = 'pv_change ASC';
                break;
        }

        // PVソートの場合は一度全件取得してソートする必要がある
        if (strpos($sort, 'pv_') === 0) {
            $stats = $wpdb->get_results(
                "SELECT s.*, p.post_title as current_title
                 FROM {$this->table_renovation_stats} s
                 LEFT JOIN {$wpdb->posts} p ON s.post_id = p.ID"
            );
            
            // 現在のPVを取得して配列に追加
            foreach ($stats as &$stat) {
                $stat->current_pv = $this->get_post_pv($stat->post_id);
                $stat->pv_change = $stat->current_pv - $stat->pv_before;
                
                if (empty($stat->post_title) && !empty($stat->current_title)) {
                    $stat->post_title = $stat->current_title;
                }
            }
            
            // PHPでソート
            usort($stats, function($a, $b) use ($sort) {
                switch ($sort) {
                    case 'pv_current_desc':
                        return $b->current_pv - $a->current_pv;
                    case 'pv_current_asc':
                        return $a->current_pv - $b->current_pv;
                    case 'pv_change_desc':
                        return $b->pv_change - $a->pv_change;
                    case 'pv_change_asc':
                        return $a->pv_change - $b->pv_change;
                    default:
                        return 0;
                }
            });
            
            // ページネーション
            $stats = array_slice($stats, ($page - 1) * $per_page, $per_page);
        } else {
            $stats = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, p.post_title as current_title
                 FROM {$this->table_renovation_stats} s
                 LEFT JOIN {$wpdb->posts} p ON s.post_id = p.ID
                 ORDER BY {$order_sql}
                 LIMIT %d OFFSET %d",
                $per_page, ($page - 1) * $per_page
            ));

            // 現在のPVを取得
            foreach ($stats as &$stat) {
                $stat->current_pv = $this->get_post_pv($stat->post_id);
                $stat->pv_change = $stat->current_pv - $stat->pv_before;
                
                if (empty($stat->post_title) && !empty($stat->current_title)) {
                    $stat->post_title = $stat->current_title;
                }
            }
        }

        // 実際のURLを追加
        foreach ($stats as &$stat) {
            $stat->url = get_permalink($stat->post_id);
        }

        wp_send_json_success(array(
            'stats' => $stats,
            'total' => (int)$total,
            'pages' => ceil($total / $per_page)
        ));
    }

    public function ajax_get_pv_history() {
        check_ajax_referer('gi_seo_nonce', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        $days = intval($_POST['days'] ?? 30);

        if (!$post_id) {
            wp_send_json_error('記事IDが指定されていません');
            return;
        }

        // post_metaから日別PVを取得
        $history = $this->get_post_pv_history($post_id, $days);

        // リノベーション日を取得
        $renovation_date = get_post_meta($post_id, '_gi_renovated_at', true);

        wp_send_json_success(array(
            'history' => $history,
            'renovation_date' => $renovation_date ? substr($renovation_date, 0, 10) : null,
            'current_pv' => $this->get_post_pv($post_id)
        ));
    }

    // ================================================================
    // 404管理
    // ================================================================
    public function ajax_debug_404() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_404_log}'");
        $test_url = '/test-404-' . time();

        $result = $wpdb->insert($this->table_404_log, array(
            'url' => $test_url,
            'title' => 'テスト404ページ',
            'referrer' => 'debug test',
            'user_agent' => 'Debug',
            'ip_address' => '127.0.0.1',
            'count' => 1,
            'first_seen' => current_time('mysql'),
            'last_seen' => current_time('mysql')
        ));

        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_404_log}");

        wp_send_json_success(array(
            'table_exists' => $table_exists ? 'YES' : 'NO',
            'table_name' => $this->table_404_log,
            'insert_result' => $result ? 'OK' : 'FAILED',
            'insert_error' => $wpdb->last_error,
            'total_count' => $count
        ));
    }

    public function ajax_get_404_logs() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 100);
        $offset = ($page - 1) * $per_page;
        $filter = sanitize_text_field($_POST['filter'] ?? 'all');
        $search = sanitize_text_field($_POST['search'] ?? '');

        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_404_log}'");
        if (!$table_exists) {
            wp_send_json_error('404テーブルが存在しません。プラグインを再有効化してください。');
            return;
        }

        $where = "1=1";
        $params = array();
        
        if ($filter === 'unset') {
            $where .= " AND redirect_to IS NULL AND redirect_url IS NULL";
        } elseif ($filter === 'set') {
            $where .= " AND (redirect_to IS NOT NULL OR redirect_url IS NOT NULL)";
        }
        
        if (!empty($search)) {
            $where .= " AND (url LIKE %s OR title LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        if (!empty($params)) {
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_404_log} WHERE {$where}",
                ...$params
            ));
        } else {
            $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_404_log} WHERE {$where}");
        }
        
        $params[] = $per_page;
        $params[] = $offset;
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, p.post_title as redirect_title 
             FROM {$this->table_404_log} l 
             LEFT JOIN {$wpdb->posts} p ON l.redirect_to = p.ID 
             WHERE {$where} 
             ORDER BY l.count DESC, l.last_seen DESC 
             LIMIT %d OFFSET %d",
            ...$params
        ));

        wp_send_json_success(array(
            'logs' => $logs ? $logs : array(),
            'total' => (int)$total,
            'pages' => $total > 0 ? ceil($total / $per_page) : 1
        ));
    }

    public function ajax_set_redirect() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $log_id = intval($_POST['log_id'] ?? 0);
        $redirect_to = intval($_POST['redirect_to'] ?? 0);
        $redirect_url = sanitize_url($_POST['redirect_url'] ?? '');

        if (!$log_id) {
            wp_send_json_error('ログIDが指定されていません');
            return;
        }

        $update_data = array('redirect_to' => null, 'redirect_url' => null);

        if ($redirect_to > 0) {
            $update_data['redirect_to'] = $redirect_to;
        } elseif (!empty($redirect_url)) {
            $update_data['redirect_url'] = $redirect_url;
        }

        $result = $wpdb->update($this->table_404_log, $update_data, array('id' => $log_id));

        if ($result === false) {
            wp_send_json_error('更新に失敗しました: ' . $wpdb->last_error);
            return;
        }

        wp_send_json_success('リダイレクトを設定しました');
    }

    public function ajax_find_similar() {
        check_ajax_referer('gi_seo_nonce', 'nonce');

        $url = sanitize_text_field($_POST['url'] ?? '');
        $title = sanitize_text_field($_POST['title'] ?? '');
        
        if (empty($url)) {
            wp_send_json_error('URLが指定されていません');
            return;
        }

        $match = $this->find_best_redirect_match($url, $title);
        $results = array();

        if ($match) {
            $results[] = array(
                'ID' => $match['post_id'],
                'title' => '[推奨:スコア' . $match['score'] . '] ' . $match['title'],
                'url' => $match['url'],
                'score' => $match['score']
            );
        }

        $path = parse_url($url, PHP_URL_PATH);
        $slug = basename($path);
        $slug = urldecode($slug);
        $slug = preg_replace('/[^a-zA-Z0-9\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FFF}]+/u', ' ', $slug);

        if (!empty(trim($slug))) {
            $args = array(
                'post_type' => $this->get_target_post_types(),
                'post_status' => 'publish',
                's' => trim($slug),
                'posts_per_page' => 10
            );
            $query = new WP_Query($args);

            foreach ($query->posts as $post) {
                $exists = false;
                foreach ($results as $r) {
                    if ($r['ID'] == $post->ID) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $results[] = array(
                        'ID' => $post->ID,
                        'title' => $post->post_title,
                        'url' => get_permalink($post->ID)
                    );
                }
            }
        }

        wp_send_json_success(array_slice($results, 0, 15));
    }

    public function ajax_clear_404_logs() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $deleted = $wpdb->query(
            "DELETE FROM {$this->table_404_log} WHERE redirect_to IS NULL AND redirect_url IS NULL"
        );

        wp_send_json_success("未設定の404ログを{$deleted}件クリアしました");
    }

    // ================================================================
    // 補助金データベース
    // ================================================================
    public function ajax_subsidy_get_list() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 100);
        $search = sanitize_text_field($_POST['search'] ?? '');
        $prefecture = sanitize_text_field($_POST['prefecture'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? '');
        $sort = sanitize_text_field($_POST['sort'] ?? 'created_desc');
        $exists_filter = sanitize_text_field($_POST['exists_filter'] ?? '');

        $where = array('1=1');
        $params = array();

        if (!empty($search)) {
            $where[] = "(title LIKE %s OR data_source LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        if (!empty($prefecture)) {
            $where[] = "prefecture = %s";
            $params[] = $prefecture;
        }

        if (!empty($status)) {
            $where[] = "status = %s";
            $params[] = $status;
        }

        if ($exists_filter === 'exists') {
            $where[] = "matched_post_id IS NOT NULL";
        } elseif ($exists_filter === 'not_exists') {
            $where[] = "matched_post_id IS NULL";
        }

        $where_sql = implode(' AND ', $where);

        $order_sql = 'created_at DESC';
        switch ($sort) {
            case 'deadline_asc':
                $order_sql = 'deadline ASC';
                break;
            case 'deadline_desc':
                $order_sql = 'deadline DESC';
                break;
            case 'title_asc':
                $order_sql = 'title ASC';
                break;
            case 'created_asc':
                $order_sql = 'created_at ASC';
                break;
        }

        if (!empty($params)) {
            $total = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_subsidy} WHERE $where_sql", ...$params));
        } else {
            $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subsidy} WHERE $where_sql");
        }

        $offset = ($page - 1) * $per_page;
        $params[] = $per_page;
        $params[] = $offset;

        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.*, p.post_title as matched_post_title 
                 FROM {$this->table_subsidy} s 
                 LEFT JOIN {$wpdb->posts} p ON s.matched_post_id = p.ID 
                 WHERE $where_sql 
                 ORDER BY $order_sql 
                 LIMIT %d OFFSET %d",
                ...$params
            )
        );

        wp_send_json_success(array(
            'items' => $items,
            'total' => (int)$total,
            'pages' => ceil($total / $per_page)
        ));
    }

    public function ajax_subsidy_add() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $data = array(
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'url' => sanitize_url($_POST['url'] ?? ''),
            'deadline' => sanitize_text_field($_POST['deadline'] ?? '') ?: null,
            'prefecture' => sanitize_text_field($_POST['prefecture'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'subsidy_amount' => sanitize_text_field($_POST['subsidy_amount'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'active'),
            'data_source' => sanitize_text_field($_POST['data_source'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        if (empty($data['title'])) {
            wp_send_json_error('タイトルは必須です');
            return;
        }

        $result = $wpdb->insert($this->table_subsidy, $data);

        if ($result === false) {
            wp_send_json_error('追加に失敗しました: ' . $wpdb->last_error);
            return;
        }

        wp_send_json_success(array(
            'message' => '追加しました',
            'id' => $wpdb->insert_id
        ));
    }

    public function ajax_subsidy_update() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            wp_send_json_error('IDが指定されていません');
            return;
        }

        $data = array(
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'url' => sanitize_url($_POST['url'] ?? ''),
            'deadline' => sanitize_text_field($_POST['deadline'] ?? '') ?: null,
            'prefecture' => sanitize_text_field($_POST['prefecture'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'subsidy_amount' => sanitize_text_field($_POST['subsidy_amount'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'active'),
            'data_source' => sanitize_text_field($_POST['data_source'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'updated_at' => current_time('mysql')
        );

        $result = $wpdb->update($this->table_subsidy, $data, array('id' => $id));

        if ($result === false) {
            wp_send_json_error('更新に失敗しました: ' . $wpdb->last_error);
            return;
        }

        wp_send_json_success('更新しました');
    }

    public function ajax_subsidy_delete() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            wp_send_json_error('IDが指定されていません');
            return;
        }

        $result = $wpdb->delete($this->table_subsidy, array('id' => $id));

        if ($result === false) {
            wp_send_json_error('削除に失敗しました');
            return;
        }

        wp_send_json_success('削除しました');
    }

    public function ajax_subsidy_bulk_delete() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $ids = isset($_POST['ids']) ? array_map('intval', (array)$_POST['ids']) : array();
        
        if (empty($ids)) {
            wp_send_json_error('削除対象が選択されていません');
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_subsidy} WHERE id IN ($placeholders)",
            ...$ids
        ));

        wp_send_json_success("{$deleted}件削除しました");
    }

    public function ajax_subsidy_import() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $csv_data = $_POST['csv_data'] ?? '';
        if (empty($csv_data)) {
            wp_send_json_error('CSVデータがありません');
            return;
        }

        $lines = explode("\n", $csv_data);
        $imported = 0;
        $errors = array();

        array_shift($lines);

        foreach ($lines as $line_num => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (strpos($line, "\t") !== false) {
                $row = explode("\t", $line);
            } else {
                $row = str_getcsv($line);
            }
            
            if (count($row) < 1) continue;

            $data = array(
                'title' => sanitize_text_field($row[0] ?? ''),
                'url' => sanitize_url($row[1] ?? ''),
                'deadline' => !empty($row[2]) ? sanitize_text_field($row[2]) : null,
                'prefecture' => sanitize_text_field($row[3] ?? ''),
                'city' => sanitize_text_field($row[4] ?? ''),
                'subsidy_amount' => sanitize_text_field($row[5] ?? ''),
                'status' => sanitize_text_field($row[6] ?? 'active'),
                'data_source' => sanitize_text_field($row[7] ?? ''),
                'notes' => sanitize_textarea_field($row[8] ?? ''),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            );

            if (empty($data['title'])) {
                $errors[] = "行 " . ($line_num + 2) . ": タイトルが空です";
                continue;
            }

            if (!empty($data['deadline'])) {
                $deadline = strtotime($data['deadline']);
                if ($deadline) {
                    $data['deadline'] = date('Y-m-d', $deadline);
                } else {
                    $data['deadline'] = null;
                }
            }

            $result = $wpdb->insert($this->table_subsidy, $data);

            if ($result) {
                $imported++;
            } else {
                $errors[] = "行 " . ($line_num + 2) . ": " . $wpdb->last_error;
            }
        }

        wp_send_json_success(array(
            'message' => "{$imported}件インポートしました",
            'imported' => $imported,
            'errors' => $errors
        ));
    }

    public function ajax_subsidy_export() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        // フィルター条件を取得
        $search = sanitize_text_field($_POST['search'] ?? '');
        $prefecture = sanitize_text_field($_POST['prefecture'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? '');
        $exists_filter = sanitize_text_field($_POST['exists_filter'] ?? '');
        
        // クエリ構築
        $where = array('1=1');
        $params = array();
        
        if (!empty($search)) {
            $where[] = "(title LIKE %s OR notes LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }
        
        if (!empty($prefecture)) {
            $where[] = "prefecture = %s";
            $params[] = $prefecture;
        }
        
        if (!empty($status)) {
            $where[] = "status = %s";
            $params[] = $status;
        }
        
        if ($exists_filter === 'exists') {
            $where[] = "matched_post_id IS NOT NULL";
        } elseif ($exists_filter === 'not_exists') {
            $where[] = "matched_post_id IS NULL";
        }
        
        $where_clause = implode(' AND ', $where);
        $sql = "SELECT * FROM {$this->table_subsidy} WHERE {$where_clause} ORDER BY created_at DESC";
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        $items = $wpdb->get_results($sql);

        $csv_data = array();
        $csv_data[] = array('タイトル', 'URL', '締切日', '都道府県', '市区町村', '補助金額', 'ステータス', 'データソース', 'メモ', '投稿ID', 'マッチ投稿タイトル', '作成日時', '更新日時');

        foreach ($items as $item) {
            $matched_title = '';
            if ($item->matched_post_id) {
                $matched_title = get_the_title($item->matched_post_id);
            }
            
            $csv_data[] = array(
                $item->title,
                $item->url,
                $item->deadline,
                $item->prefecture,
                $item->city,
                $item->subsidy_amount,
                $item->status,
                $item->data_source,
                $item->notes,
                $item->matched_post_id ?: '',
                $matched_title,
                $item->created_at,
                $item->updated_at
            );
        }

        // フィルター情報も返す
        $filter_info = array();
        if (!empty($search)) $filter_info[] = "検索: {$search}";
        if (!empty($prefecture)) $filter_info[] = "都道府県: {$prefecture}";
        if (!empty($status)) $filter_info[] = "ステータス: {$status}";
        if ($exists_filter === 'exists') $filter_info[] = "投稿あり";
        if ($exists_filter === 'not_exists') $filter_info[] = "投稿なし";

        wp_send_json_success(array(
            'data' => $csv_data,
            'count' => count($items) - 1, // ヘッダー除く
            'filter_info' => !empty($filter_info) ? implode(' / ', $filter_info) : '全件'
        ));
    }

    public function ajax_subsidy_get_stats() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subsidy}");
        $active = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subsidy} WHERE status = 'active'");
        $expired = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subsidy} WHERE status = 'expired'");
        $pending = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subsidy} WHERE status = 'pending'");
        $article_created = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subsidy} WHERE status = 'article_created'");
        $matched = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subsidy} WHERE matched_post_id IS NOT NULL");
        $unmatched = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subsidy} WHERE matched_post_id IS NULL");

        $by_prefecture = $wpdb->get_results(
            "SELECT prefecture, COUNT(*) as count FROM {$this->table_subsidy} 
             WHERE prefecture != '' GROUP BY prefecture ORDER BY count DESC LIMIT 10"
        );

        $upcoming_deadlines = $wpdb->get_results(
            "SELECT * FROM {$this->table_subsidy} 
             WHERE deadline >= CURDATE() AND status = 'active' 
             ORDER BY deadline ASC LIMIT 10"
        );

        wp_send_json_success(array(
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'pending' => $pending,
            'article_created' => $article_created,
            'matched' => $matched,
            'unmatched' => $unmatched,
            'by_prefecture' => $by_prefecture,
            'upcoming_deadlines' => $upcoming_deadlines
        ));
    }

    public function ajax_subsidy_check_exists() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        $subsidy_id = intval($_POST['subsidy_id'] ?? 0);
        
        if (!$subsidy_id) {
            wp_send_json_error('補助金IDが指定されていません');
            return;
        }

        $subsidy = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_subsidy} WHERE id = %d", $subsidy_id
        ));

        if (!$subsidy) {
            wp_send_json_error('補助金が見つかりません');
            return;
        }

        $match = $this->find_matching_post_for_subsidy($subsidy->title, $subsidy->prefecture);

        if ($match) {
            $wpdb->update($this->table_subsidy, array(
                'matched_post_id' => $match['post_id'],
                'match_type' => $match['match_type']
            ), array('id' => $subsidy_id));

            wp_send_json_success(array(
                'found' => true,
                'post_id' => $match['post_id'],
                'post_title' => $match['title'],
                'post_url' => $match['url'],
                'match_type' => $match['match_type'],
                'score' => $match['score']
            ));
        } else {
            wp_send_json_success(array('found' => false));
        }
    }

    public function ajax_subsidy_sync_posts() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;

        // 初回呼び出しかどうかをチェック
        $is_init = isset($_POST['init']) && $_POST['init'] === 'true';
        $skip_matched = isset($_POST['skip_matched']) ? $_POST['skip_matched'] === 'true' : true;
        
        // 初回は総数だけ返す
        if ($is_init) {
            if ($skip_matched) {
                $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subsidy} WHERE matched_post_id IS NULL");
            } else {
                $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subsidy}");
            }
            wp_send_json_success(array(
                'total' => $total,
                'init' => true
            ));
            return;
        }

        // 処理件数（補助金DBの件数ベース）
        $limit = intval($_POST['limit'] ?? 50);
        $offset = intval($_POST['offset'] ?? 0);
        
        // 実行時間制限を設定
        set_time_limit(120);
        
        // 照合対象を取得（補助金DBから）
        if ($skip_matched) {
            // 未マッチの補助金のみ取得（IDでページネーション）
            $subsidies = $wpdb->get_results($wpdb->prepare(
                "SELECT id, title, prefecture FROM {$this->table_subsidy} WHERE matched_post_id IS NULL ORDER BY id ASC LIMIT %d OFFSET %d",
                $limit, $offset
            ));
        } else {
            $subsidies = $wpdb->get_results($wpdb->prepare(
                "SELECT id, title, prefecture FROM {$this->table_subsidy} ORDER BY id ASC LIMIT %d OFFSET %d",
                $limit, $offset
            ));
        }

        $processed = count($subsidies);
        $matched = 0;
        $results = array();

        foreach ($subsidies as $subsidy) {
            // メモリ使用量チェック（256MBまで許容）
            if (memory_get_usage(true) > 256 * 1024 * 1024) {
                break;
            }
            
            $match = $this->find_matching_post_for_subsidy($subsidy->title, $subsidy->prefecture);

            if ($match) {
                $wpdb->update($this->table_subsidy, array(
                    'matched_post_id' => $match['post_id'],
                    'match_type' => $match['match_type']
                ), array('id' => $subsidy->id));

                $matched++;
                $results[] = array(
                    'subsidy_id' => $subsidy->id,
                    'subsidy' => $subsidy->title,
                    'post' => $match['title'],
                    'score' => $match['score']
                );
            }
        }

        // 残りの件数を再計算
        if ($skip_matched) {
            $remaining = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subsidy} WHERE matched_post_id IS NULL");
        } else {
            $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subsidy}");
            $remaining = max(0, $total - $offset - $processed);
        }

        wp_send_json_success(array(
            'processed' => $processed,
            'matched' => $matched,
            'remaining' => $remaining,
            'has_more' => $processed > 0 && $remaining > 0,
            'results' => array_slice($results, 0, 10),
            'next_offset' => $offset + $processed
        ));
    }
    
    /**
     * デバッグ用：補助金マッチングのテスト
     */
    public function ajax_subsidy_debug_match() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;
        
        // サンプルの補助金データを取得
        $samples = $wpdb->get_results(
            "SELECT id, title, prefecture FROM {$this->table_subsidy} WHERE matched_post_id IS NULL LIMIT 5"
        );
        
        $debug_results = array();
        
        foreach ($samples as $sample) {
            $names = $this->extract_all_subsidy_names($sample->title);
            
            // 各名前で検索
            $found_posts = array();
            foreach ($names as $name) {
                if (mb_strlen($name) < 3) continue;
                
                $like = '%' . $wpdb->esc_like($name) . '%';
                $posts = $wpdb->get_results($wpdb->prepare(
                    "SELECT ID, post_title FROM {$wpdb->posts} 
                     WHERE post_status = 'publish' 
                     AND post_title LIKE %s 
                     LIMIT 5",
                    $like
                ));
                
                foreach ($posts as $p) {
                    $found_posts[] = array(
                        'id' => $p->ID,
                        'title' => $p->post_title,
                        'matched_by' => $name
                    );
                }
            }
            
            $debug_results[] = array(
                'subsidy_id' => $sample->id,
                'subsidy_title' => $sample->title,
                'prefecture' => $sample->prefecture,
                'extracted_names' => $names,
                'found_posts' => $found_posts
            );
        }
        
        // WP投稿のサンプルも取得
        $wp_posts_sample = $wpdb->get_results(
            "SELECT ID, post_title FROM {$wpdb->posts} 
             WHERE post_status = 'publish' 
             AND post_type IN ('post', 'page')
             AND (post_title LIKE '%補助金%' OR post_title LIKE '%助成金%')
             LIMIT 20"
        );
        
        wp_send_json_success(array(
            'debug_results' => $debug_results,
            'wp_posts_with_subsidy' => $wp_posts_sample,
            'total_subsidies' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subsidy}"),
            'unmatched_subsidies' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_subsidy} WHERE matched_post_id IS NULL")
        ));
    }
    
    /**
     * マッチ情報をリセット（再スキャン用）
     */
    public function ajax_subsidy_reset_matches() {
        check_ajax_referer('gi_seo_nonce', 'nonce');
        global $wpdb;
        
        $mode = sanitize_text_field($_POST['mode'] ?? 'all');
        $ids = isset($_POST['ids']) ? array_map('intval', (array)$_POST['ids']) : array();
        
        if ($mode === 'all') {
            // 全マッチをリセット
            $updated = $wpdb->query(
                "UPDATE {$this->table_subsidy} SET matched_post_id = NULL, match_type = NULL WHERE matched_post_id IS NOT NULL"
            );
            wp_send_json_success(array(
                'message' => $updated . '件のマッチ情報をリセットしました',
                'count' => $updated
            ));
        } elseif ($mode === 'selected' && !empty($ids)) {
            // 選択した補助金のみリセット
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $updated = $wpdb->query($wpdb->prepare(
                "UPDATE {$this->table_subsidy} SET matched_post_id = NULL, match_type = NULL WHERE id IN ($placeholders)",
                $ids
            ));
            wp_send_json_success(array(
                'message' => $updated . '件のマッチ情報をリセットしました',
                'count' => $updated
            ));
        } else {
            wp_send_json_error('リセット対象を指定してください');
        }
    }

    private function find_matching_post_for_subsidy($subsidy_title, $prefecture = '') {
        global $wpdb;
        
        // ステップ1: 補助金名を抽出
        $subsidy_names = $this->extract_all_subsidy_names($subsidy_title);
        
        if (empty($subsidy_names)) {
            return null;
        }
        
        // 投稿タイプを取得
        $post_types = $this->get_target_post_types();
        $type_placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        
        $candidates = array();
        
        // ステップ2: 各補助金名でWP投稿を検索
        foreach ($subsidy_names as $name) {
            if (mb_strlen($name) < 3) continue;
            
            $like_keyword = '%' . $wpdb->esc_like($name) . '%';
            
            $sql = $wpdb->prepare(
                "SELECT ID, post_title FROM {$wpdb->posts} 
                 WHERE post_type IN ($type_placeholders) 
                 AND post_status = 'publish' 
                 AND post_title LIKE %s 
                 LIMIT 30",
                array_merge($post_types, array($like_keyword))
            );
            
            $posts = $wpdb->get_results($sql);
            
            foreach ($posts as $post) {
                if (isset($candidates[$post->ID])) continue;
                
                $score = 50; // 補助金名が含まれている時点で基本スコア
                $match_type = 'name_match';
                
                // 補助金名の長さでボーナス（長いほど確実なマッチ）
                $score += min(mb_strlen($name), 20);
                
                // 都道府県もマッチすればボーナス
                if (!empty($prefecture) && mb_stripos($post->post_title, $prefecture) !== false) {
                    $score += 20;
                    $match_type = 'exact';
                }
                
                $candidates[$post->ID] = array(
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink($post->ID),
                    'score' => $score,
                    'match_type' => $match_type,
                    'matched_name' => $name
                );
            }
            
            // 十分な候補が見つかったら終了
            if (count($candidates) >= 5) break;
        }
        
        if (empty($candidates)) {
            return null;
        }
        
        // スコア順にソート
        uasort($candidates, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        return reset($candidates);
    }
    
    /**
     * 補助金タイトルから全ての補助金名・事業名を抽出
     * 
     * 対応パターン:
     * - 住宅リフォーム工事費補助金
     * - 高齢者のインフルエンザ予防接種費助成事業
     * - 熊本県美里町：「美里町土地改良事業」
     * - 福岡県中小企業IT導入・賃上げ緊急支援補助金
     */
    private function extract_all_subsidy_names($title) {
        $names = array();
        
        // 前処理：都道府県名・市区町村名を除去してコア部分を取得
        $clean_title = $title;
        $clean_title = preg_replace('/^(北海道|東京都|大阪府|京都府|.{2,3}県).{1,5}(市|町|村|区)?[：:]?\s*/', '', $clean_title);
        $clean_title = preg_replace('/^(令和|平成|R|H)?\d+年度?\s*/', '', $clean_title);
        $clean_title = preg_replace('/[（(][^）)]*[）)]/', '', $clean_title); // 括弧内を除去
        $clean_title = trim($clean_title);
        
        // パターン1: 〇〇補助金、〇〇助成金、〇〇事業など（最重要）
        $suffixes = array(
            '補助金', '助成金', '支援金', '給付金', '交付金', '奨励金',
            '助成事業', '支援事業', '補助事業', '推進事業', '促進事業',
            '事業'  // 最後に広いパターン
        );
        
        foreach ($suffixes as $suffix) {
            // タイトル全体から検索
            if (preg_match('/([ぁ-んァ-ヶー一-龥a-zA-Z0-9・]{3,30}' . preg_quote($suffix, '/') . ')/', $clean_title, $m)) {
                $name = trim($m[1]);
                if (mb_strlen($name) >= 5 && !in_array($name, $names)) {
                    $names[] = $name;
                }
            }
            // 元タイトルからも検索
            if (preg_match('/([ぁ-んァ-ヶー一-龥a-zA-Z0-9・]{3,30}' . preg_quote($suffix, '/') . ')/', $title, $m)) {
                $name = trim($m[1]);
                if (mb_strlen($name) >= 5 && !in_array($name, $names)) {
                    $names[] = $name;
                }
            }
        }
        
        // パターン2: 「」内のテキスト
        if (preg_match_all('/「([^」]{3,50})」/', $title, $matches)) {
            foreach ($matches[1] as $m) {
                $m = trim($m);
                $m = preg_replace('/^(令和|平成|R|H)?\d+年度?\s*/', '', $m);
                if (mb_strlen($m) >= 4 && !in_array($m, $names)) {
                    $names[] = $m;
                }
            }
        }
        
        // パターン3: 【】内のテキスト（都道府県以外）
        if (preg_match_all('/【([^】]{3,50})】/', $title, $matches)) {
            foreach ($matches[1] as $m) {
                $m = trim($m);
                if (!preg_match('/^(北海道|東京都|大阪府|京都府|.{2,3}県|.{1,4}市|.{1,4}町|.{1,4}村)$/', $m)) {
                    if (mb_strlen($m) >= 4 && !in_array($m, $names)) {
                        $names[] = $m;
                    }
                }
            }
        }
        
        // パターン4: キーワードベースの検索用語を追加
        $keywords = array();
        
        // IT導入、リフォーム、インフルエンザなど特徴的なキーワード
        $important_words = array(
            'IT導入', 'リフォーム', '省エネ', '脱炭素', 'クリーンエネルギー',
            'インフルエンザ', '予防接種', '住宅', '空家', '解体',
            '創業', '起業', '事業承継', '販路開拓', '生産性向上',
            '人材育成', '雇用', '賃上げ', 'DX', 'デジタル',
            '移住', 'UIターン', '定住', '子育て', '保育',
            '農業', '漁業', '林業', '土地改良', '設備導入'
        );
        
        foreach ($important_words as $word) {
            if (mb_stripos($title, $word) !== false) {
                $keywords[] = $word;
            }
        }
        
        // パターン5: 省略名・通称の展開
        $abbreviations = array(
            '持続化補助金' => array('持続化補助金', '小規模事業者持続化補助金'),
            'ものづくり補助金' => array('ものづくり補助金', 'ものづくり・商業・サービス'),
            '事業再構築' => array('事業再構築補助金', '事業再構築'),
            'IT導入補助金' => array('IT導入補助金', 'IT導入'),
        );
        
        foreach ($abbreviations as $key => $variants) {
            if (mb_stripos($title, $key) !== false) {
                foreach ($variants as $v) {
                    if (!in_array($v, $names)) {
                        $names[] = $v;
                    }
                }
            }
        }
        
        // キーワードも追加（名前が少ない場合）
        if (count($names) < 3) {
            foreach ($keywords as $kw) {
                if (!in_array($kw, $names)) {
                    $names[] = $kw;
                }
            }
        }
        
        return array_unique(array_filter($names));
    }
    
    private function extract_search_keywords($title) {
        $keywords = array();
        
        // 「」内のテキストを抽出
        if (preg_match_all('/「([^」]+)」/', $title, $matches)) {
            foreach ($matches[1] as $m) {
                if (mb_strlen($m) >= 3) $keywords[] = $m;
            }
        }
        
        // ○○補助金、○○助成金、○○支援金などのパターン
        if (preg_match('/(\S{2,}(?:補助金|助成金|支援金|給付金|交付金|奨励金))/', $title, $m)) {
            $keywords[] = $m[1];
        }
        
        // 主要な名詞を抽出（簡易版）
        $clean = preg_replace('/[【】「」『』（）()\[\]\s　]+/', ' ', $title);
        $parts = preg_split('/[\s・,、，]+/', $clean);
        foreach ($parts as $part) {
            $part = trim($part);
            if (mb_strlen($part) >= 4 && !preg_match('/^(令和|平成|\d+年度?)/', $part)) {
                $keywords[] = $part;
            }
        }
        
        return array_unique($keywords);
    }

    // ================================================================
    // 設定
    // ================================================================
    public function ajax_save_settings() {
        check_ajax_referer('gi_seo_nonce', 'nonce');

        $settings = array(
            'api_key' => sanitize_text_field($_POST['api_key'] ?? ''),
            'model' => sanitize_text_field($_POST['model'] ?? 'gemini-3-pro-preview'),
            'temperature' => floatval($_POST['temperature'] ?? 0.7),
            'processing_interval' => intval($_POST['processing_interval'] ?? 30),
            'post_types' => isset($_POST['post_types']) ? array_map('sanitize_text_field', (array)$_POST['post_types']) : array('post'),
            'custom_prompt' => wp_kses_post($_POST['custom_prompt'] ?? '')
        );

        update_option('gi_seo_settings', $settings);
        wp_send_json_success('保存完了');
    }

    // ================================================================
    // 管理画面レンダリング
    // ================================================================
    public function render_page() {
        $nonce = wp_create_nonce('gi_seo_nonce');
        $cron_key = get_option('gi_seo_cron_key', '');
        $external_cron_url = admin_url('admin-ajax.php') . '?action=gi_seo_external_cron&key=' . $cron_key;
        ?>
        <style>
            .gi-wrap { font-family: -apple-system, BlinkMacSystemFont, sans-serif; }
            .gi-card { background: #fff; border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 4px; }
            .gi-card h3 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }
            .gi-stat { display: inline-block; padding: 15px 25px; background: #f5f5f5; margin-right: 15px; margin-bottom: 10px; border-radius: 4px; }
            .gi-stat-num { font-size: 28px; font-weight: bold; color: #333; }
            .gi-stat-label { font-size: 12px; color: #666; }
            .gi-btn { padding: 8px 16px; cursor: pointer; border: 1px solid #333; background: #fff; margin-right: 5px; margin-bottom: 5px; border-radius: 3px; transition: all 0.2s; }
            .gi-btn:hover { background: #333; color: #fff; }
            .gi-btn-primary { background: #333; color: #fff; }
            .gi-btn-primary:hover { background: #000; }
            .gi-btn-success { background: #28a745; color: #fff; border-color: #28a745; }
            .gi-btn-success:hover { background: #1e7e34; }
            .gi-btn:disabled { opacity: 0.5; cursor: not-allowed; }
            .gi-table { width: 100%; border-collapse: collapse; }
            .gi-table th, .gi-table td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
            .gi-table th { background: #f5f5f5; font-weight: 600; }
            .gi-table tr:hover { background: #fafafa; }
            .gi-input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 3px; }
            .gi-select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 3px; }
            .gi-textarea { padding: 8px 12px; border: 1px solid #ddd; width: 100%; height: 100px; border-radius: 3px; }
            .gi-badge { display: inline-block; padding: 3px 8px; font-size: 11px; border-radius: 3px; }
            .gi-badge-done { background: #333; color: #fff; }
            .gi-badge-pending { background: #eee; color: #666; }
            .gi-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; }
            .gi-modal-content { background: #fff; width: 900px; max-width: 95%; margin: 50px auto; max-height: 80vh; overflow-y: auto; border-radius: 8px; }
            .gi-modal-header { padding: 15px 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
            .gi-modal-body { padding: 20px; }
            .gi-progress { background: #eee; height: 20px; margin: 10px 0; border-radius: 10px; overflow: hidden; }
            .gi-progress-bar { background: #333; height: 100%; transition: width 0.3s; }
            .gi-cron-info { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-top: 15px; font-size: 12px; border-radius: 4px; }
            .gi-cron-url { background: #fff; padding: 8px; border: 1px solid #ccc; word-break: break-all; margin-top: 10px; font-family: monospace; }
            .gi-current-processing { background: #e3f2fd; border: 1px solid #2196f3; padding: 10px 15px; margin-bottom: 15px; border-radius: 4px; }
            .gi-queue-details { margin-top: 15px; }
            .gi-queue-item { padding: 5px 10px; background: #f5f5f5; margin-bottom: 3px; font-size: 12px; border-radius: 3px; }
            .gi-queue-item.completed { background: #e8f5e9; }
        </style>

        <div class="wrap gi-wrap">
            <h1>SEO Content Manager v<?php echo $this->version; ?></h1>

            <div class="gi-card">
                <h3>📊 統計</h3>
                <div class="gi-stat"><div class="gi-stat-num" id="stat-total">-</div><div class="gi-stat-label">総記事</div></div>
                <div class="gi-stat"><div class="gi-stat-num" id="stat-renovated">-</div><div class="gi-stat-label">処理済</div></div>
                <div class="gi-stat"><div class="gi-stat-num" id="stat-reports">-</div><div class="gi-stat-label">カルテ</div></div>
                <div class="gi-stat"><div class="gi-stat-num" id="stat-today">-</div><div class="gi-stat-label">本日</div></div>
            </div>

            <div class="gi-card">
                <h3>⚙️ キュー管理</h3>
                <div id="current-processing" class="gi-current-processing" style="display:none;">
                    <strong>🔄 処理中:</strong> <span id="current-title">-</span>
                </div>
                <div style="margin-bottom:15px; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                    <span>待機: <strong id="q-pending">0</strong></span> |
                    <span>処理中: <strong id="q-processing">0</strong></span> |
                    <span>完了: <strong id="q-completed">0</strong></span> |
                    <span>失敗: <strong id="q-failed">0</strong></span> |
                    <span id="q-status">停止中</span>
                </div>
                <div class="gi-progress" id="queue-progress-container" style="display:none;">
                    <div class="gi-progress-bar" id="queue-progress-bar" style="width:0%"></div>
                </div>
                <div id="queue-progress-text" style="font-size:12px;color:#666;margin-bottom:10px;"></div>
                <div style="margin-bottom: 10px;">
                    <button class="gi-btn gi-btn-success" id="btn-start">▶ 開始</button>
                    <button class="gi-btn" id="btn-stop">⏹ 停止</button>
                    <button class="gi-btn" id="btn-clear">🗑 クリア</button>
                    <button class="gi-btn" id="btn-process-one">1件処理</button>
                    <button class="gi-btn" id="btn-show-queue-details">📋 キュー詳細</button>
                </div>
                <div id="q-last" style="margin-top:10px;font-size:12px;color:#666;"></div>
                
                <div class="gi-cron-info">
                    <strong>🔄 バックグラウンド処理について</strong><br>
                    ブラウザを閉じても処理は継続されます。より確実な処理のため、サーバーのcrontabに以下を追加することを推奨：<br>
                    <div class="gi-cron-url">*/1 * * * * curl -s "<?php echo esc_url($external_cron_url); ?>" > /dev/null 2>&1</div>
                </div>
            </div>

            <div class="gi-card">
                <h3>🔗 URL一括追加</h3>
                <p style="font-size:12px;color:#666;margin-bottom:10px;">複数のURLを改行区切りで入力してキューに追加できます。</p>
                <textarea class="gi-textarea" id="bulk-urls" placeholder="https://example.com/post1&#10;https://example.com/post2&#10;https://example.com/post3"></textarea>
                <div style="margin-top:10px;">
                    <button class="gi-btn gi-btn-primary" id="btn-add-urls">URLをキューに追加</button>
                </div>
            </div>

            <div class="gi-card">
                <h3>📝 記事一覧</h3>
                <div style="margin-bottom:15px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <input type="text" class="gi-input" id="search" placeholder="検索..." style="width: 200px;">
                    <select class="gi-select" id="filter-status">
                        <option value="all">すべて</option>
                        <option value="renovated">処理済</option>
                        <option value="not_renovated">未処理</option>
                    </select>
                    <select class="gi-select" id="filter-sort">
                        <option value="date_desc">投稿日（新しい順）</option>
                        <option value="date_asc">投稿日（古い順）</option>
                        <option value="pv_desc">PV（多い順）</option>
                        <option value="renovated_desc">リノベ日（新しい順）</option>
                        <option value="renovated_asc">リノベ日（古い順）</option>
                    </select>
                    <select class="gi-select" id="per-page">
                        <option value="100">100件</option>
                        <option value="200">200件</option>
                        <option value="50">50件</option>
                    </select>
                    <button class="gi-btn" id="btn-search">🔍 検索</button>
                </div>
                <div style="margin-bottom:15px;">
                    <button class="gi-btn" id="btn-select-all">全選択</button>
                    <button class="gi-btn" id="btn-add-selected">選択をキューに追加</button>
                    <button class="gi-btn gi-btn-primary" id="btn-add-all">全記事をキューに追加</button>
                </div>
                <table class="gi-table" id="posts-table">
                    <thead>
                        <tr>
                            <th width="30"><input type="checkbox" id="check-all"></th>
                            <th>タイトル</th>
                            <th width="80">文字数</th>
                            <th width="60">PV</th>
                            <th width="90">リノベ日</th>
                            <th width="80">状態</th>
                            <th width="200">操作</th>
                        </tr>
                    </thead>
                    <tbody id="posts-tbody">
                        <tr><td colspan="7" style="text-align:center;padding:30px;">読み込み中...</td></tr>
                    </tbody>
                </table>
                <div id="pagination" style="margin-top:15px;"></div>
            </div>

            <!-- キュー詳細モーダル -->
            <div class="gi-modal" id="modal-queue-details">
                <div class="gi-modal-content">
                    <div class="gi-modal-header">
                        <h3 style="margin:0;">キュー詳細</h3>
                        <button class="gi-btn" onclick="jQuery('#modal-queue-details').hide()">閉じる</button>
                    </div>
                    <div class="gi-modal-body">
                        <h4>待機中</h4>
                        <div id="queue-pending-list" class="gi-queue-details"></div>
                        <h4 style="margin-top:20px;">完了済み（最新20件）</h4>
                        <div id="queue-completed-list" class="gi-queue-details"></div>
                    </div>
                </div>
            </div>

            <!-- リノベーション済み確認モーダル -->
            <div class="gi-modal" id="modal-confirm-renovated">
                <div class="gi-modal-content" style="width:600px;">
                    <div class="gi-modal-header">
                        <h3 style="margin:0;">⚠️ リノベーション済み記事があります</h3>
                        <button class="gi-btn" onclick="jQuery('#modal-confirm-renovated').hide()">閉じる</button>
                    </div>
                    <div class="gi-modal-body">
                        <p>以下の記事は既にリノベーション済みです。再度処理しますか？</p>
                        <div id="renovated-list" style="max-height:300px;overflow-y:auto;background:#f5f5f5;padding:10px;margin:15px 0;border-radius:4px;"></div>
                        <div style="text-align:right;">
                            <button class="gi-btn" onclick="jQuery('#modal-confirm-renovated').hide()">キャンセル</button>
                            <button class="gi-btn gi-btn-primary" id="btn-force-add">再処理する</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- カルテモーダル -->
            <div class="gi-modal" id="modal-report">
                <div class="gi-modal-content">
                    <div class="gi-modal-header">
                        <h3 style="margin:0;">📋 AIリノベーション・カルテ</h3>
                        <button class="gi-btn" onclick="jQuery('#modal-report').hide()">閉じる</button>
                    </div>
                    <div class="gi-modal-body" id="report-content">読み込み中...</div>
                </div>
            </div>
        </div>

        <script>
        jQuery(function($){
            var nonce = '<?php echo $nonce; ?>';
            var currentPage = 1;
            var totalInQueue = 0;
            var completedCount = 0;
            var pendingRenovatedIds = [];

            function loadStats() {
                $.post(ajaxurl, {action:'gi_seo_get_analytics',nonce:nonce}, function(r){
                    if(r.success){
                        $('#stat-total').text(r.data.total_posts);
                        $('#stat-renovated').text(r.data.renovated_posts + ' (' + r.data.renovation_rate + '%)');
                        $('#stat-reports').text(r.data.report_count);
                        $('#stat-today').text(r.data.today_renovated);
                    }
                });
            }

            function loadQueue() {
                $.post(ajaxurl, {action:'gi_seo_get_queue_status',nonce:nonce}, function(r){
                    if(r.success){
                        $('#q-pending').text(r.data.pending);
                        $('#q-processing').text(r.data.processing);
                        $('#q-completed').text(r.data.completed);
                        $('#q-failed').text(r.data.failed);
                        $('#q-status').text(r.data.running ? '🟢 実行中' : '⚪ 停止中').css('color', r.data.running ? '#090' : '#666');
                        if(r.data.last) $('#q-last').text('最終: ' + r.data.last);

                        if(r.data.current_processing) {
                            $('#current-processing').show();
                            $('#current-title').text(r.data.current_processing.post_title + ' (ID:' + r.data.current_processing.post_id + ')');
                        } else {
                            $('#current-processing').hide();
                        }

                        if(r.data.running && r.data.total_in_queue > 0) {
                            totalInQueue = r.data.pending + r.data.processing + r.data.completed;
                            completedCount = r.data.completed;
                            var progress = totalInQueue > 0 ? (completedCount / totalInQueue * 100) : 0;
                            $('#queue-progress-container').show();
                            $('#queue-progress-bar').css('width', progress + '%');
                            $('#queue-progress-text').text('進捗: ' + completedCount + ' / ' + totalInQueue + ' (' + Math.round(progress) + '%)');
                        } else {
                            $('#queue-progress-container').hide();
                            $('#queue-progress-text').text('');
                        }
                    }
                });
            }

            function loadPosts(page) {
                page = page || 1;
                currentPage = page;
                $.post(ajaxurl, {
                    action: 'gi_seo_get_posts',
                    nonce: nonce,
                    page: page,
                    per_page: $('#per-page').val(),
                    search: $('#search').val(),
                    status: $('#filter-status').val(),
                    sort: $('#filter-sort').val()
                }, function(r){
                    if(r.success){
                        var html = '';
                        if(r.data.posts.length === 0){
                            html = '<tr><td colspan="7" style="text-align:center;padding:30px;">なし</td></tr>';
                        } else {
                            r.data.posts.forEach(function(p){
                                var badge = p.has_report ? '<span class="gi-badge gi-badge-done">カルテ有</span>' : 
                                           (p.renovated ? '<span class="gi-badge gi-badge-done">済</span>' : 
                                           '<span class="gi-badge gi-badge-pending">未</span>');
                                var renovateInfo = p.renovated_at ? p.renovated_at : '-';
                                if(p.renovation_count > 1) renovateInfo += ' <small>('+p.renovation_count+'回)</small>';
                                
                                html += '<tr data-id="'+p.id+'">';
                                html += '<td><input type="checkbox" class="post-check" value="'+p.id+'" data-renovated="'+(p.renovated?'1':'0')+'"></td>';
                                html += '<td><a href="'+p.url+'" target="_blank">'+p.title+'</a></td>';
                                html += '<td>'+p.char_count.toLocaleString()+'</td>';
                                html += '<td>'+p.pv+'</td>';
                                html += '<td>'+renovateInfo+'</td>';
                                html += '<td>'+badge+'</td>';
                                html += '<td>';
                                html += '<button class="gi-btn btn-report" data-id="'+p.id+'" '+(p.has_report?'':'disabled')+' style="padding:4px 8px;font-size:12px;">カルテ</button> ';
                                html += '<button class="gi-btn btn-run" data-id="'+p.id+'" style="padding:4px 8px;font-size:12px;">実行</button> ';
                                html += '<button class="gi-btn btn-queue" data-id="'+p.id+'" data-renovated="'+(p.renovated?'1':'0')+'" style="padding:4px 8px;font-size:12px;">キュー</button>';
                                html += '</td></tr>';
                            });
                        }
                        $('#posts-tbody').html(html);

                        var pagHtml = '';
                        if(r.data.pages > 1){
                            for(var i=1; i<=Math.min(r.data.pages, 20); i++){
                                pagHtml += '<button class="gi-btn page-btn'+(i===currentPage?' gi-btn-primary':'')+'" data-page="'+i+'" style="padding:4px 10px;">'+i+'</button> ';
                            }
                            if(r.data.pages > 20) pagHtml += '... (全'+r.data.pages+'ページ)';
                        }
                        $('#pagination').html(pagHtml);
                    }
                });
            }

            loadStats();
            loadQueue();
            loadPosts();
            setInterval(loadQueue, 5000);
            setInterval(loadStats, 30000);

            $('#btn-search').click(function(){ loadPosts(1); });
            $('#search').keyup(function(e){ if(e.key==='Enter') loadPosts(1); });
            $('#filter-status, #filter-sort, #per-page').change(function(){ loadPosts(1); });
            $(document).on('click', '.page-btn', function(){ loadPosts($(this).data('page')); });

            $('#check-all').change(function(){ $('.post-check').prop('checked', this.checked); });
            $('#btn-select-all').click(function(){ $('.post-check').prop('checked', true); });

            function addToQueue(ids, force) {
                $.post(ajaxurl, {action:'gi_seo_add_to_queue',nonce:nonce,post_ids:ids,force:force?'true':'false'}, function(r){
                    if(r.success){
                        if(r.data.need_confirm && r.data.already_renovated && r.data.already_renovated.length > 0) {
                            pendingRenovatedIds = ids;
                            var listHtml = '';
                            r.data.already_renovated.forEach(function(item){
                                listHtml += '<div style="padding:5px 0;border-bottom:1px solid #ddd;">ID:'+item.id+' - '+item.title+'</div>';
                            });
                            $('#renovated-list').html(listHtml);
                            $('#modal-confirm-renovated').show();
                        } else {
                            alert(r.data.message);
                        }
                        loadQueue();
                    } else {
                        alert(r.data);
                    }
                });
            }

            $('#btn-add-selected').click(function(){
                var ids = [];
                $('.post-check:checked').each(function(){ ids.push($(this).val()); });
                if(ids.length === 0){ alert('選択してください'); return; }
                addToQueue(ids, false);
            });

            $('#btn-force-add').click(function(){
                $('#modal-confirm-renovated').hide();
                addToQueue(pendingRenovatedIds, true);
            });

            $(document).on('click', '.btn-queue', function(){
                var id = $(this).data('id');
                var isRenovated = $(this).data('renovated') == '1';
                
                if(isRenovated) {
                    if(!confirm('この記事は既にリノベーション済みです。再度処理しますか？')) return;
                    addToQueue([id], true);
                } else {
                    addToQueue([id], false);
                }
            });

            $('#btn-add-all').click(function(){
                if(!confirm('上限200件をキューに追加しますか？')) return;
                $.post(ajaxurl, {action:'gi_seo_add_all_to_queue',nonce:nonce,status:$('#filter-status').val(),limit:200}, function(r){
                    alert(r.success ? r.data.message : r.data);
                    loadQueue();
                });
            });

            $('#btn-add-urls').click(function(){
                var urls = $('#bulk-urls').val().trim();
                if(!urls){ alert('URLを入力してください'); return; }
                
                var btn = $(this);
                btn.prop('disabled', true).text('追加中...');
                
                $.post(ajaxurl, {action:'gi_seo_add_urls_to_queue',nonce:nonce,urls:urls,force:'false'}, function(r){
                    btn.prop('disabled', false).text('URLをキューに追加');
                    
                    if(r.success){
                        var msg = r.data.message;
                        if(r.data.not_found && r.data.not_found.length > 0) {
                            msg += '\n\n見つからなかったURL:\n' + r.data.not_found.slice(0,5).join('\n');
                            if(r.data.not_found.length > 5) msg += '\n...他' + (r.data.not_found.length - 5) + '件';
                        }
                        
                        if(r.data.need_confirm && r.data.already_renovated && r.data.already_renovated.length > 0) {
                            var listHtml = '';
                            r.data.already_renovated.forEach(function(item){
                                listHtml += '<div style="padding:5px 0;border-bottom:1px solid #ddd;">'+item.title+'</div>';
                            });
                            $('#renovated-list').html(listHtml);
                            pendingRenovatedIds = r.data.already_renovated.map(function(item){ return item.id; });
                            $('#modal-confirm-renovated').show();
                        } else {
                            alert(msg);
                            $('#bulk-urls').val('');
                        }
                        loadQueue();
                    } else {
                        alert('エラー: ' + r.data);
                    }
                });
            });

            $(document).on('click', '.btn-run', function(){
                var btn = $(this), id = btn.data('id');
                btn.prop('disabled',true).text('処理中...');
                $.post(ajaxurl, {action:'gi_seo_renovate',nonce:nonce,post_id:id}, function(r){
                    if(r.success){
                        alert('完了: ' + r.data.original_char_count + ' → ' + r.data.new_char_count + '文字 (' + r.data.processing_time + '秒)');
                        loadPosts(currentPage);
                        loadStats();
                    } else {
                        alert('エラー: ' + r.data);
                    }
                    btn.prop('disabled',false).text('実行');
                });
            });

            $('#btn-start').click(function(){
                $.post(ajaxurl, {action:'gi_seo_start_queue',nonce:nonce}, function(r){ 
                    loadQueue(); 
                    alert('キュー処理を開始しました。ブラウザを閉じても処理は継続されます。');
                });
            });

            $('#btn-stop').click(function(){
                $.post(ajaxurl, {action:'gi_seo_stop_queue',nonce:nonce}, function(r){ loadQueue(); });
            });

            $('#btn-clear').click(function(){
                if(!confirm('クリアしますか？')) return;
                $.post(ajaxurl, {action:'gi_seo_clear_queue',nonce:nonce}, function(r){ loadQueue(); });
            });

            $('#btn-process-one').click(function(){
                $.post(ajaxurl, {action:'gi_seo_process_single',nonce:nonce}, function(r){
                    loadQueue();
                    loadPosts(currentPage);
                });
            });

            $('#btn-show-queue-details').click(function(){
                $('#modal-queue-details').show();
                $.post(ajaxurl, {action:'gi_seo_get_queue_details',nonce:nonce}, function(r){
                    if(r.success){
                        var pendingHtml = '';
                        if(r.data.pending.length === 0) {
                            pendingHtml = '<p style="color:#666;">待機中の記事はありません</p>';
                        } else {
                            r.data.pending.forEach(function(item){
                                pendingHtml += '<div class="gi-queue-item">ID:'+item.post_id+' - '+item.post_title+'</div>';
                            });
                        }
                        $('#queue-pending-list').html(pendingHtml);
                        
                        var completedHtml = '';
                        if(r.data.completed.length === 0) {
                            completedHtml = '<p style="color:#666;">完了した記事はありません</p>';
                        } else {
                            r.data.completed.forEach(function(item){
                                completedHtml += '<div class="gi-queue-item completed">✓ '+item.post_title+' ('+item.completed_at+')</div>';
                            });
                        }
                        $('#queue-completed-list').html(completedHtml);
                    }
                });
            });

            $(document).on('click', '.btn-report', function(){
                var id = $(this).data('id');
                $('#modal-report').show();
                $('#report-content').html('読み込み中...');
                $.post(ajaxurl, {action:'gi_seo_get_report',nonce:nonce,post_id:id}, function(r){
                    if(r.success){
                        var d = r.data;
                        var html = '<table class="gi-table">';
                        html += '<tr><th>実行日時</th><td>'+d.date+'</td></tr>';
                        html += '<tr><th>モデル</th><td>'+d.model+'</td></tr>';
                        html += '<tr><th>処理時間</th><td>'+(d.processing_time||'-')+'秒</td></tr>';
                        html += '<tr><th>シードKW</th><td>'+(d.seed_keyword||'-')+'</td></tr>';
                        html += '<tr><th>キーフレーズ</th><td>'+(d.keyphrase||'-')+'</td></tr>';
                        html += '<tr><th>重要語</th><td>'+(d.keywords?d.keywords.join(', '):'-')+'</td></tr>';
                        html += '<tr><th>サジェスト</th><td>'+(d.suggests?d.suggests.join(', '):'-')+'</td></tr>';
                        html += '<tr><th>文字数</th><td>'+(d.original_char_count||0)+' → '+(d.new_char_count||0)+' ('+(d.new_char_count-d.original_char_count>0?'+':'')+(d.new_char_count-d.original_char_count)+')</td></tr>';
                        if(d.meta_description) html += '<tr><th>メタ説明</th><td>'+d.meta_description+'</td></tr>';
                        html += '</table>';
                        html += '<div style="margin-top:15px;"><a href="'+d.url+'" target="_blank" class="gi-btn">記事を見る</a> <a href="'+d.edit_url+'" target="_blank" class="gi-btn">編集</a></div>';
                        $('#report-content').html(html);
                    } else {
                        $('#report-content').html('<p style="color:red;">'+r.data+'</p>');
                    }
                });
            });

            $('.gi-modal').click(function(e){ if(e.target===this) $(this).hide(); });
        });
        </script>
        <?php
    }

    public function render_merge_page() {
        $nonce = wp_create_nonce('gi_seo_nonce');
        ?>
        <style>
            .gi-wrap { font-family: -apple-system, BlinkMacSystemFont, sans-serif; }
            .gi-card { background: #fff; border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 4px; }
            .gi-card h3 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }
            .gi-btn { padding: 8px 16px; cursor: pointer; border: 1px solid #333; background: #fff; margin-right: 5px; border-radius: 3px; }
            .gi-btn:hover { background: #333; color: #fff; }
            .gi-btn-primary { background: #333; color: #fff; }
            .gi-btn:disabled { opacity: 0.5; }
            .gi-group { border: 1px solid #ddd; margin-bottom: 15px; border-radius: 4px; overflow: hidden; }
            .gi-group-header { background: #f5f5f5; padding: 12px 15px; display: flex; justify-content: space-between; align-items: center; }
            .gi-group-item { padding: 10px 15px; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
            .gi-group-item.parent { background: #f9f9f9; }
            .gi-similarity { display: inline-block; padding: 2px 6px; font-size: 11px; border-radius: 3px; margin-left: 10px; }
            .gi-similarity-high { background: #d4edda; color: #155724; }
            .gi-similarity-medium { background: #fff3cd; color: #856404; }
            .gi-similarity-low { background: #f8d7da; color: #721c24; }
            .gi-progress { background: #eee; height: 20px; margin: 10px 0; border-radius: 10px; overflow: hidden; }
            .gi-progress-bar { background: #333; height: 100%; transition: width 0.3s; }
            .gi-input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 3px; }
            .gi-select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 3px; }
            .gi-table { width: 100%; border-collapse: collapse; }
            .gi-table th, .gi-table td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
            .gi-table th { background: #f5f5f5; }
        </style>

        <div class="wrap gi-wrap">
            <h1>📎 記事統合（M&A）</h1>

            <div class="gi-card">
                <h3>🔑 キーワード抽出</h3>
                <div style="margin-bottom:10px; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                    <span>総記事: <strong id="kw-total">-</strong></span> |
                    <span>抽出済: <strong id="kw-extracted">-</strong></span> |
                    <span>未抽出: <strong id="kw-pending">-</strong></span>
                </div>
                <div class="gi-progress" id="analyze-progress-container" style="display:none;">
                    <div class="gi-progress-bar" id="analyze-progress-bar" style="width:0%"></div>
                </div>
                <div id="analyze-progress-text" style="font-size:12px;color:#666;margin-bottom:10px;"></div>
                <button class="gi-btn gi-btn-primary" id="btn-analyze">キーワード抽出開始</button>
                <button class="gi-btn" id="btn-stop-analyze" style="display:none;">停止</button>
                <span id="analyze-status" style="margin-left:10px;"></span>
            </div>

            <div class="gi-card">
                <h3>🔗 統合候補</h3>
                <div style="margin-bottom:15px;">
                    <label>最低類似度: </label>
                    <select class="gi-select" id="min-similarity">
                        <option value="60">60%以上（推奨）</option>
                        <option value="70">70%以上</option>
                        <option value="80">80%以上</option>
                        <option value="50">50%以上</option>
                    </select>
                    <button class="gi-btn" id="btn-load-candidates">候補を読み込む</button>
                    <button class="gi-btn gi-btn-primary" id="btn-bulk-merge">選択を一括統合</button>
                </div>
                <p style="font-size:12px;color:#666;">※地域が異なる記事は類似度が低く計算され、候補から除外されます</p>
                <div id="merge-candidates"></div>
                <div id="merge-pagination" style="margin-top:15px;"></div>
            </div>

            <div class="gi-card">
                <h3>📜 統合履歴</h3>
                <button class="gi-btn" id="btn-load-history">履歴を読み込む</button>
                <div id="merge-history" style="margin-top:15px;"></div>
            </div>
        </div>

        <script>
        jQuery(function($){
            var nonce = '<?php echo $nonce; ?>';
            var analyzing = false;
            var currentPage = 1;

            function loadKwStats() {
                $.post(ajaxurl, {action:'gi_seo_get_keyword_stats',nonce:nonce}, function(r){
                    if(r.success){
                        $('#kw-total').text(r.data.total);
                        $('#kw-extracted').text(r.data.extracted);
                        $('#kw-pending').text(r.data.pending);
                    }
                });
            }

            loadKwStats();

            $('#btn-analyze').click(function(){
                if(analyzing) return;
                analyzing = true;
                $(this).prop('disabled',true);
                $('#btn-stop-analyze').show();
                $('#analyze-progress-container').show();

                var offset = 0, total = 0, totalPending = parseInt($('#kw-pending').text()) || 100;

                function batch() {
                    if(!analyzing) { finish(); return; }
                    $.post(ajaxurl, {action:'gi_seo_analyze_keywords',nonce:nonce,batch_size:50,offset:offset}, function(r){
                        if(r.success){
                            total += r.data.count;
                            var progress = Math.min((total / totalPending) * 100, 100);
                            $('#analyze-progress-bar').css('width', progress + '%');
                            $('#analyze-progress-text').text('処理中: ' + total + '件');
                            $('#analyze-status').text('処理中...');
                            loadKwStats();
                            if(r.data.has_more && analyzing){
                                offset += 50;
                                setTimeout(batch, 500);
                            } else {
                                finish();
                            }
                        } else {
                            finish();
                        }
                    });
                }

                function finish() {
                    analyzing = false;
                    $('#btn-analyze').prop('disabled',false);
                    $('#btn-stop-analyze').hide();
                    $('#analyze-status').text('完了: ' + total + '件');
                    loadKwStats();
                }

                batch();
            });

            $('#btn-stop-analyze').click(function(){
                analyzing = false;
                $(this).hide();
                $('#analyze-status').text('停止しました');
            });

            $('#btn-load-candidates').click(function(){
                loadCandidates(1);
            });

            function loadCandidates(page) {
                currentPage = page;
                $('#merge-candidates').html('<p>読み込み中...</p>');
                $.post(ajaxurl, {
                    action:'gi_seo_get_merge_candidates',
                    nonce:nonce,
                    page:page,
                    per_page:50,
                    min_similarity: $('#min-similarity').val()
                }, function(r){
                    if(r.success){
                        if(r.data.groups.length === 0){
                            $('#merge-candidates').html('<p>候補なし</p>');
                            return;
                        }
                        var html = '<p style="margin-bottom:15px;">統合候補: <strong>' + r.data.total + '</strong>グループ</p>';
                        r.data.groups.forEach(function(g){
                            html += '<div class="gi-group" data-parent="'+g.parent.ID+'">';
                            html += '<div class="gi-group-header">';
                            html += '<div><input type="checkbox" class="group-check" data-parent="'+g.parent.ID+'" data-children="'+g.children.map(function(c){return c.ID}).join(',')+'"> ';
                            html += '<strong>'+g.keyword+'</strong> ('+g.count+'件, 総PV:'+g.total_pv+')</div>';
                            html += '<button class="gi-btn btn-merge-group" data-parent="'+g.parent.ID+'" data-children="'+g.children.map(function(c){return c.ID}).join(',')+'">統合実行</button>';
                            html += '</div>';
                            
                            html += '<div class="gi-group-item parent">';
                            html += '<span>【親】'+g.parent.title+' ('+g.parent.char_count+'文字, PV:'+g.parent.pv+')</span>';
                            html += '<a href="'+g.parent.url+'" target="_blank" class="gi-btn" style="padding:4px 8px;font-size:12px;">見る</a>';
                            html += '</div>';
                            
                            g.children.forEach(function(c){
                                var simClass = c.similarity >= 80 ? 'gi-similarity-high' : (c.similarity >= 60 ? 'gi-similarity-medium' : 'gi-similarity-low');
                                html += '<div class="gi-group-item">';
                                html += '<span><input type="checkbox" class="child-check" data-parent="'+g.parent.ID+'" value="'+c.ID+'" checked> ';
                                html += c.title+' ('+c.char_count+'文字, PV:'+c.pv+')';
                                html += '<span class="gi-similarity '+simClass+'">類似度:'+Math.round(c.similarity)+'%</span></span>';
                                html += '<a href="'+c.url+'" target="_blank" class="gi-btn" style="padding:4px 8px;font-size:12px;">見る</a>';
                                html += '</div>';
                            });
                            html += '</div>';
                        });
                        $('#merge-candidates').html(html);

                        var pagHtml = '';
                        for(var i=1; i<=Math.min(r.data.pages, 20); i++){
                            pagHtml += '<button class="gi-btn merge-page'+(i===currentPage?' gi-btn-primary':'')+'" data-page="'+i+'" style="padding:4px 10px;">'+i+'</button> ';
                        }
                        $('#merge-pagination').html(pagHtml);
                    }
                });
            }

            $(document).on('click', '.merge-page', function(){
                loadCandidates($(this).data('page'));
            });

            $(document).on('click', '.btn-merge-group', function(){
                var parent = $(this).data('parent');
                var children = [];
                $(this).closest('.gi-group').find('.child-check:checked').each(function(){
                    children.push($(this).val());
                });
                if(children.length === 0){
                    alert('統合する子記事を選択してください');
                    return;
                }
                if(!confirm(children.length + '件の記事を親記事に統合しますか？')) return;
                
                var btn = $(this);
                btn.prop('disabled', true).text('処理中...');
                
                $.post(ajaxurl, {action:'gi_seo_execute_merge',nonce:nonce,parent_id:parent,child_ids:children}, function(r){
                    alert(r.success ? r.data.message : r.data);
                    btn.prop('disabled', false).text('統合実行');
                    if(r.success) loadCandidates(currentPage);
                });
            });

            $('#btn-bulk-merge').click(function(){
                var groups = [];
                $('.group-check:checked').each(function(){
                    var parent = $(this).data('parent');
                    var childrenStr = $(this).data('children').toString();
                    var children = childrenStr ? childrenStr.split(',').map(Number) : [];
                    if(children.length > 0) {
                        groups.push({parent_id:parent, child_ids:children});
                    }
                });
                if(groups.length === 0){
                    alert('統合するグループを選択してください');
                    return;
                }
                if(!confirm(groups.length + 'グループを一括統合しますか？')) return;
                
                $.post(ajaxurl, {action:'gi_seo_bulk_merge',nonce:nonce,groups:groups}, function(r){
                    alert(r.success ? r.data.message : r.data);
                    if(r.success) loadCandidates(currentPage);
                });
            });

            $('#btn-load-history').click(function(){
                $.post(ajaxurl, {action:'gi_seo_get_merge_history',nonce:nonce,page:1,per_page:50}, function(r){
                    if(r.success){
                        if(r.data.items.length === 0){
                            $('#merge-history').html('<p>履歴なし</p>');
                            return;
                        }
                        var html = '<table class="gi-table">';
                        html += '<thead><tr><th>親記事</th><th>子記事</th><th>日時</th><th>操作</th></tr></thead><tbody>';
                        r.data.items.forEach(function(item){
                            html += '<tr>';
                            html += '<td>'+(item.parent_title || 'ID:'+item.parent_id)+'</td>';
                            html += '<td>'+item.child_title+'</td>';
                            html += '<td>'+item.merged_at+'</td>';
                            html += '<td><button class="gi-btn btn-undo-merge" data-id="'+item.id+'" style="padding:4px 8px;font-size:12px;">取消</button></td>';
                            html += '</tr>';
                        });
                        html += '</tbody></table>';
                        $('#merge-history').html(html);
                    }
                });
            });

            $(document).on('click', '.btn-undo-merge', function(){
                if(!confirm('この統合を取り消しますか？')) return;
                var btn = $(this);
                var id = btn.data('id');
                btn.prop('disabled', true);
                $.post(ajaxurl, {action:'gi_seo_undo_merge',nonce:nonce,history_id:id}, function(r){
                    alert(r.success ? r.data : r.data);
                    if(r.success) btn.closest('tr').remove();
                    else btn.prop('disabled', false);
                });
            });
        });
        </script>
        <?php
    }

    public function render_404_page() {
        $nonce = wp_create_nonce('gi_seo_nonce');
        ?>
        <style>
            .gi-wrap { font-family: -apple-system, BlinkMacSystemFont, sans-serif; }
            .gi-card { background: #fff; border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 4px; }
            .gi-card h3 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }
            .gi-btn { padding: 8px 16px; cursor: pointer; border: 1px solid #333; background: #fff; margin-right: 5px; margin-bottom: 5px; border-radius: 3px; }
            .gi-btn:hover { background: #333; color: #fff; }
            .gi-btn-primary { background: #333; color: #fff; }
            .gi-btn-sm { padding: 4px 8px; font-size: 12px; }
            .gi-btn-success { background: #28a745; color: #fff; border-color: #28a745; }
            .gi-btn-success:hover { background: #1e7e34; }
            .gi-btn-danger { background: #dc3545; color: #fff; border-color: #dc3545; }
            .gi-btn-danger:hover { background: #c82333; }
            .gi-btn-warning { background: #ffc107; color: #000; border-color: #ffc107; }
            .gi-table { width: 100%; border-collapse: collapse; }
            .gi-table th, .gi-table td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; font-size: 13px; }
            .gi-table th { background: #f5f5f5; }
            .gi-table tr:hover { background: #fafafa; }
            .gi-input { padding: 6px 10px; border: 1px solid #ddd; font-size: 12px; border-radius: 3px; }
            .gi-select { padding: 6px 10px; border: 1px solid #ddd; font-size: 12px; border-radius: 3px; }
            .gi-debug { background: #f9f9f9; padding: 10px; font-size: 11px; color: #666; margin-top: 10px; border-radius: 4px; }
            .gi-progress { background: #eee; height: 20px; margin: 10px 0; border-radius: 10px; overflow: hidden; }
            .gi-progress-bar { background: #333; height: 100%; transition: width 0.3s; }
            .gi-redirect-set { background: #d4edda; }
            .gi-score { display: inline-block; padding: 2px 6px; font-size: 10px; background: #e9ecef; border-radius: 3px; }
            .gi-score-high { background: #28a745; color: #fff; }
            .gi-score-medium { background: #ffc107; color: #000; }
            .gi-action-bar { background: #f5f5f5; padding: 15px; margin-bottom: 20px; border-radius: 4px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
            .gi-action-bar-selected { background: #e3f2fd; border: 1px solid #2196f3; }
            .gi-selected-count { font-weight: bold; color: #1976d2; }
        </style>

        <div class="wrap gi-wrap">
            <h1>🔀 404管理</h1>

            <div class="gi-card">
                <h3>🤖 自動リダイレクト設定</h3>
                <p style="color:#666;font-size:13px;">404ログのURLをサイト内の記事と照合し、PV数やタイトル一致度を基に自動でリダイレクト先を設定します。</p>
                <div style="margin-bottom:15px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <label>最低スコア: </label>
                    <select class="gi-select" id="auto-min-score">
                        <option value="50">50以上（推奨）</option>
                        <option value="30">30以上（緩め）</option>
                        <option value="70">70以上（厳格）</option>
                    </select>
                    <label>処理件数: </label>
                    <select class="gi-select" id="auto-limit">
                        <option value="50">50件</option>
                        <option value="100">100件</option>
                        <option value="200">200件</option>
                    </select>
                    <button class="gi-btn gi-btn-success" id="btn-auto-redirect-all">🤖 全件自動リダイレクト</button>
                </div>
                <div class="gi-progress" id="auto-progress-container" style="display:none;">
                    <div class="gi-progress-bar" id="auto-progress-bar" style="width:0%"></div>
                </div>
                <div id="auto-progress-text" style="font-size:12px;color:#666;"></div>
            </div>

            <div class="gi-card">
                <h3>📋 404ログ</h3>
                
                <!-- フィルター・検索 -->
                <div style="margin-bottom:15px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <input type="text" class="gi-input" id="404-search" placeholder="URL/タイトル検索..." style="width: 200px;">
                    <select class="gi-select" id="404-filter">
                        <option value="all">すべて</option>
                        <option value="unset">未設定のみ</option>
                        <option value="set">設定済みのみ</option>
                    </select>
                    <button class="gi-btn" id="btn-load-404">🔄 読み込み</button>
                    <button class="gi-btn" id="btn-debug-404">🔧 デバッグ</button>
                </div>
                
                <!-- 選択時のアクションバー -->
                <div class="gi-action-bar" id="action-bar" style="display:none;">
                    <span><span class="gi-selected-count" id="selected-count">0</span>件選択中</span>
                    <button class="gi-btn gi-btn-success gi-btn-sm" id="btn-auto-selected">🤖 選択を自動リダイレクト</button>
                    <button class="gi-btn gi-btn-warning gi-btn-sm" id="btn-clear-selected">⚡ 選択のリダイレクト解除</button>
                    <button class="gi-btn gi-btn-danger gi-btn-sm" id="btn-delete-selected">🗑 選択を削除</button>
                    <button class="gi-btn gi-btn-sm" id="btn-clear-selection">✖ 選択解除</button>
                </div>
                
                <div id="debug-result" class="gi-debug" style="display:none;"></div>
                
                <table class="gi-table">
                    <thead>
                        <tr>
                            <th width="30"><input type="checkbox" id="check-all-404"></th>
                            <th>URL / 推測タイトル</th>
                            <th width="60">回数</th>
                            <th width="100">最終検出</th>
                            <th width="400">リダイレクト設定</th>
                        </tr>
                    </thead>
                    <tbody id="404-tbody">
                        <tr><td colspan="5" style="text-align:center;padding:30px;">「読み込み」をクリックしてください</td></tr>
                    </tbody>
                </table>
                <div id="404-pagination" style="margin-top:15px;"></div>
                
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                    <button class="gi-btn gi-btn-danger" id="btn-clear-404">🗑 未設定ログを全削除</button>
                </div>
            </div>
        </div>

        <script>
        jQuery(function($){
            var nonce = '<?php echo $nonce; ?>';
            var currentPage = 1;

            $('#btn-load-404').click(function(){ load404(1); });
            
            // 検索
            $('#404-search').keyup(function(e){ if(e.key==='Enter') load404(1); });
            $('#404-filter').change(function(){ load404(1); });

            $('#btn-debug-404').click(function(){
                $.post(ajaxurl, {action:'gi_seo_debug_404',nonce:nonce}, function(r){
                    var html = '<strong>デバッグ結果:</strong><br>';
                    if(r.success){
                        html += 'テーブル: ' + r.data.table_exists + ' | 挿入: ' + r.data.insert_result + ' | 総数: ' + r.data.total_count;
                    } else {
                        html += 'エラー: ' + r.data;
                    }
                    $('#debug-result').html(html).show();
                    load404(1);
                });
            });

            function updateSelectedCount() {
                var count = $('.log-check:checked').length;
                $('#selected-count').text(count);
                if(count > 0) {
                    $('#action-bar').show().addClass('gi-action-bar-selected');
                } else {
                    $('#action-bar').hide().removeClass('gi-action-bar-selected');
                }
            }

            function load404(page) {
                currentPage = page;
                $('#404-tbody').html('<tr><td colspan="5" style="text-align:center;">読み込み中...</td></tr>');
                $.post(ajaxurl, {
                    action:'gi_seo_get_404_logs',
                    nonce:nonce,
                    page:page,
                    per_page:100,
                    filter:$('#404-filter').val(),
                    search:$('#404-search').val()
                }, function(r){
                    if(r.success){
                        if(r.data.logs.length === 0){
                            $('#404-tbody').html('<tr><td colspan="5" style="text-align:center;padding:30px;">404ログなし</td></tr>');
                            return;
                        }
                        var html = '';
                        r.data.logs.forEach(function(log){
                            var isSet = log.redirect_to || log.redirect_url;
                            var rowClass = isSet ? 'gi-redirect-set' : '';
                            var currentVal = log.redirect_to || log.redirect_url || '';
                            var scoreHtml = '';
                            if(log.match_score > 0) {
                                var scoreClass = log.match_score >= 70 ? 'gi-score-high' : (log.match_score >= 50 ? 'gi-score-medium' : 'gi-score');
                                scoreHtml = '<span class="gi-score '+scoreClass+'">スコア:'+log.match_score+'</span> ';
                            }
                            
                            html += '<tr data-id="'+log.id+'" class="'+rowClass+'">';
                            html += '<td><input type="checkbox" class="log-check" value="'+log.id+'"></td>';
                            html += '<td style="word-break:break-all;max-width:300px;">';
                            html += '<div style="font-size:12px;color:#666;">'+log.url+'</div>';
                            if(log.title) html += '<div style="font-weight:bold;">推測: '+log.title+'</div>';
                            html += '</td>';
                            html += '<td><strong>'+log.count+'</strong></td>';
                            html += '<td style="font-size:11px;">'+log.last_seen+'</td>';
                            html += '<td>';
                            html += scoreHtml;
                            html += '<input type="text" class="gi-input redirect-input" data-id="'+log.id+'" placeholder="記事IDまたはURL" value="'+currentVal+'" style="width:80px;"> ';
                            html += '<button class="gi-btn gi-btn-sm btn-set-redirect" data-id="'+log.id+'">設定</button> ';
                            if(isSet) {
                                html += '<button class="gi-btn gi-btn-sm gi-btn-warning btn-clear-redirect" data-id="'+log.id+'">解除</button> ';
                            }
                            html += '<button class="gi-btn gi-btn-sm gi-btn-success btn-auto-single" data-id="'+log.id+'" data-url="'+encodeURIComponent(log.url)+'" data-title="'+encodeURIComponent(log.title||'')+'">自動</button> ';
                            html += '<button class="gi-btn gi-btn-sm btn-find-similar" data-url="'+encodeURIComponent(log.url)+'" data-title="'+encodeURIComponent(log.title||'')+'">検索</button>';
                            if(log.redirect_title) html += '<br><small style="color:#090;">→ '+log.redirect_title+'</small>';
                            html += '</td></tr>';
                        });
                        $('#404-tbody').html(html);

                        var pagHtml = '合計: ' + r.data.total + '件 | ';
                        for(var i=1; i<=Math.min(r.data.pages, 10); i++){
                            pagHtml += '<button class="gi-btn gi-btn-sm 404-page'+(i===currentPage?' gi-btn-primary':'')+'" data-page="'+i+'">'+i+'</button> ';
                        }
                        $('#404-pagination').html(pagHtml);
                        
                        updateSelectedCount();
                    } else {
                        $('#404-tbody').html('<tr><td colspan="5" style="color:red;">エラー: '+r.data+'</td></tr>');
                    }
                });
            }

            $(document).on('click', '.404-page', function(){ load404($(this).data('page')); });
            
            // 全選択
            $('#check-all-404').change(function(){
                $('.log-check').prop('checked', this.checked);
                updateSelectedCount();
            });
            
            $(document).on('change', '.log-check', function(){
                updateSelectedCount();
            });
            
            $('#btn-clear-selection').click(function(){
                $('.log-check').prop('checked', false);
                $('#check-all-404').prop('checked', false);
                updateSelectedCount();
            });

            // 選択したものを自動リダイレクト
            $('#btn-auto-selected').click(function(){
                var ids = [];
                $('.log-check:checked').each(function(){ ids.push($(this).val()); });
                if(ids.length === 0){ alert('対象を選択してください'); return; }
                
                if(!confirm(ids.length + '件に自動リダイレクトを設定しますか？')) return;
                
                var btn = $(this);
                btn.prop('disabled', true).text('処理中...');
                
                $.post(ajaxurl, {
                    action: 'gi_seo_auto_redirect_selected',
                    nonce: nonce,
                    ids: ids,
                    min_score: $('#auto-min-score').val()
                }, function(r){
                    btn.prop('disabled', false).text('🤖 選択を自動リダイレクト');
                    
                    if(r.success){
                        alert(r.data.message);
                        load404(currentPage);
                    } else {
                        alert('エラー: ' + r.data);
                    }
                });
            });

            // 選択したもののリダイレクト解除
            $('#btn-clear-selected').click(function(){
                var ids = [];
                $('.log-check:checked').each(function(){ ids.push($(this).val()); });
                if(ids.length === 0){ alert('対象を選択してください'); return; }
                
                if(!confirm(ids.length + '件のリダイレクト設定を解除しますか？')) return;
                
                $.post(ajaxurl, {action:'gi_seo_bulk_clear_redirect',nonce:nonce,ids:ids}, function(r){
                    alert(r.success ? r.data : 'エラー: ' + r.data);
                    if(r.success) load404(currentPage);
                });
            });

            // 選択したものを削除
            $('#btn-delete-selected').click(function(){
                var ids = [];
                $('.log-check:checked').each(function(){ ids.push($(this).val()); });
                if(ids.length === 0){ alert('対象を選択してください'); return; }
                
                if(!confirm(ids.length + '件を削除しますか？この操作は取り消せません。')) return;
                
                $.post(ajaxurl, {action:'gi_seo_delete_404_logs',nonce:nonce,ids:ids}, function(r){
                    alert(r.success ? r.data : 'エラー: ' + r.data);
                    if(r.success) load404(currentPage);
                });
            });

            // 全件自動リダイレクト
            $('#btn-auto-redirect-all').click(function(){
                if(!confirm('未設定の404ログに対して自動リダイレクトを設定しますか？')) return;
                
                var btn = $(this);
                btn.prop('disabled', true).text('処理中...');
                $('#auto-progress-container').show();
                
                $.post(ajaxurl, {
                    action: 'gi_seo_auto_redirect_all',
                    nonce: nonce,
                    min_score: $('#auto-min-score').val(),
                    limit: $('#auto-limit').val()
                }, function(r){
                    btn.prop('disabled', false).text('🤖 全件自動リダイレクト');
                    
                    if(r.success){
                        var msg = r.data.message;
                        if(r.data.results && r.data.results.length > 0) {
                            msg += '\n\n設定例:\n';
                            r.data.results.slice(0,5).forEach(function(res){
                                msg += '- ' + res.url.substring(0,30) + '... → ' + res.redirect_to + ' (スコア:' + res.score + ')\n';
                            });
                        }
                        alert(msg);
                        load404(currentPage);
                    } else {
                        alert('エラー: ' + r.data);
                    }
                    
                    $('#auto-progress-container').hide();
                });
            });

            $(document).on('click', '.btn-auto-single', function(){
                var btn = $(this);
                var id = btn.data('id');
                btn.prop('disabled', true).text('...');
                
                $.post(ajaxurl, {action:'gi_seo_auto_redirect_single',nonce:nonce,log_id:id}, function(r){
                    btn.prop('disabled', false).text('自動');
                    
                    if(r.success){
                        alert('リダイレクト設定完了\n\n→ ' + r.data.match.title + '\nスコア: ' + r.data.match.score);
                        load404(currentPage);
                    } else {
                        alert(r.data);
                    }
                });
            });

            $(document).on('click', '.btn-set-redirect', function(){
                var id = $(this).data('id');
                var val = $(this).siblings('.redirect-input').val().trim();
                if(!val){ alert('リダイレクト先を入力してください'); return; }

                var data = {action:'gi_seo_set_redirect',nonce:nonce,log_id:id};
                if(/^\d+$/.test(val)){
                    data.redirect_to = parseInt(val);
                } else {
                    data.redirect_url = val;
                }

                $.post(ajaxurl, data, function(r){
                    alert(r.success ? r.data : 'エラー: ' + r.data);
                    if(r.success) load404(currentPage);
                });
            });

            $(document).on('click', '.btn-clear-redirect', function(){
                if(!confirm('このリダイレクト設定を解除しますか？')) return;
                
                var id = $(this).data('id');
                $.post(ajaxurl, {action:'gi_seo_clear_redirect',nonce:nonce,log_id:id}, function(r){
                    alert(r.success ? r.data : 'エラー: ' + r.data);
                    if(r.success) load404(currentPage);
                });
            });

            $(document).on('click', '.btn-find-similar', function(){
                var url = decodeURIComponent($(this).data('url'));
                var title = decodeURIComponent($(this).data('title') || '');
                var row = $(this).closest('tr');
                
                $.post(ajaxurl, {action:'gi_seo_find_similar',nonce:nonce,url:url,title:title}, function(r){
                    if(r.success && r.data.length > 0){
                        var msg = '類似・推奨記事:\n\n';
                        r.data.forEach(function(p, i){
                            msg += (i+1) + '. [ID:'+p.ID+'] '+p.title+'\n';
                        });
                        msg += '\n記事IDを入力してください:';
                        var chosen = prompt(msg);
                        if(chosen && /^\d+$/.test(chosen)){
                            row.find('.redirect-input').val(chosen);
                        }
                    } else {
                        alert('類似記事が見つかりませんでした');
                    }
                });
            });

            $('#btn-clear-404').click(function(){
                if(!confirm('リダイレクト未設定の404ログをすべて削除しますか？')) return;
                $.post(ajaxurl, {action:'gi_seo_clear_404_logs',nonce:nonce}, function(r){
                    alert(r.success ? r.data : 'エラー: ' + r.data);
                    load404(1);
                });
            });
        });
        </script>
        <?php
    }

    public function render_reports_page() {
        $nonce = wp_create_nonce('gi_seo_nonce');
        ?>
        <style>
            .gi-wrap { font-family: -apple-system, BlinkMacSystemFont, sans-serif; }
            .gi-card { background: #fff; border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 4px; }
            .gi-card h3 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }
            .gi-btn { padding: 8px 16px; cursor: pointer; border: 1px solid #333; background: #fff; margin-right: 5px; border-radius: 3px; }
            .gi-btn:hover { background: #333; color: #fff; }
            .gi-btn-primary { background: #333; color: #fff; }
            .gi-table { width: 100%; border-collapse: collapse; }
            .gi-table th, .gi-table td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
            .gi-table th { background: #f5f5f5; }
            .gi-table tr:hover { background: #fafafa; }
        </style>

        <div class="wrap gi-wrap">
            <h1>📋 カルテ一覧</h1>

            <div class="gi-card">
                <div style="margin-bottom:15px;">
                    <button class="gi-btn" id="btn-export">📥 CSVエクスポート</button>
                </div>
                <table class="gi-table">
                    <thead>
                        <tr>
                            <th>タイトル</th>
                            <th width="100">シードKW</th>
                            <th width="120">文字数変化</th>
                            <th width="80">サジェスト</th>
                            <th width="100">日時</th>
                        </tr>
                    </thead>
                    <tbody id="reports-tbody">
                        <tr><td colspan="5" style="text-align:center;padding:30px;">読み込み中...</td></tr>
                    </tbody>
                </table>
                <div id="reports-pagination" style="margin-top:15px;"></div>
            </div>
        </div>

        <script>
        jQuery(function($){
            var nonce = '<?php echo $nonce; ?>';
            var currentPage = 1;

            function loadReports(page) {
                currentPage = page || 1;
                $.post(ajaxurl, {action:'gi_seo_get_report_list',nonce:nonce,page:currentPage,per_page:100}, function(r){
                    if(r.success){
                        if(r.data.reports.length === 0){
                            $('#reports-tbody').html('<tr><td colspan="5" style="text-align:center;">カルテなし</td></tr>');
                            return;
                        }
                        var html = '';
                        r.data.reports.forEach(function(rp){
                            var diff = rp.char_diff;
                            var diffStr = (diff>=0?'+':'')+diff;
                            var diffColor = diff >= 0 ? '#090' : '#c00';
                            html += '<tr>';
                            html += '<td><a href="'+rp.url+'" target="_blank">'+rp.title+'</a></td>';
                            html += '<td>'+rp.seed_keyword+'</td>';
                            html += '<td>'+rp.original_char_count+' → '+rp.new_char_count+' <span style="color:'+diffColor+'">('+diffStr+')</span></td>';
                            html += '<td>'+rp.suggests_count+'件</td>';
                            html += '<td>'+(rp.date?rp.date.substring(0,10):'-')+'</td>';
                            html += '</tr>';
                        });
                        $('#reports-tbody').html(html);

                        var pagHtml = '合計: ' + r.data.total + '件 | ';
                        for(var i=1; i<=Math.min(r.data.pages, 20); i++){
                            pagHtml += '<button class="gi-btn reports-page'+(i===currentPage?' gi-btn-primary':'')+'" data-page="'+i+'" style="padding:4px 10px;">'+i+'</button> ';
                        }
                        $('#reports-pagination').html(pagHtml);
                    }
                });
            }

            loadReports();

            $(document).on('click', '.reports-page', function(){ loadReports($(this).data('page')); });

            $('#btn-export').click(function(){
                $.post(ajaxurl, {action:'gi_seo_export_reports',nonce:nonce}, function(r){
                    if(r.success){
                        var csv = 'ID,タイトル,URL,日時,シードKW,キーフレーズ,元文字数,新文字数,差分\n';
                        r.data.data.forEach(function(row){
                            csv += row.post_id+',"'+row.title.replace(/"/g,'""')+'",'+row.url+','+row.date+',"'+row.seed_keyword+'","'+row.keyphrase+'",'+row.original_char_count+','+row.new_char_count+','+(row.new_char_count-row.original_char_count)+'\n';
                        });
                        var blob = new Blob([new Uint8Array([0xEF,0xBB,0xBF]), csv], {type:'text/csv'});
                        var a = document.createElement('a');
                        a.href = URL.createObjectURL(blob);
                        a.download = 'seo_reports_'+new Date().toISOString().slice(0,10)+'.csv';
                        a.click();
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function render_pv_stats_page() {
        $nonce = wp_create_nonce('gi_seo_nonce');
        ?>
        <style>
            .gi-wrap { font-family: -apple-system, BlinkMacSystemFont, sans-serif; }
            .gi-card { background: #fff; border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 4px; }
            .gi-card h3 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }
            .gi-btn { padding: 8px 16px; cursor: pointer; border: 1px solid #333; background: #fff; margin-right: 5px; border-radius: 3px; }
            .gi-btn:hover { background: #333; color: #fff; }
            .gi-btn-primary { background: #333; color: #fff; }
            .gi-table { width: 100%; border-collapse: collapse; }
            .gi-table th, .gi-table td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; font-size: 13px; }
            .gi-table th { background: #f5f5f5; }
            .gi-table tr:hover { background: #fafafa; }
            .gi-pv-up { color: #28a745; font-weight: bold; }
            .gi-pv-down { color: #dc3545; font-weight: bold; }
            .gi-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; }
            .gi-modal-content { background: #fff; width: 800px; max-width: 95%; margin: 50px auto; max-height: 80vh; overflow-y: auto; border-radius: 8px; }
            .gi-modal-header { padding: 15px 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
            .gi-modal-body { padding: 20px; }
            .gi-chart { height: 300px; background: #f5f5f5; margin: 20px 0; position: relative; border-radius: 4px; }
            .gi-chart-bar { position: absolute; bottom: 0; background: #333; min-width: 10px; border-radius: 2px 2px 0 0; }
        </style>

        <div class="wrap gi-wrap">
            <h1>📈 PV推移（リノベーション済み記事）</h1>

            <div class="gi-card">
                <h3>リノベーション統計</h3>
                <p style="color:#666;font-size:13px;">リノベーション実施前後のPV変化を確認できます。</p>
                
                <div style="margin-bottom:15px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <label style="font-weight:600;">並び替え:</label>
                    <select class="gi-select" id="pv-sort">
                        <option value="renovated_desc">リノベーション日（新しい順）</option>
                        <option value="renovated_asc">リノベーション日（古い順）</option>
                        <option value="pv_current_desc">現在のPV（多い順）</option>
                        <option value="pv_current_asc">現在のPV（少ない順）</option>
                        <option value="pv_change_desc">PV変化（増加順）</option>
                        <option value="pv_change_asc">PV変化（減少順）</option>
                    </select>
                    <button class="gi-btn" id="btn-pv-reload">🔄 更新</button>
                </div>
                
                <table class="gi-table">
                    <thead>
                        <tr>
                            <th>タイトル</th>
                            <th width="100" style="cursor:pointer" class="sortable" data-sort="renovated">実施日 ▼</th>
                            <th width="80">文字数変化</th>
                            <th width="80">PV(実施前)</th>
                            <th width="80" style="cursor:pointer" class="sortable" data-sort="pv_current">PV(現在) ▼</th>
                            <th width="80" style="cursor:pointer" class="sortable" data-sort="pv_change">変化 ▼</th>
                            <th width="80">操作</th>
                        </tr>
                    </thead>
                    <tbody id="pv-stats-tbody">
                        <tr><td colspan="7" style="text-align:center;padding:30px;">読み込み中...</td></tr>
                    </tbody>
                </table>
                <div id="pv-stats-pagination" style="margin-top:15px;"></div>
            </div>

            <div class="gi-modal" id="modal-pv-chart">
                <div class="gi-modal-content">
                    <div class="gi-modal-header">
                        <h3 style="margin:0;">📊 PV推移グラフ</h3>
                        <button class="gi-btn" onclick="jQuery('#modal-pv-chart').hide()">閉じる</button>
                    </div>
                    <div class="gi-modal-body">
                        <h4 id="chart-title">-</h4>
                        <div class="gi-chart" id="pv-chart"></div>
                        <p id="chart-info" style="font-size:12px;color:#666;"></p>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(function($){
            var nonce = '<?php echo $nonce; ?>';
            var currentPage = 1;
            var currentSort = 'renovated_desc';

            function loadStats(page, sort) {
                currentPage = page || 1;
                currentSort = sort || currentSort;
                $('#pv-stats-tbody').html('<tr><td colspan="7" style="text-align:center;padding:30px;">読み込み中...</td></tr>');
                
                $.post(ajaxurl, {action:'gi_seo_get_renovation_stats',nonce:nonce,page:currentPage,per_page:50,sort:currentSort}, function(r){
                    if(r.success){
                        if(r.data.stats.length === 0){
                            $('#pv-stats-tbody').html('<tr><td colspan="7" style="text-align:center;">データなし</td></tr>');
                            return;
                        }
                        var html = '';
                        r.data.stats.forEach(function(stat){
                            var charDiff = stat.new_char_count - stat.original_char_count;
                            var pvDiff = stat.pv_change;
                            var pvClass = pvDiff >= 0 ? 'gi-pv-up' : 'gi-pv-down';
                            var pvSign = pvDiff >= 0 ? '+' : '';
                            var url = stat.url || '/?p='+stat.post_id;
                            
                            html += '<tr>';
                            html += '<td><a href="'+url+'" target="_blank">'+stat.post_title+'</a></td>';
                            html += '<td>'+(stat.renovated_at ? stat.renovated_at.substring(0,10) : '-')+'</td>';
                            html += '<td>'+stat.original_char_count+' → '+stat.new_char_count+'</td>';
                            html += '<td>'+stat.pv_before+'</td>';
                            html += '<td>'+stat.current_pv+'</td>';
                            html += '<td class="'+pvClass+'">'+pvSign+pvDiff+'</td>';
                            html += '<td><button class="gi-btn btn-show-chart" data-id="'+stat.post_id+'" data-title="'+encodeURIComponent(stat.post_title)+'" style="padding:4px 8px;font-size:12px;">📊 グラフ</button></td>';
                            html += '</tr>';
                        });
                        $('#pv-stats-tbody').html(html);

                        var pagHtml = '合計: ' + r.data.total + '件 | ';
                        for(var i=1; i<=Math.min(r.data.pages, 20); i++){
                            pagHtml += '<button class="gi-btn pv-stats-page'+(i===currentPage?' gi-btn-primary':'')+'" data-page="'+i+'" style="padding:4px 10px;">'+i+'</button> ';
                        }
                        $('#pv-stats-pagination').html(pagHtml);
                    }
                });
            }

            loadStats();

            // ソート変更
            $('#pv-sort').change(function(){ loadStats(1, $(this).val()); });
            $('#btn-pv-reload').click(function(){ loadStats(currentPage, currentSort); });
            
            $(document).on('click', '.pv-stats-page', function(){ loadStats($(this).data('page'), currentSort); });

            $(document).on('click', '.btn-show-chart', function(){
                var id = $(this).data('id');
                var title = decodeURIComponent($(this).data('title'));
                
                $('#chart-title').text(title);
                $('#pv-chart').html('<p style="text-align:center;padding:50px;">読み込み中...</p>');
                $('#modal-pv-chart').show();
                
                $.post(ajaxurl, {action:'gi_seo_get_pv_history',nonce:nonce,post_id:id,days:30}, function(r){
                    if(r.success){
                        var history = r.data.history;
                        var renovationDate = r.data.renovation_date;
                        
                        if(history.length === 0 || history.every(function(h){ return h.pv === 0; })){
                            $('#pv-chart').html('<p style="text-align:center;padding:50px;color:#666;">PV履歴データがありません</p>');
                            $('#chart-info').text('現在のPV: ' + r.data.current_pv);
                            return;
                        }
                        
                        var maxPv = Math.max.apply(null, history.map(function(h){ return h.pv; }));
                        if(maxPv === 0) maxPv = 1;
                        
                        var chartHtml = '';
                        var barWidth = Math.floor(700 / history.length) - 2;
                        
                        history.forEach(function(h, i){
                            var height = Math.round((h.pv / maxPv) * 280);
                            var left = i * (barWidth + 2);
                            var isRenovationDay = h.date === renovationDate;
                            var bgColor = isRenovationDay ? '#dc3545' : '#333';
                            
                            chartHtml += '<div class="gi-chart-bar" style="left:'+left+'px;width:'+barWidth+'px;height:'+Math.max(height, 2)+'px;background:'+bgColor+'" title="'+h.date+': '+h.pv+'PV"></div>';
                        });
                        
                        $('#pv-chart').html(chartHtml);
                        
                        var info = '期間: ' + history[0].date + ' 〜 ' + history[history.length-1].date;
                        if(renovationDate) info += ' | リノベーション実施日: ' + renovationDate + '（赤色バー）';
                        info += ' | 現在のPV: ' + r.data.current_pv;
                        $('#chart-info').text(info);
                    } else {
                        $('#pv-chart').html('<p style="color:red;">エラー: '+r.data+'</p>');
                    }
                });
            });

            $('.gi-modal').click(function(e){ if(e.target===this) $(this).hide(); });
        });
        </script>
        <?php
    }

    public function render_failed_page() {
        $nonce = wp_create_nonce('gi_seo_nonce');
        ?>
        <style>
            .gi-wrap { font-family: -apple-system, BlinkMacSystemFont, sans-serif; }
            .gi-card { background: #fff; border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 4px; }
            .gi-card h3 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }
            .gi-btn { padding: 8px 16px; cursor: pointer; border: 1px solid #333; background: #fff; margin-right: 5px; border-radius: 3px; }
            .gi-btn:hover { background: #333; color: #fff; }
            .gi-table { width: 100%; border-collapse: collapse; }
            .gi-table th, .gi-table td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
            .gi-table th { background: #f5f5f5; }
            .gi-table tr:hover { background: #fafafa; }
        </style>

        <div class="wrap gi-wrap">
            <h1>❌ 失敗リスト</h1>

            <div class="gi-card">
                <div style="margin-bottom:15px;">
                    <button class="gi-btn" id="btn-load-failed">🔄 読み込み</button>
                    <button class="gi-btn" id="btn-retry-all">🔁 全てリトライ</button>
                    <button class="gi-btn" id="btn-clear-failed">🗑 クリア</button>
                </div>
                <table class="gi-table">
                    <thead>
                        <tr>
                            <th>タイトル</th>
                            <th width="300">エラー</th>
                            <th width="60">回数</th>
                            <th width="150">日時</th>
                            <th                            <th width="80">操作</th>
                        </tr>
                    </thead>
                    <tbody id="failed-tbody">
                        <tr><td colspan="5" style="text-align:center;padding:30px;">読み込み中...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        jQuery(function($){
            var nonce = '<?php echo $nonce; ?>';

            function loadFailed() {
                $.post(ajaxurl, {action:'gi_seo_get_failed',nonce:nonce}, function(r){
                    if(r.success){
                        if(r.data.length === 0){
                            $('#failed-tbody').html('<tr><td colspan="5" style="text-align:center;color:#090;">✓ 失敗なし</td></tr>');
                            return;
                        }
                        var html = '';
                        r.data.forEach(function(item){
                            html += '<tr>';
                            html += '<td>'+(item.post_title||'ID:'+item.post_id)+'</td>';
                            html += '<td style="font-size:12px;color:#c00;">'+item.error_message+'</td>';
                            html += '<td>'+item.attempts+'</td>';
                            html += '<td>'+item.failed_at+'</td>';
                            html += '<td><button class="gi-btn btn-retry" data-id="'+item.id+'" style="padding:4px 8px;font-size:12px;">リトライ</button></td>';
                            html += '</tr>';
                        });
                        $('#failed-tbody').html(html);
                    }
                });
            }

            $('#btn-load-failed').click(loadFailed);
            loadFailed();

            $(document).on('click', '.btn-retry', function(){
                var id = $(this).data('id');
                $.post(ajaxurl, {action:'gi_seo_retry_failed',nonce:nonce,failed_id:id}, function(r){
                    alert(r.success ? r.data : 'エラー: ' + r.data);
                    loadFailed();
                });
            });

            $('#btn-retry-all').click(function(){
                if(!confirm('全ての失敗記事をリトライキューに追加しますか？')) return;
                $('.btn-retry').each(function(i){
                    var btn = $(this);
                    setTimeout(function(){ btn.click(); }, i * 100);
                });
            });

            $('#btn-clear-failed').click(function(){
                if(!confirm('失敗リストをクリアしますか？')) return;
                $.post(ajaxurl, {action:'gi_seo_clear_failed',nonce:nonce}, function(r){
                    alert(r.success ? r.data : 'エラー: ' + r.data);
                    loadFailed();
                });
            });
        });
        </script>
        <?php
    }

    public function render_subsidy_page() {
        $nonce = wp_create_nonce('gi_seo_nonce');
        
        $prefectures = array(
            '', '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
            '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
            '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
            '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
            '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
            '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
            '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県', '全国'
        );
        ?>
        <style>
            .gi-wrap { font-family: -apple-system, BlinkMacSystemFont, sans-serif; }
            .gi-card { background: #fff; border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 4px; }
            .gi-card h3 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }
            .gi-stat { display: inline-block; padding: 15px 25px; background: #f5f5f5; margin-right: 15px; margin-bottom: 10px; border-radius: 4px; }
            .gi-stat-num { font-size: 28px; font-weight: bold; color: #333; }
            .gi-stat-label { font-size: 12px; color: #666; }
            .gi-btn { padding: 8px 16px; cursor: pointer; border: 1px solid #333; background: #fff; margin-right: 5px; margin-bottom: 5px; border-radius: 3px; }
            .gi-btn:hover { background: #333; color: #fff; }
            .gi-btn-primary { background: #333; color: #fff; }
            .gi-btn-success { background: #28a745; color: #fff; border-color: #28a745; }
            .gi-btn-sm { padding: 4px 8px; font-size: 12px; }
            .gi-btn-danger { border-color: #c00; color: #c00; }
            .gi-btn-danger:hover { background: #c00; color: #fff; }
            .gi-table { width: 100%; border-collapse: collapse; }
            .gi-table th, .gi-table td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; font-size: 13px; }
            .gi-table th { background: #f5f5f5; font-weight: 600; }
            .gi-table tr:hover { background: #fafafa; }
            .gi-input { padding: 8px 12px; border: 1px solid #ddd; width: 100%; box-sizing: border-box; border-radius: 3px; }
            .gi-select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 3px; }
            .gi-textarea { padding: 8px 12px; border: 1px solid #ddd; width: 100%; height: 80px; box-sizing: border-box; border-radius: 3px; }
            .gi-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; }
            .gi-modal-content { background: #fff; width: 700px; max-width: 95%; margin: 30px auto; max-height: 90vh; overflow-y: auto; border-radius: 8px; }
            .gi-modal-header { padding: 15px 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
            .gi-modal-body { padding: 20px; }
            .gi-form-row { margin-bottom: 15px; }
            .gi-form-label { display: block; margin-bottom: 5px; font-weight: 600; }
            .gi-form-row-inline { display: flex; gap: 15px; }
            .gi-form-row-inline > div { flex: 1; }
            .gi-status-active { color: #090; }
            .gi-status-expired { color: #999; }
            .gi-status-pending { color: #f90; }
            .gi-status-article_created { color: #009; }
            .gi-deadline-soon { background: #fff3cd; }
            .gi-deadline-expired { background: #f8d7da; color: #721c24; }
            .gi-matched { background: #d4edda; }
            .gi-progress { background: #eee; height: 20px; margin: 10px 0; border-radius: 10px; overflow: hidden; }
            .gi-progress-bar { background: #333; height: 100%; transition: width 0.3s; }
        </style>

        <div class="wrap gi-wrap">
            <h1>💰 補助金データベース</h1>

            <div class="gi-card">
                <h3>📊 統計</h3>
                <div class="gi-stat"><div class="gi-stat-num" id="subsidy-total">-</div><div class="gi-stat-label">総登録数</div></div>
                <div class="gi-stat"><div class="gi-stat-num" id="subsidy-active">-</div><div class="gi-stat-label">募集中</div></div>
                <div class="gi-stat"><div class="gi-stat-num" id="subsidy-matched">-</div><div class="gi-stat-label">投稿あり</div></div>
                <div class="gi-stat"><div class="gi-stat-num" id="subsidy-unmatched">-</div><div class="gi-stat-label">投稿なし</div></div>
                <div class="gi-stat"><div class="gi-stat-num" id="subsidy-expired">-</div><div class="gi-stat-label">終了</div></div>
            </div>

            <div class="gi-card">
                <h3>🔗 投稿照合</h3>
                <p style="color:#666;font-size:13px;">補助金DBの各タイトルから補助金名を抽出し、WP投稿のタイトルに含まれているかを照合します。</p>
                <div style="margin-bottom:15px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <label><input type="checkbox" id="skip-matched" checked> 既に登録済みのものを除外</label>
                    <button class="gi-btn gi-btn-success" id="btn-sync-posts">🔄 投稿と照合開始</button>
                    <button class="gi-btn" id="btn-stop-sync" style="display:none;">停止</button>
                    <button class="gi-btn" id="btn-debug-match" style="background:#f0f0f0;">🔍 デバッグ</button>
                </div>
                <div class="gi-progress" id="sync-progress-container" style="display:none;">
                    <div class="gi-progress-bar" id="sync-progress-bar" style="width:0%"></div>
                </div>
                <div id="sync-progress-text" style="font-size:12px;color:#666;"></div>
                <div id="debug-output" style="display:none; margin-top:15px; padding:15px; background:#f9f9f9; border:1px solid #ddd; border-radius:4px; max-height:400px; overflow:auto; font-size:12px;"></div>
                
                <div style="margin-top:15px; padding-top:15px; border-top:1px solid #eee;">
                    <strong>再スキャン:</strong>
                    <button class="gi-btn gi-btn-sm" id="btn-reset-all-matches" style="margin-left:10px;">🔄 全マッチをリセット</button>
                    <button class="gi-btn gi-btn-sm" id="btn-reset-selected-matches">🔄 選択をリセット</button>
                    <span style="color:#666;font-size:12px;margin-left:10px;">※リセット後に再照合すると、新しいロジックで再スキャンされます</span>
                </div>
            </div>

            <div class="gi-card">
                <h3>📋 補助金一覧</h3>
                <div style="margin-bottom:15px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <input type="text" class="gi-input" id="subsidy-search" placeholder="検索..." style="width:200px;display:inline-block;">
                    <select class="gi-select" id="subsidy-prefecture">
                        <option value="">全都道府県</option>
                        <?php foreach ($prefectures as $pref): if($pref): ?>
                        <option value="<?php echo esc_attr($pref); ?>"><?php echo esc_html($pref); ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                    <select class="gi-select" id="subsidy-status-filter">
                        <option value="">全ステータス</option>
                        <option value="active">募集中</option>
                        <option value="pending">確認待ち</option>
                        <option value="article_created">記事作成済</option>
                        <option value="expired">終了</option>
                    </select>
                    <select class="gi-select" id="subsidy-exists-filter">
                        <option value="">投稿状況（全て）</option>
                        <option value="exists">投稿あり</option>
                        <option value="not_exists">投稿なし（記事化対象）</option>
                    </select>
                    <select class="gi-select" id="subsidy-sort">
                        <option value="created_desc">登録日（新しい順）</option>
                        <option value="deadline_asc">締切日（近い順）</option>
                        <option value="title_asc">タイトル順</option>
                    </select>
                    <button class="gi-btn" id="btn-subsidy-search">🔍 検索</button>
                </div>
                <div style="margin-bottom:15px;">
                    <button class="gi-btn gi-btn-primary" id="btn-subsidy-add">➕ 新規追加</button>
                    <button class="gi-btn" id="btn-subsidy-import">📥 インポート</button>
                    <button class="gi-btn" id="btn-subsidy-export">📤 エクスポート</button>
                    <button class="gi-btn gi-btn-danger" id="btn-bulk-delete">🗑 選択削除</button>
                </div>
                <table class="gi-table" id="subsidy-table">
                    <thead>
                        <tr>
                            <th width="30"><input type="checkbox" id="check-all-subsidy"></th>
                            <th>タイトル</th>
                            <th width="80">都道府県</th>
                            <th width="90">締切日</th>
                            <th width="80">ステータス</th>
                            <th width="150">マッチ投稿</th>
                            <th width="100">操作</th>
                        </tr>
                    </thead>
                    <tbody id="subsidy-tbody">
                        <tr><td colspan="7" style="text-align:center;padding:30px;">読み込み中...</td></tr>
                    </tbody>
                </table>
                <div id="subsidy-pagination" style="margin-top:15px;"></div>
            </div>

            <!-- 補助金追加・編集モーダル -->
            <div class="gi-modal" id="modal-subsidy-form">
                <div class="gi-modal-content">
                    <div class="gi-modal-header">
                        <h3 style="margin:0;" id="modal-subsidy-title">補助金を追加</h3>
                        <button class="gi-btn" onclick="jQuery('#modal-subsidy-form').hide()">閉じる</button>
                    </div>
                    <div class="gi-modal-body">
                        <form id="subsidy-form">
                            <input type="hidden" id="subsidy-id" value="">
                            
                            <div class="gi-form-row">
                                <label class="gi-form-label">タイトル *</label>
                                <input type="text" class="gi-input" id="subsidy-title" required>
                            </div>
                            
                            <div class="gi-form-row">
                                <label class="gi-form-label">URL</label>
                                <input type="url" class="gi-input" id="subsidy-url" placeholder="https://...">
                            </div>
                            
                            <div class="gi-form-row-inline">
                                <div>
                                    <label class="gi-form-label">締切日</label>
                                    <input type="date" class="gi-input" id="subsidy-deadline">
                                </div>
                                <div>
                                    <label class="gi-form-label">都道府県</label>
                                    <select class="gi-select" id="subsidy-pref" style="width:100%;">
                                        <option value="">選択してください</option>
                                        <?php foreach ($prefectures as $pref): if($pref): ?>
                                        <option value="<?php echo esc_attr($pref); ?>"><?php echo esc_html($pref); ?></option>
                                        <?php endif; endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="gi-form-row-inline">
                                <div>
                                    <label class="gi-form-label">市区町村</label>
                                    <input type="text" class="gi-input" id="subsidy-city">
                                </div>
                                <div>
                                    <label class="gi-form-label">補助金額</label>
                                    <input type="text" class="gi-input" id="subsidy-amount" placeholder="例: 最大100万円">
                                </div>
                            </div>
                            
                            <div class="gi-form-row-inline">
                                <div>
                                    <label class="gi-form-label">ステータス</label>
                                    <select class="gi-select" id="subsidy-status" style="width:100%;">
                                        <option value="active">募集中</option>
                                        <option value="pending">確認待ち</option>
                                        <option value="article_created">記事作成済</option>
                                        <option value="expired">終了</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="gi-form-label">データソース</label>
                                    <input type="text" class="gi-input" id="subsidy-source" placeholder="例: J-Net21">
                                </div>
                            </div>
                            
                            <div class="gi-form-row">
                                <label class="gi-form-label">メモ</label>
                                <textarea class="gi-textarea" id="subsidy-notes"></textarea>
                            </div>
                            
                            <div style="text-align:right;margin-top:20px;">
                                <button type="button" class="gi-btn" onclick="jQuery('#modal-subsidy-form').hide()">キャンセル</button>
                                <button type="submit" class="gi-btn gi-btn-primary">💾 保存</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- インポートモーダル -->
            <div class="gi-modal" id="modal-subsidy-import">
                <div class="gi-modal-content">
                    <div class="gi-modal-header">
                        <h3 style="margin:0;">📥 インポート</h3>
                        <button class="gi-btn" onclick="jQuery('#modal-subsidy-import').hide()">閉じる</button>
                    </div>
                    <div class="gi-modal-body">
                        <p>ExcelまたはCSV/TSVファイルからインポートできます。以下の列順でデータを準備してください：</p>
                        <ol style="font-size:13px;color:#666;">
                            <li>タイトル（必須）</li>
                            <li>URL</li>
                            <li>締切日（YYYY-MM-DD形式）</li>
                            <li>都道府県</li>
                            <li>市区町村</li>
                            <li>補助金額</li>
                            <li>ステータス（active/pending/article_created/expired）</li>
                            <li>データソース</li>
                            <li>メモ</li>
                        </ol>
                        <p style="font-size:12px;color:#666;">※1行目はヘッダーとしてスキップされます。Excelからコピペ可能（タブ区切り対応）</p>
                        
                        <div class="gi-form-row">
                            <label class="gi-form-label">データを貼り付け</label>
                            <textarea class="gi-textarea" id="import-csv-data" style="height:200px;" placeholder="Excelからコピーしたデータを貼り付けてください..."></textarea>
                        </div>
                        
                        <div style="text-align:right;margin-top:20px;">
                            <button type="button" class="gi-btn" onclick="jQuery('#modal-subsidy-import').hide()">キャンセル</button>
                            <button type="button" class="gi-btn gi-btn-primary" id="btn-execute-import">📥 インポート実行</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(function($){
            var nonce = '<?php echo $nonce; ?>';
            var currentPage = 1;
            var syncing = false;

            function loadStats() {
                $.post(ajaxurl, {action:'gi_subsidy_get_stats',nonce:nonce}, function(r){
                    if(r.success){
                        $('#subsidy-total').text(r.data.total);
                        $('#subsidy-active').text(r.data.active);
                        $('#subsidy-matched').text(r.data.matched);
                        $('#subsidy-unmatched').text(r.data.unmatched);
                        $('#subsidy-expired').text(r.data.expired);
                    }
                });
            }

            function loadSubsidies(page) {
                currentPage = page || 1;
                $('#subsidy-tbody').html('<tr><td colspan="7" style="text-align:center;">読み込み中...</td></tr>');
                
                $.post(ajaxurl, {
                    action: 'gi_subsidy_get_list',
                    nonce: nonce,
                    page: currentPage,
                    per_page: 100,
                    search: $('#subsidy-search').val(),
                    prefecture: $('#subsidy-prefecture').val(),
                    status: $('#subsidy-status-filter').val(),
                    exists_filter: $('#subsidy-exists-filter').val(),
                    sort: $('#subsidy-sort').val()
                }, function(r){
                    if(r.success){
                        if(r.data.items.length === 0){
                            $('#subsidy-tbody').html('<tr><td colspan="7" style="text-align:center;padding:30px;">データなし</td></tr>');
                            return;
                        }
                        
                        var html = '';
                        var today = new Date().toISOString().slice(0,10);
                        var soon = new Date(Date.now() + 14*24*60*60*1000).toISOString().slice(0,10);
                        
                        r.data.items.forEach(function(item){
                            var rowClass = '';
                            if(item.matched_post_id) rowClass = 'gi-matched';
                            else if(item.deadline && item.deadline < today) rowClass = 'gi-deadline-expired';
                            else if(item.deadline && item.deadline <= soon) rowClass = 'gi-deadline-soon';
                            
                            var statusClass = 'gi-status-' + item.status;
                            var statusText = {
                                'active': '募集中',
                                'pending': '確認待ち',
                                'article_created': '記事作成済',
                                'expired': '終了'
                            }[item.status] || item.status;
                            
                            html += '<tr data-id="'+item.id+'" class="'+rowClass+'">';
                            html += '<td><input type="checkbox" class="subsidy-check" value="'+item.id+'"></td>';
                            html += '<td>';
                            if(item.url) html += '<a href="'+item.url+'" target="_blank">'+item.title+'</a>';
                            else html += item.title;
                            html += '</td>';
                            html += '<td>'+item.prefecture+'</td>';
                            html += '<td>'+(item.deadline || '-')+'</td>';
                            html += '<td class="'+statusClass+'">'+statusText+'</td>';
                            html += '<td>';
                            if(item.matched_post_id) {
                                html += '<a href="/?p='+item.matched_post_id+'" target="_blank" style="color:#090;">✓ '+item.matched_post_title+'</a>';
                                html += ' <small>('+item.match_type+')</small>';
                            } else {
                                html += '<button class="gi-btn gi-btn-sm btn-check-exists" data-id="'+item.id+'">照合</button>';
                            }
                            html += '</td>';
                            html += '<td>';
                            html += '<button class="gi-btn gi-btn-sm btn-edit-subsidy" data-id="'+item.id+'">編集</button> ';
                            html += '<button class="gi-btn gi-btn-sm gi-btn-danger btn-delete-subsidy" data-id="'+item.id+'">削除</button>';
                            html += '</td></tr>';
                        });
                        $('#subsidy-tbody').html(html);
                        
                        var pagHtml = '合計: ' + r.data.total + '件 | ';
                        for(var i=1; i<=Math.min(r.data.pages, 20); i++){
                            pagHtml += '<button class="gi-btn gi-btn-sm subsidy-page'+(i===currentPage?' gi-btn-primary':'')+'" data-page="'+i+'">'+i+'</button> ';
                        }
                        $('#subsidy-pagination').html(pagHtml);
                    }
                });
            }

            loadStats();
            loadSubsidies();

            $('#btn-subsidy-search').click(function(){ loadSubsidies(1); });
            $('#subsidy-search').keyup(function(e){ if(e.key==='Enter') loadSubsidies(1); });
            $('#subsidy-prefecture, #subsidy-status-filter, #subsidy-exists-filter, #subsidy-sort').change(function(){ loadSubsidies(1); });
            $(document).on('click', '.subsidy-page', function(){ loadSubsidies($(this).data('page')); });

            $('#check-all-subsidy').change(function(){ $('.subsidy-check').prop('checked', this.checked); });

            $('#btn-sync-posts').click(function(){
                if(syncing) return;
                syncing = true;
                $(this).prop('disabled', true);
                $('#btn-stop-sync').show();
                $('#sync-progress-container').show();
                $('#sync-progress-text').text('初期化中...');
                
                var skipMatched = $('#skip-matched').is(':checked');
                var batchSize = 50;
                var totalItems = 0;
                var offset = 0, totalMatched = 0, totalProcessed = 0;
                
                // まず総件数を取得
                $.post(ajaxurl, {
                    action: 'gi_subsidy_sync_posts',
                    nonce: nonce,
                    init: 'true',
                    skip_matched: skipMatched ? 'true' : 'false'
                }, function(initR){
                    if(initR.success && initR.data.init){
                        totalItems = initR.data.total;
                        $('#sync-progress-text').text('対象: ' + totalItems + '件の補助金データをスキャンします');
                        
                        if(totalItems === 0){
                            finish();
                            return;
                        }
                        
                        setTimeout(batch, 300);
                    } else {
                        alert('初期化エラー');
                        finish();
                    }
                }).fail(function(){
                    alert('通信エラー');
                    finish();
                });
                
                function batch() {
                    if(!syncing) { finish(); return; }
                    
                    $.post(ajaxurl, {
                        action: 'gi_subsidy_sync_posts',
                        nonce: nonce,
                        limit: batchSize,
                        offset: offset,
                        skip_matched: skipMatched ? 'true' : 'false'
                    }, function(r){
                        if(r.success){
                            totalProcessed += r.data.processed;
                            totalMatched += r.data.matched;
                            
                            // 正確なプログレス計算（補助金DB件数ベース）
                            var progress = totalItems > 0 ? Math.min((totalProcessed / totalItems) * 100, 100) : 100;
                            
                            $('#sync-progress-bar').css('width', progress.toFixed(1) + '%');
                            $('#sync-progress-text').html(
                                '<strong>補助金DB照合中</strong>: ' + totalProcessed + ' / ' + totalItems + '件' +
                                ' | マッチ: <span style="color:#090;font-weight:bold;">' + totalMatched + '件</span>' +
                                ' | 残り: ' + r.data.remaining + '件'
                            );
                            
                            // 統計更新（10バッチごと）
                            if(totalProcessed % (batchSize * 10) === 0) {
                                loadStats();
                            }
                            
                            if(r.data.has_more && syncing && r.data.remaining > 0){
                                offset = r.data.next_offset;
                                setTimeout(batch, 100);
                            } else {
                                finish();
                            }
                        } else {
                            console.error('Sync error:', r);
                            // エラー時はリトライ
                            setTimeout(batch, 1000);
                        }
                    }).fail(function(xhr){
                        console.error('Ajax error:', xhr);
                        // 通信エラー時もリトライ
                        setTimeout(batch, 2000);
                    });
                }
                
                function finish() {
                    syncing = false;
                    $('#btn-sync-posts').prop('disabled', false);
                    $('#btn-stop-sync').hide();
                    $('#sync-progress-bar').css('width', '100%');
                    $('#sync-progress-text').html(
                        '<strong style="color:#090;">✓ 完了</strong>: ' + totalProcessed + '件処理、' +
                        '<strong>' + totalMatched + '件マッチ</strong>'
                    );
                    loadStats();
                    loadSubsidies(currentPage);
                }
            });

            $('#btn-stop-sync').click(function(){
                syncing = false;
                $(this).hide();
            });
            
            // デバッグボタン
            $('#btn-debug-match').click(function(){
                var btn = $(this);
                btn.prop('disabled', true).text('確認中...');
                $('#debug-output').show().html('デバッグ情報を取得中...');
                
                $.post(ajaxurl, {action:'gi_subsidy_debug_match', nonce:nonce}, function(r){
                    btn.prop('disabled', false).text('🔍 デバッグ');
                    
                    if(r.success){
                        var html = '<h4>📊 データ概要</h4>';
                        html += '<p>補助金DB総数: <strong>' + r.data.total_subsidies + '</strong>件 / 未マッチ: <strong>' + r.data.unmatched_subsidies + '</strong>件</p>';
                        
                        html += '<h4>🔍 WP投稿（補助金・助成金を含む）- サンプル</h4>';
                        if(r.data.wp_posts_with_subsidy.length > 0){
                            html += '<ul style="max-height:150px;overflow:auto;">';
                            r.data.wp_posts_with_subsidy.forEach(function(p){
                                html += '<li><a href="/?p=' + p.ID + '" target="_blank">' + p.post_title + '</a> (ID:' + p.ID + ')</li>';
                            });
                            html += '</ul>';
                        } else {
                            html += '<p style="color:red;">⚠️ WP投稿に「補助金」「助成金」を含む記事が見つかりません！</p>';
                        }
                        
                        html += '<h4>🧪 マッチングテスト（未マッチ補助金5件）</h4>';
                        r.data.debug_results.forEach(function(d){
                            html += '<div style="margin-bottom:15px;padding:10px;background:#fff;border:1px solid #ddd;">';
                            html += '<p><strong>補助金タイトル:</strong> ' + d.subsidy_title + '</p>';
                            html += '<p><strong>都道府県:</strong> ' + (d.prefecture || '(なし)') + '</p>';
                            html += '<p><strong>抽出された補助金名:</strong> ';
                            if(d.extracted_names.length > 0){
                                html += '<span style="color:green;">' + d.extracted_names.join(', ') + '</span>';
                            } else {
                                html += '<span style="color:red;">抽出できませんでした</span>';
                            }
                            html += '</p>';
                            html += '<p><strong>マッチした投稿:</strong> ';
                            if(d.found_posts.length > 0){
                                html += '<ul>';
                                d.found_posts.forEach(function(fp){
                                    html += '<li><a href="/?p=' + fp.id + '" target="_blank">' + fp.title + '</a> (マッチ: ' + fp.matched_by + ')</li>';
                                });
                                html += '</ul>';
                            } else {
                                html += '<span style="color:orange;">なし</span>';
                            }
                            html += '</p></div>';
                        });
                        
                        $('#debug-output').html(html);
                    } else {
                        $('#debug-output').html('<p style="color:red;">エラー: ' + r.data + '</p>');
                    }
                }).fail(function(){
                    btn.prop('disabled', false).text('🔍 デバッグ');
                    $('#debug-output').html('<p style="color:red;">通信エラー</p>');
                });
            });

            $(document).on('click', '.btn-check-exists', function(){
                var btn = $(this);
                var id = btn.data('id');
                btn.prop('disabled', true).text('照合中...');
                
                $.post(ajaxurl, {action:'gi_subsidy_check_exists',nonce:nonce,subsidy_id:id}, function(r){
                    btn.prop('disabled', false).text('照合');
                    
                    if(r.success){
                        if(r.data.found){
                            alert('マッチ発見！\n\n→ ' + r.data.post_title + '\nスコア: ' + r.data.score);
                            loadSubsidies(currentPage);
                            loadStats();
                        } else {
                            alert('マッチする投稿が見つかりませんでした');
                        }
                    } else {
                        alert('エラー: ' + r.data);
                    }
                });
            });
            
            // 全マッチリセット
            $('#btn-reset-all-matches').click(function(){
                if(!confirm('全ての補助金のマッチ情報をリセットしますか？\n再照合で新しいロジックで再スキャンできます。')) return;
                
                var btn = $(this);
                btn.prop('disabled', true).text('リセット中...');
                
                $.post(ajaxurl, {action:'gi_subsidy_reset_matches', nonce:nonce, mode:'all'}, function(r){
                    btn.prop('disabled', false).text('🔄 全マッチをリセット');
                    if(r.success){
                        alert(r.data.message);
                        loadStats();
                        loadSubsidies(currentPage);
                    } else {
                        alert('エラー: ' + r.data);
                    }
                });
            });
            
            // 選択リセット
            $('#btn-reset-selected-matches').click(function(){
                var ids = [];
                $('.subsidy-check:checked').each(function(){ ids.push($(this).val()); });
                
                if(ids.length === 0){
                    alert('リセット対象を選択してください');
                    return;
                }
                
                if(!confirm(ids.length + '件のマッチ情報をリセットしますか？')) return;
                
                var btn = $(this);
                btn.prop('disabled', true).text('リセット中...');
                
                $.post(ajaxurl, {action:'gi_subsidy_reset_matches', nonce:nonce, mode:'selected', ids:ids}, function(r){
                    btn.prop('disabled', false).text('🔄 選択をリセット');
                    if(r.success){
                        alert(r.data.message);
                        loadStats();
                        loadSubsidies(currentPage);
                    } else {
                        alert('エラー: ' + r.data);
                    }
                });
            });

            $('#btn-subsidy-add').click(function(){
                $('#modal-subsidy-title').text('補助金を追加');
                $('#subsidy-id').val('');
                $('#subsidy-form')[0].reset();
                $('#modal-subsidy-form').show();
            });

            $(document).on('click', '.btn-edit-subsidy', function(){
                var id = $(this).data('id');
                
                $.post(ajaxurl, {action:'gi_subsidy_get_list',nonce:nonce,page:1,per_page:1000}, function(r){
                    if(r.success){
                        var item = r.data.items.find(function(i){ return i.id == id; });
                        if(item){
                            $('#modal-subsidy-title').text('補助金を編集');
                            $('#subsidy-id').val(id);
                            $('#subsidy-title').val(item.title);
                            $('#subsidy-url').val(item.url);
                            $('#subsidy-deadline').val(item.deadline);
                            $('#subsidy-pref').val(item.prefecture);
                            $('#subsidy-city').val(item.city);
                            $('#subsidy-amount').val(item.subsidy_amount);
                            $('#subsidy-status').val(item.status);
                            $('#subsidy-source').val(item.data_source);
                            $('#subsidy-notes').val(item.notes);
                            $('#modal-subsidy-form').show();
                        }
                    }
                });
            });

            $('#subsidy-form').submit(function(e){
                e.preventDefault();
                
                var id = $('#subsidy-id').val();
                var action = id ? 'gi_subsidy_update' : 'gi_subsidy_add';
                
                var data = {
                    action: action,
                    nonce: nonce,
                    title: $('#subsidy-title').val(),
                    url: $('#subsidy-url').val(),
                    deadline: $('#subsidy-deadline').val(),
                    prefecture: $('#subsidy-pref').val(),
                    city: $('#subsidy-city').val(),
                    subsidy_amount: $('#subsidy-amount').val(),
                    status: $('#subsidy-status').val(),
                    data_source: $('#subsidy-source').val(),
                    notes: $('#subsidy-notes').val()
                };
                
                if(id) data.id = id;
                
                $.post(ajaxurl, data, function(r){
                    if(r.success){
                        alert(id ? '更新しました' : '追加しました');
                        $('#modal-subsidy-form').hide();
                        loadSubsidies(currentPage);
                        loadStats();
                    } else {
                        alert('エラー: ' + r.data);
                    }
                });
            });

            $(document).on('click', '.btn-delete-subsidy', function(){
                if(!confirm('削除しますか？')) return;
                
                var id = $(this).data('id');
                $.post(ajaxurl, {action:'gi_subsidy_delete',nonce:nonce,id:id}, function(r){
                    if(r.success){
                        loadSubsidies(currentPage);
                        loadStats();
                    } else {
                        alert('エラー: ' + r.data);
                    }
                });
            });

            $('#btn-bulk-delete').click(function(){
                var ids = [];
                $('.subsidy-check:checked').each(function(){ ids.push($(this).val()); });
                
                if(ids.length === 0){
                    alert('削除対象を選択してください');
                    return;
                }
                
                if(!confirm(ids.length + '件を削除しますか？')) return;
                
                $.post(ajaxurl, {action:'gi_subsidy_bulk_delete',nonce:nonce,ids:ids}, function(r){
                    alert(r.success ? r.data : 'エラー: ' + r.data);
                    loadSubsidies(currentPage);
                    loadStats();
                });
            });

            $('#btn-subsidy-import').click(function(){
                $('#import-csv-data').val('');
                $('#modal-subsidy-import').show();
            });

            $('#btn-execute-import').click(function(){
                var csvData = $('#import-csv-data').val().trim();
                if(!csvData){
                    alert('データを貼り付けてください');
                    return;
                }
                
                var btn = $(this);
                btn.prop('disabled', true).text('インポート中...');
                
                $.post(ajaxurl, {action:'gi_subsidy_import',nonce:nonce,csv_data:csvData}, function(r){
                    btn.prop('disabled', false).text('📥 インポート実行');
                    
                    if(r.success){
                        var msg = r.data.message;
                        if(r.data.errors && r.data.errors.length > 0){
                            msg += '\n\nエラー:\n' + r.data.errors.slice(0,10).join('\n');
                        }
                        alert(msg);
                        $('#modal-subsidy-import').hide();
                        loadSubsidies(1);
                        loadStats();
                    } else {
                        alert('エラー: ' + r.data);
                    }
                });
            });

            $('#btn-subsidy-export').click(function(){
                // 現在のフィルター条件を取得
                var exportData = {
                    action: 'gi_subsidy_export',
                    nonce: nonce,
                    search: $('#subsidy-search').val(),
                    prefecture: $('#subsidy-prefecture').val(),
                    status: $('#subsidy-status-filter').val(),
                    exists_filter: $('#subsidy-exists-filter').val()
                };
                
                var btn = $(this);
                btn.prop('disabled', true).text('エクスポート中...');
                
                $.post(ajaxurl, exportData, function(r){
                    btn.prop('disabled', false).text('📤 エクスポート');
                    
                    if(r.success){
                        var tsv = '';
                        r.data.data.forEach(function(row){
                            tsv += row.map(function(cell){
                                return (cell || '').toString().replace(/\t/g, ' ').replace(/\n/g, ' ');
                            }).join('\t') + '\n';
                        });
                        
                        var blob = new Blob([new Uint8Array([0xEF,0xBB,0xBF]), tsv], {type:'text/tab-separated-values'});
                        var a = document.createElement('a');
                        a.href = URL.createObjectURL(blob);
                        
                        // ファイル名にフィルター情報を含める
                        var filename = 'subsidy_db_' + new Date().toISOString().slice(0,10);
                        if($('#subsidy-exists-filter').val() === 'not_exists') filename += '_記事なし';
                        if($('#subsidy-exists-filter').val() === 'exists') filename += '_記事あり';
                        if($('#subsidy-status-filter').val()) filename += '_' + $('#subsidy-status-filter').val();
                        if($('#subsidy-prefecture').val()) filename += '_' + $('#subsidy-prefecture').val();
                        filename += '.tsv';
                        
                        a.download = filename;
                        a.click();
                        
                        alert('エクスポート完了\n\n' + r.data.count + '件\nフィルター: ' + r.data.filter_info);
                    } else {
                        alert('エラー: ' + r.data);
                    }
                }).fail(function(){
                    btn.prop('disabled', false).text('📤 エクスポート');
                    alert('通信エラーが発生しました');
                });
            });

            $('.gi-modal').click(function(e){
                if(e.target === this) $(this).hide();
            });
        });
        </script>
        <?php
    }

    public function render_settings_page() {
        $nonce = wp_create_nonce('gi_seo_nonce');
        $settings = get_option('gi_seo_settings', array());
        $post_types = get_post_types(array('public' => true), 'objects');
        $cron_key = get_option('gi_seo_cron_key', '');
        $external_cron_url = admin_url('admin-ajax.php') . '?action=gi_seo_external_cron&key=' . $cron_key;
        ?>
        <style>
            .gi-wrap { font-family: -apple-system, BlinkMacSystemFont, sans-serif; max-width: 800px; }
            .gi-card { background: #fff; border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 4px; }
            .gi-card h3 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; }
            .gi-btn { padding: 10px 20px; cursor: pointer; border: 1px solid #333; background: #fff; border-radius: 3px; }
            .gi-btn:hover { background: #333; color: #fff; }
            .gi-btn-primary { background: #333; color: #fff; }
            .gi-input { padding: 10px; border: 1px solid #ddd; width: 100%; max-width: 400px; border-radius: 3px; }
            .gi-select { padding: 10px; border: 1px solid #ddd; width: 100%; max-width: 400px; border-radius: 3px; }
            .gi-textarea { padding: 10px; border: 1px solid #ddd; width: 100%; height: 300px; font-family: monospace; font-size: 12px; border-radius: 3px; }
            .gi-form-row { margin-bottom: 20px; }
            .gi-form-label { display: block; margin-bottom: 5px; font-weight: 600; }
            .gi-form-desc { font-size: 12px; color: #666; margin-top: 5px; }
            .gi-cron-box { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-top: 20px; border-radius: 4px; }
            .gi-cron-url { background: #fff; padding: 10px; border: 1px solid #ccc; word-break: break-all; margin-top: 10px; font-family: monospace; font-size: 12px; border-radius: 3px; }
            .gi-info-table { width: 100%; }
            .gi-info-table td { padding: 8px 0; border-bottom: 1px solid #eee; }
            .gi-info-table td:first-child { font-weight: 600; width: 150px; }
        </style>

        <div class="wrap gi-wrap">
            <h1>⚙️ 設定</h1>

            <form id="settings-form">
                <div class="gi-card">
                    <h3>🔑 API設定</h3>
                    
                    <div class="gi-form-row">
                        <label class="gi-form-label">Gemini APIキー</label>
                        <input type="password" class="gi-input" name="api_key" value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>">
                        <div class="gi-form-desc"><a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>でAPIキーを取得</div>
                    </div>
                    
                    <div class="gi-form-row">
                        <label class="gi-form-label">モデル</label>
                        <select class="gi-select" name="model">
                            <?php
                            $models = array(
                                'gemini-3-pro-preview' => 'Gemini 3 Pro Preview（推奨）',
                                'gemini-2.5-flash' => 'Gemini 2.5 Flash',
                                'gemini-2.5-pro' => 'Gemini 2.5 Pro',
                                'gemini-2.0-flash' => 'Gemini 2.0 Flash',
                                'gemini-1.5-pro' => 'Gemini 1.5 Pro',
                                'gemini-1.5-flash' => 'Gemini 1.5 Flash'
                            );
                            $current = $settings['model'] ?? 'gemini-3-pro-preview';
                            foreach ($models as $v => $l) {
                                echo '<option value="'.esc_attr($v).'"'.($v===$current?' selected':'').'>'.esc_html($l).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="gi-form-row">
                        <label class="gi-form-label">Temperature</label>
                        <input type="number" class="gi-input" name="temperature" value="<?php echo esc_attr($settings['temperature'] ?? 0.7); ?>" min="0" max="2" step="0.1" style="width:100px;">
                        <div class="gi-form-desc">0〜2（推奨: 0.7）</div>
                    </div>
                </div>

                <div class="gi-card">
                    <h3>📝 処理設定</h3>
                    
                    <div class="gi-form-row">
                        <label class="gi-form-label">対象投稿タイプ</label>
                        <?php
                        $selected = $settings['post_types'] ?? array('post');
                        foreach ($post_types as $type) {
                            if (in_array($type->name, array('attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation'))) continue;
                            $checked = in_array($type->name, (array)$selected) ? 'checked' : '';
                            $count = wp_count_posts($type->name);
                            echo '<label style="display:block;margin-bottom:5px;"><input type="checkbox" name="post_types[]" value="'.esc_attr($type->name).'" '.$checked.'> '.esc_html($type->label).' ('.($count->publish ?? 0).'件)</label>';
                        }
                        ?>
                    </div>
                    
                    <div class="gi-form-row">
                        <label class="gi-form-label">処理間隔（秒）</label>
                        <input type="number" class="gi-input" name="processing_interval" value="<?php echo esc_attr($settings['processing_interval'] ?? 30); ?>" min="10" max="300" style="width:100px;">
                    </div>
                </div>

                <div class="gi-card">
                    <h3>📄 AIリノベーション・カスタムプロンプト</h3>
                    <div class="gi-form-desc" style="margin-bottom:15px; padding:15px; background:#f5f5f5; border-radius:4px;">
                        <p style="margin:0 0 10px 0;"><strong>空欄の場合はデフォルトプロンプトが使用されます。</strong></p>
                        <p style="margin:0 0 10px 0;">使用可能な変数（プロンプト内で自動的に置換されます）:</p>
                        <ul style="margin:0;padding-left:20px;font-size:12px;">
                            <li><code>{title}</code> - 記事タイトル</li>
                            <li><code>{content}</code> - 記事本文（HTML）</li>
                            <li><code>{seed_keyword}</code> - 抽出されたシードキーワード</li>
                            <li><code>{keyphrase}</code> - 抽出されたキーフレーズ</li>
                            <li><code>{keywords}</code> - 重要キーワードリスト（カンマ区切り）</li>
                            <li><code>{suggests}</code> - Googleサジェストキーワード（カンマ区切り）</li>
                            <li><code>{related_posts}</code> - 関連記事リスト</li>
                        </ul>
                    </div>
                    <div style="margin-bottom:10px;">
                        <button type="button" class="gi-btn" id="btn-load-default-prompt">📋 デフォルトプロンプトを読み込む</button>
                        <button type="button" class="gi-btn" id="btn-clear-prompt">🗑 クリア</button>
                    </div>
                    <textarea class="gi-textarea" name="custom_prompt" style="height:400px;font-size:12px;line-height:1.5;"><?php echo esc_textarea($settings['custom_prompt'] ?? ''); ?></textarea>
                    <div class="gi-form-desc" style="margin-top:10px;">
                        ヒント: プロンプトの最初に「【絶対厳守ルール】」を入れると、AIの出力フォーマットを制御しやすくなります。
                    </div>
                </div>

                <button type="submit" class="gi-btn gi-btn-primary">💾 設定を保存</button>
            </form>

            <div class="gi-card" style="margin-top:30px;">
                <h3>🔄 バックグラウンド処理設定</h3>
                <p>ブラウザを閉じても処理を継続させるには、サーバーのcrontabに以下を追加してください：</p>
                <div class="gi-cron-box">
                    <strong>毎分実行（推奨）:</strong>
                    <div class="gi-cron-url">*/1 * * * * curl -s "<?php echo esc_html($external_cron_url); ?>" > /dev/null 2>&1</div>
                    
                    <strong style="display:block;margin-top:15px;">または wget を使用:</strong>
                    <div class="gi-cron-url">*/1 * * * * wget -q -O /dev/null "<?php echo esc_html($external_cron_url); ?>"</div>
                </div>
                <p style="margin-top:15px;font-size:12px;color:#666;">
                    ※ 外部Cronを設定しなくても、WordPressのWP-Cronで処理は実行されますが、サイトへのアクセスがない場合は処理が遅延する可能性があります。
                </p>
            </div>

            <div class="gi-card" style="margin-top:30px;">
                <h3>ℹ️ システム情報</h3>
                <table class="gi-info-table">
                    <tr><td>バージョン</td><td><?php echo $this->version; ?></td></tr>
                    <tr><td>WordPress</td><td><?php echo get_bloginfo('version'); ?></td></tr>
                    <tr><td>PHP</td><td><?php echo PHP_VERSION; ?></td></tr>
                    <tr><td>モデル</td><td><?php echo $this->get_current_model(); ?></td></tr>
                    <tr><td>キュー状態</td><td><?php echo get_option('gi_seo_queue_running', false) ? '🟢 実行中' : '⚪ 停止中'; ?></td></tr>
                    <tr><td>最終Cron実行</td><td><?php 
                        $last_run = get_option('gi_seo_last_cron_run', 0);
                        echo $last_run ? date('Y-m-d H:i:s', $last_run) . ' (' . human_time_diff($last_run) . '前)' : '未実行';
                    ?></td></tr>
                </table>
            </div>
        </div>

        <script>
        jQuery(function($){
            var nonce = '<?php echo $nonce; ?>';
            
            // デフォルトプロンプト
            var defaultPrompt = `以下の記事をリノベーションしてください。

【絶対厳守ルール】
- 前置き・挨拶・説明文は一切不要
- 「承知しました」「SEOの専門家として」などの文言は絶対に含めない
- HTMLコードのみを出力する
- 出力の最初の文字は必ず「<」で始める
- 内部リンクは地域やテーマが一致する場合のみ設置する

## 対象記事
タイトル: {title}

本文:
{content}

## キーワード
- シード: {seed_keyword}
- キーフレーズ: {keyphrase}
- 重要語: {keywords}

## Googleサジェスト（必ず網羅）
{suggests}

## 内部リンク設置（地域・キーワードが一致する記事のみ）
{related_posts}

## デザインルール
シンプルな白黒ベースのHTMLで出力。以下のスタイルを使用：

**ポイントボックス**
<div style="background:#f5f5f5; border:1px solid #333; padding:20px; margin:20px 0;">
<h4 style="margin:0 0 10px 0; color:#333;">■ ポイント</h4>
<p style="margin:0;">内容</p>
</div>

**注意ボックス**
<div style="background:#fff; border-left:4px solid #333; padding:15px; margin:20px 0;">
<strong>注意：</strong>内容
</div>

**テーブル**
<table style="width:100%; border-collapse:collapse; margin:20px 0;">
<tr style="background:#333; color:#fff;"><th style="padding:10px; border:1px solid #333;">項目</th><th style="padding:10px; border:1px solid #333;">内容</th></tr>
<tr><td style="padding:10px; border:1px solid #ddd;">項目名</td><td style="padding:10px; border:1px solid #ddd;">内容</td></tr>
</table>

**Q&A**
<div style="margin:20px 0;">
<div style="background:#333; color:#fff; padding:10px 15px;">Q. 質問</div>
<div style="background:#f5f5f5; padding:15px; border:1px solid #ddd; border-top:none;">A. 回答</div>
</div>

## 出力形式（厳守）
<!-- META_DESCRIPTION: 120文字以内の要約 -->
<!-- TITLE: 改善後タイトル（必要な場合） -->
<h2>最初の見出し</h2>
（以降HTMLコード本文）`;
            
            $('#btn-load-default-prompt').click(function(){
                if($('textarea[name="custom_prompt"]').val().trim() !== '') {
                    if(!confirm('現在のプロンプトを上書きしますか？')) return;
                }
                $('textarea[name="custom_prompt"]').val(defaultPrompt);
            });
            
            $('#btn-clear-prompt').click(function(){
                if(!confirm('プロンプトをクリアしますか？（デフォルトプロンプトが使用されます）')) return;
                $('textarea[name="custom_prompt"]').val('');
            });
            
            $('#settings-form').submit(function(e){
                e.preventDefault();
                
                var data = {
                    action: 'gi_seo_save_settings',
                    nonce: nonce,
                    api_key: $('input[name="api_key"]').val(),
                    model: $('select[name="model"]').val(),
                    temperature: $('input[name="temperature"]').val(),
                    processing_interval: $('input[name="processing_interval"]').val(),
                    post_types: [],
                    custom_prompt: $('textarea[name="custom_prompt"]').val()
                };
                
                $('input[name="post_types[]"]:checked').each(function(){
                    data.post_types.push($(this).val());
                });
                
                if(data.post_types.length === 0){
                    alert('投稿タイプを1つ以上選択してください');
                    return;
                }
                
                $.post(ajaxurl, data, function(r){
                    alert(r.success ? '✓ 保存しました' : 'エラー: ' + r.data);
                });
            });
        });
        </script>
        <?php
    }
}

// プラグイン初期化
new GI_SEO_Content_Manager();