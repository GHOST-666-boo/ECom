'use client';

import React, { useState, useEffect } from 'react';
import Header from '../../components/layout/Header';
import Footer from '../../components/layout/Footer';
import AuthModal from '../../components/auth/AuthModal';
import useAuthStore from '../../stores/authStore';
import useCartStore from '../../stores/cartStore';
import { useHasHydrated } from '../../hooks/useHasHydrated';

/**
 * StorefrontLayout Component (Next.js route group layout)
 * 
 * Shared layout for storefront pages (Home, Products, Cart, checkout, etc.)
 * Provides Header, Footer, global AuthModal, and loads the active cart state.
 */
export default function StorefrontLayout({ children }) {
  const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);
  const { isAuthenticated } = useAuthStore();
  const { fetchCart } = useCartStore();
  const hasHydrated = useHasHydrated();

  // Load cart once user is authenticated on the client
  useEffect(() => {
    if (hasHydrated && isAuthenticated) {
      fetchCart();
    }
  }, [hasHydrated, isAuthenticated]);

  return (
    <div className="min-h-screen flex flex-col" style={{ background: '#fcf9f8', color: '#1b1b1c' }}>
      <Header onAuthTrigger={() => setIsAuthModalOpen(true)} />
      
      {/* Main Content */}
      <main className="flex-grow pt-[73px]">
        {children}
      </main>

      <Footer />

      {/* Global Auth Modal */}
      <AuthModal isOpen={isAuthModalOpen} onClose={() => setIsAuthModalOpen(false)} />
    </div>
  );
}
