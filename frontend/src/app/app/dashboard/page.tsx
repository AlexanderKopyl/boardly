import { Badge } from '@/shared/ui/Badge'
import { Card } from '@/shared/ui/Card'
import { PageHeader } from '@/shared/ui/PageHeader'

type Metric = {
  label: string
  value: string
  detail: string
}

type TaskItem = {
  title: string
  project: string
  due: string
  status: 'Urgent' | 'In review' | 'Ready'
}

type ProjectItem = {
  name: string
  owner: string
  progress: string
  status: 'On track' | 'Needs attention' | 'Waiting'
}

type ActivityItem = {
  actor: string
  action: string
  time: string
}

const metrics: Metric[] = [
  { label: 'Open tasks', value: '18', detail: '5 due today' },
  { label: 'Pinned projects', value: '6', detail: '2 updated recently' },
  { label: 'Active boards', value: '4', detail: 'Across 3 teams' },
  { label: 'Recent activity', value: '27', detail: 'Last 24 hours' },
]

const openTasks: TaskItem[] = [
  {
    title: 'Finalize dashboard copy for the launch review',
    project: 'Launch Northstar',
    due: 'Today',
    status: 'Urgent',
  },
  {
    title: 'Align sprint scope with the design handoff',
    project: 'Mobile refresh',
    due: 'Tomorrow',
    status: 'In review',
  },
  {
    title: 'Validate board permissions edge cases',
    project: 'Workspace core',
    due: 'Fri',
    status: 'Ready',
  },
]

const pinnedProjects: ProjectItem[] = [
  {
    name: 'Launch Northstar',
    owner: 'Product',
    progress: '12/16 issues complete',
    status: 'On track',
  },
  {
    name: 'Mobile refresh',
    owner: 'Design',
    progress: '8/14 issues complete',
    status: 'Needs attention',
  },
  {
    name: 'Workspace core',
    owner: 'Platform',
    progress: '4/6 issues complete',
    status: 'Waiting',
  },
]

const recentActivity: ActivityItem[] = [
  {
    actor: 'Mila',
    action: 'moved "Board permissions" to In review',
    time: '12m ago',
  },
  {
    actor: 'Alex',
    action: 'commented on the launch checklist',
    time: '34m ago',
  },
  {
    actor: 'Boardly',
    action: 'synced 4 project cards to the dashboard',
    time: '1h ago',
  },
]

function getInitials(name: string): string {
  const parts = name.trim().split(/\s+/)

  if (parts.length === 0) {
    return 'B'
  }

  if (parts.length === 1) {
    return parts[0].slice(0, 2).toUpperCase()
  }

  return `${parts[0][0]}${parts[1][0]}`.toUpperCase()
}

function getTaskBadgeVariant(status: TaskItem['status']) {
  if (status === 'Urgent') {
    return 'destructive' as const
  }

  if (status === 'In review') {
    return 'info' as const
  }

  return 'success' as const
}

function getProjectBadgeVariant(status: ProjectItem['status']) {
  if (status === 'On track') {
    return 'success' as const
  }

  if (status === 'Needs attention') {
    return 'warning' as const
  }

  return 'neutral' as const
}

