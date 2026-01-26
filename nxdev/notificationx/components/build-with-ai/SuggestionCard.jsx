const SuggestionCard = ({ title, description }) => (
  <div className="nx-suggestion-card">
    <h4>{title}</h4>
    <p>{description}</p>
    <span className="arrow">›</span>
  </div>
);

export default SuggestionCard;