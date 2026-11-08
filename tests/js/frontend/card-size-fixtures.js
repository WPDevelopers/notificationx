/**
 * Card size presets (84708): the in-scope themes and a typical notification
 * for each, shared by the golden snapshots (card-size-golden.test.js), the
 * card-size unit tests and the screenshot tool (tests/visual/).
 *
 * In scope: free's own Sales, Comments, Reviews and Download stats themes
 * (not the Pro-locked ones). Keys are the saved `themes` values.
 */

export const CARD_SIZE_FAMILIES = {
	sales: [
		'conversions_theme-one',
		'conversions_theme-two',
		'conversions_theme-three',
		'conversions_theme-five',
		'conversions_conv-theme-twelve',
		'conversions_conv-theme-thirteen',
		'conversions_conv-theme-fifteen',
		'woocommerce_sales_theme-one',
		'woocommerce_sales_theme-two',
		'woocommerce_sales_theme-three',
		'woocommerce_sales_theme-five',
		'woocommerce_sales_conv-theme-twelve',
		'woocommerce_sales_conv-theme-thirteen',
		'woocommerce_sales_conv-theme-fifteen',
	],
	comments: [
		'comments_theme-one',
		'comments_theme-two',
		'comments_theme-three',
		'comments_theme-six-free',
		'comments_theme-seven-free',
		'comments_theme-eight-free',
	],
	reviews: [
		'reviews_total-rated',
		'reviews_reviewed',
		'reviews_review_saying',
		'reviews_review-comment',
		'reviews_review-comment-2',
		'reviews_review-comment-3',
	],
	downloads: [
		'download_stats_today-download',
		'download_stats_7day-download',
		'download_stats_actively_using',
		'download_stats_total-download',
	],
};

export const CARD_SIZE_THEMES = Object.values( CARD_SIZE_FAMILIES ).flat();

const IMAGE = { url: 'img.png', alt: 'Product' };

// [ type, source, notification-template, entry ] per family.
const FAMILY = {
	sales: ( themes ) => [
		themes.startsWith( 'woocommerce_sales_' ) ? 'woocommerce_sales' : 'conversions',
		themes.startsWith( 'woocommerce_sales_' ) ? 'woocommerce_sales' : 'woocommerce',
		{
			first_param: 'tag_name',
			custom_first_param: 'Someone',
			second_param: 'just purchased',
			third_param: 'tag_product_title',
			custom_third_param: 'Anonymous Product',
			fourth_param: 'tag_time',
			custom_fourth_param: 'Some time ago',
		},
		{ name: 'Jane D.', title: 'Organic Cotton T-shirt', updated_at: '2026-10-01 00:00:00', image_data: IMAGE },
	],
	comments: () => [
		'comments',
		'wp_comments',
		{
			first_param: 'tag_name',
			second_param: 'commented on',
			third_param: 'tag_post_title',
			fourth_param: 'tag_time',
		},
		{ name: 'Jane D.', post_title: 'How to brew a better cup of coffee', updated_at: '2026-10-01 00:00:00', image_data: IMAGE },
	],
	reviews: () => [
		'reviews',
		'wp_reviews',
		{
			first_param: 'tag_username',
			second_param: 'just reviewed',
			third_param: 'tag_plugin_name',
			fourth_param: 'tag_rating',
			review_fourth_param: 'tag_plugin_review',
		},
		{
			username: 'jane',
			rated: 120,
			plugin_name: 'NotificationX',
			plugin_review: 'Great plugin, easy to set up and it works.',
			rating: 5,
			updated_at: '2026-10-01 00:00:00',
			image_data: IMAGE,
		},
	],
	downloads: () => [
		'download_stats',
		'wp_stats',
		{
			first_param: 'tag_plugin_theme_name',
			second_param: 'has been downloaded',
			third_param: 'tag_today',
			fourth_param: 'tag_today_text',
		},
		{ plugin_theme_name: 'NotificationX', today: 1290, today_text: 'times today', image_data: IMAGE },
	],
};

export const familyOf = ( themes ) =>
	Object.keys( CARD_SIZE_FAMILIES ).find( ( family ) => CARD_SIZE_FAMILIES[ family ].includes( themes ) );

/**
 * A notification's settings and entry for a theme key. `extra` is merged into
 * the settings (e.g. { card_size: 'compact', disable_powered_by: true }).
 * The caller fills `template` (GetTemplate) so the free code under test makes it.
 */
export const cardCase = ( themes, extra = {} ) => {
	const [ type, source, template, data ] = FAMILY[ familyOf( themes ) ]( themes );
	return {
		config: {
			nx_id: 1,
			id: 1,
			type,
			source,
			themes,
			position: 'bottom_left',
			close_button: true,
			close_button_tab: true,
			close_button_mobile: true,
			image_shape: 'circle',
			size: { desktop: 500, tablet: 500, mobile: 500 },
			display_for: 5,
			affiliate_link: 'https://notificationx.com/',
			'notification-template': template,
			...extra,
		},
		data: { ...data },
	};
};
