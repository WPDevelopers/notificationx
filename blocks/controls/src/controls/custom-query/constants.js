// const { __ } = wp.i18n;
import { __ } from "@wordpress/i18n";

export const SOURCES = [
	{ label: __("Post", "essential-blocks"), value: "posts" },
	// { label: __("Page", "essential-blocks"), value: "pages" },
];

export const ORDER_BY = [
	{ label: __("Date", "essential-blocks"), value: "date" },
	{ label: __("Modified Date", "essential-blocks"), value: "modified" },
	{ label: __("Title", "essential-blocks"), value: "title" },
	{ label: __("ID", "essential-blocks"), value: "id" },
	{ label: __("Parent", "essential-blocks"), value: "parent" },
];

export const ORDER = [
	{ label: __("Desc", "essential-blocks"), value: "desc" },
	{ label: __("Asc", "essential-blocks"), value: "asc" },
];