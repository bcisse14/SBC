"use client";

import { useEffect, useState } from "react";
import { adminRequest, errorMessage } from "@/lib/admin-api";

type Entry = { id: number; settingKey?: string; settingValue?: string | null; contentKey?: string; customValue?: string | null; defaultValue?: string };

const labels: Record<string, string> = {
  company_name: "Nom de l’entreprise", company_phone: "Téléphone", company_email: "E-mail public",
  company_address: "Adresse", company_hours: "Horaires", company_intervention_area: "Zone d’intervention",
  slogan_primary: "Signature principale", slogan_secondary: "Signature secondaire",
  experience_years: "Années d’expérience", announcement_top: "Bandeau en haut du site (vide pour masquer)",
  notification_email: "Adresse de réception des demandes de devis", mail_from: "Adresse d’expédition des e-mails",
  show_photo_markers: "Afficher les repères photo (1 = oui, 0 = non)", cta_label: "Texte du bouton de contact",
  cta_url: "Destination du bouton de contact (chemin local ou URL)",
  footer_contact_title: "Titre des coordonnées dans le pied de page",
  footer_pages_title: "Titre du menu dans le pied de page",
  footer_legal_title: "Titre des liens complémentaires dans le pied de page",
  seo_description: "Description générale pour les moteurs de recherche",
  "email.confirmation.subject": "Objet de l’e-mail de confirmation au client",
  "email.confirmation.intro": "Début de l’e-mail de confirmation au client",
  "email.confirmation.outro": "Fin de l’e-mail de confirmation au client",
  "email.notification.subject": "Objet de la notification envoyée à l’entreprise",
  "email.notification.intro": "Début de la notification envoyée à l’entreprise",
};

export function SharedContentEditor({ module, csrfToken, notify, onDirty }: {
  module: "settings" | "content";
  csrfToken: string;
  notify: (message: string, error?: boolean) => void;
  onDirty: (dirty: boolean) => void;
}) {
  const [entries, setEntries] = useState<Entry[] | null>(null);
  const [drafts, setDrafts] = useState<Record<number, string>>({});
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [attempt, setAttempt] = useState(0);
  const dirty = Object.keys(drafts).length > 0;
  useEffect(() => { onDirty(dirty); return () => onDirty(false); }, [dirty, onDirty]);
  useEffect(() => {
    let active = true;
    adminRequest<Entry[]>(`/api/admin/${module}`).then((rows) => {
      if (active) { setEntries(module === "content" ? rows.filter((entry) => entry.contentKey?.startsWith("email.")) : rows); setError(""); }
    }).catch((error) => { if (active) setError(errorMessage(error)); });
    return () => { active = false; };
  }, [module, attempt]);
  const valueOf = (entry: Entry) => module === "settings" ? entry.settingValue ?? "" : entry.customValue ?? entry.defaultValue ?? "";
  return <section>
    <div className="cms-section-heading"><div><p className="cms-eyebrow">Informations partagées</p><h2>{module === "settings" ? "Coordonnées et réglages" : "Textes des e-mails"}</h2>
      <p className="mt-2 max-w-2xl text-sm text-gray-500">{module === "settings" ? "Les coordonnées sont reprises dans le site. Les informations d’envoi servent au traitement des demandes de devis." : "Les variables {prenom}, {nom} et {ville} sont remplacées automatiquement. Le contenu des pages se modifie dans Pages et navigation."}</p></div></div>
    {error ? <p className="cms-error" role="alert">{error}<button className="ml-3 underline" onClick={() => setAttempt(attempt + 1)}>Réessayer</button></p> : null}
    {!entries ? <p role="status">Chargement…</p> : <form className="cms-panel" onSubmit={async (event) => {
      event.preventDefault(); setBusy(true);
      let count = 0;
      try {
        for (const [id, value] of Object.entries(drafts)) {
          const result = await adminRequest<{ value: string }>(`/api/admin/${module}/${id}`, { method: "PATCH", body: JSON.stringify(module === "settings" ? { settingValue: value } : { customValue: value }) }, csrfToken);
          setEntries((items) => items?.map((entry) => entry.id === Number(id) ? { ...entry, ...(module === "settings" ? { settingValue: result.value } : { customValue: result.value }) } : entry) ?? null);
          setDrafts((items) => { const next = { ...items }; delete next[Number(id)]; return next; }); ++count;
        }
        notify("Toutes les modifications ont été enregistrées.");
      } catch (error) { notify(`${count ? `${count} champ(s) déjà enregistré(s). ` : ""}${errorMessage(error)}`, true); }
      finally { setBusy(false); }
    }}>
      <div className="cms-editor-toolbar"><p className="text-sm">{dirty ? "Modifications non enregistrées" : "Tout est enregistré"}</p>
        <button className="btn-primary" disabled={!dirty || busy}>{busy ? "Enregistrement…" : "Enregistrer"}</button></div>
      <fieldset disabled={busy} className="grid gap-5 p-6 md:grid-cols-2">
        {entries.map((entry) => {
          const key = entry.settingKey ?? entry.contentKey ?? "";
          const value = drafts[entry.id] ?? valueOf(entry);
          const label = labels[key] ?? key.replaceAll(".", " / ").replaceAll("_", " ");
          return <label key={entry.id} className={`field-label ${module === "content" ? "md:col-span-2" : ""}`}>{label}
            <textarea className={`field-input ${module === "content" ? "min-h-32" : "min-h-20"}`} value={value} onChange={(event) => {
              const updated = event.target.value;
              setDrafts((items) => { const next = { ...items }; if (updated === valueOf(entry)) delete next[entry.id]; else next[entry.id] = updated; return next; });
            }} />
          </label>;
        })}
      </fieldset>
    </form>}
  </section>;
}
