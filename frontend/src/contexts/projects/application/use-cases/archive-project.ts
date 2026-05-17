import type { ProjectGateway } from '../ports/project-gateway'

export interface ArchiveProjectDependencies {
  gateway: ProjectGateway
  projectId: string
}

export async function archiveProjectUseCase(deps: ArchiveProjectDependencies): Promise<void> {
  await deps.gateway.archiveProject(deps.projectId)
}
