"use client";

import { useEffect, useState } from "react";
import { adminRequest, errorMessage } from "@/lib/admin-api";

type Quote = {
  id: number; firstName: string; lastName: string; email: string; phone: string;
  city: string; postalCode: string; description: string; requestedServices: string[];
  propertyType: string; budgetRange: string; timeline: string; visitRequested: boolean;
  status: string; createdAt: string; callbackTime: string | null;
  attachments: Array<{ id: number; originalName: string }>;
};
const statuses: Record<string, string> = {
  new: "Nouveau", contacted: "Contacté", visit_planned: "Visite planifiée", quote_sent: "Devis envoyé",
  won: "Gagné", lost: "Perdu", archived: "Archivé",
};

export function QuotesAdmin({ csrfToken, notify, createEstimate }: { csrfToken: string; notify: (message: string, error?: boolean) => void; createEstimate: (requestId: number) => void }) {
  const [quotes, setQuotes] = useState<Quote[]>([]);
  const [selected, setSelected] = useState<number | null>(null);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [attempt, setAttempt] = useState(0);
  useEffect(() => {
    let active = true;
    adminRequest<Quote[]>("/api/admin/quotes").then((items) => {
      if (!active) return;
      setQuotes(items); setError("");
      const id = Number(new URLSearchParams(window.location.search).get("quote"));
      setSelected(items.some((item) => item.id === id) ? id : null);
    }).catch((error) => { if (active) setError(errorMessage(error)); });
    return () => { active = false; };
  }, [attempt]);
  const quote = quotes.find((quote) => quote.id === selected);
  return <section>
    <div className="cms-section-heading"><div><p className="cms-eyebrow">Suivi commercial</p><h2>Demandes de devis</h2><p className="mt-2 text-sm text-gray-500">Retrouvez les coordonnées, le projet et les pièces jointes de vos clients.</p></div></div>
    {error ? <p className="cms-error" role="alert">{error}<button className="ml-3 underline" onClick={() => setAttempt(attempt + 1)}>Réessayer</button></p> : null}
    <div className="cms-panel mb-5 flex flex-wrap gap-3 p-4">
      <input className="field-input flex-1" aria-label="Rechercher une demande" placeholder="Nom, ville, e-mail…" value={search} onChange={(event) => setSearch(event.target.value)} />
      <select className="field-input sm:!w-auto" aria-label="Filtrer par statut" value={status} onChange={(event) => setStatus(event.target.value)}>
        <option value="">Tous les statuts</option>{Object.entries(statuses).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
      </select>
    </div>
    <div className="grid items-start gap-5 xl:grid-cols-2">
      <div className="cms-panel divide-y divide-[var(--beton)]">
        {quotes.filter((quote) => (!status || quote.status === status) && `${quote.firstName} ${quote.lastName} ${quote.city} ${quote.email}`.toLocaleLowerCase().includes(search.toLocaleLowerCase())).map((quote) =>
          <button key={quote.id} className={`block w-full p-5 text-left ${selected === quote.id ? "bg-[#f0eee8]" : ""}`} onClick={() => setSelected(quote.id)}>
            <div className="flex justify-between gap-3"><strong>{quote.firstName} {quote.lastName}</strong><span className="cms-badge">{statuses[quote.status]}</span></div>
            <p className="mt-2 text-sm text-gray-500">{quote.city} / {quote.requestedServices.join(", ")}</p>
            <p className="mt-2 text-xs text-gray-400">#{quote.id} / {new Date(quote.createdAt).toLocaleDateString("fr-FR")}</p>
          </button>)}
        {!quotes.length && !error ? <p className="cms-empty py-16">Les prochaines demandes apparaîtront ici.</p> : null}
      </div>
      {quote ? <article className="cms-panel space-y-5 p-6">
        <h3 className="font-serif text-4xl">{quote.firstName} {quote.lastName}</h3>
        <button type="button" className="btn-primary" onClick={() => createEstimate(quote.id)}>Créer le devis</button>
        <p className="cms-help">Reprenez les coordonnées et le projet, chiffrez les prestations puis enregistrez un brouillon ou téléchargez le PDF. Les devis déjà créés se retrouvent dans Devis et clients.</p>
        <div className="space-y-2 text-sm"><p><a className="underline" href={`mailto:${quote.email}`}>{quote.email}</a></p><p><a className="underline" href={`tel:${quote.phone}`}>{quote.phone}</a></p>
          <p>{quote.postalCode} {quote.city}</p>{quote.callbackTime ? <p>Rappel : {quote.callbackTime}</p> : null}</div>
        <label className="field-label">Statut du dossier<select className="field-input" value={quote.status} disabled={busy} onChange={async (event) => {
          const status = event.target.value; setBusy(true);
          try {
            await adminRequest(`/api/admin/quotes/${quote.id}/status`, { method: "PATCH", body: JSON.stringify({ status }) }, csrfToken);
            setQuotes((items) => items.map((item) => item.id === quote.id ? { ...item, status } : item)); notify("Statut enregistré.");
          } catch (error) { notify(errorMessage(error), true); }
          finally { setBusy(false); }
        }}>{Object.entries(statuses).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></label>
        <dl className="grid grid-cols-2 gap-4 text-sm">{[["Bien", quote.propertyType], ["Budget", quote.budgetRange], ["Délai", quote.timeline], ["Visite souhaitée", quote.visitRequested ? "Oui" : "Non"]].map(([label, value]) =>
          <div key={label}><dt className="text-xs text-gray-500">{label}</dt><dd className="mt-1">{value}</dd></div>)}</dl>
        <p className="whitespace-pre-wrap border-t border-[var(--beton)] pt-5 text-sm leading-7">{quote.description}</p>
        {quote.attachments.length ? <div><h4 className="mb-3 text-sm font-semibold">Pièces jointes</h4>{quote.attachments.map((attachment) =>
          <a key={attachment.id} className="mb-2 block text-sm underline" target="_blank" rel="noreferrer" href={`/api/admin/quotes/${quote.id}/attachments/${attachment.id}`}>{attachment.originalName} ↗</a>)}</div> : null}
      </article> : <div className="cms-panel cms-empty py-24">Sélectionnez une demande pour consulter son détail.</div>}
    </div>
  </section>;
}
