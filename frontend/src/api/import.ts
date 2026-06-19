import apiClient from './client'

export type ImportSource = 'direct' | 'url' | 'ftp' | 'sftp'
export type ImportFormat = 'xlsx' | 'xls' | 'csv' | 'xml'
export type KeyField = 'title' | 'sku' | 'barcode' | 'model' | 'id'

export interface ImportTemplate {
  id: number
  name: string
  supplier: string | null
  source: ImportSource
  sourceUrl: string | null
  fileFormat: ImportFormat
  keyField: KeyField
  firstRowHeaders: boolean
  zipArchive: boolean
  importTranslations: boolean
  delimiter: string | null
  ftpServer: string | null
  ftpUsername: string | null
  ftpPort: number | null
  ftpPath: string | null
  ftpPassiveMode: boolean
  removeAfterImport: boolean
  originalFilename: string | null
  createdAt: string
}

export interface NewImportPayload {
  name: string
  supplier?: string
  source: ImportSource
  sourceUrl?: string
  fileFormat: ImportFormat
  keyField: KeyField
  firstRowHeaders: boolean
  zipArchive: boolean
  importTranslations: boolean
  delimiter?: string
  ftpServer?: string
  ftpUsername?: string
  ftpPassword?: string
  ftpPort?: string
  ftpPath?: string
  ftpPassiveMode?: boolean
  removeAfterImport?: boolean
  file?: File | null
}

export const importApi = {
  list: async (): Promise<ImportTemplate[]> => {
    const response = await apiClient.get<ImportTemplate[]>('/import-templates')
    return response.data
  },

  create: async (payload: NewImportPayload): Promise<ImportTemplate> => {
    const form = new FormData()
    const append = (k: string, v: string | undefined | null) => {
      if (v !== undefined && v !== null && v !== '') form.append(k, v)
    }
    form.append('name', payload.name)
    append('supplier', payload.supplier)
    form.append('source', payload.source)
    append('sourceUrl', payload.sourceUrl)
    form.append('fileFormat', payload.fileFormat)
    form.append('keyField', payload.keyField)
    form.append('firstRowHeaders', String(payload.firstRowHeaders))
    form.append('zipArchive', String(payload.zipArchive))
    form.append('importTranslations', String(payload.importTranslations))
    append('delimiter', payload.delimiter)
    append('ftpServer', payload.ftpServer)
    append('ftpUsername', payload.ftpUsername)
    append('ftpPassword', payload.ftpPassword)
    append('ftpPort', payload.ftpPort)
    append('ftpPath', payload.ftpPath)
    if (payload.ftpPassiveMode !== undefined) form.append('ftpPassiveMode', String(payload.ftpPassiveMode))
    if (payload.removeAfterImport !== undefined) form.append('removeAfterImport', String(payload.removeAfterImport))
    if (payload.file) form.append('file', payload.file)

    // Clear the JSON default so axios detects the FormData and sets the
    // multipart Content-Type *with boundary* itself.
    const response = await apiClient.post<ImportTemplate>('/import-templates', form, {
      headers: { 'Content-Type': undefined },
    })
    return response.data
  },

  duplicate: async (id: number): Promise<ImportTemplate> => {
    const response = await apiClient.post<ImportTemplate>(`/import-templates/${id}/duplicate`)
    return response.data
  },

  download: async (id: number, name: string): Promise<void> => {
    const response = await apiClient.get(`/import-templates/${id}/download`, {
      responseType: 'blob',
    })
    const url = URL.createObjectURL(response.data as Blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${name.replace(/[^A-Za-z0-9._-]+/g, '_')}.json`
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
  },

  remove: async (id: number): Promise<void> => {
    await apiClient.delete(`/import-templates/${id}`)
  },
}

// The 10 suppliers content is imported from. Rename these to your real
// supplier names — they are the options shown in the import wizard.
export const SUPPLIERS: string[] = [
  'Supplier 1',
  'Supplier 2',
  'Supplier 3',
  'Supplier 4',
  'Supplier 5',
  'Supplier 6',
  'Supplier 7',
  'Supplier 8',
  'Supplier 9',
  'Supplier 10',
]

export const DELIMITERS: { value: string; label: string }[] = [
  { value: ',', label: 'Comma  ( , )' },
  { value: ';', label: 'Semicolon  ( ; )' },
  { value: '\t', label: 'Tab' },
  { value: '|', label: 'Pipe  ( | )' },
  { value: ' ', label: 'Space' },
]
