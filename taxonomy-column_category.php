<?php
/**
 * Taxonomy Column Category Template
 * コラムカテゴリアーカイブページ
 * 
 * @package Grant_Insight_Perfect
 * @subpackage Column_System
 * @version 1.0.0
 */

get_header();

// ===== 現在のカテゴリ情報取得 =====
$current_term = get_queried_object();
$term_id = $current_term->term_id;
$term_name = $current_term->name;
$term_slug = $current_term->slug;
$term_description = $current_term->description;
$term_count = $current_term->count;

// ===== ページング設定 =====
$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$posts_per_page = 12;

// ===== ソート・検索パラメータ =====
$orderby_param = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// ===== メインクエリ構築 =====
$query_args = array(
    'post_type'      => 'column',
    'post_status'    => 'publish',
    'posts_per_page' => $posts_per_page,
    'paged'          => $paged,
    'tax_query'      => array(
        array(
            'taxonomy' => 'column_category',
            'field'    => 'term_id',
            'terms'    => $term_id,
        ),
    ),
);

// 検索クエリ
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

$column_query = new WP_Query($query_args);
$found_posts = $column_query->found_posts;

// ===== 全カテゴリ取得 =====
$all_categories = get_terms(array(
    'taxonomy'   => 'column_category',
    'hide_empty' => true,
    'orderby'    => 'count',
    'order'      => 'DESC',
));

if (is_wp_error($all_categories)) {
    $all_categories = array();
}

// ===== 総記事数 =====
$total_columns = wp_count_posts('column')->publish;

// ===== サイドバー用データ =====

// このカテゴリの人気記事
$popular_in_category = new WP_Query(array(
    'post_type'      => 'column',
    'posts_per_page' => 5,
    'post_status'    => 'publish',
    'tax_query'      => array(
        array(
            'taxonomy' => 'column_category',
            'field'    => 'term_id',
            'terms'    => $term_id,
        ),
    ),
    'meta_key'       => 'view_count',
    'orderby'        => 'meta_value_num',
    'order'          => 'DESC',
));

