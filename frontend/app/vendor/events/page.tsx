'use client'

import { useEffect, useState } from 'react'
import { Button } from "@/components/ui/button"
import { Calendar, MapPin, Plus, ArrowLeft, Users, DollarSign } from "lucide-react"
import { eventsAPI, authAPI } from "@/lib/api"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { useAuthStore } from "@/lib/store"

interface Event {
  id: number
  title: string
  description: string
  location: string
  event_date: string
  created_at: string
}

export default function VendorEventsPage() {
  const router = useRouter()
  const { user, token, setUser, logout } = useAuthStore()
  const [authLoading, setAuthLoading] = useState(true)
  const [events, setEvents] = useState<Event[]>([])
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
      loadEvents()
    }
  }, [authLoading])

  const loadEvents = async () => {
    try {
      const vendorId = user?.vendor?.id
      const response = await eventsAPI.getAll({ vendor_id: vendorId })
      const eventsData = response.data.data || []
      setEvents(eventsData)
    } catch (error) {
      console.error('Failed to load events:', error)
    } finally {
      setLoading(false)
    }
  }

  const formatDate = (dateString: string) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
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
          <div className="flex items-center gap-3">
            <Link href="/vendor/dashboard">
              <Button variant="outline" size="sm">
                <ArrowLeft className="w-4 h-4" />
              </Button>
            </Link>
            <h1 className="text-3xl font-bold">My Events</h1>
          </div>
          <Link href="/vendor/events/create">
            <Button>
              <Plus className="w-4 h-4 mr-2" />
              Create Event
            </Button>
          </Link>
        </div>

        {loading ? (
          <div className="text-center py-12">Loading events...</div>
        ) : events.length === 0 ? (
          <div className="bg-white p-12 rounded-lg shadow-md text-center">
            <Calendar className="w-16 h-16 text-gray-300 mx-auto mb-4" />
            <h2 className="text-xl font-semibold text-gray-700 mb-2">No events yet</h2>
            <p className="text-gray-500 mb-6">Get started by creating your first event</p>
            <Link href="/vendor/events/create">
              <Button>
                <Plus className="w-4 h-4 mr-2" />
                Create Your First Event
              </Button>
            </Link>
          </div>
        ) : (
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {events.map((event) => (
              <div key={event.id} className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div className="p-6">
                  <h3 className="text-xl font-semibold mb-2 line-clamp-1">{event.title}</h3>
                  <p className="text-gray-600 text-sm mb-4 line-clamp-2">{event.description}</p>
                  
                  <div className="space-y-2 text-sm text-gray-600">
                    <div className="flex items-center gap-2">
                      <Calendar className="w-4 h-4 text-blue-600" />
                      <span>{formatDate(event.event_date)}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <MapPin className="w-4 h-4 text-red-600" />
                      <span className="line-clamp-1">{event.location}</span>
                    </div>
                  </div>

                  <div className="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                    <span className="text-xs text-gray-400">
                      Created {formatDate(event.created_at)}
                    </span>
                    <Link href={`/vendor/events/${event.id}`}>
                      <Button variant="outline" size="sm">
                        View Details
                      </Button>
                    </Link>
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
