//
export const giveClassForResOptionChange = (domObj, resOption) => {
  const bodyClassNameString = domObj.body.className;

  // console.log("=======className: ", { bodyClassNameString, resOption });

  const regexPattern =
    /eb\-responsive\-preview\-option\-(Tablet|Mobile|Desktop)/g;

  const newBodyClass = (bodyClassNameString || " ").replace(regexPattern, "");

  domObj.body.className = newBodyClass;

  domObj.body.classList.add(`eb-responsive-preview-option-${resOption}`);
};

//
// function to mimmik css when clicking the responsive buttons in the inspector panel
export const mimmikCssForResBtns = ({
  isForPreviewButton = false,
  domObj,
  resOption,
}) => {
  let allEbBlocksWrapper;

  giveClassForResOptionChange(domObj, resOption);

  if (isForPreviewButton) {
    allEbBlocksWrapper = domObj.querySelectorAll(
      ".eb-guten-block-main-parent-wrapper > style"
    );
  } else {
    allEbBlocksWrapper = domObj.querySelectorAll(
      ".eb-guten-block-main-parent-wrapper:not(.is-selected) > style"
    );
  }

  if (allEbBlocksWrapper.length < 1) return;
  allEbBlocksWrapper.forEach((styleTag) => {
    const cssStrings = styleTag.textContent;
    const minCss = cssStrings.replace(/\s+/g, " ");
    const regexCssMimmikSpace =
      /(mimmikcssStart\s\*\/)(.+)(\/\*\smimmikcssEnd)/i;
    let newCssStrings = " ";
    if (resOption === "Tablet") {
      const tabCssStrings = (minCss.match(
        /tabcssStart\s\*\/(.+)(?=\/\*\stabcssEnd)/i
      ) || [, " "])[1];
      newCssStrings = minCss.replace(
        regexCssMimmikSpace,
        `$1 ${tabCssStrings} $3`
      );
    } else if (resOption === "Mobile") {
      const tabCssStrings = (minCss.match(
        /tabcssStart\s\*\/(.+)(?=\/\*\stabcssEnd)/i
      ) || [, " "])[1];

      const mobCssStrings = (minCss.match(
        /mobcssStart\s\*\/(.+)(?=\/\*\smobcssEnd)/i
      ) || [, " "])[1];

      newCssStrings = minCss.replace(
        regexCssMimmikSpace,
        `$1 ${tabCssStrings} ${mobCssStrings} $3`
      );
    } else {
      newCssStrings = minCss.replace(regexCssMimmikSpace, `$1  $3`);
    }
    styleTag.textContent = newCssStrings;
  });
};

//
// IMPORTANT: The following fuction declaration must be below the 'mimmikCssForResBtns' function declaration
// function to mimmik css for responsive preview when clicking the buttons in the 'Preview button of wordpress' located beside the 'update' button
export const mimmikCssForPreviewBtnClick = ({ domObj, select }) => {
  const bodyClasses = domObj.body.className;
  if (/eb\-mimmik\-added/i.test(bodyClasses)) return;
  domObj.body.classList.add("eb-mimmik-added");
  domObj.body.classList.add(`eb-responsive-preview-option-Desktop`);

  const wpResBtnsWrap = domObj.querySelector(
    ".block-editor-page .popover-slot"
  );
  wpResBtnsWrap.addEventListener("click", (e) => {
    if (
      /block\-editor\-post\-preview__button\-resize|components\-menu\-item__item/i.test(
        e.target.className
      )
    ) {
      setTimeout(() => {
        const resOption =
          select("core/edit-post").__experimentalGetPreviewDeviceType();
        mimmikCssForResBtns({
          isForPreviewButton: true,
          domObj,
          resOption,
        });
        giveClassForResOptionChange(domObj, resOption);
      }, 0);
    }
  });
};

// IMPORTANT: The following fuction declaration must be below the 'mimmikCssForResBtns' function declaration
// function to mimmik css for responsive preview when clicking the buttons in the 'Preview button of wordpress' located beside the 'update' button while any block is selected and it's inspector panel is mounted in the DOM
export const mimmikCssOnPreviewBtnClickWhileBlockSelected = ({
  domObj = { body: { classList: { add: () => {} } } },
  select,
  setAttributes,
}) => {
  const wpResBtnsWrap = domObj.querySelector(
    ".block-editor-page .popover-slot"
  );

  const handleCLick = (e) => {
    if (
      /block\-editor\-post\-preview__button\-resize|components\-menu\-item__item/i.test(
        e.target.className
      )
    ) {
      setTimeout(() => {
        const resOption =
          select("core/edit-post").__experimentalGetPreviewDeviceType();
        mimmikCssForResBtns({
          isForPreviewButton: true,
          domObj,
          resOption,
        });
        setAttributes({ resOption });
        giveClassForResOptionChange(domObj, resOption);
      }, 0);
    }
  };

  wpResBtnsWrap.addEventListener("click", handleCLick);

  return () => {
    wpResBtnsWrap.removeEventListener("click", handleCLick);
  };
};

//
// this function is for creating a unique blockId for each block's unique className
export const duplicateBlockIdFix = ({
  BLOCK_PREFIX,
  blockId,
  setAttributes,
  select,
  clientId,
}) => {
  const unique_id =
    BLOCK_PREFIX + "-" + Math.random().toString(36).substr(2, 7);

  /**
   * Define and Generate Unique Block ID
   */
  if (!blockId) {
    setAttributes({ blockId: unique_id });
  }

  /**
   * Assign New Unique ID when duplicate BlockId found
   * Mostly happens when User Duplicate a Block
   */

  const all_blocks = select("core/block-editor").getBlocks();

  let duplicateFound = false;
  const fixDuplicateBlockId = (blocks) => {
    if (duplicateFound) return;
    for (const item of blocks) {
      const { innerBlocks } = item;
      if (item.attributes.blockId === blockId) {
        if (item.clientId !== clientId) {
          setAttributes({ blockId: unique_id });
          duplicateFound = true;
          return;
        } else if (innerBlocks.length > 0) {
          fixDuplicateBlockId(innerBlocks);
        }
      } else if (innerBlocks.length > 0) {
        fixDuplicateBlockId(innerBlocks);
      }
    }
  };

  fixDuplicateBlockId(all_blocks);
};