// フォールバック
if (!$popular_in_category->have_posts()) {
    $popular_in_category = new WP_Query(array(
        'post_type'      => 'column',
        'posts_per_page' => 5,
        'post_status'    => 'publish',
        'tax_query'      => array(
            array(
                'taxonomy' => 'column_category',
                'field'    => 'term_id',
                'terms'    => $term_id,
            ),
        ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
}

// 関連タグ
$related_tags = get_terms(array(
    'taxonomy'   => 'column_tag',
    'hide_empty' => true,
    'number'     => 15,
    'orderby'    => 'count',
    'order'      => 'DESC',
));

if (is_wp_error($related_tags)) {
    $related_tags = array();
}

// ===== カテゴリアイコン取得関数 =====
if (!function_exists('gi_get_category_svg_icon')) {
    function gi_get_category_svg_icon($slug) {
        $icons = array(
            'application-tips' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            'system-explanation' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
            'news' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1"/><path d="M21 12h-8"/><path d="M21 16h-8"/><path d="M21 8h-8"/><path d="M7 8h.01"/><path d="M7 12h.01"/><path d="M7 16h.01"/></svg>',
            'success-stories' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>',
            'other' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        );
        
        // デフォルトアイコン
        $default = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>';
        
        return isset($icons[$slug]) ? $icons[$slug] : $default;
    }
}

// ===== 難易度ラベル取得関数 =====
if (!function_exists('gi_get_difficulty_label')) {
    function gi_get_difficulty_label($difficulty) {
        $labels = array(
            'beginner'     => '初心者向け',
            'intermediate' => '中級者向け',
            'advanced'     => '上級者向け',
        );
        return isset($labels[$difficulty]) ? $labels[$difficulty] : '初心者向け';
    }
}
?>

<main class="category-archive" id="main-content" role="main">

    <!-- パンくずリスト -->
    <nav class="cat-breadcrumb" aria-label="パンくずリスト">
        <div class="cat-container">
            <ol class="cat-breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a href="<?php echo esc_url(home_url('/')); ?>" itemprop="item">
                        <span itemprop="name">ホーム</span>
                    </a>
                    <meta itemprop="position" content="1">
                </li>
                <li class="separator" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </li>
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a href="<?php echo esc_url(get_post_type_archive_link('column')); ?>" itemprop="item">
                        <span itemprop="name">コラム</span>
                    </a>
                    <meta itemprop="position" content="2">
                </li>
                <li class="separator" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </li>
                <li class="current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <span itemprop="name"><?php echo esc_html($term_name); ?></span>
                    <meta itemprop="position" content="3">
                </li>
            </ol>
        </div>
    </nav>

    <!-- ヒーローセクション -->
    <header class="cat-hero">
        <div class="cat-container">
            <div class="cat-hero-content">
                
                <!-- カテゴリバッジ -->
                <div class="cat-hero-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                    </svg>
                    <span>カテゴリ</span>
                </div>
                
                <!-- カテゴリタイトル -->
                <div class="cat-hero-title-wrap">
                    <span class="cat-hero-icon" aria-hidden="true">
                        <?php echo gi_get_category_svg_icon($term_slug); ?>
                    </span>
                    <h1 class="cat-hero-title"><?php echo esc_html($term_name); ?></h1>
                </div>
                
                <!-- 説明文 -->
                <?php if (!empty($term_description)): ?>
                    <p class="cat-hero-desc"><?php echo esc_html($term_description); ?></p>
                <?php else: ?>
                    <p class="cat-hero-desc"><?php echo esc_html($term_name); ?>に関する補助金・助成金コラム記事一覧です。専門家による解説記事を掲載しています。</p>
                <?php endif; ?>
                
                <!-- メタ情報 -->
                <div class="cat-hero-meta">
                    <span class="meta-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        <strong><?php echo number_format($term_count); ?></strong>
                        <span>記事</span>
                    </span>
                    <span class="meta-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span><?php echo date('Y'); ?>年度版</span>
                    </span>
                    <span class="meta-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"/>
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                        </svg>
                        <span>随時更新</span>
                    </span>
                </div>
            </div>
        </div>
    </header>

    <!-- メインコンテンツ -->
    <div class="cat-container cat-layout">
        
        <!-- 左カラム -->
        <div class="cat-main">
            
            <!-- 検索バー -->
            <div class="cat-search-box">
                <form method="get" action="<?php echo esc_url(get_term_link($current_term)); ?>" class="cat-search-form">
                    <div class="cat-search-input-wrap">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        <label for="category-search" class="visually-hidden">このカテゴリ内を検索</label>
                        <input type="search" 
                               id="category-search"
                               name="s" 
                               placeholder="このカテゴリ内を検索..." 
                               value="<?php echo esc_attr($search_query); ?>"
                               class="cat-search-input">
                        <button type="submit" class="cat-search-btn">
                            <span>検索</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- カテゴリナビゲーション -->
            <?php if (!empty($all_categories)): ?>
            <nav class="cat-tabs-nav" aria-label="カテゴリナビゲーション">
                <ul class="cat-tabs">
                    <li>
                        <a href="<?php echo esc_url(get_post_type_archive_link('column')); ?>" class="cat-tab">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="6" x2="21" y2="6"/>
                                <line x1="8" y1="12" x2="21" y2="12"/>
                                <line x1="8" y1="18" x2="21" y2="18"/>
                                <line x1="3" y1="6" x2="3.01" y2="6"/>
                                <line x1="3" y1="12" x2="3.01" y2="12"/>
                                <line x1="3" y1="18" x2="3.01" y2="18"/>
                            </svg>
                            <span class="tab-text">すべて</span>
                            <span class="tab-count"><?php echo number_format($total_columns); ?></span>
                        </a>
                    </li>
                    <?php foreach ($all_categories as $cat): 
                        $is_current = ($cat->term_id === $term_id);
                    ?>
                        <li>
                            <a href="<?php echo esc_url(get_term_link($cat)); ?>" 
                               class="cat-tab <?php echo $is_current ? 'active' : ''; ?>"
                               <?php echo $is_current ? 'aria-current="page"' : ''; ?>>
                                <span class="tab-icon"><?php echo gi_get_category_svg_icon($cat->slug); ?></span>
                                <span class="tab-text"><?php echo esc_html($cat->name); ?></span>
                                <span class="tab-count"><?php echo number_format($cat->count); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endif; ?>

            <!-- フィルターバー -->
            <div class="cat-filter-bar">
                <div class="filter-info">
                    <?php if (!empty($search_query)): ?>
                        <span class="search-result-text">
                            「<strong><?php echo esc_html($search_query); ?></strong>」の検索結果: 
                            <strong><?php echo number_format($found_posts); ?></strong>件
                        </span>
                        <a href="<?php echo esc_url(get_term_link($current_term)); ?>" class="clear-search">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                            クリア
                        </a>
                    <?php else: ?>
                        <?php 
                        $from = $found_posts > 0 ? (($paged - 1) * $posts_per_page) + 1 : 0;
                        $to = min($paged * $posts_per_page, $found_posts);
                        ?>
                        <span class="showing-text">
                            <?php echo number_format($from); ?>〜<?php echo number_format($to); ?>件を表示（全<?php echo number_format($found_posts); ?>件）
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
                            <line x1="1" y1="14" x2="7" y2="14"/>
                            <line x1="9" y1="8" x2="15" y2="8"/>
                            <line x1="17" y1="16" x2="23" y2="16"/>
                        </svg>
                        並び順:
                    </label>
                    <form method="get" class="sort-form">
                        <?php if (!empty($search_query)): ?>
                            <input type="hidden" name="s" value="<?php echo esc_attr($search_query); ?>">
                        <?php endif; ?>
                        <select name="orderby" id="orderby-select" class="sort-select" onchange="this.form.submit()">
                            <option value="date" <?php selected($orderby_param, 'date'); ?>>新着順</option>
                            <option value="popular" <?php selected($orderby_param, 'popular'); ?>>人気順</option>
                            <option value="modified" <?php selected($orderby_param, 'modified'); ?>>更新順</option>
                            <option value="title" <?php selected($orderby_param, 'title'); ?>>タイトル順</option>
                        </select>
                        <noscript><button type="submit" class="sort-btn">適用</button></noscript>
                    </form>
                </div>
            </div>

            <!-- 記事一覧 -->
            <section class="cat-articles" aria-label="<?php echo esc_attr($term_name); ?>の記事一覧">
                <?php if ($column_query->have_posts()): ?>
                    <div class="cat-articles-grid">
                        <?php while ($column_query->have_posts()): $column_query->the_post(); 
                            $post_id = get_the_ID();
                            $view_count = get_post_meta($post_id, 'view_count', true) ?: 0;
                            $read_time = get_post_meta($post_id, 'estimated_read_time', true) ?: 5;
                            $difficulty = get_post_meta($post_id, 'difficulty_level', true) ?: 'beginner';
                            $column_status = get_post_meta($post_id, 'column_status', true);
                            $is_featured = ($column_status === 'featured');
                        ?>
                            <article class="cat-card <?php echo $is_featured ? 'cat-card-featured' : ''; ?>">
                                
                                <!-- サムネイル -->
                                <a href="<?php the_permalink(); ?>" class="cat-card-thumb" tabindex="-1" aria-hidden="true">
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
                                <div class="cat-card-body">
                                    <div class="cat-card-header">
                                        <span class="cat-card-category">
                                            <span class="category-icon"><?php echo gi_get_category_svg_icon($term_slug); ?></span>
                                            <?php echo esc_html($term_name); ?>
                                        </span>
                                        
                                        <span class="cat-card-time">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12 6 12 12 16 14"/>
                                            </svg>
                                            <?php echo esc_html($read_time); ?>分
                                        </span>
                                    </div>
                                    
                                    <h2 class="cat-card-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h2>
                                    
                                    <p class="cat-card-excerpt">
                                        <?php echo wp_trim_words(get_the_excerpt(), 40, '...'); ?>
                                    </p>
                                    
                                    <div class="cat-card-footer">
                                        <time class="cat-card-date" datetime="<?php echo get_the_date('c'); ?>">
                                            <?php echo get_the_date('Y.m.d'); ?>
                                        </time>
                                        
                                        <div class="cat-card-stats">
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
                                                <?php echo gi_get_difficulty_label($difficulty); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>

                    <!-- ページネーション -->
                    <?php if ($column_query->max_num_pages > 1): ?>
                    <nav class="cat-pagination" aria-label="ページナビゲーション">
                        <?php
                        $big = 999999999;
                        $pagination_args = array(
                            'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                            'format'    => '?paged=%#%',
                            'current'   => max(1, $paged),
                            'total'     => $column_query->max_num_pages,
                            'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg><span>前へ</span>',
                            'next_text' => '<span>次へ</span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>',
                            'mid_size'  => 2,
                            'end_size'  => 1,
                        );
                        
                        // 検索・ソートパラメータを保持
                        if (!empty($search_query)) {
                            $pagination_args['add_args'] = array('s' => $search_query);
                        }
                        if ($orderby_param !== 'date') {
                            $pagination_args['add_args']['orderby'] = $orderby_param;
                        }
                        
                        echo paginate_links($pagination_args);
                        ?>
                    </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- 記事なし -->
                    <div class="cat-no-results">
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
                                このカテゴリにはまだ記事がありません。
                            <?php endif; ?>
                        </p>
                        <div class="no-results-actions">
                            <?php if (!empty($search_query)): ?>
                                <a href="<?php echo esc_url(get_term_link($current_term)); ?>" class="action-btn action-btn-secondary">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"/>
                                        <line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                    検索をクリア
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo esc_url(get_post_type_archive_link('column')); ?>" class="action-btn action-btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="19" y1="12" x2="5" y2="12"/>
                                    <polyline points="12 19 5 12 12 5"/>
                                </svg>
                                すべての記事を見る
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                <?php wp_reset_postdata(); ?>
            </section>
        </div>
        <!-- /cat-main -->
        
        <!-- サイドバー -->
        <aside class="cat-sidebar" role="complementary" aria-label="サイドバー">
            
            <!-- 広告枠上部 -->
            <?php if (function_exists('ji_display_ad')): ?>
            <div class="sidebar-ad">
                <?php ji_display_ad('taxonomy_column_sidebar_top', 'taxonomy-column-category'); ?>
            </div>
            <?php endif; ?>

            <!-- このカテゴリの人気記事 -->
            <?php if ($popular_in_category->have_posts()): ?>
            <section class="sidebar-widget">
                <h3 class="widget-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    <?php echo esc_html($term_name); ?>の人気記事
                </h3>
                <div class="widget-body">
                    <ol class="ranking-list">
                        <?php 
                        $rank = 1;
                        while ($popular_in_category->have_posts()): $popular_in_category->the_post();
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
                <?php ji_display_ad('taxonomy_column_sidebar_middle', 'taxonomy-column-category'); ?>
            </div>
            <?php endif; ?>

            <!-- 関連タグ -->
            <?php if (!empty($related_tags)): ?>
            <section class="sidebar-widget">
                <h3 class="widget-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                        <line x1="7" y1="7" x2="7.01" y2="7"/>
                    </svg>
                    関連タグ
                </h3>
                <div class="widget-body">
                    <div class="tags-cloud">
                        <?php foreach ($related_tags as $tag): ?>
                            <a href="<?php echo esc_url(get_term_link($tag)); ?>" class="tag-item">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="4" y1="9" x2="20" y2="9"/>
                                    <line x1="4" y1="15" x2="20" y2="15"/>
                                    <line x1="10" y1="3" x2="8" y2="21"/>
                                    <line x1="16" y1="3" x2="14" y2="21"/>
                                </svg>
                                <?php echo esc_html($tag->name); ?>
                                <span class="tag-count"><?php echo number_format($tag->count); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- 他のカテゴリ -->
            <?php if (!empty($all_categories) && count($all_categories) > 1): ?>
            <section class="sidebar-widget">
                <h3 class="widget-title">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                    </svg>
                    他のカテゴリ
                </h3>
                <div class="widget-body">
                    <ul class="categories-list">
                        <?php foreach ($all_categories as $cat): 
                            if ($cat->term_id === $term_id) continue;
                        ?>
                            <li class="categories-item">
                                <a href="<?php echo esc_url(get_term_link($cat)); ?>">
                                    <span class="cat-icon"><?php echo gi_get_category_svg_icon($cat->slug); ?></span>
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
                <?php ji_display_ad('taxonomy_column_sidebar_bottom', 'taxonomy-column-category'); ?>
            </div>
            <?php endif; ?>

            <!-- 助成金検索CTA -->
            <section class="sidebar-cta">
                <div class="cta-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.35-4.35"/>
                    </svg>
                </div>
                <h3 class="cta-title">助成金を探す</h3>
                <p class="cta-desc">コラムで学んだ知識を活かして、あなたに最適な助成金を見つけましょう。</p>
                <a href="<?php echo esc_url(get_post_type_archive_link('grant')); ?>" class="cta-btn">
                    助成金検索へ
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </a>
            </section>
        </aside>
        <!-- /cat-sidebar -->

    </div>

</main>

<style>
/* ============================================
   Taxonomy Column Category - Complete Styles
   ============================================ */

:root {
    --tc-black: #000;
    --tc-white: #fff;
    --tc-gray-50: #fafafa;
    --tc-gray-100: #f5f5f5;
    --tc-gray-200: #e5e5e5;
    --tc-gray-300: #d4d4d4;
    --tc-gray-400: #a3a3a3;
    --tc-gray-500: #737373;
    --tc-gray-600: #525252;
    --tc-gray-700: #404040;
    --tc-gray-800: #262626;
    --tc-gray-900: #171717;
    --tc-green: #16a34a;
    --tc-green-light: #dcfce7;
    --tc-red: #dc2626;
    --tc-shadow: 0 2px 8px rgba(0,0,0,0.1);
    --tc-radius: 4px;
    --tc-radius-lg: 8px;
    --tc-font: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans JP', sans-serif;
    --tc-transition: 0.2s ease;
}

/* Base */
.category-archive {
    font-family: var(--tc-font);
    color: var(--tc-black);
    background: var(--tc-gray-50);
    line-height: 1.6;
}

.cat-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 16px;
}

.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Breadcrumb */
.cat-breadcrumb {
    padding: 12px 0;
    background: var(--tc-white);
    border-bottom: 1px solid var(--tc-gray-200);
}

.cat-breadcrumb-list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: 13px;
}

.cat-breadcrumb-list a {
    color: var(--tc-gray-600);
    text-decoration: none;
    transition: color var(--tc-transition);
}

.cat-breadcrumb-list a:hover {
    color: var(--tc-black);
}

.cat-breadcrumb-list .separator {
    color: var(--tc-gray-400);
    display: flex;
    align-items: center;
}

.cat-breadcrumb-list .current {
    color: var(--tc-black);
    font-weight: 600;
}

/* Hero */
.cat-hero {
    padding: 32px 0;
    background: var(--tc-white);
    border-bottom: 3px solid var(--tc-black);
}

.cat-hero-content {
    max-width: 800px;
}

.cat-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: var(--tc-black);
    color: var(--tc-white);
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 16px;
}

