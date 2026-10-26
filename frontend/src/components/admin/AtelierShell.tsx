"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import type { AdminMedia, CmsRecord, CmsSchema } from "@/lib/cms-types";
import { AdminApiError, adminRequest, errorMessage } from "@/lib/admin-api";
import { ResourceEditor } from "@/components/admin/ResourceEditor";
import { MediaLibrary } from "@/components/admin/MediaLibrary";
import { SharedContentEditor } from "@/components/admin/SharedContentEditor";
import { QuotesAdmin } from "@/components/admin/QuotesAdmin";
import { SiteSettingsEditor } from "@/components/admin/SiteSettingsEditor";
import { EstimatesAdmin } from "@/components/admin/EstimatesAdmin";

const navigation = [
  { title: "Pour commencer", items: [["dashboard", "Vue d’ensemble", "⌂"], ["pages", "Pages du site", "▤"]] },
  { title: "Vos contenus", items: [
    ["realisations", "Réalisations", "▧"], ["services", "Prestations", "≡"], ["media", "Photos et fichiers", "▣"],
    ["announcements", "Actualités et annonces", "◷"],
    ["reviews", "Avis clients", "☆"], ["faq", "Questions fréquentes", "?"],
    ["partners", "Partenaires", "◇"], ["certifications", "Certifications", "✓"],
  ] },
  { title: "Personnaliser", items: [
    ["appearance", "Apparence du site", "◐"], ["interface", "Textes et libellés", "T"],
    ["settings", "Entreprise et coordonnées", "◎"], ["photos", "Photos partagées", "↗"],
  ] },
  { title: "Votre activité", items: [
    ["quotes", "Demandes de devis", "✉"], ["content", "E-mails automatiques", "↪"],
    ["estimates", "Devis et clients", "▤"],
  ] },
];

