"use client";

import { useEffect, useRef, useState } from "react";
import type { AdminMedia } from "@/lib/cms-types";
import { errorMessage } from "@/lib/admin-api";

export function MediaPicker({ media, onPick, onClose, upload, multiple = false }: {
  media: AdminMedia[];
  onPick: (ids: number[]) => void;
  onClose: () => void;
  upload: (files: FileList) => Promise<AdminMedia[]>;
  multiple?: boolean;
}) {
  const dialog = useRef<HTMLDialogElement>(null);
  const [search, setSearch] = useState("");
  const [selected, setSelected] = useState<number[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  useEffect(() => { dialog.current?.showModal(); }, []);

  return <dialog ref={dialog} className="cms-dialog" onCancel={onClose} aria-labelledby="media-picker-title">
    <header className="flex flex-wrap items-center justify-between gap-4 border-b border-[var(--beton)] p-6">
      <div><p className="cms-eyebrow">Médiathèque</p><h2 id="media-picker-title" className="font-serif text-3xl">Choisir {multiple ? "des photos" : "une photo"}</h2></div>
      <button type="button" className="btn-secondary" onClick={onClose}>Fermer</button>
    </header>
    <div className="space-y-5 p-6">
      <div className="flex flex-wrap gap-3">
        <input aria-label="Rechercher une photo" className="field-input flex-1" placeholder="Rechercher une photo…" value={search} onChange={(event) => setSearch(event.target.value)} />
        <label className={`btn-primary cursor-pointer ${busy ? "opacity-50" : ""}`}>
          {busy ? "Envoi en cours…" : "Importer des photos"}
          <input hidden type="file" accept="image/jpeg,image/png,image/webp" multiple disabled={busy} onChange={async (event) => {
            const files = event.target.files;
            if (!files?.length) return;
            setBusy(true); setError("");
            try {
              const assets = await upload(files);
              setSelected((ids) => multiple ? [...ids, ...assets.map((asset) => asset.id)] : [assets[0].id]);
            } catch (error) { setError(errorMessage(error)); }
            finally { setBusy(false); }
          }} />
        </label>
      </div>
      {error ? <p role="alert" className="cms-error">{error}</p> : null}
      <div className="grid max-h-[48vh] grid-cols-2 gap-3 overflow-y-auto sm:grid-cols-4">
        {media.filter((asset) => asset.mimeType.startsWith("image/") && `${asset.originalName} ${asset.altText}`.toLocaleLowerCase().includes(search.toLocaleLowerCase())).map((asset) => (
          <button type="button" key={asset.id} aria-pressed={selected.includes(asset.id)} className={`cms-media-choice ${selected.includes(asset.id) ? "is-selected" : ""}`}
            onClick={() => setSelected((ids) => multiple ? ids.includes(asset.id) ? ids.filter((id) => id !== asset.id) : [...ids, asset.id] : [asset.id])}>
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img src={asset.url} alt={asset.altText ?? asset.originalName} loading="lazy" className="aspect-square w-full object-cover" />
            <span className="block truncate p-2 text-xs">{asset.originalName}</span>
          </button>
        ))}
      </div>
      {media.filter((asset) => asset.mimeType.startsWith("image/")).length === 0 ? <p className="cms-empty">Importez votre première photo pour commencer.</p> : null}
      <p className="text-xs text-[var(--terre-cuite-fonce)]">JPEG, PNG ou WebP. 30 Mo maximum par photo. Les originaux et formats optimisés sont conservés.</p>
    </div>
    <footer className="flex justify-end border-t border-[var(--beton)] p-5">
      <button type="button" disabled={!selected.length || busy} className="btn-primary" onClick={() => { onPick(selected); onClose(); }}>
        Utiliser la sélection ({selected.length})
      </button>
    </footer>
  </dialog>;
}

export function PhotoField({ value, onChange, media, upload, multiple = false }: {
  value: number[];
  onChange: (ids: number[]) => void;
  media: AdminMedia[];
  upload: (files: FileList) => Promise<AdminMedia[]>;
  multiple?: boolean;
}) {
  const [picking, setPicking] = useState(false);
  return <div className="space-y-3">
    <div className="flex flex-wrap gap-3">
      {value.map((id, index) => {
        const asset = media.find((asset) => asset.id === id);
        return <div key={id} className="w-32 border border-[var(--beton)] bg-white">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={asset?.url ?? `/api/media/${id}`} alt={asset?.altText ?? "Photo sélectionnée"} className="aspect-square w-full object-cover" />
          <div className="flex flex-wrap justify-center gap-2 p-2 text-xs">
            {multiple ? <>
              <button type="button" aria-label={`Avancer la photo ${index + 1}`} disabled={index === 0} onClick={() => {
                const result = [...value]; [result[index - 1], result[index]] = [result[index], result[index - 1]]; onChange(result);
              }}>←</button>
              <button type="button" aria-label={`Reculer la photo ${index + 1}`} disabled={index === value.length - 1} onClick={() => {
                const result = [...value]; [result[index + 1], result[index]] = [result[index], result[index + 1]]; onChange(result);
              }}>→</button>
            </> : null}
            <button type="button" className="text-[var(--terre-cuite-fonce)] underline" onClick={() => onChange(value.filter((item) => item !== id))}>Retirer</button>
          </div>
        </div>;
      })}
    </div>
    <button type="button" className="btn-secondary" onClick={() => setPicking(true)}>{value.length && !multiple ? "Remplacer la photo" : "Choisir ou importer"}</button>
    {picking ? <MediaPicker media={media} upload={upload} multiple={multiple} onClose={() => setPicking(false)}
      onPick={(ids) => onChange(multiple ? [...new Set([...value, ...ids])] : ids.slice(0, 1))} /> : null}
    <p className="text-xs text-gray-500">Retirer une photo ici ne supprime pas le fichier de la médiathèque.</p>
  </div>;
}
