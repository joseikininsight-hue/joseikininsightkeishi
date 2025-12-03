<?php
/**
 * Archive Column Template - Perfect SEO Edition v2.1
 * コラム記事一覧ページ - 完全動作保証版
 * 
 * @package Grant_Insight_Perfect
 * @subpackage Column_System
 * @version 2.1.0 - Fixed Query & Display Edition
 */

get_header();

// ===== 基本設定 =====
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$posts_per_page = 12;

// 現在のコンテキスト判定
$queried_object = get_queried_object();
$is_category = is_tax('column_category');
$is_tag = is_tax('column_tag');

// 検索パラメータ
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$orderby_param = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';

// ===== メインクエリ構築 =====
$query_args = array(
    'post_type'      => 'column',
    'post_status'    => 'publish',
    'posts_per_page' => $posts_per_page,
    'paged'          => $paged,
);

// カテゴリアーカイブの場合
if ($is_category && $queried_object) {
    $query_args['tax_query'] = array(
        array(
            'taxonomy' => 'column_category',
            'field'    => 'term_id',
            'terms'    => $queried_object->term_id,
        ),
    );
}

// タグアーカイブの場合
if ($is_tag && $queried_object) {
    $query_args['tax_query'] = array(
        array(
            'taxonomy' => 'column_tag',
            'field'    => 'term_id',
            'terms'    => $queried_object->term_id,
        ),
    );
}

// 検索の場合
if (!empty($search_query)) {
    $query_args['s'] = $search_query;
}

// ソート順
switch ($orderby_param) {
    case 'popular':
        $query_args['meta_key'] = 'view_count';
        $query_args['orderby'] = 'meta_value_num';
        $query_args['order'] = 'DESC';
        break;
    case 'title':
        $query_args['orderby'] = 'title';
        $query_args['order'] = 'ASC';
        break;
    case 'modified':
        $query_args['orderby'] = 'modified';
        $query_args['order'] = 'DESC';
        break;
    default:
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'DESC';
}

// クエリ実行
$column_query = new WP_Query($query_args);

// ===== ページ情報設定 =====
$total_count = wp_count_posts('column')->publish;
$current_count = $column_query->found_posts;

// タイトル・説明文
if ($is_category && $queried_object) {
    $page_title = $queried_object->name . 'の記事一覧';
    $page_description = $queried_object->description ?: $queried_object->name . 'に関する補助金・助成金コラム記事一覧です。';
} elseif ($is_tag && $queried_object) {
    $page_title = '「' . $queried_object->name . '」タグの記事';
    $page_description = $queried_object->name . 'に関連する補助金・助成金コラム記事一覧です。';
} elseif (!empty($search_query)) {
    $page_title = '「' . $search_query . '」の検索結果';
    $page_description = $search_query . 'に関する補助金・助成金コラム記事の検索結果です。';
} else {
    $page_title = '補助金・助成金コラム';
    $page_description = '補助金・助成金の活用ノウハウ、申請のコツ、最新情報をお届けする専門コラムです。';
}

// ===== カテゴリ一覧取得 =====
$categories = get_terms(array(
    'taxonomy'   => 'column_category',
    'hide_empty' => true,
    'orderby'    => 'count',
    'order'      => 'DESC',
));

if (is_wp_error($categories)) {
    $categories = array();
}

// ===== サイドバー用データ =====

// アクセスランキング
$ranking_args = array(
    'post_type'      => 'column',
    'posts_per_page' => 10,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
);

// view_countがある場合はそれでソート
$has_view_count = get_posts(array(
    'post_type'      => 'column',
    'posts_per_page' => 1,
    'meta_key'       => 'view_count',
    'fields'         => 'ids',
));

if (!empty($has_view_count)) {
    $ranking_args['meta_key'] = 'view_count';
    $ranking_args['orderby'] = 'meta_value_num';
    $ranking_args['order'] = 'DESC';
}

$ranking_query = new WP_Query($ranking_args);

// 新着トピックス
$recent_query = new WP_Query(array(
    'post_type'      => 'column',
    'posts_per_page' => 5,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
));

// トレンドタグ
$trending_tags = get_terms(array(
    'taxonomy'   => 'column_tag',
    'orderby'    => 'count',
    'order'      => 'DESC',
    'number'     => 10,
    'hide_empty' => true,
));

if (is_wp_error($trending_tags)) {
    $trending_tags = array();
}

// カテゴリアイコン取得関数
if (!function_exists('gi_get_column_category_icon')) {
    function gi_get_column_category_icon($slug) {
        $icons = array(
            'application-tips'   => '💡',
            'system-explanation' => '📚',
            'news'               => '📰',
            'success-stories'    => '🏆',
            'other'              => '📝',
        );
        return isset($icons[$slug]) ? $icons[$slug] : '📄';
    }
}

