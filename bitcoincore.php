<?php
/**
 * Plugin Name: bitcoincore
 * Description: Основной плагин для работы сайта
 * Author:      werewolff_
 * Version:     1.0
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
//
define('BTCPLUGIN_VERSION', '4.1.3');
define('BTCPLUGIN__DIR', plugin_dir_path(__FILE__));
//
define('BTCPLG_TBL_BLOCKCHAINS', 'btccore_blockchains');
define('BTCPLG_TBL_BLOCKCHAIN', 'btccore_blockchain');
define('BTCPLG_TBL_CATEGORIES', 'btccore_categories');
define('BTCPLG_TBL_VERSIONS', 'btccore_versions');
define('BTCPLG_TBL_METHODS', 'btccore_methods');
define('BTCPLG_TBL_METHODS_VERSIONS', 'btccore_methods_versions');
//
define('BTCPLG_META_TITLE', '_aioseop_title');
define('BTCPLG_META_DESC', '_aioseop_description');
//
define('BTCPLG_SHORTCODE_VERSION', 'bitcoin_version');
define('BTCPLG_SHORTCODE_BLOCKCHAIN', 'bitcoin_blockchain');
require_once(BTCPLUGIN__DIR . 'class.bitcoincore.php');

add_action('init', array('Bitcoincore', 'init'));
if (is_admin()) {
    require_once(BTCPLUGIN__DIR . 'class.bitcoincore-admin.php');
    add_action('init', array('Bitcoincore_Admin', 'admin_init'));
}

// активация
register_activation_hook(__FILE__, array('Bitcoincore', 'plugin_activation'));

//shortcodes
add_shortcode(BTCPLG_SHORTCODE_VERSION, array('Bitcoincore', 'shortcode_version'));
add_shortcode(BTCPLG_SHORTCODE_BLOCKCHAIN, array('Bitcoincore', 'shortcode_blockchain'));

//widgets
require_once (BTCPLUGIN__DIR . 'class.bitcoincore-widget-versions.php');
require_once (BTCPLUGIN__DIR . 'class.bitcoincore-widget-menu.php');
add_action('widgets_init', array('Bitcoincore_Versions_Widget', 'register_widget'));
add_action('widgets_init', array('Bitcoincore_Menu_Widget', 'register_widget'));
//blocks
require_once (BTCPLUGIN__DIR . 'block-bitcoincore/index.php');