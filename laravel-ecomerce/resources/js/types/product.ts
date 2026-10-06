export const ProductStatus = {
  Active: 'active',
  Draft: 'draft',
  Archived: 'archived',
} as const

export type ProductStatusValue = (typeof ProductStatus)[keyof typeof ProductStatus]

export type Product = {
  id: number
  name: string
  slug: string
  sku: string
  short_description: string | null
  description: string | null
  price: string | number
  compare_at_price: string | number | null
  stock_quantity: number
  category: string
  brand: string | null
  image_url: string | null
  status: ProductStatusValue | string
  rating_avg: string | number
  sold_count: number
  is_available: boolean
  has_discount: boolean
}

export type ProductPage = {
  data: Product[]
  meta: {
    total: number
    page: number
    per_page: number
    last_page: number
  }
}

export function formatPrice(price: string | number): string {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
  }).format(Number(price))
}

export function discountPercent(product: Product): number | null {
  if (!product.has_discount || product.compare_at_price === null) {
    return null
  }

  const price = Number(product.price)
  const compare = Number(product.compare_at_price)

  if (compare <= price) {
    return null
  }

  return Math.round(((compare - price) / compare) * 100)
}
