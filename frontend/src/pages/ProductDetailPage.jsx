import { useEffect, useState, useCallback, useRef } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import axios from '../lib/axios';
import { getImageUrl } from '../lib/imageUrl';
import useAuthStore from '../stores/authStore';
import useCartStore from '../stores/cartStore';
import ProductList from '../components/ProductList';

/**
 * ProductDetailPage - Metallic Vriddhi (Oat Edition)
 * Asymmetric layout: large gallery col + sticky product info col
 */
export default function ProductDetailPage() {
  const { slug } = useParams();
  const navigate = useNavigate();
  const { isAuthenticated } = useAuthStore();
  const { addItem } = useCartStore();

  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [selectedImage, setSelectedImage] = useState(0);
  const [quantity, setQuantity] = useState(1);
  const [addingToCart, setAddingToCart] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [isLightboxOpen, setIsLightboxOpen] = useState(false);
  // Dynamic aspect ratio: store natural W/H ratio per image index
  const [imgRatios, setImgRatios] = useState({});
  const [imgLoading, setImgLoading] = useState(true);
  // Gallery container ref + computed px height
  const galleryRef = useRef(null);
  const [galleryHeight, setGalleryHeight] = useState(480);

  // Called when each image loads — record its natural aspect ratio
  const handleImgLoad = useCallback((e, idx) => {
    const { naturalWidth, naturalHeight } = e.target;
    if (naturalWidth && naturalHeight) {
      setImgRatios(prev => ({ ...prev, [idx]: naturalWidth / naturalHeight }));
    }
    setImgLoading(false);
  }, []);

  // When switching images, show loading shimmer if ratio not yet known
  const handleSelectImage = (idx) => {
    setSelectedImage(idx);
    if (!imgRatios[idx]) setImgLoading(true);
  };

  const dragStartX = useRef(0);
  const dragStartY = useRef(0);
  const touchStart = useRef(false);

  const handleTouchStart = (e) => {
    if (e.touches.length === 1) {
      dragStartX.current = e.touches[0].clientX;
      dragStartY.current = e.touches[0].clientY;
      touchStart.current = true;
    }
  };

  const handleTouchEnd = (e) => {
    if (!touchStart.current) return;
    touchStart.current = false;
    const touchEndX = e.changedTouches[0].clientX;
    const touchEndY = e.changedTouches[0].clientY;
    
    const diffX = dragStartX.current - touchEndX;
    const diffY = dragStartY.current - touchEndY;
    
    if (Math.abs(diffX) > 45 && Math.abs(diffX) > Math.abs(diffY)) {
      if (diffX > 0) {
        setSelectedImage(prev => (prev === images.length - 1 ? 0 : prev + 1));
      } else {
        setSelectedImage(prev => (prev === 0 ? images.length - 1 : prev - 1));
      }
    }
  };

  const handleMouseDown = (e) => {
    dragStartX.current = e.clientX;
    dragStartY.current = e.clientY;
    touchStart.current = true;
  };

  const handleMouseUp = (e) => {
    if (!touchStart.current) return;
    touchStart.current = false;
    
    const diffX = dragStartX.current - e.clientX;
    const diffY = dragStartY.current - e.clientY;
    
    if (Math.abs(diffX) > 40 && Math.abs(diffX) > Math.abs(diffY)) {
      if (diffX > 0) {
        setSelectedImage(prev => (prev === images.length - 1 ? 0 : prev + 1));
      } else {
        setSelectedImage(prev => (prev === 0 ? images.length - 1 : prev - 1));
      }
    } else if (Math.abs(diffX) < 8 && Math.abs(diffY) < 8) {
      setIsLightboxOpen(true);
    }
  };

  // Inspect / right-click disable logic
  useEffect(() => {
    const handleContextMenu = (e) => {
      e.preventDefault();
    };

    const handleKeyDown = (e) => {
      if (e.key === 'F12') {
        e.preventDefault();
      }
      if (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C' || e.key === 'i' || e.key === 'j' || e.key === 'c')) {
        e.preventDefault();
      }
      if (e.ctrlKey && (e.key === 'U' || e.key === 'u')) {
        e.preventDefault();
      }
    };

    window.addEventListener('contextmenu', handleContextMenu);
    window.addEventListener('keydown', handleKeyDown);

    return () => {
      window.removeEventListener('contextmenu', handleContextMenu);
      window.removeEventListener('keydown', handleKeyDown);
    };
  }, []);

  // Recompute height in px whenever ratio or container width changes
  // Formula: height = min(containerWidth / ratio, 90vh)
  // This is exact — no CSS % guesswork
  useEffect(() => {
    const el = galleryRef.current;
    if (!el) return;

    const compute = () => {
      const w = el.offsetWidth;
      const ratio = imgRatios[selectedImage] ?? 0.8;
      const clamped = Math.min(Math.max(ratio, 0.5), 1.5);
      const naturalH = w / clamped;          // height from aspect ratio
      const maxH = window.innerHeight * 0.9; // 90vh in px
      setGalleryHeight(Math.min(naturalH, maxH));
    };

    compute(); // run immediately
    const ro = new ResizeObserver(compute); // re-run if container width changes
    ro.observe(el);
    return () => ro.disconnect();
  }, [imgRatios, selectedImage]); // eslint-disable-line

  const images = product?.image_urls?.length > 0 ? product.image_urls : (product?.images?.length > 0 ? product.images : [null]);

  useEffect(() => { fetchProduct(); }, [slug]); // eslint-disable-line

  // Handle keyboard events for Lightbox
  useEffect(() => {
    if (!isLightboxOpen) return;

    const handleKeyDown = (e) => {
      if (e.key === 'Escape') {
        setIsLightboxOpen(false);
      } else if (e.key === 'ArrowRight' && images.length > 1) {
        setSelectedImage(prev => (prev === images.length - 1 ? 0 : prev + 1));
      } else if (e.key === 'ArrowLeft' && images.length > 1) {
        setSelectedImage(prev => (prev === 0 ? images.length - 1 : prev - 1));
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isLightboxOpen, images]);

  const fetchProduct = async () => {
    try {
      setLoading(true); setError(null);
      const response = await axios.get(`/products/${slug}`);
      if (response.data?.success) {
        setProduct(response.data.product);  // Clean: response.data.product
      } else { setError('Product not found'); }
    } catch (err) {
      setError(err.response?.status === 404 ? 'Product not found' : 'Failed to load product.');
    } finally { setLoading(false); }
  };

  const handleAddToCart = async () => {
    if (!isAuthenticated) { navigate('/?loginRequired=true'); return; }
    if (!product?.id) return;
    try {
      setAddingToCart(true); setSuccessMessage('');
      const result = await addItem(product.id, quantity);
      if (result?.success) {
        setSuccessMessage('Added to your collection!');
        setTimeout(() => setSuccessMessage(''), 3000);
      } else { alert(result?.message || 'Failed to add to cart'); }
    } catch { alert('Failed to add to cart'); }
    finally { setAddingToCart(false); }
  };

  /* ── Loading ── */
  if (loading) {
    return (
      <div className="max-w-screen-2xl mx-auto px-8 py-12 animate-pulse">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-16">
          <div className="lg:col-span-7 space-y-8">
            <div className="aspect-[4/5]" style={{ background: '#f0eded' }} />
            <div className="grid grid-cols-2 gap-8">
              <div className="aspect-square" style={{ background: '#f0eded' }} />
              <div className="aspect-square" style={{ background: '#f0eded' }} />
            </div>
          </div>
          <div className="lg:col-span-5 space-y-6">
            <div className="h-3" style={{ background: '#e5e2e1', width: '30%' }} />
            <div className="h-16" style={{ background: '#e5e2e1', width: '80%' }} />
            <div className="h-8" style={{ background: '#e5e2e1', width: '25%' }} />
            <div className="h-24" style={{ background: '#f0eded' }} />
            <div className="h-14" style={{ background: '#e5e2e1' }} />
          </div>
        </div>
      </div>
    );
  }

  /* ── Error ── */
  if (error || !product) {
    return (
      <div className="max-w-screen-2xl mx-auto px-8 py-24 text-center">
        <p className="text-5xl mb-8 italic" style={{ fontFamily: 'Noto Serif, serif', color: '#cfc5bc' }}>∅</p>
        <p className="mb-6 text-lg italic" style={{ color: '#4d453f', fontFamily: 'Noto Serif, serif' }}>{error}</p>
        <button
          onClick={() => navigate('/products')}
          className="px-8 py-3 text-xs uppercase tracking-widest font-bold transition-opacity hover:opacity-80"
          style={{ background: '#463f38', color: '#ffffff', fontFamily: 'Manrope, sans-serif' }}
        >
          Back to Collection
        </button>
      </div>
    );
  }

  return (
    <div style={{ background: '#fcf9f8' }} className="pb-24">

      {/* ── Success Toast ── */}
      {successMessage && (
        <div
          className="fixed top-24 right-8 z-50 px-8 py-4 text-sm font-medium tracking-wide transition-all"
          style={{
            background: '#463f38',
            color: '#ffffff',
            fontFamily: 'Manrope, sans-serif',
            boxShadow: '0 8px 32px rgba(70,63,56,0.25)',
          }}
        >
          ✓ {successMessage}
        </div>
      )}

      <section className="max-w-screen-2xl mx-auto px-8 grid grid-cols-1 lg:grid-cols-12 gap-16 py-12">

        {/* ── Gallery Column ── */}
        <div className="lg:col-span-7 flex flex-col">
          {/* Main Image — Dynamic px height capped at 90vh */}
          <div
            ref={galleryRef}
            className="w-full relative cursor-zoom-in group/main shadow-sm mx-auto overflow-hidden select-none"
            style={{
              background: '#f6f3f2',
              height: `${galleryHeight}px`,        // exact px, never > 90vh
              transition: 'height 0.4s ease',       // smooth resize when switching
            }}
            onMouseDown={handleMouseDown}
            onMouseUp={handleMouseUp}
            onTouchStart={handleTouchStart}
            onTouchEnd={handleTouchEnd}
          >
            {images[selectedImage] ? (
              <>
                {/* Shimmer while loading */}
                {imgLoading && (
                  <div
                    className="absolute inset-0 animate-pulse"
                    style={{ background: 'linear-gradient(90deg, #f0eded 25%, #e8e4e3 50%, #f0eded 75%)' }}
                  />
                )}
                {/* All images rendered (hidden), so onLoad fires for all → ratios recorded */}
                {images.map((img, idx) => (
                  <img
                    key={idx}
                    src={getImageUrl(img)}
                    alt={product.name}
                    onLoad={e => handleImgLoad(e, idx)}
                    className="absolute inset-0 w-full h-full transition-opacity duration-300 select-none pointer-events-none"
                    style={{
                      objectFit: 'contain',
                      opacity: idx === selectedImage ? 1 : 0,
                      pointerEvents: 'none',
                    }}
                    draggable={false}
                  />
                ))}
                {/* Hover zoom hint */}
                <div className="absolute inset-0 bg-black/10 opacity-0 group-hover/main:opacity-100 transition-opacity duration-300 flex items-center justify-center pointer-events-none">
                  <span className="bg-[#fcf9f8]/95 backdrop-blur text-[#463f38] px-5 py-2.5 text-xs uppercase tracking-[0.2em] font-semibold shadow-md">
                    Click to Zoom
                  </span>
                </div>

                {/* Side Navigation Buttons */}
                {images.length > 1 && (
                  <>
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        setSelectedImage(prev => (prev === 0 ? images.length - 1 : prev - 1));
                      }}
                      onMouseDown={e => e.stopPropagation()}
                      onMouseUp={e => e.stopPropagation()}
                      className="absolute left-4 top-1/2 -translate-y-1/2 z-30 w-10 h-10 flex items-center justify-center bg-[#fcf9f8]/80 hover:bg-[#fcf9f8] backdrop-blur-md rounded-full shadow-md text-[#463f38] transition-all duration-300 opacity-0 group-hover/main:opacity-100 focus:opacity-100 hover:scale-105 active:scale-95"
                      style={{ cursor: 'pointer' }}
                      aria-label="Previous image"
                    >
                      <span className="text-xl font-light">⟨</span>
                    </button>
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        setSelectedImage(prev => (prev === images.length - 1 ? 0 : prev + 1));
                      }}
                      onMouseDown={e => e.stopPropagation()}
                      onMouseUp={e => e.stopPropagation()}
                      className="absolute right-4 top-1/2 -translate-y-1/2 z-30 w-10 h-10 flex items-center justify-center bg-[#fcf9f8]/80 hover:bg-[#fcf9f8] backdrop-blur-md rounded-full shadow-md text-[#463f38] transition-all duration-300 opacity-0 group-hover/main:opacity-100 focus:opacity-100 hover:scale-105 active:scale-95"
                      style={{ cursor: 'pointer' }}
                      aria-label="Next image"
                    >
                      <span className="text-xl font-light">⟩</span>
                    </button>
                  </>
                )}

                {/* Watermark Logo */}
                <img
                  src="/logo-white.png"
                  alt="Watermark"
                  className={`absolute z-30 h-8 md:h-10 opacity-90 select-none pointer-events-none mix-blend-difference transition-all duration-300 ${
                    images.length > 1 ? 'bottom-20 right-4' : 'bottom-4 right-4'
                  }`}
                />

                {/* Thumbnails overlaid on top of main image at bottom */}
                {images.length > 1 && (
                  <div 
                    className="absolute bottom-4 left-1/2 -translate-x-1/2 z-30 flex gap-2 px-2.5 py-1.5 bg-[#fcf9f8]/40 backdrop-blur-md border border-[#cfc5bc]/10 shadow-lg rounded-xl max-w-[95%] overflow-x-auto"
                    onClick={e => e.stopPropagation()} // prevent clicking thumbnails from zooming
                    onMouseDown={e => e.stopPropagation()} // prevent dragging when clicking thumbnails
                    onMouseUp={e => e.stopPropagation()}
                  >
                    {images.map((img, idx) => (
                      <button
                        key={idx}
                        onClick={() => handleSelectImage(idx)}
                        className="flex-shrink-0 w-11 h-11 overflow-hidden transition-all duration-300"
                        style={{
                          background: '#f6f3f2',
                          border: selectedImage === idx ? '2px solid #463f38' : '2px solid transparent',
                          borderRadius: '6px',
                          transform: selectedImage === idx ? 'scale(1.05)' : 'none',
                        }}
                      >
                        {img ? (
                          <img src={getImageUrl(img)} alt={`View ${idx + 1}`} className="w-full h-full object-cover pointer-events-none select-none" loading="lazy" />
                        ) : (
                          <div className="w-full h-full flex items-center justify-center">
                            <span style={{ color: '#cfc5bc', fontSize: '10px' }}>✦</span>
                          </div>
                        )}
                      </button>
                    ))}
                  </div>
                )}
              </>
            ) : (
              <div
                className="absolute inset-0 flex items-center justify-center"
                style={{ background: 'linear-gradient(135deg, #f6f3f2 0%, #e5e2e1 100%)' }}
              >
                <span className="text-8xl" style={{ color: '#cfc5bc' }}>✦</span>
              </div>
            )}
          </div>
        </div>

        {/* ── Product Info Column ── */}
        <div className="lg:col-span-5 flex flex-col lg:sticky lg:top-28 self-start">
          <header className="mb-10">
            <span
              className="text-xs tracking-[0.2em] uppercase block mb-4"
              style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
            >
              {product.category?.name || 'Handcrafted Object'}
            </span>
            <h1
              className="text-5xl italic leading-none mb-6"
              style={{ fontFamily: 'Noto Serif, serif', letterSpacing: '-0.03em', color: '#463f38' }}
            >
              {product.name}
            </h1>
            <div className="flex items-baseline gap-4">
              <span
                className="text-2xl font-light"
                style={{ color: '#1b1b1c', fontFamily: 'Manrope, sans-serif' }}
              >
                ₹{parseFloat(product.price).toLocaleString('en-IN')}
              </span>
              {product.stock > 0 ? (
                <span
                  className="text-xs uppercase tracking-widest"
                  style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                >
                  In Stock
                </span>
              ) : (
                <span
                  className="text-xs uppercase tracking-widest"
                  style={{ color: '#ba1a1a', fontFamily: 'Manrope, sans-serif' }}
                >
                  Out of Stock
                </span>
              )}
            </div>
          </header>

          <div className="space-y-10">
            {/* Description */}
            <article className="space-y-4">
              {product.description ? (
                <div
                  className="text-base leading-relaxed prose prose-sm max-w-none"
                  style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
                  dangerouslySetInnerHTML={{ __html: product.description }}
                />
              ) : (
                <p
                  className="text-base leading-relaxed"
                  style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
                >
                  A meticulously crafted artifact forged with intention and finished by hand.
                </p>
              )}
            </article>

            {/* Specifications */}
            {product.category && (
              <div style={{ borderTop: '1px solid rgba(207,197,188,0.20)', paddingTop: '24px' }}>
                <h3
                  className="text-xs tracking-widest uppercase mb-5"
                  style={{ color: '#463f38', fontFamily: 'Manrope, sans-serif' }}
                >
                  Details
                </h3>
                <dl className="space-y-3">
                  <div
                    className="flex justify-between items-end pb-2"
                    style={{ borderBottom: '1px solid rgba(207,197,188,0.12)' }}
                  >
                    <dt className="text-xs uppercase" style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}>Category</dt>
                    <dd className="text-sm" style={{ color: '#1b1b1c', fontFamily: 'Manrope, sans-serif' }}>{product.category.name}</dd>
                  </div>
                  <div
                    className="flex justify-between items-end pb-2"
                    style={{ borderBottom: '1px solid rgba(207,197,188,0.12)' }}
                  >
                    <dt className="text-xs uppercase" style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}>Availability</dt>
                    <dd className="text-sm" style={{ color: '#1b1b1c', fontFamily: 'Manrope, sans-serif' }}>
                      {product.stock > 0 ? `${product.stock} units` : 'Out of Stock'}
                    </dd>
                  </div>
                </dl>
              </div>
            )}

            {/* Quantity + CTA */}
            <div className="space-y-4">
              {product.stock > 0 && (
                <div className="flex items-center gap-4">
                  <label
                    className="text-xs uppercase tracking-widest"
                    style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                  >
                    Qty
                  </label>
                  <div
                    className="flex items-center"
                    style={{ background: '#e5e2e1' }}
                  >
                    <button
                      onClick={() => setQuantity(q => Math.max(1, q - 1))}
                      className="px-4 py-2 text-sm transition-colors hover:opacity-60"
                      style={{ color: '#463f38' }}
                    >
                      −
                    </button>
                    <span
                      className="px-4 py-2 text-sm min-w-[3rem] text-center"
                      style={{ color: '#463f38', fontFamily: 'Manrope, sans-serif' }}
                    >
                      {quantity}
                    </span>
                    <button
                      onClick={() => setQuantity(q => Math.min(product.stock, q + 1))}
                      className="px-4 py-2 text-sm transition-colors hover:opacity-60"
                      style={{ color: '#463f38' }}
                    >
                      +
                    </button>
                  </div>
                </div>
              )}

              <button
                onClick={handleAddToCart}
                disabled={product.stock === 0 || addingToCart}
                className="w-full py-5 text-sm tracking-[0.1em] uppercase font-medium transition-opacity hover:opacity-90 flex justify-center items-center gap-3"
                style={{
                  background: product.stock === 0
                    ? '#e5e2e1'
                    : 'linear-gradient(135deg, #463f38 0%, #5e564f 100%)',
                  color: product.stock === 0 ? '#7e766e' : '#ffffff',
                  cursor: product.stock === 0 ? 'not-allowed' : 'pointer',
                  fontFamily: 'Manrope, sans-serif',
                }}
              >
                {addingToCart ? 'Adding...' : product.stock === 0 ? 'Out of Stock' : 'Add to Cart →'}
              </button>

              {/* Shipping note */}
              <div
                className="flex items-center gap-4 py-4 px-4"
                style={{ background: '#f6f3f2' }}
              >
                <span style={{ color: '#4c3e25', fontSize: '20px' }}>📦</span>
                <p
                  className="text-[10px] uppercase tracking-widest leading-relaxed"
                  style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
                >
                  Ships pan-India with care.<br />
                  Carefully packaged for safe delivery.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ── Related Products (You May Also Like) ── */}
      <section className="border-t border-[#cfc5bc]/20 py-24 px-8 max-w-screen-2xl mx-auto">
        <h2 
          className="text-3xl italic mb-16"
          style={{ fontFamily: 'Noto Serif, serif', color: '#463f38' }}
        >
          You May Also Like
        </h2>
        <ProductList limit={4} categoryId={product.category_id} excludeId={product.id} />
      </section>

      {/* ── Premium Lightbox Modal ── */}
      {isLightboxOpen && images[selectedImage] && (
        <div
          className="fixed inset-0 z-[100] flex items-center justify-center p-4 md:p-12 bg-black/90 backdrop-blur-md transition-opacity duration-300 animate-in fade-in"
          onClick={() => setIsLightboxOpen(false)}
        >
          {/* Close button */}
          <button
            className="absolute top-6 right-6 text-white/70 hover:text-white text-3xl font-light transition-all p-3 z-[120] hover:scale-110 duration-200"
            onClick={() => setIsLightboxOpen(false)}
            aria-label="Close lightbox"
          >
            ✕
          </button>

          {/* Previous Button */}
          {images.length > 1 && (
            <button
              className="absolute left-6 text-white/50 hover:text-white text-5xl font-light transition-all p-4 z-[120] hover:scale-110 active:scale-95 duration-200"
              onClick={(e) => {
                e.stopPropagation();
                setSelectedImage(prev => (prev === 0 ? images.length - 1 : prev - 1));
              }}
              aria-label="Previous image"
            >
              ⟨
            </button>
          )}

          {/* Next Button */}
          {images.length > 1 && (
            <button
              className="absolute right-6 text-white/50 hover:text-white text-5xl font-light transition-all p-4 z-[120] hover:scale-110 active:scale-95 duration-200"
              onClick={(e) => {
                e.stopPropagation();
                setSelectedImage(prev => (prev === images.length - 1 ? 0 : prev + 1));
              }}
              aria-label="Next image"
            >
              ⟩
            </button>
          )}

          {/* Centered Image display container */}
          <div
            className="relative max-w-full max-h-[85vh] flex flex-col items-center justify-center animate-in zoom-in-95 duration-300 mb-16"
            onClick={e => e.stopPropagation()}
          >
            <div className="relative">
              <img
                src={getImageUrl(images[selectedImage])}
                alt={product.name}
                className="max-w-full max-h-[75vh] object-contain shadow-2xl"
                style={{ border: '1px solid rgba(255,255,255,0.05)' }}
              />
              {/* Lightbox Watermark */}
              <img
                src="/logo-white.png"
                alt="Watermark"
                className="absolute bottom-4 right-4 z-30 h-8 md:h-10 opacity-90 select-none pointer-events-none mix-blend-difference"
              />
            </div>
            {/* Caption */}
            <div className="mt-4 text-center">
              <span className="text-white/80 text-sm tracking-widest uppercase font-semibold" style={{ fontFamily: 'Manrope, sans-serif' }}>
                {product.name}
              </span>
              {images.length > 1 && (
                <span className="text-white/40 text-xs tracking-wider block mt-1" style={{ fontFamily: 'Manrope, sans-serif' }}>
                  Image {selectedImage + 1} of {images.length}
                </span>
              )}
            </div>
          </div>

          {/* Screen Bottom Fixed Thumbnails (inside Lightbox Modal) */}
          {images.length > 1 && (
            <div 
              className="absolute bottom-6 left-1/2 -translate-x-1/2 z-[120] flex gap-2.5 px-3 py-2 bg-white/10 backdrop-blur-md border border-white/10 shadow-2xl rounded-xl max-w-[90%] overflow-x-auto"
              onClick={e => e.stopPropagation()}
            >
              {images.map((img, idx) => (
                <button
                  key={idx}
                  onClick={() => handleSelectImage(idx)}
                  className="flex-shrink-0 w-12 h-12 overflow-hidden transition-all duration-300"
                  style={{
                    background: '#111',
                    border: selectedImage === idx ? '2px solid #ffffff' : '2px solid transparent',
                    borderRadius: '6px',
                    transform: selectedImage === idx ? 'scale(1.05)' : 'none',
                  }}
                >
                  {img ? (
                    <img src={getImageUrl(img)} alt={`View ${idx + 1}`} className="w-full h-full object-cover pointer-events-none select-none" loading="lazy" />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center">
                      <span style={{ color: '#444', fontSize: '8px' }}>✦</span>
                    </div>
                  )}
                </button>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
