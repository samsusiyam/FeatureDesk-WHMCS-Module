<?php
/**
 * WHMCS Addon Module: FeatureDesk - Category Spec Boxes & Server Notices
 *
 * Ultra-modern, responsive Category-level Feature Matrix & Server Advisory Notice Manager for WHMCS.
 * Allows hosting providers to display custom responsive HTML feature checklists (like 4-column grids),
 * server backup policy notices, and dynamic custom HTML blocks below pricing cards per product category.
 *
 * Strict Rule: Keeps WHMCS native product descriptions and pricing cards 100% untouched.
 *
 * @package    FeatureDesk
 * @author     MD Samsuzzaman Siyam <samsusiyam@gmail.com>
 * @copyright  Bahari IT
 * @license    Proprietary
 * @version    2.0.0
 */

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Module Configuration
 */
function featuredesk_config()
{
    return [
        "name"        => "FeatureDesk - Category Spec Boxes & Server Notices",
        "description" => "Category-based responsive 4-column feature checklists, server backup notices, and custom HTML boxes below WHMCS pricing cards.",
        "version"     => "2.0.0",
        "author"      => "Bahari IT / MD Samsuzzaman Siyam",
        "language"    => "english",
        "fields"      => [
            "module_status" => [
                "FriendlyName" => "Global Status",
                "Type"         => "yesno",
                "Size"         => "25",
                "Description"  => "Enable or disable FeatureDesk across all client-area cart categories.",
                "Default"      => "yes",
            ],
            "container_width" => [
                "FriendlyName" => "Max Container Width",
                "Type"         => "text",
                "Size"         => "15",
                "Description"  => "Maximum container width below pricing cards (e.g. 1200px or 100%).",
                "Default"      => "1200px",
            ],
        ]
    ];
}

/**
 * Table Creation & Migration Helper
 */
