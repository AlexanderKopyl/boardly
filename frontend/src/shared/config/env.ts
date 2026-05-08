const apiUrl = process.env.NEXT_PUBLIC_API_URL;

if (!apiUrl || apiUrl.trim() === '') {
  throw new Error('NEXT_PUBLIC_API_URL is required');
}

try {
  new URL(apiUrl);
} catch {
  throw new Error('NEXT_PUBLIC_API_URL must be a valid URL');
}

export const env = {
  apiUrl,
} as const;
