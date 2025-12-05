<?php
/**
 * Template Name: Subsidy Diagnosis Pro (AI診断)
 * Description: RAG機能を活用した補助金・助成金AI診断 - プロフェッショナル金融LP
 *
 * @package Grant_Insight_Perfect
 * @version 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// フッターの自動モーダル出力を無効化（このページ専用のモーダルを使用するため）

get_header();

// チャットシステムの利用可否確認
$has_chat_system = shortcode_exists('gip_chat');

// SEO用メタデータ（サジェストキーワード対応：補助金診断、補助金診断士、補助金診断サービス、補助金診断 個人）
$page_title = '【無料】補助金診断サービス | 法人・個人対応の補助金診断ツール';
$page_description = '補助金診断サービスを無料で提供。中小企業から個人事業主まで対応した補助金診断ツールで、申請可能な補助金・助成金を検索。専門の補助金診断士に相談する前の事前調査としても活用できます。';

// 画像パス
$img_base = 'https://joseikin-insight.com/wp-content/uploads/2025/12/';
?>

<div class="diag-wrapper" itemscope itemtype="https://schema.org/WebPage">

    <!-- ============================================
         SECTION 1: HERO - ヒーローセクション
         ============================================ -->
    <section class="diag-hero" aria-label="メインビジュアル">
        <div class="diag-container">
            <div class="diag-hero__grid">
                <!-- 左側：画像 -->
                <div class="diag-hero__visual">
                    <div class="diag-hero__image-wrapper">
                        <img 
                            src="<?php echo esc_url($img_base); ?>1.png" 
                            alt="補助金診断サービス - ビジネスと資金調達のイメージ" 
                            class="diag-hero__image"
                            width="600"
                            height="400"
                            loading="eager"
                        >
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
                        <span>補助金診断サービス</span>
                    </div>
                    
                    <h1 class="diag-hero__title" itemprop="name">
                        <span class="diag-hero__title-sub">法人・個人対応</span>
                        補助金診断<br>
                        <span class="diag-hero__title-large">無料検索ツール</span>
                    </h1>
                    
                    <div class="diag-hero__description">
                        <p>
                            事業内容をヒアリングし、<br>
                            申請可能な補助金・助成金を自動で検索。<br>
                            <span class="diag-hero__highlight">会員登録不要・完全無料</span>で今すぐ診断できます。
                        </p>
                    </div>
                    
                    <div class="diag-hero__cta">
                        <a href="#diagnosis-app" class="diag-btn diag-btn--primary smooth-scroll" aria-label="補助金診断を開始する">
                            <span>無料で診断を始める</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <polyline points="19 12 12 19 5 12"/>
                            </svg>
                        </a>
                    </div>
                    
                    <!-- 信頼性指標 -->
                    <div class="diag-hero__trust">
                        <div class="diag-hero__trust-item">
                            <span class="diag-hero__trust-number">1,000+</span>
                            <span class="diag-hero__trust-label">補助金データ</span>
                        </div>
                        <div class="diag-hero__trust-item">
                            <span class="diag-hero__trust-number">47</span>
                            <span class="diag-hero__trust-label">都道府県対応</span>
                        </div>
                        <div class="diag-hero__trust-item">
                            <span class="diag-hero__trust-number">24h</span>
                            <span class="diag-hero__trust-label">いつでも利用可</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- ============================================
         SECTION 2: FLOW - 診断の流れ（4ステップ 画像付きカード）
         ============================================ -->
    <section class="diag-section diag-flow-section" aria-labelledby="flow-title">
        <div class="diag-container">
            <header class="diag-section__header">
                <h2 id="flow-title" class="diag-section__title">補助金診断の流れ</h2>
                <p class="diag-section__subtitle">4つのステップで最適な補助金・助成金を検索</p>
            </header>

            <div class="diag-flow diag-flow--image-grid" role="list">
                <!-- Step 1: ヒアリング -->
                <article class="diag-flow__card" role="listitem">
                    <div class="diag-flow__card-image">
                        <img 
                            src="<?php echo esc_url($img_base); ?>2.png" 
                            alt="補助金診断 ステップ1 ヒアリング" 
                            loading="lazy"
                            width="400"
                            height="300"
                        >
                    </div>
                    <div class="diag-flow__card-content">
                        <div class="diag-flow__step-badge">01</div>
                        <h3 class="diag-flow__title">ヒアリング</h3>
                        <p class="diag-flow__text">
                            事業内容や課題を<br>
                            チャット形式でヒアリング
                        </p>
                    </div>
                </article>

                <!-- Step 2: 深掘り質問 -->
                <article class="diag-flow__card" role="listitem">
                    <div class="diag-flow__card-image">
                        <img 
                            src="<?php echo esc_url($img_base); ?>6.png" 
                            alt="補助金診断 ステップ2 深掘り質問" 
                            loading="lazy"
                            width="400"
                            height="300"
                        >
                    </div>
                    <div class="diag-flow__card-content">
                        <div class="diag-flow__step-badge">02</div>
                        <h3 class="diag-flow__title">深掘り質問</h3>
                        <p class="diag-flow__text">
                            補助金の特徴を質問し<br>
                            ニーズを正確に把握
                        </p>
                    </div>
                </article>

                <!-- Step 3: データベース検索 -->
                <article class="diag-flow__card" role="listitem">
                    <div class="diag-flow__card-image">
                        <img 
                            src="<?php echo esc_url($img_base); ?>3.png" 
                            alt="補助金診断 ステップ3 データベース検索" 
                            loading="lazy"
                            width="400"
                            height="300"
                        >
                    </div>
                    <div class="diag-flow__card-content">
                        <div class="diag-flow__step-badge">03</div>
                        <h3 class="diag-flow__title">データベース検索</h3>
                        <p class="diag-flow__text">
                            1,000件以上の補助金データから<br>
                            条件に合う制度を自動抽出
                        </p>
                    </div>
                </article>

                <!-- Step 4: 結果表示 -->
                <article class="diag-flow__card" role="listitem">
                    <div class="diag-flow__card-image">
                        <img 
                            src="<?php echo esc_url($img_base); ?>8.png" 
                            alt="補助金診断 ステップ4 結果表示" 
                            loading="lazy"
                            width="400"
                            height="300"
                        >
                    </div>
                    <div class="diag-flow__card-content">
                        <div class="diag-flow__step-badge">04</div>
                        <h3 class="diag-flow__title">結果表示</h3>
                        <p class="diag-flow__text">
                            マッチする補助金を<br>
                            おすすめ順にご提案
                        </p>
                    </div>
                </article>
            </div>
            
            <!-- フロー後のCTAボタン -->
            <div class="diag-flow-cta">
                <button type="button" class="diag-btn diag-btn--primary diag-btn--large diag-popup-trigger" data-gip-modal-open="true">
                    <span>今すぐ診断を開始する</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>
            </div>
        </div>
    </section>

    <!-- ============================================
         SECTION 3: CHAT CTA - ポップアップ起動ボタン
         ============================================ -->
    <section id="diagnosis-app" class="diag-section diag-app-section" aria-labelledby="app-title">
        <div class="diag-container">
            <div class="diag-chat-cta-wrapper">
                <div class="diag-chat-cta-content">
                    <div class="diag-chat-cta-avatar">
                        <img 
                            src="<?php echo esc_url($img_base); ?>7.png" 
                            alt="補助金診断コンシェルジュ" 
                            width="80" 
                            height="80"
                        >
                    </div>
                    <h2 id="app-title" class="diag-chat-cta-title">補助金診断コンシェルジュ</h2>
                    <p class="diag-chat-cta-description">
                        AIがあなたに最適な補助金・助成金をお探しします。<br>
                        簡単な質問に答えるだけで、申請可能な制度が見つかります。
                    </p>
                    <div class="diag-chat-cta-badges">
                        <span class="diag-badge">無料</span>
                        <span class="diag-badge">会員登録不要</span>
                        <span class="diag-badge">24時間対応</span>
                        <span class="diag-badge">所要時間3分</span>
                    </div>
                    <div class="diag-chat-cta-button">
                        <button type="button" class="diag-btn diag-btn--primary diag-btn--large diag-popup-trigger" data-gip-modal-open="true">
                            <span>今すぐ補助金診断を始める</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                                <polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </button>
                    </div>
                    <p class="diag-chat-cta-note">ボタンをタップするとAI診断が始まります</p>
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
         SECTION 4: FEATURES - システムの特長（2x2グリッド）
         ============================================ -->
    <section class="diag-section diag-features-section" aria-labelledby="features-title">
        <div class="diag-container">
            <header class="diag-section__header diag-section__header--center">
                <h2 id="features-title" class="diag-section__title">補助金診断ツールの特長</h2>
                <p class="diag-section__subtitle">AIによる補助金・助成金検索システム</p>
            </header>

            <div class="diag-features-grid diag-features-grid--2x2">
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
         SECTION 5: BENEFITS - 補助金診断サービスを活用するメリット
         ============================================ -->
    <section class="diag-section diag-benefits-section" aria-labelledby="benefits-title">
        <div class="diag-container">
            <header class="diag-section__header">
                <h2 id="benefits-title" class="diag-section__title">補助金診断サービスを活用するメリット</h2>
                <p class="diag-section__subtitle">補助金診断士に相談する前の事前調査としても活用できます</p>
            </header>

            <div class="diag-benefits-layout">
                <div class="diag-benefits-image">
                    <img 
                        src="<?php echo esc_url($img_base); ?>4.png" 
                        alt="補助金診断ツールを利用するビジネスパーソン" 
                        width="600" 
                        height="400"
                        loading="lazy"
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
            
            <!-- メリットセクション後のCTA -->
            <div class="diag-benefits-cta">
                <button type="button" class="diag-btn diag-btn--primary diag-btn--large diag-popup-trigger" data-gip-modal-open="true">
                    <span>無料で補助金診断を始める</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>
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
            
            <!-- 対象者セクション後のCTA -->
            <div class="diag-target-cta">
                <p class="diag-target-cta__lead">あなたも今すぐ診断してみませんか？</p>
                <button type="button" class="diag-btn diag-btn--primary diag-btn--large diag-popup-trigger" data-gip-modal-open="true">
                    <span>今すぐ補助金診断を始める</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>
            </div>
        </div>
    </section>

    <!-- ============================================
         SECTION 7: ABOUT - 補助金診断とは（SEOコンテンツ）
         ============================================ -->
    <section class="diag-section diag-about-section" aria-labelledby="about-title">
        <div class="diag-container">
            <header class="diag-section__header">
                <h2 id="about-title" class="diag-section__title">補助金診断とは</h2>
                <p class="diag-section__subtitle">事業者に最適な補助金・助成金を見つけるサービス</p>
            </header>

            <div class="diag-about-content">
                <div class="diag-about-text">
                    <h3>補助金診断サービスの概要</h3>
                    <p>
                        <strong>補助金診断</strong>とは、事業者の業種・規模・目的などの情報をもとに、申請可能な補助金・助成金を検索するサービスです。
                        国や地方自治体は毎年多くの補助金制度を設けていますが、その数は数千種類にのぼり、自社に適した制度を見つけることは容易ではありません。
                    </p>
                    <p>
                        補助金診断サービスを利用することで、専門の<strong>補助金診断士</strong>や中小企業診断士に相談する前に、
                        どのような制度が利用可能か事前に把握できます。これにより、専門家への相談をより効率的に行うことができます。
                    </p>

                    <h3>法人から個人まで幅広く対応</h3>
                    <p>
                        当サービスの補助金診断は、中小企業や法人だけでなく、<strong>個人事業主やフリーランス</strong>の方にもご利用いただけます。
                        創業支援、設備投資、IT導入、人材育成など、様々な目的に応じた補助金・助成金情報を検索できます。
                    </p>

                    <h3>検索技術について</h3>
                    <p>
                        本サービスでは、<strong>RAG（Retrieval-Augmented Generation）</strong>と呼ばれる検索拡張生成技術を採用しています。
                        RAGは、大規模なデータベースから関連情報を検索し、その情報をもとに回答を生成する技術です。
                    </p>
                    <p>
                        従来のキーワード検索とは異なり、文脈や意味を理解した検索が可能なため、
                        「設備を導入したい」「従業員を増やしたい」といった自然な言葉でも、適切な補助金を見つけることができます。
                    </p>
                    
                    <div class="diag-about-tech">
                        <h4>RAG技術の仕組み</h4>
                        <ol>
                            <li><strong>質問の理解</strong>：入力された内容から、求めている情報を理解</li>
                            <li><strong>データベース検索</strong>：1,000件以上の補助金情報から関連度の高い制度を抽出</li>
                            <li><strong>結果の整理</strong>：検索結果を優先度順に整理して表示</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         SECTION 8: FAQ - よくある質問（SEOコンテンツ）
         ============================================ -->
    <section class="diag-section diag-faq-section" aria-labelledby="faq-title" itemscope itemtype="https://schema.org/FAQPage">
        <div class="diag-container">
            <header class="diag-section__header">
                <h2 id="faq-title" class="diag-section__title">補助金診断に関するよくある質問</h2>
            </header>

            <div class="diag-faq-list">
                <div class="diag-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 class="diag-faq-question" itemprop="name">補助金診断は本当に無料ですか？</h3>
                    <div class="diag-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">はい、当サービスの補助金診断は<strong>完全無料</strong>でご利用いただけます。会員登録も不要で、何度でも診断可能です。</p>
                    </div>
                </div>

                <div class="diag-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 class="diag-faq-question" itemprop="name">個人でも補助金診断を利用できますか？</h3>
                    <div class="diag-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">はい、<strong>個人事業主やフリーランスの方</strong>も補助金診断をご利用いただけます。創業支援や事業拡大に関する補助金など、個人向けの制度も多数掲載しています。</p>
                    </div>
                </div>

                <div class="diag-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 class="diag-faq-question" itemprop="name">補助金診断士とは何ですか？</h3>
                    <div class="diag-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text"><strong>補助金診断士</strong>は、補助金・助成金の申請支援を専門とする民間資格です。当サービスは補助金診断士への相談前の事前調査としてもご活用いただけます。実際の申請手続きには、専門家へのご相談をお勧めします。</p>
                    </div>
                </div>

                <div class="diag-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 class="diag-faq-question" itemprop="name">診断結果に表示された補助金は必ず申請できますか？</h3>
                    <div class="diag-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text">診断結果は参考情報としてご活用ください。実際の申請にあたっては、各補助金の公募要領で<strong>申請要件を必ずご確認</strong>ください。制度によっては申請期限や予算状況により受付が終了している場合があります。</p>
                    </div>
                </div>

                <div class="diag-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 class="diag-faq-question" itemprop="name">補助金と助成金の違いは何ですか？</h3>
                    <div class="diag-faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p itemprop="text"><strong>補助金</strong>は主に経済産業省系の制度で、審査があり採択率が設定されています。<strong>助成金</strong>は主に厚生労働省系の制度で、要件を満たせば原則として受給できます。当サービスでは両方の制度を検索できます。</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================
         SECTION 9: CTA - 最終訴求
         ============================================ -->
    <section class="diag-section diag-cta-section" aria-labelledby="cta-title">
        <div class="diag-container">
            <div class="diag-cta-content">
                <h2 id="cta-title" class="diag-cta-title">
                    今すぐ無料で<br class="diag-sp-only">補助金診断を始める
                </h2>
                <p class="diag-cta-text">
                    会員登録不要・所要時間約3分<br>
                    法人・個人問わず、申請可能な補助金・助成金を検索します
                </p>
                <button type="button" class="diag-btn diag-btn--secondary diag-btn--large diag-popup-trigger" data-gip-modal-open="true">
                    <span>今すぐ無料診断を開始する</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>
            </div>
        </div>
    </section>

</div>

<!-- フローティングCTA（スマホ用） -->
<div class="diag-floating-cta">
    <button type="button" class="diag-btn diag-btn--primary diag-popup-trigger" data-gip-modal-open="true">
        <span>補助金診断を始める</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="5" y1="12" x2="19" y2="12"/>
            <polyline points="12 5 19 12 12 19"/>
        </svg>
    </button>
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
    max-width: 100vw;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* 横スクロール防止 */
