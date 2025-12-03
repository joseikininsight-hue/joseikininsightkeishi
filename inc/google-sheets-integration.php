<?php
/**
 * Google Sheets Integration - Optimized Edition
 * 
 * 大規模データ対応（1000-2000件以上）の安定した同期システム
 * 
 * 主な改善点：
 * - メモリ効率の大幅な改善（チャンク処理）
 * - プログレス表示対応のバッチ処理
 * - セキュアな認証情報管理
 * - シンプルで使いやすい管理画面
 * - 包括的なエラーハンドリング
 * - レート制限対応
 * 
 * @package Grant_Insight_Perfect
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// 定数定義
// ============================================================================

if (!defined('GI_SHEETS_VERSION')) {
    define('GI_SHEETS_VERSION', '3.0.0');
}

if (!defined('GI_SHEETS_BATCH_SIZE')) {
    define('GI_SHEETS_BATCH_SIZE', 50); // 一度に処理する件数
}

if (!defined('GI_SHEETS_API_DELAY')) {
    define('GI_SHEETS_API_DELAY', 100000); // API呼び出し間隔（マイクロ秒）
}

if (!defined('GI_SHEETS_MAX_EXECUTION_TIME')) {
    define('GI_SHEETS_MAX_EXECUTION_TIME', 600); // 最大実行時間（秒）
}

if (!defined('GI_SHEETS_MEMORY_LIMIT')) {
    define('GI_SHEETS_MEMORY_LIMIT', '512M');
}

// ============================================================================
// ログ関数
// ============================================================================

if (!function_exists('gi_log')) {
    /**
     * 統合ログ関数
     * 
     * @param string $message ログメッセージ
     * @param array $context 追加情報
     * @param string $level ログレベル (debug, info, warning, error)
     */
    function gi_log($message, $context = array(), $level = 'info') {
        // デバッグモードが無効な場合は debug レベルをスキップ
        if ($level === 'debug' && (!defined('WP_DEBUG') || !WP_DEBUG)) {
            return;
        }
        
        $prefix = '[GI-Sheets][' . strtoupper($level) . ']';
        $log_message = $prefix . ' ' . $message;
        
        if (!empty($context)) {
            $context_json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            if (strlen($context_json) > 2000) {
                $context_json = substr($context_json, 0, 2000) . '...(truncated)';
            }
            $log_message .= ' | ' . $context_json;
        }
        
        error_log($log_message);
    }
}

// 便利なショートカット関数
if (!function_exists('gi_log_error')) {
    function gi_log_error($message, $context = array()) {
        gi_log($message, $context, 'error');
    }
}

if (!function_exists('gi_log_info')) {
    function gi_log_info($message, $context = array()) {
        gi_log($message, $context, 'info');
    }
}

if (!function_exists('gi_log_debug')) {
    function gi_log_debug($message, $context = array()) {
        gi_log($message, $context, 'debug');
    }
}

// ============================================================================
// メインクラス: GoogleSheetsSync
// ============================================================================

/**
 * Google Sheets 同期メインクラス
 * 
 * シングルトンパターンで実装
 * 大規模データに対応した効率的な同期処理を提供
 */
class GoogleSheetsSync {
    
    /** @var GoogleSheetsSync|null シングルトンインスタンス */
    private static $instance = null;
    
    /** @var array サービスアカウントキー */
    private $service_account_key = null;
    
    /** @var string スプレッドシートID */
    private $spreadsheet_id;
    
    /** @var string シート名 */
    private $sheet_name;
    
    /** @var string|null アクセストークン */
    private $access_token = null;
    
    /** @var int トークン有効期限 */
    private $token_expires_at = 0;
    
    /** @var array|null 最後のエラー情報 */
    private $last_error = null;
    
    /** @var array 同期統計 */
    private $sync_stats = array();
    
    // Google Sheets API設定
    const SHEETS_API_URL = 'https://sheets.googleapis.com/v4/spreadsheets/';
    const AUTH_SCOPE = 'https://www.googleapis.com/auth/spreadsheets';
    const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    
    // 列数（31列: A-AE）
    const TOTAL_COLUMNS = 31;
    const COLUMN_RANGE = 'A:AE';
    
    /**
     * シングルトンインスタンス取得
     * 
     * @return GoogleSheetsSync
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * コンストラクタ
     */
    private function __construct() {
        $this->loadConfiguration();
        $this->registerHooks();
    }
    
    /**
     * 設定の読み込み
     */
    private function loadConfiguration() {
        // スプレッドシート設定（定数またはオプションから）
        $this->spreadsheet_id = defined('GI_SHEETS_SPREADSHEET_ID') 
            ? GI_SHEETS_SPREADSHEET_ID 
            : get_option('gi_sheets_spreadsheet_id', '1kGc1Eb4AYvURkSfdzMwipNjfe8xC6iGCM2q1sUgIfWg');
        
        $this->sheet_name = defined('GI_SHEETS_SHEET_NAME')
            ? GI_SHEETS_SHEET_NAME
            : get_option('gi_sheets_sheet_name', 'grant_import');
        
        // サービスアカウントキーの読み込み
        $this->loadServiceAccountKey();
        
        // キャッシュされたトークンの読み込み
        $this->loadCachedToken();
    }
    
    /**
     * サービスアカウントキーの読み込み
     * 
     * 優先順位：
     * 1. 定数 GI_SHEETS_SERVICE_ACCOUNT_JSON（JSON文字列）
     * 2. 定数 GI_SHEETS_SERVICE_ACCOUNT_FILE（ファイルパス）
     * 3. wp-content/uploads/private/service-account.json
     * 4. データベースオプション
     * 5. フォールバック（ハードコード - 開発用）
     */
    private function loadServiceAccountKey() {
        // 1. 定数からJSON文字列
        if (defined('GI_SHEETS_SERVICE_ACCOUNT_JSON')) {
            $decoded = json_decode(GI_SHEETS_SERVICE_ACCOUNT_JSON, true);
            if ($decoded && isset($decoded['private_key'])) {
                $this->service_account_key = $decoded;
                gi_log_debug('Service account loaded from constant');
                return;
            }
        }
        
        // 2. 定数からファイルパス
        if (defined('GI_SHEETS_SERVICE_ACCOUNT_FILE') && file_exists(GI_SHEETS_SERVICE_ACCOUNT_FILE)) {
            $json = file_get_contents(GI_SHEETS_SERVICE_ACCOUNT_FILE);
            $decoded = json_decode($json, true);
            if ($decoded && isset($decoded['private_key'])) {
                $this->service_account_key = $decoded;
                gi_log_debug('Service account loaded from file constant');
                return;
            }
        }
        
        // 3. アップロードディレクトリのprivateフォルダ
        $upload_dir = wp_upload_dir();
        $private_file = $upload_dir['basedir'] . '/private/service-account.json';
        if (file_exists($private_file)) {
            $json = file_get_contents($private_file);
            $decoded = json_decode($json, true);
            if ($decoded && isset($decoded['private_key'])) {
                $this->service_account_key = $decoded;
                gi_log_debug('Service account loaded from private uploads');
                return;
            }
        }
        
        // 4. データベースオプション（暗号化推奨）
        $db_key = get_option('gi_sheets_service_account_key');
        if ($db_key) {
            $decoded = is_string($db_key) ? json_decode($db_key, true) : $db_key;
            if ($decoded && isset($decoded['private_key'])) {
                $this->service_account_key = $decoded;
                gi_log_debug('Service account loaded from database');
                return;
            }
        }
        
        // 5. フォールバック（開発/テスト用）
        $this->service_account_key = array(
            "type" => "service_account",
            "project_id" => "grant-sheets-integration",
            "private_key_id" => "c0fdd6753a43e1c51cbc1854c4ce53cb461b0136",
            "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC+Ba+i0O4k0Jta\n17u3D/hJaqkLuptpyknOhjeQLzOGl9GtRP88KYX+NpKO1RxuuZMmlBt/7ShlXDPk\nJXdOtOjPlMzHZeh32M/f+98L9S9PVfapGUKRV0p4XJmExljmP7AVnXaMjlXqm9BJ\ngvO7K898LApyAsdrtcOYgt371LWZbQdTqpNWQemfJcYnTndwMcYzv6Snm/lOUruD\nrV2VOhvsMfqwVOaKywhE6rvUrF1ARaT3meQJyF9CpqFcb947f5phRUVD1QEdQp1K\nfGeFmMqR3nT4sY6I7VVqnseyr7v6U4i9V2aaL8KhUmH895xRlL6cc+QR7lgPtkT3\nZ8FJdseLAgMBAAECggEAWj9OFrg+2jo/Bmp+SyepBolDJwBl7lz2J8Fj4zUfthUl\nrrKdu9+GtWEKww5g1g+J3SErXFrwvA8J0BmhK77M8UWc6jiyqzTMKXcwjDfS082i\ne9Y04N1Bz58/BCnFr/jgcquZ0ZCKKoX86uToR+U7QiCSh2pddwDZF/ZTYla4NtiZ\nP/uZBAIuO/Fz2bLnjzQrQ1tLBdgY3mWx/wChi6+JhqubiNTnrWqy8qXG8P2OieZS\nQxU31/EjOp8rK4ErxqN5WDS0BRhIKM0DTN3WXwB8Sb5JCSluxksdICvNshiilsVF\nQGsXF3pGZA6Okv9cJS0u6vUoYVMMSzeWQvyM0tKwuQKBgQDgrUS2K21sVun+mI3L\niQ99XlMDT0AhsDaSWyenqveNawosoKz3ueBXEwkpOcM8DdcTDKbZVohM7h1cTEax\nPobdj2bQdUFWkzup5kekVBu88bIPthTMK5IuTUcHYyfiH8V7vsEtrX184UAiET/p\nXmHZ+lcUCuL+8+uKogEdvy/1UwKBgQDYg5eJlQ0hoOH0VP8HkSeJSn246X8CdeHT\n1kgkymJcLwWYr+EKngTQrSkLkIfxBER3UMfHtla95IL4qGC/iNcIWbie2Gtc2wXz\nWvwpaoliReoKOYyFG94Fl5zdcp5xYi2oA2qB9LM+eyCqqEEkVhpg3w61Xfj03wMI\n6Ibxc0al6QKBgQC7KVut7WtP7u8qOWcVgG244BSDE0e3SJWNQgY8tD1YPyzQlGDC\nVMM/hgoBn661nknmAooTTvRoMYuf0aKqEA5FDyp0yNjPCAORutU/XRlmQmk0kVet\n5TX3AEUFMGKPCix2syc1p+p7VyEXwArfmtIkxVg4yADkpck3SVFouFV5JQKBgDcz\njb45L0jkoNdPmFoQixj40gcEGSrCbVo6JtiidON15aJhLSos0aN2kqFtLwum/+G/\nyb/EYGc3zKCjJU+QDusFHQn6uZzKBsFd8C6LCA3zL1F+DLKfQUMBva/EGltkIanV\nfSE3B0Al2lVIYptmDIGoPTLGi8O63CY4SrdioZ+JAoGAMjzeU4jqFtkXaiRBTa+v\njspaqbk1rq1x4ZmnPMZzMQnZLStP9QP7SQn5/my/ZSWcnmjxW8ZgMdfWB1TD51RC\n4HYL/jGrjOUmumshQmiA1a7zCvr8yVJFkOVcYpCWl6TT5hiFbqrW82Dw73JFHTuK\n30Chu7ki9aOiJJeMmHaOfOU=\n-----END PRIVATE KEY-----\n",
            "client_email" => "grant-sheets-service@grant-sheets-integration.iam.gserviceaccount.com",
            "client_id" => "109769300820349787611",
            "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
            "token_uri" => "https://oauth2.googleapis.com/token",
            "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
            "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/grant-sheets-service%40grant-sheets-integration.iam.gserviceaccount.com",
            "universe_domain" => "googleapis.com"
        );
        
        gi_log_info('Service account loaded from fallback (development mode)');
    }
    
    /**
     * キャッシュされたトークンの読み込み
     */
    private function loadCachedToken() {
        $stored_token = get_transient('gi_sheets_access_token');
        $stored_expires = get_transient('gi_sheets_token_expires');
        
        if ($stored_token && $stored_expires && time() < ($stored_expires - 300)) {
            $this->access_token = $stored_token;
            $this->token_expires_at = $stored_expires;
            gi_log_debug('Cached token loaded');
        }
    }
    
