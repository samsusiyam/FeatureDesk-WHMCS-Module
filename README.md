# FeatureDesk - Smart Spec Box & Plan Matrix for WHMCS

<p align="center">
  <img src="logo.png" alt="FeatureDesk Logo" width="128" height="128">
</p>

<p align="center">
  <strong>Declutter tall, messy pricing cards into ultra-clean hero highlights and render an interactive Technical Specifications Comparison Box below your WHMCS order forms.</strong>
</p>

---

## 💡 The Problem FeatureDesk Solves

Traditional hosting order forms force you to list 15–25 technical specifications inside every single pricing card (NVMe storage, RAM, CPU cores, bandwidth, addon domains, subdomains, email limits, MySQL count, LiteSpeed, Imunify360, JetBackup, SSL, PHP switcher, etc.).

This creates:
- ❌ **Unreadable, bloated cards**: Cards stretch vertically over 900px, causing visual fatigue.
- ❌ **Poor user conversion**: Customers cannot quickly compare differences between plans.
- ❌ **Mobile layout breakages**: Endless vertical scrolling on smartphones.

### ✨ The FeatureDesk Solution:
1. **Ultra-Clean Hero Pricing Cards**: Replaces cluttered paragraphs on top cards with **3 to 5 clear highlight badges** (e.g. `1 GB NVMe`, `1.5 GB RAM`, `1 Core Xeon`, `100 GB Bandwidth`).
2. **Interactive Technical Specifications Box**: Automatically compiles and renders a synchronized, categorized comparison matrix **directly below your pricing cards** (matching the exact layout seen in top cloud hosting providers).
3. **Native WHMCS Product Integration**: Edit specs directly inside `WHMCS Admin ➔ Setup ➔ Products/Services ➔ Edit Product` or through the dedicated FeatureDesk Addon UI.

---

## 🚀 Key Features

- **Product Edit Page Integration**:
  - Adds native fields inside WHMCS `Edit Product` (`Card Highlights`, `Promotional Badge`, and `Extended Technical Specs`).
- **Reusable Spec Templates**:
  - Pre-packaged templates for **Shared Hosting**, **Turbo NVMe**, **Cloud VPS**, and **Dedicated Servers**.
- **Interactive "View Tech Specs ↓" Smooth Scroll**:
  - Directs interested buyers smoothly from any pricing card down to the full comparison grid.
- **Categorized Specification Grid**:
  - Groups specs cleanly into:
    - 🖥️ **Server & Hardware Resources**
    - 🌐 **Domains, Email & Database Limits**
    - 🛡️ **Speed, Security & Performance Features**
- **4 Visual Themes**:
  - `Modern Blue` (Bahari Host Standard)
  - `Slate & Navy Dark`
  - `Minimal Clean Light`
  - `Emerald Enterprise Green`
- **Zero Core File Overwrites**:
  - Works 100% via standard WHMCS hooks (`AdminProductConfigFields`, `ClientAreaPageCart`, `ClientAreaHeadOutput`). Upgrading WHMCS will never break your setup.

---

## 📂 Installation

1. Copy or upload the `featuredesk` folder into your WHMCS addon modules directory:
   ```text
   /your_whmcs_root/modules/addons/featuredesk/
   ```
2. Log in to your **WHMCS Admin Area**.
3. Navigate to **System Settings** (or ⚙️ Setup) ➔ **Addon Modules** (`/admin/configaddonmods.php`).
4. Find **FeatureDesk - Smart Spec Box & Plan Matrix** and click **Activate**.
5. Click **Configure**, select the administrator roles that should have access (e.g., Full Administrator), and click **Save Changes**.

---

## ⚙️ How to Use

### 1. From the FeatureDesk Addon Panel:
- Go to **Addons** ➔ **FeatureDesk**.
- Browse all existing products organized by product group.
- Click **Configure Specs** next to any hosting tier.
- Enter 3–5 highlights (one per line) and customize the categorized technical specs matrix.

### 2. Directly When Editing a Product:
- Go to **Setup** ➔ **Products/Services** ➔ **Products/Services**.
- Click **Edit** on any product.
- Scroll down to find the **FeatureDesk Card Promotional Badge**, **Card Highlights**, and **Extended Tech Specs** fields.
- Enter your values and click **Save Changes** — it automatically syncs with the comparison table!

---

## 🎨 Manual Custom Template Integration (Optional)

FeatureDesk automatically injects into default WHMCS cart templates (`standard_cart`, `premium_comparison`, etc.). 

If you are using a completely custom order form or external PHP template, you can manually place the Smarty matrix variable anywhere on your page:

```smarty
{$featuredesk_matrix}
```

To loop through clean highlights on individual product cards:
```smarty
{if $product.featuredesk_highlights}
  <ul class="fd-card-bullets">
    {foreach from=$product.featuredesk_highlights item=highlight}
      <li><i class="fas fa-check-circle fd-icon-check"></i> {$highlight}</li>
    {/foreach}
  </ul>
{/if}
```

---

## 👨‍💻 Developer & Support

- **Author**: Bahari IT
- **Lead Developer**: MD Samsuzzaman Siyam
- **Website**: [bahari-it.com](https://bahari-it.com)
- **Support**: [support.baharihost.com](https://support.baharihost.com)
- **License**: Proprietary / MIT (for Bahari IT clients and partners)
