/**
 * "Or continue with" divider used before Google sign-in buttons
 * in LoginForm and RegisterForm.
 */
export default function GoogleDivider() {
  return (
    <div className="relative my-6">
      <div className="absolute inset-0 flex items-center">
        <div className="w-full h-px bg-[#cec5bc]/30"></div>
      </div>
      <div className="relative flex justify-center text-sm">
        <span className="px-2 bg-[#fcf9f6] text-[#7a6d63]">Or continue with</span>
      </div>
    </div>
  );
}
