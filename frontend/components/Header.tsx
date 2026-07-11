'use client';

import { useAuthStore } from '@/lib/store';
import { useCartStore } from '@/lib/store';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useEffect } from 'react';

export default function Header() {
  const { user, logout } = useAuthStore();
  const { items } = useCartStore();
  const router = useRouter();

  useEffect(() => {
    console.log('Header - User state:', user);
    console.log('Header - Cart items:', items);
  }, [user, items]);

  const handleLogout = () => {
    logout();
    router.push('/');
  };

  const cartCount = items.reduce((sum, item) => sum + item.qty, 0);

  return (
    <header className="bg-white shadow-md sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          <Link href="/" className="text-2xl font-bold text-blue-600">
            EventHub
          </Link>

          <nav className="flex items-center space-x-4">
            <Link href="/events" className="text-gray-700 hover:text-blue-600 font-medium">
              Events
            </Link>

            {user ? (
              <>
                <div className="flex items-center space-x-4">
                  <div className="flex items-center space-x-2 bg-blue-50 px-3 py-2 rounded-lg">
                    <span className="text-gray-700 font-medium">
                      {user.name}
                    </span>
                    <span className="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded">
                      {user.type}
                    </span>
                  </div>

                  {user.type === 'vendor' && (
                    <Link href="/vendor" className="text-gray-700 hover:text-blue-600 font-medium">
                      Dashboard
                    </Link>
                  )}

                  {user.type === 'admin' && (
                    <Link href="/admin" className="text-gray-700 hover:text-blue-600 font-medium">
                      Admin
                    </Link>
                  )}

                  <Link href="/checkout" className="relative text-gray-700 hover:text-blue-600">
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    {cartCount > 0 && (
                      <span className="absolute -top-2 -right-2 bg-red-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                        {cartCount}
                      </span>
                    )}
                  </Link>

                  <button
                    onClick={handleLogout}
                    className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 font-medium"
                  >
                    Logout
                  </button>
                </div>
              </>
            ) : (
              <>
                <Link href="/login" className="text-gray-700 hover:text-blue-600 font-medium">
                  Login
                </Link>
                <Link
                  href="/login"
                  className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 font-medium"
                >
                  Register
                </Link>
              </>
            )}
          </nav>
        </div>
      </div>
    </header>
  );
}
