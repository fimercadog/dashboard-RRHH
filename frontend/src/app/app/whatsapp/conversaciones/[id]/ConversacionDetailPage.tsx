"use client";

import { useState, useEffect, useRef, useCallback } from "react";
import { useParams } from "next/navigation";
import { ArrowLeft, AlertTriangle, User, Send, RefreshCw } from "lucide-react";
import Link from "next/link";
import { api } from "@/lib/api";
import { Badge, badgeVariant, badgeLabel } from "@/components/ui/badge";
import { getStoredUser, hasAnyPermission } from "@/lib/auth";
import { cn } from "@/lib/utils";

interface ConvUser {
  id: number;
  name: string;
  email?: string;
}

interface Conversation {
  id: number;
  wa_id: string;
  contact_name: string | null;
  status: string;
  is_within_window: boolean;
  last_message_at: string | null;
  assigned_user: ConvUser | null;
  created_at: string;
}

interface Message {
  id: number;
  direction: "inbound" | "outbound";
  type: string;
  content: string | null;
  created_at: string;
}

function formatTime(dateStr: string): string {
  return new Date(dateStr).toLocaleString("es-CO", { dateStyle: "short", timeStyle: "short" });
}

export default function ConversacionDetailPage() {
  const { id } = useParams<{ id: string }>();

  const [conv, setConv] = useState<Conversation | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [users, setUsers] = useState<ConvUser[]>([]);
  const [selectedUserId, setSelectedUserId] = useState<number | "">("");
  const [messageText, setMessageText] = useState("");

  const [loadingConv, setLoadingConv] = useState(true);
  const [loadingMsgs, setLoadingMsgs] = useState(true);
  const [sending, setSending] = useState(false);
  const [actioning, setActioning] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [sendError, setSendError] = useState<string | null>(null);

  const messagesEndRef = useRef<HTMLDivElement>(null);
  const currentUser = getStoredUser();
  const canManage = hasAnyPermission(currentUser, ["conversations.manage"]);

  const loadConv = useCallback(async () => {
    setLoadingConv(true);
    setError(null);
    try {
      const res = await api.get<{ data: Conversation }>(`/conversations/${id}`);
      setConv(res.data.data ?? res.data as unknown as Conversation);
    } catch {
      setError("No se pudo cargar la conversación.");
    } finally {
      setLoadingConv(false);
    }
  }, [id]);

  const loadMessages = useCallback(async () => {
    setLoadingMsgs(true);
    try {
      const res = await api.get<{ data: Message[] }>(`/conversations/${id}/messages`);
      setMessages(res.data.data ?? res.data as unknown as Message[]);
    } catch {
      // messages not fatal
    } finally {
      setLoadingMsgs(false);
    }
  }, [id]);

  const loadUsers = useCallback(async () => {
    try {
      const res = await api.get<{ data: ConvUser[] }>("/users");
      setUsers(res.data.data ?? []);
    } catch {
      // users not fatal
    }
  }, []);

  useEffect(() => {
    loadConv();
    loadMessages();
    loadUsers();
  }, [loadConv, loadMessages, loadUsers]);

  // Scroll to bottom when messages load/arrive
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  async function handleSend() {
    if (!messageText.trim()) return;
    setSending(true);
    setSendError(null);
    try {
      const res = await api.post<{ data: Message }>(`/conversations/${id}/messages`, {
        content: messageText.trim(),
      });
      const newMsg = res.data.data ?? res.data as unknown as Message;
      setMessages((prev) => [...prev, newMsg]);
      setMessageText("");
    } catch (e: unknown) {
      const msg = (e as { response?: { data?: { message?: string } } })?.response?.data?.message;
      setSendError(msg ?? "No se pudo enviar el mensaje.");
    } finally {
      setSending(false);
    }
  }

  async function handleAction(action: "close" | "reopen" | "transfer") {
    setActioning(true);
    setError(null);
    try {
      await api.post(`/conversations/${id}/${action}`);
      await loadConv();
    } catch {
      setError(`No se pudo ejecutar la acción "${action}".`);
    } finally {
      setActioning(false);
    }
  }

  async function handleAssign() {
    if (!selectedUserId && !currentUser) return;
    setActioning(true);
    setError(null);
    try {
      const userId = selectedUserId || currentUser?.id;
      await api.post(`/conversations/${id}/assign`, { user_id: userId });
      await loadConv();
    } catch {
      setError("No se pudo asignar la conversación.");
    } finally {
      setActioning(false);
    }
  }

  if (loadingConv) {
    return (
      <div className="py-16 text-center text-sm text-muted-foreground">Cargando conversación...</div>
    );
  }

  if (error && !conv) {
    return (
      <div className="space-y-4">
        <Link href="/app/whatsapp/conversaciones" className="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
          <ArrowLeft className="h-4 w-4" /> Volver
        </Link>
        <div className="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">{error}</div>
      </div>
    );
  }

  const isOpen = conv?.status === "open";
  const isClosed = conv?.status === "closed";
  const withinWindow = conv?.is_within_window ?? false;
  const canSend = canManage && withinWindow && isOpen;

  return (
    <div className="flex h-[calc(100vh-7rem)] flex-col gap-0">
      {/* Header */}
      <div className="shrink-0 space-y-3 pb-3">
        <Link href="/app/whatsapp/conversaciones" className="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
          <ArrowLeft className="h-4 w-4" /> Conversaciones
        </Link>

        <div className="rounded-lg border border-border bg-card p-4">
          <div className="flex flex-wrap items-start justify-between gap-3">
            {/* Contact info */}
            <div className="flex items-center gap-3">
              <span className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                {(conv?.contact_name ?? conv?.wa_id)?.[0]?.toUpperCase() ?? "?"}
              </span>
              <div>
                <p className="font-medium text-foreground">{conv?.contact_name ?? conv?.wa_id}</p>
                <p className="text-xs text-muted-foreground">{conv?.wa_id}</p>
              </div>
            </div>

            {/* Status + window badge */}
            <div className="flex flex-wrap items-center gap-2">
              {conv?.status && (
                <Badge variant={badgeVariant(conv.status)}>{badgeLabel(conv.status)}</Badge>
              )}
              {!withinWindow && (
                <Badge variant="warning" className="flex items-center gap-1">
                  <AlertTriangle className="h-3 w-3" /> Fuera de ventana 24h
                </Badge>
              )}
              {conv?.assigned_user && (
                <span className="flex items-center gap-1 rounded-md bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                  <User className="h-3 w-3" /> {conv.assigned_user.name}
                </span>
              )}
            </div>
          </div>

          {error && (
            <div className="mt-3 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-xs text-destructive">{error}</div>
          )}

          {/* Actions */}
          <div className="mt-3 flex flex-wrap items-center gap-2">
            {/* Assign */}
            <div className="flex items-center gap-1.5">
              <select
                value={selectedUserId}
                onChange={(e) => setSelectedUserId(e.target.value ? Number(e.target.value) : "")}
                className="h-8 rounded-md border border-border bg-background px-2 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
              >
                <option value="">Asignar a mí</option>
                {users.map((u) => (
                  <option key={u.id} value={u.id}>{u.name}</option>
                ))}
              </select>
              <button
                disabled={actioning}
                onClick={handleAssign}
                className="h-8 rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground disabled:opacity-50 hover:bg-primary/90"
              >
                Asignar
              </button>
            </div>

            {isOpen && (
              <button
                disabled={actioning}
                onClick={() => handleAction("close")}
                className="h-8 rounded-md border border-border bg-muted px-3 text-xs font-medium text-muted-foreground disabled:opacity-50 hover:bg-border"
              >
                Cerrar
              </button>
            )}
            {isOpen && (
              <button
                disabled={actioning}
                onClick={() => handleAction("transfer")}
                className="h-8 rounded-md border border-border bg-muted px-3 text-xs font-medium text-muted-foreground disabled:opacity-50 hover:bg-border"
              >
                Transferir
              </button>
            )}
            {isClosed && (
              <button
                disabled={actioning}
                onClick={() => handleAction("reopen")}
                className="h-8 rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground disabled:opacity-50 hover:bg-primary/90"
              >
                Reabrir
              </button>
            )}
            <button
              disabled={loadingMsgs}
              onClick={loadMessages}
              title="Refrescar mensajes"
              className="flex h-8 items-center gap-1 rounded-md border border-border px-2 text-xs text-muted-foreground hover:bg-muted"
            >
              <RefreshCw className={cn("h-3.5 w-3.5", loadingMsgs && "animate-spin")} />
            </button>
          </div>
        </div>
      </div>

      {/* Messages area */}
      <div className="flex-1 overflow-y-auto rounded-lg border border-border bg-muted/30 p-4">
        {loadingMsgs && messages.length === 0 ? (
          <div className="py-10 text-center text-sm text-muted-foreground">Cargando mensajes...</div>
        ) : messages.length === 0 ? (
          <div className="py-10 text-center text-sm text-muted-foreground">Sin mensajes todavía.</div>
        ) : (
          <div className="space-y-3">
            {messages.map((msg) => {
              const isOutbound = msg.direction === "outbound";
              return (
                <div key={msg.id} className={cn("flex", isOutbound ? "justify-end" : "justify-start")}>
                  <div
                    className={cn(
                      "max-w-[75%] rounded-2xl px-4 py-2.5 text-sm shadow-sm",
                      isOutbound
                        ? "rounded-br-sm bg-primary text-primary-foreground"
                        : "rounded-bl-sm bg-card text-foreground border border-border",
                    )}
                  >
                    <p className="whitespace-pre-wrap break-words">{msg.content ?? `[${msg.type}]`}</p>
                    <p className={cn("mt-1 text-[10px]", isOutbound ? "text-primary-foreground/60 text-right" : "text-muted-foreground")}>
                      {formatTime(msg.created_at)}
                    </p>
                  </div>
                </div>
              );
            })}
            <div ref={messagesEndRef} />
          </div>
        )}
      </div>

      {/* Input or out-of-window notice */}
      <div className="shrink-0 pt-3">
        {canSend ? (
          <div className="flex gap-2">
            <input
              type="text"
              value={messageText}
              onChange={(e) => setMessageText(e.target.value)}
              onKeyDown={(e) => { if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); handleSend(); } }}
              placeholder="Escribe un mensaje…"
              disabled={sending}
              className="flex-1 rounded-lg border border-border bg-card px-4 py-2.5 text-sm text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-primary/50 disabled:opacity-50"
            />
            <button
              onClick={handleSend}
              disabled={sending || !messageText.trim()}
              className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground disabled:opacity-50 hover:bg-primary/90"
              title="Enviar"
            >
              <Send className="h-4 w-4" />
            </button>
          </div>
        ) : (
          <div className={cn(
            "rounded-lg border px-4 py-3 text-sm",
            !withinWindow
              ? "border-warning/30 bg-warning/10 text-warning"
              : "border-border bg-muted text-muted-foreground",
          )}>
            {!withinWindow
              ? "Esta conversación está fuera de la ventana de 24 horas. No se pueden enviar mensajes libres."
              : isClosed
              ? "La conversación está cerrada."
              : "No tienes permiso para enviar mensajes."}
          </div>
        )}
        {sendError && (
          <p className="mt-1.5 text-xs text-destructive">{sendError}</p>
        )}
      </div>
    </div>
  );
}
