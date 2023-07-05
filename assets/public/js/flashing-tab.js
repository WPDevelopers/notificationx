(()=>{"use strict";function e(e){if(!window.crypto&&window.crypto.randomUUID)return crypto.randomUUID();for(var t=Date.now()+Math.random();e&&e[t];)t=Date.now()+Math.random();return t}var t,n,i,o;const a=(n=function(e){var t,n="("+function(){for(var e=["ms","moz","webkit","o"],t=0;t<e.length&&!self.requestAnimationFrame;++t)self.requestAnimationFrame=self[e[t]+"RequestAnimationFrame"],self.cancelAnimationFrame=self[e[t]+"CancelAnimationFrame"]||self[e[t]+"CancelRequestAnimationFrame"];var n={},i={};self.addEventListener("message",(function(e){var t=e.data,o=t.id;if("RPC"===t.type&&null!==o)if("setInterval"==t.method){var a=t.params[0];n[a]=self.setInterval((function(){self.postMessage({type:"interval",id:a})}),t.params[1]),self.postMessage({type:"RPC",id:o,result:a})}else if("clearInterval"==t.method)self.clearInterval(n[t.params[0]]),delete n[t.params[0]];else if("setTimeout"==t.method){var r=t.params[0];i[r]=self.setTimeout((function(){self.postMessage({type:"timeout",id:r}),delete i[r]}),t.params[1]),self.postMessage({type:"RPC",id:o,result:r})}else"clearTimeout"==t.method&&(self.clearTimeout(i[t.params[0]]),delete i[t.params[0]])}))}.toString()+")()",i=window.URL||window.webkitURL;try{t=new Blob([n],{type:"application/javascript"})}catch(e){window.BlobBuilder=window.BlobBuilder||window.WebKitBlobBuilder||window.MozBlobBuilder,(t=new BlobBuilder).append(n),t=t.getBlob()}return new Worker(i.createObjectURL(t))}(),i={},t=0,o=function(e,i){var o=++t;return new Promise((function(t){n.addEventListener("message",(function e(i){var a=i.data;a&&"RPC"===a.type&&a.id===o&&(t(a.result),n.removeEventListener("message",e))})),n.postMessage({type:"RPC",method:e,id:o,params:i})}))},n.addEventListener("message",(function(e){var t=e.data;t&&("interval"===t.type||"timeout"===t.type)&&i[t.id]&&i[t.id]()})),{set:function(t,n){var a=e(i);return i[a]=t,o("setInterval",[a,n]),a},clear:function(e){return delete i[e],o("clearInterval",[e])},setTimeout:function(t,n){var a=e(i);return i[a]=t,o("setTimeout",[a,n]),a},clearTimeout:function(e){return delete i[e],o("clearTimeout",[e])}});var r,l,s,c,u=[],d=["icon","mask-icon","apple-touch-icon"];const m={init:function e(t){if("complete"===document.readyState){c=Object.assign({size:16},t);for(var n=document.querySelectorAll('link[rel*="icon"]'),i=0;i<n.length;i++){var o=n[i].cloneNode(!0);u.push(o)}l||(l=document.createElement("canvas")),l.width=l.height=c.size,(r=l.getContext("2d")).lineCap="round",s=!0}else setTimeout(e.bind(this,t),100)},animatePng:function(e){return new Promise((function(t,n){if(s)if(d.forEach((function(e){if(!document.querySelector('link[rel="'.concat(e,'"]'))){var t=document.createElement("link");t.setAttribute("rel",e),t.setAttribute("color","#000000"),document.head.appendChild(t)}})),e){r.clearRect(0,0,c.size,c.size);var i=new Image;i.onload=function(){r.drawImage(i,0,0,c.size,c.size);var e=r.canvas.toDataURL();d.forEach((function(t){var n=document.querySelector('link[rel="'.concat(t,'"]')),i=n.cloneNode(!0);i.setAttribute("href",e),n.parentNode.replaceChild(i,n)})),t(e)},i.onerror=function(){n(new Error("Image loading failed"))},i.src=e}else n(new Error("No png provided"));else n(new Error("Function not initialized"))}))},restore:function(){if(d.forEach((function(e){var t=document.querySelector('link[rel="'.concat(e,'"]'));t&&t.parentNode&&t.parentNode.removeChild(t)})),u.length)for(var e=0;e<u.length;e++)document.head.appendChild(u[e])},removeIcon:function(){for(var e=document.querySelectorAll('link[rel*="icon"]'),t=0;t<e.length;t++)e[t].remove()},interval:a,version:"0.4.4"};var f,v,h,p,_,w,g,y,b,I,T=window.nx_flashing_tab||{},C=1e3*(parseInt(T.ft_delay_before)||0),k=1e3*(parseInt(T.ft_delay_between)||1),E=1e3*(parseFloat(T.ft_display_for)||0)*60,B={message:"",icon:""},R={message:"",icon:""},A=T.nx_id,z=T.__rest_api_url;switch(T.themes){case"flashing_tab_theme-1":case"flashing_tab_theme-2":B.icon=null===(f=T.ft_theme_one_icons)||void 0===f?void 0:f["icon-one"],R.icon=null===(v=T.ft_theme_one_icons)||void 0===v?void 0:v["icon-two"],B.message=T.ft_theme_one_message;break;case"flashing_tab_theme-3":B=null!==(h=T.ft_theme_three_line_one)&&void 0!==h?h:B,R=null!==(p=T.ft_theme_three_line_two)&&void 0!==p?p:R;break;case"flashing_tab_theme-4":B=null!==(_=T.ft_theme_three_line_one)&&void 0!==_?_:B,R=(null===(w=T.ft_theme_four_line_two)||void 0===w?void 0:w["is-show-empty"])?null!==(I=null===(b=T.ft_theme_four_line_two)||void 0===b?void 0:b.alternative)&&void 0!==I?I:R:null!==(y=null===(g=T.ft_theme_four_line_two)||void 0===g?void 0:g.default)&&void 0!==y?y:R}m.init({size:32});var P=!1,S=null,q=null,L=null,F=window.document.title,M=function(e){e&&e!==document.title&&(document.title=e)},U=function(e){return m.animatePng(e)},N=function(){(P=!P)?U(B.icon).finally((function(){M(B.message)})):U(R.icon).finally((function(){M(R.message)}))},j=function(e){document.title=F,e?m.removeIcon():m.restore(),S&&(a.clear(S),S=null),q&&(a.clearTimeout(q),q=null),L&&(a.clearTimeout(L),L=null)};function D(e,t){var n={nx_id:e,type:t};fetch(z,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(n)}).then((function(e){return e.json()})).then((function(e){})).catch((function(e){}))}window.addEventListener("visibilitychange",(function(e){"visible"!==document.visibilityState?(j(!0),q=a.setTimeout((function(){N(),S=a.set(N,k),q=null,D(A,"views")}),C),E&&(L=a.setTimeout((function(){j()}),E))):(null==q&&D(A,"clicks"),j())}))})();