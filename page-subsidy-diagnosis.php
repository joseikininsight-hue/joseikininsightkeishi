<?php
/**
 * Template Name: Subsidy Diagnosis Pro (AI診断)
 * Description: RAG機能を活用した補助金・助成金AI診断 - プロフェッショナル金融LP
 *
 * @package Grant_Insight_Perfect
 * @version 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// チャットシステムの利用可否確認
$has_chat_system = shortcode_exists('gip_chat');

// SEO用メタデータ（サジェストキーワード対応）
$page_title = '【無料診断】補助金・助成金をAIで簡単検索 | 補助金診断ツール';
$page_description = '補助金診断が無料で利用可能。AIが事業内容をヒアリングし、申請可能な補助金・助成金を自動で検索。中小企業・個人事業主の資金調達をサポートする補助金診断システムです。';
?>

<div class="diag-wrapper" itemscope itemtype="https://schema.org/WebPage">

    <!-- ============================================
         SECTION 1: HERO - ヒーローセクション
         ============================================ -->
    <section class="diag-hero" aria-label="メインビジュアル">
        <div class="diag-container">
            <div class="diag-hero__grid">
                <!-- 左側：イラスト/画像 -->
                <div class="diag-hero__visual">
                    <div class="diag-hero__image-wrapper">
                        <img 
                            src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/hero-building.jpg" 
                            alt="補助金診断 - ビジネスと資金調達のイメージ" 
                            class="diag-hero__image"
                            width="500"
                            height="400"
                            loading="eager"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                        >
                        <!-- フォールバック用SVGイラスト -->
                        <div class="diag-hero__svg-fallback" style="display:none;">
                            <svg width="100%" height="100%" viewBox="0 0 500 400" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="ビルと資金調達のイラスト">
                                <g class="building-main">
                                    <path d="M150 320 L150 120 L250 60 L350 120 L350 320 Z" fill="#fff" stroke="#111" stroke-width="2.5"/>
                                    <path d="M150 120 L250 180 L350 120" stroke="#111" stroke-width="2"/>
                                    <path d="M250 60 L250 180" stroke="#111" stroke-width="2"/>
                                    <g fill="#f5f5f5" stroke="#111" stroke-width="1">
                                        <rect x="170" y="140" width="25" height="30"/>
                                        <rect x="170" y="190" width="25" height="30"/>
                                        <rect x="170" y="240" width="25" height="30"/>
                                        <rect x="210" y="140" width="25" height="30"/>
                                        <rect x="210" y="190" width="25" height="30"/>
                                        <rect x="210" y="240" width="25" height="30"/>
                                    </g>
                                    <g fill="#e8e8e8" stroke="#111" stroke-width="1">
                                        <path d="M270 140 L295 125 L295 155 L270 170 Z"/>
                                        <path d="M270 190 L295 175 L295 205 L270 220 Z"/>
                                        <path d="M270 240 L295 225 L295 255 L270 270 Z"/>
                                        <path d="M310 125 L335 110 L335 140 L310 155 Z"/>
                                        <path d="M310 175 L335 160 L335 190 L310 205 Z"/>
                                        <path d="M310 225 L335 210 L335 240 L310 255 Z"/>
                                    </g>
                                </g>
                                <g class="building-small">
                                    <path d="M80 320 L80 200 L120 180 L160 200 L160 320 Z" fill="#fff" stroke="#111" stroke-width="2"/>
                                    <rect x="95" y="220" width="20" height="25" fill="#f0f0f0" stroke="#111" stroke-width="1"/>
                                    <rect x="95" y="260" width="20" height="25" fill="#f0f0f0" stroke="#111" stroke-width="1"/>
                                </g>
                                <g class="coins" transform="translate(360, 250)">
                                    <ellipse cx="40" cy="15" rx="35" ry="12" fill="#fff" stroke="#111" stroke-width="2"/>
                                    <path d="M5 15 L5 55 A35 12 0 0 0 75 55 L75 15" fill="#fff" stroke="#111" stroke-width="2"/>
                                    <ellipse cx="40" cy="55" rx="35" ry="12" fill="none" stroke="#111" stroke-width="2"/>
                                    <path d="M5 35 A35 12 0 0 0 75 35" stroke="#111" stroke-width="1.5" fill="none"/>
                                    <text x="32" y="42" font-family="serif" font-size="24" font-weight="bold" fill="#111">¥</text>
                                </g>
                                <g class="arrow-up">
                                    <path d="M420 180 L440 120 L460 180" stroke="#111" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                    <line x1="440" y1="120" x2="440" y2="200" stroke="#111" stroke-width="3" stroke-linecap="round"/>
                                </g>
                                <g class="ai-badge" transform="translate(400, 40)">
                                    <circle cx="30" cy="30" r="28" fill="#fff" stroke="#111" stroke-width="2" stroke-dasharray="4,2"/>
                                    <text x="30" y="36" font-family="Arial, sans-serif" font-size="18" font-weight="bold" fill="#111" text-anchor="middle">AI</text>
                                </g>
                                <line x1="50" y1="320" x2="480" y2="320" stroke="#111" stroke-width="2"/>
                                <g class="person" transform="translate(60, 270)">
                                    <circle cx="15" cy="10" r="8" fill="#111"/>
                                    <path d="M15 18 L15 40 M5 50 L15 40 L25 50 M8 28 L22 28" stroke="#111" stroke-width="2" stroke-linecap="round"/>
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- 右側：テキストコンテンツ -->
                <div class="diag-hero__content">
                    <div class="diag-hero__badge">
                        <svg class="diag-hero__badge-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                            <line x1="12" y1="22.08" x2="12" y2="12"/>
                        </svg>
                        <span>補助金・助成金</span>
                    </div>
                    
                    <h1 class="diag-hero__title" itemprop="name">
                        補助金・助成金<br>
                        <span class="diag-hero__title-large">AI無料診断</span>
                    </h1>
                    
                    <div class="diag-hero__description">
                        <p>
                            事業内容をAIがヒアリングし、<br>
                            申請可能な補助金・助成金を自動で検索。<br>
                            <span class="diag-hero__highlight">会員登録不要・完全無料</span>で今すぐ診断できます。
                        </p>
                    </div>
                    
                    <div class="diag-hero__cta">
                        <a href="#diagnosis-app" class="diag-btn diag-btn--primary smooth-scroll" aria-label="AI診断を開始する">
                            <span>診断を始める</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <polyline points="19 12 12 19 5 12"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         SECTION 2: FLOW - 診断の流れ
         ============================================ -->
    <section class="diag-section diag-flow-section" aria-labelledby="flow-title">
        <div class="diag-container">
            <header class="diag-section__header">
                <h2 id="flow-title" class="diag-section__title">診断の流れ</h2>
                <p class="diag-section__subtitle">3つのステップで申請可能な補助金を検索</p>
            </header>

            <div class="diag-flow" role="list">
                <!-- Step 1 -->
                <article class="diag-flow__item" role="listitem">
                    <figure class="diag-flow__image">
                        <img 
                            src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/step-hearing.jpg" 
                            alt="AIチャットによるヒアリング - 補助金診断ツール" 
                            width="400" 
                            height="300"
                            loading="lazy"
                            onerror="this.src='https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=400&q=80&sat=-100'"
                        >
                        <div class="diag-flow__image-overlay">
                            <span class="diag-flow__step-number">01</span>
                        </div>
                    </figure>
                    <div class="diag-flow__content">
                        <span class="diag-flow__step">STEP 01</span>
                        <h3 class="diag-flow__title">ヒアリング</h3>
                        <p class="diag-flow__text">
                            AIアシスタントがチャット形式で<br>
                            事業内容や課題について質問。<br>
                            選択肢をタップするだけで簡単回答。
                        </p>
                    </div>
                </article>

                <!-- Step 2 -->
                <article class="diag-flow__item" role="listitem">
                    <figure class="diag-flow__image">
                        <img 
                            src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/step-analysis.jpg" 
                            alt="AIによる補助金データベース検索" 
                            width="400" 
                            height="300"
                            loading="lazy"
                            onerror="this.src='https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=400&q=80&sat=-100'"
                        >
                        <div class="diag-flow__image-overlay">
                            <span class="diag-flow__step-number">02</span>
                        </div>
                    </figure>
                    <div class="diag-flow__content">
                        <span class="diag-flow__step">STEP 02</span>
                        <h3 class="diag-flow__title">AI検索</h3>
                        <p class="diag-flow__text">
                            回答内容をもとにAIが<br>
                            補助金・助成金データベースを検索。<br>
                            条件に合う制度を自動抽出。
                        </p>
                    </div>
                </article>

                <!-- Step 3 -->
                <article class="diag-flow__item" role="listitem">
                    <figure class="diag-flow__image">
                        <img 
                            src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/step-result.jpg" 
                            alt="補助金診断結果の表示画面" 
                            width="400" 
                            height="300"
                            loading="lazy"
                            onerror="this.src='https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=400&q=80&sat=-100'"
                        >
                        <div class="diag-flow__image-overlay">
                            <span class="diag-flow__step-number">03</span>
                        </div>
                    </figure>
                    <div class="diag-flow__content">
                        <span class="diag-flow__step">STEP 03</span>
                        <h3 class="diag-flow__title">結果表示</h3>
                        <p class="diag-flow__text">
                            条件に合う補助金・助成金を<br>
                            一覧で表示。<br>
                            詳細情報もその場で確認可能。
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- ============================================
         SECTION 3: AI CHAT - 診断アプリ本体（拡大版）
         ============================================ -->
    <section id="diagnosis-app" class="diag-section diag-app-section" aria-labelledby="app-title">
        <div class="diag-container diag-container--wide">
            <div class="diag-chat-wrapper">
                <!-- チャットカードヘッダー -->
                <header class="diag-chat-header">
                    <div class="diag-chat-profile">
                        <div class="diag-chat-avatar" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2a10 10 0 0 1 10 10 10 10 0 0 1-10 10A10 10 0 0 1 2 12 10 10 0 0 1 12 2z"/>
                                <path d="M12 8v8M8 12h8"/>
                            </svg>
                        </div>
                        <div class="diag-chat-info">
                            <h2 id="app-title" class="diag-chat-name">補助金診断アシスタント</h2>
                            <p class="diag-chat-status">
                                <span class="diag-status-dot" aria-hidden="true"></span>
                                オンライン対応中
                            </p>
                        </div>
                    </div>
                    <div class="diag-chat-badges">
                        <span class="diag-badge">無料</span>
                        <span class="diag-badge">24時間対応</span>
                    </div>
                </header>

                <!-- チャット本体（拡大） -->
                <div class="diag-chat-body">
                    <?php if ($has_chat_system): ?>
                        <?php 
                        echo do_shortcode('[gip_chat 
                            title="補助金診断アシスタント" 
                            subtitle="AIがお悩みをお伺いします"
                            placeholder="ご質問やお悩みを入力してください..."
                        ]'); 
                        ?>
                    <?php else: ?>
                        <!-- デモ表示（システム未有効時） -->
                        <div class="diag-chat-demo">
                            <div class="diag-message diag-message--bot">
                                <div class="diag-message__avatar">AI</div>
                                <div class="diag-message__content">
                                    <div class="diag-message__bubble">
                                        <p>こんにちは。補助金・助成金の診断アシスタントです。</p>
                                        <p>あなたの事業に申請可能な制度をお探しします。</p>
                                        <p style="margin-top: 16px;"><strong>まず、どのような目的で補助金・助成金を利用したいですか？</strong></p>
                                    </div>
                                    <div class="diag-message__options">
                                        <button type="button" class="diag-option-btn">事業拡大・設備投資</button>
                                        <button type="button" class="diag-option-btn">IT導入・DX推進</button>
                                        <button type="button" class="diag-option-btn">人材採用・育成</button>
                                        <button type="button" class="diag-option-btn">創業・起業</button>
                                        <button type="button" class="diag-option-btn">販路開拓・海外展開</button>
                                        <button type="button" class="diag-option-btn">研究開発・新製品開発</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- チャット入力エリア -->
                <div class="diag-chat-footer">
                    <div class="diag-chat-input-wrapper">
                        <input 
                            type="text" 
                            class="diag-chat-input" 
                            placeholder="ご質問やお悩みを入力してください..."
                            aria-label="メッセージ入力"
                        >
                        <button type="button" class="diag-chat-send" aria-label="送信">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                        </button>
                    </div>
                    <p class="diag-chat-note">
                        選択肢をタップするか、自由にご質問ください
                    </p>
                </div>
            </div>

            <!-- 利用規約ボタン -->
            <div class="diag-terms-trigger">
                <button type="button" class="diag-terms-btn" id="openTermsModal" aria-haspopup="dialog">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    <span>ご利用規約・免責事項</span>
                </button>
                <p class="diag-terms-note">本サービスのご利用により、利用規約に同意したものとみなします。</p>
            </div>
        </div>
    </section>

    <!-- ============================================
         SECTION 4: FEATURES - システムの特長
         ============================================ -->
    <section class="diag-section diag-features-section" aria-labelledby="features-title">
        <div class="diag-container">
            <header class="diag-section__header diag-section__header--center">
                <h2 id="features-title" class="diag-section__title">補助金診断ツールの特長</h2>
                <p class="diag-section__subtitle">AIによる補助金・助成金検索システム</p>
            </header>

            <div class="diag-features-grid">
                <article class="diag-feature-card">
                    <div class="diag-feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                    <h3 class="diag-feature-title">即時検索</h3>
                    <p class="diag-feature-text">
                        回答内容に基づき<br>
                        データベースを即座に検索
                    </p>
                </article>

                <article class="diag-feature-card">
                    <div class="diag-feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        </svg>
                    </div>
                    <h3 class="diag-feature-title">AI解析</h3>
                    <p class="diag-feature-text">
                        事業内容を理解し<br>
                        適切な制度を抽出
                    </p>
                </article>

                <article class="diag-feature-card">
                    <div class="diag-feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </div>
                    <h3 class="diag-feature-title">詳細表示</h3>
                    <p class="diag-feature-text">
                        申請要件・金額・期限<br>
                        など必要情報を表示
                    </p>
                </article>

                <article class="diag-feature-card">
                    <div class="diag-feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <h3 class="diag-feature-title">登録不要</h3>
                    <p class="diag-feature-text">
                        会員登録なしで<br>
                        今すぐ利用可能
                    </p>
                </article>

                <article class="diag-feature-card">
                    <div class="diag-feature-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <h3 class="diag-feature-title">完全無料</h3>
                    <p class="diag-feature-text">
                        診断サービスは<br>
                        無料でご利用可能
                    </p>
                </article>
            </div>
        </div>
    </section>

    <!-- ============================================
         SECTION 5: BENEFITS - 活用メリット
         ============================================ -->
    <section class="diag-section diag-benefits-section" aria-labelledby="benefits-title">
        <div class="diag-container">
            <header class="diag-section__header">
                <h2 id="benefits-title" class="diag-section__title">AI診断を活用するメリット</h2>
            </header>

            <div class="diag-benefits-layout">
                <div class="diag-benefits-image">
                    <img 
                        src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/benefits-laptop.jpg" 
                        alt="補助金診断ツールを利用するビジネスパーソン" 
                        width="600" 
                        height="400"
                        loading="lazy"
                        onerror="this.src='https://images.unsplash.com/photo-1497215728101-856f4ea42174?auto=format&fit=crop&w=800&q=80&sat=-100'"
                    >
                </div>
                <div class="diag-benefits-content">
                    <ul class="diag-benefits-list" role="list">
                        <li role="listitem">
                            <span class="diag-benefits-check" aria-hidden="true"></span>
                            <div>
                                <strong>検索時間を短縮</strong>
                                <p>条件に合う補助金をAIが自動で検索</p>
                            </div>
                        </li>
                        <li role="listitem">
                            <span class="diag-benefits-check" aria-hidden="true"></span>
                            <div>
                                <strong>見落としを軽減</strong>
                                <p>複数の制度を横断的に検索</p>
                            </div>
                        </li>
                        <li role="listitem">
                            <span class="diag-benefits-check" aria-hidden="true"></span>
                            <div>
                                <strong>情報を整理して表示</strong>
                                <p>申請要件・金額・期限をまとめて確認</p>
                            </div>
                        </li>
                        <li role="listitem">
                            <span class="diag-benefits-check" aria-hidden="true"></span>
                            <div>
                                <strong>24時間いつでも利用可能</strong>
                                <p>営業時間を気にせず好きな時に診断</p>
                            </div>
                        </li>
                        <li role="listitem">
                            <span class="diag-benefits-check" aria-hidden="true"></span>
                            <div>
                                <strong>専門知識不要で利用可能</strong>
                                <p>チャット形式で誰でも簡単に診断</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         SECTION 6: TARGET - 対象者
         ============================================ -->
    <section class="diag-section diag-target-section" aria-labelledby="target-title">
        <div class="diag-container">
            <header class="diag-section__header">
                <h2 id="target-title" class="diag-section__title">このような方におすすめ</h2>
                <div class="diag-section__line" aria-hidden="true"></div>
            </header>

            <div class="diag-target-grid">
                <article class="diag-target-card">
                    <div class="diag-target-main">
                        <h3 class="diag-target-name">中小企業の経営者</h3>
                        <span class="diag-target-category">法人</span>
                    </div>
                    <div class="diag-target-info">
                        <p>設備投資や事業拡大を検討中の方</p>
                    </div>
                </article>

                <article class="diag-target-card">
                    <div class="diag-target-main">
                        <h3 class="diag-target-name">個人事業主・フリーランス</h3>
                        <span class="diag-target-category">個人</span>
                    </div>
                    <div class="diag-target-info">
                        <p>創業支援や運転資金をお探しの方</p>
                    </div>
                </article>

                <article class="diag-target-card">
                    <div class="diag-target-main">
                        <h3 class="diag-target-name">IT・DX推進担当者</h3>
                        <span class="diag-target-category">担当者</span>
                    </div>
                    <div class="diag-target-info">
                        <p>IT導入補助金などを検討中の方</p>
                    </div>
                </article>

                <article class="diag-target-card">
                    <div class="diag-target-main">
                        <h3 class="diag-target-name">人事・採用担当者</h3>
                        <span class="diag-target-category">担当者</span>
                    </div>
                    <div class="diag-target-info">
                        <p>雇用関連の助成金をお探しの方</p>
                    </div>
                </article>

                <article class="diag-target-card">
                    <div class="diag-target-main">
                        <h3 class="diag-target-name">起業準備中の方</h3>
                        <span class="diag-target-category">創業</span>
                    </div>
                    <div class="diag-target-info">
                        <p>創業・開業に使える制度をお探しの方</p>
                    </div>
                </article>

                <article class="diag-target-card">
                    <div class="diag-target-main">
                        <h3 class="diag-target-name">士業・コンサルタント</h3>
                        <span class="diag-target-category">専門家</span>
                    </div>
                    <div class="diag-target-info">
                        <p>顧客への情報提供ツールとして</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- ============================================
         SECTION 7: CTA - 最終訴求
         ============================================ -->
    <section class="diag-section diag-cta-section" aria-labelledby="cta-title">
        <div class="diag-container">
            <div class="diag-cta-content">
                <h2 id="cta-title" class="diag-cta-title">
                    今すぐ無料で<br class="diag-sp-only">AI診断を始める
                </h2>
                <p class="diag-cta-text">
                    会員登録不要・所要時間約3分<br>
                    あなたの事業に申請可能な補助金を検索します
                </p>
                <a href="#diagnosis-app" class="diag-btn diag-btn--primary diag-btn--large smooth-scroll">
                    <span>診断を開始する</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

</div>

<!-- ============================================
     利用規約モーダル
     ============================================ -->
<div class="diag-modal" id="termsModal" role="dialog" aria-labelledby="termsModalTitle" aria-modal="true" aria-hidden="true">
    <div class="diag-modal__overlay" data-close-modal></div>
    <div class="diag-modal__container">
        <header class="diag-modal__header">
            <h3 id="termsModalTitle" class="diag-modal__title">ご利用規約・免責事項</h3>
            <button type="button" class="diag-modal__close" id="closeTermsModal" aria-label="閉じる">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </header>
        <div class="diag-modal__body">
            <div class="diag-legal-content">
                <div class="diag-legal-block">
                    <h4 class="diag-legal-subtitle">1. サービスの性質について</h4>
                    <ul class="diag-legal-list">
                        <li>本サービスは、AI（人工知能）による自動診断システムであり、補助金・助成金に関する情報提供を目的としています。</li>
                        <li>診断結果は、ユーザーが入力した情報に基づきAIが自動生成したものであり、専門家による個別のアドバイスや助言ではありません。</li>
                        <li>本サービスは情報提供のみを目的としており、特定の補助金・助成金の申請を推奨・勧誘するものではありません。</li>
                    </ul>
                </div>

                <div class="diag-legal-block">
                    <h4 class="diag-legal-subtitle">2. 診断結果の取り扱いについて</h4>
                    <ul class="diag-legal-list">
                        <li><strong>診断結果は参考情報としてご活用ください。</strong>実際の申請にあたっては、必ず各補助金・助成金の公募要領、申請要件等を公式サイトでご確認ください。</li>
                        <li>診断結果に表示された補助金・助成金への申請資格を保証するものではありません。</li>
                        <li>診断結果は採択を保証するものではなく、申請結果について当社は一切の責任を負いません。</li>
                        <li>補助金・助成金の情報は随時変更される可能性があります。最新情報は各省庁・自治体の公式サイトでご確認ください。</li>
                    </ul>
                </div>

                <div class="diag-legal-block">
                    <h4 class="diag-legal-subtitle">3. 情報の正確性について</h4>
                    <ul class="diag-legal-list">
                        <li>当社は診断結果の正確性、完全性、最新性について保証いたしません。</li>
                        <li>AIによる自動生成のため、情報に誤りが含まれる可能性があります。</li>
                        <li>表示される補助金・助成金の金額、申請期限、要件等は変更される場合があります。</li>
                    </ul>
                </div>

                <div class="diag-legal-block">
                    <h4 class="diag-legal-subtitle">4. 免責事項</h4>
                    <ul class="diag-legal-list">
                        <li>本サービスの利用により生じたいかなる損害（直接損害、間接損害、逸失利益、その他の損害を含む）についても、当社は一切の責任を負いません。</li>
                        <li>本サービスの利用に基づく補助金・助成金の申請、不採択、その他の結果について、当社は一切の責任を負いません。</li>
                        <li>システムの不具合、メンテナンス、その他の理由によりサービスが一時的に利用できない場合があります。</li>
                    </ul>
                </div>

                <div class="diag-legal-block">
                    <h4 class="diag-legal-subtitle">5. 入力情報の取り扱いについて</h4>
                    <ul class="diag-legal-list">
                        <li>本サービスで入力された情報は、診断結果の生成およびサービス改善の目的でのみ使用されます。</li>
                        <li>個人を特定できる情報の入力は推奨しておりません。</li>
                        <li>詳細は当社プライバシーポリシーをご確認ください。</li>
                    </ul>
                </div>

                <div class="diag-legal-block">
                    <h4 class="diag-legal-subtitle">6. 推奨事項</h4>
                    <ul class="diag-legal-list">
                        <li>補助金・助成金の申請をご検討の際は、税理士、中小企業診断士、社会保険労務士等の専門家にご相談されることを推奨いたします。</li>
                        <li>各補助金・助成金の詳細については、実施機関の公式サイトまたは相談窓口でご確認ください。</li>
                    </ul>
                </div>
            </div>
        </div>
        <footer class="diag-modal__footer">
            <button type="button" class="diag-btn diag-btn--primary" data-close-modal>
                閉じる
            </button>
        </footer>
    </div>
</div>

<!-- ============================================
     STYLES
     ============================================ -->
<style>
/* ==========================================================================
   CSS Custom Properties (Design Tokens)
   ========================================================================== */
