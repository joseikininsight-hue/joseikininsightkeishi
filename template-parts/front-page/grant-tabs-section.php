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

