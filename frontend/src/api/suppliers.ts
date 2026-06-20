import apiClient from './client'

export interface Supplier {
  id: number
  name: string
  code: string
  active: boolean
  defaultCurrency: string
  defaultWeightUnit: string
  website: string | null
  contactEmail: string | null
  notes: string | null
  createdAt: string
}

export interface SupplierPayload {
  name: string
  code?: string
  active?: boolean
  defaultCurrency?: string
  defaultWeightUnit?: string
  website?: string | null
  contactEmail?: string | null
  notes?: string | null
}

export const suppliersApi = {
  list: async (): Promise<Supplier[]> => {
    const response = await apiClient.get<Supplier[]>('/suppliers')
    return response.data
  },

  create: async (payload: SupplierPayload): Promise<Supplier> => {
    const response = await apiClient.post<Supplier>('/suppliers', payload)
    return response.data
  },

  update: async (id: number, payload: SupplierPayload): Promise<Supplier> => {
    const response = await apiClient.put<Supplier>(`/suppliers/${id}`, payload)
    return response.data
  },

  remove: async (id: number): Promise<void> => {
    await apiClient.delete(`/suppliers/${id}`)
  },
}

export const WEIGHT_UNITS = ['g', 'kg', 'lb', 'oz']
