import {registerBlockType} from '@wordpress/blocks';
import {RichText, PlainText} from '@wordpress/block-editor';


registerBlockType('bitcoincore/block-bitcoincore', {
    title: 'Блок приветствия',
    icon: 'editor-table',
    category: 'common',
    supports: {
        className: false
    },
    attributes: {
        title: {
            type: 'string',
            default: 'Welcome!'
        },
        startText: {
            type: 'string',
            default: 'RPC API Blockchains Documentation' +
            'help you, at least we hope so. ' +
            'To start, select the blockchain in the menu on the left',
        },
        steps: {
            type: 'string',
            default: ''
        },
        endText: {
            type: 'string',
            default: 'If the documentation comes in handy, don’t forget about the donation.<br> Happy coding!'
        }
    },
    edit: (props) => {
        const {attributes: {title, startText, steps, endText}, setAttributes, className} = props;
        return (
            <div className="welcome-block-edit">
                <div>
                    <RichText
                        tagName="h4"
                        onChange={(title) => setAttributes({title})}
                        placeholder="Enter title here"
                        value={title}
                    />
                    <PlainText
                        onChange={(startText) => setAttributes({startText})}
                        placeholder="Enter text here"
                        value={startText}
                    />
                    <RichText
                        tagName="ul"
                        multiline="li"
                        onChange={(steps) => setAttributes({steps})}
                        placeholder="Enter list here"
                        value={steps}
                    />
                    <RichText
                        tagName="p"
                        onChange={(endText) => setAttributes({endText})}
                        placeholder="Enter text here"
                        value={endText}
                    />
                </div>
            </div>
        );
    },

    save: (props) => {
        const {attributes: {title, startText, steps, endText}} = props;
        return (
            <div className="welcome-block">
                <div>
                    {
                        (title)
                            ? <RichText.Content tagName="h4" value={title}/>
                            : ''
                    }
                    {
                        (startText)
                            ? <p> {startText} </p>
                            : ''
                    }
                    {
                        (steps !== '<li></li>')
                            ? <RichText.Content tagName="ul" value={steps}/>
                            : ''
                    }
                    {
                        (endText)
                            ? <RichText.Content tagName="p" value={endText}/>
                            : ''
                    }
                </div>
            </div>
        );
    }
});