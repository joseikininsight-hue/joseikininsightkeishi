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

<?php 
// Note: CSS/JS are enqueued in functions.php via gi_enqueue_external_assets()
wp_head(); 
?>
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
                <?php if ($column['is_new']): ?><span class="gic-badge gic-badge-recent">新着</span><?php endif; ?>
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

<?php endwhile; ?>

<?php wp_footer(); ?>
</body>
</html>
