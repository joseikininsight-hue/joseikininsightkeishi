<?php
/**
 * Grant Insight Perfect - Functions File (Consolidated & Clean Edition)
 * 
 * Simplified structure with consolidated files in single /inc/ directory
 * - Removed unused code and duplicate functionality
 * - Merged related files for better organization
 * - Eliminated folder over-organization
 * 
 * @package Grant_Insight_Perfect
 * @version 11.0.2 (SEO Duplicate Meta Fix)
 * 
 * Changelog v11.0.2:
 * - Disabled gi_add_seo_meta_tags to prevent duplicate meta tags (header.php handles this)
 * - Disabled gi_inject_inline_cta to prevent content flow interruption
 * - Kept remove_duplicate_sections_from_content active for duplicate section removal
 * - Cleaned up commented code and improved documentation
 *
 * Changelog v10.0.0:
 * - Implemented Yahoo! JAPAN-style tabbed grant browsing system
 * - Added 4 tabs: 締切間近(30日以内), おすすめ, 新着, あなたにおすすめ
 * - Added cookie-based viewing history tracking
 * - Created reusable grant card template (template-parts/grant/card.php)
 * - Added personalized recommendations based on browsing history
 * - Replaced separate grant sections with unified tabbed interface
 * - Current theme styling (black/white, Yahoo! functionality)
 *
 * Previous v9.2.1:
 * - Fixed Jetpack duplicate store registration errors
 * - Added React key prop warning fixes
 * - Fixed Gutenberg block editor JavaScript errors
 * - Added customizer 500 error prevention
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// テーマバージョン定数
if (!defined('GI_THEME_VERSION')) {
    define('GI_THEME_VERSION', '11.0.2');
}
if (!defined('GI_THEME_PREFIX')) {
    define('GI_THEME_PREFIX', 'gi_');
}

// 🔧 MEMORY OPTIMIZATION
if (is_admin() && !wp_doing_ajax()) {
    @ini_set('memory_limit', '256M');
    
    add_action('init', function() {
        if (!defined('WP_POST_REVISIONS')) {
            define('WP_POST_REVISIONS', 3);
        }
        
        if (!defined('AUTOSAVE_INTERVAL')) {
            define('AUTOSAVE_INTERVAL', 300);
        }
    }, 1);
}

/**
 * 🔧 JavaScript Error Handling & Optimization
 */

// Dequeue problematic Jetpack scripts
add_action('admin_enqueue_scripts', 'gi_fix_jetpack_conflicts', 100);
function gi_fix_jetpack_conflicts() {
    if (class_exists('Jetpack')) {
        wp_deregister_script('jetpack-ai-logo-generator');
        wp_deregister_script('jetpack-modules-store');
    }
}

// Fix Gutenberg block editor JavaScript errors
add_action('enqueue_block_editor_assets', 'gi_fix_block_editor_errors', 100);
function gi_fix_block_editor_errors() {
    wp_add_inline_script('wp-blocks', '
        (function() {
            var originalRegisterStore = wp.data && wp.data.registerStore;
            if (originalRegisterStore) {
                wp.data.registerStore = function(storeName, options) {
                    try {
                        return originalRegisterStore.call(wp.data, storeName, options);
                    } catch (error) {
                        if (!error.message.includes("already registered")) {
                            console.error("Store registration error:", error);
                        }
                        return wp.data.select(storeName);
                    }
                };
            }
        })();
    ', 'before');
}

// Disable Jetpack modules that cause conflicts
add_filter('jetpack_get_available_modules', 'gi_disable_problematic_jetpack_modules', 999);
function gi_disable_problematic_jetpack_modules($modules) {
    $problematic_modules = array('photon', 'photon-cdn', 'videopress');
    foreach ($problematic_modules as $module) {
        if (isset($modules[$module])) {
            unset($modules[$module]);
        }
    }
    return $modules;
}

// Fix customizer 500 error
add_action('customize_register', 'gi_fix_customizer_errors', 999);
function gi_fix_customizer_errors($wp_customize) {
    $wp_customize->remove_section('custom_css');
}

// Add error logging for JavaScript errors (debug mode only)
add_action('wp_footer', 'gi_add_js_error_logging');
add_action('admin_footer', 'gi_add_js_error_logging');
function gi_add_js_error_logging() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        ?>
        <script>
        window.addEventListener('error', function(e) {
            if (console && console.error) {
                console.error('JS Error caught:', e.message, 'at', e.filename + ':' + e.lineno);
            }
        });
        
        window.addEventListener('unhandledrejection', function(e) {
            if (console && console.error) {
                console.error('Unhandled Promise Rejection:', e.reason);
            }
        });
        </script>
        <?php
    }
}

