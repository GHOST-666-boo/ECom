/**
 * Reusable loading spinner matching the style used across
 * OrdersPage, AddressManager, Cart, and other components.
 */
export default function LoadingSpinner({ className = '' }) {
  return (
    <div className={`flex justify-center items-center py-12 ${className}`}>
      <div
        className="w-12 h-12 border-2 animate-spin rounded-full"
        style={{ borderColor: '#e5e2e1', borderTopColor: '#4c3e25' }}
      />
    </div>
  );
}
