"use client";

import { useEffect, useState } from "react";
import { adminRequest, errorMessage, moveItem } from "@/lib/admin-api";
import {
  emptyCompany, emptyContact, money,
  type EstimateCompany, type EstimateContact, type EstimateData, type EstimateProfile, type EstimateTotals, type SavedEstimate,
} from "@/lib/estimate-types";

type Props = { csrfToken: string; initialRequestId?: number; notify: (message: string, error?: boolean) => void; onDirty: (dirty: boolean) => void };
type Draft = { id?: number; number?: string; requestId: number | null; data: EstimateData };

export function EstimatesAdmin({ csrfToken, initialRequestId, notify, onDirty }: Props) {
  const [rows, setRows] = useState<SavedEstimate[]>([]);
  const [profiles, setProfiles] = useState<EstimateProfile[]>([]);
  const [draft, setDraft] = useState<Draft | null>(null);
  const [saved, setSaved] = useState("");
  const [tab, setTab] = useState("estimates");
  const [profile, setProfile] = useState<EstimateProfile | null>(null);
  const [profileSaved, setProfileSaved] = useState("");
  const [search, setSearch] = useState("");
  const [busy, setBusy] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [attempt, setAttempt] = useState(0);
  const dirty = draft !== null && JSON.stringify(draft) !== saved;
  const profileDirty = profile !== null && JSON.stringify(profile) !== profileSaved;
  useEffect(() => { onDirty(dirty || profileDirty); return () => onDirty(false); }, [dirty, profileDirty, onDirty]);
  useEffect(() => {
    let active = true;
    Promise.all([
      adminRequest<SavedEstimate[]>("/api/admin/estimates"),
      adminRequest<EstimateProfile[]>("/api/admin/estimates/profiles"),
      initialRequestId ? adminRequest<EstimateData>(`/api/admin/estimates/seed?requestId=${initialRequestId}`) : Promise.resolve(null),
    ]).then(([estimates, contacts, seed]) => {
      if (!active) return;
      setRows(estimates); setProfiles(contacts); setError(""); setLoading(false);
      const selectedId = Number(new URLSearchParams(window.location.search).get("estimate"));
      const selected = estimates.find((row) => row.id === selectedId);
      if (selected) {
        const copy = { id: selected.id, number: selected.number, requestId: selected.requestId, data: selected.data };
        setDraft(copy); setSaved(JSON.stringify(copy));
      } else if (selectedId) {
        setError("Ce devis est introuvable. Revenez à Devis et clients depuis le menu.");
      } else if (seed) { setDraft({ requestId: initialRequestId ?? null, data: seed }); setSaved(""); }
    }).catch((error) => { if (active) { setError(errorMessage(error)); setLoading(false); } });
    return () => { active = false; };
  }, [initialRequestId, attempt]);
  function canLeave() {
    return !busy && (!(dirty || profileDirty) || window.confirm("Abandonner les modifications non enregistrées ?"));
  }
  function address(id?: number) {
    const params = new URLSearchParams(window.location.search);
    params.delete("item");
    if (id) params.set("estimate", String(id)); else params.delete("estimate");
    window.history.replaceState(null, "", `${window.location.pathname}?${params}`);
  }
  function open(row: SavedEstimate) {
    if (!canLeave()) return;
    const copy = { id: row.id, number: row.number, requestId: row.requestId, data: structuredClone(row.data) };
    setDraft(copy); setSaved(JSON.stringify(copy)); setProfile(null);
    address(row.id);
  }
  async function create() {
    if (!canLeave()) return;
    setBusy(true);
    try {
      const data = await adminRequest<EstimateData>("/api/admin/estimates/seed");
      setDraft({ requestId: null, data }); setSaved(""); setProfile(null); setTab("estimates");
      address();
    } catch (error) { notify(errorMessage(error), true); }
    finally { setBusy(false); }
  }
  async function save() {
    if (!draft) return;
    setBusy(true);
    try {
      const row = await adminRequest<SavedEstimate>(`/api/admin/estimates${draft.id ? `/${draft.id}` : ""}`, {
        method: draft.id ? "PUT" : "POST", body: JSON.stringify({ requestId: draft.requestId, data: draft.data }),
      }, csrfToken);
      setRows((items) => [row, ...items.filter((item) => item.id !== row.id)]);
      const next = { id: row.id, number: row.number, requestId: row.requestId, data: row.data };
      setDraft(next); setSaved(JSON.stringify(next));
      address(row.id);
      notify("Brouillon du devis enregistré. Aucun e-mail n’a été envoyé.");
    } catch (error) { notify(errorMessage(error), true); }
    finally { setBusy(false); }
  }
  async function remove() {
    if (!draft?.id || !window.confirm("Supprimer définitivement ce devis ? La demande et les fiches restent conservées.")) return;
    setBusy(true);
    try {
      await adminRequest(`/api/admin/estimates/${draft.id}`, { method: "DELETE" }, csrfToken);
      setRows((items) => items.filter((item) => item.id !== draft.id)); setDraft(null); setSaved("");
      address();
      notify("Devis supprimé.");
    } catch (error) { notify(errorMessage(error), true); }
    finally { setBusy(false); }
  }
  async function download() {
    if (!draft?.id || dirty) return;
    setBusy(true);
    try {
      const response = await fetch(`/api/admin/estimates/${draft.id}/pdf`, { credentials: "include" });
      if (!response.ok) {
        if ([401, 403].includes(response.status)) throw new Error("Votre session a expiré. Reconnectez-vous pour télécharger le PDF.");
        if (response.headers.get("content-type")?.includes("application/json")) {
          const result: { message?: string } = await response.json();
          throw new Error(result.message || `Téléchargement impossible (${response.status}).`);
        }
        throw new Error(`Téléchargement impossible (${response.status}).`);
      }
      if (!response.headers.get("content-type")?.includes("application/pdf")) throw new Error("Le serveur n’a pas renvoyé un document PDF.");
      const url = URL.createObjectURL(await response.blob());
      const link = document.createElement("a"); link.href = url; link.download = `${draft.number}.pdf`;
      document.body.append(link); link.click(); link.remove(); setTimeout(() => URL.revokeObjectURL(url), 1000);
      notify("PDF préparé, téléchargement lancé. Le statut de la demande n’a pas été modifié.");
    } catch (error) { notify(errorMessage(error), true); }
    finally { setBusy(false); }
  }
  function editProfile(item: EstimateProfile) {
    if (!canLeave()) return;
    setDraft(null); setProfile(structuredClone(item)); setProfileSaved(item.id ? JSON.stringify(item) : "");
    address();
  }
  async function saveProfile() {
    if (!profile) return;
    setBusy(true);
    try {
      const item = await adminRequest<EstimateProfile>(`/api/admin/estimates/profiles${profile.id ? `/${profile.id}` : ""}`, {
        method: profile.id ? "PUT" : "POST", body: JSON.stringify({ kind: profile.kind, data: profile.data }),
      }, csrfToken);
      setProfiles((items) => [item, ...items.filter((row) => row.id !== item.id)]);
      setProfile(item); setProfileSaved(JSON.stringify(item)); notify("Fiche enregistrée et réutilisable. Les devis existants ne changent pas.");
    } catch (error) { notify(errorMessage(error), true); }
    finally { setBusy(false); }
  }
  async function deleteProfile() {
    if (!profile?.id || !window.confirm("Supprimer cette fiche ? Les copies enregistrées dans les devis seront conservées.")) return;
    setBusy(true);
    try {
      await adminRequest(`/api/admin/estimates/profiles/${profile.id}`, { method: "DELETE" }, csrfToken);
      setProfiles((items) => items.filter((item) => item.id !== profile.id)); setProfile(null);
      notify("Fiche supprimée. Les devis existants sont conservés.");
    } catch (error) { notify(errorMessage(error), true); }
    finally { setBusy(false); }
  }
  if (loading) return <p role="status">Chargement des devis et fiches…</p>;
  if (error) return <p role="alert" className="cms-error">{error} <button className="underline" onClick={() => { setLoading(true); setAttempt(attempt + 1); }}>Réessayer</button>
    <button className="underline ml-3" onClick={() => { address(); setDraft(null); setError(""); }}>Revenir à la liste</button></p>;
  return <section>
    <div className="cms-section-heading"><div><p className="cms-eyebrow">Vos documents commerciaux</p><h1>Devis et clients</h1>
      <p className="cms-description">Préparez un devis, retrouvez vos brouillons et réutilisez les coordonnées déjà saisies. Aucun envoi automatique.</p></div></div>
    <div className="cms-tabs mb-6" role="tablist" aria-label="Devis et fiches">
      {[["estimates", "Mes devis"], ["company", "Fiche entreprise"], ["client", "Fiches clients"]].map(([id, label]) => <button key={id} type="button" role="tab" aria-selected={tab === id} onClick={() => {
        if (canLeave()) { setTab(id); setDraft(null); setProfile(null); setSearch(""); address(); }
      }}>{label}</button>)}
    </div>
    {tab === "estimates" ? draft ? <>
      <button type="button" className="cms-back-link" disabled={busy} onClick={() => { if (canLeave()) { setDraft(null); address(); } }}>← Mes devis</button>
      <div className="cms-editor-toolbar"><div><strong>{draft.number || "Nouveau devis"}</strong><p className="cms-help">{dirty ? "Modifications non enregistrées" : "Brouillon enregistré"}{draft.requestId ? ` · Demande #${draft.requestId}` : ""}</p></div>
        <div className="flex flex-wrap gap-2"><button type="button" className="btn-primary" disabled={!dirty || busy} onClick={save}>Enregistrer le brouillon</button>
          <button type="button" className="btn-secondary" disabled={dirty || !draft.id || busy} onClick={download}>Télécharger le PDF</button></div></div>
      <EstimateForm key={draft.id ?? `new-${draft.requestId ?? ""}`} data={draft.data} setData={(data) => setDraft({ ...draft, data })} profiles={profiles} setProfiles={setProfiles} csrfToken={csrfToken} notify={notify} disabled={busy} />
      <p className="cms-help my-5">Enregistrez avant de télécharger. Un brouillon peut rester incomplet ; le PDF nécessite les champs essentiels. Vérifiez les mentions légales, les taux de TVA, l’assurance et les conditions applicables à votre activité avant transmission au client. Le téléchargement n’envoie rien et ne change pas le statut de la demande.</p>
      {draft.id ? <button type="button" className="cms-danger-link" disabled={busy} onClick={remove}>Supprimer ce devis</button> : null}
    </> : <>
      <div className="cms-collection-toolbar"><input type="search" className="field-input" aria-label="Rechercher un devis" placeholder="Numéro, client, chantier…" value={search} onChange={(event) => setSearch(event.target.value)} />
        <button type="button" className="btn-primary" disabled={busy} onClick={create}>+ Créer un devis</button></div>
      <div className="cms-content-grid">{rows.filter((row) => `${row.number} ${row.data.client.name} ${row.data.title} ${row.data.siteAddress}`.toLocaleLowerCase().includes(search.toLocaleLowerCase())).map((row) => <button type="button" key={row.id} className="cms-panel p-5 text-left" onClick={() => open(row)}>
        <span className="cms-badge">{row.missing.length ? "À compléter" : "Prêt pour PDF"}</span><h2 className="mt-3 font-semibold">{row.number} · {row.data.client.name || "Client à renseigner"}</h2>
        <p className="mt-2 text-sm">{row.data.title}</p><p className="cms-help mt-2">{money(row.totals.totalTtc)} TTC{row.requestId ? ` · Demande #${row.requestId}` : ""}</p>
      </button>)}</div>
      {!rows.length ? <p className="cms-empty">Aucun devis enregistré. Créez-en un ici ou depuis une demande de devis.</p> : null}
    </> : <>
      <div className="cms-collection-toolbar"><input type="search" className="field-input" aria-label="Rechercher une fiche" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Nom, adresse, e-mail…" />
        <button type="button" className="btn-primary" disabled={busy} onClick={() => editProfile(tab === "company" ? { id: 0, kind: "company", data: { ...emptyCompany } } : { id: 0, kind: "client", data: { ...emptyContact } })}>+ {tab === "company" ? "Ajouter une fiche entreprise" : "Ajouter un client"}</button></div>
      <div className="flex flex-wrap gap-3 mb-5">{profiles.filter((item) => item.kind === tab && Object.values(item.data).join(" ").toLocaleLowerCase().includes(search.toLocaleLowerCase())).map((item) => <button type="button" key={item.id} className="btn-secondary" onClick={() => editProfile(item)}>{item.data.name}</button>)}</div>
      {profile ? <form className="cms-panel p-5 sm:p-7" onSubmit={(event) => { event.preventDefault(); void saveProfile(); }}>
        <fieldset disabled={busy}>
          {profile.kind === "company" ? <CompanyFields value={profile.data} onChange={(data) => setProfile({ ...profile, data })} /> : <ContactFields value={profile.data} onChange={(data) => setProfile({ ...profile, data })} />}
        </fieldset>
        <div className="mt-5 flex flex-wrap gap-4"><button className="btn-primary" disabled={busy || !profileDirty}>Enregistrer la fiche</button>
          {profile.id ? <button type="button" className="cms-danger-link" disabled={busy} onClick={deleteProfile}>Supprimer la fiche</button> : null}</div>
      </form> : <p className="cms-empty">Sélectionnez une fiche ou ajoutez-en une. Les fiches restent privées et ne modifient pas les coordonnées publiques du site.</p>}
    </>}
  </section>;
}

