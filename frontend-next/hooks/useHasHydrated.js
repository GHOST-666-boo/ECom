import { useState, useEffect } from 'react';

/**
 * useHasHydrated Hook
 * 
 * Returns true if the component has mounted on the client,
 * allowing safe access to localStorage/persisted states.
 */
export function useHasHydrated() {
  const [hasHydrated, setHasHydrated] = useState(false);

  useEffect(() => {
    setHasHydrated(true);
  }, []);

  return hasHydrated;
}
