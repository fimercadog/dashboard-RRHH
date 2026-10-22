"use client";

import { useEffect, useRef, useState } from "react";
import { Camera, Eye, Trash2, Upload, Users, UserPlus, FileText } from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { CrudField } from "@/components/crud/crud-modal";
import { ToggleStatusAction } from "@/components/crud/toggle-status-action";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant, badgeLabel } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { api } from "@/lib/api";
import { KpiCard } from "@/components/kpi-card";
import { hasAnyPermission, getStoredUser } from "@/lib/auth";
import { EMPLOYEE_IDENTIFICATION_TYPE_OPTIONS, STATUS_OPTIONS } from "@/lib/constants";
import { AppColumnDef } from "@/lib/table-types";
import { Employee } from "@/lib/types";

// ── Avatar display ──────────────────────────────────────────────────────────

function EmployeeAvatar({ employee, size = "sm" }: { employee: Employee; size?: "sm" | "md" }) {
  const initials = `${employee.first_name?.[0] ?? ""}${employee.last_name?.[0] ?? ""}`.toUpperCase() || "?";
  const dim = size === "sm" ? 32 : 64;
  const cls = size === "sm"
    ? "rounded-full object-cover h-8 w-8"
    : "rounded-full object-cover h-16 w-16";
  if (employee.avatar_url) {
    return <Image unoptimized src={employee.avatar_url} alt={initials} width={dim} height={dim} className={cls} />;
  }
  return (
    <span className={`inline-flex items-center justify-center rounded-full bg-primary/10 font-semibold text-primary ${size === "sm" ? "h-8 w-8 text-xs" : "h-16 w-16 text-base"}`}>
      {initials}
    </span>
  );
}

// ── Avatar upload modal ─────────────────────────────────────────────────────

function AvatarUploadModal({
  employee,
  open,
  onOpenChange,
  onDone,
}: {
  employee: Employee | null;
  open: boolean;
  onOpenChange: (v: boolean) => void;
  onDone: () => void;
}) {
  const [preview, setPreview] = useState<string | null>(null);
  const [file, setFile] = useState<File | null>(null);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  function reset() {
    setPreview(null);
    setFile(null);
    setError(null);
  }

  function handleClose(v: boolean) {
    if (!v) reset();
    onOpenChange(v);
  }

  function handleFile(e: React.ChangeEvent<HTMLInputElement>) {
    const f = e.target.files?.[0];
    if (!f) return;
    if (f.size > 2 * 1024 * 1024) { setError("La foto no puede superar 2 MB."); return; }
    if (!["image/jpeg", "image/png", "image/webp"].includes(f.type)) { setError("Formato no válido. Usa JPG, PNG o WebP."); return; }
    setError(null);
    setFile(f);
    setPreview(URL.createObjectURL(f));
  }

  async function handleUpload() {
    if (!file || !employee) return;
    setUploading(true);
    setError(null);
    const fd = new FormData();
    fd.append("avatar", file);
    try {
      await api.post(`/employees/${employee.id}/avatar`, fd, { headers: { "Content-Type": "multipart/form-data" } });
      onDone();
      handleClose(false);
    } catch (err) {
      const status = (err as { response?: { status?: number } }).response?.status;
      setError(status === 403 ? "Sin permiso para modificar empleados." : "La foto es demasiado grande o el formato no es válido.");
    } finally {
      setUploading(false);
    }
  }

  async function handleDelete() {
    if (!employee) return;
    setUploading(true);
    setError(null);
    try {
      await api.delete(`/employees/${employee.id}/avatar`);
      onDone();
      handleClose(false);
    } catch (err) {
      const status = (err as { response?: { status?: number } }).response?.status;
      setError(status === 403 ? "Sin permiso para modificar empleados." : "Error al eliminar la foto.");
    } finally {
      setUploading(false);
    }
  }

  if (!employee) return null;
  const name = `${employee.first_name} ${employee.last_name}`;

  return (
    <Dialog open={open} onOpenChange={handleClose}>
      <DialogContent className="max-w-sm">
        <DialogHeader>
          <DialogTitle>Foto de {name}</DialogTitle>
        </DialogHeader>

        <div className="flex flex-col items-center gap-4">
          {/* Current / preview */}
          <div className="relative">
            {preview
              ? <Image unoptimized src={preview} alt="preview" width={80} height={80} className="h-20 w-20 rounded-full object-cover" />
              : <EmployeeAvatar employee={employee} size="md" />
            }
          </div>

          {/* File input */}
          <input
            ref={inputRef}
            type="file"
            accept="image/jpeg,image/png,image/webp"
            className="hidden"
            onChange={handleFile}
          />
          <Button type="button" variant="outline" size="sm" onClick={() => inputRef.current?.click()}>
            <Upload className="h-4 w-4" /> Seleccionar foto
          </Button>

          {error && <p className="text-xs text-destructive">{error}</p>}
        </div>

        <DialogFooter className="gap-2">
          {employee.avatar_url && !preview && (
            <Button type="button" variant="ghost" size="sm" className="text-destructive hover:text-destructive" onClick={handleDelete} disabled={uploading}>
              <Trash2 className="h-4 w-4" /> Eliminar foto
            </Button>
          )}
          <Button type="button" variant="outline" size="sm" onClick={() => handleClose(false)} disabled={uploading}>Cancelar</Button>
          <Button type="button" size="sm" onClick={handleUpload} disabled={!file || uploading}>
            {uploading ? "Subiendo..." : "Subir"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

const STATUS_FILTER_OPTIONS = [
  { value: "", label: "Todos los estados" },
  { value: "active", label: "Activo" },
  { value: "pending_withdrawal", label: "En proceso de retiro" },
  { value: "inactive", label: "Inactivo" },
  { value: "on_leave", label: "En licencia" },
  { value: "terminated", label: "Terminado" },
];

const columns: AppColumnDef<Employee>[] = [
  { id: "avatar", header: "", cell: ({ row }) => <EmployeeAvatar employee={row.original} /> },
  { accessorKey: "employee_code", header: "Codigo" },
  { header: "Nombre", cell: ({ row }) => row.original.full_name ?? `${row.original.first_name} ${row.original.last_name}` },
  { accessorKey: "email", header: "Correo" },
  { header: "Area", cell: ({ row }) => row.original.department?.name ?? "Sin area" },
  { header: "Cargo", cell: ({ row }) => row.original.position?.name ?? "Sin cargo" },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.employment_status)}>{badgeLabel(row.original.employment_status)}</Badge> },
];

