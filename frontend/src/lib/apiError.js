/**
 * Extracts a normalized error object from an Axios error response.
 *
 * Backend returns either:
 *   { errors: { field: [...messages] } }  — validation errors
 *   { message: "..." }                    — general error
 *
 * @param {Error} error - Axios error
 * @param {string} fallbackMessage - Default message if nothing else is available
 * @returns {{ fieldErrors: object|null, general: string|null }}
 */
export function parseApiError(error, fallbackMessage = 'Something went wrong. Please try again.') {
  const data = error.response?.data;

  if (data?.errors) {
    return { fieldErrors: data.errors, general: null };
  }

  return { fieldErrors: null, general: data?.message || fallbackMessage };
}

/**
 * Sets form errors state from an Axios error using the standard pattern
 * found across all form components.
 *
 * @param {Error} error - Axios error
 * @param {Function} setErrors - React state setter for errors
 * @param {string} fallbackMessage - Default message
 */
export function setApiErrors(error, setErrors, fallbackMessage = 'Something went wrong. Please try again.') {
  const { fieldErrors, general } = parseApiError(error, fallbackMessage);

  if (fieldErrors) {
    setErrors(fieldErrors);
  } else {
    setErrors({ general });
  }
}
