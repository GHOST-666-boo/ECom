'use client';

import { useState, useEffect } from 'react';
import { useParams, useRouter } from 'next/navigation';
import Link from 'next/link';
import ProtectedRoute from '@/components/auth/ProtectedRoute';
import axiosInstance from '@/lib/axios';
import { getImageUrl } from '@/lib/imageUrl';

// ─── Courier tracking links ───────────────────────────────────────────────────
const COURIER_LINKS = {
  delhivery:  (awb) => `https://www.delhivery.com/track/package/${awb}`,
  bluedart:   (awb) => `https://www.bluedart.com/tracking?trackFor=0&trackNo=${awb}`,
  dtdc:       (awb) => `https://www.dtdc.in/tracking.asp?awbno=${awb}`,
  ecom:       (awb) => `https://ecomexpress.in/tracking/?awb_field=${awb}`,
  shiprocket: (awb) => `https://shiprocket.co/tracking/${awb}`,
};

const getTrackingUrl = (courierName, awb) => {
  if (!courierName || !awb) return null;
  const key = courierName.toLowerCase().replace(/\s+/g, '');
  const builder = COURIER_LINKS[key];
  return builder ? builder(awb) : `https://www.google.com/search?q=${encodeURIComponent(courierName + ' tracking ' + awb)}`;
};

// ─── Status steps config ──────────────────────────────────────────────────────
const STEPS = [
  { key: 'pending',    label: 'Order Placed',  icon: '🛒', desc: 'Your order has been placed successfully' },
  { key: 'confirmed',  label: 'Confirmed',      icon: '✅', desc: 'Order confirmed by our team' },
  { key: 'processing', label: 'Processing',     icon: '📦', desc: 'Item is being packed & prepared' },
  { key: 'shipped',    label: 'Shipped',        icon: '🚚', desc: 'Out for delivery to you' },
  { key: 'delivered',  label: 'Delivered',      icon: '🎉', desc: 'Order successfully delivered' },
];
const STATUS_ORDER = STEPS.map((s) => s.key);

// ─── Styles matching Vriddhi metallic artisan / oat theme ──────────────────────
const S = {
  page: {
    minHeight: '100vh',
    background: 'var(--color-surface, #fcf9f8)',
    padding: '40px 16px 64px',
    fontFamily: '"Manrope", sans-serif',
  },
  inner: { maxWidth: '780px', margin: '0 auto' },
  backLink: {
    display: 'inline-flex', alignItems: 'center', gap: '8px',
    color: 'var(--color-tertiary, #4c3e25)', fontWeight: 600, fontSize: '0.9rem',
    textDecoration: 'none', marginBottom: '24px',
    transition: 'opacity .2s',
  },
  card: {
    background: '#fff',
    border: '1px solid var(--color-outline-variant, #cfc5bc)',
    padding: '32px', marginBottom: '20px',
    borderRadius: '4px',
    boxShadow: '0 2px 8px rgba(70,63,56,0.03)',
  },
  sectionTitle: {
    fontFamily: '"Noto Serif", serif',
    fontSize: '0.9rem', fontWeight: 700, color: 'var(--color-primary, #463f38)',
    textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: '20px',
    borderBottom: '1px solid var(--color-outline-variant, #cfc5bc)',
    paddingBottom: '10px',
  },

  // Timeline card
  timelineCard: {
    background: 'var(--color-surface-low, #f6f3f2)',
    border: '1px solid var(--color-outline-variant, #cfc5bc)',
    padding: '32px 28px', marginBottom: '20px',
    borderRadius: '4px',
  },
  timelineTitle: {
    fontFamily: '"Noto Serif", serif',
    color: 'var(--color-primary, #463f38)', fontSize: '0.9rem', fontWeight: 700,
    textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: '20px',
    borderBottom: '1px solid var(--color-outline-variant, #cfc5bc)',
    paddingBottom: '10px',
  },

  // Tracking card
  trackingCard: {
    background: 'var(--color-surface-low, #f6f3f2)',
    border: '1px solid var(--color-outline-variant, #cfc5bc)',
    padding: '24px 28px', marginBottom: '20px',
    borderRadius: '4px',
  },
};

// ─── Sub-components ───────────────────────────────────────────────────────────

