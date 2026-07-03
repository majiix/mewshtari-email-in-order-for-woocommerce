# Changelog

All notable changes to this project will be documented in this file.

## Step 1 to 6: Complete Plugin Implementation
1. Bootstrapped the plugin under `mewshtari-email-in-order-for-woocommerce.php`.
2. Created dynamic HTML email templates settings page under WooCommerce menu using Vanilla JavaScript.
3. Designed premium layout styling override for settings page components.
4. Integrated "Custom Email Content" metabox into the WooCommerce order screen with full HPOS compatibility.
5. Implemented secure AJAX handler for custom email dispatching, status update automation, and internal order logs.
6. Enforced core email injection hooks to prepend template content in WooCommerce transactional emails.

Commit message: `feat(core): implement complete mewshtari email in order plugin`

## Fix 1: Order Status Mapping
1. Refactored the AJAX handler to use strict integer parsing (`intval`) for the template index selection to prevent issues with falsy/empty key checks.
2. Wrapped the mailer From name in double quotes in headers to prevent email parser failures on store names containing spaces.
3. Explicitly defined the AJAX `Content-Type` header as `application/x-www-form-urlencoded; charset=UTF-8` and sent the body stringified.
4. Ensured that if the order is already in the target mapped status, an internal order note is still added.

Commit message: `fix(ajax): resolve order status mapping and mailer headers issue`

## Fix 2: Settings Order Status Mapping Dropdown Empty
1. Removed unnecessary escaping backslashes from JavaScript template string expressions (`${index}` and `${optionsHtml}`) inside the settings page's Vanilla JS script block. This allows the template literals to interpolate correctly in the browser and populate the select element options.

Commit message: `fix(settings): resolve empty order status mapping dropdown in template repeater`

## Step 7: AJAX-ified Settings Panel
1. Registered `wp_ajax_mewshtari_save_settings` action hook in constructor.
2. Updated the admin settings page form to intercept the submit event, serialize all template inputs using `FormData` and `URLSearchParams`, and post them via AJAX to `admin-ajax.php`.
3. Created `ajax_save_settings()` method to verify settings nonces, check `manage_woocommerce` capabilities, sanitize template inputs, save options using `update_option`, and return JSON responses.
4. Outputted status indicators on the settings screen showing saving states (saving, success, error) with zero page reloads.

Commit message: `feat(settings): make template repeater settings saving AJAX-ified`

## Step 8: Sticky Sidebar layout for Placeholders
1. Refactored settings page layout from a single column to a 2-column grid layout containing a main column and a sidebar.
2. Moved the shortcode placeholder info box to the sidebar.
3. Applied `position: sticky; top: 32px;` to the sidebar, keeping the placeholder instructions on-screen when scrolling through long lists of templates.
4. Styled shortcode placeholders as monospaced badges inside a card widget.

Commit message: `feat(settings): move shortcode placeholders into a sticky sidebar`

## Step 9: Confirmed Deletion with AJAX Auto-save
1. Refactored the card "Delete" button click handler to show a custom inline confirmation overlay (popover overlay) with glassmorphic blur styling.
2. If confirmed, the card is removed from the DOM, indices are updated, and settings are saved automatically via the AJAX settings save pipeline.
3. If cancelled, the overlay closes and the template remains intact.

Commit message: `feat(settings): add confirmed deletion popover with AJAX auto-save`

## Fix 3: Delete Confirmation Button Class Collision
1. Added a check `!target.classList.contains('mewshtari-delete-confirm')` to the main template deletion trigger logic in JavaScript. This prevents the "Yes, Delete" confirmation button (which shares the general `mewshtari-btn-danger` styling class) from triggering the overlay creation code again instead of executing the actual deletion.

Commit message: `fix(settings): resolve delete confirmation class collision`

## Step 10: Readme and Uninstall Files
1. Created `readme.txt` using standard WordPress.org plugin format.
2. Created `uninstall.php` to securely delete the `mewshtari_email_templates` option from the database when the plugin is deleted.

Commit message: `feat(core): add readme and uninstall files`

## Step 11: Compliance Audit and License Header Compliance
1. Audited the plugin structure and code against `wp-best-practices`, `wp-plugin-directory-guidelines`, and `wp-plugin-development` skills.
2. Updated the main plugin file header to use standard `License: GPL-2.0-or-later` and included the required `License URI:` header for official WordPress.org repository compliance.

Commit message: `style(core): update headers for wordpress guidelines compliance`

## Step 12: Frontend Design and Delight Interactivity
1. Applied card elevation transformations (`translateY`) on hover to make card panels feel alive.
2. Added click active scale modifications (`scale(0.97)`) for buttons to create tactile physical feedback.
3. Created an interactive, CSS-animated save status indicator featuring glowing status dots that pulse or change color to represent save states (saving, success, error) dynamically with no page reloads.

Commit message: `feat(settings): improve visual feedback and interactive delight`

## Fix 4: WordPress Security Compliance
1. Applied late escaping on template name and HTML outputs using `esc_attr` and `esc_textarea` respectively.
2. Added unslashing (`wp_unslash()`) and PHPCS validation suppression to the raw `$_POST` settings input data inside the AJAX handler.
3. Added PHPCS nonce validation suppression to the rendering order ID retrieval `$_GET` data inside the metabox.
4. Wrapped plain-text email output in `esc_html()` to prevent security warnings.

