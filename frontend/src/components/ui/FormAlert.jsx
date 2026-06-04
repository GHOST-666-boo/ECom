/**
 * Reusable alert banner for form success/error messages.
 * Matches the styling used across LoginForm, RegisterForm, ForgotPasswordForm, AddressForm.
 */
export default function FormAlert({ type, message }) {
  if (!message) return null;

  const styles =
    type === 'success'
      ? 'bg-[#e9f4ec] text-[#1b5e20]'
      : 'bg-[#fdeceb] text-[#ba1a1a]';

  return <div className={`px-4 py-3 ${styles}`}>{message}</div>;
}
