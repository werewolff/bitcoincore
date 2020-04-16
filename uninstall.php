<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// delete all pages blockchains
$bitcoincore_posts = get_posts(array('post_type' => 'bitcoincore', 'numberposts' => -1));
foreach ($bitcoincore_posts as $post) {
    wp_delete_post($post->ID, true);
}
// drop a tables of plugin
global $wpdb;
$prefix = $wpdb->prefix;

$tables = array(
    'btccore_methods_versions',
    'btccore_versions',
    'btccore_methods',
    'btccore_categories',
    'btccore_blockchains'
);
foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$prefix}{$table}");
}