// 難易度ラベル取得関数
if (!function_exists('gi_get_column_difficulty_label')) {
    function gi_get_column_difficulty_label($difficulty) {
        $labels = array(
            'beginner'     => '初心者向け',
            'intermediate' => '中級者向け',
            'advanced'     => '上級者向け',
        );
        return isset($labels[$difficulty]) ? $labels[$difficulty] : '初心者向け';
    }
}
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body <?php body_class(); ?>>

<main class="column-archive" id="main-content" role="main">

    <!-- パンくずリスト -->
    <nav class="col-breadcrumb" aria-label="パンくずリスト">
        <div class="col-container">
            <ol class="col-breadcrumb-list">
                <li><a href="<?php echo esc_url(home_url('/')); ?>">ホーム</a></li>
                <li class="separator">›</li>
                <?php if ($is_category || $is_tag): ?>
                    <li><a href="<?php echo esc_url(get_post_type_archive_link('column')); ?>">コラム</a></li>
                    <li class="separator">›</li>
                    <li class="current"><?php echo esc_html($queried_object->name); ?></li>
                <?php else: ?>
                    <li class="current">コラム</li>
                <?php endif; ?>
            </ol>
        </div>
    </nav>

    <!-- ヒーローセクション -->
    <header class="col-hero">
        <div class="col-container">
            <div class="col-hero-content">
                <span class="col-hero-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    補助金・助成金コラム
                </span>
                
                <h1 class="col-hero-title"><?php echo esc_html($page_title); ?></h1>
                <p class="col-hero-desc"><?php echo esc_html($page_description); ?></p>
                
                <div class="col-hero-meta">
                    <span class="meta-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        </svg>
                        <strong><?php echo number_format($total_count); ?></strong> 記事
                    </span>
                    <span class="meta-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <?php echo date('Y'); ?>年度版
                    </span>
                    <span class="meta-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        毎日更新
                    </span>
                </div>
            </div>
        </div>
    </header>

    <!-- メインコンテンツ -->
    <div class="col-container col-layout">
        
        <!-- 左カラム -->
        <div class="col-main">
            
            <!-- 検索バー -->
            <div class="col-search-box">
                <form method="get" action="<?php echo esc_url(get_post_type_archive_link('column')); ?>" class="col-search-form">
                    <div class="col-search-input-wrap">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        <input type="search" 
                               name="s" 
                               placeholder="キーワードで記事を検索..." 
                               value="<?php echo esc_attr($search_query); ?>"
                               class="col-search-input">
                        <input type="hidden" name="post_type" value="column">
                        <button type="submit" class="col-search-btn">検索</button>
                    </div>
                </form>
            </div>

            <!-- カテゴリタブ -->
            <?php if (!empty($categories)): ?>
            <nav class="col-tabs-nav">
                <ul class="col-tabs">
                    <li>
                        <a href="<?php echo esc_url(get_post_type_archive_link('column')); ?>" 
                           class="col-tab <?php echo (!$is_category && !$is_tag && empty($search_query)) ? 'active' : ''; ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="6" x2="21" y2="6"/>
                                <line x1="8" y1="12" x2="21" y2="12"/>
                                <line x1="8" y1="18" x2="21" y2="18"/>
                                <line x1="3" y1="6" x2="3.01" y2="6"/>
                                <line x1="3" y1="12" x2="3.01" y2="12"/>
                                <line x1="3" y1="18" x2="3.01" y2="18"/>
                            </svg>
                            すべて
                            <span class="tab-count"><?php echo number_format($total_count); ?></span>
                        </a>
                    </li>
                    <?php foreach ($categories as $cat): 
                        $is_active = ($is_category && $queried_object && $queried_object->term_id === $cat->term_id);
                    ?>
                        <li>
                            <a href="<?php echo esc_url(get_term_link($cat)); ?>" 
                               class="col-tab <?php echo $is_active ? 'active' : ''; ?>">
                                <span class="tab-emoji"><?php echo gi_get_column_category_icon($cat->slug); ?></span>
                                <?php echo esc_html($cat->name); ?>
                                <span class="tab-count"><?php echo number_format($cat->count); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endif; ?>

            <!-- フィルターバー -->
            <div class="col-filter-bar">
                <div class="filter-info">
                    <?php if (!empty($search_query)): ?>
                        <span class="search-result-text">
                            「<strong><?php echo esc_html($search_query); ?></strong>」の検索結果: 
                            <strong><?php echo number_format($current_count); ?></strong>件
                        </span>
                        <a href="<?php echo esc_url(get_post_type_archive_link('column')); ?>" class="clear-search">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                            クリア
                        </a>
                    <?php else: ?>
                        <?php 
                        $from = $current_count > 0 ? (($paged - 1) * $posts_per_page) + 1 : 0;
                        $to = min($paged * $posts_per_page, $current_count);
                        ?>
                        <span class="showing-text">
                            <?php echo number_format($from); ?>〜<?php echo number_format($to); ?>件を表示（全<?php echo number_format($current_count); ?>件）
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="sort-controls">
                    <label for="orderby-select" class="sort-label">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="4" y1="21" x2="4" y2="14"/>
                            <line x1="4" y1="10" x2="4" y2="3"/>
                            <line x1="12" y1="21" x2="12" y2="12"/>
                            <line x1="12" y1="8" x2="12" y2="3"/>
                            <line x1="20" y1="21" x2="20" y2="16"/>
                            <line x1="20" y1="12" x2="20" y2="3"/>
                        </svg>
                        並び順:
                    </label>
                    <form method="get" class="sort-form">
                        <?php foreach ($_GET as $key => $value): 
                            if ($key !== 'orderby'): ?>
                            <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                        <?php endif; endforeach; ?>
                        <select name="orderby" id="orderby-select" class="sort-select" onchange="this.form.submit()">
                            <option value="date" <?php selected($orderby_param, 'date'); ?>>新着順</option>
                            <option value="popular" <?php selected($orderby_param, 'popular'); ?>>人気順</option>
                            <option value="modified" <?php selected($orderby_param, 'modified'); ?>>更新順</option>
                            <option value="title" <?php selected($orderby_param, 'title'); ?>>タイトル順</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- 記事一覧 -->
            <section class="col-articles">
                <?php if ($column_query->have_posts()): ?>
                    <div class="col-articles-grid">
                        <?php while ($column_query->have_posts()): $column_query->the_post(); 
                            $post_id = get_the_ID();
                            $view_count = get_post_meta($post_id, 'view_count', true) ?: 0;
                            $read_time = get_post_meta($post_id, 'estimated_read_time', true) ?: 5;
                            $difficulty = get_post_meta($post_id, 'difficulty_level', true) ?: 'beginner';
                            $column_status = get_post_meta($post_id, 'column_status', true);
                            $is_featured = ($column_status === 'featured');
                            $post_categories = get_the_terms($post_id, 'column_category');
                        ?>
                            <article class="col-card <?php echo $is_featured ? 'col-card-featured' : ''; ?>">
                                
                                <!-- サムネイル -->
                                <a href="<?php the_permalink(); ?>" class="col-card-thumb">
                                    <?php if (has_post_thumbnail()): ?>
                                        <?php the_post_thumbnail('medium', array('loading' => 'lazy', 'alt' => get_the_title())); ?>
                                    <?php else: ?>
                                        <div class="thumb-placeholder">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                <polyline points="14 2 14 8 20 8"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_featured): ?>
                                        <span class="featured-label">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                            </svg>
                                            特集
                                        </span>
                                    <?php endif; ?>
                                </a>
                                
                                <!-- コンテンツ -->
                                <div class="col-card-body">
                                    <div class="col-card-header">
                                        <?php if ($post_categories && !is_wp_error($post_categories)): ?>
                                            <a href="<?php echo esc_url(get_term_link($post_categories[0])); ?>" class="col-card-cat">
                                                <span class="cat-emoji"><?php echo gi_get_column_category_icon($post_categories[0]->slug); ?></span>
                                                <?php echo esc_html($post_categories[0]->name); ?>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <span class="col-card-time">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12 6 12 12 16 14"/>
                                            </svg>
                                            <?php echo esc_html($read_time); ?>分
                                        </span>
                                    </div>
                                    
                                    <h2 class="col-card-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h2>
                                    
                                    <p class="col-card-excerpt">
                                        <?php echo wp_trim_words(get_the_excerpt(), 40, '...'); ?>
                                    </p>
                                    
                                    <div class="col-card-footer">
                                        <time class="col-card-date" datetime="<?php echo get_the_date('c'); ?>">
                                            <?php echo get_the_date('Y.m.d'); ?>
                                        </time>
                                        
                                        <div class="col-card-stats">
                                            <?php if ($view_count > 0): ?>
                                                <span class="stat-views">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                        <circle cx="12" cy="12" r="3"/>
                                                    </svg>
                                                    <?php echo number_format($view_count); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <span class="stat-level stat-level-<?php echo esc_attr($difficulty); ?>">
                                                <?php echo gi_get_column_difficulty_label($difficulty); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>

                    <!-- ページネーション -->
                    <?php if ($column_query->max_num_pages > 1): ?>
                    <nav class="col-pagination">
                        <?php
                        $big = 999999999;
                        echo paginate_links(array(
                            'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                            'format'    => '?paged=%#%',
                            'current'   => max(1, $paged),
                            'total'     => $column_query->max_num_pages,
                            'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg> 前へ',
                            'next_text' => '次へ <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>',
                            'mid_size'  => 2,
                            'end_size'  => 1,
                        ));
                        ?>
                    </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- 記事なし -->
                    <div class="col-no-results">
                        <div class="no-results-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </div>
                        <h3 class="no-results-title">記事が見つかりませんでした</h3>
                        <p class="no-results-desc">
                            <?php if (!empty($search_query)): ?>
                                「<?php echo esc_html($search_query); ?>」に一致する記事がありません。<br>
                                別のキーワードでお試しください。
                            <?php else: ?>
                                条件に一致する記事がありません。
                            <?php endif; ?>
                        </p>
                        <a href="<?php echo esc_url(get_post_type_archive_link('column')); ?>" class="back-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="19" y1="12" x2="5" y2="12"/>
                                <polyline points="12 19 5 12 12 5"/>
                            </svg>
                            すべての記事を見る
                        </a>
                    </div>
                <?php endif; ?>
                <?php wp_reset_postdata(); ?>
            </section>
        </div>
        <!-- /col-main -->
        
        <!-- サイドバー -->
        <aside class="col-sidebar">
            
            <!-- 広告枠上部 -->
            <?php if (function_exists('ji_display_ad')): ?>
            <div class="sidebar-ad">
                <?php ji_display_ad('archive_column_sidebar_top', 'archive-column'); ?>
            </div>
            <?php endif; ?>

            <!-- アクセスランキング -->
            <?php if ($ranking_query->have_posts()): ?>
            <section class="sidebar-widget">
                <h3 class="widget-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    アクセスランキング
                </h3>
                <div class="widget-body">
                    <ol class="ranking-list">
                        <?php 
                        $rank = 1;
                        while ($ranking_query->have_posts()): $ranking_query->the_post();
                            $views = get_post_meta(get_the_ID(), 'view_count', true) ?: 0;
                        ?>
                            <li class="ranking-item <?php echo $rank <= 3 ? 'top-rank' : ''; ?>">
                                <a href="<?php the_permalink(); ?>" class="ranking-link">
                                    <span class="rank-num"><?php echo $rank; ?></span>
                                    <div class="rank-content">
                                        <?php if (has_post_thumbnail() && $rank <= 3): ?>
                                            <div class="rank-thumb">
                                                <?php the_post_thumbnail('thumbnail', array('loading' => 'lazy')); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="rank-text">
                                            <span class="rank-title"><?php the_title(); ?></span>
                                            <span class="rank-meta">
                                                <time datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date('n/j'); ?></time>
                                                <?php if ($views > 0): ?>
                                                    <span class="rank-views">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                            <circle cx="12" cy="12" r="3"/>
                                                        </svg>
                                                        <?php echo number_format($views); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        <?php 
                            $rank++;
                        endwhile; 
                        wp_reset_postdata();
                        ?>
                    </ol>
                </div>
            </section>
            <?php endif; ?>

            <!-- 広告枠中央 -->
            <?php if (function_exists('ji_display_ad')): ?>
            <div class="sidebar-ad">
                <?php ji_display_ad('archive_column_sidebar_middle', 'archive-column'); ?>
            </div>
            <?php endif; ?>

            <!-- 新着トピックス -->
            <?php if ($recent_query->have_posts()): ?>
            <section class="sidebar-widget">
                <h3 class="widget-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                        <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                        <line x1="6" y1="1" x2="6" y2="4"/>
                        <line x1="10" y1="1" x2="10" y2="4"/>
                        <line x1="14" y1="1" x2="14" y2="4"/>
                    </svg>
                    新着トピックス
                </h3>
                <div class="widget-body">
                    <ul class="topics-list">
                        <?php while ($recent_query->have_posts()): $recent_query->the_post(); ?>
                            <li class="topics-item">
                                <a href="<?php the_permalink(); ?>" class="topics-link">
                                    <time datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date('Y/m/d'); ?></time>
                                    <span class="topics-title"><?php the_title(); ?></span>
                                </a>
                            </li>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </ul>
                </div>
            </section>
            <?php endif; ?>

            <!-- トレンドキーワード -->
            <?php if (!empty($trending_tags)): ?>
            <section class="sidebar-widget">
                <h3 class="widget-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    トレンドキーワード
                </h3>
                <div class="widget-body">
                    <div class="trends-list">
                        <?php foreach ($trending_tags as $index => $tag): ?>
                            <a href="<?php echo esc_url(get_term_link($tag)); ?>" class="trend-item">
                                <span class="trend-rank"><?php echo ($index + 1); ?></span>
                                <span class="trend-name"><?php echo esc_html($tag->name); ?></span>
                                <span class="trend-count"><?php echo number_format($tag->count); ?>件</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- カテゴリ一覧 -->
            <?php if (!empty($categories)): ?>
            <section class="sidebar-widget">
                <h3 class="widget-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                    </svg>
                    カテゴリ
                </h3>
                <div class="widget-body">
                    <ul class="categories-list">
                        <?php foreach ($categories as $cat): ?>
                            <li class="categories-item">
                                <a href="<?php echo esc_url(get_term_link($cat)); ?>">
                                    <span class="cat-icon"><?php echo gi_get_column_category_icon($cat->slug); ?></span>
                                    <span class="cat-name"><?php echo esc_html($cat->name); ?></span>
                                    <span class="cat-count">(<?php echo number_format($cat->count); ?>)</span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
            <?php endif; ?>

            <!-- 広告枠下部 -->
            <?php if (function_exists('ji_display_ad')): ?>
            <div class="sidebar-ad">
                <?php ji_display_ad('archive_column_sidebar_bottom', 'archive-column'); ?>
            </div>
            <?php endif; ?>

            <!-- 助成金検索CTA -->
            <section class="sidebar-cta">
                <h3 class="cta-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                    助成金を探す
                </h3>
                <p class="cta-desc">コラムで学んだ知識を活かして、あなたに最適な助成金を見つけましょう。</p>
                <a href="<?php echo esc_url(get_post_type_archive_link('grant')); ?>" class="cta-btn">
                    助成金検索へ
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </a>
            </section>
        </aside>
        <!-- /col-sidebar -->

    </div>

