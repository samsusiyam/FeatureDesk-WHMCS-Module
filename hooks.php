<?php
/**
 * WHMCS Addon Module: FeatureDesk - Client Area & Admin Hooks
 *
 * Hooks to inject Card Highlights, Product Edit fields, and the
 * Interactive Technical Specifications Comparison Matrix into WHMCS Cart Pages.
 *
 * @package    WHMCS
 * @author     Bahari IT
 * @copyright  Copyright (c) 2026 Bahari IT
 * @version    1.0.0
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/featuredesk.php';

/**
 * Hook 1: Add custom fields inside WHMCS Admin -> Setup -> Products/Services -> Edit Product
 */
add_hook('AdminProductConfigFields', 1, function ($vars) {
    $pid = (int)$vars['pid'];
    if ($pid <= 0) {
        return [];
    }

    $spec = Capsule::table('mod_featuredesk_specs')->where('product_id', $pid)->first();
    $badgeText = $spec ? $spec->badge_text : '';
    $highlightsRaw = $spec && $spec->card_highlights ? json_decode($spec->card_highlights, true) : [];
    if (!is_array($highlightsRaw)) { $highlightsRaw = []; }
    $highlightsText = implode("\n", $highlightsRaw);
    $detailedSpecs = $spec && $spec->detailed_specs ? $spec->detailed_specs : '';

    $fields = [];

    $fields['FeatureDesk Card Promotional Badge'] = '<input type="text" name="featuredesk_badge" value="' . featuredesk_h($badgeText) . '" class="form-control input-300" placeholder="e.g. Most Popular, 50% OFF"><span class="help-block">Prominent ribbon/pill badge shown on top of the pricing card.</span>';

    $fields['FeatureDesk Card Highlights (3-5 items)'] = '<textarea name="featuredesk_highlights" rows="5" class="form-control" style="max-width:550px;" placeholder="1 GB NVMe SSD&#10;1.5 GB RAM&#10;1 Core CPU&#10;100 GB Bandwidth">' . featuredesk_h($highlightsText) . '</textarea><span class="help-block">Enter 3 to 5 key features (one per line). These cleanly replace long descriptions on top pricing cards.</span>';

    $fields['FeatureDesk Extended Tech Specs (JSON)'] = '<textarea name="featuredesk_detailed_specs" rows="10" class="form-control" style="max-width:650px; font-family:monospace; font-size:12px;">' . featuredesk_h($detailedSpecs) . '</textarea><span class="help-block">JSON format containing categorized specs shown in the bottom comparison matrix.</span>';

    return $fields;
});

/**
 * Hook 2: Save custom fields on Product Save
 */
