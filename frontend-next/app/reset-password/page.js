'use client';

import React, { Suspense } from 'react';
import ResetPasswordForm from '@/components/auth/ResetPasswordForm';

/**
 * ResetPasswordPage Component
 * 
 * Renders the ResetPasswordForm outside of the storefront Header/Footer.
 * Wrapped in Suspense to prevent Next.js build bail-out due to useSearchParams usage inside ResetPasswordForm.
 */
export default function ResetPasswordPage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <div className="inline-block animate-spin rounded-full h-12 w-12 border-2 border-orange-500 border-t-transparent"></div>
          <p className="mt-4 text-gray-600">Loading reset form...</p>
        </div>
      </div>
    }>
      <ResetPasswordForm />
    </Suspense>
  );
}
