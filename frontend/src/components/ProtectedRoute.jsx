import { Navigate } from 'react-router-dom';
import useAuthStore from '../stores/authStore';

export default function ProtectedRoute({ children }) {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated);

  if (!isAuthenticated) {
    // Redirect to home page with a state indicating login is required
    // The actual login modal/page will be implemented in a later task
    return <Navigate to="/" replace state={{ loginRequired: true }} />;
  }

  return children;
}