.cat-hero-title-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.cat-hero-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: var(--tc-gray-100);
    border: 2px solid var(--tc-black);
    border-radius: var(--tc-radius);
}

.cat-hero-icon svg {
    width: 24px;
    height: 24px;
}

.cat-hero-title {
    font-size: clamp(28px, 5vw, 40px);
    font-weight: 800;
    margin: 0;
    line-height: 1.2;
    letter-spacing: -0.02em;
}

.cat-hero-desc {
    font-size: 15px;
    color: var(--tc-gray-700);
    margin: 0 0 20px;
    line-height: 1.7;
}

.cat-hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.cat-hero-meta .meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
    color: var(--tc-gray-700);
}

.cat-hero-meta .meta-item strong {
    color: var(--tc-black);
    font-weight: 700;
    font-size: 18px;
}

.cat-hero-meta .meta-item svg {
    color: var(--tc-gray-500);
}

/* Layout */
.cat-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 32px;
    padding: 32px 16px;
    align-items: start;
}

.cat-main {
    min-width: 0;
}

.cat-sidebar {
    position: sticky;
    top: 24px;
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* Search */
.cat-search-box {
    margin-bottom: 24px;
    background: var(--tc-white);
    padding: 20px;
    border: 1px solid var(--tc-gray-200);
    box-shadow: var(--tc-shadow);
}

.cat-search-input-wrap {
    display: flex;
    align-items: center;
    border: 2px solid var(--tc-gray-300);
    background: var(--tc-white);
    position: relative;
    transition: border-color var(--tc-transition);
}

.cat-search-input-wrap:focus-within {
    border-color: var(--tc-black);
}

.cat-search-input-wrap .search-icon {
    position: absolute;
    left: 14px;
    color: var(--tc-gray-400);
    pointer-events: none;
}

.cat-search-input {
    flex: 1;
    padding: 14px 14px 14px 48px;
    border: none;
    outline: none;
    font-size: 15px;
    font-family: var(--tc-font);
    background: transparent;
}

.cat-search-input::placeholder {
    color: var(--tc-gray-400);
}

.cat-search-btn {
    padding: 14px 28px;
    background: var(--tc-black);
    color: var(--tc-white);
    border: none;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    font-family: var(--tc-font);
    transition: background var(--tc-transition);
}

.cat-search-btn:hover {
    background: var(--tc-gray-800);
}

/* Tabs */
.cat-tabs-nav {
    margin-bottom: 24px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.cat-tabs {
    display: flex;
    gap: 4px;
    list-style: none;
    margin: 0;
    padding: 0;
    border-bottom: 3px solid var(--tc-black);
}

.cat-tabs li {
    flex-shrink: 0;
}

.cat-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 18px;
    font-size: 13px;
    font-weight: 600;
    color: var(--tc-gray-700);
    background: var(--tc-gray-100);
    border: 1px solid var(--tc-gray-200);
    border-bottom: none;
    text-decoration: none;
    transition: all var(--tc-transition);
    white-space: nowrap;
}

.cat-tab:hover {
    background: var(--tc-white);
    color: var(--tc-black);
}

.cat-tab.active {
    color: var(--tc-black);
    background: var(--tc-white);
    border-color: var(--tc-black);
    position: relative;
}

.cat-tab.active::after {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--tc-white);
}

