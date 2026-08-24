import {
  Activity,
  Bot,
  BriefcaseBusiness,
  CalendarCheck,
  Clock3,
  FileText,
  FolderKanban,
  LayoutDashboard,
  MessageCircle,
  ShieldCheck,
  Users,
} from "lucide-react";

export const navProduct = [
  ["Gestion de empleados", "/producto/empleados"],
  ["Asistencia", "/producto/asistencia"],
  ["Vacaciones y permisos", "/producto/vacaciones"],
  ["Documentos", "/producto/documentos"],
  ["Turnos", "/producto/turnos"],
  ["Reportes", "/producto/reportes"],
  ["IA para RRHH", "/producto/ia"],
];

export const navSolutions = [
  ["Para pequenas empresas", "/soluciones#pymes"],
  ["Para equipos de RRHH", "/soluciones#rrhh"],
  ["Para empresas con turnos", "/soluciones#turnos"],
  ["Para reclutamiento", "/soluciones#reclutamiento"],
];

export const features = [
  { title: "Empleados", description: "Centraliza toda la informacion de tus colaboradores.", icon: Users, href: "/producto/empleados" },
  { title: "Asistencia", description: "Controla entradas, salidas, retrasos y ausencias.", icon: Clock3, href: "/producto/asistencia" },
  { title: "Vacaciones y permisos", description: "Gestiona solicitudes, aprobaciones y saldos.", icon: CalendarCheck, href: "/producto/vacaciones" },
  { title: "Documentos", description: "Organiza contratos, certificados y archivos laborales.", icon: FileText, href: "/producto/documentos" },
  { title: "Turnos", description: "Planifica horarios, jornadas y descansos.", icon: Activity, href: "/producto/turnos" },
  { title: "Reportes", description: "Obtén metricas utiles para tomar decisiones.", icon: LayoutDashboard, href: "/producto/reportes" },
  { title: "Reclutamiento", description: "Gestiona candidatos y procesos de seleccion.", icon: BriefcaseBusiness, href: "/reclutamiento" },
  { title: "IA para RRHH", description: "Automatiza consultas y procesos internos.", icon: Bot, href: "/producto/ia" },
];

export const productPages = {
  empleados: {
    title: "Expediente digital de cada empleado",
    eyebrow: "Gestion de empleados",
    description: "Toda la informacion personal, laboral, documental e historica de tus colaboradores en una ficha clara y accionable.",
    icon: Users,
    bullets: ["Datos personales y laborales", "Cargo, area, contrato y jefe", "Documentos e historial por colaborador", "Tabs para asistencia, vacaciones y novedades"],
  },
  asistencia: {
    title: "Asistencia clara, diaria y reportable",
    eyebrow: "Control de asistencia",
    description: "Visualiza presentes, ausentes, llegadas tarde e incapacidades con filtros por fecha, area y empleado.",
    icon: Clock3,
    bullets: ["Entradas y salidas", "Retrasos y ausencias", "Historial por colaborador", "Reportes exportables"],
  },
  vacaciones: {
    title: "Vacaciones y permisos sin cadenas de correos",
    eyebrow: "Solicitudes y aprobaciones",
    description: "Convierte solicitudes dispersas en flujos aprobables, trazables y visibles para RRHH.",
    icon: CalendarCheck,
    bullets: ["Empleado solicita", "Jefe aprueba o rechaza", "RRHH queda informado", "Saldo y calendario actualizados"],
  },
  documentos: {
    title: "Documentos laborales siempre ubicables",
    eyebrow: "Gestion documental",
    description: "Contratos, certificados, anexos y soportes con alertas de vencimiento y expediente asociado.",
    icon: FileText,
    bullets: ["Contratos y anexos", "Certificados laborales", "Soportes personales", "Alertas por vencimiento"],
  },
  turnos: {
    title: "Planificacion semanal de turnos",
    eyebrow: "Turnos y jornadas",
    description: "Asigna horarios, controla descansos y detecta conflictos antes de que lleguen a la operacion.",
    icon: Activity,
    bullets: ["Vista semanal", "Asignacion por empleado", "Horarios y descansos", "Conflictos basicos"],
  },
  reportes: {
    title: "Reportes para decidir, no solo almacenar",
    eyebrow: "Analitica de RRHH",
    description: "Indicadores de asistencia, ausentismo, vacaciones, documentos, altas, bajas y distribucion por area.",
    icon: LayoutDashboard,
    bullets: ["Ausentismo y tardanzas", "Distribucion por area", "Documentos vencidos", "Exportaciones CSV/PDF"],
  },
  ia: {
    title: "Tu asistente de Recursos Humanos disponible 24/7",
    eyebrow: "IA para RRHH",
    description: "Prepara una capa conversacional para responder politicas, vacaciones, turnos, certificados y solicitudes.",
    icon: Bot,
    bullets: ["Consultas de vacaciones", "Certificados laborales", "Politicas internas", "Solicitudes guiadas"],
  },
};

export const blogPosts = [
  { slug: "como-digitalizar-rrhh-sin-caos", title: "Como digitalizar RRHH sin convertirlo en otro caos", category: "Recursos Humanos", excerpt: "Contenido inicial ficticio para explicar una ruta realista de adopcion digital en pequenas empresas." },
  { slug: "asistencia-y-ausentismo", title: "Asistencia y ausentismo: metricas que si sirven", category: "Asistencia", excerpt: "Contenido inicial ficticio sobre indicadores accionables para equipos de talento humano." },
  { slug: "vacaciones-sin-excel", title: "Vacaciones sin Excel: aprobaciones claras y trazables", category: "Productividad", excerpt: "Contenido inicial ficticio sobre como ordenar solicitudes y saldos de vacaciones." },
  { slug: "pipeline-de-reclutamiento", title: "Pipeline de reclutamiento para PYMES", category: "Reclutamiento", excerpt: "Contenido inicial ficticio para convertir procesos de seleccion dispersos en un flujo visual." },
  { slug: "documentos-laborales", title: "Documentos laborales: como evitar vencimientos invisibles", category: "Gestion de talento", excerpt: "Contenido inicial ficticio sobre expediente digital y alertas preventivas." },
  { slug: "ia-en-recursos-humanos", title: "IA en Recursos Humanos: casos utiles antes del hype", category: "IA", excerpt: "Contenido inicial ficticio sobre asistentes internos para consultas y solicitudes frecuentes." },
];

export const trustItems = [
  { label: "Seguro", icon: ShieldCheck },
  { label: "WhatsApp futuro", icon: MessageCircle },
  { label: "Flujos claros", icon: FolderKanban },
];
