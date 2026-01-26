import SuggestionCard from "./SuggestionCard";

const SuggestionGrid = () => {
  const suggestions = [
    {
      title: 'Urgency / FOMO',
      description: 'Bold notification bar with high contrast and countdown timer.'
    },
    {
      title: 'Promotion',
      description: 'Promotional notification bar with gradient background and CTA.'
    },
    {
      title: 'Social Proof',
      description: 'Notification bar showing user count and rating icons.'
    },
    {
      title: 'SaaS',
      description: 'Compact dashboard notification bar with dismiss icon.'
    },
    {
      title: 'Premium',
      description: 'Premium notification bar using brand colors.'
    }
  ];

  return (
    <div className="nx-suggestion-grid">
      {suggestions.map((item, index) => (
        <SuggestionCard key={index} {...item} />
      ))}
    </div>
  );
};

export default SuggestionGrid;