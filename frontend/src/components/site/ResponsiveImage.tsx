import type { MediaInfo } from "@/lib/types";

type ResponsiveImageProps = {
  media: MediaInfo;
  sizes: string;
  alt?: string;
  className?: string;
  priority?: boolean;
};

function srcSet(id: number, variants: MediaInfo["variants"], format: "webp" | "avif", version?: string) {
  const list = variants.filter((v) => v.format === format).sort((a, b) => a.w - b.w);
  return list.length ? list.map((v) => `/api/media/${id}?w=${v.w}&f=${format}${version ? `&v=${version}` : ""} ${v.w}w`).join(", ") : null;
}

export function ResponsiveImage({ media, sizes, alt, className, priority = false }: ResponsiveImageProps) {
  const avif = srcSet(media.id, media.variants, "avif", media.version);
  const webp = srcSet(media.id, media.variants, "webp", media.version);
  const [fx, fy] = (media.focalPoint ?? "50,50").split(",").map(Number);

  return (
    <picture>
      {avif ? <source type="image/avif" srcSet={avif} sizes={sizes} /> : null}
      {webp ? <source type="image/webp" srcSet={webp} sizes={sizes} /> : null}
      {/* Les variantes sont servies par l'API Symfony, next/image n'apporte rien ici. */}
      <img
        src={`/api/media/${media.id}${media.version ? `?v=${media.version}` : ""}`}
        alt={alt ?? media.alt}
        width={media.width ?? undefined}
        height={media.height ?? undefined}
        loading={priority ? "eager" : "lazy"}
        fetchPriority={priority ? "high" : undefined}
        decoding="async"
        className={className}
        style={{ objectPosition: `${Number.isFinite(fx) ? fx : 50}% ${Number.isFinite(fy) ? fy : 50}%` }}
      />
    </picture>
  );
}
