import { useState } from 'react';
import { GoogleLogin } from '@react-oauth/google';
import axios from '../lib/axios';
import useAuthStore from '../stores/authStore';

/**
 * LoginForm Component
 * 
 * Login form with email and password fields.
 * Includes Google OAuth Sign-In button.
 * Displays validation errors inline from API response.
 * Requirements: 1.1, 1.8, 1.9, 1.10, 1.11, 10.2
 */
export default function LoginForm({ onClose, onForgotPassword }) {
  const [formData, setFormData] = useState({
    email: '',
    password: '',
  });
  const [errors, setErrors] = useState({});
  const [isLoading, setIsLoading] = useState(false);

  const login = useAuthStore((state) => state.login);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    // Clear error for this field when user starts typing
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: null }));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setIsLoading(true);

    try {
      const response = await axios.post('/auth/login', formData);

      if (response.data.success) {
        const { user, token } = response.data.data;
        login(user, token);
        onClose();
      }
    } catch (error) {
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      } else {
        setErrors({
          general: error.response?.data?.message || 'Login failed. Please try again.',
        });
      }
    } finally {
      setIsLoading(false);
    }
  };

  const handleGoogleSuccess = async (credentialResponse) => {
    setErrors({});
    setIsLoading(true);

    try {
      const response = await axios.post('/auth/google', {
        id_token: credentialResponse.credential,
      });

      if (response.data.success) {
        const { user, token } = response.data.data;
        login(user, token);
        onClose();
      }
    } catch (error) {
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
      } else {
        setErrors({
          general: error.response?.data?.message || 'Google sign-in failed. Please try again.',
        });
      }
    } finally {
      setIsLoading(false);
    }
  };

  const handleGoogleError = () => {
    setErrors({
      general: 'Google sign-in failed. Please try again.',
    });
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-5">
      {/* General Error */}
      {errors.general && (
        <div className="bg-[#fdeceb] text-[#ba1a1a] px-4 py-3">
          {errors.general}
        </div>
      )}

      {/* Email Field */}
      <div>
        <label htmlFor="email" className="block text-sm font-medium text-[#5b5149] mb-1">
          Email
        </label>
        <input
          type="email"
          id="email"
          name="email"
          value={formData.email}
          onChange={handleChange}
          className={`w-full px-1 py-2 bg-transparent border-0 border-b-2 focus:outline-none ${
            errors.email ? 'border-[#ba1a1a]' : 'border-[#cec5bc] focus:border-[#745b21]'
          }`}
          required
        />
        {errors.email && (
          <p className="mt-1 text-sm text-[#ba1a1a]">{errors.email[0]}</p>
        )}
      </div>

      {/* Password Field */}
      <div>
        <label htmlFor="password" className="block text-sm font-medium text-[#5b5149] mb-1">
          Password
        </label>
        <input
          type="password"
          id="password"
          name="password"
          value={formData.password}
          onChange={handleChange}
          className={`w-full px-1 py-2 bg-transparent border-0 border-b-2 focus:outline-none ${
            errors.password ? 'border-[#ba1a1a]' : 'border-[#cec5bc] focus:border-[#745b21]'
          }`}
          required
        />
        {errors.password && (
          <p className="mt-1 text-sm text-[#ba1a1a]">{errors.password[0]}</p>
        )}
      </div>

      {/* Submit Button */}
      <button
        type="submit"
        disabled={isLoading}
        className="w-full bg-[#2c2825] hover:bg-[#1f1b18] text-[#fcf9f6] font-semibold py-2 px-4 transition-colors disabled:bg-[#948980] disabled:cursor-not-allowed"
      >
        {isLoading ? 'Logging in...' : 'Login'}
      </button>

      {/* Divider */}
      <div className="relative my-6">
        <div className="absolute inset-0 flex items-center">
          <div className="w-full h-px bg-[#cec5bc]/30"></div>
        </div>
        <div className="relative flex justify-center text-sm">
          <span className="px-2 bg-[#fcf9f6] text-[#7a6d63]">Or continue with</span>
        </div>
      </div>

      {/* Google Sign-In Button */}
      <div className="flex justify-center">
        <GoogleLogin
          onSuccess={handleGoogleSuccess}
          onError={handleGoogleError}
          useOneTap
          theme="outline"
          size="large"
          text="signin_with"
          shape="rectangular"
        />
      </div>

      {/* Forgot Password Link - Will be implemented in task 52.3 */}
      <div className="text-center mt-4">
        <button
          type="button"
          className="text-sm text-[#745b21] hover:text-[#5c491a]"
          onClick={onForgotPassword}
        >
          Forgot Password?
        </button>
      </div>
    </form>
  );
}
