import { GetTemplate } from '../../frontend/themes'
import { isVcTheme } from '../../frontend/themes/vc/isVcTheme'
import vcCompactRows from '../../frontend/themes/vc/vcCompactRows'
import { getThemeName } from '../../frontend/core/functions'
import { addFilter } from '@wordpress/hooks'

export { default as EditNx } from './EditNx'
export { default as CreateNx } from './CreateNx'
export { default as EditNotification } from './EditNotification'
export { default as AddNewNotification } from './AddNewNotification'

// Advanced Template default. Verified Card keys return slots, not rows
// (GetTemplate's vc cases), so they get the same compact rows as Pro's mobile
// card. Every other key gets GetTemplate(v) exactly as before.
const advTemplateDefault = (v) =>
    isVcTheme(v) ? vcCompactRows(getThemeName(v), GetTemplate(v)) : GetTemplate(v);

addFilter('nx_adv_template_default', 'notificationx', advTemplateDefault);
