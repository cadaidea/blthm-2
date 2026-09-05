import { useEffect, useState } from "react";
import Storefront from "./components/Storefront";
import Panel from "./components/panel/Panel";

type Route = "home" | "dash";

/* bletia.ec/dash y /dash/login → panel (con puerta de login).
   #/panel se conserva como alias interno. */
const read = (): Route =>
  window.location.hash.startsWith("#/panel") || window.location.hash.startsWith("#/dash")
    ? "dash"
    : "home";

export default function App() {
  const [route, setRoute] = useState<Route>(read);

  useEffect(() => {
    const onHash = () => {
      const r = read();
      setRoute((prev) => {
        if (prev !== r) window.scrollTo(0, 0);
        return r;
      });
    };
    window.addEventListener("hashchange", onHash);
    return () => window.removeEventListener("hashchange", onHash);
  }, []);

  /* la tienda pública siempre va en claro; el contraste oscuro es solo del dash */
  useEffect(() => {
    if (route === "home") document.documentElement.classList.remove("dark");
  }, [route]);

  return route === "dash" ? <Panel /> : <Storefront />;
}
