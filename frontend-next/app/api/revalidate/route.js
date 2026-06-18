import { revalidatePath } from 'next/cache';

/**
 * On-Demand Revalidation Route Handler
 * 
 * Supports both GET and POST requests.
 * Usage: /api/revalidate?secret=YOUR_SECRET_TOKEN&path=/products/some-product-slug
 */
async function handleRevalidate(request) {
  const { searchParams } = new URL(request.url);
  const secret = searchParams.get('secret');
  const path = searchParams.get('path');

  const revalidateSecret = process.env.REVALIDATE_SECRET || 'vriddhi_revalidation_secret_2026';

  if (secret !== revalidateSecret) {
    return new Response(JSON.stringify({ success: false, message: 'Invalid token' }), {
      status: 401,
      headers: { 'Content-Type': 'application/json' },
    });
  }

  if (!path) {
    return new Response(JSON.stringify({ success: false, message: 'Path parameter is required' }), {
      status: 400,
      headers: { 'Content-Type': 'application/json' },
    });
  }

  try {
    // Revalidate the specified page path
    revalidatePath(path);
    return new Response(
      JSON.stringify({ 
        success: true, 
        message: `Path '${path}' revalidated successfully`, 
        now: Date.now() 
      }), 
      {
        headers: { 'Content-Type': 'application/json' },
      }
    );
  } catch (err) {
    return new Response(JSON.stringify({ success: false, message: err.message }), {
      status: 500,
      headers: { 'Content-Type': 'application/json' },
    });
  }
}

export async function GET(request) {
  return handleRevalidate(request);
}

export async function POST(request) {
  return handleRevalidate(request);
}
