"use client";

import { useState } from "react";
import type { AdminMedia, CmsSchema, CmsSection, CmsSectionType } from "@/lib/cms-types";
import { moveItem } from "@/lib/admin-api";
import { PhotoField } from "@/components/admin/MediaPicker";

const collectionModules: Partial<Record<CmsSectionType, string>> = {
  services: "services", realisations: "realisations", reviews: "reviews", faq: "faq",
  partners: "partners", certifications: "certifications", announcements: "announcements",
};
const descriptions: Record<CmsSectionType, string> = {
  hero: "Un grand titre, une introduction et une photo pour accueillir vos visiteurs.",
  text: "Un texte avec, si vous le souhaitez, une image et un bouton.",
  gallery: "Une sélection de photos qui s’agrandissent au clic.",
  cards: "Des cartes ou des étapes pour expliquer votre offre et votre méthode.",
  services: "Vos prestations publiées, regroupées automatiquement par famille.",
  realisations: "Vos projets et leurs photos, depuis le module Réalisations.",
  reviews: "Les témoignages publiés dans Avis clients.",
  faq: "Vos questions et réponses, sous forme de volets dépliants.",
  partners: "Des carrousels de logos regroupés par catégorie, depuis le module Partenaires.",
  certifications: "Vos qualifications et liens de vérification.",
  quote: "Le formulaire de demande de devis et ses étapes guidées.",
  contact: "Vos coordonnées et une carte chargée à la demande.",
  announcements: "Vos actualités, offres et informations en cours de diffusion.",
};
const layoutOptions = {
  layout: { label: "Disposition", options: { auto: "Automatique (recommandé)", split: "Texte et photo côte à côte", "image-left": "Photo à gauche", stacked: "Texte puis photo" } },
  tone: { label: "Fond", options: { default: "Fond principal", soft: "Fond secondaire", dark: "Fond sombre" } },
  spacing: { label: "Espacement vertical", options: { default: "Suivre le réglage du site", compact: "Compact", airy: "Aéré" } },
  alignment: { label: "Alignement du texte", options: { left: "À gauche", center: "Centré" } },
  width: { label: "Largeur", options: { full: "Largeur du site", narrow: "Lecture resserrée" } },
  columns: { label: "Colonnes sur ordinateur", options: { auto: "Automatique", "1": "1 colonne", "2": "2 colonnes", "3": "3 colonnes", "4": "4 colonnes" } },
  imageFit: { label: "Cadrage de la photo", options: { cover: "Remplir le cadre", contain: "Conserver la photo entière" } },
};

