import { useEffect, useState } from "react";
import Storefront from "./components/Storefront";
import Panel from "./components/panel/Panel";
import { ArticuloPage, BlogPage, CategoriaPage, PaginaPage, ProductoPage } from "./components/StorePages";

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
  if (head === "blog") {
    if (a === "categoria" && b) return <BlogPage key={hash} kind="categoria" value={b} />;
    if (a === "etiqueta" && b) return <BlogPage key={hash} kind="etiqueta" value={b} />;
    if (a === "autor" && b) return <BlogPage key={hash} kind="autor" value={b} />;
    return <BlogPage key={hash} />;
  }

  return <Storefront />;
}
