import { notFound } from "next/navigation";
import { getSiteBootstrap } from "@/lib/api";
import { CmsSections } from "@/components/site/CmsSections";

export async function CmsPage({ slug, filter }: { slug: string; filter?: { service?: string; filtre?: string } }) {
  const data = await getSiteBootstrap();
  const page = data.pages.find((page) => page.slug === slug);
  if (!page) notFound();
  return <main id="main-content" tabIndex={-1} className={slug === "" ? "site-home" : "site-page"}><CmsSections sections={page.sections} pageTitle={page.title} data={data} filter={filter} home={slug === ""} /></main>;
}

export async function cmsMetadata(slug: string) {
  const data = await getSiteBootstrap();
  const page = data.pages.find((page) => page.slug === slug);
  return page ? { title: page.title, description: page.seoDescription || undefined } : {};
}
