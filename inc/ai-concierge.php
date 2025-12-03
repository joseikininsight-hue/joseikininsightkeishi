<?php
/**
 * AI補助金コンシェルジュ - Complete Edition
 * 
 * 完全修正版 v7.0.0 - 全機能統合・UX最適化
 * 
 * @package GrantInsight
 * @version 7.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (defined('GIP_LOADED')) {
    return;
}
define('GIP_LOADED', true);

define('GIP_VERSION', '7.0.0');
define('GIP_API_NS', 'gip/v1');
define('GIP_PREFIX', 'gip_');

// =============================================================================
// エラーログ関数
// =============================================================================

function gip_log($message, $data = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_message = '[GIP v' . GIP_VERSION . '] ' . $message;
        if ($data !== null) {
            $log_message .= ' | Data: ' . print_r($data, true);
        }
        error_log($log_message);
    }
}

// =============================================================================
// テーブル操作
// =============================================================================

function gip_table($name) {
    global $wpdb;
    return $wpdb->prefix . GIP_PREFIX . $name;
}

function gip_table_exists($table_name) {
    global $wpdb;
    $table = gip_table($table_name);
    return $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;
}

// =============================================================================
// 都道府県・市区町村データ
// =============================================================================

function gip_get_prefectures() {
    return array(
        '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
        '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
        '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
        '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
        '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
        '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
        '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
    );
}

function gip_get_municipalities_from_taxonomy($prefecture_name) {
    global $wpdb;
    
    if (empty($prefecture_name)) {
        return array();
    }
    
    $municipalities = array();
    
    $pref_term = get_term_by('name', $prefecture_name, 'grant_prefecture');
    if (!$pref_term) {
        $pref_term = get_term_by('slug', sanitize_title($prefecture_name), 'grant_prefecture');
    }
    
    if ($pref_term) {
        $grant_ids = get_posts(array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'grant_prefecture',
                    'field' => 'term_id',
                    'terms' => $pref_term->term_id,
                ),
            ),
        ));
        
        if (!empty($grant_ids)) {
            foreach ($grant_ids as $grant_id) {
                $muni_terms = wp_get_post_terms($grant_id, 'grant_municipality');
                if (!is_wp_error($muni_terms) && !empty($muni_terms)) {
                    foreach ($muni_terms as $muni) {
                        if (!isset($municipalities[$muni->term_id])) {
                            $municipalities[$muni->term_id] = array(
                                'name' => $muni->name,
                                'slug' => $muni->slug,
                                'count' => $muni->count,
                            );
                        }
                    }
                }
            }
        }
    }
    
    if (empty($municipalities)) {
        $all_munis = get_terms(array(
            'taxonomy' => 'grant_municipality',
            'hide_empty' => true,
            'number' => 200,
        ));
        
        if (!is_wp_error($all_munis)) {
            foreach ($all_munis as $muni) {
                $parent_pref = get_term_meta($muni->term_id, 'parent_prefecture', true);
                if ($parent_pref === $prefecture_name || strpos($muni->description, $prefecture_name) !== false) {
                    $municipalities[$muni->term_id] = array(
                        'name' => $muni->name,
                        'slug' => $muni->slug,
                        'count' => $muni->count,
                    );
                }
            }
        }
    }
    
    usort($municipalities, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    return array_values($municipalities);
}

// =============================================================================
// 初期化フック
// =============================================================================

add_action('init', 'gip_init');
add_action('admin_menu', 'gip_admin_menu');
add_action('admin_init', 'gip_admin_init');
add_action('admin_enqueue_scripts', 'gip_admin_assets');
add_action('wp_enqueue_scripts', 'gip_frontend_assets');
add_action('rest_api_init', 'gip_rest_routes');

add_action('wp_ajax_gip_test_api', 'gip_ajax_test_api');
add_action('wp_ajax_gip_build_index', 'gip_ajax_build_index');
add_action('wp_ajax_gip_get_municipalities', 'gip_ajax_get_municipalities');
add_action('wp_ajax_nopriv_gip_get_municipalities', 'gip_ajax_get_municipalities');

add_action('save_post_grant', 'gip_on_save_grant', 20, 2);

function gip_init() {
    $defaults = array(
        'gip_api_key' => '',
        'gip_model' => 'gemini-1.5-flash',
        'gip_max_results' => 30,
        'gip_welcome_message' => '',
        'gip_system_prompt' => '',
    );
    
    foreach ($defaults as $k => $v) {
        if (get_option($k) === false) {
            update_option($k, $v);
        }
    }
}

function gip_admin_init() {
    if (get_option('gip_db_version') !== GIP_VERSION) {
        gip_create_tables();
    }
}

// =============================================================================
// データベーステーブル作成
// =============================================================================

function gip_create_tables() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    $charset = $wpdb->get_charset_collate();
    
    dbDelta("CREATE TABLE " . gip_table('sessions') . " (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        session_id varchar(64) NOT NULL,
        status varchar(20) DEFAULT 'active',
        context longtext,
        user_agent varchar(255),
        ip_address varchar(45),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY session_id (session_id),
        KEY created_at (created_at)
    ) $charset;");
    
    dbDelta("CREATE TABLE " . gip_table('messages') . " (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        session_id varchar(64) NOT NULL,
        role varchar(20) NOT NULL,
        content text NOT NULL,
        metadata longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY session_id (session_id),
        KEY created_at (created_at)
    ) $charset;");
    
    dbDelta("CREATE TABLE " . gip_table('results') . " (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        session_id varchar(64) NOT NULL,
        grant_id bigint(20) unsigned NOT NULL,
        score int(3) DEFAULT 0,
        reason text,
        feedback varchar(20),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY session_id (session_id),
        KEY grant_id (grant_id)
    ) $charset;");
    
    dbDelta("CREATE TABLE " . gip_table('vectors') . " (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        grant_id bigint(20) unsigned NOT NULL,
        content_hash varchar(32) NOT NULL,
        content text NOT NULL,
        embedding longtext NOT NULL,
        metadata longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY grant_id (grant_id),
        KEY content_hash (content_hash)
    ) $charset;");
    
    update_option('gip_db_version', GIP_VERSION);
    gip_log('Tables created/updated for version ' . GIP_VERSION);
}

// =============================================================================
// 管理画面
// =============================================================================

function gip_admin_menu() {
    add_menu_page('AI補助金診断', 'AI補助金診断', 'manage_options', 'gip-admin', 'gip_page_dashboard', 'dashicons-format-chat', 30);
    add_submenu_page('gip-admin', 'ダッシュボード', 'ダッシュボード', 'manage_options', 'gip-admin', 'gip_page_dashboard');
    add_submenu_page('gip-admin', 'インデックス', 'インデックス', 'manage_options', 'gip-index', 'gip_page_index');
    add_submenu_page('gip-admin', '設定', '設定', 'manage_options', 'gip-settings', 'gip_page_settings');
}

function gip_admin_assets($hook) {
    if (strpos($hook, 'gip-') === false && $hook !== 'toplevel_page_gip-admin') {
        return;
    }
    
    wp_enqueue_style('gip-admin-style', false);
    wp_add_inline_style('gip-admin-style', gip_admin_css());
    
    wp_enqueue_script('jquery');
    wp_register_script('gip-admin-script', false, array('jquery'), GIP_VERSION, true);
    wp_enqueue_script('gip-admin-script');
    
    wp_localize_script('gip-admin-script', 'GIP', array(
        'ajax' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gip_nonce'),
    ));
    
    wp_add_inline_script('gip-admin-script', gip_admin_js());
}

function gip_admin_css() {
    return '
.gip-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #111; }
.gip-wrap * { box-sizing: border-box; }
.gip-header { background: #000; color: #fff; padding: 24px 32px; margin: -20px -20px 32px -20px; }
.gip-header h1 { font-size: 20px; font-weight: 600; margin: 0 0 4px 0; }
.gip-header p { font-size: 13px; opacity: 0.7; margin: 0; }
.gip-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 32px; }
.gip-stat { background: #fff; border: 1px solid #e5e5e5; padding: 24px; }
.gip-stat-label { font-size: 12px; color: #666; margin-bottom: 8px; }
.gip-stat-value { font-size: 32px; font-weight: 600; line-height: 1; }
.gip-stat-sub { font-size: 12px; color: #666; margin-top: 8px; }
.gip-card { background: #fff; border: 1px solid #e5e5e5; margin-bottom: 24px; }
.gip-card-header { padding: 20px 24px; border-bottom: 1px solid #e5e5e5; }
.gip-card-title { font-size: 14px; font-weight: 600; margin: 0; }
.gip-card-body { padding: 24px; }
.gip-grid { display: grid; gap: 24px; }
.gip-grid-2 { grid-template-columns: 1fr 1fr; }
.gip-field { margin-bottom: 24px; }
.gip-label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 8px; }
.gip-input { width: 100%; padding: 10px 14px; border: 1px solid #ddd; font-size: 14px; }
.gip-input:focus { outline: none; border-color: #000; }
.gip-input-sm { max-width: 120px; }
.gip-input-md { max-width: 300px; }
.gip-textarea { min-height: 100px; resize: vertical; }
.gip-help { font-size: 12px; color: #666; margin-top: 6px; }
.gip-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-size: 13px; font-weight: 500; border: 1px solid transparent; cursor: pointer; }
.gip-btn-primary { background: #000; color: #fff; border-color: #000; }
.gip-btn-primary:hover { background: #333; }
.gip-btn-secondary { background: #fff; color: #000; border-color: #ddd; }
.gip-btn-secondary:hover { background: #f5f5f5; }
.gip-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.gip-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; font-size: 12px; font-weight: 500; }
.gip-badge-success { background: #f0fdf4; color: #166534; }
.gip-badge-warning { background: #fffbeb; color: #92400e; }
.gip-badge-error { background: #fef2f2; color: #991b1b; }
.gip-badge-info { background: #f5f5f5; color: #333; }
.gip-progress { height: 8px; background: #e5e5e5; overflow: hidden; }
.gip-progress-bar { height: 100%; background: #000; transition: width 0.3s; }
.gip-table { width: 100%; border-collapse: collapse; }
.gip-table th, .gip-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e5e5e5; font-size: 13px; }
.gip-table th { font-weight: 500; color: #666; background: #fafafa; }
.gip-index-stat { text-align: center; padding: 32px; background: #fafafa; }
.gip-index-stat-value { font-size: 48px; font-weight: 700; line-height: 1; }
.gip-index-stat-label { font-size: 13px; color: #666; margin-top: 8px; }
.gip-alert { padding: 16px 20px; border: 1px solid; margin-bottom: 20px; }
.gip-alert-success { background: #f0fdf4; border-color: #86efac; color: #166534; }
.gip-alert-error { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
.gip-loading { display: inline-flex; align-items: center; gap: 8px; color: #666; }
.gip-spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid #ddd; border-top-color: #000; border-radius: 50%; animation: gip-spin 1s linear infinite; }
@keyframes gip-spin { to { transform: rotate(360deg); } }
@media (max-width: 1200px) { .gip-grid-2 { grid-template-columns: 1fr; } .gip-stats { grid-template-columns: repeat(2, 1fr); } }
';
}

function gip_admin_js() {
    return "
jQuery(document).ready(function($) {
    $('#gip-test-api').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var result = $('#gip-test-result');
        btn.prop('disabled', true);
        result.html('<span class=\"gip-loading\"><span class=\"gip-spinner\"></span> テスト中...</span>');
        $.ajax({
            url: GIP.ajax, type: 'POST',
            data: { action: 'gip_test_api', nonce: GIP.nonce },
            success: function(r) {
                result.html(r.success ? '<span class=\"gip-badge gip-badge-success\">接続成功</span>' : '<span class=\"gip-badge gip-badge-error\">' + (r.data || 'エラー') + '</span>');
            },
            error: function() { result.html('<span class=\"gip-badge gip-badge-error\">通信エラー</span>'); },
            complete: function() { btn.prop('disabled', false); }
        });
    });
    
    $('#gip-build-index').on('click', function(e) {
        e.preventDefault();
        if (!confirm('インデックスを構築しますか?')) return;
        var btn = $(this);
        var progress = $('#gip-build-progress');
        var status = $('#gip-build-status');
        var bar = progress.find('.gip-progress-bar');
        btn.prop('disabled', true);
        progress.show();
        status.text('開始中...');
        
        function buildBatch(offset) {
            $.ajax({
                url: GIP.ajax, type: 'POST',
                data: { action: 'gip_build_index', nonce: GIP.nonce, offset: offset },
                success: function(r) {
                    if (r.success) {
                        var pct = Math.round(r.data.done / r.data.total * 100);
                        bar.css('width', pct + '%');
                        status.text(r.data.done + ' / ' + r.data.total + ' 件完了');
                        if (r.data.more) {
                            setTimeout(function() { buildBatch(r.data.next); }, 300);
                        } else {
                            status.text('完了しました');
                            btn.prop('disabled', false);
                            setTimeout(function() { location.reload(); }, 1500);
                        }
                    } else {
                        status.text('エラー: ' + (r.data || '不明'));
                        btn.prop('disabled', false);
                    }
                },
                error: function() { status.text('通信エラー'); btn.prop('disabled', false); }
            });
        }
        buildBatch(0);
    });
});
";
}

// =============================================================================
// 管理画面ページ
// =============================================================================

function gip_page_dashboard() {
    $stats = gip_get_stats();
    ?>
    <div class="wrap gip-wrap">
        <div class="gip-header">
            <h1>AI補助金コンシェルジュ v<?php echo GIP_VERSION; ?></h1>
            <p>自然言語対話形式 - BtoB/BtoC両対応 - タクソノミー連動</p>
        </div>
        
        <div class="gip-stats">
            <div class="gip-stat">
                <div class="gip-stat-label">補助金データ</div>
                <div class="gip-stat-value"><?php echo number_format($stats['grants']); ?></div>
                <div class="gip-stat-sub">件登録</div>
            </div>
            <div class="gip-stat">
                <div class="gip-stat-label">インデックス</div>
                <div class="gip-stat-value"><?php echo number_format($stats['vectors']); ?></div>
                <div class="gip-stat-sub"><?php echo $stats['grants'] > 0 ? round($stats['vectors'] / $stats['grants'] * 100) : 0; ?>% 完了</div>
            </div>
            <div class="gip-stat">
                <div class="gip-stat-label">診断セッション</div>
                <div class="gip-stat-value"><?php echo number_format($stats['sessions']); ?></div>
                <div class="gip-stat-sub">累計</div>
            </div>
            <div class="gip-stat">
                <div class="gip-stat-label">満足度</div>
                <div class="gip-stat-value"><?php echo $stats['satisfaction']; ?>%</div>
                <div class="gip-stat-sub">ポジティブ評価</div>
            </div>
        </div>
        
        <div class="gip-grid gip-grid-2">
            <div class="gip-card">
                <div class="gip-card-header"><h2 class="gip-card-title">システム状態</h2></div>
                <div class="gip-card-body">
                    <table class="gip-table">
                        <tr><td>API接続</td><td><?php echo !empty(get_option('gip_api_key')) ? '<span class="gip-badge gip-badge-success">設定済み</span>' : '<span class="gip-badge gip-badge-error">未設定</span>'; ?></td></tr>
                        <tr><td>インデックス</td><td><?php 
                            if ($stats['vectors'] >= $stats['grants'] && $stats['grants'] > 0) echo '<span class="gip-badge gip-badge-success">完了</span>';
                            elseif ($stats['vectors'] > 0) echo '<span class="gip-badge gip-badge-warning">一部完了</span>';
                            else echo '<span class="gip-badge gip-badge-error">未構築</span>';
                        ?></td></tr>
                        <tr><td>最終更新</td><td><?php echo esc_html(get_option('gip_last_index', '-')); ?></td></tr>
                    </table>
                </div>
            </div>
            
            <div class="gip-card">
                <div class="gip-card-header"><h2 class="gip-card-title">最近のセッション</h2></div>
                <div class="gip-card-body">
                    <?php
                    global $wpdb;
                    $table = gip_table('sessions');
                    $recent = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") ? $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 5") : array();
                    if (empty($recent)): ?>
                        <p style="color: #666; text-align: center; padding: 20px;">データなし</p>
                    <?php else: ?>
                        <table class="gip-table">
                            <thead><tr><th>ID</th><th>状態</th><th>日時</th></tr></thead>
                            <tbody>
                                <?php foreach ($recent as $s): ?>
                                <tr>
                                    <td><code><?php echo esc_html(substr($s->session_id, 0, 8)); ?></code></td>
                                    <td><?php echo $s->status === 'completed' ? '<span class="gip-badge gip-badge-success">完了</span>' : '<span class="gip-badge gip-badge-info">進行中</span>'; ?></td>
                                    <td><?php echo date('m/d H:i', strtotime($s->created_at)); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function gip_page_index() {
    $stats = gip_get_stats();
    $pct = $stats['grants'] > 0 ? round($stats['vectors'] / $stats['grants'] * 100) : 0;
    ?>
    <div class="wrap gip-wrap">
        <div class="gip-header">
            <h1>インデックス管理</h1>
            <p>RAG用ベクトルインデックスの構築と管理</p>
        </div>
        
        <div class="gip-stats" style="grid-template-columns: repeat(3, 1fr);">
            <div class="gip-card"><div class="gip-card-body gip-index-stat"><div class="gip-index-stat-value"><?php echo number_format($stats['grants']); ?></div><div class="gip-index-stat-label">登録補助金</div></div></div>
            <div class="gip-card"><div class="gip-card-body gip-index-stat"><div class="gip-index-stat-value"><?php echo number_format($stats['vectors']); ?></div><div class="gip-index-stat-label">インデックス済み</div></div></div>
            <div class="gip-card"><div class="gip-card-body gip-index-stat"><div class="gip-index-stat-value"><?php echo $pct; ?>%</div><div class="gip-index-stat-label">完了率</div></div></div>
        </div>
        
        <div class="gip-card">
            <div class="gip-card-header"><h2 class="gip-card-title">インデックス構築</h2></div>
            <div class="gip-card-body">
                <?php if (empty(get_option('gip_api_key'))): ?>
                    <div class="gip-alert gip-alert-error">APIキーが設定されていません。</div>
                <?php elseif ($pct >= 100): ?>
                    <div class="gip-alert gip-alert-success">すべての補助金がインデックスされています。</div>
                <?php else: ?>
                    <div class="gip-alert" style="background:#fffbeb;border-color:#fde68a;color:#92400e;"><?php echo number_format($stats['grants'] - $stats['vectors']); ?>件の補助金がインデックスされていません。</div>
                <?php endif; ?>
                
                <div id="gip-build-progress" style="display: none; margin-bottom: 20px;">
                    <div class="gip-progress"><div class="gip-progress-bar" style="width: 0%"></div></div>
                    <div id="gip-build-status" style="font-size: 13px; color: #666; margin-top: 8px;">0 / 0</div>
                </div>
                
                <button type="button" id="gip-build-index" class="gip-btn gip-btn-primary" <?php echo empty(get_option('gip_api_key')) ? 'disabled' : ''; ?>>インデックス構築</button>
            </div>
        </div>
    </div>
    <?php
}

function gip_page_settings() {
    if (isset($_POST['gip_save']) && check_admin_referer('gip_settings')) {
        update_option('gip_api_key', sanitize_text_field($_POST['gip_api_key'] ?? ''));
        update_option('gip_model', sanitize_text_field($_POST['gip_model'] ?? 'gemini-1.5-flash'));
        update_option('gip_max_results', absint($_POST['gip_max_results'] ?? 30));
        update_option('gip_welcome_message', sanitize_textarea_field($_POST['gip_welcome_message'] ?? ''));
        update_option('gip_system_prompt', sanitize_textarea_field($_POST['gip_system_prompt'] ?? ''));
        echo '<div class="notice notice-success"><p>設定を保存しました。</p></div>';
    }
    ?>
    <div class="wrap gip-wrap">
        <div class="gip-header">
            <h1>設定</h1>
            <p>APIと診断システムの設定</p>
        </div>
        
        <form method="post">
            <?php wp_nonce_field('gip_settings'); ?>
            
            <div class="gip-card">
                <div class="gip-card-header"><h2 class="gip-card-title">API設定</h2></div>
                <div class="gip-card-body">
                    <div class="gip-field">
                        <label class="gip-label">Gemini API Key</label>
                        <input type="password" name="gip_api_key" value="<?php echo esc_attr(get_option('gip_api_key', '')); ?>" class="gip-input gip-input-md">
                        <div style="margin-top: 12px;">
                            <button type="button" id="gip-test-api" class="gip-btn gip-btn-secondary">接続テスト</button>
                            <span id="gip-test-result" style="margin-left: 12px;"></span>
                        </div>
                    </div>
                    <div class="gip-field">
                        <label class="gip-label">モデル</label>
                        <select name="gip_model" class="gip-input gip-input-md">
                            <option value="gemini-1.5-flash" <?php selected(get_option('gip_model'), 'gemini-1.5-flash'); ?>>gemini-1.5-flash (推奨)</option>
                            <option value="gemini-1.5-pro" <?php selected(get_option('gip_model'), 'gemini-1.5-pro'); ?>>gemini-1.5-pro</option>
                            <option value="gemini-2.0-flash-exp" <?php selected(get_option('gip_model'), 'gemini-2.0-flash-exp'); ?>>gemini-2.0-flash-exp</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="gip-card">
                <div class="gip-card-header"><h2 class="gip-card-title">チャット設定</h2></div>
                <div class="gip-card-body">
                    <div class="gip-field">
                        <label class="gip-label">最大結果表示件数</label>
                        <input type="number" name="gip_max_results" value="<?php echo esc_attr(get_option('gip_max_results', 30)); ?>" min="5" max="100" class="gip-input gip-input-sm">
                    </div>
                    <div class="gip-field">
                        <label class="gip-label">システムプロンプト（上級者向け）</label>
                        <textarea name="gip_system_prompt" class="gip-input gip-textarea"><?php echo esc_textarea(get_option('gip_system_prompt', '')); ?></textarea>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="gip_save" class="gip-btn gip-btn-primary">設定を保存</button>
        </form>
    </div>
    <?php
}

// =============================================================================
// 統計取得
// =============================================================================

function gip_get_stats() {
    global $wpdb;
    
    $grants = wp_count_posts('grant');
    $grants_count = isset($grants->publish) ? (int)$grants->publish : 0;
    
    $vectors = 0;
    $sessions = 0;
    $positive = 0;
    $total_feedback = 0;
    
    $vectors_table = gip_table('vectors');
    $sessions_table = gip_table('sessions');
    $results_table = gip_table('results');
    
    if ($wpdb->get_var("SHOW TABLES LIKE '{$vectors_table}'")) {
        $vectors = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$vectors_table}");
    }
    
    if ($wpdb->get_var("SHOW TABLES LIKE '{$sessions_table}'")) {
        $sessions = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$sessions_table}");
    }
    
    if ($wpdb->get_var("SHOW TABLES LIKE '{$results_table}'")) {
        $positive = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$results_table} WHERE feedback = 'positive'");
        $total_feedback = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$results_table} WHERE feedback IS NOT NULL");
    }
    
    return array(
        'grants' => $grants_count,
        'vectors' => $vectors,
        'sessions' => $sessions,
        'satisfaction' => $total_feedback > 0 ? round($positive / $total_feedback * 100) : 0,
    );
}

// =============================================================================
// Ajax Handlers
// =============================================================================

function gip_ajax_test_api() {
    check_ajax_referer('gip_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('権限がありません');
    
    $key = get_option('gip_api_key', '');
    if (empty($key)) wp_send_json_error('APIキー未設定');
    
    $result = gip_call_gemini('Hello', array('max_tokens' => 10));
    if (is_wp_error($result)) wp_send_json_error($result->get_error_message());
    
    wp_send_json_success();
}

function gip_ajax_build_index() {
    check_ajax_referer('gip_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('権限がありません');
    
    global $wpdb;
    $table = gip_table('vectors');
    if (!$wpdb->get_var("SHOW TABLES LIKE '{$table}'")) {
        gip_create_tables();
    }
    
    set_time_limit(120);
    
    $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
    $batch = 10;
    
    $grants = get_posts(array(
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => $batch,
        'offset' => $offset,
        'orderby' => 'ID',
        'order' => 'ASC',
        'fields' => 'ids',
    ));
    
    $total = wp_count_posts('grant');
    $total = isset($total->publish) ? (int)$total->publish : 0;
    
    if ($total === 0) wp_send_json_error('補助金データがありません');
    
    foreach ($grants as $id) {
        if (!gip_has_vector($id)) {
            gip_create_vector($id);
            usleep(200000);
        }
    }
    
    $done = $offset + count($grants);
    
    if ($done >= $total) {
        update_option('gip_last_index', current_time('Y/m/d H:i'));
    }
    
    wp_send_json_success(array(
        'done' => $done,
        'total' => $total,
        'more' => $done < $total,
        'next' => $offset + $batch,
    ));
}

function gip_ajax_get_municipalities() {
    $prefecture = isset($_POST['prefecture']) ? sanitize_text_field($_POST['prefecture']) : '';
    
    if (empty($prefecture)) {
        wp_send_json_error('都道府県が指定されていません');
    }
    
    $municipalities = gip_get_municipalities_from_taxonomy($prefecture);
    
    wp_send_json_success(array(
        'prefecture' => $prefecture,
        'municipalities' => $municipalities,
        'count' => count($municipalities),
    ));
}

// =============================================================================
// Gemini API
// =============================================================================

function gip_call_gemini($prompt, $options = array()) {
    $key = get_option('gip_api_key', '');
    $model = isset($options['model']) ? $options['model'] : get_option('gip_model', 'gemini-1.5-flash');
    
    if (empty($key)) return new WP_Error('no_key', 'APIキー未設定');
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
    
    $body = array(
        'contents' => array(array('parts' => array(array('text' => $prompt)))),
        'generationConfig' => array(
            'temperature' => isset($options['temperature']) ? $options['temperature'] : 0.7,
            'maxOutputTokens' => isset($options['max_tokens']) ? $options['max_tokens'] : 2048,
        ),
    );
    
    $response = wp_remote_post($url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => wp_json_encode($body),
        'timeout' => 60,
    ));
    
    if (is_wp_error($response)) return $response;
    
    $code = wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($code !== 200) {
        return new WP_Error('api_error', $data['error']['message'] ?? 'API Error');
    }
    
    return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
}

function gip_get_embedding($text) {
    $key = get_option('gip_api_key', '');
    if (empty($key)) return new WP_Error('no_key', 'APIキー未設定');
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent?key={$key}";
    
    $body = array(
        'model' => 'models/text-embedding-004',
        'content' => array('parts' => array(array('text' => mb_substr($text, 0, 8000)))),
    );
    
    $response = wp_remote_post($url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => wp_json_encode($body),
        'timeout' => 30,
    ));
    
    if (is_wp_error($response)) return $response;
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    return $data['embedding']['values'] ?? new WP_Error('no_embedding', 'Embedding failed');
}

// =============================================================================
// Vector Operations
// =============================================================================

function gip_has_vector($grant_id) {
    global $wpdb;
    $table = gip_table('vectors');
    if (!$wpdb->get_var("SHOW TABLES LIKE '{$table}'")) return false;
    return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE grant_id = %d", $grant_id)) > 0;
}

function gip_create_vector($grant_id) {
    global $wpdb;
    
    $text = gip_extract_grant_text($grant_id);
    if (empty($text)) return false;
    
    $embedding = gip_get_embedding($text);
    if (is_wp_error($embedding)) return false;
    
    $metadata = gip_extract_grant_metadata($grant_id);
    
    $result = $wpdb->replace(gip_table('vectors'), array(
        'grant_id' => $grant_id,
        'content_hash' => md5($text),
        'content' => $text,
        'embedding' => wp_json_encode($embedding),
        'metadata' => wp_json_encode($metadata),
    ));
    
    return $result !== false;
}

function gip_extract_grant_text($id) {
    $post = get_post($id);
    if (!$post) return '';
    
    $parts = array();
    $parts[] = $post->post_title;
    $parts[] = wp_strip_all_tags($post->post_content);
    
    if (function_exists('get_field')) {
        $acf_fields = array(
            'organization', 'grant_target', 'eligible_expenses', 'max_amount',
            'subsidy_rate', 'deadline', 'application_period', 'required_documents',
            'application_method', 'ai_summary',
        );
        
        foreach ($acf_fields as $field) {
            $value = get_field($field, $id);
            if (!empty($value)) {
                if (is_array($value)) $value = implode(' ', $value);
                $parts[] = wp_strip_all_tags($value);
            }
        }
    }
    
    $taxonomies = array('grant_category', 'grant_prefecture', 'grant_municipality', 'grant_industry', 'grant_purpose');
    foreach ($taxonomies as $tax) {
        $terms = get_the_terms($id, $tax);
        if ($terms && !is_wp_error($terms)) {
            $parts[] = implode(' ', wp_list_pluck($terms, 'name'));
        }
    }
    
    return implode("\n", array_filter($parts));
}

function gip_extract_grant_metadata($id) {
    $metadata = array(
        'prefectures' => array(),
        'municipalities' => array(),
        'categories' => array(),
        'industries' => array(),
        'purposes' => array(),
        'is_national' => false,
        'target_types' => array(),
        'max_amount_numeric' => 0,
        'application_status' => 'open',
    );
    
    $pref_terms = get_the_terms($id, 'grant_prefecture');
    if ($pref_terms && !is_wp_error($pref_terms)) {
        foreach ($pref_terms as $term) {
            $metadata['prefectures'][] = $term->name;
            if (in_array($term->name, array('全国', '国')) || in_array($term->slug, array('all', 'national', 'zenkoku'))) {
                $metadata['is_national'] = true;
            }
        }
    }
    
    $muni_terms = get_the_terms($id, 'grant_municipality');
    if ($muni_terms && !is_wp_error($muni_terms)) {
        $metadata['municipalities'] = wp_list_pluck($muni_terms, 'name');
    }
    
    $cat_terms = get_the_terms($id, 'grant_category');
    if ($cat_terms && !is_wp_error($cat_terms)) {
        $metadata['categories'] = wp_list_pluck($cat_terms, 'name');
    }
    
    $ind_terms = get_the_terms($id, 'grant_industry');
    if ($ind_terms && !is_wp_error($ind_terms)) {
        $metadata['industries'] = wp_list_pluck($ind_terms, 'name');
    }
    
    $purpose_terms = get_the_terms($id, 'grant_purpose');
    if ($purpose_terms && !is_wp_error($purpose_terms)) {
        $metadata['purposes'] = wp_list_pluck($purpose_terms, 'name');
    }
    
    if (function_exists('get_field')) {
        $metadata['max_amount_numeric'] = intval(get_field('max_amount_numeric', $id) ?: 0);
        $metadata['application_status'] = get_field('application_status', $id) ?: 'open';
        
        $target = get_field('grant_target', $id) ?: '';
        $target_lower = mb_strtolower($target);
        
        if (preg_match('/(個人|個人事業|フリーランス)/', $target_lower)) {
            $metadata['target_types'][] = 'individual';
        }
        if (preg_match('/(法人|企業|会社|株式会社)/', $target_lower)) {
            $metadata['target_types'][] = 'corporation';
        }
        if (preg_match('/(中小企業|小規模)/', $target_lower)) {
            $metadata['target_types'][] = 'small_business';
        }
        if (preg_match('/(創業|起業|スタートアップ)/', $target_lower)) {
            $metadata['target_types'][] = 'startup';
        }
        if (preg_match('/(NPO|非営利|社団|財団)/', $target_lower)) {
            $metadata['target_types'][] = 'npo';
        }
        if (preg_match('/(個人向け|一般|住民|市民|県民)/', $target_lower)) {
            $metadata['target_types'][] = 'personal';
        }
    }
    
    return $metadata;
}

function gip_search_vectors_with_filter($query, $filters = array(), $limit = 50) {
    global $wpdb;
    
    $query_embedding = gip_get_embedding($query);
    if (is_wp_error($query_embedding)) {
        return array();
    }
    
    $table = gip_table('vectors');
    if (!$wpdb->get_var("SHOW TABLES LIKE '{$table}'")) {
        return array();
    }
    
    $vectors = $wpdb->get_results("SELECT grant_id, embedding, metadata FROM {$table}");
    
    $scores = array();
    foreach ($vectors as $v) {
        $emb = json_decode($v->embedding, true);
        $meta = json_decode($v->metadata, true) ?: array();
        
        if (!is_array($emb)) continue;
        
        if (!empty($filters['user_type'])) {
            $target_types = $meta['target_types'] ?? array();
            $type_map = array(
                'corporation' => array('corporation', 'small_business'),
                'individual_business' => array('individual', 'small_business'),
                'personal' => array('personal', 'individual'),
                'startup' => array('startup', 'individual', 'corporation'),
                'npo' => array('npo'),
            );
            $allowed = $type_map[$filters['user_type']] ?? array();
            
            if (!empty($target_types) && !empty($allowed)) {
                $match = array_intersect($target_types, $allowed);
                if (empty($match) && !$meta['is_national']) {
                    continue;
                }
            }
        }
        
        if (!empty($filters['prefecture'])) {
            $grant_prefs = $meta['prefectures'] ?? array();
            $is_national = $meta['is_national'] ?? false;
            
            if (!$is_national && !in_array($filters['prefecture'], $grant_prefs)) {
                continue;
            }
        }
        
        if (!empty($filters['municipality']) && $filters['municipality'] !== '全域') {
            $grant_munis = $meta['municipalities'] ?? array();
            if (!empty($grant_munis) && !in_array($filters['municipality'], $grant_munis)) {
                continue;
            }
        }
        
        if (!empty($filters['status_open'])) {
            $status = $meta['application_status'] ?? 'open';
            if ($status !== 'open') {
                continue;
            }
        }
        
        $sim = gip_cosine_sim($query_embedding, $emb);
        if ($sim > 0.25) {
            $scores[$v->grant_id] = $sim;
        }
    }
    
    arsort($scores);
    return array_slice($scores, 0, $limit, true);
}

function gip_cosine_sim($a, $b) {
    if (count($a) !== count($b)) return 0;
    
    $dot = 0; $na = 0; $nb = 0;
    $count = count($a);
    for ($i = 0; $i < $count; $i++) {
        $dot += $a[$i] * $b[$i];
        $na += $a[$i] * $a[$i];
        $nb += $b[$i] * $b[$i];
    }
    
    $na = sqrt($na);
    $nb = sqrt($nb);
    
    return ($na && $nb) ? $dot / ($na * $nb) : 0;
}

function gip_on_save_grant($post_id, $post) {
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;
    if ($post->post_status !== 'publish') return;
    if (empty(get_option('gip_api_key'))) return;
    
    wp_schedule_single_event(time() + 5, 'gip_update_vector', array($post_id));
}

add_action('gip_update_vector', function($id) {
    gip_create_vector($id);
});

// =============================================================================
// REST API Routes
// =============================================================================

function gip_rest_routes() {
    register_rest_route(GIP_API_NS, '/chat', array(
        'methods' => 'POST',
        'callback' => 'gip_api_chat',
        'permission_callback' => '__return_true',
    ));
    
    register_rest_route(GIP_API_NS, '/feedback', array(
        'methods' => 'POST',
        'callback' => 'gip_api_feedback',
        'permission_callback' => '__return_true',
    ));
    
    register_rest_route(GIP_API_NS, '/municipalities', array(
        'methods' => 'GET',
        'callback' => 'gip_api_municipalities',
        'permission_callback' => '__return_true',
    ));
    
    register_rest_route(GIP_API_NS, '/grant/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'gip_api_grant_detail',
        'permission_callback' => '__return_true',
    ));
}

function gip_api_municipalities($request) {
    $prefecture = $request->get_param('prefecture');
    
    if (empty($prefecture)) {
        return new WP_REST_Response(array('success' => false, 'error' => '都道府県が指定されていません'), 400);
    }
    
    $municipalities = gip_get_municipalities_from_taxonomy($prefecture);
    
    return new WP_REST_Response(array(
        'success' => true,
        'prefecture' => $prefecture,
        'municipalities' => $municipalities,
        'count' => count($municipalities),
    ));
}

function gip_api_grant_detail($request) {
    $grant_id = intval($request->get_param('id'));
    
    if (!$grant_id) {
        return new WP_REST_Response(array('success' => false, 'error' => '補助金IDが指定されていません'), 400);
    }
    
    $post = get_post($grant_id);
    if (!$post || $post->post_type !== 'grant') {
        return new WP_REST_Response(array('success' => false, 'error' => '補助金が見つかりません'), 404);
    }
    
    $detail = gip_get_grant_full_detail($grant_id);
    
    return new WP_REST_Response(array(
        'success' => true,
        'data' => $detail,
    ));
}

function gip_get_grant_full_detail($id) {
    $post = get_post($id);
    if (!$post) return null;
    
    $detail = array(
        'id' => $id,
        'title' => $post->post_title,
        'content' => apply_filters('the_content', $post->post_content),
        'excerpt' => wp_trim_words(wp_strip_all_tags($post->post_content), 100),
        'url' => get_permalink($id),
    );
    
    if (function_exists('get_field')) {
        $acf_fields = array(
            'organization', 'max_amount', 'max_amount_numeric', 'subsidy_rate',
            'deadline', 'deadline_date', 'application_period', 'grant_target',
            'eligible_expenses', 'required_documents', 'application_method',
            'application_status', 'online_application', 'jgrants_available',
            'grant_difficulty', 'adoption_rate', 'ai_summary', 'official_url',
            'application_tips', 'contact_info',
        );
        
        foreach ($acf_fields as $field) {
            $value = get_field($field, $id);
            if ($value !== null && $value !== false) {
                $detail[$field] = $value;
            }
        }
    }
    
    $taxonomies = array(
        'categories' => 'grant_category',
        'prefectures' => 'grant_prefecture',
        'municipalities' => 'grant_municipality',
        'industries' => 'grant_industry',
        'purposes' => 'grant_purpose',
    );
    
    foreach ($taxonomies as $key => $tax) {
        $terms = get_the_terms($id, $tax);
        if ($terms && !is_wp_error($terms)) {
            $detail[$key] = array();
            foreach ($terms as $term) {
                $detail[$key][] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                );
            }
        }
    }
    
    $detail['amount_display'] = gip_format_amount_display($detail);
    $detail['deadline_display'] = gip_format_deadline_display($detail);
    
    return $detail;
}

function gip_format_amount_display($detail) {
    $max = isset($detail['max_amount_numeric']) ? intval($detail['max_amount_numeric']) : 0;
    
    if ($max <= 0 && !empty($detail['max_amount'])) {
        return $detail['max_amount'];
    }
    
    if ($max >= 100000000) return number_format($max / 100000000, 1) . '億円';
    if ($max >= 10000) return number_format($max / 10000) . '万円';
    if ($max > 0) return number_format($max) . '円';
    
    return '要確認';
}

function gip_format_deadline_display($detail) {
    if (!empty($detail['deadline_date'])) {
        $timestamp = strtotime($detail['deadline_date']);
        if ($timestamp) {
            $days = floor(($timestamp - current_time('timestamp')) / 86400);
            $date_str = date('Y年n月j日', $timestamp);
            if ($days > 0) {
                return $date_str . '（残り' . $days . '日）';
            } elseif ($days == 0) {
                return $date_str . '（本日締切）';
            } else {
                return $date_str . '（終了）';
            }
        }
    }
    
    return !empty($detail['deadline']) ? $detail['deadline'] : '随時';
}

// =============================================================================
// REST API: Chat - メイン処理
// =============================================================================

function gip_api_chat($request) {
    global $wpdb;
    
    $sessions_table = gip_table('sessions');
    if (!$wpdb->get_var("SHOW TABLES LIKE '{$sessions_table}'")) {
        gip_create_tables();
    }
    
    $params = $request->get_json_params();
    $session_id = sanitize_text_field($params['session_id'] ?? '');
    $message = sanitize_textarea_field($params['message'] ?? '');
    $selection = sanitize_text_field($params['selection'] ?? '');
    
    // 新規セッション
    if (empty($session_id)) {
        $session_id = wp_generate_uuid4();
        
        $wpdb->insert(gip_table('sessions'), array(
            'session_id' => $session_id,
            'status' => 'active',
            'context' => wp_json_encode(array(
                'step' => 'init',
                'understanding_level' => 0,
                'collected_info' => array(),
            )),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ));
        
        $welcome = "補助金・助成金コンシェルジュへようこそ！\n\n";
        $welcome .= "あなたに最適な補助金をお探しするために、いくつかお伺いします。\n\n";
        $welcome .= "まず、あなたのお立場を教えてください。";
        
        $wpdb->insert(gip_table('messages'), array(
            'session_id' => $session_id,
            'role' => 'assistant',
            'content' => $welcome,
        ));
        
        return new WP_REST_Response(array(
            'success' => true,
            'session_id' => $session_id,
            'message' => $welcome,
            'options' => array(
                array('id' => 'corporation', 'label' => '法人（株式会社・合同会社など）'),
                array('id' => 'individual_business', 'label' => '個人事業主・フリーランス'),
                array('id' => 'startup', 'label' => 'これから起業・創業予定'),
                array('id' => 'npo', 'label' => 'NPO・社団法人・財団法人'),
                array('id' => 'personal', 'label' => '会社員・主婦・学生など（個人）'),
            ),
            'option_type' => 'single',
            'hint' => '該当するものを選択してください。個人の方でも使える補助金・給付金もございます。',
            'allow_input' => false,
        ));
    }
    
    // 既存セッション
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . gip_table('sessions') . " WHERE session_id = %s",
        $session_id
    ));
    
    if (!$session) {
        return new WP_REST_Response(array('success' => false, 'error' => 'セッションが見つかりません'), 400);
    }
    
    $context = json_decode($session->context, true) ?: array();
    $step = $context['step'] ?? 'init';
    
    $user_input = !empty($selection) ? $selection : $message;
    
    // ユーザーメッセージ保存
    $wpdb->insert(gip_table('messages'), array(
        'session_id' => $session_id,
        'role' => 'user',
        'content' => $user_input,
    ));
    
    // 会話処理
    $response = gip_process_natural_conversation($session_id, $context, $user_input, $step);
    
    return new WP_REST_Response($response);
}

/**
 * 自然言語対話フロー処理
 */
