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
?>

<link rel="stylesheet" href="<?php echo esc_url(get_template_directory_uri() . '/assets/css/front-page.css?v=11'); ?>">

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
        
        <div class="cta-diagnosis-section">
            <div class="cta-diagnosis-wrapper">
                <div class="cta-diagnosis-content">
                    <div class="cta-icon">
                        <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                    </div>
                    
                    <h2 id="final-cta-title" class="cta-title" itemprop="name">
                        あなたに最適な補助金を無料診断
                    </h2>
                    
                    <p class="cta-description" itemprop="description">
                        簡単な質問に答えるだけで、あなたの事業に最適な補助金・助成金を診断します。<br class="pc-only">
                        診断は完全無料、所要時間はわずか3分です。
                    </p>
                    
                    <div class="cta-button-group">
                        <a href="<?php echo esc_url(home_url('/grants/')); ?>" 
                           class="cta-button cta-button-secondary"
                           title="補助金一覧から探す">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <span>補助金を探す</span>
                        </a>

                        <a href="https://joseikin-insight.com/subsidy-diagnosis/" 
                           class="cta-button cta-button-primary"
                           itemprop="url"
                           title="無料診断を今すぐ始める">
                            <i class="fas fa-play-circle" aria-hidden="true"></i>
                            <span>今すぐ無料診断を始める</span>
                        </a>
                    </div>

                    <p class="cta-note">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        <span>会員登録不要・メールアドレス不要</span>
                    </p>
                </div>
            </div>
        </div>
    </section>

</main>

<div class="scroll-progress" id="scroll-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>

<!-- BreadcrumbList構造化データ -->
<script type="application/ld+json">
<?php echo wp_json_encode($schema_breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
</script>

<script>
/**
 * Front Page JS - v12.0 (Performance Optimized)
 * カクカク問題修正: scrollHeight/innerHeightをキャッシュ化
 */
(function() {
    'use strict';
    
    // ★パフォーマンス改善: 高さをキャッシュ
    let cachedWinH = window.innerHeight;
    let cachedDocH = document.documentElement.scrollHeight;
    let resizeTimer;
    
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            cachedWinH = window.innerHeight;
            cachedDocH = document.documentElement.scrollHeight;
        }, 150);
    }, {passive: true});
    
    // DOMContentLoaded後に高さを再取得
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                cachedDocH = document.documentElement.scrollHeight;
            }, 100);
            init();
        });
    } else {
        init();
    }
    
    function init() {
        setupScrollProgress();
        // アニメーションは即座に表示（カクカク防止）
        document.querySelectorAll('.section-animate').forEach(el => el.classList.add('visible'));
        setupSmoothScroll();
        if('performance' in window) monitorPerf();
        setupSEOTracking();
    }
    
    function setupScrollProgress() {
        const bar = document.getElementById('scroll-progress');
        if(!bar) return;
        let ticking = false;
        window.addEventListener('scroll', () => {
            if(!ticking) {
                window.requestAnimationFrame(() => {
                    // キャッシュした値を使用
                    const pct = cachedDocH - cachedWinH > 0 ? (window.scrollY / (cachedDocH - cachedWinH)) * 100 : 0;
                    bar.style.width = Math.min(Math.max(pct, 0), 100) + '%';
                    ticking = false;
                });
                ticking = true;
            }
        }, {passive:true});
    }
    
    function setupSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', function(e) {
                const h = this.getAttribute('href');
                if(h && h !== '#' && h !== '#0') {
                    const t = document.querySelector(h);
                    if(t) {
                        e.preventDefault();
                        window.scrollTo({top: t.getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth'});
                    }
                }
            });
        });
    }

    function monitorPerf() {
        window.addEventListener('load', () => {
            // 高さを最終更新
            cachedDocH = document.documentElement.scrollHeight;
            setTimeout(() => {
                const p = performance.getEntriesByType('navigation')[0];
                if(p && typeof gtag !== 'undefined') {
                    gtag('event', 'page_timing', {'event_category':'Performance', 'value':Math.round(p.loadEventEnd - p.loadEventStart)});
                }
            }, 0);
        });
    }

    function setupSEOTracking() {
        let maxScroll = 0;
        const points = [25,50,75,100];
        const tracked = new Set();
        let tick = false;
        
        window.addEventListener('scroll', () => {
            if(!tick) {
                window.requestAnimationFrame(() => {
                    // キャッシュした値を使用
                    const pct = Math.round((window.scrollY / (cachedDocH - cachedWinH)) * 100);
                    if(pct > maxScroll) {
                        maxScroll = pct;
                        points.forEach(p => {
                            if(pct >= p && !tracked.has(p)) {
                                tracked.add(p);
                                if(typeof gtag !== 'undefined') {
                                    gtag('event', 'scroll_depth', {'event_category':'Engagement', 'event_label':p+'%', 'value':p});
                                }
                            }
                        });
                    }
                    tick = false;
                });
                tick = true;
            }
        }, {passive:true});
    }
})();
</script>

<?php get_footer(); ?>