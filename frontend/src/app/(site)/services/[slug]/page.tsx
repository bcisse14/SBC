import Link from "next/link";
import { notFound } from "next/navigation";
import { getSiteBootstrap } from "@/lib/api";
import { CategoryGallery } from "@/components/site/CategoryGallery";
import { PhotoPlaceholder, parseDescription, photosOf } from "@/components/site/Expertise";

type Props = { params: Promise<{ slug: string }> };

export async function generateMetadata({ params }: Props) {
  const { slug } = await params;
  const category = (await getSiteBootstrap()).realisationCategories.find((item) => item.slug === slug);
  return category ? { title: category.title, description: category.summary || undefined } : {};
}

export default async function ExpertisePage({ params }: Props) {
  const { slug } = await params;
  const data = await getSiteBootstrap();
  const index = data.realisationCategories.findIndex((item) => item.slug === slug);
  if (index < 0) notFound();
  const category = data.realisationCategories[index];
  const photos = photosOf(category, data);
  const others = data.realisationCategories.filter((item) => item.slug !== slug);
  const ctaUrl = data.settings.cta_url ?? "/contact#devis";
  const ctaLabel = data.settings.cta_label ?? "Demander un devis";
  const phone = data.settings.company_phone && data.settings.company_phone !== "À COMPLÉTER" ? data.settings.company_phone : null;
  const blocks = parseDescription(category.description);

  return (
    <main id="main-content" tabIndex={-1} className="site-page expertise-page">
      <section className="site-shell expertise-hero">
        <nav aria-label="Fil d’Ariane" className="expertise-breadcrumb">
          <Link href="/services">Services</Link><span aria-hidden="true">/</span><span aria-current="page">{category.title}</span>
        </nav>
        <p className="site-section-kicker"><span>{String(index + 1).padStart(2, "0")}</span><span>Expertise</span></p>
        <h1 className="site-hero-title">{category.title}</h1>
        {category.summary ? <p className="expertise-lead">{category.summary}</p> : null}
        <div className="site-hero-actions">
          {ctaUrl && ctaLabel ? <Link className="btn-primary" href={ctaUrl}>{ctaLabel}</Link> : null}
          <Link className="site-text-link" href="/services">Toutes nos expertises <span aria-hidden="true">↗</span></Link>
        </div>
      </section>

      <section className="site-shell expertise-body">
        <div className="expertise-prose">
          {blocks.map((block, blockIndex) => block.type === "heading" ? <h2 key={blockIndex}>{block.text}</h2>
            : block.type === "list" ? <ul key={blockIndex}>{block.items.map((item, itemIndex) => <li key={itemIndex}>{item}</li>)}</ul>
            : <p key={blockIndex}>{block.text}</p>)}
        </div>
        <aside className="expertise-aside" aria-label="Contact">
          <p className="site-card-index">Votre projet</p>
          <h2>Parlons de vos travaux</h2>
          <p>Décrivez votre besoin, nous revenons vers vous avec une proposition adaptée.</p>
          {ctaUrl && ctaLabel ? <Link className="btn-primary" href={ctaUrl}>{ctaLabel}</Link> : null}
          {phone ? <a className="site-text-link" href={`tel:${phone}`}>{phone}</a> : null}
        </aside>
      </section>

      <section className="site-shell expertise-gallery" aria-labelledby="expertise-gallery-title">
        <h2 id="expertise-gallery-title" className="site-section-title">Nos réalisations</h2>
        {photos.length ? <CategoryGallery settings={data.settings} title={category.title} photos={photos} />
          : <div className="expertise-placeholders">
            {[1, 2, 3].map((number) => <PhotoPlaceholder key={number} label={`Photo ${number} à venir`} hint={`Réalisation : ${category.title}`} />)}
          </div>}
      </section>

      {others.length ? <section className="site-shell expertise-others" aria-labelledby="expertise-others-title">
        <h2 id="expertise-others-title" className="site-section-title">Nos autres expertises</h2>
        <ul>
          {others.map((item) => <li key={item.id}><Link href={`/services/${item.slug}`}>{item.title}<span aria-hidden="true">↗</span></Link></li>)}
        </ul>
      </section> : null}
    </main>
  );
}
