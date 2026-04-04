import { useState } from 'react';
import LoginForm from './LoginForm';
import RegisterForm from './RegisterForm';
import ForgotPasswordForm from './ForgotPasswordForm';

/**
 * AuthModal Component
 * 
 * Modal with tabs for Login and Register forms.
 * Also includes Forgot Password form.
 * Requirements: 1.1, 1.3, 1.16, 10.2
 */
export default function AuthModal({ isOpen, onClose }) {
  const [activeTab, setActiveTab] = useState('login');
  const [showForgotPassword, setShowForgotPassword] = useState(false);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-[rgba(44,40,37,0.45)] backdrop-blur-[8px] flex items-center justify-center z-50 p-4">
      <div className="bg-[#fcf9f6] shadow-[0_20px_40px_rgba(44,40,37,0.12)] max-w-md w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 bg-[#f6f3f0]">
          <h2 className="text-3xl font-semibold text-[#2c2825]">
            {showForgotPassword ? 'Forgot Password' : activeTab === 'login' ? 'Login' : 'Register'}
          </h2>
          <button
            onClick={onClose}
            className="text-[#7a6d63] hover:text-[#2c2825] transition-colors"
          >
            <svg
              className="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        {/* Tabs - Hide when showing forgot password */}
        {!showForgotPassword && (
          <div className="flex bg-[#f6f3f0]">
            <button
              onClick={() => setActiveTab('login')}
              className={`flex-1 py-3 text-center font-semibold transition-colors ${
                activeTab === 'login'
                  ? 'text-[#2c2825] bg-[#fcf9f6]'
                  : 'text-[#7a6d63] hover:text-[#2c2825]'
              }`}
            >
              Login
            </button>
            <button
              onClick={() => setActiveTab('register')}
              className={`flex-1 py-3 text-center font-semibold transition-colors ${
                activeTab === 'register'
                  ? 'text-[#2c2825] bg-[#fcf9f6]'
                  : 'text-[#7a6d63] hover:text-[#2c2825]'
              }`}
            >
              Register
            </button>
          </div>
        )}

        {/* Form Content */}
        <div className="p-6">
          {showForgotPassword ? (
            <ForgotPasswordForm onBack={() => setShowForgotPassword(false)} />
          ) : activeTab === 'login' ? (
            <LoginForm onClose={onClose} onForgotPassword={() => setShowForgotPassword(true)} />
          ) : (
            <RegisterForm onClose={onClose} onSwitchToLogin={() => setActiveTab('login')} />
          )}
        </div>
      </div>
    </div>
  );
}
