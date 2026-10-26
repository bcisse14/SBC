type TechnicalIllustrationProps = {
  kind: "cuisine" | "salle-de-bain" | "peinture" | "carrelage" | "isolation" | "electricite" | "maison" | "appartement" | "local";
  label: string;
};

const paths: Record<TechnicalIllustrationProps["kind"], string> = {
  cuisine: "M4 19h16M6 17V7h12v10M8 10h8M9 7V4h6v3M10 13h1M13 13h1M16 13h1",
  "salle-de-bain": "M4 17h16M6 17v3M18 17v3M6 11h12v6H6zM9 11V7a3 3 0 0 1 6 0v1",
  peinture: "M5 5h11v7H5zM16 8h3v11M8 12v7M5 19h14M8 5V3h8v2",
  carrelage: "M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z",
  isolation: "M4 20V6l8-3 8 3v14M4 10h16M8 8v12M12 8v12M16 8v12",
  electricite: "M13 3 5 13h6l-1 8 8-11h-6z",
  maison: "M3 11 12 4l9 7v9H3zM9 20v-6h6v6M16 8h2v3h-2z",
  appartement: "M5 20V4h14v16M8 7h2M14 7h2M8 11h2M14 11h2M8 15h2M14 15h2M10 20v-3h4v3",
  local: "M4 20V8l8-4 8 4v12M7 12h3v3H7zM14 12h3v3h-3zM9 20v-4h6v4",
};

export function TechnicalIllustration({ kind, label }: TechnicalIllustrationProps) {
  return (
    <div className="technical-illustration" aria-label={label} role="img">
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d={paths[kind]} />
      </svg>
      <span>{label}</span>
    </div>
  );
}
