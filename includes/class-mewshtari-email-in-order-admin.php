<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Mewshtari_Email_In_Order_Admin' ) ) {

    /**
     * Admin Settings Panel Controller.
     * Handles settings page menu, asset enqueuing, repeater rendering, and settings saving.
     */
    class Mewshtari_Email_In_Order_Admin {

        /**
         * Constructor. Hooks into admin menu, settings init, assets, and AJAX save.
         */
        public function __construct() {
            add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
            add_action( 'wp_ajax_mewshtari_save_settings', [ $this, 'ajax_save_settings' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_assets' ] );
            add_filter( 'plugin_action_links_' . plugin_basename( MEW_EMAIL_ORDER_PATH . 'mewshtari-email-in-order-for-woocommerce.php' ), [ $this, 'add_settings_link' ] );
        }

        /**
         * Adds a settings link to the plugin action links on the plugins page.
         *
         * @param array $links Existing plugin action links.
         * @return array Modified plugin action links.
         */
        public function add_settings_link( array $links ): array {
            $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=mewshtari-email-orders' ) ) . '">' . esc_html__( 'Settings', 'mewshtari-email-in-order-for-woocommerce' ) . '</a>';
            array_unshift( $links, $settings_link );
            return $links;
        }

        /**
         * Registers the settings under the options API.
         */
        public function register_settings(): void {
            register_setting(
                'mewshtari_email_settings_group',
                'mewshtari_email_templates',
                [
                    'type'              => 'array',
                    'sanitize_callback' => [ $this, 'sanitize_templates' ],
                    'default'           => [],
                ]
            );
        }

        /**
         * Registers CSS and JS assets in WordPress admin and enqueues them on the settings page.
         *
         * @param string $hook The current admin page suffix.
         */
        public function register_admin_assets( string $hook ): void {
            if ( 'woocommerce_page_mewshtari-email-orders' !== $hook ) {
                return;
            }

            $css_ver = file_exists( MEW_EMAIL_ORDER_PATH . 'assets/css/admin-settings.css' ) ? filemtime( MEW_EMAIL_ORDER_PATH . 'assets/css/admin-settings.css' ) : MEW_EMAIL_ORDER_VERSION;
            $js_ver  = file_exists( MEW_EMAIL_ORDER_PATH . 'assets/js/admin-settings.js' ) ? filemtime( MEW_EMAIL_ORDER_PATH . 'assets/js/admin-settings.js' ) : MEW_EMAIL_ORDER_VERSION;

            wp_enqueue_style(
                'mewshtari-admin-settings',
                MEW_EMAIL_ORDER_URL . 'assets/css/admin-settings.css',
                [],
                $css_ver
            );
            wp_enqueue_script(
                'mewshtari-admin-settings',
                MEW_EMAIL_ORDER_URL . 'assets/js/admin-settings.js',
                [ 'jquery' ],
                $js_ver,
                true
            );
        }

        /**
         * Sanitizes settings template array.
         *
         * @param mixed $input Raw settings input.
         * @return array Sanitized settings output.
         */
        public function sanitize_templates( $input ): array {
            if ( ! is_array( $input ) ) {
                return [];
            }
            $input     = wp_unslash( $input );
            $sanitized = [];
            foreach ( $input as $tpl ) {
                if ( ! is_array( $tpl ) ) {
                    continue;
                }
                $name    = isset( $tpl['name'] ) ? sanitize_text_field( $tpl['name'] ) : '';
                $subject = isset( $tpl['subject'] ) ? sanitize_text_field( $tpl['subject'] ) : '';
                $status  = isset( $tpl['status'] ) ? sanitize_key( $tpl['status'] ) : '';
                $html    = isset( $tpl['html'] ) ? wp_kses_post( $tpl['html'] ) : '';

                $sanitized[] = [
                    'name'    => $name,
                    'subject' => $subject,
                    'status'  => $status,
                    'html'    => $html,
                ];
            }
            return $sanitized;
        }

        /**
         * Registers the settings page under WooCommerce menu.
         */
        public function register_admin_menu(): void {
            add_submenu_page(
                'woocommerce',
                __( 'Email in Orders', 'mewshtari-email-in-order-for-woocommerce' ),
                __( 'Email in Orders', 'mewshtari-email-in-order-for-woocommerce' ),
                'manage_woocommerce',
                'mewshtari-email-orders',
                [ $this, 'render_settings_page' ]
            );
        }

        /**
         * Renders the settings page layout with premium custom CSS and Vanilla JS.
         */
        public function render_settings_page(): void {
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mewshtari-email-in-order-for-woocommerce' ) );
            }

            wp_enqueue_editor();

            wp_localize_script(
                'mewshtari-admin-settings',
                'mewshtariSettingsData',
                [
                    'statuses' => wc_get_order_statuses(),
                    'i18n'     => [
                        'deleteConfirm' => __( 'Are you sure you want to delete this template?', 'mewshtari-email-in-order-for-woocommerce' ),
                        'yesDelete'     => __( 'Yes, Delete', 'mewshtari-email-in-order-for-woocommerce' ),
                        'cancel'        => __( 'Cancel', 'mewshtari-email-in-order-for-woocommerce' ),
                        'saving'        => __( 'Saving templates...', 'mewshtari-email-in-order-for-woocommerce' ),
                        'saveSuccess'   => __( 'Templates saved successfully!', 'mewshtari-email-in-order-for-woocommerce' ),
                        'saveFailed'    => __( 'Failed to save templates.', 'mewshtari-email-in-order-for-woocommerce' ),
                        'saveError'     => __( 'An error occurred while saving.', 'mewshtari-email-in-order-for-woocommerce' ),
                        'nameLabel'     => __( 'Template Name / Label', 'mewshtari-email-in-order-for-woocommerce' ),
                        'subjectLabel'  => __( 'Email Subject', 'mewshtari-email-in-order-for-woocommerce' ),
                        'statusLabel'   => __( 'Order Status Mapping', 'mewshtari-email-in-order-for-woocommerce' ),
                        'htmlLabel'     => __( 'HTML Content', 'mewshtari-email-in-order-for-woocommerce' ),
                        'moveUp'        => __( 'Move Up', 'mewshtari-email-in-order-for-woocommerce' ),
                        'moveDown'      => __( 'Move Down', 'mewshtari-email-in-order-for-woocommerce' ),
                        'delete'        => __( 'Delete', 'mewshtari-email-in-order-for-woocommerce' ),
                    ],
                    'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
                ]
            );

            $templates = get_option( 'mewshtari_email_templates', [] );
            $statuses  = wc_get_order_statuses();
            ?>
            <div class="wrap mewshtari-settings-wrap">
                <div class="mewshtari-header">
                    <h1><?php esc_html_e( 'Email in Orders Settings', 'mewshtari-email-in-order-for-woocommerce' ); ?></h1>
                    <p class="description"><?php esc_html_e( 'Configure dynamic HTML email templates mapped to WooCommerce order statuses.', 'mewshtari-email-in-order-for-woocommerce' ); ?></p>
                </div>

                <form id="mewshtari-settings-form" method="post" action="">
                    <input type="hidden" name="action" value="mewshtari_save_settings" />
                    <input type="hidden" name="security" value="<?php echo esc_attr( wp_create_nonce( 'mewshtari_save_settings_nonce_action' ) ); ?>" />

                    <div class="mewshtari-settings-layout">
                        <!-- Main Content: Templates Repeater -->
                        <div class="mewshtari-main-content">
                            <div id="mewshtari-templates-list" class="mewshtari-templates-container">
                                <?php if ( ! empty( $templates ) ) : ?>
                                    <?php foreach ( $templates as $index => $tpl ) : ?>
                                        <?php $this->render_template_card( $index, $tpl, $statuses ); ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px;">
                                <button type="button" id="mewshtari-add-template-btn" class="mewshtari-btn mewshtari-btn-secondary">
                                    + <?php esc_html_e( 'Add Template', 'mewshtari-email-in-order-for-woocommerce' ); ?>
                                </button>
                                <div style="display:flex; align-items:center;">
                                    <?php submit_button( __( 'Save Templates', 'mewshtari-email-in-order-for-woocommerce' ), 'primary', 'submit', false, [ 'class' => 'mewshtari-btn mewshtari-btn-primary', 'id' => 'mewshtari-save-settings-btn' ] ); ?>
                                    <span id="mewshtari-save-status" style="font-weight: 600; margin-left: 15px;"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar: Sticky Info Box -->
                        <div class="mewshtari-sidebar">
                            <div class="mewshtari-alert-box">
                                <h3><?php esc_html_e( 'Text Placeholders', 'mewshtari-email-in-order-for-woocommerce' ); ?></h3>
                                <p><?php esc_html_e( 'Use these placeholders inside your HTML content. They resolve dynamically with live order data:', 'mewshtari-email-in-order-for-woocommerce' ); ?></p>
                                <ul>
                                    <li>
                                        <strong>[name fallback="Valued Customer"]</strong>
                                        <div><?php esc_html_e( "Customer's full billing name. Supports custom fallback value.", 'mewshtari-email-in-order-for-woocommerce' ); ?></div>
                                    </li>
                                    <li>
                                        <strong>[product_title]</strong>
                                        <div><?php esc_html_e( 'Title of the first item found in the order.', 'mewshtari-email-in-order-for-woocommerce' ); ?></div>
                                    </li>
                                    <li>
                                        <strong>[product_link]</strong>
                                        <div><?php esc_html_e( 'URL link to the first item found in the order.', 'mewshtari-email-in-order-for-woocommerce' ); ?></div>
                                    </li>
                                    <li>
                                        <strong>[products_title]</strong>
                                        <div><?php esc_html_e( 'Title of all items found in the order in list order.', 'mewshtari-email-in-order-for-woocommerce' ); ?></div>
                                    </li>
                                    <li>
                                        <strong>[product_title_with_link]</strong>
                                        <div><?php esc_html_e( 'Title of the first item found in the order wrapped in an HTML link.', 'mewshtari-email-in-order-for-woocommerce' ); ?></div>
                                    </li>
                                    <li>
                                        <strong>[products_title_with_links]</strong>
                                        <div><?php esc_html_e( 'Title of all items found in the order in list order, wrapped in HTML links.', 'mewshtari-email-in-order-for-woocommerce' ); ?></div>
                                    </li>
                                    <li>
                                        <strong>[order_date]</strong>
                                        <div><?php esc_html_e( 'The site-localized creation date of the order.', 'mewshtari-email-in-order-for-woocommerce' ); ?></div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <?php
        }

        /**
         * Outputs the HTML block for a template card inside the repeater view.
         *
         * @param int   $index    The index order of the card.
         * @param array $tpl      The template details.
         * @param array $statuses Available order statuses.
         */
        private function render_template_card( int $index, array $tpl, array $statuses ): void {
            $name    = isset( $tpl['name'] ) ? $tpl['name'] : '';
            $subject = isset( $tpl['subject'] ) ? $tpl['subject'] : '';
            $status  = isset( $tpl['status'] ) ? $tpl['status'] : '';
            $html    = isset( $tpl['html'] ) ? $tpl['html'] : '';
            ?>
            <div class="mewshtari-card" data-index="<?php echo absint( $index ); ?>">
                <div class="mewshtari-card-grid">
                    <div class="mewshtari-field-group">
                        <label><?php esc_html_e( 'Template Name / Label', 'mewshtari-email-in-order-for-woocommerce' ); ?></label>
                        <input type="text" name="mewshtari_email_templates[<?php echo absint( $index ); ?>][name]" value="<?php echo esc_attr( $name ); ?>" required />
                    </div>
                    <div class="mewshtari-field-group">
                        <label><?php esc_html_e( 'Email Subject', 'mewshtari-email-in-order-for-woocommerce' ); ?></label>
                        <input type="text" name="mewshtari_email_templates[<?php echo absint( $index ); ?>][subject]" value="<?php echo esc_attr( $subject ); ?>" required />
                    </div>
                    <div class="mewshtari-field-group">
                        <label><?php esc_html_e( 'Order Status Mapping', 'mewshtari-email-in-order-for-woocommerce' ); ?></label>
                        <select name="mewshtari_email_templates[<?php echo absint( $index ); ?>][status]">
                            <?php foreach ( $statuses as $key => $label ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mewshtari-field-group">
                    <label><?php esc_html_e( 'HTML Content', 'mewshtari-email-in-order-for-woocommerce' ); ?></label>
                    <div class="mewshtari-settings-editor-wrapper">
                        <?php
                        wp_editor( $html, 'mewshtari_settings_html_' . $index, [
                            'textarea_name' => 'mewshtari_email_templates[' . $index . '][html]',
                            'media_buttons' => false,
                            'textarea_rows' => 8,
                            'teeny'         => false,
                            'quicktags'     => true,
                        ] );
                        ?>
                    </div>
                </div>
                <div class="mewshtari-card-actions">
                    <button type="button" class="mewshtari-btn mewshtari-btn-secondary mewshtari-move-btn mewshtari-move-up" title="<?php esc_attr_e( 'Move Up', 'mewshtari-email-in-order-for-woocommerce' ); ?>">&uarr;</button>
                    <button type="button" class="mewshtari-btn mewshtari-btn-secondary mewshtari-move-btn mewshtari-move-down" title="<?php esc_attr_e( 'Move Down', 'mewshtari-email-in-order-for-woocommerce' ); ?>">&darr;</button>
                    <button type="button" class="mewshtari-btn mewshtari-btn-danger"><?php esc_html_e( 'Delete', 'mewshtari-email-in-order-for-woocommerce' ); ?></button>
                </div>
            </div>
            <?php
        }

        /**
         * Secure AJAX handler for saving settings templates.
         */
        public function ajax_save_settings(): void {
            check_ajax_referer( 'mewshtari_save_settings_nonce_action', 'security' );

            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                wp_send_json_error( __( 'Insufficient permissions to manage settings.', 'mewshtari-email-in-order-for-woocommerce' ) );
            }

            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $raw_templates = isset( $_POST['mewshtari_email_templates'] ) ? wp_unslash( $_POST['mewshtari_email_templates'] ) : [];
            if ( ! is_array( $raw_templates ) ) {
                $raw_templates = [];
            }

            $sanitized = $this->sanitize_templates( $raw_templates );
            update_option( 'mewshtari_email_templates', $sanitized );

            wp_send_json_success( __( 'Templates saved successfully.', 'mewshtari-email-in-order-for-woocommerce' ) );
        }
    }
}
