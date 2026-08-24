import { cn } from "@/lib/utils";

export function Badge({ className, ...props }: React.HTMLAttributes<HTMLSpanElement>) {
  return <span className={cn("inline-flex items-center rounded-md bg-accent px-2 py-1 text-xs font-medium text-foreground", className)} {...props} />;
}
