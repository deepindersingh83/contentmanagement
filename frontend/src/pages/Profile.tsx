import React, { useState } from 'react'
import { useMutation } from '@tanstack/react-query'
import { useAuthStore } from '../store/authStore'
import { authApi } from '../api/auth'
import { Save, KeyRound } from 'lucide-react'

export default function Profile() {
  const { user, setUser } = useAuthStore()

  const [name, setName] = useState(user?.name ?? '')
  const [email, setEmail] = useState(user?.email ?? '')
  const [phone, setPhone] = useState(user?.phone ?? '')
  const [jobTitle, setJobTitle] = useState(user?.jobTitle ?? '')
  const [profileMsg, setProfileMsg] = useState<{ type: 'ok' | 'err'; text: string } | null>(null)

  const [currentPassword, setCurrentPassword] = useState('')
  const [newPassword, setNewPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')
  const [passwordMsg, setPasswordMsg] = useState<{ type: 'ok' | 'err'; text: string } | null>(null)

  const profileMutation = useMutation({
    mutationFn: authApi.updateProfile,
    onSuccess: (updated) => {
      setUser(updated)
      setProfileMsg({ type: 'ok', text: 'Profile saved.' })
    },
    onError: (e: any) => {
      setProfileMsg({
        type: 'err',
        text: e?.response?.data?.message ?? 'Could not save your profile.',
      })
    },
  })

  const passwordMutation = useMutation({
    mutationFn: authApi.changePassword,
    onSuccess: (res) => {
      setPasswordMsg({ type: 'ok', text: res.message })
      setCurrentPassword('')
      setNewPassword('')
      setConfirmPassword('')
    },
    onError: (e: any) => {
      setPasswordMsg({
        type: 'err',
        text: e?.response?.data?.message ?? 'Could not change your password.',
      })
    },
  })

  const handleProfileSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setProfileMsg(null)
    profileMutation.mutate({ name, email, phone, jobTitle })
  }

  const handlePasswordSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setPasswordMsg(null)
    if (newPassword !== confirmPassword) {
      setPasswordMsg({ type: 'err', text: 'New passwords do not match.' })
      return
    }
    passwordMutation.mutate({ currentPassword, newPassword })
  }

  const inputClass =
    'w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm'

  return (
    <div className="max-w-2xl mx-auto space-y-8">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Profile settings</h1>
        <p className="text-sm text-gray-500 mt-1">Manage your account details and password.</p>
      </div>

      {/* Account details */}
      <form
        onSubmit={handleProfileSubmit}
        className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4"
      >
        <h2 className="text-lg font-semibold text-gray-800">Account details</h2>

        {profileMsg && (
          <div
            className={`p-3 rounded-lg text-sm ${
              profileMsg.type === 'ok'
                ? 'bg-green-50 border border-green-200 text-green-700'
                : 'bg-red-50 border border-red-200 text-red-700'
            }`}
          >
            {profileMsg.text}
          </div>
        )}

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Full name</label>
            <input className={inputClass} value={name} onChange={(e) => setName(e.target.value)} required />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Email address</label>
            <input
              type="email"
              className={inputClass}
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Phone</label>
            <input className={inputClass} value={phone ?? ''} onChange={(e) => setPhone(e.target.value)} />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Job title</label>
            <input
              className={inputClass}
              value={jobTitle ?? ''}
              onChange={(e) => setJobTitle(e.target.value)}
            />
          </div>
        </div>

        <div className="flex justify-end">
          <button
            type="submit"
            disabled={profileMutation.isPending}
            className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-60"
          >
            <Save size={16} />
            {profileMutation.isPending ? 'Saving...' : 'Save changes'}
          </button>
        </div>
      </form>

      {/* Password */}
      <form
        onSubmit={handlePasswordSubmit}
        className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4"
      >
        <h2 className="text-lg font-semibold text-gray-800">Change password</h2>

        {passwordMsg && (
          <div
            className={`p-3 rounded-lg text-sm ${
              passwordMsg.type === 'ok'
                ? 'bg-green-50 border border-green-200 text-green-700'
                : 'bg-red-50 border border-red-200 text-red-700'
            }`}
          >
            {passwordMsg.text}
          </div>
        )}

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Current password</label>
          <input
            type="password"
            className={inputClass}
            value={currentPassword}
            onChange={(e) => setCurrentPassword(e.target.value)}
            autoComplete="current-password"
            required
          />
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">New password</label>
            <input
              type="password"
              className={inputClass}
              value={newPassword}
              onChange={(e) => setNewPassword(e.target.value)}
              autoComplete="new-password"
              minLength={8}
              required
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Confirm new password</label>
            <input
              type="password"
              className={inputClass}
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              autoComplete="new-password"
              minLength={8}
              required
            />
          </div>
        </div>
        <p className="text-xs text-gray-400">Password must be at least 8 characters long.</p>

        <div className="flex justify-end">
          <button
            type="submit"
            disabled={passwordMutation.isPending}
            className="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-900 disabled:opacity-60"
          >
            <KeyRound size={16} />
            {passwordMutation.isPending ? 'Updating...' : 'Update password'}
          </button>
        </div>
      </form>
    </div>
  )
}