function gip_process_natural_conversation($session_id, $context, $user_input, $step) {
    global $wpdb;
    
    $collected = $context['collected_info'] ?? array();
    $next_step = '';
    $response_text = '';
    $options = array();
    $option_type = 'single';
    $hint = '';
    $results = array();
    $show_comparison = false;
    $allow_input = false;
    
    switch ($step) {
        case 'init':
            $user_type = gip_normalize_user_type($user_input);
            $collected['user_type'] = $user_type;
            $collected['user_type_label'] = gip_get_user_type_label($user_type);
            
            $response_text = "ありがとうございます。\n\n";
            $response_text .= "【" . $collected['user_type_label'] . "】ですね。\n\n";
            $response_text .= "次に、お住まいまたは事業所のある都道府県を教えてください。";
            
            $next_step = 'prefecture';
            $prefectures = gip_get_prefectures();
            foreach ($prefectures as $pref) {
                $options[] = array('id' => $pref, 'label' => $pref);
            }
            $option_type = 'prefecture_select';
            $hint = '補助金は地域によって異なります。該当する都道府県を選択してください。';
            $allow_input = true;
            break;
            
        case 'prefecture':
            $prefecture = gip_normalize_prefecture($user_input);
            if ($prefecture) {
                $collected['prefecture'] = $prefecture;
                
                $municipalities = gip_get_municipalities_from_taxonomy($prefecture);
                
                if (!empty($municipalities)) {
                    $next_step = 'municipality';
                    $response_text = "ありがとうございます。\n\n";
                    $response_text .= "【" . $prefecture . "】ですね。\n\n";
                    $response_text .= "より詳しい補助金情報をお探しするため、市区町村も教えていただけますか？\n";
                    $response_text .= "（わからない場合は「全域」を選択してください）";
                    
                    $options = array(array('id' => '全域', 'label' => $prefecture . '全域（市区町村指定なし）'));
                    foreach ($municipalities as $muni) {
                        $options[] = array('id' => $muni['name'], 'label' => $muni['name']);
                    }
                    $option_type = 'municipality_select';
                    $hint = '市区町村独自の補助金もあります。';
                    $allow_input = true;
                } else {
                    $collected['municipality'] = '';
                    $next_step = 'purpose';
                    $response_text = gip_ask_purpose($collected);
                    $options = gip_get_purpose_options($collected['user_type']);
                    $hint = gip_get_purpose_hint($collected['user_type']);
                    $allow_input = true;
                }
            } else {
                $response_text = "申し訳ございません。都道府県を認識できませんでした。\n\n";
                $response_text .= "「東京都」「大阪府」「北海道」などの形式で、都道府県名を教えてください。";
                $next_step = 'prefecture';
                $prefectures = gip_get_prefectures();
                foreach ($prefectures as $pref) {
                    $options[] = array('id' => $pref, 'label' => $pref);
                }
                $option_type = 'prefecture_select';
                $hint = '一覧から選択するか、直接入力してください。';
                $allow_input = true;
            }
            break;
            
        case 'municipality':
            $municipality = $user_input;
            if ($municipality === '全域' || strpos($user_input, '全域') !== false || strpos($user_input, '指定なし') !== false) {
                $collected['municipality'] = '';
            } else {
                $collected['municipality'] = $municipality;
            }
            
            $next_step = 'purpose';
            $response_text = gip_ask_purpose($collected);
            $options = gip_get_purpose_options($collected['user_type']);
            $hint = gip_get_purpose_hint($collected['user_type']);
            $allow_input = true;
            break;
            
        case 'purpose':
            $collected['purpose'] = $user_input;
            
            $analysis = gip_analyze_user_needs($collected);
            
            if ($analysis['needs_clarification']) {
                $next_step = 'clarification';
                $response_text = $analysis['question'];
                $options = $analysis['options'] ?? array();
                $hint = $analysis['hint'] ?? '';
                $allow_input = true;
            } else {
                $next_step = 'searching';
                $response_text = gip_build_search_confirmation($collected);
                
                $filters = array(
                    'user_type' => $collected['user_type'] ?? '',
                    'prefecture' => $collected['prefecture'] ?? '',
                    'municipality' => $collected['municipality'] ?? '',
                    'status_open' => true,
                );
                
                $results = gip_execute_match($session_id, gip_build_natural_search_query($collected), $filters, $collected);
                
                if (!empty($results)) {
                    $count = count($results);
                    $response_text .= "\n\n🎉 検索が完了しました！\n\n";
                    $response_text .= "【" . $count . "件】の補助金・助成金が見つかりました。\n";
                    $response_text .= "マッチ度の高い順に表示しています。";
                    
                    $next_step = 'results';
                    $show_comparison = true;
                } else {
                    $response_text .= "\n\n申し訳ございません。条件に合う補助金が見つかりませんでした。\n\n";
                    $response_text .= "条件を変更して再検索しますか？";
                    
                    $next_step = 'no_result';
                    $options = array(
                        array('id' => 'expand_area', 'label' => '都道府県全域で探す'),
                        array('id' => 'national', 'label' => '全国対応の補助金を探す'),
                        array('id' => 'change_purpose', 'label' => '目的を変えて探す'),
                        array('id' => 'restart', 'label' => '最初からやり直す'),
                    );
                    $hint = '条件を広げると見つかる可能性があります。';
                }
            }
            break;
            
        case 'clarification':
            $collected['clarification'] = $user_input;
            
            $analysis = gip_analyze_user_needs($collected);
            
            if ($analysis['needs_clarification'] && ($context['clarification_count'] ?? 0) < 3) {
                $context['clarification_count'] = ($context['clarification_count'] ?? 0) + 1;
                $next_step = 'clarification';
                $response_text = $analysis['question'];
                $options = $analysis['options'] ?? array();
                $hint = $analysis['hint'] ?? '';
                $allow_input = true;
            } else {
                $next_step = 'searching';
                $response_text = gip_build_search_confirmation($collected);
                
                $filters = array(
                    'user_type' => $collected['user_type'] ?? '',
                    'prefecture' => $collected['prefecture'] ?? '',
                    'municipality' => $collected['municipality'] ?? '',
                    'status_open' => true,
                );
                
                $results = gip_execute_match($session_id, gip_build_natural_search_query($collected), $filters, $collected);
                
                if (!empty($results)) {
                    $count = count($results);
                    $response_text .= "\n\n🎉 検索が完了しました！\n【" . $count . "件】の補助金が見つかりました。";
                    $next_step = 'results';
                    $show_comparison = true;
                } else {
                    $response_text .= "\n\n条件に合う補助金が見つかりませんでした。";
                    $next_step = 'no_result';
                    $options = array(
                        array('id' => 'expand_area', 'label' => '条件を広げて探す'),
                        array('id' => 'restart', 'label' => '最初からやり直す'),
                    );
                }
            }
            break;
            
        case 'no_result':
            if (strpos($user_input, 'expand') !== false || strpos($user_input, '全域') !== false) {
                $collected['municipality'] = '';
                
                $filters = array(
                    'user_type' => $collected['user_type'] ?? '',
                    'prefecture' => $collected['prefecture'] ?? '',
                    'status_open' => true,
                );
                
                $results = gip_execute_match($session_id, gip_build_natural_search_query($collected), $filters, $collected);
                
                if (!empty($results)) {
                    $response_text = "【" . ($collected['prefecture'] ?? '') . "全域】で検索した結果、【" . count($results) . "件】見つかりました！";
                    $next_step = 'results';
                    $show_comparison = true;
                } else {
                    $response_text = "申し訳ございません。広げても見つかりませんでした。";
                    $next_step = 'results';
                }
            } elseif (strpos($user_input, 'national') !== false || strpos($user_input, '全国') !== false) {
                $collected['prefecture'] = '';
                $collected['municipality'] = '';
                
                $filters = array(
                    'user_type' => $collected['user_type'] ?? '',
                    'status_open' => true,
                );
                
                $results = gip_execute_match($session_id, gip_build_natural_search_query($collected), $filters, $collected);
                
                if (!empty($results)) {
                    $response_text = "【全国対応】の補助金を検索した結果、【" . count($results) . "件】見つかりました！";
                    $next_step = 'results';
                    $show_comparison = true;
                } else {
                    $response_text = "申し訳ございません。全国対応でも条件に合う補助金が見つかりませんでした。";
                    $next_step = 'results';
                }
            } elseif (strpos($user_input, 'restart') !== false || strpos($user_input, '最初') !== false) {
                $context = array('step' => 'init', 'understanding_level' => 0, 'collected_info' => array());
                $wpdb->update(gip_table('sessions'), array('context' => wp_json_encode($context)), array('session_id' => $session_id));
                
                return gip_api_chat(new WP_REST_Request('POST'));
            } else {
                $collected['purpose'] = $user_input;
                $next_step = 'purpose';
                
                return gip_process_natural_conversation($session_id, array_merge($context, array('collected_info' => $collected)), $user_input, 'purpose');
            }
            break;
            
        case 'results':
        case 'followup':
            $followup = gip_handle_followup_question($session_id, $collected, $user_input);
            $response_text = $followup['message'];
            $options = $followup['options'] ?? array();
            $hint = $followup['hint'] ?? '';
            $allow_input = true;
            $next_step = 'followup';
            
            if (!empty($followup['results'])) {
                $results = $followup['results'];
                $show_comparison = true;
            }
            break;
            
        default:
            $next_step = 'followup';
            $response_text = "ご質問ありがとうございます。お気軽にお尋ねください。";
            $allow_input = true;
            break;
    }
    
    // コンテキスト保存
    $context['step'] = $next_step;
    $context['collected_info'] = $collected;
    $wpdb->update(
        gip_table('sessions'),
        array(
            'context' => wp_json_encode($context),
            'updated_at' => current_time('mysql'),
        ),
        array('session_id' => $session_id)
    );
    
    // AI応答保存
    $wpdb->insert(gip_table('messages'), array(
        'session_id' => $session_id,
        'role' => 'assistant',
        'content' => $response_text,
    ));
    
    $response = array(
        'success' => true,
        'session_id' => $session_id,
        'message' => $response_text,
        'can_continue' => ($next_step === 'results' || $next_step === 'followup'),
        'allow_input' => $allow_input,
    );
    
    if (!empty($options)) {
        $response['options'] = $options;
        $response['option_type'] = $option_type;
    }
    
    if (!empty($hint)) {
        $response['hint'] = $hint;
    }
    
    if (!empty($results)) {
        $response['results'] = $results;
        $response['results_count'] = count($results);
        $response['show_comparison'] = $show_comparison;
    }
    
    return $response;
}