.cat-tab .tab-icon {
    display: flex;
    align-items: center;
}

.cat-tab .tab-icon svg {
    width: 16px;
    height: 16px;
}

.tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 22px;
    padding: 0 8px;
    font-size: 11px;
    font-weight: 700;
    color: var(--tc-white);
    background: var(--tc-gray-500);
    border-radius: 11px;
}

.cat-tab.active .tab-count {
    background: var(--tc-black);
}

/* Filter Bar */
.cat-filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 14px 20px;
    background: var(--tc-white);
    border: 1px solid var(--tc-gray-200);
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.filter-info {
    font-size: 13px;
    color: var(--tc-gray-600);
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.search-result-text strong {
    color: var(--tc-black);
}

.clear-search {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: var(--tc-gray-600);
    text-decoration: none;
    padding: 6px 10px;
    background: var(--tc-gray-100);
    border-radius: var(--tc-radius);
    transition: all var(--tc-transition);
}

.clear-search:hover {
    background: var(--tc-gray-200);
    color: var(--tc-black);
}

.sort-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sort-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: var(--tc-gray-700);
}

.sort-select {
    padding: 8px 12px;
    border: 1px solid var(--tc-gray-300);
    border-radius: var(--tc-radius);
    font-size: 13px;
    font-family: var(--tc-font);
    background: var(--tc-white);
    cursor: pointer;
}

