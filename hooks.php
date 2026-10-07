<?php
/**
 * WHMCS Addon Module: FeatureDesk - Client Area Hooks
 *
 * Injects Category-based Feature Spec Boxes, Server Backup Notices,
 * and Dynamic HTML Blocks directly below product cards in WHMCS order forms.
 *
 * STRICT RULE: Leaves native pricing cards and product descriptions 100% UNTOUCHED.
 *
 * @version 2.0.0
 * @author Samsuzzaman Siyam
 * @website https://baharihost.com
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

if (!function_exists('featuredesk_get_setting')) {
    function featuredesk_get_setting($key, $default = '')
    {
        try {
            if (Capsule::schema()->hasTable('mod_featuredesk_settings')) {
                $row = Capsule::table('mod_featuredesk_settings')->where('setting', $key)->first();
                if ($row && $row->value !== null && $row->value !== '') {
                    return $row->value;
                }
            }
        } catch (\Exception $e) {}
        return $default;
    }
}

if (!function_exists('featuredesk_is_enabled')) {
    function featuredesk_is_enabled($val)
    {
        return in_array(strtolower(trim((string)$val)), ['1', 'on', 'true', 'yes'], true);
    }
}

if (!function_exists('featuredesk_h')) {
    function featuredesk_h($str) {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Head CSS Injection: Scoped styles & responsive layouts
 */
add_hook('ClientAreaHeadOutput', 1, function ($vars) {
    $maxWidth  = featuredesk_get_setting('container_max_width', '1200px');
    $primary   = featuredesk_get_setting('color_primary', '#0284c7');
    $globalCss = featuredesk_get_setting('global_custom_css', '');

    $css = '
    <style id="featuredesk-styles">
        :root {
            --fd-max-w: ' . featuredesk_h($maxWidth) . ';
            --fd-primary: ' . featuredesk_h($primary) . ';
            --fd-green: #10b981;
            --fd-border: #e2e8f0;
            --fd-bg-card: #ffffff;
            --fd-text-main: #1e293b;
            --fd-text-muted: #64748b;
        }

        /* Container under product cards */
        .fd-category-wrapper {
            max-width: var(--fd-max-w);
            margin: 40px auto 30px auto;
            padding: 0 15px;
            box-sizing: border-box;
            clear: both;
            font-family: inherit;
        }

        /* Base Card Styling */
        .fd-card {
            background: var(--fd-bg-card);
            border: 1px solid var(--fd-border);
            border-radius: 12px;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            padding: 28px 32px;
            margin-bottom: 24px;
            box-sizing: border-box;
            transition: all 0.2s ease-in-out;
        }
        .fd-card:hover {
            box-shadow: 0 10px 25px -3px rgba(15, 23, 42, 0.08);
        }

        /* Section Header */
        .fd-card-header {
            margin-bottom: 22px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .fd-card-title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .fd-card-subtitle {
            margin: 4px 0 0 0;
            font-size: 13.5px;
            color: var(--fd-text-muted);
        }

        /* Responsive 4-Column Feature Grid (Matching Screenshot) */
        .fd-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px 24px;
            box-sizing: border-box;
        }
        .fd-grid-col {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .fd-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 13.5px;
            line-height: 1.45;
            color: #334155;
            font-weight: 500;
        }
        .fd-item svg, .fd-item .fd-check {
            width: 17px;
            height: 17px;
            flex-shrink: 0;
            margin-top: 1px;
            color: var(--fd-green);
        }

        /* Server Backup / Advisory Notice Card */
        .fd-notice-card {
            background: #fffbeb;
            border: 1px solid #fef08a;
            border-left: 5px solid #eab308;
            border-radius: 10px;
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            box-sizing: border-box;
        }
        .fd-notice-card.fd-notice-info {
            background: #eff6ff;
            border-color: #bfdbfe;
            border-left-color: #3b82f6;
        }
        .fd-notice-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            margin-top: 2px;
            color: #ca8a04;
        }
        .fd-notice-info .fd-notice-icon {
            color: #2563eb;
        }
        .fd-notice-body {
            flex: 1;
        }
        .fd-notice-title {
            margin: 0 0 6px 0;
            font-size: 15px;
            font-weight: 700;
            color: #854d0e;
        }
        .fd-notice-info .fd-notice-title {
            color: #1e40af;
        }
        .fd-notice-text {
            margin: 0;
            font-size: 13.5px;
            line-height: 1.55;
            color: #713f12;
        }
        .fd-notice-info .fd-notice-text {
            color: #1e3a8a;
        }

        /* Extra Custom Blocks */
        .fd-extra-card {
            background: #ffffff;
            border: 1px solid var(--fd-border);
            border-radius: 12px;
            padding: 24px 28px;
            margin-bottom: 24px;
            box-sizing: border-box;
        }

        /* Tablet Breakpoint (2 columns) */
        @media (max-width: 991px) {
            .fd-grid-4 {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px 20px;
            }
            .fd-card {
                padding: 22px 24px;
            }
        }

        /* Mobile Breakpoint (1 column) */
        @media (max-width: 600px) {
            .fd-category-wrapper {
                margin: 25px auto 15px auto;
                padding: 0 10px;
            }
            .fd-grid-4 {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            .fd-card {
                padding: 18px 18px;
                border-radius: 10px;
            }
            .fd-card-title {
                font-size: 17.5px;
            }
            .fd-item {
                font-size: 13px;
            }
            .fd-notice-card {
                padding: 15px 16px;
                gap: 12px;
                flex-direction: column;
            }
        }
    </style>';

    if (!empty($globalCss)) {
        $css .= '<style id="featuredesk-custom-css">' . $globalCss . '</style>';
    }

    return $css;
});

