<?php
/**
 * Affiliate Ad Manager System - Complete Edition
 * アフィリエイト広告管理システム - 完全統合版
 * 
 * Features:
 * - WordPress管理画面での広告管理
 * - 複数の広告位置対応（サイドバー、コンテンツ内など）
 * - クリック統計・表示統計
 * - 詳細分析機能（ページ別、カテゴリー別、デバイス別）
 * - スケジュール配信
 * - CSVエクスポート
 * 
 * @package Joseikin_Insight
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class JI_Affiliate_Ad_Manager {
    
    private $table_name_ads;
    private $table_name_stats;
    private $table_name_stats_detail;
    
    /**
     * 広告位置ラベル定義
     */
    private $position_labels = array(
        // シングルページ - コラム
        'single_column_sidebar_top' => 'コラム:SB上',
        'single_column_sidebar_middle' => 'コラム:SB中',
        'single_column_sidebar_bottom' => 'コラム:SB下',
        'single_column_content_top' => 'コラム:本文上',
        'single_column_content_middle' => 'コラム:本文中',
        'single_column_content_bottom' => 'コラム:本文下',
        // シングルページ - 補助金
        'single_grant_sidebar_top' => '補助金:SB上',
        'single_grant_sidebar_middle' => '補助金:SB中',
        'single_grant_sidebar_bottom' => '補助金:SB下',
        'single_grant_content_top' => '補助金:本文上',
        'single_grant_content_middle' => '補助金:本文中',
        'single_grant_content_bottom' => '補助金:本文下',
        // アーカイブページ - 補助金
        'archive_grant_sidebar_top' => '補助金AR:SB上',
        'archive_grant_sidebar_middle' => '補助金AR:SB中',
        'archive_grant_sidebar_bottom' => '補助金AR:SB下',
        'archive_grant_content_top' => '補助金AR:本文上',
        'archive_grant_content_bottom' => '補助金AR:本文下',
        // アーカイブページ - コラム
        'archive_column_sidebar_pr' => 'コラムAR:PR',
        'archive_column_sidebar_top' => 'コラムAR:SB上',
        'archive_column_sidebar_bottom' => 'コラムAR:SB下',
        // Taxonomy アーカイブ
        'category_grant_sidebar_top' => 'カテゴリAR:SB上',
        'category_grant_sidebar_middle' => 'カテゴリAR:SB中',
        'category_grant_sidebar_bottom' => 'カテゴリAR:SB下',
        'prefecture_grant_sidebar_top' => '都道府県AR:SB上',
        'prefecture_grant_sidebar_middle' => '都道府県AR:SB中',
        'prefecture_grant_sidebar_bottom' => '都道府県AR:SB下',
        'municipality_grant_sidebar_top' => '市町村AR:SB上',
        'municipality_grant_sidebar_middle' => '市町村AR:SB中',
        'municipality_grant_sidebar_bottom' => '市町村AR:SB下',
        'purpose_grant_sidebar_top' => '目的AR:SB上',
        'purpose_grant_sidebar_middle' => '目的AR:SB中',
        'purpose_grant_sidebar_bottom' => '目的AR:SB下',
        'tag_grant_sidebar_top' => 'タグAR:SB上',
        'tag_grant_sidebar_middle' => 'タグAR:SB中',
        'tag_grant_sidebar_bottom' => 'タグAR:SB下',
        // フロントページ
        'front_hero_bottom' => 'TOP:ヒーロー下',
        'front_column_zone_top' => 'TOP:コラム上',
        'front_column_zone_bottom' => 'TOP:コラム下',
        'front_grant_news_top' => 'TOP:ニュース上',
        'front_grant_news_bottom' => 'TOP:ニュース下',
        'front_search_top' => 'TOP:検索上',
        // 汎用
        'sidebar_top' => 'SB上',
        'sidebar_middle' => 'SB中',
        'sidebar_bottom' => 'SB下',
        'content_top' => '本文上',
        'content_middle' => '本文中',
        'content_bottom' => '本文下',
        'archive_sidebar_pr' => 'アーカイブPR'
    );
    
    public function __construct() {
        global $wpdb;
        $this->table_name_ads = $wpdb->prefix . 'ji_affiliate_ads';
        $this->table_name_stats = $wpdb->prefix . 'ji_affiliate_stats';
        $this->table_name_stats_detail = $wpdb->prefix . 'ji_affiliate_stats_detail';
        
        // フック登録
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_ji_save_ad', array($this, 'ajax_save_ad'));
        add_action('wp_ajax_ji_get_ad', array($this, 'ajax_get_ad'));
        add_action('wp_ajax_ji_delete_ad', array($this, 'ajax_delete_ad'));
        add_action('wp_ajax_ji_get_ad_stats', array($this, 'ajax_get_ad_stats'));
        add_action('wp_ajax_ji_track_ad_impression', array($this, 'ajax_track_impression'));
        add_action('wp_ajax_nopriv_ji_track_ad_impression', array($this, 'ajax_track_impression'));
        add_action('wp_ajax_ji_track_ad_click', array($this, 'ajax_track_click'));
        add_action('wp_ajax_nopriv_ji_track_ad_click', array($this, 'ajax_track_click'));
    }
    
    /**
     * 初期化
     */
    public function init() {
        $this->create_tables();
    }
    
    /**
     * データベーステーブル作成
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // 広告テーブル
        $sql_ads = "CREATE TABLE IF NOT EXISTS {$this->table_name_ads} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            ad_type varchar(50) NOT NULL DEFAULT 'html',
            content longtext NOT NULL,
            link_url varchar(500) DEFAULT '',
            positions text NOT NULL,
            target_pages text DEFAULT NULL,
            target_categories text DEFAULT NULL,
            device_target varchar(20) NOT NULL DEFAULT 'all',
            status varchar(20) NOT NULL DEFAULT 'active',
            priority int(11) NOT NULL DEFAULT 0,
            start_date datetime DEFAULT NULL,
            end_date datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY priority (priority),
            KEY device_target (device_target)
        ) $charset_collate;";
        
        // 統計テーブル
        $sql_stats = "CREATE TABLE IF NOT EXISTS {$this->table_name_stats} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ad_id bigint(20) NOT NULL,
            date date NOT NULL,
            impressions int(11) NOT NULL DEFAULT 0,
            clicks int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY ad_date (ad_id, date),
            KEY ad_id (ad_id),
            KEY date (date)
        ) $charset_collate;";
        
        // 詳細統計テーブル
        $sql_stats_detail = "CREATE TABLE IF NOT EXISTS {$this->table_name_stats_detail} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ad_id bigint(20) NOT NULL,
            event_type enum('impression','click') NOT NULL DEFAULT 'impression',
            page_url varchar(500) DEFAULT NULL,
            page_title varchar(500) DEFAULT NULL,
            post_id bigint(20) DEFAULT NULL,
            category_id bigint(20) DEFAULT NULL,
            category_name varchar(200) DEFAULT NULL,
            position varchar(100) DEFAULT NULL,
            device varchar(20) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            referer varchar(500) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ad_id (ad_id),
            KEY event_type (event_type),
            KEY post_id (post_id),
            KEY category_id (category_id),
            KEY position (position),
            KEY device (device),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_ads);
        dbDelta($sql_stats);
        dbDelta($sql_stats_detail);
        
        // テーブル構造の更新（既存インストール用）
        $this->update_table_structure();
    }
    
    /**
     * テーブル構造更新
     */
    private function update_table_structure() {
        global $wpdb;
        
        // device_target列を追加
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM {$this->table_name_ads} LIKE 'device_target'"
        );
        if (empty($column_exists)) {
            $wpdb->query(
                "ALTER TABLE {$this->table_name_ads} 
                ADD COLUMN device_target varchar(20) NOT NULL DEFAULT 'all' AFTER target_pages,
                ADD KEY device_target (device_target)"
            );
        }
        
        // position を positions に変更
        $position_column = $wpdb->get_results(
            "SHOW COLUMNS FROM {$this->table_name_ads} LIKE 'position'"
        );
        if (!empty($position_column)) {
            $wpdb->query(
                "ALTER TABLE {$this->table_name_ads} 
                CHANGE COLUMN position positions text NOT NULL"
            );
        }
        
        // target_categories カラムを追加
        $target_categories_column = $wpdb->get_results(
            "SHOW COLUMNS FROM {$this->table_name_ads} LIKE 'target_categories'"
        );
        if (empty($target_categories_column)) {
            $wpdb->query(
                "ALTER TABLE {$this->table_name_ads} 
                ADD COLUMN target_categories text DEFAULT NULL AFTER target_pages"
            );
        }
    }
    
    /**
     * 管理メニュー追加
     */
    public function add_admin_menu() {
        add_menu_page(
            'アフィリエイト広告管理',
            'アフィリエイト広告',
            'manage_options',
            'ji-affiliate-ads',
            array($this, 'admin_page'),
            'dashicons-megaphone',
            25
        );
        
        add_submenu_page(
            'ji-affiliate-ads',
            '広告一覧',
            '広告一覧',
            'manage_options',
            'ji-affiliate-ads',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'ji-affiliate-ads',
            '統計情報',
            '統計情報',
            'manage_options',
            'ji-affiliate-stats',
            array($this, 'stats_page')
        );
        
        add_submenu_page(
            'ji-affiliate-ads',
            '設定',
            '設定',
            'manage_options',
            'ji-affiliate-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * 管理画面アセット読み込み
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'ji-affiliate') === false) {
            return;
        }
        
        // インラインスタイルとスクリプトを出力
        add_action('admin_head', array($this, 'output_admin_styles'));
        add_action('admin_footer', array($this, 'output_admin_scripts'));
        
        // JavaScript設定の出力
        wp_localize_script('jquery', 'jiAdminAds', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ji_ad_nonce'),
        ));
    }
    
    /**
     * 管理画面スタイル出力
     */
    public function output_admin_styles() {
        ?>
        <style>
        .ji-affiliate-admin {
            margin: 20px 20px 0 0;
        }
        .ji-affiliate-admin h2,
        .ji-affiliate-admin h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #2271b1;
        }
        .ji-status-badge {
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .ji-status-badge.active {
            background: #00a32a;
            color: white;
        }
        .ji-status-badge.inactive {
            background: #dcdcde;
            color: #50575e;
        }
        .ji-status-badge.draft {
            background: #f0f0f1;
            color: #2c3338;
        }
        .ji-modal {
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7);
        }
        .ji-modal-content {
            background-color: #fff;
            margin: 3% auto;
            padding: 30px;
            border: 1px solid #888;
            width: 85%;
            max-width: 900px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            max-height: 90vh;
            overflow-y: auto;
        }
        .ji-modal-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 20px;
        }
        .ji-modal-close:hover {
            color: #000;
        }
        .required {
            color: #d63638;
        }
        .ji-notice {
            margin: 20px 0;
            padding: 12px;
            border-left: 4px solid;
        }
        .ji-notice.notice-success {
            background: #edfaed;
            border-color: #00a32a;
        }
        .ji-notice.notice-error {
            background: #fef7f1;
            border-color: #d63638;
        }
        .ji-notice.notice-info {
            background: #f0f6fc;
            border-color: #2271b1;
        }
        body.modal-open {
            overflow: hidden;
        }
        .ji-event-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
        }
        .ji-event-badge.impression {
            background: #e3f2fd;
            color: #1976d2;
        }
        .ji-event-badge.click {
            background: #fff3e0;
            color: #f57c00;
        }
        .ji-stats-filters {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
        }
        .ji-chart-container {
            background: white;
            padding: 20px;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            max-width: 1000px;
        }
        .ji-form-section {
            background: #f9f9f9;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
        }
        .ji-form-section h4 {
            margin: 0 0 10px 0;
            padding: 0;
            font-size: 14px;
            color: #1d2327;
        }
        </style>
        <?php
    }
    
    /**
     * 管理画面スクリプト出力
     */
    public function output_admin_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // モーダル開閉
            function openModal() {
                $('#ji-ad-modal').show();
                $('body').addClass('modal-open');
            }
            
            function closeModal() {
                $('#ji-ad-modal').hide();
                $('body').removeClass('modal-open');
                $('#ji-ad-form')[0].reset();
                $('#ad_id').val('');
                $('#ji-modal-title').text('広告を追加');
            }
            
            // 新規追加ボタン
            $('.ji-add-new-ad').on('click', function(e) {
                e.preventDefault();
                openModal();
            });
            
            // モーダルを閉じる
            $('.ji-modal-close').on('click', closeModal);
            $(window).on('click', function(e) {
                if ($(e.target).hasClass('ji-modal')) {
                    closeModal();
                }
            });
            
            // 編集ボタン
            $(document).on('click', '.ji-edit-ad', function() {
                var adId = $(this).data('ad-id');
                
                $.ajax({
                    url: jiAdminAds.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ji_get_ad',
                        ad_id: adId,
                        nonce: jiAdminAds.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var ad = response.data;
                            
                            $('#ad_id').val(ad.id);
                            $('#title').val(ad.title);
                            $('#ad_type').val(ad.ad_type);
                            $('#content').val(ad.content);
                            $('#link_url').val(ad.link_url);
                            $('#device_target').val(ad.device_target || 'all');
                            $('#status').val(ad.status);
                            $('#priority').val(ad.priority);
                            
                            // 複数選択の配置位置
                            if (ad.positions_array && ad.positions_array.length > 0) {
                                $('#positions').val(ad.positions_array);
                            }
                            
                            // 対象ページ
                            if (ad.target_pages_array && ad.target_pages_array.length > 0) {
                                $('#target_pages').val(ad.target_pages_array);
                            }
                            
                            // 対象カテゴリー
                            if (ad.target_categories_array && ad.target_categories_array.length > 0) {
                                $('#target_categories').val(ad.target_categories_array);
                            }
                            
                            // 日付
                            if (ad.start_date) {
                                $('#start_date').val(ad.start_date.replace(' ', 'T').slice(0, 16));
                            }
                            if (ad.end_date) {
                                $('#end_date').val(ad.end_date.replace(' ', 'T').slice(0, 16));
                            }
                            
                            $('#ji-modal-title').text('広告を編集');
                            openModal();
                        } else {
                            alert('広告データの取得に失敗しました');
                        }
                    },
                    error: function() {
                        alert('通信エラーが発生しました');
                    }
                });
            });
            
            // フォーム送信
            $('#ji-ad-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serializeArray();
                formData.push({ name: 'action', value: 'ji_save_ad' });
                formData.push({ name: 'nonce', value: jiAdminAds.nonce });
                
                $.ajax({
                    url: jiAdminAds.ajax_url,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert('保存に失敗しました: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('通信エラーが発生しました');
                    }
                });
            });
            
            // 削除ボタン
            $(document).on('click', '.ji-delete-ad', function() {
                if (!confirm('この広告を削除しますか？')) {
                    return;
                }
                
                var adId = $(this).data('ad-id');
                
                $.ajax({
                    url: jiAdminAds.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ji_delete_ad',
                        ad_id: adId,
                        nonce: jiAdminAds.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('削除に失敗しました');
                        }
                    },
                    error: function() {
                        alert('通信エラーが発生しました');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * 広告管理ページ
     */
    public function admin_page() {
        global $wpdb;
        
        $ads = $wpdb->get_results(
            "SELECT * FROM {$this->table_name_ads} ORDER BY priority DESC, id DESC"
        );
        
        $this->render_ads_list_template($ads);
    }
    
    /**
     * 広告一覧テンプレート
     */
    private function render_ads_list_template($ads) {
        $position_labels = $this->position_labels;
        ?>
        <div class="wrap ji-affiliate-admin">
            <h1 class="wp-heading-inline">アフィリエイト広告管理</h1>
            <a href="#" class="page-title-action ji-add-new-ad">新規追加</a>
            <hr class="wp-header-end">
            
            <?php if (!empty($ads)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>タイトル</th>
                            <th>タイプ</th>
                            <th>配置位置</th>
                            <th>デバイス</th>
                            <th>ステータス</th>
                            <th>優先度</th>
                            <th>開始日</th>
                            <th>終了日</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ads as $ad): ?>
                            <tr data-ad-id="<?php echo esc_attr($ad->id); ?>">
                                <td><?php echo esc_html($ad->id); ?></td>
                                <td><strong><?php echo esc_html($ad->title); ?></strong></td>
                                <td>
                                    <?php 
                                    $types = array('html' => 'HTML', 'image' => '画像', 'script' => 'スクリプト');
                                    echo esc_html($types[$ad->ad_type] ?? $ad->ad_type);
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $ad_positions = isset($ad->positions) ? explode(',', $ad->positions) : array();
                                    $display_positions = array();
                                    foreach ($ad_positions as $pos) {
                                        $pos = trim($pos);
                                        $display_positions[] = $position_labels[$pos] ?? $pos;
                                    }
                                    echo esc_html(implode(', ', $display_positions));
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $devices = array('all' => 'すべて', 'desktop' => 'PC', 'mobile' => 'スマホ');
                                    echo esc_html($devices[$ad->device_target ?? 'all'] ?? $ad->device_target);
                                    ?>
                                </td>
                                <td>
                                    <span class="ji-status-badge <?php echo esc_attr($ad->status); ?>">
                                        <?php 
                                        $statuses = array('active' => '有効', 'inactive' => '無効', 'draft' => '下書き');
                                        echo esc_html($statuses[$ad->status] ?? $ad->status);
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($ad->priority); ?></td>
                                <td><?php echo $ad->start_date ? esc_html(date('Y/m/d', strtotime($ad->start_date))) : '-'; ?></td>
                                <td><?php echo $ad->end_date ? esc_html(date('Y/m/d', strtotime($ad->end_date))) : '-'; ?></td>
                                <td>
                                    <button class="button ji-edit-ad" data-ad-id="<?php echo esc_attr($ad->id); ?>">編集</button>
                                    <button class="button ji-delete-ad" data-ad-id="<?php echo esc_attr($ad->id); ?>">削除</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>広告がまだありません。「新規追加」から最初の広告を作成してください。</p>
            <?php endif; ?>
        </div>
        
        <?php $this->render_ad_modal(); ?>
        <?php
    }
    
    /**
     * 広告編集モーダル
     */
    private function render_ad_modal() {
        ?>
        <div id="ji-ad-modal" class="ji-modal" style="display: none;">
            <div class="ji-modal-content">
                <span class="ji-modal-close">&times;</span>
                <h2 id="ji-modal-title">広告を追加</h2>
                
                <form id="ji-ad-form">
                    <input type="hidden" name="ad_id" id="ad_id" value="">
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="title">タイトル <span class="required">*</span></label></th>
                            <td><input type="text" name="title" id="title" class="regular-text" required></td>
                        </tr>
                        
                        <tr>
                            <th><label for="ad_type">広告タイプ <span class="required">*</span></label></th>
                            <td>
                                <select name="ad_type" id="ad_type" required>
                                    <option value="html">HTML</option>
                                    <option value="image">画像</option>
                                    <option value="script">スクリプト</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="content">広告コンテンツ <span class="required">*</span></label></th>
                            <td>
                                <textarea name="content" id="content" rows="8" class="large-text" required></textarea>
                                <p class="description">HTML、画像タグ、またはスクリプトコードを入力してください。</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="link_url">リンクURL</label></th>
                            <td><input type="url" name="link_url" id="link_url" class="regular-text"></td>
                        </tr>
                        
                        <tr>
                            <th><label for="positions">配置位置 <span class="required">*</span></label></th>
                            <td>
                                <?php $this->render_positions_select(); ?>
                                <p class="description">Ctrl/Cmd+クリックで複数選択可能</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="target_pages">対象ページ</label></th>
                            <td>
                                <?php $this->render_target_pages_select(); ?>
                                <p class="description">空白の場合すべてのページに表示</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="target_categories">対象カテゴリー</label></th>
                            <td>
                                <?php $this->render_target_categories_select(); ?>
                                <p class="description">空白の場合すべてのカテゴリーに表示</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="device_target">表示デバイス <span class="required">*</span></label></th>
                            <td>
                                <select name="device_target" id="device_target" required>
                                    <option value="all">すべて（PC・スマホ）</option>
                                    <option value="desktop">PCのみ</option>
                                    <option value="mobile">スマートフォンのみ</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="status">ステータス</label></th>
                            <td>
                                <select name="status" id="status">
                                    <option value="active">有効</option>
                                    <option value="inactive">無効</option>
                                    <option value="draft">下書き</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="priority">優先度</label></th>
                            <td>
                                <input type="number" name="priority" id="priority" value="0" min="0" max="100">
                                <p class="description">数値が大きいほど優先的に表示（0-100）</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="start_date">開始日時</label></th>
                            <td><input type="datetime-local" name="start_date" id="start_date"></td>
                        </tr>
                        
                        <tr>
                            <th><label for="end_date">終了日時</label></th>
                            <td><input type="datetime-local" name="end_date" id="end_date"></td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">保存</button>
                        <button type="button" class="button ji-modal-close">キャンセル</button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * 配置位置選択フィールド
     */
    private function render_positions_select() {
        ?>
        <select name="positions[]" id="positions" multiple required style="height: 200px; width: 100%;">
            <optgroup label="シングルページ - コラム">
                <option value="single_column_sidebar_top">コラム: サイドバー上部</option>
                <option value="single_column_sidebar_middle">コラム: サイドバー中央</option>
                <option value="single_column_sidebar_bottom">コラム: サイドバー下部</option>
                <option value="single_column_content_top">コラム: コンテンツ上部</option>
                <option value="single_column_content_middle">コラム: コンテンツ中央</option>
                <option value="single_column_content_bottom">コラム: コンテンツ下部</option>
            </optgroup>
            <optgroup label="シングルページ - 補助金">
                <option value="single_grant_sidebar_top">補助金: サイドバー上部</option>
                <option value="single_grant_sidebar_middle">補助金: サイドバー中央</option>
                <option value="single_grant_sidebar_bottom">補助金: サイドバー下部</option>
                <option value="single_grant_content_top">補助金: コンテンツ上部</option>
                <option value="single_grant_content_middle">補助金: コンテンツ中央</option>
                <option value="single_grant_content_bottom">補助金: コンテンツ下部</option>
            </optgroup>
            <optgroup label="アーカイブページ - 補助金">
                <option value="archive_grant_sidebar_top">補助金AR: サイドバー上部</option>
                <option value="archive_grant_sidebar_middle">補助金AR: サイドバー中央</option>
                <option value="archive_grant_sidebar_bottom">補助金AR: サイドバー下部</option>
                <option value="archive_grant_content_top">補助金AR: コンテンツ上部</option>
                <option value="archive_grant_content_bottom">補助金AR: コンテンツ下部</option>
            </optgroup>
            <optgroup label="Taxonomy アーカイブ">
                <option value="category_grant_sidebar_top">カテゴリAR: SB上部</option>
                <option value="category_grant_sidebar_middle">カテゴリAR: SB中央</option>
                <option value="category_grant_sidebar_bottom">カテゴリAR: SB下部</option>
                <option value="prefecture_grant_sidebar_top">都道府県AR: SB上部</option>
                <option value="prefecture_grant_sidebar_middle">都道府県AR: SB中央</option>
                <option value="prefecture_grant_sidebar_bottom">都道府県AR: SB下部</option>
                <option value="municipality_grant_sidebar_top">市町村AR: SB上部</option>
                <option value="municipality_grant_sidebar_middle">市町村AR: SB中央</option>
                <option value="municipality_grant_sidebar_bottom">市町村AR: SB下部</option>
                <option value="purpose_grant_sidebar_top">目的AR: SB上部</option>
                <option value="purpose_grant_sidebar_middle">目的AR: SB中央</option>
                <option value="purpose_grant_sidebar_bottom">目的AR: SB下部</option>
                <option value="tag_grant_sidebar_top">タグAR: SB上部</option>
                <option value="tag_grant_sidebar_middle">タグAR: SB中央</option>
                <option value="tag_grant_sidebar_bottom">タグAR: SB下部</option>
            </optgroup>
            <optgroup label="アーカイブページ - コラム">
                <option value="archive_column_sidebar_pr">コラムAR: PR欄</option>
                <option value="archive_column_sidebar_top">コラムAR: サイドバー上部</option>
                <option value="archive_column_sidebar_bottom">コラムAR: サイドバー下部</option>
            </optgroup>
            <optgroup label="フロントページ">
                <option value="front_hero_bottom">TOP: ヒーロー下部</option>
                <option value="front_column_zone_top">TOP: コラムゾーン上部</option>
                <option value="front_column_zone_bottom">TOP: コラムゾーン下部</option>
                <option value="front_grant_news_top">TOP: 補助金ニュース上部</option>
                <option value="front_grant_news_bottom">TOP: 補助金ニュース下部</option>
                <option value="front_search_top">TOP: 検索エリア上部</option>
            </optgroup>
            <optgroup label="汎用">
                <option value="sidebar_top">汎用: サイドバー上部</option>
                <option value="sidebar_middle">汎用: サイドバー中央</option>
                <option value="sidebar_bottom">汎用: サイドバー下部</option>
                <option value="content_top">汎用: コンテンツ上部</option>
                <option value="content_middle">汎用: コンテンツ中央</option>
                <option value="content_bottom">汎用: コンテンツ下部</option>
            </optgroup>
        </select>
        <?php
    }
    
    /**
     * 対象ページ選択フィールド
     */
    private function render_target_pages_select() {
        ?>
        <select name="target_pages[]" id="target_pages" multiple style="height: 150px; width: 100%;">
            <option value="">すべてのページ</option>
            <optgroup label="シングルページ">
                <option value="single-grant">補助金詳細ページ</option>
                <option value="single-column">コラム詳細ページ</option>
                <option value="single-post">投稿詳細ページ</option>
                <option value="single-page">固定ページ</option>
            </optgroup>
            <optgroup label="アーカイブページ">
                <option value="archive-grant">補助金アーカイブ</option>
                <option value="archive-column">コラムアーカイブ</option>
                <option value="archive">その他アーカイブ</option>
            </optgroup>
            <optgroup label="Taxonomy アーカイブ">
                <option value="taxonomy-grant_category">補助金カテゴリアーカイブ</option>
                <option value="taxonomy-grant_prefecture">補助金都道府県アーカイブ</option>
                <option value="taxonomy-grant_municipality">補助金市町村アーカイブ</option>
                <option value="taxonomy-grant_purpose">補助金目的アーカイブ</option>
                <option value="taxonomy-grant_tag">補助金タグアーカイブ</option>
            </optgroup>
            <optgroup label="その他">
                <option value="front-page">フロントページ</option>
                <option value="search">検索結果ページ</option>
                <option value="404">404ページ</option>
            </optgroup>
        </select>
        <?php
    }
    
    /**
     * 対象カテゴリー選択フィールド
     */
    private function render_target_categories_select() {
        ?>
        <select name="target_categories[]" id="target_categories" multiple style="height: 200px; width: 100%;">
            <option value="">すべてのカテゴリー</option>
            
            <?php
            // 助成金カテゴリー
            $grant_categories = get_terms(array(
                'taxonomy' => 'grant_category',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC'
            ));
            
            if (!empty($grant_categories) && !is_wp_error($grant_categories)): ?>
                <optgroup label="助成金カテゴリー">
                <?php foreach ($grant_categories as $category): ?>
                    <option value="grant_category_<?php echo esc_attr($category->term_id); ?>">
                        <?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)
                    </option>
                <?php endforeach; ?>
                </optgroup>
            <?php endif; ?>
            
            <?php
            // コラムカテゴリー
            $column_categories = get_terms(array(
                'taxonomy' => 'column_category',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC'
            ));
            
            if (!empty($column_categories) && !is_wp_error($column_categories)): ?>
                <optgroup label="コラムカテゴリー">
                <?php foreach ($column_categories as $category): ?>
                    <option value="column_category_<?php echo esc_attr($category->term_id); ?>">
                        <?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)
                    </option>
                <?php endforeach; ?>
                </optgroup>
            <?php endif; ?>
            
            <?php
            // WordPress標準カテゴリー
            $wp_categories = get_categories(array(
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC'
            ));
            
            if (!empty($wp_categories) && !is_wp_error($wp_categories)): ?>
                <optgroup label="標準カテゴリー">
                <?php foreach ($wp_categories as $category): ?>
                    <option value="category_<?php echo esc_attr($category->term_id); ?>">
                        <?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)
                    </option>
                <?php endforeach; ?>
                </optgroup>
            <?php endif; ?>
        </select>
        <?php
    }
    
    /**
     * 統計ページ
     */
    public function stats_page() {
        global $wpdb;
        
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '30';
        $ad_id = isset($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;
        
        $period_labels = array(
            '7' => '過去7日間',
            '30' => '過去30日間',
            '90' => '過去90日間',
            '365' => '過去365日間'
        );
        
        // 基本統計
        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                a.id, a.title, a.positions,
                COALESCE(SUM(s.impressions), 0) as total_impressions,
                COALESCE(SUM(s.clicks), 0) as total_clicks,
                CASE 
                    WHEN COALESCE(SUM(s.impressions), 0) > 0 
                    THEN ROUND((COALESCE(SUM(s.clicks), 0) / COALESCE(SUM(s.impressions), 1)) * 100, 2)
                    ELSE 0
                END as ctr
            FROM {$this->table_name_ads} a
            LEFT JOIN {$this->table_name_stats} s ON a.id = s.ad_id AND s.date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            GROUP BY a.id
            ORDER BY total_clicks DESC",
            $period
        ));
        
        // 日別統計
        $daily_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                s.date,
                SUM(s.impressions) as impressions,
                SUM(s.clicks) as clicks
            FROM {$this->table_name_stats} s
            WHERE s.date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            GROUP BY s.date
            ORDER BY s.date ASC",
            $period
        ));
        
        // 詳細統計（特定広告選択時）
        $detailed_stats = array();
        if ($ad_id > 0) {
            $detailed_stats = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    DATE(created_at) as date,
                    event_type, position, category_name, page_url, device,
                    COUNT(*) as count
                FROM {$this->table_name_stats_detail}
                WHERE ad_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                GROUP BY DATE(created_at), event_type, position, category_name, device
                ORDER BY created_at DESC",
                $ad_id, $period
            ));
        }
        
        // 広告一覧（フィルター用）
        $all_ads = $wpdb->get_results(
            "SELECT id, title FROM {$this->table_name_ads} ORDER BY title ASC"
        );
        
        $this->render_stats_template($stats, $daily_stats, $detailed_stats, $all_ads, $period, $ad_id, $period_labels);
    }
    
    /**
     * 統計テンプレート
     */
    private function render_stats_template($stats, $daily_stats, $detailed_stats, $all_ads, $period, $ad_id, $period_labels) {
        ?>
        <div class="wrap ji-affiliate-admin">
            <h1>広告統計情報 <span style="font-size: 16px; font-weight: normal; color: #666;">- <?php echo esc_html($period_labels[$period]); ?></span></h1>
            <hr class="wp-header-end">
            
            <!-- フィルター -->
            <div class="ji-stats-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="ji-affiliate-stats">
                    
                    <div style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                        <div>
                            <label for="period" style="display: block; margin-bottom: 5px; font-weight: 600;">期間選択</label>
                            <select name="period" id="period" style="min-width: 150px;">
                                <?php foreach ($period_labels as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($period, $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="ad_id" style="display: block; margin-bottom: 5px; font-weight: 600;">広告選択</label>
                            <select name="ad_id" id="ad_id" style="min-width: 250px;">
                                <option value="0">すべての広告</option>
                                <?php foreach ($all_ads as $ad): ?>
                                    <option value="<?php echo esc_attr($ad->id); ?>" <?php selected($ad_id, $ad->id); ?>>
                                        <?php echo esc_html($ad->title); ?> (ID: <?php echo esc_html($ad->id); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <button type="submit" class="button button-primary">表示</button>
                            <a href="?page=ji-affiliate-stats" class="button">リセット</a>
                        </div>
                        
                        <?php if (!empty($stats)): ?>
                        <div style="margin-left: auto;">
                            <button type="button" id="ji-export-csv" class="button">CSVエクスポート</button>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- 統計サマリー -->
            <div class="ji-stats-summary">
                <h2>統計サマリー</h2>
                
                <?php if (!empty($stats)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>広告タイトル</th>
                                <th style="width: 200px;">配置位置</th>
                                <th style="width: 100px;">表示回数</th>
                                <th style="width: 100px;">クリック数</th>
                                <th style="width: 80px;">CTR(%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_impressions = 0;
                            $total_clicks = 0;
                            foreach ($stats as $stat): 
                                $total_impressions += $stat->total_impressions;
                                $total_clicks += $stat->total_clicks;
                            ?>
                                <tr>
                                    <td><?php echo esc_html($stat->id); ?></td>
                                    <td>
                                        <strong><?php echo esc_html($stat->title); ?></strong>
                                        <?php if ($ad_id == 0): ?>
                                        <br><a href="?page=ji-affiliate-stats&period=<?php echo esc_attr($period); ?>&ad_id=<?php echo esc_attr($stat->id); ?>" class="button button-small" style="margin-top: 5px;">詳細</a>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 12px;"><?php echo esc_html(substr($stat->positions, 0, 30)); ?></td>
                                    <td><strong><?php echo number_format($stat->total_impressions); ?></strong></td>
                                    <td><strong><?php echo number_format($stat->total_clicks); ?></strong></td>
                                    <td>
                                        <strong style="color: <?php echo $stat->ctr >= 2 ? '#00a32a' : ($stat->ctr >= 1 ? '#f0b849' : '#2c3338'); ?>">
                                            <?php echo number_format($stat->ctr, 2); ?>%
                                        </strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f0f0f1; font-weight: bold;">
                                <td colspan="3">合計</td>
                                <td><?php echo number_format($total_impressions); ?></td>
                                <td><?php echo number_format($total_clicks); ?></td>
                                <td><?php echo $total_impressions > 0 ? number_format(($total_clicks / $total_impressions) * 100, 2) : '0.00'; ?>%</td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <!-- 日別グラフ -->
                    <?php if (!empty($daily_stats)): ?>
                    <div style="margin-top: 40px;">
                        <h3>日別推移グラフ</h3>
                        <div class="ji-chart-container">
                            <canvas id="ji-daily-chart" width="800" height="300"></canvas>
                        </div>
                    </div>
                    
                    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
                    <script>
                    jQuery(document).ready(function($) {
                        var ctx = document.getElementById('ji-daily-chart').getContext('2d');
                        var dates = [<?php echo '"' . implode('", "', array_map(function($s) { return date('m/d', strtotime($s->date)); }, $daily_stats)) . '"'; ?>];
                        var impressions = [<?php echo implode(', ', array_map(function($s) { return $s->impressions; }, $daily_stats)); ?>];
                        var clicks = [<?php echo implode(', ', array_map(function($s) { return $s->clicks; }, $daily_stats)); ?>];
                        
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: dates,
                                datasets: [
                                    {
                                        label: '表示回数',
                                        data: impressions,
                                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                        borderColor: 'rgba(54, 162, 235, 1)',
                                        borderWidth: 2,
                                        fill: true,
                                        tension: 0.4
                                    },
                                    {
                                        label: 'クリック数',
                                        data: clicks,
                                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                        borderColor: 'rgba(255, 99, 132, 1)',
                                        borderWidth: 2,
                                        fill: true,
                                        tension: 0.4
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    title: { display: true, text: '日別推移', font: { size: 16 } },
                                    legend: { display: true, position: 'top' }
                                },
                                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                            }
                        });
                        
                        // CSVエクスポート
                        $('#ji-export-csv').on('click', function() {
                            var csv = ['ID,広告タイトル,配置位置,表示回数,クリック数,CTR(%)'];
                            <?php foreach ($stats as $stat): ?>
                            csv.push('<?php echo $stat->id; ?>,<?php echo addslashes($stat->title); ?>,<?php echo addslashes($stat->positions); ?>,<?php echo $stat->total_impressions; ?>,<?php echo $stat->total_clicks; ?>,<?php echo number_format($stat->ctr, 2); ?>');
                            <?php endforeach; ?>
                            
                            var blob = new Blob(['\uFEFF' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
                            var link = document.createElement('a');
                            link.href = URL.createObjectURL(blob);
                            link.download = 'affiliate_stats_<?php echo date('Ymd'); ?>.csv';
                            link.click();
                        });
                    });
                    </script>
                    <?php endif; ?>
                    
                    <!-- 詳細統計（特定広告選択時） -->
                    <?php if ($ad_id > 0 && !empty($detailed_stats)): ?>
                    <div style="margin-top: 40px;">
                        <h3>詳細統計</h3>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>日付</th>
                                    <th>イベント</th>
                                    <th>配置位置</th>
                                    <th>カテゴリー</th>
                                    <th>デバイス</th>
                                    <th>回数</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detailed_stats as $detail): ?>
                                    <tr>
                                        <td><?php echo esc_html(date('Y/m/d', strtotime($detail->date))); ?></td>
                                        <td>
                                            <span class="ji-event-badge <?php echo esc_attr($detail->event_type); ?>">
                                                <?php echo $detail->event_type === 'impression' ? '表示' : 'クリック'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($detail->position ?: '-'); ?></td>
                                        <td><?php echo esc_html($detail->category_name ?: '-'); ?></td>
                                        <td><?php echo esc_html(ucfirst($detail->device ?: '-')); ?></td>
                                        <td><strong><?php echo number_format($detail->count); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <p>選択された期間の統計データがありません。</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * 設定ページ
     */
    public function settings_page() {
        // 設定保存処理
        if (isset($_POST['ji_save_settings']) && check_admin_referer('ji_affiliate_settings')) {
            update_option('ji_affiliate_tracking_enabled', isset($_POST['tracking_enabled']) ? '1' : '0');
            update_option('ji_affiliate_auto_optimize', isset($_POST['auto_optimize']) ? '1' : '0');
            update_option('ji_affiliate_cache_duration', intval($_POST['cache_duration']));
            
            echo '<div class="notice notice-success"><p>設定を保存しました。</p></div>';
        }
        
        $tracking_enabled = get_option('ji_affiliate_tracking_enabled', '1');
        $auto_optimize = get_option('ji_affiliate_auto_optimize', '0');
        $cache_duration = get_option('ji_affiliate_cache_duration', '3600');
        
        ?>
        <div class="wrap ji-affiliate-admin">
            <h1>アフィリエイト広告設定</h1>
            <hr class="wp-header-end">
            
            <form method="post" action="">
                <?php wp_nonce_field('ji_affiliate_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">統計追跡を有効化</th>
                        <td>
                            <label>
                                <input type="checkbox" name="tracking_enabled" value="1" <?php checked($tracking_enabled, '1'); ?>>
                                広告の表示回数とクリック数を追跡する
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">自動最適化</th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_optimize" value="1" <?php checked($auto_optimize, '1'); ?>>
                                CTRに基づいて広告を自動的に最適化する
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">キャッシュ時間（秒）</th>
                        <td>
                            <input type="number" name="cache_duration" value="<?php echo esc_attr($cache_duration); ?>" min="0" max="86400" class="small-text">
                            <p class="description">0=キャッシュなし、推奨: 3600</p>
                        </td>
                    </tr>
                </table>
                
                <div class="ji-form-section">
                    <h4>広告位置について</h4>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li><strong>サイドバー上部</strong>: サイドバーの最上部</li>
                        <li><strong>サイドバー中央</strong>: サイドバーの中央部</li>
                        <li><strong>サイドバー下部</strong>: サイドバーの最下部</li>
                        <li><strong>コンテンツ上部</strong>: 記事タイトルの直後</li>
                        <li><strong>コンテンツ中央</strong>: 記事本文の途中</li>
                        <li><strong>コンテンツ下部</strong>: 記事本文の直後</li>
                    </ul>
                </div>
                
                <p class="submit">
                    <input type="submit" name="ji_save_settings" class="button button-primary" value="設定を保存">
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * AJAX: 広告保存
     */
    public function ajax_save_ad() {
        check_ajax_referer('ji_ad_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        global $wpdb;
        
        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        
        // 複数位置
        $positions = isset($_POST['positions']) && is_array($_POST['positions']) 
            ? $_POST['positions'] 
            : (isset($_POST['position']) ? array($_POST['position']) : array());
        $positions_string = implode(',', array_map('sanitize_text_field', $positions));
        
        // 対象ページ
        $target_pages = isset($_POST['target_pages']) && is_array($_POST['target_pages']) 
            ? array_filter($_POST['target_pages'], function($page) { return !empty($page); })
            : array();
        $target_pages_string = implode(',', array_map('sanitize_text_field', $target_pages));
        
        // 対象カテゴリー
        $target_categories = isset($_POST['target_categories']) && is_array($_POST['target_categories']) 
            ? array_filter($_POST['target_categories'], function($cat) { return !empty($cat); })
            : array();
        $target_categories_string = implode(',', array_map('sanitize_text_field', $target_categories));
        
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'ad_type' => sanitize_text_field($_POST['ad_type']),
            'content' => wp_kses_post($_POST['content']),
            'link_url' => esc_url_raw($_POST['link_url']),
            'positions' => $positions_string,
            'target_pages' => $target_pages_string,
            'target_categories' => $target_categories_string,
            'device_target' => sanitize_text_field($_POST['device_target']),
            'status' => sanitize_text_field($_POST['status']),
            'priority' => intval($_POST['priority']),
            'start_date' => !empty($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null,
            'end_date' => !empty($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null,
        );
        
        if ($ad_id > 0) {
            $result = $wpdb->update($this->table_name_ads, $data, array('id' => $ad_id));
        } else {
            $result = $wpdb->insert($this->table_name_ads, $data);
            $ad_id = $wpdb->insert_id;
        }
        
        if ($result === false) {
            wp_send_json_error('保存に失敗しました');
        }
        
        wp_send_json_success(array('message' => '保存しました', 'ad_id' => $ad_id));
    }
    
    /**
     * AJAX: 広告データ取得
     */
    public function ajax_get_ad() {
        check_ajax_referer('ji_ad_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        global $wpdb;
        
        $ad_id = intval($_POST['ad_id']);
        
        $ad = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name_ads} WHERE id = %d",
            $ad_id
        ));
        
        if (!$ad) {
            wp_send_json_error('広告が見つかりません');
        }
        
        $ad->positions_array = explode(',', $ad->positions);
        $ad->target_pages_array = !empty($ad->target_pages) ? explode(',', $ad->target_pages) : array();
        $ad->target_categories_array = !empty($ad->target_categories) ? explode(',', $ad->target_categories) : array();
        
        wp_send_json_success($ad);
    }
    
    /**
     * AJAX: 広告削除
     */
    public function ajax_delete_ad() {
        check_ajax_referer('ji_ad_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        global $wpdb;
        
        $ad_id = intval($_POST['ad_id']);
        
        $wpdb->delete($this->table_name_stats, array('ad_id' => $ad_id));
        $wpdb->delete($this->table_name_stats_detail, array('ad_id' => $ad_id));
        $result = $wpdb->delete($this->table_name_ads, array('id' => $ad_id));
        
        if ($result === false) {
            wp_send_json_error('削除に失敗しました');
        }
        
        wp_send_json_success('削除しました');
    }
    
    /**
     * AJAX: 広告統計取得
     */
    public function ajax_get_ad_stats() {
        check_ajax_referer('ji_ad_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        global $wpdb;
        
        $ad_id = intval($_POST['ad_id']);
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        
        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                date, impressions, clicks,
                CASE WHEN impressions > 0 THEN ROUND((clicks / impressions) * 100, 2) ELSE 0 END as ctr
            FROM {$this->table_name_stats}
            WHERE ad_id = %d AND date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            ORDER BY date ASC",
            $ad_id, $days
        ));
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: インプレッション記録
     */
    public function ajax_track_impression() {
        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        
        if ($ad_id <= 0) {
            wp_send_json_error('Invalid ad ID');
        }
        
        global $wpdb;
        $today = current_time('Y-m-d');
        
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$this->table_name_stats} (ad_id, date, impressions, clicks)
            VALUES (%d, %s, 1, 0)
            ON DUPLICATE KEY UPDATE impressions = impressions + 1",
            $ad_id, $today
        ));
        
        $this->track_detailed_event($ad_id, 'impression', $_POST);
        
        wp_send_json_success();
    }
    
    /**
     * AJAX: クリック記録
     */
    public function ajax_track_click() {
        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        
        if ($ad_id <= 0) {
            wp_send_json_error('Invalid ad ID');
        }
        
        global $wpdb;
        $today = current_time('Y-m-d');
        
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$this->table_name_stats} (ad_id, date, impressions, clicks)
            VALUES (%d, %s, 0, 1)
            ON DUPLICATE KEY UPDATE clicks = clicks + 1",
            $ad_id, $today
        ));
        
        $this->track_detailed_event($ad_id, 'click', $_POST);
        
        wp_send_json_success();
    }
    
    /**
     * 詳細イベントトラッキング
     */
    private function track_detailed_event($ad_id, $event_type, $data) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name_stats_detail,
            array(
                'ad_id' => $ad_id,
                'event_type' => $event_type,
                'page_url' => isset($data['page_url']) ? esc_url_raw($data['page_url']) : '',
                'page_title' => isset($data['page_title']) ? sanitize_text_field($data['page_title']) : '',
                'post_id' => isset($data['post_id']) ? intval($data['post_id']) : null,
                'category_id' => isset($data['category_id']) ? intval($data['category_id']) : null,
                'category_name' => isset($data['category_name']) ? sanitize_text_field($data['category_name']) : null,
                'position' => isset($data['position']) ? sanitize_text_field($data['position']) : null,
                'device' => $this->detect_device(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
                'ip_address' => $this->get_client_ip(),
                'referer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * クライアントIP取得
     */
    private function get_client_ip() {
        $ip = '';
        $keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        foreach ($keys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                break;
            }
        }
        return sanitize_text_field($ip);
    }
    
    /**
     * デバイス検出
     */
    private function detect_device() {
        return wp_is_mobile() ? 'mobile' : 'desktop';
    }
    
    /**
     * 指定位置の広告を取得
     */
    public function get_ad_for_position($position, $options = array()) {
        global $wpdb;
        
        $current_datetime = current_time('mysql');
        $device = $this->detect_device();
        
        $category_ids = isset($options['category_ids']) ? $options['category_ids'] : array();
        $page_type = isset($options['page_type']) ? $options['page_type'] : '';
        
        $base_query = "SELECT a.* 
            FROM {$this->table_name_ads} a
            WHERE FIND_IN_SET(%s, REPLACE(a.positions, ' ', '')) > 0
            AND a.status = 'active'
            AND (a.device_target = 'all' OR a.device_target = %s)
            AND (a.start_date IS NULL OR a.start_date <= %s)
            AND (a.end_date IS NULL OR a.end_date >= %s)";
        
        $prepare_args = array($position, $device, $current_datetime, $current_datetime);
        
        $filter_parts = array();
        
        // カテゴリーフィルター
        if (!empty($category_ids) && is_array($category_ids)) {
            foreach ($category_ids as $cat_id) {
                $filter_parts[] = "FIND_IN_SET(%s, REPLACE(a.target_categories, ' ', '')) > 0";
                $prepare_args[] = $cat_id;
            }
        }
        
        // ページタイプフィルター
        if (!empty($page_type)) {
            $filter_parts[] = "FIND_IN_SET(%s, REPLACE(a.target_pages, ' ', '')) > 0";
            $prepare_args[] = $page_type;
        }
        
        // フィルター条件なしの広告も含める
        $filter_parts[] = "(a.target_categories IS NULL OR a.target_categories = '') AND (a.target_pages IS NULL OR a.target_pages = '')";
        
        if (!empty($filter_parts)) {
            $base_query .= " AND (" . implode(' OR ', $filter_parts) . ")";
        }
        
        $base_query .= " ORDER BY a.priority DESC, RAND() LIMIT 1";
        
        $query = $wpdb->prepare($base_query, $prepare_args);
        
        return $wpdb->get_row($query);
    }
    
    /**
     * 広告HTML出力
     */
    public function render_ad($position, $options = array()) {
        $ad = $this->get_ad_for_position($position, $options);
        
        if (!$ad) {
            return '';
        }
        
        global $post;
        $page_url = is_object($post) ? get_permalink($post->ID) : '';
        $page_title = is_object($post) ? get_the_title($post->ID) : '';
        $post_id = is_object($post) ? $post->ID : 0;
        
        $category_ids = isset($options['category_ids']) ? $options['category_ids'] : array();
        $category_id = !empty($category_ids) ? $category_ids[0] : '';
        $category_name = '';
        
        if (!empty($category_id)) {
            if (strpos($category_id, 'grant_category_') === 0) {
                $term_id = str_replace('grant_category_', '', $category_id);
                $term = get_term($term_id, 'grant_category');
                $category_name = !is_wp_error($term) && $term ? $term->name : '';
            } elseif (strpos($category_id, 'column_category_') === 0) {
                $term_id = str_replace('column_category_', '', $category_id);
                $term = get_term($term_id, 'column_category');
                $category_name = !is_wp_error($term) && $term ? $term->name : '';
            } elseif (strpos($category_id, 'category_') === 0) {
                $term_id = str_replace('category_', '', $category_id);
                $category = get_category($term_id);
                $category_name = $category ? $category->name : '';
            }
        }
        
        ob_start();
        ?>
        <div class="ji-affiliate-ad" 
             data-ad-id="<?php echo esc_attr($ad->id); ?>"
             data-position="<?php echo esc_attr($position); ?>"
             data-page-url="<?php echo esc_attr($page_url); ?>"
             data-page-title="<?php echo esc_attr($page_title); ?>"
             data-post-id="<?php echo esc_attr($post_id); ?>"
             data-category-id="<?php echo esc_attr($category_id); ?>"
             data-category-name="<?php echo esc_attr($category_name); ?>">
            
            <?php if ($ad->ad_type === 'html'): ?>
                <?php echo $ad->content; ?>
            <?php elseif ($ad->ad_type === 'image'): ?>
                <a href="<?php echo esc_url($ad->link_url); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="ji-ad-link"
                   data-ad-id="<?php echo esc_attr($ad->id); ?>">
                    <?php echo wp_kses_post($ad->content); ?>
                </a>
            <?php elseif ($ad->ad_type === 'script'): ?>
                <?php echo $ad->content; ?>
            <?php endif; ?>
        </div>
        
        <script>
        (function() {
            var adContainer = document.querySelector('[data-ad-id="<?php echo intval($ad->id); ?>"][data-position="<?php echo esc_js($position); ?>"]');
            if (!adContainer) return;
            
            var trackingData = {
                ad_id: <?php echo intval($ad->id); ?>,
                position: adContainer.getAttribute('data-position'),
                page_url: adContainer.getAttribute('data-page-url'),
                page_title: adContainer.getAttribute('data-page-title'),
                post_id: adContainer.getAttribute('data-post-id'),
                category_id: adContainer.getAttribute('data-category-id'),
                category_name: adContainer.getAttribute('data-category-name')
            };
            
            // インプレッション追跡
            if (typeof jQuery !== 'undefined') {
                jQuery(document).ready(function($) {
                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', Object.assign({
                        action: 'ji_track_ad_impression'
                    }, trackingData));
                });
            }
            
            // クリック追跡
            adContainer.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', function() {
                    if (typeof jQuery !== 'undefined') {
                        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', Object.assign({
                            action: 'ji_track_ad_click'
                        }, trackingData));
                    }
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}

