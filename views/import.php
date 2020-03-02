<div class="import-form">
    <form method="post" enctype="multipart/form-data">
        <input name="btc_import_file" type="file" accept="application/json"/>
        <p class="description">Только файл JSON определенной структуры</p>
        <div>
            <label>
                <input type="checkbox" name="import_full_sync" value="true"/>
                <span>Полная синхронизация(Будут удалены данные не содержащиеся в файле)</span>
            </label>
            <label>
                <input type="checkbox" name="import_change_desc" value="true"/>
                <span>Обновлять описание методов</span>
            </label>
            <input class="button button-primary" type="submit" value="Импорт"/>
        </div>
    </form>
</div>