</main>

<style>
/* ============================================
   Archive Column - Complete Styles v2.1
   ============================================ */

:root {
    --c-black: #000;
    --c-white: #fff;
    --c-gray-50: #fafafa;
    --c-gray-100: #f5f5f5;
    --c-gray-200: #e5e5e5;
    --c-gray-300: #d4d4d4;
    --c-gray-400: #a3a3a3;
    --c-gray-500: #737373;
    --c-gray-600: #525252;
    --c-gray-700: #404040;
    --c-gray-800: #262626;
    --c-gray-900: #171717;
    --c-green: #16a34a;
    --c-green-light: #dcfce7;
    --c-red: #dc2626;
    --c-shadow: 0 2px 8px rgba(0,0,0,0.1);
    --c-radius: 4px;
    --c-radius-lg: 8px;
    --c-font: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans JP', sans-serif;
    --c-transition: 0.2s ease;
}

/* Base */
.column-archive {
    font-family: var(--c-font);
    color: var(--c-black);
    background: var(--c-gray-50);
    line-height: 1.6;
}

.col-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 16px;
}

/* Breadcrumb */
.col-breadcrumb {
    padding: 12px 0;
    background: var(--c-white);
    border-bottom: 1px solid var(--c-gray-200);
}

.col-breadcrumb-list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: 13px;
}

