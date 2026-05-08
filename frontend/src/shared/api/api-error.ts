export class ApiError extends Error {
  public readonly name = 'ApiError';

  public constructor(
    public readonly statusCode: number,
    public readonly errorCode: string,
    message: string,
    public readonly details?: unknown,
  ) {
    super(message);
  }
}
