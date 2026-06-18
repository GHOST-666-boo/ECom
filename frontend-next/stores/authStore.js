import { create } from 'zustand';
import { persist } from 'zustand/middleware';

/**
 * Authentication Store
 * 
 * Manages authentication state including user data, token, and auth status.
 * State is persisted to localStorage to survive page reloads.
 */
const useAuthStore = create(
  persist(
    (set) => ({
      // State
      user: null,
      token: null,
      isAuthenticated: false,

      // Actions
      login: (user, token) => set({ user, token, isAuthenticated: true }),
      logout: () => set({ user: null, token: null, isAuthenticated: false }),
      setUser: (user) => set({ user }),
    }),
    {
      name: 'auth-storage', // unique name for localStorage key
    }
  )
);

export default useAuthStore;
