'use client'

import { useEffect, useState } from 'react'
import { Calendar, MapPin, Users } from "lucide-react"
import { eventsAPI } from "@/lib/api"
import Link from "next/link"

interface Event {
  id: number
  title: string
  description: string
  location: string
  event_date: string
  vendor_id: number
  created_at: string
}

export default function AdminEventsPage() {
  const [events, setEvents] = useState<Event[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => { loadEvents() }, [])

  const loadEvents = async () => {
    try {
      const res = await eventsAPI.getAll()
      const raw = res.data.data
      setEvents(Array.isArray(raw) ? raw : (raw?.data || []))
    } catch (error) {
      console.error('Failed to load events:', error)
    } finally {
      setLoading(false)
    }
  }

  if (loading) return <div className="text-center py-12">Loading events...</div>

  return (
    <div className="space-y-8">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <Calendar className="w-6 h-6 text-green-600" />
          <h1 className="text-2xl font-bold">All Events</h1>
        </div>
        <span className="text-sm text-gray-500">{events.length} events total</span>
      </div>

      {events.length === 0 ? (
        <div className="bg-white p-12 rounded-lg shadow text-center text-gray-400">
          No events on the platform yet.
        </div>
      ) : (
        <div className="bg-white rounded-lg shadow overflow-hidden">
          <table className="w-full text-left text-sm">
            <thead className="bg-gray-50 text-xs text-gray-500 uppercase font-semibold border-b">
              <tr>
                <th className="px-6 py-4">Event</th>
                <th className="px-6 py-4">Location</th>
                <th className="px-6 py-4">Date</th>
                <th className="px-6 py-4">Vendor ID</th>
                <th className="px-6 py-4">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {events.map(event => (
                <tr key={event.id} className="hover:bg-gray-50/50 transition-colors">
                  <td className="px-6 py-4">
                    <div className="font-semibold text-gray-900">{event.title}</div>
                    {event.description && (
                      <p className="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{event.description}</p>
                    )}
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-1 text-gray-600">
                      <MapPin className="w-3.5 h-3.5" />
                      {event.location || 'N/A'}
                    </div>
                  </td>
                  <td className="px-6 py-4 text-gray-600">
                    {new Date(event.event_date).toLocaleDateString()}
                  </td>
                  <td className="px-6 py-4 text-gray-500">
                    <span className="inline-flex items-center gap-1 bg-gray-100 px-2 py-0.5 rounded text-xs">
                      <Users className="w-3 h-3" /> #{event.vendor_id}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <Link href={`/events/${event.id}`} className="text-blue-600 hover:underline text-sm">
                      View
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