.col-breadcrumb-list a {
    color: var(--c-gray-600);
    text-decoration: none;
}

.col-breadcrumb-list a:hover {
    color: var(--c-black);
    text-decoration: underline;
}

.col-breadcrumb-list .separator {
    color: var(--c-gray-400);
}

.col-breadcrumb-list .current {
    color: var(--c-black);
    font-weight: 600;
}

/* Hero */
.col-hero {
    padding: 24px 0;
    background: var(--c-white);
    border-bottom: 2px solid var(--c-black);
}

.col-hero-content {
    max-width: 800px;
}

.col-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: var(--c-black);
    color: var(--c-white);
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 12px;
}

.col-hero-title {
    font-size: clamp(24px, 5vw, 36px);
    font-weight: 800;
    margin: 0 0 12px;
    line-height: 1.3;
    letter-spacing: -0.02em;
}

.col-hero-desc {
    font-size: 15px;
    color: var(--c-gray-700);
    margin: 0 0 16px;
    line-height: 1.7;
}

.col-hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.col-hero-meta .meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: var(--c-gray-700);
}

.col-hero-meta .meta-item strong {
    color: var(--c-black);
    font-weight: 700;
    font-size: 18px;
}

/* Layout */
.col-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 24px;
    padding: 24px 16px;
    align-items: start;
}