html, body {
    overflow-x: hidden;
    max-width: 100%;
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
    min-height: 44px; /* タップ領域確保 - Apple HIG準拠 */
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
   2. Flow Section - 2x2 Grid
   ========================================================================== */
.diag-flow-section {
    background-color: var(--diag-white);
}

.diag-flow {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--diag-space-xl);
}

/* 2x2グリッドレイアウト */
.diag-flow--grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--diag-space-lg);
}

.diag-flow__item--card {
    background-color: var(--diag-gray-50);
    border: 1px solid var(--diag-gray-200);
    padding: var(--diag-space-xl);
    text-align: center;
    transition: all var(--diag-transition-base);
}

.diag-flow__item--card:hover {
    border-color: var(--diag-black);
    box-shadow: var(--diag-shadow-md);
}

.diag-flow__step-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background-color: var(--diag-black);
    color: var(--diag-white);
    font-family: var(--diag-font-mono);
    font-size: 14px;
    font-weight: 700;
    margin-bottom: var(--diag-space-md);
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
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 var(--diag-space-sm) 0;
}

.diag-flow__text {
    font-size: 13px;
    color: var(--diag-gray-600);
    line-height: 1.7;
    margin: 0;
}

/* ==========================================================================
   3. Chat App Section - Gemini-style Clean Talk UI
   ========================================================================== */