/**
 * Purpose Page Rewrite Rules
 */
add_action('init', 'gi_register_purpose_rewrite_rules');
function gi_register_purpose_rewrite_rules() {
    add_rewrite_rule(
        '^purpose/([^/]+)/?$',
        'index.php?gi_purpose=$matches[1]',
        'top'
    );
}

// AUTO-FLUSH: Rewrite rules for purpose pages
add_action('init', function() {
    if (get_option('gi_purpose_rewrite_flushed') !== 'yes') {
        flush_rewrite_rules(false);
        update_option('gi_purpose_rewrite_flushed', 'yes');
    }
}, 99);

// Register purpose query var
add_filter('query_vars', 'gi_register_purpose_query_var');
function gi_register_purpose_query_var($vars) {
    $vars[] = 'gi_purpose';
    return $vars;
}

// Template redirect for purpose pages
add_action('template_redirect', 'gi_purpose_template_redirect');
function gi_purpose_template_redirect() {
    $purpose_slug = get_query_var('gi_purpose');
    if ($purpose_slug) {
        $template = locate_template('page-purpose.php');
        if ($template) {
            include $template;
            exit;
        }
    }
}

/**
 * Get purpose-to-category mapping
 */
function gi_get_purpose_category_mapping() {
    static $mapping = null;
    
    if ($mapping !== null) {
        return $mapping;
    }
    
    $mapping = array(
        // ===== 8 Main Purposes =====
        'equipment' => array(
            '設備投資', 'ものづくり・新商品開発', 'IT導入・DX', 
            '生産性向上・業務効率化', '防犯・防災・BCP', 
            '省エネ・再エネ', '医療・福祉', '観光・インバウンド', 
            '農業・林業・漁業'
        ),
        'training' => array(
            '人材育成・人材確保', '雇用維持・促進', 
            '働き方改革・待遇改善', '女性活躍・多様性', 
            '若者・学生支援', 'シニア・障害者支援', 
            'IT導入・DX', '生産性向上・業務効率化'
        ),
        'sales' => array(
            '販路拡大', '事業拡大', '新規事業・第二創業', 
            'ものづくり・新商品開発', '広告・マーケティング', 
            'EC・オンライン販売', '展示会・商談会', 
            '海外展開', '観光・インバウンド'
        ),
        'startup' => array(
            '創業・スタートアップ', '新規事業・第二創業', 
            '事業拡大', '販路拡大', '資金調達', 
            'IT導入・DX', '人材育成・人材確保', 
            '起業・独立'
        ),
        'digital' => array(
            'IT導入・DX', '生産性向上・業務効率化', 
            'EC・オンライン販売', '働き方改革・待遇改善', 
            'クラウド・SaaS', 'セキュリティ', 
            'AI・IoT・先端技術', '設備投資'
        ),
        'funding' => array(
            '資金調達', '運転資金', '設備投資', 
            '事業拡大', '創業・スタートアップ', 
            '事業再構築・転換', '新規事業・第二創業'
        ),
        'environment' => array(
            '省エネ・再エネ', '環境保護・脱炭素', 
            '設備投資', '生産性向上・業務効率化', 
            'SDGs', '循環型経済', '農業・林業・漁業'
        ),
        'global' => array(
            '海外展開', '輸出促進', '観光・インバウンド', 
            '販路拡大', 'クールジャパン・コンテンツ', 
            '国際交流', '展示会・商談会'
        ),
        
        // ===== 5 Additional Purposes =====
        'succession' => array(
            '事業承継', 'M&A', '経営改善', 
            '事業再構築・転換', '後継者育成', 
            '人材育成・人材確保'
        ),
        'rnd' => array(
            '研究開発', 'AI・IoT・先端技術', 
            'ものづくり・新商品開発', '設備投資', 
            '産学連携', 'イノベーション', 
            '特許・知的財産'
        ),
        'housing' => array(
            '住宅支援', 'リフォーム・改修', 
            '省エネ・再エネ', '防犯・防災・BCP', 
            '空き家対策', '子育て支援', 
            '移住・定住'
        ),
        'agriculture' => array(
            '農業・林業・漁業', '6次産業化', 
            '設備投資', '販路拡大', 
            '省エネ・再エネ', '人材育成・人材確保', 
            '地域活性化'
        ),
        'individual' => array(
            '起業・独立', 'フリーランス', 
            '資格取得・スキルアップ', '若者・学生支援', 
            '創業・スタートアップ', 'テレワーク・在宅ワーク', 
            '副業・兼業'
        )
    );
    
    return $mapping;
}

