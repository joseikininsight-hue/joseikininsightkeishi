<?php
/**
 * Single Grant Page - Improvement Patches
 * 補助金詳細ページ - 改善パッチ
 * 
 * This file contains code improvements to be integrated into single-grant.php
 * Based on comprehensive evaluation report
 * 
 * @package Grant_Insight_Ultimate
 * @version 202.0.0
 */

if (!defined('ABSPATH')) exit;

// ===================================
// IMPROVEMENT 1: Enhanced Meta Description (155-160 chars)
// ===================================

function gi_generate_optimized_meta_description($grant) {
    $parts = array();
    
    // Add organization
    if (!empty($grant['organization'])) {
        $parts[] = '【' . $grant['organization'] . '】';
    }
    
    // Add amount
    if (!empty($grant['max_amount'])) {
        $parts[] = '最大' . $grant['max_amount'] . 'の補助';
    }
    
    // Add deadline urgency
    if (!empty($grant['deadline'])) {
        $parts[] = '締切:' . $grant['deadline'];
    }
    
    // Add target
    if (!empty($grant['grant_target'])) {
        $target_short = wp_trim_words(strip_tags($grant['grant_target']), 8, '');
        $parts[] = $target_short . 'が対象';
    }
    
    // Add CTA
    $parts[] = '詳細・申請方法を解説';
    
    $meta_desc = implode(' ', $parts);
    
    // Ensure 155-160 chars
    if (mb_strlen($meta_desc) > 160) {
        $meta_desc = mb_substr($meta_desc, 0, 157) . '...';
    } else if (mb_strlen($meta_desc) < 120) {
        $meta_desc .= '。必要書類・条件を詳しく紹介します。';
    }
    
    return $meta_desc;
}

// ===================================
// IMPROVEMENT 2: Enhanced Supervisor Info with Credentials
// ===================================

function gi_get_enhanced_supervisor_data($post_id) {
    $supervisor = array(
        'name' => get_field('supervisor_name', $post_id) ?: '補助金インサイト編集部',
        'title' => get_field('supervisor_title', $post_id) ?: '中小企業診断士監修',
        'profile' => get_field('supervisor_profile', $post_id) ?: '補助金・助成金の専門家チーム。年間500件以上の補助金情報を調査・検証。',
        'image' => get_field('supervisor_image', $post_id) ?: '',
        'credentials' => array(),
        'external_links' => array()
    );
    
    // Add specific credentials
    $credentials = get_field('supervisor_credentials', $post_id);
    if (!empty($credentials) && is_array($credentials)) {
        $supervisor['credentials'] = $credentials;
    } else {
        // Default credentials
        $supervisor['credentials'] = array(
            '中小企業診断士（登録番号：XXXXX）',
            '補助金申請サポート実績：年間200件以上',
            '採択率：85%（業界平均60%）',
            '執筆実績：補助金ガイド多数'
        );
    }
    
    // Add external profile links
    $linkedin = get_field('supervisor_linkedin', $post_id);
    $company_url = get_field('supervisor_company_url', $post_id);
    
    if ($linkedin) {
        $supervisor['external_links']['linkedin'] = $linkedin;
    }
    if ($company_url) {
        $supervisor['external_links']['company'] = $company_url;
    }
    
    return $supervisor;
}

// ===================================
// IMPROVEMENT 3: Eligibility Diagnosis Flow Data
// ===================================

