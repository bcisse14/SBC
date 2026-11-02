import Link from "next/link";
import type { ReactNode } from "react";
import { ResponsiveImage } from "@/components/site/ResponsiveImage";
import type { RealisationCategory, SiteBootstrap } from "@/lib/types";

export type DescriptionBlock = { type: "heading" | "paragraph"; text: string } | { type: "list"; items: string[] };

export function parseDescription(source: string): DescriptionBlock[] {
  const blocks: DescriptionBlock[] = [];
  let paragraph: string[] = [];
  const flush = () => {
    if (paragraph.length) blocks.push({ type: "paragraph", text: paragraph.join(" ") });
    paragraph = [];
  };
  for (const raw of source.replace(/\r\n?/g, "\n").split("\n")) {
    const line = raw.trim();
    if (!line) { flush(); continue; }
    if (line.startsWith("## ")) { flush(); blocks.push({ type: "heading", text: line.slice(3).trim() }); continue; }
    if (/^[-•] /.test(line)) {
      flush();
      const last = blocks[blocks.length - 1];
      if (last?.type === "list") last.items.push(line.slice(2).trim());
      else blocks.push({ type: "list", items: [line.slice(2).trim()] });
      continue;
    }
    paragraph.push(line);
  }
  flush();
  return blocks;
}

export function photosOf(category: RealisationCategory, data: SiteBootstrap) {
  return category.gallery.flatMap((id) => data.media[String(id)] ? [data.media[String(id)]] : []);
}

export function PhotoPlaceholder({ label = "Photo à venir", hint, className = "" }: { label?: string; hint?: ReactNode; className?: string }) {
  return (
    <div className={`photo-placeholder ${className}`} role="img" aria-label={`Emplacement photo : ${label}`}>
      <svg viewBox="0 0 48 48" width="44" height="44" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <rect x="5" y="12" width="38" height="28" rx="4" />
        <path d="M17 12l2.5-4h9L31 12" />
        <circle cx="24" cy="26" r="7" />
      </svg>
      <strong>{label}</strong>
      {hint ? <span>{hint}</span> : null}
    </div>
  );
}

export function ExpertiseCard({ category, index, data }: { category: RealisationCategory; index: number; data: SiteBootstrap }) {
  const cover = photosOf(category, data)[0];
  return (
    <Link className="expertise-card" href={`/services/${category.slug}`}>
      <div className="expertise-card-media">
        {cover ? <ResponsiveImage media={cover} sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw" className="expertise-card-image" />
          : <PhotoPlaceholder hint="Photo du chantier" />}
      </div>
      <div className="expertise-card-copy">
        <span className="site-card-index">{String(index + 1).padStart(2, "0")} / Expertise</span>
        <h3>{category.title}</h3>
        {category.summary ? <p>{category.summary}</p> : null}
        <span className="expertise-card-link">Découvrir l’expertise <span aria-hidden="true">↗</span></span>
      </div>
    </Link>
  );
}