:root {
    --diag-black: #111111;
    --diag-gray-900: #1a1a1a;
    --diag-gray-800: #333333;
    --diag-gray-700: #4d4d4d;
    --diag-gray-600: #666666;
    --diag-gray-500: #808080;
    --diag-gray-400: #999999;
    --diag-gray-300: #b3b3b3;
    --diag-gray-200: #cccccc;
    --diag-gray-100: #e5e5e5;
    --diag-gray-50: #f5f5f5;
    --diag-white: #ffffff;
    
    --diag-font-sans: "Noto Sans JP", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    --diag-font-serif: "Shippori Mincho", "Yu Mincho", "YuMincho", "Hiragino Mincho ProN", serif;
    --diag-font-mono: "SF Mono", "Monaco", "Inconsolata", "Roboto Mono", monospace;
    
    --diag-space-xs: 4px;
    --diag-space-sm: 8px;
    --diag-space-md: 16px;
    --diag-space-lg: 24px;
    --diag-space-xl: 32px;
    --diag-space-2xl: 48px;
    --diag-space-3xl: 64px;
    --diag-space-4xl: 96px;
    
    --diag-container-max: 1000px;
    --diag-container-wide: 1200px;
    --diag-container-padding: 24px;
    
    --diag-transition-fast: 150ms ease;
    --diag-transition-base: 250ms ease;
    --diag-transition-slow: 400ms ease;
    
    --diag-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
    --diag-shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
    --diag-shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.1);
    --diag-shadow-xl: 0 20px 50px rgba(0, 0, 0, 0.15);
}

