import { CmsPage, cmsMetadata } from "@/components/site/CmsPage";

export const generateMetadata = () => cmsMetadata("mentions-legales");

export default function LegalPage() {
  return <CmsPage slug="mentions-legales" />;
}
