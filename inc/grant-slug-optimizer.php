<?php
/**
 * Grant Slug Optimizer - URLスラッグ最適化システム
 * 
 * 日本語スラッグを投稿IDベースのスラッグに変換し、
 * 301リダイレクトを自動で設定する機能を提供します。
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0.0
 * @since 2024-12-08
 * 
 * 機能:
 * - 新規投稿の自動ID-based スラッグ生成
 * - 既存投稿のスラッグ一括変換
 * - 旧URL→新URLへの301リダイレクト
 * - 変換履歴の保存とリダイレクトマップ管理
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * =============================================================================
 * 1. 定数とオプション
 * =============================================================================
 */

// スラッグのプレフィックス設定
if (!defined('GI_SLUG_PREFIX')) {
    define('GI_SLUG_PREFIX', 'grant-');  // 例: grant-12345
}

// リダイレクトマップを保存するオプション名
define('GI_SLUG_REDIRECT_MAP_OPTION', 'gi_grant_slug_redirect_map');

// 変換ログオプション名
define('GI_SLUG_CONVERSION_LOG_OPTION', 'gi_grant_slug_conversion_log');

/**
 * =============================================================================
 * 2. 新規投稿のスラッグ自動生成
 * =============================================================================
 */

/**
 * 新規grant投稿が作成される際にIDベースのスラッグを自動設定
 * 
 * @param int $post_id 投稿ID
 * @param WP_Post $post 投稿オブジェクト
 * @param bool $update 更新かどうか
 */
function gi_auto_set_id_based_slug($post_id, $post, $update) {
    // grant投稿タイプのみ対象
    if ($post->post_type !== 'grant') {
        return;
    }
    
    // 自動保存やリビジョンは除外
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    // 既にIDベースのスラッグが設定されている場合はスキップ
    $expected_slug = GI_SLUG_PREFIX . $post_id;
    if ($post->post_name === $expected_slug) {
        return;
    }
    
    // 下書き状態の場合は公開時に処理
    if ($post->post_status === 'draft' || $post->post_status === 'auto-draft') {
        return;
    }
    
    // スラッグが日本語や記号を含む場合のみ変換
    // （手動で英数字スラッグを設定した場合は尊重）
    if (gi_should_convert_slug($post->post_name)) {
        // 旧スラッグを保存
        $old_slug = $post->post_name;
        
        // 新スラッグを生成
        $new_slug = $expected_slug;
        
        // フックを一時的に削除（無限ループ防止）
        remove_action('save_post', 'gi_auto_set_id_based_slug', 20, 3);
        
        // スラッグを更新
        wp_update_post(array(
            'ID' => $post_id,
            'post_name' => $new_slug
        ));
        
        // フックを再登録
        add_action('save_post', 'gi_auto_set_id_based_slug', 20, 3);
        
        // リダイレクトマップに追加
        if (!empty($old_slug) && $old_slug !== $new_slug) {
            gi_add_slug_redirect($old_slug, $new_slug, $post_id);
        }
    }
}
add_action('save_post', 'gi_auto_set_id_based_slug', 20, 3);

/**
 * 投稿公開時にスラッグを確認・設定
 * 
 * @param string $new_status 新しいステータス
 * @param string $old_status 古いステータス
 * @param WP_Post $post 投稿オブジェクト
 */
function gi_set_slug_on_publish($new_status, $old_status, $post) {
    // 公開への移行時のみ処理
    if ($new_status !== 'publish' || $post->post_type !== 'grant') {
        return;
    }
    
    // 既にIDベースのスラッグなら何もしない
    $expected_slug = GI_SLUG_PREFIX . $post->ID;
    if ($post->post_name === $expected_slug) {
        return;
    }
    
    // 日本語スラッグや記号を含むスラッグの場合のみ変換
    if (gi_should_convert_slug($post->post_name)) {
        $old_slug = $post->post_name;
        
        // フックを一時的に削除
        remove_action('transition_post_status', 'gi_set_slug_on_publish', 10, 3);
        
        wp_update_post(array(
            'ID' => $post->ID,
            'post_name' => $expected_slug
        ));
        
        add_action('transition_post_status', 'gi_set_slug_on_publish', 10, 3);
        
        // リダイレクトマップに追加
        if (!empty($old_slug)) {
            gi_add_slug_redirect($old_slug, $expected_slug, $post->ID);
        }
    }
}
add_action('transition_post_status', 'gi_set_slug_on_publish', 10, 3);

/**
 * スラッグを変換すべきかどうかを判定
 * 
 * @param string $slug スラッグ
 * @return bool 変換が必要な場合true
 */
function gi_should_convert_slug($slug) {
    if (empty($slug)) {
        return true;
    }
    
    // 既にIDベースのスラッグの場合
    if (preg_match('/^' . preg_quote(GI_SLUG_PREFIX, '/') . '\d+$/', $slug)) {
        return false;
    }
    
    // 日本語文字を含む場合
    if (preg_match('/[\x{3000}-\x{303f}\x{3040}-\x{309f}\x{30a0}-\x{30ff}\x{ff00}-\x{ffef}\x{4e00}-\x{9faf}]/u', $slug)) {
        return true;
    }
    
    // URLエンコードされた文字を含む場合
    if (preg_match('/%[0-9a-fA-F]{2}/', $slug)) {
        return true;
    }
    
    // 全角記号を含む場合
    if (preg_match('/[\x{3010}-\x{301f}\x{ff01}-\x{ff5e}]/u', $slug)) {
        return true;
    }
    
    return false;
}

/**
 * =============================================================================
 * 3. 301リダイレクト機能
 * =============================================================================
 */

/**
 * リダイレクトマップにエントリを追加
 * 
 * @param string $old_slug 旧スラッグ
 * @param string $new_slug 新スラッグ
 * @param int $post_id 投稿ID
 */
