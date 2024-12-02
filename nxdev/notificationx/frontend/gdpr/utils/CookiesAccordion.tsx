import React, { useState } from 'react';

const CookiesAccordion = () => {
  const [openIndex, setOpenIndex] = useState<number | null>(null);

  // Toggle the accordion item
  const toggleAccordion = (index: number) => {
    setOpenIndex((prevIndex) => (prevIndex === index ? null : index));
  };

  // Sample accordion data
  const accordionItems = [
    { id: 1, title: 'Accordion Item 1', content: 'Content for item 1' },
    { id: 2, title: 'Accordion Item 2', content: 'Content for item 2' },
    { id: 3, title: 'Accordion Item 3', content: 'Content for item 3' },
    { id: 4, title: 'Accordion Item 4', content: 'Content for item 4' },
    { id: 5, title: 'Accordion Item 5', content: 'Content for item 5' },
  ];

  return (
    <div>
      {accordionItems.map((item, index) => (
        <div key={item.id}>
          {/* Accordion Header */}
          <div
            onClick={() => toggleAccordion(index)}
            role="button"
            aria-expanded={openIndex === index}
          >
            <span>{item.title}</span>
            {/* Icon changes based on open state */}
            <span>{openIndex === index ? '🔽' : '🔼'}</span>
          </div>

          {/* Accordion Content */}
          {openIndex === index && (
            <div>
              <p>{item.content}</p>
            </div>
          )}
        </div>
      ))}
    </div>
  );
};

export default CookiesAccordion;
