<?php
/**
 * Template Part: Ultimate UI Grant & Column Section
 * 補助金・コラム・ランキング統合セクション（完全最適化版 v30.0）
 * 
 * @package Grant_Insight_Perfect
 * @version 30.0.0 - Complete SEO & UX Optimization (Columns First)
 * 
 * Design Concept:
 * - Monochrome & Minimal: 白と黒、グレーのみで構成された洗練されたUI
 * - Flat Structure: ネストを排除し、縦スクロールで完結するモバイルファースト設計
 * - High Performance: 必要な情報への最短アクセス
 * - SEO First: 検索エンジンとユーザー両方に最適化された構造
 * - E-E-A-T Compliant: 専門性・権威性・信頼性・経験を重視
 */

if (!defined('ABSPATH')) exit;

// ==========================================================================
// データ取得（パフォーマンス最適化）
// ==========================================================================

$today = date('Y-m-d');
$two_weeks_later = date('Y-m-d', strtotime('+14 days'));

// 共通クエリ引数
$common_args = [
    'post_status' => 'publish',
    'no_found_rows' => true,
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
];

// 1. 注目の補助金（最優先表示）
$grants_featured = new WP_Query(array_merge($common_args, [
    'post_type' => 'grant',
    'posts_per_page' => 3,
    'meta_key' => 'is_featured',
    'meta_value' => '1',
    'orderby' => 'date',
    'order' => 'DESC',
]));

// 2. 締切間近の補助金
$grants_deadline = new WP_Query(array_merge($common_args, [
    'post_type' => 'grant',
    'posts_per_page' => 5,
    'meta_key' => 'deadline_date',
    'orderby' => 'meta_value',
    'order' => 'ASC',
    'meta_query' => [
        [
            'key' => 'deadline_date',
            'value' => $today,
            'compare' => '>=',
            'type' => 'DATE'
        ]
    ],
]));

// 3. 新着補助金
$grants_new = new WP_Query(array_merge($common_args, [
    'post_type' => 'grant',
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC',
]));

// 4. 新着コラム
$columns_new = new WP_Query(array_merge($common_args, [
    'post_type' => 'column',
    'posts_per_page' => 4,
    'orderby' => 'date',
    'order' => 'DESC',
]));

// 5. 人気コラム
$columns_popular = new WP_Query(array_merge($common_args, [
    'post_type' => 'column',
    'posts_per_page' => 5,
    'meta_key' => 'view_count',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
]));

// 6. お知らせ
$news_query = new WP_Query(array_merge($common_args, [
    'post_type' => 'post',
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC',
]));

// 7. ランキング
$grant_ranking = function_exists('ji_get_ranking') ? ji_get_ranking('grant', 7, 5) : [];
$column_ranking = function_exists('ji_get_ranking') ? ji_get_ranking('column', 7, 5) : [];

// 8. カウントデータ
$count_grants = wp_count_posts('grant')->publish ?? 0;
$count_columns = wp_count_posts('column')->publish ?? 0;
$count_news = wp_count_posts('post')->publish ?? 0;

// ==========================================================================
// 構造化データ（SEO最適化 - Schema.org準拠）
// ==========================================================================
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => 'コラム・補助金総合情報ハブ',
    'description' => '専門家コラム' . number_format($count_columns) . '件と最新の補助金情報' . number_format($count_grants) . '件を集約。事業者が必要とする情報を網羅的に提供します。',
    'url' => home_url('/#main-info-hub'),
    'inLanguage' => 'ja-JP',
    'publisher' => [
        '@type' => 'Organization',
        'name' => get_bloginfo('name'),
        'url' => home_url(),
    ],
    'mainEntity' => [
        '@type' => 'ItemList',
        'numberOfItems' => $count_grants + $count_columns,
        'itemListElement' => []
    ]
];

// 新着コラムを構造化データに追加
if ($columns_new->have_posts()) {
    $position = 1;
    while ($columns_new->have_posts()) {
        $columns_new->the_post();
        $schema['mainEntity']['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'item' => [
                '@type' => 'Article',
                'name' => get_the_title(),
                'url' => get_permalink(),
                'datePublished' => get_the_date('c'),
                'dateModified' => get_the_modified_date('c'),
            ]
        ];
    }
    wp_reset_postdata();
}
?>

<!-- 構造化データ（JSON-LD） -->
<script type="application/ld+json">
<?php echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>

<!-- ==========================================================================
     メインセクション
     ========================================================================== -->