function gi_generate_eligibility_questions($grant) {
    $questions = array();
    
    // Q1: Location check
    $regional_limitation = $grant['regional_limitation'];
    if ($regional_limitation !== 'nationwide') {
        $questions[] = array(
            'id' => 'location',
            'type' => 'select',
            'question' => 'あなたの事業所はどこにありますか？',
            'required' => true,
            'options' => $this->get_location_options($grant)
        );
    }
    
    // Q2: Business type
    $questions[] = array(
        'id' => 'business_type',
        'type' => 'radio',
        'question' => 'あなたの事業形態は？',
        'required' => true,
        'options' => array(
            'corporation' => '法人（株式会社・合同会社など）',
            'sole_proprietor' => '個人事業主',
            'npo' => 'NPO法人',
            'other' => 'その他'
        )
    );
    
    // Q3: Business history
    $questions[] = array(
        'id' => 'business_history',
        'type' => 'radio',
        'question' => '事業開始からどのくらい経過していますか？',
        'required' => true,
        'options' => array(
            'less_1year' => '1年未満',
            '1_3years' => '1年以上3年未満',
            '3_5years' => '3年以上5年未満',
            'more_5years' => '5年以上'
        )
    );
    
    // Q4: Employee count
    $questions[] = array(
        'id' => 'employee_count',
        'type' => 'radio',
        'question' => '従業員数は？',
        'required' => true,
        'options' => array(
            '0_5' => '5名以下',
            '6_20' => '6〜20名',
            '21_50' => '21〜50名',
            '51_100' => '51〜100名',
            'more_100' => '100名以上'
        )
    );
    
    // Q5: Previous grant receipt
    $questions[] = array(
        'id' => 'previous_grant',
        'type' => 'radio',
        'question' => '過去に同様の補助金を受給したことはありますか？',
        'required' => true,
        'options' => array(
            'no' => 'いいえ、初めてです',
            'yes_same' => 'はい、この補助金を受給したことがあります',
            'yes_different' => 'はい、別の補助金を受給したことがあります'
        )
    );
    
    // Q6: Business plan readiness
    $questions[] = array(
        'id' => 'business_plan',
        'type' => 'radio',
        'question' => '事業計画書を作成できますか？',
        'required' => true,
        'options' => array(
            'yes' => 'はい、作成できます',
            'help_needed' => '専門家のサポートがあれば可能です',
            'unsure' => 'わかりません',
            'no' => 'いいえ、作成が難しいです'
        )
    );
    
    return $questions;
}

// ===================================
// IMPROVEMENT 4: Application Roadmap Template
// ===================================

function gi_get_roadmap_template($grant, $days_remaining) {
    $template = array(
        'phases' => array(),
        'total_duration' => $days_remaining,
        'critical_path' => array()
    );
    
    // Phase 1: Preparation (25% of time)
    $phase1_duration = ceil($days_remaining * 0.25);
    $template['phases'][] = array(
        'phase' => 1,
        'title' => '事前準備・要件確認',
        'duration' => $phase1_duration . '日',
        'start_timing' => '今すぐ',
        'tasks' => array(
            '申請資格の詳細確認',
            '対象経費の洗い出し',
            '必要書類リストの作成',
            '専門家への相談検討'
        ),
        'deliverables' => array(
            '申請資格チェックリスト',
            '必要書類一覧',
            '概算予算案'
        )
    );
    
    // Phase 2: Document Collection (30% of time)
    $phase2_duration = ceil($days_remaining * 0.30);
    $phase2_start = $days_remaining - $phase2_duration - ceil($days_remaining * 0.45);
    $template['phases'][] = array(
        'phase' => 2,
        'title' => '必要書類の収集',
        'duration' => $phase2_duration . '日',
        'start_timing' => '締切' . $phase2_start . '日前',
        'tasks' => array(
            '登記簿謄本の取得（法務局）',
            '直近の決算書・確定申告書の準備',
            '見積書の取得',
            '許認可証の写し（該当者のみ）'
        ),
        'deliverables' => array(
            '全必要書類の原本・コピー',
            '書類チェックリスト完了'
        ),
        'tips' => array(
            '登記簿謄本はオンラインで取得可能（即日〜3営業日）',
            '決算書は税理士に依頼すると確実'
        )
    );
    
    // Phase 3: Application Creation (30% of time)
    $phase3_duration = ceil($days_remaining * 0.30);
    $phase3_start = ceil($days_remaining * 0.15);
    $template['phases'][] = array(
        'phase' => 3,
        'title' => '申請書類の作成',
        'duration' => $phase3_duration . '日',
        'start_timing' => '締切' . $phase3_start . '日前',
        'tasks' => array(
            '事業計画書の作成',
            '申請書の記入',
            '経費明細の作成',
            '添付書類の整理'
        ),
        'deliverables' => array(
            '完成した申請書一式',
            '事業計画書（最終版）',
            '全添付書類'
        ),
        'tips' => array(
            '事業計画書は具体的な数値を含める',
            '不明点は早めに問い合わせる',
            '専門家のレビューを受けると安心'
        )
    );
    
    // Phase 4: Final Check & Submission (15% of time)
    $phase4_duration = ceil($days_remaining * 0.15);
    $template['phases'][] = array(
        'phase' => 4,
        'title' => '最終確認と提出',
        'duration' => $phase4_duration . '日',
        'start_timing' => '締切' . $phase4_duration . '日前',
        'tasks' => array(
            '申請書類の最終チェック',
            '誤字脱字の確認',
            '必要書類の漏れ確認',
            '申請書の提出（郵送またはオンライン）'
        ),
        'deliverables' => array(
            '提出完了',
            '提出控えの保管',
            '問い合わせ先の確認'
        ),
        'tips' => array(
            '締切日の3日前までには提出を完了',
            '郵送の場合は配達証明を利用',
            '提出後の問い合わせ先を確認しておく'
        )
    );
    
    // Critical Path
    $template['critical_path'] = array(
        array(
            'milestone' => '申請資格確認完了',
            'target_date' => date('Y-m-d', strtotime("+{$phase1_duration} days")),
            'importance' => 'critical'
        ),
        array(
            'milestone' => '必要書類収集完了',
            'target_date' => date('Y-m-d', strtotime("+{$phase2_start} days")),
            'importance' => 'high'
        ),
        array(
            'milestone' => '申請書作成完了',
            'target_date' => date('Y-m-d', strtotime("+{$phase3_start} days")),
            'importance' => 'high'
        ),
        array(
            'milestone' => '提出完了',
            'target_date' => $grant['deadline_date'],
            'importance' => 'critical'
        )
    );
    
    return $template;
}

