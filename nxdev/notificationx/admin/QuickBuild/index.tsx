import React from 'react';
import { addFilter } from '@wordpress/hooks'

import Finalize from './Finalize';
import Launch from './Launch';

addFilter('nx_quick_build_finalize', 'notificationx', () => <Finalize />)
addFilter('nx_quick_build_launch', 'notificationx', (props) => <Launch {...props}/>)

export { default as QuickBuild } from "./QuickBuild";
export { default as QuickBuildWrapper } from "./QuickBuildWrapper";
export { default as Finalize } from "./Finalize";
