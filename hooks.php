<?php
/**
 * WHMCS Addon Module: FeatureDesk - Smart Spec Box & Plan Matrix
 * Hooks File
 *
 * Handles:
 * 1. Admin Product Config Tab Injection (Fields for Highlights & Detailed Specs)
 * 2. Admin Product Config Save Handler
 * 3. ClientAreaPageCart / ClientAreaPage: Clean Cards & Matrix Data Generation
 * 4. ClientAreaHeadOutput: Responsive CSS injection
 * 5. ClientAreaFooterOutput: Automatic DOM Placement below Pricing Cards
 *
 * @package    FeatureDesk
 * @author     MD Samsuzzaman Siyam <samsusiyam@gmail.com>
 * @copyright  Bahari Host
 * @license    Proprietary
 */

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (!function_exists('featuredesk_get_setting')) {
    function featuredesk_get_setting($key, $default = '') {
        try {
            if (Capsule::schema()->hasTable('mod_featuredesk_settings')) {
                $row = Capsule::table('mod_featuredesk_settings')->where('setting_key', $key)->first();
                if ($row && $row->setting_value !== null && $row->setting_value !== '') {
                    return $row->setting_value;
                }
            }
        } catch (\Exception $e) {
            // fallback
        }
        return $default;
    }
}

