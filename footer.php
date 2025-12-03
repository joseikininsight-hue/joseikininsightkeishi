<?php
/**
 * JOSEIKIN INSIGHT - Ultimate Footer
 * E-E-A-T・SEO・UI・UX 最適化版
 * 
 * @package Joseikin_Insight_Footer
 * @version 7.0.0
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
           JOSEIKIN INSIGHT - ULTIMATE FOOTER
           E-E-A-T・SEO・UI・UX 最適化版
           =============================================== */
        
        :root {
            --footer-bg: #000000;
            --footer-bg-elevated: #0a0a0a;
            --footer-text: #ffffff;
            --footer-text-secondary: #a3a3a3;
            --footer-text-tertiary: #737373;
            --footer-border: rgba(255, 255, 255, 0.08);
            --footer-border-strong: rgba(255, 255, 255, 0.15);
            --footer-accent: #ffffff;
            --footer-accent-hover: rgba(255, 255, 255, 0.9);
            --footer-success: #22c55e;
            --footer-font: 'Noto Sans JP', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --footer-transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --footer-max-width: 1200px;
        }
        
        /* ===============================================
           BASE FOOTER STYLES
           =============================================== */
        .ji-footer {
            background: var(--footer-bg);
            color: var(--footer-text);
            font-family: var(--footer-font);
            position: relative;
        }
        
        .ji-footer-inner {
            max-width: var(--footer-max-width);
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        @media (min-width: 768px) {
            .ji-footer-inner {
                padding: 0 2rem;
            }
        }
        
        /* Logo Styles */
        .ji-logo-image {
            height: 32px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
            display: block;
        }

        @media (max-width: 767px) {
            .ji-logo-image {
                height: 28px;
                max-width: 160px;
            }
        }

        /* ===============================================
           TRUST SECTION - E-E-A-T強化
           =============================================== */
        .ji-footer-trust {
            padding: 3rem 0;
            border-bottom: 1px solid var(--footer-border);
        }
        
        .ji-trust-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .ji-trust-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--footer-success);
            color: var(--footer-bg);
            padding: 0.375rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            margin-bottom: 1rem;
        }
        
        .ji-trust-badge i {
            font-size: 0.875rem;
        }
        
        .ji-trust-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--footer-text);
            margin-bottom: 0.5rem;
            letter-spacing: -0.01em;
        }
        
        .ji-trust-subtitle {
            font-size: 0.875rem;
            color: var(--footer-text-secondary);
            line-height: 1.6;
        }
        
        /* Trust Points Grid */
        .ji-trust-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        @media (min-width: 640px) {
            .ji-trust-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1024px) {
            .ji-trust-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        .ji-trust-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem;
            background: var(--footer-bg-elevated);
            border: 1px solid var(--footer-border);
            border-radius: 12px;
            transition: all var(--footer-transition);
        }
        
        .ji-trust-item:hover {
            border-color: var(--footer-border-strong);
            transform: translateY(-2px);
        }
        
        .ji-trust-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            flex-shrink: 0;
        }
        
        .ji-trust-icon i {
            font-size: 1.125rem;
            color: var(--footer-text);
        }
        
        .ji-trust-content h4 {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--footer-text);
            margin-bottom: 0.25rem;
            line-height: 1.4;
        }
        
        .ji-trust-content p {
            font-size: 0.8125rem;
            color: var(--footer-text-secondary);
            line-height: 1.5;
            margin: 0;
        }
        
        /* ===============================================
           CTA SECTION
           =============================================== */
        .ji-footer-cta {
            padding: 3rem 0;
            border-bottom: 1px solid var(--footer-border);
        }
        
        .ji-cta-wrapper {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            align-items: center;
            text-align: center;
        }
        
        @media (min-width: 768px) {
            .ji-cta-wrapper {
                flex-direction: row;
                justify-content: space-between;
                text-align: left;
            }
        }
        
        .ji-cta-content {
            max-width: 500px;
        }
        
        .ji-cta-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--footer-text);
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
            line-height: 1.3;
        }
        
        @media (min-width: 768px) {
            .ji-cta-title {
                font-size: 1.75rem;
            }
        }
        
        .ji-cta-description {
            font-size: 0.9375rem;
            color: var(--footer-text-secondary);
            line-height: 1.7;
            margin: 0;
        }
        
        .ji-cta-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: 100%;
        }
        
        @media (min-width: 480px) {
            .ji-cta-buttons {
                flex-direction: row;
                width: auto;
            }
        }
        
        .ji-cta-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem 1.75rem;
            border-radius: 10px;
            font-size: 0.9375rem;
            font-weight: 700;
            text-decoration: none;
            transition: all var(--footer-transition);
            white-space: nowrap;
            letter-spacing: 0.01em;
        }
        
        .ji-cta-btn-primary {
            background: var(--footer-text);
            color: var(--footer-bg);
            border: 2px solid var(--footer-text);
        }
        
        .ji-cta-btn-primary:hover {
            background: transparent;
            color: var(--footer-text);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 255, 255, 0.15);
        }
        
        .ji-cta-btn-secondary {
            background: transparent;
            color: var(--footer-text);
            border: 2px solid var(--footer-border-strong);
        }
        
        .ji-cta-btn-secondary:hover {
            border-color: var(--footer-text);
            transform: translateY(-2px);
        }
        
        /* ===============================================
           MAIN NAVIGATION SECTION
           =============================================== */
        .ji-footer-nav {
            padding: 3.5rem 0;
            border-bottom: 1px solid var(--footer-border);
        }
        
        .ji-nav-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2.5rem;
        }
        
        @media (min-width: 640px) {
            .ji-nav-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (min-width: 1024px) {
            .ji-nav-grid {
                grid-template-columns: 1.5fr 1fr 1fr 1fr 1fr;
                gap: 2rem;
            }
        }
        
        /* Brand Column */
        .ji-nav-brand {
            grid-column: 1 / -1;
        }
        
        @media (min-width: 1024px) {
            .ji-nav-brand {
                grid-column: auto;
            }
        }
        
        .ji-brand-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            text-decoration: none;
        }
        
        .ji-brand-icon {
            width: 44px;
            height: 44px;
            background: var(--footer-text);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.25rem;
            color: var(--footer-bg);
            letter-spacing: -0.02em;
            flex-shrink: 0;
        }
        
        .ji-brand-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .ji-brand-name {
            font-size: 1.125rem;
            font-weight: 800;
            color: var(--footer-text);
            letter-spacing: 0.01em;
            line-height: 1.2;
        }
        
        .ji-brand-tagline {
            font-size: 0.6875rem;
            color: var(--footer-text-tertiary);
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        
        .ji-brand-description {
            font-size: 0.875rem;
            color: var(--footer-text-secondary);
            line-height: 1.7;
            margin-bottom: 1.5rem;
            max-width: 280px;
        }
        
        /* Social Links */
        .ji-social-row {
            display: flex;
            gap: 0.625rem;
            flex-wrap: wrap;
        }
        
        .ji-social-link {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--footer-text);
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--footer-border);
            border-radius: 10px;
            text-decoration: none;
            font-size: 1rem;
            transition: all var(--footer-transition);
        }
        
        .ji-social-link:hover {
            background: var(--footer-text);
            color: var(--footer-bg);
            border-color: var(--footer-text);
            transform: translateY(-2px);
        }
        
        /* Navigation Columns */
        .ji-nav-column {
            display: flex;
            flex-direction: column;
        }
        
        .ji-nav-title {
            font-size: 0.6875rem;
            font-weight: 700;
            color: var(--footer-text-tertiary);
            margin-bottom: 1rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        
        .ji-nav-list {
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
        }
        
        .ji-nav-link {
            color: var(--footer-text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all var(--footer-transition);
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0;
        }
        
        .ji-nav-link:hover {
            color: var(--footer-text);
            transform: translateX(4px);
        }
        
        .ji-nav-link i {
            font-size: 0.625rem;
            opacity: 0;
            transition: opacity var(--footer-transition);
        }
        
        .ji-nav-link:hover i {
            opacity: 1;
        }
        
        /* Live Badge */
        .ji-nav-link-live {
            position: relative;
        }
        
        .ji-live-dot {
            width: 6px;
            height: 6px;
            background: var(--footer-success);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* ===============================================
           BOTTOM SECTION
           =============================================== */
        .ji-footer-bottom {
            padding: 2rem 0;
        }
        
        .ji-bottom-wrapper {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            align-items: center;
        }
        
        @media (min-width: 768px) {
            .ji-bottom-wrapper {
                flex-direction: row;
                justify-content: space-between;
            }
        }
        
        /* Left Side - Copyright & Info */
        .ji-bottom-left {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            align-items: center;
            text-align: center;
        }
        
        @media (min-width: 768px) {
            .ji-bottom-left {
                align-items: flex-start;
                text-align: left;
            }
        }
        
        .ji-copyright {
            font-size: 0.8125rem;
            color: var(--footer-text-tertiary);
            font-weight: 500;
        }
        
        .ji-copyright strong {
            color: var(--footer-text-secondary);
            font-weight: 700;
        }
        
        .ji-update-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: var(--footer-text-tertiary);
        }
        
        .ji-update-dot {
            width: 6px;
            height: 6px;
            background: var(--footer-success);
            border-radius: 50%;
        }
        
        /* Right Side - Legal Links */
        .ji-legal-links {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        @media (min-width: 768px) {
            .ji-legal-links {
                justify-content: flex-end;
            }
        }
        
        .ji-legal-link {
            color: var(--footer-text-tertiary);
            text-decoration: none;
            font-size: 0.8125rem;
            font-weight: 600;
            transition: color var(--footer-transition);
        }
        
        .ji-legal-link:hover {
            color: var(--footer-text);
        }
        
        /* ===============================================
           SCHEMA MARKUP (Hidden)
           =============================================== */
        .ji-schema-data {
            display: none !important;
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
        
        .ji-footer a:focus-visible,
        .ji-footer button:focus-visible {
            outline: 2px solid var(--footer-text);
            outline-offset: 2px;
        }
        
        /* Skip Link for Accessibility */
        .ji-skip-link {
            position: absolute;
            left: -9999px;
            top: auto;
            width: 1px;
            height: 1px;
            overflow: hidden;
        }
        
        .ji-skip-link:focus {
            position: fixed;
            top: 0;
            left: 0;
            width: auto;
            height: auto;
            padding: 1rem 2rem;
            background: var(--footer-text);
            color: var(--footer-bg);
            font-weight: 700;
            z-index: 9999;
        }
    </style>

    <!-- Ultimate Footer -->
    <footer class="ji-footer" role="contentinfo" itemscope itemtype="https://schema.org/WPFooter">
        
        <!-- Schema.org Organization Data -->
        <div class="ji-schema-data" itemscope itemtype="https://schema.org/Organization">
            <meta itemprop="name" content="<?php bloginfo('name'); ?>">
            <meta itemprop="url" content="<?php echo esc_url(home_url('/')); ?>">
            <meta itemprop="logo" content="https://joseikin-insight.com/wp-content/uploads/2025/05/cropped-logo3.webp">
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
        
        <div class="ji-footer-inner">
            
            <!-- Trust Section - E-E-A-T強化 -->
            <section class="ji-footer-trust" aria-labelledby="trust-heading">
                <div class="ji-trust-header">
                    <div class="ji-trust-badge">
                        <i class="fas fa-shield-alt" aria-hidden="true"></i>
                        <span>信頼性への取り組み</span>
                    </div>
                    <h2 id="trust-heading" class="ji-trust-title">正確で最新の情報をお届けします</h2>
                    <p class="ji-trust-subtitle">公的機関の公開情報を基に、専門スタッフが毎日更新・監修しています</p>
                </div>
                
                <div class="ji-trust-grid">
                    <div class="ji-trust-item">
                        <div class="ji-trust-icon">
                            <i class="fas fa-sync-alt" aria-hidden="true"></i>
                        </div>
                        <div class="ji-trust-content">
                            <h4>毎日更新</h4>
                            <p>公的機関の最新情報を日次で反映</p>
                        </div>
                    </div>
                    
                    <div class="ji-trust-item">
                        <div class="ji-trust-icon">
                            <i class="fas fa-user-check" aria-hidden="true"></i>
                        </div>
                        <div class="ji-trust-content">
                            <h4>専門スタッフ監修</h4>
                            <p>補助金申請の専門家がコンテンツを監修</p>
                        </div>
                    </div>
                    
                    <div class="ji-trust-item">
                        <div class="ji-trust-icon">
                            <i class="fas fa-landmark" aria-hidden="true"></i>
                        </div>
                        <div class="ji-trust-content">
                            <h4>公的情報源</h4>
                            <p>国・自治体の公式発表を情報源として使用</p>
                        </div>
                    </div>
                    
                    <div class="ji-trust-item">
                        <div class="ji-trust-icon">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                        </div>
                        <div class="ji-trust-content">
                            <h4>セキュリティ対策</h4>
                            <p>SSL暗号化通信で安全にご利用いただけます</p>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- CTA Section -->
            <section class="ji-footer-cta" aria-labelledby="cta-heading">
                <div class="ji-cta-wrapper">
                    <div class="ji-cta-content">
                        <h2 id="cta-heading" class="ji-cta-title">あなたに最適な補助金・助成金を見つけませんか？</h2>
                        <p class="ji-cta-description">簡単な質問に答えるだけで、申請可能な補助金・助成金をAIが診断します。無料でご利用いただけます。</p>
                    </div>
                    
                    <div class="ji-cta-buttons">
                        <a href="<?php echo esc_url(home_url('/subsidy-diagnosis/')); ?>" class="ji-cta-btn ji-cta-btn-primary">
                            <i class="fas fa-stethoscope" aria-hidden="true"></i>
                            <span>無料診断を受ける</span>
                        </a>
                        <a href="<?php echo esc_url(get_post_type_archive_link('grant')); ?>" class="ji-cta-btn ji-cta-btn-secondary">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <span>補助金を探す</span>
                        </a>
                    </div>
                </div>
            </section>
            
            <!-- Main Navigation -->
            <nav class="ji-footer-nav" aria-label="フッターナビゲーション">
                <div class="ji-nav-grid">
                    
                    <!-- Brand Column -->
                    <div class="ji-nav-brand">
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="ji-brand-logo" aria-label="<?php bloginfo('name'); ?> ホームへ">
                            <img src="https://joseikin-insight.com/wp-content/uploads/2025/05/cropped-logo3.webp" alt="Joseikin Insight" width="180" height="32" class="ji-logo-image">
                        </a>
                        
                        <p class="ji-brand-description">
                            日本全国の補助金・助成金情報を一元化。あなたのビジネスや生活に最適な支援制度を見つけるお手伝いをします。
                        </p>
                        
                        <div class="ji-social-row" aria-label="ソーシャルメディア">
                            <?php
                            $sns_urls = gi_get_sns_urls();
                            $social_config = [
                                'twitter' => ['icon' => 'fab fa-x-twitter', 'label' => 'X (Twitter)'],
                                'facebook' => ['icon' => 'fab fa-facebook-f', 'label' => 'Facebook'],
                                'instagram' => ['icon' => 'fab fa-instagram', 'label' => 'Instagram'],
                                'youtube' => ['icon' => 'fab fa-youtube', 'label' => 'YouTube'],
                                'note' => ['icon' => 'fas fa-pen-nib', 'label' => 'note'],
                                'linkedin' => ['icon' => 'fab fa-linkedin-in', 'label' => 'LinkedIn']
                            ];
                            
                            foreach ($sns_urls as $platform => $url) {
                                if (!empty($url) && isset($social_config[$platform])) {
                                    $config = $social_config[$platform];
                                    echo '<a href="' . esc_url($url) . '" class="ji-social-link" aria-label="' . esc_attr($config['label']) . 'でフォロー" target="_blank" rel="noopener noreferrer">';
                                    echo '<i class="' . esc_attr($config['icon']) . '" aria-hidden="true"></i>';
                                    echo '</a>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Services Column -->
                    <div class="ji-nav-column">
                        <h3 class="ji-nav-title">サービス</h3>
                        <ul class="ji-nav-list">
                            <li>
                                <a href="<?php echo esc_url(get_post_type_archive_link('grant')); ?>" class="ji-nav-link">
                                    補助金・助成金一覧
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(add_query_arg('application_status', 'open', get_post_type_archive_link('grant'))); ?>" class="ji-nav-link ji-nav-link-live">
                                    <span class="ji-live-dot" aria-hidden="true"></span>
                                    募集中の補助金
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(home_url('/subsidy-diagnosis/')); ?>" class="ji-nav-link">
                                    補助金診断
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(home_url('/calculator/')); ?>" class="ji-nav-link">
                                    計算ツール
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Learn Column -->
                    <div class="ji-nav-column">
                        <h3 class="ji-nav-title">学ぶ</h3>
                        <ul class="ji-nav-list">
                            <li>
                                <a href="<?php echo esc_url(home_url('/how-to-use/')); ?>" class="ji-nav-link">
                                    使い方ガイド
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(home_url('/knowledge/')); ?>" class="ji-nav-link">
                                    基礎知識
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(home_url('/faq/')); ?>" class="ji-nav-link">
                                    よくある質問
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(home_url('/glossary/')); ?>" class="ji-nav-link">
                                    用語集
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- News Column -->
                    <div class="ji-nav-column">
                        <h3 class="ji-nav-title">ニュース</h3>
                        <ul class="ji-nav-list">
                            <li>
                                <a href="<?php echo esc_url(home_url('/column/')); ?>" class="ji-nav-link">
                                    コラム
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(home_url('/news/')); ?>" class="ji-nav-link">
                                    お知らせ
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Support Column -->
                    <div class="ji-nav-column">
                        <h3 class="ji-nav-title">サポート</h3>
                        <ul class="ji-nav-list">
                            <li>
                                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="ji-nav-link">
                                    お問い合わせ
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(home_url('/support/')); ?>" class="ji-nav-link">
                                    ヘルプセンター
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(home_url('/about/')); ?>" class="ji-nav-link">
                                    運営者情報
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(home_url('/supervisors/')); ?>" class="ji-nav-link">
                                    監修者一覧
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(home_url('/editorial-policy/')); ?>" class="ji-nav-link">
                                    編集ポリシー
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo esc_url(home_url('/sitemap/')); ?>" class="ji-nav-link">
                                    サイトマップ
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                </div>
            </nav>
            
            <!-- Bottom Section -->
            <div class="ji-footer-bottom">
                <div class="ji-bottom-wrapper">
                    <div class="ji-bottom-left">
                        <p class="ji-copyright">
                            &copy; <?php echo date('Y'); ?> <strong><?php bloginfo('name'); ?></strong>. All rights reserved.
                        </p>
                        <?php
                        $stats = gi_get_cached_stats();
                        $last_updated = get_option('gi_last_updated', date('Y-m-d'));
                        ?>
                        <div class="ji-update-info">
                            <span class="ji-update-dot" aria-hidden="true"></span>
                            <span>最終更新: <?php echo esc_html(date_i18n('Y年n月j日', strtotime($last_updated))); ?></span>
                            <?php if (!empty($stats['total_grants'])): ?>
                            <span>|</span>
                            <span>掲載数: <?php echo number_format($stats['total_grants']); ?>件</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <nav class="ji-legal-links" aria-label="法的情報">
                        <a href="<?php echo esc_url(home_url('/privacy/')); ?>" class="ji-legal-link">プライバシーポリシー</a>
                        <a href="<?php echo esc_url(home_url('/terms/')); ?>" class="ji-legal-link">利用規約</a>
                        <a href="<?php echo esc_url(home_url('/disclaimer/')); ?>" class="ji-legal-link">免責事項</a>
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
        'logo' => 'https://joseikin-insight.com/wp-content/uploads/2025/05/cropped-logo3.webp',
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
