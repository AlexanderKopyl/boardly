import Link from 'next/link'

export default function PendingApprovalPage() {
  return (
    <main>
      <h1>Account pending approval</h1>
      <p>Your account request has been received and is waiting for administrator approval.</p>
      <Link href="/login">Return to login</Link>
    </main>
  )
}
