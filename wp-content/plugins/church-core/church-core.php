<?php
/**
 * Plugin Name: Church Core
 * Description: Sermon content model and contact workflow for the church website.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Author: Codex
 * Text Domain: church-core
 */

if (! defined('ABSPATH')) {
    exit;
}

define('CHURCH_CORE_FILE', __FILE__);
define('CHURCH_CORE_PATH', plugin_dir_path(__FILE__));
define('CHURCH_CORE_URL', plugin_dir_url(__FILE__));

require_once CHURCH_CORE_PATH . 'includes/class-church-core.php';
require_once CHURCH_CORE_PATH . 'includes/class-church-core-sermons.php';
require_once CHURCH_CORE_PATH . 'includes/class-church-core-sermon-import.php';
require_once CHURCH_CORE_PATH . 'includes/class-church-core-events.php';
require_once CHURCH_CORE_PATH . 'includes/class-church-core-contact.php';

register_activation_hook(CHURCH_CORE_FILE, ['Church_Core', 'activate']);
register_deactivation_hook(CHURCH_CORE_FILE, ['Church_Core', 'deactivate']);

Church_Core::boot();
