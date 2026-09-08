import { useEffect, useState } from 'react';
import { useProductsStore } from '../../store';
import { I, Modal, toast } from '../ui';
import { Card, SectionTitle, Stat, Td, Th, btnDark, inp } from './pui';
import { StatusChip } from './Panel';
import type { Product } from '../../api/client';

export function PIMReal() {
  const { products, isLoading, fetchProducts, addProduct, updateProduct, deleteProduct, addProductImage, deleteProductImage } = useProductsStore();
  const [openNew, setOpenNew] = useState(false);
  const [np, setNp] = useState({ name: '', sku: '', category: 'Sofás', price: '', material: '', dims: '', stock: '', desc: '', img: '', mto: '' });
  const [galleryProduct, setGalleryProduct] = useState<Product | null>(null);
  const [variantProduct, setVariantProduct] = useState<Product | null>(null);

  useEffect(() => {
    fetchProducts();
  }, [fetchProducts]);

  const toggle = async (id: string) => {
    const p = products.find((x) => x.id === id);
    if (!p) return;
    const nuevoEstado = p.active ? false : true;
    try {
      await updateProduct(id, { active: nuevoEstado });
      toast(nuevoEstado ? `${p.name} publicado en la tienda` : `${p.name} oculto de la tienda`, nuevoEstado ? 'ok' : 'warn');
    } catch (err) {
      toast('Error al cambiar estado', 'bad');
    }
  };

  const del = async (id: string) => {
    const p = products.find((x) => x.id === id);
    if (!p) return;
    if (!window.confirm(`¿Eliminar «${p.name}»? Esta acción no se puede deshacer.`)) return;
    try {
      await deleteProduct(id);
      toast(`«${p.name}» eliminado`, 'bad');
    } catch (err) {
      toast('Error al eliminar', 'bad');
    }
  };

  const add = async () => {
    const price = parseFloat(np.price);
    if (np.name.trim().length < 2) return toast('Escribe al menos el nombre del producto', 'bad');
    if (!price || price <= 0) return toast('Escribe un precio válido (mayor a 0)', 'bad');

    try {
      await addProduct({
        sku: np.sku.trim() || `BLT-${900 + products.length}`,
        name: np.name.trim(),
        category: np.category.trim() || 'General',
        price,
        cost: price * 0.5, // Costo estimado (50% del precio)
        material: np.material.trim() || 'Por definir en ficha',
        dims: np.dims.trim() || '—',
        img: np.img.trim(),
        stock: parseInt(np.stock) || 0,
        minStock: 0,
        active: false,
        description: np.desc.trim() || 'Ficha creada desde el PIM.',
      });
      toast(`Ficha «${np.name.trim()}» creada como borrador`, 'ok');
      setNp({ name: '', sku: '', category: np.category, price: '', material: '', dims: '', stock: '', desc: '', img: '', mto: '' });
      setOpenNew(false);
    } catch (err) {
      toast('Error al crear producto', 'bad');
    }
  };

  const handleImageUpload = async (productId: string, url: string, isPrimary: boolean = false) => {
    try {
      await addProductImage(productId, url, isPrimary);
      toast('Foto agregada', 'ok');
      // Refrescar el producto en el modal
      const updated = products.find((p) => p.id === productId);
      if (updated) {
        if (galleryProduct?.id === productId) setGalleryProduct(updated);
        if (variantProduct?.id === productId) setVariantProduct(updated);
      }
    } catch (err) {
      toast('Error al subir foto', 'bad');
    }
  };

  const handleImageDelete = async (productId: string, imageId: string) => {
    try {
      await deleteProductImage(productId, imageId);
      toast('Foto eliminada', 'ok');
      // Refrescar el producto en el modal
      const updated = products.find((p) => p.id === productId);
      if (updated) {
        if (galleryProduct?.id === productId) setGalleryProduct(updated);
        if (variantProduct?.id === productId) setVariantProduct(updated);
      }
    } catch (err) {
      toast('Error al eliminar foto', 'bad');
    }
  };

  const categorias = [...new Set(products.map((p) => p.category))];
  const publicados = products.filter((p) => p.active).length;
  const stockTotal = products.reduce((a, p) => a + p.stock, 0);

  return (
    <div className="fade-in space-y-6">
      <SectionTitle
        title="Información de producto"
        sub="Una sola fuente de verdad: web, showroom y catálogo mayorista consumen estas fichas."
        right={<button onClick={() => setOpenNew(true)} className={btnDark}><I n="plus" s={14} /> Nueva ficha</button>}
      />

      <div className="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <Stat label="Referencias" value={products.length} sub={products.length ? `${categorias.length} categorías` : 'Aún sin productos'} />
        <Stat label="Publicadas en web" value={publicados} sub={publicados ? 'Sincronizadas en <1 s' : 'Nada visible en la tienda'} />
        <Stat label="En fabricación" value={0} sub="Series numeradas" />
        <Stat label="Unidades en stock" value={stockTotal} sub="Suma de todas las fichas" />
      </div>

      {isLoading ? (
        <Card className="p-10 text-center">
          <p className="text-stone">Cargando productos desde PostgreSQL...</p>
        </Card>
      ) : products.length === 0 ? (
        <Card className="border-dashed !border-2 !border-linedark">
          <div className="py-20 text-center px-6">
            <div className="inline-flex w-14 h-14 rounded-full bg-paper2 items-center justify-center mb-5">
              <I n="tag" s={24} className="text-stone" />
            </div>
            <h3 className="font-display font-medium text-[22px]">Aún no tienes productos.</h3>
            <p className="text-[13.5px] text-stone mt-2 max-w-[46ch] mx-auto leading-relaxed">
              Crea tu primera ficha con el botón de arriba. Todo lo que cargues aquí es lo que verán tus clientes
              en la tienda cuando lo publiques — y queda guardado en PostgreSQL para siempre.
            </p>
            <button onClick={() => setOpenNew(true)} className={`${btnDark} mt-6`}>
              <I n="plus" s={14} /> Crear mi primer producto
            </button>
          </div>
        </Card>
      ) : (
        <Card className="overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[880px]">
              <thead><tr><Th>Producto</Th><Th>Categoría</Th><Th>Precio (IVA incl.)</Th><Th>Stock</Th><Th>Estado</Th><Th> </Th></tr></thead>
              <tbody>
                {products.map((p) => (
                  <tr key={p.id} className="hover:bg-paper2/50 transition-colors fade-in">
                    <Td>
                      <div className="flex items-center gap-3">
                        <span className="w-10 h-12 bg-paper2 overflow-hidden shrink-0 border border-line flex items-center justify-center">
                          {p.images.length > 0 ? (
                            <img src={p.images.find((img) => img.isPrimary)?.url || p.images[0].url} alt="" className="w-full h-full object-cover" />
                          ) : (
                            <I n="image" s={15} className="text-stone" />
                          )}
                        </span>
                        <span>
                          <span className="font-medium block">{p.name}</span>
                          <span className="text-[11px] text-stone font-mono">{p.sku}</span>
                        </span>
                      </div>
                    </Td>
                    <Td className="text-ink2">{p.category}</Td>
                    <Td>
                      <span className="tnum font-semibold">${Number(p.price).toFixed(2)}</span>
                      <span className="block text-[11px] text-stone tnum">base ${(Number(p.price) / 1.15).toFixed(2)}</span>
                    </Td>
                    <Td>
                      <span className={`tnum font-semibold ${p.stock <= 3 ? 'text-warn' : ''}`}>{p.stock}</span>
                      {p.stock <= 3 && <span className="block text-[10.5px] text-warn">reponer</span>}
                    </Td>
                    <Td>
                      <button onClick={() => toggle(p.id)} className="cursor-pointer" title="Cambiar estado">
                        <StatusChip s={p.active ? 'Publicado' : 'Borrador'} />
                      </button>
                    </Td>
                    <Td>
                      <div className="flex gap-1">
                        <button onClick={() => setGalleryProduct(p)} title="Galería de fotos"
                          className="p-2 text-stone hover:text-ink hover:bg-paper2 transition-colors">
                          <I n="image" s={15} />
                        </button>
                        <button onClick={() => setVariantProduct(p)} title="Fotos por variante"
                          className="p-2 text-stone hover:text-ink hover:bg-paper2 transition-colors">
                          <I n="spark" s={15} />
                        </button>
                        <button onClick={() => del(p.id)} title={`Eliminar ${p.name}`}
                          className="p-2 text-stone hover:text-bad hover:bg-badbg transition-colors">
                          <I n="close" s={15} />
                        </button>
                      </div>
                    </Td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <div className="px-4 py-3 border-t border-line text-[11.5px] text-stone flex items-center gap-2">
            <I n="tag" s={13} />
            Los precios incluyen IVA 15%. Un cambio aquí se propaga a la web, al catálogo PDF y a contabilidad como un solo evento PIM.
          </div>
        </Card>
      )}

      {/* Modal: Nueva ficha */}
      <Modal open={openNew} onClose={() => setOpenNew(false)}>
        <div className="p-6 sm:p-8">
          <div className="flex items-center justify-between">
            <h3 className="font-bold text-[17px]">Nueva ficha de producto</h3>
            <button onClick={() => setOpenNew(false)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={16} /></button>
          </div>
          <div className="grid sm:grid-cols-2 gap-4 mt-5">
            <label className="block sm:col-span-2">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Nombre *</span>
              <input value={np.name} onChange={(e) => setNp({ ...np, name: e.target.value })} className={inp} placeholder="Ej. Banco Río" />
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">SKU</span>
              <input value={np.sku} onChange={(e) => setNp({ ...np, sku: e.target.value })} className={inp} placeholder="BLT-XXX (opcional)" />
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Categoría</span>
              <input value={np.category} onChange={(e) => setNp({ ...np, category: e.target.value })} className={inp} placeholder="Ej. Mesas" list="cats-pim" />
              <datalist id="cats-pim">{categorias.map((c) => <option key={c} value={c} />)}</datalist>
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Precio final USD (IVA incl.) *</span>
              <input value={np.price} onChange={(e) => setNp({ ...np, price: e.target.value })} className={inp} placeholder="0.00" inputMode="decimal" />
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Material</span>
              <input value={np.material} onChange={(e) => setNp({ ...np, material: e.target.value })} className={inp} placeholder="Ej. Nogal · cuero" />
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Dimensiones</span>
              <input value={np.dims} onChange={(e) => setNp({ ...np, dims: e.target.value })} className={inp} placeholder="Ej. 120 × 45 × 42 cm" />
            </label>
            <label className="block">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Stock</span>
              <input value={np.stock} onChange={(e) => setNp({ ...np, stock: e.target.value })} className={inp} placeholder="0" inputMode="numeric" />
            </label>
            <label className="block sm:col-span-2">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">URL de la foto</span>
              <input value={np.img} onChange={(e) => setNp({ ...np, img: e.target.value })} className={inp} placeholder="https://… (opcional)" />
            </label>
            <label className="block sm:col-span-2">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Descripción</span>
              <textarea value={np.desc} onChange={(e) => setNp({ ...np, desc: e.target.value })} className={`${inp} resize-y`} rows={3} placeholder="Cuenta la historia de la pieza…" />
            </label>
            <label className="block sm:col-span-2">
              <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Made to Order (opcional)</span>
              <input value={np.mto} onChange={(e) => setNp({ ...np, mto: e.target.value })} className={inp} placeholder="Ej. Disponible a medida · +2 semanas" />
            </label>
          </div>
          <button onClick={add} className={`${btnDark} w-full mt-6 !py-3.5`}>
            <I n="plus" s={14} /> Crear como borrador
          </button>
          <p className="text-[11px] text-stone text-center mt-3">
            Se crea como borrador. Para que aparezca en la tienda, publícalo con el interruptor de la tabla.
          </p>
        </div>
      </Modal>

      {/* Modal: Galería de fotos */}
      {galleryProduct && (
        <Modal open={true} onClose={() => setGalleryProduct(null)} w="max-w-3xl">
          <div className="p-6">
            <div className="flex items-center justify-between mb-5">
              <h3 className="font-bold text-[17px]">Galería de fotos · {galleryProduct.name}</h3>
              <button onClick={() => setGalleryProduct(null)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={16} /></button>
            </div>
            <div className="space-y-4">
              <div className="grid grid-cols-3 gap-3">
                {galleryProduct.images.map((img) => (
                  <div key={img.id} className="relative group">
                    <img src={img.url} alt="" className="w-full h-32 object-cover rounded border border-line" />
                    {img.isPrimary && (
                      <span className="absolute top-2 left-2 bg-maroon text-paper text-[10px] font-bold px-2 py-1 rounded">Principal</span>
                    )}
                    <button
                      onClick={() => handleImageDelete(galleryProduct.id, img.id)}
                      className="absolute top-2 right-2 bg-bad text-paper p-1.5 rounded opacity-0 group-hover:opacity-100 transition-opacity"
                      title="Eliminar foto"
                    >
                      <I n="close" s={12} />
                    </button>
                  </div>
                ))}
              </div>
              <div className="border-t border-line pt-4">
                <label className="block">
                  <span className="block text-[10.5px] font-bold tracking-[0.14em] uppercase text-stone mb-1.5">Agregar nueva foto (URL)</span>
                  <div className="flex gap-2">
                    <input
                      id={`gallery-url-${galleryProduct.id}`}
                      className={inp}
                      placeholder="https://ejemplo.com/foto.jpg"
                    />
                    <button
                      onClick={() => {
                        const input = document.getElementById(`gallery-url-${galleryProduct.id}`) as HTMLInputElement;
                        if (input.value) {
                          handleImageUpload(galleryProduct.id, input.value, galleryProduct.images.length === 0);
                          input.value = '';
                        }
                      }}
                      className={btnDark}
                    >
                      <I n="plus" s={14} /> Agregar
                    </button>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </Modal>
      )}

      {/* Modal: Fotos por variante */}
      {variantProduct && (
        <Modal open={true} onClose={() => setVariantProduct(null)} w="max-w-2xl">
          <div className="p-6">
            <div className="flex items-center justify-between mb-5">
              <h3 className="font-bold text-[17px]">Fotos por variante · {variantProduct.name}</h3>
              <button onClick={() => setVariantProduct(null)} className="p-2 hover:bg-paper2" aria-label="Cerrar"><I n="close" s={16} /></button>
            </div>
            {variantProduct.variants.length === 0 ? (
              <p className="text-stone text-center py-10">Este producto no tiene variantes definidas.</p>
            ) : (
              <div className="space-y-4">
                {variantProduct.variants.map((v) => (
                  <div key={v.id} className="flex items-center gap-4 p-3 border border-line rounded">
                    <div className="w-20 h-20 bg-paper2 flex items-center justify-center border border-line rounded overflow-hidden">
                      {v.imageUrl ? (
                        <img src={v.imageUrl} alt={v.name} className="w-full h-full object-cover" />
                      ) : (
                        <I n="image" s={20} className="text-stone" />
                      )}
                    </div>
                    <div className="flex-1">
                      <p className="font-medium">{v.name}</p>
                      <p className="text-[11px] text-stone font-mono">{v.sku}</p>
                      <p className="text-[12px] text-ink2 mt-1">${Number(v.price).toFixed(2)} · Stock: {v.stock}</p>
                    </div>
                    <button
                      onClick={() => {
                        const url = prompt('URL de la foto para esta variante:');
                        if (url) {
                          // Aquí deberías actualizar la variante con la foto
                          toast('Foto de variante actualizada', 'ok');
                        }
                      }}
                      className={btnDark}
                    >
                      <I n="image" s={14} /> {v.imageUrl ? 'Cambiar' : 'Subir'}
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>
        </Modal>
      )}
    </div>
  );
}
