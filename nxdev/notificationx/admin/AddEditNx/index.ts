import { GetTemplate } from '../../frontend/themes'
import { addFilter } from '@wordpress/hooks'

export { default as EditNx } from './EditNx'
export { default as CreateNx } from './CreateNx'
export { default as EditNotification } from './EditNotification'
export { default as AddNewNotification } from './AddNewNotification'

addFilter('nx_adv_template_default', 'notificationx', GetTemplate);
