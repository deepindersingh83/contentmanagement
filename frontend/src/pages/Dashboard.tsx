import { Link } from 'react-router-dom'
import { useAuthStore } from '../store/authStore'
import { UserCog, ShieldCheck, Upload } from 'lucide-react'

export default function Dashboard() {
  const { user } = useAuthStore()

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">
          Welcome{user?.name ? `, ${user.name}` : ''}
        </h1>
        <p className="text-sm text-gray-500 mt-1">Content Management dashboard.</p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Link
          to="/import"
          className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:border-indigo-300 hover:shadow transition"
        >
          <div className="inline-flex items-center justify-center w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg mb-3">
            <Upload size={20} />
          </div>
          <h2 className="font-semibold text-gray-800">Import products</h2>
          <p className="text-sm text-gray-500 mt-1">Bring in products from your suppliers, one feed at a time.</p>
        </Link>

        <Link
          to="/profile"
          className="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:border-indigo-300 hover:shadow transition"
        >
          <div className="inline-flex items-center justify-center w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg mb-3">
            <UserCog size={20} />
          </div>
          <h2 className="font-semibold text-gray-800">Profile settings</h2>
          <p className="text-sm text-gray-500 mt-1">Update your details and change your password.</p>
        </Link>

        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div className="inline-flex items-center justify-center w-10 h-10 bg-green-50 text-green-600 rounded-lg mb-3">
            <ShieldCheck size={20} />
          </div>
          <h2 className="font-semibold text-gray-800">Your access</h2>
          <p className="text-sm text-gray-500 mt-1">
            Roles: {user?.roles?.join(', ') ?? '—'}
          </p>
        </div>
      </div>
    </div>
  )
}
