// ⚠️  NO ejecutar contra producción sin autorización expresa de Fidel.
// ⚠️  Ejecutar SOLO tras evaluar los resultados de load-low y load-medium.
import http from 'k6/http';
import { check, sleep, fail } from 'k6';
import { Trend } from 'k6/metrics';
import { login, authHeaders } from '../lib/auth.js';

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8001';

const trendEmployees   = new Trend('dur_employees',   true);
const trendAttendances = new Trend('dur_attendances', true);
const trendDashboard   = new Trend('dur_dashboard',   true);

export const options = {
  stages: [
    { duration: '2m', target: 100 }, // rampa 0 → 100 VUs
    { duration: '5m', target: 100 }, // carga sostenida
    { duration: '2m', target: 0   }, // rampa 100 → 0 VUs
  ],
  thresholds: {
    http_req_failed:   ['rate<0.05'],   // umbral relajado para carga alta
    http_req_duration: ['p(95)<3000'],  // provisional — ajustar con evidencia real
    dur_employees:     ['p(95)<3000'],
    dur_attendances:   ['p(95)<3000'],
    dur_dashboard:     ['p(95)<3000'],
  },
};

export function setup() {
  const token = login(BASE_URL);
  if (!token) fail('setup: token nulo tras login');
  return { token };
}

export default function (data) {
  const headers = authHeaders(data.token);

  let res;

  res = http.get(`${BASE_URL}/api/employees?page=1&per_page=10`, { headers, tags: { name: 'employees list' } });
  trendEmployees.add(res.timings.duration);
  check(res, { 'employees list status 200': (r) => r.status === 200 });
  sleep(0.3);

  res = http.get(`${BASE_URL}/api/attendances?page=1&per_page=10`, { headers, tags: { name: 'attendances list' } });
  trendAttendances.add(res.timings.duration);
  check(res, { 'attendances list status 200': (r) => r.status === 200 });
  sleep(0.3);

  res = http.get(`${BASE_URL}/api/dashboard`, { headers, tags: { name: 'dashboard' } });
  trendDashboard.add(res.timings.duration);
  check(res, { 'dashboard status 200': (r) => r.status === 200 });
  sleep(0.3);

  const extra = [
    { url: `${BASE_URL}/api/employees/stats`,                           tag: 'employees stats' },
    { url: `${BASE_URL}/api/attendance-stats`,                          tag: 'attendance stats' },
    { url: `${BASE_URL}/api/clients?page=1&per_page=10`,                tag: 'clients list' },
    { url: `${BASE_URL}/api/financial-transactions?page=1&per_page=10`, tag: 'financial-transactions list' },
  ];

  for (const ep of extra) {
    const r = http.get(ep.url, { headers, tags: { name: ep.tag } });
    check(r, { [`${ep.tag} status 200`]: (rr) => rr.status === 200 });
    sleep(0.3);
  }
}
