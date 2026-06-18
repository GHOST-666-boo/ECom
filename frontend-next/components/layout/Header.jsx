'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import useAuthStore from '../../stores/authStore';
import useCartStore from '../../stores/cartStore';
import { useHasHydrated } from '../../hooks/useHasHydrated';
import axios from '../../lib/axios';

/**
 * Header Component (Next.js adapted)
 * 
 * Top fixed navigation bar matching the design system:
 * - Scrolled state with shadows
 * - Desktop nav links with underlines
 * - Cart item count indicator
 * - User profile dropdown (desktop) and side menu drawer (mobile)
 */
export default function Header({ onAuthTrigger }) {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isProfileMenuOpen, setIsProfileMenuOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);

  const { isAuthenticated, logout } = useAuthStore();
  const { itemCount } = useCartStore();
  const hasHydrated = useHasHydrated();

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8);
    window.addEventListener('scroll', onScroll);
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  const handleLogout = async () => {
    try {
      await axios.post('/auth/logout');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      logout();
      setIsProfileMenuOpen(false);
      setIsMobileMenuOpen(false);
    }
  };

  return (
    <header
      className="fixed top-0 w-full z-50 transition-shadow duration-300"
      style={{
        background: 'rgba(252,249,248,0.82)',
        backdropFilter: 'blur(20px)',
        WebkitBackdropFilter: 'blur(20px)',
        boxShadow: scrolled ? '0 2px 24px rgba(27,27,28,0.07)' : 'none',
        borderBottom: '1px solid rgba(207,197,188,0.18)',
      }}
    >
      <div className="flex justify-between items-center w-full px-8 py-2 max-w-screen-2xl mx-auto">
        {/* Logo */}
        <Link href="/" className="flex items-center">
          <img
            src="/logo.png"
            alt="Vriddhi"
            className="h-14 object-contain"
          />
        </Link>

        {/* Desktop Nav Links */}
        <nav className="hidden md:flex items-center gap-10">
          {[
            { href: '/', label: 'Home' },
            { href: '/products', label: 'Collection' },
            { href: '/corporate-gifting', label: 'Corporate Gifting' },
            { href: '/contact', label: 'Contact' },
          ].map(({ href, label }) => (
            <Link
              key={href}
              href={href}
              className="font-label text-sm tracking-wide transition-colors duration-200 nav-link-underline"
              style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
            >
              {label}
            </Link>
          ))}
        </nav>

        {/* Right Icons */}
        <div className="flex items-center gap-5">
          {/* Cart */}
          <Link
            href="/cart"
            className="relative p-2 rounded-full transition-all duration-200"
            style={{ color: '#463f38' }}
            title="Cart"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            {hasHydrated && itemCount > 0 && (
              <span
                className="absolute top-0 right-0 w-4 h-4 flex items-center justify-center text-[9px] font-bold"
                style={{ background: '#4c3e25', color: '#fff', borderRadius: '50%' }}
              >
                {itemCount > 9 ? '9+' : itemCount}
              </span>
            )}
          </Link>

          {/* User */}
          <div className="relative hidden md:block">
            <button
              onClick={() => {
                if (hasHydrated && isAuthenticated) {
                  setIsProfileMenuOpen(!isProfileMenuOpen);
                } else {
                  onAuthTrigger();
                }
              }}
              className="p-2 rounded-full transition-all duration-200"
              style={{ color: '#463f38' }}
              title="Account"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </button>

            {isProfileMenuOpen && hasHydrated && isAuthenticated && (
              <div
                className="absolute right-0 mt-2 w-48 py-2 z-50"
                style={{
                  background: '#f6f3f2',
                  boxShadow: '0 16px 40px rgba(27,27,28,0.10)',
                  border: '1px solid rgba(207,197,188,0.25)',
                }}
              >
                {[
                  { href: '/profile', label: 'Profile' },
                  { href: '/orders', label: 'Orders' },
                  { href: '/addresses', label: 'Addresses' },
                ].map(({ href, label }) => (
                  <Link
                    key={href}
                    href={href}
                    className="block px-4 py-2 text-sm transition-colors"
                    style={{ color: '#1b1b1c', fontFamily: 'Manrope, sans-serif' }}
                    onClick={() => setIsProfileMenuOpen(false)}
                  >
                    {label}
                  </Link>
                ))}
                <div style={{ height: '1px', background: 'rgba(207,197,188,0.3)', margin: '6px 0' }} />
                <button
                  className="block w-full text-left px-4 py-2 text-sm transition-colors"
                  style={{ color: '#1b1b1c', fontFamily: 'Manrope, sans-serif' }}
                  onClick={handleLogout}
                >
                  Logout
                </button>
              </div>
            )}
          </div>

          {/* Mobile Hamburger */}
          <button
            className="md:hidden p-2"
            style={{ color: '#463f38' }}
            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
        </div>
      </div>

      {/* Mobile Nav Drawer */}
      {isMobileMenuOpen && (
        <nav
          className="md:hidden px-8 pb-6 space-y-1"
          style={{ borderTop: '1px solid rgba(207,197,188,0.18)' }}
        >
          {[
            { href: '/', label: 'Home' },
            { href: '/products', label: 'Collection' },
            { href: '/corporate-gifting', label: 'Corporate Gifting' },
            { href: '/contact', label: 'Contact' },
            ...(hasHydrated && isAuthenticated ? [
              { href: '/profile', label: 'Profile' },
              { href: '/orders', label: 'Orders' },
              { href: '/addresses', label: 'Addresses' },
            ] : []),
          ].map(({ href, label }) => (
            <Link
              key={href}
              href={href}
              className="block py-2 text-sm tracking-wide"
              style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
              onClick={() => setIsMobileMenuOpen(false)}
            >
              {label}
            </Link>
          ))}
          {hasHydrated && isAuthenticated ? (
            <button
              className="block w-full text-left py-2 text-sm tracking-wide"
              style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
              onClick={handleLogout}
            >
              Logout
            </button>
          ) : (
            <button
              className="block w-full text-left py-2 text-sm tracking-wide"
              style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
              onClick={() => {
                setIsMobileMenuOpen(false);
                onAuthTrigger();
              }}
            >
              Login / Sign Up
            </button>
          )}
        </nav>
      )}
    </header>
  );
}
