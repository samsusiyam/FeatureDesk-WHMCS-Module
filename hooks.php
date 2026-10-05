<?php
/**
 * FeatureDesk - Smart Spec Box & Plan Matrix
 * Client Area Hooks: DOM Injection, Spec Comparison Box, Dynamic Styles
 *
 * @version 1.3.0
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
 * Head CSS Injection with Dynamic Color Customization
 */
add_hook('ClientAreaHeadOutput', 1, function ($vars) {
    // Dynamic Custom Colors from Display Settings
    $colorPrimary      = featuredesk_get_setting('color_primary', '#0284c7');
    $colorBulletIcon   = featuredesk_get_setting('color_bullet_icon', '#10B981');
    $colorBulletText   = featuredesk_get_setting('color_bullet_text', '#334155');
    $colorHeaderBg     = featuredesk_get_setting('color_header_bg', '#fafafa');
    $colorHeaderTitle  = featuredesk_get_setting('color_header_title', '#0f172a');
    $colorCategoryBg   = featuredesk_get_setting('color_category_bg', '#f8fafc');
    $colorCategoryText = featuredesk_get_setting('color_category_text', '#475569');
    $colorRowHover     = featuredesk_get_setting('color_row_hover', '#f8fafc');
    $colorBorder       = featuredesk_get_setting('color_border', '#e2e8f0');

    return '
    <style id="featuredesk-styles">
        :root {
            --fd-primary: ' . featuredesk_h($colorPrimary) . ';
            --fd-bullet-icon: ' . featuredesk_h($colorBulletIcon) . ';
            --fd-bullet-text: ' . featuredesk_h($colorBulletText) . ';
            --fd-header-bg: ' . featuredesk_h($colorHeaderBg) . ';
            --fd-header-title: ' . featuredesk_h($colorHeaderTitle) . ';
            --fd-category-bg: ' . featuredesk_h($colorCategoryBg) . ';
            --fd-category-text: ' . featuredesk_h($colorCategoryText) . ';
            --fd-row-hover: ' . featuredesk_h($colorRowHover) . ';
            --fd-border: ' . featuredesk_h($colorBorder) . ';
        }
        .fd-card-bullets {
            list-style: none !important;
            padding: 0 !important;
            margin: 14px 0 10px 0 !important;
            text-align: left !important;
        }
        .fd-card-bullets li {
            padding: 6px 0 !important;
            font-size: 13.5px !important;
            color: var(--fd-bullet-text) !important;
            display: flex !important;
            align-items: center !important;
            border-bottom: 1px dashed #f1f5f9 !important;
            line-height: 1.4 !important;
        }
        .fd-card-bullets li:last-child {
            border-bottom: none !important;
        }
        .fd-icon-check {
            color: var(--fd-bullet-icon) !important;
            margin-right: 10px !important;
            font-size: 14px !important;
            flex-shrink: 0 !important;
        }
        .fd-custom-html-highlights {
            text-align: left !important;
            margin: 14px 0 10px 0 !important;
            font-size: 13.5px !important;
            color: var(--fd-bullet-text) !important;
        }
        .fd-scroll-wrap {
            text-align: center;
            margin: 14px 0 10px;
        }
        .fd-scroll-link {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--fd-primary);
            text-decoration: underline;
            transition: color 0.2s;
        }
        .fd-scroll-link:hover {
            color: #0369a1;
        }
        .fd-card-custom-box-top {
            margin-bottom: 12px;
        }
        .fd-card-custom-box-bottom {
            margin-top: 14px;
        }
        .fd-specbox {
            margin: 40px auto 30px;
            max-width: 1200px;
            background: #ffffff;
            border: 1px solid var(--fd-border);
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            overflow: hidden;
            font-family: inherit;
        }
        .fd-specbox-header {
            padding: 24px 28px;
            border-bottom: 1px solid var(--fd-border);
            background: var(--fd-header-bg);
        }
        .fd-specbox-title {
            margin: 0 0 4px 0;
            font-size: 20px;
            font-weight: 700;
            color: var(--fd-header-title);
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
            color: var(--fd-primary);
            margin-bottom: 8px;
        }
        .fd-category-row td {
            background: var(--fd-category-bg) !important;
            font-weight: 700;
            font-size: 13px;
            color: var(--fd-category-text) !important;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 10px 18px;
            border-top: 1px solid var(--fd-border);
            border-bottom: 1px solid var(--fd-border);
        }
        .fd-feature-row:hover td {
            background-color: var(--fd-row-hover) !important;
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
            color: var(--fd-bullet-icon);
            font-weight: 700;
        }
        .fd-val-no {
            color: #ef4444;
            font-weight: 600;
        }
        .fd-val-text {
            color: #1e293b;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .fd-specbox {
                margin: 20px 10px;
                border-radius: 8px;
            }
            .fd-specbox-header {
                padding: 16px 20px;
            }
            .fd-spec-table th, .fd-spec-table td {
                padding: 10px 12px;
                font-size: 12.5px;
            }
        }
    </style>';
});

