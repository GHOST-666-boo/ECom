import { Link, Outlet } from 'react-router-dom';
import { useState, useEffect } from 'react';
import useAuthStore from '../stores/authStore';
import useCartStore from '../stores/cartStore';
import AuthModal from './AuthModal';
import NewsletterForm from './NewsletterForm';
import axios from '../lib/axios';

export default function Layout() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);

  const { isAuthenticated, logout } = useAuthStore();
  const { itemCount, fetchCart } = useCartStore();

  useEffect(() => {
    if (isAuthenticated) fetchCart();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isAuthenticated]);

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
      setIsMenuOpen(false);
    }
  };

  return (
    <div className="min-h-screen flex flex-col" style={{ background: '#fcf9f8', color: '#1b1b1c' }}>

      {/* ── Navigation ── */}
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
        <div className="flex justify-between items-center w-full px-8 py-4 max-w-screen-2xl mx-auto">

          {/* Logo */}
          <Link
            to="/"
            className="text-2xl tracking-tighter"
            style={{ fontFamily: 'Noto Serif, serif', fontStyle: 'italic', color: '#4c3e25' }}
          >
            Artisan Kala
          </Link>

          {/* Desktop Nav Links */}
          <nav className="hidden md:flex items-center gap-10">
            {[
              { to: '/', label: 'Home' },
              { to: '/products', label: 'Collection' },
            ].map(({ to, label }) => (
              <Link
                key={to}
                to={to}
                className="font-label text-sm tracking-wide transition-colors duration-200"
                style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
                onMouseEnter={e => (e.target.style.color = '#463f38')}
                onMouseLeave={e => (e.target.style.color = '#4d453f')}
              >
                {label}
              </Link>
            ))}
          </nav>

          {/* Right Icons */}
          <div className="flex items-center gap-5">

            {/* Cart */}
            <Link
              to="/cart"
              className="relative p-2 rounded-full transition-all duration-200"
              style={{ color: '#463f38' }}
              onMouseEnter={e => (e.currentTarget.style.background = '#f6f3f2')}
              onMouseLeave={e => (e.currentTarget.style.background = 'transparent')}
              title="Cart"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                  d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
              </svg>
              {itemCount > 0 && (
                <span
                  className="absolute top-0 right-0 w-4 h-4 flex items-center justify-center text-[9px] font-bold"
                  style={{ background: '#4c3e25', color: '#fff', borderRadius: '50%' }}
                >
                  {itemCount > 9 ? '9+' : itemCount}
                </span>
              )}
            </Link>

            {/* User */}
            <div className="relative">
              <button
                onClick={() => isAuthenticated ? setIsMenuOpen(!isMenuOpen) : setIsAuthModalOpen(true)}
                className="p-2 rounded-full transition-all duration-200"
                style={{ color: '#463f38' }}
                onMouseEnter={e => (e.currentTarget.style.background = '#f6f3f2')}
                onMouseLeave={e => (e.currentTarget.style.background = 'transparent')}
                title="Account"
              >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </button>

              {isMenuOpen && isAuthenticated && (
                <div
                  className="absolute right-0 mt-2 w-48 py-2 z-50"
                  style={{
                    background: '#f6f3f2',
                    boxShadow: '0 16px 40px rgba(27,27,28,0.10)',
                    border: '1px solid rgba(207,197,188,0.25)',
                  }}
                >
                  {[
                    { to: '/profile', label: 'Profile' },
                    { to: '/orders', label: 'Orders' },
                    { to: '/addresses', label: 'Addresses' },
                  ].map(({ to, label }) => (
                    <Link
                      key={to}
                      to={to}
                      className="block px-4 py-2 text-sm transition-colors"
                      style={{ color: '#1b1b1c', fontFamily: 'Manrope, sans-serif' }}
                      onClick={() => setIsMenuOpen(false)}
                      onMouseEnter={e => (e.target.style.background = '#eae7e7')}
                      onMouseLeave={e => (e.target.style.background = 'transparent')}
                    >
                      {label}
                    </Link>
                  ))}
                  <div style={{ height: '1px', background: 'rgba(207,197,188,0.3)', margin: '6px 0' }} />
                  <button
                    className="block w-full text-left px-4 py-2 text-sm transition-colors"
                    style={{ color: '#1b1b1c', fontFamily: 'Manrope, sans-serif' }}
                    onClick={handleLogout}
                    onMouseEnter={e => (e.target.style.background = '#eae7e7')}
                    onMouseLeave={e => (e.target.style.background = 'transparent')}
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
              onClick={() => setIsMenuOpen(!isMenuOpen)}
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </div>
        </div>

        {/* Mobile Nav */}
        {isMenuOpen && (
          <nav
            className="md:hidden px-8 pb-6 space-y-1"
            style={{ borderTop: '1px solid rgba(207,197,188,0.18)' }}
          >
            {[
              { to: '/', label: 'Home' },
              { to: '/products', label: 'Collection' },
              ...(isAuthenticated ? [
                { to: '/profile', label: 'Profile' },
                { to: '/orders', label: 'Orders' },
                { to: '/addresses', label: 'Addresses' },
              ] : []),
            ].map(({ to, label }) => (
              <Link
                key={to}
                to={to}
                className="block py-2 text-sm tracking-wide"
                style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
                onClick={() => setIsMenuOpen(false)}
              >
                {label}
              </Link>
            ))}
            {isAuthenticated && (
              <button
                className="block w-full text-left py-2 text-sm tracking-wide"
                style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
                onClick={handleLogout}
              >
                Logout
              </button>
            )}
          </nav>
        )}
      </header>

      {/* ── Main Content ── */}
      <main className="flex-grow pt-[73px]">
        <Outlet />
      </main>

      {/* ── Footer ── */}
      <footer style={{ background: '#f6f3f2', borderTop: '1px solid rgba(207,197,188,0.20)' }}>
        <div className="max-w-screen-2xl mx-auto px-8 py-16">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-12 mb-12">

            {/* Brand */}
            <div>
              <p
                className="text-2xl mb-3 italic"
                style={{ fontFamily: 'Noto Serif, serif', color: '#4c3e25' }}
              >
                Artisan Kala
              </p>
              <p
                className="text-xs tracking-widest uppercase leading-relaxed"
                style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
              >
                Handcrafted with intention.<br />
                Connecting artisans &amp; collectors.
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
              © 2024 Artisan Kala. Forged with Intention.
            </p>
            <div className="flex gap-8">
              {['Products', 'Cart', 'Orders', 'Profile'].map((label) => (
                <Link
                  key={label}
                  to={`/${label.toLowerCase()}`}
                  className="text-[10px] tracking-widest uppercase transition-colors duration-300"
                  style={{
                    color: '#7e766e',
                    fontFamily: 'Manrope, sans-serif',
                    textDecoration: 'underline',
                    textUnderlineOffset: '4px',
                    textDecorationColor: 'rgba(207,197,188,0.30)',
                  }}
                  onMouseEnter={e => (e.target.style.color = '#4c3e25')}
                  onMouseLeave={e => (e.target.style.color = '#7e766e')}
                >
                  {label}
                </Link>
              ))}
            </div>
          </div>
        </div>
      </footer>

      {/* Auth Modal */}
      <AuthModal isOpen={isAuthModalOpen} onClose={() => setIsAuthModalOpen(false)} />
    </div>
  );
}
