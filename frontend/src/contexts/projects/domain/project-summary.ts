import type { ProjectStatus } from './project-status'

export interface ProjectSummary {
  readonly id: string
  readonly name: string
  readonly iconKey: string
  readonly status: ProjectStatus
  readonly createdAt: string
}
