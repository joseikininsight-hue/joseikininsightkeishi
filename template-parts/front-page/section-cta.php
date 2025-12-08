<?php
/**
 * Template Part: Front Page - Final CTA Section
 * フロントページ最終CTA（Call To Action）セクション
 *
 * @package Grant_Insight_Perfect
 * @version 11.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="cta-diagnosis-section">
    <div class="cta-diagnosis-wrapper">
        <div class="cta-diagnosis-content">
            
            <!-- Icon -->
            <div class="cta-icon">
                <i class="fas fa-clipboard-check" aria-hidden="true"></i>
            </div>
            
            <!-- Title -->
            <h2 id="final-cta-title" class="cta-title" itemprop="name">
                あなたに最適な補助金を無料診断
            </h2>
            
            <!-- Description -->
            <p class="cta-description" itemprop="description">
                簡単な質問に答えるだけで、あなたの事業に最適な補助金・助成金を診断します。<br class="pc-only">
                診断は完全無料、所要時間はわずか3分です。
            </p>
            
            <!-- Button Group -->
            <div class="cta-button-group">
                <!-- Secondary Button: Search -->
                <a href="<?php echo esc_url(home_url('/grants/')); ?>" 
                   class="cta-button cta-button-secondary"
                   title="補助金一覧から探す">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <span>補助金を探す</span>
                </a>

                <!-- Primary Button: Free Diagnosis -->
                <a href="https://joseikin-insight.com/subsidy-diagnosis/" 
                   class="cta-button cta-button-primary"
                   itemprop="url"
                   title="無料診断を今すぐ始める">
                    <i class="fas fa-play-circle" aria-hidden="true"></i>
                    <span>今すぐ無料診断を始める</span>
                </a>
            </div>

            <!-- Note -->
            <p class="cta-note">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <span>会員登録不要・メールアドレス不要</span>
            </p>
            
        </div>
    </div>
</div>
