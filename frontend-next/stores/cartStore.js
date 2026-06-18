import { create } from 'zustand';
import axios from '../lib/axios';

/**
 * Shopping Cart Store
 * 
 * Manages cart state including items, item count, and subtotal.
 * Provides actions for adding, updating, removing items, and clearing cart.
 */
const useCartStore = create((set, get) => ({
  // State
  items: [],
  itemCount: 0,
  subtotal: 0,
  isLoading: false,
  error: null,

  // Actions
  
  /**
   * Fetch cart from API
   */
  fetchCart: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await axios.get('/cart');
      
      if (response.data.success) {
        // API returns data.cart.items, not data.items
        const cartItems = response.data.data?.cart?.items || [];
        const itemCount = cartItems.reduce((sum, item) => sum + item.quantity, 0);
        const subtotal = cartItems.reduce(
          (sum, item) => sum + (item.quantity * parseFloat(item.product.price)),
          0
        );
        
        set({
          items: cartItems,
          itemCount,
          subtotal,
          isLoading: false,
        });
      }
    } catch (error) {
      console.error('Fetch cart error:', error);
      set({ 
        error: error.response?.data?.message || 'Failed to fetch cart',
        isLoading: false 
      });
    }
  },

  /**
   * Add item to cart
   * @param {number} productId - Product ID
   * @param {number} quantity - Quantity to add
   */
  addItem: async (productId, quantity = 1) => {
    try {
      const response = await axios.post('/cart/items', {
        product_id: productId,
        quantity,
      });
      
      if (response.data?.success) {
        // Refresh cart after adding
        await get().fetchCart();
        return { success: true };
      } else {
        return {
          success: false,
          message: response.data?.message || 'Failed to add item to cart',
        };
      }
    } catch (error) {
      console.error('Add to cart error:', error);
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to add item to cart',
      };
    }
  },

  /**
   * Update cart item quantity
   * @param {number} cartItemId - Cart item ID
   * @param {number} quantity - New quantity
   */
  updateItem: async (cartItemId, quantity) => {
    try {
      const response = await axios.put(`/cart/items/${cartItemId}`, {
        quantity,
      });
      
      if (response.data.success) {
        // Refresh cart after updating
        await get().fetchCart();
        return { success: true };
      }
    } catch (error) {
      console.error('Update cart item error:', error);
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to update item quantity',
      };
    }
  },

  /**
   * Remove item from cart
   * @param {number} cartItemId - Cart item ID
   */
  removeItem: async (cartItemId) => {
    try {
      const response = await axios.delete(`/cart/items/${cartItemId}`);
      
      if (response.data.success) {
        // Refresh cart after removing
        await get().fetchCart();
        return { success: true };
      }
    } catch (error) {
      console.error('Remove cart item error:', error);
      return {
        success: false,
        message: error.response?.data?.message || 'Failed to remove item',
      };
    }
  },

  /**
   * Clear cart (local state only)
   */
  clearCart: () => {
    set({
      items: [],
      itemCount: 0,
      subtotal: 0,
    });
  },
}));

export default useCartStore;
