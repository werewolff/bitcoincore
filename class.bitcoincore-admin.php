<?php

class Bitcoincore_Admin extends Bitcoincore
{
    private static $initiated = false;
    private static $current_table;
    private static $current_blockchain_id;

    /**
     * Entry point
     */
    public static function admin_init()
    {
        if (!self::$initiated) {
            self::init_hooks();
        }
    }

    /**
     * Initial hooks
     */
    public static function init_hooks()
    {
        self::$initiated = true;
        //actions
        add_action('admin_menu', array('Bitcoincore_Admin', 'admin_menu'));
        add_action('admin_enqueue_scripts', array('Bitcoincore_Admin', 'register_assets'));
        //filters
        add_filter('btc_description', array('Bitcoincore_Admin', 'filter_description'));
    }

    /**
     * Register assets(css,js)
     */
    public static function register_assets()
    {
        if (preg_match('/(btc)/', get_current_screen()->id)) { // Только для страниц плагина
            wp_register_script(
                'bootstrap',
                plugins_url('/assets/bootstrap/bootstrap.min.js', __FILE__),
                array(),
                filemtime(plugin_dir_path(__FILE__) . 'assets/bootstrap/bootstrap.min.js')
            );
            wp_register_script(
                'jquery-sortable',
                plugins_url('/assets/jquery-sortable/jquery-sortable.min.js', __FILE__),
                array(),
                filemtime(plugin_dir_path(__FILE__) . 'assets/jquery-sortable/jquery-sortable.min.js')
            );

            wp_register_script(
                'bitcoincore-admin-js',
                plugins_url('/assets/js/bitcoincore-plg-admin.js', __FILE__),
                array('jquery', 'jquery-sortable', 'bootstrap'), // Jquery-ui включен только sortable!!!
                filemtime(plugin_dir_path(__FILE__) . 'assets/js/bitcoincore-plg-admin.js')
            );
            wp_register_style(
                'bs',
                plugins_url('/assets/bootstrap/bootstrap.min.css', __FILE__),
                array(),
                filemtime(plugin_dir_path(__FILE__) . 'assets/bootstrap/bootstrap.min.css')
            );

            wp_register_style(
                'bitcoincore-admin-css',
                plugins_url('/assets/css/bitcoincore-plg-admin.css', __FILE__),
                array('bs'),
                filemtime(plugin_dir_path(__FILE__) . 'assets/css/bitcoincore-plg-admin.css')
            );
            wp_enqueue_style('bitcoincore-admin-css');
            wp_enqueue_script('bitcoincore-admin-js');
        }
    }

    /**
     * Filter desc before insert into db
     * @param $desc
     * @return string
     */
    public static function filter_description($desc)
    {
        $desc = htmlentities($desc, 0, '', false);
        $desc = strtr($desc, array('&lt;br /&gt;' => '<br>', '&lt;br&gt;' => '<br>', '&lt;p&gt;' => '<p>', '&lt;/p&gt;' => '</p>'));
        return $desc;
    }

    /**
     * Method generating meta-data and return array {
     * BTCPLG_META_TITLE => $title,
     * BTCPLG_META_DESC => $desctiption (maxlenght = 160),
     * 'btc_page_type' => method || version || blockchain
     * }
     *
     * @param $title
     * @param $desctiption
     * @param string $page_type
     * @return array
     */
    public static function generate_meta($title, $desctiption, $page_type = 'method')
    {
        $meta_title = $title;
        $meta_desc = substr($desctiption, 0, 160);
        return array(
            BTCPLG_META_TITLE => $meta_title,
            BTCPLG_META_DESC => $meta_desc,
            'btc_page_type' => $page_type
        );
    }

    public static function change_meta_title_children($parent_id, $prev_value, $next_value)
    {
        $children = get_posts(array(
            'post_parent' => $parent_id,
            'numberposts' => -1,
            'post_type' => 'bitcoincore'
        ));
        if (count($children) > 0) {
            foreach ($children AS $child) {
                $meta_title = get_post_meta($child->ID, BTCPLG_META_TITLE, true);
                $meta_title = str_replace($prev_value, $next_value, $meta_title);
                update_post_meta($child->ID, BTCPLG_META_TITLE, $meta_title);
                self::change_meta_title_children($child->ID, $prev_value, $next_value); //рекурсия
            }
        }
    }