if (!function_exists('featuredesk_h')) {
    function featuredesk_h($str) {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Hook 1: Inject Custom Product Fields in WHMCS Admin Product Edit Page
 */
add_hook('AdminProductConfigFields', 1, function ($vars) {
    $pid = isset($vars['pid']) ? (int)$vars['pid'] : 0;
    if (!$pid) {
        return [];
    }

    try {
        $spec = Capsule::table('mod_featuredesk_specs')->where('product_id', $pid)->first();
    } catch (\Exception $e) {
        $spec = null;
    }

    $badge = $spec ? $spec->badge_text : '';
    $color = ($spec && !empty($spec->badge_color)) ? $spec->badge_color : '#2563EB';
    $highlights = ($spec && $spec->card_highlights) ? json_decode($spec->card_highlights, true) : [];
    if (!is_array($highlights)) {
        $highlights = [];
    }
    $highlightsText = implode("\n", $highlights);

    $specsData = ($spec && $spec->detailed_specs) ? $spec->detailed_specs : '[]';

    $html = '
    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin:15px 0;">
        <div style="display:flex; align-items:center; margin-bottom:15px;">
            <div style="background:#2563EB; color:#fff; width:34px; height:34px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; font-size:16px; margin-right:12px;">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <h4 style="margin:0; font-size:16px; font-weight:700; color:#0f172a;">FeatureDesk — Smart Spec Box & Matrix</h4>
                <p style="margin:2px 0 0; font-size:12px; color:#64748b;">Manage short hero highlights for the pricing card and granular specs for the comparison matrix.</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6" style="margin-bottom:15px;">
                <label style="font-weight:600; font-size:13px; color:#334155;">Card Highlight Badge</label>
                <div style="display:flex; gap:10px;">
                    <input type="text" name="featuredesk_badge" value="' . featuredesk_h($badge) . '" class="form-control" placeholder="e.g. Most Popular, Best Value, Starter" style="flex:1;">
                    <input type="color" name="featuredesk_badge_color" value="' . featuredesk_h($color) . '" class="form-control" style="width:50px; padding:2px; height:34px;">
                </div>
                <small style="color:#64748b; font-size:11px;">Small tag displayed at the top corner of the pricing card.</small>
            </div>

            <div class="col-md-6" style="margin-bottom:15px;">
                <label style="font-weight:600; font-size:13px; color:#334155;">Hero Highlights (1 item per line, max 5-6 recommended)</label>
                <textarea name="featuredesk_highlights" rows="5" class="form-control" placeholder="1 Website Hosted&#10;10 GB NVMe Storage&#10;Unlimited Bandwidth&#10;Free SSL & Daily Backup">' . featuredesk_h($highlightsText) . '</textarea>
                <small style="color:#64748b; font-size:11px;">These replace the lengthy description on the pricing card with clean, modern checkmark bullets.</small>
            </div>
        </div>

        <div style="margin-top:10px;">
            <label style="font-weight:600; font-size:13px; color:#334155;">Detailed Technical Specifications (JSON Matrix Data)</label>
            <textarea name="featuredesk_specs" rows="8" class="form-control" style="font-family:monospace; font-size:12px;">' . featuredesk_h($specsData) . '</textarea>
            <small style="color:#64748b; font-size:11px;">Format: [{"group": "Hardware", "name": "CPU", "value": "1 vCPU"}, ...]</small>
        </div>
    </div>';

    return [
        'FeatureDesk Specifications' => $html,
    ];
});

/**
 * Hook 2: Save Custom Product Fields from WHMCS Admin
 */
add_hook('AdminProductConfigFieldsSave', 1, function ($vars) {
    $pid = isset($vars['pid']) ? (int)$vars['pid'] : 0;
    if (!$pid) {
        return;
    }

    $badge = isset($_POST['featuredesk_badge']) ? trim($_POST['featuredesk_badge']) : '';
    $badgeColor = isset($_POST['featuredesk_badge_color']) ? trim($_POST['featuredesk_badge_color']) : '#2563EB';
    $rawHighlights = isset($_POST['featuredesk_highlights']) ? trim($_POST['featuredesk_highlights']) : '';
    $rawSpecs = isset($_POST['featuredesk_specs']) ? trim($_POST['featuredesk_specs']) : '';

    $highlights = [];
    if (!empty($rawHighlights)) {
        $lines = explode("\n", $rawHighlights);
        foreach ($lines as $line) {
            $cleaned = trim($line);
            if ($cleaned !== '') {
                $highlights[] = $cleaned;
            }
        }
    }

    if (!empty($rawSpecs)) {
        $testJson = json_decode($rawSpecs, true);
        if ($testJson === null && json_last_error() !== JSON_ERROR_NONE) {
            $rawSpecs = '[]';
        }
    } else {
        $rawSpecs = '[]';
    }

    $now = date('Y-m-d H:i:s');
    try {
        Capsule::table('mod_featuredesk_specs')->updateOrInsert(
            ['product_id' => $pid],
            [
                'badge_text'       => $badge,
                'badge_color'      => $badgeColor,
                'card_highlights'  => json_encode($highlights),
                'detailed_specs'   => $rawSpecs,
                'enabled'          => 1,
                'updated_at'       => $now,
            ]
        );
    } catch (\Exception $e) {
        logActivity('FeatureDesk Save Error: ' . $e->getMessage());
    }
});

/**
 * Helper to build Matrix Data & Clean Cards
 */
if (!function_exists('featuredesk_process_cart_matrix')) {
    function featuredesk_process_cart_matrix(&$products) {
        if (!is_array($products) || empty($products)) {
            return '';
        }

        $cleanCards = (featuredesk_get_setting('clean_pricing_cards', '1') === '1');
        $showScrollBtn = (featuredesk_get_setting('show_scroll_btn', '1') === '1');
        $boxTitle = featuredesk_get_setting('box_title', 'Technical Specifications & Limit Comparison');
        $boxSubtitle = featuredesk_get_setting('box_subtitle', 'Transparent look at server resources, limits, and developer tooling across our plans.');
        $theme = featuredesk_get_setting('theme', 'modern_blue');

        $productIds = [];
        foreach ($products as $p) {
            $pId = isset($p['pid']) ? (int)$p['pid'] : (isset($p['id']) ? (int)$p['id'] : 0);
            if ($pId) {
                $productIds[] = $pId;
            }
        }

        if (empty($productIds)) {
            return '';
        }

        try {
            $specs = Capsule::table('mod_featuredesk_specs')
                ->whereIn('product_id', $productIds)
                ->where('enabled', 1)
                ->get()
                ->keyBy('product_id');
        } catch (\Exception $e) {
            return '';
        }

        if ($specs->isEmpty()) {
            return '';
        }

        $matrixColumns = [];
        $allCategories = [];

        foreach ($products as &$prod) {
            $pid = isset($prod['pid']) ? (int)$prod['pid'] : (isset($prod['id']) ? (int)$prod['id'] : 0);
            if (isset($specs[$pid])) {
                $item = $specs[$pid];
                $highlights = json_decode($item->card_highlights, true);
                if (is_array($highlights) && count($highlights) > 0) {
                    $prod['featuredesk_highlights'] = $highlights;
                    $prod['featuredesk_badge'] = $item->badge_text;

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

                $details = json_decode($item->detailed_specs, true);
                $normalizedDetails = [];

                if (is_array($details)) {
                    if (isset($details[0]) && is_array($details[0]) && isset($details[0]['group'])) {
                        foreach ($details as $row) {
                            $grp = !empty($row['group']) ? $row['group'] : 'General';
                            $feat = !empty($row['name']) ? $row['name'] : 'Feature';
                            $val = isset($row['value']) ? $row['value'] : '';
                            $normalizedDetails[$grp][$feat] = $val;
                        }
                    } else {
                        $normalizedDetails = $details;
                    }

                    $priceStr = '';
                    if (isset($prod['pricing']['minprice']['price'])) {
                        $priceStr = $prod['pricing']['minprice']['price'];
                        if (isset($prod['pricing']['minprice']['cycle'])) {
                            $priceStr .= ' <small>/ ' . $prod['pricing']['minprice']['cycle'] . '</small>';
                        }
                    } elseif (isset($prod['pricing']['rawpricing']['monthly'])) {
                        $priceStr = $prod['pricing']['rawpricing']['monthly'];
                    }

                    $matrixColumns[$pid] = [
                        'name'    => $prod['name'],
                        'price'   => $priceStr,
                        'order'   => isset($prod['orderurl']) ? $prod['orderurl'] : (isset($prod['productUrl']) ? $prod['productUrl'] : ''),
                        'details' => $normalizedDetails,
                    ];

                    foreach ($normalizedDetails as $categoryName => $catFeatures) {
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

        if (count($matrixColumns) === 0) {
            return '';
        }

        $matrixHtml = '<div id="featuredesk-matrix-box" class="fd-specbox fd-theme-' . featuredesk_h($theme) . '">';
        $matrixHtml .= '  <div class="fd-specbox-header">';
        $matrixHtml .= '    <h3 class="fd-specbox-title"><i class="fas fa-microchip" style="margin-right:8px;"></i>' . featuredesk_h($boxTitle) . '</h3>';
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
            if (!empty($col['order'])) {
                $matrixHtml .= '  <a href="' . featuredesk_h($col['order']) . '" class="btn btn-primary btn-sm fd-btn-choose">Order Plan</a>';
            }
            $matrixHtml .= '    </th>';
        }
        $matrixHtml .= '      </tr></thead><tbody>';

        foreach ($allCategories as $categoryName => $featureKeys) {
            $matrixHtml .= '    <tr class="fd-category-row"><td colspan="' . (count($matrixColumns) + 1) . '"><i class="fas fa-folder-open" style="margin-right:6px;"></i> ' . featuredesk_h($categoryName) . '</td></tr>';
            foreach ($featureKeys as $featureKey) {
                $matrixHtml .= '  <tr class="fd-feature-row">';
                $matrixHtml .= '    <td class="fd-feature-label">' . featuredesk_h($featureKey) . '</td>';
                foreach ($matrixColumns as $col) {
                    $val = isset($col['details'][$categoryName][$featureKey]) ? $col['details'][$categoryName][$featureKey] : '&mdash;';
                    if (strtolower(trim($val)) === 'yes' || strtolower(trim($val)) === 'true' || strtolower(trim($val)) === 'enabled') {
                        $cellContent = '<span class="fd-val-yes"><i class="fas fa-check-circle"></i> Yes</span>';
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

        $matrixHtml .= '      </tbody></table>';
        $matrixHtml .= '  </div>';
        $matrixHtml .= '</div>';

        return $matrixHtml;
    }
}

/**
 * Hook 3: Process Cart Products in ClientAreaPageCart & ClientAreaPage
 */
add_hook('ClientAreaPageCart', 1, function ($vars) {
    if (isset($vars['products']) && is_array($vars['products'])) {
        $matrixHtml = featuredesk_process_cart_matrix($vars['products']);
        if (!empty($matrixHtml)) {
            $GLOBALS['featuredesk_matrix_html'] = $matrixHtml;
            return [
                'products'           => $vars['products'],
                'featuredesk_matrix' => $matrixHtml,
            ];
        }
    }
    return [];
});

add_hook('ClientAreaPage', 1, function ($vars) {
    if (isset($vars['products']) && is_array($vars['products']) && empty($GLOBALS['featuredesk_matrix_html'])) {
        $matrixHtml = featuredesk_process_cart_matrix($vars['products']);
        if (!empty($matrixHtml)) {
            $GLOBALS['featuredesk_matrix_html'] = $matrixHtml;
            return [
                'products'           => $vars['products'],
                'featuredesk_matrix' => $matrixHtml,
            ];
        }
    }
    return [];
});

/**
 * Hook 4: Inject Head CSS Styles
 */
add_hook('ClientAreaHeadOutput', 1, function ($vars) {
    return '
    <style id="featuredesk-styles">
        .fd-card-bullets {
            list-style: none !important;
            padding: 0 !important;
            margin: 15px 0 !important;
            text-align: left !important;
        }
        .fd-card-bullets li {
            padding: 6px 0 !important;
            font-size: 13.5px !important;
            color: #334155 !important;
            display: flex !important;
            align-items: center !important;
            border-bottom: 1px dashed #f1f5f9 !important;
            line-height: 1.4 !important;
        }
        .fd-card-bullets li:last-child {
            border-bottom: none !important;
        }
        .fd-icon-check {
            color: #10B981 !important;
            margin-right: 10px !important;
            font-size: 14px !important;
            flex-shrink: 0 !important;
        }
        .fd-scroll-wrap {
            text-align: center;
            margin: 15px 0 5px;
        }
        .fd-scroll-link {
            font-size: 12px;
            font-weight: 600;
            color: #2563EB;
            text-decoration: underline;
            transition: color 0.2s;
        }
        .fd-scroll-link:hover {
            color: #1d4ed8;
        }
        .fd-specbox {
            margin: 40px auto 30px;
            max-width: 1200px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            overflow: hidden;
            font-family: inherit;
        }
        .fd-specbox-header {
            padding: 24px 28px;
            border-bottom: 1px solid #f1f5f9;
            background: #fafafa;
        }
        .fd-specbox-title {
            margin: 0 0 4px 0;
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
        }
        .fd-specbox-subtitle {
            margin: 0;
            font-size: 13.5px;
            color: #64748b;
        }
        .fd-table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .fd-spec-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13.5px;
        }
        .fd-spec-table th, .fd-spec-table td {
            padding: 12px 18px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .fd-spec-table thead th {
            background: #ffffff;
            font-weight: 600;
            color: #334155;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .fd-col-feature {
            width: 30%;
            min-width: 200px;
            color: #1e293b;
        }
        .fd-col-plan {
            text-align: center;
            min-width: 140px;
        }
        .fd-plan-name {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .fd-plan-price {
            font-size: 14px;
            font-weight: 600;
            color: #2563EB;
            margin-bottom: 8px;
        }
        .fd-btn-choose {
            font-size: 11.5px;
            padding: 4px 12px;
            border-radius: 6px;
        }
        .fd-category-row td {
            background: #f8fafc;
            font-weight: 700;
            font-size: 13px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 10px 18px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .fd-feature-row:hover td {
            background-color: #f8fafc;
        }
        .fd-feature-label {
            color: #334155;
            font-weight: 500;
        }
        .fd-feature-value {
            text-align: center;
            color: #0f172a;
        }
        .fd-val-yes {
            color: #10B981;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .fd-val-no {
            color: #94A3B8;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .fd-val-text {
            font-weight: 600;
            color: #1e293b;
        }
        .fd-theme-modern_blue {
            border-top: 4px solid #2563EB;
        }
        .fd-theme-modern_blue .fd-specbox-title {
            color: #1d4ed8;
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

    if (empty($GLOBALS['featuredesk_matrix_html'])) {
        return '';
    }

    $rawMatrixHtml = $GLOBALS['featuredesk_matrix_html'];
    $jsonHtml = json_encode($rawMatrixHtml);

    return '
    <script id="featuredesk-injector">
    (function() {
        function injectFeatureDesk() {
            if (document.getElementById("featuredesk-matrix-box")) return;

            var html = ' . $jsonHtml . ';
            var wrapper = document.createElement("div");
            wrapper.innerHTML = html;
            var node = wrapper.firstElementChild;

            // Lagom 2 and WHMCS container targets
            var target = document.querySelector(".section.products") ||
                         document.querySelector(".row.row-eq-height") ||
                         document.querySelector(".packages-list") ||
                         document.querySelector(".products-row") ||
                         (document.querySelector(".package") ? document.querySelector(".package").closest(".row") : null) ||
                         document.querySelector(".main-content");

            if (target) {
                target.parentNode.insertBefore(node, target.nextSibling);
            } else {
                var body = document.querySelector(".app-main") || document.body;
                body.appendChild(node);
            }
        }

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", injectFeatureDesk);
        } else {
            injectFeatureDesk();
        }
    })();
    </script>';
});
