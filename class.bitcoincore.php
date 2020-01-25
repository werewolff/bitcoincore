<?php

class Bitcoincore
{
    private static $initiated = false;

    public function init()
    {
        if (!self::$initiated) {
            self::init_hooks();
        }

    }

    public static function init_hooks()
    {
        self::$initiated = true;
        add_action('wp_enqueue_scripts', array('Bitcoincore', 'register_assets'));
    }

    public static function register_assets()
    {
        wp_register_script(
            'bitcoincore-js',
            plugins_url('/assets/bitcoincore-plg.js', __FILE__),
            array('jquery'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/bitcoincore-plg.js')
        );

        wp_register_style(
            'bitcoincore-css',
            plugins_url('/assets/bitcoincore-plg.css', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'assets/bitcoincore-plg.css')
        );
        wp_enqueue_style('bitcoincore-css');
        wp_enqueue_script('bitcoincore-js');
    }

    public static function render_main_table()
    {
        $versions = self::get_data(BTCPLG_TBL_VERSIONS);
        $categories = self::get_data(BTCPLG_TBL_CATEGORIES);
        $methods = self::get_data(BTCPLG_TBL_METHODS);
        $view_args = compact('versions', 'categories', 'methods');

        self::view('front-table', $view_args);
    }

    public static function shortcode_version($atts)
    {
        global $wpdb;
        $tbl_mv = $wpdb->prefix . BTCPLG_TBL_METHODS_VERSIONS;
        $tbl_m = $wpdb->prefix . BTCPLG_TBL_METHODS;
        $atts = shortcode_atts(array(
            'id' => '0',
        ), $atts);
        $categories = self::get_data(BTCPLG_TBL_CATEGORIES);
        $methods = $wpdb->get_results(
            "SELECT {$tbl_m}.name, {$tbl_mv}.category_id, {$tbl_mv}.page_id FROM {$tbl_mv}
             LEFT JOIN {$tbl_m} ON {$tbl_mv}.method_id = {$tbl_m}.id
             WHERE version_id = {$atts['id']}
             ");
        $methods_column_category_id = array_column($methods, 'category_id');
        $content = '<div class="versions">';
        foreach ($categories AS $category) {
            if (!in_array($category->id, $methods_column_category_id))
                continue;
            $content .= '<h3>' . $category->name . '</h3>';
            foreach ($methods AS $method) {
                if ($category->id == $method->category_id) {
                    $version_desc = apply_filters('the_content', get_post_field('post_content', $method->page_id, 'display'));
                    $content .= '<dt><a href="' . get_page_link($method->page_id) . '">' . $method->name . '</a></dt>';
                    $content .= '<dd class="text-break">' . $version_desc . '</dd>';
                }
            }
        }
        $content .= '</div>';
        return $content;
    }

    /**
     * Get all data from table database
     *
     * @param $tblname
     * @return array|null|object
     */
    public static function get_data($tblname)
    {
        global $wpdb;
        if ($tblname == BTCPLG_TBL_METHODS) {
            $tbl_m = $wpdb->prefix . BTCPLG_TBL_METHODS;
            $tbl_mv = $wpdb->prefix . BTCPLG_TBL_METHODS_VERSIONS;
            $tbl_v = $wpdb->prefix . BTCPLG_TBL_VERSIONS;
            $tbl_c = $wpdb->prefix . BTCPLG_TBL_CATEGORIES;
            $sql = "SELECT
    {$tbl_m}.id,
    {$tbl_m}.name,
    {$tbl_c}.id AS category_id,
    GROUP_CONCAT(
        DISTINCT {$tbl_mv}.page_id
    ORDER BY
        {$tbl_v}.id
    ASC SEPARATOR
        ';'
    ) AS page_id,
    GROUP_CONCAT(
        DISTINCT {$tbl_v}.id
    ORDER BY
        {$tbl_v}.id
    ASC SEPARATOR
        ';'
    ) AS version_id
FROM
    {$tbl_mv}
LEFT JOIN {$tbl_v} ON {$tbl_mv}.version_id = {$tbl_v}.id
LEFT JOIN {$tbl_m} ON {$tbl_mv}.method_id = {$tbl_m}.id
LEFT JOIN {$tbl_c} ON {$tbl_mv}.category_id = {$tbl_c}.id
GROUP BY {$tbl_c}.name, {$tbl_m}.name
ORDER BY {$tbl_c}.name, {$tbl_m}.name ASC";
            return $wpdb->get_results($sql);
        } else {
            $tblname = $wpdb->prefix . $tblname;
            return $wpdb->get_results("SELECT * FROM $tblname ORDER BY name ASC");
        }
    }

    /**
     * Displays the specified file
     *
     * @param $name
     * @param array $args
     */
    public static function view($name, array $args = array())
    {
        extract($args, EXTR_OVERWRITE);
        $file = BTCPLUGIN__DIR . 'views/' . $name . '.php';
        include($file);
    }

    public function plugin_activation()
    {
        global $wpdb;

        $table_name_categories = $wpdb->prefix . BTCPLG_TBL_CATEGORIES;
        $table_name_versions = $wpdb->prefix . BTCPLG_TBL_VERSIONS;
        $table_name_methods = $wpdb->prefix . BTCPLG_TBL_METHODS;
        $table_name_methods_versions = $wpdb->prefix . BTCPLG_TBL_METHODS_VERSIONS;
        $table_name_posts = $wpdb->posts;
        $charset_collate = $wpdb->get_charset_collate();
        if ($wpdb->get_var("show tables like '$table_name_methods'") != $table_name_methods) {

            $sql = "CREATE TABLE $table_name_categories (
	  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	  name varchar(200) NOT NULL,
	  PRIMARY KEY (ID, NAME)
	) $charset_collate; ";
            //
            $sql .= "CREATE TABLE $table_name_versions (
	  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	  name varchar(200) NOT NULL,
	  page_id bigint(20) UNSIGNED NOT NULL,
	  PRIMARY KEY (ID, NAME),
	  FOREIGN KEY (page_id) REFERENCES $table_name_posts (ID) ON DELETE CASCADE ON UPDATE CASCADE
	) $charset_collate; ";
            //
            $sql .= "CREATE TABLE $table_name_methods (
	  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	  name varchar(200) NOT NULL,
	  PRIMARY KEY (ID, NAME)
	) $charset_collate; ";
            //
            $sql .= "CREATE TABLE $table_name_methods_versions (
        method_id bigint(20) UNSIGNED NOT NULL,
        version_id bigint(20) UNSIGNED NOT NULL,
        category_id bigint(20) UNSIGNED NOT NULL,
        page_id bigint(20) UNSIGNED NOT NULL,
	  FOREIGN KEY (method_id) REFERENCES $table_name_methods (id) ON DELETE CASCADE ON UPDATE CASCADE,
	  FOREIGN KEY (version_id) REFERENCES $table_name_versions (id) ON DELETE CASCADE ON UPDATE CASCADE,
	  FOREIGN KEY (category_id) REFERENCES $table_name_categories (id) ON DELETE CASCADE ON UPDATE CASCADE,
	  FOREIGN KEY (page_id) REFERENCES $table_name_posts (ID) ON DELETE CASCADE ON UPDATE CASCADE
	) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

        }
    }
}