// =============================================================================
// ユーティリティ関数群
// =============================================================================

function gip_normalize_user_type($input) {
    $input = mb_strtolower($input);
    
    if (preg_match('/(法人|株式会社|合同会社|有限会社|企業)/', $input)) {
        return 'corporation';
    }
    if (preg_match('/(個人事業|フリーランス|自営)/', $input)) {
        return 'individual_business';
    }
    if (preg_match('/(起業|創業|スタートアップ)/', $input)) {
        return 'startup';
    }
    if (preg_match('/(npo|非営利|社団|財団)/', $input)) {
        return 'npo';
    }
    if (preg_match('/(会社員|サラリーマン|主婦|学生|個人|一般)/', $input)) {
        return 'personal';
    }
    
    $map = array(
        'corporation' => 'corporation',
        'individual_business' => 'individual_business',
        'startup' => 'startup',
        'npo' => 'npo',
        'personal' => 'personal',
    );
    
    return $map[$input] ?? 'personal';
}

function gip_get_user_type_label($type) {
    $labels = array(
        'corporation' => '法人',
        'individual_business' => '個人事業主・フリーランス',
        'startup' => '創業予定',
        'npo' => 'NPO・社団法人等',
        'personal' => '個人（会社員・主婦・学生等）',
    );
    return $labels[$type] ?? '個人';
}