/* ==========================================================================
   Base Styles
   ========================================================================== */
.diag-wrapper {
    background-color: var(--diag-white);
    color: var(--diag-black);
    font-family: var(--diag-font-sans);
    font-size: 16px;
    line-height: 1.8;
    overflow-x: hidden;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.diag-wrapper *,
.diag-wrapper *::before,
.diag-wrapper *::after {
    box-sizing: border-box;
}

.diag-container {
    max-width: var(--diag-container-max);
    margin: 0 auto;
    padding: 0 var(--diag-container-padding);
}

.diag-container--wide {
    max-width: var(--diag-container-wide);
}

.diag-sp-only {
    display: none;
}

@media (max-width: 768px) {
    .diag-sp-only {
        display: inline;
    }
}

/* ==========================================================================
   Section Base Styles
   ========================================================================== */
.diag-section {
    padding: var(--diag-space-4xl) 0;
}

.diag-section__header {
    margin-bottom: var(--diag-space-3xl);
}

.diag-section__header--center {
    text-align: center;
}

.diag-section__title {
    font-family: var(--diag-font-serif);
    font-size: clamp(24px, 4vw, 32px);
    font-weight: 600;
    line-height: 1.3;
    margin: 0 0 var(--diag-space-md) 0;
    letter-spacing: 0.02em;
}

.diag-section__subtitle {
    font-size: 14px;
    color: var(--diag-gray-600);
    margin: 0;
}

.diag-section__line {
    flex: 1;
    height: 1px;
    background-color: var(--diag-black);
    margin-left: var(--diag-space-lg);
}

/* ==========================================================================
   1. Hero Section
   ========================================================================== */
.diag-hero {
    padding: var(--diag-space-3xl) 0 var(--diag-space-4xl);
    background-color: var(--diag-gray-50);
}

.diag-hero__grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--diag-space-3xl);
    align-items: center;
}

