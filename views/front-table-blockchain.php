<? if (isset($_GET['filter_versions'])) {
    $filter_versions = $_GET['filter_versions'];
} ?>
<button class="btn dropdown-toggle" type="button" data-toggle="collapse" data-target="#filter-versions">
    Filter by version
</button>
<div class="collapse shadow rounded" id="filter-versions">
    <form>
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
<div class="table-responsive">
    <table class="table table-sm table-bordered table-hover table-sticky table-versions">
        <thead class="thead-light">
        <tr>
            <th class="text-center">
                <span>Method name</span>
            </th>
            <? foreach ($versions as $version) {
                if (isset($filter_versions) && !in_array($version->id, $filter_versions, true))
                    continue;
                ?>
                <th scope="col" class="text-center">
                    <a href="<? echo get_page_link($version->page_id) ?>"><? echo $version->name; ?></a>
                </th>
            <? } ?>

        </tr>
        </thead>
        <tbody>
        <? foreach ($categories as $category) {
            $methods_column_category_id = array_column($methods, 'category_id');
            if (!in_array($category->id, $methods_column_category_id)) {
                continue;
            }
            ?>
            <tr class="no-hover">
                <th class="text-center" colspan="1000">
                    <? echo $category->name ?>
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
                        <td><? echo $method->name ?></td>
                    <? } ?>
                </tr>
            <? }
        } ?>
        </tbody>
    </table>
</div>