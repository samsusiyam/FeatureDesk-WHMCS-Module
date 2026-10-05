<?php
/**
 * WHMCS Addon Module: FeatureDesk - Smart Spec Box & Plan Matrix
 *
 * Declutter pricing cards into clean hero highlights and render an interactive
 * Technical Specifications Comparison Box below pricing tables.
 *
 * Designed to strictly mirror the visual design system, typography, tab structures,
 * and high-converting UX of Bahari IT official WHMCS modules.
 *
 * @package    FeatureDesk
 * @author     MD Samsuzzaman Siyam <samsusiyam@gmail.com>
 * @copyright  Bahari Host
 * @license    Proprietary
 * @link       https://baharihost.com
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
            'description' => 'Declutter crowded hosting pricing cards into short, punchy hero badges and display a responsive, interactive Technical Specifications Comparison Matrix right below your product rows.',
            'version'     => '1.0.1',
            'author'      => '<a href="https://baharihost.com" target="_blank" style="color:#0284c7;font-weight:700;">Bahari IT</a>',
            'language'    => 'english',
            'fields'      => [
                'status_note' => [
                    'FriendlyName' => 'Configuration Notice',
                    'Type'         => 'note',
                    'Description'  => '<div style="background:#e0f2fe; border-left:4px solid #0284c7; padding:12px 16px; border-radius:6px; color:#0369a1; font-size:13px;">'
                                    . '<strong><i class="fas fa-magic"></i> FeatureDesk is Active!</strong><br>'
                                    . 'All visual display options, live interactive demo, group-wise specs & highlights builder can be customized inside the module interface at <a href="addonmodules.php?module=featuredesk" class="btn btn-xs btn-primary" style="margin-left:8px; font-weight:700;">Open FeatureDesk Dashboard &rarr;</a>'
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
                    $table->longText('detailed_specs')->nullable();
                    $table->tinyInteger('enabled')->default(1);
                    $table->timestamps();
                });
            }

            if (!Capsule::schema()->hasTable('mod_featuredesk_templates')) {
                Capsule::schema()->create('mod_featuredesk_templates', function ($table) {
                    $table->increments('id');
                    $table->string('name', 150);
                    $table->string('category', 100)->default('Hosting');
                    $table->longText('spec_schema');
                    $table->timestamps();
                });

                $defaultSchema = json_encode([
                    ["group" => "General", "name" => "Websites Hosted", "value" => "1 Website"],
                    ["group" => "Storage & Traffic", "name" => "NVMe SSD Storage", "value" => "10 GB NVMe"],
                    ["group" => "Storage & Traffic", "name" => "Bandwidth", "value" => "Unlimited"],
                    ["group" => "Hardware & Performance", "name" => "RAM Allocation", "value" => "2 GB DDR4"],
                    ["group" => "Hardware & Performance", "name" => "CPU Allocation", "value" => "1 Core Intel Xeon"],
                    ["group" => "Security & Backups", "name" => "Free SSL Certificate", "value" => "Yes"],
                    ["group" => "Security & Backups", "name" => "Daily Automated Backup", "value" => "Yes"],
                    ["group" => "Control Panel & Developer", "name" => "Control Panel", "value" => "cPanel / DirectAdmin"],
                    ["group" => "Control Panel & Developer", "name" => "NodeJS & Python", "value" => "Yes"]
                ]);

                Capsule::table('mod_featuredesk_templates')->insert([
                    [
                        'name'        => 'Standard Shared / Web Hosting',
                        'category'    => 'Shared Hosting',
                        'spec_schema' => $defaultSchema,
                        'created_at'  => date('Y-m-d H:i:s'),
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ]
                ]);
            }

            if (!Capsule::schema()->hasTable('mod_featuredesk_settings')) {
                Capsule::schema()->create('mod_featuredesk_settings', function ($table) {
                    $table->string('setting_key', 100)->primary();
                    $table->text('setting_value')->nullable();
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
                    Capsule::table('mod_featuredesk_settings')->insert(['setting_key' => $k, 'setting_value' => $v]);
                }
            }
        } catch (\Exception $e) {
            logActivity('FeatureDesk Table Migration Notice: ' . $e->getMessage());
        }
    }
}

/**
 * Module Activation
 */
