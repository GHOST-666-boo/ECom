/**
 * Reusable form submit button matching the primary CTA style
 * used across LoginForm, RegisterForm, ForgotPasswordForm, AddressForm.
 */
export default function SubmitButton({ isLoading, disabled, loadingText, children }) {
  return (
    <button
      type="submit"
      disabled={isLoading || disabled}
      className="w-full bg-[#2c2825] hover:bg-[#1f1b18] text-[#fcf9f6] font-semibold py-2 px-4 transition-colors disabled:bg-[#948980] disabled:cursor-not-allowed"
    >
      {isLoading ? loadingText : children}
    </button>
  );
}
