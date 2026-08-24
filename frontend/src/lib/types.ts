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
  roles: string[];
};
