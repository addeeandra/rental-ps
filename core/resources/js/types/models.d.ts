
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

// Inventory types
export interface Warehouse {
    id: number;
    code: string;
    name: string;
    address: string | null;
    is_active: boolean;
    total_items?: number;
    created_at: string;
    deleted_at?: string | null;
}

export interface InventoryItem {
    id: number;
    sku: string;
    name: string;
    owner_id: number;
    owner?: Partner;
    unit: string | null;
    cost: number;
    default_share_percent: number;
    is_active: boolean;
    total_stock?: number;
    stock_levels?: StockLevel[];
    created_at: string;
    deleted_at?: string | null;
}

export interface ProductComponent {
    id: number;
    product_id: number;
    inventory_item_id: number;
    inventory_item?: InventoryItem;
    slot: number;
    qty_per_product: number;
    created_at: string;
    updated_at: string;
}

export interface StockLevel {
    inventory_item_id: number;
    warehouse_id: number;
    inventory_item?: InventoryItem;
    warehouse?: Warehouse;
    qty_on_hand: number;
    qty_reserved: number;
    min_threshold: number;
    available_qty?: number;
    updated_at: string | null;
}

export type StockMovementReason = 'adjustment' | 'sale' | 'return' | 'transfer' | 'opname';

export interface StockMovement {
    id: number;
    inventory_item_id: number;
    inventory_item?: InventoryItem;
    warehouse_id: number;
    warehouse?: Warehouse;
    quantity: number;
    reason: StockMovementReason;
    ref_type: string | null;
    ref_id: number | null;
    notes: string | null;
    created_by: number | null;
    created_by_user?: {
        id: number;
        name: string;
    };
    created_at: string;
}

export interface InvoiceItemComponent {
    id: number;
    invoice_item_id: number;
    inventory_item_id: number;
    inventory_item?: InventoryItem;
    warehouse_id: number;
    warehouse?: Warehouse;
    owner_id: number;
    owner?: Partner;
    qty: number;
    share_percent: number;
    share_amount: number;
    created_at: string;
    updated_at: string;
}

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
    product_components?: ProductComponent[];
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
    invoice_item_components?: InvoiceItemComponent[];
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