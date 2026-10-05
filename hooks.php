<?php
/**
 * WHMCS Addon Module: FeatureDesk - Smart Spec Box & Plan Matrix
 * Hooks File
 *
 * Leaves pricing card headers untouched.
 * Inside card body / description:
 * 1. Custom Top HTML Box (e.g. Domain Promo Box)
 * 2. Clean Hero Bullet Badges + "View Full Tech Specs ↓"
 * 3. Custom Bottom HTML Box (e.g. Backup Policy Alert Box)
 * Below Cards:
 * 4. Responsive Technical Specifications Comparison Matrix Box
 *
 * @package    FeatureDesk
 * @author     MD Samsuzzaman Siyam <samsusiyam@gmail.com>
 * @copyright  Bahari Host
 */

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (!function_exists('featuredesk_is_enabled')) {
    function featuredesk_is_enabled($val)
    {
        return in_array(strtolower(trim((string)$val)), ['1', 'on', 'true', 'yes'], true);
    }
}

if (!function_exists('featuredesk_get_setting')) {
    function featuredesk_get_setting($key, $default = '') {
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

if (!function_exists('featuredesk_h')) {
    function featuredesk_h($str) {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Head CSS Injection
 */
add_hook('ClientAreaHeadOutput', 1, function ($vars) {
    return '
    <style id="featuredesk-styles">
        .fd-card-bullets {
            list-style: none !important;
            padding: 0 !important;
            margin: 14px 0 10px 0 !important;
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
            margin: 14px 0 10px;
        }
        .fd-scroll-link {
            font-size: 12.5px;
            font-weight: 700;
            color: #0284c7;
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
            color: #0284c7;
            margin-bottom: 8px;
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
            border-top: 4px solid #0284c7;
        }
        .fd-theme-modern_blue .fd-specbox-title {
            color: #0369a1;
        }
    </style>';
});

/**
 * Client Footer Output:
 * Replaces cluttered content with Top Box + Clean Bullets + Bottom Box, and appends Spec Matrix
 */
add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    try {
        if (!Capsule::schema()->hasTable('mod_featuredesk_specs')) {
            return '';
        }

        $allSpecs = Capsule::table('mod_featuredesk_specs')
            ->join('tblproducts', 'mod_featuredesk_specs.product_id', '=', 'tblproducts.id')
            ->select(
                'tblproducts.id as pid',
                'tblproducts.gid',
                'tblproducts.name as pname',
                'mod_featuredesk_specs.card_highlights',
                'mod_featuredesk_specs.detailed_specs',
                'mod_featuredesk_specs.card_top_html',
                'mod_featuredesk_specs.card_bottom_html'
            )
            ->where('mod_featuredesk_specs.enabled', 1)
            ->get();
    } catch (\Exception $e) {
        return '';
    }

    if ($allSpecs->isEmpty()) {
        return '';
    }

    $productMap = [];
    foreach ($allSpecs as $s) {
        $hList = json_decode($s->card_highlights, true);
        if (!is_array($hList)) $hList = [];

        $dList = json_decode($s->detailed_specs, true);
        if (!is_array($dList)) $dList = [];

        // Normalize specs
        $normDetails = [];
        if (isset($dList[0]) && is_array($dList[0]) && isset($dList[0]['group'])) {
            foreach ($dList as $row) {
                $grp = !empty($row['group']) ? $row['group'] : 'General';
                $feat = !empty($row['name']) ? $row['name'] : 'Feature';
                $val = isset($row['value']) ? $row['value'] : '';
                $normDetails[$grp][$feat] = $val;
            }
        } else {
            $normDetails = $dList;
        }

        $productMap[$s->pid] = [
            'pid'         => (int)$s->pid,
            'gid'         => (int)$s->gid,
            'name'        => $s->pname,
            'highlights'  => $hList,
            'top_html'    => (string)$s->card_top_html,
            'bottom_html' => (string)$s->card_bottom_html,
            'specs'       => $normDetails,
        ];
    }

    $cleanCards = featuredesk_is_enabled(featuredesk_get_setting('clean_pricing_cards', '1'));
    $showScroll = featuredesk_is_enabled(featuredesk_get_setting('show_scroll_btn', '1'));
    $boxTitle = featuredesk_get_setting('box_title', 'Technical Specifications & Limit Comparison');
    $boxSubtitle = featuredesk_get_setting('box_subtitle', 'Transparent look at server resources, limits, and developer tooling across our plans.');
    $theme = featuredesk_get_setting('theme', 'modern_blue');

    $payload = json_encode([
        'products'    => $productMap,
        'clean_cards' => $cleanCards,
        'show_scroll' => $showScroll,
        'title'       => $boxTitle,
        'subtitle'    => $boxSubtitle,
        'theme'       => $theme,
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

                // Replace / organize inside .package-content ONLY if highlights or custom boxes exist
                var hasCustomContent = (matched.highlights && matched.highlights.length > 0) ||
                                       (matched.top_html && matched.top_html.trim() !== "") ||
                                       (matched.bottom_html && matched.bottom_html.trim() !== "");

                if (FD_DATA.clean_cards && hasCustomContent) {
                    var contentEl = card.querySelector(".package-content") || card.querySelector(".product-desc") || card.querySelector(".package-body");
                    if (contentEl && !card.querySelector(".fd-card-bullets") && !card.querySelector(".fd-card-custom-box-top")) {
                        var html = "";

                        // 1. Custom Top HTML Box (e.g. Free Domain Offer Box)
                        if (matched.top_html && matched.top_html.trim() !== "") {
                            html += "<div class=\"fd-card-custom-box-top\">" + matched.top_html + "</div>";
                        }

                        // 2. Clean Hero Bullet Badges
                        if (matched.highlights && matched.highlights.length > 0) {
                            html += "<ul class=\"fd-card-bullets\">";
                            matched.highlights.forEach(function(item) {
                                html += "<li><i class=\"fas fa-check-circle fd-icon-check\"></i> " + item + "</li>";
                            });
                            html += "</ul>";

                            // 3. View Full Tech Specs Link (only if highlights exist and specs exist)
                            if (FD_DATA.show_scroll && matched.specs && Object.keys(matched.specs).length > 0) {
                                html += "<div class=\"fd-scroll-wrap\"><a href=\"#featuredesk-matrix-box\" class=\"fd-scroll-link\">View Full Tech Specs &darr;</a></div>";
                            }
                        }

                        // 4. Custom Bottom HTML Box (e.g. Backup Policy Warning Box)
                        if (matched.bottom_html && matched.bottom_html.trim() !== "") {
                            html += "<div class=\"fd-card-custom-box-bottom\">" + matched.bottom_html + "</div>";
                        }

                        contentEl.innerHTML = html;
                    }
                }
            });

            // 5. Bottom Spec Matrix Table
            if (matchedPlans.length > 0 && !document.getElementById("featuredesk-matrix-box")) {
                var allCats = {};
                matchedPlans.forEach(function(p) {
                    for (var cat in p.specs) {
                        if (!allCats[cat]) allCats[cat] = [];
                        for (var feat in p.specs[cat]) {
                            if (allCats[cat].indexOf(feat) === -1) {
                                allCats[cat].push(feat);
                            }
                        }
                    }
                });

                var mHtml = "<div id=\"featuredesk-matrix-box\" class=\"fd-specbox fd-theme-" + FD_DATA.theme + "\">";
                mHtml += "<div class=\"fd-specbox-header\">";
                mHtml += "<h3 class=\"fd-specbox-title\"><i class=\"fas fa-microchip\" style=\"margin-right:8px;\"></i>" + FD_DATA.title + "</h3>";
                mHtml += "<p class=\"fd-specbox-subtitle\">" + FD_DATA.subtitle + "</p>";
                mHtml += "</div>";

                mHtml += "<div class=\"fd-table-responsive\"><table class=\"fd-spec-table\">";
                mHtml += "<thead><tr><th class=\"fd-col-feature\">Specification & Limit</th>";
                matchedPlans.forEach(function(p) {
                    mHtml += "<th class=\"fd-col-plan\"><div class=\"fd-plan-name\">" + p.name + "</div></th>";
                });
                mHtml += "</tr></thead><tbody>";

                for (var cat in allCats) {
                    mHtml += "<tr class=\"fd-category-row\"><td colspan=\"" + (matchedPlans.length + 1) + "\"><i class=\"fas fa-folder-open\" style=\"margin-right:6px;\"></i> " + cat + "</td></tr>";
                    allCats[cat].forEach(function(feat) {
                        mHtml += "<tr class=\"fd-feature-row\">";
                        mHtml += "<td class=\"fd-feature-label\">" + feat + "</td>";
                        matchedPlans.forEach(function(p) {
                            var val = (p.specs[cat] && p.specs[cat][feat]) ? p.specs[cat][feat] : "—";
                            var valLow = val.toLowerCase().trim();
                            var cell = "<span class=\"fd-val-text\">" + val + "</span>";
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
