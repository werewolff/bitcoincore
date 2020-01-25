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
        wp_register_script(
            'bitcoincore-admin-js',
            plugins_url('/assets/bitcoincore-plg-admin.js', __FILE__),
            array('jquery'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/bitcoincore-plg-admin.js')
        );

        wp_register_style(
            'bitcoincore-admin-css',
            plugins_url('/assets/bitcoincore-plg-admin.css', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'assets/bitcoincore-plg-admin.css')
        );
        wp_enqueue_style('bitcoincore-admin-css');
        wp_enqueue_script('bitcoincore-admin-js');
    }

    /**
     * Method generating meta-data and return array {
     * BTCPLG_META_TITLE => $title,
     * BTCPLG_META_DESC => $desctiption (maxlenght = 160)
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

    /**
     * @param $method_name
     * @param $method_id
     * @param $description
     * @param $version_name
     * @param $parent_id
     * @param $version_id
     * @param $category_id
     * @return array
     */
    public static function create_version_for_method($method_name, $method_id, $description, $version_name, $parent_id, $version_id, $category_id)
    {
        global $wpdb;
        //создаем страницу
        $page_id = wp_insert_post(array(
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_author' => get_current_user_id(),
            'post_content' => $description,
            'post_name' => $method_name,
            'post_status' => 'publish',
            'post_title' => $method_name,
            'post_type' => 'page',
            'post_parent' => $parent_id,
            'meta_input' => self::generate_meta($method_name . ' | ' . $version_name, $description)
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
     * Create version in database and create page with SHORTCODE
     *
     * @param $name
     */
    public static function create_version($name)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $version_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $prefix . BTCPLG_TBL_VERSIONS . " WHERE name = %s", $name));
        if (!isset($version_id)) { // Если не существует версии, создаем
            //создаем страницу

            $page_id = wp_insert_post(array(
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_author' => get_current_user_id(),
                'post_name' => is_numeric($name) ? 'v-' . $name : $name,
                'post_status' => 'publish',
                'post_title' => $name,
                'post_type' => 'page',
                'meta_input' => array(
                        'btc_page_type' => 'version'
                )
            ));
            $wpdb->insert($prefix . BTCPLG_TBL_VERSIONS, array('name' => $name, 'page_id' => $page_id));
            $version_id = $wpdb->get_results("SELECT id FROM " . $prefix . BTCPLG_TBL_VERSIONS . " WHERE page_id = {$page_id}");
            wp_update_post(array(
                'ID' => $page_id,
                'post_content' => '<!-- wp:shortcode -->[' . BTCPLG_SHORTCODE_VERSION . ' id="' . $version_id[0]->id . '"]<!-- /wp:shortcode -->',
            ));
        }
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
                $method_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $prefix . BTCPLG_TBL_METHODS . " WHERE name = %s", $method_name));
                if (!isset($method_id)) {
                    $wpdb->insert($prefix . $tbl_name, array('name' => $method_name)); //Создаем сам метод
                    $method_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $prefix . BTCPLG_TBL_METHODS . " WHERE name = %s", $method_name));
                }
                $versions = parent::get_data(BTCPLG_TBL_VERSIONS);
                foreach ($versions as $version) {
                    if (isset($_POST['versions']) && $_POST['versions'][$version->id] == true) {
                        $version_desc = $_POST['versions_desc'][$version->id];
                        $category_id = $_POST['category_id'];
                        $parent_id = $version->page_id;
                        self::create_version_for_method($method_name, $method_id, $version_desc, $version->name, $parent_id, $version->id, $category_id);
                    }
                }
            }
        } else {
            if (isset($_POST['name']) && !empty($_POST['name'])) {
                $name = $_POST['name'];
                // Для версий
                if ($tbl_name == BTCPLG_TBL_VERSIONS) {
                    self::create_version($name);
                } else // Для категорий
                    $category_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $prefix . BTCPLG_TBL_CATEGORIES . " WHERE name = %s", $name));
                if (!isset($category_id)) {
                    $wpdb->insert($prefix . $tbl_name, array('name' => $name));
                }
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
     * Import from json
     *
     * @param $data
     * @param bool $mode_full_sync
     */
    public static function import($data, $mode_full_sync = false)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $tbl_m = $wpdb->prefix . BTCPLG_TBL_METHODS;
        $tbl_mv = $wpdb->prefix . BTCPLG_TBL_METHODS_VERSIONS;
        $tbl_v = $wpdb->prefix . BTCPLG_TBL_VERSIONS;
        $tbl_c = $wpdb->prefix . BTCPLG_TBL_CATEGORIES;

        $versions = (array)parent::get_data(BTCPLG_TBL_VERSIONS);
        $versions_name = array_column($versions, 'name');
        $categories = (array)parent::get_data(BTCPLG_TBL_CATEGORIES);
        $categories_name = array_column($categories, 'name');
        $methods_db = (array)$wpdb->get_results("SELECT * FROM " . $tbl_m);
        $methods_name_db = array_column($methods_db, 'name');
        $methods_name_for_del = $methods_name_db;
        $log = array(
            'delete_v' => 0,
            'delete_c' => 0,
            'delete_m' => 0,
            'delete_mv' => 0,
            'add_v' => 0,
            'add_c' => 0,
            'add_m' => 0,
            'add_mv' => 0,
            'change_desc' => 0,
        );
        /* ВЕРСИИ */
        foreach ($data['versions'] as $version) {
            if (in_array($version, $versions_name)) { // поиск в массиве версии
                $i = array_search($version, $versions_name); // ищем индекс
                unset($versions_name[$i]); // удаляем из массива
            } else { // Если версии нет, то создаем ее
                self::create_version($version);
                $log['add_v']++;
            }
        }

        /* КАТЕГОРИИ И МЕТОДЫ*/
        foreach ($data['data'] as $category => $methods) {
            if (in_array($category, $categories_name)) { // поиск в массиве текущей категории
                $i = array_search($category, $categories_name); // ищем индекс
                unset($categories_name[$i]); // удаляем из массива
            } else {// Если категории нет, то создаем ее
                $wpdb->insert($tbl_c, array('name' => $category), array('%s'));
                $log['add_c']++;
            }
            foreach ($methods as $method_name => $method_version) {
                // Работаем с методами
                $category_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $tbl_c . " WHERE name = %s", $category));
                if (in_array($method_name, $methods_name_db)) { // поиск в массиве метода
                    $method_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $tbl_m . " WHERE name = %s", $method_name));
                    $i = array_search($method_name, $methods_name_db);// ищем индекс
                    unset($methods_name_for_del[$i]); // удаляем из массива
                    // Получаем все версии данного метода в нужной категории
                    $method_version_db = $wpdb->get_results("SELECT
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
    ) AS version_id,
    GROUP_CONCAT(
        DISTINCT {$tbl_v}.name
    ORDER BY
        {$tbl_v}.id
    ASC SEPARATOR
        ';'
    ) AS version_name
