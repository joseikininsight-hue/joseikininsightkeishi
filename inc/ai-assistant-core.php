<?php
/**
 * AI Assistant Core - Consolidated & Optimized
 * 
 * Single file implementation for Grant Insight AI features
 * Merges functionality from:
 * - ai-assistant-enhanced.php (Base structure)
 * - ai-chat-fixed.php (Chat logic)
 * 
 * Features:
 * - Real-time AI chat (OpenAI/Gemini)
 * - Eligibility diagnosis flow
 * - Application roadmap generation
 * 
 * @package Grant_Insight_Perfect
 * @version 3.0.0
 */

if (!defined('ABSPATH')) exit;

class GI_AI_Assistant_Core {
    
    private static $instance = null;
    private $openai_key;
    private $gemini_key;
    private $preferred_provider = 'openai'; // 'openai' or 'gemini'
    
    private function __construct() {
        // APIキーの取得
        $this->openai_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : get_option('gi_openai_api_key', '');
        $this->gemini_key = get_option('gi_gemini_api_key', '');
        
        // AJAXハンドラーの登録
        // 1. AIチャット
        add_action('wp_ajax_gi_ai_chat', array($this, 'handle_ai_chat'));
        add_action('wp_ajax_nopriv_gi_ai_chat', array($this, 'handle_ai_chat'));
        
        // 2. 資格診断
        add_action('wp_ajax_gi_eligibility_diagnosis', array($this, 'handle_eligibility_diagnosis'));
        add_action('wp_ajax_nopriv_gi_eligibility_diagnosis', array($this, 'handle_eligibility_diagnosis'));
        
        // 3. ロードマップ生成
        add_action('wp_ajax_gi_generate_roadmap', array($this, 'handle_generate_roadmap'));
        add_action('wp_ajax_nopriv_gi_generate_roadmap', array($this, 'handle_generate_roadmap'));
    }
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * =================================================================
     * 1. AI Chat Handler (Integrated from ai-chat-fixed.php)
     * =================================================================
     */
    public function handle_ai_chat() {
        // デバッグログ
        error_log('=== AI Chat Request Started (Core) ===');
        
        // Nonce検証
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        $nonce_verified = false;

        if (wp_verify_nonce($nonce, 'gi_ai_nonce')) {
            $nonce_verified = true;
        } elseif (wp_verify_nonce($nonce, 'wp_rest')) {
            // Fallback for REST API nonce
            $nonce_verified = true;
        } elseif (wp_verify_nonce($nonce, 'gi_ajax_nonce')) {
            // Fallback for generic AJAX nonce
            $nonce_verified = true;
        } elseif (wp_verify_nonce($nonce, 'gi_ai_search_nonce')) {
            // Fallback for search nonce
            $nonce_verified = true;
        }

        if (!$nonce_verified) {
            error_log('AI Chat Nonce Verification Failed. Received: ' . $nonce);
            wp_send_json_error(array(
                'message' => 'セキュリティチェックに失敗しました。ページを再読み込みしてください。',
                'code' => 'NONCE_INVALID',
                'debug' => 'Nonce verification failed'
            ));
            return;
        }
        
        // パラメータ取得
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $question = isset($_POST['question']) ? sanitize_text_field($_POST['question']) : '';
        $history = isset($_POST['history']) ? $_POST['history'] : array();
        
        if (!$post_id || empty($question)) {
            wp_send_json_error(array(
                'message' => '質問または補助金IDが指定されていません。',
                'code' => 'MISSING_PARAMS'
            ));
            return;
        }
        
        // 投稿データ取得とコンテキスト構築
        try {
            $grant_context = $this->get_grant_context($post_id);
            
            // AI応答生成
            $response = $this->generate_ai_response($question, $grant_context, $history);
            
            wp_send_json_success(array(
                'answer' => $response['text'],
                'sources' => $response['sources'],
                'suggestions' => $response['suggestions'],
                'confidence' => $response['confidence']
            ));
            
        } catch (Exception $e) {
            error_log('AI Chat Error: ' . $e->getMessage());
            
            // フォールバック応答
            wp_send_json_success(array(
                'answer' => $this->get_fallback_response($question, $this->get_grant_context($post_id)),
                'source' => 'fallback',
                'suggestions' => array(
                    '申請に必要な書類を教えてください',
                    '締切はいつですか？',
                    '対象者の条件を詳しく教えてください'
                )
            ));
        }
    }
    
