<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class JES_Debug_Log_Rotation_Admin_Settings_Page {

	public function __construct() {

		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'init_settings'));
	}

	public function add_admin_menu() {

		add_management_page(
			esc_html__('Debug Log Rotation', 'text_domain'),
			esc_html__('Debug Log Rotation', 'text_domain'),
			'manage_options',
			'jes-debug-log-rotation-settings',
			array($this, 'page_layout'),
			''
		);
	}

	public function init_settings() {

		register_setting(
			'jes_debug_log_rotation',
			'jes_debug_log_rotation'
		);

		add_settings_section(
			'jes_debug_log_rotation_section',
			'',
			false,
			'jes_debug_log_rotation'
		);

		add_settings_field(
			'max_file_size',
			__('Archive files when they grow larger than:', 'text_domain'),
			array($this, 'render_max_file_size_field'),
			'jes_debug_log_rotation',
			'jes_debug_log_rotation_section'
		);
		add_settings_field(
			'max_zipped_files',
			__('Maximum compressed files to store:', 'text_domain'),
			array($this, 'render_max_zipped_files_field'),
			'jes_debug_log_rotation',
			'jes_debug_log_rotation_section'
		);
	}

	public function page_layout() {

		// Check required user capability
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'text_domain'));
		}

		// Admin Page Layout
		echo '<div class="wrap">';
		echo '	<h1>' . get_admin_page_title() . '</h1>';
		// echo '<h3>Output the player on any page with the below shortcode:</br></br>';
		echo '	<form action="options.php" method="post">';

		settings_fields('jes_debug_log_rotation');
		do_settings_sections('jes_debug_log_rotation');
		submit_button();

		echo '</form>';
		echo '</div>';
	}

	function render_max_file_size_field() {

		// Retrieve data from the database.
		$options = get_option('jes_debug_log_rotation');

		// Set default value.
		$value = isset($options['max_file_size']) ? $options['max_file_size'] : '';

		// Field output.
		echo '<input type="number" name="jes_debug_log_rotation[max_file_size]" class="regular-text max_file_size_field" placeholder="' . esc_attr__('', 'text_domain') . '" value="' . esc_attr($value) . '">';
		echo '<p class="description">' . __('Size must be in KB.', 'text_domain') . '</p>';
	}

	function render_max_zipped_files_field() {

		// Retrieve data from the database.
		$options = get_option('jes_debug_log_rotation');

		// Set default value.
		$value = isset($options['max_zipped_files']) ? $options['max_zipped_files'] : '';

		// Field output.
		echo '<input type="number" name="jes_debug_log_rotation[max_zipped_files]" class="regular-text max_zipped_files_field" placeholder="' . esc_attr__('', 'text_domain') . '" value="' . esc_attr($value) . '">';
		// echo '<p class="description">' . __('Found in your Anchor settings. Looks like https://anchor.fm/s/{YOUR SITE KEY}/podcast/rss (make sure there is no "/" at the end)', 'text_domain') . '</p>';
	}
}

new JES_Debug_Log_Rotation_Admin_Settings_Page;
