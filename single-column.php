<?php
/**
 * Single Column Template - Ultimate Edition v7.1
 * コラム記事詳細ページ - 完全修正版
 * 
 * @package Grant_Insight_Ultimate
 * @subpackage Column_System
 * @version 7.1.0
 */

if (!defined('ABSPATH')) exit;

get_header();

while (have_posts()): the_post();

// ===================================
// 基本データ取得
// ===================================
$post_id = get_the_ID();
$canonical_url = get_permalink($post_id);
$site_name = get_bloginfo('name');
$post_title = get_the_title();
$post_content = get_the_content();
$post_excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words(strip_tags($post_content), 55);

// ===================================
// ヘルパー関数定義
// ===================================

if (!function_exists('gic_get_field')) {
    function gic_get_field($field_name, $pid, $default = '') {
        $value = function_exists('get_field') ? get_field($field_name, $pid) : get_post_meta($pid, $field_name, true);
        return ($value !== null && $value !== false && $value !== '') ? $value : $default;
    }
}

if (!function_exists('gic_get_field_array')) {
    function gic_get_field_array($field_name, $pid) {
        $value = function_exists('get_field') ? get_field($field_name, $pid) : get_post_meta($pid, $field_name, true);
        return is_array($value) ? $value : array();
    }
}

if (!function_exists('gic_get_terms')) {
    function gic_get_terms($pid, $taxonomy) {
        if (!taxonomy_exists($taxonomy)) return array();
        $terms = wp_get_post_terms($pid, $taxonomy);
        return (!is_wp_error($terms) && !empty($terms)) ? $terms : array();
    }
}

if (!function_exists('gic_calculate_reading_time')) {
    function gic_calculate_reading_time($content) {
        $word_count = mb_strlen(strip_tags($content), 'UTF-8');
        return max(1, ceil($word_count / 400));
    }
}

if (!function_exists('gic_format_date')) {
    function gic_format_date($date_string, $format = 'Y年n月j日') {
        if (empty($date_string)) return '';
        $timestamp = strtotime($date_string);
        return $timestamp ? date($format, $timestamp) : '';
    }
}

if (!function_exists('gic_parse_key_points')) {
    function gic_parse_key_points($key_points) {
        $result = array();
        
        if (empty($key_points)) {
            return $result;
        }
        
        if (is_array($key_points)) {
            foreach ($key_points as $point) {
                $point_text = '';
                if (is_array($point)) {
                    $point_text = isset($point['point']) ? $point['point'] : 
                                 (isset($point['text']) ? $point['text'] : 
                                 (isset($point['content']) ? $point['content'] : ''));
                } else {
                    $point_text = $point;
                }
                $point_text = trim(wp_strip_all_tags($point_text));
                if (!empty($point_text) && mb_strlen($point_text, 'UTF-8') > 2) {
                    $result[] = $point_text;
                }
            }
        } else {
            $clean_text = wp_strip_all_tags($key_points);
            $lines = preg_split('/\r\n|\r|\n/', $clean_text);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line) && mb_strlen($line, 'UTF-8') > 2) {
                    $result[] = $line;
                }
            }
        }
        
        return $result;
    }
}

// ===================================
// メタ情報取得
// ===================================
$column = array(
    'read_time' => gic_get_field('estimated_read_time', $post_id),
    'view_count' => intval(gic_get_field('view_count', $post_id, 0)),
    'difficulty' => gic_get_field('difficulty_level', $post_id, 'beginner'),
    'last_updated' => gic_get_field('last_updated', $post_id),
    'last_verified_date' => gic_get_field('last_verified_date', $post_id),
    'key_points' => gic_get_field('key_points', $post_id),
    'target_audience' => gic_get_field_array('target_audience', $post_id),
    'ai_summary' => gic_get_field('ai_summary', $post_id),
    'supervisor_name' => gic_get_field('supervisor_name', $post_id),
    'supervisor_title' => gic_get_field('supervisor_title', $post_id),
    'supervisor_profile' => gic_get_field('supervisor_profile', $post_id),
    'supervisor_image' => gic_get_field_array('supervisor_image', $post_id),
    'supervisor_url' => gic_get_field('supervisor_url', $post_id),
    'supervisor_credentials' => gic_get_field_array('supervisor_credentials', $post_id),
    'source_url' => gic_get_field('source_url', $post_id),
    'source_name' => gic_get_field('source_name', $post_id),
    'related_grants' => gic_get_field_array('related_grants', $post_id),
    'related_columns' => gic_get_field_array('related_columns', $post_id),
    'faq_items' => gic_get_field_array('faq_items', $post_id),
    'is_featured' => (bool)gic_get_field('is_featured', $post_id, false),
    'is_new' => (bool)gic_get_field('is_new', $post_id, false),
    'is_updated' => (bool)gic_get_field('is_updated', $post_id, false),
);

// 読了時間
if (!$column['read_time']) {
    $column['read_time'] = gic_calculate_reading_time($post_content);
}

// キーポイントをパース
$key_points_array = gic_parse_key_points($column['key_points']);

// デフォルト監修者
if (empty($column['supervisor_name'])) {
    $column['supervisor_name'] = '補助金インサイト編集部';
    $column['supervisor_title'] = '中小企業診断士・行政書士監修';
    $column['supervisor_profile'] = '補助金・助成金の専門家チーム。中小企業診断士、行政書士、税理士など各分野の専門家が在籍。年間1,000件以上の補助金申請支援実績があります。';
    $column['supervisor_credentials'] = array(
        array('credential' => '中小企業診断士'),
        array('credential' => '行政書士'),
        array('credential' => '認定経営革新等支援機関'),
    );
}

// 最終確認日
$last_verified = $column['last_verified_date'] ? $column['last_verified_date'] : get_the_modified_date('Y-m-d');
$last_verified_display = gic_format_date($last_verified);
$freshness_class = '';
$freshness_label = '確認';
if ($last_verified) {
    $diff = (current_time('timestamp') - strtotime($last_verified)) / 86400;
    if ($diff < 30) { $freshness_class = 'fresh'; $freshness_label = '最新'; }
    elseif ($diff < 90) { $freshness_class = 'recent'; $freshness_label = '確認済'; }
    elseif ($diff > 180) { $freshness_class = 'old'; $freshness_label = '要確認'; }
}

// タクソノミー取得
$categories = gic_get_terms($post_id, 'column_category');
if (empty($categories)) {
    $categories = gic_get_terms($post_id, 'category');
}
$tags = gic_get_terms($post_id, 'column_tag');
if (empty($tags)) {
    $tags = gic_get_terms($post_id, 'post_tag');
}

// 難易度マップ
$difficulty_map = array(
    'beginner' => array('label' => '初級', 'level' => 1, 'class' => 'beginner'),
    'intermediate' => array('label' => '中級', 'level' => 2, 'class' => 'intermediate'),
    'advanced' => array('label' => '上級', 'level' => 3, 'class' => 'advanced'),
);
$difficulty = isset($difficulty_map[$column['difficulty']]) ? $difficulty_map[$column['difficulty']] : $difficulty_map['beginner'];

// 対象読者ラベル
$audience_labels = array(
    'startup' => array('label' => '創業・スタートアップを考えている方', 'icon' => 'rocket'),
    'sme' => array('label' => '中小企業の経営者・担当者', 'icon' => 'building'),
    'individual' => array('label' => '個人事業主・フリーランス', 'icon' => 'user'),
    'npo' => array('label' => 'NPO・一般社団法人', 'icon' => 'heart'),
    'agriculture' => array('label' => '農業・林業・漁業従事者', 'icon' => 'leaf'),
    'it' => array('label' => 'IT・デジタル関連事業者', 'icon' => 'laptop'),
    'manufacturing' => array('label' => '製造業・ものづくり企業', 'icon' => 'cog'),
    'service' => array('label' => 'サービス業・小売業', 'icon' => 'store'),
    'other' => array('label' => 'その他事業者', 'icon' => 'briefcase'),
);

// 閲覧数更新
$view_cookie = 'gic_viewed_' . $post_id;
if (!isset($_COOKIE[$view_cookie])) {
    update_post_meta($post_id, 'view_count', $column['view_count'] + 1);
    $column['view_count']++;
    $cookie_options = array(
        'expires' => time() + 86400,
        'path' => '/',
        'secure' => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax'
    );
    setcookie($view_cookie, '1', $cookie_options);
}

// パンくず
$breadcrumbs = array(
    array('name' => 'ホーム', 'url' => home_url('/')),
    array('name' => 'コラム', 'url' => get_post_type_archive_link('column') ?: home_url('/column/')),
);
if (!empty($categories[0])) {
    $cat_link = get_term_link($categories[0]);
    if (!is_wp_error($cat_link)) {
        $breadcrumbs[] = array('name' => $categories[0]->name, 'url' => $cat_link);
    }
}
$breadcrumbs[] = array('name' => $post_title, 'url' => $canonical_url);

// メタディスクリプション
$meta_desc = '';
if ($column['ai_summary']) {
    $meta_desc = mb_substr(wp_strip_all_tags($column['ai_summary']), 0, 120, 'UTF-8');
} elseif ($post_excerpt) {
    $meta_desc = mb_substr(wp_strip_all_tags($post_excerpt), 0, 120, 'UTF-8');
} else {
    $meta_desc = mb_substr(wp_strip_all_tags($post_content), 0, 120, 'UTF-8');
}

// OGP画像
$og_image = get_the_post_thumbnail_url($post_id, 'full');
if (!$og_image) {
    $og_image = get_template_directory_uri() . '/assets/images/ogp-default.jpg';
}

// 著者情報
$author_id = get_the_author_meta('ID');
$author_name = get_the_author();

// 関連補助金取得
$related_grants = array();
if (!empty($column['related_grants'])) {
    foreach ($column['related_grants'] as $grant_item) {
        $grant_id = is_array($grant_item) ? (isset($grant_item['ID']) ? intval($grant_item['ID']) : 0) : intval($grant_item);
        if ($grant_id > 0 && get_post_status($grant_id) === 'publish') {
            $related_grants[] = array(
                'id' => $grant_id,
                'title' => get_the_title($grant_id),
                'permalink' => get_permalink($grant_id),
                'max_amount' => gic_get_field('max_amount', $grant_id),
                'deadline' => gic_get_field('deadline', $grant_id),
                'application_status' => gic_get_field('application_status', $grant_id, 'open'),
            );
        }
    }
}