.col-main {
    min-width: 0;
}

.col-sidebar {
    position: sticky;
    top: 24px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Search */
.col-search-box {
    margin-bottom: 20px;
    background: var(--c-white);
    padding: 16px;
    border: 1px solid var(--c-gray-200);
    box-shadow: var(--c-shadow);
}

.col-search-input-wrap {
    display: flex;
    align-items: center;
    border: 2px solid var(--c-gray-300);
    background: var(--c-white);
    position: relative;
}

.col-search-input-wrap:focus-within {
    border-color: var(--c-black);
}

.col-search-input-wrap .search-icon {
    position: absolute;
    left: 12px;
    color: var(--c-gray-400);
    pointer-events: none;
}

.col-search-input {
    flex: 1;
    padding: 12px 12px 12px 44px;
    border: none;
    outline: none;
    font-size: 15px;
    font-family: var(--c-font);
    background: transparent;
}

.col-search-input::placeholder {
    color: var(--c-gray-400);
}

.col-search-btn {
    padding: 12px 24px;
    background: var(--c-black);
    color: var(--c-white);
    border: none;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    font-family: var(--c-font);
    transition: background var(--c-transition);
}

.col-search-btn:hover {
    background: var(--c-gray-800);
}

/* Tabs */
.col-tabs-nav {
    margin-bottom: 20px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.col-tabs {
    display: flex;
    gap: 4px;
    list-style: none;
    margin: 0;
    padding: 0;
    border-bottom: 2px solid var(--c-black);
}

.col-tabs li {
    flex-shrink: 0;
}

.col-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 12px 16px;
    font-size: 13px;
    font-weight: 600;
    color: var(--c-gray-700);
    background: var(--c-gray-100);
    border: 1px solid var(--c-gray-200);
    border-bottom: none;
    text-decoration: none;
    transition: all var(--c-transition);
    white-space: nowrap;
}

.col-tab:hover {
    background: var(--c-white);
    color: var(--c-black);
}

.col-tab.active {
    color: var(--c-black);
    background: var(--c-white);
    border-color: var(--c-black);
    position: relative;
}

.col-tab.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--c-white);
}

.tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 20px;
    padding: 0 6px;
    font-size: 11px;
    font-weight: 700;
    color: var(--c-white);
    background: var(--c-gray-500);
    border-radius: 10px;
}

.col-tab.active .tab-count {
    background: var(--c-black);
}

/* Filter Bar */
.col-filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    background: var(--c-white);
    border: 1px solid var(--c-gray-200);
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.filter-info {
    font-size: 13px;
    color: var(--c-gray-600);
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.search-result-text strong {
    color: var(--c-black);
}

.clear-search {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: var(--c-gray-600);
    text-decoration: none;
    padding: 4px 8px;
    background: var(--c-gray-100);
    border-radius: var(--c-radius);
    transition: all var(--c-transition);
}

.clear-search:hover {
    background: var(--c-gray-200);
    color: var(--c-black);
}

.sort-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.sort-label {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 600;
    color: var(--c-gray-700);
}

.sort-select {
    padding: 6px 10px;
    border: 1px solid var(--c-gray-300);
    border-radius: var(--c-radius);
    font-size: 13px;
    font-family: var(--c-font);
    background: var(--c-white);
    cursor: pointer;
}

.sort-select:focus {
    outline: none;
    border-color: var(--c-black);
}

/* Articles Grid */
.col-articles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

/* Card */
.col-card {
    display: flex;
    flex-direction: column;
    background: var(--c-white);
    border: 1px solid var(--c-gray-200);
    border-radius: var(--c-radius-lg);
    overflow: hidden;
    transition: all var(--c-transition);
}

.col-card:hover {
    box-shadow: var(--c-shadow);
    transform: translateY(-2px);
}

.col-card-featured {
    border-color: var(--c-green);
    border-width: 2px;
}

.col-card-thumb {
    position: relative;
    display: block;
    aspect-ratio: 16 / 9;
    background: var(--c-gray-100);
    overflow: hidden;
}

.col-card-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--c-transition);
}

.col-card:hover .col-card-thumb img {
    transform: scale(1.05);
}

.thumb-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: var(--c-gray-300);
}

.featured-label {
    position: absolute;
    top: 10px;
    left: 10px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: var(--c-green);
    color: var(--c-white);
    font-size: 11px;
    font-weight: 700;
    border-radius: var(--c-radius);
}

.col-card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 16px;
}

.col-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.col-card-cat {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    background: var(--c-gray-100);
    color: var(--c-gray-700);
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    border-radius: var(--c-radius);
    transition: all var(--c-transition);
}

.col-card-cat:hover {
    background: var(--c-gray-200);
    color: var(--c-black);
}