// インスタンス化
new JI_Affiliate_Ad_Manager();

/**
 * ヘルパー関数: 広告表示
 */
function ji_display_ad($position, $options = array()) {
    // 後方互換性
    if (is_string($options)) {
        $options = array('page_type' => $options);
    }
    
    // シングルページの場合、自動的にカテゴリーを取得
    if (is_single() && !isset($options['category_ids'])) {
        global $post;
        $category_ids = array();
        
        $post_type = get_post_type($post->ID);
        
        if ($post_type === 'grant') {
            $grant_categories = wp_get_post_terms($post->ID, 'grant_category');
            if (!empty($grant_categories) && !is_wp_error($grant_categories)) {
                foreach ($grant_categories as $category) {
                    $category_ids[] = 'grant_category_' . $category->term_id;
                }
            }
        } elseif ($post_type === 'column') {
            $column_categories = wp_get_post_terms($post->ID, 'column_category');
            if (!empty($column_categories) && !is_wp_error($column_categories)) {
                foreach ($column_categories as $category) {
                    $category_ids[] = 'column_category_' . $category->term_id;
                }
            }
        } else {
            $categories = get_the_category($post->ID);
            if (!empty($categories)) {
                foreach ($categories as $category) {
                    $category_ids[] = 'category_' . $category->term_id;
                }
            }
        }
        
        $options['category_ids'] = $category_ids;
    }
    
    global $wpdb;
    $manager = new JI_Affiliate_Ad_Manager();
    echo $manager->render_ad($position, $options);
}