    /**
     * WordPressフックの登録
     */
    private function registerHooks() {
        // 投稿保存時の同期（オプション）
        if (get_option('gi_sheets_auto_sync_on_save', false)) {
            add_action('save_post_grant', array($this, 'onPostSave'), 20, 3);
        }
        
        // AJAX ハンドラー
        add_action('wp_ajax_gi_sheets_sync', array($this, 'ajaxSync'));
        add_action('wp_ajax_gi_sheets_test_connection', array($this, 'ajaxTestConnection'));
        add_action('wp_ajax_gi_sheets_initialize', array($this, 'ajaxInitialize'));
        add_action('wp_ajax_gi_sheets_export_all', array($this, 'ajaxExportAll'));
        add_action('wp_ajax_gi_sheets_get_progress', array($this, 'ajaxGetProgress'));
        add_action('wp_ajax_gi_sheets_cancel_sync', array($this, 'ajaxCancelSync'));
        add_action('wp_ajax_gi_sheets_clear_data', array($this, 'ajaxClearData'));
        add_action('wp_ajax_gi_sheets_check_duplicates', array($this, 'ajaxCheckDuplicates'));
        add_action('wp_ajax_gi_sheets_export_duplicates', array($this, 'ajaxExportDuplicates'));
        add_action('wp_ajax_gi_sheets_validate_prefectures', array($this, 'ajaxValidatePrefectures'));
        add_action('wp_ajax_gi_sheets_export_taxonomies', array($this, 'ajaxExportTaxonomies'));
        add_action('wp_ajax_gi_sheets_import_taxonomies', array($this, 'ajaxImportTaxonomies'));
        
        // 定期同期（オプション）
        add_action('gi_sheets_scheduled_sync', array($this, 'scheduledSync'));
    }
    
    // ========================================================================
    // 認証関連メソッド
    // ========================================================================
    
