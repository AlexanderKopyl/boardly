import { env } from '@/shared/config/env';

import { ApiError } from './api-error';

export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

export interface HttpRequestOptions {
  method?: HttpMethod;
  body?: unknown;
  accessToken?: string;
  headers?: Record<string, string>;
  onUnauthorized?: () => Promise<string | null>;
}

async function parseJsonSafely(response: Response): Promise<unknown> {
  const contentType = response.headers.get('content-type') ?? '';
  if (!contentType.includes('application/json')) {
    return null;
  }
  try {
    return await response.json();
  } catch {
    return null;
  }
}

function toApiError(status: number, payload: unknown): ApiError {
  if (
    payload !== null &&
    typeof payload === 'object' &&
    'error' in payload &&
    payload.error !== null &&
    typeof payload.error === 'object'
  ) {
    const err = payload.error as Record<string, unknown>;
    const code = typeof err.code === 'string' ? err.code : 'unknown_error';
    const message =
      typeof err.message === 'string' ? err.message : 'An unexpected error occurred.';
    const details = 'violations' in err ? err.violations : undefined;
    return new ApiError(status, code, message, details);
  }
  return new ApiError(status, 'unknown_error', 'An unexpected error occurred.');
}

async function requestOnce<TResponse>(
  url: string,
  method: HttpMethod,
  body: unknown,
  accessToken: string | undefined,
  extraHeaders: Record<string, string> | undefined,
): Promise<TResponse> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
  };

  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }

  if (accessToken !== undefined) {
    headers['Authorization'] = `Bearer ${accessToken}`;
  }

  if (extraHeaders !== undefined) {
    Object.assign(headers, extraHeaders);
  }

  const response = await fetch(url, {
    method,
    credentials: 'include',
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  if (response.ok) {
    if (response.status === 204) {
      return undefined as TResponse;
    }
    const payload = await parseJsonSafely(response);
    if (payload === null) {
      throw new ApiError(
        response.status,
        'invalid_response',
        'The server returned an unexpected response.',
      );
    }
    return payload as TResponse;
  }

  const payload = await parseJsonSafely(response);
  throw toApiError(response.status, payload);
}

export async function httpRequest<TResponse>(
  path: string,
  options?: HttpRequestOptions,
): Promise<TResponse> {
  const { method = 'GET', body, accessToken, headers, onUnauthorized } = options ?? {};
  const url = `${env.apiUrl}${path}`;

  try {
    return await requestOnce<TResponse>(url, method, body, accessToken, headers);
  } catch (error) {
    if (error instanceof ApiError && error.statusCode === 401 && onUnauthorized !== undefined) {
      const newToken = await onUnauthorized();
      if (newToken !== null) {
        return requestOnce<TResponse>(url, method, body, newToken, headers);
      }
    }
    throw error;
  }
}
