import { useEffect } from "react";
import { createPortal } from "react-dom";

const Shortcode = ({children, position}) => {
  const mount = document.getElementById(position);
  if(mount){
    const el = document.createElement("div");

    // @ts-ignore
    useEffect(() => {
      mount.appendChild(el);
      return () => mount.removeChild(el);
    }, [el, mount]);

    return createPortal(children, mount)
  }
  return null;
};

export default Shortcode;