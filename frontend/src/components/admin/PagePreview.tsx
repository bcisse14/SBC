"use client";

import { useEffect, useRef, useState, type ReactNode } from "react";
import { createPortal } from "react-dom";
import { CmsSections } from "@/components/site/CmsSections";
import type { AdminMedia, CmsSection } from "@/lib/cms-types";
import type { SiteBootstrap } from "@/lib/types";
import { adminRequest, errorMessage } from "@/lib/admin-api";
import type { SiteSettings } from "@/lib/site-customization";
import { SiteFrame } from "@/components/site/SiteFrame";

export function PagePreview({ sections, media, onClose, settingsOverride, homeOverride }: {
  sections: CmsSection[];
  media: AdminMedia[];
  onClose: () => void;
  settingsOverride?: SiteSettings;
  homeOverride?: boolean;
}) {
  const dialog = useRef<HTMLDialogElement>(null);
  const [data, setData] = useState<SiteBootstrap | null>(null);
  const [error, setError] = useState("");
  const [attempt, setAttempt] = useState(0);
  const [viewport, setViewport] = useState("desktop");
  useEffect(() => { dialog.current?.showModal(); }, []);
  useEffect(() => {
    let active = true;
    adminRequest<SiteBootstrap>("/api/site/bootstrap").then((data) => {
      if (active) { setData(data); setError(""); }
    }).catch((error) => { if (active) setError(errorMessage(error)); });
    return () => { active = false; };
  }, [attempt]);
  const previewData: SiteBootstrap | null = data ? {
    ...data,
    settings: { ...data.settings, ...settingsOverride },
    media: {
      ...Object.fromEntries(media.map((asset) => [asset.id, {
        id: asset.id, alt: asset.altText ?? "", width: asset.width, height: asset.height, focalPoint: null, variants: [],
      }])),
      ...data.media,
    },
  } : null;
  const home = homeOverride ?? Boolean(data?.pages.some((page) => page.slug === "" && page.sections.some((section) => section.id === sections[0]?.id)));
  return <dialog ref={dialog} className="cms-dialog cms-preview" onCancel={onClose} aria-labelledby="page-preview-title">
    <header className="sticky top-0 z-20 flex flex-wrap items-center justify-between gap-3 border-b border-[var(--beton)] bg-[var(--papier)] p-5">
      <div><h2 id="page-preview-title" className="font-serif text-3xl">Aperçu de votre page</h2>
        <p className="mt-1 text-xs text-gray-500">Modifications en cours, sans publication. Les sections masquées ne sont pas affichées. Les formulaires et liens sont désactivés.</p></div>
      <div className="flex gap-2"><button type="button" className="btn-secondary" aria-pressed={viewport === "desktop"} onClick={() => setViewport("desktop")}>Ordinateur</button>
        <button type="button" className="btn-secondary" aria-pressed={viewport === "mobile"} onClick={() => setViewport("mobile")}>Mobile</button>
        <button type="button" className="btn-secondary" onClick={onClose}>Fermer l’aperçu</button></div>
    </header>
    {error ? <div className="cms-error m-5" role="alert">{error}<button className="ml-3 underline" onClick={() => setAttempt(attempt + 1)}>Réessayer</button></div>
      : previewData ? <PreviewCanvas mobile={viewport === "mobile"}><div inert><SiteFrame data={previewData}><main className={home ? "site-home" : "site-page"}>
        <CmsSections sections={sections} data={previewData} home={home} />
      </main></SiteFrame></div></PreviewCanvas>
        : <p className="cms-empty" role="status">Préparation de l’aperçu…</p>}
  </dialog>;
}

function PreviewCanvas({ mobile, children }: { mobile: boolean; children: ReactNode }) {
  const [body, setBody] = useState<HTMLElement | null>(null);
  return <div className="cms-preview-frame-wrap">
    <iframe srcDoc="<!doctype html><html lang='fr'><head></head><body></body></html>" title={mobile ? "Aperçu mobile de la page" : "Aperçu ordinateur de la page"} className={`cms-preview-frame ${mobile ? "is-mobile" : ""}`} onLoad={(event) => {
      const doc = event.currentTarget.contentDocument;
      if (!doc) return;
      doc.documentElement.lang = "fr";
      document.querySelectorAll('link[rel="stylesheet"], style').forEach((node) => doc.head.appendChild(node.cloneNode(true)));
      doc.body.style.margin = "0";
      setBody(doc.body);
    }} />
    {body ? createPortal(children, body) : null}
  </div>;
}
