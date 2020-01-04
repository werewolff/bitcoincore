
<? if ($tbl_name == BTCPLG_TBL_METHODS) { ?>
    <!--Форма изменения метода-->
        <form method="post">
            <div class="form-add_col-1">
                <span>Добавить метод</span>
                <label>
                    <span>Название</span>
                    <input name="name" value="<? echo $row->name?>" required/>
                </label>
                <select name="category_id" required>
                    <option value="">--------Выберите категорию--------</option>
                    <? foreach ($categories as $c) { ?>
                        <option <? echo ($c->id == $row->category_id) ? 'selected' : ''?> value="<? echo $c->id ?>"><? echo $c->name ?></option>
                    <? } ?>
                </select>
            </div>
            <div class="form-add_col-2">
                <span>Версии</span>
                <? foreach ($versions as $version) { ?>
                    <div>
                        <input class="version_checkbox" type="checkbox" name="versions[<? echo $version->id ?>]"
                               value="true"/>
                        <label>
                            <span><? echo $version->name ?></span>
                        </label>
                        <div class="version_desc">
                            <textarea name="versions_desc[<? echo $version->id ?>]"
                                      placeholder="Описание метода <? echo $version->name ?>"></textarea>
                            <input class="custom-meta-checkbox" type="checkbox"/>
                            <label>
                                <span>Кастомные мета-данные</span>
                                <p>По умолчанию, мета-данные, будут сформированы автоматически из названия метода,
                                    версии и описания</p>
                            </label>
                            <div class="custom-meta">
                                <label>
                                    <span>Title</span>
                                    <input type="text" name="meta_title[<? echo $version->id ?>]"/>
                                </label>
                                <label>
                                    <span>Description</span>
                                    <textarea cols="50" rows="3"
                                              name="meta_description[<? echo $version->id ?>]"></textarea>
                                </label>
                            </div>
                        </div>
                    </div>
                <? } ?>
            </div>
            <div class="submit-add">
                <button
                        type="button"
                        class="button cancel alignleft btn-hide-add"
                        target="form-add<? echo ($tbl_name == BTCPLG_TBL_METHODS) ? '-' . $category->id : '' ?>">
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
            <input name="name" value="<? echo $row->name; ?>"/>
            <input class="" type="submit" name="action" value="Изменить"/>
        </form>
<? } ?>