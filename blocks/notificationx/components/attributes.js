import { NX_MARGIN, NX_PADDING, NX_BORDER } from "./constants";

const {
  generateDimensionsAttributes,
  generateTypographyAttributes,
  // generateBackgroundAttributes,
  generateBorderShadowAttributes,
  // generateResponsiveRangeAttributes,
} = window.EBControls;

import * as typographyObjs from "./typographyConstants";

const attributes = {
  resOption: {
    type: "string",
    default: "Desktop",
  },

  // blockId attribute for making unique className and other uniqueness
  blockId: {
    type: "string",
  },
  blockRoot: {
    type: "string",
    default: "notificationx_pro",
  },
  blockMeta: {
    type: "object",
  },
  nx_id: {
    type: "string",
    default: null,
  },
  nxColor: {
    type: "string",
  },
  nxBgColor: {
    type: "string",
  },
  nxLinkColor: {
    type: "string",
    default: "#CF2E2E",
  },
  nxTextAlign: {
    type: "string",
    default: "left",
  },
  nxWrapperAlign: {
    type: "string",
    default: "flex-start",
  },

  // typography attributes ⬇
  ...generateTypographyAttributes(Object.values(typographyObjs)),

  // margin padding attributes ⬇
  ...generateDimensionsAttributes(NX_MARGIN, {
    top: 0,
    bottom: 0,
    right: 0,
    left: 0,
    isLinked: true,
  }),
  ...generateDimensionsAttributes(NX_PADDING, {
    top: 0,
    bottom: 0,
    right: 0,
    left: 0,
    isLinked: true,
  }),

  // border shadow attributes ⬇
  ...generateBorderShadowAttributes(NX_BORDER, {
    bdrDefaults: {
      top: 0,
      bottom: 0,
      right: 0,
      left: 0,
    },
    // defaultBdrColor: "#c3c3c3",
    // defaultBdrStyle: "solid",
    // rdsDefaults: {
    //   top: 4,
    //   right: 4,
    //   bottom: 4,
    //   left: 4,
    // },
    // noBorder: true,
  }),
};

export default attributes;
