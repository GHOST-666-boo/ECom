import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import axios from '../lib/axios';
import { getImageUrl } from '../lib/imageUrl';

/**
 * CategoryList Component
 * 
 * Fetches categories from API: GET /api/v1/categories
 * Displays category grid with images and names
 * Links to product listing filtered by category
 * 
 * Requirements: 3.1
 */
export default function CategoryList() {
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [imageErrors, setImageErrors] = useState({});

  useEffect(() => {
    fetchCategories();
  }, []);

  const fetchCategories = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await axios.get('/categories');
      
      if (response.data.success) {
        // Now: response.data.categories (much cleaner!)
        const categoriesData = response.data.categories || [];
        setCategories(Array.isArray(categoriesData) ? categoriesData : []);
      } else {
        setError('Failed to load categories');
        setCategories([]);
      }
    } catch (err) {
      console.error('Error fetching categories:', err);
      setError('Failed to load categories. Please try again later.');
      setCategories([]);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
        {[1, 2, 3, 4].map((i) => (
          <div key={i} className="bg-[#f6f3f0] p-6 animate-pulse">
            <div className="w-full h-32 bg-[#e8dfd7] mb-4"></div>
            <div className="h-4 bg-[#e8dfd7] w-3/4 mx-auto"></div>
          </div>
        ))}
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-center py-8">
        <p className="text-[#ba1a1a] mb-4">{error}</p>
        <button
          onClick={fetchCategories}
          className="bg-[#2c2825] text-[#fcf9f6] px-6 py-2 hover:bg-[#1f1b18] transition-colors"
        >
          Retry
        </button>
      </div>
    );
  }

  if (categories.length === 0) {
    return (
      <div className="text-center py-8 text-gray-500">
        No categories available at the moment.
      </div>
    );
  }

  return (
    <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
      {categories.map((category) => (
        <Link
          key={category.id}
          to={`/products?category_id=${category.id}`}
          className="bg-[#f6f3f0] overflow-hidden group transition-colors hover:bg-[#f2ece5]"
        >
          <div className="aspect-square overflow-hidden p-6">
            {category.image && !imageErrors[category.id] ? (
              <img
                src={getImageUrl(category.image)}
                alt={category.name}
                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                loading="lazy"
                onError={() => setImageErrors(prev => ({ ...prev, [category.id]: true }))}
              />
            ) : (
              <div className="w-full h-full bg-gradient-to-br from-[#2c2825] to-[#745b21] flex items-center justify-center">
                <span className="text-4xl text-[#fcf9f6] font-semibold">
                  {category.name.charAt(0)}
                </span>
              </div>
            )}
          </div>
          <div className="p-4 text-center">
            <h3 className="font-semibold text-[#2c2825] group-hover:text-[#745b21] transition-colors">
              {category.name}
            </h3>
          </div>
        </Link>
      ))}
    </div>
  );
}
