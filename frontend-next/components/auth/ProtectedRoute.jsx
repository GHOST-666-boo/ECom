'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import useAuthStore from '../../stores/authStore';
import { useHasHydrated } from '../../hooks/useHasHydrated';

/**
 * ProtectedRoute Component (Next.js adapted)
 * 
 * Guards routes by redirecting to home page with `loginRequired=true` if not authenticated.
 * Incorporates hydration guard to avoid flickering/incorrect redirects during SSR.
 */
export default function ProtectedRoute({ children }) {
  const router = useRouter();
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);
  const hasHydrated = useHasHydrated();

  useEffect(() => {
    if (hasHydrated && !isAuthenticated) {
      router.replace('/?loginRequired=true');
    }
  }, [hasHydrated, isAuthenticated, router]);

  if (!hasHydrated) {
    return null;
  }

  if (!isAuthenticated) {
    return null;
  }

  return children;
}