export function AtelierShell() {
  const router = useRouter();
  const [user, setUser] = useState("");
  const [schema, setSchema] = useState<CmsSchema | null>(null);
  const [csrfToken, setCsrfToken] = useState("");
  const [media, setMedia] = useState<AdminMedia[]>([]);
  const [active, setActive] = useState("dashboard");
  const [initialId, setInitialId] = useState<number | undefined>();
  const [navSearch, setNavSearch] = useState("");
  const sidebar = useRef<HTMLElement>(null);
  const menuButton = useRef<HTMLButtonElement>(null);
  const [dirty, setDirty] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);
  const [message, setMessage] = useState<{ text: string; error: boolean } | null>(null);
  const [loadError, setLoadError] = useState("");
  const [retry, setRetry] = useState(0);
  const notify = useCallback((text: string, error = false) => setMessage({ text, error }), []);
  const onDirty = useCallback((value: boolean) => setDirty(value), []);
  const adminPath = process.env.NEXT_PUBLIC_ADMIN_PATH || "/atelier-admin";

  useEffect(() => {
    let active = true;
    async function load() {
      try {
        const me = await adminRequest<{ email: string }>("/api/admin/me");
        const [config, csrf, assets] = await Promise.all([
          adminRequest<CmsSchema>("/api/admin/cms/schema"),
          adminRequest<{ tokens: { admin: string } }>("/api/admin/csrf"),
          adminRequest<AdminMedia[]>("/api/admin/media"),
        ]);
        if (!csrf.tokens.admin) throw new Error("Impossible d’initialiser la protection des formulaires.");
        if (!active) return;
        setUser(me.email); setSchema(config); setCsrfToken(csrf.tokens.admin); setMedia(assets); setLoadError("");
        const params = new URLSearchParams(window.location.search);
        const moduleName = params.get("module");
        if (params.has("quote")) setActive("quotes");
        else if (moduleName && navigation.some((group) => group.items.some(([id]) => id === moduleName))) {
          setActive(moduleName);
          const id = params.get("item");
          if (id && /^\d+$/.test(id)) setInitialId(Number(id));
        }
      } catch (error) {
        if (!active) return;
        if (error instanceof AdminApiError && [401, 403].includes(error.status)) router.replace(`${adminPath}/login`);
        else setLoadError(errorMessage(error));
      }
    }
    void load();
    return () => { active = false; };
  }, [router, adminPath, retry]);

  useEffect(() => {
    if (!dirty) return;
    const prevent = (event: BeforeUnloadEvent) => { event.preventDefault(); };
    window.addEventListener("beforeunload", prevent);
    return () => window.removeEventListener("beforeunload", prevent);
  }, [dirty]);

  useEffect(() => {
    if (!menuOpen) return;
    const trigger = menuButton.current;
    sidebar.current?.querySelector<HTMLElement>("a, button")?.focus();
    const overflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    const closeOnDesktop = () => { if (window.innerWidth >= 1024) setMenuOpen(false); };
    window.addEventListener("resize", closeOnDesktop);
    return () => { document.body.style.overflow = overflow; window.removeEventListener("resize", closeOnDesktop); trigger?.focus(); };
  }, [menuOpen]);

  const refreshMedia = useCallback(async () => {
    const assets = await adminRequest<AdminMedia[]>("/api/admin/media");
    setMedia(assets);
  }, []);

  useEffect(() => {
    if (active !== "media" || !schema) return;
    let current = true;
    adminRequest<AdminMedia[]>("/api/admin/media").then((assets) => {
      if (current) setMedia(assets);
    }).catch((error) => { if (current) notify(errorMessage(error), true); });
    return () => { current = false; };
  }, [active, schema, notify]);

  async function upload(files: FileList) {
    const created: AdminMedia[] = [];
    for (const file of Array.from(files)) {
      if (file.size > 30 * 1024 * 1024) {
        throw new Error(`${file.name} dépasse 30 Mo.${created.length ? ` ${created.length} fichier(s) déjà importé(s) restent dans la médiathèque.` : ""}`);
      }
      const body = new FormData(); body.append("file", file);
      try {
        const asset = await adminRequest<AdminMedia>("/api/admin/media", { method: "POST", body }, csrfToken);
        created.push(asset);
        setMedia((assets) => [asset, ...assets]);
      } catch (error) {
        throw new Error(`${file.name} : ${errorMessage(error)}${created.length ? ` ${created.length} fichier(s) déjà importé(s) restent disponibles dans la médiathèque.` : ""}`);
      }
    }
    return created;
  }

  function navigate(module: string, itemId?: number) {
    if (module === active && itemId === initialId) { setMenuOpen(false); return; }
    if (dirty && !window.confirm("Quitter ce module et abandonner les modifications non enregistrées ?")) return;
    setActive(module); setInitialId(itemId); setDirty(false); setMessage(null); setMenuOpen(false);
    const params = new URLSearchParams();
    params.set("module", module);
    if (itemId) params.set("item", String(itemId));
    window.history.replaceState(null, "", `${window.location.pathname}?${params}`);
    window.scrollTo({ top: 0 });
  }

  if (!schema) return <main className="flex min-h-screen items-center justify-center bg-[#f6f5f1] p-6">
    <div className="cms-panel max-w-lg p-10 text-center"><p className="font-serif text-5xl">SBC Studio</p>
      {loadError ? <><p role="alert" className="cms-error my-6">{loadError}</p><button className="btn-primary" onClick={() => setRetry(retry + 1)}>Réessayer</button></>
        : <p className="mt-5 text-sm text-gray-500" role="status">Ouverture de votre espace…</p>}
    </div>
  </main>;

  return <div className="cms-app">
    <aside ref={sidebar} className={`cms-sidebar ${menuOpen ? "is-open" : ""}`} role={menuOpen ? "dialog" : undefined} aria-modal={menuOpen ? true : undefined} aria-label="Navigation du Studio" onKeyDown={(event) => {
      if (event.key === "Escape") setMenuOpen(false);
      if (menuOpen && event.key === "Tab") {
        const focusable = Array.from(event.currentTarget.querySelectorAll<HTMLElement>("a, button, input")).filter((el) => el.getClientRects().length);
        const first = focusable[0]; const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
      }
    }}>
      <div className="flex items-start justify-between">
        <div><a href={adminPath} onClick={(event) => { event.preventDefault(); navigate("dashboard"); }} className="cms-brand">SBC<span>Studio</span></a>
          <p className="cms-brand-caption">Votre espace de création</p></div>
        <button className="text-sm lg:hidden" onClick={() => setMenuOpen(false)}>Fermer</button>
      </div>
      <input type="search" className="field-input cms-nav-search" aria-label="Trouver un espace" placeholder="Trouver un espace…" value={navSearch} onChange={(event) => setNavSearch(event.target.value)} />
      <nav className="cms-navigation" aria-label="Espaces de gestion">
        {navigation.filter((group) => group.items.some(([, label]) => label.toLocaleLowerCase().includes(navSearch.toLocaleLowerCase()))).map((group) => <div key={group.title}>
          <p className="cms-nav-group">{group.title}</p>
          {group.items.filter(([, label]) => label.toLocaleLowerCase().includes(navSearch.toLocaleLowerCase())).map(([id, label, number]) => <button key={id} className={`cms-nav-item ${active === id ? "is-active" : ""}`}
            aria-current={active === id ? "page" : undefined} onClick={() => navigate(id)}>
            <span className="cms-nav-symbol" aria-hidden="true">{number}</span><span>{label}</span>
          </button>)}
        </div>)}
      </nav>
      <div className="cms-account">
        <a href="/" target="_blank" rel="noreferrer">Ouvrir le site ↗</a>
        <p className="truncate" title={user}>{user}</p>
        <button className="underline" onClick={async () => {
          if (dirty && !window.confirm("Vous déconnecter sans enregistrer les modifications ?")) return;
          try { await adminRequest("/api/admin/logout", { method: "POST" }, csrfToken); router.replace(`${adminPath}/login`); }
          catch (error) { notify(errorMessage(error), true); }
        }}>Se déconnecter</button>
      </div>
    </aside>
    <div className="min-w-0 lg:col-start-2" inert={menuOpen}>
      <header className="cms-topbar">
        <button ref={menuButton} className="btn-secondary lg:hidden" aria-expanded={menuOpen} onClick={() => setMenuOpen(true)}>Menu</button>
        <div><span className="text-gray-400">Mon site / </span>{navigation.flatMap((group) => group.items).find(([id]) => id === active)?.[1]}</div>
        {dirty ? <span className="cms-unsaved-badge">Non enregistré</span> : null}
        <a href="/" target="_blank" rel="noreferrer" className="ml-auto text-sm underline">Voir le site ↗</a>
      </header>
      <main className="cms-main">
        {message ? <div role={message.error ? "alert" : "status"} className={`cms-notice ${message.error ? "is-error" : ""}`}>
          <p>{message.text}</p><button type="button" aria-label="Fermer le message" onClick={() => setMessage(null)}>×</button>
        </div> : null}
        {active === "dashboard" ? <Dashboard navigate={navigate} mediaCount={media.length} /> : null}
        {schema.resources[active] ? <ResourceEditor key={`${active}-${initialId ?? ""}`} initialId={initialId} resource={active} schema={schema} csrfToken={csrfToken} media={media} upload={upload} notify={notify} onDirty={onDirty} navigate={navigate} /> : null}
        {active === "media" ? <MediaLibrary media={media} upload={upload} refresh={refreshMedia} csrfToken={csrfToken} notify={notify} onDirty={onDirty} /> : null}
        {active === "settings" || active === "appearance" || active === "interface" ? <SiteSettingsEditor key={active} mode={active} csrfToken={csrfToken} media={media} upload={upload} notify={notify} onDirty={onDirty} /> : null}
        {active === "content" ? <SharedContentEditor module="content" csrfToken={csrfToken} notify={notify} onDirty={onDirty} /> : null}
        {active === "quotes" ? <QuotesAdmin csrfToken={csrfToken} notify={notify} createEstimate={(id) => navigate("estimates", id)} /> : null}
        {active === "estimates" ? <EstimatesAdmin key={`estimates-${initialId ?? ""}`} initialRequestId={initialId} csrfToken={csrfToken} notify={notify} onDirty={onDirty} /> : null}
      </main>
      <footer className="px-6 pb-6 text-xs text-gray-400 lg:px-10">SBC Studio · Vos modifications ne sont publiées qu’après enregistrement.</footer>
    </div>
  </div>;
}

