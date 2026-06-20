import apiClient from './client'
import type { Ref } from './products'

export interface Warehouse {
  id: number
  code: string
  name: string
  country: string | null
  region: string | null
  postcode: string | null
  supplier: Ref | null
}

export interface WarehousePayload {
  name: string
  code: string
  country?: string | null
  region?: string | null
  postcode?: string | null
  supplierId?: number | null
}

export const warehousesApi = {
  list: async (): Promise<Warehouse[]> => (await apiClient.get<Warehouse[]>('/warehouses')).data,
  create: async (p: WarehousePayload): Promise<Warehouse> => (await apiClient.post<Warehouse>('/warehouses', p)).data,
  update: async (id: number, p: WarehousePayload): Promise<Warehouse> => (await apiClient.put<Warehouse>(`/warehouses/${id}`, p)).data,
  remove: async (id: number): Promise<void> => { await apiClient.delete(`/warehouses/${id}`) },
}
