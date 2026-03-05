import React, { useContext } from 'react';
import { applyFilters } from '@wordpress/hooks';
import { useBuilderContext } from 'quickbuilder'
import ProAlertForBuildWithAI from '../components/ProAlertForBuildWithAI';
import BuildWithAIVersionCompare from '../components/BuildWithAIVersionCompare';
import { compareVersions } from '../core/functions';

const BuildWithAIContent = ({builderContext}) => {
    const pro_version = builderContext?.pro_version;
    return (
      <>
        {
          builderContext?.is_pro_active &&
          compareVersions(pro_version, '3.0.7') <= 0 && 
          <BuildWithAIVersionCompare />
        }

        { !builderContext?.is_pro_active && <ProAlertForBuildWithAI/> }
        {applyFilters(
          'nx_build_ai_render',
          null,
          { builderContext }
        )}
      </>
    );
};

const BuildWithAI = () => {
  const builderContext = useBuilderContext();
    
  return (
      <div className={`nx-build-ai ${!builderContext?.is_pro_active ? 'nx-build-ai-free-bg' : ''}`}>
        <BuildWithAIContent builderContext={builderContext} />
      </div>
  );
};

export default BuildWithAI;
