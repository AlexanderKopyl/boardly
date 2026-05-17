import type { Project } from '../../domain/project'
import type { ProjectForm } from '../../domain/project-form'
import type { ProjectSummary } from '../../domain/project-summary'

export interface ListProjectsResult {
  readonly projects: readonly ProjectSummary[]
}

export interface GetProjectResult {
  readonly project: Project
}

export interface CreateProjectResult {
  readonly project: Project
}

export interface ProjectGateway {
  listProjects(): Promise<ListProjectsResult>
  getProject(projectId: string): Promise<GetProjectResult>
  createProject(input: ProjectForm): Promise<CreateProjectResult>
  archiveProject(projectId: string): Promise<void>
  deleteProject(projectId: string): Promise<void>
}
