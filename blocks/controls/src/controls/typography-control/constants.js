import { __ } from "@wordpress/i18n";

const FONTS = {
	"Abril Fatface": { weight: ["400"] },
	Anton: { weight: ["400"] },
	Arvo: { weight: ["400", "700"] },
	Asap: { weight: ["400", "500", "600", "700"] },
	"Barlow Condensed": {
		weight: ["100", "200", "300", "400", "500", "600", "700", "800", "900"],
	},
	Barlow: {
		weight: ["100", "200", "300", "400", "500", "600", "700", "800", "900"],
	},
	"Cormorant Garamond": { weight: ["300", "400", "500", "600", "700"] },
	Faustina: { weight: ["400", "500", "600", "700"] },
	"Fira Sans": {
		weight: ["100", "200", "300", "400", "500", "600", "700", "800", "900"],
	},
	"IBM Plex Sans": {
		weight: ["100", "200", "300", "400", "500", "600", "700"],
	},
	Inconsolata: { weight: ["400", "700"] },
	Heebo: { weight: ["100", "300", "400", "500", "700", "800", "900"] },
	Karla: { weight: ["400", "700"] },
	Lato: {
		weight: ["100", "200", "300", "400", "500", "600", "700", "800", "900"],
	},
	Lora: { weight: ["400", "700"] },
	Merriweather: { weight: ["300", "400", "500", "600", "700", "800", "900"] },
	Montserrat: {
		weight: ["100", "200", "300", "400", "500", "600", "700", "800", "900"],
	},
	"Noto Sans": { weight: ["400", "700"] },
	"Noto Serif": { weight: ["400", "700"] },
	"Open Sans": { weight: ["300", "400", "500", "600", "700", "800"] },
	Oswald: { weight: ["200", "300", "400", "500", "600", "700"] },
	"Playfair Display": { weight: ["400", "700", "900"] },
	"PT Serif": { weight: ["400", "700"] },
	Roboto: { weight: ["100", "300", "400", "500", "700", "900"] },
	Rubik: { weight: ["300", "400", "500", "700", "900"] },
	Tajawal: { weight: ["200", "300", "400", "500", "700", "800", "900"] },
	Ubuntu: { weight: ["300", "400", "500", "700"] },
	Yrsa: { weight: ["300", "400", "500", "600", "700"] },
};

const WEIGHTS = [
	{ label: __("Default", "essential-blocks"), value: "" },
	{ label: __("Normal", "essential-blocks"), value: "normal" },
	{ label: __("Bold", "essential-blocks"), value: "bold" },
];

const TRANSFORMS = [
	{ label: __("None", "essential-blocks"), value: "" },
	{ label: __("AA", "essential-blocks"), value: "uppercase" },
	{ label: __("aa", "essential-blocks"), value: "lowercase" },
	{ label: __("Aa", "essential-blocks"), value: "capitalize" },
];

export { FONTS, WEIGHTS, TRANSFORMS };
