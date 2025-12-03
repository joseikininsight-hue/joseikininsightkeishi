<?php
/**
 * Template Part: Front Page Search Section
 * フロントページ検索セクション - 完全最適化版
 *
 * @package Grant_Insight_Perfect
 * @version 53.0.0 - Production Ready
 */

if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// ==========================================================================
// データ取得（パフォーマンス最適化）
// ==========================================================================

$cache_key = 'search_section_data_v53';
$cached_data = get_transient($cache_key);

if ($cached_data === false) {
    
    // カテゴリー取得
    $all_categories = get_terms([
        'taxonomy'   => 'grant_category',
        'hide_empty' => false,
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => 100,
    ]);
    if (is_wp_error($all_categories)) {
        $all_categories = [];
    }
    
    // 都道府県取得
    $prefectures = function_exists('gi_get_all_prefectures') 
        ? gi_get_all_prefectures() 
        : [];
    
    // タグ取得
    $all_tags = get_terms([
        'taxonomy'   => 'grant_tag',
        'hide_empty' => true,
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => 100,
    ]);
    if (is_wp_error($all_tags)) {
        $all_tags = [];
    }
    
    // 統計データ
    $total_grants = wp_count_posts('grant')->publish ?? 0;
    
    $cached_data = [
        'categories'   => $all_categories,
        'prefectures'  => $prefectures,
        'tags'         => $all_tags,
        'total_grants' => $total_grants,
    ];
    
    set_transient($cache_key, $cached_data, 30 * MINUTE_IN_SECONDS);
}

// データ展開
$all_categories = $cached_data['categories'];
$prefectures    = $cached_data['prefectures'];
$all_tags       = $cached_data['tags'];
$total_grants   = $cached_data['total_grants'];

// 人気カテゴリー（上位10件）
$popular_categories = is_array($all_categories) ? array_slice($all_categories, 0, 10) : [];

// 人気タグ（上位10件）
$popular_tags = is_array($all_tags) ? array_slice($all_tags, 0, 10) : [];

// カテゴリーグループ定義
$category_groups = [
    [
        'id'         => 'types',
        'name'       => '補助金の種類',
        'name_en'    => 'TYPES',
        'icon'       => 'briefcase',
        'categories' => is_array($all_categories) ? array_slice($all_categories, 0, 8) : [],
    ],
    [
        'id'         => 'fields',
        'name'       => '対象分野',
        'name_en'    => 'FIELDS',
        'icon'       => 'layers',
        'categories' => is_array($all_categories) ? array_slice($all_categories, 8, 8) : [],
    ],
    [
        'id'         => 'supports',
        'name'       => '支援内容',
        'name_en'    => 'SUPPORTS',
        'icon'       => 'heart',
        'categories' => is_array($all_categories) ? array_slice($all_categories, 16, 8) : [],
    ],
];

// 地域グループ定義
$region_groups = [
    [
        'id'          => 'hokkaido-tohoku',
        'name'        => '北海道・東北',
        'name_en'     => 'HOKKAIDO / TOHOKU',
        'prefectures' => ['北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県'],
    ],
    [
        'id'          => 'kanto',
        'name'        => '関東',
        'name_en'     => 'KANTO',
        'prefectures' => ['東京都', '神奈川県', '埼玉県', '千葉県', '茨城県', '栃木県', '群馬県'],
    ],
    [
        'id'          => 'hokuriku-koshinetsu',
        'name'        => '北陸・甲信越',
        'name_en'     => 'HOKURIKU / KOSHINETSU',
        'prefectures' => ['新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県'],
    ],
    [
        'id'          => 'tokai',
        'name'        => '東海',
        'name_en'     => 'TOKAI',
        'prefectures' => ['愛知県', '岐阜県', '三重県', '静岡県'],
    ],
    [
        'id'          => 'kinki',
        'name'        => '関西',
        'name_en'     => 'KANSAI',
        'prefectures' => ['大阪府', '京都府', '兵庫県', '奈良県', '滋賀県', '和歌山県'],
    ],
    [
        'id'          => 'chugoku',
        'name'        => '中国',
        'name_en'     => 'CHUGOKU',
        'prefectures' => ['鳥取県', '島根県', '岡山県', '広島県', '山口県'],
    ],
    [
        'id'          => 'shikoku',
        'name'        => '四国',
        'name_en'     => 'SHIKOKU',
        'prefectures' => ['徳島県', '香川県', '愛媛県', '高知県'],
    ],
    [
        'id'          => 'kyushu-okinawa',
        'name'        => '九州・沖縄',
        'name_en'     => 'KYUSHU / OKINAWA',
        'prefectures' => ['福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'],
    ],
];

// 対象者カード定義
$target_cards = [
    [
        'id'          => 'individual',
        'title'       => '個人事業主・フリーランス',
        'description' => '個人でも申請可能な補助金・給付金',
        'icon'        => 'user',
        'url'         => home_url('/grants/?grant_tag=個人向け'),
        'featured'    => true,
    ],
    [
        'id'          => 'small-business',
        'title'       => '中小企業',
        'description' => 'ものづくり・IT導入・事業再構築など',
        'icon'        => 'building',
        'url'         => home_url('/grants/?grant_tag=中小企業'),
        'featured'    => false,
    ],
    [
        'id'          => 'startup',
        'title'       => '創業・スタートアップ',
        'description' => '起業資金・創業融資・オフィス支援',
        'icon'        => 'rocket',
        'url'         => home_url('/grants/?grant_tag=創業'),
        'featured'    => false,
    ],
];

