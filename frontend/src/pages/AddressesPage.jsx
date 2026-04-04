import AddressManager from '../components/AddressManager';

/**
 * AddressesPage
 * 
 * Page for managing user addresses.
 * Uses AddressManager component for full functionality.
 * 
 * Requirements: 13.1-13.8
 */
export default function AddressesPage() {
  return (
    <div className="container mx-auto px-4 py-8">
      <AddressManager />
    </div>
  );
}
