document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    var CONFIG = {
        postId: <?php echo $post_id; ?>,
        ajaxUrl: '<?php echo admin_url("admin-ajax.php"); ?>',
        nonce: '<?php echo wp_create_nonce("gic_ai_nonce"); ?>',
        url: '<?php echo esc_js($canonical_url); ?>',
        title: <?php echo json_encode($post_title, JSON_UNESCAPED_UNICODE); ?>
    };
    
    // プログレスバー
    var progress = document.getElementById('progressBar');
    function updateProgress() {
        var h = document.documentElement.scrollHeight - window.innerHeight;
        var p = h > 0 ? Math.min(100, (window.pageYOffset / h) * 100) : 0;
        if (progress) progress.style.width = p + '%';
    }
    window.addEventListener('scroll', updateProgress, { passive: true });
    
    // 目次生成
    function generateTOC() {
        var content = document.querySelector('.gic-content');
        var tocNav = document.getElementById('tocNav');
        var mobileTocNav = document.getElementById('mobileTocNav');
        
        if (!content) return;
        
        var headings = content.querySelectorAll('h2, h3');
        if (headings.length === 0) {
            if (tocNav) tocNav.innerHTML = '<p style="color: #888; font-size: 14px;">目次がありません</p>';
            if (mobileTocNav) mobileTocNav.innerHTML = '<p style="color: #888; font-size: 14px;">目次がありません</p>';
            return;
        }
        
        var tocHTML = '<ul>';
        headings.forEach(function(heading, index) {
            var id = 'heading-' + index;
            heading.id = id;
            var level = heading.tagName === 'H2' ? 'toc-h2' : 'toc-h3';
            tocHTML += '<li><a href="#' + id + '" class="' + level + '">' + heading.textContent + '</a></li>';
        });
        tocHTML += '</ul>';
        
        if (tocNav) tocNav.innerHTML = tocHTML;
        if (mobileTocNav) {
            mobileTocNav.innerHTML = tocHTML;
            mobileTocNav.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', closePanel);
            });
        }
    }
    generateTOC();
    
    // AI送信
    function sendAiMessage(input, container, btn) {
        var question = input.value.trim();
        if (!question) return;
        
        addMessage(container, question, 'user');
        input.value = '';
        btn.disabled = true;
        
        var loadingMsg = document.createElement('div');
        loadingMsg.className = 'gic-ai-msg';
        loadingMsg.innerHTML = '<div class="gic-ai-avatar">AI</div><div class="gic-ai-bubble">考え中...</div>';
        container.appendChild(loadingMsg);
        container.scrollTop = container.scrollHeight;
        
        var formData = new FormData();
        formData.append('action', 'gic_ai_chat');
        formData.append('nonce', CONFIG.nonce);
        formData.append('post_id', CONFIG.postId);
        formData.append('question', question);
        
        fetch(CONFIG.ajaxUrl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                loadingMsg.remove();
                if (data.success && data.data && data.data.answer) {
                    addMessage(container, data.data.answer, 'ai');
                } else {
                    addMessage(container, generateFallback(question), 'ai');
                }
            })
            .catch(function() {
                loadingMsg.remove();
                addMessage(container, generateFallback(question), 'ai');
            })
            .finally(function() { btn.disabled = false; });
    }
    
    function addMessage(container, text, type) {
        var msg = document.createElement('div');
        msg.className = 'gic-ai-msg' + (type === 'user' ? ' user' : '');
        msg.innerHTML = '<div class="gic-ai-avatar">' + (type === 'user' ? 'You' : 'AI') + '</div><div class="gic-ai-bubble">' + escapeHtml(text).replace(/\n/g, '<br>') + '</div>';
        container.appendChild(msg);
        container.scrollTop = container.scrollHeight;
    }
    
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function generateFallback(question) {
        var q = question.toLowerCase();
        if (q.indexOf('ポイント') !== -1) return 'この記事のポイントについては、「この記事のポイント」セクションをご確認ください。';
        if (q.indexOf('補助金') !== -1) return '関連する補助金については、「関連する補助金」セクションをご確認ください。';
        if (q.indexOf('申請') !== -1) return '補助金の申請方法については、各補助金の詳細ページでご確認いただけます。';
        return 'ご質問ありがとうございます。記事の内容をご確認いただくか、より具体的な質問をお聞かせください。';
    }
    
    // デスクトップAI
    var aiInput = document.getElementById('aiInput');
    var aiSend = document.getElementById('aiSend');
    var aiMessages = document.getElementById('aiMessages');
    
    if (aiSend && aiInput && aiMessages) {
        aiSend.addEventListener('click', function() { sendAiMessage(aiInput, aiMessages, aiSend); });
        aiInput.addEventListener('keydown', function(e) { 
            if (e.key === 'Enter' && !e.shiftKey) { 
                e.preventDefault(); 
                sendAiMessage(aiInput, aiMessages, aiSend); 
            } 
        });
    }
    
    // モバイルAI
    var mobileAiInput = document.getElementById('mobileAiInput');
    var mobileAiSend = document.getElementById('mobileAiSend');
    var mobileAiMessages = document.getElementById('mobileAiMessages');
    
    if (mobileAiSend && mobileAiInput && mobileAiMessages) {
        mobileAiSend.addEventListener('click', function() { sendAiMessage(mobileAiInput, mobileAiMessages, mobileAiSend); });
        mobileAiInput.addEventListener('keydown', function(e) { 
            if (e.key === 'Enter' && !e.shiftKey) { 
                e.preventDefault(); 
                sendAiMessage(mobileAiInput, mobileAiMessages, mobileAiSend); 
            } 
        });
    }
    
    // AIチップ
    document.querySelectorAll('.gic-ai-chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            var q = this.dataset.q;
            if (!q) return;
            
            var isDesktop = this.closest('.gic-sidebar-section');
            var input = isDesktop ? aiInput : mobileAiInput;
            var container = isDesktop ? aiMessages : mobileAiMessages;
            var btn = isDesktop ? aiSend : mobileAiSend;
            
            if (input) {
                input.value = q;
                sendAiMessage(input, container, btn);
            }
        });
    });
    
     // モバイルパネル
    var mobileAiBtn = document.getElementById('mobileAiBtn');
    var mobileOverlay = document.getElementById('mobileOverlay');
    var mobilePanel = document.getElementById('mobilePanel');
    var panelClose = document.getElementById('panelClose');
    var panelTabs = document.querySelectorAll('.gic-panel-tab');
    var panelContents = document.querySelectorAll('.gic-panel-content-tab');
    
    function openPanel() {
        if (mobileOverlay) mobileOverlay.classList.add('active');
        if (mobilePanel) mobilePanel.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closePanel() {
        if (mobileOverlay) mobileOverlay.classList.remove('active');
        if (mobilePanel) mobilePanel.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (mobileAiBtn) mobileAiBtn.addEventListener('click', openPanel);
    if (panelClose) panelClose.addEventListener('click', closePanel);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closePanel);
    
    // Escapeキーで閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobilePanel && mobilePanel.classList.contains('active')) {
            closePanel();
        }
    });
    
    // タブ切り替え
    panelTabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var targetTab = this.dataset.tab;
            
            // タブのアクティブ状態を切り替え
            panelTabs.forEach(function(t) { 
                t.classList.remove('active'); 
            });
            this.classList.add('active');
            
            // コンテンツを切り替え
            panelContents.forEach(function(c) { 
                c.classList.remove('active'); 
            });
            
            var target = document.getElementById('tab' + targetTab.charAt(0).toUpperCase() + targetTab.slice(1));
            if (target) target.classList.add('active');
        });
    });
    
    // スワイプでパネルを閉じる
    var touchStartY = 0;
    var touchEndY = 0;
    
    if (mobilePanel) {
        mobilePanel.addEventListener('touchstart', function(e) {
            touchStartY = e.touches[0].clientY;
        }, { passive: true });
        
        mobilePanel.addEventListener('touchmove', function(e) {
            touchEndY = e.touches[0].clientY;
            var diff = touchEndY - touchStartY;
            
            // 下方向にスワイプした場合
            if (diff > 0) {
                var content = mobilePanel.querySelector('.gic-panel-content');
                if (content && content.scrollTop === 0) {
                    mobilePanel.style.transform = 'translateY(' + Math.min(diff, 200) + 'px)';
                }
            }
        }, { passive: true });
        
        mobilePanel.addEventListener('touchend', function() {
            var diff = touchEndY - touchStartY;
            
            // 100px以上下にスワイプしたらパネルを閉じる
            if (diff > 100) {
                var content = mobilePanel.querySelector('.gic-panel-content');
                if (content && content.scrollTop === 0) {
                    closePanel();
                }
            }
            
            // 位置をリセット
            mobilePanel.style.transform = '';
            touchStartY = 0;
            touchEndY = 0;
        }, { passive: true });
    }
    
    // スムーススクロール
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (href === '#') return;
            
            var target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                var top = target.getBoundingClientRect().top + window.pageYOffset - 80;
                window.scrollTo({ top: top, behavior: 'smooth' });
                
                // モバイルパネルを閉じる
                if (mobilePanel && mobilePanel.classList.contains('active')) {
                    closePanel();
                }
            }
        });
    });
    
    // トースト通知
    function showToast(msg) {
        var t = document.getElementById('gicToast');
        if (!t) return;
        t.textContent = msg;
        t.classList.add('show');
        setTimeout(function() { t.classList.remove('show'); }, 3000);
    }
    window.showToast = showToast;
    
    // 外部リンクにrel属性追加
    document.querySelectorAll('.gic-content a[href^="http"]').forEach(function(link) {
        if (link.hostname !== window.location.hostname) {
            link.setAttribute('target', '_blank');
            var rel = link.getAttribute('rel') || '';
            if (rel.indexOf('noopener') === -1) rel += ' noopener';
            if (rel.indexOf('noreferrer') === -1) rel += ' noreferrer';
            link.setAttribute('rel', rel.trim());
        }
    });
    
    // テーブルのレスポンシブ対応
    document.querySelectorAll('.gic-content table').forEach(function(table) {
        if (!table.parentElement.classList.contains('table-wrapper')) {
            var wrapper = document.createElement('div');
            wrapper.style.overflowX = 'auto';
            wrapper.style.webkitOverflowScrolling = 'touch';
            wrapper.style.marginBottom = '20px';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
    
    // 画像の遅延読み込み
    if ('IntersectionObserver' in window) {
        var imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '50px 0px' });
        
        document.querySelectorAll('img[data-src]').forEach(function(img) {
            imageObserver.observe(img);
        });
    }
    
    // 読了率トラッキング
    var readingMilestones = [25, 50, 75, 100];
    var reachedMilestones = [];
    
    function trackReading() {
        var windowHeight = window.innerHeight;
        var documentHeight = document.documentElement.scrollHeight - windowHeight;
        if (documentHeight <= 0) return;
        
        var scrolled = window.scrollY;
        var progressPercent = Math.round((scrolled / documentHeight) * 100);
        
        readingMilestones.forEach(function(milestone) {
            if (progressPercent >= milestone && reachedMilestones.indexOf(milestone) === -1) {
                reachedMilestones.push(milestone);
                
                // Google Analytics 4対応
                if (typeof gtag === 'function') {
                    gtag('event', 'reading_progress', {
                        event_category: 'engagement',
                        event_label: milestone + '%',
                        page_type: 'single_column',
                        post_id: CONFIG.postId
                    });
                }
                
                console.log('[Reading Progress] ' + milestone + '% reached');
            }
        });
    }
    
    window.addEventListener('scroll', trackReading, { passive: true });
    
    // 滞在時間トラッキング
    var startTime = Date.now();
    var timeIntervals = [30, 60, 120, 300]; // 秒
    var reportedIntervals = [];
    
    setInterval(function() {
        var elapsed = Math.floor((Date.now() - startTime) / 1000);
        
        timeIntervals.forEach(function(interval) {
            if (elapsed >= interval && reportedIntervals.indexOf(interval) === -1) {
                reportedIntervals.push(interval);
                
                if (typeof gtag === 'function') {
                    gtag('event', 'time_on_page', {
                        event_category: 'engagement',
                        event_label: interval + 's',
                        page_type: 'single_column',
                        post_id: CONFIG.postId
                    });
                }
                
                console.log('[Time on Page] ' + interval + 's reached');
            }
        });
    }, 5000);
    
    // ページ離脱時の処理
    window.addEventListener('beforeunload', function() {
        var elapsed = Math.floor((Date.now() - startTime) / 1000);
        var scrollDepth = 0;
        var documentHeight = document.documentElement.scrollHeight - window.innerHeight;
        if (documentHeight > 0) {
            scrollDepth = Math.round((window.scrollY / documentHeight) * 100);
        }
        
        // Beacon APIで送信
        if (navigator.sendBeacon) {
            var data = new FormData();
            data.append('action', 'gic_track_exit');
            data.append('post_id', CONFIG.postId);
            data.append('time_spent', elapsed);
            data.append('scroll_depth', scrollDepth);
            
            navigator.sendBeacon(CONFIG.ajaxUrl, data);
        }
    });
    
    // コードブロックのコピー機能
    document.querySelectorAll('.gic-content pre').forEach(function(pre) {
        var copyBtn = document.createElement('button');
        copyBtn.textContent = 'コピー';
        copyBtn.style.cssText = 'position: absolute; top: 8px; right: 8px; padding: 4px 10px; font-size: 12px; background: #555; color: #fff; border: none; cursor: pointer; border-radius: 3px;';
        
        pre.style.position = 'relative';
        pre.appendChild(copyBtn);
        
        copyBtn.addEventListener('click', function() {
            var code = pre.querySelector('code') || pre;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code.textContent).then(function() {
                    copyBtn.textContent = 'コピー完了!';
                    setTimeout(function() { copyBtn.textContent = 'コピー'; }, 2000);
                });
            }
        });
    });
    
    // シェアボタンのクリックトラッキング
    document.querySelectorAll('.gic-share-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var platform = 'unknown';
            if (this.href.indexOf('twitter') !== -1 || this.href.indexOf('x.com') !== -1) platform = 'twitter';
            else if (this.href.indexOf('facebook') !== -1) platform = 'facebook';
            else if (this.href.indexOf('line') !== -1) platform = 'line';
            
            if (typeof gtag === 'function') {
                gtag('event', 'share', {
                    event_category: 'social',
                    event_label: platform,
                    page_type: 'single_column',
                    post_id: CONFIG.postId
                });
            }
            
            console.log('[Share] ' + platform);
        });
    });
    
    // FAQ開閉トラッキング
    document.querySelectorAll('.gic-faq-item').forEach(function(item, index) {
        item.addEventListener('toggle', function() {
            if (this.open) {
                if (typeof gtag === 'function') {
                    gtag('event', 'faq_open', {
                        event_category: 'engagement',
                        event_label: 'FAQ #' + (index + 1),
                        page_type: 'single_column',
                        post_id: CONFIG.postId
                    });
                }
                
                console.log('[FAQ] Opened #' + (index + 1));
            }
        });
    });
    
    // 関連コンテンツのクリックトラッキング
    document.querySelectorAll('.gic-grant-card, .gic-related-card').forEach(function(card) {
        card.addEventListener('click', function() {
            var title = this.querySelector('.gic-grant-title, .gic-related-card-title');
            var titleText = title ? title.textContent : 'Unknown';
            var type = this.classList.contains('gic-grant-card') ? 'grant' : 'column';
            
            if (typeof gtag === 'function') {
                gtag('event', 'related_click', {
                    event_category: 'navigation',
                    event_label: titleText,
                    content_type: type,
                    page_type: 'single_column',
                    post_id: CONFIG.postId
                });
            }
            
            console.log('[Related Click] ' + type + ': ' + titleText);
        });
    });
    
    // オンライン/オフライン検知
    window.addEventListener('online', function() {
        console.log('[Network] Connection restored');
        var notice = document.querySelector('.gic-offline-notice');
        if (notice) notice.remove();
        showToast('インターネット接続が復旧しました');
    });
    
    window.addEventListener('offline', function() {
        console.warn('[Network] Connection lost');
        var notice = document.createElement('div');
        notice.className = 'gic-offline-notice';
        notice.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; background: #DC2626; color: white; padding: 12px 20px; text-align: center; font-size: 14px; font-weight: 600; z-index: 10000;';
        notice.innerHTML = '⚠️ インターネット接続が切断されました';
        document.body.appendChild(notice);
    });
    
    // 印刷対応
    window.addEventListener('beforeprint', function() {
        // 全てのdetailsを開く
        document.querySelectorAll('details').forEach(function(details) {
            details.setAttribute('open', '');
        });
    });
    
    // パフォーマンス計測
    if (window.performance && window.performance.timing) {
        window.addEventListener('load', function() {
            setTimeout(function() {
                var timing = window.performance.timing;
                var pageLoadTime = timing.loadEventEnd - timing.navigationStart;
                var domReadyTime = timing.domContentLoadedEventEnd - timing.navigationStart;
                
                console.log('[Performance] Page Load: ' + pageLoadTime + 'ms');
                console.log('[Performance] DOM Ready: ' + domReadyTime + 'ms');
            }, 0);
        });
    }
    
    // 初期化完了ログ
    console.log('[✓] Single Column v7.1 initialized');
    console.log('[✓] Post ID: ' + CONFIG.postId);
    console.log('[✓] Features: AI Chat, TOC, Progress Bar, Analytics, Accessibility');
});
