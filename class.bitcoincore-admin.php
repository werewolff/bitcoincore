<?php

class Bitcoincore_Admin
{
    private static $initiated = false;
    private static $current_table;

    public static function init()
    {
        if (!self::$initiated) {
            self::init_hooks();
        }
    }

    public static function init_hooks()
    {
        self::$initiated = true;
        add_action('admin_menu', array('Bitcoincore_Admin', 'admin_menu'));
        add_action('admin_enqueue_scripts', array('Bitcoincore_Admin', 'register_assets'));
    }

    public static function register_assets()
    {
        wp_enqueue_style('bitcoincore', plugins_url('/assets/bitcoincore-plg-admin.css', __FILE__));
        wp_enqueue_script('bitcoincore', plugins_url('/assets/bitcoincore-plg-admin.js', __FILE__), array('jquery'));
    }

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
                $versions = self::get_data(BTCPLG_TBL_VERSIONS);
                foreach ($versions as $version) {
                    if (isset($_POST['versions']) && $_POST['versions'][$version->id] == true) {
                        $version_desc = $_POST['versions_desc'][$version->id];
                        $category_id = $_POST['category_id'];
                        $parent_id = $version->page_id;
                        //Генерируем мета-данные или используем кастомные
                        if (empty($_POST['meta_title'][$version->id]) && empty($_POST['meta_description'][$version->id])) {
                            $meta_title = $method_name . ' ' . $version->name;
                            $meta_desc = substr($version_desc, 0, 160);
                        } elseif (empty($_POST['meta_title'][$version->id])) {
                            $meta_title = $method_name . ' ' . $version->name;
                            $meta_desc = $_POST['meta_description'][$version->id];
                        } elseif (empty($_POST['meta_description'][$version->id])) {
                            $meta_title = $_POST['meta_title'][$version->id];
                            $meta_desc = substr($version_desc, 0, 160);
                        } else {
                            $meta_title = $_POST['meta_title'][$version->id];
                            $meta_desc = $_POST['meta_description'][$version->id];
                        }

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
                            'meta_input' => array(
                                BTCPLG_META_TITLE => $meta_title,
                                BTCPLG_META_DESC => $meta_desc
                            )
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
                $post_name = $_POST['name'];
                // Для версий
                if ($tbl_name == BTCPLG_TBL_VERSIONS) {
                    //создаем страницу
                    $page_id = wp_insert_post(array(
                        'comment_status' => 'closed',
                        'ping_status' => 'closed',
                        'post_author' => get_current_user_id(),
                        'post_content' => '',
                        'post_name' => $post_name,
                        'post_status' => 'publish',
                        'post_title' => $post_name,
                        'post_type' => 'page',
                    ));
                    $wpdb->insert($prefix . $tbl_name, array('name' => $post_name, 'page_id' => $page_id));
                } else // Для категорий
                    $wpdb->insert($prefix . $tbl_name, array('name' => $post_name));
            }
        }
    }

    public static function action_edit()
    {
        global $wpdb;
        $tbl_name = self::$current_table;
        $prefix = $wpdb->prefix;
        print_r($_POST);
        if (isset($_POST['id']) && isset($_POST['name']) && !empty($_POST['id']) && !empty($_POST['name'])) {
            $id = $_POST['id'];
            $name = $_POST['name'];
            if ($tbl_name == BTCPLG_TBL_METHODS) {
                $wpdb->update($prefix . $tbl_name, array('name' => $name), array('id' => $id));
                $versions = self::get_data(BTCPLG_TBL_VERSIONS);
                foreach ($versions AS $version) {
                    if (isset($_POST['versions']) && $_POST['versions'][$version->id] == true) {
                        $version_desc = $_POST['versions_desc'][$version->id];
                        $category_id = $_POST['category_id'];
                        $parent_id = $version->page_id;
                        $page_id = $wpdb->get_results("SELECT page_id FROM " . $prefix . BTCPLG_TBL_METHODS_VERSIONS . " WHERE method_id = $id AND version_id = $version->id AND category_id = $category_id");

                        /*//Генерируем мета-данные или используем кастомные
                        if (empty($_POST['meta_title'][$version->id]) && empty($_POST['meta_description'][$version->id])) {
                            $meta_title = $method_name . ' ' . $version->name;
                            $meta_desc = substr($version_desc, 0, 160);
                        } elseif (empty($_POST['meta_title'][$version->id])) {
                            $meta_title = $method_name . ' ' . $version->name;
                            $meta_desc = $_POST['meta_description'][$version->id];
                        } elseif (empty($_POST['meta_description'][$version->id])) {
                            $meta_title = $_POST['meta_title'][$version->id];
                            $meta_desc = substr($version_desc, 0, 160);
                        } else {
                            $meta_title = $_POST['meta_title'][$version->id];
                            $meta_desc = $_POST['meta_description'][$version->id];
                        }
                        */
                        //Обновляем страницу
                        wp_update_post(array(
                            'ID' => $page_id[0]->page_id,
                            'post_author' => get_current_user_id(),
                            'post_content' => $version_desc,
                            'post_name' => $name,
                            'post_title' => $name,
                            'post_parent' => $parent_id,
                        ));
                        // Обновляем данные в БД
                        $wpdb->update($prefix . BTCPLG_TBL_METHODS_VERSIONS, array(
                            'method_id' => $id,
                            'version_id' => $version->id,
                            'category_id' => $category_id,
                            'page_id' => $page_id
                        ),array(), array('%d', '%d', '%d', '%d'));
                    }
                }
            } else {
                if ($tbl_name == BTCPLG_TBL_VERSIONS) {
                    $page_id = $page_id = $wpdb->get_results("SELECT page_id FROM " . $prefix . $tbl_name . " WHERE id = {$id}");
                    wp_update_post(array(
                        'ID' => $page_id[0]->page_id,
                        'post_name' => $name,
                        'post_title' => $name
                    ));
                    $wpdb->update($prefix . $tbl_name, array('name' => $name), array('id' => $id));
                }
                $wpdb->update($prefix . $tbl_name, array('name' => $name), array('id' => $id));

            }
        }
    }

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

    public static function render()
    {
        self::register_assets();
        $tbl_name = self::$current_table;
        $data = self::get_data($tbl_name);
        if ($tbl_name == BTCPLG_TBL_METHODS) {
            $categories = self::get_data(BTCPLG_TBL_CATEGORIES);
            $versions = self::get_data(BTCPLG_TBL_VERSIONS);
            self::view('header');
            foreach ($categories AS $category) {
                $view_args = compact('tbl_name', 'data', 'categories', 'versions', 'category');
                echo '<h3>' . $category->name . '</h3>';
                self::view('table', $view_args);
                self::view('add', $view_args);
            }
        } else {
            $view_args = compact('tbl_name', 'data');
            self::view('header');
            self::view('table', $view_args);
            self::view('add', $view_args);
        }

    }

    public static function render_methods()
    {
        self::$current_table = BTCPLG_TBL_METHODS;
        $action_type = self::get_type_action();
        self::do_action($action_type);
        self::render();
    }

    public static function render_categories()
    {
        self::$current_table = BTCPLG_TBL_CATEGORIES;
        $action_type = self::get_type_action();
        self::do_action($action_type);
        self::render();
    }

    public static function render_versions()
    {
        self::$current_table = BTCPLG_TBL_VERSIONS;
        $action_type = self::get_type_action();
        self::do_action($action_type);
        self::render();
    }

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

    public static function view($name, array $args = array())
    {
        extract($args, EXTR_OVERWRITE);
        $file = BTCPLUGIN__DIR . 'views/' . $name . '.php';
        include($file);
    }
}