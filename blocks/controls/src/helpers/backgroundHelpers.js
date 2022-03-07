// function to generate Background control's attributes
export const generateBackgroundAttributes = (controlName, defaults = {}) => {
  const {
    isBgDefaultGradient,
    defaultFillColor,
    defaultBgGradient = "linear-gradient(45deg,#00000000,#00000000)",
    defaultHovBgGradient,
    noOverlay = false,
    noMainBgi = false,
    noOverlayBgi = false,
    noTransition = false,
    forButton = false,
  } = defaults;

  const bgColorAttr = defaultFillColor
    ? {
        [`${controlName}backgroundColor`]: {
          type: "string",
          default: defaultFillColor,
        },
      }
    : {
        [`${controlName}backgroundColor`]: {
          type: "string",
        },
      };

  const transitionAttr = noTransition
    ? {}
    : {
        [`${controlName}bg_transition`]: {
          type: "number",
          default: 0.5,
        },
      };

  const ovlTransitionAttr = noTransition
    ? {}
    : {
        [`${controlName}ovl_bg_transition`]: {
          type: "number",
          default: 0.5,
        },
        [`${controlName}ovl_filtersTransition`]: {
          type: "number",
          default: 0.5,
        },
        [`${controlName}ovl_opacityTransition`]: {
          type: "number",
          default: 0.5,
        },
      };

  const hovBgGradientAttr = defaultHovBgGradient
    ? {
        [`hov_${controlName}gradientColor`]: {
          type: "string",
          default: defaultHovBgGradient,
        },
      }
    : {
        [`hov_${controlName}gradientColor`]: {
          type: "string",
        },
      };

  const mainWithoutBgiAttrs = {
    [`${controlName}bg_hoverType`]: {
      type: "string",
      default: "normal",
    },
    ...transitionAttr,

    //  attributes for main background (not overlay) -> hover type 'normal' start  ⬇
    [`${controlName}backgroundType`]: {
      type: "string",
      default: isBgDefaultGradient === true ? "gradient" : "classic",
    },
    ...bgColorAttr,
    [`${controlName}gradientColor`]: {
      type: "string",
      default: defaultBgGradient,
    },
    //  attributes for main background (not overlay) -> hover type 'normal' end

    //  attributes for main background (not overlay) -> hover type 'hover' start  ⬇
    [`hov_${controlName}backgroundType`]: {
      type: "string",
      default: "classic",
    },
    [`hov_${controlName}backgroundColor`]: {
      type: "string",
    },
    ...hovBgGradientAttr,
    //  attributes for main background (not overlay) -> hover type 'hover' end
  };

  const mainBgiAttrs = {
    //  attributes for main background (not overlay) -> hover type 'normal' start  ⬇
    // desktop attributes start ⬇
    [`${controlName}bgImageURL`]: {
      type: "string",
    },
    [`${controlName}bgImageID`]: {
      type: "string",
    },
    [`${controlName}bgImgAttachment`]: {
      type: "string",
    },

    [`${controlName}backgroundSize`]: {
      type: "string",
    },
    [`${controlName}bgImgCustomSize`]: {
      type: "number",
      default: 100,
    },
    [`${controlName}bgImgCustomSizeUnit`]: {
      type: "string",
      default: "%",
    },
    [`${controlName}bgImgPos`]: {
      type: "string",
    },
    [`${controlName}bgImgcustomPosX`]: {
      type: "number",
      default: 0,
    },
    [`${controlName}bgImgcustomPosXUnit`]: {
      type: "string",
      default: "px",
    },
    [`${controlName}bgImgcustomPosY`]: {
      type: "number",
      default: 0,
    },
    [`${controlName}bgImgcustomPosYUnit`]: {
      type: "string",
      default: "px",
    },
    [`${controlName}bgImgRepeat`]: {
      type: "string",
    },
    // desktop attributes end

    // Tab attributes start ⬇
    [`TAB${controlName}backgroundSize`]: {
      type: "string",
    },
    [`TAB${controlName}bgImgCustomSize`]: {
      type: "number",
      default: 100,
    },
    [`TAB${controlName}bgImgCustomSizeUnit`]: {
      type: "string",
      default: "%",
    },
    [`TAB${controlName}bgImgPos`]: {
      type: "string",
    },
    [`TAB${controlName}bgImgcustomPosX`]: {
      type: "number",
      default: 0,
    },
    [`TAB${controlName}bgImgcustomPosXUnit`]: {
      type: "string",
      default: "px",
    },
    [`TAB${controlName}bgImgcustomPosY`]: {
      type: "number",
      default: 0,
    },
    [`TAB${controlName}bgImgcustomPosYUnit`]: {
      type: "string",
      default: "px",
    },
    [`TAB${controlName}bgImgRepeat`]: {
      type: "string",
    },
    // Tab attributes end

    // Mobile attributes start ⬇
    [`MOB${controlName}backgroundSize`]: {
      type: "string",
    },
    [`MOB${controlName}bgImgCustomSize`]: {
      type: "number",
      default: 100,
    },
    [`MOB${controlName}bgImgCustomSizeUnit`]: {
      type: "string",
      default: "%",
    },
    [`MOB${controlName}bgImgPos`]: {
      type: "string",
    },
    [`MOB${controlName}bgImgcustomPosX`]: {
      type: "number",
      default: 0,
    },
    [`MOB${controlName}bgImgcustomPosXUnit`]: {
      type: "string",
      default: "px",
    },
    [`MOB${controlName}bgImgcustomPosY`]: {
      type: "number",
      default: 0,
    },
    [`MOB${controlName}bgImgcustomPosYUnit`]: {
      type: "string",
      default: "px",
    },
    [`MOB${controlName}bgImgRepeat`]: {
      type: "string",
    },
    // Mobile attributes end
    //  attributes for main background (not overlay) -> hover type 'normal' end

    //  attributes for main background (not overlay) -> hover type 'hover' start  ⬇
    // desktop attributes start
    [`hov_${controlName}bgImageURL`]: {
      type: "string",
    },
    [`hov_${controlName}bgImageID`]: {
      type: "string",
    },
    [`hov_${controlName}bgImgAttachment`]: {
      type: "string",
    },
    [`hov_${controlName}backgroundSize`]: {
      type: "string",
    },
    [`hov_${controlName}bgImgCustomSize`]: {
      type: "number",
      default: 100,
    },
    [`hov_${controlName}bgImgCustomSizeUnit`]: {
      type: "string",
      default: "%",
    },
    [`hov_${controlName}bgImgPos`]: {
      type: "string",
    },
    [`hov_${controlName}bgImgcustomPosX`]: {
      type: "number",
      default: 0,
    },
    [`hov_${controlName}bgImgcustomPosXUnit`]: {
      type: "string",
      default: "px",
    },
    [`hov_${controlName}bgImgcustomPosY`]: {
      type: "number",
      default: 0,
    },
    [`hov_${controlName}bgImgcustomPosYUnit`]: {
      type: "string",
      default: "px",
    },
    [`hov_${controlName}bgImgRepeat`]: {
      type: "string",
    },
    // desktop attributes end

    // Tab attributes start
    [`hov_TAB${controlName}backgroundSize`]: {
      type: "string",
    },
    [`hov_TAB${controlName}bgImgCustomSize`]: {
      type: "number",
    },
    [`hov_TAB${controlName}bgImgCustomSizeUnit`]: {
      type: "string",
      default: "%",
    },
    [`hov_TAB${controlName}bgImgPos`]: {
      type: "string",
    },
    [`hov_TAB${controlName}bgImgcustomPosX`]: {
      type: "number",
    },
    [`hov_TAB${controlName}bgImgcustomPosXUnit`]: {
      type: "string",
      default: "px",
    },
    [`hov_TAB${controlName}bgImgcustomPosY`]: {
      type: "number",
    },
    [`hov_TAB${controlName}bgImgcustomPosYUnit`]: {
      type: "string",
      default: "px",
    },
    [`hov_TAB${controlName}bgImgRepeat`]: {
      type: "string",
    },
    // Tab attributes end

    // Mobile attributes start
    [`hov_MOB${controlName}backgroundSize`]: {
      type: "string",
    },
    [`hov_MOB${controlName}bgImgCustomSize`]: {
      type: "number",
    },
    [`hov_MOB${controlName}bgImgCustomSizeUnit`]: {
      type: "string",
      default: "%",
    },
    [`hov_MOB${controlName}bgImgPos`]: {
      type: "string",
    },
    [`hov_MOB${controlName}bgImgcustomPosX`]: {
      type: "number",
    },
    [`hov_MOB${controlName}bgImgcustomPosXUnit`]: {
      type: "string",
      default: "px",
    },
    [`hov_MOB${controlName}bgImgcustomPosY`]: {
      type: "number",
    },
    [`hov_MOB${controlName}bgImgcustomPosYUnit`]: {
      type: "string",
      default: "px",
    },
    [`hov_MOB${controlName}bgImgRepeat`]: {
      type: "string",
    },
    // Mobile attributes end
    //  attributes for main background (not overlay) -> hover type 'hover' start  ⬇
  };

  const ovlWithoutBgiAttrs = {
    //  attributes for background overlay -> hover type 'normal' start  ⬇
    [`${controlName}isBgOverlay`]: {
      type: "boolean",
      default: false,
    },
    ...ovlTransitionAttr,

    [`${controlName}ovl_hoverType`]: {
      type: "string",
      default: "normal",
    },

    [`${controlName}overlayType`]: {
      type: "string",
      default: "classic",
    },
    [`${controlName}overlayColor`]: {
      type: "string",
    },
    [`${controlName}overlayGradient`]: {
      type: "string",
      default: "linear-gradient(45deg,#000000cc,#00000099)",
    },

    [`${controlName}ovl_opacity`]: {
      type: "number",
      default: 0.5,
    },
    [`${controlName}ovl_blendMode`]: {
      type: "string",
    },
    [`${controlName}ovl_allowFilters`]: {
      type: "boolean",
      default: false,
    },
    [`${controlName}ovl_fltrBrightness`]: {
      type: "number",
      default: 100,
    },
    [`${controlName}ovl_fltrContrast`]: {
      type: "number",
      default: 100,
    },
    [`${controlName}ovl_fltrSaturation`]: {
      type: "number",
      default: 100,
    },
    [`${controlName}ovl_fltrBlur`]: {
      type: "number",
      default: 0,
    },
    [`${controlName}ovl_fltrHue`]: {
      type: "number",
      default: 0,
    },
    //  attributes for background overlay -> hover type 'normal' end

    //  attributes for background overlay -> hover type 'hover' start  ⬇
    [`hov_${controlName}overlayType`]: {
      type: "string",
      default: "classic",
    },
    [`hov_${controlName}overlayColor`]: {
      type: "string",
    },
    [`hov_${controlName}overlayGradient`]: {
      type: "string",
    },
    [`hov_${controlName}ovl_bgImageURL`]: {
      type: "string",
    },
    [`hov_${controlName}ovl_bgImageID`]: {
      type: "string",
    },
    [`hov_${controlName}ovl_bgImgAttachment`]: {
      type: "string",
    },
    [`hov_${controlName}ovl_opacity`]: {
      type: "number",
    },
    [`hov_${controlName}ovl_blendMode`]: {
      type: "string",
    },
    [`hov_${controlName}ovl_allowFilters`]: {
      type: "boolean",
      default: false,
    },
    [`hov_${controlName}ovl_fltrBrightness`]: {
      type: "number",
    },
    [`hov_${controlName}ovl_fltrContrast`]: {
      type: "number",
    },
    [`hov_${controlName}ovl_fltrSaturation`]: {
      type: "number",
    },
    [`hov_${controlName}ovl_fltrBlur`]: {
      type: "number",
    },
    [`hov_${controlName}ovl_fltrHue`]: {
      type: "number",
    },
    //  attributes for background overlay -> hover type 'hover' end
  };

  const ovlBgiAttrs = {
    //  attributes for background overlay -> hover type 'normal' start  ⬇
    // desktop attributes start ⬇
    [`${controlName}ovl_bgImageURL`]: {
      type: "string",
    },
    [`${controlName}ovl_bgImageID`]: {
      type: "string",
    },
    [`${controlName}ovl_bgImgAttachment`]: {
      type: "string",
    },
    [`${controlName}ovl_backgroundSize`]: {
      type: "string",
    },
    [`${controlName}ovl_bgImgCustomSize`]: {
      type: "number",
      default: 100,
    },
    [`${controlName}ovl_bgImgCustomSizeUnit`]: {
      type: "string",
      default: "%",
    },
    [`${controlName}ovl_bgImgPos`]: {
      type: "string",
    },
    [`${controlName}ovl_bgImgcustomPosX`]: {
      type: "number",
      default: 0,
    },
    [`${controlName}ovl_bgImgcustomPosXUnit`]: {
      type: "string",
      default: "px",
    },
    [`${controlName}ovl_bgImgcustomPosY`]: {
      type: "number",
      default: 0,
    },
    [`${controlName}ovl_bgImgcustomPosYUnit`]: {
      type: "string",
      default: "px",
    },
    [`${controlName}ovl_bgImgRepeat`]: {
      type: "string",
    },
    // desktop attributes end

    // Tab attributes start ⬇
    [`TAB${controlName}ovl_backgroundSize`]: {
      type: "string",
    },
    [`TAB${controlName}ovl_bgImgCustomSize`]: {
      type: "number",
      default: 100,
    },
    [`TAB${controlName}ovl_bgImgCustomSizeUnit`]: {
      type: "string",
      default: "%",
    },
    [`TAB${controlName}ovl_bgImgPos`]: {
      type: "string",
    },
    [`TAB${controlName}ovl_bgImgcustomPosX`]: {
      type: "number",
      default: 0,
    },
    [`TAB${controlName}ovl_bgImgcustomPosXUnit`]: {
      type: "string",
      default: "px",
    },
    [`TAB${controlName}ovl_bgImgcustomPosY`]: {
      type: "number",
      default: 0,
    },
    [`TAB${controlName}ovl_bgImgcustomPosYUnit`]: {
      type: "string",
      default: "px",
    },
    [`TAB${controlName}ovl_bgImgRepeat`]: {
      type: "string",
    },
    // Tab attributes end

    // Mob attributes start ⬇
    [`MOB${controlName}ovl_backgroundSize`]: {
      type: "string",
    },
    [`MOB${controlName}ovl_bgImgCustomSize`]: {
      type: "number",
      default: 100,
    },
    [`MOB${controlName}ovl_bgImgCustomSizeUnit`]: {
      type: "string",
      default: "%",
    },
    [`MOB${controlName}ovl_bgImgPos`]: {
      type: "string",
    },
    [`MOB${controlName}ovl_bgImgcustomPosX`]: {
      type: "number",
      default: 0,
    },
    [`MOB${controlName}ovl_bgImgcustomPosXUnit`]: {
      type: "string",
      default: "px",
    },
    [`MOB${controlName}ovl_bgImgcustomPosY`]: {
      type: "number",
      default: 0,
    },
    [`MOB${controlName}ovl_bgImgcustomPosYUnit`]: {
      type: "string",
      default: "px",
    },
    [`MOB${controlName}ovl_bgImgRepeat`]: {
      type: "string",
    },
    // Mob attributes end
    //  attributes for background overlay -> hover type 'normal' end

    //  attributes for background overlay -> hover type 'hover' start  ⬇
    // desktop attributes start ⬇
    [`hov_${controlName}ovl_backgroundSize`]: {
      type: "string",
    },
    [`hov_${controlName}ovl_bgImgCustomSize`]: {
      type: "number",
      default: 100,
    },
    [`hov_${controlName}ovl_bgImgCustomSizeUnit`]: {
      type: "string",
      default: "%",
    },
    [`hov_${controlName}ovl_bgImgPos`]: {
      type: "string",
    },
    [`hov_${controlName}ovl_bgImgcustomPosX`]: {
      type: "number",
      default: 0,
    },
    [`hov_${controlName}ovl_bgImgcustomPosXUnit`]: {
      type: "string",
      default: "px",
    },
    [`hov_${controlName}ovl_bgImgcustomPosY`]: {
      type: "number",
      default: 0,
    },
    [`hov_${controlName}ovl_bgImgcustomPosYUnit`]: {
      type: "string",
      default: "px",
    },
    [`hov_${controlName}ovl_bgImgRepeat`]: {
      type: "string",
    },
    // desktop attributes end

    // Tab attributes start ⬇
    [`hov_TAB${controlName}ovl_backgroundSize`]: {
      type: "string",
    },
    [`hov_TAB${controlName}ovl_bgImgCustomSize`]: {
      type: "number",
    },
    [`hov_TAB${controlName}ovl_bgImgCustomSizeUnit`]: {
      type: "string",
      default: "%",
    },
    [`hov_TAB${controlName}ovl_bgImgPos`]: {
      type: "string",
    },
    [`hov_TAB${controlName}ovl_bgImgcustomPosX`]: {
      type: "number",
    },
    [`hov_TAB${controlName}ovl_bgImgcustomPosXUnit`]: {
      type: "string",
      default: "px",
    },
    [`hov_TAB${controlName}ovl_bgImgcustomPosY`]: {
      type: "number",
    },
    [`hov_TAB${controlName}ovl_bgImgcustomPosYUnit`]: {
      type: "string",
      default: "px",
    },
    [`hov_TAB${controlName}ovl_bgImgRepeat`]: {
      type: "string",
    },
    // Tab attributes end

    // Mob attributes start ⬇
    [`hov_MOB${controlName}ovl_backgroundSize`]: {
      type: "string",
    },
    [`hov_MOB${controlName}ovl_bgImgCustomSize`]: {
      type: "number",
    },
    [`hov_MOB${controlName}ovl_bgImgCustomSizeUnit`]: {
      type: "string",
      default: "%",
    },
    [`hov_MOB${controlName}ovl_bgImgPos`]: {
      type: "string",
    },
    [`hov_MOB${controlName}ovl_bgImgcustomPosX`]: {
      type: "number",
    },
    [`hov_MOB${controlName}ovl_bgImgcustomPosXUnit`]: {
      type: "string",
      default: "px",
    },
    [`hov_MOB${controlName}ovl_bgImgcustomPosY`]: {
      type: "number",
    },
    [`hov_MOB${controlName}ovl_bgImgcustomPosYUnit`]: {
      type: "string",
      default: "px",
    },
    [`hov_MOB${controlName}ovl_bgImgRepeat`]: {
      type: "string",
    },
    // Mob attributes end
    //  attributes for background overlay -> hover type 'hover' end
  };

  let result = {};

  if (forButton === true) {
    result = {
      ...mainWithoutBgiAttrs,
    };
  } else {
    result =
      noOverlay === true
        ? noMainBgi === true
          ? {
              ...mainWithoutBgiAttrs,
            }
          : {
              ...mainWithoutBgiAttrs,
              ...mainBgiAttrs,
            }
        : noOverlayBgi === true && noMainBgi === true
        ? {
            ...mainWithoutBgiAttrs,
            ...ovlWithoutBgiAttrs,
          }
        : noOverlayBgi === true && noMainBgi === false
        ? {
            ...mainWithoutBgiAttrs,
            ...mainBgiAttrs,
            ...ovlWithoutBgiAttrs,
          }
        : noOverlayBgi === false && noMainBgi === true
        ? {
            ...mainWithoutBgiAttrs,
            ...ovlWithoutBgiAttrs,
            ...ovlBgiAttrs,
          }
        : {
            ...mainWithoutBgiAttrs,
            ...mainBgiAttrs,
            ...ovlWithoutBgiAttrs,
            ...ovlBgiAttrs,
          };
  }

  return result;
};

