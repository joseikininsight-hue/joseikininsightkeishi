<?php
/**
 * JOSEIKIN INSIGHT - Perfect Header
 * SEO Cleaned版 - Yoast SEO完全対応
 * ホバー廃止・クリック式メニュー
 * 
 * @package Joseikin_Insight_Header
 * @version 10.0.0 (Click Menu Edition)
 */

if (!defined('ABSPATH')) {
    exit;
}

// ヘッダー用データ取得
if (!function_exists('ji_get_header_data')) {
    function ji_get_header_data() {
        $cached = wp_cache_get('ji_header_data', 'joseikin');
        if ($cached !== false) return $cached;
        
        $data = [
            'total_grants' => wp_count_posts('grant')->publish ?? 0,
            'active_grants' => 0,
            'last_updated' => get_option('ji_last_data_update', current_time('Y-m-d')),
            'categories' => [],
            'prefectures' => [],
            'popular_searches' => ['IT導入補助金', '小規模事業者持続化補助金', 'ものづくり補助金']
        ];
        
        $active_query = new WP_Query([
            'post_type' => 'grant',
            'post_status' => 'publish',
            'meta_query' => [['key' => 'grant_status', 'value' => 'active']],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true
        ]);
        $data['active_grants'] = $active_query->found_posts;
        wp_reset_postdata();
        
        $data['categories'] = get_terms(['taxonomy' => 'grant_category', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 10]);
        $data['prefectures'] = get_terms(['taxonomy' => 'grant_prefecture', 'hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC']);
        
        wp_cache_set('ji_header_data', $data, 'joseikin', 3600);
        return $data;
    }
}