function gip_normalize_prefecture($input) {
    $prefectures = gip_get_prefectures();
    
    if (in_array($input, $prefectures)) {
        return $input;
    }
    
    foreach ($prefectures as $pref) {
        $base = preg_replace('/(都|道|府|県)$/', '', $pref);
        $input_base = preg_replace('/(都|道|府|県)$/', '', $input);
        if ($base === $input_base) {
            return $pref;
        }
    }
    
    foreach ($prefectures as $pref) {
        if (strpos($pref, $input) !== false || strpos($input, preg_replace('/(都|道|府|県)$/', '', $pref)) !== false) {
            return $pref;
        }
    }
    
    return null;
}

function gip_ask_purpose($collected) {
    $type = $collected['user_type'] ?? 'personal';
    $location = $collected['prefecture'] ?? '';
    if (!empty($collected['municipality'])) {
        $location .= ' ' . $collected['municipality'];
    }
    
    $text = "ありがとうございます。\n\n";
    
    if (!empty($location)) {
        $text .= "【" . $location . "】でお探しですね。\n\n";
    }
    
    switch ($type) {
        case 'corporation':
        case 'individual_business':
            $text .= "次に、補助金を使いたい目的を教えてください。\n";
            $text .= "例えば、設備投資、人材採用、販路拡大など、何でも自由にお書きください。";
            break;
        case 'startup':
            $text .= "起業・創業に向けて、どのような支援をお探しですか？\n";
            $text .= "例えば、開業資金、オフィス賃料、設備購入など。";
            break;
        case 'personal':
            $text .= "どのような支援をお探しですか？\n";
            $text .= "例えば、住宅リフォーム、子育て支援、資格取得、医療費補助など。";
            break;
        default:
            $text .= "補助金・助成金を使いたい目的を教えてください。";
    }
    
    return $text;
}

function gip_get_purpose_options($user_type) {
    switch ($user_type) {
        case 'corporation':
        case 'individual_business':
            return array(
                array('id' => 'equipment', 'label' => '設備投資・機械導入'),
                array('id' => 'it', 'label' => 'IT化・DX推進'),
                array('id' => 'hr', 'label' => '人材採用・育成'),
                array('id' => 'rd', 'label' => '研究開発・新製品開発'),
                array('id' => 'sales', 'label' => '販路拡大・海外展開'),
                array('id' => 'eco', 'label' => '省エネ・環境対策'),
                array('id' => 'recovery', 'label' => '経営改善・事業転換'),
            );
        case 'startup':
            return array(
                array('id' => 'funding', 'label' => '開業資金・創業融資'),
                array('id' => 'office', 'label' => 'オフィス・店舗'),
                array('id' => 'equipment', 'label' => '設備・備品購入'),
                array('id' => 'consulting', 'label' => '経営相談・メンタリング'),
            );
        case 'personal':
            return array(
                array('id' => 'housing', 'label' => '住宅購入・リフォーム'),
                array('id' => 'childcare', 'label' => '子育て・出産'),
                array('id' => 'education', 'label' => '教育・資格取得'),
                array('id' => 'medical', 'label' => '医療・介護'),
                array('id' => 'energy', 'label' => '省エネ・太陽光'),
                array('id' => 'moving', 'label' => '移住・UIJターン'),
            );
        default:
            return array();
    }
}

function gip_get_purpose_hint($user_type) {
    switch ($user_type) {
        case 'corporation':
        case 'individual_business':
            return '選択肢から選ぶか、「新しいシステムを導入したい」「従業員を増やしたい」など自由にお書きください。';
        case 'startup':
            return '「カフェを開業したい」「ECサイトを立ち上げたい」など、具体的な計画があればお書きください。';
        case 'personal':
            return '「家のリフォームをしたい」「子供の保育園代」など、お困りのことを教えてください。';
        default:
            return '具体的な用途を教えていただけると、より適切な補助金をご案内できます。';
    }
}

