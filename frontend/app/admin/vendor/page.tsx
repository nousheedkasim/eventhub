'use client'

import { useEffect, useState } from 'react'
import { Button } from "@/components/ui/button"
import { CheckCircle, XCircle, Users } from "lucide-react"
import { vendorsAPI } from "@/lib/api"

interface Vendor {
  id: number
  company_name: string
  contact_person: string
  email: string
  phone: string
  kyc_status: string
  is_active: boolean
}

export default function AdminVendorPage() {
  const [vendors, setVendors] = useState<Vendor[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => { loadData() }, [])

  const loadData = async () => {
    try {
      const res = await vendorsAPI.getAll()
      const raw = res.data.data
      setVendors(Array.isArray(raw) ? raw : (raw?.data || []))
    } catch (error) {
      console.error('Failed to load vendors:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleApprove = async (id: number) => {
    try {
      await vendorsAPI.approve(id)
      loadData()
    } catch (error) {
      console.error('Failed to approve:', error)
    }
  }

  const handleReject = async (id: number) => {
    try {
      await vendorsAPI.reject(id)
      loadData()
    } catch (error) {
      console.error('Failed to reject:', error)
    }
  }

  const pending = vendors.filter(v => v.kyc_status === 'pending')
  const verified = vendors.filter(v => v.kyc_status === 'verified')
  const rejected = vendors.filter(v => v.kyc_status === 'rejected')

  if (loading) return <div className="text-center py-12">Loading vendors...</div>

  return (
    <div className="space-y-8">
      <div className="flex items-center gap-2">
        <Users className="w-6 h-6 text-blue-600" />
        <h1 className="text-2xl font-bold">Vendor Management</h1>
      </div>

      {/* Summary Cards */}
      <div className="grid md:grid-cols-3 gap-4">
        <div className="bg-white p-4 rounded-lg shadow text-center">
          <p className="text-2xl font-bold text-yellow-600">{pending.length}</p>
          <p className="text-sm text-gray-500">Pending Review</p>
        </div>
        <div className="bg-white p-4 rounded-lg shadow text-center">
          <p className="text-2xl font-bold text-green-600">{verified.length}</p>
          <p className="text-sm text-gray-500">Verified</p>
        </div>
        <div className="bg-white p-4 rounded-lg shadow text-center">
          <p className="text-2xl font-bold text-red-600">{rejected.length}</p>
          <p className="text-sm text-gray-500">Rejected</p>
        </div>
      </div>

      {/* Pending Approvals */}
      <div className="bg-white p-6 rounded-lg shadow">
        <h2 className="text-lg font-semibold mb-4">
          Pending Approvals
          {pending.length > 0 && (
            <span className="ml-2 bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded-full">{pending.length}</span>
          )}
        </h2>
        {pending.length === 0 ? (
          <p className="text-gray-500 text-center py-6">No pending approvals</p>
        ) : (
          <div className="space-y-3">
            {pending.map(v => (
              <div key={v.id} className="flex items-center justify-between p-4 border rounded-lg">
                <div>
                  <h3 className="font-semibold">{v.company_name}</h3>
                  <p className="text-sm text-gray-500">{v.email}</p>
                  {v.contact_person && <p className="text-xs text-gray-400">Contact: {v.contact_person}</p>}
                </div>
                <div className="flex gap-2">
                  <Button size="sm" onClick={() => handleApprove(v.id)}>
                    <CheckCircle className="w-4 h-4 mr-1" /> Approve
                  </Button>
                  <Button size="sm" variant="destructive" onClick={() => handleReject(v.id)}>
                    <XCircle className="w-4 h-4 mr-1" /> Reject
                  </Button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* All Vendors */}
      <div className="bg-white p-6 rounded-lg shadow">
        <h2 className="text-lg font-semibold mb-4">All Vendors ({vendors.length})</h2>
        <div className="space-y-3">
          {vendors.map(v => (
            <div key={v.id} className="flex items-center justify-between p-4 border rounded-lg">
              <div>
                <h3 className="font-semibold">{v.company_name}</h3>
                <p className="text-sm text-gray-500">{v.email}</p>
              </div>
              <span className={`text-xs px-3 py-1 rounded-full font-medium ${
                v.kyc_status === 'verified' ? 'bg-green-100 text-green-800' :
                v.kyc_status === 'rejected' ? 'bg-red-100 text-red-800' :
                'bg-yellow-100 text-yellow-800'
              }`}>
                {v.kyc_status}
              </span>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
