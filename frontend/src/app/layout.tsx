import type { Metadata } from "next";
import "@fontsource/instrument-serif";
import "@fontsource/instrument-serif/400-italic.css";
import "@fontsource/hanken-grotesk/400.css";
import "@fontsource/hanken-grotesk/500.css";
import "@fontsource/hanken-grotesk/700.css";
import "@fontsource/dm-mono/400.css";
import "./globals.css";
import "./site-customization.css";
import "./admin-studio.css";

export const metadata: Metadata = {
  title: {
    default: "SBC, rénovation intérieure et extérieure",
    template: "%s | SBC",
  },
  description: "SBC, entrepreneur général en rénovation dans la région parisienne.",
  metadataBase: new URL(process.env.NEXT_PUBLIC_SITE_URL || "http://localhost:3000"),
  openGraph: {
    type: "website",
    locale: "fr_FR",
    title: "SBC",
    description: "Rénovation intérieure et extérieure en région parisienne.",
  },
  robots: {
    index: true,
    follow: true,
  },
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="fr" className="h-full antialiased">
      <body id="top" className="min-h-full flex flex-col">{children}</body>
    </html>
  );
}
