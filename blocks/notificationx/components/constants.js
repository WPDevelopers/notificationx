import { __ } from "@wordpress/i18n";
import { Dashicon } from "@wordpress/components";

// the consts defined here should be unique from one another
export const NX_MARGIN = "nxMargin";
export const NX_PADDING = "nxPadding";
export const NX_BORDER = "nxBorder";

export const TEXT_ALIGN = [
  { label: __(<Dashicon icon={"editor-alignleft"} />), value: "left" },
  { label: __(<Dashicon icon={"editor-aligncenter"} />), value: "center" },
  { label: __(<Dashicon icon={"editor-alignright"} />), value: "right" },
];
export const WRAPPER_ALIGN = [
  { label: __(<Dashicon icon={"editor-alignleft"} />), value: "flex-start" },
  { label: __(<Dashicon icon={"editor-aligncenter"} />), value: "center" },
  { label: __(<Dashicon icon={"editor-alignright"} />), value: "flex-end" },
];