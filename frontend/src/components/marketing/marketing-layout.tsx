import { MarketingFooter } from "./footer";
import { MarketingHeader } from "./header";
import { WhatsAppButton } from "./whatsapp-button";

export function MarketingLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen bg-white text-foreground" data-theme="light">
      <MarketingHeader />
      {children}
      <MarketingFooter />
      <WhatsAppButton />
    </div>
  );
}
