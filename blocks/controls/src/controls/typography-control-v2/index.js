import { __ } from "@wordpress/i18n";
import {
	BaseControl,
	Button,
	Dropdown,
	RangeControl,
	SelectControl,
} from "@wordpress/components";
import { useEffect, useState } from "@wordpress/element";

import UnitControl from "../unit-control";
import FontPicker from "./fontPicker";
import { TypographyIcon } from "../../extras/icons";
import WithResButtons from "../withResButtons";
import ResetControl from "../reset-control";
import {
	sizeUnitTypes,
	optionsFontWeights,
	optionsFontStyles,
	optionsTextTransforms,
	optionsTextDecorations,
	optionsLhLsp,
} from "./constants";
import { googleFonts } from "./fontPicker/googleFonts";

function TypographyDropdown({
	baseLabel,
	typographyPrefixConstant,
	resRequiredProps,
	defaultFontSize,
}) {
	const { attributes, setAttributes, resOption, objAttributes } =
		resRequiredProps;

	const {
		[`${typographyPrefixConstant}FontFamily`]: fontFamily,
		[`${typographyPrefixConstant}FontWeight`]: fontWeight,
		[`${typographyPrefixConstant}FontStyle`]: fontStyle,
		[`${typographyPrefixConstant}TextTransform`]: textTransform,
		[`${typographyPrefixConstant}TextDecoration`]: textDecoration,
		[`${typographyPrefixConstant}FontSize`]: fontSize = defaultFontSize ||
			undefined,
		[`${typographyPrefixConstant}SizeUnit`]: sizeUnit,
		[`${typographyPrefixConstant}LetterSpacing`]: letterSpacing,
		[`${typographyPrefixConstant}LetterSpacingUnit`]: letterSpacingUnit,
		[`${typographyPrefixConstant}LineHeight`]: lineHeight,
		[`${typographyPrefixConstant}LineHeightUnit`]: lineHeightUnit,

		[`TAB${typographyPrefixConstant}SizeUnit`]: TABsizeUnit,
		[`TAB${typographyPrefixConstant}LetterSpacingUnit`]:
			TABletterSpacingUnit,
		[`TAB${typographyPrefixConstant}LineHeightUnit`]: TABlineHeightUnit,
		[`TAB${typographyPrefixConstant}FontSize`]: TABfontSize,
		[`TAB${typographyPrefixConstant}LetterSpacing`]: TABletterSpacing,
		[`TAB${typographyPrefixConstant}LineHeight`]: TABlineHeight,

		[`MOB${typographyPrefixConstant}SizeUnit`]: MOBsizeUnit,
		[`MOB${typographyPrefixConstant}LetterSpacingUnit`]:
			MOBletterSpacingUnit,
		[`MOB${typographyPrefixConstant}LineHeightUnit`]: MOBlineHeightUnit,
		[`MOB${typographyPrefixConstant}FontSize`]: MOBfontSize,
		[`MOB${typographyPrefixConstant}LetterSpacing`]: MOBletterSpacing,
		[`MOB${typographyPrefixConstant}LineHeight`]: MOBlineHeight,
	} = attributes;

	//Update Font Weight and Font Varient
	const [ebFontWeight, setEbFontWeight] = useState(optionsFontWeights);
	useEffect(() => {
		const fontFamilyKey = (fontFamily || "").replace(/\s+/g, "-");
		let googleFontWeight = googleFonts[fontFamilyKey]
			? googleFonts[fontFamilyKey].variants
			: [];
		let fontWeightVal = googleFontWeight.map((item) => ({
			label: item,
			value: item,
		}));
		const fontWeightwithDefault = [
			{ label: "Default", value: "" },
			...fontWeightVal,
		];
		setEbFontWeight(fontWeightwithDefault);
	}, [fontFamily]);

	return (
		<BaseControl label={__(baseLabel)} className="eb-typography-base">
			<Dropdown
				className="eb-typography-dropdown"
				contentClassName="my-popover-content-classname"
				position="bottom right"
				renderToggle={({ isOpen, onToggle }) => (
					<Button isSmall onClick={onToggle} aria-expanded={isOpen}>
						<TypographyIcon />
					</Button>
				)}
				renderContent={() => (
					<div
						className="eb-panel-control eb-typography-component-panel"
						style={{ padding: "0.2rem" }}>
						<FontPicker
							className="eb-fontpicker-fontfamily"
							label={__("Font Family", "essential-blocks")}
							value={fontFamily}
							onChange={(FontFamily) => {
								setAttributes({
									[`${typographyPrefixConstant}FontFamily`]:
										FontFamily,
								});
							}}
						/>

						<WithResButtons
							className="forFontSize"
							resRequiredProps={resRequiredProps}>
							{resOption === "Desktop" && (
								<>
									<UnitControl
										selectedUnit={sizeUnit}
										unitTypes={sizeUnitTypes}
										onClick={(sizeUnit) =>
											setAttributes({
												[`${typographyPrefixConstant}SizeUnit`]:
													sizeUnit,
											})
										}
									/>
									<ResetControl
										onReset={() =>
											setAttributes({
												[`${typographyPrefixConstant}FontSize`]:
													defaultFontSize ||
													(
														objAttributes[
															`${typographyPrefixConstant}FontSize`
														] || {}
													).default,
											})
										}>
										<RangeControl
											label={__(
												"Font Size",
												"essential-blocks"
											)}
											value={fontSize}
											onChange={(FontSize) =>
												setAttributes({
													[`${typographyPrefixConstant}FontSize`]:
														FontSize,
												})
											}
											step={sizeUnit === "em" ? 0.1 : 1}
											min={0}
											max={sizeUnit === "em" ? 10 : 300}
										/>
									</ResetControl>
								</>
							)}

							{resOption === "Tablet" && (
								<>
									<UnitControl
										selectedUnit={TABsizeUnit}
										unitTypes={sizeUnitTypes}
										onClick={(TABsizeUnit) =>
											setAttributes({
												[`TAB${typographyPrefixConstant}SizeUnit`]:
													TABsizeUnit,
											})
										}
									/>

									<ResetControl
										onReset={() =>
											setAttributes({
												[`TAB${typographyPrefixConstant}FontSize`]:
													(
														objAttributes[
															`TAB${typographyPrefixConstant}FontSize`
														] || {}
													).default,
											})
										}>
										<RangeControl
											label={__(
												"Font Size",
												"essential-blocks"
											)}
											value={TABfontSize}
											onChange={(FontSize) =>
												setAttributes({
													[`TAB${typographyPrefixConstant}FontSize`]:
														FontSize,
												})
											}
											step={
												TABsizeUnit === "em" ? 0.1 : 1
											}
											min={0}
											max={
												TABsizeUnit === "em" ? 10 : 300
											}
										/>
									</ResetControl>
								</>
							)}

							{resOption === "Mobile" && (
								<>
									<UnitControl
										selectedUnit={MOBsizeUnit}
										unitTypes={sizeUnitTypes}
										onClick={(MOBsizeUnit) =>
											setAttributes({
												[`MOB${typographyPrefixConstant}SizeUnit`]:
													MOBsizeUnit,
											})
										}
									/>

									<ResetControl
										onReset={() =>
											setAttributes({
												[`MOB${typographyPrefixConstant}FontSize`]:
													(
														objAttributes[
															`MOB${typographyPrefixConstant}FontSize`
														] || {}
													).default,
											})
										}>
										<RangeControl
											label={__(
												"Font Size",
												"essential-blocks"
											)}
											value={MOBfontSize}
											onChange={(FontSize) =>
												setAttributes({
													[`MOB${typographyPrefixConstant}FontSize`]:
														FontSize,
												})
											}
											step={
												MOBsizeUnit === "em" ? 0.1 : 1
											}
											min={0}
											max={
												MOBsizeUnit === "em" ? 10 : 300
											}
										/>
									</ResetControl>
								</>
							)}
						</WithResButtons>

						<SelectControl
							label={__("Font Weight", "essential-blocks")}
							value={fontWeight}
							options={ebFontWeight}
							onChange={(FontWeight) =>
								setAttributes({
									[`${typographyPrefixConstant}FontWeight`]:
										FontWeight,
								})
							}
						/>

						<SelectControl
							label={__("Font Style", "essential-blocks")}
							value={fontStyle}
							options={optionsFontStyles}
							onChange={(fontStyle) =>
								setAttributes({
									[`${typographyPrefixConstant}FontStyle`]:
										fontStyle,
								})
							}
						/>

						<SelectControl
							label={__("Text Transform", "essential-blocks")}
							value={textTransform}
							options={optionsTextTransforms}
							onChange={(TextTransform) =>
								setAttributes({
									[`${typographyPrefixConstant}TextTransform`]:
										TextTransform,
								})
							}
						/>

						<SelectControl
							label={__("Text Decoration", "essential-blocks")}
							value={textDecoration}
							options={optionsTextDecorations}
							onChange={(TextDecoration) =>
								setAttributes({
									[`${typographyPrefixConstant}TextDecoration`]:
										TextDecoration,
								})
							}
						/>

						<WithResButtons
							className="forLetterSpacing"
							resRequiredProps={resRequiredProps}>
							{resOption === "Desktop" && (
								<>
									<UnitControl
										selectedUnit={letterSpacingUnit}
										unitTypes={optionsLhLsp}
										onClick={(LetterSpacingUnit) =>
											setAttributes({
												[`${typographyPrefixConstant}LetterSpacingUnit`]:
													LetterSpacingUnit,
											})
										}
									/>

									<ResetControl
										onReset={() =>
											setAttributes({
												[`${typographyPrefixConstant}LetterSpacing`]:
													(
														objAttributes[
															`${typographyPrefixConstant}LetterSpacing`
														] || {}
													).default,
											})
										}>
										<RangeControl
											label={__(
												"Letter Spacing",
												"essential-blocks"
											)}
											value={letterSpacing}
											onChange={(LetterSpacing) =>
												setAttributes({
													[`${typographyPrefixConstant}LetterSpacing`]:
														LetterSpacing,
												})
											}
											min={0}
											max={
												letterSpacingUnit === "em"
													? 10
													: 100
											}
											step={
												letterSpacingUnit === "em"
													? 0.1
													: 1
											}
										/>
									</ResetControl>
								</>
							)}

							{resOption === "Tablet" && (
								<>
									<UnitControl
										selectedUnit={TABletterSpacingUnit}
										unitTypes={optionsLhLsp}
										onClick={(TABletterSpacingUnit) =>
											setAttributes({
												[`TAB${typographyPrefixConstant}LetterSpacingUnit`]:
													TABletterSpacingUnit,
											})
										}
									/>

									<ResetControl
										onReset={() =>
											setAttributes({
												[`TAB${typographyPrefixConstant}LetterSpacing`]:
													(
														objAttributes[
															`TAB${typographyPrefixConstant}LetterSpacing`
														] || {}
													).default,
											})
										}>
										<RangeControl
											label={__(
												"Letter Spacing",
												"essential-blocks"
											)}
											value={TABletterSpacing}
											onChange={(LetterSpacing) =>
												setAttributes({
													[`TAB${typographyPrefixConstant}LetterSpacing`]:
														LetterSpacing,
												})
											}
											min={0}
											max={
												TABletterSpacingUnit === "em"
													? 10
													: 100
											}
											step={
												TABletterSpacingUnit === "em"
													? 0.1
													: 1
											}
										/>
									</ResetControl>
								</>
							)}

							{resOption === "Mobile" && (
								<>
									<UnitControl
										selectedUnit={MOBletterSpacingUnit}
										unitTypes={optionsLhLsp}
										onClick={(MOBletterSpacingUnit) =>
											setAttributes({
												[`MOB${typographyPrefixConstant}LetterSpacingUnit`]:
													MOBletterSpacingUnit,
											})
										}
									/>

									<ResetControl
										onReset={() =>
											setAttributes({
												[`MOB${typographyPrefixConstant}LetterSpacing`]:
													(
														objAttributes[
															`MOB${typographyPrefixConstant}LetterSpacing`
														] || {}
													).default,
											})
										}>
										<RangeControl
											label={__(
												"Letter Spacing",
												"essential-blocks"
											)}
											value={MOBletterSpacing}
											onChange={(LetterSpacing) =>
												setAttributes({
													[`MOB${typographyPrefixConstant}LetterSpacing`]:
														LetterSpacing,
												})
											}
											min={0}
											max={
												MOBletterSpacingUnit === "em"
													? 10
													: 100
											}
											step={
												MOBletterSpacingUnit === "em"
													? 0.1
													: 1
											}
										/>
									</ResetControl>
								</>
							)}
						</WithResButtons>

						<WithResButtons
							className="forLineHeight"
							resRequiredProps={resRequiredProps}>
							{resOption === "Desktop" && (
								<>
									<UnitControl
										selectedUnit={lineHeightUnit}
										unitTypes={optionsLhLsp}
										onClick={(LineHeightUnit) =>
											setAttributes({
												[`${typographyPrefixConstant}LineHeightUnit`]:
													LineHeightUnit,
											})
										}
									/>

									<ResetControl
										onReset={() =>
											setAttributes({
												[`${typographyPrefixConstant}LineHeight`]:
													(
														objAttributes[
															`${typographyPrefixConstant}LineHeight`
														] || {}
													).default,
											})
										}>
										<RangeControl
											label={__(
												"Line Height",
												"essential-blocks"
											)}
											value={lineHeight}
											onChange={(LineHeight) =>
												setAttributes({
													[`${typographyPrefixConstant}LineHeight`]:
														LineHeight,
												})
											}
											min={0}
											max={
												lineHeightUnit === "em"
													? 10
													: 600
											}
											step={
												lineHeightUnit === "em"
													? 0.1
													: 1
											}
										/>
									</ResetControl>
								</>
							)}

							{resOption === "Tablet" && (
								<>
									<UnitControl
										selectedUnit={TABlineHeightUnit}
										unitTypes={optionsLhLsp}
										onClick={(TABlineHeightUnit) =>
											setAttributes({
												[`TAB${typographyPrefixConstant}LineHeightUnit`]:
													TABlineHeightUnit,
											})
										}
									/>

									<ResetControl
										onReset={() =>
											setAttributes({
												[`TAB${typographyPrefixConstant}LineHeight`]:
													(
														objAttributes[
															`TAB${typographyPrefixConstant}LineHeight`
														] || {}
													).default,
											})
										}>
										<RangeControl
											label={__(
												"Line Height",
												"essential-blocks"
											)}
											value={TABlineHeight}
											onChange={(LineHeight) =>
												setAttributes({
													[`TAB${typographyPrefixConstant}LineHeight`]:
														LineHeight,
												})
											}
											min={0}
											max={
												TABlineHeightUnit === "em"
													? 10
													: 600
											}
											step={
												TABlineHeightUnit === "em"
													? 0.1
													: 1
											}
										/>
									</ResetControl>
								</>
							)}

							{resOption === "Mobile" && (
								<>
									<UnitControl
										selectedUnit={MOBlineHeightUnit}
										unitTypes={optionsLhLsp}
										onClick={(MOBlineHeightUnit) =>
											setAttributes({
												[`MOB${typographyPrefixConstant}LineHeightUnit`]:
													MOBlineHeightUnit,
											})
										}
									/>

									<ResetControl
										onReset={() =>
											setAttributes({
												[`MOB${typographyPrefixConstant}LineHeight`]:
													(
														objAttributes[
															`MOB${typographyPrefixConstant}LineHeight`
														] || {}
													).default,
											})
										}>
										<RangeControl
											label={__(
												"Line Height",
												"essential-blocks"
											)}
											value={MOBlineHeight}
											onChange={(LineHeight) =>
												setAttributes({
													[`MOB${typographyPrefixConstant}LineHeight`]:
														LineHeight,
												})
											}
											min={0}
											max={
												MOBlineHeightUnit === "em"
													? 10
													: 600
											}
											step={
												MOBlineHeightUnit === "em"
													? 0.1
													: 1
											}
										/>
									</ResetControl>
								</>
							)}
						</WithResButtons>
					</div>
				)}
			/>
		</BaseControl>
	);
}

export default TypographyDropdown;
