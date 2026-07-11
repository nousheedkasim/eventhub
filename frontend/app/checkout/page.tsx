'use client'

import { useEffect, useState } from 'react'
import { Button } from "@/components/ui/button"
import { CreditCard, Lock } from "lucide-react"
import { ordersAPI, paymentsAPI } from "@/lib/api"
import { useCartStore, useAuthStore } from "@/lib/store"
import { formatCurrency } from "@/lib/utils"
import Link from "next/link"
import { useRouter } from "next/navigation"

export default function CheckoutPage() {
  const router = useRouter()
  const { items, eventId, clearCart, getTotal } = useCartStore()
  const { user, token } = useAuthStore()
  const [loading, setLoading] = useState(false)
  const [orderCreated, setOrderCreated] = useState(false)
  const [orderId, setOrderId] = useState<number | null>(null)
  const [error, setError] = useState('')

  useEffect(() => {
    if (!token) {
      router.push('/login')
    }
  }, [token, router])

  const handlePlaceOrder = async () => {
    if (!user || !token) {
      router.push('/login')
      return
    }

    setLoading(true)
    setError('')
    try {
      // Create order
      const orderResponse = await ordersAPI.create({
        attendee_id: user.id,
        items: items.map(item => ({
          ticket_type_id: item.ticket_type_id,
          qty: item.qty,
        })),
      })

      const order = orderResponse.data.data
      setOrderId(order.id)
      setOrderCreated(true)

      // Initiate payment
      const paymentResponse = await paymentsAPI.create({
        order_id: order.id,
        gateway: 'stripe',
      })

      // In a real app, you'd redirect to payment gateway
      // For now, just show success
      clearCart()
      
      setTimeout(() => {
        router.push('/orders')
      }, 2000)

    } catch (err: any) {
      console.error('Failed to place order:', err)
      const data = err?.response?.data
      if (data?.errors) {
        const firstKey = Object.keys(data.errors)[0]
        setError(Array.isArray(data.errors[firstKey]) ? data.errors[firstKey][0] : data.errors[firstKey])
      } else {
        setError(data?.message || 'Failed to place order. Please try again.')
      }
    } finally {
      setLoading(false)
    }
  }

  if (!token) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <p className="mb-4">Please login to continue</p>
          <Link href="/login">
            <Button>Login</Button>
          </Link>
        </div>
      </div>
    )
  }

  if (orderCreated) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="bg-white p-8 rounded-lg shadow-md text-center">
          <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <Lock className="w-8 h-8 text-green-600" />
          </div>
          <h2 className="text-2xl font-bold mb-2">Order Created!</h2>
          <p className="text-gray-600 mb-4">Your order #{orderId} has been placed successfully.</p>
          <p className="text-sm text-gray-500">Redirecting to your orders...</p>
        </div>
      </div>
    )
  }

  if (items.length === 0) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <p className="mb-4">Your cart is empty</p>
          <Link href="/events">
            <Button>Browse Events</Button>
          </Link>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="container mx-auto px-4 py-8">
        <Link href="/events">
          <Button variant="outline" className="mb-6">← Back to Events</Button>
        </Link>

        <h1 className="text-3xl font-bold mb-8">Checkout</h1>

        <div className="grid md:grid-cols-2 gap-8">
          {/* Order Summary */}
          <div className="bg-white p-6 rounded-lg shadow-md">
            <h2 className="text-xl font-semibold mb-4">Order Summary</h2>
            <div className="space-y-4">
              {items.map((item) => (
                <div key={item.ticket_type_id} className="flex justify-between items-center">
                  <div>
                    <p className="font-medium">{item.type_name}</p>
                    <p className="text-sm text-gray-600">Quantity: {item.qty}</p>
                  </div>
                  <p className="font-semibold">{formatCurrency(item.price * item.qty)}</p>
                </div>
              ))}
            </div>

            <div className="border-t mt-6 pt-6">
              <div className="flex justify-between items-center text-lg font-bold">
                <span>Total</span>
                <span>{formatCurrency(getTotal())}</span>
              </div>
            </div>
          </div>

          {/* Payment Information */}
          <div className="bg-white p-6 rounded-lg shadow-md">
            <h2 className="text-xl font-semibold mb-4">Payment Information</h2>
            
            <div className="space-y-4">
              <div className="flex items-center gap-3 p-4 border rounded-lg">
                <CreditCard className="w-6 h-6 text-blue-600" />
                <div>
                  <p className="font-medium">Credit Card</p>
                  <p className="text-sm text-gray-600">Stripe (Simulated)</p>
                </div>
              </div>

              <div className="bg-blue-50 p-4 rounded-lg">
                <div className="flex items-start gap-2">
                  <Lock className="w-5 h-5 text-blue-600 mt-0.5" />
                  <div className="text-sm text-blue-800">
                    <p className="font-medium">Secure Payment</p>
                    <p>Your payment information is encrypted and secure.</p>
                  </div>
                </div>
              </div>

              <div className="text-sm text-gray-600">
                <p>By placing this order, you agree to our Terms of Service.</p>
              </div>

              {error && (
                <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md text-sm">
                  {error}
                </div>
              )}
            </div>

            <Button
              size="lg"
              className="w-full mt-6"
              onClick={handlePlaceOrder}
              disabled={loading}
            >
              {loading ? 'Processing...' : `Pay ${formatCurrency(getTotal())}`}
            </Button>
          </div>
        </div>
      </div>
    </div>
  )
}