Commit message: `fix(security): resolve WordPress security compliance warnings and escaping`

## Step 13: Template-Level Subject Line Support
1. Added `subject` field to template repeaters inside the settings panel.
2. Configured Settings API template array sanitization to register, unslash, and sanitize the subject field.
3. Refactored the order edit screen metabox JavaScript event listener to auto-populate the subject field with the selected template's subject, supporting placeholder replacements.

Commit message: `feat(repeater): add template subject field and dynamic auto-population`

## Step 14: Rebranded Order Metabox Title
1. Changed the metabox title from `"Custom Email Content"` to `"Mewshtari Email in Order"` in [register_meta_boxes()](file:///e:/wps/dorsanet/app/public/wp-content/plugins/mewshtari-email-in-order-for-woocommerce/mewshtari-email-in-order-for-woocommerce.php#L694-L712) to match the plugin's name.

Commit message: `style(metabox): rebrand order metabox title to match plugin name`

## Fix 5: Premature Closure of Admin Wrapper Elements
1. Removed a redundant extra closing `</div>` tag at the end of the [render_settings_page()](file:///e:/wps/dorsanet/app/public/wp-content/plugins/mewshtari-email-in-order-for-woocommerce/mewshtari-email-in-order-for-woocommerce.php#L120-L193) HTML container, resolving the issue where the WordPress footer was pushed up into the middle of the settings page layout.

Commit message: `fix(settings): resolve premature admin container closure displacing footer`

## Step 15: Metabox UI/UX Redesign and Interactivity
1. Rewrote the order edit screen metabox layout to implement a modern flex/grid styling with custom input border focuses and transitions.
2. Built a click-to-copy placeholder badges bar with temporary toast notification feedback.
3. Added custom CSS `@keyframes` animations for button loaders, pulsing sending status cards, drawing SVG checkmarks, and shaking error containers.

Commit message: `feat(metabox): redesign layout with copy badges and CSS animations`

## Step 16: Countdown Delay and Undo Cancellation
1. Implemented a 10-second countdown delay prior to AJAX email dispatch in the order edit screen metabox.
2. Added `.cancel-state` CSS animations and styles to transform the primary button into a red/rose warning button during the countdown.
3. Created `setFormDisabled()` JavaScript helper to freeze all form controls (select, subject input, TinyMCE editor) during the countdown and transmission stages, re-enabling them upon cancellation or error.

Commit message: `feat(metabox): add 10-second countdown delay with cancel action`

## Step 17: Visual HTML Editors in Settings Panel
1. Enqueued editor scripts on settings page load via `wp_enqueue_editor()`.
2. Replaced the simple settings HTML Content textarea with the WordPress rich editor using `wp_editor()` in PHP.
3. Implemented dynamic TinyMCE editor creation/destruction inside settings JS repeater logic.
4. Integrated card reordering safety checks (`swapCards()`) that temporarily detach TinyMCE states to prevent visual editor freezing when elements are shifted in the DOM.
5. Synced all editors back to form inputs using `tinymce.triggerSave()` prior to AJAX submission.

Commit message: `feat(settings): integrate wp_editor rich visual HTML editors in repeater`

## Step 18: WP Best Practices Audit & Release Version Bump
1. Audited plugin code against `wp-best-practices` rules (prefixing, translations, caching, PHP compatibility).
2. Confirmed translations match plugin text domain and correct placeholder comments (`// translators:`) are maintained.
3. Bumped plugin release version and stable tag from `1.0.0` to `1.1.0` in the main plugin file header and `readme.txt`.

Commit message: `chore(release): bump plugin version to 1.1.0 and audit best practices`

## Step 19: Externalize CSS and JS Asset Files
1. Extracted all settings and metabox styling blocks into separate stylesheets: `assets/css/admin-settings.css` and `assets/css/order-metabox.css`.
2. Extracted all settings and metabox interactivity scripts into separate scripts: `assets/js/admin-settings.js` and `assets/js/order-metabox.js`.
3. Added the `admin_enqueue_scripts` hook to register these styles/scripts inside the plugin constructor.
4. Enqueued and localized settings and order metabox asset payloads via `wp_enqueue_style()`, `wp_enqueue_script()`, and `wp_localize_script()`.
5. Completely removed all inline HTML style and script elements, slimming down the main PHP controller file.

Commit message: `refactor(assets): move inline CSS and JS to external enqueued assets`

## Step 20: Pre-Select First Template in Metabox
1. Set the WooCommerce Order edit metabox dropdown template select markup to pre-select index `0` using the standard `selected()` helper in PHP.
2. Extracted template parsing and field population logic into the reusable `populateTemplate()` function in JavaScript.
3. Automatically set the dropdown select element value to `'0'` and triggered the population routine on page load if templates are defined.
4. Hooked into TinyMCE's `AddEditor` and `init` event loops to guarantee that delayed visual editor initializations load the default template's compiled HTML content correctly.

Commit message: `feat(metabox): pre-select and load first template on page load`
