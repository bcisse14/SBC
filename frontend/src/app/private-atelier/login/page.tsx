"use client";

import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";
import { adminRequest, errorMessage } from "@/lib/admin-api";

export default function AtelierLoginPage() {
  const router = useRouter();
  const [error, setError] = useState<string>("");
  const [busy, setBusy] = useState(false);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);

    setBusy(true);
    setError("");
    try {
      await adminRequest("/api/admin/login", {
        method: "POST",
        body: JSON.stringify({
          email: String(formData.get("email") || ""),
          password: String(formData.get("password") || ""),
        }),
      });
      router.replace(process.env.NEXT_PUBLIC_ADMIN_PATH || "/atelier-admin");
    } catch (error) {
      setError(errorMessage(error));
    } finally {
      setBusy(false);
    }
  }

  return (
    <main className="cms-login">
      <aside className="cms-login-intro">
        <p className="text-2xl font-bold tracking-tight">SBC <span className="font-normal text-[#b4c9bc]">Studio</span></p>
        <div><p className="mb-6 font-mono text-xs uppercase tracking-[.2em] text-[#c8cdbc]">Votre espace privé</p>
          <p className="font-serif text-6xl leading-[1.05] lg:text-8xl">Le site,<br /><em className="text-[#b4c9bc]">entre vos mains.</em></p>
          <p className="mt-8 max-w-sm text-sm leading-7 text-[#c8cdbc]">Vos pages, vos photos, vos projets. Un seul espace pour faire vivre votre entreprise en ligne.</p>
        </div>
        <p className="text-xs text-[#a7b09f]">STUDIO / GESTION DU SITE SBC</p>
      </aside>
      <section className="flex items-center justify-center px-6 py-16">
        <div className="w-full max-w-sm"><p className="cms-eyebrow">Bienvenue</p><h1 className="mt-4 text-4xl font-medium tracking-tight">Votre espace de création.</h1>
        <p className="mt-4 text-sm text-gray-500">Connectez-vous avec votre compte administrateur.</p>
        <form onSubmit={onSubmit} className="mt-8 space-y-5">
          <label className="field-label">
            E-mail
            <input className="field-input" name="email" type="email" autoComplete="username" required disabled={busy} />
          </label>
          <label className="field-label">
            Mot de passe
            <input className="field-input" name="password" type="password" autoComplete="current-password" required disabled={busy} />
          </label>
          {error ? <p role="alert" className="cms-error">{error}</p> : null}
          <button className="btn-primary w-full" type="submit" disabled={busy}>{busy ? "Connexion…" : "Se connecter"}</button>
        </form>
        <p className="mt-8 text-xs leading-6 text-gray-500">L’accès à cet espace est réservé aux personnes autorisées. Aucune modification n’est publiée sans votre intervention.</p>
        </div>
      </section>
    </main>
  );
}
