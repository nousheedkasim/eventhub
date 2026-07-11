import { create } from 'zustand';

interface AuthState {
  user: any | null;
  token: string | null;
  setAuth: (user: any, token: string) => void;
  setUser: (user: any) => void;
  logout: () => void;
}

export const useAuthStore = create<AuthState>((set) => {
  const token = typeof window !== 'undefined' ? localStorage.getItem('token') : null;
  const user = typeof window !== 'undefined' ? JSON.parse(localStorage.getItem('user') || 'null') : null;
  return {
    user,
    token,
    setAuth: (user, token) => {
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
      set({ user, token });
    },
    setUser: (user) => {
      localStorage.setItem('user', JSON.stringify(user));
      set({ user });
    },
    logout: () => {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      set({ user: null, token: null });
    },
  };
});

interface CartItem {
  ticket_type_id: number;
  qty: number;
  price: number;
  type_name: string;
}

interface CartState {
  items: CartItem[];
  eventId: number | null;
  addToCart: (item: CartItem, eventId: number) => void;
  removeFromCart: (ticketTypeId: number) => void;
  clearCart: () => void;
  getTotal: () => number;
}

export const useCartStore = create<CartState>((set, get) => ({
  items: [],
  eventId: null,
  addToCart: (item, eventId) => {
    const items = get().items;
    const existingIndex = items.findIndex(i => i.ticket_type_id === item.ticket_type_id);
    
    if (existingIndex >= 0) {
      items[existingIndex].qty += item.qty;
    } else {
      items.push(item);
    }
    
    set({ items, eventId });
  },
  removeFromCart: (ticketTypeId) => {
    set({ items: get().items.filter(i => i.ticket_type_id !== ticketTypeId) });
  },
  clearCart: () => set({ items: [], eventId: null }),
  getTotal: () => {
    return get().items.reduce((sum, item) => sum + (item.price * item.qty), 0);
  },
}));
