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



var hidden, visibilityChange;
if (typeof document.hidden !== "undefined") {
    // Opera 12.10 and Firefox 18 and later support
    hidden = "hidden";
    visibilityChange = "visibilitychange";
// @ts-ignore
} else if (typeof document.msHidden !== "undefined") {
    hidden = "msHidden";
    visibilityChange = "msvisibilitychange";
// @ts-ignore
} else if (typeof document.webkitHidden !== "undefined") {
    hidden = "webkitHidden";
    visibilityChange = "webkitvisibilitychange";
}

function warn(message) {
    if (console && console.warn) {
        console.warn(message);
    } else {
        setTimeout(function () {
            throw new Error(message);
        }, 0);
    }
}

var ctx:CanvasRenderingContext2D,
    c,
    link,
    links = [],
    id,
    progress = 0,
    duration,
    initialized,
    settings,
    gif,
    initial_turns = -0.25,
    interval_id,
    step;

function init(options) {
    if (document.readyState !== "complete") {
        setTimeout(init.bind(this, options), 100);
        return;
    }
    settings = Object.assign(
        {
            size: 16,
            radius: 6,
            thickness: 2,
            color: "#0F60A8",
            duration: 5000,
        },
        options
    );

    const _links = document.querySelectorAll('link[rel*="icon"]');
    for (var i = 0; i < _links.length; i++) {
        const link  = _links[i];
        const clone = link.cloneNode(true);  // clone the node and its subtree
        links.push(clone); // add the clone to the new array
    }

    clear();

    if (settings.gif) {
        if (typeof parseGIF === "undefined") {
            throw new Error(
                "parseGIF not defined, please include parseGIF.js file"
            );
        }
        parseGIF(settings.gif).then(function (data) {
            gif = data;
        });
    } else {
        if (!c) {
            c = document.createElement("canvas");
        }
        c.width = c.height = settings.size;
        ctx = c.getContext("2d");

        ctx.lineCap = "round";
        ctx.lineWidth = settings.thickness;
        ctx.strokeStyle = settings.color;
        duration = settings.duration;
    }
    initialized = true;
}

function clear() {
    if (interval_id) {
        interval.clear(interval_id);
        interval_id = null;
    }
}

function createIcon() {
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
        if(links[i] !== link){
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
    clear();
}

function animate() {
    if (!initialized) {
        setTimeout(animate, 100);
        return;
    }
    if (interval_id) {
        return;
    }
    removeIcon();
    createIcon();
    progress = 0;
    if (settings.gif && parseGIF) {
        if (!gif) {
            setTimeout(animate, 100);
            return;
        }
        interval_id = interval.set(animateGIF, 20);
    } else {
        interval_id = interval.set(draw, 20);
    }
}

function animatePng(png) {
    if (!initialized) {
        setTimeout(animate, 100);
        return;
    }
    if (interval_id) {
        return;
    }
    removeIcon();
    createIcon();
    if (png) {
        ctx.clearRect(0, 0, settings.size, settings.size);
        const image  = new Image();  // create a new image object
        image.src    = png;          // set the image source
        image.onload = function() { // wait for the image to load
            ctx.drawImage(image, 0, 0, settings.size, settings.size);  // draw the image at (0, 0)
            update(ctx.canvas.toDataURL());
        };
    }
}

function animateGIF() {
    progress++;
    if (progress >= gif.uris.length) {
        progress = 0;
    }
    update(gif.uris[progress]);
}

function update(dataURI) {
    var newIcon,
        icon = document.querySelector('link[rel*="icon"]');
    (newIcon = <Element>icon.cloneNode(true)).setAttribute("href", dataURI);
    icon.parentNode.replaceChild(newIcon, icon);
    link = newIcon;
}

function draw() {
    ctx.clearRect(0, 0, settings.size, settings.size);
    if (typeof settings.frame === "function") {
        settings.frame(ctx);
    }
    update(ctx.canvas.toDataURL());
}

export default {
    init      : init,
    start     : animate,
    animatePng: animatePng,
    stop      : restore,
    interval  : interval,
    version   : "0.4.4",
};
