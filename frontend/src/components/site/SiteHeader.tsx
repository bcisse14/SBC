"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useEffect, useRef, useState } from "react";
import { siteText, type SiteSettings } from "@/lib/site-customization";

export function SiteHeader({ topAnnouncement, navItems, companyName, ctaLabel, ctaUrl, settings = {} }: {
  settings?: SiteSettings;
  topAnnouncement?: string | null;
  navItems: Array<{ href: string; label: string }>;
  companyName: string;
  ctaLabel: string;
  ctaUrl: string;
}) {
  const pathname = usePathname();
  const [scrolled, setScrolled] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);
  const menuButton = useRef<HTMLButtonElement>(null);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 16);
    window.addEventListener("scroll", onScroll);
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  return (
    <header className={`site-header sticky top-0 z-40 ${scrolled ? "is-scrolled" : ""}`} onKeyDown={(event) => {
      if (event.key === "Escape" && menuOpen) {
        setMenuOpen(false);
        menuButton.current?.focus();
      }
    }}>
      {topAnnouncement ? (
        <div className="site-announcement-bar">
          {topAnnouncement}
        </div>
      ) : null}

      <div className="site-header-bar">
        <div className="site-shell site-header-inner">
          <Link href="/" className="site-brand" aria-label={`${companyName}, accueil`} onClick={() => setMenuOpen(false)}>
            {settings.brand_logo_id ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={`/api/media/${settings.brand_logo_id}`} className="site-brand-logo" alt={companyName} />
            ) : <>{companyName}<span>.</span></>}
          </Link>

          <nav aria-label="Navigation principale" className="site-desktop-nav">
            {navItems.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                aria-current={pathname === item.href || (item.href !== "/" && pathname.startsWith(`${item.href}/`)) ? "page" : undefined}
              >
                {item.label}
              </Link>
            ))}
          </nav>

          {ctaUrl && ctaLabel ? <Link href={ctaUrl} className="btn-primary site-header-cta">{ctaLabel}<span aria-hidden="true">↗</span></Link> : null}
          <button
            ref={menuButton}
            type="button"
            className="site-menu-toggle"
            aria-expanded={menuOpen}
            aria-controls="mobile-menu"
            onClick={() => setMenuOpen(!menuOpen)}
          >
            <span>{menuOpen ? siteText(settings, "menu_close", "Fermer") : siteText(settings, "menu_open", "Menu")}</span>
            <span className={`site-menu-icon ${menuOpen ? "is-open" : ""}`} aria-hidden="true"><i /><i /></span>
          </button>
        </div>
        {menuOpen ? (
          <nav id="mobile-menu" aria-label="Navigation mobile" className="site-mobile-menu">
            <div className="site-shell">
              {navItems.map((item, index) => (
                <Link
                  key={item.href}
                  href={item.href}
                  aria-current={pathname === item.href || (item.href !== "/" && pathname.startsWith(`${item.href}/`)) ? "page" : undefined}
                  onClick={() => setMenuOpen(false)}
                >
                  <span>{String(index + 1).padStart(2, "0")}</span>{item.label}<span aria-hidden="true">↗</span>
                </Link>
              ))}
            </div>
          </nav>
        ) : null}
      </div>
    </header>
  );
}
