import { AdminShell } from "@/components/layout/admin-shell";
import { ThemeProvider } from "@/components/theme-provider";
import { ContingencyProvider } from "@/lib/contingency/context";
import { ThemedToaster } from "@/components/layout/themed-toaster";

export default function AppLayout({ children }: { children: React.ReactNode }) {
  return (
    <ThemeProvider>
      <ContingencyProvider>
        <AdminShell>{children}</AdminShell>
      </ContingencyProvider>
      <ThemedToaster />
    </ThemeProvider>
  );
}
