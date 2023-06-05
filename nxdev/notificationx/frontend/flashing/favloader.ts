import interval from "./webWorker";

/**@license
 *
 * favloader v. 0.4.4
 *
 * Vanilla JavaScript library for loading animation in favicon
 *
 * Copyright (c) 2018-2019 Jakub T. Jankiewicz <https://jcubic.pl/me>
 * Released under the MIT license
 *
 * Build: Wed, 20 Nov 2019 13:06:21 +0000
 */

function warn(message) {
    if (console && console.warn) {
        console.warn(message);
    } else {
        setTimeout(function () {
            throw new Error(message);
        }, 0);
    }
}

let ctx: CanvasRenderingContext2D,
    c: HTMLCanvasElement,
    link,
    links = [],
    initialized,
    settings;

function init(options) {
    if (document.readyState !== "complete") {
        setTimeout(init.bind(this, options), 100);
        return;
    }
    settings = Object.assign(
        {
            size: 16,
        },
        options
    );

    const _links = document.querySelectorAll('link[rel*="icon"]');
    for (var i = 0; i < _links.length; i++) {
        const link = _links[i];
        const clone = link.cloneNode(true); // clone the node and its subtree
        links.push(clone); // add the clone to the new array
    }

    if (!c) {
        c = document.createElement("canvas");
    }
    c.width     = c.height = settings.size;
    ctx         = c.getContext("2d");
    ctx.lineCap = "round";
    initialized = true;
}

function createIcon() {
    link = document.querySelector('link[rel*="icon"]');
    link = document.querySelector('link[rel*="icon"]');
    if (!link) {
        link = document.createElement("link");
        link.setAttribute("rel", "icon");
        document.head.appendChild(link);
        warn("No default icon found, restore state will not work");
    }
}

function removeIcon() {
    const links = document.querySelectorAll('link[rel*="icon"]');
    for (var i = 0; i < links.length; i++) {
        if (links[i] !== link) {
            links[i].remove(); // remove the original node from its parent
        }
    }
}

function restore() {
    if (link && link.parentNode) {
        link.parentNode.removeChild(link);
    }
    if (links.length) {
        for (var i = 0; i < links.length; i++) {
            document.head.appendChild(links[i]); // append the icon element to the document head
        }
    }
}

function animatePng(png) {
    if (!initialized) {
        return;
    }
    removeIcon();
    createIcon();

    if (png) {
        ctx.clearRect(0, 0, settings.size, settings.size);
        const image = new Image(); // create a new image object
        image.src = png; // set the image source
        image.onload = function () {
            // wait for the image to load
            ctx.drawImage(image, 0, 0, settings.size, settings.size); // draw the image at (0, 0)

            var newIcon,
                icon = document.querySelector('link[rel*="icon"]');
            (newIcon = <Element>icon.cloneNode(true)).setAttribute(
                "href",
                ctx.canvas.toDataURL()
            );
            icon.parentNode.replaceChild(newIcon, icon);
            link = newIcon;
        };
    }
}

export default {
    init      : init,
    animatePng: animatePng,
    stop      : restore,
    interval  : interval,
    version   : "0.4.4",
};
