<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Mewshtari_Email_In_Order' ) ) {

    /**
     * Main Plugin Controller Class.
     * Orchestrates the loading of admin, metabox, and injector sub-components.
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
         * Constructor. Instantiates components based on screen context.
         */
        private function __construct() {
            if ( is_admin() ) {
                new Mewshtari_Email_In_Order_Admin();
                new Mewshtari_Email_In_Order_Metabox();
            }
            new Mewshtari_Email_In_Order_Injector();
        }

        /**
         * Compiles order-specific placeholder data.
         *
         * @param WC_Order $order WooCommerce order object.
         * @return array Compiled order placeholders.
         */
        public static function get_order_placeholder_data( WC_Order $order ): array {
            $first_name   = $order->get_billing_first_name();
            $last_name    = $order->get_billing_last_name();
            $billing_name = trim( $first_name . ' ' . $last_name );

            $items = [];
            foreach ( $order->get_items() as $item ) {
                $product = $item->get_product();
                if ( $product ) {
                    $items[] = [
                        'name'       => $product->get_name(),
                        'link'       => $product->get_permalink(),
                        'is_virtual' => $product->is_virtual(),
                    ];
                }
            }

            // Find first non-virtual product, or fallback to first product, or fallback to 'Item'
            $first_title = 'Item';
            $first_link  = site_url();

            foreach ( $items as $item ) {
                if ( ! $item['is_virtual'] ) {
                    $first_title = $item['name'];
                    $first_link  = $item['link'];
                    break;
                }
            }

            if ( 'Item' === $first_title && ! empty( $items ) ) {
                $first_title = $items[0]['name'];
                $first_link  = $items[0]['link'];
            }

            $order_date     = $order->get_date_created();
            $order_date_str = $order_date ? wc_format_datetime( $order_date ) : '';

            return [
                'billing_name' => $billing_name,
                'first_title'  => $first_title,
                'first_link'   => $first_link,
                'items'        => $items,
                'order_date'   => $order_date_str,
            ];
        }

        /**
         * Replaces placeholders in the template text.
         *
         * @param string $text       The template text.
         * @param array  $data       Placeholder data.
         * @param bool   $plain_text Format for plain text instead of HTML.
         * @return string Replaced text.
         */
        public static function replace_placeholders( string $text, array $data, bool $plain_text = false ): string {
            $text = preg_replace_callback(
                '/\[name(?: fallback=["\'](.*?)["\'])?\]/',
                function( $matches ) use ( $data ) {
                    if ( ! empty( $data['billing_name'] ) ) {
                        return esc_html( $data['billing_name'] );
                    }
                    $fallback = isset( $matches[1] ) ? $matches[1] : 'Customer';
                    return esc_html( $fallback );
                },
                $text
            );

            $first_title = $data['first_title'];
            $first_link  = $data['first_link'];
            $items       = $data['items'];
            $order_date  = $data['order_date'];

            $product_names = array_column( $items, 'name' );
            if ( empty( $product_names ) ) {
                $product_names[] = 'Item';
            }

            if ( $plain_text ) {
                $products_title            = implode( ', ', $product_names );
                $product_title_with_link   = $first_title . ' (' . $first_link . ')';
                $all_products_plain        = [];
                foreach ( $items as $item ) {
                    $all_products_plain[] = $item['name'] . ' (' . $item['link'] . ')';
                }
                if ( empty( $all_products_plain ) ) {
                    $all_products_plain[] = 'Item';
                }
                $products_title_with_links = implode( ', ', $all_products_plain );
            } else {
                $products_title            = '<ul><li>' . implode( '</li><li>', array_map( 'esc_html', $product_names ) ) . '</li></ul>';
                $product_title_with_link   = '<a href="' . esc_url( $first_link ) . '">' . esc_html( $first_title ) . '</a>';
                $all_products_html         = [];
                foreach ( $items as $item ) {
                    $all_products_html[] = '<li><a href="' . esc_url( $item['link'] ) . '">' . esc_html( $item['name'] ) . '</a></li>';
                }
                if ( empty( $all_products_html ) ) {
                    $all_products_html[] = '<li>Item</li>';
                }
                $products_title_with_links = '<ul>' . implode( '', $all_products_html ) . '</ul>';
            }

            $text = str_replace( '[product_title]', esc_html( $first_title ), $text );
            $text = str_replace( '[products_title]', $products_title, $text );
            $text = str_replace( '[product_title_with_link]', $product_title_with_link, $text );
            $text = str_replace( '[products_title_with_links]', $products_title_with_links, $text );
            $text = str_replace( '[order_date]', esc_html( $order_date ), $text );

            return $text;
        }
    }
}
