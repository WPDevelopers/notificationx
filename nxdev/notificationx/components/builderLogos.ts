// Integration logos from assets/admin/images/extensions/sources, exposed as CSS
// variables so the builder styles can show them on the notification type cards.
const TYPE_CARD_LOGOS = ['woocommerce', 'wordpress', 'wp-comments', 'google-analytics', 'youtube', 'custom', 'mailchimp', 'give'];

export const builderLogoVars = (adminAssets = ''): Record<string, string> => {
    const base = adminAssets.replace(/\/+$/, '');
    return TYPE_CARD_LOGOS.reduce((vars, name) => {
        vars[`--nx-logo-${name}`] = `url("${base}/images/extensions/sources/${name}.png")`;
        return vars;
    }, {} as Record<string, string>);
};
