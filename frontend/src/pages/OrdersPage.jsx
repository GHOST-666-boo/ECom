import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import axiosInstance from '../lib/axios';

/**
 * OrdersPage Component
 * 
 * Displays user's order history with cursor-based pagination.
 * Fetches orders from GET /api/v1/orders endpoint.
 * 
 * Features:
 * - Order list with order_number, status, total, and date
 * - Status badges with colors (pending: yellow, confirmed: blue, shipped: purple, delivered: green, cancelled: red)
 * - Link to order detail page
 * - Cursor-based pagination with "Load More" button
 * 
 * Requirements: 7.1, 7.2, 7.3
 */
export default function OrdersPage() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [nextCursor, setNextCursor] = useState(null);
  const [loadingMore, setLoadingMore] = useState(false);

  useEffect(() => {
    fetchOrders();
  }, []);

  const fetchOrders = async (cursor = null) => {
    try {
      if (cursor) {
        setLoadingMore(true);
      } else {
        setLoading(true);
      }
      
      const url = cursor ? `/orders?cursor=${cursor}` : '/orders';
      const response = await axiosInstance.get(url);
      
      if (response.data.success) {
        const newOrders = response.data.data;
        setOrders(prev => cursor ? [...prev, ...newOrders] : newOrders);
        setNextCursor(response.data.meta?.next_cursor || null);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load orders');
    } finally {
      setLoading(false);
      setLoadingMore(false);
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
          <p className="mt-4 text-gray-600">Loading orders...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="container mx-auto px-4 py-8">
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
          {error}
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold text-gray-800 mb-6">My Orders</h1>

      {orders.length > 0 ? (
        <>
          <div className="space-y-4">
            {orders.map((order) => (
              <div
                key={order.id}
                className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow"
              >
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                  <div className="flex-grow">
                    <div className="flex items-center gap-3 mb-2">
                      <h3 className="font-semibold text-gray-800">
                        {order.order_number}
                      </h3>
                      <span
                        className={`px-3 py-1 rounded-full text-xs font-semibold ${getStatusColor(
                          order.status
                        )}`}
                      >
                        {order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                      </span>
                    </div>
                    <p className="text-gray-600 text-sm">
                      Placed on {new Date(order.created_at).toLocaleDateString('en-IN', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                      })}
                    </p>
                    <p className="text-gray-600 text-sm">
                      Total: ₹{parseFloat(order.total).toLocaleString('en-IN')}
                    </p>
                  </div>
                  <div className="flex gap-2">
                    <Link
                      to={`/orders/${order.id}`}
                      className="px-4 py-2 border border-orange-500 text-orange-500 rounded-lg font-semibold hover:bg-orange-50 transition-colors"
                    >
                      View Details
                    </Link>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {nextCursor && (
            <div className="mt-6 text-center">
              <button
                onClick={() => fetchOrders(nextCursor)}
                disabled={loadingMore}
                className="px-6 py-3 bg-orange-500 text-white rounded-lg font-semibold hover:bg-orange-600 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
              >
                {loadingMore ? 'Loading...' : 'Load More'}
              </button>
            </div>
          )}
        </>
      ) : (
        <div className="text-center py-12">
          <p className="text-gray-600 mb-4">You haven't placed any orders yet</p>
          <Link
            to="/products"
            className="inline-block bg-orange-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-orange-600 transition-colors"
          >
            Start Shopping
          </Link>
        </div>
      )}
    </div>
  );
}
