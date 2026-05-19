import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import axios from '../lib/axios';
import { getImageUrl } from '../lib/imageUrl';
import useAuthStore from '../stores/authStore';
import useCartStore from '../stores/cartStore';

/**
 * ProductList - Metallic Vriddhi (Oat Edition)
 * 4-column gallery grid with "Quick Look" hover, editorial typography
 */
export default function ProductList() {
  const [searchParams] = useSearchParams();
  const { isAuthenticated } = useAuthStore();
  const { addItem } = useCartStore();

  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState(null);
  const [nextCursor, setNextCursor] = useState(null);
  const [addingToCart, setAddingToCart] = useState({});
  const [addedToCart, setAddedToCart] = useState({});

  const categoryId = searchParams.get('category_id');
  const minPrice = searchParams.get('min_price');
  const maxPrice = searchParams.get('max_price');

  useEffect(() => {
    setProducts([]);
    setNextCursor(null);
    fetchProducts();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [categoryId, minPrice, maxPrice]);

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
        const newProducts = response.data.products || [];  // Clean: response.data.products
        setProducts(prev => cursor ? [...prev, ...newProducts] : newProducts);
        setNextCursor(response.data.meta?.next_cursor || null);
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
      window.location.href = '/?loginRequired=true';
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
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-16">
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
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-16">
        {products.map((product) => (
          <article key={product.id} className="group" style={{ cursor: 'pointer' }}>

            {/* Image Container */}
            <div
              className="aspect-[3/4] overflow-hidden mb-6 relative"
              style={{ background: '#f6f3f2' }}
            >
              {product.image_urls?.length > 0 ? (
                <img
                  src={product.image_urls[0]}
                  alt={product.name}
                  className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                  loading="lazy"
                />
              ) : product.images && product.images.length > 0 ? (
                <img
                  src={getImageUrl(product.images[0])}
                  alt={product.name}
                  className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                  loading="lazy"
                />
              ) : (
                <div
                  className="w-full h-full flex items-center justify-center"
                  style={{ background: 'linear-gradient(135deg, #f0eded 0%, #e5e2e1 100%)' }}
                >
                  <span className="text-5xl" style={{ color: '#cfc5bc' }}>✦</span>
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

              {/* Quick Look overlay */}
              {product.stock > 0 && (
                <div
                  className="absolute bottom-4 left-0 right-0 flex justify-center transition-all duration-300 opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0"
                >
                  <button
                    onClick={() => handleAddToCart(product.id)}
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

            {/* Product Info */}
            <div className="space-y-1">
              <div className="flex justify-between items-baseline">
                <Link to={`/products/${product.slug}`}>
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
