import Link from "next/link";
import { siteText, type SiteSettings } from "@/lib/site-customization";

export function SiteFooter({ company, navItems, legalItems, titles, settings = {} }: {
  settings?: SiteSettings;
  company: { name: string; phone?: string | null; email?: string | null; area?: string | null };
  navItems: Array<{ href: string; label: string }>;
  legalItems: Array<{ href: string; label: string }>;
  titles: { contact: string; pages: string; legal: string };
}) {
  return (
    <footer className="site-footer">
      <div className="site-shell">
        <div className="site-footer-main">
          <div className="site-footer-contact">
            <p className="site-footer-kicker">{siteText(settings, "footer_kicker", "Parlons de votre projet")}</p>
            <h2>{titles.contact}</h2>
            {company.phone && company.phone !== "À COMPLÉTER" ? <a href={`tel:${company.phone}`}>{company.phone}</a> : null}
            {company.email && company.email !== "À COMPLÉTER" ? <a href={`mailto:${company.email}`}>{company.email}</a> : null}
            <p>{company.area ?? "Région parisienne"}</p>
          </div>

          <div>
            <h3>{titles.pages}</h3>
            <ul>
              {navItems.map((item) => <li key={item.href}><Link href={item.href}>{item.label}</Link></li>)}
            </ul>
          </div>

          <div>
            <h3>{titles.legal}</h3>
            <ul>
              {legalItems.map((item) => <li key={item.href}><Link href={item.href}>{item.label}</Link></li>)}
            </ul>
          </div>
        </div>

        <div className="site-footer-bottom">
          <Link href="/" className="site-footer-mark">{settings.brand_logo_id ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={`/api/media/${settings.brand_logo_id}`} className="site-brand-logo" alt={company.name} />
          ) : <>{company.name}<span>.</span></>}</Link>
          <p>{siteText(settings, "footer_signature", "Rénovation, avec justesse.")}</p>
          <a href="https://bafodecisse.fr/">Site réalisé par Karlsefni</a>
          <a href="#top">{siteText(settings, "back_top", "Retour en haut")} ↑</a>
        </div>
      </div>
    </footer>
  );
}
