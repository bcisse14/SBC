"use client";

import { useState } from "react";
import { siteText, type SiteSettings } from "@/lib/site-customization";

export function MapReveal({ settings = {} }: { settings?: SiteSettings }) {
  const [visible, setVisible] = useState(false);

  return (
    <div className="mt-4">
      {!visible ? (
        <button className="btn-secondary" type="button" onClick={() => setVisible(true)}>
          {siteText(settings, "map_button", "Afficher la carte")}
        </button>
      ) : (
        <iframe
          title={siteText(settings, "map_title", "Carte de la région parisienne")}
          className="h-72 w-full border border-[var(--beton)]"
          loading="lazy"
          src={settings.map_url || "https://www.openstreetmap.org/export/embed.html?bbox=1.45%2C48.55%2C3.05%2C49.15&layer=mapnik"}
        />
      )}
    </div>
  );
}