    /**
     * アクセストークンの取得
     * 
     * @return string|false アクセストークンまたはfalse
     */
    public function getAccessToken() {
        // 有効なキャッシュトークンがあればそれを使用
        if ($this->access_token && $this->token_expires_at && time() < ($this->token_expires_at - 300)) {
            return $this->access_token;
        }
        
        gi_log_debug('Requesting new access token');
        
        // JWTを作成
        $jwt = $this->createJWT();
        if (!$jwt) {
            $this->setError('JWTの作成に失敗しました');
            return false;
        }
        
        // トークンリクエスト
        $response = wp_remote_post(self::TOKEN_URL, array(
            'body' => array(
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ),
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            $this->setError('トークンリクエストに失敗しました', array(
                'error' => $response->get_error_message()
            ));
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $token_data = json_decode($body, true);
        
        if ($response_code !== 200 || !isset($token_data['access_token'])) {
            $this->setError('無効なトークンレスポンス', array(
                'response_code' => $response_code,
                'response' => $body
            ));
            return false;
        }
        
        // トークンをキャッシュ
        $this->access_token = $token_data['access_token'];
        $this->token_expires_at = time() + ($token_data['expires_in'] - 300);
        
        set_transient('gi_sheets_access_token', $this->access_token, $token_data['expires_in'] - 300);
        set_transient('gi_sheets_token_expires', $this->token_expires_at, $token_data['expires_in'] - 300);
        
        gi_log_debug('New access token obtained');
        
        return $this->access_token;
    }
    
    /**
     * JWT（JSON Web Token）の作成
     * 
     * @return string|false JWT文字列またはfalse
     */
    private function createJWT() {
        if (!$this->service_account_key || !isset($this->service_account_key['private_key'])) {
            gi_log_error('Service account key not configured');
            return false;
        }
        
        $header = json_encode(array(
            'alg' => 'RS256',
            'typ' => 'JWT'
        ));
        
        $now = time();
        $payload = json_encode(array(
            'iss' => $this->service_account_key['client_email'],
            'scope' => self::AUTH_SCOPE,
            'aud' => self::TOKEN_URL,
            'exp' => $now + 3600,
            'iat' => $now
        ));
        
        $base64_header = $this->base64urlEncode($header);
        $base64_payload = $this->base64urlEncode($payload);
        $signature_input = $base64_header . '.' . $base64_payload;
        
        // 秘密鍵で署名
        $private_key = $this->service_account_key['private_key'];
        $sign_result = openssl_sign($signature_input, $signature, $private_key, OPENSSL_ALGO_SHA256);
        
        if (!$sign_result) {
            gi_log_error('OpenSSL signing failed', array('error' => openssl_error_string()));
            return false;
        }
        
        return $signature_input . '.' . $this->base64urlEncode($signature);
    }
    
    /**
     * Base64URL エンコード
     * 
     * @param string $data エンコードするデータ
     * @return string エンコード結果
     */
    private function base64urlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    // ========================================================================
    // スプレッドシート操作メソッド
    // ========================================================================
    
    /**
     * スプレッドシートからデータを読み取り
     * 
     * @param string|null $range 読み取り範囲
     * @return array|false データ配列またはfalse
     */
    public function readSheetData($range = null) {
        $access_token = $this->getAccessToken();
        if (!$access_token) {
            return false;
        }
        
        if (!$range) {
            $range = $this->sheet_name . '!' . self::COLUMN_RANGE;
        }
        
        $url = self::SHEETS_API_URL . $this->spreadsheet_id . '/values/' . urlencode($range);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            $this->setError('シートの読み取りに失敗しました', array(
                'error' => $response->get_error_message()
            ));
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            $this->setError('シート読み取りエラー', array(
                'response_code' => $response_code,
                'response' => $body
            ));
            return false;
        }
        
        $data = json_decode($body, true);
        return isset($data['values']) ? $data['values'] : array();
    }
    
    /**
     * スプレッドシートにデータを書き込み
     * 
     * @param string $range 書き込み範囲
     * @param array $values 書き込むデータ
     * @param string $input_option 入力オプション (RAW または USER_ENTERED)
     * @return bool 成功/失敗
     */
    public function writeSheetData($range, $values, $input_option = 'RAW') {
        $access_token = $this->getAccessToken();
        if (!$access_token) {
            return false;
        }
        
        // 範囲にシート名が含まれていなければ追加
        if (strpos($range, '!') === false) {
            $range = $this->sheet_name . '!' . $range;
        }
        
        $url = self::SHEETS_API_URL . $this->spreadsheet_id . '/values/' . urlencode($range) . '?valueInputOption=' . $input_option;
        
        $request_body = array(
            'range' => $range,
            'majorDimension' => 'ROWS',
            'values' => $values
        );
        
        $response = wp_remote_request($url, array(
            'method' => 'PUT',
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json; charset=utf-8'
            ),
            'body' => json_encode($request_body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            $this->setError('シートへの書き込みに失敗しました', array(
                'error' => $response->get_error_message()
            ));
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code < 200 || $response_code >= 300) {
            $this->setError('シート書き込みエラー', array(
                'response_code' => $response_code,
                'response' => wp_remote_retrieve_body($response)
            ));
            return false;
        }
        
        // API レート制限対策
        usleep(GI_SHEETS_API_DELAY);
        
        return true;
    }
    
    /**
     * スプレッドシートにデータを追記
     * 
     * @param array $values 追記するデータ（1行分）
     * @param string $input_option 入力オプション
     * @return bool 成功/失敗
     */
    public function appendSheetData($values, $input_option = 'RAW') {
        $access_token = $this->getAccessToken();
        if (!$access_token) {
            return false;
        }
        
        $url = self::SHEETS_API_URL . $this->spreadsheet_id . '/values/' . urlencode($this->sheet_name) . ':append?valueInputOption=' . $input_option;
        
        $request_body = array(
            'range' => $this->sheet_name,
            'majorDimension' => 'ROWS',
            'values' => is_array($values[0]) ? $values : array($values)
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json; charset=utf-8'
            ),
            'body' => json_encode($request_body, JSON_UNESCAPED_UNICODE),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            $this->setError('データの追記に失敗しました', array(
                'error' => $response->get_error_message()
            ));
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        usleep(GI_SHEETS_API_DELAY);
        
        return $response_code >= 200 && $response_code < 300;
    }
    
    /**
     * スプレッドシートの範囲をクリア
     * 
     * @param string $range クリアする範囲
     * @return bool 成功/失敗
     */
    public function clearSheetRange($range) {
        $access_token = $this->getAccessToken();
        if (!$access_token) {
            return false;
        }
        
        // 範囲にシート名が含まれていなければ追加
        if (strpos($range, '!') === false) {
            $range = $this->sheet_name . '!' . $range;
        }
        
        $url = self::SHEETS_API_URL . $this->spreadsheet_id . '/values/' . urlencode($range) . ':clear';
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => '{}',
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            $this->setError('シートのクリアに失敗しました', array(
                'error' => $response->get_error_message()
            ));
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code === 200;
    }
    
    /**
     * シートが存在するかチェック
     * 
     * @param string $sheet_name シート名
     * @return bool 存在する/しない
     */
    public function sheetExists($sheet_name) {
        $access_token = $this->getAccessToken();
        if (!$access_token) {
            return false;
        }
        
        $url = self::SHEETS_API_URL . $this->spreadsheet_id . '?fields=sheets.properties.title';
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['sheets'])) {
            return false;
        }
        
        foreach ($data['sheets'] as $sheet) {
            if (isset($sheet['properties']['title']) && $sheet['properties']['title'] === $sheet_name) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 新しいシートを作成
     * 
     * @param string $sheet_name シート名
     * @return array|false シートプロパティまたはfalse
     */
    public function createSheet($sheet_name) {
        $access_token = $this->getAccessToken();
        if (!$access_token) {
            return false;
        }
        
        $url = self::SHEETS_API_URL . $this->spreadsheet_id . ':batchUpdate';
        
        $request_body = array(
            'requests' => array(
                array(
                    'addSheet' => array(
                        'properties' => array(
                            'title' => $sheet_name,
                            'gridProperties' => array(
                                'frozenRowCount' => 1
                            )
                        )
                    )
                )
            )
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json; charset=utf-8'
            ),
            'body' => json_encode($request_body, JSON_UNESCAPED_UNICODE),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            $this->setError('シートの作成に失敗しました', array(
                'error' => $response->get_error_message()
            ));
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($response_code < 200 || $response_code >= 300) {
            $this->setError('シート作成エラー', array(
                'response_code' => $response_code,
                'response' => $body
            ));
            return false;
        }
        
        $result = json_decode($body, true);
        
        if (isset($result['replies'][0]['addSheet']['properties'])) {
            return $result['replies'][0]['addSheet']['properties'];
        }
        
        return false;
    }
    
    // ========================================================================
    // 同期メソッド（大規模データ対応）
    // ========================================================================
    
    /**
     * スプレッドシートからWordPressへの同期（メイン）
     * 
     * バッチ処理による大規模データ対応版
     * 
     * @return array 同期結果
     */
    public function syncFromSheets() {
        gi_log_info('Starting sync from sheets');
        
        // 実行環境の設定
        $this->setupExecutionEnvironment();
        
        // 同期状態の初期化
        $this->initSyncProgress('sheets_to_wp');
        
        try {
            // シートデータの読み取り
            $sheet_data = $this->readSheetData();
            if ($sheet_data === false || empty($sheet_data)) {
                throw new Exception('シートデータの読み取りに失敗しました');
            }
            
            // ヘッダー行を除去
            $headers = array_shift($sheet_data);
            $total_rows = count($sheet_data);
            
            $this->updateSyncProgress(array(
                'total' => $total_rows,
                'status' => 'processing'
            ));
            
            gi_log_info('Sheet data loaded', array('total_rows' => $total_rows));
            
            // 統計初期化
            $stats = array(
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
                'skipped' => 0,
                'errors' => 0
            );
            
            // 新規投稿のID更新用
            $new_post_ids = array();
            
            // バッチ処理
            $batch_size = GI_SHEETS_BATCH_SIZE;
            $batches = array_chunk($sheet_data, $batch_size, true);
            $processed = 0;
            
            foreach ($batches as $batch_index => $batch) {
                // キャンセルチェック
                if ($this->isSyncCancelled()) {
                    gi_log_info('Sync cancelled by user');
                    break;
                }
                
                foreach ($batch as $row_index => $row) {
                    try {
                        $result = $this->processSheetRow($row, $row_index + 2); // +2 はヘッダー行と0-indexed調整
                        
                        switch ($result['action']) {
                            case 'created':
                                $stats['created']++;
                                if (isset($result['post_id']) && isset($result['row_number'])) {
                                    $new_post_ids[$result['row_number']] = $result['post_id'];
                                }
                                break;
                            case 'updated':
                                $stats['updated']++;
                                break;
                            case 'deleted':
                                $stats['deleted']++;
                                break;
                            case 'skipped':
                                $stats['skipped']++;
                                break;
                        }
                        
                    } catch (Exception $e) {
                        $stats['errors']++;
                        gi_log_error('Row processing error', array(
                            'row' => $row_index + 2,
                            'error' => $e->getMessage()
                        ));
                    }
                    
                    $processed++;
                }
                
                // プログレス更新
                $this->updateSyncProgress(array(
                    'processed' => $processed,
                    'stats' => $stats
                ));
                
                // メモリクリア
                $this->cleanupMemory();
                
                gi_log_debug('Batch processed', array(
                    'batch' => $batch_index + 1,
                    'processed' => $processed,
                    'memory' => $this->getMemoryUsage()
                ));
            }
            
            // 新規投稿IDをシートに書き戻し
            if (!empty($new_post_ids)) {
                $this->updateSheetPostIds($new_post_ids);
            }
            
            // 完了
            $this->updateSyncProgress(array(
                'status' => 'completed',
                'stats' => $stats,
                'completed_at' => current_time('mysql')
            ));
            
            $this->logSyncResult('sheets_to_wp', $stats);
            
            gi_log_info('Sync from sheets completed', $stats);
            
            return array(
                'success' => true,
                'stats' => $stats,
                'message' => $this->formatSyncResultMessage($stats)
            );
            
        } catch (Exception $e) {
            $this->updateSyncProgress(array(
                'status' => 'error',
                'error' => $e->getMessage()
            ));
            
            gi_log_error('Sync from sheets failed', array('error' => $e->getMessage()));
            
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * シートの1行を処理
     * 
     * @param array $row 行データ
     * @param int $row_number 行番号
     * @return array 処理結果
     */
    private function processSheetRow($row, $row_number) {
        // 空行チェック
        if (empty($row) || count($row) < 2) {
            return array('action' => 'skipped', 'reason' => 'empty_row');
        }
        
        $post_id = !empty($row[0]) ? intval($row[0]) : 0;
        $title = isset($row[1]) ? trim(sanitize_text_field($row[1])) : '';
        
        // タイトルが空の場合はスキップ
        if (empty($title)) {
            return array('action' => 'skipped', 'reason' => 'empty_title');
        }
        
        // ステータス取得
        $status = isset($row[4]) ? sanitize_text_field($row[4]) : 'draft';
        $valid_statuses = array('draft', 'publish', 'private', 'pending', 'deleted');
        if (!in_array($status, $valid_statuses)) {
            $status = 'draft';
        }
        
        // 削除処理
        if ($status === 'deleted') {
            if ($post_id && get_post($post_id)) {
                wp_delete_post($post_id, true);
                return array('action' => 'deleted', 'post_id' => $post_id);
            }
            return array('action' => 'skipped', 'reason' => 'already_deleted');
        }
        
        // 投稿データを準備
        $post_data = array(
            'post_title' => $title,
            'post_content' => isset($row[2]) ? $row[2] : '',
            'post_excerpt' => isset($row[3]) ? sanitize_textarea_field($row[3]) : '',
            'post_status' => $status,
            'post_type' => 'grant'
        );
        
        // 既存投稿の更新または新規作成
        $existing_post = null;
        
        if ($post_id && get_post($post_id)) {
            // IDで既存投稿を更新
            $post_data['ID'] = $post_id;
            $result_id = wp_update_post($post_data, true);
            
            if (is_wp_error($result_id)) {
                throw new Exception($result_id->get_error_message());
            }
            
            $action = 'updated';
            
        } else {
            // タイトルで既存投稿を検索
            $existing_post = $this->findPostByTitle($title);
            
            if ($existing_post) {
                // 既存投稿を更新
                $post_data['ID'] = $existing_post->ID;
                $result_id = wp_update_post($post_data, true);
                $post_id = $existing_post->ID;
                $action = 'updated';
            } else {
                // 新規投稿を作成
                $result_id = wp_insert_post($post_data, true);
                $post_id = $result_id;
                $action = 'created';
            }
            
            if (is_wp_error($result_id)) {
                throw new Exception($result_id->get_error_message());
            }
        }
        
        // ACFフィールドとタクソノミーを更新
        $this->updatePostMeta($post_id, $row);
        $this->updatePostTaxonomies($post_id, $row);
        
        return array(
            'action' => $action,
            'post_id' => $post_id,
            'row_number' => $row_number
        );
    }
    
    /**
     * タイトルで投稿を検索
     * 
     * @param string $title タイトル
     * @return WP_Post|null 投稿オブジェクトまたはnull
     */
    private function findPostByTitle($title) {
        global $wpdb;
        
        $post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_type = 'grant' 
             AND post_status IN ('publish', 'draft', 'private', 'pending')
             AND post_title = %s
             LIMIT 1",
            $title
        ));
        
        return $post_id ? get_post($post_id) : null;
    }
    
    /**
     * 投稿のメタ情報（ACFフィールド）を更新
     * 
     * @param int $post_id 投稿ID
     * @param array $row シートの行データ
     */
    private function updatePostMeta($post_id, $row) {
        // ACFフィールドマッピング（列インデックス => フィールド名）
        $acf_fields = array(
            7  => 'max_amount',
            8  => 'max_amount_numeric',
            9  => 'deadline',
            10 => 'deadline_date',
            11 => 'organization',
            12 => 'organization_type',
            13 => 'grant_target',
            14 => 'application_method',
            15 => 'contact_info',
            16 => 'official_url',
            17 => 'regional_limitation',
            18 => 'application_status',
            23 => 'external_link',
            24 => 'area_notes',
            25 => 'required_documents_detailed',
            26 => 'adoption_rate',
            27 => 'difficulty_level',
            28 => 'eligible_expenses_detailed',
            29 => 'subsidy_rate_detailed',
        );
        
        foreach ($acf_fields as $col_index => $field_name) {
            if (isset($row[$col_index])) {
                $value = $row[$col_index];
                
                // 数値フィールドの処理
                if (in_array($field_name, array('max_amount_numeric', 'adoption_rate'))) {
                    $value = is_numeric($value) ? floatval($value) : 0;
                }
                
                // 日付フィールドの処理
                if ($field_name === 'deadline_date' && !empty($value) && $value !== '0000-00-00') {
                    $timestamp = strtotime($value);
                    if ($timestamp !== false) {
                        $value = date('Y-m-d', $timestamp);
                    }
                }
                
                if (function_exists('update_field')) {
                    update_field($field_name, $value, $post_id);
                } else {
                    update_post_meta($post_id, $field_name, $value);
                }
            }
        }
    }
    
    /**
     * 投稿のタクソノミーを更新
     * 
     * @param int $post_id 投稿ID
     * @param array $row シートの行データ
     */
    private function updatePostTaxonomies($post_id, $row) {
        // タクソノミーマッピング（列インデックス => タクソノミー名）
        $taxonomy_fields = array(
            19 => 'grant_prefecture',
            20 => 'grant_municipality',
            21 => 'grant_category',
            22 => 'grant_tag',
        );
        
        foreach ($taxonomy_fields as $col_index => $taxonomy) {
            if (isset($row[$col_index]) && !empty($row[$col_index])) {
                $terms = array_filter(array_map('trim', explode(',', $row[$col_index])));
                $this->setTermsWithAutoCreate($post_id, $terms, $taxonomy);
            }
        }
    }
    
    /**
     * タームを自動作成して設定
     * 
     * @param int $post_id 投稿ID
     * @param array $terms タームの配列
     * @param string $taxonomy タクソノミー名
     */
    private function setTermsWithAutoCreate($post_id, $terms, $taxonomy) {
        if (empty($terms)) {
            wp_set_post_terms($post_id, array(), $taxonomy);
            return;
        }
        
        $term_ids = array();
        
        foreach ($terms as $term_name) {
            $term_name = trim($term_name);
            if (empty($term_name)) {
                continue;
            }
            
            $existing_term = term_exists($term_name, $taxonomy);
            
            if ($existing_term) {
                $term_ids[] = (int) $existing_term['term_id'];
            } else {
                $new_term = wp_insert_term($term_name, $taxonomy);
                if (!is_wp_error($new_term)) {
                    $term_ids[] = (int) $new_term['term_id'];
                }
            }
        }
        
        wp_set_post_terms($post_id, $term_ids, $taxonomy);
    }
    
    /**
     * 新規投稿IDをシートに書き戻し
     * 
     * @param array $new_post_ids 行番号 => 投稿ID の配列
     */
    private function updateSheetPostIds($new_post_ids) {
        gi_log_info('Updating sheet with new post IDs', array('count' => count($new_post_ids)));
        
        foreach ($new_post_ids as $row_number => $post_id) {
            $range = $this->sheet_name . '!A' . $row_number;
            $this->writeSheetData($range, array(array($post_id)));
        }
    }
    
    /**
     * WordPressからスプレッドシートへの全件エクスポート
     * 
     * @return array エクスポート結果
     */
    public function exportAllPosts() {
        gi_log_info('Starting export all posts');
        
        $this->setupExecutionEnvironment();
        $this->initSyncProgress('wp_to_sheets');
        
        try {
            // 投稿数をカウント
            $post_counts = wp_count_posts('grant');
            $total = $post_counts->publish + $post_counts->draft + $post_counts->private;
            
            if ($total === 0) {
                return array(
                    'success' => true,
                    'exported' => 0,
                    'message' => 'エクスポートする投稿がありません'
                );
            }
            
            $this->updateSyncProgress(array(
                'total' => $total,
                'status' => 'processing'
            ));
            
            // シートをクリアしてヘッダーを設定
            $this->clearSheetRange('A:AE');
            $this->setupSheetHeaders();
            
            // バッチ処理でエクスポート
            $batch_size = GI_SHEETS_BATCH_SIZE;
            $page = 1;
            $exported = 0;
            $current_row = 2; // ヘッダー行の次
            
            while (true) {
                if ($this->isSyncCancelled()) {
                    gi_log_info('Export cancelled by user');
                    break;
                }
                
                $posts = get_posts(array(
                    'post_type' => 'grant',
                    'post_status' => array('publish', 'draft', 'private'),
                    'posts_per_page' => $batch_size,
                    'paged' => $page,
                    'orderby' => 'ID',
                    'order' => 'ASC'
                ));
                
                if (empty($posts)) {
                    break;
                }
                
                // バッチデータを準備
                $batch_data = array();
                foreach ($posts as $post) {
                    $row_data = $this->convertPostToRow($post->ID);
                    if ($row_data) {
                        $batch_data[] = $row_data;
                    }
                }
                
                // バッチを書き込み
                if (!empty($batch_data)) {
                    $end_row = $current_row + count($batch_data) - 1;
                    $range = $this->sheet_name . '!A' . $current_row . ':AE' . $end_row;
                    
                    $result = $this->writeSheetData($range, $batch_data);
                    
                    if ($result) {
                        $exported += count($batch_data);
                        $current_row = $end_row + 1;
                    }
                }
                
                // プログレス更新
                $this->updateSyncProgress(array(
                    'processed' => $exported
                ));
                
                // 次のページへ
                $posts_count = count($posts);
                $page++;
                
                // メモリクリア
                unset($posts);
                unset($batch_data);
                $this->cleanupMemory();
                
                if ($posts_count < $batch_size) {
                    break;
                }
            }
            
            $this->updateSyncProgress(array(
                'status' => 'completed',
                'completed_at' => current_time('mysql')
            ));
            
            gi_log_info('Export completed', array('exported' => $exported));
            
            return array(
                'success' => true,
                'exported' => $exported,
                'message' => "{$exported} 件の投稿をエクスポートしました"
            );
            
        } catch (Exception $e) {
            $this->updateSyncProgress(array(
                'status' => 'error',
                'error' => $e->getMessage()
            ));
            
            gi_log_error('Export failed', array('error' => $e->getMessage()));
            
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * 投稿データをシート用の行に変換
     * 
     * @param int $post_id 投稿ID
     * @return array|false 行データまたはfalse
     */
    private function convertPostToRow($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'grant') {
            return false;
        }
        
        // 基本データ
        $row = array(
            $post_id,                    // A: ID
            $post->post_title,           // B: タイトル
            $post->post_content,         // C: 内容
            $post->post_excerpt,         // D: 抜粋
            $post->post_status,          // E: ステータス
            $post->post_date,            // F: 作成日
            $post->post_modified,        // G: 更新日
        );
        
        // ACFフィールド（H-S列）
        $acf_fields = array(
            'max_amount', 'max_amount_numeric', 'deadline', 'deadline_date',
            'organization', 'organization_type', 'grant_target', 'application_method',
            'contact_info', 'official_url', 'regional_limitation', 'application_status'
        );
        
        foreach ($acf_fields as $field) {
            $value = function_exists('get_field') ? get_field($field, $post_id, false) : get_post_meta($post_id, $field, true);
            $row[] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
        }
        
        // タクソノミー（T-W列）
        $taxonomies = array('grant_prefecture', 'grant_municipality', 'grant_category', 'grant_tag');
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'names'));
            $row[] = (is_array($terms) && !is_wp_error($terms)) ? implode(', ', $terms) : '';
        }
        
        // 追加フィールド（X-AD列）
        $extra_fields = array(
            'external_link', 'area_notes', 'required_documents_detailed',
            'adoption_rate', 'difficulty_level', 'eligible_expenses_detailed', 'subsidy_rate_detailed'
        );
        
        foreach ($extra_fields as $field) {
            $value = function_exists('get_field') ? get_field($field, $post_id, false) : get_post_meta($post_id, $field, true);
            $row[] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
        }
        
        // シート更新日（AE列）
        $row[] = current_time('mysql');
        
        return $row;
    }
    
