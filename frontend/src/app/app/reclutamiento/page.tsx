import { Users } from "lucide-react";

export default function AppRecruitingPage() {
  return (
    <div className="flex flex-col items-center justify-center py-24 text-center space-y-4">
      <div className="rounded-full bg-muted p-5">
        <Users className="h-10 w-10 text-muted-foreground" />
      </div>
      <h1 className="text-2xl font-semibold">Reclutamiento</h1>
      <p className="text-sm text-muted-foreground max-w-sm">
        Módulo en desarrollo. Próximamente podrás gestionar vacantes,
        candidatos y entrevistas directamente desde el ERP.
      </p>
    </div>
  );
}
