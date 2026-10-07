"use client";

import * as React from "react";
import { CrudField } from "@/components/crud/crud-modal";
import { ToggleStatusAction } from "@/components/crud/toggle-status-action";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { STATUS_OPTIONS } from "@/lib/constants";
import { api, PaginatedResponse } from "@/lib/api";
import { AppColumnDef } from "@/lib/table-types";
import { AppUser, Role } from "@/lib/types";
import { updateStoredUser, getStoredUser } from "@/lib/auth";

function UserAvatar({ name, avatarUrl }: { name?: string; avatarUrl?: string | null }) {
  const initials = name
    ? name.split(" ").slice(0, 2).map((w) => w[0]).join("").toUpperCase()
    : "?";
  if (avatarUrl) {
    return <img src={avatarUrl} alt={name} className="h-7 w-7 rounded-full object-cover ring-1 ring-border" />;
  }
  return (
    <span className="flex h-7 w-7 items-center justify-center rounded-full bg-primary/10 text-[10px] font-semibold text-primary">
      {initials}
    </span>
  );
}

function AvatarActionDialog({
  user,
  onClose,
  onSaved,
}: {
  user: AppUser;
  onClose: () => void;
  onSaved: () => void;
}) {
  const [preview, setPreview] = React.useState<string | null>(user.avatar_url ?? null);
  const [saving, setSaving] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const fileRef = React.useRef<HTMLInputElement>(null);

  const handleFile = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setPreview(URL.createObjectURL(file));
    setError(null);
  };

  const upload = async () => {
    const file = fileRef.current?.files?.[0];
    if (!file) return;
    setSaving(true);
    setError(null);
    try {
      const fd = new FormData();
      fd.append("avatar", file);
      const r = await api.post(`/users/${user.id}/avatar`, fd);
      const stored = getStoredUser();
      if (stored && stored.id === user.id) {
        updateStoredUser({ ...stored, avatar_url: r.data.avatar_url });
      }
      onSaved();
      onClose();
    } catch {
      setError("Error al subir la imagen. Verifica el formato (JPG/PNG/WebP, máx 2 MB).");
    } finally {
      setSaving(false);
    }
  };

  const remove = async () => {
    setSaving(true);
    setError(null);
    try {
      await api.delete(`/users/${user.id}/avatar`);
      const stored = getStoredUser();
      if (stored && stored.id === user.id) {
        updateStoredUser({ ...stored, avatar_url: null });
      }
      onSaved();
      onClose();
    } catch {
      setError("Error al eliminar el avatar.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog open onOpenChange={(v) => !v && onClose()}>
      <DialogContent className="max-w-sm">
        <DialogHeader>
          <DialogTitle>Avatar de {user.name}</DialogTitle>
        </DialogHeader>
        <div className="space-y-4">
          <div className="flex justify-center">
            {preview ? (
              <img src={preview} alt="Preview" className="h-24 w-24 rounded-full object-cover ring-2 ring-border" />
            ) : (
              <span className="flex h-24 w-24 items-center justify-center rounded-full bg-primary/10 text-2xl font-semibold text-primary">
                {user.name?.split(" ").slice(0, 2).map((w) => w[0]).join("").toUpperCase() ?? "?"}
              </span>
            )}
          </div>
          {error && <p className="text-sm text-destructive">{error}</p>}
          <input
            ref={fileRef}
            type="file"
            accept="image/jpeg,image/png,image/webp"
            onChange={handleFile}
            className="block w-full text-sm text-foreground file:mr-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary-foreground hover:file:bg-primary/90"
          />
          <div className="flex gap-2 justify-end">
            <Button variant="outline" onClick={onClose} disabled={saving}>Cancelar</Button>
            {user.avatar_url && (
              <Button variant="outline" onClick={remove} disabled={saving} className="text-destructive hover:text-destructive">
                {saving ? "..." : "Eliminar"}
              </Button>
            )}
            <Button onClick={upload} disabled={saving || !fileRef.current?.files?.length}>
              {saving ? "Subiendo..." : "Guardar"}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}

const columns: AppColumnDef<AppUser>[] = [
  {
    header: "Usuario",
    cell: ({ row }) => (
      <div className="flex items-center gap-2">
        <UserAvatar name={row.original.name} avatarUrl={row.original.avatar_url} />
        <span>{row.original.name}</span>
      </div>
    ),
  },
  { accessorKey: "email", header: "Correo" },
  { header: "Empleado", cell: ({ row }) => row.original.employee?.full_name ?? "Sin vincular" },
  { header: "Roles", cell: ({ row }) => row.original.roles?.join(", ") || "Sin rol" },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{row.original.status}</Badge> },
];

const baseFields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "email", label: "Correo", type: "email", required: true },
  { name: "password", label: "Contrasena", type: "password", placeholder: "Dejar en blanco para generar una automatica", omitWhenEmpty: true },
  { name: "employee_id", label: "Empleado", type: "relation-select", endpoint: "/employees/selector" },
  { name: "status", label: "Estado", type: "select", required: true, options: STATUS_OPTIONS },
  { name: "avatar", label: "Foto de perfil", type: "file", accept: "image/jpeg,image/png,image/webp", colSpan: "full" },
];

export default function AppUsersPage() {
  const [roles, setRoles] = React.useState<Role[]>([]);
  const [avatarTarget, setAvatarTarget] = React.useState<AppUser | null>(null);

  React.useEffect(() => {
    api
      .get<PaginatedResponse<Role>>("/roles", { params: { per_page: 100 } })
      .then((response) => setRoles(response.data.data))
      .catch(() => {});
  }, []);

  const fields = React.useMemo<CrudField[]>(() => {
    const roleField: CrudField = {
      name: "role",
      label: "Rol",
      type: "select",
      omitWhenEmpty: true,
      options: roles.map((role) => ({ label: role.name, value: role.name })),
    };
    return [...baseFields.slice(0, 4), roleField, ...baseFields.slice(4)];
  }, [roles]);

  return (
    <>
      <ModuleTablePage
        title="Usuarios"
        description="Cuentas de acceso al panel, vinculadas opcionalmente a un empleado."
        resource="/users"
        columns={columns}
        fields={fields}
        actionLabel="Nuevo usuario"
        modalDescription="Si dejas la contrasena en blanco se genera una temporal y se muestra al crear."
        extraRowActions={(row, refresh) => (
          <>
            <Button variant="ghost" size="sm" onClick={() => setAvatarTarget(row)}>
              Avatar
            </Button>
            <ToggleStatusAction resource="/users" id={row.id} active={row.status === "active"} refresh={refresh} />
          </>
        )}
      />
      {avatarTarget && (
        <AvatarActionDialog
          user={avatarTarget}
          onClose={() => setAvatarTarget(null)}
          onSaved={() => setAvatarTarget(null)}
        />
      )}
    </>
  );
}
