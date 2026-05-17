import type { ProjectStatus } from '../../domain/project-status'

export interface ProjectSummaryResponse {
  readonly id: string
  readonly name: string
  readonly iconKey: string
  readonly status: ProjectStatus
  readonly createdAt: string
}

export interface ProjectResponse {
  readonly id: string
  readonly name: string
  readonly iconKey: string
  readonly status: ProjectStatus
  readonly createdAt: string
  readonly updatedAt: string
  readonly archivedAt: string | null
}

export interface ListProjectsResponse {
  readonly projects: readonly ProjectSummaryResponse[]
}

export interface CreateProjectRequest {
  readonly name: string
  readonly iconKey?: string
}
