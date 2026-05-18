import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import axiosInstance from '../lib/axios';

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

// ─── Styles ───────────────────────────────────────────────────────────────────
const S = {
  page: {
    minHeight: '100vh',
    background: '#f8f7f4',
    padding: '24px 16px 48px',
    fontFamily: "'Inter', 'Segoe UI', sans-serif",
  },
  inner: { maxWidth: '780px', margin: '0 auto' },
  backLink: {
    display: 'inline-flex', alignItems: 'center', gap: '6px',
    color: '#c2782a', fontWeight: 600, fontSize: '0.9rem',
    textDecoration: 'none', marginBottom: '20px',
    transition: 'opacity .2s',
  },
  card: {
    background: '#fff', borderRadius: '16px',
    boxShadow: '0 2px 16px rgba(0,0,0,0.07)',
    padding: '28px', marginBottom: '16px',
  },
  sectionTitle: {
    fontSize: '0.8rem', fontWeight: 700, color: '#888',
    textTransform: 'uppercase', letterSpacing: '0.08em', marginBottom: '16px',
  },

  // Timeline card
  timelineCard: {
    background: 'linear-gradient(135deg, #1a1a2e 0%, #16213e 55%, #0f3460 100%)',
    borderRadius: '16px', padding: '28px 24px', marginBottom: '16px',
    boxShadow: '0 8px 32px rgba(15,52,96,0.25)',
  },
  timelineTitle: {
    color: 'rgba(255,255,255,0.65)', fontSize: '0.75rem', fontWeight: 700,
    textTransform: 'uppercase', letterSpacing: '0.1em', marginBottom: '28px',
  },

  // Tracking card
  trackingCard: {
    background: 'linear-gradient(135deg, #1e3a5f 0%, #0f3460 100%)',
    border: '1px solid rgba(194,120,42,0.35)',
    borderRadius: '14px', padding: '20px 22px', marginBottom: '16px',
    boxShadow: '0 4px 20px rgba(0,0,0,0.12)',
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
          background: 'rgba(239,68,68,0.12)',
          border: '1px solid rgba(239,68,68,0.35)',
          borderRadius: '12px', padding: '18px 20px',
        }}>
          <span style={{ fontSize: '2.2rem' }}>❌</span>
          <div>
            <div style={{ color: '#f87171', fontWeight: 700, fontSize: '1.1rem' }}>Order Cancelled</div>
            <div style={{ color: 'rgba(255,255,255,0.45)', fontSize: '0.83rem', marginTop: '4px' }}>
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

      {/* Mobile-friendly vertical layout on small screens via flex wrap */}
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
                  width: '100%', height: '3px',
                  background: index < currentIndex
                    ? 'linear-gradient(90deg, #f97316, #c2782a)'
                    : 'rgba(255,255,255,0.08)',
                  transition: 'background 0.5s ease', zIndex: 0,
                }} />
              )}

              {/* Icon circle */}
              <div style={{
                width: '46px', height: '46px', borderRadius: '50%',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontSize: '1.25rem', position: 'relative', zIndex: 2,
                background: isDone
                  ? 'linear-gradient(135deg, #f97316, #c2782a)'
                  : 'rgba(255,255,255,0.06)',
                border: isActive
                  ? '3px solid #fb923c'
                  : isDone ? '3px solid #f97316' : '3px solid rgba(255,255,255,0.12)',
                boxShadow: isActive
                  ? '0 0 20px rgba(249,115,22,0.7)'
                  : isDone ? '0 0 10px rgba(249,115,22,0.3)' : 'none',
                transition: 'all 0.4s ease',
                animation: isActive ? 'ord-pulse 2s infinite' : 'none',
              }}>
                {isDone && !isActive ? '✓' : step.icon}
              </div>

              {/* Label */}
              <div style={{ marginTop: '10px', textAlign: 'center', padding: '0 2px' }}>
                <div style={{
                  color: isDone ? '#fff' : 'rgba(255,255,255,0.28)',
                  fontWeight: isActive ? 700 : 500,
                  fontSize: '0.72rem', lineHeight: 1.3,
                  transition: 'color 0.4s ease',
                }}>
                  {step.label}
                </div>
                {isActive && (
                  <div style={{
                    color: '#fb923c', fontSize: '0.65rem',
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
          marginTop: '24px', padding: '12px 16px',
          background: 'rgba(249,115,22,0.1)',
          borderLeft: '3px solid #f97316',
          borderRadius: '0 8px 8px 0',
        }}>
          <span style={{ color: 'rgba(255,255,255,0.7)', fontSize: '0.82rem' }}>
            {STEPS[currentIndex].desc}
          </span>
        </div>
      )}

      <style>{`
        @keyframes ord-pulse {
          0%,100% { box-shadow: 0 0 16px rgba(249,115,22,0.6); }
          50% { box-shadow: 0 0 30px rgba(249,115,22,1); }
        }
      `}</style>
    </div>
  );
}