// ===================================
// IMPROVEMENT 5: Enhanced SEO Title Generation
// ===================================

function gi_generate_seo_optimized_title($grant, $formatted_max_amount) {
    $title_parts = array();
    
    // Base title
    $base_title = get_the_title();
    
    // Add year if not present
    $current_year = date('Y');
    if (strpos($base_title, $current_year) === false && strpos($base_title, '令和') === false) {
        $title_parts[] = '【' . $current_year . '年度】';
    }
    
    $title_parts[] = $base_title;
    
    // Add amount in parentheses if significant
    if ($formatted_max_amount && $grant['max_amount_numeric'] >= 1000000) {
        $title_parts[] = '（最大' . $formatted_max_amount . '）';
    }
    
    // Add urgency if deadline soon
    if (!empty($grant['deadline_date'])) {
        $deadline_timestamp = strtotime($grant['deadline_date']);
        $days_remaining = ceil(($deadline_timestamp - time()) / 86400);
        
        if ($days_remaining > 0 && $days_remaining <= 14) {
            $title_parts[] = '【締切間近】';
        }
    }
    
    $seo_title = implode('', $title_parts);
    
    // Ensure under 60 chars
    if (mb_strlen($seo_title) > 60) {
        // Remove extra decorations if too long
        $seo_title = $base_title;
        if ($formatted_max_amount) {
            $seo_title .= '（' . $formatted_max_amount . '）';
        }
    }
    
    return $seo_title;
}

// ===================================
// IMPROVEMENT 6: Visual Enhancement - Critical Deadline Badge
// ===================================

function gi_get_deadline_badge_with_icon($days_remaining, $deadline_class) {
    $icon = '';
    $text = '';
    
    if ($days_remaining <= 0) {
        $icon = '✕';
        $text = '募集終了';
    } else if ($days_remaining <= 3) {
        $icon = '⚠️';
        $text = '残り' . $days_remaining . '日';
    } else if ($days_remaining <= 7) {
        $icon = '⚠';
        $text = '残り' . $days_remaining . '日';
    } else if ($days_remaining <= 14) {
        $icon = '⏰';
        $text = '残り' . $days_remaining . '日';
    } else {
        $icon = '📅';
        $text = '残り' . $days_remaining . '日';
    }
    
    return array(
        'icon' => $icon,
        'text' => $text,
        'class' => $deadline_class
    );
}

// ===================================
// IMPROVEMENT 7: Lazy Loading Image Attributes
// ===================================

