import type { MetadataRoute } from "next";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: {
      userAgent: "*",
      allow: "/",
      ...(process.env.NEXT_PUBLIC_DEMO_MODE === "1" ? { disallow: "/" } : {}),
    },
    sitemap: "/sitemap.xml",
  };
}
