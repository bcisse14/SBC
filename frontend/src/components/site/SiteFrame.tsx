import type { ReactNode } from "react";
import Link from "next/link";
import type { SiteBootstrap } from "@/lib/types";
import { SiteHeader } from "@/components/site/SiteHeader";
import { SiteFooter } from "@/components/site/SiteFooter";
import { siteText, siteTheme } from "@/lib/site-customization";

export function SiteFrame({ data, children }: { data: SiteBootstrap; children: ReactNode }) {
  const navItems = data.pages.filter((page) => page.showInNavigation).map((page) => ({ href: `/${page.slug}`, label: page.title }));
  const legalItems = data.pages.filter((page) => page.showInFooter ?? ["mentions-legales", "politique-confidentialite"].includes(page.slug))
    .map((page) => ({ href: `/${page.slug}`, label: page.title }));
  const companyName = data.settings.company_name ?? "SBC";
  const ctaLabel = data.settings.cta_label ?? "Demander un devis";
  const ctaUrl = data.settings.cta_url ?? "/contact#devis";
  return <div className="public-site site-customized" style={siteTheme(data.settings)} data-density={data.settings.theme_density} data-sticky-header={data.settings.sticky_header} data-text-size={data.settings.theme_text_size}>
    <a className="site-skip-link" href="#main-content">{siteText(data.settings, "skip_link", "Aller au contenu")}</a>
    {data.settings.show_header !== "0" ? <SiteHeader settings={data.settings} topAnnouncement={data.settings.announcement_top} navItems={navItems} companyName={companyName} ctaLabel={ctaLabel} ctaUrl={ctaUrl} /> : null}
    {children}
    {data.settings.show_footer !== "0" ? <SiteFooter settings={data.settings}
      company={{ phone: data.settings.company_phone, email: data.settings.company_email, area: data.settings.company_intervention_area, name: companyName }}
      navItems={navItems} legalItems={legalItems}
      titles={{ contact: data.settings.footer_contact_title ?? "Coordonnées", pages: data.settings.footer_pages_title ?? "Pages", legal: data.settings.footer_legal_title ?? "Informations" }}
    /> : null}
    {data.settings.show_mobile_contact !== "0" && data.settings.company_phone && data.settings.company_phone !== "À COMPLÉTER" ? <div className="fixed bottom-4 left-1/2 z-40 flex w-[calc(100%-1.5rem)] max-w-md -translate-x-1/2 gap-2 border border-[var(--beton)] bg-[var(--papier)] p-2 lg:hidden">
      <a href={`tel:${data.settings.company_phone}`} className="btn-secondary flex-1 text-center">{siteText(data.settings, "call_label", "Appeler")}</a>
      {ctaUrl && ctaLabel ? <Link href={ctaUrl} className="btn-primary flex-1 text-center">{ctaLabel}</Link> : null}
    </div> : null}
  </div>;
}