// 自動で関連補助金を取得
if (empty($related_grants)) {
    $grant_query = new WP_Query(array(
        'post_type' => 'grant',
        'posts_per_page' => 4,
        'post_status' => 'publish',
        'orderby' => 'rand',
    ));
    if ($grant_query->have_posts()) {
        while ($grant_query->have_posts()) {
            $grant_query->the_post();
            $gid = get_the_ID();
            $related_grants[] = array(
                'id' => $gid,
                'title' => get_the_title(),
                'permalink' => get_permalink(),
                'max_amount' => gic_get_field('max_amount', $gid),
                'deadline' => gic_get_field('deadline', $gid),
                'application_status' => gic_get_field('application_status', $gid, 'open'),
            );
        }
        wp_reset_postdata();
    }
}

// 関連コラム取得
$related_columns = array();
$related_query = new WP_Query(array(
    'post_type' => array('column', 'post'),
    'posts_per_page' => 4,
    'post__not_in' => array($post_id),
    'post_status' => 'publish',
    'orderby' => 'rand',
));
if ($related_query->have_posts()) {
    while ($related_query->have_posts()) {
        $related_query->the_post();
        $related_columns[] = array(
            'id' => get_the_ID(),
            'title' => get_the_title(),
            'permalink' => get_permalink(),
            'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
            'date' => get_the_date('Y.m.d'),
            'read_time' => gic_get_field('estimated_read_time', get_the_ID(), gic_calculate_reading_time(get_the_content())),
        );
    }
    wp_reset_postdata();
}

// 人気コラム取得
$popular_columns = array();
$popular_query = new WP_Query(array(
    'post_type' => array('column', 'post'),
    'posts_per_page' => 5,
    'post__not_in' => array($post_id),
    'post_status' => 'publish',
    'meta_key' => 'view_count',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
));
if ($popular_query->have_posts()) {
    while ($popular_query->have_posts()) {
        $popular_query->the_post();
        $popular_columns[] = array(
            'id' => get_the_ID(),
            'title' => get_the_title(),
            'permalink' => get_permalink(),
            'view_count' => intval(gic_get_field('view_count', get_the_ID(), 0)),
        );
    }
    wp_reset_postdata();
}

// FAQ生成
$faq_items = array();
if (!empty($column['faq_items'])) {
    foreach ($column['faq_items'] as $faq) {
        if (is_array($faq) && !empty($faq['question']) && !empty($faq['answer'])) {
            $faq_items[] = $faq;
        }
    }
}

if (count($faq_items) < 3) {
    $default_faqs = array(
        array(
            'question' => 'この記事の情報は最新ですか？',
            'answer' => 'はい、' . $last_verified_display . '時点で内容を確認・更新しています。補助金制度は変更されることがありますので、申請前に必ず公式サイトで最新情報をご確認ください。'
        ),
        array(
            'question' => '補助金の申請サポートは受けられますか？',
            'answer' => '当サイトでは補助金申請のサポートサービスを提供しています。専門家による申請書類の作成支援や、採択率を高めるためのアドバイスを受けることができます。'
        ),
        array(
            'question' => '関連する補助金を探すにはどうすればいいですか？',
            'answer' => '当サイトのAI診断機能を使えば、あなたの事業に最適な補助金を簡単に見つけることができます。また、補助金一覧ページから条件で絞り込み検索も可能です。'
        ),
    );
    foreach ($default_faqs as $dfaq) {
        if (count($faq_items) < 5) {
            $faq_items[] = $dfaq;
        }
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<title><?php echo esc_html($post_title); ?> | <?php echo esc_html($site_name); ?></title>
<meta name="description" content="<?php echo esc_attr($meta_desc); ?>">
<link rel="canonical" href="<?php echo esc_url($canonical_url); ?>">

<!-- OGP -->
<meta property="og:type" content="article">
<meta property="og:title" content="<?php echo esc_attr($post_title); ?>">
<meta property="og:description" content="<?php echo esc_attr($meta_desc); ?>">
<meta property="og:url" content="<?php echo esc_url($canonical_url); ?>">
<meta property="og:image" content="<?php echo esc_url($og_image); ?>">
<meta property="og:site_name" content="<?php echo esc_attr($site_name); ?>">
<meta property="article:published_time" content="<?php echo get_the_date('c'); ?>">
<meta property="article:modified_time" content="<?php echo get_the_modified_date('c'); ?>">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo esc_attr($post_title); ?>">
<meta name="twitter:description" content="<?php echo esc_attr($meta_desc); ?>">
<meta name="twitter:image" content="<?php echo esc_url($og_image); ?>">

<!-- 構造化データ -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        <?php foreach ($breadcrumbs as $i => $crumb): ?>
        {"@type": "ListItem", "position": <?php echo $i + 1; ?>, "name": <?php echo json_encode($crumb['name'], JSON_UNESCAPED_UNICODE); ?>, "item": "<?php echo esc_url($crumb['url']); ?>"}<?php echo $i < count($breadcrumbs) - 1 ? ',' : ''; ?>
        <?php endforeach; ?>
    ]
}
</script>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": <?php echo json_encode($post_title, JSON_UNESCAPED_UNICODE); ?>,
    "description": <?php echo json_encode($meta_desc, JSON_UNESCAPED_UNICODE); ?>,
    "image": "<?php echo esc_url($og_image); ?>",
    "datePublished": "<?php echo get_the_date('c'); ?>",
    "dateModified": "<?php echo get_the_modified_date('c'); ?>",
    "author": {"@type": "Person", "name": <?php echo json_encode($author_name, JSON_UNESCAPED_UNICODE); ?>},
    "publisher": {"@type": "Organization", "name": "<?php echo esc_js($site_name); ?>"},
    "reviewedBy": {"@type": "Person", "name": <?php echo json_encode($column['supervisor_name'], JSON_UNESCAPED_UNICODE); ?>},
    "mainEntityOfPage": {"@type": "WebPage", "@id": "<?php echo esc_url($canonical_url); ?>"}
}
</script>

<?php if (!empty($faq_items)): ?>
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        <?php foreach ($faq_items as $i => $faq): ?>
        {"@type": "Question", "name": <?php echo json_encode($faq['question'], JSON_UNESCAPED_UNICODE); ?>, "acceptedAnswer": {"@type": "Answer", "text": <?php echo json_encode($faq['answer'], JSON_UNESCAPED_UNICODE); ?>}}<?php echo $i < count($faq_items) - 1 ? ',' : ''; ?>
        <?php endforeach; ?>
    ]
}
</script>
<?php endif; ?>

<style>
/* ============================================
   Single Column v7.1 - Complete Fixed Version
   ============================================ */

:root {
    --gic-black: #111;
    --gic-dark: #1a1a1a;
    --gic-gray-900: #222;
    --gic-gray-800: #333;
    --gic-gray-700: #444;
    --gic-gray-600: #4b5563;
    --gic-gray-500: #888;
    --gic-gray-400: #aaa;
    --gic-gray-300: #ccc;
    --gic-gray-200: #e5e5e5;
    --gic-gray-100: #f5f5f5;
    --gic-gray-50: #fafafa;
    --gic-white: #fff;
    --gic-accent: #FFD700;
    --gic-accent-dark: #E6C200;
    --gic-accent-light: #FFF8DC;
    --gic-success: #059669;
    --gic-success-light: #D1FAE5;
    --gic-success-text: #047857;
    --gic-warning: #D97706;
    --gic-warning-light: #FEF3C7;
    --gic-error: #DC2626;
    --gic-error-light: #FEE2E2;
    --gic-info: #2563EB;
    --gic-info-light: #DBEAFE;
    --gic-font: 'Noto Sans JP', -apple-system, BlinkMacSystemFont, sans-serif;
    --gic-container: 1280px;
    --gic-sidebar: 380px;
    --gic-gap: 48px;
    --gic-transition: 0.2s ease;
    --gic-shadow: 0 2px 8px rgba(0,0,0,0.08);
    --gic-shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
    --gic-mobile-banner: 60px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: var(--gic-font); font-size: 16px; line-height: 1.8; color: var(--gic-black); background: var(--gic-white); -webkit-font-smoothing: antialiased; }
a { color: inherit; text-decoration: none; }
img { max-width: 100%; height: auto; display: block; }
button { font-family: inherit; cursor: pointer; border: none; background: none; }
ul, ol { list-style: none; }

.gic-skip-link { position: absolute; top: -100px; left: 0; background: var(--gic-black); color: var(--gic-white); padding: 12px 16px; z-index: 10000; transition: top 0.2s; }
.gic-skip-link:focus { top: 0; }

.gic-progress { position: fixed; top: 0; left: 0; width: 0; height: 3px; background: var(--gic-accent); z-index: 9999; transition: width 0.1s linear; }

.gic-container { max-width: var(--gic-container); margin: 0 auto; padding: 0 24px; }

.gic-layout { display: grid; grid-template-columns: 1fr var(--gic-sidebar); gap: var(--gic-gap); padding: 48px 0; align-items: start; }
.gic-main { min-width: 0; }
.gic-sidebar { position: sticky; top: 24px; display: flex; flex-direction: column; gap: 24px; }

@media (max-width: 1100px) {
    .gic-layout { grid-template-columns: 1fr; gap: 32px; }
    .gic-sidebar { position: static; display: none; }
    .gic-page { padding-bottom: calc(var(--gic-mobile-banner) + 80px); }
}

/* パンくず */
.gic-breadcrumb { padding: 16px 0; border-bottom: 1px solid var(--gic-gray-200); background: var(--gic-gray-50); }
.gic-breadcrumb-list { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: 14px; color: var(--gic-gray-600); max-width: var(--gic-container); margin: 0 auto; padding: 0 24px; list-style: none; }
.gic-breadcrumb-link:hover { color: var(--gic-black); }
.gic-breadcrumb-sep { color: var(--gic-gray-300); }
.gic-breadcrumb-current { color: var(--gic-black); font-weight: 600; }

