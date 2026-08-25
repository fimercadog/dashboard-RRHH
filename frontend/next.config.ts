import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  devIndicators: false,
  allowedDevOrigins: ["*.ngrok-free.dev", "*.ngrok-free.app"],
  async rewrites() {
    return [{ source: "/api/:path*", destination: "http://127.0.0.1:8001/api/:path*" }];
  },
};

export default nextConfig;
