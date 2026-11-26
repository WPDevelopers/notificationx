import { toBase64 } from "js-base64";

/**
 * The builder Preview request: the current, unsaved builder values plus the
 * per-user preview nonce. Every preview (the frame, another page, a new tab)
 * sends exactly these two fields, so Preview::is_authorized_preview_request()
 * checks all of them the same way.
 */
export const previewPayload = (values: any, previewType: string) => ({
    "nx-preview": toBase64(JSON.stringify({ ...values, previewType })),
    // Without it the front end ignores the payload (Preview::is_authorized_preview_request()).
    "nx-preview-nonce": (window as any).notificationxTabs?.preview_nonce || "",
});

// A function that creates and returns a form element
export const createForm = (action: string, method: string, target: string, data: { [key: string]: string }) => {
    // Create a form element
    const form  = document.createElement("form");
    form.action = action;
    form.method = method;
    form.target = target;
    if ("_blank" === target) {
        // The new tab gets no handle on the builder window.
        form.setAttribute("rel", "noopener");
    }

    // Loop through the data object and create input elements
    for (let key in data) {
        const input = document.createElement("input");
        input.type  = "hidden";
        input.name  = key;
        input.value = data[key];
        // Append the input to the form
        form.appendChild(input);
    }

    // Return the form element
    return form;
};

/**
 * The Preview frame's name, which the form posts to. WordPress core renames
 * the frame's window on `?preview=true` pages (wp_post_preview_js():
 * `window.name = 'wp-preview-<id>'`), and a form posted to a name no window
 * has opens a new tab. Called right before every post into the frame.
 */
export const FRAME_NAME = "preview-modal-iframe";

export const resetFrameName = (iframe: HTMLIFrameElement | null) => {
    if (!iframe) {
        return;
    }
    iframe.name = FRAME_NAME;
    try {
        if (iframe.contentWindow && iframe.contentWindow.name !== FRAME_NAME) {
            iframe.contentWindow.name = FRAME_NAME;
        }
    } catch (e) {
        // A frame on another origin can't be renamed; it's re-mounted per page anyway.
    }
};

// A function that creates and submits a form to an iframe or a new tab
export const postToTarget = (action: string, method: string, target: string, data: { [key: string]: string }) => {
    // Create a form element with the given parameters
    const form = createForm(action, method, target, data);

    // Append the form to the document body
    document.body.appendChild(form);

    // Submit the form
    form.submit();

    // Remove the form from the document body
    document.body.removeChild(form);
};