function featuredesk_ensure_tables()
{
    // 1. Category Features & Notices Table
    if (!Capsule::schema()->hasTable('mod_featuredesk_categories')) {
        Capsule::schema()->create('mod_featuredesk_categories', function ($table) {
            $table->increments('id');
            $table->integer('group_id')->unique();
            $table->tinyInteger('status')->default(1);
            $table->mediumText('features_html')->nullable();
            $table->mediumText('backup_notice_html')->nullable();
            $table->longText('extra_boxes')->nullable();
            $table->text('custom_css')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    } else {
        // Upgrade columns if missing
        if (!Capsule::schema()->hasColumn('mod_featuredesk_categories', 'backup_notice_html')) {
            Capsule::schema()->table('mod_featuredesk_categories', function ($table) {
                $table->mediumText('backup_notice_html')->nullable()->after('features_html');
            });
        }
        if (!Capsule::schema()->hasColumn('mod_featuredesk_categories', 'extra_boxes')) {
            Capsule::schema()->table('mod_featuredesk_categories', function ($table) {
                $table->longText('extra_boxes')->nullable()->after('backup_notice_html');
            });
        }
    }

    // 2. Global Settings Table
    if (!Capsule::schema()->hasTable('mod_featuredesk_settings')) {
        Capsule::schema()->create('mod_featuredesk_settings', function ($table) {
            $table->increments('id');
            $table->string('setting', 64)->unique();
            $table->text('value')->nullable();
        });

        // Default settings
        $defaults = [
            'status'              => '1',
            'container_max_width' => '1200px',
            'color_primary'       => '#0284c7',
            'global_custom_css'   => '',
        ];
        foreach ($defaults as $k => $v) {
            Capsule::table('mod_featuredesk_settings')->insert(['setting' => $k, 'value' => $v]);
        }
    }
}

/**
 * Clean & Decode HTML helper to counter WHMCS request sanitization
 */
function featuredesk_decode_html($raw)
{
    if (empty($raw)) {
        return '';
    }
    $decoded = html_entity_decode((string)$raw, ENT_QUOTES, 'UTF-8');
    if (strpos($decoded, '&lt;') !== false || strpos($decoded, '&gt;') !== false || strpos($decoded, '&quot;') !== false) {
        $decoded = html_entity_decode($decoded, ENT_QUOTES, 'UTF-8');
    }
    return trim($decoded);
}

/**
 * Module Activation
 */
function featuredesk_activate()
{
    try {
        featuredesk_ensure_tables();
        return ['status' => 'success', 'description' => 'FeatureDesk v2.0 successfully activated. Category spec boxes & server notices table initialized.'];
    } catch (\Exception $e) {
        return ['status' => 'error', 'description' => 'Failed to initialize database: ' . $e->getMessage()];
    }
}

/**
 * Module Deactivation
 */
function featuredesk_deactivate()
{
    return ['status' => 'success', 'description' => 'FeatureDesk v2.0 deactivated. Database records preserved.'];
}

/**
 * Module Upgrade
 */
function featuredesk_upgrade($vars)
{
    featuredesk_ensure_tables();
}

/**
 * Sample Templates Generator
 */
function featuredesk_get_sample_templates()
{
    $featuresSample = '<div class="fd-card fd-features-card">
  <div class="fd-card-header">
    <div>
      <h3 class="fd-card-title">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--fd-primary);"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Advanced Features
      </h3>
      <p class="fd-card-subtitle">Included with all web hosting plans in this category</p>
    </div>
  </div>
  <div class="fd-grid-4">
    <div class="fd-grid-col">
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> 30-Day Money-Back Guarantee</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> 99.9% Server Uptime Guarantee</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> 24/7 Technical Support</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> 20 GBPS DDoS Protection</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Free Let\'s Encrypt SSL Certificates</div>
    </div>
    <div class="fd-grid-col">
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> CloudLinux OS</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> LiteSpeed Web Server</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> cPanel Control Panel</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Node.js Support</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Python Support</div>
    </div>
    <div class="fd-grid-col">
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Unlimited Subdomains</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Unlimited MySQL Databases</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Unlimited Email Accounts</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Ruby on Rails Support</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Multiple PHP Versions (5.6 - 8.x)</div>
    </div>
    <div class="fd-grid-col">
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Enterprise NVMe SSD Storage</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Imunify360 AI Antivirus & WAF</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> 1-Click Softaculous Apps Installer</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Free Website Migration Assistance</div>
      <div class="fd-item"><svg class="fd-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> HTTP/3 & QUIC Enabled</div>
    </div>
  </div>
</div>';

    $noticeSample = '<div class="fd-notice-card">
  <svg class="fd-notice-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
  <div class="fd-notice-body">
    <div class="fd-notice-title">Important Server Backup Policy & Advisory</div>
    <p class="fd-notice-text">
      We take automated disaster-recovery server backups on a regular weekly cycle. However, this service is provided as a courtesy for disaster recovery only. We strongly advise all clients to create and download their own regular full cPanel backups to local computers or off-site cloud storage to guarantee absolute data security.
    </p>
  </div>
</div>';

    return [
        'features' => $featuresSample,
        'notice'   => $noticeSample
    ];
}

/**
 * Admin Panel Output
 */
function featuredesk_output($vars)
{
    featuredesk_ensure_tables();
    $modulelink = $vars['modulelink'];
    $action     = isset($_GET['action']) ? trim($_GET['action']) : 'categories';
    $successMsg = '';
    $errorMsg   = '';

    // Save Category Configuration
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_category') {
        check_token("WHMCS.admin.default");

        $groupId          = (int)$_POST['group_id'];
        $status           = isset($_POST['status']) ? 1 : 0;
        $featuresHtml     = featuredesk_decode_html(isset($_POST['features_html']) ? $_POST['features_html'] : '');
        $backupNoticeHtml = featuredesk_decode_html(isset($_POST['backup_notice_html']) ? $_POST['backup_notice_html'] : '');
        $customCss        = featuredesk_decode_html(isset($_POST['custom_css']) ? $_POST['custom_css'] : '');

        // Process Extra Dynamic Boxes
        $extraBoxes = [];
        if (!empty($_POST['extra_box_title']) && is_array($_POST['extra_box_title'])) {
            foreach ($_POST['extra_box_title'] as $idx => $bTitle) {
                $bHtml   = featuredesk_decode_html(isset($_POST['extra_box_html'][$idx]) ? $_POST['extra_box_html'][$idx] : '');
                $bStatus = isset($_POST['extra_box_status'][$idx]) ? 1 : 0;
                if (!empty(trim($bTitle)) || !empty(trim($bHtml))) {
                    $extraBoxes[] = [
                        'id'     => 'box_' . substr(md5(uniqid((string)$idx, true)), 0, 8),
                        'title'  => trim($bTitle),
                        'html'   => $bHtml,
                        'status' => $bStatus
                    ];
                }
            }
        }

        try {
            Capsule::table('mod_featuredesk_categories')->updateOrInsert(
                ['group_id' => $groupId],
                [
                    'status'             => $status,
                    'features_html'      => $featuresHtml,
                    'backup_notice_html' => $backupNoticeHtml,
                    'extra_boxes'        => json_encode($extraBoxes),
                    'custom_css'         => $customCss,
                    'updated_at'         => date('Y-m-d H:i:s'),
                ]
            );
            $successMsg = "Category settings saved successfully!";
        } catch (\Exception $e) {
            $errorMsg = "Error saving category: " . $e->getMessage();
        }
    }

    // Save Global Settings
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_global_settings') {
        check_token("WHMCS.admin.default");

        $settingsToUpdate = [
            'status'              => isset($_POST['global_status']) ? '1' : '0',
            'container_max_width' => !empty($_POST['container_max_width']) ? trim($_POST['container_max_width']) : '1200px',
            'color_primary'       => !empty($_POST['color_primary']) ? trim($_POST['color_primary']) : '#0284c7',
            'global_custom_css'   => featuredesk_decode_html(isset($_POST['global_custom_css']) ? $_POST['global_custom_css'] : ''),
        ];

        try {
            foreach ($settingsToUpdate as $key => $val) {
                Capsule::table('mod_featuredesk_settings')->updateOrInsert(
                    ['setting' => $key],
                    ['value' => $val]
                );
            }
            $successMsg = "Global settings updated successfully!";
        } catch (\Exception $e) {
            $errorMsg = "Error updating settings: " . $e->getMessage();
        }
    }

    // Load Product Groups
    $groups = Capsule::table('tblproductgroups')
        ->orderBy('order', 'asc')
        ->orderBy('name', 'asc')
        ->get();

    // Load Category Configurations
    $catConfigs = [];
    $rawConfigs = Capsule::table('mod_featuredesk_categories')->get();
    foreach ($rawConfigs as $c) {
        $catConfigs[$c->group_id] = $c;
    }

    // Load Global Settings
    $globalSettings = [];
    $rawSettings = Capsule::table('mod_featuredesk_settings')->get();
    foreach ($rawSettings as $s) {
        $globalSettings[$s->setting] = $s->value;
    }

    $activeGid = isset($_GET['gid']) ? (int)$_GET['gid'] : ($groups->isNotEmpty() ? $groups->first()->id : 0);
    $activeGroup = null;
    foreach ($groups as $g) {
        if ($g->id == $activeGid) {
            $activeGroup = $g;
            break;
        }
    }
    if (!$activeGroup && $groups->isNotEmpty()) {
        $activeGroup = $groups->first();
        $activeGid   = $activeGroup->id;
    }

    $activeConfig = isset($catConfigs[$activeGid]) ? $catConfigs[$activeGid] : null;
    $samples      = featuredesk_get_sample_templates();
    $csrfToken    = generate_token("WHMCS.admin.default");

    // Output Admin Styles & Interface (Chatwoot & Modern WHMCS style)
    ?>
    <style>
        .fd-module-wrap {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
            overflow: hidden;
            margin-top: 10px;
            margin-bottom: 30px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .fd-module-header {
            background: #12589b;
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #ffffff;
        }
        .fd-header-brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .fd-brand-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #ffffff;
        }
        .fd-brand-title {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.3px;
        }
        .fd-brand-subtitle {
            margin: 3px 0 0 0;
            font-size: 13px;
            opacity: 0.9;
            color: #dbeafe;
        }
        .fd-header-version {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 6px 14px;
            border-radius: 999px;
            color: #ffffff;
            text-transform: uppercase;
        }
        .fd-nav-container {
            background: #ffffff;
            border-bottom: 1px solid #dbe5ee;
            padding: 12px 28px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .fd-nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 10px 18px;
            background: #f8fafc;
            border: 1px solid #d6e0ec;
            border-radius: 8px;
            color: #334155;
            text-decoration: none !important;
            font-size: 13.5px;
            font-weight: 700;
            transition: all 0.2s ease;
        }
        .fd-nav-btn:hover {
            background: #edf2f7;
            color: #0f172a;
            border-color: #cbd5e1;
        }
        .fd-nav-btn.active {
            background: #1267b3;
            border-color: #1267b3;
            color: #ffffff !important;
        }
        .fd-module-body {
            padding: 26px 28px;
            background: #eef3f8;
        }
        .fd-layout {
            display: flex;
            gap: 24px;
        }
        .fd-sidebar {
            width: 290px;
            flex-shrink: 0;
        }
        .fd-content {
            flex: 1;
            min-width: 0;
        }
        .fd-group-nav {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        .fd-group-nav-header {
            padding: 14px 18px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 700;
            font-size: 13px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .fd-group-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 18px;
            color: #334155;
            text-decoration: none !important;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.15s ease;
            font-size: 13.5px;
            font-weight: 600;
        }
        .fd-group-link:hover {
            background: #f1f5f9;
            color: #1267b3;
        }
        .fd-group-link.active {
            background: #eff6ff;
            color: #1267b3;
            font-weight: 700;
            border-left: 4px solid #1267b3;
        }
        .fd-group-pill {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            background: #e2e8f0;
            color: #475569;
            font-weight: 700;
        }
        .fd-group-pill.active-pill {
            background: #dcfce7;
            color: #15803d;
        }
        .fd-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 22px 26px;
            margin-bottom: 22px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        .fd-panel-title {
            font-size: 17px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .fd-panel-subtitle {
            font-size: 13px;
            color: #64748b;
            margin: 0 0 16px 0;
            line-height: 1.45;
        }
        .fd-code-area {
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 13px;
            line-height: 1.45;
            background: #0f172a;
            color: #f8fafc;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 14px;
            width: 100%;
            box-sizing: border-box;
            resize: vertical;
        }
        .fd-code-area:focus {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.25);
        }
        .fd-template-btn {
            font-size: 12px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 6px;
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            cursor: pointer;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .fd-template-btn:hover {
            background: #bae6fd;
            color: #0284c7;
        }
        .fd-extra-box-row {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            position: relative;
        }
        .fd-extra-box-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .fd-btn-delete-box {
            color: #ef4444;
            background: #fee2e2;
            border: 1px solid #fecaca;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 600;
        }
        .fd-btn-delete-box:hover {
            background: #fca5a5;
            color: #b91c1c;
        }
        .fd-save-bar {
            position: sticky;
            bottom: 20px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(8px);
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 14px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            margin-top: 24px;
            z-index: 100;
        }
        .fd-btn-primary {
            background: #1267b3;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .fd-btn-primary:hover {
            background: #0d5292;
            color: #ffffff;
        }
    </style>

    <div class="fd-module-wrap">
        <!-- Module Header (Chatwoot & Modern WHMCS style) -->
        <div class="fd-module-header">
            <div class="fd-header-brand">
                <div class="fd-brand-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div>
                    <h1 class="fd-brand-title">FeatureDesk</h1>
                    <p class="fd-brand-subtitle">Category Spec Boxes, Feature Checklists & Server Advisories</p>
                </div>
            </div>
            <div class="fd-header-meta">
                <span class="fd-header-version">v2.0 • BAHARI IT</span>
            </div>
        </div>

        <!-- Navigation Tabs Bar -->
        <div class="fd-nav-container">
            <a href="<?php echo $modulelink; ?>&action=categories" class="fd-nav-btn <?php echo ($action === 'categories') ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> Category Features & Notices
            </a>
            <a href="<?php echo $modulelink; ?>&action=display_settings" class="fd-nav-btn <?php echo ($action === 'display_settings') ? 'active' : ''; ?>">
                <i class="fas fa-sliders-h"></i> Display Settings
            </a>
            <a href="<?php echo $modulelink; ?>&action=integration_guide" class="fd-nav-btn <?php echo ($action === 'integration_guide') ? 'active' : ''; ?>">
                <i class="fas fa-code"></i> Integration Guide
            </a>
            <a href="<?php echo $modulelink; ?>&action=developer_info" class="fd-nav-btn <?php echo ($action === 'developer_info') ? 'active' : ''; ?>">
                <i class="fas fa-info-circle"></i> Developer & Support
            </a>
        </div>

        <!-- Module Body -->
        <div class="fd-module-body">

            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success alert-dismissible" style="border-radius:8px; margin-bottom:20px;">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMsg); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger alert-dismissible" style="border-radius:8px; margin-bottom:20px;">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($errorMsg); ?>
                </div>
            <?php endif; ?>

            <?php if ($action === 'display_settings'): ?>
                <!-- Display & Global Settings Tab -->
                <div class="fd-panel">
                    <div class="fd-panel-title">
                        <span><i class="fas fa-sliders-h" style="color:#1267b3; margin-right:8px;"></i> Global Display & Layout Settings</span>
                        <a href="<?php echo $modulelink; ?>&action=categories" class="btn btn-sm btn-default"><i class="fas fa-arrow-left"></i> Back to Categories</a>
                    </div>
                    <p class="fd-panel-subtitle">Configure module-wide behavior and styling across all order forms</p>

                    <form method="post" action="<?php echo $modulelink; ?>&action=display_settings">
                        <input type="hidden" name="token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="save_global_settings">

                        <div class="form-group" style="margin-bottom: 22px;">
                            <label style="font-weight: 700;">Global FeatureDesk Status</label>
                            <div class="checkbox" style="margin-top:2px;">
                                <label style="font-weight: 500;">
                                    <input type="checkbox" name="global_status" value="1" <?php echo (!isset($globalSettings['status']) || $globalSettings['status'] == '1') ? 'checked' : ''; ?>>
                                    Enable FeatureDesk rendering on client-area order forms
                                </label>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 22px;">
                            <label style="font-weight: 700;">Container Max Width</label>
                            <input type="text" class="form-control" name="container_max_width" value="<?php echo htmlspecialchars(isset($globalSettings['container_max_width']) ? $globalSettings['container_max_width'] : '1200px'); ?>" style="max-width:320px;">
                            <span class="help-block" style="font-size:12px; color:#64748b;">Default: <code>1200px</code>. Controls maximum width of the bottom features section.</span>
                        </div>

                        <div class="form-group" style="margin-bottom: 22px;">
                            <label style="font-weight: 700;">Primary Accent Color</label>
                            <input type="color" name="color_primary" value="<?php echo htmlspecialchars(isset($globalSettings['color_primary']) ? $globalSettings['color_primary'] : '#0284c7'); ?>" style="height:38px; width:70px; padding:2px; border:1px solid #ccc; border-radius:4px; cursor:pointer;">
                            <span class="help-block" style="font-size:12px; color:#64748b;">Used for feature section icons and accents.</span>
                        </div>

                        <div class="form-group" style="margin-bottom: 22px;">
                            <label style="font-weight: 700;">Global Custom CSS</label>
                            <textarea class="fd-code-area" name="global_custom_css" rows="6"><?php echo htmlspecialchars(isset($globalSettings['global_custom_css']) ? $globalSettings['global_custom_css'] : ''); ?></textarea>
                            <span class="help-block" style="font-size:12px; color:#64748b;">Applied globally to all order forms where FeatureDesk renders.</span>
                        </div>

                        <button type="submit" class="fd-btn-primary"><i class="fas fa-save"></i> Save Global Settings</button>
                    </form>
                </div>

            <?php elseif ($action === 'integration_guide'): ?>
                <!-- Integration Guide Tab -->
                <div class="fd-panel">
                    <div class="fd-panel-title">
                        <span><i class="fas fa-code" style="color:#1267b3; margin-right:8px;"></i> Integration & Implementation Guide</span>
                    </div>
                    <p class="fd-panel-subtitle">How FeatureDesk v2.0 seamlessly hooks into WHMCS cart templates without touching product descriptions</p>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top:20px;">
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:18px;">
                            <h4 style="margin:0 0 8px 0; color:#0f172a; font-weight:700;"><i class="fas fa-check-circle" style="color:#10b981;"></i> 100% Non-Destructive</h4>
                            <p style="margin:0; font-size:13px; color:#475569; line-height:1.5;">
                                Your WHMCS native product descriptions, pricing matrices, and order form buttons remain completely untouched. FeatureDesk hooks underneath the pricing grid.
                            </p>
                        </div>
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:18px;">
                            <h4 style="margin:0 0 8px 0; color:#0f172a; font-weight:700;"><i class="fas fa-mobile-alt" style="color:#0284c7;"></i> Mobile Responsive</h4>
                            <p style="margin:0; font-size:13px; color:#475569; line-height:1.5;">
                                Checklists dynamically adapt to the user's viewport: 4 columns on desktop, 2 columns on tablet, and clean stacked items on smartphones.
                            </p>
                        </div>
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:18px;">
                            <h4 style="margin:0 0 8px 0; color:#0f172a; font-weight:700;"><i class="fas fa-shield-alt" style="color:#eab308;"></i> Server Advisories</h4>
                            <p style="margin:0; font-size:13px; color:#475569; line-height:1.5;">
                                Highlight important backup advisories, server terms, or fair-use policies prominently below your plans to prevent customer disputes.
                            </p>
                        </div>
                    </div>
                </div>

            <?php elseif ($action === 'developer_info'): ?>
                <!-- Developer & Support Tab -->
                <div class="fd-panel">
                    <div class="fd-panel-title">
                        <span><i class="fas fa-info-circle" style="color:#1267b3; margin-right:8px;"></i> Developer & Support Information</span>
                    </div>
                    <p class="fd-panel-subtitle">Module development, licensing, and direct assistance</p>

                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:20px; max-width:600px;">
                        <table class="table" style="margin-bottom:0;">
                            <tr>
                                <td style="width:160px; font-weight:700; border-top:none;">Module Name:</td>
                                <td style="border-top:none;">FeatureDesk (v2.0)</td>
                            </tr>
                            <tr>
                                <td style="font-weight:700;">Author:</td>
                                <td>MD Samsuzzaman Siyam</td>
                            </tr>
                            <tr>
                                <td style="font-weight:700;">Company:</td>
                                <td>Bahari IT / Bahari Host</td>
                            </tr>
                            <tr>
                                <td style="font-weight:700;">Support Website:</td>
                                <td><a href="https://support.baharihost.com" target="_blank">support.baharihost.com</a></td>
                            </tr>
                            <tr>
                                <td style="font-weight:700;">Architecture:</td>
                                <td>Category-level HTML Feature Matrix & Modular Hooks</td>
                            </tr>
                        </table>
                    </div>
                </div>

            <?php else: ?>
                <!-- Category Specific Features Editor (Default: action=categories) -->
                <div class="fd-layout">
                    <!-- Sidebar: Product Groups list -->
                    <div class="fd-sidebar">
                        <div class="fd-group-nav">
                            <div class="fd-group-nav-header">
                                <i class="fas fa-layer-group"></i> Product Groups
                            </div>
                            <?php if ($groups->isEmpty()): ?>
                                <div style="padding:15px; color:#64748b; font-size:13px;">No product groups found in WHMCS.</div>
                            <?php else: ?>
                                <?php foreach ($groups as $g): ?>
                                    <?php
                                    $cfg = isset($catConfigs[$g->id]) ? $catConfigs[$g->id] : null;
                                    $isConfigured = $cfg && (!empty($cfg->features_html) || !empty($cfg->backup_notice_html));
                                    $isActive = ($g->id == $activeGid);
                                    ?>
                                    <a href="<?php echo $modulelink; ?>&action=categories&gid=<?php echo $g->id; ?>" class="fd-group-link <?php echo $isActive ? 'active' : ''; ?>">
                                        <span><?php echo htmlspecialchars($g->name); ?></span>
                                        <span class="fd-group-pill <?php echo $isConfigured ? 'active-pill' : ''; ?>">
                                            <?php echo $isConfigured ? 'Active' : 'Empty'; ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Main Content Editor -->
                    <div class="fd-content">
                        <?php if ($activeGroup): ?>
                            <form method="post" action="<?php echo $modulelink; ?>&action=categories&gid=<?php echo $activeGid; ?>" id="fd-category-form">
                                <input type="hidden" name="token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="save_category">
                                <input type="hidden" name="group_id" value="<?php echo $activeGid; ?>">

                                <!-- Category Settings Header -->
                                <div class="fd-panel">
                                    <div class="fd-panel-title">
                                        <span>
                                            <i class="fas fa-folder-open" style="color:#1267b3; margin-right:8px;"></i>
                                            <?php echo htmlspecialchars($activeGroup->name); ?>
                                        </span>
                                        <div>
                                            <label style="font-weight:600; font-size:14px; cursor:pointer;">
                                                <input type="checkbox" name="status" value="1" <?php echo (!$activeConfig || $activeConfig->status == 1) ? 'checked' : ''; ?>>
                                                Enable for this Group
                                            </label>
                                        </div>
                                    </div>
                                    <p class="fd-panel-subtitle">
                                        Configure custom feature checklists and backup notices rendered under pricing cards for this category.
                                        <a href="../cart.php?gid=<?php echo $activeGid; ?>" target="_blank" style="margin-left:8px; font-weight:700; color:#1267b3;">
                                            <i class="fas fa-external-link-alt"></i> View Category Storefront
                                        </a>
                                    </p>
                                </div>

                                <!-- 1. Features HTML Box -->
                                <div class="fd-panel">
                                    <div class="fd-panel-title">
                                        <span><i class="fas fa-th-list" style="color:#10b981; margin-right:8px;"></i> 1. Category Features Box (Custom HTML)</span>
                                        <button type="button" class="fd-template-btn" onclick="fdLoadSampleFeatures()">
                                            <i class="fas fa-magic"></i> Load Sample 4-Column Template
                                        </button>
                                    </div>
                                    <p class="fd-panel-subtitle">Place your custom HTML code here (like the 4-column checklist with guarantees, server tech, and specs). Product cards and descriptions remain 100% untouched.</p>

                                    <textarea class="fd-code-area" name="features_html" id="fd_features_html" rows="14"><?php 
                                        $rawVal = $activeConfig ? (string)$activeConfig->features_html : '';
                                        echo htmlspecialchars(featuredesk_decode_html($rawVal), ENT_QUOTES, 'UTF-8'); 
                                    ?></textarea>
                                </div>

                                <!-- 2. Server Backup Notice Box -->
                                <div class="fd-panel">
                                    <div class="fd-panel-title">
                                        <span><i class="fas fa-shield-alt" style="color:#eab308; margin-right:8px;"></i> 2. Server Backup Notice Box (Advisory / Policy)</span>
                                        <button type="button" class="fd-template-btn" onclick="fdLoadSampleNotice()">
                                            <i class="fas fa-magic"></i> Load Sample Backup Notice
                                        </button>
                                    </div>
                                    <p class="fd-panel-subtitle">A dedicated HTML block for server backup advisories, disaster recovery notices, or customer backup recommendations.</p>

                                    <textarea class="fd-code-area" name="backup_notice_html" id="fd_backup_notice_html" rows="7"><?php 
                                        $rawNotice = $activeConfig ? (string)$activeConfig->backup_notice_html : '';
                                        echo htmlspecialchars(featuredesk_decode_html($rawNotice), ENT_QUOTES, 'UTF-8'); 
                                    ?></textarea>
                                </div>

                                <!-- 3. Additional Dynamic Custom HTML Boxes -->
                                <div class="fd-panel">
                                    <div class="fd-panel-title">
                                        <span><i class="fas fa-plus-square" style="color:#6366f1; margin-right:8px;"></i> 3. Additional Custom HTML Boxes</span>
                                        <button type="button" class="fd-template-btn" onclick="fdAddExtraBox()">
                                            <i class="fas fa-plus"></i> Add Custom HTML Box
                                        </button>
                                    </div>
                                    <p class="fd-panel-subtitle">Need more sections? Add as many extra HTML blocks as you like (e.g. FAQs, Payment badges, Datacenter speed tests).</p>

                                    <div id="fd-extra-boxes-container">
                                        <?php
                                        $extraBoxes = [];
                                        if ($activeConfig && !empty($activeConfig->extra_boxes)) {
                                            $decoded = json_decode($activeConfig->extra_boxes, true);
                                            if (is_array($decoded)) {
                                                $extraBoxes = $decoded;
                                            }
                                        }
                                        ?>
                                        <?php foreach ($extraBoxes as $idx => $box): ?>
                                            <div class="fd-extra-box-row" id="fd-box-row-<?php echo $idx; ?>">
                                                <div class="fd-extra-box-header">
                                                    <div style="flex:1; margin-right:15px;">
                                                        <input type="text" class="form-control input-sm" name="extra_box_title[]" value="<?php echo htmlspecialchars(isset($box['title']) ? $box['title'] : ''); ?>" placeholder="Section Title (e.g. Frequently Asked Questions)">
                                                    </div>
                                                    <div style="margin-right:15px;">
                                                        <label style="font-size:12px; font-weight:600; margin-bottom:0; cursor:pointer;">
                                                            <input type="checkbox" name="extra_box_status[<?php echo $idx; ?>]" value="1" <?php echo (!empty($box['status'])) ? 'checked' : ''; ?>> Enabled
                                                        </label>
                                                    </div>
                                                    <button type="button" class="fd-btn-delete-box" onclick="fdRemoveBox('fd-box-row-<?php echo $idx; ?>')">
                                                        <i class="fas fa-trash"></i> Remove
                                                    </button>
                                                </div>
                                                <textarea class="fd-code-area" name="extra_box_html[]" rows="6" placeholder="Write custom HTML content for this section..."><?php 
                                                    $rawExtra = isset($box['html']) ? (string)$box['html'] : '';
                                                    echo htmlspecialchars(featuredesk_decode_html($rawExtra), ENT_QUOTES, 'UTF-8'); 
                                                ?></textarea>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- 4. Category Custom CSS -->
                                <div class="fd-panel">
                                    <div class="fd-panel-title">
                                        <span><i class="fas fa-code" style="color:#64748b; margin-right:8px;"></i> 4. Category Custom CSS (Optional)</span>
                                    </div>
                                    <p class="fd-panel-subtitle">Write custom CSS scoped specifically to this category order form.</p>

                                    <textarea class="fd-code-area" name="custom_css" rows="4"><?php 
                                        $rawCss = $activeConfig ? (string)$activeConfig->custom_css : '';
                                        echo htmlspecialchars(featuredesk_decode_html($rawCss), ENT_QUOTES, 'UTF-8'); 
                                    ?></textarea>
                                </div>

                                <!-- Floating Action Bar -->
                                <div class="fd-save-bar">
                                    <div style="font-size:13px; color:#475569;">
                                        Editing Category: <strong><?php echo htmlspecialchars($activeGroup->name); ?></strong>
                                    </div>
                                    <button type="submit" class="fd-btn-primary">
                                        <i class="fas fa-save"></i> Save Category Changes
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Client-side Template Helpers -->
                <script>
                    var FD_SAMPLE_FEATURES = <?php echo json_encode($samples['features']); ?>;
                    var FD_SAMPLE_NOTICE   = <?php echo json_encode($samples['notice']); ?>;

                    function fdLoadSampleFeatures() {
                        if (confirm("Load sample 4-column Advanced Features template into this box? (Will replace current content)")) {
                            document.getElementById("fd_features_html").value = FD_SAMPLE_FEATURES;
                        }
                    }

                    function fdLoadSampleNotice() {
                        if (confirm("Load sample Server Backup Notice template into this box? (Will replace current content)")) {
                            document.getElementById("fd_backup_notice_html").value = FD_SAMPLE_NOTICE;
                        }
                    }

                    var fdExtraCounter = <?php echo count($extraBoxes) + 100; ?>;
                    function fdAddExtraBox() {
                        fdExtraCounter++;
                        var container = document.getElementById("fd-extra-boxes-container");
                        var rowId = "fd-box-row-" + fdExtraCounter;

                        var html = '<div class="fd-extra-box-row" id="' + rowId + '">' +
                            '<div class="fd-extra-box-header">' +
                                '<div style="flex:1; margin-right:15px;">' +
                                    '<input type="text" class="form-control input-sm" name="extra_box_title[]" value="" placeholder="Section Title (e.g. Additional Specs / FAQ)">' +
                                '</div>' +
                                '<div style="margin-right:15px;">' +
                                    '<label style="font-size:12px; font-weight:600; margin-bottom:0; cursor:pointer;">' +
                                        '<input type="checkbox" name="extra_box_status[' + fdExtraCounter + ']" value="1" checked> Enabled' +
                                    '</label>' +
                                '</div>' +
                                '<button type="button" class="fd-btn-delete-box" onclick="fdRemoveBox(\'' + rowId + '\')">' +
                                    '<i class="fas fa-trash"></i> Remove' +
                                '</button>' +
                            '</div>' +
                            '<textarea class="fd-code-area" name="extra_box_html[]" rows="6" placeholder="Write custom HTML content for this section..."></textarea>' +
                        '</div>';

                        var div = document.createElement("div");
                        div.innerHTML = html;
                        container.appendChild(div.firstElementChild);
                    }

                    function fdRemoveBox(id) {
                        var el = document.getElementById(id);
                        if (el) el.remove();
                    }
                </script>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