.diag-app-section {
    background-color: var(--diag-white);
    padding: var(--diag-space-xl) 0 var(--diag-space-2xl);
}

.diag-chat-wrapper {
    border: 2px solid var(--diag-black);
    box-shadow: 8px 8px 0 var(--diag-black);
    background-color: var(--diag-gray-50);
    overflow-x: hidden;
    overflow-y: visible;
    display: flex;
    flex-direction: column;
    min-height: 500px;
    max-width: 800px;
    width: 100%;
    margin: 0 auto;
    border-radius: 0;
}

/* ヘッダー - シンプルに */
.diag-chat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 0;
    border-bottom: none;
    background-color: transparent;
    flex-shrink: 0;
    margin-bottom: 8px;
}

.diag-chat-profile {
    display: flex;
    align-items: center;
    gap: 12px;
}

.diag-chat-avatar {
    width: 40px;
    height: 40px;
    background-color: var(--diag-black);
    color: var(--diag-white);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 50%;
}

.diag-chat-avatar svg {
    width: 18px;
    height: 18px;
}

.diag-chat-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.diag-chat-name {
    font-size: 15px;
    font-weight: 600;
    margin: 0;
    line-height: 1.3;
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
    width: 6px;
    height: 6px;
    background-color: #22c55e;
    border-radius: 50%;
    animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.diag-chat-badges {
    display: flex;
    gap: 8px;
}

.diag-badge {
    font-size: 11px;
    font-weight: 500;
    padding: 4px 10px;
    background-color: var(--diag-gray-100);
    color: var(--diag-gray-600);
    border-radius: 12px;
}

/* チャットボディ */
.diag-chat-body {
    flex: 1;
    min-height: 400px;
    overflow-y: auto;
    padding: 16px;
    background-color: var(--diag-white);
    -webkit-overflow-scrolling: touch;
}

.diag-chat-demo {
    padding: 0;
}

/* メッセージ - Gemini風 */
.diag-message {
    margin-bottom: 0;
}

.diag-message--bot {
    /* AIメッセージは薄いグレー背景で区別 */
    background-color: var(--diag-gray-50);
    padding: 24px 16px;
    margin: 0;
}

.diag-message__avatar {
    display: none;
}

.diag-message__content {
    flex: 1;
    max-width: 100%;
}

.diag-message__bubble {
    background-color: transparent;
    border: none;
    padding: 0;
    font-size: 15px;
    line-height: 1.8;
    color: var(--diag-black);
}

.diag-message__bubble p {
    margin: 0 0 12px 0;
}

.diag-message__bubble p:last-child {
    margin-bottom: 0;
}

/* オプションボタン - Gemini風のピル型 */
.diag-message__options {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 24px;
}

.diag-option-btn {
    width: 100%;
    background-color: var(--diag-white);
    border: 1px solid var(--diag-gray-200);
    padding: 14px 20px;
    min-height: 52px;
    font-size: 15px;
    font-family: var(--diag-font-sans);
    cursor: pointer;
    transition: all var(--diag-transition-fast);
    text-align: left;
    display: flex;
    align-items: center;
    border-radius: 12px;
}

.diag-option-btn:hover {
    background-color: var(--diag-gray-100);
    border-color: var(--diag-gray-300);
}

.diag-option-btn:active,
.diag-option-btn.selected {
    background-color: var(--diag-black);
    color: var(--diag-white);
    border-color: var(--diag-black);
}

/* フッター入力エリア */
.diag-chat-footer {
    padding: 16px;
    border-top: 1px solid var(--diag-gray-200);
    background-color: var(--diag-white);
    flex-shrink: 0;
    overflow: visible; /* ボタンが切れないように */
}

.diag-chat-input-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;
    background-color: var(--diag-white);
    border: 1px solid var(--diag-gray-300);
    border-radius: 24px;
    padding: 4px 6px 4px 16px;
    max-width: 100%;
    width: 100%;
    box-sizing: border-box;
    overflow: visible; /* ボタンが切れないように */
}

.diag-chat-input {
    flex: 1;
    min-width: 0; /* flex内でのオーバーフロー防止 */
    padding: 10px 0;
    border: none;
    font-size: 16px !important; /* iOS自動ズーム防止必須 */
    font-family: var(--diag-font-sans);
    background-color: transparent;
    -webkit-appearance: none;
    -webkit-text-size-adjust: 100%;
    appearance: none;
}

.diag-chat-input:focus {
    outline: none;
}

.diag-chat-input::placeholder {
    color: var(--diag-gray-500);
    font-size: 14px;
}

.diag-chat-send {
    width: 40px;
    height: 40px;
    min-width: 40px;
    flex-shrink: 0; /* ボタンが縮小しないように */
    background-color: var(--diag-black);
    color: var(--diag-white);
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--diag-transition-fast);
    flex-shrink: 0;
}

.diag-chat-send:hover {
    background-color: var(--diag-gray-800);
}

.diag-chat-send:active {
    transform: scale(0.95);
}

.diag-chat-note {
    display: none; /* ノート非表示でクリーンに */
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
    min-height: 44px; /* タップ領域確保 - Apple HIG準拠 */
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
   4. Features Section - 2x2 Grid
   ========================================================================== */
.diag-features-section {
    background-color: var(--diag-white);
}

.diag-features-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: var(--diag-space-lg);
}

