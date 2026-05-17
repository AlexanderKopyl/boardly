import { ApiError } from '@/shared/api/api-error'
import { httpRequest, type HttpMethod } from '@/shared/api/http-client'

import type {
  CreateProjectResult,
  GetProjectResult,
  ListProjectsResult,
  ProjectGateway,
} from '../../application/ports/project-gateway'
import type { ProjectForm } from '../../domain/project-form'
import type { Project } from '../../domain/project'
import type { ProjectSummary } from '../../domain/project-summary'
import { ProjectsError } from '../../domain/project-errors'
import type {
  CreateProjectRequest,
  ListProjectsResponse,
  ProjectResponse,
  ProjectSummaryResponse,
} from './projects-api-contracts'

export interface ProjectsHttpGatewayDependencies {
  getAccessToken: () => string | null
  refreshAccessToken: () => Promise<string | null>
}

function mapSummary(response: ProjectSummaryResponse): ProjectSummary {
  return {
    id: response.id,
    name: response.name,
    iconKey: response.iconKey,
    status: response.status,
    createdAt: response.createdAt,
  }
}

function mapProject(response: ProjectResponse): Project {
  return {
    id: response.id,
    name: response.name,
    iconKey: response.iconKey,
    status: response.status,
    createdAt: response.createdAt,
    updatedAt: response.updatedAt,
    archivedAt: response.archivedAt,
  }
}

function mapApiError(error: unknown): Error {
  if (error instanceof ProjectsError) {
    return error
  }

  if (error instanceof ApiError) {
    switch (error.statusCode) {
      case 401:
        return new ProjectsError('unauthorized', error.message)
      case 403:
        return new ProjectsError('forbidden', error.message)
      case 404:
        return new ProjectsError('project_not_found', error.message)
      case 422:
        return new ProjectsError('validation_failed', error.message, error.details)
      default:
        return new ProjectsError('unexpected', error.message)
    }
  }

  return error instanceof Error
    ? error
    : new ProjectsError('unexpected', 'Unexpected projects error')
}

export class ProjectsHttpGateway implements ProjectGateway {
  constructor(private readonly deps: ProjectsHttpGatewayDependencies) {}

  async listProjects(): Promise<ListProjectsResult> {
    const response = await this.request<ListProjectsResponse>('/api/projects')

    return {
      projects: response.projects.map(mapSummary),
    }
  }

  async getProject(projectId: string): Promise<GetProjectResult> {
    const response = await this.request<ProjectResponse>(`/api/projects/${projectId}`)

    return {
      project: mapProject(response),
    }
  }

  async createProject(input: ProjectForm): Promise<CreateProjectResult> {
    const response = await this.request<ProjectResponse>('/api/projects', {
      method: 'POST',
      body: {
        name: input.name,
        iconKey: input.iconKey,
      } satisfies CreateProjectRequest,
    })

    return {
      project: mapProject(response),
    }
  }

  async archiveProject(projectId: string): Promise<void> {
    await this.request<void>(`/api/projects/${projectId}/archive`, {
      method: 'POST',
    })
  }

  async deleteProject(projectId: string): Promise<void> {
    await this.request<void>(`/api/projects/${projectId}`, {
      method: 'DELETE',
    })
  }

  private async request<TResponse>(
    path: string,
    options?: {
      method?: HttpMethod
      body?: unknown
    },
  ): Promise<TResponse> {
    const accessToken = this.deps.getAccessToken()

    if (accessToken === null) {
      throw new ProjectsError('unauthorized', 'No active authenticated session was found.')
    }

    try {
      return await httpRequest<TResponse>(path, {
        method: options?.method,
        body: options?.body,
        accessToken,
        onUnauthorized: this.deps.refreshAccessToken,
      })
    } catch (error) {
      throw mapApiError(error)
    }
  }
}
