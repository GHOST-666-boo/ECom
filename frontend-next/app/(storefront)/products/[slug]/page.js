import ProductDetailPageClient from '@/components/product/ProductDetailPageClient';
import JsonLd from '@/components/seo/JsonLd';
import axios from '@/lib/axios';

// Enable ISR revalidation every 60 seconds
export const revalidate = 60;

/**
 * generateMetadata
 * 
 * Fetches the product details to dynamically set SEO tags
 * (title, meta description) for search engines.
 */
export async function generateMetadata({ params }) {
  const resolvedParams = await params;
  const { slug } = resolvedParams;
  try {
    const response = await axios.get(`/products/${slug}`);
    if (response.data?.success && response.data.product) {
      const product = response.data.product;
      // Strip HTML tags for meta description
      const plainDesc = product.description
        ? product.description.replace(/<[^>]*>/g, '').slice(0, 160)
        : `Explore ${product.name} handcrafted jewelry and metalwork at Vriddhi.`;
      
      return {
        title: `${product.name} | Vriddhi Handicrafts & Jewelry`,
        description: plainDesc,
      };
    }
  } catch (error) {
    console.error('Failed to generate product metadata:', error);
  }
  
  return {
    title: 'Product Details | Vriddhi',
    description: 'Handcrafted objects of intention.',
  };
}

/**
 * generateStaticParams
 * 
 * Pre-renders all products for instant static page loading.
 */
export async function generateStaticParams() {
  try {
    const response = await axios.get('/products');
    if (response.data?.success && response.data.products) {
      return response.data.products.map((product) => ({
        slug: product.slug,
      }));
    }
  } catch (error) {
    console.error('Failed to generate static params for products:', error);
  }
  return [];
}

export default async function ProductDetailPage({ params }) {
  const resolvedParams = await params;
  const { slug } = resolvedParams;
  
  let jsonLdData = null;

  try {
    const response = await axios.get(`/products/${slug}`);
    if (response.data?.success && response.data.product) {
      const product = response.data.product;
      const baseUrl = process.env.NEXT_PUBLIC_SITE_URL || 'https://vriddhi.in';
      const imageUrl = product.image_urls?.[0] || '';
      
      jsonLdData = {
        '@context': 'https://schema.org',
        '@type': 'Product',
        'name': product.name,
        'image': imageUrl,
        'description': product.description ? product.description.replace(/<[^>]*>/g, '') : '',
        'sku': product.sku || `VRIDDHI-${product.id}`,
        'offers': {
          '@type': 'Offer',
          'url': `${baseUrl}/products/${product.slug}`,
          'priceCurrency': 'INR',
          'price': product.price,
          'itemCondition': 'https://schema.org/NewCondition',
          'availability': product.stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        }
      };
    }
  } catch (error) {
    console.error('Failed to load JSON-LD schema for product:', error);
  }

  return (
    <>
      {jsonLdData && <JsonLd data={jsonLdData} />}
      <ProductDetailPageClient slug={slug} />
    </>
  );
}