/* 2x2グリッドレイアウト */
.diag-features-grid--2x2 {
    grid-template-columns: repeat(2, 1fr);
    max-width: 700px;
    margin: 0 auto;
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

/* AIアバター非表示 - 自然な質問表現 */
.diag-chat-body .gip-message-bot .gip-message-avatar {
    display: none !important;
}

/* AIメッセージは枠なし - 自然に質問している表現 */
.diag-chat-body .gip-message-bot .gip-message-bubble {
    background: transparent !important;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    padding: 0 !important;
    color: var(--diag-black) !important;
}

.diag-chat-body .gip-message-user .gip-message-bubble {
    background-color: var(--diag-black) !important;
    color: var(--diag-white) !important;
    border-radius: 0 !important;
}

/* オプションボタン - モバイルでも見やすく */
.diag-chat-body .gip-options {
    display: flex !important;
    flex-direction: column !important;
    gap: 10px !important;
    margin-top: 16px !important;
}

.diag-chat-body .gip-option-btn {
    width: 100% !important;
    text-align: center !important;
    padding: 14px 20px !important;
    min-height: 48px !important;
    font-size: 15px !important;
    border-radius: 0 !important;
    border: 1px solid var(--diag-gray-300) !important;
    background: var(--diag-white) !important;
    color: var(--diag-black) !important;
}

.diag-chat-body .gip-option-btn:hover,
.diag-chat-body .gip-option-btn.selected {
    background-color: var(--diag-black) !important;
    color: var(--diag-white) !important;
    border-color: var(--diag-black) !important;
}

/* 選択肢表示中は入力欄を非表示 */
.diag-chat-body .gip-chat-input-wrap.has-options {
    display: none !important;
}

/* ==========================================================================
   Results Display Overrides (ai-concierge.php 結果表示との統合)
   - 階層化された結果表示（上位1-5詳細、6-10補足）に対応
   ========================================================================== */

/* 結果エリア全体 */
.diag-chat-body .gip-results {
    background-color: var(--diag-white) !important;
    border-radius: 0 !important;
    border: 1px solid var(--diag-gray-200) !important;
    margin-top: var(--diag-space-lg) !important;
    overflow: hidden !important;
}

.diag-chat-body .gip-results-header {
    padding: var(--diag-space-lg) var(--diag-space-xl) !important;
    border-bottom: 1px solid var(--diag-gray-200) !important;
    background-color: var(--diag-gray-50) !important;
}

.diag-chat-body .gip-results-title {
    font-family: var(--diag-font-serif) !important;
    color: var(--diag-black) !important;
    font-weight: 600 !important;
}

.diag-chat-body .gip-results-count {
    color: var(--diag-gray-600) !important;
    font-size: 14px !important;
}

/* メイン結果カード（上位1-5位詳細表示） */
.diag-chat-body .gip-results-grid-main .gip-result-card {
    border: 1px solid var(--diag-gray-200) !important;
    border-radius: 0 !important;
    background-color: var(--diag-white) !important;
    box-shadow: none !important;
    transition: border-color var(--diag-transition-base), box-shadow var(--diag-transition-base) !important;
    margin-bottom: var(--diag-space-lg) !important;
}

.diag-chat-body .gip-results-grid-main .gip-result-card:hover {
    border-color: var(--diag-black) !important;
    box-shadow: var(--diag-shadow-md) !important;
}

.diag-chat-body .gip-result-header {
    background-color: var(--diag-gray-50) !important;
    border-bottom: 1px solid var(--diag-gray-200) !important;
}

.diag-chat-body .gip-result-rank {
    background-color: var(--diag-black) !important;
    color: var(--diag-white) !important;
    border-radius: 0 !important;
}

.diag-chat-body .gip-result-title {
    font-family: var(--diag-font-serif) !important;
    color: var(--diag-black) !important;
}

.diag-chat-body .gip-result-score {
    background-color: var(--diag-white) !important;
    border: 1px solid var(--diag-gray-200) !important;
    border-radius: 0 !important;
}

.diag-chat-body .gip-result-score-value {
    color: var(--diag-black) !important;
    font-family: var(--diag-font-mono) !important;
}

.diag-chat-body .gip-result-body {
    padding: var(--diag-space-lg) var(--diag-space-xl) !important;
}

.diag-chat-body .gip-result-meta-item {
    background-color: var(--diag-gray-50) !important;
    border: 1px solid var(--diag-gray-200) !important;
    border-radius: 0 !important;
}

.diag-chat-body .gip-result-meta-label {
    color: var(--diag-gray-600) !important;
}

.diag-chat-body .gip-result-meta-value {
    color: var(--diag-black) !important;
    font-weight: 600 !important;
}

.diag-chat-body .gip-result-actions {
    padding: var(--diag-space-md) var(--diag-space-xl) !important;
    border-top: 1px solid var(--diag-gray-200) !important;
}

.diag-chat-body .gip-result-btn {
    border-radius: 0 !important;
    min-height: 44px !important;
}

.diag-chat-body .gip-result-btn-primary {
    background-color: var(--diag-black) !important;
    color: var(--diag-white) !important;
}

.diag-chat-body .gip-result-btn-primary:hover {
    background-color: var(--diag-gray-800) !important;
}

.diag-chat-body .gip-result-btn-secondary {
    background-color: var(--diag-white) !important;
    border: 1px solid var(--diag-gray-300) !important;
    color: var(--diag-gray-700) !important;
}

.diag-chat-body .gip-result-btn-secondary:hover {
    border-color: var(--diag-black) !important;
    color: var(--diag-black) !important;
}

/* サブ結果カード（6-10位補足表示） */
.diag-chat-body .gip-results-grid-sub {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: var(--diag-space-md) !important;
    padding: var(--diag-space-lg) !important;
    background-color: var(--diag-gray-50) !important;
    border-top: 2px dashed var(--diag-gray-300) !important;
}

.diag-chat-body .gip-results-sub-title {
    grid-column: span 2 !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    color: var(--diag-gray-600) !important;
    margin-bottom: var(--diag-space-sm) !important;
}

.diag-chat-body .gip-results-grid-sub .gip-result-card-mini {
    background-color: var(--diag-white) !important;
    border: 1px solid var(--diag-gray-200) !important;
    padding: var(--diag-space-md) !important;
    transition: border-color var(--diag-transition-base) !important;
}

.diag-chat-body .gip-results-grid-sub .gip-result-card-mini:hover {
    border-color: var(--diag-black) !important;
}

.diag-chat-body .gip-result-mini-rank {
    display: inline-block !important;
    background-color: var(--diag-gray-600) !important;
    color: var(--diag-white) !important;
    font-size: 11px !important;
    padding: 2px 8px !important;
    margin-bottom: var(--diag-space-xs) !important;
}

.diag-chat-body .gip-result-mini-title {
    font-size: 13px !important;
    font-weight: 600 !important;
    color: var(--diag-black) !important;
    line-height: 1.4 !important;
}

.diag-chat-body .gip-result-mini-amount {
    font-size: 12px !important;
    color: var(--diag-gray-600) !important;
    margin-top: var(--diag-space-xs) !important;
}

/* 比較モーダル統合 */
.diag-chat-body .gip-comparison-modal .gip-comparison-content {
    border-radius: 0 !important;
}

.diag-chat-body .gip-comparison-modal .gip-comparison-header {
    background-color: var(--diag-white) !important;
    border-bottom: 2px solid var(--diag-black) !important;
}

.diag-chat-body .gip-comparison-modal .gip-comparison-title {
    font-family: var(--diag-font-serif) !important;
}

.diag-chat-body .gip-comparison-modal .gip-comparison-close {
    border-radius: 0 !important;
    background-color: var(--diag-gray-100) !important;
}

.diag-chat-body .gip-comparison-modal .table-header {
    background-color: var(--diag-black) !important;
}

/* 続きオプション・再検索ボタン */
.diag-chat-body .gip-continue-chat {
    border-top: 2px solid var(--diag-gray-200) !important;
    padding-top: var(--diag-space-xl) !important;
    margin-top: var(--diag-space-xl) !important;
}

.diag-chat-body .gip-continue-title {
    font-family: var(--diag-font-serif) !important;
    color: var(--diag-gray-700) !important;
}

.diag-chat-body .gip-continue-options .gip-option-btn {
    border: 1px solid var(--diag-gray-300) !important;
    border-radius: 0 !important;
    min-height: 44px !important;
}

/* ロード更多ボタン */
.diag-chat-body .gip-btn-load-more {
    border: 2px solid var(--diag-gray-300) !important;
    border-radius: 0 !important;
    background-color: var(--diag-white) !important;
    min-height: 44px !important;
}

.diag-chat-body .gip-btn-load-more:hover {
    border-color: var(--diag-black) !important;
    color: var(--diag-black) !important;
}

/* バッジスタイル統合 */
.diag-chat-body .gip-badge {
    border-radius: 0 !important;
}

.diag-chat-body .gip-badge-success {
    background-color: var(--diag-gray-100) !important;
    color: var(--diag-black) !important;
    border: 1px solid var(--diag-gray-300) !important;
}

.diag-chat-body .gip-badge-warning {
    background-color: var(--diag-gray-200) !important;
    color: var(--diag-gray-700) !important;
}

/* 空状態 */
.diag-chat-body .gip-empty-state {
    padding: var(--diag-space-3xl) var(--diag-space-xl) !important;
}

.diag-chat-body .gip-empty-state-title {
    font-family: var(--diag-font-serif) !important;
    color: var(--diag-black) !important;
}

/* ローディング状態 */
.diag-chat-body .gip-loading-overlay {
    background: rgba(255,255,255,0.95) !important;
}

.diag-chat-body .gip-spinner-large {
    border-color: var(--diag-gray-200) !important;
    border-top-color: var(--diag-black) !important;
}

/* タイピングアニメーション - 枠なし・自然な表現 */
.diag-chat-body .gip-message-typing,
.diag-chat-body .gip-typing-indicator {
    background: transparent !important;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    padding: 8px 0 !important;
}

.diag-chat-body .gip-typing-dot {
    background-color: var(--diag-gray-500) !important;
}

/* 入力エリア - 選択肢表示中は非表示 */
.diag-chat-body .gip-chat-input-area {
    display: none !important;
}

.diag-chat-body .gip-chat-input-area.show-input {
    display: flex !important;
}

/* ==========================================================================
   Responsive Styles - Mobile First Optimization
   ========================================================================== */
@media (max-width: 1024px) {
    .diag-features-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    :root {
        --diag-container-padding: 12px;
    }
    
    /* 横スクロール完全防止 */
    html, body {
        overflow-x: hidden !important;
        max-width: 100vw !important;
    }
    
    .diag-section {
        padding: var(--diag-space-2xl) 0;
    }
    
    /* ================================
       Chat App - Mobile Full Screen (Gemini/ChatGPT style)
       ================================ */
    .diag-app-section {
        padding: 0;
        margin: 0;
    }
    
    .diag-app-section .diag-container--wide {
        padding: 0;
        max-width: 100%;
    }
    
    .diag-chat-wrapper {
        border: none;
        box-shadow: none;
        background-color: var(--diag-white);
        min-height: calc(100vh - 120px);
        min-height: calc(100dvh - 120px); /* iOS Safari dynamic viewport */
        max-width: 100%;
        width: 100%;
        overflow-x: hidden;
    }
    
    .diag-chat-header {
        flex-direction: row;
        gap: 8px;
        padding: 10px 14px;
        border-bottom: 1px solid var(--diag-gray-200);
        position: sticky;
        top: 0;
        z-index: 100;
        background-color: var(--diag-white);
    }
    
    .diag-chat-profile {
        gap: 8px;
    }
    
    .diag-chat-avatar {
        width: 32px;
        height: 32px;
    }
    
    .diag-chat-avatar svg {
        width: 14px;
        height: 14px;
    }
    
    .diag-chat-name {
        font-size: 13px;
    }
    
    .diag-chat-status {
        font-size: 10px;
    }
    
    .diag-chat-badges {
        gap: 4px;
    }
    
    .diag-badge {
        font-size: 9px;
        padding: 3px 6px;
    }
    
    .diag-chat-body {
        flex: 1;
        min-height: auto;
        padding: 16px 12px;
        overflow-y: auto;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        max-width: 100%;
    }
    
    /* フッター入力エリア - 送信ボタン見切れ防止 */
    .diag-chat-footer {
        padding: 12px;
        padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px));
        background-color: var(--diag-white);
        border-top: 1px solid var(--diag-gray-200);
        overflow: visible;
    }
    
    .diag-chat-input-wrapper {
        padding: 4px 6px 4px 12px;
        gap: 6px;
        min-height: 48px;
        box-sizing: border-box;
        width: 100%;
        overflow: visible;
    }
    
    /* iOS zoom prevention - critical */
    .diag-chat-input,
    .diag-chat-body input,
    .diag-chat-body textarea,
    .diag-chat-body select {
        font-size: 16px !important;
        -webkit-text-size-adjust: 100% !important;
        -webkit-appearance: none !important;
        appearance: none !important;
        border-radius: 0 !important;
        transform: scale(1); /* ズーム防止追加対策 */
        transform-origin: left top;
    }
    
    .diag-chat-input {
        padding: 10px 0;
        min-width: 0;
    }
    
    .diag-chat-send {
        width: 40px;
        height: 40px;
        min-width: 40px;
        flex-shrink: 0;
    }
    
    .diag-chat-note {
        font-size: 10px;
        margin-top: 6px;
    }
    
    /* Options - Full Width Touch Friendly */
    .diag-message__options,
    .diag-chat-body .gip-options {
        display: flex !important;
        flex-direction: column !important;
        gap: 10px !important;
        margin-top: 16px !important;
    }
    
    .diag-option-btn,
    .diag-chat-body .gip-option-btn {
        width: 100% !important;
        text-align: center !important;
        padding: 14px 16px !important;
        font-size: 15px !important;
        min-height: 52px !important;
        border-radius: 0 !important;
        -webkit-tap-highlight-color: transparent;
    }
    
    /* Select boxes */
    .diag-chat-body .gip-select {
        width: 100% !important;
        max-width: none !important;
        font-size: 16px !important;
        padding: 14px 16px !important;
        min-height: 52px !important;
    }
    
    /* Inline inputs */
    .diag-chat-body .gip-input-inline {
        flex-direction: column !important;
        gap: 10px !important;
        max-width: none !important;
    }
    
    .diag-chat-body .gip-inline-input {
        width: 100% !important;
        font-size: 16px !important;
        padding: 14px 16px !important;
    }
    
    .diag-chat-body .gip-inline-submit {
        width: 100% !important;
        min-height: 48px !important;
    }
    
    /* Results mobile optimization */
    .diag-chat-body .gip-results-grid-sub {
        grid-template-columns: 1fr !important;
    }
    
    .diag-chat-body .gip-results-sub-title {
        grid-column: span 1 !important;
    }
    
    .diag-chat-body .gip-result-meta {
        grid-template-columns: 1fr !important;
    }
    
    /* ================================
       Hero Section - Mobile
       ================================ */
    .diag-hero {
        padding: var(--diag-space-xl) 0 var(--diag-space-2xl);
    }
    
    .diag-hero__grid {
        grid-template-columns: 1fr;
        gap: var(--diag-space-lg);
    }
    
    .diag-hero__visual {
        order: 1;
    }
    
    .diag-hero__content {
        order: 2;
        padding-left: 0;
        text-align: center;
    }
    
    .diag-hero__badge {
        justify-content: center;
    }
    
    .diag-hero__title {
        font-size: 24px;
    }
    
    .diag-hero__title-large {
        font-size: 32px;
    }
    
    .diag-hero__description {
        text-align: left;
        padding: 14px 16px;
        font-size: 13px;
    }
    
    .diag-hero__cta {
        display: flex;
        justify-content: center;
    }
    
    /* ================================
       Flow Section - Mobile 2x2 Grid
       ================================ */
    .diag-flow--grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .diag-flow__item--card {
        padding: 16px 12px;
    }
    
    .diag-flow__step-badge {
        width: 32px;
        height: 32px;
        font-size: 12px;
        margin-bottom: 10px;
    }
    
    .diag-flow__title {
        font-size: 14px;
    }
    
    .diag-flow__text {
        font-size: 11px;
    }
    
    /* ================================
       Features Section - Mobile 2x2 Grid
       ================================ */
    .diag-features-grid,
    .diag-features-grid--2x2 {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        max-width: 100%;
    }
    
    .diag-feature-card {
        padding: 16px 12px;
    }
    
    .diag-feature-icon {
        width: 44px;
        height: 44px;
        margin-bottom: 10px;
    }
    
    .diag-feature-title {
        font-size: 13px;
    }
    
    .diag-feature-text {
        font-size: 11px;
    }
    
    /* 2x2グリッドでは最後の要素を特別扱いしない */
    .diag-features-grid--2x2 .diag-feature-card:last-child {
        grid-column: auto;
        max-width: none;
    }
    
    /* ================================
       Benefits Section - Mobile
       ================================ */
    .diag-benefits-layout {
        grid-template-columns: 1fr;
        gap: var(--diag-space-lg);
    }
    
    .diag-benefits-image {
        display: none; /* Hide image on mobile for space */
    }
    
    .diag-benefits-list li {
        padding: 12px 0;
    }
    
    .diag-benefits-list strong {
        font-size: 14px;
    }
    
    .diag-benefits-list p {
        font-size: 12px;
    }
    
    /* ================================
       Target Section - Mobile
       ================================ */
    .diag-target-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .diag-target-card {
        flex-direction: row;
    }
    
    .diag-target-main {
        padding: 14px;
    }
    
    .diag-target-name {
        font-size: 14px;
    }
    
    .diag-target-info {
        width: 140px;
        padding: 12px;
    }
    
    .diag-target-info p {
        font-size: 11px;
    }
    
    /* ================================
       Section Headers - Mobile
       ================================ */
    .diag-section__header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: var(--diag-space-xl);
    }
    
    .diag-section__title {
        font-size: 22px;
    }
    
    .diag-section__subtitle {
        font-size: 13px;
    }
    
    .diag-section__line {
        width: 100%;
        margin-left: 0;
    }
    
    /* ================================
       CTA Section - Mobile
       ================================ */
    .diag-cta-section {
        padding: var(--diag-space-2xl) 0;
    }
    
    .diag-cta-title {
        font-size: 24px;
    }
    
    .diag-cta-text {
        font-size: 13px;
    }
    
    /* ================================
       Terms & Modal - Mobile
       ================================ */
    .diag-terms-trigger {
        margin-top: 16px;
    }
    
    .diag-terms-btn {
        font-size: 12px;
        padding: 8px 12px;
    }
    
    .diag-modal__container {
        width: 95%;
        max-height: 90vh;
    }
    
    .diag-modal__header {
        padding: 14px 16px;
    }
    
    .diag-modal__title {
        font-size: 16px;
    }
    
    .diag-modal__body {
        padding: 16px;
    }
}

