import React, { useMemo } from 'react';
import { useNotificationXContext } from '../hooks';
import { __ } from '@wordpress/i18n';
import { useBuilderContext } from 'quickbuilder';

const NxBarPresets = () => {
    const { assets } = useNotificationXContext();
    const { values } = useBuilderContext() || {};

    const basePath = `${assets.admin}/images/extensions/themes`;

    const previewUrl = useMemo(() => {
        if (!values) return '';

        // Elementor Path
        if (values.is_elementor && values.elementor_id && values.elementor_bar_theme) {
            return `${basePath}/bar-elementor/${values.elementor_bar_theme}.jpg`;
        }

        // Gutenberg Path
        if (values.is_gutenberg && values.gutenberg_bar_theme) {
            const specialMap = {
                'theme-five': 'nx-bar-theme-one.jpg',
                'theme-six': 'nx-bar-theme-two.jpg',
                'theme-seven': 'nx-bar-theme-three.jpg',
            };

            return specialMap[values.gutenberg_bar_theme]
                ? `${basePath}/${specialMap[values.gutenberg_bar_theme]}`
                : `${basePath}/bar-gutenberg/${values.gutenberg_bar_theme}.png`;
        }

        // Fallback
        return values.preview_url || '';
    }, [values, basePath]);

    const showEmptyState =
        values?.is_gutenberg &&
        !values?.elementor_id &&
        !values?.gutenberg_id;

    return (
        <div className="nxbar-presets">
            {showEmptyState ? (
                <div className="nxbar-presets-empty-state">
                    <img src={`${assets.admin}images/new-img/nxbar-empty-state.webp`} alt="" />
                    <h4>{__('Create with Your Preferred Builder/Editor', 'notificationx')}</h4>
                </div>
            ) : (
                <div className="nxbar-selected-presets">
                    <div className="nxbar-selected-presets-gutenberg">
                        <img src={previewUrl} alt="" />
                    </div>
                </div>
            )}
        </div>
    );
};

export default NxBarPresets;