/**
 * Get grant categories for a specific purpose
 */
function gi_get_categories_for_purpose($purpose_slug) {
    $mapping = gi_get_purpose_category_mapping();
    
    if (!isset($mapping[$purpose_slug])) {
        return array();
    }
    
    $category_names = $mapping[$purpose_slug];
    
    $terms = get_terms(array(
        'taxonomy' => 'grant_category',
        'name' => $category_names,
        'hide_empty' => false
    ));
    
    if (is_wp_error($terms)) {
        return array();
    }
    
    return $terms;
}

/**
 * Get category slugs for a specific purpose
 */
function gi_get_category_slugs_for_purpose($purpose_slug) {
    $terms = gi_get_categories_for_purpose($purpose_slug);
    $slugs = array();
    
    if (empty($terms)) {
        return $slugs;
    }
    
    foreach ($terms as $term) {
        $slugs[] = $term->slug;
    }
    
    return $slugs;
}

/**
 * Load Required Include Files
 */
$inc_dir = get_template_directory() . '/inc/';

$required_files = array(
    // Core files
    'theme-foundation.php',
    'data-processing.php',
    
    // Admin & UI
    'admin-functions.php',
    'acf-fields.php',
    'customizer-error-handler.php',
    
    // Core functionality
    'card-display.php',
    'ajax-functions.php',
    
    // AI Assistant Core
    'ai-assistant-core.php',
    
    // Performance optimization
    'performance-optimization.php',
    
    // Google Sheets integration
    'google-sheets-integration.php',
    'safe-sync-manager.php',
    
    // Dynamic CSS Generator
    'grant-dynamic-css-generator.php',
    
    // Column System
    'column-system.php',
    
    // Grant Amount Fixer
    'grant-amount-fixer.php',
);

foreach ($required_files as $file) {
    $file_path = $inc_dir . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } elseif (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Grant Insight: Missing required file: ' . $file);
    }
}

/**
 * ============================================================================
 * CONTENT FILTER: Remove Duplicate Sections (ACTIVE)
 * ============================================================================
 * 
 * 本文（the_content）から重複する特定のセクションを削除する
 * 
 * 【有効化理由】(2025-11-27)
 * - 重複コンテンツがユーザー体験を悪化させているため
 * - single-grant.php の「詳細情報」セクションでACFフィールドから表示される内容と、
 *   本文で重複する部分を削除することで、ページの可読性を向上
 * 
 * 【削除対象】
 * - テンプレート（ACFフィールド）で既に表示されているセクション
 * - 本文中の見出しで始まる重複セクション
 */
