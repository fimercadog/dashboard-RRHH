import * as React from "react";
import Link from "next/link";
import { TrendingDown, TrendingUp } from "lucide-react";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { cn } from "@/lib/utils";

export type KpiCardProps = {
  label: string;
  value: string | number | null;
  icon: React.ElementType;
  unit?: string;
  delta?: {
    value: number;
    period: string;
  };
  status?: "loading" | "error" | "empty" | "ready";
  href?: string;
  className?: string;
};

export function KpiCard({
  label,
  value,
  icon: Icon,
  unit,
  delta,
  status = "ready",
  href,
  className,
}: KpiCardProps) {
  const isReady = status === "ready" && value !== null;
  const showValue = status === "error" || status === "empty" || value === null ? "—" : value;
  const showDelta = isReady && delta != null;

  const body = (
    <Card className={cn(href ? "cursor-pointer hover:bg-muted/50 transition-colors" : undefined, className)}>
      <CardHeader className="flex flex-row items-center justify-between pb-2">
        <span className="text-sm text-muted-foreground">{label}</span>
        <Icon className="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent>
        {status === "loading" ? (
          <div className="h-8 w-24 animate-pulse rounded bg-muted" />
        ) : (
          <div className="flex items-baseline gap-1">
            <span className="text-2xl font-bold">{showValue}</span>
            {isReady && unit ? (
              <span className="text-sm text-muted-foreground">{unit}</span>
            ) : null}
          </div>
        )}
        {showDelta ? (
          <div
            className={cn(
              "mt-1 flex items-center gap-1 text-xs",
              delta!.value >= 0
                ? "text-green-600 dark:text-green-400"
                : "text-red-600 dark:text-red-400",
            )}
          >
            {delta!.value >= 0 ? (
              <TrendingUp className="h-3 w-3" />
            ) : (
              <TrendingDown className="h-3 w-3" />
            )}
            <span>
              {delta!.value > 0 ? "+" : ""}
              {delta!.value} {delta!.period}
            </span>
          </div>
        ) : null}
      </CardContent>
    </Card>
  );

  if (href) {
    return <Link href={href}>{body}</Link>;
  }
  return body;
}
