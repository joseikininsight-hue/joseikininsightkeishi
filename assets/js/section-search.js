(function() {
    'use strict';
    
    // Get configuration from localized script (set by WordPress)
    const CONFIG = window.giSearchConfig || {
        ajaxUrl: '/wp-admin/admin-ajax.php',
        nonce: '',
        grantsUrl: '/grants/',
        municipalityUrl: '/grant_municipality/'
    };
    
    const $ = (sel) => document.querySelector(sel);
    const $$ = (sel) => document.querySelectorAll(sel);
    
    // ==========================================================================
    // 都道府県 → 市区町村連動（メインフォーム）
    // ==========================================================================
    const prefectureSelect = $('#search-prefecture');
    const municipalityField = $('#municipality-field');
    const municipalitySelect = $('#search-municipality');
    const municipalitySpinner = $('#municipality-spinner');
    
    if (prefectureSelect && municipalitySelect) {
        prefectureSelect.addEventListener('change', async function() {
            const slug = this.value;
            
            municipalitySelect.innerHTML = '<option value="">選択してください</option>';
            
            if (!slug) {
                municipalityField.style.display = 'none';
                return;
            }
            
            municipalityField.style.display = 'block';
            municipalitySelect.disabled = true;
            if (municipalitySpinner) municipalitySpinner.style.display = 'block';
            
            try {
                const municipalities = await fetchMunicipalities(slug);
                populateSelect(municipalitySelect, municipalities);
            } catch (err) {
                console.error('Municipality fetch error:', err);
                municipalitySelect.innerHTML = '<option value="">読み込みエラー</option>';
            } finally {
                municipalitySelect.disabled = false;
                if (municipalitySpinner) municipalitySpinner.style.display = 'none';
            }
        });
    }
    
    // ==========================================================================
    // 市町村検索セクション
    // ==========================================================================
    const muniPrefSelect = $('#municipality-prefecture-filter');
    const muniListContainer = $('#municipality-list');
    const muniLoading = $('#municipality-loading');
    
    if (muniPrefSelect && muniListContainer) {
        muniPrefSelect.addEventListener('change', async function() {
            const slug = this.value;
            
            if (!slug) {
                muniListContainer.innerHTML = `
                    <div class="search__municipality-placeholder">
                        <div class="search__placeholder-icon">
                            <svg width="48" height="48" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="24" cy="24" r="18" stroke-dasharray="4 4"/>
                                <path d="M24 16v8M24 28v2"/>
                            </svg>
                        </div>
                        <p class="search__placeholder-text">
                            都道府県を選択すると、<br>ここに市町村一覧が表示されます
                        </p>
                    </div>`;
                return;
            }
            
            muniListContainer.style.display = 'none';
            if (muniLoading) muniLoading.style.display = 'flex';
            
            try {
                const municipalities = await fetchMunicipalities(slug);
                renderMunicipalityList(municipalities);
            } catch (err) {
                console.error('Municipality list error:', err);
                muniListContainer.innerHTML = '<p class="search__no-result">読み込みに失敗しました</p>';
            } finally {
                if (muniLoading) muniLoading.style.display = 'none';
                muniListContainer.style.display = 'block';
            }
        });
    }
    
    function renderMunicipalityList(municipalities) {
        if (!municipalities || municipalities.length === 0) {
            muniListContainer.innerHTML = '<p class="search__no-result">市区町村データがありません</p>';
            return;
        }
        
        const html = `<div class="search__municipality-links">
            ${municipalities.map(m => 
                `<a href="${CONFIG.municipalityUrl}${escapeHtml(m.slug)}/" class="search__municipality-link">${escapeHtml(m.name)}</a>`
            ).join('')}
        </div>`;
        
        muniListContainer.innerHTML = html;
    }
    
    // ==========================================================================
    // カテゴリフィルター
    // ==========================================================================
    setupFilter('category-filter-input', ['popular-categories-grid', 'all-categories-grid'], 'no-categories-msg');
    
    // ==========================================================================
    // タグフィルター
    // ==========================================================================
    setupFilter('tag-filter-input', ['popular-tags-grid', 'all-tags-grid'], 'no-tags-msg');
    
    function setupFilter(inputId, gridIds, noResultId) {
        const input = $('#' + inputId);
        const noResult = $('#' + noResultId);
        
        if (!input) return;
        
        input.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            let totalVisible = 0;
            
            gridIds.forEach(gridId => {
                const grid = $('#' + gridId);
                if (!grid) return;
                
                const pills = grid.querySelectorAll('.search__pill');
                pills.forEach(pill => {
                    const name = pill.getAttribute('data-name') || '';
                    const match = name.toLowerCase().includes(query);
                    pill.style.display = match ? 'inline-flex' : 'none';
                    if (match) totalVisible++;
                });
            });
            
            if (noResult) {
                noResult.style.display = totalVisible === 0 && query ? 'block' : 'none';
            }
        });
    }
    
    // ==========================================================================
    // 折りたたみトグル
    // ==========================================================================
    setupToggle('all-categories-toggle', 'all-categories-content');
    setupToggle('all-tags-toggle', 'all-tags-content');
    
    function setupToggle(btnId, contentId) {
        const btn = $('#' + btnId);
        const content = $('#' + contentId);
        
        if (!btn || !content) return;
        
        btn.addEventListener('click', function() {
            const isHidden = content.hidden;
            content.hidden = !isHidden;
            this.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            
            const span = this.querySelector('span');
            if (span) {
                // Determine the correct label based on the button ID
                const openLabel = btnId.includes('categories') ? 'すべてのカテゴリを見る' : 'すべてのタグを見る';
                span.textContent = isHidden ? '閉じる' : openLabel;
            }
        });
    }
    
    // ==========================================================================
    // フォームリセット
    // ==========================================================================
    const resetBtn = $('#search-reset');
    const form = $('#grant-search-form');
    
    if (resetBtn && form) {
        resetBtn.addEventListener('click', function() {
            form.reset();
            
            if (municipalityField) municipalityField.style.display = 'none';
            if (municipalitySelect) {
                municipalitySelect.innerHTML = '<option value="">選択してください</option>';
            }
            
            // フィルター入力もクリア
            const categoryFilter = $('#category-filter-input');
            const tagFilter = $('#tag-filter-input');
            if (categoryFilter) {
                categoryFilter.value = '';
                categoryFilter.dispatchEvent(new Event('input'));
            }
            if (tagFilter) {
                tagFilter.value = '';
                tagFilter.dispatchEvent(new Event('input'));
            }
        });
    }
    
    // ==========================================================================
    // ユーティリティ
    // ==========================================================================
    async function fetchMunicipalities(prefectureSlug) {
        const formData = new FormData();
        formData.append('action', 'gi_get_municipalities_for_prefecture');
        formData.append('prefecture_slug', prefectureSlug);
        formData.append('nonce', CONFIG.nonce);
        
        const response = await fetch(CONFIG.ajaxUrl, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) throw new Error('Network error');
        
        const data = await response.json();
        
        if (data.success) {
            return data.data.municipalities || data.data?.data?.municipalities || [];
        }
        
        throw new Error(data.data?.message || 'Unknown error');
    }
    
    function populateSelect(select, items) {
        let html = '<option value="">すべての市区町村</option>';
        
        if (items && items.length > 0) {
            items.forEach(item => {
                html += `<option value="${escapeHtml(item.slug)}">${escapeHtml(item.name)}</option>`;
            });
        }
        
        select.innerHTML = html;
    }
    
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    console.log('✅ Search Section v53.0 initialized');
    
})();