// AJAX用ノンス
$ajax_nonce = wp_create_nonce('gi_ajax_nonce');

/**
 * アイコンSVGを取得
 */
function search_get_svg_icon($name) {
    $icons = [
        'user'      => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>',
        'building'  => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M9 15h2M13 15h2"/></svg>',
        'rocket'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2c4 4 6 8 6 14H6c0-6 2-10 6-14z"/><circle cx="12" cy="11" r="2"/><path d="M6 16l-3 5h6M18 16l3 5h-6"/></svg>',
        'briefcase' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2M3 12h18"/></svg>',
        'layers'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>',
        'heart'     => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>',
        'map'       => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 6v16l7-4 8 4 7-4V2l-7 4-8-4-7 4zM8 2v16M16 6v16"/></svg>',
        'folder'    => '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 4a1 1 0 011-1h3l2 2h5a1 1 0 011 1v6a1 1 0 01-1 1H3a1 1 0 01-1-1V4z"/></svg>',
        'hash'      => '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 6h10M3 10h10M6 3v10M10 3v10"/></svg>',
    ];
    
    return $icons[$name] ?? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="9"/></svg>';
}
?>

<section 
    class="search" 
    id="search-section" 
    role="search" 
    aria-labelledby="search-heading">
    
    <!-- ========================================
         統計バー
         ======================================== -->
    <div class="search__stats-bar">
        <div class="search__container">
            <div class="search__stats-inner">
                <div class="search__stat-item">
                    <span class="search__stat-label">DATABASE:</span>
                    <span class="search__stat-value"><?php echo number_format($total_grants); ?></span>
                    <span class="search__stat-unit">件掲載</span>
                </div>
                <div class="search__stat-divider" aria-hidden="true"></div>
                <div class="search__stat-item">
                    <span class="search__stat-label">UPDATE:</span>
                    <time class="search__stat-date" datetime="<?php echo date('Y-m-d'); ?>">
                        <?php echo date('Y.m.d'); ?> 更新
                    </time>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ========================================
         メイン検索フォーム
         ======================================== -->
    <div class="search__main">
        <div class="search__container">
            
            <header class="search__header">
                <p class="search__header-sub">SEARCH GRANTS</p>
                <h2 class="search__header-title" id="search-heading">補助金・助成金を検索</h2>
            </header>
            
            <form 
                id="grant-search-form" 
                class="search__form" 
                action="<?php echo esc_url(home_url('/grants/')); ?>" 
                method="get"
                role="search">
                
                <!-- 受付状況 -->
                <div class="search__form-row">
                    <span class="search__label">受付状況</span>
                    <div class="search__radio-group">
                        <label class="search__radio">
                            <input type="radio" name="status" value="open" checked class="search__radio-input">
                            <span class="search__radio-label">募集中のみ</span>
                        </label>
                        <label class="search__radio">
                            <input type="radio" name="status" value="all" class="search__radio-input">
                            <span class="search__radio-label">すべて表示</span>
                        </label>
                    </div>
                </div>
                
                <!-- 選択フィールド -->
                <div class="search__form-grid">
                    
                    <!-- カテゴリー -->
                    <div class="search__field">
                        <label for="search-category" class="search__label">用途・カテゴリー</label>
                        <div class="search__select-wrap">
                            <select id="search-category" name="category" class="search__select">
                                <option value="">選択してください</option>
                                <?php foreach ($all_categories as $cat): ?>
                                <option value="<?php echo esc_attr($cat->slug); ?>">
                                    <?php echo esc_html($cat->name); ?> (<?php echo esc_html($cat->count); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- 都道府県 -->
                    <div class="search__field">
                        <label for="search-prefecture" class="search__label">都道府県</label>
                        <div class="search__select-wrap">
                            <select id="search-prefecture" name="prefecture" class="search__select">
                                <option value="">選択してください</option>
                                <?php foreach ($prefectures as $pref): ?>
                                <option value="<?php echo esc_attr($pref['slug']); ?>">
                                    <?php echo esc_html($pref['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- 市区町村（動的） -->
                    <div class="search__field search__field--municipality" id="municipality-field" style="display: none;">
                        <label for="search-municipality" class="search__label">市区町村</label>
                        <div class="search__select-wrap">
                            <select id="search-municipality" name="municipality" class="search__select">
                                <option value="">選択してください</option>
                            </select>
                            <div class="search__spinner" id="municipality-spinner"></div>
                        </div>
                    </div>
                    
                </div>
                
                <!-- フリーワード -->
                <div class="search__form-row">
                    <label for="search-keyword" class="search__label">フリーワード</label>
                    <input 
                        type="search" 
                        id="search-keyword" 
                        name="search" 
                        class="search__input" 
                        placeholder="例：IT導入、設備投資、創業支援..."
                        autocomplete="off">
                </div>
                
                <!-- アクションボタン -->
                <div class="search__actions">
                    <button type="button" id="search-reset" class="search__btn search__btn--outline">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M2 8a6 6 0 1011.5 2.5"/>
                            <path d="M2 4v4h4"/>
                        </svg>
                        <span>条件クリア</span>
                    </button>
                    <button type="submit" class="search__btn search__btn--solid">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <circle cx="8" cy="8" r="5"/>
                            <path d="M12 12l4 4"/>
                        </svg>
                        <span>この条件で検索</span>
                    </button>
                </div>
                
                <!-- 補助リンク -->
                <div class="search__sub-links">
                    <a href="<?php echo esc_url(home_url('/grants/')); ?>" class="search__sub-link">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M2 4h10M2 7h10M2 10h6"/>
                        </svg>
                        <span>詳細検索</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/saved-searches/')); ?>" class="search__sub-link">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M2 2h10v12l-5-3-5 3V2z"/>
                        </svg>
                        <span>保存条件</span>
                    </a>
                    <a href="<?php echo esc_url(home_url('/history/')); ?>" class="search__sub-link">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <circle cx="7" cy="7" r="5"/>
                            <path d="M7 4v3l2 2"/>
                        </svg>
                        <span>閲覧履歴</span>
                    </a>
                </div>
                
            </form>
            
        </div>
    </div>
    
    <!-- ========================================
         対象者から探す
         ======================================== -->
    <div class="search__section search__section--alt">
        <div class="search__container">
            
            <header class="search__section-header">
                <p class="search__section-sub">SEARCH BY TARGET</p>
                <h3 class="search__section-title">対象者から探す</h3>
            </header>
            
            <div class="search__target-grid">
                <?php foreach ($target_cards as $card): ?>
                <a href="<?php echo esc_url($card['url']); ?>" 
                   class="search__target-card<?php echo $card['featured'] ? ' search__target-card--featured' : ''; ?>">
                    <div class="search__target-icon">
                        <?php echo search_get_svg_icon($card['icon']); ?>
                    </div>
                    <div class="search__target-content">
                        <h4 class="search__target-title"><?php echo esc_html($card['title']); ?></h4>
                        <p class="search__target-desc"><?php echo esc_html($card['description']); ?></p>
                    </div>
                    <span class="search__target-arrow">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M7 4l6 6-6 6"/>
                        </svg>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
            
        </div>
    </div>
    
    <!-- ========================================
         用途・目的から探す（カテゴリーグループ）
         ======================================== -->
    <div class="search__section">
        <div class="search__container">
            
            <header class="search__section-header">
                <p class="search__section-sub">BROWSE BY PURPOSE</p>
                <h3 class="search__section-title">用途・目的から探す</h3>
            </header>
            
            <div class="search__category-groups">
                <?php foreach ($category_groups as $group): ?>
                <?php if (!empty($group['categories'])): ?>
                <article class="search__category-card">
                    <header class="search__category-card-header">
                        <span class="search__category-card-icon">
                            <?php echo search_get_svg_icon($group['icon']); ?>
                        </span>
                        <div class="search__category-card-titles">
                            <h4 class="search__category-card-name"><?php echo esc_html($group['name']); ?></h4>
                            <span class="search__category-card-name-en"><?php echo esc_html($group['name_en']); ?></span>
                        </div>
                    </header>
                    <ul class="search__category-list">
                        <?php foreach ($group['categories'] as $cat): ?>
                        <li class="search__category-item">
                            <a href="<?php echo esc_url(get_term_link($cat)); ?>" class="search__category-link">
                                <span class="search__category-name"><?php echo esc_html($cat->name); ?></span>
                                <span class="search__category-count"><?php echo esc_html($cat->count); ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </article>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
        </div>
    </div>
    
    <!-- ========================================
         人気カテゴリから探す
         ======================================== -->
    <div class="search__section search__section--alt">
        <div class="search__container">
            
            <header class="search__section-header">
                <p class="search__section-sub">POPULAR CATEGORIES</p>
                <h3 class="search__section-title">人気カテゴリから探す</h3>
            </header>
            
            <!-- フィルター入力 -->
            <div class="search__filter-box">
                <input 
                    type="text" 
                    id="category-filter-input" 
                    class="search__filter-input" 
                    placeholder="カテゴリをキーワードで絞り込む..."
                    aria-label="カテゴリを絞り込む">
            </div>
            
            <!-- 人気カテゴリ（常に表示） -->
            <div class="search__pill-grid" id="popular-categories-grid">
                <?php foreach ($popular_categories as $cat): ?>
                <a href="<?php echo esc_url(get_term_link($cat)); ?>" 
                   class="search__pill" 
                   data-name="<?php echo esc_attr($cat->name); ?>">
                    <?php echo search_get_svg_icon('folder'); ?>
                    <span class="search__pill-text"><?php echo esc_html($cat->name); ?></span>
                    <span class="search__pill-count"><?php echo esc_html($cat->count); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            
            <!-- すべてのカテゴリ（折りたたみ） -->
            <?php if (count($all_categories) > 10): ?>
            <div class="search__collapse">
                <button type="button" id="all-categories-toggle" class="search__collapse-btn" aria-expanded="false">
                    <span>すべてのカテゴリを見る</span>
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M3 4.5l3 3 3-3"/>
                    </svg>
                </button>
                <div id="all-categories-content" class="search__collapse-content" hidden>
                    <div class="search__pill-grid" id="all-categories-grid">
                        <?php foreach ($all_categories as $cat): ?>
                        <a href="<?php echo esc_url(get_term_link($cat)); ?>" 
                           class="search__pill" 
                           data-name="<?php echo esc_attr($cat->name); ?>">
                            <span class="search__pill-text"><?php echo esc_html($cat->name); ?></span>
                            <span class="search__pill-count"><?php echo esc_html($cat->count); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <p id="no-categories-msg" class="search__no-result" style="display: none;">
                    一致するカテゴリがありません
                </p>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
    
    <!-- ========================================
         都道府県から探す
         ======================================== -->
    <div class="search__section">
        <div class="search__container">
            
            <header class="search__section-header">
                <p class="search__section-sub">BROWSE BY REGION</p>
                <h3 class="search__section-title">都道府県から探す</h3>
            </header>
            
            <div class="search__region-grid">
                <?php foreach ($region_groups as $region): ?>
                <article class="search__region-card">
                    <header class="search__region-header">
                        <h4 class="search__region-name"><?php echo esc_html($region['name']); ?></h4>
                        <span class="search__region-name-en"><?php echo esc_html($region['name_en']); ?></span>
                    </header>
                    <div class="search__region-prefs">
                        <?php foreach ($region['prefectures'] as $pref_name): ?>
                            <?php 
                            $pref_slug = '';
                            foreach ($prefectures as $p) {
                                if ($p['name'] === $pref_name) {
                                    $pref_slug = $p['slug'];
                                    break;
                                }
                            }
                            if ($pref_slug):
                            ?>
                        <a href="<?php echo esc_url(get_term_link($pref_slug, 'grant_prefecture')); ?>" 
                           class="search__region-pref">
                            <?php echo esc_html($pref_name); ?>
                        </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            
        </div>
    </div>
    
    <!-- ========================================
         市町村から探す
         ======================================== -->
    <div class="search__section search__section--alt">
        <div class="search__container">
            
            <header class="search__section-header">
                <p class="search__section-sub">MUNICIPALITY SEARCH</p>
                <h3 class="search__section-title">市町村から探す</h3>
            </header>
            
            <div class="search__municipality-interface">
                
                <div class="search__municipality-control">
                    <label for="municipality-prefecture-filter" class="search__label search__label--center">
                        都道府県を選択してください
                    </label>
                    <div class="search__select-wrap search__select-wrap--lg">
                        <select id="municipality-prefecture-filter" class="search__select search__select--lg">
                            <option value="">都道府県を選択...</option>
                            <?php foreach ($prefectures as $pref): ?>
                            <option value="<?php echo esc_attr($pref['slug']); ?>">
                                <?php echo esc_html($pref['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- ローディング -->
                <div id="municipality-loading" class="search__municipality-loading" style="display: none;">
                    <div class="search__spinner search__spinner--lg"></div>
                    <span>読み込み中...</span>
                </div>
                
                <!-- 市町村リスト -->
                <div id="municipality-list" class="search__municipality-grid">
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
                    </div>
                </div>
                
            </div>
            
        </div>
    </div>
    
    <!-- ========================================
         人気キーワードから探す
         ======================================== -->
    <?php if (!empty($popular_tags)): ?>
    <div class="search__section">
        <div class="search__container">
            
            <header class="search__section-header">
                <p class="search__section-sub">POPULAR KEYWORDS</p>
                <h3 class="search__section-title">人気キーワードから探す</h3>
            </header>
            
            <!-- フィルター入力 -->
            <div class="search__filter-box">
                <input 
                    type="text" 
                    id="tag-filter-input" 
                    class="search__filter-input" 
                    placeholder="タグをキーワードで絞り込む..."
                    aria-label="タグを絞り込む">
            </div>
            
            <!-- 人気タグ（常に表示） -->
            <div class="search__pill-grid" id="popular-tags-grid">
                <?php foreach ($popular_tags as $tag): ?>
                <a href="<?php echo esc_url(home_url('/grants/?grant_tag=' . $tag->slug)); ?>" 
                   class="search__pill search__pill--tag" 
                   data-name="<?php echo esc_attr($tag->name); ?>">
                    <?php echo search_get_svg_icon('hash'); ?>
                    <span class="search__pill-text"><?php echo esc_html($tag->name); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            
            <!-- すべてのタグ（折りたたみ） -->
            <?php if (count($all_tags) > 10): ?>
            <div class="search__collapse">
                <button type="button" id="all-tags-toggle" class="search__collapse-btn" aria-expanded="false">
                    <span>すべてのタグを見る</span>
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M3 4.5l3 3 3-3"/>
                    </svg>
                </button>
                <div id="all-tags-content" class="search__collapse-content" hidden>
                    <div class="search__pill-grid" id="all-tags-grid">
                        <?php foreach ($all_tags as $tag): ?>
                        <a href="<?php echo esc_url(home_url('/grants/?grant_tag=' . $tag->slug)); ?>" 
                           class="search__pill search__pill--tag" 
                           data-name="<?php echo esc_attr($tag->name); ?>">
                            <span class="search__pill-text"><?php echo esc_html($tag->name); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <p id="no-tags-msg" class="search__no-result" style="display: none;">
                    一致するタグがありません
                </p>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
    <?php endif; ?>
    
    <!-- ========================================
         信頼性バー（E-E-A-T）
         ======================================== -->
    <div class="search__trust-bar">
        <div class="search__container">
            <p class="search__trust-text">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M8 1l2 4 4.5.5-3.25 3 .75 4.5L8 11l-4 2 .75-4.5L1.5 5.5 6 5l2-4z"/>
                </svg>
                <span>当サイトの情報は各省庁・自治体の公表データに基づき、専門家監修のもと更新されています。</span>
            </p>
        </div>
    </div>
    
    <!-- ========================================
         CTA セクション（条件付き表示）
         ======================================== -->
    <?php if (!get_query_var('exclude_cta')): ?>
    <div class="search__cta">
        <div class="search__container">
            <div class="search__cta-card">
                <div class="search__cta-icon">
                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                        <rect x="8" y="6" width="32" height="36" rx="2" stroke="currentColor" stroke-width="2"/>
                        <path d="M16 18h16M16 26h12M16 34h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="36" cy="36" r="10" fill="currentColor"/>
                        <path d="M33 36l2 2 4-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="search__cta-title">あなたに最適な補助金を無料診断</h3>
                <p class="search__cta-desc">
                    簡単な質問に答えるだけで、あなたの事業に最適な補助金・助成金を診断します。<br class="search__cta-br">
                    診断は完全無料、所要時間はわずか3分です。
                </p>
                <div class="search__cta-btns">
                    <a href="<?php echo esc_url(home_url('/grants/')); ?>" class="search__cta-btn search__cta-btn--secondary">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <circle cx="8" cy="8" r="5"/>
                            <path d="M12 12l4 4"/>
                        </svg>
                        <span>補助金を探す</span>
                    </a>
                    <a href="https://joseikin-insight.com/subsidy-diagnosis/" class="search__cta-btn search__cta-btn--primary">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M4 9h10M10 5l4 4-4 4"/>
                        </svg>
                        <span>今すぐ無料診断を始める</span>
                    </a>
                </div>
                <p class="search__cta-note">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <circle cx="7" cy="7" r="5"/>
                        <path d="M7 5v2M7 9v.5"/>
                    </svg>
                    <span>会員登録不要・メールアドレス不要</span>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
</section>

<style>
/* ==========================================================================
   Search Section v53.0 - Complete Styles
   ========================================================================== */

.search {
    --s-black: #111111;
    --s-white: #ffffff;
    --s-gray-900: #1a1a1a;
    --s-gray-800: #2a2a2a;
    --s-gray-700: #404040;
    --s-gray-600: #555555;
    --s-gray-500: #666666;
    --s-gray-400: #888888;
    --s-gray-300: #aaaaaa;
    --s-gray-200: #cccccc;
    --s-gray-100: #e5e5e5;
    --s-gray-50: #f5f5f5;
    --s-bg: #fafafa;
    
    --s-font: 'Noto Sans JP', -apple-system, BlinkMacSystemFont, sans-serif;
    --s-font-mono: 'SF Mono', 'Monaco', 'Consolas', monospace;
    
    --s-transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    --s-shadow: 0 1px 3px rgba(0,0,0,0.08);
    --s-shadow-md: 0 4px 12px rgba(0,0,0,0.1);
    --s-shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
    --s-radius: 4px;
    --s-radius-lg: 8px;
    
    font-family: var(--s-font);
    color: var(--s-black);
    line-height: 1.6;
}

.search__container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}

@media (max-width: 640px) {
    .search__container { padding: 0 16px; }
}

/* --------------------------------------------------------------------------
   Stats Bar
   -------------------------------------------------------------------------- */
.search__stats-bar {
    background: var(--s-black);
    color: var(--s-white);
    padding: 12px 0;
}

.search__stats-inner {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 24px;
    font-size: 13px;
}

.search__stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.search__stat-label {
    font-family: var(--s-font-mono);
    font-weight: 700;
    font-size: 11px;
    color: var(--s-gray-400);
}

.search__stat-value {
    font-family: var(--s-font-mono);
    font-weight: 900;
    font-size: 16px;
    color: #FFD700;
}

.search__stat-unit,
.search__stat-date {
    font-size: 12px;
    color: var(--s-gray-300);
}

.search__stat-divider {
    width: 1px;
    height: 16px;
    background: var(--s-gray-700);
}

/* --------------------------------------------------------------------------
   Main Search Form
   -------------------------------------------------------------------------- */
.search__main {
    background: var(--s-white);
    padding: 80px 0;
}

.search__header {
    text-align: center;
    margin-bottom: 48px;
}

.search__header-sub {
    font-family: var(--s-font-mono);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.15em;
    color: var(--s-gray-500);
    margin: 0 0 12px 0;
}

.search__header-title {
    font-size: clamp(24px, 5vw, 32px);
    font-weight: 900;
    color: var(--s-black);
    margin: 0;
    letter-spacing: -0.02em;
}

.search__form {
    max-width: 900px;
    margin: 0 auto;
}

.search__form-row {
    margin-bottom: 24px;
}

.search__form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 24px;
    margin-bottom: 24px;
}

.search__field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.search__label {
    font-size: 14px;
    font-weight: 700;
    color: var(--s-gray-700);
}

.search__label--center {
    text-align: center;
}

/* Radio Group */
.search__radio-group {
    display: flex;
    gap: 12px;
}

.search__radio {
    flex: 1;
    cursor: pointer;
}

.search__radio-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.search__radio-label {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 48px;
    background: var(--s-white);
    border: 1px solid var(--s-gray-200);
    border-radius: var(--s-radius);
    font-size: 14px;
    font-weight: 600;
    color: var(--s-gray-700);
    transition: all var(--s-transition);
}

.search__radio-input:checked + .search__radio-label {
    background: var(--s-black);
    border-color: var(--s-black);
    color: var(--s-white);
}

.search__radio-label:hover {
    border-color: var(--s-gray-400);
}

.search__radio-input:focus-visible + .search__radio-label {
    outline: 3px solid var(--s-black);
    outline-offset: 2px;
}

/* Select */
.search__select-wrap {
    position: relative;
}

.search__select-wrap::after {
    content: '';
    position: absolute;
    right: 16px;
    top: 50%;
    border: 5px solid transparent;
    border-top-color: var(--s-black);
    transform: translateY(-25%);
    pointer-events: none;
}

.search__select {
    width: 100%;
    height: 56px;
    padding: 0 40px 0 16px;
    background: var(--s-white);
    border: 1px solid var(--s-gray-200);
    border-radius: var(--s-radius);
    font-family: var(--s-font);
    font-size: 15px;
    color: var(--s-black);
    cursor: pointer;
    appearance: none;
    transition: border-color var(--s-transition), box-shadow var(--s-transition);
}

.search__select:hover {
    border-color: var(--s-gray-400);
}

.search__select:focus {
    outline: none;
    border-color: var(--s-black);
    box-shadow: 0 0 0 3px rgba(17,17,17,0.1);
}

.search__select--lg {
    height: 64px;
    font-size: 16px;
    font-weight: 700;
}

.search__select-wrap--lg {
    max-width: 400px;
    margin: 0 auto;
}

/* Spinner */
.search__spinner {
    width: 20px;
    height: 20px;
    border: 2px solid var(--s-gray-200);
    border-top-color: var(--s-black);
    border-radius: 50%;
    animation: searchSpin 0.8s linear infinite;
    position: absolute;
    right: 40px;
    top: 50%;
    transform: translateY(-50%);
}

.search__spinner--lg {
    width: 32px;
    height: 32px;
    border-width: 3px;
    position: static;
    transform: none;
}

@keyframes searchSpin {
    to { transform: rotate(360deg); }
}

/* Input */
.search__input {
    width: 100%;
    height: 56px;
    padding: 0 16px;
    background: var(--s-gray-50);
    border: 1px solid var(--s-gray-200);
    border-radius: var(--s-radius);
    font-family: var(--s-font);
    font-size: 15px;
    color: var(--s-black);
    transition: all var(--s-transition);
}

.search__input::placeholder {
    color: var(--s-gray-400);
}

.search__input:hover {
    background: var(--s-white);
    border-color: var(--s-gray-400);
}

.search__input:focus {
    outline: none;
    background: var(--s-white);
    border-color: var(--s-black);
    box-shadow: 0 0 0 3px rgba(17,17,17,0.1);
}

/* Actions */
.search__actions {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 16px;
    margin: 32px 0 24px;
}

@media (max-width: 480px) {
    .search__actions {
        grid-template-columns: 1fr;
    }
}

.search__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 56px;
    padding: 0 32px;
    border-radius: var(--s-radius);
    font-family: var(--s-font);
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all var(--s-transition);
    border: none;
}

.search__btn--outline {
    background: transparent;
    border: 1px solid var(--s-gray-200);
    color: var(--s-gray-700);
}

.search__btn--outline:hover {
    background: var(--s-gray-50);
    border-color: var(--s-gray-400);
}

.search__btn--solid {
    background: var(--s-black);
    color: var(--s-white);
}

.search__btn--solid:hover {
    background: var(--s-gray-900);
    transform: translateY(-2px);
    box-shadow: var(--s-shadow-md);
}

.search__btn:focus-visible {
    outline: 3px solid var(--s-black);
    outline-offset: 2px;
}

/* Sub Links */
.search__sub-links {
    display: flex;
    justify-content: center;
    gap: 24px;
    flex-wrap: wrap;
}

.search__sub-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--s-gray-600);
    text-decoration: none;
    transition: color var(--s-transition);
}