<section class="ui-section" id="main-info-hub" aria-label="コラム・補助金総合情報" itemscope itemtype="https://schema.org/CollectionPage">
    <div class="ui-container">
        
        <!-- ヘッダー -->
        <header class="ui-header">
            <p class="ui-title" role="doc-subtitle">LATEST INTELLIGENCE</p>
            <h2 class="ui-subtitle" itemprop="name">コラム・補助金・最新情報ハブ</h2>
            <p class="ui-description" itemprop="description">
                コラム<strong class="ui-count-emphasis"><?php echo number_format($count_columns); ?>件</strong>・
                補助金<strong class="ui-count-emphasis"><?php echo number_format($count_grants); ?>件</strong>から、
                <br class="ui-sp-only" aria-hidden="true">今あなたに必要な情報を見つけましょう
            </p>
        </header>

        <!-- タブナビゲーション -->
        <div class="ui-tabs-wrapper">
            <nav class="ui-tabs-nav" role="tablist" aria-label="情報カテゴリ">
                <button class="ui-tab-btn active" 
                        role="tab" 
                        id="tab-columns" 
                        aria-selected="true" 
                        aria-controls="panel-columns" 
                        data-tab="columns"
                        type="button">
                    <span class="ui-tab-en">COLUMNS</span>
                    <span class="ui-tab-ja">コラム</span>
                    <span class="ui-tab-count" aria-label="<?php echo $count_columns; ?>件"><?php echo number_format($count_columns); ?></span>
                </button>
                <button class="ui-tab-btn" 
                        role="tab" 
                        id="tab-grants" 
                        aria-selected="false" 
                        aria-controls="panel-grants" 
                        data-tab="grants"
                        type="button">
                    <span class="ui-tab-en">GRANTS</span>
                    <span class="ui-tab-ja">補助金</span>
                    <span class="ui-tab-count" aria-label="<?php echo $count_grants; ?>件"><?php echo number_format($count_grants); ?></span>
                </button>
                <button class="ui-tab-btn" 
                        role="tab" 
                        id="tab-news" 
                        aria-selected="false" 
                        aria-controls="panel-news" 
                        data-tab="news"
                        type="button">
                    <span class="ui-tab-en">NEWS</span>
                    <span class="ui-tab-ja">お知らせ</span>
                    <span class="ui-tab-count" aria-label="<?php echo $count_news; ?>件"><?php echo number_format($count_news); ?></span>
                </button>
            </nav>
        </div>

        <!-- タブコンテンツ -->
        <div class="ui-panels-container">

            <!-- コラムパネル -->
            <div class="ui-panel active" id="panel-columns" role="tabpanel" aria-labelledby="tab-columns">
                
                <!-- 新着コラム -->
                <section class="ui-sub-section" aria-labelledby="new-columns-heading">
                    <header class="ui-sub-header">
                        <h3 class="ui-sub-title" id="new-columns-heading">
                            <span class="ui-icon-dot" aria-hidden="true"></span>
                            新着コラム
                        </h3>
                        <p class="ui-sub-description">専門家による最新の解説記事</p>
                    </header>
                    <?php if ($columns_new->have_posts()) : ?>
                    <div class="ui-grid-columns">
                        <?php while ($columns_new->have_posts()) : $columns_new->the_post(); ?>
                        <article class="ui-card ui-card-column" itemscope itemtype="https://schema.org/Article">
                            <a href="<?php the_permalink(); ?>" 
                               class="ui-card-link"
                               itemprop="url"
                               aria-label="<?php echo esc_attr(get_the_title()); ?>">
                                <?php if (has_post_thumbnail()) : ?>
                                <figure class="ui-card-image" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
                                    <?php 
                                    the_post_thumbnail('medium', [
                                        'class' => 'ui-card-thumb',
                                        'loading' => 'lazy',
                                        'alt' => get_the_title(),
                                        'itemprop' => 'url'
                                    ]); 
                                    ?>
                                </figure>
                                <?php else : ?>
                                <div class="ui-card-image ui-card-no-image" role="img" aria-label="画像なし">
                                    <span class="ui-no-image-text" aria-hidden="true">No Image</span>
                                </div>
                                <?php endif; ?>
                                <div class="ui-card-body">
                                    <time class="ui-card-date" 
                                          datetime="<?php echo get_the_date('c'); ?>"
                                          itemprop="datePublished">
                                        <?php echo get_the_date('Y.m.d'); ?>
                                    </time>
                                    <h4 class="ui-card-title" itemprop="headline"><?php the_title(); ?></h4>
                                </div>
                            </a>
                        </article>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                    <?php else : ?>
                    <p class="ui-empty-state">現在、コラムはありません</p>
                    <?php endif; ?>
                </section>

                <!-- 人気コラム -->
                <section class="ui-sub-section" aria-labelledby="popular-columns-heading">
                    <header class="ui-sub-header">
                        <h3 class="ui-sub-title" id="popular-columns-heading">
                            <span class="ui-icon-dot" aria-hidden="true"></span>
                            よく読まれている記事
                        </h3>
                        <p class="ui-sub-description">多くの方に支持されているコラム</p>
                    </header>
                    <?php if ($columns_popular->have_posts()) : ?>
                    <div class="ui-list-group" role="list">
                        <?php while ($columns_popular->have_posts()) : $columns_popular->the_post(); 
                            $view_count = get_post_meta(get_the_ID(), 'view_count', true);
                        ?>
                        <article class="ui-list-item" role="listitem" itemscope itemtype="https://schema.org/Article">
                            <a href="<?php the_permalink(); ?>" 
                               class="ui-list-link"
                               itemprop="url"
                               aria-label="<?php echo esc_attr(get_the_title()); ?>">
                                <div class="ui-list-content">
                                    <h4 class="ui-list-title" itemprop="headline"><?php the_title(); ?></h4>
                                    <div class="ui-list-meta">
                                        <time class="ui-list-date" 
                                              datetime="<?php echo get_the_date('c'); ?>"
                                              itemprop="datePublished">
                                            <?php echo get_the_date('Y.m.d'); ?>
                                        </time>
                                        <?php if ($view_count) : ?>
                                        <span class="ui-list-views" aria-label="<?php echo number_format($view_count); ?>回閲覧">
                                            PV: <?php echo number_format($view_count); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="ui-list-arrow" aria-hidden="true">→</span>
                            </a>
                        </article>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                    <div class="ui-more-container">
                        <a href="<?php echo esc_url(home_url('/columns/')); ?>" 
                           class="ui-btn-more"
                           aria-label="コラムをすべて見る">
                            View All Columns
                        </a>
                    </div>
                    <?php else : ?>
                    <p class="ui-empty-state">現在、人気コラムはありません</p>
                    <?php endif; ?>
                </section>
            </div>

            <!-- 補助金パネル -->
            <div class="ui-panel" id="panel-grants" role="tabpanel" aria-labelledby="tab-grants" hidden>
                
                <!-- 注目の補助金 -->
                <?php if ($grants_featured->have_posts()) : ?>
                <section class="ui-sub-section" aria-labelledby="featured-grants-heading">
                    <header class="ui-sub-header">
                        <h3 class="ui-sub-title" id="featured-grants-heading">
                            <span class="ui-icon-dot" aria-hidden="true"></span>
                            注目の補助金
                        </h3>
                        <p class="ui-sub-description">専門家が厳選した、今申請すべき補助金制度</p>
                    </header>
                    <div class="ui-grid-featured">
                        <?php 
                        $featured_count = 0;
                        while ($grants_featured->have_posts()) : 
                            $grants_featured->the_post(); 
                            $featured_count++;
                            $limit = get_post_meta(get_the_ID(), 'limit_amount', true);
                            $deadline = get_post_meta(get_the_ID(), 'deadline_date', true);
                            $is_urgent = $deadline && $deadline <= $two_weeks_later;
                        ?>
                        <article class="ui-card ui-card-featured" itemscope itemtype="https://schema.org/Article">
                            <a href="<?php the_permalink(); ?>" 
                               class="ui-card-link" 
                               itemprop="url"
                               aria-label="<?php echo esc_attr(get_the_title()); ?>の詳細を見る">
                                <div class="ui-card-body">
                                    <div class="ui-card-labels" role="list">
                                        <span class="ui-label ui-label-featured" role="listitem">注目</span>
                                        <?php if ($is_urgent) : ?>
                                        <span class="ui-label ui-label-urgent" role="listitem">締切間近</span>
                                        <?php endif; ?>
                                    </div>
                                    <h4 class="ui-card-title" itemprop="headline"><?php the_title(); ?></h4>
                                    <dl class="ui-card-meta">
                                        <?php if ($limit) : ?>
                                        <div class="ui-card-meta-item">
                                            <dt>上限額</dt>
                                            <dd itemprop="amount"><?php echo esc_html($limit); ?></dd>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($deadline) : ?>
                                        <div class="ui-card-meta-item">
                                            <dt>締切</dt>
                                            <dd>
                                                <time datetime="<?php echo esc_attr($deadline); ?>" itemprop="expires">
                                                    <?php echo date('Y年m月d日', strtotime($deadline)); ?>
                                                </time>
                                            </dd>
                                        </div>
                                        <?php else : ?>
                                        <div class="ui-card-meta-item">
                                            <dt>締切</dt>
                                            <dd>随時受付</dd>
                                        </div>
                                        <?php endif; ?>
                                    </dl>
                                </div>
                            </a>
                        </article>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- 2カラムレイアウト -->
                <div class="ui-dual-columns">
                    
                    <!-- 締切間近 -->
                    <section class="ui-sub-section" aria-labelledby="deadline-grants-heading">
                        <header class="ui-sub-header">
                            <h3 class="ui-sub-title" id="deadline-grants-heading">
                                <span class="ui-icon-dot" aria-hidden="true"></span>
                                締切間近
                            </h3>
                            <p class="ui-sub-description">申請期限が迫っている補助金</p>
                        </header>
                        <?php if ($grants_deadline->have_posts()) : ?>
                        <div class="ui-list-group" role="list">
                            <?php while ($grants_deadline->have_posts()) : $grants_deadline->the_post(); 
                                $deadline = get_post_meta(get_the_ID(), 'deadline_date', true);
                                $days_left = ceil((strtotime($deadline) - strtotime($today)) / 86400);
                            ?>
                            <article class="ui-list-item" role="listitem" itemscope itemtype="https://schema.org/Article">
                                <a href="<?php the_permalink(); ?>" 
                                   class="ui-list-link"
                                   itemprop="url"
                                   aria-label="<?php echo esc_attr(get_the_title()); ?>（あと<?php echo max(0, $days_left); ?>日）">
                                    <div class="ui-list-content">
                                        <h4 class="ui-list-title" itemprop="headline"><?php the_title(); ?></h4>
                                        <time class="ui-list-date ui-list-date-urgent" 
                                              datetime="<?php echo esc_attr($deadline); ?>"
                                              itemprop="expires">
                                            あと<strong><?php echo max(0, $days_left); ?>日</strong>（<?php echo date('m月d日', strtotime($deadline)); ?>締切）
                                        </time>
                                    </div>
                                    <span class="ui-list-arrow" aria-hidden="true">→</span>
                                </a>
                            </article>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                        <div class="ui-more-container">
                            <a href="<?php echo esc_url(home_url('/grants/?orderby=deadline')); ?>" 
                               class="ui-btn-more"
                               aria-label="締切間近の補助金をすべて見る">
                                View All Deadlines
                            </a>
                        </div>
                        <?php else : ?>
                        <p class="ui-empty-state">現在、締切間近の補助金はありません</p>
                        <?php endif; ?>
                    </section>

                    <!-- 新着補助金 -->
                    <section class="ui-sub-section" aria-labelledby="new-grants-heading">
                        <header class="ui-sub-header">
                            <h3 class="ui-sub-title" id="new-grants-heading">
                                <span class="ui-icon-dot" aria-hidden="true"></span>
                                新着補助金
                            </h3>
                            <p class="ui-sub-description">最近公開された補助金制度</p>
                        </header>
                        <?php if ($grants_new->have_posts()) : ?>
                        <div class="ui-list-group" role="list">
                            <?php while ($grants_new->have_posts()) : $grants_new->the_post(); ?>
                            <article class="ui-list-item" role="listitem" itemscope itemtype="https://schema.org/Article">
                                <a href="<?php the_permalink(); ?>" 
                                   class="ui-list-link"
                                   itemprop="url"
                                   aria-label="<?php echo esc_attr(get_the_title()); ?>">
                                    <div class="ui-list-content">
                                        <h4 class="ui-list-title" itemprop="headline"><?php the_title(); ?></h4>
                                        <time class="ui-list-date" 
                                              datetime="<?php echo get_the_date('c'); ?>"
                                              itemprop="datePublished">
                                            <?php echo get_the_date('Y年m月d日'); ?>公開
                                        </time>
                                    </div>
                                    <span class="ui-list-arrow" aria-hidden="true">→</span>
                                </a>
                            </article>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                        <div class="ui-more-container">
                            <a href="<?php echo esc_url(home_url('/grants/?orderby=date')); ?>" 
                               class="ui-btn-more"
                               aria-label="新着補助金をすべて見る">
                                View All New Grants
                            </a>
                        </div>
                        <?php else : ?>
                        <p class="ui-empty-state">現在、新着補助金はありません</p>
                        <?php endif; ?>
                    </section>

                </div>
            </div>

            <!-- お知らせパネル -->
            <div class="ui-panel" id="panel-news" role="tabpanel" aria-labelledby="tab-news" hidden>
                <section class="ui-sub-section" aria-labelledby="news-heading">
                    <header class="ui-sub-header">
                        <h3 class="ui-sub-title" id="news-heading">
                            <span class="ui-icon-dot" aria-hidden="true"></span>
                            お知らせ
                        </h3>
                        <p class="ui-sub-description">サイトからの最新情報</p>
                    </header>
                    <?php if ($news_query->have_posts()) : ?>
                    <div class="ui-list-group ui-list-simple" role="list">
                        <?php while ($news_query->have_posts()) : $news_query->the_post(); 
                            $categories = get_the_category();
                        ?>
                        <article class="ui-list-item" role="listitem" itemscope itemtype="https://schema.org/Article">
                            <a href="<?php the_permalink(); ?>" 
                               class="ui-list-link"
                               itemprop="url"
                               aria-label="<?php echo esc_attr(get_the_title()); ?>">
                                <div class="ui-list-content">
                                    <div class="ui-list-header">
                                        <time class="ui-list-date" 
                                              datetime="<?php echo get_the_date('c'); ?>"
                                              itemprop="datePublished">
                                            <?php echo get_the_date('Y.m.d'); ?>
                                        </time>
                                        <?php if ($categories) : ?>
                                        <span class="ui-list-category" itemprop="articleSection">
                                            <?php echo esc_html($categories[0]->name); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <h4 class="ui-list-title" itemprop="headline"><?php the_title(); ?></h4>
                                </div>
                            </a>
                        </article>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </div>
                    <div class="ui-more-container">
                        <a href="<?php echo esc_url(home_url('/news/')); ?>" 
                           class="ui-btn-more"
                           aria-label="お知らせをすべて見る">
                            View All News
                        </a>
                    </div>
                    <?php else : ?>
                    <p class="ui-empty-state">現在、お知らせはありません</p>
                    <?php endif; ?>
                </section>
            </div>

        </div>

        <!-- ランキングセクション -->
        <aside class="ui-ranking-section" aria-label="週間アクセスランキング">
            <header class="ui-ranking-main-header">
                <h3 class="ui-ranking-main-title">WEEKLY RANKING</h3>
                <p class="ui-ranking-main-description">過去7日間で最も読まれた記事</p>
            </header>
            
            <div class="ui-ranking-grid">
                
                <!-- コラムランキング -->
                <section class="ui-ranking-col" aria-labelledby="column-ranking-heading">
                    <header class="ui-ranking-header">
                        <h4 class="ui-ranking-title" id="column-ranking-heading">COLUMN RANKING</h4>
                        <p class="ui-ranking-subtitle">コラムアクセスTOP5</p>
                    </header>
                    <?php if (!empty($column_ranking)) : ?>
                    <ol class="ui-ranking-list" role="list">
                        <?php foreach ($column_ranking as $index => $item) : 
                            $rank = $index + 1;
                        ?>
                        <li class="ui-ranking-item rank-<?php echo $rank; ?>" role="listitem">
                            <a href="<?php echo esc_url(get_permalink($item->post_id)); ?>" 
                               class="ui-ranking-link"
                               aria-label="第<?php echo $rank; ?>位：<?php echo esc_attr(get_the_title($item->post_id)); ?>">
                                <span class="ui-ranking-number" aria-hidden="true">
                                    <?php echo sprintf('%02d', $rank); ?>
                                </span>
                                <span class="ui-ranking-text">
                                    <?php echo esc_html(get_the_title($item->post_id)); ?>
                                </span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                    <?php else : ?>
                    <p class="ui-empty-state-sm">現在集計中です</p>
                    <?php endif; ?>
                </section>

                <!-- 補助金ランキング -->
                <section class="ui-ranking-col" aria-labelledby="grant-ranking-heading">
                    <header class="ui-ranking-header">
                        <h4 class="ui-ranking-title" id="grant-ranking-heading">GRANT RANKING</h4>
                        <p class="ui-ranking-subtitle">補助金アクセスTOP5</p>
                    </header>
                    <?php if (!empty($grant_ranking)) : ?>
                    <ol class="ui-ranking-list" role="list">
                        <?php foreach ($grant_ranking as $index => $item) : 
                            $rank = $index + 1;
                        ?>
                        <li class="ui-ranking-item rank-<?php echo $rank; ?>" role="listitem">
                            <a href="<?php echo esc_url(get_permalink($item->post_id)); ?>" 
                               class="ui-ranking-link"
                               aria-label="第<?php echo $rank; ?>位：<?php echo esc_attr(get_the_title($item->post_id)); ?>">
                                <span class="ui-ranking-number" aria-hidden="true">
                                    <?php echo sprintf('%02d', $rank); ?>
                                </span>
                                <span class="ui-ranking-text">
                                    <?php echo esc_html(get_the_title($item->post_id)); ?>
                                </span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                    <?php else : ?>
                    <p class="ui-empty-state-sm">現在集計中です</p>
                    <?php endif; ?>
                </section>

            </div>
        </aside>

    </div>
