"use client";

import { useEffect, useState } from "react";
import type { AdminMedia, CmsRecord, CmsSchema, CmsSection, CmsValue } from "@/lib/cms-types";
import { adminRequest, errorMessage, moveItem } from "@/lib/admin-api";
import { BlocksEditor } from "@/components/admin/BlocksEditor";
import { PhotoField } from "@/components/admin/MediaPicker";
import { PagePreview } from "@/components/admin/PagePreview";

function sectionsOf(value: CmsValue | undefined): CmsSection[] {
  return Array.isArray(value) && value.every((item) => typeof item === "object" && item !== null && "type" in item) ? value : [];
}

export function ResourceEditor({ resource, schema, csrfToken, media, upload, notify, onDirty, navigate, initialId }: {
  resource: string;
  schema: CmsSchema;
  csrfToken: string;
  media: AdminMedia[];
  upload: (files: FileList) => Promise<AdminMedia[]>;
  notify: (message: string, error?: boolean) => void;
  onDirty: (dirty: boolean) => void;
  navigate?: (module: string) => void;
  initialId?: number;
}) {
  const definition = schema.resources[resource];
  const [rows, setRows] = useState<CmsRecord[]>([]);
  const [services, setServices] = useState<CmsRecord[]>([]);
  const [draft, setDraft] = useState<CmsRecord | null>(null);
  const [saved, setSaved] = useState("");
  const [search, setSearch] = useState("");
  const [busy, setBusy] = useState(false);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState("");
  const [attempt, setAttempt] = useState(0);
  const [previewOpen, setPreviewOpen] = useState(false);
  const [tab, setTab] = useState("content");
  const [publication, setPublication] = useState("all");
  const dirty = draft !== null && JSON.stringify(draft) !== saved;

  useEffect(() => { onDirty(dirty); return () => onDirty(false); }, [dirty, onDirty]);
  useEffect(() => {
    let active = true;
    Promise.all([
      adminRequest<CmsRecord[]>(`/api/admin/cms/${resource}`),
      resource === "realisations" ? adminRequest<CmsRecord[]>("/api/admin/cms/services") : Promise.resolve([]),
    ]).then(([items, serviceRows]) => {
      if (!active) return;
      setRows(items); setServices(serviceRows); setLoadError(""); setLoading(false);
      if (initialId) {
        const row = items.find((item) => item.id === initialId);
        if (row) { setDraft(structuredClone(row)); setSaved(JSON.stringify(row)); }
      }
    }).catch((error) => { if (active) { setLoadError(errorMessage(error)); setLoading(false); } });
    return () => { active = false; };
  }, [resource, attempt, initialId]);

  function select(row: CmsRecord) {
    if (busy || (dirty && !window.confirm("Abandonner les modifications non enregistrées ?"))) return;
    const copy = structuredClone(row); setDraft(copy); setSaved(JSON.stringify(copy)); setTab("content");
    updateAddress(row.id);
  }

  function create() {
    if (busy || (dirty && !window.confirm("Abandonner les modifications non enregistrées ?"))) return;
    const item: CmsRecord = { isPublished: false };
    for (const [key, field] of Object.entries(definition.fields)) {
      item[key] = field.type === "boolean" ? false
        : ["blocks", "lines", "gallery"].includes(field.type) ? []
        : ["media", "service"].includes(field.type) ? null
        : field.type === "number" ? field.min ?? 0
        : field.type === "select" ? Object.keys(field.options ?? {})[0] ?? "" : "";
    }
    setDraft(item); setSaved(""); setTab("content");
    updateAddress();
  }

  async function save() {
    if (!draft) return;
    setBusy(true);
    try {
      const row = await adminRequest<CmsRecord>(`/api/admin/cms/${resource}${draft.id ? `/${draft.id}` : ""}`, {
        method: draft.id ? "PATCH" : "POST", body: JSON.stringify(draft),
      }, csrfToken);
      setRows((items) => draft.id ? items.map((item) => item.id === row.id ? row : item) : [...items, row]);
      setDraft(row); setSaved(JSON.stringify(row));
      updateAddress(row.id);
      notify(row.isPublished ? "Modifications enregistrées et visibles sur le site." : "Modifications enregistrées.");
    } catch (error) { notify(errorMessage(error), true); }
    finally { setBusy(false); }
  }

  async function remove() {
    if (!draft?.id || !window.confirm(`Supprimer définitivement « ${String(draft.title ?? draft.name ?? draft.question ?? draft.authorName)} » ? Cette action est irréversible. Les photos restent dans la médiathèque.`)) return;
    setBusy(true);
    try {
      await adminRequest(`/api/admin/cms/${resource}/${draft.id}`, { method: "DELETE" }, csrfToken);
      setRows((items) => items.filter((item) => item.id !== draft.id)); setDraft(null); setSaved("");
      notify("Élément supprimé définitivement.");
    } catch (error) { notify(errorMessage(error), true); }
    finally { setBusy(false); }
  }

  async function reorder(index: number, destination: number) {
    setBusy(true);
    const ordered = moveItem(rows, index, destination);
    try {
      await adminRequest(`/api/admin/cms/${resource}/order`, { method: "PUT", body: JSON.stringify({ ids: ordered.map((row) => row.id) }) }, csrfToken);
      setRows(ordered); notify("Nouvel ordre enregistré.");
    } catch (error) { notify(errorMessage(error), true); }
    finally { setBusy(false); }
  }

  if (loading) return <p className="cms-empty" role="status">Chargement des contenus…</p>;
  if (loadError) return <div className="cms-error" role="alert">{loadError}<button className="btn-secondary ml-3" onClick={() => { setLoading(true); setAttempt(attempt + 1); }}>Réessayer</button></div>;

  const set = (key: string, value: CmsValue) => setDraft((item) => item ? { ...item, [key]: value } : item);
  function updateAddress(id?: number) {
    const params = new URLSearchParams(window.location.search);
    if (id) params.set("item", String(id)); else params.delete("item");
    window.history.replaceState(null, "", `${window.location.pathname}?${params}`);
  }
  const visibleRows = rows.filter((row) => (publication === "all" || (publication === "published") === Boolean(row.isPublished)) && Object.values(row).some((value) => typeof value === "string" && value.toLocaleLowerCase().includes(search.toLocaleLowerCase())));
  function back() {
    if (busy || (dirty && !window.confirm("Revenir à la liste et abandonner les modifications non enregistrées ?"))) return;
    setDraft(null); setSaved("");
    updateAddress();
  }
  return <section>
    <div className="cms-section-heading">
      <div>{draft ? <button type="button" className="cms-back-link" disabled={busy} onClick={back}>← Tous les contenus</button> : <p className="cms-eyebrow">Votre site</p>}<h1>{draft ? String(draft.title ?? draft.name ?? draft.question ?? draft.authorName ?? "Nouveau contenu") || "Nouveau contenu" : definition.label}</h1>
        <p className="cms-description">{draft ? "Modifiez, prévisualisez, puis enregistrez quand tout vous convient." : `${rows.length} contenu(s) · ${rows.filter((row) => row.isPublished).length} publié(s). ${definition.fixed ? "Des photos partagées entre plusieurs sections." : "Choisissez un contenu pour le modifier, ou créez-en un nouveau."}`}</p>
      </div>
      {!definition.fixed && !draft ? <button className="btn-primary" disabled={busy} onClick={create}>+ {resource === "pages" ? "Créer une page" : "Ajouter un contenu"}</button> : null}
    </div>
    <div>
      {!draft ? <div>
        <div className="cms-collection-toolbar"><input className="field-input" type="search" aria-label="Rechercher un contenu" placeholder={`Rechercher dans ${definition.label.toLocaleLowerCase()}…`} value={search} onChange={(event) => setSearch(event.target.value)} />
          {!definition.fixed ? <select className="field-input" aria-label="Filtrer par publication" value={publication} onChange={(event) => setPublication(event.target.value)}><option value="all">Tous les contenus</option><option value="published">Publiés</option><option value="draft">Brouillons</option></select> : null}</div>
        <div className="cms-content-grid">
          {visibleRows.map((row) => {
            const index = rows.indexOf(row);
            const title = String(row.title ?? row.name ?? row.question ?? row.authorName ?? row.slotKey ?? "Sans titre");
            const photoId = row.mediaAssetId ?? row.logoMediaId ?? (Array.isArray(row.gallery) ? row.gallery[0] : null);
            const photo = media.find((asset) => asset.id === photoId);
            return <article key={row.id} className="cms-content-card">
              <button className="cms-content-card-main" onClick={() => select(row)} disabled={busy}>
                {photo ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={photo.url} alt="" className="cms-content-thumbnail" loading="lazy" />
                ) : <span className="cms-content-symbol" aria-hidden="true">{resource === "pages" ? "/" : "•"}</span>}
                <span className="cms-content-card-copy"><span className={`cms-status ${row.isPublished ? "is-published" : ""}`}>{definition.fixed ? "Photo partagée" : row.isPublished ? "Publié" : "Brouillon"}</span>
                  <strong>{title}</strong><span className="cms-content-summary">{resource === "pages" ? `/${row.slug ?? ""} · ${sectionsOf(row.sections).filter((section) => !section.hidden).length} sections visibles` : String(row.description ?? row.answer ?? row.message ?? "").replace("À COMPLÉTER", "Texte à renseigner")}</span></span>
              </button>
              <footer><button type="button" className="cms-reset-link" onClick={() => select(row)} disabled={busy}>Modifier {resource === "pages" ? "la page" : "le contenu"} →</button>
              {!definition.fixed ? <div className="flex gap-2">
                <button className="cms-small-btn" aria-label={`Monter ${title}`} disabled={busy || index === 0} onClick={() => reorder(index, index - 1)}>↑</button>
                <button className="cms-small-btn" aria-label={`Descendre ${title}`} disabled={busy || index === rows.length - 1} onClick={() => reorder(index, index + 1)}>↓</button>
              </div> : null}</footer>
            </article>;
          })}
          {!visibleRows.length ? <p className="cms-empty text-sm">{rows.length ? "Aucun résultat." : "Aucun contenu. Ajoutez votre premier élément."}</p> : null}
        </div>
      </div> : null}
      {draft ? <form className="cms-panel min-w-0" onSubmit={(event) => { event.preventDefault(); void save(); }}>
        <div className="cms-editor-toolbar">
          <div className={`cms-save-state ${dirty ? "is-dirty" : ""}`}>{dirty ? "Modifications non enregistrées" : "Tout est enregistré"}</div>
          <div className="flex flex-wrap gap-2">
            {resource === "pages" ? <button className="btn-secondary" type="button" onClick={() => setPreviewOpen(true)}>Aperçu</button> : null}
            <button className="btn-primary" type="submit" disabled={busy || !dirty}>{busy ? "En cours…" : "Enregistrer"}</button>
          </div>
        </div>
        {resource === "pages" ? <div className="cms-tabs px-5" role="tablist" aria-label="Réglages de la page">
          {[["content", "Contenu de la page"], ["navigation", "Adresse et menus"], ["seo", "Référencement"]].map(([key, label]) => <button key={key} type="button" role="tab" aria-selected={tab === key} onClick={() => setTab(key)}>{label}</button>)}
        </div> : null}
        <fieldset disabled={busy} className="space-y-6 p-5 sm:p-7">
          {!definition.fixed ? <label className="flex items-center gap-3 rounded-lg border border-[var(--beton)] p-4 text-sm">
            <input type="checkbox" checked={Boolean(draft.isPublished)} onChange={(event) => set("isPublished", event.target.checked)} />
            <span><strong>Publié sur le site</strong><span className="mt-1 block text-xs text-gray-500">Décochez pour conserver un brouillon invisible au public.</span></span>
          </label> : null}
          {Object.entries(definition.fields).map(([key, field]) => {
            if (resource === "pages") {
              const fieldTab = key === "seoDescription" ? "seo" : ["slug", "showInNavigation", "showInFooter"].includes(key) ? "navigation" : "content";
              if (tab !== fieldTab) return null;
            }
            const value = draft[key];
            const text = typeof value === "string" ? value : "";
            if (field.type === "blocks") return <div key={key}>
              <BlocksEditor sections={sectionsOf(value)} onChange={(sections) => set(key, sections)} schema={schema} media={media} upload={upload} navigate={navigate} /></div>;
            if (field.type === "media" || field.type === "gallery") {
              const ids = field.type === "media" ? typeof value === "number" ? [value] : [] : Array.isArray(value) ? value.filter((id): id is number => typeof id === "number") : [];
              return <div className="space-y-3" key={key}><p className="field-label">{field.label}</p><PhotoField value={ids} media={media} upload={upload} multiple={field.type === "gallery"}
                onChange={(ids) => set(key, field.type === "media" ? ids[0] ?? null : ids)} /></div>;
            }
            if (field.type === "boolean") return <label key={key} className="flex items-center gap-3 text-sm"><input type="checkbox" checked={Boolean(value)} onChange={(event) => set(key, event.target.checked)} />{field.label}</label>;
            return <label className="field-label" key={key}>{resource === "pages" && key === "title" ? "Nom de la page (utilisé dans les menus)" : field.label}{field.required ? " *" : ""}
              {field.type === "textarea" || field.type === "lines" ? <textarea className="field-input min-h-32" required={field.required}
                value={field.type === "lines" && Array.isArray(value) ? value.join("\n") : text}
                maxLength={field.max} onChange={(event) => set(key, field.type === "lines" ? event.target.value.split("\n") : event.target.value)} />
                : field.type === "select" || field.type === "service" ? <select className="field-input" value={typeof value === "number" ? value : text} required={field.required}
                  onChange={(event) => set(key, field.type === "service" ? event.target.value ? Number(event.target.value) : null : event.target.value)}>
                  {field.type === "service" ? <><option value="">Aucun service associé</option>{services.map((service) => <option key={service.id} value={service.id}>{String(service.name)}</option>)}</>
                    : Object.entries(field.options ?? {}).map(([id, label]) => <option key={id} value={id}>{label}</option>)}
                </select>
                  : <input className="field-input" type={field.type === "number" ? "number" : field.type === "datetime" ? "datetime-local" : "text"}
                    required={field.required} readOnly={field.readonly} value={typeof value === "number" ? value : text} min={field.min}
                    max={field.type === "number" ? field.max : undefined} maxLength={field.type !== "number" ? field.max : undefined}
                    placeholder={field.type === "url" ? "/contact ou https://…" : undefined}
                    onChange={(event) => set(key, field.type === "number" ? Number(event.target.value) : event.target.value)} />}
              {field.type === "slug" && resource === "pages" ? <span className="text-xs text-gray-500">Adresse publique : /{text}</span> : null}
            </label>;
          })}
        </fieldset>
        <footer className="flex flex-wrap items-center justify-between gap-3 border-t border-[var(--beton)] p-5">
          {draft.id && !definition.fixed ? <button type="button" className="cms-danger-link" disabled={busy} onClick={remove}>Supprimer définitivement</button> : <span />}
          <div className="flex flex-wrap gap-3">
            {resource === "pages" && draft.id && draft.isPublished ? <a className="btn-secondary" href={`/${draft.slug ?? ""}`} target="_blank" rel="noreferrer">Voir la page enregistrée ↗</a> : null}
            <button type="submit" className="btn-primary" disabled={busy || !dirty}>Enregistrer</button>
          </div>
        </footer>
      </form> : null}
    </div>
    {previewOpen && draft ? <PagePreview sections={sectionsOf(draft.sections)} homeOverride={draft.slug === ""} media={media} onClose={() => setPreviewOpen(false)} /> : null}
  </section>;
}
