import React, { useMemo } from 'react';
import { useNotificationXContext } from '../hooks';
import { __ } from '@wordpress/i18n';
import { useBuilderContext } from 'quickbuilder';

const NxBarPresets = () => {
    const { assets } = useNotificationXContext();
    const { values } = useBuilderContext() || {};

    const basePath = `${assets.admin}/images/extensions/themes`;

    // Elementor previews render the live design in an iframe (see
    // PressBar::render_elementor_bar_preview()); everything else stays an <img>.
    const isElementorPreview = values?.is_elementor && values?.elementor_id;

    const previewUrl = useMemo(() => {
        if (!values) return '';

        // Elementor: live preview of the actual Elementor-built bar.
        if (values.is_elementor && values.elementor_id) {
            return `${assets.home}?nx_bar_preview=${values.elementor_id}&_wpnonce=${assets.bar_preview_nonce || ''}`;
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
    }, [values, basePath, assets]);

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
                    <div
                        className="nxbar-selected-presets-gutenberg"
                        style={isElementorPreview ? { display: 'block', width: '100%', padding: '12px' } : undefined}
                    >
                        {isElementorPreview ? (
                            <iframe
                                className="nx-bar-elementor-preview-frame"
                                src={previewUrl}
                                scrolling="no"
                                style={{ width: '100%', border: 0, display: 'block', minHeight: '60px' }}
                            />
                        ) : (
                            <img src={previewUrl} alt="" />
                        )}
                    </div>
                </div>
            )}
        </div>
    );
};

export default NxBarPresets;