function gi_add_slug_redirect($old_slug, $new_slug, $post_id) {
    $redirect_map = get_option(GI_SLUG_REDIRECT_MAP_OPTION, array());
    
    // URLエンコードされた形式も保存（両方でリダイレクトに対応）
    $old_slug_encoded = urlencode($old_slug);
    $old_slug_decoded = urldecode($old_slug);
    
    // 両方の形式を保存
    $redirect_map[$old_slug] = array(
        'new_slug' => $new_slug,
        'post_id' => $post_id,
        'created_at' => current_time('mysql'),
        'original_url' => home_url('/grants/' . $old_slug . '/')
    );
    
    // エンコードされた形式が異なる場合は追加
    if ($old_slug !== $old_slug_encoded) {
        $redirect_map[$old_slug_encoded] = array(
            'new_slug' => $new_slug,
            'post_id' => $post_id,
            'created_at' => current_time('mysql'),
            'original_url' => home_url('/grants/' . $old_slug_encoded . '/')
        );
    }
    
    if ($old_slug !== $old_slug_decoded) {
        $redirect_map[$old_slug_decoded] = array(
            'new_slug' => $new_slug,
            'post_id' => $post_id,
            'created_at' => current_time('mysql'),
            'original_url' => home_url('/grants/' . $old_slug_decoded . '/')
        );
    }
    
    update_option(GI_SLUG_REDIRECT_MAP_OPTION, $redirect_map);
    
    // ログに記録
    gi_log_slug_conversion($post_id, $old_slug, $new_slug);
}

/**
 * 変換ログを記録
 * 
 * @param int $post_id 投稿ID
 * @param string $old_slug 旧スラッグ
 * @param string $new_slug 新スラッグ
 */
function gi_log_slug_conversion($post_id, $old_slug, $new_slug) {
    $log = get_option(GI_SLUG_CONVERSION_LOG_OPTION, array());
    
    $log[] = array(
        'post_id' => $post_id,
        'old_slug' => $old_slug,
        'new_slug' => $new_slug,
        'converted_at' => current_time('mysql'),
        'post_title' => get_the_title($post_id)
    );
    
    // ログは最大500件まで保存
    if (count($log) > 500) {
        $log = array_slice($log, -500);
    }
    
    update_option(GI_SLUG_CONVERSION_LOG_OPTION, $log);
}

/**
 * 旧URLから新URLへ301リダイレクト
 */
function gi_handle_old_slug_redirect() {
    // 管理画面やAPIリクエストでは実行しない
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    
    // grants のシングルページのみ対象
    $request_uri = $_SERVER['REQUEST_URI'];
    
    // /grants/ で始まるURLのみ処理
    if (!preg_match('#^/grants/([^/]+)/?#', $request_uri, $matches)) {
        return;
    }
    
    $requested_slug = $matches[1];
    
    // URLデコード
    $requested_slug_decoded = urldecode($requested_slug);
    
    // リダイレクトマップを取得
    $redirect_map = get_option(GI_SLUG_REDIRECT_MAP_OPTION, array());
    
    // マップに存在するかチェック
    $redirect_info = null;
    
    if (isset($redirect_map[$requested_slug])) {
        $redirect_info = $redirect_map[$requested_slug];
    } elseif (isset($redirect_map[$requested_slug_decoded])) {
        $redirect_info = $redirect_map[$requested_slug_decoded];
    }
    
    if ($redirect_info) {
        // 投稿が存在するか確認
        $post = get_post($redirect_info['post_id']);
        
        if ($post && $post->post_status === 'publish') {
            // 新しいURLを構築
            $new_url = home_url('/grants/' . $redirect_info['new_slug'] . '/');
            
            // 301リダイレクトを実行
            wp_redirect($new_url, 301);
            exit;
        }
    }
}
add_action('template_redirect', 'gi_handle_old_slug_redirect', 1);

/**
 * =============================================================================
 * 4. 既存投稿の一括変換機能
 * =============================================================================
 */

/**
 * 変換が必要な投稿を取得
 * 
 * @param int $limit 取得件数制限
 * @return array 投稿の配列
 */
function gi_get_grants_needing_slug_conversion($limit = -1) {
    global $wpdb;
    
    // メモリ制限を緩和
    @ini_set('memory_limit', '768M');
    
    $prefix = GI_SLUG_PREFIX;
    
    // 最小限のカラムのみ取得（メモリ節約）
    // grant-で始まらないもの、またはgrant-数字の形式でないものを取得
    $query = "SELECT ID, post_name 
         FROM {$wpdb->posts} 
         WHERE post_type = 'grant' 
         AND post_status = 'publish'
         AND post_name NOT REGEXP '^grant-[0-9]+$'
         ORDER BY ID ASC";
    
    if ($limit > 0) {
        $query .= $wpdb->prepare(" LIMIT %d", intval($limit));
    }
    
    $results = $wpdb->get_results($query, ARRAY_A); // 配列で取得（メモリ効率化）
    
    if (empty($results)) {
        return array();
    }
    
    // objectに変換して返す（互換性のため）
    $objects = array();
    foreach ($results as $row) {
        $objects[] = (object)$row;
    }
    
    return $objects;
}

/**
 * 変換が必要な投稿数を取得（キャッシュ付き）
 * 
 * @return int 件数
 */
function gi_count_grants_needing_conversion() {
    // トランジェントキャッシュを使用（10分間）
    $cache_key = 'gi_grants_need_conversion_count';
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return (int) $cached;
    }
    
    global $wpdb;
    
    // メモリ制限を緩和
    @ini_set('memory_limit', '768M');
    
    // 全体数を取得
    $total = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} 
         WHERE post_type = 'grant' 
         AND post_status = 'publish'"
    );
    
    // IDベースのスラッグの数を取得（grant-数字のみ）
    // REGEXPで正確にカウント
    $id_based = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} 
         WHERE post_type = 'grant' 
         AND post_status = 'publish'
         AND post_name REGEXP '^grant-[0-9]+$'"
    );
    
    $count = max(0, $total - $id_based);
    
    // キャッシュに保存（10分）
    set_transient($cache_key, $count, 10 * MINUTE_IN_SECONDS);
    
    return $count;
}

/**
 * キャッシュをクリア
 */
