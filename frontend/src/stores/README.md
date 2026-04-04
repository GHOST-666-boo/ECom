# Authentication State Management

## Overview

The authentication state is managed using Zustand, a lightweight state management library. The token is stored in memory (not localStorage) to prevent XSS attacks as per security requirement 10.8.

## Auth Store (`authStore.js`)

### State

- `user`: User object containing user details (null when not authenticated)
- `token`: Sanctum bearer token (null when not authenticated)
- `isAuthenticated`: Boolean flag indicating authentication status

### Actions

- `login(user, token)`: Sets user, token, and isAuthenticated to true
- `logout()`: Clears user, token, and sets isAuthenticated to false
- `setUser(user)`: Updates user data without changing token

## Usage Example

```javascript
import useAuthStore from './stores/authStore';

// In a component
function LoginComponent() {
  const { login, logout, isAuthenticated, user } = useAuthStore();

  const handleLogin = async (email, password) => {
    const response = await axios.post('/auth/login', { email, password });
    const { user, token } = response.data.data;
    login(user, token);
  };

  const handleLogout = async () => {
    await axios.post('/auth/logout');
    logout();
  };

  return (
    <div>
      {isAuthenticated ? (
        <div>
          <p>Welcome, {user.name}</p>
          <button onClick={handleLogout}>Logout</button>
        </div>
      ) : (
        <button onClick={() => handleLogin('user@example.com', 'password')}>
          Login
        </button>
      )}
    </div>
  );
}
```

## Axios Instance (`lib/axios.js`)

The Axios instance is configured with:

1. **Request Interceptor**: Automatically adds `Authorization: Bearer {token}` header to all requests
2. **Response Interceptor**: Handles HTTP 401 responses by:
   - Clearing authentication state (logout)
   - Redirecting to home page with `loginRequired=true` query parameter

## Security Considerations

- **Token Storage**: Token is stored in memory (Zustand state) and NOT in localStorage to prevent XSS attacks
- **Token Expiry**: Tokens expire after 7 days (configured on backend)
- **Automatic Logout**: HTTP 401 responses automatically clear auth state and redirect to login

## Requirements Satisfied

- **1.7**: Session token valid for 7 days
- **1.13**: Logout revokes current token
- **1.15**: Expired tokens return HTTP 401
- **10.8**: Token stored in memory (not localStorage) to prevent XSS
