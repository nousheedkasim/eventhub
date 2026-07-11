'use client'

import { useEffect, useState } from 'react'
import { BarChart3, Users, Calendar, DollarSign, ShoppingCart, Ticket } from "lucide-react"
import { vendorsAPI, eventsAPI, ordersAPI } from "@/lib/api"
import Link from "next/link"
import { formatCurrency } from "@/lib/utils"

export default function AdminDashboard() {
  const [stats, setStats] = useState({
    totalVendors: 0,
    verifiedVendors: 0,
    pendingVendors: 0,
    totalEvents: 0,
    totalOrders: 0,
    totalTicketsSold: 0,
    totalRevenue: 0,
  })
  const [loading, setLoading] = useState(true)

  useEffect(() => { loadStats() }, [])

  const loadStats = async () => {
    try {
      const [vendorsRes, eventsRes, ordersRes] = await Promise.all([
        vendorsAPI.getAll(),
        eventsAPI.getAll(),
        ordersAPI.getAll(),
      ])

      const vendors = vendorsRes.data.data || []
      const events = Array.isArray(eventsRes.data.data) ? eventsRes.data.data : (eventsRes.data.data?.data || [])
      const orders = ordersRes.data.data || []
      const ordersList = Array.isArray(orders) ? orders : (orders?.data || [])

      setStats({
        totalVendors: vendors.length,
        verifiedVendors: vendors.filter((v: any) => v.kyc_status === 'verified').length,
        pendingVendors: vendors.filter((v: any) => v.kyc_status === 'pending').length,
        totalEvents: events.length,
        totalOrders: ordersList.length,
        totalTicketsSold: ordersList.reduce((sum: number, o: any) => {
          const items = o.items || []
          return sum + items.reduce((s: number, i: any) => s + (i.qty || 0), 0)
        }, 0),
        totalRevenue: ordersList.reduce((sum: number, o: any) => sum + parseFloat(o.total_amount || 0), 0),
      })
    } catch (error) {
      console.error('Failed to load dashboard stats:', error)
    } finally {
      setLoading(false)
    }
  }

  if (loading) return <div className="text-center py-12">Loading dashboard...</div>

  return (
    <div className="space-y-8">
      <div className="flex items-center gap-2">
        <BarChart3 className="w-6 h-6 text-red-600" />
        <h1 className="text-2xl font-bold">Admin Dashboard</h1>
      </div>

      {/* Primary Stats */}
      <div className="grid md:grid-cols-4 gap-6">
        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center gap-4">
            <div className="p-3 bg-blue-100 rounded-full">
              <Users className="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <p className="text-gray-500 text-sm">Total Vendors</p>
              <p className="text-3xl font-bold">{stats.totalVendors}</p>
              <p className="text-xs text-gray-400">{stats.verifiedVendors} verified, {stats.pendingVendors} pending</p>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center gap-4">
            <div className="p-3 bg-green-100 rounded-full">
              <Calendar className="w-6 h-6 text-green-600" />
            </div>
            <div>
              <p className="text-gray-500 text-sm">Total Events</p>
              <p className="text-3xl font-bold">{stats.totalEvents}</p>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center gap-4">
            <div className="p-3 bg-orange-100 rounded-full">
              <Ticket className="w-6 h-6 text-orange-600" />
            </div>
            <div>
              <p className="text-gray-500 text-sm">Tickets Sold</p>
              <p className="text-3xl font-bold">{stats.totalTicketsSold}</p>
              <p className="text-xs text-gray-400">{stats.totalOrders} orders</p>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center gap-4">
            <div className="p-3 bg-purple-100 rounded-full">
              <DollarSign className="w-6 h-6 text-purple-600" />
            </div>
            <div>
              <p className="text-gray-500 text-sm">Total Revenue</p>
              <p className="text-3xl font-bold">{formatCurrency(stats.totalRevenue)}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Quick Links */}
      <div className="grid md:grid-cols-3 gap-6">
        <Link href="/admin/vendor" className="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow">
          <div className="flex items-center gap-3">
            <Users className="w-8 h-8 text-blue-600" />
            <div>
              <h3 className="font-semibold text-gray-900">Manage Vendors</h3>
              <p className="text-sm text-gray-500">{stats.pendingVendors} pending approvals</p>
            </div>
          </div>
        </Link>

        <Link href="/admin/events" className="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow">
          <div className="flex items-center gap-3">
            <Calendar className="w-8 h-8 text-green-600" />
            <div>
              <h3 className="font-semibold text-gray-900">All Events</h3>
              <p className="text-sm text-gray-500">{stats.totalEvents} events on platform</p>
            </div>
          </div>
        </Link>

        <Link href="/admin/reports" className="bg-white p-6 rounded-lg shadow hover:shadow-md transition-shadow">
          <div className="flex items-center gap-3">
            <BarChart3 className="w-8 h-8 text-purple-600" />
            <div>
              <h3 className="font-semibold text-gray-900">Reports</h3>
              <p className="text-sm text-gray-500">Sales, payouts, and analytics</p>
            </div>
          </div>
        </Link>
      </div>
    </div>
  )
}
