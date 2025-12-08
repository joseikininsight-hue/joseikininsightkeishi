/**
 * Front Page JS - v12.0 (Performance Optimized)
 * フロントページ専用JavaScript
 * カクカク問題修正: scrollHeight/innerHeightをキャッシュ化
 * 
 * @package Grant_Insight_Perfect
 * @version 12.0.0
 */

(function() {
    'use strict';
    
    // ============================================
    // Performance Optimization: Height Caching
    // ============================================
    let cachedWinH = window.innerHeight;
    let cachedDocH = document.documentElement.scrollHeight;
    let resizeTimer;
    
    // Resize時に高さをキャッシュ更新
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
    
    /**
     * Initialize Front Page Features
     */
    function init() {
        setupScrollProgress();
        setupSectionAnimation();
        setupSmoothScroll();
        
        if ('performance' in window) {
            monitorPerf();
        }
        
        setupSEOTracking();
    }
    
    /**
     * Setup Scroll Progress Bar
     */
    function setupScrollProgress() {
        const bar = document.getElementById('scroll-progress');
        if (!bar) return;
        
        let ticking = false;
        
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    // キャッシュした値を使用してパフォーマンス向上
                    const scrollableHeight = cachedDocH - cachedWinH;
                    const pct = scrollableHeight > 0 
                        ? (window.scrollY / scrollableHeight) * 100 
                        : 0;
                    
                    bar.style.width = Math.min(Math.max(pct, 0), 100) + '%';
                    bar.setAttribute('aria-valuenow', Math.round(pct));
                    
                    ticking = false;
                });
                ticking = true;
            }
        }, {passive: true});
    }
    
    /**
     * Setup Section Animation
     * アニメーションは即座に表示（カクカク防止）
     */
    function setupSectionAnimation() {
        document.querySelectorAll('.section-animate').forEach(el => {
            el.classList.add('visible');
        });
    }
    
    /**
     * Setup Smooth Scroll for Anchor Links
     */
    function setupSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                if (href && href !== '#' && href !== '#0') {
                    const target = document.querySelector(href);
                    
                    if (target) {
                        e.preventDefault();
                        const targetTop = target.getBoundingClientRect().top + window.scrollY - 80;
                        
                        window.scrollTo({
                            top: targetTop,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
    }
    
    /**
     * Monitor Performance Metrics
     */
    function monitorPerf() {
        window.addEventListener('load', () => {
            // 高さを最終更新
            cachedDocH = document.documentElement.scrollHeight;
            
            setTimeout(() => {
                const perfData = performance.getEntriesByType('navigation')[0];
                
                if (perfData && typeof gtag !== 'undefined') {
                    const loadTime = Math.round(perfData.loadEventEnd - perfData.loadEventStart);
                    
                    gtag('event', 'page_timing', {
                        'event_category': 'Performance',
                        'event_label': 'Front Page Load Time',
                        'value': loadTime
                    });
                }
            }, 0);
        });
    }
    
    /**
     * Setup SEO Tracking (Scroll Depth)
     */
    function setupSEOTracking() {
        let maxScroll = 0;
        const scrollPoints = [25, 50, 75, 100];
        const trackedPoints = new Set();
        let ticking = false;
        
        window.addEventListener('scroll', () => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    // キャッシュした値を使用
                    const scrollableHeight = cachedDocH - cachedWinH;
                    const scrollPercent = Math.round((window.scrollY / scrollableHeight) * 100);
                    
                    if (scrollPercent > maxScroll) {
                        maxScroll = scrollPercent;
                        
                        scrollPoints.forEach(point => {
                            if (scrollPercent >= point && !trackedPoints.has(point)) {
                                trackedPoints.add(point);
                                
                                if (typeof gtag !== 'undefined') {
                                    gtag('event', 'scroll_depth', {
                                        'event_category': 'Engagement',
                                        'event_label': point + '%',
                                        'value': point
                                    });
                                }
                            }
                        });
                    }
                    
                    ticking = false;
                });
                ticking = true;
            }
        }, {passive: true});
    }
    
})();
