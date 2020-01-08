import {registerBlockType} from '@wordpress/blocks';

const blockStyle = {
    'padding': '30px',
    'font-size': '1.5em'
};

registerBlockType('bitcoincore/block-bitcoincore', {
    title: 'Отображение главной таблицы версий',
    icon: 'editor-table',
    category: 'widgets',
    edit() {
        return <div style={blockStyle}>Здесь будут построена таблица методов.</div>;
    },

    save() {
        return null;
    }
});