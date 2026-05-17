import type { ProjectGateway, ListProjectsResult } from '../ports/project-gateway'

export interface ListProjectsDependencies {
  gateway: ProjectGateway
}

export async function listProjectsUseCase(
  deps: ListProjectsDependencies,
): Promise<ListProjectsResult> {
  return deps.gateway.listProjects()
}
