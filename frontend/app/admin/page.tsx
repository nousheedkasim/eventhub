'use client'

import { useState, useEffect } from 'react'
import { Button } from "@/components/ui/button"
import { authAPI } from "@/lib/api"
import { useAuthStore } from "@/lib/store"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { Shield, ArrowLeft } from "lucide-react"

export default function AdminPage() {
  const router = useRouter()
  const { user, token, setAuth, setUser, logout } = useAuthStore()
  const [checkingAuth, setCheckingAuth] = useState(true)
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [errorMsg, setErrorMsg] = useState('')

  useEffect(() => {
    const checkSession = async () => {
      if (token && !user) {
        try {
          const response = await authAPI.me()
          const currentUser = response.data.data
          if (currentUser && currentUser.type === 'admin') {
            setUser(currentUser)
            router.push('/admin/dashboard')
            return
          } else {
            logout()
          }
        } catch (error) {
          logout()
        }
      } else if (token && user?.type === 'admin') {
        router.push('/admin/dashboard')
        return
      }
      setCheckingAuth(false)
    }

    checkSession()
  }, [token, user, router, setUser, logout])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setErrorMsg('')

    try {
      const response = await authAPI.login({ email, password })
      const loggedUser = response.data.data.user
      const loggedToken = response.data.data.token

      if (loggedUser.type !== 'admin') {
        setErrorMsg('Access denied. This portal is for admin accounts only.')
        logout()
      } else {
        setAuth(loggedUser, loggedToken)
        router.push('/admin/dashboard')
      }
    } catch (error: any) {
      setErrorMsg(error.response?.data?.message || 'Invalid credentials.')
    } finally {
      setLoading(false)
    }
  }

  if (checkingAuth) {
    return (
      <div className="min-h-screen bg-gray-900 flex items-center justify-center">
        <div className="text-white">Checking session...</div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-900 flex items-center justify-center py-12 px-4">
      <div className="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <div className="text-center mb-8">
          <div className="inline-flex p-3 rounded-full bg-red-100 text-red-600 mb-3">
            <Shield className="w-8 h-8" />
          </div>
          <h1 className="text-2xl font-bold text-gray-900">Admin Portal</h1>
          <p className="text-gray-500 text-sm mt-1">Platform administration access</p>
        </div>

        {errorMsg && (
          <div className="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg mb-4 text-sm">
            {errorMsg}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
              placeholder="admin@example.com"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
              placeholder="••••••••"
              required
            />
          </div>

          <Button type="submit" className="w-full bg-red-600 hover:bg-red-700" disabled={loading}>
            {loading ? 'Signing in...' : 'Sign In'}
          </Button>
        </form>

        <div className="mt-6 text-center">
          <Link href="/">
            <Button variant="ghost" size="sm" className="text-gray-500 gap-1">
              <ArrowLeft className="w-4 h-4" /> Back to Home
            </Button>
          </Link>
        </div>
      </div>
    </div>
  )
}
