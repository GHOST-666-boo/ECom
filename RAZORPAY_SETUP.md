# Razorpay Payment Gateway Setup

## Overview
This guide will help you configure Razorpay payment gateway for your e-commerce application.

## Prerequisites
- Razorpay account (Sign up at https://razorpay.com/)
- Access to Razorpay Dashboard

## Step 1: Get Razorpay Credentials

1. **Login to Razorpay Dashboard**
   - Go to https://dashboard.razorpay.com/
   - Login with your credentials

2. **Get API Keys**
   - Navigate to Settings → API Keys
   - You'll see two modes:
     - **Test Mode** (for development) - Keys start with `rzp_test_`
     - **Live Mode** (for production) - Keys start with `rzp_live_`
   
3. **Copy Your Keys**
   - Key ID (e.g., `rzp_test_1234567890abcd`)
   - Key Secret (e.g., `your_secret_key_here`)

## Step 2: Configure Backend (.env)

Open `backend/.env` and update:

```env
RAZORPAY_KEY_ID=rzp_test_your_actual_key_id
RAZORPAY_KEY_SECRET=your_actual_key_secret
RAZORPAY_WEBHOOK_SECRET=your_webhook_secret
```

**Important:** 
- Use **Test Mode** keys for development
- Use **Live Mode** keys only in production
- Never commit actual keys to version control

## Step 3: Configure Frontend (.env)

Open `frontend/.env` and update:

```env
VITE_RAZORPAY_KEY_ID=rzp_test_your_actual_key_id
```

**Note:** Only the Key ID is needed in frontend, NOT the secret.

## Step 4: Test the Integration

1. **Start Backend Server**
   ```bash
   cd backend
   php artisan serve
   ```

2. **Start Frontend Server**
   ```bash
   cd frontend
   npm run dev
   ```

3. **Test Payment Flow**
   - Add products to cart
   - Go to checkout
   - Select "Razorpay" as payment method
   - Click "Finalise Purchase"
   - Razorpay payment modal should open

## Test Cards (Test Mode Only)

Use these test cards in Test Mode:

### Successful Payment
- **Card Number:** 4111 1111 1111 1111
- **CVV:** Any 3 digits
- **Expiry:** Any future date
- **Name:** Any name

### Failed Payment
- **Card Number:** 4000 0000 0000 0002
- **CVV:** Any 3 digits
- **Expiry:** Any future date

### UPI Test
- **UPI ID:** success@razorpay
- **UPI ID (Failed):** failure@razorpay

## Troubleshooting

### Error: "Failed to create Razorpay order"

**Possible Causes:**
1. Razorpay credentials not configured
2. Invalid API keys
3. Network connectivity issues

**Solutions:**
1. Verify credentials in `.env` files
2. Check if keys are from correct mode (test/live)
3. Restart both backend and frontend servers after updating `.env`
4. Check backend logs: `backend/storage/logs/laravel.log`

### Error: "Payment gateway not configured"

This means Razorpay keys are not set or still have placeholder values.

**Solution:**
1. Update `backend/.env` with actual Razorpay keys
2. Update `frontend/.env` with actual Razorpay Key ID
3. Restart servers

### Payment Modal Not Opening

**Possible Causes:**
1. Razorpay script not loaded
2. Invalid Key ID in frontend

**Solutions:**
1. Check browser console for errors
2. Verify `VITE_RAZORPAY_KEY_ID` in `frontend/.env`
3. Clear browser cache and reload

## Alternative: Use Cash on Delivery

If you don't want to configure Razorpay immediately:
- Select "Cash on Delivery" as payment method during checkout
- This works without any payment gateway configuration

## Production Deployment

When deploying to production:

1. **Switch to Live Mode Keys**
   - Get Live Mode keys from Razorpay Dashboard
   - Update both backend and frontend `.env` files

2. **Enable Webhooks** (Optional but recommended)
   - Go to Razorpay Dashboard → Webhooks
   - Add webhook URL: `https://yourdomain.com/api/v1/webhooks/razorpay`
   - Copy webhook secret to `RAZORPAY_WEBHOOK_SECRET`

3. **Test Thoroughly**
   - Test with real cards (small amounts)
   - Verify order status updates
   - Check email notifications

## Security Best Practices

1. ✅ Never expose Key Secret in frontend
2. ✅ Use environment variables for all credentials
3. ✅ Use Test Mode for development
4. ✅ Validate webhook signatures
5. ✅ Use HTTPS in production
6. ✅ Keep credentials in `.env` (not in version control)

## Support

- **Razorpay Documentation:** https://razorpay.com/docs/
- **Razorpay Support:** https://razorpay.com/support/
- **Test Mode Guide:** https://razorpay.com/docs/payments/payments/test-card-details/

## Current Status

⚠️ **Razorpay is currently NOT configured**

To enable Razorpay payments:
1. Follow steps above to get credentials
2. Update `.env` files
3. Restart servers
4. Test the payment flow

Until then, use **Cash on Delivery** for testing orders.