export function BlocksEditor({ sections, onChange, schema, media, upload, navigate }: {
  sections: CmsSection[]; onChange: (sections: CmsSection[]) => void; schema: CmsSchema; media: AdminMedia[];
  upload: (files: FileList) => Promise<AdminMedia[]>; navigate?: (module: string) => void;
}) {
  const [openId, setOpenId] = useState<string | null>(null);
  const [tab, setTab] = useState("content");
  const [adding, setAdding] = useState(false);
  const update = (id: string, patch: Partial<CmsSection>) => onChange(sections.map((section) => section.id === id ? { ...section, ...patch } : section));
  function add(type: CmsSectionType) {
    const section: CmsSection = {
      id: crypto.randomUUID(), type, title: "", label: "", body: "", hidden: false,
      linkLabel: "", linkUrl: "", mediaAssetId: null, photoSlotKey: "", gallery: [], cards: [],
    };
    onChange([...sections, section]); setOpenId(section.id); setTab("content"); setAdding(false);
  }
  return <div className="cms-composer">
    <div className="cms-composer-intro"><div><h2 className="cms-form-title">Les sections de votre page</h2>
      <p className="cms-description">Dans l’ordre où vos visiteurs les découvrent. Ouvrez une section pour modifier son contenu ou son apparence.</p></div><span className="cms-badge">{sections.length} / 60</span></div>
    {!sections.length ? <p className="cms-empty">Cette page est vide. Ajoutez une première section ci-dessous.</p> : null}
    {sections.map((section, index) => {
      const expanded = openId === section.id;
      const collection = collectionModules[section.type];
      const visual = section.type === "hero" || section.type === "text";
      const columns = ["cards", "services", "realisations", "reviews", "partners", "certifications", "announcements"].includes(section.type);
      const presentations = section.type === "cards" ? { auto: "Automatique", cards: "Cartes", steps: "Étapes de méthode" }
        : section.type === "services" ? { auto: "Automatique", families: "Trois familles résumées", catalog: "Liste complète des prestations" }
          : section.type === "realisations" ? { auto: "Automatique", galleries: "Projets avec galeries" } : null;
      return <article key={section.id} className={`cms-block ${expanded ? "is-expanded" : ""} ${section.hidden ? "is-hidden" : ""}`}>
        <header className="cms-block-header">
          <span className="cms-order">{String(index + 1).padStart(2, "0")}</span>
          <button type="button" className="cms-block-open" aria-expanded={expanded} onClick={() => { setOpenId(expanded ? null : section.id); setTab("content"); }}>
            <span className="cms-block-type">{schema.blocks[section.type]}{section.hidden ? " · Masquée" : ""}</span>
            <strong>{section.title || (section.type === "text" ? section.body.slice(0, 55) : "") || schema.blocks[section.type]}</strong>
          </button>
          <div className="cms-block-actions">
            <button type="button" className="cms-icon-btn" aria-label={`Monter la section ${index + 1}`} disabled={index === 0} onClick={() => onChange(moveItem(sections, index, index - 1))}>↑</button>
            <button type="button" className="cms-icon-btn" aria-label={`Descendre la section ${index + 1}`} disabled={index === sections.length - 1} onClick={() => onChange(moveItem(sections, index, index + 1))}>↓</button>
            <button type="button" className="cms-icon-btn" aria-label={`Dupliquer la section ${index + 1}`} disabled={sections.length >= 60} onClick={() => {
              const copy = structuredClone(section); copy.id = crypto.randomUUID();
              onChange([...sections.slice(0, index + 1), copy, ...sections.slice(index + 1)]); setOpenId(copy.id);
            }}>Dupliquer</button>
          </div>
        </header>
        {expanded ? <div className="cms-block-inspector">
          <div className="cms-tabs" role="tablist" aria-label={`Édition de la section ${index + 1}`}>
            <button type="button" role="tab" aria-selected={tab === "content"} onClick={() => setTab("content")}>Contenu</button>
            <button type="button" role="tab" aria-selected={tab === "design"} onClick={() => setTab("design")}>Mise en page</button>
          </div>
          {tab === "content" ? <div className="cms-block-fields">
            {collection ? <div className="cms-context-note"><p>{descriptions[section.type]}</p>
              {navigate ? <button type="button" className="cms-reset-link" onClick={() => navigate(collection)}>Gérer les éléments de « {schema.blocks[section.type]} » ↗</button> : null}</div> : null}
            <label className="field-label">Titre de la section<input className="field-input" placeholder="Un titre clair et court" value={section.title} onChange={(event) => update(section.id, { title: event.target.value })} /></label>
            <label className="field-label">Texte d’introduction<textarea className="field-input min-h-28" value={section.body} onChange={(event) => update(section.id, { body: event.target.value })} /></label>
            <details className="cms-details"><summary>Petit repère au-dessus du titre</summary><input aria-label="Petit repère" className="field-input mt-3" value={section.label} onChange={(event) => update(section.id, { label: event.target.value })} /></details>
            {visual ? <div className="cms-field-group"><h3>Photo</h3>
              <label className="cms-toggle-field mb-4"><input type="checkbox" checked={!section.hideImage} onChange={(event) => update(section.id, { hideImage: !event.target.checked })} /><span>Afficher une photo dans cette section</span></label>
              <PhotoField media={media} upload={upload} value={section.mediaAssetId ? [section.mediaAssetId] : []} onChange={(ids) => update(section.id, { mediaAssetId: ids[0] ?? null, photoSlotKey: "" })} />
              <details className="cms-details mt-4"><summary>Utiliser une photo partagée entre plusieurs pages</summary>
                <select aria-label="Emplacement photo partagé" className="field-input mt-3" value={section.photoSlotKey ?? ""} onChange={(event) => update(section.id, { photoSlotKey: event.target.value, mediaAssetId: null })}>
                  <option value="">Aucun emplacement partagé</option>
                  {schema.photoSlots.map((slot) => <option key={slot.slotKey} value={slot.slotKey}>{slot.idealDescription}</option>)}
                </select>
              </details>
            </div> : null}
            {section.type === "gallery" ? <PhotoField media={media} upload={upload} value={section.gallery} multiple onChange={(gallery) => update(section.id, { gallery })} /> : null}
            {section.type === "cards" ? <div className="cms-field-group"><h3>Cartes et étapes</h3>
              {section.cards.map((card, cardIndex) => <div key={cardIndex} className="cms-card-editor">
                <div className="flex flex-wrap items-center gap-2"><strong className="mr-auto text-sm">Carte {cardIndex + 1}</strong>
                  <button type="button" className="cms-icon-btn" aria-label="Monter la carte" disabled={!cardIndex} onClick={() => update(section.id, { cards: moveItem(section.cards, cardIndex, cardIndex - 1) })}>↑</button>
                  <button type="button" className="cms-icon-btn" aria-label="Descendre la carte" disabled={cardIndex === section.cards.length - 1} onClick={() => update(section.id, { cards: moveItem(section.cards, cardIndex, cardIndex + 1) })}>↓</button>
                  <button type="button" className="cms-danger-link" onClick={() => update(section.id, { cards: section.cards.filter((_, index) => index !== cardIndex) })}>Retirer</button>
                </div>
                <label className="field-label">Titre<input className="field-input" value={card.title} onChange={(event) => update(section.id, { cards: section.cards.map((item, index) => index === cardIndex ? { ...item, title: event.target.value } : item) })} /></label>
                <label className="field-label">Texte<textarea className="field-input" value={card.body} onChange={(event) => update(section.id, { cards: section.cards.map((item, index) => index === cardIndex ? { ...item, body: event.target.value } : item) })} /></label>
              </div>)}
              <button type="button" className="btn-secondary" disabled={section.cards.length >= 30} onClick={() => update(section.id, { cards: [...section.cards, { title: "", body: "" }] })}>+ Ajouter une carte</button>
            </div> : null}
            <details className="cms-details"><summary>Bouton de cette section</summary><div className="grid gap-4 pt-4 sm:grid-cols-2">
              <label className="field-label">Texte du bouton<input className="field-input" value={section.linkLabel} onChange={(event) => update(section.id, { linkLabel: event.target.value })} /></label>
              <label className="field-label">Destination<input className="field-input" placeholder="/contact#devis" value={section.linkUrl} onChange={(event) => update(section.id, { linkUrl: event.target.value })} /></label>
            </div><p className="cms-help mt-3">Un chemin du site (/contact), une ancre (#devis) ou une adresse https. Dans le hero d’accueil, un bouton vide reprend le bouton principal du site.</p></details>
            <p className="cms-help">Vous pouvez utiliser {"{company_name}"}, {"{experience_years}"} et {"{company_intervention_area}"} dans vos textes.</p>
          </div> : <div className="cms-block-fields">
            <p className="cms-help">Ces choix concernent uniquement cette section. Les couleurs et les polices communes se gèrent dans Apparence.</p>
            <div className="grid gap-5 sm:grid-cols-2">
              {presentations ? <label className="field-label">Présentation de la collection<select className="field-input" value={section.presentation ?? "auto"} onChange={(event) => update(section.id, { presentation: event.target.value as CmsSection["presentation"] })}>
                {Object.entries(presentations).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
              </select></label> : null}
              {Object.entries(layoutOptions).filter(([key]) => (key !== "layout" && key !== "imageFit" || visual) && (key !== "columns" || columns)).map(([key, option]) => {
                const name = key as keyof typeof layoutOptions;
                return <label className="field-label" key={key}>{option.label}<select className="field-input" value={section[name] ?? Object.keys(option.options)[0]} onChange={(event) => update(section.id, { [name]: event.target.value })}>
                  {Object.entries(option.options).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                </select></label>;
              })}
            </div>
          </div>}
          <footer className="cms-block-footer"><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={!section.hidden} onChange={(event) => update(section.id, { hidden: !event.target.checked })} />Visible sur la page</label>
            <button type="button" className="cms-danger-link" onClick={() => {
              if (window.confirm("Retirer cette section ? Enregistrez la page pour confirmer. Les photos et les contenus des collections seront conservés.")) onChange(sections.filter((item) => item.id !== section.id));
            }}>Retirer la section</button></footer>
        </div> : null}
      </article>;
    })}
    <button type="button" className="cms-add-section" aria-expanded={adding} disabled={sections.length >= 60} onClick={() => setAdding(!adding)}>{adding ? "Fermer le choix des sections" : "+ Ajouter une section"}</button>
    {adding ? <div className="cms-section-catalog">
      {(Object.entries(schema.blocks) as Array<[CmsSectionType, string]>).map(([type, label]) => <button type="button" key={type} onClick={() => add(type)}>
        <strong>{label}</strong><span>{descriptions[type]}</span><span className="cms-reset-link">Ajouter cette section +</span>
      </button>)}
    </div> : null}
  </div>;
}
