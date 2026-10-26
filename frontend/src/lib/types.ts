import type { Announcement, CmsPage } from "@/lib/cms-types";

export type MediaInfo = {
  id: number;
  version?: string;
  alt: string;
  width: number | null;
  height: number | null;
  focalPoint: string | null;
  variants: Array<{ w: number; format: "webp" | "avif" }>;
};

export type RealisationCategory = {
  id: number;
  title: string;
  slug: string;
  description: string;
  serviceId: number | null;
  serviceGroup: string | null;
  serviceName: string | null;
  gallery: number[];
};

export type SiteBootstrap = {
  pages: CmsPage[];
  announcements: Announcement[];
  settings: Record<string, string | null>;
  content: Record<string, string>;
  services: Array<{
    id: number;
    name: string;
    group: string;
    description: string;
    items: string[];
    mediaAssetId: number | null;
  }>;
  realisationCategories: RealisationCategory[];
  media: Record<string, MediaInfo>;
  reviews: Array<{
    authorName: string;
    city: string;
    projectType: string;
    rating: number;
    message: string;
  }>;
  certifications: Array<{
    name: string;
    description: string | null;
    verificationUrl: string | null;
    logoMediaId: number | null;
  }>;
  partners: Array<{
    name: string;
    category: string | null;
    websiteUrl: string | null;
    logoMediaId: number | null;
  }>;
  faq: Array<{ question: string; answer: string }>;
  photoSlots: Array<{
    slotKey: string;
    ratio: string;
    minimumSize: string;
    idealDescription: string;
    mediaAssetId: number | null;
  }>;
};

export type QuotePayload = {
  propertyType: string;
  requestedServices: string[];
  description: string;
  city: string;
  postalCode: string;
  budgetRange: string;
  timeline: string;
  visitRequested: boolean;
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  callbackTime?: string;
  consent: boolean;
  website?: string;
  photos?: FileList;
};