function TrackingCard({ trackingNumber, courierName }) {
  const trackUrl = getTrackingUrl(courierName, trackingNumber);

  return (
    <div style={S.trackingCard}>
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap', gap: '12px' }}>
        <div>
          <div style={{ color: 'rgba(255,255,255,0.5)', fontSize: '0.72rem', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.08em', marginBottom: '6px' }}>
            🚚 Shipment Tracking
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', flexWrap: 'wrap' }}>
            <span style={{
              fontFamily: 'monospace', fontSize: '1.05rem', fontWeight: 700,
              color: '#fb923c', letterSpacing: '0.05em',
            }}>
              {trackingNumber}
            </span>
            {courierName && (
              <span style={{
                background: 'rgba(255,255,255,0.1)', color: 'rgba(255,255,255,0.7)',
                fontSize: '0.75rem', padding: '3px 10px', borderRadius: '20px',
                fontWeight: 600,
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
              display: 'inline-flex', alignItems: 'center', gap: '6px',
              background: 'linear-gradient(135deg, #f97316, #c2782a)',
              color: '#fff', fontWeight: 700, fontSize: '0.85rem',
              padding: '10px 18px', borderRadius: '10px',
              textDecoration: 'none',
              boxShadow: '0 4px 12px rgba(249,115,22,0.4)',
              transition: 'transform .2s, box-shadow .2s',
            }}
            onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-2px)'; e.currentTarget.style.boxShadow = '0 6px 20px rgba(249,115,22,0.55)'; }}
            onMouseLeave={e => { e.currentTarget.style.transform = ''; e.currentTarget.style.boxShadow = '0 4px 12px rgba(249,115,22,0.4)'; }}
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
    shipped:    { bg: '#f3e8ff', color: '#6b21a8', label: 'Shipped' },
    delivered:  { bg: '#d1fae5', color: '#065f46', label: 'Delivered' },
    cancelled:  { bg: '#fee2e2', color: '#991b1b', label: 'Cancelled' },
  }[status] || { bg: '#f3f4f6', color: '#374151', label: status };

  return (
    <span style={{
      background: cfg.bg, color: cfg.color,
      fontWeight: 700, fontSize: '0.82rem',
      padding: '6px 14px', borderRadius: '20px',
      textTransform: 'capitalize',
    }}>
      {cfg.label}
    </span>
  );
}

// ─── Main Page ────────────────────────────────────────────────────────────────
export default function OrderDetailPage() {
  const { id } = useParams();
  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showCancelModal, setShowCancelModal] = useState(false);
  const [cancelling, setCancelling] = useState(false);
  const [cancelError, setCancelError] = useState(null);
  const [cancelSuccess, setCancelSuccess] = useState(false);

  useEffect(() => { fetchOrder(); }, [id]);

  const fetchOrder = async () => {
    try {
      setLoading(true);
      setError(null);
      const res = await axiosInstance.get(`/orders/${id}`);
      if (res.data.success) setOrder(res.data.order);  // Clean: res.data.order
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

  // ── Loading ──
  if (loading) return (
    <div style={{ ...S.page, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
      <div style={{ textAlign: 'center' }}>
        <div style={{
          width: '48px', height: '48px', borderRadius: '50%',
          border: '4px solid #f3f4f6', borderTopColor: '#f97316',
          animation: 'spin 0.8s linear infinite', margin: '0 auto 16px',
        }} />
        <p style={{ color: '#888', fontSize: '0.9rem' }}>Loading order details…</p>
        <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>
      </div>
    </div>
  );

  // ── Error ──
  if (error) return (
    <div style={S.page}>
      <div style={S.inner}>
        <div style={{ background: '#fee2e2', border: '1px solid #fca5a5', borderRadius: '12px', padding: '16px', color: '#991b1b', marginBottom: '16px' }}>
          {error}
        </div>
        <Link to="/orders" style={S.backLink}>← Back to Orders</Link>
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
        <Link to="/orders" style={S.backLink}>
          ← Back to Orders
        </Link>

        {/* Cancel success banner */}
        {cancelSuccess && (
          <div style={{ background: '#d1fae5', border: '1px solid #6ee7b7', borderRadius: '12px', padding: '14px 18px', color: '#065f46', fontWeight: 600, marginBottom: '16px' }}>
            ✅ Order cancelled successfully.
          </div>
        )}

        {/* ── Header card ── */}
        <div style={S.card}>
          <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', flexWrap: 'wrap', gap: '12px' }}>
            <div>
              <h1 style={{ fontSize: '1.4rem', fontWeight: 800, color: '#1a1a2e', margin: '0 0 6px' }}>
                Order #{order.order_number}
              </h1>
              <p style={{ color: '#888', fontSize: '0.85rem', margin: 0 }}>
                Placed on {new Date(order.created_at).toLocaleDateString('en-IN', { year: 'numeric', month: 'long', day: 'numeric' })}
              </p>
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
          <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
            {order.items?.map((item) => (
              <div key={item.id} style={{ display: 'flex', gap: '14px', alignItems: 'center' }}>
                <div style={{
                  width: '72px', height: '72px', borderRadius: '10px',
                  overflow: 'hidden', background: '#f3f4f6', flexShrink: 0,
                }}>
                  {item.product?.images?.[0] ? (
                    <img src={item.product.images[0]} alt={item.product.name} style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                  ) : (
                    <div style={{ width: '100%', height: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#ccc', fontSize: '1.5rem' }}>🖼</div>
                  )}
                </div>
                <div style={{ flex: 1 }}>
                  <div style={{ fontWeight: 600, color: '#1a1a2e', fontSize: '0.95rem' }}>{item.product?.name || 'Product'}</div>
                  <div style={{ color: '#888', fontSize: '0.82rem', marginTop: '3px' }}>Qty: {item.quantity}</div>
                </div>
                <div style={{ fontWeight: 700, color: '#c2782a', fontSize: '0.95rem', whiteSpace: 'nowrap' }}>
                  ₹{parseFloat(item.price).toLocaleString('en-IN')}
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* ── Delivery address ── */}
        <div style={S.card}>
          <div style={S.sectionTitle}>📍 Delivery Address</div>
          <div style={{ color: '#444', lineHeight: 1.7, fontSize: '0.9rem' }}>
            <div style={{ fontWeight: 700, color: '#1a1a2e' }}>{order.address_snapshot?.name}</div>
            <div>{order.address_snapshot?.line1}</div>
            {order.address_snapshot?.line2 && <div>{order.address_snapshot.line2}</div>}
            <div>{order.address_snapshot?.city}, {order.address_snapshot?.state} – {order.address_snapshot?.pincode}</div>
          </div>
        </div>

        {/* ── Payment summary ── */}
        <div style={S.card}>
          <div style={S.sectionTitle}>💳 Payment Summary</div>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>

            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.88rem' }}>
              <span style={{ color: '#888' }}>Payment Method</span>
              <span style={{ fontWeight: 600, color: '#1a1a2e' }}>
                {isCod ? '💵 Cash on Delivery' : '💳 Razorpay (Online)'}
              </span>
            </div>

            {order.payment_method === 'razorpay' && order.payment_id && (
              <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.88rem' }}>
                <span style={{ color: '#888' }}>Payment ID</span>
                <span style={{ fontFamily: 'monospace', fontSize: '0.82rem', color: '#555' }}>{order.payment_id}</span>
              </div>
            )}

            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: '0.88rem' }}>
              <span style={{ color: '#888' }}>Payment Status</span>
              <span style={{
                fontWeight: 700,
                color: order.payment_status === 'paid' ? '#065f46' : '#92400e',
                background: order.payment_status === 'paid' ? '#d1fae5' : '#fef3c7',
                padding: '2px 10px', borderRadius: '12px', fontSize: '0.78rem',
              }}>
                {order.payment_status === 'paid' ? '✅ Paid' : isCod ? '⏳ Pay on Delivery' : '⏳ Pending'}
              </span>
            </div>

            <div style={{ borderTop: '1px solid #f3f4f6', paddingTop: '10px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <span style={{ fontWeight: 700, color: '#1a1a2e', fontSize: '1rem' }}>Total Amount</span>
              <span style={{ fontWeight: 800, color: '#c2782a', fontSize: '1.15rem' }}>
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
              width: '100%', padding: '14px', borderRadius: '12px', border: 'none',
              background: 'linear-gradient(135deg, #ef4444, #dc2626)',
              color: '#fff', fontWeight: 700, fontSize: '0.95rem',
              cursor: 'pointer', marginTop: '4px',
              boxShadow: '0 4px 14px rgba(239,68,68,0.3)',
              transition: 'transform .2s, box-shadow .2s',
            }}
            onMouseEnter={e => { e.currentTarget.style.transform = 'translateY(-2px)'; }}
            onMouseLeave={e => { e.currentTarget.style.transform = ''; }}
          >
            Cancel Order
          </button>
        )}
      </div>

      {/* ── Cancel modal ── */}
      {showCancelModal && (
        <div style={{
          position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)',
          display: 'flex', alignItems: 'center', justifyContent: 'center',
          zIndex: 50, padding: '16px',
        }}>
          <div style={{
            background: '#fff', borderRadius: '20px', padding: '32px',
            maxWidth: '420px', width: '100%',
            boxShadow: '0 20px 60px rgba(0,0,0,0.2)',
          }}>
            <div style={{ fontSize: '2.5rem', marginBottom: '12px', textAlign: 'center' }}>⚠️</div>
            <h3 style={{ fontWeight: 800, color: '#1a1a2e', fontSize: '1.2rem', textAlign: 'center', margin: '0 0 10px' }}>
              Cancel this order?
            </h3>
            <p style={{ color: '#666', fontSize: '0.88rem', textAlign: 'center', margin: '0 0 20px' }}>
              Order <strong>{order.order_number}</strong> will be cancelled. This cannot be undone.
            </p>

            {cancelError && (
              <div style={{ background: '#fee2e2', borderRadius: '10px', padding: '12px', color: '#991b1b', fontSize: '0.84rem', marginBottom: '16px' }}>
                {cancelError}
              </div>
            )}

            <div style={{ display: 'flex', gap: '12px' }}>
              <button
                onClick={() => { setShowCancelModal(false); setCancelError(null); }}
                disabled={cancelling}
                style={{
                  flex: 1, padding: '12px', borderRadius: '10px',
                  border: '2px solid #e5e7eb', background: '#fff',
                  fontWeight: 700, color: '#555', cursor: 'pointer', fontSize: '0.9rem',
                }}
              >
                Keep Order
              </button>
              <button
                onClick={handleCancelOrder}
                disabled={cancelling}
                style={{
                  flex: 1, padding: '12px', borderRadius: '10px', border: 'none',
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
