export type CmsSectionType = "hero" | "text" | "gallery" | "cards" | "services" | "realisations" | "reviews" | "faq" | "partners" | "certifications" | "quote" | "contact" | "announcements";

export type CmsSection = {
  id: string;
  type: CmsSectionType;
  title: string;
  label: string;
  body: string;
  hidden: boolean;
  mediaAssetId: number | null;
  photoSlotKey: string;
  linkLabel: string;
  linkUrl: string;
  gallery: number[];
  cards: Array<{ title: string; body: string }>;
  layout?: "auto" | "split" | "stacked" | "image-left";
  tone?: "default" | "soft" | "dark";
  spacing?: "default" | "compact" | "airy";
  alignment?: "left" | "center";
  width?: "full" | "narrow";
  columns?: "auto" | "1" | "2" | "3" | "4";
  imageFit?: "cover" | "contain";
  hideImage?: boolean;
  presentation?: "auto" | "cards" | "steps" | "families" | "catalog" | "galleries";
};

export type CmsPage = {
  id: number;
  title: string;
  slug: string;
  seoDescription: string;
  showInNavigation: boolean;
  showInFooter: boolean;
  sections: CmsSection[];
  sortOrder: number;
  isPublished: boolean;
};

export type Announcement = {
  id: number;
  title: string;
  slug: string;
  type: "news" | "offer" | "job" | "information";
  body: string;
  mediaAssetId: number | null;
  linkUrl: string;
  linkLabel: string;
  startsAt: string;
  endsAt: string;
};

export type AdminMedia = {
  id: number;
  originalName: string;
  mimeType: string;
  altText: string | null;
  width: number | null;
  height: number | null;
  url: string;
  usage: string[];
};

export type FieldDefinition = {
  label: string;
  type: "text" | "textarea" | "slug" | "url" | "boolean" | "blocks" | "media" | "gallery" | "service" | "select" | "number" | "lines" | "datetime";
  required: boolean;
  readonly?: boolean;
  min?: number;
  max?: number;
  options?: Record<string, string>;
};

export type ResourceDefinition = {
  label: string;
  fixed?: boolean;
  fields: Record<string, FieldDefinition>;
};

export type CmsSchema = {
  resources: Record<string, ResourceDefinition>;
  blocks: Record<CmsSectionType, string>;
  photoSlots: Array<{ slotKey: string; idealDescription: string }>;
};

export type CmsValue = string | number | boolean | null | string[] | number[] | CmsSection[];
export type CmsRecord = { id?: number; [key: string]: CmsValue | undefined };
