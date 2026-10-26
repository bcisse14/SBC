import { CmsPage, cmsMetadata } from "@/components/site/CmsPage";

export const generateMetadata = () => cmsMetadata("");

export default function HomePage() {
  return <CmsPage slug="" />;
}
