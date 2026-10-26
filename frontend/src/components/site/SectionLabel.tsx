type SectionLabelProps = {
  index: string;
  title: string;
};

export function SectionLabel({ index, title }: SectionLabelProps) {
  return (
    <div className="mb-8 flex items-center gap-3">
      <span className="font-mono text-[11px] uppercase tracking-[0.2em] text-[var(--terre-cuite-fonce)]">
        {index} / {title}
      </span>
      <span className="block h-px flex-1 bg-[var(--beton)]" aria-hidden />
    </div>
  );
}
