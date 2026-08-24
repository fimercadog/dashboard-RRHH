import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

const roles = ["Super Admin", "Administrador de empresa", "Recursos Humanos", "Supervisor", "Empleado"];

export default function AppRolesPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold">Roles y permisos</h1>
        <p className="text-sm text-muted-foreground">Arquitectura visual conectable a permisos backend.</p>
      </div>
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {roles.map((role) => (
          <Card key={role}><CardHeader><CardTitle>{role}</CardTitle></CardHeader><CardContent><Badge>Permisos configurables</Badge></CardContent></Card>
        ))}
      </div>
    </div>
  );
}
