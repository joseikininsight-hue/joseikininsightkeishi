/**
 * Single Grant Page JavaScript
 * Version: 302.0.0
 * 補助金詳細ページ専用スクリプト
 */

// CONFIG は PHP側で設定される
// var CONFIG = { postId, ajaxUrl, nonce, url, title, totalChecklist };

document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // プログレスバー
    var progress = document.getElementById('progressBar');
    function updateProgress() {
        var h = document.documentElement.scrollHeight - window.innerHeight;
        var p = h > 0 ? Math.min(100, (window.pageYOffset / h) * 100) : 0;
        if (progress) progress.style.width = p + '%';
    }
    window.addEventListener('scroll', updateProgress, { passive: true });
    
    // チェックリスト
    var checklistItems = document.querySelectorAll('.gi-checklist-item');
    var checklistFill = document.getElementById('checklistFill');
    var checklistCount = document.getElementById('checklistCount');
    var checklistPercent = document.getElementById('checklistPercent');
    var checklistResult = document.getElementById('checklistResult');
    var checklistResultText = document.getElementById('checklistResultText');
    var checklistResultSub = document.getElementById('checklistResultSub');
    
    function updateChecklistUI() {
        var total = checklistItems.length;
        var checked = document.querySelectorAll('.gi-checklist-item.checked').length;
        var requiredItems = document.querySelectorAll('.gi-checklist-item[data-required="true"]');
        var requiredChecked = document.querySelectorAll('.gi-checklist-item[data-required="true"].checked').length;
        var percent = Math.round((checked / total) * 100);
        
        if (checklistFill) checklistFill.style.width = percent + '%';
        if (checklistCount) checklistCount.textContent = checked + ' / ' + total + ' 完了';
        if (checklistPercent) checklistPercent.textContent = percent + '%';
        
        var allRequiredChecked = requiredChecked === requiredItems.length;
        
        if (checklistResult) {
            if (allRequiredChecked && requiredItems.length > 0) {
                checklistResult.classList.add('complete');
                if (checklistResultText) checklistResultText.textContent = '✓ 申請可能です！';
                if (checklistResultSub) checklistResultSub.textContent = 'すべての必須項目をクリアしました。公式サイトから申請を進めましょう。';
            } else {
                checklistResult.classList.remove('complete');
                var remaining = requiredItems.length - requiredChecked;
                if (checklistResultText) checklistResultText.textContent = 'あと' + remaining + '項目で申請可能';
                if (checklistResultSub) checklistResultSub.textContent = '必須項目をすべてクリアすると申請可能です';
            }
        }
        
        var checkedIds = [];
        document.querySelectorAll('.gi-checklist-item.checked').forEach(function(item) { checkedIds.push(item.dataset.id); });
        try { localStorage.setItem('gi_checklist_' + CONFIG.postId, JSON.stringify(checkedIds)); } catch(e) {}
    }
    
    // 復元
    try {
        var saved = localStorage.getItem('gi_checklist_' + CONFIG.postId);
        if (saved) {
            var checkedIds = JSON.parse(saved);
            checklistItems.forEach(function(item) {
                if (checkedIds.indexOf(item.dataset.id) !== -1) {
                    item.classList.add('checked');
                    var cb = item.querySelector('.gi-checklist-checkbox');
                    if (cb) cb.setAttribute('aria-checked', 'true');
                }
            });
            updateChecklistUI();
        }
    } catch(e) {}
    
    checklistItems.forEach(function(item) {
        var cb = item.querySelector('.gi-checklist-checkbox');
        var helpBtn = item.querySelector('.gi-checklist-help-btn');
        
        function toggleCheck(e) {
            if (e.target.closest('.gi-checklist-help-btn')) return;
            item.classList.toggle('checked');
            if (cb) cb.setAttribute('aria-checked', item.classList.contains('checked') ? 'true' : 'false');
            updateChecklistUI();
        }
        
        item.addEventListener('click', toggleCheck);
        if (cb) cb.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleCheck(e); } });
        if (helpBtn) helpBtn.addEventListener('click', function(e) { e.stopPropagation(); item.classList.toggle('show-help'); });
    });
    
    var checklistReset = document.getElementById('checklistReset');
    if (checklistReset) {
        checklistReset.addEventListener('click', function() {
            if (confirm('チェックをすべてリセットしますか？')) {
                checklistItems.forEach(function(item) {
                    item.classList.remove('checked', 'show-help');
                    var cb = item.querySelector('.gi-checklist-checkbox');
                    if (cb) cb.setAttribute('aria-checked', 'false');
                });
                try { localStorage.removeItem('gi_checklist_' + CONFIG.postId); } catch(e) {}
                updateChecklistUI();
                if(window.showToast) window.showToast('チェックリストをリセットしました');
            }
        });
    }
    
    var checklistPrint = document.getElementById('checklistPrint');
    if (checklistPrint) checklistPrint.addEventListener('click', function() { window.print(); });
    
    // AI
    function sendAiMessage(input, container, btn) {
        var question = input.value.trim();
        if (!question) return;
        
        addMessage(container, question, 'user');
        input.value = '';
        btn.disabled = true;
        
        var loadingMsg = document.createElement('div');
        loadingMsg.className = 'gi-ai-msg';
        loadingMsg.innerHTML = '<div class="gi-ai-avatar">AI</div><div class="gi-ai-bubble">考え中...</div>';
        container.appendChild(loadingMsg);
        container.scrollTop = container.scrollHeight;
        
        var formData = new FormData();
        formData.append('action', 'gi_ai_chat');
        // Try to use fresh nonce from global settings if available, otherwise use CONFIG.nonce
        var nonce = '';
        if (typeof window.gi_ajax !== 'undefined' && window.gi_ajax.nonce) {
            nonce = window.gi_ajax.nonce;
        } else if (typeof window.ajaxSettings !== 'undefined' && window.ajaxSettings.nonce) {
            nonce = window.ajaxSettings.nonce;
        } else if (typeof window.wpApiSettings !== 'undefined' && window.wpApiSettings.nonce) {
            nonce = window.wpApiSettings.nonce;
        } else {
            nonce = CONFIG.nonce;
        }
        formData.append('nonce', nonce);
        formData.append('post_id', CONFIG.postId);
        formData.append('question', question);
        
        fetch(CONFIG.ajaxUrl, { method: 'POST', body: formData })
            .then(function(r) { 
                if (!r.ok) {
                    throw new Error('HTTP error! status: ' + r.status);
                }
                return r.json(); 
            })
            .then(function(data) {
                loadingMsg.remove();
                console.log('AI Chat Response:', data);
                
                if (data.success && data.data && data.data.answer) {
                    addMessage(container, data.data.answer, 'ai');
                } else {
                    // Show detailed error with better formatting
                    var errorMsg = 'エラーが発生しました。';
                    if (data.data && data.data.message) {
                        errorMsg = data.data.message;
                    } else if (!data.success) {
                        errorMsg = '申し訳ございません。AI応答の生成に失敗しました。ページを再読み込みしてもう一度お試しください。';
                    }
                    addMessage(container, errorMsg, 'ai');
                    console.error('AI Chat Error:', data);
                }
            })
            .catch(function(err) {
                loadingMsg.remove();
                addMessage(container, '通信エラーが発生しました。インターネット接続を確認してから、もう一度お試しください。', 'ai');
                console.error('AI Chat Network Error:', err);
            })
            .finally(function() { btn.disabled = false; });
    }
    
    function addMessage(container, text, type) {
        var msg = document.createElement('div');
        msg.className = 'gi-ai-msg' + (type === 'user' ? ' user' : '');
        msg.innerHTML = '<div class="gi-ai-avatar">' + (type === 'user' ? 'You' : 'AI') + '</div><div class="gi-ai-bubble">' + escapeHtml(text).replace(/\n/g, '<br>') + '</div>';
        container.appendChild(msg);
        container.scrollTop = container.scrollHeight;
    }
    
    function escapeHtml(text) { var div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
    
    var aiInput = document.getElementById('aiInput');
    var aiSend = document.getElementById('aiSend');
    var aiMessages = document.getElementById('aiMessages');
    
    if (aiSend && aiInput && aiMessages) {
        aiSend.addEventListener('click', function() { sendAiMessage(aiInput, aiMessages, aiSend); });
        aiInput.addEventListener('keydown', function(e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendAiMessage(aiInput, aiMessages, aiSend); } });
        aiInput.addEventListener('input', function() { this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 100) + 'px'; });
    }
    
    document.querySelectorAll('.gi-ai-chip, .gi-mobile-ai-chip').forEach(function(chip) {
        chip.addEventListener('click', function() {
            var input = this.classList.contains('gi-ai-chip') ? aiInput : mobileAiInput;
            var container = this.classList.contains('gi-ai-chip') ? aiMessages : mobileAiMessages;
            var btn = this.classList.contains('gi-ai-chip') ? aiSend : mobileAiSend;
            
            if (this.dataset.action) {
                if (this.dataset.action === 'diagnosis') {
                    runDiagnosis(container);
                } else if (this.dataset.action === 'roadmap') {
                    generateRoadmap(container);
                }
            } else if (input) {
                input.value = this.dataset.q;
                sendAiMessage(input, container, btn);
            }
        });
    });

    // 資格診断機能
    function runDiagnosis(container) {
        addMessage(container, '申請資格があるか診断してください。', 'user');
        
        var loadingMsg = createLoadingMessage(container, '資格を診断中...');
        
        // 簡易的な診断のため、チェックリストの状態などを送信（今回はPOST_IDのみでバックエンドに任せる）
        var formData = new FormData();
        formData.append('action', 'gi_eligibility_diagnosis');
        // Use fresh nonce if available
        var nonce = '';
        if (typeof window.gi_ajax !== 'undefined' && window.gi_ajax.nonce) {
            nonce = window.gi_ajax.nonce;
        } else if (typeof window.ajaxSettings !== 'undefined' && window.ajaxSettings.nonce) {
            nonce = window.ajaxSettings.nonce;
        } else if (typeof window.wpApiSettings !== 'undefined' && window.wpApiSettings.nonce) {
            nonce = window.wpApiSettings.nonce;
        } else {
            nonce = CONFIG.nonce;
        }
        formData.append('nonce', nonce);
        formData.append('post_id', CONFIG.postId);
        
        // チェックリストの回答状況も送る場合
        var answers = {};
        document.querySelectorAll('.gi-checklist-item').forEach(function(item) {
            answers[item.querySelector('.gi-checklist-label').textContent.trim()] = item.classList.contains('checked') ? 'はい' : 'いいえ';
        });
        for (var key in answers) {
            formData.append('answers[' + key + ']', answers[key]);
        }

        fetch(CONFIG.ajaxUrl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                loadingMsg.remove();
                if (data.success) {
                    var d = data.data;
                    var html = '<div style="font-weight:bold;margin-bottom:8px;font-size:1.1em;">' + (d.eligible ? '✅ 申請資格の可能性が高いです' : '⚠️ 要件を確認してください') + '</div>';
                    
                    if (d.reasons && d.reasons.length) {
                        html += '<strong>判定理由:</strong><ul style="margin:4px 0 8px 20px;list-style:disc;">' + d.reasons.map(function(r){return '<li>'+r+'</li>'}).join('') + '</ul>';
                    }
                    if (d.warnings && d.warnings.length) {
                        html += '<strong>注意点:</strong><ul style="margin:4px 0 8px 20px;list-style:disc;color:#dc2626;">' + d.warnings.map(function(w){return '<li>'+w+'</li>'}).join('') + '</ul>';
                    }
                    if (d.next_steps && d.next_steps.length) {
                        html += '<strong>次のステップ:</strong><ol style="margin:4px 0 0 20px;list-style:decimal;">' + d.next_steps.map(function(s){return '<li>'+s+'</li>'}).join('') + '</ol>';
                    }
                    addHtmlMessage(container, html, 'ai');
                } else {
                    addMessage(container, '診断中にエラーが発生しました: ' + (data.data.message || '不明なエラー'), 'ai');
                }
            })
            .catch(function(e) {
                loadingMsg.remove();
                addMessage(container, '通信エラーが発生しました。', 'ai');
            });
    }

    // ロードマップ生成機能
    function generateRoadmap(container) {
        addMessage(container, '申請までのロードマップを作成してください。', 'user');
        var loadingMsg = createLoadingMessage(container, 'ロードマップを作成中...');

        var formData = new FormData();
        formData.append('action', 'gi_generate_roadmap');
        // Use fresh nonce if available
        var nonce = '';
        if (typeof window.gi_ajax !== 'undefined' && window.gi_ajax.nonce) {
            nonce = window.gi_ajax.nonce;
        } else if (typeof window.ajaxSettings !== 'undefined' && window.ajaxSettings.nonce) {
            nonce = window.ajaxSettings.nonce;
        } else if (typeof window.wpApiSettings !== 'undefined' && window.wpApiSettings.nonce) {
            nonce = window.wpApiSettings.nonce;
        } else {
            nonce = CONFIG.nonce;
        }
        formData.append('nonce', nonce);
        formData.append('post_id', CONFIG.postId);

        fetch(CONFIG.ajaxUrl, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                loadingMsg.remove();
                if (data.success) {
                    var d = data.data;
                    var html = '<div style="font-weight:bold;margin-bottom:12px;">📅 申請ロードマップ</div>';
                    
                    if (d.roadmap && d.roadmap.length) {
                        html += '<div style="display:flex;flex-direction:column;gap:12px;">';
                        d.roadmap.forEach(function(step, i) {
                            html += '<div style="background:#f9fafb;padding:10px;border-left:3px solid #111;font-size:0.95em;">';
                            html += '<div style="font-weight:bold;color:#111;">' + (i+1) + '. ' + step.title + ' <span style="font-weight:normal;color:#666;font-size:0.9em;">(' + step.timing + ')</span></div>';
                            html += '<div style="color:#4b5563;margin-top:4px;">' + step.description + '</div>';
                            html += '</div>';
                        });
                        html += '</div>';
                    }
                    
                    if (d.tips && d.tips.length) {
                        html += '<div style="margin-top:12px;font-size:0.9em;color:#4b5563;"><strong>💡 アドバイス:</strong> ' + d.tips[0] + '</div>';
                    }
                    
                    addHtmlMessage(container, html, 'ai');
                } else {
                    addMessage(container, 'ロードマップ生成に失敗しました。', 'ai');
                }
            })
            .catch(function(e) {
                loadingMsg.remove();
                addMessage(container, '通信エラーが発生しました。', 'ai');
            });
    }

    function createLoadingMessage(container, text) {
        var msg = document.createElement('div');
        msg.className = 'gi-ai-msg';
        msg.innerHTML = '<div class="gi-ai-avatar">AI</div><div class="gi-ai-bubble">' + text + '</div>';
        container.appendChild(msg);
        container.scrollTop = container.scrollHeight;
        return msg;
    }

    function addHtmlMessage(container, html, type) {
        var msg = document.createElement('div');
        msg.className = 'gi-ai-msg' + (type === 'user' ? ' user' : '');
        msg.innerHTML = '<div class="gi-ai-avatar">' + (type === 'user' ? 'You' : 'AI') + '</div><div class="gi-ai-bubble">' + html + '</div>';
        container.appendChild(msg);
        container.scrollTop = container.scrollHeight;
    }
    
    // パネル
    var mobileAiBtn = document.getElementById('mobileAiBtn');
    var mobileOverlay = document.getElementById('mobileOverlay');
    var mobilePanel = document.getElementById('mobilePanel');
    var panelClose = document.getElementById('panelClose');
    var panelTabs = document.querySelectorAll('.gi-panel-tab');
    var panelContents = document.querySelectorAll('.gi-panel-content-tab');
    
    function openPanel() { if (mobileOverlay) mobileOverlay.classList.add('active'); if (mobilePanel) mobilePanel.classList.add('active'); document.body.style.overflow = 'hidden'; }
    function closePanel() { if (mobileOverlay) mobileOverlay.classList.remove('active'); if (mobilePanel) mobilePanel.classList.remove('active'); document.body.style.overflow = ''; }
    
    if (mobileAiBtn) mobileAiBtn.addEventListener('click', openPanel);
    if (panelClose) panelClose.addEventListener('click', closePanel);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closePanel);
    
    panelTabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var targetTab = this.dataset.tab;
            panelTabs.forEach(function(t) { t.classList.remove('active'); });
            panelContents.forEach(function(c) { c.classList.remove('active'); });
            this.classList.add('active');
            var target = document.getElementById('tab' + targetTab.charAt(0).toUpperCase() + targetTab.slice(1));
            if (target) target.classList.add('active');
        });
    });
    
    document.querySelectorAll('.mobile-toc-link').forEach(function(link) { link.addEventListener('click', closePanel); });
    
    // ブックマーク
    var bookmarkBtn = document.getElementById('bookmarkBtn');
    var mobileBookmarkBtn = document.getElementById('mobileBookmarkBtn');
    var bookmarkKey = 'gi_bookmarks';
    
    function getBookmarks() { try { return JSON.parse(localStorage.getItem(bookmarkKey) || '[]'); } catch(e) { return []; } }
    function isBookmarked() { return getBookmarks().indexOf(CONFIG.postId) !== -1; }
    
    function updateBookmarkUI() {
        var bookmarked = isBookmarked();
        var text = bookmarked ? '保存済み' : '保存する';
        if (bookmarkBtn) { var svg = bookmarkBtn.querySelector('svg'); if (svg) svg.style.fill = bookmarked ? 'currentColor' : 'none'; var span = bookmarkBtn.querySelector('span'); if (span) span.textContent = text; }
        if (mobileBookmarkBtn) { var span = mobileBookmarkBtn.querySelector('span'); if (span) span.textContent = text; }
    }
    
    function toggleBookmark() {
        var bookmarks = getBookmarks();
        var index = bookmarks.indexOf(CONFIG.postId);
        if (index !== -1) { bookmarks.splice(index, 1); } else { bookmarks.push(CONFIG.postId); }
        try { localStorage.setItem(bookmarkKey, JSON.stringify(bookmarks)); } catch(e) {}
        updateBookmarkUI();
    }
    
    if (bookmarkBtn) bookmarkBtn.addEventListener('click', toggleBookmark);
    if (mobileBookmarkBtn) mobileBookmarkBtn.addEventListener('click', toggleBookmark);
    updateBookmarkUI();
    
    // シェア
    var shareBtn = document.getElementById('shareBtn');
    var mobileShareBtn = document.getElementById('mobileShareBtn');
    
    function handleShare() {
        if (navigator.share) { navigator.share({ title: CONFIG.title, url: CONFIG.url }).catch(function() {}); }
        else if (navigator.clipboard) { navigator.clipboard.writeText(CONFIG.url).then(function() { alert('URLをコピーしました'); }).catch(function() {}); }
    }
    
    if (shareBtn) shareBtn.addEventListener('click', handleShare);
    if (mobileShareBtn) mobileShareBtn.addEventListener('click', handleShare);
    
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
            }
        });
    });
    
    console.log('Grant Single v302 Initialized');
    
    // トースト通知
    function showToast(msg) {
        var t = document.getElementById('giToast');
        if(!t) return;
        t.textContent = msg;
        t.classList.add('show');
        setTimeout(function(){ t.classList.remove('show'); }, 3000);
    }
    window.showToast = showToast;
    
    // Mobile AI references
    var mobileAiInput = document.getElementById('mobileAiInput');
    var mobileAiSend = document.getElementById('mobileAiSend');
    var mobileAiMessages = document.getElementById('mobileAiMessages');
    
    if (mobileAiSend && mobileAiInput && mobileAiMessages) {
        mobileAiSend.addEventListener('click', function() { sendAiMessage(mobileAiInput, mobileAiMessages, mobileAiSend); });
        mobileAiInput.addEventListener('keydown', function(e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendAiMessage(mobileAiInput, mobileAiMessages, mobileAiSend); } });
        mobileAiInput.addEventListener('input', function() { this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 100) + 'px'; });
    }
});
