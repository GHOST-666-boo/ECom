/** @type {import('next').NextConfig} */
const nextConfig = {
  reactCompiler: true,
  images: {
    remotePatterns: [
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '8000',
        pathname: '/storage/**',
      },
      {
        protocol: 'https',
        hostname: '80.225.244.29',
        pathname: '/storage/**',
      },
      {
        protocol: 'http',
        hostname: '80.225.244.29',
        pathname: '/storage/**',
      },
    ],
  },
};

export default nextConfig;
