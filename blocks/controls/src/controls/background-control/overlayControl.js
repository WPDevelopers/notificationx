import { __ } from "@wordpress/i18n";
import { MediaUpload } from "@wordpress/block-editor";

import {
	SelectControl,
	Button,
	RangeControl,
	BaseControl,
	ButtonGroup,
	ToggleControl,
} from "@wordpress/components";

/**
 * Internal dependencies
 */
import GradientColorControl from "../gradient-color-controller";
import UnitControl from "../unit-control";
import ColorControl from "../color-control";
import ImageAvatar from "../image-avatar";
import WithResButtons from "../withResButtons";
import ResetControl from "../reset-control";

export default function overlayControl({
	resRequiredProps,
	controlName,
	noOverlayBgi,
	noTransition,
}) {
	const { setAttributes, attributes, resOption } = resRequiredProps;

	const {
		[`${controlName}ovl_hoverType`]: ovl_hoverType,
		[`${controlName}ovl_bg_transition`]: ovl_bg_transition,
		[`${controlName}ovl_filtersTransition`]: ovl_filtersTransition,
		[`${controlName}ovl_opacityTransition`]: ovl_opacityTransition,

		//  attributes for ovl_hoverType normal start  ⬇
		[`${controlName}overlayType`]: overlayType,
		[`${controlName}overlayColor`]: overlayColor,
		[`${controlName}overlayGradient`]: overlayGradient,
		[`${controlName}ovl_bgImageURL`]: ovl_bgImageURL,
		[`${controlName}ovl_bgImageID`]: ovl_bgImageID,
		[`${controlName}ovl_bgImgAttachment`]: ovl_bgImgAttachment,

		[`${controlName}ovl_opacity`]: ovl_opacity,
		[`${controlName}ovl_blendMode`]: ovl_blendMode,

		[`${controlName}ovl_allowFilters`]: ovl_allowFilters,
		[`${controlName}ovl_fltrBrightness`]: ovl_fltrBrightness,
		[`${controlName}ovl_fltrContrast`]: ovl_fltrContrast,
		[`${controlName}ovl_fltrSaturation`]: ovl_fltrSaturation,
		[`${controlName}ovl_fltrBlur`]: ovl_fltrBlur,
		[`${controlName}ovl_fltrHue`]: ovl_fltrHue,

		[`${controlName}ovl_backgroundSize`]: ovl_backgroundSize,
		[`${controlName}ovl_bgImgCustomSize`]: ovl_bgImgCustomSize,
		[`${controlName}ovl_bgImgCustomSizeUnit`]: ovl_bgImgCustomSizeUnit,
		[`${controlName}ovl_bgImgPos`]: ovl_bgImgPos,
		[`${controlName}ovl_bgImgcustomPosX`]: ovl_bgImgcustomPosX,
		[`${controlName}ovl_bgImgcustomPosXUnit`]: ovl_bgImgcustomPosXUnit,
		[`${controlName}ovl_bgImgcustomPosY`]: ovl_bgImgcustomPosY,
		[`${controlName}ovl_bgImgcustomPosYUnit`]: ovl_bgImgcustomPosYUnit,
		[`${controlName}ovl_bgImgRepeat`]: ovl_bgImgRepeat,

		[`TAB${controlName}ovl_backgroundSize`]: TABovl_backgroundSize,
		[`TAB${controlName}ovl_bgImgCustomSize`]: TABovl_bgImgCustomSize,
		[`TAB${controlName}ovl_bgImgCustomSizeUnit`]:
			TABovl_bgImgCustomSizeUnit,
		[`TAB${controlName}ovl_bgImgPos`]: TABovl_bgImgPos,
		[`TAB${controlName}ovl_bgImgcustomPosX`]: TABovl_bgImgcustomPosX,
		[`TAB${controlName}ovl_bgImgcustomPosXUnit`]:
			TABovl_bgImgcustomPosXUnit,
		[`TAB${controlName}ovl_bgImgcustomPosY`]: TABovl_bgImgcustomPosY,
		[`TAB${controlName}ovl_bgImgcustomPosYUnit`]:
			TABovl_bgImgcustomPosYUnit,
		[`TAB${controlName}ovl_bgImgRepeat`]: TABovl_bgImgRepeat,

		[`MOB${controlName}ovl_backgroundSize`]: MOBovl_backgroundSize,
		[`MOB${controlName}ovl_bgImgCustomSize`]: MOBovl_bgImgCustomSize,
		[`MOB${controlName}ovl_bgImgCustomSizeUnit`]:
			MOBovl_bgImgCustomSizeUnit,
		[`MOB${controlName}ovl_bgImgPos`]: MOBovl_bgImgPos,
		[`MOB${controlName}ovl_bgImgcustomPosX`]: MOBovl_bgImgcustomPosX,
		[`MOB${controlName}ovl_bgImgcustomPosXUnit`]:
			MOBovl_bgImgcustomPosXUnit,
		[`MOB${controlName}ovl_bgImgcustomPosY`]: MOBovl_bgImgcustomPosY,
		[`MOB${controlName}ovl_bgImgcustomPosYUnit`]:
			MOBovl_bgImgcustomPosYUnit,
		[`MOB${controlName}ovl_bgImgRepeat`]: MOBovl_bgImgRepeat,
		//  attributes for ovl_hoverType normal end

		//  attributes for ovl_hoverType hover start ⬇
		[`hov_${controlName}overlayType`]: hov_overlayType,
		[`hov_${controlName}overlayColor`]: hov_overlayColor,
		[`hov_${controlName}overlayGradient`]: hov_overlayGradient,
		[`hov_${controlName}ovl_bgImageURL`]: hov_ovl_bgImageURL,
		[`hov_${controlName}ovl_bgImageID`]: hov_ovl_bgImageID,
		[`hov_${controlName}ovl_bgImgAttachment`]: hov_ovl_bgImgAttachment,

		[`hov_${controlName}ovl_opacity`]: hov_ovl_opacity,
		[`hov_${controlName}ovl_blendMode`]: hov_ovl_blendMode,

		[`hov_${controlName}ovl_allowFilters`]: hov_ovl_allowFilters,
		[`hov_${controlName}ovl_fltrBrightness`]: hov_ovl_fltrBrightness,
		[`hov_${controlName}ovl_fltrContrast`]: hov_ovl_fltrContrast,
		[`hov_${controlName}ovl_fltrSaturation`]: hov_ovl_fltrSaturation,
		[`hov_${controlName}ovl_fltrBlur`]: hov_ovl_fltrBlur,
		[`hov_${controlName}ovl_fltrHue`]: hov_ovl_fltrHue,

		[`hov_${controlName}ovl_backgroundSize`]: hov_ovl_backgroundSize,
		[`hov_${controlName}ovl_bgImgCustomSize`]: hov_ovl_bgImgCustomSize,
		[`hov_${controlName}ovl_bgImgCustomSizeUnit`]:
			hov_ovl_bgImgCustomSizeUnit,
		[`hov_${controlName}ovl_bgImgPos`]: hov_ovl_bgImgPos,
		[`hov_${controlName}ovl_bgImgcustomPosX`]: hov_ovl_bgImgcustomPosX,
		[`hov_${controlName}ovl_bgImgcustomPosXUnit`]:
			hov_ovl_bgImgcustomPosXUnit,
		[`hov_${controlName}ovl_bgImgcustomPosY`]: hov_ovl_bgImgcustomPosY,
		[`hov_${controlName}ovl_bgImgcustomPosYUnit`]:
			hov_ovl_bgImgcustomPosYUnit,
		[`hov_${controlName}ovl_bgImgRepeat`]: hov_ovl_bgImgRepeat,

		[`hov_TAB${controlName}ovl_backgroundSize`]: hov_TABovl_backgroundSize,
		[`hov_TAB${controlName}ovl_bgImgCustomSize`]:
			hov_TABovl_bgImgCustomSize,
		[`hov_TAB${controlName}ovl_bgImgCustomSizeUnit`]:
			hov_TABovl_bgImgCustomSizeUnit,
		[`hov_TAB${controlName}ovl_bgImgPos`]: hov_TABovl_bgImgPos,
		[`hov_TAB${controlName}ovl_bgImgcustomPosX`]:
			hov_TABovl_bgImgcustomPosX,
		[`hov_TAB${controlName}ovl_bgImgcustomPosXUnit`]:
			hov_TABovl_bgImgcustomPosXUnit,
		[`hov_TAB${controlName}ovl_bgImgcustomPosY`]:
			hov_TABovl_bgImgcustomPosY,
		[`hov_TAB${controlName}ovl_bgImgcustomPosYUnit`]:
			hov_TABovl_bgImgcustomPosYUnit,
		[`hov_TAB${controlName}ovl_bgImgRepeat`]: hov_TABovl_bgImgRepeat,

		[`hov_MOB${controlName}ovl_backgroundSize`]: hov_MOBovl_backgroundSize,
		[`hov_MOB${controlName}ovl_bgImgCustomSize`]:
			hov_MOBovl_bgImgCustomSize,
		[`hov_MOB${controlName}ovl_bgImgCustomSizeUnit`]:
			hov_MOBovl_bgImgCustomSizeUnit,
		[`hov_MOB${controlName}ovl_bgImgPos`]: hov_MOBovl_bgImgPos,
		[`hov_MOB${controlName}ovl_bgImgcustomPosX`]:
			hov_MOBovl_bgImgcustomPosX,
		[`hov_MOB${controlName}ovl_bgImgcustomPosXUnit`]:
			hov_MOBovl_bgImgcustomPosXUnit,
		[`hov_MOB${controlName}ovl_bgImgcustomPosY`]:
			hov_MOBovl_bgImgcustomPosY,
		[`hov_MOB${controlName}ovl_bgImgcustomPosYUnit`]:
			hov_MOBovl_bgImgcustomPosYUnit,
		[`hov_MOB${controlName}ovl_bgImgRepeat`]: hov_MOBovl_bgImgRepeat,
	} = attributes;

	return (
		<>
			<BaseControl
				label={__(
					"Background Overlay",
					"essential-blocks"
				)}></BaseControl>
			<BaseControl>
				<ButtonGroup>
					{[
						{
							label: __("Normal", "essential-blocks"),
							value: "normal",
						},
						{
							label: __("Hover", "essential-blocks"),
							value: "hover",
						},
					].map(({ value, label }) => (
						<Button
							// isSmall
							// isLarge
							isPrimary={ovl_hoverType === value}
							isSecondary={ovl_hoverType !== value}
							onClick={() =>
								setAttributes({
									[`${controlName}ovl_hoverType`]: value,
								})
							}>
							{label}
						</Button>
					))}
				</ButtonGroup>
			</BaseControl>

			{ovl_hoverType === "normal" && (
				<>
					<BaseControl label={__("Overlay Type", "essential-blocks")}>
						<ButtonGroup id="eb-background-control-new">
							{[
								{ label: "Classic", value: "classic" },
								{ label: "Gradient", value: "gradient" },
							].map(({ value, label }) => (
								<Button
									isLarge
									isPrimary={overlayType === value}
									isSecondary={overlayType !== value}
									onClick={() =>
										setAttributes({
											[`${controlName}overlayType`]:
												value,
										})
									}>
									{label}
								</Button>
							))}
						</ButtonGroup>
					</BaseControl>

					{overlayType === "classic" && (
						<>
							<ColorControl
								label={__("Overlay Color", "essential-blocks")}
								color={overlayColor}
								onChange={(overlayColor) =>
									setAttributes({
										[`${controlName}overlayColor`]:
											overlayColor,
									})
								}
							/>

							{noOverlayBgi === false && (
								<>
									<BaseControl
										label={__(
											"Overlay Image",
											"essential-blocks"
										)}></BaseControl>

									<MediaUpload
										onSelect={({ url, id }) =>
											setAttributes({
												[`${controlName}ovl_bgImageURL`]:
													url,
												[`${controlName}ovl_bgImageID`]:
													id,
											})
										}
										type="image"
										value={ovl_bgImageID}
										render={({ open }) =>
											!ovl_bgImageURL && (
												<>
													<Button
														className="eb-background-control-inspector-panel-img-btn components-button"
														label={__(
															"Upload Image",
															"essential-blocks"
														)}
														icon="format-image"
														onClick={open}
													/>
													<span
														style={{
															padding: "10px 0",
															display: "block",
														}}></span>
												</>
											)
										}
									/>

									{ovl_bgImageURL && (
										<>
											<ImageAvatar
												imageUrl={ovl_bgImageURL}
												onDeleteImage={() =>
													setAttributes({
														[`${controlName}ovl_bgImageURL`]:
															null,
													})
												}
											/>

											{resOption === "Desktop" && (
												<>
													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Position">
														<SelectControl
															value={ovl_bgImgPos}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"Center Center",
																		"essential-blocks"
																	),
																	value: "center center",
																},
																{
																	label: __(
																		"Center Left",
																		"essential-blocks"
																	),
																	value: "center left",
																},
																{
																	label: __(
																		"Center Right",
																		"essential-blocks"
																	),
																	value: "center right",
																},
																{
																	label: __(
																		"Top Center",
																		"essential-blocks"
																	),
																	value: "top center",
																},
																{
																	label: __(
																		"Top Left",
																		"essential-blocks"
																	),
																	value: "top left",
																},
																{
																	label: __(
																		"Top Right",
																		"essential-blocks"
																	),
																	value: "top right",
																},
																{
																	label: __(
																		"Bottom Center",
																		"essential-blocks"
																	),
																	value: "bottom center",
																},
																{
																	label: __(
																		"Bottom Left",
																		"essential-blocks"
																	),
																	value: "bottom left",
																},
																{
																	label: __(
																		"Bottom Right",
																		"essential-blocks"
																	),
																	value: "bottom right",
																},
																{
																	label: __(
																		"Custom",
																		"essential-blocks"
																	),
																	value: "custom",
																},
															]}
															onChange={(
																ovl_bgImgPos
															) =>
																setAttributes({
																	[`${controlName}ovl_bgImgPos`]:
																		ovl_bgImgPos,
																})
															}
														/>
													</WithResButtons>

													{ovl_bgImgPos ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	ovl_bgImgcustomPosXUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	ovl_bgImgcustomPosXUnit
																) =>
																	setAttributes(
																		{
																			[`${controlName}ovl_bgImgcustomPosXUnit`]:
																				ovl_bgImgcustomPosXUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="X Position">
																<RangeControl
																	value={
																		ovl_bgImgcustomPosX
																	}
																	min={0}
																	max={
																		ovl_bgImgcustomPosXUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	onChange={(
																		ovl_bgImgcustomPosX
																	) =>
																		setAttributes(
																			{
																				[`${controlName}ovl_bgImgcustomPosX`]:
																					ovl_bgImgcustomPosX,
																			}
																		)
																	}
																/>
															</WithResButtons>

															<UnitControl
																selectedUnit={
																	ovl_bgImgcustomPosYUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	ovl_bgImgcustomPosYUnit
																) =>
																	setAttributes(
																		{
																			[`${controlName}ovl_bgImgcustomPosYUnit`]:
																				ovl_bgImgcustomPosYUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="Y Position">
																<RangeControl
																	value={
																		ovl_bgImgcustomPosY
																	}
																	min={0}
																	max={
																		ovl_bgImgcustomPosYUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		ovl_bgImgcustomPosYUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		ovl_bgImgcustomPosY
																	) =>
																		setAttributes(
																			{
																				[`${controlName}ovl_bgImgcustomPosY`]:
																					ovl_bgImgcustomPosY,
																			}
																		)
																	}
																/>
															</WithResButtons>
														</>
													)}

													<SelectControl
														label="Attachment"
														value={
															ovl_bgImgAttachment
														}
														options={[
															{
																label: __(
																	"Default",
																	"essential-blocks"
																),
																value: "",
															},
															{
																label: __(
																	"Scroll",
																	"essential-blocks"
																),
																value: "scroll",
															},
															{
																label: __(
																	"Fixed",
																	"essential-blocks"
																),
																value: "fixed",
															},
														]}
														onChange={(
															ovl_bgImgAttachment
														) =>
															setAttributes({
																[`${controlName}ovl_bgImgAttachment`]:
																	ovl_bgImgAttachment,
															})
														}
													/>

													{ovl_bgImgAttachment ===
														"fixed" && (
														<p
															style={{
																marginTop:
																	"-10px",
																paddingBottom:
																	"10px",
															}}>
															<i>
																Note: Attachment
																Fixed works only
																on desktop.
															</i>
														</p>
													)}

													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Repeat">
														<SelectControl
															value={
																ovl_bgImgRepeat
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"No-repeat",
																		"essential-blocks"
																	),
																	value: "no-repeat",
																},
																{
																	label: __(
																		"Repeat",
																		"essential-blocks"
																	),
																	value: "repeat",
																},
																{
																	label: __(
																		"Repeat-x",
																		"essential-blocks"
																	),
																	value: "repeat-x",
																},
																{
																	label: __(
																		"Repeat-y",
																		"essential-blocks"
																	),
																	value: "repeat-y",
																},
															]}
															onChange={(
																ovl_bgImgRepeat
															) =>
																setAttributes({
																	[`${controlName}ovl_bgImgRepeat`]:
																		ovl_bgImgRepeat,
																})
															}
														/>
													</WithResButtons>

													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Size">
														<SelectControl
															value={
																ovl_backgroundSize
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"Auto",
																		"essential-blocks"
																	),
																	value: "auto",
																},
																{
																	label: __(
																		"Cover",
																		"essential-blocks"
																	),
																	value: "cover",
																},
																{
																	label: __(
																		"Contain",
																		"essential-blocks"
																	),
																	value: "contain",
																},
																{
																	label: __(
																		"Custom",
																		"essential-blocks"
																	),
																	value: "custom",
																},
															]}
															onChange={(
																ovl_backgroundSize
															) =>
																setAttributes({
																	[`${controlName}ovl_backgroundSize`]:
																		ovl_backgroundSize,
																})
															}
														/>
													</WithResButtons>

													{ovl_backgroundSize ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	ovl_bgImgCustomSizeUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	ovl_bgImgCustomSizeUnit
																) =>
																	setAttributes(
																		{
																			[`${controlName}ovl_bgImgCustomSizeUnit`]:
																				ovl_bgImgCustomSizeUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="Width">
																<RangeControl
																	value={
																		ovl_bgImgCustomSize
																	}
																	min={0}
																	max={
																		ovl_bgImgCustomSizeUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		ovl_bgImgCustomSizeUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		ovl_bgImgCustomSize
																	) =>
																		setAttributes(
																			{
																				[`${controlName}ovl_bgImgCustomSize`]:
																					ovl_bgImgCustomSize,
																			}
																		)
																	}
																/>
															</WithResButtons>
														</>
													)}
												</>
											)}

											{resOption === "Tablet" && (
												<>
													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Position">
														<SelectControl
															value={
																TABovl_bgImgPos
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"Center Center",
																		"essential-blocks"
																	),
																	value: "center center",
																},
																{
																	label: __(
																		"Center Left",
																		"essential-blocks"
																	),
																	value: "center left",
																},
																{
																	label: __(
																		"Center Right",
																		"essential-blocks"
																	),
																	value: "center right",
																},
																{
																	label: __(
																		"Top Center",
																		"essential-blocks"
																	),
																	value: "top center",
																},
																{
																	label: __(
																		"Top Left",
																		"essential-blocks"
																	),
																	value: "top left",
																},
																{
																	label: __(
																		"Top Right",
																		"essential-blocks"
																	),
																	value: "top right",
																},
																{
																	label: __(
																		"Bottom Center",
																		"essential-blocks"
																	),
																	value: "bottom center",
																},
																{
																	label: __(
																		"Bottom Left",
																		"essential-blocks"
																	),
																	value: "bottom left",
																},
																{
																	label: __(
																		"Bottom Right",
																		"essential-blocks"
																	),
																	value: "bottom right",
																},
																{
																	label: __(
																		"Custom",
																		"essential-blocks"
																	),
																	value: "custom",
																},
															]}
															onChange={(
																TABovl_bgImgPos
															) =>
																setAttributes({
																	[`TAB${controlName}ovl_bgImgPos`]:
																		TABovl_bgImgPos,
																})
															}
														/>
													</WithResButtons>

													{TABovl_bgImgPos ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	TABovl_bgImgcustomPosXUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	TABovl_bgImgcustomPosXUnit
																) =>
																	setAttributes(
																		{
																			[`TAB${controlName}ovl_bgImgcustomPosXUnit`]:
																				TABovl_bgImgcustomPosXUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="X Position">
																<RangeControl
																	value={
																		TABovl_bgImgcustomPosX
																	}
																	min={-2000}
																	max={
																		// TABovl_bgImgcustomPosXUnit === "px"
																		//   ?
																		2000
																		// : 100
																	}
																	onChange={(
																		TABovl_bgImgcustomPosX
																	) =>
																		setAttributes(
																			{
																				[`TAB${controlName}ovl_bgImgcustomPosX`]:
																					TABovl_bgImgcustomPosX,
																			}
																		)
																	}
																/>
															</WithResButtons>

															<UnitControl
																selectedUnit={
																	TABovl_bgImgcustomPosYUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	TABovl_bgImgcustomPosYUnit
																) =>
																	setAttributes(
																		{
																			[`TAB${controlName}ovl_bgImgcustomPosYUnit`]:
																				TABovl_bgImgcustomPosYUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="Y Position">
																<RangeControl
																	value={
																		TABovl_bgImgcustomPosY
																	}
																	min={-2000}
																	max={
																		// TABovl_bgImgcustomPosYUnit === "px"
																		//   ?
																		2000
																		// : 100
																	}
																	step={
																		TABovl_bgImgcustomPosYUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		TABovl_bgImgcustomPosY
																	) =>
																		setAttributes(
																			{
																				[`TAB${controlName}ovl_bgImgcustomPosY`]:
																					TABovl_bgImgcustomPosY,
																			}
																		)
																	}
																/>
															</WithResButtons>
														</>
													)}

													<SelectControl
														label="Attachment"
														value={
															ovl_bgImgAttachment
														}
														options={[
															{
																label: __(
																	"Default",
																	"essential-blocks"
																),
																value: "",
															},
															{
																label: __(
																	"Scroll",
																	"essential-blocks"
																),
																value: "scroll",
															},
															{
																label: __(
																	"Fixed",
																	"essential-blocks"
																),
																value: "fixed",
															},
														]}
														onChange={(
															ovl_bgImgAttachment
														) =>
															setAttributes({
																[`${controlName}ovl_bgImgAttachment`]:
																	ovl_bgImgAttachment,
															})
														}
													/>

													{ovl_bgImgAttachment ===
														"fixed" && (
														<p
															style={{
																marginTop:
																	"-10px",
																paddingBottom:
																	"10px",
															}}>
															<i>
																Note: Attachment
																Fixed works only
																on desktop.
															</i>
														</p>
													)}

													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Repeat">
														<SelectControl
															value={
																TABovl_bgImgRepeat
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"No-repeat",
																		"essential-blocks"
																	),
																	value: "no-repeat",
																},
																{
																	label: __(
																		"Repeat",
																		"essential-blocks"
																	),
																	value: "repeat",
																},
																{
																	label: __(
																		"Repeat-x",
																		"essential-blocks"
																	),
																	value: "repeat-x",
																},
																{
																	label: __(
																		"Repeat-y",
																		"essential-blocks"
																	),
																	value: "repeat-y",
																},
															]}
															onChange={(
																TABovl_bgImgRepeat
															) =>
																setAttributes({
																	[`TAB${controlName}ovl_bgImgRepeat`]:
																		TABovl_bgImgRepeat,
																})
															}
														/>
													</WithResButtons>

													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Size">
														<SelectControl
															value={
																TABovl_backgroundSize
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"Auto",
																		"essential-blocks"
																	),
																	value: "auto",
																},
																{
																	label: __(
																		"Cover",
																		"essential-blocks"
																	),
																	value: "cover",
																},
																{
																	label: __(
																		"Contain",
																		"essential-blocks"
																	),
																	value: "contain",
																},
																{
																	label: __(
																		"Custom",
																		"essential-blocks"
																	),
																	value: "custom",
																},
															]}
															onChange={(
																TABovl_backgroundSize
															) =>
																setAttributes({
																	[`TAB${controlName}ovl_backgroundSize`]:
																		TABovl_backgroundSize,
																})
															}
														/>
													</WithResButtons>

													{TABovl_backgroundSize ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	TABovl_bgImgCustomSizeUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	TABovl_bgImgCustomSizeUnit
																) =>
																	setAttributes(
																		{
																			[`TAB${controlName}ovl_bgImgCustomSizeUnit`]:
																				TABovl_bgImgCustomSizeUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="Width">
																<RangeControl
																	value={
																		TABovl_bgImgCustomSize
																	}
																	min={0}
																	max={
																		TABovl_bgImgCustomSizeUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		TABovl_bgImgCustomSizeUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		TABovl_bgImgCustomSize
																	) =>
																		setAttributes(
																			{
																				[`TAB${controlName}ovl_bgImgCustomSize`]:
																					TABovl_bgImgCustomSize,
																			}
																		)
																	}
																/>
															</WithResButtons>
														</>
													)}
												</>
											)}

											{resOption === "Mobile" && (
												<>
													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Position">
														<SelectControl
															value={
																MOBovl_bgImgPos
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"Center Center",
																		"essential-blocks"
																	),
																	value: "center center",
																},
																{
																	label: __(
																		"Center Left",
																		"essential-blocks"
																	),
																	value: "center left",
																},
																{
																	label: __(
																		"Center Right",
																		"essential-blocks"
																	),
																	value: "center right",
																},
																{
																	label: __(
																		"Top Center",
																		"essential-blocks"
																	),
																	value: "top center",
																},
																{
																	label: __(
																		"Top Left",
																		"essential-blocks"
																	),
																	value: "top left",
																},
																{
																	label: __(
																		"Top Right",
																		"essential-blocks"
																	),
																	value: "top right",
																},
																{
																	label: __(
																		"Bottom Center",
																		"essential-blocks"
																	),
																	value: "bottom center",
																},
																{
																	label: __(
																		"Bottom Left",
																		"essential-blocks"
																	),
																	value: "bottom left",
																},
																{
																	label: __(
																		"Bottom Right",
																		"essential-blocks"
																	),
																	value: "bottom right",
																},
																{
																	label: __(
																		"Custom",
																		"essential-blocks"
																	),
																	value: "custom",
																},
															]}
															onChange={(
																MOBovl_bgImgPos
															) =>
																setAttributes({
																	[`MOB${controlName}ovl_bgImgPos`]:
																		MOBovl_bgImgPos,
																})
															}
														/>
													</WithResButtons>

													{MOBovl_bgImgPos ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	MOBovl_bgImgcustomPosXUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	MOBovl_bgImgcustomPosXUnit
																) =>
																	setAttributes(
																		{
																			[`MOB${controlName}ovl_bgImgcustomPosXUnit`]:
																				MOBovl_bgImgcustomPosXUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="X Position">
																<RangeControl
																	value={
																		MOBovl_bgImgcustomPosX
																	}
																	min={0}
																	max={
																		MOBovl_bgImgcustomPosXUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	onChange={(
																		MOBovl_bgImgcustomPosX
																	) =>
																		setAttributes(
																			{
																				[`MOB${controlName}ovl_bgImgcustomPosX`]:
																					MOBovl_bgImgcustomPosX,
																			}
																		)
																	}
																/>
															</WithResButtons>

															<UnitControl
																selectedUnit={
																	MOBovl_bgImgcustomPosYUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	MOBovl_bgImgcustomPosYUnit
																) =>
																	setAttributes(
																		{
																			[`MOB${controlName}ovl_bgImgcustomPosYUnit`]:
																				MOBovl_bgImgcustomPosYUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="Y Position">
																<RangeControl
																	value={
																		MOBovl_bgImgcustomPosY
																	}
																	min={0}
																	max={
																		MOBovl_bgImgcustomPosYUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		MOBovl_bgImgcustomPosYUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		MOBovl_bgImgcustomPosY
																	) =>
																		setAttributes(
																			{
																				[`MOB${controlName}ovl_bgImgcustomPosY`]:
																					MOBovl_bgImgcustomPosY,
																			}
																		)
																	}
																/>
															</WithResButtons>
														</>
													)}

													<SelectControl
														label="Attachment"
														value={
															ovl_bgImgAttachment
														}
														options={[
															{
																label: __(
																	"Default",
																	"essential-blocks"
																),
																value: "",
															},
															{
																label: __(
																	"Scroll",
																	"essential-blocks"
																),
																value: "scroll",
															},
															{
																label: __(
																	"Fixed",
																	"essential-blocks"
																),
																value: "fixed",
															},
														]}
														onChange={(
															ovl_bgImgAttachment
														) =>
															setAttributes({
																[`${controlName}ovl_bgImgAttachment`]:
																	ovl_bgImgAttachment,
															})
														}
													/>

													{ovl_bgImgAttachment ===
														"fixed" && (
														<p
															style={{
																marginTop:
																	"-10px",
																paddingBottom:
																	"10px",
															}}>
															<i>
																Note: Attachment
																Fixed works only
																on desktop.
															</i>
														</p>
													)}

													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Repeat">
														<SelectControl
															value={
																MOBovl_bgImgRepeat
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"No-repeat",
																		"essential-blocks"
																	),
																	value: "no-repeat",
																},
																{
																	label: __(
																		"Repeat",
																		"essential-blocks"
																	),
																	value: "repeat",
																},
																{
																	label: __(
																		"Repeat-x",
																		"essential-blocks"
																	),
																	value: "repeat-x",
																},
																{
																	label: __(
																		"Repeat-y",
																		"essential-blocks"
																	),
																	value: "repeat-y",
																},
															]}
															onChange={(
																MOBovl_bgImgRepeat
															) =>
																setAttributes({
																	[`MOB${controlName}ovl_bgImgRepeat`]:
																		MOBovl_bgImgRepeat,
																})
															}
														/>
													</WithResButtons>

													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Size">
														<SelectControl
															value={
																MOBovl_backgroundSize
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"Auto",
																		"essential-blocks"
																	),
																	value: "auto",
																},
																{
																	label: __(
																		"Cover",
																		"essential-blocks"
																	),
																	value: "cover",
																},
																{
																	label: __(
																		"Contain",
																		"essential-blocks"
																	),
																	value: "contain",
																},
																{
																	label: __(
																		"Custom",
																		"essential-blocks"
																	),
																	value: "custom",
																},
															]}
															onChange={(
																MOBovl_backgroundSize
															) =>
																setAttributes({
																	[`MOB${controlName}ovl_backgroundSize`]:
																		MOBovl_backgroundSize,
																})
															}
														/>
													</WithResButtons>

													{MOBovl_backgroundSize ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	MOBovl_bgImgCustomSizeUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	MOBovl_bgImgCustomSizeUnit
																) =>
																	setAttributes(
																		{
																			[`MOB${controlName}ovl_bgImgCustomSizeUnit`]:
																				MOBovl_bgImgCustomSizeUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="Width">
																<RangeControl
																	value={
																		MOBovl_bgImgCustomSize
																	}
																	min={0}
																	max={
																		MOBovl_bgImgCustomSizeUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		MOBovl_bgImgCustomSizeUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		MOBovl_bgImgCustomSize
																	) =>
																		setAttributes(
																			{
																				[`MOB${controlName}ovl_bgImgCustomSize`]:
																					MOBovl_bgImgCustomSize,
																			}
																		)
																	}
																/>
															</WithResButtons>
														</>
													)}
												</>
											)}
										</>
									)}
								</>
							)}
						</>
					)}

					{overlayType === "gradient" && (
						<GradientColorControl
							gradientColor={overlayGradient}
							onChange={(overlayGradient) =>
								setAttributes({
									[`${controlName}overlayGradient`]:
										overlayGradient,
								})
							}
						/>
					)}

					<hr />

					<RangeControl
						label={__("Opacity", "essential-blocks")}
						value={ovl_opacity}
						onChange={(ovl_opacity) =>
							setAttributes({
								[`${controlName}ovl_opacity`]: ovl_opacity,
							})
						}
						step={0.01}
						min={0}
						max={1}
					/>

					<SelectControl
						label={__("Blend Mode", "essential-blocks")}
						value={ovl_blendMode}
						options={[
							{
								label: __("Normal", "essential-blocks"),
								value: "",
							},
							{
								label: __("Multiply", "essential-blocks"),
								value: "multiply",
							},
							{
								label: __("Screen", "essential-blocks"),
								value: "screen",
							},
							{
								label: __("Overlay", "essential-blocks"),
								value: "overlay",
							},
							{
								label: __("Darken", "essential-blocks"),
								value: "darken",
							},
							{
								label: __("Lighten", "essential-blocks"),
								value: "lighten",
							},
							{
								label: __("Color Dodge", "essential-blocks"),
								value: "color-dodge",
							},
							{
								label: __("Saturation", "essential-blocks"),
								value: "saturation",
							},
							{
								label: __("Color", "essential-blocks"),
								value: "color",
							},
							{
								label: __("Luminosity", "essential-blocks"),
								value: "luminosity",
							},
						]}
						onChange={(ovl_blendMode) =>
							setAttributes({
								[`${controlName}ovl_blendMode`]: ovl_blendMode,
							})
						}
					/>

					<ToggleControl
						label={__("CSS Filters", "essential-blocks")}
						checked={ovl_allowFilters}
						onChange={() =>
							setAttributes({
								[`${controlName}ovl_allowFilters`]:
									!ovl_allowFilters,
							})
						}
					/>

					{ovl_allowFilters && (
						<>
							<RangeControl
								label={__("Blur", "essential-blocks")}
								value={ovl_fltrBlur}
								onChange={(ovl_fltrBlur) =>
									setAttributes({
										[`${controlName}ovl_fltrBlur`]:
											ovl_fltrBlur,
									})
								}
								step={0.1}
								min={0}
								max={10}
							/>
							<RangeControl
								label={__("Brightness", "essential-blocks")}
								value={ovl_fltrBrightness}
								onChange={(ovl_fltrBrightness) =>
									setAttributes({
										[`${controlName}ovl_fltrBrightness`]:
											ovl_fltrBrightness,
									})
								}
								step={1}
								min={0}
								max={200}
							/>
							<RangeControl
								label={__("Contrast", "essential-blocks")}
								value={ovl_fltrContrast}
								onChange={(ovl_fltrContrast) =>
									setAttributes({
										[`${controlName}ovl_fltrContrast`]:
											ovl_fltrContrast,
									})
								}
								step={1}
								min={0}
								max={200}
							/>
							<RangeControl
								label={__("Saturation", "essential-blocks")}
								value={ovl_fltrSaturation}
								onChange={(ovl_fltrSaturation) =>
									setAttributes({
										[`${controlName}ovl_fltrSaturation`]:
											ovl_fltrSaturation,
									})
								}
								step={1}
								min={0}
								max={200}
							/>
							<RangeControl
								label={__("Hue", "essential-blocks")}
								value={ovl_fltrHue}
								onChange={(ovl_fltrHue) =>
									setAttributes({
										[`${controlName}ovl_fltrHue`]:
											ovl_fltrHue,
									})
								}
								step={1}
								min={0}
								max={360}
							/>
						</>
					)}
				</>
			)}

			{ovl_hoverType === "hover" && (
				<>
					<BaseControl label={__("Overlay Type", "essential-blocks")}>
						<ButtonGroup id="eb-background-control-new">
							{[
								{ label: "Classic", value: "classic" },
								{ label: "Gradient", value: "gradient" },
							].map(({ value, label }) => (
								<Button
									isLarge
									isPrimary={hov_overlayType === value}
									isSecondary={hov_overlayType !== value}
									onClick={() =>
										setAttributes({
											[`hov_${controlName}overlayType`]:
												value,
										})
									}>
									{label}
								</Button>
							))}
						</ButtonGroup>
					</BaseControl>

					{hov_overlayType === "classic" && (
						<>
							<ColorControl
								label={__("Overlay Color", "essential-blocks")}
								color={hov_overlayColor}
								onChange={(hov_overlayColor) =>
									setAttributes({
										[`hov_${controlName}overlayColor`]:
											hov_overlayColor,
									})
								}
							/>

							{noOverlayBgi === false && (
								<>
									<BaseControl
										label={__(
											"Overlay Image",
											"essential-blocks"
										)}></BaseControl>

									<MediaUpload
										onSelect={({ url, id }) =>
											setAttributes({
												[`hov_${controlName}ovl_bgImageURL`]:
													url,
												[`hov_${controlName}ovl_bgImageID`]:
													id,
											})
										}
										type="image"
										value={hov_ovl_bgImageID}
										render={({ open }) =>
											!hov_ovl_bgImageURL && (
												<>
													<Button
														className="eb-background-control-inspector-panel-img-btn components-button"
														label={__(
															"Upload Image",
															"essential-blocks"
														)}
														icon="format-image"
														onClick={open}
													/>
													<span
														style={{
															padding: "10px 0",
															display: "block",
														}}></span>
												</>
											)
										}
									/>

									{hov_ovl_bgImageURL && (
										<>
											<ImageAvatar
												imageUrl={hov_ovl_bgImageURL}
												onDeleteImage={() =>
													setAttributes({
														[`hov_${controlName}ovl_bgImageURL`]:
															null,
													})
												}
											/>

											{resOption === "Desktop" && (
												<>
													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Position">
														<SelectControl
															value={
																hov_ovl_bgImgPos
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"Center Center",
																		"essential-blocks"
																	),
																	value: "center center",
																},
																{
																	label: __(
																		"Center Left",
																		"essential-blocks"
																	),
																	value: "center left",
																},
																{
																	label: __(
																		"Center Right",
																		"essential-blocks"
																	),
																	value: "center right",
																},
																{
																	label: __(
																		"Top Center",
																		"essential-blocks"
																	),
																	value: "top center",
																},
																{
																	label: __(
																		"Top Left",
																		"essential-blocks"
																	),
																	value: "top left",
																},
																{
																	label: __(
																		"Top Right",
																		"essential-blocks"
																	),
																	value: "top right",
																},
																{
																	label: __(
																		"Bottom Center",
																		"essential-blocks"
																	),
																	value: "bottom center",
																},
																{
																	label: __(
																		"Bottom Left",
																		"essential-blocks"
																	),
																	value: "bottom left",
																},
																{
																	label: __(
																		"Bottom Right",
																		"essential-blocks"
																	),
																	value: "bottom right",
																},
																{
																	label: __(
																		"Custom",
																		"essential-blocks"
																	),
																	value: "custom",
																},
															]}
															onChange={(
																hov_ovl_bgImgPos
															) =>
																setAttributes({
																	[`hov_${controlName}ovl_bgImgPos`]:
																		hov_ovl_bgImgPos,
																})
															}
														/>
													</WithResButtons>

													{hov_ovl_bgImgPos ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	hov_ovl_bgImgcustomPosXUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	hov_ovl_bgImgcustomPosXUnit
																) =>
																	setAttributes(
																		{
																			[`hov_${controlName}ovl_bgImgcustomPosXUnit`]:
																				hov_ovl_bgImgcustomPosXUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="X Position">
																<RangeControl
																	value={
																		hov_ovl_bgImgcustomPosX
																	}
																	min={0}
																	max={
																		hov_ovl_bgImgcustomPosXUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	onChange={(
																		hov_ovl_bgImgcustomPosX
																	) =>
																		setAttributes(
																			{
																				[`hov_${controlName}ovl_bgImgcustomPosX`]:
																					hov_ovl_bgImgcustomPosX,
																			}
																		)
																	}
																/>
															</WithResButtons>

															<UnitControl
																selectedUnit={
																	hov_ovl_bgImgcustomPosYUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	hov_ovl_bgImgcustomPosYUnit
																) =>
																	setAttributes(
																		{
																			[`hov_${controlName}ovl_bgImgcustomPosYUnit`]:
																				hov_ovl_bgImgcustomPosYUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="Y Position">
																<RangeControl
																	value={
																		hov_ovl_bgImgcustomPosY
																	}
																	min={0}
																	max={
																		hov_ovl_bgImgcustomPosYUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		hov_ovl_bgImgcustomPosYUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		hov_ovl_bgImgcustomPosY
																	) =>
																		setAttributes(
																			{
																				[`hov_${controlName}ovl_bgImgcustomPosY`]:
																					hov_ovl_bgImgcustomPosY,
																			}
																		)
																	}
																/>
															</WithResButtons>
														</>
													)}

													<SelectControl
														label="Attachment"
														value={
															hov_ovl_bgImgAttachment
														}
														options={[
															{
																label: __(
																	"Default",
																	"essential-blocks"
																),
																value: "",
															},
															{
																label: __(
																	"Scroll",
																	"essential-blocks"
																),
																value: "scroll",
															},
															{
																label: __(
																	"Fixed",
																	"essential-blocks"
																),
																value: "fixed",
															},
														]}
														onChange={(
															hov_ovl_bgImgAttachment
														) =>
															setAttributes({
																[`hov_${controlName}ovl_bgImgAttachment`]:
																	hov_ovl_bgImgAttachment,
															})
														}
													/>

													{hov_ovl_bgImgAttachment ===
														"fixed" && (
														<p
															style={{
																marginTop:
																	"-10px",
																paddingBottom:
																	"10px",
															}}>
															<i>
																Note: Attachment
																Fixed works only
																on desktop.
															</i>
														</p>
													)}

													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Repeat">
														<SelectControl
															value={
																hov_ovl_bgImgRepeat
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"No-repeat",
																		"essential-blocks"
																	),
																	value: "no-repeat",
																},
																{
																	label: __(
																		"Repeat",
																		"essential-blocks"
																	),
																	value: "repeat",
																},
																{
																	label: __(
																		"Repeat-x",
																		"essential-blocks"
																	),
																	value: "repeat-x",
																},
																{
																	label: __(
																		"Repeat-y",
																		"essential-blocks"
																	),
																	value: "repeat-y",
																},
															]}
															onChange={(
																hov_ovl_bgImgRepeat
															) =>
																setAttributes({
																	[`hov_${controlName}ovl_bgImgRepeat`]:
																		hov_ovl_bgImgRepeat,
																})
															}
														/>
													</WithResButtons>

													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Size">
														<SelectControl
															value={
																hov_ovl_backgroundSize
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"Auto",
																		"essential-blocks"
																	),
																	value: "auto",
																},
																{
																	label: __(
																		"Cover",
																		"essential-blocks"
																	),
																	value: "cover",
																},
																{
																	label: __(
																		"Contain",
																		"essential-blocks"
																	),
																	value: "contain",
																},
																{
																	label: __(
																		"Custom",
																		"essential-blocks"
																	),
																	value: "custom",
																},
															]}
															onChange={(
																hov_ovl_backgroundSize
															) =>
																setAttributes({
																	[`hov_${controlName}ovl_backgroundSize`]:
																		hov_ovl_backgroundSize,
																})
															}
														/>
													</WithResButtons>

													{hov_ovl_backgroundSize ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	hov_ovl_bgImgCustomSizeUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	hov_ovl_bgImgCustomSizeUnit
																) =>
																	setAttributes(
																		{
																			[`hov_${controlName}ovl_bgImgCustomSizeUnit`]:
																				hov_ovl_bgImgCustomSizeUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="Width">
																<RangeControl
																	value={
																		hov_ovl_bgImgCustomSize
																	}
																	min={0}
																	max={
																		hov_ovl_bgImgCustomSizeUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		hov_ovl_bgImgCustomSizeUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		hov_ovl_bgImgCustomSize
																	) =>
																		setAttributes(
																			{
																				[`hov_${controlName}ovl_bgImgCustomSize`]:
																					hov_ovl_bgImgCustomSize,
																			}
																		)
																	}
																/>
															</WithResButtons>
														</>
													)}
												</>
											)}

											{resOption === "Tablet" && (
												<>
													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Position">
														<SelectControl
															value={
																hov_TABovl_bgImgPos
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"Center Center",
																		"essential-blocks"
																	),
																	value: "center center",
																},
																{
																	label: __(
																		"Center Left",
																		"essential-blocks"
																	),
																	value: "center left",
																},
																{
																	label: __(
																		"Center Right",
																		"essential-blocks"
																	),
																	value: "center right",
																},
																{
																	label: __(
																		"Top Center",
																		"essential-blocks"
																	),
																	value: "top center",
																},
																{
																	label: __(
																		"Top Left",
																		"essential-blocks"
																	),
																	value: "top left",
																},
																{
																	label: __(
																		"Top Right",
																		"essential-blocks"
																	),
																	value: "top right",
																},
																{
																	label: __(
																		"Bottom Center",
																		"essential-blocks"
																	),
																	value: "bottom center",
																},
																{
																	label: __(
																		"Bottom Left",
																		"essential-blocks"
																	),
																	value: "bottom left",
																},
																{
																	label: __(
																		"Bottom Right",
																		"essential-blocks"
																	),
																	value: "bottom right",
																},
																{
																	label: __(
																		"Custom",
																		"essential-blocks"
																	),
																	value: "custom",
																},
															]}
															onChange={(
																hov_TABovl_bgImgPos
															) =>
																setAttributes({
																	[`hov_TAB${controlName}ovl_bgImgPos`]:
																		hov_TABovl_bgImgPos,
																})
															}
														/>
													</WithResButtons>

													{hov_TABovl_bgImgPos ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	hov_TABovl_bgImgcustomPosXUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	hov_TABovl_bgImgcustomPosXUnit
																) =>
																	setAttributes(
																		{
																			[`hov_TAB${controlName}ovl_bgImgcustomPosXUnit`]:
																				hov_TABovl_bgImgcustomPosXUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="X Position">
																<RangeControl
																	value={
																		hov_TABovl_bgImgcustomPosX
																	}
																	min={0}
																	max={
																		hov_TABovl_bgImgcustomPosXUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	onChange={(
																		hov_TABovl_bgImgcustomPosX
																	) =>
																		setAttributes(
																			{
																				[`hov_TAB${controlName}ovl_bgImgcustomPosX`]:
																					hov_TABovl_bgImgcustomPosX,
																			}
																		)
																	}
																/>
															</WithResButtons>

															<UnitControl
																selectedUnit={
																	hov_TABovl_bgImgcustomPosYUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	hov_TABovl_bgImgcustomPosYUnit
																) =>
																	setAttributes(
																		{
																			[`hov_TAB${controlName}ovl_bgImgcustomPosYUnit`]:
																				hov_TABovl_bgImgcustomPosYUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="Y Position">
																<RangeControl
																	value={
																		hov_TABovl_bgImgcustomPosY
																	}
																	min={0}
																	max={
																		hov_TABovl_bgImgcustomPosYUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		hov_TABovl_bgImgcustomPosYUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		hov_TABovl_bgImgcustomPosY
																	) =>
																		setAttributes(
																			{
																				[`hov_TAB${controlName}ovl_bgImgcustomPosY`]:
																					hov_TABovl_bgImgcustomPosY,
																			}
																		)
																	}
																/>
															</WithResButtons>
														</>
													)}

													<SelectControl
														label="Attachment"
														value={
															hov_ovl_bgImgAttachment
														}
														options={[
															{
																label: __(
																	"Default",
																	"essential-blocks"
																),
																value: "",
															},
															{
																label: __(
																	"Scroll",
																	"essential-blocks"
																),
																value: "scroll",
															},
															{
																label: __(
																	"Fixed",
																	"essential-blocks"
																),
																value: "fixed",
															},
														]}
														onChange={(
															hov_ovl_bgImgAttachment
														) =>
															setAttributes({
																[`hov_${controlName}ovl_bgImgAttachment`]:
																	hov_ovl_bgImgAttachment,
															})
														}
													/>

													{hov_ovl_bgImgAttachment ===
														"fixed" && (
														<p
															style={{
																marginTop:
																	"-10px",
																paddingBottom:
																	"10px",
															}}>
															<i>
																Note: Attachment
																Fixed works only
																on desktop.
															</i>
														</p>
													)}

													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Repeat">
														<SelectControl
															value={
																hov_TABovl_bgImgRepeat
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"No-repeat",
																		"essential-blocks"
																	),
																	value: "no-repeat",
																},
																{
																	label: __(
																		"Repeat",
																		"essential-blocks"
																	),
																	value: "repeat",
																},
																{
																	label: __(
																		"Repeat-x",
																		"essential-blocks"
																	),
																	value: "repeat-x",
																},
																{
																	label: __(
																		"Repeat-y",
																		"essential-blocks"
																	),
																	value: "repeat-y",
																},
															]}
															onChange={(
																hov_TABovl_bgImgRepeat
															) =>
																setAttributes({
																	[`hov_TAB${controlName}ovl_bgImgRepeat`]:
																		hov_TABovl_bgImgRepeat,
																})
															}
														/>
													</WithResButtons>

													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Size">
														<SelectControl
															value={
																hov_TABovl_backgroundSize
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"Auto",
																		"essential-blocks"
																	),
																	value: "auto",
																},
																{
																	label: __(
																		"Cover",
																		"essential-blocks"
																	),
																	value: "cover",
																},
																{
																	label: __(
																		"Contain",
																		"essential-blocks"
																	),
																	value: "contain",
																},
																{
																	label: __(
																		"Custom",
																		"essential-blocks"
																	),
																	value: "custom",
																},
															]}
															onChange={(
																hov_TABovl_backgroundSize
															) =>
																setAttributes({
																	[`hov_TAB${controlName}ovl_backgroundSize`]:
																		hov_TABovl_backgroundSize,
																})
															}
														/>
													</WithResButtons>

													{hov_TABovl_backgroundSize ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	hov_TABovl_bgImgCustomSizeUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	hov_TABovl_bgImgCustomSizeUnit
																) =>
																	setAttributes(
																		{
																			[`hov_TAB${controlName}ovl_bgImgCustomSizeUnit`]:
																				hov_TABovl_bgImgCustomSizeUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="Width">
																<RangeControl
																	value={
																		hov_TABovl_bgImgCustomSize
																	}
																	min={0}
																	max={
																		hov_TABovl_bgImgCustomSizeUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		hov_TABovl_bgImgCustomSizeUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		hov_TABovl_bgImgCustomSize
																	) =>
																		setAttributes(
																			{
																				[`hov_TAB${controlName}ovl_bgImgCustomSize`]:
																					hov_TABovl_bgImgCustomSize,
																			}
																		)
																	}
																/>
															</WithResButtons>
														</>
													)}
												</>
											)}

											{resOption === "Mobile" && (
												<>
													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Position">
														<SelectControl
															value={
																hov_MOBovl_bgImgPos
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"Center Center",
																		"essential-blocks"
																	),
																	value: "center center",
																},
																{
																	label: __(
																		"Center Left",
																		"essential-blocks"
																	),
																	value: "center left",
																},
																{
																	label: __(
																		"Center Right",
																		"essential-blocks"
																	),
																	value: "center right",
																},
																{
																	label: __(
																		"Top Center",
																		"essential-blocks"
																	),
																	value: "top center",
																},
																{
																	label: __(
																		"Top Left",
																		"essential-blocks"
																	),
																	value: "top left",
																},
																{
																	label: __(
																		"Top Right",
																		"essential-blocks"
																	),
																	value: "top right",
																},
																{
																	label: __(
																		"Bottom Center",
																		"essential-blocks"
																	),
																	value: "bottom center",
																},
																{
																	label: __(
																		"Bottom Left",
																		"essential-blocks"
																	),
																	value: "bottom left",
																},
																{
																	label: __(
																		"Bottom Right",
																		"essential-blocks"
																	),
																	value: "bottom right",
																},
																{
																	label: __(
																		"Custom",
																		"essential-blocks"
																	),
																	value: "custom",
																},
															]}
															onChange={(
																hov_MOBovl_bgImgPos
															) =>
																setAttributes({
																	[`hov_MOB${controlName}ovl_bgImgPos`]:
																		hov_MOBovl_bgImgPos,
																})
															}
														/>
													</WithResButtons>

													{hov_MOBovl_bgImgPos ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	hov_MOBovl_bgImgcustomPosXUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	hov_MOBovl_bgImgcustomPosXUnit
																) =>
																	setAttributes(
																		{
																			[`hov_MOB${controlName}ovl_bgImgcustomPosXUnit`]:
																				hov_MOBovl_bgImgcustomPosXUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="X Position">
																<RangeControl
																	value={
																		hov_MOBovl_bgImgcustomPosX
																	}
																	min={0}
																	max={
																		hov_MOBovl_bgImgcustomPosXUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	onChange={(
																		hov_MOBovl_bgImgcustomPosX
																	) =>
																		setAttributes(
																			{
																				[`hov_MOB${controlName}ovl_bgImgcustomPosX`]:
																					hov_MOBovl_bgImgcustomPosX,
																			}
																		)
																	}
																/>
															</WithResButtons>

															<UnitControl
																selectedUnit={
																	hov_MOBovl_bgImgcustomPosYUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	hov_MOBovl_bgImgcustomPosYUnit
																) =>
																	setAttributes(
																		{
																			[`hov_MOB${controlName}ovl_bgImgcustomPosYUnit`]:
																				hov_MOBovl_bgImgcustomPosYUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="Y Position">
																<RangeControl
																	value={
																		hov_MOBovl_bgImgcustomPosY
																	}
																	min={0}
																	max={
																		hov_MOBovl_bgImgcustomPosYUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		hov_MOBovl_bgImgcustomPosYUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		hov_MOBovl_bgImgcustomPosY
																	) =>
																		setAttributes(
																			{
																				[`hov_MOB${controlName}ovl_bgImgcustomPosY`]:
																					hov_MOBovl_bgImgcustomPosY,
																			}
																		)
																	}
																/>
															</WithResButtons>
														</>
													)}

													<SelectControl
														label="Attachment"
														value={
															hov_ovl_bgImgAttachment
														}
														options={[
															{
																label: __(
																	"Default",
																	"essential-blocks"
																),
																value: "",
															},
															{
																label: __(
																	"Scroll",
																	"essential-blocks"
																),
																value: "scroll",
															},
															{
																label: __(
																	"Fixed",
																	"essential-blocks"
																),
																value: "fixed",
															},
														]}
														onChange={(
															hov_ovl_bgImgAttachment
														) =>
															setAttributes({
																[`hov_${controlName}ovl_bgImgAttachment`]:
																	hov_ovl_bgImgAttachment,
															})
														}
													/>

													{hov_ovl_bgImgAttachment ===
														"fixed" && (
														<p
															style={{
																marginTop:
																	"-10px",
																paddingBottom:
																	"10px",
															}}>
															<i>
																Note: Attachment
																Fixed works only
																on desktop.
															</i>
														</p>
													)}

													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Repeat">
														<SelectControl
															value={
																hov_MOBovl_bgImgRepeat
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"No-repeat",
																		"essential-blocks"
																	),
																	value: "no-repeat",
																},
																{
																	label: __(
																		"Repeat",
																		"essential-blocks"
																	),
																	value: "repeat",
																},
																{
																	label: __(
																		"Repeat-x",
																		"essential-blocks"
																	),
																	value: "repeat-x",
																},
																{
																	label: __(
																		"Repeat-y",
																		"essential-blocks"
																	),
																	value: "repeat-y",
																},
															]}
															onChange={(
																hov_MOBovl_bgImgRepeat
															) =>
																setAttributes({
																	[`hov_MOB${controlName}ovl_bgImgRepeat`]:
																		hov_MOBovl_bgImgRepeat,
																})
															}
														/>
													</WithResButtons>

													<WithResButtons
														resRequiredProps={
															resRequiredProps
														}
														label="Size">
														<SelectControl
															value={
																hov_MOBovl_backgroundSize
															}
															options={[
																{
																	label: __(
																		"Default",
																		"essential-blocks"
																	),
																	value: "",
																},
																{
																	label: __(
																		"Auto",
																		"essential-blocks"
																	),
																	value: "auto",
																},
																{
																	label: __(
																		"Cover",
																		"essential-blocks"
																	),
																	value: "cover",
																},
																{
																	label: __(
																		"Contain",
																		"essential-blocks"
																	),
																	value: "contain",
																},
																{
																	label: __(
																		"Custom",
																		"essential-blocks"
																	),
																	value: "custom",
																},
															]}
															onChange={(
																hov_MOBovl_backgroundSize
															) =>
																setAttributes({
																	[`hov_MOB${controlName}ovl_backgroundSize`]:
																		hov_MOBovl_backgroundSize,
																})
															}
														/>
													</WithResButtons>

													{hov_MOBovl_backgroundSize ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	hov_MOBovl_bgImgCustomSizeUnit
																}
																unitTypes={[
																	{
																		label: "px",
																		value: "px",
																	},
																	{
																		label: "em",
																		value: "em",
																	},
																	{
																		label: "%",
																		value: "%",
																	},
																]}
																onClick={(
																	hov_MOBovl_bgImgCustomSizeUnit
																) =>
																	setAttributes(
																		{
																			[`hov_MOB${controlName}ovl_bgImgCustomSizeUnit`]:
																				hov_MOBovl_bgImgCustomSizeUnit,
																		}
																	)
																}
															/>

															<WithResButtons
																resRequiredProps={
																	resRequiredProps
																}
																label="Width">
																<RangeControl
																	value={
																		hov_MOBovl_bgImgCustomSize
																	}
																	min={0}
																	max={
																		hov_MOBovl_bgImgCustomSizeUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		hov_MOBovl_bgImgCustomSizeUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		hov_MOBovl_bgImgCustomSize
																	) =>
																		setAttributes(
																			{
																				[`hov_MOB${controlName}ovl_bgImgCustomSize`]:
																					hov_MOBovl_bgImgCustomSize,
																			}
																		)
																	}
																/>
															</WithResButtons>
														</>
													)}
												</>
											)}
										</>
									)}
								</>
							)}
						</>
					)}

					{hov_overlayType === "gradient" && (
						<GradientColorControl
							gradientColor={hov_overlayGradient}
							onChange={(hov_overlayGradient) =>
								setAttributes({
									[`hov_${controlName}overlayGradient`]:
										hov_overlayGradient,
								})
							}
						/>
					)}

					<hr />

					{!noTransition && (
						<RangeControl
							label={__("Overlay Transition", "essential-blocks")}
							value={ovl_bg_transition}
							min={0}
							max={5}
							step={0.1}
							onChange={(ovl_bg_transition) =>
								setAttributes({
									[`${controlName}ovl_bg_transition`]:
										ovl_bg_transition,
								})
							}
						/>
					)}

					<hr />

					<ResetControl
						onReset={() =>
							setAttributes({
								[`hov_${controlName}ovl_opacity`]: undefined,
							})
						}>
						<RangeControl
							label={__("Opacity", "essential-blocks")}
							value={hov_ovl_opacity}
							onChange={(hov_ovl_opacity) =>
								setAttributes({
									[`hov_${controlName}ovl_opacity`]:
										hov_ovl_opacity,
								})
							}
							step={0.01}
							min={0}
							max={1}
						/>
					</ResetControl>

					{!noTransition && hov_ovl_opacity && (
						<RangeControl
							label={__("Opacity Transition", "essential-blocks")}
							value={ovl_opacityTransition}
							onChange={(ovl_opacityTransition) =>
								setAttributes({
									[`${controlName}ovl_opacityTransition`]:
										ovl_opacityTransition,
								})
							}
							step={0.01}
							min={0}
							max={5}
						/>
					)}

					<hr />

					<SelectControl
						label={__("Blend Mode", "essential-blocks")}
						value={hov_ovl_blendMode}
						options={[
							{
								label: __("Normal", "essential-blocks"),
								value: "",
							},
							{
								label: __("multiply", "essential-blocks"),
								value: "multiply",
							},
							{
								label: __("screen", "essential-blocks"),
								value: "screen",
							},
							{
								label: __("overlay", "essential-blocks"),
								value: "overlay",
							},
							{
								label: __("darken", "essential-blocks"),
								value: "darken",
							},
							{
								label: __("lighten", "essential-blocks"),
								value: "lighten",
							},
							{
								label: __("color-dodge", "essential-blocks"),
								value: "Color Dodge",
							},
							{
								label: __("saturation", "essential-blocks"),
								value: "saturation",
							},
							{
								label: __("color", "essential-blocks"),
								value: "color",
							},
							{
								label: __("luminosity", "essential-blocks"),
								value: "luminosity",
							},
						]}
						onChange={(hov_ovl_blendMode) =>
							setAttributes({
								[`hov_${controlName}ovl_blendMode`]:
									hov_ovl_blendMode,
							})
						}
					/>

					<ToggleControl
						label={__("CSS Filters", "essential-blocks")}
						checked={hov_ovl_allowFilters}
						onChange={() =>
							setAttributes({
								[`hov_${controlName}ovl_allowFilters`]:
									!hov_ovl_allowFilters,
							})
						}
					/>

					{hov_ovl_allowFilters && (
						<>
							<RangeControl
								label={__("Blur", "essential-blocks")}
								value={hov_ovl_fltrBlur}
								onChange={(hov_ovl_fltrBlur) =>
									setAttributes({
										[`hov_${controlName}ovl_fltrBlur`]:
											hov_ovl_fltrBlur,
									})
								}
								step={0.1}
								min={0}
								max={10}
							/>
							<RangeControl
								label={__("Brightness", "essential-blocks")}
								value={hov_ovl_fltrBrightness}
								onChange={(hov_ovl_fltrBrightness) =>
									setAttributes({
										[`hov_${controlName}ovl_fltrBrightness`]:
											hov_ovl_fltrBrightness,
									})
								}
								step={1}
								min={0}
								max={200}
							/>
							<RangeControl
								label={__("Contrast", "essential-blocks")}
								value={hov_ovl_fltrContrast}
								onChange={(hov_ovl_fltrContrast) =>
									setAttributes({
										[`hov_${controlName}ovl_fltrContrast`]:
											hov_ovl_fltrContrast,
									})
								}
								step={1}
								min={0}
								max={200}
							/>
							<RangeControl
								label={__("Saturation", "essential-blocks")}
								value={hov_ovl_fltrSaturation}
								onChange={(hov_ovl_fltrSaturation) =>
									setAttributes({
										[`hov_${controlName}ovl_fltrSaturation`]:
											hov_ovl_fltrSaturation,
									})
								}
								step={1}
								min={0}
								max={200}
							/>
							<RangeControl
								label={__("Hue", "essential-blocks")}
								value={hov_ovl_fltrHue}
								onChange={(hov_ovl_fltrHue) =>
									setAttributes({
										[`hov_${controlName}ovl_fltrHue`]:
											hov_ovl_fltrHue,
									})
								}
								step={1}
								min={0}
								max={360}
							/>

							<hr />

							{!noTransition && (
								<RangeControl
									label={__(
										"Css Filters Transition",
										"essential-blocks"
									)}
									value={ovl_filtersTransition}
									onChange={(ovl_filtersTransition) =>
										setAttributes({
											[`${controlName}ovl_filtersTransition`]:
												ovl_filtersTransition,
										})
									}
									step={0.01}
									min={0}
									max={5}
								/>
							)}
						</>
					)}
				</>
			)}
		</>
	);
}
