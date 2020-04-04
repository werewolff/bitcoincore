<?php

class Bitcoincore_Menu_Widget extends WP_Widget
{
    function __construct()
    {
        parent::__construct('bitcoincore_widget_menu', __('Bitcoincore menu', 'wpb_widget_domain'), array(
            'description' => __('Menu btc pages', 'wpb_widget_domain')
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
        $tbl_mv = $wpdb->prefix . BTCPLG_TBL_METHODS_VERSIONS;
        $tbl_m = $wpdb->prefix . BTCPLG_TBL_METHODS;
        $blockchains_list = array();
        $blockchains = Bitcoincore::get_data(BTCPLG_TBL_BLOCKCHAINS);
        $btn_expand = '<span class="btn-expand dashicons dashicons-arrow-right"></span>';
        $btn_expanded = '<span class="btn-expand btn-expanded dashicons dashicons-arrow-right"></span>';
        foreach ($blockchains as $blockchain) {
            $versions_list = array();
            $versions = Bitcoincore::get_data(BTCPLG_TBL_VERSIONS, $blockchain->id);
            foreach ($versions as $version) {
                $categories_list = array();
                $categories = Bitcoincore::get_data(BTCPLG_TBL_CATEGORIES);
                foreach ($categories as $category) {
                    $methods_list = array();
                    $methods = $wpdb->get_results("SELECT name, page_id 
                    FROM {$tbl_mv} LEFT JOIN {$tbl_m} ON {$tbl_mv}.method_id = {$tbl_m}.id 
                    WHERE version_id = {$version->id} AND category_id = {$category->id}");
                    foreach ($methods as $method) {
                        $method_page_link = get_page_link($method->page_id);
                        $methods_list[] = '<li><ul><li><a href="' . $method_page_link . '">' . $method->name . '</a></li></ul></li>';
                    }
                    $methods_list = implode('', $methods_list);
                    if (!empty($methods_list))
                        $categories_list[] = '<li><ul><li>' . $btn_expand . $category->name . '</li>' . $methods_list . '</ul></li>';
                }
                $categories_list = implode('', $categories_list);
                if (!empty($categories_list)) {
                    $version_page_link = get_page_link($version->page_id);
                    $versions_list[] = '<li><ul><li>' . $btn_expand . '<a href="' . $version_page_link . '">' . $version->name . '</a></li>' . $categories_list . '</ul></li>';
                }
            }
            $versions_list = implode('', $versions_list);
            if (!empty($versions_list)) {
                $blockchain_page_link = get_page_link($blockchain->page_id);
                $blockchains_list[] = '<ul><li>' . $btn_expanded . '<a href="' . $blockchain_page_link . '">' . $blockchain->name . '</a></li>' . $versions_list . '</ul>';
            }
        }

        $content = implode('', $blockchains_list);
        $content_block = '<div>' . $content . '</div>';

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
            'bitcoincore-widget-menu-script',
            plugins_url('/assets/js/bitcoincore-plg-widget-menu.js', __FILE__),
            array('jquery'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/bitcoincore-plg-widget-menu.js')
        );
        wp_register_style(
            'bitcoincore-widget-menu-style',
            plugins_url('/assets/css/bitcoincore-plg-widget-menu.css', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/bitcoincore-plg-widget-menu.css')
        );

        wp_enqueue_style('bitcoincore-widget-menu-style');
        wp_enqueue_script('bitcoincore-widget-menu-script');
    }

    public static function register_widget()
    {
        register_widget('Bitcoincore_Menu_Widget');
    }

}