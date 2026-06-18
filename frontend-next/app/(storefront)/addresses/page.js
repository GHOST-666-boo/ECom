'use client';

import ProtectedRoute from '@/components/auth/ProtectedRoute';
import AddressManager from '@/components/address/AddressManager';

/**
 * AddressesPage Component
 * 
 * Adapts the original AddressesPage.jsx. Renders the AddressManager inside a standard container.
 */
function AddressesPageContent() {
  return (
    <div className="container mx-auto px-4 py-8">
      <AddressManager />
    </div>
  );
}

export default function AddressesPage() {
  return (
    <ProtectedRoute>
      <AddressesPageContent />
    </ProtectedRoute>
  );
}
