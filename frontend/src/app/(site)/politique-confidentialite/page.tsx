import { CmsPage, cmsMetadata } from "@/components/site/CmsPage";

export const generateMetadata = () => cmsMetadata("politique-confidentialite");

export default function PrivacyPage() {
  return <CmsPage slug="politique-confidentialite" />;
}
