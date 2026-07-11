'use client'

import { useEffect, useState } from 'react'
import { Button } from "@/components/ui/button"
import { Calendar, DollarSign, Users, Plus, TrendingUp } from "lucide-react"
import { eventsAPI, ordersAPI, payoutsAPI, authAPI } from "@/lib/api"
import { formatCurrency } from "@/lib/utils"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { useAuthStore } from "@/lib/store"

interface EventSales {
  eventId: number
  eventTitle: string
  orderCount: number
  revenue: number
}

export default function VendorDashboard() {
  const router = useRouter()
  const { user, token, setUser, logout } = useAuthStore()
  const [authLoading, setAuthLoading] = useState(true)

  const [stats, setStats] = useState({
    totalEvents: 0,
    totalSales: 0,
    totalRevenue: 0,
    pendingPayouts: 0,
  })
  const [eventSales, setEventSales] = useState<EventSales[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const verifyAuth = async () => {
      if (!token) {
        router.push('/vendor')
        return
      }

      try {
        const response = await authAPI.me()
        const currentUser = response.data.data
        if (currentUser && currentUser.type === 'vendor') {
          setUser(currentUser)
          setAuthLoading(false)
        } else {
          logout()
          router.push('/vendor')
        }
      } catch (error) {
        console.error("Auth verification failed:", error)
        logout()
        router.push('/vendor')
      }
    }

    verifyAuth()
  }, [token, router, setUser, logout])

  useEffect(() => {
    if (!authLoading) {
      loadStats()
    }
  }, [authLoading])

  const loadStats = async () => {
    try {
      const vendorId = user?.vendor?.id
      const [eventsRes, ordersRes, payoutsRes] = await Promise.all([
        eventsAPI.getAll({ vendor_id: vendorId }),
        ordersAPI.getAll({ vendor_id: vendorId }),
        payoutsAPI.getAll({ vendor_id: vendorId }),
      ])

      const events = eventsRes.data.data || []
      const ordersRaw = ordersRes.data.data
      const orders = Array.isArray(ordersRaw) ? ordersRaw : (ordersRaw?.data || [])
      const payoutsRaw = payoutsRes.data.data
      const payouts = Array.isArray(payoutsRaw) ? payoutsRaw : (payoutsRaw?.data || [])

      // Build per-event sales breakdown
      const eventMap: Record<number, { title: string; orderCount: number; revenue: number }> = {}

      for (const event of events) {
        eventMap[event.id] = {
          title: event.title,
          orderCount: 0,
          revenue: 0,
        }
      }

      for (const order of orders) {
        const seenEvents: Record<number, boolean> = {}
        const orderItems = order.items || []
        for (const item of orderItems) {
          const eventId = item.ticketType?.event?.id
          if (eventId && eventMap[eventId]) {
            if (!seenEvents[eventId]) {
              eventMap[eventId].orderCount++
              seenEvents[eventId] = true
            }
            eventMap[eventId].revenue += parseFloat(item.price_at_purchase || '0') * (item.qty || 0)
          }
        }
      }

      const breakdown: EventSales[] = Object.entries(eventMap).map(([id, data]) => ({
        eventId: parseInt(id),
        eventTitle: data.title,
        orderCount: data.orderCount,
        revenue: data.revenue,
      }))

      // Sort by revenue descending
      breakdown.sort((a, b) => b.revenue - a.revenue)

      setEventSales(breakdown)
      setStats({
        totalEvents: events.length,
        totalSales: orders.length,
        totalRevenue: orders.reduce((sum: number, order: any) => sum + parseFloat(order.total_amount || '0'), 0),
        pendingPayouts: payouts.filter((p: any) => p.status === 'pending').length || 0,
      })
    } catch (error) {
      console.error('Failed to load stats:', error)
    } finally {
      setLoading(false)
    }
  }

  if (authLoading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-gray-500">Verifying session...</div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="container mx-auto px-4 py-8">
        <div className="flex justify-between items-center mb-8">
          <h1 className="text-3xl font-bold">Vendor Dashboard</h1>
          <Link href="/">
            <Button variant="outline">Back to Home</Button>
          </Link>
        </div>

        {loading ? (
          <div className="text-center py-12">Loading dashboard...</div>
        ) : (
          <>
            {/* Stats Cards */}
            <div className="grid md:grid-cols-4 gap-6 mb-8">
              <Link href="/vendor/events">
                <div className="bg-white p-6 rounded-lg shadow-md cursor-pointer hover:shadow-lg transition-shadow">
                  <div className="flex items-center gap-4">
                    <Calendar className="w-10 h-10 text-blue-600" />
                    <div>
                      <p className="text-gray-600 text-sm">Total Events</p>
                      <p className="text-2xl font-bold">{stats.totalEvents}</p>
                    </div>
                  </div>
                </div>
              </Link>
              <div className="bg-white p-6 rounded-lg shadow-md">
                <div className="flex items-center gap-4">
                  <Users className="w-10 h-10 text-green-600" />
                  <div>
                    <p className="text-gray-600 text-sm">Total Sales</p>
                    <p className="text-2xl font-bold">{stats.totalSales}</p>
                  </div>
                </div>
              </div>
              <div className="bg-white p-6 rounded-lg shadow-md">
                <div className="flex items-center gap-4">
                  <DollarSign className="w-10 h-10 text-purple-600" />
                  <div>
                    <p className="text-gray-600 text-sm">Total Revenue</p>
                    <p className="text-2xl font-bold">{formatCurrency(stats.totalRevenue)}</p>
                  </div>
                </div>
              </div>
              <div className="bg-white p-6 rounded-lg shadow-md">
                <div className="flex items-center gap-4">
                  <DollarSign className="w-10 h-10 text-orange-600" />
                  <div>
                    <p className="text-gray-600 text-sm">Pending Payouts</p>
                    <p className="text-2xl font-bold">{stats.pendingPayouts}</p>
                  </div>
                </div>
              </div>
            </div>

            {/* Quick Actions */}
            <div className="bg-white p-6 rounded-lg shadow-md mb-8">
              <h2 className="text-xl font-semibold mb-4">Quick Actions</h2>
              <div className="flex gap-4">
                <Link href="/vendor/events/create">
                  <Button>
                    <Plus className="w-4 h-4 mr-2" />
                    Create Event
                  </Button>
                </Link>
                <Link href="/vendor/payout">
                  <Button variant="outline">View Payouts</Button>
                </Link>
              </div>
            </div>

            {/* Per-Event Sales Breakdown */}
            <div className="bg-white p-6 rounded-lg shadow-md mb-8">
              <div className="flex items-center gap-2 mb-4">
                <TrendingUp className="w-5 h-5 text-blue-600" />
                <h2 className="text-xl font-semibold">Sales by Event</h2>
              </div>
              {eventSales.length === 0 ? (
                <div className="text-gray-500 text-center py-8">No events yet</div>
              ) : (
                <div className="overflow-x-auto">
                  <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                      <tr>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                        <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Orders</th>
                        <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                      {eventSales.map((es) => (
                        <tr key={es.eventId} className="hover:bg-gray-50">
                          <td className="px-4 py-3 text-sm font-medium text-gray-900">{es.eventTitle}</td>
                          <td className="px-4 py-3 text-sm text-gray-600 text-right">{es.orderCount}</td>
                          <td className="px-4 py-3 text-sm font-medium text-gray-900 text-right">{formatCurrency(es.revenue)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>

            {/* Recent Activity */}
            <div className="bg-white p-6 rounded-lg shadow-md">
              <h2 className="text-xl font-semibold mb-4">Recent Activity</h2>
              <div className="text-gray-500 text-center py-8">
                Recent activity will appear here
              </div>
            </div>
          </>
        )}
      </div>
    </div>
  )
}
