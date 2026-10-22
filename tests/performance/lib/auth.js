import http from 'k6/http';
import { fail } from 'k6';

/**
 * Autenticar contra la API y devolver el token Bearer.
 * Usa K6_USER y K6_PASS del entorno.
 *
 * @param {string} baseUrl  URL base del backend, ej. "http://127.0.0.1:8001"
 * @returns {string}        Token Bearer listo para usar en cabeceras
 */
export function login(baseUrl) {
  const payload = JSON.stringify({
    email: __ENV.K6_USER,
    password: __ENV.K6_PASS,
  });

  const res = http.post(`${baseUrl}/api/auth/login`, payload, {
    headers: { 'Content-Type': 'application/json' },
  });

  if (res.status !== 200) {
    fail(`Login fallido: status ${res.status} — ${res.body}`);
  }

  const body = res.json();
  const token = body?.token ?? body?.data?.token ?? body?.access_token;

  if (!token) {
    fail(`Login OK pero no se encontró token en la respuesta: ${res.body}`);
  }

  return token;
}

/**
 * Cabeceras autorizadas listas para pasar a cualquier request k6.
 *
 * @param {string} token  Token obtenido de login()
 * @returns {object}
 */
export function authHeaders(token) {
  return {
    'Content-Type': 'application/json',
    Authorization: `Bearer ${token}`,
  };
}
