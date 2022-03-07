import { hasVal } from "./hasVal";

// function to generate New Dimensions-Control's attributes for multiple Dimensions control based on the array of values(prefixs)
export const generateDimensionsAttributes = (controlName, defaults = {}) => {
  const {
    top,
    right,
    bottom,
    left,
    isLinked = true,
    disableLeftRight = false,
  } = defaults;

  const desktopTop = hasVal(top)
    ? {
        [`${controlName}Top`]: {
          type: "string",
          default: `${top}`,
        },
      }
    : {
        [`${controlName}Top`]: {
          type: "string",
        },
      };

  const desktopRight = hasVal(right)
    ? {
        [`${controlName}Right`]: {
          type: "string",
          default: `${right}`,
        },
      }
    : {
        [`${controlName}Right`]: {
          type: "string",
        },
      };

  const desktopBottom = hasVal(bottom)
    ? {
        [`${controlName}Bottom`]: {
          type: "string",
          default: `${bottom}`,
        },
      }
    : {
        [`${controlName}Bottom`]: {
          type: "string",
        },
      };

  const desktopLeft = hasVal(left)
    ? {
        [`${controlName}Left`]: {
          type: "string",
          default: `${left}`,
        },
      }
    : {
        [`${controlName}Left`]: {
          type: "string",
        },
      };

  const objsAfterCaringForDisableLeftRightProp = disableLeftRight
    ? {
        ...desktopTop,
        ...desktopBottom,

        [`TAB${controlName}Top`]: {
          type: "string",
        },
        [`TAB${controlName}Bottom`]: {
          type: "string",
        },

        [`MOB${controlName}Top`]: {
          type: "string",
        },
        [`MOB${controlName}Bottom`]: {
          type: "string",
        },
      }
    : {
        ...desktopTop,
        ...desktopRight,
        ...desktopBottom,
        ...desktopLeft,

        [`TAB${controlName}Top`]: {
          type: "string",
        },
        [`TAB${controlName}Right`]: {
          type: "string",
        },
        [`TAB${controlName}Bottom`]: {
          type: "string",
        },
        [`TAB${controlName}Left`]: {
          type: "string",
        },

        [`MOB${controlName}Top`]: {
          type: "string",
        },
        [`MOB${controlName}Right`]: {
          type: "string",
        },
        [`MOB${controlName}Bottom`]: {
          type: "string",
        },
        [`MOB${controlName}Left`]: {
          type: "string",
        },
      };

  return {
    [`${controlName}isLinked`]: {
      type: "boolean",
      default: isLinked,
    },
    [`${controlName}Unit`]: {
      type: "string",
      default: "px",
    },
    [`TAB${controlName}Unit`]: {
      type: "string",
      default: "px",
    },
    [`MOB${controlName}Unit`]: {
      type: "string",
      default: "px",
    },
    ...objsAfterCaringForDisableLeftRightProp,
  };
};

