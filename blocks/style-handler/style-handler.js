//Generate Styles
(() => {
  /**
   * WordPress Dependencies
   */
  const { select, subscribe } = wp.data;
  const { isFunction } = lodash;
  const { parse, isReusableBlock, isTemplatePart } = wp.blocks;
  const { editor_type } = nx_style_handler;

  const makeIdOfTemplatePart = (theme, slug) =>
    theme && slug ? theme + "//" + slug : null;
  const emptyFuns = () => {};

  let ebEditGetPreviewDeviceType = false;

  if (editor_type === "edit-site") {
    //
    ebEditGetPreviewDeviceType = select("core/edit-site")
      .__experimentalGetPreviewDeviceType;
  } else if (editor_type === "edit-post") {
    //
    ebEditGetPreviewDeviceType = select("core/edit-post")
      .__experimentalGetPreviewDeviceType;
  }

  window.ebEditCurrentPreviewOption = ebEditGetPreviewDeviceType();

  const {
    isSavingPost = emptyFuns,
    isAutosavingPost = emptyFuns,
    isSavingNonPostEntityChanges = emptyFuns,
  } = select("core/editor");

  const regexForBlockNameTest = /^notificationx\-pro\//g;

  //
  const generateAndSendStyleToBackend = () => {
    const { getEditedEntityRecord = emptyFuns } = select("core");
    const { getBlocks = emptyFuns } = select("core/block-editor");
    const { getCurrentPostId = emptyFuns } = select("core/editor");
    const post_id = getCurrentPostId();
    const all_blocks = getBlocks();

    const styles = {};
    const cssObjectMaker = (blocks) => {
      for (const item of blocks) {
        const {
          attributes: { blockMeta, blockRoot, blockId },
          innerBlocks,
        } = item;
        if (isFunction(isReusableBlock) && isReusableBlock(item)) {
          const reusableBlock = getEditedEntityRecord(
            "postType",
            "wp_block",
            item.attributes.ref
          );
          const parsedBlocks = !isEmpty(reusableBlock)
            ? parse(
                isFunction(reusableBlock.content)
                  ? reusableBlock.content(reusableBlock)
                  : reusableBlock.content
              )
            : false;

          if (parsedBlocks) {
            for (const blockItem of parsedBlocks) {
              const {
                attributes: { blockMeta, blockRoot, blockId },
                innerBlocks,
              } = blockItem;
              if (blockMeta && blockRoot === "notificationx_pro") {
                styles[blockId] = blockMeta;
              }
              if (innerBlocks.length > 0) {
                cssObjectMaker(innerBlocks);
              }
            }
          }
        } else if (isFunction(isTemplatePart) && isTemplatePart(item)) {
          const { theme, slug } = item.attributes;

          const templatePartEntity = getEditedEntityRecord(
            "postType",
            "wp_template_part",
            makeIdOfTemplatePart(theme, slug)
          );

          const { blocks = [], innerBlocks = [] } = templatePartEntity;
          cssObjectMaker(blocks);
          cssObjectMaker(innerBlocks);
        } else if (blockMeta && blockRoot === "notificationx_pro") {
          styles[blockId] = blockMeta;
        }
        if (innerBlocks.length > 0) {
          cssObjectMaker(innerBlocks);
        }
      }
    };

    cssObjectMaker(all_blocks); // Call function

    const stringStyles = JSON.stringify(styles);

    jQuery.ajax({
      type: "POST",
      url: ajaxurl,
      data: {
        data: stringStyles,
        id: post_id,
        editorType: editor_type,
        action: "notificationx_pro_write_block_css",
        nonce: nx_style_handler.sth_nonce,
      },
      error: function (msg) {
        console.log(msg);
      },
    });
  };

  const callBackFuncOnPreviewChange = () => {
    if (window.ebEditCurrentPreviewOption !== ebEditGetPreviewDeviceType()) {
      const newDeviceType = ebEditGetPreviewDeviceType();

      // the following line of code should be at the top of this if statement
      window.ebEditCurrentPreviewOption = newDeviceType;

      //
      const { isFunction } = lodash;
      const {
        parse = emptyFuns,
        isReusableBlock = emptyFuns,
        isTemplatePart = emptyFuns,
      } = wp.blocks;
      const { getEditedEntityRecord = emptyFuns } = select("core");
      const {
        getBlocks = emptyFuns,
        updateBlockAttributes = emptyFuns,
      } = select("core/block-editor");

      const all_blocks = getBlocks();

      const resOptionChanger = (blocks) => {
        for (const item of blocks) {
          const { name, clientId, innerBlocks } = item;

          if (isFunction(isReusableBlock) && isReusableBlock(item)) {
            const reusableBlock = getEditedEntityRecord(
              "postType",
              "wp_block",
              item.attributes.ref
            );

            const parsedBlocks = parse(
              isFunction(reusableBlock.content)
                ? reusableBlock.content(reusableBlock)
                : reusableBlock.content
            );

            for (const blockItem of parsedBlocks) {
              const { innerBlocks, clientId, name } = blockItem;
              if (regexForBlockNameTest.test(name)) {
                updateBlockAttributes(clientId, {
                  resOption: newDeviceType,
                });
              }
              if (innerBlocks.length > 0) {
                resOptionChanger(innerBlocks);
              }
            }
          } else if (isFunction(isTemplatePart) && isTemplatePart(item)) {
            const { theme, slug } = item.attributes;

            const templatePartEntity = getEditedEntityRecord(
              "postType",
              "wp_template_part",
              makeIdOfTemplatePart(theme, slug)
            );

            const { blocks = [], innerBlocks = [] } = templatePartEntity;
            resOptionChanger(blocks);
            resOptionChanger(innerBlocks);
          } else if (regexForBlockNameTest.test(name)) {
            updateBlockAttributes(clientId, {
              resOption: newDeviceType,
            });
          }
          if (innerBlocks.length > 0) {
            resOptionChanger(innerBlocks);
          }
        }
      };

      resOptionChanger(all_blocks); // Call function
    }
  };

  //
  const callBackFuncForSubsCribeForEditPost = () => {
    if (isSavingPost() && !isAutosavingPost()) {
      generateAndSendStyleToBackend();
    }
    callBackFuncOnPreviewChange();
  };

  const callBackFuncForSubsCribeForEditSite = () => {
    if (isSavingNonPostEntityChanges()) {
      generateAndSendStyleToBackend();
    }
    callBackFuncOnPreviewChange();
  };

  if (editor_type === "edit-site") {
    //
    subscribe(callBackFuncForSubsCribeForEditSite);
  } else if (editor_type === "edit-post") {
    //
    subscribe(callBackFuncForSubsCribeForEditPost);
  }
})();

//Helper Functions
function isEmpty(obj) {
  return Object.keys(obj).length === 0;
}
