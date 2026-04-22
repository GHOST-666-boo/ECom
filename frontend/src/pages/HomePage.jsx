import { useEffect, useState } from 'react';
import { useLocation, Link } from 'react-router-dom';
import AuthModal from '../components/AuthModal';
import CategoryList from '../components/CategoryList';

/**
 * HomePage - Metallic Artisan (Oat Edition)
 * Mirrors the Stitch design: hero with editorial split,
 * bento featured grid, product gallery strip, newsletter CTA
 */
export default function HomePage() {
  const location = useLocation();
  const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);

  useEffect(() => {
    if (location.state?.loginRequired || new URLSearchParams(location.search).get('loginRequired')) {
      setIsAuthModalOpen(true);
    }
  }, [location]);

  return (
    <div style={{ background: '#fcf9f8', color: '#1b1b1c' }}>

      {/* ── Hero Section ── */}
      <section className="px-8 max-w-screen-2xl mx-auto py-16 md:py-24">
        <div className="grid grid-cols-1 md:grid-cols-12 gap-12 items-center">

          {/* Left: Copy */}
          <div className="md:col-span-6">
            <span
              className="uppercase tracking-[0.3em] text-xs font-semibold block mb-6"
              style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
            >
              Forged with Intention
            </span>
            <h1
              className="text-5xl md:text-7xl italic leading-tight mb-8"
              style={{
                fontFamily: 'Noto Serif, serif',
                letterSpacing: '-0.03em',
                color: '#1b1b1c',
              }}
            >
              Sacred Objects for<br />Modern Rituals.
            </h1>
            <p
              className="text-lg max-w-md mb-12 leading-relaxed"
              style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
            >
              Meticulously handcrafted metalwork and handicrafts that bridge ancient alchemy
              and contemporary design. Every piece is an ingot of history.
            </p>
            <div className="flex flex-col sm:flex-row gap-4">
              <Link
                to="/products"
                className="inline-flex items-center justify-center px-8 py-4 text-sm font-medium tracking-wide transition-opacity hover:opacity-90"
                style={{
                  background: 'linear-gradient(135deg, #463f38 0%, #5e564f 100%)',
                  color: '#ffffff',
                  fontFamily: 'Manrope, sans-serif',
                  letterSpacing: '0.08em',
                  boxShadow: '0 8px 24px rgba(70,63,56,0.22)',
                }}
              >
                Explore the Collection
              </Link>
            </div>
          </div>

          {/* Right: Hero image placeholder */}
          <div className="md:col-span-6 relative">
            <div
              className="aspect-[4/5] overflow-hidden"
              style={{ background: '#f6f3f2' }}
            >
              <div
                className="w-full h-full flex items-center justify-center"
                style={{
                  background: 'linear-gradient(135deg, #f6f3f2 0%, #e5e2e1 100%)',
                }}
              >
                <div className="text-center p-12">
                  <p
                    className="text-6xl mb-4"
                    style={{ color: '#cfc5bc' }}
                  >
                    ✦
                  </p>
                  <p
                    className="text-xs tracking-[0.3em] uppercase"
                    style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                  >
                    Artisan Collection
                  </p>
                </div>
              </div>
            </div>
            {/* Floating accent box */}
            <div
              className="absolute -bottom-6 -left-6 hidden lg:block w-40 aspect-square p-4"
              style={{
                background: '#fcf9f8',
                boxShadow: '0 20px 40px rgba(27,27,28,0.10)',
              }}
            >
              <div
                className="w-full h-full flex items-end"
                style={{ background: '#f0eded' }}
              >
                <p
                  className="text-[9px] uppercase tracking-widest p-2 text-center w-full"
                  style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                >
                  Material Focus No. 04
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ── Bento Featured Grid ── */}
      <section style={{ background: '#f6f3f2' }} className="py-24 px-8">
        <div className="max-w-screen-2xl mx-auto">
          <div className="flex justify-between items-end mb-16">
            <div>
              <h2
                className="text-3xl md:text-5xl mb-4"
                style={{ fontFamily: 'Noto Serif, serif', color: '#1b1b1c', letterSpacing: '-0.02em' }}
              >
                Curated Selections
              </h2>
              <p style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}>
                Exceptional artifacts organised by material and purpose.
              </p>
            </div>
            <Link
              to="/products"
              className="hidden md:flex items-center gap-2 text-sm font-semibold tracking-wider uppercase transition-opacity hover:opacity-70"
              style={{ color: '#463f38', fontFamily: 'Manrope, sans-serif' }}
            >
              View All →
            </Link>
          </div>

          {/* Grid */}
          <div className="grid grid-cols-1 md:grid-cols-4 md:grid-rows-2 gap-6" style={{ minHeight: '640px' }}>
            {/* Large featured card */}
            <div
              className="md:col-span-2 md:row-span-2 relative overflow-hidden group cursor-pointer flex flex-col justify-end p-10"
              style={{ background: 'linear-gradient(135deg, #463f38 0%, #5e564f 100%)', minHeight: '360px' }}
            >
              <span
                className="text-xs uppercase tracking-widest mb-2 block"
                style={{ color: 'rgba(255,255,255,0.7)', fontFamily: 'Manrope, sans-serif' }}
              >
                Signature
              </span>
              <h3
                className="text-4xl mb-6"
                style={{ fontFamily: 'Noto Serif, serif', color: '#ffffff', letterSpacing: '-0.02em' }}
              >
                Sacred Geometry Series
              </h3>
              <Link
                to="/products"
                className="inline-block px-6 py-2 text-xs uppercase tracking-widest transition-all"
                style={{
                  background: 'rgba(255,255,255,0.12)',
                  border: '1px solid rgba(255,255,255,0.22)',
                  color: '#ffffff',
                  fontFamily: 'Manrope, sans-serif',
                  backdropFilter: 'blur(8px)',
                }}
                onMouseEnter={e => (e.target.style.background = 'rgba(255,255,255,0.22)')}
                onMouseLeave={e => (e.target.style.background = 'rgba(255,255,255,0.12)')}
              >
                Discover
              </Link>
            </div>

            {/* Medium card */}
            <div
              className="md:col-span-2 relative overflow-hidden group cursor-pointer flex flex-col justify-center items-center text-center p-6"
              style={{ background: '#e5e2e1', minHeight: '200px' }}
            >
              <h3
                className="text-2xl mb-2"
                style={{ fontFamily: 'Noto Serif, serif', color: '#463f38' }}
              >
                The Sterling Table
              </h3>
              <p
                className="text-sm italic"
                style={{ fontFamily: 'Noto Serif, serif', color: '#4d453f' }}
              >
                Refined utility for the home.
              </p>
            </div>

            {/* Icon card */}
            <div
              className="md:col-span-1 p-8 flex flex-col justify-between"
              style={{ background: '#eae7e7' }}
            >
              <div
                className="w-full aspect-square flex items-center justify-center mb-4"
                style={{ background: 'rgba(255,255,255,0.4)' }}
              >
                <span className="text-4xl" style={{ color: '#4c3e25' }}>⚒</span>
              </div>
              <div>
                <h4
                  className="text-xl mb-2"
                  style={{ fontFamily: 'Noto Serif, serif', color: '#1b1b1c' }}
                >
                  Bespoke Iron
                </h4>
                <p
                  className="text-xs leading-relaxed"
                  style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
                >
                  Custom architectural elements hand-forged for lasting legacy.
                </p>
              </div>
            </div>

            {/* Dark arrival card */}
            <div
              className="md:col-span-1 p-8 flex flex-col justify-between"
              style={{ background: '#463f38' }}
            >
              <span
                className="text-[10px] uppercase tracking-widest font-bold"
                style={{ color: '#d0c4bc', fontFamily: 'Manrope, sans-serif' }}
              >
                New Arrival
              </span>
              <div className="my-6">
                <h4
                  className="text-xl mb-2"
                  style={{ fontFamily: 'Noto Serif, serif', color: '#ffffff' }}
                >
                  Aged Bronze Altar
                </h4>
                <p
                  className="text-xs leading-relaxed"
                  style={{ color: '#d7ccc3', fontFamily: 'Manrope, sans-serif' }}
                >
                  Limited release available for discerning collectors.
                </p>
              </div>
              <Link
                to="/products"
                className="text-xs flex items-center gap-2 font-bold uppercase tracking-widest"
                style={{ color: '#ffffff', fontFamily: 'Manrope, sans-serif' }}
              >
                Shop Now →
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* ── Shop by Category (Gallery of Intent) ── */}
      <section className="py-24 px-8 max-w-screen-2xl mx-auto">
        <div className="mb-16 text-center max-w-2xl mx-auto">
          <h2
            className="text-3xl mb-6 italic"
            style={{ fontFamily: 'Noto Serif, serif', color: '#1b1b1c' }}
          >
            The Gallery of Intent
          </h2>
          <div
            className="w-12 h-px mx-auto mb-6"
            style={{ background: '#4c3e25' }}
          />
          <p
            className="text-sm uppercase tracking-[0.2em]"
            style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
          >
            Individually Hand-Crafted Artifacts
          </p>
        </div>
        <CategoryList />
      </section>

      {/* ── Newsletter CTA ── */}
      <section
        className="px-8 py-24"
        style={{ background: '#eae7e7' }}
      >
        <div className="max-w-screen-xl mx-auto flex flex-col items-center text-center">
          <span className="text-4xl mb-8" style={{ color: '#4c3e25' }}>✦</span>
          <h2
            className="text-4xl md:text-5xl mb-8 italic"
            style={{ fontFamily: 'Noto Serif, serif', color: '#1b1b1c', letterSpacing: '-0.02em' }}
          >
            Stay Within the Glow
          </h2>
          <p
            className="max-w-lg mb-12 leading-relaxed"
            style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
          >
            Join our folio for exclusive access to limited-run collections, artisan profiles,
            and the philosophy behind our craft.
          </p>
          <form
            className="w-full max-w-md flex flex-col md:flex-row gap-0"
            onSubmit={e => e.preventDefault()}
          >
            <input
              type="email"
              placeholder="Email Address"
              className="flex-grow py-4 px-2 text-sm focus:outline-none transition-colors"
              style={{
                background: 'transparent',
                borderBottom: '1px solid #7e766e',
                color: '#1b1b1c',
                fontFamily: 'Manrope, sans-serif',
              }}
            />
            <button
              type="submit"
              className="mt-8 md:mt-0 px-10 py-4 text-xs uppercase tracking-widest font-bold transition-opacity hover:opacity-90"
              style={{
                background: '#463f38',
                color: '#ffffff',
                fontFamily: 'Manrope, sans-serif',
              }}
            >
              Join
            </button>
          </form>
        </div>
      </section>

      {/* Auth Modal */}
      <AuthModal isOpen={isAuthModalOpen} onClose={() => setIsAuthModalOpen(false)} />
    </div>
  );
}
