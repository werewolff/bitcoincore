<?php
function block_bitcoincore_server_side_render(){

    $content = Bitcoincore::render_main_table();
    return $content;
}

function bitcoincore_register_block()
{
    // automatically load dependencies and version
    $asset_file = include(plugin_dir_path(__FILE__) . 'build/index.asset.php');

    wp_register_script(
        'block-bitcoincore',
        plugins_url('build/index.js', __FILE__),
        $asset_file['dependencies'],
        $asset_file['version']
    );

    wp_register_style(
        'block-bitcoincore-editor',
        plugins_url( 'src/editor.css', __FILE__ ),
        array( 'wp-edit-blocks' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'src/editor.css' )
    );

    wp_register_style(
        'block-bitcoincore-style',
        plugins_url( 'src/style.css', __FILE__ ),
        array( ),
        filemtime( plugin_dir_path( __FILE__ ) . 'src/style.css' )
    );

    register_block_type('bitcoincore/block-bitcoincore', array(
        'style' => 'block-bitcoincore-style',
        'editor_style' => 'block-bitcoincore-editor',
        'editor_script' => 'block-bitcoincore',
        'render_callback' => 'block_bitcoincore_server_side_render'
    ));
}

add_action('init', 'bitcoincore_register_block');