$header_data = ji_get_header_data();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <meta name="format-detection" content="telephone=no, email=no, address=no">
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/solid.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/brands.min.css">
    
    <?php wp_head(); ?>
    
    <style>
        /* ===============================================
           RESET & BASE
           =============================================== */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; -webkit-text-size-adjust: 100%; }
        
        body {
            font-family: 'Noto Sans JP', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #1a1a1a;
            background: #fff;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        img { max-width: 100%; height: auto; display: block; }
        a { color: inherit; text-decoration: none; }
        button { font-family: inherit; cursor: pointer; border: none; background: none; }
        
        /* ===============================================
           CSS VARIABLES
           =============================================== */
        :root {
            --black: #000;
            --white: #fff;
            --gray-50: #fafafa;
            --gray-100: #f5f5f5;
            --gray-200: #e5e5e5;
            --gray-300: #d4d4d4;
            --gray-400: #a3a3a3;
            --gray-500: #737373;
            --gray-600: #525252;
            --gray-700: #404040;
            --gray-800: #262626;
            --gray-900: #171717;
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --header-height: 64px;
            --max-width: 1280px;
            --transition: 200ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* ===============================================
           SKIP LINK
           =============================================== */
        .ji-skip-link {
            position: absolute;
            top: -100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--black);
            color: var(--white);
            padding: 12px 24px;
            border-radius: 0 0 8px 8px;
            z-index: 100000;
            font-weight: 600;
        }
        .ji-skip-link:focus { top: 0; }
        
        /* ===============================================
           HEADER PLACEHOLDER
           =============================================== */
        .ji-header-placeholder { height: var(--header-height); }
        @media (min-width: 768px) { .ji-header-placeholder { height: 72px; } }
        
        /* ===============================================
           MAIN HEADER
           =============================================== */
        .ji-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            background: var(--black);
            height: var(--header-height);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        @media (min-width: 768px) {
            .ji-header { height: 72px; }
        }
        
        .ji-header.scrolled {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        }
        
        .ji-header.hidden {
            transform: translateY(-100%);
        }
        
        .ji-header-inner {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 0 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
        }
        
        @media (min-width: 768px) {
            .ji-header-inner { padding: 0 24px; }
        }
        
        /* ===============================================
           LOGO
           =============================================== */
        .ji-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            padding: 8px 12px;
            margin: -8px -12px;
            border-radius: 12px;
            transition: all var(--transition);
            text-decoration: none;
        }
        
        .ji-logo:hover {
            background: rgba(255, 255, 255, 0.08);
        }
        
        .ji-logo:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        
        .ji-logo-image-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .ji-logo-image {
            height: 32px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
            display: block;
        }
        
        @media (max-width: 767px) {
            .ji-logo-image {
                height: 28px;
                max-width: 160px;
            }
        }
        
        @media (min-width: 768px) {
            .ji-logo-image {
                height: 32px;
                max-width: 200px;
            }
        }
        
        @media (min-width: 1024px) {
            .ji-logo-image {
                height: 32px;
                max-width: 200px;
            }
        }
        
        /* ===============================================
           DESKTOP NAVIGATION
           =============================================== */
        .ji-nav {
            display: none;
            align-items: center;
            gap: 2px;
            margin: 0 24px;
            flex: 1;
            justify-content: center;
            height: 100%;
        }
        
        @media (min-width: 1024px) { .ji-nav { display: flex; } }
        
        .ji-nav-item {
            position: static;
            height: 100%;
            display: flex;
            align-items: center;
        }
        
        .ji-nav-link {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 14px 18px;
            color: var(--white);
            font-size: 0.9375rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all var(--transition);
            white-space: nowrap;
            cursor: pointer;
            position: relative;
            min-height: 48px;
            text-decoration: none;
        }
        
        .ji-nav-link:hover,
        .ji-nav-link:focus-visible {
            background: rgba(255, 255, 255, 0.15);
            color: var(--white);
        }
        
        .ji-nav-link:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        
        .ji-nav-link[aria-current="page"] {
            background: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }
        
        .ji-nav-link .ji-icon {
            font-size: 0.875rem;
            opacity: 0.8;
            color: var(--white);
        }
        
        .ji-nav-link .ji-chevron {
            font-size: 0.625rem;
            margin-left: 4px;
            transition: transform var(--transition);
            color: var(--white);
        }
        
        .ji-nav-item.menu-active .ji-chevron {
            transform: rotate(180deg);
        }
        
        /* ===============================================
           MEGA MENU
           =============================================== */
        .ji-mega-menu {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            width: 100vw;
            background: var(--black);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 32px 0 40px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-4px);
            transition: opacity 0.2s ease, visibility 0.2s ease, transform 0.2s ease;
            pointer-events: none;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
            z-index: 9998;
        }
        
        .ji-nav-item.menu-active .ji-mega-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
        }
        
        .ji-mega-menu-inner {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 0 24px;
        }
        
        .ji-mega-menu-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .ji-mega-menu-title {
            color: var(--white);
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .ji-mega-menu-title i { color: var(--primary-light); }
        
        .ji-mega-menu-stats { display: flex; gap: 32px; }
        
        .ji-mega-stat { text-align: center; }
        .ji-mega-stat-value {
            color: var(--white);
            font-size: 1.5rem;
            font-weight: 800;
            display: block;
            letter-spacing: -0.02em;
        }
        .ji-mega-stat-label {
            color: var(--gray-400);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .ji-mega-menu-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
        }
        
        .ji-mega-menu-grid.cols-1 {
            grid-template-columns: 1fr;
            max-width: 400px;
        }
        
        .ji-mega-menu-grid.cols-2 {
            grid-template-columns: repeat(2, 1fr);
            max-width: 600px;
        }
        
        .ji-mega-menu-grid.cols-3 {
            grid-template-columns: repeat(3, 1fr);
            max-width: 900px;
        }
        
        .ji-mega-column { display: flex; flex-direction: column; }
        
        .ji-mega-column-title {
            color: var(--gray-400);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .ji-mega-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.9375rem;
            font-weight: 500;
            padding: 12px 14px;
            margin: 2px -14px;
            border-radius: 8px;
            transition: all var(--transition);
            min-height: 48px;
            position: relative;
            text-decoration: none;
        }
        
        .ji-mega-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 0;
            background: var(--primary-light);
            border-radius: 2px;
            transition: height var(--transition);
        }
        
        .ji-mega-link:hover,
        .ji-mega-link:focus-visible {
            color: var(--white);
            background: rgba(255, 255, 255, 0.1);
            padding-left: 22px;
        }
        
        .ji-mega-link:hover::before,
        .ji-mega-link:focus-visible::before {
            height: 24px;
        }
        
        .ji-mega-link:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        
        .ji-mega-link .ji-badge {
            background: var(--danger);
            color: var(--white);
            font-size: 0.625rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 9999px;
            margin-left: auto;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        
        .ji-mega-link .ji-badge.new { background: var(--success); }
        
        .ji-prefecture-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4px 8px;
            max-height: 320px;
            overflow-y: auto;
            padding-right: 8px;
            margin: 0 -8px;
        }
        
        .ji-prefecture-grid::-webkit-scrollbar { width: 6px; }
        .ji-prefecture-grid::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 9999px;
        }
        .ji-prefecture-grid::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 9999px;
        }
        .ji-prefecture-grid::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .ji-prefecture-link {
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.8125rem;
            font-weight: 500;
            padding: 10px 12px;
            border-radius: 6px;
            transition: all var(--transition);
            text-align: center;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        
        .ji-prefecture-link:hover,
        .ji-prefecture-link:focus-visible {
            color: var(--white);
            background: rgba(255, 255, 255, 0.12);
        }
        
        .ji-prefecture-link:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 1px;
        }
        
        /* ===============================================
           HEADER ACTIONS
           =============================================== */
        .ji-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        
        @media (min-width: 768px) { .ji-actions { gap: 12px; } }
        
        .ji-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all var(--transition);
            white-space: nowrap;
            min-height: 44px;
            text-decoration: none;
        }
        
        .ji-btn:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        
        .ji-btn-icon {
            width: 44px;
            height: 44px;
            padding: 0;
            color: var(--white);
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        
        .ji-btn-icon:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
        }
        
        .ji-btn-primary {
            background: var(--white);
            color: var(--black);
            display: none;
        }
        
        @media (min-width: 768px) { .ji-btn-primary { display: inline-flex; } }
        
        .ji-btn-primary:hover {
            background: var(--gray-100);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
        }
        
        .ji-mobile-toggle {
            display: flex;
            width: 44px;
            height: 44px;
            color: var(--white);
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 8px;
            align-items: center;
            justify-content: center;
        }
        
        @media (min-width: 1024px) { .ji-mobile-toggle { display: none; } }
        
        .ji-mobile-toggle:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.4);
        }
        
        .ji-mobile-toggle:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        
        .ji-hamburger {
            width: 20px;
            height: 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .ji-hamburger-line {
            width: 100%;
            height: 2px;
            background: currentColor;
            border-radius: 9999px;
            transition: all var(--transition);
            transform-origin: center;
        }
        
        .ji-mobile-toggle[aria-expanded="true"] .ji-hamburger-line:nth-child(1) {
            transform: translateY(6px) rotate(45deg);
        }
        .ji-mobile-toggle[aria-expanded="true"] .ji-hamburger-line:nth-child(2) {
            opacity: 0;
            transform: scaleX(0);
        }
        .ji-mobile-toggle[aria-expanded="true"] .ji-hamburger-line:nth-child(3) {
            transform: translateY(-6px) rotate(-45deg);
        }
        
        /* ===============================================
           SEARCH PANEL
           =============================================== */
        .ji-search-panel {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--white);
            border-top: 1px solid var(--gray-200);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
            padding: 28px 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.25s ease;
            pointer-events: none;
            z-index: 9997;
        }
        
        .ji-search-panel.open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
        }
        
        .ji-search-panel-inner {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 0 24px;
        }
        
        .ji-search-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        @media (min-width: 768px) {
            .ji-search-form { flex-direction: row; align-items: flex-start; }
        }
        
        .ji-search-main { flex: 1; }
        
        .ji-search-input-wrapper { position: relative; }
        
        .ji-search-input {
            width: 100%;
            padding: 16px 20px 16px 52px;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            color: var(--gray-900);
            background: var(--white);
            transition: all var(--transition);
            min-height: 56px;
        }
        
        .ji-search-input:hover {
            border-color: var(--gray-300);
        }
        
        .ji-search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            color: var(--gray-900);
        }
        
        .ji-search-input::placeholder { color: var(--gray-400); }
        
        .ji-search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-size: 1.125rem;
            pointer-events: none;
        }
        
        .ji-search-clear {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            padding: 8px;
            opacity: 0;
            visibility: hidden;
            transition: all var(--transition);
            border-radius: 6px;
        }
        
        .ji-search-input:not(:placeholder-shown) ~ .ji-search-clear {
            opacity: 1;
            visibility: visible;
        }
        
        .ji-search-clear:hover {
            color: var(--gray-600);
            background: var(--gray-100);
        }
        
        .ji-search-suggestions {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        
        .ji-search-suggestion-label {
            color: var(--gray-600);
            font-size: 0.8125rem;
            font-weight: 600;
        }
        
        .ji-search-suggestion {
            background: var(--gray-100);
            color: var(--gray-700);
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 0.8125rem;
            font-weight: 600;
            transition: all var(--transition);
            cursor: pointer;
            min-height: 36px;
            border: none;
        }
        
        .ji-search-suggestion:hover {
            background: var(--gray-200);
            color: var(--gray-900);
        }
        
        .ji-search-suggestion:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        
        .ji-search-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .ji-search-select {
            padding: 14px 36px 14px 16px;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-700);
            background: var(--white) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23737373' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 14px center;
            appearance: none;
            min-width: 160px;
            cursor: pointer;
            transition: all var(--transition);
            min-height: 52px;
        }
        
        .ji-search-select:hover { border-color: var(--gray-300); }
        .ji-search-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            color: var(--gray-700);
        }
        
        .ji-search-select option {
            color: var(--gray-700);
            background: var(--white);
        }
        
        .ji-search-submit {
            background: var(--black);
            color: var(--white);
            padding: 14px 28px;
            border-radius: 12px;
            font-size: 0.9375rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all var(--transition);
            min-height: 52px;
            border: none;
        }
        
        .ji-search-submit:hover {
            background: var(--gray-800);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            color: var(--white);
        }
        
        .ji-search-submit:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        
        /* ===============================================
           MOBILE MENU
           =============================================== */
        .ji-mobile-menu {
            position: fixed;
            inset: 0;
            background: var(--black);
            z-index: 99999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .ji-mobile-menu.open { opacity: 1; visibility: visible; }
        
        .ji-mobile-menu-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            background: var(--black);
            z-index: 10;
        }
        
        .ji-mobile-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .ji-mobile-logo-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .ji-mobile-logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .ji-mobile-logo-text {
            color: var(--white);
            font-size: 1rem;
            font-weight: 700;
        }
        
        .ji-mobile-close {
            width: 44px;
            height: 44px;
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            border-radius: 8px;
            transition: all var(--transition);
        }
        
        .ji-mobile-close:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .ji-mobile-close:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        
        .ji-mobile-search {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .ji-mobile-search-wrapper { position: relative; }
        
        .ji-mobile-search-input {
            width: 100%;
            padding: 14px 18px 14px 48px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: var(--white);
            font-size: 1rem;
            font-weight: 500;
            min-height: 52px;
            transition: all var(--transition);
        }
        
        .ji-mobile-search-input::placeholder { color: rgba(255, 255, 255, 0.5); }
        
        .ji-mobile-search-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary);
            color: var(--white);
        }
        
        .ji-mobile-search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            font-size: 1rem;
            pointer-events: none;
        }
        
        .ji-mobile-content { padding: 20px; }
        
        .ji-mobile-section { margin-bottom: 24px; }
        
        .ji-mobile-accordion {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .ji-mobile-accordion-trigger {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 0;
            color: var(--white);
            font-size: 1.0625rem;
            font-weight: 600;
            text-align: left;
            min-height: 60px;
            transition: all var(--transition);
            background: transparent;
            border: none;
        }
        
        .ji-mobile-accordion-trigger:hover {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .ji-mobile-accordion-trigger:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: -2px;
            border-radius: 8px;
        }
        
        .ji-mobile-accordion-trigger i {
            color: var(--white);
            font-size: 0.75rem;
            transition: transform var(--transition);
        }
        
        .ji-mobile-accordion-trigger[aria-expanded="true"] i {
            transform: rotate(180deg);
        }
        
        .ji-mobile-accordion-content {
            display: none;
            padding-bottom: 16px;
        }
        
        .ji-mobile-accordion-content.open {
            display: block;
            animation: slideDown 0.25s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .ji-mobile-link {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.85);
            font-size: 1rem;
            font-weight: 500;
            padding: 14px 16px;
            margin: 0 -16px;
            border-radius: 8px;
            transition: all var(--transition);
            min-height: 52px;
            text-decoration: none;
        }
        
        .ji-mobile-link:hover {
            color: var(--white);
            background: rgba(255, 255, 255, 0.08);
            padding-left: 24px;
        }
        
        .ji-mobile-link:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: -2px;
        }
        
        /* 単独リンク（ドロップダウンなし） */
        .ji-mobile-single-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 0;
            color: var(--white);
            font-size: 1.0625rem;
            font-weight: 600;
            text-align: left;
            min-height: 60px;
            transition: all var(--transition);
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .ji-mobile-single-link:hover {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .ji-mobile-single-link:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: -2px;
            border-radius: 8px;
        }
        
        .ji-mobile-single-link i {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.75rem;
        }
        
        .ji-mobile-cta {
            background: var(--white);
            color: var(--black);
            padding: 18px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1rem;
            margin-top: 24px;
            transition: all var(--transition);
            min-height: 60px;
            text-decoration: none;
        }
        
        .ji-mobile-cta:hover {
            background: var(--gray-100);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 255, 255, 0.15);
            color: var(--black);
        }
        
        .ji-mobile-cta:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        
        .ji-mobile-cta i { color: var(--black); }
        
        .ji-mobile-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            padding: 28px 0;
            margin-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .ji-mobile-stat { text-align: center; }
        .ji-mobile-stat-value {
            color: var(--white);
            font-size: 1.75rem;
            font-weight: 800;
            display: block;
            letter-spacing: -0.02em;
        }
        .ji-mobile-stat-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .ji-mobile-footer {
            padding: 28px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .ji-mobile-social {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .ji-mobile-social-link {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
            transition: all var(--transition);
            text-decoration: none;
        }
        
        .ji-mobile-social-link:hover {
            background: var(--white);
            color: var(--black);
            transform: translateY(-2px);
        }
        
        .ji-mobile-social-link:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        
        .ji-mobile-trust {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .ji-mobile-trust-badge {
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .ji-mobile-trust-badge i { color: var(--success); }
        
        .ji-mobile-copyright {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.8125rem;
        }
        
        /* ===============================================
           ACCESSIBILITY & UTILITIES
           =============================================== */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        body.menu-open {
            overflow: hidden;
            position: fixed;
            width: 100%;
            height: 100%;
        }
        
        :focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a href="#main-content" class="ji-skip-link">メインコンテンツへスキップ</a>

<!-- Main Header -->
<header id="ji-header" class="ji-header" role="banner">
    <div class="ji-header-inner">
        <!-- Logo -->
        <a href="<?php echo esc_url(home_url('/')); ?>" class="ji-logo" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?> - ホームへ">
            <div class="ji-logo-image-wrapper">
                <img 
                    src="https://joseikin-insight.com/wp-content/uploads/2025/05/cropped-logo3.webp" 
                    alt="<?php echo esc_attr(get_bloginfo('name')); ?>" 
                    class="ji-logo-image"
                    width="240"
                    height="40"
                    loading="eager"
                    fetchpriority="high"
                    decoding="async"
                >
            </div>
        </a>
        
        <!-- Desktop Navigation -->
        <nav class="ji-nav" role="navigation" aria-label="メインナビゲーション">
            <?php
            $grants_url = get_post_type_archive_link('grant');
            $is_grants_page = is_post_type_archive('grant') || is_singular('grant') || is_tax('grant_category') || is_tax('grant_prefecture');
            ?>
            
            <!-- サービス一覧 -->
            <div class="ji-nav-item" data-menu="services">
                <button type="button" 
                   class="ji-nav-link" 
                   aria-haspopup="true"
                   aria-expanded="false">
                    <i class="fas fa-list-ul ji-icon" aria-hidden="true"></i>
                    <span>サービス一覧</span>
                    <i class="fas fa-chevron-down ji-chevron" aria-hidden="true"></i>
                </button>
                
                <div class="ji-mega-menu" role="menu" aria-label="サービス一覧メニュー">
                    <div class="ji-mega-menu-inner">
                        <div class="ji-mega-menu-header">
                            <div class="ji-mega-menu-title">
                                <i class="fas fa-coins" aria-hidden="true"></i>
                                補助金・助成金を探す
                            </div>
                            <div class="ji-mega-menu-stats">
                                <div class="ji-mega-stat">
                                    <span class="ji-mega-stat-value"><?php echo number_format($header_data['total_grants']); ?></span>
                                    <span class="ji-mega-stat-label">総掲載数</span>
                                </div>
                                <div class="ji-mega-stat">
                                    <span class="ji-mega-stat-value"><?php echo number_format($header_data['active_grants']); ?></span>
                                    <span class="ji-mega-stat-label">募集中</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ji-mega-menu-grid">
                            <div class="ji-mega-column">
                                <div class="ji-mega-column-title">検索方法</div>
                                <a href="<?php echo esc_url($grants_url); ?>" class="ji-mega-link" role="menuitem">すべての補助金・助成金</a>
                                <a href="<?php echo esc_url(add_query_arg('application_status', 'open', $grants_url)); ?>" class="ji-mega-link" role="menuitem">募集中の補助金・助成金<span class="ji-badge">HOT</span></a>
                                <a href="<?php echo esc_url(add_query_arg('orderby', 'deadline', $grants_url)); ?>" class="ji-mega-link" role="menuitem">締切間近</a>
                                <a href="<?php echo esc_url(add_query_arg('orderby', 'new', $grants_url)); ?>" class="ji-mega-link" role="menuitem">新着補助金・助成金<span class="ji-badge new">NEW</span></a>
                                <a href="<?php echo esc_url(add_query_arg('orderby', 'popular', $grants_url)); ?>" class="ji-mega-link" role="menuitem">人気の補助金・助成金</a>
                            </div>
                            
                            <div class="ji-mega-column">
                                <div class="ji-mega-column-title">カテゴリーから探す</div>
                                <?php
                                if ($header_data['categories'] && !is_wp_error($header_data['categories'])) {
                                    foreach (array_slice($header_data['categories'], 0, 8) as $category) {
                                        echo '<a href="' . esc_url(get_term_link($category)) . '" class="ji-mega-link" role="menuitem">' . esc_html($category->name) . '</a>';
                                    }
                                }
                                ?>
                            </div>
                            
                            <div class="ji-mega-column">
                                <div class="ji-mega-column-title">対象者から探す</div>
                                <a href="<?php echo esc_url(add_query_arg('grant_tag', '個人向け', $grants_url)); ?>" class="ji-mega-link" role="menuitem">個人向け</a>
                                <a href="<?php echo esc_url(add_query_arg('grant_tag', '中小企業', $grants_url)); ?>" class="ji-mega-link" role="menuitem">中小企業向け</a>
                                <a href="<?php echo esc_url(add_query_arg('grant_tag', '小規模事業者', $grants_url)); ?>" class="ji-mega-link" role="menuitem">小規模事業者向け</a>
                                <a href="<?php echo esc_url(add_query_arg('grant_tag', 'スタートアップ', $grants_url)); ?>" class="ji-mega-link" role="menuitem">スタートアップ向け</a>
                                <a href="<?php echo esc_url(add_query_arg('grant_tag', 'NPO', $grants_url)); ?>" class="ji-mega-link" role="menuitem">NPO・団体向け</a>
                                <a href="<?php echo esc_url(add_query_arg('grant_tag', '農業', $grants_url)); ?>" class="ji-mega-link" role="menuitem">農業・一次産業向け</a>
                            </div>
                            
                            <div class="ji-mega-column">
                                <div class="ji-mega-column-title">都道府県から探す</div>
                                <div class="ji-prefecture-grid">
                                    <?php
                                    $prefectures_order = ['北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県', '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県', '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県', '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県', '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'];
                                    
                                    $prefecture_terms = [];
                                    if ($header_data['prefectures'] && !is_wp_error($header_data['prefectures'])) {
                                        foreach ($header_data['prefectures'] as $pref) {
                                            $prefecture_terms[$pref->name] = $pref;
                                        }
                                    }
                                    
                                    foreach ($prefectures_order as $pref_name) {
                                        if (isset($prefecture_terms[$pref_name])) {
                                            $pref = $prefecture_terms[$pref_name];
                                            echo '<a href="' . esc_url(get_term_link($pref)) . '" class="ji-prefecture-link" role="menuitem">' . esc_html($pref->name) . '</a>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 補助金診断（直接リンク） -->
            <a href="<?php echo esc_url(home_url('/subsidy-diagnosis/')); ?>" class="ji-nav-link">
                <i class="fas fa-stethoscope ji-icon" aria-hidden="true"></i>
                <span>補助金診断</span>
            </a>
            
            <!-- 当サイトについて（直接リンク） -->
            <a href="<?php echo esc_url(home_url('/about/')); ?>" class="ji-nav-link">
                <i class="fas fa-info-circle ji-icon" aria-hidden="true"></i>
                <span>当サイトについて</span>
            </a>
            
            <a href="<?php echo esc_url(home_url('/column/')); ?>" class="ji-nav-link">
                <i class="fas fa-newspaper ji-icon" aria-hidden="true"></i>
                <span>ニュース</span>
            </a>
            
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="ji-nav-link">
                <i class="fas fa-envelope ji-icon" aria-hidden="true"></i>
                <span>お問い合わせ</span>
            </a>
        </nav>
        
        <!-- Header Actions -->
        <div class="ji-actions">
            <button type="button" id="ji-search-toggle" class="ji-btn ji-btn-icon" aria-label="検索を開く" aria-expanded="false" aria-controls="ji-search-panel">
                <i class="fas fa-search" aria-hidden="true"></i>
            </button>
            
            <a href="<?php echo esc_url($grants_url); ?>" class="ji-btn ji-btn-primary">
                <i class="fas fa-search" aria-hidden="true"></i>
                <span>補助金を探す</span>
            </a>
            
            <button type="button" id="ji-mobile-toggle" class="ji-mobile-toggle" aria-label="メニューを開く" aria-expanded="false" aria-controls="ji-mobile-menu">
                <span class="ji-hamburger">
                    <span class="ji-hamburger-line"></span>
                    <span class="ji-hamburger-line"></span>
                    <span class="ji-hamburger-line"></span>
                </span>
            </button>
        </div>
    </div>
    
    <!-- Search Panel -->
    <div id="ji-search-panel" class="ji-search-panel" role="search" aria-label="サイト内検索">
        <div class="ji-search-panel-inner">
            <form id="ji-search-form" class="ji-search-form" action="<?php echo esc_url($grants_url); ?>" method="get">
                <div class="ji-search-main">
                    <div class="ji-search-input-wrapper">
                        <i class="fas fa-search ji-search-icon" aria-hidden="true"></i>
                        <input type="search" id="ji-search-input" name="search" class="ji-search-input" placeholder="補助金名、キーワードで検索..." autocomplete="off" aria-label="検索キーワード">
                        <button type="button" class="ji-search-clear" aria-label="検索をクリア">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                    
                    <div class="ji-search-suggestions" role="group" aria-label="人気の検索キーワード">
                        <span class="ji-search-suggestion-label">人気:</span>
                        <?php foreach ($header_data['popular_searches'] as $search): ?>
                        <button type="button" class="ji-search-suggestion" data-search="<?php echo esc_attr($search); ?>"><?php echo esc_html($search); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="ji-search-filters">
                    <select name="category" class="ji-search-select" aria-label="カテゴリー">
                        <option value="">すべてのカテゴリー</option>
                        <?php
                        if ($header_data['categories'] && !is_wp_error($header_data['categories'])) {
                            foreach ($header_data['categories'] as $cat) {
                                echo '<option value="' . esc_attr($cat->slug) . '">' . esc_html($cat->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    
                    <select name="prefecture" class="ji-search-select" aria-label="都道府県">
                        <option value="">すべての都道府県</option>
                        <?php
                        if ($header_data['prefectures'] && !is_wp_error($header_data['prefectures'])) {
                            foreach ($header_data['prefectures'] as $pref) {
                                echo '<option value="' . esc_attr($pref->slug) . '">' . esc_html($pref->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    
                    <button type="submit" class="ji-search-submit">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span>検索</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</header>

<!-- Mobile Menu -->
<div id="ji-mobile-menu" class="ji-mobile-menu" role="dialog" aria-modal="true" aria-label="モバイルメニュー">
    <div class="ji-mobile-menu-header">
        <div class="ji-mobile-logo">
            <div class="ji-mobile-logo-icon">
                <img src="https://joseikin-insight.com/wp-content/uploads/2025/05/cropped-logo3.webp" alt="アイコン" width="32" height="32">
            </div>
            <span class="ji-mobile-logo-text">助成金インサイト</span>
        </div>
        <button type="button" id="ji-mobile-close" class="ji-mobile-close" aria-label="メニューを閉じる">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
    </div>
    
    <div class="ji-mobile-search">
        <div class="ji-mobile-search-wrapper">
            <i class="fas fa-search ji-mobile-search-icon" aria-hidden="true"></i>
            <input type="search" id="ji-mobile-search-input" class="ji-mobile-search-input" placeholder="補助金を検索..." aria-label="補助金を検索">
        </div>
    </div>
    
    <div class="ji-mobile-content">
        <div class="ji-mobile-section">
            <!-- サービス一覧（アコーディオン） -->
            <div class="ji-mobile-accordion">
                <button type="button" class="ji-mobile-accordion-trigger" aria-expanded="false" aria-controls="accordion-services">
                    <span>サービス一覧</span>
                    <i class="fas fa-chevron-down" aria-hidden="true"></i>
                </button>
                <div id="accordion-services" class="ji-mobile-accordion-content">
                    <a href="<?php echo esc_url($grants_url); ?>" class="ji-mobile-link">すべての補助金・助成金</a>
                    <a href="<?php echo esc_url(add_query_arg('application_status', 'open', $grants_url)); ?>" class="ji-mobile-link">募集中の補助金・助成金</a>
                    <a href="<?php echo esc_url(add_query_arg('orderby', 'deadline', $grants_url)); ?>" class="ji-mobile-link">締切間近</a>
                    <a href="<?php echo esc_url(add_query_arg('orderby', 'new', $grants_url)); ?>" class="ji-mobile-link">新着補助金・助成金</a>
                    <a href="<?php echo esc_url(home_url('/categories/')); ?>" class="ji-mobile-link">カテゴリー一覧</a>
                    <a href="<?php echo esc_url(home_url('/prefectures/')); ?>" class="ji-mobile-link">都道府県一覧</a>
                </div>
            </div>
            
            <!-- 補助金診断（単独リンク） -->
            <a href="<?php echo esc_url(home_url('/subsidy-diagnosis/')); ?>" class="ji-mobile-single-link">
                <span>補助金診断</span>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </a>
            
            <!-- 当サイトについて（単独リンク） -->
            <a href="<?php echo esc_url(home_url('/about/')); ?>" class="ji-mobile-single-link">
                <span>当サイトについて</span>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </a>
            
            <!-- ニュース（単独リンク） -->
            <a href="<?php echo esc_url(home_url('/column/')); ?>" class="ji-mobile-single-link">
                <span>ニュース</span>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </a>
            
            <!-- お問い合わせ（単独リンク） -->
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="ji-mobile-single-link">
                <span>お問い合わせ</span>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </a>
        </div>
        
        <a href="<?php echo esc_url($grants_url); ?>" class="ji-mobile-cta">
            <i class="fas fa-search" aria-hidden="true"></i>
            <span>補助金・助成金を探す</span>
        </a>
        
        <div class="ji-mobile-stats">
            <div class="ji-mobile-stat">
                <span class="ji-mobile-stat-value"><?php echo number_format($header_data['total_grants']); ?></span>
                <span class="ji-mobile-stat-label">掲載数</span>
            </div>
            <div class="ji-mobile-stat">
                <span class="ji-mobile-stat-value"><?php echo number_format($header_data['active_grants']); ?></span>
                <span class="ji-mobile-stat-label">募集中</span>
            </div>
            <div class="ji-mobile-stat">
                <span class="ji-mobile-stat-value">47</span>
                <span class="ji-mobile-stat-label">都道府県</span>
            </div>
        </div>
    </div>
    
    <div class="ji-mobile-footer">
        <div class="ji-mobile-social">
            <a href="https://twitter.com/joseikininsight" class="ji-mobile-social-link" aria-label="Twitter" target="_blank" rel="noopener"><i class="fab fa-twitter"></i></a>
            <a href="https://facebook.com/joseikin.insight" class="ji-mobile-social-link" aria-label="Facebook" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
            <a href="https://www.youtube.com/channel/UCbfjOrG3nSPI3GFzKnGcspQ" class="ji-mobile-social-link" aria-label="YouTube" target="_blank" rel="noopener"><i class="fab fa-youtube"></i></a>
            <a href="https://note.com/joseikin_insight" class="ji-mobile-social-link" aria-label="Note" target="_blank" rel="noopener"><i class="fas fa-sticky-note"></i></a>
        </div>
        
        <div class="ji-mobile-trust">
            <span class="ji-mobile-trust-badge"><i class="fas fa-shield-alt"></i>専門家監修</span>
            <span class="ji-mobile-trust-badge"><i class="fas fa-sync-alt"></i>毎日更新</span>
        </div>
        
        <div class="ji-mobile-copyright">&copy; <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?></div>
    </div>
</div>

<!-- Header Placeholder -->
<div class="ji-header-placeholder" aria-hidden="true"></div>

<main id="main-content" role="main" tabindex="-1">

<script>
(function() {
    'use strict';
    
    const header = document.getElementById('ji-header');
    const searchToggle = document.getElementById('ji-search-toggle');
    const searchPanel = document.getElementById('ji-search-panel');
    const searchInput = document.getElementById('ji-search-input');
    const mobileToggle = document.getElementById('ji-mobile-toggle');
    const mobileMenu = document.getElementById('ji-mobile-menu');
    const mobileClose = document.getElementById('ji-mobile-close');
    const mobileSearchInput = document.getElementById('ji-mobile-search-input');
    const navItems = document.querySelectorAll('.ji-nav-item[data-menu]');
    
    let lastScrollY = 0;
    let isSearchOpen = false;
    let isMobileMenuOpen = false;
    let ticking = false;
    
    // クリック式メガメニュー（ホバー廃止）
    function initMegaMenus() {
        navItems.forEach(item => {
            const link = item.querySelector('.ji-nav-link');
            const menu = item.querySelector('.ji-mega-menu');
            
            if (!menu || !link) return;
            
            // クリックでトグル
            link.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const isExpanded = item.classList.contains('menu-active');
                
                // 他のメニューを閉じる
                navItems.forEach(otherItem => {
                    if (otherItem !== item) {
                        otherItem.classList.remove('menu-active');
                        const otherLink = otherItem.querySelector('.ji-nav-link');
                        if (otherLink) otherLink.setAttribute('aria-expanded', 'false');
                    }
                });
                
                // 現在のメニューをトグル
                if (isExpanded) {
                    item.classList.remove('menu-active');
                    link.setAttribute('aria-expanded', 'false');
                } else {
                    item.classList.add('menu-active');
                    link.setAttribute('aria-expanded', 'true');
                }
            });
            
            // キーボード操作
            link.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    link.click();
                }
                
                if (e.key === 'Escape') {
                    item.classList.remove('menu-active');
                    link.setAttribute('aria-expanded', 'false');
                    link.focus();
                }
            });
        });
        
        // 外部クリックで閉じる
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.ji-nav-item')) {
                navItems.forEach(item => {
                    item.classList.remove('menu-active');
                    const link = item.querySelector('.ji-nav-link');
                    if (link) link.setAttribute('aria-expanded', 'false');
                });
            }
        });
    }
    
    function handleScroll() {
        const scrollY = window.scrollY;
        
        if (scrollY > 50) {
            header?.classList.add('scrolled');
        } else {
            header?.classList.remove('scrolled');
        }
        
        if (scrollY > 150) {
            if (scrollY > lastScrollY + 5) {
                header?.classList.add('hidden');
                // スクロール時にメニューを閉じる
                navItems.forEach(item => {
                    item.classList.remove('menu-active');
                    const link = item.querySelector('.ji-nav-link');
                    if (link) link.setAttribute('aria-expanded', 'false');
                });
            } else if (scrollY < lastScrollY - 5) {
                header?.classList.remove('hidden');
            }
        } else {
            header?.classList.remove('hidden');
        }
        
        lastScrollY = scrollY;
        ticking = false;
    }
    
    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(handleScroll);
            ticking = true;
        }
    }
    
    function toggleSearch() {
        isSearchOpen = !isSearchOpen;
        searchPanel?.classList.toggle('open', isSearchOpen);
        
        // メガメニューを閉じる
        navItems.forEach(item => {
            item.classList.remove('menu-active');
            const link = item.querySelector('.ji-nav-link');
            if (link) link.setAttribute('aria-expanded', 'false');
        });
        
        if (searchToggle) {
            searchToggle.setAttribute('aria-expanded', isSearchOpen);
            searchToggle.innerHTML = isSearchOpen 
                ? '<i class="fas fa-times" aria-hidden="true"></i>'
                : '<i class="fas fa-search" aria-hidden="true"></i>';
        }
        
        if (isSearchOpen && searchInput) {
            setTimeout(() => searchInput.focus(), 150);
        }
    }
    
    function closeSearch() {
        if (!isSearchOpen) return;
        isSearchOpen = false;
        searchPanel?.classList.remove('open');
        if (searchToggle) {
            searchToggle.setAttribute('aria-expanded', 'false');
            searchToggle.innerHTML = '<i class="fas fa-search" aria-hidden="true"></i>';
        }
    }
    
    function openMobileMenu() {
        isMobileMenuOpen = true;
        mobileMenu?.classList.add('open');
        mobileToggle?.setAttribute('aria-expanded', 'true');
        document.body.classList.add('menu-open');
        setTimeout(() => mobileClose?.focus(), 100);
    }
    
    function closeMobileMenu() {
        if (!isMobileMenuOpen) return;
        isMobileMenuOpen = false;
        mobileMenu?.classList.remove('open');
        mobileToggle?.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('menu-open');
        mobileToggle?.focus();
    }
    
    function initAccordions() {
        document.querySelectorAll('.ji-mobile-accordion-trigger').forEach(trigger => {
            trigger.addEventListener('click', () => {
                const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
                const contentId = trigger.getAttribute('aria-controls');
                const content = document.getElementById(contentId);
                
                // 他のアコーディオンを閉じる
                document.querySelectorAll('.ji-mobile-accordion-trigger').forEach(t => {
                    if (t !== trigger) {
                        t.setAttribute('aria-expanded', 'false');
                        const c = document.getElementById(t.getAttribute('aria-controls'));
                        c?.classList.remove('open');
                    }
                });
                
                trigger.setAttribute('aria-expanded', !isExpanded);
                content?.classList.toggle('open', !isExpanded);
            });
        });
    }
    
    function initSearchSuggestions() {
        document.querySelectorAll('.ji-search-suggestion').forEach(btn => {
            btn.addEventListener('click', () => {
                if (searchInput) {
                    searchInput.value = btn.dataset.search;
                    searchInput.focus();
                }
            });
        });
    }
    
    function initSearchClear() {
        const clearBtn = document.querySelector('.ji-search-clear');
        if (clearBtn && searchInput) {
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                searchInput.focus();
            });
        }
    }
    
    function initMobileSearch() {
        if (mobileSearchInput) {
            mobileSearchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const query = mobileSearchInput.value.trim();
                    if (query) {
                        window.location.href = '<?php echo esc_url($grants_url); ?>?search=' + encodeURIComponent(query);
                    }
                }
            });
        }
    }
    
    function trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        element.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                } else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        });
    }
    
    // イベントリスナー
    window.addEventListener('scroll', requestTick, { passive: true });
    
    searchToggle?.addEventListener('click', toggleSearch);
    mobileToggle?.addEventListener('click', openMobileMenu);
    mobileClose?.addEventListener('click', closeMobileMenu);
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (isMobileMenuOpen) closeMobileMenu();
            else if (isSearchOpen) closeSearch();
            else {
                navItems.forEach(item => {
                    item.classList.remove('menu-active');
                    const link = item.querySelector('.ji-nav-link');
                    if (link) link.setAttribute('aria-expanded', 'false');
                });
            }
        }
        
        // Ctrl/Cmd + K で検索
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            toggleSearch();
        }
    });
    
    document.addEventListener('click', (e) => {
        if (isSearchOpen && !e.target.closest('.ji-search-panel') && !e.target.closest('#ji-search-toggle')) {
            closeSearch();
        }
    });
    
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024 && isMobileMenuOpen) {
            closeMobileMenu();
        }
    });
    
    // 初期化
    initMegaMenus();
    initAccordions();
    initSearchSuggestions();
    initSearchClear();
    initMobileSearch();
    
    if (mobileMenu) {
        trapFocus(mobileMenu);
    }
    
    handleScroll();
    
    console.log('[✓] Joseikin Insight Header v10.0.0 - Click Menu Edition');
})();
</script>
