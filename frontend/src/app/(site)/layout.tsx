import type { ReactNode } from "react";
import { getSiteBootstrap } from "@/lib/api";
import { SiteFrame } from "@/components/site/SiteFrame";

export async function generateMetadata() {
  const data = await getSiteBootstrap();
  const name = data.settings.company_name ?? "SBC";
  return {
    title: { default: name, template: `%s | ${name}` },
    description: data.settings.seo_description || undefined,
    openGraph: { title: name, description: data.settings.seo_description || undefined },
    icons: data.settings.brand_icon_id ? { icon: `/api/media/${data.settings.brand_icon_id}` } : undefined,
  };
}

export default async function SiteLayout({ children }: { children: ReactNode }) {
  const data = await getSiteBootstrap();
  return <SiteFrame data={data}>{children}</SiteFrame>;
}
