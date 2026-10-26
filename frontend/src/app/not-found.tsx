import Link from "next/link";

export default function NotFound() {
  return (
    <main className="site-shell flex min-h-[70vh] flex-col items-start justify-center py-16">
      <p className="font-mono text-xs uppercase tracking-[0.2em] text-[var(--terre-cuite-fonce)]">404</p>
      <h1 className="mt-2 font-serif text-7xl leading-none">Page non trouvee</h1>
      <p className="mt-4 max-w-lg text-sm">Le contenu demande est introuvable ou indisponible.</p>
      <Link href="/" className="btn-primary mt-6">Retour a l accueil</Link>
    </main>
  );
}