function ContactFields<T extends EstimateContact>({ value, onChange }: { value: T; onChange: (value: T) => void }) {
  return <div className="grid gap-4 sm:grid-cols-2">
    {([["name", "Nom / raison sociale", 180], ["address", "Adresse complète", 500], ["email", "E-mail", 180], ["phone", "Téléphone", 40]] as const).map(([key, label, max]) => <label key={key} className="field-label">{label}
      {key === "address" ? <textarea className="field-input" maxLength={max} value={value[key]} onChange={(event) => onChange({ ...value, [key]: event.target.value })} />
        : <input className="field-input" type={key === "email" ? "email" : "text"} maxLength={max} value={value[key]} onChange={(event) => onChange({ ...value, [key]: event.target.value })} />}
    </label>)}
  </div>;
}

function CompanyFields({ value, onChange }: { value: EstimateCompany; onChange: (value: EstimateCompany) => void }) {
  return <><ContactFields value={value} onChange={onChange} />
    <div className="grid gap-4 sm:grid-cols-2 mt-4">
      <label className="field-label">Immatriculation, forme juridique et capital<input className="field-input" placeholder="SIRET, RCS, forme juridique, capital…" maxLength={180} value={value.registration} onChange={(event) => onChange({ ...value, registration: event.target.value })} /></label>
      <label className="field-label">Numéro de TVA intracommunautaire<input className="field-input" maxLength={80} value={value.vatNumber} onChange={(event) => onChange({ ...value, vatNumber: event.target.value })} /></label>
    </div>
    <label className="field-label mt-4">Assurance professionnelle<textarea className="field-input" placeholder="Assureur, coordonnées, contrat et couverture géographique…" maxLength={1000} value={value.insurance} onChange={(event) => onChange({ ...value, insurance: event.target.value })} /></label>
  </>;
}

