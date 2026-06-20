import { useEffect, useMemo, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation } from '@tanstack/react-query'
import {
  ArrowLeft, Sparkles, LayoutTemplate, CalendarClock, ChevronsUpDown, Search, X, Info,
  Image, Megaphone, Tag, Boxes, Truck, Layers, Network, Database, Settings2,
  ListChecks, Package, Wand2, Bot, Lock, Circle, type LucideIcon,
} from 'lucide-react'
import { importApi } from '../api/import'
import { CATEGORIES, type FieldDef } from '../lib/importFields'

type MappingValue = string | boolean | string[]
type Mapping = Record<string, MappingValue>

const ICONS: Record<string, LucideIcon> = {
  Info, Image, Megaphone, Tag, Boxes, Truck, Layers, Search, Network, Database,
  Settings2, ListChecks, Package, Wand2, Bot, Lock,
}

function Icon({ name, ...props }: { name: string; size?: number; className?: string }) {
  const Cmp = ICONS[name] ?? Circle
  return <Cmp {...props} />
}

export default function ImportSettings() {
  const { id } = useParams()
  const templateId = Number(id)
  const navigate = useNavigate()

  const [active, setActive] = useState('information')
  const [search, setSearch] = useState('')
  const [mapping, setMapping] = useState<Mapping>({})
  const [savedMsg, setSavedMsg] = useState('')

  const { data: template } = useQuery({
    queryKey: ['import-template', templateId],
    queryFn: () => importApi.get(templateId),
    enabled: Number.isFinite(templateId),
  })

  const { data: columnsData } = useQuery({
    queryKey: ['import-columns', templateId],
    queryFn: () => importApi.columns(templateId),
    enabled: Number.isFinite(templateId),
  })

  // Seed mapping from the saved template once it loads.
  useEffect(() => {
    if (template?.mapping) setMapping(template.mapping as Mapping)
  }, [template])

  const columns = columnsData?.columns ?? []

  const saveMutation = useMutation({
    mutationFn: () => importApi.saveMapping(templateId, mapping),
    onSuccess: () => {
      setSavedMsg('Saved.')
      setTimeout(() => setSavedMsg(''), 2000)
    },
  })

  const activeCategory = CATEGORIES.find((c) => c.key === active)
  const visibleCategories = CATEGORIES.filter((c) =>
    c.label.toLowerCase().includes(search.toLowerCase()),
  )

  const set = (key: string, value: MappingValue) => setMapping((m) => ({ ...m, [key]: value }))

  const selectClass =
    'w-full appearance-none px-3 py-2 pr-9 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm'

  const renderField = (f: FieldDef) => {
    if (f.type === 'checkbox') {
      return (
        <label key={f.key} className="flex items-center gap-2 text-sm text-gray-700">
          <input
            type="checkbox"
            className="accent-indigo-600"
            checked={Boolean(mapping[f.key])}
            onChange={(e) => set(f.key, e.target.checked)}
          />
          {f.label}
        </label>
      )
    }

    if (f.type === 'tags') {
      const values = Array.isArray(mapping[f.key]) ? (mapping[f.key] as string[]) : []
      return (
        <div key={f.key}>
          <Label text={f.label} />
          <TagsInput
            values={values}
            options={f.options}
            onChange={(v) => set(f.key, v)}
          />
        </div>
      )
    }

    // 'column' or 'fixed' -> a select
    const options = f.type === 'fixed' ? f.options ?? [] : columns
    const current = typeof mapping[f.key] === 'string' ? (mapping[f.key] as string) : ''
    return (
      <div key={f.key}>
        <Label text={f.label} />
        <div className="relative">
          <select className={selectClass} value={current} onChange={(e) => set(f.key, e.target.value)}>
            <option value="">{f.type === 'fixed' ? 'Not selected' : 'Not selected'}</option>
            {options.map((o) => (
              <option key={o} value={o}>{o}</option>
            ))}
          </select>
          <ChevronsUpDown size={14} className="pointer-events-none absolute right-3 top-3 text-gray-400" />
        </div>
      </div>
    )
  }

  // Group fields by their optional sub-heading.
  const grouped = useMemo(() => {
    const fields = activeCategory?.fields ?? []
    const groups: { group: string | undefined; fields: FieldDef[] }[] = []
    for (const f of fields) {
      const last = groups[groups.length - 1]
      if (last && last.group === f.group) last.fields.push(f)
      else groups.push({ group: f.group, fields: [f] })
    }
    return groups
  }, [activeCategory])

  return (
    <div className="max-w-6xl mx-auto">
      {/* Header */}
      <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
        <button onClick={() => navigate('/import')} className="inline-flex items-center gap-2 text-lg font-semibold text-gray-900">
          <ArrowLeft size={20} /> Import settings
        </button>
        <div className="flex items-center gap-2">
          <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium text-white bg-gradient-to-r from-fuchsia-600 to-indigo-600">
            <Sparkles size={15} /> AI assistant
          </span>
          <button onClick={() => navigate('/import')} className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm text-gray-600 border border-gray-200 bg-white hover:bg-gray-50">
            <LayoutTemplate size={15} /> Saved templates
          </button>
          <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm text-gray-600 border border-gray-200 bg-white">
            <CalendarClock size={15} /> Scheduled tasks
          </span>
          <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm text-gray-600 border border-gray-200 bg-white">
            En <ChevronsUpDown size={14} />
          </span>
        </div>
      </div>

      {template && (
        <p className="text-sm text-gray-500 mb-3">
          Mapping <span className="font-medium text-gray-700">{template.name}</span>
          {template.supplier ? ` · ${template.supplier}` : ''} · {template.fileFormat.toUpperCase()}
        </p>
      )}

      <div className="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-5">
        {/* Sidebar */}
        <aside className="bg-white border border-gray-200 rounded-xl p-3 h-fit">
          <div className="relative mb-2">
            <Search size={15} className="absolute left-3 top-2.5 text-gray-400" />
            <input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search Fields"
              className="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <nav className="space-y-0.5">
            {visibleCategories.map((c) => (
              <button
                key={c.key}
                onClick={() => !c.locked && setActive(c.key)}
                disabled={c.locked}
                className={`w-full flex items-center justify-between gap-2 px-3 py-2 rounded-lg text-sm text-left ${
                  active === c.key ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'
                } ${c.locked ? 'opacity-70 cursor-not-allowed' : ''}`}
              >
                <span className="inline-flex items-center gap-2">
                  <Icon name={c.icon} size={16} />
                  {c.label}
                </span>
                {c.badge && (
                  <span className={`text-[11px] px-1.5 py-0.5 rounded ${c.badge === 'New' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'}`}>
                    {c.badge}
                  </span>
                )}
              </button>
            ))}
          </nav>
        </aside>

        {/* Panel */}
        <section className="bg-white border border-gray-200 rounded-xl p-6">
          {activeCategory?.locked || !activeCategory?.fields ? (
            <div className="text-center text-gray-500 py-16">
              <Icon name={activeCategory?.icon ?? 'Lock'} size={28} className="mx-auto mb-3 text-gray-300" />
              <p className="font-medium text-gray-700">{activeCategory?.label}</p>
              <p className="text-sm mt-1">This section is available as an add-on and isn't configured yet.</p>
            </div>
          ) : (
            <div className="space-y-5 max-w-xl">
              {grouped.map((g, gi) => (
                <div key={gi} className="space-y-4">
                  {g.group && <h3 className="text-sm font-semibold text-gray-800 pt-1">{g.group}</h3>}
                  {g.fields.map(renderField)}
                </div>
              ))}
            </div>
          )}
        </section>
      </div>

      {/* Footer actions */}
      <div className="flex items-center justify-end gap-3 mt-6">
        {savedMsg && <span className="text-sm text-green-600">{savedMsg}</span>}
        {columnsData?.note && <span className="text-xs text-amber-600 mr-auto inline-flex items-center gap-1"><Info size={13} /> {columnsData.note}</span>}
        <button
          onClick={() => saveMutation.mutate()}
          disabled={saveMutation.isPending}
          className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-60"
        >
          {saveMutation.isPending ? 'Saving…' : 'Save'}
        </button>
        <button
          onClick={() => alert('Test import will validate the mapping against a few rows — coming in the next step.')}
          className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          Test import
        </button>
        <button
          onClick={() => {
            saveMutation.mutate()
            alert('Mapping saved. Running the actual product import is the next milestone.')
          }}
          className="px-5 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-black"
        >
          Import
        </button>
      </div>
    </div>
  )
}