.sort-select:focus {
    outline: none;
    border-color: var(--tc-black);
}

.sort-btn {
    display: none;
}

/* Articles Grid */
.cat-articles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

/* Card */
.cat-card {
    display: flex;
    flex-direction: column;
    background: var(--tc-white);
    border: 1px solid var(--tc-gray-200);
    border-radius: var(--tc-radius-lg);
    overflow: hidden;
    transition: all var(--tc-transition);
}

.cat-card:hover {
    box-shadow: var(--tc-shadow);
    transform: translateY(-3px);
}

.cat-card-featured {
    border-color: var(--tc-black);
    border-width: 2px;
}

.cat-card-thumb {
    position: relative;
    display: block;
    aspect-ratio: 16 / 9;
    background: var(--tc-gray-100);
    overflow: hidden;
}

.cat-card-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--tc-transition);
}

.cat-card:hover .cat-card-thumb img {
    transform: scale(1.05);
}

.thumb-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: var(--tc-gray-300);
}

.featured-label {
    position: absolute;
    top: 12px;
    left: 12px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    background: var(--tc-black);
    color: var(--tc-white);
    font-size: 11px;
    font-weight: 700;
    border-radius: var(--tc-radius);
}

.cat-card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 20px;
}

.cat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.cat-card-category {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: var(--tc-gray-100);
    color: var(--tc-gray-700);
    font-size: 11px;
    font-weight: 600;
    border-radius: var(--tc-radius);
}