.search__sub-link:hover {
    color: var(--s-black);
}

/* --------------------------------------------------------------------------
   Sections
   -------------------------------------------------------------------------- */
.search__section {
    padding: 80px 0;
}

.search__section--alt {
    background: var(--s-gray-50);
}

.search__section-header {
    text-align: center;
    margin-bottom: 48px;
}

.search__section-sub {
    font-family: var(--s-font-mono);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.15em;
    color: var(--s-gray-500);
    margin: 0 0 8px 0;
}

.search__section-title {
    font-size: clamp(20px, 4vw, 24px);
    font-weight: 900;
    color: var(--s-black);
    margin: 0;
}

/* --------------------------------------------------------------------------
   Target Cards
   -------------------------------------------------------------------------- */
.search__target-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.search__target-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 24px;
    background: var(--s-white);
    border: 1px solid var(--s-gray-100);
    border-radius: var(--s-radius-lg);
    text-decoration: none;
    color: var(--s-black);
    transition: all var(--s-transition);
}

.search__target-card:hover {
    border-color: var(--s-black);
    transform: translateY(-4px);
    box-shadow: var(--s-shadow-lg);
}

.search__target-card--featured {
    border-left: 4px solid var(--s-black);
}

.search__target-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--s-gray-50);
    border-radius: 50%;
    color: var(--s-gray-700);
    transition: all var(--s-transition);
}

