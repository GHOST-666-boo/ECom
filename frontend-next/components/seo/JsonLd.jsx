import React from 'react';

/**
 * JsonLd Component
 * 
 * Injects structured schema markup (JSON-LD) for SEO.
 */
export default function JsonLd({ data }) {
  if (!data) return null;

  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(data) }}
    />
  );
}