function gip_analyze_user_needs($collected) {
    $prompt = "以下のユーザー情報を分析し、補助金検索に十分な情報があるか判断してください。

## ユーザー情報
- 属性: " . ($collected['user_type_label'] ?? '不明') . "
- 都道府県: " . ($collected['prefecture'] ?? '不明') . "
- 市区町村: " . ($collected['municipality'] ?? '全域') . "
- 目的・要望: " . ($collected['purpose'] ?? '不明') . "
" . (!empty($collected['clarification']) ? "- 追加情報: " . $collected['clarification'] : "") . "

## タスク
1. 補助金検索に十分な情報があれば {\"needs_clarification\": false} を返す
2. 追加情報が必要な場合は、以下の形式で1つだけ質問を生成：

{
  \"needs_clarification\": true,
  \"question\": \"質問文（150文字以内、丁寧な口調で）\",
  \"hint\": \"回答のヒント\",
  \"options\": [
    {\"id\": \"option1\", \"label\": \"選択肢1\"},
    {\"id\": \"option2\", \"label\": \"選択肢2\"}
  ]
}

## 注意
- 質問は最大3回まで。すでに十分な情報があれば追加質問しない
- 質問は具体的で答えやすいものにする

JSON形式のみで出力してください。";

    $response = gip_call_gemini($prompt, array('temperature' => 0.3, 'max_tokens' => 500));
    
    if (is_wp_error($response)) {
        return array('needs_clarification' => false);
    }
    
    if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
        $parsed = json_decode($matches[0], true);
        if (is_array($parsed)) {
            return $parsed;
        }
    }
    
    return array('needs_clarification' => false);
}

function gip_build_search_confirmation($collected) {
    $text = "ありがとうございます。以下の条件で補助金を検索します。\n\n";
    $text .= "━━━━━━━━━━━━━━━━━━━━\n";
    $text .= "📋 検索条件\n";
    $text .= "━━━━━━━━━━━━━━━━━━━━\n";
    
    if (!empty($collected['user_type_label'])) {
        $text .= "👤 お立場: " . $collected['user_type_label'] . "\n";
    }
    
    $location = $collected['prefecture'] ?? '';
    if (!empty($collected['municipality'])) {
        $location .= ' ' . $collected['municipality'];
    }
    if (!empty($location)) {
        $text .= "📍 地域: " . $location . "\n";
    }
    
    if (!empty($collected['purpose'])) {
        $text .= "🎯 目的: " . mb_substr($collected['purpose'], 0, 50) . "\n";
    }
    
    return $text;
}

function gip_build_natural_search_query($collected) {
    $parts = array();
    
    if (!empty($collected['purpose'])) {
        $parts[] = $collected['purpose'];
    }
    
    if (!empty($collected['clarification'])) {
        $parts[] = $collected['clarification'];
    }
    
    if (!empty($collected['user_type_label'])) {
        $parts[] = $collected['user_type_label'] . '向け';
    }
    
    if (!empty($collected['prefecture'])) {
        $parts[] = $collected['prefecture'];
    }
    
    return implode(' ', $parts);
}

function gip_handle_followup_question($session_id, $collected, $user_input) {
    global $wpdb;
    
    $history = $wpdb->get_results($wpdb->prepare(
        "SELECT role, content FROM " . gip_table('messages') . " WHERE session_id = %s ORDER BY id DESC LIMIT 10",
        $session_id
    ));
    $history = array_reverse($history);
    
    $recent_results = $wpdb->get_results($wpdb->prepare(
        "SELECT r.grant_id, r.score, r.reason FROM " . gip_table('results') . " r WHERE r.session_id = %s ORDER BY r.score DESC LIMIT 10",
        $session_id
    ));
    
    $grants_info = '';
    if (!empty($recent_results)) {
        foreach ($recent_results as $i => $r) {
            $post = get_post($r->grant_id);
            if ($post) {
                $grants_info .= ($i + 1) . ". " . $post->post_title . "（スコア: {$r->score}）\n";
            }
        }
    }
    
    $prompt = "あなたは補助金・助成金の専門アドバイザーです。

## ユーザー情報
お立場: " . ($collected['user_type_label'] ?? '不明') . "
地域: " . ($collected['prefecture'] ?? '') . " " . ($collected['municipality'] ?? '') . "
目的: " . ($collected['purpose'] ?? '不明') . "

## 直近の検索結果（上位10件）
{$grants_info}

## 直近の会話
";
    foreach ($history as $h) {
        $role = $h->role === 'assistant' ? 'AI' : 'ユーザー';
        $prompt .= $role . ": " . mb_substr($h->content, 0, 200) . "\n";
    }

    $prompt .= "
ユーザーの最新発言: " . $user_input . "

## タスク
ユーザーの質問や要望に対して適切に回答してください。

回答は300文字以内で、具体的かつ丁寧に。
末尾に、ユーザーが次に聞きそうな質問を2-3個提案してください。

出力形式（JSON）:
{
  \"message\": \"回答テキスト\",
  \"options\": [
    {\"id\": \"option1\", \"label\": \"次の質問候補1\"},
    {\"id\": \"option2\", \"label\": \"次の質問候補2\"}
  ],
  \"hint\": \"ヒント\"
}
";

    $response = gip_call_gemini($prompt, array('temperature' => 0.7, 'max_tokens' => 800));
    
    if (is_wp_error($response)) {
        return array(
            'message' => "ご質問ありがとうございます。\n\n他にお聞きになりたいことはありますか？",
            'options' => array(
                array('id' => 'top_detail', 'label' => '1位の補助金について詳しく教えて'),
                array('id' => 'how_to_apply', 'label' => '申請方法を教えて'),
                array('id' => 'documents', 'label' => '必要書類を教えて'),
            ),
            'hint' => '「○○補助金について教えて」「申請のコツは？」など、自由にお聞きください。',
        );
    }
    
    if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
        $parsed = json_decode($matches[0], true);
        if (is_array($parsed) && !empty($parsed['message'])) {
            return array(
                'message' => $parsed['message'],
                'options' => $parsed['options'] ?? array(),
                'hint' => $parsed['hint'] ?? '',
            );
        }
    }
    
    return array(
        'message' => $response,
        'options' => array(
            array('id' => 'more_info', 'label' => 'もっと詳しく'),
            array('id' => 'other_grant', 'label' => '他の補助金を見る'),
        ),
        'hint' => '',
    );
}

function gip_execute_match($session_id, $user_context, $filters = array(), $context = array()) {
    global $wpdb;
    
    $max_results = (int)get_option('gip_max_results', 30);
    
    $similar = gip_search_vectors_with_filter($user_context, $filters, $max_results * 2);
    
    if (empty($similar)) {
        return array();
    }
    
    $candidates = array();
    foreach (array_keys($similar) as $grant_id) {
        $detail = gip_get_grant_card_data($grant_id);
        if ($detail) {
            $detail['vector_score'] = $similar[$grant_id];
            $candidates[] = $detail;
        }
    }
    
    if (empty($candidates)) {
        return array();
    }
    
    $scored = gip_llm_score($user_context, $candidates, $context);
    
    if (is_wp_error($scored) || empty($scored)) {
        usort($candidates, function($a, $b) {
            return $b['vector_score'] <=> $a['vector_score'];
        });
        
        $results = array_slice($candidates, 0, $max_results);
        
        foreach ($results as $i => &$r) {
            $r['score'] = round($r['vector_score'] * 100);
            $r['reason'] = '類似度に基づく推薦';
            $r['rank'] = $i + 1;
        }
        
        return $results;
    }
    
    foreach ($scored as $r) {
        $wpdb->insert(gip_table('results'), array(
            'session_id' => $session_id,
            'grant_id' => $r['grant_id'],
            'score' => $r['score'],
            'reason' => $r['reason'],
        ));
    }
    
    return $scored;
}

function gip_get_grant_card_data($id) {
    $post = get_post($id);
    if (!$post || $post->post_status !== 'publish') return null;
    
    $data = array(
        'grant_id' => $id,
        'title' => $post->post_title,
        'excerpt' => wp_trim_words(wp_strip_all_tags($post->post_content), 80),
        'url' => get_permalink($id),
    );
    
    if (function_exists('get_field')) {
        $data['organization'] = get_field('organization', $id) ?: '';
        $data['max_amount'] = get_field('max_amount', $id) ?: '';
        $data['max_amount_numeric'] = intval(get_field('max_amount_numeric', $id) ?: 0);
        $data['subsidy_rate'] = get_field('subsidy_rate', $id) ?: '';
        $data['deadline'] = get_field('deadline', $id) ?: '';
        $data['deadline_date'] = get_field('deadline_date', $id) ?: '';
        $data['grant_target'] = get_field('grant_target', $id) ?: '';
        $data['eligible_expenses'] = get_field('eligible_expenses', $id) ?: '';
        $data['online_application'] = (bool)get_field('online_application', $id);
        $data['jgrants_available'] = (bool)get_field('jgrants_available', $id);
        $data['application_status'] = get_field('application_status', $id) ?: 'open';
        $data['ai_summary'] = get_field('ai_summary', $id) ?: '';
        $data['required_documents'] = get_field('required_documents', $id) ?: '';
        $data['application_tips'] = get_field('application_tips', $id) ?: '';
    }
    
    $pref_terms = get_the_terms($id, 'grant_prefecture');
    $data['prefectures'] = ($pref_terms && !is_wp_error($pref_terms)) ? wp_list_pluck($pref_terms, 'name') : array();
    
    $muni_terms = get_the_terms($id, 'grant_municipality');
    $data['municipalities'] = ($muni_terms && !is_wp_error($muni_terms)) ? wp_list_pluck($muni_terms, 'name') : array();
    
    $cat_terms = get_the_terms($id, 'grant_category');
    $data['categories'] = ($cat_terms && !is_wp_error($cat_terms)) ? wp_list_pluck($cat_terms, 'name') : array();
    
    $data['amount_display'] = gip_format_amount_display($data);
    $data['deadline_display'] = gip_format_deadline_display($data);
    
    if (!empty($data['deadline_date'])) {
        $deadline_ts = strtotime($data['deadline_date'] . ' 23:59:59');
        if ($deadline_ts) {
            $data['days_remaining'] = floor(($deadline_ts - current_time('timestamp')) / 86400);
        }
    }
    
    return $data;
}

function gip_llm_score($context, $candidates, $user_context = array()) {
    if (empty($candidates)) return array();
    
    $list = "";
    foreach ($candidates as $i => $c) {
        $list .= ($i + 1) . ". " . $c['title'] . "\n";
        if (!empty($c['organization'])) $list .= "   実施機関: " . $c['organization'] . "\n";
        if (!empty($c['max_amount'])) $list .= "   上限額: " . $c['max_amount'] . "\n";
        if (!empty($c['prefectures'])) $list .= "   地域: " . implode(',', array_slice($c['prefectures'], 0, 3)) . "\n";
        $list .= "\n";
    }
    
    $user_type = $user_context['collected_info']['user_type_label'] ?? '';
    $user_pref = $user_context['collected_info']['prefecture'] ?? '';
    
    $prompt = "以下のユーザー情報と補助金候補を分析し、適合度をスコアリングしてください。

## ユーザー情報
{$context}

## ユーザー属性
- お立場: {$user_type}
- 都道府県: {$user_pref}

## スコアリング基準
- 目的・用途の一致: +30点
- 地域の一致: +25点
- 対象者要件の適合: +25点
- 金額規模の適切さ: +10点
- その他条件: +10点

## 補助金候補
{$list}

## 出力形式（JSONのみ）
[
  {\"index\":1,\"score\":85,\"reason\":\"ユーザーの目的に最適。○○が対象で△△万円まで補助\"},
  ...
]

全候補をスコアリングし、高い順に出力。
reasonは50文字以内で、ユーザーにとってのメリットを具体的に記載。";

    $response = gip_call_gemini($prompt, array('temperature' => 0.3, 'max_tokens' => 3000));
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    if (preg_match('/\[[\s\S]*\]/', $response, $m)) {
        $parsed = json_decode($m[0], true);
        
        if (is_array($parsed)) {
            $results = array();
            foreach ($parsed as $rank => $item) {
                $idx = ($item['index'] ?? 1) - 1;
                if (isset($candidates[$idx])) {
                    $r = $candidates[$idx];
                    $r['score'] = min(100, max(0, intval($item['score'] ?? 50)));
                    $r['reason'] = $item['reason'] ?? '';
                    $r['rank'] = $rank + 1;
                    $results[] = $r;
                }
            }
            
            usort($results, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            
            foreach ($results as $i => &$r) {
                $r['rank'] = $i + 1;
            }
            
            return array_slice($results, 0, (int)get_option('gip_max_results', 30));
        }
    }
    
    return new WP_Error('parse_error', 'Failed to parse LLM response');
}

function gip_api_feedback($request) {
    global $wpdb;
    
    $params = $request->get_json_params();
    $session_id = sanitize_text_field($params['session_id'] ?? '');
    $grant_id = absint($params['grant_id'] ?? 0);
    $feedback = sanitize_text_field($params['feedback'] ?? '');
    
    if (empty($session_id) || empty($grant_id) || !in_array($feedback, array('positive', 'negative'))) {
        return new WP_REST_Response(array('success' => false), 400);
    }
    
    $wpdb->update(
        gip_table('results'),
        array('feedback' => $feedback),
        array('session_id' => $session_id, 'grant_id' => $grant_id)
    );
    
    return new WP_REST_Response(array('success' => true));
}

// =============================================================================
// Frontend Assets
// =============================================================================

function gip_frontend_assets() {
    global $post;
    
    $should_load = false;
    
    if (is_singular() && is_a($post, 'WP_Post')) {
        if (has_shortcode($post->post_content, 'gip_chat')) {
            $should_load = true;
        }
        $template = get_page_template_slug($post->ID);
        if (strpos($template, 'ai-diagnosis') !== false || strpos($template, 'gip') !== false) {
            $should_load = true;
        }
    }
    
    if (is_front_page() || is_page()) {
        $should_load = true;
    }
    
    if (!$should_load) {
        return;
    }
    
    wp_register_style('gip-chat-style', false, array(), GIP_VERSION);
    wp_enqueue_style('gip-chat-style');
    wp_add_inline_style('gip-chat-style', gip_frontend_css());
    
    wp_enqueue_script('jquery');
    
    wp_register_script('gip-chat-script', false, array('jquery'), GIP_VERSION, true);
    wp_enqueue_script('gip-chat-script');
    
    wp_localize_script('gip-chat-script', 'GIP_CHAT', array(
        'api' => esc_url_raw(rest_url(GIP_API_NS)),
        'ajax' => esc_url_raw(admin_url('admin-ajax.php')),
        'nonce' => wp_create_nonce('wp_rest'),
        'debug' => defined('WP_DEBUG') && WP_DEBUG,
    ));
    
    wp_add_inline_script('gip-chat-script', gip_frontend_js());
}

// =============================================================================
// Frontend CSS - 完全版
// =============================================================================

function gip_frontend_css() {
    return '
:root {
    --gip-black: #111111;
    --gip-white: #ffffff;
    --gip-gray-50: #fafafa;
    --gip-gray-100: #f5f5f5;
    --gip-gray-200: #e5e5e5;
    --gip-gray-300: #d4d4d4;
    --gip-gray-400: #a3a3a3;
    --gip-gray-500: #737373;
    --gip-gray-600: #525252;
    --gip-gray-700: #404040;
    --gip-gray-900: #171717;
    --gip-accent: #1a56db;
    --gip-accent-light: #e8f0fe;
    --gip-accent-dark: #1447b8;
    --gip-success: #059669;
    --gip-success-light: #d1fae5;
    --gip-warning: #d97706;
    --gip-warning-light: #fef3c7;
    --gip-error: #dc2626;
    --gip-error-light: #fee2e2;
    --gip-shadow: 0 4px 24px rgba(0,0,0,0.08);
    --gip-shadow-lg: 0 10px 40px rgba(0,0,0,0.12);
    --gip-transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    --gip-radius: 12px;
    --gip-radius-lg: 20px;
}

.gip-chat {
    max-width: 900px;
    margin: 0 auto;
    font-family: "Noto Sans JP", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background: var(--gip-white);
    border: 1px solid var(--gip-gray-200);
    border-radius: var(--gip-radius);
    box-shadow: var(--gip-shadow-lg);
    overflow: hidden;
    line-height: 1.7;
}

.gip-chat * {
    box-sizing: border-box;
}

.gip-chat-header {
    padding: 20px 24px;
    background: linear-gradient(135deg, var(--gip-black) 0%, #1f1f1f 100%);
    color: var(--gip-white);
    display: flex;
    align-items: center;
    gap: 16px;
}

.gip-chat-icon {
    width: 48px;
    height: 48px;
    background: var(--gip-accent);
    border-radius: var(--gip-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.gip-chat-icon svg {
    width: 24px;
    height: 24px;
    stroke: var(--gip-white);
    fill: none;
}

.gip-chat-header-text h3 {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 4px 0;
}

.gip-chat-header-text p {
    font-size: 13px;
    opacity: 0.8;
    margin: 0;
}

.gip-chat-messages {
    min-height: 400px;
    max-height: 500px;
    overflow-y: auto;
    padding: 24px;
    background: var(--gip-gray-50);
    scroll-behavior: smooth;
}

.gip-chat-messages::-webkit-scrollbar {
    width: 6px;
}

.gip-chat-messages::-webkit-scrollbar-track {
    background: transparent;
}

.gip-chat-messages::-webkit-scrollbar-thumb {
    background: var(--gip-gray-300);
    border-radius: 3px;
}

.gip-message {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    animation: gipFadeIn 0.4s ease;
}

@keyframes gipFadeIn {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

.gip-message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: -0.5px;
}

.gip-message-bot .gip-message-avatar {
    background: var(--gip-accent);
    color: var(--gip-white);
}

.gip-message-user .gip-message-avatar {
    background: var(--gip-gray-300);
    color: var(--gip-gray-700);
}

.gip-message-content {
    flex: 1;
    max-width: 80%;
}

.gip-message-user {
    flex-direction: row-reverse;
}

.gip-message-user .gip-message-content {
    text-align: right;
}

.gip-message-bubble {
    display: inline-block;
    padding: 14px 18px;
    font-size: 15px;
    line-height: 1.7;
    white-space: pre-wrap;
    word-break: break-word;
}

.gip-message-bot .gip-message-bubble {
    background: var(--gip-white);
    border: 1px solid var(--gip-gray-200);
    border-radius: 4px var(--gip-radius-lg) var(--gip-radius-lg) var(--gip-radius-lg);
    text-align: left;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.gip-message-user .gip-message-bubble {
    background: var(--gip-accent);
    color: var(--gip-white);
    border-radius: var(--gip-radius-lg) 4px var(--gip-radius-lg) var(--gip-radius-lg);
}

.gip-hint {
    margin-top: 12px;
    padding: 12px 16px;
    background: var(--gip-accent-light);
    border-left: 3px solid var(--gip-accent);
    border-radius: 0 8px 8px 0;
    font-size: 13px;
    color: var(--gip-gray-700);
}

.gip-options {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 16px;
}

.gip-option-btn {
    padding: 12px 20px;
    font-size: 14px;
    font-weight: 600;
    border: 2px solid var(--gip-gray-200);
    background: var(--gip-white);
    color: var(--gip-gray-700);
    border-radius: 24px;
    cursor: pointer;
    transition: all var(--gip-transition);
    font-family: inherit;
}

.gip-option-btn:hover {
    background: var(--gip-gray-100);
    border-color: var(--gip-gray-400);
    transform: translateY(-1px);
}

.gip-option-btn:active {
    transform: translateY(0);
}

.gip-option-btn.selected {
    background: var(--gip-accent);
    border-color: var(--gip-accent);
    color: var(--gip-white);
}

.gip-select-wrap {
    margin-top: 16px;
    position: relative;
}

.gip-select {
    width: 100%;
    max-width: 320px;
    padding: 14px 40px 14px 16px;
    font-size: 15px;
    font-family: inherit;
    border: 2px solid var(--gip-gray-200);
    border-radius: 8px;
    background: var(--gip-white);
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 12 12\'%3E%3Cpath fill=\'%23666\' d=\'M6 8L1 3h10z\'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    transition: border-color var(--gip-transition);
}

.gip-select:focus {
    outline: none;
    border-color: var(--gip-accent);
}

.gip-input-inline {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    max-width: 320px;
}

.gip-input-inline input,
.gip-inline-input {
    flex: 1;
    padding: 12px 14px;
    border: 2px solid var(--gip-gray-200);
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: border-color var(--gip-transition);
}

.gip-input-inline input:focus,
.gip-inline-input:focus {
    outline: none;
    border-color: var(--gip-accent);
}

.gip-input-inline button,
.gip-inline-submit {
    padding: 12px 20px;
    background: var(--gip-accent);
    color: var(--gip-white);
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: background var(--gip-transition);
}

.gip-input-inline button:hover,
.gip-inline-submit:hover {
    background: var(--gip-accent-dark);
}

.gip-message-typing {
    display: flex;
    gap: 5px;
    padding: 16px 20px;
    background: var(--gip-white);
    border: 1px solid var(--gip-gray-200);
    border-radius: 4px var(--gip-radius-lg) var(--gip-radius-lg) var(--gip-radius-lg);
}

.gip-typing-dot {
    width: 8px;
    height: 8px;
    background: var(--gip-gray-400);
    border-radius: 50%;
    animation: gipTyping 1.4s infinite;
}

.gip-typing-dot:nth-child(2) { animation-delay: 0.2s; }
.gip-typing-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes gipTyping {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
    30% { transform: translateY(-6px); opacity: 1; }
}

.gip-chat-input-area {
    padding: 20px 24px;
    border-top: 1px solid var(--gip-gray-200);
    background: var(--gip-white);
}

.gip-chat-input-wrap {
    display: flex;
    gap: 12px;
    align-items: flex-end;
}

.gip-chat-input {
    flex: 1;
    padding: 14px 18px;
    border: 2px solid var(--gip-gray-200);
    border-radius: 24px;
    font-size: 15px;
    font-family: inherit;
    resize: none;
    max-height: 120px;
    line-height: 1.5;
    transition: border-color var(--gip-transition);
}

.gip-chat-input:focus {
    outline: none;
    border-color: var(--gip-accent);
}

.gip-chat-input::placeholder {
    color: var(--gip-gray-400);
}

.gip-chat-send {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--gip-accent);
    color: var(--gip-white);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all var(--gip-transition);
}

.gip-chat-send:hover:not(:disabled) {
    background: var(--gip-accent-dark);
    transform: scale(1.05);
}

.gip-chat-send:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    transform: none;
}

.gip-chat-send svg {
    width: 20px;
    height: 20px;
}

/* Results Section */
.gip-results {
    padding: 28px;
    border-top: 3px solid var(--gip-accent);
    background: var(--gip-white);
}

.gip-results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--gip-gray-200);
    flex-wrap: wrap;
    gap: 16px;
}

.gip-results-title {
    font-size: 22px;
    font-weight: 800;
    margin: 0;
    color: var(--gip-black);
}

.gip-results-count {
    font-size: 14px;
    color: var(--gip-gray-500);
    margin-top: 4px;
}

.gip-btn-compare {
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 700;
    font-family: inherit;
    background: var(--gip-white);
    border: 2px solid var(--gip-accent);
    color: var(--gip-accent);
    border-radius: 8px;
    cursor: pointer;
    transition: all var(--gip-transition);
}

.gip-btn-compare:hover:not(:disabled) {
    background: var(--gip-accent);
    color: var(--gip-white);
}

.gip-btn-compare:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.gip-results-grid {
    display: grid;
    gap: 20px;
}

.gip-result-card {
    border: 1px solid var(--gip-gray-200);
    border-radius: var(--gip-radius);
    overflow: hidden;
    transition: all var(--gip-transition);
    background: var(--gip-white);
}

.gip-result-card:hover {
    border-color: var(--gip-accent);
    box-shadow: var(--gip-shadow);
    transform: translateY(-2px);
}

.gip-result-card.highlight {
    border-color: var(--gip-accent);
    border-width: 2px;
}

.gip-result-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px;
    background: var(--gip-gray-50);
    border-bottom: 1px solid var(--gip-gray-200);
}

.gip-result-rank {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 900;
    flex-shrink: 0;
    border-radius: 8px;
    background: var(--gip-gray-200);
    color: var(--gip-gray-600);
}

.gip-result-rank-1 { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #78350f; }
.gip-result-rank-2 { background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%); color: #fff; }
.gip-result-rank-3 { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); color: #fff; }

.gip-result-info {
    flex: 1;
    min-width: 0;
}

.gip-result-title {
    font-size: 17px;
    font-weight: 700;
    margin: 0 0 8px 0;
    line-height: 1.4;
    color: var(--gip-black);
}

.gip-result-org {
    font-size: 13px;
    color: var(--gip-gray-500);
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.gip-result-prefecture {
    background: var(--gip-accent-light);
    color: var(--gip-accent);
    padding: 3px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.gip-result-score {
    text-align: center;
    padding: 12px 16px;
    background: var(--gip-white);
    border-radius: 8px;
    border: 2px solid var(--gip-gray-200);
    min-width: 85px;
}

.gip-result-score-value {
    font-size: 28px;
    font-weight: 900;
    color: var(--gip-accent);
    font-family: "SF Mono", Monaco, Consolas, monospace;
    line-height: 1;
}

.gip-result-score-label {
    font-size: 11px;
    color: var(--gip-gray-500);
    margin-top: 4px;
    font-weight: 500;
}

.gip-result-body {
    padding: 20px;
}

.gip-result-reason {
    font-size: 14px;
    color: var(--gip-gray-700);
    padding: 12px 16px;
    background: var(--gip-accent-light);
    border-radius: 8px;
    margin-bottom: 16px;
    border-left: 4px solid var(--gip-accent);
    font-weight: 500;
    line-height: 1.6;
}

.gip-result-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
    font-size: 14px;
}

.gip-result-meta-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.gip-result-meta-label {
    font-size: 12px;
    color: var(--gip-gray-500);
    font-weight: 500;
}

.gip-result-meta-value {
    font-weight: 700;
    color: var(--gip-black);
}

.gip-result-meta-value.highlight {
    color: var(--gip-accent);
}

.gip-result-excerpt {
    font-size: 14px;
    color: var(--gip-gray-600);
    line-height: 1.7;
    margin-bottom: 16px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.gip-result-details {
    display: none;
    padding: 16px;
    background: var(--gip-gray-50);
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 14px;
    border: 1px solid var(--gip-gray-200);
}

.gip-result-details.show {
    display: block;
    animation: gipFadeIn 0.3s ease;
}

.gip-result-details-title {
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--gip-black);
    font-size: 13px;
}

.gip-result-details-content {
    color: var(--gip-gray-700);
    line-height: 1.7;
}

.gip-result-details-section {
    margin-bottom: 16px;
}

.gip-result-details-section:last-child {
    margin-bottom: 0;
}

.gip-result-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.gip-result-btn {
    flex: 1;
    min-width: 120px;
    padding: 12px 20px;
    text-align: center;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    border-radius: 8px;
    transition: all var(--gip-transition);
    cursor: pointer;
    border: none;
    font-family: inherit;
}

.gip-result-btn-primary {
    background: var(--gip-accent);
    color: var(--gip-white);
}

.gip-result-btn-primary:hover {
    background: var(--gip-accent-dark);
    transform: translateY(-1px);
}

.gip-result-btn-secondary {
    background: var(--gip-white);
    color: var(--gip-gray-700);
    border: 2px solid var(--gip-gray-200);
}

.gip-result-btn-secondary:hover {
    border-color: var(--gip-accent);
    color: var(--gip-accent);
}

.gip-result-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    background: var(--gip-gray-50);
    border-top: 1px solid var(--gip-gray-200);
    font-size: 13px;
}

.gip-compare-check {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: 500;
    color: var(--gip-gray-600);
    transition: color var(--gip-transition);
}

.gip-compare-check:hover {
    color: var(--gip-accent);
}

.gip-compare-check input {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--gip-accent);
}

.gip-feedback-btns {
    display: flex;
    gap: 8px;
    align-items: center;
}

.gip-feedback-label {
    color: var(--gip-gray-500);
    font-size: 12px;
}

.gip-feedback-btn {
    padding: 6px 14px;
    font-size: 13px;
    font-family: inherit;
    border: 1px solid var(--gip-gray-300);
    background: var(--gip-white);
    border-radius: 6px;
    cursor: pointer;
    transition: all var(--gip-transition);
}

.gip-feedback-btn:hover {
    background: var(--gip-gray-100);
    border-color: var(--gip-gray-400);
}

.gip-feedback-btn.selected {
    background: var(--gip-accent);
    color: var(--gip-white);
    border-color: var(--gip-accent);
}

/* Comparison Modal */
.gip-comparison-modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 20px;
    backdrop-filter: blur(4px);
    animation: gipModalFadeIn 0.3s ease;
}

@keyframes gipModalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.gip-comparison-content {
    background: var(--gip-white);
    border-radius: 16px;
    max-width: 1100px;
    max-height: 90vh;
    overflow: auto;
    width: 100%;
    box-shadow: var(--gip-shadow-lg);
    animation: gipModalSlideIn 0.3s ease;
}

@keyframes gipModalSlideIn {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.gip-comparison-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 28px;
    border-bottom: 2px solid var(--gip-gray-200);
    position: sticky;
    top: 0;
    background: var(--gip-white);
    z-index: 10;
}

.gip-comparison-title {
    font-size: 20px;
    font-weight: 900;
    margin: 0;
}

.gip-comparison-close {
    width: 40px;
    height: 40px;
    border: none;
    background: var(--gip-gray-100);
    border-radius: 50%;
    cursor: pointer;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--gip-transition);
    color: var(--gip-gray-600);
}

.gip-comparison-close:hover {
    background: var(--gip-gray-200);
    color: var(--gip-black);
}

.gip-comparison-body {
    padding: 28px;
    overflow-x: auto;
}

.gip-comparison-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.gip-comparison-table th,
.gip-comparison-table td {
    padding: 14px 18px;
    text-align: left;
    border-bottom: 1px solid var(--gip-gray-200);
    vertical-align: top;
}

.gip-comparison-table th {
    background: var(--gip-gray-50);
    font-weight: 700;
    white-space: nowrap;
    width: 120px;
    color: var(--gip-gray-700);
}

.gip-comparison-table td {
    min-width: 180px;
}

.gip-comparison-table .table-header {
    background: var(--gip-accent);
    color: var(--gip-white);
    font-weight: 700;
    font-size: 13px;
}

.gip-comparison-table .table-score {
    font-size: 24px;
    font-weight: 900;
    color: var(--gip-accent);
}

/* Load More */
.gip-load-more {
    text-align: center;
    margin-top: 24px;
}

.gip-btn-load-more {
    padding: 14px 40px;
    font-size: 15px;
    font-weight: 700;
    font-family: inherit;
    background: var(--gip-white);
    border: 2px solid var(--gip-gray-300);
    border-radius: 8px;
    cursor: pointer;
    transition: all var(--gip-transition);
}

.gip-btn-load-more:hover {
    border-color: var(--gip-accent);
    color: var(--gip-accent);
    transform: translateY(-1px);
}

/* Continue Chat */
.gip-continue-chat {
    margin-top: 28px;
    padding-top: 28px;
    border-top: 2px solid var(--gip-gray-200);
}

.gip-continue-title {
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 16px 0;
    color: var(--gip-gray-700);
}

.gip-continue-options {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

/* Badges */
.gip-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 700;
    border-radius: 4px;
}

.gip-badge-success { background: var(--gip-success-light); color: var(--gip-success); }
.gip-badge-warning { background: var(--gip-warning-light); color: var(--gip-warning); }
.gip-badge-error { background: var(--gip-error-light); color: var(--gip-error); }
.gip-badge-info { background: var(--gip-accent-light); color: var(--gip-accent); }

/* Empty State */
.gip-empty-state {
    text-align: center;
    padding: 48px 24px;
    color: var(--gip-gray-500);
}

.gip-empty-state-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.gip-empty-state-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--gip-gray-700);
    margin-bottom: 8px;
}

