import { cn } from "@/lib/utils";

const heights = {
  sm: "h-8",
  md: "h-10",
  lg: "h-14",
};

export function LogoMark({ size = "md", className }: { size?: keyof typeof heights; className?: string }) {
  return (
    <span className={cn("inline-flex shrink-0 items-center justify-center rounded-xl bg-white p-1", heights[size], "aspect-square", className)}>
      {/* eslint-disable-next-line @next/next/no-img-element */}
      <img src="/logo-mark.png" alt="DFC Talento Humano" className="h-full w-full object-contain" />
    </span>
  );
}

export function Logo({ size = "md", className }: { size?: keyof typeof heights; className?: string }) {
  return (
    <span className={cn("inline-flex items-center rounded-2xl bg-white px-3 py-2", className)}>
      {/* eslint-disable-next-line @next/next/no-img-element */}
      <img
        src="/logo-full.png"
        alt="DFC Talento Humano — Conectamos personas, potenciamos talentos"
        className={cn(heights[size], "w-auto object-contain")}
      />
    </span>
  );
}