/* ヒーロー */
.gic-hero { padding: 40px 0; border-bottom: 1px solid var(--gic-gray-200); }
.gic-hero-badges { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.gic-badge { display: inline-flex; align-items: center; padding: 4px 12px; font-size: 12px; font-weight: 700; letter-spacing: 0.05em; }
.gic-badge-category { background: var(--gic-black); color: var(--gic-accent); }
.gic-badge-beginner { background: var(--gic-success); color: var(--gic-white); }
.gic-badge-intermediate { background: var(--gic-warning); color: var(--gic-white); }
.gic-badge-advanced { background: var(--gic-error); color: var(--gic-white); }
.gic-badge-featured { background: var(--gic-accent); color: var(--gic-black); }
.gic-badge-new { background: var(--gic-info); color: var(--gic-white); }
.gic-badge-updated { background: var(--gic-success-light); color: var(--gic-success-text); border: 1px solid var(--gic-success); }
.gic-badge-fresh { background: var(--gic-success-light); color: var(--gic-success-text); }
.gic-badge-old { background: var(--gic-warning-light); color: var(--gic-warning); }

.gic-hero-title { font-size: 32px; font-weight: 900; line-height: 1.3; letter-spacing: -0.02em; margin-bottom: 16px; }
@media (max-width: 768px) { .gic-hero-title { font-size: 24px; } }

.gic-hero-meta { display: flex; flex-wrap: wrap; gap: 16px; font-size: 14px; color: var(--gic-gray-600); }
.gic-hero-meta-item { display: flex; align-items: center; gap: 4px; }
.gic-hero-meta-item svg { width: 14px; height: 14px; flex-shrink: 0; }

/* メトリクス */
.gic-metrics { display: grid; grid-template-columns: repeat(4, 1fr); border: 2px solid var(--gic-black); margin: 32px 0; }
.gic-metric { padding: 20px; text-align: center; border-right: 1px solid var(--gic-gray-200); background: var(--gic-white); }
.gic-metric:last-child { border-right: none; }
.gic-metric-label { font-size: 12px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--gic-gray-600); margin-bottom: 8px; }
.gic-metric-value { font-size: 20px; font-weight: 900; color: var(--gic-black); line-height: 1.2; }
.gic-metric-value.highlight { background: linear-gradient(transparent 60%, var(--gic-accent) 60%); display: inline; padding: 0 4px; }
.gic-metric-sub { font-size: 12px; color: var(--gic-gray-600); margin-top: 4px; }
.gic-metric-stars { display: flex; justify-content: center; gap: 2px; margin-top: 4px; }
.gic-metric-star { width: 12px; height: 12px; }
.gic-metric-star.active { fill: var(--gic-accent); }
.gic-metric-star:not(.active) { fill: var(--gic-gray-300); }

@media (max-width: 768px) {
    .gic-metrics { grid-template-columns: repeat(2, 1fr); }
    .gic-metric:nth-child(2) { border-right: none; }
    .gic-metric:nth-child(1), .gic-metric:nth-child(2) { border-bottom: 1px solid var(--gic-gray-200); }
}

/* AI要約 */
.gic-summary { background: var(--gic-black); color: var(--gic-white); padding: 32px; margin-bottom: 32px; position: relative; }
.gic-summary::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--gic-accent); }
.gic-summary-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.gic-summary-icon { width: 40px; height: 40px; background: var(--gic-accent); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.gic-summary-icon svg { width: 24px; height: 24px; color: var(--gic-black); }
.gic-summary-label { font-size: 12px; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; }
.gic-summary-badge { margin-left: auto; padding: 4px 12px; background: rgba(255,255,255,0.1); font-size: 12px; font-weight: 600; }
.gic-summary-text { font-size: 18px; line-height: 2; color: rgba(255,255,255,0.95); }

/* 対象読者 */
.gic-audience { background: var(--gic-gray-50); border-left: 4px solid var(--gic-black); padding: 24px; margin-bottom: 32px; }
.gic-audience-title { font-size: 16px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.gic-audience-title svg { width: 20px; height: 20px; flex-shrink: 0; }
.gic-audience-list { display: flex; flex-direction: column; gap: 8px; list-style: none; margin: 0; padding: 0; }
.gic-audience-item { display: flex; align-items: center; gap: 8px; font-size: 15px; color: var(--gic-gray-700); margin: 0; padding: 0; }
.gic-audience-item svg { width: 16px; height: 16px; color: var(--gic-success); flex-shrink: 0; }

/* アイキャッチ */
.gic-thumbnail { margin: 32px 0; border: 2px solid var(--gic-black); overflow: hidden; }
.gic-thumbnail img { width: 100%; height: auto; display: block; }

/* セクション */
.gic-section { margin-bottom: 48px; }
.gic-section-header { display: flex; align-items: center; gap: 12px; padding-bottom: 16px; border-bottom: 2px solid var(--gic-black); margin-bottom: 24px; }
.gic-section-icon { width: 24px; height: 24px; flex-shrink: 0; }
.gic-section-title { font-size: 20px; font-weight: 900; letter-spacing: -0.01em; margin: 0; padding: 0; border: none; background: none; }
.gic-section-en { font-size: 12px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--gic-gray-500); margin-left: auto; }

/* コンテンツ */
.gic-content { font-size: 16px; line-height: 2; color: var(--gic-gray-800); }
.gic-content h2 { font-size: 24px; font-weight: 900; color: var(--gic-black); margin: 48px 0 20px; padding-bottom: 12px; border-bottom: 2px solid var(--gic-black); }
.gic-content h3 { font-size: 20px; font-weight: 700; color: var(--gic-black); margin: 36px 0 16px; padding-left: 16px; border-left: 4px solid var(--gic-accent); }
.gic-content h4 { font-size: 18px; font-weight: 700; color: var(--gic-black); margin: 28px 0 12px; }
.gic-content p { margin-bottom: 20px; }
.gic-content ul, .gic-content ol { margin: 20px 0; padding-left: 28px; }
.gic-content li { margin-bottom: 10px; }
.gic-content ul li { list-style: disc; }
.gic-content ol li { list-style: decimal; }
.gic-content strong { font-weight: 700; color: var(--gic-black); }
.gic-content a { color: var(--gic-info); text-decoration: underline; text-underline-offset: 2px; }
.gic-content a:hover { color: var(--gic-black); }
.gic-content blockquote { margin: 24px 0; padding: 20px 24px; background: var(--gic-gray-50); border-left: 4px solid var(--gic-accent); font-style: italic; }
.gic-content img { margin: 24px 0; border: 1px solid var(--gic-gray-200); }
.gic-content table { width: 100%; margin: 24px 0; border-collapse: collapse; }
.gic-content th, .gic-content td { padding: 12px 16px; border: 1px solid var(--gic-gray-200); text-align: left; }
.gic-content th { background: var(--gic-gray-100); font-weight: 700; }

/* ============================================
   ポイントまとめ - 完全修正版
   ============================================ */
.gic-keypoints {
    background: var(--gic-accent-light) !important;
    border: 2px solid var(--gic-accent) !important;
    padding: 28px !important;
    margin: 40px 0 !important;
}

.gic-keypoints-title {
    font-size: 18px !important;
    font-weight: 700 !important;
    margin: 0 0 20px 0 !important;
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    color: var(--gic-black) !important;
    border: none !important;
    border-bottom: none !important;
    background: transparent !important;
}

.gic-keypoints-title svg {
    width: 24px !important;
    height: 24px !important;
    min-width: 24px !important;
    min-height: 24px !important;
    max-width: 24px !important;
    max-height: 24px !important;
    flex-shrink: 0 !important;
}

.gic-keypoints-list {
    display: flex !important;
    flex-direction: column !important;
    gap: 16px !important;
    list-style: none !important;
    margin: 0 !important;
    padding: 0 !important;
}

.gic-keypoints-item {
    display: flex !important;
    align-items: flex-start !important;
    gap: 14px !important;
    font-size: 15px !important;
    line-height: 1.7 !important;
    color: var(--gic-gray-800) !important;
    margin: 0 !important;
    padding: 0 !important;
    list-style: none !important;
}

.gic-keypoints-item::before {
    display: none !important;
}

.gic-keypoints-item::marker {
    display: none !important;
}

.gic-keypoints-num {
    width: 28px !important;
    height: 28px !important;
    min-width: 28px !important;
    background: var(--gic-black) !important;
    color: var(--gic-white) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 14px !important;
    font-weight: 700 !important;
    flex-shrink: 0 !important;
    margin-top: 2px !important;
}

.gic-keypoints-text {
    flex: 1 !important;
    word-wrap: break-word !important;
    overflow-wrap: break-word !important;
}

/* ============================================
   CTA - 完全修正版
   ============================================ */
.gic-cta {
    background: linear-gradient(135deg, var(--gic-black) 0%, var(--gic-dark) 100%) !important;
    color: var(--gic-white) !important;
    padding: 48px 32px !important;
    margin: 48px 0 !important;
    text-align: center !important;
    position: relative !important;
    overflow: hidden !important;
}

.gic-cta::before,
.gic-cta::after {
    content: '' !important;
    position: absolute !important;
    left: 0 !important;
    right: 0 !important;
    height: 4px !important;
    background: var(--gic-accent) !important;
}

.gic-cta::before { top: 0 !important; }
.gic-cta::after { bottom: 0 !important; }

.gic-cta-inner {
    max-width: 700px !important;
    margin: 0 auto !important;
}

.gic-cta-icon {
    width: 64px !important;
    height: 64px !important;
    margin: 0 auto 20px !important;
    background: rgba(255, 215, 0, 0.15) !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.gic-cta-icon svg {
    width: 32px !important;
    height: 32px !important;
    min-width: 32px !important;
    max-width: 32px !important;
    color: var(--gic-accent) !important;
}

.gic-cta-title {
    font-size: 24px !important;
    font-weight: 900 !important;
    margin: 0 0 12px 0 !important;
    padding: 0 !important;
    color: var(--gic-white) !important;
    border: none !important;
    background: transparent !important;
}

.gic-cta-desc {
    font-size: 16px !important;
    color: rgba(255, 255, 255, 0.9) !important;
    margin: 0 0 28px 0 !important;
    line-height: 1.7 !important;
}

.gic-cta-buttons {
    display: flex !important;
    justify-content: center !important;
    gap: 16px !important;
    flex-wrap: wrap !important;
}

.gic-cta-btn {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 10px !important;
    padding: 16px 28px !important;
    font-size: 16px !important;
    font-weight: 700 !important;
    border: 2px solid !important;
    text-decoration: none !important;
    transition: all 0.2s ease !important;
    min-width: 180px !important;
}

.gic-cta-btn svg {
    width: 20px !important;
    height: 20px !important;
    min-width: 20px !important;
    max-width: 20px !important;
    flex-shrink: 0 !important;
}

.gic-cta-btn-primary {
    background: var(--gic-accent) !important;
    border-color: var(--gic-accent) !important;
    color: var(--gic-black) !important;
}

.gic-cta-btn-primary:hover {
    background: var(--gic-accent-dark) !important;
    border-color: var(--gic-accent-dark) !important;
    transform: translateY(-2px) !important;
}

.gic-cta-btn-secondary {
    background: transparent !important;
    border-color: var(--gic-white) !important;
    color: var(--gic-white) !important;
}

.gic-cta-btn-secondary:hover {
    background: var(--gic-white) !important;
    color: var(--gic-black) !important;
    transform: translateY(-2px) !important;
}

@media (max-width: 768px) {
    .gic-keypoints { padding: 20px 16px !important; margin: 28px 0 !important; }
    .gic-keypoints-title { font-size: 16px !important; }
    .gic-keypoints-title svg { width: 20px !important; height: 20px !important; min-width: 20px !important; max-width: 20px !important; }
    .gic-keypoints-item { gap: 12px !important; font-size: 14px !important; }
    .gic-keypoints-num { width: 24px !important; height: 24px !important; min-width: 24px !important; font-size: 12px !important; }
    .gic-cta { padding: 36px 20px !important; margin: 36px 0 !important; }
    .gic-cta-icon { width: 56px !important; height: 56px !important; }
    .gic-cta-icon svg { width: 28px !important; height: 28px !important; }
    .gic-cta-title { font-size: 20px !important; }
    .gic-cta-desc { font-size: 14px !important; }
    .gic-cta-buttons { flex-direction: column !important; gap: 12px !important; }
    .gic-cta-btn { width: 100% !important; padding: 14px 24px !important; font-size: 15px !important; }
}

/* FAQ */
.gic-faq-list { display: flex; flex-direction: column; gap: 12px; }
.gic-faq-item { border: 1px solid var(--gic-gray-200); transition: var(--gic-transition); }
.gic-faq-item:hover { border-color: var(--gic-gray-400); }
.gic-faq-item[open] { border-color: var(--gic-black); }
.gic-faq-question { display: flex; align-items: center; gap: 16px; padding: 20px; font-size: 16px; font-weight: 700; color: var(--gic-black); cursor: pointer; list-style: none; transition: var(--gic-transition); }
.gic-faq-question::-webkit-details-marker { display: none; }
.gic-faq-question:hover { background: var(--gic-gray-50); }
.gic-faq-q-mark { width: 28px; height: 28px; min-width: 28px; background: var(--gic-black); color: var(--gic-white); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 900; flex-shrink: 0; }
.gic-faq-question-text { flex: 1; }
.gic-faq-icon { width: 20px; height: 20px; flex-shrink: 0; transition: transform 0.2s ease; }
.gic-faq-item[open] .gic-faq-icon { transform: rotate(45deg); }
.gic-faq-answer { padding: 0 20px 20px; padding-left: calc(20px + 28px + 16px); font-size: 16px; line-height: 1.8; color: var(--gic-gray-700); }

/* 関連補助金 */
.gic-grants-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
@media (max-width: 768px) { .gic-grants-grid { grid-template-columns: 1fr; } }
.gic-grant-card { border: 2px solid var(--gic-gray-200); padding: 20px; transition: var(--gic-transition); background: var(--gic-white); display: block; text-decoration: none; }
.gic-grant-card:hover { border-color: var(--gic-black); transform: translateY(-2px); box-shadow: var(--gic-shadow-lg); }
.gic-grant-badge { display: inline-block; padding: 2px 8px; font-size: 11px; font-weight: 700; margin-bottom: 8px; }
.gic-grant-badge.open { background: var(--gic-success); color: var(--gic-white); }
.gic-grant-badge.closed { background: var(--gic-gray-400); color: var(--gic-white); }
.gic-grant-title { font-size: 16px; font-weight: 700; line-height: 1.5; margin-bottom: 12px; color: var(--gic-black); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.gic-grant-meta { display: flex; flex-wrap: wrap; gap: 12px; font-size: 14px; color: var(--gic-gray-600); }
.gic-grant-meta strong { color: var(--gic-black); }
.gic-grant-link { display: inline-flex; align-items: center; gap: 4px; margin-top: 12px; font-size: 14px; font-weight: 600; color: var(--gic-black); }

/* タグ */
.gic-tags { display: flex; flex-wrap: wrap; gap: 8px; margin: 32px 0; }
.gic-tag { display: inline-flex; align-items: center; padding: 6px 14px; background: var(--gic-gray-100); border: 1px solid var(--gic-gray-200); font-size: 14px; font-weight: 500; transition: var(--gic-transition); }
.gic-tag:hover { background: var(--gic-black); border-color: var(--gic-black); color: var(--gic-white); }

/* シェア */
.gic-share { margin: 40px 0; padding: 24px; background: var(--gic-black); text-align: center; }
.gic-share-title { font-size: 16px; font-weight: 700; color: var(--gic-white); margin-bottom: 16px; }
.gic-share-buttons { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; }
.gic-share-btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; font-size: 14px; font-weight: 600; border: 2px solid var(--gic-white); color: var(--gic-white); transition: var(--gic-transition); text-decoration: none; }
.gic-share-btn:hover { background: var(--gic-white); color: var(--gic-black); }
.gic-share-btn svg { width: 18px; height: 18px; }

/* 情報ソース */
.gic-source-card { border: 2px solid var(--gic-gray-200); background: var(--gic-white); margin: 40px 0; }
.gic-source-header { display: flex; align-items: center; gap: 8px; padding: 12px 16px; background: var(--gic-gray-100); border-bottom: 1px solid var(--gic-gray-200); }
.gic-source-header svg { width: 18px; height: 18px; color: var(--gic-gray-600); }
.gic-source-label { font-size: 12px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--gic-gray-600); }
.gic-source-body { padding: 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.gic-source-info { flex: 1; min-width: 200px; }
.gic-source-name { font-size: 16px; font-weight: 700; color: var(--gic-black); margin-bottom: 4px; }
.gic-source-verified { display: inline-flex; align-items: center; gap: 4px; font-size: 14px; color: var(--gic-success-text); }
.gic-source-verified svg { width: 16px; height: 16px; }
.gic-source-link { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: var(--gic-black); color: var(--gic-white); font-size: 14px; font-weight: 600; transition: var(--gic-transition); text-decoration: none; }
.gic-source-link:hover { background: var(--gic-gray-800); }
.gic-source-link svg { width: 14px; height: 14px; }
.gic-source-footer { padding: 12px 16px; background: var(--gic-gray-50); border-top: 1px solid var(--gic-gray-200); font-size: 14px; color: var(--gic-gray-600); }

/* 監修者 */
.gic-supervisor { background: var(--gic-gray-50); border: 2px solid var(--gic-gray-200); padding: 24px; margin: 40px 0; }
.gic-supervisor-label { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--gic-gray-600); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--gic-gray-200); }
.gic-supervisor-label svg { width: 16px; height: 16px; }
.gic-supervisor-content { display: flex; gap: 20px; }
.gic-supervisor-avatar { width: 72px; height: 72px; background: var(--gic-gray-200); display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden; border-radius: 50%; }
.gic-supervisor-avatar img { width: 100%; height: 100%; object-fit: cover; }
.gic-supervisor-avatar svg { width: 32px; height: 32px; color: var(--gic-gray-500); }
.gic-supervisor-info { flex: 1; }
.gic-supervisor-name { font-size: 18px; font-weight: 900; margin-bottom: 4px; }
.gic-supervisor-title { font-size: 14px; color: var(--gic-gray-600); margin-bottom: 8px; }
.gic-supervisor-bio { font-size: 14px; color: var(--gic-gray-700); line-height: 1.7; margin-bottom: 12px; }
.gic-supervisor-credentials { display: flex; flex-wrap: wrap; gap: 8px; }
.gic-supervisor-credential { padding: 4px 12px; background: var(--gic-white); border: 1px solid var(--gic-gray-300); font-size: 12px; font-weight: 600; }

