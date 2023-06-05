/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { useState, useEffect } from "@wordpress/element";
import { InspectorControls, PanelColorSettings } from "@wordpress/block-editor";
import apiFetch from "@wordpress/api-fetch";
import { applyFilters } from '@wordpress/hooks';
import Select from 'react-select/async';

import {
  PanelBody,
  SelectControl,
  // ToggleControl,
  // TextControl,
  // TextareaControl,
  Button,
  ButtonGroup,
  BaseControl,
  TabPanel,
} from "@wordpress/components";
import { select } from "@wordpress/data";

/**
 * Internal depencencies
 */
import {
  NX_MARGIN,
  NX_PADDING,
  NX_BORDER,
  UNIT_TYPES,
  TEXT_ALIGN,
  WRAPPER_ALIGN,
} from "./constants";
import { NX_TYPOGRAPHY } from "./typographyConstants";
const {
  // faIcons,
  ResponsiveDimensionsControl,
  TypographyDropdown,
  BorderShadowControl,
  // ResponsiveRangeController,
  // BackgroundControl,
} = window.EBControls;

import objAttributes from "./attributes";

export default function Inspector(props) {
  const { attributes, setAttributes } = props;
  const {
    resOption,
    blockId,
    blockRoot,
    blockMeta,
    nx_id,
    nxColor,
    nxBgColor,
    nxLinkColor,
    nxTextAlign,
    nxWrapperAlign,
    product_id,
    selected_product,
    post_type,
  } = attributes;
  const [nx_ids, set_nx_ids] = useState(null);

  const editorStoreForGettingPreivew =
    nx_style_handler.editor_type === "edit-site"
      ? "core/edit-site"
      : "core/edit-post";

  // this useEffect is for setting the resOption attribute to desktop/tab/mobile depending on the added 'eb-res-option-' class only the first time once
  useEffect(() => {
    setAttributes({
      resOption: select(
        editorStoreForGettingPreivew
      ).__experimentalGetPreviewDeviceType(),
    });
  }, []);

  // Get current post type for Site Editor Template from URL
  const urlParams = new URLSearchParams(window.location.search);
  const postType = urlParams.get('postType');
  attributes.post_type = postType;

  // All woocommerce product
  const [nx_products,set_nx_products] = useState(null);

  // Notification type state handler
  const [nx_type,set_nx_type] = useState(null);
  const [nx_source,set_nx_source] = useState(null);
  const [nx_no_options,set_nx_no_options] = useState(null);

  const resRequiredProps = {
    setAttributes,
    resOption,
    attributes,
    objAttributes,
  };

  useEffect(() => {
    // set current notification type
    set_nx_type( nx_ids ? nx_ids.filter( (item) => item.value == nx_id )[0]?.type : null );
    set_nx_source( nx_ids ? nx_ids.filter( (item) => item.value == nx_id )[0]?.source : null );
    if ( nx_source ) {
      const data = {
        "search_empty" : true,
        "type": "inline",
        "source": nx_source,
        "field": "product_list"
      };
      apiFetch({ path: "notificationx/v1/get-data", method: "POST", data: data }).then((res) => {
        set_nx_products([
          { label: __("Select", "notificationx"), value: "" },
          ...res,
        ]);
      });
    }
  },[nx_ids,nx_id,nx_source]);

  useEffect(() => {
    apiFetch({ path: "notificationx/v1/nx?per_page=999", method: "GET", }).then((res) => {
      let ids = [];
      if (res?.posts?.length > 0) {
        ids = res.posts
          .filter((item) => item.enabled && ( ( postType == 'wp_template' && item.source != 'edd_inline' && item.source != 'tutor_inline' && item.source != 'learndash_inline' ) || ( postType === null && item.source != "press_bar") ))
          .map((item) => ({
            label: item?.title || item?.nx_id,
            value: item?.nx_id,
            type: item.type,
            source: item.source,
          }));
      }
      set_nx_ids([
        { label: __("Select", "notificationx"), value: "" },
        ...ids,
      ]);

    });

  }, []);

  const loadOptions = async (inputValue, callback) => {
    if (inputValue.length >= 3) {
      const data = {
        "search_empty" : true,
        "inputValue" : inputValue,
        "type": "inline",
        "source": nx_source,
        "field": "product_list"
      };;
      await apiFetch({ path: "notificationx/v1/get-data", method: "POST", data: data }).then((res) => {
        if( res ) {
          callback(res);
          set_nx_no_options('No Result Found'); 
        }else{
        }
      });
    }else{
      set_nx_no_options('Please type 3 or more characters'); 
    }
  };

  return (
    <InspectorControls key="controls">
      <div className="eb-panel-control">
        <TabPanel
          className="eb-parent-tab-panel"
          activeClass="active-tab"
          tabs={[
            {
              name: "general",
              title: "General",
              className: "eb-tab general",
            },
            {
              name: "styles",
              title: "Style",
              className: "eb-tab styles",
            },
          ]}
        >
          {(tab) => (
            <div className={"eb-tab-controls" + tab.name}>
              {tab.name === "general" && (
                <div className="eb-panel-control">
                  <div className="nx-panel-body-default">
                    <SelectControl
                      label={__("Choose Notification", "notificationx")}
                      value={nx_id}
                      options={nx_ids}
                      onChange={(selected) =>
                        setAttributes({ nx_id: selected })
                      }
                    />
                    {nx_type === 'inline' && postType !== 'wp_template' &&
                      <>
                        {
                          nx_source == 'tutor_inline' || nx_source == 'learndash_inline' ? (
                            <label htmlFor="chooseOption">{ __( 'Choose Course','notificationx' ) }</label>
                          ) : (
                            <label htmlFor="chooseOption">{ __( 'Choose Product','notificationx' ) }</label>
                          )}
                        <Select
                          value={ selected_product }
                          id="chooseOption"
                          loadOptions={loadOptions}
                          defaultOptions={nx_products}
                          noOptionsMessage={ () => __( nx_no_options,'notificationx' ) }
                          onChange={ (selected) => {
                            setAttributes( { product_id: selected.value + '' } )
                            delete selected.rules;
                            setAttributes( { selected_product:  selected } )
                          }
                          }
                        />
                      </>
                    }
                    {nx_ids && nx_ids.length <= 1 ? (
                      <>
                        <p className="nx-block-no-nx-notice">
                          <b>{__("Note: ", "notificationx")}</b>
                          {__(
                            "You have no notification enabled. Please Enable notifications to get here.",
                            "notificationx"
                          )}
                        </p>
                      </>
                    ) : (
                      <></>
                    )}
                    <div style={{ marginBottom: 20 }}>
                      <BaseControl label={__("Alignment", "notificationx")}>
                        <ButtonGroup id="eb-advance-heading-alignment">
                          {WRAPPER_ALIGN.map((item) => (
                            <Button
                              key={item.value}
                              isPrimary={nxWrapperAlign === item.value}
                              isSecondary={nxWrapperAlign !== item.value}
                              onClick={() =>
                                setAttributes({
                                  nxWrapperAlign: item.value,
                                })
                              }
                            >
                              {item.label}
                            </Button>
                          ))}
                        </ButtonGroup>
                      </BaseControl>
                      <a href="https://notificationx.com/docs/notificationx-inline-notification-in-gutenberg/" target="_blank">Need Help?</a>
                    </div>
                  </div>
                </div>
              )}
              {tab.name === "styles" && (
                <div className="eb-panel-control">
                  <div className="nx-panel-body-default">
                    <div style={{ marginBottom: 15 }}>
                      <TypographyDropdown
                        baseLabel={__("Typography", "notificationx")}
                        typographyPrefixConstant={NX_TYPOGRAPHY}
                        resRequiredProps={resRequiredProps}
                      />
                    </div>
                    <div style={{ marginBottom: 20 }}>
                      <BaseControl label={__("Alignment", "notificationx")}>
                        <ButtonGroup id="eb-advance-heading-alignment">
                          {TEXT_ALIGN.map((item) => (
                            <Button
                              isPrimary={nxTextAlign === item.value}
                              isSecondary={nxTextAlign !== item.value}
                              onClick={() =>
                                setAttributes({
                                  nxTextAlign: item.value,
                                })
                              }
                            >
                              {item.label}
                            </Button>
                          ))}
                        </ButtonGroup>
                      </BaseControl>
                    </div>
                    <div style={{ marginBottom: 20 }}>
                      <ResponsiveDimensionsControl
                        resRequiredProps={resRequiredProps}
                        controlName={NX_PADDING}
                        baseLabel="Padding"
                      />
                    </div>
                    <div style={{ marginBottom: 20 }}>
                      <ResponsiveDimensionsControl
                        resRequiredProps={resRequiredProps}
                        controlName={NX_MARGIN}
                        baseLabel="Margin"
                      />
                    </div>
                    <PanelColorSettings
                      initialOpen={false}
                      className={"nx-color-control"}
                      title={__("Color", "notificationx")}
                      colorSettings={[
                        {
                          value: nxLinkColor,
                          onChange: (newColor) =>
                            setAttributes({ nxLinkColor: newColor }),
                          label: __("Link Color", "notificationx"),
                        },
                        {
                          value: nxColor,
                          onChange: (newColor) =>
                            setAttributes({ nxColor: newColor }),
                          label: __("Text Color", "notificationx"),
                        },
                        {
                          value: nxBgColor,
                          onChange: (newColor) =>
                            setAttributes({ nxBgColor: newColor }),
                          label: __("Background Color", "notificationx"),
                        },
                      ]}
                    />
                    <PanelBody
                      title={__("Border")}
                      initialOpen={false}
                      className={"nx-color-control"}
                    >
                      <BorderShadowControl
                        controlName={NX_BORDER}
                        resRequiredProps={resRequiredProps}
                        noBdrHover
                        noShdowHover
                      />
                    </PanelBody>
                  </div>
                </div>
              )}
            </div>
          )}
        </TabPanel>
      </div>
    </InspectorControls>
  );
}