function gi_clear_conversion_cache() {
    delete_transient('gi_grants_need_conversion_count');
}

/**
 * 単一の投稿のスラッグを変換
 * 
 * @param int $post_id 投稿ID
 * @return array 結果
 */
function gi_convert_single_grant_slug($post_id) {
    try {
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'grant') {
            return array(
                'success' => false,
                'message' => '投稿が見つかりません (ID: ' . $post_id . ')'
            );
        }
        
        $old_slug = $post->post_name;
        $new_slug = GI_SLUG_PREFIX . $post_id;
        
        // 既にIDベースのスラッグの場合
        if (preg_match('/^grant-\d+$/', $old_slug)) {
            return array(
                'success' => true,
                'message' => '既にIDベースのスラッグです',
                'skipped' => true
            );
        }
        
        // フックを一時的に無効化（無限ループ防止）
        remove_action('save_post', 'gi_auto_set_id_based_slug', 20);
        remove_action('transition_post_status', 'gi_set_slug_on_publish', 10);
        
        // 直接DBを更新（wp_update_postより軽量）
        global $wpdb;
        $updated = $wpdb->update(
            $wpdb->posts,
            array('post_name' => $new_slug),
            array('ID' => $post_id),
            array('%s'),
            array('%d')
        );
        
        // フックを再有効化
        add_action('save_post', 'gi_auto_set_id_based_slug', 20, 3);
        add_action('transition_post_status', 'gi_set_slug_on_publish', 10, 3);
        
        if ($updated === false) {
            return array(
                'success' => false,
                'message' => 'DB更新エラー: ' . $wpdb->last_error
            );
        }
        
        // キャッシュをクリア
        clean_post_cache($post_id);
        
        // リダイレクトマップに追加
        gi_add_slug_redirect($old_slug, $new_slug, $post_id);
        
        // カウントキャッシュをクリア
        gi_clear_conversion_cache();
        
        return array(
            'success' => true,
            'post_id' => $post_id,
            'old_slug' => $old_slug,
            'new_slug' => $new_slug,
            'old_url' => home_url('/grants/' . $old_slug . '/'),
            'new_url' => home_url('/grants/' . $new_slug . '/')
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => '例外エラー: ' . $e->getMessage()
        );
    }
}

/**
 * 一括変換処理（バッチ処理）
 * 
 * @param int $batch_size 1回あたりの処理件数
 * @return array 結果
 */
function gi_bulk_convert_grant_slugs($batch_size = 20) {
    // メモリ制限を大幅に緩和
    @ini_set('memory_limit', '768M');
    
    // 最大実行時間を延長
    @set_time_limit(300);
    
    $results = array(
        'processed' => 0,
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
        'details' => array(),
        'remaining' => 0,
        'error' => null
    );
    
    try {
        // キャッシュをクリア
        gi_clear_conversion_cache();
        
        $grants = gi_get_grants_needing_slug_conversion($batch_size);
        
        if (empty($grants)) {
            $results['remaining'] = 0;
            return $results;
        }
        
        foreach ($grants as $grant) {
            // メモリ使用量をチェック
            $memory_usage = memory_get_usage(true);
            $memory_limit = ini_get('memory_limit');
            $memory_limit_bytes = wp_convert_hr_to_bytes($memory_limit);
            
            // メモリが85%以上使用されていたら中断
            if ($memory_usage > $memory_limit_bytes * 0.85) {
                $results['error'] = 'メモリ制限に近づいたため中断しました (使用: ' . round($memory_usage / 1048576, 2) . 'MB / ' . $memory_limit . ')';
                error_log('[Slug Optimizer] Memory limit reached: ' . round($memory_usage / 1048576, 2) . 'MB');
                break;
            }
            
            $result = gi_convert_single_grant_slug($grant->ID);
            $results['processed']++;
            
            if ($result['success']) {
                if (isset($result['skipped']) && $result['skipped']) {
                    $results['skipped']++;
                } else {
                    $results['success']++;
                    // 詳細は最大10件まで
                    if (count($results['details']) < 10) {
                        $results['details'][] = array(
                            'post_id' => $grant->ID,
                            'title' => mb_substr(get_the_title($grant->ID), 0, 30),
                            'old_slug' => mb_substr($result['old_slug'] ?? $grant->post_name, 0, 30),
                            'new_slug' => $result['new_slug'] ?? GI_SLUG_PREFIX . $grant->ID
                        );
                    }
                }
            } else {
                $results['failed']++;
                if (count($results['details']) < 10) {
                    $results['details'][] = array(
                        'post_id' => $grant->ID,
                        'title' => mb_substr(get_the_title($grant->ID), 0, 30),
                        'error' => $result['message']
                    );
                }
            }
            
            // 1件ごとにメモリ解放
            wp_cache_flush();
        }
        
        // 残り件数を計算（キャッシュなしで）
        gi_clear_conversion_cache();
        $results['remaining'] = gi_count_grants_needing_conversion();
        
    } catch (Exception $e) {
        $results['error'] = '例外: ' . $e->getMessage();
        error_log('[Slug Optimizer] Batch error: ' . $e->getMessage());
    } catch (Error $e) {
        $results['error'] = 'PHPエラー: ' . $e->getMessage();
        error_log('[Slug Optimizer] PHP error: ' . $e->getMessage());
    }
    
    return $results;
}

/**
 * =============================================================================
 * 5. 管理画面UI
 * =============================================================================
 */

/**
 * 管理画面メニューを追加
 */
function gi_add_slug_optimizer_menu() {
    add_submenu_page(
        'edit.php?post_type=grant',
        'URLスラッグ最適化',
        'URLスラッグ最適化',
        'manage_options',
        'grant-slug-optimizer',
        'gi_slug_optimizer_admin_page'
    );
}
add_action('admin_menu', 'gi_add_slug_optimizer_menu');

/**
 * 管理画面ページを表示
 */
