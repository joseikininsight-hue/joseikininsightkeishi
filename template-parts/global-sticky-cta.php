<?php
/**
 * Template Part: Global Sticky CTA - Government Official Style
 * グローバルスティッキーCTA - 官公庁風デザイン
 * 
 * @package Grant_Insight_Perfect
 * @version 12.0.0
 * 
 * === 変更点 ===
 * - 官公庁風カラースキーム（濃紺×金）
 * - 「助成金を探す」→「補助金・助成金を探す」に変更
 */

if (!defined('ABSPATH')) exit;

// ページ設定で非表示にする場合の判定
if (get_query_var('hide_sticky_cta')) return;
?>

<div id="ui-sticky-cta" class="ui-sticky-cta" aria-hidden="false">
    <div class="ui-sticky-inner">
        
        <a href="<?php echo esc_url(home_url('/subsidy-diagnosis/')); ?>" 
           class="ui-sticky-btn ui-btn-diagnosis"
           aria-label="無料で診断する">
            <div class="ui-btn-icon-wrap">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <div class="ui-btn-text">
                <span class="en">DIAGNOSIS</span>
                <span class="ja">無料診断</span>
            </div>
        </a>

        <a href="<?php echo esc_url(home_url('/grants/')); ?>" 
           class="ui-sticky-btn ui-btn-search"
           aria-label="補助金・助成金を探す">
            <div class="ui-btn-text">
                <span class="en">SEARCH GRANTS</span>
                <span class="ja">補助金・助成金を探す</span>
            </div>
            <div class="ui-btn-icon-wrap">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </div>
            
            <div class="ui-btn-effect"></div>
        </a>

    </div>
</div>

<style>
/* ============================================
   Global Sticky CTA Styles - Government Official Style v12.0
   官公庁風デザイン - 濃紺×金カラースキーム
   ============================================ */

:root {
    --cta-z-index: 9999;
    --cta-height: 70px;
    /* 官公庁カラーパレット */
    --cta-gov-navy: #0D2A52;
    --cta-gov-navy-light: #1A3D6E;
    --cta-gov-gold: #C5A059;
    --cta-gov-gold-light: #D4B77A;
    --cta-bg-light: #ffffff;
    --cta-text-main: #0D2A52;
    --cta-text-inverse: #ffffff;
    --cta-border: #E2E8F0;
    --cta-font-en: 'Inter', -apple-system, sans-serif;
    --cta-font-ja: 'Noto Sans JP', sans-serif;
    --cta-trans: cubic-bezier(0.2, 0.8, 0.2, 1);
}

@media (max-width: 767px) {
    :root {
        --cta-height: 60px;
    }
}

/* Wrapper */
.ui-sticky-cta {
    position: fixed !important;
    bottom: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: var(--cta-z-index) !important;
    background: #FFFFFF !important;
    background-color: #FFFFFF !important;
    border-top: 3px solid #C5A059 !important;
    box-shadow: 0 -8px 32px rgba(13, 42, 82, 0.15) !important;
    padding-bottom: env(safe-area-inset-bottom);
    transform: translateY(0);
    transition: transform 0.4s var(--cta-trans);
    will-change: transform;
}

/* Hidden State */
.ui-sticky-cta.is-hidden {
    transform: translateY(100%);
}

/* Inner Layout */
.ui-sticky-inner {
    display: flex;
    height: var(--cta-height);
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

/* Buttons Common */
.ui-sticky-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    border: none;
    cursor: pointer;
    padding: 0 16px;
    gap: 12px;
    position: relative;
    overflow: hidden;
    transition: all 0.2s ease;
}

/* Text Styling */
.ui-btn-text {
    display: flex;
    flex-direction: column;
    justify-content: center;
    line-height: 1.2;
}

