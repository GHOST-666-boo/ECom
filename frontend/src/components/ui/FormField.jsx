/**
 * Reusable form field with label, input, and inline error display.
 * Matches the underline-input style used across auth and address forms.
 */
export default function FormField({
  label,
  name,
  type = 'text',
  value,
  onChange,
  error,
  required = false,
  placeholder,
  readOnly = false,
  disabled = false,
  maxLength,
}) {
  const errorValue = Array.isArray(error) ? error[0] : error;
  const hasError = !!errorValue;

  return (
    <div>
      <label
        htmlFor={name}
        className="block text-sm font-medium text-[#5b5149] mb-1"
      >
        {label}
      </label>
      <input
        type={type}
        id={name}
        name={name}
        value={value}
        onChange={onChange}
        readOnly={readOnly}
        disabled={disabled}
        maxLength={maxLength}
        placeholder={placeholder}
        className={`w-full px-1 py-2 bg-transparent border-0 border-b-2 focus:outline-none ${
          hasError
            ? 'border-[#ba1a1a]'
            : 'border-[#cec5bc] focus:border-[#745b21]'
        }`}
        required={required}
      />
      {hasError && (
        <p className="mt-1 text-sm text-[#ba1a1a]">{errorValue}</p>
      )}
    </div>
  );
}
