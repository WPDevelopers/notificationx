import { registerBlockType } from "@wordpress/blocks";
const { omit } = lodash;

export {
  generateBackgroundControlStyles,
  generateBackgroundAttributes,
} from "./backgroundHelpers";

export {
  generateTypographyAttributes,
  generateTypographyStyles,
} from "./typoHelpers";

export {
  generateDimensionsAttributes,
  generateDimensionsControlStyles,
} from "./dimensionHelpers";

export {
  generateBorderShadowAttributes,
  generateBorderShadowStyles,
} from "./borderShadowHelpers";

export {
  generateResponsiveRangeStyles,
  generateResponsiveRangeAttributes,
} from "./responsiveRangeHelpers";

export {
  textInsideForEdit,
  generateRandomNumber,
  hardMinifyCssStrings,
  softMinifyCssStrings,
  isCssExists,
} from "./miniHelperFuncs";

export {
  handleDesktopBtnClick,
  handleTabBtnClick,
  handleMobileBtnClick,
} from "./handlingPreviewBtnsHelpers";

export {
  mimmikCssForResBtns,
  mimmikCssForPreviewBtnClick,
  mimmikCssOnPreviewBtnClickWhileBlockSelected,
  duplicateBlockIdFix,
} from "./funcsForUseEffect";

export { getFlipTransform, getButtonClasses } from "./flipboxHelpers";

export const ebConditionalRegisterBlockType = (metadata, settings) => {
  const { name } = metadata;
  if (EssentialBlocksLocalize.eb_wp_version >= 5.8) {
    registerBlockType({ name, ...metadata }, settings);
  } else {
    registerBlockType(`${name}`, {
      ...omit(metadata, ["name"]),
      ...settings,
    });
  }
};
