import http from 'k6/http';
import { check, sleep, fail } from 'k6';
import { login, authHeaders } from '../lib/auth.js';

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8001';

export const options = {
  vus: 1,
  duration: '30s',
  thresholds: {
    http_req_failed:   ['rate<0.01'],
    // 2 000 ms en local/dev (SQLite, php artisan serve). Objetivo de producción: < 800 ms.
    http_req_duration: ['p(95)<2000'],
  },
};

export function setup() {
  const token = login(BASE_URL);
  if (!token) fail('setup: token nulo tras login');
  return { token };
}

export default function (data) {
  const headers = authHeaders(data.token);

  // Endpoints GET seguros (solo lectura, paginados, sin efectos secundarios).
  // Permisos mínimos requeridos: employees.manage, attendance.manage, dashboard.view,
  // clients.manage, inventory.manage, finance.view, requests.approve, roles.manage.
  // El usuario admin@andespeople.co (seeder) tiene todos estos permisos.
  // attendance-stats requiere ?from y ?to (validación obligatoria en el controller).
  // El rango se fija a los últimos 30 días para que siempre sea válido.
  const today = new Date();
  const yyyy  = today.getFullYear();
  const mm    = String(today.getMonth() + 1).padStart(2, '0');
  const dd    = String(today.getDate()).padStart(2, '0');
  const dateTo   = `${yyyy}-${mm}-${dd}`;
  const dateFrom = `${yyyy}-${mm}-01`;

  const endpoints = [
    { url: `${BASE_URL}/api/employees?page=1&per_page=10`,              tag: 'employees list' },
    { url: `${BASE_URL}/api/employees/stats`,                           tag: 'employees stats' },
    { url: `${BASE_URL}/api/attendances?page=1&per_page=10`,            tag: 'attendances list' },
    // attendance-stats exige from+to; sin ellos devuelve 422
    { url: `${BASE_URL}/api/attendance-stats?from=${dateFrom}&to=${dateTo}`, tag: 'attendance stats' },
    { url: `${BASE_URL}/api/dashboard`,                                 tag: 'dashboard' },
    { url: `${BASE_URL}/api/clients?page=1&per_page=10`,                tag: 'clients list' },
    { url: `${BASE_URL}/api/products?page=1&per_page=10`,               tag: 'products list' },
    { url: `${BASE_URL}/api/financial-transactions?page=1&per_page=10`, tag: 'financial-transactions list' },
    { url: `${BASE_URL}/api/vacation-requests?page=1&per_page=10`,      tag: 'vacation-requests list' },
    { url: `${BASE_URL}/api/roles?page=1&per_page=10`,                  tag: 'roles list' },
  ];

  for (const ep of endpoints) {
    const res = http.get(ep.url, { headers, tags: { name: ep.tag } });
    check(res, {
      // Solo status 200. k6 v2 guarda headers en minúsculas (content-type, no Content-Type).
      [`${ep.tag} status 200`]: (r) => r.status === 200,
      [`${ep.tag} is json`]: (r) => (r.headers['content-type'] || r.headers['Content-Type'] || '').includes('application/json'),
    });
    sleep(1);
  }
}
