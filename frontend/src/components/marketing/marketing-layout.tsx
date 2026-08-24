import { MarketingFooter } from "./footer";
import { MarketingHeader } from "./header";

export function MarketingLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen bg-white text-foreground">
      <MarketingHeader />
      {children}
      <MarketingFooter />
    </div>
  );
}
