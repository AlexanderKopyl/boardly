import type { ProjectStatus } from './project-status'

export interface Project {
  readonly id: string
  readonly name: string
  readonly iconKey: string
  readonly status: ProjectStatus
  readonly createdAt: string
  readonly updatedAt: string
  readonly archivedAt: string | null
}
