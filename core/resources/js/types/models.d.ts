
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