<? if (isset($_GET['filter_versions'])) {
    $filter_versions = $_GET['filter_versions'];
} ?>

<? if (!empty($versions)) { ?>
    <button class="btn dropdown-toggle" type="button" data-toggle="collapse" data-target="#filter-versions">
        Filter by version
    </button>

    <div class="collapse shadow rounded" id="filter-versions">
        <form>
            <input hidden name="page" value="<? echo $_GET['page'] ?>"/>
            <? foreach ($versions as $version) { ?>
                <label>
                    <input
                            type="checkbox"
                            name="filter_versions[]"
                            value="<? echo $version->id ?>"
                        <? echo (isset($filter_versions) && in_array($version->id, $filter_versions, true)) ? 'checked' : '' ?>
                    />
                    <? echo $version->name ?>
                </label>
            <? } ?>
            <button class="btn">Apply</button>
        </form>
    </div>
<? } ?>

<div class="table-responsive">
    <table class="table table-sm table-bordered table-hover table-sticky sorted_head table-versions">
        <thead class="thead-light ">
        <tr>
            <th class="align-middle text-center not-sortable">
                <span>Method name</span>
            </th>
            <? foreach ($versions as $version) {
                if (isset($filter_versions) && !in_array($version->id, $filter_versions, true))
                    continue;
                ?>
                <th scope="col" class="text-center">
                    <a data-order="<? echo $version->order ?>" href="<? echo get_page_link($version->page_id) ?>"><? echo $version->name; ?></a>
                    <div class="control-bar control-bar-version">
                        <span class="dashicons dashicons-edit" id="edit-version-<? echo $version->id?>"></span>
                        <span class="dashicons dashicons-no-alt" id="delete-version-<? echo $version->id?>"></span>
                    </div>
                    <div class="control-bar" style="display: none">
                        <form method="post">
                            <input hidden name="id" value="<? echo $version->id; ?>"/>
                            <input hidden name="prev_name" value="<? echo $version->name; ?>"/>
                            <input class="form-control input-edit-version" name="name" value="<? echo $version->name; ?>"/>
                            <button class="btn-edit btn-edit-success" name="action" value="edit_version">
                                <span class="dashicons dashicons-yes"></span>
                            </button>
                            <button class="btn-edit btn-edit-cancel" type="button" value="cancel">
                                <span class="dashicons dashicons-minus"></span>
                            </button>
                        </form>
                    </div>
                </th>
            <? } ?>

        </tr>
        </thead>
        <tbody>
        <? foreach ($categories as $category) {
            $methods_column_category_id = array_column($methods, 'category_id');
            ?>
            <tr class="no-hover">
                <th class="text-center not-sortable" colspan="1000">
                    <span><? echo $category->name ?></span>
                    <div class="control-bar control-bar-category">
                        <span class="dashicons dashicons-edit" id="edit-category-<? echo $category->id?>"></span>
                        <span class="dashicons dashicons-no-alt" id="delete-category-<? echo $category->id?>"></span>
                    </div>
                    <div class="control-bar" style="display: none">
                        <form method="post">
                            <input hidden name="id" value="<? echo $category->id; ?>"/>
                            <input hidden name="prev_name" value="<? echo $category->name; ?>"/>
                            <input class="form-control input-edit-category" name="name" value="<? echo $category->name; ?>"/>
                            <button class="btn-edit btn-edit-success" name="action" value="edit_category">
                                <span class="dashicons dashicons-yes"></span>
                            </button>
                            <button class="btn-edit btn-edit-cancel" type="button" value="cancel">
                                <span class="dashicons dashicons-minus"></span>
                            </button>
                        </form>
                    </div>
                </th>
            </tr>
            <? foreach ($methods as $method) {
                if ($method->category_id != $category->id)
                    continue; ?>
                <tr>
                    <? foreach ($method as $key => $col) {
                        if ($key == 'id' || $key == 'category_id' || $key == 'version_desc' || $key == 'page_id')
                            continue;
                        if ($key == 'version_id') {
                            $versions_id = explode(';', $col);
                            $pages_id = explode(';', $method->page_id);
                            foreach ($versions as $version) {
                                if (isset($filter_versions) && !in_array($version->id, $filter_versions, true))
                                    continue;
                                foreach ($versions_id as $version_key => $version_id) {
                                    if ($version->id === $version_id) {
                                        $version_desc = apply_filters('the_content', get_post_field('post_content', $pages_id[$version_key], 'display'));
                                        ?>
                                        <td class="table-success text-center ">
                                            <a class="text-success"
                                               href="<? echo get_page_link($pages_id[$version_key]) ?>"
                                               title="<? echo !empty($version_desc) ? wp_filter_nohtml_kses($version_desc) : 'Missing description' ?>">
                                                <? include(BTCPLUGIN__DIR . 'assets/img/check.svg'); ?>
                                            </a>
                                        </td>
                                        <? continue 2;
                                    }
                                } ?>
                                <td class="table-danger text-danger text-center">
                                    <? include(BTCPLUGIN__DIR . 'assets/img/times.svg'); ?>
                                </td>
                                <?
                            }
                            continue;
                        } ?>
                        <td>
                            <span><? echo $method->name ?></span>
                            <div class="control-bar control-bar-method">
                                <span class="dashicons dashicons-edit" id="edit-method-<? echo $method->id?>"></span>
                                <span class="dashicons dashicons-no-alt" id="delete-method-<? echo $method->id . '-' . $category->id . '-' . $blockchain_id?>"></span>
                            </div>
                        </td>
                    <? } ?>
                </tr>
                <tr class="edit-bar no-hover" id="edit-bar-<? echo $method->id; ?>" style="display: none">
                    <td colspan="1000">
                        <? include(BTCPLUGIN__DIR . 'views/edit-method.php'); ?>
                    </td>
                </tr>
            <? }
        } ?>
        </tbody>
    </table>
</div>