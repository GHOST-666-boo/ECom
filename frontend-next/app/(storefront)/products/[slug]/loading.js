export default function ProductDetailLoading() {
  return (
    <div className="min-h-[80vh] flex items-center justify-center" style={{ background: '#fcf9f8' }}>
      <div className="text-center">
        <div className="inline-block animate-spin rounded-full h-12 w-12 border-2 border-[#4c3e25] border-t-transparent"></div>
        <p className="mt-4 text-gray-600 font-medium tracking-wide" style={{ fontFamily: 'Manrope, sans-serif' }}>
          Loading object details...
        </p>
      </div>
    </div>
  );
}
