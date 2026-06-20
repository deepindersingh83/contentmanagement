import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Boxes, Trash2, Link2, Star, X, Search } from 'lucide-react'
import { offersApi, type Offer, type OfferPayload, type OfferFilters } from '../api/offers'
import { suppliersApi } from '../api/suppliers'
import { productsApi } from '../api/products'

const WEIGHT_UNITS = ['', 'g', 'kg', 'lb', 'oz']

export default function Offers() {
  const queryClient = useQueryClient()
  const [filters, setFilters] = useState<OfferFilters>({ supplierId: '', unmatched: '', q: '' })
  const [creating, setCreating] = useState<OfferPayload | null>(null)
  const [linking, setLinking] = useState<Offer | null>(null)
  const [error, setError] = useState('')

  const { data, isLoading } = useQuery({ queryKey: ['offers', filters], queryFn: () => offersApi.list(filters) })
  const { data: suppliers } = useQuery({ queryKey: ['suppliers'], queryFn: suppliersApi.list })

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['offers'] })

  const createMutation = useMutation({
    mutationFn: (p: OfferPayload) => offersApi.create(p),
    onSuccess: () => { invalidate(); setCreating(null) },
    onError: (e: any) => setError(e?.response?.data?.message ?? 'Could not create offer.'),
  })
  const removeMutation = useMutation({ mutationFn: offersApi.remove, onSuccess: invalidate })
  const primaryMutation = useMutation({
    mutationFn: (o: Offer) => offersApi.link(o.id, o.product?.id ?? null, !o.isPrimary),
    onSuccess: invalidate,
  })

  const input = 'px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500'

  return (
    <div className="max-w-6xl mx-auto">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Supplier offers</h1>
          <p className="text-sm text-gray-500 mt-1">Each supplier's price &amp; stock per product{data ? ` · ${data.total} offers` : ''}.</p>
        </div>
        <button onClick={() => { setError(''); setCreating({ supplierId: undefined, supplierRefCode: '', costPrice: '0', stockQuantity: 0 }) }} className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
          <Plus size={16} /> Add offer
        </button>
      </div>

      {/* Filters */}
      <div className="flex flex-wrap gap-3 mb-4">
        <select className={input} value={filters.supplierId} onChange={(e) => setFilters((f) => ({ ...f, supplierId: e.target.value ? Number(e.target.value) : '' }))}>
          <option value="">All suppliers</option>
          {(suppliers ?? []).map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
        </select>
        <select className={input} value={filters.unmatched} onChange={(e) => setFilters((f) => ({ ...f, unmatched: e.target.value as OfferFilters['unmatched'] }))}>
          <option value="">Matched &amp; unmatched</option>
          <option value="0">Matched to a product</option>
          <option value="1">Unmatched (needs review)</option>
        </select>
        <div className="relative">
          <Search size={15} className="absolute left-3 top-2.5 text-gray-400" />
          <input className={`${input} pl-9`} placeholder="Search ref / SKU / title" value={filters.q} onChange={(e) => setFilters((f) => ({ ...f, q: e.target.value }))} />
        </div>
      </div>

      {isLoading ? (
        <div className="text-sm text-gray-500">Loading…</div>
      ) : !data || data.items.length === 0 ? (
        <div className="bg-white border border-dashed border-gray-300 rounded-xl p-12 text-center">
          <Boxes size={28} className="mx-auto text-gray-400 mb-3" />
          <h2 className="font-semibold text-gray-800">No offers</h2>
          <p className="text-sm text-gray-500 mt-1">Offers are created when you import a supplier feed, or add one manually.</p>
        </div>
      ) : (
        <div className="bg-white border border-gray-200 rounded-xl overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-gray-500">
              <tr>
                <th className="text-left font-medium px-4 py-3">Supplier</th>
                <th className="text-left font-medium px-4 py-3">Ref</th>
                <th className="text-left font-medium px-4 py-3">Cost</th>
                <th className="text-left font-medium px-4 py-3">Stock</th>
                <th className="text-left font-medium px-4 py-3">Weight</th>
                <th className="text-left font-medium px-4 py-3">Product</th>
                <th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {data.items.map((o) => (
                <tr key={o.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-gray-700">{o.supplier?.name ?? '—'}</td>
                  <td className="px-4 py-3 font-medium text-gray-800">{o.supplierRefCode}{o.title ? <span className="block text-xs text-gray-400 font-normal">{o.title}</span> : null}</td>
                  <td className="px-4 py-3 text-gray-600">{o.currency} {o.costPrice}</td>
                  <td className="px-4 py-3 text-gray-600">{o.stockQuantity}</td>
                  <td className="px-4 py-3 text-gray-600">{o.weightGrams ? `${o.weightGrams} g` : '—'}</td>
                  <td className="px-4 py-3">
                    {o.product ? (
                      <span className="inline-flex items-center gap-1 text-gray-700">
                        {o.isPrimary && <Star size={13} className="text-amber-500 fill-amber-400" />}
                        {o.product.title}
                      </span>
                    ) : (
                      <span className="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Unmatched</span>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-3">
                      <button onClick={() => setLinking(o)} className="inline-flex items-center gap-1 text-gray-500 hover:text-indigo-600"><Link2 size={15} /> Link</button>
                      {o.product && (
                        <button onClick={() => primaryMutation.mutate(o)} className={`inline-flex items-center gap-1 ${o.isPrimary ? 'text-amber-600' : 'text-gray-500 hover:text-amber-600'}`}>
                          <Star size={15} /> {o.isPrimary ? 'Primary' : 'Make primary'}
                        </button>
                      )}
                      <button onClick={() => { if (confirm('Delete this offer?')) removeMutation.mutate(o.id) }} className="inline-flex items-center gap-1 text-gray-500 hover:text-red-600"><Trash2 size={15} /></button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {creating && (
        <CreateOfferModal
          payload={creating}
          setPayload={setCreating}
          error={error}
          suppliers={(suppliers ?? []).map((s) => ({ id: s.id, name: s.name }))}
          onSubmit={(p) => { setError(''); createMutation.mutate(p) }}
          pending={createMutation.isPending}
        />
      )}

      {linking && <LinkModal offer={linking} onClose={() => setLinking(null)} onLinked={() => { setLinking(null); invalidate() }} />}
    </div>
  )
}

function CreateOfferModal({
  payload, setPayload, error, suppliers, onSubmit, pending,
}: {
  payload: OfferPayload
  setPayload: (p: OfferPayload | null) => void
  error: string
  suppliers: { id: number; name: string }[]
  onSubmit: (p: OfferPayload) => void
  pending: boolean
}) {
  const input = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500'
  const set = (k: keyof OfferPayload, v: any) => setPayload({ ...payload, [k]: v })
  return (
    <div className="fixed inset-0 bg-black/30 flex items-center justify-center p-4 z-50" onClick={() => setPayload(null)}>
      <div className="bg-white rounded-xl shadow-xl w-full max-w-lg p-6" onClick={(e) => e.stopPropagation()}>
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-semibold text-gray-900">Add offer</h2>
          <button onClick={() => setPayload(null)} className="text-gray-400 hover:text-gray-600"><X size={18} /></button>
        </div>
        {error && <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{error}</div>}
        <form onSubmit={(e) => { e.preventDefault(); onSubmit(payload) }} className="space-y-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm text-gray-600 mb-1">Supplier</label>
              <select className={input} value={payload.supplierId ?? ''} onChange={(e) => set('supplierId', e.target.value ? Number(e.target.value) : undefined)} required>
                <option value="">Select…</option>
                {suppliers.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
              </select>
            </div>
            <div>
              <label className="block text-sm text-gray-600 mb-1">Supplier ref code</label>
              <input className={input} value={payload.supplierRefCode ?? ''} onChange={(e) => set('supplierRefCode', e.target.value)} required />
            </div>
            <div>
              <label className="block text-sm text-gray-600 mb-1">Cost price</label>
              <input className={input} value={payload.costPrice ?? ''} onChange={(e) => set('costPrice', e.target.value)} inputMode="decimal" />
            </div>
            <div>
              <label className="block text-sm text-gray-600 mb-1">Stock quantity</label>
              <input className={input} value={payload.stockQuantity ?? 0} onChange={(e) => set('stockQuantity', Number(e.target.value) || 0)} inputMode="numeric" />
            </div>
            <div>
              <label className="block text-sm text-gray-600 mb-1">Weight value</label>
              <input className={input} value={payload.weightValue ?? ''} onChange={(e) => set('weightValue', e.target.value)} inputMode="decimal" />
            </div>
            <div>
              <label className="block text-sm text-gray-600 mb-1">Weight unit</label>
              <select className={input} value={payload.weightUnit ?? ''} onChange={(e) => set('weightUnit', e.target.value || null)}>
                {WEIGHT_UNITS.map((u) => <option key={u} value={u}>{u || '—'}</option>)}
              </select>
            </div>
          </div>
          <div>
            <label className="block text-sm text-gray-600 mb-1">Title (supplier's)</label>
            <input className={input} value={payload.title ?? ''} onChange={(e) => set('title', e.target.value)} />
          </div>
          <div className="flex justify-end gap-3 pt-2">
            <button type="button" onClick={() => setPayload(null)} className="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
            <button type="submit" disabled={pending} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-60">{pending ? 'Saving…' : 'Save'}</button>
          </div>
        </form>
      </div>
    </div>
  )
}

function LinkModal({ offer, onClose, onLinked }: { offer: Offer; onClose: () => void; onLinked: () => void }) {
  const [q, setQ] = useState('')
  const { data } = useQuery({ queryKey: ['products', q], queryFn: () => productsApi.list(q) })
  const linkMutation = useMutation({
    mutationFn: (productId: number | null) => offersApi.link(offer.id, productId),
    onSuccess: onLinked,
  })

  return (
    <div className="fixed inset-0 bg-black/30 flex items-center justify-center p-4 z-50" onClick={onClose}>
      <div className="bg-white rounded-xl shadow-xl w-full max-w-lg p-6" onClick={(e) => e.stopPropagation()}>
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-semibold text-gray-900">Link offer to a product</h2>
          <button onClick={onClose} className="text-gray-400 hover:text-gray-600"><X size={18} /></button>
        </div>
        <p className="text-sm text-gray-500 mb-3">{offer.supplier?.name} · {offer.supplierRefCode}{offer.title ? ` · ${offer.title}` : ''}</p>
        <input autoFocus className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Search products…" value={q} onChange={(e) => setQ(e.target.value)} />
        <div className="max-h-72 overflow-y-auto divide-y divide-gray-100 border border-gray-100 rounded-lg">
          {(data?.items ?? []).map((p) => (
            <button key={p.id} onClick={() => linkMutation.mutate(p.id)} className="w-full text-left px-3 py-2 hover:bg-indigo-50 text-sm">
              <span className="font-medium text-gray-800">{p.title}</span>
              <span className="text-gray-400"> · {p.sku}</span>
            </button>
          ))}
          {data && data.items.length === 0 && <p className="px-3 py-4 text-sm text-gray-400">No products match.</p>}
        </div>
        {offer.product && (
          <button onClick={() => linkMutation.mutate(null)} className="mt-3 text-sm text-red-600 hover:underline">Unlink from {offer.product.title}</button>
        )}
      </div>
    </div>
  )
}
