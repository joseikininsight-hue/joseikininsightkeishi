<?php
/**
 * Grant Insight Perfect - Front Page Template
 * 完全統合版 v11.0 - SEO/UI/UX 100点満点仕様
 *
 * @package Grant_Insight_Perfect
 * @version 11.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// ===== データ取得の最適化 =====
$cache_key = 'front_page_grant_queries_v11';
$grant_queries = get_transient($cache_key);

if ($grant_queries === false) {
    $today = current_time('Y-m-d');
    $deadline_soon_date = date('Y-m-d', strtotime('+30 days'));
    
    // 締切間近の補助金
    $deadline_soon_query = new WP_Query(array(
        'post_type' => 'grant',
        'posts_per_page' => 9,
        'post_status' => 'publish',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_key' => 'deadline_date',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'deadline_date',
                'value' => $today,
                'compare' => '>=',
                'type' => 'DATE'
            ),
            array(
                'key' => 'deadline_date',
                'value' => $deadline_soon_date,
                'compare' => '<=',
                'type' => 'DATE'
            )
        ),
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ));
    
    // 注目の補助金
    $recommended_query = new WP_Query(array(
        'post_type' => 'grant',
        'posts_per_page' => 9,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array(
            array(
                'key' => 'is_featured',
                'value' => '1',
                'compare' => '='
            )
        ),
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ));
    
    // フォールバック
    if (!$recommended_query->have_posts()) {
        $recommended_query = new WP_Query(array(
            'post_type' => 'grant',
            'posts_per_page' => 9,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ));
    }
    
    // 新着補助金
    $new_query = new WP_Query(array(
        'post_type' => 'grant',
        'posts_per_page' => 9,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ));
    
    $grant_queries = array(
        'deadline_soon' => $deadline_soon_query->posts,
        'recommended' => $recommended_query->posts,
        'new' => $new_query->posts,
    );
    
    // キャッシュ（15分）
    set_transient($cache_key, $grant_queries, 15 * MINUTE_IN_SECONDS);
}

// 構造化データ
// NOTE: WebSite schemaはheader.phpでフロントページのみ出力されるため、ここでは削除
// 重複するWebSite schemaはGoogleのSEO評価に悪影響を与える可能性があります

$schema_breadcrumb = array(
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => array(
        array(
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'ホーム',
            'item' => home_url('/')
        )
    )
);

// Note: CSS/JS are enqueued in functions.php via gi_enqueue_external_assets()
?>

<a href="#main-content" class="skip-to-content" aria-label="メインコンテンツへスキップ">
    メインコンテンツへスキップ
</a>

<main id="main-content" class="site-main" role="main" itemscope itemtype="https://schema.org/WebPage">

    <section class="front-page-section section-animate" id="hero-section" aria-labelledby="hero-heading">
        <?php get_template_part('template-parts/front-page/section', 'hero'); ?>
    </section>

    <?php if (function_exists('ji_display_ad')) : ?>
        <div class="front-ad-space front-ad-hero-bottom"><?php ji_display_ad('front_hero_bottom', 'front-page'); ?></div>
    <?php endif; ?>

    <?php if (function_exists('ji_display_ad')) : ?>
        <div class="front-ad-space front-ad-column-top"><?php ji_display_ad('front_column_zone_top', 'front-page'); ?></div>
    <?php endif; ?>
    
    <section class="front-page-section section-animate" id="column-section" aria-labelledby="column-heading">
        <?php get_template_part('template-parts/column/zone'); ?>
    </section>

    <?php if (function_exists('ji_display_ad')) : ?>
        <div class="front-ad-space front-ad-search-top"><?php ji_display_ad('front_search_top', 'front-page'); ?></div>
    <?php endif; ?>
    
    <section class="front-page-section section-animate" id="grant-zone-section" aria-labelledby="grant-zone-heading">
        <?php 
        set_query_var('exclude_cta', true);
        get_template_part('template-parts/front-page/section', 'search'); 
        ?>
    </section>

    <?php if (function_exists('ji_display_ad')) : ?>
        <div class="front-ad-space front-ad-grant-news-top"><?php ji_display_ad('front_grant_news_top', 'front-page'); ?></div>
    <?php endif; ?>
    
    <section class="front-page-section section-animate" id="grant-news-section" aria-labelledby="grant-news-heading">
        <?php 
        set_query_var('deadline_soon_grants', $grant_queries['deadline_soon']);
        set_query_var('recommended_grants', $grant_queries['recommended']);
        set_query_var('new_grants', $grant_queries['new']);
        get_template_part('template-parts/front-page/grant-tabs-section'); 
        ?>
    </section>

    <?php if (function_exists('ji_display_ad')) : ?>
        <div class="front-ad-space front-ad-grant-news-bottom"><?php ji_display_ad('front_grant_news_bottom', 'front-page'); ?></div>
    <?php endif; ?>

    <?php if (function_exists('ji_display_ad')) : ?>
        <div class="front-ad-space front-ad-cta-top"><?php ji_display_ad('front_cta_top', 'front-page'); ?></div>
    <?php endif; ?>
    
    <section class="front-page-section section-animate" 
             id="final-cta-section"
             aria-labelledby="final-cta-title"
             itemscope 
             itemtype="https://schema.org/Service">
        <?php get_template_part('template-parts/front-page/section', 'cta'); ?>
    </section>

</main>

<div class="scroll-progress" id="scroll-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>

<!-- BreadcrumbList構造化データ -->
<script type="application/ld+json">
<?php echo wp_json_encode($schema_breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
</script>

<?php get_footer(); ?>