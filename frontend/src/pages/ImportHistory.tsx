import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowLeft, History } from 'lucide-react'
import { importApi } from '../api/import'

const statusBadge: Record<string, string> = {
  success: 'bg-green-100 text-green-700',
  partial: 'bg-amber-100 text-amber-700',
  failed: 'bg-red-100 text-red-700',
}

export default function ImportHistory() {
  const { data: runs, isLoading } = useQuery({ queryKey: ['import-runs'], queryFn: () => importApi.runs(50) })

  return (
    <div className="max-w-5xl mx-auto">
      <div className="mb-6">
        <Link to="/import" className="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-1"><ArrowLeft size={14} /> Import</Link>
        <h1 className="text-2xl font-bold text-gray-900">Import run history</h1>
        <p className="text-sm text-gray-500 mt-1">Results of manual and scheduled imports.</p>
      </div>

      {isLoading ? (
        <div className="text-sm text-gray-500">Loading…</div>
      ) : !runs || runs.length === 0 ? (
        <div className="bg-white border border-dashed border-gray-300 rounded-xl p-12 text-center">
          <History size={28} className="mx-auto text-gray-400 mb-3" />
          <h2 className="font-semibold text-gray-800">No imports have run yet</h2>
          <p className="text-sm text-gray-500 mt-1">Run an import from its settings page, or schedule one.</p>
        </div>
      ) : (
        <div className="bg-white border border-gray-200 rounded-xl overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-gray-500">
              <tr>
                <th className="text-left font-medium px-4 py-3">When</th>
                <th className="text-left font-medium px-4 py-3">Import</th>
                <th className="text-left font-medium px-4 py-3">Supplier</th>
                <th className="text-left font-medium px-4 py-3">Status</th>
                <th className="text-left font-medium px-4 py-3">Rows</th>
                <th className="text-left font-medium px-4 py-3">Created</th>
                <th className="text-left font-medium px-4 py-3">Updated</th>
                <th className="text-left font-medium px-4 py-3">Matched</th>
                <th className="text-left font-medium px-4 py-3">Failed</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {runs.map((r) => (
                <tr key={r.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-gray-500 text-xs">{new Date(r.startedAt).toLocaleString()}</td>
                  <td className="px-4 py-3 text-gray-800">{r.template?.name ?? '—'}</td>
                  <td className="px-4 py-3 text-gray-600">{r.supplier?.name ?? '—'}</td>
                  <td className="px-4 py-3"><span className={`text-xs px-2 py-0.5 rounded-full ${statusBadge[r.status] ?? ''}`}>{r.status}</span></td>
                  <td className="px-4 py-3 text-gray-600">{r.rowsTotal}</td>
                  <td className="px-4 py-3 text-gray-600">{r.rowsCreated}</td>
                  <td className="px-4 py-3 text-gray-600">{r.rowsUpdated}</td>
                  <td className="px-4 py-3 text-gray-600">{r.rowsMatched}</td>
                  <td className="px-4 py-3 text-gray-600">{r.rowsFailed}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
