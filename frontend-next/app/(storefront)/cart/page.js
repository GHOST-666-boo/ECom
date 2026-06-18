'use client';

import ProtectedRoute from '../../../components/auth/ProtectedRoute';
import Cart from '../../../components/cart/Cart';

/**
 * CartPage Component (Next.js adapted)
 * 
 * Renders the shopping cart view, protected by the auth guard.
 */
export default function CartPage() {
  return (
    <ProtectedRoute>
      <Cart />
    </ProtectedRoute>
  );
}
