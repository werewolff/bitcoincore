<table class="">
    <thead>
    <tr>
        <th scope="col" class="">
            <span>Название</span>
        </th>
        <? if ($tbl_name == BTCPLG_TBL_METHODS) { ?>
            <? foreach ($versions as $version) { ?>
                <th scope="col" class="">
                    <span><? echo $version->name; ?></span>
                </th>
            <? }
        } ?>
    </tr>
    </thead>
    <tbody>
    <? foreach ($data as $row) {
        $row_was_add = false;
        ?>
        <tr class="">
            <? foreach ($row as $key => $col) {
                if($row->category_id != $category->id)
                    continue 2;
                if ($key == 'id' || $key == 'category_id' || $key == 'version_desc' || $key == 'page_id')
                    continue;
                if ($key == 'version_id') {
                    $versions_id = explode(';', $col);
                    $pages_id = explode(';', $row->page_id);
                    foreach ($versions as $version) {
                        foreach ($versions_id as $version_key => $version_id) {
                            if ($version->id === $version_id) {
                                $version_desc = get_post_field('post_content', $pages_id[$version_key], 'display');
                                ?>
                                <td class="version-support">
                                    <strong>
                                        <a href="<? echo get_page_link($pages_id[$version_key]) ?>"
                                           title="<? echo !empty($version_desc) ? esc_html($version_desc) : 'Отсутствует описание' ?>">
                                            <? echo !empty($version_desc) ? substr($version_desc, 0, 160) : 'Отсутствует описание' ?>
                                        </a>
                                    </strong>
                                </td>
                                <? continue 2;
                            }
                        } ?>
                        <td class="version-not-support">
                            <strong>Не поддерживается</strong>
                        </td>
                        <?
                    }
                    continue;
                }
                ?>
                <td class="">
                    <strong>
                        <? if ($tbl_name == BTCPLG_TBL_VERSIONS && $key == 'name') { ?>
                            <a class="" href="<? echo get_page_link($row->page_id) ?>"><? echo $col ?></a>
                        <? } else
                            echo $col;
                        ?>
                    </strong>
                    <? if ($row->name == $col) { ?>
                        <div class="row-actions">
                            <button class="submit-delete edit-bar-btn" target="edit-bar-<? echo $row->category_id . '-';
                            echo $row->id; ?>">
                                Изменить
                            </button>
                            <span> | </span>
                            <form method="post">
                                <input hidden name="id" value="<? echo $row->id; ?>"/>
                                <input hidden name="category_id" value="<? echo $row->category_id ?>"/>
                                <input class="submit-delete" type="submit" name="action" value="Удалить"/>
                            </form>
                        </div>
                    <? }
                    $row_was_add = true ?>
                </td>
            <? }; ?>
        </tr>
        <? if ($row_was_add) { ?>
            <tr class="edit-bar" id="edit-bar-<? echo $row->category_id . '-' . $row->id; ?>">
                <td colspan="1000">
                    <? include(BTCPLUGIN__DIR . 'views/edit.php'); ?>
                </td>
            </tr>
        <? } ?>
    <? } ?>
    </tbody>
</table>