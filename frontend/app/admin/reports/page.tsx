'use client'

import { useEffect, useState } from 'react'
import { BarChart3, DollarSign, ShoppingCart, TrendingUp } from "lucide-react"
import { ordersAPI, eventsAPI, payoutsAPI } from "@/lib/api"
import { formatCurrency } from "@/lib/utils"

interface Order {
  id: number
  attendee_id: number
  status: string
  total_amount: string
  created_at: string
}

interface Event {
  id: number
  title: string
  event_date: string
  vendor_id: number
}

interface Payout {
  id: number
  vendor_id: number
  gross_amount: string
  commission: string
  amount: string
  status: string
  created_at: string
}

export default function AdminReportsPage() {
  const [orders, setOrders] = useState<Order[]>([])
  const [events, setEvents] = useState<Event[]>([])
  const [payouts, setPayouts] = useState<Payout[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => { loadAll() }, [])

  const loadAll = async () => {
    try {
      const [ordersRes, eventsRes, payoutsRes] = await Promise.all([
        ordersAPI.getAll(),
        eventsAPI.getAll(),
        payoutsAPI.getAll(),
      ])
      const oRaw = ordersRes.data.data
      setOrders(Array.isArray(oRaw) ? oRaw : (oRaw?.data || []))
      const eRaw = eventsRes.data.data
      setEvents(Array.isArray(eRaw) ? eRaw : (eRaw?.data || []))
      const pRaw = payoutsRes.data.data
      setPayouts(Array.isArray(pRaw) ? pRaw : (pRaw?.data || []))
    } catch (error) {
      console.error('Failed to load reports:', error)
    } finally {
      setLoading(false)
    }
  }

  if (loading) return <div className="text-center py-12">Loading reports...</div>

  const totalRevenue = orders.reduce((sum, o) => sum + parseFloat(o.total_amount || '0'), 0)
  const paidOrders = orders.filter(o => o.status === 'paid' || o.status === 'completed')
  const refundedOrders = orders.filter(o => o.status === 'refunded')
  const totalPaidOut = payouts.filter(p => p.status === 'paid').reduce((sum, p) => sum + parseFloat(p.amount || '0'), 0)
  const pendingPayouts = payouts.filter(p => p.status === 'pending').reduce((sum, p) => sum + parseFloat(p.amount || '0'), 0)
  const totalCommission = payouts.reduce((sum, p) => sum + parseFloat(p.commission || '0'), 0)

  return (
    <div className="space-y-8">
      <div className="flex items-center gap-2">
        <BarChart3 className="w-6 h-6 text-purple-600" />
        <h1 className="text-2xl font-bold">Reports</h1>
      </div>

      {/* Sales Overview */}
      <div className="grid md:grid-cols-4 gap-6">
        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center justify-between mb-2">
            <span className="text-gray-500 text-sm">Total Revenue</span>
            <DollarSign className="w-5 h-5 text-green-600" />
          </div>
          <p className="text-2xl font-bold">{formatCurrency(totalRevenue)}</p>
          <p className="text-xs text-gray-400 mt-1">From {orders.length} orders</p>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center justify-between mb-2">
            <span className="text-gray-500 text-sm">Paid Orders</span>
            <ShoppingCart className="w-5 h-5 text-blue-600" />
          </div>
          <p className="text-2xl font-bold">{paidOrders.length}</p>
          <p className="text-xs text-gray-400 mt-1">{refundedOrders.length} refunded</p>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center justify-between mb-2">
            <span className="text-gray-500 text-sm">Commission Earned</span>
            <TrendingUp className="w-5 h-5 text-purple-600" />
          </div>
          <p className="text-2xl font-bold">{formatCurrency(totalCommission)}</p>
          <p className="text-xs text-gray-400 mt-1">10% platform fee</p>
        </div>

        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex items-center justify-between mb-2">
            <span className="text-gray-500 text-sm">Payouts Settled</span>
            <DollarSign className="w-5 h-5 text-indigo-600" />
          </div>
          <p className="text-2xl font-bold">{formatCurrency(totalPaidOut)}</p>
          <p className="text-xs text-gray-400 mt-1">{formatCurrency(pendingPayouts)} pending</p>
        </div>
      </div>

      {/* Recent Orders */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="p-6 border-b">
          <h2 className="text-lg font-semibold">Recent Orders</h2>
        </div>
        {orders.length === 0 ? (
          <div className="text-center py-8 text-gray-400 text-sm">No orders yet.</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="bg-gray-50/50 text-xs text-gray-500 uppercase font-semibold border-b">
                <tr>
                  <th className="px-6 py-3">Order ID</th>
                  <th className="px-6 py-3">Status</th>
                  <th className="px-6 py-3">Amount</th>
                  <th className="px-6 py-3">Date</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {orders.slice(0, 20).map(order => (
                  <tr key={order.id} className="hover:bg-gray-50/50">
                    <td className="px-6 py-3 font-mono text-gray-600">#{order.id}</td>
                    <td className="px-6 py-3">
                      <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                        order.status === 'paid' || order.status === 'completed' ? 'bg-green-100 text-green-700' :
                        order.status === 'refunded' ? 'bg-red-100 text-red-700' :
                        order.status === 'held' ? 'bg-yellow-100 text-yellow-700' :
                        'bg-gray-100 text-gray-700'
                      }`}>
                        {order.status}
                      </span>
                    </td>
                    <td className="px-6 py-3 font-semibold">{formatCurrency(parseFloat(order.total_amount || '0'))}</td>
                    <td className="px-6 py-3 text-gray-500">{new Date(order.created_at).toLocaleDateString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Payout History */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="p-6 border-b">
          <h2 className="text-lg font-semibold">Payout Settlements</h2>
        </div>
        {payouts.length === 0 ? (
          <div className="text-center py-8 text-gray-400 text-sm">No payouts yet.</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
              <thead className="bg-gray-50/50 text-xs text-gray-500 uppercase font-semibold border-b">
                <tr>
                  <th className="px-6 py-3">Payout ID</th>
                  <th className="px-6 py-3">Vendor</th>
                  <th className="px-6 py-3">Gross</th>
                  <th className="px-6 py-3">Commission</th>
                  <th className="px-6 py-3">Net Payout</th>
                  <th className="px-6 py-3">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {payouts.map(payout => (
                  <tr key={payout.id} className="hover:bg-gray-50/50">
                    <td className="px-6 py-3 font-mono text-gray-600">#{payout.id}</td>
                    <td className="px-6 py-3 text-gray-600">#{payout.vendor_id}</td>
                    <td className="px-6 py-3">{formatCurrency(parseFloat(payout.gross_amount || '0'))}</td>
                    <td className="px-6 py-3 text-red-500">-{formatCurrency(parseFloat(payout.commission || '0'))}</td>
                    <td className="px-6 py-3 font-semibold">{formatCurrency(parseFloat(payout.amount || '0'))}</td>
                    <td className="px-6 py-3">
                      <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                        payout.status === 'paid' ? 'bg-green-100 text-green-700' :
                        payout.status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                        'bg-gray-100 text-gray-700'
                      }`}>
                        {payout.status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  )
}
