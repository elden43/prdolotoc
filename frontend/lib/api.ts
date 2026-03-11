/**
 * API client for prdolotoc backend.
 *
 * Base URL resolution:
 *  - Docker/nginx: NEXT_PUBLIC_API_BASE_URL is unset → relative paths like /api/...
 *    are routed by nginx to the Symfony backend.
 *  - Local no-Docker: set NEXT_PUBLIC_API_BASE_URL=http://localhost:8000 (or wherever
 *    the Symfony backend is listening).
 */

const API_BASE = (process.env.NEXT_PUBLIC_API_BASE_URL ?? "").replace(/\/$/, "");

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

export type VisualMode = "classic" | "slow" | "chaotic";

export interface SpinConfig {
  id: string;
  slug: string;
  name: string;
  options: string[];
  removeAfterPick: boolean;
  visualMode: VisualMode;
  createdAt: string; // ISO 8601 datetime string
}

export interface ApiErrorResponse {
  error: "validation_failed" | "not_found" | "server_error";
  message: string;
  details?: Record<string, string[]>;
}

export interface CreateSpinConfigPayload {
  name: string;
  options: string[];
  removeAfterPick?: boolean;
  visualMode?: VisualMode;
}

// ---------------------------------------------------------------------------
// Error class
// ---------------------------------------------------------------------------

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly body: ApiErrorResponse,
  ) {
    super(body.message);
    this.name = "ApiError";
  }
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const url = `${API_BASE}${path}`;
  const response = await fetch(url, {
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    ...init,
  });

  if (!response.ok) {
    let body: ApiErrorResponse;
    try {
      body = (await response.json()) as ApiErrorResponse;
    } catch {
      body = { error: "server_error", message: response.statusText };
    }
    throw new ApiError(response.status, body);
  }

  return response.json() as Promise<T>;
}

// ---------------------------------------------------------------------------
// API functions
// ---------------------------------------------------------------------------

/**
 * POST /api/spin-configs
 * Creates a new SpinConfig and returns the persisted entity (201).
 */
export async function createSpinConfig(
  payload: CreateSpinConfigPayload,
): Promise<SpinConfig> {
  return request<SpinConfig>("/api/spin-configs", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

/**
 * GET /api/spin-configs/{slug}
 * Fetches an existing SpinConfig by slug (200) or throws ApiError for 404/5xx.
 */
export async function getSpinConfig(slug: string): Promise<SpinConfig> {
  return request<SpinConfig>(`/api/spin-configs/${encodeURIComponent(slug)}`);
}