FROM
    {$tbl_mv}
LEFT JOIN {$tbl_v} ON {$tbl_mv}.version_id = {$tbl_v}.id
LEFT JOIN {$tbl_m} ON {$tbl_mv}.method_id = {$tbl_m}.id
LEFT JOIN {$tbl_c} ON {$tbl_mv}.category_id = {$tbl_c}.id
WHERE method_id = {$method_id} AND category_id = {$category_id}
GROUP BY {$tbl_c}.name, {$tbl_m}.name
ORDER BY {$tbl_c}.name, {$tbl_m}.name ASC")[0];
                    $pages_id_db = explode(';', $method_version_db->page_id);
                    $pages_id_for_del = $pages_id_db;
                    $versions_name_db = explode(';', $method_version_db->version_name);
                    foreach ($method_version as $version) {
                        if (in_array($version, $versions_name_db)) { //Если такая версия у данного метода есть в базе
                            $i = array_search($version, $versions_name_db);// ищем индекс версии
                            if ($mode_full_sync) { // Заменяем описание если полная синхронизация
                                $current_desc = get_post_field('post_content', $pages_id_db[$i], 'display');
                                $desc_from_file = isset($data['help'][$version][$method_name]) ? $data['help'][$version][$method_name] : '';
                                if ($current_desc !== $desc_from_file) {
                                    wp_update_post(array(
                                        'ID' => $pages_id_db[$i],
                                        'post_content' => $desc_from_file
                                    ));
                                    $log['change_desc']++;
                                }
                            }
                            //Оставляем только не найденые версии
                            unset($pages_id_for_del[$i]);
                        } else { // Если нет
                            $version_from_db = $wpdb->get_row("SELECT * FROM " . $tbl_v . " WHERE name = '{$version}'");
                            $desc_from_file = isset($data['help'][$version][$method_name]) ? $data['help'][$version][$method_name] : '';
                            self::create_version_for_method($method_name, $method_id, $desc_from_file, $version, $version_from_db->page_id, $version_from_db->id, $category_id);
                            $log['add_mv']++;
                        }
                    }
                    if ($mode_full_sync) { // Удаляем лишнии версии если полная синхронизация
                        foreach ($pages_id_for_del as $page_id) {
                            wp_delete_post($page_id, true); // Удаляем страницу версии и за счет связей удалится запись из БД
                            $log['delete_mv']++;
                        }
                    }
                } else { // Если метода нет, то создаем его
                    $wpdb->insert($tbl_m, array('name' => $method_name), array('%s'));
                    $method_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM " . $tbl_m . " WHERE name = %s", $method_name));
                    $log['add_m']++;
                    // Если метод создается то создаем его версии и страницы
                    foreach ($method_version as $version) {
                        $description = isset($data['help'][$version][$method_name]) ? $data['help'][$version][$method_name] : '';
                        $version_from_db = $wpdb->get_row("SELECT * FROM " . $tbl_v . " WHERE name = '{$version}'");
                        self::create_version_for_method($method_name, $method_id, $description, $version, $version_from_db->page_id, $version_from_db->id, $category_id);
                        $log['add_mv']++;
                    }
                }

            }
        }

        if ($mode_full_sync) { // Полная синхронизация с удалением
            foreach ($versions_name as $id => $version_name) {
                wp_delete_post($versions[$id]->page_id, true); // Удаляем страницу WP и за счет связей БД удалятся версии и все что с ними связано
                $log['delete_v']++;
            }
            foreach ($categories_name as $id => $category_name) {
                $wpdb->delete($prefix . BTCPLG_TBL_CATEGORIES, array('id' => $categories[$id]->id), array('%d')); // Удаляем категорию из БД
                $log['delete_c']++;
            }
            foreach ($methods_name_for_del as $id => $method_name) {
                $wpdb->delete($prefix . BTCPLG_TBL_METHODS, array('id' => $methods_db[$id]->id), array('%d')); // Удаляем метод из БД
                $log['delete_m']++;
            }
        }

        // LOG
        printf('<div class="notice notice-success is-dismissible">
                        <p>Добавлено версий: %s</p>
                        <p>Добавлено категорий: %s</p>
                        <p>Добавлено методов: %s</p>
                        <p>Добавлено версий к методам: %s</p>
                        <p>Удалено версий: %s</p>
                        <p>Удалено категорий: %s</p>
                        <p>Удалено методов: %s</p>
                        <p>Удалено версий у методов: %s</p>
                        <p>Изменено описаний: %s</p>
                    </div>', $log['add_v'], $log['add_c'], $log['add_m'], $log['add_mv'], $log['delete_v'], $log['delete_c'], $log['delete_m'], $log['delete_mv'], $log['change_desc']);

    }

    /**
     * Render import page
     */
    public
    static function render_import()
    {
        self::register_assets();
        if (isset($_FILES['btc_import_file'])) {
            $file = $_FILES['btc_import_file'];
            if ($file['type'] == 'application/json') {
                $content = json_decode(file_get_contents($file['tmp_name']), TRUE);
                if (isset($content['versions']) && isset($content['data']) && isset($content['help'])) {
                    $import_full_sync = false;
                    if (isset($_POST['import_full_sync']) && $_POST['import_full_sync'] == true)
                        $import_full_sync = true;
                    self::import($content, $import_full_sync);
                } else {
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p>Неверная структура файла</p>
                    </div>
                    <?
                }
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
    public
    static function admin_menu()
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
        // Версии
        add_submenu_page(
            'btc-admin-menu', 'Импорт', 'Импорт', 'manage_options', 'btc-import', array('Bitcoincore_Admin', 'render_import')
        );
    }

}