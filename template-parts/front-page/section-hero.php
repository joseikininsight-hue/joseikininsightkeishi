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
    
    --hero-font-sans: 'Noto Sans JP', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --hero-font-mono: 'SF Mono', 'Monaco', 'Cascadia Code', monospace;
    
    --hero-space-1: 4px;
    --hero-space-2: 8px;
    --hero-space-3: 12px;
    --hero-space-4: 16px;
    --hero-space-5: 20px;
    --hero-space-6: 24px;
    --hero-space-8: 32px;
    --hero-space-10: 40px;
    --hero-space-12: 48px;
    --hero-space-16: 64px;
    --hero-space-20: 80px;
    
    --hero-container-max: 1200px;
    --hero-header-height: 56px;
    
    --hero-transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --hero-transition-slow: 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    --hero-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --hero-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.hero {
    position: relative;
    min-height: calc(100vh - var(--hero-header-height));
    min-height: calc(100dvh - var(--hero-header-height));
    display: flex;
    align-items: center;
    background: var(--hero-color-white);
    font-family: var(--hero-font-sans);
    color: var(--hero-color-black);
    overflow: hidden;
    padding: var(--hero-space-16) 0;
}

.hero__bg {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 0;
}

.hero__grid-pattern {
    position: absolute;
    inset: 0;
    background-image: 
        linear-gradient(to right, var(--hero-color-gray-100) 1px, transparent 1px),
        linear-gradient(to bottom, var(--hero-color-gray-100) 1px, transparent 1px);
    background-size: 40px 40px;
    opacity: 0.5;
}

.hero__container {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: var(--hero-container-max);
    margin: 0 auto;
    padding: 0 var(--hero-space-6);
}

.hero__content {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--hero-space-12);
    align-items: center;
}

@media (min-width: 1024px) {
    .hero__content {
        grid-template-columns: 1fr 1fr;
        gap: var(--hero-space-16);
    }
}

.hero__text {
    display: flex;
    flex-direction: column;
    gap: var(--hero-space-6);
}

.hero__tagline {
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.1em;
    color: var(--hero-color-gray-500);
    margin: 0;
}

.hero__title {
    margin: 0;
    line-height: 1.1;
}

.hero__title-main {
    display: block;
    font-size: clamp(24px, 5vw, 32px);
    font-weight: 400;
    color: var(--hero-color-gray-700);
    margin-bottom: var(--hero-space-2);
}

.hero__title-accent {
    display: block;
    font-size: clamp(36px, 8vw, 56px);
    font-weight: 900;
    color: var(--hero-color-black);
    letter-spacing: -0.02em;
    position: relative;
}

.hero__title-accent::after {
    content: '';
    position: absolute;
    bottom: 0.1em;
    left: 0;
    right: 0;
    height: 0.2em;
    background: linear-gradient(90deg, var(--hero-color-black) 0%, transparent 100%);
    opacity: 0.1;
    z-index: -1;
}

.hero__description {
    font-size: 16px;
    line-height: 1.8;
    color: var(--hero-color-gray-700);
    margin: 0;
    max-width: 540px;
}

.hero__features {
    display: flex;
    flex-direction: column;
    gap: var(--hero-space-3);
    list-style: none;
    margin: 0;
    padding: 0;
}

.hero__feature {
    display: flex;
    align-items: center;
    gap: var(--hero-space-3);
    font-size: 14px;
    color: var(--hero-color-gray-700);
    font-weight: 500;
}

.hero__feature--emphasis {
    color: var(--hero-color-black);
    font-weight: 700;
}

.hero__feature-icon {
    flex-shrink: 0;
    color: var(--hero-color-black);
}

.hero__cta {
    display: flex;
    flex-direction: column;
    gap: var(--hero-space-4);
    margin-top: var(--hero-space-4);
}

.hero__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--hero-space-3);
    padding: var(--hero-space-5) var(--hero-space-8);
    border-radius: 4px;
    font-size: 16px;
    font-weight: 700;
    text-decoration: none;
    transition: 
        background var(--hero-transition),
        transform var(--hero-transition),
        box-shadow var(--hero-transition);
    cursor: pointer;
}