//
// function to generate dimensions-controls styles for an element based on it's controlName(prefix)
export const generateDimensionsControlStyles = ({
  controlName,
  styleFor,
  attributes,
  disableLeftRight = false,
}) => {
  const {
    [`${controlName}isLinked`]: isLinked,

    [`${controlName}Unit`]: dimensionUnit,
    [`${controlName}Top`]: dimensionTop,
    [`${controlName}Right`]: dimensionRight,
    [`${controlName}Bottom`]: dimensionBottom,
    [`${controlName}Left`]: dimensionLeft,

    [`TAB${controlName}Unit`]: TABdimensionUnit,
    [`TAB${controlName}Top`]: TABdimensionTop,
    [`TAB${controlName}Right`]: TABdimensionRight,
    [`TAB${controlName}Bottom`]: TABdimensionBottom,
    [`TAB${controlName}Left`]: TABdimensionLeft,

    [`MOB${controlName}Unit`]: MOBdimensionUnit,
    [`MOB${controlName}Top`]: MOBdimensionTop,
    [`MOB${controlName}Right`]: MOBdimensionRight,
    [`MOB${controlName}Bottom`]: MOBdimensionBottom,
    [`MOB${controlName}Left`]: MOBdimensionLeft,
  } = attributes;

  let dimensionStylesDesktop = " ";
  let dimensionStylesTab = " ";
  let dimensionStylesMobile = " ";

  if (isLinked === true && disableLeftRight === false) {
    if (styleFor === "border") {
      dimensionStylesDesktop = `
            ${
              dimensionTop
                ? `border-width: ${parseFloat(dimensionTop)}${dimensionUnit}; `
                : " "
            }
        
            `;

      dimensionStylesTab = `
                ${
                  TABdimensionTop
                    ? `border-width: ${parseFloat(
                        TABdimensionTop
                      )}${TABdimensionUnit};`
                    : " "
                }
    
            `;

      dimensionStylesMobile = `
                ${
                  MOBdimensionTop
                    ? `border-width: ${parseFloat(
                        MOBdimensionTop
                      )}${MOBdimensionUnit};`
                    : " "
                }
    
            `;
    } else if (styleFor === "border-radius") {
      dimensionStylesDesktop = `
                ${
                  dimensionTop
                    ? `border-radius: ${parseFloat(
                        dimensionTop
                      )}${dimensionUnit};`
                    : " "
                }
        
            `;

      dimensionStylesTab = `
                ${
                  TABdimensionTop
                    ? `border-radius: ${parseFloat(
                        TABdimensionTop
                      )}${TABdimensionUnit};`
                    : " "
                }
    
            `;

      dimensionStylesMobile = `
                ${
                  MOBdimensionTop
                    ? `border-radius: ${parseFloat(
                        MOBdimensionTop
                      )}${MOBdimensionUnit};`
                    : " "
                }
    
            `;
    } else {
      dimensionStylesDesktop = `
            ${
              dimensionTop
                ? `${styleFor}: ${parseFloat(dimensionTop)}${dimensionUnit};`
                : " "
            }
        
            `;

      dimensionStylesTab = `
                ${
                  TABdimensionTop
                    ? `${styleFor}: ${parseFloat(
                        TABdimensionTop
                      )}${TABdimensionUnit};`
                    : " "
                }
    
            `;

      dimensionStylesMobile = `
                ${
                  MOBdimensionTop
                    ? `${styleFor}: ${parseFloat(
                        MOBdimensionTop
                      )}${MOBdimensionUnit};`
                    : " "
                }
    
            `;
    }
  } else if (isLinked === false && disableLeftRight === false) {
    if (styleFor === "border") {
      dimensionStylesDesktop = `
            ${
              dimensionTop
                ? `border-top-width: ${parseFloat(
                    dimensionTop
                  )}${dimensionUnit};`
                : " "
            }
            ${
              dimensionRight
                ? `border-right-width: ${parseFloat(
                    dimensionRight
                  )}${dimensionUnit};`
                : " "
            }
            ${
              dimensionLeft
                ? `border-left-width: ${parseFloat(
                    dimensionLeft
                  )}${dimensionUnit};`
                : " "
            }
            ${
              dimensionBottom
                ? `border-bottom-width: ${parseFloat(
                    dimensionBottom
                  )}${dimensionUnit};`
                : " "
            }
        
            `;

      dimensionStylesTab = `
                ${
                  TABdimensionTop
                    ? `border-top-width: ${parseFloat(
                        TABdimensionTop
                      )}${TABdimensionUnit};`
                    : " "
                }
                ${
                  TABdimensionRight
                    ? `border-right-width: ${parseFloat(
                        TABdimensionRight
                      )}${TABdimensionUnit};`
                    : " "
                }
                ${
                  TABdimensionLeft
                    ? `border-left-width: ${parseFloat(
                        TABdimensionLeft
                      )}${TABdimensionUnit};`
                    : " "
                }
                ${
                  TABdimensionBottom
                    ? `border-bottom-width: ${parseFloat(
                        TABdimensionBottom
                      )}${TABdimensionUnit};`
                    : " "
                }
    
            `;

      dimensionStylesMobile = `
                ${
                  MOBdimensionTop
                    ? `border-top-width: ${parseFloat(
                        MOBdimensionTop
                      )}${MOBdimensionUnit};`
                    : " "
                }
                ${
                  MOBdimensionRight
                    ? `border-right-width: ${parseFloat(
                        MOBdimensionRight
                      )}${MOBdimensionUnit};`
                    : " "
                }
                ${
                  MOBdimensionLeft
                    ? `border-left-width: ${parseFloat(
                        MOBdimensionLeft
                      )}${MOBdimensionUnit};`
                    : " "
                }
                ${
                  MOBdimensionBottom
                    ? `border-bottom-width: ${parseFloat(
                        MOBdimensionBottom
                      )}${MOBdimensionUnit};`
                    : " "
                }
    
            `;
    } else if (styleFor === "border-radius") {
      dimensionStylesDesktop = `
                ${
                  dimensionTop
                    ? `border-top-left-radius: ${parseFloat(
                        dimensionTop
                      )}${dimensionUnit};`
                    : " "
                }
                ${
                  dimensionRight
                    ? `border-top-right-radius: ${parseFloat(
                        dimensionRight
                      )}${dimensionUnit};`
                    : " "
                }
                ${
                  dimensionLeft
                    ? `border-bottom-left-radius: ${parseFloat(
                        dimensionLeft
                      )}${dimensionUnit};`
                    : " "
                }
                ${
                  dimensionBottom
                    ? `border-bottom-right-radius: ${parseFloat(
                        dimensionBottom
                      )}${dimensionUnit};`
                    : " "
                }
        
            `;

      dimensionStylesTab = `
                ${
                  TABdimensionTop
                    ? `border-top-left-radius: ${parseFloat(
                        TABdimensionTop
                      )}${TABdimensionUnit};`
                    : " "
                }
                ${
                  TABdimensionRight
                    ? `border-top-right-radius: ${parseFloat(
                        TABdimensionRight
                      )}${TABdimensionUnit};`
                    : " "
                }
                ${
                  TABdimensionLeft
                    ? `border-bottom-left-radius: ${parseFloat(
                        TABdimensionLeft
                      )}${TABdimensionUnit};`
                    : " "
                }
                ${
                  TABdimensionBottom
                    ? `border-bottom-right-radius: ${parseFloat(
                        TABdimensionBottom
                      )}${TABdimensionUnit};`
                    : " "
                }
    
            `;

      dimensionStylesMobile = `
                ${
                  MOBdimensionTop
                    ? `border-top-left-radius: ${parseFloat(
                        MOBdimensionTop
                      )}${MOBdimensionUnit};`
                    : " "
                }
                ${
                  MOBdimensionRight
                    ? `border-top-right-radius: ${parseFloat(
                        MOBdimensionRight
                      )}${MOBdimensionUnit};`
                    : " "
                }
                ${
                  MOBdimensionLeft
                    ? `border-bottom-left-radius: ${parseFloat(
                        MOBdimensionLeft
                      )}${MOBdimensionUnit};`
                    : " "
                }
                ${
                  MOBdimensionBottom
                    ? `border-bottom-right-radius: ${parseFloat(
                        MOBdimensionBottom
                      )}${MOBdimensionUnit};`
                    : " "
                }
    
            `;
    } else {
      dimensionStylesDesktop = `
            ${
              dimensionTop
                ? `${styleFor}-top: ${parseFloat(
                    dimensionTop
                  )}${dimensionUnit};`
                : " "
            }
            ${
              dimensionRight
                ? `${styleFor}-right: ${parseFloat(
                    dimensionRight
                  )}${dimensionUnit};`
                : " "
            }
            ${
              dimensionLeft
                ? `${styleFor}-left: ${parseFloat(
                    dimensionLeft
                  )}${dimensionUnit};`
                : " "
            }
            ${
              dimensionBottom
                ? `${styleFor}-bottom: ${parseFloat(
                    dimensionBottom
                  )}${dimensionUnit};`
                : " "
            }
        
            `;

      dimensionStylesTab = `
                ${
                  TABdimensionTop
                    ? `${styleFor}-top: ${parseFloat(
                        TABdimensionTop
                      )}${TABdimensionUnit};`
                    : " "
                }
                ${
                  TABdimensionRight
                    ? `${styleFor}-right: ${parseFloat(
                        TABdimensionRight
                      )}${TABdimensionUnit};`
                    : " "
                }
                ${
                  TABdimensionLeft
                    ? `${styleFor}-left: ${parseFloat(
                        TABdimensionLeft
                      )}${TABdimensionUnit};`
                    : " "
                }
                ${
                  TABdimensionBottom
                    ? `${styleFor}-bottom: ${parseFloat(
                        TABdimensionBottom
                      )}${TABdimensionUnit};`
                    : " "
                }
    
            `;

      dimensionStylesMobile = `
                ${
                  MOBdimensionTop
                    ? `${styleFor}-top: ${parseFloat(
                        MOBdimensionTop
                      )}${MOBdimensionUnit};`
                    : " "
                }
                ${
                  MOBdimensionRight
                    ? `${styleFor}-right: ${parseFloat(
                        MOBdimensionRight
                      )}${MOBdimensionUnit};`
                    : " "
                }
                ${
                  MOBdimensionLeft
                    ? `${styleFor}-left: ${parseFloat(
                        MOBdimensionLeft
                      )}${MOBdimensionUnit};`
                    : " "
                }
                ${
                  MOBdimensionBottom
                    ? `${styleFor}-bottom: ${parseFloat(
                        MOBdimensionBottom
                      )}${MOBdimensionUnit};`
                    : " "
                }
    
            `;
    }
  } else if (isLinked === true && disableLeftRight === true) {
    if (styleFor === "border") {
      dimensionStylesDesktop = `
            ${
              dimensionTop
                ? `border-top-width: ${parseFloat(
                    dimensionTop
                  )}${dimensionUnit}; `
                : " "
            }
            ${
              dimensionBottom
                ? `border-bottom-width: ${parseFloat(
                    dimensionBottom
                  )}${dimensionUnit}; `
                : " "
            }
        
            `;

      dimensionStylesTab = `
                ${
                  TABdimensionTop
                    ? `border-top-width: ${parseFloat(
                        TABdimensionTop
                      )}${TABdimensionUnit};`
                    : " "
                }
                ${
                  TABdimensionBottom
                    ? `border-bottom-width: ${parseFloat(
                        TABdimensionBottom
                      )}${TABdimensionUnit};`
                    : " "
                }
    
            `;

      dimensionStylesMobile = `
                ${
                  MOBdimensionTop
                    ? `border-top-width: ${parseFloat(
                        MOBdimensionTop
                      )}${MOBdimensionUnit};`
                    : " "
                }
                ${
                  MOBdimensionBottom
                    ? `border-bottom-width: ${parseFloat(
                        MOBdimensionBottom
                      )}${MOBdimensionUnit};`
                    : " "
                }
    
            `;
    } else if (styleFor === "border-radius") {
      dimensionStylesDesktop = `
                ${
                  dimensionTop
                    ? `border-top-left-radius: ${parseFloat(
                        dimensionTop
                      )}${dimensionUnit};`
                    : " "
                }
                
                ${
                  dimensionBottom
                    ? `border-bottom-right-radius: ${parseFloat(
                        dimensionBottom
                      )}${dimensionUnit};`
                    : " "
                }
        
            `;

      dimensionStylesTab = `
                ${
                  TABdimensionTop
                    ? `border-top-left-radius: ${parseFloat(
                        TABdimensionTop
                      )}${TABdimensionUnit};`
                    : " "
                }
                
                ${
                  TABdimensionBottom
                    ? `border-bottom-right-radius: ${parseFloat(
                        TABdimensionBottom
                      )}${TABdimensionUnit};`
                    : " "
                }
    
            `;

      dimensionStylesMobile = `
                ${
                  MOBdimensionTop
                    ? `border-top-left-radius: ${parseFloat(
                        MOBdimensionTop
                      )}${MOBdimensionUnit};`
                    : " "
                }
                
                ${
                  MOBdimensionBottom
                    ? `border-bottom-right-radius: ${parseFloat(
                        MOBdimensionBottom
                      )}${MOBdimensionUnit};`
                    : " "
                }
    
            `;
    } else {
      dimensionStylesDesktop = `
            ${
              dimensionTop
                ? `${styleFor}-top: ${parseFloat(
                    dimensionTop
                  )}${dimensionUnit};`
                : " "
            }
        
            ${
              dimensionBottom
                ? `${styleFor}-bottom: ${parseFloat(
                    dimensionBottom
                  )}${dimensionUnit};`
                : " "
            }
        
            `;

      dimensionStylesTab = `
                ${
                  TABdimensionTop
                    ? `${styleFor}-top: ${parseFloat(
                        TABdimensionTop
                      )}${TABdimensionUnit};`
                    : " "
                }
                
                ${
                  TABdimensionBottom
                    ? `${styleFor}-bottom: ${parseFloat(
                        TABdimensionBottom
                      )}${TABdimensionUnit};`
                    : " "
                }
    
            `;

      dimensionStylesMobile = `
                ${
                  MOBdimensionTop
                    ? `${styleFor}-top: ${parseFloat(
                        MOBdimensionTop
                      )}${MOBdimensionUnit};`
                    : " "
                }
                
                ${
                  MOBdimensionBottom
                    ? `${styleFor}-bottom: ${parseFloat(
                        MOBdimensionBottom
                      )}${MOBdimensionUnit};`
                    : " "
                }
    
            `;
    }
  } else if (isLinked === false && disableLeftRight === true) {
    if (styleFor === "border") {
      dimensionStylesDesktop = `
            ${
              dimensionTop
                ? `border-top-width: ${parseFloat(
                    dimensionTop
                  )}${dimensionUnit};`
                : " "
            }
            ${
              dimensionBottom
                ? `border-bottom-width: ${parseFloat(
                    dimensionBottom
                  )}${dimensionUnit};`
                : " "
            }
        
            `;

      dimensionStylesTab = `
                ${
                  TABdimensionTop
                    ? `border-top-width: ${parseFloat(
                        TABdimensionTop
                      )}${TABdimensionUnit};`
                    : " "
                }
                ${
                  TABdimensionBottom
                    ? `border-bottom-width: ${parseFloat(
                        TABdimensionBottom
                      )}${TABdimensionUnit};`
                    : " "
                }
    
            `;

      dimensionStylesMobile = `
                ${
                  MOBdimensionTop
                    ? `border-top-width: ${parseFloat(
                        MOBdimensionTop
                      )}${MOBdimensionUnit};`
                    : " "
                }
                ${
                  MOBdimensionBottom
                    ? `border-bottom-width: ${parseFloat(
                        MOBdimensionBottom
                      )}${MOBdimensionUnit};`
                    : " "
                }
    
            `;
    } else if (styleFor === "border-radius") {
      dimensionStylesDesktop = `
                ${
                  dimensionTop
                    ? `border-top-left-radius: ${parseFloat(
                        dimensionTop
                      )}${dimensionUnit};`
                    : " "
                }
                ${
                  dimensionBottom
                    ? `border-bottom-right-radius: ${parseFloat(
                        dimensionBottom
                      )}${dimensionUnit};`
                    : " "
                }
        
            `;

      dimensionStylesTab = `
                ${
                  TABdimensionTop
                    ? `border-top-left-radius: ${parseFloat(
                        TABdimensionTop
                      )}${TABdimensionUnit};`
                    : " "
                }
                ${
                  TABdimensionBottom
                    ? `border-bottom-right-radius: ${parseFloat(
                        TABdimensionBottom
                      )}${TABdimensionUnit};`
                    : " "
                }
    
            `;

      dimensionStylesMobile = `
                ${
                  MOBdimensionTop
                    ? `border-top-left-radius: ${parseFloat(
                        MOBdimensionTop
                      )}${MOBdimensionUnit};`
                    : " "
                }
                ${
                  MOBdimensionBottom
                    ? `border-bottom-right-radius: ${parseFloat(
                        MOBdimensionBottom
                      )}${MOBdimensionUnit};`
                    : " "
                }
    
            `;
    } else {
      dimensionStylesDesktop = `
            ${
              dimensionTop
                ? `${styleFor}-top: ${parseFloat(
                    dimensionTop
                  )}${dimensionUnit};`
                : " "
            }
            ${
              dimensionBottom
                ? `${styleFor}-bottom: ${parseFloat(
                    dimensionBottom
                  )}${dimensionUnit};`
                : " "
            }
        
            `;

      dimensionStylesTab = `
                ${
                  TABdimensionTop
                    ? `${styleFor}-top: ${parseFloat(
                        TABdimensionTop
                      )}${TABdimensionUnit};`
                    : " "
                }
                ${
                  TABdimensionBottom
                    ? `${styleFor}-bottom: ${parseFloat(
                        TABdimensionBottom
                      )}${TABdimensionUnit};`
                    : " "
                }
    
            `;

      dimensionStylesMobile = `
                ${
                  MOBdimensionTop
                    ? `${styleFor}-top: ${parseFloat(
                        MOBdimensionTop
                      )}${MOBdimensionUnit};`
                    : " "
                }
                ${
                  MOBdimensionBottom
                    ? `${styleFor}-bottom: ${parseFloat(
                        MOBdimensionBottom
                      )}${MOBdimensionUnit};`
                    : " "
                }
    
            `;
    }
  }

  return {
    dimensionStylesDesktop,
    dimensionStylesTab,
    dimensionStylesMobile,
  };
};
