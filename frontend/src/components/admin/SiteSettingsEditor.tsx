"use client";

import { useEffect, useState } from "react";
import { adminRequest, errorMessage } from "@/lib/admin-api";
import { siteTheme } from "@/lib/site-customization";
import type { AdminMedia } from "@/lib/cms-types";
import { PagePreview } from "@/components/admin/PagePreview";
import type { SiteBootstrap } from "@/lib/types";
import { PhotoField } from "@/components/admin/MediaPicker";

type EditorField = {
  group: string; label: string; default: string;
  type: "text" | "textarea" | "url" | "map" | "color" | "number" | "boolean" | "select" | "media";
  options?: Record<string, string>; min?: number; max?: number;
};
type EditorData = { fields: Record<string, EditorField>; values: Record<string, string | null> };
const groups: Record<string, { title: string; description: string }> = {
  company: { title: "L’entreprise", description: "Ces informations sont reprises automatiquement dans les pages et les coordonnées." },
  navigation: { title: "En-tête et pied de page", description: "Personnalisez les boutons, les signatures et les titres des menus. Les liens des menus se gèrent dans chaque page." },
  delivery: { title: "Réception des demandes", description: "Adresses utilisées pour les notifications. La configuration du prestataire d’envoi reste gérée sur le serveur." },
  palette: { title: "Couleurs", description: "Les couleurs s’appliquent à tout le site, sans modifier cet espace d’administration." },
  typography: { title: "Typographie", description: "Choisissez parmi les polices déjà disponibles, sans chargement externe supplémentaire." },
  layout: { title: "Mise en page", description: "Réglez les proportions et la visibilité des éléments communs. Les sections restent adaptées au mobile." },
  home: { title: "Accueil et repères", description: "Les titres et textes des sections se modifient dans Pages. Ici : légendes et repères communs du hero." },
  services: { title: "Prestations et projets", description: "Personnalisez les familles et les libellés communs aux listes et aux réalisations." },
  interface: { title: "Navigation, photos et carte", description: "Tous les petits textes visibles dans les menus et les galeries. Videz l’adresse de la carte pour la masquer." },
  form: { title: "Formulaire de devis", description: "Modifiez les libellés, les aides et les boutons. Les validations et les limites de fichiers restent protégées pour garantir le traitement des demandes." },
};
const modes = {
  appearance: { title: "Apparence du site", description: "Couleurs, typographie et mise en page. Votre identité, sans toucher au code.", groups: ["palette", "typography", "layout"] },
  settings: { title: "Entreprise et coordonnées", description: "Un seul endroit pour les informations utilisées sur tout le site.", groups: ["company", "navigation", "delivery"] },
  interface: { title: "Textes et libellés", description: "Les légendes, les familles de prestations et les petits textes du site, enfin à portée de main.", groups: ["home", "services", "interface", "form"] },
};

