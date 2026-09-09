// Cliente API para conectar frontend con backend
// Reemplaza localStorage con llamadas HTTP reales

const API_URL = import.meta.env.VITE_API_URL || '';

// Productos de fallback para cuando la API no está disponible
const FALLBACK_PRODUCTS: Product[] = [
  { id: '1', sku: 'BLT-001', name: 'Butaca Aura', slug: 'butaca-aura', category: 'Sillones', price: 1190, cost: 640, stock: 6, minStock: 2, active: true, material: 'Nogal americano · Bouclé crudo', dims: '78 × 82 × 74 cm', img: '/assets/img/hero.jpg', description: 'Curva continua tallada en nogal, cojín en bouclé de lana. Ensamble de espiga a la vista, sin herrajes. Serie numerada y firmada por el maestro de taller.', images: [], variants: [], createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
  { id: '2', sku: 'BLT-002', name: 'Sofá Nudo', slug: 'sofa-nudo', category: 'Sofás', price: 2890, cost: 1310, stock: 4, minStock: 2, active: true, material: 'Lino avena · Patas de nogal', dims: '228 × 95 × 80 cm', img: '/assets/img/sofa.jpg', description: 'Tres cuerpos, plumón recuperado y espuma de alta densidad. Funda removible lavable. Estructura garantizada por 10 años.', images: [], variants: [], createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
  { id: '3', sku: 'BLT-003', name: 'Mesa Raíz', slug: 'mesa-raiz', category: 'Mesas', price: 1750, cost: 850, stock: 3, minStock: 2, active: true, material: 'Roble europeo ahumado', dims: '200 × 100 × 75 cm', img: '/assets/img/mesa.jpg', description: 'Tablero monolítico de roble ahumado con aceite natural. Patas cónicas torneadas a mano. Admite extensión a 260 cm bajo pedido.', images: [], variants: [], createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
  { id: '4', sku: 'BLT-004', name: 'Estantería Trama', slug: 'estanteria-trama', category: 'Almacenaje', price: 1320, cost: 650, stock: 8, minStock: 2, active: true, material: 'Nogal · Entrepaños de 18 mm', dims: '160 × 32 × 190 cm', img: '/assets/img/estanteria.jpg', description: 'Sistema modular de entrepaños flotantes. Soporta 40 kg por nivel. Anclaje antisísmico incluido para pared.', images: [], variants: [], createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
  { id: '5', sku: 'BLT-005', name: 'Silla Vela', slug: 'silla-vela', category: 'Sillas', price: 420, cost: 150, stock: 24, minStock: 5, active: true, material: 'Nogal · Asiento de cuero vegetalizado', dims: '46 × 52 × 81 cm', img: '/assets/img/silla.jpg', description: 'Respaldo curvado al vapor, una sola pieza. Cuero de curtiembre local con sello ambiental. Apilable de a dos.', images: [], variants: [], createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
  { id: '6', sku: 'BLT-006', name: 'Cama Duna', slug: 'cama-duna', category: 'Descanso', price: 2140, cost: 1050, stock: 5, minStock: 2, active: true, material: 'Nogal · Cabecero tapizado marfil', dims: '205 × 190 × 95 cm', img: '/assets/img/cama.jpg', description: 'Plataforma baja sin boxspring. Cabecero flotante tapizado en lino marfil. Ensamble sin herramientas en 10 minutos.', images: [], variants: [], createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
  { id: '7', sku: 'BLT-007', name: 'Centro Nube', slug: 'centro-nube', category: 'Centros', price: 940, cost: 450, stock: 7, minStock: 2, active: true, material: 'Nogal americano · Base cilíndrica', dims: '90 × 90 × 35 cm', img: '/assets/img/centro.jpg', description: 'Mesa de centro de nogal con base escultórica torneada en una sola pieza. Borde biselado a mano y acabado al aceite.', images: [], variants: [], createdAt: new Date().toISOString(), updatedAt: new Date().toISOString() },
];

// ============================================
// TIPOS
// ============================================

export interface User {
  id: string;
  email: string;
  name: string;
  role: 'GERENCIA' | 'VENTAS' | 'TALLER' | 'LOGISTICA' | 'CONTABILIDAD' | 'ADMIN';
  active: boolean;
  lastLoginAt?: string;
}

export interface LoginResponse {
  token: string;
  user: User;
}

export interface Customer {
  id: string;
  code: string;
  name: string;
  email?: string;
  phone?: string;
  taxId?: string;
  address?: string;
  city?: string;
  notes?: string;
  createdAt: string;
  updatedAt: string;
}

export interface Product {
  id: string;
  sku: string;
  name: string;
  slug?: string;
  description?: string;
  category: string;
  price: number;
  cost: number;
  stock: number;
  minStock: number;
  active: boolean;
  material?: string;
  dims?: string;
  img?: string;
  images: ProductImage[];
  variants: ProductVariant[];
  createdAt: string;
  updatedAt: string;
  // Propiedades adicionales para compatibilidad con frontend
  state?: string;
  origin?: string;
  lead?: string;
  desc?: string;
  channels?: string[];
  mto?: string;
}

export interface ProductImage {
  id: string;
  url: string;
  order: number;
  isPrimary: boolean;
}

export interface ProductVariant {
  id: string;
  name: string;
  sku: string;
  price: number;
  stock: number;
  imageUrl?: string;
  attributes?: Record<string, any>;
}

export interface SalesOrder {
  id: string;
  code: string;
  customerId: string;
  status: 'PENDIENTE' | 'CONFIRMADO' | 'EN_PRODUCCION' | 'LISTO' | 'ENVIADO' | 'ENTREGADO' | 'CANCELADO';
  subtotal: number;
  tax: number;
  total: number;
  notes?: string;
  customer: Customer;
  items: SalesOrderItem[];
  createdAt: string;
  updatedAt: string;
}

export interface SalesOrderItem {
  id: string;
  productId: string;
  quantity: number;
  unitPrice: number;
  subtotal: number;
  product: Product;
}

// ============================================
// HELPERS
// ============================================

function getToken(): string | null {
  return localStorage.getItem('bletia-token');
}

function setToken(token: string): void {
  localStorage.setItem('bletia-token', token);
}

function clearToken(): void {
  localStorage.removeItem('bletia-token');
}

function getHeaders(): HeadersInit {
  const token = getToken();
  return {
    'Content-Type': 'application/json',
    ...(token && { Authorization: `Bearer ${token}` }),
  };
}

async function handleResponse<T>(response: Response): Promise<T> {
  if (response.status === 401) {
    clearToken();
    window.location.href = '/login';
    throw new Error('No autorizado');
  }

  if (!response.ok) {
    const error = await response.json().catch(() => ({ error: 'Error desconocido' }));
    throw new Error(error.error || 'Error en la petición');
  }

  return response.json();
}

// ============================================
// AUTENTICACIÓN
// ============================================

export const auth = {
  async login(email: string, password: string): Promise<LoginResponse> {
    const response = await fetch(`${API_URL}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
    });

    const data = await handleResponse<LoginResponse>(response);
    setToken(data.token);
    localStorage.setItem('bletia-user', JSON.stringify(data.user));
    return data;
  },

  async logout(): Promise<void> {
    const token = getToken();
    if (token) {
      await fetch(`${API_URL}/auth/logout`, {
        method: 'POST',
        headers: getHeaders(),
      }).catch(() => {}); // Ignorar errores
    }
    clearToken();
    localStorage.removeItem('bletia-user');
  },

  async me(): Promise<User> {
    const response = await fetch(`${API_URL}/auth/me`, {
      headers: getHeaders(),
    });
    return handleResponse<User>(response);
  },

  getCurrentUser(): User | null {
    const userStr = localStorage.getItem('bletia-user');
    if (!userStr) return null;
    try {
      return JSON.parse(userStr);
    } catch {
      return null;
    }
  },

  isAuthenticated(): boolean {
    return !!getToken();
  },
};

// ============================================
// USUARIOS
// ============================================

export const users = {
  async getAll(): Promise<User[]> {
    const response = await fetch(`${API_URL}/users`, {
      headers: getHeaders(),
    });
    return handleResponse<User[]>(response);
  },

  async create(data: {
    email: string;
    password: string;
    name: string;
    role: User['role'];
  }): Promise<User> {
    const response = await fetch(`${API_URL}/users`, {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify(data),
    });
    return handleResponse<User>(response);
  },
};

// ============================================
// CLIENTES
// ============================================

export const customers = {
  async getAll(): Promise<Customer[]> {
    const response = await fetch(`${API_URL}/customers`, {
      headers: getHeaders(),
    });
    return handleResponse<Customer[]>(response);
  },

  async getById(id: string): Promise<Customer> {
    const response = await fetch(`${API_URL}/customers/${id}`, {
      headers: getHeaders(),
    });
    return handleResponse<Customer>(response);
  },

  async create(data: Omit<Customer, 'id' | 'code' | 'createdAt' | 'updatedAt'>): Promise<Customer> {
    const response = await fetch(`${API_URL}/customers`, {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify(data),
    });
    return handleResponse<Customer>(response);
  },

  async update(id: string, data: Partial<Customer>): Promise<Customer> {
    const response = await fetch(`${API_URL}/customers/${id}`, {
      method: 'PUT',
      headers: getHeaders(),
      body: JSON.stringify(data),
    });
    return handleResponse<Customer>(response);
  },

  async delete(id: string): Promise<void> {
    const response = await fetch(`${API_URL}/customers/${id}`, {
      method: 'DELETE',
      headers: getHeaders(),
    });
    await handleResponse(response);
  },
};

// ============================================
// PRODUCTOS
// ============================================

export const products = {
  async getAll(): Promise<Product[]> {
    // Si no hay API configurada, usar productos de fallback
    if (!API_URL) {
      return FALLBACK_PRODUCTS;
    }
    
    try {
      const response = await fetch(`${API_URL}/products`, {
        headers: getHeaders(),
      });
      return handleResponse<Product[]>(response);
    } catch (error) {
      // Si la API falla, usar productos de fallback
      console.warn('API no disponible, usando productos de fallback');
      return FALLBACK_PRODUCTS;
    }
  },

  async getById(id: string): Promise<Product> {
    const response = await fetch(`${API_URL}/products/${id}`, {
      headers: getHeaders(),
    });
    return handleResponse<Product>(response);
  },

  async create(data: Omit<Product, 'id' | 'images' | 'variants' | 'createdAt' | 'updatedAt'>): Promise<Product> {
    const response = await fetch(`${API_URL}/products`, {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify(data),
    });
    return handleResponse<Product>(response);
  },

  async update(id: string, data: Partial<Product>): Promise<Product> {
    const response = await fetch(`${API_URL}/products/${id}`, {
      method: 'PUT',
      headers: getHeaders(),
      body: JSON.stringify(data),
    });
    return handleResponse<Product>(response);
  },

  async delete(id: string): Promise<void> {
    const response = await fetch(`${API_URL}/products/${id}`, {
      method: 'DELETE',
      headers: getHeaders(),
    });
    await handleResponse(response);
  },

  async addImage(productId: string, url: string, isPrimary: boolean = false): Promise<ProductImage> {
    const response = await fetch(`${API_URL}/products/${productId}/images`, {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify({ url, isPrimary }),
    });
    return handleResponse<ProductImage>(response);
  },

  async deleteImage(productId: string, imageId: string): Promise<void> {
    const response = await fetch(`${API_URL}/products/${productId}/images/${imageId}`, {
      method: 'DELETE',
      headers: getHeaders(),
    });
    await handleResponse(response);
  },
};

// ============================================
// ÓRDENES DE VENTA
// ============================================

export const orders = {
  async getAll(): Promise<SalesOrder[]> {
    const response = await fetch(`${API_URL}/orders`, {
      headers: getHeaders(),
    });
    return handleResponse<SalesOrder[]>(response);
  },

  async getById(id: string): Promise<SalesOrder> {
    const response = await fetch(`${API_URL}/orders/${id}`, {
      headers: getHeaders(),
    });
    return handleResponse<SalesOrder>(response);
  },

  async create(data: {
    customerId: string;
    items: Array<{
      productId: string;
      quantity: number;
      unitPrice: number;
    }>;
    notes?: string;
  }): Promise<SalesOrder> {
    const response = await fetch(`${API_URL}/orders`, {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify(data),
    });
    return handleResponse<SalesOrder>(response);
  },

  async updateStatus(id: string, status: SalesOrder['status']): Promise<SalesOrder> {
    const response = await fetch(`${API_URL}/orders/${id}/status`, {
      method: 'PUT',
      headers: getHeaders(),
      body: JSON.stringify({ status }),
    });
    return handleResponse<SalesOrder>(response);
  },

  async delete(id: string): Promise<void> {
    const response = await fetch(`${API_URL}/orders/${id}`, {
      method: 'DELETE',
      headers: getHeaders(),
    });
    await handleResponse(response);
  },
};

// ============================================
// PROVEEDORES Y ÓRDENES DE COMPRA
// ============================================

export interface Supplier {
  id: string;
  code: string;
  name: string;
  email?: string;
  phone?: string;
  taxId?: string;
  address?: string;
  notes?: string;
  createdAt: string;
  updatedAt: string;
  _count?: {
    purchaseOrders: number;
  };
}

export interface PurchaseOrder {
  id: string;
  code: string;
  supplierId: string;
  status: 'BORRADOR' | 'ENVIADA' | 'PARCIAL' | 'RECIBIDA' | 'CANCELADA';
  subtotal: number;
  tax: number;
  total: number;
  expectedDate?: string;
  notes?: string;
  createdAt: string;
  updatedAt: string;
  supplier?: Supplier;
  items: PurchaseOrderItem[];
}

export interface PurchaseOrderItem {
  id: string;
  productId: string;
  quantity: number;
  unitCost: number;
  subtotal: number;
  product?: Product;
}

export const suppliers = {
  async getAll(): Promise<Supplier[]> {
    const response = await fetch(`${API_URL}/suppliers`, {
      headers: getHeaders(),
    });
    return handleResponse<Supplier[]>(response);
  },

  async create(data: {
    code: string;
    name: string;
    email?: string;
    phone?: string;
    taxId?: string;
    address?: string;
    notes?: string;
  }): Promise<Supplier> {
    const response = await fetch(`${API_URL}/suppliers`, {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify(data),
    });
    return handleResponse<Supplier>(response);
  },

  async update(id: string, data: Partial<Supplier>): Promise<Supplier> {
    const response = await fetch(`${API_URL}/suppliers/${id}`, {
      method: 'PUT',
      headers: getHeaders(),
      body: JSON.stringify(data),
    });
    return handleResponse<Supplier>(response);
  },

  async delete(id: string): Promise<void> {
    const response = await fetch(`${API_URL}/suppliers/${id}`, {
      method: 'DELETE',
      headers: getHeaders(),
    });
    await handleResponse(response);
  },
};

export const purchaseOrders = {
  async getAll(): Promise<PurchaseOrder[]> {
    const response = await fetch(`${API_URL}/purchase-orders`, {
      headers: getHeaders(),
    });
    return handleResponse<PurchaseOrder[]>(response);
  },

  async create(data: {
    supplierId: string;
    items: Array<{
      productId: string;
      quantity: number;
      unitCost: number;
    }>;
    expectedDate?: string;
    notes?: string;
  }): Promise<PurchaseOrder> {
    const response = await fetch(`${API_URL}/purchase-orders`, {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify(data),
    });
    return handleResponse<PurchaseOrder>(response);
  },

  async updateStatus(id: string, status: PurchaseOrder['status']): Promise<PurchaseOrder> {
    const response = await fetch(`${API_URL}/purchase-orders/${id}/status`, {
      method: 'PUT',
      headers: getHeaders(),
      body: JSON.stringify({ status }),
    });
    return handleResponse<PurchaseOrder>(response);
  },

  async delete(id: string): Promise<void> {
    const response = await fetch(`${API_URL}/purchase-orders/${id}`, {
      method: 'DELETE',
      headers: getHeaders(),
    });
    await handleResponse(response);
  },
};

// ============================================
// BODEGAS E INVENTARIOS
// ============================================

export interface Warehouse {
  id: string;
  code: string;
  name: string;
  address?: string;
  active: boolean;
  createdAt: string;
  updatedAt: string;
  _count?: {
    inventoryMoves: number;
  };
}

export interface InventoryMove {
  id: string;
  productId: string;
  warehouseId: string;
  type: 'ENTRADA' | 'SALIDA' | 'AJUSTE' | 'TRANSFERENCIA';
  quantity: number;
  reference?: string;
  notes?: string;
  createdAt: string;
  product?: Product;
  warehouse?: Warehouse;
}

export const warehouses = {
  async getAll(): Promise<Warehouse[]> {
    const response = await fetch(`${API_URL}/warehouses`, {
      headers: getHeaders(),
    });
    return handleResponse<Warehouse[]>(response);
  },

  async create(data: { code: string; name: string; address?: string }): Promise<Warehouse> {
    const response = await fetch(`${API_URL}/warehouses`, {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify(data),
    });
    return handleResponse<Warehouse>(response);
  },

  async update(id: string, data: Partial<Warehouse>): Promise<Warehouse> {
    const response = await fetch(`${API_URL}/warehouses/${id}`, {
      method: 'PUT',
      headers: getHeaders(),
      body: JSON.stringify(data),
    });
    return handleResponse<Warehouse>(response);
  },
};

export const inventory = {
  async getMoves(filters?: { productId?: string; warehouseId?: string; type?: string }): Promise<InventoryMove[]> {
    const params = new URLSearchParams();
    if (filters?.productId) params.append('productId', filters.productId);
    if (filters?.warehouseId) params.append('warehouseId', filters.warehouseId);
    if (filters?.type) params.append('type', filters.type);

    const response = await fetch(`${API_URL}/inventory-moves?${params}`, {
      headers: getHeaders(),
    });
    return handleResponse<InventoryMove[]>(response);
  },

  async createMove(data: {
    productId: string;
    warehouseId: string;
    type: 'ENTRADA' | 'SALIDA' | 'AJUSTE' | 'TRANSFERENCIA';
    quantity: number;
    reference?: string;
    notes?: string;
  }): Promise<InventoryMove> {
    const response = await fetch(`${API_URL}/inventory-moves`, {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify(data),
    });
    return handleResponse<InventoryMove>(response);
  },

  async getProductStock(productId: string): Promise<{ id: string; sku: string; name: string; stock: number; minStock: number }> {
    const response = await fetch(`${API_URL}/inventory/${productId}`, {
      headers: getHeaders(),
    });
    return handleResponse(response);
  },
};

// ============================================
// HEALTH CHECK
// ============================================

export const health = {
  async check(): Promise<boolean> {
    try {
      const response = await fetch('http://localhost:3000/health');
      return response.ok;
    } catch {
      return false;
    }
  },
};
