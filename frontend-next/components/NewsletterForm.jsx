'use client';

import { useState } from 'react';
import axios from '../lib/axios';

/**
 * NewsletterForm - Metallic Vriddhi (Oat Edition)
 * Minimal underline input + dark pill button, used in footer
 */
export default function NewsletterForm() {
  const [email, setEmail] = useState('');
  const [status, setStatus] = useState({ message: '', type: '' });
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!email.trim()) return;
    setIsSubmitting(true);
    setStatus({ message: '', type: '' });
    try {
      const response = await axios.post('/newsletter/subscribe', { email });
      if (response.data.success) {
        setStatus({ message: 'Subscribed! Welcome to The Folio.', type: 'success' });
        setEmail('');
      }
    } catch (error) {
      if (error.response?.status === 422 && error.response?.data?.errors?.email) {
        setStatus({ message: error.response.data.errors.email[0], type: 'error' });
      } else {
        setStatus({ message: error.response?.data?.message || 'Failed to subscribe. Try again.', type: 'error' });
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div>
      <h3
        className="text-xl mb-2"
        style={{ fontFamily: 'Noto Serif, serif', fontStyle: 'italic', color: '#4c3e25' }}
      >
        Stay Within the Glow
      </h3>
      <p
        className="text-xs tracking-wide mb-6"
        style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
      >
        Join The Folio for exclusive access to limited-run collections and artisan profiles.
      </p>

      {status.message && (
        <div
          className="mb-4 px-4 py-2 text-xs tracking-wide"
          style={{
            background: status.type === 'success' ? '#f6dfbc' : '#ffdad6',
            color: status.type === 'success' ? '#53452b' : '#93000a',
            fontFamily: 'Manrope, sans-serif',
          }}
          role="alert"
        >
          {status.message}
        </div>
      )}

      <form onSubmit={handleSubmit} className="flex gap-0">
        <input
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          placeholder="Email Address"
          className="flex-grow py-3 px-2 text-sm focus:outline-none transition-colors"
          style={{
            background: 'transparent',
            borderBottom: '1px solid #7e766e',
            color: '#1b1b1c',
            fontFamily: 'Manrope, sans-serif',
          }}
          required
          disabled={isSubmitting}
          aria-label="Email address"
        />
        <button
          type="submit"
          disabled={isSubmitting}
          className="px-8 py-3 text-xs uppercase tracking-widest font-bold transition-opacity hover:opacity-80 disabled:opacity-40"
          style={{
            background: '#463f38',
            color: '#ffffff',
            fontFamily: 'Manrope, sans-serif',
          }}
        >
          {isSubmitting ? '...' : 'Join'}
        </button>
      </form>
    </div>
  );
}
