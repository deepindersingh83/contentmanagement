import React, { useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useMutation } from '@tanstack/react-query'
import {
  ArrowLeft,
  Sparkles,
  LayoutTemplate,
  CalendarClock,
  ChevronsUpDown,
  UploadCloud,
  X,
} from 'lucide-react'
import {
  importApi,
  SUPPLIERS,
  type ImportSource,
  type ImportFormat,
  type KeyField,
} from '../api/import'

const SOURCES: { value: ImportSource; label: string }[] = [
  { value: 'direct', label: 'Directly Upload' },
  { value: 'url', label: 'Upload via URL' },
  { value: 'ftp', label: 'Upload via FTP' },
  { value: 'sftp', label: 'Upload via sFTP' },
]

const FORMATS: { value: ImportFormat; label: string }[] = [
  { value: 'xlsx', label: 'XLSX' },
  { value: 'xls', label: 'XLS' },
  { value: 'csv', label: 'CSV' },
  { value: 'xml', label: 'XML' },
]

const KEY_FIELDS: { value: KeyField; label: string }[] = [
  { value: 'title', label: 'Product title' },
  { value: 'sku', label: 'SKU' },
  { value: 'barcode', label: 'Barcode (EAN/UPC)' },
  { value: 'model', label: 'Model' },
  { value: 'id', label: 'Product ID' },
]

