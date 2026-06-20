import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Truck, Trash2, Pencil, X } from 'lucide-react'
import { suppliersApi, WEIGHT_UNITS, type Supplier, type SupplierPayload } from '../api/suppliers'

const empty: SupplierPayload = {
  name: '',
  code: '',
  active: true,
  defaultCurrency: 'AUD',
  defaultWeightUnit: 'kg',
  website: '',
  contactEmail: '',
}

export default function Suppliers() {
  const queryClient = useQueryClient()
  const { data: suppliers, isLoading } = useQuery({ queryKey: ['suppliers'], queryFn: suppliersApi.list })

  const [editing, setEditing] = useState<Supplier | null>(null)
  const [form, setForm] = useState<SupplierPayload | null>(null)
  const [error, setError] = useState('')

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['suppliers'] })

  const saveMutation = useMutation({
    mutationFn: (payload: SupplierPayload) =>
      editing ? suppliersApi.update(editing.id, payload) : suppliersApi.create(payload),
    onSuccess: () => {
      invalidate()
      closeForm()
    },
    onError: (e: any) => setError(e?.response?.data?.message ?? 'Could not save supplier.'),
  })

  const removeMutation = useMutation({
    mutationFn: suppliersApi.remove,
    onSuccess: invalidate,
  })

  const openCreate = () => {
    setEditing(null)
    setForm({ ...empty })
    setError('')
  }
  const openEdit = (s: Supplier) => {
    setEditing(s)
    setForm({
      name: s.name,
      code: s.code,
      active: s.active,
      defaultCurrency: s.defaultCurrency,
      defaultWeightUnit: s.defaultWeightUnit,
      website: s.website ?? '',
      contactEmail: s.contactEmail ?? '',
      notes: s.notes ?? '',
    })
    setError('')
  }
  const closeForm = () => {
    setForm(null)
    setEditing(null)
  }

  const field = (k: keyof SupplierPayload, v: string | boolean) => setForm((f) => (f ? { ...f, [k]: v } : f))

  const input = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500'

  return (
    <div className="max-w-5xl mx-auto">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Suppliers</h1>
          <p className="text-sm text-gray-500 mt-1">The suppliers you source products from.</p>
        </div>
        <button onClick={openCreate} className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
          <Plus size={16} /> Add supplier
        </button>
      </div>

      {isLoading ? (
        <div className="text-sm text-gray-500">Loading…</div>
      ) : !suppliers || suppliers.length === 0 ? (
        <div className="bg-white border border-dashed border-gray-300 rounded-xl p-12 text-center">
          <Truck size={28} className="mx-auto text-gray-400 mb-3" />
          <h2 className="font-semibold text-gray-800">No suppliers yet</h2>
          <p className="text-sm text-gray-500 mt-1 mb-4">Add a supplier, or seed the starter list with the console command.</p>
          <button onClick={openCreate} className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
            <Plus size={16} /> Add supplier
          </button>
        </div>
      ) : (
        <div className="bg-white border border-gray-200 rounded-xl overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-gray-500">
              <tr>
                <th className="text-left font-medium px-4 py-3">Name</th>
                <th className="text-left font-medium px-4 py-3">Code</th>
                <th className="text-left font-medium px-4 py-3">Currency</th>
                <th className="text-left font-medium px-4 py-3">Weight unit</th>
                <th className="text-left font-medium px-4 py-3">Status</th>
                <th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {suppliers.map((s) => (
                <tr key={s.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 font-medium text-gray-800">{s.name}</td>
                  <td className="px-4 py-3 text-gray-500">{s.code}</td>
                  <td className="px-4 py-3 text-gray-600">{s.defaultCurrency}</td>
                  <td className="px-4 py-3 text-gray-600">{s.defaultWeightUnit}</td>
                  <td className="px-4 py-3">
                    <span className={`text-xs px-2 py-0.5 rounded-full ${s.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}>
                      {s.active ? 'Active' : 'Inactive'}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-4">
                      <button onClick={() => openEdit(s)} className="inline-flex items-center gap-1 text-gray-500 hover:text-indigo-600">
                        <Pencil size={15} /> Edit
                      </button>
                      <button
                        onClick={() => {
                          if (confirm(`Delete supplier "${s.name}"?`)) removeMutation.mutate(s.id)
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

      {/* Create / edit drawer */}
      {form && (
        <div className="fixed inset-0 bg-black/30 flex items-center justify-center p-4 z-50" onClick={closeForm}>
          <div className="bg-white rounded-xl shadow-xl w-full max-w-lg p-6" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-lg font-semibold text-gray-900">{editing ? 'Edit supplier' : 'Add supplier'}</h2>
              <button onClick={closeForm} className="text-gray-400 hover:text-gray-600"><X size={18} /></button>
            </div>

            {error && <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{error}</div>}

            <form
              onSubmit={(e) => {
                e.preventDefault()
                setError('')
                if (form) saveMutation.mutate(form)
              }}
              className="space-y-4"
            >
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Name</label>
                  <input className={input} value={form.name} onChange={(e) => field('name', e.target.value)} required />
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Code <span className="text-gray-400">(auto if blank)</span></label>
                  <input className={input} value={form.code ?? ''} onChange={(e) => field('code', e.target.value)} placeholder="auto" />
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Default currency</label>
                  <input className={input} value={form.defaultCurrency ?? ''} onChange={(e) => field('defaultCurrency', e.target.value.toUpperCase())} maxLength={3} />
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Default weight unit</label>
                  <select className={input} value={form.defaultWeightUnit} onChange={(e) => field('defaultWeightUnit', e.target.value)}>
                    {WEIGHT_UNITS.map((u) => <option key={u} value={u}>{u}</option>)}
                  </select>
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Website</label>
                  <input className={input} value={form.website ?? ''} onChange={(e) => field('website', e.target.value)} placeholder="https://…" />
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Contact email</label>
                  <input type="email" className={input} value={form.contactEmail ?? ''} onChange={(e) => field('contactEmail', e.target.value)} />
                </div>
              </div>
              <label className="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" className="accent-indigo-600" checked={Boolean(form.active)} onChange={(e) => field('active', e.target.checked)} />
                Active
              </label>

              <div className="flex justify-end gap-3 pt-2">
                <button type="button" onClick={closeForm} className="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" disabled={saveMutation.isPending} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-60">
                  {saveMutation.isPending ? 'Saving…' : 'Save'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
