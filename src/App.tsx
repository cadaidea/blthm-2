import { useEffect, useState } from "react";
import Storefront from "./components/Storefront";
import Panel from "./components/panel/Panel";
import { ArticuloPage, BlogPage, CategoriaPage, PaginaPage, ProductoPage } from "./components/StorePages";
import { CuentaPage, PedidoTrackingPage } from "./components/CustomerAccount";
import { BLOG_CATEGORIAS, slugDe } from "./data";

/* categorías del blog en formato slug — para la ruta /{categoria}/{articulo} */
const CAT_SLUGS = BLOG_CATEGORIAS.map(slugDe);

/* Enrutador por hash (bletia.ec):
   #/dash · #/panel            → panel interno (puerta de login)
   #/producto/{slug}           → ficha de producto
   #/categoria/{slug}          → colección
   #/blog[/categoria|etiqueta|autor/{x}] → diario + filtros
   #/articulo/{slug}           → artículo
   #/pagina/{slug}             → políticas / contacto / nosotros
   cualquier otra cosa         → home de la tienda                      */
const read = (): string => window.location.hash || "#/";

export default function App() {
  const [hash, setHash] = useState<string>(read);

  useEffect(() => {
    const onHash = () => {
      const h = read();
      setHash((prev) => {
        if (prev !== h) window.scrollTo(0, 0);
        return h;
      });
    };
    window.addEventListener("hashchange", onHash);
    return () => window.removeEventListener("hashchange", onHash);
  }, []);

  const isDash = hash.startsWith("#/panel") || hash.startsWith("#/dash");

  /* la tienda pública siempre va en claro; el contraste oscuro es solo del dash */
  useEffect(() => {
    if (!isDash) document.documentElement.classList.remove("dark");
  }, [isDash]);

  if (isDash) return <Panel />;

  const parts = hash.replace(/^#\//, "").split("/").filter(Boolean);
  const [head, a, b] = parts;

  if (head === "producto" && a) return <ProductoPage key={hash} slug={a} />;
  if (head === "categoria" && a) return <CategoriaPage key={hash} slug={a} />;
  if (head === "articulo" && a) return <ArticuloPage key={hash} slug={a} />;
  if (head === "pagina" && a) return <PaginaPage key={hash} slug={a} />;
  if (head === "cuenta") return <CuentaPage key={hash} />;
  if (head === "pedido" && a) return <PedidoTrackingPage key={hash} codigo={a} />;
  if (head === "blog") {
    if (a === "categoria" && b) return <BlogPage key={hash} kind="categoria" value={b} />;
    if (a === "tag" && b) return <BlogPage key={hash} kind="etiqueta" value={b} />;
    if (a === "autor" && b) return <BlogPage key={hash} kind="autor" value={b} />;
    return <BlogPage key={hash} />;
  }
  /* artículo: /{categoría}/{slug-del-artículo} — igual que en bletia.ec */
  if (CAT_SLUGS.includes(head) && a) return <ArticuloPage key={hash} slug={a} />;

  return <Storefront />;
}
