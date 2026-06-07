import React, { useMemo } from 'react';
import { useNotificationXContext } from '../hooks';
import { __ } from '@wordpress/i18n';
import { useBuilderContext } from 'quickbuilder';

/**
 * Custom-tab preview for the Exit Intent Popup (Elementor) design.
 *
 * Mirrors the Notification Bar's NxBarPresets behaviour but is scoped to the
 * Exit Intent source (`exit_intent_custom`). Reuses the existing `nxbar-presets`
 * markup/classes so it inherits the shared design-system styling.
 *
 * Preview resolution:
 *   - Once an Elementor design is linked (`is_elementor` + `elementor_id`),
 *     show the built-in design screenshot for the chosen seed theme under
 *     `exit-intent/exit-intent-{theme}.png`. These are local, always-present
 *     assets, so the preview paints in a single request (no 404 round-trip).
 *     When bespoke `exit-intent-elementor/{theme}.png` captures are produced,
 *     switch `previewUrl` to that folder.
 *   - Before any design is linked, show the empty-state illustration.
 *
 * Images load eagerly (this control is shown the moment the Custom tab opens,
 * so lazy-loading would only delay the paint).
 */
const ExitIntentPreset = () => {
    const { assets } = useNotificationXContext();
    const { values } = useBuilderContext() || {};

    // Normalize trailing slash so we never emit `//images` or `imagesfoo`.
    const adminBase = String(assets?.admin || '').replace(/\/$/, '');
    const basePath = `${adminBase}/images/extensions/themes`;

    const theme = values?.elementor_exit_theme || 'theme-one';

    const previewUrl = useMemo(() => {
        if (values?.is_elementor && values?.elementor_id) {
            return `${basePath}/exit-intent/exit-intent-${theme}.png`;
        }
        return '';
    }, [values?.is_elementor, values?.elementor_id, theme, basePath]);

    const showEmptyState = !values?.elementor_id;

    return (
        <div className="nxbar-presets nx-exit-intent-presets">
            {showEmptyState ? (
                <div className="nxbar-presets-empty-state">
                    <img src={`${adminBase}/images/new-img/nxbar-empty-state.webp`} alt="" loading="eager" decoding="async" />
                    <h4>{__('Create your Exit Intent Popup with Elementor', 'notificationx')}</h4>
                </div>
            ) : (
                <div className="nxbar-selected-presets">
                    <div className="nxbar-selected-presets-gutenberg">
                        <img src={previewUrl} alt="" loading="eager" decoding="async" />
                    </div>
                </div>
            )}
        </div>
    );
};

export default ExitIntentPreset;
