import injectEBLogo from "./injectEBLogo";

const { apiFetch } = wp;
const { parse } = wp.blocks;

window.onload = function () {
	injectEBLogo();

	document
		.querySelector("#eb-export-icon")
		.addEventListener("click", function (e) {
			e.preventDefault();

			const title = wp.data
				.select("core/editor")
				.getEditedPostAttribute("title");
			const content = wp.data.select("core/editor").getEditedPostContent();
			const id = wp.data.select("core/editor").getCurrentPostId();
			const type = wp.data.select("core/editor").getCurrentPostType();
			const isEditedPostEmpty = wp.data
				.select("core/editor")
				.isEditedPostEmpty();
			const { insertBlocks } = wp.data.dispatch("core/block-editor");

			const data = { title: title, content: content };

			if (!isEditedPostEmpty) {
				// Store in database then send data in cloud
				// wp.data.dispatch("core/editor").savePost();

				// test item for insert block
				const item =
					'<!-- wp:paragraph {"eb":{"id":"112165356402"}} -->\r\n<p>TEST world</p>\r\n<!-- /wp:paragraph -->\r\n\r\n<!-- wp:essential-blocks/block-notice {"noticeId":"a9f00","eb":{"id":"21110354946"}} -->\r\n<div class="wp-block-essential-blocks-block-notice eb-notice-wrapper" style="background:#3074ff;padding:65px 60px;box-shadow:0px 0px 0px 0px #000000;border-radius:5px" data-id="a9f00" data-show-again="false"><div class="eb-notice-title-wrapper" style="display:flex;justify-content:space-between"><div class="eb-notice-title" style="font-size:32px;color:#fff">Save 20%</div><span class="eb-notice-dismiss" style="color:#fff;display:none;justify-content:center;width:24px;height:24px;cursor:pointer;align-items:center"></span></div><div><div class="eb-notice-text" style="font-size:18px;color:#edf1f7">Free shipping on all orders</div></div></div>\r\n<!-- /wp:essential-blocks/block-notice -->';
				insertBlocks(parse(item));
				return;

				apiFetch({
					path: `/wp/v2/${type}s/${id}`,
					method: "POST",
					// data: data
					data: { title: title, content: item },
				}).then((res) => {
					const postData = {
						__file: "wp_block",
						...data,
					};
				});
			}
		});
};