.diag-hero__visual {
    position: relative;
}

.diag-hero__image-wrapper {
    position: relative;
    border: 2px solid var(--diag-black);
    box-shadow: 12px 12px 0 var(--diag-black);
    background-color: var(--diag-white);
    overflow: hidden;
}

.diag-hero__image {
    display: block;
    width: 100%;
    height: auto;
    filter: grayscale(100%);
    transition: filter var(--diag-transition-slow);
}

.diag-hero__image-wrapper:hover .diag-hero__image {
    filter: grayscale(80%);
}

.diag-hero__svg-fallback {
    padding: var(--diag-space-lg);
}

.diag-hero__content {
    padding-left: var(--diag-space-lg);
}

.diag-hero__badge {
    display: inline-flex;
    align-items: center;
    gap: var(--diag-space-sm);
    font-size: 13px;
    font-weight: 500;
    color: var(--diag-gray-700);
    margin-bottom: var(--diag-space-lg);
    padding-bottom: var(--diag-space-sm);
    border-bottom: 2px solid var(--diag-black);
}

.diag-hero__badge-icon {
    width: 20px;
    height: 20px;
}

.diag-hero__title {
    font-family: var(--diag-font-serif);
    font-size: clamp(24px, 4vw, 28px);
    font-weight: 600;
    line-height: 1.3;
    margin: 0 0 var(--diag-space-lg) 0;
    letter-spacing: 0.03em;
}

