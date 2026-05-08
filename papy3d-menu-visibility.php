<?php
declare(strict_types=1);

/**
 * Plugin Name: Papy3D Menu Visibility
 * Plugin URI: https://github.com/Papy-3D-Factory/Papy3D-Menu-Visibility
 * Description: Menu visibility by roles
 * Version: 1.1.0
 * Requires at least: 6.5
 * Tested up to: 6.9
 * Requires PHP: 8.1
 * Author: papy3d
 * Author URI: https://papy-3d-factory.xyz
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Papy3D\MenuVisibility;

use WP_Post;
use WP_Roles;

defined('ABSPATH') || exit;

final class Plugin {

	public const VERSION    = '1.1.0';
	public const META_ROLES = '_p3dmv_roles';
	public const META_MODE  = '_p3dmv_mode';

	public static function init(): void {
		add_action('plugins_loaded', [self::class, 'load_textdomain']);
		add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);

		add_action(
			'wp_nav_menu_item_custom_fields',
			[self::class, 'render_fields'],
			10,
			4
		);

		add_action(
			'wp_update_nav_menu_item',
			[self::class, 'save_fields'],
			10,
			2
		);

		add_filter(
			'wp_nav_menu_objects',
			[self::class, 'filter_menu_items'],
			10,
			2
		);

		add_filter(
			'render_block_data',
			[self::class, 'filter_navigation_block'],
			10,
			1
		);
	}

	public static function load_textdomain(): void {
		/*
		load_plugin_textdomain(
			'papy3d-menu-visibility',
			false,
			dirname(plugin_basename(__FILE__)) . '/languages'
		);
		*/
	}

	public static function enqueue_admin_assets(string $hook): void {
		if ($hook !== 'nav-menus.php') {
			return;
		}

		wp_enqueue_style(
			'p3dmv-admin',
			plugin_dir_url(__FILE__) . 'assets/admin.css',
			[],
			self::VERSION
		);
	}

	private static function get_roles(): array {
		global $wp_roles;

		if (!$wp_roles instanceof WP_Roles) {
			$wp_roles = new WP_Roles();
		}

		return $wp_roles->roles;
	}

	public static function render_fields(
		int $item_id,
		WP_Post $item,
		int $depth,
		$args
	): void {

		unset($item, $depth, $args);

		$saved_roles = get_post_meta($item_id, self::META_ROLES, true);
		$mode        = get_post_meta($item_id, self::META_MODE, true);

		if (!is_array($saved_roles)) {
			$saved_roles = [];
		}

		if (empty($mode)) {
			$mode = 'show';
		}

		$roles = self::get_roles();
		?>

		<fieldset class="p3dmv-fieldset">
			<legend><?php esc_html_e('Menu visibility', 'papy3d-menu-visibility'); ?></legend>

			<p>
				<label>
					<input type="radio"
						name="menu-item-p3dmv-mode[<?php echo esc_attr((string) $item_id); ?>]"
						value="show"
						<?php checked($mode, 'show'); ?>
					/>
					<?php esc_html_e('Show only for selected roles', 'papy3d-menu-visibility'); ?>
				</label>

				<br>

				<label>
					<input type="radio"
						name="menu-item-p3dmv-mode[<?php echo esc_attr((string) $item_id); ?>]"
						value="hide"
						<?php checked($mode, 'hide'); ?>
					/>
					<?php esc_html_e('Hide for selected roles', 'papy3d-menu-visibility'); ?>
				</label>
			</p>

			<div class="p3dmv-capabilities-list">

				<?php foreach ($roles as $role_key => $role) : ?>

					<label class="p3dmv-capability-label">

						<input
							type="checkbox"
							name="menu-item-p3dmv-roles[<?php echo esc_attr((string) $item_id); ?>][]"
							value="<?php echo esc_attr($role_key); ?>"
							<?php checked(in_array($role_key, $saved_roles, true), true); ?>
						/>

						<?php echo esc_html(translate_user_role($role['name'])); ?>

					</label>

				<?php endforeach; ?>

			</div>
		</fieldset>

		<?php
	}

	public static function save_fields(int $menu_id, int $menu_item_db_id): void {

		unset($menu_id);

		if (!current_user_can('edit_theme_options')) {
			return;
		}

		$p3dmv_nonce = isset($_POST['update-nav-menu-nonce'])
			? sanitize_text_field(wp_unslash($_POST['update-nav-menu-nonce']))
			: '';

		if (
			empty($p3dmv_nonce) ||
			!wp_verify_nonce($p3dmv_nonce, 'update-nav_menu')
		) {
			return;
		}

		// --- Mode ---
		// wp_unslash() on the whole array satisfies MissingUnslash.
		// sanitize_key() on the extracted value below satisfies InputNotSanitized.
		$p3dmv_raw_mode = isset($_POST['menu-item-p3dmv-mode']) && is_array($_POST['menu-item-p3dmv-mode'])
			? wp_unslash($_POST['menu-item-p3dmv-mode']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_key() applied on extracted value below.
			: [];

		$mode = 'show';

		if (isset($p3dmv_raw_mode[$menu_item_db_id])) {
			$mode = sanitize_key((string) $p3dmv_raw_mode[$menu_item_db_id]);
		}

		if (!in_array($mode, ['show', 'hide'], true)) {
			$mode = 'show';
		}

		update_post_meta($menu_item_db_id, self::META_MODE, $mode);

		// --- Roles ---
		// wp_unslash() on the whole array satisfies MissingUnslash.
		// sanitize_key() applied on each value in the foreach below satisfies InputNotSanitized.
		$p3dmv_raw_roles = isset($_POST['menu-item-p3dmv-roles']) && is_array($_POST['menu-item-p3dmv-roles'])
			? wp_unslash($_POST['menu-item-p3dmv-roles']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_key() applied on each value in the foreach below.
			: [];

		$p3dmv_input = [];

		if (
			isset($p3dmv_raw_roles[$menu_item_db_id])
			&& is_array($p3dmv_raw_roles[$menu_item_db_id])
		) {
			$p3dmv_input = $p3dmv_raw_roles[$menu_item_db_id];
		}

		$p3dmv_roles = [];
		$valid_roles  = self::get_roles();

		foreach ($p3dmv_input as $role) {
			$role = sanitize_key((string) $role);

			if (isset($valid_roles[$role])) {
				$p3dmv_roles[] = $role;
			}
		}

		update_post_meta(
			$menu_item_db_id,
			self::META_ROLES,
			array_values(array_unique($p3dmv_roles))
		);
	}

	private static function user_has_role(array $roles): bool {

		if (!is_user_logged_in()) {
			return false;
		}

		$user = wp_get_current_user();

		foreach ($user->roles as $role) {
			if (in_array($role, $roles, true)) {
				return true;
			}
		}

		return false;
	}

	public static function filter_menu_items(array $items, $args): array {

		unset($args);

		$filtered = [];

		foreach ($items as $item) {

			$roles = get_post_meta($item->ID, self::META_ROLES, true);
			$mode  = get_post_meta($item->ID, self::META_MODE, true);

			if (!is_array($roles) || empty($roles)) {
				$filtered[] = $item;
				continue;
			}

			$has_role = self::user_has_role($roles);

			if ($mode === 'show' && $has_role) {
				$filtered[] = $item;
				continue;
			}

			if ($mode === 'hide' && !$has_role) {
				$filtered[] = $item;
			}
		}

		return $filtered;
	}

	public static function filter_navigation_block(array $block): array {

		if (
			!isset($block['blockName']) ||
			$block['blockName'] !== 'core/navigation-link'
		) {
			return $block;
		}

		if (empty($block['attrs']['id'])) {
			return $block;
		}

		$item_id = (int) $block['attrs']['id'];

		$roles = get_post_meta($item_id, self::META_ROLES, true);
		$mode  = get_post_meta($item_id, self::META_MODE, true);

		if (!is_array($roles) || empty($roles)) {
			return $block;
		}

		$has_role = self::user_has_role($roles);

		if ($mode === 'show' && !$has_role) {
			return [];
		}

		if ($mode === 'hide' && $has_role) {
			return [];
		}

		return $block;
	}
}

Plugin::init();