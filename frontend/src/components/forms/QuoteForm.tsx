"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { submitQuote } from "@/lib/api";
import { TechnicalIllustration } from "@/components/site/TechnicalIllustration";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { siteText, type SiteSettings } from "@/lib/site-customization";

const schema = z.object({
  propertyType: z.string().min(1),
  requestedServices: z.array(z.string()).min(1),
  description: z.string().min(20),
  city: z.string().min(2),
  postalCode: z.string().regex(/^[0-9]{5}$/),
  budgetRange: z.string().min(1),
  timeline: z.string().min(1),
  visitRequested: z.boolean(),
  firstName: z.string().min(1),
  lastName: z.string().min(1),
  email: z.string().email(),
  phone: z.string().min(8),
  callbackTime: z.string().optional(),
  consent: z.literal(true),
  website: z.string().optional(),
  photos: z.any().optional(),
});

type FormValues = z.infer<typeof schema>;

const serviceChoices = [
  "Cuisine",
  "Salle de bain",
  "Peinture",
  "Carrelage et revêtements",
  "Doublage et isolation",
  "Plomberie et robinetterie",
  "Électricité",
  "Meubles de rangement",
  "Façades",
  "Toitures",
  "Terrasses",
  "Aménagement et agrandissement",
];

export function QuoteForm({ preselectedService, services = serviceChoices, settings = {} }: { preselectedService?: string; services?: string[]; settings?: SiteSettings }) {
  const text = (key: string, fallback: string) => siteText(settings, `form_${key}`, fallback);
  const [step, setStep] = useState(1);
  const [resultMessage, setResultMessage] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    trigger,
    watch,
    setValue,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      propertyType: "",
      requestedServices: preselectedService ? [preselectedService] : [],
      description: "",
      city: "",
      postalCode: "",
      budgetRange: "",
      timeline: "",
      visitRequested: false,
      firstName: "",
      lastName: "",
      email: "",
      phone: "",
      callbackTime: "",
      consent: false as never,
      website: "",
      photos: undefined,
    },
    mode: "onTouched",
  });

  const selectedServices = watch("requestedServices") ?? [];

  function toggleService(label: string) {
    if (selectedServices.includes(label)) {
      setValue(
        "requestedServices",
        selectedServices.filter((v) => v !== label),
        { shouldValidate: true, shouldTouch: true },
      );
      return;
    }

    setValue("requestedServices", [...selectedServices, label], { shouldValidate: true, shouldTouch: true });
  }

  async function onSubmit(values: FormValues) {
    const response = await submitQuote(values);
    setResultMessage(response.message);
  }

  async function goToNextStep() {
    const fieldsByStep: Record<number, Array<keyof FormValues>> = {
      1: ["propertyType", "requestedServices", "description"],
      2: ["city", "postalCode", "budgetRange", "timeline"],
    };
    const valid = await trigger(fieldsByStep[step], { shouldFocus: true });
    if (valid) {
      setStep((value) => value + 1);
    }
  }

  const progress = `${Math.round((step / 3) * 100)}%`;

  return (
    <form id="devis" onSubmit={handleSubmit(onSubmit)} className="space-y-6" noValidate>
      <div className="space-y-2">
        <div className="flex items-center justify-between text-xs font-mono uppercase tracking-[0.18em]">
          <span>{text("progress", "Progression")}</span>
          <span>{progress}</span>
        </div>
        <div className="h-2 bg-[var(--beton)]">
          <div className="h-full bg-[var(--terre-cuite)] transition-all duration-500" style={{ width: progress }} />
        </div>
      </div>

      <input type="text" className="hidden" tabIndex={-1} autoComplete="off" {...register("website")} />

      {step === 1 && (
        <section className="space-y-4">
          <h3 className="font-serif text-3xl">1. {text("step_project", "Projet")}</h3>
          <div className="field-label">
            {text("property_type", "Type de bien")}
            <div className="grid gap-2 sm:grid-cols-3">
              {(["maison", "appartement", "local"] as const).map((type) => (
                <label key={type} className={`cursor-pointer border p-3 ${watch("propertyType") === type ? "border-[var(--terre-cuite)] bg-[var(--papier)]" : "border-[var(--beton)]"}`}>
                  <input className="sr-only" type="radio" value={type} {...register("propertyType")} />
                  <TechnicalIllustration kind={type} label={type === "maison" ? text("house", "Maison") : type === "appartement" ? text("apartment", "Appartement") : text("commercial", "Local")} />
                </label>
              ))}
            </div>
          </div>
          {errors.propertyType ? <p className="field-error">{text("error_property", "Choisissez un type de bien.")}</p> : null}
          <fieldset>
            <legend className="field-label mb-2">{text("services", "Prestations souhaitées")}</legend>
            <div className="grid gap-2 sm:grid-cols-2">
              {services.map((service) => (
                <label key={service} className="flex items-center gap-2 border border-[var(--beton)] px-3 py-2 text-sm">
                  <input
                    type="checkbox"
                    checked={selectedServices.includes(service)}
                    onChange={() => toggleService(service)}
                    aria-label={service}
                  />
                  <span>{service}</span>
                </label>
              ))}
            </div>
          </fieldset>
          {errors.requestedServices ? <p className="field-error">{text("error_services", "Choisissez au moins une prestation.")}</p> : null}
          <label className="field-label">
            {text("description", "Description du projet")}
            <textarea className="field-input min-h-32" {...register("description")} />
          </label>
          {errors.description ? <p className="field-error">{text("error_description", "Décrivez votre projet en 20 caractères minimum.")}</p> : null}
        </section>
      )}

      {step === 2 && (
        <section className="space-y-4">
          <h3 className="font-serif text-3xl">2. {text("step_details", "Détails")}</h3>
          <label className="field-label">
            {text("city", "Ville")}
            <input className="field-input" {...register("city")} />
          </label>
          {errors.city ? <p className="field-error">{text("error_city", "Indiquez une ville.")}</p> : null}
          <label className="field-label">
            {text("postal_code", "Code postal")}
            <input className="field-input" inputMode="numeric" {...register("postalCode")} />
          </label>
          {errors.postalCode ? <p className="field-error">{text("error_postal", "Indiquez un code postal à 5 chiffres.")}</p> : null}
          <label className="field-label">
            {text("budget", "Budget indicatif")}
            <select className="field-input" {...register("budgetRange")}>
              <option value="">{text("select", "Sélectionnez")}</option>
              <option value="moins_15k">{text("budget_low", "Moins de 15 000 EUR")}</option>
              <option value="15k_40k">{text("budget_medium", "15 000 EUR à 40 000 EUR")}</option>
              <option value="40k_80k">{text("budget_high", "40 000 EUR à 80 000 EUR")}</option>
              <option value="plus_80k">{text("budget_large", "Plus de 80 000 EUR")}</option>
            </select>
          </label>
          <label className="field-label">
            {text("timeline", "Délai souhaité")}
            <select className="field-input" {...register("timeline")}>
              <option value="">{text("select", "Sélectionnez")}</option>
              <option value="rapide">{text("timeline_fast", "Dans 1 à 2 mois")}</option>
              <option value="moyen">{text("timeline_medium", "Dans 3 à 6 mois")}</option>
              <option value="souple">{text("timeline_flexible", "Calendrier souple")}</option>
            </select>
          </label>
          <label className="flex items-center gap-3 text-sm">
            <input type="checkbox" {...register("visitRequested")} />
            {text("visit", "Je souhaite une visite sur place")}
          </label>
          <label className="field-label">
            {text("photos", "Photos du projet, 5 maximum")}
            <input className="field-input" type="file" accept="image/jpeg,image/png,image/webp" multiple {...register("photos")} />
            <span className="text-xs text-[var(--terre-cuite-fonce)]">{text("photos_help", "JPG, PNG ou WebP, 8 Mo maximum par fichier.")}</span>
          </label>
        </section>
      )}

      {step === 3 && (
        <section className="space-y-4">
          <h3 className="font-serif text-3xl">3. {text("step_contact", "Coordonnées")}</h3>
          <div className="grid gap-4 sm:grid-cols-2">
            <label className="field-label">
              {text("first_name", "Prénom")}
              <input className="field-input" {...register("firstName")} />
            </label>
            <label className="field-label">
              {text("last_name", "Nom")}
              <input className="field-input" {...register("lastName")} />
            </label>
          </div>
          <label className="field-label">
            {text("email", "E-mail")}
            <input className="field-input" type="email" {...register("email")} />
          </label>
          <label className="field-label">
            {text("phone", "Téléphone")}
            <input className="field-input" type="tel" {...register("phone")} />
          </label>
          <label className="field-label">
            {text("callback", "Moment préféré pour être rappelé")}
            <input className="field-input" {...register("callbackTime")} />
          </label>
          <label className="flex items-start gap-3 text-sm">
            <input type="checkbox" {...register("consent")} />
            <span>{text("consent", "J’accepte que mes données soient utilisées pour traiter ma demande de devis.")}</span>
          </label>
        </section>
      )}

      {Object.values(errors).length > 0 ? (
        <p className="text-sm text-[var(--terre-cuite-fonce)]">{text("required", "Merci de vérifier les champs obligatoires.")}</p>
      ) : null}

      {resultMessage ? (
        <div className="border border-[var(--terre-cuite)] bg-[var(--papier)] p-4 text-sm">{resultMessage}</div>
      ) : null}

      <div className="flex flex-wrap gap-3">
        {step > 1 ? (
          <button type="button" className="btn-secondary" onClick={() => setStep((v) => v - 1)}>
            {text("previous", "Étape précédente")}
          </button>
        ) : null}

        {step < 3 ? (
          <button type="button" className="btn-primary" onClick={goToNextStep}>
            {text("next", "Étape suivante")}
          </button>
        ) : (
          <button type="submit" className="btn-primary" disabled={isSubmitting}>
            {isSubmitting ? text("sending", "Envoi en cours...") : text("send", "Envoyer la demande")}
          </button>
        )}
      </div>
    </form>
  );
}