function gi_slug_optimizer_admin_page() {
    // 権限チェック
    if (!current_user_can('manage_options')) {
        wp_die('アクセス権限がありません');
    }
    
    // 統計情報を取得
    $total_grants = wp_count_posts('grant')->publish;
    $needs_conversion = gi_count_grants_needing_conversion();
    $already_converted = $total_grants - $needs_conversion;
    $redirect_map = get_option(GI_SLUG_REDIRECT_MAP_OPTION, array());
    $conversion_log = get_option(GI_SLUG_CONVERSION_LOG_OPTION, array());
    
    ?>
    <div class="wrap">
        <h1>🔗 URLスラッグ最適化</h1>
        
        <div class="notice notice-info">
            <p>
                <strong>この機能について:</strong><br>
                日本語や記号を含むURLスラッグを投稿IDベースの安全なスラッグに変換します。<br>
                例: <code>/grants/【2025年】物価高騰対応重点支援地方創生臨時交付/</code> → <code>/grants/grant-12345/</code><br>
                変換後も旧URLから新URLへ自動的に301リダイレクトされるため、SEOへの悪影響を最小限に抑えます。
            </p>
        </div>
        
        <div class="card">
            <h2>📊 現在の状態</h2>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>項目</th>
                    <th>件数</th>
                </tr>
                <tr>
                    <td>公開済み助成金総数</td>
                    <td><strong><?php echo number_format($total_grants); ?></strong> 件</td>
                </tr>
                <tr>
                    <td>✅ 変換済み（IDベーススラッグ）</td>
                    <td><span style="color: green;"><?php echo number_format($already_converted); ?></span> 件</td>
                </tr>
                <tr>
                    <td>⚠️ 変換が必要</td>
                    <td><span style="color: <?php echo $needs_conversion > 0 ? 'red' : 'green'; ?>">
                        <?php echo number_format($needs_conversion); ?>
                    </span> 件</td>
                </tr>
                <tr>
                    <td>🔀 リダイレクト登録数</td>
                    <td><?php echo number_format(count($redirect_map)); ?> 件</td>
                </tr>
            </table>
        </div>
        
        <?php if ($needs_conversion > 0): ?>
        <div class="card">
            <h2>🔄 一括変換</h2>
            <p>
                <strong><?php echo number_format($needs_conversion); ?> 件</strong>の投稿のスラッグを変換する必要があります。
            </p>
            
            <!-- 進捗状況パネル -->
            <div id="conversion-status-panel" style="display: none; margin: 20px 0; padding: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px;">
                <h3 style="margin-top: 0;">📊 変換進捗状況</h3>
                
                <!-- プログレスバー -->
                <div style="margin: 15px 0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span id="progress-label">処理中...</span>
                        <span id="progress-percent">0%</span>
                    </div>
                    <div style="width: 100%; background: #e0e0e0; border-radius: 4px; overflow: hidden; height: 24px;">
                        <div id="progress-fill" style="width: 0%; height: 100%; background: linear-gradient(90deg, #0073aa, #00a0d2); transition: width 0.3s;"></div>
                    </div>
                </div>
                
                <!-- 詳細統計 -->
                <div id="progress-stats" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 15px 0;">
                    <div style="text-align: center; padding: 10px; background: #fff; border-radius: 4px;">
                        <div style="font-size: 24px; font-weight: bold; color: #0073aa;" id="stat-processed">0</div>
                        <div style="font-size: 12px; color: #666;">処理済み</div>
                    </div>
                    <div style="text-align: center; padding: 10px; background: #fff; border-radius: 4px;">
                        <div style="font-size: 24px; font-weight: bold; color: #28a745;" id="stat-success">0</div>
                        <div style="font-size: 12px; color: #666;">成功</div>
                    </div>
                    <div style="text-align: center; padding: 10px; background: #fff; border-radius: 4px;">
                        <div style="font-size: 24px; font-weight: bold; color: #ffc107;" id="stat-skipped">0</div>
                        <div style="font-size: 12px; color: #666;">スキップ</div>
                    </div>
                    <div style="text-align: center; padding: 10px; background: #fff; border-radius: 4px;">
                        <div style="font-size: 24px; font-weight: bold; color: #dc3545;" id="stat-failed">0</div>
                        <div style="font-size: 12px; color: #666;">失敗</div>
                    </div>
                </div>
                
                <!-- ログ出力 -->
                <div style="margin-top: 15px;">
                    <details open>
                        <summary style="cursor: pointer; font-weight: bold;">📝 処理ログ</summary>
                        <div id="conversion-log" style="max-height: 200px; overflow-y: auto; background: #1e1e1e; color: #d4d4d4; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; margin-top: 10px;">
                            <div class="log-entry">[待機中] 変換開始を待っています...</div>
                        </div>
                    </details>
                </div>
                
                <!-- 残り時間推定 -->
                <div id="time-estimate" style="margin-top: 10px; font-size: 12px; color: #666;"></div>
            </div>
            
            <div id="conversion-result" style="display: none; margin: 20px 0;"></div>
            
            <p>
                <input type="hidden" id="gi_bulk_convert_nonce" value="<?php echo wp_create_nonce('gi_bulk_convert_nonce'); ?>" />
                <button type="button" id="start-conversion" class="button button-primary button-large">
                    🚀 一括変換を開始
                </button>
                <button type="button" id="stop-conversion" class="button button-secondary" style="display: none; margin-left: 10px;">
                    ⏹️ 停止
                </button>
                <span class="spinner" id="conversion-spinner" style="float: none; margin-left: 10px;"></span>
            </p>
            
            <div class="notice notice-info" style="margin-top: 15px;">
                <p>
                    <strong>💡 ヒント:</strong> 
                    処理は20件ずつバッチ処理されます。エラーが発生しても自動リトライします。<br>
                    ページを閉じても処理状態は保存されます。再度このページを開くと続きから再開できます。
                </p>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <h2>✅ 変換完了</h2>
            <p style="color: green;">
                すべての助成金投稿が最適化されたスラッグを使用しています。
            </p>
        </div>
        <?php endif; ?>
        
        <?php
        // 変換が必要な投稿のプレビュー
        $preview_posts = gi_get_grants_needing_slug_conversion(10);
        if (!empty($preview_posts)):
        ?>
        <div class="card">
            <h2>📋 変換対象プレビュー（最初の10件）</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>タイトル</th>
                        <th>現在のスラッグ</th>
                        <th>変換後のスラッグ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview_posts as $post): ?>
                    <tr>
                        <td><?php echo $post->ID; ?></td>
                        <td><?php echo esc_html(mb_substr(get_the_title($post->ID), 0, 40)); ?>...</td>
                        <td>
                            <code style="font-size: 11px; background: #fff3cd; padding: 2px 6px; display: inline-block; max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo esc_html(urldecode($post->post_name)); ?>
                            </code>
                        </td>
                        <td>
                            <code style="font-size: 11px; background: #d4edda; padding: 2px 6px;">
                                <?php echo GI_SLUG_PREFIX . $post->ID; ?>
                            </code>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($conversion_log)): ?>
        <div class="card">
            <h2>📜 変換履歴（最新10件）</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>日時</th>
                        <th>投稿ID</th>
                        <th>タイトル</th>
                        <th>旧スラッグ → 新スラッグ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $recent_logs = array_slice(array_reverse($conversion_log), 0, 10);
                    foreach ($recent_logs as $log): 
                    ?>
                    <tr>
                        <td><?php echo esc_html($log['converted_at']); ?></td>
                        <td><?php echo esc_html($log['post_id']); ?></td>
                        <td><?php echo esc_html(mb_substr($log['post_title'], 0, 30)); ?>...</td>
                        <td>
                            <code style="font-size: 10px; background: #fff3cd; padding: 1px 4px;">
                                <?php echo esc_html(mb_substr(urldecode($log['old_slug']), 0, 20)); ?>...
                            </code>
                            →
                            <code style="font-size: 10px; background: #d4edda; padding: 1px 4px;">
                                <?php echo esc_html($log['new_slug']); ?>
                            </code>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>🔧 リダイレクトマップ管理</h2>
            <p>
                現在 <strong><?php echo number_format(count($redirect_map)); ?></strong> 件のリダイレクトが登録されています。
            </p>
            <?php if (!empty($redirect_map)): ?>
            <details>
                <summary style="cursor: pointer; color: #0073aa;">リダイレクトマップを表示（クリックで展開）</summary>
                <div style="max-height: 400px; overflow-y: auto; margin-top: 10px;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>旧スラッグ</th>
                                <th>新スラッグ</th>
                                <th>投稿ID</th>
                                <th>登録日時</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 0;
                            foreach ($redirect_map as $old_slug => $info): 
                                if ($count++ >= 100) {
                                    echo '<tr><td colspan="4">...以降省略（残り' . (count($redirect_map) - 100) . '件）</td></tr>';
                                    break;
                                }
                            ?>
                            <tr>
                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                    <code style="font-size: 10px;"><?php echo esc_html(mb_substr(urldecode($old_slug), 0, 30)); ?></code>
                                </td>
                                <td><code style="font-size: 10px;"><?php echo esc_html($info['new_slug']); ?></code></td>
                                <td><?php echo esc_html($info['post_id']); ?></td>
                                <td><?php echo esc_html($info['created_at']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </details>
            <?php endif; ?>
            
            <form method="post" style="margin-top: 15px;">
                <?php wp_nonce_field('gi_clear_redirect_map', 'gi_redirect_nonce'); ?>
                <button type="submit" name="gi_clear_redirect_map" class="button" 
                        onclick="return confirm('リダイレクトマップをクリアしますか？\n\n注意: クリアすると旧URLからのリダイレクトが機能しなくなります。');">
                    🗑️ リダイレクトマップをクリア
                </button>
            </form>
            
            <?php
            // リダイレクトマップクリア処理
            if (isset($_POST['gi_clear_redirect_map']) && 
                wp_verify_nonce($_POST['gi_redirect_nonce'], 'gi_clear_redirect_map')) {
                delete_option(GI_SLUG_REDIRECT_MAP_OPTION);
                echo '<div class="notice notice-success"><p>リダイレクトマップをクリアしました。</p></div>';
            }
            ?>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // ============================================
        // 状態管理
        // ============================================
        var state = {
            isConverting: false,
            isStopped: false,
            totalToConvert: <?php echo $needs_conversion; ?>,
            processed: 0,
            success: 0,
            skipped: 0,
            failed: 0,
            retryCount: 0,
            maxRetries: 3, // リトライ回数を削減（無限ループ防止）
            consecutiveErrors: 0,
            maxConsecutiveErrors: 3, // 連続エラーで停止
            batchSize: 5, // バッチサイズを削減（メモリ節約）
            startTime: null,
            nonce: $('#gi_bulk_convert_nonce').val()
        };
        
        // ローカルストレージから状態を復元
        var savedState = localStorage.getItem('gi_slug_conversion_state');
        if (savedState) {
            try {
                var parsed = JSON.parse(savedState);
                if (parsed.totalToConvert === state.totalToConvert) {
                    // 同じジョブの続き
                    addLog('info', '前回の処理状態を復元しました');
                }
            } catch(e) {}
        }
        
        console.log('[Slug Optimizer] Initialized. Total:', state.totalToConvert);
        
        // ============================================
        // ログ機能
        // ============================================
        function addLog(type, message) {
            var $log = $('#conversion-log');
            var timestamp = new Date().toLocaleTimeString();
            var colors = {
                'info': '#58a6ff',
                'success': '#3fb950',
                'warning': '#d29922',
                'error': '#f85149'
            };
            var color = colors[type] || '#d4d4d4';
            
            $log.append('<div class="log-entry" style="color: ' + color + '">[' + timestamp + '] ' + message + '</div>');
            $log.scrollTop($log[0].scrollHeight);
        }
        
        // ============================================
        // UI更新
        // ============================================
        function updateUI() {
            var progress = state.totalToConvert > 0 ? Math.min(100, (state.processed / state.totalToConvert) * 100) : 0;
            
            $('#progress-fill').css('width', progress + '%');
            $('#progress-percent').text(progress.toFixed(1) + '%');
            $('#progress-label').text('処理中... ' + state.processed + ' / ' + state.totalToConvert + ' 件');
            
            $('#stat-processed').text(state.processed);
            $('#stat-success').text(state.success);
            $('#stat-skipped').text(state.skipped);
            $('#stat-failed').text(state.failed);
            
            // 残り時間推定
            if (state.startTime && state.processed > 0) {
                var elapsed = (Date.now() - state.startTime) / 1000;
                var rate = state.processed / elapsed;
                var remaining = state.totalToConvert - state.processed;
                var eta = remaining / rate;
                
                if (eta > 60) {
                    $('#time-estimate').text('推定残り時間: 約 ' + Math.ceil(eta / 60) + ' 分');
                } else {
                    $('#time-estimate').text('推定残り時間: 約 ' + Math.ceil(eta) + ' 秒');
                }
            }
            
            // 状態を保存
            localStorage.setItem('gi_slug_conversion_state', JSON.stringify(state));
        }
        
        // ============================================
        // バッチ処理
        // ============================================
        function runBatch() {
            if (state.isStopped) {
                addLog('warning', '処理が停止されました');
                finishConversion(false, '処理が停止されました。続きは「一括変換を開始」で再開できます。');
                return;
            }
            
            addLog('info', 'バッチ処理開始 (リトライ: ' + state.retryCount + '/' + state.maxRetries + ')');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gi_bulk_convert_slugs',
                    _wpnonce: state.nonce,
                    batch_size: state.batchSize
                },
                timeout: 60000, // 60秒タイムアウト
                success: function(response) {
                    state.retryCount = 0; // 成功したらリトライカウントをリセット
                    state.consecutiveErrors = 0; // 連続エラーカウントもリセット
                    
                    if (response.success) {
                        var data = response.data;
                        
                        state.processed += data.processed || 0;
                        state.success += data.success || 0;
                        state.skipped += data.skipped || 0;
                        state.failed += data.failed || 0;
                        
                        addLog('success', '処理完了: ' + (data.processed || 0) + '件 (成功: ' + (data.success || 0) + ', スキップ: ' + (data.skipped || 0) + ', 失敗: ' + (data.failed || 0) + ')');
                        
                        // エラーメッセージがあれば表示
                        if (data.error) {
                            addLog('warning', 'バッチ警告: ' + data.error);
                        }
                        
                        // 詳細ログ
                        if (data.details && data.details.length > 0) {
                            data.details.forEach(function(d) {
                                if (d.error) {
                                    addLog('error', 'ID ' + d.post_id + ': ' + d.error);
                                } else {
                                    addLog('info', 'ID ' + d.post_id + ': ' + d.old_slug + ' → ' + d.new_slug);
                                }
                            });
                        }
                        
                        updateUI();
                        
                        var remaining = data.remaining || 0;
                        if (remaining > 0) {
                            addLog('info', '残り ' + remaining + ' 件。次のバッチを開始...');
                            setTimeout(runBatch, 2000); // サーバー負荷軽減のため間隔を延長
                        } else {
                            finishConversion(true, '全ての変換が完了しました！ (成功: ' + state.success + ', スキップ: ' + state.skipped + ', 失敗: ' + state.failed + ')');
                        }
                    } else {
                        // エラーでも詳細を表示
                        var errMsg = '不明なエラー';
                        if (response.data) {
                            errMsg = response.data.message || errMsg;
                            if (response.data.code) errMsg += ' [' + response.data.code + ']';
                            if (response.data.file) errMsg += ' (' + response.data.file + ':' + response.data.line + ')';
                        }
                        handleError(errMsg);
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = 'AJAX エラー: ' + status;
                    if (error) errorMsg += ' - ' + error;
                    if (xhr.status) errorMsg += ' (HTTP ' + xhr.status + ')';
                    
                    handleError(errorMsg);
                }
            });
        }
        
        // ============================================
        // エラーハンドリング
        // ============================================
        function handleError(errorMsg) {
            addLog('error', errorMsg);
            
            state.retryCount++;
            state.consecutiveErrors++;
            
            // HTTP 500エラーまたは連続エラーが多い場合は即座に停止
            if (errorMsg.includes('HTTP 500') || state.consecutiveErrors >= state.maxConsecutiveErrors) {
                addLog('error', '致命的なエラーが発生しました。処理を中断します。');
                addLog('error', 'サーバーのメモリ不足またはデータベースエラーの可能性があります。');
                addLog('info', 'バッチサイズを減らすか、管理者に連絡してください。');
                
                if (state.processed > 0) {
                    finishConversion(false, '処理中断: ' + state.processed + '件処理済み。ページをリロードして続きを実行してください。（エラー: ' + errorMsg + '）');
                } else {
                    finishConversion(false, 'エラーが発生しました: ' + errorMsg + '\n\nサーバーのメモリ不足の可能性があります。管理者に連絡してください。');
                }
                return;
            }
            
            if (state.retryCount <= state.maxRetries) {
                var waitTime = state.retryCount * 5000; // リトライ間隔を延長（5秒ずつ）
                addLog('warning', state.retryCount + '回目のリトライを ' + (waitTime/1000) + '秒後に実行...');
                
                setTimeout(function() {
                    if (state.retryCount <= 1) {
                        addLog('info', '直接リトライを試行...');
                        runBatch();
                    } else {
                        // 2回目以降はnonce再取得
                        refreshNonceAndRetry();
                    }
                }, waitTime);
            } else {
                // 最大リトライ超過
                if (state.processed > 0) {
                    addLog('warning', '一部処理完了。残りはページリロード後に再開してください。');
                    finishConversion(false, '一部処理完了: ' + state.processed + '件処理済み。ページをリロードして続きを実行してください。');
                } else {
                    finishConversion(false, 'エラーが発生しました。ページをリロードしてください。');
                }
            }
        }
        
        // ============================================
        // Nonce再取得
        // ============================================
        function refreshNonceAndRetry() {
            addLog('info', 'セキュリティトークンを再取得中...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                timeout: 30000,
                data: {
                    action: 'gi_refresh_slug_nonce'
                },
                success: function(response) {
                    if (response.success && response.data && response.data.nonce) {
                        state.nonce = response.data.nonce;
                        addLog('success', 'トークン再取得成功。処理を再開します。');
                        state.retryCount = 0; // リトライカウントをリセット
                        setTimeout(runBatch, 1000);
                    } else {
                        addLog('warning', 'トークン再取得失敗。元のトークンで再試行...');
                        // 失敗しても元のnonceで再試行
                        setTimeout(runBatch, 2000);
                    }
                },
                error: function(xhr, status, error) {
                    addLog('warning', 'トークン再取得通信エラー (' + status + ')。元のトークンで再試行...');
                    setTimeout(runBatch, 2000); // 2秒後に古いnonceで再試行
                }
            });
        }
        
        // ============================================
        // 完了処理
        // ============================================
        function finishConversion(success, message) {
            state.isConverting = false;
            state.isStopped = false;
            
            $('#start-conversion').prop('disabled', false).show();
            $('#stop-conversion').hide();
            $('#conversion-spinner').removeClass('is-active');
            
            if (success) {
                addLog('success', '=== 変換完了 ===');
                localStorage.removeItem('gi_slug_conversion_state');
                
                $('#conversion-result')
                    .html('<div class="notice notice-success"><p>✅ ' + message + '</p></div>')
                    .show();
                
                setTimeout(function() {
                    location.reload();
                }, 3000);
            } else {
                addLog('warning', '=== 処理中断 ===');
                
                $('#conversion-result')
                    .html('<div class="notice notice-warning"><p>⚠️ ' + message + '</p></div>')
                    .show();
            }
        }
        
        // ============================================
        // イベントハンドラ
        // ============================================
        $('#start-conversion').on('click', function() {
            if (state.isConverting) return;
            
            if (!confirm('一括変換を開始しますか？\n\n処理中は他のタブで作業できます。')) {
                return;
            }
            
            state.isConverting = true;
            state.isStopped = false;
            state.startTime = Date.now();
            state.retryCount = 0;
            
            $(this).prop('disabled', true);
            $('#stop-conversion').show();
            $('#conversion-spinner').addClass('is-active');
            $('#conversion-status-panel').show();
            $('#conversion-result').hide();
            $('#conversion-log').html('');
            
            addLog('info', '=== 一括変換を開始 ===');
            addLog('info', '対象: ' + state.totalToConvert + ' 件');
            
            updateUI();
            runBatch();
        });
        
        $('#stop-conversion').on('click', function() {
            if (confirm('処理を停止しますか？\n\n停止後、再度「一括変換を開始」で続きから再開できます。')) {
                state.isStopped = true;
                addLog('warning', '停止リクエストを受信...');
            }
        });
    });
    </script>
    <?php
}