.search__target-card:hover .search__target-icon {
    background: var(--s-black);
    color: var(--s-white);
}

.search__target-content {
    flex: 1;
    min-width: 0;
}

.search__target-title {
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 4px 0;
}

.search__target-desc {
    font-size: 13px;
    color: var(--s-gray-600);
    margin: 0;
}

.search__target-arrow {
    flex-shrink: 0;
    color: var(--s-gray-300);
    transition: all var(--s-transition);
}

.search__target-card:hover .search__target-arrow {
    color: var(--s-black);
    transform: translateX(4px);
}

/* --------------------------------------------------------------------------
   Category Groups (用途・目的)
   -------------------------------------------------------------------------- */
.search__category-groups {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 24px;
}

.search__category-card {
    background: var(--s-white);
    border: 1px solid var(--s-gray-100);
    border-radius: var(--s-radius-lg);
    overflow: hidden;
    transition: border-color var(--s-transition);
}

.search__category-card:hover {
    border-color: var(--s-gray-300);
}

.search__category-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px;
    border-bottom: 2px solid var(--s-black);
}

.search__category-card-icon {
    flex-shrink: 0;
    color: var(--s-gray-600);
}

.search__category-card-titles {
    flex: 1;
}

.search__category-card-name {
    font-size: 18px;
    font-weight: 700;
    margin: 0;
    line-height: 1.3;
}

