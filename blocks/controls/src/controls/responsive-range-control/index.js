import { __ } from "@wordpress/i18n";
import { RangeControl } from "@wordpress/components";

import WithResBtns from "./responsive-btn";
import UnitControl from "../unit-control";

const ResponsiveRangeController = ({
	baseLabel,
	controlName,
	resRequiredProps,
	units,
	min,
	max,
	step,
	noUnits,
}) => {
	const { attributes, setAttributes, resOption } = resRequiredProps;

	let desktopSizeUnit;
	let TABsizeUnit;
	let MOBsizeUnit;
	let defaultUnits;

	const {
		[`${controlName}Range`]: desktopRange,
		[`TAB${controlName}Range`]: TABrange,
		[`MOB${controlName}Range`]: MOBrange,
	} = attributes;

	if (!noUnits) {
		desktopSizeUnit = attributes[`${controlName}Unit`];
		TABsizeUnit = attributes[`TAB${controlName}Unit`];
		MOBsizeUnit = attributes[`MOB${controlName}Unit`];
		defaultUnits = [
			{ label: "px", value: "px" },
			{ label: "em", value: "em" },
			{ label: "%", value: "%" },
		];
	}

	return (
		<div className="responsiveRangeControllerWrapper">
			{noUnits ? (
				<>
					{resOption == "Desktop" && (
						<WithResBtns
							noUnits={noUnits}
							label={baseLabel}
							resRequiredProps={resRequiredProps}
							controlName={controlName}>
							<RangeControl
								value={desktopRange}
								onChange={(desktopRange) =>
									setAttributes({
										[`${controlName}Range`]: desktopRange,
									})
								}
								step={step || 1}
								min={min || 0}
								max={max || 100}
							/>
						</WithResBtns>
					)}
					{resOption == "Tablet" && (
						<WithResBtns
							noUnits={noUnits}
							label={baseLabel}
							resRequiredProps={resRequiredProps}
							controlName={controlName}>
							<RangeControl
								value={TABrange}
								onChange={(TABrange) =>
									setAttributes({
										[`TAB${controlName}Range`]: TABrange,
									})
								}
								step={step || 1}
								min={min || 0}
								max={max || 100}
							/>
						</WithResBtns>
					)}
					{resOption == "Mobile" && (
						<WithResBtns
							noUnits={noUnits}
							label={baseLabel}
							resRequiredProps={resRequiredProps}
							controlName={controlName}>
							<RangeControl
								value={MOBrange}
								onChange={(MOBrange) =>
									setAttributes({
										[`MOB${controlName}Range`]: MOBrange,
									})
								}
								step={step || 1}
								min={min || 0}
								max={max || 100}
							/>
						</WithResBtns>
					)}
				</>
			) : (
				<>
					{resOption == "Desktop" && (
						<>
							<UnitControl
								selectedUnit={desktopSizeUnit}
								unitTypes={units || defaultUnits}
								onClick={(desktopSizeUnit) =>
									setAttributes({
										[`${controlName}Unit`]: desktopSizeUnit,
									})
								}
							/>
							<WithResBtns
								label={baseLabel}
								resRequiredProps={resRequiredProps}
								controlName={controlName}>
								<RangeControl
									value={desktopRange}
									onChange={(desktopRange) =>
										setAttributes({
											[`${controlName}Range`]:
												desktopRange,
										})
									}
									step={desktopSizeUnit === "em" ? 0.1 : step}
									min={desktopSizeUnit === "px" ? min : 0}
									max={desktopSizeUnit === "px" ? max : 100}
								/>
							</WithResBtns>
						</>
					)}
					{resOption == "Tablet" && (
						<>
							<UnitControl
								selectedUnit={TABsizeUnit}
								unitTypes={units || defaultUnits}
								onClick={(TABsizeUnit) =>
									setAttributes({
										[`TAB${controlName}Unit`]: TABsizeUnit,
									})
								}
							/>
							<WithResBtns
								label={baseLabel}
								resRequiredProps={resRequiredProps}
								controlName={controlName}>
								<RangeControl
									value={TABrange}
									onChange={(TABrange) =>
										setAttributes({
											[`TAB${controlName}Range`]:
												TABrange,
										})
									}
									step={TABsizeUnit === "em" ? 0.1 : step}
									min={TABsizeUnit === "px" ? min : 0}
									max={TABsizeUnit === "px" ? max : 100}
								/>
							</WithResBtns>
						</>
					)}
					{resOption == "Mobile" && (
						<>
							<UnitControl
								selectedUnit={MOBsizeUnit}
								unitTypes={units || defaultUnits}
								onClick={(MOBsizeUnit) =>
									setAttributes({
										[`MOB${controlName}Unit`]: MOBsizeUnit,
									})
								}
							/>
							<WithResBtns
								label={baseLabel}
								resRequiredProps={resRequiredProps}
								controlName={controlName}>
								<RangeControl
									value={MOBrange}
									onChange={(MOBrange) =>
										setAttributes({
											[`MOB${controlName}Range`]:
												MOBrange,
										})
									}
									step={MOBsizeUnit === "em" ? 0.1 : step}
									min={MOBsizeUnit === "px" ? min : 0}
									max={MOBsizeUnit === "px" ? max : 100}
								/>
							</WithResBtns>
						</>
					)}
				</>
			)}
		</div>
	);
};

export default ResponsiveRangeController;
