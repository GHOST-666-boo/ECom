'use client';

import { useEffect, useState, useRef } from 'react';
import Link from 'next/link';
import { useSearchParams, useRouter } from 'next/navigation';
import axios from '../../lib/axios';
import { getImageUrl } from '../../lib/imageUrl';
import useAuthStore from '../../stores/authStore';
import useCartStore from '../../stores/cartStore';

/**
 * ProductList - Metallic Vriddhi (Oat Edition) (Next.js adapted)
 * 4-column gallery grid with "Quick Look" hover, editorial typography
 */
export default function ProductList({ limit = null, categoryId: propCategoryId = null, excludeId = null }) {
  const searchParams = useSearchParams();
  const router = useRouter();
  const { isAuthenticated } = useAuthStore();
  const { addItem } = useCartStore();

  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState(null);
  const [nextCursor, setNextCursor] = useState(null);
  const [addingToCart, setAddingToCart] = useState({});
  const [addedToCart, setAddedToCart] = useState({});
  const [cardImageIndex, setCardImageIndex] = useState({});
  const touchStartX = useRef({});
  const dragged = useRef({});

  const urlCategoryId = searchParams.get('category_id');
  const categoryId = propCategoryId !== null ? propCategoryId : urlCategoryId;
  const minPrice = searchParams.get('min_price');
  const maxPrice = searchParams.get('max_price');

  useEffect(() => {
    setProducts([]);
    setNextCursor(null);
    fetchProducts();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [categoryId, minPrice, maxPrice, excludeId]);

  const fetchProducts = async (cursor = null) => {
    try {
      cursor ? setLoadingMore(true) : setLoading(true);
      setError(null);
      const params = new URLSearchParams();
      if (categoryId) params.append('category_id', categoryId);
      if (minPrice) params.append('min_price', minPrice);
      if (maxPrice) params.append('max_price', maxPrice);
      if (cursor) params.append('cursor', cursor);
      const response = await axios.get(`/products?${params.toString()}`);
      if (response.data?.success) {
        let newProducts = response.data.products || [];
        if (excludeId) {
          newProducts = newProducts.filter(p => p.id !== excludeId);
        }

        // Fallback: if we filtered by category but found no other products,
        // load general products from other categories instead of showing empty state.
        if (newProducts.length === 0 && categoryId && excludeId && !cursor) {
          const fallbackParams = new URLSearchParams();
          if (minPrice) fallbackParams.append('min_price', minPrice);
          if (maxPrice) fallbackParams.append('max_price', maxPrice);
          const fallbackResponse = await axios.get(`/products?${fallbackParams.toString()}`);
          if (fallbackResponse.data?.success) {
            let fallbackProducts = fallbackResponse.data.products || [];
            newProducts = fallbackProducts.filter(p => p.id !== excludeId);
          }
        }

        setProducts(prev => {
          const combined = cursor ? [...prev, ...newProducts] : newProducts;
          return limit ? combined.slice(0, limit) : combined;
        });
        setNextCursor(limit ? null : (response.data.meta?.next_cursor || null));
      } else {
        setError('Failed to load products');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load products. Please try again.');
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  };

  const handleAddToCart = async (productId) => {
    if (!isAuthenticated) {
      router.push('/?loginRequired=true');
      return;
    }
    try {
      setAddingToCart(prev => ({ ...prev, [productId]: true }));
      const result = await addItem(productId, 1);
      if (result?.success) {
        setAddedToCart(prev => ({ ...prev, [productId]: true }));
        setTimeout(() => setAddedToCart(prev => ({ ...prev, [productId]: false })), 2000);
      } else {
        alert(result?.message || 'Failed to add to cart');
      }
    } catch {
      alert('Failed to add to cart');
    } finally {
      setAddingToCart(prev => ({ ...prev, [productId]: false }));
    }
  };

  /* ── Loading Skeleton ── */
  if (loading) {
    return (
      <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-10 sm:gap-x-8 sm:gap-y-16">
        {[1, 2, 3, 4, 5, 6, 7, 8].map(i => (
          <div key={i} className="animate-pulse">
            <div className="aspect-[3/4] mb-6" style={{ background: '#f0eded' }} />
            <div className="h-3 mb-2" style={{ background: '#e5e2e1', width: '70%' }} />
            <div className="h-3" style={{ background: '#e5e2e1', width: '40%' }} />
          </div>
        ))}
      </div>
    );
  }

  /* ── Error ── */
  if (error) {
    return (
      <div className="text-center py-16">
        <p className="mb-6 text-sm" style={{ color: '#ba1a1a', fontFamily: 'Manrope, sans-serif' }}>{error}</p>
        <button
          onClick={() => fetchProducts()}
          className="px-8 py-3 text-xs uppercase tracking-widest font-bold transition-opacity hover:opacity-90"
          style={{ background: '#463f38', color: '#ffffff', fontFamily: 'Manrope, sans-serif' }}
        >
          Retry
        </button>
      </div>
    );
  }

  /* ── Empty State ── */
  if (products.length === 0) {
    return (
      <div className="text-center py-24">
        <p
          className="text-5xl mb-8 italic"
          style={{ fontFamily: 'Noto Serif, serif', color: '#cfc5bc' }}
        >
          ∅
        </p>
        <p className="text-lg mb-2" style={{ color: '#4d453f', fontFamily: 'Noto Serif, serif', fontStyle: 'italic' }}>
          No objects found
        </p>
        <p className="text-xs uppercase tracking-widest" style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}>
          Try adjusting your filters
        </p>
      </div>
    );
  }

  return (
    <div>
      {/* ── Product Gallery Grid ── */}
      <div className="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-10 sm:gap-x-8 sm:gap-y-16">
        {products.map((product) => (
          <article 
            key={product.id} 
            className="group" 
            style={{ cursor: 'pointer' }}
            onClick={() => router.push(`/products/${product.slug}`)}
          >
            {/* Image Container with touch+hover carousel */}
            {(() => {
              const allImgs = product.image_urls?.length > 0
                ? product.image_urls
                : (product.images?.length > 0 ? product.images : []);
              const hasMultiple = allImgs.length > 1;
              const currentIdx = cardImageIndex[product.id] || 0;

              const goToNext = () =>
                setCardImageIndex(prev => ({ ...prev, [product.id]: (currentIdx + 1) % allImgs.length }));
              const goToPrev = () =>
                setCardImageIndex(prev => ({ ...prev, [product.id]: (currentIdx - 1 + allImgs.length) % allImgs.length }));

              return (
                <div
                  className="aspect-[3/4] overflow-hidden mb-6 relative select-none"
                  style={{ background: '#f6f3f2', touchAction: 'pan-y' }}
                  onMouseDown={e => {
                    if (hasMultiple) {
                      e.preventDefault();
                      touchStartX.current[product.id] = e.clientX;
                      dragged.current[product.id] = false;
                    }
                  }}
                  onMouseUp={e => {
                    if (!hasMultiple) return;
                    const startX = touchStartX.current[product.id];
                    if (startX == null) return;
                    const diff = startX - e.clientX;
                    if (Math.abs(diff) > 10) {
                      dragged.current[product.id] = true;
                    }
                    if (Math.abs(diff) > 40) {
                      diff > 0 ? goToNext() : goToPrev();
                    }
                    delete touchStartX.current[product.id];
                  }}
                  onMouseLeave={() => {
                    if (touchStartX.current[product.id] != null) {
                      delete touchStartX.current[product.id];
                    }
                  }}
                  onClick={e => {
                    if (dragged.current[product.id]) {
                      e.stopPropagation();
                      e.preventDefault();
                      dragged.current[product.id] = false;
                    }
                  }}
                  onTouchStart={e => {
                    if (hasMultiple) touchStartX.current[product.id] = e.touches[0].clientX;
                  }}
                  onTouchEnd={e => {
                    if (!hasMultiple) return;
                    const startX = touchStartX.current[product.id];
                    if (startX == null) return;
                    const diff = startX - e.changedTouches[0].clientX;
                    if (Math.abs(diff) > 40) {
                      diff > 0 ? goToNext() : goToPrev();
                    }
                    delete touchStartX.current[product.id];
                  }}
                >
                  {/* Sliding image strip */}
                  {allImgs.length > 0 ? (
                    <div
                      className="flex h-full transition-transform duration-500 ease-in-out"
                      style={{
                        width: `${allImgs.length * 100}%`,
                        transform: `translateX(-${currentIdx * (100 / allImgs.length)}%)`,
                      }}
                    >
                      {allImgs.map((imgSrc, imgIdx) => (
                        <img
                          key={imgIdx}
                          src={product.image_urls?.length > 0 ? imgSrc : getImageUrl(imgSrc)}
                          alt={product.name}
                          className="h-full object-cover"
                          style={{ width: `${100 / allImgs.length}%` }}
                          loading="lazy"
                          draggable={false}
                        />
                      ))}
                    </div>
                  ) : (
                    <div
                      className="w-full h-full flex items-center justify-center"
                      style={{ background: 'linear-gradient(135deg, #f0eded 0%, #e5e2e1 100%)' }}
                    >
                      <span className="text-5xl" style={{ color: '#cfc5bc' }}>✦</span>
                    </div>
                  )}

                  {/* Dots — always visible when multiple images */}
                  {hasMultiple && (
                    <div
                      className="absolute left-0 right-0 flex justify-center gap-1.5"
                      style={{ bottom: '10px', pointerEvents: 'auto' }}
                      onClick={e => e.stopPropagation()}
                    >
                      {allImgs.map((_, dotIdx) => (
                        <button
                          key={dotIdx}
                          onClick={e => {
                            e.stopPropagation();
                            setCardImageIndex(prev => ({ ...prev, [product.id]: dotIdx }));
                          }}
                          style={{
                            width: currentIdx === dotIdx ? '18px' : '6px',
                            height: '6px',
                            borderRadius: '3px',
                            background: currentIdx === dotIdx
                              ? 'rgba(70,63,56,0.9)'
                              : 'rgba(70,63,56,0.3)',
                            border: 'none',
                            padding: 0,
                            transition: 'all 0.3s ease',
                            cursor: 'pointer',
                            flexShrink: 0,
                          }}
                        />
                      ))}
                    </div>
                  )}

                  {/* Out of Stock overlay */}
                  {product.stock === 0 && (
                    <div
                      className="absolute inset-0 flex items-center justify-center"
                      style={{ background: 'rgba(252,249,248,0.7)' }}
                    >
                      <span
                        className="text-[10px] uppercase tracking-widest font-bold px-4 py-2"
                        style={{
                          background: '#1b1b1c',
                          color: '#f6f3f2',
                          fontFamily: 'Manrope, sans-serif',
                        }}
                      >
                        Out of Stock
                      </span>
                    </div>
                  )}

                  {/* Add to Cart */}
                  {product.stock > 0 && (
                    <div
                      className="absolute left-0 right-0 flex justify-center transition-all duration-300 opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0"
                      style={{ bottom: hasMultiple ? '30px' : '16px' }}
                    >
                      <button
                        onClick={e => {
                          e.stopPropagation();
                          handleAddToCart(product.id);
                        }}
                        disabled={addingToCart[product.id]}
                        className="px-6 py-2 text-[10px] uppercase font-bold tracking-widest transition-opacity hover:opacity-80"
                        style={{
                          background: addedToCart[product.id] ? '#4c3e25' : '#fcf9f8',
                          color: addedToCart[product.id] ? '#ffffff' : '#463f38',
                          fontFamily: 'Manrope, sans-serif',
                          boxShadow: '0 4px 16px rgba(27,27,28,0.12)',
                        }}
                      >
                        {addingToCart[product.id] ? '...' : addedToCart[product.id] ? '✓ Added' : 'Add to Cart'}
                      </button>
                    </div>
                  )}
                </div>
              );
            })()}

            {/* Product Info */}
            <div className="space-y-1">
              <div className="flex justify-between items-baseline">
                <Link href={`/products/${product.slug}`} onClick={e => e.stopPropagation()}>
                  <h3
                    className="text-sm font-medium transition-colors hover:opacity-70"
                    style={{ color: '#1b1b1c', fontFamily: 'Manrope, sans-serif' }}
                  >
                    {product.name}
                  </h3>
                </Link>
                <span
                  className="text-sm ml-4 flex-shrink-0"
                  style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
                >
                  ₹{parseFloat(product.price).toLocaleString('en-IN')}
                </span>
              </div>
              {product.category && (
                <p
                  className="text-xs italic"
                  style={{ color: '#7e766e', fontFamily: 'Noto Serif, serif' }}
                >
                  {product.category.name}
                </p>
              )}
            </div>
          </article>
        ))}
      </div>

      {/* Load More */}
      {nextCursor && (
        <div className="text-center mt-20">
          <button
            onClick={() => fetchProducts(nextCursor)}
            disabled={loadingMore}
            className="px-12 py-4 text-xs uppercase tracking-[0.2em] font-bold transition-opacity hover:opacity-80 disabled:opacity-40"
            style={{
              background: 'transparent',
              color: '#463f38',
              border: '1px solid rgba(70,63,56,0.3)',
              fontFamily: 'Manrope, sans-serif',
            }}
          >
            {loadingMore ? 'Loading...' : 'Load More Objects'}
          </button>
        </div>
      )}
    </div>
  );
}
