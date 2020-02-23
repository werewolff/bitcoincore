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
        wp_register_script(
            'bootstrap',
            plugins_url('/assets/bootstrap/bootstrap.min.js', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'assets/bootstrap/bootstrap.min.js')
        );
        wp_register_script(
            'jquery-ui',
            plugins_url('/assets/jquery-ui/jquery-ui.min.js', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'assets/jquery-ui/jquery-ui.min.js')
        );

        wp_register_script(
            'bitcoincore-admin-js',
            plugins_url('/assets/js/bitcoincore-plg-admin.js', __FILE__),
            array('jquery', 'jquery-ui', 'bootstrap'), // Jquery-ui включен только sortable!!!
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/bitcoincore-plg-admin.js')
        );
        wp_register_style(
            'bs',
            plugins_url('/assets/bootstrap/bootstrap.min.css', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'assets/bootstrap/bootstrap.min.css')
        );
        wp_register_style(
            'jquery-ui-css',
            plugins_url('/assets/jquery-ui/jquery-ui.min.css', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'assets/jquery-ui/jquery-ui.min.css')
        );

        wp_register_style(
            'bitcoincore-admin-css',
            plugins_url('/assets/css/bitcoincore-plg-admin.css', __FILE__),
            array('jquery-ui-css', 'bs'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/bitcoincore-plg-admin.css')
        );
        wp_enqueue_style('bitcoincore-admin-css');
        wp_enqueue_script('bitcoincore-admin-js');

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
     * Create version in database and create page with SHORTCODE
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
            $blockchain_page_id = $wpdb->get_var($wpdb->prepare("SELECT page_id FROM " . $prefix . BTCPLG_TBL_BLOCKCHAINS . " WHERE id = %d", $blockchain_id));
            //создаем страницу
            $page_id = wp_insert_post(array(
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'post_author' => get_current_user_id(),
                'post_name' => is_numeric($name) ? 'version-' . $name : $name,
                'post_status' => 'publish',
                'post_title' => $name,
                'post_parent' => $blockchain_page_id,
                'post_type' => 'bitcoincore',
                'meta_input' => array(
                    'btc_page_type' => 'version'
                )
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
                'meta_input' => array(
                    'btc_page_type' => 'blockchain'
                )
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
     * Method execute add action for $_POST data
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
     * Method execute edit action for $_POST data
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
            $page_id = $page_id = $wpdb->get_results("SELECT page_id FROM " . $prefix . BTCPLG_TBL_VERSIONS . " WHERE id = {$id}");
            wp_update_post(array(
                'ID' => $page_id[0]->page_id,
                'post_name' => $name,
                'post_title' => $name
            ));

            // изменяем мета у дочерних страниц
            $posts = get_posts(array(
                'post_parent' => $page_id[0]->page_id,
                'numberposts' => -1,
                'post_type' => 'bitcoincore'
            ));
            foreach ($posts AS $post) {
                $meta_title = get_post_meta($post->ID, BTCPLG_META_TITLE, true);
                $meta_title = str_replace($prev_name, $name, $meta_title);
                update_post_meta($post->ID, BTCPLG_META_TITLE, $meta_title);

            }
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
            $page_id = $page_id = $wpdb->get_results("SELECT page_id FROM " . $prefix . BTCPLG_TBL_BLOCKCHAINS . " WHERE id = {$id}");
            wp_update_post(array(
                'ID' => $page_id[0]->page_id,
                'post_name' => $name,
                'post_title' => $name
            ));

            // изменяем мета у дочерних страниц
            $posts = get_posts(array(
                'post_parent' => $page_id[0]->page_id,
                'numberposts' => -1,
                'post_type' => 'bitcoincore'
            ));
            foreach ($posts AS $post) {
                $meta_title = get_post_meta($post->ID, BTCPLG_META_TITLE, true);
                $meta_title = str_replace($prev_name, $name, $meta_title);
                update_post_meta($post->ID, BTCPLG_META_TITLE, $meta_title);

            }
            $wpdb->update($prefix . BTCPLG_TBL_BLOCKCHAINS, array('name' => $name), array('id' => $id));
            // Перезагрузка чтобы изменения отобразились в меню
            echo '<script type="application/javascript">location.reload()</script>';
        }
    }

    /**
     * Method execute delete action for $_POST data
     * @param $type
     */
    public
    static function action_delete($type)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        if ($type === 'version') {

            $page_id = $wpdb->get_results("SELECT page_id FROM " . $prefix . BTCPLG_TBL_VERSIONS . " WHERE id = {$_POST['id']}");
            $wpdb->delete($prefix . BTCPLG_TBL_VERSIONS, array('id' => $_POST['id']), array('%d'));
            wp_delete_post($page_id[0]->page_id, true);
        }

        if ($type === 'category') {
            //Получаем ID страниц
            $pages_id = $wpdb->get_results("SELECT page_id FROM " . $prefix . BTCPLG_TBL_METHODS_VERSIONS . " WHERE category_id = {$_POST['id']}");
            // Удаляем страницы с методами в данной категории
            foreach ($pages_id as $p_id) {
                // Удаляем страницы
                wp_delete_post($p_id->page_id, true);
            }
            // Удаляем саму категорию из базы
            $wpdb->delete($prefix . BTCPLG_TBL_CATEGORIES, array('id' => $_POST['id']), array('%d'));
        }

        if ($type === 'method') {
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
                $wpdb->delete($prefix . BTCPLG_TBL_METHODS, array('id' => $_POST['id']), array('%d'));
            }
        }

        if ($type === 'blockchain') {
            // Получаем ID страницы блокчейна
            $page_id = $wpdb->get_results("SELECT page_id FROM " . $prefix . BTCPLG_TBL_BLOCKCHAINS . " WHERE id = {$_POST['id']}");
            // Получаем версии данного блокчейна
            $versions = parent::get_data(BTCPLG_TBL_VERSIONS, $_POST['id']);
            foreach ($versions as $version) {
                // Получаем страницы методов каждой версии
                $pages_methods = $wpdb->get_results("SELECT page_id FROM " . $prefix . BTCPLG_TBL_METHODS_VERSIONS . " WHERE id = {$version->id}");
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
    public
    static function import($data, $mode_full_sync = false)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;

        $tbl_m = $wpdb->prefix . BTCPLG_TBL_METHODS;
        $tbl_mv = $wpdb->prefix . BTCPLG_TBL_METHODS_VERSIONS;
        $tbl_v = $wpdb->prefix . BTCPLG_TBL_VERSIONS;
        $tbl_c = $wpdb->prefix . BTCPLG_TBL_CATEGORIES;

        $versions = (array)parent::get_data(BTCPLG_TBL_VERSIONS, self::$current_blockchain_id);
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
                                    $desc_from_file = apply_filters('btc_description', $desc_from_file);
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