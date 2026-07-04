<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Mewshtari_Email_In_Order_Injector' ) ) {

    /**
     * WooCommerce Email Hook Injector.
     * Hooks into transactional WooCommerce emails and prepends template content.
     */
    class Mewshtari_Email_In_Order_Injector {

        /**
         * Constructor. Hooks into woocommerce_email_before_order_table.
         */
        public function __construct() {
            add_action( 'woocommerce_email_before_order_table', [ $this, 'inject_email_content' ], 10, 4 );
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
            $data = Mewshtari_Email_In_Order::get_order_placeholder_data( $order );
            $html = Mewshtari_Email_In_Order::replace_placeholders( $html, $data, $plain_text );

            if ( $plain_text ) {
                echo esc_html( wp_strip_all_tags( $html ) ) . "\n\n";
            } else {
                echo wp_kses_post( $html );
            }
        }
    }
}
