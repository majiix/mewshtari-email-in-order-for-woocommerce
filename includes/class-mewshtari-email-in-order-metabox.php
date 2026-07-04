<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Mewshtari_Email_In_Order_Metabox' ) ) {

    /**
     * WooCommerce Metabox Controller.
     * Handles metabox registration, layout rendering, placeholders compiling, and AJAX email transmission.
     */
    class Mewshtari_Email_In_Order_Metabox {

        /**
         * Constructor. Hooks into metabox registration, script enqueuing, and send handler.
         */
        public function __construct() {
            add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
            add_action( 'wp_ajax_mewshtari_send_custom_email', [ $this, 'ajax_send_custom_email' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'register_metabox_assets' ] );
        }

        /**
         * Registers assets conditionally for the order edit screens.
         *
         * @param string $hook Current admin page suffix.
         */
        public function register_metabox_assets( string $hook ): void {
            if ( 'post.php' !== $hook && 'post-new.php' !== $hook && 'woocommerce_page_wc-orders' !== $hook ) {
                return;
            }

            $css_ver = file_exists( MEW_EMAIL_ORDER_PATH . 'assets/css/order-metabox.css' ) ? filemtime( MEW_EMAIL_ORDER_PATH . 'assets/css/order-metabox.css' ) : MEW_EMAIL_ORDER_VERSION;
            $js_ver  = file_exists( MEW_EMAIL_ORDER_PATH . 'assets/js/order-metabox.js' ) ? filemtime( MEW_EMAIL_ORDER_PATH . 'assets/js/order-metabox.js' ) : MEW_EMAIL_ORDER_VERSION;

            wp_register_style(
                'mewshtari-order-metabox',
                MEW_EMAIL_ORDER_URL . 'assets/css/order-metabox.css',
                [],
                $css_ver
            );
            wp_register_script(
                'mewshtari-order-metabox',
                MEW_EMAIL_ORDER_URL . 'assets/js/order-metabox.js',
                [ 'jquery' ],
                $js_ver,
                true
            );
        }

        /**
         * Registers the custom metabox on WooCommerce Order edit screens.
         */
        public function register_meta_boxes(): void {
            $templates = get_option( 'mewshtari_email_templates', [] );
            if ( empty( $templates ) ) {
                return;
            }
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
                    'low'
                );
            }
        }

        /**
         * Renders the order edit screen metabox.
         *
         * @param WP_Post|WC_Order $post_or_order The global post or order object.
         */
        public function render_meta_box( $post_or_order ): void {
            $order_id = 0;
            if ( is_a( $post_or_order, 'WP_Post' ) ) {
                $order_id = $post_or_order->ID;
            } elseif ( is_a( $post_or_order, 'WC_Order' ) ) {
                $order_id = $post_or_order->get_id();
            }

            if ( ! $order_id ) {
                if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $order_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
                } else {
                    global $post;
                    $order_id = $post ? $post->ID : 0;
                }
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
            $raw_data = Mewshtari_Email_In_Order::get_order_placeholder_data( $order );

            $product_names = array_column( $raw_data['items'], 'name' );
            if ( empty( $product_names ) ) {
                $product_names[] = 'Item';
            }

            $products_title_csv = implode( ', ', $product_names );
            $products_title_list = '<ul><li>' . implode( '</li><li>', array_map( 'esc_html', $product_names ) ) . '</li></ul>';

            $first_title_with_link_html = '<a href="' . esc_url( $raw_data['first_link'] ) . '">' . esc_html( $raw_data['first_title'] ) . '</a>';
            $first_title_with_link_plain = $raw_data['first_title'] . ' (' . $raw_data['first_link'] . ')';

            $all_products_html = [];
            $all_products_plain = [];
            foreach ( $raw_data['items'] as $item ) {
                $all_products_html[] = '<li><a href="' . esc_url( $item['link'] ) . '">' . esc_html( $item['name'] ) . '</a></li>';
                $all_products_plain[] = $item['name'] . ' (' . $item['link'] . ')';
            }
            if ( empty( $all_products_html ) ) {
                $all_products_html[] = '<li>Item</li>';
                $all_products_plain[] = 'Item';
            }

            $all_title_with_links_html = '<ul>' . implode( '', $all_products_html ) . '</ul>';
            $all_title_with_links_plain = implode( ', ', $all_products_plain );

            return [
                'name'                           => esc_attr( $raw_data['billing_name'] ),
                'product_title'                  => esc_attr( $raw_data['first_title'] ),
                'product_link'                   => esc_url( $raw_data['first_link'] ),
                'products_title'                 => esc_attr( $products_title_csv ),
                'products_title_html'            => $products_title_list,
                'product_title_with_link'        => esc_attr( $first_title_with_link_plain ),
                'product_title_with_link_html'   => $first_title_with_link_html,
                'products_title_with_links'      => esc_attr( $all_title_with_links_plain ),
                'products_title_with_links_html' => $all_title_with_links_html,
                'order_date'                     => esc_attr( $raw_data['order_date'] ),
            ];
        }

        /**
         * Outputs the metabox HTML.
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
                    'statuses'  => wc_get_order_statuses(),
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
                        'statusNoticePrefix' => __( 'After sending email the status of this order will be changed to ', 'mewshtari-email-in-order-for-woocommerce' ),
                    ],
                ]
            );
            ?>
            <div class="mewshtari-mb-container">
                <div class="mewshtari-mb-grid">
                    <div class="mewshtari-mb-field">
                        <label for="mewshtari_template_select" class="mewshtari-mb-label">
                            <?php esc_html_e( 'Select Email Template', 'mewshtari-email-in-order-for-woocommerce' ); ?>
                        </label>
                        <select id="mewshtari_template_select" class="mewshtari-mb-select">
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
                </div>

                <div id="mewshtari-mb-status-notice" class="mewshtari-mb-status-notice" style="display: none;"></div>

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
         * Dispatches HTML emails using WooCommerce mailer framework.
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

            $mailer = WC()->mailer();
            if ( ! $mailer ) {
                return false;
            }

            return $mailer->send( $recipient, $subject, $body, $headers );
        }

        /**
         * Executes order status transitions and logs transmission note.
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
    }
}