</section>

<style>
/* ==========================================================================
   Design System - CSS Variables
   ========================================================================== */
:root {
    /* Colors - Monochrome Palette */
    --ui-black: #111111;
    --ui-dark: #222222;
    --ui-gray-dark: #404040;
    --ui-gray: #666666;
    --ui-gray-light: #999999;
    --ui-light: #e5e5e5;
    --ui-pale: #f9f9f9;
    --ui-white: #ffffff;
    
    /* Typography */
    --ui-font-main: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
    --ui-font-jp: 'Noto Sans JP', 'Hiragino Sans', 'Yu Gothic', sans-serif;
    --ui-font-mono: 'SF Mono', 'Monaco', 'Cascadia Code', 'Courier New', monospace;
    
    /* Font Sizes */
    --ui-text-xs: 11px;
    --ui-text-sm: 12px;
    --ui-text-base: 14px;
    --ui-text-md: 15px;
    --ui-text-lg: 16px;
    --ui-text-xl: 18px;
    --ui-text-2xl: 20px;
    --ui-text-3xl: 24px;
    --ui-text-4xl: 32px;
    
    /* Spacing */
    --ui-space-1: 4px;
    --ui-space-2: 8px;
    --ui-space-3: 12px;
    --ui-space-4: 16px;
    --ui-space-5: 20px;
    --ui-space-6: 24px;
    --ui-space-8: 32px;
    --ui-space-10: 40px;
    --ui-space-12: 48px;
    --ui-space-15: 60px;
    --ui-space-20: 80px;
    --ui-space-25: 100px;
    
    /* Layout */
    --ui-container-max: 1200px;
    --ui-radius: 0px;
    
    /* Shadows */
    --ui-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --ui-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    --ui-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --ui-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    
    /* Transitions */
    --ui-transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    --ui-transition-slow: 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

/* ==========================================================================
   Base Styles
   ========================================================================== */
.ui-section {
    background: var(--ui-white);
    color: var(--ui-black);
    font-family: var(--ui-font-jp);
    padding: var(--ui-space-25) 0;
    line-height: 1.7;
    font-size: var(--ui-text-base);
}

.ui-container {
    max-width: var(--ui-container-max);
    margin: 0 auto;
    padding: 0 var(--ui-space-6);
}

/* Reset */
a {
    text-decoration: none;
    color: inherit;
}

ul, ol {
    list-style: none;
    padding: 0;
    margin: 0;
}

h2, h3, h4, p, dl, dt, dd {
    margin: 0;
}

figure {
    margin: 0;
}

/* ==========================================================================
   Header
   ========================================================================== */
.ui-header {
    text-align: center;
    margin-bottom: var(--ui-space-15);
}

.ui-title {
    font-family: var(--ui-font-main);
    font-size: var(--ui-text-sm);
    letter-spacing: 0.2em;
    font-weight: 700;
    color: var(--ui-gray);
    margin-bottom: var(--ui-space-4);
    text-transform: uppercase;
}

.ui-subtitle {
    font-size: var(--ui-text-4xl);
    font-weight: 900;
    letter-spacing: -0.02em;
    color: var(--ui-black);
    margin-bottom: var(--ui-space-4);
}

.ui-description {
    font-size: var(--ui-text-lg);
    color: var(--ui-gray);
    line-height: 1.8;
}

.ui-count-emphasis {
    color: var(--ui-black);
    font-weight: 700;
}

.ui-sp-only {
    display: none;
}

/* ==========================================================================
   Tabs Navigation
   ========================================================================== */
.ui-tabs-wrapper {
    border-bottom: 1px solid var(--ui-light);
    margin-bottom: var(--ui-space-10);
}

.ui-tabs-nav {
    display: flex;
    justify-content: center;
    gap: 0;
}

.ui-tab-btn {
    background: transparent;
    border: none;
    padding: var(--ui-space-5) var(--ui-space-10);
    cursor: pointer;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    color: var(--ui-gray);
    transition: color var(--ui-transition);
    font-family: inherit;
}

.ui-tab-btn:hover {
    color: var(--ui-black);
}

.ui-tab-btn:focus-visible {
    outline: 2px solid var(--ui-black);
    outline-offset: 4px;
}

.ui-tab-btn.active {
    color: var(--ui-black);
}

.ui-tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 2px;
    background: var(--ui-black);
}

