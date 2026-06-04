import { useEffect } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import axios from '../lib/axios';
import useFormState from '../hooks/useFormState';
import { setApiErrors } from '../lib/apiError';
import FormField from './ui/FormField';
import FormAlert from './ui/FormAlert';
import SubmitButton from './ui/SubmitButton';

/**
 * ResetPasswordForm Component
 * 
 * Form to reset password with token from email link.
 * Includes token, password, and password confirmation fields.
 * Displays success/error messages from API.
 * Requirements: 1.17, 1.18
 */
export default function ResetPasswordForm() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  
  const {
    formData, setFormData, errors, setErrors, isLoading, setIsLoading,
    successMessage, setSuccessMessage, handleChange,
  } = useFormState({
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
  });

  useEffect(() => {
    const token = searchParams.get('token') || '';
    const email = searchParams.get('email') || '';
    
    setFormData((prev) => ({ ...prev, token, email }));
  }, [searchParams, setFormData]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setSuccessMessage('');
    setIsLoading(true);

    try {
      const response = await axios.post('/auth/password/reset', formData);

      if (response.data.success) {
        setSuccessMessage('Password reset successfully. Redirecting to login...');
        setTimeout(() => {
          navigate('/?loginRequired=true');
        }, 2000);
      }
    } catch (error) {
      setApiErrors(error, setErrors, 'Failed to reset password. Please try again.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
        <div className="text-center mb-6">
          <h2 className="text-2xl font-bold text-gray-800 mb-2">Reset Password</h2>
          <p className="text-sm text-gray-600">
            Enter your new password below.
          </p>
        </div>

        <FormAlert type="success" message={successMessage} />
        <FormAlert type="error" message={errors.general} />

        <form onSubmit={handleSubmit} className="space-y-4">
          {/* Email Field (read-only) */}
          <FormField
            label="Email"
            name="email"
            type="email"
            value={formData.email}
            readOnly
          />

          <FormField
            label="New Password"
            name="password"
            type="password"
            value={formData.password}
            onChange={handleChange}
            error={errors.password}
            required
          />

          <FormField
            label="Confirm New Password"
            name="password_confirmation"
            type="password"
            value={formData.password_confirmation}
            onChange={handleChange}
            error={errors.password_confirmation}
            required
          />

          {/* Token Error */}
          {errors.token && (
            <FormAlert type="error" message={Array.isArray(errors.token) ? errors.token[0] : errors.token} />
          )}

          <SubmitButton
            isLoading={isLoading}
            disabled={!!successMessage}
            loadingText="Resetting..."
          >
            Reset Password
          </SubmitButton>
        </form>
      </div>
    </div>
  );
}
