export type AuthUser = {
  id: number
  name: string
  email: string
}

export type SharedAuth = {
  user: AuthUser | null
}

export type FlashMessages = {
  success?: string | null
  error?: string | null
}
