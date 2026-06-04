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
 * LoginForm Component
 * 
 * Login form with email and password fields.
 * Includes Google OAuth Sign-In button.
 * Displays validation errors inline from API response.
 * Requirements: 1.1, 1.8, 1.9, 1.10, 1.11, 10.2
 */
export default function LoginForm({ onClose, onForgotPassword }) {
  const {
    formData, errors, setErrors, isLoading, setIsLoading, handleChange,
  } = useFormState({ email: '', password: '' });

  const login = useAuthStore((state) => state.login);

  const { handleGoogleSuccess, handleGoogleError } = useGoogleAuth({
    setErrors,
    setIsLoading,
    onSuccess: onClose,
  });

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
      setApiErrors(error, setErrors, 'Login failed. Please try again.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-5">
      <FormAlert type="error" message={errors.general} />

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

      <SubmitButton isLoading={isLoading} loadingText="Logging in...">
        Login
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
          text="signin_with"
          shape="rectangular"
        />
      </div>

      {/* Forgot Password Link */}
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
