'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import ProtectedRoute from '../../../components/auth/ProtectedRoute';
import axiosInstance from '../../../lib/axios';
import { getImageUrl } from '../../../lib/imageUrl';

/**
 * OrdersPage Component (Next.js adapted)
 * 
 * Renders the user's order history with status indicators and details buttons.
 */
function OrdersPageContent() {
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
        const newOrders = response.data.orders;
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
          <div className="inline-block animate-spin rounded-full h-12 w-12 border-2 border-[#4c3e25] border-t-transparent"></div>
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
                <div className="flex flex-col md:flex-row gap-6">
                  {/* Product Images */}
                  <div className="flex gap-2 overflow-x-auto md:w-48 flex-shrink-0">
                    {order.order_items?.slice(0, 3).map((item) => (
                      <div
                        key={item.id}
                        className="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden"
                        style={{ background: '#eae7e7' }}
                      >
                        {item.product?.image_urls?.[0] ? (
                          <img
                            src={item.product.image_urls[0]}
                            alt={item.product.name || 'Product'}
                            className="w-full h-full object-cover"
                            style={{ filter: 'grayscale(0.2)' }}
                          />
                        ) : item.product?.images?.[0] ? (
                          <img
                            src={getImageUrl(item.product.images[0])}
                            alt={item.product.name || 'Product'}
                            className="w-full h-full object-cover"
                            style={{ filter: 'grayscale(0.2)' }}
                          />
                        ) : (
                          <div className="w-full h-full flex items-center justify-center text-gray-400">
                            <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                          </div>
                        )}
                      </div>
                    ))}
                    {order.order_items?.length > 3 && (
                      <div
                        className="w-20 h-20 flex-shrink-0 rounded-lg flex items-center justify-center text-sm font-semibold"
                        style={{ background: '#f6f3f2', color: '#4c3e25' }}
                      >
                        +{order.order_items.length - 3}
                      </div>
                    )}
                  </div>

                  {/* Order Info */}
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
                    <p className="text-gray-600 text-sm mb-1">
                      Placed on {new Date(order.created_at).toLocaleDateString('en-IN', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                      })}
                    </p>
                    <p className="text-gray-600 text-sm mb-1">
                      {order.order_items?.length || 0} {order.order_items?.length === 1 ? 'item' : 'items'}
                    </p>
                    <p className="font-semibold text-lg" style={{ color: '#4c3e25' }}>
                      ₹{parseFloat(order.total).toLocaleString('en-IN')}
                    </p>
                  </div>

                  {/* Action Button */}
                  <div className="flex items-center">
                    <Link
                      href={`/orders/${order.id}`}
                      className="px-6 py-2 rounded-lg font-semibold transition-colors text-sm border"
                      style={{
                        borderColor: '#4c3e25',
                        color: '#4c3e25',
                      }}
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
            href="/products"
            className="inline-block bg-orange-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-orange-600 transition-colors"
          >
            Start Shopping
          </Link>
        </div>
      )}
    </div>
  );
}

export default function OrdersPage() {
  return (
    <ProtectedRoute>
      <OrdersPageContent />
    </ProtectedRoute>
  );
}
