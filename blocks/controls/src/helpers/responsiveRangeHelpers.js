// function to generate responsive range controller attributes for multiple range control based on the array of prefix
export const generateResponsiveRangeAttributes = (
  controlName,
  defaults = {}
) => {
  const { defaultRange, noUnits, defaultUnit = "px" } = defaults;
  const desktop = defaultRange
    ? {
        [`${controlName}Range`]: {
          type: "number",
          default: defaultRange,
        },
      }
    : {
        [`${controlName}Range`]: {
          type: "number",
        },
      };

  const units =
    noUnits === true
      ? {}
      : {
          [`${controlName}Unit`]: {
            type: "string",
            default: defaultUnit,
          },
          [`TAB${controlName}Unit`]: {
            type: "string",
            default: "px",
          },
          [`MOB${controlName}Unit`]: {
            type: "string",
            default: "px",
          },
        };

  return {
    ...desktop,
    [`TAB${controlName}Range`]: {
      type: "number",
    },

    [`MOB${controlName}Range`]: {
      type: "number",
    },
    ...units,
  };
};

// function to generate responsive range control styles for an element based on it's prefix
export const generateResponsiveRangeStyles = ({
  controlName,
  property,
  attributes,
  customUnit,
}) => {
  let desktopSizeUnit;
  let TABsizeUnit;
  let MOBsizeUnit;

  if (!customUnit) {
    desktopSizeUnit = attributes[`${controlName}Unit`];
    TABsizeUnit = attributes[`TAB${controlName}Unit`];
    MOBsizeUnit = attributes[`MOB${controlName}Unit`];
  } else {
    desktopSizeUnit = TABsizeUnit = MOBsizeUnit = customUnit;
  }

  const {
    [`${controlName}Range`]: desktopRange,
    [`TAB${controlName}Range`]: TABrange,
    [`MOB${controlName}Range`]: MOBrange,
  } = attributes;

  const rangeStylesDesktop =
    desktopRange || desktopRange === 0
      ? property +
        ":" +
        (desktopSizeUnit !== "px" && desktopRange > 100 ? 100 : desktopRange) +
        (customUnit || desktopSizeUnit) +
        ";"
      : "";
  const rangeStylesTab =
    TABrange || TABrange === 0
      ? property +
        ":" +
        (TABsizeUnit !== "px" && TABrange > 100 ? 100 : TABrange) +
        (customUnit || TABsizeUnit) +
        ";"
      : "";
  const rangeStylesMobile =
    MOBrange || MOBrange === 0
      ? property +
        ":" +
        (MOBsizeUnit !== "px" && MOBrange > 100 ? 100 : MOBrange) +
        (customUnit || MOBsizeUnit) +
        ";"
      : "";

  return {
    rangeStylesDesktop,
    rangeStylesTab,
    rangeStylesMobile,
  };
};
