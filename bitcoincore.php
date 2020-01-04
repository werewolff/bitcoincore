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
define('BTCPLG_TBL_CATEGORIES', 'btccore_categories');
define('BTCPLG_TBL_VERSIONS', 'btccore_versions');
define('BTCPLG_TBL_METHODS', 'btccore_methods');
define('BTCPLG_TBL_METHODS_VERSIONS', 'btccore_methods_versions');


require_once(BTCPLUGIN__DIR . 'class.bitcoincore.php');

add_action('init', array('Bitcoincore', 'init'));

if (is_admin()) {
    require_once(BTCPLUGIN__DIR . 'class.bitcoincore-admin.php');
    add_action('init', array('Bitcoincore_Admin', 'init'));
}

// активация
register_activation_hook(__FILE__, array('Bitcoincore', 'plugin_activation'));