// function to generate Background control styles based on the unique controlName(prefix)
export const generateBackgroundControlStyles = ({
  controlName,
  attributes,
  noOverlay = false,
  noMainBgi = false,
  noOverlayBgi = false,
  noTransition = false,
  forButton = false,
}) => {
  let BGnoOverlay = noOverlay;
  let BGnoMainBgi = noMainBgi;
  let BGnoOverlayBgi = noOverlayBgi;

  if (forButton === true) {
    BGnoOverlay = true;
    BGnoMainBgi = true;
    BGnoOverlayBgi = true;
  }

  const {
    // background attributes starts ⬇
    // [`${controlName}bg_hoverType`]: bg_hoverType,
    [`${controlName}bg_transition`]: bg_transition,

    //  attributes for bg_hoverType normal start  ⬇
    [`${controlName}backgroundType`]: backgroundType,
    [`${controlName}backgroundColor`]: backgroundColor,
    [`${controlName}gradientColor`]: gradientColor,
    [`${controlName}bgImageURL`]: bgImageURL,
    // [`${controlName}bgImageID`]: bgImageID,
    [`${controlName}backgroundSize`]: backgroundSize,
    [`${controlName}bgImgCustomSize`]: bgImgCustomSize,
    [`${controlName}bgImgCustomSizeUnit`]: bgImgCustomSizeUnit,
    [`${controlName}bgImgPos`]: bgImgPos,
    [`${controlName}bgImgcustomPosX`]: bgImgcustomPosX,
    [`${controlName}bgImgcustomPosXUnit`]: bgImgcustomPosXUnit,
    [`${controlName}bgImgcustomPosY`]: bgImgcustomPosY,
    [`${controlName}bgImgcustomPosYUnit`]: bgImgcustomPosYUnit,
    [`${controlName}bgImgAttachment`]: bgImgAttachment,
    [`${controlName}bgImgRepeat`]: bgImgRepeat,

    [`TAB${controlName}backgroundSize`]: TABbackgroundSize,
    [`TAB${controlName}bgImgCustomSize`]: TABbgImgCustomSize,
    [`TAB${controlName}bgImgCustomSizeUnit`]: TABbgImgCustomSizeUnit,
    [`TAB${controlName}bgImgPos`]: TABbgImgPos,
    [`TAB${controlName}bgImgcustomPosX`]: TABbgImgcustomPosX,
    [`TAB${controlName}bgImgcustomPosXUnit`]: TABbgImgcustomPosXUnit,
    [`TAB${controlName}bgImgcustomPosY`]: TABbgImgcustomPosY,
    [`TAB${controlName}bgImgcustomPosYUnit`]: TABbgImgcustomPosYUnit,
    [`TAB${controlName}bgImgRepeat`]: TABbgImgRepeat,

    [`MOB${controlName}backgroundSize`]: MOBbackgroundSize,
    [`MOB${controlName}bgImgCustomSize`]: MOBbgImgCustomSize,
    [`MOB${controlName}bgImgCustomSizeUnit`]: MOBbgImgCustomSizeUnit,
    [`MOB${controlName}bgImgPos`]: MOBbgImgPos,
    [`MOB${controlName}bgImgcustomPosX`]: MOBbgImgcustomPosX,
    [`MOB${controlName}bgImgcustomPosXUnit`]: MOBbgImgcustomPosXUnit,
    [`MOB${controlName}bgImgcustomPosY`]: MOBbgImgcustomPosY,
    [`MOB${controlName}bgImgcustomPosYUnit`]: MOBbgImgcustomPosYUnit,
    [`MOB${controlName}bgImgRepeat`]: MOBbgImgRepeat,
    //  attributes for bg_hoverType normal end

    //  attributes for bg_hoverType hover start  ⬇
    [`hov_${controlName}backgroundType`]: hov_backgroundType,
    [`hov_${controlName}backgroundColor`]: hov_backgroundColor,
    [`hov_${controlName}gradientColor`]: hov_gradientColor,
    [`hov_${controlName}bgImageURL`]: hov_bgImageURL,
    // [`hov_${controlName}bgImageID`]: hov_bgImageID,
    [`hov_${controlName}bgImgAttachment`]: hov_bgImgAttachment,

    [`hov_${controlName}backgroundSize`]: hov_backgroundSize,
    [`hov_${controlName}bgImgCustomSize`]: hov_bgImgCustomSize,
    [`hov_${controlName}bgImgCustomSizeUnit`]: hov_bgImgCustomSizeUnit,
    [`hov_${controlName}bgImgPos`]: hov_bgImgPos,
    [`hov_${controlName}bgImgcustomPosX`]: hov_bgImgcustomPosX,
    [`hov_${controlName}bgImgcustomPosXUnit`]: hov_bgImgcustomPosXUnit,
    [`hov_${controlName}bgImgcustomPosY`]: hov_bgImgcustomPosY,
    [`hov_${controlName}bgImgcustomPosYUnit`]: hov_bgImgcustomPosYUnit,
    [`hov_${controlName}bgImgRepeat`]: hov_bgImgRepeat,

    [`hov_TAB${controlName}backgroundSize`]: hov_TABbackgroundSize,
    [`hov_TAB${controlName}bgImgCustomSize`]: hov_TABbgImgCustomSize,
    [`hov_TAB${controlName}bgImgCustomSizeUnit`]: hov_TABbgImgCustomSizeUnit,
    [`hov_TAB${controlName}bgImgPos`]: hov_TABbgImgPos,
    [`hov_TAB${controlName}bgImgcustomPosX`]: hov_TABbgImgcustomPosX,
    [`hov_TAB${controlName}bgImgcustomPosXUnit`]: hov_TABbgImgcustomPosXUnit,
    [`hov_TAB${controlName}bgImgcustomPosY`]: hov_TABbgImgcustomPosY,
    [`hov_TAB${controlName}bgImgcustomPosYUnit`]: hov_TABbgImgcustomPosYUnit,
    [`hov_TAB${controlName}bgImgRepeat`]: hov_TABbgImgRepeat,

    [`hov_MOB${controlName}backgroundSize`]: hov_MOBbackgroundSize,
    [`hov_MOB${controlName}bgImgCustomSize`]: hov_MOBbgImgCustomSize,
    [`hov_MOB${controlName}bgImgCustomSizeUnit`]: hov_MOBbgImgCustomSizeUnit,
    [`hov_MOB${controlName}bgImgPos`]: hov_MOBbgImgPos,
    [`hov_MOB${controlName}bgImgcustomPosX`]: hov_MOBbgImgcustomPosX,
    [`hov_MOB${controlName}bgImgcustomPosXUnit`]: hov_MOBbgImgcustomPosXUnit,
    [`hov_MOB${controlName}bgImgcustomPosY`]: hov_MOBbgImgcustomPosY,
    [`hov_MOB${controlName}bgImgcustomPosYUnit`]: hov_MOBbgImgcustomPosYUnit,
    [`hov_MOB${controlName}bgImgRepeat`]: hov_MOBbgImgRepeat,
    //  attributes for bg_hoverType hover end
    // background attributes end

    // background overlay attributes start
    [`${controlName}isBgOverlay`]: isBgOverlay,
    // [`${controlName}ovl_hoverType`]: ovl_hoverType,
    [`${controlName}ovl_bg_transition`]: ovl_bg_transition,
    [`${controlName}ovl_filtersTransition`]: ovl_filtersTransition,
    [`${controlName}ovl_opacityTransition`]: ovl_opacityTransition,

    //  attributes for ovl_hoverType normal start  ⬇
    [`${controlName}overlayType`]: overlayType,
    [`${controlName}overlayColor`]: overlayColor,
    [`${controlName}overlayGradient`]: overlayGradient,
    [`${controlName}ovl_bgImageURL`]: ovl_bgImageURL,
    // [`${controlName}ovl_bgImageID`]: ovl_bgImageID,
    [`${controlName}ovl_bgImgAttachment`]: ovl_bgImgAttachment,

    [`${controlName}ovl_opacity`]: ovl_opacity,
    [`${controlName}ovl_blendMode`]: ovl_blendMode,

    [`${controlName}ovl_allowFilters`]: ovl_allowFilters,
    [`${controlName}ovl_fltrBrightness`]: ovl_fltrBrightness,
    [`${controlName}ovl_fltrContrast`]: ovl_fltrContrast,
    [`${controlName}ovl_fltrSaturation`]: ovl_fltrSaturation,
    [`${controlName}ovl_fltrBlur`]: ovl_fltrBlur,
    [`${controlName}ovl_fltrHue`]: ovl_fltrHue,

    [`${controlName}ovl_backgroundSize`]: ovl_backgroundSize,
    [`${controlName}ovl_bgImgCustomSize`]: ovl_bgImgCustomSize,
    [`${controlName}ovl_bgImgCustomSizeUnit`]: ovl_bgImgCustomSizeUnit,
    [`${controlName}ovl_bgImgPos`]: ovl_bgImgPos,
    [`${controlName}ovl_bgImgcustomPosX`]: ovl_bgImgcustomPosX,
    [`${controlName}ovl_bgImgcustomPosXUnit`]: ovl_bgImgcustomPosXUnit,
    [`${controlName}ovl_bgImgcustomPosY`]: ovl_bgImgcustomPosY,
    [`${controlName}ovl_bgImgcustomPosYUnit`]: ovl_bgImgcustomPosYUnit,
    [`${controlName}ovl_bgImgRepeat`]: ovl_bgImgRepeat,

    [`TAB${controlName}ovl_backgroundSize`]: TABovl_backgroundSize,
    [`TAB${controlName}ovl_bgImgCustomSize`]: TABovl_bgImgCustomSize,
    [`TAB${controlName}ovl_bgImgCustomSizeUnit`]: TABovl_bgImgCustomSizeUnit,
    [`TAB${controlName}ovl_bgImgPos`]: TABovl_bgImgPos,
    [`TAB${controlName}ovl_bgImgcustomPosX`]: TABovl_bgImgcustomPosX,
    [`TAB${controlName}ovl_bgImgcustomPosXUnit`]: TABovl_bgImgcustomPosXUnit,
    [`TAB${controlName}ovl_bgImgcustomPosY`]: TABovl_bgImgcustomPosY,
    [`TAB${controlName}ovl_bgImgcustomPosYUnit`]: TABovl_bgImgcustomPosYUnit,
    [`TAB${controlName}ovl_bgImgRepeat`]: TABovl_bgImgRepeat,

    [`MOB${controlName}ovl_backgroundSize`]: MOBovl_backgroundSize,
    [`MOB${controlName}ovl_bgImgCustomSize`]: MOBovl_bgImgCustomSize,
    [`MOB${controlName}ovl_bgImgCustomSizeUnit`]: MOBovl_bgImgCustomSizeUnit,
    [`MOB${controlName}ovl_bgImgPos`]: MOBovl_bgImgPos,
    [`MOB${controlName}ovl_bgImgcustomPosX`]: MOBovl_bgImgcustomPosX,
    [`MOB${controlName}ovl_bgImgcustomPosXUnit`]: MOBovl_bgImgcustomPosXUnit,
    [`MOB${controlName}ovl_bgImgcustomPosY`]: MOBovl_bgImgcustomPosY,
    [`MOB${controlName}ovl_bgImgcustomPosYUnit`]: MOBovl_bgImgcustomPosYUnit,
    [`MOB${controlName}ovl_bgImgRepeat`]: MOBovl_bgImgRepeat,
    //  attributes for ovl_hoverType normal end

    //  attributes for ovl_hoverType hover start ⬇
    [`hov_${controlName}overlayType`]: hov_overlayType,
    [`hov_${controlName}overlayColor`]: hov_overlayColor,
    [`hov_${controlName}overlayGradient`]: hov_overlayGradient,
    [`hov_${controlName}ovl_bgImageURL`]: hov_ovl_bgImageURL,
    // [`hov_${controlName}ovl_bgImageID`]: hov_ovl_bgImageID,
    [`hov_${controlName}ovl_bgImgAttachment`]: hov_ovl_bgImgAttachment,

    [`hov_${controlName}ovl_opacity`]: hov_ovl_opacity,
    [`hov_${controlName}ovl_blendMode`]: hov_ovl_blendMode,

    [`hov_${controlName}ovl_allowFilters`]: hov_ovl_allowFilters,
    [`hov_${controlName}ovl_fltrBrightness`]: hov_ovl_fltrBrightness,
    [`hov_${controlName}ovl_fltrContrast`]: hov_ovl_fltrContrast,
    [`hov_${controlName}ovl_fltrSaturation`]: hov_ovl_fltrSaturation,
    [`hov_${controlName}ovl_fltrBlur`]: hov_ovl_fltrBlur,
    [`hov_${controlName}ovl_fltrHue`]: hov_ovl_fltrHue,

    [`hov_${controlName}ovl_backgroundSize`]: hov_ovl_backgroundSize,
    [`hov_${controlName}ovl_bgImgCustomSize`]: hov_ovl_bgImgCustomSize,
    [`hov_${controlName}ovl_bgImgCustomSizeUnit`]: hov_ovl_bgImgCustomSizeUnit,
    [`hov_${controlName}ovl_bgImgPos`]: hov_ovl_bgImgPos,
    [`hov_${controlName}ovl_bgImgcustomPosX`]: hov_ovl_bgImgcustomPosX,
    [`hov_${controlName}ovl_bgImgcustomPosXUnit`]: hov_ovl_bgImgcustomPosXUnit,
    [`hov_${controlName}ovl_bgImgcustomPosY`]: hov_ovl_bgImgcustomPosY,
    [`hov_${controlName}ovl_bgImgcustomPosYUnit`]: hov_ovl_bgImgcustomPosYUnit,
    [`hov_${controlName}ovl_bgImgRepeat`]: hov_ovl_bgImgRepeat,

    [`hov_TAB${controlName}ovl_backgroundSize`]: hov_TABovl_backgroundSize,
    [`hov_TAB${controlName}ovl_bgImgCustomSize`]: hov_TABovl_bgImgCustomSize,
    [`hov_TAB${controlName}ovl_bgImgCustomSizeUnit`]:
      hov_TABovl_bgImgCustomSizeUnit,
    [`hov_TAB${controlName}ovl_bgImgPos`]: hov_TABovl_bgImgPos,
    [`hov_TAB${controlName}ovl_bgImgcustomPosX`]: hov_TABovl_bgImgcustomPosX,
    [`hov_TAB${controlName}ovl_bgImgcustomPosXUnit`]:
      hov_TABovl_bgImgcustomPosXUnit,
    [`hov_TAB${controlName}ovl_bgImgcustomPosY`]: hov_TABovl_bgImgcustomPosY,
    [`hov_TAB${controlName}ovl_bgImgcustomPosYUnit`]:
      hov_TABovl_bgImgcustomPosYUnit,
    [`hov_TAB${controlName}ovl_bgImgRepeat`]: hov_TABovl_bgImgRepeat,

    [`hov_MOB${controlName}ovl_backgroundSize`]: hov_MOBovl_backgroundSize,
    [`hov_MOB${controlName}ovl_bgImgCustomSize`]: hov_MOBovl_bgImgCustomSize,
    [`hov_MOB${controlName}ovl_bgImgCustomSizeUnit`]:
      hov_MOBovl_bgImgCustomSizeUnit,
    [`hov_MOB${controlName}ovl_bgImgPos`]: hov_MOBovl_bgImgPos,
    [`hov_MOB${controlName}ovl_bgImgcustomPosX`]: hov_MOBovl_bgImgcustomPosX,
    [`hov_MOB${controlName}ovl_bgImgcustomPosXUnit`]:
      hov_MOBovl_bgImgcustomPosXUnit,
    [`hov_MOB${controlName}ovl_bgImgcustomPosY`]: hov_MOBovl_bgImgcustomPosY,
    [`hov_MOB${controlName}ovl_bgImgcustomPosYUnit`]:
      hov_MOBovl_bgImgcustomPosYUnit,
    [`hov_MOB${controlName}ovl_bgImgRepeat`]: hov_MOBovl_bgImgRepeat,
    //  attributes for ovl_hoverType hover end ⬇

    // background overlay attributes end
  } = attributes;

  const backgroundStylesDesktop = `
  ${
    (BGnoMainBgi === false && backgroundType === "classic" && bgImageURL) ||
    (backgroundType === "gradient" && gradientColor)
      ? `
    background-image: ${
      backgroundType === "classic"
        ? `url("${bgImageURL}")`
        : backgroundType === "gradient"
        ? gradientColor
        : "none"
    };
    `
      : " "
  }
  
  ${
    BGnoMainBgi === false && backgroundType === "classic" && bgImageURL
      ? `
      ${
        backgroundSize && backgroundSize !== "custom"
          ? `background-size: ${backgroundSize};`
          : backgroundSize === "custom"
          ? `background-size: ${bgImgCustomSize}${bgImgCustomSizeUnit} auto;`
          : " "
      }

      ${
        bgImgPos && bgImgPos !== "custom"
          ? `background-position: ${bgImgPos};`
          : bgImgPos === "custom"
          ? `background-position: ${bgImgcustomPosX}${bgImgcustomPosXUnit} ${bgImgcustomPosY}${bgImgcustomPosYUnit};`
          : " "
      }

      ${bgImgAttachment ? `background-attachment: ${bgImgAttachment};` : " "}

      ${bgImgRepeat ? `background-repeat: ${bgImgRepeat};` : " "}
      
      
      `
      : " "
  }

  ${
    isBgOverlay
      ? `
        z-index: 2;
        position: relative;
      `
      : " "
  }	

  ${backgroundColor ? `background-color: ${backgroundColor};` : " "}
  
  ${
    forButton === true
      ? `
    position: relative;
    overflow: hidden;
    z-index:1;
    
    `
      : ""
  }
    `;

  const hoverBackgroundStylesDesktop = `

    ${
      forButton === true
        ? `
        content: " ";
        position: absolute;
        left: 0;
        top: 0;
        right: 0;
        bottom: 0;
        z-index: -1;
        opacity: 0;
        transition: all ${bg_transition || 0}s;

        `
        : ""
    }

    ${
      (BGnoMainBgi === false &&
        hov_backgroundType === "classic" &&
        hov_bgImageURL) ||
      (hov_backgroundType === "gradient" && hov_gradientColor)
        ? `
        background-image: ${
          hov_backgroundType === "classic"
            ? `url("${hov_bgImageURL}")`
            : hov_backgroundType === "gradient"
            ? hov_gradientColor
            : "none"
        };    
        `
        : " "
    }
  
    ${
      BGnoMainBgi === false &&
      hov_backgroundType === "classic" &&
      hov_bgImageURL
        ? `
        ${
          hov_backgroundSize && hov_backgroundSize !== "custom"
            ? `background-size: ${hov_backgroundSize};`
            : hov_backgroundSize === "custom"
            ? `background-size: ${hov_bgImgCustomSize}${hov_bgImgCustomSizeUnit} auto;`
            : " "
        }
  
        ${
          hov_bgImgPos && hov_bgImgPos !== "custom"
            ? `background-position: ${hov_bgImgPos};`
            : hov_bgImgPos === "custom"
            ? `background-position: ${hov_bgImgcustomPosX}${hov_bgImgcustomPosXUnit} ${hov_bgImgcustomPosY}${hov_bgImgcustomPosYUnit};`
            : " "
        }
  
        ${
          hov_bgImgAttachment
            ? `background-attachment: ${hov_bgImgAttachment};`
            : " "
        }
  
        ${hov_bgImgRepeat ? `background-repeat: ${hov_bgImgRepeat};` : " "}
        
        `
        : " "
    }
  
    ${hov_backgroundColor ? `background-color: ${hov_backgroundColor};` : " "}
  
  `;

  const backgroundStylesTab = `
      ${
        BGnoMainBgi === false && backgroundType === "classic" && bgImageURL
          ? `
          ${
            TABbackgroundSize && TABbackgroundSize !== "custom"
              ? `background-size: ${TABbackgroundSize};`
              : TABbackgroundSize === "custom"
              ? `background-size: ${TABbgImgCustomSize}${TABbgImgCustomSizeUnit} auto;`
              : " "
          }
  
          ${
            TABbgImgPos && TABbgImgPos !== "custom"
              ? `background-position: ${TABbgImgPos};`
              : TABbgImgPos === "custom"
              ? `background-position: ${TABbgImgcustomPosX}${TABbgImgcustomPosXUnit} ${TABbgImgcustomPosY}${TABbgImgcustomPosYUnit};`
              : " "
          }
  
          ${TABbgImgRepeat ? `background-repeat: ${TABbgImgRepeat};` : " "}
          background-attachment: scroll;
          `
          : " "
      }
  
    `;

  const hoverBackgroundStylesTab = `
    ${
      BGnoMainBgi === false &&
      hov_backgroundType === "classic" &&
      hov_bgImageURL
        ? `
        ${
          hov_TABbackgroundSize && hov_TABbackgroundSize !== "custom"
            ? `background-size: ${hov_TABbackgroundSize};`
            : hov_TABbackgroundSize === "custom"
            ? `background-size: ${hov_TABbgImgCustomSize}${hov_TABbgImgCustomSizeUnit} auto;`
            : " "
        }
  
        ${
          hov_TABbgImgPos && hov_TABbgImgPos !== "custom"
            ? `background-position: ${hov_TABbgImgPos};`
            : hov_TABbgImgPos === "custom"
            ? `background-position: ${hov_TABbgImgcustomPosX}${hov_TABbgImgcustomPosXUnit} ${hov_TABbgImgcustomPosY}${hov_TABbgImgcustomPosYUnit};`
            : " "
        }
  
        ${
          hov_TABbgImgRepeat ? `background-repeat: ${hov_TABbgImgRepeat};` : " "
        }
        background-attachment: scroll;
        `
        : " "
    }
  
  `;

  const backgroundStylesMobile = `
      ${
        BGnoMainBgi === false && backgroundType === "classic" && bgImageURL
          ? `
          ${
            MOBbackgroundSize && MOBbackgroundSize !== "custom"
              ? `background-size: ${MOBbackgroundSize};`
              : MOBbackgroundSize === "custom"
              ? `background-size: ${MOBbgImgCustomSize}${MOBbgImgCustomSizeUnit} auto;`
              : " "
          }
  
          ${
            MOBbgImgPos && MOBbgImgPos !== "custom"
              ? `background-position: ${MOBbgImgPos};`
              : MOBbgImgPos === "custom"
              ? `background-position: ${MOBbgImgcustomPosX}${MOBbgImgcustomPosXUnit} ${MOBbgImgcustomPosY}${MOBbgImgcustomPosYUnit};`
              : " "
          }
  
          ${MOBbgImgRepeat ? `background-repeat: ${MOBbgImgRepeat};` : " "}
  
          `
          : " "
      }
  
    `;

  const hoverBackgroundStylesMobile = `
    ${
      BGnoMainBgi === false &&
      hov_backgroundType === "classic" &&
      hov_bgImageURL
        ? `
        ${
          hov_MOBbackgroundSize && hov_MOBbackgroundSize !== "custom"
            ? `background-size: ${hov_MOBbackgroundSize};`
            : hov_MOBbackgroundSize === "custom"
            ? `background-size: ${hov_MOBbgImgCustomSize}${hov_MOBbgImgCustomSizeUnit} auto;`
            : " "
        }
    
        ${
          hov_MOBbgImgPos && hov_MOBbgImgPos !== "custom"
            ? `background-position: ${hov_MOBbgImgPos};`
            : hov_MOBbgImgPos === "custom"
            ? `background-position: ${hov_MOBbgImgcustomPosX}${hov_MOBbgImgcustomPosXUnit} ${hov_MOBbgImgcustomPosY}${hov_MOBbgImgcustomPosYUnit};`
            : " "
        }
    
        ${
          hov_MOBbgImgRepeat ? `background-repeat: ${hov_MOBbgImgRepeat};` : " "
        }
    
        `
        : " "
    }
    
    `;

  const overlayStylesDesktop = `
    
      ${
        BGnoOverlay === false && isBgOverlay
          ? `
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            right: 0;
            left: 0;
            z-index: 0;
            ${
              (BGnoOverlayBgi === false &&
                overlayType === "classic" &&
                ovl_bgImageURL) ||
              (overlayType === "gradient" && overlayGradient)
                ? `
                background-image: ${
                  overlayType === "classic"
                    ? `url("${ovl_bgImageURL}")`
                    : overlayType === "gradient"
                    ? overlayGradient
                    : "none"
                };              
              `
                : " "
            }
           
            ${overlayColor ? `background-color: ${overlayColor};` : " "}
            ${
              ovl_opacity || ovl_opacity === 0
                ? `opacity: ${ovl_opacity};`
                : " "
            }
            ${ovl_blendMode ? `mix-blend-mode: ${ovl_blendMode};` : " "}
            ${
              ovl_allowFilters
                ? `filter: brightness( ${ovl_fltrBrightness}% ) contrast( ${ovl_fltrContrast}% ) saturate( ${ovl_fltrSaturation}% ) blur( ${ovl_fltrBlur}px ) hue-rotate( 
              ${ovl_fltrHue}deg );`
                : " "
            }
  
        ${
          BGnoOverlayBgi === false &&
          overlayType === "classic" &&
          ovl_bgImageURL
            ? `
            ${
              ovl_backgroundSize && ovl_backgroundSize !== "custom"
                ? `background-size: ${ovl_backgroundSize};`
                : ovl_backgroundSize === "custom"
                ? `background-size: ${ovl_bgImgCustomSize}${ovl_bgImgCustomSizeUnit} auto;`
                : " "
            }
  
            ${
              ovl_bgImgPos && ovl_bgImgPos !== "custom"
                ? `background-position: ${ovl_bgImgPos};`
                : ovl_bgImgPos === "custom"
                ? `background-position: ${ovl_bgImgcustomPosX}${ovl_bgImgcustomPosXUnit} ${ovl_bgImgcustomPosY}${ovl_bgImgcustomPosYUnit};`
                : " "
            }
  
            ${
              ovl_bgImgAttachment
                ? `background-attachment: ${ovl_bgImgAttachment};`
                : " "
            }
  
            ${ovl_bgImgRepeat ? `background-repeat: ${ovl_bgImgRepeat};` : " "}
            
            `
            : " "
        }
  
        `
          : " "
      }
    
    
    `;

  const hoverOverlayStylesDesktop = `
    
    ${
      BGnoOverlay === false && isBgOverlay
        ? `
        ${
          (BGnoOverlayBgi === false &&
            hov_overlayType === "classic" &&
            hov_ovl_bgImageURL) ||
          (hov_overlayType === "gradient" && hov_overlayGradient)
            ? `
          background-image: ${
            hov_overlayType === "classic"
              ? `url("${hov_ovl_bgImageURL}")`
              : hov_overlayType === "gradient"
              ? hov_overlayGradient
              : "none"
          };
          `
            : " "
        }
  
        ${hov_overlayColor ? `background-color: ${hov_overlayColor};` : " "}
        ${
          hov_ovl_opacity || hov_ovl_opacity === 0
            ? `opacity: ${hov_ovl_opacity};`
            : " "
        }
        ${hov_ovl_blendMode ? `mix-blend-mode: ${hov_ovl_blendMode};` : " "}
        ${
          hov_ovl_allowFilters
            ? `filter: brightness( ${hov_ovl_fltrBrightness}% ) contrast( ${hov_ovl_fltrContrast}% ) saturate( ${hov_ovl_fltrSaturation}% ) blur( ${hov_ovl_fltrBlur}px ) hue-rotate( 
          ${hov_ovl_fltrHue}deg );`
            : " "
        }
    
      ${
        BGnoOverlayBgi === false &&
        hov_overlayType === "classic" &&
        hov_ovl_bgImageURL
          ? `
          ${
            hov_ovl_backgroundSize && hov_ovl_backgroundSize !== "custom"
              ? `background-size: ${hov_ovl_backgroundSize};`
              : hov_ovl_backgroundSize === "custom"
              ? `background-size: ${hov_ovl_bgImgCustomSize}${hov_ovl_bgImgCustomSizeUnit} auto;`
              : " "
          }
    
          ${
            hov_ovl_bgImgPos && hov_ovl_bgImgPos !== "custom"
              ? `background-position: ${hov_ovl_bgImgPos};`
              : hov_ovl_bgImgPos === "custom"
              ? `background-position: ${hov_ovl_bgImgcustomPosX}${hov_ovl_bgImgcustomPosXUnit} ${hov_ovl_bgImgcustomPosY}${hov_ovl_bgImgcustomPosYUnit};`
              : " "
          }
    
          ${
            hov_ovl_bgImgAttachment
              ? `background-attachment: ${hov_ovl_bgImgAttachment};`
              : " "
          }
    
          ${
            hov_ovl_bgImgRepeat
              ? `background-repeat: ${hov_ovl_bgImgRepeat};`
              : " "
          }
          
          `
          : " "
      }
    
      `
        : " "
    }
    
    
    `;

  const overlayStylesTab = `
    ${
      BGnoOverlay === false &&
      BGnoOverlayBgi === false &&
      isBgOverlay &&
      overlayType === "classic" &&
      ovl_bgImageURL
        ? `
        ${
          TABovl_backgroundSize && TABovl_backgroundSize !== "custom"
            ? `background-size: ${TABovl_backgroundSize};`
            : TABovl_backgroundSize === "custom"
            ? `background-size: ${TABovl_bgImgCustomSize}${TABovl_bgImgCustomSizeUnit} auto;`
            : " "
        }
  
          ${
            TABovl_bgImgPos && TABovl_bgImgPos !== "custom"
              ? `background-position: ${TABovl_bgImgPos};`
              : TABovl_bgImgPos === "custom"
              ? `background-position: ${TABovl_bgImgcustomPosX}${TABovl_bgImgcustomPosXUnit} ${TABovl_bgImgcustomPosY}${TABovl_bgImgcustomPosYUnit};`
              : " "
          }
  
          ${
            TABovl_bgImgRepeat
              ? `background-repeat: ${TABovl_bgImgRepeat};`
              : " "
          }
          background-attachment: scroll;
        `
        : " "
    }
    
    `;

  const hoverOverlayStylesTab = `
  ${
    BGnoOverlay === false &&
    BGnoOverlayBgi === false &&
    isBgOverlay &&
    hov_overlayType === "classic" &&
    hov_ovl_bgImageURL
      ? `
      ${
        hov_TABovl_backgroundSize && hov_TABovl_backgroundSize !== "custom"
          ? `background-size: ${hov_TABovl_backgroundSize};`
          : hov_TABovl_backgroundSize === "custom"
          ? `background-size: ${hov_TABovl_bgImgCustomSize}${hov_TABovl_bgImgCustomSizeUnit} auto;`
          : " "
      }
  
        ${
          hov_TABovl_bgImgPos && hov_TABovl_bgImgPos !== "custom"
            ? `background-position: ${hov_TABovl_bgImgPos};`
            : hov_TABovl_bgImgPos === "custom"
            ? `background-position: ${hov_TABovl_bgImgcustomPosX}${hov_TABovl_bgImgcustomPosXUnit} ${hov_TABovl_bgImgcustomPosY}${hov_TABovl_bgImgcustomPosYUnit};`
            : " "
        }
  
        ${
          hov_TABovl_bgImgRepeat
            ? `background-repeat: ${hov_TABovl_bgImgRepeat};`
            : " "
        }
        background-attachment: scroll;
      `
      : " "
  }
  
  `;

  const overlayStylesMobile = `
    ${
      BGnoOverlay === false &&
      BGnoOverlayBgi === false &&
      isBgOverlay &&
      overlayType === "classic" &&
      ovl_bgImageURL
        ? `
        ${
          MOBovl_backgroundSize && MOBovl_backgroundSize !== "custom"
            ? `background-size: ${MOBovl_backgroundSize};`
            : MOBovl_backgroundSize === "custom"
            ? `background-size: ${MOBovl_bgImgCustomSize}${MOBovl_bgImgCustomSizeUnit} auto;`
            : " "
        }
  
        ${
          MOBovl_bgImgPos && MOBovl_bgImgPos !== "custom"
            ? `background-position: ${MOBovl_bgImgPos};`
            : MOBovl_bgImgPos === "custom"
            ? `background-position: ${MOBovl_bgImgcustomPosX}${MOBovl_bgImgcustomPosXUnit} ${MOBovl_bgImgcustomPosY}${MOBovl_bgImgcustomPosYUnit};`
            : " "
        }
  
        ${
          MOBovl_bgImgRepeat ? `background-repeat: ${MOBovl_bgImgRepeat};` : " "
        }
        `
        : " "
    }
    
    `;

  const hoverOverlayStylesMobile = `
    ${
      BGnoOverlay === false &&
      BGnoOverlayBgi === false &&
      isBgOverlay &&
      hov_overlayType === "classic" &&
      hov_ovl_bgImageURL
        ? `
        ${
          hov_MOBovl_backgroundSize && hov_MOBovl_backgroundSize !== "custom"
            ? `background-size: ${hov_MOBovl_backgroundSize};`
            : hov_MOBovl_backgroundSize === "custom"
            ? `background-size: ${hov_MOBovl_bgImgCustomSize}${hov_MOBovl_bgImgCustomSizeUnit} auto;`
            : " "
        }
  
        ${
          hov_MOBovl_bgImgPos && hov_MOBovl_bgImgPos !== "custom"
            ? `background-position: ${hov_MOBovl_bgImgPos};`
            : hov_MOBovl_bgImgPos === "custom"
            ? `background-position: ${hov_MOBovl_bgImgcustomPosX}${hov_MOBovl_bgImgcustomPosXUnit} ${hov_MOBovl_bgImgcustomPosY}${hov_MOBovl_bgImgcustomPosYUnit};`
            : " "
        }
  
        ${
          hov_MOBovl_bgImgRepeat
            ? `background-repeat: ${hov_MOBovl_bgImgRepeat};`
            : " "
        }
        `
        : " "
    }
    
    `;

  const bgTransitionStyle = noTransition
    ? " "
    : `background ${bg_transition || 0}s`;

  const ovlTransitionStyle = noTransition
    ? " "
    : `background ${ovl_bg_transition || 0}s, opacity ${
        ovl_opacityTransition || 0
      }s, filter ${ovl_filtersTransition || 0}s`;

  return {
    backgroundStylesDesktop,
    hoverBackgroundStylesDesktop,
    backgroundStylesTab,
    hoverBackgroundStylesTab,
    backgroundStylesMobile,
    hoverBackgroundStylesMobile,
    overlayStylesDesktop,
    hoverOverlayStylesDesktop,
    overlayStylesTab,
    hoverOverlayStylesTab,
    overlayStylesMobile,
    hoverOverlayStylesMobile,
    bgTransitionStyle,
    ovlTransitionStyle,
  };
};
