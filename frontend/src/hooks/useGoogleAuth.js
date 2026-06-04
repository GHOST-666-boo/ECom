import axios from '../lib/axios';
import useAuthStore from '../stores/authStore';
import { setApiErrors } from '../lib/apiError';

/**
 * Custom hook for Google OAuth sign-in logic shared between
 * LoginForm and RegisterForm.
 *
 * @param {object} opts
 * @param {Function} opts.setErrors - State setter for errors
 * @param {Function} opts.setIsLoading - State setter for loading
 * @param {Function} opts.onSuccess - Callback on successful auth (e.g. close modal)
 */
export default function useGoogleAuth({ setErrors, setIsLoading, onSuccess }) {
  const login = useAuthStore((state) => state.login);

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
        onSuccess();
      }
    } catch (error) {
      setApiErrors(error, setErrors, 'Google sign-in failed. Please try again.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleGoogleError = () => {
    setErrors({ general: 'Google sign-in failed. Please try again.' });
  };

  return { handleGoogleSuccess, handleGoogleError };
}
