<?php
/**
 * JOSEIKIN INSIGHT - Perfect Header
 * SEO Cleaned版 - Yoast SEO完全対応
 * ホバー廃止・クリック式メニュー
 * 
 * @package Joseikin_Insight_Header
 * @version 10.0.0 (Click Menu Edition)
 */

if (!defined('ABSPATH')) {
    exit;
}

// ヘッダー用データ取得
if (!function_exists('ji_get_header_data')) {
    function ji_get_header_data() {
        $cached = wp_cache_get('ji_header_data', 'joseikin');
        if ($cached !== false) return $cached;
        
        $data = [
            'total_grants' => wp_count_posts('grant')->publish ?? 0,
            'active_grants' => 0,
            'last_updated' => get_option('ji_last_data_update', current_time('Y-m-d')),
            'categories' => [],
            'prefectures' => [],
            'popular_searches' => ['IT導入補助金', '小規模事業者持続化補助金', 'ものづくり補助金']
        ];
        
        $active_query = new WP_Query([
            'post_type' => 'grant',
            'post_status' => 'publish',
            'meta_query' => [['key' => 'grant_status', 'value' => 'active']],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true
        ]);
        $data['active_grants'] = $active_query->found_posts;
        wp_reset_postdata();
        
        $data['categories'] = get_terms(['taxonomy' => 'grant_category', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 10]);
        $data['prefectures'] = get_terms(['taxonomy' => 'grant_prefecture', 'hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC']);
        
        wp_cache_set('ji_header_data', $data, 'joseikin', 3600);
        return $data;
    }
}

$header_data = ji_get_header_data();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <meta name="format-detection" content="telephone=no, email=no, address=no">
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://pagead2.googlesyndication.com">
    <link rel="dns-prefetch" href="https://www.googletagmanager.com">
    <link rel="dns-prefetch" href="https://fundingchoicesmessages.google.com">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome with display=swap -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/fontawesome.min.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/solid.min.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/brands.min.css" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/fontawesome.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/solid.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/brands.min.css">
    </noscript>
    
    <?php wp_head(); ?>
    
    <link rel="stylesheet" href="<?php echo esc_url(get_template_directory_uri() . '/assets/css/header.css'); ?>">
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a href="#main-content" class="ji-skip-link">メインコンテンツへスキップ</a>

