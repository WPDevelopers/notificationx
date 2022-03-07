/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import {
	RangeControl,
	BaseControl,
	Dropdown,
	Tooltip,
	ColorPicker,
} from "@wordpress/components";
import { useEffect, useState } from "@wordpress/element";

/**
 * Internal dependencies
 */
import ToggleButton from "../toggle-button";
import { GRADIENT_TYPE, RADIAL_TYPES } from "./constants";
import { parseGradientColor } from "./helper";

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

const GradientColorControl = ({
	gradientColor = "linear-gradient(45deg,rgba(0,0,0,0.8),rgba(0,0,0,0.4))",
	onChange,
}) => {
	const [gradientType, setGradientType] = useState("linear");
	const [colorOne, setColorOne] = useState("transparent");
	const [colorOnePosition, setColorOnePosition] = useState(0);
	const [colorTwo, setColorTwo] = useState("transparent");
	const [colorTwoPosition, setColorTwoPosition] = useState(100);
	const [angle, setAngle] = useState(0);
	const [radialShape, setRadialShape] = useState("ellipse");
	const [radialX, setRadialX] = useState(50);
	const [radialY, setRadialY] = useState(50);

	useEffect(() => {
		let {
			gradientType,
			angle,
			colorOne,
			colorTwo,
			colorOnePosition,
			colorTwoPosition,
			radialShape,
			radialX,
			radialY,
		} = parseGradientColor(gradientColor);

		setGradientType(gradientType);
		setAngle(angle);
		setColorOne(colorOne);
		setColorTwo(colorTwo);
		setColorOnePosition(colorOnePosition);
		setColorTwoPosition(colorTwoPosition);
		setRadialShape(radialShape);
		setRadialX(radialX);
		setRadialY(radialY);
	}, []);

	useEffect(() => {
		onChange(
			gradientType === "linear"
				? getLinearGradient()
				: getRadialGradient()
		);
	}, [
		gradientType,
		colorOne,
		colorOnePosition,
		colorTwo,
		colorTwoPosition,
		angle,
		radialShape,
		radialX,
		radialY,
	]);

	const getColorString = () =>
		`${colorOne} ${colorOnePosition}% , ${colorTwo} ${colorTwoPosition}%`;

	const getRadialGradient = () =>
		`radial-gradient(${radialShape} at ${radialX}% ${radialY}%, ${getColorString()})`;

	const getLinearGradient = () =>
		`linear-gradient(${angle}deg, ${getColorString()})`;

	return (
		<div className="eb-gradient-control">
			<BaseControl
				label={__("Gradient Type", "essential-blocks")}
				className="eb-gradient-toggle-label">
				<ToggleButton
					defaultSelected={
						gradientType === "linear"
							? GRADIENT_TYPE[0]
							: GRADIENT_TYPE[1]
					}
					options={GRADIENT_TYPE}
					onChange={(gradientType) => setGradientType(gradientType)}
				/>
			</BaseControl>

			{gradientType === "radial" && (
				<BaseControl
					label={__("Radial Type", "essential-blocks")}
					className="eb-gradient-toggle-label">
					<ToggleButton
						defaultSelected={
							radialShape === "ellipse"
								? RADIAL_TYPES[0]
								: RADIAL_TYPES[1]
						}
						options={RADIAL_TYPES}
						onChange={(radialShape) => setRadialShape(radialShape)}
					/>
				</BaseControl>
			)}

			<BaseControl label={"First Color"} className="eb-color-base">
				<Dropdown
					renderToggle={({ isOpen, onToggle }) => (
						<Tooltip text={colorOne || "default"}>
							<div
								className="eb-color-ball"
								style={colorOne && colorBallStyles}>
								<div
									style={{
										...colorStyles,
										backgroundColor: colorOne,
									}}
									aria-expanded={isOpen}
									onClick={onToggle}
									aria-label={colorOne || "default"}></div>
							</div>
						</Tooltip>
					)}
					renderContent={() => (
						<ColorPicker
							color={colorOne}
							onChangeComplete={({ rgb }) => {
								setColorOne(
									`rgba(${rgb.r},${rgb.g},${rgb.b},${rgb.a})`
								);
							}}
						/>
					)}
				/>
			</BaseControl>

			<BaseControl label={"Second Color"} className="eb-color-base">
				<Dropdown
					renderToggle={({ isOpen, onToggle }) => (
						<Tooltip text={colorTwo || "default"}>
							<div
								className="eb-color-ball"
								style={colorTwo && colorBallStyles}>
								<div
									style={{
										...colorStyles,
										backgroundColor: colorTwo,
									}}
									aria-expanded={isOpen}
									onClick={onToggle}
									aria-label={colorTwo || "default"}></div>
							</div>
						</Tooltip>
					)}
					renderContent={() => (
						<ColorPicker
							color={colorTwo}
							onChangeComplete={({ rgb }) => {
								setColorTwo(
									`rgba(${rgb.r},${rgb.g},${rgb.b},${rgb.a})`
								);
							}}
						/>
					)}
				/>
			</BaseControl>

			<RangeControl
				label={__("First Color Position", "essential-blocks")}
				value={colorOnePosition}
				onChange={(colorOnePosition) =>
					setColorOnePosition(colorOnePosition)
				}
				min={0}
				max={100}
			/>

			<RangeControl
				label={__("Second Color Position", "essential-blocks")}
				value={colorTwoPosition}
				onChange={(colorTwoPosition) =>
					setColorTwoPosition(colorTwoPosition)
				}
				min={0}
				max={100}
			/>

			{gradientType === "linear" && (
				<RangeControl
					label={__("Angle", "essential-blocks")}
					value={angle}
					onChange={(angle) => setAngle(angle)}
					min={0}
					max={360}
				/>
			)}

			{gradientType === "radial" && (
				<>
					<RangeControl
						label={__("Center X Position", "essential-blocks")}
						value={radialX}
						onChange={(radialX) => setRadialX(radialX)}
						min={0}
						max={100}
					/>

					<RangeControl
						label={__("Center Y Position", "essential-blocks")}
						value={radialY}
						onChange={(radialY) => setRadialY(radialY)}
						min={0}
						max={100}
					/>
				</>
			)}
		</div>
	);
};

// GradientColorControl.propTypes = {
//   gradientColor: PropTypes.string.isRequired,
//   onChange: PropTypes.func.isRequired,
// };

export default GradientColorControl;
