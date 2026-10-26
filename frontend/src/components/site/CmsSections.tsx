import Link from "next/link";
import type { CmsSection } from "@/lib/cms-types";
import type { SiteBootstrap } from "@/lib/types";
import { ResponsiveImage } from "@/components/site/ResponsiveImage";
import { CategoryGallery } from "@/components/site/CategoryGallery";
import { QuoteForm } from "@/components/forms/QuoteForm";
import { MapReveal } from "@/components/site/MapReveal";
import { siteText } from "@/lib/site-customization";
import { PartnerCarousels } from "@/components/site/PartnerCarousels";

const announcementTypes = { news: "Actualité", offer: "Offre", job: "Emploi", information: "Information" };
const defaultServiceFamilies = [
  { key: "interieur", label: "Intérieur", title: "Intérieurs", description: "Des pièces dans lesquelles on se sent bien." },
  { key: "exterieur", label: "Extérieur", title: "Extérieurs", description: "Prendre soin de votre maison, au-dehors aussi." },
  { key: "projet_global", label: "Projet global", title: "Projets sur mesure", description: "Une vision d’ensemble, un accompagnement complet." },
];

export function CmsSections({ sections, data, filter = {}, home = false, pageTitle }: {
  sections: CmsSection[];
  data: SiteBootstrap;
  filter?: { service?: string; filtre?: string };
  home?: boolean;
  pageTitle?: string;
}) {
  const image = (id: number | null, className = "w-full object-cover") => {
    const media = id ? data.media[String(id)] : null;
    return media ? <ResponsiveImage media={media} className={className} sizes="(min-width: 1024px) 45vw, 100vw" /> : null;
  };
  const resolve = (text: string) => text.replace(/\{([a-z_]+)\}/g, (token, key: string) => data.settings[key] ?? token);
  const text = (key: string, fallback: string) => siteText(data.settings, key, fallback);
  const serviceFamilies = defaultServiceFamilies.map((family) => ({
    ...family,
    label: text(`family_${family.key}_label`, family.label),
    title: text(`family_${family.key}_title`, family.title),
    description: text(`family_${family.key}_description`, family.description),
  }));
  const ctaUrl = data.settings.cta_url ?? "/contact#devis";
  const ctaLabel = data.settings.cta_label ?? "Demander un devis";
  const realisationsPage = data.pages.find((page) => page.slug === "realisations")
    ?? data.pages.find((page) => page.slug && page.sections.some((section) => section.type === "realisations" && !section.hidden));
  const servicesPage = data.pages.find((page) => page.slug === "services")
    ?? data.pages.find((page) => page.slug && page.sections.some((section) => section.type === "services" && !section.hidden));
  const filteredServices = data.services.filter((service) => !filter.filtre || filter.filtre === "tous" || service.group === filter.filtre);
  const servicePhotos = filteredServices.filter((service, index, services) =>
    service.mediaAssetId && data.media[String(service.mediaAssetId)] && services.findIndex((item) => item.mediaAssetId === service.mediaAssetId) === index);
  const visibleSections = sections.filter((section) => {
    if (section.hidden) return false;
    const collections = { services: data.services, realisations: data.realisationCategories, reviews: data.reviews, faq: data.faq, partners: data.partners, certifications: data.certifications, announcements: data.announcements };
    if (section.type in collections && !collections[section.type as keyof typeof collections].length) return false;
    if (section.type === "gallery" && !section.gallery.some((id) => data.media[String(id)])) return false;
    if (section.type === "cards" && !section.cards.length) return false;
    const slot = data.photoSlots.find((slot) => slot.slotKey === section.photoSlotKey);
    return section.type !== "text" || Boolean(section.title || section.body || section.linkUrl || section.mediaAssetId || slot?.mediaAssetId);
  });

  const primaryHeroId = visibleSections.find((section) => section.type === "hero" && section.title)?.id;
  return <>{!primaryHeroId && pageTitle ? <h1 className="sr-only">{pageTitle}</h1> : null}{visibleSections.map((source, sectionIndex) => {
    const section = { ...source, title: resolve(source.title), body: resolve(source.body), label: resolve(source.label), linkLabel: resolve(source.linkLabel) };
    const { type } = section;
    const hero = type === "hero";
    const stepsPresentation = type === "cards" && (section.presentation === "steps" || (home && section.presentation !== "cards"));
    const familiesPresentation = type === "services" && (!filter.filtre || filter.filtre === "tous") && (section.presentation === "families" || (home && section.presentation !== "catalog"));
    const coversPresentation = type === "realisations" && home && section.presentation !== "galleries";
    const slot = section.photoSlotKey ? data.photoSlots.find((slot) => slot.slotKey === section.photoSlotKey) : null;
    const featuredProjectImage = home && hero && !section.mediaAssetId
      ? data.realisationCategories.find((category) => category.slug === "amenagement-sur-mesure")?.gallery[0]
        ?? data.realisationCategories.find((category) => category.slug === "renovation-chambre")?.gallery[0]
      : null;
    const heroMediaId = section.mediaAssetId ?? slot?.mediaAssetId ?? featuredProjectImage;
    const visualMedia = heroMediaId && !section.hideImage ? data.media[String(heroMediaId)] : null;
    const hasImage = Boolean(visualMedia);
    const heroCtaUrl = section.linkUrl || ctaUrl;
    const heroCtaLabel = section.linkUrl ? section.linkLabel || ctaLabel : ctaLabel;
    const sectionNumber = String(sectionIndex + 1).padStart(2, "0");
    return (
      <section key={section.id} id={type === "quote" ? "devis" : section.id}
        className={`site-section site-shell site-section--${type} ${hero && home ? "site-section--home-hero" : ""} ${hasImage ? "has-image" : ""} ${type === "text" && section.linkUrl ? "site-section--invitation" : ""} scroll-mt-28`}
        data-section-number={sectionNumber} data-layout={section.layout} data-tone={section.tone}
        data-spacing={section.spacing} data-alignment={section.alignment} data-width={section.width}
        data-columns={section.columns} data-image-fit={section.imageFit}>
        <div className={`site-section-inner ${hero && hasImage ? "site-hero-layout" : hasImage && type === "text" ? "site-feature-layout" : ""}`}>
          <div className={`site-section-copy ${hero ? "site-hero-copy" : ""}`}>
            {section.label || section.title ? <p className="site-section-kicker">
              <span>{hero ? text("hero_kicker", "Rénovation & aménagement") : sectionNumber}</span>
              <span>{section.label || (hero ? data.settings.company_intervention_area : text("section_kicker", "Le sens du détail"))}</span>
            </p> : null}
            {section.title ? (hero
              ? section.id === primaryHeroId ? <h1 className="site-hero-title">{section.title}</h1> : <h2 className="site-hero-title">{section.title}</h2>
              : <h2 className="site-section-title">{section.title}</h2>) : type === "services" || type === "realisations"
                ? <h2 className="sr-only">{type === "services" ? "Nos prestations" : "Nos réalisations"}</h2> : null}
            {section.body ? <div className={`site-section-body ${hero ? "site-hero-body" : ""}`}>{section.body}</div> : null}
            {hero && home ? (
              <div className="site-hero-actions">
                {heroCtaUrl && heroCtaLabel ? <Link className="btn-primary" href={heroCtaUrl}>{heroCtaLabel}</Link> : null}
                {realisationsPage && text("hero_projects", "Découvrir nos réalisations") ? <Link className="site-text-link" href={`/${realisationsPage.slug}`}>{text("hero_projects", "Découvrir nos réalisations")} <span aria-hidden="true">↗</span></Link> : null}
              </div>
            ) : section.linkLabel && section.linkUrl ? <Link className="btn-primary mt-7" href={section.linkUrl}>{section.linkLabel}</Link> : null}
          </div>
          {(hero || type === "text") && visualMedia ? <div className={hero ? "site-hero-visual" : "site-feature-visual"}>
              <ResponsiveImage media={visualMedia} sizes="(min-width: 1024px) 48vw, 100vw" priority={hero && home} className={hero ? "site-hero-photo" : "w-full object-cover"} />
              {hero && home ? <div className="site-hero-caption"><span>{text("hero_caption", "Une réalisation {company_name}")}</span><span>{text("hero_photo_label", "Aménagement sur mesure")}</span></div> : null}
            </div> : null}
        </div>
        {hero && home && data.settings.show_hero_proof !== "0" ? (
          <div className="site-hero-proof" aria-label="Informations sur l’entreprise">
            <p><strong>{text("proof_experience", "{experience_years} ans")}</strong><span>{text("proof_experience_detail", "d’expérience")}</span></p>
            <span className="site-hero-proof-rule" aria-hidden="true" />
            <p><strong>{text("proof_contact", "Un seul interlocuteur")}</strong><span>{text("proof_contact_detail", "de l’idée au chantier")}</span></p>
          </div>
        ) : null}

        {type === "gallery" && section.gallery.length ? (
          <div className="site-section-content"><CategoryGallery settings={data.settings} title={section.title} photos={section.gallery.flatMap((id) => data.media[String(id)] ? [data.media[String(id)]] : [])} /></div>
        ) : null}
        {stepsPresentation ? <ol className="site-section-content site-process">{section.cards.map((card, index) => (
          <li key={index}>
            <span className="site-process-number" aria-hidden="true">{String(index + 1).padStart(2, "0")}</span>
            <h3>{resolve(card.title)}</h3>
            <p>{resolve(card.body)}</p>
          </li>
        ))}</ol> : type === "cards" ? <div className="site-section-content site-card-grid">{section.cards.map((card, index) => (
          <article key={index} className="site-card"><p className="site-card-index">{String(index + 1).padStart(2, "0")}</p>
            <h3>{resolve(card.title)}</h3><p>{resolve(card.body)}</p></article>
        ))}</div> : null}

        {familiesPresentation && servicesPage ? (
          <div className="site-section-content site-expertise-grid">
            {serviceFamilies.map((family, index) => {
              const services = data.services.filter((service) => service.group === family.key);
              return services.length ? <Link className="site-expertise" key={family.key} href={`/${servicesPage.slug}?filtre=${family.key}`}>
                <span className="site-card-index">0{index + 1} <span aria-hidden="true">↗</span></span>
                <h3>{family.title}</h3><p>{family.description}</p><p className="site-expertise-items">{services.map((service) => service.name).join(" · ")}</p>
                <span className="site-text-link">{text("services_discover", "Explorer nos prestations")}</span>
              </Link> : null;
            })}
          </div>
        ) : type === "services" ? <>
          <nav aria-label="Filtrer les services" className={`site-filter-list ${section.title || section.label || section.body || (section.linkLabel && section.linkUrl) ? "site-section-content" : ""}`}>
            {[{ key: "tous", label: text("filter_all", "Tous") }, ...serviceFamilies].map(({ key, label }) =>
              <Link key={key} href={`?filtre=${key}#${section.id}`} aria-current={(filter.filtre ?? "tous") === key ? "true" : undefined}
                className={`pill ${(filter.filtre ?? "tous") === key ? "pill-active" : ""}`}>{label}</Link>)}
          </nav>
          <div className="site-section-content site-service-families">
            {serviceFamilies.map((family, index) => {
              const services = filteredServices.filter((service) => service.group === family.key);
              return services.length ? <div className="site-service-family" key={family.key} data-family={family.key}>
                <div className="site-service-family-heading">
                  <p className="site-card-index">0{index + 1} / {services.length} {text("services_unit", "prestation")}{services.length > 1 ? "s" : ""}</p>
                  <h3>{family.title}</h3>
                  <p>{family.description}</p>
                </div>
                <ul className="site-service-list">
                  {services.map((service) => <li className="site-service-row" key={service.id}>
                    <div className="site-service-row-copy">
                      <h4>{service.name}</h4>
                      {service.description && service.description !== "À COMPLÉTER" ? <p>{service.description}</p> : null}
                      {service.items.some((item) => item && item !== "À COMPLÉTER") ? <ul>{service.items.filter((item) => item && item !== "À COMPLÉTER").map((item, itemIndex) => <li key={itemIndex}>{item}</li>)}</ul> : null}
                    </div>
                    {ctaUrl && ctaLabel ? <Link href={ctaUrl === "/contact#devis" ? `/contact?service=${encodeURIComponent(service.name)}#devis` : ctaUrl}
                      className="site-text-link" aria-label={`${ctaLabel} : ${service.name}`}>{ctaLabel}<span aria-hidden="true">↗</span></Link> : null}
                  </li>)}
                </ul>
              </div> : null;
            })}
            {!filteredServices.length ? <p role="status">{text("services_empty", "Aucune prestation ne correspond à ce filtre. Sélectionnez « Tous » pour retrouver nos services.")}</p> : null}
          </div>
          {servicePhotos.length && data.settings.show_service_photos !== "0" ? <div className="site-service-projects">
            <div className="site-service-projects-heading"><p className="site-card-index">{text("services_photo_kicker", "Du savoir-faire au résultat")}</p>
              <h3>{text("services_photo_title", "Des prestations qui prennent vie.")}</h3></div>
            <div className="site-service-projects-grid">
              {servicePhotos.map((service) => {
                const project = data.realisationCategories.find((category) => service.mediaAssetId !== null && category.gallery.includes(service.mediaAssetId));
                return <figure className="site-service-project" key={service.mediaAssetId}>
                  <CategoryGallery settings={data.settings} title={service.name} photos={service.mediaAssetId ? [data.media[String(service.mediaAssetId)]] : []} />
                  <figcaption><span>{service.name}</span>
                    {project && realisationsPage ? <Link href={`/${realisationsPage.slug}#${project.slug}`} className="site-text-link"
                      aria-label={`${text("project_view", "Voir la réalisation")} : ${project.title}`}>{text("project_view", "Voir la réalisation")} <span aria-hidden="true">↗</span></Link> : null}</figcaption>
                </figure>;
              })}
            </div>
          </div> : null}
        </> : null}

        {coversPresentation && realisationsPage ? (
          <div className="site-section-content site-project-previews">
            {data.realisationCategories.filter((category) => category.gallery.some((id) => data.media[String(id)])).map((category, index) => {
              const coverId = category.gallery.find((id) => data.media[String(id)]);
              return <Link className="site-project-preview" key={category.id} href={`/${realisationsPage.slug}#${category.slug}`}>
                {coverId ? image(coverId, "site-project-cover") : null}
                <div><span className="site-card-index">0{index + 1} / Réalisation</span><span aria-hidden="true">↗</span></div>
                <h3>{category.title}</h3><p>{text("project_discover", "Voir le projet")} · {category.gallery.length} {text("photo_unit", "photo")}{category.gallery.length > 1 ? "s" : ""}</p>
              </Link>;
            })}
          </div>
        ) : type === "realisations" ? <>
          <nav aria-label="Filtrer les réalisations" className="site-section-content site-filter-list">
            <Link href={`?service=#${section.id}`} className={`pill ${!filter.service ? "pill-active" : ""}`}>{text("filter_all", "Tous")}</Link>
            {[...new Set(data.realisationCategories.map((category) => category.serviceName).filter(Boolean))].map((service) =>
              <Link key={service} href={`?service=${encodeURIComponent(service!)}#${section.id}`} className={`pill ${filter.service === service ? "pill-active" : ""}`}>{service}</Link>)}
          </nav>
          <div className="site-section-content site-project-list">{data.realisationCategories.filter((category) => !filter.service || category.serviceName === filter.service).map((category, index) => (
            <article key={category.id} id={category.slug} className="site-project scroll-mt-28">
              <div className="site-project-copy"><p className="site-card-index">{String(index + 1).padStart(2, "0")} / {text("project_unit", "Projet")}</p><h3>{category.title}</h3>
                <p>{category.description}</p>
                {category.serviceName ? <span className="site-project-tag">{category.serviceName}</span> : null}</div>
              {category.gallery.length ? <CategoryGallery settings={data.settings} title={category.title} photos={category.gallery.flatMap((id) => data.media[String(id)] ? [data.media[String(id)]] : [])} /> : null}
            </article>
          ))}</div>
        </> : null}

        {type === "reviews" ? <div className="site-section-content site-review-grid">{data.reviews.map((review, index) => (
          <figure className="site-review" key={index}>
            <p aria-label={`${review.rating} sur 5`} className="site-review-rating">{"★".repeat(review.rating)}{"☆".repeat(5 - review.rating)}</p>
            <blockquote>{review.message}</blockquote>
            <figcaption><strong>{review.authorName}</strong><span>{review.city} / {review.projectType}</span></figcaption>
          </figure>
        ))}</div> : null}
        {type === "faq" ? <div className="site-section-content site-faq-list">{data.faq.map((item, index) =>
          <details key={index}><summary><span>{String(index + 1).padStart(2, "0")}</span>{item.question}</summary>
            <p>{item.answer}</p></details>)}</div> : null}
        {type === "partners" ? <PartnerCarousels partners={data.partners} media={data.media} /> : null}
        {type === "certifications" ? <div className="site-section-content site-partner-grid">
          {data.certifications.map((item, index) => {
            const href = item.verificationUrl;
            return <article key={index} className="site-partner">
              {image(item.logoMediaId, "site-partner-image aspect-[3/2] w-full object-contain")}
              <h3>{item.name}</h3>
              <p>{item.description}</p>
              {href ? <Link className="site-text-link" href={href}>{text("learn_more", "En savoir plus")}<span aria-hidden="true">↗</span></Link> : null}
            </article>;
          })}
        </div> : null}
        {type === "quote" ? <div className="site-section-content site-quote-panel">{process.env.NEXT_PUBLIC_DEMO_MODE === "1"
          ? <p role="status">Version de démonstration : le formulaire sera disponible lors de la mise en ligne du site. Aucune demande ne peut être envoyée ici.</p>
          : data.services.length
          ? <QuoteForm settings={data.settings} preselectedService={filter.service} services={data.services.map((service) => service.name)} />
          : <p role="status" className="text-sm leading-7">{text("form_unavailable", "Le formulaire de devis est momentanément indisponible. Vous pouvez nous joindre directement aux coordonnées indiquées sur le site.")}</p>}</div> : null}
        {type === "contact" ? <div className="site-section-content site-contact-grid">
          <address className="site-contact-details">
            <p className="site-contact-name">{data.settings.company_name}</p>
            {data.settings.company_address && data.settings.company_address !== "À COMPLÉTER" ? <p>{data.settings.company_address}</p> : null}
            {data.settings.company_hours && data.settings.company_hours !== "À COMPLÉTER" ? <p>{data.settings.company_hours}</p> : null}
            {data.settings.company_phone && data.settings.company_phone !== "À COMPLÉTER" ? <p><a href={`tel:${data.settings.company_phone}`}>{data.settings.company_phone}</a></p> : null}
            {data.settings.company_email && data.settings.company_email !== "À COMPLÉTER" ? <p><a href={`mailto:${data.settings.company_email}`}>{data.settings.company_email}</a></p> : null}
            <p>{data.settings.company_intervention_area}</p>
          </address>
          {data.settings.map_url !== "" ? <div className="site-contact-map"><p>{text("map_notice", "La carte se charge uniquement à votre demande.")}</p><MapReveal settings={data.settings} /></div> : null}
        </div> : null}
        {type === "announcements" ? <div className="site-section-content site-announcement-grid">{data.announcements.map((announcement) => (
          <article key={announcement.id} className="site-announcement">
            {image(announcement.mediaAssetId, "site-announcement-image aspect-[4/3] w-full object-cover")}
            <p className="mb-3 font-mono text-xs uppercase text-[var(--terre-cuite)]">{text(`announcement_${announcement.type}`, announcementTypes[announcement.type])}</p>
            <h3>{announcement.title}</h3>
            <p>{announcement.body}</p>
            {announcement.linkUrl ? <Link href={announcement.linkUrl} className="btn-secondary mt-5">{announcement.linkLabel || text("learn_more", "En savoir plus")}</Link> : null}
          </article>
        ))}</div> : null}
      </section>
    );
  })}</>;
}