.diag-hero__title-large {
    display: block;
    font-size: clamp(36px, 6vw, 48px);
    font-weight: 700;
    margin-top: var(--diag-space-sm);
}

.diag-hero__description {
    background-color: rgba(255, 255, 255, 0.8);
    padding: var(--diag-space-lg);
    border-left: 4px solid var(--diag-black);
    margin-bottom: var(--diag-space-xl);
    font-size: 14px;
    line-height: 1.9;
}

.diag-hero__description p {
    margin: 0;
}

.diag-hero__highlight {
    display: inline-block;
    background-color: var(--diag-black);
    color: var(--diag-white);
    padding: 2px 8px;
    font-size: 13px;
    font-weight: 500;
    margin-top: var(--diag-space-sm);
}

/* Button Styles */
.diag-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--diag-space-sm);
    padding: var(--diag-space-md) var(--diag-space-2xl);
    font-family: var(--diag-font-sans);
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    border: 2px solid var(--diag-black);
    cursor: pointer;
    transition: all var(--diag-transition-base);
}

.diag-btn--primary {
    background-color: var(--diag-black);
    color: var(--diag-white);
}

.diag-btn--primary:hover {
    background-color: var(--diag-white);
    color: var(--diag-black);
}

.diag-btn--large {
    padding: var(--diag-space-lg) var(--diag-space-3xl);
    font-size: 17px;
}

.diag-btn svg {
    width: 16px;
    height: 16px;
    transition: transform var(--diag-transition-fast);
}

.diag-btn:hover svg {
    transform: translateY(2px);
}

.diag-btn--primary:hover svg {
    transform: translateX(4px);
}

/* ==========================================================================
   2. Flow Section
   ========================================================================== */
.diag-flow-section {
    background-color: var(--diag-white);
}

.diag-flow {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--diag-space-xl);
}

.diag-flow__item {
    position: relative;
}

.diag-flow__image {
    position: relative;
    margin: 0 0 var(--diag-space-lg) 0;
    overflow: hidden;
    background-color: var(--diag-gray-100);
    aspect-ratio: 4/3;
}

.diag-flow__image img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: grayscale(100%);
    transition: all var(--diag-transition-slow);
}

.diag-flow__item:hover .diag-flow__image img {
    transform: scale(1.05);
    filter: grayscale(70%);
}

.diag-flow__image-overlay {
    position: absolute;
    top: var(--diag-space-md);
    left: var(--diag-space-md);
    background-color: var(--diag-black);
    color: var(--diag-white);
    padding: var(--diag-space-xs) var(--diag-space-md);
}

.diag-flow__step-number {
    font-family: var(--diag-font-mono);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.1em;
}

.diag-flow__content {
    padding: 0 var(--diag-space-sm);
}

.diag-flow__step {
    display: block;
    font-family: var(--diag-font-serif);
    font-size: 12px;
    color: var(--diag-gray-500);
    letter-spacing: 0.15em;
    margin-bottom: var(--diag-space-sm);
}