export function SiteSettingsEditor({ mode, csrfToken, media, upload, notify, onDirty }: {
  mode: keyof typeof modes; csrfToken: string; media: AdminMedia[];
  notify: (message: string, error?: boolean) => void; onDirty: (dirty: boolean) => void;
  upload: (files: FileList) => Promise<AdminMedia[]>;
}) {
  const config = modes[mode];
  const [data, setData] = useState<EditorData | null>(null);
  const [drafts, setDrafts] = useState<Record<string, string>>({});
  const [group, setGroup] = useState(config.groups[0]);
  const [search, setSearch] = useState("");
  const [error, setError] = useState("");
  const [attempt, setAttempt] = useState(0);
  const [busy, setBusy] = useState(false);
  const [preview, setPreview] = useState<SiteBootstrap | null>(null);
  const dirty = Object.keys(drafts).length > 0;
  useEffect(() => { onDirty(dirty); return () => onDirty(false); }, [dirty, onDirty]);
  useEffect(() => {
    let active = true;
    adminRequest<EditorData>("/api/admin/site-editor").then((result) => {
      if (active) { setData(result); setError(""); }
    }).catch((error) => { if (active) setError(errorMessage(error)); });
    return () => { active = false; };
  }, [attempt]);
  function change(key: string, value: string) {
    setDrafts((current) => {
      const next = { ...current };
      if (value === (data?.values[key] ?? "")) delete next[key]; else next[key] = value;
      return next;
    });
  }
  async function save() {
    setBusy(true);
    try {
      const saved = await adminRequest<EditorData>("/api/admin/site-editor", { method: "PUT", body: JSON.stringify(drafts) }, csrfToken);
      setData(saved); setDrafts({}); notify("Réglages enregistrés. Le site est à jour.");
    } catch (error) { notify(errorMessage(error), true); }
    finally { setBusy(false); }
  }
  const values = { ...data?.values, ...drafts };
  const filtered = Object.entries(data?.fields ?? {}).filter(([, field]) =>
    config.groups.includes(field.group) && (search ? field.label.toLocaleLowerCase().includes(search.toLocaleLowerCase()) : field.group === group));
  return <section>
    <div className="cms-section-heading"><div><p className="cms-eyebrow">Personnaliser</p><h1>{config.title}</h1><p className="cms-description">{config.description}</p></div></div>
    {error ? <p className="cms-error" role="alert">{error} <button className="underline ml-3" onClick={() => setAttempt(attempt + 1)}>Réessayer</button>
      <a className="underline ml-3" href={`${process.env.NEXT_PUBLIC_ADMIN_PATH || "/atelier-admin"}/login`}>Se reconnecter</a></p> : null}
    {!data ? !error ? <p role="status">Chargement des réglages…</p> : null : <form onSubmit={(event) => { event.preventDefault(); void save(); }}>
      <div className="cms-editor-toolbar">
        <span className={`cms-save-state ${dirty ? "is-dirty" : ""}`}>{dirty ? `${Object.keys(drafts).length} modification(s) à enregistrer` : "Tous les réglages sont enregistrés"}</span>
        <div className="flex flex-wrap gap-2">
          <button className="btn-secondary" type="button" disabled={busy} onClick={async () => {
            setBusy(true);
            try { setPreview(await adminRequest<SiteBootstrap>("/api/site/bootstrap")); }
            catch (error) { notify(errorMessage(error), true); }
            finally { setBusy(false); }
          }}>Prévisualiser</button>
          <button className="btn-primary" disabled={!dirty || busy}>{busy ? "Enregistrement…" : "Enregistrer les réglages"}</button>
        </div>
      </div>
      <div className="cms-settings-layout">
        <nav className="cms-settings-nav" aria-label="Catégories de réglages">
          {config.groups.map((key) => <button key={key} type="button" aria-current={!search && group === key ? "page" : undefined} onClick={() => { setGroup(key); setSearch(""); }}>{groups[key].title}</button>)}
        </nav>
        <div className="cms-panel p-5 sm:p-7">
          <input className="field-input mb-6" type="search" aria-label="Rechercher un réglage" placeholder="Rechercher un réglage…" value={search} onChange={(event) => setSearch(event.target.value)} />
          {!search ? <div className="mb-7"><h2 className="cms-form-title">{groups[group].title}</h2><p className="cms-description">{groups[group].description}</p></div> : null}
          {mode === "appearance" && group === "palette" && !search ? <div className="cms-theme-sample public-site site-customized mb-6" style={siteTheme(values)}>
            <span className="cms-eyebrow">Aperçu des couleurs</span><h3>Des espaces à votre image.</h3><p>Vos textes, sur le fond principal.</p><span className="btn-primary">Votre bouton</span>
          </div> : null}
          <fieldset disabled={busy} className="cms-settings-fields">
            {filtered.map(([key, field]) => {
              const value = values[key] ?? "";
              if (field.type === "media") return <div key={key}><h3 className="field-label mb-3">{field.label}</h3>
                <PhotoField media={media} upload={upload} value={value ? [Number(value)] : []} onChange={(ids) => change(key, ids[0] ? String(ids[0]) : "")} /></div>;
              return <div key={key} className={field.type === "textarea" ? "cms-field-wide" : ""}>
                <label className={`field-label ${field.type === "boolean" ? "cms-toggle-field" : ""}`}>
                  {field.type === "boolean" ? <><input type="checkbox" checked={value === "1"} onChange={(event) => change(key, event.target.checked ? "1" : "0")} /><span>{field.label}</span></> : <>
                    <span>{field.label}</span>
                    {field.type === "select" ? <select className="field-input" value={value} onChange={(event) => change(key, event.target.value)}>{Object.entries(field.options ?? {}).map(([option, label]) => <option key={option} value={option}>{label}</option>)}</select>
                      : field.type === "textarea" ? <textarea className="field-input min-h-24" value={value} onChange={(event) => change(key, event.target.value)} />
                        : field.type === "color" ? <span className="cms-color-input"><input type="color" value={value} onChange={(event) => change(key, event.target.value)} /><input className="field-input" aria-label={`${field.label} : code hexadécimal`} pattern="#[0-9a-fA-F]{6}" required value={value} onChange={(event) => change(key, event.target.value)} /></span>
                          : <input className="field-input" type={field.type === "number" ? "number" : "text"} min={field.min} max={field.max} required={field.type === "number"} value={value} onChange={(event) => change(key, event.target.value)} />}
                  </>}
                </label>
                {value !== field.default ? <button type="button" className="cms-reset-link" onClick={() => change(key, field.default)}>Rétablir la valeur d’origine</button> : null}
              </div>;
            })}
            {!filtered.length ? <p role="status">Aucun réglage ne correspond à votre recherche.</p> : null}
          </fieldset>
          <p className="cms-help mt-8">Les changements restent privés jusqu’à l’enregistrement. Les variables {"{company_name}"} et {"{experience_years}"} reprennent vos informations d’entreprise.</p>
        </div>
      </div>
    </form>}
    {preview ? <PagePreview sections={preview.pages.find((page) => page.slug === "")?.sections ?? []} media={media} settingsOverride={values} homeOverride onClose={() => setPreview(null)} /> : null}
  </section>;
}