<!-- Main Header -->
<header id="ji-header" class="ji-header" role="banner">
    <div class="ji-header-inner">
        <!-- Logo -->
        <a href="<?php echo esc_url(home_url('/')); ?>" class="ji-logo" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?> - ホームへ">
            <div class="ji-logo-image-wrapper">
                <img 
                    src="https://joseikin-insight.com/wp-content/uploads/2025/05/cropped-logo3.webp" 
                    alt="<?php echo esc_attr(get_bloginfo('name')); ?>" 
                    class="ji-logo-image"
                    width="240"
                    height="40"
                    loading="eager"
                    fetchpriority="high"
                    decoding="async"
                >
            </div>
        </a>
        
        <!-- Desktop Navigation -->
        <nav class="ji-nav" role="navigation" aria-label="メインナビゲーション">
            <?php
            $grants_url = get_post_type_archive_link('grant');
            $is_grants_page = is_post_type_archive('grant') || is_singular('grant') || is_tax('grant_category') || is_tax('grant_prefecture');
            ?>
            
            <!-- サービス一覧 -->
            <div class="ji-nav-item" data-menu="services">
                <button type="button" 
                   class="ji-nav-link" 
                   aria-haspopup="true"
                   aria-expanded="false">
                    <i class="fas fa-list-ul ji-icon" aria-hidden="true"></i>
                    <span>サービス一覧</span>
                    <i class="fas fa-chevron-down ji-chevron" aria-hidden="true"></i>
                </button>
                
                <div class="ji-mega-menu" role="menu" aria-label="サービス一覧メニュー">
                    <div class="ji-mega-menu-inner">
                        <div class="ji-mega-menu-header">
                            <div class="ji-mega-menu-title">
                                <i class="fas fa-coins" aria-hidden="true"></i>
                                補助金・助成金を探す
                            </div>
                            <div class="ji-mega-menu-stats">
                                <div class="ji-mega-stat">
                                    <span class="ji-mega-stat-value"><?php echo number_format($header_data['total_grants']); ?></span>
                                    <span class="ji-mega-stat-label">総掲載数</span>
                                </div>
                                <div class="ji-mega-stat">
                                    <span class="ji-mega-stat-value"><?php echo number_format($header_data['active_grants']); ?></span>
                                    <span class="ji-mega-stat-label">募集中</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ji-mega-menu-grid">
                            <div class="ji-mega-column">
                                <div class="ji-mega-column-title">検索方法</div>
                                <a href="<?php echo esc_url($grants_url); ?>" class="ji-mega-link" role="menuitem">すべての補助金・助成金</a>
                                <a href="<?php echo esc_url(add_query_arg('application_status', 'open', $grants_url)); ?>" class="ji-mega-link" role="menuitem">募集中の補助金・助成金<span class="ji-badge">HOT</span></a>
                                <a href="<?php echo esc_url(add_query_arg('orderby', 'deadline', $grants_url)); ?>" class="ji-mega-link" role="menuitem">締切間近</a>
                                <a href="<?php echo esc_url(add_query_arg('orderby', 'new', $grants_url)); ?>" class="ji-mega-link" role="menuitem">新着補助金・助成金<span class="ji-badge new">NEW</span></a>
                                <a href="<?php echo esc_url(add_query_arg('orderby', 'popular', $grants_url)); ?>" class="ji-mega-link" role="menuitem">人気の補助金・助成金</a>
                            </div>
                            
                            <div class="ji-mega-column">
                                <div class="ji-mega-column-title">カテゴリーから探す</div>
                                <?php
                                if ($header_data['categories'] && !is_wp_error($header_data['categories'])) {
                                    foreach (array_slice($header_data['categories'], 0, 8) as $category) {
                                        echo '<a href="' . esc_url(get_term_link($category)) . '" class="ji-mega-link" role="menuitem">' . esc_html($category->name) . '</a>';
                                    }
                                }
                                ?>
                            </div>
                            
                            <div class="ji-mega-column">
                                <div class="ji-mega-column-title">対象者から探す</div>
                                <a href="<?php echo esc_url(add_query_arg('grant_tag', '個人向け', $grants_url)); ?>" class="ji-mega-link" role="menuitem">個人向け</a>
                                <a href="<?php echo esc_url(add_query_arg('grant_tag', '中小企業', $grants_url)); ?>" class="ji-mega-link" role="menuitem">中小企業向け</a>
                                <a href="<?php echo esc_url(add_query_arg('grant_tag', '小規模事業者', $grants_url)); ?>" class="ji-mega-link" role="menuitem">小規模事業者向け</a>
                                <a href="<?php echo esc_url(add_query_arg('grant_tag', 'スタートアップ', $grants_url)); ?>" class="ji-mega-link" role="menuitem">スタートアップ向け</a>
                                <a href="<?php echo esc_url(add_query_arg('grant_tag', 'NPO', $grants_url)); ?>" class="ji-mega-link" role="menuitem">NPO・団体向け</a>
                                <a href="<?php echo esc_url(add_query_arg('grant_tag', '農業', $grants_url)); ?>" class="ji-mega-link" role="menuitem">農業・一次産業向け</a>
                            </div>
                            
                            <div class="ji-mega-column">
                                <div class="ji-mega-column-title">都道府県から探す</div>
                                <div class="ji-prefecture-grid">
                                    <?php
                                    $prefectures_order = ['北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県', '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県', '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県', '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県', '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'];
                                    
                                    $prefecture_terms = [];
                                    if ($header_data['prefectures'] && !is_wp_error($header_data['prefectures'])) {
                                        foreach ($header_data['prefectures'] as $pref) {
                                            $prefecture_terms[$pref->name] = $pref;
                                        }
                                    }
                                    
                                    foreach ($prefectures_order as $pref_name) {
                                        if (isset($prefecture_terms[$pref_name])) {
                                            $pref = $prefecture_terms[$pref_name];
                                            echo '<a href="' . esc_url(get_term_link($pref)) . '" class="ji-prefecture-link" role="menuitem">' . esc_html($pref->name) . '</a>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 補助金診断（直接リンク） -->
            <a href="<?php echo esc_url(home_url('/subsidy-diagnosis/')); ?>" class="ji-nav-link">
                <i class="fas fa-stethoscope ji-icon" aria-hidden="true"></i>
                <span>補助金診断</span>
            </a>
            
            <!-- 当サイトについて（直接リンク） -->
            <a href="<?php echo esc_url(home_url('/about/')); ?>" class="ji-nav-link">
                <i class="fas fa-info-circle ji-icon" aria-hidden="true"></i>
                <span>当サイトについて</span>
            </a>
            
            <a href="<?php echo esc_url(home_url('/column/')); ?>" class="ji-nav-link">
                <i class="fas fa-newspaper ji-icon" aria-hidden="true"></i>
                <span>ニュース</span>
            </a>
            
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="ji-nav-link">
                <i class="fas fa-envelope ji-icon" aria-hidden="true"></i>
                <span>お問い合わせ</span>
            </a>
        </nav>
        
        <!-- Header Actions -->
        <div class="ji-actions">
            <button type="button" id="ji-search-toggle" class="ji-btn ji-btn-icon" aria-label="検索を開く" aria-expanded="false" aria-controls="ji-search-panel">
                <i class="fas fa-search" aria-hidden="true"></i>
            </button>
            
            <a href="<?php echo esc_url($grants_url); ?>" class="ji-btn ji-btn-primary">
                <i class="fas fa-search" aria-hidden="true"></i>
                <span>補助金を探す</span>
            </a>
            
            <button type="button" id="ji-mobile-toggle" class="ji-mobile-toggle" aria-label="メニューを開く" aria-expanded="false" aria-controls="ji-mobile-menu">
                <span class="ji-hamburger">
                    <span class="ji-hamburger-line"></span>
                    <span class="ji-hamburger-line"></span>
                    <span class="ji-hamburger-line"></span>
                </span>
            </button>
        </div>
    </div>
    
    <!-- Search Panel -->
    <div id="ji-search-panel" class="ji-search-panel" role="search" aria-label="サイト内検索">
        <div class="ji-search-panel-inner">
            <form id="ji-search-form" class="ji-search-form" action="<?php echo esc_url($grants_url); ?>" method="get">
                <div class="ji-search-main">
                    <div class="ji-search-input-wrapper">
                        <i class="fas fa-search ji-search-icon" aria-hidden="true"></i>
                        <input type="search" id="ji-search-input" name="search" class="ji-search-input" placeholder="補助金名、キーワードで検索..." autocomplete="off" aria-label="検索キーワード">
                        <button type="button" class="ji-search-clear" aria-label="検索をクリア">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                    
                    <div class="ji-search-suggestions" role="group" aria-label="人気の検索キーワード">
                        <span class="ji-search-suggestion-label">人気:</span>
                        <?php foreach ($header_data['popular_searches'] as $search): ?>
                        <button type="button" class="ji-search-suggestion" data-search="<?php echo esc_attr($search); ?>"><?php echo esc_html($search); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="ji-search-filters">
                    <select name="category" class="ji-search-select" aria-label="カテゴリー">
                        <option value="">すべてのカテゴリー</option>
                        <?php
                        if ($header_data['categories'] && !is_wp_error($header_data['categories'])) {
                            foreach ($header_data['categories'] as $cat) {
                                echo '<option value="' . esc_attr($cat->slug) . '">' . esc_html($cat->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    
                    <select name="prefecture" class="ji-search-select" aria-label="都道府県">
                        <option value="">すべての都道府県</option>
                        <?php
                        if ($header_data['prefectures'] && !is_wp_error($header_data['prefectures'])) {
                            foreach ($header_data['prefectures'] as $pref) {
                                echo '<option value="' . esc_attr($pref->slug) . '">' . esc_html($pref->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    
                    <button type="submit" class="ji-search-submit">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span>検索</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</header>

<!-- Mobile Menu -->
<div id="ji-mobile-menu" class="ji-mobile-menu" role="dialog" aria-modal="true" aria-label="モバイルメニュー">
    <div class="ji-mobile-menu-header">
        <div class="ji-mobile-logo">
            <div class="ji-mobile-logo-icon">
                <img src="https://joseikin-insight.com/wp-content/uploads/2025/05/cropped-logo3.webp" alt="アイコン" width="32" height="32">
            </div>
            <span class="ji-mobile-logo-text">助成金インサイト</span>
        </div>
        <button type="button" id="ji-mobile-close" class="ji-mobile-close" aria-label="メニューを閉じる">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
    </div>
    
    <div class="ji-mobile-search">
        <div class="ji-mobile-search-wrapper">
            <i class="fas fa-search ji-mobile-search-icon" aria-hidden="true"></i>
            <input type="search" id="ji-mobile-search-input" class="ji-mobile-search-input" placeholder="補助金を検索..." aria-label="補助金を検索">
        </div>
    </div>
    
    <div class="ji-mobile-content">
        <div class="ji-mobile-section">
            <!-- サービス一覧（アコーディオン） -->
            <div class="ji-mobile-accordion">
                <button type="button" class="ji-mobile-accordion-trigger" aria-expanded="false" aria-controls="accordion-services">
                    <span>サービス一覧</span>
                    <i class="fas fa-chevron-down" aria-hidden="true"></i>
                </button>
                <div id="accordion-services" class="ji-mobile-accordion-content">
                    <a href="<?php echo esc_url($grants_url); ?>" class="ji-mobile-link">すべての補助金・助成金</a>
                    <a href="<?php echo esc_url(add_query_arg('application_status', 'open', $grants_url)); ?>" class="ji-mobile-link">募集中の補助金・助成金</a>
                    <a href="<?php echo esc_url(add_query_arg('orderby', 'deadline', $grants_url)); ?>" class="ji-mobile-link">締切間近</a>
                    <a href="<?php echo esc_url(add_query_arg('orderby', 'new', $grants_url)); ?>" class="ji-mobile-link">新着補助金・助成金</a>
                    <a href="<?php echo esc_url(home_url('/categories/')); ?>" class="ji-mobile-link">カテゴリー一覧</a>
                    <a href="<?php echo esc_url(home_url('/prefectures/')); ?>" class="ji-mobile-link">都道府県一覧</a>
                </div>
            </div>
            
            <!-- 補助金診断（単独リンク） -->
            <a href="<?php echo esc_url(home_url('/subsidy-diagnosis/')); ?>" class="ji-mobile-single-link">
                <span>補助金診断</span>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </a>
            
            <!-- 当サイトについて（単独リンク） -->
            <a href="<?php echo esc_url(home_url('/about/')); ?>" class="ji-mobile-single-link">
                <span>当サイトについて</span>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </a>
            
            <!-- ニュース（単独リンク） -->
            <a href="<?php echo esc_url(home_url('/column/')); ?>" class="ji-mobile-single-link">
                <span>ニュース</span>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </a>
            
            <!-- お問い合わせ（単独リンク） -->
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="ji-mobile-single-link">
                <span>お問い合わせ</span>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </a>
        </div>
        
        <a href="<?php echo esc_url($grants_url); ?>" class="ji-mobile-cta">
            <i class="fas fa-search" aria-hidden="true"></i>
            <span>補助金・助成金を探す</span>
        </a>
        
        <div class="ji-mobile-stats">
            <div class="ji-mobile-stat">
                <span class="ji-mobile-stat-value"><?php echo number_format($header_data['total_grants']); ?></span>
                <span class="ji-mobile-stat-label">掲載数</span>
            </div>
            <div class="ji-mobile-stat">
                <span class="ji-mobile-stat-value"><?php echo number_format($header_data['active_grants']); ?></span>
                <span class="ji-mobile-stat-label">募集中</span>
            </div>
            <div class="ji-mobile-stat">
                <span class="ji-mobile-stat-value">47</span>
                <span class="ji-mobile-stat-label">都道府県</span>
            </div>
        </div>
    </div>
    
    <div class="ji-mobile-footer">
        <div class="ji-mobile-social">
            <a href="https://twitter.com/joseikininsight" class="ji-mobile-social-link" aria-label="Twitter" target="_blank" rel="noopener"><i class="fab fa-twitter"></i></a>
            <a href="https://facebook.com/joseikin.insight" class="ji-mobile-social-link" aria-label="Facebook" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
            <a href="https://www.youtube.com/channel/UCbfjOrG3nSPI3GFzKnGcspQ" class="ji-mobile-social-link" aria-label="YouTube" target="_blank" rel="noopener"><i class="fab fa-youtube"></i></a>
            <a href="https://note.com/joseikin_insight" class="ji-mobile-social-link" aria-label="Note" target="_blank" rel="noopener"><i class="fas fa-sticky-note"></i></a>
        </div>
        
        <div class="ji-mobile-trust">
            <span class="ji-mobile-trust-badge"><i class="fas fa-shield-alt"></i>専門家監修</span>
            <span class="ji-mobile-trust-badge"><i class="fas fa-sync-alt"></i>毎日更新</span>
        </div>
        
        <div class="ji-mobile-copyright">&copy; <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?></div>
    </div>
</div>

<!-- Header Placeholder -->
<div class="ji-header-placeholder" aria-hidden="true"></div>

<main id="main-content" role="main" tabindex="-1">

<script>
(function() {
    'use strict';
    
    const header = document.getElementById('ji-header');
    const searchToggle = document.getElementById('ji-search-toggle');
    const searchPanel = document.getElementById('ji-search-panel');
    const searchInput = document.getElementById('ji-search-input');
    const mobileToggle = document.getElementById('ji-mobile-toggle');
    const mobileMenu = document.getElementById('ji-mobile-menu');
    const mobileClose = document.getElementById('ji-mobile-close');
    const mobileSearchInput = document.getElementById('ji-mobile-search-input');
    const navItems = document.querySelectorAll('.ji-nav-item[data-menu]');
    
    let lastScrollY = 0;
    let isSearchOpen = false;
    let isMobileMenuOpen = false;
    let ticking = false;
    
    // クリック式メガメニュー（ホバー廃止）
    function initMegaMenus() {
        navItems.forEach(item => {
            const link = item.querySelector('.ji-nav-link');
            const menu = item.querySelector('.ji-mega-menu');
            
            if (!menu || !link) return;
            
            // クリックでトグル
            link.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const isExpanded = item.classList.contains('menu-active');
                
                // 他のメニューを閉じる
                navItems.forEach(otherItem => {
                    if (otherItem !== item) {
                        otherItem.classList.remove('menu-active');
                        const otherLink = otherItem.querySelector('.ji-nav-link');
                        if (otherLink) otherLink.setAttribute('aria-expanded', 'false');
                    }
                });
                
                // 現在のメニューをトグル
                if (isExpanded) {
                    item.classList.remove('menu-active');
                    link.setAttribute('aria-expanded', 'false');
                } else {
                    item.classList.add('menu-active');
                    link.setAttribute('aria-expanded', 'true');
                }
            });
            
            // キーボード操作
            link.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    link.click();
                }
                
                if (e.key === 'Escape') {
                    item.classList.remove('menu-active');
                    link.setAttribute('aria-expanded', 'false');
                    link.focus();
                }
            });
        });
        
        // 外部クリックで閉じる
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.ji-nav-item')) {
                navItems.forEach(item => {
                    item.classList.remove('menu-active');
                    const link = item.querySelector('.ji-nav-link');
                    if (link) link.setAttribute('aria-expanded', 'false');
                });
            }
        });
    }
    
    function handleScroll() {
        const scrollY = window.scrollY;
        
        if (scrollY > 50) {
            header?.classList.add('scrolled');
        } else {
            header?.classList.remove('scrolled');
        }
        
        if (scrollY > 150) {
            if (scrollY > lastScrollY + 5) {
                header?.classList.add('hidden');
                // スクロール時にメニューを閉じる
                navItems.forEach(item => {
                    item.classList.remove('menu-active');
                    const link = item.querySelector('.ji-nav-link');
                    if (link) link.setAttribute('aria-expanded', 'false');
                });
            } else if (scrollY < lastScrollY - 5) {
                header?.classList.remove('hidden');
            }
        } else {
            header?.classList.remove('hidden');
        }
        
        lastScrollY = scrollY;
        ticking = false;
    }
    
    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(handleScroll);
            ticking = true;
        }
    }
    
    function toggleSearch() {
        isSearchOpen = !isSearchOpen;
        searchPanel?.classList.toggle('open', isSearchOpen);
        
        // メガメニューを閉じる
        navItems.forEach(item => {
            item.classList.remove('menu-active');
            const link = item.querySelector('.ji-nav-link');
            if (link) link.setAttribute('aria-expanded', 'false');
        });
        
        if (searchToggle) {
            searchToggle.setAttribute('aria-expanded', isSearchOpen);
            searchToggle.innerHTML = isSearchOpen 
                ? '<i class="fas fa-times" aria-hidden="true"></i>'
                : '<i class="fas fa-search" aria-hidden="true"></i>';
        }
        
        if (isSearchOpen && searchInput) {
            setTimeout(() => searchInput.focus(), 150);
        }
    }
    
    function closeSearch() {
        if (!isSearchOpen) return;
        isSearchOpen = false;
        searchPanel?.classList.remove('open');
        if (searchToggle) {
            searchToggle.setAttribute('aria-expanded', 'false');
            searchToggle.innerHTML = '<i class="fas fa-search" aria-hidden="true"></i>';
        }
    }
    
    function openMobileMenu() {
        isMobileMenuOpen = true;
        mobileMenu?.classList.add('open');
        mobileToggle?.setAttribute('aria-expanded', 'true');
        document.body.classList.add('menu-open');
        setTimeout(() => mobileClose?.focus(), 100);
    }
    
    function closeMobileMenu() {
        if (!isMobileMenuOpen) return;
        isMobileMenuOpen = false;
        mobileMenu?.classList.remove('open');
        mobileToggle?.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('menu-open');
        mobileToggle?.focus();
    }
    
    function initAccordions() {
        document.querySelectorAll('.ji-mobile-accordion-trigger').forEach(trigger => {
            trigger.addEventListener('click', () => {
                const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
                const contentId = trigger.getAttribute('aria-controls');
                const content = document.getElementById(contentId);
                
                // 他のアコーディオンを閉じる
                document.querySelectorAll('.ji-mobile-accordion-trigger').forEach(t => {
                    if (t !== trigger) {
                        t.setAttribute('aria-expanded', 'false');
                        const c = document.getElementById(t.getAttribute('aria-controls'));
                        c?.classList.remove('open');
                    }
                });
                
                trigger.setAttribute('aria-expanded', !isExpanded);
                content?.classList.toggle('open', !isExpanded);
            });
        });
    }
    
    function initSearchSuggestions() {
        document.querySelectorAll('.ji-search-suggestion').forEach(btn => {
            btn.addEventListener('click', () => {
                if (searchInput) {
                    searchInput.value = btn.dataset.search;
                    searchInput.focus();
                }
            });
        });
    }
    
    function initSearchClear() {
        const clearBtn = document.querySelector('.ji-search-clear');
        if (clearBtn && searchInput) {
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                searchInput.focus();
            });
        }
    }
    
    function initMobileSearch() {
        if (mobileSearchInput) {
            mobileSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const query = mobileSearchInput.value.trim();
                    if (query) {
                        window.location.href = '<?php echo esc_url($grants_url); ?>?search=' + encodeURIComponent(query);
                    }
                }
            });
        }
    }
    
    function trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        element.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                } else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        });
    }
    
    // イベントリスナー
    window.addEventListener('scroll', requestTick, { passive: true });
    
    searchToggle?.addEventListener('click', toggleSearch);
    mobileToggle?.addEventListener('click', openMobileMenu);
    mobileClose?.addEventListener('click', closeMobileMenu);
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (isMobileMenuOpen) closeMobileMenu();
            else if (isSearchOpen) closeSearch();
            else {
                navItems.forEach(item => {
                    item.classList.remove('menu-active');
                    const link = item.querySelector('.ji-nav-link');
                    if (link) link.setAttribute('aria-expanded', 'false');
                });
            }
        }
        
        // Ctrl/Cmd + K で検索
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            toggleSearch();
        }
    });
    
    document.addEventListener('click', (e) => {
        if (isSearchOpen && !e.target.closest('.ji-search-panel') && !e.target.closest('#ji-search-toggle')) {
            closeSearch();
        }
    });
    
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024 && isMobileMenuOpen) {
            closeMobileMenu();
        }
    });
    
    // 初期化
    initMegaMenus();
    initAccordions();
    initSearchSuggestions();
    initSearchClear();
    initMobileSearch();
    
    if (mobileMenu) {
        trapFocus(mobileMenu);
    }
    
    handleScroll();
    
    console.log('[✓] Joseikin Insight Header v10.0.0 - Click Menu Edition');
})();
</script>