.hero__btn--primary {
    background: var(--hero-color-black);
    color: var(--hero-color-white);
    box-shadow: var(--hero-shadow);
}

.hero__btn--primary:hover {
    background: var(--hero-color-gray-900);
    transform: translateY(-2px);
    box-shadow: var(--hero-shadow-lg);
}

.hero__btn--primary:focus-visible {
    outline: 3px solid var(--hero-color-black);
    outline-offset: 3px;
}

.hero__btn--primary:active {
    transform: translateY(0);
}

.hero__btn-icon {
    flex-shrink: 0;
}

.hero__btn-arrow {
    flex-shrink: 0;
    transition: transform var(--hero-transition);
}

.hero__btn:hover .hero__btn-arrow {
    transform: translateX(4px);
}

.hero__sub-cta {
    font-size: 14px;
    color: var(--hero-color-gray-500);
    margin: 0;
}

.hero__sub-cta-link {
    color: var(--hero-color-black);
    font-weight: 700;
    text-decoration: underline;
    text-underline-offset: 3px;
    transition: opacity var(--hero-transition);
}

.hero__sub-cta-link:hover {
    opacity: 0.7;
}

.hero__sub-cta-link:focus-visible {
    outline: 2px solid var(--hero-color-black);
    outline-offset: 2px;
}

.hero__stats {
    display: flex;
    gap: var(--hero-space-8);
    padding-top: var(--hero-space-8);
    border-top: 1px solid var(--hero-color-gray-100);
    margin-top: var(--hero-space-4);
}

.hero__stat {
    display: flex;
    flex-direction: column;
    gap: var(--hero-space-1);
}

.hero__stat-number {
    font-family: var(--hero-font-mono);
    font-size: 24px;
    font-weight: 900;
    color: var(--hero-color-black);
    letter-spacing: -0.02em;
    line-height: 1;
}

.hero__stat-suffix {
    font-size: 14px;
    font-weight: 700;
}

.hero__stat-label {
    font-size: 12px;
    color: var(--hero-color-gray-500);
    font-weight: 500;
}

.hero__visual {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
}

.hero__image-wrapper {
    position: relative;
    width: 100%;
    max-width: 560px;
    margin: 0;
}

.hero__image {
    width: 100%;
    height: auto;
    display: block;
    border-radius: 4px;
    box-shadow: var(--hero-shadow-lg);
}

.hero__image-decoration {
    position: absolute;
    inset: -20px;
    pointer-events: none;
}

.hero__decoration-dot {
    position: absolute;
    width: 8px;
    height: 8px;
    background: var(--hero-color-black);
    border-radius: 50%;
}

.hero__decoration-dot--1 {
    top: 0;
    right: 0;
}

.hero__decoration-dot--2 {
    bottom: 0;
    left: 0;
}

.hero__decoration-line {
    position: absolute;
    top: 50%;
    right: -40px;
    width: 80px;
    height: 1px;
    background: var(--hero-color-gray-100);
    transform: translateY(-50%);
}

.hero__scroll {
    position: absolute;
    bottom: var(--hero-space-8);
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--hero-space-2);
}

.hero__scroll-text {
    font-family: var(--hero-font-mono);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.2em;
    color: var(--hero-color-gray-300);
    text-transform: uppercase;
}

.hero__scroll-line {
    width: 1px;
    height: 40px;
    background: linear-gradient(to bottom, var(--hero-color-gray-300), transparent);
    animation: hero-scroll-pulse 2s ease-in-out infinite;
}

@keyframes hero-scroll-pulse {
    0%, 100% {
        opacity: 1;
        transform: scaleY(1);
    }
    50% {
        opacity: 0.5;
        transform: scaleY(0.8);
    }
}

/* Responsive */
@media (max-width: 1023px) {
    .hero {
        min-height: auto;
        padding: var(--hero-space-12) 0;
    }
    
    .hero__content {
        text-align: center;
    }
    
    .hero__text {
        align-items: center;
    }
    
    .hero__description {
        max-width: 100%;
    }
    
    .hero__features {
        align-items: center;
    }
    
    .hero__stats {
        justify-content: center;
    }
    
    .hero__visual {
        order: -1;
    }
    
    .hero__image-wrapper {
        max-width: 400px;
    }
    
    .hero__decoration-line {
        display: none;
    }
    
    .hero__scroll {
        display: none;
    }
}

