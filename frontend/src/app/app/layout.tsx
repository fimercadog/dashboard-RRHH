import { AdminShell } from "@/components/layout/admin-shell";
import { ThemeProvider } from "@/components/theme-provider";
import { ContingencyProvider } from "@/lib/contingency/context";
import { Toaster } from "sonner";

export default function AppLayout({ children }: { children: React.ReactNode }) {
  return (
    <ThemeProvider>
      <ContingencyProvider>
        <AdminShell>{children}</AdminShell>
      </ContingencyProvider>
      <Toaster richColors position="top-right" />
    </ThemeProvider>
  );
}