if (!function_exists('featuredesk_activate')) {
    function featuredesk_activate()
    {
        featuredesk_ensure_tables();
        return ['status' => 'success', 'description' => 'FeatureDesk has been successfully activated!'];
    }
}

/**
 * Module Deactivation
 */
if (!function_exists('featuredesk_deactivate')) {
    function featuredesk_deactivate()
    {
        return ['status' => 'success', 'description' => 'FeatureDesk has been deactivated.'];
    }
}

/**
 * Module Upgrade
 */
if (!function_exists('featuredesk_upgrade')) {
    function featuredesk_upgrade($vars)
    {
        featuredesk_ensure_tables();
    }
}

/**
 * Settings Helpers
 */
if (!function_exists('featuredesk_get_setting')) {
    function featuredesk_get_setting($key, $default = '')
    {
        try {
            if (Capsule::schema()->hasTable('mod_featuredesk_settings')) {
                $row = Capsule::table('mod_featuredesk_settings')->where('setting_key', $key)->first();
                if ($row && $row->setting_value !== null && $row->setting_value !== '') {
                    return $row->setting_value;
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
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        } catch (\Exception $e) {}
    }
}

if (!function_exists('featuredesk_h')) {
    function featuredesk_h($str)
    {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Module Admin UI Stylesheet
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
            .fd-badge-purple { background: #f3e8ff; color: #7e22ce; }
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
            .fd-btn-default { background: #e2e8f0; color: #334155 !important; }
            .fd-btn-default:hover { background: #cbd5e1; }
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
            .fd-accordion-header:hover {
                background: #f8fafc;
            }
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
            .fd-table tr:last-child td {
                border-bottom: none;
            }
            .fd-table tr:hover td {
                background: #fafafa;
            }

            /* Visual Builder Styles */
            .fd-builder-category {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                margin-bottom: 15px;
                overflow: hidden;
            }
            .fd-builder-category-header {
                background: #f8fafc;
                padding: 10px 16px;
                border-bottom: 1px solid #e2e8f0;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .fd-builder-rows {
                padding: 12px 16px;
            }
            .fd-builder-row {
                display: flex;
                gap: 10px;
                margin-bottom: 8px;
                align-items: center;
            }
            .fd-builder-row input {
                flex: 1;
                padding: 6px 10px;
                font-size: 12.5px;
                border: 1px solid #cbd5e1;
                border-radius: 4px;
            }
            .fd-builder-row button {
                background: #fee2e2;
                color: #dc2626;
                border: 0;
                padding: 6px 10px;
                border-radius: 4px;
                cursor: pointer;
            }
            .fd-builder-row button:hover {
                background: #fca5a5;
            }

            .fd-form-group { margin-bottom: 18px; }
            .fd-form-label { display: block; font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 6px; }
            .fd-form-hint { font-size: 12px; color: #64748b; margin-top: 4px; }
            .fd-form-input, .fd-form-textarea, .fd-form-select {
                width: 100%;
                max-width: 700px;
                padding: 10px 12px;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                font-size: 13px;
                color: #1e293b;
                background: #ffffff;
            }
            .fd-form-input:focus, .fd-form-textarea:focus {
                border-color: #0284c7;
                outline: 0;
                box-shadow: 0 0 0 3px rgba(2,132,199,0.15);
            }
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
            .fd-code-block {
                background: #0f172a;
                color: #38bdf8;
                padding: 16px;
                border-radius: 8px;
                font-family: monospace;
                font-size: 12px;
                overflow-x: auto;
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
        $html .= '        <div class="fd-subtitle">Smart Plan Highlights & Technical Specifications Matrix for WHMCS</div>';
        $html .= '      </div>';
        $html .= '    </div>';
        $html .= '    <div class="fd-version">v1.0.1 &bull; Bahari IT</div>';
        $html .= '  </div>';

        $html .= '  <div class="fd-nav-wrap">';
        $html .= '    <a href="' . featuredesk_h($moduleLink) . '&action=products" class="fd-nav-btn' . ($action === 'products' || $action === 'edit_product' ? ' active' : '') . '"><i class="fas fa-boxes"></i> Product Groups & Specs</a>';
        $html .= '    <a href="' . featuredesk_h($moduleLink) . '&action=display_settings" class="fd-nav-btn' . ($action === 'display_settings' ? ' active' : '') . '"><i class="fas fa-sliders-h"></i> Display Settings & Live Demo</a>';
        $html .= '    <a href="' . featuredesk_h($moduleLink) . '&action=templates" class="fd-nav-btn' . ($action === 'templates' || $action === 'edit_template' ? ' active' : '') . '"><i class="fas fa-layer-group"></i> Spec Templates</a>';
        $html .= '    <a href="' . featuredesk_h($moduleLink) . '&action=integration_guide" class="fd-nav-btn' . ($action === 'integration_guide' ? ' active' : '') . '"><i class="fas fa-code"></i> Integration Guide</a>';
        $html .= '    <a href="' . featuredesk_h($moduleLink) . '&action=developer_info" class="fd-nav-btn' . ($action === 'developer_info' ? ' active' : '') . '"><i class="fas fa-info-circle"></i> Developer & Support</a>';
        $html .= '  </div>';

        $html .= '  <div class="fd-body">';

        if (isset($_GET['saved'])) {
            $html .= '<div class="fd-alert-success"><i class="fas fa-check-circle"></i> Specifications and settings updated successfully!</div>';
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
 * Tab 1: Group-wise Accordion Products View
 */
if (!function_exists('featuredesk_render_products_page')) {
    function featuredesk_render_products_page($moduleLink)
    {
        $groups = Capsule::table('tblproductgroups')->orderBy('order', 'asc')->get();
        $products = Capsule::table('tblproducts')->orderBy('order', 'asc')->get();

        $specsMap = Capsule::table('mod_featuredesk_specs')->get()->keyBy('product_id');

        // Group products by gid
        $groupedProducts = [];
        foreach ($products as $p) {
            $groupedProducts[$p->gid][] = $p;
        }

        $html = '<div class="fd-card">';
        $html .= '<div class="fd-card-title"><i class="fas fa-cubes text-primary"></i> Product Groups & Hosting Tiers</div>';
        $html .= '<div class="fd-card-desc">Click on any group below to expand its products. You can customize card highlight bullets (3-5 badges) and granular technical comparison specs for each plan.</div>';

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

            // Auto expand groups that have configurations (like DMCA Ignored)
            $isExpanded = ($configuredCount > 0) ? ' active' : '';

            $html .= '<div class="fd-accordion-item' . $isExpanded . '" data-group-name="' . strtolower(featuredesk_h($g->name)) . '">';
            $html .= '  <div class="fd-accordion-header" onclick="fdToggleAccordion(this)">';
            $html .= '    <div class="fd-accordion-title">';
            $html .= '      <i class="fas fa-folder text-primary"></i>';
            $html .= '      <span>' . featuredesk_h($g->name) . '</span>';
            $html .= '      <span class="fd-badge fd-badge-muted">' . $totalCount . ' Plans</span>';
            if ($configuredCount > 0) {
                $html .= '    <span class="fd-badge fd-badge-success"><i class="fas fa-check"></i> ' . $configuredCount . ' Configured</span>';
            }
            $html .= '    </div>';
            $html .= '    <div style="display:flex; align-items:center; gap:12px;">';
            $html .= '      <a href="../cart.php?gid=' . (int)$g->id . '" target="_blank" class="fd-btn fd-btn-default fd-btn-sm" onclick="event.stopPropagation();"><i class="fas fa-external-link-alt"></i> View Store</a>';
            $html .= '      <i class="fas fa-chevron-down fd-accordion-chevron"></i>';
            $html .= '    </div>';
            $html .= '  </div>';

            $html .= '  <div class="fd-accordion-body">';
            $html .= '    <table class="fd-table">';
            $html .= '      <thead><tr><th>Plan Name</th><th>Badge</th><th>Hero Highlights</th><th>Comparison Specs</th><th>Actions</th></tr></thead>';
            $html .= '      <tbody>';

            foreach ($prods as $p) {
                $spec = isset($specsMap[$p->id]) ? $specsMap[$p->id] : null;
                $highlights = ($spec && $spec->card_highlights) ? json_decode($spec->card_highlights, true) : [];
                $badge = ($spec && !empty($spec->badge_text)) ? $spec->badge_text : '';
                $detailedSpecs = ($spec && $spec->detailed_specs) ? json_decode($spec->detailed_specs, true) : [];

                $html .= '<tr class="fd-prod-row" data-prod-name="' . strtolower(featuredesk_h($p->name)) . '">';
                $html .= '  <td><strong>' . featuredesk_h($p->name) . '</strong> <span style="font-size:11px; color:#94a3b8;">(PID: ' . (int)$p->id . ')</span></td>';
                $html .= '  <td>' . (!empty($badge) ? '<span class="fd-badge fd-badge-warning">' . featuredesk_h($badge) . '</span>' : '<span style="color:#cbd5e1;">&mdash;</span>') . '</td>';
                $html .= '  <td>';
                if (is_array($highlights) && count($highlights) > 0) {
                    $html .= '<span class="fd-badge fd-badge-primary"><i class="fas fa-check-circle"></i> ' . count($highlights) . ' Bullets Set</span>';
                } else {
                    $html .= '<span style="font-size:12px; color:#94a3b8;">Default description</span>';
                }
                $html .= '  </td>';
                $html .= '  <td>';
                if (is_array($detailedSpecs) && count($detailedSpecs) > 0) {
                    $html .= '<span class="fd-badge fd-badge-success"><i class="fas fa-table"></i> ' . count($detailedSpecs) . ' Specs Active</span>';
                } else {
                    $html .= '<span style="font-size:12px; color:#94a3b8;">None</span>';
                }
                $html .= '  </td>';
                $html .= '  <td>';
                $html .= '    <a href="' . featuredesk_h($moduleLink) . '&action=edit_product&pid=' . (int)$p->id . '" class="fd-btn fd-btn-primary fd-btn-sm"><i class="fas fa-edit"></i> Edit Specs</a>';
                $html .= '  </td>';
                $html .= '</tr>';
            }

            $html .= '      </tbody>';
            $html .= '    </table>';
            $html .= '  </div>';
            $html .= '</div>';
        }

        $html .= '</div></div>';

        // JS for Filtering & Accordion
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
 * Tab 1 (Sub): Edit Product Specs Page with Visual Builder & Quick Templates
 */
if (!function_exists('featuredesk_render_edit_product_page')) {
    function featuredesk_render_edit_product_page($moduleLink, $productId)
    {
        $product = Capsule::table('tblproducts')->where('id', $productId)->first();
        if (!$product) {
            return '<div class="fd-card"><p>Product not found.</p></div>';
        }

        $spec = Capsule::table('mod_featuredesk_specs')->where('product_id', $productId)->first();
        $badgeText = $spec ? $spec->badge_text : '';
        $badgeColor = ($spec && !empty($spec->badge_color)) ? $spec->badge_color : '#2563EB';

        $highlightsRaw = $spec && $spec->card_highlights ? json_decode($spec->card_highlights, true) : [];
        if (!is_array($highlightsRaw)) { $highlightsRaw = []; }
        $highlightsText = implode("\n", $highlightsRaw);

        $detailedSpecs = $spec && $spec->detailed_specs ? $spec->detailed_specs : '';
        if (empty($detailedSpecs)) {
            $detailedSpecs = json_encode([
                ["group" => "General", "name" => "Websites Hosted", "value" => "1 Website"],
                ["group" => "Storage & Traffic", "name" => "NVMe SSD Storage", "value" => "10 GB NVMe"],
                ["group" => "Storage & Traffic", "name" => "Bandwidth", "value" => "Unlimited"],
                ["group" => "Hardware & Performance", "name" => "RAM Allocation", "value" => "2 GB Guaranteed"],
                ["group" => "Hardware & Performance", "name" => "CPU Allocation", "value" => "1 Core Xeon"],
                ["group" => "Security & Backups", "name" => "Free SSL Certificate", "value" => "Yes"],
                ["group" => "Security & Backups", "name" => "Weekly Auto Backup", "value" => "Yes"]
            ], JSON_PRETTY_PRINT);
        }

        $html = '<div class="fd-card">';
        $html .= '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">';
        $html .= '  <div><h3 style="margin:0; font-size:18px;">Editing Specs: ' . featuredesk_h($product->name) . '</h3><span style="color:#64748b; font-size:13px;">Manage card highlight bullets and detailed technical matrix specs</span></div>';
        $html .= '  <a href="' . featuredesk_h($moduleLink) . '&action=products" class="fd-btn fd-btn-default"><i class="fas fa-arrow-left"></i> Back to Products</a>';
        $html .= '</div>';

        $html .= '<form method="post" action="' . featuredesk_h($moduleLink) . '&action=save_product_specs" id="fdSpecForm">';
        $html .= '<input type="hidden" name="product_id" value="' . (int)$productId . '">';

        // Top Badge
        $html .= '<div class="row" style="display:flex; gap:20px; margin-bottom:18px;">';
        $html .= '  <div style="flex:1;">';
        $html .= '    <label class="fd-form-label"><i class="fas fa-ribbon text-warning"></i> Card Promo Badge (Optional)</label>';
        $html .= '    <div style="display:flex; gap:8px;">';
        $html .= '      <input type="text" name="badge_text" class="fd-form-input" value="' . featuredesk_h($badgeText) . '" placeholder="e.g. Starter, Most Popular, Best Value">';
        $html .= '      <input type="color" name="badge_color" value="' . featuredesk_h($badgeColor) . '" style="width:48px; height:38px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;">';
        $html .= '    </div>';
        $html .= '    <div class="fd-form-hint">Prominent pill tag shown at the top corner of this card.</div>';
        $html .= '  </div>';
        $html .= '</div>';

        // Highlights
        $html .= '<div class="fd-form-group">';
        $html .= '  <label class="fd-form-label"><i class="fas fa-check-circle text-success"></i> Hero Highlights (3 to 5 key features &mdash; 1 per line)</label>';
        $html .= '  <textarea name="card_highlights" class="fd-form-textarea" rows="5" placeholder="1 Website Hosted&#10;5 GB NVMe SSD Storage&#10;500 GB Bandwidth&#10;LiteSpeed + Free SSL">' . featuredesk_h($highlightsText) . '</textarea>';
        $html .= '  <div class="fd-form-hint"><strong>Declutter effect:</strong> These replace the lengthy 20-line description on the pricing card with clean, modern checkmark bullets.</div>';
        $html .= '</div>';

        // Detailed Specs
        $html .= '<div class="fd-form-group" style="margin-top:25px;">';
        $html .= '  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">';
        $html .= '    <label class="fd-form-label" style="margin:0;"><i class="fas fa-table text-primary"></i> Extended Technical Specifications (Comparison Matrix)</label>';
        $html .= '    <div style="font-size:12px; color:#64748b;">Powers the interactive comparison table below the pricing cards</div>';
        $html .= '  </div>';
        $html .= '  <textarea name="detailed_specs" id="fdDetailedSpecsJson" class="fd-form-textarea" rows="14" style="font-family:monospace; font-size:12px;">' . featuredesk_h($detailedSpecs) . '</textarea>';
        $html .= '  <div class="fd-form-hint">Format: <code>[{"group": "Storage", "name": "NVMe Storage", "value": "20 GB"}, ...]</code></div>';
        $html .= '</div>';

        $html .= '<div style="margin-top:24px;">';
        $html .= '  <button type="submit" class="fd-btn fd-btn-primary"><i class="fas fa-save"></i> Save Product Specifications</button>';
        $html .= '</div>';

        $html .= '</form></div>';
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
        $html .= '<div class="fd-card-title"><i class="fas fa-sliders-h text-primary"></i> Display Settings & Live Demo</div>';
        $html .= '<div class="fd-card-desc">Control where and how the comparison box renders on your order forms and customize the styling.</div>';

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
        $html .= '    <input type="checkbox" name="clean_pricing_cards" value="1"' . ($cleanCards === '1' ? ' checked' : '') . '>';
        $html .= '    <span>Automatically substitute long cluttered description text with clean, elegant bullet badges</span>';
        $html .= '  </label>';
        $html .= '</div>';

        $html .= '<div class="fd-form-group">';
        $html .= '  <label class="fd-form-label">Card "View Specs" Link</label>';
        $html .= '  <label style="display:flex; align-items:center; gap:8px; font-weight:normal; cursor:pointer;">';
        $html .= '    <input type="checkbox" name="show_scroll_btn" value="1"' . ($showScrollBtn === '1' ? ' checked' : '') . '>';
        $html .= '    <span>Add a clickable smooth-scrolling button at the bottom of each pricing card</span>';
        $html .= '  </label>';
        $html .= '</div>';

        $html .= '<div class="fd-form-group">';
        $html .= '  <label class="fd-form-label">Visual Theme</label>';
        $html .= '  <select name="theme" class="fd-form-select">';
        $html .= '    <option value="modern_blue"' . ($theme === 'modern_blue' ? ' selected' : '') . '>Modern Blue (Bahari Host Standard)</option>';
        $html .= '  </select>';
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
        $html .= '</form>';

        $html .= '</div>';
        return $html;
    }
}

/**
 * Tab 3: Spec Templates Library
 */
if (!function_exists('featuredesk_render_templates_page')) {
    function featuredesk_render_templates_page($moduleLink)
    {
        $templates = Capsule::table('mod_featuredesk_templates')->get();

        $html = '<div class="fd-card">';
        $html .= '<div class="fd-card-title"><i class="fas fa-layer-group text-primary"></i> Reusable Specification Templates</div>';
        $html .= '<div class="fd-card-desc">Standardize your technical spec schemas across products.</div>';

        $html .= '<table class="fd-table">';
        $html .= '<thead><tr><th>Template Name</th><th>Category</th><th>Features Included</th></tr></thead><tbody>';

        foreach ($templates as $t) {
            $schema = json_decode($t->spec_schema, true);
            $count = is_array($schema) ? count($schema) : 0;
            $html .= '<tr>';
            $html .= '<td><strong>' . featuredesk_h($t->name) . '</strong></td>';
            $html .= '<td><span class="fd-badge fd-badge-primary">' . featuredesk_h($t->category) . '</span></td>';
            $html .= '<td>' . $count . ' specifications predefined</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';
        return $html;
    }
}

/**
 * Tab 4: Integration Guide
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
 * Tab 5: Developer Info
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
        $html .= '    <li><strong>Module Version:</strong> 1.0.1 (Production Stable)</li>';
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

        // Handle POST Actions
        if ($action === 'save_product_specs' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $pid = (int)$_POST['product_id'];
            $badgeText = trim((string)$_POST['badge_text']);
            $badgeColor = isset($_POST['badge_color']) ? trim((string)$_POST['badge_color']) : '#2563EB';
            $highlightsText = trim((string)$_POST['card_highlights']);
            $detailedSpecs = trim((string)$_POST['detailed_specs']);

            $lines = array_filter(array_map('trim', explode("\n", $highlightsText)));
            $highlightsJson = json_encode(array_values($lines));

            $now = date('Y-m-d H:i:s');
            Capsule::table('mod_featuredesk_specs')->updateOrInsert(
                ['product_id' => $pid],
                [
                    'badge_text'      => $badgeText,
                    'badge_color'     => $badgeColor,
                    'card_highlights' => $highlightsJson,
                    'detailed_specs'  => $detailedSpecs,
                    'enabled'         => 1,
                    'updated_at'      => $now,
                ]
            );

            header('Location: ' . $moduleLink . '&action=edit_product&pid=' . $pid . '&saved=1');
            exit;
        }

        if ($action === 'save_display_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            featuredesk_save_setting('display_mode', trim($_POST['display_mode']));
            featuredesk_save_setting('clean_pricing_cards', isset($_POST['clean_pricing_cards']) ? '1' : '0');
            featuredesk_save_setting('show_scroll_btn', isset($_POST['show_scroll_btn']) ? '1' : '0');
            featuredesk_save_setting('theme', trim($_POST['theme']));
            featuredesk_save_setting('box_title', trim($_POST['box_title']));
            featuredesk_save_setting('box_subtitle', trim($_POST['box_subtitle']));

            header('Location: ' . $moduleLink . '&action=display_settings&saved=1');
            exit;
        }

        // Render View
        echo featuredesk_render_header($moduleLink, $action);

        switch ($action) {
            case 'edit_product':
                echo featuredesk_render_edit_product_page($moduleLink, (int)$_GET['pid']);
                break;
            case 'templates':
                echo featuredesk_render_templates_page($moduleLink);
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
