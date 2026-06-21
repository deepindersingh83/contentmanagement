import apiClient from './client'
import type { Ref } from './products'

export interface Offer {
  id: number
  supplier: Ref | null
  product: { id: number; sku: string; title: string } | null
  supplierRefCode: string
  supplierSku: string | null
  matchKey: string | null
  costPrice: string
  currency: string
  stockQuantity: number
  weightValue: string | null
  weightUnit: string | null
  weightGrams: string | null
  title: string | null
  categoryRaw: string | null
  imageUrl: string | null
  leadTimeDays: number | null
  isPrimary: boolean
  updatedAt: string
}

export interface OfferList {
  items: Offer[]
  total: number
  page: number
  perPage: number
}

export interface OfferPayload {
  supplierId?: number
  supplierRefCode?: string
  supplierSku?: string | null
  costPrice?: string
  currency?: string
  stockQuantity?: number
  weightValue?: string | null
  weightUnit?: string | null
  title?: string | null
  categoryRaw?: string | null
  imageUrl?: string | null
  leadTimeDays?: number | null
}

export interface OfferFilters {
  supplierId?: number | ''
  unmatched?: '' | '0' | '1'
  q?: string
}

export const offersApi = {
  list: async (filters: OfferFilters = {}, page = 1, perPage = 25): Promise<OfferList> => {
    const response = await apiClient.get<OfferList>('/supplier-products', {
      params: { ...filters, page, perPage },
    })
    return response.data
  },
  create: async (payload: OfferPayload): Promise<Offer> =>
    (await apiClient.post<Offer>('/supplier-products', payload)).data,
  update: async (id: number, payload: OfferPayload): Promise<Offer> =>
    (await apiClient.put<Offer>(`/supplier-products/${id}`, payload)).data,
  link: async (id: number, productId: number | null, isPrimary?: boolean): Promise<Offer> =>
    (await apiClient.post<Offer>(`/supplier-products/${id}/link`, { productId, isPrimary })).data,
  remove: async (id: number): Promise<void> => {
    await apiClient.delete(`/supplier-products/${id}`)
  },
}
