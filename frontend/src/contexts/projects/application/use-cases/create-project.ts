import type { ProjectForm } from '../../domain/project-form'
import type { CreateProjectResult, ProjectGateway } from '../ports/project-gateway'

export interface CreateProjectDependencies {
  gateway: ProjectGateway
}

export async function createProjectUseCase(
  input: ProjectForm,
  deps: CreateProjectDependencies,
): Promise<CreateProjectResult> {
  return deps.gateway.createProject(input)
}
