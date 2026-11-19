/**
 * Width of the Preview popup for each device button (PreviewModal content style).
 */
export const DEVICE_WIDTHS: { [device: string]: number } = {
    desktop: 1440,
    tablet: 768,
    phone: 480,
};

// The overlay's side padding (15px each side).
const OVERLAY_PADDING = 30;

export type ToolbarSize = "wide" | "narrow" | "compact";

/**
 * How wide the popup's top bar is: the device width, or less on a narrow window.
 */
export const popupWidth = (previewType: string, windowWidth: number) =>
    Math.min(DEVICE_WIDTHS[previewType] ?? DEVICE_WIDTHS.desktop, Math.max(0, windowWidth - OVERLAY_PADDING));

/**
 * The toolbar on the right of the top bar must stay clear of the centred
 * device buttons and of the close button:
 * - wide: "Page: name", the new-tab icon, add-on buttons with labels;
 * - narrow (the 820px builder, the tablet frame): the page name only, icons;
 * - compact (the phone frame): icons only.
 */
export const toolbarSize = (width: number): ToolbarSize => {
    if (width >= 1000) {
        return "wide";
    }
    return width >= 720 ? "narrow" : "compact";
};
