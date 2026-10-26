import { CmsPage, cmsMetadata } from "@/components/site/CmsPage";

export const generateMetadata = () => cmsMetadata("services");

export default async function ServicesPage({ searchParams }: { searchParams: Promise<{ filtre?: string }> }) {
  return <CmsPage slug="services" filter={await searchParams} />;
}
