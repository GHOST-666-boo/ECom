'use client';

import { useState } from 'react';
import axios from '../../lib/axios';

/**
 * ForgotPasswordForm Component (Next.js adapted)
 */
export default function ForgotPasswordForm({ onBack }) {
  const [email, setEmail] = useState('');
  const [errors, setErrors] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setSuccessMessage('');
    setIsLoading(true);

    try {
      const response = await axios.post('/auth/password/email', { email });

      if (response.data.success) {
        setSuccessMessage('Password reset link sent to your email.');
        setEmail('');
      }
    } catch (error) {
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      } else {
        setErrors({
          general: error.response?.data?.message || 'Failed to send reset link. Please try again.',
        });
      }
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="space-y-4">
      <div className="text-center mb-6">
        <h3 className="text-2xl font-semibold text-[#2c2825] mb-2">Forgot Password?</h3>
        <p className="text-sm text-[#5b5149]">
          Enter your email address and we'll send you a link to reset your password.
        </p>
      </div>

      {/* Success Message */}
      {successMessage && (
        <div className="bg-[#e9f4ec] text-[#1b5e20] px-4 py-3">
          {successMessage}
        </div>
      )}

      {/* General Error */}
      {errors.general && (
        <div className="bg-[#fdeceb] text-[#ba1a1a] px-4 py-3">
          {errors.general}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Email Field */}
        <div>
          <label htmlFor="email" className="block text-sm font-medium text-[#5b5149] mb-1">
            Email
          </label>
          <input
            type="email"
            id="email"
            name="email"
            value={email}
            onChange={(e) => {
              setEmail(e.target.value);
              if (errors.email) {
                setErrors((prev) => ({ ...prev, email: null }));
              }
            }}
            className={`w-full px-1 py-2 bg-transparent border-0 border-b-2 focus:outline-none ${
              errors.email ? 'border-[#ba1a1a]' : 'border-[#cec5bc] focus:border-[#745b21]'
            }`}
            required
          />
          {errors.email && (
            <p className="mt-1 text-sm text-[#ba1a1a]">{errors.email[0]}</p>
          )}
        </div>

        {/* Submit Button */}
        <button
          type="submit"
          disabled={isLoading || successMessage}
          className="w-full bg-[#2c2825] hover:bg-[#1f1b18] text-[#fcf9f6] font-semibold py-2 px-4 transition-colors disabled:bg-[#948980] disabled:cursor-not-allowed"
        >
          {isLoading ? 'Sending...' : 'Send Reset Link'}
        </button>

        {/* Back to Login */}
        <div className="text-center">
          <button
            type="button"
            onClick={onBack}
            className="text-sm text-[#745b21] hover:text-[#5c491a]"
          >
            Back to Login
          </button>
        </div>
      </form>
    </div>
  );
}
