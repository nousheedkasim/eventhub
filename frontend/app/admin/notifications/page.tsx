'use client'

import { useEffect, useState } from 'react'
import { Bell, RefreshCw } from "lucide-react"
import { Button } from "@/components/ui/button"
import { queueAPI } from "@/lib/api"

interface QueueStatus {
  waiting: number
  active: number
  completed: number
  failed: number
}

interface QueueData {
  email: QueueStatus
  webhook: QueueStatus
}

interface WaitingJob {
  id: number
  name: string | null
  data: any
  timestamp: number
}

interface CompletedJob extends WaitingJob {
  returnvalue: any
  finishedOn: number
}

interface FailedJob extends WaitingJob {
  failedReason: string
  attemptsMade: number
  finishedOn: number
}

interface DeadLetterJob {
  id: number
  queue: string
  type: string
  payload: any
  error: string
  attemptsMade: number
  failedAt: string
}

export default function AdminNotificationsPage() {
  const [queues, setQueues] = useState<QueueData | null>(null)
  const [emailWaiting, setEmailWaiting] = useState<WaitingJob[]>([])
  const [webhookWaiting, setWebhookWaiting] = useState<WaitingJob[]>([])
  const [emailCompleted, setEmailCompleted] = useState<CompletedJob[]>([])
  const [webhookCompleted, setWebhookCompleted] = useState<CompletedJob[]>([])
  const [emailFailed, setEmailFailed] = useState<FailedJob[]>([])
  const [webhookFailed, setWebhookFailed] = useState<FailedJob[]>([])
  const [deadLetters, setDeadLetters] = useState<DeadLetterJob[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const loadAll = async () => {
    setLoading(true)
    setError(null)
    try {
      const [qRes, eW, wW, eC, wC, eF, wF, dRes] = await Promise.allSettled([
        queueAPI.getStatus(),
        queueAPI.getEmailWaiting(),
        queueAPI.getWebhookWaiting(),
        queueAPI.getEmailCompleted(),
        queueAPI.getWebhookCompleted(),
        queueAPI.getEmailFailed(),
        queueAPI.getWebhookFailed(),
        queueAPI.getDeadLetters(),
      ])
      if (qRes.status === 'fulfilled') setQueues(qRes.value.data)
      if (eW.status === 'fulfilled') setEmailWaiting(eW.value.data.jobs || [])
      if (wW.status === 'fulfilled') setWebhookWaiting(wW.value.data.jobs || [])
      if (eC.status === 'fulfilled') setEmailCompleted(eC.value.data.jobs || [])
      if (wC.status === 'fulfilled') setWebhookCompleted(wC.value.data.jobs || [])
      if (eF.status === 'fulfilled') setEmailFailed(eF.value.data.jobs || [])
      if (wF.status === 'fulfilled') setWebhookFailed(wF.value.data.jobs || [])
      if (dRes.status === 'fulfilled') setDeadLetters(dRes.value.data.jobs || [])
    } catch {
      setError('Failed to connect to notification service')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { loadAll() }, [])

  if (loading) return <div className="text-center py-12">Loading queue status...</div>

  if (error) {
    return (
      <div className="space-y-8">
        <div className="flex items-center gap-2">
          <Bell className="w-6 h-6 text-purple-600" />
          <h1 className="text-2xl font-bold">Notification Queues</h1>
        </div>
        <div className="bg-white p-6 rounded-lg shadow text-center">
          <p className="text-red-500 mb-4">{error}</p>
          <p className="text-sm text-gray-400 mb-4">Make sure the notification service is running on port 3002</p>
          <Button onClick={loadAll} variant="outline" size="sm">
            <RefreshCw className="w-4 h-4 mr-1" /> Retry
          </Button>
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-8">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <Bell className="w-6 h-6 text-purple-600" />
          <h1 className="text-2xl font-bold">Notification Queues</h1>
        </div>
        <Button onClick={loadAll} variant="outline" size="sm">
          <RefreshCw className="w-4 h-4 mr-1" /> Refresh
        </Button>
      </div>

      {/* Queue Status Cards */}
      {queues && (
        <div className="grid md:grid-cols-2 gap-6">
          {/* Email Queue */}
          <div className="bg-white p-6 rounded-lg shadow">
            <h2 className="text-lg font-semibold mb-4 text-blue-700">Email Queue</h2>
            <div className="grid grid-cols-2 gap-4">
              <div className="text-center p-3 bg-yellow-50 rounded-lg">
                <p className="text-2xl font-bold text-yellow-600">{queues.email.waiting}</p>
                <p className="text-xs text-gray-500">Waiting</p>
              </div>
              <div className="text-center p-3 bg-blue-50 rounded-lg">
                <p className="text-2xl font-bold text-blue-600">{queues.email.active}</p>
                <p className="text-xs text-gray-500">Active</p>
              </div>
              <div className="text-center p-3 bg-green-50 rounded-lg">
                <p className="text-2xl font-bold text-green-600">{queues.email.completed}</p>
                <p className="text-xs text-gray-500">Completed</p>
              </div>
              <div className="text-center p-3 bg-red-50 rounded-lg">
                <p className="text-2xl font-bold text-red-600">{queues.email.failed}</p>
                <p className="text-xs text-gray-500">Failed</p>
              </div>
            </div>
          </div>

          {/* Webhook Queue */}
          <div className="bg-white p-6 rounded-lg shadow">
            <h2 className="text-lg font-semibold mb-4 text-orange-700">Webhook Queue</h2>
            <div className="grid grid-cols-2 gap-4">
              <div className="text-center p-3 bg-yellow-50 rounded-lg">
                <p className="text-2xl font-bold text-yellow-600">{queues.webhook.waiting}</p>
                <p className="text-xs text-gray-500">Waiting</p>
              </div>
              <div className="text-center p-3 bg-blue-50 rounded-lg">
                <p className="text-2xl font-bold text-blue-600">{queues.webhook.active}</p>
                <p className="text-xs text-gray-500">Active</p>
              </div>
              <div className="text-center p-3 bg-green-50 rounded-lg">
                <p className="text-2xl font-bold text-green-600">{queues.webhook.completed}</p>
                <p className="text-xs text-gray-500">Completed</p>
              </div>
              <div className="text-center p-3 bg-red-50 rounded-lg">
                <p className="text-2xl font-bold text-red-600">{queues.webhook.failed}</p>
                <p className="text-xs text-gray-500">Failed</p>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Waiting Email Jobs */}
      <div className="bg-white p-6 rounded-lg shadow">
        <h2 className="text-lg font-semibold mb-4">
          Waiting Email Jobs
          {emailWaiting.length > 0 && (
            <span className="ml-2 bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded-full">{emailWaiting.length}</span>
          )}
        </h2>
        {emailWaiting.length === 0 ? (
          <p className="text-gray-500 text-center py-4">No waiting email jobs</p>
        ) : (
          <div className="space-y-2">
            {emailWaiting.map(job => (
              <div key={job.id} className="p-3 border rounded-lg text-sm">
                <div className="flex items-center justify-between">
                  <div>
                    <span className="font-mono text-gray-500">Job #{job.id}</span>
                    {job.data?.type && <span className="ml-2 text-blue-600">{job.data.type}</span>}
                  </div>
                  <span className="text-xs text-gray-400">{new Date(job.timestamp).toLocaleString()}</span>
                </div>
                {job.data?.data && (
                  <pre className="mt-2 text-xs text-gray-400 overflow-x-auto">{JSON.stringify(job.data.data, null, 2)}</pre>
                )}
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Waiting Webhook Jobs */}
      <div className="bg-white p-6 rounded-lg shadow">
        <h2 className="text-lg font-semibold mb-4">
          Waiting Webhook Jobs
          {webhookWaiting.length > 0 && (
            <span className="ml-2 bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded-full">{webhookWaiting.length}</span>
          )}
        </h2>
        {webhookWaiting.length === 0 ? (
          <p className="text-gray-500 text-center py-4">No waiting webhook jobs</p>
        ) : (
          <div className="space-y-2">
            {webhookWaiting.map(job => (
              <div key={job.id} className="p-3 border rounded-lg text-sm">
                <div className="flex items-center justify-between">
                  <div>
                    <span className="font-mono text-gray-500">Job #{job.id}</span>
                    {job.data?.type && <span className="ml-2 text-orange-600">{job.data.type}</span>}
                  </div>
                  <span className="text-xs text-gray-400">{new Date(job.timestamp).toLocaleString()}</span>
                </div>
                {job.data?.data && (
                  <pre className="mt-2 text-xs text-gray-400 overflow-x-auto">{JSON.stringify(job.data.data, null, 2)}</pre>
                )}
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Completed Email Jobs */}
      <div className="bg-white p-6 rounded-lg shadow">
        <h2 className="text-lg font-semibold mb-4">
          Completed Email Jobs
          {emailCompleted.length > 0 && (
            <span className="ml-2 bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded-full">{emailCompleted.length}</span>
          )}
        </h2>
        {emailCompleted.length === 0 ? (
          <p className="text-gray-500 text-center py-4">No completed email jobs</p>
        ) : (
          <div className="space-y-2">
            {emailCompleted.map(job => (
              <div key={job.id} className="p-3 border border-green-200 bg-green-50 rounded-lg text-sm">
                <div className="flex items-center justify-between">
                  <div>
                    <span className="font-mono text-gray-500">Job #{job.id}</span>
                    {job.data?.type && <span className="ml-2 text-green-700">{job.data.type}</span>}
                  </div>
                  <span className="text-xs text-gray-400">{new Date(job.finishedOn).toLocaleString()}</span>
                </div>
                {job.returnvalue && (
                  <pre className="mt-2 text-xs text-green-700 overflow-x-auto">{JSON.stringify(job.returnvalue, null, 2)}</pre>
                )}
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Completed Webhook Jobs */}
      <div className="bg-white p-6 rounded-lg shadow">
        <h2 className="text-lg font-semibold mb-4">
          Completed Webhook Jobs
          {webhookCompleted.length > 0 && (
            <span className="ml-2 bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded-full">{webhookCompleted.length}</span>
          )}
        </h2>
        {webhookCompleted.length === 0 ? (
          <p className="text-gray-500 text-center py-4">No completed webhook jobs</p>
        ) : (
          <div className="space-y-2">
            {webhookCompleted.map(job => (
              <div key={job.id} className="p-3 border border-green-200 bg-green-50 rounded-lg text-sm">
                <div className="flex items-center justify-between">
                  <div>
                    <span className="font-mono text-gray-500">Job #{job.id}</span>
                    {job.data?.type && <span className="ml-2 text-green-700">{job.data.type}</span>}
                  </div>
                  <span className="text-xs text-gray-400">{new Date(job.finishedOn).toLocaleString()}</span>
                </div>
                {job.returnvalue && (
                  <pre className="mt-2 text-xs text-green-700 overflow-x-auto">{JSON.stringify(job.returnvalue, null, 2)}</pre>
                )}
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Failed Email Jobs */}
      <div className="bg-white p-6 rounded-lg shadow">
        <h2 className="text-lg font-semibold mb-4">
          Failed Email Jobs
          {emailFailed.length > 0 && (
            <span className="ml-2 bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded-full">{emailFailed.length}</span>
          )}
        </h2>
        {emailFailed.length === 0 ? (
          <p className="text-gray-500 text-center py-4">No failed email jobs</p>
        ) : (
          <div className="space-y-2">
            {emailFailed.map(job => (
              <div key={job.id} className="p-3 border border-red-200 bg-red-50 rounded-lg text-sm">
                <div className="flex items-center justify-between mb-1">
                  <div>
                    <span className="font-mono text-gray-500">Job #{job.id}</span>
                    {job.data?.type && <span className="ml-2 text-red-700">{job.data.type}</span>}
                  </div>
                  <span className="text-xs text-gray-400">{new Date(job.finishedOn).toLocaleString()}</span>
                </div>
                <p className="text-xs text-red-600">{job.failedReason}</p>
                <p className="text-xs text-gray-400 mt-1">Attempts: {job.attemptsMade}</p>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Failed Webhook Jobs */}
      <div className="bg-white p-6 rounded-lg shadow">
        <h2 className="text-lg font-semibold mb-4">
          Failed Webhook Jobs
          {webhookFailed.length > 0 && (
            <span className="ml-2 bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded-full">{webhookFailed.length}</span>
          )}
        </h2>
        {webhookFailed.length === 0 ? (
          <p className="text-gray-500 text-center py-4">No failed webhook jobs</p>
        ) : (
          <div className="space-y-2">
            {webhookFailed.map(job => (
              <div key={job.id} className="p-3 border border-red-200 bg-red-50 rounded-lg text-sm">
                <div className="flex items-center justify-between mb-1">
                  <div>
                    <span className="font-mono text-gray-500">Job #{job.id}</span>
                    {job.data?.type && <span className="ml-2 text-red-700">{job.data.type}</span>}
                  </div>
                  <span className="text-xs text-gray-400">{new Date(job.finishedOn).toLocaleString()}</span>
                </div>
                <p className="text-xs text-red-600">{job.failedReason}</p>
                <p className="text-xs text-gray-400 mt-1">Attempts: {job.attemptsMade}</p>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Dead Letter Queue */}
      <div className="bg-white p-6 rounded-lg shadow">
        <h2 className="text-lg font-semibold mb-4">
          Dead Letter Queue
          {deadLetters.length > 0 && (
            <span className="ml-2 bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded-full">{deadLetters.length}</span>
          )}
        </h2>
        {deadLetters.length === 0 ? (
          <p className="text-gray-500 text-center py-4">No dead letter jobs</p>
        ) : (
          <div className="space-y-2">
            {deadLetters.map((job, i) => (
              <div key={i} className="p-3 border border-red-200 bg-red-50 rounded-lg text-sm">
                <div className="flex items-center justify-between mb-1">
                  <div className="flex items-center gap-2">
                    <span className="font-mono text-gray-500">Job #{job.id}</span>
                    <span className="text-xs px-2 py-0.5 rounded bg-red-100 text-red-800">{job.queue}</span>
                    <span className="text-red-600">{job.type}</span>
                  </div>
                  <span className="text-xs text-gray-400">{new Date(job.failedAt).toLocaleString()}</span>
                </div>
                <p className="text-xs text-red-600">{job.error}</p>
                <p className="text-xs text-gray-400 mt-1">Attempts: {job.attemptsMade}</p>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
