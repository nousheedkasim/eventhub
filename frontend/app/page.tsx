'use client'

import { useAuthStore } from "@/lib/store"
import { Button } from "@/components/ui/button"
import { Calendar, MapPin, Users, Shield, ShoppingCart } from "lucide-react"
import Link from "next/link"

export default function Home() {
  const { user } = useAuthStore()
  const isAdmin = user?.type === 'admin'
  const isVendor = user?.type === 'vendor'

  return (
    <div className="min-h-screen bg-gradient-to-b from-blue-50 to-white">
      {/* Hero Section */}
      <div className="container mx-auto px-4 py-16">
        <div className="text-center mb-12">
          <h1 className="text-5xl font-bold text-gray-900 mb-4">
            EventHub
          </h1>
          <p className="text-xl text-gray-600 mb-8">
            Discover amazing events and book your tickets
          </p>
          <div className="flex gap-4 justify-center">
            <Link href="/events">
              <Button size="lg">Browse Events</Button>
            </Link>
            {isVendor && (
              <Link href="/vendor/dashboard">
                <Button size="lg" variant="outline">
                  My Dashboard
                </Button>
              </Link>
            )}
            {!isVendor && (
              <Link href="/vendor">
                <Button size="lg" variant="outline">
                  Vendor Portal
                </Button>
              </Link>
            )}
            {isAdmin && (
              <Link href="/admin/dashboard">
                <Button size="lg" variant="destructive">
                  <Shield className="w-4 h-4 mr-2" />
                  Admin Panel
                </Button>
              </Link>
            )}
          </div>
        </div>

        {/* Features */}
        <div className="grid md:grid-cols-3 gap-8 mt-16">
          <div className="bg-white p-6 rounded-lg shadow-md">
            <Calendar className="w-12 h-12 text-blue-600 mb-4" />
            <h3 className="text-xl font-semibold mb-2">Easy Booking</h3>
            <p className="text-gray-600">
              Browse and book tickets for events in just a few clicks
            </p>
          </div>
          <div className="bg-white p-6 rounded-lg shadow-md">
            <MapPin className="w-12 h-12 text-blue-600 mb-4" />
            <h3 className="text-xl font-semibold mb-2">Local Events</h3>
            <p className="text-gray-600">
              Discover events happening near you
            </p>
          </div>
          <div className="bg-white p-6 rounded-lg shadow-md">
            <Users className="w-12 h-12 text-blue-600 mb-4" />
            <h3 className="text-xl font-semibold mb-2">Vendor Portal</h3>
            <p className="text-gray-600">
              Create and manage your events with ease
            </p>
          </div>
        </div>
      </div>
    </div>
  )
}
