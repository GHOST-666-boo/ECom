import { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import ProductList from '../components/ProductList';
import ErrorBoundary from '../components/ErrorBoundary';
import axios from '../lib/axios';

/**
 * ProductsPage - Metallic Vriddhi (Oat Edition)
 * Editorial header + minimalist pill filters + bento product grid
 */
export default function ProductsPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [categories, setCategories] = useState([]);
  const [minPrice, setMinPrice] = useState(searchParams.get('min_price') || '');
  const [maxPrice, setMaxPrice] = useState(searchParams.get('max_price') || '');
  const [selectedCategory, setSelectedCategory] = useState(searchParams.get('category_id') || '');
  const [showFilters, setShowFilters] = useState(false);

  useEffect(() => { fetchCategories(); }, []);

  const fetchCategories = async () => {
    try {
      const response = await axios.get('/categories');
      if (response.data.success) {
        // Clean: response.data.categories
        const categoriesData = response.data.categories || [];
        setCategories(Array.isArray(categoriesData) ? categoriesData : []);
      }
    } catch (err) {
      console.error('Error fetching categories:', err);
      setCategories([]);
    }
  };

  const handleFilterChange = () => {
    const params = new URLSearchParams();
    if (selectedCategory) params.append('category_id', selectedCategory);
    if (minPrice) params.append('min_price', minPrice);
    if (maxPrice) params.append('max_price', maxPrice);
    setSearchParams(params);
    setShowFilters(false);
  };

  const handleClearFilters = () => {
    setSelectedCategory(''); setMinPrice(''); setMaxPrice('');
    setSearchParams({});
  };

  const hasActiveFilters = selectedCategory || minPrice || maxPrice;

  return (
    <div style={{ background: '#fcf9f8', color: '#1b1b1c', minHeight: '100vh' }}>

      {/* ── Editorial Header ── */}
      <div className="pt-12 pb-8 px-8 max-w-screen-2xl mx-auto">
        <h1
          className="text-6xl md:text-8xl leading-tight mb-4"
          style={{
            fontFamily: 'Noto Serif, serif',
            fontStyle: 'italic',
            letterSpacing: '-0.03em',
            color: '#463f38',
          }}
        >
          The Metalsmith's<br />Collection
        </h1>
        <p
          className="max-w-xl text-lg leading-relaxed"
          style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
        >
          A curated selection of artisanal artifacts, forged with intention and finished
          with time-honoured patinas.
        </p>
      </div>

      {/* ── Filter Bar ── */}
      <div
        className="px-8 max-w-screen-2xl mx-auto"
        style={{ borderBottom: '1px solid rgba(207,197,188,0.15)', paddingBottom: '24px', marginBottom: '32px' }}
      >
        <div className="flex flex-col md:flex-row justify-between items-start md:items-end gap-6">

          {/* Category Pill Filters */}
          <div className="flex flex-wrap gap-3">
            <button
              onClick={handleClearFilters}
              className="px-6 py-2 text-xs tracking-widest uppercase font-medium transition-all"
              style={{
                background: !hasActiveFilters ? '#e5e2e1' : 'transparent',
                color: !hasActiveFilters ? '#463f38' : '#4d453f',
                fontFamily: 'Manrope, sans-serif',
                border: '1px solid transparent',
              }}
            >
              All Objects
            </button>
            {categories.map(cat => (
              <button
                key={cat.id}
                onClick={() => {
                  setSelectedCategory(cat.id);
                  const params = new URLSearchParams();
                  params.append('category_id', cat.id);
                  if (minPrice) params.append('min_price', minPrice);
                  if (maxPrice) params.append('max_price', maxPrice);
                  setSearchParams(params);
                }}
                className="px-6 py-2 text-xs tracking-widest uppercase font-medium transition-all"
                style={{
                  background: selectedCategory === String(cat.id) ? '#e5e2e1' : 'transparent',
                  color: selectedCategory === String(cat.id) ? '#463f38' : '#4d453f',
                  fontFamily: 'Manrope, sans-serif',
                }}
                onMouseEnter={e => { if (selectedCategory !== String(cat.id)) e.currentTarget.style.background = '#f6f3f2'; }}
                onMouseLeave={e => { if (selectedCategory !== String(cat.id)) e.currentTarget.style.background = 'transparent'; }}
              >
                {cat.name}
              </button>
            ))}
          </div>

          {/* Price Filter Toggle */}
          <div className="flex items-center gap-4">
            <button
              onClick={() => setShowFilters(!showFilters)}
              className="flex items-center gap-1 text-xs tracking-widest uppercase"
              style={{ color: '#463f38', fontFamily: 'Manrope, sans-serif' }}
            >
              Price Range {showFilters ? '▲' : '▼'}
            </button>
          </div>
        </div>

        {/* Price Range Inputs */}
        {showFilters && (
          <div
            className="mt-6 flex flex-wrap gap-4 items-end p-6"
            style={{ background: '#f6f3f2' }}
          >
            <div className="flex flex-col gap-1">
              <label
                className="text-[10px] uppercase tracking-widest"
                style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
              >
                Min Price
              </label>
              <input
                type="number"
                placeholder="0"
                value={minPrice}
                onChange={e => setMinPrice(e.target.value)}
                min="0"
                className="bg-transparent py-2 text-sm w-32 focus:outline-none"
                style={{
                  borderBottom: '1px solid #7e766e',
                  color: '#1b1b1c',
                  fontFamily: 'Manrope, sans-serif',
                }}
              />
            </div>
            <div className="flex flex-col gap-1">
              <label
                className="text-[10px] uppercase tracking-widest"
                style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
              >
                Max Price
              </label>
              <input
                type="number"
                placeholder="999999"
                value={maxPrice}
                onChange={e => setMaxPrice(e.target.value)}
                min="0"
                className="bg-transparent py-2 text-sm w-32 focus:outline-none"
                style={{
                  borderBottom: '1px solid #7e766e',
                  color: '#1b1b1c',
                  fontFamily: 'Manrope, sans-serif',
                }}
              />
            </div>
            <button
              onClick={handleFilterChange}
              className="px-8 py-2 text-xs uppercase tracking-widest font-bold transition-opacity hover:opacity-90"
              style={{
                background: '#463f38',
                color: '#ffffff',
                fontFamily: 'Manrope, sans-serif',
              }}
            >
              Apply
            </button>
            {hasActiveFilters && (
              <button
                onClick={handleClearFilters}
                className="px-4 py-2 text-xs uppercase tracking-widest"
                style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
              >
                Clear All
              </button>
            )}
          </div>
        )}
      </div>

      {/* ── Products Grid ── */}
      <div className="px-8 max-w-screen-2xl mx-auto pb-24">
        <ErrorBoundary>
          <ProductList />
        </ErrorBoundary>
      </div>
    </div>
  );
}