/* ==========================================================================
   Extra Small Devices (max-width: 480px)
   ========================================================================== */
@media (max-width: 480px) {
    :root {
        --diag-container-padding: 12px;
    }
    
    .diag-hero__image-wrapper {
        box-shadow: 6px 6px 0 var(--diag-black);
    }
    
    .diag-hero__title {
        font-size: 20px;
    }
    
    .diag-hero__title-large {
        font-size: 28px;
    }
    
    .diag-flow__item {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .diag-flow__image {
        max-width: 160px;
        margin: 0 auto 12px;
    }
    
    .diag-features-grid {
        grid-template-columns: 1fr;
        gap: 10px;
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
        padding: 14px 20px;
        font-size: 14px;
        width: 100%;
    }
    
    .diag-chat-body {
        padding: 14px 10px;
    }
    
    .diag-option-btn,
    .diag-chat-body .gip-option-btn {
        padding: 12px 14px !important;
        font-size: 14px !important;
        min-height: 48px !important;
    }
    
    /* 横スクロール防止強化 - 結果カード・ボタン */
    .diag-chat-body .gip-result-card,
    .diag-chat-body .gip-results,
    .diag-chat-body .gip-result-actions,
    .diag-chat-body .gip-result-meta {
        max-width: 100% !important;
        overflow-x: hidden !important;
    }
    
    .diag-chat-body .gip-result-btn {
        width: 100% !important;
        white-space: normal !important;
        word-break: break-word !important;
    }
    
    .diag-chat-body .gip-result-actions {
        flex-direction: column !important;
        gap: 8px !important;
    }
}

/* ==========================================================================
   Hero Trust Indicators
   ========================================================================== */
.diag-hero__title-sub {
    display: block;
    font-family: var(--diag-font-sans);
    font-size: 14px;
    font-weight: 500;
    color: var(--diag-gray-600);
    letter-spacing: 0.1em;
    margin-bottom: 8px;
}

.diag-hero__trust {
    display: flex;
    gap: var(--diag-space-xl);
    margin-top: var(--diag-space-xl);
    padding-top: var(--diag-space-lg);
    border-top: 1px solid var(--diag-gray-200);
}

.diag-hero__trust-item {
    text-align: center;
}

.diag-hero__trust-number {
    display: block;
    font-family: var(--diag-font-mono);
    font-size: 24px;
    font-weight: 700;
    color: var(--diag-black);
    line-height: 1.2;
}

.diag-hero__trust-label {
    font-size: 11px;
    color: var(--diag-gray-600);
    letter-spacing: 0.05em;
}

/* ==========================================================================
   Flow Section - Image Cards
   ========================================================================== */
.diag-flow--image-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--diag-space-lg);
}

