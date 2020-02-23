<div class="control-bar-add">
    <button type="button" class="button button-primary" data-toggle="collapse" data-target="#add-version">Добавить
        версию
    </button>
    <button type="button" class="button button-primary" data-toggle="collapse" data-target="#add-category">Добавить
        категорию
    </button>
    <button type="button" class="button button-primary" data-toggle="collapse" data-target="#add-method">Добавить
        метод
    </button>
    <div class="collapse" id="add-version">
        <form method="post">
            <input hidden name="blockchain_id" value="<? echo $blockchain_id ?>"/>
            <label style="display: inline-block; padding: 10px">
                <input name="name" placeholder="Название версии"
                       required/>
            </label>
            <div class="submit-add" style="display: inline-block; margin: 0; vertical-align: middle">
                <button class="button button-primary" name="action" value="add_version">Добавить версию</button>
            </div>
        </form>
    </div>
    <div class="collapse" id="add-category">
        <form method="post">
            <label style="display: inline-block; padding: 10px">
                <input name="name" placeholder="Название категории"
                       required/>
            </label>
            <div class="submit-add" style="display: inline-block; margin: 0; vertical-align: middle">
                <button class="button button-primary" name="action" value="add_category">Добавить категорию</button>
            </div>
        </form>
    </div>
    <div class="collapse form-add" id="add-method">
        <form method="post">
            <div class="row">
                <div class="form-add_col-1 col-auto">
                    <span>Добавить метод</span>
                    <label>
                        <span>Название</span>
                        <input name="name" placeholder="Название метода" required/>
                    </label>
                    <select name="category_id" required>
                        <option value="">--------Выберите категорию--------</option>
                        <? foreach ($categories as $c) { ?>
                            <option value="<? echo $c->id ?>"><? echo $c->name ?></option>
                        <? } ?>
                    </select>
                </div>
                <div class="form-add_col-2 col-auto col-lg-8">
                    <span>Версии</span>
                    <? foreach ($versions as $version) { ?>
                        <div>
                            <input class="version_checkbox" type="checkbox" name="versions[<? echo $version->id ?>]"
                                   value="true"/>
                            <label>
                                <span><? echo $version->name ?></span>
                            </label>
                            <div class="version_desc">
                            <textarea rows="5" name="versions_desc[<? echo $version->id ?>]"
                                      placeholder="Описание метода <? echo $version->name ?>"></textarea>
                            </div>
                        </div>
                    <? } ?>
                </div>
            </div>
            <div class="submit-add">
                <button type="button" class="button cancel alignleft btn-hide-add">
                    Отмена
                </button>
                <button class="button button-primary alignright" name="action" value="add_method">Добавить
                    метод
                </button>
            </div>
        </form>
    </div>
</div>