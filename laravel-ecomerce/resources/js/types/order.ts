export type OrderItem = {
  id: number | null
  product_id: number | null
  product_name: string
  product_sku: string
  unit_price: string | number
  quantity: number
  line_total: string | number
}

export type Order = {
  id: number
  number: string
  user_id: number
  status: string
  status_label: string
  payment_status: string
  payment_status_label: string
  payment_method: string
  payment_method_label: string
  subtotal: string | number
  total: string | number
  customer_name: string
  customer_email: string
  customer_phone: string
  shipping_address: string
  shipping_city: string
  note: string | null
  payment_reference: string | null
  paid_at: string | null
  created_at: string | null
  can_pay: boolean
  items: OrderItem[]
}

export type PaymentMethodOption = {
  value: string
  label: string
}