/**
 * AJAX: 一括変換処理
 */
function gi_ajax_bulk_convert_slugs() {
    // エラーハンドリングを設定
    $error_handler = function($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    };
    set_error_handler($error_handler, E_ERROR | E_WARNING);
    
    try {
        // タイムアウトを延長
        @set_time_limit(300);
        @ini_set('max_execution_time', 300);
        @ini_set('memory_limit', '512M');
        
        // セキュリティチェック - nonceを検証
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '';
        
        if (!wp_verify_nonce($nonce, 'gi_bulk_convert_nonce')) {
            restore_error_handler();
            wp_send_json_error(array(
                'message' => 'セキュリティトークンが無効です。ページをリロードしてください。',
                'code' => 'invalid_nonce'
            ));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            restore_error_handler();
            wp_send_json_error(array(
                'message' => '権限がありません',
                'code' => 'no_permission'
            ));
            return;
        }
        
        // バッチサイズを取得（デフォルト5件 - メモリ安全性優先）
        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 5;
        $batch_size = min(max($batch_size, 3), 10); // 3〜10件の範囲（大幅に削減）
        
        // バッチ処理を実行
        $results = gi_bulk_convert_grant_slugs($batch_size);
        
        restore_error_handler();
        wp_send_json_success($results);
        
    } catch (Exception $e) {
        restore_error_handler();
        error_log('[Slug Optimizer AJAX] Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        wp_send_json_error(array(
            'message' => 'サーバーエラー: ' . $e->getMessage(),
            'code' => 'exception',
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ));
    } catch (Error $e) {
        restore_error_handler();
        error_log('[Slug Optimizer AJAX] PHP Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        wp_send_json_error(array(
            'message' => 'PHPエラー: ' . $e->getMessage(),
            'code' => 'php_error',
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ));
    }
}
add_action('wp_ajax_gi_bulk_convert_slugs', 'gi_ajax_bulk_convert_slugs');

