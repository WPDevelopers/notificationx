// Parses the gradient color string
export const parseGradientColor = (
  gradientColor = `linear-gradient(45deg,#7967ff,#c277f2)`
) => {
  let angle = 45,
    colorOnePosition = 0,
    colorTwoPosition = 100,
    radialShape = "ellipse",
    radialX = 50,
    radialY = 50;
  const [colorOne, colorTwo] = gradientColor.match(
    /\#[a-f\d]{3,8}|rgba?\([\d\,\.]{3,16}\)/gi
  ) || ["rgba(0,0,0,0)", "rgba(0,0,0,0)"];
  const [gradientType] = gradientColor.match(/\w{6}(?=-)/i) || ["linear"];
  if (gradientType == "linear") {
    angle = gradientColor.match(/\d{1,3}(?=deg)/i)[0];
    [colorOnePosition, colorTwoPosition] = gradientColor.match(
      /\d{1,3}(?=\%)/gi
    ) || ["0", "100"];
  } else {
    radialShape = (gradientColor.match(/\w+(?= at)/i) || ["circle"])[0];

    const pcents = gradientColor.match(/\d{1,3}(?=\%)/gi) || [
      "50",
      "50",
      "18",
      "82",
    ];

    radialX = pcents[0];
    radialY = pcents[1];
    colorOnePosition = pcents[2];
    colorTwoPosition = pcents[3];
  }
  return {
    gradientType,
    angle: parseInt(angle),
    colorOne,
    colorTwo,
    colorOnePosition: parseInt(colorOnePosition),
    colorTwoPosition: parseInt(colorTwoPosition),
    radialShape,
    radialX: parseInt(radialX),
    radialY: parseInt(radialY),
  };
};