.diag-flow__title {
    font-family: var(--diag-font-serif);
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 var(--diag-space-md) 0;
}

.diag-flow__text {
    font-size: 13px;
    color: var(--diag-gray-600);
    line-height: 1.7;
    margin: 0;
}

/* ==========================================================================
   3. Chat App Section
   ========================================================================== */
.diag-app-section {
    background-color: var(--diag-gray-50);
    padding: var(--diag-space-3xl) 0 var(--diag-space-4xl);
}

.diag-chat-wrapper {
    border: 1px solid var(--diag-gray-200);
    box-shadow: var(--diag-shadow-xl);
    background-color: var(--diag-white);
    overflow: hidden;
}

.diag-chat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--diag-space-lg) var(--diag-space-xl);
    border-bottom: 1px solid var(--diag-gray-200);
    background-color: var(--diag-white);
}

.diag-chat-profile {
    display: flex;
    align-items: center;
    gap: var(--diag-space-md);
}

.diag-chat-avatar {
    width: 48px;
    height: 48px;
    background-color: var(--diag-black);
    color: var(--diag-white);
    display: flex;
    align-items: center;
    justify-content: center;
}

.diag-chat-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.diag-chat-name {
    font-size: 16px;
    font-weight: 700;
    margin: 0;
}

.diag-chat-status {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--diag-gray-500);
    margin: 0;
}

.diag-status-dot {
    width: 8px;
    height: 8px;
    background-color: var(--diag-gray-600);
    border-radius: 50%;
}

.diag-chat-badges {
    display: flex;
    gap: var(--diag-space-sm);
}

.diag-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 6px 12px;
    background-color: var(--diag-gray-100);
    color: var(--diag-gray-700);
}

.diag-chat-body {
    min-height: 500px;
    max-height: 700px;
    overflow-y: auto;
    padding: var(--diag-space-xl);
    background-color: var(--diag-white);
}

.diag-chat-demo {
    padding: var(--diag-space-md);
}

.diag-message {
    display: flex;
    gap: var(--diag-space-md);
    margin-bottom: var(--diag-space-xl);
}

.diag-message--bot {
    flex-direction: row;
}

.diag-message__avatar {
    width: 40px;
    height: 40px;
    background-color: var(--diag-black);
    color: var(--diag-white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    flex-shrink: 0;
}

.diag-message__content {
    flex: 1;
    max-width: 85%;
}

.diag-message__bubble {
    background-color: var(--diag-gray-50);
    border: 1px solid var(--diag-gray-200);
    padding: var(--diag-space-lg) var(--diag-space-xl);
    font-size: 15px;
    line-height: 1.8;
}

.diag-message__bubble p {
    margin: 0 0 var(--diag-space-sm) 0;
}

.diag-message__bubble p:last-child {
    margin-bottom: 0;
}

.diag-message__options {
    display: flex;
    flex-wrap: wrap;
    gap: var(--diag-space-sm);
    margin-top: var(--diag-space-lg);
}

.diag-option-btn {
    background-color: var(--diag-white);
    border: 1px solid var(--diag-gray-300);
    padding: var(--diag-space-sm) var(--diag-space-lg);
    font-size: 14px;
    font-family: var(--diag-font-sans);
    cursor: pointer;
    transition: all var(--diag-transition-fast);
}

.diag-option-btn:hover {
    background-color: var(--diag-black);
    color: var(--diag-white);
    border-color: var(--diag-black);
}

.diag-chat-footer {
    padding: var(--diag-space-lg) var(--diag-space-xl);
    border-top: 1px solid var(--diag-gray-200);
    background-color: var(--diag-gray-50);
}

.diag-chat-input-wrapper {
    display: flex;
    gap: var(--diag-space-sm);
}

.diag-chat-input {
    flex: 1;
    padding: var(--diag-space-md) var(--diag-space-lg);
    border: 1px solid var(--diag-gray-300);
    font-size: 15px;
    font-family: var(--diag-font-sans);
    background-color: var(--diag-white);
    transition: border-color var(--diag-transition-fast);
}

.diag-chat-input:focus {
    outline: none;
    border-color: var(--diag-black);
}

.diag-chat-input::placeholder {
    color: var(--diag-gray-400);
}

.diag-chat-send {
    width: 52px;
    height: 52px;
    background-color: var(--diag-black);
    color: var(--diag-white);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity var(--diag-transition-fast);
}

.diag-chat-send:hover {
    opacity: 0.8;
}

.diag-chat-note {
    font-size: 12px;
    color: var(--diag-gray-500);
    text-align: center;
    margin: var(--diag-space-sm) 0 0 0;
}

/* Terms Trigger */
.diag-terms-trigger {
    margin-top: var(--diag-space-lg);
    text-align: center;
}

.diag-terms-btn {
    display: inline-flex;
    align-items: center;
    gap: var(--diag-space-sm);
    padding: var(--diag-space-sm) var(--diag-space-md);
    background-color: transparent;
    border: 1px solid var(--diag-gray-300);
    font-size: 13px;
    font-family: var(--diag-font-sans);
    color: var(--diag-gray-600);
    cursor: pointer;
    transition: all var(--diag-transition-fast);
}

.diag-terms-btn:hover {
    border-color: var(--diag-black);
    color: var(--diag-black);
}

.diag-terms-note {
    font-size: 11px;
    color: var(--diag-gray-500);
    margin: var(--diag-space-sm) 0 0 0;
}

/* ==========================================================================
   4. Features Section
   ========================================================================== */
.diag-features-section {
    background-color: var(--diag-white);
}

.diag-features-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: var(--diag-space-lg);
}

.diag-feature-card {
    text-align: center;
    padding: var(--diag-space-xl) var(--diag-space-md);
    background-color: var(--diag-gray-50);
    border: 1px solid var(--diag-gray-200);
    transition: all var(--diag-transition-base);
}

.diag-feature-card:hover {
    border-color: var(--diag-black);
    box-shadow: var(--diag-shadow-md);
}

.diag-feature-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto var(--diag-space-md);
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--diag-white);
    border: 1px solid var(--diag-gray-200);
}

.diag-feature-icon svg {
    color: var(--diag-black);
}

.diag-feature-title {
    font-size: 14px;
    font-weight: 700;
    margin: 0 0 var(--diag-space-sm) 0;
}

.diag-feature-text {
    font-size: 12px;
    color: var(--diag-gray-600);
    line-height: 1.6;
    margin: 0;
}

