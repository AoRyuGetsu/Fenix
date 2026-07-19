import { TemplateRef } from '@angular/core';

export interface GtColumn<T = any> {
  key: string
  label: string
  width?: string
  format?: 'text' | 'badge' | 'boolean' | 'date' | 'template'
  template?: TemplateRef<{ $implicit: T; value: any }>
  badgeMap?: Record<string, { class: string; label: string }>
  class?: string
}

export interface GtAction<T = any> {
  key: string
  icon: string
  label: string
  btnClass?: string
  visible?: (item: T) => boolean
}

export interface GtDetailField {
  label: string
  key: string
  format?: 'text' | 'badge' | 'boolean' | 'date'
  colspan?: string
  badgeMap?: Record<string, { class: string; label: string }>
}

export interface GtDetailConfig {
  fields?: GtDetailField[]
  template?: TemplateRef<{ $implicit: any }>
}

export interface GtPagination {
  page: number
  perPage: number
  total: number
}
