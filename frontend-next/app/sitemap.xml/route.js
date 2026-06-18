import axios from '@/lib/axios';

export const dynamic = 'force-dynamic';

/**
 * Dynamic sitemap.xml Route Handler
 * 
 * Fetches all products and categories from the Laravel API and builds the XML structure.
 */
export async function GET() {
  const baseUrl = process.env.NEXT_PUBLIC_SITE_URL || 'https://vriddhi.in';

  let products = [];
  let categories = [];

  try {
    const prodRes = await axios.get('/products');
    if (prodRes.data?.success && prodRes.data.products) {
      products = prodRes.data.products;
    }
  } catch (e) {
    console.error('Sitemap product fetch error:', e);
  }

  try {
    const catRes = await axios.get('/categories');
    if (catRes.data?.success && catRes.data.categories) {
      categories = catRes.data.categories;
    }
  } catch (e) {
    console.error('Sitemap category fetch error:', e);
  }

  const staticPaths = [
    '',
    '/products',
    '/contact',
    '/corporate-gifting',
  ];

  let xml = `<?xml version="1.0" encoding="UTF-8"?>\n`;
  xml += `<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">`;

  // Static pages
  staticPaths.forEach((path) => {
    xml += `
  <url>
    <loc>${baseUrl}${path}</loc>
    <lastmod>${new Date().toISOString()}</lastmod>
    <changefreq>daily</changefreq>
    <priority>${path === '' ? '1.0' : '0.8'}</priority>
  </url>`;
  });

  // Dynamic products
  products.forEach((product) => {
    const dateStr = product.updated_at || product.created_at || new Date().toISOString();
    xml += `
  <url>
    <loc>${baseUrl}/products/${product.slug}</loc>
    <lastmod>${new Date(dateStr).toISOString()}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>`;
  });

  // Dynamic categories
  categories.forEach((cat) => {
    const catSlug = cat.slug || cat.name.toLowerCase().replace(/\s+/g, '-');
    xml += `
  <url>
    <loc>${baseUrl}/products/category/${catSlug}</loc>
    <lastmod>${new Date().toISOString()}</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.6</priority>
  </url>`;
  });

  xml += `\n</urlset>`;

  return new Response(xml, {
    headers: {
      'Content-Type': 'application/xml',
      'Cache-Control': 'public, max-age=3600, s-maxage=3600, stale-while-revalidate=600',
    },
  });
}
