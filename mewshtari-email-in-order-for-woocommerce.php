<?php
/**
 * Plugin Name: Mewshtari Email in Order for WooCommerce
 * Description: Adds a customizable WYSIWYG email panel to the WooCommerce order page to send personalized emails to customers. Includes support for dynamic templates with customizable labels, HTML content, and order statuses.
 * Author: micromax
 * Version: 1.0.0
 * Text Domain: mewshtari-email-in-order-for-woocommerce
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Mewshtari\EmailInOrder;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order;

if (!defined('ABSPATH')) { exit; }

add_action('plugins_loaded', function () {
	if (!class_exists('WooCommerce')) return;
	new Mewshtari_Email_In_Order();
});

final class Mewshtari_Email_In_Order {
	const NONCE = 'meiofw_send_email';

	public function __construct() {
		add_action('add_meta_boxes', [$this, 'add_box'], 20);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
		add_action('woocommerce_email_before_order_table', [$this, 'email_content'], 10, 4);
		add_action('admin_menu', [$this, 'settings_page']);
		add_action('admin_init', [$this, 'register_settings']);
		add_action('wp_ajax_meiofw_send_email', [$this, 'ajax_send_email']);
	}

	public function get_templates() {
		$templates = get_option('meiofw_templates', []);
		if (!is_array($templates) || empty($templates)) {
			$templates = [
				[
					'label'  => __('Default Template', 'mewshtari-email-in-order-for-woocommerce'),
					'html'   => '',
					'status' => 'completed',
				],
			];
		}
		return $templates;
	}

	private function format_order_date(WC_Order $order): string {
		$dt = $order->get_date_created();
		if (!$dt) return '';
		return wc_format_datetime($dt, get_option('date_format'));
	}

	private function replace_placeholders(string $template, WC_Order $order): string {
		$name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
		if ($name === '') $name = __('Donor', 'mewshtari-email-in-order-for-woocommerce');

		$title = '';
		$link  = '';
		foreach ($order->get_items('line_item') as $item) {
			$product = $item->get_product();
			if ($product) {
				$title = $product->get_name();
				$link  = get_permalink($product->is_type('variation') ? $product->get_parent_id() : $product->get_id());
				break;
			}
		}
		if ($title === '') {
			$title = __('our project', 'mewshtari-email-in-order-for-woocommerce');
			$link  = home_url('/');
		}

		$order_date = $this->format_order_date($order);

		$replacements = [
			'[name]'          => esc_html($name),
			'[product_title]' => esc_html($title),
			'[product_link]'  => esc_url($link),
			'[order_date]'    => esc_html($order_date),
		];

		return strtr($template, $replacements);
	}

	private function get_templates_for_order(WC_Order $order): array {
		$templates = $this->get_templates();
		$out = [];
		foreach ($templates as $i => $tpl) {
			$label  = isset($tpl['label']) ? $tpl['label'] : '';
			$html   = isset($tpl['html']) ? $tpl['html'] : '';
			$status = isset($tpl['status']) ? $tpl['status'] : 'completed';

			$processed = $html !== '' ? $this->replace_placeholders($html, $order) : '';
			$out[] = [
				'id'       => $i,
				'label'    => $label,
				'raw'      => $html,
				'html'     => $processed,
				'status'   => $status,
				'has_html' => $processed !== '',
			];
		}
		return $out;
	}

	private function get_first_nonempty_template_html(WC_Order $order): string {
		foreach ($this->get_templates_for_order($order) as $tpl) {
			if ($tpl['has_html']) return $tpl['html'];
		}
		return '';
	}

	private function get_template_html_by_id(WC_Order $order, int $id): string {
		$templates = $this->get_templates();
		if (!isset($templates[$id])) return '';
		$html = isset($templates[$id]['html']) ? trim($templates[$id]['html']) : '';
		return $html !== '' ? $this->replace_placeholders($html, $order) : '';
	}

	private function get_current_order() {
		if (class_exists('Automattic\\WooCommerce\\Utilities\\OrderUtil') && OrderUtil::custom_orders_table_usage_is_enabled()) {
			$id = isset($_GET['id']) ? absint($_GET['id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $id ? wc_get_order($id) : false;
		}
		global $post;
		return $post ? wc_get_order($post->ID) : false;
	}

	public function enqueue_admin_assets($hook) {
		if (strpos((string) $hook, 'meiofw-settings') !== false) {
			wp_enqueue_style(
				'meiofw-admin-style',
				plugins_url('assets/css/admin.css', __FILE__),
				[],
				'1.0.0'
			);
			wp_enqueue_script(
				'meiofw-admin-settings',
				plugins_url('assets/js/admin-settings.js', __FILE__),
				['jquery'],
				'1.0.0',
				true
			);
			$settings = wp_enqueue_code_editor(['type' => 'text/html']);
			wp_localize_script('meiofw-admin-settings', 'MEIOFW_Settings', [
				'codeEditorSettings' => $settings,
				'statuses'           => wc_get_order_statuses(),
				'templates'          => $this->get_templates(),
			]);
			return;
		}

		$is_orders_screen = (strpos((string) $hook, 'wc-orders') !== false) || $hook === 'post.php';
		if (!$is_orders_screen) return;

		wp_enqueue_style(
			'meiofw-admin-style',
			plugins_url('assets/css/admin.css', __FILE__),
			['woocommerce_admin_styles'],
			'1.0.0'
		);

		wp_enqueue_script(
			'meiofw-admin-script',
			plugins_url('assets/js/admin.js', __FILE__),
			['jquery'],
			'1.0.0',
			true
		);

		wp_localize_script('meiofw-admin-script', 'MEIOFW', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce'   => wp_create_nonce(self::NONCE),
			'i18n'    => [
				'sending' => __('Sending…', 'mewshtari-email-in-order-for-woocommerce'),
				'failed'  => __('Failed to send.', 'mewshtari-email-in-order-for-woocommerce'),
				'request' => __('Request failed.', 'mewshtari-email-in-order-for-woocommerce'),
			],
		]);
	}

	public function add_box() {
		if (!function_exists('wc_get_page_screen_id')) return;
		$screen = wc_get_page_screen_id('shop-order');
		add_meta_box(
			'meiofw-box',
			__('Mewshtari Email Content', 'mewshtari-email-in-order-for-woocommerce'),
			[$this, 'render_box'],
			$screen,
			'normal',
			'core'
		);
	}

	public function render_box($post_or_screen) {
		$order = $this->get_current_order();
		if (!$order) {
			echo '<p>' . esc_html__('Order not found.', 'mewshtari-email-in-order-for-woocommerce') . '</p>';
			return;
		}

		$email        = $order->get_billing_email();
		$is_completed = $order->has_status('completed');
		$is_cancelled = $order->has_status('cancelled');

		$subject_default = __('Donation Confirmation - Islamic Donate Charity', 'mewshtari-email-in-order-for-woocommerce');
		$templates       = $this->get_templates_for_order($order);
		$initial_html    = $this->get_first_nonempty_template_html($order);

		echo '<div class="panel-wrap woocommerce meiofw-panel">';

		echo '<div class="woocommerce-order-data__heading"><strong>' . esc_html__('Template', 'mewshtari-email-in-order-for-woocommerce') . '</strong></div>';
		echo '<select id="meiofw-template-select" class="meiofw-field" style="max-width:420px">';
		
		$statuses = wc_get_order_statuses();
		foreach ($templates as $tpl) {
			$status_label = '';
			$status_key = strpos($tpl['status'], 'wc-') === 0 ? $tpl['status'] : 'wc-' . $tpl['status'];
			if (isset($statuses[$status_key])) {
				$status_label = ' (' . $statuses[$status_key] . ')';
			}
			echo '<option value="' . esc_attr($tpl['id']) . '">' . esc_html($tpl['label'] . $status_label) . '</option>';
		}
		echo '</select>';

		echo '<div class="woocommerce-order-data__heading" style="margin-top:10px;"><strong>' . esc_html__('Email subject', 'mewshtari-email-in-order-for-woocommerce') . '</strong></div>';
		echo '<input type="text" name="meiofw_subject" class="meiofw-field" value="' . esc_attr($subject_default) . '" />';

		echo '<div class="woocommerce-order-data__heading" style="margin-top:10px;"><strong>' . esc_html__('HTML to include in customer emails', 'mewshtari-email-in-order-for-woocommerce') . '</strong></div>';

		wp_editor(
			$initial_html,
			'meiofw_note',
			[
				'textarea_name' => 'meiofw_note',
				'textarea_rows' => 12,
				'media_buttons' => false,
				'teeny'         => true,
			]
		);

		if ($email && !$is_completed && !$is_cancelled) {
			echo '<div class="meiofw-actions">';
			echo '<button type="button" class="button button-primary meiofw-send-now" data-order="' . esc_attr($order->get_id()) . '">' . esc_html__('Send to customer now', 'mewshtari-email-in-order-for-woocommerce') . '</button>';
			echo '<span class="meiofw-status"></span>';
			echo '</div>';
		}

		$map = [];
		foreach ($templates as $tpl) {
			$map[(string)$tpl['id']] = $tpl['html'];
		}
		echo '<script>window.MEIOFW_TEMPLATES = ' . wp_json_encode($map) . ';</script>';

		echo '</div>';
	}

	public function email_content($order, $sent_to_admin, $plain_text, $email) {
		if (!$order instanceof WC_Order) return;
		$note = $this->get_first_nonempty_template_html($order);
		if ($note === '') return;
		if ($plain_text) {
			echo PHP_EOL . wp_strip_all_tags($note) . PHP_EOL;
			return;
		}
		echo '<div>' . wpautop(wp_kses_post($note)) . '</div>';
	}

	public function settings_page() {
		add_submenu_page(
			'woocommerce',
			__('Mewshtari Email in Order Settings', 'mewshtari-email-in-order-for-woocommerce'),
			__('Mewshtari Email in Order', 'mewshtari-email-in-order-for-woocommerce'),
			'manage_woocommerce',
			'meiofw-settings',
			[$this, 'settings_page_html']
		);
	}

	public function register_settings() {
		register_setting(
			'meiofw_settings_group',
			'meiofw_templates',
			[
				'type'              => 'array',
				'sanitize_callback' => [$this, 'sanitize_templates'],
				'show_in_rest'      => false,
			]
		);
	}

	public function sanitize_templates($v) {
		if (!is_array($v)) return [];
		$sanitized = [];
		foreach ($v as $item) {
			if (!is_array($item)) continue;
			$label  = isset($item['label']) ? sanitize_text_field(wp_unslash($item['label'])) : '';
			$html   = isset($item['html']) ? wp_kses_post(wp_unslash($item['html'])) : '';
			$status = isset($item['status']) ? sanitize_key(wp_unslash($item['status'])) : 'completed';

			$sanitized[] = [
				'label'  => $label,
				'html'   => $html,
				'status' => $status,
			];
		}
		return $sanitized;
	}

	public function settings_page_html() {
		if (!current_user_can('manage_woocommerce')) return;
		?>
		<div class="meiofw-settings-wrap">
			<div class="meiofw-header">
				<h1><?php _e('Mewshtari Email in Order Settings', 'mewshtari-email-in-order-for-woocommerce'); ?></h1>
				<p><?php _e('Configure custom email templates and link them to order status changes.', 'mewshtari-email-in-order-for-woocommerce'); ?></p>
			</div>

			<div class="meiofw-settings-body">
				<div class="meiofw-settings-main">
					<form method="post" action="options.php">
						<?php settings_fields('meiofw_settings_group'); ?>
						
						<div id="meiofw-repeater-container">
							<!-- Repeater rows rendered dynamically by JS -->
						</div>

						<div style="display: flex; gap: 15px; align-items: center; margin-top: 30px;">
							<button type="button" class="meiofw-add-btn" id="meiofw-add-template">
								<?php _e('Add New Template', 'mewshtari-email-in-order-for-woocommerce'); ?>
							</button>
							<button type="submit" class="meiofw-save-btn">
								<?php _e('Save Settings', 'mewshtari-email-in-order-for-woocommerce'); ?>
							</button>
						</div>
					</form>
				</div>

				<div class="meiofw-settings-sidebar">
					<div class="meiofw-card meiofw-sticky-sidebar">
						<h2><?php _e('Available Placeholders', 'mewshtari-email-in-order-for-woocommerce'); ?></h2>
						<p style="color: var(--meiofw-text-muted); font-size: 13px; margin-bottom: 15px;"><?php _e('Click on any badge below to copy the placeholder code to your clipboard.', 'mewshtari-email-in-order-for-woocommerce'); ?></p>
						
						<div class="meiofw-placeholder-badge" title="<?php esc_attr_e('Click to copy', 'mewshtari-email-in-order-for-woocommerce'); ?>" style="margin-bottom: 12px;">
							<code>[name]</code>
							<span><?php _e('Customer full name', 'mewshtari-email-in-order-for-woocommerce'); ?></span>
						</div>
						<div class="meiofw-placeholder-badge" title="<?php esc_attr_e('Click to copy', 'mewshtari-email-in-order-for-woocommerce'); ?>" style="margin-bottom: 12px;">
							<code>[product_title]</code>
							<span><?php _e('First product title', 'mewshtari-email-in-order-for-woocommerce'); ?></span>
						</div>
						<div class="meiofw-placeholder-badge" title="<?php esc_attr_e('Click to copy', 'mewshtari-email-in-order-for-woocommerce'); ?>" style="margin-bottom: 12px;">
							<code>[product_link]</code>
							<span><?php _e('Link to the product page', 'mewshtari-email-in-order-for-woocommerce'); ?></span>
						</div>
						<div class="meiofw-placeholder-badge" title="<?php esc_attr_e('Click to copy', 'mewshtari-email-in-order-for-woocommerce'); ?>">
							<code>[order_date]</code>
							<span><?php _e('Order creation date', 'mewshtari-email-in-order-for-woocommerce'); ?></span>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function ajax_send_email() {
		$order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
		if (!$order_id || !current_user_can('edit_shop_order', $order_id)) {
			wp_send_json_error(['message' => __('You do not have permission to do this.', 'mewshtari-email-in-order-for-woocommerce')]);
		}

		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (!wp_verify_nonce($nonce, self::NONCE)) {
			wp_send_json_error(['message' => __('Security check failed.', 'mewshtari-email-in-order-for-woocommerce')]);
		}

		$order = wc_get_order($order_id);
		if (!$order) {
			wp_send_json_error(['message' => __('Order not found.', 'mewshtari-email-in-order-for-woocommerce')]);
		}

		$content     = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';
		$subject     = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : __('Donation Confirmation - Islamic Donate Charity', 'mewshtari-email-in-order-for-woocommerce');
		$template_id = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;

		$templates = $this->get_templates();
		$template = isset($templates[$template_id]) ? $templates[$template_id] : null;

		if (!$template) {
			wp_send_json_error(['message' => __('Selected template does not exist.', 'mewshtari-email-in-order-for-woocommerce')]);
		}

		if (trim($content) === '') {
			$content = $this->get_template_html_by_id($order, $template_id);
		}

		if (trim($content) === '') {
			wp_send_json_error(['message' => __('Email content is empty.', 'mewshtari-email-in-order-for-woocommerce')]);
		}

		$mailer = \WC()->mailer();
		$to     = $order->get_billing_email();
		if (!$to) {
			wp_send_json_error(['message' => __('Order has no customer email.', 'mewshtari-email-in-order-for-woocommerce')]);
		}

		// Pull WooCommerce's configured "From" name/address so Reply-To matches the actual sending address.
		$from_email = sanitize_email( get_option('woocommerce_email_from_address', get_bloginfo('admin_email')) );
		$from_name  = wp_specialchars_decode( get_option('woocommerce_email_from_name', get_bloginfo('name')), ENT_QUOTES );

		// Build headers with From + Reply-To and proper content type.
		$headers = [
			'From: ' . sprintf('%s <%s>', $from_name, $from_email),
			'Reply-To: ' . sprintf('%s <%s>', $from_name, $from_email),
			'Content-Type: text/html; charset=UTF-8',
		];

		$message = '<div style="font-family:Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;">' . wpautop(wp_kses_post($content)) . '</div>';

		// Pass headers so the Reply-To is enforced.
		$sent = $mailer->send($to, $subject, $message, $headers);

		if (!$sent) {
			wp_send_json_error(['message' => __('Email could not be sent. Check your email settings.', 'mewshtari-email-in-order-for-woocommerce')]);
		}

		// Update order status dynamically based on template configuration.
		$status_to_apply = str_replace('wc-', '', $template['status']);
		if (!$order->has_status($status_to_apply)) {
			$order->update_status($status_to_apply, sprintf(__('Status updated after custom email sent (%s).', 'mewshtari-email-in-order-for-woocommerce'), $template['label']), true);
		}

		wp_send_json_success(['message' => sprintf(__('Email sent and order status updated to %s.', 'mewshtari-email-in-order-for-woocommerce'), $status_to_apply)]);
	}
}
