# Changelog

## Step 1:
1. Created the main plugin entry file `mewshtari-email-in-order-for-woocommerce.php` with correct WordPress plugin headers.
2. Updated all text domain identifiers from `wcocb` to `mewshtari-email-in-order-for-woocommerce`.
3. Added translator comments for parameterized translation strings.
4. Cleaned up and deleted `codes.php`.
5. Updated plugin version to `1.0.0`.

Commit message:
`feat: initialize standard wordpress plugin structure and set version to 1.0.0`

## Step 2:
1. Wrapped the entire PHP execution code under the modern namespace `Mewshtari\EmailInOrder`.
2. Extracted inline CSS and JavaScript enqueues into dedicated [assets/css/admin.css](file:///e:/wps/dorsanet/app/public/wp-content/plugins/mewshtari-email-in-order-for-woocommerce/assets/css/admin.css) and [assets/js/admin.js](file:///e:/wps/dorsanet/app/public/wp-content/plugins/mewshtari-email-in-order-for-woocommerce/assets/js/admin.js) files.
3. Updated script/style enqueuing in `enqueue_admin_assets()` to reference local assets URLs.

Commit message:
`refactor: add PHP namespacing and externalize assets to follow WordPress directory guidelines`

## Step 3:
1. Replaced occurrences of "Custom Email Box" and "Custom Email Content" in metabox headers, page titles, and menus with localized strings based on the plugin name (`Mewshtari Email in Order` and `Mewshtari Email Content`).

Commit message:
`style: rename text strings to match new plugin name`

## Step 4:
1. Renamed all codebase references to class `WC_Order_Custom_Email_Box` and its database prefix `wcocb` to `Mewshtari_Email_In_Order` and `meiofw` to ensure no traces of the old name remain.
2. Created a standard `readme.txt` file following the WordPress.org Plugin Directory documentation formatting requirements.
3. Created an `uninstall.php` file to cleanly drop all plugin options on deletion.

Commit message:
`refactor: rename class/options to meiofw, add readme.txt and uninstall.php`

## Step 5:
1. Replaced the 5 static template settings with a single `meiofw_templates` option containing an array of templates.
2. Built a Javascript repeater interface on the settings page to let users dynamically add, remove, and sort templates.
3. Integrated the standard WordPress `wp_enqueue_code_editor` (CodeMirror) on the settings page to provide syntax highlighting inside the template HTML textarea fields.
4. Added status selection (`status`) to each template in the repeater to allow templates to transition orders to different statuses (like Completed, Cancelled, Processing) dynamically upon sending.
5. Updated `uninstall.php` to delete the new single `meiofw_templates` database option key.

Commit message:
`feat: transition static templates to a dynamic repeater settings system with CodeMirror syntax highlighting`

## Step 6:
1. Updated the default templates initialization to only return a single "Default Template" on plugin install/first load, instead of five fallback items.

Commit message:
`chore: default to a single template configuration on fresh installation`

## Step 7:
1. Re-styled the plugin settings page UI/UX using a clean, modern card-based layout with professional indigo accents and gradients.
2. Built a click-to-copy placeholder dashboard component using CSS variables and interactive JS clipboard APIs with animated toast micro-interactions.
3. Enhanced the repeater animation experience with smooth jQuery `slideUp` and `slideDown` transition effects.

Commit message:
`design: modernize settings page UI/UX with professional cards and interactive copy helper`

## Step 8:
1. Converted the settings layout into a two-column layout: main template form on the left, and copyable placeholders sidebar on the right.
2. Made the placeholders sidebar sticky using CSS `position: sticky; top: 50px;` so it remains visible when scrolling through templates.

Commit message:
`design: move placeholders to a sticky sidebar layout`
