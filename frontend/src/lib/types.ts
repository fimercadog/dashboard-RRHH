export type Employee = {
  id: number;
  employee_code: string;
  full_name?: string;
  first_name: string;
  last_name: string;
  email?: string;
  employment_status: string;
  department?: { id: number; name: string };
  position?: { id: number; name: string };
  hire_date?: string;
};

export type Attendance = {
  id: number;
  employee?: Employee;
  date: string;
  status: string;
  check_in?: string;
  check_out?: string;
  late_minutes: number;
};

export type RequestRow = {
  id: number;
  employee?: Employee;
  type?: string;
  start_date: string;
  end_date: string;
  status: string;
  requested_days?: number;
  days?: number;
};

export type DocumentRow = {
  id: number;
  employee?: Employee;
  document_type: string;
  name: string;
  expiration_date?: string;
  status: string;
};

export type Role = {
  id: number;
  name: string;
  guard_name: string;
  status: string;
  permissions_count?: number;
};

export type AppUser = {
  id: number;
  name: string;
  email: string;
  status: string;
  employee?: Employee;
  role?: string;
  roles: string[];
};

export type Client = {
  id: number;
  first_name: string;
  last_name: string;
  full_name?: string;
  company_name?: string;
  identification_type?: string;
  identification_number?: string;
  email?: string;
  phone?: string;
  city?: string;
  notes?: string;
  status: string;
};

// ── Inventario — Fase B ─────────────────────────────────────────────────────

export type Category = {
  id: number;
  name: string;
  status: string;
  parent?: { id: number; name: string };
};

export type Brand = {
  id: number;
  name: string;
  status: string;
};

export type Unit = {
  id: number;
  name: string;
  abbreviation: string;
  status: string;
};

export type Warehouse = {
  id: number;
  name: string;
  location?: string;
  status: string;
};

export type Product = {
  id: number;
  sku?: string;
  name: string;
  description?: string;
  type: "storable" | "service" | "consumable";
  cost_price: number;
  sale_price: number;
  min_stock?: number;
  status: string;
  category?: { id: number; name: string };
  brand?: { id: number; name: string };
  unit?: { id: number; name: string; abbreviation: string };
};

export type ProductStock = {
  id: number;
  quantity: number;
  avg_cost?: number;
  product?: { id: number; sku?: string; name: string; min_stock?: number; type: string };
  warehouse?: { id: number; name: string };
};

export type StockMovement = {
  id: number;
  type: "entry" | "exit" | "transfer_out" | "transfer_in" | "adjustment" | "opening";
  quantity: number;
  unit_cost?: number;
  total_cost?: number;
  transfer_id?: string;
  reference_type?: string;
  reference_id?: number;
  notes?: string;
  posted_at?: string;
  created_at: string;
  product?: { id: number; sku?: string; name: string };
  warehouse?: { id: number; name: string };
  user?: { id: number; name: string };
};

// ── CRM — Fase A ────────────────────────────────────────────────────────────

export type Contact = {
  id: number;
  client_id: number;
  name: string;
  job_title?: string;
  email?: string;
  phone?: string;
  notes?: string;
  client?: Client;
};

export type ClientNote = {
  id: number;
  client_id: number;
  user_id?: number;
  body: string;
  user?: { id: number; name: string };
  created_at: string;
};

export type Deal = {
  id: number;
  client_id: number;
  owner_id?: number;
  title: string;
  amount: number;
  stage: "prospecting" | "qualification" | "proposal" | "negotiation" | "won" | "lost" | "stalled";
  expected_close_date?: string;
  notes?: string;
  client?: Client;
  owner?: { id: number; name: string };
};

export type Activity = {
  id: number;
  deal_id?: number;
  client_id?: number;
  contact_id?: number;
  user_id?: number;
  type: "call" | "email" | "meeting" | "note" | "task";
  title: string;
  body?: string;
  due_at?: string;
  done_at?: string;
  status: "pending" | "done" | "cancelled";
  user?: { id: number; name: string };
  deal?: { id: number; title: string };
  client?: Client;
};

export type Segment = {
  id: number;
  name: string;
  description?: string;
};

