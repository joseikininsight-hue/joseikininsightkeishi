<?php
/**
 * Plugin Name: Grant Article Creator
 * Description: 補助金記事データをペーストするだけで新規投稿を作成
 * Version: 1.0.0
 * Author: GI Web Team
 */

if (!defined('ABSPATH')) exit;

class GI_Grant_Article_Creator {
    private $version = '1.0.0';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_gi_grant_create_post', array($this, 'ajax_create_post'));
        add_action('wp_ajax_gi_grant_preview', array($this, 'ajax_preview'));
        add_action('wp_ajax_gi_grant_parse_data', array($this, 'ajax_parse_data'));
    }

    public function add_admin_menu() {
        add_menu_page(
            '補助金記事作成',
            '補助金記事作成',
            'edit_posts',
            'gi-grant-creator',
            array($this, 'render_page'),
            'dashicons-plus-alt',
            26
        );
    }

    /**
     * データ解析API
     */
    public function ajax_parse_data() {
        check_ajax_referer('gi_grant_nonce', 'nonce');
        
        $raw_data = wp_unslash($_POST['raw_data'] ?? '');
        
        if (empty($raw_data)) {
            wp_send_json_error('データが入力されていません');
            return;
        }
        
        $parsed = $this->parse_grant_data($raw_data);
        
        if (empty($parsed['title'])) {
            wp_send_json_error('タイトルを抽出できませんでした。データ形式を確認してください。');
            return;
        }
        
        wp_send_json_success($parsed);
    }

    /**
     * プレビューAPI
     */
    public function ajax_preview() {
        check_ajax_referer('gi_grant_nonce', 'nonce');
        
        $data = json_decode(wp_unslash($_POST['data'] ?? '{}'), true);
        
        if (empty($data)) {
            wp_send_json_error('データがありません');
            return;
        }
        
        $html = '<div style="max-width:800px;margin:0 auto;">';
        $html .= '<h1 style="font-size:24px;margin-bottom:20px;">' . esc_html($data['title'] ?? '') . '</h1>';
        $html .= '<p style="color:#666;margin-bottom:20px;">' . esc_html($data['meta_description'] ?? '') . '</p>';
        $html .= '<hr style="margin:20px 0;">';
        $html .= $data['content'] ?? '';
        $html .= '</div>';
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * 投稿作成API
     */
    public function ajax_create_post() {
        check_ajax_referer('gi_grant_nonce', 'nonce');
        
        $data = json_decode(wp_unslash($_POST['data'] ?? '{}'), true);
        
        if (empty($data['title'])) {
            wp_send_json_error('タイトルが必要です');
            return;
        }
        
        // 投稿データ作成
        $post_data = array(
            'post_title'   => sanitize_text_field($data['title']),
            'post_content' => wp_kses_post($data['content'] ?? ''),
            'post_status'  => sanitize_text_field($data['post_status'] ?? 'draft'),
            'post_type'    => 'post',
            'post_author'  => get_current_user_id(),
        );
        
        // 投稿作成
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error('投稿作成に失敗しました: ' . $post_id->get_error_message());
            return;
        }
        
        // カスタムフィールドを保存
        $meta_fields = array(
            'meta_description', 'grant_amount_text', 'grant_amount_num',
            'application_deadline_text', 'application_deadline_date',
            'implementing_organization', 'organization_type', 'target_applicant',
            'application_method', 'contact_info', 'official_website',
            'regional_limitation', 'application_status', 'grant_prefecture',
            'grant_category', 'grant_tag', 'required_documents',
            'adoption_rate', 'difficulty_level', 'eligible_expenses'
        );
        
        foreach ($meta_fields as $field) {
            if (isset($data[$field]) && $data[$field] !== '' && $data[$field] !== '-') {
                update_post_meta($post_id, $field, sanitize_text_field($data[$field]));
            }
        }
        
        // Yoast SEO対応（メタディスクリプション）
        if (!empty($data['meta_description'])) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', sanitize_text_field($data['meta_description']));
        }
        
        // カテゴリ設定（grant_categoryから）
        if (!empty($data['grant_category'])) {
            $categories = array_map('trim', explode(',', $data['grant_category']));
            $cat_ids = array();
            foreach ($categories as $cat_name) {
                $cat = get_category_by_slug(sanitize_title($cat_name));
                if ($cat) {
                    $cat_ids[] = $cat->term_id;
                } else {
                    // カテゴリがなければ作成
                    $new_cat = wp_insert_category(array('cat_name' => $cat_name));
                    if (!is_wp_error($new_cat)) {
                        $cat_ids[] = $new_cat;
                    }
                }
            }
            if (!empty($cat_ids)) {
                wp_set_post_categories($post_id, $cat_ids);
            }
        }
        
        // タグ設定（grant_tagから）
        if (!empty($data['grant_tag'])) {
            $tags = array_map('trim', explode(',', $data['grant_tag']));
            wp_set_post_tags($post_id, $tags);
        }
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link($post_id, 'raw'),
            'view_url' => get_permalink($post_id),
            'message' => '投稿を作成しました（ID: ' . $post_id . '）'
        ));
    }

    /**
     * 生データを解析してフィールドに分解
     */
    private function parse_grant_data($raw_data) {
        $result = array(
            'title' => '',
            'meta_description' => '',
            'content' => '',
            'post_status' => 'draft',
        );
        
        // 行に分割
        $lines = preg_split('/\r\n|\r|\n/', $raw_data);
        $current_section = '';
        $content_lines = array();
        $in_data_fields = false;
        
        foreach ($lines as $i => $line) {
            $line = trim($line);
            
            // 空行スキップ
            if (empty($line)) continue;
            
            // セクション検出
            if ($line === 'メタディスクリプション（120-160文字）') {
                $current_section = 'meta';
                continue;
            }
            if ($line === 'HTML本文') {
                $current_section = 'content';
                continue;
            }
            if ($line === 'データフィールド（WordPress用）') {
                $current_section = 'fields';
                $in_data_fields = true;
                continue;
            }
            
            // タイトル（最初の行で【】を含む）
            if (empty($result['title']) && (mb_strpos($line, '【') !== false || mb_strpos($line, '補助') !== false || mb_strpos($line, '助成') !== false)) {
                // 「メタディスクリプション」が続いていたら分離
                if (mb_strpos($line, 'メタディスクリプション') !== false) {
                    $parts = explode('メタディスクリプション', $line);
                    $result['title'] = trim($parts[0]);
                    $current_section = 'meta';
                } else {
                    $result['title'] = $line;
                }
                continue;
            }
            
            // メタディスクリプション
            if ($current_section === 'meta' && empty($result['meta_description'])) {
                // 「HTML本文」が含まれていたら分離
                if (mb_strpos($line, 'HTML本文') !== false) {
                    $parts = explode('HTML本文', $line);
                    $result['meta_description'] = trim($parts[0]);
                    $current_section = 'content';
                } else {
                    $result['meta_description'] = $line;
                }
                continue;
            }
            
            // HTML本文
            if ($current_section === 'content') {
                // データフィールドセクションが始まったら終了
                if (mb_strpos($line, 'データフィールド') !== false) {
                    $current_section = 'fields';
                    $in_data_fields = true;
                    continue;
                }
                $content_lines[] = $line;
                continue;
            }
            
            // データフィールド（キー・値のペア）
            if ($in_data_fields || $current_section === 'fields') {
                // フィールド名と値を検出
                $field_patterns = array(
                    'post_status' => 'post_status',
                    'grant_amount_text' => 'grant_amount_text',
                    'grant_amount_num' => 'grant_amount_num',
                    'application_deadline_text' => 'application_deadline_text',
                    'application_deadline_date' => 'application_deadline_date',
                    'implementing_organization' => 'implementing_organization',
                    'organization_type' => 'organization_type',
                    'target_applicant' => 'target_applicant',
                    'application_method' => 'application_method',
                    'contact_info' => 'contact_info',
                    'official_website' => 'official_website',
                    'regional_limitation' => 'regional_limitation',
                    'application_status' => 'application_status',
                    'grant_prefecture' => 'grant_prefecture',
                    'grant_category' => 'grant_category',
                    'grant_tag' => 'grant_tag',
                    'required_documents' => 'required_documents',
                    'adoption_rate' => 'adoption_rate',
                    'difficulty_level' => 'difficulty_level',
                    'eligible_expenses' => 'eligible_expenses',
                );
                
                foreach ($field_patterns as $pattern => $field) {
                    if (preg_match('/^' . preg_quote($pattern, '/') . '[\s\t]*(.*)$/u', $line, $m)) {
                        $result[$field] = trim($m[1]);
                        break;
                    }
                }
            }
        }
        
        // コンテンツを結合
        $result['content'] = implode("\n", $content_lines);
        
        // HTMLタグが含まれていなければ、<div>で検出
        if (strpos($result['content'], '<') === false && !empty($content_lines)) {
            // 生テキストの場合はそのまま
        }
        
        return $result;
    }

    /**
     * 管理画面レンダリング
     */
    public function render_page() {
        $nonce = wp_create_nonce('gi_grant_nonce');
        ?>
        <style>
            .gi-grant-wrap { font-family: -apple-system, BlinkMacSystemFont, sans-serif; max-width: 1400px; margin: 20px auto; padding: 0 20px; }
            .gi-grant-header { margin-bottom: 30px; }
            .gi-grant-header h1 { font-size: 28px; font-weight: 700; margin: 0 0 10px 0; }
            .gi-grant-header p { color: #666; margin: 0; }
            .gi-grant-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
            @media (max-width: 1200px) { .gi-grant-layout { grid-template-columns: 1fr; } }
            .gi-grant-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 24px; }
            .gi-grant-card h2 { font-size: 18px; font-weight: 700; margin: 0 0 20px 0; padding-bottom: 15px; border-bottom: 2px solid #111; }
            .gi-grant-textarea { width: 100%; height: 400px; padding: 16px; border: 2px solid #ddd; border-radius: 6px; font-size: 13px; font-family: monospace; line-height: 1.6; resize: vertical; }
            .gi-grant-textarea:focus { border-color: #111; outline: none; }
            .gi-grant-btn { padding: 12px 24px; font-size: 15px; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; transition: all 0.2s; }
            .gi-grant-btn-primary { background: #111; color: #fff; }
            .gi-grant-btn-primary:hover { background: #333; }
            .gi-grant-btn-success { background: #059669; color: #fff; }
            .gi-grant-btn-success:hover { background: #047857; }
            .gi-grant-btn-secondary { background: #f5f5f5; color: #333; border: 1px solid #ddd; }
            .gi-grant-btn-secondary:hover { background: #e5e5e5; }
            .gi-grant-btn:disabled { opacity: 0.5; cursor: not-allowed; }
            .gi-grant-actions { display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap; }
            .gi-grant-fields { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-top: 20px; }
            @media (max-width: 600px) { .gi-grant-fields { grid-template-columns: 1fr; } }
            .gi-grant-field { }
            .gi-grant-field.full { grid-column: 1 / -1; }
            .gi-grant-field label { display: block; font-size: 12px; font-weight: 600; color: #666; margin-bottom: 6px; text-transform: uppercase; }
            .gi-grant-field input, .gi-grant-field select, .gi-grant-field textarea { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
            .gi-grant-field input:focus, .gi-grant-field select:focus, .gi-grant-field textarea:focus { border-color: #111; outline: none; }
            .gi-grant-preview { margin-top: 20px; padding: 20px; background: #fafafa; border: 1px solid #eee; border-radius: 6px; max-height: 500px; overflow: auto; }
            .gi-grant-preview h3 { font-size: 14px; font-weight: 600; color: #666; margin: 0 0 15px 0; }
            .gi-grant-status { padding: 12px 16px; border-radius: 6px; margin-top: 20px; font-size: 14px; }
            .gi-grant-status.success { background: #D1FAE5; color: #065F46; }
            .gi-grant-status.error { background: #FEE2E2; color: #991B1B; }
            .gi-grant-status.info { background: #DBEAFE; color: #1E40AF; }
            .gi-grant-tabs { display: flex; gap: 0; border-bottom: 2px solid #ddd; margin-bottom: 20px; }
            .gi-grant-tab { padding: 12px 20px; font-size: 14px; font-weight: 600; color: #666; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; }
            .gi-grant-tab.active { color: #111; border-bottom-color: #111; }
            .gi-grant-tab-content { display: none; }
            .gi-grant-tab-content.active { display: block; }
            .gi-grant-help { font-size: 12px; color: #888; margin-top: 4px; }
            .gi-grant-badge { display: inline-block; padding: 4px 8px; font-size: 11px; font-weight: 600; border-radius: 4px; background: #f0f0f0; color: #666; margin-left: 8px; }
            .gi-grant-badge.required { background: #FEE2E2; color: #991B1B; }
        </style>

        <div class="gi-grant-wrap">
            <div class="gi-grant-header">
                <h1>📝 補助金記事作成ツール</h1>
                <p>補助金データをペーストするだけで、カスタムフィールド付きの投稿を自動作成します。</p>
            </div>

            <div class="gi-grant-layout">
                <!-- 左カラム：入力 -->
                <div class="gi-grant-card">
                    <h2>① データ入力</h2>
                    
                    <div class="gi-grant-tabs">
                        <div class="gi-grant-tab active" data-tab="paste">ペースト入力</div>
                        <div class="gi-grant-tab" data-tab="manual">手動入力</div>
                    </div>
                    
                    <!-- ペースト入力タブ -->
                    <div class="gi-grant-tab-content active" id="tab-paste">
                        <p style="font-size:13px;color:#666;margin-bottom:15px;">タイトル・メタディスクリプション・HTML本文・データフィールドを含むデータをそのまま貼り付けてください。</p>
                        <textarea class="gi-grant-textarea" id="raw-data" placeholder="【2025年】横浜市木造住宅耐震改修補助事業｜最大155万円・対象者・締切2月27日
メタディスクリプション（120-160文字）
横浜市木造住宅耐震改修補助事業は...

HTML本文
<div style='font-family...'>...</div>

データフィールド（WordPress用）
フィールド	値
post_status	publish
grant_amount_text	最大155万円
..."></textarea>
                        
                        <div class="gi-grant-actions">
                            <button class="gi-grant-btn gi-grant-btn-primary" id="btn-parse">🔍 データ解析</button>
                            <button class="gi-grant-btn gi-grant-btn-secondary" id="btn-clear">クリア</button>
                        </div>
                    </div>
                    
                    <!-- 手動入力タブ -->
                    <div class="gi-grant-tab-content" id="tab-manual">
                        <div class="gi-grant-fields">
                            <div class="gi-grant-field full">
                                <label>タイトル <span class="gi-grant-badge required">必須</span></label>
                                <input type="text" id="field-title" placeholder="【2025年】横浜市木造住宅耐震改修補助事業｜最大155万円">
                            </div>
                            <div class="gi-grant-field full">
                                <label>メタディスクリプション（120-160文字）</label>
                                <textarea id="field-meta" rows="2" placeholder="補助金の概要を120-160文字で..."></textarea>
                            </div>
                            <div class="gi-grant-field full">
                                <label>HTML本文</label>
                                <textarea id="field-content" rows="8" placeholder="<div>...</div>"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 右カラム：フィールド編集・プレビュー -->
                <div class="gi-grant-card">
                    <h2>② フィールド確認・編集</h2>
                    
                    <div class="gi-grant-fields">
                        <div class="gi-grant-field">
                            <label>公開状態</label>
                            <select id="field-post_status">
                                <option value="draft">下書き</option>
                                <option value="publish">公開</option>
                                <option value="pending">レビュー待ち</option>
                            </select>
                        </div>
                        <div class="gi-grant-field">
                            <label>補助金額（テキスト）</label>
                            <input type="text" id="field-grant_amount_text" placeholder="最大155万円">
                        </div>
                        <div class="gi-grant-field">
                            <label>補助金額（数値）</label>
                            <input type="number" id="field-grant_amount_num" placeholder="1550000">
                        </div>
                        <div class="gi-grant-field">
                            <label>申請締切（テキスト）</label>
                            <input type="text" id="field-application_deadline_text" placeholder="令和8年2月27日">
                        </div>
                        <div class="gi-grant-field">
                            <label>申請締切（日付）</label>
                            <input type="date" id="field-application_deadline_date">
                        </div>
                        <div class="gi-grant-field">
                            <label>実施機関</label>
                            <input type="text" id="field-implementing_organization" placeholder="横浜市">
                        </div>
                        <div class="gi-grant-field">
                            <label>機関種別</label>
                            <select id="field-organization_type">
                                <option value="">選択...</option>
                                <option value="national">国</option>
                                <option value="prefecture">都道府県</option>
                                <option value="city">市区町村</option>
                                <option value="other">その他</option>
                            </select>
                        </div>
                        <div class="gi-grant-field full">
                            <label>対象者</label>
                            <input type="text" id="field-target_applicant" placeholder="横浜市内の木造住宅所有者">
                        </div>
                        <div class="gi-grant-field">
                            <label>申請方法</label>
                            <select id="field-application_method">
                                <option value="">選択...</option>
                                <option value="online">オンライン</option>
                                <option value="mail">郵送</option>
                                <option value="visit">窓口</option>
                                <option value="mixed">複合</option>
                            </select>
                        </div>
                        <div class="gi-grant-field">
                            <label>申請状況</label>
                            <select id="field-application_status">
                                <option value="">選択...</option>
                                <option value="open">募集中</option>
                                <option value="coming">募集予定</option>
                                <option value="closed">募集終了</option>
                            </select>
                        </div>
                        <div class="gi-grant-field full">
                            <label>問い合わせ先</label>
                            <input type="text" id="field-contact_info" placeholder="横浜市 建築局...">
                        </div>
                        <div class="gi-grant-field full">
                            <label>公式サイトURL</label>
                            <input type="url" id="field-official_website" placeholder="https://...">
                        </div>
                        <div class="gi-grant-field">
                            <label>地域制限</label>
                            <select id="field-regional_limitation">
                                <option value="">選択...</option>
                                <option value="nationwide">全国</option>
                                <option value="prefecture_only">都道府県限定</option>
                                <option value="municipality_only">市区町村限定</option>
                            </select>
                        </div>
                        <div class="gi-grant-field">
                            <label>都道府県</label>
                            <input type="text" id="field-grant_prefecture" placeholder="神奈川県">
                        </div>
                        <div class="gi-grant-field">
                            <label>カテゴリ（カンマ区切り）</label>
                            <input type="text" id="field-grant_category" placeholder="住宅・建築,防災・災害対策">
                        </div>
                        <div class="gi-grant-field">
                            <label>タグ（カンマ区切り）</label>
                            <input type="text" id="field-grant_tag" placeholder="耐震改修,木造住宅,横浜市">
                        </div>
                        <div class="gi-grant-field">
                            <label>難易度</label>
                            <select id="field-difficulty_level">
                                <option value="">選択...</option>
                                <option value="easy">簡単</option>
                                <option value="normal">普通</option>
                                <option value="hard">難しい</option>
                            </select>
                        </div>
                        <div class="gi-grant-field">
                            <label>採択率</label>
                            <input type="text" id="field-adoption_rate" placeholder="80%">
                        </div>
                        <div class="gi-grant-field full">
                            <label>必要書類</label>
                            <input type="text" id="field-required_documents" placeholder="申請書, 診断報告書...">
                        </div>
                        <div class="gi-grant-field full">
                            <label>対象経費</label>
                            <input type="text" id="field-eligible_expenses" placeholder="基礎補強工事, 耐力壁設置工事...">
                        </div>
                    </div>
                    
                    <div class="gi-grant-actions" style="margin-top:30px;">
                        <button class="gi-grant-btn gi-grant-btn-secondary" id="btn-preview">👁 プレビュー</button>
                        <button class="gi-grant-btn gi-grant-btn-success" id="btn-create" style="flex:1;">✅ 投稿を作成</button>
                    </div>
                    
                    <div id="status-message"></div>
                </div>
            </div>
            
            <!-- プレビューモーダル -->
            <div id="preview-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:9999;">
                <div style="background:#fff;width:90%;max-width:900px;height:90%;margin:2.5% auto;border-radius:8px;overflow:hidden;display:flex;flex-direction:column;">
                    <div style="padding:20px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center;">
                        <h3 style="margin:0;font-size:18px;">プレビュー</h3>
                        <button class="gi-grant-btn gi-grant-btn-secondary" id="btn-close-preview">✕ 閉じる</button>
                    </div>
                    <div id="preview-content" style="flex:1;overflow:auto;padding:30px;"></div>
                </div>
            </div>
        </div>

        <script>
        jQuery(function($){
            var nonce = '<?php echo $nonce; ?>';
            var parsedData = {};
            
            // タブ切り替え
            $('.gi-grant-tab').click(function(){
                var tab = $(this).data('tab');
                $('.gi-grant-tab').removeClass('active');
                $(this).addClass('active');
                $('.gi-grant-tab-content').removeClass('active');
                $('#tab-' + tab).addClass('active');
            });
            
            // データ解析
            $('#btn-parse').click(function(){
                var rawData = $('#raw-data').val().trim();
                if(!rawData){
                    showStatus('error', 'データを入力してください');
                    return;
                }
                
                var btn = $(this);
                btn.prop('disabled', true).text('解析中...');
                
                $.post(ajaxurl, {
                    action: 'gi_grant_parse_data',
                    nonce: nonce,
                    raw_data: rawData
                }, function(r){
                    btn.prop('disabled', false).text('🔍 データ解析');
                    
                    if(r.success){
                        parsedData = r.data;
                        fillFields(r.data);
                        showStatus('success', 'データを解析しました。フィールドを確認して「投稿を作成」をクリックしてください。');
                    } else {
                        showStatus('error', r.data);
                    }
                }).fail(function(){
                    btn.prop('disabled', false).text('🔍 データ解析');
                    showStatus('error', '通信エラーが発生しました');
                });
            });
            
            // フィールドに値をセット
            function fillFields(data){
                $('#field-title').val(data.title || '');
                $('#field-meta').val(data.meta_description || '');
                $('#field-content').val(data.content || '');
                $('#field-post_status').val(data.post_status || 'draft');
                $('#field-grant_amount_text').val(data.grant_amount_text || '');
                $('#field-grant_amount_num').val(data.grant_amount_num || '');
                $('#field-application_deadline_text').val(data.application_deadline_text || '');
                $('#field-application_deadline_date').val(data.application_deadline_date || '');
                $('#field-implementing_organization').val(data.implementing_organization || '');
                $('#field-organization_type').val(data.organization_type || '');
                $('#field-target_applicant').val(data.target_applicant || '');
                $('#field-application_method').val(data.application_method || '');
                $('#field-contact_info').val(data.contact_info || '');
                $('#field-official_website').val(data.official_website || '');
                $('#field-regional_limitation').val(data.regional_limitation || '');
                $('#field-application_status').val(data.application_status || '');
                $('#field-grant_prefecture').val(data.grant_prefecture || '');
                $('#field-grant_category').val(data.grant_category || '');
                $('#field-grant_tag').val(data.grant_tag || '');
                $('#field-required_documents').val(data.required_documents || '');
                $('#field-adoption_rate').val(data.adoption_rate || '');
                $('#field-difficulty_level').val(data.difficulty_level || '');
                $('#field-eligible_expenses').val(data.eligible_expenses || '');
            }
            
            // フィールドから値を取得
            function collectFields(){
                return {
                    title: $('#field-title').val(),
                    meta_description: $('#field-meta').val(),
                    content: $('#field-content').val() || parsedData.content || '',
                    post_status: $('#field-post_status').val(),
                    grant_amount_text: $('#field-grant_amount_text').val(),
                    grant_amount_num: $('#field-grant_amount_num').val(),
                    application_deadline_text: $('#field-application_deadline_text').val(),
                    application_deadline_date: $('#field-application_deadline_date').val(),
                    implementing_organization: $('#field-implementing_organization').val(),
                    organization_type: $('#field-organization_type').val(),
                    target_applicant: $('#field-target_applicant').val(),
                    application_method: $('#field-application_method').val(),
                    contact_info: $('#field-contact_info').val(),
                    official_website: $('#field-official_website').val(),
                    regional_limitation: $('#field-regional_limitation').val(),
                    application_status: $('#field-application_status').val(),
                    grant_prefecture: $('#field-grant_prefecture').val(),
                    grant_category: $('#field-grant_category').val(),
                    grant_tag: $('#field-grant_tag').val(),
                    required_documents: $('#field-required_documents').val(),
                    adoption_rate: $('#field-adoption_rate').val(),
                    difficulty_level: $('#field-difficulty_level').val(),
                    eligible_expenses: $('#field-eligible_expenses').val()
                };
            }
            
            // プレビュー
            $('#btn-preview').click(function(){
                var data = collectFields();
                if(!data.title){
                    showStatus('error', 'タイトルを入力してください');
                    return;
                }
                
                $.post(ajaxurl, {
                    action: 'gi_grant_preview',
                    nonce: nonce,
                    data: JSON.stringify(data)
                }, function(r){
                    if(r.success){
                        $('#preview-content').html(r.data.html);
                        $('#preview-modal').fadeIn(200);
                    }
                });
            });
            
            $('#btn-close-preview, #preview-modal').click(function(e){
                if(e.target === this) $('#preview-modal').fadeOut(200);
            });
            
            // 投稿作成
            $('#btn-create').click(function(){
                var data = collectFields();
                if(!data.title){
                    showStatus('error', 'タイトルを入力してください');
                    return;
                }
                
                if(!confirm('投稿を作成しますか？\n\nタイトル: ' + data.title + '\n状態: ' + data.post_status)){
                    return;
                }
                
                var btn = $(this);
                btn.prop('disabled', true).text('作成中...');
                
                $.post(ajaxurl, {
                    action: 'gi_grant_create_post',
                    nonce: nonce,
                    data: JSON.stringify(data)
                }, function(r){
                    btn.prop('disabled', false).text('✅ 投稿を作成');
                    
                    if(r.success){
                        showStatus('success', r.data.message + 
                            ' <a href="' + r.data.edit_url + '" target="_blank">編集</a> | ' +
                            '<a href="' + r.data.view_url + '" target="_blank">表示</a>');
                        
                        // フォームをクリア
                        if(confirm('作成完了！フォームをクリアして次の記事を作成しますか？')){
                            clearForm();
                        }
                    } else {
                        showStatus('error', r.data);
                    }
                }).fail(function(){
                    btn.prop('disabled', false).text('✅ 投稿を作成');
                    showStatus('error', '通信エラーが発生しました');
                });
            });
            
            // クリア
            $('#btn-clear').click(function(){
                if(confirm('入力内容をクリアしますか？')){
                    clearForm();
                }
            });
            
            function clearForm(){
                $('#raw-data').val('');
                $('#field-title, #field-meta, #field-content').val('');
                $('.gi-grant-field input, .gi-grant-field select, .gi-grant-field textarea').val('');
                $('#field-post_status').val('draft');
                parsedData = {};
                $('#status-message').empty();
            }
            
            function showStatus(type, message){
                $('#status-message').html('<div class="gi-grant-status ' + type + '">' + message + '</div>');
            }
        });
        </script>
        <?php
    }
}

// 初期化
new GI_Grant_Article_Creator();
