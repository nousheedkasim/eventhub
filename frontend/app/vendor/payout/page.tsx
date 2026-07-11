'use client'

import { useState, useEffect } from 'react'
import { Button } from "@/components/ui/button"
import { payoutsAPI, ordersAPI, authAPI } from "@/lib/api"
import { useAuthStore } from "@/lib/store"
import { formatCurrency } from "@/lib/utils"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { ArrowLeft, DollarSign, CreditCard, ShieldAlert, CheckCircle, Clock } from "lucide-react"

export default function PayoutsPage() {
  const router = useRouter()
  const { user, token, setUser, logout } = useAuthStore()
  const [authLoading, setAuthLoading] = useState(true)

  // Data State
  const [orders, setOrders] = useState<any[]>([])
  const [payouts, setPayouts] = useState<any[]>([])
  const [loading, setLoading] = useState(true)
  const [requesting, setRequesting] = useState(false)
  const [successMsg, setSuccessMsg] = useState('')
  const [errorMsg, setErrorMsg] = useState('')

  // Metrics
  const [metrics, setMetrics] = useState({
    grossEarnings: 0,
    commissionDeducted: 0,
    netEarnings: 0,
    totalPaidOut: 0,
    availableBalance: 0,
  })

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
    if (!authLoading && user?.vendor?.id) {
      loadData()
    }
  }, [authLoading, user])

  const loadData = async () => {
    setLoading(true)
    try {
      const vendorId = user?.vendor?.id
      if (!vendorId) {
        setErrorMsg("Vendor information not found.")
        setLoading(false)
        return
      }
      const [ordersRes, payoutsRes] = await Promise.all([
        ordersAPI.getAll({ vendor_id: vendorId }),
        payoutsAPI.getAll({ vendor_id: vendorId }),
      ])

      const ordersRaw = ordersRes.data.data
      const fetchedOrders = Array.isArray(ordersRaw) ? ordersRaw : (ordersRaw?.data || [])
      const payoutsRaw = payoutsRes.data.data
      const fetchedPayouts = Array.isArray(payoutsRaw) ? payoutsRaw : (payoutsRaw?.data || [])

      setOrders(fetchedOrders)
      setPayouts(fetchedPayouts)

      // Calculate Metrics
      // 1. Gross Earnings: sum of all completed/paid orders for this vendor's events
      const gross = fetchedOrders
        .filter((o: any) => o.status === 'paid' || o.status === 'completed')
        .reduce((sum: number, o: any) => sum + parseFloat(o.total_amount || 0), 0)

      // 2. Commission: 10% standard commission rate
      const commission = gross * 0.10
      const net = gross - commission

      // 3. Total Paid Out: sum of payouts requested in 'paid' status
      const paidOut = fetchedPayouts
        .filter((p: any) => p.status === 'paid')
        .reduce((sum: number, p: any) => sum + parseFloat(p.amount || 0), 0)

      // 4. Sum of all payouts requested (pending or paid)
      const requestedGross = fetchedPayouts
        .filter((p: any) => p.status !== 'failed')
        .reduce((sum: number, p: any) => sum + parseFloat(p.gross_amount || 0), 0)

      // 5. Available balance
      const available = Math.max(0, gross - requestedGross)

      setMetrics({
        grossEarnings: gross,
        commissionDeducted: commission,
        netEarnings: net,
        totalPaidOut: paidOut,
        availableBalance: available,
      })

    } catch (error) {
      console.error("Failed to load payout data:", error)
      setErrorMsg("Failed to load payout details.")
    } finally {
      setLoading(false)
    }
  }

  const handleRequestPayout = async () => {
    if (metrics.availableBalance < 50) {
      setErrorMsg("Available balance must be at least $50.00 to request a payout.")
      return
    }

    const vendorId = user?.vendor?.id
    if (!vendorId) {
      setErrorMsg("Vendor information not found.")
      return
    }

    setRequesting(true)
    setErrorMsg('')
    setSuccessMsg('')

    try {
      const grossAmount = metrics.availableBalance
      const commission = grossAmount * 0.10
      const amount = grossAmount - commission

      await payoutsAPI.create({
        vendor_id: vendorId,
        gross_amount: grossAmount,
        commission: commission,
        amount: amount,
        status: 'pending',
      })

      setSuccessMsg("Your payout request was submitted successfully! It will be reviewed by our finance team.")
      await loadData()
    } catch (error: any) {
      console.error("Payout request failed:", error)
      setErrorMsg(error.response?.data?.message || "Failed to submit payout request. Please try again.")
    } finally {
      setRequesting(false)
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
      <div className="max-w-5xl mx-auto">
        
        {/* Header */}
        <div className="flex items-center gap-3 mb-8">
          <Link href="/vendor/dashboard">
            <Button variant="outline" size="sm">
              <ArrowLeft className="w-4 h-4" />
            </Button>
          </Link>
          <h1 className="text-3xl font-bold tracking-tight text-gray-900">Payouts Dashboard</h1>
        </div>

        {errorMsg && (
          <div className="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl mb-6 text-sm">
            {errorMsg}
          </div>
        )}

        {successMsg && (
          <div className="bg-green-50 border border-green-200 text-green-700 p-4 rounded-xl mb-6 text-sm flex items-center gap-2">
            <CheckCircle className="w-5 h-5 flex-shrink-0" />
            {successMsg}
          </div>
        )}

        {loading ? (
          <div className="text-center py-12 text-gray-500">Loading payout details...</div>
        ) : (
          <div className="space-y-8">
            {/* Metrics Row */}
            <div className="grid md:grid-cols-4 gap-6">
              <div className="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div className="flex items-center justify-between mb-4">
                  <span className="text-gray-500 text-sm font-medium">Gross Earnings</span>
                  <DollarSign className="w-5 h-5 text-indigo-600" />
                </div>
                <div className="text-2xl font-bold text-gray-900">{formatCurrency(metrics.grossEarnings)}</div>
                <p className="text-xs text-gray-400 mt-1">Total revenue from ticket sales</p>
              </div>

              <div className="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div className="flex items-center justify-between mb-4">
                  <span className="text-gray-500 text-sm font-medium">Fee Deductions</span>
                  <span className="text-rose-500 text-xs font-semibold">10% Platform fee</span>
                </div>
                <div className="text-2xl font-bold text-gray-900">{formatCurrency(metrics.commissionDeducted)}</div>
                <p className="text-xs text-gray-400 mt-1">Commission paid to EventHub</p>
              </div>

              <div className="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div className="flex items-center justify-between mb-4">
                  <span className="text-gray-500 text-sm font-medium">Total Paid Out</span>
                  <CreditCard className="w-5 h-5 text-green-600" />
                </div>
                <div className="text-2xl font-bold text-gray-900">{formatCurrency(metrics.totalPaidOut)}</div>
                <p className="text-xs text-gray-400 mt-1">Transferred to your bank account</p>
              </div>

              <div className="bg-white p-6 rounded-2xl shadow-sm border border-indigo-100 bg-indigo-50/10">
                <div className="flex items-center justify-between mb-4">
                  <span className="text-indigo-950 text-sm font-semibold">Available Balance</span>
                  <DollarSign className="w-5 h-5 text-indigo-600" />
                </div>
                <div className="text-2xl font-bold text-indigo-950">{formatCurrency(metrics.availableBalance * 0.90)}</div>
                <p className="text-xs text-indigo-500 mt-1">Net amount available for payout</p>
              </div>
            </div>

            {/* Payout Request Card */}
            <div className="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
              <div className="space-y-2">
                <h2 className="text-lg font-bold text-gray-800">Request Earnings Settlement</h2>
                <p className="text-sm text-gray-500 max-w-xl">
                  Submit a payout request to transfer your net earnings of <span className="font-bold text-gray-700">{formatCurrency(metrics.availableBalance * 0.90)}</span> directly to your registered bank account {user.vendor?.bank_name && `(${user.vendor.bank_name})`}.
                </p>
                {metrics.availableBalance * 0.90 < 50 && (
                  <div className="flex items-center gap-1.5 text-xs text-amber-600 bg-amber-50 px-2.5 py-1.5 rounded-lg border border-amber-200 w-fit">
                    <ShieldAlert className="w-4 h-4 flex-shrink-0" />
                    <span>Minimum threshold for payout is $50.00</span>
                  </div>
                )}
              </div>

              <Button
                onClick={handleRequestPayout}
                className="px-6 py-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold flex-shrink-0 shadow-sm"
                disabled={requesting || (metrics.availableBalance * 0.90 < 50)}
              >
                {requesting ? "Requesting..." : "Request Payout Now"}
              </Button>
            </div>

            {/* Payout History Table */}
            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
              <div className="p-6 border-b border-gray-100">
                <h2 className="text-lg font-bold text-gray-800">Payout Settlement History</h2>
              </div>

              {payouts.length === 0 ? (
                <div className="text-center py-12 text-gray-400 text-sm">No payout settlements requested yet.</div>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full text-left text-sm text-gray-500">
                    <thead className="bg-gray-50/50 text-xs text-gray-500 uppercase font-semibold border-b border-gray-100">
                      <tr>
                        <th className="px-6 py-4">Request Date</th>
                        <th className="px-6 py-4">Gross Earnings</th>
                        <th className="px-6 py-4">Commission</th>
                        <th className="px-6 py-4">Net Payout</th>
                        <th className="px-6 py-4">Status</th>
                        <th className="px-6 py-4">Bank Reference</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                      {payouts.map((payout: any) => (
                        <tr key={payout.id} className="hover:bg-gray-50/35 transition-colors">
                          <td className="px-6 py-4 font-medium text-gray-800">
                            {new Date(payout.created_at).toLocaleDateString()}
                          </td>
                          <td className="px-6 py-4">{formatCurrency(payout.gross_amount)}</td>
                          <td className="px-6 py-4 text-rose-500">-{formatCurrency(payout.commission)}</td>
                          <td className="px-6 py-4 font-semibold text-gray-900">{formatCurrency(payout.amount)}</td>
                          <td className="px-6 py-4">
                            {payout.status === 'paid' && (
                              <span className="inline-flex items-center gap-1 bg-green-50 text-green-700 px-2 py-1 rounded-lg text-xs font-semibold border border-green-200">
                                <CheckCircle className="w-3.5 h-3.5" /> Settled
                              </span>
                            )}
                            {payout.status === 'pending' && (
                              <span className="inline-flex items-center gap-1 bg-amber-50 text-amber-700 px-2 py-1 rounded-lg text-xs font-semibold border border-amber-200">
                                <Clock className="w-3.5 h-3.5" /> Pending Review
                              </span>
                            )}
                            {payout.status === 'processing' && (
                              <span className="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-2 py-1 rounded-lg text-xs font-semibold border border-blue-200">
                                <Clock className="w-3.5 h-3.5" /> Processing
                              </span>
                            )}
                            {payout.status === 'failed' && (
                              <span className="inline-flex items-center gap-1 bg-rose-50 text-rose-700 px-2 py-1 rounded-lg text-xs font-semibold border border-rose-200">
                                <ShieldAlert className="w-3.5 h-3.5" /> Failed
                              </span>
                            )}
                          </td>
                          <td className="px-6 py-4 font-mono text-xs text-gray-400">
                            {payout.transfer_reference || 'N/A'}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>

          </div>
        )}
      </div>
    </div>
  )
}
