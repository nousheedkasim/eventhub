'use client'

import { useState, useEffect } from 'react'
import { Button } from "@/components/ui/button"
import { authAPI } from "@/lib/api"
import { useAuthStore } from "@/lib/store"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { ShieldCheck, Building, CreditCard, User, ArrowRight, ArrowLeft } from "lucide-react"

export default function VendorAuthPage() {
  const router = useRouter()
  const { user, token, setAuth, setUser, logout } = useAuthStore()
  const [checkingAuth, setCheckingAuth] = useState(true)
  const [activeTab, setActiveTab] = useState<'login' | 'register'>('login')
  const [registerStep, setRegisterStep] = useState<1 | 2 | 3>(1)
  
  // Form State
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [errorMsg, setErrorMsg] = useState('')

  // Vendor Register Form State
  const [regData, setRegData] = useState({
    name: '',
    email: '',
    password: '',
    confirmPassword: '',
    company_name: '',
    contact_person: '',
    phone: '',
    address: '',
    website: '',
    bank_name: '',
    account_holder_name: '',
    account_number: '',
    iban: '',
    swift_code: ''
  })

  useEffect(() => {
    const checkSession = async () => {
      if (token && !user) {
        try {
          const response = await authAPI.me()
          const currentUser = response.data.data
          if (currentUser && currentUser.type === 'vendor') {
            setUser(currentUser)
            router.push('/vendor/dashboard')
            return
          } else {
            // Log out if not a vendor
            logout()
          }
        } catch (error) {
          console.error("Failed to restore vendor session:", error)
          logout()
        }
      } else if (token && user?.type === 'vendor') {
        router.push('/vendor/dashboard')
        return
      }
      setCheckingAuth(false)
    }

    checkSession()
  }, [token, user, router, setUser, logout])

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target
    setRegData(prev => ({
      ...prev,
      [name]: value
    }))
  }

  const handleLoginSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setLoading(true)
    setErrorMsg('')

    try {
      const response = await authAPI.login({ email, password })
      const loggedUser = response.data.data.user
      const loggedToken = response.data.data.token

      if (loggedUser.type !== 'vendor') {
        setErrorMsg('Access denied. This portal is only for vendor accounts.')
        logout()
      } else {
        setAuth(loggedUser, loggedToken)
        router.push('/vendor/dashboard')
      }
    } catch (error: any) {
      console.error('Login failed:', error)
      setErrorMsg(error.response?.data?.message || 'Invalid email or password. Please try again.')
    } finally {
      setLoading(false)
    }
  }

  const handleRegisterSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    
    if (regData.password !== regData.confirmPassword) {
      setErrorMsg('Passwords do not match.')
      return
    }

    setLoading(true)
    setErrorMsg('')

    try {
      const payload = {
        name: regData.name,
        email: regData.email,
        password: regData.password,
        type: 'vendor',
        company_name: regData.company_name,
        contact_person: regData.contact_person,
        phone: regData.phone || null,
        address: regData.address || null,
        website: regData.website || null,
        bank_name: regData.bank_name || null,
        account_holder_name: regData.account_holder_name || null,
        account_number: regData.account_number || null,
        iban: regData.iban || null,
        swift_code: regData.swift_code || null
      }

      const response = await authAPI.register(payload)
      const createdUser = response.data.data.user
      const createdToken = response.data.data.token

      setAuth(createdUser, createdToken)
      router.push('/vendor/dashboard')
    } catch (error: any) {
      console.error('Registration failed:', error)
      setErrorMsg(error.response?.data?.message || 'Registration failed. Please check your inputs.')
    } finally {
      setLoading(false)
    }
  }

  if (checkingAuth) {
    return (
      <div className="min-h-screen bg-gradient-to-tr from-slate-900 via-indigo-950 to-slate-900 flex items-center justify-center">
        <div className="text-white text-lg">Checking session...</div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gradient-to-tr from-slate-900 via-indigo-950 to-slate-900 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div className="bg-white/10 backdrop-blur-md border border-white/20 p-8 rounded-2xl shadow-2xl w-full max-w-2xl text-white">
        
        {/* Header */}
        <div className="text-center mb-8">
          <div className="inline-flex p-3 rounded-full bg-indigo-500/20 border border-indigo-500/30 text-indigo-400 mb-3">
            <Building className="w-8 h-8" />
          </div>
          <h1 className="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-indigo-200 via-purple-200 to-pink-200 bg-clip-text text-transparent">
            Vendor Portal
          </h1>
          <p className="text-slate-300 mt-2 text-sm">
            Create, manage and monetize your events easily
          </p>
        </div>

        {/* Tab Buttons */}
        <div className="flex border-b border-white/10 mb-8 p-1 bg-white/5 rounded-xl">
          <button
            type="button"
            onClick={() => {
              setActiveTab('login')
              setErrorMsg('')
            }}
            className={`flex-1 py-3 text-sm font-semibold rounded-lg transition-all ${
              activeTab === 'login'
                ? 'bg-indigo-600 text-white shadow-md'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            Login
          </button>
          <button
            type="button"
            onClick={() => {
              setActiveTab('register')
              setErrorMsg('')
            }}
            className={`flex-1 py-3 text-sm font-semibold rounded-lg transition-all ${
              activeTab === 'register'
                ? 'bg-indigo-600 text-white shadow-md'
                : 'text-slate-400 hover:text-white'
            }`}
          >
            Create Account
          </button>
        </div>

        {errorMsg && (
          <div className="bg-rose-500/20 border border-rose-500/30 text-rose-300 p-4 rounded-xl mb-6 text-sm">
            {errorMsg}
          </div>
        )}

        {/* Login Form */}
        {activeTab === 'login' && (
          <form onSubmit={handleLoginSubmit} className="space-y-6">
            <div>
              <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Email Address</label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-all text-sm"
                placeholder="you@company.com"
                required
              />
            </div>

            <div>
              <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Password</label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-all text-sm"
                placeholder="••••••••"
                required
              />
            </div>

            <Button type="submit" className="w-full py-6 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm" disabled={loading}>
              {loading ? 'Logging in...' : 'Sign In'}
            </Button>
          </form>
        )}

        {/* Register Step Form */}
        {activeTab === 'register' && (
          <form onSubmit={handleRegisterSubmit} className="space-y-6">
            {/* Step Indicators */}
            <div className="flex items-center justify-between text-xs text-slate-400 mb-4 px-2">
              <span className={`flex items-center gap-1 ${registerStep >= 1 ? 'text-indigo-400 font-bold' : ''}`}>
                <User className="w-4 h-4" /> Account Info
              </span>
              <div className="h-px bg-white/10 flex-1 mx-4"></div>
              <span className={`flex items-center gap-1 ${registerStep >= 2 ? 'text-indigo-400 font-bold' : ''}`}>
                <Building className="w-4 h-4" /> Business Profile
              </span>
              <div className="h-px bg-white/10 flex-1 mx-4"></div>
              <span className={`flex items-center gap-1 ${registerStep === 3 ? 'text-indigo-400 font-bold' : ''}`}>
                <CreditCard className="w-4 h-4" /> Payout Info
              </span>
            </div>

            {/* STEP 1: Account Info */}
            {registerStep === 1 && (
              <div className="space-y-6">
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Full Name</label>
                  <input
                    type="text"
                    name="name"
                    value={regData.name}
                    onChange={handleInputChange}
                    className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-indigo-500 text-sm"
                    placeholder="John Doe"
                    required
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Email Address</label>
                  <input
                    type="email"
                    name="email"
                    value={regData.email}
                    onChange={handleInputChange}
                    className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-indigo-500 text-sm"
                    placeholder="john@example.com"
                    required
                  />
                </div>
                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Password</label>
                    <input
                      type="password"
                      name="password"
                      value={regData.password}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-indigo-500 text-sm"
                      placeholder="••••••••"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Confirm Password</label>
                    <input
                      type="password"
                      name="confirmPassword"
                      value={regData.confirmPassword}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-indigo-500 text-sm"
                      placeholder="••••••••"
                      required
                    />
                  </div>
                </div>
                
                <div className="flex justify-end pt-4">
                  <Button
                    type="button"
                    onClick={() => {
                      if (regData.name && regData.email && regData.password && regData.confirmPassword) {
                        setRegisterStep(2)
                        setErrorMsg('')
                      } else {
                        setErrorMsg('Please fill in all account fields.')
                      }
                    }}
                    className="px-6 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm flex items-center gap-2"
                  >
                    Next <ArrowRight className="w-4 h-4" />
                  </Button>
                </div>
              </div>
            )}

            {/* STEP 2: Business Profile */}
            {registerStep === 2 && (
              <div className="space-y-6">
                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Company Name</label>
                    <input
                      type="text"
                      name="company_name"
                      value={regData.company_name}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-indigo-500 text-sm"
                      placeholder="Acme Corporation"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Contact Person</label>
                    <input
                      type="text"
                      name="contact_person"
                      value={regData.contact_person}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-indigo-500 text-sm"
                      placeholder="Manager Name"
                      required
                    />
                  </div>
                </div>

                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Phone Number</label>
                    <input
                      type="text"
                      name="phone"
                      value={regData.phone}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-indigo-500 text-sm"
                      placeholder="+971 50 123 4567"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Website</label>
                    <input
                      type="text"
                      name="website"
                      value={regData.website}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-indigo-500 text-sm"
                      placeholder="https://acme.com"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Business Address</label>
                  <textarea
                    name="address"
                    value={regData.address}
                    onChange={handleInputChange}
                    rows={3}
                    className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-indigo-500 text-sm resize-none"
                    placeholder="Full business address details"
                  />
                </div>

                <div className="flex justify-between pt-4">
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => setRegisterStep(1)}
                    className="px-6 border-white/10 hover:bg-white/5 text-white font-semibold rounded-xl text-sm flex items-center gap-2"
                  >
                    <ArrowLeft className="w-4 h-4" /> Back
                  </Button>
                  <Button
                    type="button"
                    onClick={() => {
                      if (regData.company_name && regData.contact_person) {
                        setRegisterStep(3)
                        setErrorMsg('')
                      } else {
                        setErrorMsg('Company Name and Contact Person are required.')
                      }
                    }}
                    className="px-6 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm flex items-center gap-2"
                  >
                    Next <ArrowRight className="w-4 h-4" />
                  </Button>
                </div>
              </div>
            )}

            {/* STEP 3: Payout Info */}
            {registerStep === 3 && (
              <div className="space-y-6">
                <div className="bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 p-4 rounded-xl text-xs flex gap-2">
                  <ShieldCheck className="w-5 h-5 flex-shrink-0" />
                  <div>
                    <span className="font-semibold">KYC Verification Info</span>: Your business registration will be in <span className="underline">pending</span> status until our team reviews your profile. You can proceed with setup.
                  </div>
                </div>

                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Bank Name</label>
                    <input
                      type="text"
                      name="bank_name"
                      value={regData.bank_name}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-indigo-500 text-sm"
                      placeholder="Emirates NBD"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Account Holder Name</label>
                    <input
                      type="text"
                      name="account_holder_name"
                      value={regData.account_holder_name}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-indigo-500 text-sm"
                      placeholder="Acme Corp LLC"
                    />
                  </div>
                </div>

                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">Account Number</label>
                    <input
                      type="text"
                      name="account_number"
                      value={regData.account_number}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-indigo-500 text-sm"
                      placeholder="10100029302"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">SWIFT / BIC Code</label>
                    <input
                      type="text"
                      name="swift_code"
                      value={regData.swift_code}
                      onChange={handleInputChange}
                      className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-indigo-500 text-sm"
                      placeholder="EBILAEADXXX"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">IBAN</label>
                  <input
                    type="text"
                    name="iban"
                    value={regData.iban}
                    onChange={handleInputChange}
                    className="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white focus:outline-none focus:border-indigo-500 text-sm"
                    placeholder="AE430260000000000123456"
                  />
                </div>

                <div className="flex justify-between pt-4">
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => setRegisterStep(2)}
                    className="px-6 border-white/10 hover:bg-white/5 text-white font-semibold rounded-xl text-sm flex items-center gap-2"
                  >
                    <ArrowLeft className="w-4 h-4" /> Back
                  </Button>
                  <Button
                    type="submit"
                    className="px-6 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm"
                    disabled={loading}
                  >
                    {loading ? 'Creating Account...' : 'Complete Signup'}
                  </Button>
                </div>
              </div>
            )}
          </form>
        )}

        <div className="mt-8 pt-6 border-t border-white/10 text-center">
          <Link href="/">
            <Button variant="ghost" className="text-slate-400 hover:text-white text-xs gap-1">
              <ArrowLeft className="w-3.5 h-3.5" /> Back to EventHub Home
            </Button>
          </Link>
        </div>

      </div>
    </div>
  )
}
