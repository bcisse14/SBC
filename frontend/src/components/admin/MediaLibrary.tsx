"use client";

import { useEffect, useState } from "react";
import type { AdminMedia } from "@/lib/cms-types";
import { adminRequest, errorMessage } from "@/lib/admin-api";

export function MediaLibrary({ media, upload, refresh, csrfToken, notify, onDirty }: {
  media: AdminMedia[];
  upload: (files: FileList) => Promise<AdminMedia[]>;
  refresh: () => Promise<void>;
  csrfToken: string;
  notify: (message: string, error?: boolean) => void;
  onDirty: (dirty: boolean) => void;
}) {
  const [search, setSearch] = useState("");
  const [unused, setUnused] = useState(false);
  const [busy, setBusy] = useState(false);
  const [drafts, setDrafts] = useState<Record<number, string>>({});
  const dirty = Object.keys(drafts).length > 0;
  useEffect(() => { onDirty(dirty); return () => onDirty(false); }, [dirty, onDirty]);
  function clearDraft(id: number) {
    setDrafts((items) => { const next = { ...items }; delete next[id]; return next; });
  }
  const filtered = media.filter((asset) => (!unused || asset.usage.length === 0) && `${asset.originalName} ${asset.altText}`.toLocaleLowerCase().includes(search.toLocaleLowerCase()));
  return <section>
    <div className="cms-section-heading">
      <div><p className="cms-eyebrow">Vos images, au même endroit</p><h2>Médiathèque</h2><p className="mt-2 text-sm text-gray-500">{media.length} média(s). Une photo peut être utilisée à plusieurs endroits sans être dupliquée.</p></div>
      <label className="btn-primary cursor-pointer">{busy ? "Importation…" : "+ Importer des fichiers"}
        <input hidden type="file" multiple accept="image/jpeg,image/png,image/webp,video/mp4" disabled={busy} onChange={async (event) => {
          const files = event.target.files; if (!files?.length) return;
          setBusy(true);
          try { const assets = await upload(files); notify(`${assets.length} fichier(s) importé(s).`); }
          catch (error) { notify(errorMessage(error), true); }
          finally { setBusy(false); }
          event.target.value = "";
        }} />
      </label>
    </div>
    <div className="cms-panel mb-6 flex flex-wrap items-center gap-5 p-4">
      <input className="field-input max-w-md" aria-label="Rechercher un média" placeholder="Rechercher une photo ou un fichier…" value={search} onChange={(event) => setSearch(event.target.value)} />
      <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={unused} onChange={(event) => setUnused(event.target.checked)} />Uniquement les médias inutilisés</label>
    </div>
    <p className="mb-5 text-xs leading-6 text-gray-500">La suppression définitive efface l’original et ses variantes. Les médias encore utilisés, même dans un brouillon, sont protégés. Retirez-les de leurs contenus avant de les supprimer ici.</p>
    <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
      {filtered.map((asset) => <MediaCard key={asset.id} asset={asset} csrfToken={csrfToken} refresh={refresh} notify={notify}
        alt={drafts[asset.id] ?? asset.altText ?? ""} onSaved={() => clearDraft(asset.id)}
        onAltChange={(value) => {
          if (value === (asset.altText ?? "")) clearDraft(asset.id);
          else setDrafts((items) => ({ ...items, [asset.id]: value }));
        }} />)}
    </div>
    {!filtered.length ? <p className="cms-panel cms-empty py-16">{media.length ? "Aucun média ne correspond à votre recherche." : "Ajoutez vos premières photos. Elles seront disponibles dans tous les éditeurs."}</p> : null}
  </section>;
}

function MediaCard({ asset, csrfToken, refresh, notify, alt, onAltChange, onSaved }: {
  asset: AdminMedia;
  csrfToken: string;
  refresh: () => Promise<void>;
  notify: (message: string, error?: boolean) => void;
  alt: string;
  onAltChange: (value: string) => void;
  onSaved: () => void;
}) {
  const [busy, setBusy] = useState(false);
  async function remove() {
    if (!window.confirm(`Supprimer définitivement « ${asset.originalName} » et tous ses fichiers ? Cette action est irréversible.`)) return;
    setBusy(true);
    try {
      await adminRequest(`/api/admin/media/${asset.id}`, { method: "DELETE" }, csrfToken);
      onSaved(); await refresh(); notify("Média et fichiers supprimés définitivement.");
    } catch (error) { notify(errorMessage(error), true); }
    finally { setBusy(false); }
  }
  return <article className="cms-panel overflow-hidden">
    {asset.mimeType.startsWith("image/") ? (
      // eslint-disable-next-line @next/next/no-img-element
      <img className="aspect-[4/3] w-full bg-[#eae8e1] object-cover" src={asset.url} alt={asset.altText || asset.originalName} loading="lazy" />
    ) : <video className="aspect-[4/3] w-full bg-black" src={asset.url} controls preload="metadata" />}
    <div className="p-4">
      <p className="truncate text-sm font-semibold" title={asset.originalName}>{asset.originalName}</p>
      <p className="mt-1 text-xs text-gray-500">{asset.width ? `${asset.width} × ${asset.height} px / ` : ""}#{asset.id}</p>
      <form className="mt-4 space-y-3" onSubmit={async (event) => {
        event.preventDefault(); setBusy(true);
        try {
          await adminRequest(`/api/admin/media/${asset.id}`, { method: "PATCH", body: JSON.stringify({ altText: alt }) }, csrfToken);
          await refresh(); onSaved(); notify("Description de la photo enregistrée.");
        } catch (error) { notify(errorMessage(error), true); }
        finally { setBusy(false); }
      }}>
        <label className="field-label text-xs">Description accessible<input className="field-input text-sm" value={alt} maxLength={255} required disabled={busy} onChange={(event) => onAltChange(event.target.value)} /></label>
        <button className="btn-secondary w-full" disabled={busy || alt === (asset.altText ?? "")}>Enregistrer la description</button>
      </form>
      <details className="mt-4 text-xs text-gray-500">
        <summary className="cursor-pointer">{asset.usage.length ? `Utilisé dans ${asset.usage.length} contenu(s)` : "Non utilisé"}</summary>
        <ul className="mt-2 space-y-1">{asset.usage.map((usage) => <li key={usage}>{usage}</li>)}</ul>
      </details>
      <button type="button" className="cms-danger-link mt-4" disabled={busy || asset.usage.length > 0} onClick={remove}>Supprimer définitivement</button>
    </div>
  </article>;
}
