import { CmsPage, cmsMetadata } from "@/components/site/CmsPage";

export const generateMetadata = () => cmsMetadata("a-propos");

export default function AboutPage() {
  return <CmsPage slug="a-propos" />;
}
