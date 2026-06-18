import ProductList from '@/components/product/ProductList';
import ErrorBoundary from '@/components/common/ErrorBoundary';
import axios from '@/lib/axios';

// Enable ISR revalidation every 60 seconds
export const revalidate = 60;

/**
 * generateMetadata
 * 
 * Sets dynamic SEO tags based on category slug.
 */
export async function generateMetadata({ params }) {
  const resolvedParams = await params;
  const { slug } = resolvedParams;
  try {
    const response = await axios.get('/categories');
    if (response.data?.success && response.data.categories) {
      const category = response.data.categories.find(
        (c) => c.slug === slug || c.name.toLowerCase().replace(/\s+/g, '-') === slug
      );
      if (category) {
        return {
          title: `${category.name} | Vriddhi Collection`,
          description: `Discover handcrafted ${category.name.toLowerCase()} at Vriddhi.`,
        };
      }
    }
  } catch (error) {
    console.error('Failed to generate category metadata:', error);
  }
  return {
    title: 'Category Collection | Vriddhi',
    description: 'Explore our curated collections.',
  };
}

/**
 * generateStaticParams
 * 
 * Pre-renders all category pages at build time.
 */
export async function generateStaticParams() {
  try {
    const response = await axios.get('/categories');
    if (response.data?.success && response.data.categories) {
      return response.data.categories.map((cat) => ({
        slug: cat.slug || cat.name.toLowerCase().replace(/\s+/g, '-'),
      }));
    }
  } catch (error) {
    console.error('Failed to generate static params for categories:', error);
  }
  return [];
}

export default async function CategoryPage({ params }) {
  const resolvedParams = await params;
  const { slug } = resolvedParams;
  
  let categoryId = null;
  let categoryName = 'Collection';
  
  try {
    const response = await axios.get('/categories');
    if (response.data?.success && response.data.categories) {
      const category = response.data.categories.find(
        (c) => c.slug === slug || c.name.toLowerCase().replace(/\s+/g, '-') === slug
      );
      if (category) {
        categoryId = category.id;
        categoryName = category.name;
      }
    }
  } catch (error) {
    console.error('Failed to fetch category detail:', error);
  }

  return (
    <div style={{ background: '#fcf9f8', color: '#1b1b1c', minHeight: '100vh' }}>
      <div className="pt-12 pb-8 px-8 max-w-screen-2xl mx-auto">
        <h1
          className="text-6xl md:text-8xl leading-tight mb-4 animate-fade-in"
          style={{
            fontFamily: 'Noto Serif, serif',
            fontStyle: 'italic',
            letterSpacing: '-0.03em',
            color: '#463f38',
          }}
        >
          {categoryName}
        </h1>
        <p
          className="max-w-xl text-lg leading-relaxed"
          style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}
        >
          A curated selection of artisanal {categoryName.toLowerCase()} finished with time-honoured patinas.
        </p>
      </div>

      <div className="px-8 max-w-screen-2xl mx-auto pb-24">
        <ErrorBoundary>
          <ProductList categoryId={categoryId} />
        </ErrorBoundary>
      </div>
    </div>
  );
}