.gip-empty-state-text {
    font-size: 14px;
    line-height: 1.6;
}

/* Loading Overlay */
.gip-loading-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
}

.gip-spinner-large {
    width: 40px;
    height: 40px;
    border: 3px solid var(--gip-gray-200);
    border-top-color: var(--gip-accent);
    border-radius: 50%;
    animation: gip-spin 1s linear infinite;
}

@keyframes gip-spin {
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .gip-chat {
        border-radius: 0;
        border-width: 0 0 1px 0;
    }
    
    .gip-chat-header {
        padding: 16px 20px;
    }
    
    .gip-chat-header-text h3 {
        font-size: 16px;
    }
    
    .gip-chat-messages {
        padding: 16px;
        min-height: 300px;
        max-height: 400px;
    }
    
    .gip-message-content {
        max-width: 90%;
    }
    
    .gip-message-bubble {
        padding: 12px 14px;
        font-size: 14px;
    }
    
    .gip-chat-input-area {
        padding: 16px;
    }
    
    .gip-chat-input {
        padding: 12px 16px;
        font-size: 14px;
    }
    
    .gip-results {
        padding: 20px;
    }
    
    .gip-results-title {
        font-size: 18px;
    }
    
    .gip-result-header {
        flex-wrap: wrap;
        gap: 12px;
        padding: 16px;
    }
    
    .gip-result-score {
        width: 100%;
        margin-top: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px;
    }
    
    .gip-result-score-value {
        font-size: 24px;
    }
    
    .gip-result-score-label {
        margin-top: 0;
    }
    
    .gip-result-body {
        padding: 16px;
    }
    
    .gip-result-meta {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    
    .gip-result-actions {
        flex-direction: column;
    }
    
    .gip-result-btn {
        width: 100%;
    }
    
    .gip-result-footer {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }
    
    .gip-feedback-btns {
        justify-content: center;
    }
    
    .gip-options {
        flex-direction: column;
    }
    
    .gip-option-btn {
        width: 100%;
        text-align: center;
    }
    
    .gip-results-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .gip-btn-compare {
        width: 100%;
    }
    
    .gip-comparison-content {
        border-radius: 12px 12px 0 0;
        max-height: 95vh;
    }
    
    .gip-comparison-header {
        padding: 16px 20px;
    }
    
    .gip-comparison-body {
        padding: 16px;
    }
    
    .gip-comparison-table th,
    .gip-comparison-table td {
        padding: 10px 12px;
        font-size: 13px;
    }
    
    .gip-select {
        max-width: 100%;
    }
    
    .gip-input-inline {
        max-width: 100%;
    }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    .gip-message,
    .gip-result-card,
    .gip-comparison-modal,
    .gip-comparison-content {
        animation: none;
    }
    
    .gip-typing-dot {
        animation: none;
        opacity: 0.6;
    }
}

/* Focus States */
.gip-option-btn:focus,
.gip-result-btn:focus,
.gip-chat-send:focus,
.gip-btn-compare:focus,
.gip-feedback-btn:focus {
    outline: 2px solid var(--gip-accent);
    outline-offset: 2px;
}

/* Print Styles */
@media print {
    .gip-chat-input-area,
    .gip-result-actions,
    .gip-result-footer,
    .gip-continue-chat,
    .gip-btn-compare {
        display: none !important;
    }
    
    .gip-result-card {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
';
}

// =============================================================================
// Frontend JavaScript - 完全版
// =============================================================================

function gip_frontend_js() {
    return <<<'ENDJS'
(function($) {
    'use strict';
    
    if (typeof $ === 'undefined') {
        console.error('GIP Chat: jQuery is not loaded');
        return;
    }
    
    var GIPChat = {
        sessionId: null,
        isLoading: false,
        results: [],
        allResults: [],
        displayedCount: 0,
        resultsPerPage: 10,
        selectedForCompare: [],
        canContinue: false,
        allowInput: false,
        initialized: false,
        retryCount: 0,
        maxRetries: 3,
        
        init: function() {
            var self = this;
            
            if (self.initialized) {
                console.log('GIP Chat: Already initialized');
                return;
            }
            
            self.$container = $('.gip-chat');
            if (!self.$container.length) {
                if (self.retryCount < self.maxRetries) {
                    self.retryCount++;
                    console.log('GIP Chat: Container not found, retry ' + self.retryCount);
                    setTimeout(function() { self.init(); }, 500);
                    return;
                }
                console.error('GIP Chat: Container not found after retries');
                return;
            }
            
            console.log('GIP Chat: Initializing v7.0...');
            
            self.$messages = self.$container.find('.gip-chat-messages');
            self.$input = self.$container.find('.gip-chat-input');
            self.$send = self.$container.find('.gip-chat-send');
            self.$results = self.$container.find('.gip-results');
            self.$inputArea = self.$container.find('.gip-chat-input-area');
            
            // 初期状態では入力エリアを非表示
            self.$inputArea.hide();
            
            self.bindEvents();
            self.startSession();
            self.initialized = true;
            
            console.log('GIP Chat: Initialization complete');
        },
        
        bindEvents: function() {
            var self = this;
            
            // 送信ボタン
            self.$send.off('click.gip').on('click.gip', function(e) {
                e.preventDefault();
                self.sendMessage();
            });
            
            // Enterキーで送信
            self.$input.off('keydown.gip').on('keydown.gip', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });
            
            // テキストエリアの自動リサイズ
            self.$input.off('input.gip').on('input.gip', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
            
            // イベント委譲でオプション処理
            $(document)
                .off('.gip')
                .on('click.gip', '.gip-option-btn:not(.gip-continue-btn)', function(e) {
                    e.preventDefault();
                    if (!self.isLoading) {
                        self.handleOptionClick($(this));
                    }
                })
                .on('click.gip', '.gip-continue-btn', function(e) {
                    e.preventDefault();
                    if (!self.isLoading) {
                        var value = $(this).data('value') || $(this).text().trim();
                        self.sendMessage(value);
                    }
                })
                .on('change.gip', '.gip-select', function() {
                    var val = $(this).val();
                    if (val && !self.isLoading) {
                        self.sendSelection(val);
                    }
                })
                .on('click.gip', '.gip-inline-submit', function(e) {
                    e.preventDefault();
                    if (self.isLoading) return;
                    var $input = $(this).siblings('.gip-inline-input');
                    var val = $input.val().trim();
                    if (val) {
                        self.sendSelection(val);
                        $input.val('');
                    }
                })
                .on('keydown.gip', '.gip-inline-input', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (self.isLoading) return;
                        var val = $(this).val().trim();
                        if (val) {
                            self.sendSelection(val);
                            $(this).val('');
                        }
                    }
                })
                .on('click.gip', '.gip-feedback-btn', function(e) {
                    e.preventDefault();
                    self.handleFeedback($(this));
                })
                .on('change.gip', '.gip-compare-checkbox', function() {
                    self.updateCompareSelection();
                })
                .on('click.gip', '.gip-btn-compare', function(e) {
                    e.preventDefault();
                    self.showComparisonTable();
                })
                .on('click.gip', '.gip-comparison-close', function(e) {
                    e.preventDefault();
                    $('.gip-comparison-modal').remove();
                })
                .on('click.gip', '.gip-comparison-modal', function(e) {
                    if (e.target === this) {
                        $(this).remove();
                    }
                })
                .on('click.gip', '.gip-btn-load-more', function(e) {
                    e.preventDefault();
                    self.loadMoreResults();
                })
                .on('click.gip', '.gip-btn-toggle-details', function(e) {
                    e.preventDefault();
                    var $card = $(this).closest('.gip-result-card');
                    var $details = $card.find('.gip-result-details');
                    $details.toggleClass('show');
                    $(this).text($details.hasClass('show') ? '詳細を閉じる' : '詳細を見る');
                })
                .on('click.gip', '.gip-btn-ask-about', function(e) {
                    e.preventDefault();
                    var title = $(this).data('title');
                    var message = '「' + title + '」について詳しく教えてください。';
                    self.sendMessage(message);
                    $('html, body').animate({ scrollTop: self.$messages.offset().top - 100 }, 500);
                });
            
            // ESCキーでモーダルを閉じる
            $(document).on('keydown.gip', function(e) {
                if (e.key === 'Escape') {
                    $('.gip-comparison-modal').remove();
                }
            });
        },
        
        handleOptionClick: function($btn) {
            var self = this;
            if (self.isLoading) return;
            
            $btn.siblings('.gip-option-btn').removeClass('selected');
            $btn.addClass('selected');
            
            setTimeout(function() {
                var value = $btn.data('value') || $btn.text().trim();
                self.sendSelection(value);
            }, 150);
        },
        
        handleFeedback: function($btn) {
            var self = this;
            var $card = $btn.closest('.gip-result-card');
            var grantId = $card.data('grant-id');
            var feedback = $btn.data('feedback');
            
            $card.find('.gip-feedback-btn').removeClass('selected');
            $btn.addClass('selected');
            
            self.sendFeedback(grantId, feedback);
        },
        
        updateCompareSelection: function() {
            var self = this;
            self.selectedForCompare = [];
            
            $('.gip-compare-checkbox:checked').each(function() {
                var grantId = $(this).closest('.gip-result-card').data('grant-id');
                for (var i = 0; i < self.allResults.length; i++) {
                    if (self.allResults[i].grant_id == grantId) {
                        self.selectedForCompare.push(self.allResults[i]);
                        break;
                    }
                }
            });
            
            var $btn = $('.gip-btn-compare');
            var count = self.selectedForCompare.length;
            
            if (count >= 2) {
                $btn.prop('disabled', false).text('選択した' + count + '件を比較');
            } else {
                $btn.prop('disabled', true).text('比較する（2件以上選択）');
            }
        },
        
        showComparisonTable: function() {
            var self = this;
            if (self.selectedForCompare.length < 2) return;
            
            var html = '<div class="gip-comparison-modal" role="dialog" aria-modal="true" aria-labelledby="comparison-title">';
            html += '<div class="gip-comparison-content">';
            html += '<div class="gip-comparison-header">';
            html += '<h3 class="gip-comparison-title" id="comparison-title">補助金比較表</h3>';
            html += '<button type="button" class="gip-comparison-close" aria-label="閉じる">&times;</button>';
            html += '</div>';
            html += '<div class="gip-comparison-body">';
            html += '<table class="gip-comparison-table">';
            
            // ヘッダー行
            html += '<tr><th></th>';
            for (var i = 0; i < self.selectedForCompare.length; i++) {
                var r = self.selectedForCompare[i];
                var shortTitle = r.title.length > 20 ? r.title.substring(0, 20) + '...' : r.title;
                html += '<td class="table-header">' + self.escapeHtml(shortTitle) + '</td>';
            }
            html += '</tr>';
            
            // マッチ度
            html += '<tr><th>マッチ度</th>';
            for (var i = 0; i < self.selectedForCompare.length; i++) {
                html += '<td class="table-score">' + self.selectedForCompare[i].score + '点</td>';
            }
            html += '</tr>';
            
            // 実施機関
            html += '<tr><th>実施機関</th>';
            for (var i = 0; i < self.selectedForCompare.length; i++) {
                html += '<td>' + self.escapeHtml(self.selectedForCompare[i].organization || '-') + '</td>';
            }
            html += '</tr>';
            
            // 補助金額
            html += '<tr><th>補助金額</th>';
            for (var i = 0; i < self.selectedForCompare.length; i++) {
                var r = self.selectedForCompare[i];
                html += '<td><strong>' + self.escapeHtml(r.amount_display || r.max_amount || '要確認') + '</strong></td>';
            }
            html += '</tr>';
            
            // 補助率
            html += '<tr><th>補助率</th>';
            for (var i = 0; i < self.selectedForCompare.length; i++) {
                html += '<td>' + self.escapeHtml(self.selectedForCompare[i].subsidy_rate || '-') + '</td>';
            }
            html += '</tr>';
            
            // 申請締切
            html += '<tr><th>申請締切</th>';
            for (var i = 0; i < self.selectedForCompare.length; i++) {
                var r = self.selectedForCompare[i];
                var deadline = r.deadline_display || r.deadline || '随時';
                var isUrgent = r.days_remaining !== undefined && r.days_remaining <= 14 && r.days_remaining >= 0;
                var style = isUrgent ? 'color:#dc2626;font-weight:700;' : '';
                html += '<td style="' + style + '">' + self.escapeHtml(deadline) + '</td>';
            }
            html += '</tr>';
            
            // 対象地域
            html += '<tr><th>対象地域</th>';
            for (var i = 0; i < self.selectedForCompare.length; i++) {
                var r = self.selectedForCompare[i];
                var prefs = (r.prefectures && r.prefectures.length) ? r.prefectures.slice(0, 3).join(', ') : '全国';
                html += '<td>' + self.escapeHtml(prefs) + '</td>';
            }
            html += '</tr>';
            
            // オンライン申請
            html += '<tr><th>オンライン申請</th>';
            for (var i = 0; i < self.selectedForCompare.length; i++) {
                var r = self.selectedForCompare[i];
                html += '<td>' + (r.online_application ? '<span class="gip-badge gip-badge-success">対応</span>' : '-') + '</td>';
            }
            html += '</tr>';
            
            // 詳細リンク
            html += '<tr><th>詳細</th>';
            for (var i = 0; i < self.selectedForCompare.length; i++) {
                var r = self.selectedForCompare[i];
                html += '<td><a href="' + self.escapeHtml(r.url) + '" target="_blank" rel="noopener" class="gip-result-btn gip-result-btn-primary" style="display:inline-block;padding:8px 16px;font-size:13px;">詳細ページ</a></td>';
            }
            html += '</tr>';
            
            html += '</table></div></div></div>';
            
            $('body').append(html);
            
            // フォーカス管理
            $('.gip-comparison-close').focus();
        },
        
        startSession: function() {
            var self = this;
            console.log('GIP Chat: Starting new session...');
            self.callApi({ message: '' });
        },
        
        sendMessage: function(predefinedMsg) {
            var self = this;
            var msg = predefinedMsg || self.$input.val().trim();
            
            if (!msg || self.isLoading) {
                return;
            }
            
            self.$input.val('').css('height', 'auto');
            self.addMessage('user', msg);
            self.removeOptions();
            
            self.callApi({
                session_id: self.sessionId,
                message: msg
            });
        },
        
        sendSelection: function(value) {
            var self = this;
            
            if (self.isLoading || !value) {
                return;
            }
            
            self.addMessage('user', value);
            self.removeOptions();
            
            self.callApi({
                session_id: self.sessionId,
                selection: value
            });
        },
        
        removeOptions: function() {
            var self = this;
            self.$messages.find('.gip-options, .gip-select-wrap, .gip-hint, .gip-input-inline').remove();
        },
        
        callApi: function(data) {
            var self = this;
            
            if (self.isLoading) {
                console.log('GIP Chat: Request blocked - already loading');
                return;
            }
            
            self.isLoading = true;
            self.$send.prop('disabled', true);
            self.showTyping();
            
            if (typeof GIP_CHAT === 'undefined' || !GIP_CHAT.api) {
                console.error('GIP Chat: API configuration missing');
                self.hideTyping();
                self.addMessage('bot', 'システムエラーが発生しました。ページを再読み込みしてください。');
                self.isLoading = false;
                self.$send.prop('disabled', false);
                return;
            }
            
            console.log('GIP Chat: API call', data);
            
            $.ajax({
                url: GIP_CHAT.api + '/chat',
                method: 'POST',
                contentType: 'application/json',
                headers: {
                    'X-WP-Nonce': GIP_CHAT.nonce
                },
                data: JSON.stringify(data),
                timeout: 60000,
                success: function(response) {
                    console.log('GIP Chat: Response', response);
                    self.hideTyping();
                    
                    if (response && response.success) {
                        self.sessionId = response.session_id;
                        self.canContinue = response.can_continue || false;
                        self.allowInput = response.allow_input || false;
                        
                        self.addMessage('bot', response.message);
                        
                        if (response.hint) {
                            self.showHint(response.hint);
                        }
                        
                        if (response.options && response.options.length > 0) {
                            self.showOptions(response.options, response.option_type);
                        }
                        
                        if (response.allow_input && response.option_type !== 'prefecture_select' && response.option_type !== 'municipality_select') {
                            self.showInlineInput();
                        }
                        
                        if (response.results && response.results.length > 0) {
                            self.allResults = response.results;
                            self.displayedCount = 0;
                            self.selectedForCompare = [];
                            self.renderResults(response.show_comparison);
                            
                            if (self.canContinue) {
                                self.showContinueOptions();
                            }
                        }
                        
                        self.updateInputState();
                        
                    } else {
                        var errorMsg = (response && response.error) ? response.error : 'エラーが発生しました。';
                        self.addMessage('bot', errorMsg + '\n\nページを更新してやり直してください。');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('GIP Chat: Error', status, error);
                    self.hideTyping();
                    
                    var errorMsg = '通信エラーが発生しました。';
                    if (status === 'timeout') {
                        errorMsg = 'リクエストがタイムアウトしました。もう一度お試しください。';
                    } else if (xhr.status === 0) {
                        errorMsg = 'ネットワーク接続を確認してください。';
                    } else if (xhr.status >= 500) {
                        errorMsg = 'サーバーエラーが発生しました。しばらく待ってからお試しください。';
                    }
                    
                    self.addMessage('bot', errorMsg);
                },
                complete: function() {
                    self.isLoading = false;
                    self.$send.prop('disabled', false);
                }
            });
        },
        
        updateInputState: function() {
            var self = this;
            
            if (self.canContinue || self.allowInput) {
                self.$inputArea.slideDown(200);
                self.$input.attr('placeholder', '質問を入力してください...');
            } else {
                var hasOptions = self.$messages.find('.gip-options, .gip-select-wrap').length > 0;
                if (hasOptions) {
                    self.$inputArea.hide();
                }
            }
        },
        
        addMessage: function(role, text) {
            var self = this;
            var avatarText = role === 'bot' ? 'AI' : 'You';
            
            var html = '<div class="gip-message gip-message-' + role + '">';
            html += '<div class="gip-message-avatar">' + avatarText + '</div>';
            html += '<div class="gip-message-content">';
            html += '<div class="gip-message-bubble">' + self.escapeHtml(text) + '</div>';
            html += '</div></div>';
            
            self.$messages.append(html);
            self.scrollToBottom();
        },
        
        showHint: function(hint) {
            var self = this;
            var $lastMessage = self.$messages.find('.gip-message:last .gip-message-content');
            var html = '<div class="gip-hint">' + self.escapeHtml(hint) + '</div>';
            $lastMessage.append(html);
        },
        
        showOptions: function(options, type) {
            var self = this;
            var html = '';
            var $lastMessage = self.$messages.find('.gip-message:last .gip-message-content');
            
            if (type === 'prefecture_select' || type === 'municipality_select') {
                html = '<div class="gip-select-wrap">';
                html += '<select class="gip-select" aria-label="選択してください"><option value="">選択してください</option>';
                for (var i = 0; i < options.length; i++) {
                    var opt = options[i];
                    html += '<option value="' + self.escapeHtml(opt.label) + '">' + self.escapeHtml(opt.label) + '</option>';
                }
                html += '</select></div>';
                
                html += '<div class="gip-input-inline">';
                html += '<input type="text" class="gip-inline-input" placeholder="または直接入力..." aria-label="直接入力">';
                html += '<button type="button" class="gip-inline-submit">送信</button>';
                html += '</div>';
            } else {
                html = '<div class="gip-options" role="group">';
                for (var i = 0; i < options.length; i++) {
                    var opt = options[i];
                    html += '<button type="button" class="gip-option-btn" data-value="' + self.escapeHtml(opt.label) + '">' + self.escapeHtml(opt.label) + '</button>';
                }
                html += '</div>';
            }
            
            $lastMessage.append(html);
            self.scrollToBottom();
        },
        
        showInlineInput: function() {
            var self = this;
            var $lastMessage = self.$messages.find('.gip-message:last .gip-message-content');
            
            if ($lastMessage.find('.gip-input-inline').length > 0) {
                return;
            }
            
            var html = '<div class="gip-input-inline">';
            html += '<input type="text" class="gip-inline-input" placeholder="自由に入力できます..." aria-label="自由入力">';
            html += '<button type="button" class="gip-inline-submit">送信</button>';
            html += '</div>';
            
            $lastMessage.append(html);
        },
        
        showTyping: function() {
            var self = this;
            
            var html = '<div class="gip-message gip-message-bot gip-message-typing-wrap">';
            html += '<div class="gip-message-avatar">AI</div>';
            html += '<div class="gip-message-content">';
            html += '<div class="gip-message-typing" aria-label="入力中">';
            html += '<div class="gip-typing-dot"></div>';
            html += '<div class="gip-typing-dot"></div>';
            html += '<div class="gip-typing-dot"></div>';
            html += '</div></div></div>';
            
            self.$messages.append(html);
            self.scrollToBottom();
        },
        
        hideTyping: function() {
            this.$messages.find('.gip-message-typing-wrap').remove();
        },
        
        renderResults: function(showComparison) {
            var self = this;
            var resultsToShow = self.allResults.slice(0, self.resultsPerPage);
            self.displayedCount = resultsToShow.length;
            
            var html = '<div class="gip-results-header">';
            html += '<div>';
            html += '<h3 class="gip-results-title">🎯 検索結果</h3>';
            html += '<p class="gip-results-count">全' + self.allResults.length + '件の補助金が見つかりました</p>';
            html += '</div>';
            
            if (showComparison && self.allResults.length >= 2) {
                html += '<button type="button" class="gip-btn-compare" disabled>比較する（2件以上選択）</button>';
            }
            html += '</div>';
            
            html += '<div class="gip-results-grid">';
            for (var i = 0; i < resultsToShow.length; i++) {
                html += self.renderResultCard(resultsToShow[i], i);
            }
            html += '</div>';
            
            if (self.allResults.length > self.displayedCount) {
                var remaining = self.allResults.length - self.displayedCount;
                html += '<div class="gip-load-more">';
                html += '<button type="button" class="gip-btn-load-more">さらに表示（残り' + remaining + '件）</button>';
                html += '</div>';
            }
            
            self.$results.html(html).slideDown(300);
            
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: self.$results.offset().top - 100
                }, 500);
            }, 100);
        },
        
        renderResultCard: function(r, index) {
            var self = this;
            var rankClass = '';
            
            if (index === 0) rankClass = ' gip-result-rank-1';
            else if (index === 1) rankClass = ' gip-result-rank-2';
            else if (index === 2) rankClass = ' gip-result-rank-3';
            
            var highlightClass = index < 3 ? ' highlight' : '';
            
            var prefDisplay = '';
            if (r.prefectures && r.prefectures.length > 0) {
                prefDisplay = r.prefectures.slice(0, 2).join(', ');
                if (r.prefectures.length > 2) prefDisplay += ' 他';
            }
            
            var html = '<div class="gip-result-card' + highlightClass + '" data-grant-id="' + r.grant_id + '">';
            
            // ヘッダー
            html += '<div class="gip-result-header">';
            html += '<div class="gip-result-rank' + rankClass + '">' + (index + 1) + '</div>';
            html += '<div class="gip-result-info">';
            html += '<h4 class="gip-result-title">' + self.escapeHtml(r.title) + '</h4>';
            html += '<div class="gip-result-org">';
            if (r.organization) {
                html += '<span>' + self.escapeHtml(r.organization) + '</span>';
            }
            if (prefDisplay) {
                html += '<span class="gip-result-prefecture">' + self.escapeHtml(prefDisplay) + '</span>';
            }
            if (r.days_remaining !== undefined && r.days_remaining <= 14 && r.days_remaining >= 0) {
                html += '<span class="gip-badge gip-badge-error">残り' + r.days_remaining + '日</span>';
            }
            html += '</div></div>';
            
            html += '<div class="gip-result-score">';
            html += '<div class="gip-result-score-value">' + r.score + '</div>';
            html += '<div class="gip-result-score-label">マッチ度</div>';
            html += '</div></div>';
            
            // ボディ
            html += '<div class="gip-result-body">';
            
            if (r.reason) {
                html += '<div class="gip-result-reason">💡 ' + self.escapeHtml(r.reason) + '</div>';
            }
            
            html += '<div class="gip-result-meta">';
            
            var amountDisplay = r.amount_display || r.max_amount;
            if (amountDisplay) {
                html += '<div class="gip-result-meta-item">';
                html += '<span class="gip-result-meta-label">💰 補助金額</span>';
                html += '<span class="gip-result-meta-value highlight">' + self.escapeHtml(amountDisplay) + '</span>';
                html += '</div>';
            }
            
            if (r.subsidy_rate) {
                html += '<div class="gip-result-meta-item">';
                html += '<span class="gip-result-meta-label">📊 補助率</span>';
                html += '<span class="gip-result-meta-value">' + self.escapeHtml(r.subsidy_rate) + '</span>';
                html += '</div>';
            }
            
            var deadlineDisplay = r.deadline_display || r.deadline;
            if (deadlineDisplay) {
                html += '<div class="gip-result-meta-item">';
                html += '<span class="gip-result-meta-label">📅 申請締切</span>';
                html += '<span class="gip-result-meta-value">' + self.escapeHtml(deadlineDisplay) + '</span>';
                html += '</div>';
            }
            
            if (r.online_application || r.jgrants_available) {
                html += '<div class="gip-result-meta-item">';
                html += '<span class="gip-result-meta-label">📝 申請方法</span>';
                html += '<span class="gip-result-meta-value">';
                if (r.online_application) html += '<span class="gip-badge gip-badge-success">オンライン可</span> ';
                if (r.jgrants_available) html += '<span class="gip-badge gip-badge-info">jGrants</span>';
                html += '</span></div>';
            }
            
            html += '</div>';
            
            var summary = r.ai_summary || r.excerpt;
            if (summary) {
                html += '<div class="gip-result-excerpt">' + self.escapeHtml(summary) + '</div>';
            }
            
            // 詳細セクション
            html += '<div class="gip-result-details">';
            if (r.grant_target) {
                html += '<div class="gip-result-details-section">';
                html += '<div class="gip-result-details-title">👥 対象者</div>';
                html += '<div class="gip-result-details-content">' + self.escapeHtml(r.grant_target.substring(0, 500)) + '</div>';
                html += '</div>';
            }
            if (r.eligible_expenses) {
                html += '<div class="gip-result-details-section">';
                html += '<div class="gip-result-details-title">📋 対象経費</div>';
                html += '<div class="gip-result-details-content">' + self.escapeHtml(r.eligible_expenses.substring(0, 500)) + '</div>';
                html += '</div>';
            }
            if (r.required_documents) {
                html += '<div class="gip-result-details-section">';
                html += '<div class="gip-result-details-title">📄 必要書類</div>';
                html += '<div class="gip-result-details-content">' + self.escapeHtml(r.required_documents.substring(0, 500)) + '</div>';
                html += '</div>';
            }
            html += '</div>';
            
            // アクションボタン
            html += '<div class="gip-result-actions">';
            html += '<a href="' + self.escapeHtml(r.url) + '" class="gip-result-btn gip-result-btn-primary" target="_blank" rel="noopener">詳細ページを見る →</a>';
            html += '<button type="button" class="gip-result-btn gip-result-btn-secondary gip-btn-toggle-details">詳細を見る</button>';
            html += '<button type="button" class="gip-result-btn gip-result-btn-secondary gip-btn-ask-about" data-grant-id="' + r.grant_id + '" data-title="' + self.escapeHtml(r.title) + '">この補助金について質問</button>';
            html += '</div></div>';
            
            // フッター
            html += '<div class="gip-result-footer">';
            html += '<label class="gip-compare-check">';
            html += '<input type="checkbox" class="gip-compare-checkbox">';
            html += '<span>比較リストに追加</span>';
            html += '</label>';
            html += '<div class="gip-feedback-btns">';
            html += '<span class="gip-feedback-label">参考になった?</span>';
            html += '<button type="button" class="gip-feedback-btn" data-feedback="positive">👍 はい</button>';
            html += '<button type="button" class="gip-feedback-btn" data-feedback="negative">👎 いいえ</button>';
            html += '</div></div>';
            
            html += '</div>';
            
            return html;
        },
        
        loadMoreResults: function() {
            var self = this;
            var nextResults = self.allResults.slice(self.displayedCount, self.displayedCount + self.resultsPerPage);
            
            var cardsHtml = '';
            for (var i = 0; i < nextResults.length; i++) {
                cardsHtml += self.renderResultCard(nextResults[i], self.displayedCount + i);
            }
            
            self.$results.find('.gip-results-grid').append(cardsHtml);
            self.displayedCount += nextResults.length;
            
            var remaining = self.allResults.length - self.displayedCount;
            if (remaining > 0) {
                self.$results.find('.gip-btn-load-more').text('さらに表示（残り' + remaining + '件）');
            } else {
                self.$results.find('.gip-load-more').remove();
            }
        },
        
        showContinueOptions: function() {
            var self = this;
            
            var html = '<div class="gip-continue-chat">';
            html += '<p class="gip-continue-title">💬 さらに詳しく知りたいことはありますか?</p>';
            html += '<div class="gip-continue-options">';
            html += '<button type="button" class="gip-option-btn gip-continue-btn" data-value="1位の補助金について詳しく教えてください">1位の補助金について詳しく</button>';
            html += '<button type="button" class="gip-option-btn gip-continue-btn" data-value="申請方法を教えてください">申請方法を教えて</button>';
            html += '<button type="button" class="gip-option-btn gip-continue-btn" data-value="必要書類を教えてください">必要書類を教えて</button>';
            html += '<button type="button" class="gip-option-btn gip-continue-btn" data-value="採択率を上げるコツを教えてください">採択率アップのコツ</button>';
            html += '<button type="button" class="gip-option-btn gip-continue-btn" data-value="条件を変えて他の補助金も探したい">他の補助金も探す</button>';
            html += '</div></div>';
            
            self.$results.append(html);
        },
        
        sendFeedback: function(grantId, feedback) {
            var self = this;
            
            if (!self.sessionId || !grantId) return;
            
            $.ajax({
                url: GIP_CHAT.api + '/feedback',
                method: 'POST',
                contentType: 'application/json',
                headers: {
                    'X-WP-Nonce': GIP_CHAT.nonce
                },
                data: JSON.stringify({
                    session_id: self.sessionId,
                    grant_id: grantId,
                    feedback: feedback
                }),
                success: function(response) {
                    console.log('GIP Chat: Feedback sent', response);
                },
                error: function(xhr, status, error) {
                    console.error('GIP Chat: Feedback error', status, error);
                }
            });
        },
        
        scrollToBottom: function() {
            var self = this;
            var el = self.$messages[0];
            
            if (el) {
                setTimeout(function() {
                    el.scrollTop = el.scrollHeight;
                }, 50);
            }
        },
        
        escapeHtml: function(str) {
            if (str === null || str === undefined) return '';
            
            var entityMap = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            };
            
            return String(str).replace(/[&<>"']/g, function(s) {
                return entityMap[s];
            });
        }
    };
    
    // DOM準備完了時に初期化
    $(function() {
        console.log('GIP Chat: Document ready');
        
        setTimeout(function() {
            GIPChat.init();
        }, 100);
    });
    
    // グローバル公開（デバッグ用）
    window.GIPChat = GIPChat;
    
})(jQuery);
ENDJS;
}

