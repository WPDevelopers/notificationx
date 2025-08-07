import React from 'react'
import { useNotificationXContext } from '../hooks';
import { __ } from '@wordpress/i18n';
import { useBuilderContext } from 'quickbuilder';

const NxBarPresets = () => {
    const nxContext = useNotificationXContext();
    const builderContext = useBuilderContext();
    const values = builderContext?.values;

    return (
        <div className='nxbar-presets'>
            { values?.is_elementor && values?.is_gutenberg && !values?.elementor_id && !values?.gutenberg_id ?
                <div className="nxbar-presets-empty-state">
                    <img src={ `${nxContext.assets.admin}images/new-img/nxbar-empty-state.png` } />
                    <h4>{ __('Create with Your Preferred Builder/Editor','notificationx') }</h4>
                </div>
            : 
            <div className='nxbar-selected-presets'>
                <div className='nxbar-selected-presets-gutenberg'>
                    <img src="" alt="" />
                </div>
            </div>
            }
        </div>
    )
}

export default NxBarPresets
