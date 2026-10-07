// Usuarios para el selector de agentes demo — sin contraseñas.
// La contraseña vive solo en process.env.DEMO_PASSWORD (server-only).
export type DemoUser = {
  name: string;
  role: string;
  email: string;
};

export const DEMO_USERS: DemoUser[] = [
  { name: "Sofia Mercado",    role: "Super Admin",              email: "superadmin@andespeople.co" },
  { name: "Camila Rojas",     role: "Administrador de empresa", email: "admin@andespeople.co" },
  { name: "Sebastian Moreno", role: "Recursos Humanos",         email: "rrhh@andespeople.co" },
  { name: "Valentina Castro", role: "Supervisor",               email: "supervisor@andespeople.co" },
  { name: "Laura Medina",     role: "Empleado",                 email: "empleado@andespeople.co" },
];
