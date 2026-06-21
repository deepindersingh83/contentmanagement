import apiClient from './client'

export interface Ref {
  id: number
  name: string
}

export interface Product {
  id: number
  sku: string
  gtin: string | null
  title: string
  shortDescription: string | null
  longDescription: string | null
  status: 'active' | 'draft' | 'archived'
  weightGrams: string | null
  sellPrice: string | null
  primaryImageUrl: string | null
  category: Ref | null
  brand: Ref | null
  updatedAt: string
}

export interface ProductPayload {
  sku: string
  title: string
  gtin?: string | null
  shortDescription?: string | null
  longDescription?: string | null
  status?: string
  weightGrams?: string | null
  sellPrice?: string | null
  primaryImageUrl?: string | null
  categoryId?: number | null
  brandId?: number | null
}

export interface ProductList {
  items: Product[]
  total: number
  page: number
  perPage: number
}

export const productsApi = {
  list: async (q = '', page = 1, perPage = 25): Promise<ProductList> => {
    const response = await apiClient.get<ProductList>('/products', { params: { q, page, perPage } })
    return response.data
  },
  create: async (payload: ProductPayload): Promise<Product> => {
    const response = await apiClient.post<Product>('/products', payload)
    return response.data
  },
  update: async (id: number, payload: ProductPayload): Promise<Product> => {
    const response = await apiClient.put<Product>(`/products/${id}`, payload)
    return response.data
  },
  remove: async (id: number): Promise<void> => {
    await apiClient.delete(`/products/${id}`)
  },
}

export const categoriesApi = {
  list: async (): Promise<Ref[]> => (await apiClient.get<Ref[]>('/categories')).data,
  create: async (name: string): Promise<Ref> => (await apiClient.post<Ref>('/categories', { name })).data,
}

export const brandsApi = {
  list: async (): Promise<Ref[]> => (await apiClient.get<Ref[]>('/brands')).data,
  create: async (name: string): Promise<Ref> => (await apiClient.post<Ref>('/brands', { name })).data,
}

export const PRODUCT_STATUSES = ['active', 'draft', 'archived']
