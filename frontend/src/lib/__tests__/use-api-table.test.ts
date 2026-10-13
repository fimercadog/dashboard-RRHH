/**
 * PENDIENTE — Jest no está configurado en este proyecto (no hay jest.config, ni
 * script "test" en package.json, ni @testing-library/react instalado).
 *
 * Para habilitar este test:
 *   npm install --save-dev jest @testing-library/react @testing-library/jest-dom jest-environment-jsdom ts-jest
 *   Agregar jest.config.ts y script "test" en package.json.
 *
 * El test está escrito para que compile y sea ejecutable una vez configurado Jest.
 */

// @ts-nocheck — deshabilitado hasta que se instale Jest y su configuración de tipos

import { renderHook, act } from "@testing-library/react";
import { useApiTable } from "../use-api-table";

// Mockear api antes de importar el hook
jest.mock("../api", () => ({
  api: {
    get: jest.fn(() => Promise.resolve({ data: { data: [], meta: {} } })),
  },
}));

import { api } from "../api";

describe("useApiTable debounce", () => {
  beforeEach(() => {
    jest.useFakeTimers();
    (api.get as jest.Mock).mockClear();
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  it("does NOT fire API call immediately when search changes", () => {
    const { result } = renderHook(() => useApiTable("employees"));

    act(() => {
      result.current.setSearch("Juan");
    });

    // El primer render dispara una llamada. Limpiar para aislar el test.
    const callsAfterMount = (api.get as jest.Mock).mock.calls.length;

    act(() => {
      result.current.setSearch("Juan G");
    });

    // No debe haber llamada adicional antes de que pasen los 300ms
    expect((api.get as jest.Mock).mock.calls.length).toBe(callsAfterMount);
  });

  it("fires API call after 300ms debounce", async () => {
    const { result } = renderHook(() => useApiTable("employees"));

    const callsAfterMount = (api.get as jest.Mock).mock.calls.length;

    act(() => {
      result.current.setSearch("Juan G");
    });

    act(() => {
      jest.advanceTimersByTime(300);
    });

    // Después de 300ms, debe haberse disparado una nueva llamada
    expect((api.get as jest.Mock).mock.calls.length).toBeGreaterThan(callsAfterMount);
  });
});
