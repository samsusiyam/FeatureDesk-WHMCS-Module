<?php
/**
 * WHMCS Addon Module: FeatureDesk - Smart Spec Box & Plan Matrix
 *
 * Modern Visual Group Matrix Spreadsheet Editor for WHMCS
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

/**
 * Module Metadata Configuration
 */
if (!function_exists('featuredesk_config')) {
    function featuredesk_config()
    {
        return [
            'name'        => 'FeatureDesk - Smart Spec Box & Plan Matrix',
            'description' => 'Declutter crowded hosting pricing cards into short, punchy hero badges, custom top/bottom promo boxes, and display a responsive, interactive Technical Specifications Comparison Matrix below your product rows.',
            'version'     => '1.2.0',
            'author'      => '<a href="https://baharihost.com" target="_blank" style="color:#0284c7;font-weight:700;">Bahari IT</a>',
            'language'    => 'english',
            'fields'      => [
                'status_note' => [
                    'FriendlyName' => 'Configuration Notice',
                    'Type'         => 'note',
                    'Description'  => '<div style="background:#e0f2fe; border-left:4px solid #0284c7; padding:12px 16px; border-radius:6px; color:#0369a1; font-size:13px;">'
                                    . '<strong><i class="fas fa-magic"></i> FeatureDesk is Active!</strong><br>'
                                    . 'Manage your product groups, custom HTML boxes, hero highlights, and visual comparison matrix directly at <a href="addonmodules.php?module=featuredesk" class="btn btn-xs btn-primary" style="margin-left:8px; font-weight:700;">Open FeatureDesk Dashboard &rarr;</a>'
                                    . '</div>',
                ],
            ],
        ];
    }
}

/**
 * Ensure Required Database Tables Exist
 */
if (!function_exists('featuredesk_ensure_tables')) {
    function featuredesk_ensure_tables()
    {
        try {
            if (!Capsule::schema()->hasTable('mod_featuredesk_specs')) {
                Capsule::schema()->create('mod_featuredesk_specs', function ($table) {
                    $table->increments('id');
                    $table->integer('product_id')->unique();
                    $table->string('badge_text', 100)->nullable();
                    $table->string('badge_color', 20)->default('#2563EB');
                    $table->text('card_highlights')->nullable();
                    $table->text('card_top_html')->nullable();
                    $table->text('card_bottom_html')->nullable();
                    $table->longText('detailed_specs')->nullable();
                    $table->tinyInteger('enabled')->default(1);
                    $table->timestamps();
                });
            } else {
                if (!Capsule::schema()->hasColumn('mod_featuredesk_specs', 'card_top_html')) {
                    Capsule::schema()->table('mod_featuredesk_specs', function ($table) {
                        $table->text('card_top_html')->nullable();
                    });
                }
                if (!Capsule::schema()->hasColumn('mod_featuredesk_specs', 'card_bottom_html')) {
                    Capsule::schema()->table('mod_featuredesk_specs', function ($table) {
                        $table->text('card_bottom_html')->nullable();
                    });
                }
            }

            if (!Capsule::schema()->hasTable('mod_featuredesk_settings')) {
                Capsule::schema()->create('mod_featuredesk_settings', function ($table) {
                    $table->string('setting', 100)->primary();
                    $table->text('value')->nullable();
                });

                $defaults = [
                    'display_mode'        => 'auto_below_cards',
                    'clean_pricing_cards' => '1',
                    'show_scroll_btn'     => '1',
                    'theme'               => 'modern_blue',
                    'box_title'           => 'Technical Specifications & Limit Comparison',
                    'box_subtitle'        => 'Transparent look at server resources, limits, and developer tooling across our plans.',
                ];
                foreach ($defaults as $k => $v) {
                    Capsule::table('mod_featuredesk_settings')->insert(['setting' => $k, 'value' => $v]);
                }
            }
        } catch (\Exception $e) {
            logActivity('FeatureDesk Table Migration Notice: ' . $e->getMessage());
        }
    }
}

if (!function_exists('featuredesk_activate')) {
    function featuredesk_activate()
    {
        featuredesk_ensure_tables();
        return ['status' => 'success', 'description' => 'FeatureDesk has been successfully activated!'];
    }
}

if (!function_exists('featuredesk_deactivate')) {
    function featuredesk_deactivate()
    {
        return ['status' => 'success', 'description' => 'FeatureDesk has been deactivated.'];
    }
}

if (!function_exists('featuredesk_upgrade')) {
    function featuredesk_upgrade($vars)
    {
        featuredesk_ensure_tables();
    }
}

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

if (!function_exists('featuredesk_save_setting')) {
    function featuredesk_save_setting($key, $value)
    {
        try {
            Capsule::table('mod_featuredesk_settings')->updateOrInsert(
                ['setting' => $key],
                ['value' => $value]
            );
        } catch (\Exception $e) {}
    }
}

if (!function_exists('featuredesk_is_enabled')) {
    function featuredesk_is_enabled($val)
    {
        return in_array(strtolower(trim((string)$val)), ['1', 'on', 'true', 'yes'], true);
    }
}

