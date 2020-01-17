<div class="btn-group-toggle" data-toggle="buttons">
    <? foreach ($versions as $version) { ?>
        <label class="btn btn-secondary btn-sm">
            <input
                    type="checkbox"
                    name="version-<? echo $version->id ?>"
                    data-toggle='collapse'
                    data-target='.collapse-<? echo $version->id ?>'
                    checked
            />
            <? echo $version->name ?>
        </label>
    <? } ?>
</div>
<div class="table-responsive">
    <table class="table table-sm table-bordered table-hover" id="versions_table">
        <thead class="thead-light">
        <tr>
            <th class="text-center">
                <span>Method name</span>
            </th>
            <? foreach ($versions as $version) { ?>
                <th scope="col" class="text-center collapse collapse-<? echo $version->id ?> show">
                    <a href="<? echo get_page_link($version->page_id) ?>"><? echo $version->name; ?></a>
                </th>
            <? } ?>

        </tr>
        </thead>
        <tbody>
        <? foreach ($categories as $category) { ?>
            <tr>
                <th class="text-center" colspan="1000">
                    <? echo $category->name ?>
                </th>
            </tr>
            <? foreach ($methods as $method) { ?>
                <tr>
                    <? foreach ($method as $key => $col) {
                        if ($method->category_id != $category->id)
                            continue 2;
                        if ($key == 'id' || $key == 'category_id' || $key == 'version_desc' || $key == 'page_id')
                            continue;
                        if ($key == 'version_id') {
                            $versions_id = explode(';', $col);
                            $pages_id = explode(';', $method->page_id);
                            foreach ($versions as $version) {
                                foreach ($versions_id as $version_key => $version_id) {
                                    if ($version->id === $version_id) {
                                        $version_desc = apply_filters('the_content', get_post_field('post_content', $pages_id[$version_key], 'display'));
                                        ?>
                                        <td class="table-success text-center collapse collapse-<? echo $version->id ?> show">
                                            <a class="text-success"
                                               href="<? echo get_page_link($pages_id[$version_key]) ?>"
                                               title="<? echo !empty($version_desc) ? wp_filter_nohtml_kses($version_desc) : 'Missing description' ?>">
                                                <? include(BTCPLUGIN__DIR . 'assets/check.svg'); ?>
                                            </a>
                                        </td>
                                        <? continue 2;
                                    }
                                } ?>
                                <td class="table-danger text-danger text-center collapse collapse-<? echo $version->id ?> show">
                                    <? include(BTCPLUGIN__DIR . 'assets/times.svg'); ?>
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