.cat-card-category .category-icon {
    display: flex;
    align-items: center;
}

.cat-card-category .category-icon svg {
    width: 14px;
    height: 14px;
}

.cat-card-time {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: var(--tc-gray-500);
}

.cat-card-title {
    margin: 0 0 12px;
    font-size: 17px;
    font-weight: 700;
    line-height: 1.4;
}

.cat-card-title a {
    color: var(--tc-black);
    text-decoration: none;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.cat-card-title a:hover {
    color: var(--tc-gray-700);
}

.cat-card-excerpt {
    flex: 1;
    margin: 0 0 16px;
    font-size: 13px;
    color: var(--tc-gray-600);
    line-height: 1.7;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.cat-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 16px;
    border-top: 1px solid var(--tc-gray-200);
}

.cat-card-date {
    font-size: 12px;
    color: var(--tc-gray-500);
}

.cat-card-stats {
    display: flex;
    align-items: center;
    gap: 12px;
}

.stat-views {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    color: var(--tc-gray-500);
}

.stat-level {
    padding: 3px 10px;
    font-size: 10px;
    font-weight: 600;
    border-radius: var(--tc-radius);
}

.stat-level-beginner {
    background: var(--tc-green-light);
    color: var(--tc-green);
}

.stat-level-intermediate {
    background: #fef3c7;
    color: #d97706;
}

.stat-level-advanced {
    background: #fee2e2;
    color: var(--tc-red);
}

/* Pagination */
.cat-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    margin-top: 40px;
    padding-top: 32px;
    border-top: 1px solid var(--tc-gray-200);
    flex-wrap: wrap;
}

