<?php
/**
 * Plugin Name: Grant Article Creator Pro
 * Plugin URI: https://example.com/grant-article-creator
 * Description: 補助金記事データをペーストするだけでカスタム投稿タイプ「grant」に新規投稿を作成するプラグイン
 * Version: 4.0.0
 * Author: GI Web Team
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gi-grant-creator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// 直接アクセス禁止
if (!defined('ABSPATH')) {
    exit;
}

/**
 * メインクラス
 */
class GI_Grant_Article_Creator_Pro {
    
    /**
     * プラグインバージョン
     */
    private $version = '4.0.0';
    
    /**
     * カスタム投稿タイプ名
     */
    private $post_type = 'grant';
    
    /**
     * タクソノミー設定
     */
    private $taxonomies = array(
        'grant_category' => array(
            'hierarchical' => true,
            'label' => 'カテゴリ'
        ),
        'grant_tag' => array(
            'hierarchical' => false,
            'label' => 'タグ'
        ),
        'grant_prefecture' => array(
            'hierarchical' => true,
            'label' => '都道府県'
        ),
        'grant_municipality' => array(
            'hierarchical' => true,
            'label' => '市区町村'
        )
    );
    
    /**
     * カスタムフィールド名の一覧（ACF設定と完全一致）
     */
    private $field_names = array(
        // 投稿設定
        'post_status',
        
        // 基本情報
        'organization',
        'organization_type',
        'grant_number',
        
        // 金額情報
        'max_amount',
        'max_amount_numeric',
        'min_amount',
        'min_amount_numeric',
        'subsidy_rate',
        'subsidy_rate_detailed',
        'subsidy_rate_max',
        'subsidy_rate_min',
        
        // 締切・ステータス
        'deadline',
        'deadline_date',
        'start_date',
        'application_period',
        'next_deadline',
        'application_status',
        
        // 対象・条件
        'grant_target',
        'target_company_size',
        'target_employee_count',
        'target_capital',
        'target_years_in_business',
        'eligible_expenses',
        'eligible_expenses_detailed',
        'eligible_expenses_list',
        'ineligible_expenses',
        'ineligible_expenses_list',
        
        // 難易度・採択
        'grant_difficulty',
        'difficulty_level',
        'adoption_rate',
        'adoption_count',
        'application_count',
        'budget_total',
        'budget_remaining',
        'preparation_days',
        'review_period',
        'review_period_days',
        
        // 書類
        'required_documents',
        'required_documents_detailed',
        'required_documents_list',
        
        // 地域情報
        'regional_limitation',
        'area_notes',
        'grant_prefecture',
        'grant_municipality',
        
        // 申請・連絡先
        'application_method',
        'application_flow',
        'application_flow_steps',
        'application_tips',
        'common_mistakes',
        'success_points',
        'contact_info',
        'contact_phone',
        'contact_email',
        'contact_hours',
        'official_url',
        'application_url',
        'guideline_url',
        'external_link',
        
        // 分類（タクソノミー）
        'grant_category',
        'grant_tag',
        
        // フラグ
        'is_featured',
        'is_new',
        'is_popular',
        'online_application',
        'jgrants_available',
        
        // 管理
        'priority_order',
        'views_count',
        'bookmark_count',
        'admin_notes',
        'ai_summary',
        
        // 成功事例
        'success_cases',
        'similar_grants',
        'comparison_points',
        
        // 監修者
        'supervisor_name',
        'supervisor_title',
        'supervisor_profile',
        'supervisor_url',
        'supervisor_credentials',
        
        // ソース
        'source_url',
        'source_name',
        'last_verified_date',
        'update_history',
        
        // FAQ・関連
        'faq_items',
        'related_columns',
        'related_grants',
        
        // 構造化データ
        'structured_data_json'
    );
    
    /**
     * フィールドのラベル
     */
    private $field_labels = array(
        'post_status' => '公開状態',
        'organization' => '実施組織',
        'organization_type' => '組織タイプ',
        'grant_number' => '補助金番号',
        'max_amount' => '最大助成額（テキスト）',
        'max_amount_numeric' => '最大助成額（数値）',
        'min_amount' => '最小助成額（テキスト）',
        'min_amount_numeric' => '最小助成額（数値）',
        'subsidy_rate' => '補助率',
        'subsidy_rate_detailed' => '補助率（詳細）',
        'subsidy_rate_max' => '補助率上限',
        'subsidy_rate_min' => '補助率下限',
        'deadline' => '締切（表示用）',
        'deadline_date' => '締切日',
        'start_date' => '開始日',
        'application_period' => '申請期間',
        'next_deadline' => '次回締切',
        'application_status' => '申請ステータス',
        'grant_target' => '対象者・対象事業',
        'target_company_size' => '対象企業規模',
        'target_employee_count' => '対象従業員数',
        'target_capital' => '対象資本金',
        'target_years_in_business' => '対象業歴',
        'eligible_expenses' => '対象経費',
        'eligible_expenses_detailed' => '対象経費（詳細）',
        'eligible_expenses_list' => '対象経費リスト',
        'ineligible_expenses' => '対象外経費',
        'ineligible_expenses_list' => '対象外経費リスト',
        'grant_difficulty' => '申請難易度（旧）',
        'difficulty_level' => '申請難易度',
        'adoption_rate' => '採択率',
        'adoption_count' => '採択件数',
        'application_count' => '申請件数',
        'budget_total' => '予算総額',
        'budget_remaining' => '残り予算',
        'preparation_days' => '準備日数',
        'review_period' => '審査期間',
        'review_period_days' => '審査日数',
        'required_documents' => '必要書類',
        'required_documents_detailed' => '必要書類（詳細）',
        'required_documents_list' => '必要書類リスト',
        'regional_limitation' => '地域制限',
        'area_notes' => '地域に関する備考',
        'grant_prefecture' => '都道府県',
        'grant_municipality' => '市区町村',
        'application_method' => '申請方法',
        'application_flow' => '申請フロー',
        'application_flow_steps' => '申請ステップ',
        'application_tips' => '申請のコツ',
        'common_mistakes' => 'よくある失敗',
        'success_points' => '成功ポイント',
        'contact_info' => '問い合わせ先',
        'contact_phone' => '電話番号',
        'contact_email' => 'メールアドレス',
        'contact_hours' => '受付時間',
        'official_url' => '公式URL',
        'application_url' => '申請URL',
        'guideline_url' => 'ガイドラインURL',
        'external_link' => '外部リンク',
        'grant_category' => 'カテゴリ',
        'grant_tag' => 'タグ',
        'is_featured' => '注目の助成金',
        'is_new' => '新着',
        'is_popular' => '人気',
        'online_application' => 'オンライン申請可',
        'jgrants_available' => 'jGrants対応',
        'priority_order' => '表示優先度',
        'views_count' => '閲覧数',
        'bookmark_count' => 'ブックマーク数',
        'admin_notes' => '管理者メモ',
        'ai_summary' => 'AI要約',
        'success_cases' => '成功事例',
        'similar_grants' => '類似補助金',
        'comparison_points' => '比較ポイント',
        'supervisor_name' => '監修者名',
        'supervisor_title' => '監修者肩書',
        'supervisor_profile' => '監修者プロフィール',
        'supervisor_url' => '監修者URL',
        'supervisor_credentials' => '監修者資格',
        'source_url' => 'ソースURL',
        'source_name' => 'ソース名',
        'last_verified_date' => '最終確認日',
        'update_history' => '更新履歴',
        'faq_items' => 'FAQ',
        'related_columns' => '関連コラム',
        'related_grants' => '関連補助金',
        'structured_data_json' => '構造化データ'
    );
    