    /**
     * @param $method_name
     * @param $method_id
     * @param $description
     * @param $version_name
     * @param $blockchain_name
     * @param $parent_id
     * @param $version_id
     * @param $category_id
     * @return array
     */
    public static function create_version_for_method($method_name, $method_id, $description, $version_name, $blockchain_name, $parent_id, $version_id, $category_id)
    {
        global $wpdb;

        $description = apply_filters('btc_description', $description); // фильтруем (html сущности)
        //создаем страницу
        $page_id = wp_insert_post(array(
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_author' => get_current_user_id(),
            'post_content' => $description,
            'post_name' => $method_name,
            'post_status' => 'publish',
            'post_title' => $method_name,
            'post_type' => 'bitcoincore',
            'post_parent' => $parent_id,
            'meta_input' => self::generate_meta($method_name . ' ' . $version_name . ' ' . $blockchain_name, $description)
        ));
        // Записываем данные в БД
        $result = $wpdb->insert($wpdb->prefix . BTCPLG_TBL_METHODS_VERSIONS, array(
            'method_id' => $method_id,
            'version_id' => $version_id,
            'category_id' => $category_id,
            'page_id' => $page_id
        ), array('%d', '%d', '%d', '%d'));

        return array(
            'result_insert_post' => $page_id,
            'result_insert_db' => $result
        );
    }

