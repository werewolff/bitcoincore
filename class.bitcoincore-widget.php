<?php

class Bitcoincore_Main_Widget extends WP_Widget
{
    function __construct()
    {
        parent::__construct('bitcoincore_widget', __('Bitcoincore widget', 'wpb_widget_domain'), array(
            'description' => __('Sample widget based on WPBeginner Tutorial', 'wpb_widget_domain')
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
        $title = apply_filters('widget_title', $instance['title']);

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        echo __('Hello, World!', 'text_domain');
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
        wp_enqueue_style('bitcoincore', plugins_url('/assets/bitcoincore-plg-widget.css', __FILE__));
        wp_enqueue_script('bitcoincore', plugins_url('/assets/bitcoincore-plg-widget.js', __FILE__), array('jquery'));
    }

    public static function register_widget()
    {
        register_widget('Bitcoincore_Main_Widget');
    }

}