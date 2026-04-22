import { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import axiosInstance from '../lib/axios';

/**
 * OrderDetailPage Component
 * 
 * Displays detailed information about a specific order.
 * Fetches order from GET /api/v1/orders/{id} endpoint.
 * 
 * Features:
 * - Display order_number, status, total, and order date
 * - Display order items with product image, name, quantity, and price
 * - Display delivery address snapshot
 * - Display payment method and payment_id (if Razorpay)
 * - "Cancel Order" button (only if status is 'pending')
 * - Confirmation modal before cancellation
 * - Submit cancellation to PUT /api/v1/orders/{id}/cancel
 * 
 * Requirements: 7.2, 7.5, 7.7, 7.9, 7.10, 7.11
 */
export default function OrderDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [cancelling, setCancelling] = useState(false);
  const [cancelError, setCancelError] = useState(null);
  const [cancelSuccess, setCancelSuccess] = useState(false);

  useEffect(() => {
    fetchOrder();
  }, [id]);

  const fetchOrder = async () => {
    try {
      setLoading(true);
      const response = await axiosInstance.get(`/orders/${id}`);
      
      if (response.data.success) {
        setOrder(response.data.data.order);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load order details');
    } finally {
      setLoading(false);
    }
  };

  const handleCancelOrder = async () => {
    try {
      setCancelling(true);
      setCancelError(null);
      
      const response = await axiosInstance.put(`/orders/${id}/cancel`);
      
      if (response.data.success) {
        setCancelSuccess(true);
        setShowCancelModal(false);
        // Refresh order details to show updated status
        await fetchOrder();
      }
    } catch (err) {
      setCancelError(err.response?.data?.message || 'Failed to cancel order');
    } finally {
      setCancelling(false);
    }
  };

  const getStatusColor = (status) => {
    const colors = {
      pending: 'bg-yellow-100 text-yellow-800',
      confirmed: 'bg-blue-100 text-blue-800',
      shipped: 'bg-purple-100 text-purple-800',
      delivered: 'bg-green-100 text-green-800',
      cancelled: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  };

  if (loading) {
    return (
      <div className="container mx-auto px-4 py-8">
        <div className="text-center py-12">
          <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500"></div>
          <p className="mt-4 text-gray-600">Loading order details...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="container mx-auto px-4 py-8">
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700 mb-4">
          {error}
        </div>
        <Link
          to="/orders"
          className="inline-flex items-center text-orange-500 hover:text-orange-600"
        >
          <svg
            className="w-5 h-5 mr-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M15 19l-7-7 7-7"
            />
          </svg>
          Back to Orders
        </Link>
      </div>
    );
  }

  if (!order) {
    return null;
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <Link
        to="/orders"
        className="inline-flex items-center text-orange-500 hover:text-orange-600 mb-6"
      >
        <svg
          className="w-5 h-5 mr-2"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M15 19l-7-7 7-7"
          />
        </svg>
        Back to Orders
      </Link>

      {cancelSuccess && (
        <div className="bg-green-50 border border-green-200 rounded-lg p-4 text-green-700 mb-4">
          Order cancelled successfully
        </div>
      )}

      <div className="bg-white rounded-lg shadow-md p-6 mb-6">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
          <div>
            <h1 className="text-2xl font-bold text-gray-800 mb-2">
              Order {order.order_number}
            </h1>
            <p className="text-gray-600">
              Placed on{' '}
              {new Date(order.created_at).toLocaleDateString('en-IN', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
              })}
            </p>
          </div>
          <span
            className={`px-4 py-2 rounded-full text-sm font-semibold ${getStatusColor(
              order.status
            )}`}
          >
            {order.status.charAt(0).toUpperCase() + order.status.slice(1)}
          </span>
        </div>

        {/* Order Items */}
        <div className="border-t pt-6 mb-6">
          <h2 className="text-lg font-semibold text-gray-800 mb-4">
            Order Items
          </h2>
          <div className="space-y-4">
            {order.items?.map((item) => (
              <div key={item.id} className="flex gap-4">
                <div className="w-20 h-20 bg-gray-200 rounded-lg flex-shrink-0 overflow-hidden">
                  {item.product?.images?.[0] ? (
                    <img
                      src={item.product.images[0]}
                      alt={item.product.name}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center text-gray-400">
                      <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                  )}
                </div>
                <div className="flex-grow">
                  <h3 className="font-semibold text-gray-800">{item.product?.name || 'Product'}</h3>
                  <p className="text-gray-600 text-sm">
                    Quantity: {item.quantity}
                  </p>
                  <p className="font-semibold text-orange-500">
                    ₹{parseFloat(item.price).toLocaleString('en-IN')}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Delivery Address */}
        <div className="border-t pt-6 mb-6">
          <h2 className="text-lg font-semibold text-gray-800 mb-4">
            Delivery Address
          </h2>
          <div className="text-gray-600">
            <p className="font-semibold text-gray-800">{order.address_snapshot?.name}</p>
            <p>{order.address_snapshot?.line1}</p>
            {order.address_snapshot?.line2 && <p>{order.address_snapshot.line2}</p>}
            <p>
              {order.address_snapshot?.city}, {order.address_snapshot?.state} {order.address_snapshot?.pincode}
            </p>
          </div>
        </div>

        {/* Payment & Total */}
        <div className="border-t pt-6">
          <div className="flex justify-between items-center mb-2">
            <span className="text-gray-600">Payment Method</span>
            <span className="font-semibold text-gray-800">
              {order.payment_method === 'cod'
                ? 'Cash on Delivery'
                : 'Razorpay'}
            </span>
          </div>
          {order.payment_method === 'razorpay' && order.payment_id && (
            <div className="flex justify-between items-center mb-2">
              <span className="text-gray-600">Payment ID</span>
              <span className="font-mono text-sm text-gray-800">{order.payment_id}</span>
            </div>
          )}
          <div className="flex justify-between items-center text-lg">
            <span className="font-semibold text-gray-800">Total Amount</span>
            <span className="font-bold text-orange-500">
              ₹{parseFloat(order.total).toLocaleString('en-IN')}
            </span>
          </div>
        </div>
      </div>

      {order.status === 'pending' && (
        <button
          onClick={() => setShowCancelModal(true)}
          className="w-full md:w-auto bg-red-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-600 transition-colors"
        >
          Cancel Order
        </button>
      )}

      {/* Cancel Confirmation Modal */}
      {showCancelModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg max-w-md w-full p-6">
            <h3 className="text-xl font-bold text-gray-800 mb-4">
              Cancel Order?
            </h3>
            <p className="text-gray-600 mb-6">
              Are you sure you want to cancel order {order.order_number}? This action cannot be undone.
            </p>
            
            {cancelError && (
              <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-red-700 text-sm mb-4">
                {cancelError}
              </div>
            )}

            <div className="flex gap-3">
              <button
                onClick={() => {
                  setShowCancelModal(false);
                  setCancelError(null);
                }}
                disabled={cancelling}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Keep Order
              </button>
              <button
                onClick={handleCancelOrder}
                disabled={cancelling}
                className="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {cancelling ? 'Cancelling...' : 'Yes, Cancel'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
