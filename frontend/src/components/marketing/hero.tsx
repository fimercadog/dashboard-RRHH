"use client";

import Link from "next/link";
import { motion, type Variants } from "framer-motion";
import { ArrowRight, CheckCircle2, PlayCircle } from "lucide-react";
import { DashboardPreview } from "./dashboard-preview";
import { trustItems } from "./marketing-data";

const container: Variants = {
  hidden: {},
  show: { transition: { staggerChildren: 0.15, delayChildren: 0.1 } },
};

const item: Variants = {
  hidden: { opacity: 0, y: 24 },
  show: { opacity: 1, y: 0, transition: { duration: 0.55, ease: "easeOut" } },
};

export function Hero() {
  return (
    <section className="relative overflow-hidden px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
      <motion.div
        className="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,#ffffff_0%,#fbe9f0_100%)]"
        initial={{ scale: 1.15 }}
        animate={{ scale: 1 }}
        transition={{ duration: 2, ease: "easeOut" }}
      />
      <div className="animate-marketing-grid pointer-events-none absolute inset-0" />
      <div className="animate-marketing-float pointer-events-none absolute left-1/2 top-16 h-72 w-72 rounded-full bg-primary/15 blur-3xl" />
      <div className="animate-marketing-drift pointer-events-none absolute -left-16 bottom-0 h-80 w-80 rounded-full bg-navy/10 blur-3xl" />
      <div className="animate-marketing-pulse-glow pointer-events-none absolute right-0 top-0 h-56 w-56 rounded-full bg-success/25 blur-3xl" />
      <motion.div
        className="relative mx-auto grid max-w-7xl gap-12 lg:grid-cols-[1fr_0.95fr] lg:items-center"
        variants={container}
        initial="hidden"
        animate="show"
      >
        <div>
          <motion.div variants={item} className="mb-6 flex flex-wrap gap-2">
            {["Facil de usar", "Seguro", "Para PYMES"].map((label) => (
              <span key={label} className="inline-flex items-center gap-2 rounded-full border border-border bg-white px-3 py-1 text-xs font-medium text-navy">
                <CheckCircle2 className="h-3.5 w-3.5 text-success" /> {label}
              </span>
            ))}
          </motion.div>
          <motion.h1 variants={item} className="max-w-3xl text-5xl font-semibold leading-[1.04] tracking-tight text-navy sm:text-6xl">
            Gestiona tu equipo desde <span className="animate-marketing-gradient-text">un solo lugar</span>
          </motion.h1>
          <motion.p variants={item} className="mt-6 max-w-2xl text-lg leading-8 text-muted-foreground">
            Empleados, asistencia, documentos, vacaciones, permisos y reclutamiento en una sola plataforma.
          </motion.p>
          <motion.div variants={item} className="mt-8 flex flex-col gap-3 sm:flex-row">
            <Link className="inline-flex h-12 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-primary px-6 text-sm font-semibold text-white shadow-lg shadow-primary/20 transition-transform duration-200 hover:scale-105 active:scale-95" href="/demo">
              Solicitar demo <ArrowRight className="h-4 w-4" />
            </Link>
            <Link className="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-border bg-white px-6 text-sm font-semibold text-navy transition-transform duration-200 hover:scale-105 active:scale-95" href="/producto">
              <PlayCircle className="h-4 w-4" /> Ver como funciona
            </Link>
          </motion.div>
          <motion.div variants={item} className="mt-10 grid max-w-lg grid-cols-3 gap-3">
            {trustItems.map((trustItem) => {
              const Icon = trustItem.icon;
              return (
                <div key={trustItem.label} className="rounded-2xl bg-white p-4 text-sm font-medium text-navy shadow-sm transition-transform duration-200 hover:-translate-y-1">
                  <Icon className="mb-2 h-5 w-5 text-primary" />{trustItem.label}
                </div>
              );
            })}
          </motion.div>
        </div>
        <motion.div variants={item}>
          <DashboardPreview />
        </motion.div>
      </motion.div>
    </section>
  );
}
