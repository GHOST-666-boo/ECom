'use client';

import { useEffect } from 'react';
import Link from 'next/link';

/**
 * ProductDetailError Boundary Component
 */
export default function ProductDetailError({ error, reset }) {
  useEffect(() => {
    console.error('Product Detail Page Error:', error);
  }, [error]);

  return (
    <div className="min-h-[80vh] flex items-center justify-center" style={{ background: '#fcf9f8' }}>
      <div className="text-center p-8 max-w-md bg-white rounded-lg shadow-sm border border-[#cfc5bc]">
        <span className="text-4xl mb-4 block">⚠️</span>
        <h2 className="text-2xl font-semibold mb-2 text-gray-800" style={{ fontFamily: 'Noto Serif, serif' }}>
          Failed to load product details
        </h2>
        <p className="text-sm text-gray-600 mb-6" style={{ fontFamily: 'Manrope, sans-serif' }}>
          {error?.message || 'We encountered an error fetching this item.'}
        </p>
        <div className="flex justify-center gap-4">
          <button
            onClick={() => reset()}
            className="px-6 py-2 bg-[#4c3e25] text-white rounded font-medium hover:opacity-90 transition-opacity"
          >
            Try Again
          </button>
          <Link
            href="/products"
            className="px-6 py-2 border border-[#cfc5bc] text-[#4c3e25] rounded font-medium hover:bg-gray-50 transition-colors"
          >
            Back to Shop
          </Link>
        </div>
      </div>
    </div>
  );
}
