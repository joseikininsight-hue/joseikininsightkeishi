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

define('GIP_VERSION', '7.2.0');
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
    
    // 都道府県の正規化
    $pref_base = preg_replace('/(都|道|府|県)$/', '', $prefecture_name);
    
    // 東京都の区市町村リスト（ハードコード - 確実なフィルタリング用）
    $tokyo_municipalities = array(
        '千代田区', '中央区', '港区', '新宿区', '文京区', '台東区', '墨田区', '江東区',
        '品川区', '目黒区', '大田区', '世田谷区', '渋谷区', '中野区', '杉並区', '豊島区',
        '北区', '荒川区', '板橋区', '練馬区', '足立区', '葛飾区', '江戸川区',
        '八王子市', '立川市', '武蔵野市', '三鷹市', '青梅市', '府中市', '昭島市', '調布市',
        '町田市', '小金井市', '小平市', '日野市', '東村山市', '国分寺市', '国立市', '福生市',
        '狛江市', '東大和市', '清瀬市', '東久留米市', '武蔵村山市', '多摩市', '稲城市', '羽村市',
        'あきる野市', '西東京市',
        '瑞穂町', '日の出町', '檜原村', '奥多摩町', '大島町', '利島村', '新島村', '神津島村',
        '三宅村', '御蔵島村', '八丈町', '青ヶ島村', '小笠原村',
    );
    
    // 大阪府の市町村リスト
    $osaka_municipalities = array(
        '大阪市', '堺市', '岸和田市', '豊中市', '池田市', '吹田市', '泉大津市', '高槻市',
        '貝塚市', '守口市', '枚方市', '茨木市', '八尾市', '泉佐野市', '富田林市', '寝屋川市',
        '河内長野市', '松原市', '大東市', '和泉市', '箕面市', '柏原市', '羽曳野市', '門真市',
        '摂津市', '高石市', '藤井寺市', '東大阪市', '泉南市', '四條畷市', '交野市', '大阪狭山市',
        '阪南市', '島本町', '豊能町', '能勢町', '忠岡町', '熊取町', '田尻町', '岬町', '太子町',
        '河南町', '千早赤阪村',
    );
    
    // 他の都道府県で重複しやすい市区町村名をブラックリスト化
    // （都道府県名から判定できない「同名市区町村」を除外）
    $common_municipality_conflicts = array(
        '北区' => array('東京', '大阪', '名古屋', '札幌', '京都', '神戸', '新潟', '浜松', '堺', '岡山', '横浜'),
        '中央区' => array('東京', '大阪', '札幌', '福岡', '千葉', '新潟', '神戸', '相模原'),
        '南区' => array('横浜', '名古屋', '札幌', '京都', '神戸', '堺', '浜松', '岡山', '福岡', '相模原', '新潟'),
        '西区' => array('横浜', '大阪', '名古屋', '札幌', '神戸', '堺', '浜松', '新潟', '福岡'),
        '東区' => array('名古屋', '札幌', '福岡', '堺', '新潟', '浜松', '岡山'),
        '港区' => array('東京', '大阪', '名古屋'),
        '緑区' => array('横浜', '名古屋', '千葉', '相模原', '埼玉'),
        '青葉区' => array('横浜', '仙台'),
        '旭区' => array('横浜', '大阪'),
        '栄区' => array('横浜'),
        '瀬谷区' => array('横浜'),
        '鶴見区' => array('横浜', '大阪'),
        '戸塚区' => array('横浜'),
        '中区' => array('横浜', '名古屋', '広島', '浜松', '岡山', '堺'),
        '保土ケ谷区' => array('横浜'),
        '磯子区' => array('横浜'),
        '神奈川区' => array('横浜'),
        '金沢区' => array('横浜'),
        '泉区' => array('横浜', '仙台'),
        '都筑区' => array('横浜'),
        '港北区' => array('横浜'),
        '緑区' => array('横浜', '名古屋', '千葉', '相模原'),
    );
    
    // 特定都道府県のハードコードリスト
    $valid_municipalities = array();
    if ($pref_base === '東京') {
        $valid_municipalities = $tokyo_municipalities;
    } elseif ($pref_base === '大阪') {
        $valid_municipalities = $osaka_municipalities;
    }
    
    // Posts Relation Check
    $pref_term = get_term_by('name', $prefecture_name, 'grant_prefecture');
    if (!$pref_term) {
        $pref_term = get_term_by('slug', sanitize_title($prefecture_name), 'grant_prefecture');
    }
    if (!$pref_term) {
        $pref_term = get_term_by('name', $pref_base, 'grant_prefecture');
    }
    
    if ($pref_term) {
        // メモリ最適化: 全件取得せず、最大100件に制限
        $grant_ids = get_posts(array(
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => 100, // メモリ節約のため制限
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
                        // ハードコードリストで検証（東京・大阪）
                        if (!empty($valid_municipalities)) {
                            $is_valid = false;
                            foreach ($valid_municipalities as $valid_muni) {
                                if ($muni->name === $valid_muni || 
                                    strpos($muni->name, preg_replace('/(市|区|町|村)$/', '', $valid_muni)) !== false) {
                                    $is_valid = true;
                                    break;
                                }
                            }
                            if (!$is_valid) {
                                continue;
                            }
                        } else {
                            // 他の都道府県の場合：重複市区町村名のフィルタリング
                            $muni_name = $muni->name;
                            if (isset($common_municipality_conflicts[$muni_name])) {
                                // 同名市区町村がある場合、この都道府県に属するか確認
                                $allowed_prefs = $common_municipality_conflicts[$muni_name];
                                if (!in_array($pref_base, $allowed_prefs)) {
                                    // この都道府県にない同名市区町村は除外
                                    continue;
                                }
                            }
                            
                            // term_metaの親都道府県をチェック
                            $parent_pref = get_term_meta($muni->term_id, 'parent_prefecture', true);
                            if (!empty($parent_pref) && $parent_pref !== $prefecture_name && $parent_pref !== $pref_base) {
                                // 明確に他の都道府県に属している場合は除外
                                continue;
                            }
                            
                            // 市区町村のdescriptionに都道府県情報があればチェック
                            if (!empty($muni->description)) {
                                $desc_prefs = gip_get_prefectures();
                                foreach ($desc_prefs as $dp) {
                                    $dp_base = preg_replace('/(都|道|府|県)$/', '', $dp);
                                    if ($dp_base !== $pref_base && 
                                        (mb_strpos($muni->description, $dp) !== false || 
                                         mb_strpos($muni->description, $dp_base) === 0)) {
                                        // 他の都道府県名がdescriptionに含まれている場合は除外
                                        continue 2;
                                    }
                                }
                            }
                        }
                        
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
    
    // ハードコードリストからのフォールバック
    if (empty($municipalities) && !empty($valid_municipalities)) {
        foreach ($valid_municipalities as $muni_name) {
            $municipalities[] = array(
                'name' => $muni_name,
                'slug' => sanitize_title($muni_name),
                'count' => 0,
            );
        }
        return $municipalities;
    }
    
    // その他の都道府県用フォールバック（より厳密なフィルタリング）
    if (empty($municipalities)) {
        $all_munis = get_terms(array(
            'taxonomy' => 'grant_municipality',
            'hide_empty' => true,
            'number' => 200,
        ));
        
        if (!is_wp_error($all_munis)) {
            foreach ($all_munis as $muni) {
                $parent_pref = get_term_meta($muni->term_id, 'parent_prefecture', true);
                
                // 親都道府県が明確に設定されている場合
                if (!empty($parent_pref)) {
                    $parent_base = preg_replace('/(都|道|府|県)$/', '', $parent_pref);
                    if ($parent_pref === $prefecture_name || $parent_base === $pref_base) {
                        $municipalities[$muni->term_id] = array(
                            'name' => $muni->name,
                            'slug' => $muni->slug,
                            'count' => $muni->count,
                        );
                    }
                    continue;
                }
                
                // descriptionで判定（先頭が都道府県名で始まる場合）
                if (!empty($muni->description)) {
                    if (mb_strpos($muni->description, $prefecture_name) === 0 ||
                        mb_strpos($muni->description, $pref_base) === 0) {
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
add_action('wp_ajax_gip_init_tables', 'gip_ajax_init_tables');
add_action('wp_ajax_gip_get_municipalities', 'gip_ajax_get_municipalities');
add_action('wp_ajax_nopriv_gip_get_municipalities', 'gip_ajax_get_municipalities');
add_action('wp_ajax_gip_get_indexed_count', 'gip_ajax_get_indexed_count');

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
    
    // 質問ログテーブル（改善分析用）- 強化版
    dbDelta("CREATE TABLE " . gip_table('question_logs') . " (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        session_id varchar(64) NOT NULL,
        user_type varchar(50),
        prefecture varchar(50),
        municipality varchar(100),
        purpose text,
        clarification text,
        detected_category varchar(50),
        matched_grant_id bigint(20) unsigned,
        result_count int(5) DEFAULT 0,
        result_grant_ids text,
        result_grant_titles text,
        user_feedback varchar(20),
        satisfaction_score int(2),
        raw_input text,
        conversation_history longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY session_id (session_id),
        KEY detected_category (detected_category),
        KEY created_at (created_at),
        KEY user_feedback (user_feedback)
    ) $charset;");
    
    // ユーザーフィードバックテーブル（改善コメント保存用）
    dbDelta("CREATE TABLE " . gip_table('user_feedbacks') . " (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        session_id varchar(64) NOT NULL,
        grant_id bigint(20) unsigned,
        feedback_type varchar(20) NOT NULL,
        rating int(2),
        comment text,
        suggestion text,
        user_email varchar(255),
        ip_address varchar(45),
        user_agent varchar(255),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY session_id (session_id),
        KEY grant_id (grant_id),
        KEY feedback_type (feedback_type),
        KEY created_at (created_at)
    ) $charset;");
    
    // 会話履歴テーブル（戻る機能用）
    dbDelta("CREATE TABLE " . gip_table('conversation_states') . " (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        session_id varchar(64) NOT NULL,
        step_number int(3) NOT NULL,
        step_name varchar(50) NOT NULL,
        context_snapshot longtext NOT NULL,
        user_input text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY session_id (session_id),
        KEY step_number (step_number)
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
    add_submenu_page('gip-admin', '質問ログ分析', '質問ログ分析', 'manage_options', 'gip-question-logs', 'gip_page_question_logs');
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
                        var statusMsg = r.data.done + ' / ' + r.data.total + ' 件完了';
                        if (r.data.skipped > 0) {
                            statusMsg += ' (スキップ: ' + r.data.skipped + '件)';
                        }
                        status.text(statusMsg);
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
        // インデックス済み件数を取得して途中から再開
        $.ajax({
            url: GIP.ajax, type: 'POST',
            data: { action: 'gip_get_indexed_count', nonce: GIP.nonce },
            success: function(r) {
                // last_indexed_offset を使用（投稿ID順での最後の処理位置）
                var startOffset = (r.success && r.data.last_indexed_offset) ? r.data.last_indexed_offset : 0;
                var indexedCount = (r.success && r.data.indexed_count) ? r.data.indexed_count : 0;
                if (startOffset > 0) {
                    status.text(indexedCount + '件完了済み。続きから再開...');
                }
                buildBatch(startOffset);
            },
            error: function() { buildBatch(0); }
        });
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
// 質問ログ分析ページ（管理画面用）
// =============================================================================

function gip_page_question_logs() {
    global $wpdb;
    $logs_table = gip_table('question_logs');
    $table_exists = gip_table_exists('question_logs');
    
    // ページネーション
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // フィルター
    $category_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
    $feedback_filter = isset($_GET['feedback']) ? sanitize_text_field($_GET['feedback']) : '';
    
    ?>
    <div class="wrap gip-wrap">
        <div class="gip-header">
            <h1>質問ログ分析</h1>
            <p>ユーザーの質問を分析して、AIの精度向上に活用します</p>
        </div>
        
        <?php if (!$table_exists): ?>
        <div class="notice notice-warning" style="padding: 15px;">
            <p style="margin: 0 0 10px 0;"><strong>質問ログテーブルがまだ作成されていません。</strong></p>
            <p style="margin: 0;">
                <button type="button" id="gip-init-tables" class="button button-primary" onclick="gipInitTables()">
                    テーブルを初期化する
                </button>
                <span id="gip-init-result" style="margin-left: 10px;"></span>
            </p>
        </div>
        <script>
        function gipInitTables() {
            var btn = document.getElementById('gip-init-tables');
            var result = document.getElementById('gip-init-result');
            btn.disabled = true;
            btn.textContent = '初期化中...';
            result.textContent = '';
            
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'gip_init_tables',
                    nonce: '<?php echo wp_create_nonce('gip_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        result.innerHTML = '<span style="color: green;">初期化完了 ページを再読み込みします...</span>';
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        result.innerHTML = '<span style="color: red;">エラー: ' + (response.data || '不明なエラー') + '</span>';
                        btn.disabled = false;
                        btn.textContent = 'テーブルを初期化する';
                    }
                },
                error: function() {
                    result.innerHTML = '<span style="color: red;">通信エラーが発生しました</span>';
                    btn.disabled = false;
                    btn.textContent = 'テーブルを初期化する';
                }
            });
        }
        </script>
        <?php else: 
            // 統計情報取得
            $total_logs = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$logs_table}");
            $today_logs = (int)$wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$logs_table} WHERE DATE(created_at) = %s",
                current_time('Y-m-d')
            ));
            
            // カテゴリ別統計
            $category_stats = $wpdb->get_results(
                "SELECT detected_category, COUNT(*) as count FROM {$logs_table} 
                 WHERE detected_category IS NOT NULL AND detected_category != '' 
                 GROUP BY detected_category ORDER BY count DESC LIMIT 10"
            );
            
            // フィードバック統計
            $feedback_stats = $wpdb->get_results(
                "SELECT user_feedback, COUNT(*) as count FROM {$logs_table} 
                 WHERE user_feedback IS NOT NULL 
                 GROUP BY user_feedback ORDER BY count DESC"
            );
            
            // 満足度平均
            $avg_satisfaction = $wpdb->get_var(
                "SELECT AVG(satisfaction_score) FROM {$logs_table} WHERE satisfaction_score IS NOT NULL"
            );
        ?>
        
        <!-- 統計サマリー -->
        <div class="gip-stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
            <div class="gip-card">
                <div class="gip-card-body" style="text-align: center; padding: 20px;">
                    <div style="font-size: 32px; font-weight: bold; color: #2563eb;"><?php echo number_format($total_logs); ?></div>
                    <div style="color: #6b7280; margin-top: 4px;">総質問数</div>
                </div>
            </div>
            <div class="gip-card">
                <div class="gip-card-body" style="text-align: center; padding: 20px;">
                    <div style="font-size: 32px; font-weight: bold; color: #059669;"><?php echo number_format($today_logs); ?></div>
                    <div style="color: #6b7280; margin-top: 4px;">本日の質問</div>
                </div>
            </div>
            <div class="gip-card">
                <div class="gip-card-body" style="text-align: center; padding: 20px;">
                    <div style="font-size: 32px; font-weight: bold; color: #d97706;">
                        <?php echo $avg_satisfaction ? number_format($avg_satisfaction, 1) : '-'; ?>
                    </div>
                    <div style="color: #6b7280; margin-top: 4px;">平均満足度</div>
                </div>
            </div>
            <div class="gip-card">
                <div class="gip-card-body" style="text-align: center; padding: 20px;">
                    <div style="font-size: 32px; font-weight: bold; color: #7c3aed;">
                        <?php echo count($category_stats); ?>
                    </div>
                    <div style="color: #6b7280; margin-top: 4px;">検出カテゴリ数</div>
                </div>
            </div>
        </div>
        
        <!-- カテゴリ別グラフ -->
        <div class="gip-card" style="margin-bottom: 24px;">
            <div class="gip-card-header"><h2 class="gip-card-title">カテゴリ別質問分布</h2></div>
            <div class="gip-card-body">
                <?php if ($category_stats): ?>
                <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                    <?php 
                    $category_labels = array(
                        'childcare' => '子育て・出産',
                        'housing' => '住宅',
                        'medical_welfare' => '医療・福祉',
                        'education' => '教育・学習',
                        'senior' => 'シニア向け',
                        'it_digital' => 'IT・デジタル',
                        'equipment' => '設備投資',
                        'hr_employment' => '人材・雇用',
                        'startup' => '創業・起業',
                        'sales' => '販路開拓',
                        'energy' => '省エネ・環境',
                        'local_culture' => '地域・文化',
                        'agriculture' => '農業・林業・水産',
                        'tourism' => '観光・宿泊',
                        'npo' => 'NPO・市民活動',
                        'disaster' => '防災・安全',
                        'research' => '研究開発',
                        'default' => 'その他',
                    );
                    foreach ($category_stats as $cat): 
                        $label = $category_labels[$cat->detected_category] ?? $cat->detected_category;
                    ?>
                    <div style="background: #f3f4f6; padding: 8px 16px; border-radius: 8px;">
                        <span style="font-weight: 600;"><?php echo esc_html($label); ?></span>
                        <span style="color: #2563eb; margin-left: 8px;"><?php echo number_format($cat->count); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="color: #6b7280;">まだデータがありません。</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- フィードバック分析 -->
        <div class="gip-card" style="margin-bottom: 24px;">
            <div class="gip-card-header"><h2 class="gip-card-title">ユーザーフィードバック分析</h2></div>
            <div class="gip-card-body">
                <?php if ($feedback_stats): ?>
                <div style="display: flex; gap: 24px;">
                    <?php 
                    $feedback_labels = array(
                        'positive' => '参考になった',
                        'negative' => '期待と違った',
                        'close' => '近い（精度向上に貢献）',
                        'different' => '違う（改善が必要）',
                        'helpful' => '役に立った',
                        'not_helpful' => '役に立たなかった',
                        'yes' => '正解',
                        'no' => '不正解',
                    );
                    $feedback_colors = array(
                        'positive' => '#059669',
                        'negative' => '#dc2626',
                        'close' => '#059669',
                        'different' => '#dc2626',
                        'helpful' => '#059669',
                        'not_helpful' => '#dc2626',
                        'yes' => '#2563eb',
                        'no' => '#d97706',
                    );
                    foreach ($feedback_stats as $fb): 
                        $label = $feedback_labels[$fb->user_feedback] ?? $fb->user_feedback;
                        $color = $feedback_colors[$fb->user_feedback] ?? '#6b7280';
                    ?>
                    <div style="text-align: center;">
                        <div style="font-size: 24px; font-weight: bold; color: <?php echo $color; ?>;">
                            <?php echo number_format($fb->count); ?>
                        </div>
                        <div style="color: #6b7280; font-size: 14px;"><?php echo esc_html($label); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p style="color: #6b7280;">まだフィードバックデータがありません。</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- フィルター -->
        <div class="gip-card" style="margin-bottom: 24px;">
            <div class="gip-card-header"><h2 class="gip-card-title">質問ログ一覧</h2></div>
            <div class="gip-card-body">
                <form method="get" style="margin-bottom: 16px; display: flex; gap: 12px; align-items: center;">
                    <input type="hidden" name="page" value="gip-question-logs">
                    <select name="category" class="gip-input" style="width: auto;">
                        <option value="">全カテゴリ</option>
                        <?php foreach ($category_labels as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($category_filter, $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="feedback" class="gip-input" style="width: auto;">
                        <option value="">全フィードバック</option>
                        <option value="positive" <?php selected($feedback_filter, 'positive'); ?>>参考になった</option>
                        <option value="negative" <?php selected($feedback_filter, 'negative'); ?>>期待と違った</option>
                        <option value="helpful" <?php selected($feedback_filter, 'helpful'); ?>>役に立った</option>
                        <option value="not_helpful" <?php selected($feedback_filter, 'not_helpful'); ?>>役に立たなかった</option>
                        <option value="close" <?php selected($feedback_filter, 'close'); ?>>近い</option>
                        <option value="different" <?php selected($feedback_filter, 'different'); ?>>違う</option>
                    </select>
                    <button type="submit" class="gip-btn gip-btn-secondary">フィルター適用</button>
                </form>
                
                <?php
                // クエリ構築
                $where = "1=1";
                $params = array();
                if ($category_filter) {
                    $where .= " AND detected_category = %s";
                    $params[] = $category_filter;
                }
                if ($feedback_filter) {
                    $where .= " AND user_feedback = %s";
                    $params[] = $feedback_filter;
                }
                
                $count_query = "SELECT COUNT(*) FROM {$logs_table} WHERE {$where}";
                $filtered_total = $params ? $wpdb->get_var($wpdb->prepare($count_query, ...$params)) : $wpdb->get_var($count_query);
                
                // コメント情報を取得するためにLEFT JOINを追加
                $feedbacks_table = gip_table('user_feedbacks');
                $query = "SELECT l.*, f.comment as fb_comment, f.suggestion as fb_suggestion 
                          FROM {$logs_table} l 
                          LEFT JOIN {$feedbacks_table} f ON l.session_id = f.session_id 
                          WHERE {$where} 
                          GROUP BY l.id 
                          ORDER BY l.created_at DESC 
                          LIMIT {$per_page} OFFSET {$offset}";
                $logs = $params ? $wpdb->get_results($wpdb->prepare($query, ...$params)) : $wpdb->get_results($query);
                
                $total_pages = ceil($filtered_total / $per_page);
                ?>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 130px;">日時</th>
                            <th style="width: 80px;">カテゴリ</th>
                            <th style="width: 200px;">目的・質問内容</th>
                            <th style="width: 80px;">地域</th>
                            <th style="width: 60px;">結果数</th>
                            <th>診断結果（補助金）</th>
                            <th>フィードバック・コメント</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logs): foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html(date('Y/m/d H:i', strtotime($log->created_at))); ?></td>
                            <td>
                                <span style="background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                                    <?php echo esc_html($category_labels[$log->detected_category] ?? $log->detected_category ?? '-'); ?>
                                </span>
                            </td>
                            <td style="max-width: 300px; word-wrap: break-word;">
                                <strong><?php echo esc_html($log->purpose ?? ''); ?></strong>
                                <?php if ($log->clarification): ?>
                                <br><small style="color: #6b7280;"><?php echo esc_html($log->clarification); ?></small>
                                <?php endif; ?>
                                <?php if ($log->raw_input): ?>
                                <br><small style="color: #9ca3af;"><?php echo esc_html($log->raw_input); ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 12px;"><?php echo esc_html(($log->prefecture ?? '') . ($log->municipality ? ' ' . $log->municipality : '')); ?></td>
                            <td style="text-align: center;"><?php echo esc_html($log->result_count ?? '0'); ?></td>
                            <td style="font-size: 12px;">
                                <?php 
                                if (!empty($log->result_grant_titles)) {
                                    $titles = explode('|', $log->result_grant_titles);
                                    $count = 0;
                                    foreach ($titles as $title) {
                                        if ($count >= 3) {
                                            echo '<span style="color: #9ca3af;">他' . (count($titles) - 3) . '件...</span>';
                                            break;
                                        }
                                        if ($title) {
                                            echo '<div style="margin-bottom: 4px; padding: 4px 6px; background: #f3f4f6; border-radius: 4px; white-space: normal; line-height: 1.4;" title="' . esc_attr($title) . '">';
                                            echo esc_html($title);
                                            echo '</div>';
                                            $count++;
                                        }
                                    }
                                } else {
                                    echo '<span style="color: #9ca3af;">-</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $fb = $log->user_feedback;
                                $fb_label = $feedback_labels[$fb] ?? $fb;
                                $fb_color = $feedback_colors[$fb] ?? '#9ca3af';
                                if ($fb): ?>
                                    <span style="color: <?php echo $fb_color; ?>; font-weight: 600; font-size: 12px;"><?php echo esc_html($fb_label); ?></span>
                                    <?php if ($log->satisfaction_score): ?>
                                    <br><small style="color: #6b7280;">満足度: <?php echo esc_html($log->satisfaction_score); ?>/5</small>
                                    <?php endif; ?>
                                    <?php if (!empty($log->fb_comment)): ?>
                                    <div style="margin-top: 6px; padding: 8px; background: #fff; border: 1px solid #e5e5e5; border-radius: 4px; font-size: 12px;">
                                        <strong>コメント:</strong><br>
                                        <?php echo nl2br(esc_html($log->fb_comment)); ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($log->fb_suggestion)): ?>
                                    <div style="margin-top: 6px; padding: 8px; background: #f0fdf4; border: 1px solid #86efac; border-radius: 4px; font-size: 12px; color: #166534;">
                                        <strong>改善案:</strong><br>
                                        <?php echo nl2br(esc_html($log->fb_suggestion)); ?>
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 24px; color: #6b7280;">質問ログがありません</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- ページネーション -->
                <?php if ($total_pages > 1): ?>
                <div style="margin-top: 16px; display: flex; justify-content: center; gap: 8px;">
                    <?php for ($i = 1; $i <= min($total_pages, 10); $i++): ?>
                    <a href="<?php echo add_query_arg(array('paged' => $i, 'category' => $category_filter, 'feedback' => $feedback_filter)); ?>" 
                       class="gip-btn <?php echo $i == $current_page ? 'gip-btn-primary' : 'gip-btn-secondary'; ?>" 
                       style="min-width: 36px; text-align: center;">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 改善提案 -->
        <div class="gip-card">
            <div class="gip-card-header"><h2 class="gip-card-title">改善のヒント</h2></div>
            <div class="gip-card-body">
                <ul style="margin: 0; padding-left: 20px; color: #374151;">
                    <li style="margin-bottom: 8px;">「違う」フィードバックが多いカテゴリは、深掘り質問の精度向上が必要です</li>
                    <li style="margin-bottom: 8px;">質問内容を分析し、よく使われるキーワードをカテゴリ判定に追加しましょう</li>
                    <li style="margin-bottom: 8px;">結果数が0の質問は、検索クエリの改善やインデックス更新が必要かもしれません</li>
                    <li>満足度スコアが低い場合は、AIの回答精度やUIの改善を検討してください</li>
                </ul>
            </div>
        </div>
        
        <?php endif; ?>
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

function gip_ajax_init_tables() {
    check_ajax_referer('gip_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('権限がありません');
    
    // テーブルを作成
    gip_create_tables();
    
    // バージョン更新
    update_option('gip_db_version', GIP_VERSION);
    
    wp_send_json_success('テーブルを初期化しました');
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
    
    $indexed_count = 0;
    $skipped_count = 0;
    
    foreach ($grants as $id) {
        // 既にインデックス済みの補助金はスキップ
        if (gip_has_vector($id)) {
            $skipped_count++;
            continue;
        }
        
        // 新規インデックス作成
        gip_create_vector($id);
        $indexed_count++;
        usleep(200000);
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
        'indexed' => $indexed_count,
        'skipped' => $skipped_count,
    ));
}

/**
 * インデックス済み件数と進捗を取得（途中再開用）
 * - indexed_count: インデックス済み件数
 * - last_indexed_offset: 最後に処理した投稿のオフセット位置（途中再開に使用）
 */
function gip_ajax_get_indexed_count() {
    check_ajax_referer('gip_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('権限がありません');
    
    global $wpdb;
    $table = gip_table('vectors');
    
    // テーブルが存在するか確認
    if (!$wpdb->get_var("SHOW TABLES LIKE '{$table}'")) {
        wp_send_json_success(array('indexed_count' => 0, 'last_indexed_offset' => 0));
        return;
    }
    
    // インデックス済み件数をカウント
    $indexed_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    
    // 最後にインデックスされた投稿IDを取得
    $last_indexed_id = (int) $wpdb->get_var("SELECT MAX(post_id) FROM {$table}");
    
    // その投稿IDのオフセット位置を計算（ID順でソートした場合の位置）
    $last_indexed_offset = 0;
    if ($last_indexed_id > 0) {
        $posts_table = $wpdb->posts;
        $last_indexed_offset = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$posts_table} WHERE post_type = 'grant' AND post_status = 'publish' AND ID <= %d",
            $last_indexed_id
        ));
    }
    
    wp_send_json_success(array(
        'indexed_count' => $indexed_count,
        'last_indexed_offset' => $last_indexed_offset,
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
    
    $scores = array();
    $chunk_size = 100; // メモリ最適化: チャンク処理
    $offset = 0;
    
    // 総数を取得
    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    if (!$total) {
        return array();
    }
    
    // チャンク処理でベクトルを読み込み
    while ($offset < $total) {
        $vectors = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT grant_id, embedding, metadata FROM {$table} LIMIT %d OFFSET %d",
                $chunk_size,
                $offset
            )
        );
        
        if (empty($vectors)) break;
        
        foreach ($vectors as $v) {
            $emb = !empty($v->embedding) ? json_decode($v->embedding, true) : null;
            $meta = !empty($v->metadata) ? json_decode($v->metadata, true) : array();
            if (!is_array($meta)) $meta = array();
            
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
        
        // メモリ解放
        unset($vectors);
        $offset += $chunk_size;
        
        // 十分な結果が得られたら早期終了
        if (count($scores) >= $limit * 3) {
            break;
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
    // ヘルスチェック用エンドポイント（デバッグ・疎通確認用）
    register_rest_route(GIP_API_NS, '/health', array(
        'methods' => 'GET',
        'callback' => 'gip_api_health',
        'permission_callback' => '__return_true',
    ));
    
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
    
    // 詳細フィードバック（改善コメント付き）
    register_rest_route(GIP_API_NS, '/feedback-detailed', array(
        'methods' => 'POST',
        'callback' => 'gip_api_feedback_detailed',
        'permission_callback' => '__return_true',
    ));
    
    // 戻る機能用API
    register_rest_route(GIP_API_NS, '/step-back', array(
        'methods' => 'POST',
        'callback' => 'gip_api_step_back',
        'permission_callback' => '__return_true',
    ));
    
    // 再調整機能用API
    register_rest_route(GIP_API_NS, '/readjust', array(
        'methods' => 'POST',
        'callback' => 'gip_api_readjust',
        'permission_callback' => '__return_true',
    ));
    
    // セッション全体フィードバックAPI（評価・コメント）
    register_rest_route(GIP_API_NS, '/session-feedback', array(
        'methods' => 'POST',
        'callback' => 'gip_api_session_feedback',
        'permission_callback' => '__return_true',
    ));
    
    gip_log('REST API routes registered: ' . GIP_API_NS);
}

/**
 * ヘルスチェックAPI - 疎通確認用
 */
function gip_api_health($request) {
    return new WP_REST_Response(array(
        'success' => true,
        'status' => 'ok',
        'version' => GIP_VERSION,
        'namespace' => GIP_API_NS,
        'timestamp' => current_time('mysql'),
        'endpoints' => array(
            'chat' => rest_url(GIP_API_NS . '/chat'),
            'feedback' => rest_url(GIP_API_NS . '/feedback'),
            'municipalities' => rest_url(GIP_API_NS . '/municipalities'),
            'grant' => rest_url(GIP_API_NS . '/grant/{id}'),
        ),
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
 * 自然言語対話フロー処理 - UX最適化版 v7.1.0
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
    $main_results = array();
    $sub_results = array();
    $show_comparison = false;
    $allow_input = false;
    $show_reset_option = false;
    $show_research_option = false;
    
    // セッションリセット検知（最初から、やり直す、リセット等）
    if (gip_detect_reset_intent($user_input)) {
        $context = array('step' => 'init', 'understanding_level' => 0, 'collected_info' => array());
        $wpdb->update(gip_table('sessions'), array('context' => wp_json_encode($context)), array('session_id' => $session_id));
        
        $welcome = "了解しました！最初からやり直しましょう。\n\n";
        $welcome .= "補助金・助成金コンシェルジュへようこそ！\n\n";
        $welcome .= "あなたに最適な補助金をお探しするために、いくつかお伺いします。\n\n";
        $welcome .= "まず、あなたのお立場を教えてください。";
        
        $wpdb->insert(gip_table('messages'), array(
            'session_id' => $session_id,
            'role' => 'assistant',
            'content' => $welcome,
        ));
        
        return array(
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
        );
    }
    
    // 再検索意図検知
    $research_intent = gip_detect_research_intent($user_input, $collected);
    if ($research_intent && ($step === 'results' || $step === 'followup')) {
        return gip_handle_research($session_id, $context, $user_input, $research_intent);
    }
    
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
                
                // 市区町村は入力式に変更
                $next_step = 'municipality';
                $response_text = "ありがとうございます。\n\n";
                $response_text .= "【" . $prefecture . "】ですね。\n\n";
                $response_text .= "市区町村を入力してください。\n";
                $response_text .= "（都道府県全域で検索する場合は「全域」または「スキップ」と入力してください）";
                
                $options = array(
                    array('id' => 'skip', 'label' => $prefecture . '全域で検索（市区町村指定なし）'),
                );
                $option_type = 'text_input';
                $hint = '例: 渋谷区、横浜市、札幌市中央区 など';
                $allow_input = true;
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
            // スキップまたは全域の場合は空に
            if ($municipality === 'skip' || $municipality === '全域' || 
                strpos($user_input, '全域') !== false || strpos($user_input, 'スキップ') !== false ||
                strpos($user_input, '指定なし') !== false) {
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
            $deep_dive_count = $context['deep_dive_count'] ?? 0;
            
            // 【重要】目的入力後は必ず深掘り質問を行う
            // フロー: 目的入力 → 深掘り質問1回目 → 回答 → 必要なら2回目 → 検索確認
            // ※「この補助金かも？」機能は廃止 - 深掘りを徹底して検索精度を上げる
            $analysis = gip_analyze_user_needs_deep($collected, $context);
            
            // 深掘り質問が必要な場合（常に最初は深掘り質問を行う）
            if ($analysis['needs_clarification']) {
                $context['deep_dive_count'] = $deep_dive_count + 1;
                $context['detected_category'] = $analysis['category'] ?? '';
                $next_step = 'clarification';
                $response_text = $analysis['question'];
                $options = $analysis['options'] ?? array();
                $hint = $analysis['hint'] ?? '';
                $hint_important = $analysis['hint_important'] ?? ''; // 赤文字ヒント
                $allow_input = true;
                $show_reset_option = true;
            } else {
                // 十分な情報が集まった場合は検索確認へ（補助金提案はしない）
                $next_step = 'searching';
                $response_text = "ありがとうございます。お話を伺いました。\n\n";
                $response_text .= gip_build_search_confirmation($collected);
                $response_text .= "\n\nこの内容で検索してよろしいですか？";
                
                $options = array(
                    array('id' => 'search', 'label' => 'この条件で検索する'),
                    array('id' => 'add_detail', 'label' => 'さらに詳細を追加する'),
                    array('id' => 'restart', 'label' => '最初からやり直す'),
                );
                $hint = '条件を変更したい場合は「さらに詳細を追加」を選択してください。';
                $allow_input = true;
            }
            break;
            
        case 'genie_guess':
            // 2択フロー：「近いです」か「違います」
            $last_guess = $context['last_guess'] ?? array();
            $guess_category = $last_guess['guess_category'] ?? '';
            $followup_question = $last_guess['followup_question'] ?? array();
            
            // 「近いです」を選択 - 結果を出す
            if ($user_input === 'close_yes' || (strpos($user_input, '近い') !== false && strpos($user_input, '近くない') === false) || 
                strpos($user_input, 'はい') !== false || strpos($user_input, 'そう') !== false) {
                
                // フィードバックをログに記録
                gip_update_question_log_feedback($session_id, 'close', 4);
                
                // 推測した補助金IDを保存（検索結果で最優先にするため）
                if (!empty($last_guess['guess_subsidy_id'])) {
                    $collected['matched_grant_id'] = $last_guess['guess_subsidy_id'];
                }
                $collected['genie_hint'] = ($last_guess['guess_subsidy'] ?? '') . ' ' . $guess_category;
                $collected['clarification'] = ($collected['clarification'] ?? '') . ' ' . $guess_category;
                
                // 直接検索実行して結果を表示
                $filters = array(
                    'user_type' => $collected['user_type'] ?? '',
                    'prefecture' => $collected['prefecture'] ?? '',
                    'municipality' => $collected['municipality'] ?? '',
                    'status_open' => true,
                );
                
                $results = gip_execute_match($session_id, gip_build_natural_search_query($collected), $filters, $collected);
                
                if (!empty($results)) {
                    $count = count($results);
                    $next_step = 'results';
                    $response_text = "承知いたしました。\n\n";
                    $response_text .= "【" . ($last_guess['guess_subsidy'] ?? '補助金') . "】に関連する補助金を検索しました。\n\n";
                    $response_text .= "【" . $count . "件】の補助金・助成金が見つかりました。\n";
                    $response_text .= "マッチ度の高い順に表示しています。";
                    
                    $options = array(
                        array('id' => 'compare', 'label' => '比較して詳しく見る'),
                        array('id' => 'continue', 'label' => '他にも探す'),
                        array('id' => 'research', 'label' => '条件を変えて再検索'),
                    );
                    $show_comparison = true;
                    $show_research = true;
                    $allow_input = true;
                } else {
                    // 結果がない場合
                    $next_step = 'no_result';
                    $response_text = "申し訳ございません。条件に合う補助金が見つかりませんでした。";
                    $options = array(
                        array('id' => 'expand_area', 'label' => '都道府県全域で探す'),
                        array('id' => 'national', 'label' => '全国対応の補助金を探す'),
                        array('id' => 'restart', 'label' => '最初からやり直す'),
                    );
                    $allow_input = true;
                }
            }
            // 「近くないです」「違います」を選択 - 質問を重ねる
            elseif ($user_input === 'close_no' || strpos($user_input, '近くない') !== false || 
                    strpos($user_input, '違') !== false || $user_input === 'no') {
                
                // フィードバックをログに記録（改善が必要なケース）
                gip_update_question_log_feedback($session_id, 'different', 2);
                
                // 前回の推測を除外リストに追加
                $previous_guesses = $context['previous_guesses'] ?? array();
                if (!empty($last_guess['guess_subsidy_id'])) {
                    $previous_guesses[] = $last_guess['guess_subsidy_id'];
                }
                $context['previous_guesses'] = $previous_guesses;
                
                $guess_count = $context['guess_count'] ?? 0;
                
                // まだ推測回数が残っている場合は質問を重ねる
                if ($guess_count < 3) {
                    $next_step = 'genie_refine';
                    $response_text = "承知いたしました。\n\n";
                    $response_text .= "より適切な補助金をお探しするために、もう少し詳しくお聞かせください。\n\n";
                    $response_text .= "具体的にどのようなことでお困りですか？\n";
                    $response_text .= "（例：事業の内容、対象経費、希望金額など）";
                    
                    $options = array(
                        array('id' => 'tell_purpose', 'label' => '具体的な用途を教える'),
                        array('id' => 'tell_industry', 'label' => '業種・業界を教える'),
                        array('id' => 'tell_amount', 'label' => '希望金額を教える'),
                        array('id' => 'search_anyway', 'label' => 'このまま検索する'),
                    );
                    $hint = '自由に入力することもできます。';
                    $allow_input = true;
                } else {
                    // 推測回数上限 - 通常検索へ
                    $next_step = 'searching';
                    $response_text = "承知いたしました。\n\n";
                    $response_text .= "いただいた情報をもとに検索いたします。\n\n";
                    $response_text .= gip_build_search_confirmation($collected);
                    $response_text .= "\n\nこの内容で検索してよろしいですか？";
                    
                    $options = array(
                        array('id' => 'search', 'label' => 'この条件で検索する'),
                        array('id' => 'add_detail', 'label' => '詳細条件を追加する'),
                        array('id' => 'restart', 'label' => '最初からやり直す'),
                    );
                    $allow_input = true;
                }
            }
            // その他の入力 - 追加情報として処理
            else {
                $collected['clarification'] = ($collected['clarification'] ?? '') . ' ' . $user_input;
                $next_step = 'genie_refine';
                $response_text = "ありがとうございます。\n\n追加情報をもとに、もう一度お探しします。";
                $options = array();
                $allow_input = true;
            }
            break;
        
        case 'genie_followup':
            // 深掘り質問への回答処理（「近いです」選択後）
            $last_guess = $context['last_guess'] ?? array();
            $collected['clarification'] = ($collected['clarification'] ?? '') . ' ' . $user_input;
            
            // 推測した補助金を優先して検索
            if (!empty($last_guess['guess_subsidy_id'])) {
                $collected['matched_grant_id'] = $last_guess['guess_subsidy_id'];
            }
            $collected['genie_hint'] = ($last_guess['guess_subsidy'] ?? '') . ' ' . ($last_guess['guess_category'] ?? '');
            
            $next_step = 'searching';
            $response_text = "ありがとうございます。\n\n";
            $response_text .= "いただいた条件で検索いたします。\n\n";
            $response_text .= gip_build_search_confirmation($collected);
            $response_text .= "\n\nこの内容で検索してよろしいですか？";
            
            $options = array(
                array('id' => 'search', 'label' => 'この条件で検索する'),
                array('id' => 'add_detail', 'label' => '詳細条件を追加する'),
                array('id' => 'restart', 'label' => '最初からやり直す'),
            );
            $allow_input = true;
            break;
        
        case 'genie_refine':
            // 「違います」選択後 - 質問を重ねて再推測
            $collected['clarification'] = ($collected['clarification'] ?? '') . ' ' . $user_input;
            
            // 検索へ進む場合
            if ($user_input === 'search_anyway' || strpos($user_input, '検索') !== false) {
                $next_step = 'searching';
                $response_text = "承知いたしました。\n\n";
                $response_text .= gip_build_search_confirmation($collected);
                $response_text .= "\n\nこの内容で検索してよろしいですか？";
                
                $options = array(
                    array('id' => 'search', 'label' => 'この条件で検索する'),
                    array('id' => 'add_detail', 'label' => '詳細条件を追加する'),
                    array('id' => 'restart', 'label' => '最初からやり直す'),
                );
                $allow_input = true;
            } else {
                // 追加情報で再推測（前回の推測を除外）
                $guess = gip_guess_subsidy_like_genie($collected, $context);
                
                if (!empty($guess['should_guess']) && ($guess['confidence'] ?? 0) >= 25) {
                    $context['guess_count'] = ($context['guess_count'] ?? 0) + 1;
                    $context['last_guess'] = $guess;
                    $next_step = 'genie_guess';
                    
                    // 概要抜粋を表示して2択確認
                    $response_text = "ありがとうございます。\n\n";
                    $guess_subsidy = $guess['guess_subsidy'] ?? '補助金';
                    $guess_summary = $guess['guess_summary'] ?? '';
                    
                    $response_text .= "【" . $guess_subsidy . "】\n\n";
                    
                    if (!empty($guess_summary)) {
                        $response_text .= "《概要》\n" . $guess_summary . "\n\n";
                    }
                    
                    if (!empty($guess['guess_features'])) {
                        foreach (array_slice($guess['guess_features'], 0, 3) as $feature) {
                            $response_text .= "・" . $feature . "\n";
                        }
                        $response_text .= "\n";
                    }
                    
                    $response_text .= "こういった内容の補助金ですが、お探しのものに近いですか？";
                    
                    $options = array(
                        array('id' => 'close_yes', 'label' => '近いです'),
                        array('id' => 'close_no', 'label' => '違います'),
                    );
                    $allow_input = true;
                } else {
                    // これ以上推測できない - 検索へ
                    $next_step = 'searching';
                    $response_text = "ありがとうございます。\n\n";
                    $response_text .= "いただいた情報をもとに検索いたします。\n\n";
                    $response_text .= gip_build_search_confirmation($collected);
                    $response_text .= "\n\nこの内容で検索してよろしいですか？";
                    
                    $options = array(
                        array('id' => 'search', 'label' => 'この条件で検索する'),
                        array('id' => 'add_detail', 'label' => '詳細条件を追加する'),
                        array('id' => 'restart', 'label' => '最初からやり直す'),
                    );
                    $allow_input = true;
                }
            }
            break;
            
        case 'genie_more_info':
            // ユーザーから追加情報をもらって再推測
            $collected['clarification'] = ($collected['clarification'] ?? '') . ' ' . $user_input;
            
            // 追加情報をもとに再推測
            $guess = gip_guess_subsidy_like_genie($collected, $context);
            
            if (!empty($guess['should_guess']) && ($guess['confidence'] ?? 0) >= 25) {
                $context['guess_count'] = ($context['guess_count'] ?? 0) + 1;
                $context['last_guess'] = $guess;
                $next_step = 'genie_guess';
                
                // 概要抜粋を表示して2択確認
                $response_text = "ありがとうございます。\n\n";
                $guess_subsidy = $guess['guess_subsidy'] ?? '補助金';
                $guess_summary = $guess['guess_summary'] ?? '';
                
                $response_text .= "【" . $guess_subsidy . "】\n\n";
                
                if (!empty($guess_summary)) {
                    $response_text .= "《概要》\n" . $guess_summary . "\n\n";
                }
                
                if (!empty($guess['guess_features'])) {
                    foreach (array_slice($guess['guess_features'], 0, 3) as $feature) {
                        $response_text .= "・" . $feature . "\n";
                    }
                    $response_text .= "\n";
                }
                
                $response_text .= "こういった内容の補助金ですが、お探しのものに近いですか？";
                
                $options = array(
                    array('id' => 'close_yes', 'label' => '近いです'),
                    array('id' => 'close_no', 'label' => '違います'),
                );
                $allow_input = true;
            } else {
                // 推測できない - 検索へ
                $next_step = 'searching';
                $response_text = "ありがとうございます。\n\n";
                $response_text .= "いただいた情報をもとに検索いたします。\n\n";
                $response_text .= gip_build_search_confirmation($collected);
                $response_text .= "\n\nこの内容で検索してよろしいですか？";
                
                $options = array(
                    array('id' => 'search', 'label' => 'この条件で検索する'),
                    array('id' => 'add_detail', 'label' => '詳細条件を追加する'),
                    array('id' => 'restart', 'label' => '最初からやり直す'),
                );
                $allow_input = true;
            }
            break;
            
        case 'genie_retry':
            // 該当しなかった場合 - ユーザーの追加情報で再推測
            $collected['clarification'] = ($collected['clarification'] ?? '') . ' ' . $user_input;
            
            // 前回の推測を除外リストに追加
            $previous_guesses = $context['previous_guesses'] ?? array();
            if (!empty($context['last_guess']['guess_subsidy_id'])) {
                $previous_guesses[] = $context['last_guess']['guess_subsidy_id'];
            }
            $context['previous_guesses'] = $previous_guesses;
            
            // 検索へ進む場合
            if ($user_input === 'search_now' || strpos($user_input, '検索') !== false) {
                $next_step = 'searching';
                $response_text = "承知いたしました。\n\n";
                $response_text .= gip_build_search_confirmation($collected);
                $response_text .= "\n\nこの内容で検索してよろしいですか？";
                
                $options = array(
                    array('id' => 'search', 'label' => 'この条件で検索する'),
                    array('id' => 'add_detail', 'label' => '詳細条件を追加する'),
                    array('id' => 'restart', 'label' => '最初からやり直す'),
                );
                $allow_input = true;
            } else {
                // 追加情報で再推測（前回の推測を除外）
                $guess = gip_guess_subsidy_like_genie($collected, $context);
                
                if (!empty($guess['should_guess']) && ($guess['confidence'] ?? 0) >= 25) {
                    $context['guess_count'] = ($context['guess_count'] ?? 0) + 1;
                    $context['last_guess'] = $guess;
                    $next_step = 'genie_guess';
                    
                    // 概要抜粋を表示して2択確認
                    $response_text = "ありがとうございます。\n\n";
                    $guess_subsidy = $guess['guess_subsidy'] ?? '補助金';
                    $guess_summary = $guess['guess_summary'] ?? '';
                    
                    $response_text .= "【" . $guess_subsidy . "】\n\n";
                    
                    if (!empty($guess_summary)) {
                        $response_text .= "《概要》\n" . $guess_summary . "\n\n";
                    }
                    
                    if (!empty($guess['guess_features'])) {
                        foreach (array_slice($guess['guess_features'], 0, 3) as $feature) {
                            $response_text .= "・" . $feature . "\n";
                        }
                        $response_text .= "\n";
                    }
                    
                    $response_text .= "こういった内容の補助金ですが、お探しのものに近いですか？";
                    
                    $options = array(
                        array('id' => 'close_yes', 'label' => '近いです'),
                        array('id' => 'close_no', 'label' => '違います'),
                    );
                    $allow_input = true;
                } else {
                    // これ以上推測できない
                    $next_step = 'searching';
                    $response_text = "ありがとうございます。\n\n";
                    $response_text .= "いただいた情報をもとに検索いたします。\n\n";
                    $response_text .= gip_build_search_confirmation($collected);
                    $response_text .= "\n\nこの内容で検索してよろしいですか？";
                    
                    $options = array(
                        array('id' => 'search', 'label' => 'この条件で検索する'),
                        array('id' => 'add_detail', 'label' => '詳細条件を追加する'),
                        array('id' => 'restart', 'label' => '最初からやり直す'),
                    );
                    $allow_input = true;
                }
            }
            break;
            
        case 'genie_close':
            // 「近い」と言われた後の追加情報収集（レガシー互換性のため維持）
            $collected['clarification'] = ($collected['clarification'] ?? '') . ' ' . $user_input;
            
            // 前回の推測を除外リストに追加
            $previous_guesses = $context['previous_guesses'] ?? array();
            if (!empty($context['last_guess']['guess_subsidy_id'])) {
                $previous_guesses[] = $context['last_guess']['guess_subsidy_id'];
            }
            $context['previous_guesses'] = $previous_guesses;
            
            // 再度推測を試みる（前回の推測を除外）
            $guess = gip_guess_subsidy_like_genie($collected, $context);
            
            if (!empty($guess['should_guess']) && ($guess['confidence'] ?? 0) >= 30) {
                $context['guess_count'] = ($context['guess_count'] ?? 0) + 1;
                $context['last_guess'] = $guess;
                $next_step = 'genie_guess';
                
                // 概要抜粋を表示して2択確認
                $response_text = "承知いたしました。\n\n";
                $guess_subsidy = $guess['guess_subsidy'] ?? '補助金';
                $guess_summary = $guess['guess_summary'] ?? '';
                
                $response_text .= "【" . $guess_subsidy . "】\n\n";
                
                if (!empty($guess_summary)) {
                    $response_text .= "《概要》\n" . $guess_summary . "\n\n";
                }
                
                if (!empty($guess['guess_features'])) {
                    foreach (array_slice($guess['guess_features'], 0, 3) as $feature) {
                        $response_text .= "・" . $feature . "\n";
                    }
                    $response_text .= "\n";
                }
                
                $response_text .= "こういった内容の補助金ですが、お探しのものに近いですか？";
                
                $options = array(
                    array('id' => 'close_yes', 'label' => '近いです'),
                    array('id' => 'close_no', 'label' => '違います'),
                );
                $allow_input = true;
            } else {
                // これ以上推測できない - 検索へ
                $next_step = 'searching';
                $response_text = "承知しました。いただいた情報で検索いたします。\n\n" . gip_build_search_confirmation($collected);
                $response_text .= "\n\nこの内容で検索してよろしいですか？";
                
                $options = array(
                    array('id' => 'search', 'label' => 'この条件で検索する'),
                    array('id' => 'add_detail', 'label' => '詳細条件を追加する'),
                    array('id' => 'restart', 'label' => '最初からやり直す'),
                );
                $allow_input = true;
            }
            break;
            
        case 'searching':
            // 検索確認後の処理
            if (strpos($user_input, 'search') !== false || strpos($user_input, '検索') !== false || $user_input === 'この条件で検索する') {
                $filters = array(
                    'user_type' => $collected['user_type'] ?? '',
                    'prefecture' => $collected['prefecture'] ?? '',
                    'municipality' => $collected['municipality'] ?? '',
                    'status_open' => true,
                );
                
                $results = gip_execute_match($session_id, gip_build_natural_search_query($collected), $filters, $collected);
                
                if (!empty($results)) {
                    $count = count($results);
                    $response_text = "検索が完了しました\n\n";
                    $response_text .= "【" . $count . "件】の補助金・助成金が見つかりました。\n";
                    $response_text .= "マッチ度の高い順に表示しています。";
                    
                    $next_step = 'results';
                    $show_comparison = true;
                    $show_research_option = true;
                } else {
                    $response_text = "申し訳ございません。条件に合う補助金が見つかりませんでした。\n\n";
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
            } elseif (strpos($user_input, 'add_detail') !== false || strpos($user_input, '詳細') !== false || strpos($user_input, '追加') !== false) {
                $next_step = 'add_detail';
                $response_text = "追加の条件や詳細を教えてください。\n\n";
                $response_text .= "例：\n";
                $response_text .= "・予算規模（100万円以内など）\n";
                $response_text .= "・具体的な用途（〇〇の購入など）\n";
                $response_text .= "・希望する締切時期";
                $allow_input = true;
                $hint = '具体的な条件を入力してください。';
            } else {
                // デフォルトで検索を実行
                $filters = array(
                    'user_type' => $collected['user_type'] ?? '',
                    'prefecture' => $collected['prefecture'] ?? '',
                    'municipality' => $collected['municipality'] ?? '',
                    'status_open' => true,
                );
                
                $results = gip_execute_match($session_id, gip_build_natural_search_query($collected), $filters, $collected);
                
                if (!empty($results)) {
                    $count = count($results);
                    $response_text = "検索が完了しました\n\n";
                    $response_text .= "【" . $count . "件】の補助金・助成金が見つかりました。\n";
                    $response_text .= "マッチ度の高い順に表示しています。";
                    
                    $next_step = 'results';
                    $show_comparison = true;
                    $show_research_option = true;
                } else {
                    $response_text = "申し訳ございません。条件に合う補助金が見つかりませんでした。";
                    $next_step = 'no_result';
                    $options = array(
                        array('id' => 'expand_area', 'label' => '都道府県全域で探す'),
                        array('id' => 'national', 'label' => '全国対応の補助金を探す'),
                        array('id' => 'change_purpose', 'label' => '目的を変えて探す'),
                        array('id' => 'restart', 'label' => '最初からやり直す'),
                    );
                }
            }
            break;
            
        case 'add_detail':
            // 追加条件を収集
            $collected['additional_details'] = ($collected['additional_details'] ?? '') . ' ' . $user_input;
            
            $next_step = 'searching';
            $response_text = gip_build_search_confirmation($collected);
            $response_text .= "\n\nこの内容で検索してよろしいですか？";
            
            $options = array(
                array('id' => 'search', 'label' => 'この条件で検索する'),
                array('id' => 'add_detail', 'label' => 'さらに詳細を追加'),
                array('id' => 'restart', 'label' => '最初からやり直す'),
            );
            $allow_input = true;
            break;
            
        case 'clarification':
            $collected['clarification'] = ($collected['clarification'] ?? '') . ' ' . $user_input;
            
            // 具体的な補助金名を選択した場合は直接検索へ進む
            // IDとラベル両方をチェック
            $specific_subsidy_map = array(
                // ID => 表示用ラベル
                'it_intro' => 'IT導入補助金',
                'monodukuri' => 'ものづくり補助金',
                'monodukuri_digital' => 'ものづくり補助金（デジタル枠）',
                'jisedai' => '事業再構築補助金',
                'jisedai_equip' => '事業再構築補助金（設備導入）',
                'jisedai_new' => '事業再構築補助金（新分野展開）',
                'jisedai_sales' => '事業再構築補助金（販路開拓）',
                'shokibo' => '小規模事業者持続化補助金',
                'shoene' => '省エネ補助金',
                'shoene_main' => '省エネルギー投資促進支援',
                'ev_charge' => 'EV・充電設備導入補助金',
                'solar' => '太陽光・再エネ設備補助金',
                'koyou_chosei' => '雇用調整助成金',
                'career_up' => 'キャリアアップ助成金',
                'jinzai' => '人材開発支援助成金',
                'try_koyou' => 'トライアル雇用助成金',
                'sogyo' => '創業補助金・起業支援金',
                'chiiki' => '地域の創業支援',
                'ec_support' => 'ECサイト構築支援',
                // その他の選択肢も検索へ進める
                'other_it' => 'IT関連補助金',
                'other_equip' => '設備投資補助金',
                'other_new' => '新規事業支援',
                'other_hr' => '人材関連助成金',
                'other_sales' => '販路開拓支援',
                'other_energy' => '環境関連補助金',
            );
            
            $specific_subsidy_keywords = array(
                'IT導入補助金', 'ものづくり補助金', '事業再構築補助金', '小規模事業者持続化補助金',
                '省エネルギー', '省エネ', 'キャリアアップ助成金', '人材開発支援助成金',
                '雇用調整助成金', 'トライアル雇用', '創業補助金', 'EV', '太陽光', '充電設備',
                'ECサイト', '地域の創業',
            );
            
            $is_specific_choice = false;
            $matched_subsidy = $user_input;
            
            // IDでマッチするかチェック
            if (isset($specific_subsidy_map[$user_input])) {
                $is_specific_choice = true;
                $matched_subsidy = $specific_subsidy_map[$user_input];
            } else {
                // ラベルのキーワードでチェック
                foreach ($specific_subsidy_keywords as $keyword) {
                    if (mb_strpos($user_input, $keyword) !== false) {
                        $is_specific_choice = true;
                        break;
                    }
                }
            }
            
            // 具体的な補助金を選んだ場合は検索へ
            if ($is_specific_choice) {
                $next_step = 'searching';
                $response_text = "承知いたしました。\n\n";
                $response_text .= "【" . $matched_subsidy . "】関連の補助金をお探しですね。\n\n";
                $response_text .= gip_build_search_confirmation($collected);
                $response_text .= "\n\nこの内容で検索してよろしいですか？";
                
                $options = array(
                    array('id' => 'search', 'label' => 'この条件で検索する'),
                    array('id' => 'add_detail', 'label' => '詳細条件を追加する'),
                    array('id' => 'restart', 'label' => '最初からやり直す'),
                );
                $allow_input = true;
            } else {
                // 深掘り質問の回答を受け取った後の処理
                // ※「この補助金かも？」機能は廃止 - 深掘りを徹底して検索精度を上げる
                $deep_dive_count = $context['deep_dive_count'] ?? 0;
                
                // 深掘り質問を継続するか判断
                $context['deep_dive_count'] = $deep_dive_count + 1;
                $analysis = gip_analyze_user_needs_deep($collected, $context);
                
                if ($analysis['needs_clarification']) {
                    // 更に深掘り質問を続ける
                    $context['detected_category'] = $analysis['category'] ?? '';
                    $next_step = 'clarification';
                    $response_text = $analysis['question'];
                    $options = $analysis['options'] ?? array();
                    $hint = $analysis['hint'] ?? '';
                    $hint_important = $analysis['hint_important'] ?? ''; // 赤文字ヒント
                    $allow_input = true;
                    $show_reset_option = true;
                } else {
                    // 十分な情報が集まった - 検索確認へ（補助金提案はしない）
                    $next_step = 'searching';
                    $response_text = "ありがとうございます。お話を伺いました。\n\n";
                    $response_text .= gip_build_search_confirmation($collected);
                    $response_text .= "\n\nこの内容で検索してよろしいですか？";
                    
                    $options = array(
                        array('id' => 'search', 'label' => 'この条件で検索する'),
                        array('id' => 'add_detail', 'label' => 'さらに詳細を追加する'),
                        array('id' => 'restart', 'label' => '最初からやり直す'),
                    );
                    $hint = '条件を変更したい場合は「さらに詳細を追加」を選択してください。';
                    $allow_input = true;
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
    
    // 会話状態を保存（戻る機能用）
    gip_save_conversation_state($session_id, $next_step, $context, $user_input);
    
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
        // メイン5件とサブ5件に分割
        $main_results = array_slice($results, 0, 5);
        $sub_results = array_slice($results, 5, 5);
        
        $response['results'] = $results;
        $response['main_results'] = $main_results;
        $response['sub_results'] = $sub_results;
        $response['results_count'] = count($results);
        $response['show_comparison'] = $show_comparison;
    }
    
    // 再検索オプションのフラグ
    if ($show_research_option || $next_step === 'results') {
        $response['show_research_option'] = true;
    }
    
    // リセットオプションのフラグ
    if ($show_reset_option) {
        $response['show_reset_option'] = true;
    }
    
    // 自然言語入力を促す赤文字ヒント
    if (!empty($hint_important)) {
        $response['hint_important'] = $hint_important;
    }
    
    // 質問ログを保存（改善分析用）
    // 【重要】診断完了時（resultsステップで結果がある時）のみ完全なログを保存
    // 途中経過では不完全なログが保存されないようにする
    $should_save_log = false;
    $log_extra = array(
        'detected_category' => $analysis['category'] ?? ($context['detected_category'] ?? ''),
        'raw_input' => $user_input,
    );
    
    if ($next_step === 'results' && !empty($results)) {
        // 診断完了: 結果情報を含めてログを保存
        $should_save_log = true;
        $log_extra['result_count'] = count($results);
        $log_extra['results'] = $results;
        $log_extra['is_diagnosis_complete'] = true;
        gip_log('Saving complete diagnosis log', array(
            'session_id' => $session_id,
            'result_count' => count($results),
            'step' => $next_step,
        ));
    } elseif ($next_step === 'results' && empty($results)) {
        // 結果なし: 0件の結果としてログを保存（改善分析に有用）
        $should_save_log = true;
        $log_extra['result_count'] = 0;
        $log_extra['results'] = array();
        $log_extra['is_diagnosis_complete'] = true;
        gip_log('Saving no-result diagnosis log', array(
            'session_id' => $session_id,
            'step' => $next_step,
        ));
    }
    
    if ($should_save_log) {
        gip_save_question_log($session_id, $collected, $log_extra);
    }
    
    return $response;
}

// =============================================================================
// 質問ログ保存関数（改善分析用）
// =============================================================================

/**
 * 質問ログをDBに保存（改善分析用）- 強化版 v7.2.0
 * 
 * 【重要】この関数は診断完了時（resultsステップ）のみ呼び出されるべき
 * 途中経過での呼び出しは不完全なログの原因となる
 * 
 * @param string $session_id セッションID
 * @param array $collected 収集した情報（user_type, prefecture, municipality, purpose, clarification等）
 * @param array $extra 追加情報（detected_category, result_count, results, raw_input, is_diagnosis_complete等）
 * @return int|false 保存したログのID、または失敗時はfalse
 */
function gip_save_question_log($session_id, $collected, $extra = array()) {
    global $wpdb;
    
    gip_log('Save question log called', array(
        'session_id' => $session_id,
        'is_diagnosis_complete' => $extra['is_diagnosis_complete'] ?? false,
        'result_count' => $extra['result_count'] ?? 0,
    ));
    
    // テーブルが存在しない場合は作成を試みる
    if (!gip_table_exists('question_logs')) {
        gip_log('Question logs table does not exist, attempting to create');
        gip_create_tables();
        if (!gip_table_exists('question_logs')) {
            gip_log('Failed to create question logs table');
            return false;
        }
    }
    
    // 結果の補助金情報を整理（最大20件まで保存）
    $result_grant_ids = '';
    $result_grant_titles = '';
    $result_scores = array();
    
    if (!empty($extra['results'])) {
        $grant_ids = array();
        $grant_titles = array();
        
        foreach (array_slice($extra['results'], 0, 20) as $r) {
            if (!empty($r['grant_id'])) {
                $grant_ids[] = $r['grant_id'];
            }
            if (!empty($r['title'])) {
                $grant_titles[] = $r['title'];
            }
            if (!empty($r['score'])) {
                $result_scores[] = array(
                    'id' => $r['grant_id'] ?? '',
                    'score' => $r['score'] ?? 0,
                );
            }
        }
        
        $result_grant_ids = implode(',', array_filter($grant_ids));
        $result_grant_titles = implode('|', array_filter($grant_titles));
        
        gip_log('Processing results for log', array(
            'grant_ids_count' => count($grant_ids),
            'titles_count' => count($grant_titles),
            'first_grant_id' => $grant_ids[0] ?? 'none',
        ));
    }
    
    // 会話履歴を取得（最大30メッセージ、各500文字まで）
    $conversation_history = '';
    $messages_table = gip_table('messages');
    if ($wpdb->get_var("SHOW TABLES LIKE '{$messages_table}'")) {
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT role, content, created_at FROM {$messages_table} WHERE session_id = %s ORDER BY id ASC LIMIT 30",
            $session_id
        ));
        if ($messages) {
            $history_arr = array();
            foreach ($messages as $m) {
                $history_arr[] = array(
                    'role' => $m->role,
                    'content' => mb_substr($m->content, 0, 500),
                    'time' => $m->created_at ?? '',
                );
            }
            $conversation_history = wp_json_encode($history_arr, JSON_UNESCAPED_UNICODE);
        }
    }
    
    $logs_table = gip_table('question_logs');
    
    // 既に同じセッションで診断完了済みのログがあるかチェック
    // セッションIDで検索し、診断完了済み（result_count > 0）のものを優先
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT id, result_count, user_feedback FROM {$logs_table} 
         WHERE session_id = %s 
         ORDER BY result_count DESC, id DESC 
         LIMIT 1",
        $session_id
    ));
    
    // 既存のログがある場合
    if ($existing) {
        // 既存ログに結果があり、今回も結果がある場合は更新
        // 既存ログに結果がなく、今回結果がある場合も更新（診断完了で上書き）
        $should_update = (
            ($extra['result_count'] ?? 0) > 0 || 
            ($existing->result_count == 0)
        );
        
        if ($should_update) {
            $update_data = array(
                'clarification' => $collected['clarification'] ?? '',
                'detected_category' => $extra['detected_category'] ?? '',
                'result_count' => $extra['result_count'] ?? 0,
                'result_grant_ids' => $result_grant_ids,
                'result_grant_titles' => $result_grant_titles,
                'raw_input' => $extra['raw_input'] ?? '',
                'conversation_history' => $conversation_history,
            );
            
            // 既存のフィードバックは上書きしない
            if (!empty($extra['user_feedback']) && empty($existing->user_feedback)) {
                $update_data['user_feedback'] = $extra['user_feedback'];
            }
            
            $result = $wpdb->update($logs_table, $update_data, array('id' => $existing->id));
            
            gip_log('Updated existing question log', array(
                'log_id' => $existing->id,
                'result_count' => $extra['result_count'] ?? 0,
                'update_result' => $result,
            ));
            
            return $existing->id;
        }
        
        gip_log('Skipped log update - existing log has results', array(
            'existing_id' => $existing->id,
            'existing_result_count' => $existing->result_count,
        ));
        return $existing->id;
    }
    
    // 新規保存
    $insert_data = array(
        'session_id' => $session_id,
        'user_type' => $collected['user_type'] ?? '',
        'prefecture' => $collected['prefecture'] ?? '',
        'municipality' => $collected['municipality'] ?? '',
        'purpose' => $collected['purpose'] ?? '',
        'clarification' => $collected['clarification'] ?? '',
        'detected_category' => $extra['detected_category'] ?? '',
        'result_count' => $extra['result_count'] ?? 0,
        'result_grant_ids' => $result_grant_ids,
        'result_grant_titles' => $result_grant_titles,
        'raw_input' => $extra['raw_input'] ?? '',
        'conversation_history' => $conversation_history,
        'created_at' => current_time('mysql'),
    );
    
    $insert_result = $wpdb->insert($logs_table, $insert_data);
    $insert_id = $wpdb->insert_id;
    
    gip_log('Created new question log', array(
        'log_id' => $insert_id,
        'result_count' => $extra['result_count'] ?? 0,
        'insert_result' => $insert_result,
        'purpose' => mb_substr($collected['purpose'] ?? '', 0, 50),
    ));
    
    return $insert_id;
}

/**
 * ユーザーフィードバックをログに記録
 * 
 * @param string $session_id セッションID
 * @param string $feedback フィードバックタイプ (positive, negative, helpful, not_helpful, close, different)
 * @param int|null $satisfaction 満足度スコア (1-5)
 * @return bool 更新に成功したかどうか
 */
function gip_update_question_log_feedback($session_id, $feedback, $satisfaction = null) {
    global $wpdb;
    
    gip_log('Update question log feedback called', array(
        'session_id' => $session_id,
        'feedback' => $feedback,
        'satisfaction' => $satisfaction,
    ));
    
    if (!gip_table_exists('question_logs')) {
        gip_log('Question logs table does not exist');
        return false;
    }
    
    $logs_table = gip_table('question_logs');
    
    // 最新のログを検索（診断完了済みのログを優先）
    $latest = $wpdb->get_row($wpdb->prepare(
        "SELECT id, result_count, user_feedback FROM {$logs_table} 
         WHERE session_id = %s 
         ORDER BY result_count DESC, id DESC 
         LIMIT 1",
        $session_id
    ));
    
    if ($latest) {
        $update_data = array('user_feedback' => $feedback);
        if ($satisfaction !== null) {
            $update_data['satisfaction_score'] = $satisfaction;
        }
        
        $result = $wpdb->update($logs_table, $update_data, array('id' => $latest->id));
        
        gip_log('Question log feedback updated', array(
            'log_id' => $latest->id,
            'previous_feedback' => $latest->user_feedback,
            'new_feedback' => $feedback,
            'affected_rows' => $result,
        ));
        
        return ($result !== false);
    }
    
    // ログが存在しない場合、セッション情報からログを作成してフィードバックを記録
    gip_log('No existing log found, attempting to create new log with feedback');
    
    $sessions_table = gip_table('sessions');
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT context FROM {$sessions_table} WHERE session_id = %s",
        $session_id
    ));
    
    if ($session && !empty($session->context)) {
        $context = json_decode($session->context, true);
        $collected = $context['collected_info'] ?? array();
        
        // フィードバック付きで新規ログを作成
        $insert_result = $wpdb->insert(
            $logs_table,
            array(
                'session_id' => $session_id,
                'user_type' => $collected['user_type'] ?? '',
                'prefecture' => $collected['prefecture'] ?? '',
                'municipality' => $collected['municipality'] ?? '',
                'purpose' => $collected['purpose'] ?? '',
                'clarification' => $collected['clarification'] ?? '',
                'detected_category' => $context['detected_category'] ?? '',
                'result_count' => 0,
                'user_feedback' => $feedback,
                'satisfaction_score' => $satisfaction,
                'created_at' => current_time('mysql'),
            )
        );
        
        gip_log('New log created with feedback', array(
            'insert_result' => $insert_result,
            'insert_id' => $wpdb->insert_id,
        ));
        
        return ($insert_result !== false);
    }
    
    gip_log('Failed to update or create feedback log - no session found');
    return false;
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

/**
 * リセット意図の検知
 */
function gip_detect_reset_intent($input) {
    $reset_keywords = array(
        '最初から', 'やり直す', 'やり直し', 'リセット', 'reset',
        '最初に戻る', '戻る', 'はじめから', '初めから',
        '新しく', '新規', '最初へ', 'クリア', 'clear',
    );
    
    $input_lower = mb_strtolower($input);
    foreach ($reset_keywords as $keyword) {
        if (strpos($input_lower, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * 再検索意図の検知
 */
function gip_detect_research_intent($input, $collected) {
    $input_lower = mb_strtolower($input);
    
    // 異なる補助金を探す意図
    if (preg_match('/(違う|別の|他の).*(補助金|助成金|制度)/', $input)) {
        return array('type' => 'different_grant', 'original' => $input);
    }
    
    // 用途変更での検索
    if (preg_match('/(設備投資|人材|IT|DX|販路|海外|省エネ|環境|セキュリティ|研究開発).*(で|の|を).*?(探|使える|申請|補助)/', $input)) {
        return array('type' => 'change_purpose', 'new_purpose' => $input);
    }
    
    // 「〜で使える補助金」パターン
    if (preg_match('/(.+)(で|に)(使える|申請できる|もらえる).*(補助金|助成金)/', $input, $matches)) {
        return array('type' => 'change_purpose', 'new_purpose' => $matches[1]);
    }
    
    // 条件変更キーワード
    $condition_keywords = array(
        '条件を変えて', '条件変更', '他の補助金', '別の補助金',
        '違う補助金', '再検索', '検索し直', '探し直',
    );
    
    foreach ($condition_keywords as $keyword) {
        if (strpos($input_lower, $keyword) !== false) {
            return array('type' => 'new_search', 'original' => $input);
        }
    }
    
    return false;
}

/**
 * 再検索処理
 */
function gip_handle_research($session_id, $context, $user_input, $research_intent) {
    global $wpdb;
    
    $collected = $context['collected_info'] ?? array();
    
    switch ($research_intent['type']) {
        case 'different_grant':
        case 'change_purpose':
            // 目的を変更して再検索
            if (!empty($research_intent['new_purpose'])) {
                $collected['purpose'] = $research_intent['new_purpose'];
            } else {
                // 目的入力をリクエスト
                $response_text = "承知しました。新しい条件で補助金をお探しします。\n\n";
                $response_text .= "どのような目的で補助金をお探しですか？";
                
                $options = gip_get_purpose_options($collected['user_type'] ?? 'corporation');
                
                $context['step'] = 'purpose';
                $context['collected_info'] = $collected;
                $wpdb->update(gip_table('sessions'), array('context' => wp_json_encode($context)), array('session_id' => $session_id));
                
                return array(
                    'success' => true,
                    'session_id' => $session_id,
                    'message' => $response_text,
                    'options' => $options,
                    'option_type' => 'single',
                    'hint' => '選択肢から選ぶか、自由にお書きください。',
                    'allow_input' => true,
                    'can_continue' => false,
                );
            }
            break;
            
        case 'new_search':
            // 新しい検索フロー
            $response_text = "新しい条件で補助金をお探しします。\n\n";
            $response_text .= "どのような目的で補助金をお探しですか？";
            
            $options = gip_get_purpose_options($collected['user_type'] ?? 'corporation');
            
            $context['step'] = 'purpose';
            $context['collected_info'] = $collected;
            $wpdb->update(gip_table('sessions'), array('context' => wp_json_encode($context)), array('session_id' => $session_id));
            
            return array(
                'success' => true,
                'session_id' => $session_id,
                'message' => $response_text,
                'options' => $options,
                'option_type' => 'single',
                'hint' => '選択肢から選ぶか、自由にお書きください。',
                'allow_input' => true,
                'can_continue' => false,
            );
    }
    
    // 再検索実行
    $filters = array(
        'user_type' => $collected['user_type'] ?? '',
        'prefecture' => $collected['prefecture'] ?? '',
        'municipality' => $collected['municipality'] ?? '',
        'status_open' => true,
    );
    
    $results = gip_execute_match($session_id, gip_build_natural_search_query($collected), $filters, $collected);
    
    $response_text = "新しい条件で検索した結果、";
    
    if (!empty($results)) {
        $response_text .= "【" . count($results) . "件】の補助金が見つかりました！";
        
        $context['step'] = 'results';
        $context['collected_info'] = $collected;
        $wpdb->update(gip_table('sessions'), array('context' => wp_json_encode($context)), array('session_id' => $session_id));
        
        return array(
            'success' => true,
            'session_id' => $session_id,
            'message' => $response_text,
            'results' => $results,
            'results_count' => count($results),
            'show_comparison' => true,
            'can_continue' => true,
            'allow_input' => true,
            'show_research_option' => true,
        );
    } else {
        $response_text .= "条件に合う補助金が見つかりませんでした。\n\n";
        $response_text .= "条件を変更して再検索しますか？";
        
        return array(
            'success' => true,
            'session_id' => $session_id,
            'message' => $response_text,
            'options' => array(
                array('id' => 'expand_area', 'label' => '都道府県全域で探す'),
                array('id' => 'national', 'label' => '全国対応の補助金を探す'),
                array('id' => 'change_purpose', 'label' => '目的を変えて探す'),
                array('id' => 'restart', 'label' => '最初からやり直す'),
            ),
            'can_continue' => false,
            'allow_input' => true,
        );
    }
}

/**
 * 深掘り質問の分析（Deep Dive機能）- AI動的プロンプト生成版
 * 
 * 【革新的変更】PHPの配列ベースの質問生成を廃止
 * Gemini AIにリアルタイムで質問を生成させることで、
 * どんなマニアックな相談にも熟練コンサルタントのように対応
 * 
 * 例：「ドローンで農薬散布したい」
 * → AIが「機体購入費ですか？免許取得ですか？散布サービス利用ですか？」と的確に質問
 */
function gip_analyze_user_needs_deep($collected, $context = array()) {
    $purpose = $collected['purpose'] ?? '';
    $clarification = $collected['clarification'] ?? '';
    $additional = $collected['additional_details'] ?? '';
    $user_type = $collected['user_type'] ?? '';
    $user_type_label = $collected['user_type_label'] ?? $user_type;
    $prefecture = $collected['prefecture'] ?? '';
    $deep_dive_count = $context['deep_dive_count'] ?? 0;
    
    // 検索クエリを作成（会話履歴全体）
    $search_query = trim($purpose . ' ' . $clarification . ' ' . $additional);
    
    // 強化版NLU: 詳細情報を抽出
    $detailed_intent = gip_extract_detailed_intent($search_query);
    
    // 理解度を評価
    $understanding_level = gip_evaluate_understanding_level($collected);
    
    // カテゴリを判定
    $category = gip_detect_purpose_category($search_query);
    
    // 十分な情報が集まっている場合は検索へ（deep_dive_count >= 2 かつ理解度60以上）
    if ($deep_dive_count >= 2 && $understanding_level >= 60) {
        return array(
            'needs_clarification' => false,
            'category' => $category,
            'detailed_intent' => $detailed_intent,
            'understanding_level' => $understanding_level,
        );
    }
    
    // 最大3回まで深掘り
    if ($deep_dive_count >= 3) {
        return array(
            'needs_clarification' => false,
            'category' => $category,
            'detailed_intent' => $detailed_intent,
            'understanding_level' => $understanding_level,
        );
    }
    
    // ========================================
    // AI動的プロンプト生成
    // ========================================
    $prompt = "あなたはプロの補助金コンサルタントです。
ユーザーの入力情報から、最適な補助金を検索するために「不足している情報」を特定し、
それを聞き出すための「たった1つの質問」と「4つの選択肢」を作成してください。

## ユーザー情報
- 属性: {$user_type_label}
- 地域: " . (!empty($prefecture) ? $prefecture : '未指定') . "
- 現在の要望: {$search_query}
- 深掘り回数: {$deep_dive_count}回目

## ルール
1. 業界、用途、金額規模、緊急度などが不明確な場合、それを特定する質問をする
2. ユーザーの入力にある言葉（例：「ドローン」「厨房」）を質問に含めて「理解している感」を出す
3. 選択肢のIDは英語（snake_case）、ラベルは日本語で
4. 質問は親身で丁寧なトーン、ただし絵文字は使わない（金融情報のため）
5. すでに十分に具体的で、これ以上聞く必要がない場合は needs_clarification: false を返す

## 出力形式（JSONのみ、説明文不要）
{
  \"needs_clarification\": true,
  \"question\": \"質問文（改行は\\nで表現）\",
  \"options\": [
    {\"id\": \"option_1\", \"label\": \"選択肢1\"},
    {\"id\": \"option_2\", \"label\": \"選択肢2\"},
    {\"id\": \"option_3\", \"label\": \"選択肢3\"},
    {\"id\": \"tell_detail\", \"label\": \"詳しく入力する\"}
  ],
  \"hint\": \"入力欄のプレースホルダー用ヒント\"
}";

    // Gemini APIを呼び出し（温度は低めで安定させる）
    $response = gip_call_gemini($prompt, array('temperature' => 0.3, 'max_tokens' => 800));
    
    // 赤文字ヒント（固定）
    $hint_important = '【精度アップのコツ】業種・金額・用途を具体的に書くと、より最適な補助金が見つかります';
    
    if (is_wp_error($response)) {
        // エラー時は従来の配列ベースにフォールバック
        gip_debug_log('AI深掘り質問生成エラー: ' . $response->get_error_message());
        return gip_get_deep_questions_legacy($collected, $context, $category, $detailed_intent, $hint_important);
    }
    
    // JSON解析
    if (preg_match('/\{[\s\S]*\}/u', $response, $matches)) {
        $parsed = json_decode($matches[0], true);
        if (is_array($parsed) && isset($parsed['needs_clarification'])) {
            // 成功
            $parsed['hint_important'] = $hint_important;
            $parsed['category'] = $category;
            $parsed['detailed_intent'] = $detailed_intent;
            return $parsed;
        }
    }
    
    // JSON解析失敗時もフォールバック
    gip_debug_log('AI深掘り質問JSON解析失敗: ' . $response);
    return gip_get_deep_questions_legacy($collected, $context, $category, $detailed_intent, $hint_important);
}

/**
 * 深掘り質問のフォールバック（従来の配列ベース）
 * API障害時やJSON解析失敗時に使用
 */
function gip_get_deep_questions_legacy($collected, $context, $category, $detailed_intent, $hint_important) {
    $purpose = $collected['purpose'] ?? '';
    $user_type = $collected['user_type'] ?? '';
    $deep_dive_count = $context['deep_dive_count'] ?? 0;
    
    // 従来の質問パターン
    $deep_questions = gip_get_deep_questions_patterns($purpose, $user_type);
    
    if ($deep_dive_count < 1) {
        $question_data = $deep_questions[$category] ?? $deep_questions['default'];
        
        $industry_prefix = '';
        if (!empty($detailed_intent['industry'])) {
            $industry = $detailed_intent['industry'];
            $industry_prefix = "{$industry}業に関するご相談ですね。\n\n";
        }
        
        return array(
            'needs_clarification' => true,
            'question' => $industry_prefix . $question_data['question'],
            'options' => $question_data['options'],
            'hint' => $question_data['hint'],
            'hint_important' => $hint_important,
            'category' => $category,
            'detailed_intent' => $detailed_intent,
        );
    }
    
    if ($deep_dive_count < 2) {
        $missing_info = array();
        if (empty($detailed_intent['industry'])) $missing_info[] = '業種・業界';
        if (empty($detailed_intent['budget_range'])) $missing_info[] = 'おおよその金額・規模';
        
        $question_text = "ありがとうございます。\n\nより適切な補助金をお探しするために、もう少し詳しくお聞かせください。";
        if (!empty($missing_info)) {
            $question_text .= "\n\n特に「" . implode('」「', $missing_info) . "」がわかると、マッチング精度が上がります。";
        }
        
        return array(
            'needs_clarification' => true,
            'question' => $question_text,
            'options' => array(
                array('id' => 'budget_small', 'label' => '50万円未満'),
                array('id' => 'budget_medium', 'label' => '50万円から200万円'),
                array('id' => 'budget_large', 'label' => '200万円から500万円'),
                array('id' => 'budget_xlarge', 'label' => '500万円から1000万円'),
                array('id' => 'budget_huge', 'label' => '1000万円以上'),
            ),
            'hint' => '選択肢を選ぶか、業種や金額を含めて自由にご入力ください。',
            'hint_important' => $hint_important,
            'category' => $category,
            'detailed_intent' => $detailed_intent,
        );
    }
    
    // 3回目
    return array(
        'needs_clarification' => true,
        'question' => "ありがとうございます。\n\n最後にもう少しだけお聞かせください。\n\nどのような課題を解決したいですか？",
        'options' => array(
            array('id' => 'goal_productivity', 'label' => '生産性・効率を上げたい'),
            array('id' => 'goal_cost', 'label' => 'コストを削減したい'),
            array('id' => 'goal_labor', 'label' => '人手不足を解消したい'),
            array('id' => 'goal_sales', 'label' => '売上を拡大したい'),
            array('id' => 'goal_other', 'label' => 'その他'),
        ),
        'hint' => '選択肢を選ぶか、自由にご入力ください。',
        'hint_important' => $hint_important,
        'category' => $category,
        'detailed_intent' => $detailed_intent,
    );
}

/**
 * 深掘り質問パターンを取得（フォールバック用）
 * AI動的生成が失敗した場合に使用
 */
function gip_get_deep_questions_patterns($purpose, $user_type = '') {
    return array(
        // ===== 個人・家庭向け =====
        'childcare' => array(
            'question' => "子育て・出産支援についてのご相談ですね。\n\nより適切な補助金をお探しするために、もう少し詳しくお聞かせください。\n\n具体的にどのような支援をお探しですか？",
            'options' => array(
                array('id' => 'childcare_allowance', 'label' => '出産・育児に関する給付金'),
                array('id' => 'childcare_equipment', 'label' => 'ベビー用品・育児用品の購入補助'),
                array('id' => 'childcare_service', 'label' => '保育サービス・託児支援'),
                array('id' => 'childcare_medical', 'label' => '医療費・健診費用の助成'),
                array('id' => 'childcare_education', 'label' => '教育費・学費支援'),
                array('id' => 'childcare_other', 'label' => 'その他の子育て支援'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        'housing' => array(
            'question' => "住宅に関するご相談ですね。\n\n具体的にどのような支援をお探しですか？",
            'options' => array(
                array('id' => 'housing_reform', 'label' => 'リフォーム・改修工事'),
                array('id' => 'housing_new', 'label' => '新築・購入支援'),
                array('id' => 'housing_barrier', 'label' => 'バリアフリー化・介護対応'),
                array('id' => 'housing_energy', 'label' => '省エネ・断熱・太陽光'),
                array('id' => 'housing_earthquake', 'label' => '耐震診断・耐震改修'),
                array('id' => 'housing_move', 'label' => '移住・定住支援'),
                array('id' => 'housing_other', 'label' => 'その他の住宅支援'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        'medical_welfare' => array(
            'question' => "医療・福祉に関するご相談ですね。\n\n具体的にどのような支援をお探しですか？",
            'options' => array(
                array('id' => 'medical_expense', 'label' => '医療費助成・高額療養費'),
                array('id' => 'medical_disability', 'label' => '障がい者支援・福祉サービス'),
                array('id' => 'medical_nursing', 'label' => '介護サービス・介護用品'),
                array('id' => 'medical_mental', 'label' => '精神保健・カウンセリング'),
                array('id' => 'medical_dental', 'label' => '歯科・口腔ケア'),
                array('id' => 'medical_other', 'label' => 'その他の医療・福祉'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        'education' => array(
            'question' => "教育・学習に関するご相談ですね。\n\n具体的にどのような支援をお探しですか？",
            'options' => array(
                array('id' => 'edu_scholarship', 'label' => '奨学金・学費支援'),
                array('id' => 'edu_qualification', 'label' => '資格取得・スキルアップ'),
                array('id' => 'edu_study_abroad', 'label' => '留学・海外研修'),
                array('id' => 'edu_lifelong', 'label' => '生涯学習・社会人教育'),
                array('id' => 'edu_children', 'label' => '子どもの習い事・学習塾'),
                array('id' => 'edu_other', 'label' => 'その他の教育支援'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        'senior' => array(
            'question' => "シニア・高齢者向け支援に関するご相談ですね。\n\n具体的にどのような支援をお探しですか？",
            'options' => array(
                array('id' => 'senior_pension', 'label' => '年金・生活支援'),
                array('id' => 'senior_care', 'label' => '介護・ケアサービス'),
                array('id' => 'senior_health', 'label' => '健康増進・予防医療'),
                array('id' => 'senior_activity', 'label' => '社会参加・生きがい活動'),
                array('id' => 'senior_housing', 'label' => '高齢者住宅・施設'),
                array('id' => 'senior_other', 'label' => 'その他のシニア支援'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        
        // ===== 事業者向け =====
        'it_digital' => array(
            'question' => "IT・デジタル化についてのご相談ですね。\n\n具体的にどのような用途をお考えですか？",
            'options' => array(
                array('id' => 'it_accounting', 'label' => '会計・経理システム'),
                array('id' => 'it_sales', 'label' => '販売管理・顧客管理（CRM）'),
                array('id' => 'it_ec', 'label' => 'ECサイト・ネット販売'),
                array('id' => 'it_security', 'label' => 'セキュリティ対策'),
                array('id' => 'it_ai', 'label' => 'AI・自動化・RPA'),
                array('id' => 'it_cloud', 'label' => 'クラウド移行・テレワーク'),
                array('id' => 'it_other', 'label' => 'その他のIT投資'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        'equipment' => array(
            'question' => "設備投資についてのご相談ですね。\n\n具体的にどのような設備をお考えですか？",
            'options' => array(
                array('id' => 'equip_production', 'label' => '生産設備・製造機械'),
                array('id' => 'equip_vehicle', 'label' => '車両・運搬機器'),
                array('id' => 'equip_store', 'label' => '店舗設備・内装'),
                array('id' => 'equip_office', 'label' => '事務機器・オフィス設備'),
                array('id' => 'equip_kitchen', 'label' => '厨房設備・飲食設備'),
                array('id' => 'equip_medical', 'label' => '医療・介護設備'),
                array('id' => 'equip_other', 'label' => 'その他の設備'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        'hr_employment' => array(
            'question' => "人材・雇用についてのご相談ですね。\n\n具体的にどのような支援をお探しですか？",
            'options' => array(
                array('id' => 'hr_hiring', 'label' => '新規採用・雇用'),
                array('id' => 'hr_training', 'label' => '社員研修・教育'),
                array('id' => 'hr_workstyle', 'label' => '働き方改革・テレワーク'),
                array('id' => 'hr_welfare', 'label' => '障がい者・高齢者雇用'),
                array('id' => 'hr_foreign', 'label' => '外国人雇用'),
                array('id' => 'hr_retention', 'label' => '離職防止・定着支援'),
                array('id' => 'hr_other', 'label' => 'その他の人材支援'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        'startup' => array(
            'question' => "創業・起業についてのご相談ですね。\n\n現在の状況はどれに近いですか？",
            'options' => array(
                array('id' => 'startup_planning', 'label' => 'これから創業予定'),
                array('id' => 'startup_recent', 'label' => '創業して間もない（3年以内）'),
                array('id' => 'startup_second', 'label' => '第二創業・事業転換'),
                array('id' => 'startup_succession', 'label' => '事業承継・M&A'),
                array('id' => 'startup_social', 'label' => '社会起業・ソーシャルビジネス'),
                array('id' => 'startup_other', 'label' => 'その他'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        'sales' => array(
            'question' => "販路開拓についてのご相談ですね。\n\n具体的にどのような展開をお考えですか？",
            'options' => array(
                array('id' => 'sales_domestic', 'label' => '国内市場開拓'),
                array('id' => 'sales_overseas', 'label' => '海外市場・輸出'),
                array('id' => 'sales_online', 'label' => 'オンライン販売強化'),
                array('id' => 'sales_exhibition', 'label' => '展示会・商談会出展'),
                array('id' => 'sales_branding', 'label' => 'ブランディング・PR'),
                array('id' => 'sales_other', 'label' => 'その他の販路施策'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        'energy' => array(
            'question' => "省エネ・環境についてのご相談ですね。\n\n具体的にどのような対策をお考えですか？",
            'options' => array(
                array('id' => 'energy_solar', 'label' => '太陽光・再生可能エネルギー'),
                array('id' => 'energy_ev', 'label' => '電気自動車・EV充電設備'),
                array('id' => 'energy_save', 'label' => '省エネ設備への更新'),
                array('id' => 'energy_insulation', 'label' => '断熱・ZEH・ZEB'),
                array('id' => 'energy_carbon', 'label' => '脱炭素・CO2削減'),
                array('id' => 'energy_waste', 'label' => '廃棄物削減・リサイクル'),
                array('id' => 'energy_other', 'label' => 'その他の環境対策'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        
        // ===== 地域・文化・農業 =====
        'local_culture' => array(
            'question' => "地域・文化活動についてのご相談ですね。\n\n具体的にどのような活動をお考えですか？",
            'options' => array(
                array('id' => 'local_event', 'label' => '地域イベント・祭り'),
                array('id' => 'local_art', 'label' => '芸術・文化振興'),
                array('id' => 'local_community', 'label' => 'コミュニティ活動・交流'),
                array('id' => 'local_tourism', 'label' => '観光・まちづくり'),
                array('id' => 'local_history', 'label' => '歴史・伝統文化保存'),
                array('id' => 'local_sports', 'label' => 'スポーツ振興'),
                array('id' => 'local_other', 'label' => 'その他の地域活動'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        'agriculture' => array(
            'question' => "農業・林業・水産業についてのご相談ですね。\n\n具体的にどのような支援をお探しですか？",
            'options' => array(
                array('id' => 'agri_equipment', 'label' => '農機具・設備導入'),
                array('id' => 'agri_new_farmer', 'label' => '新規就農支援'),
                array('id' => 'agri_6th', 'label' => '6次産業化・加工販売'),
                array('id' => 'agri_organic', 'label' => '有機農業・環境保全'),
                array('id' => 'agri_forestry', 'label' => '林業支援'),
                array('id' => 'agri_fishery', 'label' => '水産業・漁業支援'),
                array('id' => 'agri_other', 'label' => 'その他の農林水産支援'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        'tourism' => array(
            'question' => "観光・宿泊業についてのご相談ですね。\n\n具体的にどのような支援をお探しですか？",
            'options' => array(
                array('id' => 'tourism_facility', 'label' => '施設改修・整備'),
                array('id' => 'tourism_inbound', 'label' => 'インバウンド対応'),
                array('id' => 'tourism_promotion', 'label' => '観光PR・誘客'),
                array('id' => 'tourism_experience', 'label' => '体験型観光・コンテンツ'),
                array('id' => 'tourism_wifi', 'label' => 'Wi-Fi・多言語対応'),
                array('id' => 'tourism_other', 'label' => 'その他の観光支援'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        
        // ===== NPO・団体向け =====
        'npo' => array(
            'question' => "NPO・市民活動についてのご相談ですね。\n\n具体的にどのような活動をお考えですか？",
            'options' => array(
                array('id' => 'npo_welfare', 'label' => '福祉・介護活動'),
                array('id' => 'npo_education', 'label' => '教育・子ども支援'),
                array('id' => 'npo_environment', 'label' => '環境保全活動'),
                array('id' => 'npo_international', 'label' => '国際協力・多文化共生'),
                array('id' => 'npo_disaster', 'label' => '防災・復興支援'),
                array('id' => 'npo_community', 'label' => '地域づくり・まちづくり'),
                array('id' => 'npo_other', 'label' => 'その他のNPO活動'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        
        // ===== 防災・安全 =====
        'disaster' => array(
            'question' => "防災・安全対策についてのご相談ですね。\n\n具体的にどのような対策をお考えですか？",
            'options' => array(
                array('id' => 'disaster_earthquake', 'label' => '耐震化・地震対策'),
                array('id' => 'disaster_flood', 'label' => '浸水対策・水害対策'),
                array('id' => 'disaster_fire', 'label' => '防火・消防設備'),
                array('id' => 'disaster_security', 'label' => '防犯対策・セキュリティ'),
                array('id' => 'disaster_bcp', 'label' => 'BCP・事業継続計画'),
                array('id' => 'disaster_other', 'label' => 'その他の防災対策'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        
        // ===== 研究・開発 =====
        'research' => array(
            'question' => "研究開発についてのご相談ですね。\n\n具体的にどのような研究をお考えですか？",
            'options' => array(
                array('id' => 'research_product', 'label' => '新製品・新技術開発'),
                array('id' => 'research_academic', 'label' => '学術研究・基礎研究'),
                array('id' => 'research_collab', 'label' => '産学連携・共同研究'),
                array('id' => 'research_patent', 'label' => '特許・知財取得'),
                array('id' => 'research_other', 'label' => 'その他の研究開発'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
        
        // ===== デフォルト =====
        'default' => array(
            'question' => $purpose . "についてのご相談ですね。\n\nより適切な補助金をお探しするために、もう少し詳しくお聞かせください。",
            'options' => array(
                array('id' => 'default_business', 'label' => '事業・ビジネス関連'),
                array('id' => 'default_personal', 'label' => '個人・家庭向け'),
                array('id' => 'default_community', 'label' => '地域・コミュニティ活動'),
                array('id' => 'default_other', 'label' => 'その他'),
            ),
            'hint' => '選択肢を選ぶか、自由にご入力ください。',
        ),
    );
}

/**
 * ユーザーの目的からカテゴリを判定（大幅拡充版）
 */
function gip_detect_purpose_category($text) {
    $categories = array(
        // 個人・家庭向け
        'childcare' => array('子育て', '出産', '育児', 'ベビー', '保育', '妊娠', '赤ちゃん', '子ども', '乳幼児', '産休', '育休', 'マタニティ'),
        'housing' => array('住宅', 'リフォーム', '改修', '新築', 'バリアフリー', '断熱', '耐震', 'マイホーム', '家', '住まい', '移住', '定住'),
        'medical_welfare' => array('医療', '福祉', '介護', '障がい', '障害', '健康', '病院', '診療', '治療', 'リハビリ', '看護'),
        'education' => array('教育', '学校', '学習', '奨学', '塾', '習い事', '資格', '留学', 'スキルアップ'),
        'senior' => array('高齢', 'シニア', '年金', '老後', '介護予防', '生きがい'),
        
        // 事業者向け
        'it_digital' => array('IT', 'DX', 'デジタル', 'システム', 'ソフト', 'Web', 'パソコン', 'ホームページ', 'AI', '自動化', 'RPA', 'クラウド'),
        'equipment' => array('設備', '機械', '製造', '生産', '工場', '装置', '機器', '厨房'),
        'hr_employment' => array('人材', '採用', '雇用', '従業員', '人手', 'スタッフ', '研修', '働き方', 'テレワーク', '外国人'),
        'startup' => array('創業', '起業', 'スタートアップ', '新規事業', '開業', '独立', '事業承継', 'M&A'),
        'sales' => array('販路', '販売', '輸出', 'マーケティング', 'EC', '展示会', 'ブランド', 'PR', '広告'),
        'energy' => array('省エネ', '環境', 'エネルギー', 'EV', '太陽光', '電気', 'エコ', 'ZEH', 'ZEB', '脱炭素', 'CO2'),
        
        // 地域・文化・農業
        'local_culture' => array('地域', '文化', '芸術', 'イベント', '祭り', 'まちづくり', '振興', '活性化', 'スポーツ', '歴史', '伝統'),
        'agriculture' => array('農業', '農家', '農産', '林業', '水産', '漁業', '就農', '6次産業', '有機'),
        'tourism' => array('観光', '宿泊', 'ホテル', '旅館', '民泊', 'インバウンド', '旅行'),
        
        // NPO・団体向け
        'npo' => array('NPO', 'NGO', 'ボランティア', '市民活動', '公益', '非営利', '団体'),
        
        // 防災・安全
        'disaster' => array('防災', '防犯', '安全', '耐震', '浸水', '水害', '消防', 'BCP', 'セキュリティ'),
        
        // 研究・開発
        'research' => array('研究', '開発', 'R&D', '技術', '特許', '知財', '産学連携', '新製品'),
    );
    
    foreach ($categories as $category => $keywords) {
        foreach ($keywords as $kw) {
            if (mb_strpos($text, $kw) !== false) {
                return $category;
            }
        }
    }
    
    return 'default';
}

/**
 * 後方互換性のためのダミー配列（既存コードで参照されている場合）
 */
function gip_get_subsidy_feature_patterns() {
    // 補助金の特徴に基づく提案パターン（当てに行く質問）- 後方互換用
    $subsidy_features = array(
        'it_digital' => array(
            'keywords' => array('it', 'IT', 'デジタル', 'システム', 'ソフト', 'パソコン', 'DX'),
            'question' => "IT導入に関するご相談ですね。\n\nこのような補助金をお探しではありませんか？",
            'options' => array(
                array('id' => 'it_intro', 'label' => 'IT導入補助金（会計・受発注など）'),
                array('id' => 'monodukuri_digital', 'label' => 'ものづくり補助金（デジタル枠）'),
                array('id' => 'jisedai', 'label' => '事業再構築補助金（DX推進）'),
                array('id' => 'other_it', 'label' => 'その他のIT関連補助金'),
            ),
            'hint' => '具体的な補助金名がわからなくても大丈夫です。用途を教えてください。',
        ),
        'equipment' => array(
            'keywords' => array('設備', '機械', '製造', '生産', '工場', '装置'),
            'question' => "設備投資に関するご相談ですね。\n\nこのような補助金をお探しではありませんか？",
            'options' => array(
                array('id' => 'monodukuri', 'label' => 'ものづくり補助金（設備投資）'),
                array('id' => 'jisedai_equip', 'label' => '事業再構築補助金（設備導入）'),
                array('id' => 'shoene', 'label' => '省エネ補助金（高効率設備）'),
                array('id' => 'other_equip', 'label' => 'その他の設備投資補助金'),
            ),
            'hint' => '導入予定の設備の種類や金額がわかると、より適切な補助金をご提案できます。',
        ),
        'new_business' => array(
            'keywords' => array('新規事業', '新事業', '創業', '起業', 'スタートアップ', '開業'),
            'question' => "新規事業・創業に関するご相談ですね。\n\nこのような補助金をお探しではありませんか？",
            'options' => array(
                array('id' => 'jisedai_new', 'label' => '事業再構築補助金（新分野展開）'),
                array('id' => 'sogyo', 'label' => '創業補助金・起業支援金'),
                array('id' => 'chiiki', 'label' => '地域の創業支援（都道府県独自）'),
                array('id' => 'other_new', 'label' => 'その他の新規事業支援'),
            ),
            'hint' => '事業計画の有無や創業時期を教えていただくとより適切にご案内できます。',
        ),
        'hr_employment' => array(
            'keywords' => array('人材', '採用', '雇用', '従業員', '人手', 'スタッフ'),
            'question' => "人材・雇用に関するご相談ですね。\n\nこのような補助金・助成金をお探しではありませんか？",
            'options' => array(
                array('id' => 'koyou_chosei', 'label' => '雇用調整助成金'),
                array('id' => 'career_up', 'label' => 'キャリアアップ助成金'),
                array('id' => 'jinzai', 'label' => '人材開発支援助成金'),
                array('id' => 'try_koyou', 'label' => 'トライアル雇用助成金'),
                array('id' => 'other_hr', 'label' => 'その他の人材関連助成金'),
            ),
            'hint' => '正社員化・研修・新規採用など、目的によって最適な助成金が異なります。',
        ),
        'sales_marketing' => array(
            'keywords' => array('販売', '販路', '広告', 'マーケティング', '集客', '宣伝', 'PR'),
            'question' => "販路開拓・マーケティングに関するご相談ですね。\n\nこのような補助金をお探しではありませんか？",
            'options' => array(
                array('id' => 'shokibo', 'label' => '小規模事業者持続化補助金'),
                array('id' => 'jisedai_sales', 'label' => '事業再構築補助金（販路開拓）'),
                array('id' => 'ec_support', 'label' => 'ECサイト構築支援'),
                array('id' => 'other_sales', 'label' => 'その他の販路開拓支援'),
            ),
            'hint' => 'チラシ作成からEC構築まで幅広い販促活動が対象になる補助金があります。',
        ),
        'energy_environment' => array(
            'keywords' => array('省エネ', '環境', 'CO2', '脱炭素', 'カーボン', '太陽光', 'EV'),
            'question' => "省エネ・環境対策に関するご相談ですね。\n\nこのような補助金をお探しではありませんか？",
            'options' => array(
                array('id' => 'shoene_main', 'label' => '省エネルギー投資促進支援'),
                array('id' => 'ev_charge', 'label' => 'EV・充電設備導入補助金'),
                array('id' => 'solar', 'label' => '太陽光・再エネ設備補助金'),
                array('id' => 'other_energy', 'label' => 'その他の環境関連補助金'),
            ),
            'hint' => '設備の種類や導入規模によって適用できる補助金が変わります。',
        ),
    );
    
    return $subsidy_features;
}

/**
 * AI補助金推測機能（実DB連携版）
 * ユーザーの入力から実際にDBに存在する補助金を検索し、提案
 */
function gip_guess_subsidy_like_genie($collected, $context = array()) {
    $purpose = $collected['purpose'] ?? '';
    $clarification = $collected['clarification'] ?? '';
    $additional = $collected['additional_details'] ?? '';
    $user_type = $collected['user_type'] ?? '';
    $prefecture = $collected['prefecture'] ?? '';
    $municipality = $collected['municipality'] ?? '';
    $guess_count = $context['guess_count'] ?? 0;
    $previous_guesses = $context['previous_guesses'] ?? array();
    
    // 3回以上推測したら終了
    if ($guess_count >= 3) {
        return array('should_guess' => false);
    }
    
    // 収集した情報を全て結合して検索クエリを作成
    $search_query = trim($purpose . ' ' . $clarification . ' ' . $additional);
    
    // ユーザーの意図を理解するためのキーワード抽出
    $user_keywords = gip_extract_intent_keywords($search_query);
    
    // 実際のDBから候補となる補助金を検索（ハイブリッド検索）
    $filters = array(
        'user_type' => $user_type,
        'prefecture' => $prefecture,
        'municipality' => $municipality,
        'status_open' => true,
    );
    
    // ベクトル検索 + キーワードブースト検索
    $candidates = gip_hybrid_search($search_query, $user_keywords, $filters, 10, $previous_guesses);
    
    if (empty($candidates)) {
        // フォールバック: フィルター緩和して再検索
        $fallback_filters = array(
            'user_type' => $user_type,
            'prefecture' => $prefecture,
            'status_open' => true,
        );
        $candidates = gip_hybrid_search($search_query, $user_keywords, $fallback_filters, 10, $previous_guesses);
    }
    
    if (empty($candidates)) {
        return array('should_guess' => false);
    }
    
    // 最もマッチ度の高い補助金を取得
    $top_grant_id = key($candidates);
    $top_score = current($candidates);
    
    // スコアが低すぎる場合は推測しない（閾値を下げて対応）
    if ($top_score < 0.2) {
        return array('should_guess' => false);
    }
    
    // 補助金の詳細を取得
    $grant = get_post($top_grant_id);
    if (!$grant) {
        return array('should_guess' => false);
    }
    
    $grant_title = $grant->post_title;
    $grant_content = $grant->post_content;
    $grant_excerpt = wp_trim_words(wp_strip_all_tags($grant_content), 50, '...');
    
    // ACFメタデータを取得（正しいフィールド名を使用）
    $grant_amount = '';
    $grant_deadline = '';
    
    if (function_exists('get_field')) {
        $max_amount = get_field('max_amount', $top_grant_id);
        $max_numeric = get_field('max_amount_numeric', $top_grant_id);
        $deadline = get_field('deadline', $top_grant_id);
        $deadline_date = get_field('deadline_date', $top_grant_id);
        
        // 金額フォーマット
        if (!empty($max_numeric) && intval($max_numeric) > 0) {
            $num = intval($max_numeric);
            if ($num >= 100000000) {
                $grant_amount = number_format($num / 100000000, 1) . '億円';
            } elseif ($num >= 10000) {
                $grant_amount = number_format($num / 10000) . '万円';
            } else {
                $grant_amount = number_format($num) . '円';
            }
        } elseif (!empty($max_amount)) {
            $grant_amount = $max_amount;
        }
        
        // 締切フォーマット
        if (!empty($deadline_date)) {
            $ts = strtotime($deadline_date);
            if ($ts) {
                $days = floor(($ts - current_time('timestamp')) / 86400);
                $grant_deadline = date('Y年n月j日', $ts);
                if ($days > 0 && $days <= 30) {
                    $grant_deadline .= '（残り' . $days . '日）';
                }
            }
        } elseif (!empty($deadline)) {
            $grant_deadline = $deadline;
        }
    }
    
    // 特徴を構築
    $features = array();
    if (!empty($grant_amount)) {
        $features[] = '補助上限: ' . $grant_amount;
    }
    if (!empty($grant_deadline)) {
        $features[] = '申請期限: ' . $grant_deadline;
    }
    
    // 地域情報を追加
    $grant_prefs = get_the_terms($top_grant_id, 'grant_prefecture');
    $grant_munis = get_the_terms($top_grant_id, 'grant_municipality');
    if ($grant_munis && !is_wp_error($grant_munis)) {
        $muni_names = wp_list_pluck($grant_munis, 'name');
        $features[] = '対象地域: ' . implode('、', array_slice($muni_names, 0, 2));
    } elseif ($grant_prefs && !is_wp_error($grant_prefs)) {
        $pref_names = wp_list_pluck($grant_prefs, 'name');
        $features[] = '対象地域: ' . implode('、', $pref_names);
    }
    
    // 補助金の概要抜粋（ユーザーが内容を理解できるように）
    $grant_summary = '';
    if (!empty($grant_excerpt)) {
        $grant_summary = mb_substr(wp_strip_all_tags($grant_excerpt), 0, 80);
        if (mb_strlen($grant_excerpt) > 80) {
            $grant_summary .= '...';
        }
    }
    
    // 特徴が少ない場合は概要を追加
    if (count($features) < 2 && !empty($grant_summary)) {
        $features[] = $grant_summary;
    }
    
    // カテゴリを判定（深掘り質問の生成に使用）
    $category = '補助金';
    $category_keywords = array(
        'IT・デジタル' => array('IT', 'デジタル', 'システム', 'ソフト', 'DX', 'パソコン', 'クラウド'),
        '設備投資' => array('設備', '機械', '製造', 'ものづくり', '機器'),
        '人材・雇用' => array('人材', '雇用', '採用', '研修', 'キャリア', '従業員'),
        '販路開拓' => array('販路', '販売', '広告', 'マーケティング', 'EC', '輸出'),
        '省エネ・環境' => array('省エネ', '環境', 'エネルギー', 'EV', '太陽光', '電気', '脱炭素'),
        '創業・起業' => array('創業', '起業', 'スタートアップ', '新規事業', '開業'),
        '地域・文化' => array('地域', '活性化', 'イベント', '文化', '芸術', 'まちづくり', '振興', '観光'),
    );
    
    $combined_text = $grant_title . ' ' . $grant_excerpt . ' ' . $purpose . ' ' . $clarification;
    foreach ($category_keywords as $cat => $keywords) {
        foreach ($keywords as $kw) {
            if (mb_strpos($combined_text, $kw) !== false) {
                $category = $cat;
                break 2;
            }
        }
    }
    
    // カテゴリに基づく深掘り質問を生成
    $followup_question = gip_get_category_followup_question($category, $combined_text);
    
    return array(
        'should_guess' => true,
        'guess_category' => $category,
        'guess_subsidy' => $grant_title,
        'guess_subsidy_id' => $top_grant_id,
        'guess_reason' => 'ご要望に合致する補助金です',
        'guess_features' => array_slice($features, 0, 3),
        'guess_summary' => $grant_summary,
        'confidence' => min(intval($top_score * 100), 95),
        'followup_question' => $followup_question,
        'narrowing_questions' => array(
            'この補助金の対象経費について詳しく知りたい',
            '申請に必要な書類を確認したい',
        ),
    );
}

/**
 * ユーザー入力から意図キーワードを抽出
 */
function gip_extract_intent_keywords($query) {
    $keywords = array();
    
    // 意図を表すキーワードパターン
    $intent_patterns = array(
        // 地域・文化系
        '地域' => array('地域', '地方', 'まち', '町', '村'),
        '文化' => array('文化', '芸術', 'アート', '伝統', '芸能'),
        'イベント' => array('イベント', '祭り', '催し', 'フェス', '行事'),
        '活性化' => array('活性化', '振興', '支援', '促進', '推進'),
        
        // IT系
        'IT' => array('IT', 'DX', 'デジタル', 'システム', 'ソフト', 'Web'),
        
        // 設備系
        '設備' => array('設備', '機械', '製造', 'ものづくり', '工場'),
        
        // 人材系
        '人材' => array('人材', '雇用', '採用', '研修', '教育', '従業員'),
        
        // 環境系
        '環境' => array('省エネ', '環境', 'エネルギー', 'EV', '太陽光', 'エコ'),
        
        // 創業系
        '創業' => array('創業', '起業', 'スタートアップ', '新規', '開業'),
        
        // 販路系
        '販路' => array('販路', '販売', '広告', 'マーケティング', 'EC', '輸出'),
    );
    
    foreach ($intent_patterns as $category => $patterns) {
        foreach ($patterns as $pattern) {
            if (mb_strpos($query, $pattern) !== false) {
                $keywords[] = $category;
                break;
            }
        }
    }
    
    return array_unique($keywords);
}

/**
 * 強化版NLU: ユーザーの自然言語入力から詳細情報を抽出
 * 業種、金額、具体的な用途、対象者などを推定
 */
function gip_extract_detailed_intent($text) {
    $result = array(
        'industry' => null,        // 業種
        'budget_range' => null,    // 予算規模
        'specific_use' => array(), // 具体的な用途
        'urgency' => null,         // 緊急度
        'target_type' => null,     // 対象者タイプ
        'keywords' => array(),     // 抽出キーワード
        'confidence' => 0,         // 理解度スコア
    );
    
    // 業種パターン
    $industry_patterns = array(
        '飲食' => array('飲食', 'レストラン', '食堂', 'カフェ', '居酒屋', 'バー', 'ラーメン', '寿司', '焼肉', '厨房'),
        '小売' => array('小売', '店舗', 'ショップ', '販売店', '専門店', '物販', '雑貨'),
        '製造' => array('製造', '工場', 'メーカー', '生産', '加工', 'ものづくり', '金属', 'プラスチック'),
        '建設' => array('建設', '工事', '建築', '土木', 'リフォーム', '塗装', '設備工事'),
        'IT' => array('IT', 'システム', 'ソフトウェア', 'Web', 'アプリ', 'プログラム'),
        '医療' => array('医療', '病院', 'クリニック', '歯科', '介護', '福祉施設', '薬局'),
        '美容' => array('美容', '理容', 'サロン', 'エステ', 'ネイル', '美容室'),
        'サービス' => array('サービス', 'コンサル', '人材', '派遣', '清掃', '警備'),
        '農業' => array('農業', '農家', '畑', '牧場', '酪農', '養殖', '漁業'),
        '観光' => array('観光', 'ホテル', '旅館', '民泊', '旅行', 'ツアー'),
        '運輸' => array('運輸', '物流', '運送', '配送', 'タクシー', 'トラック'),
        '教育' => array('教育', '塾', 'スクール', '学校', '研修', 'セミナー'),
    );
    
    foreach ($industry_patterns as $industry => $patterns) {
        foreach ($patterns as $pattern) {
            if (mb_strpos($text, $pattern) !== false) {
                $result['industry'] = $industry;
                $result['keywords'][] = $pattern;
                $result['confidence'] += 15;
                break 2;
            }
        }
    }
    
    // 金額パターン（万円・億円を認識）
    if (preg_match('/(\d+(?:,\d{3})*)\s*(?:万|萬)/', $text, $matches)) {
        $amount = (int)str_replace(',', '', $matches[1]);
        if ($amount < 100) {
            $result['budget_range'] = 'small';
        } elseif ($amount < 500) {
            $result['budget_range'] = 'medium';
        } elseif ($amount < 1000) {
            $result['budget_range'] = 'large';
        } else {
            $result['budget_range'] = 'very_large';
        }
        $result['keywords'][] = $matches[0];
        $result['confidence'] += 20;
    }
    
    // 具体的な用途パターン
    $use_patterns = array(
        '購入' => array('購入', '買う', '導入', '調達'),
        '改修' => array('改修', '修理', '更新', '交換', 'リニューアル'),
        '新規' => array('新規', '新しく', '新店', '開業', '立ち上げ'),
        '拡大' => array('拡大', '増設', '拡張', '広げ'),
        '効率化' => array('効率化', '自動化', '省力化', '時短'),
        '人材' => array('採用', '雇用', '研修', '人材育成'),
        '広告' => array('広告', '宣伝', 'PR', 'マーケティング', '販促'),
    );
    
    foreach ($use_patterns as $use => $patterns) {
        foreach ($patterns as $pattern) {
            if (mb_strpos($text, $pattern) !== false) {
                $result['specific_use'][] = $use;
                $result['keywords'][] = $pattern;
                $result['confidence'] += 10;
            }
        }
    }
    
    // 緊急度パターン
    if (preg_match('/(急ぎ|至急|すぐ|早め|今月|今年中|締切)/', $text)) {
        $result['urgency'] = 'high';
        $result['confidence'] += 5;
    }
    
    // 対象者タイプの推定
    if (preg_match('/(個人|一人|自分|副業|フリー)/', $text)) {
        $result['target_type'] = 'personal';
    } elseif (preg_match('/(法人|会社|企業|株式会社|合同会社)/', $text)) {
        $result['target_type'] = 'corporation';
    } elseif (preg_match('/(創業|起業|これから|予定)/', $text)) {
        $result['target_type'] = 'startup';
    } elseif (preg_match('/(NPO|ボランティア|非営利|団体)/', $text)) {
        $result['target_type'] = 'npo';
    }
    
    // 理解度の正規化（0-100）
    $result['confidence'] = min(100, $result['confidence']);
    $result['specific_use'] = array_unique($result['specific_use']);
    
    return $result;
}

/**
 * ユーザー入力の理解度を評価
 * 補助金検索に十分な情報があるかを判定
 */
function gip_evaluate_understanding_level($collected) {
    $score = 0;
    $max_score = 100;
    
    // 基本情報
    if (!empty($collected['user_type'])) $score += 15;
    if (!empty($collected['prefecture'])) $score += 15;
    if (!empty($collected['municipality'])) $score += 5;
    
    // 目的情報
    if (!empty($collected['purpose'])) {
        $purpose_len = mb_strlen($collected['purpose']);
        if ($purpose_len > 50) {
            $score += 25;
        } elseif ($purpose_len > 20) {
            $score += 15;
        } elseif ($purpose_len > 5) {
            $score += 10;
        }
    }
    
    // 詳細情報
    if (!empty($collected['clarification'])) {
        $clarification_len = mb_strlen($collected['clarification']);
        if ($clarification_len > 30) {
            $score += 20;
        } elseif ($clarification_len > 10) {
            $score += 10;
        }
    }
    
    // 追加情報
    if (!empty($collected['additional_details'])) $score += 10;
    if (!empty($collected['industry'])) $score += 10;
    if (!empty($collected['budget_range'])) $score += 10;
    
    return min($max_score, $score);
}

/**
 * カテゴリに基づく深掘り質問を生成
 * ユーザーが補助金の内容を理解できるよう、具体的な質問を返す
 */
function gip_get_category_followup_question($category, $context_text = '') {
    // カテゴリごとの深掘り質問パターン
    $followup_patterns = array(
        'IT・デジタル' => array(
            'question' => 'どのようなIT・デジタル化をご検討ですか？',
            'options' => array(
                array('id' => 'system', 'label' => '業務システム導入（会計、在庫管理等）'),
                array('id' => 'ec', 'label' => 'ECサイト・ネット販売'),
                array('id' => 'security', 'label' => 'セキュリティ対策'),
                array('id' => 'automation', 'label' => '業務自動化・効率化'),
                array('id' => 'other_it', 'label' => 'その他のIT投資'),
            ),
        ),
        '設備投資' => array(
            'question' => 'どのような設備をご検討ですか？',
            'options' => array(
                array('id' => 'production', 'label' => '生産設備・製造機械'),
                array('id' => 'vehicle', 'label' => '車両・運搬機器'),
                array('id' => 'office', 'label' => '事務機器・オフィス設備'),
                array('id' => 'store', 'label' => '店舗設備・内装'),
                array('id' => 'other_equip', 'label' => 'その他の設備'),
            ),
        ),
        '人材・雇用' => array(
            'question' => 'どのような人材関連の取り組みをご検討ですか？',
            'options' => array(
                array('id' => 'hiring', 'label' => '新規採用・雇用'),
                array('id' => 'training', 'label' => '社員研修・教育'),
                array('id' => 'workstyle', 'label' => '働き方改革・福利厚生'),
                array('id' => 'welfare', 'label' => '障がい者・高齢者雇用'),
                array('id' => 'other_hr', 'label' => 'その他の人材施策'),
            ),
        ),
        '販路開拓' => array(
            'question' => 'どのような販路開拓をご検討ですか？',
            'options' => array(
                array('id' => 'domestic', 'label' => '国内市場開拓'),
                array('id' => 'overseas', 'label' => '海外市場・輸出'),
                array('id' => 'online', 'label' => 'オンライン販売強化'),
                array('id' => 'exhibition', 'label' => '展示会・商談会出展'),
                array('id' => 'other_sales', 'label' => 'その他の販路施策'),
            ),
        ),
        '省エネ・環境' => array(
            'question' => 'どのような環境・省エネ対策をご検討ですか？',
            'options' => array(
                array('id' => 'solar', 'label' => '太陽光・再生可能エネルギー'),
                array('id' => 'ev', 'label' => '電気自動車・EV充電設備'),
                array('id' => 'energy_save', 'label' => '省エネ設備への更新'),
                array('id' => 'carbon', 'label' => '脱炭素・CO2削減'),
                array('id' => 'other_eco', 'label' => 'その他の環境対策'),
            ),
        ),
        '創業・起業' => array(
            'question' => 'どのような創業・起業をご検討ですか？',
            'options' => array(
                array('id' => 'new_biz', 'label' => '新規事業立ち上げ'),
                array('id' => 'second', 'label' => '第二創業・事業転換'),
                array('id' => 'startup', 'label' => 'スタートアップ・ベンチャー'),
                array('id' => 'succession', 'label' => '事業承継'),
                array('id' => 'other_start', 'label' => 'その他の創業支援'),
            ),
        ),
        '地域・文化' => array(
            'question' => 'どのような地域・文化活動をご検討ですか？',
            'options' => array(
                array('id' => 'event', 'label' => '地域イベント・祭り'),
                array('id' => 'art', 'label' => '芸術・文化振興'),
                array('id' => 'community', 'label' => 'コミュニティ活動・交流'),
                array('id' => 'tourism', 'label' => '観光・まちづくり'),
                array('id' => 'other_local', 'label' => 'その他の地域活動'),
            ),
        ),
    );
    
    // カテゴリに対応する質問を返す
    if (isset($followup_patterns[$category])) {
        return $followup_patterns[$category];
    }
    
    // デフォルトの質問
    return array(
        'question' => '具体的にどのような用途でご利用予定ですか？',
        'options' => array(
            array('id' => 'equipment', 'label' => '設備・機器の購入'),
            array('id' => 'service', 'label' => 'サービス・システム導入'),
            array('id' => 'hiring', 'label' => '人材確保・育成'),
            array('id' => 'marketing', 'label' => '広告・販促活動'),
            array('id' => 'other', 'label' => 'その他'),
        ),
    );
}

/**
 * ハイブリッド検索（ベクトル + キーワード）
 */
function gip_hybrid_search($query, $intent_keywords, $filters, $limit = 10, $exclude_ids = array()) {
    global $wpdb;
    
    // ベクトル検索
    $vector_results = gip_search_vectors_with_filter($query, $filters, $limit * 2);
    
    // キーワード検索（タイトル・コンテンツマッチ）
    $keyword_results = gip_keyword_search($query, $intent_keywords, $filters, $limit * 2);
    
    // スコアを統合
    $combined = array();
    
    // ベクトル検索結果を追加
    foreach ($vector_results as $grant_id => $score) {
        if (in_array($grant_id, $exclude_ids)) continue;
        $combined[$grant_id] = $score;
    }
    
    // キーワード検索結果を追加/ブースト
    foreach ($keyword_results as $grant_id => $score) {
        if (in_array($grant_id, $exclude_ids)) continue;
        if (isset($combined[$grant_id])) {
            // 両方にヒットした場合はブースト
            $combined[$grant_id] = $combined[$grant_id] * 0.6 + $score * 0.5;
        } else {
            $combined[$grant_id] = $score * 0.8;
        }
    }
    
    // スコア順にソート
    arsort($combined);
    
    return array_slice($combined, 0, $limit, true);
}

/**
 * キーワードベース検索
 */
function gip_keyword_search($query, $intent_keywords, $filters, $limit = 20) {
    global $wpdb;
    
    $scores = array();
    
    // 検索クエリから重要語を抽出
    $search_terms = preg_split('/[\s　]+/u', $query);
    $search_terms = array_filter($search_terms, function($t) {
        return mb_strlen($t) >= 2;
    });
    
    if (empty($search_terms) && empty($intent_keywords)) {
        return array();
    }
    
    // grant投稿を取得（フィルター付き）
    $args = array(
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => 100, // メモリ制限
        'fields' => 'ids',
    );
    
    // 地域フィルター
    $tax_query = array();
    if (!empty($filters['prefecture'])) {
        $tax_query[] = array(
            'taxonomy' => 'grant_prefecture',
            'field' => 'name',
            'terms' => $filters['prefecture'],
        );
    }
    if (!empty($filters['municipality']) && $filters['municipality'] !== '全域') {
        $tax_query[] = array(
            'taxonomy' => 'grant_municipality',
            'field' => 'name',
            'terms' => $filters['municipality'],
        );
    }
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }
    
    $grant_ids = get_posts($args);
    
    foreach ($grant_ids as $grant_id) {
        $post = get_post($grant_id);
        if (!$post) continue;
        
        $title = $post->post_title;
        $content = wp_strip_all_tags($post->post_content);
        $combined = $title . ' ' . $content;
        
        $score = 0;
        
        // タイトルマッチは高得点
        foreach ($search_terms as $term) {
            if (mb_strpos($title, $term) !== false) {
                $score += 0.3;
            }
            if (mb_strpos($content, $term) !== false) {
                $score += 0.1;
            }
        }
        
        // 意図キーワードマッチ
        $intent_map = array(
            '地域' => array('地域', '地方', 'まちづくり'),
            '文化' => array('文化', '芸術', 'アート', '芸能'),
            'イベント' => array('イベント', '祭り', '催し', '行事'),
            '活性化' => array('活性化', '振興', '推進', '促進'),
            'IT' => array('IT', 'DX', 'デジタル'),
            '設備' => array('設備', '機械', 'ものづくり'),
            '人材' => array('人材', '雇用', '採用'),
            '環境' => array('省エネ', '環境', 'エネルギー'),
            '創業' => array('創業', '起業', 'スタートアップ'),
            '販路' => array('販路', '販売', 'マーケティング'),
        );
        
        foreach ($intent_keywords as $intent) {
            if (isset($intent_map[$intent])) {
                foreach ($intent_map[$intent] as $kw) {
                    if (mb_strpos($title, $kw) !== false) {
                        $score += 0.25;
                    }
                    if (mb_strpos($content, $kw) !== false) {
                        $score += 0.1;
                    }
                }
            }
        }
        
        if ($score > 0.15) {
            $scores[$grant_id] = min($score, 1.0);
        }
    }
    
    arsort($scores);
    return array_slice($scores, 0, $limit, true);
}

/**
 * AI推測に対するユーザーの回答を処理
 * プロフェッショナルな対応
 */
function gip_process_genie_response($collected, $user_response, $last_guess) {
    $guess_subsidy = $last_guess['guess_subsidy'] ?? '';
    $guess_category = $last_guess['guess_category'] ?? '';
    $narrowing_questions = $last_guess['narrowing_questions'] ?? array();
    
    // 「はい、この補助金を探しています」の場合
    if ($user_response === 'yes' || strpos($user_response, 'はい') !== false || 
        strpos($user_response, 'そう') !== false || strpos($user_response, 'それ') !== false ||
        strpos($user_response, '探して') !== false) {
        return array(
            'matched' => true,
            'message' => "承知いたしました。\n\n" .
                        "【" . $guess_subsidy . "】関連の補助金をお探しですね。\n" .
                        "それでは、最適な補助金を検索いたします。",
            'search_hint' => $guess_subsidy . ' ' . $guess_category,
        );
    }
    
    // 「近いですが、少し違います」の場合
    if ($user_response === 'close' || strpos($user_response, '近い') !== false || 
        strpos($user_response, '少し違') !== false) {
        $message = "承知いたしました。\n\n";
        $message .= "【" . $guess_subsidy . "】に近いとのことですね。\n\n";
        $message .= "より適切な補助金をご提案するため、もう少し詳しくお聞かせください。\n\n";
        
        // 絞り込み質問があれば使用
        if (!empty($narrowing_questions)) {
            $message .= "次のどれに近いでしょうか？";
            $options = array();
            foreach ($narrowing_questions as $i => $q) {
                $options[] = array('id' => 'narrow_' . $i, 'label' => $q);
            }
            $options[] = array('id' => 'tell_more', 'label' => '自分の言葉で説明する');
            
            return array(
                'matched' => false,
                'close' => true,
                'message' => $message,
                'options' => $options,
                'hint' => '該当するものを選択してください。',
                'allow_input' => true,
            );
        }
        
        return array(
            'matched' => false,
            'close' => true,
            'message' => $message . "具体的にどのような点が異なりますか？",
            'allow_input' => true,
            'hint' => '例：「〇〇ではなく△△が目的です」',
        );
    }
    
    // 「違う補助金を探しています」の場合
    return array(
        'matched' => false,
        'close' => false,
        'message' => "承知いたしました。\n\n" .
                    "より適切な補助金をご提案するため、もう少し詳しくお聞かせください。",
        'options' => array(
            array('id' => 'tell_purpose', 'label' => '具体的な用途を教える'),
            array('id' => 'tell_industry', 'label' => '業種・業界を教える'),
            array('id' => 'tell_amount', 'label' => '希望金額を教える'),
            array('id' => 'search_now', 'label' => 'このまま検索する'),
        ),
        'allow_input' => true,
        'hint' => '具体的な情報をいただければ、より適切な補助金をご提案できます。',
    );
}

function gip_build_search_confirmation($collected) {
    $text = "ありがとうございます。以下の条件で補助金を検索します。\n\n";
    if (!empty($collected['user_type_label'])) {
        $text .= "■ お立場: " . $collected['user_type_label'] . "\n";
    }
    
    $location = $collected['prefecture'] ?? '';
    if (!empty($collected['municipality'])) {
        $location .= ' ' . $collected['municipality'];
    }
    if (!empty($location)) {
        $text .= "■ 地域: " . $location . "\n";
    }
    
    if (!empty($collected['purpose'])) {
        $text .= "■ 目的: " . mb_substr($collected['purpose'], 0, 50) . "\n";
    }
    
    // 追加情報があれば表示
    $additional_info = array();
    if (!empty($collected['clarification'])) {
        $additional_info[] = mb_substr($collected['clarification'], 0, 50);
    }
    if (!empty($collected['additional_details'])) {
        $additional_info[] = mb_substr($collected['additional_details'], 0, 50);
    }
    if (!empty($additional_info)) {
        $text .= "■ 追加条件: " . implode(', ', $additional_info) . "\n";
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
    
    // ユーザーが「はい」と答えた補助金を最優先
    $matched_grant_id = $context['matched_grant_id'] ?? null;
    
    // ユーザーの意図キーワードを抽出
    $intent_keywords = gip_extract_intent_keywords($user_context);
    
    // ハイブリッド検索（ベクトル + キーワード）
    $similar = gip_hybrid_search($user_context, $intent_keywords, $filters, $max_results * 2, array());
    
    // フォールバック: 結果が少ない場合は地域フィルターを緩和
    if (count($similar) < 3 && !empty($filters['municipality'])) {
        $relaxed_filters = $filters;
        unset($relaxed_filters['municipality']);
        $similar = gip_hybrid_search($user_context, $intent_keywords, $relaxed_filters, $max_results * 2, array());
    }
    
    // ユーザーが選択した補助金が検索結果にない場合は追加
    if ($matched_grant_id && !isset($similar[$matched_grant_id])) {
        $matched_post = get_post($matched_grant_id);
        if ($matched_post && $matched_post->post_status === 'publish') {
            // 最高スコアで追加
            $similar = array($matched_grant_id => 1.0) + $similar;
        }
    }
    
    if (empty($similar)) {
        return array();
    }
    
    $candidates = array();
    foreach (array_keys($similar) as $grant_id) {
        $detail = gip_get_grant_card_data($grant_id);
        if ($detail) {
            $detail['vector_score'] = $similar[$grant_id];
            // ユーザーが選択した補助金にはボーナススコア
            if ($matched_grant_id && $grant_id == $matched_grant_id) {
                $detail['vector_score'] = 1.0;
                $detail['is_matched'] = true;
            }
            $candidates[] = $detail;
        }
    }
    
    if (empty($candidates)) {
        return array();
    }
    
    $scored = gip_llm_score($user_context, $candidates, $context);
    
    if (is_wp_error($scored) || empty($scored)) {
        // マッチした補助金を最優先にソート
        usort($candidates, function($a, $b) {
            // ユーザー選択の補助金を最優先
            if (!empty($a['is_matched'])) return -1;
            if (!empty($b['is_matched'])) return 1;
            return $b['vector_score'] <=> $a['vector_score'];
        });
        
        $results = array_slice($candidates, 0, $max_results);
        
        foreach ($results as $i => &$r) {
            $r['score'] = round($r['vector_score'] * 100);
            $r['reason'] = !empty($r['is_matched']) ? 'ご選択の補助金です' : '類似度に基づく推薦';
            $r['rank'] = $i + 1;
        }
        
        return $results;
    }
    
    // LLMスコアリング結果もマッチした補助金を最優先
    if ($matched_grant_id) {
        foreach ($scored as &$item) {
            if ($item['grant_id'] == $matched_grant_id) {
                $item['score'] = max($item['score'], 95);
                $item['reason'] = 'ご選択の補助金です。' . $item['reason'];
            }
        }
        usort($scored, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        // ランクを再設定
        foreach ($scored as $i => &$item) {
            $item['rank'] = $i + 1;
        }
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
    
    gip_log('Feedback API called', array(
        'session_id' => $session_id,
        'grant_id' => $grant_id,
        'feedback' => $feedback,
    ));
    
    // セッションIDとフィードバックは必須（grant_idは0でもOK = 全体フィードバック）
    if (empty($session_id) || !in_array($feedback, array('positive', 'negative', 'helpful', 'not_helpful'))) {
        gip_log('Feedback API: Invalid parameters', array('feedback' => $feedback));
        return new WP_REST_Response(array('success' => false, 'error' => 'Invalid parameters'), 400);
    }
    
    $results_updated = false;
    $log_updated = false;
    
    // grant_idが指定されている場合はresultsテーブルを更新
    if ($grant_id > 0) {
        $result = $wpdb->update(
            gip_table('results'),
            array('feedback' => $feedback),
            array('session_id' => $session_id, 'grant_id' => $grant_id)
        );
        $results_updated = ($result !== false);
        gip_log('Feedback API: Results table update', array(
            'affected_rows' => $result,
            'success' => $results_updated,
        ));
    }
    
    // question_logsテーブルも更新（全体フィードバックとして）
    // 満足度スコアを設定: positive/helpful=4, negative/not_helpful=2
    $satisfaction = in_array($feedback, array('positive', 'helpful')) ? 4 : 2;
    $log_updated = gip_update_question_log_feedback($session_id, $feedback, $satisfaction);
    
    gip_log('Feedback API: Question log update', array(
        'session_id' => $session_id,
        'feedback' => $feedback,
        'satisfaction' => $satisfaction,
        'log_updated' => $log_updated,
    ));
    
    return new WP_REST_Response(array(
        'success' => true, 
        'updated' => true,
        'results_updated' => $results_updated,
        'log_updated' => $log_updated,
    ));
}

/**
 * 詳細フィードバックAPI - 改善コメント付き
 */
function gip_api_feedback_detailed($request) {
    global $wpdb;
    
    $params = $request->get_json_params();
    $session_id = sanitize_text_field($params['session_id'] ?? '');
    $grant_id = absint($params['grant_id'] ?? 0);
    $feedback_type = sanitize_text_field($params['feedback_type'] ?? '');
    $rating = absint($params['rating'] ?? 0);
    $comment = sanitize_textarea_field($params['comment'] ?? '');
    $suggestion = sanitize_textarea_field($params['suggestion'] ?? '');
    $user_email = sanitize_email($params['email'] ?? '');
    
    gip_log('Detailed feedback API called', array(
        'session_id' => $session_id,
        'grant_id' => $grant_id,
        'feedback_type' => $feedback_type,
        'rating' => $rating,
        'has_comment' => !empty($comment),
        'has_suggestion' => !empty($suggestion),
    ));
    
    if (empty($session_id) || empty($feedback_type)) {
        gip_log('Detailed feedback API: Missing required parameters');
        return new WP_REST_Response(array('success' => false, 'error' => '必須パラメータが不足しています'), 400);
    }
    
    // フィードバックテーブルが存在しなければ作成
    if (!gip_table_exists('user_feedbacks')) {
        gip_create_tables();
    }
    
    // フィードバックを保存
    $result = $wpdb->insert(
        gip_table('user_feedbacks'),
        array(
            'session_id' => $session_id,
            'grant_id' => $grant_id > 0 ? $grant_id : null,
            'feedback_type' => $feedback_type,
            'rating' => $rating > 0 ? $rating : null,
            'comment' => $comment,
            'suggestion' => $suggestion,
            'user_email' => $user_email,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '',
            'created_at' => current_time('mysql'),
        )
    );
    
    $feedback_id = $wpdb->insert_id;
    
    gip_log('Detailed feedback saved to user_feedbacks', array(
        'insert_result' => $result,
        'feedback_id' => $feedback_id,
    ));
    
    if ($result === false) {
        gip_log('Detailed feedback API: Database error', array('last_error' => $wpdb->last_error));
        return new WP_REST_Response(array('success' => false, 'error' => 'データベースエラー'), 500);
    }
    
    // 質問ログも更新
    $log_updated = gip_update_question_log_feedback($session_id, $feedback_type, $rating > 0 ? $rating : null);
    
    gip_log('Detailed feedback: Question log update result', array(
        'log_updated' => $log_updated,
    ));
    
    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'フィードバックを送信しました。ご協力ありがとうございます。',
        'feedback_id' => $feedback_id,
        'log_updated' => $log_updated,
    ));
}

/**
 * セッション全体フィードバックAPI - 診断結果への評価・コメント
 * フロントエンドの showContinueOptions() から呼び出される
 */
function gip_api_session_feedback($request) {
    global $wpdb;
    
    $params = $request->get_json_params();
    $session_id = sanitize_text_field($params['session_id'] ?? '');
    $rating = sanitize_text_field($params['rating'] ?? ''); // satisfied, neutral, unsatisfied
    $comment = sanitize_textarea_field($params['comment'] ?? '');
    
    gip_log('Session feedback API called', array(
        'session_id' => $session_id,
        'rating' => $rating,
        'has_comment' => !empty($comment),
        'comment_length' => strlen($comment),
    ));
    
    // セッションIDは必須、評価またはコメントのいずれかが必要
    if (empty($session_id)) {
        gip_log('Session feedback API: Missing session_id');
        return new WP_REST_Response(array('success' => false, 'error' => 'セッションIDが必要です'), 400);
    }
    
    if (empty($rating) && empty($comment)) {
        gip_log('Session feedback API: No rating or comment provided');
        return new WP_REST_Response(array('success' => false, 'error' => '評価またはコメントが必要です'), 400);
    }
    
    // フィードバックテーブルが存在しなければ作成
    if (!gip_table_exists('user_feedbacks')) {
        gip_create_tables();
    }
    
    // 評価を満足度スコアに変換 (1-5)
    $satisfaction_map = array(
        'satisfied' => 5,
        'neutral' => 3,
        'unsatisfied' => 1,
    );
    $satisfaction_score = isset($satisfaction_map[$rating]) ? $satisfaction_map[$rating] : null;
    
    // 評価をフィードバックタイプに変換
    $feedback_type_map = array(
        'satisfied' => 'positive',
        'neutral' => 'neutral',
        'unsatisfied' => 'negative',
    );
    $feedback_type = isset($feedback_type_map[$rating]) ? $feedback_type_map[$rating] : 'comment_only';
    
    // user_feedbacksテーブルにフィードバックを保存
    $result = $wpdb->insert(
        gip_table('user_feedbacks'),
        array(
            'session_id' => $session_id,
            'grant_id' => null, // セッション全体へのフィードバック
            'feedback_type' => $feedback_type,
            'rating' => $satisfaction_score,
            'comment' => $comment,
            'suggestion' => '', // 改善案は別途
            'user_email' => '',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '',
            'created_at' => current_time('mysql'),
        )
    );
    
    $feedback_id = $wpdb->insert_id;
    
    gip_log('Session feedback saved to user_feedbacks', array(
        'insert_result' => $result,
        'feedback_id' => $feedback_id,
        'feedback_type' => $feedback_type,
        'satisfaction_score' => $satisfaction_score,
    ));
    
    if ($result === false) {
        gip_log('Session feedback API: Database error', array('last_error' => $wpdb->last_error));
        return new WP_REST_Response(array('success' => false, 'error' => 'データベースエラー'), 500);
    }
    
    // question_logsテーブルも更新（フィードバック情報を紐付け）
    $log_updated = gip_update_question_log_feedback($session_id, $feedback_type, $satisfaction_score);
    
    gip_log('Session feedback: Question log update result', array(
        'session_id' => $session_id,
        'feedback_type' => $feedback_type,
        'satisfaction_score' => $satisfaction_score,
        'log_updated' => $log_updated,
    ));
    
    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'フィードバックをお送りいただきありがとうございます！',
        'feedback_id' => $feedback_id,
        'log_updated' => $log_updated,
    ));
}

/**
 * ステップバック（戻る）API
 */
function gip_api_step_back($request) {
    global $wpdb;
    
    $params = $request->get_json_params();
    $session_id = sanitize_text_field($params['session_id'] ?? '');
    $steps_back = absint($params['steps_back'] ?? 1);
    
    if (empty($session_id)) {
        return new WP_REST_Response(array('success' => false, 'error' => 'セッションIDが必要です'), 400);
    }
    
    // 会話履歴テーブルが存在しなければ作成
    if (!gip_table_exists('conversation_states')) {
        gip_create_tables();
    }
    
    // 現在のセッション情報を取得
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . gip_table('sessions') . " WHERE session_id = %s",
        $session_id
    ));
    
    if (!$session) {
        return new WP_REST_Response(array('success' => false, 'error' => 'セッションが見つかりません'), 404);
    }
    
    // 過去の状態を取得
    $states_table = gip_table('conversation_states');
    $previous_state = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$states_table} 
         WHERE session_id = %s 
         ORDER BY step_number DESC 
         LIMIT 1 OFFSET %d",
        $session_id,
        $steps_back
    ));
    
    if (!$previous_state) {
        // 最初のステップに戻る
        $context = array('step' => 'init', 'understanding_level' => 0, 'collected_info' => array());
        $wpdb->update(
            gip_table('sessions'),
            array('context' => wp_json_encode($context)),
            array('session_id' => $session_id)
        );
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => '最初のステップに戻りました',
            'step' => 'init',
            'context' => $context,
            'restart' => true,
        ));
    }
    
    // 過去の状態を復元
    $restored_context = json_decode($previous_state->context_snapshot, true);
    
    $wpdb->update(
        gip_table('sessions'),
        array('context' => $previous_state->context_snapshot),
        array('session_id' => $session_id)
    );
    
    // それ以降の状態を削除
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$states_table} WHERE session_id = %s AND step_number > %d",
        $session_id,
        $previous_state->step_number - $steps_back
    ));
    
    return new WP_REST_Response(array(
        'success' => true,
        'message' => '前のステップに戻りました',
        'step' => $restored_context['step'] ?? 'init',
        'context' => $restored_context,
        'restart' => false,
    ));
}

/**
 * 再調整API - 条件を変更して再検索
 */
function gip_api_readjust($request) {
    global $wpdb;
    
    $params = $request->get_json_params();
    $session_id = sanitize_text_field($params['session_id'] ?? '');
    $adjust_type = sanitize_text_field($params['adjust_type'] ?? '');
    $new_value = sanitize_text_field($params['new_value'] ?? '');
    
    if (empty($session_id) || empty($adjust_type)) {
        return new WP_REST_Response(array('success' => false, 'error' => '必須パラメータが不足しています'), 400);
    }
    
    // セッション情報を取得
    $session = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . gip_table('sessions') . " WHERE session_id = %s",
        $session_id
    ));
    
    if (!$session) {
        return new WP_REST_Response(array('success' => false, 'error' => 'セッションが見つかりません'), 404);
    }
    
    $context = json_decode($session->context, true) ?: array();
    $collected = $context['collected_info'] ?? array();
    
    // 調整タイプに応じて情報を更新
    switch ($adjust_type) {
        case 'prefecture':
            $prefecture = gip_normalize_prefecture($new_value);
            if ($prefecture) {
                $collected['prefecture'] = $prefecture;
                $collected['municipality'] = ''; // 都道府県変更時は市区町村をクリア
            }
            break;
            
        case 'municipality':
            $collected['municipality'] = $new_value;
            break;
            
        case 'purpose':
            $collected['purpose'] = $new_value;
            $collected['clarification'] = ''; // 目的変更時は詳細をクリア
            break;
            
        case 'user_type':
            $user_type = gip_normalize_user_type($new_value);
            $collected['user_type'] = $user_type;
            $collected['user_type_label'] = gip_get_user_type_label($user_type);
            break;
            
        case 'clarification':
            $collected['clarification'] = ($collected['clarification'] ?? '') . ' ' . $new_value;
            break;
            
        case 'expand_area':
            // 地域を広げる
            $collected['municipality'] = '';
            break;
            
        case 'national':
            // 全国検索
            $collected['prefecture'] = '';
            $collected['municipality'] = '';
            break;
    }
    
    // コンテキストを更新
    $context['collected_info'] = $collected;
    $context['step'] = 'searching';
    
    $wpdb->update(
        gip_table('sessions'),
        array('context' => wp_json_encode($context)),
        array('session_id' => $session_id)
    );
    
    // 再検索実行
    $filters = array(
        'user_type' => $collected['user_type'] ?? '',
        'prefecture' => $collected['prefecture'] ?? '',
        'municipality' => $collected['municipality'] ?? '',
        'status_open' => true,
    );
    
    $results = gip_execute_match($session_id, gip_build_natural_search_query($collected), $filters, $collected);
    
    $message = '条件を変更して再検索しました。';
    if (!empty($results)) {
        $message .= "\n\n【" . count($results) . "件】の補助金が見つかりました。";
    } else {
        $message .= "\n\n条件に合う補助金が見つかりませんでした。";
    }
    
    // 応答にコンテキスト情報を追加（UIで検索条件を表示するため）
    return new WP_REST_Response(array(
        'success' => true,
        'session_id' => $session_id,
        'message' => $message,
        'results' => $results,
        'results_count' => count($results),
        'context' => $context,
        'collected_info' => $collected,
        'can_continue' => !empty($results),
        'show_comparison' => !empty($results),
        'show_research_option' => true,
    ));
}

/**
 * 会話状態を保存（戻る機能用）
 */
function gip_save_conversation_state($session_id, $step_name, $context, $user_input = '') {
    global $wpdb;
    
    if (!gip_table_exists('conversation_states')) {
        return false;
    }
    
    $states_table = gip_table('conversation_states');
    
    // 現在の最大ステップ番号を取得
    $max_step = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT MAX(step_number) FROM {$states_table} WHERE session_id = %s",
        $session_id
    ));
    
    $new_step = $max_step + 1;
    
    $wpdb->insert(
        $states_table,
        array(
            'session_id' => $session_id,
            'step_number' => $new_step,
            'step_name' => $step_name,
            'context_snapshot' => wp_json_encode($context),
            'user_input' => $user_input,
            'created_at' => current_time('mysql'),
        )
    );
    
    return $new_step;
}

// =============================================================================
// Frontend Assets
// =============================================================================

function gip_frontend_assets() {
    global $post;
    
    $should_load = false;
    
    if (is_singular() && is_a($post, 'WP_Post')) {
        $template = get_page_template_slug($post->ID);
        
        if (has_shortcode($post->post_content, 'gip_chat')) {
            $should_load = true;
        }
        // 複数のテンプレートパターンをサポート
        $template_patterns = array('ai-diagnosis', 'gip', 'diagnosis', 'subsidy-diagnosis');
        foreach ($template_patterns as $pattern) {
            if (strpos($template, $pattern) !== false) {
                $should_load = true;
                break;
            }
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
// Frontend CSS - Gemini-style Clean Talk UI v9.0
// =============================================================================

function gip_frontend_css() {
    return '
:root {
    /* Gemini風カラー - クリーンなモノクロベース */
    --gip-black: #1f1f1f;
    --gip-white: #ffffff;
    --gip-gray-50: #f8f9fa;
    --gip-gray-100: #f1f3f4;
    --gip-gray-200: #e8eaed;
    --gip-gray-300: #dadce0;
    --gip-gray-400: #9aa0a6;
    --gip-gray-500: #80868b;
    --gip-gray-600: #5f6368;
    --gip-gray-700: #3c4043;
    --gip-gray-800: #202124;
    --gip-gray-900: #171717;
    --gip-accent: #1f1f1f;
    --gip-accent-light: #f8f9fa;
    --gip-shadow: 0 1px 3px rgba(0,0,0,0.08);
    --gip-shadow-lg: 0 4px 12px rgba(0,0,0,0.1);
    --gip-transition: 0.2s ease;
    --gip-radius: 12px;
    --gip-radius-lg: 24px;
    --gip-font-sans: "Noto Sans JP", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    --gip-font-serif: "Shippori Mincho", "Yu Mincho", serif;
}

/* iOSキーボードズーム防止 - グローバルルール（16px必須） */
.gip-chat input[type="text"],
.gip-chat input[type="email"],
.gip-chat input[type="tel"],
.gip-chat input[type="number"],
.gip-chat textarea,
.gip-chat select {
    font-size: 16px !important;
    -webkit-text-size-adjust: 100%;
}

/* Gemini風 - チャットコンテナ */
.gip-chat {
    max-width: 100%;
    width: 100%;
    margin: 0 auto;
    font-family: var(--gip-font-sans);
    background: transparent;
    border: none;
    border-radius: 0;
    box-shadow: none;
    overflow-x: hidden;
    overflow-y: visible;
    line-height: 1.7;
}

.gip-chat * {
    box-sizing: border-box;
}

/* ヘッダー非表示（LP側で表示） */
.gip-chat-header {
    display: none;
}

/* メッセージエリア - クリーン */
.gip-chat-messages {
    min-height: auto;
    max-height: none;
    overflow-y: visible;
    padding: 0;
    background: transparent;
    display: flex;
    flex-direction: column;
}

.gip-chat-messages::-webkit-scrollbar {
    display: none;
}

.gip-chat-messages {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Gemini風 - メッセージ */
.gip-message {
    margin-bottom: 0;
    animation: gipFadeIn 0.3s ease;
}

@keyframes gipFadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* AIメッセージ - 薄いグレー背景で区別 */
.gip-message-bot {
    background-color: var(--gip-gray-50);
    padding: 24px 16px;
    margin: 0;
}

/* アバター非表示 - Gemini風クリーン表示 */
.gip-message-avatar {
    display: none;
}

.gip-message-content {
    flex: 1;
    max-width: 100%;
}

.gip-message-bot .gip-message-content {
    max-width: 100%;
}

/* ユーザーメッセージ */
.gip-message-user {
    padding: 16px 0;
    display: flex;
    justify-content: flex-end;
}

.gip-message-user .gip-message-content {
    text-align: right;
    max-width: 80%;
}

/* Gemini風 - メッセージテキスト */
.gip-message-bubble {
    display: inline-block;
    padding: 0;
    font-size: 15px;
    line-height: 1.8;
    white-space: pre-wrap;
    word-break: break-word;
}

/* AIメッセージは枠なし */
.gip-message-bot .gip-message-bubble {
    background: transparent;
    border: none;
    border-radius: 0;
    text-align: left;
    box-shadow: none;
    padding: 0;
    color: var(--gip-black);
}

/* ユーザーメッセージはピル型バブル */
.gip-message-user .gip-message-bubble {
    background: var(--gip-gray-200);
    color: var(--gip-black);
    border-radius: var(--gip-radius-lg);
    padding: 12px 18px;
}

/* Gemini風 - ヒント */
.gip-hint {
    margin-top: 16px;
    padding: 0;
    background: transparent;
    border: none;
    font-size: 13px;
    color: var(--gip-gray-500);
}

/* 重要ヒント - 赤文字で自然言語入力を促す */
.gip-hint-important {
    margin-top: 20px;
    padding: 14px 18px;
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border: 1px solid #fecaca;
    border-left: 4px solid #dc2626;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #dc2626;
    line-height: 1.6;
    animation: gipPulseHint 2s ease-in-out infinite;
}

.gip-hint-important::before {
    content: "";
}

@keyframes gipPulseHint {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.9; transform: scale(1.01); }
}

/* Gemini風 - オプションボタン - ピル型 */
.gip-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 24px;
}

.gip-option-btn {
    width: 100%;
    padding: 14px 20px;
    min-height: 52px;
    font-size: 15px;
    font-weight: 500;
    border: 1px solid var(--gip-gray-200);
    background: var(--gip-white);
    color: var(--gip-black);
    border-radius: var(--gip-radius);
    cursor: pointer;
    transition: all var(--gip-transition);
    font-family: inherit;
    text-align: left;
}

.gip-option-btn:hover {
    background: var(--gip-gray-100);
    border-color: var(--gip-gray-300);
}

.gip-option-btn:active {
    transform: scale(0.98);
}

.gip-option-btn.selected {
    background: var(--gip-black);
    border-color: var(--gip-black);
    color: var(--gip-white);
}

/* 前に戻るボタン */
.gip-back-btn-wrap {
    margin-top: 12px;
    text-align: left;
}

.gip-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: transparent;
    border: 1px solid var(--gip-gray-300);
    border-radius: var(--gip-radius);
    font-size: 13px;
    color: var(--gip-gray-600);
    cursor: pointer;
    transition: all var(--gip-transition);
}

.gip-back-btn:hover {
    background: var(--gip-gray-100);
    border-color: var(--gip-gray-400);
    color: var(--gip-gray-700);
}

.gip-back-btn svg {
    flex-shrink: 0;
}

/* Gemini風 - セレクトボックス */
.gip-select-wrap {
    margin-top: 16px;
    position: relative;
}

.gip-select {
    width: 100%;
    max-width: 100%;
    padding: 14px 44px 14px 16px;
    font-size: 16px;
    font-family: inherit;
    border: 1px solid var(--gip-gray-200);
    border-radius: var(--gip-radius);
    background: var(--gip-white);
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 12 12\'%3E%3Cpath fill=\'%235f6368\' d=\'M6 8L1 3h10z\'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 16px center;
    transition: all var(--gip-transition);
}

.gip-select:focus {
    outline: none;
    border-color: var(--gip-black);
}

.gip-select:hover {
    background-color: var(--gip-gray-50);
}

/* Gemini風 - インライン入力 */
.gip-input-inline {
    display: flex;
    gap: 10px;
    margin-top: 16px;
    max-width: 100%;
}

.gip-input-inline input,
.gip-inline-input {
    flex: 1;
    padding: 14px 16px;
    border: 1px solid var(--gip-gray-200);
    border-radius: var(--gip-radius);
    font-size: 16px;
    font-family: inherit;
    transition: all var(--gip-transition);
}

.gip-input-inline input:focus,
.gip-inline-input:focus {
    outline: none;
    border-color: var(--gip-black);
}

.gip-input-inline button,
.gip-inline-submit {
    padding: 14px 24px;
    min-height: 48px;
    background: var(--gip-black);
    color: var(--gip-white);
    border: none;
    border-radius: var(--gip-radius);
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all var(--gip-transition);
}

.gip-input-inline button:hover,
.gip-inline-submit:hover {
    background: var(--gip-gray-800);
}

/* Gemini風 - タイピングインジケーター */
.gip-message-typing {
    display: flex;
    gap: 6px;
    padding: 8px 0;
    background: transparent;
    border: none;
}

.gip-typing-dot {
    width: 8px;
    height: 8px;
    background: var(--gip-gray-400);
    border-radius: 50%;
    animation: gipTyping 1.2s infinite;
}

.gip-typing-dot:nth-child(2) { animation-delay: 0.15s; }
.gip-typing-dot:nth-child(3) { animation-delay: 0.3s; }

@keyframes gipTyping {
    0%, 60%, 100% { transform: scale(1); opacity: 0.4; }
    30% { transform: scale(1.2); opacity: 1; }
}

/* 入力エリア */
.gip-chat-input-area {
    display: none;
    padding: 16px;
    border-top: none;
    background: transparent;
    position: relative;
    z-index: 100;
}

.gip-chat-input-wrap {
    display: flex;
    gap: 8px;
    align-items: center;
    background: var(--gip-gray-100);
    border-radius: var(--gip-radius-lg);
    padding: 6px 8px 6px 16px;
    max-width: 100%;
    box-sizing: border-box;
}

.gip-chat-input {
    flex: 1;
    padding: 10px 0;
    border: none;
    border-radius: 0;
    font-size: 16px;
    font-family: inherit;
    background: transparent;
    resize: none;
    max-height: 120px;
    line-height: 1.5;
}

.gip-chat-input:focus {
    outline: none;
}

.gip-chat-input::placeholder {
    color: var(--gip-gray-500);
}

.gip-chat-send {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 50%;
    background: var(--gip-black);
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
    opacity: 0.8;
}

.gip-chat-send:disabled {
    opacity: 0.3;
    cursor: not-allowed;
    background-color: var(--gip-gray-400);
}

/* ローディングテキスト */
.gip-loading-text {
    font-size: 13px;
    color: var(--gip-gray-600);
    margin-top: 8px;
    font-weight: 500;
    animation: gipFadeIn 0.5s ease;
}

.gip-chat-send svg {
    width: 18px;
    height: 18px;
}

/* LP統合 - 結果セクション（Gemini/ChatGPTスタイル） */
.gip-results {
    padding: 24px 16px;
    border-top: none;
    background: var(--gip-white);
    margin-top: 16px;
    border-radius: 0;
    box-shadow: none;
}

.gip-results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding: 12px;
    margin: -12px -12px 24px -12px;
    border-bottom: 1px solid var(--gip-gray-200);
    border-radius: 8px;
    flex-wrap: wrap;
    gap: 16px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.gip-results-header:hover {
    background-color: var(--gip-gray-100);
}

.gip-results-toggle-icon {
    transition: transform 0.3s ease;
    flex-shrink: 0;
}

.gip-results.minimized .gip-results-main,
.gip-results.minimized .gip-results-sub,
.gip-results.minimized .gip-load-more,
.gip-results.minimized .gip-results-feedback-panel,
.gip-results.minimized .gip-readjust-panel,
.gip-results.minimized .gip-continue-chat,
.gip-results.minimized .gip-results-summary {
    display: none !important;
}

.gip-results.minimized .gip-results-toggle-icon {
    transform: rotate(-180deg);
}

.gip-results-title {
    font-family: var(--gip-font-serif);
    font-size: 20px;
    font-weight: 600;
    margin: 0;
    color: var(--gip-black);
}

.gip-results-count {
    font-size: 13px;
    color: var(--gip-gray-600);
    margin-top: 4px;
}

/* LP統合 - セクションタイトル */
.gip-results-section-title {
    font-family: var(--gip-font-serif);
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 16px 0;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--gip-gray-200);
    color: var(--gip-black);
}

.gip-btn-compare {
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 600;
    font-family: inherit;
    background: var(--gip-white);
    border: 1px solid var(--gip-black);
    color: var(--gip-black);
    border-radius: 0;
    cursor: pointer;
    transition: all var(--gip-transition);
}

.gip-btn-compare:hover:not(:disabled) {
    background: var(--gip-black);
    color: var(--gip-white);
}

.gip-btn-compare:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.gip-results-grid {
    display: grid;
    gap: 16px;
}

.gip-results-main {
    margin-bottom: 32px;
}

.gip-results-sub {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 2px dashed var(--gip-gray-300);
}

/* LP統合 - サブ結果グリッド */
.gip-results-grid-sub {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

/* LP統合 - サブ結果カード（コンパクト表示） */
.gip-result-card-sub {
    background: var(--gip-white);
    border: 1px solid var(--gip-gray-200);
    padding: 0;
    transition: all var(--gip-transition);
}

.gip-result-card-sub:hover {
    border-color: var(--gip-black);
}

.gip-result-sub-content {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
}

.gip-result-sub-rank {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gip-gray-600);
    color: var(--gip-white);
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.gip-result-sub-info {
    flex: 1;
    min-width: 0;
}

.gip-result-sub-title {
    font-size: 13px;
    font-weight: 600;
    margin: 0 0 4px 0;
    line-height: 1.4;
    color: var(--gip-black);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.gip-result-sub-meta {
    display: flex;
    gap: 12px;
    font-size: 11px;
    color: var(--gip-gray-600);
}

.gip-result-sub-amount {
    color: var(--gip-black);
    font-weight: 600;
}

.gip-result-sub-score {
    color: var(--gip-gray-500);
}

.gip-sub-ask-btn {
    padding: 8px 12px;
    font-size: 11px;
    font-weight: 600;
    background: var(--gip-white);
    border: 1px solid var(--gip-gray-300);
    color: var(--gip-gray-700);
    cursor: pointer;
    transition: all var(--gip-transition);
    flex-shrink: 0;
}

.gip-sub-ask-btn:hover {
    background: var(--gip-black);
    border-color: var(--gip-black);
    color: var(--gip-white);
}

/* LP統合 - 再検索オプション */
.gip-research-options {
    margin-top: 24px;
    padding: 20px;
    border: 1px solid var(--gip-gray-200);
    background: var(--gip-gray-50);
}

.gip-research-title {
    font-family: var(--gip-font-serif);
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 12px 0;
    color: var(--gip-gray-700);
}

.gip-research-btns {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.gip-research-btn {
    padding: 10px 16px;
    font-size: 13px;
}

/* LP統合 - 結果カード（Gemini/ChatGPTスタイル - クリーンな表示） */
.gip-result-card {
    border: none;
    border-bottom: 1px solid var(--gip-gray-200);
    border-radius: 0;
    overflow: hidden;
    transition: all var(--gip-transition);
    background: var(--gip-white);
    margin-bottom: 0;
    padding: 16px 0;
}

.gip-result-card:hover {
    background: var(--gip-gray-50);
}

.gip-result-card.highlight {
    background: var(--gip-gray-50);
}

.gip-result-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 0;
    background: transparent;
    border-bottom: none;
}

/* LP統合 - ランク表示（モノクロ） */
.gip-result-rank {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    flex-shrink: 0;
    border-radius: 0;
    background: var(--gip-gray-200);
    color: var(--gip-gray-700);
}

.gip-result-rank-1 { background: var(--gip-black); color: var(--gip-white); }
.gip-result-rank-2 { background: var(--gip-gray-700); color: var(--gip-white); }
.gip-result-rank-3 { background: var(--gip-gray-500); color: var(--gip-white); }

.gip-result-info {
    flex: 1;
    min-width: 0;
}

.gip-result-title {
    font-family: var(--gip-font-serif);
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 8px 0;
    line-height: 1.4;
    color: var(--gip-black);
}

.gip-result-org {
    font-size: 12px;
    color: var(--gip-gray-600);
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.gip-result-prefecture {
    background: var(--gip-gray-100);
    color: var(--gip-gray-700);
    padding: 3px 10px;
    border-radius: 0;
    font-size: 11px;
    font-weight: 600;
}

/* LP統合 - スコア表示 */
.gip-result-score {
    text-align: center;
    padding: 10px 14px;
    background: var(--gip-white);
    border-radius: 0;
    border: 1px solid var(--gip-gray-200);
    min-width: 80px;
}

.gip-result-score-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--gip-black);
    font-family: "SF Mono", Monaco, Consolas, monospace;
    line-height: 1;
}

.gip-result-score-label {
    font-size: 10px;
    color: var(--gip-gray-500);
    margin-top: 4px;
    font-weight: 500;
}

.gip-result-body {
    padding: 20px;
}

/* LP統合 - 推薦理由（AIおすすめコメント） - 赤文字で目立つ表示 */
.gip-result-reason {
    font-size: 14px;
    color: #dc2626;
    padding: 14px 18px;
    background: linear-gradient(135deg, #fef2f2 0%, #fff5f5 100%);
    border-radius: 8px;
    margin-bottom: 16px;
    border-left: 4px solid #dc2626;
    font-weight: 600;
    line-height: 1.6;
    position: relative;
}

.gip-result-reason::before {
    content: "\\1F3AF  AI\\304A\\3059\\3059\\3081";
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #b91c1c;
    margin-bottom: 6px;
    letter-spacing: 0.5px;
}

/* AI要約のスタイル */
.gip-result-ai-summary {
    background-color: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 6px;
    padding: 10px 12px;
    margin-bottom: 12px;
    font-size: 13px;
    color: #0c4a6e;
    line-height: 1.6;
}

.gip-result-ai-summary-label {
    font-weight: 700;
    font-size: 11px;
    color: #0284c7;
    display: block;
    margin-bottom: 4px;
}

/* LP統合 - メタ情報 */
.gip-result-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
    font-size: 13px;
}

.gip-result-meta-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 10px;
    background: var(--gip-gray-50);
    border: 1px solid var(--gip-gray-200);
}

.gip-result-meta-label {
    font-size: 11px;
    color: var(--gip-gray-500);
    font-weight: 500;
}

.gip-result-meta-value {
    font-weight: 700;
    color: var(--gip-black);
}

.gip-result-meta-value.highlight {
    color: var(--gip-black);
}

.gip-result-excerpt {
    font-size: 14px;
    color: var(--gip-gray-700);
    line-height: 1.7;
    margin: 12px 0 16px;
    padding: 12px;
    background: var(--gip-gray-50);
    border-left: 3px solid var(--gip-gray-300);
    border-radius: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* LP統合 - 詳細セクション */
.gip-result-details {
    display: none;
    padding: 16px;
    background: var(--gip-gray-50);
    border-radius: 0;
    margin-bottom: 16px;
    font-size: 13px;
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
    font-size: 12px;
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
    min-height: 44px; /* タップ領域確保 - Apple HIG準拠 */
    padding: 12px 20px;
    text-align: center;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    border-radius: 0; /* LP統合 - 角ばったデザイン */
    transition: all var(--gip-transition);
    cursor: pointer;
    border: 1px solid var(--gip-black);
    font-family: inherit;
}

.gip-result-btn-primary {
    background: var(--gip-black);
    color: var(--gip-white);
}

.gip-result-btn-primary:hover {
    background: var(--gip-white);
    color: var(--gip-black);
    transform: none;
}

.gip-result-btn-secondary {
    background: var(--gip-white);
    color: var(--gip-black);
    border: 1px solid var(--gip-gray-300);
}

.gip-result-btn-secondary:hover {
    border-color: var(--gip-black);
    background: var(--gip-gray-50);
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
    padding: 10px 14px;
    min-height: 44px; /* タップ領域確保 - Apple HIG準拠 */
    font-size: 13px;
    font-family: inherit;
    border: 1px solid var(--gip-gray-300);
    background: var(--gip-white);
    border-radius: 0; /* LP統合 - 角ばったデザイン */
    cursor: pointer;
    transition: all var(--gip-transition);
}

.gip-feedback-btn:hover {
    background: var(--gip-gray-100);
    border-color: var(--gip-black);
}

.gip-feedback-btn.selected {
    background: var(--gip-black);
    color: var(--gip-white);
    border-color: var(--gip-black);
}

/* Comparison Modal - LP統合 */
.gip-comparison-modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 20px;
    animation: gipModalFadeIn 0.3s ease;
}

@keyframes gipModalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.gip-comparison-content {
    background: var(--gip-white);
    border-radius: 0; /* LP統合 - 角ばったデザイン */
    max-width: 1100px;
    max-height: 90vh;
    overflow: auto;
    width: 100%;
    box-shadow: var(--gip-shadow-lg);
    animation: gipModalSlideIn 0.3s ease;
}

@keyframes gipModalSlideIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.gip-comparison-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 28px;
    border-bottom: 2px solid var(--gip-black);
    position: sticky;
    top: 0;
    background: var(--gip-white);
    z-index: 10;
}

.gip-comparison-title {
    font-family: var(--gip-font-serif);
    font-size: 20px;
    font-weight: 600;
    margin: 0;
}

.gip-comparison-close {
    width: 40px;
    height: 40px;
    border: 1px solid var(--gip-gray-300);
    background: var(--gip-white);
    border-radius: 0; /* LP統合 - 角ばったデザイン */
    cursor: pointer;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--gip-transition);
    color: var(--gip-gray-600);
}

.gip-comparison-close:hover {
    background: var(--gip-gray-100);
    border-color: var(--gip-black);
    color: var(--gip-black);
}

.gip-comparison-body {
    position: relative;
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

/* 比較表の左列固定（スマホ対応） */
.gip-comparison-table th:first-child, 
.gip-comparison-table td:first-child {
    position: sticky;
    left: 0;
    z-index: 2;
    background-color: var(--gip-white);
    border-right: 2px solid var(--gip-gray-200);
    box-shadow: 2px 0 5px rgba(0,0,0,0.05);
}

.gip-comparison-table tr:first-child th:first-child {
    background-color: var(--gip-gray-50);
    z-index: 3;
}

.gip-comparison-table td {
    min-width: 180px;
}

.gip-comparison-table .table-header {
    background: var(--gip-black); /* LP統合 */
    color: var(--gip-white);
    font-weight: 700;
    font-size: 13px;
}

.gip-comparison-table .table-score {
    font-size: 24px;
    font-weight: 900;
    color: var(--gip-black); /* LP統合 */
    font-family: var(--gip-font-mono, "SF Mono", Monaco, Consolas, monospace);
}

/* Load More - LP統合 */
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
    border: 1px solid var(--gip-gray-300);
    border-radius: 0; /* LP統合 - 角ばったデザイン */
    cursor: pointer;
    transition: all var(--gip-transition);
}

.gip-btn-load-more:hover {
    border-color: var(--gip-black);
    color: var(--gip-black);
    transform: none;
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

/* Badges - LP統合 */
.gip-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 700;
    border-radius: 0; /* LP統合 - 角ばったデザイン */
}

.gip-badge-success { background: var(--gip-gray-100); color: var(--gip-black); border: 1px solid var(--gip-gray-300); }
.gip-badge-warning { background: var(--gip-gray-200); color: var(--gip-gray-700); }
.gip-badge-error { background: var(--gip-gray-800); color: var(--gip-white); }
.gip-badge-info { background: var(--gip-gray-50); color: var(--gip-black); border: 1px solid var(--gip-gray-200); }

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

/* ============================================================
   Responsive - Mobile First Full-Screen Optimization v8.1
   ============================================================ */
@media (max-width: 768px) {
    .gip-chat {
        border-radius: 0;
        border: none;
        background: transparent;
        display: flex;
        flex-direction: column;
        max-height: none;
        min-height: 100%;
    }
    
    .gip-chat-header {
        display: none; /* LP側でヘッダー表示 */
    }
    
    /* メッセージエリア - フルスクリーン対応 */
    .gip-chat-messages {
        padding: 0;
        min-height: auto;
        max-height: none;
        flex: 1;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
    }
    
    .gip-message {
        margin-bottom: 20px;
        gap: 12px;
    }
    
    .gip-message-content {
        max-width: 100%;
    }
    
    /* AIメッセージは枠なし - すっきり表示 */
    .gip-message-bot .gip-message-bubble {
        padding: 0;
        font-size: 15px;
        line-height: 1.7;
    }
    
    /* ユーザーメッセージはバブル表示 */
    .gip-message-user .gip-message-bubble {
        padding: 12px 16px;
        font-size: 14px;
    }
    
    .gip-message-user .gip-message-content {
        max-width: 85%;
    }
    
    /* ヒント - コンパクト表示 */
    .gip-hint {
        margin-top: 12px;
        font-size: 12px;
    }
    
    /* 重要ヒント（赤文字）- モバイル最適化 */
    .gip-hint-important {
        margin-top: 16px;
        padding: 12px 14px;
        font-size: 13px;
        border-radius: 6px;
    }
    
    /* オプションボタン - タッチ最適化 */
    .gip-options {
        flex-direction: column;
        gap: 10px;
        margin-top: 16px;
    }
    
    .gip-option-btn {
        width: 100%;
        text-align: center;
        padding: 14px 16px;
        min-height: 52px;
        font-size: 15px;
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
    }
    
    /* iOS自動ズーム防止 - 16px必須 */
    .gip-select,
    .gip-inline-input,
    .gip-chat-input,
    input[type="text"],
    input[type="email"],
    input[type="tel"],
    textarea {
        font-size: 16px !important;
        -webkit-text-size-adjust: 100%;
        -webkit-appearance: none;
        appearance: none;
        border-radius: 0;
    }
    
    .gip-select {
        width: 100%;
        max-width: none;
        padding: 14px 40px 14px 16px;
        min-height: 52px;
    }
    
    .gip-input-inline {
        flex-direction: column;
        gap: 10px;
        max-width: none;
    }
    
    .gip-inline-input {
        width: 100%;
        padding: 14px 16px;
    }
    
    .gip-inline-submit {
        width: 100%;
        min-height: 48px;
    }
    
    /* 入力エリア - 固定表示・送信ボタン見切れ防止 */
    .gip-chat-input-area {
        padding: 10px 12px;
        padding-bottom: calc(10px + env(safe-area-inset-bottom, 0px));
    }
    
    .gip-chat-input-wrap {
        padding: 4px 6px 4px 12px;
        gap: 6px;
    }
    
    .gip-chat-input {
        padding: 10px 0;
        font-size: 16px !important; /* iOS自動ズーム防止 */
        -webkit-text-size-adjust: 100%;
        -webkit-appearance: none;
        appearance: none;
    }
    
    .gip-chat-send {
        width: 42px;
        height: 42px;
        min-width: 42px;
        flex-shrink: 0;
    }
    
    /* 結果表示 - モバイル フルスクリーン最適化 */
    .gip-results {
        padding: 12px;
        margin: 0;
        border: none;
        max-width: 100%;
        overflow-x: hidden;
    }
    
    .gip-results-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 16px;
        padding-bottom: 12px;
    }
    
    .gip-results-title {
        font-size: 16px;
    }
    
    .gip-results-count {
        font-size: 12px;
    }
    
    .gip-btn-compare {
        width: 100%;
    }
    
    /* 結果カード - モバイル フルスクリーン（Gemini/ChatGPTスタイル） */
    .gip-result-card {
        margin: 0;
        padding: 16px 0;
        border: none;
        border-bottom: 1px solid var(--gip-gray-200);
        border-radius: 0;
    }
    
    .gip-result-card:last-child {
        border-bottom: none;
    }
    
    .gip-result-header {
        flex-direction: column;
        gap: 8px;
        padding: 0;
    }
    
    .gip-result-rank {
        width: 32px;
        height: 32px;
        font-size: 12px;
    }
    
    .gip-result-info {
        width: 100%;
    }
    
    .gip-result-title {
        font-size: 15px;
        line-height: 1.4;
    }
    
    .gip-result-org {
        font-size: 12px;
    }
    
    .gip-result-score {
        width: 100%;
        margin-top: 8px;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        padding: 8px 12px;
    }
    
    .gip-result-score-value {
        font-size: 18px;
    }
    
    .gip-result-score-label {
        margin-top: 0;
        font-size: 11px;
    }
    
    .gip-result-body {
        padding: 12px 0;
    }
    
    .gip-result-reason {
        font-size: 13px;
        padding: 12px 14px;
        margin-bottom: 12px;
    }
    
    .gip-result-reason::before {
        font-size: 10px;
        margin-bottom: 4px;
    }
    
    .gip-result-meta {
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    
    .gip-result-meta-item {
        padding: 8px 10px;
    }
    
    .gip-result-meta-label {
        font-size: 10px;
    }
    
    .gip-result-meta-value {
        font-size: 13px;
    }
    
    .gip-result-meta-value {
        font-size: 13px;
    }
    
    /* サブ結果 - モバイル */
    .gip-results-grid-sub {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .gip-results-section-title {
        font-size: 14px;
    }
    
    .gip-result-sub-content {
        flex-wrap: wrap;
        padding: 12px;
    }
    
    .gip-result-sub-rank {
        width: 24px;
        height: 24px;
        font-size: 11px;
    }
    
    .gip-result-sub-title {
        font-size: 12px;
    }
    
    .gip-result-sub-meta {
        font-size: 10px;
    }
    
    .gip-sub-ask-btn {
        width: 100%;
        margin-top: 8px;
        font-size: 12px;
        padding: 10px 14px;
    }
    
    /* 再検索オプション */
    .gip-research-options {
        padding: 14px;
        margin-top: 16px;
    }
    
    .gip-research-title {
        font-size: 13px;
    }
    
    .gip-research-btns {
        flex-direction: column;
        gap: 8px;
    }
    
    .gip-research-btn {
        width: 100%;
        min-height: 44px;
    }
    
    /* アクションボタン - フル幅 */
    .gip-result-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 12px;
    }
    
    .gip-result-btn {
        width: 100%;
        min-height: 48px;
        font-size: 14px;
        white-space: normal;
        word-break: keep-all;
    }
    
    .gip-result-footer {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
        padding: 12px;
    }
    
    .gip-feedback-btns {
        display: flex;
        justify-content: center;
        gap: 12px;
    }
    
    .gip-feedback-btn {
        flex: 1;
        min-height: 44px;
    }
    
    /* 比較モーダル */
    .gip-comparison-content {
        border-radius: 0;
        max-height: 100vh;
        max-height: 100dvh;
    }
    
    .gip-comparison-header {
        padding: 14px 16px;
    }
    
    .gip-comparison-title {
        font-size: 15px;
    }
    
    .gip-comparison-body {
        padding: 12px;
    }
    
    .gip-comparison-table th,
    .gip-comparison-table td {
        padding: 8px 10px;
        font-size: 12px;
    }
}

/* ============================================================
   Extra Small Devices (max-width: 480px)
   ============================================================ */
@media (max-width: 480px) {
    /* 横スクロール完全防止 */
    .gip-chat,
    .gip-chat-messages,
    .gip-results,
    .gip-result-card {
        max-width: 100% !important;
        overflow-x: hidden !important;
    }
    
    .gip-message-bot .gip-message-bubble {
        font-size: 14px;
        max-width: 100%;
        word-break: break-word;
    }
    
    .gip-option-btn {
        padding: 12px 14px;
        font-size: 14px;
        min-height: 48px;
        max-width: 100%;
    }
    
    .gip-results {
        padding: 12px 10px;
    }
    
    .gip-result-header {
        padding: 0;
    }
    
    .gip-result-body {
        padding: 10px 0;
    }
    
    .gip-result-meta {
        gap: 8px;
        grid-template-columns: 1fr !important;
    }
    
    .gip-result-actions {
        flex-direction: column !important;
        gap: 8px !important;
    }
    
    .gip-result-btn {
        width: 100% !important;
        white-space: normal !important;
        text-align: center !important;
    }
    
    .gip-result-footer {
        flex-direction: column !important;
        gap: 10px !important;
    }
    
    .gip-feedback-btns {
        width: 100%;
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

/* ======================================
   戻る機能・再調整機能・詳細フィードバックUI
   ====================================== */

/* 戻るボタン - チャットヘッダー内 */
.gip-step-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    background: var(--gip-gray-100);
    border: 1px solid var(--gip-gray-300);
    border-radius: 20px;
    font-size: 12px;
    color: var(--gip-gray-600);
    cursor: pointer;
    transition: var(--gip-transition);
    margin-left: auto;
}

.gip-step-back-btn:hover {
    background: var(--gip-gray-200);
    color: var(--gip-gray-700);
}

.gip-step-back-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.gip-step-back-btn svg {
    width: 14px;
    height: 14px;
}

/* 進捗バー */
.gip-progress-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: var(--gip-gray-50);
    border-bottom: 1px solid var(--gip-gray-200);
    font-size: 12px;
    color: var(--gip-gray-600);
}

.gip-progress-steps {
    display: flex;
    gap: 4px;
}

.gip-progress-step {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--gip-gray-300);
    transition: var(--gip-transition);
}

.gip-progress-step.active {
    background: var(--gip-accent);
}

.gip-progress-step.completed {
    background: #10b981;
}

.gip-progress-label {
    flex: 1;
    text-align: center;
}

/* 再調整パネル */
.gip-readjust-panel {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: var(--gip-radius);
    padding: 16px;
    margin: 12px 0;
    border: 1px solid var(--gip-gray-200);
}

.gip-readjust-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-size: 14px;
    font-weight: 600;
    color: var(--gip-gray-700);
}

.gip-readjust-header svg {
    width: 18px;
    height: 18px;
    color: var(--gip-accent);
}

.gip-readjust-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 8px;
}

.gip-readjust-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 12px;
    background: var(--gip-white);
    border: 1px solid var(--gip-gray-300);
    border-radius: 8px;
    font-size: 13px;
    color: var(--gip-gray-700);
    cursor: pointer;
    transition: var(--gip-transition);
}

.gip-readjust-btn:hover {
    background: var(--gip-accent-light);
    border-color: var(--gip-accent);
    color: var(--gip-accent);
}

.gip-readjust-btn svg {
    width: 14px;
    height: 14px;
}

/* 詳細フィードバックモーダル */
.gip-feedback-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100001;
    opacity: 0;
    visibility: hidden;
    transition: var(--gip-transition);
}

.gip-feedback-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.gip-feedback-modal {
    background: var(--gip-white);
    border-radius: var(--gip-radius-lg);
    max-width: 440px;
    width: 90%;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: var(--gip-shadow-lg);
    transform: scale(0.95) translateY(20px);
    transition: var(--gip-transition);
}

.gip-feedback-modal-overlay.active .gip-feedback-modal {
    transform: scale(1) translateY(0);
}

.gip-feedback-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--gip-gray-200);
}

.gip-feedback-modal-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--gip-gray-800);
}

.gip-feedback-modal-close {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    border-radius: 50%;
    color: var(--gip-gray-500);
    cursor: pointer;
    transition: var(--gip-transition);
}

.gip-feedback-modal-close:hover {
    background: var(--gip-gray-100);
    color: var(--gip-gray-700);
}

.gip-feedback-modal-body {
    padding: 20px;
}

/* 評価セクション */
.gip-feedback-section {
    margin-bottom: 20px;
}

.gip-feedback-section-label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: var(--gip-gray-700);
    margin-bottom: 8px;
}

/* 星評価 */
.gip-rating-stars {
    display: flex;
    gap: 8px;
}

.gip-star-btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gip-gray-100);
    border: 2px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: var(--gip-transition);
}

.gip-star-btn svg {
    width: 24px;
    height: 24px;
    fill: var(--gip-gray-300);
    stroke: var(--gip-gray-400);
    transition: var(--gip-transition);
}

.gip-star-btn:hover svg,
.gip-star-btn.active svg {
    fill: #fbbf24;
    stroke: #f59e0b;
}

.gip-star-btn.active {
    border-color: #fbbf24;
    background: #fef3c7;
}

/* フィードバックタイプ選択 */
.gip-feedback-types {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.gip-feedback-type-btn {
    padding: 8px 16px;
    background: var(--gip-gray-100);
    border: 2px solid transparent;
    border-radius: 20px;
    font-size: 13px;
    color: var(--gip-gray-600);
    cursor: pointer;
    transition: var(--gip-transition);
}

.gip-feedback-type-btn:hover {
    background: var(--gip-gray-200);
}

.gip-feedback-type-btn.selected {
    background: var(--gip-accent-light);
    border-color: var(--gip-accent);
    color: var(--gip-accent);
}

/* テキストエリア */
.gip-feedback-textarea {
    width: 100%;
    min-height: 100px;
    padding: 12px;
    border: 1px solid var(--gip-gray-300);
    border-radius: 8px;
    font-family: inherit;
    font-size: 14px;
    line-height: 1.5;
    resize: vertical;
    transition: var(--gip-transition);
}

.gip-feedback-textarea:focus {
    outline: none;
    border-color: var(--gip-accent);
    box-shadow: 0 0 0 3px rgba(31, 31, 31, 0.1);
}

.gip-feedback-textarea::placeholder {
    color: var(--gip-gray-400);
}

/* メール入力 */
.gip-feedback-email {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--gip-gray-300);
    border-radius: 8px;
    font-size: 14px;
    transition: var(--gip-transition);
}

.gip-feedback-email:focus {
    outline: none;
    border-color: var(--gip-accent);
}

.gip-feedback-email-note {
    font-size: 11px;
    color: var(--gip-gray-500);
    margin-top: 4px;
}

/* 送信ボタン */
.gip-feedback-submit {
    width: 100%;
    padding: 14px;
    background: var(--gip-accent);
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    color: var(--gip-white);
    cursor: pointer;
    transition: var(--gip-transition);
}

.gip-feedback-submit:hover {
    background: var(--gip-gray-800);
}

.gip-feedback-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* フィードバック完了メッセージ */
.gip-feedback-success {
    text-align: center;
    padding: 30px 20px;
}

.gip-feedback-success-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 16px;
    background: #d1fae5;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.gip-feedback-success-icon svg {
    width: 32px;
    height: 32px;
    color: #10b981;
}

.gip-feedback-success-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--gip-gray-800);
    margin-bottom: 8px;
}

.gip-feedback-success-text {
    font-size: 14px;
    color: var(--gip-gray-600);
}

/* 結果サマリーパネル */
.gip-results-summary {
    background: linear-gradient(135deg, var(--gip-accent) 0%, var(--gip-gray-800) 100%);
    color: var(--gip-white);
    border-radius: var(--gip-radius);
    padding: 20px;
    margin-bottom: 16px;
}

.gip-results-summary-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.gip-results-summary-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.gip-results-summary-icon svg {
    width: 28px;
    height: 28px;
}

.gip-results-summary-title {
    font-size: 18px;
    font-weight: 600;
}

.gip-results-summary-count {
    font-size: 14px;
    opacity: 0.9;
}

.gip-results-summary-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 12px;
    padding-top: 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.gip-summary-stat {
    text-align: center;
}

.gip-summary-stat-value {
    font-size: 20px;
    font-weight: 700;
}

.gip-summary-stat-label {
    font-size: 11px;
    opacity: 0.8;
}

/* 診断結果フィードバックボタン群 */
.gip-results-feedback-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 16px;
    background: var(--gip-gray-50);
    border-radius: var(--gip-radius);
    margin-bottom: 16px;
}

.gip-results-feedback-text {
    font-size: 13px;
    color: var(--gip-gray-600);
}

.gip-results-feedback-btns {
    display: flex;
    gap: 8px;
}

.gip-results-fb-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    background: var(--gip-white);
    border: 2px solid var(--gip-gray-300);
    border-radius: 24px;
    font-size: 14px;
    font-weight: 500;
    color: var(--gip-gray-700);
    cursor: pointer;
    transition: var(--gip-transition);
}

.gip-results-fb-btn:hover {
    background: var(--gip-gray-100);
    transform: translateY(-1px);
}

.gip-results-fb-btn.positive {
    border-color: #86efac;
    color: #166534;
}

.gip-results-fb-btn.positive:hover {
    background: #dcfce7;
    border-color: #22c55e;
    color: #166534;
}

.gip-results-fb-btn.negative {
    border-color: #fca5a5;
    color: #991b1b;
}

.gip-results-fb-btn.negative:hover {
    background: #fee2e2;
    border-color: #ef4444;
    color: #991b1b;
}

.gip-results-fb-btn svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

/* フィードバック選択済みスタイル */
.gip-results-fb-btn.selected.positive {
    background: #dcfce7;
    border-color: #22c55e;
    color: #166534;
    box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
}

.gip-results-fb-btn.selected.negative {
    background: #fee2e2;
    border-color: #ef4444;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
    color: #dc2626;
}

/* フィードバックパネル */
.gip-results-feedback-panel {
    margin-bottom: 16px;
}

/* フィードバックコメント入力欄 */
.gip-feedback-comment-section {
    margin-top: 12px;
    padding: 16px;
    background: var(--gip-gray-50);
    border-radius: var(--gip-radius);
}

.gip-feedback-comment-header {
    margin-bottom: 10px;
}

.gip-feedback-comment-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--gip-gray-700);
}

.gip-feedback-comment-input {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--gip-gray-300);
    border-radius: var(--gip-radius);
    font-family: inherit;
    font-size: 14px;
    line-height: 1.6;
    resize: vertical;
    min-height: 80px;
    box-sizing: border-box;
}

.gip-feedback-comment-input:focus {
    outline: none;
    border-color: var(--gip-accent);
}

.gip-feedback-comment-input::placeholder {
    color: var(--gip-gray-400);
}

.gip-feedback-submit-row {
    display: flex;
    gap: 10px;
    margin-top: 12px;
}

.gip-feedback-submit-btn {
    flex: 1;
    padding: 10px 16px;
    background: var(--gip-accent);
    color: var(--gip-white);
    border: none;
    border-radius: var(--gip-radius);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--gip-transition);
}

.gip-feedback-submit-btn:hover {
    background: var(--gip-gray-800);
}

.gip-feedback-skip-btn {
    padding: 10px 16px;
    background: var(--gip-white);
    color: var(--gip-gray-600);
    border: 1px solid var(--gip-gray-300);
    border-radius: var(--gip-radius);
    font-size: 13px;
    cursor: pointer;
    transition: var(--gip-transition);
}

.gip-feedback-skip-btn:hover {
    background: var(--gip-gray-100);
    color: var(--gip-gray-700);
}

/* フィードバック評価ボタン */
.gip-feedback-rating {
    display: flex;
    gap: 12px;
    justify-content: center;
    margin-bottom: 16px;
}

.gip-feedback-rating-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 12px 20px;
    background: var(--gip-white);
    border: 2px solid var(--gip-gray-200);
    border-radius: var(--gip-radius);
    cursor: pointer;
    transition: var(--gip-transition);
    font-size: 13px;
    color: var(--gip-gray-600);
}

.gip-feedback-rating-btn:hover {
    border-color: var(--gip-accent);
    background: var(--gip-gray-50);
}

.gip-feedback-rating-btn.selected {
    border-color: var(--gip-accent);
    background: rgba(37, 99, 235, 0.08);
    color: var(--gip-accent);
}

.gip-rating-icon {
    font-size: 24px;
    line-height: 1;
}

/* フィードバックコメント送信ボタン */
.gip-feedback-comment-submit {
    display: block;
    width: 100%;
    margin-top: 12px;
    padding: 12px 20px;
    background: var(--gip-accent);
    color: var(--gip-white);
    border: none;
    border-radius: var(--gip-radius);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--gip-transition);
}

.gip-feedback-comment-submit:hover {
    background: var(--gip-gray-800);
}

.gip-feedback-comment-submit:disabled {
    background: var(--gip-gray-300);
    cursor: not-allowed;
}

/* フィードバックコメントヘッダー・説明 */
.gip-feedback-comment-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.gip-feedback-comment-icon {
    font-size: 20px;
}

.gip-feedback-comment-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--gip-gray-800);
}

.gip-feedback-comment-desc {
    font-size: 13px;
    color: var(--gip-gray-600);
    margin-bottom: 16px;
}

.gip-feedback-comment-note {
    font-size: 12px;
    color: var(--gip-gray-500);
    margin-top: 10px;
    text-align: center;
}

.gip-feedback-thanks {
    padding: 16px;
    background: linear-gradient(135deg, #d1fae5 0%, #f0fdf4 100%);
    border-radius: var(--gip-radius);
    text-align: center;
}

.gip-feedback-thanks-text {
    font-size: 14px;
    font-weight: 600;
    color: #059669;
}

/* PC向けポップアップ調整 */
@media (min-width: 769px) {
    /* .gip-modal への max-width 指定を削除 - 全画面オーバーレイとして機能させる */
    
    .gip-modal .gip-chat-messages {
        min-height: 450px;
        max-height: 55vh;
    }
    
    .gip-modal .gip-results {
        max-height: 50vh;
    }
    
    .gip-modal .gip-result-card {
        padding: 20px;
    }
}

/* タブレット向け */
@media (min-width: 769px) and (max-width: 1024px) {
    /* .gip-modal への max-width 指定を削除 */
}

/* 大画面向け */
@media (min-width: 1200px) {
    /* .gip-modal への max-width 指定を削除 */
    
    .gip-modal .gip-chat-messages {
        min-height: 500px;
    }
}

/* ======================================
   サブ結果カード・個別フィードバック
   ====================================== */

/* サブ結果グリッド */
.gip-results-sub {
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid var(--gip-gray-200);
}

.gip-results-grid-sub {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
}

/* サブ結果カード（コンパクト版） */
.gip-result-card-sub {
    padding: 12px 16px;
    background: var(--gip-white);
    border: 1px solid var(--gip-gray-200);
    border-radius: 8px;
    transition: var(--gip-transition);
}

.gip-result-card-sub:hover {
    border-color: var(--gip-gray-300);
    box-shadow: var(--gip-shadow);
}

.gip-result-sub-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.gip-result-sub-rank {
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gip-gray-100);
    border-radius: 50%;
    font-size: 12px;
    font-weight: 600;
    color: var(--gip-gray-600);
}

.gip-result-sub-info {
    flex: 1;
    min-width: 0;
}

.gip-result-sub-title {
    font-size: 13px;
    font-weight: 500;
    color: var(--gip-gray-800);
    margin: 0 0 4px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.gip-result-sub-meta {
    display: flex;
    gap: 12px;
    font-size: 11px;
    color: var(--gip-gray-500);
}

.gip-result-sub-amount {
    color: var(--gip-accent);
    font-weight: 500;
}

.gip-result-sub-score {
    color: var(--gip-gray-500);
}

.gip-sub-ask-btn {
    flex-shrink: 0;
    padding: 6px 12px;
    background: var(--gip-gray-100);
    border: none;
    border-radius: 6px;
    font-size: 12px;
    color: var(--gip-gray-600);
    cursor: pointer;
    transition: var(--gip-transition);
}

.gip-sub-ask-btn:hover {
    background: var(--gip-accent);
    color: var(--gip-white);
}

/* 結果カードハイライト */
.gip-result-card.highlight {
    border-left: 3px solid var(--gip-accent);
}

/* スコアバッジ */
.gip-result-score-badge {
    display: inline-block;
    padding: 2px 8px;
    background: var(--gip-accent);
    color: var(--gip-white);
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    margin-top: 4px;
}

/* 個別フィードバック */
.gip-result-feedback {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--gip-gray-100);
}

.gip-result-feedback-label {
    font-size: 12px;
    color: var(--gip-gray-500);
}

.gip-feedback-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: var(--gip-gray-100);
    border: 1px solid transparent;
    border-radius: 6px;
    color: var(--gip-gray-500);
    cursor: pointer;
    transition: var(--gip-transition);
}

.gip-feedback-btn:hover {
    background: var(--gip-gray-200);
    color: var(--gip-gray-700);
}

.gip-feedback-btn.selected[data-feedback="positive"] {
    background: #d1fae5;
    border-color: #10b981;
    color: #059669;
}

.gip-feedback-btn.selected[data-feedback="negative"] {
    background: #fee2e2;
    border-color: #ef4444;
    color: #dc2626;
}

/* フィードバックバーレスポンシブ */
@media (max-width: 500px) {
    .gip-results-feedback-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    
    .gip-results-feedback-btns {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .gip-results-fb-btn {
        padding: 6px 10px;
        font-size: 12px;
    }
}

/* 再調整パネルレスポンシブ */
@media (max-width: 500px) {
    .gip-readjust-options {
        grid-template-columns: 1fr 1fr;
    }
    
    .gip-readjust-btn {
        padding: 8px 10px;
        font-size: 12px;
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
        loadingTimer: null,
        loadingPhrases: [
            "考え中...",
            "データベースを検索中...",
            "条件を分析しています...",
            "最適な補助金を選定中...",
            "情報を整理しています..."
        ],
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
        messageCount: 0,
        
        init: function() {
            var self = this;
            
            if (self.initialized) {
                console.log('GIP Chat: Already initialized');
                return;
            }
            
            // モーダル内のチャット(.gip-chat--modal)は除外し、インラインチャットのみを対象にする
            self.$container = $('.gip-chat').not('.gip-chat--modal');
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
            
            console.log('GIP Chat: Initializing v7.3...');
            
            self.$messages = self.$container.find('.gip-chat-messages');
            self.$input = self.$container.find('.gip-chat-input');
            self.$send = self.$container.find('.gip-chat-send');
            self.$results = self.$container.find('.gip-results');
            self.$inputArea = self.$container.find('.gip-chat-input-area');
            
            // 初期状態では入力エリアを非表示
            self.$inputArea.hide();
            
            // 送信ボタンの初期状態（無効化）
            self.toggleSendButton(false);
            
            // スクロール検知用フラグ
            self.isUserScrolling = false;
            
            // セッション復元
            var savedSession = sessionStorage.getItem('gip_session_id');
            var savedHistory = sessionStorage.getItem('gip_chat_history');
            
            self.bindEvents();
            
            // セッション復元または新規開始
            if (savedSession && savedHistory) {
                console.log('GIP Chat: Resuming session ' + savedSession);
                self.sessionId = savedSession;
                self.$messages.html(savedHistory);
                self.scrollToBottom(false);
            } else {
                self.startSession();
            }
            
            self.initialized = true;
            
            console.log('GIP Chat: Initialization complete');
        },
        
        toggleSendButton: function(enable) {
            this.$send.prop('disabled', !enable);
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
            
            // テキストエリアの自動リサイズ＆送信ボタン制御
            self.$input.off('input.gip').on('input.gip', function() {
                var val = $(this).val().trim();
                self.toggleSendButton(val.length > 0);
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
            
            // 結果エリアの開閉トグル
            self.$container.on('click.gip', '.gip-results-header', function(e) {
                // クローズボタンやリンクのクリックは除外
                if ($(e.target).closest('.gip-results-close-btn, a, button').length) return;
                var $results = $(this).closest('.gip-results');
                $results.toggleClass('minimized');
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
                        // 結果エリアを非表示にしてトークを見やすく
                        if (self.$results && self.$results.is(':visible')) {
                            self.$results.slideUp(300);
                        }
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
                })
                .on('click.gip', '.gip-back-btn', function(e) {
                    e.preventDefault();
                    if (!self.isLoading) {
                        self.stepBack(1);
                    }
                });
            
            // ESCキーでモーダルを閉じる
            $(document).on('keydown.gip', function(e) {
                if (e.key === 'Escape') {
                    $('.gip-comparison-modal').remove();
                }
            });
            
            // MutationObserver でメッセージ追加を検知して自動スクロール
            self.setupAutoScroll();
        },
        
        setupAutoScroll: function() {
            var self = this;
            if (!self.$messages.length) return;
            
            var observer = new MutationObserver(function(mutations) {
                // ユーザーがスクロール中でなければ自動スクロール
                if (!self.isUserScrolling) {
                    self.scrollToBottom(true);
                }
            });
            
            observer.observe(self.$messages[0], {
                childList: true,
                subtree: true
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
            
            // 送信時にキーボードを閉じる
            self.hideKeyboard();
            
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
            
            // 選択時にキーボードを閉じる
            self.hideKeyboard();
            
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
                        
                        // セッション保存
                        sessionStorage.setItem('gip_session_id', self.sessionId);
                        
                        self.addMessage('bot', response.message);
                        
                        // HTML履歴保存（レスポンス後に遅延実行）
                        setTimeout(function(){
                            sessionStorage.setItem('gip_chat_history', self.$messages.html());
                        }, 500);
                        
                        // 赤文字の重要ヒント（自然言語入力を促す）
                        if (response.hint_important) {
                            self.showHintImportant(response.hint_important);
                        }
                        
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
                    console.error('GIP Chat: XHR Status', xhr.status);
                    console.error('GIP Chat: Response Text', xhr.responseText);
                    console.error('GIP Chat: API URL was', GIP_CHAT.api + '/chat');
                    self.hideTyping();
                    
                    var errorMsg = '通信エラーが発生しました。';
                    if (status === 'timeout') {
                        errorMsg = 'リクエストがタイムアウトしました。もう一度お試しください。';
                    } else if (xhr.status === 0) {
                        errorMsg = 'ネットワーク接続を確認してください。';
                    } else if (xhr.status === 400) {
                        errorMsg = 'リクエストエラーです。ページを更新してください。';
                    } else if (xhr.status === 404) {
                        errorMsg = 'APIエンドポイントが見つかりません。パーマリンク設定を確認してください。';
                        console.error('GIP Chat: 404 - REST API endpoint not found. Check permalink settings.');
                    } else if (xhr.status >= 500) {
                        errorMsg = 'サーバーエラーが発生しました。しばらく待ってからお試しください。';
                    }
                    
                    // レスポンスJSONがある場合は解析
                    try {
                        var respJson = JSON.parse(xhr.responseText);
                        if (respJson.message) {
                            console.error('GIP Chat: Server message:', respJson.message);
                            if (respJson.code === 'rest_no_route') {
                                errorMsg = 'APIルートが見つかりません。管理画面で「設定」→「パーマリンク」を開き、保存ボタンを押してください。';
                            }
                        }
                    } catch(e) {}
                    
                    self.addMessage('bot', errorMsg);
                },
                complete: function() {
                    self.isLoading = false;
                    self.$send.prop('disabled', false);
                    
                    // キーボードを閉じる（モバイル対応）
                    self.hideKeyboard();
                }
            });
        },
        
        hideKeyboard: function() {
            var self = this;
            // 入力フィールドからフォーカスを外してキーボードを閉じる
            if (document.activeElement && 
                (document.activeElement.tagName === 'INPUT' || 
                 document.activeElement.tagName === 'TEXTAREA')) {
                document.activeElement.blur();
            }
            self.$input.blur();
            
            // セレクトボックスやインライン入力からもフォーカスを外す
            self.$messages.find('.gip-inline-input, .gip-select').blur();
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
            self.messageCount++;
            self.scrollToBottom();
        },
        
        showHint: function(hint) {
            var self = this;
            var $lastMessage = self.$messages.find('.gip-message:last .gip-message-content');
            var html = '<div class="gip-hint">' + self.escapeHtml(hint) + '</div>';
            $lastMessage.append(html);
        },
        
        // 赤文字の重要ヒント表示（自然言語入力を促す）
        showHintImportant: function(hint) {
            var self = this;
            var $lastMessage = self.$messages.find('.gip-message:last .gip-message-content');
            var html = '<div class="gip-hint-important">' + self.escapeHtml(hint) + '</div>';
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
            
            // 前の質問に戻るボタン（最初の質問以外で表示）
            if (self.messageCount > 1) {
                html += '<div class="gip-back-btn-wrap">';
                html += '<button type="button" class="gip-back-btn">';
                html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>';
                html += '前の質問に戻る';
                html += '</button></div>';
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
            html += '</div>';
            html += '<div class="gip-loading-text">考え中...</div>';
            html += '</div></div>';
            
            self.$messages.append(html);
            self.scrollToBottom();
            
            // テキストローテーション開始
            var phraseIndex = 0;
            self.loadingTimer = setInterval(function() {
                var $text = self.$messages.find('.gip-loading-text');
                if ($text.length) {
                    phraseIndex = (phraseIndex + 1) % self.loadingPhrases.length;
                    $text.text(self.loadingPhrases[phraseIndex]);
                } else {
                    clearInterval(self.loadingTimer);
                }
            }, 2500);
        },
        
        hideTyping: function() {
            clearInterval(this.loadingTimer);
            this.$messages.find('.gip-message-typing-wrap').remove();
        },
        
        renderResults: function(showComparison, response) {
            var self = this;
            response = response || {};
            
            // メイン5件とサブ5件に分割
            var mainResults = response.main_results || self.allResults.slice(0, 5);
            var subResults = response.sub_results || self.allResults.slice(5, 10);
            var remainingResults = self.allResults.slice(10);
            
            self.displayedCount = mainResults.length + subResults.length;
            
            // 結果サマリーパネル
            var html = '<div class="gip-results-summary">';
            html += '<div class="gip-results-summary-header">';
            html += '<div class="gip-results-summary-icon">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>';
            html += '</div>';
            html += '<div>';
            html += '<div class="gip-results-summary-title">診断完了</div>';
            html += '<div class="gip-results-summary-count">' + self.allResults.length + '件の補助金が見つかりました</div>';
            html += '</div>';
            html += '</div>';
            html += '<div class="gip-results-summary-info">';
            // 合計金額（上位5件の最大補助額の合計概算）
            var totalAmount = 0;
            var openCount = 0;
            for (var k = 0; k < Math.min(5, self.allResults.length); k++) {
                var amt = self.allResults[k].max_amount_numeric || 0;
                if (amt > 0) totalAmount += amt;
                if (self.allResults[k].application_status === 'open' || !self.allResults[k].application_status) openCount++;
            }
            var amountDisplay = totalAmount > 100000000 ? Math.round(totalAmount / 100000000) + '億円+' : (totalAmount > 10000 ? Math.round(totalAmount / 10000) + '万円+' : '要確認');
            html += '<div class="gip-summary-stat"><div class="gip-summary-stat-value">' + self.allResults.length + '</div><div class="gip-summary-stat-label">該当件数</div></div>';
            html += '<div class="gip-summary-stat"><div class="gip-summary-stat-value">' + openCount + '</div><div class="gip-summary-stat-label">受付中</div></div>';
            html += '<div class="gip-summary-stat"><div class="gip-summary-stat-value">' + amountDisplay + '</div><div class="gip-summary-stat-label">補助金額目安</div></div>';
            html += '</div>';
            html += '</div>';
            
            // フィードバックパネル（コメント入力欄付き）
            html += '<div class="gip-results-feedback-panel">';
            html += '<div class="gip-results-feedback-bar">';
            html += '<span class="gip-results-feedback-text">この診断結果はいかがでしたか？</span>';
            html += '<div class="gip-results-feedback-btns">';
            html += '<button type="button" class="gip-results-fb-btn positive" data-feedback="positive">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3zM7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3"/></svg>';
            html += '参考になった';
            html += '</button>';
            html += '<button type="button" class="gip-results-fb-btn negative" data-feedback="negative">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 15v4a3 3 0 003 3l4-9V2H5.72a2 2 0 00-2 1.7l-1.38 9a2 2 0 002 2.3zm7-13h2.67A2.31 2.31 0 0122 4v7a2.31 2.31 0 01-2.33 2H17"/></svg>';
            html += '期待と違った';
            html += '</button>';
            html += '</div>';
            html += '</div>';
            
            // フィードバックコメント入力欄（初期非表示、ボタンクリックで表示）
            html += '<div class="gip-feedback-comment-section" style="display:none;">';
            html += '<div class="gip-feedback-comment-header">';
            html += '<span class="gip-feedback-comment-label">ご意見・改善点をお聞かせください（任意）</span>';
            html += '</div>';
            html += '<textarea class="gip-feedback-comment-input" rows="3" placeholder="診断結果について気になった点や、改善のご要望があればお聞かせください..."></textarea>';
            html += '<div class="gip-feedback-submit-row">';
            html += '<button type="button" class="gip-feedback-submit-btn">送信する</button>';
            html += '<button type="button" class="gip-feedback-skip-btn">スキップ</button>';
            html += '</div>';
            html += '</div>';
            html += '<div class="gip-feedback-thanks" style="display:none;">';
            html += '<span class="gip-feedback-thanks-text">ご協力ありがとうございました！</span>';
            html += '</div>';
            html += '</div>';
            
            // 結果ヘッダー（トグル機能付き）
            html += '<div class="gip-results-header" title="タップして開閉">';
            html += '<div style="display:flex; justify-content:space-between; width:100%; align-items:center;">';
            html += '<div>';
            html += '<h3 class="gip-results-title">マッチした補助金 (' + self.allResults.length + '件)</h3>';
            html += '<div style="font-size:12px; color:var(--gip-gray-500);">タップして結果を最小化/展開</div>';
            html += '</div>';
            html += '<div class="gip-results-toggle-icon">';
            html += '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 15l-6-6-6 6"/></svg>';
            html += '</div>';
            html += '</div>';
            
            if (showComparison && self.allResults.length >= 2) {
                html += '<button type="button" class="gip-btn-compare" disabled>比較する（2件以上選択）</button>';
            }
            html += '</div>';
            
            // メイン5件（本命・大きく表示）
            if (mainResults.length > 0) {
                html += '<div class="gip-results-main">';
                html += '<h4 class="gip-results-section-title">おすすめの補助金</h4>';
                html += '<div class="gip-results-grid gip-results-grid-main">';
                for (var i = 0; i < mainResults.length; i++) {
                    html += self.renderResultCard(mainResults[i], i, false);
                }
                html += '</div></div>';
            }
            
            // サブ5件（小さいカード表示）
            if (subResults.length > 0) {
                html += '<div class="gip-results-sub">';
                html += '<h4 class="gip-results-section-title">他にもこのような補助金があります</h4>';
                html += '<div class="gip-results-grid gip-results-grid-sub">';
                for (var i = 0; i < subResults.length; i++) {
                    html += self.renderSubResultCard(subResults[i], i + 5);
                }
                html += '</div></div>';
            }
            
            // 残りがあればロードモア
            if (remainingResults.length > 0) {
                html += '<div class="gip-load-more">';
                html += '<button type="button" class="gip-btn-load-more">さらに表示（残り' + remainingResults.length + '件）</button>';
                html += '</div>';
            }
            
            // 再調整パネル
            html += '<div class="gip-readjust-panel">';
            html += '<div class="gip-readjust-header">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
            html += '<path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>';
            html += '</svg>';
            html += '<span>結果が期待と異なる場合</span>';
            html += '</div>';
            html += '<div class="gip-readjust-options">';
            html += '<button type="button" class="gip-readjust-btn" data-adjust="expand_area">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>';
            html += '地域を広げる';
            html += '</button>';
            html += '<button type="button" class="gip-readjust-btn" data-adjust="national">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            html += '全国で検索';
            html += '</button>';
            html += '<button type="button" class="gip-readjust-btn" data-adjust="change_purpose">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
            html += '目的を変更';
            html += '</button>';
            html += '<button type="button" class="gip-readjust-btn" data-adjust="restart">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>';
            html += '最初から';
            html += '</button>';
            html += '</div>';
            html += '</div>';
            
            self.$results.html(html).slideDown(300);
            
            // イベントバインド
            self.bindResultsEvents();
            
            setTimeout(function() {
                // スクロールはポップアップ内でのみ行う（ページ全体はスクロールしない）
                var $resultsInner = self.$results;
                if ($resultsInner.length && $resultsInner[0].scrollIntoView) {
                    // smooth scroll within the container only
                }
            }, 100);
        },
        
        // 結果エリアのイベントバインド
        bindResultsEvents: function() {
            var self = this;
            
            // 現在のフィードバックタイプを保存
            var currentFeedbackType = null;
            
            // フィードバックバーのクリック
            self.$results.off('click.gipfb').on('click.gipfb', '.gip-results-fb-btn', function() {
                var feedback = $(this).data('feedback');
                currentFeedbackType = feedback;
                $(this).addClass('selected').siblings().removeClass('selected');
                
                // 基本フィードバック送信（即座に保存）
                self.sendFeedback(0, feedback === 'positive' ? 'positive' : 'negative');
                
                // フィードバック送信完了メッセージ
                var msg = feedback === 'positive' ? 'ありがとうございます！' : 'ご意見をありがとうございます。';
                $(this).closest('.gip-results-feedback-bar').find('.gip-results-feedback-text').text(msg);
                
                // コメント入力欄を表示
                var $panel = $(this).closest('.gip-results-feedback-panel');
                $panel.find('.gip-feedback-comment-section').slideDown(200);
            });
            
            // フィードバックコメント送信
            self.$results.off('click.gipfbsubmit').on('click.gipfbsubmit', '.gip-feedback-submit-btn', function() {
                var $panel = $(this).closest('.gip-results-feedback-panel');
                var comment = $panel.find('.gip-feedback-comment-input').val().trim();
                
                if (comment) {
                    // 詳細フィードバック送信
                    self.sendDetailedFeedback({
                        feedbackType: currentFeedbackType === 'positive' ? 'helpful' : 'not_helpful',
                        rating: currentFeedbackType === 'positive' ? 4 : 2,
                        comment: comment
                    });
                }
                
                // コメント欄を非表示、感謝メッセージ表示
                $panel.find('.gip-feedback-comment-section').slideUp(200);
                $panel.find('.gip-feedback-thanks').slideDown(200);
            });
            
            // フィードバックスキップ
            self.$results.off('click.gipfbskip').on('click.gipfbskip', '.gip-feedback-skip-btn', function() {
                var $panel = $(this).closest('.gip-results-feedback-panel');
                $panel.find('.gip-feedback-comment-section').slideUp(200);
                $panel.find('.gip-feedback-thanks').slideDown(200);
            });
            
            // 再調整ボタン
            self.$results.off('click.gipreadj').on('click.gipreadj', '.gip-readjust-btn', function() {
                var adjustType = $(this).data('adjust');
                
                // 結果エリアを非表示にしてトークを見やすく
                if (self.$results && self.$results.is(':visible')) {
                    self.$results.slideUp(300);
                }
                
                if (adjustType === 'change_purpose') {
                    var newPurpose = prompt('新しい目的を入力してください：');
                    if (newPurpose) {
                        self.readjust('purpose', newPurpose);
                    }
                } else if (adjustType === 'restart') {
                    self.stepBack(99);
                } else {
                    self.readjust(adjustType, '');
                }
            });
        },
        
        // サブ結果カード（小さい表示）
        renderSubResultCard: function(r, index) {
            var self = this;
            
            var html = '<div class="gip-result-card gip-result-card-sub" data-grant-id="' + r.grant_id + '">';
            html += '<div class="gip-result-sub-content">';
            html += '<div class="gip-result-sub-rank">' + (index + 1) + '</div>';
            html += '<div class="gip-result-sub-info">';
            html += '<h5 class="gip-result-sub-title">' + self.escapeHtml(r.title) + '</h5>';
            html += '<div class="gip-result-sub-meta">';
            if (r.amount_display || r.max_amount) {
                html += '<span class="gip-result-sub-amount">' + self.escapeHtml(r.amount_display || r.max_amount) + '</span>';
            }
            if (r.score) {
                html += '<span class="gip-result-sub-score">マッチ度 ' + r.score + '点</span>';
            }
            html += '</div></div>';
            html += '<button type="button" class="gip-btn-ask-about gip-sub-ask-btn" data-grant-id="' + r.grant_id + '" data-title="' + self.escapeHtml(r.title) + '">詳しく</button>';
            html += '</div></div>';
            
            return html;
        },
        
        renderResultCard: function(r, index, isSub) {
            var self = this;
            isSub = isSub || false;
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
                html += '<div class="gip-result-reason">' + self.escapeHtml(r.reason) + '</div>';
            }
            
            html += '<div class="gip-result-meta">';
            
            var amountDisplay = r.amount_display || r.max_amount;
            if (amountDisplay) {
                html += '<div class="gip-result-meta-item">';
                html += '<span class="gip-result-meta-label">補助金額</span>';
                html += '<span class="gip-result-meta-value highlight">' + self.escapeHtml(amountDisplay) + '</span>';
                html += '</div>';
            }
            
            if (r.subsidy_rate) {
                html += '<div class="gip-result-meta-item">';
                html += '<span class="gip-result-meta-label">補助率</span>';
                html += '<span class="gip-result-meta-value">' + self.escapeHtml(r.subsidy_rate) + '</span>';
                html += '</div>';
            }
            
            var deadlineDisplay = r.deadline_display || r.deadline;
            if (deadlineDisplay) {
                html += '<div class="gip-result-meta-item">';
                html += '<span class="gip-result-meta-label">申請締切</span>';
                html += '<span class="gip-result-meta-value">' + self.escapeHtml(deadlineDisplay) + '</span>';
                html += '</div>';
            }
            
            if (r.online_application || r.jgrants_available) {
                html += '<div class="gip-result-meta-item">';
                html += '<span class="gip-result-meta-label">申請方法</span>';
                html += '<span class="gip-result-meta-value">';
                if (r.online_application) html += '<span class="gip-badge gip-badge-success">オンライン可</span> ';
                if (r.jgrants_available) html += '<span class="gip-badge gip-badge-info">jGrants</span>';
                html += '</span></div>';
            }
            
            html += '</div>';
            
            // AI要約または抜粋の表示
            if (r.ai_summary) {
                html += '<div class="gip-result-ai-summary">';
                html += '<span class="gip-result-ai-summary-label">✨ AI要約</span>';
                html += self.escapeHtml(r.ai_summary);
                html += '</div>';
            } else if (r.excerpt) {
                html += '<div class="gip-result-ai-summary">';
                html += '<span class="gip-result-ai-summary-label">📋 概要</span>';
                html += self.escapeHtml(r.excerpt);
                html += '</div>';
            }
            
            // 詳細セクション
            html += '<div class="gip-result-details">';
            if (r.grant_target) {
                html += '<div class="gip-result-details-section">';
                html += '<div class="gip-result-details-title">対象者</div>';
                html += '<div class="gip-result-details-content">' + self.escapeHtml(r.grant_target.substring(0, 500)) + '</div>';
                html += '</div>';
            }
            if (r.eligible_expenses) {
                html += '<div class="gip-result-details-section">';
                html += '<div class="gip-result-details-title">対象経費</div>';
                html += '<div class="gip-result-details-content">' + self.escapeHtml(r.eligible_expenses.substring(0, 500)) + '</div>';
                html += '</div>';
            }
            if (r.required_documents) {
                html += '<div class="gip-result-details-section">';
                html += '<div class="gip-result-details-title">必要書類</div>';
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
            html += '<button type="button" class="gip-feedback-btn" data-feedback="positive">';
            html += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>';
            html += 'はい</button>';
            html += '<button type="button" class="gip-feedback-btn" data-feedback="negative">';
            html += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"></path></svg>';
            html += 'いいえ</button>';
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
            html += '<p class="gip-continue-title">さらに詳しく知りたいことはありますか?</p>';
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
        
        // 詳細フィードバック送信
        sendDetailedFeedback: function(feedbackData) {
            var self = this;
            
            if (!self.sessionId) return;
            
            $.ajax({
                url: GIP_CHAT.api + '/feedback-detailed',
                method: 'POST',
                contentType: 'application/json',
                headers: {
                    'X-WP-Nonce': GIP_CHAT.nonce
                },
                data: JSON.stringify({
                    session_id: self.sessionId,
                    grant_id: feedbackData.grantId || 0,
                    feedback_type: feedbackData.feedbackType,
                    rating: feedbackData.rating || 0,
                    comment: feedbackData.comment || '',
                    suggestion: feedbackData.suggestion || '',
                    email: feedbackData.email || ''
                }),
                success: function(response) {
                    console.log('GIP Chat: Detailed feedback sent', response);
                    if (response.success) {
                        self.showFeedbackSuccess();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('GIP Chat: Detailed feedback error', status, error);
                    alert('フィードバックの送信に失敗しました。');
                }
            });
        },
        
        // フィードバックモーダルを表示
        showFeedbackModal: function(grantId) {
            var self = this;
            
            var html = '<div class="gip-feedback-modal-overlay">';
            html += '<div class="gip-feedback-modal">';
            html += '<div class="gip-feedback-modal-header">';
            html += '<span class="gip-feedback-modal-title">診断結果へのフィードバック</span>';
            html += '<button type="button" class="gip-feedback-modal-close">&times;</button>';
            html += '</div>';
            html += '<div class="gip-feedback-modal-body">';
            
            // 評価セクション
            html += '<div class="gip-feedback-section">';
            html += '<label class="gip-feedback-section-label">診断結果の満足度</label>';
            html += '<div class="gip-rating-stars" data-rating="0">';
            for (var i = 1; i <= 5; i++) {
                html += '<button type="button" class="gip-star-btn" data-rating="' + i + '">';
                html += '<svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                html += '</button>';
            }
            html += '</div>';
            html += '</div>';
            
            // フィードバックタイプ
            html += '<div class="gip-feedback-section">';
            html += '<label class="gip-feedback-section-label">フィードバックの種類</label>';
            html += '<div class="gip-feedback-types">';
            html += '<button type="button" class="gip-feedback-type-btn" data-type="helpful">参考になった</button>';
            html += '<button type="button" class="gip-feedback-type-btn" data-type="not_matched">条件に合わない</button>';
            html += '<button type="button" class="gip-feedback-type-btn" data-type="more_info">もっと情報がほしい</button>';
            html += '<button type="button" class="gip-feedback-type-btn" data-type="other">その他</button>';
            html += '</div>';
            html += '</div>';
            
            // コメント
            html += '<div class="gip-feedback-section">';
            html += '<label class="gip-feedback-section-label">コメント・改善提案</label>';
            html += '<textarea class="gip-feedback-textarea" placeholder="診断結果についてのご意見や、改善してほしい点があればお聞かせください..."></textarea>';
            html += '</div>';
            
            // メール（任意）
            html += '<div class="gip-feedback-section">';
            html += '<label class="gip-feedback-section-label">メールアドレス（任意）</label>';
            html += '<input type="email" class="gip-feedback-email" placeholder="example@email.com">';
            html += '<div class="gip-feedback-email-note">回答が必要な場合はご入力ください</div>';
            html += '</div>';
            
            html += '<button type="button" class="gip-feedback-submit" data-grant-id="' + (grantId || 0) + '">フィードバックを送信</button>';
            html += '</div></div></div>';
            
            $('body').append(html);
            
            // アニメーションで表示
            setTimeout(function() {
                $('.gip-feedback-modal-overlay').addClass('active');
            }, 10);
            
            // イベントハンドラー
            self.bindFeedbackModalEvents();
        },
        
        bindFeedbackModalEvents: function() {
            var self = this;
            
            // モーダルを閉じる
            $(document).off('click.gipfb').on('click.gipfb', '.gip-feedback-modal-close, .gip-feedback-modal-overlay', function(e) {
                if (e.target === this) {
                    self.closeFeedbackModal();
                }
            });
            
            // 星評価
            $(document).on('click.gipfb', '.gip-star-btn', function() {
                var rating = $(this).data('rating');
                var $container = $(this).closest('.gip-rating-stars');
                $container.data('rating', rating);
                $container.find('.gip-star-btn').removeClass('active');
                $container.find('.gip-star-btn').each(function() {
                    if ($(this).data('rating') <= rating) {
                        $(this).addClass('active');
                    }
                });
            });
            
            // フィードバックタイプ選択
            $(document).on('click.gipfb', '.gip-feedback-type-btn', function() {
                $('.gip-feedback-type-btn').removeClass('selected');
                $(this).addClass('selected');
            });
            
            // 送信
            $(document).on('click.gipfb', '.gip-feedback-submit', function() {
                var $modal = $('.gip-feedback-modal');
                var rating = $modal.find('.gip-rating-stars').data('rating') || 0;
                var feedbackType = $modal.find('.gip-feedback-type-btn.selected').data('type') || 'general';
                var comment = $modal.find('.gip-feedback-textarea').val().trim();
                var email = $modal.find('.gip-feedback-email').val().trim();
                var grantId = $(this).data('grant-id');
                
                if (rating === 0 && !feedbackType && !comment) {
                    alert('評価またはコメントを入力してください');
                    return;
                }
                
                $(this).prop('disabled', true).text('送信中...');
                
                self.sendDetailedFeedback({
                    grantId: grantId,
                    feedbackType: feedbackType,
                    rating: rating,
                    comment: comment,
                    email: email
                });
            });
        },
        
        closeFeedbackModal: function() {
            var $overlay = $('.gip-feedback-modal-overlay');
            $overlay.removeClass('active');
            setTimeout(function() {
                $overlay.remove();
            }, 300);
            $(document).off('click.gipfb');
        },
        
        showFeedbackSuccess: function() {
            var self = this;
            var $body = $('.gip-feedback-modal-body');
            
            $body.html(
                '<div class="gip-feedback-success">' +
                '<div class="gip-feedback-success-icon">' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<path d="M20 6L9 17l-5-5"/>' +
                '</svg>' +
                '</div>' +
                '<div class="gip-feedback-success-title">フィードバックを受け付けました</div>' +
                '<div class="gip-feedback-success-text">貴重なご意見をありがとうございます。<br>サービス改善に活用させていただきます。</div>' +
                '</div>'
            );
            
            setTimeout(function() {
                self.closeFeedbackModal();
            }, 2500);
        },
        
        // 戻る機能
        stepBack: function(stepsBack) {
            var self = this;
            stepsBack = stepsBack || 1;
            
            if (!self.sessionId) return;
            
            $.ajax({
                url: GIP_CHAT.api + '/step-back',
                method: 'POST',
                contentType: 'application/json',
                headers: {
                    'X-WP-Nonce': GIP_CHAT.nonce
                },
                data: JSON.stringify({
                    session_id: self.sessionId,
                    steps_back: stepsBack
                }),
                success: function(response) {
                    console.log('GIP Chat: Step back response', response);
                    if (response.success) {
                        // 前のステップに戻った旨を表示
                        self.addMessage('bot', response.message);
                        
                        if (response.restart) {
                            // 最初からやり直す場合
                            self.callApi({ session_id: self.sessionId, message: '' });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('GIP Chat: Step back error', status, error);
                }
            });
        },
        
        // 再調整機能
        readjust: function(adjustType, newValue) {
            var self = this;
            
            if (!self.sessionId || !adjustType) return;
            
            self.isLoading = true;
            self.showTyping();
            
            $.ajax({
                url: GIP_CHAT.api + '/readjust',
                method: 'POST',
                contentType: 'application/json',
                headers: {
                    'X-WP-Nonce': GIP_CHAT.nonce
                },
                data: JSON.stringify({
                    session_id: self.sessionId,
                    adjust_type: adjustType,
                    new_value: newValue || ''
                }),
                success: function(response) {
                    console.log('GIP Chat: Readjust response', response);
                    self.hideTyping();
                    
                    if (response.success) {
                        self.addMessage('bot', response.message);
                        
                        if (response.results && response.results.length > 0) {
                            self.allResults = response.results;
                            self.displayedCount = 0;
                            self.selectedForCompare = [];
                            self.renderResults(response.show_comparison);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('GIP Chat: Readjust error', status, error);
                    self.hideTyping();
                    self.addMessage('bot', '再検索中にエラーが発生しました。');
                },
                complete: function() {
                    self.isLoading = false;
                }
            });
        },
        
        // 再調整パネルを表示
        showReadjustPanel: function() {
            var self = this;
            
            var html = '<div class="gip-readjust-panel">';
            html += '<div class="gip-readjust-header">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
            html += '<path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>';
            html += '</svg>';
            html += '<span>条件を変更して再検索</span>';
            html += '</div>';
            html += '<div class="gip-readjust-options">';
            html += '<button type="button" class="gip-readjust-btn" data-adjust="expand_area">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>';
            html += '地域を広げる';
            html += '</button>';
            html += '<button type="button" class="gip-readjust-btn" data-adjust="national">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
            html += '全国で検索';
            html += '</button>';
            html += '<button type="button" class="gip-readjust-btn" data-adjust="change_purpose">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
            html += '目的を変更';
            html += '</button>';
            html += '<button type="button" class="gip-readjust-btn" data-adjust="restart">';
            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';
            html += '最初から';
            html += '</button>';
            html += '</div>';
            html += '</div>';
            
            self.$results.append(html);
            
            // イベントハンドラー
            self.$results.find('.gip-readjust-btn').on('click', function() {
                var adjustType = $(this).data('adjust');
                
                if (adjustType === 'change_purpose') {
                    // 目的変更の場合は入力を促す
                    var newPurpose = prompt('新しい目的を入力してください：');
                    if (newPurpose) {
                        self.readjust('purpose', newPurpose);
                    }
                } else if (adjustType === 'restart') {
                    // 最初からやり直す
                    self.stepBack(99);
                } else {
                    self.readjust(adjustType, '');
                }
            });
        },
        
        scrollToBottom: function(smooth) {
            var self = this;
            var el = self.$messages[0];
            
            if (el) {
                // チャットコンテナ内のスクロールのみ実行
                // ★重要: ウィンドウ全体のスクロール(scrollWindow)は削除
                // ポップアップ式に統一したため、ページ全体をスクロールする必要がない
                // これがページのカクカク・勝手にスクロールする原因だった
                var scrollContainer = function() {
                    if (el.scrollHeight > el.clientHeight) {
                        if (smooth !== false) {
                            el.scrollTo({
                                top: el.scrollHeight,
                                behavior: 'smooth'
                            });
                        } else {
                            el.scrollTop = el.scrollHeight;
                        }
                    }
                };
                
                // コンテナスクロールのみ実行
                scrollContainer();
                
                // DOMレンダリング後に再度実行
                requestAnimationFrame(function() {
                    scrollContainer();
                });
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

// =============================================================================
// ポップアップモーダル型AIコンシェルジュ
// =============================================================================

/**
 * CTAボタン用ショートコード
 * [gip_cta_button text="今すぐ補助金診断を始める" style="primary"]
 */
add_shortcode('gip_cta_button', 'gip_shortcode_cta_button');

function gip_shortcode_cta_button($atts = array()) {
    $atts = shortcode_atts(array(
        'text' => '今すぐ補助金診断を始める',
        'style' => 'primary', // primary, secondary, outline
        'size' => 'large', // small, medium, large
        'icon' => 'true', // true, false
        'class' => '',
    ), $atts);
    
    $class = 'gip-cta-btn';
    $class .= ' gip-cta-btn--' . esc_attr($atts['style']);
    $class .= ' gip-cta-btn--' . esc_attr($atts['size']);
    if (!empty($atts['class'])) {
        $class .= ' ' . esc_attr($atts['class']);
    }
    
    $icon_html = '';
    if ($atts['icon'] === 'true') {
        $icon_html = '<svg class="gip-cta-btn-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>';
    }
    
    return sprintf(
        '<button type="button" class="%s" data-gip-modal-open="true" aria-haspopup="dialog">%s<span>%s</span></button>',
        esc_attr($class),
        $icon_html,
        esc_html($atts['text'])
    );
}

/**
 * ポップアップモーダル用ショートコード
 * [gip_chat_modal]
 */
add_shortcode('gip_chat_modal', 'gip_shortcode_chat_modal');

function gip_shortcode_chat_modal($atts = array()) {
    static $modal_rendered = false;
    
    // モーダルは1回だけ出力
    if ($modal_rendered) {
        return '';
    }
    $modal_rendered = true;
    
    if (!wp_script_is('gip-chat-script', 'enqueued')) {
        gip_frontend_assets();
    }
    
    ob_start();
    ?>
    <!-- AIコンシェルジュ ポップアップモーダル -->
    <div id="gip-chat-modal" class="gip-modal" role="dialog" aria-modal="true" aria-labelledby="gip-modal-title" aria-hidden="true">
        <div class="gip-modal-overlay" data-gip-modal-close></div>
        <div class="gip-modal-container">
            <div class="gip-modal-header">
                <div class="gip-modal-header-content">
                    <div class="gip-modal-avatar">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <div class="gip-modal-header-text">
                        <h2 id="gip-modal-title" class="gip-modal-title">補助金診断コンシェルジュ</h2>
                        <p class="gip-modal-subtitle">
                            <span class="gip-modal-status-dot"></span>
                            オンライン対応中
                        </p>
                    </div>
                </div>
                <button type="button" class="gip-modal-close" data-gip-modal-close aria-label="閉じる">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="gip-modal-body">
                <div class="gip-chat gip-chat--modal" role="application" aria-label="補助金診断チャット">
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
            </div>
        </div>
    </div>
    
    <style>
    /* =============================================================================
       ポップアップモーダル用CSS
       ============================================================================= */
    
    /* CTAボタン共通スタイル */
    .gip-cta-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-family: "Noto Sans JP", -apple-system, BlinkMacSystemFont, sans-serif;
        font-weight: 700;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
    }
    
    .gip-cta-btn-icon {
        flex-shrink: 0;
    }
    
    /* サイズ */
    .gip-cta-btn--small {
        padding: 10px 20px;
        font-size: 13px;
        border-radius: 6px;
    }
    
    .gip-cta-btn--medium {
        padding: 14px 28px;
        font-size: 15px;
        border-radius: 8px;
    }
    
    .gip-cta-btn--large {
        padding: 18px 36px;
        font-size: 17px;
        border-radius: 10px;
    }
    
    /* スタイル - Primary */
    .gip-cta-btn--primary {
        background: linear-gradient(135deg, #111111 0%, #333333 100%);
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    
    .gip-cta-btn--primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }
    
    .gip-cta-btn--primary:active {
        transform: translateY(0);
    }
    
    /* スタイル - Secondary */
    .gip-cta-btn--secondary {
        background: #ffffff;
        color: #111111;
        border: 2px solid #111111;
    }
    
    .gip-cta-btn--secondary:hover {
        background: #111111;
        color: #ffffff;
    }
    
    /* スタイル - Outline */
    .gip-cta-btn--outline {
        background: transparent;
        color: #111111;
        border: 2px solid #cccccc;
    }
    
    .gip-cta-btn--outline:hover {
        border-color: #111111;
        background: rgba(0, 0, 0, 0.05);
    }
    
    /* モーダル */
    .gip-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 100000;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    
    .gip-modal[aria-hidden="false"] {
        opacity: 1;
        visibility: visible;
    }
    
    .gip-modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }
    
    .gip-modal-container,
    #gip-chat-modal .gip-modal-container,
    #gip-chat-modal > .gip-modal-container {
        position: relative;
        width: 80% !important;
        max-width: 1200px !important;
        height: 80vh !important;
        max-height: 80vh !important;
        min-width: 800px;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: column;
        transform: translateY(20px) scale(0.95);
        transition: transform 0.3s ease;
        overflow: hidden;
    }
    
    .gip-modal[aria-hidden="false"] .gip-modal-container {
        transform: translateY(0) scale(1);
    }
    
    /* モーダルヘッダー */
    .gip-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid #e5e5e5;
        background: #fafafa;
    }
    
    .gip-modal-header-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .gip-modal-avatar {
        width: 40px;
        height: 40px;
        background: #111111;
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .gip-modal-header-text {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    
    .gip-modal-title {
        font-size: 16px;
        font-weight: 700;
        margin: 0;
        color: #111111;
    }
    
    .gip-modal-subtitle {
        font-size: 12px;
        color: #666666;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .gip-modal-status-dot {
        width: 8px;
        height: 8px;
        background: #22c55e;
        border-radius: 50%;
        animation: gip-pulse 2s infinite;
    }
    
    @keyframes gip-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .gip-modal-close {
        width: 40px;
        height: 40px;
        background: transparent;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #666666;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .gip-modal-close:hover {
        background: #e5e5e5;
        color: #111111;
    }
    
    /* モーダルボディ */
    .gip-modal-body {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    .gip-modal-body .gip-chat {
        border: none !important;
        box-shadow: none !important;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .gip-modal-body .gip-chat-messages {
        flex: 1;
        max-height: none;
        min-height: 300px;
        overflow-y: auto;
        padding: 20px;
        background: #ffffff;
    }
    
    .gip-modal-body .gip-chat-input-area {
        padding: 16px 20px;
        border-top: 1px solid #e5e5e5;
        background: #fafafa;
    }
    
    /* タブレット最適化 */
    @media (min-width: 769px) and (max-width: 1024px) {
        .gip-modal-container,
        #gip-chat-modal .gip-modal-container {
            width: 90% !important;
            max-width: 900px !important;
            min-width: 0 !important;
            height: 85vh !important;
        }
    }
    
    /* モバイル最適化 */
    @media (max-width: 768px) {
        .gip-modal-container,
        #gip-chat-modal .gip-modal-container {
            width: 100% !important;
            height: 100% !important;
            max-width: 100% !important;
            max-height: 100% !important;
            min-width: 0 !important;
            border-radius: 0;
        }
        
        .gip-modal-body .gip-chat-messages {
            max-height: none;
            min-height: auto;
            flex: 1;
        }
        
        .gip-cta-btn--large {
            width: 100%;
            padding: 16px 24px;
            font-size: 16px;
        }
    }
    </style>
    
    <script>
    (function($) {
        'use strict';
        
        // モーダル制御
        var GIPModal = {
            $modal: null,
            isOpen: false,
            chatInitialized: false,
            modalChatInstance: null,
            
            init: function() {
                var self = this;
                self.$modal = $('#gip-chat-modal');
                
                if (!self.$modal.length) {
                    console.log('GIP Modal: Modal element not found');
                    return;
                }
                
                console.log('GIP Modal: Initializing...');
                
                // CTAボタンのクリック
                $(document).on('click', '[data-gip-modal-open]', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('GIP Modal: CTA button clicked');
                    self.open();
                });
                
                // 閉じるボタンのクリック（イベント委譲で確実に動作）
                $(document).on('click', '#gip-chat-modal .gip-modal-close, #gip-chat-modal [data-gip-modal-close]:not(.gip-modal-overlay)', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('GIP Modal: Close button clicked');
                    self.close();
                });
                
                // オーバーレイのクリック（イベント委譲で確実に動作）
                $(document).on('click', '#gip-chat-modal .gip-modal-overlay', function(e) {
                    e.preventDefault();
                    console.log('GIP Modal: Overlay clicked');
                    self.close();
                });
                
                // ESCキーで閉じる
                $(document).on('keydown.gipmodal', function(e) {
                    if (e.key === 'Escape' && self.isOpen) {
                        console.log('GIP Modal: ESC key pressed');
                        self.close();
                    }
                });
                
                // モーダル内クリックは伝播停止（閉じるボタンは除外）
                self.$modal.find('.gip-modal-container').on('click', function(e) {
                    if (!$(e.target).closest('.gip-modal-close').length) {
                        e.stopPropagation();
                    }
                });
                
                console.log('GIP Modal: Initialization complete');
            },
            
            open: function() {
                var self = this;
                
                console.log('GIP Modal: Opening...');
                
                self.$modal.attr('aria-hidden', 'false');
                $('body').css('overflow', 'hidden');
                self.isOpen = true;
                
                // チャット初期化（初回のみ）
                if (!self.chatInitialized) {
                    self.initializeModalChat();
                }
                
                // フォーカストラップ
                setTimeout(function() {
                    self.$modal.find('.gip-modal-close').focus();
                }, 100);
            },
            
            initializeModalChat: function() {
                var self = this;
                var $modalChat = self.$modal.find('.gip-chat');
                
                if (!$modalChat.length) {
                    console.error('GIP Modal: Chat container not found in modal');
                    return;
                }
                
                console.log('GIP Modal: Initializing chat...');
                
                // モーダル専用のチャットインスタンスを作成
                self.modalChatInstance = {
                    sessionId: null,
                    isLoading: false,
                    results: [],
                    allResults: [],
                    displayedCount: 0,
                    resultsPerPage: 10,
                    selectedForCompare: [],
                    canContinue: false,
                    allowInput: false,
                    messageCount: 0,
                    
                    $container: $modalChat,
                    $messages: $modalChat.find('.gip-chat-messages'),
                    $input: $modalChat.find('.gip-chat-input'),
                    $send: $modalChat.find('.gip-chat-send'),
                    $results: $modalChat.find('.gip-results'),
                    $inputArea: $modalChat.find('.gip-chat-input-area'),
                    
                    init: function() {
                        var chat = this;
                        
                        // 初期状態では入力エリアを非表示
                        chat.$inputArea.hide();
                        
                        // イベントバインド
                        chat.bindEvents();
                        
                        // セッション開始
                        chat.startSession();
                    },
                    
                    bindEvents: function() {
                        var chat = this;
                        
                        // 送信ボタン
                        chat.$send.off('click.gipmodal').on('click.gipmodal', function(e) {
                            e.preventDefault();
                            chat.sendMessage();
                        });
                        
                        // Enterキーで送信
                        chat.$input.off('keydown.gipmodal').on('keydown.gipmodal', function(e) {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                chat.sendMessage();
                            }
                        });
                        
                        // テキストエリアの自動リサイズ
                        chat.$input.off('input.gipmodal').on('input.gipmodal', function() {
                            this.style.height = 'auto';
                            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                        });
                        
                        // オプションボタン（モーダル内のみ）
                        chat.$container.off('click.gipmodal', '.gip-option-btn').on('click.gipmodal', '.gip-option-btn:not(.gip-continue-btn)', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            if (!chat.isLoading) {
                                chat.handleOptionClick($(this));
                            }
                        });
                        
                        // 続行ボタン
                        chat.$container.off('click.gipmodal', '.gip-continue-btn').on('click.gipmodal', '.gip-continue-btn', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            if (!chat.isLoading) {
                                var value = $(this).data('value') || $(this).text().trim();
                                // 結果エリアを非表示にしてトークを見やすく
                                if (chat.$results && chat.$results.is(':visible')) {
                                    chat.$results.slideUp(300);
                                }
                                chat.sendMessage(value);
                            }
                        });
                        
                        // セレクトボックス変更
                        chat.$container.off('change.gipmodal', '.gip-select').on('change.gipmodal', '.gip-select', function() {
                            var val = $(this).val();
                            if (val && !chat.isLoading) {
                                chat.sendSelection(val);
                            }
                        });
                        
                        // インライン送信ボタン
                        chat.$container.off('click.gipmodal', '.gip-inline-submit').on('click.gipmodal', '.gip-inline-submit', function(e) {
                            e.preventDefault();
                            if (chat.isLoading) return;
                            var $input = $(this).siblings('.gip-inline-input');
                            var val = $input.val().trim();
                            if (val) {
                                chat.sendSelection(val);
                                $input.val('');
                            }
                        });
                        
                        // インライン入力のEnterキー
                        chat.$container.off('keydown.gipmodal', '.gip-inline-input').on('keydown.gipmodal', '.gip-inline-input', function(e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                if (chat.isLoading) return;
                                var val = $(this).val().trim();
                                if (val) {
                                    chat.sendSelection(val);
                                    $(this).val('');
                                }
                            }
                        });
                        
                        // フィードバックボタン
                        chat.$container.off('click.gipmodal', '.gip-feedback-btn').on('click.gipmodal', '.gip-feedback-btn', function(e) {
                            e.preventDefault();
                            chat.handleFeedback($(this));
                        });
                        
                        // 詳細を見る/閉じる
                        chat.$container.off('click.gipmodal', '.gip-btn-toggle-details').on('click.gipmodal', '.gip-btn-toggle-details', function(e) {
                            e.preventDefault();
                            var $card = $(this).closest('.gip-result-card');
                            var $details = $card.find('.gip-result-details');
                            $details.toggleClass('show');
                            $(this).text($details.hasClass('show') ? '詳細を閉じる' : '詳細を見る');
                        });
                        
                        // 補助金について質問
                        chat.$container.off('click.gipmodal', '.gip-btn-ask-about').on('click.gipmodal', '.gip-btn-ask-about', function(e) {
                            e.preventDefault();
                            var title = $(this).data('title');
                            var message = '「' + title + '」について詳しく教えてください。';
                            chat.sendMessage(message);
                            
                            // メッセージエリアにスクロール
                            chat.$messages[0].scrollTop = chat.$messages[0].scrollHeight;
                        });
                        
                        // 前の質問に戻るボタン
                        chat.$container.off('click.gipmodal', '.gip-back-btn').on('click.gipmodal', '.gip-back-btn', function(e) {
                            e.preventDefault();
                            if (!chat.isLoading) {
                                chat.stepBack(1);
                            }
                        });
                        
                        // フィードバック評価ボタン
                        chat.$container.off('click.gipmodal', '.gip-feedback-rating-btn').on('click.gipmodal', '.gip-feedback-rating-btn', function(e) {
                            e.preventDefault();
                            $(this).siblings('.gip-feedback-rating-btn').removeClass('selected');
                            $(this).addClass('selected');
                        });
                        
                        // フィードバックコメント送信
                        chat.$container.off('click.gipmodal', '.gip-feedback-comment-submit').on('click.gipmodal', '.gip-feedback-comment-submit', function(e) {
                            e.preventDefault();
                            var $section = $(this).closest('.gip-feedback-comment-section');
                            var $selectedRating = $section.find('.gip-feedback-rating-btn.selected');
                            var rating = $selectedRating.length ? $selectedRating.data('rating') : '';
                            var comment = $section.find('.gip-feedback-comment-input').val().trim();
                            
                            if (!rating && !comment) {
                                alert('評価またはコメントを入力してください');
                                return;
                            }
                            
                            chat.sendSessionFeedback(rating, comment);
                            
                            // 送信完了表示
                            $section.html('<div class="gip-feedback-success"><span class="gip-feedback-success-icon">✓</span><p>フィードバックをお送りいただきありがとうございます！</p></div>');
                        });
                    },
                    
                    handleOptionClick: function($btn) {
                        var chat = this;
                        if (chat.isLoading) return;
                        
                        $btn.siblings('.gip-option-btn').removeClass('selected');
                        $btn.addClass('selected');
                        
                        setTimeout(function() {
                            var value = $btn.data('value') || $btn.text().trim();
                            chat.sendSelection(value);
                        }, 150);
                    },
                    
                    handleFeedback: function($btn) {
                        var chat = this;
                        var $card = $btn.closest('.gip-result-card');
                        var grantId = $card.data('grant-id');
                        var feedback = $btn.data('feedback');
                        
                        $card.find('.gip-feedback-btn').removeClass('selected');
                        $btn.addClass('selected');
                        
                        chat.sendFeedback(grantId, feedback);
                    },
                    
                    startSession: function() {
                        var chat = this;
                        console.log('GIP Modal Chat: Starting new session...');
                        chat.callApi({ message: '' });
                    },
                    
                    sendMessage: function(predefinedMsg) {
                        var chat = this;
                        var msg = predefinedMsg || chat.$input.val().trim();
                        
                        if (!msg || chat.isLoading) return;
                        
                        chat.$input.val('').css('height', 'auto');
                        chat.hideKeyboard();
                        chat.addMessage('user', msg);
                        chat.removeOptions();
                        
                        chat.callApi({
                            session_id: chat.sessionId,
                            message: msg
                        });
                    },
                    
                    sendSelection: function(value) {
                        var chat = this;
                        
                        if (chat.isLoading || !value) return;
                        
                        chat.hideKeyboard();
                        chat.addMessage('user', value);
                        chat.removeOptions();
                        
                        chat.callApi({
                            session_id: chat.sessionId,
                            selection: value
                        });
                    },
                    
                    removeOptions: function() {
                        var chat = this;
                        chat.$messages.find('.gip-options, .gip-select-wrap, .gip-hint, .gip-input-inline').remove();
                    },
                    
                    callApi: function(data) {
                        var chat = this;
                        
                        if (chat.isLoading) return;
                        
                        chat.isLoading = true;
                        chat.$send.prop('disabled', true);
                        chat.showTyping();
                        
                        if (typeof GIP_CHAT === 'undefined' || !GIP_CHAT.api) {
                            console.error('GIP Modal Chat: API configuration missing');
                            chat.hideTyping();
                            chat.addMessage('bot', 'システムエラーが発生しました。ページを再読み込みしてください。');
                            chat.isLoading = false;
                            chat.$send.prop('disabled', false);
                            return;
                        }
                        
                        console.log('GIP Modal Chat: API call', data);
                        
                        $.ajax({
                            url: GIP_CHAT.api + '/chat',
                            method: 'POST',
                            contentType: 'application/json',
                            headers: { 'X-WP-Nonce': GIP_CHAT.nonce },
                            data: JSON.stringify(data),
                            timeout: 60000,
                            success: function(response) {
                                console.log('GIP Modal Chat: Response', response);
                                chat.hideTyping();
                                
                                if (response && response.success) {
                                    chat.sessionId = response.session_id;
                                    chat.canContinue = response.can_continue || false;
                                    chat.allowInput = response.allow_input || false;
                                    
                                    chat.addMessage('bot', response.message);
                                    
                                    if (response.hint_important) {
                                        chat.showHintImportant(response.hint_important);
                                    }
                                    
                                    if (response.hint) {
                                        chat.showHint(response.hint);
                                    }
                                    
                                    if (response.options && response.options.length > 0) {
                                        chat.showOptions(response.options, response.option_type);
                                    }
                                    
                                    if (response.allow_input && response.option_type !== 'prefecture_select' && response.option_type !== 'municipality_select') {
                                        chat.showInlineInput();
                                    }
                                    
                                    if (response.results && response.results.length > 0) {
                                        chat.allResults = response.results;
                                        chat.displayedCount = 0;
                                        chat.selectedForCompare = [];
                                        chat.renderResults(response.show_comparison, response);
                                        
                                        if (chat.canContinue) {
                                            chat.showContinueOptions();
                                        }
                                    }
                                    
                                    chat.updateInputState();
                                } else {
                                    var errorMsg = (response && response.error) ? response.error : 'エラーが発生しました。';
                                    chat.addMessage('bot', errorMsg + '\n\nページを更新してやり直してください。');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('GIP Modal Chat: Error', status, error);
                                chat.hideTyping();
                                
                                var errorMsg = '通信エラーが発生しました。';
                                if (status === 'timeout') {
                                    errorMsg = 'リクエストがタイムアウトしました。もう一度お試しください。';
                                }
                                
                                chat.addMessage('bot', errorMsg);
                            },
                            complete: function() {
                                chat.isLoading = false;
                                chat.$send.prop('disabled', false);
                                chat.hideKeyboard();
                            }
                        });
                    },
                    
                    hideKeyboard: function() {
                        var chat = this;
                        if (document.activeElement && 
                            (document.activeElement.tagName === 'INPUT' || 
                             document.activeElement.tagName === 'TEXTAREA')) {
                            document.activeElement.blur();
                        }
                        chat.$input.blur();
                        chat.$messages.find('.gip-inline-input, .gip-select').blur();
                    },
                    
                    updateInputState: function() {
                        var chat = this;
                        
                        if (chat.canContinue || chat.allowInput) {
                            chat.$inputArea.slideDown(200);
                            chat.$input.attr('placeholder', '質問を入力してください...');
                        } else {
                            var hasOptions = chat.$messages.find('.gip-options, .gip-select-wrap').length > 0;
                            if (hasOptions) {
                                chat.$inputArea.hide();
                            }
                        }
                    },
                    
                    addMessage: function(role, text) {
                        var chat = this;
                        var avatarText = role === 'bot' ? 'AI' : 'You';
                        
                        var html = '<div class="gip-message gip-message-' + role + '">';
                        html += '<div class="gip-message-avatar">' + avatarText + '</div>';
                        html += '<div class="gip-message-content">';
                        html += '<div class="gip-message-bubble">' + chat.escapeHtml(text) + '</div>';
                        html += '</div></div>';
                        
                        chat.$messages.append(html);
                        chat.messageCount++;
                        chat.scrollToBottom();
                    },
                    
                    showHint: function(hint) {
                        var chat = this;
                        var $lastMessage = chat.$messages.find('.gip-message:last .gip-message-content');
                        var html = '<div class="gip-hint">' + chat.escapeHtml(hint) + '</div>';
                        $lastMessage.append(html);
                    },
                    
                    showHintImportant: function(hint) {
                        var chat = this;
                        var $lastMessage = chat.$messages.find('.gip-message:last .gip-message-content');
                        var html = '<div class="gip-hint-important">' + chat.escapeHtml(hint) + '</div>';
                        $lastMessage.append(html);
                    },
                    
                    showOptions: function(options, type) {
                        var chat = this;
                        var html = '';
                        var $lastMessage = chat.$messages.find('.gip-message:last .gip-message-content');
                        
                        if (type === 'prefecture_select' || type === 'municipality_select') {
                            html = '<div class="gip-select-wrap">';
                            html += '<select class="gip-select" aria-label="選択してください"><option value="">選択してください</option>';
                            for (var i = 0; i < options.length; i++) {
                                var opt = options[i];
                                html += '<option value="' + chat.escapeHtml(opt.label) + '">' + chat.escapeHtml(opt.label) + '</option>';
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
                                html += '<button type="button" class="gip-option-btn" data-value="' + chat.escapeHtml(opt.label) + '">' + chat.escapeHtml(opt.label) + '</button>';
                            }
                            html += '</div>';
                        }
                        
                        // 前の質問に戻るボタン（最初の質問以外で表示）
                        if (chat.messageCount > 1) {
                            html += '<div class="gip-back-btn-wrap">';
                            html += '<button type="button" class="gip-back-btn">';
                            html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>';
                            html += '前の質問に戻る';
                            html += '</button></div>';
                        }
                        
                        $lastMessage.append(html);
                        chat.scrollToBottom();
                    },
                    
                    showInlineInput: function() {
                        var chat = this;
                        var $lastMessage = chat.$messages.find('.gip-message:last .gip-message-content');
                        
                        if ($lastMessage.find('.gip-input-inline').length > 0) return;
                        
                        var html = '<div class="gip-input-inline">';
                        html += '<input type="text" class="gip-inline-input" placeholder="自由に入力できます..." aria-label="自由入力">';
                        html += '<button type="button" class="gip-inline-submit">送信</button>';
                        html += '</div>';
                        
                        $lastMessage.append(html);
                    },
                    
                    loadingPhrases: [
                        "考え中...",
                        "データベースを検索中...",
                        "条件を分析しています...",
                        "最適な補助金を選定中...",
                        "情報を整理しています..."
                    ],
                    loadingTimer: null,
                    
                    showTyping: function() {
                        var chat = this;
                        
                        var html = '<div class="gip-message gip-message-bot gip-message-typing-wrap">';
                        html += '<div class="gip-message-avatar">AI</div>';
                        html += '<div class="gip-message-content">';
                        html += '<div class="gip-message-typing" aria-label="入力中">';
                        html += '<div class="gip-typing-dot"></div>';
                        html += '<div class="gip-typing-dot"></div>';
                        html += '<div class="gip-typing-dot"></div>';
                        html += '</div>';
                        html += '<div class="gip-loading-text">考え中...</div>';
                        html += '</div></div>';
                        
                        chat.$messages.append(html);
                        chat.scrollToBottom();
                        
                        // テキストローテーション開始
                        var phraseIndex = 0;
                        chat.loadingTimer = setInterval(function() {
                            var $text = chat.$messages.find('.gip-loading-text');
                            if ($text.length) {
                                phraseIndex = (phraseIndex + 1) % chat.loadingPhrases.length;
                                $text.text(chat.loadingPhrases[phraseIndex]);
                            } else {
                                clearInterval(chat.loadingTimer);
                            }
                        }, 2500);
                    },
                    
                    hideTyping: function() {
                        clearInterval(this.loadingTimer);
                        this.$messages.find('.gip-message-typing-wrap').remove();
                    },
                    
                    scrollToBottom: function() {
                        var chat = this;
                        var el = chat.$messages[0];
                        if (el) {
                            setTimeout(function() {
                                el.scrollTop = el.scrollHeight;
                            }, 50);
                        }
                    },
                    
                    renderResults: function(showComparison, response) {
                        var chat = this;
                        response = response || {};
                        
                        var mainResults = response.main_results || chat.allResults.slice(0, 5);
                        var subResults = response.sub_results || chat.allResults.slice(5, 10);
                        var remainingResults = chat.allResults.slice(10);
                        
                        chat.displayedCount = mainResults.length + subResults.length;
                        
                        // 結果サマリーパネル
                        var html = '<div class="gip-results-summary">';
                        html += '<div class="gip-results-summary-header">';
                        html += '<div class="gip-results-summary-icon">';
                        html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>';
                        html += '</div>';
                        html += '<div>';
                        html += '<div class="gip-results-summary-title">診断完了</div>';
                        html += '<div class="gip-results-summary-count">' + chat.allResults.length + '件の補助金が見つかりました</div>';
                        html += '</div>';
                        html += '</div>';
                        html += '<div class="gip-results-summary-info">';
                        var totalAmount = 0;
                        var openCount = 0;
                        for (var k = 0; k < Math.min(5, chat.allResults.length); k++) {
                            var amt = chat.allResults[k].max_amount_numeric || 0;
                            if (amt > 0) totalAmount += amt;
                            if (chat.allResults[k].application_status === 'open' || !chat.allResults[k].application_status) openCount++;
                        }
                        var amountDisplay = totalAmount > 100000000 ? Math.round(totalAmount / 100000000) + '億円+' : (totalAmount > 10000 ? Math.round(totalAmount / 10000) + '万円+' : '要確認');
                        html += '<div class="gip-summary-stat"><div class="gip-summary-stat-value">' + chat.allResults.length + '</div><div class="gip-summary-stat-label">該当件数</div></div>';
                        html += '<div class="gip-summary-stat"><div class="gip-summary-stat-value">' + openCount + '</div><div class="gip-summary-stat-label">受付中</div></div>';
                        html += '<div class="gip-summary-stat"><div class="gip-summary-stat-value">' + amountDisplay + '</div><div class="gip-summary-stat-label">補助金額目安</div></div>';
                        html += '</div>';
                        html += '</div>';
                        
                        // フィードバックパネル（コメント入力欄付き）
                        html += '<div class="gip-results-feedback-panel">';
                        html += '<div class="gip-results-feedback-bar">';
                        html += '<span class="gip-results-feedback-text">この診断結果はいかがでしたか？</span>';
                        html += '<div class="gip-results-feedback-btns">';
                        html += '<button type="button" class="gip-results-fb-btn positive" data-feedback="positive">';
                        html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3zM7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3"/></svg>';
                        html += '参考になった';
                        html += '</button>';
                        html += '<button type="button" class="gip-results-fb-btn negative" data-feedback="negative">';
                        html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 15v4a3 3 0 003 3l4-9V2H5.72a2 2 0 00-2 1.7l-1.38 9a2 2 0 002 2.3zm7-13h2.67A2.31 2.31 0 0122 4v7a2.31 2.31 0 01-2.33 2H17"/></svg>';
                        html += '期待と違った';
                        html += '</button>';
                        html += '</div>';
                        html += '</div>';
                        
                        // フィードバックコメント入力欄（初期非表示）
                        html += '<div class="gip-feedback-comment-section" style="display:none;">';
                        html += '<div class="gip-feedback-comment-header">';
                        html += '<span class="gip-feedback-comment-label">ご意見・改善点をお聞かせください（任意）</span>';
                        html += '</div>';
                        html += '<textarea class="gip-feedback-comment-input" rows="3" placeholder="診断結果について気になった点や、改善のご要望があればお聞かせください..."></textarea>';
                        html += '<div class="gip-feedback-submit-row">';
                        html += '<button type="button" class="gip-feedback-submit-btn">送信する</button>';
                        html += '<button type="button" class="gip-feedback-skip-btn">スキップ</button>';
                        html += '</div>';
                        html += '</div>';
                        html += '<div class="gip-feedback-thanks" style="display:none;">';
                        html += '<span class="gip-feedback-thanks-text">ご協力ありがとうございました！</span>';
                        html += '</div>';
                        html += '</div>';
                        
                        // 結果ヘッダー（トグル機能付き）
                        html += '<div class="gip-results-header" title="タップして開閉">';
                        html += '<div style="display:flex; justify-content:space-between; width:100%; align-items:center;">';
                        html += '<div>';
                        html += '<h3 class="gip-results-title">マッチした補助金 (' + chat.allResults.length + '件)</h3>';
                        html += '<div style="font-size:12px; color:var(--gip-gray-500);">タップして結果を最小化/展開</div>';
                        html += '</div>';
                        html += '<div class="gip-results-toggle-icon">';
                        html += '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 15l-6-6-6 6"/></svg>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                        
                        // メイン結果
                        if (mainResults.length > 0) {
                            html += '<div class="gip-results-main">';
                            html += '<h4 class="gip-results-section-title">おすすめの補助金</h4>';
                            html += '<div class="gip-results-grid gip-results-grid-main">';
                            for (var i = 0; i < mainResults.length; i++) {
                                html += chat.renderResultCard(mainResults[i], i);
                            }
                            html += '</div></div>';
                        }
                        
                        // サブ結果
                        if (subResults.length > 0) {
                            html += '<div class="gip-results-sub">';
                            html += '<h4 class="gip-results-section-title">他にもこのような補助金があります</h4>';
                            html += '<div class="gip-results-grid gip-results-grid-sub">';
                            for (var j = 0; j < subResults.length; j++) {
                                html += chat.renderSubResultCard(subResults[j], j + 5);
                            }
                            html += '</div></div>';
                        }
                        
                        // 再調整パネル
                        html += '<div class="gip-readjust-panel">';
                        html += '<div class="gip-readjust-header">';
                        html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
                        html += '<path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>';
                        html += '</svg>';
                        html += '<span>結果が期待と異なる場合</span>';
                        html += '</div>';
                        html += '<div class="gip-readjust-options">';
                        html += '<button type="button" class="gip-readjust-btn" data-adjust="expand_area">';
                        html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>';
                        html += '地域を広げる';
                        html += '</button>';
                        html += '<button type="button" class="gip-readjust-btn" data-adjust="national">';
                        html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                        html += '全国で検索';
                        html += '</button>';
                        html += '<button type="button" class="gip-readjust-btn" data-adjust="change_purpose">';
                        html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
                        html += '目的を変更';
                        html += '</button>';
                        html += '<button type="button" class="gip-readjust-btn" data-adjust="restart">';
                        html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>';
                        html += '最初から';
                        html += '</button>';
                        html += '</div>';
                        html += '</div>';
                        
                        chat.$results.html(html).slideDown(300);
                        
                        // イベントバインド
                        chat.bindModalResultsEvents();
                    },
                    
                    // サブ結果カード
                    renderSubResultCard: function(r, index) {
                        var chat = this;
                        
                        var html = '<div class="gip-result-card gip-result-card-sub" data-grant-id="' + r.grant_id + '">';
                        html += '<div class="gip-result-sub-content">';
                        html += '<div class="gip-result-sub-rank">' + (index + 1) + '</div>';
                        html += '<div class="gip-result-sub-info">';
                        html += '<h5 class="gip-result-sub-title">' + chat.escapeHtml(r.title) + '</h5>';
                        html += '<div class="gip-result-sub-meta">';
                        if (r.amount_display || r.max_amount) {
                            html += '<span class="gip-result-sub-amount">' + chat.escapeHtml(r.amount_display || r.max_amount) + '</span>';
                        }
                        if (r.score) {
                            html += '<span class="gip-result-sub-score">マッチ度 ' + r.score + '点</span>';
                        }
                        html += '</div></div>';
                        html += '<button type="button" class="gip-btn-ask-about gip-sub-ask-btn" data-grant-id="' + r.grant_id + '" data-title="' + chat.escapeHtml(r.title) + '">詳しく</button>';
                        html += '</div></div>';
                        
                        return html;
                    },
                    
                    // モーダル版の結果イベントバインド
                    bindModalResultsEvents: function() {
                        var chat = this;
                        
                        // 結果エリアの開閉トグル
                        chat.$results.off('click.giptoggle').on('click.giptoggle', '.gip-results-header', function(e) {
                            if ($(e.target).closest('.gip-results-close-btn, a, button').length) return;
                            var $results = $(this).closest('.gip-results');
                            $results.toggleClass('minimized');
                        });
                        
                        // 現在のフィードバックタイプを保存
                        var currentFeedbackType = null;
                        
                        // フィードバックバーのクリック
                        chat.$results.off('click.gipfb').on('click.gipfb', '.gip-results-fb-btn', function() {
                            var feedback = $(this).data('feedback');
                            currentFeedbackType = feedback;
                            $(this).addClass('selected').siblings('.gip-results-fb-btn').removeClass('selected');
                            
                            // 基本フィードバック送信
                            chat.sendFeedback(0, feedback === 'positive' ? 'positive' : 'negative');
                            
                            var msg = feedback === 'positive' ? 'ありがとうございます！' : 'ご意見をありがとうございます。';
                            $(this).closest('.gip-results-feedback-bar').find('.gip-results-feedback-text').text(msg);
                            
                            // コメント入力欄を表示
                            var $panel = $(this).closest('.gip-results-feedback-panel');
                            $panel.find('.gip-feedback-comment-section').slideDown(200);
                        });
                        
                        // フィードバックコメント送信
                        chat.$results.off('click.gipfbsubmit').on('click.gipfbsubmit', '.gip-feedback-submit-btn', function() {
                            var $panel = $(this).closest('.gip-results-feedback-panel');
                            var comment = $panel.find('.gip-feedback-comment-input').val().trim();
                            
                            if (comment) {
                                chat.sendDetailedFeedback({
                                    feedbackType: currentFeedbackType === 'positive' ? 'helpful' : 'not_helpful',
                                    rating: currentFeedbackType === 'positive' ? 4 : 2,
                                    comment: comment
                                });
                            }
                            
                            $panel.find('.gip-feedback-comment-section').slideUp(200);
                            $panel.find('.gip-feedback-thanks').slideDown(200);
                        });
                        
                        // フィードバックスキップ
                        chat.$results.off('click.gipfbskip').on('click.gipfbskip', '.gip-feedback-skip-btn', function() {
                            var $panel = $(this).closest('.gip-results-feedback-panel');
                            $panel.find('.gip-feedback-comment-section').slideUp(200);
                            $panel.find('.gip-feedback-thanks').slideDown(200);
                        });
                        
                        // 再調整ボタン
                        chat.$results.off('click.gipreadj').on('click.gipreadj', '.gip-readjust-btn', function() {
                            var adjustType = $(this).data('adjust');
                            
                            // 結果エリアを非表示にしてトークを見やすく
                            if (chat.$results && chat.$results.is(':visible')) {
                                chat.$results.slideUp(300);
                            }
                            
                            if (adjustType === 'change_purpose') {
                                var newPurpose = prompt('新しい目的を入力してください：');
                                if (newPurpose) {
                                    chat.readjust('purpose', newPurpose);
                                }
                            } else if (adjustType === 'restart') {
                                chat.stepBack(99);
                            } else {
                                chat.readjust(adjustType, '');
                            }
                        });
                    },
                    
                    // 詳細フィードバック送信
                    sendDetailedFeedback: function(feedbackData) {
                        var chat = this;
                        
                        if (!chat.sessionId) return;
                        
                        $.ajax({
                            url: GIP_CHAT.api + '/feedback-detailed',
                            method: 'POST',
                            contentType: 'application/json',
                            headers: { 'X-WP-Nonce': GIP_CHAT.nonce },
                            data: JSON.stringify({
                                session_id: chat.sessionId,
                                grant_id: feedbackData.grantId || 0,
                                feedback_type: feedbackData.feedbackType,
                                rating: feedbackData.rating || 0,
                                comment: feedbackData.comment || '',
                                suggestion: feedbackData.suggestion || '',
                                email: feedbackData.email || ''
                            }),
                            success: function(response) {
                                console.log('GIP Modal: Detailed feedback sent', response);
                                if (response.success) {
                                    chat.showFeedbackSuccess();
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('GIP Modal: Detailed feedback error', status, error);
                            }
                        });
                    },
                    
                    // フィードバックモーダル表示
                    showFeedbackModal: function(grantId) {
                        var chat = this;
                        
                        var html = '<div class="gip-feedback-modal-overlay">';
                        html += '<div class="gip-feedback-modal">';
                        html += '<div class="gip-feedback-modal-header">';
                        html += '<span class="gip-feedback-modal-title">診断結果へのフィードバック</span>';
                        html += '<button type="button" class="gip-feedback-modal-close">&times;</button>';
                        html += '</div>';
                        html += '<div class="gip-feedback-modal-body">';
                        
                        html += '<div class="gip-feedback-section">';
                        html += '<label class="gip-feedback-section-label">診断結果の満足度</label>';
                        html += '<div class="gip-rating-stars" data-rating="0">';
                        for (var i = 1; i <= 5; i++) {
                            html += '<button type="button" class="gip-star-btn" data-rating="' + i + '">';
                            html += '<svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                            html += '</button>';
                        }
                        html += '</div>';
                        html += '</div>';
                        
                        html += '<div class="gip-feedback-section">';
                        html += '<label class="gip-feedback-section-label">フィードバックの種類</label>';
                        html += '<div class="gip-feedback-types">';
                        html += '<button type="button" class="gip-feedback-type-btn" data-type="helpful">参考になった</button>';
                        html += '<button type="button" class="gip-feedback-type-btn" data-type="not_matched">条件に合わない</button>';
                        html += '<button type="button" class="gip-feedback-type-btn" data-type="more_info">もっと情報がほしい</button>';
                        html += '<button type="button" class="gip-feedback-type-btn" data-type="other">その他</button>';
                        html += '</div>';
                        html += '</div>';
                        
                        html += '<div class="gip-feedback-section">';
                        html += '<label class="gip-feedback-section-label">コメント・改善提案</label>';
                        html += '<textarea class="gip-feedback-textarea" placeholder="診断結果についてのご意見や、改善してほしい点があればお聞かせください..."></textarea>';
                        html += '</div>';
                        
                        html += '<div class="gip-feedback-section">';
                        html += '<label class="gip-feedback-section-label">メールアドレス（任意）</label>';
                        html += '<input type="email" class="gip-feedback-email" placeholder="example@email.com">';
                        html += '<div class="gip-feedback-email-note">回答が必要な場合はご入力ください</div>';
                        html += '</div>';
                        
                        html += '<button type="button" class="gip-feedback-submit" data-grant-id="' + (grantId || 0) + '">フィードバックを送信</button>';
                        html += '</div></div></div>';
                        
                        $('body').append(html);
                        
                        setTimeout(function() {
                            $('.gip-feedback-modal-overlay').addClass('active');
                        }, 10);
                        
                        chat.bindFeedbackModalEvents();
                    },
                    
                    bindFeedbackModalEvents: function() {
                        var chat = this;
                        
                        $(document).off('click.gipfbmodal2').on('click.gipfbmodal2', '.gip-feedback-modal-close, .gip-feedback-modal-overlay', function(e) {
                            if (e.target === this) {
                                chat.closeFeedbackModal();
                            }
                        });
                        
                        $(document).off('click.gipfbstar').on('click.gipfbstar', '.gip-star-btn', function() {
                            var rating = $(this).data('rating');
                            var $container = $(this).closest('.gip-rating-stars');
                            $container.data('rating', rating);
                            $container.find('.gip-star-btn').removeClass('active');
                            $container.find('.gip-star-btn').each(function() {
                                if ($(this).data('rating') <= rating) {
                                    $(this).addClass('active');
                                }
                            });
                        });
                        
                        $(document).off('click.gipfbtype').on('click.gipfbtype', '.gip-feedback-type-btn', function() {
                            $('.gip-feedback-type-btn').removeClass('selected');
                            $(this).addClass('selected');
                        });
                        
                        $(document).off('click.gipfbsubmit').on('click.gipfbsubmit', '.gip-feedback-submit', function() {
                            var $modal = $('.gip-feedback-modal');
                            var rating = $modal.find('.gip-rating-stars').data('rating') || 0;
                            var feedbackType = $modal.find('.gip-feedback-type-btn.selected').data('type') || 'general';
                            var comment = $modal.find('.gip-feedback-textarea').val().trim();
                            var email = $modal.find('.gip-feedback-email').val().trim();
                            var grantId = $(this).data('grant-id');
                            
                            if (rating === 0 && !feedbackType && !comment) {
                                alert('評価またはコメントを入力してください');
                                return;
                            }
                            
                            $(this).prop('disabled', true).text('送信中...');
                            
                            chat.sendDetailedFeedback({
                                grantId: grantId,
                                feedbackType: feedbackType,
                                rating: rating,
                                comment: comment,
                                email: email
                            });
                        });
                    },
                    
                    closeFeedbackModal: function() {
                        var $overlay = $('.gip-feedback-modal-overlay');
                        $overlay.removeClass('active');
                        setTimeout(function() {
                            $overlay.remove();
                        }, 300);
                        $(document).off('click.gipfbmodal2 click.gipfbstar click.gipfbtype click.gipfbsubmit');
                    },
                    
                    showFeedbackSuccess: function() {
                        var chat = this;
                        var $body = $('.gip-feedback-modal-body');
                        
                        $body.html(
                            '<div class="gip-feedback-success">' +
                            '<div class="gip-feedback-success-icon">' +
                            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                            '<path d="M20 6L9 17l-5-5"/>' +
                            '</svg>' +
                            '</div>' +
                            '<div class="gip-feedback-success-title">フィードバックを受け付けました</div>' +
                            '<div class="gip-feedback-success-text">貴重なご意見をありがとうございます。<br>サービス改善に活用させていただきます。</div>' +
                            '</div>'
                        );
                        
                        setTimeout(function() {
                            chat.closeFeedbackModal();
                        }, 2500);
                    },
                    
                    // 戻る機能
                    stepBack: function(stepsBack) {
                        var chat = this;
                        stepsBack = stepsBack || 1;
                        
                        if (!chat.sessionId) return;
                        
                        $.ajax({
                            url: GIP_CHAT.api + '/step-back',
                            method: 'POST',
                            contentType: 'application/json',
                            headers: { 'X-WP-Nonce': GIP_CHAT.nonce },
                            data: JSON.stringify({
                                session_id: chat.sessionId,
                                steps_back: stepsBack
                            }),
                            success: function(response) {
                                if (response.success) {
                                    chat.addMessage('bot', response.message);
                                    if (response.restart) {
                                        chat.startSession();
                                    }
                                }
                            }
                        });
                    },
                    
                    // 再調整機能
                    readjust: function(adjustType, newValue) {
                        var chat = this;
                        
                        if (!chat.sessionId || !adjustType) return;
                        
                        chat.isLoading = true;
                        chat.showTyping();
                        
                        $.ajax({
                            url: GIP_CHAT.api + '/readjust',
                            method: 'POST',
                            contentType: 'application/json',
                            headers: { 'X-WP-Nonce': GIP_CHAT.nonce },
                            data: JSON.stringify({
                                session_id: chat.sessionId,
                                adjust_type: adjustType,
                                new_value: newValue || ''
                            }),
                            success: function(response) {
                                chat.hideTyping();
                                
                                if (response.success) {
                                    chat.addMessage('bot', response.message);
                                    
                                    if (response.results && response.results.length > 0) {
                                        chat.allResults = response.results;
                                        chat.displayedCount = 0;
                                        chat.renderResults(response.show_comparison, response);
                                    }
                                }
                            },
                            error: function() {
                                chat.hideTyping();
                                chat.addMessage('bot', '再検索中にエラーが発生しました。');
                            },
                            complete: function() {
                                chat.isLoading = false;
                            }
                        });
                    },
                    
                    renderResultCard: function(r, index) {
                        var chat = this;
                        var rankClass = '';
                        
                        if (index === 0) rankClass = ' gip-result-rank-1';
                        else if (index === 1) rankClass = ' gip-result-rank-2';
                        else if (index === 2) rankClass = ' gip-result-rank-3';
                        
                        var highlightClass = index < 3 ? ' highlight' : '';
                        
                        var html = '<div class="gip-result-card' + highlightClass + '" data-grant-id="' + r.grant_id + '">';
                        html += '<div class="gip-result-header">';
                        html += '<div class="gip-result-rank' + rankClass + '">' + (index + 1) + '</div>';
                        html += '<div class="gip-result-info">';
                        html += '<h4 class="gip-result-title">' + chat.escapeHtml(r.title) + '</h4>';
                        
                        // スコアバッジ
                        if (r.score) {
                            html += '<div class="gip-result-score-badge">マッチ度 ' + r.score + '点</div>';
                        }
                        html += '</div></div>';
                        
                        html += '<div class="gip-result-body">';
                        if (r.reason) {
                            html += '<div class="gip-result-reason">' + chat.escapeHtml(r.reason) + '</div>';
                        }
                        
                        // メタ情報
                        html += '<div class="gip-result-meta">';
                        var amountDisplay = r.amount_display || r.max_amount;
                        if (amountDisplay) {
                            html += '<div class="gip-result-meta-item">';
                            html += '<span class="gip-result-meta-label">補助金額</span>';
                            html += '<span class="gip-result-meta-value">' + chat.escapeHtml(amountDisplay) + '</span>';
                            html += '</div>';
                        }
                        if (r.deadline_display || r.deadline) {
                            html += '<div class="gip-result-meta-item">';
                            html += '<span class="gip-result-meta-label">締切</span>';
                            html += '<span class="gip-result-meta-value">' + chat.escapeHtml(r.deadline_display || r.deadline) + '</span>';
                            html += '</div>';
                        }
                        if (r.organization) {
                            html += '<div class="gip-result-meta-item">';
                            html += '<span class="gip-result-meta-label">実施機関</span>';
                            html += '<span class="gip-result-meta-value">' + chat.escapeHtml(r.organization) + '</span>';
                            html += '</div>';
                        }
                        html += '</div>';
                        
                        // AI要約または抜粋の表示
                        if (r.ai_summary) {
                            html += '<div class="gip-result-ai-summary">';
                            html += '<span class="gip-result-ai-summary-label">✨ AI要約</span>';
                            html += chat.escapeHtml(r.ai_summary);
                            html += '</div>';
                        } else if (r.excerpt) {
                            html += '<div class="gip-result-ai-summary">';
                            html += '<span class="gip-result-ai-summary-label">📋 概要</span>';
                            html += chat.escapeHtml(r.excerpt);
                            html += '</div>';
                        }
                        
                        // アクション
                        html += '<div class="gip-result-actions">';
                        html += '<a href="' + chat.escapeHtml(r.url) + '" class="gip-result-btn gip-result-btn-primary" target="_blank" rel="noopener">詳細ページを見る →</a>';
                        html += '<button type="button" class="gip-result-btn gip-result-btn-secondary gip-btn-ask-about" data-grant-id="' + r.grant_id + '" data-title="' + chat.escapeHtml(r.title) + '">この補助金について質問</button>';
                        html += '</div>';
                        
                        // 個別フィードバック
                        html += '<div class="gip-result-feedback">';
                        html += '<span class="gip-result-feedback-label">この結果は？</span>';
                        html += '<button type="button" class="gip-feedback-btn" data-feedback="positive" data-grant-id="' + r.grant_id + '">';
                        html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-right:4px;"><path d="M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3zM7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3"/></svg>';
                        html += 'はい</button>';
                        html += '<button type="button" class="gip-feedback-btn" data-feedback="negative" data-grant-id="' + r.grant_id + '">';
                        html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" style="margin-right:4px;"><path d="M10 15v4a3 3 0 003 3l4-9V2H5.72a2 2 0 00-2 1.7l-1.38 9a2 2 0 002 2.3zm7-13h2.67A2.31 2.31 0 0122 4v7a2.31 2.31 0 01-2.33 2H17"/></svg>';
                        html += 'いいえ</button>';
                        html += '</div>';
                        
                        html += '</div></div>';
                        
                        return html;
                    },
                    
                    showContinueOptions: function() {
                        var chat = this;
                        
                        // フィードバックコメントセクション
                        var feedbackHtml = '<div class="gip-feedback-comment-section">';
                        feedbackHtml += '<div class="gip-feedback-comment-header">';
                        feedbackHtml += '<span class="gip-feedback-comment-icon">💬</span>';
                        feedbackHtml += '<span class="gip-feedback-comment-title">診断結果へのフィードバック</span>';
                        feedbackHtml += '</div>';
                        feedbackHtml += '<p class="gip-feedback-comment-desc">診断結果はいかがでしたか？ご意見をお聞かせください。</p>';
                        feedbackHtml += '<div class="gip-feedback-rating">';
                        feedbackHtml += '<button type="button" class="gip-feedback-rating-btn" data-rating="satisfied"><span class="gip-rating-icon">😊</span><span>満足</span></button>';
                        feedbackHtml += '<button type="button" class="gip-feedback-rating-btn" data-rating="neutral"><span class="gip-rating-icon">😐</span><span>普通</span></button>';
                        feedbackHtml += '<button type="button" class="gip-feedback-rating-btn" data-rating="unsatisfied"><span class="gip-rating-icon">😞</span><span>不満</span></button>';
                        feedbackHtml += '</div>';
                        feedbackHtml += '<textarea class="gip-feedback-comment-input" placeholder="ご意見・ご要望があればお聞かせください（任意）" rows="3"></textarea>';
                        feedbackHtml += '<button type="button" class="gip-feedback-comment-submit">フィードバックを送信</button>';
                        feedbackHtml += '<p class="gip-feedback-comment-note">※ いただいたご意見はサービス改善に活用させていただきます</p>';
                        feedbackHtml += '</div>';
                        
                        chat.$results.append(feedbackHtml);
                        
                        // 続きのオプション
                        var html = '<div class="gip-continue-chat">';
                        html += '<p class="gip-continue-title">さらに詳しく知りたいことはありますか?</p>';
                        html += '<div class="gip-continue-options">';
                        html += '<button type="button" class="gip-option-btn gip-continue-btn" data-value="1位の補助金について詳しく教えてください">1位の補助金について詳しく</button>';
                        html += '<button type="button" class="gip-option-btn gip-continue-btn" data-value="申請方法を教えてください">申請方法を教えて</button>';
                        html += '<button type="button" class="gip-option-btn gip-continue-btn" data-value="必要書類を教えてください">必要書類を教えて</button>';
                        html += '<button type="button" class="gip-option-btn gip-continue-btn" data-value="条件を変えて他の補助金も探したい">他の補助金も探す</button>';
                        html += '</div></div>';
                        
                        chat.$results.append(html);
                    },
                    
                    sendFeedback: function(grantId, feedback) {
                        var chat = this;
                        
                        if (!chat.sessionId || !grantId) return;
                        
                        $.ajax({
                            url: GIP_CHAT.api + '/feedback',
                            method: 'POST',
                            contentType: 'application/json',
                            headers: { 'X-WP-Nonce': GIP_CHAT.nonce },
                            data: JSON.stringify({
                                session_id: chat.sessionId,
                                grant_id: grantId,
                                feedback: feedback
                            }),
                            success: function(response) {
                                console.log('GIP Modal Chat: Feedback sent', response);
                            }
                        });
                    },
                    
                    // セッション全体へのフィードバック（評価・コメント）
                    sendSessionFeedback: function(rating, comment) {
                        var chat = this;
                        
                        if (!chat.sessionId) {
                            console.warn('GIP Modal Chat: No session ID for feedback');
                            return;
                        }
                        
                        console.log('GIP Modal Chat: Sending session feedback', { rating: rating, comment: comment });
                        
                        $.ajax({
                            url: GIP_CHAT.api + '/session-feedback',
                            method: 'POST',
                            contentType: 'application/json',
                            headers: { 'X-WP-Nonce': GIP_CHAT.nonce },
                            data: JSON.stringify({
                                session_id: chat.sessionId,
                                rating: rating,
                                comment: comment
                            }),
                            success: function(response) {
                                console.log('GIP Modal Chat: Session feedback sent', response);
                            },
                            error: function(xhr, status, error) {
                                console.error('GIP Modal Chat: Session feedback error', error);
                            }
                        });
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
                
                // チャット初期化
                self.modalChatInstance.init();
                self.chatInitialized = true;
                
                console.log('GIP Modal: Chat initialized');
            },
            
            close: function() {
                var self = this;
                
                self.$modal.attr('aria-hidden', 'true');
                $('body').css('overflow', '');
                self.isOpen = false;
            }
        };
        
        // DOM Ready
        $(function() {
            GIPModal.init();
        });
        
        window.GIPModal = GIPModal;
        
    })(jQuery);
    </script>
    <?php
    return ob_get_clean();
}

/**
 * フッターにモーダルを自動出力
 */
add_action('wp_footer', 'gip_output_modal_in_footer', 99);

function gip_output_modal_in_footer() {
    // ショートコードが存在する場合のみ
    if (shortcode_exists('gip_chat') || shortcode_exists('gip_cta_button')) {
        echo do_shortcode('[gip_chat_modal]');
    }
}