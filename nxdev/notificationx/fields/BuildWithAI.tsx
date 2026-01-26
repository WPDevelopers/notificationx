import React from 'react'
import BuildHeader from '../components/build-with-ai/BuildHeader'
import SuggestionGrid from '../components/build-with-ai/SuggestionGrid'
import PromptSection from '../components/build-with-ai/PromptSection'
import NavigationTab from '../components/build-with-ai/NavigationTab'

const BuildWithAI = () => {
  return (
    <div className="nx-build-ai">
      <NavigationTab/>
      <BuildHeader />
      <SuggestionGrid />
      <PromptSection />
    </div>
  )
}

export default BuildWithAI
