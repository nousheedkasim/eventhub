'use client'

import { useEffect, useState } from 'react'
import { Button } from "@/components/ui/button"
import { AlertTriangle } from "lucide-react"
import { disputesAPI } from "@/lib/api"

interface Dispute {
  id: number
  order_id: number
  status: string
  reason: string
  resolution: string | null
  created_at: string
}

export default function AdminDisputesPage() {
  const [disputes, setDisputes] = useState<Dispute[]>([])
  const [loading, setLoading] = useState(true)
  const [modal, setModal] = useState<{ id: number; action: 'resolved' | 'rejected' } | null>(null)
  const [resolutionText, setResolutionText] = useState('')

  useEffect(() => { loadDisputes() }, [])

  const loadDisputes = async () => {
    try {
      const res = await disputesAPI.getAll()
      setDisputes(res.data.data || [])
    } catch (error) {
      console.error('Failed to load disputes:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleResolve = async () => {
    if (!modal || !resolutionText.trim()) return
    try {
      await disputesAPI.resolve(modal.id, {
        status: modal.action,
        resolution: resolutionText,
      })
      setModal(null)
      setResolutionText('')
      loadDisputes()
    } catch (error) {
      console.error('Failed to resolve dispute:', error)
    }
  }

  const openDisputes = disputes.filter(d => d.status === 'open' || d.status === 'investigating')
  const resolvedDisputes = disputes.filter(d => d.status === 'resolved' || d.status === 'rejected')

  if (loading) return <div className="text-center py-12">Loading disputes...</div>

  return (
    <div className="space-y-8">
      <div className="flex items-center gap-2">
        <AlertTriangle className="w-6 h-6 text-orange-600" />
        <h1 className="text-2xl font-bold">Dispute Queue</h1>
      </div>

      {/* Open Disputes */}
      <div className="bg-white p-6 rounded-lg shadow">
        <h2 className="text-lg font-semibold mb-4">
          Open Disputes
          {openDisputes.length > 0 && (
            <span className="ml-2 bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded-full">{openDisputes.length}</span>
          )}
        </h2>
        {openDisputes.length === 0 ? (
          <p className="text-gray-500 text-center py-6">No open disputes</p>
        ) : (
          <div className="space-y-3">
            {openDisputes.map(d => (
              <div key={d.id} className="p-4 border rounded-lg">
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                      <span className="font-semibold">Dispute #{d.id}</span>
                      <span className={`text-xs px-2 py-0.5 rounded ${
                        d.status === 'investigating' ? 'bg-blue-100 text-blue-800' :
                        'bg-yellow-100 text-yellow-800'
                      }`}>
                        {d.status}
                      </span>
                    </div>
                    <p className="text-sm text-gray-500">Order #{d.order_id}</p>
                    <p className="text-sm mt-1">{d.reason}</p>
                  </div>
                  <div className="flex gap-2 ml-4">
                    <Button size="sm" onClick={() => setModal({ id: d.id, action: 'resolved' })}>
                      Resolve
                    </Button>
                    <Button size="sm" variant="destructive" onClick={() => setModal({ id: d.id, action: 'rejected' })}>
                      Reject
                    </Button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Resolved Disputes */}
      <div className="bg-white p-6 rounded-lg shadow">
        <h2 className="text-lg font-semibold mb-4">Resolved ({resolvedDisputes.length})</h2>
        {resolvedDisputes.length === 0 ? (
          <p className="text-gray-500 text-center py-6">No resolved disputes</p>
        ) : (
          <div className="space-y-3">
            {resolvedDisputes.map(d => (
              <div key={d.id} className="p-4 border rounded-lg">
                <div className="flex items-center gap-2 mb-1">
                  <span className="font-semibold">Dispute #{d.id}</span>
                  <span className={`text-xs px-2 py-0.5 rounded ${
                    d.status === 'resolved' ? 'bg-green-100 text-green-800' :
                    'bg-red-100 text-red-800'
                  }`}>
                    {d.status}
                  </span>
                </div>
                <p className="text-sm text-gray-500">Order #{d.order_id}</p>
                <p className="text-sm mt-1">{d.reason}</p>
                {d.resolution && (
                  <p className="text-sm text-green-700 mt-1">Resolution: {d.resolution}</p>
                )}
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Modal */}
      {modal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h3 className="text-lg font-semibold mb-4">
              {modal.action === 'resolved' ? 'Resolve Dispute' : 'Reject Dispute'}
            </h3>
            <textarea
              className="w-full border rounded-lg p-3 mb-4"
              rows={4}
              placeholder="Enter resolution details..."
              value={resolutionText}
              onChange={(e) => setResolutionText(e.target.value)}
            />
            <div className="flex justify-end gap-2">
              <Button variant="outline" onClick={() => { setModal(null); setResolutionText('') }}>
                Cancel
              </Button>
              <Button
                variant={modal.action === 'rejected' ? 'destructive' : 'default'}
                onClick={handleResolve}
                disabled={!resolutionText.trim()}
              >
                Confirm
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
