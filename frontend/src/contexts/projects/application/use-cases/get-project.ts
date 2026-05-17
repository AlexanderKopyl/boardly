import type { GetProjectResult, ProjectGateway } from '../ports/project-gateway'

export interface GetProjectDependencies {
  gateway: ProjectGateway
  projectId: string
}

export async function getProjectUseCase(
  deps: GetProjectDependencies,
): Promise<GetProjectResult> {
  return deps.gateway.getProject(deps.projectId)
}