/**
 * Footer Injection: Detects Category (gid) and dynamically renders HTML sections below pricing cards
 */
add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    // 1. Check if FeatureDesk is globally enabled
    if (!featuredesk_is_enabled(featuredesk_get_setting('status', '1'))) {
        return '';
    }

    // 2. Identify Product Group (gid)
    $gid = null;
    if (!empty($vars['gid'])) {
        $gid = (int)$vars['gid'];
    } elseif (isset($_GET['gid']) && is_numeric($_GET['gid'])) {
        $gid = (int)$_GET['gid'];
    } elseif (isset($_REQUEST['gid']) && is_numeric($_REQUEST['gid'])) {
        $gid = (int)$_REQUEST['gid'];
    }

    // Attempt to extract gid from WHMCS product groups in template vars
    if (!$gid && !empty($vars['productGroup']->id)) {
        $gid = (int)$vars['productGroup']->id;
    }

    if (!$gid) {
        return '';
    }

    try {
        if (!Capsule::schema()->hasTable('mod_featuredesk_categories')) {
            return '';
        }

        $groupConfig = Capsule::table('mod_featuredesk_categories')
            ->where('group_id', $gid)
            ->where('status', 1)
            ->first();

        if (!$groupConfig) {
            return '';
        }
    } catch (\Exception $e) {
        return '';
    }

    $featuresHtml = trim((string)$groupConfig->features_html);
    $backupNoticeHtml = trim((string)$groupConfig->backup_notice_html);
    $customCss = trim((string)$groupConfig->custom_css);

    $extraBoxes = [];
    if (!empty($groupConfig->extra_boxes)) {
        $decoded = json_decode($groupConfig->extra_boxes, true);
        if (is_array($decoded)) {
            $extraBoxes = $decoded;
        }
    }

    // If all sections are empty, do not inject anything
    if (empty($featuresHtml) && empty($backupNoticeHtml) && empty($extraBoxes)) {
        return '';
    }

    // Build the rendered HTML
    $output = '';

    // Per-category custom CSS if present
    if (!empty($customCss)) {
        $output .= '<style id="fd-group-' . $gid . '-css">' . $customCss . '</style>';
    }

    $output .= '<div id="featuredesk-category-wrapper" class="fd-category-wrapper" data-gid="' . (int)$gid . '">';

    // 1. Main Features HTML Box (e.g. 4-column checklist)
    if (!empty($featuresHtml)) {
        $output .= '<div class="fd-features-block">' . $featuresHtml . '</div>';
    }

    // 2. Server Backup Notice HTML Box
    if (!empty($backupNoticeHtml)) {
        $output .= '<div class="fd-backup-notice-block">' . $backupNoticeHtml . '</div>';
    }

    // 3. Dynamic Extra HTML Boxes
    if (!empty($extraBoxes)) {
        foreach ($extraBoxes as $box) {
            if (!empty($box['status']) && !empty($box['html'])) {
                $output .= '<div class="fd-extra-card">';
                if (!empty($box['title'])) {
                    $output .= '<div class="fd-card-header"><h3 class="fd-card-title">' . htmlspecialchars($box['title']) . '</h3></div>';
                }
                $output .= '<div class="fd-extra-content">' . $box['html'] . '</div>';
                $output .= '</div>';
            }
        }
    }

    $output .= '</div>';

    // Safe JSON encoded payload for client-side injection
    $payloadJson = json_encode($output);

    return '
    <script id="featuredesk-runtime">
    (function() {
        var FD_HTML = ' . $payloadJson . ';

        function injectFeatureDesk() {
            if (document.getElementById("featuredesk-category-wrapper")) {
                return; // Already injected
            }

            // Target element below pricing cards
            // Covers Standard Cart, Supreme, Modern, Universal, Lagom, Twenty-One, Six
            var target = document.querySelector(".products") ||
                         document.querySelector(".product-grid") ||
                         document.querySelector(".products-wrapper") ||
                         document.querySelector(".row.row-eq-height") ||
                         document.querySelector(".packages-list") ||
                         document.querySelector(".products-row") ||
                         document.querySelector(".cart-body") ||
                         (document.querySelector(".package") ? document.querySelector(".package").closest(".row") : null) ||
                         (document.querySelector(".product") ? document.querySelector(".product").closest(".row") : null) ||
                         document.querySelector("#order-standard_cart") ||
                         document.querySelector(".main-content");

            var tempDiv = document.createElement("div");
            tempDiv.innerHTML = FD_HTML;
            var nodeToInject = tempDiv.firstElementChild;

            if (target && target.parentNode) {
                target.parentNode.insertBefore(nodeToInject, target.nextSibling);
            } else {
                var contentArea = document.querySelector("main") || document.querySelector("#main-body") || document.body;
                contentArea.appendChild(nodeToInject);
            }
        }

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", injectFeatureDesk);
        } else {
            injectFeatureDesk();
        }

        setTimeout(injectFeatureDesk, 400);
        setTimeout(injectFeatureDesk, 1200);
    })();
    </script>';
});