.col-card-time {
    display: flex;
    align-items: center;
    gap: 3px;
    font-size: 11px;
    color: var(--c-gray-500);
}

.col-card-title {
    margin: 0 0 10px;
    font-size: 16px;
    font-weight: 700;
    line-height: 1.4;
}

.col-card-title a {
    color: var(--c-black);
    text-decoration: none;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.col-card-title a:hover {
    color: var(--c-green);
}

.col-card-excerpt {
    flex: 1;
    margin: 0 0 12px;
    font-size: 13px;
    color: var(--c-gray-600);
    line-height: 1.6;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.col-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid var(--c-gray-200);
}

.col-card-date {
    font-size: 12px;
    color: var(--c-gray-500);
}

.col-card-stats {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stat-views {
    display: flex;
    align-items: center;
    gap: 3px;
    font-size: 11px;
    color: var(--c-gray-500);
}

.stat-level {
    padding: 2px 8px;
    font-size: 10px;
    font-weight: 600;
    border-radius: var(--c-radius);
}

.stat-level-beginner {
    background: var(--c-green-light);
    color: var(--c-green);
}

.stat-level-intermediate {
    background: #fef3c7;
    color: #d97706;
}

.stat-level-advanced {
    background: #fee2e2;
    color: var(--c-red);
}

/* Pagination */
.col-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid var(--c-gray-200);
    flex-wrap: wrap;
}

.col-pagination a,
.col-pagination span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    font-size: 14px;
    font-weight: 600;
    border: 1px solid var(--c-gray-300);
    background: var(--c-white);
    color: var(--c-gray-700);
    text-decoration: none;
    transition: all var(--c-transition);
}

.col-pagination a:hover {
    border-color: var(--c-black);
    color: var(--c-black);
    background: var(--c-gray-50);
}

.col-pagination .current {
    background: var(--c-black);
    border-color: var(--c-black);
    color: var(--c-white);
}

.col-pagination .prev,
.col-pagination .next {
    gap: 4px;
}

.col-pagination .dots {
    border: none;
    background: none;
    color: var(--c-gray-400);
}

/* No Results */
.col-no-results {
    text-align: center;
    padding: 60px 20px;
    background: var(--c-white);
    border: 1px solid var(--c-gray-200);
    border-radius: var(--c-radius-lg);
}

.no-results-icon {
    color: var(--c-gray-300);
    margin-bottom: 20px;
}

.no-results-title {
    font-size: 20px;
    font-weight: 700;
    margin: 0 0 12px;
}

.no-results-desc {
    font-size: 14px;
    color: var(--c-gray-600);
    margin: 0 0 24px;
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 600;
    color: var(--c-white);
    background: var(--c-black);
    border-radius: 24px;
    text-decoration: none;
    transition: all var(--c-transition);
}

.back-btn:hover {
    background: var(--c-gray-800);
    transform: translateY(-2px);
}

/* Sidebar */
.sidebar-widget {
    background: var(--c-white);
    border: 1px solid var(--c-gray-200);
    overflow: hidden;
}

.widget-title {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: var(--c-gray-50);
    border-bottom: 2px solid var(--c-black);
    font-size: 14px;
    font-weight: 700;
    margin: 0;
}

.widget-body {
    padding: 16px;
}

/* Ranking */
.ranking-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.ranking-item {
    border-bottom: 1px solid var(--c-gray-200);
}

.ranking-item:last-child {
    border-bottom: none;
}

.ranking-link {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 0;
    text-decoration: none;
    color: inherit;
    transition: background var(--c-transition);
}

.ranking-link:hover {
    background: var(--c-gray-50);
    margin: 0 -16px;
    padding: 10px 16px;
}

.rank-num {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    font-size: 12px;
    font-weight: 800;
    color: var(--c-gray-600);
    background: var(--c-gray-100);
    border-radius: var(--c-radius);
    flex-shrink: 0;
}

.top-rank .rank-num {
    background: var(--c-black);
    color: var(--c-white);
}

.rank-content {
    flex: 1;
    min-width: 0;
    display: flex;
    gap: 10px;
}

.rank-thumb {
    width: 50px;
    height: 40px;
    flex-shrink: 0;
    border-radius: var(--c-radius);
    overflow: hidden;
    background: var(--c-gray-100);
}

.rank-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.rank-text {
    flex: 1;
    min-width: 0;
}

.rank-title {
    display: block;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.4;
    color: var(--c-black);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ranking-link:hover .rank-title {
    color: var(--c-green);
}

.rank-meta {
    display: flex;
    gap: 8px;
    font-size: 11px;
    color: var(--c-gray-500);
    margin-top: 4px;
}

.rank-views {
    display: flex;
    align-items: center;
    gap: 2px;
}

/* Topics */
.topics-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.topics-item {
    border-bottom: 1px solid var(--c-gray-200);
    padding-bottom: 10px;
    margin-bottom: 10px;
}

.topics-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
    margin-bottom: 0;
}

