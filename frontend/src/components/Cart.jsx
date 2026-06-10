import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import useCartStore from '../stores/cartStore';
import { getImageUrl } from '../lib/imageUrl';

/**
 * Cart - Metallic Vriddhi (Oat Edition)
 * Editorial "Your Selection" header + item list + sticky summary sidebar
 */
export default function Cart() {
  const { items, subtotal, isLoading, fetchCart, updateItem, removeItem } = useCartStore();
  const [updatingItems, setUpdatingItems] = useState({});
  const [removingItems, setRemovingItems] = useState({});

  useEffect(() => { fetchCart(); }, [fetchCart]);

  const handleQuantityChange = async (cartItemId, newQuantity) => {
    if (newQuantity < 1) return;
    setUpdatingItems(prev => ({ ...prev, [cartItemId]: true }));
    const result = await updateItem(cartItemId, newQuantity);
    if (!result.success) alert(result.message);
    setUpdatingItems(prev => ({ ...prev, [cartItemId]: false }));
  };

  const handleRemoveItem = async (cartItemId) => {
    setRemovingItems(prev => ({ ...prev, [cartItemId]: true }));
    const result = await removeItem(cartItemId);
    if (!result.success) {
      alert(result.message);
      setRemovingItems(prev => ({ ...prev, [cartItemId]: false }));
    }
  };

  /* ── Loading ── */
  if (isLoading) {
    return (
      <div className="max-w-screen-xl mx-auto px-8 py-32 flex justify-center">
        <div
          className="w-12 h-12 border-2 rounded-full animate-spin"
          style={{ borderColor: '#e5e2e1', borderTopColor: '#4c3e25' }}
        />
      </div>
    );
  }

  /* ── Empty Cart ── */
  if (items.length === 0) {
    return (
      <div style={{ background: '#fcf9f8', minHeight: '100vh' }}>
        <div className="max-w-screen-xl mx-auto px-8 pt-12 pb-20">
          <h1
            className="text-6xl md:text-8xl italic leading-none mb-16"
            style={{ fontFamily: 'Noto Serif, serif', letterSpacing: '-0.03em', color: '#463f38' }}
          >
            Your Selection
          </h1>
          <div className="text-center py-24 px-16" style={{ background: '#f6f3f2' }}>
            <p className="text-5xl mb-8" style={{ color: '#cfc5bc' }}>∅</p>
            <h2
              className="text-2xl italic mb-4"
              style={{ fontFamily: 'Noto Serif, serif', color: '#4d453f' }}
            >
              Your folio is empty
            </h2>
            <p
              className="text-sm mb-10 max-w-sm mx-auto"
              style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
            >
              Add some beautiful handcrafted artifacts to begin your curation.
            </p>
            <Link
              to="/products"
              className="inline-block px-10 py-4 text-xs uppercase tracking-[0.2em] font-bold transition-opacity hover:opacity-80"
              style={{ background: '#463f38', color: '#ffffff', fontFamily: 'Manrope, sans-serif' }}
            >
              Browse Collection
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div style={{ background: '#fcf9f8', minHeight: '100vh' }}>
      <div className="max-w-screen-xl mx-auto px-6 pt-12 pb-20">

        {/* Header */}
        <header className="mb-16">
          <h1
            className="text-6xl md:text-8xl italic leading-none mb-4"
            style={{ fontFamily: 'Noto Serif, serif', letterSpacing: '-0.03em', color: '#463f38' }}
          >
            Your Selection
          </h1>
          <p style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}>
            A curated assembly of forged intentions, awaiting their journey.
          </p>
        </header>

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-16 items-start">

          {/* ── Cart Items ── */}
          <section className="lg:col-span-8 space-y-0">
            <h2
              className="text-xs uppercase tracking-widest mb-8"
              style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
            >
              Items ({items.length})
            </h2>

            {items.map((item) => (
              <article
                key={item.id}
                className="group flex flex-col md:flex-row gap-8 items-center py-8 px-6 transition-colors duration-500"
                style={{
                  background: removingItems[item.id] ? 'rgba(248,244,240,0.3)' : 'rgba(246,243,242,0.4)',
                  opacity: removingItems[item.id] ? 0.5 : 1,
                  borderBottom: '1px solid rgba(207,197,188,0.15)',
                }}
              >
                {/* Product Image */}
                <div
                  className="w-full md:w-44 aspect-square overflow-hidden flex-shrink-0"
                  style={{ background: '#eae7e7' }}
                >
                  {item.product?.image_urls?.length > 0 ? (
                    <img
                      src={item.product.image_urls[0]}
                      alt={item.product?.name || 'Product'}
                      className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                    />
                  ) : item.product?.images?.length > 0 ? (
                    <img
                      src={getImageUrl(item.product.images[0])}
                      alt={item.product?.name || 'Product'}
                      className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center">
                      <span className="text-3xl" style={{ color: '#cfc5bc' }}>✦</span>
                    </div>
                  )}
                </div>

                {/* Product Info */}
                <div className="flex-1 space-y-3 text-center md:text-left">
                  <div>
                    <h3
                      className="text-xl transition-opacity hover:opacity-70"
                      style={{ fontFamily: 'Noto Serif, serif', color: '#463f38' }}
                    >
                      <Link to={`/products/${item.product?.slug}`}>{item.product?.name || 'Unavailable Product'}</Link>
                    </h3>
                    <p className="text-sm mt-1" style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}>
                      ₹{parseFloat(item.product?.price || 0).toLocaleString('en-IN')} each
                    </p>
                  </div>

                  {/* Quantity Controls */}
                  <div className="flex items-center justify-center md:justify-start gap-4">
                    <div
                      className="flex items-center"
                      style={{ background: '#eae7e7' }}
                    >
                      <button
                        onClick={() => handleQuantityChange(item.id, item.quantity - 1)}
                        disabled={updatingItems[item.id] || item.quantity <= 1}
                        className="px-3 py-1 text-sm transition-colors hover:opacity-60 disabled:opacity-30"
                        style={{ color: '#463f38' }}
                      >
                        −
                      </button>
                      <span
                        className="px-4 py-1 text-sm min-w-[2.5rem] text-center"
                        style={{ color: '#463f38', fontFamily: 'Manrope, sans-serif' }}
                      >
                        {updatingItems[item.id] ? '…' : item.quantity}
                      </span>
                      <button
                        onClick={() => handleQuantityChange(item.id, item.quantity + 1)}
                        disabled={updatingItems[item.id]}
                        className="px-3 py-1 text-sm transition-colors hover:opacity-60 disabled:opacity-30"
                        style={{ color: '#463f38' }}
                      >
                        +
                      </button>
                    </div>
                    <button
                      onClick={() => handleRemoveItem(item.id)}
                      disabled={removingItems[item.id]}
                      className="text-xs uppercase tracking-widest transition-colors hover:opacity-60 disabled:opacity-30"
                      style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                    >
                      Remove
                    </button>
                  </div>
                </div>

                {/* Line Price */}
                <div className="text-right flex-shrink-0">
                  <span
                    className="text-xl"
                    style={{ fontFamily: 'Noto Serif, serif', color: '#4c3e25' }}
                  >
                    ₹{(item.quantity * parseFloat(item.product?.price || 0)).toLocaleString('en-IN')}
                  </span>
                </div>
              </article>
            ))}
          </section>

          {/* ── Order Summary Sidebar ── */}
          <aside className="lg:col-span-4 sticky top-32">
            <div
              className="p-10 space-y-8"
              style={{ background: '#f6f3f2' }}
            >
              <h2
                className="text-3xl italic"
                style={{ fontFamily: 'Noto Serif, serif', color: '#463f38' }}
              >
                Summary
              </h2>

              <div className="space-y-4" style={{ borderBottom: '1px solid rgba(207,197,188,0.20)', paddingBottom: '24px' }}>
                <div className="flex justify-between text-sm">
                  <span style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}>
                    Subtotal ({items.length} {items.length === 1 ? 'item' : 'items'})
                  </span>
                  <span className="font-medium" style={{ color: '#1b1b1c', fontFamily: 'Manrope, sans-serif' }}>
                    ₹{subtotal.toLocaleString('en-IN')}
                  </span>
                </div>
                <div className="flex justify-between text-sm">
                  <span style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}>Shipping</span>
                  <span className="font-medium" style={{ color: '#1b1b1c', fontFamily: 'Manrope, sans-serif' }}>Calculated next</span>
                </div>
              </div>

              <div className="flex justify-between items-end">
                <span
                  className="text-xs uppercase tracking-widest"
                  style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                >
                  Total Est.
                </span>
                <span
                  className="text-4xl tracking-tighter"
                  style={{ fontFamily: 'Noto Serif, serif', color: '#4c3e25' }}
                >
                  ₹{subtotal.toLocaleString('en-IN')}
                </span>
              </div>

              <div className="space-y-4">
                <Link
                  to="/checkout"
                  className="w-full py-5 text-sm uppercase tracking-[0.2em] font-bold flex items-center justify-center gap-3 transition-opacity hover:opacity-90"
                  style={{
                    background: 'linear-gradient(135deg, #4c3e25 0%, #65553a 100%)',
                    color: '#ffffff',
                    fontFamily: 'Manrope, sans-serif',
                    display: 'flex',
                    boxShadow: '0 8px 24px rgba(76,62,37,0.18)',
                  }}
                >
                  Proceed to Checkout →
                </Link>
                <p
                  className="text-[10px] text-center uppercase tracking-widest"
                  style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                >
                  Secure & encrypted checkout
                </p>
              </div>

              {/* Payment icons */}
              <div className="pt-4 space-y-3">
                <span
                  className="block text-[10px] uppercase tracking-[0.15em] text-center"
                  style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
                >
                  Secure Methods
                </span>
                <div className="flex justify-center gap-6 opacity-40">
                  💳 🏦 📱
                </div>
              </div>
            </div>

            <div className="mt-6 text-center">
              <Link
                to="/products"
                className="text-xs uppercase tracking-widest transition-colors hover:opacity-70"
                style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
              >
                ← Continue Shopping
              </Link>
            </div>
          </aside>
        </div>
      </div>
    </div>
  );
}
