'use client'

import { useEffect, useState } from 'react'
import { useRouter, usePathname } from 'next/navigation'
import Link from 'next/link'
import { Shield, Users, Calendar, BarChart3, FileText, AlertTriangle, Bell, LogOut } from "lucide-react"
import { useAuthStore } from "@/lib/store"
import { authAPI } from "@/lib/api"

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter()
  const pathname = usePathname()
  const { user, token, setUser, logout } = useAuthStore()
  const [checking, setChecking] = useState(true)

  useEffect(() => {
    if (pathname === '/admin') {
      setChecking(false)
      return
    }

    const verify = async () => {
      if (!token) {
        router.push('/admin')
        return
      }
      if (!user) {
        try {
          const res = await authAPI.me()
          if (res.data.data?.type === 'admin') {
            setUser(res.data.data)
          } else {
            logout()
            router.push('/admin')
            return
          }
        } catch {
          logout()
          router.push('/admin')
          return
        }
      } else if (user.type !== 'admin') {
        logout()
        router.push('/admin')
        return
      }
      setChecking(false)
    }
    verify()
  }, [token, user, pathname, router, setUser, logout])

  if (pathname === '/admin') {
    return <>{children}</>
  }

  if (checking) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-gray-500">Verifying admin session...</div>
      </div>
    )
  }

  const navItems = [
    { href: '/admin/dashboard', label: 'Dashboard', icon: BarChart3 },
    { href: '/admin/vendor', label: 'Vendors', icon: Users },
    { href: '/admin/events', label: 'Events', icon: Calendar },
    { href: '/admin/reports', label: 'Reports', icon: FileText },
    { href: '/admin/disputes', label: 'Disputes', icon: AlertTriangle },
    { href: '/admin/notifications', label: 'Notifications', icon: Bell },
  ]

  const handleLogout = () => {
    logout()
    router.push('/admin')
  }

  return (
    <div className="min-h-screen bg-gray-50">

      <div className="bg-white border-b shadow-sm">
        <div className="container mx-auto px-4">
          <div className="flex items-center justify-between h-14">
            <div className="flex items-center gap-6">
              <Link href="/admin/dashboard" className="flex items-center gap-2 font-bold text-red-600">
                <Shield className="w-5 h-5" />
                Admin Panel
              </Link>
              <nav className="flex gap-1">
                {navItems.map((item) => {
                  const Icon = item.icon
                  const isActive = pathname === item.href || pathname.startsWith(item.href + '/')
                  return (
                    <Link
                      key={item.href}
                      href={item.href}
                      className={`flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                        isActive
                          ? 'bg-red-50 text-red-700'
                          : 'text-gray-600 hover:bg-gray-100'
                      }`}
                    >
                      <Icon className="w-4 h-4" />
                      {item.label}
                    </Link>
                  )
                })}
              </nav>
            </div>
            <button onClick={handleLogout} className="flex items-center gap-1 text-sm text-gray-500 hover:text-red-600 transition-colors">
              <LogOut className="w-4 h-4" />
              Logout
            </button>
          </div>
        </div>
      </div>

      <div className="container mx-auto px-4 py-6">
        {children}
      </div>
    </div>
  )
}