@media (max-width: 640px) {
    .hero {
        padding: var(--hero-space-10) 0;
    }
    
    .hero__container {
        padding: 0 var(--hero-space-4);
    }
    
    .hero__tagline {
        font-size: 12px;
    }
    
    .hero__description {
        font-size: 14px;
    }
    
    .hero__feature {
        font-size: 13px;
    }
    
    .hero__btn {
        width: 100%;
        padding: var(--hero-space-4) var(--hero-space-6);
        font-size: 15px;
    }
    
    .hero__stats {
        flex-wrap: wrap;
        gap: var(--hero-space-6);
        justify-content: center;
    }
    
    .hero__stat {
        min-width: 80px;
        align-items: center;
    }
    
    .hero__stat-number {
        font-size: 20px;
    }
    
    .hero__image-wrapper {
        max-width: 320px;
    }
    
    .hero__image-decoration {
        display: none;
    }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
    .hero__btn,
    .hero__btn-arrow,
    .hero__sub-cta-link {
        transition: none;
    }
    
    .hero__scroll-line {
        animation: none;
    }
}

@media (prefers-contrast: high) {
    .hero__btn--primary {
        border: 2px solid var(--hero-color-white);
    }
}

.hero a:focus-visible,
.hero button:focus-visible {
    outline: 3px solid var(--hero-color-black);
    outline-offset: 3px;
}

/* Print */
@media print {
    .hero {
        min-height: auto;
        padding: 20px 0;
        background: white;
    }
    
    .hero__bg,
    .hero__scroll,
    .hero__image-decoration {
        display: none;
    }
    
    .hero__btn--primary {
        border: 2px solid black;
        background: white;
        color: black;
    }
}
</style>

<script>
(function() {
    'use strict';
    
    const init = () => {
        setupImageLoading();
        setupScrollIndicator();
        setupCTATracking();
        setupKeyboardNavigation();
    };
    
    const setupImageLoading = () => {
        const heroImage = document.querySelector('.hero__image');
        if (!heroImage) return;
        
        if (heroImage.complete) {
            heroImage.classList.add('is-loaded');
        } else {
            heroImage.addEventListener('load', () => {
                heroImage.classList.add('is-loaded');
            }, { once: true });
        }
    };
    
    const setupScrollIndicator = () => {
        const scrollIndicator = document.querySelector('.hero__scroll');
        if (!scrollIndicator) return;
        
        let hasScrolled = false;
        
        const handleScroll = () => {
            if (hasScrolled) return;
            
            if (window.scrollY > 100) {
                hasScrolled = true;
                scrollIndicator.style.opacity = '0';
                scrollIndicator.style.transform = 'translateX(-50%) translateY(20px)';
                window.removeEventListener('scroll', handleScroll);
            }
        };
        
        window.addEventListener('scroll', handleScroll, { passive: true });
    };
    
    const setupCTATracking = () => {
        const primaryCTA = document.querySelector('.hero__btn--primary');
        const secondaryCTA = document.querySelector('.hero__sub-cta-link');
        
        const trackClick = (label, destination) => {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'cta_click', {
                    'event_category': 'hero_section',
                    'event_label': label,
                    'destination': destination,
                    'transport_type': 'beacon'
                });
            }
            
            if (typeof dataLayer !== 'undefined') {
                dataLayer.push({
                    'event': 'hero_cta_click',
                    'cta_label': label,
                    'cta_destination': destination
                });
            }
        };
        
        if (primaryCTA) {
            primaryCTA.addEventListener('click', () => {
                trackClick('primary_search', primaryCTA.href);
            });
        }
        
        if (secondaryCTA) {
            secondaryCTA.addEventListener('click', () => {
                trackClick('secondary_diagnosis', secondaryCTA.href);
            });
        }
    };
    
    const setupKeyboardNavigation = () => {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
        });
        
        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
    };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
