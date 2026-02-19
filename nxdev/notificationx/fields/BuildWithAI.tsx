import React, { useContext } from 'react';
import { applyFilters } from '@wordpress/hooks';
import { useBuilderContext } from 'quickbuilder'
import ProAlertForBuildWithAI from '../components/ProAlertForBuildWithAI';

const BuildWithAIContent = () => {
    const builderContext = useBuilderContext();
    
    return (
      <>
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
  return (
      <div className="nx-build-ai">
        <BuildWithAIContent />
      </div>
  );
};

export default BuildWithAI;
