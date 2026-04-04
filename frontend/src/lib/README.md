# Axios Configuration

## Overview

This directory contains the configured Axios instance with interceptors for authentication and error handling.

## Usage

Import the configured Axios instance instead of using the default axios:

```javascript
import axios from './lib/axios';

// Make API calls
const response = await axios.get('/products');
const data = response.data;
```

## Features

### Automatic Authorization Header

The request interceptor automatically adds the `Authorization: Bearer {token}` header to all requests if a token is present in the auth store.

```javascript
// No need to manually add Authorization header
const response = await axios.get('/cart'); // Header added automatically
```

### Automatic 401 Handling

The response interceptor automatically handles HTTP 401 (Unauthorized) responses by:
1. Clearing the authentication state (logout)
2. Redirecting to the home page with `loginRequired=true` parameter

```javascript
// If token expires, user is automatically logged out and redirected
const response = await axios.get('/orders'); // If 401, auto logout + redirect
```

## Configuration

The base URL is configured from the environment variable:
- `VITE_API_BASE_URL`: Base URL for the API (e.g., `http://localhost:8000/api/v1`)

## Example: Login Flow

```javascript
import axios from './lib/axios';
import useAuthStore from './stores/authStore';

async function login(email, password) {
  try {
    const response = await axios.post('/auth/login', { email, password });
    const { user, token } = response.data.data;
    
    // Store auth state
    useAuthStore.getState().login(user, token);
    
    // Subsequent requests will automatically include the token
    const cartResponse = await axios.get('/cart');
    console.log(cartResponse.data);
  } catch (error) {
    console.error('Login failed:', error);
  }
}
```

## Example: Logout Flow

```javascript
import axios from './lib/axios';
import useAuthStore from './stores/authStore';

async function logout() {
  try {
    // Call logout endpoint (token automatically included in header)
    await axios.post('/auth/logout');
    
    // Clear local auth state
    useAuthStore.getState().logout();
    
    // Redirect to home
    window.location.href = '/';
  } catch (error) {
    console.error('Logout failed:', error);
    // Clear local state anyway
    useAuthStore.getState().logout();
  }
}
```

## Requirements Satisfied

- **1.7**: Authorization header with Bearer token
- **1.15**: HTTP 401 handling (clear token, redirect)
- **10.8**: Token stored in memory (not localStorage)