/**
 * AJAX: Nonce再取得
 */
function gi_ajax_refresh_slug_nonce() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => '権限がありません'));
        return;
    }
    
    wp_send_json_success(array(
        'nonce' => wp_create_nonce('gi_bulk_convert_nonce')
    ));
}
add_action('wp_ajax_gi_refresh_slug_nonce', 'gi_ajax_refresh_slug_nonce');

/**
 * =============================================================================
 * 6. 投稿一覧にスラッグ状態を表示
 * =============================================================================
 */

/**
 * 投稿一覧にスラッグ状態カラムを追加
 */
function gi_add_slug_status_column($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        // タイトルの後にスラッグ状態を追加
        if ($key === 'title') {
            $new_columns['slug_status'] = 'スラッグ状態';
        }
    }
    
    return $new_columns;
}
add_filter('manage_grant_posts_columns', 'gi_add_slug_status_column');

/**
 * スラッグ状態カラムの内容を表示
 */
function gi_display_slug_status_column($column, $post_id) {
    if ($column !== 'slug_status') {
        return;
    }
    
    $post = get_post($post_id);
    $slug = $post->post_name;
    $expected_slug = GI_SLUG_PREFIX . $post_id;
    
    if ($slug === $expected_slug) {
        echo '<span style="color: green;" title="' . esc_attr($slug) . '">✅ 最適化済み</span>';
    } elseif (gi_should_convert_slug($slug)) {
        echo '<span style="color: red;" title="' . esc_attr(urldecode($slug)) . '">⚠️ 要変換</span>';
        echo '<br><a href="#" class="gi-convert-single" data-post-id="' . $post_id . '" style="font-size: 11px;">変換する</a>';
    } else {
        echo '<span style="color: #666;" title="' . esc_attr($slug) . '">✓ カスタム</span>';
    }
}
add_action('manage_grant_posts_custom_column', 'gi_display_slug_status_column', 10, 2);

