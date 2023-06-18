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
    links = [],
    iconType = ["icon", "mask-icon", "apple-touch-icon"],
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

    iconType.forEach((icon) => {
        const link = document.querySelector(`link[rel="${icon}"]`);
        if (!link) {
            const link = document.createElement("link");
            link.setAttribute("rel", icon);
            link.setAttribute("color", "#000000");
            document.head.appendChild(link);
            // warn("No default icon found, restore state will not work");
        }
    });

}

function removeIcon() {
    const links = document.querySelectorAll('link[rel*="icon"]');
    for (var i = 0; i < links.length; i++) {
        links[i].remove(); // remove the original node from its parent
    }
}

function restore() {
    iconType.forEach((icon) => {
        const link = document.querySelector(`link[rel="${icon}"]`);
        if (link && link.parentNode) {
            link.parentNode.removeChild(link);
        }
    });

    if (links.length) {
        for (var i = 0; i < links.length; i++) {
            document.head.appendChild(links[i]); // append the icon element to the document head
        }
    }
}

function animatePng(png) {
    // create a new promise object
    const promise = new Promise((resolve, reject) => {
        if (!initialized) {
            // reject the promise with an error message
            reject(new Error("Function not initialized"));
        } else {
            // removeIcon();
            createIcon();

            if (png) {
                ctx.clearRect(0, 0, settings.size, settings.size);
                const image = new Image(); // create a new image object
                image.onload = function () {
                    // wait for the image to load
                    ctx.drawImage(image, 0, 0, settings.size, settings.size); // draw the image at (0, 0)
                    const dataURL = ctx.canvas.toDataURL();

                    iconType.forEach((icon) => {
                        const link = document.querySelector(
                            `link[rel="${icon}"]`
                        );
                        const newIcon = <Element>link.cloneNode(true);
                        newIcon.setAttribute("href", dataURL);
                        link.parentNode.replaceChild(newIcon, link);
                    });

                    // resolve the promise with the dataURL as the value
                    resolve(dataURL);
                };
                // handle the error case
                image.onerror = function () {
                    // reject the promise with an error message
                    reject(new Error("Image loading failed"));
                };
                image.src = png; // set the image source
            }
            else{
                reject(new Error("No png provided"));
            }
        }
    });
    // return the promise object
    return promise;
}

export default {
    init      : init,
    animatePng: animatePng,
    restore   : restore,
    removeIcon: removeIcon,
    interval  : interval,
    version   : "0.4.4",
};