.search__category-card-name-en {
    font-family: var(--s-font-mono);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: var(--s-gray-400);
}

.search__category-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.search__category-item {
    border-bottom: 1px solid var(--s-gray-50);
}

.search__category-item:last-child {
    border-bottom: none;
}

.search__category-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    text-decoration: none;
    color: var(--s-gray-700);
    font-size: 14px;
    font-weight: 500;
    transition: all var(--s-transition);
}

.search__category-link:hover {
    background: var(--s-gray-50);
    padding-left: 24px;
    color: var(--s-black);
}

.search__category-count {
    font-family: var(--s-font-mono);
    font-size: 12px;
    color: var(--s-gray-400);
}

/* --------------------------------------------------------------------------
   Pills & Filter (人気カテゴリ・タグ)
   -------------------------------------------------------------------------- */
.search__filter-box {
    max-width: 500px;
    margin: 0 auto 40px;
}

.search__filter-input {
    width: 100%;
    height: 50px;
    padding: 0 24px;
    background: var(--s-white);
    border: 1px solid var(--s-gray-200);
    border-radius: 25px;
    font-family: var(--s-font);
    font-size: 14px;
    text-align: center;
    transition: all var(--s-transition);
}

.search__filter-input:focus {
    outline: none;
    border-color: var(--s-black);
    box-shadow: 0 0 0 3px rgba(17,17,17,0.1);
}

