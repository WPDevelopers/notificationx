//
// These following 3 functions to handle the resBtns click
// function 1: to handle desktop button click
export const handleDesktopBtnClick = ({
  setPreviewDeviceType,
  setAttributes,
}) => {
  setAttributes({
    resOption: "Desktop",
  });
  setPreviewDeviceType("Desktop");
};

// function 2: to handle Tab button click
export const handleTabBtnClick = ({ setPreviewDeviceType, setAttributes }) => {
  setAttributes({
    resOption: "Tablet",
  });
  setPreviewDeviceType("Tablet");
};

// function 3: to handle Mobile button click
export const handleMobileBtnClick = ({
  setPreviewDeviceType,
  setAttributes,
}) => {
  setAttributes({
    resOption: "Mobile",
  });
  setPreviewDeviceType("Mobile");
};
