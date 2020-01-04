<div class="wrap">
    <h1 class="wp-heading-inline">
        <? echo get_admin_page_title(); ?>
    </h1>
    <hr class="wp-header-end">

    <?
    if ($tbl_name == $wpdb->prefix . BTCPLG_TBL_METHODS) {
        foreach ($categories as $category) { ?>
            <h3><? echo $category->name ?></h3>
            <?
            include('table.php');
            include('add.php');
        }
    } else {
        include('table.php');
        include('add.php');
    }
    ?>
</div>