const NAME_PATTERN = "[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ '.\\-]*";

const fields: CrudField[] = [
  { name: "employee_code", label: "Codigo", required: true, pattern: "[A-Za-z0-9\\-]+", hint: "Letras, numeros y guion" },
  { name: "first_name", label: "Nombres", required: true, pattern: NAME_PATTERN, hint: "Solo letras" },
  { name: "last_name", label: "Apellidos", required: true, pattern: NAME_PATTERN, hint: "Solo letras" },
  { name: "identification_type", label: "Tipo de documento", type: "select", required: true, options: EMPLOYEE_IDENTIFICATION_TYPE_OPTIONS },
  { name: "identification_number", label: "Numero de documento", required: true, pattern: "[0-9][0-9.\\-]*", hint: "Solo numeros" },
  { name: "email", label: "Correo", type: "email", hint: "ejemplo@dominio.com" },
  { name: "hire_date", label: "Fecha de ingreso", type: "date", required: true },
  {
    name: "employment_status",
    label: "Estado",
    type: "select",
    required: true,
    defaultValue: "active",
    createHidden: true,
    options: [
      ...STATUS_OPTIONS,
      { label: "Terminado", value: "terminated" },
      { label: "En licencia", value: "on_leave" },
      { label: "En proceso de retiro", value: "pending_withdrawal" },
    ],
  },
  { name: "eps", label: "EPS" },
  { name: "arl", label: "ARL" },
  { name: "pension_fund", label: "Fondo de pension" },
  { name: "compensation_fund", label: "Caja de compensacion" },
  { name: "department_id", label: "Area", type: "relation-select", endpoint: "/departments?per_page=100" },
  { name: "position_id", label: "Cargo", type: "relation-select", endpoint: "/positions?per_page=100" },
  { name: "salary", label: "Salario", type: "number", min: 0, hint: "Solo numeros, sin negativos" },
];

type EmployeeStats = { total_active: number; hired_this_month: number; by_contract_type: Record<string, number> };

export default function EmployeesPage() {
  const [statusFilter, setStatusFilter] = useState("");
  const [avatarEmployee, setAvatarEmployee] = useState<Employee | null>(null);
  const [avatarOpen, setAvatarOpen] = useState(false);
  const refreshRef = useRef<(() => void) | null>(null);
  const currentUser = getStoredUser();
  const canManage = hasAnyPermission(currentUser, ["employees.manage"]);

  const [stats, setStats] = useState<EmployeeStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<EmployeeStats>("/employees/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const extraParams = statusFilter ? { status: statusFilter } : undefined;

  const indefinidoCount = stats?.by_contract_type?.["indefinido"] ?? stats?.by_contract_type?.["permanent"] ?? null;

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <KpiCard label="Empleados activos" value={stats?.total_active ?? null} icon={Users} status={statsStatus} />
      <KpiCard label="Ingresos este mes" value={stats?.hired_this_month ?? null} icon={UserPlus} status={statsStatus} />
      <KpiCard label="Contrato indefinido" value={indefinidoCount} icon={FileText} status={statsStatus} />
    </div>
  );

  const filterSlot = (
    <select
      value={statusFilter}
      onChange={(e) => setStatusFilter(e.target.value)}
      className="w-52 rounded-md border border-input bg-background px-3 py-2 text-sm"
    >
      {STATUS_FILTER_OPTIONS.map((opt) => (
        <option key={opt.value} value={opt.value}>{opt.label}</option>
      ))}
    </select>
  );

  return (
    <>
      <ModuleTablePage
        title="Empleados"
        description="Listado central con busqueda, paginacion backend y exportaciones."
        resource="/employees"
        exportResource="employees"
        columns={columns}
        fields={fields}
        actionLabel="Nuevo empleado"
        modalDescription="Captura la informacion basica del colaborador."
        extraParams={extraParams}
        filterSlot={filterSlot}
        statsSlot={statsSlot}
        extraRowActions={(row, refresh) => {
          refreshRef.current = refresh;
          return (
            <>
              {canManage && (
                <button
                  onClick={() => { setAvatarEmployee(row); setAvatarOpen(true); }}
                  className="inline-flex h-8 items-center gap-2 rounded-md px-2 text-sm hover:bg-muted"
                  title="Editar foto"
                >
                  <Camera className="h-4 w-4" />
                </button>
              )}
              <Link href={`/app/empleados/${row.id}`} className="inline-flex h-8 items-center gap-2 rounded-md px-2 text-sm hover:bg-muted">
                <Eye className="h-4 w-4" /> Ver
              </Link>
              <ToggleStatusAction
                resource="/employees"
                id={row.id}
                active={row.employment_status === "active"}
                refresh={refresh}
                field="employment_status"
              />
            </>
          );
        }}
      />
      <AvatarUploadModal
        employee={avatarEmployee}
        open={avatarOpen}
        onOpenChange={setAvatarOpen}
        onDone={() => { setAvatarOpen(false); refreshRef.current?.(); }}
      />
    </>
  );
}
