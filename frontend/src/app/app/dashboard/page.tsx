import { ProtectedRoute } from '@/contexts/identity-access/presentation/guards/ProtectedRoute'
import { SidebarAccountCard } from '@/contexts/identity-access/presentation/ui/SidebarAccountCard'
import { LogoutButton } from '@/contexts/identity-access/presentation/ui/LogoutButton'
import { AppShell } from '@/shared/ui/AppShell'
import { Badge } from '@/shared/ui/Badge'
import { Card } from '@/shared/ui/Card'
import { PageHeader } from '@/shared/ui/PageHeader'
import { SidebarNav, type SidebarNavSection } from '@/shared/ui/SidebarNav'

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

const navigationSections: SidebarNavSection[] = [
  {
    label: 'Main',
    items: [
      {
        label: 'Dashboard',
        href: '/app/dashboard',
        current: true,
      },
      {
        label: 'Projects',
        href: '/app/dashboard',
        disabled: true,
      },
      {
        label: 'My tasks',
        href: '/app/dashboard',
        disabled: true,
      },
    ],
  },
  {
    label: 'Work',
    items: [
      {
        label: 'Boards',
        href: '/app/dashboard',
        disabled: true,
      },
      {
        label: 'Calendar',
        href: '/app/dashboard',
        disabled: true,
      },
      {
        label: 'Settings',
        href: '/app/dashboard',
        disabled: true,
      },
    ],
  },
]

export default function AppDashboardPage() {
  return (
    <ProtectedRoute>
      <AppShell
      sidebar={
        <div className="ui-app-shell__sidebar-content">
          <div className="ui-app-shell__sidebar-primary">
            <div className="ui-app-shell__sidebar-brand">
              <div className="ui-app-shell__sidebar-brand-mark">B</div>
              <div className="ui-app-shell__sidebar-brand-copy">
                <div className="ui-app-shell__sidebar-brand-label">Boardly</div>
                <div className="ui-app-shell__sidebar-brand-subtitle">
                  Project delivery workspace
                </div>
              </div>
            </div>

            <section className="ui-app-shell__sidebar-group">
              <div className="ui-app-shell__workspace-card">
                <div className="ui-app-shell__workspace-card-copy">
                  <div className="ui-app-shell__workspace-name">Northstar Studio</div>
                  <div className="ui-app-shell__workspace-meta">12 active projects</div>
                </div>
                <Badge className="ui-app-shell__workspace-badge" variant="neutral">
                  Main
                </Badge>
              </div>
            </section>

            <SidebarNav label="Main navigation" sections={navigationSections} />
          </div>

          <div className="ui-app-shell__sidebar-footer">
            <SidebarAccountCard />
              <LogoutButton />
            </div>
          </div>
        }
        header={
          <div className="ui-dashboard-header">
            <div className="ui-dashboard-header__top">
              <div className="ui-dashboard-breadcrumbs">
                <span>Workspace</span>
                <span>/</span>
                <span>Dashboard</span>
              </div>
              <div className="ui-dashboard-search" aria-hidden="true">
                <span className="ui-dashboard-search__icon">⌕</span>
                <span className="ui-dashboard-search__placeholder">
                  Search projects, tasks, people
                </span>
                <span className="ui-dashboard-search__shortcut">Ctrl K</span>
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
        }
      >
        <section className="ui-dashboard-metrics" aria-label="Dashboard metrics">
          {metrics.map((metric) => (
            <Card key={metric.label} className="ui-dashboard-metric-card">
              <p className="ui-dashboard-metric-card__label">{metric.label}</p>
              <div className="ui-dashboard-metric-card__value">{metric.value}</div>
              <p className="ui-dashboard-metric-card__detail">{metric.detail}</p>
            </Card>
          ))}
        </section>

        <section className="ui-dashboard-grid" aria-label="Dashboard content">
          <Card className="ui-dashboard-panel ui-dashboard-panel--tasks">
            <div className="ui-dashboard-panel__header">
              <div>
                <p className="ui-dashboard-panel__eyebrow">Work queue</p>
                <h2 className="ui-dashboard-panel__title">Open tasks</h2>
              </div>
              <Badge variant="warning">3 urgent</Badge>
            </div>
            <ul className="ui-dashboard-list">
              {openTasks.map((task) => (
                <li className="ui-dashboard-list__item" key={task.title}>
                  <div className="ui-dashboard-list__item-copy">
                    <div className="ui-dashboard-list__item-title">{task.title}</div>
                    <div className="ui-dashboard-list__item-meta">{task.project}</div>
                  </div>
                  <div className="ui-dashboard-list__item-side">
                    <Badge variant={getTaskBadgeVariant(task.status)}>{task.status}</Badge>
                    <span className="ui-dashboard-list__item-due">{task.due}</span>
                  </div>
                </li>
              ))}
            </ul>
          </Card>

          <Card className="ui-dashboard-panel ui-dashboard-panel--projects">
            <div className="ui-dashboard-panel__header">
              <div>
                <p className="ui-dashboard-panel__eyebrow">Pinned work</p>
                <h2 className="ui-dashboard-panel__title">Pinned projects</h2>
              </div>
              <Badge variant="neutral">6 total</Badge>
            </div>
            <ul className="ui-dashboard-projects">
              {pinnedProjects.map((project) => (
                <li className="ui-dashboard-projects__item" key={project.name}>
                  <div className="ui-dashboard-projects__item-copy">
                    <div className="ui-dashboard-projects__item-title">{project.name}</div>
                    <div className="ui-dashboard-projects__item-meta">{project.owner}</div>
                  </div>
                  <div className="ui-dashboard-projects__item-side">
                    <Badge variant={getProjectBadgeVariant(project.status)}>{project.status}</Badge>
                    <span className="ui-dashboard-projects__item-progress">{project.progress}</span>
                  </div>
                </li>
              ))}
            </ul>
          </Card>

          <Card className="ui-dashboard-panel ui-dashboard-panel--activity">
            <div className="ui-dashboard-panel__header">
              <div>
                <p className="ui-dashboard-panel__eyebrow">Team pulse</p>
                <h2 className="ui-dashboard-panel__title">Recent activity</h2>
              </div>
              <Badge variant="info">Live feed stub</Badge>
            </div>
            <ul className="ui-dashboard-activity">
              {recentActivity.map((item) => (
                <li className="ui-dashboard-activity__item" key={`${item.actor}-${item.time}`}>
                  <div className="ui-dashboard-activity__avatar">{getInitials(item.actor)}</div>
                  <div className="ui-dashboard-activity__copy">
                    <div className="ui-dashboard-activity__message">
                      <strong>{item.actor}</strong> {item.action}
                    </div>
                    <div className="ui-dashboard-activity__time">{item.time}</div>
                  </div>
                </li>
              ))}
            </ul>
          </Card>
        </section>
      </AppShell>
    </ProtectedRoute>
  )
}