    /**
     * Creates version in database and create page with SHORTCODE
     *
     * @param $name
     * @param $blockchain_id
     */
    public static function create_version($name, $blockchain_id)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $version_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $prefix . BTCPLG_TBL_VERSIONS . " WHERE name = %s AND blockchain_id = %d", $name, $blockchain_id));
        if (!isset($version_id)) { // Если не существует версии, создаем
            $blockchain = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $prefix . BTCPLG_TBL_BLOCKCHAINS . " WHERE id = %d", $blockchain_id))[0];
            //создаем страницу
            $page_id = wp_insert_post(array(
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_author' => get_current_user_id(),
                'post_name' => is_numeric($name) ? 'version-' . $name : $name,
                'post_status' => 'publish',
                'post_title' => $name,
                'post_parent' => $blockchain->page_id,
                'post_type' => 'bitcoincore',
                'meta_input' => self::generate_meta($name . ' ' . $blockchain->name, '', 'version')
            ));
            $max_order = $wpdb->get_var("SELECT MAX(`order`) FROM " . $prefix . BTCPLG_TBL_VERSIONS);
            $order = (int)$max_order + 1;
            $wpdb->insert($prefix . BTCPLG_TBL_VERSIONS, array('name' => $name, 'page_id' => $page_id, 'blockchain_id' => $blockchain_id, 'order' => $order));
            $version_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $prefix . BTCPLG_TBL_VERSIONS . " WHERE name = %s AND blockchain_id = %d", $name, $blockchain_id));
            wp_update_post(array(
                'ID' => $page_id,
                'post_content' => '<!-- wp:shortcode -->[' . BTCPLG_SHORTCODE_VERSION . ' id="' . $version_id . '"]<!-- /wp:shortcode -->',
            ));
        }
    }

    /**
     * Creates  blockchain
     * @param $name
     */
    public static function create_blockchain($name)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $blockchain_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $prefix . BTCPLG_TBL_BLOCKCHAINS . " WHERE name = %s", $name));
        if (!isset($blockchain_id)) { // Если не существует блокчейна, создаем
            //создаем страницу
            $page_id = wp_insert_post(array(
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_author' => get_current_user_id(),
                'post_name' => $name,
                'post_status' => 'publish',
                'post_title' => $name,
                'post_type' => 'bitcoincore',
                'meta_input' => self::generate_meta($name, '', 'blockchain')
            ));
            $wpdb->insert($prefix . BTCPLG_TBL_BLOCKCHAINS, array('name' => $name, 'page_id' => $page_id));
            $blockchain_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $prefix . BTCPLG_TBL_BLOCKCHAINS . " WHERE name = %s", $name));
            wp_update_post(array(
                'ID' => $page_id,
                'post_content' => '<!-- wp:shortcode -->[' . BTCPLG_SHORTCODE_BLOCKCHAIN . ' id="' . $blockchain_id . '"]<!-- /wp:shortcode -->',
            ));
        }
    }

    /**
     * Adds a specific entity.
     * @param $type
     */
    public static function action_add($type)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        // VERSION
        if ($type === 'version') {
            if (isset($_POST['name']) && !empty($_POST['name']) && isset($_POST['blockchain_id'])) {
                $name = $_POST['name'];
                $blockchain_id = $_POST['blockchain_id'];
                self::create_version($name, $blockchain_id);
            }
        }
        // CATEGORY
        if ($type === 'category') {
            if (isset($_POST['name']) && !empty($_POST['name'])) {
                $name = $_POST['name'];
                $category_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $prefix . BTCPLG_TBL_CATEGORIES . " WHERE name = %s", $name));
                if (!isset($category_id)) {
                    $wpdb->insert($prefix . BTCPLG_TBL_CATEGORIES, array('name' => $name));
                }
            }
        }
        // METHOD
        if ($type === 'method') {
            if (isset($_POST['name']) && isset($_POST['category_id'])) {
                $method_name = $_POST['name'];
                $method_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $prefix . BTCPLG_TBL_METHODS . " WHERE name = %s", $method_name));
                if (!isset($method_id)) {
                    $wpdb->insert($prefix . BTCPLG_TBL_METHODS, array('name' => $method_name)); //Создаем сам метод
                    $method_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $prefix . BTCPLG_TBL_METHODS . " WHERE name = %s", $method_name));
                }
                $versions = parent::get_data(BTCPLG_TBL_VERSIONS, self::$current_blockchain_id);
                foreach ($versions as $version) {
                    if (isset($_POST['versions']) && $_POST['versions'][$version->id] == true) {
                        $version_desc = $_POST['versions_desc'][$version->id];
                        $category_id = $_POST['category_id'];
                        $parent_id = $version->page_id;
                        self::create_version_for_method($method_name, $method_id, $version_desc, $version->name, $version->blockchain_name, $parent_id, $version->id, $category_id);
                    }
                }
            }
        }
        // BLOCKCHAIN
        if ($type === 'blockchain') {
            $name = $_POST['name'];
            self::create_blockchain($name);
            // Перезагрузка чтобы блокчейн отобразился в меню
            echo '<script type="application/javascript">location.reload()</script>';
        }
    }

    /**
     * Сhanges a specific entity.
     * @param $type
     */
    public static function action_edit($type)
    {
        global $wpdb;
        $prefix = $wpdb->prefix; // Префикс для таблиц

        if (!isset($_POST['id']) && !isset($_POST['name']) && empty($_POST['id']) && empty($_POST['name'])) {
            return;
        }
        $id = $_POST['id']; // ID
        $name = $_POST['name']; // Имя
        $prev_name = $_POST['prev_name']; // Предудущее имя

        //Версия
        if ($type === 'version' && $name !== $prev_name) {
            $version = $page_id = $wpdb->get_results("SELECT page_id FROM " . $prefix . BTCPLG_TBL_VERSIONS . " WHERE id = {$id}")[0];
            wp_update_post(array(
                'ID' => $version->page_id,
                'post_name' => $name,
                'post_title' => $name
            ));
            // Обновляем мета
            $meta_title = get_post_meta($version->page_id, BTCPLG_META_TITLE, true);
            $meta_title = str_replace($prev_name, $name, $meta_title);
            update_post_meta($version->page_id, BTCPLG_META_TITLE, $meta_title);
            // изменяем мета у дочерних страниц
            self::change_meta_title_children($version->page_id, $prev_name, $name);
            //
            $wpdb->update($prefix . BTCPLG_TBL_VERSIONS, array('name' => $name), array('id' => $id));

        }
        // Категория
        if ($type === 'category' && $name !== $prev_name) {
            $wpdb->update($prefix . BTCPLG_TBL_CATEGORIES, array('name' => $name), array('id' => $id)); // Для категорий
        }

        // Метод
        if ($type === 'method') {
            $prev_category_id = $_POST['prev_category_id']; // Предыдущая категория
            $category_id = $_POST['category_id']; // Текущаяя категория
            $prev_versions = explode(';', $_POST['prev_versions']); // Предыдушие версии

            if ($prev_name !== $name) // Если изменилось имя записываем в базу
                $wpdb->update($prefix . BTCPLG_TBL_METHODS, array('name' => $name), array('id' => $id));
            $versions = parent::get_data(BTCPLG_TBL_VERSIONS, self::$current_blockchain_id);
            foreach ($versions AS $version) {
                if ($_POST['versions'][$version->id] == true && in_array($version->id, $prev_versions)) { // Если версия активирована и она существовала в предыдущей
                    $prev_version_desc = $_POST['prev_versions_desc'][$version->id];
                    $version_desc = $_POST['versions_desc'][$version->id];
                    $page_id = $wpdb->get_results(
                        "SELECT page_id FROM "
                        . $prefix . BTCPLG_TBL_METHODS_VERSIONS .
                        " WHERE method_id = $id AND
                                version_id = $version->id AND
                                category_id = $prev_category_id");
                    if ($prev_version_desc !== $version_desc) { // Если описание метода изменилось
                        $version_desc = apply_filters('btc_description', $version_desc); // фильтруем (html сущности)
                        //Обновляем страницу
                        wp_update_post(array(
                            'ID' => $page_id[0]->page_id,
                            'post_author' => get_current_user_id(),
                            'post_content' => $version_desc,
                            'post_name' => $name,
                            'post_title' => $name,
                        ));
                        $meta = self::generate_meta($name . ' ' . $version->name, $version_desc);
                        update_post_meta($page_id[0]->page_id, BTCPLG_META_TITLE, $meta[BTCPLG_META_TITLE]);
                        update_post_meta($page_id[0]->page_id, BTCPLG_META_DESC, $meta[BTCPLG_META_DESC]);
                    } elseif ($prev_name !== $name) { // Если менялось имя обновляем название страницы
                        wp_update_post(array(
                            'ID' => $page_id[0]->page_id,
                            'post_author' => get_current_user_id(),
                            'post_name' => $name,
                            'post_title' => $name,
                        ));
                        $meta = self::generate_meta($name . ' ' . $version->name, $version_desc);
                        update_post_meta($page_id[0]->page_id, BTCPLG_META_TITLE, $meta[BTCPLG_META_TITLE]);
                        update_post_meta($page_id[0]->page_id, BTCPLG_META_DESC, $meta[BTCPLG_META_DESC]);
                    }
                    continue;
                } elseif ($_POST['versions'][$version->id] == true && !in_array($version->id, $prev_versions)) { // Если версия активирована и она не существовала раньше
                    $version_desc = $_POST['versions_desc'][$version->id];
                    $parent_id = $version->page_id;
                    $version_desc = apply_filters('btc_description', $version_desc); // фильтруем (html сущности)
                    //создаем страницу
                    $page_id = wp_insert_post(array(
                        'comment_status' => 'closed',
                        'ping_status' => 'closed',
                        'post_author' => get_current_user_id(),
                        'post_content' => $version_desc,
                        'post_name' => $name,
                        'post_status' => 'publish',
                        'post_title' => $name,
                        'post_type' => 'bitcoincore',
                        'post_parent' => $parent_id,
                        'meta_input' => self::generate_meta($name . ' ' . $version->name, $version_desc)
                    ));
                    // Записываем данные в БД
                    $wpdb->insert($prefix . BTCPLG_TBL_METHODS_VERSIONS, array(
                        'method_id' => $id,
                        'version_id' => $version->id,
                        'category_id' => $category_id,
                        'page_id' => $page_id
                    ), array('%d', '%d', '%d', '%d'));
                    continue;
                } elseif ($_POST['versions'][$version->id] !== true && in_array($version->id, $prev_versions)) { // Деактивация версии
                    $page_id = $wpdb->get_results(
                        "SELECT page_id FROM "
                        . $prefix . BTCPLG_TBL_METHODS_VERSIONS .
                        " WHERE method_id = $id AND
                                version_id = $version->id AND
                                category_id = $prev_category_id");
                    wp_delete_post($page_id[0]->page_id, true);
                    continue;
                }
            }
            if ($prev_category_id !== $category_id) { // Изменение категории
                $wpdb->update(
                    $prefix . BTCPLG_TBL_METHODS_VERSIONS,
                    array('category_id' => $category_id),
                    array('method_id' => $id),
                    array('%d'),
                    array('%d')
                );
            }
        }

        if ($type === 'blockchain' && $prev_name !== $name) {
            $blockchain = $wpdb->get_results("SELECT page_id FROM " . $prefix . BTCPLG_TBL_BLOCKCHAINS . " WHERE id = {$id}")[0];
            wp_update_post(array(
                'ID' => $blockchain->page_id,
                'post_name' => $name,
                'post_title' => $name
            ));
            // Обновляем мета
            update_post_meta($blockchain->page_id, BTCPLG_META_TITLE, $name);
            // изменяем мета у дочерних страниц
            self::change_meta_title_children($blockchain->page_id, $prev_name, $name);
            $wpdb->update($prefix . BTCPLG_TBL_BLOCKCHAINS, array('name' => $name), array('id' => $id));
            // Перезагрузка чтобы изменения отобразились в меню
            echo '<script type="application/javascript">location.reload()</script>';
        }
    }

    /**
     * @param $id
     */
    public static function delete_blockchain($id)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        // Получаем ID страницы блокчейна
        $page_id = $wpdb->get_results("SELECT page_id FROM " . $prefix . BTCPLG_TBL_BLOCKCHAINS . " WHERE id = {$id}");
        // Получаем версии данного блокчейна
        $versions = parent::get_data(BTCPLG_TBL_VERSIONS, $id);
        foreach ($versions as $version) {
            // Получаем страницы методов каждой версии
            $pages_methods = $wpdb->get_results("SELECT page_id FROM " . $prefix . BTCPLG_TBL_METHODS_VERSIONS . " WHERE version_id = {$version->id}");
            foreach ($pages_methods as $page_method) {
                // Удаляем страницы методов
                wp_delete_post($page_method->page_id, true);
            }
            // Удаляем страницы версии
            wp_delete_post($version->page_id, true);
        }
        // Удаляем страницу блокчейна
        wp_delete_post($page_id[0]->page_id, true);
    }

    /**
     * @param $id
     */
    public static function delete_version($id)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $version_page_id = $wpdb->get_var("SELECT page_id FROM " . $prefix . BTCPLG_TBL_VERSIONS . " WHERE id = {$id}");
        $methods_pages_id = $wpdb->get_results("SELECT page_id FROM " . $prefix . BTCPLG_TBL_METHODS_VERSIONS . " WHERE version_id = {$id}");
        // Удаляем страницы с методами данной версии
        foreach ($methods_pages_id as $method_page_id) {
            // Удаляем страницы
            wp_delete_post($method_page_id->page_id, true);
        }
        $wpdb->delete($prefix . BTCPLG_TBL_VERSIONS, array('id' => $id), array('%d'));
        wp_delete_post($version_page_id, true);
    }

    /**
     * @param $id
     */
    public static function delete_category($id)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        //Получаем ID страниц
        $methods_pages_id = $wpdb->get_results("SELECT page_id FROM " . $prefix . BTCPLG_TBL_METHODS_VERSIONS . " WHERE category_id = {$id}");
        // Удаляем страницы с методами в данной категории
        foreach ($methods_pages_id as $method_page_id) {
            // Удаляем страницы
            wp_delete_post($method_page_id->page_id, true);
        }
        // Удаляем саму категорию из базы
        $wpdb->delete($prefix . BTCPLG_TBL_CATEGORIES, array('id' => $id), array('%d'));
    }

    /**
     * @param $id
     * @param $blockchain_id
     * @param null $category_id
     * @param null $version_id
     */
    public static function delete_method($id, $blockchain_id, $category_id = null, $version_id = null)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $table_mv = $prefix . BTCPLG_TBL_METHODS_VERSIONS;
        $table_v = $prefix . BTCPLG_TBL_VERSIONS;
        //Получаем ID страниц
        $sql = "SELECT {$table_mv}.page_id FROM {$table_mv} ";
        $sql .= "LEFT JOIN $table_v ON {$table_v}.blockchain_id = {$blockchain_id} ";
        $sql .= "WHERE method_id = {$id} AND version_id = {$table_v}.id ";
        $sql .= (isset($version_id)) ? "AND version_id = {$version_id} " : '';
        $sql .= (isset($category_id)) ? "AND category_id = {$category_id} " : '';

        $pages_id = $wpdb->get_results($sql);
        foreach ($pages_id as $p_id) {
            // Удаляем страницы
            wp_delete_post($p_id->page_id, true);
        }
        // Кол-во записей с данным методом
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_mv} WHERE method_id = {$id}");
        if ($count == 0) {
            // Удаляем если метод больше не используется в других версиях
            $wpdb->delete($table_mv, array('id' => $id), array('%d'));
        }

    }

    /**
     * Removes a specific entity
     * @param $type
     */
    public
    static function action_delete($type)
    {
        if ($type === 'version') {
            self::delete_version($_POST['id']);
        }

        if ($type === 'category') {
            self::delete_category($_POST['id']);
        }

        if ($type === 'method') {
            self::delete_method($_POST['id'], $_POST['blockchain_id'], $_POST['category_id']);
        }

        if ($type === 'blockchain') {
            self::delete_blockchain($_POST['id']);
        }
    }

    /**
     *
     * Executes a method appropriate to the type
     *
     */
    public
    static function do_action()
    {
        if (!isset($_POST['action']))
            return;
        $action = $_POST['action'];
        // делим действие на тип и на сущность, для которого осуществляется это действие
        $action = explode('_', $action);
        $action_type = $action[0];
        $obj_type = $action[1];
        switch ($action_type) {
            case 'add':
                self::action_add($obj_type);
                break;
            case 'edit':
                self::action_edit($obj_type);
                break;
            case 'delete':
                self::action_delete($obj_type);
                break;
        }
    }

    /**
     * Render page
     */
    public
    static function render()
    {
        $tbl_name = self::$current_table;
        if ($tbl_name == BTCPLG_TBL_BLOCKCHAIN) {
            $blockchain_id = self::$current_blockchain_id;
            $methods = parent::get_data(BTCPLG_TBL_METHODS, self::$current_blockchain_id);
            $categories = parent::get_data(BTCPLG_TBL_CATEGORIES);
            $versions = parent::get_data(BTCPLG_TBL_VERSIONS, self::$current_blockchain_id);
            $view_args = compact('blockchain_id', 'methods', 'categories', 'versions', 'category');
            parent::view('header');
            parent::view('add-bar-blockchain', $view_args);
            parent::view('table-blockchain', $view_args);
        } elseif ($tbl_name == BTCPLG_TBL_BLOCKCHAINS) {
            $blockchains = parent::get_data($tbl_name);
            $view_args = compact('tbl_name', 'blockchains');
            parent::view('header');
            parent::view('list-blockchains', $view_args);
        }
    }

    public
    static function render_blockchains()
    {
        self::$current_table = BTCPLG_TBL_BLOCKCHAINS;
        self::do_action();
        self::render();
    }

    public
    static function render_blockchain()
    {
        global $wpdb;
        $blockchain_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $wpdb->prefix . BTCPLG_TBL_BLOCKCHAINS . " WHERE name = %s", get_admin_page_title()));
        self::$current_table = BTCPLG_TBL_BLOCKCHAIN;
        self::$current_blockchain_id = $blockchain_id;
        self::do_action();
        self::render();
    }

    /**
     * Import from json
     *
     * @param $data
     * @param bool $mode_full_sync
     */
    public static function import($data, $mode_full_sync = false, $change_desc = false)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $tbl_mv = $prefix . BTCPLG_TBL_METHODS_VERSIONS;
        $tbl_m = $prefix . BTCPLG_TBL_METHODS;
        $tbl_v = $prefix . BTCPLG_TBL_VERSIONS;
        $tbl_c = $prefix . BTCPLG_TBL_CATEGORIES;
        $tbl_b = $prefix . BTCPLG_TBL_BLOCKCHAINS;
        $blockchains = (array)parent::get_data(BTCPLG_TBL_BLOCKCHAINS);
        $blockchains_column_name = array_column($blockchains, 'name');
        $categories_in_db = (array)parent::get_data(BTCPLG_TBL_CATEGORIES);
        $categories_column_name = array_column($categories_in_db, 'name');
        $categories_import = array();
        $log = array(
            'delete_b' => 0,
            'delete_v' => 0,
            'delete_c' => 0,
            'delete_m' => 0,
            'delete_mv' => 0,
            'add_b' => 0,
            'add_v' => 0,
            'add_c' => 0,
            'add_m' => 0,
            'add_mv' => 0,
            'change_desc' => 0,
        );
        // Блокчейны
        foreach ($data as $blockchain_name => $versions) {
            if (!in_array(strtolower($blockchain_name), array_map('mb_strtolower', $blockchains_column_name))) { // Если блокчейна нет в базе, то создаем его
                self::create_blockchain($blockchain_name);
                $log['add_b']++;
            }
            $blockchain_id = $wpdb->get_var("SELECT id FROM {$tbl_b} WHERE name = '{$blockchain_name}'");
            $versions_in_db = (array)parent::get_data(BTCPLG_TBL_VERSIONS, $blockchain_id);
            $versions_column_name = array_column($versions_in_db, 'name');
            // Версии
            foreach ($versions as $version_name => $categories) {
                $methods_import = array();
                if (!in_array(strtolower($version_name), array_map('mb_strtolower', $versions_column_name))) { // Если версии нет в базе, то создаем ее
                    self::create_version($version_name, $blockchain_id);
                    $log['add_v']++;
                }
                $current_version = $wpdb->get_results("SELECT * FROM {$tbl_v} WHERE name = '{$version_name}' AND blockchain_id = {$blockchain_id}")[0];
                // Категории
                foreach ($categories as $category_name => $methods) {
                    if (!in_array(strtolower($category_name), array_map('mb_strtolower', $categories_column_name))) { // Если категории нет в базе, то создаем ее
                        $wpdb->insert($tbl_c, array('name' => $category_name));
                        $log['add_c']++;
                    }
                    $category_id = $wpdb->get_var("SELECT id FROM {$tbl_c} WHERE name = '{$category_name}'");
                    $categories_import[] = $category_name;
                    $sql = "SELECT {$tbl_m}.name 
                    FROM {$tbl_mv}
                    LEFT JOIN {$tbl_m} ON {$tbl_mv}.method_id = {$tbl_m}.id 
                    WHERE version_id = {$current_version->id} AND category_id = {$category_id}";
                    $methods_in_db = (array)$wpdb->get_results($sql);
                    $methods_column_name = array_column($methods_in_db, 'name');
                    // Методы
                    foreach ($methods as $method_name => $description) {
                        $methods_import[] = $method_name; // Собираем список импортируемых методов в текущей версии
                        if (!in_array(strtolower($method_name), array_map('mb_strtolower', $methods_column_name))) { // Если метода нет в базе, то создаем его
                            $method_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$tbl_m} WHERE name = %s", $method_name));
                            if (!isset($method_id)) {
                                $wpdb->insert($prefix . BTCPLG_TBL_METHODS, array('name' => $method_name)); //Создаем сам метод
                                $method_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$tbl_m} WHERE name = %s", $method_name));
                                $log['add_m']++;
                            }
                            self::create_version_for_method($method_name, $method_id, $description, $version_name, $blockchain_name, $current_version->page_id, $current_version->id, $category_id);
                            $log['add_mv']++;
                        } else { // Метод существовал раньше в данной версии
                            if ($change_desc) { // Нужно ли изменять описание
                                $method_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$tbl_m} WHERE name = %s", $method_name)); // Получаем ID метода
                                $method_page_id = $wpdb->get_var("SELECT page_id
                                FROM {$tbl_mv} 
                                WHERE method_id = {$method_id} 
                                AND version_id = {$current_version->id} 
                                AND category_id = {$category_id}"); // Получаем ID страницы
                                $prev_desc = get_post_field('post_content', $method_page_id); // Предыдущее описание метода
                                $description = apply_filters('btc_description', $description); // фильтруем (html сущности)
                                if ($prev_desc !== $description) { // Если отличаются
                                    wp_update_post(array(
                                        'ID' => $method_page_id,
                                        'post_content' => $description
                                    )); // Обновляем описание
                                    $log['change_desc']++; // Плюсик в лог
                                }
                            }
                        }
                    }
                }
                // Методы
                if ($mode_full_sync) {
                    $sql = "SELECT {$tbl_m}.name 
                    FROM {$tbl_mv}
                    LEFT JOIN {$tbl_m} ON {$tbl_mv}.method_id = {$tbl_m}.id 
                    WHERE version_id = {$current_version->id}";
                    $methods_in_db = (array)$wpdb->get_results($sql);
                    $methods_import = array_map('mb_strtolower', $methods_import);
                    $methods_column_name = array_map('mb_strtolower', array_column($methods_in_db, 'name'));
                    $methods_diff = array_diff($methods_column_name, $methods_import);
                    foreach ($methods_diff as $method_diff) {
                        $method_id = $wpdb->get_var("SELECT id FROM {$tbl_m} WHERE name = '{$method_diff}'");
                        self::delete_method($method_id, $blockchain_id, null, $current_version->id);
                        $log['delete_mv']++;
                    }
                }
            }
            // Версии
            if ($mode_full_sync) {
                $versions_import = array_map('mb_strtolower', array_keys($versions));
                $versions_column_name = array_map('mb_strtolower', $versions_column_name);
                $versions_diff = array_diff($versions_column_name, $versions_import);
                foreach ($versions_diff as $version_diff) {
                    $id = $wpdb->get_var("SELECT id FROM " . $prefix . BTCPLG_TBL_VERSIONS . " WHERE name = '{$version_diff}' AND blockchain_id = {$blockchain_id}");
                    self::delete_version($id);
                    $log['delete_v']++;
                }
            }

        }
        // Блокчейны
        if ($mode_full_sync) {
            $blockchains_import = array_map('mb_strtolower', array_keys($data));
            $blockchains_column_name = array_map('mb_strtolower', $blockchains_column_name);
            $blockchains_diff = array_diff($blockchains_column_name, $blockchains_import);
            foreach ($blockchains_diff as $blockchain_diff) {
                $id = $wpdb->get_var("SELECT id FROM " . $prefix . BTCPLG_TBL_BLOCKCHAINS . "WHERE name = '{$blockchain_diff}'");
                self::delete_blockchain($id);
                $log['delete_b']++;
            }
        }

        // Категории
        if ($mode_full_sync) {
            $categories_import = array_map('mb_strtolower', $categories_import);
            $categories_column_name = array_map('mb_strtolower', $categories_column_name);
            $categories_diff = array_diff($categories_column_name, $categories_import);
            foreach ($categories_diff as $category_diff) {
                $id = $wpdb->get_var("SELECT id FROM " . $prefix . BTCPLG_TBL_CATEGORIES . " WHERE name = '{$category_diff}'");
                self::delete_category($id);
                $log['delete_c']++;
            }
        }
        // LOG
        printf('<div class="notice notice-success is-dismissible">
                        <p>Добавлено блокчейнов: %s</p>
                        <p>Добавлено версий: %s</p>
                        <p>Добавлено категорий: %s</p>
                        <p>Добавлено методов: %s</p>
                        <p>Добавлено версий к методам: %s</p>
                        <p>Удалено блокчейнов: %s</p>
                        <p>Удалено версий: %s</p>
                        <p>Удалено категорий: %s</p>
                        <p>Удалено методов: %s</p>
                        <p>Удалено версий у методов: %s</p>
                        <p>Изменено описаний: %s</p>
                    </div>',
            $log['add_b'],
            $log['add_v'],
            $log['add_c'],
            $log['add_m'],
            $log['add_mv'],
            $log['delete_b'],
            $log['delete_v'],
            $log['delete_c'],
            $log['delete_m'],
            $log['delete_mv'],
            $log['change_desc']
        );

    }

    /**
     * Render import page
     */
    public static function render_import()
    {
        self::register_assets();
        if (isset($_FILES['btc_import_file'])) {
            $file = $_FILES['btc_import_file'];
            if ($file['type'] == 'application/json') {
                $content = json_decode(file_get_contents($file['tmp_name']), TRUE);
                self::import($content, $_POST['import_full_sync'], $_POST['import_change_desc']);
            } else {
                ?>
                <div class="notice notice-error is-dismissible">
                    <p>Неверный тип файла</p>
                </div>
                <?

            }
        }
        parent::view('header');
        parent::view('import');
    }

    /**
     * Add admin menu
     */
    public static function admin_menu()
    {
        $blockchains = self::get_data(BTCPLG_TBL_BLOCKCHAINS);
        // Меню админки
        add_menu_page(
            'BitcoinCore', 'BitcoinCore', 'manage_options', 'btc-admin-menu', array('Bitcoincore_Admin', 'render_blockchains'), 'dashicons-edit', 2
        );
        // Блокчейны
        add_submenu_page(
            'btc-admin-menu', 'Blockchains', 'Blockchains', 'manage_options', 'btc-admin-menu'
        );
        foreach ($blockchains as $blockchain) {
            add_submenu_page(
                'btc-admin-menu',
                $blockchain->name,
                $blockchain->name,
                'manage_options',
                'btc-admin-menu-blockchain-' . $blockchain->name,
                array('Bitcoincore_Admin', 'render_blockchain')
            );
        };
        // Импорт
        add_submenu_page(
            'btc-admin-menu', 'Импорт', 'Импорт', 'manage_options', 'btc-import', array('Bitcoincore_Admin', 'render_import')
        );
    }

}