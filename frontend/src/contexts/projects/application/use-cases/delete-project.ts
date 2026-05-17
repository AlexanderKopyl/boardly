import type { ProjectGateway } from '../ports/project-gateway'

export interface DeleteProjectDependencies {
  gateway: ProjectGateway
  projectId: string
}

export async function deleteProjectUseCase(deps: DeleteProjectDependencies): Promise<void> {
  await deps.gateway.deleteProject(deps.projectId)
}