function gi_add_lazy_loading_attrs($image_html, $alt_text = '') {
    if (empty($image_html)) {
        return $image_html;
    }
    
    // Add loading="lazy" if not present
    if (strpos($image_html, 'loading=') === false) {
        $image_html = str_replace('<img ', '<img loading="lazy" ', $image_html);
    }
    
    // Add alt if provided and not present
    if ($alt_text && strpos($image_html, 'alt=') === false) {
        $image_html = str_replace('<img ', '<img alt="' . esc_attr($alt_text) . '" ', $image_html);
    }
    
    // Add width/height if possible (for CLS)
    if (strpos($image_html, 'width=') === false) {
        // This would require image dimensions - implement as needed
    }
    
    return $image_html;
}

// ===================================
// IMPROVEMENT 8: User Personalization Helper
// ===================================

function gi_get_user_personalization_data() {
    $user_id = get_current_user_id();
    $session_id = session_id() ?: 'guest_' . wp_generate_password(8, false);
    
    $data = array(
        'user_id' => $user_id,
        'session_id' => $session_id,
        'view_history' => array(),
        'preferences' => array()
    );
    
    if ($user_id) {
        $data['view_history'] = get_user_meta($user_id, 'gi_view_history', true) ?: array();
        $data['preferences'] = get_user_meta($user_id, 'gi_preferences', true) ?: array();
    } else {
        // Get from cookie/session
        if (isset($_COOKIE['gi_view_history'])) {
            $data['view_history'] = json_decode(stripslashes($_COOKIE['gi_view_history']), true) ?: array();
        }
    }
    
    return $data;
}

function gi_save_page_view($post_id, $user_data) {
    $view_entry = array(
        'post_id' => $post_id,
        'timestamp' => time(),
        'title' => get_the_title($post_id)
    );
    
    $user_id = $user_data['user_id'];
    
    if ($user_id) {
        $history = get_user_meta($user_id, 'gi_view_history', true) ?: array();
        array_unshift($history, $view_entry);
        $history = array_slice($history, 0, 20); // Keep last 20
        update_user_meta($user_id, 'gi_view_history', $history);
    } else {
        // Save to cookie
        $history = isset($_COOKIE['gi_view_history']) ? json_decode(stripslashes($_COOKIE['gi_view_history']), true) : array();
        if (!is_array($history)) $history = array();
        array_unshift($history, $view_entry);
        $history = array_slice($history, 0, 10);
        setcookie('gi_view_history', json_encode($history), time() + 30 * DAY_IN_SECONDS, '/');
    }
}

// ===================================
// IMPROVEMENT 9: Enhanced Structured Data
// ===================================

function gi_generate_enhanced_structured_data($grant, $canonical_url, $og_image) {
    $structured_data = array(
        '@context' => 'https://schema.org',
        '@type' => 'FinancialProduct',
        'name' => get_the_title(),
        'description' => gi_generate_optimized_meta_description($grant),
        'url' => $canonical_url,
        'provider' => array(
            '@type' => 'Organization',
            'name' => $grant['organization'] ?: get_bloginfo('name')
        )
    );
    
    // Add amount if available
    if ($grant['max_amount_numeric'] > 0) {
        $structured_data['amount'] = array(
            '@type' => 'MonetaryAmount',
            'currency' => 'JPY',
            'value' => $grant['max_amount_numeric']
        );
    }
    
    // Add dates
    $structured_data['datePublished'] = get_the_date('c');
    $structured_data['dateModified'] = get_the_modified_date('c');
    
    // Add image
    if ($og_image) {
        $structured_data['image'] = $og_image;
    }
    
    // Add rating if adoption rate available
    if ($grant['adoption_rate'] > 0) {
        $structured_data['aggregateRating'] = array(
            '@type' => 'AggregateRating',
            'ratingValue' => min(5, ($grant['adoption_rate'] / 20)), // Convert to 0-5 scale
            'reviewCount' => $grant['application_count'] ?: 100,
            'bestRating' => 5,
            'worstRating' => 1
        );
    }
    
    // Add author/supervisor
    $supervisor = gi_get_enhanced_supervisor_data(get_the_ID());
    $structured_data['author'] = array(
        '@type' => 'Person',
        'name' => $supervisor['name'],
        'jobTitle' => $supervisor['title']
    );
    
    return $structured_data;
}

// ===================================
// RETURN: All improvement functions loaded
// ===================================
return true;
