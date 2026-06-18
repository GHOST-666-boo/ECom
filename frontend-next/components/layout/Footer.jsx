'use client';

import Link from 'next/link';
import NewsletterForm from '../NewsletterForm';

/**
 * Footer Component (Next.js adapted)
 * 
 * Footer with brand, newsletter subscribe form, and footer navigation links.
 */
export default function Footer() {
  return (
    <footer style={{ background: '#f6f3f2', borderTop: '1px solid rgba(207,197,188,0.20)' }}>
      <div className="max-w-screen-2xl mx-auto px-8 py-16">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-12 mb-12">
          {/* Brand */}
          <div>
            <Link href="/" className="inline-block mb-3">
              <img
                src="/logo.png"
                alt="Vriddhi"
                className="h-12 object-contain"
              />
            </Link>
            <p
              className="text-xs tracking-widest uppercase leading-relaxed"
              style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
            >
              Handcrafted with intention.<br />
              Connecting creators &amp; collectors.
            </p>
          </div>

          {/* Newsletter */}
          <div className="md:col-span-2">
            <NewsletterForm />
          </div>
        </div>

        {/* Bottom Row */}
        <div
          className="flex flex-col md:flex-row justify-between items-center pt-8 gap-4"
          style={{ borderTop: '1px solid rgba(207,197,188,0.25)' }}
        >
          <p
            className="text-[10px] tracking-widest uppercase"
            style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
          >
            © 2024 Vriddhi. Forged with Intention.
          </p>
          <div className="flex gap-8">
            {['Products', 'Contact', 'Cart', 'Orders', 'Profile'].map((label) => (
              <Link
                key={label}
                href={`/${label.toLowerCase()}`}
                className="text-[10px] tracking-widest uppercase transition-colors duration-300"
                style={{
                  color: '#7e766e',
                  fontFamily: 'Manrope, sans-serif',
                  textDecoration: 'underline',
                  textUnderlineOffset: '4px',
                  textDecorationColor: 'rgba(207,197,188,0.30)',
                }}
              >
                {label}
              </Link>
            ))}
          </div>
        </div>
      </div>
    </footer>
  );
}