.diag-flow__card {
    display: flex;
    flex-direction: row;
    background-color: var(--diag-white);
    border: 1px solid var(--diag-gray-200);
    overflow: hidden;
    transition: all var(--diag-transition-base);
}

.diag-flow__card:hover {
    border-color: var(--diag-black);
    box-shadow: var(--diag-shadow-md);
}

.diag-flow__card-image {
    flex: 0 0 50%;
    overflow: hidden;
    background-color: var(--diag-gray-100);
}

.diag-flow__card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: grayscale(30%);
    transition: all var(--diag-transition-slow);
}

.diag-flow__card:hover .diag-flow__card-image img {
    filter: grayscale(0%);
    transform: scale(1.05);
}

.diag-flow__card-content {
    flex: 1;
    padding: var(--diag-space-lg);
    display: flex;
    flex-direction: column;
    justify-content: center;
}

/* ==========================================================================
   Chat Avatar with Image
   ========================================================================== */
.diag-chat-avatar--image {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    overflow: hidden;
    background: none;
    border: 2px solid var(--diag-gray-200);
}

.diag-chat-avatar--image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* ==========================================================================
   About Section - SEO Content
   ========================================================================== */
.diag-about-section {
    background-color: var(--diag-gray-50);
}

.diag-about-content {
    max-width: 800px;
    margin: 0 auto;
}

.diag-about-text h3 {
    font-family: var(--diag-font-serif);
    font-size: 20px;
    font-weight: 600;
    margin: var(--diag-space-xl) 0 var(--diag-space-md);
    padding-bottom: var(--diag-space-sm);
    border-bottom: 1px solid var(--diag-gray-300);
}

.diag-about-text h3:first-child {
    margin-top: 0;
}

.diag-about-text p {
    font-size: 15px;
    line-height: 1.9;
    color: var(--diag-gray-800);
    margin-bottom: var(--diag-space-md);
}

.diag-about-tech {
    background-color: var(--diag-white);
    border: 1px solid var(--diag-gray-200);
    padding: var(--diag-space-lg);
    margin-top: var(--diag-space-xl);
}

.diag-about-tech h4 {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 var(--diag-space-md);
}

.diag-about-tech ol {
    margin: 0;
    padding-left: var(--diag-space-lg);
}

.diag-about-tech li {
    font-size: 14px;
    line-height: 1.8;
    margin-bottom: var(--diag-space-sm);
    color: var(--diag-gray-700);
}

/* ==========================================================================
   FAQ Section - SEO Content
   ========================================================================== */
.diag-faq-section {
    background-color: var(--diag-white);
}

.diag-faq-list {
    max-width: 800px;
    margin: 0 auto;
}

.diag-faq-item {
    border-bottom: 1px solid var(--diag-gray-200);
    padding: var(--diag-space-lg) 0;
}

.diag-faq-item:last-child {
    border-bottom: none;
}

.diag-faq-question {
    font-family: var(--diag-font-serif);
    font-size: 17px;
    font-weight: 600;
    color: var(--diag-black);
    margin: 0 0 var(--diag-space-md);
    padding-left: var(--diag-space-lg);
    position: relative;
}

.diag-faq-question::before {
    content: 'Q';
    position: absolute;
    left: 0;
    top: 0;
    font-family: var(--diag-font-mono);
    font-size: 14px;
    font-weight: 700;
    color: var(--diag-black);
}

.diag-faq-answer {
    padding-left: var(--diag-space-lg);
}

.diag-faq-answer p {
    font-size: 15px;
    line-height: 1.8;
    color: var(--diag-gray-700);
    margin: 0;
}

/* ==========================================================================
   Mobile Responsive - New Sections
   ========================================================================== */
@media (max-width: 768px) {
    /* Hero Trust */
    .diag-hero__trust {
        justify-content: center;
        gap: var(--diag-space-lg);
        flex-wrap: wrap;
    }
    
    .diag-hero__trust-number {
        font-size: 20px;
    }
    
    .diag-hero__trust-label {
        font-size: 10px;
    }
    
    /* Flow Image Cards - Mobile: background image style */
    .diag-flow--image-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .diag-flow__card {
        flex-direction: column;
        position: relative;
        min-height: 180px;
    }
    
    .diag-flow__card-image {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1;
    }
    
    .diag-flow__card-image img {
        filter: grayscale(30%) brightness(0.7);
    }
    
    .diag-flow__card-content {
        position: relative;
        z-index: 2;
        padding: var(--diag-space-md);
        text-align: center;
        color: var(--diag-white);
    }
    
    .diag-flow__card .diag-flow__step-badge {
        background-color: var(--diag-white);
        color: var(--diag-black);
    }
    
    .diag-flow__card .diag-flow__title {
        color: var(--diag-white);
        font-size: 14px;
        text-shadow: 0 1px 3px rgba(0,0,0,0.5);
    }
    
    .diag-flow__card .diag-flow__text {
        color: rgba(255,255,255,0.9);
        font-size: 11px;
        text-shadow: 0 1px 2px rgba(0,0,0,0.5);
    }
    
    /* About Section Mobile */
    .diag-about-text h3 {
        font-size: 17px;
    }
    
    .diag-about-text p {
        font-size: 14px;
    }
    
    .diag-about-tech {
        padding: var(--diag-space-md);
    }
    
    .diag-about-tech h4 {
        font-size: 14px;
    }
    
    .diag-about-tech li {
        font-size: 13px;
    }
    
    /* FAQ Section Mobile */
    .diag-faq-question {
        font-size: 15px;
        padding-left: var(--diag-space-lg);
    }
    
    .diag-faq-answer p {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .diag-hero__trust {
        gap: var(--diag-space-md);
    }
    
    .diag-flow--image-grid {
        grid-template-columns: 1fr;
    }
    
    .diag-flow__card {
        min-height: 160px;
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

/* ==========================================================================
   インラインCTAセクション
   ========================================================================== */
.diag-cta-inline {
    padding: var(--diag-space-2xl) 0;
    background-color: var(--diag-gray-50);
}

.diag-cta-inline--hero {
    padding: var(--diag-space-3xl) 0;
    background: linear-gradient(180deg, var(--diag-gray-50) 0%, var(--diag-white) 100%);
}

.diag-cta-inline__content {
    text-align: center;
}

.diag-cta-inline__note {
    font-size: 13px;
    color: var(--diag-gray-600);
    margin: var(--diag-space-md) 0 0 0;
}

/* フローセクション後のCTA */
.diag-flow-cta {
    margin-top: var(--diag-space-3xl);
    text-align: center;
}

/* メリットセクション後のCTA */
.diag-benefits-cta {
    margin-top: var(--diag-space-3xl);
    text-align: center;
    grid-column: span 2;
}

/* 対象者セクション後のCTA */
.diag-target-cta {
    margin-top: var(--diag-space-3xl);
    text-align: center;
    background-color: var(--diag-gray-50);
    padding: var(--diag-space-2xl);
    border: 1px solid var(--diag-gray-200);
}

.diag-target-cta__lead {
    font-family: var(--diag-font-serif);
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 var(--diag-space-lg) 0;
    color: var(--diag-black);
}

/* 最終CTAセクション - ボタンスタイル調整 */
.diag-cta-section .gip-cta-btn--secondary {
    background: transparent;
    color: var(--diag-black);
    border: 2px solid var(--diag-black);
}

.diag-cta-section .gip-cta-btn--secondary:hover {
    background: var(--diag-black);
    color: var(--diag-white);
}

/* モバイル対応 */
@media (max-width: 768px) {
    .diag-cta-inline {
        padding: var(--diag-space-xl) 0;
    }
    
    .diag-cta-inline--hero {
        padding: var(--diag-space-2xl) 0;
    }
    
    .diag-benefits-cta,
    .diag-target-cta,
    .diag-flow-cta {
        margin-top: var(--diag-space-2xl);
    }
    
    .diag-target-cta {
        padding: var(--diag-space-lg);
    }
    
    .diag-target-cta__lead {
        font-size: 16px;
    }
}

/* フローティングCTAボタン（スマホ用） */
.diag-floating-cta {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    display: none;
}

@media (max-width: 768px) {
    .diag-floating-cta {
        display: block;
    }
    
    .diag-floating-cta .gip-cta-btn {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
}

/* ==========================================================================
   Chat CTA Wrapper - ポップアップ起動用CTAセクション
   ========================================================================== */
.diag-chat-cta-wrapper {
    background: linear-gradient(135deg, var(--diag-gray-50) 0%, var(--diag-white) 100%);
    border: 2px solid var(--diag-black);
    box-shadow: 8px 8px 0 var(--diag-black);
    padding: var(--diag-space-3xl);
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
}

.diag-chat-cta-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--diag-space-lg);
}

.diag-chat-cta-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid var(--diag-black);
    background: var(--diag-white);
}

.diag-chat-cta-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.diag-chat-cta-title {
    font-family: var(--diag-font-serif);
    font-size: clamp(24px, 4vw, 32px);
    font-weight: 700;
    margin: 0;
    color: var(--diag-black);
}

.diag-chat-cta-description {
    font-size: 15px;
    line-height: 1.8;
    color: var(--diag-gray-700);
    margin: 0;
}

.diag-chat-cta-badges {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--diag-space-sm);
}