.ui-tab-en {
    font-family: var(--ui-font-main);
    font-weight: 700;
    font-size: var(--ui-text-sm);
    letter-spacing: 0.1em;
    margin-bottom: var(--ui-space-1);
}

.ui-tab-ja {
    font-size: var(--ui-text-base);
    font-weight: 700;
}

.ui-tab-count {
    position: absolute;
    top: var(--ui-space-2);
    right: var(--ui-space-5);
    font-size: var(--ui-text-xs);
    color: var(--ui-gray-light);
    font-family: var(--ui-font-mono);
}

/* ==========================================================================
   Panels
   ========================================================================== */
.ui-panels-container {
    margin-bottom: var(--ui-space-20);
}

.ui-panel {
    display: none;
}

.ui-panel.active {
    display: block;
    animation: fadeIn 0.6s var(--ui-transition-slow);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(var(--ui-space-2));
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ==========================================================================
   Sub Sections
   ========================================================================== */
.ui-sub-section {
    margin-bottom: var(--ui-space-15);
}

.ui-sub-header {
    margin-bottom: var(--ui-space-6);
}

.ui-sub-title {
    font-size: var(--ui-text-xl);
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: var(--ui-space-2);
    color: var(--ui-black);
    margin-bottom: var(--ui-space-2);
}

.ui-sub-description {
    font-size: var(--ui-text-sm);
    color: var(--ui-gray);
    padding-left: calc(6px + var(--ui-space-2));
}

.ui-icon-dot {
    width: 6px;
    height: 6px;
    background: var(--ui-black);
    border-radius: 50%;
    flex-shrink: 0;
}

/* ==========================================================================
   Layout Helpers
   ========================================================================== */
.ui-dual-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--ui-space-10);
}