if (!function_exists('featuredesk_h')) {
    function featuredesk_h($str)
    {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Admin CSS Stylesheet
 */
if (!function_exists('featuredesk_shared_css')) {
    function featuredesk_shared_css()
    {
        return '<style>
            .fd-wrap {
                background: #f8fafc;
                border-radius: 12px;
                box-shadow: 0 16px 36px rgba(15,23,42,0.08);
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                margin-bottom: 30px;
                overflow: hidden;
                border: 1px solid #e2e8f0;
            }
            .fd-header {
                background: linear-gradient(135deg, #0284c7 0%, #1e40af 100%);
                padding: 24px 28px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 15px;
            }
            .fd-brand {
                display: flex;
                align-items: center;
                gap: 16px;
            }
            .fd-icon {
                width: 52px;
                height: 52px;
                background: rgba(255,255,255,0.18);
                border: 1px solid rgba(255,255,255,0.3);
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: inset 0 1px 0 rgba(255,255,255,0.25);
            }
            .fd-icon img {
                width: 36px;
                height: 36px;
                border-radius: 8px;
            }
            .fd-title {
                font-size: 22px;
                font-weight: 800;
                color: #ffffff;
                margin: 0;
                line-height: 1.2;
            }
            .fd-subtitle {
                color: rgba(255,255,255,0.9);
                font-size: 13px;
                margin-top: 4px;
            }
            .fd-version {
                background: rgba(255,255,255,0.18);
                border: 1px solid rgba(255,255,255,0.3);
                color: #ffffff;
                font-size: 12px;
                font-weight: 800;
                padding: 6px 14px;
                border-radius: 20px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .fd-nav-wrap {
                background: #ffffff;
                padding: 12px 24px;
                border-bottom: 1px solid #e2e8f0;
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            .fd-nav-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                min-height: 40px;
                padding: 8px 16px;
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                color: #334155 !important;
                font-size: 13px;
                font-weight: 700;
                text-decoration: none !important;
                transition: all 0.15s ease;
            }
            .fd-nav-btn:hover {
                transform: translateY(-1px);
                background: #e0f2fe;
                border-color: #7dd3fc;
                color: #0369a1 !important;
            }
            .fd-nav-btn.active {
                background: #0284c7;
                border-color: #0284c7;
                color: #ffffff !important;
                box-shadow: 0 4px 12px rgba(2,132,199,0.3);
            }
            .fd-body {
                padding: 26px;
            }
            .fd-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 24px;
                margin-bottom: 24px;
                box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            }
            .fd-card-title {
                font-size: 16px;
                font-weight: 800;
                color: #0f172a;
                margin: 0 0 8px 0;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .fd-card-desc {
                font-size: 13px;
                color: #64748b;
                margin-bottom: 20px;
            }
            .fd-badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 4px 10px;
                font-size: 11px;
                font-weight: 700;
                border-radius: 20px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .fd-badge-primary { background: #e0f2fe; color: #0369a1; }
            .fd-badge-success { background: #dcfce7; color: #15803d; }
            .fd-badge-warning { background: #fef3c7; color: #b45309; }
            .fd-badge-muted { background: #f1f5f9; color: #64748b; }

            .fd-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 16px;
                font-size: 13px;
                font-weight: 700;
                border-radius: 6px;
                text-decoration: none !important;
                cursor: pointer;
                border: 0;
                transition: all 0.15s ease;
            }
            .fd-btn-primary { background: #0284c7; color: #ffffff !important; }
            .fd-btn-primary:hover { background: #0369a1; }
            .fd-btn-success { background: #10b981; color: #ffffff !important; }
            .fd-btn-success:hover { background: #059669; }
            .fd-btn-default { background: #e2e8f0; color: #334155 !important; }
            .fd-btn-default:hover { background: #cbd5e1; }
            .fd-btn-danger { background: #fee2e2; color: #dc2626 !important; }
            .fd-btn-danger:hover { background: #fca5a5; }
            .fd-btn-sm { padding: 4px 10px; font-size: 12px; }

            /* Accordion Group View */
            .fd-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 15px;
                margin-bottom: 20px;
                background: #f1f5f9;
                padding: 12px 18px;
                border-radius: 8px;
            }
            .fd-search-box {
                position: relative;
                flex: 1;
                max-width: 360px;
            }
            .fd-search-box input {
                width: 100%;
                padding: 8px 12px 8px 36px;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                font-size: 13px;
                outline: none;
            }
            .fd-search-box i {
                position: absolute;
                left: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: #94a3b8;
            }
            .fd-accordion-item {
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                margin-bottom: 14px;
                background: #ffffff;
                box-shadow: 0 1px 3px rgba(0,0,0,0.03);
                overflow: hidden;
            }
            .fd-accordion-header {
                padding: 14px 20px;
                background: #ffffff;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: space-between;
                transition: background 0.2s;
                user-select: none;
            }
            .fd-accordion-header:hover { background: #f8fafc; }
            .fd-accordion-title {
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 15px;
                font-weight: 700;
                color: #0f172a;
            }
            .fd-accordion-chevron {
                transition: transform 0.2s;
                color: #64748b;
                font-size: 14px;
            }
            .fd-accordion-item.active .fd-accordion-chevron {
                transform: rotate(180deg);
            }
            .fd-accordion-body {
                display: none;
                border-top: 1px solid #e2e8f0;
                padding: 0;
            }
            .fd-accordion-item.active .fd-accordion-body {
                display: block;
            }

            .fd-table {
                width: 100%;
                border-collapse: collapse;
            }
            .fd-table th {
                background: #f8fafc;
                padding: 10px 16px;
                font-size: 11.5px;
                font-weight: 700;
                text-transform: uppercase;
                color: #64748b;
                border-bottom: 1px solid #e2e8f0;
                text-align: left;
            }
            .fd-table td {
                padding: 12px 16px;
                border-bottom: 1px solid #f1f5f9;
                font-size: 13px;
                color: #334155;
                vertical-align: middle;
            }
            .fd-table tr:last-child td { border-bottom: none; }
            .fd-table tr:hover td { background: #fafafa; }

            /* Modern Visual Grid Spreadsheet */
            .fd-matrix-grid-wrap {
                overflow-x: auto;
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                margin-top: 20px;
            }
            .fd-matrix-grid {
                width: 100%;
                border-collapse: collapse;
                min-width: 800px;
            }
            .fd-matrix-grid th {
                background: #0f172a;
                color: #ffffff;
                padding: 14px 16px;
                font-size: 13px;
                font-weight: 700;
                text-align: center;
                border-right: 1px solid #334155;
            }
            .fd-matrix-grid th.fd-grid-spec-col {
                width: 280px;
                text-align: left;
                background: #1e293b;
            }
            .fd-matrix-grid td {
                padding: 10px 14px;
                border-bottom: 1px solid #e2e8f0;
                border-right: 1px solid #e2e8f0;
                vertical-align: middle;
            }
            .fd-grid-cat-header td {
                background: #f1f5f9 !important;
                font-weight: 800;
                font-size: 13px;
                color: #0f172a;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                padding: 12px 16px;
            }
            .fd-grid-input {
                width: 100%;
                padding: 8px 10px;
                font-size: 12.5px;
                border: 1px solid #cbd5e1;
                border-radius: 5px;
                background: #ffffff;
                box-sizing: border-box;
            }
            .fd-grid-input:focus {
                border-color: #0284c7;
                outline: 0;
                box-shadow: 0 0 0 2px rgba(2,132,199,0.2);
            }
            .fd-grid-textarea {
                width: 100%;
                padding: 8px 10px;
                font-size: 12px;
                border: 1px solid #cbd5e1;
                border-radius: 5px;
                background: #ffffff;
                box-sizing: border-box;
                line-height: 1.4;
            }
            .fd-btn-del-row {
                background: transparent;
                color: #ef4444;
                border: 0;
                cursor: pointer;
                padding: 4px;
                font-size: 14px;
            }
            .fd-btn-del-row:hover { color: #b91c1c; }
            .fd-alert-success {
                background: #dcfce7;
                border: 1px solid #86efac;
                color: #166534;
                padding: 12px 18px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-size: 13px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .fd-form-group { margin-bottom: 18px; }
            .fd-form-label { display: block; font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 6px; }
            .fd-form-select, .fd-form-input {
                width: 100%;
                max-width: 600px;
                padding: 10px 12px;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                font-size: 13px;
            }
        </style>';
    }
}

/**
 * Module Header Renderer
 */
if (!function_exists('featuredesk_render_header')) {
    function featuredesk_render_header($moduleLink, $action)
    {
        $logoUrl = '../modules/addons/featuredesk/logo.png';
        $html = featuredesk_shared_css();
        $html .= '<div class="fd-wrap">';
        $html .= '  <div class="fd-header">';
        $html .= '    <div class="fd-brand">';
        $html .= '      <div class="fd-icon"><img src="' . $logoUrl . '" alt="FeatureDesk Logo"></div>';
        $html .= '      <div>';
        $html .= '        <div class="fd-title">FeatureDesk</div>';
        $html .= '        <div class="fd-subtitle">Smart Plan Highlights, Custom Boxes & Technical Specifications Matrix</div>';
        $html .= '      </div>';
        $html .= '    </div>';
        $html .= '    <div class="fd-version">v1.2.0 &bull; Bahari IT</div>';
        $html .= '  </div>';

        $html .= '  <div class="fd-nav-wrap">';
        $html .= '    <a href="' . featuredesk_h($moduleLink) . '&action=products" class="fd-nav-btn' . ($action === 'products' || $action === 'edit_group' ? ' active' : '') . '"><i class="fas fa-boxes"></i> Product Groups & Matrix Editor</a>';
        $html .= '    <a href="' . featuredesk_h($moduleLink) . '&action=display_settings" class="fd-nav-btn' . ($action === 'display_settings' ? ' active' : '') . '"><i class="fas fa-sliders-h"></i> Display Settings</a>';
        $html .= '    <a href="' . featuredesk_h($moduleLink) . '&action=integration_guide" class="fd-nav-btn' . ($action === 'integration_guide' ? ' active' : '') . '"><i class="fas fa-code"></i> Integration Guide</a>';
        $html .= '    <a href="' . featuredesk_h($moduleLink) . '&action=developer_info" class="fd-nav-btn' . ($action === 'developer_info' ? ' active' : '') . '"><i class="fas fa-info-circle"></i> Developer & Support</a>';
        $html .= '  </div>';

        $html .= '  <div class="fd-body">';

        if (isset($_GET['saved'])) {
            $html .= '<div class="fd-alert-success"><i class="fas fa-check-circle"></i> All group specifications, custom boxes, and highlights saved successfully!</div>';
        }
        if (isset($_GET['reset'])) {
            $html .= '<div class="fd-alert-success" style="background:#fee2e2; border-color:#fca5a5; color:#991b1b;"><i class="fas fa-undo"></i> Group has been reset to default WHMCS! Original descriptions are restored.</div>';
        }

        return $html;
    }
}

/**
 * Module Footer Renderer
 */
if (!function_exists('featuredesk_render_footer')) {
    function featuredesk_render_footer()
    {
        return '  </div></div>';
    }
}

/**
 * Tab 1: Groups Overview Page
 */
if (!function_exists('featuredesk_render_products_page')) {
    function featuredesk_render_products_page($moduleLink)
    {
        $groups = Capsule::table('tblproductgroups')->orderBy('order', 'asc')->get();
        $products = Capsule::table('tblproducts')->orderBy('order', 'asc')->get();
        $specsMap = Capsule::table('mod_featuredesk_specs')->get()->keyBy('product_id');

        $groupedProducts = [];
        foreach ($products as $p) {
            $groupedProducts[$p->gid][] = $p;
        }

        $html = '<div class="fd-card">';
        $html .= '<div class="fd-card-title"><i class="fas fa-cubes text-primary"></i> Product Groups & Visual Matrix Editor</div>';
        $html .= '<div class="fd-card-desc">Click <strong>"⚡ Edit Group Specs"</strong> on any group below to open the Modern Visual Spreadsheet. You can customize top domain boxes, hero highlights, bottom backup policy boxes, and comparison specs for all plans side-by-side!</div>';

        // Toolbar
        $html .= '<div class="fd-toolbar">';
        $html .= '  <div class="fd-search-box">';
        $html .= '    <i class="fas fa-search"></i>';
        $html .= '    <input type="text" id="fdGroupSearch" placeholder="Search product or group..." onkeyup="fdFilterGroups()">';
        $html .= '  </div>';
        $html .= '  <div style="display:flex; gap:8px;">';
        $html .= '    <button type="button" class="fd-btn fd-btn-default fd-btn-sm" onclick="fdToggleAll(true)"><i class="fas fa-angle-double-down"></i> Expand All</button>';
        $html .= '    <button type="button" class="fd-btn fd-btn-default fd-btn-sm" onclick="fdToggleAll(false)"><i class="fas fa-angle-double-up"></i> Collapse All</button>';
        $html .= '  </div>';
        $html .= '</div>';

        // Accordion Groups
        $html .= '<div id="fdAccordionList">';

        foreach ($groups as $g) {
            $prods = isset($groupedProducts[$g->id]) ? $groupedProducts[$g->id] : [];
            $totalCount = count($prods);
            if ($totalCount === 0) continue;

            $configuredCount = 0;
            foreach ($prods as $p) {
                if (isset($specsMap[$p->id])) {
                    $item = $specsMap[$p->id];
                    $h = json_decode($item->card_highlights, true);
                    if (is_array($h) && count($h) > 0) {
                        $configuredCount++;
                    }
                }
            }

            $isExpanded = ($configuredCount > 0) ? ' active' : '';

            $html .= '<div class="fd-accordion-item' . $isExpanded . '" data-group-name="' . strtolower(featuredesk_h($g->name)) . '">';
            $html .= '  <div class="fd-accordion-header" onclick="fdToggleAccordion(this)">';
            $html .= '    <div class="fd-accordion-title">';
            $html .= '      <i class="fas fa-folder text-primary"></i>';
            $html .= '      <span>' . featuredesk_h($g->name) . '</span>';
            $html .= '      <span class="fd-badge fd-badge-muted">' . $totalCount . ' Plans</span>';
            if ($configuredCount > 0) {
                $html .= '    <span class="fd-badge fd-badge-success"><i class="fas fa-check"></i> ' . $configuredCount . ' Active</span>';
            }
            $html .= '    </div>';
            $html .= '    <div style="display:flex; align-items:center; gap:10px;" onclick="event.stopPropagation();">';
            $html .= '      <a href="' . featuredesk_h($moduleLink) . '&action=edit_group&gid=' . (int)$g->id . '" class="fd-btn fd-btn-primary fd-btn-sm"><i class="fas fa-table"></i> ⚡ Edit Group Specs</a>';
            $html .= '      <a href="../cart.php?gid=' . (int)$g->id . '" target="_blank" class="fd-btn fd-btn-default fd-btn-sm"><i class="fas fa-external-link-alt"></i> Store</a>';
            $html .= '      <i class="fas fa-chevron-down fd-accordion-chevron" style="cursor:pointer;" onclick="fdToggleAccordion(this.closest(\'.fd-accordion-header\'))"></i>';
            $html .= '    </div>';
            $html .= '  </div>';

            $html .= '  <div class="fd-accordion-body">';
            $html .= '    <table class="fd-table">';
            $html .= '      <thead><tr><th>Plan Name</th><th>Hero Highlights</th><th>Top Custom Box</th><th>Bottom Custom Box</th><th>Comparison Specs</th></tr></thead>';
            $html .= '      <tbody>';

            foreach ($prods as $p) {
                $spec = isset($specsMap[$p->id]) ? $specsMap[$p->id] : null;
                $highlights = ($spec && $spec->card_highlights) ? json_decode($spec->card_highlights, true) : [];
                $topBox = ($spec && !empty($spec->card_top_html)) ? true : false;
                $bottomBox = ($spec && !empty($spec->card_bottom_html)) ? true : false;
                $detailedSpecs = ($spec && $spec->detailed_specs) ? json_decode($spec->detailed_specs, true) : [];

                $html .= '<tr class="fd-prod-row" data-prod-name="' . strtolower(featuredesk_h($p->name)) . '">';
                $html .= '  <td><strong>' . featuredesk_h($p->name) . '</strong> <span style="font-size:11px; color:#94a3b8;">(PID: ' . (int)$p->id . ')</span></td>';
                $html .= '  <td>';
                if (is_array($highlights) && count($highlights) > 0) {
                    $html .= '<span class="fd-badge fd-badge-primary"><i class="fas fa-check-circle"></i> ' . count($highlights) . ' Bullets</span>';
                } else {
                    $html .= '<span style="font-size:12px; color:#94a3b8;">Default description</span>';
                }
                $html .= '  </td>';
                $html .= '  <td>' . ($topBox ? '<span class="fd-badge fd-badge-success">Active</span>' : '<span style="color:#cbd5e1;">&mdash;</span>') . '</td>';
                $html .= '  <td>' . ($bottomBox ? '<span class="fd-badge fd-badge-success">Active</span>' : '<span style="color:#cbd5e1;">&mdash;</span>') . '</td>';
                $html .= '  <td>';
                if (is_array($detailedSpecs) && count($detailedSpecs) > 0) {
                    $html .= '<span class="fd-badge fd-badge-success"><i class="fas fa-table"></i> ' . count($detailedSpecs) . ' Specs</span>';
                } else {
                    $html .= '<span style="font-size:12px; color:#94a3b8;">None</span>';
                }
                $html .= '  </td>';
                $html .= '</tr>';
            }

            $html .= '      </tbody>';
            $html .= '    </table>';
            $html .= '  </div>';
            $html .= '</div>';
        }

        $html .= '</div></div>';

        $html .= '
        <script>
        function fdToggleAccordion(headerEl) {
            var item = headerEl.closest(".fd-accordion-item");
            item.classList.toggle("active");
        }
        function fdToggleAll(expand) {
            var items = document.querySelectorAll(".fd-accordion-item");
            items.forEach(function(el) {
                if (expand) el.classList.add("active");
                else el.classList.remove("active");
            });
        }
        function fdFilterGroups() {
            var val = document.getElementById("fdGroupSearch").value.toLowerCase().trim();
            var items = document.querySelectorAll(".fd-accordion-item");
            items.forEach(function(item) {
                var gname = item.getAttribute("data-group-name");
                var prods = item.querySelectorAll(".fd-prod-row");
                var matchedInGroup = false;

                prods.forEach(function(pr) {
                    var pname = pr.getAttribute("data-prod-name");
                    if (val === "" || gname.includes(val) || pname.includes(val)) {
                        pr.style.display = "";
                        matchedInGroup = true;
                    } else {
                        pr.style.display = "none";
                    }
                });

                if (matchedInGroup) {
                    item.style.display = "";
                    if (val !== "") item.classList.add("active");
                } else {
                    item.style.display = "none";
                }
            });
        }
        </script>';

        return $html;
    }
}

/**
 * Tab 1 (Sub): Modern Visual Group Matrix Spreadsheet Editor
 */
if (!function_exists('featuredesk_render_edit_group_page')) {
    function featuredesk_render_edit_group_page($moduleLink, $groupId)
    {
        $group = Capsule::table('tblproductgroups')->where('id', $groupId)->first();
        if (!$group) {
            return '<div class="fd-card"><p>Product group not found.</p></div>';
        }

        $products = Capsule::table('tblproducts')->where('gid', $groupId)->orderBy('order', 'asc')->get();
        if ($products->isEmpty()) {
            return '<div class="fd-card"><p>No products found in this group.</p></div>';
        }

        $productIds = $products->pluck('id')->all();
        $specsMap = Capsule::table('mod_featuredesk_specs')->whereIn('product_id', $productIds)->get()->keyBy('product_id');

        $matrixCategories = [];
        foreach ($products as $p) {
            if (isset($specsMap[$p->id])) {
                $details = json_decode($specsMap[$p->id]->detailed_specs, true);
                if (is_array($details)) {
                    foreach ($details as $row) {
                        if (isset($row['group']) && isset($row['name'])) {
                            $grp = $row['group'];
                            $feat = $row['name'];
                            if (!isset($matrixCategories[$grp])) {
                                $matrixCategories[$grp] = [];
                            }
                            if (!in_array($feat, $matrixCategories[$grp])) {
                                $matrixCategories[$grp][] = $feat;
                            }
                        }
                    }
                }
            }
        }

        if (empty($matrixCategories)) {
            $matrixCategories = [
                'General & Locations' => ['Server Location', 'Websites Hosted'],
                'Storage & Bandwidth' => ['NVMe SSD Storage', 'Monthly Bandwidth', 'Inodes Limit'],
                'Hardware & Engine'   => ['Control Panel', 'Web Server', 'CPU Core', 'RAM Memory'],
                'Security & Backups'  => ['Free SSL Certificate', 'Automated Backups', 'Imunify360 Protection']
            ];
        }

        $html = '<div class="fd-card">';
        $html .= '<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">';
        $html .= '  <div>';
        $html .= '    <h3 style="margin:0; font-size:18px;"><i class="fas fa-table text-primary"></i> Visual Group Editor: ' . featuredesk_h($group->name) . '</h3>';
        $html .= '    <span style="color:#64748b; font-size:13px;">Manage Top Custom Boxes, Clean Bullets, Bottom Boxes, and Comparison Specs all in one visual grid!</span>';
        $html .= '  </div>';
        $html .= '  <div style="display:flex; gap:10px; align-items:center;">';
        $html .= '    <a href="../cart.php?gid=' . (int)$groupId . '" target="_blank" class="fd-btn fd-btn-default"><i class="fas fa-eye"></i> View Live Store</a>';
        $html .= "    <a href=\"" . featuredesk_h($moduleLink) . "&action=reset_group&gid=" . (int)$groupId . "\" class=\"fd-btn fd-btn-danger fd-btn-sm\" onclick=\"return confirm('Are you sure you want to RESET this entire group to default WHMCS?');\"><i class=\"fas fa-undo\"></i> Reset Group to Default</a>";
        $html .= '    <a href="' . featuredesk_h($moduleLink) . '&action=products" class="fd-btn fd-btn-default"><i class="fas fa-arrow-left"></i> Back</a>';
        $html .= '  </div>';
        $html .= '</div>';

        $html .= '<form method="post" action="' . featuredesk_h($moduleLink) . '&action=save_group_specs" id="fdGroupForm">';
        $html .= '<input type="hidden" name="group_id" value="' . (int)$groupId . '">';

        $html .= '<div class="fd-matrix-grid-wrap">';
        $html .= '<table class="fd-matrix-grid" id="fdVisualGrid">';

        // Header Row: Plan Names
        $html .= '<thead><tr>';
        $html .= '<th class="fd-grid-spec-col"><i class="fas fa-sliders-h"></i> Specification / Section</th>';
        foreach ($products as $p) {
            $html .= '<th>' . featuredesk_h($p->name) . '<br><span style="font-size:11px; opacity:0.8; font-weight:normal;">(PID: ' . (int)$p->id . ')</span></th>';
        }
        $html .= '</tr></thead><tbody>';

        // 1. Top Custom Box (e.g. Free Domain Box)
        $html .= '<tr style="background:#eff6ff;">';
        $html .= '<td style="font-weight:700; color:#1e40af;"><i class="fas fa-window-maximize text-primary"></i> Top Custom HTML Box<br><span style="font-size:11px; font-weight:normal; color:#3b82f6;">Appears directly above bullet list (e.g. Free Domain Banner)</span></td>';
        foreach ($products as $p) {
            $spec = isset($specsMap[$p->id]) ? $specsMap[$p->id] : null;
            $topHtml = $spec ? (string)$spec->card_top_html : '';
            $html .= '<td>';
            $html .= '  <textarea name="plans[' . (int)$p->id . '][card_top_html]" rows="4" class="fd-grid-textarea" placeholder="<div style=...>FREE DOMAIN...</div>">' . featuredesk_h($topHtml) . '</textarea>';
            $html .= '</td>';
        }
        $html .= '</tr>';

        // 2. Hero Highlights Bullets Row
        $html .= '<tr style="background:#f0fdf4;">';
        $html .= '<td style="font-weight:700; color:#166534;"><i class="fas fa-check-circle text-success"></i> Hero Highlights (3-5 Bullets)<br><span style="font-size:11px; font-weight:normal; color:#15803d;">One short spec per line (replaces cluttered description on card)</span></td>';
        foreach ($products as $p) {
            $spec = isset($specsMap[$p->id]) ? $specsMap[$p->id] : null;
            $hList = ($spec && $spec->card_highlights) ? json_decode($spec->card_highlights, true) : [];
            if (!is_array($hList)) $hList = [];
            $hText = implode("\n", $hList);
            $html .= '<td>';
            $html .= '  <textarea name="plans[' . (int)$p->id . '][highlights]" rows="5" class="fd-grid-textarea" placeholder="1 Website Hosted&#10;1 GB NVMe SSD&#10;100 GB Bandwidth&#10;LiteSpeed + Free SSL">' . featuredesk_h($hText) . '</textarea>';
            $html .= '</td>';
        }
        $html .= '</tr>';

        // 3. Bottom Custom Box (e.g. Backup Policy Warning Box)
        $html .= '<tr style="background:#fffbeb;">';
        $html .= '<td style="font-weight:700; color:#92400e;"><i class="fas fa-exclamation-triangle text-warning"></i> Bottom Custom HTML Box<br><span style="font-size:11px; font-weight:normal; color:#b45309;">Appears at the bottom of the card (e.g. Backup Policy Box)</span></td>';
        foreach ($products as $p) {
            $spec = isset($specsMap[$p->id]) ? $specsMap[$p->id] : null;
            $botHtml = $spec ? (string)$spec->card_bottom_html : '';
            $html .= '<td>';
            $html .= '  <textarea name="plans[' . (int)$p->id . '][card_bottom_html]" rows="4" class="fd-grid-textarea" placeholder="<div style=...>Backup Policy...</div>">' . featuredesk_h($botHtml) . '</textarea>';
            $html .= '</td>';
        }
        $html .= '</tr>';

        // 4. Categorized Comparison Specs Rows
        $catIndex = 0;
        foreach ($matrixCategories as $catName => $featureList) {
            $catIndex++;
            $html .= '<tr class="fd-grid-cat-header" data-cat-id="' . $catIndex . '">';
            $html .= '  <td colspan="' . (count($products) + 1) . '">';
            $html .= '    <div style="display:flex; justify-content:space-between; align-items:center;">';
            $html .= '      <div style="display:flex; align-items:center; gap:8px;">';
            $html .= '        <i class="fas fa-folder-open text-primary"></i>';
            $html .= '        <input type="text" name="categories[' . $catIndex . '][name]" value="' . featuredesk_h($catName) . '" class="fd-grid-input" style="font-weight:800; font-size:13px; max-width:280px; background:#ffffff;">';
            $html .= '      </div>';
            $html .= '      <div style="display:flex; gap:8px;">';
            $html .= '        <button type="button" class="fd-btn fd-btn-default fd-btn-sm" onclick="fdAddSpecRow(' . $catIndex . ')"><i class="fas fa-plus"></i> Add Spec Row</button>';
            $html .= '        <button type="button" class="fd-btn fd-btn-danger fd-btn-sm" onclick="fdDeleteCategory(' . $catIndex . ')"><i class="fas fa-trash-alt"></i> Delete Category</button>';
            $html .= '      </div>';
            $html .= '    </div>';
            $html .= '  </td>';
            $html .= '</tr>';

            $rowIndex = 0;
            foreach ($featureList as $featName) {
                $rowIndex++;
                $html .= '<tr class="fd-grid-spec-row" data-cat="' . $catIndex . '">';
                $html .= '  <td style="display:flex; align-items:center; gap:8px; border-right:1px solid #e2e8f0;">';
                $html .= '    <button type="button" class="fd-btn-del-row" onclick="this.closest(\'tr\').remove()" title="Delete Spec Row"><i class="fas fa-trash-alt"></i></button>';
                $html .= '    <input type="text" name="categories[' . $catIndex . '][features][' . $rowIndex . '][name]" value="' . featuredesk_h($featName) . '" class="fd-grid-input" style="flex:1; font-weight:600;">';
                $html .= '  </td>';

                foreach ($products as $p) {
                    $val = '';
                    if (isset($specsMap[$p->id])) {
                        $details = json_decode($specsMap[$p->id]->detailed_specs, true);
                        if (is_array($details)) {
                            foreach ($details as $r) {
                                if (isset($r['group']) && isset($r['name']) && $r['group'] === $catName && $r['name'] === $featName) {
                                    $val = $r['value'];
                                    break;
                                }
                            }
                        }
                    }
                    $html .= '  <td>';
                    $html .= '    <input type="text" name="categories[' . $catIndex . '][features][' . $rowIndex . '][values][' . (int)$p->id . ']" value="' . featuredesk_h($val) . '" class="fd-grid-input" placeholder="e.g. 10 GB, Unlimited, Yes">';
                    $html .= '  </td>';
                }
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';
        $html .= '</div>';

        // Actions
        $html .= '<div style="margin-top:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">';
        $html .= '  <button type="button" class="fd-btn fd-btn-default" onclick="fdAddCategoryBlock()"><i class="fas fa-folder-plus"></i> + Add New Category Block</button>';
        $html .= '  <button type="submit" class="fd-btn fd-btn-success" style="font-size:15px; padding:10px 24px;"><i class="fas fa-save"></i> 💾 Save All Group Specifications</button>';
        $html .= '</div>';

        $html .= '</form></div>';

        $prodCount = count($products);
        $prodIdsJson = json_encode($productIds);

        $html .= '
        <script>
        var fdProdIds = ' . $prodIdsJson . ';
        var fdCatCounter = ' . ($catIndex + 1) . ';
        var fdRowCounter = 1000;

        function fdAddSpecRow(catId) {
            fdRowCounter++;
            var catHeader = document.querySelector("tr.fd-grid-cat-header[data-cat-id=\'" + catId + "\']");
            if (!catHeader) return;

            var tr = document.createElement("tr");
            tr.className = "fd-grid-spec-row";
            tr.setAttribute("data-cat", catId);

            var tdFirst = document.createElement("td");
            tdFirst.style.cssText = "display:flex; align-items:center; gap:8px; border-right:1px solid #e2e8f0;";
            tdFirst.innerHTML = \'<button type="button" class="fd-btn-del-row" onclick="this.closest(\\\'tr\\\').remove()"><i class="fas fa-trash-alt"></i></button>\' +
                                \'<input type="text" name="categories[\' + catId + \'][features][\' + fdRowCounter + \'][name]" placeholder="New Feature Name" class="fd-grid-input" style="flex:1; font-weight:600;">\';
            tr.appendChild(tdFirst);

            fdProdIds.forEach(function(pid) {
                var td = document.createElement("td");
                td.innerHTML = \'<input type="text" name="categories[\' + catId + \'][features][\' + fdRowCounter + \'][values][\' + pid + \']" placeholder="Value" class="fd-grid-input">\';
                tr.appendChild(td);
            });

            var rows = document.querySelectorAll("tr.fd-grid-spec-row[data-cat=\'" + catId + "\']");
            if (rows.length > 0) {
                rows[rows.length - 1].after(tr);
            } else {
                catHeader.after(tr);
            }
        }

        function fdDeleteCategory(catId) {
            if (confirm("Are you sure you want to delete this entire category and all its spec rows?")) {
                var catHeader = document.querySelector("tr.fd-grid-cat-header[data-cat-id=\'" + catId + "\']");
                if (catHeader) catHeader.remove();
                var rows = document.querySelectorAll("tr.fd-grid-spec-row[data-cat=\'" + catId + "\']");
                rows.forEach(function(r) { r.remove(); });
            }
        }

        function fdAddCategoryBlock() {
            fdCatCounter++;
            var tbody = document.querySelector("#fdVisualGrid tbody");
            var trCat = document.createElement("tr");
            trCat.className = "fd-grid-cat-header";
            trCat.setAttribute("data-cat-id", fdCatCounter);

            var td = document.createElement("td");
            td.setAttribute("colspan", ' . ($prodCount + 1) . ');
            td.innerHTML = \'<div style="display:flex; justify-content:space-between; align-items:center;">\' +
                           \'  <div style="display:flex; align-items:center; gap:8px;">\' +
                           \'    <i class="fas fa-folder-open text-primary"></i>\' +
                           \'    <input type="text" name="categories[\' + fdCatCounter + \'][name]" value="New Category" class="fd-grid-input" style="font-weight:800; font-size:13px; max-width:280px; background:#ffffff;">\' +
                           \'  </div>\' +
                           \'  <div style="display:flex; gap:8px;">\' +
                           \'    <button type="button" class="fd-btn fd-btn-default fd-btn-sm" onclick="fdAddSpecRow(\' + fdCatCounter + \')"><i class="fas fa-plus"></i> Add Spec Row</button>\' +
                           \'    <button type="button" class="fd-btn fd-btn-danger fd-btn-sm" onclick="fdDeleteCategory(\' + fdCatCounter + \')"><i class="fas fa-trash-alt"></i> Delete Category</button>\' +
                           \'  </div>\' +
                           \'</div>\';
            trCat.appendChild(td);
            tbody.appendChild(trCat);
            fdAddSpecRow(fdCatCounter);
        }
        </script>';

        return $html;
    }
}

/**
 * Tab 2: Display Settings
 */
if (!function_exists('featuredesk_render_display_settings_page')) {
    function featuredesk_render_display_settings_page($moduleLink)
    {
        $displayMode   = featuredesk_get_setting('display_mode', 'auto_below_cards');
        $cleanCards    = featuredesk_get_setting('clean_pricing_cards', '1');
        $showScrollBtn = featuredesk_get_setting('show_scroll_btn', '1');
        $theme         = featuredesk_get_setting('theme', 'modern_blue');
        $boxTitle      = featuredesk_get_setting('box_title', 'Technical Specifications & Limit Comparison');
        $boxSubtitle   = featuredesk_get_setting('box_subtitle', 'Transparent look at server resources, limits, and developer tooling across our plans.');

        $html = '<div class="fd-card">';
        $html .= '<div class="fd-card-title"><i class="fas fa-sliders-h text-primary"></i> Display Settings</div>';
        $html .= '<div class="fd-card-desc">Control where and how the comparison box renders on your order forms.</div>';

        $html .= '<form method="post" action="' . featuredesk_h($moduleLink) . '&action=save_display_settings">';

        $html .= '<div class="fd-form-group">';
        $html .= '  <label class="fd-form-label">Comparison Matrix Placement</label>';
        $html .= '  <select name="display_mode" class="fd-form-select">';
        $html .= '    <option value="auto_below_cards"' . ($displayMode === 'auto_below_cards' ? ' selected' : '') . '>Auto-Inject Directly Below Pricing Cards (Recommended)</option>';
        $html .= '    <option value="disabled"' . ($displayMode === 'disabled' ? ' selected' : '') . '>Disable Bottom Spec Matrix</option>';
        $html .= '  </select>';
        $html .= '</div>';

        $html .= '<div class="fd-form-group">';
        $html .= '  <label class="fd-form-label">Clean Long Descriptions on Pricing Cards</label>';
        $html .= '  <label style="display:flex; align-items:center; gap:8px; font-weight:normal; cursor:pointer;">';
        $html .= '    <input type="checkbox" name="clean_pricing_cards" value="1"' . (featuredesk_is_enabled($cleanCards) ? ' checked' : '') . '>';
        $html .= '    <span>Substitute cluttered descriptions with Custom Top Box + Clean Bullets + Custom Bottom Box</span>';
        $html .= '  </label>';
        $html .= '</div>';

        $html .= '<div class="fd-form-group">';
        $html .= '  <label class="fd-form-label">Card "View Specs" Link</label>';
        $html .= '  <label style="display:flex; align-items:center; gap:8px; font-weight:normal; cursor:pointer;">';
        $html .= '    <input type="checkbox" name="show_scroll_btn" value="1"' . (featuredesk_is_enabled($showScrollBtn) ? ' checked' : '') . '>';
        $html .= '    <span>Add a clickable smooth-scrolling button at the bottom of each pricing card</span>';
        $html .= '  </label>';
        $html .= '</div>';

        $html .= '<div class="fd-form-group">';
        $html .= '  <label class="fd-form-label">Spec Box Title</label>';
        $html .= '  <input type="text" name="box_title" class="fd-form-input" value="' . featuredesk_h($boxTitle) . '">';
        $html .= '</div>';

        $html .= '<div class="fd-form-group">';
        $html .= '  <label class="fd-form-label">Spec Box Subtitle</label>';
        $html .= '  <input type="text" name="box_subtitle" class="fd-form-input" value="' . featuredesk_h($boxSubtitle) . '">';
        $html .= '</div>';

        $html .= '<div style="margin-top:20px;">';
        $html .= '  <button type="submit" class="fd-btn fd-btn-primary"><i class="fas fa-save"></i> Save Display Settings</button>';
        $html .= '</div>';
        $html .= '</form></div>';

        return $html;
    }
}

/**
 * Tab 3: Integration Guide
 */
if (!function_exists('featuredesk_render_integration_guide_page')) {
    function featuredesk_render_integration_guide_page()
    {
        $html = '<div class="fd-card">';
        $html .= '<div class="fd-card-title"><i class="fas fa-code text-primary"></i> Integration Guide</div>';
        $html .= '<p style="color:#475569; font-size:13px; line-height:1.6;">FeatureDesk hooks automatically into WHMCS order form templates (Lagom 2, Standard Cart, Supreme, Premium Comparison) without any template edits required.</p>';
        $html .= '</div>';
        return $html;
    }
}

/**
 * Tab 4: Developer Info
 */
if (!function_exists('featuredesk_render_developer_page')) {
    function featuredesk_render_developer_page()
    {
        $html = '<div class="fd-card">';
        $html .= '<div class="fd-card-title"><i class="fas fa-info-circle text-primary"></i> About FeatureDesk & Bahari IT</div>';
        $html .= '<p style="color:#475569; font-size:13px; line-height:1.6;">FeatureDesk was engineered by <strong>Bahari IT</strong> to solve the modern web hosting conversion dilemma: giving customers transparent, deep technical specifications without turning the pricing table into an unreadable wall of text.</p>';
        $html .= '<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:18px; margin-top:20px;">';
        $html .= '  <div style="font-weight:700; margin-bottom:8px;">Developer & Support:</div>';
        $html .= '  <ul style="margin:0; padding-left:20px; font-size:13px; color:#475569;">';
        $html .= '    <li><strong>Developer / Lead:</strong> MD Samsuzzaman Siyam</li>';
        $html .= '    <li><strong>Organization:</strong> Bahari IT (bahari-it.com)</li>';
        $html .= '    <li><strong>Support Portal:</strong> support.baharihost.com</li>';
        $html .= '    <li><strong>Module Version:</strong> 1.2.0 (Production Stable)</li>';
        $html .= '  </ul>';
        $html .= '</div></div>';
        return $html;
    }
}

/**
 * Main Module Admin Dispatcher
 */
if (!function_exists('featuredesk_output')) {
    function featuredesk_output($vars)
    {
        featuredesk_ensure_tables();
        $moduleLink = $vars['modulelink'];
        $action = isset($_GET['action']) ? trim($_GET['action']) : 'products';

        // Handle Reset Group Action
        if ($action === 'reset_group' && isset($_GET['gid'])) {
            $groupId = (int)$_GET['gid'];
            $pids = Capsule::table('tblproducts')->where('gid', $groupId)->pluck('id')->all();
            if (!empty($pids)) {
                Capsule::table('mod_featuredesk_specs')->whereIn('product_id', $pids)->delete();
            }
            header('Location: ' . $moduleLink . '&action=products&reset=1');
            exit;
        }

        // 1. Handle Save Group Specs POST (The Visual Spreadsheet Save)
        if ($action === 'save_group_specs' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $groupId = (int)$_POST['group_id'];
            $plans = isset($_POST['plans']) ? $_POST['plans'] : [];
            $categories = isset($_POST['categories']) ? $_POST['categories'] : [];

            $productSpecsMap = [];
            foreach ($categories as $cIndex => $catData) {
                $catName = !empty($catData['name']) ? trim($catData['name']) : 'General';
                if (isset($catData['features']) && is_array($catData['features'])) {
                    foreach ($catData['features'] as $fIndex => $featData) {
                        $featName = !empty($featData['name']) ? trim($featData['name']) : '';
                        if ($featName === '') continue;

                        if (isset($featData['values']) && is_array($featData['values'])) {
                            foreach ($featData['values'] as $pId => $val) {
                                $productSpecsMap[(int)$pId][] = [
                                    'group' => $catName,
                                    'name'  => $featName,
                                    'value' => trim((string)$val)
                                ];
                            }
                        }
                    }
                }
            }

            $now = date('Y-m-d H:i:s');

            foreach ($plans as $pId => $planData) {
                $pId = (int)$pId;
                $topHtml = isset($planData['card_top_html']) ? trim($planData['card_top_html']) : '';
                $botHtml = isset($planData['card_bottom_html']) ? trim($planData['card_bottom_html']) : '';

                $rawHighlights = !empty($planData['highlights']) ? trim($planData['highlights']) : '';
                $hLines = array_filter(array_map('trim', explode("\n", $rawHighlights)));
                $highlightsJson = json_encode(array_values($hLines));

                $detailedJson = json_encode(isset($productSpecsMap[$pId]) ? $productSpecsMap[$pId] : []);

                // If everything is completely empty, delete from FeatureDesk so it resets to default WHMCS
                $hasContent = (!empty($topHtml) || !empty($botHtml) || !empty($hLines) || !empty($productSpecsMap[$pId]));
                if ($hasContent) {
                    Capsule::table('mod_featuredesk_specs')->updateOrInsert(
                        ['product_id' => $pId],
                        [
                            'card_top_html'    => $topHtml,
                            'card_bottom_html' => $botHtml,
                            'card_highlights'  => $highlightsJson,
                            'detailed_specs'   => $detailedJson,
                            'enabled'          => 1,
                            'updated_at'       => $now,
                        ]
                    );
                } else {
                    Capsule::table('mod_featuredesk_specs')->where('product_id', $pId)->delete();
                }
            }

            header('Location: ' . $moduleLink . '&action=edit_group&gid=' . $groupId . '&saved=1');
            exit;
        }

        // 2. Handle Save Display Settings POST
        if ($action === 'save_display_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            featuredesk_save_setting('display_mode', trim($_POST['display_mode']));
            featuredesk_save_setting('clean_pricing_cards', isset($_POST['clean_pricing_cards']) ? '1' : '0');
            featuredesk_save_setting('show_scroll_btn', isset($_POST['show_scroll_btn']) ? '1' : '0');
            featuredesk_save_setting('box_title', trim($_POST['box_title']));
            featuredesk_save_setting('box_subtitle', trim($_POST['box_subtitle']));

            header('Location: ' . $moduleLink . '&action=display_settings&saved=1');
            exit;
        }

        // Render Views
        echo featuredesk_render_header($moduleLink, $action);

        switch ($action) {
            case 'edit_group':
                echo featuredesk_render_edit_group_page($moduleLink, (int)$_GET['gid']);
                break;
            case 'display_settings':
                echo featuredesk_render_display_settings_page($moduleLink);
                break;
            case 'integration_guide':
                echo featuredesk_render_integration_guide_page();
                break;
            case 'developer_info':
                echo featuredesk_render_developer_page();
                break;
            case 'products':
            default:
                echo featuredesk_render_products_page($moduleLink);
                break;
        }

        echo featuredesk_render_footer();
    }
}
