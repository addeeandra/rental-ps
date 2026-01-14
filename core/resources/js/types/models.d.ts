
export interface Partner {
    id: number;
    code: string;
    type: string;
    name: string;
    email: string | null;
    phone: string | null;
    mobile_phone: string | null;
    address_line_1: string | null;
    address_line_2: string | null;
    city: string | null;
    province: string | null;
    postal_code: string | null;
    country: string | null;
    gmap_url: string | null;
    website: string | null;
    notes: string | null;
    created_at: string;
}

export interface CompanySetting {
    id: number;
    company_name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    city: string | null;
    postal_code: string | null;
    website: string | null;
    logo_path: string | null;
    logo_url: string | null;
    tax_number: string | null;
    invoice_number_prefix: string;
    invoice_default_terms: string | null;
    invoice_default_notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface Category {
    id: number;
    code: string;
    name: string;
    description: string | null;
    products_count?: number;
    created_at: string;
}

export type RentalDuration = 'hour' | 'day' | 'week' | 'month';

export interface Product {
    id: number;
    code: string;
    name: string;
    description: string | null;
    category_id: number;
    category?: Category;
    sales_price: number;
    rental_price: number;
    uom: string;
    rental_duration: RentalDuration;
    created_at: string;
    deleted_at?: string | null;
}

export type InvoiceStatus = 'unpaid' | 'partial' | 'paid' | 'void';
export type OrderType = 'sales' | 'rental';

export interface InvoiceItem {
    id: number;
    invoice_id: number;
    product_id: number | null;
    product?: Product;
    description: string;
    quantity: number;
    unit_price: number;
    total: number;
    sort_order: number;
    display_description?: string;
    created_at: string;
}

export interface Invoice {
    id: number;
    invoice_number: string;
    reference_number: string | null;
    partner_id: number;
    partner?: Partner;
    user_id: number;
    user?: {
        id: number;
        name: string;
        email: string;
    };
    invoice_date: string;
    due_date: string;
    order_type: OrderType;
    rental_start_date: string | null;
    rental_end_date: string | null;
    delivery_time: string | null;
    return_time: string | null;
    notes: string | null;
    terms: string | null;
    status: InvoiceStatus;
    subtotal: number;
    discount_amount: number;
    tax_amount: number;
    shipping_fee: number;
    total_amount: number;
    paid_amount: number;
    balance: number;
    is_editable: boolean;
    rental_days: number | null;
    invoice_items?: InvoiceItem[];
    created_at: string;
    updated_at: string;
}