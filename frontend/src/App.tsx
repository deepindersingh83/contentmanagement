import { ReactNode } from 'react'
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom'
import { useAuthStore } from './store/authStore'
import Layout from './components/Layout'
import Login from './pages/Login'
import Dashboard from './pages/Dashboard'
import Profile from './pages/Profile'
import ImportList from './pages/ImportList'
import NewImport from './pages/NewImport'
import ImportSettings from './pages/ImportSettings'
import Suppliers from './pages/Suppliers'

function ProtectedRoute({ children }: { children: ReactNode }) {
  const { isAuthenticated } = useAuthStore()
  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }
  return <Layout>{children}</Layout>
}

// Vite injects BASE_URL from the configured `base` (e.g. "/contentmanagement/frontend/").
// React Router wants a basename without a trailing slash.
const basename = import.meta.env.BASE_URL.replace(/\/$/, '')

export default function App() {
  const { isAuthenticated } = useAuthStore()

  return (
    <Router basename={basename}>
      <Routes>
        <Route
          path="/login"
          element={isAuthenticated ? <Navigate to="/" replace /> : <Login />}
        />
        <Route
          path="/"
          element={
            <ProtectedRoute>
              <Dashboard />
            </ProtectedRoute>
          }
        />
        <Route
          path="/import"
          element={
            <ProtectedRoute>
              <ImportList />
            </ProtectedRoute>
          }
        />
        <Route
          path="/import/new"
          element={
            <ProtectedRoute>
              <NewImport />
            </ProtectedRoute>
          }
        />
        <Route
          path="/import/:id/settings"
          element={
            <ProtectedRoute>
              <ImportSettings />
            </ProtectedRoute>
          }
        />
        <Route
          path="/suppliers"
          element={
            <ProtectedRoute>
              <Suppliers />
            </ProtectedRoute>
          }
        />
        <Route
          path="/profile"
          element={
            <ProtectedRoute>
              <Profile />
            </ProtectedRoute>
          }
        />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </Router>
  )
}