.ui-grid-featured {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--ui-space-6);
}

.ui-grid-columns {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--ui-space-6);
}

/* ==========================================================================
   Cards
   ========================================================================== */
.ui-card {
    background: var(--ui-white);
    border: 1px solid var(--ui-light);
    transition: border-color var(--ui-transition), transform var(--ui-transition-slow), box-shadow var(--ui-transition);
}

.ui-card:hover {
    border-color: var(--ui-black);
    transform: translateY(-4px);
    box-shadow: var(--ui-shadow-md);
}

.ui-card-link {
    display: block;
    height: 100%;
}

.ui-card-link:focus-visible {
    outline: 3px solid var(--ui-black);
    outline-offset: 2px;
}

.ui-card-body {
    padding: var(--ui-space-6);
}

/* Featured Card */
.ui-card-featured .ui-card-labels {
    display: flex;
    gap: var(--ui-space-2);
    margin-bottom: var(--ui-space-4);
}

.ui-label {
    font-size: var(--ui-text-xs);
    font-weight: 700;
    padding: var(--ui-space-1) var(--ui-space-2);
    border-radius: 2px;
}

.ui-label-featured {
    background: var(--ui-black);
    color: var(--ui-white);
}

.ui-label-urgent {
    border: 1px solid var(--ui-black);
    color: var(--ui-black);
}