/* 関連コラム */
.gic-related { padding: 48px 0; background: var(--gic-gray-50); border-top: 1px solid var(--gic-gray-200); margin-top: 48px; }
.gic-related-header { text-align: center; margin-bottom: 32px; }
.gic-related-en { font-size: 12px; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; color: var(--gic-gray-500); margin-bottom: 8px; }
.gic-related-title { font-size: 24px; font-weight: 900; margin: 0; }
.gic-related-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
@media (max-width: 1024px) { .gic-related-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .gic-related-grid { grid-template-columns: 1fr; } }
.gic-related-card { background: var(--gic-white); border: 1px solid var(--gic-gray-200); overflow: hidden; transition: var(--gic-transition); display: block; text-decoration: none; }
.gic-related-card:hover { border-color: var(--gic-black); transform: translateY(-4px); box-shadow: var(--gic-shadow-lg); }
.gic-related-card-thumb { height: 140px; background: var(--gic-gray-100); overflow: hidden; }
.gic-related-card-thumb img { width: 100%; height: 100%; object-fit: cover; }
.gic-related-card-body { padding: 16px; }
.gic-related-card-title { font-size: 15px; font-weight: 700; line-height: 1.5; margin-bottom: 8px; color: var(--gic-black); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.gic-related-card-meta { font-size: 13px; color: var(--gic-gray-600); display: flex; gap: 12px; }

/* サイドバー */
.gic-sidebar-section { border: 1px solid var(--gic-gray-200); background: var(--gic-white); }
.gic-sidebar-header { padding: 16px 20px; background: var(--gic-gray-50); border-bottom: 1px solid var(--gic-gray-200); }
.gic-sidebar-title { font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px; margin: 0; }
.gic-sidebar-title svg { width: 16px; height: 16px; }
.gic-sidebar-body { padding: 16px 20px; }

/* AIチャット */
.gic-ai-section { border: 2px solid var(--gic-black); }
.gic-ai-section .gic-sidebar-header { background: var(--gic-black); border-bottom: none; }
.gic-ai-section .gic-sidebar-title { color: var(--gic-white); }
.gic-ai-body { padding: 0; display: flex; flex-direction: column; height: 480px; background: var(--gic-white); }
.gic-ai-messages { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 16px; background: var(--gic-gray-50); }
.gic-ai-msg { display: flex; gap: 12px; max-width: 90%; }
.gic-ai-msg.user { align-self: flex-end; flex-direction: row-reverse; }
.gic-ai-avatar { width: 36px; height: 36px; min-width: 36px; background: var(--gic-black); color: var(--gic-white); font-size: 12px; font-weight: 900; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.gic-ai-msg.user .gic-ai-avatar { background: var(--gic-gray-400); }
.gic-ai-bubble { padding: 12px 16px; background: var(--gic-white); border: 1px solid var(--gic-gray-200); color: var(--gic-black); font-size: 14px; line-height: 1.7; }
.gic-ai-msg.user .gic-ai-bubble { background: var(--gic-black); border-color: var(--gic-black); color: var(--gic-white); }
.gic-ai-input-area { padding: 16px; border-top: 1px solid var(--gic-gray-200); background: var(--gic-white); }
.gic-ai-input-wrap { display: flex; gap: 8px; }
.gic-ai-input { flex: 1; padding: 12px 16px; background: var(--gic-white); border: 2px solid var(--gic-gray-300); color: var(--gic-black); font-size: 14px; font-family: inherit; resize: none; min-height: 44px; max-height: 100px; }
.gic-ai-input::placeholder { color: var(--gic-gray-500); }
.gic-ai-input:focus { outline: none; border-color: var(--gic-black); }
.gic-ai-send { width: 44px; height: 44px; min-width: 44px; background: var(--gic-black); display: flex; align-items: center; justify-content: center; transition: var(--gic-transition); flex-shrink: 0; }
.gic-ai-send:hover { background: var(--gic-gray-800); }
.gic-ai-send:disabled { opacity: 0.5; cursor: not-allowed; }
.gic-ai-send svg { width: 18px; height: 18px; color: var(--gic-white); }
.gic-ai-suggestions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.gic-ai-chip { padding: 8px 12px; background: var(--gic-white); border: 1px solid var(--gic-gray-300); color: var(--gic-gray-700); font-size: 12px; font-weight: 600; cursor: pointer; transition: var(--gic-transition); }
.gic-ai-chip:hover { background: var(--gic-black); border-color: var(--gic-black); color: var(--gic-white); }

/* 目次 */
.gic-toc-nav { font-size: 14px; }
.gic-toc-nav ul { list-style: none; margin: 0; padding: 0; }
.gic-toc-nav li { margin: 8px 0; }
.gic-toc-nav a { color: var(--gic-gray-600); text-decoration: none; display: block; padding: 6px 0; transition: color 0.2s; line-height: 1.5; }
.gic-toc-nav a:hover { color: var(--gic-black); }
.gic-toc-nav .toc-h3 { padding-left: 16px; font-size: 13px; }

/* 人気リスト */
.gic-popular-list { display: flex; flex-direction: column; }
.gic-popular-item { border-bottom: 1px solid var(--gic-gray-100); }
.gic-popular-item:last-child { border-bottom: none; }
.gic-popular-link { display: flex; align-items: center; gap: 12px; padding: 12px 0; transition: var(--gic-transition); text-decoration: none; }
.gic-popular-link:hover { padding-left: 8px; }
.gic-popular-rank { width: 28px; height: 28px; min-width: 28px; background: var(--gic-gray-100); font-size: 12px; font-weight: 900; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.gic-popular-rank.rank-1 { background: var(--gic-accent); color: var(--gic-black); }
.gic-popular-rank.rank-2 { background: var(--gic-gray-400); color: var(--gic-white); }
.gic-popular-rank.rank-3 { background: #CD7F32; color: var(--gic-white); }
.gic-popular-content { flex: 1; min-width: 0; }
.gic-popular-title { font-size: 14px; font-weight: 600; line-height: 1.4; color: var(--gic-black); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.gic-popular-views { font-size: 12px; color: var(--gic-gray-600); margin-top: 2px; }

/* モバイルFAB */
.gic-mobile-fab { display: none; position: fixed; bottom: calc(var(--gic-mobile-banner) + 20px); right: 16px; z-index: 100; }
@media (max-width: 1100px) { .gic-mobile-fab { display: block; } }
.gic-fab-btn { width: 60px; height: 60px; background: var(--gic-black); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; box-shadow: var(--gic-shadow-lg); transition: var(--gic-transition); border-radius: 50%; }
.gic-fab-btn:hover { transform: scale(1.05); }
.gic-fab-btn svg { width: 24px; height: 24px; color: var(--gic-white); }
.gic-fab-btn span { font-size: 10px; font-weight: 700; color: var(--gic-white); }

/* モバイルパネル */
.gic-mobile-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 998; opacity: 0; visibility: hidden; transition: var(--gic-transition); }
.gic-mobile-overlay.active { opacity: 1; visibility: visible; }
@media (max-width: 1100px) { .gic-mobile-overlay { display: block; } }
.gic-mobile-panel { display: none; position: fixed; bottom: var(--gic-mobile-banner); left: 0; right: 0; background: var(--gic-white); max-height: calc(85vh - var(--gic-mobile-banner)); z-index: 999; transform: translateY(100%); visibility: hidden; transition: 0.4s cubic-bezier(0.16, 1, 0.3, 1); flex-direction: column; border-top-left-radius: 16px; border-top-right-radius: 16px; box-shadow: 0 -4px 20px rgba(0,0,0,0.15); }
.gic-mobile-panel.active { transform: translateY(0); visibility: visible; }
@media (max-width: 1100px) { .gic-mobile-panel { display: flex; } }
.gic-panel-handle { width: 40px; height: 4px; background: var(--gic-gray-300); margin: 12px auto; border-radius: 2px; }
.gic-panel-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; border-bottom: 1px solid var(--gic-gray-200); }
.gic-panel-title { font-size: 18px; font-weight: 900; margin: 0; }
.gic-panel-close { width: 36px; height: 36px; background: var(--gic-gray-100); display: flex; align-items: center; justify-content: center; border-radius: 50%; }
.gic-panel-close svg { width: 20px; height: 20px; color: var(--gic-gray-600); }
.gic-panel-tabs { display: flex; border-bottom: 1px solid var(--gic-gray-200); }
.gic-panel-tab { flex: 1; padding: 12px; font-size: 14px; font-weight: 600; color: var(--gic-gray-500); border-bottom: 2px solid transparent; margin-bottom: -1px; transition: var(--gic-transition); background: none; }
.gic-panel-tab.active { color: var(--gic-black); border-bottom-color: var(--gic-black); }
.gic-panel-content { flex: 1; overflow-y: auto; padding: 20px; -webkit-overflow-scrolling: touch; }
.gic-panel-content-tab { display: none; }
.gic-panel-content-tab.active { display: block; }

/* トースト */
.gic-toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: var(--gic-black); color: var(--gic-white); padding: 12px 24px; border-radius: 4px; font-size: 14px; font-weight: 600; z-index: 10000; opacity: 0; visibility: hidden; transition: 0.3s; }
.gic-toast.show { opacity: 1; visibility: visible; bottom: 40px; }

/* 印刷 */
@media print {
    .gic-sidebar, .gic-mobile-fab, .gic-mobile-overlay, .gic-mobile-panel, .gic-progress, .gic-related, .gic-ai-section, .gic-cta, .gic-share { display: none !important; }
    .gic-layout { grid-template-columns: 1fr; }
    .gic-page { padding-bottom: 0 !important; }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
}
:focus-visible { outline: 3px solid var(--gic-accent); outline-offset: 2px; }
.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0; }
</style>

<?php wp_head(); ?>
</head>
<body <?php body_class('gic-page'); ?>>
<?php wp_body_open(); ?>

<a href="#main-content" class="gic-skip-link">メインコンテンツへスキップ</a>
<div class="gic-progress" id="progressBar"></div>

<!-- パンくず -->
<nav class="gic-breadcrumb" aria-label="パンくずリスト">
    <ol class="gic-breadcrumb-list">
        <?php foreach ($breadcrumbs as $i => $crumb): ?>
        <li>
            <?php if ($i < count($breadcrumbs) - 1): ?>
            <a href="<?php echo esc_url($crumb['url']); ?>" class="gic-breadcrumb-link"><?php echo esc_html($crumb['name']); ?></a>
            <span class="gic-breadcrumb-sep" aria-hidden="true">›</span>
            <?php else: ?>
            <span class="gic-breadcrumb-current"><?php echo esc_html($crumb['name']); ?></span>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ol>
</nav>

<div class="gic-page">
    <div class="gic-container">
        
        <!-- ヒーロー -->
        <header class="gic-hero">
            <div class="gic-hero-badges">
                <?php if (!empty($categories)): ?>
                    <?php foreach (array_slice($categories, 0, 2) as $cat): 
                        $cat_link = get_term_link($cat);
                        if (!is_wp_error($cat_link)):
                    ?>
                    <a href="<?php echo esc_url($cat_link); ?>" class="gic-badge gic-badge-category"><?php echo esc_html($cat->name); ?></a>
                    <?php endif; endforeach; ?>
                <?php endif; ?>
                <span class="gic-badge gic-badge-<?php echo esc_attr($difficulty['class']); ?>"><?php echo esc_html($difficulty['label']); ?></span>
                <?php if ($column['is_featured']): ?><span class="gic-badge gic-badge-featured">注目</span><?php endif; ?>
                <?php if ($column['is_new']): ?><span class="gic-badge gic-badge-new">NEW</span><?php endif; ?>
                <?php if ($freshness_class): ?><span class="gic-badge gic-badge-<?php echo $freshness_class; ?>"><?php echo $freshness_label; ?></span><?php endif; ?>
            </div>
            <h1 class="gic-hero-title"><?php echo esc_html($post_title); ?></h1>
            <div class="gic-hero-meta">
                <span class="gic-hero-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <time datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date('Y年n月j日'); ?></time>
                </span>
                <span class="gic-hero-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    約<?php echo intval($column['read_time']); ?>分で読めます
                </span>
                <span class="gic-hero-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <?php echo number_format($column['view_count']); ?>回閲覧
                </span>
            </div>
        </header>

        <!-- メトリクス -->
        <section class="gic-metrics" aria-label="記事情報">
            <div class="gic-metric">
                <div class="gic-metric-label">読了時間</div>
                <div class="gic-metric-value highlight">約<?php echo intval($column['read_time']); ?>分</div>
                <div class="gic-metric-sub"><?php echo number_format(mb_strlen(strip_tags($post_content), 'UTF-8')); ?>文字</div>
            </div>
            <div class="gic-metric">
                <div class="gic-metric-label">難易度</div>
                <div class="gic-metric-value"><?php echo esc_html($difficulty['label']); ?></div>
                <div class="gic-metric-stars">
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                    <svg class="gic-metric-star <?php echo $i <= $difficulty['level'] ? 'active' : ''; ?>" viewBox="0 0 24 24" width="12" height="12"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="gic-metric">
                <div class="gic-metric-label">閲覧数</div>
                <div class="gic-metric-value"><?php echo number_format($column['view_count']); ?></div>
                <div class="gic-metric-sub">累計</div>
            </div>
            <div class="gic-metric">
                <div class="gic-metric-label">関連補助金</div>
                <div class="gic-metric-value"><?php echo count($related_grants); ?>件</div>
                <div class="gic-metric-sub">募集中含む</div>
            </div>
        </section>

        <div class="gic-layout">
            <main class="gic-main" id="main-content">
                
                <!-- AI要約 -->
                <?php if ($column['ai_summary']): ?>
                <section class="gic-summary" id="summary">
                    <div class="gic-summary-header">
                        <div class="gic-summary-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                        </div>
                        <span class="gic-summary-label">AI要約</span>
                        <span class="gic-summary-badge">30秒で理解</span>
                    </div>
                    <p class="gic-summary-text"><?php echo nl2br(esc_html($column['ai_summary'])); ?></p>
                </section>
                <?php endif; ?>

                <!-- 対象読者 -->
                <?php if (!empty($column['target_audience'])): ?>
                <aside class="gic-audience">
                    <h2 class="gic-audience-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        この記事はこんな方におすすめ
                    </h2>
                    <ul class="gic-audience-list">
                        <?php foreach ($column['target_audience'] as $audience): 
                            if (!isset($audience_labels[$audience])) continue;
                            $aud = $audience_labels[$audience];
                        ?>
                        <li class="gic-audience-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                            <?php echo esc_html($aud['label']); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </aside>
                <?php endif; ?>

                <!-- アイキャッチ -->
                <?php if (has_post_thumbnail()): ?>
                <figure class="gic-thumbnail">
                    <?php the_post_thumbnail('large', array('alt' => esc_attr($post_title))); ?>
                </figure>
                <?php endif; ?>

                <!-- 記事本文 -->
                <article class="gic-section" id="content">
                    <div class="gic-content">
                        <?php echo apply_filters('the_content', $post_content); ?>
                    </div>
                </article>

                <!-- ポイントまとめ -->
                <?php if (!empty($key_points_array)): ?>
                <section class="gic-keypoints" id="keypoints">
                    <h2 class="gic-keypoints-title">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="#FFD700" stroke="#111" stroke-width="1.5" style="width:24px;height:24px;min-width:24px;max-width:24px;">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        <span>この記事のポイント</span>
                    </h2>
                    <ul class="gic-keypoints-list">
                        <?php foreach ($key_points_array as $i => $point_text): ?>
                        <li class="gic-keypoints-item">
                            <span class="gic-keypoints-num"><?php echo ($i + 1); ?></span>
                            <span class="gic-keypoints-text"><?php echo esc_html($point_text); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>

                <!-- CTA -->
                <section class="gic-cta">
                    <div class="gic-cta-inner">
                        <div class="gic-cta-icon">
                            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" style="width:32px;height:32px;">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                <circle cx="12" cy="12" r="2" fill="currentColor"/>
                            </svg>
                        </div>
                        <h2 class="gic-cta-title">あなたに合う補助金を今すぐ見つけましょう</h2>
                        <p class="gic-cta-desc">AI診断で最適な補助金を提案。助成金インサイトであなたのビジネスに最適な支援制度を見つけましょう。</p>
                        <div class="gic-cta-buttons">
                            <a href="<?php echo esc_url(home_url('/subsidy-diagnosis/')); ?>" class="gic-cta-btn gic-cta-btn-primary">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;">
                                    <path d="M9 11l3 3L22 4"/>
                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                </svg>
                                <span>AIで診断する</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/grants/')); ?>" class="gic-cta-btn gic-cta-btn-secondary">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;">
                                    <circle cx="11" cy="11" r="8"/>
                                    <path d="m21 21-4.35-4.35"/>
                                </svg>
                                <span>一覧から探す</span>
                            </a>
                        </div>
                    </div>
                </section>

                <!-- 関連補助金 -->
                <?php if (!empty($related_grants)): ?>
                <section class="gic-section" id="grants">
                    <header class="gic-section-header">
                        <svg class="gic-section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                        <h2 class="gic-section-title">関連する補助金</h2>
                        <span class="gic-section-en">Related Grants</span>
                    </header>
                    <div class="gic-grants-grid">
                        <?php foreach ($related_grants as $grant): ?>
                        <a href="<?php echo esc_url($grant['permalink']); ?>" class="gic-grant-card">
                            <span class="gic-grant-badge <?php echo $grant['application_status'] === 'open' ? 'open' : 'closed'; ?>">
                                <?php echo $grant['application_status'] === 'open' ? '募集中' : '募集終了'; ?>
                            </span>
                            <h3 class="gic-grant-title"><?php echo esc_html($grant['title']); ?></h3>
                            <div class="gic-grant-meta">
                                <?php if ($grant['max_amount']): ?><span><strong><?php echo esc_html($grant['max_amount']); ?></strong></span><?php endif; ?>
                                <?php if ($grant['deadline']): ?><span><?php echo esc_html($grant['deadline']); ?></span><?php endif; ?>
                            </div>
                            <span class="gic-grant-link">詳細を見る →</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- FAQ -->
                <?php if (!empty($faq_items)): ?>
                <section class="gic-section" id="faq">
                    <header class="gic-section-header">
                        <svg class="gic-section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <h2 class="gic-section-title">よくある質問</h2>
                        <span class="gic-section-en">FAQ</span>
                    </header>
                    <div class="gic-faq-list">
                        <?php foreach ($faq_items as $faq): ?>
                        <details class="gic-faq-item">
                            <summary class="gic-faq-question">
                                <span class="gic-faq-q-mark">Q</span>
                                <span class="gic-faq-question-text"><?php echo esc_html($faq['question']); ?></span>
                                <svg class="gic-faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </summary>
                            <div class="gic-faq-answer"><?php echo nl2br(esc_html($faq['answer'])); ?></div>
                        </details>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- タグ -->
                <?php if (!empty($tags)): ?>
                <nav class="gic-tags">
                    <?php foreach ($tags as $tag): 
                        $tag_link = get_term_link($tag);
                        if (is_wp_error($tag_link)) continue;
                    ?>
                    <a href="<?php echo esc_url($tag_link); ?>" class="gic-tag">#<?php echo esc_html($tag->name); ?></a>
                    <?php endforeach; ?>
                </nav>
                <?php endif; ?>

                <!-- シェア -->
                <aside class="gic-share">
                    <h2 class="gic-share-title">この記事をシェア</h2>
                    <div class="gic-share-buttons">
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($canonical_url); ?>&text=<?php echo urlencode($post_title); ?>" target="_blank" rel="noopener noreferrer" class="gic-share-btn">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            X
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($canonical_url); ?>" target="_blank" rel="noopener noreferrer" class="gic-share-btn">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            Facebook
                        </a>
                        <a href="https://social-plugins.line.me/lineit/share?url=<?php echo urlencode($canonical_url); ?>" target="_blank" rel="noopener noreferrer" class="gic-share-btn">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>
                            LINE
                        </a>
                    </div>
                </aside>

                <!-- 情報ソース -->
                <div class="gic-source-card">
                    <div class="gic-source-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        <span class="gic-source-label">情報ソース</span>
                    </div>
                    <div class="gic-source-body">
                        <div class="gic-source-info">
                            <div class="gic-source-name"><?php echo esc_html($column['source_name'] ?: '補助金インサイト編集部'); ?></div>
                            <div class="gic-source-verified">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                <?php echo esc_html($last_verified_display); ?> 確認済み
                            </div>
                        </div>
                        <?php if ($column['source_url']): ?>
                        <a href="<?php echo esc_url($column['source_url']); ?>" class="gic-source-link" target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            公式ページを確認
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="gic-source-footer">※最新情報は必ず公式サイトでご確認ください。本ページの情報は参考情報です。</div>
                </div>

                <!-- 監修者 -->
                <aside class="gic-supervisor">
                    <div class="gic-supervisor-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        監修・編集
                    </div>
                    <div class="gic-supervisor-content">
                        <div class="gic-supervisor-avatar">
                            <?php if (!empty($column['supervisor_image']) && isset($column['supervisor_image']['url'])): ?>
                            <img src="<?php echo esc_url($column['supervisor_image']['url']); ?>" alt="<?php echo esc_attr($column['supervisor_name']); ?>" loading="lazy" width="72" height="72">
                            <?php else: ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="32" height="32"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <?php endif; ?>
                        </div>
                        <div class="gic-supervisor-info">
                            <div class="gic-supervisor-name"><?php echo esc_html($column['supervisor_name']); ?></div>
                            <div class="gic-supervisor-title"><?php echo esc_html($column['supervisor_title']); ?></div>
                            <p class="gic-supervisor-bio"><?php echo esc_html($column['supervisor_profile']); ?></p>
                            <?php if (!empty($column['supervisor_credentials'])): ?>
                            <div class="gic-supervisor-credentials">
                                <?php foreach ($column['supervisor_credentials'] as $cred): 
                                    if (!is_array($cred) || empty($cred['credential'])) continue;
                                ?>
                                <span class="gic-supervisor-credential"><?php echo esc_html($cred['credential']); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </aside>

            </main>

            <!-- サイドバー -->
            <aside class="gic-sidebar">
                
                <!-- AIアシスタント -->
                <section class="gic-sidebar-section gic-ai-section">
                    <header class="gic-sidebar-header">
                        <h3 class="gic-sidebar-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            AIアシスタント
                        </h3>
                    </header>
                    <div class="gic-ai-body">
                        <div class="gic-ai-messages" id="aiMessages">
                            <div class="gic-ai-msg">
                                <div class="gic-ai-avatar">AI</div>
                                <div class="gic-ai-bubble">この記事について何でもお聞きください。</div>
                            </div>
                        </div>
                        <div class="gic-ai-input-area">
                            <div class="gic-ai-input-wrap">
                                <textarea class="gic-ai-input" id="aiInput" placeholder="質問を入力..." rows="1"></textarea>
                                <button class="gic-ai-send" id="aiSend" type="button">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                </button>
                            </div>
                            <div class="gic-ai-suggestions">
                                <button class="gic-ai-chip" data-q="この記事のポイントは？">ポイント</button>
                                <button class="gic-ai-chip" data-q="関連する補助金は？">補助金</button>
                                <button class="gic-ai-chip" data-q="申請方法を教えて">申請方法</button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 目次 -->
                <section class="gic-sidebar-section">
                    <header class="gic-sidebar-header">
                        <h3 class="gic-sidebar-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            目次
                        </h3>
                    </header>
                    <div class="gic-sidebar-body">
                        <nav class="gic-toc-nav" id="tocNav"></nav>
                    </div>
                </section>

                <!-- 人気コラム -->
                <?php if (!empty($popular_columns)): ?>
                <section class="gic-sidebar-section">
                    <header class="gic-sidebar-header">
                        <h3 class="gic-sidebar-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                            人気のコラム
                        </h3>
                    </header>
                    <div class="gic-sidebar-body">
                        <div class="gic-popular-list">
                            <?php foreach ($popular_columns as $i => $pop): 
                                $rank_class = $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : ''));
                            ?>
                            <div class="gic-popular-item">
                                <a href="<?php echo esc_url($pop['permalink']); ?>" class="gic-popular-link">
                                    <span class="gic-popular-rank <?php echo $rank_class; ?>"><?php echo $i + 1; ?></span>
                                    <div class="gic-popular-content">
                                        <div class="gic-popular-title"><?php echo esc_html($pop['title']); ?></div>
                                        <div class="gic-popular-views"><?php echo number_format($pop['view_count']); ?>回閲覧</div>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

            </aside>
        </div>
    </div>

    <!-- 関連コラム -->
    <?php if (!empty($related_columns)): ?>
    <section class="gic-related">
        <div class="gic-container">
            <header class="gic-related-header">
                <p class="gic-related-en">Related Columns</p>
                <h2 class="gic-related-title">関連するコラム</h2>
            </header>
            <div class="gic-related-grid">
                <?php foreach ($related_columns as $rel): ?>
                <a href="<?php echo esc_url($rel['permalink']); ?>" class="gic-related-card">
                    <div class="gic-related-card-thumb">
                        <?php if ($rel['thumbnail']): ?>
                        <img src="<?php echo esc_url($rel['thumbnail']); ?>" alt="<?php echo esc_attr($rel['title']); ?>" loading="lazy">
                        <?php endif; ?>
                    </div>
                    <div class="gic-related-card-body">
                        <h3 class="gic-related-card-title"><?php echo esc_html($rel['title']); ?></h3>
                        <div class="gic-related-card-meta">
                            <span><?php echo esc_html($rel['date']); ?></span>
                            <span>約<?php echo intval($rel['read_time']); ?>分</span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</div>