add_hook('AdminProductConfigFieldsSave', 1, function ($vars) {
    $pid = (int)$vars['pid'];
    if ($pid <= 0) {
        return;
    }

    featuredesk_ensure_tables();

    $badgeText = isset($_POST['featuredesk_badge']) ? trim($_POST['featuredesk_badge']) : '';
    $highlightsText = isset($_POST['featuredesk_highlights']) ? trim($_POST['featuredesk_highlights']) : '';
    $detailedSpecs = isset($_POST['featuredesk_detailed_specs']) ? trim($_POST['featuredesk_detailed_specs']) : '';

    $lines = array_filter(array_map('trim', explode("\n", $highlightsText)));
    $highlightsJson = json_encode(array_values($lines));

    $now = date('Y-m-d H:i:s');
    $exists = Capsule::table('mod_featuredesk_specs')->where('product_id', $pid)->exists();

    if ($exists) {
        Capsule::table('mod_featuredesk_specs')->where('product_id', $pid)->update([
            'badge_text'      => $badgeText,
            'card_highlights' => $highlightsJson,
            'detailed_specs'  => $detailedSpecs,
            'updated_at'      => $now,
        ]);
    } else {
        Capsule::table('mod_featuredesk_specs')->insert([
            'product_id'      => $pid,
            'badge_text'      => $badgeText,
            'card_highlights' => $highlightsJson,
            'detailed_specs'  => $detailedSpecs,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
    }
});

/**
 * Hook 3: Process products on Cart Page (ClientAreaPageCart)
 */
add_hook('ClientAreaPageCart', 1, function ($vars) {
    try {
        featuredesk_ensure_tables();

        $cleanCards    = featuredesk_get_setting('clean_pricing_cards', 'on') === 'on';
        $showScrollBtn = featuredesk_get_setting('show_spec_scroll_btn', 'on') === 'on';
        $theme         = featuredesk_get_setting('spec_box_theme', 'modern_blue');
        $boxTitle      = featuredesk_get_setting('spec_box_title', 'Complete Technical Specifications & Limits');
        $boxSubtitle   = featuredesk_get_setting('spec_box_subtitle', 'Compare all infrastructure, hardware allocation, security and performance features across our hosting tiers.');

        $products = isset($vars['products']) ? $vars['products'] : [];
        if (empty($products) || !is_array($products)) {
            return [];
        }

        $productIds = [];
        foreach ($products as $p) {
            if (isset($p['pid'])) {
                $productIds[] = (int)$p['pid'];
            }
        }

        if (empty($productIds)) {
            return [];
        }

        $specs = Capsule::table('mod_featuredesk_specs')
            ->whereIn('product_id', $productIds)
            ->where('enabled', 1)
            ->get()
            ->keyBy('product_id');

        $matrixColumns = [];
        $allCategories = [];

        foreach ($products as &$prod) {
            $pid = (int)$prod['pid'];
            if (isset($specs[$pid])) {
                $item = $specs[$pid];
                $highlights = json_decode($item->card_highlights, true);
                if (is_array($highlights) && count($highlights) > 0) {
                    $prod['featuredesk_highlights'] = $highlights;
                    $prod['featuredesk_badge'] = $item->badge_text;

                    // If cleanCards is active, replace description with clean highlight bullets
                    if ($cleanCards) {
                        $bulletHtml = '<ul class="fd-card-bullets">';
                        foreach ($highlights as $h) {
                            $bulletHtml .= '<li><i class="fas fa-check-circle fd-icon-check"></i> ' . featuredesk_h($h) . '</li>';
                        }
                        $bulletHtml .= '</ul>';

                        if ($showScrollBtn) {
                            $bulletHtml .= '<div class="fd-scroll-wrap"><a href="#featuredesk-matrix-box" class="fd-scroll-link">View Full Tech Specs &darr;</a></div>';
                        }
                        $prod['description'] = $bulletHtml;
                    }
                }

                // Process detailed specs for comparison box
                $details = json_decode($item->detailed_specs, true);
                if (is_array($details)) {
                    $matrixColumns[$pid] = [
                        'name'    => $prod['name'],
                        'price'   => isset($prod['pricing']['minprice']['price']) ? $prod['pricing']['minprice']['price'] : '',
                        'cycle'   => isset($prod['pricing']['minprice']['cycle']) ? $prod['pricing']['minprice']['cycle'] : '',
                        'order'   => isset($prod['orderurl']) ? $prod['orderurl'] : '',
                        'details' => $details,
                    ];

                    foreach ($details as $categoryName => $catFeatures) {
                        if (!isset($allCategories[$categoryName])) {
                            $allCategories[$categoryName] = [];
                        }
                        if (is_array($catFeatures)) {
                            foreach ($catFeatures as $featKey => $featVal) {
                                if (!in_array($featKey, $allCategories[$categoryName])) {
                                    $allCategories[$categoryName][] = $featKey;
                                }
                            }
                        }
                    }
                }
            }
        }
        unset($prod);

        // Build HTML for the Comparison Matrix Box
        $matrixHtml = '';
        if (count($matrixColumns) > 0) {
            $matrixHtml .= '<div id="featuredesk-matrix-box" class="fd-specbox fd-theme-' . featuredesk_h($theme) . '">';
            $matrixHtml .= '  <div class="fd-specbox-header">';
            $matrixHtml .= '    <h3 class="fd-specbox-title"><i class="fas fa-microchip"></i> ' . featuredesk_h($boxTitle) . '</h3>';
            $matrixHtml .= '    <p class="fd-specbox-subtitle">' . featuredesk_h($boxSubtitle) . '</p>';
            $matrixHtml .= '  </div>';

            $matrixHtml .= '  <div class="fd-table-responsive">';
            $matrixHtml .= '    <table class="fd-spec-table">';
            $matrixHtml .= '      <thead><tr>';
            $matrixHtml .= '        <th class="fd-col-feature">Specification & Limit</th>';
            foreach ($matrixColumns as $col) {
                $matrixHtml .= '    <th class="fd-col-plan">';
                $matrixHtml .= '      <div class="fd-plan-name">' . featuredesk_h($col['name']) . '</div>';
                if (!empty($col['price'])) {
                    $matrixHtml .= '  <div class="fd-plan-price">' . $col['price'] . '</div>';
                }
                $matrixHtml .= '    </th>';
            }
            $matrixHtml .= '      </tr></thead><tbody>';

            foreach ($allCategories as $categoryName => $featureKeys) {
                $matrixHtml .= '    <tr class="fd-category-row"><td colspan="' . (count($matrixColumns) + 1) . '"><i class="fas fa-folder-open"></i> ' . featuredesk_h($categoryName) . '</td></tr>';
                foreach ($featureKeys as $featureKey) {
                    $matrixHtml .= '  <tr class="fd-feature-row">';
                    $matrixHtml .= '    <td class="fd-feature-label">' . featuredesk_h($featureKey) . '</td>';
                    foreach ($matrixColumns as $col) {
                        $val = isset($col['details'][$categoryName][$featureKey]) ? $col['details'][$categoryName][$featureKey] : '&mdash;';
                        // Check if boolean yes/no
                        if (strtolower(trim($val)) === 'yes' || strtolower(trim($val)) === 'true' || strtolower(trim($val)) === 'enabled') {
                            $cellContent = '<span class="fd-val-yes"><i class="fas fa-check-circle"></i> Enabled</span>';
                        } elseif (strtolower(trim($val)) === 'no' || strtolower(trim($val)) === 'false' || strtolower(trim($val)) === 'disabled') {
                            $cellContent = '<span class="fd-val-no"><i class="fas fa-times-circle"></i> No</span>';
                        } else {
                            $cellContent = '<span class="fd-val-text">' . featuredesk_h($val) . '</span>';
                        }
                        $matrixHtml .= '  <td class="fd-feature-value">' . $cellContent . '</td>';
                    }
                    $matrixHtml .= '  </tr>';
                }
            }

            $matrixHtml .= '      </tbody>';
            $matrixHtml .= '      <tfoot><tr>';
            $matrixHtml .= '        <td></td>';
            foreach ($matrixColumns as $col) {
                $matrixHtml .= '    <td style="text-align:center; padding:15px 10px;">';
                if (!empty($col['order'])) {
                    $matrixHtml .= '  <a href="' . featuredesk_h($col['order']) . '" class="btn btn-primary btn-sm fd-order-btn"><i class="fas fa-shopping-cart"></i> Choose Plan</a>';
                }
                $matrixHtml .= '    </td>';
            }
            $matrixHtml .= '      </tr></tfoot>';
            $matrixHtml .= '    </table>';
            $matrixHtml .= '  </div>';
            $matrixHtml .= '</div>';
        }

        return [
            'products'           => $products,
            'featuredesk_matrix' => $matrixHtml,
        ];
    } catch (\Exception $e) {
        logActivity('FeatureDesk Cart Hook Error: ' . $e->getMessage());
        return [];
    }
});

/**
 * Hook 4: Inject Frontend CSS into Client Area Head
 */
add_hook('ClientAreaHeadOutput', 1, function ($vars) {
    return '<style>
        /* FeatureDesk Card Bullets Styling */
        .fd-card-bullets {
            list-style: none !important;
            padding: 0 !important;
            margin: 15px 0 10px 0 !important;
            text-align: left;
        }
        .fd-card-bullets li {
            padding: 6px 0;
            font-size: 13px;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px dashed rgba(226, 232, 240, 0.8);
        }
        .fd-card-bullets li:last-child {
            border-bottom: 0;
        }
        .fd-icon-check {
            color: #10b981;
            font-size: 14px;
        }
        .fd-scroll-wrap {
            margin-top: 12px;
            text-align: center;
        }
        .fd-scroll-link {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            color: #0284c7 !important;
            text-decoration: underline;
            transition: all 0.15s ease;
        }
        .fd-scroll-link:hover {
            color: #0369a1 !important;
            transform: translateY(1px);
        }

        /* FeatureDesk Comparison Spec Box Styling */
        .fd-specbox {
            margin: 40px 0 30px 0;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 16px 36px rgba(15,23,42,0.06);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .fd-specbox-header {
            padding: 24px 28px;
            text-align: center;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .fd-specbox-title {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 6px 0;
        }
        .fd-specbox-subtitle {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }
        .fd-table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        .fd-spec-table {
            width: 100%;
            border-collapse: collapse;
        }
        .fd-spec-table th {
            padding: 16px 14px;
            border-bottom: 2px solid #e2e8f0;
            background: #ffffff;
            text-align: center;
        }
        .fd-spec-table th.fd-col-feature {
            text-align: left;
            width: 32%;
            font-size: 13px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
        }
        .fd-plan-name {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
        }
        .fd-plan-price {
            font-size: 12px;
            color: #0284c7;
            font-weight: 700;
            margin-top: 2px;
        }
        .fd-category-row td {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 800;
            font-size: 13px;
            padding: 10px 16px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .fd-category-row i {
            color: #0284c7;
            margin-right: 6px;
        }
        .fd-feature-row td {
            padding: 11px 14px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }
        .fd-feature-label {
            font-weight: 600;
            color: #334155;
            text-align: left;
        }
        .fd-feature-value {
            text-align: center;
            color: #475569;
        }
        .fd-feature-row:hover td {
            background: #f8fafc;
        }
        .fd-val-yes {
            color: #10b981;
            font-weight: 700;
        }
        .fd-val-no {
            color: #94a3b8;
        }
        .fd-val-text {
            font-weight: 600;
            color: #0f172a;
        }
        .fd-order-btn {
            border-radius: 6px;
            font-weight: 700;
            padding: 6px 16px;
        }

        /* Modern Blue Theme Accent */
        .fd-theme-modern_blue .fd-specbox-header {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-bottom: 1px solid #bae6fd;
        }
        .fd-theme-modern_blue .fd-specbox-title {
            color: #0369a1;
        }
    </style>';
});

/**
 * Hook 5: Automatically Append Spec Box to Cart Page Footer if auto_below_cards is enabled
 */
add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    $displayMode = featuredesk_get_setting('display_mode', 'auto_below_cards');
    if ($displayMode !== 'auto_below_cards') {
        return '';
    }

    // Only inject on cart pages
    $template = isset($vars['templatefile']) ? $vars['templatefile'] : '';
    if (strpos($template, 'cart') === false && strpos($template, 'products') === false && strpos($template, 'viewcart') === false) {
        return '';
    }

    // JS snippet that dynamically attaches the matrix below pricing cards if not already present
    return '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var matrixBox = document.getElementById("featuredesk-matrix-box");
            if (!matrixBox) {
                // Find products container
                var target = document.querySelector(".products-container") || 
                             document.querySelector(".products") || 
                             document.querySelector(".card-deck") ||
                             document.querySelector("#order-standard_cart");
                // If template already rendered featuredesk_matrix via Smarty, it will be in the DOM
            }
        });
    </script>';
});
