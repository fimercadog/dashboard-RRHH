import { cn } from "@/lib/utils";

const markSizes = {
  sm: "h-8 w-8 rounded-xl text-sm",
  md: "h-10 w-10 rounded-2xl text-lg",
  lg: "h-14 w-14 rounded-2xl text-2xl",
};

export function LogoMark({ size = "md", className }: { size?: keyof typeof markSizes; className?: string }) {
  return (
    <span className={cn("grid place-items-center bg-primary font-bold text-primary-foreground", markSizes[size], className)}>
      D
    </span>
  );
}

export function Logo({
  size = "md",
  tagline,
  className,
}: {
  size?: keyof typeof markSizes;
  tagline?: string;
  className?: string;
}) {
  return (
    <span className={cn("flex items-center gap-3", className)}>
      <LogoMark size={size} />
      <span>
        <span className="block text-base font-bold text-foreground">DFC Talento Humano</span>
        {tagline ? <span className="block text-xs text-muted-foreground">{tagline}</span> : null}
      </span>
    </span>
  );
}