export default function NewImport() {
  const navigate = useNavigate()
  const fileInputRef = useRef<HTMLInputElement>(null)

  const [source, setSource] = useState<ImportSource>('direct')
  const [sourceUrl, setSourceUrl] = useState('')
  const [file, setFile] = useState<File | null>(null)
  const [name, setName] = useState('')
  const [fileFormat, setFileFormat] = useState<ImportFormat>('xlsx')
  const [keyField, setKeyField] = useState<KeyField>('title')
  const [supplier, setSupplier] = useState('')
  const [firstRowHeaders, setFirstRowHeaders] = useState(true)
  const [zipArchive, setZipArchive] = useState(false)
  const [importTranslations, setImportTranslations] = useState(false)
  const [error, setError] = useState('')

  const createMutation = useMutation({
    mutationFn: importApi.create,
    onSuccess: () => navigate('/import'),
    onError: (e: any) =>
      setError(e?.response?.data?.message ?? 'Could not save this import.'),
  })

  const handleFiles = (files: FileList | null) => {
    if (files && files.length > 0) setFile(files[0])
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    if (!name.trim()) {
      setError('Please enter a template name.')
      return
    }
    if (source === 'direct' && !file) {
      setError('Please add a file to upload.')
      return
    }
    if (source !== 'direct' && !sourceUrl.trim()) {
      setError('Please enter the source URL / address.')
      return
    }
    createMutation.mutate({
      name: name.trim(),
      supplier: supplier || undefined,
      source,
      sourceUrl: source !== 'direct' ? sourceUrl.trim() : undefined,
      fileFormat,
      keyField,
      firstRowHeaders,
      zipArchive,
      importTranslations,
      file: source === 'direct' ? file : null,
    })
  }

  const select =
    'w-full appearance-none px-3 py-2 pr-9 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm'
  const input =
    'w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm'

  return (
    <div className="max-w-5xl mx-auto">
      {/* Header row */}
      <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
        <button
          onClick={() => navigate('/import')}
          className="inline-flex items-center gap-2 text-lg font-semibold text-gray-900"
        >
          <ArrowLeft size={20} /> New import
        </button>
        <div className="flex items-center gap-2">
          <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium text-white bg-gradient-to-r from-fuchsia-600 to-indigo-600">
            <Sparkles size={15} /> AI assistant
          </span>
          <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm text-gray-600 border border-gray-200 bg-white">
            <LayoutTemplate size={15} /> Saved templates
          </span>
          <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm text-gray-600 border border-gray-200 bg-white">
            <CalendarClock size={15} /> Scheduled tasks
          </span>
          <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm text-gray-600 border border-gray-200 bg-white">
            En <ChevronsUpDown size={14} />
          </span>
        </div>
      </div>

      <form
        onSubmit={handleSubmit}
        className="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6"
      >
        {error && (
          <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
            {error}
          </div>
        )}

        {/* Source to import */}
        <div>
          <h2 className="text-sm font-semibold text-gray-800 mb-3">Source to import</h2>
          <div className="flex flex-wrap gap-x-8 gap-y-3">
            {SOURCES.map((s) => (
              <label key={s.value} className="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input
                  type="radio"
                  name="source"
                  checked={source === s.value}
                  onChange={() => setSource(s.value)}
                  className="accent-indigo-600"
                />
                {s.label}
              </label>
            ))}
          </div>
        </div>

        {/* Upload file OR source address */}
        {source === 'direct' ? (
          <div>
            <label className="block text-sm font-semibold text-gray-800 mb-2">Upload file</label>
            <div
              onDragOver={(e) => e.preventDefault()}
              onDrop={(e) => {
                e.preventDefault()
                handleFiles(e.dataTransfer.files)
              }}
              className="border-2 border-dashed border-gray-300 rounded-xl py-10 px-4 text-center"
            >
              {file ? (
                <div className="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-sm">
                  {file.name}
                  <button type="button" onClick={() => setFile(null)} aria-label="Remove file">
                    <X size={14} />
                  </button>
                </div>
              ) : (
                <UploadCloud size={28} className="mx-auto text-gray-400 mb-2" />
              )}
              <div className="mt-3">
                <button
                  type="button"
                  onClick={() => fileInputRef.current?.click()}
                  className="px-4 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                  Add files
                </button>
              </div>
              <p className="text-xs text-gray-400 mt-2">
                Accepts .xls, .xlsx, .csv, .xml and zip files
              </p>
              <input
                ref={fileInputRef}
                type="file"
                accept=".xls,.xlsx,.csv,.xml,.zip"
                className="hidden"
                onChange={(e) => handleFiles(e.target.files)}
              />
            </div>
          </div>
        ) : (
          <div>
            <label className="block text-sm font-semibold text-gray-800 mb-2">
              Source address ({source.toUpperCase()})
            </label>
            <input
              className={input}
              value={sourceUrl}
              onChange={(e) => setSourceUrl(e.target.value)}
              placeholder={source === 'url' ? 'https://example.com/feed.xml' : 'ftp://host/path/file.csv'}
            />
          </div>
        )}

        {/* Config grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
          <div>
            <label className="block text-sm text-gray-600 mb-1">Template name</label>
            <input
              className={input}
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="Type name here"
            />
          </div>
          <div>
            <label className="block text-sm text-gray-600 mb-1">Choose file format</label>
            <div className="relative">
              <select className={select} value={fileFormat} onChange={(e) => setFileFormat(e.target.value as ImportFormat)}>
                {FORMATS.map((f) => (
                  <option key={f.value} value={f.value}>{f.label}</option>
                ))}
              </select>
              <ChevronsUpDown size={14} className="pointer-events-none absolute right-3 top-3 text-gray-400" />
            </div>
          </div>
          <div>
            <label className="block text-sm text-gray-600 mb-1">Key for product identification</label>
            <div className="relative">
              <select className={select} value={keyField} onChange={(e) => setKeyField(e.target.value as KeyField)}>
                {KEY_FIELDS.map((k) => (
                  <option key={k.value} value={k.value}>{k.label}</option>
                ))}
              </select>
              <ChevronsUpDown size={14} className="pointer-events-none absolute right-3 top-3 text-gray-400" />
            </div>
          </div>
          <div>
            <label className="block text-sm text-gray-600 mb-1">Supplier</label>
            <div className="relative">
              <select className={select} value={supplier} onChange={(e) => setSupplier(e.target.value)}>
                <option value="">Select a supplier…</option>
                {SUPPLIERS.map((s) => (
                  <option key={s} value={s}>{s}</option>
                ))}
              </select>
              <ChevronsUpDown size={14} className="pointer-events-none absolute right-3 top-3 text-gray-400" />
            </div>
          </div>
        </div>

        {/* Checkboxes */}
        <div className="space-y-2">
          <label className="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" className="accent-indigo-600" checked={firstRowHeaders} onChange={(e) => setFirstRowHeaders(e.target.checked)} />
            Use the first row as headers
          </label>
          <label className="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" className="accent-indigo-600" checked={zipArchive} onChange={(e) => setZipArchive(e.target.checked)} />
            My file is in zip archive
          </label>
          <label className="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" className="accent-indigo-600" checked={importTranslations} onChange={(e) => setImportTranslations(e.target.checked)} />
            Import translations
          </label>
        </div>

        <div className="flex justify-end">
          <button
            type="submit"
            disabled={createMutation.isPending}
            className="px-5 py-2.5 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-black disabled:opacity-60"
          >
            {createMutation.isPending ? 'Saving…' : 'Next Step'}
          </button>
        </div>
      </form>
    </div>
  )
}
