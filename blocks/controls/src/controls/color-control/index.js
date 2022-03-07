// const { useState, useEffect } = wp.element;
// const { BaseControl, Dropdown, Tooltip, ColorPicker, Button } = wp.components;
import {
  useState, 
  useEffect
} from "@wordpress/element";
import {
  BaseControl,
  Dropdown,
  Tooltip,
  ColorPicker,
  Button
} from "@wordpress/components";

const colorBallStyles = {
  padding: 2,
  borderRadius: 0,
  background: "white",
  border: "1px solid #ebebeb",
};

const colorStyles = {
  height: 16,
  width: 16,
  borderRadius: "0%",
  boxShadow: "inset 0 0 0 1px rgba(0,0,0,.1)",
};

const ColorControl = ({ label, color, onChange, defaultColor }) => {
  const [bgColor, setBgColor] = useState(null);

  useEffect(() => {
    onChange(bgColor);
  }, [bgColor]);

  useEffect(() => {
    setBgColor(color || defaultColor);
  }, []);

  return (
    <BaseControl label={label || ""} className="eb-color-base">
      <Dropdown
        renderToggle={({ isOpen, onToggle }) => (
          <Tooltip text={bgColor || "default"}>
            <div className="eb-color-ball" style={bgColor && colorBallStyles}>
              <div
                style={{
                  ...colorStyles,
                  backgroundColor: bgColor,
                }}
                aria-expanded={isOpen}
                onClick={onToggle}
                aria-label={bgColor || "default"}
              ></div>
            </div>
          </Tooltip>
        )}
        renderContent={() => (
          <ColorPicker
            color={bgColor}
            onChangeComplete={({ rgb }) => {
              setBgColor(`rgba(${rgb.r},${rgb.g},${rgb.b},${rgb.a})`);
            }}
          />
        )}
      />
      {bgColor && (
        <Button
          isSmall
          className="eb-color-undo"
          icon="image-rotate"
          style={{
            transform: "scaleX(-1) rotate(90deg)",
          }}
          onClick={() => {
            setBgColor(defaultColor);
          }}
        ></Button>
      )}
    </BaseControl>
  );
};

export default ColorControl;
