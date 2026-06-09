import { useState } from 'react';

/**
 * Custom hook encapsulating the form state pattern used across
 * LoginForm, RegisterForm, ForgotPasswordForm, ResetPasswordForm, AddressForm, ProfilePage.
 *
 * Provides: formData, errors, isLoading, successMessage, and handlers.
 *
 * @param {object} initialData - Initial form field values
 */
export default function useFormState(initialData) {
  const [formData, setFormData] = useState(initialData);
  const [errors, setErrors] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));
    if (errors[name]) {
      setErrors((prev) => {
        const next = { ...prev };
        delete next[name];
        return next;
      });
    }
  };

  const resetForm = (data) => {
    setFormData(data || initialData);
    setErrors({});
    setSuccessMessage('');
  };

  return {
    formData,
    setFormData,
    errors,
    setErrors,
    isLoading,
    setIsLoading,
    successMessage,
    setSuccessMessage,
    handleChange,
    resetForm,
  };
}
