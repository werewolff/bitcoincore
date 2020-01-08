<div>
    <? foreach ($versions as $version) { ?>
        <input type="checkbox" name="version-<? echo $version->id ?>"/>
    <? } ?>
</div>

<table id="versions_table">
    <thead>
    <tr>
        <th>
            <span>Method</span>
        </th>
        <? foreach ($versions as $version) { ?>
            <th scope="col" class="">
                <span><? echo $version->name; ?></span>
            </th>
        <? } ?>

    </tr>
    </thead>
    <tbody>
    <? foreach ($categories as $category) { ?>
        <tr>
            <th colspan="1000">
                <? echo $category->name ?>
            </th>
        </tr>
        <? foreach ($methods as $method) { ?>
            <tr>
                <? foreach ($method as $key => $col) {
                    if($method->category_id != $category->id)
                        continue 2;
                    if ($key == 'id' || $key == 'category_id' || $key == 'version_desc' || $key == 'page_id')
                        continue;
                    if ($key == 'version_id') {
                        $versions_id = explode(';', $col);
                        $pages_id = explode(';', $method->page_id);
                        foreach ($versions as $version) {
                            foreach ($versions_id as $version_key => $version_id) {
                                if ($version->id === $version_id) {
                                    $version_desc = get_post_field('post_content', $pages_id[$version_key], 'display');
                                    ?>
                                    <td class="version-support">
                                        <strong>
                                            <a href="<? echo get_page_link($pages_id[$version_key]) ?>"
                                               title="<? echo !empty($version_desc) ? esc_html($version_desc) : 'Missing description' ?>">
                                                &#10003;
                                            </a>
                                        </strong>
                                    </td>
                                    <? continue 2;
                                }
                            } ?>
                            <td class="version-not-support">
                                <strong>X</strong>
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