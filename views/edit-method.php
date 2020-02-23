<!--Форма изменения метода-->
<form method="post">
    <div class="row">
        <div class="form-edit_col-1 col-auto">
            <span>Изменить метод</span>
            <label>
                <span>Название</span>
                <input hidden name="id" value="<? echo $method->id ?>"/>
                <input hidden name="prev_versions" value="<? echo $method->version_id ?>"/>
                <input hidden name="prev_name" value="<? echo $method->name ?>"/>
                <input name="name" value="<? echo $method->name ?>" required/>
            </label>
            <input hidden name="prev_category_id" value="<? echo $method->category_id ?>"/>
            <select name="category_id" required>
                <option value="">--------Выберите категорию--------</option>
                <? foreach ($categories as $c) { ?>
                    <option <? echo ($c->id == $method->category_id) ? 'selected' : '' ?>
                            value="<? echo $c->id ?>"><? echo $c->name ?></option>
                <? } ?>
            </select>
        </div>

        <div class="form-edit_col-2 col-auto col-lg-8">
            <span>Версии</span>
            <? foreach ($versions as $version) {
                $checked = false;
                $versions_id = explode(';', $method->version_id);
                $pages_id = explode(';', $method->page_id);
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
    </div>
    <div class="submit-edit">
        <button
                type="button"
                class="button cancel alignleft btn-hide-edit"
                target="edit-bar-<? echo $method->id ?>">
            Отмена
        </button>
        <button class="button button-primary alignright" type="submit" name="action" value="edit_method">Изменить
        </button>
    </div>
</form>