.topics-link {
    display: flex;
    flex-direction: column;
    gap: 4px;
    text-decoration: none;
    color: inherit;
}

.topics-link time {
    font-size: 11px;
    color: var(--c-gray-500);
}

.topics-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--c-black);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    transition: color var(--c-transition);
}

.topics-link:hover .topics-title {
    color: var(--c-green);
}

/* Trends */
.trends-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.trend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: var(--c-gray-50);
    border: 1px solid var(--c-gray-200);
    border-radius: var(--c-radius);
    text-decoration: none;
    color: inherit;
    transition: all var(--c-transition);
}

.trend-item:hover {
    background: var(--c-gray-100);
    border-color: var(--c-black);
    transform: translateX(4px);
}

.trend-rank {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    font-size: 11px;
    font-weight: 800;
    color: var(--c-white);
    background: var(--c-gray-400);
    border-radius: 10px;
}

.trend-item:nth-child(-n+3) .trend-rank {
    background: var(--c-black);
}

.trend-name {
    flex: 1;
    font-size: 13px;
    font-weight: 600;
}

.trend-count {
    font-size: 11px;
    color: var(--c-gray-500);
}

/* Categories */
.categories-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.categories-item {
    border-bottom: 1px solid var(--c-gray-200);
}

.categories-item:last-child {
    border-bottom: none;
}

.categories-item a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 0;
    text-decoration: none;
    color: inherit;
    transition: background var(--c-transition);
}

.categories-item a:hover {
    background: var(--c-gray-50);
    margin: 0 -16px;
    padding: 10px 16px;
}

.cat-icon {
    font-size: 16px;
}

.cat-name {
    flex: 1;
    font-size: 13px;
    font-weight: 600;
    color: var(--c-black);
}

.categories-item a:hover .cat-name {
    color: var(--c-green);
}

.cat-count {
    font-size: 12px;
    color: var(--c-gray-500);
}

/* CTA */
.sidebar-cta {
    background: var(--c-black);
    color: var(--c-white);
    padding: 20px;
    text-align: center;
}

.cta-title {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 10px;
}

.cta-desc {
    font-size: 13px;
    opacity: 0.9;
    margin: 0 0 16px;
    line-height: 1.6;
}

.cta-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    width: 100%;
    padding: 12px 20px;
    background: var(--c-white);
    color: var(--c-black);
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    border-radius: var(--c-radius);
    transition: all var(--c-transition);
}

.cta-btn:hover {
    background: var(--c-gray-100);
    transform: translateY(-2px);
}

/* Ad Spaces */
.sidebar-ad {
    background: var(--c-gray-100);
    border: 1px solid var(--c-gray-200);
    min-height: 250px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--c-gray-400);
    font-size: 12px;
}

/* Responsive */
@media (max-width: 1024px) {
    .col-layout {
        grid-template-columns: 1fr;
        gap: 32px;
    }
    
    .col-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    
    .sidebar-cta,
    .sidebar-ad {
        grid-column: span 2;
    }
}

@media (max-width: 768px) {
    .col-container {
        padding: 0 12px;
    }
    
    .col-hero {
        padding: 20px 0;
    }
    
    .col-hero-title {
        font-size: 24px;
    }
    
    .col-hero-meta {
        gap: 12px;
    }
    
    .col-tabs {
        padding-bottom: 8px;
    }
    
    .col-tab {
        padding: 10px 12px;
        font-size: 12px;
    }
    
    .col-filter-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .col-articles-grid {
        grid-template-columns: 1fr;
    }
    
    .col-sidebar {
        grid-template-columns: 1fr;
    }
    
    .sidebar-cta,
    .sidebar-ad {
        grid-column: span 1;
    }
    
    .col-pagination {
        gap: 4px;
    }
    
    .col-pagination a,
    .col-pagination span {
        min-width: 36px;
        height: 36px;
        font-size: 13px;
    }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}

*:focus-visible {
    outline: 2px solid var(--c-black);
    outline-offset: 2px;
}

/* Print */
@media print {
    .column-archive {
        background: white;
    }
    
    .col-tabs-nav,
    .col-search-box,
    .col-filter-bar,
    .col-sidebar,
    .col-pagination,
    .col-breadcrumb {
        display: none !important;
    }
    
    .col-layout {
        display: block;
    }
    
    .col-card {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>

<?php get_footer(); ?>