.ui-card-title {
    font-size: var(--ui-text-lg);
    font-weight: 700;
    line-height: 1.5;
    margin-bottom: var(--ui-space-4);
    min-height: calc(var(--ui-text-lg) * 1.5 * 2);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ui-card-meta {
    display: flex;
    flex-direction: column;
    gap: var(--ui-space-2);
}

.ui-card-meta-item {
    display: flex;
    justify-content: space-between;
    font-size: var(--ui-text-sm);
    padding-bottom: var(--ui-space-1);
    border-bottom: 1px dashed var(--ui-light);
}

.ui-card-meta-item dt {
    color: var(--ui-gray);
}

.ui-card-meta-item dd {
    font-weight: 700;
    font-family: var(--ui-font-mono);
    color: var(--ui-black);
}

/* Column Card */
.ui-card-column .ui-card-image {
    aspect-ratio: 16/9;
    background: var(--ui-pale);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ui-card-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--ui-transition-slow);
}

.ui-card:hover .ui-card-thumb {
    transform: scale(1.05);
}

.ui-card-no-image {
    background: var(--ui-pale);
}

.ui-no-image-text {
    color: var(--ui-light);
    font-size: var(--ui-text-sm);
    font-weight: 700;
}

.ui-card-date {
    font-size: var(--ui-text-sm);
    color: var(--ui-gray);
    display: block;
    margin-bottom: var(--ui-space-2);
    font-family: var(--ui-font-mono);
}

/* ==========================================================================
   List Items
   ========================================================================== */
.ui-list-group {
    border-top: 1px solid var(--ui-light);
}

.ui-list-item {
    border-bottom: 1px solid var(--ui-light);
    transition: background var(--ui-transition);
}

.ui-list-item:hover {
    background: var(--ui-pale);
}

.ui-list-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--ui-space-5) 0;
    gap: var(--ui-space-5);
}

.ui-list-link:focus-visible {
    outline: 3px solid var(--ui-black);
    outline-offset: -3px;
}

.ui-list-content {
    flex: 1;
    min-width: 0;
}

.ui-list-title {
    font-size: var(--ui-text-md);
    font-weight: 600;
    margin-bottom: var(--ui-space-2);
    line-height: 1.5;
}

.ui-list-date {
    font-size: var(--ui-text-sm);
    color: var(--ui-gray);
    font-family: var(--ui-font-mono);
}

.ui-list-date-urgent {
    color: var(--ui-black);
    font-weight: 700;
}

.ui-list-date-urgent strong {
    font-size: var(--ui-text-lg);
}

.ui-list-meta {
    display: flex;
    align-items: center;
    gap: var(--ui-space-3);
    font-size: var(--ui-text-sm);
}

.ui-list-views {
    color: var(--ui-gray);
    font-weight: 600;
    font-family: var(--ui-font-mono);
}

.ui-list-header {
    display: flex;
    align-items: center;
    gap: var(--ui-space-3);
    margin-bottom: var(--ui-space-2);
}

.ui-list-category {
    background: var(--ui-light);
    color: var(--ui-gray-dark);
    font-size: var(--ui-text-xs);
    padding: var(--ui-space-1) var(--ui-space-2);
    font-weight: 700;
    border-radius: 2px;
}

.ui-list-arrow {
    font-family: var(--ui-font-main);
    font-weight: 300;
    flex-shrink: 0;
    transition: transform var(--ui-transition);
    font-size: var(--ui-text-xl);
}

.ui-list-link:hover .ui-list-arrow {
    transform: translateX(var(--ui-space-1));
}

/* ==========================================================================
   More Button
   ========================================================================== */
.ui-more-container {
    margin-top: var(--ui-space-6);
    text-align: right;
}

.ui-btn-more {
    font-family: var(--ui-font-main);
    font-size: var(--ui-text-sm);
    font-weight: 700;
    border-bottom: 1px solid var(--ui-black);
    padding-bottom: 2px;
    transition: opacity var(--ui-transition);
    display: inline-block;
}

.ui-btn-more:hover {
    opacity: 0.6;
}

.ui-btn-more:focus-visible {
    outline: 3px solid var(--ui-black);
    outline-offset: 4px;
}

/* ==========================================================================
   Ranking Section
   ========================================================================== */
.ui-ranking-section {
    margin-top: var(--ui-space-20);
    padding-top: var(--ui-space-20);
    border-top: 4px solid var(--ui-black);
}

.ui-ranking-main-header {
    text-align: center;
    margin-bottom: var(--ui-space-12);
}

