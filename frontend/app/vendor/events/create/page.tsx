'use client'

import { useState, useEffect } from 'react'
import { Button } from "@/components/ui/button"
import { eventsAPI, ticketTypesAPI, authAPI } from "@/lib/api"
import { useAuthStore } from "@/lib/store"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { Plus, Trash2, Calendar, MapPin, Tag, PlusCircle, ArrowLeft } from "lucide-react"

export default function CreateEventPage() {
  const router = useRouter()
  const { user, token, setUser, logout } = useAuthStore()
  const [authLoading, setAuthLoading] = useState(true)

  // Event Details State
  const [title, setTitle] = useState('')
  const [description, setDescription] = useState('')
  const [location, setLocation] = useState('')
  const [eventDate, setEventDate] = useState('')

  // Ticket Tiers State
  const [ticketTiers, setTicketTiers] = useState<Array<{
    type: string;
    price: string;
    inventory: string;
    available_from: string;
    available_until: string;
  }>>([
    { type: 'General Admission', price: '49.99', inventory: '100', available_from: '', available_until: '' }
  ])

  const [loading, setLoading] = useState(false)
  const [errorMsg, setErrorMsg] = useState('')

  useEffect(() => {
    const verifyAuth = async () => {
      if (!token) {
        router.push('/vendor')
        return
      }

      if (!user) {
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
      } else {
        if (user.type !== 'vendor') {
          logout()
          router.push('/vendor')
        } else {
          setAuthLoading(false)
        }
      }
    }

    verifyAuth()
  }, [token, user, router, setUser, logout])

  const handleAddTier = () => {
    setTicketTiers(prev => [
      ...prev,
      { type: '', price: '', inventory: '', available_from: '', available_until: '' }
    ])
  }

  const handleRemoveTier = (index: number) => {
    if (ticketTiers.length === 1) {
      setErrorMsg('You must define at least one ticket type.')
      return
    }
    setTicketTiers(prev => prev.filter((_, idx) => idx !== index))
  }

  const handleTierChange = (index: number, field: string, value: string) => {
    setTicketTiers(prev => prev.map((tier, idx) => {
      if (idx === index) {
        return { ...tier, [field]: value }
      }
      return tier
    }))
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setErrorMsg('')

    try {
      // 1. Create Event
      const eventResponse = await eventsAPI.create({
        title,
        description,
        location,
        event_date: eventDate,
      })

      const eventId = eventResponse.data.data.id

      // 2. Create Ticket Tiers Sequentially
      for (const tier of ticketTiers) {
        const payload: any = {
          event_id: eventId,
          type: tier.type,
          price: parseFloat(tier.price),
          inventory: parseInt(tier.inventory),
        }

        if (tier.available_from) payload.available_from = tier.available_from
        if (tier.available_until) payload.available_until = tier.available_until

        await ticketTypesAPI.create(payload)
      }

      router.push('/vendor/dashboard')
    } catch (error: any) {
      console.error('Failed to create event & ticket types:', error)
      setErrorMsg(error.response?.data?.message || 'Failed to create event. Please verify your details.')
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
    <div className="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-4xl mx-auto">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div className="flex items-center gap-3">
            <Link href="/vendor/dashboard">
              <Button variant="outline" size="sm">
                <ArrowLeft className="w-4 h-4" />
              </Button>
            </Link>
            <h1 className="text-3xl font-bold tracking-tight text-gray-900">Create New Event</h1>
          </div>
        </div>

        {errorMsg && (
          <div className="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl mb-6 text-sm">
            {errorMsg}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-8">
          
          {/* Section 1: Event Details */}
          <div className="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6">
            <h2 className="text-xl font-semibold text-gray-800 flex items-center gap-2">
              <Calendar className="w-5 h-5 text-indigo-600" /> Event Details
            </h2>
            <div className="h-px bg-gray-100"></div>

            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Event Title</label>
                <input
                  type="text"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  placeholder="e.g. Summer Music Festival 2026"
                  className="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  placeholder="Provide details about your event, schedule, line-up, etc."
                  rows={4}
                  className="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm resize-none"
                />
              </div>

              <div className="grid md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Location</label>
                  <div className="relative">
                    <MapPin className="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                    <input
                      type="text"
                      value={location}
                      onChange={(e) => setLocation(e.target.value)}
                      placeholder="e.g. Dubai World Trade Centre"
                      className="w-full pl-10 pr-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                      required
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Event Date & Time</label>
                  <input
                    type="datetime-local"
                    value={eventDate}
                    onChange={(e) => setEventDate(e.target.value)}
                    className="w-full px-4 py-2 border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                    required
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Section 2: Ticket Tiers Setup */}
          <div className="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6">
            <div className="flex justify-between items-center">
              <h2 className="text-xl font-semibold text-gray-800 flex items-center gap-2">
                <Tag className="w-5 h-5 text-indigo-600" /> Ticket Pricing & Inventory
              </h2>
              <Button type="button" variant="outline" size="sm" onClick={handleAddTier} className="text-indigo-600 border-indigo-100 hover:bg-indigo-50/50">
                <Plus className="w-4 h-4 mr-1" /> Add Ticket Class
              </Button>
            </div>
            <div className="h-px bg-gray-100"></div>

            <div className="space-y-6">
              {ticketTiers.map((tier, idx) => (
                <div key={idx} className="p-4 rounded-xl border border-gray-100 bg-gray-50/50 relative space-y-4">
                  <div className="flex justify-between items-center">
                    <span className="text-sm font-semibold text-gray-600">Ticket Class #{idx + 1}</span>
                    <button
                      type="button"
                      onClick={() => handleRemoveTier(idx)}
                      className="text-gray-400 hover:text-rose-600 transition-colors"
                      title="Remove tier"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>

                  <div className="grid md:grid-cols-3 gap-4">
                    <div>
                      <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Ticket Name</label>
                      <input
                        type="text"
                        value={tier.type}
                        onChange={(e) => handleTierChange(idx, 'type', e.target.value)}
                        placeholder="e.g. VIP Pass, Early Bird"
                        className="w-full px-3 py-2 border rounded-lg bg-white focus:outline-none text-sm"
                        required
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Price (USD)</label>
                      <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={tier.price}
                        onChange={(e) => handleTierChange(idx, 'price', e.target.value)}
                        placeholder="0.00"
                        className="w-full px-3 py-2 border rounded-lg bg-white focus:outline-none text-sm"
                        required
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Capacity</label>
                      <input
                        type="number"
                        min="1"
                        value={tier.inventory}
                        onChange={(e) => handleTierChange(idx, 'inventory', e.target.value)}
                        placeholder="100"
                        className="w-full px-3 py-2 border rounded-lg bg-white focus:outline-none text-sm"
                        required
                      />
                    </div>
                  </div>

                  <div className="grid md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Sales Start Date (Optional)</label>
                      <input
                        type="datetime-local"
                        value={tier.available_from}
                        onChange={(e) => handleTierChange(idx, 'available_from', e.target.value)}
                        className="w-full px-3 py-2 border rounded-lg bg-white focus:outline-none text-sm"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Sales End Date (Optional)</label>
                      <input
                        type="datetime-local"
                        value={tier.available_until}
                        onChange={(e) => handleTierChange(idx, 'available_until', e.target.value)}
                        className="w-full px-3 py-2 border rounded-lg bg-white focus:outline-none text-sm"
                      />
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Form Actions */}
          <div className="flex justify-end gap-4">
            <Link href="/vendor/dashboard">
              <Button type="button" variant="outline" className="px-6 py-3 rounded-xl text-sm border-gray-200">
                Cancel
              </Button>
            </Link>
            <Button type="submit" className="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm" disabled={loading}>
              {loading ? 'Creating Event...' : 'Publish Event'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  )
}