.ui-btn-text .en {
    font-family: var(--cta-font-en);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.ui-btn-text .ja {
    font-family: var(--cta-font-ja);
    font-size: 14px;
    font-weight: 700;
}

/* --- Diagnosis Button (Secondary) --- */
.ui-btn-diagnosis {
    flex: 0 0 38%;
    background: #FFFFFF !important;
    background-color: #FFFFFF !important;
    color: #0D2A52 !important;
    border-right: 1px solid #E2E8F0 !important;
}

.ui-btn-diagnosis:hover {
    background: #F8FAFC;
}

.ui-btn-diagnosis:active {
    background: #F1F5F9;
}

.ui-btn-diagnosis .ui-btn-text .en {
    color: var(--cta-gov-gold);
    opacity: 1;
}

.ui-btn-diagnosis .ui-btn-text .ja {
    font-size: 13px;
    color: var(--cta-gov-navy);
}

.ui-btn-diagnosis .ui-btn-icon-wrap {
    color: var(--cta-gov-gold);
}

/* --- Search Button (Primary / Navy / Focus) --- */
.ui-btn-search {
    flex: 1;
    background: linear-gradient(135deg, #0D2A52 0%, #1A3D6E 100%) !important;
    color: #FFFFFF !important;
}

.ui-btn-search:hover {
    background: linear-gradient(135deg, var(--cta-gov-navy-light) 0%, var(--cta-gov-navy) 100%);
}

.ui-btn-search:active {
    opacity: 0.95;
}

.ui-btn-search .ui-btn-text .en {
    color: var(--cta-gov-gold);
    opacity: 1;
}

.ui-btn-search .ui-btn-text .ja {
    font-size: 15px;
    color: var(--cta-text-inverse);
}

/* Icon Wrapper */
.ui-btn-icon-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
}

.ui-btn-search .ui-btn-icon-wrap {
    background: rgba(255, 255, 255, 0.15);
    padding: 8px;
    border-radius: 8px;
}

/* Shine Animation */
.ui-btn-effect {
    position: absolute;
    top: 0;
    left: -100%;
    width: 50%;
    height: 100%;
    background: linear-gradient(to right, rgba(197, 160, 89, 0) 0%, rgba(197, 160, 89, 0.3) 50%, rgba(197, 160, 89, 0) 100%);
    transform: skewX(-25deg);
    animation: cta-shine 5s infinite;
    pointer-events: none;
}

@keyframes cta-shine {
    0%, 75% { left: -100%; }
    100% { left: 200%; }
}

/* Responsive Adjustments */
@media (max-width: 767px) {
    .ui-sticky-btn {
        padding: 0 10px;
        gap: 8px;
    }
    
    .ui-btn-text .en {
        font-size: 8px;
        letter-spacing: 0.05em;
    }
    
    .ui-btn-diagnosis .ui-btn-text .ja {
        font-size: 11px;
    }
    
    .ui-btn-search .ui-btn-text .ja {
        font-size: 13px;
    }
    
    .ui-btn-icon-wrap svg {
        width: 16px;
        height: 16px;
    }
    
    .ui-btn-search .ui-btn-icon-wrap {
        padding: 6px;
        border-radius: 6px;
    }
}

/* Body padding handling: コンテンツが隠れないようにするためのパディング */
/* 注意: ページ固有のCSSで上書きされる可能性があるため、!importantは避ける */
/* JSで動的に調整することを推奨 (下記スクリプトで対応) */
@supports (padding-bottom: env(safe-area-inset-bottom)) {
    body:not(.no-sticky-cta-padding) {
        padding-bottom: calc(var(--cta-height) + env(safe-area-inset-bottom));
    }
}

/* Single Grant ページは独自のモバイルバナー設定があるため除外 */
body.single-grant {
    /* single-grant.php で --gi-mobile-banner: 60px が設定されているため、
       ここでは追加パディングを適用しない */
}
</style>

<script>
(function() {
    'use strict';

    const stickyBar = document.getElementById('ui-sticky-cta');
    if (!stickyBar) return;

    let lastScrollY = window.scrollY;
    let ticking = false;
    const threshold = 50;
    
    // ★パフォーマンス改善: 高さをキャッシュ（リサイズ時のみ再計算）
    let cachedWindowHeight = window.innerHeight;
    let cachedDocHeight = document.documentElement.scrollHeight;
    
    // リサイズ時のみ高さを再計算
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            cachedWindowHeight = window.innerHeight;
            cachedDocHeight = document.documentElement.scrollHeight;
        }, 150);
    }, { passive: true });

    const updateUI = () => {
        const currentScrollY = window.scrollY;

        // キャッシュした値を使用（Reflow防止）
        if (cachedWindowHeight + currentScrollY >= cachedDocHeight - 50) {
            stickyBar.classList.remove('is-hidden');
            ticking = false;
            return;
        }

        if (currentScrollY > lastScrollY && currentScrollY > threshold) {
            stickyBar.classList.add('is-hidden');
        } else {
            stickyBar.classList.remove('is-hidden');
        }

        lastScrollY = currentScrollY;
        ticking = false;
    };

    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(updateUI);
            ticking = true;
        }
    }, { passive: true });

    setTimeout(() => {
        stickyBar.classList.remove('is-hidden');
    }, 800);

    const btns = stickyBar.querySelectorAll('.ui-sticky-btn');
    btns.forEach(btn => {
        btn.addEventListener('click', function() {
            const label = this.querySelector('.en').innerText;
            if (typeof gtag !== 'undefined') {
                gtag('event', 'click', {
                    'event_category': 'Sticky CTA',
                    'event_label': label
                });
            }
        });
    });
})();
</script>