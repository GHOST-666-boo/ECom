'use client';

import { useEffect } from 'react';

/**
 * ProductsError Boundary Component
 */
export default function ProductsError({ error, reset }) {
  useEffect(() => {
    console.error('Products Page Error:', error);
  }, [error]);

  return (
    <div className="min-h-[60vh] flex items-center justify-center" style={{ background: '#fcf9f8' }}>
      <div className="text-center p-8 max-w-md bg-white rounded-lg shadow-sm border border-[#cfc5bc]">
        <span className="text-4xl mb-4 block">⚠️</span>
        <h2 className="text-2xl font-semibold mb-2 text-gray-800" style={{ fontFamily: 'Noto Serif, serif' }}>
          Failed to load collection
        </h2>
        <p className="text-sm text-gray-600 mb-6" style={{ fontFamily: 'Manrope, sans-serif' }}>
          {error?.message || 'An unexpected error occurred while retrieving products.'}
        </p>
        <button
          onClick={() => reset()}
          className="px-6 py-2 bg-[#4c3e25] text-white rounded font-medium hover:opacity-90 transition-opacity"
        >
          Try Again
        </button>
      </div>
    </div>
  );
}
