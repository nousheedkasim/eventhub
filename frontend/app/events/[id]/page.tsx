'use client'

import { useEffect, useState } from 'react'
import { Button } from "@/components/ui/button"
import { Calendar, MapPin, DollarSign, Plus, Minus } from "lucide-react"
import { eventsAPI, ticketTypesAPI, ordersAPI } from "@/lib/api"
import { useCartStore } from "@/lib/store"
import { formatCurrency, formatDate } from "@/lib/utils"
import Link from "next/link"
import { useRouter } from "next/navigation"

interface TicketType {
  id: number
  event_id: number
  type: string
  price: string
  inventory: number
  sold_count: number
  available_from: string
  available_until: string
  is_active: boolean
}

export default function EventDetailPage({ params }: { params: { id: string } }) {
  const router = useRouter()
  const [event, setEvent] = useState<any>(null)
  const [ticketTypes, setTicketTypes] = useState<TicketType[]>([])
  const [loading, setLoading] = useState(true)
  const [quantities, setQuantities] = useState<Record<number, number>>({})
  const { addToCart, getTotal } = useCartStore()

  useEffect(() => {
    loadEventData()
  }, [params.id])

  const loadEventData = async () => {
    try {
      const [eventRes, ticketsRes] = await Promise.all([
        eventsAPI.getById(parseInt(params.id)),
        ticketTypesAPI.getByEvent(parseInt(params.id)),
      ])
      
      // Handle both nested and direct response structures
      const eventData = eventRes.data.data || eventRes.data
      const ticketsData = ticketsRes.data.data || ticketsRes.data || []
      
      setEvent(eventData)
      setTicketTypes(Array.isArray(ticketsData) ? ticketsData : [])
      
      // Initialize quantities
      const initialQuantities: Record<number, number> = {}
      if (Array.isArray(ticketsData)) {
        ticketsData.forEach((tt: TicketType) => {
          initialQuantities[tt.id] = 0
        })
      }
      setQuantities(initialQuantities)
    } catch (error) {
      console.error('Failed to load event:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleQuantityChange = (ticketTypeId: number, delta: number) => {
    setQuantities(prev => {
      const newQty = Math.max(0, (prev[ticketTypeId] || 0) + delta)
      const ticketType = ticketTypes.find(tt => tt.id === ticketTypeId)
      if (ticketType && newQty <= (ticketType.inventory - ticketType.sold_count)) {
        return { ...prev, [ticketTypeId]: newQty }
      }
      return prev
    })
  }

  const handleAddToCart = () => {
    const items = Object.entries(quantities)
      .filter(([_, qty]) => qty > 0)
      .map(([ticketTypeId, qty]) => {
        const ticketType = ticketTypes.find(tt => tt.id === parseInt(ticketTypeId))
        return {
          ticket_type_id: parseInt(ticketTypeId),
          qty,
          price: parseFloat(ticketType?.price || '0'),
          type_name: ticketType?.type || '',
        }
      })

    if (items.length === 0) {
      alert('Please select at least one ticket')
      return
    }

    items.forEach(item => {
      addToCart(item, parseInt(params.id))
    })

    router.push('/checkout')
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center py-12">Loading event details...</div>
      </div>
    )
  }

  if (!event) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center py-12">Event not found</div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="container mx-auto px-4 py-8">
        <Link href="/events">
          <Button variant="outline" className="mb-6">← Back to Events</Button>
        </Link>

        <div className="bg-white rounded-lg shadow-md overflow-hidden">
          <div className="p-8">
            <h1 className="text-4xl font-bold mb-4">{event.title}</h1>
            <p className="text-gray-600 mb-6">{event.description}</p>
            
            <div className="flex gap-6 text-gray-600 mb-8">
              <div className="flex items-center gap-2">
                <Calendar className="w-5 h-5" />
                <span>{formatDate(event.event_date)}</span>
              </div>
              <div className="flex items-center gap-2">
                <MapPin className="w-5 h-5" />
                <span>{event.location}</span>
              </div>
            </div>

            <h2 className="text-2xl font-semibold mb-4">Available Tickets</h2>
            
            {ticketTypes.length === 0 ? (
              <div className="text-gray-500 text-center py-8">No tickets available</div>
            ) : (
              <div className="space-y-4">
                {ticketTypes.map((ticketType) => {
                  const available = ticketType.inventory - ticketType.sold_count
                  const qty = quantities[ticketType.id] || 0
                  
                  return (
                    <div key={ticketType.id} className="border rounded-lg p-4 flex items-center justify-between">
                      <div className="flex-1">
                        <h3 className="font-semibold text-lg">{ticketType.type}</h3>
                        <div className="flex items-center gap-4 text-sm text-gray-600">
                          <div className="flex items-center gap-1">
                            <DollarSign className="w-4 h-4" />
                            <span>{formatCurrency(parseFloat(ticketType.price))}</span>
                          </div>
                          <span>{available} available</span>
                        </div>
                      </div>
                      
                      <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2">
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => handleQuantityChange(ticketType.id, -1)}
                            disabled={qty === 0}
                          >
                            <Minus className="w-4 h-4" />
                          </Button>
                          <span className="w-8 text-center">{qty}</span>
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => handleQuantityChange(ticketType.id, 1)}
                            disabled={qty >= available}
                          >
                            <Plus className="w-4 h-4" />
                          </Button>
                        </div>
                      </div>
                    </div>
                  )
                })}
              </div>
            )}

            <div className="mt-8 pt-6 border-t">
              <div className="flex justify-between items-center">
                <div>
                  <span className="text-gray-600">Total:</span>
                  <span className="text-2xl font-bold ml-2">
                    {formatCurrency(
                      Object.entries(quantities).reduce((sum, [ticketTypeId, qty]) => {
                        const ticketType = ticketTypes.find(tt => tt.id === parseInt(ticketTypeId))
                        return sum + parseFloat(ticketType?.price || '0') * qty
                      }, 0)
                    )}
                  </span>
                </div>
                <Button 
                  size="lg"
                  onClick={handleAddToCart}
                  disabled={Object.values(quantities).every(q => q === 0)}
                >
                  Proceed to Checkout
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
