<?php

class Bitcoincore_Admin extends Bitcoincore
{
    private static $initiated = false;
    private static $current_table;

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
        add_action('admin_menu', array('Bitcoincore_Admin', 'admin_menu'));
        add_action('admin_enqueue_scripts', array('Bitcoincore_Admin', 'register_assets'));
    }

    /**
     * Register assets(css,js)
     */
    public static function register_assets()
    {
        wp_enqueue_style('bitcoincore', plugins_url('/assets/bitcoincore-plg-admin.css', __FILE__));
        wp_enqueue_script('bitcoincore', plugins_url('/assets/bitcoincore-plg-admin.js', __FILE__), array('jquery'));
    }

    /**
     * Method generating meta-data and return array {
     * BTCPLG_META_TITLE => $title,
     * BTCPLG_META_DESC => $desctiption (maxlenght = 160)
     * }
     *
     * @param $title
     * @param $desctiption
     * @return array
     */
    public static function generate_meta($title, $desctiption)
    {
        $meta_title = $title;
        $meta_desc = substr($desctiption, 0, 160);
        return array(
            BTCPLG_META_TITLE => $meta_title,
            BTCPLG_META_DESC => $meta_desc
        );
    }

    /**
     * Method execute add action for $_POST data
     */
    public static function action_add()
    {
        global $wpdb;
        $tbl_name = self::$current_table;
        $prefix = $wpdb->prefix;
        if ($tbl_name == BTCPLG_TBL_METHODS) {
            if (isset($_POST['name']) && isset($_POST['category_id'])) {
                $method_name = $_POST['name'];
                $wpdb->insert($prefix . $tbl_name, array('name' => $method_name)); //Создаем сам метод
                $method_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $prefix . BTCPLG_TBL_METHODS . " WHERE name = %s", $method_name));
                $versions = parent::get_data(BTCPLG_TBL_VERSIONS);
                foreach ($versions as $version) {
                    if (isset($_POST['versions']) && $_POST['versions'][$version->id] == true) {
                        $version_desc = $_POST['versions_desc'][$version->id];
                        $category_id = $_POST['category_id'];
                        $parent_id = $version->page_id;
                        //создаем страницу
                        $page_id = wp_insert_post(array(
                            'comment_status' => 'closed',
                            'ping_status' => 'closed',
                            'post_author' => get_current_user_id(),
                            'post_content' => $version_desc,
                            'post_name' => $method_name,
                            'post_status' => 'publish',
                            'post_title' => $method_name,
                            'post_type' => 'page',
                            'post_parent' => $parent_id,
                            'meta_input' => self::generate_meta($method_name . ' ' . $version->name, $version_desc)
                        ));
                        // Записываем данные в БД
                        $wpdb->insert($prefix . BTCPLG_TBL_METHODS_VERSIONS, array(
                            'method_id' => $method_id,
                            'version_id' => $version->id,
                            'category_id' => $category_id,
                            'page_id' => $page_id
                        ), array('%d', '%d', '%d', '%d'));
                    }
                }
            }
        } else {
            if (isset($_POST['name']) && !empty($_POST['name'])) {
                $name = $_POST['name'];
                // Для версий
                if ($tbl_name == BTCPLG_TBL_VERSIONS) {
                    //создаем страницу
                    $page_id = wp_insert_post(array(
                        'comment_status' => 'closed',
                        'ping_status' => 'closed',
                        'post_author' => get_current_user_id(),
                        'post_name' => $name,
                        'post_status' => 'publish',
                        'post_title' => $name,
                        'post_type' => 'page',
                    ));
                    $wpdb->insert($prefix . $tbl_name, array('name' => $name, 'page_id' => $page_id));
                    $version_id = $wpdb->get_results("SELECT id FROM {$prefix}{$tbl_name} WHERE page_id = {$page_id}");
                    wp_update_post(array(
                        'ID' => $page_id,
                        'post_content' => '<!-- wp:shortcode -->['.BTCPLG_SHORTCODE_VERSION.' id="'.$version_id[0]->id.'"]<!-- /wp:shortcode -->',
                    ));
                } else // Для категорий
                    $wpdb->insert($prefix . $tbl_name, array('name' => $name));
            }
        }
    }

    /**
     * Method execute edit action for $_POST data
     */
    public static function action_edit()
    {
        global $wpdb;
        $tbl_name = self::$current_table; // Текущая таблица
        $prefix = $wpdb->prefix; // Префикс для таблиц
        if (isset($_POST['id']) && isset($_POST['name']) && !empty($_POST['id']) && !empty($_POST['name'])) {
            $id = $_POST['id']; // ID метода
            $name = $_POST['name']; // имя метода
            $prev_name = $_POST['prev_name']; // Предудущее имя
            if ($tbl_name == BTCPLG_TBL_METHODS) { // Таблица методов
                $prev_category_id = $_POST['prev_category_id']; // Предыдущая категория
                $category_id = $_POST['category_id']; // Текущаяя категория
                $prev_versions = explode(';', $_POST['prev_versions']); // Предыдушие версии

                if ($prev_name !== $name) // Если изменилось имя записываем в базу
                    $wpdb->update($prefix . $tbl_name, array('name' => $name), array('id' => $id));
                $versions = parent::get_data(BTCPLG_TBL_VERSIONS);
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
                        //создаем страницу
                        $page_id = wp_insert_post(array(
                            'comment_status' => 'closed',
                            'ping_status' => 'closed',
                            'post_author' => get_current_user_id(),
                            'post_content' => $version_desc,
                            'post_name' => $name,
                            'post_status' => 'publish',
                            'post_title' => $name,
                            'post_type' => 'page',
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
                    $wpdb->update($prefix . BTCPLG_TBL_METHODS_VERSIONS,
                        array('category_id' => $category_id),
                        array(
                            'method_id' => $id,
                        ),
                        array(
                            '%d'
                        ),
                        array(
                            '%d'
                        ));
                }
            } else {
                if ($name != $prev_name) { // Если изменилось название
                    if ($tbl_name == BTCPLG_TBL_VERSIONS) { // Для версий
                        $page_id = $page_id = $wpdb->get_results("SELECT page_id FROM " . $prefix . $tbl_name . " WHERE id = {$id}");
                        wp_update_post(array(
                            'ID' => $page_id[0]->page_id,
                            'post_name' => $name,
                            'post_title' => $name
                        ));

                        // изменяем мета у дочерних страниц
                        $posts = get_posts(array(
                            'post_parent' => $page_id[0]->page_id,
                            'numberposts' => -1,
                            'post_type' => 'page'
                        ));
                        foreach ($posts AS $post) {
                            $meta_title = get_post_meta($post->ID, BTCPLG_META_TITLE, true);
                            $meta_title = str_replace($prev_name, $name, $meta_title);
                            update_post_meta($post->ID, BTCPLG_META_TITLE, $meta_title);

                        }
                        $wpdb->update($prefix . $tbl_name, array('name' => $name), array('id' => $id));
                    }
                    $wpdb->update($prefix . $tbl_name, array('name' => $name), array('id' => $id)); // Для категорий
                }
            }
        }
    }

    /**
     * Method execute delete action for $_POST data
     */
    public static function action_delete()
    {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            global $wpdb;
            $tbl_name = self::$current_table;
            $prefix = $wpdb->prefix;
            if ($tbl_name == BTCPLG_TBL_METHODS) {
                //Получаем ID страниц
                $pages_id = $wpdb->get_results("SELECT page_id FROM " . $prefix . BTCPLG_TBL_METHODS_VERSIONS . " WHERE method_id = {$_POST['id']} AND category_id = {$_POST['category_id']}");
                //Удаляем записи из бд
                $wpdb->delete($prefix . BTCPLG_TBL_METHODS_VERSIONS,
                    array(
                        'method_id' => $_POST['id'],
                        'category_id' => $_POST['category_id']
                    ),
                    array('%d', '%d'));
                foreach ($pages_id as $p_id) {
                    // Удаляем страницы
                    wp_delete_post($p_id->page_id, true);
                }
                // Кол-во записей с данным методом
                $count = $wpdb->get_var("SELECT COUNT(*) FROM " . $prefix . BTCPLG_TBL_METHODS_VERSIONS . " WHERE method_id = {$_POST['id']}");
                if ($count == 0) {
                    // Удаляем если метод больше не используется в других версиях
                    $wpdb->delete($prefix . $tbl_name, array('id' => $_POST['id']), array('%d'));
                }
            } elseif ($tbl_name == BTCPLG_TBL_VERSIONS) {
                $page_id = $wpdb->get_results("SELECT page_id FROM " . $prefix . $tbl_name . " WHERE id = {$_POST['id']}");
                $wpdb->delete($prefix . $tbl_name, array('id' => $_POST['id']), array('%d'));
                wp_delete_post($page_id[0]->page_id, true);
            } else
                $wpdb->delete($prefix . $tbl_name, array('id' => $_POST['id']), array('%d'));
        }
    }

    /**
     * returns type $_POST[action]
     *
     * @return string
     */
    public static function get_type_action()
    {
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            switch ($action) {
                case 'Добавить':
                    return 'add';
                    break;
                case 'Удалить':
                    return 'delete';
                    break;
                case 'Изменить':
                    return 'edit';
                    break;
            }
        }
    }

    /**
     *
     * Executes a method appropriate to the type
     *
     * @param $action_type
     */
    public static function do_action($action_type)
    {
        switch ($action_type) {
            case 'add':
                self::action_add();
                break;
            case 'edit':
                self::action_edit();
                break;
            case 'delete':
                self::action_delete();
                break;
        }
    }

    /**
     * Render page
     */
    public static function render()
    {
        self::register_assets();
        $tbl_name = self::$current_table;
        $data = parent::get_data($tbl_name);
        if ($tbl_name == BTCPLG_TBL_METHODS) {
            $categories = parent::get_data(BTCPLG_TBL_CATEGORIES);
            $versions = parent::get_data(BTCPLG_TBL_VERSIONS);
            parent::view('header');
            foreach ($categories AS $category) {
                $view_args = compact('tbl_name', 'data', 'categories', 'versions', 'category');
                echo '<h3>' . $category->name . '</h3>';
                parent::view('table', $view_args);
                parent::view('add', $view_args);
            }
        } else {
            $view_args = compact('tbl_name', 'data');
            parent::view('header');
            parent::view('table', $view_args);
            parent::view('add', $view_args);
        }

    }

    /**
     * Render methods
     */
    public static function render_methods()
    {
        self::$current_table = BTCPLG_TBL_METHODS;
        $action_type = self::get_type_action();
        self::do_action($action_type);
        self::render();
    }

    /**
     * Render categories
     */
    public static function render_categories()
    {
        self::$current_table = BTCPLG_TBL_CATEGORIES;
        $action_type = self::get_type_action();
        self::do_action($action_type);
        self::render();
    }

    /**
     * Render versions
     */
    public static function render_versions()
    {
        self::$current_table = BTCPLG_TBL_VERSIONS;
        $action_type = self::get_type_action();
        self::do_action($action_type);
        self::render();
    }

    /**
     * Add admin menu
     */
    public static function admin_menu()
    {
        // Меню админки
        add_menu_page(
            'BitcoinCore', 'BitcoinCore', 'manage_options', 'btc-admin-menu', array('Bitcoincore_Admin', 'render_methods'), 'dashicons-edit', 2
        );
        // Методы
        add_submenu_page(
            'btc-admin-menu', 'Методы', 'Методы', 'manage_options', 'btc-admin-menu');
        // Категории
        add_submenu_page(
            'btc-admin-menu', 'Категории', 'Категории', 'manage_options', 'btc-admin-menu-categories', array('Bitcoincore_Admin', 'render_categories')
        );
        // Версии
        add_submenu_page(
            'btc-admin-menu', 'Версии', 'Версии', 'manage_options', 'btc-admin-menu-versions', array('Bitcoincore_Admin', 'render_versions')
        );
    }

}