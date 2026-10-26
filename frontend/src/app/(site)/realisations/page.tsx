import { CmsPage, cmsMetadata } from "@/components/site/CmsPage";

export const generateMetadata = () => cmsMetadata("realisations");

export default async function RealisationsPage({ searchParams }: { searchParams: Promise<{ service?: string }> }) {
  return <CmsPage slug="realisations" filter={await searchParams} />;
}