function remove_duplicate_sections_from_content($content) {
    // 助成金（grant）の個別ページ以外では実行しない
    if (!is_singular('grant')) {
        return $content;
    }
    
    // 空のコンテンツは処理しない
    if (empty(trim($content))) {
        return $content;
    }

    // 削除したい見出しのリスト
    $targets = [
        // 完全一致パターン
        '対象経費（詳細）',
        '必要書類（詳細）',
        '対象者・対象事業',
        '■対象経費（詳細）',
        '■必要書類（詳細）',
        '■対象者・対象事業',
        // 表記ゆれ対策
        '対象経費',
        '必要書類',
        '対象者',
        '対象事業',
        // 追加パターン
        '補助対象経費',
        '申請書類',
        '提出書類',
    ];

    foreach ($targets as $target) {
        $escaped_target = preg_quote($target, '/');
        
        // パターン1: <h2>〜</h2> 見出しから次の同レベル以上の見出しまで削除
        $pattern1 = '/<h([2-4])[^>]*>\s*(?:■|●|◆|▼|【|★)?\s*' . $escaped_target . '.*?<\/h\1>[\s\S]*?(?=<h[2-4]|$)/iu';
        
        // パターン2: <p><strong>見出し</strong></p> 形式
        $pattern2 = '/<p[^>]*>\s*<strong>\s*(?:■|●|◆|▼|【|★)?\s*' . $escaped_target . '.*?<\/strong>\s*<\/p>[\s\S]*?(?=<p[^>]*>\s*<strong>|<h[2-6]|$)/iu';
        
        $content = preg_replace($pattern1, '', $content);
        $content = preg_replace($pattern2, '', $content);
    }
    
    // 空の段落タグを削除
    $content = preg_replace('/<p[^>]*>\s*<\/p>/i', '', $content);
    
    // 連続した改行を整理
    $content = preg_replace('/(\s*<br\s*\/?>\s*){3,}/i', '<br><br>', $content);

    return $content;
}
add_filter('the_content', 'remove_duplicate_sections_from_content', 20);

/**
 * ============================================================================
 * DISABLED FUNCTIONS - SEO DUPLICATE PREVENTION
 * ============================================================================
 * 
 * 以下の関数は header.php で既に出力されているため無効化しました。
 * 
 * 1. gi_add_seo_meta_tags() - DISABLED
 *    理由: header.php の ji_get_current_page_info() が以下を出力済み
 *    - <meta name="description">
 *    - <link rel="canonical">
 *    - <meta property="og:*"> (OGPタグ全般)
 *    - <meta name="twitter:*"> (Twitterカード)
 * 
 * 2. gi_inject_inline_cta() - DISABLED
 *    理由: コンテンツの自然な流れを妨げる可能性がある
 *    CTAが必要な場合は single-grant.php 内で直接配置を推奨
 * 
 * 3. gi_remove_duplicate_acf_content() - DISABLED
 *    理由: remove_duplicate_sections_from_content() と機能が重複
 */

/**
 * ============================================================================
 * REST API SETTINGS
 * ============================================================================
 */
function gi_enqueue_rest_api_settings() {
    wp_enqueue_script('jquery');
    
    wp_localize_script('jquery', 'wpApiSettings', array(
        'root' => esc_url_raw(rest_url()),
        'nonce' => wp_create_nonce('wp_rest')
    ));
    
    wp_localize_script('jquery', 'ajaxSettings', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wp_rest')
    ));
}
add_action('wp_enqueue_scripts', 'gi_enqueue_rest_api_settings');

/**
 * ============================================================================
 * ADDITIONAL INCLUDE FILES
 * ============================================================================
 */

// Affiliate Ad Manager System
$affiliate_ad_file = get_template_directory() . '/inc/affiliate-ad-manager.php';
if (file_exists($affiliate_ad_file)) {
    require_once $affiliate_ad_file;
}

// Access Tracking System
$access_tracking_file = get_template_directory() . '/inc/access-tracking.php';
if (file_exists($access_tracking_file)) {
    require_once $access_tracking_file;
}

// SEO Content Manager
$seo_content_manager_file = get_template_directory() . '/inc/seo-content-manager.php';
if (file_exists($seo_content_manager_file)) {
    require_once $seo_content_manager_file;
}

// AI補助金コンシェルジュ読み込み
require_once get_template_directory() . '/inc/ai-concierge.php';