import React, { useContext } from 'react';
import { applyFilters } from '@wordpress/hooks';
import { useBuilderContext } from 'quickbuilder'

const BuildWithAIContent = () => {
    const builderContext = useBuilderContext();
    
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

const BuildWithAI = () => {
  return (
      <div className="nx-build-ai">
        <BuildWithAIContent />
      </div>
  );
};

export default BuildWithAI;
