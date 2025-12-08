<?php
/**
 * Hero Section - Complete SEO/E-E-A-T/UX Optimized Version
 * ヒーローセクション - 完全最適化版
 *
 * @package Grant_Insight_Perfect
 * @version 51.0.0 - Production Ready
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Enqueue section-hero external CSS and JS
wp_enqueue_style(
    'gic-section-hero',
    get_template_directory_uri() . '/assets/css/section-hero.css',
    array(),
    filemtime(get_template_directory() . '/assets/css/section-hero.css')
);

wp_enqueue_script(
    'gic-section-hero',
    get_template_directory_uri() . '/assets/js/section-hero.js',
    array(),
    filemtime(get_template_directory() . '/assets/js/section-hero.js'),
    true
);

// ==========================================================================
// 設定値
// ==========================================================================
$hero_config = [
    // テキスト
    'tagline'           => '補助金・助成金の検索を、もっとシンプルに。',
    'main_title'        => '補助金・助成金を',
    'sub_title'         => 'AIで効率的に検索',
    'description'       => '全国10,000件以上の補助金・助成金情報から、あなたのビジネスに最適な制度をAIが瞬時にマッチング。専門家監修の信頼できる情報で、申請までサポートします。',
    
    // CTA
    'cta_primary_text'  => '今すぐ補助金を探す',
    'cta_primary_url'   => home_url('/grants/'),
    'cta_secondary_text'=> '3分で無料診断',
    'cta_secondary_url' => 'https://joseikin-insight.com/subsidy-diagnosis/',
    
    // 統計（E-E-A-T強化）
    'stats' => [
        [
            'number' => '10,000',
            'suffix' => '件+',
            'label'  => '掲載補助金数'
        ],
        [
            'number' => '47',
            'suffix' => '都道府県',
            'label'  => '全国対応'
        ],
        [
            'number' => '毎日',
            'suffix' => '',
            'label'  => '情報更新'
        ]
    ],
    
    // 特徴
    'features' => [
        [
            'text' => '経済産業省・中小企業庁の公式データを収集',
            'emphasis' => false
        ],
        [
            'text' => '中小企業診断士による情報監修',
            'emphasis' => true
        ],
        [
            'text' => '会員登録不要・完全無料で利用可能',
            'emphasis' => false
        ]
    ],
    
    // 画像
    'hero_image'        => 'https://joseikin-insight.com/1-3/',
    'hero_image_alt'    => '補助金検索システムのダッシュボード画面',
    'hero_image_width'  => 800,
    'hero_image_height' => 600,
];

// 動的データ取得
$total_grants = wp_count_posts('grant')->publish ?? 0;
if ($total_grants > 0) {
    $hero_config['stats'][0]['number'] = number_format($total_grants);
    $hero_config['stats'][0]['suffix'] = '件';
}
?>

<section 
    class="hero" 
    id="hero-section" 
    role="banner" 
    aria-labelledby="hero-heading"
    itemscope 
    itemtype="https://schema.org/WPHeader">
    
    <!-- 背景パターン -->
    <div class="hero__bg" aria-hidden="true">
        <div class="hero__grid-pattern"></div>
    </div>
    
    <div class="hero__container">
        <div class="hero__content">
            
            <!-- 左カラム: テキストコンテンツ -->
            <div class="hero__text">
                
                <!-- タグライン -->
                <p class="hero__tagline" itemprop="alternativeHeadline">
                    <?php echo esc_html($hero_config['tagline']); ?>
                </p>
                
                <!-- メインタイトル -->
                <h1 class="hero__title" id="hero-heading" itemprop="headline">
                    <span class="hero__title-main">
                        <?php echo esc_html($hero_config['main_title']); ?>
                    </span>
                    <span class="hero__title-accent">
                        <?php echo esc_html($hero_config['sub_title']); ?>
                    </span>
                </h1>
                
                <!-- 説明文 -->
                <p class="hero__description" itemprop="description">
                    <?php echo esc_html($hero_config['description']); ?>
                </p>
                
                <!-- 特徴リスト（E-E-A-T強化） -->
                <ul class="hero__features" aria-label="サービスの特徴">
                    <?php foreach ($hero_config['features'] as $feature): ?>
                    <li class="hero__feature<?php echo $feature['emphasis'] ? ' hero__feature--emphasis' : ''; ?>">
                        <svg class="hero__feature-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.1"/>
                            <path d="M6 10l3 3 5-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                        </svg>
                        <span><?php echo esc_html($feature['text']); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <!-- CTA エリア -->
                <div class="hero__cta">
                    <!-- プライマリCTA -->
                    <a 
                        href="<?php echo esc_url($hero_config['cta_primary_url']); ?>" 
                        class="hero__btn hero__btn--primary"
                        aria-label="<?php echo esc_attr($hero_config['cta_primary_text']); ?> - 補助金検索ページへ移動">
                        <svg class="hero__btn-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" fill="currentColor"/>
                        </svg>
                        <span><?php echo esc_html($hero_config['cta_primary_text']); ?></span>
                        <svg class="hero__btn-arrow" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    
                    <!-- セカンダリCTA（テキストリンク） -->
                    <p class="hero__sub-cta">
                        または 
                        <a 
                            href="<?php echo esc_url($hero_config['cta_secondary_url']); ?>"
                            class="hero__sub-cta-link"
                            aria-label="<?php echo esc_attr($hero_config['cta_secondary_text']); ?> - 補助金診断ページへ移動">
                            <?php echo esc_html($hero_config['cta_secondary_text']); ?>
                        </a>
                        を受ける
                    </p>
                </div>
                
                <!-- 統計バー（E-E-A-T強化） -->
                <div class="hero__stats" role="list" aria-label="サービス実績">
                    <?php foreach ($hero_config['stats'] as $stat): ?>
                    <div class="hero__stat" role="listitem">
                        <span class="hero__stat-number">
                            <?php echo esc_html($stat['number']); ?>
                            <?php if ($stat['suffix']): ?>
                            <span class="hero__stat-suffix"><?php echo esc_html($stat['suffix']); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="hero__stat-label"><?php echo esc_html($stat['label']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
            </div>
            
            <!-- 右カラム: ビジュアル -->
            <div class="hero__visual">
                <figure class="hero__image-wrapper">
                    <img 
                        src="<?php echo esc_url($hero_config['hero_image']); ?>" 
                        alt="<?php echo esc_attr($hero_config['hero_image_alt']); ?>"
                        class="hero__image"
                        width="<?php echo esc_attr($hero_config['hero_image_width']); ?>"
                        height="<?php echo esc_attr($hero_config['hero_image_height']); ?>"
                        loading="eager"
                        fetchpriority="high"
                        decoding="async"
                        itemprop="image">
                    
                    <!-- 装飾要素 -->
                    <div class="hero__image-decoration" aria-hidden="true">
                        <div class="hero__decoration-dot hero__decoration-dot--1"></div>
                        <div class="hero__decoration-dot hero__decoration-dot--2"></div>
                        <div class="hero__decoration-line"></div>
                    </div>
                </figure>
            </div>
            
        </div>
    </div>
    
    <!-- スクロールインジケーター -->
    <div class="hero__scroll" aria-hidden="true">
        <span class="hero__scroll-text">Scroll</span>
        <div class="hero__scroll-line"></div>
    </div>
    
</section>

<style>
/* ==========================================================================
   Hero Section - Design System v51.0
   ========================================================================== */

.hero {
    --hero-color-black: #111111;
    --hero-color-white: #ffffff;
    --hero-color-gray-900: #1a1a1a;
    --hero-color-gray-700: #404040;
    --hero-color-gray-500: #666666;
    --hero-color-gray-300: #999999;
    --hero-color-gray-100: #e5e5e5;
    --hero-color-gray-50: #f8f9fa;
    
        
        if (heroImage.complete) {