// =============================================================================
// Shortcode
// =============================================================================

add_shortcode('gip_chat', 'gip_shortcode_chat');

function gip_shortcode_chat($atts = array()) {
    $atts = shortcode_atts(array(
        'title' => '補助金・助成金コンシェルジュ',
        'subtitle' => 'あなたに最適な支援制度をAIがご案内します',
    ), $atts);
    
    if (!wp_script_is('gip-chat-script', 'enqueued')) {
        gip_frontend_assets();
    }
    
    ob_start();
    ?>
    <div class="gip-chat" role="application" aria-label="補助金診断チャット">
        <div class="gip-chat-header">
            <div class="gip-chat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </div>
            <div class="gip-chat-header-text">
                <h3><?php echo esc_html($atts['title']); ?></h3>
                <p><?php echo esc_html($atts['subtitle']); ?></p>
            </div>
        </div>
        <div class="gip-chat-messages" role="log" aria-live="polite" aria-label="チャット履歴"></div>
        <div class="gip-chat-input-area">
            <div class="gip-chat-input-wrap">
                <textarea class="gip-chat-input" placeholder="メッセージを入力..." rows="1" aria-label="メッセージ入力"></textarea>
                <button class="gip-chat-send" aria-label="送信">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </div>
        <div class="gip-results" style="display:none" role="region" aria-label="検索結果"></div>
    </div>
    <?php
    return ob_get_clean();
}