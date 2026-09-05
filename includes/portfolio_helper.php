<?php
/**
 * Stylist Portfolio Local Persistence Helper
 * Bridges portfolio storage for salon stylists while backend direct portfolio API is in progress.
 */

function get_portfolio_storage_path() {
    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir . '/stylists_portfolio.json';
}

function load_all_stylist_portfolios() {
    $filePath = get_portfolio_storage_path();
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        if (is_array($data)) {
            return $data;
        }
    }
    return [];
}

function get_stylist_portfolio($stylistId) {
    if (empty($stylistId)) return [];
    $all = load_all_stylist_portfolios();
    return $all[$stylistId] ?? [];
}

function save_stylist_portfolio($stylistId, array $portfolioList) {
    if (empty($stylistId)) return false;
    $filePath = get_portfolio_storage_path();
    $all = load_all_stylist_portfolios();
    $all[$stylistId] = array_values(array_filter($portfolioList));
    return file_put_contents($filePath, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}

function delete_stylist_portfolio($stylistId) {
    if (empty($stylistId)) return false;
    $filePath = get_portfolio_storage_path();
    $all = load_all_stylist_portfolios();
    if (isset($all[$stylistId])) {
        unset($all[$stylistId]);
        return file_put_contents($filePath, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
    }
    return true;
}

/**
 * Stylist Metadata (Serviceable Gender, Profession) Persistence
 */
function get_meta_storage_path() {
    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir . '/stylists_meta.json';
}

function load_all_stylist_meta() {
    $filePath = get_meta_storage_path();
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        if (is_array($data)) {
            return $data;
        }
    }
    return [];
}

function get_stylist_meta($stylistId) {
    if (empty($stylistId)) return [];
    $all = load_all_stylist_meta();
    return $all[$stylistId] ?? [];
}

function save_stylist_meta($stylistId, array $meta) {
    if (empty($stylistId)) return false;
    $filePath = get_meta_storage_path();
    $all = load_all_stylist_meta();
    $existing = $all[$stylistId] ?? [];
    $all[$stylistId] = array_merge($existing, $meta);
    return file_put_contents($filePath, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
}

function delete_stylist_meta($stylistId) {
    if (empty($stylistId)) return false;
    $filePath = get_meta_storage_path();
    $all = load_all_stylist_meta();
    if (isset($all[$stylistId])) {
        unset($all[$stylistId]);
        return file_put_contents($filePath, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
    }
    return true;
}
