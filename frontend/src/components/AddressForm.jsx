import { useEffect } from 'react';
import axiosInstance from '../lib/axios';
import useFormState from '../hooks/useFormState';
import { setApiErrors } from '../lib/apiError';
import FormField from './ui/FormField';
import FormAlert from './ui/FormAlert';

/**
 * AddressForm Component
 * 
 * Form for creating or updating addresses.
 * Fields: name, line1, line2, city, state, pincode, is_default
 * Validates pincode format (6 digits)
 * Submits to API: POST /api/v1/user/addresses (create) or PUT /api/v1/user/addresses/{id} (update)
 * Displays validation errors inline
 * 
 * Requirements: 13.2, 13.3, 13.4, 13.5, 13.8
 */
export default function AddressForm({ address, onSuccess, onCancel }) {
  const {
    formData, setFormData, errors, setErrors, isLoading, setIsLoading, handleChange,
  } = useFormState({
    name: '',
    line1: '',
    line2: '',
    city: '',
    state: '',
    pincode: '',
    is_default: false,
  });

  // Populate form if editing
  useEffect(() => {
    if (address) {
      setFormData({
        name: address.name || '',
        line1: address.line1 || '',
        line2: address.line2 || '',
        city: address.city || '',
        state: address.state || '',
        pincode: address.pincode || '',
        is_default: address.is_default || false,
      });
    }
  }, [address, setFormData]);

  // Validate pincode format (6 digits)
  const validatePincode = (pincode) => /^[0-9]{6}$/.test(pincode);

  // Handle form submit
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    // Client-side validation
    const newErrors = {};
    if (!formData.name.trim()) newErrors.name = 'Name is required';
    if (!formData.line1.trim()) newErrors.line1 = 'Address line 1 is required';
    if (!formData.city.trim()) newErrors.city = 'City is required';
    if (!formData.state.trim()) newErrors.state = 'State is required';
    if (!formData.pincode.trim()) {
      newErrors.pincode = 'Pincode is required';
    } else if (!validatePincode(formData.pincode)) {
      newErrors.pincode = 'Pincode must be exactly 6 digits';
    }
    
    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    try {
      setIsLoading(true);
      setErrors({});
      
      let response;
      if (address) {
        response = await axiosInstance.put(`/user/addresses/${address.id}`, formData);
      } else {
        response = await axiosInstance.post('/user/addresses', formData);
      }
      
      if (response.data.success) {
        onSuccess();
      }
    } catch (err) {
      setApiErrors(err, setErrors, 'Failed to save address');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-5">
      <FormAlert type="error" message={errors.general} />

      {/* Name field */}
      <div>
        <label htmlFor="name" className="block text-xs font-medium text-[#5b5149] mb-1 uppercase tracking-widest">
          Name / Label <span className="text-[#ba1a1a]">*</span>
        </label>
        <input
          type="text"
          id="name"
          name="name"
          value={formData.name}
          onChange={handleChange}
          className={`w-full px-1 py-2 bg-transparent border-0 border-b-2 focus:outline-none ${
            errors.name ? 'border-[#ba1a1a]' : 'border-[#cec5bc] focus:border-[#745b21]'
          }`}
          placeholder="e.g., Home, Office"
        />
        {errors.name && (
          <p className="mt-1 text-sm text-[#ba1a1a]">{errors.name}</p>
        )}
      </div>

      {/* Line 1 field */}
      <div>
        <label htmlFor="line1" className="block text-xs font-medium text-[#5b5149] mb-1 uppercase tracking-widest">
          Address Line 1 <span className="text-[#ba1a1a]">*</span>
        </label>
        <input
          type="text"
          id="line1"
          name="line1"
          value={formData.line1}
          onChange={handleChange}
          className={`w-full px-1 py-2 bg-transparent border-0 border-b-2 focus:outline-none ${
            errors.line1 ? 'border-[#ba1a1a]' : 'border-[#cec5bc] focus:border-[#745b21]'
          }`}
          placeholder="House/Flat No., Building Name, Street"
        />
        {errors.line1 && (
          <p className="mt-1 text-sm text-[#ba1a1a]">{errors.line1}</p>
        )}
      </div>

      {/* Line 2 field */}
      <div>
        <label htmlFor="line2" className="block text-xs font-medium text-[#5b5149] mb-1 uppercase tracking-widest">
          Address Line 2 (Optional)
        </label>
        <input
          type="text"
          id="line2"
          name="line2"
          value={formData.line2}
          onChange={handleChange}
          className="w-full px-1 py-2 bg-transparent border-0 border-b-2 border-[#cec5bc] focus:outline-none focus:border-[#745b21]"
          placeholder="Landmark, Area"
        />
      </div>

      {/* City and State fields */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label htmlFor="city" className="block text-xs font-medium text-[#5b5149] mb-1 uppercase tracking-widest">
            City <span className="text-[#ba1a1a]">*</span>
          </label>
          <input
            type="text"
            id="city"
            name="city"
            value={formData.city}
            onChange={handleChange}
            className={`w-full px-1 py-2 bg-transparent border-0 border-b-2 focus:outline-none ${
              errors.city ? 'border-[#ba1a1a]' : 'border-[#cec5bc] focus:border-[#745b21]'
            }`}
            placeholder="City"
          />
          {errors.city && (
            <p className="mt-1 text-sm text-[#ba1a1a]">{errors.city}</p>
          )}
        </div>

        <div>
          <label htmlFor="state" className="block text-xs font-medium text-[#5b5149] mb-1 uppercase tracking-widest">
            State <span className="text-[#ba1a1a]">*</span>
          </label>
          <input
            type="text"
            id="state"
            name="state"
            value={formData.state}
            onChange={handleChange}
            className={`w-full px-1 py-2 bg-transparent border-0 border-b-2 focus:outline-none ${
              errors.state ? 'border-[#ba1a1a]' : 'border-[#cec5bc] focus:border-[#745b21]'
            }`}
            placeholder="State"
          />
          {errors.state && (
            <p className="mt-1 text-sm text-[#ba1a1a]">{errors.state}</p>
          )}
        </div>
      </div>

      {/* Pincode field */}
      <FormField
        label="Pincode"
        name="pincode"
        value={formData.pincode}
        onChange={handleChange}
        error={errors.pincode}
        maxLength={6}
        placeholder="6-digit pincode"
      />

      {/* Default checkbox */}
      <div className="flex items-center">
        <input
          type="checkbox"
          id="is_default"
          name="is_default"
          checked={formData.is_default}
          onChange={handleChange}
          className="w-4 h-4 text-[#745b21] border-[#cec5bc] focus:ring-[#745b21]"
        />
        <label htmlFor="is_default" className="ml-2 text-sm text-[#5b5149]">
          Set as default address
        </label>
      </div>

      {/* Form actions */}
      <div className="flex gap-3 pt-4">
        <button
          type="button"
          onClick={onCancel}
          className="flex-1 border border-[#cec5bc] text-[#5b5149] py-2 font-semibold hover:bg-[#f6f3f0] transition-colors"
          disabled={isLoading}
        >
          Cancel
        </button>
        <button
          type="submit"
          className="flex-1 bg-[#2c2825] text-[#fcf9f6] py-2 font-semibold hover:bg-[#1f1b18] transition-colors disabled:bg-[#948980] disabled:cursor-not-allowed"
          disabled={isLoading}
        >
          {isLoading ? 'Saving...' : address ? 'Update Address' : 'Add Address'}
        </button>
      </div>
    </form>
  );
}
