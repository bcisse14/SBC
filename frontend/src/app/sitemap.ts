import type { MetadataRoute } from "next";
import { getSiteBootstrap } from "@/lib/api";

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const base = process.env.NEXT_PUBLIC_SITE_URL || "http://localhost:3000";
  const data = await getSiteBootstrap();

  return [
    ...data.pages.map((page) => ({
      url: `${base}/${page.slug}`,
      lastModified: new Date(),
      changeFrequency: "weekly" as const,
      priority: page.slug === "" ? 1 : 0.8,
    })),
    ...data.realisationCategories.map((category) => ({
      url: `${base}/services/${category.slug}`,
      lastModified: new Date(),
      changeFrequency: "monthly" as const,
      priority: 0.7,
    })),
  ];
}
