// Static export: IDs are not known at build time; page renders client-side from API
export const dynamicParams = false;
// .htaccess rewrites all /app/empleados/[realId] to this shell; useParams reads the actual URL
export function generateStaticParams() { return [{ id: "_" }]; }

import { EmployeeProfileClient } from "./employee-profile-client";

export default function EmployeeProfilePage() {
  return <EmployeeProfileClient />;
}