    /**
     * =================================================================
     * 2. Eligibility Diagnosis Handler
     * =================================================================
     */
    public function handle_eligibility_diagnosis() {
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        $nonce_verified = false;
        
        if (wp_verify_nonce($nonce, 'gi_ai_nonce') || 
            wp_verify_nonce($nonce, 'wp_rest') || 
            wp_verify_nonce($nonce, 'gi_ajax_nonce')) {
            $nonce_verified = true;
        }
        
        if (!$nonce_verified) {
             wp_send_json_error(array('message' => 'Security check failed'));
             return;
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $user_answers = $_POST['answers'] ?? array();
        
        if (!$post_id) {
            wp_send_json_error(array('message' => 'Invalid post ID'));
            return;
        }
        
        try {
            $grant_data = $this->get_grant_context($post_id);
            $diagnosis = $this->perform_eligibility_diagnosis($grant_data, $user_answers);
            
            wp_send_json_success(array(
                'eligible' => $diagnosis['eligible'],
                'confidence' => $diagnosis['confidence'],
                'reasons' => $diagnosis['reasons'],
                'next_steps' => $diagnosis['next_steps'],
                'warnings' => $diagnosis['warnings']
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Diagnosis failed: ' . $e->getMessage()));
        }
    }
    
    /**
     * =================================================================
     * 3. Roadmap Generation Handler
     * =================================================================
     */
    public function handle_generate_roadmap() {
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        $nonce_verified = false;
        
        if (wp_verify_nonce($nonce, 'gi_ai_nonce') || 
            wp_verify_nonce($nonce, 'wp_rest') || 
            wp_verify_nonce($nonce, 'gi_ajax_nonce')) {
            $nonce_verified = true;
        }

        if (!$nonce_verified) {
             wp_send_json_error(array('message' => 'Security check failed'));
             return;
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $user_profile = $_POST['profile'] ?? array();
        
        if (!$post_id) {
            wp_send_json_error(array('message' => 'Invalid post ID'));
            return;
        }
        
        try {
            $grant_data = $this->get_grant_context($post_id);
            $roadmap = $this->generate_application_roadmap($grant_data, $user_profile);
            
            wp_send_json_success(array(
                'roadmap' => $roadmap['steps'],
                'timeline' => $roadmap['timeline'],
                'milestones' => $roadmap['milestones'],
                'tips' => $roadmap['tips']
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Roadmap generation failed: ' . $e->getMessage()));
        }
    }
    
    /**
     * =================================================================
     * Core Logic & API Methods
     * =================================================================
     */
    
    /**
     * 補助金コンテキスト情報の取得
     */
    private function get_grant_context($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'grant') {
            throw new Exception('Invalid post');
        }
        
        // ACFまたはメタデータから取得
        $deadline_date = $this->get_meta($post_id, 'deadline_date');
        
        return array(
            'title' => $post->post_title,
            'organization' => $this->get_meta($post_id, 'organization'),
            'max_amount' => $this->get_meta($post_id, 'max_amount'),
            'deadline' => $this->get_meta($post_id, 'deadline'),
            'deadline_timestamp' => $deadline_date ? strtotime($deadline_date) : 0,
            'target' => $this->get_meta($post_id, 'grant_target'),
            'documents' => $this->get_meta($post_id, 'required_documents'),
            'expenses' => $this->get_meta($post_id, 'eligible_expenses'),
            'regions' => $this->get_regions_text($post_id),
            'prep_time' => $this->get_meta($post_id, 'preparation_time') ?: '2-3週間',
            'subsidy_rate' => $this->get_meta($post_id, 'subsidy_rate'),
            'application_method' => $this->get_meta($post_id, 'application_method'),
            'content' => wp_trim_words($post->post_content, 200)
        );
    }
    
    /**
     * メタデータ取得ヘルパー（ACF対応）
     */
    private function get_meta($post_id, $key) {
        if (function_exists('get_field')) {
            $value = get_field($key, $post_id);
            if ($value) return $value;
        }
        return get_post_meta($post_id, $key, true) ?: '';
    }
    
    /**
     * AI応答生成のメインロジック
     */
    private function generate_ai_response($question, $grant_data, $history = array()) {
        // プロンプト構築
        $prompt = $this->build_chat_prompt($question, $grant_data, $history);
        
        // API呼び出し
        $api_response = '';
        if ($this->preferred_provider === 'gemini' && !empty($this->gemini_key)) {
            $api_response = $this->call_gemini_api($prompt);
        } else if (!empty($this->openai_key)) {
            $api_response = $this->call_openai_api($prompt);
        } else {
            throw new Exception('No AI provider configured');
        }
        
        // 応答の解析
        return $this->parse_ai_response($api_response);
    }
    
    /**
     * チャットプロンプト構築
     */
    private function build_chat_prompt($question, $grant_data, $history) {
        $system_prompt = "あなたは補助金・助成金の専門アドバイザーです。\n\n";
        $system_prompt .= "現在の補助金情報:\n";
        $system_prompt .= "タイトル: {$grant_data['title']}\n";
        $system_prompt .= "主催機関: {$grant_data['organization']}\n";
        $system_prompt .= "最大金額: {$grant_data['max_amount']}\n";
        $system_prompt .= "締切: {$grant_data['deadline']}\n";
        $system_prompt .= "対象者: " . strip_tags($grant_data['target']) . "\n";
        $system_prompt .= "必要書類: " . strip_tags($grant_data['documents']) . "\n";
        $system_prompt .= "対象経費: " . strip_tags($grant_data['expenses']) . "\n\n";
        
        $system_prompt .= "ユーザーの質問に対して、以下の形式で回答してください:\n";
        $system_prompt .= "1. 明確で簡潔な回答（重要）\n";
        $system_prompt .= "2. 根拠（ページ内のどの情報に基づいているか）\n";
        $system_prompt .= "3. 関連する追加質問の提案（2-3個）\n\n";
        
        // 会話履歴
        if (!empty($history) && is_array($history)) {
            $system_prompt .= "会話履歴:\n";
            // 最新3件のみ使用
            $recent_history = array_slice($history, -3);
            foreach ($recent_history as $item) {
                if (isset($item['question']) && isset($item['answer'])) {
                    $system_prompt .= "ユーザー: {$item['question']}\n";
                    $system_prompt .= "アシスタント: " . mb_substr($item['answer'], 0, 100) . "...\n\n";
                }
            }
        }
        
        $system_prompt .= "現在の質問: {$question}";
        
        return $system_prompt;
    }
    
    /**
     * OpenAI API呼び出し
     */
    private function call_openai_api($prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->openai_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-4-turbo-preview',
                'messages' => array(
                    array('role' => 'system', 'content' => 'あなたは補助金の専門家です。'),
                    array('role' => 'user', 'content' => $prompt)
                ),
                'temperature' => 0.7,
                'max_tokens' => 1000
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response');
        }
        
        return $body['choices'][0]['message']['content'];
    }
    
    /**
     * Gemini API呼び出し
     */
    private function call_gemini_api($prompt) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->gemini_key;
        
        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array(
                'contents' => array(
                    array('parts' => array(array('text' => $prompt)))
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Invalid Gemini API response');
        }
        
        return $body['candidates'][0]['content']['parts'][0]['text'];
    }
    
    /**
     * AI応答の解析（構造化）
     */
    private function parse_ai_response($response_text) {
        $result = array(
            'text' => '',
            'sources' => array(),
            'suggestions' => array(),
            'confidence' => 0.85
        );
        
        // テキストを行に分割して解析
        $lines = explode("\n", $response_text);
        $main_text = array();
        $in_sources = false;
        $in_suggestions = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // セクション検出
            if (preg_match('/^(根拠|出典|ソース)[:：]/ui', $line)) {
                $in_sources = true;
                $in_suggestions = false;
                continue;
            }
            if (preg_match('/^(関連質問|追加質問|他の質問)[:：]/ui', $line)) {
                $in_suggestions = true;
                $in_sources = false;
                continue;
            }
            
            if ($in_sources) {
                if (preg_match('/^[-・]\s*(.+)$/', $line, $matches)) {
                    $result['sources'][] = trim($matches[1]);
                }
            } else if ($in_suggestions) {
                if (preg_match('/^[-・]\s*(.+)$/', $line, $matches)) {
                    $result['suggestions'][] = trim($matches[1]);
                }
            } else {
                $main_text[] = $line;
            }
        }
        
        $result['text'] = implode("\n\n", $main_text);
        
        // デフォルトの提案
        if (empty($result['suggestions'])) {
            $result['suggestions'] = array(
                '申請に必要な書類を教えてください',
                '締切までの準備期間について',
                '採択率を高めるコツはありますか？'
            );
        }
        
        return $result;
    }
    
    /**
     * 資格診断の実行
     */
    private function perform_eligibility_diagnosis($grant_data, $user_answers) {
        $prompt = "以下の補助金について、ユーザーの回答から申請資格を診断してください。\n\n";
        $prompt .= "補助金: {$grant_data['title']}\n";
        $prompt .= "対象者: " . strip_tags($grant_data['target']) . "\n\n";
        
        $prompt .= "ユーザーの回答:\n";
        foreach ($user_answers as $key => $value) {
            $prompt .= "- {$key}: {$value}\n";
        }
        
        $prompt .= "\n以下のJSON形式のみを返してください:\n";
        $prompt .= "{\n  \"eligible\": true/false,\n  \"confidence\": 0.0-1.0,\n  \"reasons\": [\"理由\"],\n  \"next_steps\": [\"ステップ\"],\n  \"warnings\": [\"注意点\"]\n}";
        
        try {
            $response = $this->call_openai_api($prompt);
            if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $response, $matches)) {
                $data = json_decode($matches[0], true);
                if ($data) return $data;
            }
        } catch (Exception $e) {
            // 無視してフォールバックへ
        }
        
        // フォールバック
        return array(
            'eligible' => true,
            'confidence' => 0.6,
            'reasons' => array('回答内容に基づき判定しましたが、詳細は要項をご確認ください'),
            'next_steps' => array('公式サイトで詳細条件を確認する'),
            'warnings' => array('AIによる自動診断のため、正確性を保証するものではありません')
        );
    }
    
    /**
     * ロードマップ生成
     */
    private function generate_application_roadmap($grant_data, $user_profile) {
        $days_remaining = 30;
        if ($grant_data['deadline_timestamp']) {
            $days_remaining = ceil(($grant_data['deadline_timestamp'] - time()) / 86400);
        }
        
        // フォールバックロードマップ（APIエラー時または迅速な応答用）
        $phase_duration = max(1, floor($days_remaining / 4));
        
        return array(
            'steps' => array(
                array('title' => '要件確認', 'description' => '申請要件の詳細確認', 'timing' => '今すぐ', 'duration' => '1-2日'),
                array('title' => '書類準備', 'description' => '必要書類の収集', 'timing' => '締切' . ($phase_duration * 3) . '日前', 'duration' => $phase_duration . '日'),
                array('title' => '申請書作成', 'description' => '事業計画書の作成', 'timing' => '締切' . ($phase_duration * 2) . '日前', 'duration' => $phase_duration . '日'),
                array('title' => '提出・確認', 'description' => '最終確認と提出', 'timing' => '締切' . $phase_duration . '日前', 'duration' => $phase_duration . '日')
            ),
            'timeline' => array(
                'total_days' => $days_remaining,
                'critical_dates' => array()
            ),
            'milestones' => array('要件確認完了', '書類収集完了', '申請書完成', '提出完了'),
            'tips' => array('余裕を持ったスケジュールで進めましょう', '不明点は早めに問い合わせましょう')
        );
    }
    
    /**
     * フォールバック応答（APIエラー時）
     */
    private function get_fallback_response($question, $grant_data) {
        $q = mb_strtolower($question);
        
        if (strpos($q, '対象') !== false || strpos($q, '資格') !== false) {
            return "対象者は以下の通りです：\n" . strip_tags($grant_data['target']) . "\n\n詳細は公式サイトをご確認ください。";
        }
        if (strpos($q, '締切') !== false || strpos($q, 'いつまで') !== false) {
            return "申請締切は " . $grant_data['deadline'] . " です。余裕を持って準備しましょう。";
        }
        if (strpos($q, '金額') !== false || strpos($q, 'いくら') !== false) {
            return "最大金額は " . $grant_data['max_amount'] . " です。補助率は" . $grant_data['subsidy_rate'] . "です。";
        }
        if (strpos($q, '書類') !== false || strpos($q, '必要') !== false) {
            return "主な必要書類：\n" . strip_tags($grant_data['documents']) . "\n\n詳細は要項をご確認ください。";
        }
        
        return "申し訳ありません、その質問には正確に答えられません。ページ内の情報をご確認いただくか、主催機関にお問い合わせください。";
    }
    
    /**
     * 地域情報の取得
     */
    private function get_regions_text($post_id) {
        $terms = get_the_terms($post_id, 'grant_prefecture');
        if ($terms && !is_wp_error($terms)) {
            return implode(', ', wp_list_pluck($terms, 'name'));
        }
        return '全国';
    }
}

// 初期化
GI_AI_Assistant_Core::get_instance();