/* ==========================================================================
   5. Benefits Section
   ========================================================================== */
.diag-benefits-section {
    background-color: var(--diag-gray-50);
}

.diag-benefits-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--diag-space-3xl);
    align-items: center;
}

.diag-benefits-image img {
    display: block;
    width: 100%;
    height: auto;
    filter: grayscale(100%);
    border: 1px solid var(--diag-gray-200);
}

.diag-benefits-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.diag-benefits-list li {
    display: flex;
    gap: var(--diag-space-md);
    padding: var(--diag-space-md) 0;
    border-bottom: 1px solid var(--diag-gray-200);
}

.diag-benefits-list li:last-child {
    border-bottom: none;
}

.diag-benefits-check {
    width: 20px;
    height: 20px;
    background-color: var(--diag-black);
    flex-shrink: 0;
    position: relative;
    margin-top: 2px;
}

.diag-benefits-check::after {
    content: "";
    position: absolute;
    left: 6px;
    top: 3px;
    width: 6px;
    height: 10px;
    border: solid var(--diag-white);
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.diag-benefits-list strong {
    display: block;
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 4px;
}

.diag-benefits-list p {
    font-size: 13px;
    color: var(--diag-gray-600);
    margin: 0;
}

/* ==========================================================================
   6. Target Section
   ========================================================================== */
.diag-target-section {
    background-color: var(--diag-white);
}

.diag-section__header {
    display: flex;
    align-items: center;
}

.diag-target-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--diag-space-lg);
}

.diag-target-card {
    display: flex;
    background-color: var(--diag-gray-50);
    border: 1px solid var(--diag-gray-200);
    overflow: hidden;
    transition: all var(--diag-transition-base);
}

.diag-target-card:hover {
    border-color: var(--diag-black);
    box-shadow: var(--diag-shadow-md);
}

.diag-target-main {
    flex: 1;
    padding: var(--diag-space-lg);
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.diag-target-name {
    font-size: 15px;
    font-weight: 700;
    margin: 0 0 var(--diag-space-sm) 0;
}

.diag-target-category {
    font-size: 11px;
    color: var(--diag-gray-500);
}

.diag-target-info {
    width: 180px;
    padding: var(--diag-space-md);
    background-color: var(--diag-gray-100);
    display: flex;
    align-items: center;
}

.diag-target-info p {
    font-size: 12px;
    color: var(--diag-gray-700);
    margin: 0;
    line-height: 1.5;
}

/* ==========================================================================
   7. CTA Section
   ========================================================================== */
.diag-cta-section {
    background-color: var(--diag-gray-200);
    text-align: center;
}

.diag-cta-content {
    max-width: 600px;
    margin: 0 auto;
}

.diag-cta-title {
    font-family: var(--diag-font-serif);
    font-size: clamp(28px, 5vw, 36px);
    font-weight: 600;
    margin: 0 0 var(--diag-space-lg) 0;
    line-height: 1.3;
    color: var(--diag-black);
}

.diag-cta-text {
    font-size: 14px;
    color: var(--diag-gray-700);
    margin: 0 0 var(--diag-space-xl) 0;
}

/* ==========================================================================
   Modal Styles
   ========================================================================== */
.diag-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all var(--diag-transition-base);
}

.diag-modal[aria-hidden="false"] {
    opacity: 1;
    visibility: visible;
}

.diag-modal__overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    cursor: pointer;
}

.diag-modal__container {
    position: relative;
    width: 90%;
    max-width: 700px;
    max-height: 85vh;
    background-color: var(--diag-white);
    display: flex;
    flex-direction: column;
    transform: translateY(20px);
    transition: transform var(--diag-transition-base);
}

.diag-modal[aria-hidden="false"] .diag-modal__container {
    transform: translateY(0);
}

.diag-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--diag-space-lg) var(--diag-space-xl);
    border-bottom: 1px solid var(--diag-gray-200);
    flex-shrink: 0;
}

.diag-modal__title {
    font-family: var(--diag-font-serif);
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.diag-modal__close {
    width: 40px;
    height: 40px;
    background-color: transparent;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--diag-gray-600);
    transition: color var(--diag-transition-fast);
}

.diag-modal__close:hover {
    color: var(--diag-black);
}

.diag-modal__body {
    flex: 1;
    overflow-y: auto;
    padding: var(--diag-space-xl);
}

.diag-modal__footer {
    padding: var(--diag-space-lg) var(--diag-space-xl);
    border-top: 1px solid var(--diag-gray-200);
    text-align: center;
    flex-shrink: 0;
}

/* Legal Content in Modal */
.diag-legal-content {
    font-size: 13px;
    line-height: 1.7;
}

.diag-legal-block {
    margin-bottom: var(--diag-space-xl);
}

.diag-legal-block:last-child {
    margin-bottom: 0;
}

.diag-legal-subtitle {
    font-size: 14px;
    font-weight: 700;
    margin: 0 0 var(--diag-space-md) 0;
    color: var(--diag-black);
}

.diag-legal-list {
    margin: 0;
    padding-left: var(--diag-space-lg);
}

.diag-legal-list li {
    color: var(--diag-gray-700);
    margin-bottom: var(--diag-space-sm);
}

.diag-legal-list li:last-child {
    margin-bottom: 0;
}

.diag-legal-list strong {
    color: var(--diag-black);
}

/* ==========================================================================
   Chat System Overrides (GIP Chat Plugin)
   ========================================================================== */
.diag-chat-body .gip-chat {
    border: none !important;
    box-shadow: none !important;
    max-width: 100% !important;
    background: transparent !important;
    border-radius: 0 !important;
}

.diag-chat-body .gip-chat-header {
    display: none !important;
}

.diag-chat-body .gip-chat-messages {
    background-color: transparent !important;
    padding: 0 !important;
    min-height: 400px !important;
}

.diag-chat-body .gip-message-bot .gip-message-avatar {
    background-color: var(--diag-black) !important;
    color: var(--diag-white) !important;
    border-radius: 0 !important;
}

.diag-chat-body .gip-message-bot .gip-message-bubble {
    background-color: var(--diag-gray-50) !important;
    border: 1px solid var(--diag-gray-200) !important;
    border-radius: 0 !important;
    box-shadow: none !important;
}

.diag-chat-body .gip-message-user .gip-message-bubble {
    background-color: var(--diag-black) !important;
    color: var(--diag-white) !important;
    border-radius: 0 !important;
}

