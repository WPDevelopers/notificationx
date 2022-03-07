import { __ } from "@wordpress/i18n";
import { MediaUpload } from "@wordpress/block-editor";
import {
	SelectControl,
	Button,
	RangeControl,
	BaseControl,
	ButtonGroup,
} from "@wordpress/components";

/**
 * Internal dependencies
 */
import GradientColorControl from "../gradient-color-controller";
import UnitControl from "../unit-control";
import ColorControl from "../color-control";
import ImageAvatar from "../image-avatar";
import WithResButtons from "../withResButtons";

export default function bgControl({
	resRequiredProps,
	controlName,
	noMainBgi,
	noTransition,
}) {
	const { setAttributes, attributes, resOption } = resRequiredProps;

	const {
		[`${controlName}bg_hoverType`]: bg_hoverType,
		[`${controlName}bg_transition`]: bg_transition,

		//  attributes for bg_hoverType normal start  ⬇
		[`${controlName}backgroundType`]: backgroundType,
		[`${controlName}backgroundColor`]: backgroundColor,
		[`${controlName}gradientColor`]: gradientColor,
		[`${controlName}bgImageURL`]: bgImageURL,
		[`${controlName}bgImageID`]: bgImageID,
		[`${controlName}bgImgAttachment`]: bgImgAttachment,

		[`${controlName}backgroundSize`]: backgroundSize,
		[`${controlName}bgImgCustomSize`]: bgImgCustomSize,
		[`${controlName}bgImgCustomSizeUnit`]: bgImgCustomSizeUnit,
		[`${controlName}bgImgPos`]: bgImgPos,
		[`${controlName}bgImgcustomPosX`]: bgImgcustomPosX,
		[`${controlName}bgImgcustomPosXUnit`]: bgImgcustomPosXUnit,
		[`${controlName}bgImgcustomPosY`]: bgImgcustomPosY,
		[`${controlName}bgImgcustomPosYUnit`]: bgImgcustomPosYUnit,
		[`${controlName}bgImgRepeat`]: bgImgRepeat,

		[`TAB${controlName}backgroundSize`]: TABbackgroundSize,
		[`TAB${controlName}bgImgCustomSize`]: TABbgImgCustomSize,
		[`TAB${controlName}bgImgCustomSizeUnit`]: TABbgImgCustomSizeUnit,
		[`TAB${controlName}bgImgPos`]: TABbgImgPos,
		[`TAB${controlName}bgImgcustomPosX`]: TABbgImgcustomPosX,
		[`TAB${controlName}bgImgcustomPosXUnit`]: TABbgImgcustomPosXUnit,
		[`TAB${controlName}bgImgcustomPosY`]: TABbgImgcustomPosY,
		[`TAB${controlName}bgImgcustomPosYUnit`]: TABbgImgcustomPosYUnit,
		[`TAB${controlName}bgImgRepeat`]: TABbgImgRepeat,

		[`MOB${controlName}backgroundSize`]: MOBbackgroundSize,
		[`MOB${controlName}bgImgCustomSize`]: MOBbgImgCustomSize,
		[`MOB${controlName}bgImgCustomSizeUnit`]: MOBbgImgCustomSizeUnit,
		[`MOB${controlName}bgImgPos`]: MOBbgImgPos,
		[`MOB${controlName}bgImgcustomPosX`]: MOBbgImgcustomPosX,
		[`MOB${controlName}bgImgcustomPosXUnit`]: MOBbgImgcustomPosXUnit,
		[`MOB${controlName}bgImgcustomPosY`]: MOBbgImgcustomPosY,
		[`MOB${controlName}bgImgcustomPosYUnit`]: MOBbgImgcustomPosYUnit,
		[`MOB${controlName}bgImgRepeat`]: MOBbgImgRepeat,
		//  attributes for bg_hoverType normal end

		//  attributes for bg_hoverType hover start  ⬇
		[`hov_${controlName}backgroundType`]: hov_backgroundType,
		[`hov_${controlName}backgroundColor`]: hov_backgroundColor,
		[`hov_${controlName}gradientColor`]: hov_gradientColor,
		[`hov_${controlName}bgImageURL`]: hov_bgImageURL,
		[`hov_${controlName}bgImageID`]: hov_bgImageID,
		[`hov_${controlName}bgImgAttachment`]: hov_bgImgAttachment,

		[`hov_${controlName}backgroundSize`]: hov_backgroundSize,
		[`hov_${controlName}bgImgCustomSize`]: hov_bgImgCustomSize,
		[`hov_${controlName}bgImgCustomSizeUnit`]: hov_bgImgCustomSizeUnit,
		[`hov_${controlName}bgImgPos`]: hov_bgImgPos,
		[`hov_${controlName}bgImgcustomPosX`]: hov_bgImgcustomPosX,
		[`hov_${controlName}bgImgcustomPosXUnit`]: hov_bgImgcustomPosXUnit,
		[`hov_${controlName}bgImgcustomPosY`]: hov_bgImgcustomPosY,
		[`hov_${controlName}bgImgcustomPosYUnit`]: hov_bgImgcustomPosYUnit,
		[`hov_${controlName}bgImgRepeat`]: hov_bgImgRepeat,

		[`hov_TAB${controlName}backgroundSize`]: hov_TABbackgroundSize,
		[`hov_TAB${controlName}bgImgCustomSize`]: hov_TABbgImgCustomSize,
		[`hov_TAB${controlName}bgImgCustomSizeUnit`]:
			hov_TABbgImgCustomSizeUnit,
		[`hov_TAB${controlName}bgImgPos`]: hov_TABbgImgPos,
		[`hov_TAB${controlName}bgImgcustomPosX`]: hov_TABbgImgcustomPosX,
		[`hov_TAB${controlName}bgImgcustomPosXUnit`]:
			hov_TABbgImgcustomPosXUnit,
		[`hov_TAB${controlName}bgImgcustomPosY`]: hov_TABbgImgcustomPosY,
		[`hov_TAB${controlName}bgImgcustomPosYUnit`]:
			hov_TABbgImgcustomPosYUnit,
		[`hov_TAB${controlName}bgImgRepeat`]: hov_TABbgImgRepeat,

		[`hov_MOB${controlName}backgroundSize`]: hov_MOBbackgroundSize,
		[`hov_MOB${controlName}bgImgCustomSize`]: hov_MOBbgImgCustomSize,
		[`hov_MOB${controlName}bgImgCustomSizeUnit`]:
			hov_MOBbgImgCustomSizeUnit,
		[`hov_MOB${controlName}bgImgPos`]: hov_MOBbgImgPos,
		[`hov_MOB${controlName}bgImgcustomPosX`]: hov_MOBbgImgcustomPosX,
		[`hov_MOB${controlName}bgImgcustomPosXUnit`]:
			hov_MOBbgImgcustomPosXUnit,
		[`hov_MOB${controlName}bgImgcustomPosY`]: hov_MOBbgImgcustomPosY,
		[`hov_MOB${controlName}bgImgcustomPosYUnit`]:
			hov_MOBbgImgcustomPosYUnit,
		[`hov_MOB${controlName}bgImgRepeat`]: hov_MOBbgImgRepeat,
		//  attributes for bg_hoverType hover end
	} = attributes;

	return (
		<>
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
							isPrimary={bg_hoverType === value}
							isSecondary={bg_hoverType !== value}
							onClick={() =>
								setAttributes({
									[`${controlName}bg_hoverType`]: value,
								})
							}>
							{label}
						</Button>
					))}
				</ButtonGroup>
			</BaseControl>

			{bg_hoverType === "normal" && (
				<>
					<BaseControl
						label={__("Background Type", "essential-blocks")}>
						<ButtonGroup>
							{[
								{
									label: __("Classic", "essential-blocks"),
									value: "classic",
								},
								{
									label: __("Gradient", "essential-blocks"),
									value: "gradient",
								},
							].map(({ value, label }) => (
								<Button
									// isSmall
									// isLarge
									isPrimary={backgroundType === value}
									isSecondary={backgroundType !== value}
									onClick={() =>
										setAttributes({
											[`${controlName}backgroundType`]:
												value,
										})
									}>
									{label}
								</Button>
							))}
						</ButtonGroup>
					</BaseControl>

					{backgroundType === "classic" && (
						<>
							<ColorControl
								label={__(
									"Background Color",
									"essential-blocks"
								)}
								color={backgroundColor}
								onChange={(backgroundColor) =>
									setAttributes({
										[`${controlName}backgroundColor`]:
											backgroundColor,
									})
								}
							/>

							{noMainBgi === false && (
								<>
									<BaseControl
										label={__(
											"Background Image",
											"essential-blocks"
										)}></BaseControl>

									<MediaUpload
										onSelect={({ url, id }) =>
											setAttributes({
												[`${controlName}bgImageURL`]:
													url,
												[`${controlName}bgImageID`]: id,
											})
										}
										type="image"
										value={bgImageID}
										render={({ open }) =>
											!bgImageURL && (
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

									{bgImageURL && (
										<>
											<ImageAvatar
												imageUrl={bgImageURL}
												onDeleteImage={() =>
													setAttributes({
														[`${controlName}bgImageURL`]:
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
															value={bgImgPos}
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
																bgImgPos
															) =>
																setAttributes({
																	[`${controlName}bgImgPos`]:
																		bgImgPos,
																})
															}
														/>
													</WithResButtons>

													{bgImgPos === "custom" && (
														<>
															<UnitControl
																selectedUnit={
																	bgImgcustomPosXUnit
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
																	bgImgcustomPosXUnit
																) =>
																	setAttributes(
																		{
																			[`${controlName}bgImgcustomPosXUnit`]:
																				bgImgcustomPosXUnit,
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
																		bgImgcustomPosX
																	}
																	min={-2000}
																	max={
																		// bgImgcustomPosXUnit === "px" ?
																		2000
																		//  : 100
																	}
																	onChange={(
																		bgImgcustomPosX
																	) =>
																		setAttributes(
																			{
																				[`${controlName}bgImgcustomPosX`]:
																					bgImgcustomPosX,
																			}
																		)
																	}
																/>
															</WithResButtons>

															<UnitControl
																selectedUnit={
																	bgImgcustomPosYUnit
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
																	bgImgcustomPosYUnit
																) =>
																	setAttributes(
																		{
																			[`${controlName}bgImgcustomPosYUnit`]:
																				bgImgcustomPosYUnit,
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
																		bgImgcustomPosY
																	}
																	min={-2000}
																	max={
																		// bgImgcustomPosYUnit === "px" ?
																		2000
																		// : 100
																	}
																	step={
																		bgImgcustomPosYUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		bgImgcustomPosY
																	) =>
																		setAttributes(
																			{
																				[`${controlName}bgImgcustomPosY`]:
																					bgImgcustomPosY,
																			}
																		)
																	}
																/>
															</WithResButtons>
														</>
													)}

													<SelectControl
														label="Attachment"
														value={bgImgAttachment}
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
															bgImgAttachment
														) =>
															setAttributes({
																[`${controlName}bgImgAttachment`]:
																	bgImgAttachment,
															})
														}
													/>

													{bgImgAttachment ===
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
															value={bgImgRepeat}
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
																bgImgRepeat
															) =>
																setAttributes({
																	[`${controlName}bgImgRepeat`]:
																		bgImgRepeat,
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
																backgroundSize
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
																backgroundSize
															) =>
																setAttributes({
																	[`${controlName}backgroundSize`]:
																		backgroundSize,
																})
															}
														/>
													</WithResButtons>

													{backgroundSize ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	bgImgCustomSizeUnit
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
																	bgImgCustomSizeUnit
																) =>
																	setAttributes(
																		{
																			[`${controlName}bgImgCustomSizeUnit`]:
																				bgImgCustomSizeUnit,
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
																		bgImgCustomSize
																	}
																	min={0}
																	max={
																		bgImgCustomSizeUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		bgImgCustomSizeUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		bgImgCustomSize
																	) =>
																		setAttributes(
																			{
																				[`${controlName}bgImgCustomSize`]:
																					bgImgCustomSize,
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
															value={TABbgImgPos}
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
																TABbgImgPos
															) =>
																setAttributes({
																	[`TAB${controlName}bgImgPos`]:
																		TABbgImgPos,
																})
															}
														/>
													</WithResButtons>

													{TABbgImgPos ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	TABbgImgcustomPosXUnit
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
																	TABbgImgcustomPosXUnit
																) =>
																	setAttributes(
																		{
																			[`TAB${controlName}bgImgcustomPosXUnit`]:
																				TABbgImgcustomPosXUnit,
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
																		TABbgImgcustomPosX
																	}
																	min={0}
																	max={
																		TABbgImgcustomPosXUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	onChange={(
																		TABbgImgcustomPosX
																	) =>
																		setAttributes(
																			{
																				[`TAB${controlName}bgImgcustomPosX`]:
																					TABbgImgcustomPosX,
																			}
																		)
																	}
																/>
															</WithResButtons>

															<UnitControl
																selectedUnit={
																	TABbgImgcustomPosYUnit
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
																	TABbgImgcustomPosYUnit
																) =>
																	setAttributes(
																		{
																			[`TAB${controlName}bgImgcustomPosYUnit`]:
																				TABbgImgcustomPosYUnit,
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
																		TABbgImgcustomPosY
																	}
																	min={0}
																	max={
																		TABbgImgcustomPosYUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		TABbgImgcustomPosYUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		TABbgImgcustomPosY
																	) =>
																		setAttributes(
																			{
																				[`TAB${controlName}bgImgcustomPosY`]:
																					TABbgImgcustomPosY,
																			}
																		)
																	}
																/>
															</WithResButtons>
														</>
													)}

													<SelectControl
														label="Attachment"
														value={bgImgAttachment}
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
															bgImgAttachment
														) =>
															setAttributes({
																[`${controlName}bgImgAttachment`]:
																	bgImgAttachment,
															})
														}
													/>

													{bgImgAttachment ===
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
																TABbgImgRepeat
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
																TABbgImgRepeat
															) =>
																setAttributes({
																	[`TAB${controlName}bgImgRepeat`]:
																		TABbgImgRepeat,
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
																TABbackgroundSize
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
																TABbackgroundSize
															) =>
																setAttributes({
																	[`TAB${controlName}backgroundSize`]:
																		TABbackgroundSize,
																})
															}
														/>
													</WithResButtons>

													{TABbackgroundSize ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	TABbgImgCustomSizeUnit
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
																	TABbgImgCustomSizeUnit
																) =>
																	setAttributes(
																		{
																			[`TAB${controlName}bgImgCustomSizeUnit`]:
																				TABbgImgCustomSizeUnit,
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
																		TABbgImgCustomSize
																	}
																	min={0}
																	max={
																		TABbgImgCustomSizeUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		TABbgImgCustomSizeUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		TABbgImgCustomSize
																	) =>
																		setAttributes(
																			{
																				[`TAB${controlName}bgImgCustomSize`]:
																					TABbgImgCustomSize,
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
															value={MOBbgImgPos}
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
																MOBbgImgPos
															) =>
																setAttributes({
																	[`MOB${controlName}bgImgPos`]:
																		MOBbgImgPos,
																})
															}
														/>
													</WithResButtons>

													{MOBbgImgPos ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	MOBbgImgcustomPosXUnit
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
																	MOBbgImgcustomPosXUnit
																) =>
																	setAttributes(
																		{
																			[`MOB${controlName}bgImgcustomPosXUnit`]:
																				MOBbgImgcustomPosXUnit,
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
																		MOBbgImgcustomPosX
																	}
																	min={0}
																	max={
																		MOBbgImgcustomPosXUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	onChange={(
																		MOBbgImgcustomPosX
																	) =>
																		setAttributes(
																			{
																				[`MOB${controlName}bgImgcustomPosX`]:
																					MOBbgImgcustomPosX,
																			}
																		)
																	}
																/>
															</WithResButtons>

															<UnitControl
																selectedUnit={
																	MOBbgImgcustomPosYUnit
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
																	MOBbgImgcustomPosYUnit
																) =>
																	setAttributes(
																		{
																			[`MOB${controlName}bgImgcustomPosYUnit`]:
																				MOBbgImgcustomPosYUnit,
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
																		MOBbgImgcustomPosY
																	}
																	min={0}
																	max={
																		MOBbgImgcustomPosYUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		MOBbgImgcustomPosYUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		MOBbgImgcustomPosY
																	) =>
																		setAttributes(
																			{
																				[`MOB${controlName}bgImgcustomPosY`]:
																					MOBbgImgcustomPosY,
																			}
																		)
																	}
																/>
															</WithResButtons>
														</>
													)}

													<SelectControl
														label="Attachment"
														value={bgImgAttachment}
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
															bgImgAttachment
														) =>
															setAttributes({
																[`${controlName}bgImgAttachment`]:
																	bgImgAttachment,
															})
														}
													/>

													{bgImgAttachment ===
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
																MOBbgImgRepeat
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
																MOBbgImgRepeat
															) =>
																setAttributes({
																	[`MOB${controlName}bgImgRepeat`]:
																		MOBbgImgRepeat,
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
																MOBbackgroundSize
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
																MOBbackgroundSize
															) =>
																setAttributes({
																	[`MOB${controlName}backgroundSize`]:
																		MOBbackgroundSize,
																})
															}
														/>
													</WithResButtons>

													{MOBbackgroundSize ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	MOBbgImgCustomSizeUnit
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
																	MOBbgImgCustomSizeUnit
																) =>
																	setAttributes(
																		{
																			[`MOB${controlName}bgImgCustomSizeUnit`]:
																				MOBbgImgCustomSizeUnit,
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
																		MOBbgImgCustomSize
																	}
																	min={0}
																	max={
																		MOBbgImgCustomSizeUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		MOBbgImgCustomSizeUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		MOBbgImgCustomSize
																	) =>
																		setAttributes(
																			{
																				[`MOB${controlName}bgImgCustomSize`]:
																					MOBbgImgCustomSize,
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

					{backgroundType === "gradient" && (
						<GradientColorControl
							gradientColor={gradientColor}
							onChange={(gradientColor) =>
								setAttributes({
									[`${controlName}gradientColor`]:
										gradientColor,
								})
							}
						/>
					)}
				</>
			)}

			{bg_hoverType === "hover" && (
				<>
					<BaseControl
						label={__("Background Type", "essential-blocks")}>
						<ButtonGroup>
							{[
								{
									label: __("Classic", "essential-blocks"),
									value: "classic",
								},
								{
									label: __("Gradient", "essential-blocks"),
									value: "gradient",
								},
							].map(({ value, label }) => (
								<Button
									// isSmall
									// isLarge
									isPrimary={hov_backgroundType === value}
									isSecondary={hov_backgroundType !== value}
									onClick={() =>
										setAttributes({
											[`hov_${controlName}backgroundType`]:
												value,
										})
									}>
									{label}
								</Button>
							))}
						</ButtonGroup>
					</BaseControl>

					{hov_backgroundType === "classic" && (
						<>
							<ColorControl
								label={__(
									"Background Color",
									"essential-blocks"
								)}
								color={hov_backgroundColor}
								onChange={(hov_backgroundColor) =>
									setAttributes({
										[`hov_${controlName}backgroundColor`]:
											hov_backgroundColor,
									})
								}
							/>

							{noMainBgi === false && (
								<>
									<BaseControl
										label={__(
											"Background Image",
											"essential-blocks"
										)}></BaseControl>

									<MediaUpload
										onSelect={({ url, id }) =>
											setAttributes({
												[`hov_${controlName}bgImageURL`]:
													url,
												[`hov_${controlName}bgImageID`]:
													id,
											})
										}
										type="image"
										value={hov_bgImageID}
										render={({ open }) =>
											!hov_bgImageURL && (
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

									{hov_bgImageURL && (
										<>
											<ImageAvatar
												imageUrl={hov_bgImageURL}
												onDeleteImage={() =>
													setAttributes({
														[`hov_${controlName}bgImageURL`]:
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
															value={hov_bgImgPos}
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
																hov_bgImgPos
															) =>
																setAttributes({
																	[`hov_${controlName}bgImgPos`]:
																		hov_bgImgPos,
																})
															}
														/>
													</WithResButtons>

													{hov_bgImgPos ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	hov_bgImgcustomPosXUnit
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
																	hov_bgImgcustomPosXUnit
																) =>
																	setAttributes(
																		{
																			[`hov_${controlName}bgImgcustomPosXUnit`]:
																				hov_bgImgcustomPosXUnit,
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
																		hov_bgImgcustomPosX
																	}
																	min={0}
																	max={
																		hov_bgImgcustomPosXUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	onChange={(
																		hov_bgImgcustomPosX
																	) =>
																		setAttributes(
																			{
																				[`hov_${controlName}bgImgcustomPosX`]:
																					hov_bgImgcustomPosX,
																			}
																		)
																	}
																/>
															</WithResButtons>

															<UnitControl
																selectedUnit={
																	hov_bgImgcustomPosYUnit
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
																	hov_bgImgcustomPosYUnit
																) =>
																	setAttributes(
																		{
																			[`hov_${controlName}bgImgcustomPosYUnit`]:
																				hov_bgImgcustomPosYUnit,
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
																		hov_bgImgcustomPosY
																	}
																	min={0}
																	max={
																		hov_bgImgcustomPosYUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		hov_bgImgcustomPosYUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		hov_bgImgcustomPosY
																	) =>
																		setAttributes(
																			{
																				[`hov_${controlName}bgImgcustomPosY`]:
																					hov_bgImgcustomPosY,
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
															hov_bgImgAttachment
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
															hov_bgImgAttachment
														) =>
															setAttributes({
																[`hov_${controlName}bgImgAttachment`]:
																	hov_bgImgAttachment,
															})
														}
													/>

													{hov_bgImgAttachment ===
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
																hov_bgImgRepeat
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
																hov_bgImgRepeat
															) =>
																setAttributes({
																	[`hov_${controlName}bgImgRepeat`]:
																		hov_bgImgRepeat,
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
																hov_backgroundSize
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
																hov_backgroundSize
															) =>
																setAttributes({
																	[`hov_${controlName}backgroundSize`]:
																		hov_backgroundSize,
																})
															}
														/>
													</WithResButtons>

													{hov_backgroundSize ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	hov_bgImgCustomSizeUnit
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
																	hov_bgImgCustomSizeUnit
																) =>
																	setAttributes(
																		{
																			[`hov_${controlName}bgImgCustomSizeUnit`]:
																				hov_bgImgCustomSizeUnit,
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
																		hov_bgImgCustomSize
																	}
																	min={0}
																	max={
																		hov_bgImgCustomSizeUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		hov_bgImgCustomSizeUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		hov_bgImgCustomSize
																	) =>
																		setAttributes(
																			{
																				[`hov_${controlName}bgImgCustomSize`]:
																					hov_bgImgCustomSize,
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
																hov_TABbgImgPos
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
																hov_TABbgImgPos
															) =>
																setAttributes({
																	[`hov_TAB${controlName}bgImgPos`]:
																		hov_TABbgImgPos,
																})
															}
														/>
													</WithResButtons>

													{hov_TABbgImgPos ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	hov_TABbgImgcustomPosXUnit
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
																	hov_TABbgImgcustomPosXUnit
																) =>
																	setAttributes(
																		{
																			[`hov_TAB${controlName}bgImgcustomPosXUnit`]:
																				hov_TABbgImgcustomPosXUnit,
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
																		hov_TABbgImgcustomPosX
																	}
																	min={0}
																	max={
																		hov_TABbgImgcustomPosXUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	onChange={(
																		hov_TABbgImgcustomPosX
																	) =>
																		setAttributes(
																			{
																				[`hov_TAB${controlName}bgImgcustomPosX`]:
																					hov_TABbgImgcustomPosX,
																			}
																		)
																	}
																/>
															</WithResButtons>

															<UnitControl
																selectedUnit={
																	hov_TABbgImgcustomPosYUnit
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
																	hov_TABbgImgcustomPosYUnit
																) =>
																	setAttributes(
																		{
																			[`hov_TAB${controlName}bgImgcustomPosYUnit`]:
																				hov_TABbgImgcustomPosYUnit,
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
																		hov_TABbgImgcustomPosY
																	}
																	min={0}
																	max={
																		hov_TABbgImgcustomPosYUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		hov_TABbgImgcustomPosYUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		hov_TABbgImgcustomPosY
																	) =>
																		setAttributes(
																			{
																				[`hov_TAB${controlName}bgImgcustomPosY`]:
																					hov_TABbgImgcustomPosY,
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
															hov_bgImgAttachment
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
															hov_bgImgAttachment
														) =>
															setAttributes({
																[`hov_${controlName}bgImgAttachment`]:
																	hov_bgImgAttachment,
															})
														}
													/>

													{hov_bgImgAttachment ===
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
																hov_TABbgImgRepeat
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
																hov_TABbgImgRepeat
															) =>
																setAttributes({
																	[`hov_TAB${controlName}bgImgRepeat`]:
																		hov_TABbgImgRepeat,
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
																hov_TABbackgroundSize
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
																hov_TABbackgroundSize
															) =>
																setAttributes({
																	[`hov_TAB${controlName}backgroundSize`]:
																		hov_TABbackgroundSize,
																})
															}
														/>
													</WithResButtons>

													{hov_TABbackgroundSize ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	hov_TABbgImgCustomSizeUnit
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
																	hov_TABbgImgCustomSizeUnit
																) =>
																	setAttributes(
																		{
																			[`hov_TAB${controlName}bgImgCustomSizeUnit`]:
																				hov_TABbgImgCustomSizeUnit,
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
																		hov_TABbgImgCustomSize
																	}
																	min={0}
																	max={
																		hov_TABbgImgCustomSizeUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		hov_TABbgImgCustomSizeUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		hov_TABbgImgCustomSize
																	) =>
																		setAttributes(
																			{
																				[`hov_TAB${controlName}bgImgCustomSize`]:
																					hov_TABbgImgCustomSize,
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
																hov_MOBbgImgPos
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
																hov_MOBbgImgPos
															) =>
																setAttributes({
																	[`hov_MOB${controlName}bgImgPos`]:
																		hov_MOBbgImgPos,
																})
															}
														/>
													</WithResButtons>

													{hov_MOBbgImgPos ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	hov_MOBbgImgcustomPosXUnit
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
																	hov_MOBbgImgcustomPosXUnit
																) =>
																	setAttributes(
																		{
																			[`hov_MOB${controlName}bgImgcustomPosXUnit`]:
																				hov_MOBbgImgcustomPosXUnit,
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
																		hov_MOBbgImgcustomPosX
																	}
																	min={0}
																	max={
																		hov_MOBbgImgcustomPosXUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	onChange={(
																		hov_MOBbgImgcustomPosX
																	) =>
																		setAttributes(
																			{
																				[`hov_MOB${controlName}bgImgcustomPosX`]:
																					hov_MOBbgImgcustomPosX,
																			}
																		)
																	}
																/>
															</WithResButtons>

															<UnitControl
																selectedUnit={
																	hov_MOBbgImgcustomPosYUnit
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
																	hov_MOBbgImgcustomPosYUnit
																) =>
																	setAttributes(
																		{
																			[`hov_MOB${controlName}bgImgcustomPosYUnit`]:
																				hov_MOBbgImgcustomPosYUnit,
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
																		hov_MOBbgImgcustomPosY
																	}
																	min={0}
																	max={
																		hov_MOBbgImgcustomPosYUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		hov_MOBbgImgcustomPosYUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		hov_MOBbgImgcustomPosY
																	) =>
																		setAttributes(
																			{
																				[`hov_MOB${controlName}bgImgcustomPosY`]:
																					hov_MOBbgImgcustomPosY,
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
															hov_bgImgAttachment
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
															hov_bgImgAttachment
														) =>
															setAttributes({
																[`hov_${controlName}bgImgAttachment`]:
																	hov_bgImgAttachment,
															})
														}
													/>

													{hov_bgImgAttachment ===
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
																hov_MOBbgImgRepeat
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
																hov_MOBbgImgRepeat
															) =>
																setAttributes({
																	[`hov_MOB${controlName}bgImgRepeat`]:
																		hov_MOBbgImgRepeat,
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
																hov_MOBbackgroundSize
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
																hov_MOBbackgroundSize
															) =>
																setAttributes({
																	[`hov_MOB${controlName}backgroundSize`]:
																		hov_MOBbackgroundSize,
																})
															}
														/>
													</WithResButtons>

													{hov_MOBbackgroundSize ===
														"custom" && (
														<>
															<UnitControl
																selectedUnit={
																	hov_MOBbgImgCustomSizeUnit
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
																	hov_MOBbgImgCustomSizeUnit
																) =>
																	setAttributes(
																		{
																			[`hov_MOB${controlName}bgImgCustomSizeUnit`]:
																				hov_MOBbgImgCustomSizeUnit,
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
																		hov_MOBbgImgCustomSize
																	}
																	min={0}
																	max={
																		hov_MOBbgImgCustomSizeUnit ===
																		"px"
																			? 2000
																			: 100
																	}
																	step={
																		hov_MOBbgImgCustomSizeUnit ===
																		"px"
																			? 1
																			: 0.1
																	}
																	onChange={(
																		hov_MOBbgImgCustomSize
																	) =>
																		setAttributes(
																			{
																				[`hov_MOB${controlName}bgImgCustomSize`]:
																					hov_MOBbgImgCustomSize,
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

					{hov_backgroundType === "gradient" && (
						<GradientColorControl
							gradientColor={hov_gradientColor}
							onChange={(hov_gradientColor) =>
								setAttributes({
									[`hov_${controlName}gradientColor`]:
										hov_gradientColor,
								})
							}
						/>
					)}
					{!noTransition && (
						<RangeControl
							label={__(
								"Background Transition",
								"essential-blocks"
							)}
							value={bg_transition}
							min={0}
							max={5}
							step={0.1}
							onChange={(bg_transition) =>
								setAttributes({
									[`${controlName}bg_transition`]:
										bg_transition,
								})
							}
						/>
					)}
				</>
			)}
		</>
	);
}
