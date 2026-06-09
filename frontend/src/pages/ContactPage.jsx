import { useState } from 'react';
import axiosInstance from '../lib/axios';

export default function ContactPage() {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitStatus, setSubmitStatus] = useState(null);

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    setSubmitStatus(null);

    try {
      const response = await axiosInstance.post('/contact', formData);
      
      if (response.data.success) {
        setSubmitStatus('success');
        setFormData({
          name: '',
          email: '',
          phone: '',
          subject: '',
          message: '',
        });
      } else {
        setSubmitStatus('error');
      }
    } catch (error) {
      setSubmitStatus('error');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen" style={{ background: '#fcf9f8' }}>
      {/* Hero Section */}
      <div
        className="py-20 px-8"
        style={{
          background: 'linear-gradient(135deg, #f6f3f2 0%, #fcf9f8 100%)',
          borderBottom: '1px solid rgba(207,197,188,0.20)',
        }}
      >
        <div className="max-w-screen-xl mx-auto text-center">
          <h1
            className="text-5xl md:text-6xl mb-6 italic"
            style={{ fontFamily: 'Noto Serif, serif', color: '#4c3e25' }}
          >
            Get in Touch
          </h1>
          <p
            className="text-base md:text-lg max-w-2xl mx-auto leading-relaxed"
            style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
          >
            Have a question about our handcrafted jewelry? We'd love to hear from you.
            Reach out and we'll respond as soon as possible.
          </p>
        </div>
      </div>

      {/* Contact Content */}
      <div className="max-w-screen-xl mx-auto px-8 py-16">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-12">
          
          {/* Contact Information */}
          <div className="lg:col-span-1 space-y-8">
            <div>
              <h2
                className="text-2xl mb-8 italic"
                style={{ fontFamily: 'Noto Serif, serif', color: '#4c3e25' }}
              >
                Contact Information
              </h2>
              
              <div className="space-y-6">
                {/* Email */}
                <div className="flex items-start gap-4">
                  <div
                    className="p-3 rounded-full"
                    style={{ background: '#f6f3f2' }}
                  >
                    <svg className="w-5 h-5" style={{ color: '#4c3e25' }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div>
                    <p
                      className="text-xs uppercase tracking-widest mb-1"
                      style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                    >
                      Email
                    </p>
                    <a
                      href="mailto:contact@vriddhi.com"
                      className="text-base transition-colors duration-200"
                      style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                      onMouseEnter={(e) => (e.target.style.color = '#463f38')}
                      onMouseLeave={(e) => (e.target.style.color = '#4c3e25')}
                    >
                      contact@vriddhi.com
                    </a>
                  </div>
                </div>

                {/* Phone */}
                <div className="flex items-start gap-4">
                  <div
                    className="p-3 rounded-full"
                    style={{ background: '#f6f3f2' }}
                  >
                    <svg className="w-5 h-5" style={{ color: '#4c3e25' }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  </div>
                  <div>
                    <p
                      className="text-xs uppercase tracking-widest mb-1"
                      style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                    >
                      Phone
                    </p>
                    <a
                      href="tel:+911234567890"
                      className="text-base transition-colors duration-200"
                      style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                      onMouseEnter={(e) => (e.target.style.color = '#463f38')}
                      onMouseLeave={(e) => (e.target.style.color = '#4c3e25')}
                    >
                      +91 123 456 7890
                    </a>
                  </div>
                </div>

                {/* Address */}
                <div className="flex items-start gap-4">
                  <div
                    className="p-3 rounded-full"
                    style={{ background: '#f6f3f2' }}
                  >
                    <svg className="w-5 h-5" style={{ color: '#4c3e25' }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                  </div>
                  <div>
                    <p
                      className="text-xs uppercase tracking-widest mb-1"
                      style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                    >
                      Address
                    </p>
                    <p
                      className="text-base leading-relaxed"
                      style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                    >
                      123 Vriddhi Street<br />
                      Mumbai, Maharashtra 400001<br />
                      India
                    </p>
                  </div>
                </div>
              </div>
            </div>

            {/* Business Hours */}
            <div
              className="p-6 rounded-lg"
              style={{ background: '#f6f3f2', border: '1px solid rgba(207,197,188,0.25)' }}
            >
              <h3
                className="text-lg mb-4 italic"
                style={{ fontFamily: 'Noto Serif, serif', color: '#4c3e25' }}
              >
                Business Hours
              </h3>
              <div className="space-y-2">
                <div className="flex justify-between">
                  <span
                    className="text-sm"
                    style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                  >
                    Monday - Friday
                  </span>
                  <span
                    className="text-sm font-medium"
                    style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                  >
                    9:00 AM - 6:00 PM
                  </span>
                </div>
                <div className="flex justify-between">
                  <span
                    className="text-sm"
                    style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                  >
                    Saturday
                  </span>
                  <span
                    className="text-sm font-medium"
                    style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                  >
                    10:00 AM - 4:00 PM
                  </span>
                </div>
                <div className="flex justify-between">
                  <span
                    className="text-sm"
                    style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                  >
                    Sunday
                  </span>
                  <span
                    className="text-sm font-medium"
                    style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                  >
                    Closed
                  </span>
                </div>
              </div>
            </div>
          </div>

          {/* Contact Form */}
          <div className="lg:col-span-2">
            <div
              className="p-8 md:p-12 rounded-lg"
              style={{
                background: '#f6f3f2',
                border: '1px solid rgba(207,197,188,0.25)',
                boxShadow: '0 8px 32px rgba(27,27,28,0.06)',
              }}
            >
              <h2
                className="text-3xl mb-2 italic"
                style={{ fontFamily: 'Noto Serif, serif', color: '#4c3e25' }}
              >
                Send us a Message
              </h2>
              <p
                className="text-sm mb-8"
                style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
              >
                Fill out the form below and we'll get back to you within 24 hours.
              </p>

              <form onSubmit={handleSubmit} className="space-y-6">
                {/* Name */}
                <div>
                  <label
                    htmlFor="name"
                    className="block text-xs uppercase tracking-widest mb-2"
                    style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                  >
                    Full Name *
                  </label>
                  <input
                    type="text"
                    id="name"
                    name="name"
                    value={formData.name}
                    onChange={handleChange}
                    required
                    className="w-full px-4 py-3 rounded-lg transition-all duration-200 focus:outline-none"
                    style={{
                      background: '#fcf9f8',
                      border: '1px solid rgba(207,197,188,0.30)',
                      color: '#1b1b1c',
                      fontFamily: 'Manrope, sans-serif',
                    }}
                    onFocus={(e) => (e.target.style.borderColor = '#4c3e25')}
                    onBlur={(e) => (e.target.style.borderColor = 'rgba(207,197,188,0.30)')}
                  />
                </div>

                {/* Email & Phone */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label
                      htmlFor="email"
                      className="block text-xs uppercase tracking-widest mb-2"
                      style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                    >
                      Email Address *
                    </label>
                    <input
                      type="email"
                      id="email"
                      name="email"
                      value={formData.email}
                      onChange={handleChange}
                      required
                      className="w-full px-4 py-3 rounded-lg transition-all duration-200 focus:outline-none"
                      style={{
                        background: '#fcf9f8',
                        border: '1px solid rgba(207,197,188,0.30)',
                        color: '#1b1b1c',
                        fontFamily: 'Manrope, sans-serif',
                      }}
                      onFocus={(e) => (e.target.style.borderColor = '#4c3e25')}
                      onBlur={(e) => (e.target.style.borderColor = 'rgba(207,197,188,0.30)')}
                    />
                  </div>

                  <div>
                    <label
                      htmlFor="phone"
                      className="block text-xs uppercase tracking-widest mb-2"
                      style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                    >
                      Phone Number
                    </label>
                    <input
                      type="tel"
                      id="phone"
                      name="phone"
                      value={formData.phone}
                      onChange={handleChange}
                      className="w-full px-4 py-3 rounded-lg transition-all duration-200 focus:outline-none"
                      style={{
                        background: '#fcf9f8',
                        border: '1px solid rgba(207,197,188,0.30)',
                        color: '#1b1b1c',
                        fontFamily: 'Manrope, sans-serif',
                      }}
                      onFocus={(e) => (e.target.style.borderColor = '#4c3e25')}
                      onBlur={(e) => (e.target.style.borderColor = 'rgba(207,197,188,0.30)')}
                    />
                  </div>
                </div>

                {/* Subject */}
                <div>
                  <label
                    htmlFor="subject"
                    className="block text-xs uppercase tracking-widest mb-2"
                    style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                  >
                    Subject *
                  </label>
                  <input
                    type="text"
                    id="subject"
                    name="subject"
                    value={formData.subject}
                    onChange={handleChange}
                    required
                    className="w-full px-4 py-3 rounded-lg transition-all duration-200 focus:outline-none"
                    style={{
                      background: '#fcf9f8',
                      border: '1px solid rgba(207,197,188,0.30)',
                      color: '#1b1b1c',
                      fontFamily: 'Manrope, sans-serif',
                    }}
                    onFocus={(e) => (e.target.style.borderColor = '#4c3e25')}
                    onBlur={(e) => (e.target.style.borderColor = 'rgba(207,197,188,0.30)')}
                  />
                </div>

                {/* Message */}
                <div>
                  <label
                    htmlFor="message"
                    className="block text-xs uppercase tracking-widest mb-2"
                    style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                  >
                    Message *
                  </label>
                  <textarea
                    id="message"
                    name="message"
                    value={formData.message}
                    onChange={handleChange}
                    required
                    rows="6"
                    className="w-full px-4 py-3 rounded-lg transition-all duration-200 focus:outline-none resize-none"
                    style={{
                      background: '#fcf9f8',
                      border: '1px solid rgba(207,197,188,0.30)',
                      color: '#1b1b1c',
                      fontFamily: 'Manrope, sans-serif',
                    }}
                    onFocus={(e) => (e.target.style.borderColor = '#4c3e25')}
                    onBlur={(e) => (e.target.style.borderColor = 'rgba(207,197,188,0.30)')}
                  />
                </div>

                {/* Submit Button */}
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="w-full md:w-auto px-8 py-4 rounded-lg transition-all duration-300 flex items-center justify-center gap-2"
                  style={{
                    background: isSubmitting ? '#7e766e' : '#4c3e25',
                    color: '#fff',
                    fontFamily: 'Manrope, sans-serif',
                    fontSize: '14px',
                    letterSpacing: '0.05em',
                    textTransform: 'uppercase',
                    cursor: isSubmitting ? 'not-allowed' : 'pointer',
                  }}
                  onMouseEnter={(e) => {
                    if (!isSubmitting) e.target.style.background = '#463f38';
                  }}
                  onMouseLeave={(e) => {
                    if (!isSubmitting) e.target.style.background = '#4c3e25';
                  }}
                >
                  {isSubmitting ? (
                    <>
                      <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                      Sending...
                    </>
                  ) : (
                    <>
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                      </svg>
                      Send Message
                    </>
                  )}
                </button>

                {/* Status Messages */}
                {submitStatus === 'success' && (
                  <div
                    className="p-4 rounded-lg"
                    style={{
                      background: 'rgba(76, 175, 80, 0.1)',
                      border: '1px solid rgba(76, 175, 80, 0.3)',
                    }}
                  >
                    <p
                      className="text-sm"
                      style={{ color: '#2e7d32', fontFamily: 'Manrope, sans-serif' }}
                    >
                      Thank you for your message! We'll get back to you soon.
                    </p>
                  </div>
                )}

                {submitStatus === 'error' && (
                  <div
                    className="p-4 rounded-lg"
                    style={{
                      background: 'rgba(244, 67, 54, 0.1)',
                      border: '1px solid rgba(244, 67, 54, 0.3)',
                    }}
                  >
                    <p
                      className="text-sm"
                      style={{ color: '#c62828', fontFamily: 'Manrope, sans-serif' }}
                    >
                      Something went wrong. Please try again later.
                    </p>
                  </div>
                )}
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
