export type ProjectsErrorCode =
  | 'project_not_found'
  | 'validation_failed'
  | 'unauthorized'
  | 'forbidden'
  | 'unexpected'

export class ProjectsError extends Error {
  readonly code: ProjectsErrorCode
  readonly details?: unknown

  constructor(code: ProjectsErrorCode, message?: string, details?: unknown) {
    super(message ?? code)
    this.name = 'ProjectsError'
    this.code = code
    this.details = details
  }
}