function OrderTimeline({ status }) {
  const isCancelled = status === 'cancelled';
  const currentIndex = STATUS_ORDER.indexOf(status);

  if (isCancelled) {
    return (
      <div style={S.timelineCard}>
        <div style={S.timelineTitle}>📦 Order Status</div>
        <div style={{
          display: 'flex', alignItems: 'center', gap: '16px',
          background: 'rgba(239,68,68,0.06)',
          border: '1px solid rgba(239,68,68,0.25)',
          borderRadius: '4px', padding: '18px 20px',
        }}>
          <span style={{ fontSize: '2rem' }}>❌</span>
          <div>
            <div style={{ color: '#ef4444', fontWeight: 700, fontSize: '1rem', fontFamily: '"Noto Serif", serif' }}>Order Cancelled</div>
            <div style={{ color: 'var(--color-on-surface-variant, #4d453f)', fontSize: '0.83rem', marginTop: '4px' }}>
              This order was cancelled. If you paid online, refund will be processed within 5–7 days.
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div style={S.timelineCard}>
      <div style={S.timelineTitle}>📦 Order Status</div>

      <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', position: 'relative' }}>
        {STEPS.map((step, index) => {
          const isDone   = index <= currentIndex;
          const isActive = index === currentIndex;

          return (
            <div key={step.key} style={{
              flex: 1, display: 'flex', flexDirection: 'column',
              alignItems: 'center', position: 'relative', zIndex: 1,
            }}>
              {/* Connector line */}
              {index < STEPS.length - 1 && (
                <div style={{
                  position: 'absolute', top: '22px', left: '50%',
                  width: '100%', height: '2px',
                  background: index < currentIndex
                    ? 'var(--color-tertiary, #4c3e25)'
                    : 'var(--color-outline-variant, #cfc5bc)',
                  transition: 'background 0.5s ease', zIndex: 0,
                }} />
              )}

              {/* Icon circle */}
              <div style={{
                width: '44px', height: '44px', borderRadius: '50%',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontSize: '1.1rem', position: 'relative', zIndex: 2,
                background: isDone
                  ? 'var(--gradient-tertiary, linear-gradient(135deg, #4c3e25 0%, #65553a 100%))'
                  : 'var(--color-surface, #fcf9f8)',
                border: isActive
                  ? '3px solid var(--color-tertiary, #4c3e25)'
                  : isDone ? '3px solid var(--color-tertiary, #4c3e25)' : '1px solid var(--color-outline-variant, #cfc5bc)',
                color: isDone ? '#fff' : 'var(--color-outline, #7e766e)',
                boxShadow: isActive
                  ? '0 0 16px rgba(76,62,37,0.4)'
                  : isDone ? '0 0 8px rgba(76,62,37,0.15)' : 'none',
                transition: 'all 0.4s ease',
                animation: isActive ? 'ord-pulse 2s infinite' : 'none',
              }}>
                {isDone && !isActive ? '✓' : step.icon}
              </div>

              {/* Label */}
              <div style={{ marginTop: '12px', textAlign: 'center', padding: '0 2px' }}>
                <div style={{
                  color: isDone ? 'var(--color-on-surface, #1b1b1c)' : 'var(--color-outline, #7e766e)',
                  fontWeight: isActive ? 700 : 500,
                  fontSize: '0.75rem', lineHeight: 1.3,
                  transition: 'color 0.4s ease',
                }}>
                  {step.label}
                </div>
                {isActive && (
                  <div style={{
                    color: 'var(--color-tertiary, #4c3e25)', fontSize: '0.65rem',
                    marginTop: '4px', fontWeight: 700,
                    letterSpacing: '0.04em',
                  }}>
                    ● Current
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>

      {/* Active step description */}
      {currentIndex >= 0 && (
        <div style={{
          marginTop: '28px', padding: '16px 20px',
          background: 'var(--color-surface, #fcf9f8)',
          borderLeft: '3px solid var(--color-tertiary, #4c3e25)',
          borderRadius: '0 4px 4px 0',
          border: '1px solid var(--color-outline-variant, #cfc5bc)',
          borderLeftWidth: '3px',
        }}>
          <span style={{ color: 'var(--color-on-surface-variant, #4d453f)', fontSize: '0.85rem' }}>
            {STEPS[currentIndex].desc}
          </span>
        </div>
      )}

      <style>{`
        @keyframes ord-pulse {
          0%,100% { box-shadow: 0 0 12px rgba(76,62,37,0.4); }
          50% { box-shadow: 0 0 24px rgba(76,62,37,0.7); }
        }
      `}</style>
    </div>
  );
}

function TrackingCard({ trackingNumber, courierName }) {
  const trackUrl = getTrackingUrl(courierName, trackingNumber);

  return (
    <div style={S.trackingCard}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '16px' }}>
        <div>
          <div style={{ color: 'var(--color-on-surface-variant, #4d453f)', fontSize: '0.75rem', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.08em', marginBottom: '8px' }}>
            🚚 Shipment Tracking
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px', flexWrap: 'wrap' }}>
            <span style={{
              fontFamily: 'monospace', fontSize: '1.1rem', fontWeight: 700,
              color: 'var(--color-tertiary, #4c3e25)', letterSpacing: '0.05em',
            }}>
              {trackingNumber}
            </span>
            {courierName && (
              <span style={{
                background: 'var(--color-surface-container, #f0eded)', color: 'var(--color-on-surface-variant, #4d453f)',
                fontSize: '0.75rem', padding: '4px 12px', borderRadius: '4px',
                fontWeight: 600,
                border: '1px solid var(--color-outline-variant, #cfc5bc)',
              }}>
                {courierName}
              </span>
            )}
          </div>
        </div>
        {trackUrl && (
          <a
            href={trackUrl}
            target="_blank"
            rel="noopener noreferrer"
            style={{
              display: 'inline-flex', alignItems: 'center', gap: '8px',
              background: 'var(--gradient-tertiary, linear-gradient(135deg, #4c3e25 0%, #65553a 100%))',
              color: '#fff', fontWeight: 700, fontSize: '0.85rem',
              padding: '10px 20px', borderRadius: '4px',
              textDecoration: 'none',
              transition: 'transform .2s',
            }}
            onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-2px)'; }}
            onMouseLeave={e => { e.currentTarget.style.transform = ''; }}
          >
            📍 Track Package
          </a>
        )}
      </div>
    </div>
  );
}

function StatusBadge({ status }) {
  const cfg = {
    pending:    { bg: '#fef3c7', color: '#92400e', label: 'Pending' },
    confirmed:  { bg: '#dbeafe', color: '#1e40af', label: 'Confirmed' },
    processing: { bg: '#ede9fe', color: '#5b21b6', label: 'Processing' },
    shipped:    { bg: '#e0f2fe', color: '#0369a1', label: 'Shipped' },
    delivered:  { bg: '#d1fae5', color: '#065f46', label: 'Delivered' },
    cancelled:  { bg: '#fee2e2', color: '#991b1b', label: 'Cancelled' },
  }[status] || { bg: '#f3f4f6', color: '#374151', label: status };

  return (
    <span style={{
      background: cfg.bg, color: cfg.color,
      fontWeight: 700, fontSize: '0.8rem',
      padding: '6px 14px', borderRadius: '4px',
      textTransform: 'capitalize',
      border: '1px solid rgba(0,0,0,0.05)',
    }}>
      {cfg.label}
    </span>
  );
}

// ─── Main Page Content Component ──────────────────────────────────────────────
function OrderDetailPageContent() {
  const params = useParams();
  const id = params?.id;
  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [cancelling, setCancelling] = useState(false);
  const [cancelError, setCancelError] = useState(null);
  const [cancelSuccess, setCancelSuccess] = useState(false);
  const [downloadingInvoice, setDownloadingInvoice] = useState(false);

  const handleDownloadInvoice = async () => {
    try {
      setDownloadingInvoice(true);
      let pdfUrl = null;
      try {
        const res = await axiosInstance.get(`/orders/${id}/invoice`);
        if (res.data.success && res.data.invoice?.pdf_url) {
          pdfUrl = res.data.invoice.pdf_url;
        }
      } catch (err) {
        if (err.response?.status === 404) {
          const genRes = await axiosInstance.post(`/orders/${id}/invoice`);
          if (genRes.data.success && genRes.data.invoice?.pdf_url) {
            pdfUrl = genRes.data.invoice.pdf_url;
          }
        } else {
          throw err;
        }
      }

      if (pdfUrl) {
        window.open(pdfUrl, '_blank');
      } else {
        alert('Could not retrieve invoice PDF URL.');
      }
    } catch (err) {
      alert(err.response?.data?.message || 'Failed to download invoice.');
    } finally {
      setDownloadingInvoice(false);
    }
  };

  useEffect(() => {
    if (id) {
      fetchOrder();
    }
  }, [id]);

  const fetchOrder = async () => {
    try {
      setLoading(true);
      setError(null);
      const res = await axiosInstance.get(`/orders/${id}`);
      if (res.data.success) setOrder(res.data.order);
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
      const res = await axiosInstance.put(`/orders/${id}/cancel`);
      if (res.data.success) {
        setCancelSuccess(true);
        setShowCancelModal(false);
        await fetchOrder();
      }
    } catch (err) {
      setCancelError(err.response?.data?.message || 'Failed to cancel order');
    } finally {
      setCancelling(false);
    }
  };

  if (loading) return (
    <div style={{ ...S.page, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
      <div style={{ textAlign: 'center' }}>
        <div style={{
          width: '48px', height: '48px', borderRadius: '50%',
          border: '4px solid var(--color-surface-container, #f0eded)', borderTopColor: 'var(--color-tertiary, #4c3e25)',
          animation: 'spin 0.8s linear infinite', margin: '0 auto 16px',
        }} />
        <p style={{ color: 'var(--color-on-surface-variant, #4d453f)', fontSize: '0.9rem' }}>Loading order details…</p>
        <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>
      </div>
    </div>
  );

  if (error) return (
    <div style={S.page}>
      <div style={S.inner}>
        <div style={{ background: '#fee2e2', border: '1px solid #fca5a5', borderRadius: '4px', padding: '16px', color: '#991b1b', marginBottom: '16px' }}>
          {error}
        </div>
        <Link href="/orders" style={S.backLink}>← Back to Orders</Link>
      </div>
    </div>
  );

  if (!order) return null;

  const isShipped  = ['shipped', 'delivered'].includes(order.status);
  const isPending  = order.status === 'pending';
  const isCod      = order.payment_method === 'cod';

  return (
    <div style={S.page}>
      <div style={S.inner}>

        {/* Back link */}
        <Link href="/orders" style={S.backLink}>
          ← Back to Orders
        </Link>

        {/* Cancel success banner */}
        {cancelSuccess && (
          <div style={{ background: '#d1fae5', border: '1px solid #6ee7b7', borderRadius: '4px', padding: '14px 18px', color: '#065f46', fontWeight: 600, marginBottom: '16px' }}>
            ✅ Order cancelled successfully.
          </div>
        )}

        {/* ── Header card ── */}
        <div style={S.card}>
          <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', flexWrap: 'wrap', gap: '16px' }}>
            <div>
              <h1 style={{ fontFamily: '"Noto Serif", serif', fontSize: '1.6rem', fontWeight: 700, color: 'var(--color-primary, #463f38)', margin: '0 0 8px' }}>
                Order #{order.order_number}
              </h1>
              <p style={{ color: 'var(--color-on-surface-variant, #4d453f)', fontSize: '0.85rem', margin: 0 }}>
                Placed on {new Date(order.created_at).toLocaleDateString('en-IN', { year: 'numeric', month: 'long', day: 'numeric' })}
              </p>
              {order.status === 'delivered' && (
                <button
                  onClick={handleDownloadInvoice}
                  disabled={downloadingInvoice}
                  style={{
                    marginTop: '20px',
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '8px',
                    background: 'var(--gradient-tertiary, linear-gradient(135deg, #4c3e25 0%, #65553a 100%))',
                    color: '#fff',
                    fontWeight: 700,
                    fontSize: '0.85rem',
                    padding: '12px 24px',
                    borderRadius: '4px',
                    border: 'none',
                    cursor: downloadingInvoice ? 'not-allowed' : 'pointer',
                    transition: 'opacity 0.2s',
                    opacity: downloadingInvoice ? 0.7 : 1,
                  }}
                  onMouseEnter={e => { if (!downloadingInvoice) { e.currentTarget.style.opacity = 0.9; } }}
                  onMouseLeave={e => { if (!downloadingInvoice) { e.currentTarget.style.opacity = 1; } }}
                >
                  {downloadingInvoice ? (
                    <>
                      <span style={{
                        width: '14px',
                        height: '14px',
                        borderRadius: '50%',
                        border: '2px solid #fff',
                        borderTopColor: 'transparent',
                        animation: 'spin 0.6s linear infinite',
                        display: 'inline-block',
                      }} />
                      <span>Downloading…</span>
                    </>
                  ) : (
                    <>
                      <span>📄</span>
                      <span>Download Invoice</span>
                    </>
                  )}
                </button>
              )}
            </div>
            <StatusBadge status={order.status} />
          </div>
        </div>

        {/* ── Timeline ── */}
        <OrderTimeline status={order.status} />

        {/* ── Tracking card (only when shipped/delivered) ── */}
        {isShipped && order.tracking_number && (
          <TrackingCard
            trackingNumber={order.tracking_number}
            courierName={order.courier_name}
          />
        )}

        {/* ── Order items ── */}
        <div style={S.card}>
          <div style={S.sectionTitle}>🛍️ Order Items</div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '20px' }}>
            {order.items?.map((item) => (
              <div key={item.id} style={{ display: 'flex', gap: '16px', alignItems: 'center' }}>
                <div style={{
                  width: '72px', height: '72px', borderRadius: '4px',
                  overflow: 'hidden', background: 'var(--color-surface-low, #f6f3f2)',
                  flexShrink: 0,
                  border: '1px solid var(--color-outline-variant, #cfc5bc)',
                }}>
                  {item.product?.image_urls?.[0] ? (
                    <img src={item.product.image_urls[0]} alt={item.product.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                  ) : item.product?.images?.[0] ? (
                    <img src={getImageUrl(item.product.images[0])} alt={item.product.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                  ) : (
                    <div style={{ width: '100%', height: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--color-outline, #7e766e)', fontSize: '1.5rem' }}>🖼</div>
                  )}
                </div>
                <div style={{ flex: 1 }}>
                  <div style={{ fontWeight: 600, color: 'var(--color-on-surface, #1b1b1c)', fontSize: '0.95rem' }}>{item.product?.name || 'Product'}</div>
                  <div style={{ color: 'var(--color-on-surface-variant, #4d453f)', fontSize: '0.82rem', marginTop: '4px' }}>Qty: {item.quantity}</div>
                </div>
                <div style={{ fontWeight: 700, color: 'var(--color-tertiary, #4c3e25)', fontSize: '0.95rem', whiteSpace: 'nowrap' }}>
                  ₹{parseFloat(item.price).toLocaleString('en-IN')}
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* ── Delivery address ── */}
        <div style={S.card}>
          <div style={S.sectionTitle}>📍 Delivery Address</div>
          <div style={{ color: 'var(--color-on-surface-variant, #4d453f)', lineHeight: 1.7, fontSize: '0.9rem' }}>
            <div style={{ fontWeight: 700, color: 'var(--color-on-surface, #1b1b1c)', marginBottom: '4px' }}>{order.address_snapshot?.name}</div>
            <div>{order.address_snapshot?.line1}</div>
            {order.address_snapshot?.line2 && <div>{order.address_snapshot.line2}</div>}
            <div>{order.address_snapshot?.city}, {order.address_snapshot?.state} – {order.address_snapshot?.pincode}</div>
          </div>
        </div>

        {/* ── Payment summary ── */}
        <div style={S.card}>
          <div style={S.sectionTitle}>💳 Payment Summary</div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>

            <div style={{ display: 'flex', justifycontent: 'space-between', fontSize: '0.88rem' }}>
              <span style={{ color: 'var(--color-on-surface-variant, #4d453f)' }}>Payment Method</span>
              <span style={{ fontWeight: 600, color: 'var(--color-on-surface, #1b1b1c)' }}>
                {isCod ? '💵 Cash on Delivery' : '💳 Razorpay (Online)'}
              </span>
            </div>

            {order.payment_method === 'razorpay' && order.payment_id && (
              <div style={{ display: 'flex', justifycontent: 'space-between', fontSize: '0.88rem' }}>
                <span style={{ color: 'var(--color-on-surface-variant, #4d453f)' }}>Payment ID</span>
                <span style={{ fontFamily: 'monospace', fontSize: '0.82rem', color: 'var(--color-on-surface, #1b1b1c)' }}>{order.payment_id}</span>
              </div>
            )}

            <div style={{ display: 'flex', justifycontent: 'space-between', fontSize: '0.88rem' }}>
              <span style={{ color: 'var(--color-on-surface-variant, #4d453f)' }}>Payment Status</span>
              <span style={{
                fontWeight: 700,
                color: order.payment_status === 'paid' ? '#065f46' : '#92400e',
                background: order.payment_status === 'paid' ? '#d1fae5' : '#fef3c7',
                padding: '3px 12px', borderRadius: '4px', fontSize: '0.78rem',
                border: '1px solid rgba(0,0,0,0.02)',
              }}>
                {order.payment_status === 'paid' ? '✅ Paid' : isCod ? '⏳ Pay on Delivery' : '⏳ Pending'}
              </span>
            </div>

            <div style={{ borderTop: '1px solid var(--color-outline-variant, #cfc5bc)', paddingTop: '16px', display: 'flex', justifycontent: 'space-between', alignItems: 'center' }}>
              <span style={{ fontWeight: 700, color: 'var(--color-on-surface, #1b1b1c)', fontSize: '1rem' }}>Total Amount</span>
              <span style={{ fontWeight: 800, color: 'var(--color-tertiary, #4c3e25)', fontSize: '1.25rem' }}>
                ₹{parseFloat(order.total).toLocaleString('en-IN')}
              </span>
            </div>
          </div>
        </div>

        {/* ── Cancel button ── */}
        {isPending && (
          <button
            onClick={() => setShowCancelModal(true)}
            style={{
              width: '100%', padding: '14px', borderRadius: '4px', border: 'none',
              background: 'linear-gradient(135deg, #ef4444, #dc2626)',
              color: '#fff', fontWeight: 700, fontSize: '0.95rem',
              cursor: 'pointer', marginTop: '4px',
              transition: 'opacity .2s',
            }}
            onMouseEnter={e => { e.currentTarget.style.opacity = 0.9; }}
            onMouseLeave={e => { e.currentTarget.style.opacity = 1; }}
          >
            Cancel Order
          </button>
        )}
      </div>

      {/* ── Cancel modal ── */}
      {showCancelModal && (
        <div style={{
          position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          zIndex: 50, padding: '16px',
        }}>
          <div style={{
            background: '#fff', borderRadius: '4px', padding: '32px',
            maxWidth: '420px', width: '100%',
            border: '1px solid var(--color-outline-variant, #cfc5bc)',
            boxShadow: '0 20px 60px rgba(0,0,0,0.15)',
          }}>
            <div style={{ fontSize: '2.5rem', marginBottom: '12px', textAlign: 'center' }}>⚠️</div>
            <h3 style={{ fontFamily: '"Noto Serif", serif', fontWeight: 700, color: 'var(--color-primary, #463f38)', fontSize: '1.25rem', textAlign: 'center', margin: '0 0 12px' }}>
              Cancel this order?
            </h3>
            <p style={{ color: 'var(--color-on-surface-variant, #4d453f)', fontSize: '0.88rem', textAlign: 'center', margin: '0 0 24px' }}>
              Order <strong>{order.order_number}</strong> will be cancelled. This cannot be undone.
            </p>

            {cancelError && (
              <div style={{ background: '#fee2e2', borderRadius: '4px', padding: '12px', color: '#991b1b', fontSize: '0.84rem', marginBottom: '16px' }}>
                {cancelError}
              </div>
            )}

            <div style={{ display: 'flex', gap: '12px' }}>
              <button
                onClick={() => { setShowCancelModal(false); setCancelError(null); }}
                disabled={cancelling}
                style={{
                  flex: 1, padding: '12px', borderRadius: '4px',
                  border: '1px solid var(--color-outline-variant, #cfc5bc)', background: '#fff',
                  fontWeight: 700, color: 'var(--color-on-surface-variant, #4d453f)', cursor: 'pointer', fontSize: '0.9rem',
                }}
              >
                Keep Order
              </button>
              <button
                onClick={handleCancelOrder}
                disabled={cancelling}
                style={{
                  flex: 1, padding: '12px', borderRadius: '4px', border: 'none',
                  background: 'linear-gradient(135deg, #ef4444, #dc2626)',
                  color: '#fff', fontWeight: 700, fontSize: '0.9rem',
                  cursor: cancelling ? 'not-allowed' : 'pointer',
                  opacity: cancelling ? 0.7 : 1,
                }}
              >
                {cancelling ? 'Cancelling…' : 'Yes, Cancel'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default function OrderDetailPage() {
  return (
    <ProtectedRoute>
      <OrderDetailPageContent />
    </ProtectedRoute>
  );
}
