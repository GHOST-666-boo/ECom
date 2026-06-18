import Link from 'next/link';

/**
 * Global 404 Not Found Component
 */
export default function NotFound() {
  return (
    <div className="min-h-screen flex items-center justify-center" style={{ background: '#fcf9f8' }}>
      <div className="text-center p-12 max-w-md bg-white rounded-lg shadow-sm border border-[#cfc5bc]">
        <p className="text-6xl mb-6 font-light" style={{ color: '#cfc5bc', fontFamily: 'Noto Serif, serif' }}>
          404
        </p>
        <h1 className="text-3xl italic mb-4 text-[#463f38]" style={{ fontFamily: 'Noto Serif, serif' }}>
          Object Not Found
        </h1>
        <div className="w-12 h-px bg-[#4c3e25] mx-auto mb-6"></div>
        <p className="text-sm text-gray-600 mb-8 leading-relaxed" style={{ fontFamily: 'Manrope, sans-serif' }}>
          The path you have navigated to does not exist or has been archived. Let us guide you back to our curated collections.
        </p>
        <Link
          href="/products"
          className="inline-block px-8 py-3 text-xs uppercase tracking-widest font-bold transition-opacity hover:opacity-90"
          style={{
            background: 'linear-gradient(135deg, #463f38 0%, #5e564f 100%)',
            color: '#ffffff',
            fontFamily: 'Manrope, sans-serif'
          }}
        >
          Return to Shop
        </Link>
      </div>
    </div>
  );
}
