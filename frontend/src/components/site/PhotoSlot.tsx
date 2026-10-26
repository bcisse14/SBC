import type { MediaInfo } from "@/lib/types";
import { ResponsiveImage } from "@/components/site/ResponsiveImage";

type PhotoSlotProps = {
  slotKey: string;
  ratio: string;
  minSize: string;
  idealShot: string;
  mediaAssetId?: number | null;
  media?: MediaInfo | null;
  sizes?: string;
  showMarkers?: boolean;
  hideIfEmpty?: boolean;
  className?: string;
};

function toAspectRatio(ratio: string): string {
  const [w, h] = ratio.split(":").map(Number);
  if (!w || !h) {
    return "4 / 3";
  }
  return `${w} / ${h}`;
}

export function PhotoSlot({
  slotKey,
  ratio,
  minSize,
  idealShot,
  mediaAssetId,
  media,
  sizes = "(min-width: 1024px) 40vw, 100vw",
  showMarkers = true,
  hideIfEmpty = false,
  className,
}: PhotoSlotProps) {
  if (!mediaAssetId && !media && hideIfEmpty) {
    return null;
  }

  const hasMedia = Boolean(media || mediaAssetId);

  return (
    <figure
      className={`relative overflow-hidden border border-[var(--beton)] bg-[var(--papier)] ${className ?? ""}`}
      style={{ aspectRatio: toAspectRatio(ratio) }}
      aria-label={`Emplacement photo ${slotKey}`}
    >
      {media ? (
        <ResponsiveImage media={media} sizes={sizes} className="h-full w-full object-cover" />
      ) : hasMedia ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img src={`/api/media/${mediaAssetId}`} alt={idealShot} className="h-full w-full object-cover" loading="lazy" />
      ) : (
        <div className="relative h-full w-full">
          <div
            className="absolute inset-0"
            style={{
              backgroundImage:
                "repeating-linear-gradient(45deg, rgba(180,80,46,.16), rgba(180,80,46,.16) 1px, transparent 1px, transparent 12px)",
            }}
          />
          <div className="absolute left-4 top-4 border border-dashed border-[var(--terre-cuite)] px-2 py-1 text-[10px] tracking-[0.14em] text-[var(--terre-cuite-fonce)]">
            PHOTO A AJOUTER
          </div>
          <div className="absolute left-4 bottom-4 max-w-[86%] bg-[rgba(247,244,238,.9)] p-3">
            <p className="font-mono text-[11px] uppercase tracking-[0.14em] text-[var(--encre)]">
              {slotKey} · {ratio} · {minSize}
            </p>
            <p className="mt-1 text-sm text-[var(--encre)]">{idealShot}</p>
          </div>
        </div>
      )}

      {showMarkers && !hasMedia && (
        <>
          <span className="absolute left-2 top-2 h-4 w-4 text-[var(--terre-cuite)]">+</span>
          <span className="absolute right-2 top-2 h-4 w-4 text-[var(--terre-cuite)]">+</span>
          <span className="absolute left-2 bottom-2 h-4 w-4 text-[var(--terre-cuite)]">+</span>
          <span className="absolute right-2 bottom-2 h-4 w-4 text-[var(--terre-cuite)]">+</span>
        </>
      )}
    </figure>
  );
}
