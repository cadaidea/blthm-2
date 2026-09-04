import { useEffect, useState } from "react";
import Storefront from "./components/Storefront";
import Panel from "./components/panel/Panel";

type Route = "home" | "panel";

const read = (): Route => (window.location.hash.startsWith("#/panel") ? "panel" : "home");

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

  return route === "panel" ? <Panel /> : <Storefront />;
}
