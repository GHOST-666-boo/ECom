import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import useCartStore from '../stores/cartStore';
import axiosInstance from '../lib/axios';
import AddressForm from '../components/AddressForm';
import { getImageUrl } from '../lib/imageUrl';

/**
 * CheckoutPage - Metallic Vriddhi (Oat Edition)
 * 7/5 grid: form col (shipping identity + payment method) + sticky order summary sidebar
 */
export default function CheckoutPage() {
  const navigate = useNavigate();
  const { items, subtotal, fetchCart, clearCart } = useCartStore();

  const [addresses, setAddresses] = useState([]);
  const [selectedAddressId, setSelectedAddressId] = useState(null);
  const [paymentMethod, setPaymentMethod] = useState('cod');
  const [showAddressForm, setShowAddressForm] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [loadingAddresses, setLoadingAddresses] = useState(true);

  useEffect(() => {
    fetchCart();
    fetchAddresses();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const fetchAddresses = async () => {
    try {
      setLoadingAddresses(true);
      const response = await axiosInstance.get('/user/addresses');
      if (response.data.success) {
        const addressList = response.data.data?.addresses || [];
        setAddresses(addressList);
        const defaultAddress = addressList.find(addr => addr.is_default);
        if (defaultAddress) setSelectedAddressId(defaultAddress.id);
        else if (addressList.length > 0) setSelectedAddressId(addressList[0].id);
      }
    } catch (err) {
      console.error('Fetch addresses error:', err);
      setError('Failed to load addresses');
    } finally {
      setLoadingAddresses(false);
    }
  };

  const handleAddressSuccess = () => {
    setShowAddressForm(false);
    fetchAddresses();
  };

  const loadRazorpayScript = () =>
    new Promise((resolve) => {
      const script = document.createElement('script');
      script.src = 'https://checkout.razorpay.com/v1/checkout.js';
      script.onload = () => resolve(true);
      script.onerror = () => resolve(false);
      document.body.appendChild(script);
    });

  const handleRazorpayPayment = async (orderId) => {
    try {
      const scriptLoaded = await loadRazorpayScript();
      if (!scriptLoaded) { setError('Failed to load Razorpay.'); setLoading(false); return; }
      const response = await axiosInstance.post('/payments/razorpay/create', { order_id: orderId });
      if (!response.data.success) { 
        setError(response.data.message || 'Failed to initialize payment'); 
        setLoading(false); 
        return; 
      }
      const { razorpay_order_id, amount, currency } = response.data.data;
      
      // Check if Razorpay key is configured
      const razorpayKey = import.meta.env.VITE_RAZORPAY_KEY_ID;
      if (!razorpayKey || razorpayKey === 'your_razorpay_key_id') {
        setError('Payment gateway not configured. Please use Cash on Delivery.');
        setLoading(false);
        return;
      }

      const options = {
        key: razorpayKey,
        amount, currency,
        name: 'Vriddhi',
        description: `Order #${orderId}`,
        order_id: razorpay_order_id,
        handler: () => { clearCart(); navigate(`/orders/${orderId}`); },
        modal: { ondismiss: () => { setError('Payment was cancelled.'); setLoading(false); } },
        theme: { color: '#463f38' },
      };
      const razorpay = new window.Razorpay(options);
      razorpay.open();
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Payment failed.';
      setError(errorMessage);
      setLoading(false);
    }
  };

  const handlePlaceOrder = async () => {
    if (!selectedAddressId) { setError('Please select a delivery address'); return; }
    if (items.length === 0) { setError('Your cart is empty'); return; }
    try {
      setLoading(true); setError(null);
      const response = await axiosInstance.post('/orders', {
        address_id: selectedAddressId,
        payment_method: paymentMethod,
      });
      if (response.data.success) {
        const order = response.data.order;  // Clean: response.data.order
        if (paymentMethod === 'cod') { clearCart(); navigate(`/orders/${order.id}`); }
        else if (paymentMethod === 'razorpay') await handleRazorpayPayment(order.id);
      }
    } catch (err) {
      if (err.response?.data?.errors) {
        setError(Object.values(err.response.data.errors).flat().join(', '));
      } else {
        setError(err.response?.data?.message || 'Failed to place order.');
      }
      setLoading(false);
    }
  };

  /* ── Empty Cart Redirect ── */
  if (!loadingAddresses && items.length === 0) {
    return (
      <div style={{ background: '#fcf9f8', minHeight: '100vh' }} className="flex items-center justify-center">
        <div className="text-center p-16" style={{ background: '#f6f3f2' }}>
          <p className="text-5xl mb-6" style={{ color: '#cfc5bc' }}>∅</p>
          <h2 className="text-2xl italic mb-4" style={{ fontFamily: 'Noto Serif, serif', color: '#4d453f' }}>
            Your cart is empty
          </h2>
          <button
            onClick={() => navigate('/products')}
            className="mt-4 px-8 py-3 text-xs uppercase tracking-widest font-bold"
            style={{ background: '#463f38', color: '#ffffff', fontFamily: 'Manrope, sans-serif' }}
          >
            Browse Collection
          </button>
        </div>
      </div>
    );
  }

  return (
    <div style={{ background: '#fcf9f8', minHeight: '100vh' }}>
      <div className="max-w-7xl mx-auto px-6 md:px-12 pt-12 pb-20">

        {/* Header */}
        <div className="mb-16">
          <h1
            className="text-5xl md:text-6xl italic tracking-tight mb-4"
            style={{ fontFamily: 'Noto Serif, serif', color: '#463f38', letterSpacing: '-0.03em' }}
          >
            Secure Checkout
          </h1>
          <p style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}>
            Review your selection and finalise your intent.
          </p>
        </div>

        {/* ── Error Banner ── */}
        {error && (
          <div
            className="mb-8 px-6 py-4 text-sm"
            style={{
              background: '#ffdad6',
              color: '#93000a',
              fontFamily: 'Manrope, sans-serif',
              borderLeft: '3px solid #ba1a1a',
            }}
          >
            {error}
          </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-16 lg:gap-24 items-start">

          {/* ── Checkout Form ── */}
          <section className="lg:col-span-7 space-y-16">

            {/* 01. Shipping */}
            <article>
              <header className="mb-8 flex items-center justify-between">
                <h2
                  className="text-2xl"
                  style={{ fontFamily: 'Noto Serif, serif', color: '#463f38' }}
                >
                  01. Shipping Identity
                </h2>
                <span
                  className="text-xs uppercase tracking-widest"
                  style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                >
                  Required
                </span>
              </header>

              {loadingAddresses ? (
                <div className="space-y-3">
                  {[1, 2].map(i => (
                    <div key={i} className="h-20 animate-pulse" style={{ background: '#f0eded' }} />
                  ))}
                </div>
              ) : addresses.length === 0 ? (
                <p className="mb-4 text-sm italic" style={{ color: '#4d453f', fontFamily: 'Noto Serif, serif' }}>
                  No saved addresses. Please add one below.
                </p>
              ) : (
                <div className="space-y-4 mb-6">
                  {addresses.map((address) => (
                    <label
                      key={address.id}
                      className="flex items-start justify-between p-6 cursor-pointer transition-colors"
                      style={{
                        background: selectedAddressId === address.id ? '#f6f3f2' : 'transparent',
                        border: `1px solid ${selectedAddressId === address.id ? '#4c3e25' : 'rgba(207,197,188,0.30)'}`,
                      }}
                    >
                      <div className="flex-1">
                        <div className="flex items-center gap-3 mb-1">
                          <p className="font-medium text-sm" style={{ color: '#1b1b1c', fontFamily: 'Manrope, sans-serif' }}>
                            {address.name}
                          </p>
                          {address.is_default && (
                            <span
                              className="text-[10px] uppercase tracking-widest px-2 py-0.5"
                              style={{ background: '#f6dfbc', color: '#53452b', fontFamily: 'Manrope, sans-serif' }}
                            >
                              Default
                            </span>
                          )}
                        </div>
                        <p className="text-sm leading-relaxed" style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}>
                          {address.line1}{address.line2 && `, ${address.line2}`}<br />
                          {address.city}, {address.state} {address.pincode}
                        </p>
                      </div>
                      <input
                        type="radio"
                        name="address"
                        value={address.id}
                        checked={selectedAddressId === address.id}
                        onChange={() => setSelectedAddressId(address.id)}
                        className="mt-1 ml-4"
                        style={{ accentColor: '#463f38' }}
                      />
                    </label>
                  ))}
                </div>
              )}

              <button
                onClick={() => setShowAddressForm(true)}
                className="text-sm font-medium transition-opacity hover:opacity-60"
                style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
              >
                + Add New Address
              </button>
            </article>

            {/* 02. Payment Method */}
            <article>
              <header className="mb-8 flex items-center justify-between">
                <h2
                  className="text-2xl"
                  style={{ fontFamily: 'Noto Serif, serif', color: '#463f38' }}
                >
                  02. Method of Exchange
                </h2>
                <span
                  className="text-xs uppercase tracking-widest"
                  style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                >
                  Select One
                </span>
              </header>

              <div className="space-y-4">
                {[
                  { value: 'cod', label: 'Cash on Delivery', sub: 'Pay when you receive your order' },
                  { value: 'razorpay', label: 'Razorpay', sub: 'Card, UPI, Net Banking, Wallet' },
                ].map(({ value, label, sub }) => (
                  <label
                    key={value}
                    className="group flex items-center justify-between p-6 cursor-pointer transition-colors"
                    style={{
                      background: paymentMethod === value ? '#f6f3f2' : 'transparent',
                      border: `1px solid ${paymentMethod === value ? '#4c3e25' : 'rgba(207,197,188,0.30)'}`,
                    }}
                    onMouseEnter={e => { if (paymentMethod !== value) e.currentTarget.style.background = '#f6f3f2'; }}
                    onMouseLeave={e => { if (paymentMethod !== value) e.currentTarget.style.background = 'transparent'; }}
                  >
                    <div className="flex items-center gap-6">
                      <input
                        type="radio"
                        name="payment"
                        value={value}
                        checked={paymentMethod === value}
                        onChange={e => setPaymentMethod(e.target.value)}
                        style={{ accentColor: '#463f38' }}
                      />
                      <div>
                        <p className="font-semibold text-sm" style={{ color: '#1b1b1c', fontFamily: 'Manrope, sans-serif' }}>{label}</p>
                        <p className="text-sm" style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}>{sub}</p>
                      </div>
                    </div>
                    <span className="text-xl" style={{ color: 'rgba(207,197,188,0.6)' }}>
                      {value === 'cod' ? '💵' : '💳'}
                    </span>
                  </label>
                ))}
              </div>
            </article>
          </section>

          {/* ── Order Summary Sidebar ── */}
          <aside className="lg:col-span-5 sticky top-32">
            <div className="p-8 md:p-12 space-y-10" style={{ background: '#f6f3f2' }}>

              {/* Items */}
              <div>
                <h3
                  className="text-xl italic mb-8"
                  style={{ fontFamily: 'Noto Serif, serif', color: '#463f38' }}
                >
                  Your Selection
                </h3>
                <div className="space-y-6 max-h-60 overflow-y-auto">
                  {items.map(item => (
                    <div key={item.id} className="flex gap-4">
                      <div className="w-20 h-20 flex-shrink-0 overflow-hidden" style={{ background: '#eae7e7' }}>
                        {item.product?.image_urls?.[0] ? (
                          <img
                            src={item.product.image_urls[0]}
                            alt={item.product?.name || 'Product'}
                          />
                        ) : item.product?.images?.[0] ? (
                          <img
                            src={getImageUrl(item.product?.images[0])}
                            alt={item.product?.name || 'Product'}
                            className="w-full h-full object-cover"
                            style={{ filter: 'grayscale(0.3)' }}
                          />
                        ) : (
                          <div className="w-full h-full flex items-center justify-center">
                            <span style={{ color: '#cfc5bc' }}>✦</span>
                          </div>
                        )}
                      </div>
                      <div className="flex flex-col justify-between py-1 flex-1">
                        <div>
                          <p className="font-medium text-sm tracking-tight" style={{ color: '#1b1b1c', fontFamily: 'Manrope, sans-serif' }}>
                            {item.product?.name || 'Unavailable Product'}
                          </p>
                          <p className="text-[10px] uppercase tracking-widest mt-1" style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}>
                            {item.product?.category?.name || 'Handcrafted Object'}
                          </p>
                        </div>
                        <div className="flex justify-between items-baseline">
                          <p className="text-xs" style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}>Qty: {item.quantity}</p>
                          <p className="font-semibold text-sm" style={{ color: '#463f38', fontFamily: 'Manrope, sans-serif' }}>
                            ₹{(item.quantity * parseFloat(item.product?.price || 0)).toLocaleString('en-IN')}
                          </p>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Totals */}
              <div className="space-y-4">
                {[
                  { label: 'Subtotal', value: `₹${subtotal.toLocaleString('en-IN')}` },
                  { label: 'Shipping', value: 'Complimentary' },
                ].map(({ label, value }) => (
                  <div key={label} className="flex justify-between text-sm">
                    <span className="uppercase" style={{ color: '#4d453f', fontFamily: 'Manrope, sans-serif' }}>{label}</span>
                    <span className="font-semibold" style={{ color: '#1b1b1c', fontFamily: 'Manrope, sans-serif' }}>{value}</span>
                  </div>
                ))}
                <div
                  className="pt-8 flex justify-between items-baseline"
                  style={{ borderTop: '1px solid rgba(207,197,188,0.20)' }}
                >
                  <span className="text-lg italic" style={{ fontFamily: 'Noto Serif, serif', color: '#463f38' }}>Total Intent</span>
                  <span
                    className="text-3xl font-bold tracking-tighter"
                    style={{ fontFamily: 'Noto Serif, serif', color: '#463f38' }}
                  >
                    ₹{subtotal.toLocaleString('en-IN')}
                  </span>
                </div>
              </div>

              {/* Place Order CTA */}
              <button
                onClick={handlePlaceOrder}
                disabled={loading || !selectedAddressId || items.length === 0}
                className="w-full py-5 text-sm uppercase tracking-[0.2em] font-bold flex items-center justify-center gap-3 transition-opacity hover:opacity-90 disabled:opacity-45 disabled:cursor-not-allowed"
                style={{
                  background: 'linear-gradient(135deg, #463f38 0%, #5e564f 100%)',
                  color: '#ffffff',
                  fontFamily: 'Manrope, sans-serif',
                }}
              >
                {loading ? 'Processing...' : `Finalise Purchase 🔒`}
              </button>

              <p
                className="text-[10px] text-center uppercase tracking-widest leading-relaxed"
                style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
              >
                Your data is encrypted and secure. By placing your order, you agree to our terms.
              </p>
            </div>
          </aside>
        </div>
      </div>

      {/* ── Address Form Modal ── */}
      {showAddressForm && (
        <div
          className="fixed inset-0 flex items-center justify-center z-50 p-4"
          style={{ background: 'rgba(27,27,28,0.55)', backdropFilter: 'blur(10px)' }}
        >
          <div
            className="max-w-md w-full max-h-[90vh] overflow-y-auto p-8"
            style={{
              background: '#fcf9f8',
              boxShadow: '0 24px 64px rgba(27,27,28,0.20)',
            }}
          >
            <h2
              className="text-2xl italic mb-6"
              style={{ fontFamily: 'Noto Serif, serif', color: '#463f38' }}
            >
              Add New Address
            </h2>
            <AddressForm onSuccess={handleAddressSuccess} onCancel={() => setShowAddressForm(false)} />
          </div>
        </div>
      )}
    </div>
  );
}
