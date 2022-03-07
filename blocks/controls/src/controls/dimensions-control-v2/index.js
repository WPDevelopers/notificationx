import DimensionsControl from "./dimensions";
import UnitControl from "../unit-control";

export default function ResponsiveDimensionsControl({
  resRequiredProps,
  controlName,
  baseLabel,
  forBorderRadius,
  disableLeftRight = false,
}) {
  const { attributes, setAttributes, resOption } = resRequiredProps;

  const {
    [`${controlName}isLinked`]: dimensionIsLinked,

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

  const UNITS = [
    { label: "px", value: "px" },
    { label: "em", value: "em" },
    { label: "%", value: "%" },
  ];

  const neededProps = {
    resOption,
    baseLabel,
    forBorderRadius,
    setAttributes,
    dimensionIsLinked,
    controlName,
  };

  return (
    <>
      {resOption == "Desktop" && (
        <>
          <UnitControl
            selectedUnit={dimensionUnit}
            unitTypes={UNITS}
            onClick={(dimensionUnit) =>
              setAttributes({ [`${controlName}Unit`]: dimensionUnit })
            }
          />

          <DimensionsControl
            top={dimensionTop}
            right={dimensionRight}
            bottom={dimensionBottom}
            left={dimensionLeft}
            neededProps={neededProps}
            disableLeftRight={disableLeftRight}
            onChange={({ top, right, bottom, left }) =>
              setAttributes({
                [`${controlName}Top`]: top,
                [`${controlName}Right`]: right,
                [`${controlName}Bottom`]: bottom,
                [`${controlName}Left`]: left,
              })
            }
          />
        </>
      )}
      {resOption == "Tablet" && (
        <>
          <UnitControl
            selectedUnit={TABdimensionUnit}
            unitTypes={UNITS}
            onClick={(TABdimensionUnit) =>
              setAttributes({ [`TAB${controlName}Unit`]: TABdimensionUnit })
            }
          />

          <DimensionsControl
            top={TABdimensionTop}
            right={TABdimensionRight}
            bottom={TABdimensionBottom}
            left={TABdimensionLeft}
            neededProps={neededProps}
            disableLeftRight={disableLeftRight}
            onChange={({ top, right, bottom, left }) =>
              setAttributes({
                [`TAB${controlName}Top`]: top,
                [`TAB${controlName}Right`]: right,
                [`TAB${controlName}Bottom`]: bottom,
                [`TAB${controlName}Left`]: left,
              })
            }
          />
        </>
      )}
      {resOption == "Mobile" && (
        <>
          <UnitControl
            selectedUnit={MOBdimensionUnit}
            unitTypes={UNITS}
            onClick={(MOBdimensionUnit) =>
              setAttributes({ [`MOB${controlName}Unit`]: MOBdimensionUnit })
            }
          />

          <DimensionsControl
            top={MOBdimensionTop}
            right={MOBdimensionRight}
            bottom={MOBdimensionBottom}
            left={MOBdimensionLeft}
            neededProps={neededProps}
            disableLeftRight={disableLeftRight}
            onChange={({ top, right, bottom, left }) =>
              setAttributes({
                [`MOB${controlName}Top`]: top,
                [`MOB${controlName}Right`]: right,
                [`MOB${controlName}Bottom`]: bottom,
                [`MOB${controlName}Left`]: left,
              })
            }
          />
        </>
      )}
    </>
  );
}
