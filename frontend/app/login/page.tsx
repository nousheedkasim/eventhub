'use client'

import { useState } from 'react'
import { Button } from "@/components/ui/button"
import { authAPI } from "@/lib/api"
import { useAuthStore } from "@/lib/store"
import Link from "next/link"
import { useRouter } from "next/navigation"

export default function LoginPage() {
  const router = useRouter()
  const { setAuth } = useAuthStore()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [isRegister, setIsRegister] = useState(false)
  const [name, setName] = useState('')
  const [error, setError] = useState('')

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setError('')

    try {
      if (isRegister) {
        const response = await authAPI.register({ name, email, password })
        setAuth(response.data.data.user, response.data.data.token)
      } else {
        const response = await authAPI.login({ email, password })
        setAuth(response.data.data.user, response.data.data.token)
      }
      router.push('/')
    } catch (err: any) {
      const data = err?.response?.data
      if (data?.errors) {
        const firstKey = Object.keys(data.errors)[0]
        setError(Array.isArray(data.errors[firstKey]) ? data.errors[firstKey][0] : data.errors[firstKey])
      } else {
        setError(data?.message || 'Something went wrong. Please try again.')
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center pt-20">
      <div className="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 className="text-2xl font-bold mb-6 text-center">
          {isRegister ? 'Create Account' : 'Login'}
        </h1>

          <form onSubmit={handleSubmit} className="space-y-4">
            {error && (
              <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md text-sm">
                {error}
              </div>
            )}

            {isRegister && (
              <div>
                <label className="block text-sm font-medium mb-1">Name</label>
                <input
                  type="text"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  className="w-full px-3 py-2 border rounded-md"
                  required
                />
              </div>
            )}
            
            <div>
              <label className="block text-sm font-medium mb-1">Email</label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full px-3 py-2 border rounded-md"
                required
              />
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">Password</label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full px-3 py-2 border rounded-md"
                required
              />
            </div>

            <Button type="submit" className="w-full" disabled={loading}>
              {loading ? 'Processing...' : (isRegister ? 'Register' : 'Login')}
            </Button>
          </form>

          <div className="mt-4 text-center text-sm">
            {isRegister ? (
              <>
                Already have an account?{' '}
                <button
                  type="button"
                  onClick={() => { setIsRegister(false); setError('') }}
                  className="text-blue-600 hover:underline"
                >
                  Login
                </button>
              </>
            ) : (
              <>
                Don&apos;t have an account?{' '}
                <button
                  type="button"
                  onClick={() => { setIsRegister(true); setError('') }}
                  className="text-blue-600 hover:underline"
                >
                  Register
                </button>
              </>
            )}
          </div>

          <div className="mt-4 text-center">
            <Link href="/">
              <Button variant="outline" size="sm">Back to Home</Button>
            </Link>
          </div>
        </div>
      </div>
  )
}