.search__pill-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}

.search__pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background: var(--s-white);
    border: 1px solid var(--s-gray-200);
    border-radius: var(--s-radius);
    font-size: 13px;
    font-weight: 700;
    color: var(--s-gray-700);
    text-decoration: none;
    transition: all var(--s-transition);
}

.search__pill:hover {
    background: var(--s-black);
    border-color: var(--s-black);
    color: var(--s-white);
}

.search__pill-text {
    font-weight: 600;
}

.search__pill-count {
    font-family: var(--s-font-mono);
    font-size: 11px;
    opacity: 0.7;
}

.search__pill--tag {
    background: var(--s-gray-50);
    border-color: var(--s-gray-100);
}

/* Collapse */
.search__collapse {
    text-align: center;
    margin-top: 32px;
}

.search__collapse-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
    background: none;
    border: none;
    border-bottom: 1px solid var(--s-black);
    font-family: var(--s-font);
    font-size: 13px;
    font-weight: 700;
    color: var(--s-black);
    cursor: pointer;
    transition: opacity var(--s-transition);
}

.search__collapse-btn:hover {
    opacity: 0.7;
}

.search__collapse-btn[aria-expanded="true"] svg {
    transform: rotate(180deg);
}

.search__collapse-content {
    margin-top: 24px;
    animation: searchSlideDown 0.3s ease-out;
}

