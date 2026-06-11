import { useState, useEffect } from 'react';
import axiosInstance from '../lib/axios';

export default function CorporateGiftingPage() {
  const [categories, setCategories] = useState([]);
  const [formData, setFormData] = useState({
    company_name: '',
    company_email: '',
    contact_number: '',
    categories: [],
    message: '',
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitStatus, setSubmitStatus] = useState(null);
  const [charCount, setCharCount] = useState(0);

  // Fetch actual product categories from the API
  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const response = await axiosInstance.get('/categories');
        const cats = response.data?.categories || response.data?.data || response.data;
        if (Array.isArray(cats) && cats.length > 0) {
          // Flatten parent + children category names
          const names = [];
          cats.forEach((c) => {
            names.push(c.name);
            if (c.children && Array.isArray(c.children)) {
              c.children.forEach((child) => names.push(child.name));
            }
          });
          setCategories(names);
        }
      } catch (err) {
        // Fallback categories
        setCategories(['Rings', 'Necklaces', 'Bracelets', 'Earrings', 'Custom']);
      }
    };
    fetchCategories();
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    if (name === 'message') {
      if (value.length <= 500) {
        setFormData((prev) => ({ ...prev, message: value }));
        setCharCount(value.length);
      }
    } else {
      setFormData((prev) => ({ ...prev, [name]: value }));
    }
  };

  const handleCategoryToggle = (category) => {
    setFormData((prev) => ({
      ...prev,
      categories: prev.categories.includes(category)
        ? prev.categories.filter((c) => c !== category)
        : [...prev.categories, category],
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    setSubmitStatus(null);

    try {
      const response = await axiosInstance.post('/corporate-enquiry', formData);
      if (response.data.success) {
        setSubmitStatus('success');
        setFormData({
          company_name: '',
          company_email: '',
          contact_number: '',
          categories: [],
          message: '',
        });
        setCharCount(0);
      } else {
        setSubmitStatus('error');
      }
    } catch (error) {
      setSubmitStatus('error');
    } finally {
      setIsSubmitting(false);
    }
  };

  // Input style helper
  const inputStyle = {
    background: '#fcf9f8',
    border: '1px solid rgba(207,197,188,0.30)',
    color: '#1b1b1c',
    fontFamily: 'Manrope, sans-serif',
  };

  const features = [
    {
      icon: (
        <svg className="w-7 h-7" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        </svg>
      ),
      title: 'Bulk Pricing',
      description:
        'Exclusive volume discounts for orders of 10+ pieces. The more you order, the more you save on our handcrafted collections.',
    },
    {
      icon: (
        <svg className="w-7 h-7" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
        </svg>
      ),
      title: 'Custom Packaging',
      description:
        'Premium gift packaging with your company logo and personalised messaging. Create a lasting impression with every gift.',
    },
    {
      icon: (
        <svg className="w-7 h-7" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
        </svg>
      ),
      title: 'Dedicated Support',
      description:
        'A dedicated account manager to guide you through the entire process — from selection to delivery and beyond.',
    },
  ];

  return (
    <div className="min-h-screen" style={{ background: '#fcf9f8' }}>

      {/* ── Hero Section ── */}
      <section
        className="relative overflow-hidden"
        style={{ minHeight: '520px' }}
      >
        {/* Dark geometric background */}
        <div
          className="absolute inset-0"
          style={{
            background: 'linear-gradient(135deg, #1b1b1c 0%, #2d2926 40%, #463f38 100%)',
          }}
        />
        {/* Decorative pattern overlay */}
        <div
          className="absolute inset-0"
          style={{
            backgroundImage: `radial-gradient(circle at 20% 50%, rgba(207,197,188,0.08) 0%, transparent 50%),
                              radial-gradient(circle at 80% 20%, rgba(207,197,188,0.06) 0%, transparent 40%),
                              radial-gradient(circle at 60% 80%, rgba(76,62,37,0.12) 0%, transparent 50%)`,
          }}
        />
        {/* Geometric accent lines */}
        <div
          className="absolute top-0 right-0 w-1/2 h-full hidden lg:block"
          style={{
            backgroundImage: `linear-gradient(135deg, transparent 30%, rgba(207,197,188,0.04) 30%, rgba(207,197,188,0.04) 31%, transparent 31%),
                              linear-gradient(135deg, transparent 50%, rgba(207,197,188,0.03) 50%, rgba(207,197,188,0.03) 51%, transparent 51%),
                              linear-gradient(135deg, transparent 70%, rgba(207,197,188,0.02) 70%, rgba(207,197,188,0.02) 71%, transparent 71%)`,
          }}
        />

        <div className="relative max-w-screen-xl mx-auto px-8 py-28 md:py-36 flex flex-col items-center text-center">
          <span
            className="uppercase tracking-[0.35em] text-xs font-semibold block mb-6"
            style={{ color: '#cfc5bc', fontFamily: 'Manrope, sans-serif' }}
          >
            For Businesses & Organisations
          </span>
          <h1
            className="text-5xl md:text-7xl italic leading-tight mb-6"
            style={{
              fontFamily: 'Noto Serif, serif',
              letterSpacing: '-0.03em',
              color: '#ffffff',
            }}
          >
            Corporate Gifting
          </h1>
          <div className="w-16 h-px mx-auto mb-8" style={{ background: '#cfc5bc' }} />
          <p
            className="text-base md:text-lg max-w-2xl mx-auto leading-relaxed mb-10"
            style={{ color: 'rgba(255,255,255,0.7)', fontFamily: 'Manrope, sans-serif' }}
          >
            Elevate your business relationships with our handcrafted collections. 
            Bulk orders for corporate events, employee gifts, client appreciation, 
            and business partnerships — curated with intention.
          </p>
          <a
            href="#enquiry-form"
            className="inline-flex items-center justify-center px-10 py-4 text-sm font-medium tracking-wider uppercase transition-all duration-300"
            style={{
              background: 'rgba(255,255,255,0.12)',
              color: '#ffffff',
              fontFamily: 'Manrope, sans-serif',
              letterSpacing: '0.1em',
              border: '1px solid rgba(255,255,255,0.25)',
              backdropFilter: 'blur(8px)',
            }}
            onMouseEnter={(e) => {
              e.currentTarget.style.background = 'rgba(255,255,255,0.20)';
              e.currentTarget.style.borderColor = 'rgba(255,255,255,0.45)';
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.background = 'rgba(255,255,255,0.12)';
              e.currentTarget.style.borderColor = 'rgba(255,255,255,0.25)';
            }}
          >
            Submit Enquiry
            <svg className="w-4 h-4 ml-3" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
          </a>
        </div>
      </section>

      {/* ── Why Choose Us Section ── */}
      <section className="py-20 px-8" style={{ background: '#f6f3f2' }}>
        <div className="max-w-screen-xl mx-auto">
          <div className="text-center mb-16">
            <h2
              className="text-3xl md:text-4xl mb-4 italic"
              style={{ fontFamily: 'Noto Serif, serif', color: '#1b1b1c' }}
            >
              Why Choose Us
            </h2>
            <div className="w-12 h-px mx-auto mb-4" style={{ background: '#4c3e25' }} />
            <p
              className="text-sm max-w-xl mx-auto"
              style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
            >
              We make corporate gifting effortless and memorable.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {features.map((feature, idx) => (
              <div
                key={idx}
                className="p-8 rounded-lg text-center transition-all duration-300 group"
                style={{
                  background: '#fcf9f8',
                  border: '1px solid rgba(207,197,188,0.20)',
                  boxShadow: '0 4px 24px rgba(27,27,28,0.04)',
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.boxShadow = '0 12px 40px rgba(27,27,28,0.10)';
                  e.currentTarget.style.transform = 'translateY(-4px)';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.boxShadow = '0 4px 24px rgba(27,27,28,0.04)';
                  e.currentTarget.style.transform = 'translateY(0)';
                }}
              >
                <div
                  className="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6"
                  style={{ background: '#f6f3f2', color: '#4c3e25' }}
                >
                  {feature.icon}
                </div>
                <h3
                  className="text-xl mb-3"
                  style={{ fontFamily: 'Noto Serif, serif', color: '#1b1b1c' }}
                >
                  {feature.title}
                </h3>
                <p
                  className="text-sm leading-relaxed"
                  style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                >
                  {feature.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ── Enquiry Form Section ── */}
      <section id="enquiry-form" className="py-20 px-8">
        <div className="max-w-screen-lg mx-auto">
          <div className="grid grid-cols-1 lg:grid-cols-5 gap-12">

            {/* Left info */}
            <div className="lg:col-span-2 flex flex-col justify-center">
              <span
                className="uppercase tracking-[0.25em] text-xs font-semibold block mb-4"
                style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
              >
                Corporate Enquiry
              </span>
              <h2
                className="text-3xl md:text-4xl mb-6 italic"
                style={{ fontFamily: 'Noto Serif, serif', color: '#1b1b1c' }}
              >
                Let's Create Something Special
              </h2>
              <p
                className="text-sm leading-relaxed mb-8"
                style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
              >
                Fill out the form and our corporate gifting team will get back to you 
                within 24 hours with a personalised quote and catalogue.
              </p>

              <div className="space-y-6">
                {/* Email */}
                <div className="flex items-center gap-4">
                  <div className="p-3 rounded-full" style={{ background: '#f6f3f2' }}>
                    <svg className="w-5 h-5" style={{ color: '#4c3e25' }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div>
                    <p className="text-xs uppercase tracking-widest mb-1" style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}>
                      Email
                    </p>
                    <a href="mailto:corporate@vriddhi.in" className="text-sm" style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}>
                      corporate@vriddhi.in
                    </a>
                  </div>
                </div>
                {/* Phone */}
                <div className="flex items-center gap-4">
                  <div className="p-3 rounded-full" style={{ background: '#f6f3f2' }}>
                    <svg className="w-5 h-5" style={{ color: '#4c3e25' }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  </div>
                  <div>
                    <p className="text-xs uppercase tracking-widest mb-1" style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}>
                      Phone
                    </p>
                    <a href="tel:+919650640407" className="text-sm" style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}>
                      +91 96506 40407
                    </a>
                  </div>
                </div>
              </div>
            </div>

            {/* Right form */}
            <div className="lg:col-span-3">
              <div
                className="p-8 md:p-10 rounded-lg"
                style={{
                  background: '#f6f3f2',
                  border: '1px solid rgba(207,197,188,0.25)',
                  boxShadow: '0 8px 32px rgba(27,27,28,0.06)',
                }}
              >
                <form onSubmit={handleSubmit} className="space-y-6">
                  {/* Company Name */}
                  <div>
                    <label
                      htmlFor="company_name"
                      className="block text-xs uppercase tracking-widest mb-2"
                      style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                    >
                      Company Name *
                    </label>
                    <input
                      type="text"
                      id="company_name"
                      name="company_name"
                      value={formData.company_name}
                      onChange={handleChange}
                      required
                      placeholder="Enter your company name"
                      className="w-full px-4 py-3 rounded-lg transition-all duration-200 focus:outline-none"
                      style={inputStyle}
                      onFocus={(e) => (e.target.style.borderColor = '#4c3e25')}
                      onBlur={(e) => (e.target.style.borderColor = 'rgba(207,197,188,0.30)')}
                    />
                  </div>

                  {/* Email & Phone row */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label
                        htmlFor="company_email"
                        className="block text-xs uppercase tracking-widest mb-2"
                        style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                      >
                        Company Email *
                      </label>
                      <input
                        type="email"
                        id="company_email"
                        name="company_email"
                        value={formData.company_email}
                        onChange={handleChange}
                        required
                        placeholder="company@domain.com"
                        className="w-full px-4 py-3 rounded-lg transition-all duration-200 focus:outline-none"
                        style={inputStyle}
                        onFocus={(e) => (e.target.style.borderColor = '#4c3e25')}
                        onBlur={(e) => (e.target.style.borderColor = 'rgba(207,197,188,0.30)')}
                      />
                    </div>
                    <div>
                      <label
                        htmlFor="contact_number"
                        className="block text-xs uppercase tracking-widest mb-2"
                        style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                      >
                        Contact Number *
                      </label>
                      <input
                        type="tel"
                        id="contact_number"
                        name="contact_number"
                        value={formData.contact_number}
                        onChange={handleChange}
                        required
                        placeholder="+91 98765 43210"
                        className="w-full px-4 py-3 rounded-lg transition-all duration-200 focus:outline-none"
                        style={inputStyle}
                        onFocus={(e) => (e.target.style.borderColor = '#4c3e25')}
                        onBlur={(e) => (e.target.style.borderColor = 'rgba(207,197,188,0.30)')}
                      />
                    </div>
                  </div>

                  {/* Categories */}
                  {categories.length > 0 && (
                    <div>
                      <label
                        className="block text-xs uppercase tracking-widest mb-3"
                        style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                      >
                        Categories
                      </label>
                      <div className="flex flex-wrap gap-3">
                        {categories.map((category) => {
                          const isSelected = formData.categories.includes(category);
                          return (
                            <button
                              key={category}
                              type="button"
                              onClick={() => handleCategoryToggle(category)}
                              className="px-4 py-2 rounded-full text-sm transition-all duration-200 cursor-pointer"
                              style={{
                                fontFamily: 'Manrope, sans-serif',
                                background: isSelected ? '#463f38' : '#fcf9f8',
                                color: isSelected ? '#ffffff' : '#4c3e25',
                                border: isSelected
                                  ? '1px solid #463f38'
                                  : '1px solid rgba(207,197,188,0.40)',
                              }}
                            >
                              {isSelected && (
                                <svg className="w-3 h-3 inline mr-1.5 -mt-0.5" fill="none" stroke="currentColor" strokeWidth={3} viewBox="0 0 24 24">
                                  <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                              )}
                              {category}
                            </button>
                          );
                        })}
                      </div>
                    </div>
                  )}

                  {/* Message */}
                  <div>
                    <label
                      htmlFor="message"
                      className="block text-xs uppercase tracking-widest mb-2"
                      style={{ color: '#4c3e25', fontFamily: 'Manrope, sans-serif' }}
                    >
                      Message
                    </label>
                    <textarea
                      id="message"
                      name="message"
                      value={formData.message}
                      onChange={handleChange}
                      rows="4"
                      placeholder="Tell us about your requirements, quantity, timeline..."
                      className="w-full px-4 py-3 rounded-lg transition-all duration-200 focus:outline-none resize-none"
                      style={inputStyle}
                      onFocus={(e) => (e.target.style.borderColor = '#4c3e25')}
                      onBlur={(e) => (e.target.style.borderColor = 'rgba(207,197,188,0.30)')}
                    />
                    <p
                      className="text-right text-xs mt-1"
                      style={{ color: '#7e766e', fontFamily: 'Manrope, sans-serif' }}
                    >
                      {charCount} / 500
                    </p>
                  </div>

                  {/* Submit Button */}
                  <button
                    type="submit"
                    disabled={isSubmitting}
                    className="w-full px-8 py-4 rounded-lg transition-all duration-300 flex items-center justify-center gap-2"
                    style={{
                      background: isSubmitting
                        ? '#7e766e'
                        : 'linear-gradient(135deg, #463f38 0%, #5e564f 100%)',
                      color: '#fff',
                      fontFamily: 'Manrope, sans-serif',
                      fontSize: '14px',
                      letterSpacing: '0.08em',
                      textTransform: 'uppercase',
                      cursor: isSubmitting ? 'not-allowed' : 'pointer',
                      boxShadow: isSubmitting ? 'none' : '0 8px 24px rgba(70,63,56,0.22)',
                    }}
                    onMouseEnter={(e) => {
                      if (!isSubmitting) e.currentTarget.style.opacity = '0.9';
                    }}
                    onMouseLeave={(e) => {
                      if (!isSubmitting) e.currentTarget.style.opacity = '1';
                    }}
                  >
                    {isSubmitting ? (
                      <>
                        <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                        Submitting...
                      </>
                    ) : (
                      <>
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        Submit Corporate Enquiry
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
                      <p className="text-sm" style={{ color: '#2e7d32', fontFamily: 'Manrope, sans-serif' }}>
                        ✓ Your corporate gifting enquiry has been submitted! Our team will reach out within 24 hours.
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
                      <p className="text-sm" style={{ color: '#c62828', fontFamily: 'Manrope, sans-serif' }}>
                        Something went wrong. Please try again or email us directly.
                      </p>
                    </div>
                  )}
                </form>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ── Bottom CTA Section ── */}
      <section
        className="py-20 px-8"
        style={{
          background: 'linear-gradient(135deg, #5e564f 0%, #463f38 50%, #2d2926 100%)',
        }}
      >
        <div className="max-w-screen-lg mx-auto text-center">
          <h2
            className="text-3xl md:text-4xl mb-4 italic"
            style={{ fontFamily: 'Noto Serif, serif', color: '#ffffff' }}
          >
            Pleasure to have you here!
          </h2>
          <p
            className="text-lg mb-2"
            style={{ color: 'rgba(255,255,255,0.75)', fontFamily: 'Manrope, sans-serif' }}
          >
            You have arrived to the world of
          </p>
          <p
            className="text-xl mb-10"
            style={{ color: 'rgba(255,255,255,0.85)', fontFamily: 'Manrope, sans-serif', fontWeight: 500 }}
          >
            the finest Corporate Gifting catalogue
          </p>

          <div className="w-12 h-px mx-auto mb-10" style={{ background: 'rgba(207,197,188,0.4)' }} />

          <h3
            className="text-2xl md:text-3xl mb-8 font-semibold uppercase tracking-wider"
            style={{ color: '#ffffff', fontFamily: 'Manrope, sans-serif' }}
          >
            Connect Here
          </h3>

          <a
            href="mailto:corporate@vriddhi.in"
            className="inline-flex items-center gap-3 text-lg md:text-xl transition-opacity duration-300 hover:opacity-80"
            style={{ color: '#cfc5bc', fontFamily: 'Manrope, sans-serif' }}
          >
            <svg className="w-6 h-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
            corporate@vriddhi.in
          </a>
        </div>
      </section>
    </div>
  );
}
