import { GoogleLogin } from '@react-oauth/google';
import axios from '../lib/axios';
import useAuthStore from '../stores/authStore';
import useFormState from '../hooks/useFormState';
import useGoogleAuth from '../hooks/useGoogleAuth';
import { setApiErrors } from '../lib/apiError';
import FormField from './ui/FormField';
import FormAlert from './ui/FormAlert';
import SubmitButton from './ui/SubmitButton';
import GoogleDivider from './ui/GoogleDivider';

/**
 * RegisterForm Component
 * 
 * Registration form with name, email, password, and password confirmation fields.
 * Includes Google OAuth Sign-In button.
 * Displays validation errors inline from API response.
 * Shows success message: "Check your email to verify your account"
 * Requirements: 1.1, 1.3, 1.8, 1.9, 1.10, 1.11, 10.2
 */
export default function RegisterForm({ onClose, onSwitchToLogin }) {
  const {
    formData, errors, setErrors, isLoading, setIsLoading,
    successMessage, setSuccessMessage, handleChange, resetForm,
  } = useFormState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  });

  useAuthStore((state) => state.login);

  const { handleGoogleSuccess, handleGoogleError } = useGoogleAuth({
    setErrors,
    setIsLoading,
    onSuccess: onClose,
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setSuccessMessage('');
    setIsLoading(true);

    try {
      const response = await axios.post('/auth/register', formData);

      if (response.data.success) {
        setSuccessMessage('Check your email to verify your account');
        resetForm();
        setTimeout(() => {
          onSwitchToLogin();
        }, 3000);
      }
    } catch (error) {
      setApiErrors(error, setErrors, 'Registration failed. Please try again.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-5">
      <FormAlert type="success" message={successMessage} />
      <FormAlert type="error" message={errors.general} />

      <FormField
        label="Name"
        name="name"
        value={formData.name}
        onChange={handleChange}
        error={errors.name}
        required
      />

      <FormField
        label="Email"
        name="email"
        type="email"
        value={formData.email}
        onChange={handleChange}
        error={errors.email}
        required
      />

      <FormField
        label="Password"
        name="password"
        type="password"
        value={formData.password}
        onChange={handleChange}
        error={errors.password}
        required
      />

      <FormField
        label="Confirm Password"
        name="password_confirmation"
        type="password"
        value={formData.password_confirmation}
        onChange={handleChange}
        error={errors.password_confirmation}
        required
      />

      <SubmitButton
        isLoading={isLoading}
        disabled={!!successMessage}
        loadingText="Registering..."
      >
        Register
      </SubmitButton>

      <GoogleDivider />

      {/* Google Sign-In Button */}
      <div className="flex justify-center">
        <GoogleLogin
          onSuccess={handleGoogleSuccess}
          onError={handleGoogleError}
          useOneTap
          theme="outline"
          size="large"
          text="signup_with"
          shape="rectangular"
        />
      </div>
    </form>
  );
}
