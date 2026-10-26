import type { CSSProperties } from "react";

export type SiteSettings = Record<string, string | null>;

export function siteText(settings: SiteSettings, key: string, fallback = "") {
  return (settings[key] ?? fallback).replace(/\{([a-z_]+)\}/g, (token, name: string) => settings[name] ?? token);
}

export function siteTheme(settings: SiteSettings): CSSProperties {
  const style: Record<string, string> = {};
  const colors = { paper: "--papier", surface: "--platre", ink: "--encre", accent: "--terre-cuite", accent_dark: "--terre-cuite-fonce", border: "--beton", hero: "--hero-background", hero_text: "--hero-foreground" };
  for (const [key, variable] of Object.entries(colors)) {
    const value = settings[`theme_${key}`];
    if (value && /^#[0-9a-f]{6}$/i.test(value)) style[variable] = value;
  }
  const numbers = { width: [960, 1600], radius: [0, 40], button_radius: [0, 999], text_size: [14, 20], hero_height: [400, 850], hero_split: [35, 65] };
  for (const [key, [min, max]] of Object.entries(numbers)) {
    const value = settings[`theme_${key}`];
    if (value && /^\d+$/.test(value) && Number(value) >= min && Number(value) <= max) {
      style[`--site-${key.replaceAll("_", "-")}`] = `${value}${key === "hero_split" ? "%" : "px"}`;
    }
  }
  const fonts: Record<string, string> = { serif: '"Instrument Serif", Georgia, serif', sans: '"Hanken Grotesk", sans-serif', classic: "Georgia, serif" };
  for (const key of ["heading", "body"]) {
    const font = settings[`theme_${key}_font`];
    if (font && fonts[font]) style[`--site-${key}-font`] = fonts[font];
  }
  return style;
}