.cat-pagination a,
.cat-pagination span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    height: 44px;
    padding: 0 14px;
    font-size: 14px;
    font-weight: 600;
    border: 1px solid var(--tc-gray-300);
    background: var(--tc-white);
    color: var(--tc-gray-700);
    text-decoration: none;
    transition: all var(--tc-transition);
}

.cat-pagination a:hover {
    border-color: var(--tc-black);
    color: var(--tc-black);
    background: var(--tc-gray-50);
}

.cat-pagination .current {
    background: var(--tc-black);
    border-color: var(--tc-black);
    color: var(--tc-white);
}

.cat-pagination .prev,
.cat-pagination .next {
    gap: 6px;
}

.cat-pagination .dots {
    border: none;
    background: none;
    color: var(--tc-gray-400);
}

/* No Results */
.cat-no-results {
    text-align: center;
    padding: 80px 24px;
    background: var(--tc-white);
    border: 1px solid var(--tc-gray-200);
    border-radius: var(--tc-radius-lg);
}

.no-results-icon {
    color: var(--tc-gray-300);
    margin-bottom: 24px;
}

.no-results-title {
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 12px;
}

.no-results-desc {
    font-size: 14px;
    color: var(--tc-gray-600);
    margin: 0 0 28px;
}

.no-results-actions {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    border-radius: 28px;
    transition: all var(--tc-transition);
}

.action-btn-primary {
    color: var(--tc-white);
    background: var(--tc-black);
}

.action-btn-primary:hover {
    background: var(--tc-gray-800);
    transform: translateY(-2px);
}

.action-btn-secondary {
    color: var(--tc-gray-700);
    background: var(--tc-gray-100);
    border: 1px solid var(--tc-gray-300);
}

.action-btn-secondary:hover {
    background: var(--tc-gray-200);
    color: var(--tc-black);
}

/* Sidebar */
.sidebar-widget {
    background: var(--tc-white);
    border: 1px solid var(--tc-gray-200);
    overflow: hidden;
}

.widget-title {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    background: var(--tc-gray-50);
    border-bottom: 2px solid var(--tc-black);
    font-size: 14px;
    font-weight: 700;
    margin: 0;
}

.widget-body {
    padding: 20px;
}

/* Ranking */
.ranking-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.ranking-item {
    border-bottom: 1px solid var(--tc-gray-200);
}

.ranking-item:last-child {
    border-bottom: none;
}

.ranking-link {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 0;
    text-decoration: none;
    color: inherit;
    transition: background var(--tc-transition);
}

.ranking-link:hover {
    background: var(--tc-gray-50);
    margin: 0 -20px;
    padding: 12px 20px;
}

.rank-num {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 28px;
    font-size: 13px;
    font-weight: 800;
    color: var(--tc-gray-600);
    background: var(--tc-gray-100);
    border-radius: var(--tc-radius);
    flex-shrink: 0;
}

.top-rank .rank-num {
    background: var(--tc-black);
    color: var(--tc-white);
}

.rank-content {
    flex: 1;
    min-width: 0;
    display: flex;
    gap: 12px;
}

.rank-thumb {
    width: 56px;
    height: 42px;
    flex-shrink: 0;
    border-radius: var(--tc-radius);
    overflow: hidden;
    background: var(--tc-gray-100);
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
    color: var(--tc-black);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ranking-link:hover .rank-title {
    color: var(--tc-gray-700);
}

.rank-meta {
    display: flex;
    gap: 10px;
    font-size: 11px;
    color: var(--tc-gray-500);
    margin-top: 4px;
}

.rank-views {
    display: flex;
    align-items: center;
    gap: 3px;
}

/* Tags Cloud */
.tags-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.tag-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: var(--tc-gray-50);
    border: 1px solid var(--tc-gray-200);
    border-radius: var(--tc-radius);
    text-decoration: none;
    color: var(--tc-gray-700);
    font-size: 12px;
    font-weight: 500;
    transition: all var(--tc-transition);
}

