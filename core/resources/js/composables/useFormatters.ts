export function formatCurrency(value: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value);
}

export function formatDate(date: string | Date, format: 'short' | 'long' = 'short'): string {
  if (!date) return '';
  
  const dateObj = typeof date === 'string' ? new Date(date) : date;
  
  if (format === 'long') {
    return new Intl.DateTimeFormat('id-ID', {
      dateStyle: 'long',
    }).format(dateObj);
  }
  
  return new Intl.DateTimeFormat('id-ID', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).format(dateObj);
}

export function formatDateTime(date: string | Date): string {
  if (!date) return '';
  
  const dateObj = typeof date === 'string' ? new Date(date) : date;
  
  return new Intl.DateTimeFormat('id-ID', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(dateObj);
}

export function formatNumber(value: number, decimals: number = 2): string {
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  }).format(value);
}
