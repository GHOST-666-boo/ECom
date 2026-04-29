import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import axios from '../lib/axios';
import { getImageUrl } from '../lib/imageUrl';
import useAuthStore from '../stores/authStore';
import useCartStore from '../stores/cartStore';

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

  useEffect(() => { fetchProduct(); }, [slug]); // eslint-disable-line

  const fetchProduct = async () => {
    try {
      setLoading(true); setError(null);
      const response = await axios.get(`/products/${slug}`);
      if (response.data?.success) {
        setProduct(response.data.data?.product || response.data.data);
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

  const images = product.images?.length > 0 ? product.images : [null];

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
        <div className="lg:col-span-7 flex flex-col gap-8">
          {/* Main Image */}
          <div className="aspect-[4/5] overflow-hidden" style={{ background: '#f6f3f2' }}>
            {images[selectedImage] ? (
              <img
                src={getImageUrl(images[selectedImage])}
                alt={product.name}
                className="w-full h-full object-cover"
              />
            ) : (
              <div
                className="w-full h-full flex items-center justify-center"
                style={{ background: 'linear-gradient(135deg, #f6f3f2 0%, #e5e2e1 100%)' }}
              >
                <span className="text-8xl" style={{ color: '#cfc5bc' }}>✦</span>
              </div>
            )}
          </div>

          {/* Thumbnails */}
          {images.length > 1 && (
            <div className="grid grid-cols-2 gap-8">
              {images.map((img, idx) => (
                <button
                  key={idx}
                  onClick={() => setSelectedImage(idx)}
                  className="aspect-square overflow-hidden transition-all"
                  style={{
                    background: '#f6f3f2',
                    outline: selectedImage === idx ? '2px solid #4c3e25' : '2px solid transparent',
                    outlineOffset: '2px',
                  }}
                >
                  {img ? (
                    <img src={getImageUrl(img)} alt={`View ${idx + 1}`} className="w-full h-full object-cover" loading="lazy" />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center">
                      <span style={{ color: '#cfc5bc' }}>✦</span>
                    </div>
                  )}
                </button>
              ))}
            </div>
          )}
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
                {addingToCart ? 'Adding...' : product.stock === 0 ? 'Out of Stock' : 'Add to Collection →'}
              </button>

              <button
                className="w-full py-5 text-sm tracking-[0.1em] uppercase font-medium transition-colors"
                style={{
                  background: '#f0eded',
                  color: '#463f38',
                  fontFamily: 'Manrope, sans-serif',
                }}
                onMouseEnter={e => (e.currentTarget.style.background = '#e5e2e1')}
                onMouseLeave={e => (e.currentTarget.style.background = '#f0eded')}
              >
                Enquire for Custom Commission
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
                  Ships pan-India with artisan care.<br />
                  Carefully packaged for safe delivery.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