.ui-ranking-main-title {
    font-family: var(--ui-font-main);
    font-size: var(--ui-text-3xl);
    font-weight: 900;
    letter-spacing: 0.1em;
    margin-bottom: var(--ui-space-2);
    color: var(--ui-black);
}

.ui-ranking-main-description {
    font-size: var(--ui-text-base);
    color: var(--ui-gray);
}

.ui-ranking-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--ui-space-15);
}

.ui-ranking-header {
    margin-bottom: var(--ui-space-8);
}

.ui-ranking-title {
    font-family: var(--ui-font-main);
    font-size: var(--ui-text-2xl);
    font-weight: 900;
    letter-spacing: 0.05em;
    margin-bottom: var(--ui-space-1);
    color: var(--ui-black);
}

.ui-ranking-subtitle {
    font-size: var(--ui-text-sm);
    color: var(--ui-gray);
}

.ui-ranking-list {
    display: flex;
    flex-direction: column;
    gap: var(--ui-space-3);
}

.ui-ranking-item {
    border-bottom: 1px solid var(--ui-light);
    padding-bottom: var(--ui-space-3);
}

.ui-ranking-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.ui-ranking-link {
    display: flex;
    align-items: center;
    gap: var(--ui-space-4);
    transition: transform var(--ui-transition);
}

.ui-ranking-link:hover {
    transform: translateX(var(--ui-space-1));
}

.ui-ranking-link:focus-visible {
    outline: 3px solid var(--ui-black);
    outline-offset: 2px;
}

.ui-ranking-number {
    flex-shrink: 0;
    width: 30px;
    font-family: var(--ui-font-mono);
    font-size: var(--ui-text-2xl);
    font-weight: 900;
    color: var(--ui-light);
    transition: color var(--ui-transition);
}

.ui-ranking-link:hover .ui-ranking-number {
    color: var(--ui-black);
}

.rank-1 .ui-ranking-number,
.rank-2 .ui-ranking-number,
.rank-3 .ui-ranking-number {
    color: var(--ui-black);
}

