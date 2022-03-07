/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { Component } from "@wordpress/element";
import {
	SelectControl,
	RangeControl,
	Dropdown,
	ButtonGroup,
	Button,
	BaseControl,
} from "@wordpress/components";
import { DOWN } from "@wordpress/keycodes";

/**
 * Internal dependencies
 */
import FontPicker from "./FontPicker";
import UnitControl from "../unit-control";
import Icon from "./Icon";
import { WEIGHTS, TRANSFORMS, FONTS } from "./constants";
// import "./style.scss";

class TypographyControl extends Component {
	onFontChange = (value, onClose) => {
		const { fontFamily, fontWeight } = this.props.attributes;

		this.props.setAttributes({ fontFamily: value });

		if (
			typeof FONTS[value] !== "undefined" &&
			typeof FONTS[value].weight !== "undefined"
		) {
			if (
				fontWeight &&
				Object.values(FONTS[fontFamily].weight).indexOf(fontWeight) < 0
			) {
				this.props.setAttributes({ fontWeight: "" });
			}
		}

		onClose();
	};

	render() {
		const { attributes, setAttributes } = this.props;

		const {
			fontFamily,
			fontSize,
			fontSizeUnit,
			fontWeight,
			textTransform,
			lineHeight,
			letterSpacing,
		} = attributes;

		const SIZE_STEP = fontSizeUnit === "em" ? 0.1 : 1;
		const SIZE_MAX = fontSizeUnit === "em" ? 10 : 100;

		return (
			<Dropdown
				className="components-dropdown-menu components-eb-typography-dropdown"
				position="top right"
				renderToggle={({ isOpen, onToggle }) => {
					const onArrowDown = (event) => {
						if (!isOpen && event.keyCode === DOWN) {
							event.preventDefault();
							event.stopPropagation();
							onToggle();
						}
					};

					return (
						<Button
							className="components-dropdown-menu__toggle"
							icon={Icon}
							onClick={onToggle}
							onKeyDown={onArrowDown}
							aria-haspopup="true"
							label="Open"
							tooltip="Here it is ">
							<span className="components-dropdown-menu__indicator" />
						</Button>
					);
				}}
				renderContent={({ onClose }) => (
					<div className="eb-typography-wrapper">
						<FontPicker
							label={__("Font", "essential-blocks")}
							value={fontFamily || null}
							onChange={(nextFontFamily) =>
								this.onFontChange(nextFontFamily, onClose)
							}
							className="components-base-control--with-flex"
						/>

						<SelectControl
							label={__("Weight", "essential-blocks")}
							value={fontWeight || null}
							options={WEIGHTS}
							onChange={(fontWeight) =>
								setAttributes({ fontWeight })
							}
						/>

						<BaseControl
							label={__("Transform", "essential-blocks")}>
							<ButtonGroup>
								{TRANSFORMS.map((item) => (
									<Button
										isPrimary={textTransform === item.value}
										isSecondary={
											textTransform !== item.value
										}
										onClick={() =>
											setAttributes({
												textTransform: item.value,
											})
										}>
										{item.label}
									</Button>
								))}
							</ButtonGroup>
						</BaseControl>

						<UnitControl
							selectedUnit={fontSizeUnit}
							unitTypes={[
								{ label: "px", value: "px" },
								{ label: "em", value: "em" },
								{ label: "%", value: "%" },
							]}
							onClick={(fontSizeUnit) =>
								setAttributes({ fontSizeUnit })
							}
						/>

						<RangeControl
							label={__("Size", "essential-blocks")}
							value={fontSize || null}
							onChange={(fontSize) => setAttributes({ fontSize })}
							min={0}
							max={SIZE_MAX}
							step={SIZE_STEP}
						/>

						<RangeControl
							label={__("Line Height", "essential-blocks")}
							value={lineHeight || null}
							onChange={(lineHeight) =>
								setAttributes({ lineHeight })
							}
							min={-1}
							max={4}
							step={0.1}
						/>

						<RangeControl
							label={__("Letter Spacing", "essential-blocks")}
							value={letterSpacing || null}
							onChange={(letterSpacing) =>
								setAttributes({ letterSpacing })
							}
							min={0}
							max={4}
							step={0.1}
						/>
					</div>
				)}
			/>
		);
	}
}

export default TypographyControl;
