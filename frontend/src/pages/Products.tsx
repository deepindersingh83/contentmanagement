import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Plus, Package, Trash2, Pencil, X, Search } from 'lucide-react'
import {
  productsApi, categoriesApi, brandsApi, PRODUCT_STATUSES,
  type Product, type ProductPayload, type Ref,
} from '../api/products'

const emptyForm: ProductPayload = {
  sku: '', title: '', gtin: '', status: 'draft',
  shortDescription: '', longDescription: '', weightGrams: '', sellPrice: '',
  primaryImageUrl: '', categoryId: null, brandId: null,
}

const statusBadge: Record<string, string> = {
  active: 'bg-green-100 text-green-700',
  draft: 'bg-gray-100 text-gray-600',
  archived: 'bg-amber-100 text-amber-700',
}

export default function Products() {
  const queryClient = useQueryClient()
  const [search, setSearch] = useState('')
  const [editing, setEditing] = useState<Product | null>(null)
  const [form, setForm] = useState<ProductPayload | null>(null)
  const [error, setError] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['products', search],
    queryFn: () => productsApi.list(search),
  })
  const { data: categories } = useQuery({ queryKey: ['categories'], queryFn: categoriesApi.list })
  const { data: brands } = useQuery({ queryKey: ['brands'], queryFn: brandsApi.list })

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['products'] })

  const saveMutation = useMutation({
    mutationFn: (payload: ProductPayload) =>
      editing ? productsApi.update(editing.id, payload) : productsApi.create(payload),
    onSuccess: () => { invalidate(); close() },
    onError: (e: any) => setError(e?.response?.data?.message ?? 'Could not save product.'),
  })
  const removeMutation = useMutation({ mutationFn: productsApi.remove, onSuccess: invalidate })

  const openCreate = () => { setEditing(null); setForm({ ...emptyForm }); setError('') }
  const openEdit = (p: Product) => {
    setEditing(p)
    setForm({
      sku: p.sku, title: p.title, gtin: p.gtin ?? '', status: p.status,
      shortDescription: p.shortDescription ?? '', longDescription: p.longDescription ?? '',
      weightGrams: p.weightGrams ?? '', sellPrice: p.sellPrice ?? '', primaryImageUrl: p.primaryImageUrl ?? '',
      categoryId: p.category?.id ?? null, brandId: p.brand?.id ?? null,
    })
    setError('')
  }
  const close = () => { setForm(null); setEditing(null) }
  const set = (k: keyof ProductPayload, v: any) => setForm((f) => (f ? { ...f, [k]: v } : f))

  // Quick-add a category/brand via a prompt, then select it.
  const quickAdd = async (kind: 'category' | 'brand') => {
    const name = window.prompt(`New ${kind} name`)
    if (!name) return
    const created: Ref = kind === 'category' ? await categoriesApi.create(name) : await brandsApi.create(name)
    queryClient.invalidateQueries({ queryKey: [kind === 'category' ? 'categories' : 'brands'] })
    set(kind === 'category' ? 'categoryId' : 'brandId', created.id)
  }

  const input = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500'

  return (
    <div className="max-w-6xl mx-auto">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Products</h1>
          <p className="text-sm text-gray-500 mt-1">Your master catalogue{data ? ` · ${data.total} products` : ''}.</p>
        </div>
        <button onClick={openCreate} className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
          <Plus size={16} /> Add product
        </button>
      </div>

      <div className="relative mb-4 max-w-sm">
        <Search size={15} className="absolute left-3 top-2.5 text-gray-400" />
        <input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search title, SKU or barcode" className={`${input} pl-9`} />
      </div>

      {isLoading ? (
        <div className="text-sm text-gray-500">Loading…</div>
      ) : !data || data.items.length === 0 ? (
        <div className="bg-white border border-dashed border-gray-300 rounded-xl p-12 text-center">
          <Package size={28} className="mx-auto text-gray-400 mb-3" />
          <h2 className="font-semibold text-gray-800">No products yet</h2>
          <p className="text-sm text-gray-500 mt-1 mb-4">Add one manually, or import a supplier feed.</p>
          <button onClick={openCreate} className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
            <Plus size={16} /> Add product
          </button>
        </div>
      ) : (
        <div className="bg-white border border-gray-200 rounded-xl overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-gray-500">
              <tr>
                <th className="text-left font-medium px-4 py-3">Title</th>
                <th className="text-left font-medium px-4 py-3">SKU</th>
                <th className="text-left font-medium px-4 py-3">Category</th>
                <th className="text-left font-medium px-4 py-3">Brand</th>
                <th className="text-left font-medium px-4 py-3">Price</th>
                <th className="text-left font-medium px-4 py-3">Status</th>
                <th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {data.items.map((p) => (
                <tr key={p.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 font-medium text-gray-800">{p.title}</td>
                  <td className="px-4 py-3 text-gray-500">{p.sku}</td>
                  <td className="px-4 py-3 text-gray-600">{p.category?.name ?? '—'}</td>
                  <td className="px-4 py-3 text-gray-600">{p.brand?.name ?? '—'}</td>
                  <td className="px-4 py-3 text-gray-600">{p.sellPrice ? `$${p.sellPrice}` : '—'}</td>
                  <td className="px-4 py-3">
                    <span className={`text-xs px-2 py-0.5 rounded-full ${statusBadge[p.status] ?? ''}`}>{p.status}</span>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-4">
                      <button onClick={() => openEdit(p)} className="inline-flex items-center gap-1 text-gray-500 hover:text-indigo-600"><Pencil size={15} /> Edit</button>
                      <button onClick={() => { if (confirm(`Delete "${p.title}"?`)) removeMutation.mutate(p.id) }} className="inline-flex items-center gap-1 text-gray-500 hover:text-red-600"><Trash2 size={15} /> Delete</button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {form && (
        <div className="fixed inset-0 bg-black/30 flex items-center justify-center p-4 z-50 overflow-y-auto" onClick={close}>
          <div className="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 my-8" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-lg font-semibold text-gray-900">{editing ? 'Edit product' : 'Add product'}</h2>
              <button onClick={close} className="text-gray-400 hover:text-gray-600"><X size={18} /></button>
            </div>
            {error && <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{error}</div>}
            <form onSubmit={(e) => { e.preventDefault(); setError(''); if (form) saveMutation.mutate(form) }} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm text-gray-600 mb-1">SKU</label>
                  <input className={input} value={form.sku} onChange={(e) => set('sku', e.target.value)} required />
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Barcode / GTIN</label>
                  <input className={input} value={form.gtin ?? ''} onChange={(e) => set('gtin', e.target.value)} />
                </div>
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Title</label>
                <input className={input} value={form.title} onChange={(e) => set('title', e.target.value)} required />
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Short description</label>
                <textarea className={input} rows={2} value={form.shortDescription ?? ''} onChange={(e) => set('shortDescription', e.target.value)} />
              </div>
              <div>
                <label className="block text-sm text-gray-600 mb-1">Long description</label>
                <textarea className={input} rows={4} value={form.longDescription ?? ''} onChange={(e) => set('longDescription', e.target.value)} />
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <div className="flex items-center justify-between mb-1">
                    <label className="block text-sm text-gray-600">Category</label>
                    <button type="button" onClick={() => quickAdd('category')} className="text-xs text-indigo-600 hover:underline">+ New</button>
                  </div>
                  <select className={input} value={form.categoryId ?? ''} onChange={(e) => set('categoryId', e.target.value ? Number(e.target.value) : null)}>
                    <option value="">—</option>
                    {(categories ?? []).map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                  </select>
                </div>
                <div>
                  <div className="flex items-center justify-between mb-1">
                    <label className="block text-sm text-gray-600">Brand</label>
                    <button type="button" onClick={() => quickAdd('brand')} className="text-xs text-indigo-600 hover:underline">+ New</button>
                  </div>
                  <select className={input} value={form.brandId ?? ''} onChange={(e) => set('brandId', e.target.value ? Number(e.target.value) : null)}>
                    <option value="">—</option>
                    {(brands ?? []).map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                  </select>
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Weight (grams)</label>
                  <input className={input} value={form.weightGrams ?? ''} onChange={(e) => set('weightGrams', e.target.value)} inputMode="decimal" />
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Sell price</label>
                  <input className={input} value={form.sellPrice ?? ''} onChange={(e) => set('sellPrice', e.target.value)} inputMode="decimal" />
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Status</label>
                  <select className={input} value={form.status} onChange={(e) => set('status', e.target.value)}>
                    {PRODUCT_STATUSES.map((s) => <option key={s} value={s}>{s}</option>)}
                  </select>
                </div>
                <div>
                  <label className="block text-sm text-gray-600 mb-1">Primary image URL</label>
                  <input className={input} value={form.primaryImageUrl ?? ''} onChange={(e) => set('primaryImageUrl', e.target.value)} placeholder="https://…" />
                </div>
              </div>
              <div className="flex justify-end gap-3 pt-2">
                <button type="button" onClick={close} className="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
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
