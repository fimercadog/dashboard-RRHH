// ⚠️ NO ejecutar contra producción sin autorización expresa
import http from 'k6/http';
import { check, sleep, fail } from 'k6';
import { login, authHeaders } from '../lib/auth.js';

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8001';

export const options = {
  stages: [
    { duration: '1m', target: 50 },  // rampa 0 → 50 VUs
    { duration: '3m', target: 50 },  // carga sostenida
    { duration: '1m', target: 0  },  // rampa 50 → 0 VUs
  ],
  thresholds: {
    http_req_failed:   ['rate<0.02'],
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

  const endpoints = [
    { url: `${BASE_URL}/api/employees?page=1&per_page=10`,             tag: 'employees list' },
    { url: `${BASE_URL}/api/employees/stats`,                           tag: 'employees stats' },
    { url: `${BASE_URL}/api/attendances?page=1&per_page=10`,            tag: 'attendances list' },
    { url: `${BASE_URL}/api/attendance-stats`,                          tag: 'attendance stats' },
    { url: `${BASE_URL}/api/dashboard`,                                 tag: 'dashboard' },
    { url: `${BASE_URL}/api/clients?page=1&per_page=10`,                tag: 'clients list' },
    { url: `${BASE_URL}/api/clients/stats`,                             tag: 'clients stats' },
    { url: `${BASE_URL}/api/products?page=1&per_page=10`,               tag: 'products list' },
    { url: `${BASE_URL}/api/products/stats`,                            tag: 'products stats' },
    { url: `${BASE_URL}/api/financial-transactions?page=1&per_page=10`, tag: 'financial-transactions list' },
  ];

  for (const ep of endpoints) {
    const res = http.get(ep.url, { headers, tags: { name: ep.tag } });
    check(res, { [`${ep.tag} GET 200`]: (r) => r.status === 200 });
    sleep(0.3);
  }
}
