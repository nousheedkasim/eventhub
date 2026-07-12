'use client'

import { useEffect, useState } from 'react'
import { Button } from "@/components/ui/button"
import { ordersAPI } from "@/lib/api"
import { formatCurrency } from "@/lib/utils"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { useAuthStore } from "@/lib/store"

interface OrderItem {
  ticket_type_id: number
  qty: number
  price_at_purchase: number
}

interface Order {
  id: number
  attendee_id: number
  status: string
  total_amount: number
  hold_expires_at: string | null
  created_at: string
  items: OrderItem[]
}

export default function OrdersPage() {
  const router = useRouter()
  const { token } = useAuthStore()
  const [orders, setOrders] = useState<Order[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (!token) {
      router.push('/login')
      return
    }
    fetchOrders()
  }, [token, router])

  const fetchOrders = async () => {
    try {
      const response = await ordersAPI.getAll()
      const result = response.data.data
      setOrders(Array.isArray(result) ? result : result?.data ?? [])
    } catch (error) {
      console.error('Failed to fetch orders:', error)
    } finally {
      setLoading(false)
    }
  }

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'paid':
        return 'bg-green-100 text-green-800'
      case 'held':
        return 'bg-blue-100 text-blue-800'
      case 'pending':
        return 'bg-yellow-100 text-yellow-800'
      case 'cancelled':
      case 'expired':
        return 'bg-red-100 text-red-800'
      case 'refunded':
        return 'bg-gray-100 text-gray-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  }

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-gray-600">Loading orders...</div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="max-w-4xl mx-auto px-4">
        <div className="flex justify-between items-center mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">My Orders</h1>
            <p className="mt-2 text-gray-600">View your ticket purchase history</p>
          </div>
          <Link href="/events">
            <Button variant="outline">Browse Events</Button>
          </Link>
        </div>

        {orders.length === 0 ? (
          <div className="bg-white rounded-lg shadow p-8 text-center">
            <p className="text-gray-500 mb-4">No orders yet.</p>
            <Link href="/events">
              <Button>Browse Events</Button>
            </Link>
          </div>
        ) : (
          <div className="space-y-4">
            {orders.map((order) => (
              <div key={order.id} className="bg-white rounded-lg shadow-md p-6">
                <div className="flex items-start justify-between mb-4">
                  <div>
                    <h3 className="text-lg font-semibold">Order #{order.id}</h3>
                    <p className="text-sm text-gray-500">{formatDate(order.created_at)}</p>
                  </div>
                  <span className={`px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(order.status)}`}>
                    {order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                  </span>
                </div>

                {order.items && order.items.length > 0 && (
                  <div className="border-t pt-4 mb-4">
                    <p className="text-sm font-medium text-gray-700 mb-2">Items:</p>
                    {order.items.map((item, idx) => (
                      <div key={idx} className="flex justify-between text-sm text-gray-600">
                        <span>Ticket Type #{item.ticket_type_id} x {item.qty}</span>
                        <span>{formatCurrency(item.price_at_purchase * item.qty)}</span>
                      </div>
                    ))}
                  </div>
                )}

                <div className="border-t pt-4 flex justify-between items-center">
                  <div className="text-sm text-gray-600">
                    {order.hold_expires_at && order.status === 'held' && (
                      <span className="text-orange-600">
                        Hold expires: {formatDate(order.hold_expires_at)}
                      </span>
                    )}
                  </div>
                  <div className="text-right">
                    <span className="text-sm text-gray-600">Total: </span>
                    <span className="text-lg font-bold">{formatCurrency(order.total_amount)}</span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