export default function AppDashboardPage() {
  return (
    <div className="space-y-8">
      <div className="space-y-4">
        <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
          <span>Workspace</span>
          <span>/</span>
          <span>Dashboard</span>
        </div>
        <div className="rounded-3xl border border-border/70 bg-card/80 px-4 py-3 shadow-sm">
          <div className="flex items-center gap-3 text-sm text-muted-foreground">
            <span className="text-lg leading-none">⌕</span>
            <span className="flex-1">Search projects, tasks, people</span>
            <span className="rounded-full border border-border/70 bg-background px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-foreground">
              Ctrl K
            </span>
          </div>
        </div>
        <PageHeader
          compact
          eyebrow="Workspace overview"
          title="Dashboard"
          description="A denser, prototype-aligned home screen for project status, tasks, and recent movement."
          actions={
            <>
              <Badge variant="info">Prototype-aligned</Badge>
              <Badge variant="neutral">Updated just now</Badge>
            </>
          }
        />
      </div>

      <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Dashboard metrics">
        {metrics.map((metric) => (
          <Card key={metric.label} className="space-y-2 p-5">
            <p className="text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
              {metric.label}
            </p>
            <div className="text-3xl font-semibold tracking-tight text-foreground">
              {metric.value}
            </div>
            <p className="text-sm text-muted-foreground">{metric.detail}</p>
          </Card>
        ))}
      </section>

      <section className="grid gap-6 xl:grid-cols-[1.25fr_1fr] 2xl:grid-cols-[1.25fr_1fr_0.9fr]" aria-label="Dashboard content">
        <Card className="space-y-5 p-6 xl:col-span-1">
          <div className="flex items-start justify-between gap-4">
            <div>
              <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                Work queue
              </p>
              <h2 className="mt-1 text-xl font-semibold tracking-tight text-foreground">
                Open tasks
              </h2>
            </div>
            <Badge variant="warning">3 urgent</Badge>
          </div>
          <ul className="space-y-3">
            {openTasks.map((task) => (
              <li
                className="flex items-start justify-between gap-4 rounded-2xl border border-border/70 bg-background/60 px-4 py-3"
                key={task.title}
              >
                <div className="min-w-0">
                  <div className="text-sm font-medium leading-6 text-foreground">{task.title}</div>
                  <div className="text-xs text-muted-foreground">{task.project}</div>
                </div>
                <div className="flex shrink-0 flex-col items-end gap-2">
                  <Badge variant={getTaskBadgeVariant(task.status)}>{task.status}</Badge>
                  <span className="text-xs text-muted-foreground">{task.due}</span>
                </div>
              </li>
            ))}
          </ul>
        </Card>

        <Card className="space-y-5 p-6 xl:col-span-1">
          <div className="flex items-start justify-between gap-4">
            <div>
              <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                Pinned work
              </p>
              <h2 className="mt-1 text-xl font-semibold tracking-tight text-foreground">
                Pinned projects
              </h2>
            </div>
            <Badge variant="neutral">6 total</Badge>
          </div>
          <ul className="space-y-3">
            {pinnedProjects.map((project) => (
              <li
                className="flex items-start justify-between gap-4 rounded-2xl border border-border/70 bg-background/60 px-4 py-3"
                key={project.name}
              >
                <div className="min-w-0">
                  <div className="text-sm font-medium leading-6 text-foreground">{project.name}</div>
                  <div className="text-xs text-muted-foreground">{project.owner}</div>
                </div>
                <div className="flex shrink-0 flex-col items-end gap-2">
                  <Badge variant={getProjectBadgeVariant(project.status)}>{project.status}</Badge>
                  <span className="text-xs text-muted-foreground">{project.progress}</span>
                </div>
              </li>
            ))}
          </ul>
        </Card>

        <Card className="space-y-5 p-6 xl:col-span-2 2xl:col-span-1">
          <div className="flex items-start justify-between gap-4">
            <div>
              <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                Team pulse
              </p>
              <h2 className="mt-1 text-xl font-semibold tracking-tight text-foreground">
                Recent activity
              </h2>
            </div>
            <Badge variant="info">Live feed stub</Badge>
          </div>
          <ul className="space-y-4">
            {recentActivity.map((item) => (
              <li className="flex items-start gap-3" key={`${item.actor}-${item.time}`}>
                <div className="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-accent text-sm font-semibold text-accent-foreground">
                  {getInitials(item.actor)}
                </div>
                <div className="min-w-0">
                  <div className="text-sm leading-6 text-foreground">
                    <strong>{item.actor}</strong> {item.action}
                  </div>
                  <div className="text-xs text-muted-foreground">{item.time}</div>
                </div>
              </li>
            ))}
          </ul>
        </Card>
      </section>
    </div>
  )
}
