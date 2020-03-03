<?php

class Bitcoincore_Versions_Widget extends WP_Widget
{
    function __construct()
    {
        parent::__construct('bitcoincore_widget_versions', __('Bitcoincore versions', 'wpb_widget_domain'), array(
            'description' => __('Show all versions and support current method', 'wpb_widget_domain')
        ));

        if (is_active_widget(false, false, $this->id_base) || is_customize_preview()) {
            add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        }
    }

    /**
     * Вывод виджета во Фронт-энде
     *
     * @param array $args аргументы виджета.
     * @param array $instance сохраненные данные из настроек
     */
    function widget($args, $instance)
    {
        global $post, $wpdb;
        $title = apply_filters('widget_title', $instance['title']);
        preg_match('/\w+/', $_SERVER['REQUEST_URI'], $blockchain_name);
        $blockchain_name = $blockchain_name[0];
        $blockchain_id = $wpdb->get_var("SELECT id FROM ".$wpdb->prefix. BTCPLG_TBL_BLOCKCHAINS." WHERE name = '{$blockchain_name}'");
        if(!isset($blockchain_id))
            return;
        $versions = Bitcoincore::get_data(BTCPLG_TBL_VERSIONS, $blockchain_id);
        $content = array();
        $tbl_mv = $wpdb->prefix . BTCPLG_TBL_METHODS_VERSIONS;
        $is_method = $wpdb->get_var("SELECT COUNT(page_id) FROM $tbl_mv WHERE page_id = $post->ID");
        foreach ($versions as $version) {
            $page_url = get_page_link($version->page_id);
            $version_title = $version->name;
            if ($is_method) {
                $version_id = intval($version->id);
                $method_id = $wpdb->get_var("SELECT method_id FROM $tbl_mv WHERE page_id = $post->ID");
                $method_support = $wpdb->get_var("SELECT COUNT(method_id) FROM $tbl_mv WHERE method_id = $method_id AND version_id = $version_id");
                $content[] = '<dt class="col-6 pl-5"><a href="' . $page_url . '">' . $version_title . '</a>';
                if ($method_support) {
                    $svg_check = file_get_contents((BTCPLUGIN__DIR . 'assets/img/check.svg'));
                    $content[] = '<dd class="col-6 text-success">' . $svg_check . '</dd>';

                } else {
                    $svg_times = file_get_contents(BTCPLUGIN__DIR . 'assets/img/times.svg');
                    $content[] = '<dd class="col-6 text-danger">' . $svg_times . '</dd>';
                }
            } else
                $content[] = '<dt class="col-12 text-center mb-2"><a href="' . $page_url . '">' . $version_title . '</a>';
        }
        $content = implode('', $content);
        $content_block = '<dl class="row">' . $content . '</dl>';

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        echo $content_block;
        echo $args['after_widget'];
    }

    /**
     * Админ-часть виджета
     *
     * @param array $instance сохраненные данные из настроек
     * @return string|void
     */
    function form($instance)
    {
        $title = @ $instance['title'] ?: 'Заголовок по умолчанию';

        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    /**
     * Сохранение настроек виджета. Здесь данные должны быть очищены и возвращены для сохранения их в базу данных.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance новые настройки
     * @param array $old_instance предыдущие настройки
     *
     * @return array данные которые будут сохранены
     */
    function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';

        return $instance;
    }


    public function register_assets()
    {
        wp_register_script(
            'bitcoincore-widget-versions-script',
            plugins_url('/assets/js/bitcoincore-plg-widget-versions.js', __FILE__),
            array('jquery'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/bitcoincore-plg-widget-versions.js')
        );
        wp_register_style(
            'bitcoincore-widget-versions-style',
            plugins_url('/assets/css/bitcoincore-plg-widget-versions.css', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/bitcoincore-plg-widget-versions.css')
        );

        wp_enqueue_style('bitcoincore-widget-versions-style', plugins_url('/assets/css/bitcoincore-plg-widget-versions.css', __FILE__));
        wp_enqueue_script('bitcoincore-widget-versions-script', plugins_url('/assets/js/bitcoincore-plg-widget-versions.js', __FILE__), array('jquery'));
    }

    public static function register_widget()
    {
        register_widget('Bitcoincore_Versions_Widget');
    }

}