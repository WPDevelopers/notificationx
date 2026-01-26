import GenerateButton from "./GenerateButton";
import GenerationControl from "./GenerationControl";
import PromptInput from "./PromptInput";

const PromptSection = () => (
  <div className="nx-prompt-section">
    <PromptInput />
    <div className="nx-prompt-actions">
      <GenerationControl />
      <GenerateButton />
    </div>
  </div>
);

export default PromptSection;
