<div class="control-bar-add">
    <button type="button" class="button button-primary" data-toggle="collapse" data-target="#add-blockchain">Добавить
    </button>
    <div class="collapse" id="add-blockchain">
        <form method="post">
            <label style="display: inline-block; padding: 10px">
                <input name="name" placeholder="Название блокчейна"
                       required/>
            </label>
            <div class="submit-add" style="display: inline-block; margin: 0; vertical-align: middle">
                <button class="button button-primary" name="action" value="add_blockchain">Добавить блокчейн</button>
            </div>
        </form>
    </div>
</div>
<table class="table table-sm table-bordered table-hover table-sticky">
    <thead class="thead-light">
    <tr>
        <th>
            Название блокчейна
        </th>
    </tr>
    </thead>
    <tbody>
    <? foreach ($blockchains as $blockchain) { ?>
        <tr>
            <td>
                <a href="<? echo get_page_link($blockchain->page_id)?>"><? echo $blockchain->name?></a>
                <div class="control-bar control-bar-blockchain">
                    <span class="dashicons dashicons-edit" id="edit-blockchain-<? echo $blockchain->id?>"></span>
                    <span class="dashicons dashicons-no-alt" id="delete-blockchain-<? echo $blockchain->id?>"></span>
                </div>
                <div class="control-bar" style="display: none">
                    <form method="post">
                        <input hidden name="id" value="<? echo $blockchain->id; ?>"/>
                        <input hidden name="prev_name" value="<? echo $blockchain->name; ?>"/>
                        <input class="form-control input-edit-blockchain" name="name" value="<? echo $blockchain->name; ?>"/>
                        <button class="btn-edit btn-edit-success" name="action" value="edit_blockchain">
                            <span class="dashicons dashicons-yes"></span>
                        </button>
                        <button class="btn-edit btn-edit-cancel" type="button" value="cancel">
                            <span class="dashicons dashicons-minus"></span>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    <? } ?>
    </tbody>
</table>