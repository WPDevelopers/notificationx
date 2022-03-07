import { useEffect, useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import ServerSideRender from "@wordpress/server-side-render";
import {
  BlockControls,
  AlignmentToolbar,
  RichText,
  useBlockProps,
  InspectorControls,
} from "@wordpress/block-editor";
import apiFetch from "@wordpress/api-fetch";
import { select } from "@wordpress/data";
import Inspector from "./inspector";

import { NX_TYPOGRAPHY } from "./typographyConstants";
import { NX_MARGIN, NX_PADDING, NX_BORDER } from "./constants";

const {
  // classnames,
  softMinifyCssStrings,
  generateTypographyStyles,
  generateDimensionsControlStyles,
  generateBorderShadowStyles,
  // generateResponsiveRangeStyles,
  // generateBackgroundControlStyles,
  duplicateBlockIdFix,
} = window.EBControls;

export default function Edit(props) {
  const { attributes, setAttributes, clientId, isSelected, className } = props;

  const {
    resOption,
    blockId,
    blockMeta,
    nxTextAlign,
    nxWrapperAlign,
    nxColor,
    nxBgColor,
    nxLinkColor,
  } = attributes;

  const editorStoreForGettingPreivew =
    nx_style_handler.editor_type === "edit-site"
      ? "core/edit-site"
      : "core/edit-post";

  // this useEffect is for setting the resOption attribute to desktop/tab/mobile depending on the added 'eb-res-option-' class
  useEffect(() => {
    const bodyClasses = document.body.className;
    setAttributes({
      resOption: select(
        editorStoreForGettingPreivew
      ).__experimentalGetPreviewDeviceType(),
    });
  }, []);
  // this useEffect is for creating a unique id for each block's unique className by a random unique number
  useEffect(() => {
    const BLOCK_PREFIX = "notificationx-inline";
    duplicateBlockIdFix({
      BLOCK_PREFIX,
      blockId,
      setAttributes,
      select,
      clientId,
    });
  }, []);

  // typography styles
  const {
    typoStylesDesktop: titleTypographyDesktop,
    typoStylesTab: titleTypographyTab,
    typoStylesMobile: titleTypographyMobile,
  } = generateTypographyStyles({
    attributes,
    prefixConstant: NX_TYPOGRAPHY,
    // defaultFontSize: 18,
  });
  /* Wrapper Margin */
  const {
    dimensionStylesDesktop: wrapperMarginDesktop,
    dimensionStylesTab: wrapperMarginTab,
    dimensionStylesMobile: wrapperMarginMobile,
  } = generateDimensionsControlStyles({
    controlName: NX_MARGIN,
    styleFor: "margin",
    attributes,
  });
  /* Wrapper Padding */
  const {
    dimensionStylesDesktop: wrapperPaddingDesktop,
    dimensionStylesTab: wrapperPaddingTab,
    dimensionStylesMobile: wrapperPaddingMobile,
  } = generateDimensionsControlStyles({
    controlName: NX_PADDING,
    styleFor: "padding",
    attributes,
  });
  // wrapper Border
  const {
    styesDesktop: wrapperBDShadowDesktop,
    styesTab: wrapperBDShadowTab,
    styesMobile: wrapperBDShadowMobile,
  } = generateBorderShadowStyles({
    controlName: NX_BORDER,
    attributes,
  });

  // style for Desktop
  const desktopStyles = `
    .${blockId}.notificationx-block-wrapper {
      display: flex;
      align-items: center;
      justify-content: ${nxWrapperAlign};
    }
    .${blockId}.notificationx-block-wrapper .nx-shortcode-notice {
      ${titleTypographyDesktop}
      ${wrapperMarginDesktop}
      ${wrapperPaddingDesktop}
      ${wrapperBDShadowDesktop}
			text-align: ${nxTextAlign};
      color: ${nxColor};
      background-color: ${nxBgColor};
    }
    .${blockId}.notificationx-block-wrapper .nx-shortcode-notice a {
      ${titleTypographyDesktop}
      color: ${nxLinkColor};
    }
  `;
  // style for tablet
  const tabStyles = `
    .${blockId}.notificationx-block-wrapper .nx-shortcode-notice {
      ${titleTypographyTab}
      ${wrapperMarginTab}
      ${wrapperPaddingTab}
      ${wrapperBDShadowTab}
    }
  `;
  //  style for mobile
  const mobileStyles = `
    .${blockId}.notificationx-block-wrapper .nx-shortcode-notice {
      ${titleTypographyMobile}
      ${wrapperMarginMobile}
      ${wrapperPaddingMobile}
      ${wrapperBDShadowMobile}
    }
  `;

  // all css styles for large screen width (desktop/laptop) in strings ⬇
  const desktopAllStyles = softMinifyCssStrings(`${desktopStyles}`);
  // all css styles for Tab in strings ⬇
  const tabAllStyles = softMinifyCssStrings(`${tabStyles}`);
  // all css styles for Mobile in strings ⬇
  const mobileAllStyles = softMinifyCssStrings(`${mobileStyles}`);

  // Set All Style in "blockMeta" Attribute
  useEffect(() => {
    const styleObject = {
      desktop: desktopAllStyles,
      tab: tabAllStyles,
      mobile: mobileAllStyles,
    };
    if (JSON.stringify(blockMeta) != JSON.stringify(styleObject)) {
      setAttributes({ blockMeta: styleObject });
    }
  }, [attributes]);

  return (
    <>
      <Inspector attributes={attributes} setAttributes={setAttributes} />
      <div {...useBlockProps()}>
        <style>
          {`
            ${desktopAllStyles}

            /* mimmikcssStart */

            ${resOption === "Tablet" ? tabAllStyles : " "}
            ${resOption === "Mobile" ? tabAllStyles + mobileAllStyles : " "}

            /* mimmikcssEnd */

            @media all and (max-width: 1024px) {	

              /* tabcssStart */			
              ${softMinifyCssStrings(tabAllStyles)}
              /* tabcssEnd */			
            
            }
            
            @media all and (max-width: 767px) {
              
              /* mobcssStart */			
              ${softMinifyCssStrings(mobileAllStyles)}
              /* mobcssEnd */			
            
            }
				  `}
        </style>
        <ServerSideRender
          block={"notificationx-pro/notificationx-render"}
          attributes={{ nx_id: attributes.nx_id }}
          className={`notificationx-block-wrapper ${blockId}`}
        />
      </div>
    </>
  );
}