<!-- モバイルFAB -->
<div class="gic-mobile-fab">
    <button class="gic-fab-btn" id="mobileAiBtn" type="button">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <span>AI相談</span>
    </button>
</div>

<!-- モバイルパネル -->
<div class="gic-mobile-overlay" id="mobileOverlay"></div>
<div class="gic-mobile-panel" id="mobilePanel">
    <div class="gic-panel-handle"></div>
    <header class="gic-panel-header">
        <h2 class="gic-panel-title">AIアシスタント</h2>
        <button class="gic-panel-close" id="panelClose" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </header>
    <div class="gic-panel-tabs">
        <button class="gic-panel-tab active" data-tab="ai">AI質問</button>
        <button class="gic-panel-tab" data-tab="toc">目次</button>
        <button class="gic-panel-tab" data-tab="action">アクション</button>
    </div>
    <div class="gic-panel-content">
        <div class="gic-panel-content-tab active" id="tabAi">
            <div class="gic-ai-messages" id="mobileAiMessages" style="min-height: 200px; max-height: 300px; margin-bottom: 16px;">
                <div class="gic-ai-msg">
                    <div class="gic-ai-avatar">AI</div>
                    <div class="gic-ai-bubble">この記事について何でもお聞きください。</div>
                </div>
            </div>
            <div class="gic-ai-input-wrap">
                <textarea class="gic-ai-input" id="mobileAiInput" placeholder="質問を入力..." rows="2"></textarea>
                <button class="gic-ai-send" id="mobileAiSend" type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </button>
            </div>
            <div class="gic-ai-suggestions" style="margin-top: 12px;">
                <button class="gic-ai-chip" data-q="この記事のポイントは？">ポイント</button>
                <button class="gic-ai-chip" data-q="関連する補助金は？">補助金</button>
            </div>
        </div>
        <div class="gic-panel-content-tab" id="tabToc">
            <nav class="gic-toc-nav" id="mobileTocNav"></nav>
        </div>
        <div class="gic-panel-content-tab" id="tabAction">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="<?php echo esc_url(home_url('/subsidy-diagnosis/')); ?>" class="gic-cta-btn gic-cta-btn-primary" style="justify-content: center;">AIで補助金診断</a>
                <a href="<?php echo esc_url(home_url('/grants/')); ?>" class="gic-cta-btn gic-cta-btn-secondary" style="justify-content: center; background: var(--gic-white); border-color: var(--gic-gray-300); color: var(--gic-black);">補助金一覧を見る</a>
            </div>
        </div>
    </div>