    /**
     * ヘッダー行を設定
     * 
     * @return bool 成功/失敗
     */
    public function setupSheetHeaders() {
        $headers = array(
            'ID', 'タイトル', '内容・詳細', '抜粋・概要',
            'ステータス', '作成日', '更新日',
            '助成金額', '助成金額(数値)', '申請期限', '申請期限(日付)',
            '実施組織', '組織タイプ', '対象者・対象事業', '申請方法',
            '問い合わせ先', '公式URL', '地域制限', '申請ステータス',
            '都道府県', '市町村', 'カテゴリ', 'タグ',
            '外部リンク', '地域備考', '必要書類', '採択率',
            '申請難易度', '対象経費', '補助率', 'シート更新日'
        );
        
        $range = $this->sheet_name . '!A1:AE1';
        return $this->writeSheetData($range, array($headers));
    }
    
    // ========================================================================
    // プログレス管理
    // ========================================================================
    
    /**
     * 同期プログレスの初期化
     * 
     * @param string $type 同期タイプ
     */
    private function initSyncProgress($type) {
        $progress = array(
            'type' => $type,
            'status' => 'starting',
            'total' => 0,
            'processed' => 0,
            'stats' => array(),
            'started_at' => current_time('mysql'),
            'cancelled' => false
        );
        
        set_transient('gi_sheets_sync_progress', $progress, 3600);
    }
    
    /**
     * 同期プログレスの更新
     * 
     * @param array $updates 更新データ
     */
    private function updateSyncProgress($updates) {
        $progress = get_transient('gi_sheets_sync_progress');
        if (!$progress) {
            $progress = array();
        }
        
        $progress = array_merge($progress, $updates);
        set_transient('gi_sheets_sync_progress', $progress, 3600);
    }
    
    /**
     * 同期プログレスの取得
     * 
     * @return array プログレス情報
     */
    public function getSyncProgress() {
        $progress = get_transient('gi_sheets_sync_progress');
        
        if (!$progress) {
            return array(
                'status' => 'idle',
                'total' => 0,
                'processed' => 0
            );
        }
        
        // パーセンテージを計算
        if ($progress['total'] > 0) {
            $progress['percentage'] = round(($progress['processed'] / $progress['total']) * 100, 1);
        } else {
            $progress['percentage'] = 0;
        }
        
        return $progress;
    }
    
    /**
     * 同期がキャンセルされたかチェック
     * 
     * @return bool キャンセルされたかどうか
     */
    private function isSyncCancelled() {
        $progress = get_transient('gi_sheets_sync_progress');
        return $progress && isset($progress['cancelled']) && $progress['cancelled'];
    }
    
    /**
     * 同期をキャンセル
     */
    public function cancelSync() {
        $this->updateSyncProgress(array('cancelled' => true));
    }
    
    // ========================================================================
    // ユーティリティメソッド
    // ========================================================================
    
    /**
     * 実行環境の設定
     */
    private function setupExecutionEnvironment() {
        @set_time_limit(GI_SHEETS_MAX_EXECUTION_TIME);
        @ini_set('memory_limit', GI_SHEETS_MEMORY_LIMIT);
        wp_suspend_cache_addition(true);
    }
    
