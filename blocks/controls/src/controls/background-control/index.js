/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { ToggleControl } from "@wordpress/components";

/**
 * Internal dependencies
 */
import OverlayControl from "./overlayControl";
import BgControl from "./bgControl";

export default function BackgroundControl({
	resRequiredProps,
	controlName,
	noOverlay = false,
	noMainBgi = false,
	noOverlayBgi = false,
	noTransition = false,

	//
	forButton = false,
}) {
	const { setAttributes, attributes } = resRequiredProps;

	const { [`${controlName}isBgOverlay`]: isBgOverlay } = attributes;

	return (
		<>
			<BgControl
				resRequiredProps={resRequiredProps}
				controlName={controlName}
				noMainBgi={forButton === true ? true : noMainBgi}
				noTransition={noTransition}
			/>

			{noOverlay === false && forButton == false && (
				<>
					<hr />

					<ToggleControl
						label={__("Enable Overlay", "essential-blocks")}
						checked={isBgOverlay}
						onChange={() =>
							setAttributes({
								[`${controlName}isBgOverlay`]: !isBgOverlay,
							})
						}
					/>

					{isBgOverlay && (
						<OverlayControl
							resRequiredProps={resRequiredProps}
							controlName={controlName}
							noOverlayBgi={noOverlayBgi}
							noTransition={noTransition}
						/>
					)}
				</>
			)}
		</>
	);
}
