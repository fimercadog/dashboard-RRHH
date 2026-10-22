import http from 'k6/http';
import { check, sleep, fail } from 'k6';
import { Trend } from 'k6/metrics';
import { login, authHeaders } from '../lib/auth.js';

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8001';

// Métricas de tendencia para endpoints críticos
const trendEmployees     = new Trend('dur_employees',     true);
const trendAttendances   = new Trend('dur_attendances',   true);
const trendDashboard     = new Trend('dur_dashboard',     true);

export const options = {
  vus: 10,
  duration: '2m',
  thresholds: {
    http_req_failed:   ['rate<0.01'],
    http_req_duration: ['p(95)<2000', 'p(90)<1500'],
    dur_employees:     ['p(95)<2000'],
    dur_attendances:   ['p(95)<2000'],
    dur_dashboard:     ['p(95)<2000'],
  },
};

export function setup() {
  const token = login(BASE_URL);
  if (!token) fail('setup: token nulo tras login');
  return { token };
}

export default function (data) {
  const headers = authHeaders(data.token);

  // attendance-stats requiere ?from y ?to obligatorios
  const today    = new Date();
  const yyyy     = today.getFullYear();
  const mm       = String(today.getMonth() + 1).padStart(2, '0');
  const dd       = String(today.getDate()).padStart(2, '0');
  const dateTo   = `${yyyy}-${mm}-${dd}`;
  const dateFrom = `${yyyy}-${mm}-01`;

  // --- Endpoints críticos con métricas propias ---
  let res;

  res = http.get(`${BASE_URL}/api/employees?page=1&per_page=10`, { headers, tags: { name: 'employees list' } });
  trendEmployees.add(res.timings.duration);
  check(res, { 'employees list GET 200': (r) => r.status === 200 });
  sleep(0.5);

  res = http.get(`${BASE_URL}/api/employees/stats`, { headers, tags: { name: 'employees stats' } });
  check(res, { 'employees stats GET 200': (r) => r.status === 200 });
  sleep(0.5);

  res = http.get(`${BASE_URL}/api/attendances?page=1&per_page=10`, { headers, tags: { name: 'attendances list' } });
  trendAttendances.add(res.timings.duration);
  check(res, { 'attendances list GET 200': (r) => r.status === 200 });
  sleep(0.5);

  res = http.get(`${BASE_URL}/api/attendance-stats?from=${dateFrom}&to=${dateTo}`, { headers, tags: { name: 'attendance stats' } });
  check(res, { 'attendance stats GET 200': (r) => r.status === 200 });
  sleep(0.5);

  res = http.get(`${BASE_URL}/api/dashboard`, { headers, tags: { name: 'dashboard' } });
  trendDashboard.add(res.timings.duration);
  check(res, { 'dashboard GET 200': (r) => r.status === 200 });
  sleep(0.5);

  // --- Resto de endpoints ---
  const extra = [
    { url: `${BASE_URL}/api/clients?page=1&per_page=10`,                tag: 'clients list' },
    { url: `${BASE_URL}/api/clients/stats`,                             tag: 'clients stats' },
    { url: `${BASE_URL}/api/products?page=1&per_page=10`,               tag: 'products list' },
    { url: `${BASE_URL}/api/products/stats`,                            tag: 'products stats' },
    { url: `${BASE_URL}/api/financial-transactions?page=1&per_page=10`, tag: 'financial-transactions list' },
  ];

  for (const ep of extra) {
    const r = http.get(ep.url, { headers, tags: { name: ep.tag } });
    check(r, { [`${ep.tag} GET 200`]: (rr) => rr.status === 200 });
    sleep(0.5);
  }
}
