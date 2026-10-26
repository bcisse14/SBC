import { CmsPage, cmsMetadata } from "@/components/site/CmsPage";

type Props = { params: Promise<{ slug: string }>; searchParams: Promise<{ service?: string; filtre?: string }> };

export async function generateMetadata({ params }: Props) {
  return cmsMetadata((await params).slug);
}

export default async function CustomPage({ params, searchParams }: Props) {
  return <CmsPage slug={(await params).slug} filter={await searchParams} />;
}