.diag-chat-cta-badges .diag-badge {
    background: var(--diag-black);
    color: var(--diag-white);
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 0;
}

.diag-chat-cta-button {
    margin-top: var(--diag-space-md);
}

.diag-chat-cta-note {
    font-size: 13px;
    color: var(--diag-gray-500);
    margin: var(--diag-space-sm) 0 0 0;
}

/* Chat CTA Mobile */
@media (max-width: 768px) {
    .diag-chat-cta-wrapper {
        padding: var(--diag-space-2xl) var(--diag-space-lg);
        box-shadow: 4px 4px 0 var(--diag-black);
    }
    
    .diag-chat-cta-avatar {
        width: 64px;
        height: 64px;
    }
    
    .diag-chat-cta-description {
        font-size: 14px;
    }
    
    .diag-chat-cta-badges .diag-badge {
        font-size: 11px;
        padding: 4px 10px;
    }
}

/* ==========================================================================
   ポップアップチャット スタイル
   ========================================================================== */
.diag-chat-popup {
    position: fixed;
    inset: 0;
    z-index: 10001;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all var(--diag-transition-base);
}

.diag-chat-popup[aria-hidden="false"] {
    opacity: 1;
    visibility: visible;
}

.diag-chat-popup__overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    cursor: pointer;
}

.diag-chat-popup__container {
    position: relative;
    width: 95%;
    max-width: 900px; /* PCでさらに大きく表示 */
    max-height: 90vh;
    background: var(--diag-white);
    display: flex;
    flex-direction: column;
    border: 2px solid var(--diag-black);
    box-shadow: 12px 12px 0 var(--diag-black);
    /* アニメーション削除 - カクカク防止 */
}

/* アニメーション削除 - カクカク防止 */

.diag-chat-popup__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 2px solid var(--diag-black);
    background: var(--diag-gray-50);
    flex-shrink: 0;
}

.diag-chat-popup__profile {
    display: flex;
    align-items: center;
    gap: 12px;
}

.diag-chat-popup__avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid var(--diag-black);
    flex-shrink: 0;
}

.diag-chat-popup__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.diag-chat-popup__info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.diag-chat-popup__name {
    font-size: 15px;
    font-weight: 700;
    margin: 0;
    color: var(--diag-black);
}

.diag-chat-popup__status {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--diag-gray-600);
    margin: 0;
}

.diag-chat-popup__close {
    width: 40px;
    height: 40px;
    background: transparent;
    border: 1px solid var(--diag-gray-300);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--diag-gray-600);
    transition: all var(--diag-transition-fast);
}

.diag-chat-popup__close:hover {
    border-color: var(--diag-black);
    color: var(--diag-black);
}

.diag-chat-popup__body {
    flex: 1;
    overflow-y: auto;
    padding: 24px 28px;
    min-height: 400px;
    max-height: 60vh;
    background: var(--diag-white);
    -webkit-overflow-scrolling: touch;
}

.diag-chat-popup__footer {
    padding: 16px 20px;
    border-top: 1px solid var(--diag-gray-200);
    background: var(--diag-white);
    flex-shrink: 0;
}

.diag-chat-popup__input-wrap {
    display: flex;
    gap: 10px;
    align-items: center;
    background: var(--diag-gray-100);
    border-radius: 24px;
    padding: 6px 8px 6px 16px;
}

.diag-chat-popup__input {
    flex: 1;
    padding: 10px 0;
    border: none;
    font-size: 16px;
    font-family: var(--diag-font-sans);
    background: transparent;
    min-width: 0;
}

.diag-chat-popup__input:focus {
    outline: none;
}

.diag-chat-popup__input::placeholder {
    color: var(--diag-gray-500);
}

.diag-chat-popup__send {
    width: 40px;
    height: 40px;
    min-width: 40px;
    background: var(--diag-black);
    color: var(--diag-white);
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all var(--diag-transition-fast);
}

.diag-chat-popup__send:hover {
    background: var(--diag-gray-800);
}

.diag-chat-popup__send:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ポップアップ内メッセージスタイル */
.diag-popup-message {
    margin-bottom: 16px;
    /* アニメーション削除 - カクカク防止 */
}

.diag-popup-message--bot {
    background: var(--diag-gray-50);
    padding: 16px;
    margin-left: 0;
    margin-right: 20px;
}

.diag-popup-message--user {
    display: flex;
    justify-content: flex-end;
}

.diag-popup-message--user .diag-popup-bubble {
    background: var(--diag-gray-200);
    padding: 12px 16px;
    border-radius: 18px;
    max-width: 80%;
}

.diag-popup-bubble {
    font-size: 14px;
    line-height: 1.7;
    white-space: pre-wrap;
    word-break: break-word;
}

/* ポップアップ内選択肢 */
.diag-popup-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 20px;
}

.diag-popup-option {
    width: 100%;
    padding: 12px 16px;
    min-height: 48px;
    font-size: 14px;
    font-weight: 500;
    font-family: var(--diag-font-sans);
    border: 1px solid var(--diag-gray-200);
    background: var(--diag-white);
    color: var(--diag-black);
    cursor: pointer;
    text-align: left;
    display: flex;
    align-items: center;
    border-radius: 6px;
}

.diag-popup-option:hover {
    background: var(--diag-gray-100);
    border-color: var(--diag-gray-300);
}

.diag-popup-option:active,
.diag-popup-option.selected {
    background: var(--diag-black);
    color: var(--diag-white);
    border-color: var(--diag-black);
}

/* 都道府県セレクト */
.diag-popup-select {
    width: 100%;
    padding: 12px 40px 12px 14px;
    font-size: 14px;
    font-family: var(--diag-font-sans);
    border: 1px solid var(--diag-gray-200);
    background: var(--diag-white);
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235f6368' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    margin-top: 14px;
    border-radius: 6px;
}

.diag-popup-select:focus {
    outline: none;
    border-color: var(--diag-black);
}

/* ヒントテキスト */
.diag-popup-hint {
    margin-top: 12px;
    font-size: 13px;
    color: var(--diag-gray-500);
}

/* 入力ヒント（テキスト入力モード時） */
.diag-popup-input-hint {
    margin-top: 8px;
    font-size: 12px;
    color: var(--diag-gray-400);
    text-align: center;
}

/* ショートカットボタンスタイル */
.diag-popup-options--shortcuts {
    flex-direction: row;
    flex-wrap: wrap;
    gap: 8px;
}

.diag-popup-option--shortcut {
    width: auto;
    flex: 0 0 auto;
    padding: 10px 16px;
    min-height: auto;
    font-size: 14px;
    background: var(--diag-gray-50);
    border-color: var(--diag-gray-200);
}

.diag-popup-option--shortcut:hover {
    background: var(--diag-gray-200);
}

/* 無効化されたオプションボタン */
.diag-popup-option:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}

/* 無効化されたセレクト */
.diag-popup-select:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background-color: var(--diag-gray-100);
}

/* タイピングインジケーター */
.diag-popup-typing {
    display: flex;
    gap: 6px;
    padding: 12px 16px;
    background: var(--diag-gray-50);
    margin-bottom: 16px;
}

.diag-popup-typing-dot {
    width: 8px;
    height: 8px;
    background: var(--diag-gray-400);
    border-radius: 50%;
    animation: diagTyping 1.2s infinite;
}