function EstimateForm({ data, setData, profiles, setProfiles, csrfToken, notify, disabled }: {
  data: EstimateData; setData: (data: EstimateData) => void; profiles: EstimateProfile[];
  setProfiles: (update: (items: EstimateProfile[]) => EstimateProfile[]) => void;
  csrfToken: string; notify: Props["notify"]; disabled: boolean;
}) {
  const [totals, setTotals] = useState<EstimateTotals | null>(null);
  const [missing, setMissing] = useState<string[]>([]);
  const [calculationError, setCalculationError] = useState("");
  const [calculating, setCalculating] = useState(true);
  const [calculationAttempt, setCalculationAttempt] = useState(0);
  const [profileBusy, setProfileBusy] = useState(false);
  const [companyId, setCompanyId] = useState(() => String(profiles.find((item) => item.kind === "company" && JSON.stringify(item.data) === JSON.stringify(data.company))?.id ?? ""));
  const [clientId, setClientId] = useState(() => String(profiles.find((item) => item.kind === "client" && JSON.stringify(item.data) === JSON.stringify(data.client))?.id ?? ""));
  useEffect(() => {
    let active = true;
    const timer = setTimeout(() => {
      adminRequest<{ totals: EstimateTotals; missing: string[] }>("/api/admin/estimates/calculate", { method: "POST", body: JSON.stringify(data) }, csrfToken)
        .then((result) => { if (active) { setTotals(result.totals); setMissing(result.missing); setCalculationError(""); setCalculating(false); } })
        .catch((error) => { if (active) { setTotals(null); setCalculationError(errorMessage(error)); setCalculating(false); } });
    }, 350);
    return () => { active = false; clearTimeout(timer); };
  }, [data, csrfToken, calculationAttempt]);
  function change(next: EstimateData) {
    setCalculating(true); setTotals(null); setCalculationError(""); setData(next);
  }
  async function remember(kind: "company" | "client") {
    const id = kind === "company" ? companyId : clientId;
    const action = id ? "Mettre à jour la fiche sélectionnée avec ces coordonnées ?" : "Créer une fiche réutilisable avec ces coordonnées ?";
    if (!window.confirm(action)) return;
    setProfileBusy(true);
    try {
      const item = await adminRequest<EstimateProfile>(`/api/admin/estimates/profiles${id ? `/${id}` : ""}`, {
        method: id ? "PUT" : "POST", body: JSON.stringify({ kind, data: data[kind] }),
      }, csrfToken);
      setProfiles((items) => [item, ...items.filter((row) => row.id !== item.id)]);
      if (kind === "company") setCompanyId(String(item.id)); else setClientId(String(item.id));
      notify("Coordonnées enregistrées dans une fiche réutilisable. Enregistrez aussi le brouillon pour conserver le devis.");
    } catch (error) { notify(errorMessage(error), true); }
    finally { setProfileBusy(false); }
  }
  function picker(kind: "company" | "client") {
    const id = kind === "company" ? companyId : clientId;
    return <div className="flex flex-wrap gap-3 mb-5">
      <label className="field-label flex-1">Fiche enregistrée<select className="field-input" value={id} onChange={(event) => {
        const selected = profiles.find((item) => item.kind === kind && item.id === Number(event.target.value));
        if (kind === "company") setCompanyId(event.target.value); else setClientId(event.target.value);
        if (selected) {
          if (selected.kind === "company") change({ ...data, company: { ...selected.data } });
          else change({ ...data, client: { ...selected.data } });
        }
      }}><option value="">Saisie libre / nouvelle fiche</option>{profiles.filter((item) => item.kind === kind).map((item) => <option key={item.id} value={item.id}>{item.data.name}</option>)}</select></label>
      <button type="button" className="btn-secondary self-end" onClick={() => remember(kind)}>{id ? "Mettre à jour la fiche" : "Enregistrer dans les fiches"}</button>
    </div>;
  }
  return <>
    <fieldset disabled={disabled || profileBusy} className="space-y-5">
      <section className="cms-panel p-5 sm:p-7"><h2 className="cms-form-title mb-5">Le projet et les dates</h2>
        <label className="field-label">Titre du devis<input className="field-input" maxLength={180} value={data.title} onChange={(event) => change({ ...data, title: event.target.value })} /></label>
        <div className="grid gap-4 sm:grid-cols-2 my-4">
          <label className="field-label">Date du devis<input type="date" className="field-input" value={data.date} onChange={(event) => change({ ...data, date: event.target.value })} /></label>
          <label className="field-label">Valable jusqu’au<input type="date" className="field-input" value={data.validUntil} onChange={(event) => change({ ...data, validUntil: event.target.value })} /></label>
        </div>
        <label className="field-label">Adresse du chantier<textarea className="field-input" maxLength={500} value={data.siteAddress} onChange={(event) => change({ ...data, siteAddress: event.target.value })} /></label>
        <label className="field-label mt-4">Présentation du projet<textarea className="field-input" maxLength={5000} value={data.introduction} onChange={(event) => change({ ...data, introduction: event.target.value })} /></label>
      </section>
      <section className="cms-panel p-5 sm:p-7"><h2 className="cms-form-title mb-5">Votre entreprise</h2>{picker("company")}
        <CompanyFields value={data.company} onChange={(company) => change({ ...data, company })} />
      </section>
      <section className="cms-panel p-5 sm:p-7"><h2 className="cms-form-title mb-5">Votre client</h2>{picker("client")}
        <ContactFields value={data.client} onChange={(client) => change({ ...data, client })} />
        <p className="cms-help mt-3">Les informations de la demande sont une base : complétez l’adresse postale exacte. Modifier ce devis ne change pas la demande d’origine.</p>
      </section>
      <section className="cms-panel p-5 sm:p-7"><h2 className="cms-form-title mb-5">Les prestations</h2>
        <p className="cms-help mb-4">Prix en euros HT, quantité jusqu’à 3 décimales. TVA arrondie au centime par ligne. Les taux doivent être vérifiés selon les travaux et la situation du client.</p>
        {data.lines.map((line, index) => <div className="cms-estimate-line" key={index}>
          <div className="flex flex-wrap gap-2 items-center mb-4"><h3 className="font-semibold mr-auto">Prestation {index + 1}</h3>
            <button type="button" className="cms-small-btn" aria-label={`Monter la prestation ${index + 1}`} disabled={index === 0} onClick={() => change({ ...data, lines: moveItem(data.lines, index, index - 1) })}>↑</button>
            <button type="button" className="cms-small-btn" aria-label={`Descendre la prestation ${index + 1}`} disabled={index === data.lines.length - 1} onClick={() => change({ ...data, lines: moveItem(data.lines, index, index + 1) })}>↓</button>
            <button type="button" className="cms-danger-link" aria-label={`Retirer la prestation ${index + 1}`} onClick={() => change({ ...data, lines: data.lines.filter((_, position) => position !== index) })}>Retirer</button>
          </div>
          <label className="field-label">Désignation<textarea className="field-input" maxLength={2000} value={line.description} onChange={(event) => change({ ...data, lines: data.lines.map((item, i) => i === index ? { ...item, description: event.target.value } : item) })} /></label>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mt-4">{([["quantity", "Quantité"], ["unit", "Unité"], ["unitPrice", "Prix unitaire HT (€)"], ["vatRate", "TVA (%)"]] as const).map(([key, label]) => <label key={key} className="field-label">{label}
            {key === "vatRate" ? <select className="field-input" value={Number(line.vatRate)} onChange={(event) => change({ ...data, lines: data.lines.map((item, i) => i === index ? { ...item, vatRate: event.target.value } : item) })}>{["0", "5.5", "10", "20"].map((rate) => <option key={rate} value={rate}>{rate}</option>)}</select>
              : <input className="field-input" type="text" inputMode={key === "unit" ? "text" : "decimal"} maxLength={key === "unit" ? 30 : 14} value={line[key]} onChange={(event) => change({ ...data, lines: data.lines.map((item, i) => i === index ? { ...item, [key]: event.target.value } : item) })} />}
          </label>)}</div>
          {totals?.lines[index] ? <p className="text-right text-sm mt-3">Total HT : <strong>{money(totals.lines[index].ht)}</strong></p> : null}
        </div>)}
        <button type="button" className="btn-secondary" disabled={data.lines.length >= 100} onClick={() => change({ ...data, lines: [...data.lines, { description: "", quantity: "1", unit: "forfait", unitPrice: "0", vatRate: "20" }] })}>+ Ajouter une prestation</button>
      </section>
      <section className="cms-panel p-5 sm:p-7"><h2 className="cms-form-title mb-5">Acompte et conditions</h2>
        <label className="field-label">Acompte (%)<input className="field-input" inputMode="decimal" value={data.depositPercent} maxLength={6} onChange={(event) => change({ ...data, depositPercent: event.target.value })} /></label>
        <label className="field-label mt-4">Conditions de règlement et d’exécution<textarea className="field-input min-h-32" placeholder="Modalités de paiement, date ou délai d’exécution, durée des travaux, fournitures, exclusions, régime de TVA…" maxLength={5000} value={data.conditions} onChange={(event) => change({ ...data, conditions: event.target.value })} /></label>
        <label className="field-label mt-4">Informations complémentaires (visibles sur le PDF)<textarea className="field-input" maxLength={3000} value={data.notes} onChange={(event) => change({ ...data, notes: event.target.value })} /></label>
      </section>
    </fieldset>
    <section className="cms-panel p-5 mt-5" aria-label="Totaux du devis" aria-live="polite">
      {calculating ? <p role="status">Calcul des montants…</p> : calculationError ? <p role="alert" className="cms-error">{calculationError} <button className="underline" onClick={() => { setCalculating(true); setCalculationAttempt(calculationAttempt + 1); }}>Recalculer</button></p> : totals ? <>
        <dl className="cms-estimate-totals">
          <dt>Total HT</dt><dd>{money(totals.totalHt)}</dd>
          {Object.entries(totals.vatGroups).map(([rate, amount]) => <div className="contents" key={rate}><dt>TVA {Number(rate)} %</dt><dd>{money(amount)}</dd></div>)}
          <dt className="font-bold">Total TTC</dt><dd className="font-bold">{money(totals.totalTtc)}</dd>
          <dt>Acompte ({data.depositPercent} %)</dt><dd>{money(totals.deposit)}</dd>
          <dt>Solde</dt><dd>{money(totals.balance)}</dd>
        </dl>
        {missing.length ? <p className="cms-help mt-5">À compléter avant PDF : {missing.join(", ")}.</p> : <p className="cms-help mt-5">Champs essentiels renseignés. Enregistrez le brouillon avant de télécharger le PDF.</p>}
      </> : null}
    </section>
  </>;
}
