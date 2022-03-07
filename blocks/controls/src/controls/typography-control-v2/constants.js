import { __ } from "@wordpress/i18n";

export const sizeUnitTypes = [
	{ label: "px", value: "px" },
	{ label: "%", value: "%" },
	{ label: "em", value: "em" },
];

export const optionsFontWeights = [
	{ label: __("Default", "essential-blocks"), value: "" },
	{ label: __("100", "essential-blocks"), value: "100" },
	{ label: __("200", "essential-blocks"), value: "200" },
	{ label: __("300", "essential-blocks"), value: "300" },
	{ label: __("400", "essential-blocks"), value: "400" },
	{ label: __("500", "essential-blocks"), value: "500" },
	{ label: __("600", "essential-blocks"), value: "600" },
	{ label: __("700", "essential-blocks"), value: "700" },
	{ label: __("800", "essential-blocks"), value: "800" },
	{ label: __("900", "essential-blocks"), value: "900" },
];

export const optionsTextTransforms = [
	{ label: __("Default", "essential-blocks"), value: "" },
	{ label: __("None", "essential-blocks"), value: "none" },
	{ label: __("Lowercase", "essential-blocks"), value: "lowercase" },
	{ label: __("Capitalize", "essential-blocks"), value: "capitalize" },
	{ label: __("Uppercase", "essential-blocks"), value: "uppercase" },
];

export const optionsTextDecorations = [
	{ label: __("Default", "essential-blocks"), value: "" },
	{ label: __("None", "essential-blocks"), value: "initial" },
	{ label: __("Overline", "essential-blocks"), value: "overline" },
	{ label: __("Line Through", "essential-blocks"), value: "line-through" },
	{ label: __("Underline", "essential-blocks"), value: "underline" },
	{
		label: __("Underline Oveline", "essential-blocks"),
		value: "underline overline",
	},
];

export const optionsFontStyles = [
	{ label: __("Default", "essential-blocks"), value: "" },
	{ label: __("Normal", "essential-blocks"), value: "normal" },
	{ label: __("Italic", "essential-blocks"), value: "italic" },
	{ label: __("Oblique", "essential-blocks"), value: "oblique" },
];

export const optionsLhLsp = [
	{ label: "px", value: "px" },
	{ label: "em", value: "em" },
];
