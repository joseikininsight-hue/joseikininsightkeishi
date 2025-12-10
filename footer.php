<?php
/**
 * JOSEIKIN INSIGHT - Government Style Footer
 * 官公庁風デザイン - 信頼性・公共性・堅実性を演出
 * 
 * @package Joseikin_Insight_Footer
 * @version 9.1.0
 */

// SNS URLヘルパー関数
if (!function_exists('gi_get_sns_urls')) {
    function gi_get_sns_urls() {
        return [
            'twitter' => get_option('gi_sns_twitter_url', 'https://twitter.com/joseikininsight'),
            'facebook' => get_option('gi_sns_facebook_url', 'https://facebook.com/joseikin.insight'),
            'linkedin' => get_option('gi_sns_linkedin_url', ''),
            'instagram' => get_option('gi_sns_instagram_url', 'https://instagram.com/joseikin_insight'),
            'youtube' => get_option('gi_sns_youtube_url', 'https://www.youtube.com/channel/UCbfjOrG3nSPI3GFzKnGcspQ'),
            'note' => get_option('gi_sns_note_url', 'https://note.com/joseikin_insight')
        ];
    }
}

// 統計情報取得関数
if (!function_exists('gi_get_cached_stats')) {
    function gi_get_cached_stats() {
        $stats = wp_cache_get('gi_stats', 'grant_insight');
        
        if (false === $stats) {
            $stats = [
                'total_grants' => wp_count_posts('grant')->publish ?? 0,
                'active_grants' => 0,
                'today_updated' => 0
            ];
            
            // 募集中の助成金数を取得
            $active_query = new WP_Query([
                'post_type' => 'grant',
                'post_status' => 'publish',
                'meta_query' => [
                    [
                        'key' => 'grant_status',
                        'value' => 'active',
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => -1,
                'fields' => 'ids'
            ]);
            $stats['active_grants'] = $active_query->found_posts;
            wp_reset_postdata();
            
            // 本日更新数を取得
            $today_query = new WP_Query([
                'post_type' => 'grant',
                'post_status' => 'publish',
                'date_query' => [
                    ['after' => 'today']
                ],
                'posts_per_page' => -1,
                'fields' => 'ids'
            ]);
            $stats['today_updated'] = $today_query->found_posts;
            wp_reset_postdata();
            
            wp_cache_set('gi_stats', $stats, 'grant_insight', 3600);
        }
        
        return $stats;
    }
}
?>

    </main>

    <style>
        /* ===============================================
           JOSEIKIN INSIGHT - GOVERNMENT STYLE FOOTER
           官公庁風デザイン v9.1.0
           =============================================== */
        
        :root {
            /* Government Color Palette */
            --gov-navy: #0D2A52;
            --gov-navy-dark: #081C38;
            --gov-navy-light: #1A3D6E;
            --gov-gold: #C5A059;
            --gov-gold-light: #D4B57A;
            --gov-white: #FFFFFF;
            --gov-gray-100: #F8F9FA;
            --gov-gray-200: #E9ECEF;
            --gov-gray-300: #DEE2E6;
            --gov-gray-400: #CED4DA;
            --gov-gray-500: #ADB5BD;
            --gov-gray-600: #6C757D;
            --gov-text-light: rgba(255, 255, 255, 0.9);
            --gov-text-muted: rgba(255, 255, 255, 0.7);
            --gov-border: rgba(255, 255, 255, 0.15);
            --gov-font: 'Noto Sans JP', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --gov-transition: 0.2s ease;
            --gov-max-width: 1200px;
        }
        
        /* ===============================================
           BASE FOOTER STYLES
           =============================================== */
        .gov-footer {
            background: var(--gov-navy);
            color: var(--gov-white);
            font-family: var(--gov-font);
            position: relative;
            border-top: 4px solid var(--gov-gold);
        }
        
        .gov-footer-inner {
            max-width: var(--gov-max-width);
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        @media (min-width: 768px) {
            .gov-footer-inner {
                padding: 0 2rem;
            }
        }
        
        /* ===============================================
           MAIN NAVIGATION - 4カラム構成
           =============================================== */
        .gov-footer-nav {
            padding: 3rem 0;
            border-bottom: 1px solid var(--gov-border);
        }
        
        .gov-nav-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2.5rem;
        }
        
        @media (min-width: 640px) {
            .gov-nav-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1024px) {
            .gov-nav-grid {
                grid-template-columns: 1.5fr 1fr 1fr;
                gap: 2rem;
            }
        }
        
        /* Column 1: ロゴ・連絡先 */
        .gov-nav-brand {
            grid-column: 1 / -1;
        }
        
        @media (min-width: 1024px) {
            .gov-nav-brand {
                grid-column: auto;
            }
        }
        
        .gov-brand-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            text-decoration: none;
        }
        
        .gov-logo-image {
            height: 36px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
            display: block;
        }
        
        @media (max-width: 767px) {
            .gov-logo-image {
                height: 32px;
                max-width: 180px;
            }
        }
        
        .gov-brand-description {
            font-size: 0.875rem;
            color: var(--gov-text-muted);
            line-height: 1.7;
            margin-bottom: 1.5rem;
            max-width: 320px;
        }
        
        .gov-contact-info {
            margin-bottom: 1.5rem;
        }
        
        .gov-contact-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            color: var(--gov-text-light);
        }
        
        .gov-contact-item i {
            color: var(--gov-gold);
            width: 16px;
            margin-top: 0.125rem;
        }
        
        .gov-contact-item a {
            color: var(--gov-text-light);
            text-decoration: none;
            transition: color var(--gov-transition);
        }
        
        .gov-contact-item a:hover {
            color: var(--gov-gold);
        }
        
        /* Social Links */
        .gov-social-row {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .gov-social-link {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gov-white);
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--gov-border);
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9375rem;
            transition: all var(--gov-transition);
        }
        
        .gov-social-link:hover {
            background: var(--gov-gold);
            color: var(--gov-navy);
            border-color: var(--gov-gold);
            transform: translateY(-2px);
        }
        
        /* Navigation Columns */
        .gov-nav-column {
            display: flex;
            flex-direction: column;
        }
        
        .gov-nav-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--gov-white);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-left: 3px solid var(--gov-gold);
            padding-left: 0.75rem;
        }
        
        .gov-nav-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .gov-nav-link {
            color: var(--gov-text-light);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all var(--gov-transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
        }
        
        .gov-nav-link:hover {
            color: var(--gov-gold);
            padding-left: 0.75rem;
        }
        
        .gov-nav-link i {
            font-size: 0.625rem;
            opacity: 0.7;
            color: var(--gov-gold);
            transition: all var(--gov-transition);
        }
        
        .gov-nav-link:hover i {
            opacity: 1;
            transform: translateX(4px);
        }
        
        /* Live Badge */
        .gov-nav-link-live {
            position: relative;
        }
        
        .gov-live-dot {
            width: 6px;
            height: 6px;
            background: #22c55e;
            border-radius: 50%;
            animation: gov-pulse 2s infinite;
        }
        
        @keyframes gov-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* ===============================================
           TRUST SECTION - 信頼性
           =============================================== */
        .gov-footer-trust {
            padding: 2rem 0;
            border-bottom: 1px solid var(--gov-border);
        }
        
        .gov-trust-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        @media (min-width: 1024px) {
            .gov-trust-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        .gov-trust-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--gov-border);
            border-radius: 8px;
            transition: all var(--gov-transition);
        }
        
        .gov-trust-item:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: var(--gov-gold);
        }
        
        .gov-trust-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gov-gold);
            border-radius: 50%;
            flex-shrink: 0;
        }
        
        .gov-trust-icon i {
            font-size: 0.9375rem;
            color: var(--gov-navy);
        }
        
        .gov-trust-content h4 {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--gov-white);
            margin-bottom: 0.125rem;
            line-height: 1.4;
        }
        
        .gov-trust-content p {
            font-size: 0.75rem;
            color: var(--gov-text-muted);
            line-height: 1.4;
            margin: 0;
        }
        
        /* ===============================================
           BOTTOM SECTION
           =============================================== */
        .gov-footer-bottom {
            padding: 1.5rem 0;
            background: var(--gov-navy-dark);
        }
        
        .gov-bottom-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
        }
        
        @media (min-width: 768px) {
            .gov-bottom-wrapper {
                flex-direction: row;
                justify-content: space-between;
            }
        }
        
        /* Left Side - Copyright & Info */
        .gov-bottom-left {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: center;
            text-align: center;
        }
        
        @media (min-width: 768px) {
            .gov-bottom-left {
                align-items: flex-start;
                text-align: left;
            }
        }
        
        .gov-copyright {
            font-size: 0.8125rem;
            color: var(--gov-text-muted);
            font-weight: 500;
        }
        
        .gov-copyright strong {
            color: var(--gov-text-light);
            font-weight: 700;
        }
        
        .gov-update-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--gov-text-muted);
        }
        
        .gov-update-dot {
            width: 6px;
            height: 6px;
            background: #22c55e;
            border-radius: 50%;
        }
        
        /* Right Side - Legal Links */
        .gov-legal-links {
            display: flex;
            gap: 1.25rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        @media (min-width: 768px) {
            .gov-legal-links {
                justify-content: flex-end;
            }
        }
        
        .gov-legal-link {
            color: var(--gov-text-muted);
            text-decoration: none;
            font-size: 0.8125rem;
            font-weight: 500;
            transition: color var(--gov-transition);
        }
        
        .gov-legal-link:hover {
            color: var(--gov-gold);
        }
        
        /* ===============================================
           SCHEMA MARKUP (Hidden)
           =============================================== */
        .gov-schema-data {
            display: none !important;
        }
        
        /* ===============================================
           JETPACK CAROUSEL FIX
           =============================================== */
        #jp-carousel-loading-wrapper,
        div[id^="jp-carousel"] {
            display: none !important;
            height: 0 !important;
            overflow: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* ===============================================
           ACCESSIBILITY
           =============================================== */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        .gov-footer a:focus-visible,
        .gov-footer button:focus-visible {
            outline: 2px solid var(--gov-gold);
            outline-offset: 2px;
        }
        
        /* Skip Link for Accessibility */
        .gov-skip-link {
            position: absolute;
            left: -9999px;
            top: auto;
            width: 1px;
            height: 1px;
            overflow: hidden;
        }
        
        .gov-skip-link:focus {
            position: fixed;
            top: 0;
            left: 0;
            width: auto;
            height: auto;
            padding: 1rem 2rem;
            background: var(--gov-gold);
            color: var(--gov-navy);
            font-weight: 700;
            z-index: 9999;
        }
    </style>

    <!-- Government Style Footer -->
    <footer class="gov-footer" role="contentinfo" itemscope itemtype="https://schema.org/WPFooter">
        
        <!-- Schema.org Organization Data -->
        <div class="gov-schema-data" itemscope itemtype="https://schema.org/Organization">
            <meta itemprop="name" content="<?php bloginfo('name'); ?>">
            <meta itemprop="url" content="<?php echo esc_url(home_url('/')); ?>">
            <meta itemprop="logo" content="https://joseikin-insight.com/gemini_generated_image_19k6yi19k6yi19k6/">
            <meta itemprop="description" content="日本全国の補助金・助成金情報を網羅した検索プラットフォーム。専門家監修のもと、最新情報を毎日更新しています。">
            <?php 
            $sns_urls = gi_get_sns_urls();
            foreach ($sns_urls as $url) {
                if (!empty($url)) {
                    echo '<link itemprop="sameAs" href="' . esc_url($url) . '" />';
                }
            }
            ?>
            <div itemprop="address" itemscope itemtype="https://schema.org/PostalAddress">
                <meta itemprop="addressCountry" content="JP">
            </div>
        </div>
        
        <div class="gov-footer-inner">
            
            <!-- Main Navigation - 4カラム構成 -->
            <nav class="gov-footer-nav" aria-label="フッターナビゲーション">
                <div class="gov-nav-grid">
                    
                    <!-- Column 1: ロゴ・連絡先 -->
                    <div class="gov-nav-brand">
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="gov-brand-logo" aria-label="<?php bloginfo('name'); ?> ホームへ">
                            <img src="https://joseikin-insight.com/gemini_generated_image_19k6yi19k6yi19k6/" alt="Joseikin Insight" width="200" height="36" class="gov-logo-image">
                        </a>
                        
                        <p class="gov-brand-description">
                            日本全国の補助金・助成金情報を一元化。あなたのビジネスや生活に最適な支援制度を見つけるお手伝いをします。
                        </p>
                        
                        <div class="gov-contact-info">
                            <div class="gov-contact-item">
                                <i class="fas fa-envelope" aria-hidden="true"></i>
                                <a href="<?php echo esc_url(home_url('/contact/')); ?>">お問い合わせフォーム</a>
                            </div>
                            <div class="gov-contact-item">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                <span>営業時間: 平日 9:00〜18:00</span>
                            </div>
                        </div>
                        
                        <div class="gov-social-row" aria-label="ソーシャルメディア">
                            <?php
                            $sns_urls = gi_get_sns_urls();
                            // X (Twitter) - SVGインラインで確実に表示
                            if (!empty($sns_urls['twitter'])) {
                                echo '<a href="' . esc_url($sns_urls['twitter']) . '" class="gov-social-link" aria-label="Xでフォロー" target="_blank" rel="noopener noreferrer">';
                                echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>';
                                echo '</a>';
                            }
                            // Facebook
                            if (!empty($sns_urls['facebook'])) {
                                echo '<a href="' . esc_url($sns_urls['facebook']) . '" class="gov-social-link" aria-label="Facebookでフォロー" target="_blank" rel="noopener noreferrer">';
                                echo '<i class="fab fa-facebook-f" aria-hidden="true"></i>';
                                echo '</a>';
                            }
                            // Instagram
                            if (!empty($sns_urls['instagram'])) {
                                echo '<a href="' . esc_url($sns_urls['instagram']) . '" class="gov-social-link" aria-label="Instagramでフォロー" target="_blank" rel="noopener noreferrer">';
                                echo '<i class="fab fa-instagram" aria-hidden="true"></i>';
                                echo '</a>';
                            }
                            // YouTube
                            if (!empty($sns_urls['youtube'])) {
                                echo '<a href="' . esc_url($sns_urls['youtube']) . '" class="gov-social-link" aria-label="YouTubeでフォロー" target="_blank" rel="noopener noreferrer">';
                                echo '<i class="fab fa-youtube" aria-hidden="true"></i>';
                                echo '</a>';
                            }
                            // note
                            if (!empty($sns_urls['note'])) {
                                echo '<a href="' . esc_url($sns_urls['note']) . '" class="gov-social-link" aria-label="noteでフォロー" target="_blank" rel="noopener noreferrer">';
                                echo '<i class="fas fa-pen-nib" aria-hidden="true"></i>';
                                echo '</a>';
                            }
                            // LinkedIn
                            if (!empty($sns_urls['linkedin'])) {
                                echo '<a href="' . esc_url($sns_urls['linkedin']) . '" class="gov-social-link" aria-label="LinkedInでフォロー" target="_blank" rel="noopener noreferrer">';
                                echo '<i class="fab fa-linkedin-in" aria-hidden="true"></i>';
                                echo '</a>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Column 2: 主要リンク -->
                    <div class="gov-nav-column">
                        <h3 class="gov-nav-title">主要リンク</h3>
                        <ul class="gov-nav-list">
                            <li>
                                <a href="<?php echo esc_url(get_post_type_archive_link('grant')); ?>" class="gov-nav-link">
                                    補助金・助成金一覧
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(add_query_arg('application_status', 'open', get_post_type_archive_link('grant'))); ?>" class="gov-nav-link gov-nav-link-live">
                                    <span class="gov-live-dot" aria-hidden="true"></span>
                                    募集中の補助金
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(home_url('/subsidy-diagnosis/')); ?>" class="gov-nav-link">
                                    補助金診断
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(home_url('/column/')); ?>" class="gov-nav-link">
                                    コラム
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Column 3: サポート -->
                    <div class="gov-nav-column">
                        <h3 class="gov-nav-title">サポート</h3>
                        <ul class="gov-nav-list">
                            <li>
                                <a href="<?php echo esc_url(home_url('/about/')); ?>" class="gov-nav-link">
                                    当サイトについて
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="gov-nav-link">
                                    お問い合わせ
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(home_url('/news/')); ?>" class="gov-nav-link">
                                    お知らせ
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                </div>
            </nav>
            
            <!-- Trust Section - 信頼性 -->
            <section class="gov-footer-trust" aria-labelledby="trust-heading">
                <div class="gov-trust-grid">
                    <div class="gov-trust-item">
                        <div class="gov-trust-icon">
                            <i class="fas fa-sync-alt" aria-hidden="true"></i>
                        </div>
                        <div class="gov-trust-content">
                            <h4>毎日更新</h4>
                            <p>公的機関の最新情報を反映</p>
                        </div>
                    </div>
                    
                    <div class="gov-trust-item">
                        <div class="gov-trust-icon">
                            <i class="fas fa-user-check" aria-hidden="true"></i>
                        </div>
                        <div class="gov-trust-content">
                            <h4>専門家監修</h4>
                            <p>補助金申請の専門家が監修</p>
                        </div>
                    </div>
                    
                    <div class="gov-trust-item">
                        <div class="gov-trust-icon">
                            <i class="fas fa-landmark" aria-hidden="true"></i>
                        </div>
                        <div class="gov-trust-content">
                            <h4>公的情報源</h4>
                            <p>国・自治体の公式発表を使用</p>
                        </div>
                    </div>
                    
                    <div class="gov-trust-item">
                        <div class="gov-trust-icon">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                        </div>
                        <div class="gov-trust-content">
                            <h4>セキュリティ</h4>
                            <p>SSL暗号化通信で安全</p>
                        </div>
                    </div>
                </div>
            </section>
            
        </div>
        
        <!-- Bottom Section -->
        <div class="gov-footer-bottom">
            <div class="gov-footer-inner">
                <div class="gov-bottom-wrapper">
                    <div class="gov-bottom-left">
                        <p class="gov-copyright">
                            &copy; <?php echo date('Y'); ?> <strong><?php bloginfo('name'); ?></strong>. All rights reserved.
                        </p>
                        <?php
                        $stats = gi_get_cached_stats();
                        $last_updated = get_option('gi_last_updated', date('Y-m-d'));
                        ?>
                        <div class="gov-update-info">
                            <span class="gov-update-dot" aria-hidden="true"></span>
                            <span>最終更新: <?php echo esc_html(date_i18n('Y年n月j日', strtotime($last_updated))); ?></span>
                            <?php if (!empty($stats['total_grants'])): ?>
                            <span>|</span>
                            <span>掲載数: <?php echo number_format($stats['total_grants']); ?>件</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <nav class="gov-legal-links" aria-label="法的情報">
                        <a href="<?php echo esc_url(home_url('/privacy/')); ?>" class="gov-legal-link">プライバシーポリシー</a>
                        <a href="<?php echo esc_url(home_url('/terms/')); ?>" class="gov-legal-link">利用規約</a>
                        <a href="<?php echo esc_url(home_url('/disclaimer/')); ?>" class="gov-legal-link">免責事項</a>
                    </nav>
                </div>
            </div>
        </div>
    </footer>

    <?php 
    // グローバルスティッキーCTAバナーを表示
    get_template_part('template-parts/global-sticky-cta'); 
    ?>

    <?php wp_footer(); ?>
    
    <?php
    // Organization構造化データ（JSON-LD形式）- SEO強化
    $sns_urls = gi_get_sns_urls();
    $same_as_urls = array_filter(array_values($sns_urls));
    
    $organization_schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => get_bloginfo('name'),
        'url' => home_url('/'),
        'logo' => 'https://joseikin-insight.com/gemini_generated_image_19k6yi19k6yi19k6/',
        'description' => '日本全国の補助金・助成金情報を網羅した検索プラットフォーム。専門家監修のもと、最新情報を毎日更新しています。',
        'foundingDate' => '2024',
        'areaServed' => array(
            '@type' => 'Country',
            'name' => 'Japan'
        ),
        'contactPoint' => array(
            '@type' => 'ContactPoint',
            'contactType' => 'customer service',
            'url' => home_url('/contact/'),
            'availableLanguage' => 'Japanese'
        )
    );
    
    if (!empty($same_as_urls)) {
        $organization_schema['sameAs'] = $same_as_urls;
    }
    ?>
    <script type="application/ld+json">
    <?php echo wp_json_encode($organization_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>
</body>
</html>
