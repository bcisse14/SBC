"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { ResponsiveImage } from "@/components/site/ResponsiveImage";
import type { MediaInfo } from "@/lib/types";
import { siteText, type SiteSettings } from "@/lib/site-customization";

export function CategoryGallery({ title, photos, settings = {} }: { title: string; photos: MediaInfo[]; settings?: SiteSettings }) {
  const [openIndex, setOpenIndex] = useState<number | null>(null);
  const dialog = useRef<HTMLDialogElement>(null);
  const rail = useRef<HTMLUListElement>(null);
  const opener = useRef<HTMLButtonElement | null>(null);

  const close = useCallback(() => {
    dialog.current?.close();
    setOpenIndex(null);
    opener.current?.focus();
  }, []);

  const step = (delta: number) => {
    setOpenIndex((index) => index === null ? null : (index + delta + photos.length) % photos.length);
  };

  useEffect(() => {
    if (openIndex === null) return;
    if (!dialog.current?.open) dialog.current?.showModal();
    const overflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => { document.body.style.overflow = overflow; };
  }, [openIndex]);

  if (!photos.length) return null;
  const current = openIndex === null ? null : photos[openIndex];

  return (
    <div className={`project-gallery ${photos.length === 1 ? "project-gallery--single" : photos.length === 2 ? "project-gallery--pair" : ""}`}>
      <ul ref={rail} className="project-gallery-rail" aria-label={`Photos : ${title}`}>
        {photos.map((photo, index) => (
          <li key={photo.id}>
            <button
              type="button"
              className="project-gallery-photo"
              aria-label={`Agrandir la photo ${index + 1} sur ${photos.length} : ${title}`}
              onClick={(event) => {
                opener.current = event.currentTarget;
                setOpenIndex(index);
              }}
            >
              <ResponsiveImage media={photo} sizes="(min-width: 1024px) 300px, (min-width: 640px) 45vw, 80vw" className="project-gallery-image" />
              <span className="project-gallery-zoom" aria-hidden="true">{siteText(settings, "gallery_zoom", "Agrandir")} ↗</span>
            </button>
          </li>
        ))}
      </ul>
      {photos.length > 1 ? <div className="project-gallery-controls">
        <p>{siteText(settings, "gallery_scroll", "Faites défiler les {count} photos").replace("{count}", String(photos.length))}</p>
        <div>
          <button type="button" aria-label={`Défiler vers la photo précédente : ${title}`} onClick={() => rail.current?.scrollBy({ left: -rail.current.clientWidth })}>←</button>
          <button type="button" aria-label={`Défiler vers la photo suivante : ${title}`} onClick={() => rail.current?.scrollBy({ left: rail.current.clientWidth })}>→</button>
        </div>
      </div> : null}

      {current ? (
        <dialog
          ref={dialog}
          className="project-lightbox"
          aria-label={`${title}, photo ${(openIndex ?? 0) + 1} sur ${photos.length}`}
          onCancel={(event) => { event.preventDefault(); close(); }}
          onClick={(event) => { if (event.target === event.currentTarget) close(); }}
          onKeyDown={(event) => {
            if (event.key === "Escape") { event.preventDefault(); close(); }
            if (event.key === "ArrowRight") { event.preventDefault(); step(1); }
            if (event.key === "ArrowLeft") { event.preventDefault(); step(-1); }
          }}
        >
          <div className="project-lightbox-layout">
            <header>
              <p>{title} <span>{(openIndex ?? 0) + 1} / {photos.length}</span></p>
              <button type="button" onClick={close} autoFocus>{siteText(settings, "gallery_close", "Fermer")} ✕</button>
            </header>
            <div className="project-lightbox-image">
              <ResponsiveImage media={current} sizes="(min-width: 1024px) 75vw, 100vw" className="h-full w-full object-contain" priority />
            </div>
            <footer>
              <p>{current.alt}</p>
              {photos.length > 1 ? <div>
                <button type="button" aria-label={siteText(settings, "gallery_previous", "Photo précédente")} onClick={() => step(-1)}>←</button>
                <button type="button" aria-label={siteText(settings, "gallery_next", "Photo suivante")} onClick={() => step(1)}>→</button>
              </div> : null}
            </footer>
          </div>
        </dialog>
      ) : null}
    </div>
  );
}