@keyframes searchSlideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.search__no-result {
    text-align: center;
    padding: 24px;
    color: var(--s-gray-500);
    font-size: 14px;
}

/* --------------------------------------------------------------------------
   Region Grid
   -------------------------------------------------------------------------- */
.search__region-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 20px;
}

.search__region-card {
    background: var(--s-white);
    border: 1px solid var(--s-gray-100);
    border-radius: var(--s-radius-lg);
    padding: 20px;
    transition: border-color var(--s-transition);
}

.search__region-card:hover {
    border-color: var(--s-gray-300);
}

.search__region-header {
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--s-gray-100);
}

.search__region-name {
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 4px 0;
}

.search__region-name-en {
    font-family: var(--s-font-mono);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: var(--s-gray-400);
}

.search__region-prefs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.search__region-pref {
    padding: 6px 12px;
    background: var(--s-gray-50);
    border-radius: var(--s-radius);
    font-size: 13px;
    color: var(--s-gray-700);
    text-decoration: none;
    transition: all var(--s-transition);
}

.search__region-pref:hover {
    background: var(--s-black);
    color: var(--s-white);
}

/* --------------------------------------------------------------------------
   Municipality Search
   -------------------------------------------------------------------------- */
.search__municipality-interface {
    max-width: 800px;
    margin: 0 auto;
}

