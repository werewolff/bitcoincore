<? if ($tbl_name == BTCPLG_TBL_METHODS) { ?>
    <!--Форма изменения метода-->
    <form method="post">
        <div class="form-edit_col-1">
            <span>Изменить метод</span>
            <label>
                <span>Название</span>
                <input hidden name="id" value="<? echo $row->id ?>"/>
                <input hidden name="prev_versions" value="<? echo $row->version_id ?>"/>
                <input hidden name="prev_name" value="<? echo $row->name ?>"/>
                <input name="name" value="<? echo $row->name ?>" required/>
            </label>
            <input hidden name="prev_category_id" value="<? echo $row->category_id ?>"/>
            <select name="category_id" required>
                <option value="">--------Выберите категорию--------</option>
                <? foreach ($categories as $c) { ?>
                    <option <? echo ($c->id == $row->category_id) ? 'selected' : '' ?>
                            value="<? echo $c->id ?>"><? echo $c->name ?></option>
                <? } ?>
            </select>
        </div>
        <div class="form-edit_col-2">
            <span>Версии</span>
            <? foreach ($versions as $version) {
                $checked = false;
                $versions_id = explode(';', $row->version_id);
                $pages_id = explode(';', $row->page_id);
                $version_desc = '';
                $current_page_id = '';
                foreach ($versions_id as $version_key => $version_id) {
                    if ($version->id === $version_id) {
                        $checked = true;
                        $current_page_id = $pages_id[$version_key];
                        $version_desc = get_post_field('post_content', $current_page_id, 'display');
                    }
                }
                ?>

                <div>
                    <input class="version_checkbox" type="checkbox" name="versions[<? echo $version->id ?>]"
                           value="true" <? echo ($checked) ? 'checked' : '' ?> />
                    <label>
                        <span><? echo $version->name ?></span>
                    </label>
                    <div class="version_desc">
                        <input hidden name="prev_versions_desc[<? echo $version->id ?>]"
                               value="<? echo htmlspecialchars($version_desc) ?>"/>
                        <textarea rows="5"
                                  name="versions_desc[<? echo $version->id ?>]"><? echo $version_desc ?></textarea>
                    </div>
                </div>
            <? } ?>
        </div>
        <div class="submit-edit">
            <button
                    type="button"
                    class="button cancel alignleft btn-hide-edit"
                    target="edit-bar-<? echo $row->category_id . '-';
                    echo $row->id; ?>">
                Отмена
            </button>
            <input class="button button-primary alignright" type="submit" name="action" value="Изменить"/>
        </div>
    </form>
<? } else {
    ?>
    <!--Форма изменения таблиц категории и версии-->
    <form method="post">
        <input hidden name="id" value="<? echo $row->id; ?>"/>
        <input hidden name="prev_name" value="<? echo $row->name; ?>"/>
        <input name="name" value="<? echo $row->name; ?>"/>
        <input class="" type="submit" name="action" value="Изменить"/>
    </form>
<? } ?>