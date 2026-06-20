import { ReactNode } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuthStore } from '../store/authStore'
import { LayoutGrid, UserCog, LogOut, Upload, Truck } from 'lucide-react'

export default function Layout({ children }: { children: ReactNode }) {
  const { user, logout } = useAuthStore()
  const navigate = useNavigate()
  const location = useLocation()

  const handleLogout = () => {
    logout()
    navigate('/login')
  }

  const navItem = (to: string, label: string, icon: ReactNode) => {
    const active = to === '/' ? location.pathname === '/' : location.pathname.startsWith(to)
    return (
      <Link
        to={to}
        className={`inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition ${
          active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100'
        }`}
      >
        {icon}
        {label}
      </Link>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white border-b border-gray-200">
        <div className="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
          <div className="flex items-center gap-6">
            <Link to="/" className="flex items-center gap-2 font-bold text-gray-900">
              <span className="inline-flex items-center justify-center w-8 h-8 bg-indigo-600 text-white rounded-lg">
                <LayoutGrid size={18} />
              </span>
              Content Management
            </Link>
            <nav className="hidden sm:flex items-center gap-1">
              {navItem('/', 'Dashboard', <LayoutGrid size={16} />)}
              {navItem('/import', 'Import', <Upload size={16} />)}
              {navItem('/suppliers', 'Suppliers', <Truck size={16} />)}
              {navItem('/profile', 'Profile', <UserCog size={16} />)}
            </nav>
          </div>

          <div className="flex items-center gap-3">
            <span className="hidden sm:inline text-sm text-gray-500">{user?.email}</span>
            <button
              onClick={handleLogout}
              className="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg"
            >
              <LogOut size={16} />
              Sign out
            </button>
          </div>
        </div>
      </header>

      <main className="max-w-5xl mx-auto px-4 py-8">{children}</main>
    </div>
  )
}
