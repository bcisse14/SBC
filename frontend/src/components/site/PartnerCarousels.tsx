"use client";

import { useEffect, useState } from "react";
import type { SiteBootstrap } from "@/lib/types";
import { ResponsiveImage } from "@/components/site/ResponsiveImage";

type Partner = SiteBootstrap["partners"][number];

export function PartnerCarousels({ partners, media }: Pick<SiteBootstrap, "partners" | "media">) {
  const [paused, setPaused] = useState(false);
  const [reducedMotion, setReducedMotion] = useState(true);
  useEffect(() => {
    const preference = matchMedia("(prefers-reduced-motion: reduce)");
    const update = () => setReducedMotion(preference.matches);
    update();
    preference.addEventListener("change", update);
    return () => preference.removeEventListener("change", update);
  }, []);
  const groups = new Map<string, Partner[]>();
  for (const partner of partners) {
    const category = partner.category?.trim() || "Partenaires";
    groups.set(category, [...(groups.get(category) ?? []), partner]);
  }
  return <div className="site-section-content site-partner-carousels">
    <div className="site-partner-carousel-grid">
      {[...groups].map(([category, items], index) => <PartnerCarousel key={category} category={category} items={items}
        media={media} paused={paused || reducedMotion} delay={4200 + index * 600} />)}
    </div>
    {!reducedMotion && [...groups.values()].some((items) => items.length > 1) ? <button type="button"
      className="site-partner-pause" aria-pressed={paused} onClick={() => setPaused(!paused)}>
      {paused ? "Reprendre le défilement" : "Mettre le défilement en pause"}
    </button> : null}
  </div>;
}

function PartnerCarousel({ category, items, media, paused, delay }: {
  category: string; items: Partner[]; media: SiteBootstrap["media"]; paused: boolean; delay: number;
}) {
  const [index, setIndex] = useState(0);
  const [hovered, setHovered] = useState(false);
  const [focused, setFocused] = useState(false);
  const activeIndex = index % items.length;
  useEffect(() => {
    if (paused || hovered || focused || items.length < 2) return;
    const timer = setInterval(() => {
      if (!document.hidden) setIndex((current) => (current + 1) % items.length);
    }, delay);
    return () => clearInterval(timer);
  }, [paused, hovered, focused, items.length, delay]);
  return <div className="site-partner-carousel" role="group" aria-roledescription="carrousel" aria-label={category}
    onMouseEnter={() => setHovered(true)} onMouseLeave={() => setHovered(false)}
    onFocusCapture={() => setFocused(true)} onBlurCapture={(event) => {
      if (!event.currentTarget.contains(event.relatedTarget)) setFocused(false);
    }}>
    <div className="site-partner-window">
      <div className="site-partner-track" style={{ transform: `translateX(-${activeIndex * 100}%)` }}>
        {items.map((item, slideIndex) => {
          const logo = item.logoMediaId ? media[String(item.logoMediaId)] : null;
          const content = <>{logo ? <ResponsiveImage media={logo} alt={item.name} sizes="(min-width: 1024px) 220px, 50vw" className="site-partner-logo" />
            : <><span className="site-partner-placeholder" aria-hidden="true"><svg viewBox="0 0 40 32" fill="none"><rect x="1" y="1" width="38" height="30" rx="4" /><circle cx="28" cy="10" r="3" /><path d="m3 27 10-12 9 9 5-5 10 9" /></svg></span><span className="site-partner-name">{item.name}</span></>}</>;
          return <div key={slideIndex} className="site-partner-slide" role="group" aria-roledescription="diapositive"
            aria-label={`${slideIndex + 1} sur ${items.length}`} aria-hidden={slideIndex !== activeIndex} inert={slideIndex !== activeIndex}>
            {item.websiteUrl ? <a href={item.websiteUrl}>{content}</a> : <div>{content}</div>}
          </div>;
        })}
      </div>
    </div>
    {items.length > 1 ? <div className="site-partner-arrows">
      <button type="button" aria-label={`Partenaire précédent : ${category}`} onClick={() => setIndex((activeIndex + items.length - 1) % items.length)}>‹</button>
      <button type="button" aria-label={`Partenaire suivant : ${category}`} onClick={() => setIndex((activeIndex + 1) % items.length)}>›</button>
    </div> : null}
    <h3>{category}</h3>
    <div className="site-partner-dots" aria-hidden="true">{items.map((_, dot) => <span key={dot} className={dot === activeIndex ? "is-active" : ""} />)}</div>
  </div>;
}
