import { CmsPage, cmsMetadata } from "@/components/site/CmsPage";

export const generateMetadata = () => cmsMetadata("contact");

export default async function ContactPage({ searchParams }: { searchParams: Promise<{ service?: string }> }) {
  return <CmsPage slug="contact" filter={await searchParams} />;
}