function Label({ text }: { text: string }) {
  return (
    <div className="flex items-center gap-1.5 mb-1">
      <label className="block text-sm text-gray-600">{text}</label>
      <Info size={13} className="text-gray-300" />
    </div>
  )
}

function TagsInput({
  values,
  options,
  onChange,
}: {
  values: string[]
  options?: string[]
  onChange: (v: string[]) => void
}) {
  const [input, setInput] = useState('')
  const suggestions = (options ?? []).filter((o) => !values.includes(o))

  const add = (v: string) => {
    const t = v.trim()
    if (t && !values.includes(t)) onChange([...values, t])
    setInput('')
  }

  return (
    <div className="border border-gray-300 rounded-lg px-2 py-1.5 focus-within:ring-2 focus-within:ring-indigo-500">
      <div className="flex flex-wrap gap-1.5">
        {values.map((v) => (
          <span key={v} className="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
            {v}
            <button type="button" onClick={() => onChange(values.filter((x) => x !== v))}>
              <X size={12} />
            </button>
          </span>
        ))}
        <input
          value={input}
          onChange={(e) => setInput(e.target.value)}
          onKeyDown={(e) => {
            if (e.key === 'Enter') {
              e.preventDefault()
              add(input)
            }
          }}
          placeholder="Search…"
          className="flex-1 min-w-[80px] text-sm py-0.5 outline-none"
          list={`opts-${values.length}`}
        />
        {suggestions.length > 0 && (
          <datalist id={`opts-${values.length}`}>
            {suggestions.map((s) => (
              <option key={s} value={s} />
            ))}
          </datalist>
        )}
      </div>
    </div>
  )
}