.tag-item:hover {
    background: var(--tc-gray-100);
    border-color: var(--tc-black);
    color: var(--tc-black);
}

.tag-count {
    font-size: 10px;
    color: var(--tc-gray-500);
}

/* Categories */
.categories-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.categories-item {
    border-bottom: 1px solid var(--tc-gray-200);
}

.categories-item:last-child {
    border-bottom: none;
}

.categories-item a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 0;
    text-decoration: none;
    color: inherit;
    transition: background var(--tc-transition);
}

.categories-item a:hover {
    background: var(--tc-gray-50);
    margin: 0 -20px;
    padding: 12px 20px;
}

.categories-item .cat-icon {
    display: flex;
    align-items: center;
}

.categories-item .cat-icon svg {
    width: 18px;
    height: 18px;
    color: var(--tc-gray-500);
}

.categories-item .cat-name {
    flex: 1;
    font-size: 13px;
    font-weight: 600;
    color: var(--tc-black);
}

.categories-item a:hover .cat-name {
    color: var(--tc-gray-700);
}

.categories-item .cat-count {
    font-size: 12px;
    color: var(--tc-gray-500);
}

/* CTA */
.sidebar-cta {
    background: var(--tc-black);
    color: var(--tc-white);
    padding: 28px;
    text-align: center;
}

.cta-icon {
    margin-bottom: 12px;
}

.cta-icon svg {
    width: 32px;
    height: 32px;
}

.cta-title {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 10px;
}

.cta-desc {
    font-size: 13px;
    opacity: 0.9;
    margin: 0 0 20px;
    line-height: 1.6;
}

.cta-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 14px 24px;
    background: var(--tc-white);
    color: var(--tc-black);
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    border-radius: var(--tc-radius);
    transition: all var(--tc-transition);
}

.cta-btn:hover {
    background: var(--tc-gray-100);
    transform: translateY(-2px);
}

/* Ad Spaces */
.sidebar-ad {
    background: var(--tc-gray-100);
    border: 1px solid var(--tc-gray-200);
    min-height: 250px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--tc-gray-400);
    font-size: 12px;
}

/* Responsive */
@media (max-width: 1024px) {
    .cat-layout {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .cat-sidebar {
        position: static;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .sidebar-cta,
    .sidebar-ad {
        grid-column: span 2;
    }
}

@media (max-width: 768px) {
    .cat-container {
        padding: 0 12px;
    }
    
    .cat-hero {
        padding: 24px 0;
    }
    
    .cat-hero-title-wrap {
        gap: 10px;
    }
    
    .cat-hero-icon {
        width: 40px;
        height: 40px;
    }
    
    .cat-hero-icon svg {
        width: 20px;
        height: 20px;
    }
    
    .cat-hero-title {
        font-size: 26px;
    }
    
    .cat-hero-meta {
        gap: 14px;
    }
    
    .cat-tabs {
        padding-bottom: 10px;
    }
    
    .cat-tab {
        padding: 12px 14px;
        font-size: 12px;
    }
    
    .cat-tab .tab-text {
        display: none;
    }
    
    .cat-tab.active .tab-text {
        display: inline;
    }
    
    .cat-filter-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 14px;
    }
    
    .cat-articles-grid {
        grid-template-columns: 1fr;
    }
    
    .cat-sidebar {
        grid-template-columns: 1fr;
    }
    
    .sidebar-cta,
    .sidebar-ad {
        grid-column: span 1;
    }
    
    .cat-pagination {
        gap: 4px;
    }
    
    .cat-pagination a,
    .cat-pagination span {
        min-width: 40px;
        height: 40px;
        font-size: 13px;
    }
    
    .no-results-actions {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
        justify-content: center;
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
    outline: 2px solid var(--tc-black);
    outline-offset: 2px;
}

/* Print */
@media print {
    .category-archive {
        background: white;
    }
    
    .cat-tabs-nav,
    .cat-search-box,
    .cat-filter-bar,
    .cat-sidebar,
    .cat-pagination,
    .cat-breadcrumb {
        display: none !important;
    }
    
    .cat-layout {
        display: block;
    }
    
    .cat-card {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>

<?php get_footer(); ?>