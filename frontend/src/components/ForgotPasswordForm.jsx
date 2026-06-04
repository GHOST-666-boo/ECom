import { useState } from 'react';
import axios from '../lib/axios';
import { setApiErrors } from '../lib/apiError';
import FormField from './ui/FormField';
import FormAlert from './ui/FormAlert';
import SubmitButton from './ui/SubmitButton';

/**
 * ForgotPasswordForm Component
 * 
 * Form to request password reset link via email.
 * Displays success/error messages from API.
 * Requirements: 1.16, 1.17
 */
export default function ForgotPasswordForm({ onBack }) {
  const [email, setEmail] = useState('');
  const [errors, setErrors] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  const handleEmailChange = (e) => {
    setEmail(e.target.value);
    if (errors.email) {
      setErrors((prev) => ({ ...prev, email: null }));
    }
  };

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
      setApiErrors(error, setErrors, 'Failed to send reset link. Please try again.');
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

      <FormAlert type="success" message={successMessage} />
      <FormAlert type="error" message={errors.general} />

      <form onSubmit={handleSubmit} className="space-y-4">
        <FormField
          label="Email"
          name="email"
          type="email"
          value={email}
          onChange={handleEmailChange}
          error={errors.email}
          required
        />

        <SubmitButton
          isLoading={isLoading}
          disabled={!!successMessage}
          loadingText="Sending..."
        >
          Send Reset Link
        </SubmitButton>

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