function Dashboard({ navigate, mediaCount }: { navigate: (module: string, itemId?: number) => void; mediaCount: number }) {
  const [counts, setCounts] = useState<{ pages: number; announcements: number; quotes: number } | null>(null);
  const [pages, setPages] = useState<CmsRecord[]>([]);
  const [error, setError] = useState("");
  const [attempt, setAttempt] = useState(0);
  useEffect(() => {
    let active = true;
    Promise.all([
      adminRequest<CmsRecord[]>("/api/admin/cms/pages"),
      adminRequest<CmsRecord[]>("/api/admin/cms/announcements"),
      adminRequest<Array<{ status: string }>>("/api/admin/quotes"),
    ]).then(([pages, announcements, quotes]) => {
      if (active) { setPages(pages); setCounts({ pages: pages.length, announcements: announcements.length, quotes: quotes.filter((quote) => quote.status === "new").length }); setError(""); }
    }).catch((error) => { if (active) setError(errorMessage(error)); });
    return () => { active = false; };
  }, [attempt]);
  return <section>
    <div className="cms-dashboard-heading"><div><p className="cms-eyebrow">Bienvenue chez vous</p><h1>Que voulez-vous faire aujourd’hui ?</h1>
      <p className="cms-description">Votre site, vos contenus, votre identité. Choisissez un point de départ.</p></div><a className="btn-secondary" href="/" target="_blank" rel="noreferrer">Voir mon site ↗</a></div>
    {error ? <p className="cms-error my-5" role="alert">{error}<button className="ml-4 underline" onClick={() => setAttempt(attempt + 1)}>Réessayer</button></p> : null}
    <div className="cms-quick-actions">
      {[
        ["pages", "Modifier une page", "Retrouvez vos textes, vos photos et l’ordre des sections.", "▤"],
        ["realisations", "Ajouter une réalisation", "Présentez un chantier terminé et ses photos.", "▧"],
        ["appearance", "Personnaliser le site", "Couleurs, polices, proportions et mise en page.", "◐"],
      ].map(([module, title, description, symbol]) => <button type="button" key={module} onClick={() => navigate(module)}><span aria-hidden="true">{symbol}</span><strong>{title}</strong><p>{description}</p><span className="cms-reset-link">Commencer →</span></button>)}
    </div>
    <div className="my-8 grid grid-cols-2 gap-4 xl:grid-cols-4">
      {[
        ["Pages du site", counts?.pages, "pages"], ["Photos et médias", mediaCount, "media"],
        ["Annonces", counts?.announcements, "announcements"], ["Nouvelles demandes", counts?.quotes, "quotes"],
      ].map(([label, count, module]) => <button key={module} className="cms-stat text-left" onClick={() => navigate(String(module))}>
        <span className="text-xs text-gray-500">{label}</span><strong className="mt-3 block text-3xl font-semibold">{count ?? "…"}</strong>
      </button>)}
    </div>
    <div className="cms-dashboard-columns">
      <div className="cms-panel p-5 sm:p-7"><div className="flex items-center justify-between gap-4"><h2 className="cms-form-title">Vos pages</h2><button className="cms-reset-link" onClick={() => navigate("pages")}>Tout voir →</button></div>
        <div className="cms-dashboard-pages">{pages.map((page) => <button key={page.id} onClick={() => navigate("pages", page.id)}>
          <span><strong>{String(page.title)}</strong><small>/{String(page.slug ?? "")}</small></span><span className={`cms-status ${page.isPublished ? "is-published" : ""}`}>{page.isPublished ? "Publiée" : "Brouillon"}</span><span aria-hidden="true">→</span>
        </button>)}</div>
      </div>
      <aside className="cms-guide"><span className="cms-eyebrow">Un repère utile</span><h2>Vous gardez la main.</h2>
        <ol><li><strong>Modifiez</strong><p>Les changements restent dans l’éditeur tant que vous n’enregistrez pas.</p></li>
          <li><strong>Prévisualisez</strong><p>Contrôlez votre page avant de la rendre visible.</p></li>
          <li><strong>Enregistrez</strong><p>Une page publiée est mise à jour immédiatement. Un brouillon reste privé.</p></li></ol>
        <button type="button" className="btn-secondary mt-5" onClick={() => navigate("interface")}>Changer un petit texte du site</button>
      </aside>
    </div>
  </section>;
}
