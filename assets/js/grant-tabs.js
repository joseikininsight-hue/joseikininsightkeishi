/**
 * UI Section - JavaScript
 * タブ切り替え、遅延ロード、アクセシビリティ対応
 * @version 30.0.0
 */
(function() {
    'use strict';
    
    // ==========================================================================
    // Tab Switching
    // ==========================================================================
    const tabs = document.querySelectorAll('.ui-tab-btn');
    const panels = document.querySelectorAll('.ui-panel');
    
    if (tabs.length === 0 || panels.length === 0) {
        console.warn('UI Section: Tabs or panels not found');
        return;
    }
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // タブの切り替え
            tabs.forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            
            // パネルの切り替え
            panels.forEach(p => {
                p.classList.remove('active');
                p.hidden = true;
            });
            
            const activePanel = document.getElementById(`panel-${targetTab}`);
            if (activePanel) {
                activePanel.classList.add('active');
                activePanel.hidden = false;
            }
            
            // Analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'tab_switch', {
                    'event_category': 'engagement',
                    'event_label': targetTab,
                    'transport_type': 'beacon'
                });
            }
        });
        
        // Keyboard Navigation (Arrow Keys)
        tab.addEventListener('keydown', function(e) {
            const currentIndex = Array.from(tabs).indexOf(this);
            let newIndex;
            
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                e.preventDefault();
                newIndex = (currentIndex + 1) % tabs.length;
            } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                e.preventDefault();
                newIndex = (currentIndex - 1 + tabs.length) % tabs.length;
            } else if (e.key === 'Home') {
                e.preventDefault();
                newIndex = 0;
            } else if (e.key === 'End') {
                e.preventDefault();
                newIndex = tabs.length - 1;
            }
            
            if (newIndex !== undefined) {
                tabs[newIndex].click();
                tabs[newIndex].focus();
            }
        });
    });
    
    // ==========================================================================
    // Intersection Observer - Lazy Animation
    // ==========================================================================
    if ('IntersectionObserver' in window) {
        const observerOptions = {
            rootMargin: '50px 0px',
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = entry.target;
                    
                    // 初期スタイル設定
                    target.style.opacity = '0';
                    target.style.transform = 'translateY(10px)';
                    
                    // アニメーション実行
                    requestAnimationFrame(() => {
                        target.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                        target.style.opacity = '1';
                        target.style.transform = 'translateY(0)';
                    });
                    
                    observer.unobserve(target);
                }
            });
        }, observerOptions);
        
        // Observe elements
        const elementsToObserve = document.querySelectorAll('.ui-card, .ui-list-item, .ui-ranking-item');
        elementsToObserve.forEach(el => {
            observer.observe(el);
        });
    }
    
    // ==========================================================================
    // Analytics Tracking
    // ==========================================================================
    const trackClick = (category, action, label) => {
        if (typeof gtag !== 'undefined') {
            gtag('event', action, {
                'event_category': category,
                'event_label': label,
                'transport_type': 'beacon'
            });
        }
    };
    
    // Track card clicks
    document.querySelectorAll('.ui-card-link').forEach(link => {
        link.addEventListener('click', function() {
            const titleEl = this.querySelector('.ui-card-title');
            if (titleEl) {
                const title = titleEl.textContent.trim();
                const cardType = this.closest('.ui-card').classList.contains('ui-card-featured') 
                    ? 'featured' 
                    : 'regular';
                trackClick('engagement', `card_click_${cardType}`, title);
            }
        });
    });
    
    // Track list clicks
    document.querySelectorAll('.ui-list-link').forEach(link => {
        link.addEventListener('click', function() {
            const titleEl = this.querySelector('.ui-list-title');
            if (titleEl) {
                const title = titleEl.textContent.trim();
                trackClick('engagement', 'list_click', title);
            }
        });
    });
    
    // Track ranking clicks
    document.querySelectorAll('.ui-ranking-link').forEach(link => {
        link.addEventListener('click', function() {
            const rankItem = this.closest('.ui-ranking-item');
            const rankClass = rankItem ? rankItem.className.match(/rank-(\d+)/) : null;
            const rank = rankClass ? rankClass[1] : 'unknown';
            const titleEl = this.querySelector('.ui-ranking-text');
            const title = titleEl ? titleEl.textContent.trim() : 'unknown';
            trackClick('engagement', 'ranking_click', `Rank ${rank}: ${title}`);
        });
    });
    
    // ==========================================================================
    // Performance Monitoring
    // ==========================================================================
    if ('PerformanceObserver' in window) {
        try {
            // Largest Contentful Paint (LCP)
            const lcpObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lastEntry = entries[entries.length - 1];
                const lcpTime = lastEntry.renderTime || lastEntry.loadTime;
                console.log('LCP:', lcpTime.toFixed(2) + 'ms');
                
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'lcp', {
                        'event_category': 'performance',
                        'value': Math.round(lcpTime),
                        'transport_type': 'beacon'
                    });
                }
            });
            lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
            
            // First Input Delay (FID)
            const fidObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                entries.forEach(entry => {
                    const fidTime = entry.processingStart - entry.startTime;
                    console.log('FID:', fidTime.toFixed(2) + 'ms');
                    
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'fid', {
                            'event_category': 'performance',
                            'value': Math.round(fidTime),
                            'transport_type': 'beacon'
                        });
                    }
                });
            });
            fidObserver.observe({ entryTypes: ['first-input'] });
            
        } catch (e) {
            console.warn('Performance observation not supported:', e);
        }
    }
    
    // ==========================================================================
    // Error Handling
    // ==========================================================================
    window.addEventListener('error', function(event) {
        console.error('UI Section Error:', event.error);
    });
    
    // ==========================================================================
    // Initialize
    // ==========================================================================
    console.log('✅ UI Section v30.0 (Columns First - Complete SEO & UX Optimization) Initialized');
    console.log('📊 Tab switching enabled');
    console.log('🎨 Lazy animations active');
    console.log('📈 Analytics tracking configured');
    console.log('♿ Accessibility features enabled');
    console.log('⚡ Performance monitoring active');
    
})();
