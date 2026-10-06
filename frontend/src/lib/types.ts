export type Employee = {
  id: number;
  employee_code: string;
  full_name?: string;
  first_name: string;
  middle_name?: string;
  last_name: string;
  second_last_name?: string;
  identification_type?: string;
  identification_number?: string;
  email?: string;
  phone?: string;
  birth_date?: string;
  gender?: string;
  address?: string;
  city?: string;
  emergency_contact_name?: string;
  emergency_contact_phone?: string;
  hire_date?: string;
  termination_date?: string;
  employment_status: string;
  contract_type?: string;
  salary?: number;
  work_schedule?: string;
  notes?: string;
  department?: { id: number; name: string };
  position?: { id: number; name: string };
  manager?: { id: number; first_name: string; last_name: string; full_name?: string };
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
  avatar_url?: string | null;
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

// ── Compras — Fase C ────────────────────────────────────────────────────────

export type Supplier = {
  id: number;
  name: string;
  nit?: string;
  email?: string;
  phone?: string;
  address?: string;
  payment_terms?: number;
  status: string;
  created_at?: string;
};

export type PurchaseOrderItem = {
  id: number;
  product_id: number;
  quantity: number;
  unit_cost: number;
  subtotal: number;
  received_qty?: number;
  product?: { id: number; sku?: string; name: string };
};

export type PurchaseOrder = {
  id: number;
  number: string;
  date: string;
  expected_date?: string;
  status: "draft" | "sent" | "partial" | "received" | "cancelled";
  subtotal: number;
  tax: number;
  total: number;
  notes?: string;
  supplier?: { id: number; name: string };
  warehouse?: { id: number; name: string };
  items?: PurchaseOrderItem[];
  created_at?: string;
};

export type PurchaseReceiptItem = {
  id: number;
  product_id: number;
  quantity: number;
  unit_cost: number;
  product?: { id: number; sku?: string; name: string };
};

export type PurchaseReceipt = {
  id: number;
  number: string;
  date: string;
  type: "receipt" | "return";
  status: "draft" | "posted" | "cancelled";
  notes?: string;
  purchase_order_id?: number;
  supplier?: { id: number; name: string };
  warehouse?: { id: number; name: string };
  items?: PurchaseReceiptItem[];
  created_at?: string;
};

export type PurchaseInvoice = {
  id: number;
  number: string;
  date: string;
  due_date?: string;
  status: "draft" | "posted" | "partially_paid" | "paid" | "cancelled";
  subtotal: number;
  tax: number;
  total: number;
  notes?: string;
  purchase_order_id?: number;
  purchase_receipt_id?: number;
  supplier?: { id: number; name: string };
  account_payable?: AccountPayable;
  created_at?: string;
};

export type AccountPayable = {
  id: number;
  amount: number;
  balance: number;
  due_date?: string;
  status: "open" | "partially_paid" | "paid" | "cancelled";
  purchase_invoice_id?: number;
  supplier?: { id: number; name: string };
  purchase_invoice?: { id: number; number: string; total: number };
  created_at?: string;
};

// ── Ventas — Fase D ──────────────────────────────────────────────────────────

export type QuoteItem = {
  id: number;
  product_id: number;
  quantity: number;
  unit_price: number;
  discount_pct: number;
  subtotal: number;
  product?: { id: number; sku?: string; name: string };
};

export type Quote = {
  id: number;
  number: string;
  date: string;
  valid_until: string;
  status: "draft" | "sent" | "accepted" | "expired" | "cancelled";
  subtotal: number;
  discount_total: number;
  tax: number;
  total: number;
  notes?: string;
  client?: { id: number; name: string };
  deal?: { id: number; title: string };
  items?: QuoteItem[];
  created_at?: string;
};

export type SaleOrderItem = {
  id: number;
  product_id: number;
  quantity: number;
  unit_price: number;
  discount_pct: number;
  subtotal: number;
  delivered_qty: number;
  product?: { id: number; sku?: string; name: string };
};

export type SaleOrder = {
  id: number;
  number: string;
  date: string;
  status: "draft" | "confirmed" | "partial" | "fulfilled" | "cancelled";
  subtotal: number;
  discount_total: number;
  tax: number;
  total: number;
  notes?: string;
  client?: { id: number; name: string };
  warehouse?: { id: number; name: string };
  quote?: { id: number; number: string };
  items?: SaleOrderItem[];
  created_at?: string;
};

export type SaleInvoiceItem = {
  id: number;
  product_id: number;
  quantity: number;
  unit_price: number;
  discount_pct: number;
  subtotal: number;
  cost_at_time: number;
  product?: { id: number; sku?: string; name: string };
};

export type SaleInvoice = {
  id: number;
  number: string;
  date: string;
  due_date: string;
  type: "invoice" | "return";
  status: "draft" | "posted" | "partially_paid" | "paid" | "cancelled";
  subtotal: number;
  discount_total: number;
  tax: number;
  total: number;
  notes?: string;
  client?: { id: number; name: string };
  sale_order?: { id: number; number: string };
  items?: SaleInvoiceItem[];
  account_receivable?: AccountsReceivable;
  created_at?: string;
};

export type AccountsReceivable = {
  id: number;
  amount: number;
  balance: number;
  due_date: string;
  status: "open" | "partially_paid" | "paid" | "cancelled";
  sale_invoice_id?: number;
  client?: { id: number; name: string };
  sale_invoice?: { id: number; number: string; total: number };
  created_at?: string;
};

// ── Finanzas — Fase E ────────────────────────────────────────────────────────

export type CashAccount = {
  id: number;
  name: string;
  type: "cash" | "bank" | "savings";
  currency: string;
  balance: number;
  status: "active" | "inactive";
  notes?: string;
  created_at?: string;
};

export type Payment = {
  id: number;
  payable_type: "accounts_receivable" | "accounts_payable";
  payable_id: number;
  amount: number;
  date: string;
  method: "cash" | "transfer" | "check" | "card" | "other";
  reference?: string;
  notes?: string;
  status: "active" | "cancelled";
  cash_account?: { id: number; name: string };
  user?: { id: number; name: string };
  created_at?: string;
};

export type FinancialTransaction = {
  id: number;
  type: "income" | "expense" | "transfer_in" | "transfer_out";
  amount: number;
  date: string;
  reference_type: string;
  reference_id: number;
  description: string;
  cash_account?: { id: number; name: string };
  user?: { id: number; name: string };
  created_at?: string;
};

export type Transfer = {
  id: number;
  amount: number;
  date: string;
  reference?: string;
  notes?: string;
  status: "active" | "cancelled";
  from_account?: { id: number; name: string };
  to_account?: { id: number; name: string };
  user?: { id: number; name: string };
  created_at?: string;
};

// ── Contabilidad (Fase F) ──────────────────────────────────────────────────

export type ChartOfAccount = {
  id: number;
  code: string;
  name: string;
  type: "asset" | "liability" | "equity" | "revenue" | "expense" | "cost";
  nature: "debit" | "credit";
  parent_id: number | null;
  level: number;
  allows_movements: boolean;
  status: "active" | "inactive";
  notes?: string;
  created_at?: string;
};

export type AccountingPeriod = {
  id: number;
  name: string;
  start_date: string;
  end_date: string;
  status: "open" | "closed";
  closed_by?: number | null;
  closed_at?: string | null;
  entries_count?: number;
  created_at?: string;
};

export type JournalEntryLine = {
  id: number;
  account_id: number;
  account_code?: string;
  account_name?: string;
  description?: string;
  debit: number;
  credit: number;
  sequence: number;
};

export type JournalEntry = {
  id: number;
  number: string;
  date: string;
  description: string;
  status: "draft" | "posted" | "reversed";
  reference_type?: string;
  reference_id?: number;
  accounting_period_id: number;
  reversed_by_entry_id?: number | null;
  reversal_of_entry_id?: number | null;
  user_id?: number;
  lines?: JournalEntryLine[];
  created_at?: string;
};

export type AccountingAccountConfig = {
  id: number;
  config_key: string;
  account_id: number;
  account_code?: string;
  account_name?: string;
  created_at?: string;
};