.ui-ranking-text {
    flex: 1;
    font-size: var(--ui-text-base);
    font-weight: 600;
    line-height: 1.5;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* ==========================================================================
   Empty State
   ========================================================================== */
.ui-empty-state {
    text-align: center;
    padding: var(--ui-space-12) var(--ui-space-6);
    color: var(--ui-gray);
    font-size: var(--ui-text-base);
}

.ui-empty-state-sm {
    text-align: center;
    padding: var(--ui-space-8) var(--ui-space-5);
    color: var(--ui-gray);
    font-size: var(--ui-text-sm);
}

/* ==========================================================================
   Responsive Design
   ========================================================================== */

/* Tablet (768px以下) */
@media (max-width: 768px) {
    .ui-section {
        padding: var(--ui-space-15) 0;
    }
    
    .ui-container {
        padding: 0 var(--ui-space-4);
    }
    
    .ui-sp-only {
        display: inline;
    }
    
    .ui-subtitle {
        font-size: var(--ui-text-3xl);
    }
    
    .ui-description {
        font-size: var(--ui-text-base);
    }
    
    /* Tabs - Horizontal Scroll */
    .ui-tabs-wrapper {
        margin: 0 calc(var(--ui-space-4) * -1) var(--ui-space-8);
        padding: 0 var(--ui-space-4);
        border-bottom: 1px solid var(--ui-light);
    }
    
    .ui-tabs-nav {
        justify-content: flex-start;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    
    .ui-tabs-nav::-webkit-scrollbar {
        display: none;
    }
    
    .ui-tab-btn {
        padding: var(--ui-space-4) var(--ui-space-5);
        flex-shrink: 0;
    }
    
    /* Stack Columns */
    .ui-dual-columns,
    .ui-ranking-grid {
        grid-template-columns: 1fr;
        gap: var(--ui-space-10);
    }
    
    /* Horizontal Scroll for Cards */
    .ui-grid-featured,
    .ui-grid-columns {
        display: flex;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        gap: var(--ui-space-4);
        padding-bottom: var(--ui-space-4);
        margin-right: calc(var(--ui-space-4) * -1);
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    
    .ui-grid-featured::-webkit-scrollbar,
    .ui-grid-columns::-webkit-scrollbar {
        display: none;
    }
    
    .ui-grid-featured .ui-card,
    .ui-grid-columns .ui-card {
        min-width: 280px;
        scroll-snap-align: start;
    }
    
    .ui-ranking-section {
        margin-top: var(--ui-space-15);
        padding-top: var(--ui-space-10);
        border-top: 2px solid var(--ui-black);
    }
    
    .ui-list-title {
        font-size: var(--ui-text-base);
    }
}

/* Small Mobile (480px以下) */
@media (max-width: 480px) {
    .ui-subtitle {
        font-size: var(--ui-text-2xl);
    }
    
    .ui-grid-featured .ui-card,
    .ui-grid-columns .ui-card {
        min-width: 260px;
    }
    
    .ui-card-title {
        font-size: var(--ui-text-md);
    }
}

/* ==========================================================================
   Print Styles
   ========================================================================== */
@media print {
    .ui-tabs-wrapper,
    .ui-ranking-section,
    .ui-btn-more {
        display: none !important;
    }
    
    .ui-panel {
        display: block !important;
    }
    
    .ui-card,
    .ui-list-item {
        page-break-inside: avoid;
    }
}

/* ==========================================================================
   Accessibility
   ========================================================================== */

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .ui-card,
    .ui-list-item {
        border-width: 2px;
    }
    
    .ui-label-featured {
        border: 2px solid var(--ui-white);
    }
}

/* Dark Mode Support (Optional) */
@media (prefers-color-scheme: dark) {
    /* 必要に応じてダークモード対応を追加 */
}
</style>

<script>
/**
 * UI Section - JavaScript
 * タブ切り替え、遅延ロード、アクセシビリティ対応
 * @version 30.0.0
 */
(function() {
    'use strict';
    
    // ==========================================================================
    // Tab Switching
    // ==========================================================================
    const tabs = document.querySelectorAll('.ui-tab-btn');
    const panels = document.querySelectorAll('.ui-panel');
    
    if (tabs.length === 0 || panels.length === 0) {
        console.warn('UI Section: Tabs or panels not found');
        return;
    }
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // タブの切り替え
            tabs.forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            
            // パネルの切り替え
            panels.forEach(p => {
                p.classList.remove('active');
                p.hidden = true;
            });
            
            const activePanel = document.getElementById(`panel-${targetTab}`);
            if (activePanel) {
                activePanel.classList.add('active');
                activePanel.hidden = false;
            }
            
            // Analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'tab_switch', {
                    'event_category': 'engagement',
                    'event_label': targetTab,
                    'transport_type': 'beacon'
                });
            }
        });
        
        // Keyboard Navigation (Arrow Keys)
        tab.addEventListener('keydown', function(e) {
            const currentIndex = Array.from(tabs).indexOf(this);
            let newIndex;
            
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                e.preventDefault();
                newIndex = (currentIndex + 1) % tabs.length;
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                e.preventDefault();
                newIndex = (currentIndex - 1 + tabs.length) % tabs.length;
            } else if (e.key === 'Home') {
                e.preventDefault();
                newIndex = 0;
            } else if (e.key === 'End') {
                e.preventDefault();
                newIndex = tabs.length - 1;
            }
            
            if (newIndex !== undefined) {
                tabs[newIndex].click();
                tabs[newIndex].focus();
            }
        });
    });
    
    // ==========================================================================
    // Intersection Observer - Lazy Animation
    // ==========================================================================
    if ('IntersectionObserver' in window) {
        const observerOptions = {
            rootMargin: '50px 0px',
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = entry.target;
                    
                    // 初期スタイル設定
                    target.style.opacity = '0';
                    target.style.transform = 'translateY(10px)';
                    
                    // アニメーション実行
                    requestAnimationFrame(() => {
                        target.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                        target.style.opacity = '1';
                        target.style.transform = 'translateY(0)';
                    });
                    
                    observer.unobserve(target);
                }
            });
        }, observerOptions);
        
        // Observe elements
        const elementsToObserve = document.querySelectorAll('.ui-card, .ui-list-item, .ui-ranking-item');
        elementsToObserve.forEach(el => {
            observer.observe(el);
        });
    }
    
    // ==========================================================================
    // Analytics Tracking
    // ==========================================================================
    const trackClick = (category, action, label) => {
        if (typeof gtag !== 'undefined') {
            gtag('event', action, {
                'event_category': category,
                'event_label': label,
                'transport_type': 'beacon'
            });
        }
    };
    
    // Track card clicks
    document.querySelectorAll('.ui-card-link').forEach(link => {
        link.addEventListener('click', function() {
            const titleEl = this.querySelector('.ui-card-title');
            if (titleEl) {
                const title = titleEl.textContent.trim();
                const cardType = this.closest('.ui-card').classList.contains('ui-card-featured') 
                    ? 'featured' 
                    : 'regular';
                trackClick('engagement', `card_click_${cardType}`, title);
            }
        });
    });
    
    // Track list clicks
    document.querySelectorAll('.ui-list-link').forEach(link => {
        link.addEventListener('click', function() {
            const titleEl = this.querySelector('.ui-list-title');
            if (titleEl) {
                const title = titleEl.textContent.trim();
                trackClick('engagement', 'list_click', title);
            }
        });
    });
    
    // Track ranking clicks
    document.querySelectorAll('.ui-ranking-link').forEach(link => {
        link.addEventListener('click', function() {
            const rankItem = this.closest('.ui-ranking-item');
            const rankClass = rankItem ? rankItem.className.match(/rank-(\d+)/) : null;
            const rank = rankClass ? rankClass[1] : 'unknown';
            const titleEl = this.querySelector('.ui-ranking-text');
            const title = titleEl ? titleEl.textContent.trim() : 'unknown';
            trackClick('engagement', 'ranking_click', `Rank ${rank}: ${title}`);
        });
    });
    
    // ==========================================================================
    // Performance Monitoring
    // ==========================================================================
    if ('PerformanceObserver' in window) {
        try {
            // Largest Contentful Paint (LCP)
            const lcpObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lastEntry = entries[entries.length - 1];
                const lcpTime = lastEntry.renderTime || lastEntry.loadTime;
                console.log('LCP:', lcpTime.toFixed(2) + 'ms');
                
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'lcp', {
                        'event_category': 'performance',
                        'value': Math.round(lcpTime),
                        'transport_type': 'beacon'
                    });
                }
            });
            lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
            
            // First Input Delay (FID)
            const fidObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                entries.forEach(entry => {
                    const fidTime = entry.processingStart - entry.startTime;
                    console.log('FID:', fidTime.toFixed(2) + 'ms');
                    
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'fid', {
                            'event_category': 'performance',
                            'value': Math.round(fidTime),
                            'transport_type': 'beacon'
                        });
                    }
                });
            });
            fidObserver.observe({ entryTypes: ['first-input'] });
            
        } catch (e) {
            console.warn('Performance observation not supported:', e);
        }
    }
    
    // ==========================================================================
    // Error Handling
    // ==========================================================================
    window.addEventListener('error', function(event) {
        console.error('UI Section Error:', event.error);
    });
    
    // ==========================================================================
    // Initialize
    // ==========================================================================
    console.log('✅ UI Section v30.0 (Columns First - Complete SEO & UX Optimization) Initialized');
    console.log('📊 Tab switching enabled');
    console.log('🎨 Lazy animations active');
    console.log('📈 Analytics tracking configured');
    console.log('♿ Accessibility features enabled');
    console.log('⚡ Performance monitoring active');
    
})();
</script>