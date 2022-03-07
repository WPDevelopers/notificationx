import {
  generateDimensionsAttributes,
  generateDimensionsControlStyles,
} from "./dimensionHelpers";

// Important: the following "generateBorderShadowAttributes" function must be declared below the "generateDimensionsAttributes" function declaration
// function to generate BorderShadow control's attributes
export const generateBorderShadowAttributes = (controlName, defaults = {}) => {
  const {
    bdrDefaults = {
      top: 1,
      right: 1,
      bottom: 1,
      left: 1,
    },
    rdsDefaults = {},
    noBorder = false,
    noShadow = false,
    defaultBdrColor = false,
    defaultBdrStyle = false,
    noBdrHover = false,
    noShdowHover = false,
  } = defaults;

  const bdrColor = defaultBdrColor
    ? {
        [`${controlName}borderColor`]: {
          type: "string",
          default: defaultBdrColor,
        },
      }
    : {
        [`${controlName}borderColor`]: {
          type: "string",
        },
      };

  const bdrStyle = defaultBdrStyle
    ? {
        [`${controlName}borderStyle`]: {
          type: "string",
          default: defaultBdrStyle,
        },
      }
    : {
        [`${controlName}borderStyle`]: {
          type: "string",
          default: "none",
        },
      };

  const bdrAttrs = {
    // border attributes ⬇

    ...bdrColor,
    ...bdrStyle,
    ...generateDimensionsAttributes(`${controlName}Bdr_`, bdrDefaults),
    ...generateDimensionsAttributes(`${controlName}Rds_`, rdsDefaults),
  };

  const hvBdrAttrs = {
    [`${controlName}BorderType`]: {
      type: "string",
      default: "normal",
    },
    [`${controlName}HborderColor`]: {
      type: "string",
    },
    [`${controlName}HborderStyle`]: {
      type: "string",
      default: "none",
    },
    ...generateDimensionsAttributes(`${controlName}HBdr_`),
    ...generateDimensionsAttributes(`${controlName}HRds_`),
  };

  const shdAttrs = {
    // shadow attributes  ⬇
    [`${controlName}hOffset`]: {
      type: "number",
    },
    [`${controlName}vOffset`]: {
      type: "number",
    },
    [`${controlName}blur`]: {
      type: "number",
    },
    [`${controlName}spread`]: {
      type: "number",
    },
    [`${controlName}shadowColor`]: {
      type: "string",
    },
    [`${controlName}inset`]: {
      type: "boolean",
      default: false,
    },
  };

  const hvShdAttrs = {
    [`${controlName}shadowType`]: {
      type: "string",
      default: "normal",
    },
    [`${controlName}hoverHOffset`]: {
      type: "number",
    },
    [`${controlName}hoverVOffset`]: {
      type: "number",
    },
    [`${controlName}hoverBlur`]: {
      type: "number",
    },
    [`${controlName}hoverSpread`]: {
      type: "number",
    },
    [`${controlName}hoverShadowColor`]: {
      type: "string",
    },
    [`${controlName}hoverInset`]: {
      type: "boolean",
      default: false,
    },
  };

  const transitionAttrs = {
    [`${controlName}borderTransition`]: {
      type: "number",
      default: 0.5,
    },
    [`${controlName}radiusTransition`]: {
      type: "number",
      default: 0.5,
    },
    [`${controlName}shadowTransition`]: {
      type: "number",
      default: 0.5,
    },
  };

  if (noBorder === true) {
    if (noShdowHover) {
      return {
        ...shdAttrs,
      };
    } else {
      return {
        ...shdAttrs,
        ...hvShdAttrs,
        ...transitionAttrs,
      };
    }
  } else if (noShadow === true) {
    if (noBdrHover) {
      return {
        ...bdrAttrs,
      };
    } else {
      return {
        ...bdrAttrs,
        ...hvBdrAttrs,
        ...transitionAttrs,
      };
    }
  } else {
    let result = {};

    if (noShdowHover && noBdrHover) {
      result = {
        ...bdrAttrs,
        ...shdAttrs,
      };
    } else if (noShdowHover && !noBdrHover) {
      result = {
        ...bdrAttrs,
        ...hvBdrAttrs,
        ...transitionAttrs,
        ...shdAttrs,
      };
    } else if (!noShdowHover && noBdrHover) {
      result = {
        ...shdAttrs,
        ...hvShdAttrs,
        ...transitionAttrs,
        ...bdrAttrs,
      };
    } else if (!noShdowHover && !noBdrHover) {
      result = {
        ...bdrAttrs,
        ...shdAttrs,
        ...hvShdAttrs,
        ...hvBdrAttrs,
        ...transitionAttrs,
      };
    }

    return result;
  }
};

