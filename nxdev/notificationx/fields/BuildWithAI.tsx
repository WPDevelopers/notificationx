import React, { useContext } from 'react';
import { applyFilters } from '@wordpress/hooks';
import { useBuilderContext } from 'quickbuilder'
import ProAlertForBuildWithAI from '../components/ProAlertForBuildWithAI';
import BuildWithAIVersionCompare from '../components/BuildWithAIVersionCompare';
import { compareVersions } from '../core/functions';
import { useNotificationXContext } from '../hooks';

// Exit Intent's Build With AI needs Pro >= 3.1.4. On older Pro the feature
// can't render, so we show the "please update" notice for Exit Intent only.
//
// NOTE: detect Exit Intent from the FIELD type (`exit-intent-build_with_ai`),
// NOT `builderContext.values.type`. On a new/unsaved notification `values.type`
// lags behind the selected source, which would let outdated Pro render the
// interactive feature for Exit Intent. The field type is always accurate.

const isOutdatedPro = (builderContext, isExitIntent, pro_version) =>
  isExitIntent &&
  builderContext?.is_pro_active &&
  compareVersions(pro_version, '3.1.4') < 0;
const BuildWithAIContent = ({builderContext, isExitIntent}) => {
    const nxContext = useNotificationXContext();
    const pro_version = nxContext.pro_version;
    // Outdated Pro can't render the feature cleanly, so show only the notice —
    // do NOT let the Pro plugin's `nx_build_ai_render` output through, or the
    // broken live UI shows behind the warning.
    if (isOutdatedPro(builderContext, isExitIntent, pro_version)) {
        return <BuildWithAIVersionCompare />;
    }

    if (!builderContext?.is_pro_active) {
        return <ProAlertForBuildWithAI/>;
    }

    return (
        <>
            {applyFilters(
                'nx_build_ai_render',
                null,
                { builderContext }
            )}
        </>
    );
};

const BuildWithAI = (props) => {
  const builderContext = useBuilderContext();
  const isExitIntent = props?.type === 'exit-intent-build_with_ai';
  const nxContext = useNotificationXContext();
  const pro_version = nxContext.pro_version;
  // Both the free upsell and the outdated-Pro notice sit on top of the
  // dimmed Build With AI screenshot instead of the (broken/unrenderable)
  // live feature UI.
  const showPreviewBg =
    !builderContext?.is_pro_active || isOutdatedPro(builderContext, isExitIntent, pro_version);

  return (
      <div className={`nx-build-ai ${showPreviewBg ? 'nx-build-ai-free-bg' : ''}`}>
        <BuildWithAIContent builderContext={builderContext} isExitIntent={isExitIntent} />
      </div>
  );
};

export default BuildWithAI;
