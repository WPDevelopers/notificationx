// helper Functions: function 'textInsideForEdit' is for setting the innertext depending on whether separator should be shown and which separator should be shown in the number-counter block
export const textInsideForEdit = (value, isShowSeparator, separator) =>
  isShowSeparator
    ? value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, separator)
    : value.toString();

// generateRandomNumber function is for generating a random number
export const generateRandomNumber = () =>
  Math.floor(Math.random() * 1000000000);

// hardMinifyCssStrings is for minifying the css which is in the style tag as a string  for view.js
export const hardMinifyCssStrings = (cssString) => {
  return (
    cssString
      .replace(/\s+/g, " ")
      .replace(/(?<=\:).+(?=\;)/g, function (match) {
        return match.trim().replace(/\s+/g, "__s_p_a_c_e__");
      })
      // .replace(/\s+(?!(?:[\w\d\.\-\#]+\{))/g, "")
      .replace(/\s+(?![\w\d\.\-\#]+\{)/g, "")
      .replace(/\s+/g, " ")
      .replace(/__s_p_a_c_e__/g, " ")
  );
};

// softMinifyCssStrings is for minifying the css which is in the style tag as a string  for view.js
export const softMinifyCssStrings = (cssString = " ") =>
  cssString.replace(/\s+/g, " ").replace(/\.eb\-[\w\-\s\.\,\:\>\(\)\d\+\[\]\#\>]+\{[\s]+\}/g, "");

// check if css string is empty or not.
export const isCssExists = (cssString) =>
  /.+(?=\:(?!hover)(?!focus))/.test(cssString);