    /**
     * 都道府県リスト
     */
    private $prefectures = array(
        '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
        '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
        '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県',
        '岐阜県', '静岡県', '愛知県', '三重県',
        '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県',
        '鳥取県', '島根県', '岡山県', '広島県', '山口県',
        '徳島県', '香川県', '愛媛県', '高知県',
        '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
    );
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * フック初期化
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_gi_grant_parse', array($this, 'ajax_parse_data'));
        add_action('wp_ajax_gi_grant_create', array($this, 'ajax_create_post'));
        add_action('wp_ajax_gi_grant_preview', array($this, 'ajax_preview'));
        add_action('wp_ajax_gi_grant_template', array($this, 'ajax_get_template'));
        add_action('wp_ajax_gi_grant_get_municipalities', array($this, 'ajax_get_municipalities'));
    }
    
    /**
     * 管理メニュー追加
     */
    public function add_admin_menu() {
        add_menu_page(
            '補助金記事作成',
            '補助金記事作成',
            'edit_posts',
            'gi-grant-creator',
            array($this, 'render_admin_page'),
            'dashicons-plus-alt',
            26
        );
    }
    
    /**
     * カスタム投稿タイプの存在チェック
     */
    private function check_post_type_exists() {
        return post_type_exists($this->post_type);
    }
    
    /**
     * カスタム投稿タイプのタクソノミー取得
     */
    private function get_post_type_taxonomies() {
        $taxonomies = get_object_taxonomies($this->post_type, 'objects');
        return $taxonomies;
    }
    
    /**
     * AJAX: 市区町村取得
     */
    public function ajax_get_municipalities() {
        if (!check_ajax_referer('gi_grant_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'セキュリティチェックに失敗しました'));
            return;
        }
        
        $prefecture = isset($_POST['prefecture']) ? sanitize_text_field($_POST['prefecture']) : '';
        
        if (empty($prefecture)) {
            wp_send_json_error(array('message' => '都道府県を指定してください'));
            return;
        }
        
        // grant_municipality タクソノミーが存在するか確認
        if (!taxonomy_exists('grant_municipality')) {
            wp_send_json_success(array('municipalities' => array()));
            return;
        }
        
        // 都道府県に関連する市区町村を取得
        $terms = get_terms(array(
            'taxonomy' => 'grant_municipality',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'prefecture',
                    'value' => $prefecture,
                    'compare' => '='
                )
            )
        ));
        
        $municipalities = array();
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $municipalities[] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug
                );
            }
        }
        
        wp_send_json_success(array('municipalities' => $municipalities));
    }
    
    /**
     * AJAX: データ解析
     */
    public function ajax_parse_data() {
        // nonceチェック
        if (!check_ajax_referer('gi_grant_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'セキュリティチェックに失敗しました'));
            return;
        }
        
        // 権限チェック
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => '権限がありません'));
            return;
        }
        
        // データ取得
        $raw_data = isset($_POST['raw_data']) ? wp_unslash($_POST['raw_data']) : '';
        
        if (empty(trim($raw_data))) {
            wp_send_json_error(array('message' => 'データが入力されていません'));
            return;
        }
        
        // パース実行
        $parsed = $this->parse_data($raw_data);
        
        // タイトルチェック
        if (empty($parsed['title'])) {
            wp_send_json_error(array(
                'message' => 'タイトルを抽出できませんでした。データ形式を確認してください。',
                'parsed' => $parsed
            ));
            return;
        }
        
        // フィールド数カウント
        $field_count = 0;
        foreach ($parsed as $key => $value) {
            if (!empty($value) && $value !== '-') {
                $field_count++;
            }
        }
        
        wp_send_json_success(array(
            'parsed' => $parsed,
            'field_count' => $field_count,
            'message' => "正常にパースされました（{$field_count}フィールド検出）"
        ));
    }
    
    /**
     * データパース処理
     */
    private function parse_data($raw_data) {
        $result = array(
            'title' => '',
            'meta_description' => '',
            'content' => '',
            'post_status' => 'draft',
            'structured_data_json' => ''
        );
        
        // ===区切り形式の場合
        if (strpos($raw_data, '===') !== false) {
            return $this->parse_delimiter_format($raw_data);
        }
        
        // ###区切り形式の場合
        if (strpos($raw_data, '###') !== false) {
            return $this->parse_hash_format($raw_data);
        }
        
        // 従来形式
        return $this->parse_legacy_format($raw_data);
    }
    
    /**
     * ===区切り形式のパース
     */
    private function parse_delimiter_format($raw_data) {
        $result = array(
            'title' => '',
            'meta_description' => '',
            'content' => '',
            'post_status' => 'draft',
            'structured_data_json' => ''
        );
        
        // 正規化: ===SECTION=== の前後に改行を追加
        $normalized = preg_replace('/([^\n\r])===([A-Z_]+)===/s', "$1\n===\$2===", $raw_data);
        $normalized = preg_replace('/===([A-Z_]+)===([^\n\r])/s', "===\$1===\n\$2", $normalized);
        
        // セクション分割
        $parts = preg_split('/===([A-Z_]+)===/', $normalized, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $current_key = null;
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }
            
            if (preg_match('/^[A-Z_]+$/', $part)) {
                $current_key = strtolower($part);
            } elseif ($current_key !== null) {
                $result[$current_key] = $part;
                $current_key = null;
            }
        }
        
        // CONTENTから構造化データを抽出
        if (!empty($result['content'])) {
            $result = $this->extract_structured_data($result);
        }
        
        // STRUCTURED_DATAセクションがあれば統合
        if (!empty($result['structured_data'])) {
            $result['structured_data_json'] = $result['structured_data'];
            unset($result['structured_data']);
        }
        
        // FIELDSセクションを個別フィールドに展開
        if (!empty($result['fields'])) {
            $this->expand_fields_section($result);
        }
        
        return $result;
    }
    
    /**
     * 構造化データを本文から抽出
     */
    private function extract_structured_data($result) {
        $content = $result['content'];
        $structured_data = '';
        
        // コメントマーカー形式で囲まれた構造化データを抽出
        if (preg_match('/<!--STRUCTURED_DATA_START-->(.*?)<!--STRUCTURED_DATA_END-->/is', $content, $match)) {
            $structured_data = trim($match[1]);
            $content = preg_replace('/<!--STRUCTURED_DATA_START-->.*?<!--STRUCTURED_DATA_END-->/is', '', $content);
        }
        
        // script type='application/ld+json' タグを抽出
        if (preg_match_all('/<script\s+type=[\'"]application\/ld\+json[\'"]\s*>(.*?)<\/script>/is', $content, $matches)) {
            if (!empty($matches[0])) {
                $structured_data .= "\n" . implode("\n", $matches[0]);
                $content = preg_replace('/<script\s+type=[\'"]application\/ld\+json[\'"]\s*>.*?<\/script>/is', '', $content);
            }
        }
        
        // 生のJSONオブジェクトを検出（{ "@context": で始まるもの）
        if (preg_match_all('/\{\s*["\']@context["\']\s*:\s*["\']https?:\/\/schema\.org["\'].*?\}(?=\s*\{|\s*$)/is', $content, $matches)) {
            foreach ($matches[0] as $json) {
                // 有効なJSONか確認
                $decoded = json_decode($json);
                if ($decoded !== null) {
                    $structured_data .= "\n<script type='application/ld+json'>\n" . $json . "\n</script>";
                    $content = str_replace($json, '', $content);
                }
            }
        }
        
        $result['content'] = trim($content);
        $result['structured_data_json'] = trim($structured_data);
        
        return $result;
    }
    
    /**
     * ###区切り形式のパース
     */
    private function parse_hash_format($raw_data) {
        $result = array(
            'title' => '',
            'meta_description' => '',
            'content' => '',
            'post_status' => 'draft',
            'structured_data_json' => ''
        );
        
        // 正規化
        $normalized = preg_replace('/([^\n\r])###([A-Z_]+)###/s', "$1\n###\$2###", $raw_data);
        $normalized = preg_replace('/###([A-Z_]+)###([^\n\r])/s', "###\$1###\n\$2", $normalized);
        
        // セクション分割
        $parts = preg_split('/###([A-Z_]+)###/', $normalized, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $current_key = null;
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }
            
            if (preg_match('/^[A-Z_]+$/', $part)) {
                $current_key = strtolower($part);
            } elseif ($current_key !== null) {
                $result[$current_key] = $part;
                $current_key = null;
            }
        }
        
        // CONTENTから構造化データを抽出
        if (!empty($result['content'])) {
            $result = $this->extract_structured_data($result);
        }
        
        // FIELDSセクションを個別フィールドに展開
        if (!empty($result['fields'])) {
            $this->expand_fields_section($result);
        }
        
        return $result;
    }
    
    /**
     * 従来形式のパース
     */
    private function parse_legacy_format($raw_data) {
        $result = array(
            'title' => '',
            'meta_description' => '',
            'content' => '',
            'post_status' => 'draft',
            'structured_data_json' => ''
        );
        
        $lines = preg_split('/[\r\n]+/', $raw_data);
        $current_section = 'title';
        $content_buffer = array();
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            // セクション検出
            if (preg_match('/^メタディスクリプション/u', $trimmed)) {
                $current_section = 'meta';
                continue;
            }
            if (preg_match('/^HTML本文/u', $trimmed)) {
                $current_section = 'content';
                continue;
            }
            if (preg_match('/^データフィールド/u', $trimmed)) {
                $current_section = 'fields';
                continue;
            }
            if ($trimmed === 'フィールド	値' || $trimmed === 'フィールド\t値') {
                continue;
            }
            
            // 空行処理
            if (empty($trimmed) && $current_section !== 'content') {
                continue;
            }
            
            // セクション別処理
            switch ($current_section) {
                case 'title':
                    if (empty($result['title'])) {
                        $result['title'] = $trimmed;
                    }
                    break;
                    
                case 'meta':
                    if (empty($result['meta_description'])) {
                        $result['meta_description'] = $trimmed;
                    }
                    break;
                    
                case 'content':
                    $content_buffer[] = $line;
                    break;
                    
                case 'fields':
                    if (preg_match('/^([a-z_]+)[\s\t]+(.+)$/i', $trimmed, $matches)) {
                        $key = strtolower(trim($matches[1]));
                        $value = trim($matches[2]);
                        if (!empty($value) && $value !== '-') {
                            $result[$key] = $value;
                        }
                    }
                    break;
            }
        }
        
        $result['content'] = implode("\n", $content_buffer);
        
        // 構造化データを抽出
        $result = $this->extract_structured_data($result);
        
        return $result;
    }
    
    /**
     * FIELDSセクションを個別フィールドに展開
     */
    private function expand_fields_section(&$result) {
        $fields_content = $result['fields'];
        unset($result['fields']);
        
        // フィールド名の前に改行を挿入（改行なしデータ対応）
        foreach ($this->field_names as $field_name) {
            $pattern = '/(?<=[a-zA-Z0-9_,\s])(' . preg_quote($field_name, '/') . '\s*:)/i';
            $fields_content = preg_replace($pattern, "\n\$1", $fields_content);
        }
        
        // 各行をパース
        $lines = preg_split('/[\r\n]+/', $fields_content);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            
            // key: value 形式をパース
            if (preg_match('/^([a-z_]+)\s*:\s*(.*)$/i', $line, $matches)) {
                $key = strtolower(trim($matches[1]));
                $value = trim($matches[2]);
                
                // 次のフィールド名が含まれている場合、そこで切り取る
                foreach ($this->field_names as $next_field) {
                    $pos = stripos($value, $next_field . ':');
                    if ($pos !== false && $pos > 0) {
                        $value = trim(substr($value, 0, $pos));
                        break;
                    }
                }
                
                if ($value !== '' && $value !== '-') {
                    $result[$key] = $value;
                }
            }
        }
    }
    
    /**
     * AJAX: 投稿作成
     */
    public function ajax_create_post() {
        // nonceチェック
        if (!check_ajax_referer('gi_grant_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'セキュリティチェックに失敗しました'));
            return;
        }
        
        // 権限チェック
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => '投稿を作成する権限がありません'));
            return;
        }
        
        // カスタム投稿タイプの存在チェック
        if (!$this->check_post_type_exists()) {
            wp_send_json_error(array('message' => 'カスタム投稿タイプ「' . $this->post_type . '」が存在しません。先にカスタム投稿タイプを登録してください。'));
            return;
        }
        
        // データ取得
        $json_data = isset($_POST['data']) ? wp_unslash($_POST['data']) : '{}';
        $data = json_decode($json_data, true);
        
        if (empty($data) || !is_array($data)) {
            wp_send_json_error(array('message' => 'データの形式が正しくありません'));
            return;
        }
        
        // タイトルチェック
        if (empty($data['title'])) {
            wp_send_json_error(array('message' => 'タイトルが必要です'));
            return;
        }
        
        // 本文から構造化データを分離
        $content = isset($data['content']) ? $data['content'] : '';
        $structured_data = isset($data['structured_data_json']) ? $data['structured_data_json'] : '';
        
        // 投稿データ作成（カスタム投稿タイプ「grant」を指定）
        $post_data = array(
            'post_title'   => sanitize_text_field($data['title']),
            'post_content' => $content, // 後でサニタイズ
            'post_excerpt' => sanitize_textarea_field($data['meta_description']),
            'post_status'  => sanitize_key($data['post_status']),
            'post_type'    => $this->post_type,
            'post_author'  => get_current_user_id(),
        );
        
        // 投稿作成
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => '投稿作成に失敗しました: ' . $post_id->get_error_message()));
            return;
        }
        
        // カスタムフィールド保存
        $saved_fields = array();
        
        // メタディスクリプション
        if (!empty($data['meta_description'])) {
            $meta_desc = sanitize_textarea_field($data['meta_description']);
            
            // カスタムフィールド
            update_post_meta($post_id, 'meta_description', $meta_desc);
            $saved_fields[] = 'meta_description';
            
            // SEOプラグイン対応
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_desc);
            update_post_meta($post_id, '_aioseo_description', $meta_desc);
            update_post_meta($post_id, 'rank_math_description', $meta_desc);
            update_post_meta($post_id, '_seopress_titles_desc', $meta_desc);
        }
        
        // 構造化データを保存
        if (!empty($structured_data)) {
            update_post_meta($post_id, 'structured_data_json', $structured_data);
            $saved_fields[] = 'structured_data_json';
        }
        
        // ACFフィールドの保存（ACFが有効な場合はACF関数を使用）
        $use_acf = function_exists('update_field');
        
        // その他のカスタムフィールド
        foreach ($this->field_names as $field) {
            if ($field === 'post_status' || $field === 'grant_category' || $field === 'grant_tag' || $field === 'grant_prefecture' || $field === 'grant_municipality') {
                continue; // タクソノミーは別処理
            }
            
            if (isset($data[$field]) && $data[$field] !== '' && $data[$field] !== '-') {
                $value = $this->sanitize_field_value($field, $data[$field]);
                
                if ($use_acf) {
                    update_field($field, $value, $post_id);
                } else {
                    update_post_meta($post_id, $field, $value);
                }
                $saved_fields[] = $field;
            }
        }
        
        // タクソノミーを設定
        $taxonomy_results = $this->set_taxonomies($post_id, $data);
        
        // 成功レスポンス
        wp_send_json_success(array(
            'post_id' => $post_id,
            'edit_url' => admin_url('post.php?post=' . $post_id . '&action=edit'),
            'view_url' => get_permalink($post_id),
            'saved_fields' => $saved_fields,
            'taxonomy_results' => $taxonomy_results,
            'post_type' => $this->post_type,
            'message' => '補助金記事を作成しました（ID: ' . $post_id . '）'
        ));
    }
    
    /**
     * タクソノミーを設定
     */
    private function set_taxonomies($post_id, $data) {
        $results = array();
        $registered_taxonomies = $this->get_post_type_taxonomies();
        
        // カテゴリ設定
        if (!empty($data['grant_category'])) {
            $result = $this->set_taxonomy_terms($post_id, 'grant_category', $data['grant_category'], $registered_taxonomies);
            $results['grant_category'] = $result;
        }
        
        // タグ設定
        if (!empty($data['grant_tag'])) {
            $result = $this->set_taxonomy_terms($post_id, 'grant_tag', $data['grant_tag'], $registered_taxonomies);
            $results['grant_tag'] = $result;
        }
        
        // 都道府県設定
        if (!empty($data['grant_prefecture'])) {
            $result = $this->set_taxonomy_terms($post_id, 'grant_prefecture', $data['grant_prefecture'], $registered_taxonomies);
            $results['grant_prefecture'] = $result;
        }
        
        // 市区町村設定
        if (!empty($data['grant_municipality'])) {
            $result = $this->set_taxonomy_terms($post_id, 'grant_municipality', $data['grant_municipality'], $registered_taxonomies);
            $results['grant_municipality'] = $result;
        }
        
        return $results;
    }
    
    /**
     * タクソノミーターム設定
     */
    private function set_taxonomy_terms($post_id, $taxonomy_key, $terms_string, $registered_taxonomies) {
        // 実際のタクソノミー名を検索
        $taxonomy_name = null;
        
        // 直接タクソノミー名で検索
        if (isset($registered_taxonomies[$taxonomy_key])) {
            $taxonomy_name = $taxonomy_key;
        } else {
            // 階層型/非階層型で検索
            foreach ($registered_taxonomies as $tax_name => $tax_obj) {
                // カテゴリ系（階層型）
                if ($taxonomy_key === 'grant_category' && $tax_obj->hierarchical) {
                    $taxonomy_name = $tax_name;
                    break;
                }
                // タグ系（非階層型、都道府県・市区町村以外）
                if ($taxonomy_key === 'grant_tag' && !$tax_obj->hierarchical && 
                    strpos($tax_name, 'prefecture') === false && strpos($tax_name, 'municipality') === false) {
                    $taxonomy_name = $tax_name;
                    break;
                }
                // 都道府県
                if ($taxonomy_key === 'grant_prefecture' && strpos($tax_name, 'prefecture') !== false) {
                    $taxonomy_name = $tax_name;
                    break;
                }
                // 市区町村
                if ($taxonomy_key === 'grant_municipality' && strpos($tax_name, 'municipality') !== false) {
                    $taxonomy_name = $tax_name;
                    break;
                }
            }
        }
        
        if (!$taxonomy_name) {
            return array(
                'status' => 'skipped',
                'message' => "タクソノミー '{$taxonomy_key}' が見つかりません"
            );
        }
        
        // カンマ区切りでターム名を分割
        $term_names = array_filter(array_map('trim', explode(',', $terms_string)));
        
        if (empty($term_names)) {
            return array(
                'status' => 'skipped',
                'message' => 'ターム名が空です'
            );
        }
        
        $term_ids = array();
        $created = array();
        $found = array();
        
        foreach ($term_names as $term_name) {
            if (empty($term_name)) {
                continue;
            }
            
            // 名前で検索
            $term = get_term_by('name', $term_name, $taxonomy_name);
            
            if ($term) {
                $term_ids[] = $term->term_id;
                $found[] = $term_name;
            } else {
                // スラッグで検索
                $term = get_term_by('slug', sanitize_title($term_name), $taxonomy_name);
                
                if ($term) {
                    $term_ids[] = $term->term_id;
                    $found[] = $term_name;
                } else {
                    // 新規作成
                    $new_term = wp_insert_term($term_name, $taxonomy_name, array(
                        'slug' => sanitize_title($term_name)
                    ));
                    
                    if (!is_wp_error($new_term)) {
                        $term_ids[] = $new_term['term_id'];
                        $created[] = $term_name;
                    }
                }
            }
        }
        
        // タームを設定
        if (!empty($term_ids)) {
            $set_result = wp_set_object_terms($post_id, $term_ids, $taxonomy_name);
            
            if (is_wp_error($set_result)) {
                return array(
                    'status' => 'error',
                    'message' => $set_result->get_error_message()
                );
            }
        }
        
        return array(
            'status' => 'success',
            'taxonomy' => $taxonomy_name,
            'found' => $found,
            'created' => $created,
            'total' => count($term_ids)
        );
    }
    
    /**
     * フィールド値のサニタイズ
     */
    private function sanitize_field_value($field, $value) {
        // 数値フィールド
        $numeric_fields = array(
            'max_amount_numeric', 'min_amount_numeric', 'min_amount',
            'adoption_rate', 'adoption_count', 'application_count',
            'budget_total', 'budget_remaining', 'preparation_days', 'review_period_days',
            'priority_order', 'views_count', 'bookmark_count',
            'subsidy_rate_max', 'subsidy_rate_min'
        );
        if (in_array($field, $numeric_fields)) {
            return floatval($value);
        }
        
        // URLフィールド
        $url_fields = array('official_url', 'application_url', 'guideline_url', 'external_link', 'source_url', 'supervisor_url');
        if (in_array($field, $url_fields)) {
            return esc_url_raw($value);
        }
        
        // ブールフィールド
        $bool_fields = array('is_featured', 'is_new', 'is_popular', 'online_application', 'jgrants_available');
        if (in_array($field, $bool_fields)) {
            return ($value === '1' || $value === 'true' || $value === true || $value === 1) ? 1 : 0;
        }
        
        // WYSIWYGフィールド（HTMLを許可）
        $wysiwyg_fields = array(
            'grant_target', 'eligible_expenses', 'eligible_expenses_detailed',
            'ineligible_expenses', 'required_documents', 'required_documents_detailed',
            'application_flow', 'application_tips', 'common_mistakes', 'success_points',
            'ai_summary', 'supervisor_profile'
        );
        if (in_array($field, $wysiwyg_fields)) {
            return wp_kses_post($value);
        }
        
        // 配列フィールド（JSON文字列の場合）
        $array_fields = array(
            'target_company_size', 'eligible_expenses_list', 'ineligible_expenses_list',
            'required_documents_list', 'application_flow_steps', 'success_cases',
            'similar_grants', 'comparison_points', 'supervisor_credentials',
            'update_history', 'faq_items', 'related_columns', 'related_grants'
        );
        if (in_array($field, $array_fields)) {
            // JSON文字列の場合はデコード、そうでなければそのまま
            $decoded = json_decode($value, true);
            if ($decoded !== null) {
                return $decoded;
            }
            // カンマ区切りの場合は配列に変換
            if (strpos($value, ',') !== false) {
                return array_filter(array_map('trim', explode(',', $value)));
            }
            return $value;
        }
        
        // テキストエリアフィールド
        $textarea_fields = array(
            'contact_info', 'area_notes', 'admin_notes', 'application_period',
            'review_period', 'contact_hours'
        );
        if (in_array($textarea_fields, $textarea_fields)) {
            return sanitize_textarea_field($value);
        }
        
        // デフォルト: テキストフィールド
        return sanitize_text_field($value);
    }
    
    /**
     * AJAX: プレビュー
     */
    public function ajax_preview() {
        // nonceチェック
        if (!check_ajax_referer('gi_grant_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'セキュリティチェックに失敗しました'));
            return;
        }
        
        // データ取得
        $json_data = isset($_POST['data']) ? wp_unslash($_POST['data']) : '{}';
        $data = json_decode($json_data, true);
        
        if (empty($data['title'])) {
            wp_send_json_error(array('message' => 'タイトルがありません'));
            return;
        }
        
        // プレビューHTML生成
        $html = $this->generate_preview_html($data);
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * プレビューHTML生成
     */
    private function generate_preview_html($data) {
        ob_start();
        ?>
        <div style="max-width: 900px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, 'Noto Sans JP', sans-serif;">
            
            <div style="background: #dbeafe; border: 1px solid #3b82f6; padding: 12px 16px; margin-bottom: 20px; border-radius: 6px; font-size: 13px; color: #1e40af;">
                <strong>投稿タイプ:</strong> <?php echo esc_html($this->post_type); ?>（カスタム投稿タイプ）
            </div>
            
            <h1 style="font-size: 24px; font-weight: 700; margin: 0 0 24px; line-height: 1.5; color: #111;">
                <?php echo esc_html($data['title']); ?>
            </h1>
            
            <?php if (!empty($data['meta_description'])): ?>
            <div style="background: #f0f9ff; border: 2px solid #0369a1; padding: 20px; margin-bottom: 24px; border-radius: 8px;">
                <div style="font-size: 12px; font-weight: 700; color: #0369a1; margin-bottom: 8px;">
                    抜粋 / META DESCRIPTION（<?php echo mb_strlen($data['meta_description']); ?>文字）
                </div>
                <p style="margin: 0; color: #333; font-size: 15px; line-height: 1.8;">
                    <?php echo esc_html($data['meta_description']); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; margin-bottom: 24px;">
                <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 16px; padding-bottom: 12px; border-bottom: 2px solid #111;">
                    基本情報
                </h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <?php
                    $info_fields = array(
                        'max_amount' => '最大助成額',
                        'deadline' => '締切',
                        'application_status' => '申請ステータス',
                        'organization' => '実施組織',
                        'grant_target' => '対象者',
                        'grant_prefecture' => '都道府県',
                        'grant_municipality' => '市区町村',
                        'official_url' => '公式URL',
                        'adoption_rate' => '採択率',
                        'difficulty_level' => '難易度',
                    );
                    
                    foreach ($info_fields as $key => $label):
                        if (empty($data[$key])) continue;
                    ?>
                    <tr>
                        <th style="text-align: left; padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; width: 30%; font-size: 13px; font-weight: 600;">
                            <?php echo esc_html($label); ?>
                        </th>
                        <td style="padding: 10px 12px; border: 1px solid #e5e7eb; font-size: 14px;">
                            <?php 
                            if ($key === 'official_url') {
                                echo '<a href="' . esc_url($data[$key]) . '" target="_blank" rel="noopener noreferrer" style="color: #0369a1;">' . esc_html($data[$key]) . '</a>';
                            } elseif ($key === 'adoption_rate') {
                                echo esc_html($data[$key]) . '%';
                            } else {
                                echo esc_html($data[$key]); 
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
            <?php if (!empty($data['structured_data_json'])): ?>
            <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                <div style="font-size: 12px; font-weight: 700; color: #92400e; margin-bottom: 8px;">
                    構造化データ（別フィールドに保存されます）
                </div>
                <pre style="margin: 0; font-size: 11px; color: #78350f; white-space: pre-wrap; word-break: break-all; max-height: 150px; overflow: auto;">
<?php echo esc_html(substr($data['structured_data_json'], 0, 500)); ?><?php echo strlen($data['structured_data_json']) > 500 ? '...' : ''; ?>
                </pre>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($data['content'])): ?>
            <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; margin-bottom: 24px;">
                <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 16px; padding-bottom: 12px; border-bottom: 2px solid #111;">
                    本文プレビュー
                </h3>
                <div style="font-size: 15px; line-height: 1.9; color: #333; max-height: 400px; overflow: auto;">
                    <?php echo $data['content']; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
                <h3 style="font-size: 14px; font-weight: 700; margin: 0 0 16px; color: #666;">
                    カスタムフィールド一覧
                </h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-size: 12px; max-height: 300px; overflow: auto;">
                    <?php 
                    $display_fields = array_slice($this->field_labels, 0, 40);
                    foreach ($display_fields as $key => $label): 
                    ?>
                    <div style="display: flex; gap: 8px;">
                        <span style="color: #888; min-width: 140px; flex-shrink: 0;"><?php echo esc_html($label); ?>:</span>
                        <span style="font-weight: 500; color: <?php echo empty($data[$key]) ? '#ccc' : '#333'; ?>; word-break: break-all;">
                            <?php 
                            $val = isset($data[$key]) ? $data[$key] : '';
                            if (is_array($val)) {
                                echo esc_html(implode(', ', $val));
                            } else {
                                echo esc_html(mb_substr($val, 0, 50) . (mb_strlen($val) > 50 ? '...' : ''));
                            }
                            ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX: テンプレート取得
     */
    public function ajax_get_template() {
        // nonceチェック
        if (!check_ajax_referer('gi_grant_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'セキュリティチェックに失敗しました'));
            return;
        }
        
        $template = $this->get_template_text();
        
        wp_send_json_success(array('template' => $template));
    }
    
    /**
     * テンプレートテキスト取得
     */
    private function get_template_text() {
        $template = '===TITLE===
【2025年】○○補助金｜最大○○万円・対象者・締切○月○日

===META_DESCRIPTION===
○○補助金は、○○を対象に最大○○万円を支援する制度です。申請条件・必要書類・締切日を完全網羅。採択されるためのポイントも専門家が解説します。

===CONTENT===
<div style=\'font-family: Noto Sans JP, sans-serif; line-height: 1.9; color: #111;\'>

<p style=\'font-size: 17px; color: #333; margin-bottom: 28px; line-height: 2;\'>
ここにリード文を記載します。補助金の概要、対象者、主な特徴を簡潔にまとめてください。
</p>

<div style=\'background: #f0f9ff; border: 2px solid #0369a1; padding: 24px; margin: 32px 0;\'>
<p style=\'font-weight: 900; font-size: 17px; margin: 0 0 16px 0; color: #0369a1;\'>この記事でわかること</p>
<ul style=\'margin: 0; padding-left: 24px; list-style: disc;\'>
<li style=\'margin-bottom: 10px; color: #333;\'>補助金額と補助率の詳細</li>
<li style=\'margin-bottom: 10px; color: #333;\'>対象者の要件</li>
<li style=\'margin-bottom: 10px; color: #333;\'>申請の流れと必要書類</li>
<li style=\'margin-bottom: 10px; color: #333;\'>採択されるためのポイント</li>
</ul>
</div>

<h2 style=\'font-size: 20px; font-weight: 900; color: #111; border-bottom: 2px solid #111; padding-bottom: 12px; margin: 48px 0 24px 0;\'>
補助金の概要
</h2>

<p style=\'font-size: 16px; color: #333; margin-bottom: 20px; line-height: 1.9;\'>
ここに補助金の概要を記載します。
</p>

<h2 style=\'font-size: 20px; font-weight: 900; color: #111; border-bottom: 2px solid #111; padding-bottom: 12px; margin: 48px 0 24px 0;\'>
まとめ
</h2>

<div style=\'background: #111; color: #fff; padding: 28px; margin: 28px 0;\'>
<p style=\'font-size: 16px; line-height: 2; margin: 0;\'>
まとめ文を記載します。
</p>
</div>

</div>

===FIELDS===
post_status: publish
organization: ○○市
organization_type: city
grant_number: 
max_amount: 最大○○万円
max_amount_numeric: 1000000
min_amount: 10万円
min_amount_numeric: 100000
subsidy_rate: 1/2
subsidy_rate_detailed: 補助対象経費の1/2以内（上限100万円）
subsidy_rate_max: 50
subsidy_rate_min: 0
deadline: 令和○年○月○日
deadline_date: 2025-12-31
start_date: 2025-04-01
application_period: 令和7年4月1日〜令和7年12月31日
next_deadline: 
application_status: open
grant_target: 市内の中小企業・個人事業主
target_company_size: 中小企業,小規模事業者
target_employee_count: 300人以下
target_capital: 3億円以下
target_years_in_business: 1年以上
eligible_expenses: 設備費、システム導入費
eligible_expenses_detailed: 機械装置費,外注費,専門家経費,クラウドサービス利用費
eligible_expenses_list: 機械装置費,外注費,専門家経費
ineligible_expenses: 人件費、土地取得費
ineligible_expenses_list: 人件費,土地取得費,汎用性の高い備品
grant_difficulty: normal
difficulty_level: 中級
adoption_rate: 80
adoption_count: 100
application_count: 125
budget_total: 50000000
budget_remaining: 30000000
preparation_days: 14
review_period: 約1ヶ月
review_period_days: 30
required_documents: 申請書、事業計画書
required_documents_detailed: 申請書,事業計画書,見積書,決算書,登記事項証明書
required_documents_list: 申請書,事業計画書,見積書
regional_limitation: municipality_only
area_notes: 本社または主要事業所が対象地域内にある事業者限定
grant_prefecture: 東京都
grant_municipality: 渋谷区
application_method: online
application_flow: 事前相談→申請書提出→審査→交付決定
application_tips: 事業計画書は具体的な数値目標を記載することが重要です
common_mistakes: 申請期限直前の提出、必要書類の不備
success_points: 明確な事業目標、具体的な実施計画
contact_info: ○○課 
contact_phone: 00-0000-0000
contact_email: info@example.com
contact_hours: 平日9:00-17:00
official_url: https://example.com
application_url: https://example.com/apply
guideline_url: https://example.com/guidelines
external_link: https://example.com/faq
grant_category: 設備投資,IT導入
grant_tag: 中小企業,補助金,○○市
is_featured: 0
is_new: 1
is_popular: 0
online_application: 1
jgrants_available: 0
priority_order: 100
admin_notes: 
ai_summary: この補助金は中小企業のIT導入を支援する制度です。
source_url: https://example.com/source
source_name: ○○市公式サイト
last_verified_date: 2025-12-05';
        
        return $template;
    }
    
    /**
     * 管理画面CSS取得
     */
    private function get_admin_css() {
        return '
        :root {
            --gi-black: #111111;
            --gi-gray-900: #1a1a1a;
            --gi-gray-800: #333333;
            --gi-gray-700: #4a4a4a;
            --gi-gray-600: #666666;
            --gi-gray-500: #888888;
            --gi-gray-400: #aaaaaa;
            --gi-gray-300: #cccccc;
            --gi-gray-200: #e5e5e5;
            --gi-gray-100: #f5f5f5;
            --gi-white: #ffffff;
            --gi-success: #10b981;
            --gi-success-light: #d1fae5;
            --gi-error: #ef4444;
            --gi-error-light: #fee2e2;
            --gi-warning: #f59e0b;
            --gi-warning-light: #fef3c7;
            --gi-info: #3b82f6;
            --gi-info-light: #dbeafe;
        }
        
        * { box-sizing: border-box; }
        
        .gi-page {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Noto Sans JP", sans-serif;
        }
        
        .gi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--gi-black);
        }
        
        .gi-header h1 {
            font-size: 26px;
            font-weight: 700;
            margin: 0;
            color: var(--gi-black);
        }
        
        .gi-header-sub {
            font-size: 13px;
            color: var(--gi-gray-500);
            margin-top: 4px;
        }
        
        .gi-header-sub code {
            background: var(--gi-info-light);
            color: var(--gi-info);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .gi-header-actions { display: flex; gap: 10px; }
        
        .gi-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            line-height: 1;
        }
        
        .gi-btn:focus { outline: none; box-shadow: 0 0 0 3px rgba(17, 17, 17, 0.1); }
        .gi-btn-black { background: var(--gi-black); color: var(--gi-white); }
        .gi-btn-black:hover { background: var(--gi-gray-800); }
        .gi-btn-outline { background: var(--gi-white); color: var(--gi-black); border: 2px solid var(--gi-black); }
        .gi-btn-outline:hover { background: var(--gi-black); color: var(--gi-white); }
        .gi-btn-success { background: var(--gi-success); color: var(--gi-white); }
        .gi-btn-success:hover { background: #059669; }
        .gi-btn-secondary { background: var(--gi-gray-100); color: var(--gi-gray-700); border: 1px solid var(--gi-gray-200); }
        .gi-btn-secondary:hover { background: var(--gi-gray-200); }
        .gi-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .gi-btn-lg { padding: 16px 32px; font-size: 16px; }
        .gi-btn-sm { padding: 8px 16px; font-size: 13px; }
        
        .gi-steps {
            display: flex;
            margin-bottom: 28px;
            background: var(--gi-gray-100);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--gi-gray-200);
        }
        
        .gi-step {
            flex: 1;
            padding: 18px 20px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
            color: var(--gi-gray-500);
            cursor: pointer;
            transition: all 0.2s ease;
            border-right: 1px solid var(--gi-gray-200);
            background: transparent;
            border-top: none;
            border-bottom: none;
            border-left: none;
        }
        
        .gi-step:last-child { border-right: none; }
        .gi-step:hover { background: var(--gi-gray-200); }
        .gi-step.active { background: var(--gi-black); color: var(--gi-white); }
        .gi-step.completed { background: var(--gi-success); color: var(--gi-white); }
        
        .gi-step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            font-size: 13px;
            font-weight: 700;
            margin-right: 10px;
        }
        
        .gi-step.active .gi-step-number { background: rgba(255, 255, 255, 0.2); }
        
        .gi-panel {
            display: none;
            background: var(--gi-white);
            border: 1px solid var(--gi-gray-200);
            border-radius: 10px;
            padding: 28px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .gi-panel.active { display: block; }
        
        .gi-panel-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--gi-black);
            color: var(--gi-black);
        }
        
        .gi-textarea {
            width: 100%;
            height: 420px;
            padding: 18px;
            border: 2px solid var(--gi-gray-200);
            border-radius: 8px;
            font-size: 13px;
            font-family: "Monaco", "Consolas", "Courier New", monospace;
            line-height: 1.7;
            resize: vertical;
            background: var(--gi-gray-100);
            transition: border-color 0.2s, background-color 0.2s;
        }
        
        .gi-textarea:focus { border-color: var(--gi-black); background: var(--gi-white); outline: none; }
        .gi-textarea::placeholder { color: var(--gi-gray-400); }
        
        .gi-fields-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        
        @media (max-width: 1024px) { .gi-fields-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 640px) { .gi-fields-grid { grid-template-columns: 1fr; } }
        
        .gi-field { display: flex; flex-direction: column; }
        .gi-field.span-2 { grid-column: span 2; }
        .gi-field.span-3 { grid-column: span 3; }
        
        @media (max-width: 1024px) { .gi-field.span-3 { grid-column: span 2; } }
        @media (max-width: 640px) { .gi-field.span-2, .gi-field.span-3 { grid-column: span 1; } }
        
        .gi-field label {
            font-size: 12px;
            font-weight: 600;
            color: var(--gi-gray-600);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .gi-field input,
        .gi-field select,
        .gi-field textarea {
            padding: 11px 14px;
            border: 1px solid var(--gi-gray-200);
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: var(--gi-white);
        }
        
        .gi-field input:focus,
        .gi-field select:focus,
        .gi-field textarea:focus {
            border-color: var(--gi-black);
            outline: none;
            box-shadow: 0 0 0 3px rgba(17, 17, 17, 0.05);
        }
        
        .gi-field input::placeholder { color: var(--gi-gray-400); }
        
        .gi-section { margin-bottom: 32px; }
        
        .gi-section-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--gi-black);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .gi-section-title::after { content: ""; flex: 1; height: 1px; background: var(--gi-gray-200); }
        
        .gi-actions { display: flex; gap: 12px; margin-top: 24px; flex-wrap: wrap; }
        
        .gi-status {
            padding: 16px 20px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .gi-status-success { background: var(--gi-success-light); color: #065f46; border: 1px solid #a7f3d0; }
        .gi-status-error { background: var(--gi-error-light); color: #991b1b; border: 1px solid #fecaca; }
        .gi-status-warning { background: var(--gi-warning-light); color: #92400e; border: 1px solid #fde68a; }
        .gi-status-info { background: var(--gi-info-light); color: #1e40af; border: 1px solid #bfdbfe; }
        .gi-status a { color: inherit; font-weight: 600; text-decoration: underline; }
        .gi-status a:hover { text-decoration: none; }
        
        .gi-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }
        
        @media (max-width: 800px) { .gi-summary { grid-template-columns: repeat(2, 1fr); } }
        
        .gi-summary-item {
            background: var(--gi-gray-100);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid var(--gi-gray-200);
        }
        
        .gi-summary-value { font-size: 32px; font-weight: 700; color: var(--gi-black); line-height: 1; }
        .gi-summary-label { font-size: 11px; color: var(--gi-gray-500); text-transform: uppercase; margin-top: 8px; letter-spacing: 0.5px; }
        
        .gi-confirm {
            background: var(--gi-warning-light);
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .gi-confirm h4 { margin: 0 0 14px; font-size: 15px; font-weight: 700; color: #92400e; }
        .gi-confirm-list { font-size: 14px; line-height: 1.9; color: #78350f; }
        .gi-confirm-list strong { color: var(--gi-black); }
        
        .gi-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 999999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px);
        }
        
        .gi-modal.active { display: flex; }
        
        .gi-modal-box {
            background: var(--gi-white);
            width: 90%;
            max-width: 900px;
            max-height: 85vh;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .gi-modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--gi-gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--gi-gray-100);
        }
        
        .gi-modal-header h3 { margin: 0; font-size: 17px; font-weight: 700; color: var(--gi-black); }
        .gi-modal-body { padding: 24px; overflow: auto; flex: 1; }
        
        .gi-modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--gi-gray-500);
            line-height: 1;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background 0.15s;
        }
        
        .gi-modal-close:hover { background: var(--gi-gray-200); color: var(--gi-black); }
        
        .gi-template-box {
            background: var(--gi-gray-900);
            color: #e0e0e0;
            padding: 24px;
            border-radius: 8px;
            font-family: "Monaco", "Consolas", monospace;
            font-size: 12px;
            line-height: 1.8;
            white-space: pre-wrap;
            position: relative;
            overflow-x: auto;
            max-height: 500px;
        }
        
        .gi-copy-btn { position: absolute; top: 12px; right: 12px; }
        
        .gi-loading {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: gi-spin 0.7s linear infinite;
            vertical-align: middle;
            margin-right: 8px;
        }
        
        .gi-loading-dark { border-color: rgba(0, 0, 0, 0.1); border-top-color: var(--gi-black); }
        
        @keyframes gi-spin { to { transform: rotate(360deg); } }
        
        .gi-help-text {
            font-size: 13px;
            color: var(--gi-gray-500);
            margin-top: 16px;
            padding: 16px;
            background: var(--gi-gray-100);
            border-radius: 6px;
            line-height: 1.7;
            border: 1px solid var(--gi-gray-200);
        }
        
        .gi-help-text strong { color: var(--gi-black); }
        
        .gi-char-count { font-size: 11px; color: var(--gi-gray-500); margin-top: 4px; text-align: right; }
        .gi-char-count.warning { color: var(--gi-warning); }
        .gi-char-count.success { color: var(--gi-success); }
        
        .gi-badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: 700;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .gi-badge-required { background: var(--gi-error-light); color: var(--gi-error); }
        .gi-badge-excerpt { background: var(--gi-success-light); color: var(--gi-success); }
        .gi-badge-info { background: var(--gi-info-light); color: var(--gi-info); }
        
        .gi-post-type-notice {
            background: var(--gi-info-light);
            border: 1px solid #93c5fd;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .gi-post-type-notice-icon { font-size: 24px; }
        .gi-post-type-notice-text { flex: 1; }
        .gi-post-type-notice-text strong { color: #1e40af; font-size: 14px; }
        .gi-post-type-notice-text p { margin: 4px 0 0; font-size: 13px; color: #3b82f6; }
        
        .gi-tabs { display: flex; gap: 0; margin-bottom: 20px; border-bottom: 2px solid var(--gi-gray-200); }
        .gi-tab {
            padding: 12px 20px;
            font-size: 13px;
            font-weight: 600;
            color: var(--gi-gray-500);
            cursor: pointer;
            border: none;
            background: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
        }
        .gi-tab:hover { color: var(--gi-black); }
        .gi-tab.active { color: var(--gi-black); border-bottom-color: var(--gi-black); }
        .gi-tab-content { display: none; }
        .gi-tab-content.active { display: block; }
        ';
    }
    
    /**
     * 管理画面レンダリング
     */
    public function render_admin_page() {
        $nonce = wp_create_nonce('gi_grant_nonce');
        $post_type_exists = $this->check_post_type_exists();
        $taxonomies = $post_type_exists ? $this->get_post_type_taxonomies() : array();
        ?>
        <style><?php echo $this->get_admin_css(); ?></style>
        
        <div class="gi-page">
            
            <!-- ヘッダー -->
            <div class="gi-header">
                <div>
                    <h1>補助金記事作成 Pro</h1>
                    <div class="gi-header-sub">
                        投稿先: <code><?php echo esc_html($this->post_type); ?></code> | バージョン <?php echo esc_html($this->version); ?>
                    </div>
                </div>
                <div class="gi-header-actions">
                    <button type="button" class="gi-btn gi-btn-outline" id="btnTemplate">テンプレート</button>
                    <button type="button" class="gi-btn gi-btn-secondary" id="btnHelp">ヘルプ</button>
                </div>
            </div>
            
            <?php if (!$post_type_exists): ?>
            <div class="gi-status gi-status-error" style="margin-bottom: 24px;">
                <strong>エラー:</strong> カスタム投稿タイプ「<?php echo esc_html($this->post_type); ?>」が登録されていません。
            </div>
            <?php else: ?>
            
            <!-- タクソノミー情報 -->
            <div class="gi-post-type-notice">
                <div class="gi-post-type-notice-icon">📋</div>
                <div class="gi-post-type-notice-text">
                    <strong>カスタム投稿タイプ「<?php echo esc_html($this->post_type); ?>」に投稿されます</strong>
                    <p>
                        タクソノミー: 
                        <?php 
                        if (!empty($taxonomies)) {
                            $tax_names = array();
                            foreach ($taxonomies as $tax) {
                                $tax_names[] = $tax->label . '(' . $tax->name . ')';
                            }
                            echo esc_html(implode(', ', $tax_names));
                        } else {
                            echo 'なし';
                        }
                        ?>
                    </p>
                </div>
            </div>
            
            <!-- ステップナビゲーション -->
            <div class="gi-steps">
                <button type="button" class="gi-step active" data-step="1">
                    <span class="gi-step-number">1</span>データ入力
                </button>
                <button type="button" class="gi-step" data-step="2">
                    <span class="gi-step-number">2</span>確認・編集
                </button>
                <button type="button" class="gi-step" data-step="3">
                    <span class="gi-step-number">3</span>投稿作成
                </button>
            </div>
            
            <!-- STEP 1 -->
            <div class="gi-panel active" id="panel1">
                <h2 class="gi-panel-title">データを貼り付け</h2>
                <textarea class="gi-textarea" id="rawData" placeholder="===TITLE===&#10;タイトル&#10;&#10;===META_DESCRIPTION===&#10;メタディスクリプション&#10;&#10;===CONTENT===&#10;HTML本文&#10;&#10;===FIELDS===&#10;フィールド: 値"></textarea>
                <div class="gi-actions">
                    <button type="button" class="gi-btn gi-btn-black gi-btn-lg" id="btnParse">解析して次へ</button>
                    <button type="button" class="gi-btn gi-btn-secondary" id="btnClear">クリア</button>
                </div>
                <div class="gi-help-text">
                    <strong>使い方:</strong> 「テンプレート」からフォーマットをコピー → データ入力 → 貼り付け → 「解析して次へ」<br>
                    <strong>構造化データ:</strong> 本文内のJSON-LDは自動的に別フィールドに分離保存されます
                </div>
                <div id="status1"></div>
            </div>
            
            <!-- STEP 2 -->
            <div class="gi-panel" id="panel2">
                <h2 class="gi-panel-title">内容確認・編集</h2>
                
                <div class="gi-summary">
                    <div class="gi-summary-item">
                        <div class="gi-summary-value" id="sumTitle">0</div>
                        <div class="gi-summary-label">タイトル文字数</div>
                    </div>
                    <div class="gi-summary-item">
                        <div class="gi-summary-value" id="sumMeta">0</div>
                        <div class="gi-summary-label">抜粋文字数</div>
                    </div>
                    <div class="gi-summary-item">
                        <div class="gi-summary-value" id="sumContent">0</div>
                        <div class="gi-summary-label">本文文字数</div>
                    </div>
                    <div class="gi-summary-item">
                        <div class="gi-summary-value" id="sumFields">0</div>
                        <div class="gi-summary-label">フィールド数</div>
                    </div>
                </div>
                
                <!-- タブナビゲーション -->
                <div class="gi-tabs">
                    <button type="button" class="gi-tab active" data-tab="basic">基本情報</button>
                    <button type="button" class="gi-tab" data-tab="amount">金額・期限</button>
                    <button type="button" class="gi-tab" data-tab="target">対象・条件</button>
                    <button type="button" class="gi-tab" data-tab="region">地域・連絡先</button>
                    <button type="button" class="gi-tab" data-tab="other">その他</button>
                </div>
                
                <!-- 基本情報タブ -->
                <div class="gi-tab-content active" id="tab-basic">
                    <div class="gi-section">
                        <div class="gi-fields-grid">
                            <div class="gi-field span-3">
                                <label>タイトル <span class="gi-badge gi-badge-required">必須</span></label>
                                <input type="text" id="fTitle">
                            </div>
                            <div class="gi-field span-3">
                                <label>抜粋 / メタディスクリプション <span class="gi-badge gi-badge-excerpt">抜粋に保存</span></label>
                                <textarea id="fMeta" rows="3"></textarea>
                                <div class="gi-char-count" id="metaCharCount">0 / 160文字</div>
                            </div>
                            <div class="gi-field span-2">
                                <label>実施組織</label>
                                <input type="text" id="fOrg">
                            </div>
                            <div class="gi-field">
                                <label>組織タイプ</label>
                                <select id="fOrgType">
                                    <option value="">選択</option>
                                    <option value="national">国（省庁）</option>
                                    <option value="prefecture">都道府県</option>
                                    <option value="city">市区町村</option>
                                    <option value="public_org">公的機関</option>
                                    <option value="private_org">民間団体</option>
                                    <option value="other">その他</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 金額・期限タブ -->
                <div class="gi-tab-content" id="tab-amount">
                    <div class="gi-section">
                        <div class="gi-section-title">金額情報</div>
                        <div class="gi-fields-grid">
                            <div class="gi-field">
                                <label>最大助成額（テキスト）</label>
                                <input type="text" id="fAmountText">
                            </div>
                            <div class="gi-field">
                                <label>最大助成額（数値）</label>
                                <input type="number" id="fAmountNum">
                            </div>
                            <div class="gi-field">
                                <label>最小助成額（数値）</label>
                                <input type="number" id="fMinAmount">
                            </div>
                            <div class="gi-field span-2">
                                <label>補助率（詳細）</label>
                                <input type="text" id="fSubsidyRate">
                            </div>
                            <div class="gi-field">
                                <label>補助率上限（%）</label>
                                <input type="number" id="fSubsidyRateMax" min="0" max="100">
                            </div>
                        </div>
                    </div>
                    <div class="gi-section">
                        <div class="gi-section-title">期限・ステータス</div>
                        <div class="gi-fields-grid">
                            <div class="gi-field">
                                <label>締切（表示用）</label>
                                <input type="text" id="fDeadlineText">
                            </div>
                            <div class="gi-field">
                                <label>締切日</label>
                                <input type="date" id="fDeadlineDate">
                            </div>
                            <div class="gi-field">
                                <label>申請ステータス</label>
                                <select id="fAppStatus">
                                    <option value="open">募集中</option>
                                    <option value="upcoming">募集予定</option>
                                    <option value="closed">募集終了</option>
                                    <option value="suspended">一時停止</option>
                                </select>
                            </div>
                            <div class="gi-field span-2">
                                <label>申請期間</label>
                                <input type="text" id="fAppPeriod">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 対象・条件タブ -->
                <div class="gi-tab-content" id="tab-target">
                    <div class="gi-section">
                        <div class="gi-section-title">対象者</div>
                        <div class="gi-fields-grid">
                            <div class="gi-field span-3">
                                <label>対象者・対象事業</label>
                                <textarea id="fTarget" rows="3"></textarea>
                            </div>
                            <div class="gi-field">
                                <label>対象企業規模</label>
                                <input type="text" id="fTargetSize" placeholder="中小企業,小規模事業者">
                            </div>
                            <div class="gi-field">
                                <label>対象従業員数</label>
                                <input type="text" id="fTargetEmployee">
                            </div>
                            <div class="gi-field">
                                <label>対象資本金</label>
                                <input type="text" id="fTargetCapital">
                            </div>
                        </div>
                    </div>
                    <div class="gi-section">
                        <div class="gi-section-title">経費・書類</div>
                        <div class="gi-fields-grid">
                            <div class="gi-field span-2">
                                <label>対象経費</label>
                                <textarea id="fExpenses" rows="2"></textarea>
                            </div>
                            <div class="gi-field">
                                <label>申請難易度</label>
                                <select id="fDifficulty">
                                    <option value="">選択</option>
                                    <option value="初級">初級</option>
                                    <option value="中級">中級</option>
                                    <option value="上級">上級</option>
                                    <option value="非常に高い">非常に高い</option>
                                </select>
                            </div>
                            <div class="gi-field">
                                <label>採択率（%）</label>
                                <input type="number" id="fRate" min="0" max="100">
                            </div>
                            <div class="gi-field span-2">
                                <label>必要書類</label>
                                <textarea id="fDocs" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 地域・連絡先タブ -->
                <div class="gi-tab-content" id="tab-region">
                    <div class="gi-section">
                        <div class="gi-section-title">地域情報</div>
                        <div class="gi-fields-grid">
                            <div class="gi-field">
                                <label>地域制限</label>
                                <select id="fRegion">
                                    <option value="">選択</option>
                                    <option value="nationwide">全国対象</option>
                                    <option value="prefecture_only">都道府県内限定</option>
                                    <option value="municipality_only">市町村限定</option>
                                </select>
                            </div>
                            <div class="gi-field">
                                <label>都道府県 <span class="gi-badge gi-badge-info">タクソノミー</span></label>
                                <input type="text" id="fPref" placeholder="東京都">
                            </div>
                            <div class="gi-field">
                                <label>市区町村 <span class="gi-badge gi-badge-info">タクソノミー</span></label>
                                <input type="text" id="fMunicipality" placeholder="渋谷区">
                            </div>
                            <div class="gi-field span-3">
                                <label>地域に関する備考</label>
                                <textarea id="fAreaNotes" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="gi-section">
                        <div class="gi-section-title">連絡先</div>
                        <div class="gi-fields-grid">
                            <div class="gi-field span-2">
                                <label>問い合わせ先</label>
                                <textarea id="fContact" rows="2"></textarea>
                            </div>
                            <div class="gi-field">
                                <label>電話番号</label>
                                <input type="text" id="fPhone">
                            </div>
                            <div class="gi-field span-2">
                                <label>公式URL</label>
                                <input type="url" id="fUrl">
                            </div>
                            <div class="gi-field">
                                <label>申請URL</label>
                                <input type="url" id="fAppUrl">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- その他タブ -->
                <div class="gi-tab-content" id="tab-other">
                    <div class="gi-section">
                        <div class="gi-section-title">分類</div>
                        <div class="gi-fields-grid">
                            <div class="gi-field span-2">
                                <label>カテゴリ <span class="gi-badge gi-badge-info">タクソノミー</span></label>
                                <input type="text" id="fCategory" placeholder="設備投資,IT導入">
                            </div>
                            <div class="gi-field span-3">
                                <label>タグ <span class="gi-badge gi-badge-info">タクソノミー</span></label>
                                <input type="text" id="fTag" placeholder="中小企業,補助金">
                            </div>
                        </div>
                    </div>
                    <div class="gi-section">
                        <div class="gi-section-title">フラグ・管理</div>
                        <div class="gi-fields-grid">
                            <div class="gi-field">
                                <label>注目の助成金</label>
                                <select id="fFeatured">
                                    <option value="0">いいえ</option>
                                    <option value="1">はい</option>
                                </select>
                            </div>
                            <div class="gi-field">
                                <label>新着</label>
                                <select id="fNew">
                                    <option value="0">いいえ</option>
                                    <option value="1">はい</option>
                                </select>
                            </div>
                            <div class="gi-field">
                                <label>オンライン申請可</label>
                                <select id="fOnline">
                                    <option value="0">いいえ</option>
                                    <option value="1">はい</option>
                                </select>
                            </div>
                            <div class="gi-field">
                                <label>表示優先度</label>
                                <input type="number" id="fPriority" value="100">
                            </div>
                            <div class="gi-field span-3">
                                <label>管理者メモ</label>
                                <textarea id="fAdminNotes" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="gi-actions">
                    <button type="button" class="gi-btn gi-btn-secondary" id="btnBack1">戻る</button>
                    <button type="button" class="gi-btn gi-btn-outline" id="btnPreview">プレビュー</button>
                    <button type="button" class="gi-btn gi-btn-black gi-btn-lg" id="btnNext2">次へ：作成確認</button>
                </div>
                <div id="status2"></div>
            </div>
            
            <!-- STEP 3 -->
            <div class="gi-panel" id="panel3">
                <h2 class="gi-panel-title">投稿作成</h2>
                
                <div class="gi-confirm">
                    <h4>作成内容の確認</h4>
                    <div class="gi-confirm-list" id="confirmList"></div>
                </div>
                
                <div class="gi-section">
                    <div class="gi-section-title">公開設定</div>
                    <div class="gi-fields-grid">
                        <div class="gi-field">
                            <label>公開状態</label>
                            <select id="fStatus">
                                <option value="draft">下書き</option>
                                <option value="publish">公開</option>
                                <option value="pending">レビュー待ち</option>
                                <option value="private">非公開</option>
                            </select>
                        </div>
                        <div class="gi-field">
                            <label>投稿タイプ</label>
                            <input type="text" value="<?php echo esc_attr($this->post_type); ?>" readonly style="background: #f5f5f5;">
                        </div>
                    </div>
                </div>
                
                <div class="gi-actions">
                    <button type="button" class="gi-btn gi-btn-secondary" id="btnBack2">戻る</button>
                    <button type="button" class="gi-btn gi-btn-success gi-btn-lg" id="btnCreate">補助金記事を作成する</button>
                </div>
                <div id="status3"></div>
            </div>
            
            <?php endif; ?>
        </div>
        
        <!-- モーダル: テンプレート -->
        <div class="gi-modal" id="modalTemplate">
            <div class="gi-modal-box">
                <div class="gi-modal-header">
                    <h3>入力テンプレート</h3>
                    <button type="button" class="gi-modal-close" data-close="modalTemplate">&times;</button>
                </div>
                <div class="gi-modal-body">
                    <p style="margin: 0 0 16px; color: #666; font-size: 14px;">
                        以下をコピーしてデータを入力してください。構造化データは本文から自動分離されます。
                    </p>
                    <div class="gi-template-box">
                        <button type="button" class="gi-btn gi-btn-black gi-btn-sm gi-copy-btn" id="btnCopy">コピー</button>
                        <div id="templateText"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- モーダル: プレビュー -->
        <div class="gi-modal" id="modalPreview">
            <div class="gi-modal-box" style="max-width: 1000px;">
                <div class="gi-modal-header">
                    <h3>プレビュー</h3>
                    <button type="button" class="gi-modal-close" data-close="modalPreview">&times;</button>
                </div>
                <div class="gi-modal-body" id="previewContent"></div>
            </div>
        </div>
        
        <!-- モーダル: ヘルプ -->
        <div class="gi-modal" id="modalHelp">
            <div class="gi-modal-box" style="max-width: 800px;">
                <div class="gi-modal-header">
                    <h3>使い方ガイド</h3>
                    <button type="button" class="gi-modal-close" data-close="modalHelp">&times;</button>
                </div>
                <div class="gi-modal-body">
                    <h4 style="margin-top: 0;">バージョン <?php echo esc_html($this->version); ?> の新機能</h4>
                    <ul style="padding-left: 20px; line-height: 1.8;">
                        <li><strong>構造化データの自動分離:</strong> 本文内のJSON-LDは自動的に別フィールドに保存</li>
                        <li><strong>都道府県・市区町村タクソノミー対応:</strong> grant_prefecture, grant_municipality</li>
                        <li><strong>拡張フィールド対応:</strong> 80以上のACFフィールドに対応</li>
                    </ul>
                    
                    <h4>タクソノミーについて</h4>
                    <p style="color: #666;">以下のタクソノミーが設定されます（存在する場合）：</p>
                    <ul style="padding-left: 20px; line-height: 1.8;">
                        <li><strong>grant_category:</strong> カテゴリ（階層型）</li>
                        <li><strong>grant_tag:</strong> タグ（非階層型）</li>
                        <li><strong>grant_prefecture:</strong> 都道府県（階層型）</li>
                        <li><strong>grant_municipality:</strong> 市区町村（階層型）</li>
                    </ul>
                    
                    <h4>フィールド値の指定方法</h4>
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <tr style="background: #f5f5f5;">
                            <th style="padding: 10px; border: 1px solid #e5e5e5; text-align: left;">フィールド</th>
                            <th style="padding: 10px; border: 1px solid #e5e5e5; text-align: left;">値の例</th>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #e5e5e5;">application_status</td>
                            <td style="padding: 10px; border: 1px solid #e5e5e5;">open / upcoming / closed / suspended</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #e5e5e5;">difficulty_level</td>
                            <td style="padding: 10px; border: 1px solid #e5e5e5;">初級 / 中級 / 上級 / 非常に高い</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #e5e5e5;">is_featured, is_new</td>
                            <td style="padding: 10px; border: 1px solid #e5e5e5;">0 / 1</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #e5e5e5;">grant_prefecture</td>
                            <td style="padding: 10px; border: 1px solid #e5e5e5;">東京都, 神奈川県（カンマ区切りで複数可）</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #e5e5e5;">grant_municipality</td>
                            <td style="padding: 10px; border: 1px solid #e5e5e5;">渋谷区, 新宿区（カンマ区切りで複数可）</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(function($) {
            var nonce = '<?php echo esc_js($nonce); ?>';
            var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
            var postType = '<?php echo esc_js($this->post_type); ?>';
            var parsedData = {};
            
            // ステップ切り替え
            function goToStep(stepNumber) {
                $('.gi-step').removeClass('active');
                $('.gi-step[data-step="' + stepNumber + '"]').addClass('active');
                $('.gi-panel').removeClass('active');
                $('#panel' + stepNumber).addClass('active');
                $('html, body').animate({ scrollTop: 0 }, 300);
            }
            
            $('.gi-step').on('click', function() {
                var step = $(this).data('step');
                if (step === 1) { goToStep(1); return; }
                if (step >= 2 && parsedData.title) { goToStep(step); }
            });
            
            // タブ切り替え
            $('.gi-tab').on('click', function() {
                var tabId = $(this).data('tab');
                $('.gi-tab').removeClass('active');
                $(this).addClass('active');
                $('.gi-tab-content').removeClass('active');
                $('#tab-' + tabId).addClass('active');
            });
            
            // データ解析
            $('#btnParse').on('click', function() {
                var rawData = $('#rawData').val().trim();
                if (!rawData) {
                    showStatus('#status1', 'warning', 'データを入力してください');
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).html('<span class="gi-loading"></span>解析中...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'gi_grant_parse', nonce: nonce, raw_data: rawData },
                    success: function(response) {
                        $btn.prop('disabled', false).text('解析して次へ');
                        if (response.success) {
                            parsedData = response.data.parsed;
                            fillFields(parsedData);
                            updateSummary();
                            goToStep(2);
                            $('#status1').empty();
                        } else {
                            showStatus('#status1', 'error', response.data.message || response.data);
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('解析して次へ');
                        showStatus('#status1', 'error', '通信エラーが発生しました');
                    }
                });
            });
            
            $('#btnClear').on('click', function() {
                if (confirm('入力内容をクリアしますか？')) {
                    $('#rawData').val('');
                    parsedData = {};
                    $('#status1').empty();
                }
            });
            
            // フィールド入力
            function fillFields(data) {
                $('#fTitle').val(data.title || '');
                $('#fMeta').val(data.meta_description || '');
                $('#fOrg').val(data.organization || '');
                $('#fOrgType').val(data.organization_type || '');
                $('#fAmountText').val(data.max_amount || '');
                $('#fAmountNum').val(data.max_amount_numeric || '');
                $('#fMinAmount').val(data.min_amount_numeric || data.min_amount || '');
                $('#fSubsidyRate').val(data.subsidy_rate_detailed || data.subsidy_rate || '');
                $('#fSubsidyRateMax').val(data.subsidy_rate_max || '');
                $('#fDeadlineText').val(data.deadline || '');
                $('#fDeadlineDate').val(data.deadline_date || '');
                $('#fAppStatus').val(data.application_status || 'open');
                $('#fAppPeriod').val(data.application_period || '');
                $('#fTarget').val(data.grant_target || '');
                $('#fTargetSize').val(data.target_company_size || '');
                $('#fTargetEmployee').val(data.target_employee_count || '');
                $('#fTargetCapital').val(data.target_capital || '');
                $('#fExpenses').val(data.eligible_expenses || data.eligible_expenses_detailed || '');
                $('#fDifficulty').val(data.difficulty_level || '');
                $('#fRate').val(data.adoption_rate || '');
                $('#fDocs').val(data.required_documents || data.required_documents_detailed || '');
                $('#fRegion').val(data.regional_limitation || '');
                $('#fPref').val(data.grant_prefecture || '');
                $('#fMunicipality').val(data.grant_municipality || '');
                $('#fAreaNotes').val(data.area_notes || '');
                $('#fContact').val(data.contact_info || '');
                $('#fPhone').val(data.contact_phone || '');
                $('#fUrl').val(data.official_url || '');
                $('#fAppUrl').val(data.application_url || '');
                $('#fCategory').val(data.grant_category || '');
                $('#fTag').val(data.grant_tag || '');
                $('#fFeatured').val(data.is_featured || '0');
                $('#fNew').val(data.is_new || '0');
                $('#fOnline').val(data.online_application || '0');
                $('#fPriority').val(data.priority_order || '100');
                $('#fAdminNotes').val(data.admin_notes || '');
                $('#fStatus').val(data.post_status || 'draft');
                updateMetaCharCount();
            }
            
            function collectFields() {
                return {
                    title: $('#fTitle').val(),
                    meta_description: $('#fMeta').val(),
                    content: parsedData.content || '',
                    structured_data_json: parsedData.structured_data_json || '',
                    post_status: $('#fStatus').val(),
                    organization: $('#fOrg').val(),
                    organization_type: $('#fOrgType').val(),
                    max_amount: $('#fAmountText').val(),
                    max_amount_numeric: $('#fAmountNum').val(),
                    min_amount_numeric: $('#fMinAmount').val(),
                    subsidy_rate_detailed: $('#fSubsidyRate').val(),
                    subsidy_rate_max: $('#fSubsidyRateMax').val(),
                    deadline: $('#fDeadlineText').val(),
                    deadline_date: $('#fDeadlineDate').val(),
                    application_status: $('#fAppStatus').val(),
                    application_period: $('#fAppPeriod').val(),
                    grant_target: $('#fTarget').val(),
                    target_company_size: $('#fTargetSize').val(),
                    target_employee_count: $('#fTargetEmployee').val(),
                    target_capital: $('#fTargetCapital').val(),
                    eligible_expenses: $('#fExpenses').val(),
                    difficulty_level: $('#fDifficulty').val(),
                    adoption_rate: $('#fRate').val(),
                    required_documents: $('#fDocs').val(),
                    regional_limitation: $('#fRegion').val(),
                    grant_prefecture: $('#fPref').val(),
                    grant_municipality: $('#fMunicipality').val(),
                    area_notes: $('#fAreaNotes').val(),
                    contact_info: $('#fContact').val(),
                    contact_phone: $('#fPhone').val(),
                    official_url: $('#fUrl').val(),
                    application_url: $('#fAppUrl').val(),
                    grant_category: $('#fCategory').val(),
                    grant_tag: $('#fTag').val(),
                    is_featured: $('#fFeatured').val(),
                    is_new: $('#fNew').val(),
                    online_application: $('#fOnline').val(),
                    priority_order: $('#fPriority').val(),
                    admin_notes: $('#fAdminNotes').val()
                };
            }
            
            function updateSummary() {
                var title = $('#fTitle').val() || '';
                var meta = $('#fMeta').val() || '';
                var content = parsedData.content || '';
                var contentText = content.replace(/<[^>]*>/g, '');
                
                $('#sumTitle').text(title.length);
                $('#sumMeta').text(meta.length);
                $('#sumContent').text(contentText.length);
                
                var fieldCount = 0;
                var data = collectFields();
                for (var key in data) {
                    if (data[key] && data[key] !== '-' && data[key] !== '') fieldCount++;
                }
                $('#sumFields').text(fieldCount);
            }
            
            function updateMetaCharCount() {
                var len = $('#fMeta').val().length;
                var $count = $('#metaCharCount');
                $count.text(len + ' / 160文字');
                $count.removeClass('warning success');
                if (len > 160) $count.addClass('warning');
                else if (len >= 120) $count.addClass('success');
            }
            
            $('#fMeta').on('input', updateMetaCharCount);
            $('#fTitle, #fMeta').on('input', updateSummary);
            
            // ナビゲーション
            $('#btnBack1').on('click', function() { goToStep(1); });
            $('#btnBack2').on('click', function() { goToStep(2); });
            
            $('#btnNext2').on('click', function() {
                var data = collectFields();
                if (!data.title) {
                    showStatus('#status2', 'warning', 'タイトルを入力してください');
                    return;
                }
                
                var statusLabels = { draft: '下書き', publish: '公開', pending: 'レビュー待ち', private: '非公開' };
                var confirmHtml = '';
                confirmHtml += '<strong>タイトル:</strong> ' + escapeHtml(data.title) + '<br>';
                confirmHtml += '<strong>投稿タイプ:</strong> ' + postType + '<br>';
                confirmHtml += '<strong>公開状態:</strong> ' + (statusLabels[data.post_status] || data.post_status) + '<br>';
                confirmHtml += '<strong>都道府県:</strong> ' + escapeHtml(data.grant_prefecture || '-') + '<br>';
                confirmHtml += '<strong>市区町村:</strong> ' + escapeHtml(data.grant_municipality || '-') + '<br>';
                confirmHtml += '<strong>カテゴリ:</strong> ' + escapeHtml(data.grant_category || '-') + '<br>';
                confirmHtml += '<strong>タグ:</strong> ' + escapeHtml(data.grant_tag || '-') + '<br>';
                if (data.structured_data_json) {
                    confirmHtml += '<strong>構造化データ:</strong> あり（別フィールドに保存）<br>';
                }
                
                $('#confirmList').html(confirmHtml);
                goToStep(3);
                $('#status2').empty();
            });
            
            // プレビュー
            $('#btnPreview').on('click', function() {
                var data = collectFields();
                if (!data.title) {
                    showStatus('#status2', 'warning', 'タイトルを入力してください');
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'gi_grant_preview', nonce: nonce, data: JSON.stringify(data) },
                    success: function(response) {
                        if (response.success) {
                            $('#previewContent').html(response.data.html);
                            openModal('modalPreview');
                        }
                    }
                });
            });
            
            // 投稿作成
            $('#btnCreate').on('click', function() {
                var data = collectFields();
                if (!data.title) {
                    showStatus('#status3', 'error', 'タイトルが必要です');
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).html('<span class="gi-loading"></span>作成中...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'gi_grant_create', nonce: nonce, data: JSON.stringify(data) },
                    success: function(response) {
                        $btn.prop('disabled', false).text('補助金記事を作成する');
                        if (response.success) {
                            var msg = response.data.message + '<br>';
                            msg += '<strong>保存フィールド:</strong> ' + response.data.saved_fields.length + '件<br>';
                            
                            // タクソノミー結果
                            if (response.data.taxonomy_results) {
                                var taxResults = response.data.taxonomy_results;
                                for (var tax in taxResults) {
                                    if (taxResults[tax].status === 'success') {
                                        msg += '<strong>' + tax + ':</strong> ' + taxResults[tax].total + '件設定';
                                        if (taxResults[tax].created && taxResults[tax].created.length > 0) {
                                            msg += '（新規: ' + taxResults[tax].created.join(', ') + '）';
                                        }
                                        msg += '<br>';
                                    }
                                }
                            }
                            
                            msg += '<a href="' + response.data.edit_url + '" target="_blank">編集画面を開く</a>';
                            msg += ' | <a href="' + response.data.view_url + '" target="_blank">記事を表示</a>';
                            
                            showStatus('#status3', 'success', msg);
                            
                            if (confirm('作成しました！続けて別の記事を作成しますか？')) {
                                resetForm();
                            }
                        } else {
                            showStatus('#status3', 'error', response.data.message || response.data);
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).text('補助金記事を作成する');
                        showStatus('#status3', 'error', '通信エラーが発生しました');
                    }
                });
            });
            
            function resetForm() {
                parsedData = {};
                $('#rawData').val('');
                $('input[id^="f"], textarea[id^="f"], select[id^="f"]').val('');
                $('#fStatus').val('draft');
                $('#fAppStatus').val('open');
                $('#fFeatured, #fNew, #fOnline').val('0');
                $('#fPriority').val('100');
                $('#sumTitle, #sumMeta, #sumContent, #sumFields').text('0');
                $('#metaCharCount').text('0 / 160文字').removeClass('warning success');
                $('#status1, #status2, #status3').empty();
                goToStep(1);
            }
                     // テンプレート
            $('#btnTemplate').on('click', function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'gi_grant_template', nonce: nonce },
                    success: function(response) {
                        if (response.success) {
                            $('#templateText').text(response.data.template);
                            openModal('modalTemplate');
                        }
                    }
                });
            });
            
            // コピー
            $('#btnCopy').on('click', function() {
                var text = $('#templateText').text();
                var $btn = $(this);
                
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function() {
                        $btn.addClass('gi-btn-success').text('コピーしました');
                        setTimeout(function() {
                            $btn.removeClass('gi-btn-success').text('コピー');
                        }, 2000);
                    });
                } else {
                    var $temp = $('<textarea>');
                    $('body').append($temp);
                    $temp.val(text).select();
                    document.execCommand('copy');
                    $temp.remove();
                    $btn.addClass('gi-btn-success').text('コピーしました');
                    setTimeout(function() {
                        $btn.removeClass('gi-btn-success').text('コピー');
                    }, 2000);
                }
            });
            
            // ヘルプ
            $('#btnHelp').on('click', function() {
                openModal('modalHelp');
            });
            
            // モーダル
            function openModal(modalId) {
                $('#' + modalId).addClass('active');
                $('body').css('overflow', 'hidden');
            }
            
            function closeModal(modalId) {
                $('#' + modalId).removeClass('active');
                $('body').css('overflow', '');
            }
            
            $('.gi-modal-close').on('click', function() {
                var modalId = $(this).data('close');
                closeModal(modalId);
            });
            
            $('.gi-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).removeClass('active');
                    $('body').css('overflow', '');
                }
            });
            
            $('.gi-modal-box').on('click', function(e) {
                e.stopPropagation();
            });
            
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.gi-modal.active').removeClass('active');
                    $('body').css('overflow', '');
                }
            });
            
            // ユーティリティ
            function showStatus(selector, type, message) {
                $(selector).html('<div class="gi-status gi-status-' + type + '">' + message + '</div>');
            }
            
            function escapeHtml(text) {
                if (!text) return '';
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(text));
                return div.innerHTML;
            }
        });
        </script>
        <?php
    }
}

// プラグイン初期化
new GI_Grant_Article_Creator_Pro();
