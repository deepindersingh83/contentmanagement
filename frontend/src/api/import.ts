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
  file?: File | null
}

export const importApi = {
  list: async (): Promise<ImportTemplate[]> => {
    const response = await apiClient.get<ImportTemplate[]>('/import-templates')
    return response.data
  },

  create: async (payload: NewImportPayload): Promise<ImportTemplate> => {
    const form = new FormData()
    form.append('name', payload.name)
    if (payload.supplier) form.append('supplier', payload.supplier)
    form.append('source', payload.source)
    if (payload.sourceUrl) form.append('sourceUrl', payload.sourceUrl)
    form.append('fileFormat', payload.fileFormat)
    form.append('keyField', payload.keyField)
    form.append('firstRowHeaders', String(payload.firstRowHeaders))
    form.append('zipArchive', String(payload.zipArchive))
    form.append('importTranslations', String(payload.importTranslations))
    if (payload.file) form.append('file', payload.file)

    // Clear the JSON default so axios detects the FormData and sets the
    // multipart Content-Type *with boundary* itself.
    const response = await apiClient.post<ImportTemplate>('/import-templates', form, {
      headers: { 'Content-Type': undefined },
    })
    return response.data
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