/**
 * 投稿一覧用のJavaScript
 */
function gi_admin_list_scripts($hook) {
    if ($hook !== 'edit.php') return;
    
    $screen = get_current_screen();
    if ($screen->post_type !== 'grant') return;
    
    ?>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '.gi-convert-single', function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var postId = $link.data('post-id');
            
            if (!confirm('この投稿のスラッグを変換しますか？')) {
                return;
            }
            
            $link.text('変換中...');
            
            $.post(ajaxurl, {
                action: 'gi_convert_single_slug',
                post_id: postId,
                _wpnonce: '<?php echo wp_create_nonce('gi_convert_single_nonce'); ?>'
            })
            .done(function(response) {
                if (response.success) {
                    $link.closest('td').html('<span style="color: green;">✅ 変換完了</span>');
                } else {
                    $link.text('エラー');
                    alert('変換に失敗しました: ' + response.data.message);
                }
            })
            .fail(function() {
                $link.text('エラー');
                alert('通信エラーが発生しました');
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'gi_admin_list_scripts');

/**
 * AJAX: 単一投稿の変換
 */
function gi_ajax_convert_single_slug() {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'gi_convert_single_nonce')) {
        wp_send_json_error(array('message' => 'セキュリティチェックに失敗しました'));
    }
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => '権限がありません'));
    }
    
    $post_id = intval($_POST['post_id']);
    $result = gi_convert_single_grant_slug($post_id);
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
add_action('wp_ajax_gi_convert_single_slug', 'gi_ajax_convert_single_slug');

