import { useEffect, useState } from 'react';
import { useLocation, Link } from 'react-router-dom';
import AuthModal from '../components/AuthModal';
import ProductList from '../components/ProductList';
import axios from '../lib/axios';

/**
 * HomePage - Metallic Vriddhi (Oat Edition)
 * Mirrors the Stitch design: hero with editorial split,
 * bento featured grid, product gallery strip, newsletter CTA
 */
export default function HomePage() {
  const location = useLocation();
  const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);
  const [bentoSlots, setBentoSlots] = useState({
    slot_1: {
      slot_key: 'slot_1',
      title: 'Sacred Geometry Series',
      subtitle: null,
      image_url: null,
      icon: null,
      badge: 'Signature',
      theme: 'gradient',
      computed_link: '/products'
    },
    slot_2: {
      slot_key: 'slot_2',
      title: 'The Sterling Table',
      subtitle: 'Refined utility for the home.',
      image_url: null,
      icon: null,
      badge: null,
      theme: 'light',
      computed_link: '/products'
    },
    slot_3: {
      slot_key: 'slot_3',
      title: 'Bespoke Iron',
      subtitle: 'Custom architectural elements hand-forged for lasting legacy.',
      image_url: null,
      icon: '⚒',
      badge: null,
      theme: 'light',
      computed_link: '/products'
    },
    slot_4: {
      slot_key: 'slot_4',
      title: 'Aged Bronze Altar',
      subtitle: 'Limited release available for discerning collectors.',
      image_url: null,
      icon: null,
      badge: 'New Arrival',
      theme: 'dark',
      computed_link: '/products'
    }
  });
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    if (location.state?.loginRequired || new URLSearchParams(location.search).get('loginRequired')) {
      setIsAuthModalOpen(true);
    }
  }, [location]);

  useEffect(() => {
    const fetchBentoSlots = async () => {
      try {
        setIsLoading(true);
        const response = await axios.get('/homepage-bento');
        if (response.data.success && response.data.bento_slots) {
          const slotsMap = {};
          response.data.bento_slots.forEach(slot => {
            slotsMap[slot.slot_key] = slot;
          });
          setBentoSlots(prev => ({ ...prev, ...slotsMap }));
        }
      } catch (err) {
        console.error('Error fetching homepage bento slots:', err);
      } finally {
        setIsLoading(false);
      }
    };
    fetchBentoSlots();
  }, []);

  const getSlotStyles = (slot, defaultBg) => {
    const isDark = slot.theme === 'dark' || slot.theme === 'gradient';
    const isGradient = slot.theme === 'gradient';
    
    let backgroundStyle = {};
    if (slot.image_url) {
      backgroundStyle = {
        backgroundImage: isDark 
          ? `linear-gradient(to bottom, rgba(27,27,28,0.3), rgba(27,27,28,0.75)), url(${slot.image_url})` 
          : `linear-gradient(to bottom, rgba(252,249,248,0.65), rgba(252,249,248,0.9)), url(${slot.image_url})`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
      };
    } else if (isGradient) {
      backgroundStyle = {
        background: 'linear-gradient(135deg, #463f38 0%, #5e564f 100%)'
      };
    } else {
      backgroundStyle = {
        background: defaultBg
      };
    }

    return {
      style: backgroundStyle,
      textColor: isDark ? '#ffffff' : '#1b1b1c',
      mutedColor: isDark ? 'rgba(255,255,255,0.7)' : '#4d453f',
      accentColor: isDark ? '#d0c4bc' : '#463f38'
    };
  };

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
                    Vriddhi Collection
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
            {(() => {
              const slotLayoutConfigs = {
                slot_1: {
                  gridClass: "md:col-span-2 md:row-span-2 p-10 justify-end",
                  defaultBg: "#463f38",
                  minHeight: "360px",
                  renderInner: (slot, textColor, mutedColor) => (
                    <>
                      {slot.badge && (
                        <span className="text-xs uppercase tracking-widest mb-2 block" style={{ color: mutedColor, fontFamily: 'Manrope, sans-serif' }}>
                          {slot.badge}
                        </span>
                      )}
                      <h3 className="text-4xl mb-6" style={{ fontFamily: 'Noto Serif, serif', color: textColor, letterSpacing: '-0.02em' }}>
                        {slot.title}
                      </h3>
                      {slot.computed_link && (
                        <span className="inline-block px-6 py-2 text-xs uppercase tracking-widest transition-all w-fit text-center" style={{
                          background: slot.theme === 'light' ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.12)',
                          border: slot.theme === 'light' ? '1px solid rgba(0,0,0,0.12)' : '1px solid rgba(255,255,255,0.22)',
                          color: textColor,
                          fontFamily: 'Manrope, sans-serif',
                          backdropFilter: 'blur(8px)',
                        }}>
                          Discover
                        </span>
                      )}
                    </>
                  )
                },
                slot_2: {
                  gridClass: "md:col-span-2 p-6 justify-center items-center text-center",
                  defaultBg: "#e5e2e1",
                  minHeight: "200px",
                  renderInner: (slot, textColor, mutedColor) => (
                    <>
                      {slot.badge && (
                        <span className="text-xs uppercase tracking-widest mb-2 block" style={{ color: mutedColor, fontFamily: 'Manrope, sans-serif' }}>
                          {slot.badge}
                        </span>
                      )}
                      <h3 className="text-2xl mb-2" style={{ fontFamily: 'Noto Serif, serif', color: textColor }}>
                        {slot.title}
                      </h3>
                      {slot.subtitle && (
                        <p className="text-sm italic" style={{ fontFamily: 'Noto Serif, serif', color: mutedColor }}>
                          {slot.subtitle}
                        </p>
                      )}
                    </>
                  )
                },
                slot_3: {
                  gridClass: "md:col-span-1 p-8 justify-between",
                  defaultBg: "#eae7e7",
                  renderInner: (slot, textColor, mutedColor) => (
                    <>
                      {slot.icon ? (
                        <div className="w-12 h-12 flex items-center justify-center mb-4" style={{ background: slot.theme === 'light' ? 'rgba(255,255,255,0.4)' : 'rgba(0,0,0,0.15)' }}>
                          <span className="text-4xl">{slot.icon}</span>
                        </div>
                      ) : <div />}
                      <div>
                        {slot.badge && (
                          <span className="text-[10px] uppercase tracking-widest font-bold block mb-1" style={{ color: mutedColor, fontFamily: 'Manrope, sans-serif' }}>
                            {slot.badge}
                          </span>
                        )}
                        <h4 className="text-xl mb-2" style={{ fontFamily: 'Noto Serif, serif', color: textColor }}>
                          {slot.title}
                        </h4>
                        {slot.subtitle && (
                          <p className="text-xs leading-relaxed" style={{ color: mutedColor, fontFamily: 'Manrope, sans-serif' }}>
                            {slot.subtitle}
                          </p>
                        )}
                      </div>
                    </>
                  )
                },
                slot_4: {
                  gridClass: "md:col-span-1 p-8 justify-between",
                  defaultBg: "#463f38",
                  renderInner: (slot, textColor, mutedColor) => (
                    <>
                      {slot.badge ? (
                        <span className="text-[10px] uppercase tracking-widest font-bold block" style={{ color: mutedColor, fontFamily: 'Manrope, sans-serif' }}>
                          {slot.badge}
                        </span>
                      ) : <div />}
                      <div className="my-6">
                        <h4 className="text-xl mb-2" style={{ fontFamily: 'Noto Serif, serif', color: textColor }}>
                          {slot.title}
                        </h4>
                        {slot.subtitle && (
                          <p className="text-xs leading-relaxed" style={{ color: mutedColor, fontFamily: 'Manrope, sans-serif' }}>
                            {slot.subtitle}
                          </p>
                        )}
                      </div>
                      {slot.computed_link ? (
                        <span className="text-xs flex items-center gap-2 font-bold uppercase tracking-widest" style={{ color: textColor, fontFamily: 'Manrope, sans-serif' }}>
                          Shop Now →
                        </span>
                      ) : <div />}
                    </>
                  )
                }
              };

              const slotOrder = ['slot_1', 'slot_2', 'slot_3', 'slot_4'];

              return slotOrder.map(key => {
                const slot = bentoSlots[key];
                if (!slot) return null;
                const config = slotLayoutConfigs[key];
                const { style, textColor, mutedColor } = getSlotStyles(slot, config.defaultBg);
                const Element = slot.computed_link ? Link : 'div';
                const elementProps = slot.computed_link ? { to: slot.computed_link } : {};
                
                // Add hover transform & pointer cursor ONLY if the slot has a link
                const interactiveClass = slot.computed_link 
                  ? "cursor-pointer transition-transform hover:scale-[1.01] duration-300" 
                  : "cursor-default";

                return (
                  <Element
                    key={key}
                    {...elementProps}
                    className={`${config.gridClass} relative overflow-hidden group flex flex-col ${interactiveClass} ${isLoading ? 'animate-pulse opacity-85' : ''}`}
                    style={{ ...style, minHeight: config.minHeight }}
                  >
                    {config.renderInner(slot, textColor, mutedColor)}
                  </Element>
                );
              });
            })()}
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
        <ProductList limit={8} />
      </section>



      {/* Auth Modal */}
      <AuthModal isOpen={isAuthModalOpen} onClose={() => setIsAuthModalOpen(false)} />
    </div>
  );
}
