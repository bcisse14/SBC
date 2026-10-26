import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  allowedDevOrigins: ["localhost:8080", "127.0.0.1:8080"],
  async rewrites() {
    const apiOrigin = process.env.API_PROXY_TARGET || "http://localhost:8080";

    return [
      {
        source: "/api/:path*",
        destination: `${apiOrigin}/api/:path*`,
      },
    ];
  },
};

export default nextConfig;
