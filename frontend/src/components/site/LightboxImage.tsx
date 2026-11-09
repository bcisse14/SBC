"use client";

import { useState } from "react";
import { ResponsiveImage } from "@/components/site/ResponsiveImage";
import type { MediaInfo } from "@/lib/types";

export function LightboxImage({ media, alt, sizes, className }: {
  media: MediaInfo;
  alt?: string;
  sizes: string;
  className?: string;
}) {
  const [open, setOpen] = useState(false);
  return (
    <>
      <button type="button" className="image-lightbox-trigger" aria-label={`Agrandir : ${alt ?? media.alt}`} onClick={() => setOpen(true)}>
        <ResponsiveImage media={media} sizes={sizes} priority className={className} />
      </button>
      {open ? (
        <dialog className="project-lightbox image-lightbox" open aria-label={alt ?? media.alt} onClick={(event) => {
          if (event.target === event.currentTarget) setOpen(false);
        }}>
          <div className="project-lightbox-layout">
            <header>
              <p>{alt ?? media.alt}</p>
              <button type="button" onClick={() => setOpen(false)}>Fermer ✕</button>
            </header>
            <div className="project-lightbox-image">
              <ResponsiveImage media={media} sizes="(min-width: 1024px) 75vw, 100vw" className="h-full w-full object-contain" priority />
            </div>
          </div>
        </dialog>
      ) : null}
    </>
  );
}
