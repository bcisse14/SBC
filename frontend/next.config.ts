import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  allowedDevOrigins: ["localhost:8080", "127.0.0.1:8080"],
  async rewrites() {
    if (process.env.NEXT_PUBLIC_DEMO_MODE === "1") {
      const demo = (await import("./src/data/demo-site.json")).default;
      return Object.entries(demo.media).map(([id, media]) => ({
        source: `/api/media/${id}`,
        destination: media.demoPath,
      }));
    }
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
