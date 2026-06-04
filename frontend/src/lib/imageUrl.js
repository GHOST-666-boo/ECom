/**
 * getImageUrl - Resolves product image paths to full URLs
 *
 * Backend returns relative paths like: "products/jewelry-xyz.jpg"
 * These need to be prefixed with the Laravel storage URL.
 */
const STORAGE_BASE = import.meta.env.VITE_API_BASE_URL
  ? import.meta.env.VITE_API_BASE_URL.replace('/api/v1', '/storage')
  : 'http://localhost:8000/storage';

export function getImageUrl(path) {
  if (!path || typeof path !== 'string') return null;
  // Already a full URL (http/https)
  if (path.startsWith('http://') || path.startsWith('https://')) return path;
  // Already has /storage/ prefix
  if (path.startsWith('/storage/')) return `http://localhost:8000${path}`;
  // Relative path from backend
  return `${STORAGE_BASE}/${path}`;
}