.diag-popup-typing-dot:nth-child(2) { animation-delay: 0.15s; }
.diag-popup-typing-dot:nth-child(3) { animation-delay: 0.3s; }

@keyframes diagTyping {
    0%, 60%, 100% { transform: scale(1); opacity: 0.4; }
    30% { transform: scale(1.2); opacity: 1; }
}

/* 結果カード（ポップアップ内） */
.diag-popup-results {
    margin-top: 16px;
}

/* ========================================
   結果サマリーパネル
   ======================================== */
.diag-results-summary {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #86efac;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
}

.diag-results-summary-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.diag-results-summary-icon {
    width: 40px;
    height: 40px;
    background: #22c55e;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.diag-results-summary-icon svg {
    width: 20px;
    height: 20px;
    color: white;
}

.diag-results-summary-title {
    font-size: 16px;
    font-weight: 700;
    color: #166534;
    margin: 0;
}

.diag-results-summary-count {
    font-size: 13px;
    color: #15803d;
    margin-top: 2px;
}

.diag-results-summary-info {
    display: flex;
    justify-content: space-around;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid #86efac;
}

.diag-summary-stat {
    text-align: center;
}

.diag-summary-stat-value {
    font-size: 18px;
    font-weight: 700;
    color: #166534;
}

.diag-summary-stat-label {
    font-size: 11px;
    color: #15803d;
}

/* ========================================
   フィードバックバー
   ======================================== */
.diag-results-feedback-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 16px;
    background: var(--diag-gray-50);
    border-radius: 8px;
    margin-bottom: 16px;
}

.diag-results-feedback-text {
    font-size: 13px;
    color: var(--diag-gray-600);
}

.diag-results-feedback-btns {
    display: flex;
    gap: 8px;
}

.diag-results-fb-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: white;
    border: 1px solid var(--diag-gray-200);
    border-radius: 6px;
    font-size: 12px;
    color: var(--diag-gray-600);
    cursor: pointer;
    transition: all 0.2s;
}

.diag-results-fb-btn:hover {
    background: var(--diag-gray-100);
}

.diag-results-fb-btn.selected.positive {
    background: #dcfce7;
    border-color: #22c55e;
    color: #166534;
}

.diag-results-fb-btn.selected.negative {
    background: #fee2e2;
    border-color: #ef4444;
    color: #dc2626;
}

.diag-results-fb-btn svg {
    flex-shrink: 0;
}

/* ========================================
   セクションタイトル
   ======================================== */
.diag-results-section-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--diag-gray-800);
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 2px solid var(--diag-black);
}

/* ========================================
   結果カード
   ======================================== */
.diag-popup-result-card {
    border: 1px solid var(--diag-gray-200);
    padding: 14px;
    margin-bottom: 10px;
    background: var(--diag-white);
    border-radius: 8px;
    transition: all 0.2s;
}

.diag-popup-result-card:hover {
    border-color: var(--diag-black);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.diag-popup-result-card--highlight {
    border-left: 3px solid var(--diag-black);
}

.diag-popup-result-header {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 10px;
}

.diag-popup-result-header-info {
    flex: 1;
}

.diag-popup-result-rank {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--diag-gray-200);
    color: var(--diag-gray-700);
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
    border-radius: 50%;
}

.diag-popup-result-rank--1 {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
}

.diag-popup-result-rank--2 {
    background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
    color: white;
}

.diag-popup-result-rank--3 {
    background: linear-gradient(135deg, #cd7c2c 0%, #b45309 100%);
    color: white;
}

.diag-popup-result-title {
    font-family: var(--diag-font-serif);
    font-size: 14px;
    font-weight: 600;
    margin: 0;
    line-height: 1.4;
}

.diag-popup-result-score {
    display: inline-block;
    padding: 2px 8px;
    background: var(--diag-black);
    color: white;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    margin-top: 4px;
}

.diag-popup-result-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 11px;
    color: var(--diag-gray-600);
    margin-bottom: 10px;
}

.diag-popup-result-amount {
    color: #2563eb;
    font-weight: 600;
}

.diag-popup-result-deadline {
    color: #dc2626;
}

.diag-popup-result-reason {
    font-size: 12px;
    color: #dc2626;
    padding: 8px 10px;
    background: #fef2f2;
    border-left: 3px solid #dc2626;
    margin-bottom: 10px;
    border-radius: 0 4px 4px 0;
}

.diag-popup-result-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.diag-popup-result-btn {
    width: 100%;
    padding: 10px 14px;
    background: var(--diag-black);
    color: var(--diag-white);
    border: none;
    font-size: 13px;
    font-weight: 600;
    font-family: var(--diag-font-sans);
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    display: block;
    border-radius: 6px;
    transition: all 0.2s;
}

.diag-popup-result-btn:hover {
    background: var(--diag-gray-800);
}

.diag-popup-result-btn--primary {
    background: var(--diag-black);
    color: white;
}

.diag-popup-result-btn--secondary {
    background: white;
    color: var(--diag-gray-700);
    border: 1px solid var(--diag-gray-300);
}

.diag-popup-result-btn--secondary:hover {
    background: var(--diag-gray-100);
}

/* ========================================
   個別フィードバック
   ======================================== */
.diag-result-feedback {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--diag-gray-100);
}

.diag-result-feedback-label {
    font-size: 12px;
    color: var(--diag-gray-500);
}

.diag-feedback-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: var(--diag-gray-100);
    border: 1px solid transparent;
    border-radius: 6px;
    color: var(--diag-gray-500);
    cursor: pointer;
    transition: all 0.2s;
}

.diag-feedback-btn:hover {
    background: var(--diag-gray-200);
    color: var(--diag-gray-700);
}

.diag-feedback-btn.selected[data-feedback="positive"] {
    background: #dcfce7;
    border-color: #22c55e;
    color: #166534;
}

.diag-feedback-btn.selected[data-feedback="negative"] {
    background: #fee2e2;
    border-color: #ef4444;
    color: #dc2626;
}

/* ========================================
   再調整パネル
   ======================================== */
.diag-readjust-panel {
    margin-top: 16px;
    padding: 16px;
    background: var(--diag-gray-50);
    border-radius: 8px;
}

.diag-readjust-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--diag-gray-700);
    margin-bottom: 12px;
}

.diag-readjust-header svg {
    color: var(--diag-gray-500);
}

.diag-readjust-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

.diag-readjust-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 12px;
    background: white;
    border: 1px solid var(--diag-gray-200);
    border-radius: 6px;
    font-size: 12px;
    color: var(--diag-gray-700);
    cursor: pointer;
    transition: all 0.2s;
}

.diag-readjust-btn:hover {
    border-color: var(--diag-black);
    background: var(--diag-gray-100);
}

.diag-readjust-btn svg {
    flex-shrink: 0;
    color: var(--diag-gray-500);
}

/* ポップアップ モバイル対応 */
@media (max-width: 768px) {
    .diag-chat-popup__container {
        width: 100%;
        max-width: none;
        max-height: 100vh;
        height: 100%;
        border: none;
        box-shadow: none;
    }
    
    .diag-chat-popup__body {
        max-height: none;
        flex: 1;
    }
    
    .diag-chat-popup__footer {
        padding-bottom: calc(16px + env(safe-area-inset-bottom, 0px));
    }
}

/* セカンダリボタンスタイル */
.diag-btn--secondary {
    background-color: var(--diag-white);
    color: var(--diag-black);
}

.diag-btn--secondary:hover {
    background-color: var(--diag-black);
    color: var(--diag-white);
}
</style>

<!-- ============================================
     SCRIPTS - 利用規約モーダル＆スムーススクロールのみ
     （チャット機能は inc/ai-concierge.php から提供）
     ============================================ -->
<script>
(function() {
    'use strict';
    
    // ===========================================
    // 利用規約モーダル
    // ===========================================
    
    const termsModal = document.getElementById('termsModal');
    const openTermsBtn = document.getElementById('openTermsModal');
    const closeTermsBtn = document.getElementById('closeTermsModal');
    
    function openTermsModal() {
        termsModal?.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    
    function closeTermsModal() {
        termsModal?.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }
    
    openTermsBtn?.addEventListener('click', openTermsModal);
    closeTermsBtn?.addEventListener('click', closeTermsModal);
    termsModal?.querySelector('[data-close-modal]')?.addEventListener('click', closeTermsModal);
    termsModal?.querySelectorAll('[data-close-modal]').forEach(el => {
        el.addEventListener('click', closeTermsModal);
    });
    
    // ESCキーで利用規約モーダルを閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && termsModal?.getAttribute('aria-hidden') === 'false') {
            closeTermsModal();
        }
    });
    
    // ===========================================
    // スムーススクロール
    // ===========================================
    
    document.querySelectorAll('.smooth-scroll').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href.startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
    
})();
</script>

<?php get_footer(); ?>