import { SiteBootstrap, QuotePayload } from "@/lib/types";

const fallback: SiteBootstrap = {
  pages: [],
  announcements: [],
  settings: {
    company_name: "SBC",
    company_phone: "À COMPLÉTER",
    company_email: "À COMPLÉTER",
    company_address: "À COMPLÉTER",
    company_hours: "À COMPLÉTER",
    company_intervention_area: "Région parisienne",
    slogan_primary: "Avec SBC, vos projets se réalisent avec style et fiabilité",
    slogan_secondary: "SBC, la qualité, la durabilité, le service sur mesure",
    experience_years: "25",
    show_photo_markers: "1",
  },
  content: {
    "home.hero.title": "Votre vision, notre expertise. Nous transformons vos espaces intérieurs et extérieurs avec audace et précision.",
    "home.hero.subtitle": "Que ce soit pour un coup de pinceau frais ou une métamorphose complète, nous donnons vie à vos rêves.",
    "home.intro.p1": "Chez SBC, nous réalisons des projets de rénovation de petite et grande envergure depuis plus de {experience_years} ans.",
    "home.intro.p2": "Service clé en main de la gestion à la réalisation.",
    "home.intro.p3": "Peinture, carrelage, isolation, plomberie, électricité et plus.",
    "home.interior.title": "Rénovation intérieure complète",
    "home.interior.tagline": "Des murs qui parlent, des espaces qui respirent.",
    "home.interior.body": "Transformation élégante et fonctionnelle de votre intérieur.",
    "home.exterior.title": "Rénovation extérieure moderne",
    "home.exterior.body": "Solutions sur mesure pour terrasses et façades.",
    "home.exterior.focus": "Façades / toitures : une nouvelle jeunesse pour votre maison.",
    "home.consultation": "Consultation sur place et estimation adaptée à votre budget.",
    "about.company": "SBC accompagne les particuliers en rénovation intérieure et extérieure.",
    "realisations.intro": "Les projets seront présentés avec contexte et visuels avant / après.",
    "partners.intro": "Sélection de partenaires selon la fiabilité et la qualité des prestations.",
  },
  services: [],
  realisationCategories: [],
  media: {},
  reviews: [],
  certifications: [],
  partners: [],
  faq: [],
  photoSlots: [],
};

function normalize(content: Record<string, string>, years: string): Record<string, string> {
  const out: Record<string, string> = {};
  for (const [key, value] of Object.entries(content)) {
    out[key] = value.replaceAll("{experience_years}", years);
  }
  return out;
}

export async function getSiteBootstrap(): Promise<SiteBootstrap> {
  const baseUrl = process.env.API_BASE_URL ?? process.env.NEXT_PUBLIC_API_BASE_URL ?? "http://localhost:8080";

  const response = await fetch(`${baseUrl}/api/site/bootstrap`, {
    cache: "no-store",
    headers: { Accept: "application/json" },
  });

  if (!response.ok) {
    console.error("Impossible de charger les contenus du site.", response.status);
    throw new Error("Le site est temporairement indisponible. Veuillez réessayer.");
  }
  const data = (await response.json()) as SiteBootstrap;
  const years = data.settings.experience_years ?? "25";

  return {
    ...fallback,
    ...data,
    content: normalize({ ...fallback.content, ...data.content }, years),
  };
}

export async function submitQuote(payload: QuotePayload): Promise<{ ok: boolean; message: string }> {
  const baseUrl = process.env.NEXT_PUBLIC_API_BASE_URL ?? "";

  const csrfResponse = await fetch(`${baseUrl}/api/admin/csrf`, {
    credentials: "include",
  });
  const csrfData = (await csrfResponse.json()) as { tokens?: { quote?: string } };
  const csrfToken = csrfData.tokens?.quote;

  const formData = new FormData();
  for (const [key, value] of Object.entries(payload)) {
    if (key === "photos" || value === undefined) {
      continue;
    }
    formData.append(key, Array.isArray(value) ? JSON.stringify(value) : String(value));
  }
  if (payload.photos) {
    for (const photo of Array.from(payload.photos)) {
      formData.append("photos[]", photo);
    }
  }

  const response = await fetch(`${baseUrl}/api/quotes`, {
    method: "POST",
    headers: {
      ...(csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {}),
    },
    credentials: "include",
    body: formData,
  });

  const data = (await response.json()) as { message?: string };

  return {
    ok: response.ok,
    message: data.message ?? "Une erreur est survenue.",
  };
}