.search__municipality-control {
    margin-bottom: 32px;
    text-align: center;
}

.search__municipality-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 40px;
    color: var(--s-gray-500);
    font-size: 14px;
}

.search__municipality-grid {
    min-height: 200px;
}

.search__municipality-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px 24px;
    background: var(--s-white);
    border: 2px dashed var(--s-gray-200);
    border-radius: var(--s-radius-lg);
    text-align: center;
}

.search__placeholder-icon {
    color: var(--s-gray-300);
    margin-bottom: 16px;
}

.search__placeholder-text {
    font-size: 14px;
    color: var(--s-gray-500);
    line-height: 1.6;
    margin: 0;
}

.search__municipality-links {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
}

.search__municipality-link {
    display: block;
    padding: 12px;
    background: var(--s-white);
    border: 1px solid var(--s-gray-100);
    border-radius: var(--s-radius);
    font-size: 13px;
    color: var(--s-gray-700);
    text-decoration: none;
    text-align: center;
    transition: all var(--s-transition);
}

.search__municipality-link:hover {
    background: var(--s-black);
    border-color: var(--s-black);
    color: var(--s-white);
}

/* --------------------------------------------------------------------------
   Trust Bar
   -------------------------------------------------------------------------- */
.search__trust-bar {
    background: var(--s-gray-50);
    padding: 20px 0;
    border-top: 1px solid var(--s-gray-100);
}

.search__trust-text {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 12px;
    color: var(--s-gray-600);
    margin: 0;
    text-align: center;
    line-height: 1.6;
}

.search__trust-text svg {
    flex-shrink: 0;
    color: var(--s-gray-500);
}

@media (max-width: 640px) {
    .search__trust-text {
        flex-direction: column;
        gap: 8px;
    }
}

/* --------------------------------------------------------------------------
   CTA Section
   -------------------------------------------------------------------------- */
.search__cta {
    background: var(--s-black);
    padding: 100px 0;
}

.search__cta-card {
    max-width: 700px;
    margin: 0 auto;
    text-align: center;
    color: var(--s-white);
}

.search__cta-icon {
    margin-bottom: 24px;
    color: var(--s-white);
}

.search__cta-title {
    font-size: clamp(24px, 5vw, 36px);
    font-weight: 900;
    margin: 0 0 16px 0;
    letter-spacing: -0.02em;
}

.search__cta-desc {
    font-size: 15px;
    color: var(--s-gray-300);
    margin: 0 0 40px 0;
    line-height: 1.8;
}

.search__cta-br {
    display: none;
}

@media (min-width: 640px) {
    .search__cta-br { display: inline; }
}

.search__cta-btns {
    display: flex;
    justify-content: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.search__cta-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    height: 56px;
    padding: 0 32px;
    border-radius: 50px;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    transition: all var(--s-transition);
    min-width: 220px;
}

.search__cta-btn--primary {
    background: var(--s-white);
    color: var(--s-black);
}

.search__cta-btn--primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(255,255,255,0.2);
}

.search__cta-btn--secondary {
    background: transparent;
    border: 2px solid var(--s-white);
    color: var(--s-white);
}

.search__cta-btn--secondary:hover {
    background: rgba(255,255,255,0.1);
    transform: translateY(-3px);
}

.search__cta-note {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 13px;
    color: var(--s-gray-400);
    margin: 0;
}

@media (max-width: 640px) {
    .search__cta-btns {
        flex-direction: column;
    }
    .search__cta-btn {
        width: 100%;
    }
}

/* --------------------------------------------------------------------------
   Accessibility
   -------------------------------------------------------------------------- */
@media (prefers-reduced-motion: reduce) {
    .search *,
    .search *::before,
    .search *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}

.search a:focus-visible,
.search button:focus-visible,
.search input:focus-visible,
.search select:focus-visible {
    outline: 3px solid var(--s-black);
    outline-offset: 2px;
}

/* Print */
@media print {
    .search__stats-bar,
    .search__cta,
    .search__trust-bar,
    .search__municipality-interface {
        display: none !important;
    }
}
</style>

<script>
(function() {
    'use strict';
    
    const CONFIG = {
        ajaxUrl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
        nonce: '<?php echo esc_js($ajax_nonce); ?>',
        grantsUrl: '<?php echo esc_js(home_url('/grants/')); ?>',
        municipalityUrl: '<?php echo esc_js(home_url('/grant_municipality/')); ?>'
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
            this.setAttribute('aria-expanded', isHidden);
            
            const span = this.querySelector('span');
            if (span) {
                span.textContent = isHidden ? '閉じる' : 'すべて見る';
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
</script>
