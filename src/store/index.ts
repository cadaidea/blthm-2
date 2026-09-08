import { create } from 'zustand';
import { auth, users, customers, products, type User, type Customer, type Product } from '../api/client';

// ============================================
// STORE DE AUTENTICACIÓN
// ============================================

interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  checkAuth: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set) => ({
  user: auth.getCurrentUser(),
  isAuthenticated: auth.isAuthenticated(),
  isLoading: false,

  login: async (email: string, password: string) => {
    set({ isLoading: true });
    try {
      const data = await auth.login(email, password);
      set({ user: data.user, isAuthenticated: true, isLoading: false });
    } catch (error) {
      set({ isLoading: false });
      throw error;
    }
  },

  logout: async () => {
    await auth.logout();
    set({ user: null, isAuthenticated: false });
  },

  checkAuth: async () => {
    if (!auth.isAuthenticated()) {
      set({ user: null, isAuthenticated: false });
      return;
    }
    try {
      const user = await auth.me();
      set({ user, isAuthenticated: true });
    } catch {
      set({ user: null, isAuthenticated: false });
    }
  },
}));

// ============================================
// STORE DE USUARIOS
// ============================================

interface UsersState {
  users: User[];
  isLoading: boolean;
  fetchUsers: () => Promise<void>;
}

export const useUsersStore = create<UsersState>((set) => ({
  users: [],
  isLoading: false,

  fetchUsers: async () => {
    set({ isLoading: true });
    try {
      const usersList = await users.getAll();
      set({ users: usersList, isLoading: false });
    } catch (error) {
      set({ isLoading: false });
      throw error;
    }
  },
}));

// ============================================
// STORE DE CLIENTES
// ============================================

interface CustomersState {
  customers: Customer[];
  isLoading: boolean;
  fetchCustomers: () => Promise<void>;
  addCustomer: (customer: Omit<Customer, 'id' | 'code' | 'createdAt' | 'updatedAt'>) => Promise<Customer>;
  updateCustomer: (id: string, data: Partial<Customer>) => Promise<Customer>;
  deleteCustomer: (id: string) => Promise<void>;
}

export const useCustomersStore = create<CustomersState>((set, get) => ({
  customers: [],
  isLoading: false,

  fetchCustomers: async () => {
    set({ isLoading: true });
    try {
      const customersList = await customers.getAll();
      set({ customers: customersList, isLoading: false });
    } catch (error) {
      set({ isLoading: false });
      throw error;
    }
  },

  addCustomer: async (data) => {
    const customer = await customers.create(data);
    set({ customers: [...get().customers, customer] });
    return customer;
  },

  updateCustomer: async (id, data) => {
    const customer = await customers.update(id, data);
    set({ customers: get().customers.map((c) => (c.id === id ? customer : c)) });
    return customer;
  },

  deleteCustomer: async (id) => {
    await customers.delete(id);
    set({ customers: get().customers.filter((c) => c.id !== id) });
  },
}));

// ============================================
// STORE DE PRODUCTOS
// ============================================

interface ProductsState {
  products: Product[];
  isLoading: boolean;
  fetchProducts: () => Promise<void>;
  addProduct: (product: Omit<Product, 'id' | 'images' | 'variants' | 'createdAt' | 'updatedAt'>) => Promise<Product>;
  updateProduct: (id: string, data: Partial<Product>) => Promise<Product>;
  deleteProduct: (id: string) => Promise<void>;
  addProductImage: (productId: string, url: string, isPrimary?: boolean) => Promise<void>;
  deleteProductImage: (productId: string, imageId: string) => Promise<void>;
}

export const useProductsStore = create<ProductsState>((set, get) => ({
  products: [],
  isLoading: false,

  fetchProducts: async () => {
    set({ isLoading: true });
    try {
      const productsList = await products.getAll();
      set({ products: productsList, isLoading: false });
    } catch (error) {
      set({ isLoading: false });
      throw error;
    }
  },

  addProduct: async (data) => {
    const product = await products.create(data);
    set({ products: [...get().products, product] });
    return product;
  },

  updateProduct: async (id, data) => {
    const product = await products.update(id, data);
    set({ products: get().products.map((p) => (p.id === id ? product : p)) });
    return product;
  },

  deleteProduct: async (id) => {
    await products.delete(id);
    set({ products: get().products.filter((p) => p.id !== id) });
  },

  addProductImage: async (productId, url, isPrimary = false) => {
    const image = await products.addImage(productId, url, isPrimary);
    set({
      products: get().products.map((p) =>
        p.id === productId ? { ...p, images: [...p.images, image] } : p
      ),
    });
  },

  deleteProductImage: async (productId, imageId) => {
    await products.deleteImage(productId, imageId);
    set({
      products: get().products.map((p) =>
        p.id === productId ? { ...p, images: p.images.filter((img) => img.id !== imageId) } : p
      ),
    });
  },
}));
