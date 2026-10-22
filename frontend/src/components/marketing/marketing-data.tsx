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

export type BlogSection =
  | { type: "p"; text: string }
  | { type: "h2"; text: string }
  | { type: "ul"; items: string[] }
  | { type: "cta"; text: string; href: string; label: string };

export type BlogPost = {
  slug: string;
  title: string;
  category: string;
  excerpt: string;
  readTime: string;
  publishedAt: string;
  body: BlogSection[];
};

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

export const blogPosts: BlogPost[] = [
  {
    slug: "como-digitalizar-rrhh-sin-caos",
    title: "Como digitalizar RRHH sin convertirlo en otro caos",
    category: "Recursos Humanos",
    excerpt: "La digitalizacion mal ejecutada no elimina el desorden, lo multiplica. Aqui hay una ruta realista para pequenas empresas.",
    readTime: "6 min",
    publishedAt: "2026-09-05",
    body: [
      { type: "p", text: "Muchas empresas arrancan su digitalizacion de RRHH con la mejor intencion y terminan con mas herramientas sueltas que antes. Un Excel para asistencia, un chat de WhatsApp para solicitudes de vacaciones, un correo para los documentos y un sistema de nomina que nadie sabe sincronizar. El resultado no es digitalizacion: es caos distribuido." },
      { type: "h2", text: "Por que la digitalizacion fragmentada es peor que el papel" },
      { type: "p", text: "Cuando cada proceso vive en una herramienta diferente, el equipo de RRHH pasa la mitad del tiempo consolidando informacion en lugar de usarla. La persona que registra asistencia en un sistema no puede ver facilmente si ese empleado tiene vacaciones pendientes, porque ese dato esta en otro lugar. El problema no es la tecnologia: es la falta de conexion entre los datos." },
      { type: "h2", text: "Que digitalizar primero" },
      { type: "p", text: "La pregunta correcta no es cuantas herramientas necesito, sino que proceso me cuesta mas tiempo y errores hoy. Para la mayoria de las pequenas empresas la respuesta suele ser asistencia o vacaciones. Son los dos puntos donde mas se pierde tiempo en seguimiento manual y donde mas conflictos se generan por falta de trazabilidad." },
      { type: "ul", items: [
        "Asistencia: el registro diario es repetitivo y los datos se necesitan rapido",
        "Vacaciones: cada solicitud genera una cadena de correos que nadie archiva bien",
        "Documentos: los contratos vencen sin que nadie lo note hasta que es tarde",
        "Empleados: el expediente disperso obliga a buscar datos en tres sitios distintos",
      ]},
      { type: "h2", text: "Pasos concretos para empezar sin enredarse" },
      { type: "p", text: "El primer paso es mapear los procesos que hoy generan mas fricciones. No se trata de digitalizarlo todo al mismo tiempo, sino de encontrar el punto donde una herramienta centralizada libera mas tiempo con menos cambio. Una vez identificado ese punto, la adopcion es mas natural y el equipo no percibe la herramienta como una carga adicional." },
      { type: "p", text: "El segundo paso es asegurarse de que la herramienta elegida conecte los datos relevantes. Un sistema de asistencia que no se integra con el expediente del empleado solo mueve el problema de lugar. La clave es que el mismo registro de entrada genere el dato que necesita RRHH, el jefe directo y el reporte mensual." },
      { type: "cta", text: "Conoce como FidelOS conecta asistencia, vacaciones, documentos y empleados en una sola plataforma.", href: "/demo", label: "Solicitar demo" },
    ],
  },
  {
    slug: "asistencia-y-ausentismo",
    title: "Asistencia y ausentismo: metricas que si sirven",
    category: "Asistencia",
    excerpt: "Los registros de papel y Excel generan datos que nadie termina usando. Estas son las metricas que realmente ayudan a tomar decisiones.",
    readTime: "5 min",
    publishedAt: "2026-09-12",
    body: [
      { type: "p", text: "El registro de asistencia existe en casi todas las empresas, pero los datos que produce rara vez llegan a quien los necesita en un formato util. El libro de firmas o el Excel de turnos captura el dato, pero convertirlo en informacion accionable requiere horas de consolidacion que pocas areas de RRHH tienen." },
      { type: "h2", text: "Por que los registros manuales no generan datos confiables" },
      { type: "p", text: "Un registro en papel depende de que alguien lo llene correctamente todos los dias. Un Excel compartido depende de que nadie lo sobreescriba por error. Ambos formatos hacen que el dato de asistencia llegue tarde, incompleto o con inconsistencias que son dificiles de rastrear. Cuando el dato no es confiable, las decisiones sobre ausentismo se basan en percepciones, no en evidencia." },
      { type: "h2", text: "Las metricas que realmente importan" },
      { type: "p", text: "No todas las metricas de asistencia son utiles de la misma manera. El porcentaje de ausentismo global dice poco por si solo. Lo que ayuda a actuar es ver el ausentismo por area o por equipo, identificar patrones de tardanza recurrente en ciertos dias de la semana, y detectar si los picos de ausencia coinciden con fechas especificas o con cambios en la carga de trabajo." },
      { type: "ul", items: [
        "Tasa de ausentismo por area: permite comparar y priorizar donde actuar",
        "Tardanzas recurrentes: distingue habitos de situaciones puntuales",
        "Incapacidades por periodo: ayuda a planificar reemplazos y cargas",
        "Presencia efectiva vs. horas contratadas: base para calcular productividad real",
      ]},
      { type: "h2", text: "Como pasar de dato a decision" },
      { type: "p", text: "El valor de medir asistencia no esta en el registro en si sino en la frecuencia con que se puede revisar y la facilidad con que el dato llega al responsable correcto. Un jefe de area que puede ver en tiempo real quien falta hoy puede tomar decisiones operativas inmediatas. Un reporte mensual que llega dos semanas despues solo sirve para el historico." },
      { type: "cta", text: "Mira como funciona el modulo de asistencia de FidelOS con filtros por fecha, area y empleado.", href: "/producto/asistencia", label: "Ver modulo de asistencia" },
    ],
  },
  {
    slug: "vacaciones-sin-excel",
    title: "Vacaciones sin Excel: aprobaciones claras y trazables",
    category: "Productividad",
    excerpt: "Gestionar vacaciones por correo o planilla genera conflictos que se podrian evitar. Un flujo digital cambia la dinamica sin complicar el proceso.",
    readTime: "5 min",
    publishedAt: "2026-09-20",
    body: [
      { type: "p", text: "El proceso de vacaciones en una empresa pequena suele funcionar hasta que no funciona. Mientras el equipo es chico y todos se conocen, una solicitud por WhatsApp o un correo al jefe alcanza. Pero cuando la empresa crece o cuando el jefe esta de viaje, la solicitud se pierde, el saldo no esta actualizado y el empleado no sabe si su periodo fue aprobado." },
      { type: "h2", text: "El problema real de gestionar vacaciones sin un sistema" },
      { type: "p", text: "No es que el Excel este mal disenado. El problema es que el Excel no notifica, no actualiza el saldo automaticamente y no registra quien aprobo que ni cuando. Cuando RRHH necesita saber cuantos dias le quedan a un empleado o si su solicitud fue autorizada, tiene que rastrear correos y versiones del archivo. Ese tiempo es el costo real del proceso manual." },
      { type: "h2", text: "Como un flujo digital cambia la dinamica" },
      { type: "p", text: "Un flujo de aprobacion digital no elimina al jefe de la ecuacion, lo pone en el lugar correcto. El empleado solicita desde donde este, el jefe recibe la notificacion, aprueba o rechaza con un comentario y RRHH queda informado sin necesidad de intermediarios. El saldo se actualiza solo y el historial queda registrado sin que nadie tenga que archivar nada." },
      { type: "ul", items: [
        "El empleado ve su saldo en tiempo real sin tener que preguntar",
        "El jefe aprueba o rechaza desde la plataforma con un motivo si lo considera necesario",
        "RRHH tiene visibilidad de todas las solicitudes activas sin necesidad de consolidar",
        "El calendario de ausencias ayuda a planificar cobertura con anticipacion",
      ]},
      { type: "h2", text: "Que esperar de una implementacion real" },
      { type: "p", text: "La adopcion de un flujo digital de vacaciones es mas rapida de lo que parece porque resuelve una friccion que el equipo ya siente. Los empleados prefieren saber el estado de su solicitud en tiempo real. Los jefes prefieren aprobar con un clic que buscar el correo que les mandaron. RRHH prefiere no consolidar datos manualmente. El cambio no requiere convencer a nadie de algo nuevo: requiere mostrar que el proceso existente puede funcionar mejor." },
      { type: "cta", text: "Conoce el modulo de vacaciones y permisos de FidelOS con solicitudes, aprobaciones y saldos en un solo lugar.", href: "/producto/vacaciones", label: "Ver modulo de vacaciones" },
    ],
  },
  {
    slug: "pipeline-de-reclutamiento",
    title: "Pipeline de reclutamiento para PYMES",
    category: "Reclutamiento",
    excerpt: "El reclutamiento informal pierde buenos candidatos. Un pipeline claro no tiene que ser complicado para funcionar bien.",
    readTime: "6 min",
    publishedAt: "2026-09-28",
    body: [
      { type: "p", text: "En muchas pequenas empresas el reclutamiento ocurre de la misma manera: alguien envia el CV por correo, el jefe lo lee cuando puede, la entrevista se agenda por WhatsApp y el seguimiento depende de la memoria de quien lleva el proceso. Mientras el volumen de candidatos sea bajo esto funciona. Cuando hay varias posiciones abiertas al mismo tiempo, el proceso se vuelve opaco y los buenos candidatos se van con otra empresa." },
      { type: "h2", text: "Por que el reclutamiento informal pierde candidatos" },
      { type: "p", text: "El problema no es la informalidad en si sino la falta de visibilidad. Cuando el estado de cada candidato vive en el correo o en la cabeza de una persona, es facil que alguien quede sin respuesta por dias o que dos personas del equipo contacten al mismo candidato con mensajes diferentes. La experiencia del candidato refleja el orden interno de la empresa." },
      { type: "h2", text: "Las etapas minimas de un pipeline que funciona" },
      { type: "p", text: "Un pipeline de reclutamiento no necesita ser sofisticado para ser efectivo. Las etapas basicas que cubren la mayoria de los procesos son: recepcion del CV, revision inicial, primera entrevista, entrevista tecnica o con el jefe directo, oferta y cierre. Lo importante es que cada candidato tenga un estado visible y que alguien sea responsable de moverlo a la siguiente etapa." },
      { type: "ul", items: [
        "Recepcion: todos los CVs en un solo lugar, sin depender del correo personal de quien recluta",
        "Revision: un criterio claro para filtrar antes de invertir tiempo en entrevistas",
        "Entrevistas: fechas y notas registradas para que cualquier miembro del equipo pueda continuar",
        "Oferta: historial del proceso disponible para futuras referencias o repescas",
      ]},
      { type: "h2", text: "Como mantenerlo simple sin perder trazabilidad" },
      { type: "p", text: "La tentacion al implementar un pipeline es agregar demasiadas etapas o criterios de evaluacion. Un proceso con ocho etapas que nadie actualiza es peor que uno con cuatro que se usa de verdad. La clave es empezar con lo minimo necesario y ajustar segun lo que el equipo realmente hace, no segun lo que idealmente deberia hacer." },
      { type: "cta", text: "Conoce el modulo de reclutamiento de FidelOS para gestionar candidatos y procesos de seleccion.", href: "/reclutamiento", label: "Ver reclutamiento" },
    ],
  },
  {
    slug: "documentos-laborales",
    title: "Documentos laborales: como evitar vencimientos invisibles",
    category: "Gestion de talento",
    excerpt: "Los documentos vencidos generan problemas legales que se podrian haber evitado. Asi funciona un sistema de alertas preventivas.",
    readTime: "5 min",
    publishedAt: "2026-10-06",
    body: [
      { type: "p", text: "Un contrato que vence sin renovarse, una licencia de conduccion que caduca en un conductor de reparto, una certificacion tecnica que expiro hace dos meses. Estos son los documentos que generan problemas legales o operativos cuando nadie los monitorea de forma sistematica. El riesgo no es que la empresa no tenga los documentos: es que no tiene un sistema para saber cuando vencen." },
      { type: "h2", text: "Los documentos criticos que necesitan monitoreo activo" },
      { type: "p", text: "No todos los documentos tienen la misma urgencia. Los que requieren atencion preventiva son aquellos cuyo vencimiento genera consecuencias inmediatas: contratos a termino fijo, examenes medicos periodicos, licencias profesionales o habilitaciones que son requisito para el puesto. Un expediente digital que no registra las fechas de vencimiento es solo un archivo, no una herramienta de gestion." },
      { type: "ul", items: [
        "Contratos a termino fijo: la renovacion o terminacion debe prepararse con anticipacion",
        "Examenes medicos: obligatorios en muchas actividades y con periodos de vigencia definidos",
        "Licencias y certificaciones: requisito legal para ciertos cargos, su vencimiento puede inhabilitar al empleado",
        "Documentos de identidad y seguros: necesarios para tramites y obligaciones laborales",
      ]},
      { type: "h2", text: "Como funcionan las alertas preventivas" },
      { type: "p", text: "Una alerta preventiva es una notificacion que llega antes de que el documento venza, no el dia que vence. El tiempo de anticipacion depende del tipo de documento: para un examen medico pueden ser 30 dias, para la renovacion de un contrato pueden ser 60 o 90 dias. El objetivo es que quien debe actuar tenga tiempo suficiente para hacerlo sin presion." },
      { type: "h2", text: "El costo de no tener un sistema de alertas" },
      { type: "p", text: "El costo de un documento vencido no es solo la multa o la sancion. Es el tiempo que invierte el equipo en resolver una emergencia que era predecible. La persona que debia renovar el examen medico no puede trabajar hoy. El contrato que debia renovarse hace un mes ahora requiere una decision urgente con menos informacion. Los problemas que vienen de documentos vencidos son casi siempre evitables." },
      { type: "cta", text: "Mira como FidelOS organiza los documentos laborales con alertas de vencimiento y expediente por colaborador.", href: "/producto/documentos", label: "Ver modulo de documentos" },
    ],
  },
  {
    slug: "ia-en-recursos-humanos",
    title: "IA en Recursos Humanos: casos utiles antes del hype",
    category: "IA",
    excerpt: "La mayoria de las aplicaciones de IA en RRHH todavia estan en fase experimental. Estos son los casos donde ya aporta valor real hoy.",
    readTime: "7 min",
    publishedAt: "2026-10-14",
    body: [
      { type: "p", text: "La conversacion sobre IA en Recursos Humanos mezcla lo que ya es posible con lo que todavia es especulacion. Las herramientas de IA generativa avanzan rapido, pero eso no significa que todas las aplicaciones que se anuncian sean practicas para una empresa mediana hoy. Vale la pena separar los casos de uso que ya funcionan de los que requieren infraestructura, datos o madurez tecnologica que pocas empresas tienen." },
      { type: "h2", text: "Lo que la IA puede hacer bien hoy en RRHH" },
      { type: "p", text: "El caso de uso mas maduro y probado es la respuesta automatica a consultas frecuentes. Preguntas sobre saldo de vacaciones, politicas de permisos, proceso de solicitud de certificados o procedimientos de onboarding tienen respuestas que no cambian frecuentemente y que consumen tiempo de RRHH en cada repeticion. Un asistente conversacional entrenado sobre la informacion real de la empresa puede responder estas preguntas de forma confiable las 24 horas." },
      { type: "ul", items: [
        "Consultas de vacaciones y permisos: cuantos dias me quedan, como solicito, cuando me responden",
        "Certificados laborales: como pedirlos, cuanto tardan, que informacion incluyen",
        "Politicas internas: permisos no remunerados, licencias de maternidad, dias de incapacidad",
        "Onboarding: pasos del proceso, documentos necesarios, primeros accesos y contactos clave",
      ]},
      { type: "h2", text: "Donde la IA todavia no reemplaza el criterio humano" },
      { type: "p", text: "Las decisiones que involucran contexto humano, negociacion o criterio etico siguen siendo territorio de las personas. La evaluacion de desempeno, la decision de ascenso, la gestion de conflictos interpersonales o la definicion de cultura organizacional requieren juicio que un sistema automatizado no puede replicar de forma confiable con las herramientas actuales. Usar IA para estas decisiones sin supervision humana genera riesgos que superan los beneficios." },
      { type: "h2", text: "Como integrar IA sin desplazar al equipo" },
      { type: "p", text: "El enfoque mas sensato es usar IA para liberar tiempo del equipo de RRHH en tareas repetitivas de bajo valor, no para reemplazar la interaccion humana donde esta agrega valor. Cuando el equipo deja de responder las mismas preguntas diez veces al dia, puede dedicar ese tiempo a procesos que realmente requieren su atencion: el bienestar del equipo, el desarrollo de las personas, la cultura de la empresa." },
      { type: "cta", text: "Conoce el modulo de IA de FidelOS para responder consultas internas de forma automatica.", href: "/producto/ia", label: "Ver modulo de IA" },
    ],
  },
];

export const trustItems = [
  { label: "Seguro", icon: ShieldCheck },
  { label: "Acceso por roles", icon: MessageCircle },
  { label: "Flujos claros", icon: FolderKanban },
];
