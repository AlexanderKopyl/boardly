'use client'

import { useRouter } from 'next/navigation'

import { Button } from '@/shared/ui/Button'
import { Card } from '@/shared/ui/Card'
import { PageHeader } from '@/shared/ui/PageHeader'

import { ProjectCreateForm } from './ProjectCreateForm'

export function ProjectCreatePage() {
  const router = useRouter()

  return (
    <div className="space-y-8">
      <PageHeader
        eyebrow="Workspace"
        title="Create project"
        description="Create a new project for the authenticated workspace."
        actions={
          <Button variant="secondary" onClick={() => router.push('/app/projects')}>
            Back to projects
          </Button>
        }
      />

      <Card className="space-y-6 p-6">
        <div className="space-y-2">
          <p className="text-sm font-medium uppercase tracking-[0.2em] text-muted-foreground">
            New project
          </p>
          <p className="text-sm text-muted-foreground">
            The backend will default the icon key to <span className="font-medium">folder</span>
            if you leave it empty.
          </p>
        </div>

        <ProjectCreateForm />
      </Card>
    </div>
  )
}
