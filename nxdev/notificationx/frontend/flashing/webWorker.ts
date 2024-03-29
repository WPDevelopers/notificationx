
// we use web worker to trigger interval since browser main thread is limited
// when tab is not active
function fworker(fn) {
    // ref: https://stackoverflow.com/a/10372280/387194
    var str = "(" + fn.toString() + ")()";
    var URL = window.URL || window.webkitURL;
    var blob;
    try {
        blob = new Blob([str], { type: "application/javascript" });
    } catch (e) {
        // Backwards-compatibility
        // @ts-ignore
        window.BlobBuilder = window.BlobBuilder || window.WebKitBlobBuilder || window.MozBlobBuilder;
        // @ts-ignore
        blob = new BlobBuilder();
        blob.append(str);
        blob = blob.getBlob();
    }
    return new Worker(URL.createObjectURL(blob));
}
function generateUniqueId (callbacks) {
    // Check if crypto is supported
    if (!window.crypto && window.crypto.randomUUID) {
      // Use crypto.randomUUID () if available
      return crypto.randomUUID ();
    } else {
      // Use Date.now () and Math.random () as fallback
      var id = Date.now () + Math.random ();
      // Check if id already exists in callbacks
      while (callbacks && callbacks[id]) {
        // Generate a new id
        id = Date.now () + Math.random ();
      }
      // Return the unique id
      return id;
    }
  }

// ----------------------------------------------------------------------------------
var interval = (function () {
    var worker = fworker(function () {
        // rAF polyfil without setTimeout, ref: https://gist.github.com/paulirish/1579671
        var vendors = ["ms", "moz", "webkit", "o"];
        for (
            var x = 0;
            x < vendors.length && !self.requestAnimationFrame;
            ++x
        ) {
            self.requestAnimationFrame =
                self[vendors[x] + "RequestAnimationFrame"];
            self.cancelAnimationFrame =
                self[vendors[x] + "CancelAnimationFrame"] ||
                self[vendors[x] + "CancelRequestAnimationFrame"];
        }
        var raf = {};
        var timeout = {};
        self.addEventListener("message", function (response) {
            var data = response.data;
            var id = data.id;
            if (data.type !== "RPC" || id === null) {
                return;
            }
            if (data.method == "setInterval") {
                var interval_id = data.params[0];
                raf[interval_id] = self.setInterval(function () {
                    self.postMessage({ type: "interval", id: interval_id });
                }, data.params[1]);
                self.postMessage({ type: "RPC", id: id, result: interval_id });
            } else if (data.method == "clearInterval") {
                self.clearInterval(raf[data.params[0]]);
                delete raf[data.params[0]];
            } else if (data.method == "setTimeout") {
                var timeout_id = data.params[0];
                timeout[timeout_id] = self.setTimeout(function () {
                  self.postMessage({ type: "timeout", id: timeout_id });
                  delete timeout[timeout_id];
                }, data.params[1]);
                self.postMessage({ type: "RPC", id: id, result: timeout_id });
              } else if (data.method == "clearTimeout") {
                self.clearTimeout(timeout[data.params[0]]);
                delete timeout[data.params[0]];
              }
        });
    });
    var callbacks = {};
    var rpc = (function () {
        var id = 0;
        return function rpc(method, params) {
            var _id = ++id;
            return new Promise(function (resolve) {
                worker.addEventListener("message", function handler(response) {
                    var data = response.data;
                    if (data && data.type === "RPC" && data.id === _id) {
                        resolve(data.result);
                        worker.removeEventListener("message", handler);
                    }
                });
                worker.postMessage({
                    type: "RPC",
                    method: method,
                    id: _id,
                    params: params,
                });
            });
        };
    })();
    worker.addEventListener("message", function (response) {
        var data = response.data;
        if (data && (data.type === "interval" || data.type === "timeout") && callbacks[data.id]) {
            callbacks[data.id]();
        }
    });
    return {
        set: function (fn, interval) {
            var interval_id = generateUniqueId(callbacks);
            callbacks[interval_id] = fn;
            rpc("setInterval", [interval_id, interval]);
            return interval_id;
        },
        clear: function (id) {
            delete callbacks[id];
            return rpc("clearInterval", [id]);
        },
        setTimeout: function (fn, interval) {
            var interval_id = generateUniqueId(callbacks);
            callbacks[interval_id] = fn;
            rpc("setTimeout", [interval_id, interval]);
            return interval_id;
        },
        clearTimeout: function (id) {
            delete callbacks[id];
            return rpc("clearTimeout", [id]);
        },
    };
})();

export default interval;
