# FeatureDesk v2.0 - Category Feature Spec Boxes & Server Notices for WHMCS

<p align="center">
  <img src="logo.png" alt="FeatureDesk Logo" width="128" height="128">
</p>

<p align="center">
  <strong>Category-based Responsive Feature Matrices, 4-Column Advanced Checklists, Server Backup Advisory Notices, and Custom Dynamic HTML Blocks rendered directly below WHMCS pricing cards.</strong>
</p>

---

## 💡 What's New in FeatureDesk v2.0

Unlike old-fashioned rigid comparison tables, **FeatureDesk v2.0** provides complete creative freedom with an ultra-clean, category-centric architecture:

1. **Category-Based Feature Boxes**:
   - Assign dedicated, custom HTML feature boxes per Product Group / Category (e.g. Shared Hosting, Turbo NVMe, Reseller Hosting).
   - Display modern 4-column checklists (e.g. Guarantees, Server Specs, Developer Tooling, Resource Limits) matching top cloud hosting brands.
2. **Dedicated Server Backup Notice Box**:
   - High-visibility, beautifully styled server backup policy and advisory notice cards.
   - Built-in warning and information alert themes with shield/cloud icons.
3. **Product Descriptions Remain 100% Untouched**:
   - Strict rule: **Never modifies, strips, or replaces native WHMCS product descriptions or pricing cards**. Everything renders neatly below the product packages.
4. **Dynamic Extra HTML Blocks**:
   - Add as many additional custom HTML sections as needed (e.g., FAQ accordions, Payment methods, Promo banners, Datacenter speed tests).
5. **Mobile-First Responsive Layout**:
   - Automatically shifts from 4 columns (desktop) to 2 columns (tablet) and 1 column (smartphones) with zero horizontal scroll.
6. **Pre-Loaded 1-Click Starter Templates**:
   - Ready-to-use HTML templates for 4-Column Advanced Features and Server Backup Notices built right into the admin UI.

---

## 🚀 Key Highlights

- **Product Group (Category) Focused**: Manage content per category from one unified dashboard.
- **Full Custom HTML & Code Flexibility**: Write your own HTML, SVG icons, or load the built-in starter templates with one click.
- **Server Backup Notice**: Dedicated field with pre-styled alert classes (`.fd-notice-card`).
- **Expandable Dynamic Sections**: Add unlimited extra HTML blocks per category.
- **Universal Order Form Compatibility**: Seamlessly works with `standard_cart`, `supreme`, `modern`, `universal`, `boxes`, `twenty-one`, and custom themes.
- **Zero Core File Modifications**: Pure WHMCS Addon + Hook architecture.

---

## 📂 Installation

1. Upload the `featuredesk` folder into your WHMCS addon modules directory:
   ```text
   /your_whmcs_root/modules/addons/featuredesk/
   ```
2. Log in to your **WHMCS Admin Area**.
3. Go to **Setup** (or System Settings) ➔ **Addon Modules** (`/admin/configaddonmods.php`).
4. Find **FeatureDesk** and click **Activate**.
5. Click **Configure**, select the administrator roles that should have access (e.g. *Full Administrator*), and click **Save Changes**.

---

## ⚙️ How to Use

1. Go to **Addons** ➔ **FeatureDesk** in your WHMCS admin.
2. Under the **Categories & Features** tab, select any WHMCS Product Group (Category).
3. **Main Features Box**: Enter your custom HTML or click **"Load Sample 4-Column Template"** to immediately insert the modern 4-column checklist.
4. **Server Backup Notice**: Enter your backup policy advisory or click **"Load Sample Notice"**.
5. **Extra HTML Blocks**: Click **"+ Add Custom HTML Box"** if you want to add FAQ, payment badges, or extra promotions.
6. Click **Save Changes** — your order form at `cart.php?gid=X` will immediately display the new responsive sections right below the pricing cards!

---

## 👨‍💻 Developer & Support

- **Author**: Bahari IT
- **Lead Developer**: MD Samsuzzaman Siyam
- **Website**: [bahari-it.com](https://bahari-it.com)
- **Support**: [support.baharihost.com](https://support.baharihost.com)
- **License**: Proprietary / MIT