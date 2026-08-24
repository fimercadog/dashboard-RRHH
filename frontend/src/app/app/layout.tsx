import { AdminShell } from "@/components/layout/admin-shell";
import { ThemeProvider } from "@/components/theme-provider";
import { Toaster } from "sonner";

export default function AppLayout({ children }: { children: React.ReactNode }) {
  return (
    <ThemeProvider>
      <AdminShell>{children}</AdminShell>
      <Toaster richColors position="top-right" />
    </ThemeProvider>
  );
}