.diag-chat-body .gip-option-btn {
    border-radius: 0 !important;
    border: 1px solid var(--diag-gray-300) !important;
}

.diag-chat-body .gip-option-btn:hover,
.diag-chat-body .gip-option-btn.selected {
    background-color: var(--diag-black) !important;
    color: var(--diag-white) !important;
    border-color: var(--diag-black) !important;
}

/* ==========================================================================
   Responsive Styles
   ========================================================================== */
@media (max-width: 1024px) {
    .diag-features-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    :root {
        --diag-container-padding: 20px;
    }
    
    .diag-section {
        padding: var(--diag-space-3xl) 0;
    }
    
    .diag-hero__grid {
        grid-template-columns: 1fr;
        gap: var(--diag-space-xl);
    }
    
    .diag-hero__content {
        padding-left: 0;
        text-align: center;
    }
    
    .diag-hero__badge {
        justify-content: center;
    }
    
    .diag-hero__description {
        text-align: left;
    }
    
    .diag-hero__cta {
        display: flex;
        justify-content: center;
    }
    
    .diag-flow {
        grid-template-columns: 1fr;
        gap: var(--diag-space-2xl);
    }
    
    .diag-flow__item {
        display: grid;
        grid-template-columns: 120px 1fr;
        gap: var(--diag-space-lg);
        align-items: center;
    }
    
    .diag-flow__image {
        margin: 0;
        aspect-ratio: 1;
    }
    
    .diag-chat-body {
        min-height: 400px;
        max-height: none;
    }
    
    .diag-features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .diag-features-grid .diag-feature-card:last-child {
        grid-column: span 2;
        max-width: 50%;
        margin: 0 auto;
    }
    
    .diag-benefits-layout {
        grid-template-columns: 1fr;
        gap: var(--diag-space-xl);
    }
    
    .diag-target-grid {
        grid-template-columns: 1fr;
    }
    
    .diag-section__header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--diag-space-md);
    }
    
    .diag-section__line {
        width: 100%;
        margin-left: 0;
    }
    
    .diag-chat-header {
        flex-direction: column;
        gap: var(--diag-space-md);
        align-items: flex-start;
    }
    
    .diag-modal__container {
        width: 95%;
        max-height: 90vh;
    }
}

@media (max-width: 480px) {
    .diag-hero__image-wrapper {
        box-shadow: 8px 8px 0 var(--diag-black);
    }
    
    .diag-flow__item {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .diag-flow__image {
        max-width: 200px;
        margin: 0 auto var(--diag-space-md);
    }
    
    .diag-features-grid {
        grid-template-columns: 1fr;
    }
    
    .diag-features-grid .diag-feature-card:last-child {
        grid-column: span 1;
        max-width: 100%;
    }
    
    .diag-target-card {
        flex-direction: column;
    }
    
    .diag-target-info {
        width: 100%;
    }
    
    .diag-btn--large {
        padding: var(--diag-space-md) var(--diag-space-xl);
        font-size: 15px;
        width: 100%;
    }
}

/* ==========================================================================
   Print Styles
   ========================================================================== */
@media print {
    .diag-hero__cta,
    .diag-chat-wrapper,
    .diag-cta-section,
    .diag-terms-trigger,
    .diag-modal {
        display: none;
    }
    
    .diag-wrapper {
        color: #000;
    }
    
    .diag-section {
        padding: 20px 0;
    }
}
</style>

<!-- ============================================
     SCRIPTS
     ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // ========================================
    // Modal Functions
    // ========================================
    const modal = document.getElementById('termsModal');
    const openBtn = document.getElementById('openTermsModal');
    const closeBtn = document.getElementById('closeTermsModal');
    const closeElements = document.querySelectorAll('[data-close-modal]');
    
    function openModal() {
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        closeBtn.focus();
    }
    
    function closeModal() {
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        openBtn.focus();
    }
    
    if (openBtn) {
        openBtn.addEventListener('click', openModal);
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    closeElements.forEach(function(el) {
        el.addEventListener('click', closeModal);
    });
    
    // ESCキーでモーダルを閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
            closeModal();
        }
    });
    
    // モーダル内でのタブトラップ
    modal.addEventListener('keydown', function(e) {
        if (e.key !== 'Tab') return;
        
        const focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        if (e.shiftKey && document.activeElement === firstElement) {
            e.preventDefault();
            lastElement.focus();
        } else if (!e.shiftKey && document.activeElement === lastElement) {
            e.preventDefault();
            firstElement.focus();
        }
    });
    
    // ========================================
    // Smooth Scroll
    // ========================================
    const smoothScrollLinks = document.querySelectorAll('.smooth-scroll');
    
    smoothScrollLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                const headerOffset = 80;
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // ========================================
    // Intersection Observer for Animations
    // ========================================
    const observerOptions = {
        root: null,
        rootMargin: '0px 0px -50px 0px',
        threshold: 0.1
    };
    
    const animationObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                animationObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    const animatedElements = document.querySelectorAll(
        '.diag-flow__item, ' +
        '.diag-feature-card, ' +
        '.diag-target-card, ' +
        '.diag-benefits-list li'
    );
    
    animatedElements.forEach(function(el, index) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease ' + (index * 0.05) + 's, transform 0.6s ease ' + (index * 0.05) + 's';
        animationObserver.observe(el);
    });
    
    document.head.insertAdjacentHTML('beforeend', 
        '<style>.is-visible { opacity: 1 !important; transform: translateY(0) !important; }</style>'
    );
    
    // ========================================
    // Demo Option Buttons
    // ========================================
    const demoOptionBtns = document.querySelectorAll('.diag-chat-demo .diag-option-btn');
    
    demoOptionBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            demoOptionBtns.forEach(function(b) {
                b.classList.remove('selected');
            });
            
            this.classList.add('selected');
            this.style.backgroundColor = 'var(--diag-black)';
            this.style.color = 'var(--diag-white)';
            this.style.borderColor = 'var(--diag-black)';
        });
    });
    
    // ========================================
    // Chat Input
    // ========================================
    const chatInput = document.querySelector('.diag-chat-input');
    const chatSend = document.querySelector('.diag-chat-send');
    
    if (chatInput && chatSend) {
        chatSend.addEventListener('click', function() {
            if (chatInput.value.trim()) {
                console.log('Message sent:', chatInput.value);
                chatInput.value = '';
            }
        });
        
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                chatSend.click();
            }
        });
    }
});
</script>

<?php get_footer(); ?>