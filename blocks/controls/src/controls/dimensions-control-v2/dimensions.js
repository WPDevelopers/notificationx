// const { useEffect, useState } = wp.element;
// const { dispatch } = wp.data;

import { useEffect, useState } from "@wordpress/element";
import { dispatch } from "@wordpress/data";

import {
	handleDesktopBtnClick,
	handleTabBtnClick,
	handleMobileBtnClick,
} from "../../helpers";

export default function DimensionsControl({
	top,
	right,
	bottom,
	left,
	onChange,
	neededProps,
	disableLeftRight,
}) {
	const [dimensions, setDimensions] = useState({
		top,
		right,
		bottom,
		left,
	});

	const {
		baseLabel,
		resOption,
		forBorderRadius,
		setAttributes,
		dimensionIsLinked,
		controlName,
	} = neededProps;

	const [isLinked, setIsLinked] = useState(dimensionIsLinked);

	const onButtonClick = () => setIsLinked(!isLinked);

	const onInputChange = (event) => {
		let { name, value } = event.target;
		if (isLinked) {
			setDimensions({
				top: value,
				right: value,
				bottom: value,
				left: value,
			});
		} else {
			setDimensions((prevDimensions) => ({
				...prevDimensions,
				[name]: value,
			}));
		}
	};

	useEffect(() => {
		onChange(dimensions);
	}, [dimensions]);

	useEffect(() => {
		setAttributes({
			[`${controlName}isLinked`]: isLinked,
		});
		const { top: value } = dimensions;
		if (isLinked) {
			setDimensions({
				top: value,
				right: value,
				bottom: value,
				left: value,
			});
		}
	}, [isLinked]);

	return (
		<div className="dimention-container">
			<div className="withResWrapperInDimension">
				<div className="dimention-label">{baseLabel}</div>
				<span
					onClick={() =>
						handleDesktopBtnClick({
							setAttributes,
							setPreviewDeviceType:
								dispatch("core/edit-post")
									.__experimentalSetPreviewDeviceType,
						})
					}
					className={`typoResButton dashicons dashicons-desktop ${
						resOption === "Desktop" ? "active" : " "
					}`}></span>
				<span
					onClick={() =>
						handleTabBtnClick({
							setAttributes,
							setPreviewDeviceType:
								dispatch("core/edit-post")
									.__experimentalSetPreviewDeviceType,
						})
					}
					className={`typoResButton dashicons dashicons-tablet ${
						resOption === "Tablet" ? "active" : " "
					}`}></span>
				<span
					onClick={() =>
						handleMobileBtnClick({
							setAttributes,
							setPreviewDeviceType:
								dispatch("core/edit-post")
									.__experimentalSetPreviewDeviceType,
						})
					}
					className={`typoResButton dashicons dashicons-smartphone ${
						resOption === "Mobile" ? "active" : " "
					}`}></span>
			</div>

			<div
				className={`input-container ${
					disableLeftRight ? "left-right-disabled" : ""
				}`}>
				<div className="input-wrapper">
					<input
						type="number"
						name="top"
						value={dimensions.top}
						onChange={onInputChange}
					/>
					<label className="dimentions-input-label">
						{forBorderRadius ? " " : "Top"}
					</label>
				</div>
				{/* {!disableLeftRight && ( */}
				<div className="input-wrapper">
					<input
						type={disableLeftRight ? "text" : "number"}
						name="right"
						// value={disableLeftRight ? "auto" : dimensions.right}
						value={disableLeftRight ? undefined : dimensions.right}
						disabled={disableLeftRight ? true : false}
						onChange={onInputChange}
					/>
					<label className="dimentions-input-label">
						{forBorderRadius ? " " : "Right"}
					</label>
				</div>
				{/* )} */}
				<div className="input-wrapper">
					<input
						type="number"
						name="bottom"
						value={dimensions.bottom}
						onChange={onInputChange}
					/>
					<label className="dimentions-input-label">
						{forBorderRadius ? " " : "Bottom"}
					</label>
				</div>
				{/* {!disableLeftRight && ( */}
				<div className="input-wrapper">
					<input
						type={disableLeftRight ? "text" : "number"}
						name="left"
						// value={disableLeftRight ? "auto" : dimensions.left}
						value={disableLeftRight ? undefined : dimensions.left}
						disabled={disableLeftRight ? true : false}
						onChange={onInputChange}
					/>
					<label className="dimentions-input-label">
						{forBorderRadius ? " " : "Left"}
					</label>
				</div>
				{/* )} */}
				<button
					className={`linked-btn components-button is-button dashicons dashicons-${
						isLinked
							? "admin-links is-primary"
							: "editor-unlink is-default"
					}`}
					onClick={onButtonClick}
				/>
			</div>
		</div>
	);
}
