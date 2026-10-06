export type CartItem = {
  product_id: number
  name: string
  sku: string
  price: string | number
  image_url: string | null
  stock_quantity: number
  quantity: number
  line_total: string | number
  is_available: boolean
}

export type Cart = {
  items: CartItem[]
  item_count: number
  subtotal: string | number
  is_empty: boolean
}
