<?php
/**
 * Plugin Name: Mewshtari Email in Order for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/mewshtari-email-in-order-for-woocommerce
 * Description: Dynamically sends custom status-mapped HTML emails to customers from WooCommerce orders.
 * Version: 1.1.0
 * Author: Antigravity
 * Text Domain: mewshtari-email-in-order-for-woocommerce
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

// Prevent naming collisions using a unique class check.
if ( ! class_exists( 'Mewshtari_Email_In_Order' ) ) {

    /**
     * Main Plugin Controller Class.
     * Handles settings, meta boxes, AJAX dispatch, and email injections.
     */
    class Mewshtari_Email_In_Order {

        /**
         * Singleton instance of the class.
         *
         * @var Mewshtari_Email_In_Order|null
         */
        private static ?Mewshtari_Email_In_Order $instance = null;

        /**
         * Returns the singleton instance.
         *
         * @return Mewshtari_Email_In_Order
         */
        public static function get_instance(): Mewshtari_Email_In_Order {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Constructor. Registers core WordPress and WooCommerce hooks.
         */
        private function __construct() {
            add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
            add_action( 'admin_init', [ $this, 'register_settings' ] );
            add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
            add_action( 'wp_ajax_mewshtari_send_custom_email', [ $this, 'ajax_send_custom_email' ] );
            add_action( 'wp_ajax_mewshtari_save_settings', [ $this, 'ajax_save_settings' ] );
            add_action( 'woocommerce_email_before_order_table', [ $this, 'inject_email_content' ], 10, 4 );
            add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_assets' ] );
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
         * Registers CSS and JS assets in WordPress admin so they can be enqueued conditionally.
         *
         * @param string $hook The current admin page suffix.
         */
        public function register_admin_assets( string $hook ): void {
            // Register Stylesheets
            wp_register_style(
                'mewshtari-admin-settings',
                plugin_dir_url( __FILE__ ) . 'assets/css/admin-settings.css',
                [],
                '1.1.0'
            );
            wp_register_style(
                'mewshtari-order-metabox',
                plugin_dir_url( __FILE__ ) . 'assets/css/order-metabox.css',
                [],
                '1.1.0'
            );

            // Register Scripts
            wp_register_script(
                'mewshtari-admin-settings',
                plugin_dir_url( __FILE__ ) . 'assets/js/admin-settings.js',
                [ 'jquery' ],
                '1.1.0',
                true
            );
            wp_register_script(
                'mewshtari-order-metabox',
                plugin_dir_url( __FILE__ ) . 'assets/js/order-metabox.js',
                [ 'jquery' ],
                '1.1.0',
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
            $sanitized = [];
            foreach ( $input as $tpl ) {
                if ( ! is_array( $tpl ) ) {
                    continue;
                }
                $name    = isset( $tpl['name'] ) ? sanitize_text_field( wp_unslash( $tpl['name'] ) ) : '';
                $subject = isset( $tpl['subject'] ) ? sanitize_text_field( wp_unslash( $tpl['subject'] ) ) : '';
                $status  = isset( $tpl['status'] ) ? sanitize_key( wp_unslash( $tpl['status'] ) ) : '';
                $html    = isset( $tpl['html'] ) ? wp_kses_post( wp_unslash( $tpl['html'] ) ) : '';

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
                'mewshtari-email-in-orders',
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

            wp_enqueue_style( 'mewshtari-admin-settings' );
            wp_enqueue_script( 'mewshtari-admin-settings' );
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
                                        <strong>[name]</strong>
                                        <div><?php esc_html_e( "Customer's full billing name (falls back to 'Donor' if empty).", 'mewshtari-email-in-order-for-woocommerce' ); ?></div>
                                    </li>
                                    <li>
                                        <strong>[product_title]</strong>
                                        <div><?php esc_html_e( 'Title of the first item found in the order.', 'mewshtari-email-in-order-for-woocommerce' ); ?></div>
                                    </li>
                                    <li>
                                        <strong>[product_link]</strong>
                                        <div><?php esc_html_e( 'Absolute permalink to that specific product.', 'mewshtari-email-in-order-for-woocommerce' ); ?></div>
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
         * Registers the custom metabox on WooCommerce Order edit screens.
         */
        public function register_meta_boxes(): void {
            $screens = [ 'shop_order' ];
            if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
                $hpos_screen = wc_get_page_screen_id( 'shop_order' );
                if ( $hpos_screen && ! in_array( $hpos_screen, $screens, true ) ) {
                    $screens[] = $hpos_screen;
                }
            }
            foreach ( $screens as $screen ) {
                add_meta_box(
                    'mewshtari_custom_email_metabox',
                    __( 'Mewshtari Email in Order', 'mewshtari-email-in-order-for-woocommerce' ),
                    [ $this, 'render_meta_box' ],
                    $screen,
                    'normal',
                    'high'
                );
            }
        }

        /**
         * Renders the order edit screen metabox.
         * Handles HPOS check and dual-compatibility.
         *
         * @param WP_Post|WC_Order $post_or_order The global post or order object.
         */
        public function render_meta_box( $post_or_order ): void {
            $order_id = 0;
            if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $order_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
            } else {
                global $post;
                $order_id = $post ? $post->ID : 0;
            }

            if ( ! $order_id && is_a( $post_or_order, 'WP_Post' ) ) {
                $order_id = $post_or_order->ID;
            } elseif ( ! $order_id && is_a( $post_or_order, 'WC_Order' ) ) {
                $order_id = $post_or_order->get_id();
            }

            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                echo '<p>' . esc_html__( 'Error: Order not found.', 'mewshtari-email-in-order-for-woocommerce' ) . '</p>';
                return;
            }

            $templates = get_option( 'mewshtari_email_templates', [] );
            $this->output_metabox_html( $order_id, $templates, $order );
        }

        /**
         * Compiles order specific placeholders.
         *
         * @param WC_Order $order WooCommerce order object.
         * @return array Compiled order placeholders.
         */
        private function get_order_placeholder_data( WC_Order $order ): array {
            $first_name   = $order->get_billing_first_name();
            $last_name    = $order->get_billing_last_name();
            $billing_name = trim( $first_name . ' ' . $last_name );
            if ( empty( $billing_name ) ) {
                $billing_name = 'Donor';
            }

            $product_title = '';
            $product_link  = '';

            foreach ( $order->get_items() as $item ) {
                $product = $item->get_product();
                if ( $product ) {
                    if ( ! $product->is_virtual() ) {
                        $product_title = $product->get_name();
                        $product_link  = $product->get_permalink();
                        break;
                    }
                }
            }

            if ( empty( $product_title ) ) {
                foreach ( $order->get_items() as $item ) {
                    $product = $item->get_product();
                    if ( $product ) {
                        $product_title = $product->get_name();
                        $product_link  = $product->get_permalink();
                        break;
                    }
                }
            }

            if ( empty( $product_title ) ) {
                $product_title = 'Item';
                $product_link  = site_url();
            }

            $order_date     = $order->get_date_created();
            $order_date_str = $order_date ? wc_format_datetime( $order_date ) : '';

            return [
                'name'          => esc_attr( $billing_name ),
                'product_title' => esc_attr( $product_title ),
                'product_link'  => esc_url( $product_link ),
                'order_date'    => esc_attr( $order_date_str ),
            ];
        }

        /**
         * Outputs the metabox inputs, styles, script elements, and localized data.
         *
         * @param int      $order_id  The order ID.
         * @param array    $templates Saved email templates array.
         * @param WC_Order $order     The WC order object.
         */
        private function output_metabox_html( int $order_id, array $templates, WC_Order $order ): void {
            $placeholder_data = $this->get_order_placeholder_data( $order );

            wp_enqueue_style( 'mewshtari-order-metabox' );
            wp_enqueue_script( 'mewshtari-order-metabox' );
            wp_localize_script(
                'mewshtari-order-metabox',
                'mewshtariMetaboxData',
                [
                    'orderData' => $placeholder_data,
                    'templates' => $templates,
                    'orderId'   => absint( $order_id ),
                    'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                    'nonce'     => wp_create_nonce( 'mewshtari_send_email_nonce_action' ),
                    'i18n'      => [
                        'copied'             => __( 'Copied placeholder to clipboard: ', 'mewshtari-email-in-order-for-woocommerce' ),
                        'selectTemplate'     => __( 'Please select an email template first.', 'mewshtari-email-in-order-for-woocommerce' ),
                        'enterSubject'       => __( 'Please enter an email subject.', 'mewshtari-email-in-order-for-woocommerce' ),
                        'enterContent'       => __( 'Please enter email content.', 'mewshtari-email-in-order-for-woocommerce' ),
                        'cancelSending'      => __( 'Cancel sending', 'mewshtari-email-in-order-for-woocommerce' ),
                        'sendingIn'          => __( 'Email will be sent in 10 seconds...', 'mewshtari-email-in-order-for-woocommerce' ),
                        'sendingInSec'       => __( 'Email will be sent in ', 'mewshtari-email-in-order-for-woocommerce' ),
                        'secondsEllipsis'    => __( ' seconds...', 'mewshtari-email-in-order-for-woocommerce' ),
                        'sendingText'        => __( 'Sending...', 'mewshtari-email-in-order-for-woocommerce' ),
                        'transmittingText'   => __( 'Transmitting email via WooCommerce mailer...', 'mewshtari-email-in-order-for-woocommerce' ),
                        'sendSuccessText'    => __( 'Email sent successfully! Reloading notes...', 'mewshtari-email-in-order-for-woocommerce' ),
                        'sendFailedText'     => __( 'Failed to send email.', 'mewshtari-email-in-order-for-woocommerce' ),
                        'sendErrorText'      => __( 'An error occurred during transmission.', 'mewshtari-email-in-order-for-woocommerce' ),
                        'sendToCustomerText' => __( 'Send to customer now', 'mewshtari-email-in-order-for-woocommerce' ),
                        'sendingCancelled'   => __( 'Sending cancelled.', 'mewshtari-email-in-order-for-woocommerce' ),
                    ],
                ]
            );
            ?>
            <div class="mewshtari-mb-container">
                <!-- Custom Toast element -->
                <div id="mewshtari-toast" class="mewshtari-toast"></div>

                <div class="mewshtari-mb-grid">
                    <div class="mewshtari-mb-field">
                        <label for="mewshtari_template_select" class="mewshtari-mb-label">
                            <?php esc_html_e( 'Select Email Template', 'mewshtari-email-in-order-for-woocommerce' ); ?>
                        </label>
                        <select id="mewshtari_template_select" class="mewshtari-mb-select">
                            <option value=""><?php esc_html_e( '-- Choose a template --', 'mewshtari-email-in-order-for-woocommerce' ); ?></option>
                            <?php foreach ( $templates as $index => $tpl ) : ?>
                                <option value="<?php echo absint( $index ); ?>" <?php selected( $index, 0 ); ?>><?php echo esc_html( $tpl['name'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mewshtari-mb-field">
                        <label for="mewshtari_email_subject" class="mewshtari-mb-label">
                            <?php esc_html_e( 'Email Subject', 'mewshtari-email-in-order-for-woocommerce' ); ?>
                        </label>
                        <input type="text" id="mewshtari_email_subject" value="<?php esc_attr_e( 'Confirmation', 'mewshtari-email-in-order-for-woocommerce' ); ?>" class="mewshtari-mb-input" />
                    </div>

                    <div class="mewshtari-badge-container">
                        <span class="mewshtari-badge-title"><?php esc_html_e( 'Click tags to copy:', 'mewshtari-email-in-order-for-woocommerce' ); ?></span>
                        <span class="mewshtari-mb-badge" data-tag="[name]" title="<?php esc_attr_e( 'Click to copy [name]', 'mewshtari-email-in-order-for-woocommerce' ); ?>">[name]</span>
                        <span class="mewshtari-mb-badge" data-tag="[product_title]" title="<?php esc_attr_e( 'Click to copy [product_title]', 'mewshtari-email-in-order-for-woocommerce' ); ?>">[product_title]</span>
                        <span class="mewshtari-mb-badge" data-tag="[product_link]" title="<?php esc_attr_e( 'Click to copy [product_link]', 'mewshtari-email-in-order-for-woocommerce' ); ?>">[product_link]</span>
                        <span class="mewshtari-mb-badge" data-tag="[order_date]" title="<?php esc_attr_e( 'Click to copy [order_date]', 'mewshtari-email-in-order-for-woocommerce' ); ?>">[order_date]</span>
                    </div>
                </div>

                <div class="mewshtari-mb-field" style="margin-bottom: 16px;">
                    <label class="mewshtari-mb-label">
                        <?php esc_html_e( 'Email Message Body', 'mewshtari-email-in-order-for-woocommerce' ); ?>
                    </label>
                    <div class="mewshtari-editor-card">
                        <?php
                        wp_editor( '', 'mewshtari_email_content', [
                            'textarea_name' => 'mewshtari_email_content',
                            'media_buttons' => false,
                            'textarea_rows' => 10,
                            'teeny'         => true,
                            'quicktags'     => true,
                        ] );
                        ?>
                    </div>
                </div>

                <div class="mewshtari-action-row">
                    <button type="button" id="mewshtari-send-email-btn" class="mewshtari-send-btn">
                        <svg class="mewshtari-spinner-svg" id="mewshtari-btn-spinner" viewBox="0 0 24 24" style="display:none;">
                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="4" style="opacity:0.25;"></circle>
                            <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" fill="currentColor"></path>
                        </svg>
                        <span id="mewshtari-btn-text"><?php esc_html_e( 'Send to customer now', 'mewshtari-email-in-order-for-woocommerce' ); ?></span>
                    </button>
                    <div id="mewshtari-status-card" class="mewshtari-status-card">
                        <span id="mewshtari-status-icon"></span>
                        <span id="mewshtari-status-text"></span>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
         * Secure AJAX handler for sending the custom email.
         * Updates WooCommerce order status upon success.
         */
        public function ajax_send_custom_email(): void {
            check_ajax_referer( 'mewshtari_send_email_nonce_action', 'security' );

            $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
            if ( ! current_user_can( 'edit_shop_order', $order_id ) ) {
                wp_send_json_error( __( 'Insufficient permissions to edit this order.', 'mewshtari-email-in-order-for-woocommerce' ) );
            }

            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                wp_send_json_error( __( 'Order not found.', 'mewshtari-email-in-order-for-woocommerce' ) );
            }

            $recipient = $order->get_billing_email();
            if ( empty( $recipient ) ) {
                wp_send_json_error( __( 'Order billing email is empty.', 'mewshtari-email-in-order-for-woocommerce' ) );
            }

            if ( ! isset( $_POST['template_index'] ) || $_POST['template_index'] === '' ) {
                wp_send_json_error( __( 'No template selected.', 'mewshtari-email-in-order-for-woocommerce' ) );
            }
            $template_index = intval( $_POST['template_index'] );

            $templates = get_option( 'mewshtari_email_templates', [] );
            if ( ! isset( $templates[ $template_index ] ) ) {
                wp_send_json_error( __( 'Invalid template selection.', 'mewshtari-email-in-order-for-woocommerce' ) );
            }

            $subject  = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
            $body     = isset( $_POST['body'] ) ? wp_kses_post( wp_unslash( $_POST['body'] ) ) : '';
            $template = $templates[ $template_index ];
            $sent     = $this->dispatch_email( $recipient, $subject, $body );

            if ( $sent ) {
                $this->process_workflow_automaton( $order, $template, $subject, $recipient );
                wp_send_json_success();
            }

            wp_send_json_error( __( 'WooCommerce mailer failed to transmit the message.', 'mewshtari-email-in-order-for-woocommerce' ) );
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

        /**
         * Dispatches HTML emails using WooCommerce mailer framework.
         *
         * @param string $recipient Target email address.
         * @param string $subject   Email subject.
         * @param string $body      HTML message content.
         * @return bool True on success, false on failure.
         */
        private function dispatch_email( string $recipient, string $subject, string $body ): bool {
            $from_address = get_option( 'woocommerce_email_from_address' );
            if ( empty( $from_address ) ) {
                $from_address = get_option( 'admin_email' );
            }
            $from_name    = get_option( 'woocommerce_email_from_name' );
            if ( empty( $from_name ) ) {
                $from_name = get_bloginfo( 'name' );
            }

            $from_name_clean = str_replace( '"', '', $from_name );

            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: "' . esc_html( $from_name_clean ) . '" <' . sanitize_email( $from_address ) . '>',
                'Reply-To: "' . esc_html( $from_name_clean ) . '" <' . sanitize_email( $from_address ) . '>',
            ];

            return WC()->mailer()->send( $recipient, $subject, $body, $headers );
        }

        /**
         * Executes order status transitions and logs transmission note.
         *
         * @param WC_Order $order     The WC order object.
         * @param array    $template  The email template configuration.
         * @param string   $subject   Email subject.
         * @param string   $recipient Recipient email.
         */
        private function process_workflow_automaton( WC_Order $order, array $template, string $subject, string $recipient ): void {
            $status_mapping = isset( $template['status'] ) ? sanitize_key( $template['status'] ) : '';
            if ( 0 === strpos( $status_mapping, 'wc-' ) ) {
                $status_mapping = substr( $status_mapping, 3 );
            }

            if ( ! empty( $status_mapping ) ) {
                $note_text = sprintf(
                    // translators: 1: template name, 2: email subject, 3: recipient address.
                    __( 'Custom email template "%1$s" successfully sent to %3$s with subject "%2$s". Status updated automatically.', 'mewshtari-email-in-order-for-woocommerce' ),
                    esc_html( $template['name'] ),
                    esc_html( $subject ),
                    esc_html( $recipient )
                );

                // Only update status if it is not already in the target status.
                if ( $order->get_status() !== $status_mapping ) {
                    $order->update_status( $status_mapping, $note_text );
                } else {
                    $order->add_order_note( $note_text );
                }
            } else {
                $note_text = sprintf(
                    // translators: 1: template name, 2: email subject, 3: recipient address.
                    __( 'Custom email template "%1$s" successfully sent to %3$s with subject "%2$s".', 'mewshtari-email-in-order-for-woocommerce' ),
                    esc_html( $template['name'] ),
                    esc_html( $subject ),
                    esc_html( $recipient )
                );
                $order->add_order_note( $note_text );
            }
        }

        /**
         * Hooks into WooCommerce before order table transactional emails.
         * Dynamic mapping matches active status and injects status-matched template.
         *
         * @param WC_Order $order          WooCommerce order.
         * @param bool     $sent_to_admin  Sent to store manager.
         * @param bool     $plain_text     Plain text flag.
         * @param object   $email          The WC email instance.
         */
        public function inject_email_content( $order, $sent_to_admin, $plain_text, $email ): void {
            if ( ! is_a( $order, 'WC_Order' ) || $sent_to_admin ) {
                return;
            }

            $email_id = isset( $email->id ) ? $email->id : '';
            $status   = '';

            if ( 'customer_processing_order' === $email_id ) {
                $status = 'processing';
            } elseif ( 'customer_completed_order' === $email_id ) {
                $status = 'completed';
            } elseif ( 'customer_on_hold_order' === $email_id ) {
                $status = 'on-hold';
            } elseif ( 'customer_refunded_order' === $email_id ) {
                $status = 'refunded';
            } elseif ( 'customer_partially_refunded_order' === $email_id ) {
                $status = 'refunded';
            }

            if ( empty( $status ) ) {
                $status = $order->get_status();
            }

            $templates = get_option( 'mewshtari_email_templates', [] );
            $matched   = null;

            foreach ( $templates as $tpl ) {
                $tpl_status = isset( $tpl['status'] ) ? sanitize_key( $tpl['status'] ) : '';
                if ( 0 === strpos( $tpl_status, 'wc-' ) ) {
                    $tpl_status = substr( $tpl_status, 3 );
                }
                if ( $tpl_status === $status ) {
                    $matched = $tpl;
                    break;
                }
            }

            if ( ! $matched || empty( $matched['html'] ) ) {
                return;
            }

            $this->output_injected_content( $order, $matched['html'], $plain_text );
        }

        /**
         * Resolves placeholders and prints matched template content.
         *
         * @param WC_Order $order      The WooCommerce order.
         * @param string   $html       Matched template HTML content.
         * @param bool     $plain_text Output plain text instead of HTML.
         */
        private function output_injected_content( WC_Order $order, string $html, bool $plain_text ): void {
            $first_name   = $order->get_billing_first_name();
            $last_name    = $order->get_billing_last_name();
            $billing_name = trim( $first_name . ' ' . $last_name );
            if ( empty( $billing_name ) ) {
                $billing_name = 'Donor';
            }

            $product_title = '';
            $product_link  = '';

            foreach ( $order->get_items() as $item ) {
                $product = $item->get_product();
                if ( $product ) {
                    if ( ! $product->is_virtual() ) {
                        $product_title = $product->get_name();
                        $product_link  = $product->get_permalink();
                        break;
                    }
                }
            }

            if ( empty( $product_title ) ) {
                foreach ( $order->get_items() as $item ) {
                    $product = $item->get_product();
                    if ( $product ) {
                        $product_title = $product->get_name();
                        $product_link  = $product->get_permalink();
                        break;
                    }
                }
            }

            if ( empty( $product_title ) ) {
                $product_title = 'Item';
                $product_link  = site_url();
            }

            $order_date     = $order->get_date_created();
            $order_date_str = $order_date ? wc_format_datetime( $order_date ) : '';

            $html = str_replace( '[name]', esc_html( $billing_name ), $html );
            $html = str_replace( '[product_title]', esc_html( $product_title ), $html );
            $html = str_replace( '[product_link]', esc_url( $product_link ), $html );
            $html = str_replace( '[order_date]', esc_html( $order_date_str ), $html );

            if ( $plain_text ) {
                echo esc_html( wp_strip_all_tags( $html ) ) . "\n\n";
            } else {
                echo wp_kses_post( $html );
            }
        }
    }
}

// Initialize the plugin class inside the plugins_loaded hook, ensuring WooCommerce is fully active.
add_action( 'plugins_loaded', 'mewshtari_email_in_order_init' );

function mewshtari_email_in_order_init() {
    if ( class_exists( 'WooCommerce' ) ) {
        Mewshtari_Email_In_Order::get_instance();
    }
}