// Important: the following "generateBorderShadowStyles" function must be declared below the "generateDimensionsControlStyles" function declaration
// function to generate BorderShadow control's Styles for an element based on it's controlName(prefix)
export const generateBorderShadowStyles = ({
  controlName,
  attributes,
  noBorder,
  noShadow,
}) => {
  let borderStylesDesktop = "";
  let borderStylesTab = "";
  let borderStylesMobile = "";
  let radiusStylesDesktop = "";
  let radiusStylesTab = "";
  let radiusStylesMobile = "";
  let HborderStylesDesktop = "";
  let HborderStylesTab = "";
  let HborderStylesMobile = "";
  let HradiusStylesDesktop = "";
  let HradiusStylesTab = "";
  let HradiusStylesMobile = "";

  if (noBorder !== true) {
    const {
      dimensionStylesDesktop: F_borderStylesDesktop,
      dimensionStylesTab: F_borderStylesTab,
      dimensionStylesMobile: F_borderStylesMobile,
    } = generateDimensionsControlStyles({
      controlName: `${controlName}Bdr_`,
      styleFor: "border",
      attributes,
    });

    const {
      dimensionStylesDesktop: F_radiusStylesDesktop,
      dimensionStylesTab: F_radiusStylesTab,
      dimensionStylesMobile: F_radiusStylesMobile,
    } = generateDimensionsControlStyles({
      controlName: `${controlName}Rds_`,
      styleFor: "border-radius",
      attributes,
    });

    const {
      dimensionStylesDesktop: F_HborderStylesDesktop,
      dimensionStylesTab: F_HborderStylesTab,
      dimensionStylesMobile: F_HborderStylesMobile,
    } = generateDimensionsControlStyles({
      controlName: `${controlName}HBdr_`,
      styleFor: "border",
      attributes,
    });

    const {
      dimensionStylesDesktop: F_HradiusStylesDesktop,
      dimensionStylesTab: F_HradiusStylesTab,
      dimensionStylesMobile: F_HradiusStylesMobile,
    } = generateDimensionsControlStyles({
      controlName: `${controlName}HRds_`,
      styleFor: "border-radius",
      attributes,
    });

    borderStylesDesktop = F_borderStylesDesktop;
    borderStylesTab = F_borderStylesTab;
    borderStylesMobile = F_borderStylesMobile;
    radiusStylesDesktop = F_radiusStylesDesktop;
    radiusStylesTab = F_radiusStylesTab;
    radiusStylesMobile = F_radiusStylesMobile;
    HborderStylesDesktop = F_HborderStylesDesktop;
    HborderStylesTab = F_HborderStylesTab;
    HborderStylesMobile = F_HborderStylesMobile;
    HradiusStylesDesktop = F_HradiusStylesDesktop;
    HradiusStylesTab = F_HradiusStylesTab;
    HradiusStylesMobile = F_HradiusStylesMobile;
  }

  // const {
  //   dimensionStylesDesktop: borderStylesDesktop,
  //   dimensionStylesTab: borderStylesTab,
  //   dimensionStylesMobile: borderStylesMobile,
  // } = generateDimensionsControlStyles({
  //   controlName: `${controlName}Bdr_`,
  //   styleFor: "border",
  //   attributes,
  // });

  // const {
  //   dimensionStylesDesktop: radiusStylesDesktop,
  //   dimensionStylesTab: radiusStylesTab,
  //   dimensionStylesMobile: radiusStylesMobile,
  // } = generateDimensionsControlStyles({
  //   controlName: `${controlName}Rds_`,
  //   styleFor: "border-radius",
  //   attributes,
  // });

  // const {
  //   dimensionStylesDesktop: HborderStylesDesktop,
  //   dimensionStylesTab: HborderStylesTab,
  //   dimensionStylesMobile: HborderStylesMobile,
  // } = generateDimensionsControlStyles({
  //   controlName: `${controlName}HBdr_`,
  //   styleFor: "border",
  //   attributes,
  // });

  // const {
  //   dimensionStylesDesktop: HradiusStylesDesktop,
  //   dimensionStylesTab: HradiusStylesTab,
  //   dimensionStylesMobile: HradiusStylesMobile,
  // } = generateDimensionsControlStyles({
  //   controlName: `${controlName}HRds_`,
  //   styleFor: "border-radius",
  //   attributes,
  // });

  const {
    // [`${controlName}BorderType`]: BorderType,
    [`${controlName}borderStyle`]: borderStyle,
    [`${controlName}borderColor`]: borderColor,
    [`${controlName}HborderStyle`]: HborderStyle,
    [`${controlName}HborderColor`]: HborderColor,

    [`${controlName}shadowColor`]: shadowColor,
    [`${controlName}hOffset`]: hOffset = 0,
    [`${controlName}vOffset`]: vOffset = 0,
    [`${controlName}blur`]: blur = 0,
    [`${controlName}spread`]: spread = 0,
    [`${controlName}inset`]: inset,

    [`${controlName}hoverShadowColor`]: hoverShadowColor = shadowColor,
    [`${controlName}hoverHOffset`]: hoverHOffset = hOffset,
    [`${controlName}hoverVOffset`]: hoverVOffset = vOffset,
    [`${controlName}hoverBlur`]: hoverBlur = blur,
    [`${controlName}hoverSpread`]: hoverSpread = spread,

    [`${controlName}borderTransition`]: borderTransition,
    [`${controlName}radiusTransition`]: radiusTransition,
    [`${controlName}shadowTransition`]: shadowTransition,
  } = attributes;

  const styesDesktop = `  
      ${
        noBorder !== true
          ? `
          ${radiusStylesDesktop}
          ${
            borderStyle !== "none" && borderColor
              ? `
              ${borderStylesDesktop}
              border-color: ${borderColor};
              border-style: ${borderStyle};
              `
              : " "
          }
          `
          : " "
      }
    
      ${
        noShadow !== true
          ? shadowColor
            ? `box-shadow: ${shadowColor} ${hOffset}px ${vOffset}px ${blur}px ${spread}px ${
                inset ? "inset" : ""
              };`
            : " "
          : " "
      }
  
  
    `;

  const styesTab = `  
    ${
      noBorder !== true
        ? `
        ${borderColor ? borderStylesTab : " "}
        ${radiusStylesTab}
        `
        : " "
    }
      
    `;

  const styesMobile = `
    ${
      noBorder !== true
        ? `
        ${borderColor ? borderStylesMobile : " "}
        ${radiusStylesMobile}
        `
        : " "
    }
    `;

  const stylesHoverDesktop = `
    ${
      noBorder !== true
        ? `
        ${
          HborderStyle !== "none"
            ? `
              ${
                HborderColor !== borderColor
                  ? `border-color: ${HborderColor};`
                  : " "
              } 
              ${
                HborderStyle !== borderStyle
                  ? `border-style: ${HborderStyle};`
                  : " "
              }
              ${HborderStylesDesktop}
            `
            : " "
        }
  
        ${HradiusStylesDesktop}    
        `
        : " "
    }   
     
    ${
      noShadow !== true
        ? hoverShadowColor
          ? `box-shadow: ${hoverShadowColor} ${hoverHOffset}px ${hoverVOffset}px ${hoverBlur}px ${hoverSpread}px ${
              inset ? "inset" : " "
            };`
          : " "
        : " "
    }
  
    `;

  const stylesHoverTab = `
    ${
      noBorder !== true
        ? `
        ${HborderStyle !== "none" ? HborderStylesTab : " "}
        ${HradiusStylesTab}  
        `
        : " "
    }
    `;

  const stylesHoverMobile = `
    ${
      noBorder !== true
        ? `
        ${HborderStyle !== "none" ? HborderStylesMobile : " "}
        ${HradiusStylesMobile}
        `
        : " "
    }
     
    `;

  const transitionStyle = `
  border ${borderTransition || 0}s, border-radius ${
    radiusTransition || 0
  }s, box-shadow ${shadowTransition || 0}s
  `;

  return {
    styesDesktop,
    styesTab,
    styesMobile,
    stylesHoverDesktop,
    stylesHoverTab,
    stylesHoverMobile,
    transitionStyle,
  };
};
