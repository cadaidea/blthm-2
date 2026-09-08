import { useState } from 'react';
import { useAuthStore } from '../store';
import { I } from '../components/ui';

export function LoginAPI() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  
  const { login, isAuthenticated } = useAuthStore();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      await login(email, password);
      // Redirigir al dashboard después del login exitoso
      window.location.href = '#/dash';
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al iniciar sesión');
    } finally {
      setLoading(false);
    }
  };

  // Si ya está autenticado, redirigir al dashboard
  if (isAuthenticated) {
    window.location.href = '#/dash';
    return null;
  }

  return (
    <div className="min-h-screen bg-paper flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        <div className="bg-card border border-line rounded-lg shadow-lg p-8">
          {/* Logo */}
          <div className="text-center mb-8">
            <h1 className="font-display font-bold text-3xl text-ink">BLETIA</h1>
            <p className="text-stone text-sm mt-2">Sistema ERP · Inicia sesión</p>
          </div>

          {/* Formulario */}
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-semibold text-ink mb-2">
                Correo electrónico
              </label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full px-4 py-2.5 border border-linedark bg-card text-ink rounded-md focus:outline-none focus:border-ink transition-colors"
                placeholder="admin@bletia.ec"
                required
              />
            </div>

            <div>
              <label className="block text-sm font-semibold text-ink mb-2">
                Contraseña
              </label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full px-4 py-2.5 border border-linedark bg-card text-ink rounded-md focus:outline-none focus:border-ink transition-colors"
                placeholder="••••••••"
                required
              />
            </div>

            {error && (
              <div className="p-3 bg-badbg border border-bad/30 rounded-md">
                <p className="text-bad text-sm flex items-center gap-2">
                  <I n="alert" s={16} />
                  {error}
                </p>
              </div>
            )}

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-ink text-paper py-3 rounded-md font-semibold hover:bg-maroon transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              {loading ? (
                <>
                  <span className="animate-spin">⏳</span>
                  Iniciando sesión...
                </>
              ) : (
                <>
                  <I n="user" s={18} />
                  Iniciar sesión
                </>
              )}
            </button>
          </form>

          {/* Info */}
          <div className="mt-6 pt-6 border-t border-line">
            <p className="text-xs text-stone text-center">
              <strong>Credenciales de prueba:</strong><br />
              admin@bletia.ec / admin123
            </p>
          </div>

          {/* Estado de conexión */}
          <div className="mt-4 p-3 bg-paper2 rounded-md">
            <p className="text-xs text-stone flex items-center gap-2">
              <span className="w-2 h-2 bg-ok rounded-full animate-pulse"></span>
              API: http://localhost:3000
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
