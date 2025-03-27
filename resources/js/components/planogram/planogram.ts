// Types for planogram components

export interface Image {
    id: string;
    url: string;
    image_url: string;
}

export interface Product {
    id: string;
    image_url: string;
    name: string;
    height: number;
    width: number;
    depth: number;
    image: Image;
}

export interface Section {
    id: string;
    name: string;
    width: number;
    height: number;
    position: number;
    shelves: Shelf[];
}

export interface Shelf {
    id: string;
    section_id: string;
    name?: string;
    position: number;
    height: number;
    depth: number;
    section: Section;
    segments: Segment[];
    settings: LayerSettings;
}

export interface Segment {
    id: string;
    width: number;
    ordering: number;
    quantity: number;
    spacing: number;
    position: number;
    status: string;
    layer: Layer;
}

export interface LayerSettings {
    horizontal_alignment: 'left' | 'center' | 'right';
    vertical_alignment: 'top' | 'middle' | 'bottom';
    justify: boolean;
}

export interface Layer {
    id: string;
    product_id: string;
    product_name?: string;
    product_image?: string;
    height: number;
    spacing: number;
    quantity: number;
    status: string;
    settings?: string;
    product: Product;
    horizontal_alignment?: 'left' | 'center' | 'right';
    vertical_alignment?: 'top' | 'middle' | 'bottom';
    is_justified?: boolean;
}

export interface ContextMenu {
    show: boolean;
    position: {
        x: number;
        y: number;
    };
    section: Section | null;
}

export interface ShelfIndex {
    id: string;
    position: number;
    index: number;
}

export interface Gondola {
    id: string;
    name: string;
    height: number;
    width: number;
    base_height: number;
    shelf_height: number;
    hole_spacing: number;
    thickness: number;
    scale_factor: number;
}
