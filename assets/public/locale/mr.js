(self.webpackChunknotificationx=self.webpackChunknotificationx||[]).push([[5068],{370:function(e,a,s){!function(e){"use strict";var a={1:"१",2:"२",3:"३",4:"४",5:"५",6:"६",7:"७",8:"८",9:"९",0:"०"},s={"१":"1","२":"2","३":"3","४":"4","५":"5","६":"6","७":"7","८":"8","९":"9","०":"0"};function r(e,a,s,r){var c="";if(a)switch(s){case"s":c="काही सेकंद";break;case"ss":c="%d सेकंद";break;case"m":c="एक मिनिट";break;case"mm":c="%d मिनिटे";break;case"h":c="एक तास";break;case"hh":c="%d तास";break;case"d":c="एक दिवस";break;case"dd":c="%d दिवस";break;case"M":c="एक महिना";break;case"MM":c="%d महिने";break;case"y":c="एक वर्ष";break;case"yy":c="%d वर्षे"}else switch(s){case"s":c="काही सेकंदां";break;case"ss":c="%d सेकंदां";break;case"m":c="एका मिनिटा";break;case"mm":c="%d मिनिटां";break;case"h":c="एका तासा";break;case"hh":c="%d तासां";break;case"d":c="एका दिवसा";break;case"dd":c="%d दिवसां";break;case"M":c="एका महिन्या";break;case"MM":c="%d महिन्यां";break;case"y":c="एका वर्षा";break;case"yy":c="%d वर्षां"}return c.replace(/%d/i,e)}e.defineLocale("mr",{months:"जानेवारी_फेब्रुवारी_मार्च_एप्रिल_मे_जून_जुलै_ऑगस्ट_सप्टेंबर_ऑक्टोबर_नोव्हेंबर_डिसेंबर".split("_"),monthsShort:"जाने._फेब्रु._मार्च._एप्रि._मे._जून._जुलै._ऑग._सप्टें._ऑक्टो._नोव्हें._डिसें.".split("_"),monthsParseExact:!0,weekdays:"रविवार_सोमवार_मंगळवार_बुधवार_गुरूवार_शुक्रवार_शनिवार".split("_"),weekdaysShort:"रवि_सोम_मंगळ_बुध_गुरू_शुक्र_शनि".split("_"),weekdaysMin:"र_सो_मं_बु_गु_शु_श".split("_"),longDateFormat:{LT:"A h:mm वाजता",LTS:"A h:mm:ss वाजता",L:"DD/MM/YYYY",LL:"D MMMM YYYY",LLL:"D MMMM YYYY, A h:mm वाजता",LLLL:"dddd, D MMMM YYYY, A h:mm वाजता"},calendar:{sameDay:"[आज] LT",nextDay:"[उद्या] LT",nextWeek:"dddd, LT",lastDay:"[काल] LT",lastWeek:"[मागील] dddd, LT",sameElse:"L"},relativeTime:{future:"%sमध्ये",past:"%sपूर्वी",s:r,ss:r,m:r,mm:r,h:r,hh:r,d:r,dd:r,M:r,MM:r,y:r,yy:r},preparse:function(e){return e.replace(/[१२३४५६७८९०]/g,(function(e){return s[e]}))},postformat:function(e){return e.replace(/\d/g,(function(e){return a[e]}))},meridiemParse:/पहाटे|सकाळी|दुपारी|सायंकाळी|रात्री/,meridiemHour:function(e,a){return 12===e&&(e=0),"पहाटे"===a||"सकाळी"===a?e:"दुपारी"===a||"सायंकाळी"===a||"रात्री"===a?e>=12?e:e+12:void 0},meridiem:function(e,a,s){return e>=0&&e<6?"पहाटे":e<12?"सकाळी":e<17?"दुपारी":e<20?"सायंकाळी":"रात्री"},week:{dow:0,doy:6}})}(s(381))}}]);