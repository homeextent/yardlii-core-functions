Markdown

\# 🧩 YARDLII Core Functions

\[\!\[WordPress Tested\](https://img.shields.io/badge/tested\_up\_to-6.8-blue.svg)\](https://wordpress.org/plugins/)  
\[\!\[PHP Version\](https://img.shields.io/badge/php-8.0%2B-blue.svg)\](https://www.php.net/)  
\[\!\[License\](https://img.shields.io/badge/license-GPLv2%2B-green.svg)\](https://www.gnu.org/licenses/gpl-2.0.html)  
\[\!\[YARDLII\](https://img.shields.io/badge/YARDLII-Core%20Platform-blueviolet.svg)\](https://yardlii.com)

\> The **\*\*YARDLII Core Functions\*\*** plugin provides the modular engine for advanced real estate listing systems, admin automation, and dynamic feature control — built with ACF, FacetWP, and WPUF integrations.

\---

\#\# 🧠 Description

**\*\*YARDLII Core Functions\*\*** is the backbone of the YARDLII platform — a modular, expandable framework designed for enterprise-grade WordPress automation.

It powers all major YARDLII components through a clean, extensible architecture with centralized controls, admin branding, and modular settings management.

\#\#\# 🔧 Core Features

\- 🗺️ **\*\*Google Maps API\*\*** key management \+ diagnostics  
\- 🖼️ **\*\*Automated Featured Image\*\*** assignment (from WPUF or ACF gallery)  
\- 🔍 **\*\*Homepage Search Form\*\*** with FacetWP binding  
\- 👤 **\*\*ACF → User field synchronization\*\*** (toggleable)  
\- 🧩 **\*\*Feature Flag system\*\*** — enable/disable modules individually  
\- 🛡️ **\*\*Role Control System\*\***  
    \- **\*\*Submit Access Control:\*\*** Front-end page gating by role  
    \- **\*\*Custom User Roles:\*\*** Define roles by cloning base caps \+ adding extra caps  
    \- **\*\*Badge Assignment:\*\*** Map roles → ACF Options image fields; store image ID in user meta  
\- 🧭 **\*\*Scoped admin tabs\*\*** (Main / Sub / Inner)  
\- ⚙️ **\*\*Built-in debug\*\***, logging, and diagnostics  
\- 🎨 **\*\*YARDLII-branded admin interface\*\*** with consistent UX styling

\---

\#\# 🛡️ Role Control

**\*\*Location:\*\*** \`Settings → YARDLII Core → Role Control\`

The Role Control area contains independent features gated by the Role Control master flag (\`Advanced → Feature Flags\`). In **\*\*“Show-but-Locked”\*\*** mode, the tab remains visible, but inputs and handlers are disabled when the master flag is **\*\*OFF\*\***.

\#\#\# 1\. Submit Access Control  
Limit access to a front-end page by role.

\* **\*\*Behavior:\*\***  
    \* If no roles are selected, any logged-in user may access the target page.  
    \* Otherwise, only users with a selected role may access; others see a message or are redirected to the login page.

\#\#\# 2\. Custom User Roles  
Create, update, and remove custom roles.

\* **\*\*Behavior:\*\***  
    \* On save, roles are created (\`add\_role\`) or updated (caps diffed, display name updated).  
    \* Removing a row and saving removes the WP role.  
    \* Users with only that role are reassigned to Subscriber.  
    \* Reserved roles (administrator, etc.) cannot be created or removed.

\#\#\# 3\. Badge Assignment  
Map roles to ACF Options image fields and sync to user meta.

\* **\*\*Settings:\*\***  
    \* Managed in an isolated settings group (\`yardlii\_role\_control\_badges\_group\`).  
    \* Includes "Resync All Users" action (uses Action Scheduler for background processing to prevent timeouts).  
\* **\*\*Behavior:\*\***  
    \* Syncs on role change, profile update, login, and add/remove role.  
    \* Picks the first mapped role from the user’s roles; otherwise uses fallback (if set).  
    \* Reads the ACF Options field and saves the attachment ID to the configured user meta key.

\---

\#\# 🚩 Feature Flags (Advanced)

**\*\*Location:\*\*** \`Settings → YARDLII Core → Advanced → Feature Flags\`

\* \`yardlii\_enable\_role\_control\`: Master switch for Role Control.  
    \* **\*\*OFF\*\***: Role Control tab stays visible but is read-only; no runtime effects.  
    \* **\*\*ON\*\***: Role Control fully interactive; runtime effects gated by each feature’s toggle.  
\* \`yardlii\_enable\_acf\_user\_sync\`: ACF User Sync toggle.

**\*\*Constant Overrides (Optional):\*\***  
You can hard-lock features via \`wp-config.php\`:  
\`\`\`php  
define('YARDLII\_ENABLE\_ROLE\_CONTROL', true); // hard-enable Role Control  
define('YARDLII\_ENABLE\_ACF\_USER\_SYNC', false); // hard-disable ACF user sync

---

## **🧩 Installation**

1. Upload the yardlii-core-functions folder to /wp-content/plugins/  
2. Activate the plugin through the **Plugins** menu in WordPress  
3. Go to **Settings → YARDLII Core** to configure your modules

---

## **💡 Frequently Asked Questions**

### **❓ Can I add my own modules?**

Yes. Add a feature class under /includes/Features/ and wire it in the Loader. Add a settings partial under /includes/Admin/views/partials/. If your feature shares a screen, consider using an isolated settings group.

### **❓ How do I add a new top-level tab with subtabs?**

Add a main button to the nav (\<button class="yardlii-tab" data-tab="my-feature"\>...) and a panel (\<section ... data-panel="my-feature"\>...). Inside, add subtabs and \<details\> sections with a new scope (e.g., data-msection). Hook the logic in assets/js/admin.js.

### **❓ Can I disable Badge Assignment or ACF User Sync?**

Yes. Toggle them off in **Advanced → Feature Flags**, or hard-disable via constants in wp-config.php.

### **❓ What happens if I disable Role Control (master flag OFF)?**

The Role Control tab remains visible but is locked (read-only). No Submit Access gating, Custom Role syncing, or Badge syncing occurs until you turn it back ON.

---

## **💻 Developer Notes**

* Feature registration (Loader):  
  Guard features by master AND per-feature toggle:  
  PHP  
  if ($rc\_master && (bool) get\_option('yardlii\_enable\_role\_control\_submit', false)) { ... }

* Settings registration:  
  Use register\_setting() with sanitize callbacks. When two forms live on the same page, use separate settings groups to avoid collisions.  
* JS consolidation:  
  All tab handlers live in assets/js/admin.js. Do not reintroduce tabs.js — it caused duplicate bindings and state conflicts.

---

## 📦 Changelog

## [3.23.0] - 2025-11-28
### Added
- **Universal Location Engine:** New global system for Google Places Autocomplete.
- **Global Router:** Implemented `google-maps-router.js` to manage API dependencies and prevent race conditions between Directory, FacetWP, and Elementor.
- **Smart Autocomplete:** Any text input with the CSS class `yardlii-city-autocomplete` will now automatically attach a "Cities Only" dropdown.
- **User Directory Polish:** Directory search bar now auto-detects location inputs and applies autocomplete logic without manual configuration.

## [3.22.1] - 2025-11-28
### Added
- **Elementor Utility:** Added `ElementorQueryMods` class to handle custom query modifications.
- **Author Archive Fix:** Implemented `yardlii_author_listings` Query ID. Using this ID in an Elementor Loop Grid forces it to display listings belonging to the currently viewed author profile.
- **Documentation:** Added usage instructions for Elementor Query IDs to the User Directory settings tab.

## [3.22.0] - 2025-11-28
### Added
- **User Directory System (Final):** Complete overhaul of the directory module.
- **Google Places Autocomplete:** Location input now suggests valid cities to prevent typos (Requires Google Maps Key).
- **Search Reset:** Added a "Clear" button that appears when filters are active to reset the grid.
- **Decoupled Architecture:** Search bar and Grid can now be placed in different page sections (e.g., Hero vs Body).
- **Admin UI Builder:** New "User Directory" settings tab with a smart shortcode generator and role-based field mapping.

## [3.21.0] - 2025-11-28
### Added
- **Dual-Filter Search:** Directory now supports filtering by "Trade" (Dropdown) AND "Location" (Text) simultaneously.
- **Dynamic Trade Source:** New Global Setting allows admins to define which ACF Field populates the Trade dropdown automatically.
- **UI Polish:** Fixed search icon overlap issues and moved helper text into the input placeholder.

## [3.20.0] - 2025-11-28
### Added
- **Dynamic User Directory:** New module to display user grids with instant search.
- **Role-Based Configuration:** New "User Directory" settings tab allows mapping specific ACF fields or Meta keys per user role.
- **Shortcode:** Added `[yardlii_directory]` with `role` and `limit` attributes.
- **Visuals:** Role-specific CSS classes added to directory grids for custom styling.

### 3.19.0 - 2025-11-26
* **UI Overhaul:** Completely redesigned the Verification Requests admin screen to match native WordPress standards while retaining custom Yardlii functionality.
* **Feature:** "Search Requests" toolbar. Replaces the default WordPress search with a dedicated engine that supports searching by **User Email**, **User Login**, **Display Name**, and **Request ID**.
* **UX:** Moved the "Send me a copy" toggle to the filter bar for better visibility.
* **Logic:** Fixed persistent search filtering issues. Clicking "All", "Pending", etc., now correctly resets the active search query.
* **Dev:** Implemented strict typing (PHPStan Level 6) across all Admin Column classes.
* **Style:** Applied Yardlii branding (Action Orange/Trust Blue) to search buttons and status badges.

### 3.18.4 - 2025-11-25
* **Fix:** Resolved "Spinning Wheel" crash in SmartFormOverrides by handling WPUF AJAX array responses correctly.
* **Fix:** Added strict 1-post limit check to SmartFormOverrides to prevent duplicate "Pending" listings.
* **Fix:** Updated Auto-Publisher to support the plural `listings` Custom Post Type slug.
* **Feature:** Auto-Publisher now triggers immediately when a user role switches to "Pending Verification", ensuring instant draft publication.

### 3.18.0 - 2025-11-25
* **Feature:** "Smart Form Overrides" module added. Forces 'Publish' status and instant redirect for Pending Verification users on the Basic Form.
* **Update:** Dynamic Posting Logic now exclusively targets Verified users (swapping them to Pro Form) using "God Mode" metadata interception.
* **Update:** Auto-Publisher now targets the configured "Basic Member Form" to release drafts upon user verification.
* **Admin:** Added "Basic Member Form ID" setting to WPUF Customisations. Deprecated "Provisional Form ID".

### 3.17.1 - 2025-11-25
* **Fix:** Updated PostingLogic to use metadata interception ("God Mode") to prevent WPUF permission redirects.

### 3.17.0 - 2025-11-25
* **Feature:** Added Dynamic Posting Logic module to automatically swap WPUF forms based on user role (e.g., swapping a "Basic" form for a "Pro" form upon editing).
* **UI:** Added configuration fields for "Verified/Pro Form ID" and "Provisional Form ID" under **Settings -> YARDLII Core -> General -> WPUF Customisations**.
* **Fix:** Resolved PHPStan strict type errors in role checking logic.

= 3.16.2 =
* **Fix:** Resolved a critical conflict between FacetWP and Google Maps "Advanced Markers" (gmp-pin error) by forcing the 'weekly' API channel.
* **Fix:** Restored map pin visibility by enabling compatibility with legacy map markers.

= 3.16.1 = 
Added
Diagnostics UI: Added a dedicated "Media Cleanup" section in Settings → Advanced → Diagnostics. This verifies if the "Janitor" Cron job (yardlii_daily_media_cleanup) is correctly scheduled and active.

Integration Tests: Added automated testing (tests/integration/Features/MediaCleanupTest.php) to the CI pipeline. This simulates the creation and deletion of listings to guarantee that images (including PixRefiner variants) are physically removed from the disk.


= 3.16.0 = 
### Added
- **Automated Media Cleanup:** New module to prevent server bloat.
    - **Listing Deletion:** Automatically deletes all attached images when a listing is permanently deleted.
    - **Smart "Edit" Logic:** Detects when an image is removed from a WPUF gallery during an edit and deletes the orphaned file.
    - **Ghost File Janitor:** Daily Cron job to sweep up abandoned uploads (files >24h old with no parent listing).
    - **PixRefiner Compatibility:** Explicitly calculates and removes unregistered optimization variants (e.g., `-400.webp`, `-768.webp`) and `.orig` backups.

= 3.15.0 =

NEW: Added "Request Expiration Days" setting to Trust & Verification global configuration. Admins can now customize the expiration window (default: 5 days).

ENHANCEMENT: Synchronized the "Employer Vouch" link validity with the global expiration setting. Links now remain valid for the same duration as the request lifespan.

DEV: Refactored EmployerVouchService to remove hardcoded constants and utilize the new dynamic setting.

= 3.14.0 =
* **NEW:** Automated Expiration Service. Pending verification requests older than 5 days are now automatically rejected to prevent stalled accounts.
* **NEW:** Cleanup Protocol. When a user is rejected (manually or via expiration), their pending/draft listings are automatically moved to Trash.
* **LOGIC:** Added `listings_trashed` and `auto_expired` events to the verification history log.

= 3.13.0 =
* **NEW:** Auto-Publisher module for Trust & Verification. Automatically publishes any 'pending' listings owned by a user immediately after they are approved/verified.
* **NEW:** Added `auto_publish` event to the Verification Request history log for audit trails.

= 3.12.0 =
* **NEW:** Added "Privacy Geocoding Engine" for WPUF forms. Automatically converts submitted Postal Codes into latitude/longitude (for search) and "City, Province" (for display) to protect user privacy.
* **NEW:** Added "Backend Geocoding Key" setting in Google Map options. Allows separating Frontend keys (HTTP restrictions) from Backend keys (IP restrictions) to prevent API errors.
* **NEW:** Added "Geocoding Diagnostics" panel. Includes a live API tester, quota reminders, and a "FacetWP Index Inspector" to verify database integrity.
* **ENHANCEMENT:** Updated WPUF Customisations tab with a new configuration UI for mapping Form IDs to Postal Code meta keys.
* **DEV:** Implemented strict typing (PHPStan level) across all new Geocoding classes.
* **FIX:** Resolved conflict between Google Maps "Advanced Markers" and FacetWP by documenting the need for "Legacy" marker settings.

### 3.11.1
* **Fix:** Manually booted Action Scheduler library to resolve autoloader conflicts preventing "Resync All Users" from running.
* **Chore:** Removed temporary verbose debugging checks from the Diagnostics panel.

### 3.11.0
* **Feat:** Enabled selecting multiple forms for "Featured Image Automation" (previously only one).
* **Feat:** Added a comprehensive "Diagnostics" panel to the "Advanced" tab for plugin health checks.
* **Dev:** The new diagnostics panel includes:
    * An Environment & Dependencies check (ACF, WPUF, FacetWP, etc.).
    * An effective Feature Flag Status table (shows DB option vs. constant overrides).
    * Role Control & Badges diagnostics (ACF Options check, Action Scheduler queue status, and an AJAX sync test button).
    * Homepage Search diagnostics (FacetWP check and a transient cache clearing tool).
* **Fix:** Added Action Scheduler stubs to the PHPStan bootstrap file to resolve CI analysis errors.

### 3.10.1
* **Fix**: Resolved styling conflicts for the Featured Badge by properly enqueuing a dedicated `frontend.css` file.
* **Refactor**: Removed inline CSS from the `[yardlii_featured_badge]` shortcode to allow global styling control.
* **UI**: Validated badge colors against Brand Identity Guidelines (Action Orange)..

### **3.10.0**
* **Feat**: Added **Featured Listings Logic**.
    * Synchronizes `is_featured_item` (ACF/Meta) with native WordPress Sticky status.
    * Adds "Show Featured Only" filter to the Admin Listings table.
    * Adds "Sticky" label visual indicator to the Admin Listings table.
    * Adds `[yardlii_featured_badge]` shortcode for frontend display.
    * Adds `featured_listings` Query ID for Elementor Loop Grids.
* **UI**: Reorganized the **WPUF Customisations** panel into clear "Frontend Styling" and "Listing Logic" sections.
* **Fix**: Resolved recursion memory leak in Featured Listings admin filter logic.

### **3.9.5**
* **Fix**: Resolved layout issues in Multistep forms where buttons overlapped content. Added proper footer spacing and mobile stacking for navigation buttons.

### **3.9.3**
* **Fix**: Resolved CSS specificity issues for "Card-Style Layout" on Single-Step forms.
* **UI**: Improved card contrast and padding to ensure visibility on white backgrounds and fix header alignment.

### **3.9.2**
* **Fix**: Resolved an issue where "Card-Style Layout" was not applying to Single-Step WPUF forms due to DOM nesting structure.

### **3.9.1**
* **Fix**: Updated CSS selectors for "Card Layout" and "Modern Uploader" to support WPUF Pro's Multistep forms and dynamic ID structures.

### **3.9.0**
* **Feat**: Introduced "Card-Style Layout" for WPUF forms. Fields are visually grouped into modern cards based on "Section Break" fields.
* **Feat**: Added "Modern Uploader" skin. Transforms standard upload buttons into a professional "Dropzone" style with drag-and-drop visual cues.
* **Refactor**: Renamed `WPUFEnhancedDropdown` to `WPUFFrontendEnhancements` to serve as a central controller for all WPUF visual improvements.
* **UI**: Added toggles for Card Layout and Modern Uploader to the WPUF Customisations panel.

### **3.8.2**
* **Feat**: Added configuration field to "WPUF Customisations" to target specific pages (by slug or ID) for the Enhanced Dropdown, replacing the hardcoded check.
* **UI**: Integrated the new target page setting directly into the WPUF toggle row for better UX.

### **3.8.1**
* **Security**: Implemented "Kill Switch" in WPUF provider to strictly prevent users from self-verifying using their own email address.
* **Feat**: Added a "Revocation/Lifecycle" footer to Employer Vouch emails, allowing employers to request access removal via a pre-filled support email.
* **Fix**: Resolved PHPStan array type errors in Trust & Verification providers.

### 3.8.0
* **Feat**: Introduced "Employer Vouch" workflow: Applicants can now verify via an external employer email field in WPUF forms.
* **Feat**: Added secure public verification portal for employers to Approve/Reject requests without logging in (token-based, 5-day expiry).
* **UI**: Enhanced Requests table with a dedicated "Employer Vouch" filter (with counts) and specific status icons.
* **UI**: Updated "Processed By" column to indicate when a request was auto-approved by an Employer/System.
* **Logic**: Suppressed standard Admin notification emails when the Employer Vouch flow is triggered to reduce inbox clutter.
* **Dev**: Added `EmployerVouchService` and `EmployerVerificationHandler` classes.

### **3.7.7**

* **Fix**: Resolved WYSIWYG editor loading issues in Trust & Verification configuration (added missing dependencies and classes).  
* **Fix**: Corrected admin tab navigation to properly respect URL parameters and deep linking (e.g., from email notifications).  
* **Fix**: Fixed "stuck URL" issues where saving settings would redirect to the wrong tab.  
* **Fix**: Resolved JavaScript reference error preventing main tabs from switching.  
* **Feat**: Improved Trust & Verification admin notification emails with clearer subject lines and user details.

### **3.7.6**

* **Fix**: Corrected TvProviderRegistry to check for the correct interface (ProviderInterface), fixing a bug where WPUF and Elementor providers were not loading.  
* **Fix**: Patched a Fatal Error in the ElementorPro provider by replacing a non-existent function call (yardlii\_debug\_log).  
* **Fix**: Removed the temporary WPUF workaround (yardlii\_tv\_enable\_legacy\_wpuf\_hooks filter).

### **3.7.5**

* **Refactor**: Modernized trust-verification.js into ES6 classes for maintainability.  
* **Chore**: Renamed trust-verification.js to admin-tv.js and updated enqueue.

### **3.7.4**

* **Docs**: Added new EXTENSIBILITY.md file to document hooks, filters, and providers.  
* **Perf**: Optimized ListTable.php to fix N+1 query problem and improve admin performance.

### **3.7.3**

* **Chore**: Removed 'ACF User Sync' feature.  
* **Note**: The feature is now considered deprecated.

### **3.7.2**

* **Fix**: Corrected JavaScript typo that prevented 'Advanced' tab sections from opening.

### **3.7.1**

* **Feat**: Restructured 'Advanced' tab with sub-tab navigation (Flags & Diagnostics).  
* **Feat**: Created new 'Diagnostics' panel and migrated Trust & Verification diagnostics to it.  
* **Refactor**: Removed diagnostics panel from the 'Trust & Verification' tab.

### **3.7.0**

* **Feat**: Refactored Trust & Verification decision logic into a dedicated TvDecisionService.  
* **Feat**: Added yardlii\_tv\_decision\_made action hook to allow other code to react after a verification decision is applied.  
* **Refactor**: Created a TvProviderRegistry to cleanly manage and load form providers (e.g., WPUF, Elementor).

### **3.6.0 \- 2025-11-07**

* **Feature**: Refactored the "Resync All Users" badge assignment to use Action Scheduler for reliable background processing.  
* **Dev**: Added woocommerce/action-scheduler as a Composer dependency.

### **3.5.0 \- 2025-11-06**

* **New**: Added an uninstall.php routine to remove all plugin data on deletion (CPTs, options, meta).  
* **New**: Added a "Remove all data on deletion" safety toggle in the Advanced tab to control the uninstall routine.  
* **Test**: Added integration smoke test for the feature Loader to ensure flags are respected.  
* **Fix**: Corrected logic bug in Caps::userCanManage method.  
* **Dev**: Completed full unit test coverage for Mailer, Caps, and Templates.  
* **Dev**: Integrated PHPStan (Level 6\) into the CI pipeline for static analysis.

### **3.4.0 — 2025-11-04**

* **CI**: PHPUnit \+ PHPStan gates in place (green baseline).  
* **Tests**: Mailer/Placeholders/Caps \+ AJAX happy path.  
* **New**: Diagnostics Panel (MVP) with “Send self-test email”.  
* **Security/UX**: Strict nonces/caps on AJAX \+ clearer notices.

### **3.2.0**

* **New**: Role Control → Badge Assignment (role → ACF field mapping; stores image ID in user meta; sync on role/profile/login).  
* **New**: Badge panel repeater UI (role dropdown \+ field name; add/remove rows).  
* **New**: Resync All Users action (admin-post; chunked).  
* **New**: Profile badge preview on user profile screens.  
* **Change**: Badge settings moved to an isolated settings group to prevent form collisions.  
* **Change**: Consolidated admin tab logic in admin.js.

### **3.1.0**

* Introduced full Feature Flags System (UI \+ constant override).  
* Added Advanced tab controls for per-feature toggling.  
* Rebuilt JavaScript tab architecture with scoped handlers.  
* Added General tab subtabs (Google Map, Featured Image, Homepage Search, WPUF).  
* Restored inner horizontal tabs for Google Map Settings.

### **3.0.0**

* Introduced modular admin settings tabs.  
* Added AJAX test \+ reset tools for Featured Image Automation.  
* Improved Homepage Search with FacetWP binding.  
* Added YARDLII-branded UI styling.

## **🔮 Future Roadmap (Lifecycle Verification)**

* **Architectural Separation (Planned)**:
  * **Goal:** Decouple the "Trust & Verification" module into a standalone plugin (`yardlii-trust-verification`).
  * **Reason:** As the logic for verification, employer vouching, and email automation grows, it warrants its own lifecycle separate from the Core utility functions (Maps, Search, etc.).

* **Automated Re-verification**: 
  * Integration with Action Scheduler to query employers every 6 months to confirm the employee is still active.
  * Logic to automatically demote users if the employer clicks "No" during re-verification.
* **Employer Opt-Out**:
  * Mechanism to store suppressed/blocked emails.
  * "Unsubscribe" link in vouch emails to prevent future requests to that employer.
* **Permanent Revocation UI**:
  * A permanent interface for employers to revoke a previous approval without contacting support.