/**
 * Footer Injection: Dynamic Cards Cleansing & Specification Matrix Rendering
 */
add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    if (empty($vars['templatefile']) || !in_array($vars['templatefile'], ['products', 'viewcart', 'configureproduct'])) {
        $reqUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (strpos($reqUri, '/store/') === false && strpos($reqUri, 'cart.php') === false) {
            return '';
        }
    }

    try {
        if (!Capsule::schema()->hasTable('mod_featuredesk_specs')) {
            return '';
        }

        $specs = Capsule::table('mod_featuredesk_specs')
            ->join('tblproducts', 'tblproducts.id', '=', 'mod_featuredesk_specs.product_id')
            ->where('mod_featuredesk_specs.enabled', 1)
            ->select(
                'mod_featuredesk_specs.*',
                'tblproducts.name as pname',
                'tblproducts.gid as gid',
                'tblproducts.id as pid'
            )
            ->get();

        if ($specs->isEmpty()) {
            return '';
        }
    } catch (\Exception $e) {
        return '';
    }

    $productMap = [];
    foreach ($specs as $s) {
        // Raw highlights (could be array of lines or raw HTML string)
        $hList = [];
        $rawHighlights = (string)$s->card_highlights;
        $isHtmlHighlights = false;
        
        $decodedH = json_decode($rawHighlights, true);
        if (is_array($decodedH)) {
            $hList = $decodedH;
        } elseif (!empty($rawHighlights)) {
            $hList = array_filter(array_map('trim', explode("\n", $rawHighlights)));
        }

        // Check if highlights is custom HTML block
        $joinedH = is_array($hList) ? implode("", $hList) : (string)$rawHighlights;
        if (strpos($joinedH, '<div') !== false || strpos($joinedH, '<ul') !== false || strpos($joinedH, '<table') !== false || strpos($joinedH, '<p') !== false) {
            $isHtmlHighlights = true;
        }

        // Detailed Specs
        $dList = json_decode($s->detailed_specs, true);
        $normDetails = [];
        if (is_array($dList)) {
            foreach ($dList as $item) {
                if (isset($item['group']) && isset($item['name'])) {
                    $g = $item['group'];
                    $n = $item['name'];
                    $v = isset($item['value']) ? $item['value'] : '';
                    $gIcon = isset($item['group_icon']) ? $item['group_icon'] : '';
                    $fIcon = isset($item['icon']) ? $item['icon'] : '';
                    $normDetails[$g][$n] = [
                        'val'       => $v,
                        'cat_icon'  => $gIcon,
                        'feat_icon' => $fIcon
                    ];
                }
            }
        }

        $productMap[$s->pid] = [
            'pid'                => (int)$s->pid,
            'gid'                => (int)$s->gid,
            'name'               => $s->pname,
            'highlights'         => $hList,
            'is_html_highlights' => $isHtmlHighlights,
            'top_html'           => (string)$s->card_top_html,
            'bottom_html'        => (string)$s->card_bottom_html,
            'specs'              => $normDetails,
        ];
    }

    $cleanCards      = featuredesk_is_enabled(featuredesk_get_setting('clean_pricing_cards', '1'));
    $showScroll      = featuredesk_is_enabled(featuredesk_get_setting('show_scroll_btn', '1'));
    $bulletIconClass = featuredesk_get_setting('bullet_icon_class', 'fas fa-check-circle');
    $boxTitle        = featuredesk_get_setting('box_title', 'Technical Specifications & Limit Comparison');
    $boxSubtitle     = featuredesk_get_setting('box_subtitle', 'Transparent look at server resources, limits, and developer tooling across our plans.');
    $theme           = featuredesk_get_setting('theme', 'modern_blue');

    $payload = json_encode([
        'products'          => $productMap,
        'clean_cards'       => $cleanCards,
        'show_scroll'       => $showScroll,
        'bullet_icon_class' => $bulletIconClass,
        'title'             => $boxTitle,
        'subtitle'          => $boxSubtitle,
        'theme'             => $theme,
    ]);

    return '
    <script id="featuredesk-runtime">
    (function() {
        var FD_DATA = ' . $payload . ';

        function initFeatureDesk() {
            var cards = document.querySelectorAll(".package, .product-card, .price-table");
            if (!cards.length) return;

            var matchedPlans = [];

            cards.forEach(function(card) {
                var titleEl = card.querySelector(".package-title, .product-title, h3, h4");
                if (!titleEl) return;

                var titleText = titleEl.innerText.trim().toLowerCase();
                var matched = null;

                for (var pid in FD_DATA.products) {
                    var p = FD_DATA.products[pid];
                    if (titleText.includes(p.name.toLowerCase()) || p.name.toLowerCase().includes(titleText)) {
                        matched = p;
                        break;
                    }
                }

                if (!matched) return;
                matchedPlans.push(matched);

                // Replace inside card content ONLY if highlights or custom boxes exist
                var hasCustomContent = (matched.highlights && matched.highlights.length > 0) ||
                                       (matched.top_html && matched.top_html.trim() !== "") ||
                                       (matched.bottom_html && matched.bottom_html.trim() !== "");

                // 1. Clean Long Descriptions (Hero Highlights & Custom Top/Bottom Boxes)
                if (FD_DATA.clean_cards && hasCustomContent) {
                    var contentEl = card.querySelector(".package-content") || card.querySelector(".product-desc") || card.querySelector(".package-body");
                    if (contentEl && !card.querySelector(".fd-card-bullets") && !card.querySelector(".fd-card-custom-box-top") && !card.querySelector(".fd-custom-html-highlights")) {
                        var html = "";

                        // 1. Custom Top HTML Box (e.g. Free Domain Offer Box)
                        if (matched.top_html && matched.top_html.trim() !== "") {
                            html += "<div class=\"fd-card-custom-box-top\">" + matched.top_html + "</div>";
                        }

                        // 2. Highlights (Supports Text Lines with optional/custom icons OR Direct Custom HTML)
                        if (matched.highlights && matched.highlights.length > 0) {
                            if (matched.is_html_highlights) {
                                html += "<div class=\"fd-custom-html-highlights\">" + matched.highlights.join("\n") + "</div>";
                            } else {
                                html += "<ul class=\"fd-card-bullets\">";
                                matched.highlights.forEach(function(item) {
                                    var hasExistingIcon = (item.indexOf("<i") !== -1 || item.indexOf("<svg") !== -1 || item.indexOf("icon") !== -1 || item.indexOf("<img") !== -1);
                                    if (hasExistingIcon || !FD_DATA.bullet_icon_class || FD_DATA.bullet_icon_class === "none") {
                                        html += "<li>" + item + "</li>";
                                    } else {
                                        html += "<li><i class=\"" + FD_DATA.bullet_icon_class + " fd-icon-check\"></i> " + item + "</li>";
                                    }
                                });
                                html += "</ul>";
                            }
                        }

                        // 3. Custom Bottom HTML Box (e.g. Backup Policy Warning Box)
                        if (matched.bottom_html && matched.bottom_html.trim() !== "") {
                            html += "<div class=\"fd-card-custom-box-bottom\">" + matched.bottom_html + "</div>";
                        }

                        contentEl.innerHTML = html;
                    }
                }

                // 2. Card \'View Specs\' Link (Completely decoupled & independent of Clean Long Descriptions!)
                if (FD_DATA.show_scroll && matched.specs && Object.keys(matched.specs).length > 0 && !card.querySelector(".fd-scroll-wrap")) {
                    var scrollDiv = document.createElement("div");
                    scrollDiv.className = "fd-scroll-wrap";
                    scrollDiv.innerHTML = "<a href=\"#featuredesk-matrix-box\" class=\"fd-scroll-link\">View Full Tech Specs &darr;</a>";

                    var botBox = card.querySelector(".fd-card-custom-box-bottom");
                    var bullets = card.querySelector(".fd-card-bullets, .fd-custom-html-highlights");
                    var footerEl = card.querySelector(".package-footer, .order-button, .btn-order, .package-actions");
                    var cEl = card.querySelector(".package-content") || card.querySelector(".product-desc") || card.querySelector(".package-body");

                    if (botBox) {
                        botBox.parentNode.insertBefore(scrollDiv, botBox);
                    } else if (bullets) {
                        bullets.parentNode.insertBefore(scrollDiv, bullets.nextSibling);
                    } else if (cEl) {
                        cEl.appendChild(scrollDiv);
                    } else if (footerEl) {
                        footerEl.parentNode.insertBefore(scrollDiv, footerEl);
                    } else {
                        card.appendChild(scrollDiv);
                    }
                }
            });

            // 5. Bottom Spec Matrix Table
            if (matchedPlans.length > 0 && !document.getElementById("featuredesk-matrix-box")) {
                var allCats = {};
                var catIcons = {};
                var featIcons = {};

                matchedPlans.forEach(function(p) {
                    for (var cat in p.specs) {
                        if (!allCats[cat]) allCats[cat] = [];
                        for (var feat in p.specs[cat]) {
                            var specObj = p.specs[cat][feat];
                            if (specObj && typeof specObj === "object") {
                                if (specObj.cat_icon && !catIcons[cat]) catIcons[cat] = specObj.cat_icon;
                                if (specObj.feat_icon && !featIcons[cat + ":::" + feat]) featIcons[cat + ":::" + feat] = specObj.feat_icon;
                            }
                            if (allCats[cat].indexOf(feat) === -1) {
                                allCats[cat].push(feat);
                            }
                        }
                    }
                });

                var mHtml = "<div id=\"featuredesk-matrix-box\" class=\"fd-specbox fd-theme-" + FD_DATA.theme + "\">";
                mHtml += "<div class=\"fd-specbox-header\">";
                mHtml += "<h3 class=\"fd-specbox-title\"><i class=\"fas fa-microchip\" style=\"margin-right:8px; color:var(--fd-primary);\"></i>" + FD_DATA.title + "</h3>";
                mHtml += "<p class=\"fd-specbox-subtitle\">" + FD_DATA.subtitle + "</p>";
                mHtml += "</div>";

                mHtml += "<div class=\"fd-table-responsive\"><table class=\"fd-spec-table\">";
                mHtml += "<thead><tr><th class=\"fd-col-feature\">Specification & Limit</th>";
                matchedPlans.forEach(function(p) {
                    mHtml += "<th class=\"fd-col-plan\"><div class=\"fd-plan-name\">" + p.name + "</div></th>";
                });
                mHtml += "</tr></thead><tbody>";

                for (var cat in allCats) {
                    var cIcon = catIcons[cat] ? catIcons[cat] : "fas fa-folder-open";
                    mHtml += "<tr class=\"fd-category-row\"><td colspan=\"" + (matchedPlans.length + 1) + "\"><i class=\"" + cIcon + "\" style=\"margin-right:8px; opacity:0.85;\"></i> " + cat + "</td></tr>";
                    allCats[cat].forEach(function(feat) {
                        var fIcon = featIcons[cat + ":::" + feat];
                        var fIconHtml = fIcon ? "<i class=\"" + fIcon + "\" style=\"margin-right:8px; opacity:0.8; color:var(--fd-primary);\"></i> " : "";
                        mHtml += "<tr class=\"fd-feature-row\">";
                        mHtml += "<td class=\"fd-feature-label\">" + fIconHtml + feat + "</td>";
                        matchedPlans.forEach(function(p) {
                            var specVal = (p.specs[cat] && p.specs[cat][feat]) ? (typeof p.specs[cat][feat] === "object" ? p.specs[cat][feat].val : p.specs[cat][feat]) : "—";
                            if (!specVal || specVal.trim() === "") specVal = "—";
                            var valLow = specVal.toLowerCase().trim();
                            var cell = "<span class=\"fd-val-text\">" + specVal + "</span>";
                            if (valLow === "yes" || valLow === "true" || valLow === "enabled") {
                                cell = "<span class=\"fd-val-yes\"><i class=\"fas fa-check-circle\"></i> Yes</span>";
                            } else if (valLow === "no" || valLow === "false" || valLow === "disabled") {
                                cell = "<span class=\"fd-val-no\"><i class=\"fas fa-times-circle\"></i> No</span>";
                            }
                            mHtml += "<td class=\"fd-feature-value\">" + cell + "</td>";
                        });
                        mHtml += "</tr>";
                    });
                }

                mHtml += "</tbody></table></div></div>";

                var wrapper = document.createElement("div");
                wrapper.innerHTML = mHtml;
                var boxNode = wrapper.firstElementChild;

                var target = document.querySelector(".section.products") ||
                             document.querySelector(".row.row-eq-height") ||
                             document.querySelector(".packages-list") ||
                             document.querySelector(".products-row") ||
                             (document.querySelector(".package") ? document.querySelector(".package").closest(".row") : null) ||
                             document.querySelector(".main-content");

                if (target) {
                    target.parentNode.insertBefore(boxNode, target.nextSibling);
                } else {
                    document.body.appendChild(boxNode);
                }
            }
        }

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", initFeatureDesk);
        } else {
            initFeatureDesk();
        }
        setTimeout(initFeatureDesk, 500);
        setTimeout(initFeatureDesk, 1500);
    })();
    </script>';
});
