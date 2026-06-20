import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Warehouse as WarehouseIcon, Trash2, Pencil, X, ArrowLeft } from 'lucide-react'
import { warehousesApi, type Warehouse, type WarehousePayload } from '../api/warehouses'
import { suppliersApi } from '../api/suppliers'

const empty: WarehousePayload = { name: '', code: '', country: '', region: '', postcode: '', supplierId: null }

export default function Warehouses() {
  const queryClient = useQueryClient()
  const { data: warehouses, isLoading } = useQuery({ queryKey: ['warehouses'], queryFn: warehousesApi.list })
  const { data: suppliers } = useQuery({ queryKey: ['suppliers'], queryFn: suppliersApi.list })

  const [editing, setEditing] = useState<Warehouse | null>(null)
  const [form, setForm] = useState<WarehousePayload | null>(null)
  const [error, setError] = useState('')

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['warehouses'] })
  const saveMutation = useMutation({
    mutationFn: (p: WarehousePayload) => (editing ? warehousesApi.update(editing.id, p) : warehousesApi.create(p)),
    onSuccess: () => { invalidate(); close() },
    onError: (e: any) => setError(e?.response?.data?.message ?? 'Could not save warehouse.'),
  })
  const removeMutation = useMutation({ mutationFn: warehousesApi.remove, onSuccess: invalidate })

  const openCreate = () => { setEditing(null); setForm({ ...empty }); setError('') }
  const openEdit = (w: Warehouse) => {
    setEditing(w)
    setForm({ name: w.name, code: w.code, country: w.country ?? '', region: w.region ?? '', postcode: w.postcode ?? '', supplierId: w.supplier?.id ?? null })
    setError('')
  }
  const close = () => { setForm(null); setEditing(null) }
  const set = (k: keyof WarehousePayload, v: any) => setForm((f) => (f ? { ...f, [k]: v } : f))

  const input = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500'

  return (
    <div className="max-w-5xl mx-auto">
      <div className="flex items-center justify-between mb-6">
        <div>
          <Link to="/suppliers" className="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-1"><ArrowLeft size={14} /> Suppliers</Link>
          <h1 className="text-2xl font-bold text-gray-900">Warehouses</h1>
          <p className="text-sm text-gray-500 mt-1">Stock locations — a supplier's DC or your own.</p>
        </div>
        <button onClick={openCreate} className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
          <Plus size={16} /> Add warehouse
        </button>
      </div>

      {isLoading ? (
        <div className="text-sm text-gray-500">Loading…</div>
      ) : !warehouses || warehouses.length === 0 ? (
        <div className="bg-white border border-dashed border-gray-300 rounded-xl p-12 text-center">
          <WarehouseIcon size={28} className="mx-auto text-gray-400 mb-3" />
          <h2 className="font-semibold text-gray-800">No warehouses yet</h2>
          <p className="text-sm text-gray-500 mt-1 mb-4">Add one to track per-location stock for suppliers that provide it.</p>
          <button onClick={openCreate} className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700"><Plus size={16} /> Add warehouse</button>
        </div>
      ) : (
        <div className="bg-white border border-gray-200 rounded-xl overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-gray-500">
              <tr>
                <th className="text-left font-medium px-4 py-3">Name</th>
                <th className="text-left font-medium px-4 py-3">Code</th>
                <th className="text-left font-medium px-4 py-3">Supplier</th>
                <th className="text-left font-medium px-4 py-3">Location</th>
                <th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {warehouses.map((w) => (
                <tr key={w.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 font-medium text-gray-800">{w.name}</td>
                  <td className="px-4 py-3 text-gray-500">{w.code}</td>
                  <td className="px-4 py-3 text-gray-600">{w.supplier?.name ?? 'Own'}</td>
                  <td className="px-4 py-3 text-gray-600">{[w.region, w.country, w.postcode].filter(Boolean).join(', ') || '—'}</td>
                  <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-4">
                      <button onClick={() => openEdit(w)} className="inline-flex items-center gap-1 text-gray-500 hover:text-indigo-600"><Pencil size={15} /> Edit</button>
                      <button onClick={() => { if (confirm(`Delete "${w.name}"?`)) removeMutation.mutate(w.id) }} className="inline-flex items-center gap-1 text-gray-500 hover:text-red-600"><Trash2 size={15} /> Delete</button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {form && (
        <div className="fixed inset-0 bg-black/30 flex items-center justify-center p-4 z-50" onClick={close}>
          <div className="bg-white rounded-xl shadow-xl w-full max-w-lg p-6" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-lg font-semibold text-gray-900">{editing ? 'Edit warehouse' : 'Add warehouse'}</h2>
              <button onClick={close} className="text-gray-400 hover:text-gray-600"><X size={18} /></button>
            </div>
            {error && <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{error}</div>}
            <form onSubmit={(e) => { e.preventDefault(); setError(''); if (form) saveMutation.mutate(form) }} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Name</label>
                  <input className={input} value={form.name} onChange={(e) => set('name', e.target.value)} required />
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Code</label>
                  <input className={input} value={form.code} onChange={(e) => set('code', e.target.value)} required />
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Supplier</label>
                  <select className={input} value={form.supplierId ?? ''} onChange={(e) => set('supplierId', e.target.value ? Number(e.target.value) : null)}>
                    <option value="">Own warehouse</option>
                    {(suppliers ?? []).map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                  </select>
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Country (2-letter)</label>
                  <input className={input} value={form.country ?? ''} onChange={(e) => set('country', e.target.value.toUpperCase())} maxLength={2} />
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Region / State</label>
                  <input className={input} value={form.region ?? ''} onChange={(e) => set('region', e.target.value)} />
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Postcode</label>
                  <input className={input} value={form.postcode ?? ''} onChange={(e) => set('postcode', e.target.value)} />
                </div>
              </div>
              <div className="flex justify-end gap-3 pt-2">
                <button type="button" onClick={close} className="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" disabled={saveMutation.isPending} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-60">{saveMutation.isPending ? 'Saving…' : 'Save'}</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
