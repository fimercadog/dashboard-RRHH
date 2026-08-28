"use client";

import { motion } from "framer-motion";
import { CalendarDays, CheckCircle2, Clock3, type LucideIcon, Users } from "lucide-react";

const metrics: Array<[string, string, LucideIcon]> = [
  ["42", "empleados", Users],
  ["38", "presentes", CheckCircle2],
  ["2", "ausentes", Clock3],
  ["3", "solicitudes", CalendarDays],
];

export function DashboardPreview() {
  return (
    <motion.div
      className="relative rounded-4xl border border-white/70 bg-white p-4 shadow-(--marketing-shadow)"
      animate={{ y: [0, -10, 0] }}
      transition={{ duration: 5, repeat: Infinity, ease: "easeInOut" }}
    >
      <div className="rounded-3xl bg-[#fdf6f8] p-4">
        <div className="mb-4 flex items-center justify-between">
          <div>
            <p className="text-xs font-semibold uppercase text-primary">Dashboard RRHH</p>
            <p className="text-sm font-semibold text-navy">Hoy, 24 de agosto</p>
          </div>
          <span className="relative flex items-center gap-1.5 rounded-full bg-success/10 px-3 py-1 text-xs font-medium text-success">
            <span className="relative flex h-1.5 w-1.5">
              <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-success opacity-75" />
              <span className="relative inline-flex h-1.5 w-1.5 rounded-full bg-success" />
            </span>
            En linea
          </span>
        </div>
        <div className="grid grid-cols-2 gap-3">
          {metrics.map(([value, label, Icon], index) => (
            <motion.div
              key={label as string}
              className="rounded-2xl bg-white p-4 shadow-sm"
              initial={{ opacity: 0, y: 12 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.4, delay: index * 0.1 }}
            >
              <Icon className="mb-3 h-5 w-5 text-primary" />
              <p className="text-2xl font-bold text-navy">{value}</p>
              <p className="text-xs text-muted-foreground">{label}</p>
            </motion.div>
          ))}
        </div>
        <div className="mt-4 rounded-2xl bg-white p-4 shadow-sm">
          <div className="mb-3 flex items-center justify-between">
            <p className="text-sm font-semibold text-navy">Asistencia semanal</p>
            <p className="text-xs text-muted-foreground">Live</p>
          </div>
          <div className="flex h-28 gap-2">
            {[58, 72, 64, 88, 76, 66, 91].map((height, index) => (
              <div key={index} className="flex flex-1 flex-col justify-end overflow-hidden rounded-t-xl bg-primary/15">
                <motion.div
                  className="rounded-t-xl bg-primary"
                  initial={{ height: 0 }}
                  whileInView={{ height: `${height}%` }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.6, delay: index * 0.07, ease: "easeOut" }}
                />
              </div>
            ))}
          </div>
        </div>
        <div className="mt-4 grid gap-3 md:grid-cols-2">
          <div className="rounded-2xl bg-white p-4 shadow-sm">
            <p className="mb-3 text-sm font-semibold text-navy">Solicitudes pendientes</p>
            {["Vacaciones - Laura", "Permiso medico - Daniel", "Documento - Camila"].map((item) => (
              <div key={item} className="mb-2 rounded-xl bg-muted px-3 py-2 text-xs text-muted-foreground">{item}</div>
            ))}
          </div>
          <div className="rounded-2xl bg-white p-4 shadow-sm">
            <p className="mb-3 text-sm font-semibold text-navy">Proximos eventos</p>
            {["Contrato vence en 17 dias", "Cumpleanos de Valentina", "Turno nocturno inicia"].map((item) => (
              <div key={item} className="mb-2 rounded-xl bg-accent px-3 py-2 text-xs text-navy">{item}</div>
            ))}
          </div>
        </div>
      </div>
    </motion.div>
  );
}
