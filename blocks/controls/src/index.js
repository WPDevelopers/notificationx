//Export React Libraries
// export {
// 	SortableContainer,
// 	SortableElement,
// 	SortableHandle,
// } from "react-sortable-hoc";
// export { default as FontIconPicker } from "@fonticonpicker/react-fonticonpicker";
// export { default as classnames } from "classnames";
// export { default as Select2 } from "react-select";
// export { default as striptags } from "striptags";
// export { default as Typed } from "typed.js";
// export { default as SlickSlider } from "react-slick";

import "./backend-css";

//Export All Controls
// export { default as BackgroundControl } from "./controls/background-control";
export { default as BorderShadowControl } from "./controls/border-shadow-control";
// export { default as ColorControl } from "./controls/color-control";
// export { default as CustomQuery } from "./controls/custom-query";
export { default as ResponsiveDimensionsControl } from "./controls/dimensions-control-v2";
// export { default as GradientColorControl } from "./controls/gradient-color-controller";
// export { default as ImageAvatar } from "./controls/image-avatar";
// export { default as ResetControl } from "./controls/reset-control";
// export { default as ResponsiveRangeController } from "./controls/responsive-range-control";
// export { default as WithResBtns } from "./controls/responsive-range-control/responsive-btn";
// export { default as DealSocialProfiles } from "./controls/social-profiles-v2/DealSocialProfiles";
// export { default as ToggleButton } from "./controls/toggle-button";
export { default as TypographyDropdown } from "./controls/typography-control-v2";
// export { default as UnitControl } from "./controls/unit-control";
// export { default as faIcons } from "./extras/faIcons";
// export * from "./extras/icons";

//Export Helper Functions
export {
	// mimmikCssForResBtns,
	// mimmikCssOnPreviewBtnClickWhileBlockSelected,
	softMinifyCssStrings,
	// generateBackgroundControlStyles,
	generateDimensionsControlStyles,
	generateTypographyStyles,
	generateBorderShadowStyles,
	// generateResponsiveRangeStyles,
	// mimmikCssForPreviewBtnClick,
	duplicateBlockIdFix,
	generateDimensionsAttributes,
	generateTypographyAttributes,
	// generateBackgroundAttributes,
	generateBorderShadowAttributes,
	// generateResponsiveRangeAttributes,
	// textInsideForEdit,
	// getFlipTransform,
	// ebConditionalRegisterBlockType
} from "./helpers";