    /**
     * メモリクリーンアップ
     */
    private function cleanupMemory() {
        wp_cache_flush();
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
    
    /**
     * メモリ使用量の取得
     * 
     * @return string メモリ使用量
     */
    private function getMemoryUsage() {
        return round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB';
    }
    
    /**
     * エラーを設定
     * 
     * @param string $message エラーメッセージ
     * @param array $details 詳細情報
     */
    private function setError($message, $details = array()) {
        $this->last_error = array(
            'message' => $message,
            'details' => $details,
            'timestamp' => current_time('mysql')
        );
        
        gi_log_error($message, $details);
    }
    
    /**
     * 最後のエラーを取得
     * 
     * @return array|null エラー情報
     */
    public function getLastError() {
        return $this->last_error;
    }
    
    /**
     * 同期結果メッセージのフォーマット
     * 
     * @param array $stats 統計情報
     * @return string フォーマット済みメッセージ
     */
    private function formatSyncResultMessage($stats) {
        $parts = array();
        
        if ($stats['created'] > 0) {
            $parts[] = "作成: {$stats['created']}件";
        }
        if ($stats['updated'] > 0) {
            $parts[] = "更新: {$stats['updated']}件";
        }
        if ($stats['deleted'] > 0) {
            $parts[] = "削除: {$stats['deleted']}件";
        }
        if ($stats['skipped'] > 0) {
            $parts[] = "スキップ: {$stats['skipped']}件";
        }
        if ($stats['errors'] > 0) {
            $parts[] = "エラー: {$stats['errors']}件";
        }
        
        return empty($parts) ? '処理対象がありませんでした' : implode(', ', $parts);
    }
    
    /**
     * 同期結果をログに記録
     * 
     * @param string $type 同期タイプ
     * @param array $stats 統計情報
     */
    private function logSyncResult($type, $stats) {
        $logs = get_option('gi_sheets_sync_log', array());
        
        $logs[] = array(
            'type' => $type,
            'timestamp' => time(),
            'stats' => $stats,
            'message' => $this->formatSyncResultMessage($stats)
        );
        
        // 最大100件を保持
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option('gi_sheets_sync_log', $logs);
        update_option('gi_sheets_last_sync', current_time('mysql'));
    }
    
    /**
     * 接続テスト
     * 
     * @return array テスト結果
     */
    public function testConnection() {
        try {
            $access_token = $this->getAccessToken();
            
            if (!$access_token) {
                return array(
                    'success' => false,
                    'message' => '認証に失敗しました',
                    'error' => $this->getLastError()
                );
            }
            
            // テスト読み取り
            $test_data = $this->readSheetData($this->sheet_name . '!A1:A1');
            
            if ($test_data !== false) {
                return array(
                    'success' => true,
                    'message' => 'Google Sheetsへの接続に成功しました',
                    'spreadsheet_id' => $this->spreadsheet_id,
                    'sheet_name' => $this->sheet_name
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'スプレッドシートの読み取りに失敗しました',
                    'error' => $this->getLastError()
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => '接続テストに失敗しました: ' . $e->getMessage()
            );
        }
    }
    
    // ========================================================================
    // 重複チェック機能
    // ========================================================================
    
    /**
     * 重複タイトルをチェック
     * 
     * @return array 重複情報
     */
    public function checkDuplicateTitles() {
        global $wpdb;
        
        $duplicates = $wpdb->get_results("
            SELECT post_title, COUNT(*) as count
            FROM {$wpdb->posts}
            WHERE post_type = 'grant'
            AND post_status IN ('publish', 'draft', 'private', 'pending')
            GROUP BY post_title
            HAVING count > 1
            ORDER BY count DESC, post_title ASC
        ");
        
        if (empty($duplicates)) {
            return array(
                'has_duplicates' => false,
                'count' => 0,
                'duplicates' => array()
            );
        }
        
        $duplicate_details = array();
        
        foreach ($duplicates as $dup) {
            $posts = get_posts(array(
                'post_type' => 'grant',
                'post_status' => array('publish', 'draft', 'private', 'pending'),
                'title' => $dup->post_title,
                'posts_per_page' => -1,
                'orderby' => 'ID',
                'order' => 'ASC'
            ));
            
            $post_details = array();
            foreach ($posts as $post) {
                $post_details[] = array(
                    'id' => $post->ID,
                    'status' => $post->post_status,
                    'date' => $post->post_date
                );
            }
            
            $duplicate_details[] = array(
                'title' => $dup->post_title,
                'count' => $dup->count,
                'posts' => $post_details
            );
        }
        
        return array(
            'has_duplicates' => true,
            'count' => count($duplicates),
            'duplicates' => $duplicate_details
        );
    }
    
    /**
     * 重複タイトルをシートにエクスポート
     * 
     * @return array エクスポート結果
     */
    public function exportDuplicateTitles() {
        $check_result = $this->checkDuplicateTitles();
        
        if (!$check_result['has_duplicates']) {
            return array(
                'success' => true,
                'message' => '重複タイトルはありません',
                'count' => 0
            );
        }
        
        $sheet_name = 'DuplicateTitles';
        
        // シートを作成（存在しない場合）
        if (!$this->sheetExists($sheet_name)) {
            $this->createSheet($sheet_name);
        } else {
            $this->clearSheetRange($sheet_name . '!A:K');
        }
        
        // ヘッダー
        $export_data = array(
            array('グループ', 'ID', 'タイトル', 'ステータス', '作成日', '都道府県', 'カテゴリ', '重複数', '対処方法')
        );
        
        $group_number = 1;
        
        foreach ($check_result['duplicates'] as $dup) {
            $is_first = true;
            
            foreach ($dup['posts'] as $post_detail) {
                $post = get_post($post_detail['id']);
                
                $prefectures = wp_get_post_terms($post_detail['id'], 'grant_prefecture', array('fields' => 'names'));
                $categories = wp_get_post_terms($post_detail['id'], 'grant_category', array('fields' => 'names'));
                
                $export_data[] = array(
                    'グループ' . $group_number,
                    $post_detail['id'],
                    $dup['title'],
                    $post_detail['status'],
                    $post_detail['date'],
                    is_array($prefectures) ? implode(', ', $prefectures) : '',
                    is_array($categories) ? implode(', ', $categories) : '',
                    $dup['count'],
                    $is_first ? '✅ 保持推奨' : '❌ 削除候補'
                );
                
                $is_first = false;
            }
            
            $export_data[] = array('', '', '', '', '', '', '', '', ''); // 空行
            $group_number++;
        }
        
        // 書き込み
        $range = $sheet_name . '!A1:I' . count($export_data);
        $result = $this->writeSheetData($range, $export_data, 'USER_ENTERED');
        
        if ($result) {
            return array(
                'success' => true,
                'message' => "{$check_result['count']} グループの重複をエクスポートしました",
                'count' => $check_result['count'],
                'sheet_name' => $sheet_name
            );
        } else {
            return array(
                'success' => false,
                'message' => 'エクスポートに失敗しました',
                'error' => $this->getLastError()
            );
        }
    }
    
    // ========================================================================
    // 都道府県バリデーション
    // ========================================================================
    
    /**
     * 都道府県データを検証
     * 
     * @return array 検証結果
     */
    public function validatePrefectures() {
        $valid_prefectures = array(
            '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
            '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
            '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県',
            '岐阜県', '静岡県', '愛知県', '三重県',
            '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県',
            '鳥取県', '島根県', '岡山県', '広島県', '山口県',
            '徳島県', '香川県', '愛媛県', '高知県',
            '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
        );
        
        $invalid_posts = array();
        $batch_size = 100;
        $page = 1;
        
        while (true) {
            $post_ids = get_posts(array(
                'post_type' => 'grant',
                'post_status' => array('publish', 'draft', 'private'),
                'posts_per_page' => $batch_size,
                'paged' => $page,
                'fields' => 'ids'
            ));
            
            if (empty($post_ids)) {
                break;
            }
            
            foreach ($post_ids as $post_id) {
                $prefectures = wp_get_post_terms($post_id, 'grant_prefecture', array('fields' => 'names'));
                $municipalities = wp_get_post_terms($post_id, 'grant_municipality', array('fields' => 'names'));
                
                $pref_names = is_array($prefectures) && !is_wp_error($prefectures) ? $prefectures : array();
                $muni_names = is_array($municipalities) && !is_wp_error($municipalities) ? $municipalities : array();
                
                $both_empty = empty($pref_names) && empty($muni_names);
                
                $invalid_values = array();
                foreach ($pref_names as $pref) {
                    if (!in_array($pref, $valid_prefectures)) {
                        $invalid_values[] = $pref;
                    }
                }
                
                if ($both_empty || !empty($invalid_values)) {
                    $post = get_post($post_id);
                    $invalid_posts[] = array(
                        'id' => $post_id,
                        'title' => $post->post_title,
                        'prefecture' => implode(', ', $pref_names),
                        'municipality' => implode(', ', $muni_names),
                        'issue' => $both_empty ? '空白' : '無効な値: ' . implode(', ', $invalid_values),
                        'status' => $post->post_status
                    );
                }
            }
            
            $page++;
        }
        
        return array(
            'has_issues' => !empty($invalid_posts),
            'count' => count($invalid_posts),
            'invalid_posts' => $invalid_posts
        );
    }
    
    /**
     * 無効な都道府県データをシートにエクスポート
     * 
     * @return array エクスポート結果
     */
    public function exportInvalidPrefectures() {
        $validation = $this->validatePrefectures();
        
        if (!$validation['has_issues']) {
            return array(
                'success' => true,
                'message' => '問題のある都道府県データはありません',
                'count' => 0
            );
        }
        
        $sheet_name = 'PrefectureValidation';
        
        if (!$this->sheetExists($sheet_name)) {
            $this->createSheet($sheet_name);
        } else {
            $this->clearSheetRange($sheet_name . '!A:G');
        }
        
        $export_data = array(
            array('ID', 'タイトル', '都道府県', '市町村', '問題点', 'ステータス', 'チェック日時')
        );
        
        $check_date = current_time('mysql');
        
        foreach ($validation['invalid_posts'] as $post) {
            $export_data[] = array(
                $post['id'],
                $post['title'],
                $post['prefecture'],
                $post['municipality'],
                $post['issue'],
                $post['status'],
                $check_date
            );
        }
        
        $range = $sheet_name . '!A1:G' . count($export_data);
        $result = $this->writeSheetData($range, $export_data, 'USER_ENTERED');
        
        if ($result) {
            return array(
                'success' => true,
                'message' => "{$validation['count']} 件の問題をエクスポートしました",
                'count' => $validation['count'],
                'sheet_name' => $sheet_name
            );
        } else {
            return array(
                'success' => false,
                'message' => 'エクスポートに失敗しました',
                'error' => $this->getLastError()
            );
        }
    }
    
    // ========================================================================
    // タクソノミー管理
    // ========================================================================
    
    /**
     * タクソノミーをエクスポート
     * 
     * @return array エクスポート結果
     */
    public function exportTaxonomies() {
        $taxonomies = array(
            array('taxonomy' => 'grant_category', 'sheet' => 'カテゴリ'),
            array('taxonomy' => 'grant_prefecture', 'sheet' => '都道府県_マスタ'),
            array('taxonomy' => 'grant_municipality', 'sheet' => '市町村_マスタ'),
            array('taxonomy' => 'grant_tag', 'sheet' => 'タグ'),
        );
        
        $results = array();
        
        foreach ($taxonomies as $config) {
            $terms = get_terms(array(
                'taxonomy' => $config['taxonomy'],
                'hide_empty' => false,
                'orderby' => 'term_id',
                'order' => 'ASC'
            ));
            
            if (is_wp_error($terms)) {
                $results[] = array(
                    'taxonomy' => $config['taxonomy'],
                    'success' => false,
                    'error' => $terms->get_error_message()
                );
                continue;
            }
            
            // シートを準備
            if (!$this->sheetExists($config['sheet'])) {
                $this->createSheet($config['sheet']);
            } else {
                $this->clearSheetRange($config['sheet'] . '!A:F');
            }
            
            $export_data = array(
                array('ID', '名前', 'スラッグ', '説明', '投稿数', 'エクスポート日時')
            );
            
            $export_date = current_time('mysql');
            
            foreach ($terms as $term) {
                $export_data[] = array(
                    $term->term_id,
                    $term->name,
                    $term->slug,
                    $term->description,
                    $term->count,
                    $export_date
                );
            }
            
            $range = $config['sheet'] . '!A1:F' . count($export_data);
            $write_result = $this->writeSheetData($range, $export_data, 'USER_ENTERED');
            
            $results[] = array(
                'taxonomy' => $config['taxonomy'],
                'sheet' => $config['sheet'],
                'success' => $write_result,
                'count' => count($terms)
            );
        }
        
        return array(
            'success' => true,
            'results' => $results
        );
    }
    
    /**
     * タクソノミーをインポート
     * 
     * @return array インポート結果
     */
    public function importTaxonomies() {
        $taxonomies = array(
            array('taxonomy' => 'grant_category', 'sheet' => 'カテゴリ'),
            array('taxonomy' => 'grant_prefecture', 'sheet' => '都道府県_マスタ'),
            array('taxonomy' => 'grant_municipality', 'sheet' => '市町村_マスタ'),
            array('taxonomy' => 'grant_tag', 'sheet' => 'タグ'),
        );
        
        $results = array();
        
        foreach ($taxonomies as $config) {
            if (!$this->sheetExists($config['sheet'])) {
                $results[] = array(
                    'taxonomy' => $config['taxonomy'],
                    'success' => false,
                    'error' => 'シートが存在しません'
                );
                continue;
            }
            
            $sheet_data = $this->readSheetData($config['sheet'] . '!A1:F10000');
            
            if (empty($sheet_data) || count($sheet_data) < 2) {
                $results[] = array(
                    'taxonomy' => $config['taxonomy'],
                    'success' => false,
                    'error' => 'データがありません'
                );
                continue;
            }
            
            // ヘッダーを除去
            array_shift($sheet_data);
            
            $stats = array('created' => 0, 'updated' => 0, 'deleted' => 0, 'skipped' => 0);
            
            foreach ($sheet_data as $row) {
                if (empty($row) || empty($row[0])) {
                    continue;
                }
                
                $term_id = intval($row[0]);
                $name = isset($row[1]) ? trim($row[1]) : '';
                $slug = isset($row[2]) ? sanitize_title($row[2]) : '';
                $description = isset($row[3]) ? trim($row[3]) : '';
                
                if (empty($name)) {
                    $stats['skipped']++;
                    continue;
                }
                
                // 削除フラグ
                if ($name === 'DELETE' || $name === '削除') {
                    if ($term_id > 0) {
                        wp_delete_term($term_id, $config['taxonomy']);
                        $stats['deleted']++;
                    }
                    continue;
                }
                
                $term_args = array(
                    'description' => $description,
                    'slug' => $slug
                );
                
                if ($term_id > 0 && term_exists($term_id, $config['taxonomy'])) {
                    wp_update_term($term_id, $config['taxonomy'], array_merge($term_args, array('name' => $name)));
                    $stats['updated']++;
                } else {
                    $result = wp_insert_term($name, $config['taxonomy'], $term_args);
                    if (!is_wp_error($result)) {
                        $stats['created']++;
                    } else {
                        $stats['skipped']++;
                    }
                }
            }
            
            $results[] = array(
                'taxonomy' => $config['taxonomy'],
                'sheet' => $config['sheet'],
                'success' => true,
                'stats' => $stats
            );
        }
        
        return array(
            'success' => true,
            'results' => $results
        );
    }
    
    // ========================================================================
    // ゲッター
    // ========================================================================
    
    /**
     * スプレッドシートIDを取得
     * 
     * @return string
     */
    public function getSpreadsheetId() {
        return $this->spreadsheet_id;
    }
    
    /**
     * シート名を取得
     * 
     * @return string
     */
    public function getSheetName() {
        return $this->sheet_name;
    }
    
    // ========================================================================
    // AJAXハンドラー
    // ========================================================================
    
    /**
     * 同期AJAXハンドラー
     */
    public function ajaxSync() {
        check_ajax_referer('gi_sheets_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => '権限がありません'));
        }
        
        $direction = isset($_POST['direction']) ? sanitize_text_field($_POST['direction']) : 'sheets_to_wp';
        
        if ($direction === 'sheets_to_wp') {
            $result = $this->syncFromSheets();
        } else {
            wp_send_json_error(array('message' => '無効な同期方向です'));
            return;
        }
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * 接続テストAJAXハンドラー
     */
    public function ajaxTestConnection() {
        check_ajax_referer('gi_sheets_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => '権限がありません'));
        }
        
        $result = $this->testConnection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * 初期化AJAXハンドラー
     */
    public function ajaxInitialize() {
        check_ajax_referer('gi_sheets_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => '権限がありません'));
        }
        
        try {
            $this->clearSheetRange('A:AE');
            $this->setupSheetHeaders();
            
            wp_send_json_success(array('message' => 'スプレッドシートを初期化しました'));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => '初期化に失敗しました: ' . $e->getMessage()));
        }
    }
    
    /**
     * 全件エクスポートAJAXハンドラー
     */
    public function ajaxExportAll() {
        check_ajax_referer('gi_sheets_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => '権限がありません'));
        }
        
        $result = $this->exportAllPosts();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * プログレス取得AJAXハンドラー
     */
    public function ajaxGetProgress() {
        check_ajax_referer('gi_sheets_nonce', 'nonce');
        
        wp_send_json_success($this->getSyncProgress());
    }
    
    /**
     * 同期キャンセルAJAXハンドラー
     */
    public function ajaxCancelSync() {
        check_ajax_referer('gi_sheets_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => '権限がありません'));
        }
        
        $this->cancelSync();
        wp_send_json_success(array('message' => '同期をキャンセルしました'));
    }
    
    /**
     * データクリアAJAXハンドラー
     */
    public function ajaxClearData() {
        check_ajax_referer('gi_sheets_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => '権限がありません'));
        }
        
        $result = $this->clearSheetRange('A2:AE10000');
        
        if ($result) {
            wp_send_json_success(array('message' => 'データをクリアしました'));
        } else {
            wp_send_json_error(array('message' => 'クリアに失敗しました'));
        }
    }
    
    /**
     * 重複チェックAJAXハンドラー
     */
    public function ajaxCheckDuplicates() {
        check_ajax_referer('gi_sheets_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => '権限がありません'));
        }
        
        $result = $this->checkDuplicateTitles();
        wp_send_json_success($result);
    }
    
    /**
     * 重複エクスポートAJAXハンドラー
     */
    public function ajaxExportDuplicates() {
        check_ajax_referer('gi_sheets_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => '権限がありません'));
        }
        
        $result = $this->exportDuplicateTitles();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * 都道府県バリデーションAJAXハンドラー
     */
    public function ajaxValidatePrefectures() {
        check_ajax_referer('gi_sheets_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => '権限がありません'));
        }
        
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'check';
        
        if ($action === 'export') {
            $result = $this->exportInvalidPrefectures();
        } else {
            $result = $this->validatePrefectures();
            $result['success'] = true;
        }
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * タクソノミーエクスポートAJAXハンドラー
     */
    public function ajaxExportTaxonomies() {
        check_ajax_referer('gi_sheets_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '権限がありません'));
        }
        
        $result = $this->exportTaxonomies();
        wp_send_json_success($result);
    }
    
    /**
     * タクソノミーインポートAJAXハンドラー
     */
    public function ajaxImportTaxonomies() {
        check_ajax_referer('gi_sheets_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '権限がありません'));
        }
        
        $result = $this->importTaxonomies();
        wp_send_json_success($result);
    }
    
    /**
     * 投稿保存時のフック
     * 
     * @param int $post_id 投稿ID
     * @param WP_Post $post 投稿オブジェクト
     * @param bool $update 更新かどうか
     */
    public function onPostSave($post_id, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // 単一投稿をシートに同期（オプション機能）
        // $this->syncSinglePostToSheet($post_id);
    }
    
    /**
     * スケジュール同期
     */
    public function scheduledSync() {
        gi_log_info('Starting scheduled sync');
        
        try {
            $result = $this->syncFromSheets();
            gi_log_info('Scheduled sync completed', $result);
        } catch (Exception $e) {
            gi_log_error('Scheduled sync failed', array('error' => $e->getMessage()));
        }
    }
}

// ============================================================================
// 管理画面クラス
// ============================================================================

/**
 * Google Sheets 管理画面クラス
 * 
 * シンプルで使いやすいUIを提供
 */
class GoogleSheetsAdmin {
    
    /** @var GoogleSheetsAdmin|null */
    private static $instance = null;
    
    /** @var GoogleSheetsSync */
    private $sync;
    
    /**
     * シングルトンインスタンス取得
     * 
     * @return GoogleSheetsAdmin
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * コンストラクタ
     */
    private function __construct() {
        $this->sync = GoogleSheetsSync::getInstance();
        
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
    }
    
    /**
     * 管理メニューの追加
     */
    public function addAdminMenu() {
        add_menu_page(
            'Sheets連携',
            'Sheets連携',
            'edit_posts',
            'gi-sheets-sync',
            array($this, 'renderAdminPage'),
            'dashicons-media-spreadsheet',
            30
        );
    }
    
    /**
     * スクリプト・スタイルの読み込み
     * 
     * @param string $hook 現在のページフック
     */
    public function enqueueScripts($hook) {
        if (strpos($hook, 'gi-sheets-sync') === false) {
            return;
        }
        
        // インラインスタイル
        wp_add_inline_style('wp-admin', $this->getAdminStyles());
        
        // インラインスクリプト
        wp_add_inline_script('jquery', $this->getAdminScript());
        
        // Nonce を出力
        wp_localize_script('jquery', 'giSheets', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gi_sheets_nonce'),
            'spreadsheetUrl' => 'https://docs.google.com/spreadsheets/d/' . $this->sync->getSpreadsheetId()
        ));
    }
    
    /**
     * 管理画面のスタイル
     * 
     * @return string CSS
     */
    private function getAdminStyles() {
        return '
        .gi-admin-wrap {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .gi-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .gi-header h1 {
            margin: 0 0 10px;
            font-size: 28px;
            color: white;
        }
        .gi-header p {
            margin: 0;
            opacity: 0.9;
        }
        .gi-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .gi-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
        }
        .gi-card h2 {
            margin: 0 0 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .gi-card h2 .dashicons {
            color: #667eea;
        }
        .gi-status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .gi-status.success {
            background: #d4edda;
            color: #155724;
        }
        .gi-status.error {
            background: #f8d7da;
            color: #721c24;
        }
        .gi-status.loading {
            background: #fff3cd;
            color: #856404;
        }
        .gi-status.idle {
            background: #e2e3e5;
            color: #383d41;
        }
        .gi-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }
        .gi-btn-primary {
            background: #667eea;
            color: white;
        }
        .gi-btn-primary:hover {
            background: #5a6fd6;
            color: white;
        }
        .gi-btn-secondary {
            background: #6c757d;
            color: white;
        }
        .gi-btn-secondary:hover {
            background: #5a6268;
            color: white;
        }
        .gi-btn-success {
            background: #28a745;
            color: white;
        }
        .gi-btn-success:hover {
            background: #218838;
            color: white;
        }
        .gi-btn-danger {
            background: #dc3545;
            color: white;
        }
        .gi-btn-danger:hover {
            background: #c82333;
            color: white;
        }
        .gi-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .gi-btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .gi-progress {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 15px 0;
        }
        .gi-progress-bar {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        .gi-info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 0 8px 8px 0;
            margin: 15px 0;
        }
        .gi-info-box code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
        .gi-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .gi-stat {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .gi-stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .gi-stat-label {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .gi-log {
            max-height: 300px;
            overflow-y: auto;
            background: #1e1e1e;
            border-radius: 8px;
            padding: 15px;
            font-family: monospace;
            font-size: 12px;
        }
        .gi-log-entry {
            padding: 5px 0;
            border-bottom: 1px solid #333;
            color: #ccc;
        }
        .gi-log-entry.success { color: #28a745; }
        .gi-log-entry.error { color: #dc3545; }
        .gi-log-entry.warning { color: #ffc107; }
        .gi-log-time {
            color: #888;
            margin-right: 10px;
        }
        .gi-full-width {
            grid-column: 1 / -1;
        }
        @media (max-width: 782px) {
            .gi-cards {
                grid-template-columns: 1fr;
            }
        }
        ';
    }
    
    /**
     * 管理画面のスクリプト
     * 
     * @return string JavaScript
     */
    private function getAdminScript() {
        return "
        jQuery(document).ready(function($) {
            var progressInterval = null;
            
            // 接続テスト
            $('#gi-test-connection').on('click', function() {
                var btn = $(this);
                var status = $('#gi-connection-status');
                
                btn.prop('disabled', true);
                status.removeClass('success error idle').addClass('loading').html('<span class=\"dashicons dashicons-update spin\"></span> 接続中...');
                
                $.post(giSheets.ajaxUrl, {
                    action: 'gi_sheets_test_connection',
                    nonce: giSheets.nonce
                }, function(response) {
                    btn.prop('disabled', false);
                    status.removeClass('loading');
                    
                    if (response.success) {
                        status.addClass('success').html('<span class=\"dashicons dashicons-yes-alt\"></span> ' + response.data.message);
                    } else {
                        status.addClass('error').html('<span class=\"dashicons dashicons-warning\"></span> ' + (response.data.message || 'エラー'));
                    }
                }).fail(function() {
                    btn.prop('disabled', false);
                    status.removeClass('loading').addClass('error').html('<span class=\"dashicons dashicons-warning\"></span> 通信エラー');
                });
            });
            
            // 同期実行
            $('#gi-sync-sheets').on('click', function() {
                if (!confirm('スプレッドシートからWordPressに同期します。続行しますか？')) {
                    return;
                }
                
                var btn = $(this);
                btn.prop('disabled', true);
                
                $('#gi-sync-status').removeClass('success error idle').addClass('loading').html('<span class=\"dashicons dashicons-update spin\"></span> 同期中...');
                $('#gi-progress-container').show();
                
                startProgressMonitor();
                
                $.post(giSheets.ajaxUrl, {
                    action: 'gi_sheets_sync',
                    nonce: giSheets.nonce,
                    direction: 'sheets_to_wp'
                }, function(response) {
                    stopProgressMonitor();
                    btn.prop('disabled', false);
                    $('#gi-sync-status').removeClass('loading');
                    
                    if (response.success) {
                        $('#gi-sync-status').addClass('success').html('<span class=\"dashicons dashicons-yes-alt\"></span> ' + response.data.message);
                        updateStats(response.data.stats);
                    } else {
                        $('#gi-sync-status').addClass('error').html('<span class=\"dashicons dashicons-warning\"></span> ' + (response.data.message || response.data.error || 'エラー'));
                    }
                }).fail(function() {
                    stopProgressMonitor();
                    btn.prop('disabled', false);
                    $('#gi-sync-status').removeClass('loading').addClass('error').html('<span class=\"dashicons dashicons-warning\"></span> 通信エラー');
                });
            });
            
            // 全件エクスポート
            $('#gi-export-all').on('click', function() {
                if (!confirm('全投稿をスプレッドシートにエクスポートします。既存データは上書きされます。続行しますか？')) {
                    return;
                }
                
                var btn = $(this);
                btn.prop('disabled', true);
                
                $('#gi-sync-status').removeClass('success error idle').addClass('loading').html('<span class=\"dashicons dashicons-update spin\"></span> エクスポート中...');
                $('#gi-progress-container').show();
                
                startProgressMonitor();
                
                $.post(giSheets.ajaxUrl, {
                    action: 'gi_sheets_export_all',
                    nonce: giSheets.nonce
                }, function(response) {
                    stopProgressMonitor();
                    btn.prop('disabled', false);
                    $('#gi-sync-status').removeClass('loading');
                    
                    if (response.success) {
                        $('#gi-sync-status').addClass('success').html('<span class=\"dashicons dashicons-yes-alt\"></span> ' + response.data.message);
                    } else {
                        $('#gi-sync-status').addClass('error').html('<span class=\"dashicons dashicons-warning\"></span> ' + (response.data.message || 'エラー'));
                    }
                }).fail(function() {
                    stopProgressMonitor();
                    btn.prop('disabled', false);
                    $('#gi-sync-status').removeClass('loading').addClass('error').html('<span class=\"dashicons dashicons-warning\"></span> 通信エラー');
                });
            });
            
            // 初期化
            $('#gi-initialize').on('click', function() {
                if (!confirm('スプレッドシートを初期化します。既存データは全て削除されます。続行しますか？')) {
                    return;
                }
                
                var btn = $(this);
                btn.prop('disabled', true);
                
                $.post(giSheets.ajaxUrl, {
                    action: 'gi_sheets_initialize',
                    nonce: giSheets.nonce
                }, function(response) {
                    btn.prop('disabled', false);
                    alert(response.success ? response.data.message : (response.data.message || 'エラー'));
                }).fail(function() {
                    btn.prop('disabled', false);
                    alert('通信エラー');
                });
            });
            
            // 重複チェック
            $('#gi-check-duplicates').on('click', function() {
                var btn = $(this);
                btn.prop('disabled', true);
                
                $.post(giSheets.ajaxUrl, {
                    action: 'gi_sheets_check_duplicates',
                    nonce: giSheets.nonce
                }, function(response) {
                    btn.prop('disabled', false);
                    
                    if (response.success) {
                        var data = response.data;
                        if (data.has_duplicates) {
                            alert('重複タイトル: ' + data.count + ' グループ見つかりました');
                        } else {
                            alert('重複タイトルはありません');
                        }
                    }
                }).fail(function() {
                    btn.prop('disabled', false);
                    alert('通信エラー');
                });
            });
            
            // 重複エクスポート
            $('#gi-export-duplicates').on('click', function() {
                var btn = $(this);
                btn.prop('disabled', true);
                
                $.post(giSheets.ajaxUrl, {
                    action: 'gi_sheets_export_duplicates',
                    nonce: giSheets.nonce
                }, function(response) {
                    btn.prop('disabled', false);
                    alert(response.success ? response.data.message : (response.data.message || 'エラー'));
                }).fail(function() {
                    btn.prop('disabled', false);
                    alert('通信エラー');
                });
            });
            
            // 都道府県バリデーション
            $('#gi-validate-prefectures').on('click', function() {
                var btn = $(this);
                btn.prop('disabled', true);
                
                $.post(giSheets.ajaxUrl, {
                    action: 'gi_sheets_validate_prefectures',
                    nonce: giSheets.nonce,
                    action_type: 'export'
                }, function(response) {
                    btn.prop('disabled', false);
                    alert(response.success ? response.data.message : (response.data.message || 'エラー'));
                }).fail(function() {
                    btn.prop('disabled', false);
                    alert('通信エラー');
                });
            });
            
            // タクソノミーエクスポート
            $('#gi-export-taxonomies').on('click', function() {
                var btn = $(this);
                btn.prop('disabled', true);
                
                $.post(giSheets.ajaxUrl, {
                    action: 'gi_sheets_export_taxonomies',
                    nonce: giSheets.nonce
                }, function(response) {
                    btn.prop('disabled', false);
                    if (response.success) {
                        var msg = 'タクソノミーをエクスポートしました:\\n';
                        response.data.results.forEach(function(r) {
                            msg += r.taxonomy + ': ' + r.count + '件\\n';
                        });
                        alert(msg);
                    } else {
                        alert('エラー');
                    }
                }).fail(function() {
                    btn.prop('disabled', false);
                    alert('通信エラー');
                });
            });
            
            // タクソノミーインポート
            $('#gi-import-taxonomies').on('click', function() {
                if (!confirm('スプレッドシートからタクソノミーをインポートします。続行しますか？')) {
                    return;
                }
                
                var btn = $(this);
                btn.prop('disabled', true);
                
                $.post(giSheets.ajaxUrl, {
                    action: 'gi_sheets_import_taxonomies',
                    nonce: giSheets.nonce
                }, function(response) {
                    btn.prop('disabled', false);
                    if (response.success) {
                        var msg = 'インポート完了:\\n';
                        response.data.results.forEach(function(r) {
                            if (r.stats) {
                                msg += r.taxonomy + ': 作成' + r.stats.created + ' 更新' + r.stats.updated + ' 削除' + r.stats.deleted + '\\n';
                            }
                        });
                        alert(msg);
                    } else {
                        alert('エラー');
                    }
                }).fail(function() {
                    btn.prop('disabled', false);
                    alert('通信エラー');
                });
            });
            
            // プログレス監視開始
            function startProgressMonitor() {
                progressInterval = setInterval(function() {
                    $.post(giSheets.ajaxUrl, {
                        action: 'gi_sheets_get_progress',
                        nonce: giSheets.nonce
                    }, function(response) {
                        if (response.success) {
                            var progress = response.data;
                            var percentage = progress.percentage || 0;
                            
                            $('#gi-progress-bar').css('width', percentage + '%').text(percentage + '%');
                            $('#gi-progress-text').text(progress.processed + ' / ' + progress.total);
                            
                            if (progress.status === 'completed' || progress.status === 'error') {
                                stopProgressMonitor();
                            }
                        }
                    });
                }, 2000);
            }
            
            // プログレス監視停止
            function stopProgressMonitor() {
                if (progressInterval) {
                    clearInterval(progressInterval);
                    progressInterval = null;
                }
            }
            
            // 統計更新
            function updateStats(stats) {
                if (stats) {
                    $('#stat-created').text(stats.created || 0);
                    $('#stat-updated').text(stats.updated || 0);
                    $('#stat-deleted').text(stats.deleted || 0);
                    $('#stat-errors').text(stats.errors || 0);
                }
            }
            
            // スピンアニメーション
            $('<style>.spin { animation: spin 1s linear infinite; } @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>').appendTo('head');
        });
        ";
    }
    
    /**
     * 管理画面のレンダリング
     */
    public function renderAdminPage() {
        $spreadsheet_id = $this->sync->getSpreadsheetId();
        $sheet_name = $this->sync->getSheetName();
        $last_sync = get_option('gi_sheets_last_sync', '未実行');
        
        // 投稿統計
        $post_counts = wp_count_posts('grant');
        $total_posts = $post_counts->publish + $post_counts->draft + $post_counts->private;
        
        ?>
        <div class="gi-admin-wrap">
            <!-- ヘッダー -->
            <div class="gi-header">
                <h1><span class="dashicons dashicons-media-spreadsheet"></span> Google Sheets連携</h1>
                <p>スプレッドシートとWordPressの助成金データを同期します（大規模データ対応）</p>
            </div>
            
            <!-- カード群 -->
            <div class="gi-cards">
                <!-- 接続状態 -->
                <div class="gi-card">
                    <h2><span class="dashicons dashicons-cloud"></span> 接続状態</h2>
                    
                    <div id="gi-connection-status" class="gi-status idle">
                        <span class="dashicons dashicons-minus"></span> 未確認
                    </div>
                    
                    <div class="gi-info-box">
                        <p><strong>スプレッドシートID:</strong><br><code><?php echo esc_html($spreadsheet_id); ?></code></p>
                        <p><strong>シート名:</strong> <code><?php echo esc_html($sheet_name); ?></code></p>
                        <p><strong>最終同期:</strong> <?php echo esc_html($last_sync); ?></p>
                    </div>
                    
                    <div class="gi-btn-group">
                        <button id="gi-test-connection" class="gi-btn gi-btn-secondary">
                            <span class="dashicons dashicons-yes"></span> 接続テスト
                        </button>
                        <a href="https://docs.google.com/spreadsheets/d/<?php echo esc_attr($spreadsheet_id); ?>" target="_blank" class="gi-btn gi-btn-secondary">
                            <span class="dashicons dashicons-external"></span> シートを開く
                        </a>
                    </div>
                </div>
                
                <!-- 同期操作 -->
                <div class="gi-card">
                    <h2><span class="dashicons dashicons-update"></span> 同期操作</h2>
                    
                    <div id="gi-sync-status" class="gi-status idle">
                        <span class="dashicons dashicons-minus"></span> 待機中
                    </div>
                    
                    <div id="gi-progress-container" style="display: none;">
                        <div class="gi-progress">
                            <div id="gi-progress-bar" class="gi-progress-bar" style="width: 0%;">0%</div>
                        </div>
                        <p id="gi-progress-text" style="text-align: center; color: #666;">0 / 0</p>
                    </div>
                    
                    <div class="gi-stats">
                        <div class="gi-stat">
                            <div class="gi-stat-value" id="stat-created">0</div>
                            <div class="gi-stat-label">作成</div>
                        </div>
                        <div class="gi-stat">
                            <div class="gi-stat-value" id="stat-updated">0</div>
                            <div class="gi-stat-label">更新</div>
                        </div>
                        <div class="gi-stat">
                            <div class="gi-stat-value" id="stat-deleted">0</div>
                            <div class="gi-stat-label">削除</div>
                        </div>
                        <div class="gi-stat">
                            <div class="gi-stat-value" id="stat-errors">0</div>
                            <div class="gi-stat-label">エラー</div>
                        </div>
                    </div>
                    
                    <div class="gi-btn-group">
                        <button id="gi-sync-sheets" class="gi-btn gi-btn-primary">
                            <span class="dashicons dashicons-download"></span> Sheets → WordPress
                        </button>
                        <button id="gi-export-all" class="gi-btn gi-btn-success">
                            <span class="dashicons dashicons-upload"></span> WordPress → Sheets
                        </button>
                    </div>
                </div>
                
                <!-- データ管理 -->
                <div class="gi-card">
                    <h2><span class="dashicons dashicons-database"></span> データ管理</h2>
                    
                    <div class="gi-info-box">
                        <p><strong>WordPress投稿数:</strong> <?php echo number_format($total_posts); ?> 件</p>
                        <p>（公開: <?php echo number_format($post_counts->publish); ?> / 下書き: <?php echo number_format($post_counts->draft); ?> / 非公開: <?php echo number_format($post_counts->private); ?>）</p>
                    </div>
                    
                    <div class="gi-btn-group">
                        <button id="gi-check-duplicates" class="gi-btn gi-btn-secondary">
                            <span class="dashicons dashicons-search"></span> 重複チェック
                        </button>
                        <button id="gi-export-duplicates" class="gi-btn gi-btn-secondary">
                            <span class="dashicons dashicons-media-spreadsheet"></span> 重複エクスポート
                        </button>
                    </div>
                    
                    <div class="gi-btn-group">
                        <button id="gi-validate-prefectures" class="gi-btn gi-btn-secondary">
                            <span class="dashicons dashicons-location"></span> 都道府県検証
                        </button>
                    </div>
                </div>
                
                <!-- タクソノミー管理 -->
                <div class="gi-card">
                    <h2><span class="dashicons dashicons-category"></span> タクソノミー管理</h2>
                    
                    <p style="color: #666;">カテゴリ、都道府県、市町村、タグのマスタデータを管理します。</p>
                    
                    <div class="gi-btn-group">
                        <button id="gi-export-taxonomies" class="gi-btn gi-btn-secondary">
                            <span class="dashicons dashicons-upload"></span> タクソノミーエクスポート
                        </button>
                        <button id="gi-import-taxonomies" class="gi-btn gi-btn-primary">
                            <span class="dashicons dashicons-download"></span> タクソノミーインポート
                        </button>
                    </div>
                </div>
                                    <div class="gi-info-box" style="margin-top: 20px;">
                        <h4 style="margin-top: 0;">⚠️ 注意事項</h4>
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li>「シート初期化」は既存データを全て削除します</li>
                            <li>大量データの同期には数分かかる場合があります</li>
                            <li>同期中はブラウザを閉じないでください</li>
                        </ul>
                    </div>
                </div>
                
                <!-- 使い方ガイド（フル幅） -->
                <div class="gi-card gi-full-width">
                    <h2><span class="dashicons dashicons-book"></span> 使い方ガイド</h2>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div>
                            <h4 style="color: #667eea;">📥 スプレッドシート → WordPress</h4>
                            <ol style="padding-left: 20px; line-height: 1.8;">
                                <li>スプレッドシートでデータを編集</li>
                                <li>「Sheets → WordPress」ボタンをクリック</li>
                                <li>新規行は自動的に投稿が作成されます</li>
                                <li>既存IDの行は投稿が更新されます</li>
                                <li>ステータスを「deleted」にすると削除されます</li>
                            </ol>
                        </div>
                        
                        <div>
                            <h4 style="color: #28a745;">📤 WordPress → スプレッドシート</h4>
                            <ol style="padding-left: 20px; line-height: 1.8;">
                                <li>「WordPress → Sheets」ボタンをクリック</li>
                                <li>全ての助成金データがエクスポートされます</li>
                                <li>既存のシートデータは上書きされます</li>
                                <li>バックアップを取ってから実行してください</li>
                            </ol>
                        </div>
                        
                        <div>
                            <h4 style="color: #dc3545;">⚠️ 重複タイトルの処理</h4>
                            <ol style="padding-left: 20px; line-height: 1.8;">
                                <li>「重複チェック」で重複を確認</li>
                                <li>「重複エクスポート」でシートに出力</li>
                                <li>シートで保持する投稿を選択</li>
                                <li>削除する投稿のステータスを「deleted」に変更</li>
                                <li>同期を実行して削除を反映</li>
                            </ol>
                        </div>
                        
                        <div>
                            <h4 style="color: #6c757d;">📋 列構成（31列）</h4>
                            <div style="font-size: 12px; line-height: 1.6; background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto;">
                                <strong>A:</strong> ID（自動）<br>
                                <strong>B:</strong> タイトル<br>
                                <strong>C:</strong> 内容<br>
                                <strong>D:</strong> 抜粋<br>
                                <strong>E:</strong> ステータス（publish/draft/private/deleted）<br>
                                <strong>F:</strong> 作成日（自動）<br>
                                <strong>G:</strong> 更新日（自動）<br>
                                <strong>H:</strong> 助成金額<br>
                                <strong>I:</strong> 助成金額（数値）<br>
                                <strong>J:</strong> 申請期限<br>
                                <strong>K:</strong> 申請期限（日付）<br>
                                <strong>L:</strong> 実施組織<br>
                                <strong>M:</strong> 組織タイプ<br>
                                <strong>N:</strong> 対象者・対象事業<br>
                                <strong>O:</strong> 申請方法<br>
                                <strong>P:</strong> 問い合わせ先<br>
                                <strong>Q:</strong> 公式URL<br>
                                <strong>R:</strong> 地域制限<br>
                                <strong>S:</strong> 申請ステータス<br>
                                <strong>T:</strong> 都道府県<br>
                                <strong>U:</strong> 市町村<br>
                                <strong>V:</strong> カテゴリ<br>
                                <strong>W:</strong> タグ<br>
                                <strong>X:</strong> 外部リンク<br>
                                <strong>Y:</strong> 地域備考<br>
                                <strong>Z:</strong> 必要書類<br>
                                <strong>AA:</strong> 採択率<br>
                                <strong>AB:</strong> 申請難易度<br>
                                <strong>AC:</strong> 対象経費<br>
                                <strong>AD:</strong> 補助率<br>
                                <strong>AE:</strong> シート更新日（自動）
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 同期ログ（フル幅） -->
                <div class="gi-card gi-full-width">
                    <h2><span class="dashicons dashicons-list-view"></span> 同期ログ</h2>
                    
                    <div class="gi-log">
                        <?php
                        $logs = get_option('gi_sheets_sync_log', array());
                        $logs = array_slice($logs, -20); // 最新20件
                        $logs = array_reverse($logs);
                        
                        if (empty($logs)) {
                            echo '<div class="gi-log-entry">ログはまだありません</div>';
                        } else {
                            foreach ($logs as $log) {
                                $time = isset($log['timestamp']) ? date('Y-m-d H:i:s', $log['timestamp']) : '';
                                $message = isset($log['message']) ? esc_html($log['message']) : '';
                                $type = isset($log['type']) ? $log['type'] : 'info';
                                
                                $class = '';
                                if (strpos($message, 'エラー') !== false || strpos($message, 'error') !== false) {
                                    $class = 'error';
                                } elseif (strpos($message, '完了') !== false || strpos($message, 'success') !== false) {
                                    $class = 'success';
                                }
                                
                                echo '<div class="gi-log-entry ' . $class . '">';
                                echo '<span class="gi-log-time">' . esc_html($time) . '</span>';
                                echo '<span>' . $message . '</span>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- フッター情報 -->
            <div style="text-align: center; color: #999; padding: 20px;">
                <p>Google Sheets Integration v<?php echo GI_SHEETS_VERSION; ?> | バッチサイズ: <?php echo GI_SHEETS_BATCH_SIZE; ?>件 | メモリ上限: <?php echo GI_SHEETS_MEMORY_LIMIT; ?></p>
            </div>
        </div>
        <?php
    }
}

// ============================================================================
// ヘルパー関数
// ============================================================================

/**
 * タクソノミータームを自動作成して設定するヘルパー関数
 * 
 * @param int $post_id 投稿ID
 * @param array|string $terms タームの配列または文字列
 * @param string $taxonomy タクソノミー名
 * @return array|WP_Error 設定されたタームIDの配列、またはエラー
 */
if (!function_exists('gi_set_terms_with_auto_create')) {
    function gi_set_terms_with_auto_create($post_id, $terms, $taxonomy) {
        if (empty($terms)) {
            return wp_set_post_terms($post_id, array(), $taxonomy);
        }
        
        if (!is_array($terms)) {
            $terms = array($terms);
        }
        
        $term_ids = array();
        
        foreach ($terms as $term_name) {
            $term_name = trim($term_name);
            if (empty($term_name)) {
                continue;
            }
            
            $existing_term = term_exists($term_name, $taxonomy);
            
            if ($existing_term) {
                $term_ids[] = (int) $existing_term['term_id'];
            } else {
                $new_term = wp_insert_term($term_name, $taxonomy);
                if (!is_wp_error($new_term)) {
                    $term_ids[] = (int) $new_term['term_id'];
                    gi_log_debug('New term created', array(
                        'taxonomy' => $taxonomy,
                        'term' => $term_name,
                        'term_id' => $new_term['term_id']
                    ));
                }
            }
        }
        
        return wp_set_post_terms($post_id, $term_ids, $taxonomy);
    }
}

// ============================================================================
// Webhook ハンドラークラス
// ============================================================================

/**
 * Google Apps Script からのWebhookを処理するクラス
 */
class GoogleSheetsWebhook {
    
    /** @var GoogleSheetsWebhook|null */
    private static $instance = null;
    
    /** @var string Webhook シークレット */
    private $secret;
    
    /**
     * シングルトンインスタンス取得
     * 
     * @return GoogleSheetsWebhook
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * コンストラクタ
     */
    private function __construct() {
        $this->secret = $this->getOrGenerateSecret();
        
        add_action('rest_api_init', array($this, 'registerEndpoints'));
        add_action('init', array($this, 'handleLegacyWebhook'));
    }
    
    /**
     * シークレットの取得または生成
     * 
     * @return string
     */
    private function getOrGenerateSecret() {
        $secret = get_option('gi_sheets_webhook_secret');
        
        if (!$secret) {
            $secret = wp_generate_password(32, false);
            update_option('gi_sheets_webhook_secret', $secret);
        }
        
        return $secret;
    }
    
    /**
     * REST APIエンドポイントの登録
     */
    public function registerEndpoints() {
        register_rest_route('gi/v1', '/sheets-webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handleWebhook'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route('gi/v1', '/export-grants', array(
            'methods' => 'GET',
            'callback' => array($this, 'handleExport'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * レガシーWebhookハンドラー
     */
    public function handleLegacyWebhook() {
        if (!isset($_GET['gi_sheets_webhook']) || $_GET['gi_sheets_webhook'] !== 'true') {
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            wp_die('Method Not Allowed', '', array('response' => 405));
        }
        
        $raw_data = file_get_contents('php://input');
        $data = json_decode($raw_data, true);
        
        if (!$data) {
            http_response_code(400);
            wp_die('Invalid JSON', '', array('response' => 400));
        }
        
        if (!$this->verifySignature($data)) {
            http_response_code(403);
            wp_die('Forbidden', '', array('response' => 403));
        }
        
        $result = $this->processWebhook($data);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Webhookリクエストの処理
     * 
     * @param WP_REST_Request $request リクエスト
     * @return WP_REST_Response レスポンス
     */
    public function handleWebhook($request) {
        $data = $request->get_json_params();
        
        if (!$data) {
            return new WP_Error('invalid_json', 'Invalid JSON data', array('status' => 400));
        }
        
        if (!$this->verifySignature($data)) {
            return new WP_Error('forbidden', 'Signature verification failed', array('status' => 403));
        }
        
        $result = $this->processWebhook($data);
        
        return rest_ensure_response($result);
    }
    
    /**
     * 署名の検証
     * 
     * @param array $data リクエストデータ
     * @return bool 検証結果
     */
    private function verifySignature($data) {
        if (!isset($data['timestamp']) || !isset($data['signature']) || !isset($data['payload'])) {
            return false;
        }
        
        // タイムスタンプ検証（5分以内）
        $current_time = time();
        $request_time = intval($data['timestamp']);
        
        if (abs($current_time - $request_time) > 300) {
            return false;
        }
        
        // 署名検証
        $payload_string = json_encode($data['payload']);
        $expected_signature = hash_hmac('sha256', $request_time . $payload_string, $this->secret);
        
        return hash_equals($expected_signature, $data['signature']);
    }
    
    /**
     * Webhookデータの処理
     * 
     * @param array $data リクエストデータ
     * @return array 処理結果
     */
    private function processWebhook($data) {
        try {
            $payload = $data['payload'];
            $action = isset($payload['action']) ? $payload['action'] : '';
            
            switch ($action) {
                case 'row_updated':
                    return $this->handleRowUpdate($payload);
                    
                case 'row_added':
                    return $this->handleRowAdd($payload);
                    
                case 'row_deleted':
                    return $this->handleRowDelete($payload);
                    
                case 'bulk_update':
                    return $this->handleBulkUpdate($payload);
                    
                default:
                    return array(
                        'success' => false,
                        'message' => 'Unknown action: ' . $action
                    );
            }
            
        } catch (Exception $e) {
            gi_log_error('Webhook processing failed', array('error' => $e->getMessage()));
            
            return array(
                'success' => false,
                'message' => 'Processing failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * 行更新の処理
     * 
     * @param array $payload ペイロード
     * @return array 処理結果
     */
    private function handleRowUpdate($payload) {
        if (!isset($payload['row_data'])) {
            return array('success' => false, 'message' => 'Missing row data');
        }
        
        $row_data = $payload['row_data'];
        $post_id = intval($row_data[0]);
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'grant') {
            return array('success' => false, 'message' => 'Post not found');
        }
        
        $updated_post = array(
            'ID' => $post_id,
            'post_title' => sanitize_text_field($row_data[1]),
            'post_content' => wp_kses_post($row_data[2]),
            'post_excerpt' => sanitize_textarea_field($row_data[3]),
            'post_status' => sanitize_text_field($row_data[4]),
        );
        
        $result = wp_update_post($updated_post, true);
        
        if (is_wp_error($result)) {
            return array('success' => false, 'message' => $result->get_error_message());
        }
        
        // ACFフィールドとタクソノミーを更新
        $sync = GoogleSheetsSync::getInstance();
        // Note: These methods would need to be made public or called differently
        
        return array(
            'success' => true,
            'message' => "Post {$post_id} updated successfully"
        );
    }
    
    /**
     * 行追加の処理
     * 
     * @param array $payload ペイロード
     * @return array 処理結果
     */
    private function handleRowAdd($payload) {
        if (!isset($payload['row_data'])) {
            return array('success' => false, 'message' => 'Missing row data');
        }
        
        $row_data = $payload['row_data'];
        
        $new_post = array(
            'post_title' => sanitize_text_field($row_data[1]),
            'post_content' => wp_kses_post($row_data[2]),
            'post_excerpt' => sanitize_textarea_field($row_data[3]),
            'post_status' => sanitize_text_field($row_data[4]),
            'post_type' => 'grant'
        );
        
        $post_id = wp_insert_post($new_post, true);
        
        if (is_wp_error($post_id)) {
            return array('success' => false, 'message' => $post_id->get_error_message());
        }
        
        return array(
            'success' => true,
            'message' => "Post {$post_id} created successfully",
            'post_id' => $post_id
        );
    }
    
    /**
     * 行削除の処理
     * 
     * @param array $payload ペイロード
     * @return array 処理結果
     */
    private function handleRowDelete($payload) {
        if (!isset($payload['post_id'])) {
            return array('success' => false, 'message' => 'Missing post ID');
        }
        
        $post_id = intval($payload['post_id']);
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'grant') {
            return array('success' => false, 'message' => 'Post not found');
        }
        
        $result = wp_delete_post($post_id, true);
        
        if (!$result) {
            return array('success' => false, 'message' => 'Failed to delete post');
        }
        
        return array(
            'success' => true,
            'message' => "Post {$post_id} deleted successfully"
        );
    }
    
    /**
     * 一括更新の処理
     * 
     * @param array $payload ペイロード
     * @return array 処理結果
     */
    private function handleBulkUpdate($payload) {
        if (!isset($payload['updates']) || !is_array($payload['updates'])) {
            return array('success' => false, 'message' => 'Missing updates data');
        }
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($payload['updates'] as $update) {
            $action = isset($update['action']) ? $update['action'] : '';
            
            switch ($action) {
                case 'update':
                    $result = $this->handleRowUpdate($update);
                    break;
                case 'add':
                    $result = $this->handleRowAdd($update);
                    break;
                case 'delete':
                    $result = $this->handleRowDelete($update);
                    break;
                default:
                    continue 2;
            }
            
            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        return array(
            'success' => true,
            'message' => "Bulk update completed: {$success_count} success, {$error_count} errors"
        );
    }
    
    /**
     * エクスポートハンドラー
     * 
     * @param WP_REST_Request $request リクエスト
     * @return WP_REST_Response レスポンス
     */
    public function handleExport($request) {
        $posts = get_posts(array(
            'post_type' => 'grant',
            'post_status' => array('publish', 'draft', 'private'),
            'numberposts' => -1,
            'orderby' => 'ID',
            'order' => 'ASC'
        ));
        
        $exported_data = array();
        
        foreach ($posts as $post) {
            $exported_data[] = $this->convertPostToExportRow($post);
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'count' => count($exported_data),
            'data' => $exported_data
        ));
    }
    
    /**
     * 投稿をエクスポート用の行に変換
     * 
     * @param WP_Post $post 投稿
     * @return array 行データ
     */
    private function convertPostToExportRow($post) {
        $post_id = $post->ID;
        
        return array(
            $post_id,
            $post->post_title,
            wp_strip_all_tags($post->post_content),
            $post->post_excerpt,
            $post->post_status,
            $post->post_date,
            $post->post_modified
        );
    }
    
    /**
     * Webhook URLの取得
     * 
     * @return string
     */
    public function getWebhookUrl() {
        return home_url('/?gi_sheets_webhook=true');
    }
    
    /**
     * REST API Webhook URLの取得
     * 
     * @return string
     */
    public function getRestWebhookUrl() {
        return rest_url('gi/v1/sheets-webhook');
    }
    
    /**
     * シークレットの取得
     * 
     * @return string
     */
    public function getSecret() {
        return $this->secret;
    }
}

// ============================================================================
// 初期化
// ============================================================================

/**
 * プラグイン初期化
 */
function gi_sheets_init() {
    // メインクラスの初期化
    GoogleSheetsSync::getInstance();
    
    // 管理画面の初期化（管理画面のみ）
    if (is_admin()) {
        GoogleSheetsAdmin::getInstance();
    }
    
    // Webhookハンドラーの初期化
    GoogleSheetsWebhook::getInstance();
}
add_action('init', 'gi_sheets_init', 10);

/**
 * プラグイン有効化時の処理
 */
function gi_sheets_activation() {
    // 必要なオプションの初期化
    if (!get_option('gi_sheets_sync_log')) {
        update_option('gi_sheets_sync_log', array());
    }
    
    // Cronの設定（オプション）
    if (!wp_next_scheduled('gi_sheets_scheduled_sync')) {
        // 定期同期が必要な場合はここで設定
        // wp_schedule_event(time(), 'hourly', 'gi_sheets_scheduled_sync');
    }
}
register_activation_hook(__FILE__, 'gi_sheets_activation');

/**
 * プラグイン無効化時の処理
 */
function gi_sheets_deactivation() {
    // Cronの解除
    $timestamp = wp_next_scheduled('gi_sheets_scheduled_sync');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'gi_sheets_scheduled_sync');
    }
    
    // トランジェントのクリア
    delete_transient('gi_sheets_access_token');
    delete_transient('gi_sheets_token_expires');
    delete_transient('gi_sheets_sync_progress');
}
register_deactivation_hook(__FILE__, 'gi_sheets_deactivation');

// ============================================================================
// 後方互換性のためのエイリアス関数
// ============================================================================

/**
 * GoogleSheetsSyncインスタンスの取得（後方互換性）
 * 
 * @return GoogleSheetsSync
 */
function gi_init_google_sheets_sync() {
    return GoogleSheetsSync::getInstance();
}

/**
 * エラーログ関数（後方互換性）
 */
if (!function_exists('gi_log_error')) {
    function gi_log_error($message, $context = array()) {
        gi_log($message, $context, 'error');
    }
}