</div>

<div class="gic-toast" id="gicToast"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    var CONFIG = {
        postId: <?php echo $post_id; ?>,
        ajaxUrl: '<?php echo admin_url("admin-ajax.php"); ?>',
        nonce: '<?php echo wp_create_nonce("gic_ai_nonce"); ?>',
        url: '<?php echo esc_js($canonical_url); ?>',
        title: <?php echo json_encode($post_title, JSON_UNESCAPED_UNICODE); ?>
    };
    
    // プログレスバー
    var progress = document.getElementById('progressBar');
    function updateProgress() {
        var h = document.documentElement.scrollHeight - window.innerHeight;
        var p = h > 0 ? Math.min(100, (window.pageYOffset / h) * 100) : 0;
        if (progress) progress.style.width = p + '%';
    }
    window.addEventListener('scroll', updateProgress, { passive: true });
    
    // 目次生成
    function generateTOC() {
        var content = document.querySelector('.gic-content');
        var tocNav = document.getElementById('tocNav');
        var mobileTocNav = document.getElementById('mobileTocNav');
        
        if (!content) return;
        
        var headings = content.querySelectorAll('h2, h3');
        if (headings.length === 0) {
            if (tocNav) tocNav.innerHTML = '<p style="color: #888; font-size: 14px;">目次がありません</p>';
            if (mobileTocNav) mobileTocNav.innerHTML = '<p style="color: #888; font-size: 14px;">目次がありません</p>';
            return;
        }
        
        var tocHTML = '<ul>';
        headings.forEach(function(heading, index) {
            var id = 'heading-' + index;
            heading.id = id;
            var level = heading.tagName === 'H2' ? 'toc-h2' : 'toc-h3';
            tocHTML += '<li><a href="#' + id + '" class="' + level + '">' + heading.textContent + '</a></li>';
        });
        tocHTML += '</ul>';
        
        if (tocNav) tocNav.innerHTML = tocHTML;
        if (mobileTocNav) {
            mobileTocNav.innerHTML = tocHTML;
            mobileTocNav.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', closePanel);
            });
        }
    }
    generateTOC();
    
    // AI送信
    function sendAiMessage(input, container, btn) {
        var question = input.value.trim();
        if (!question) return;
        
        addMessage(container, question, 'user');
        input.value = '';
        btn.disabled = true;
        
        var loadingMsg = document.createElement('div');
        loadingMsg.className = 'gic-ai-msg';
        loadingMsg.innerHTML = '<div class="gic-ai-avatar">AI</div><div class="gic-ai-bubble">考え中...</div>';
        container.appendChild(loadingMsg);
        container.scrollTop = container.scrollHeight;
        
        var formData = new FormData();
        formData.append('action', 'gic_ai_chat');
        formData.append('nonce', CONFIG.nonce);
        formData.append('post_id', CONFIG.postId);
        formData.append('question', question);
        
        fetch(CONFIG.ajaxUrl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                loadingMsg.remove();
                if (data.success && data.data && data.data.answer) {
                    addMessage(container, data.data.answer, 'ai');
                } else {
                    addMessage(container, generateFallback(question), 'ai');
                }
            })
            .catch(function() {
                loadingMsg.remove();
                addMessage(container, generateFallback(question), 'ai');
            })
            .finally(function() { btn.disabled = false; });
    }
    
    function addMessage(container, text, type) {
        var msg = document.createElement('div');
        msg.className = 'gic-ai-msg' + (type === 'user' ? ' user' : '');
        msg.innerHTML = '<div class="gic-ai-avatar">' + (type === 'user' ? 'You' : 'AI') + '</div><div class="gic-ai-bubble">' + escapeHtml(text).replace(/\n/g, '<br>') + '</div>';
        container.appendChild(msg);
        container.scrollTop = container.scrollHeight;
    }
    
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function generateFallback(question) {
        var q = question.toLowerCase();
        if (q.indexOf('ポイント') !== -1) return 'この記事のポイントについては、「この記事のポイント」セクションをご確認ください。';
        if (q.indexOf('補助金') !== -1) return '関連する補助金については、「関連する補助金」セクションをご確認ください。';
        if (q.indexOf('申請') !== -1) return '補助金の申請方法については、各補助金の詳細ページでご確認いただけます。';
        return 'ご質問ありがとうございます。記事の内容をご確認いただくか、より具体的な質問をお聞かせください。';
    }
    
    // デスクトップAI
    var aiInput = document.getElementById('aiInput');
    var aiSend = document.getElementById('aiSend');
    var aiMessages = document.getElementById('aiMessages');
    
    if (aiSend && aiInput && aiMessages) {
        aiSend.addEventListener('click', function() { sendAiMessage(aiInput, aiMessages, aiSend); });
        aiInput.addEventListener('keydown', function(e) { 
            if (e.key === 'Enter' && !e.shiftKey) { 
                e.preventDefault(); 
                sendAiMessage(aiInput, aiMessages, aiSend); 
            } 
        });
    }
    
    // モバイルAI
    var mobileAiInput = document.getElementById('mobileAiInput');
    var mobileAiSend = document.getElementById('mobileAiSend');
    var mobileAiMessages = document.getElementById('mobileAiMessages');
    
    if (mobileAiSend && mobileAiInput && mobileAiMessages) {
        mobileAiSend.addEventListener('click', function() { sendAiMessage(mobileAiInput, mobileAiMessages, mobileAiSend); });
        mobileAiInput.addEventListener('keydown', function(e) { 
            if (e.key === 'Enter' && !e.shiftKey) { 
                e.preventDefault(); 
                sendAiMessage(mobileAiInput, mobileAiMessages, mobileAiSend); 
            } 
        });
    }
    
    // AIチップ
    document.querySelectorAll('.gic-ai-chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            var q = this.dataset.q;
            if (!q) return;
            
            var isDesktop = this.closest('.gic-sidebar-section');
            var input = isDesktop ? aiInput : mobileAiInput;
            var container = isDesktop ? aiMessages : mobileAiMessages;
            var btn = isDesktop ? aiSend : mobileAiSend;
            
            if (input) {
                input.value = q;
                sendAiMessage(input, container, btn);
            }
        });
    });
    
     // モバイルパネル
    var mobileAiBtn = document.getElementById('mobileAiBtn');
    var mobileOverlay = document.getElementById('mobileOverlay');
    var mobilePanel = document.getElementById('mobilePanel');
    var panelClose = document.getElementById('panelClose');
    var panelTabs = document.querySelectorAll('.gic-panel-tab');
    var panelContents = document.querySelectorAll('.gic-panel-content-tab');
    
    function openPanel() {
        if (mobileOverlay) mobileOverlay.classList.add('active');
        if (mobilePanel) mobilePanel.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closePanel() {
        if (mobileOverlay) mobileOverlay.classList.remove('active');
        if (mobilePanel) mobilePanel.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (mobileAiBtn) mobileAiBtn.addEventListener('click', openPanel);
    if (panelClose) panelClose.addEventListener('click', closePanel);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closePanel);
    
    // Escapeキーで閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobilePanel && mobilePanel.classList.contains('active')) {
            closePanel();
        }
    });
    
    // タブ切り替え
    panelTabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var targetTab = this.dataset.tab;
            
            // タブのアクティブ状態を切り替え
            panelTabs.forEach(function(t) { 
                t.classList.remove('active'); 
            });
            this.classList.add('active');
            
            // コンテンツを切り替え
            panelContents.forEach(function(c) { 
                c.classList.remove('active'); 
            });
            
            var target = document.getElementById('tab' + targetTab.charAt(0).toUpperCase() + targetTab.slice(1));
            if (target) target.classList.add('active');
        });
    });
    
    // スワイプでパネルを閉じる
    var touchStartY = 0;
    var touchEndY = 0;
    
    if (mobilePanel) {
        mobilePanel.addEventListener('touchstart', function(e) {
            touchStartY = e.touches[0].clientY;
        }, { passive: true });
        
        mobilePanel.addEventListener('touchmove', function(e) {
            touchEndY = e.touches[0].clientY;
            var diff = touchEndY - touchStartY;
            
            // 下方向にスワイプした場合
            if (diff > 0) {
                var content = mobilePanel.querySelector('.gic-panel-content');
                if (content && content.scrollTop === 0) {
                    mobilePanel.style.transform = 'translateY(' + Math.min(diff, 200) + 'px)';
                }
            }
        }, { passive: true });
        
        mobilePanel.addEventListener('touchend', function() {
            var diff = touchEndY - touchStartY;
            
            // 100px以上下にスワイプしたらパネルを閉じる
            if (diff > 100) {
                var content = mobilePanel.querySelector('.gic-panel-content');
                if (content && content.scrollTop === 0) {
                    closePanel();
                }
            }
            
            // 位置をリセット
            mobilePanel.style.transform = '';
            touchStartY = 0;
            touchEndY = 0;
        }, { passive: true });
    }
    
    // スムーススクロール
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (href === '#') return;
            
            var target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                var top = target.getBoundingClientRect().top + window.pageYOffset - 80;
                window.scrollTo({ top: top, behavior: 'smooth' });
                
                // モバイルパネルを閉じる
                if (mobilePanel && mobilePanel.classList.contains('active')) {
                    closePanel();
                }
            }
        });
    });
    
    // トースト通知
    function showToast(msg) {
        var t = document.getElementById('gicToast');
        if (!t) return;
        t.textContent = msg;
        t.classList.add('show');
        setTimeout(function() { t.classList.remove('show'); }, 3000);
    }
    window.showToast = showToast;
    
    // 外部リンクにrel属性追加
    document.querySelectorAll('.gic-content a[href^="http"]').forEach(function(link) {
        if (link.hostname !== window.location.hostname) {
            link.setAttribute('target', '_blank');
            var rel = link.getAttribute('rel') || '';
            if (rel.indexOf('noopener') === -1) rel += ' noopener';
            if (rel.indexOf('noreferrer') === -1) rel += ' noreferrer';
            link.setAttribute('rel', rel.trim());
        }
    });
    
    // テーブルのレスポンシブ対応
    document.querySelectorAll('.gic-content table').forEach(function(table) {
        if (!table.parentElement.classList.contains('table-wrapper')) {
            var wrapper = document.createElement('div');
            wrapper.style.overflowX = 'auto';
            wrapper.style.webkitOverflowScrolling = 'touch';
            wrapper.style.marginBottom = '20px';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
    
    // 画像の遅延読み込み
    if ('IntersectionObserver' in window) {
        var imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '50px 0px' });
        
        document.querySelectorAll('img[data-src]').forEach(function(img) {
            imageObserver.observe(img);
        });
    }
    
    // 読了率トラッキング
    var readingMilestones = [25, 50, 75, 100];
    var reachedMilestones = [];
    
    function trackReading() {
        var windowHeight = window.innerHeight;
        var documentHeight = document.documentElement.scrollHeight - windowHeight;
        if (documentHeight <= 0) return;
        
        var scrolled = window.scrollY;
        var progressPercent = Math.round((scrolled / documentHeight) * 100);
        
        readingMilestones.forEach(function(milestone) {
            if (progressPercent >= milestone && reachedMilestones.indexOf(milestone) === -1) {
                reachedMilestones.push(milestone);
                
                // Google Analytics 4対応
                if (typeof gtag === 'function') {
                    gtag('event', 'reading_progress', {
                        event_category: 'engagement',
                        event_label: milestone + '%',
                        page_type: 'single_column',
                        post_id: CONFIG.postId
                    });
                }
                
                console.log('[Reading Progress] ' + milestone + '% reached');
            }
        });
    }
    
    window.addEventListener('scroll', trackReading, { passive: true });
    
    // 滞在時間トラッキング
    var startTime = Date.now();
    var timeIntervals = [30, 60, 120, 300]; // 秒
    var reportedIntervals = [];
    
    setInterval(function() {
        var elapsed = Math.floor((Date.now() - startTime) / 1000);
        
        timeIntervals.forEach(function(interval) {
            if (elapsed >= interval && reportedIntervals.indexOf(interval) === -1) {
                reportedIntervals.push(interval);
                
                if (typeof gtag === 'function') {
                    gtag('event', 'time_on_page', {
                        event_category: 'engagement',
                        event_label: interval + 's',
                        page_type: 'single_column',
                        post_id: CONFIG.postId
                    });
                }
                
                console.log('[Time on Page] ' + interval + 's reached');
            }
        });
    }, 5000);
    
    // ページ離脱時の処理
    window.addEventListener('beforeunload', function() {
        var elapsed = Math.floor((Date.now() - startTime) / 1000);
        var scrollDepth = 0;
        var documentHeight = document.documentElement.scrollHeight - window.innerHeight;
        if (documentHeight > 0) {
            scrollDepth = Math.round((window.scrollY / documentHeight) * 100);
        }
        
        // Beacon APIで送信
        if (navigator.sendBeacon) {
            var data = new FormData();
            data.append('action', 'gic_track_exit');
            data.append('post_id', CONFIG.postId);
            data.append('time_spent', elapsed);
            data.append('scroll_depth', scrollDepth);
            
            navigator.sendBeacon(CONFIG.ajaxUrl, data);
        }
    });
    
    // コードブロックのコピー機能
    document.querySelectorAll('.gic-content pre').forEach(function(pre) {
        var copyBtn = document.createElement('button');
        copyBtn.textContent = 'コピー';
        copyBtn.style.cssText = 'position: absolute; top: 8px; right: 8px; padding: 4px 10px; font-size: 12px; background: #555; color: #fff; border: none; cursor: pointer; border-radius: 3px;';
        
        pre.style.position = 'relative';
        pre.appendChild(copyBtn);
        
        copyBtn.addEventListener('click', function() {
            var code = pre.querySelector('code') || pre;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code.textContent).then(function() {
                    copyBtn.textContent = 'コピー完了!';
                    setTimeout(function() { copyBtn.textContent = 'コピー'; }, 2000);
                });
            }
        });
    });
    
    // シェアボタンのクリックトラッキング
    document.querySelectorAll('.gic-share-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var platform = 'unknown';
            if (this.href.indexOf('twitter') !== -1 || this.href.indexOf('x.com') !== -1) platform = 'twitter';
            else if (this.href.indexOf('facebook') !== -1) platform = 'facebook';
            else if (this.href.indexOf('line') !== -1) platform = 'line';
            
            if (typeof gtag === 'function') {
                gtag('event', 'share', {
                    event_category: 'social',
                    event_label: platform,
                    page_type: 'single_column',
                    post_id: CONFIG.postId
                });
            }
            
            console.log('[Share] ' + platform);
        });
    });
    
    // FAQ開閉トラッキング
    document.querySelectorAll('.gic-faq-item').forEach(function(item, index) {
        item.addEventListener('toggle', function() {
            if (this.open) {
                if (typeof gtag === 'function') {
                    gtag('event', 'faq_open', {
                        event_category: 'engagement',
                        event_label: 'FAQ #' + (index + 1),
                        page_type: 'single_column',
                        post_id: CONFIG.postId
                    });
                }
                
                console.log('[FAQ] Opened #' + (index + 1));
            }
        });
    });
    
    // 関連コンテンツのクリックトラッキング
    document.querySelectorAll('.gic-grant-card, .gic-related-card').forEach(function(card) {
        card.addEventListener('click', function() {
            var title = this.querySelector('.gic-grant-title, .gic-related-card-title');
            var titleText = title ? title.textContent : 'Unknown';
            var type = this.classList.contains('gic-grant-card') ? 'grant' : 'column';
            
            if (typeof gtag === 'function') {
                gtag('event', 'related_click', {
                    event_category: 'navigation',
                    event_label: titleText,
                    content_type: type,
                    page_type: 'single_column',
                    post_id: CONFIG.postId
                });
            }
            
            console.log('[Related Click] ' + type + ': ' + titleText);
        });
    });
    
    // オンライン/オフライン検知
    window.addEventListener('online', function() {
        console.log('[Network] Connection restored');
        var notice = document.querySelector('.gic-offline-notice');
        if (notice) notice.remove();
        showToast('インターネット接続が復旧しました');
    });
    
    window.addEventListener('offline', function() {
        console.warn('[Network] Connection lost');
        var notice = document.createElement('div');
        notice.className = 'gic-offline-notice';
        notice.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; background: #DC2626; color: white; padding: 12px 20px; text-align: center; font-size: 14px; font-weight: 600; z-index: 10000;';
        notice.innerHTML = '⚠️ インターネット接続が切断されました';
        document.body.appendChild(notice);
    });
    
    // 印刷対応
    window.addEventListener('beforeprint', function() {
        // 全てのdetailsを開く
        document.querySelectorAll('details').forEach(function(details) {
            details.setAttribute('open', '');
        });
    });
    
    // パフォーマンス計測
    if (window.performance && window.performance.timing) {
        window.addEventListener('load', function() {
            setTimeout(function() {
                var timing = window.performance.timing;
                var pageLoadTime = timing.loadEventEnd - timing.navigationStart;
                var domReadyTime = timing.domContentLoadedEventEnd - timing.navigationStart;
                
                console.log('[Performance] Page Load: ' + pageLoadTime + 'ms');
                console.log('[Performance] DOM Ready: ' + domReadyTime + 'ms');
            }, 0);
        });
    }
    
    // 初期化完了ログ
    console.log('[✓] Single Column v7.1 initialized');
    console.log('[✓] Post ID: ' + CONFIG.postId);
    console.log('[✓] Features: AI Chat, TOC, Progress Bar, Analytics, Accessibility');
});
</script>

<?php endwhile; ?>

<?php wp_footer(); ?>
</body>
</html>
