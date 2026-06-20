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
  DELIMITERS,
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

const inputClass =
  'w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm'

function Field({ label, hint, children }: { label: string; hint?: React.ReactNode; children: React.ReactNode }) {
  return (
    <div>
      <div className="flex items-center justify-between mb-1">
        <label className="block text-sm text-gray-600">{label}</label>
        {hint}
      </div>
      {children}
    </div>
  )
}

function Select({ value, onChange, children }: { value: string; onChange: (v: string) => void; children: React.ReactNode }) {
  return (
    <div className="relative">
      <select
        className="w-full appearance-none px-3 py-2 pr-9 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
        value={value}
        onChange={(e) => onChange(e.target.value)}
      >
        {children}
      </select>
      <ChevronsUpDown size={14} className="pointer-events-none absolute right-3 top-3 text-gray-400" />
    </div>
  )
}

export default function NewImport() {
  const navigate = useNavigate()
  const fileInputRef = useRef<HTMLInputElement>(null)

  const [source, setSource] = useState<ImportSource>('direct')
  const [sourceUrl, setSourceUrl] = useState('')
  const [file, setFile] = useState<File | null>(null)
  const [name, setName] = useState('')
  const [fileFormat, setFileFormat] = useState<ImportFormat>('xlsx')
  const [delimiter, setDelimiter] = useState(',')
  const [keyField, setKeyField] = useState<KeyField>('title')
  const [supplier, setSupplier] = useState('')

  // FTP / sFTP
  const [ftpServer, setFtpServer] = useState('')
  const [ftpUsername, setFtpUsername] = useState('')
  const [ftpPassword, setFtpPassword] = useState('')
  const [ftpPort, setFtpPort] = useState('')
  const [ftpPath, setFtpPath] = useState('')

  // Options
  const [firstRowHeaders, setFirstRowHeaders] = useState(true)
  const [ftpPassiveMode, setFtpPassiveMode] = useState(true)
  const [removeAfterImport, setRemoveAfterImport] = useState(false)
  const [zipArchive, setZipArchive] = useState(false)
  const [importTranslations, setImportTranslations] = useState(false)

  const [error, setError] = useState('')

  const isCsv = fileFormat === 'csv'
  const isFtp = source === 'ftp' || source === 'sftp'

  const createMutation = useMutation({
    mutationFn: importApi.create,
    onSuccess: (created) => navigate(`/import/${created.id}/settings`),
    onError: (e: any) => setError(e?.response?.data?.message ?? 'Could not save this import.'),
  })

  const handleFiles = (files: FileList | null) => {
    if (files && files.length > 0) setFile(files[0])
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    if (!name.trim()) return setError('Please enter a template name.')
    if (source === 'direct' && !file) return setError('Please add a file to upload.')
    if (source === 'url' && !sourceUrl.trim()) return setError('Please enter the File URL.')
    if (isFtp && (!ftpServer.trim() || !ftpPath.trim())) {
      return setError('Please enter at least the FTP server and the absolute path to the file.')
    }

    createMutation.mutate({
      name: name.trim(),
      supplier: supplier || undefined,
      source,
      sourceUrl: source === 'url' ? sourceUrl.trim() : undefined,
      fileFormat,
      keyField,
      firstRowHeaders,
      zipArchive,
      importTranslations,
      delimiter: isCsv ? delimiter : undefined,
      ftpServer: isFtp ? ftpServer.trim() : undefined,
      ftpUsername: isFtp ? ftpUsername.trim() : undefined,
      ftpPassword: isFtp ? ftpPassword : undefined,
      ftpPort: isFtp ? ftpPort.trim() : undefined,
      ftpPath: isFtp ? ftpPath.trim() : undefined,
      ftpPassiveMode: source === 'ftp' ? ftpPassiveMode : undefined,
      removeAfterImport: isFtp ? removeAfterImport : undefined,
      file: source === 'direct' ? file : null,
    })
  }

  const tutorial = (
    <a href="#" onClick={(e) => e.preventDefault()} className="text-xs text-indigo-600 hover:underline">
      Tutorial
    </a>
  )

  const formatField = (
    <Field label="Choose file format">
      <Select value={fileFormat} onChange={(v) => setFileFormat(v as ImportFormat)}>
        {FORMATS.map((f) => (
          <option key={f.value} value={f.value}>{f.label}</option>
        ))}
      </Select>
    </Field>
  )

  const delimiterField = isCsv && (
    <Field label="Delimiter">
      <Select value={delimiter} onChange={setDelimiter}>
        {DELIMITERS.map((d) => (
          <option key={d.value} value={d.value}>{d.label}</option>
        ))}
      </Select>
    </Field>
  )

  const keyFieldField = (
    <Field label="Key for product identification">
      <Select value={keyField} onChange={(v) => setKeyField(v as KeyField)}>
        {KEY_FIELDS.map((k) => (
          <option key={k.value} value={k.value}>{k.label}</option>
        ))}
      </Select>
    </Field>
  )

  const supplierField = (
    <Field label="Supplier">
      <Select value={supplier} onChange={setSupplier}>
        <option value="">Select a supplier…</option>
        {SUPPLIERS.map((s) => (
          <option key={s} value={s}>{s}</option>
        ))}
      </Select>
    </Field>
  )

  const nameField = (
    <Field label="Template name">
      <input className={inputClass} value={name} onChange={(e) => setName(e.target.value)} placeholder="Type name here" />
    </Field>
  )

  return (
    <div className="max-w-5xl mx-auto">
      {/* Header row */}
      <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
        <button onClick={() => navigate('/import')} className="inline-flex items-center gap-2 text-lg font-semibold text-gray-900">
          <ArrowLeft size={20} /> New import
        </button>
        <div className="flex items-center gap-2">
          <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium text-white bg-gradient-to-r from-fuchsia-600 to-indigo-600">
            <Sparkles size={15} /> AI assistant
          </span>
          <button type="button" onClick={() => navigate('/import')} className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm text-gray-600 border border-gray-200 bg-white hover:bg-gray-50">
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

      <form onSubmit={handleSubmit} className="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-6">
        {error && (
          <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{error}</div>
        )}

        {/* Source to import */}
        <div>
          <h2 className="text-sm font-semibold text-gray-800 mb-3">Source to import</h2>
          <div className="flex flex-wrap gap-x-8 gap-y-3">
            {SOURCES.map((s) => (
              <label key={s.value} className="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="radio" name="source" checked={source === s.value} onChange={() => setSource(s.value)} className="accent-indigo-600" />
                {s.label}
              </label>
            ))}
          </div>
        </div>

        {/* ---- Directly Upload ---- */}
        {source === 'direct' && (
          <>
            <Field label="Upload file">
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
                    <button type="button" onClick={() => setFile(null)} aria-label="Remove file"><X size={14} /></button>
                  </div>
                ) : (
                  <UploadCloud size={28} className="mx-auto text-gray-400 mb-2" />
                )}
                <div className="mt-3">
                  <button type="button" onClick={() => fileInputRef.current?.click()} className="px-4 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Add files
                  </button>
                </div>
                <p className="text-xs text-gray-400 mt-2">Accepts .xls, .xlsx, .csv, .xml and zip files</p>
                <input ref={fileInputRef} type="file" accept=".xls,.xlsx,.csv,.xml,.zip" className="hidden" onChange={(e) => handleFiles(e.target.files)} />
              </div>
            </Field>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
              {nameField}
              {formatField}
              {delimiterField}
              {keyFieldField}
              {supplierField}
            </div>
          </>
        )}

        {/* ---- Upload via URL ---- */}
        {source === 'url' && (
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            {nameField}
            <Field label="File URL" hint={tutorial}>
              <input className={inputClass} value={sourceUrl} onChange={(e) => setSourceUrl(e.target.value)} placeholder="https://example.com/export/feed.csv" />
            </Field>
            {formatField}
            {delimiterField}
            {keyFieldField}
            {supplierField}
          </div>
        )}

        {/* ---- Upload via FTP / sFTP ---- */}
        {isFtp && (
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            {nameField}
            <Field label="FTP server">
              <input className={inputClass} value={ftpServer} onChange={(e) => setFtpServer(e.target.value)} placeholder="Server" />
            </Field>
            <Field label="FTP Username">
              <input className={inputClass} value={ftpUsername} onChange={(e) => setFtpUsername(e.target.value)} placeholder="Username" autoComplete="off" />
            </Field>
            <Field label="FTP User password">
              <input type="password" className={inputClass} value={ftpPassword} onChange={(e) => setFtpPassword(e.target.value)} placeholder="Password" autoComplete="new-password" />
            </Field>
            <Field label="FTP Port">
              <input className={inputClass} value={ftpPort} onChange={(e) => setFtpPort(e.target.value)} placeholder={source === 'sftp' ? '22' : '21'} inputMode="numeric" />
            </Field>
            <Field label="Absolute path to file" hint={tutorial}>
              <input className={inputClass} value={ftpPath} onChange={(e) => setFtpPath(e.target.value)} placeholder="Path" />
            </Field>
            {formatField}
            {delimiterField}
            {keyFieldField}
            {supplierField}
          </div>
        )}

        {/* Options */}
        <div className="space-y-2">
          <label className="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" className="accent-indigo-600" checked={firstRowHeaders} onChange={(e) => setFirstRowHeaders(e.target.checked)} />
            Use the first row as headers
          </label>
          {source === 'ftp' && (
            <label className="flex items-center gap-2 text-sm text-gray-700">
              <input type="checkbox" className="accent-indigo-600" checked={ftpPassiveMode} onChange={(e) => setFtpPassiveMode(e.target.checked)} />
              FTP passive mode
            </label>
          )}
          {isFtp && (
            <label className="flex items-center gap-2 text-sm text-gray-700">
              <input type="checkbox" className="accent-indigo-600" checked={removeAfterImport} onChange={(e) => setRemoveAfterImport(e.target.checked)} />
              Remove file from {source === 'sftp' ? 'sFTP' : 'FTP'} after import
            </label>
          )}
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
          <button type="submit" disabled={createMutation.isPending} className="px-5 py-2.5 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-black disabled:opacity-60">
            {createMutation.isPending ? 'Saving…' : 'Next Step'}
          </button>
        </div>
      </form>
    </div>
  )
}