/**
 * =============================================================================
 * 7. スラッグ状態カラムのスタイル
 * =============================================================================
 */

/**
 * 管理画面のスタイルを追加
 */
function gi_slug_optimizer_admin_styles() {
    $screen = get_current_screen();
    
    if ($screen && $screen->post_type === 'grant') {
        ?>
        <style>
            .column-slug_status {
                width: 100px;
            }
            .gi-convert-single {
                color: #d63384;
            }
            .gi-convert-single:hover {
                color: #a61b60;
            }
        </style>
        <?php
    }
}
add_action('admin_head', 'gi_slug_optimizer_admin_styles');

/**
 * =============================================================================
 * 8. 単一投稿変換関数（安全版）
 * =============================================================================
 */

/**
 * 単一の投稿を安全に変換（プレビュー付き）
 * 
 * @param int $post_id 投稿ID
 * @param bool $dry_run 実行せずにプレビューのみ
 * @return array 結果
 */
function gi_safe_convert_grant_slug($post_id, $dry_run = false) {
    $post = get_post($post_id);
    
    if (!$post || $post->post_type !== 'grant') {
        return array(
            'success' => false,
            'message' => '投稿が見つからないか、助成金投稿ではありません'
        );
    }
    
    $old_slug = $post->post_name;
    $new_slug = GI_SLUG_PREFIX . $post_id;
    
    $preview = array(
        'post_id' => $post_id,
        'title' => $post->post_title,
        'old_slug' => $old_slug,
        'old_url' => home_url('/grants/' . $old_slug . '/'),
        'new_slug' => $new_slug,
        'new_url' => home_url('/grants/' . $new_slug . '/'),
        'needs_conversion' => gi_should_convert_slug($old_slug)
    );
    
    if ($dry_run) {
        return array(
            'success' => true,
            'dry_run' => true,
            'preview' => $preview
        );
    }
    
    // 実際に変換を実行
    return gi_convert_single_grant_slug($post_id);
}

/**
 * =============================================================================
 * 9. WP-CLI コマンド（オプション）
 * =============================================================================
 */

if (defined('WP_CLI') && WP_CLI) {
    /**
     * WP-CLIコマンド: 助成金スラッグの変換
     */
    WP_CLI::add_command('gi slug convert', function($args, $assoc_args) {
        $batch_size = isset($assoc_args['batch']) ? intval($assoc_args['batch']) : 50;
        $dry_run = isset($assoc_args['dry-run']);
        
        if ($dry_run) {
            $posts = gi_get_grants_needing_slug_conversion($batch_size);
            WP_CLI::log("プレビュー（変換は実行されません）:");
            
            foreach ($posts as $post) {
                WP_CLI::log(sprintf(
                    "ID: %d | %s -> %s",
                    $post->ID,
                    $post->post_name,
                    GI_SLUG_PREFIX . $post->ID
                ));
            }
            
            WP_CLI::success("変換対象: " . count($posts) . "件");
        } else {
            $results = gi_bulk_convert_grant_slugs($batch_size);
            
            WP_CLI::success(sprintf(
                "処理完了: %d件処理（成功: %d, スキップ: %d, 失敗: %d, 残り: %d）",
                $results['processed'],
                $results['success'],
                $results['skipped'],
                $results['failed'],
                $results['remaining']
            ));
        }
    });
    
    WP_CLI::add_command('gi slug status', function() {
        $total = wp_count_posts('grant')->publish;
        $needs = gi_count_grants_needing_conversion();
        $redirects = count(get_option(GI_SLUG_REDIRECT_MAP_OPTION, array()));
        
        WP_CLI::log("=== 助成金スラッグ状態 ===");
        WP_CLI::log("公開済み投稿: " . $total . "件");
        WP_CLI::log("変換済み: " . ($total - $needs) . "件");
        WP_CLI::log("要変換: " . $needs . "件");
        WP_CLI::log("リダイレクト登録: " . $redirects . "件");
    });
}
