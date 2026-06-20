import { Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, FileSpreadsheet, Trash2, Upload, Copy, Download, History } from 'lucide-react'
import { importApi } from '../api/import'

const SCHEDULES = ['manual', 'hourly', 'daily', 'weekly']

const SOURCE_LABELS: Record<string, string> = {
  direct: 'Direct upload',
  url: 'URL',
  ftp: 'FTP',
  sftp: 'sFTP',
}

export default function ImportList() {
  const queryClient = useQueryClient()
  const { data: templates, isLoading } = useQuery({
    queryKey: ['import-templates'],
    queryFn: importApi.list,
  })

  const removeMutation = useMutation({
    mutationFn: importApi.remove,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['import-templates'] }),
  })

  const duplicateMutation = useMutation({
    mutationFn: importApi.duplicate,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['import-templates'] }),
  })

  const scheduleMutation = useMutation({
    mutationFn: ({ id, freq }: { id: number; freq: string }) => importApi.setSchedule(id, freq),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['import-templates'] }),
  })

  return (
    <div className="max-w-5xl mx-auto">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Import</h1>
          <p className="text-sm text-gray-500 mt-1">
            Import products from your suppliers, one feed at a time.
          </p>
        </div>
        <div className="flex items-center gap-3">
          <Link to="/import/history" className="inline-flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50">
            <History size={16} /> Run history
          </Link>
          <Link
            to="/import/new"
            className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700"
          >
            <Plus size={16} /> New import
          </Link>
        </div>
      </div>

      {isLoading ? (
        <div className="text-sm text-gray-500">Loading…</div>
      ) : !templates || templates.length === 0 ? (
        <div className="bg-white border border-dashed border-gray-300 rounded-xl p-12 text-center">
          <Upload size={28} className="mx-auto text-gray-400 mb-3" />
          <h2 className="font-semibold text-gray-800">No imports yet</h2>
          <p className="text-sm text-gray-500 mt-1 mb-4">
            Create your first import to bring in a supplier's products.
          </p>
          <Link
            to="/import/new"
            className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700"
          >
            <Plus size={16} /> New import
          </Link>
        </div>
      ) : (
        <div className="bg-white border border-gray-200 rounded-xl overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-gray-500">
              <tr>
                <th className="text-left font-medium px-4 py-3">Template</th>
                <th className="text-left font-medium px-4 py-3">Supplier</th>
                <th className="text-left font-medium px-4 py-3">Source</th>
                <th className="text-left font-medium px-4 py-3">Format</th>
                <th className="text-left font-medium px-4 py-3">Schedule</th>
                <th className="text-left font-medium px-4 py-3">Last run</th>
                <th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {templates.map((t) => (
                <tr key={t.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3">
                    <Link to={`/import/${t.id}/settings`} className="flex items-center gap-2 font-medium text-gray-800 hover:text-indigo-600">
                      <FileSpreadsheet size={16} className="text-indigo-500" />
                      {t.name}
                    </Link>
                  </td>
                  <td className="px-4 py-3 text-gray-600">{t.supplier ?? '—'}</td>
                  <td className="px-4 py-3 text-gray-600">{SOURCE_LABELS[t.source] ?? t.source}</td>
                  <td className="px-4 py-3 text-gray-600 uppercase">{t.fileFormat}</td>
                  <td className="px-4 py-3">
                    <select
                      value={t.scheduleFrequency}
                      onChange={(e) => scheduleMutation.mutate({ id: t.id, freq: e.target.value })}
                      className="px-2 py-1 border border-gray-300 rounded-md text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                      {SCHEDULES.map((s) => <option key={s} value={s}>{s}</option>)}
                    </select>
                  </td>
                  <td className="px-4 py-3 text-gray-500 text-xs">{t.lastRunAt ? new Date(t.lastRunAt).toLocaleString() : '—'}</td>
                  <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-4 text-sm">
                      <button
                        onClick={() => duplicateMutation.mutate(t.id)}
                        className="inline-flex items-center gap-1 text-gray-500 hover:text-indigo-600"
                      >
                        <Copy size={15} /> Duplicate
                      </button>
                      <button
                        onClick={() => importApi.download(t.id, t.name)}
                        className="inline-flex items-center gap-1 text-gray-500 hover:text-indigo-600"
                      >
                        <Download size={15} /> Download
                      </button>
                      <button
                        onClick={() => {
                          if (confirm(`Delete import "${t.name}"?`)) removeMutation.mutate(t.id)
                        }}
                        className="inline-flex items-center gap-1 text-gray-500 hover:text-red-600"
                      >
                        <Trash2 size={15} /> Delete
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}
